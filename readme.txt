=== ZigWeather ===
Contributors: ZigPress 
Donate link: http://www.zigpress.com/donations/
Tags: widget, multi-widget, sidebar, weather, worldwide, temperature, wind, rainfall, humidity, zig, zigpress
Requires at least: 3.6
Tested up to: 4.3
Stable tag: 2.3.4

ZigWeather gives you a multi-widget to show weather on your site.

== Description ==

NOTE: ZIGWEATHER REQUIRES PHP 5.3!

ZigWeather gives you a multi-widget to show weather on your site.

Anyone using earlier (pre 2.0) versions of this plugin should try this new version, which uses a different online weather service for data capture, and a different method of setting locations. This is the plugin that wouldn't die!

Please note that caching is now fixed automatically to comply with the terms of use of the World Weather Online API, and the credit link should not be removed.

For further information and support, please visit [the ZigWeather home page](http://www.zigpress.com/plugins/zigweather/).  Support will ONLY be provided in response to comments posted on that page.

== Installation ==

1. Go to Admin > Plugins > Add New and enter ZigWeather in the search box.
2. Follow the prompts to install and activate the plugin.
3. Go to the settings page and follow the link to get a World Weather Online API key (it's free).
4. Enter this key on the settings page and save.
5. Go to the widgets control page and place the widget, entering a location in the widget control panel.

== Frequently Asked Questions ==

= What can I enter in the location box in the widget control panel? =

You can try a few different formats in order to get the widget to show weather for your desired location:

* city
* city, country
* city, state, country
* city, state (USA only)
* postalcode (UK, USA, Canada only)

If the widget displays "Data cannot be shown" or shows the wrong location, try a different format or a different nearby location. For example, in Malta, the API doesn't know where the town "Bugibba" is, but it is happy to take "Naxxar" which is a slightly larger town about 3km away.

= How often is the information refreshed? =

The plugin gets fresh information each half hour, and whenever you change a location. However, the World Weather Online service only updates its API data every 3 to 4 hours. But hey, it's free.

More information can be found at [World Weather Online](http://www.worldweatheronline.com/weather-api.aspx).

= I don't like the way it looks. What can I do? =

Go to the settings page and set the "Load stylesheet" dropdown to "None" then save. You will now need to add some styles in your theme stylesheet.

== Changelog ==

= 2.3.4 =
* Confirmed compatibility with WordPress 4.3 
= 2.3.2 =
* Confirmed compatibility with WordPress 4.2.2
* Updated classes to use PHP5 constructors to satisfy WordPress Changeset 32990
= 2.3.1 =
* Confirmed compatibility with WordPress 4.2
* Increased minimum PHP version to 5.3 in accordance with ZigPress policy of gradually dropping support for deprecated platforms
= 2.3.0 =
* Confirmed compatibility with WordPress 4.1
= 2.2.9 =
* Updated instructions to get World Weather Online key (new URL)
= 2.2.8 =
* Various fixes to prevent warnings on first activation
* Various fixes to prevent warnings in debug mode
* Added Settings link in plugin summary on admin plugins page
* Confirmed compatibility with WordPress 4.0
= 2.2.7 =
* Removed some warnings that were appearing while WordPress was running in debug mode
= 2.2.6 =
* Confirmed compatibility with WordPress 3.9
= 2.2.5 =
* Confirmed compatibility with WordPress 3.8
* Minor text changes on admin page
* Increased minimum WordPress version to 3.6 in accordance with ZigPress policy of encouraging WordPress updates
= 2.2.4 =
* Updated API call to meet new World Weather Online requirements
* Confirmed compatibility with WordPress 3.5.2
= 2.2.3 =
* Fixed further fatal error on activation - really not sure what I was smoking when I released 2.2.1 and 2.2.2
* Some code modularisation to allow use by external code
= 2.2.2 =
* Fixed fatal error on first time activation
= 2.2.1 =
* Verified compatibility with WordPress 3.5
= 2.2 =
* Added ability to hide ZigPress credit (but please consider leaving it visible or making a donation)
* Added ability to show debug information on settings page (mainly for plugin creator's benefit)
* More code refactoring
* Minor admin CSS improvements
* Widget control panel now contains advice about entering the location
* Browsers are prevented from caching the plugin's stylesheets
= 2.1 =
* Added ability to show temperatures in celsius or fahrenheit
* Added ability to show windspeed in km/h or mph
* Coding style improvements
* Code refactoring
* Added ZigPress credit
* Verified compatibility with WordPress 3.4.2
= 2.0.1 =
* Donation link on plugins list page, added fetched time (display optional), removed unused files from SVN, added basic theme chooser (to be improved later)
= 2.0 =
* Total rebuild using different API
= 0.9.2 =
* End of life notice
= 0.9.1 =
* Rewrote AJAX location search code
= 0.9 =
* Cache system completely overhauled
* Added AJAX error handling to make problem resolution easier
* Updated PHP version requirement in readiness for WordPress 3.2
* Updated WordPress version requirement as new code only tested on most recent versions
= 0.8.2 =
* Verified compatibility with WordPress 3.1.1
= 0.8.1 =
* Changes aimed at fixing a reported redirect bug when saving options (bug not reproducible by ZigPress)
= 0.8 =
* Made wind information a selectable feature
* Added humidity as a selectable feature
= 0.7 =
* Added admin icon, donation and information boxes
* Removed dependency on separate base class and some redundant code
* Added links for weather.com signup
= 0.6 =
* Added newer base class
= 0.5 =
* Added wind information
= 0.4 =
* Added settings page.
* Added live city search using AJAX
= 0.3 =
* Improvements to meet WordPress plugin repository hosting requirements
= 0.2 =
* Total rewrite using OOP techniques
= 0.1 =
* First working version

