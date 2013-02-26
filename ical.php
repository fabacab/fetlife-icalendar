<?php
require_once dirname(__FILE__) . '/lib/FetLife/FetLife.php';
require_once dirname(__FILE__) . '/lib/iCalcreator/iCalcreator.class.php';

// Load configuration data.
$flical_config = parse_ini_file(dirname(__FILE__) . '/fetlife-icalendar.ini.php', true);
if (!$flical_config) {
    die("Failed to load configuration file.");
}

// Parse script options, however we got them.
// First the Web.
$web_opts = array();
if (!empty($_GET['refresh'])) {
    $web_opts['refresh'] = trim(strip_tags($_GET['refresh']));
}
// Then the command line.
$cli_opts = getopt('h', array('help', 'refresh:'));
$fin_opts = array_replace($web_opts, $cli_opts); // CLI overrides Web.

// Process options.
$places = $flical_config; // But first, copy the config array.
foreach ($fin_opts as $opt_key => $opt_val) {
    switch ($opt_key) {
        case 'h':
        case 'help':
            $usage = 'php ical.php [-h|--help] [--refresh="PLACE_1"[, --refresh="PLACE_2"]]';
            exit("$usage\n");
        case 'r':
        case 'refresh':
            // Filter out the places that don't match the refresh options.
            if (is_string($opt_val)) { $opt_val = array($opt_val); }
            $places = array_intersect_key($places, array_flip($opt_val));
            break;
    }
}

$FL = new FetLifeUser($flical_config['FetLife']['username'], $flical_config['FetLife']['password']);
if ($flical_config['FetLife']['proxyurl']) {
    $p = parse_url($flical_config['FetLife']['proxyurl']);
    $FL->connection->setProxy(
        "{$p['host']}:{$p['port']}",
        ('socks' === $p['scheme']) ? CURLPROXY_SOCKS5 : CURLPROXY_HTTP
    );
}
$FL->logIn() or die("Failed to log in to FetLife. Last HTML received: {$FL->connection->cur_page}");

// All your events are belong to us.
while ($place = array_splice($places, 0, 1)) {
    if ($place['FetLife']) { continue; }
    // Get key of first item, which is the place name.
    reset($place);
    $k = key($place);
    // Bail if we're missing any crucial parameters.
    if (!$place[$k]['placeurl'] || !$place[$k]['timezone']) {
        continue;
    }

    // Set export options for this place.
    $num_pages = ($place[$k]['pages']) ? $place[$k]['pages'] : $flical_config['FetLife']['pages'];
    switch ($num_rsvps = strtolower(
        ($place[$k]['rsvps']) ? $place[$k]['rsvps'] : $flical_config['FetLife']['rsvps']
    )) {
        case 'all':
            $populate = true;
            break;
        case 'none':
            $populate = false;
            break;
        default:
            $populate = (int) $num_rsvps;
            break;
    }
    // TODO: Refactor "on"/"off" (bool) config processing so it's in a function?
    switch ($summaries = strtolower(
        ($place[$k]['summaries']) ? $place[$k]['summaries'] : $flical_config['FetLife']['summaries']
    )) {
        case 'on':
            $summaries = true;
            break;
        case 'off':
        default:
            $summaries = false;
            break;
    }
    switch ($archive = strtolower(
        ($place[$k]['archive']) ? $place[$k]['archive'] : $flical_config['FetLife']['archive']
    )) {
        case 'on':
            $archive = true;
            break;
        case 'off':
        default:
            $archive = false;
            break;
    }

    // Deal with timezone headaches.
    date_default_timezone_set($place[$k]['timezone']);

    // Get an iCalcreator instance.
    $vcal = new vcalendar();

    // Configure it.
    $vcal->setConfig('unique_id', 'fetlife-icalendar-' . $_SERVER['SERVER_NAME']);
    $vcal->setConfig('TZID', $place[$k]['timezone']);

    // Set calendar properties.
    $vcal->setProperty('method', 'PUBLISH');
    $vcal->setProperty('x-wr-calname', "$k FetLife Events");
    $vcal->setProperty('X-WR-TIMEZONE', $place[$k]['timezone']);
    $vcal->setProperty('X-WR-CALDESC', "FetLife Events in $k. via https://fetlife.com/{$place[$k]['placeurl']}/events");

    $x = $FL->getUpcomingEventsInLocation($place[$k]['placeurl'], $num_pages);
    foreach ($x as $event) {
        // If the "summaries" option is enabled, don't populate any event data.
        if (!$summaries) {
            $event->populate($populate);
        }
        $vevent = &$vcal->newComponent('vevent');
        // FetLife doesn't actually provide UTC time, even though it claims to. :(
        $vevent->setProperty('dtstart', substr($event->dtstart, 0, -1));
        if ($event->dtend) {
            $vevent->setProperty('dtend', substr($event->dtend, 0, -1));
        }
        // TODO: Add an appropriate URI representation for LOCATION.
        //       See: http://www.kanzaki.com/docs/ical/location.html#example
        $vevent->setProperty('LOCATION', trim($event->venue_name) . ' @ ' . trim($event->venue_address));
        $vevent->setProperty('summary', $event->title);
        $desc = trim($event->description);
        $desc .= ($event->cost) ? "\n\nCost: {$event->cost}" : '';
        $desc .= ($event->dress_code) ? "\n\nDress code: {$event->dress_code}" : '';
        // Some iCalendar clients don't display a URL property, so embed it.
        $desc .= "\n\nvia {$event->getPermalink()}";
        $vevent->setProperty('description', $desc);
        $vevent->setProperty('url', $event->getPermalink());
        if ($event->created_by) {
            $vevent->setProperty('organizer', $event->created_by->getPermalink(), array(
                'CN' => $event->created_by->nickname
            ));
        }
        if ($event->going) {
            foreach ($event->going as $profile) {
                $vevent->setProperty('attendee', $profile->getPermalink(), array(
                    'role' => 'OPT-PARTICIPANT',
                    'PARTSTAT' => 'ACCEPTED',
                    'CN' => $profile->nickname
                ));
            }
        }
        if ($event->maybegoing) {
            foreach ($event->maybegoing as $profile) {
                $vevent->setProperty('attendee', $profile->getPermalink(), array(
                    'role' => 'OPT-PARTICIPANT',
                    'PARTSTAT' => 'TENTATIVE',
                    'CN' => $profile->nickname
                ));
            }
        }
    }

    // Set timezone offsets.
    iCalUtilityFunctions::createTimezone($vcal, $place[$k]['timezone']);

    $icalfile = str_replace(' ', '_', key($place)) . '.ics';
    if ($archive && is_readable($icalfile)) {
        $time = time();
        if (!copy($icalfile, "$icalfile.archived.$time")) {
            error_log("Unable to archive to $icalfile.archived.$time.");
        }
    }

    // Finally, print the file.
    // TODO: Create an output directory option.
    //$vcal->setConfig('directory', 'ical');
    $vcal->setConfig('filename', $icalfile);
    $vcal->saveCalendar();
}
?>
