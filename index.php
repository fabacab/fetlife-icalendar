<!DOCTYPE html>
<html lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title>FetLife to iCalendar Exporter</title>
</head>
<body>
<h1>FetLife to iCalendar Exporter</h1>
<p>This server exports events from <a href="https://en.wikipedia.org/wiki/FetLife">FetLife</a> into <a href="https://en.wikipedia.org/wiki/ICalendar">iCalendar (<code>.ics</code>)</a> files. You can subscribe to them in your calendaring application of choice, such as <a href="https://www.apple.com/osx/apps/#calendar">Apple's iCal/Calendar</a>, <a href="https://www.google.com/calendar/">Google Calendar</a>, <a href="https://office.microsoft.com/outlook/">Microsoft Outlook</a>, <a href="http://calendar.yahoo.com/">Yahoo! Calendar</a>, <a href="https://www.mozilla.org/projects/calendar/">Mozilla Sunbird</a>, and so on.</p>
<?php
$files = glob('*.ics');
if ($files) {
?>
<p>The following calendars are available on this server:</p>
<ul>
<?php
    foreach ($files as $file) {
?>
    <li>
        <a href="<?php printHTMLSafe($file);?>"><?php printHTMLSafe(str_replace('_', ' ', basename($file, '.ics')));?></a>
        (<a href="webcal://<?php printHTMLSafe($_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/$file");?>">webcal</a>)
        [Last updated: <?php printHTMLSafe(@date('F d, Y \a\t g:ia', filemtime($file)));?>]
    </li>
<?php
    } // endforeach;
?>
</ul>
<?php
} // endif;
?>
<p>Need help? <a href="https://github.com/meitar/fetlife-icalendar/#readme">Read the friendly manual</a>. :)</p>
<p>You can <a href="https://github.com/meitar/fetlife-icalendar/">download</a> and install this tool on your own website, too. That way, you can choose what calendars to make available, with what information, and to whom.</p>
</body>
</html>
<?php
function printHTMLSafe ($str) {
    print htmlentities($str, ENT_QUOTES, 'UTF-8');
}
?>
