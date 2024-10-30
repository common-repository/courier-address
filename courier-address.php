<?php
/*
Plugin Name: Courier Address Distance Price
Plugin URI: https://wordpress.org/plugins/courier-address
Description: Courier Address possibility lookups / predictions / postcodes with GeoCoding, postcode charges, distances and price calculations. Useful for Couriers.
Version: 3.2
Author: Annesley Newholm
License: GPL2
Text Domain: courier-address
*/

// No script kiddies
if ( ! defined( 'ABSPATH' ) ) exit; 

define( 'COURIER_ADDRESS', 'courier-address' );
define( 'COURIER_ADDRESS_TITLE', 'Courier Address' );
define( 'COURIER_ADDRESS_VERSION', '3.1' );
define( 'COURIER_ADDRESS_MINIMUM_PHP_VERSION', '4.0' );
define( 'COURIER_ADDRESS_MINIMUM_WP_VERSION', '4.0' );

define( 'COURIER_ADDRESS_MAX_POSTCODE_GROUPS', 20 );

/* -------------------------------------------- Options -------------------------------------------- */
add_action( 'admin_menu', 'courier_address_add_admin_menu' );
add_action( 'admin_init', 'courier_address_settings_init' );

function courier_address_add_admin_menu() { 
  add_submenu_page( 'options-general.php', COURIER_ADDRESS_TITLE, COURIER_ADDRESS_TITLE, 'manage_options', COURIER_ADDRESS, 'courier_address_options_page', 'dashicons-admin-home' );
}

function courier_address_settings_init() { 
  register_setting( 'courier_address_plugin_page', 'courier_address_settings' );
  add_settings_section(
    'courier_address_plugin_page_section', 
    '', 
    '', 
    'courier_address_plugin_page'
  );

  //---------------------- Addresses
  add_settings_field( 
    'courier_address_API_key', 
    'Your Google API Key', 
    'courier_address_API_key_render', 
    'courier_address_plugin_page', 
    'courier_address_plugin_page_section' 
  );
  add_settings_field( 
    'courier_address_bounds_southwest', 
    'Courier Address Bounds SouthWest', 
    'courier_address_bounds_southwest_render', 
    'courier_address_plugin_page', 
    'courier_address_plugin_page_section' 
  );
  add_settings_field( 
    'courier_address_bounds_northeast', 
    'Courier Address Bounds NorthEast', 
    'courier_address_bounds_northeast_render', 
    'courier_address_plugin_page', 
    'courier_address_plugin_page_section' 
  );
  add_settings_field( 
    'courier_address_bounds_strict', 
    'Courier Address Bounds Strict', 
    'courier_address_bounds_strict_render', 
    'courier_address_plugin_page', 
    'courier_address_plugin_page_section' 
  );
  add_settings_field( 
    'courier_address_type', 
    'Courier Address Type', 
    'courier_address_type_render', 
    'courier_address_plugin_page', 
    'courier_address_plugin_page_section' 
  );
  add_settings_field( 
    'courier_address_external_notification', 
    'External Notification', 
    'courier_address_external_notification_render', 
    'courier_address_plugin_page', 
    'courier_address_plugin_page_section' 
  );
  
  //---------------------- Equations
  add_settings_field( 
    'courier_address_equation', 
    'Result Equation', 
    'courier_address_equation_render', 
    'courier_address_plugin_page', 
    'courier_address_plugin_page_section' 
  );
  add_settings_field( 
    'courier_address_equation_explanation', 
    'Result Explanation', 
    'courier_address_equation_explanation_render', 
    'courier_address_plugin_page', 
    'courier_address_plugin_page_section' 
  );

  //---------------------- PostCode charges
  for ($i = 1; $i <= COURIER_ADDRESS_MAX_POSTCODE_GROUPS; $i++) {
    register_setting('courier_address_plugin_page', "courier_address_postcode_group$i");
    register_setting('courier_address_plugin_page', "courier_address_postcode_group_price$i");
  }
  add_settings_field( 
    'courier_address_postcode_charges', 
    'PostCode charges', 
    'courier_address_postcode_charges_render', 
    'courier_address_plugin_page', 
    'courier_address_plugin_page_section' 
  );
}

