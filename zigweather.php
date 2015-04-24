<?php
/*
Plugin Name: ZigWeather
Plugin URI: http://www.zigpress.com/plugins/zigweather/
Description: Completely rebuilt plugin to show current weather conditions.
Version: 2.3.1
Author: ZigPress
Requires at least: 3.6
Tested up to: 4.2
Author URI: http://www.zigpress.com/
License: GPLv2
*/


/*
Copyright (c) 2010-2015 ZigPress

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation Inc, 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
*/


require_once dirname(__FILE__) . '/admincallbacks.php';
require_once dirname(__FILE__) . '/widgets.php';


if (!class_exists('zigweather')) {


	class zigweather {
	

		public $plugin_folder;
		public $plugin_directory;
		public $options;
		public $params;
		public $result;
		public $result_type;
		public $result_message;
		public $callback_url;
	
	
		public function __construct() {
			$this->plugin_folder = get_bloginfo('wpurl') . '/' . PLUGINDIR . '/' . dirname(plugin_basename(__FILE__)) . '/';
			$this->plugin_directory = WP_PLUGIN_DIR . '/' . dirname(plugin_basename(__FILE__)) . '/';
			global $wp_version;
			if (version_compare(phpversion(), '5.3', '<')) wp_die('ZigWeather requires PHP 5.3 or newer. Please update your server.');
			if (version_compare($wp_version, '3.6', '<')) wp_die('ZigWeather requires WordPress 3.6 or newer. Please update your installation.'); 
			$this->get_params();
			$this->callback_url = preg_replace('/\?.*/', '', $_SERVER['REQUEST_URI']);
			add_action('widgets_init', create_function('', 'return register_widget("widget_zigweather");'));
			add_action('wp_enqueue_scripts', array($this, 'action_wp_enqueue_scripts'));
			add_action('admin_init', array($this, 'action_admin_init'));
			add_action('admin_enqueue_scripts', array($this, 'action_admin_enqueue_scripts'));
			add_action('admin_menu', array($this, 'action_admin_menu'));
			add_filter('plugin_row_meta', array($this, 'filter_plugin_row_meta'), 10, 2 );
			/* That which can be added without discussion, can be removed without discussion. */
			remove_filter('the_title', 'capital_P_dangit', 11);
			remove_filter('the_content', 'capital_P_dangit', 11);
			remove_filter('comment_text', 'capital_P_dangit', 31);
			$this->options = get_option('zigweather2_options');
		}
	
	
		public function activate() {
			if (!$this->options = get_option('zigweather2_options')) { 
				$this->options = array(); 
				add_option('zigweather2_options', $this->options);
				$this->options['key'] = '';
				$this->options['which_css'] = '2'; # light option
				$this->options['which_temp'] = 'C'; # celsius
				$this->options['which_speed'] = 'K'; # kmph
				$this->options['show_fetched'] = '1'; # time fetched
				$this->options['hide_credit'] = '0'; # hide credit
				$this->options['debug'] = '0'; # debug
			}
			$this->options['delete_options_next_deactivate'] = 0; # always reset this
			update_option("zigweather2_options", $this->options);
		}
	
	
		public function deactivate() {
			if ($this->options['delete_options_next_deactivate'] == 1) delete_option("zigweather2_options");
		}
	
	
		# ACTIONS
	
	
		public function action_wp_enqueue_scripts() {
			if ($this->options['which_css'] > 0) { 
				wp_enqueue_style('zigweather', $this->plugin_folder . 'css/zigweather.' . $this->options['which_css'] . '.css', false, date('Ymd'));
			}
		}
	
	
		public function action_admin_init() {
			new zigweather_admincallbacks(@$this->params['zigaction']);
		}
	
	
		public function action_admin_enqueue_scripts() {
			wp_enqueue_style('zigweatheradmin', $this->plugin_folder . 'css/admin.css', false, date('Ymd'));
		}
	
	
		public function action_admin_menu() {
			add_options_page('ZigWeather Options', 'ZigWeather', 'manage_options', 'zigweather-options', array($this, 'admin_page_options'));
		}
	
	
		# FILTERS
	
	
		public function filter_plugin_row_meta($links, $file) {
			$plugin = plugin_basename(__FILE__);
			$newlinks = array(
				'<a target="_blank" href="http://www.zigpress.com/donations/">Donate</a>',
				'<a href="' . get_admin_url() . 'options-general.php?page=zigweather-options">Settings</a>',
			);
			if ($file == $plugin) return array_merge($links, $newlinks);
			return $links;
		}
	
	
		# ADMIN CONTENT
	
	
		public function admin_page_options() {
			if (!current_user_can('manage_options')) { wp_die('You are not allowed to do this.'); }
			if ($this->result_type != '') echo $this->show_result($this->result_type, $this->result_message);
			?>
			<div class="wrap zigweather-admin">
			<div id="icon-zigweather" class="icon32"><br /></div>
			<h2>ZigWeather - Options</h2>
			<div class="wrap-left">
			<div class="col-pad">
			<p>The location to retrieve weather data for is now entered in each widget control panel. The options below affect all widgets.</p>
			<p><strong>NOTE:</strong> if your key stops working, simply apply for a new one. World Weather Online changed their API in summer 2013.</p>
			<form action="<?php echo $_SERVER['PHP_SELF']?>?page=zigweather-options" method="post">
			<input type="hidden" name="zigaction" value="zigweather-admin-options-update" />
			<?php wp_nonce_field('zigpress_nonce'); ?>
			<table class="form-table">
			<tr valign="top">
			<th scope="row" class="right">World Weather Online key:</th>
			<td><input name="key" type="text" id="key" value="<?php echo esc_attr($this->options['key']) ?>" class="regular-text" /><br /><span class="description">Get a free key by registering at <a target="_blank" href="https://developer.worldweatheronline.com/auth/register">https://developer.worldweatheronline.com/auth/register</a></span></td>
			</tr>
			<tr valign="top">
			<th scope="row" class="right">Load stylesheet:</th>
			<td><select name="which_css" id="which_css">
			<option value="0">[none]</option>
			<option value="1" <?php echo ($this->options['which_css'] == '1') ? 'selected="selected"' : ''?> >Layout Only</option>
			<option value="2" <?php echo ($this->options['which_css'] == '2') ? 'selected="selected"' : ''?> >Light Theme</option>
			<option value="3" <?php echo ($this->options['which_css'] == '3') ? 'selected="selected"' : ''?> >Dark Theme</option>
			</select> <span class="description">You can still put overrides in your theme stylesheet</span></td>
			</tr>
			<tr valign="top">
			<th scope="row" class="right">Show temperature in:</th>
			<td><select name="which_temp" id="which_temp">
			<option value="C" <?php echo ($this->options['which_temp'] == 'C') ? 'selected="selected"' : ''?> >Celsius</option>
			<option value="F" <?php echo ($this->options['which_temp'] == 'F') ? 'selected="selected"' : ''?> >Fahrenheit</option>
			</select> <span class="description"></span></td>
			</tr>
			<tr valign="top">
			<th scope="row" class="right">Show windspeed in:</th>
			<td><select name="which_speed" id="which_speed">
			<option value="K" <?php echo ($this->options['which_speed'] == 'K') ? 'selected="selected"' : ''?> >km/h</option>
			<option value="M" <?php echo ($this->options['which_speed'] == 'M') ? 'selected="selected"' : ''?> >mph</option>
			</select> <span class="description"></span></td>
			</tr>
			<tr valign="top">
			<th scope="row" class="right">Show time fetched:</th>
			<td><input class="checkbox" type="checkbox" name="show_fetched" id="show_fetched" value="1" <?php if (@$this->options['show_fetched'] == 1) { echo('checked="checked"'); } ?> /></td>
			</tr>
			<tr valign="top">
			<th scope="row" class="right">Hide ZigPress credit:</th>
			<td><input class="checkbox" type="checkbox" name="hide_credit" id="hide_credit" value="1" <?php if (@$this->options['hide_credit'] == 1) { echo('checked="checked"'); } ?> /> <span class="description">Please consider leaving the credit visible or making a donation - thanks!</span></td>
			</tr>
			<tr valign="top">
			<th scope="row" class="right">Show debug info below:</th>
			<td><input class="checkbox" type="checkbox" name="debug" id="debug" value="1" <?php if (@$this->options['debug'] == 1) { echo('checked="checked"'); } ?> /> <span class="description">Shows the current option data held by the plugin</span></td>
			</tr>
			<tr valign="top">
			<th scope="row" class="right">Deactivation kills options:</th>
			<td><input class="checkbox" type="checkbox" name="delete_options_next_deactivate" id="delete_options_next_deactivate" value="1" <?php if (@$this->options['delete_options_next_deactivate'] == 1) { echo('checked="checked"'); } ?> /> <span class="description">Remove stored options on next deactivation</span></td>
			</tr>
			</table>
			<p class="submit"><input type="submit" name="Submit" class="button-primary" value="Save Changes" /></p> 
			</form>
			</div><!--col-pad-->
			</div><!--wrap-left-->
			<div class="wrap-right">
			<table class="widefat donate" cellspacing="0">
			<thead>
			<tr><th>Support this plugin!</th></tr>
			</thead>
			<tr><td>
			<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_blank">
			<input type="hidden" name="cmd" value="_s-xclick">
			<input type="hidden" name="hosted_button_id" value="GT252NPAFY8NN">
			<input type="image" src="https://www.paypal.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
			<img alt="" border="0" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1">
			</form>
			<p>If you find ZigWeather useful, please keep it free and actively developed by making a donation.</p>
			<p>Suggested donation: &euro;10 - &euro;20 or an amount of your choice. Thanks!</p>
			</td></tr>
			</table>
			<table class="widefat donate" cellspacing="0">
			<thead>
			<tr><th><img class="icon floatRight" src="<?php echo $this->plugin_folder?>images/icon-16x16-zp.png" alt="Yes" title="Yes" />Brought to you by ZigPress</th></tr>
			</thead>
			<tr><td>
			<p><a href="http://www.zigpress.com/">ZigPress</a> is engaged in WordPress consultancy, solutions and research. We have also released a number of free plugins to support the WordPress community.</p>
			<p><a target="_blank" href="http://www.zigpress.com/plugins/zigweather/"><img class="icon" src="<?php echo $this->plugin_folder?>images/weather-few-clouds.png" alt="ZigWeather WordPress plugin by ZigPress" title="ZigWeather WordPress plugin by ZigPress" /> ZigWeather page</a></p>
			<p><a target="_blank" href="http://www.zigpress.com/plugins/"><img class="icon" src="<?php echo $this->plugin_folder?>images/plugin.png" alt="WordPress plugins by ZigPress" title="WordPress plugins by ZigPress" /> Other ZigPress plugins</a></p>
			<p><a target="_blank" href="http://www.facebook.com/zigpress"><img class="icon" src="<?php echo $this->plugin_folder?>images/facebook.png" alt="ZigPress on Facebook" title="ZigPress on Facebook" /> ZigPress on Facebook</a></p>
			<p><a target="_blank" href="http://twitter.com/ZigPress"><img class="icon" src="<?php echo $this->plugin_folder?>images/twitter.png" alt="ZigPress on Twitter" title="ZigPress on Twitter" /> ZigPress on Twitter</a></p>
			</td></tr>
			</table>
			</div><!--wrap-right-->
			<div class="clearer">&nbsp;</div>
			<?php
			if (@$this->options['debug'] == '1') {
				?>
				<h3>Debug Information</h3>
				<pre><?php print_r($this->options)?></pre>
				<?php
			}
			?>
			</div><!--/wrap-->
			<?php
		}
		
		
		# FUNCTIONS
		
		
		function maybe_clear_caches() {
			if (@$this->options['clearcaches'] == 1){
				$this->options['cache_time'] = array(); # clear ALL
				$this->options['cache_content'] = array();
				$this->options['clearcaches'] = 0;
				update_option("zigweather2_options", $this->options);
			}
		}


		function maybe_clear_cache_time($widget_id) {
			if (!$cache_time = @$this->options['cache_time'][$widget_id]){
				# couldn't get cache time so initialise it
				$this->options['cache_time'][$widget_id] = 0;
				update_option("zigweather2_options", $this->options);
			}
		}	
		
		
		function maybe_clear_cache_content($widget_id) {
			if (!$cache_content = @$this->options['cache_content'][$widget_id]){
				# couldn't get cache content so initialise it - and make sure the time is reset too
				$this->options['cache_time'][$widget_id] = 0;
				$this->options['cache_content'][$widget_id] = '';
				update_option("zigweather2_options", $this->options);
			}
		}
		
		
		function maybe_fetch_data($widget_id, $location) {
			if (time() - 1800 > (@$this->options['cache_time'][$widget_id])) { # 30 minutes
				# get feed and cache it
				$ch = curl_init();

				# old version
				#curl_setopt($ch, CURLOPT_URL, 'http://free.worldweatheronline.com/feed/weather.ashx?key=' . $this->options['key'] . '&q=' . urlencode($location) . '&format=json');

				# new version
				curl_setopt($ch, CURLOPT_URL, 'http://api.worldweatheronline.com/free/v1/weather.ashx?key=' . $this->options['key'] . '&q=' . urlencode($location) . '&format=json');

				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
				$data = curl_exec($ch);
				curl_close($ch);
				if ($info = json_decode($data, true)) {
					$this->options['cache_time'][$widget_id] = time();
					$this->options['cache_content'][$widget_id] = $data;
					update_option("zigweather2_options", $this->options);
				}
			}
		}
		
		
		function get_location($info) {
			return $info['data']['request'][0]['query'];
		}
		
		
		function get_tempf($info) {
			return $info['data']['current_condition'][0]['temp_F'];
		}
	
	
		function get_tempc($info) {
			return $info['data']['current_condition'][0]['temp_C'];
		}
		
		
		function get_speedm($info) {
			return $info['data']['current_condition'][0]['windspeedMiles'];
		}
		
		
		function get_speedk($info) {
			return $info['data']['current_condition'][0]['windspeedKmph'];
		}
		
		
		function get_direction($info) {
			return $info['data']['current_condition'][0]['winddir16Point'];
		}
		
		
		function get_humidity($info) {
			return $info['data']['current_condition'][0]['humidity'];
		}
		
		
		function get_iconurl($info) {
			return $info['data']['current_condition'][0]['weatherIconUrl'][0]['value'];
		}
		
		
		function get_description($info) {
			return $info['data']['current_condition'][0]['weatherDesc'][0]['value'];
		}


		# UTILITIES
	
	
		public function get_params() {
			$this->params = array();
			foreach ($_REQUEST as $key=>$value) {
				$this->params[$key] = $value;
				if (!is_array($this->params[$key])) { $this->params[$key] = strip_tags(stripslashes(trim($this->params[$key]))); }
				# need to sanitise arrays as well really
			}
			if (!is_numeric(@$this->params['zigpage'])) { $this->params['zigpage'] = 1; }
			if ((@$this->params['zigaction'] == '') && (@$this->params['zigaction2'] != '')) { $this->params['zigaction'] = $this->params['zigaction2']; }
			$this->result = '';
			$this->result_type = '';
			$this->result_message = '';
			if ($this->result = base64_decode(@$this->params['r'])) list($this->result_type, $this->result_message) = explode('|', $this->result); # base64 for ease of encoding
		}
	
	
		public function show_result($strType, $strMessage) {
			$strOutput = '';
			if ($strMessage != '') {
				$strClass = '';
				switch (strtoupper($strType)) {
					case 'OK' :
						$strClass = 'updated';
					break;
					case 'INFO' :
						$strClass = 'updated highlight';
					break;
					case 'ERR' :
						$strClass = 'error';
					break;
					case 'WARN' :
						$strClass = 'error';
					break;
				}
				if ($strClass != '') {
					$strOutput .= '<div class="msg ' . $strClass . '" title="Click to hide"><p>' . $strMessage . '</p></div>';
				}
			}
			return $strOutput;
		}
	
	
		public function validate_as_integer($param, $default = 0, $min = -1, $max = -1) {
			if (!is_numeric($param)) $param = $default;
			$param = (int) $param;
			if ($min != -1) { if ($param < $min) $param = $min; }
			if ($max != -1) { if ($param > $max) $param = $max; }
			return $param;
		}
	
	
		function get_all_post_meta($id = 0) {
			if ($id == 0) {
				global $wp_query;
				$content_array = $wp_query->get_queried_object();
				$id = $content_array->ID;
			}
			$data = array();
			global $wpdb;
			$wpdb->query("SELECT meta_key, meta_value FROM {$wpdb->postmeta} WHERE post_id = {$id} ");
			foreach($wpdb->last_result as $k => $v) {
				$data[$v->meta_key] = $v->meta_value;
			}
			return $data;
		}
	
	
	} # END OF CLASS


} else {
	wp_die('Namespace clash! Class zigweather already exists.');
}


# INSTANTIATE PLUGIN


$zigweather = new zigweather();
register_activation_hook(__FILE__, array(&$zigweather, 'activate'));
register_deactivation_hook(__FILE__, array(&$zigweather, 'deactivate'));


# EOF
