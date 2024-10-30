=== Courier Address ===
Contributors: anewholm
Tags: address, contact form, form, field, google map, autocomplete, distance, courier, delivery, google place, places, world, equation, price, postcode
Requires at least: 4.0
Tested up to: 4.7.2
Stable tag: 3.2
License: GPLv2
License URI: https://www.gnu.org/licenses/gpl-2.0.html

[courier_address] Google address Autocomplete, [courier_distance] Google Map, [courier_result] price from configurable equation. PostCode area charges and more!

== Description ==

This is a generic Courier Address field management plugin with special extensions for Courier delivery charging. It is compatable with [Contact Form 7](https://wordpress.org/plugins/contact-form-7/) but also creates generic form element shortcodes. [Working example](http://wordpress.xsearchservices.com/allplugins/courier-address/)

*   [courier_address (from/to)] Courier Address possibility lookups / predictions / postcodes with GeoCoding.
  e.g. [courier_address from] [courier_address to]
  with Postcode charges based on Addresses filled out in settings.
*   [courier_distance] Distances with embedded Google map to show journey.
  based on the names of other address fields, e.g. [courier_distance (from/to)]
*   [courier_result (name)] Result calculations based on flexible equation in the settings: 
  e.g. [courier_result price]
  Math.max({from-postal_code_price}, {to-postal_code_price})
  
Courier Address field also enables all other installed shortcodes in [Contact Form 7](https://wordpress.org/plugins/contact-form-7/) forms, e.g. [simple_tooltip](https://wordpress.org/plugins/simple-tooltips/)

== Installation ==

1. Download and install the Plugin
2. Upload the plugin folder to the '/wp-content/plugins/' directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. You will now have [courier_address], [courier_distance] and [courier_result] tag shortcodes and in Contact Form 7
5. Insert your Google Maps Api Key in the settings

== Example CF7 form ==
    <div id="addresses">
      [courier_address* from required-address-detail:postcode_charge]Where?[/courier_address*] 
      [courier_address* to  required-address-detail:postcode_charge]To?[/courier_address*]
    </div>

    <div id="results" class="when-has-price">
      <label class="price-label">Price:</label> [courier_result* price currency:Ft]
      [text* your-name placeholder "Your Name"]
      [text* phone-number placeholder "Phonenumber"]
      [submit "Request Delivery"]
    </div>

    [courier_distance* from to measure:km map:400]

== Screenshots ==

In /assets/

1. Example
2. Postcode groups charge Settings
3. Equation Settings
4. Courier Address Lookup Settings

== Changelog ==

= 1.0 =
* Birth.

= 2.0 =
* UXD updates

= 3.0 =
* Language text domains added
* JS validation errors translated
* EN, US and HU

= 3.1 =
* plugin page moved under settings
* new CF7 compatability
* readme better

= 3.2 =
* external notifications calls, e.g. for SMS gateways