function courier_address_options_page() { 
  ?>
  <form action='options.php' method='post'>
    <h2>Courier Address settings - <a target="_blank" href="https://wordpress.org/plugins/courier-address">wordpress.org plugin page</a> - <a target="_blank" href="http://wordpress.xsearchservices.com/plugins/courier-address/demo/">working demo</a></h2>
    
    <h3>Available short_codes</h3>
    <ul class="shortcodes-list">
      <li><strong>[courier_address <i>name</i>]</strong> - generate an Autcomplete address field for a given type within bounds. Associated sibling hidden INPUT fields will be filled out with the GeoData.</li>
      <li><strong>[courier_distance <i>from to measure:[km] map:[x]</i>]</strong> - calculates distance between the 2 named [courier_address <i>name</i>] fields. May also show a map of the points and distance.</li>
      <li><strong>[courier_result <i>name</i>]</strong> - holds the result of the equation calulation below.</li>
      <li><strong>(<a href="https://wordpress.org/plugins/simple-tooltips/" target="_blank">[simple_tooltip <i>...</i>]</a>)</strong> - Courier Address Field brings all normal installed shortcodes in to Contact Form 7 forms. Simply install and use.</li>
    </ul>
    
    <?php
      settings_fields( 'courier_address_plugin_page' );
      do_settings_sections( 'courier_address_plugin_page' );
      submit_button();
    ?>
  </form>
  <?php
}

function courier_address_postcode_charges_render() {
  $options = get_option( 'courier_address_settings' );
  
  ?>
    <p>
      <small style="float:left;">PostCode, e.g. 1234, 4566, 1212, ...</small>
      <small style="float:right;">Price, e.g. 18800</small>
    </p>
  <?php
  
  for ($i = 1; $i <= COURIER_ADDRESS_MAX_POSTCODE_GROUPS; $i++) {
    $optname  = "courier_address_postcode_group$i";
    $optPname = "courier_address_postcode_group_price$i";
    $optval   = '';
    $optPval  = '';
    if (isset($options[$optname]))  $optval  = $options[$optname];
    if (isset($options[$optPname])) $optPval = $options[$optPname];
    ?>
      <input type='text' class='courier_address_postcode_group' size='80' name='courier_address_settings[<?php echo $optname ; ?>]' value='<?php echo $optval ; ?>'>
      <input type='text' class='courier_address_postcode_group_price' size='10' name='courier_address_settings[<?php echo $optPname ; ?>]' value='<?php echo $optPval ; ?>'>
      <br class="clear-both"/>
    <?php
  }
  ?>
    <p><small>
      When associated postcode fields are filled out, the price field will also get populated.
      TODO: allow adding more!
    </small></p>
  <?php
}

function courier_address_type_render() {
  $options = get_option( 'courier_address_settings' );
  $optval  = '';
  if (isset($options['courier_address_type'])) $optval = $options['courier_address_type'];
  ?>
    <input type='text' name='courier_address_settings[courier_address_type]' value='<?php echo $optval ; ?>'>
    <p><small>Courier Address suggestion <a target="_blank" href="https://developers.google.com/maps/documentation/javascript/places-autocomplete#add_autocomplete">types</a>, e.g. address, (regions), geocode, ...</small></p>
  <?php
}

