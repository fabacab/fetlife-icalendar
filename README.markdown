# FetLife iCalendar Exporter - Technical Documentation

The FetLife iCalendar Exporter is a PHP-based FetLife Event to iCalendar exporter. You can run it on any system with PHP version 5.3 installed. It also comes bundled with a simple Web viewer so you can offer iCalendar subscriptions to website visitors.

## Prerequisites

This tool requires PHP version 5.3 or greater. To automatically fetch new events, you'll also need a system scheduler, such as `cron`. Additionally, this tool requires two libraries:

* [iCalcreator](https://github.com/iCalcreator/iCalcreator)
* [libFetLife](https://github.com/meitar/libFetLife)

We'll get these in the next step.

## Installing

This section documents how to install FetLife iCalendar.

### Installing from source

    git clone git://github.com/meitar/fetlife-icalendar.git
    cd fetlife-icalendar
    git submodule init
    git submodule update
    cp fetlife-icalendar.ini.php-sample fetlife-icalendar.ini.php

## Configuration

This section documents configuration options availalbe to FetLife iCalendar.

### File format

The configuration file uses [PHP's `ini` file](http://php.net/parse_ini_file) syntax.

FetLife iCalendar looks for a configuration filen named `fetlife-icalendar.ini.php` in the same directory as its main workhorse script, `ical.php`. The program ships with a sample configuration file called `fetlife-icalendar.ini.php-sample`. Rename or copy the sample file to the expected name to use its settings.

### Places

The configuration file defines a set of "places" corresponding to section headers. Each "place," except the special-case place called `FetLife`, is at minimum:

* A place name, defined in the section heading.
* A `placeurl`, which is the URL fragment of the FetLife Place without a leading slash (`/`).
* A `timezone`, which is the PHP timezone string for the place. Timezones must be a [valid PHP DateTimeZone name](http://www.php.net/manual/en/timezones.php).

#### The `FetLife` Place

The configuration file must include at least one place and the special-case `FetLife` place. The `FetLife` case must include, at a minimum:

* The name `FetLife`, defined in the section heading.
* A `username`, which is the FetLife username or email address used to log in to FetLife.com.
* A `password`, which is the password used to log in to FetLife.com.

The following optional configuration options can be set in the `FetLife` place, too:

* `proxyurl`: Whether or not to use a proxy. Must be a URL, such as `"http://example.proxy.com:8080"` (for an HTTP proxy) or `"socks://localhost:9050"` for a SOCKS5 proxy.

Additionally, the `FetLife` place can optionally also define default settings that will be used for all other places. If a setting exists in both the `FetLife` place and another place, the setting from the latter place is used (overriding the default one specified in the `FetLife` place).

Default place options that can be set in the `FetLife` place are:

* `pages`: Default number of event pages to get for each place.
* `rsvps`: How many pages of RSVPs to get for each event. `"All"`, `"none"`, or a number of pages. Default is `"none"`.
* `summaries`: If set to `"on"`, only event summaries are fetched. This will override the value of `rsvps`. May be useful if your `pages` value is very high and all you need is basic event data. Default is `"off"`.

It is a good idea to set some defaults. ;)

#### Place configuration options

Each place specified in the configuration file must have, at a minimum, the following required settings:

* `placeurl`: The URL fragment for this place. For example, `"cities/5930"` means "Baltimore, Maryland, United States". Get these by browsing to [https://fetlife.com/places](https://fetlife.com/places), finding the place you're interested in exporting events from, and then extracting the last part of URL.
* `timezone`: The appropriate timezone string. Timezones must be a [valid PHP DateTimeZone name](http://www.php.net/manual/en/timezones.php).

In addition to the required settings listed above, you can define per-place export behavior by using the following optional settings:

* `pages`: The number of event pages to get for this place. (A value of `"1"` is a maximum of 5 events, `"2"` is a maximum of 10 events, and so on.)
* `rsvps`: How many pages of RSVPs to get for each event. This can be set to one of three different kinds of values. They are:
    * `all`: Fetch all RSVPs for events.
    * `none`: Do not fetch RSVPs for events. (This is the default.)
    * *INTEGER*: The number of RSVP pages to fetch. (A value of `"1"` is a maximum of 10 RSVPs, `"2"` is a maximum of 20 RSVPs, and so on.)
* `summaries`: Can be either `"on"` or `"off"` (the default). When `"on"`, only basic event data will be fetched, and the value of `rsvps`, if set, is ignored.

Options specified here override any default set in [the `FetLife` place](#the-fetlife-place).

#### Example configuration

The following is an example configuration file.

    [FetLife]
    username="MY_USERNAME"
    password="MY_PASSWORD"
    pages="5"       ;// Default number of event pages to get for each place, below.
    rsvps="none"    ;// How many RSVPs to get? "All", "none" or a number of pages.
    summaries="off" ;// When "on", only fetches summaries. Faster, but incomplete.

    [Atlanta]
    placeurl="cities/2600"
    timezone="US/Eastern"
    pages="10"
    rsvps="all"

    [Boston]
    placeurl="cities/5930"
    timezone="America/New_York"

    [London]
    placeurl="administrative_areas/3779"
    timezone="Europe/London"
    summaries="on"

## Running

FetLife iCalendar can be executed in two ways: [from the command line](#command-line-use), or [from a Web browser](#web-browser-use). You can pass runtime options in both modes, with some minor differences. (See below.)

### Runtime options

The following options can be passed to FetLife iCalendar at runtime to effect its behavior:

* `h` or `help`: Prints a usage message and exits. (This option is only available when run from a command line.)
* `refresh="PLACE NAME"`: Only export events from `PLACE NAME`, specified in the configuration file, ignoring any other configured places.

### Command line use

After [installing](#installing) and [configuring FetLife iCalendar](#configuration), you can run it by issuing the following command:

    php ical.php

To get a usage message, pass the `-h` or `--help` option:

    php ical.php --help

You can pass any number of `--refresh` options. Given the above [example configuration](#example-configuration) file, the following invocation will export 5 pages of event summaries from the city of London in the United Kingdom, and will ignore both Atlanta and Boston:

    php ical.php --refresh="London"

Conversely, the following invocation will run the configured export for Atlanta and Boston but ignore London:

    php ical.php --refresh="Atlanta" --refresh="Boston"

### Web browser use

To run FetLife iCalendar from the Web browser, you must have [installed](#installing) it to a Web-accessible directory. Once installed and [configured](#configuration), load `ical.php` in your Web browser.

There is currently no feedback provided to you when invoking FetLife iCalendar from your Web browser. (Patches welcome.) Instead, load the associated `index.php` file to see an updated timestamp of exported events.

You can pass the name of a specific configured place to export events from using the `refresh` parameter in the query string. For example:

    http://my.server.com/fetlife-icalendar/ical.php?refresh=Atlanta

Unlike when running from the command line, only one place can be `refresh`ed at a time.

## Troubleshooting

* If [running `ical.php` from a Web browser](#web-browser-use), ensure your `lib/FetLife` directory and its `fl_sessions` is read and writable by your user.