function courier_address_external_notification_render() {
  $options = get_option( 'courier_address_settings' );
  $optval  = '';
  if (isset($options['courier_address_external_notification'])) $optval = $options['courier_address_external_notification'];
  ?>
    <input type='text' size="70" name='courier_address_settings[courier_address_external_notification]' value='<?php echo $optval ; ?>'>
    <p><small>
      External Notification call on booking, e.g. SMS gateway like <a target="_blank" href="https://seeme.hu/online-tomeges-sms">SeeMe.hu</a><br/>
      e.g. http://seeme.dream.hu/gateway.d2?user=some-user-name&amp;password=fake-password&amp;message=<b>%%address%%</b>&amp;number=from-number<br/>
      Tokens include all form elements. In the case of a from and to address: <b>from, from-postal_code, from-postal_code_price, from-district, from-lat, from-lng, from-type, from-type-validates, to, to-postal_code, to-postal_code_price, to-district, to-lat, to-lng, to-type, to-type-validates</b><br/>
      For example also with an equation field called price and a CF7 field called name: <b>price, name</b>
    </small></p>
  <?php
}

function courier_address_bounds_strict_render() {
  $options = get_option( 'courier_address_settings' );
  $optval  = '';
  if (isset($options['courier_address_bounds_strict'])) $optval = $options['courier_address_bounds_strict'];
  ?>
    <input type='checkbox' name='courier_address_settings[courier_address_bounds_strict]' <?php if ($optval  == 'on') echo "checked='1'"; ?>>
    <p><small>Courier Address suggestions never outside the bounds</small></p>
  <?php
}

function courier_address_bounds_southwest_render() {
  $options = get_option( 'courier_address_settings' );
  $optval  = '';
  if (isset($options['courier_address_bounds_southwest'])) $optval = $options['courier_address_bounds_southwest'];
  ?>
    <input type='text' size="50" name='courier_address_settings[courier_address_bounds_southwest]' value='<?php echo $optval ; ?>'>
    <p><small>Bounds for address suggestions, e.g. 47.3885977,18.9062566</small></p>
  <?php
}

function courier_address_bounds_northeast_render() {
  $options = get_option( 'courier_address_settings' );
  $optval  = '';
  if (isset($options['courier_address_bounds_northeast'])) $optval = $options['courier_address_bounds_northeast'];
  ?>
    <input type='text' size="50" name='courier_address_settings[courier_address_bounds_northeast]' value='<?php echo $optval ; ?>'>
    <p><small>Bounds for address suggestions, e.g. 47.6006172,19.2900914</small></p>
  <?php
}

function courier_address_API_key_render() {
  $options = get_option( 'courier_address_settings' );
  $optval  = '';
  if (isset($options['courier_address_API_key'])) $optval = $options['courier_address_API_key'];
  ?>
    <input type='text' size="50" name='courier_address_settings[courier_address_API_key]' value='<?php echo $optval ; ?>'>
    <p><small>Insert your Google Maps API Key. <a href="https://developers.google.com/maps/documentation/javascript/get-api-key">Get your KEY!</a></small></p>
  <?php
}

function courier_address_equation_render() {
  $options = get_option( 'courier_address_settings' );
  $optval  = '';
  if (isset($options['courier_address_equation'])) $optval = $options['courier_address_equation'];
  ?>
    <input type='text' size="50" name='courier_address_settings[courier_address_equation]' value='<?php echo $optval ; ?>'>
    <p><small>
      Result equation, e.g. {distance} * {size} + {return}<br/>
      The {names} come from the @name attribute on &lt;input name="name" class="equation_component" /&gt; fields.<br/>
      And the value is taken from the @value attribute.
    </small></p>
  <?php
}

function courier_address_equation_explanation_render() {
  $options = get_option( 'courier_address_settings' );
  $optval  = '';
  if (isset($options['courier_address_equation_explanation'])) $optval = $options['courier_address_equation_explanation'];
  ?>
    <input type='text' size="50" name='courier_address_settings[courier_address_equation_explanation]' value='<?php echo $optval ; ?>'>
    <p><small>
      Explanation, e.g. {distance} km * {size} size + {return} = {result}<br/>
      Works the same as the Result Equation field.
    </small></p>
  <?php
}

/* -------------------------------------------- Infrastructure -------------------------------------------- */
function courier_address_load_scripts() {
  $options  = get_option( 'courier_address_settings' );
  $key_part = '';
  if (isset($options['courier_address_API_key'])) 
    $key_part = '&key=' . $options['courier_address_API_key'];
    
  // Create the client-side JavaScript Object window.courier_address_settings
  wp_register_script( 'courier-address-plugin-options', plugins_url( '/js/options.js',  __FILE__ ) );
  wp_localize_script( 'courier-address-plugin-options', 'courier_address_settings', $options);
  wp_enqueue_script(  'courier-address-plugin-options');
  
  wp_enqueue_script( 'courier-address-google-places-api', 'https://maps.googleapis.com/maps/api/js?libraries=places' . $key_part);
  wp_enqueue_script( 'courier-address-plugin-script', plugins_url( '/js/script.js',  __FILE__ ), ["jquery"]);
  wp_enqueue_style(  'courier-address-plugin-style',  plugins_url( '/css/style.css', __FILE__ ));
}
add_action( 'wp_enqueue_scripts', 'courier_address_load_scripts' );

function courier_address_load_wp_admin_style() {
  wp_enqueue_style( 'courier-address-plugin-admin-style', plugins_url( '/css/admin.css', __FILE__ ));
}
add_action( 'admin_enqueue_scripts', 'courier_address_load_wp_admin_style' );

/* -------------------------------------------- Courier Address Shortcode -------------------------------------------- */
function courier_address_init(){
  load_plugin_textdomain( 'courier-address', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'init', 'courier_address_init' , 20 );

function courier_address_plugins_loaded(){
  if (function_exists('wpcf7_add_form_tag')) {
    //CF7 >= 4.6
    wpcf7_add_form_tag( 'courier_address',    'courier_address_shortcode_handler',  true);
    wpcf7_add_form_tag( 'courier_address*',   'courier_address_shortcode_handler',  true);
    wpcf7_add_form_tag( 'courier_distance',   'courier_address_distance_shortcode_handler', false); //no name on shortcode
    wpcf7_add_form_tag( 'courier_distance*',  'courier_address_distance_shortcode_handler', false); //no name on shortcode
    wpcf7_add_form_tag( 'courier_result',     'courier_address_result_shortcode_handler',   true);
    wpcf7_add_form_tag( 'courier_result*',    'courier_address_result_shortcode_handler',   true);
  } else if (function_exists('wpcf7_add_shortcode')) {
    //CF7 < 4.6
    wpcf7_add_shortcode( 'courier_address',    'courier_address_shortcode_handler',  true);
    wpcf7_add_shortcode( 'courier_address*',   'courier_address_shortcode_handler',  true);
    wpcf7_add_shortcode( 'courier_distance',   'courier_address_distance_shortcode_handler', false); //no name on shortcode
    wpcf7_add_shortcode( 'courier_distance*',  'courier_address_distance_shortcode_handler', false); //no name on shortcode
    wpcf7_add_shortcode( 'courier_result',     'courier_address_result_shortcode_handler',   true);
    wpcf7_add_shortcode( 'courier_result*',    'courier_address_result_shortcode_handler',   true);
  } else {
    add_shortcode( 'courier_address',    'courier_address_shortcode_handler');
    add_shortcode( 'courier_address*',   'courier_address_shortcode_handler');
    add_shortcode( 'courier_distance',   'courier_address_distance_shortcode_handler');
    add_shortcode( 'courier_distance*',  'courier_address_distance_shortcode_handler');
    add_shortcode( 'courier_result',     'courier_address_result_shortcode_handler');
    add_shortcode( 'courier_result*',    'courier_address_result_shortcode_handler');
  }

  add_filter( 'wpcf7_validate_courier_address',  'courier_address_validation_filter', 10, 2 );
  add_filter( 'wpcf7_validate_courier_address*', 'courier_address_validation_filter', 10, 2 );
}
add_action( 'plugins_loaded', 'courier_address_plugins_loaded' , 20 );

function courier_address_form_elements( $form ) {
  // Process all normal shortcodes in CF7 forms
  return do_shortcode( $form );
}
add_filter( 'wpcf7_form_elements', 'courier_address_form_elements' );

function courier_address_result_shortcode_handler( $tag, $content = NULL ) {
  $html   = '';
  $posted = false;
  $wpcf7_contact_form = NULL;
  
  if (class_exists('WPCF7_ContactForm')) {
    // Check CF7 update which can send through a WPCF7_FormTag instead of an array
    if ( class_exists('WPCF7_FormTag') // Since PHP 4
      && is_object( $tag )             // Since PHP 4
      && is_a( $tag, 'WPCF7_FormTag' ) // Since PHP 4.2.0
    ) {
      $tag = (array) $tag;
    }
    
    // Test for CF7 existence and if this form has been posted already
    $wpcf7_contact_form = WPCF7_ContactForm::get_current();
    if (!is_a( $wpcf7_contact_form, 'WPCF7_ContactForm' )) $wpcf7_contact_form = NULL;
    $posted = $wpcf7_contact_form && $wpcf7_contact_form->is_posted();
  }
  
  // Assume from now on that we are dealing with an array
  if ( is_object( $tag ) )  $tag = (array) $tag; // General hopeful catch all future changes
  if ( ! is_array( $tag ) ) return 'Error: shortcode not understood';
  if (isset($tag['type'])) {
    $type        = $tag['type'];
    $name        = $tag['name'];
    $content     = $tag['content']; //accomodating CF7 and also WP shortcode
    $options     = (array) $tag['options'];
  } else {
    //TODO: normal shortcode options
    $type        = 'courier_result';
    $name        = $tag[0];
    $options     = array();
  }
  $plugin_options = get_option( 'courier_address_settings' );
  if (!$name) $name = 'courier-result';
  $class_att = ' wpcf7-form-control wpcf7-result-container';
  $currency  = '';
  $atts      = '';

  if (!$type) $type = 'courier_result';
  if ( substr($type, -1) == '*' ) {
    $class_att .= ' wpcf7-validates-as-required';
    $atts      .= ' aria-required="true"';
  }

  foreach ( $options as $option ) {
    if ( preg_match( '%^class:([-0-9a-zA-Z_]+)$%', $option, $matches ) ) {
      $class_att .= ' ' . $matches[1];
    }
    if ( preg_match( '%^currency:([-0-9a-zA-Z_]+)$%', $option, $matches ) ) {
      $currency = $matches[1];
    }
  }
  if ( $class_att ) $atts .= ' class="' . trim( $class_att ) . '"';

  //-------------------- validation
  $validation_error_html = '';
  if ( $wpcf7_contact_form ) $validation_error_html = $wpcf7_contact_form->validation_error( $name );

  //-------------------- assemble HTML
  $html  = "<span class='wpcf7-form-control-wrap $name'>";
  $html .= "<input name='$name' $atts />";
  $html .= "<span class='result-container-value'></span>";
  $html .= "<span class='result-container-explanation'></span>";
  //TODO: get the (complex) equations from the tags
  $html .= "<span class='result-data equation'>"    . $plugin_options['courier_address_equation'] . '</span>';
  $html .= "<span class='result-data explanation'>" . $plugin_options['courier_address_equation_explanation'] . '</span>';
  $html .= "<input type='text' class='distance-data currency' value='$currency' />";
  $html .= $validation_error_html;
  $html .= "</span>";

  return $html;
}

function courier_address_distance_shortcode_handler( $tag, $content = NULL ) {
  $html   = '';
  $posted = false;
  $wpcf7_contact_form = NULL;
  
  if (class_exists('WPCF7_ContactForm')) {
    // Check CF7 update which can send through a WPCF7_FormTag instead of an array
    if ( class_exists('WPCF7_FormTag') // Since PHP 4
      && is_object( $tag )             // Since PHP 4
      && is_a( $tag, 'WPCF7_FormTag' ) // Since PHP 4.2.0
    ) {
      $tag = (array) $tag;
    }
    
    // Test for CF7 existence and if this form has been posted already
    $wpcf7_contact_form = WPCF7_ContactForm::get_current();
    if (!is_a( $wpcf7_contact_form, 'WPCF7_ContactForm' )) $wpcf7_contact_form = NULL;
    $posted = $wpcf7_contact_form && $wpcf7_contact_form->is_posted();
  }

  // Assume from now on that we are dealing with an array
  if ( is_object( $tag ) )  $tag = (array) $tag; // General hopeful catch all future changes
  if ( ! is_array( $tag ) ) return 'Error: shortcode not understood';
  if (isset($tag['type'])) {
    $type        = $tag['type'];
    $name        = $tag['name'];
    $content     = $tag['content']; //accomodating CF7 and also WP shortcode
    $options     = (array) $tag['options'];
  } else {
    //TODO: normal shortcode options
    $type        = 'courier_distance';
    $name        = $tag[0];
    $options     = array();
  }
  $class_att   = ' wpcf7-form-control wpcf7-courier-address-distance equation_component';
  $atts        = '';
  $measure     = 'km';
  $courier_address1    = 'from';
  $courier_address2    = 'to';
  $map_height  = '400';

  if (!$type) $type = 'courier_distance';
  if ( substr($type, -1) == '*' ) {
    $class_att .= ' wpcf7-validates-as-required';
    $atts      .= ' aria-required="true"';
  }

  //-------------------- option tags
  foreach ( $options as $option ) {
    if        (preg_match( '%^class:([-0-9a-zA-Z_]+)$%', $option, $matches )) {
      $class_att  .= ' ' . $matches[1];
    } else if (preg_match( '%^measure:([-0-9a-zA-Z_]+)$%', $option, $matches )) {
      $measure     = $matches[1];
    } else if (preg_match( '%^map:([-0-9a-zA-Z_]+)$%', $option, $matches )) {
      $map_height  = $matches[1];
    } else if (strstr($option, ":") === FALSE) {
      if      (!$courier_address1) $courier_address1 = $option;
      else if (!$courier_address2) $courier_address2 = $option;
    }
  }
  if ( $class_att ) $atts .= ' class="' . trim( $class_att ) . '"';

  //-------------------- validation
  $validation_error_html = '';
  if ( $wpcf7_contact_form ) $validation_error_html = $wpcf7_contact_form->validation_error( $name );

  //-------------------- assemble HTML
  $html  = "<span class='wpcf7-form-control-wrap $name'>";
  $html .= "<input type='text' name='$name' $atts value='0' />";
  $html .= "<input type='text' class='distance-data courier-address1' value='$courier_address1' />";
  $html .= "<input type='text' class='distance-data courier-address2' value='$courier_address2' />";
  $html .= "<input type='text' class='distance-data measure' value='$measure' />";
  $html .= "<input type='text' class='distance-data map-height' value='$map_height' />";
  $html .= "<span class='courier-address-distance-map-canvas'></span>";
  $html .= $validation_error_html;
  $html .= '</span>';

  return $html;
}

function courier_address_validation_filter( $result, $tag ) {
  $wpcf7_contact_form = WPCF7_ContactForm::get_current();
  $type    = $tag['type'];
  $name    = $tag['name'];
  $options = (array) $tag['options'];
  $value   = isset( $_POST[$name] ) ? trim( wp_unslash( strtr( (string) $_POST[$name], "\n", " " ) ) ) : '';
  $address_detail_level = isset( $_POST["$name-type"] ) ? trim( wp_unslash( strtr( (string) $_POST["$name-type"], "\n", " " ) ) ) : '';
  $required_address_detail_level = 0;

  foreach ( $options as $option) {
    if ( preg_match( '%^required-address-detail:([-0-9a-zA-Z_]+)$%', $option, $matches ) ) {
      $required_address_detail_level = courier_address_accuracy_level($matches[1]);
    }
  }
  
  if ($required_address_detail_level && $address_detail_level) {
    if (intval($address_detail_level) < $required_address_detail_level)
      $result->invalidate( $tag, wpcf7_get_message( 'invalid_address_detail_level' ) );
  }
  
  if ( substr($type, -1) == '*' && $value == '' ) {
    $result->invalidate( $tag, wpcf7_get_message( 'invalid_required' ) );
  }

  return $result;
}

function courier_address_shortcode_handler( $tag, $content = NULL ) {
  $html   = '';
  $posted = false;
  $wpcf7_contact_form = NULL;
  
  if ( class_exists('WPCF7_ContactForm') ) {
    // Check CF7 update which can send through a WPCF7_FormTag instead of an array
    if ( class_exists('WPCF7_FormTag') // Since PHP 4
      && is_object( $tag )             // Since PHP 4
      && is_a( $tag, 'WPCF7_FormTag' ) // Since PHP 4.2.0
    ) {
      $tag = (array) $tag;
    }
    
    // Test for CF7 existence and if this form has been posted already
    $wpcf7_contact_form = WPCF7_ContactForm::get_current();
    if (!is_a( $wpcf7_contact_form, 'WPCF7_ContactForm' )) $wpcf7_contact_form = NULL;
    $posted = $wpcf7_contact_form && $wpcf7_contact_form->is_posted();
  }
  
  // Assume from now on that we are dealing with an array
  if ( is_object( $tag ) )  $tag = (array) $tag; // General hopeful catch all future changes
  if ( ! is_array( $tag ) ) return 'Error: shortcode not understood';
  if (isset($tag['type'])) {
    $type        = $tag['type'];
    $name        = $tag['name'];
    $content     = $tag['content'];
    $options     = (array) $tag['options'];
    $values      = (array) $tag['values'];
  } else {
    //TODO: normal shortcode options
    $type        = 'courier_address';
    $name        = $tag[0];
    $options     = array();
  }
  $plugin_options = get_option( 'courier_address_settings' );
  $class_att = ' wpcf7-form-control wpcf7-courier-address';
  $atts      = '';
  $required_address_detail_level  = 0;
  $required_address_detail_string = '';

  if (!$type) $type = 'courier_address';
  if ( substr($type, -1) == '*' ) {
    $class_att .= ' wpcf7-validates-as-required';
    $atts      .= ' aria-required="true"';
   }

  foreach ( $options as $option) {
    if ( preg_match( '%^class:([-0-9a-zA-Z_]+)$%', $option, $matches ) ) {
      $class_att .= ' ' . $matches[1];
    } else if ( preg_match( '%^required-address-detail:([-0-9a-zA-Z_]+)$%', $option, $matches ) ) {
      $required_address_detail_string = $matches[1];
      $required_address_detail_level  = courier_address_accuracy_level($required_address_detail_string);
      $class_att                     .= ' required-address-detail-' . $required_address_detail_level;
    }
  }
  
  if ($class_att) $atts .= ' class="' . trim( $class_att ) . '"';
  if ($content)   $atts .= ' placeholder="' . trim( $content ) . '"';

  if ( $posted ) $value = stripslashes_deep( $_POST[$name] );
  else           $value = isset( $values[0] ) ? $values[0] : '';
  
  $value = esc_attr($value);
  $_postcode = __('postcode', 'courier-address');
  $_district = __('district', 'courier-address');
  $_postal_code_price = __('postal code price', 'courier-address');
  
  //-------------------- validation
  $validation_error_html = '';
  if ( $wpcf7_contact_form ) $validation_error_html = $wpcf7_contact_form->validation_error( $name );

  //-------------------- assemble HTML
  $html  = "<span class='wpcf7-form-control-wrap $name'>";
  $html .= "<input type='text' name='$name' value='$value' $atts></input>";
  $html .= "<input type='text' name='$name-postal_code' placeholder='$_postcode' class='courier-address-data postal_code equation_component $name'/>";
  $html .= "<input type='text' name='$name-postal_code_price' placeholder='$_postal_code_price' class='courier-address-data postal_code_price equation_component $name'/>";
  $html .= "<input type='text' name='$name-district' placeholder='$_district' class='courier-address-data district equation_component $name'/>"; //Specific to Hungary
  $html .= "<input type='text' name='$name-lat' class='courier-address-data lat equation_component $name'/>";
  $html .= "<input type='text' name='$name-lng' class='courier-address-data lng equation_component $name'/>";
  $html .= "<input type='text' name='$name-type' class='courier-address-data type equation_component $name'/>";
  $html .= "<input type='text' name='$name-type-validates' class='courier-address-data type-validates wpcf7-validates-as-required equation_component $name'/>";
  /* TODO: future per address field configuration
  $html .= "<input class='courier-address-options courier_address_bounds_northeast' value='$plugin_options[courier_address_bounds_northeast]'/>";
  $html .= "<input class='courier-address-options courier_address_bounds_southwest' value='$plugin_options[courier_address_bounds_southwest]'/>";
  $html .= "<input class='courier-address-options courier_address_bounds_strict' value='$plugin_options[courier_address_bounds_strict]'/>";
  */
  $html .= $validation_error_html;
  //cannot use wpcf7-not-valid-tip because cf7 removes them on submission
  $html .= '<span role="alert" class="wpcf7-js-not-valid-tip courier-address-js-validation courier-address-level-110-not-valid-message">' . __("Address is not accurate enough. Please enter a street.", 'courier-address') . '</span>';
  $html .= '<span role="alert" class="wpcf7-js-not-valid-tip courier-address-js-validation courier-address-level-120-not-valid-message">' . __("Address is not accurate enough. Please enter a complete street address.", 'courier-address') . '</span>';
  $html .= '<span role="alert" class="wpcf7-js-not-valid-tip courier-address-js-validation courier-address-level-130-not-valid-message">' . __("Address is not accurate enough. Please enter a complete street address.", 'courier-address') . '</span>';
  $html .= '<span role="alert" class="wpcf7-js-not-valid-tip courier-address-js-validation courier-address-level-140-not-valid-message">' . __("We do not deliver to this area.", 'courier-address') . '</span>';
  $html .= '</span>';

  return $html;
}

function courier_address_accuracy_level( $required_address_detail_string ) {
  $required_address_detail_level = 0;
  switch ($required_address_detail_string) {
    case 'route':            $required_address_detail_level = 110; break;
    case 'street_address':   $required_address_detail_level = 120; break;
    case 'postcode':         $required_address_detail_level = 130; break;
    case 'postcode_charge':  $required_address_detail_level = 140; break;
  }
  return $required_address_detail_level;
}

function courier_address_wpcf7_mail_sent( $cf7 ) {
  $plugin_options = get_option( 'courier_address_settings' );
  $courier_address_external_notification = ( isset( $plugin_options['courier_address_external_notification'] ) ? $plugin_options['courier_address_external_notification'] : '' );
  if ( $courier_address_external_notification ) {
    foreach ( $_POST as $key => $value ) {
      $courier_address_external_notification = str_replace( "%%$key%%", $value, $courier_address_external_notification );
    }
    $courier_address_external_notification = utf8_decode( $courier_address_external_notification );
    $response = file_get_contents( $courier_address_external_notification );
    if ( $response ) {
      $response_fields = explode( '&', $response );
      $response_assoc  = array();
      foreach ( $response_fields as $value ) {
        $value_split = explode( '=', $value );
        $response_assoc[$value_split[0]] = ( count( $value_split ) > 1 ? $value_split[1] : '' );
      }
      if ( ! isset( $response_assoc['result'] ) || $response_assoc['result'] != 'OK' ) { 
        $admin_email = get_option('admin_email');
        if ( $admin_email ) wp_mail( $admin_email, 'SMS delivery failed: ' . $response_assoc['message'], $courier_address_external_notification );
      }
    } else {
      $admin_email = get_option('admin_email');
      if ( $admin_email ) wp_mail( $admin_email, 'SMS delivery maybe failed: NO RESPONSE', $courier_address_external_notification );
    }
  }
}
add_action( 'wpcf7_mail_sent', 'courier_address_wpcf7_mail_sent', 10, 1 );
