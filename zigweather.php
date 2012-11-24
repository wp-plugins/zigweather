<?php
/*
Plugin Name: ZigWeather
Plugin URI: http://www.zigpress.com/plugins/zigweather/
Description: Completely rebuilt plugin to show current weather conditions.
Version: 2.1
Author: ZigPress
Requires at least: 3.3
Tested up to: 3.4.2
Author URI: http://www.zigpress.com/
License: GPLv2
*/


/*
Copyright (c) 2010-2012 ZigPress

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


require_once dirname(__FILE__) . '/optionbuilder.php';
require_once dirname(__FILE__) . '/admincallbacks.php';
require_once dirname(__FILE__) . '/widgets.php';


if (!class_exists('zigweather')) {


	class zigweather
		{
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
			if (version_compare(phpversion(), '5.2.4', '<')) wp_die('ZigWeather requires PHP 5.2.4 or newer. Please update your server.'); 
			if (version_compare($wp_version, '3.3', '<')) wp_die('ZigWeather requires WordPress 3.3 or newer. Please update your installation.'); 
			$this->get_params();
			if (!$this->options = get_option('zigweather2_options')) new zigweather_optionbuilder();
			$this->callback_url = preg_replace('/\?.*/', '', $_SERVER['REQUEST_URI']);
			add_action('widgets_init', create_function('', 'return register_widget("widget_zigweather");'));
			add_action('wp_enqueue_scripts', array($this, 'action_wp_enqueue_scripts'));
			add_action('admin_init', array($this, 'action_admin_init'));
			add_action('admin_head', array($this, 'action_admin_head'));
			add_action('admin_menu', array($this, 'action_admin_menu'));
			add_filter('plugin_row_meta', array($this, 'filter_plugin_row_meta'), 10, 2 );
		}
	
	
		public function activate() {
			new zigweather_optionbuilder();
		}
	
	
		public function deactivate() {
			if ($this->options['delete_options_next_deactivate'] == 1) delete_option("zigweather2_options");
		}
	
	
		# ACTIONS
	
	
		public function action_wp_enqueue_scripts() {
			if ($this->options['which_css'] > 0) wp_enqueue_style('zigweather', $this->plugin_folder . 'css/zigweather.' . $this->options['which_css'] . '.css');
		}
	
	
		public function action_admin_init() {
			new zigweather_admincallbacks($this->params['zigaction']);
		}
	
	
		public function action_admin_head() {
			?>
			<link rel="stylesheet" href="<?php echo $this->plugin_folder?>css/admin.css?<?php echo rand()?>" type="text/css" media="screen" />
			<?php
		}
	
	
		public function action_admin_menu() {
			add_options_page('ZigWeather Options', 'ZigWeather', 'manage_options', 'zigweather-options', array($this, 'admin_page_options'));
		}
	
	
		# FILTERS
	
	
		public function filter_plugin_row_meta($links, $file) {
			$plugin = plugin_basename(__FILE__);
			if ($file == $plugin) return array_merge($links, array('<a target="_blank" href="http://www.zigpress.com/donations/">Donate</a>'));
			return $links;
		}
	
	
		# DATABASE
	
	
		# ADMIN CONTENT
	
	
		public function admin_page_options() {
			if (!current_user_can('manage_options')) { wp_die(__('You are not allowed to do this.', 'zigcrm')); }
			if ($this->result_type != '') echo $this->show_result($this->result_type, $this->result_message);
			?>
			<div class="wrap zigweather-admin">
			<div id="icon-zigweather" class="icon32"><br /></div>
			<h2>ZigWeather - Options</h2>
			<div class="wrap-left">
			<div class="col-pad">
	
			<p>The location to retrieve weather data for is now entered in each widget control panel. The options below affect all widgets.</p>
	
			<form action="<?php echo $_SERVER['PHP_SELF']?>?page=zigweather-options" method="post">
			<input type="hidden" name="zigaction" value="zigweather-admin-options-update" />
			<?php wp_nonce_field('zigpress_nonce'); ?>
			<table class="form-table">
			<tr valign="top">
			<th scope="row" class="right">World Weather Online API key:</th>
			<td><input name="key" type="text" id="key" value="<?php echo esc_attr($this->options['key']) ?>" class="regular-text" /><br /><span class="description">Sign up at <a target="_blank" href="http://www.worldweatheronline.com/register.aspx">http://www.worldweatheronline.com/register.aspx</a></span></td>
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
			<td><input class="checkbox" type="checkbox" name="show_fetched" id="show_fetched" value="1" <?php if ($this->options['show_fetched'] == 1) { echo('checked="checked"'); } ?> /></td>
			</tr>
			<tr valign="top">
			<th scope="row" class="right">Next deactivation removes:</th>
			<td><input class="checkbox" type="checkbox" name="delete_options_next_deactivate" id="delete_options_next_deactivate" value="1" <?php if ($this->options['delete_options_next_deactivate'] == 1) { echo('checked="checked"'); } ?> /> Options &nbsp; &nbsp;</td>
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
			<tr><th><img class="icon floatRight" src="<?php echo $this->plugin_folder?>images/favicon.zigpress.png" alt="Yes" title="Yes" />Brought to you by ZigPress</th></tr>
			</thead>
			<tr><td>
			<p><a href="http://www.zigpress.com/">ZigPress</a> is a technical blog aimed at WordPress users and developers. We have also released a number of free plugins to support the WordPress community.</p>
			<p><a target="_blank" href="http://www.zigpress.com/plugins/zigweather/"><img class="icon" src="<?php echo $this->plugin_folder?>images/weather-few-clouds.png" alt="ZigWeather WordPress plugin by ZigPress" title="ZigWeather WordPress plugin by ZigPress" /> ZigWeather page</a></p>
			<p><a target="_blank" href="http://www.zigpress.com/plugins/"><img class="icon" src="<?php echo $this->plugin_folder?>images/plugin.png" alt="WordPress plugins by ZigPress" title="WordPress plugins by ZigPress" /> Other ZigPress plugins</a></p>
			<p><a target="_blank" href="http://www.facebook.com/zigpress"><img class="icon" src="<?php echo $this->plugin_folder?>images/facebook.png" alt="ZigPress on Facebook" title="ZigPress on Facebook" /> ZigPress on Facebook</a></p>
			<p><a target="_blank" href="http://twitter.com/ZigPress"><img class="icon" src="<?php echo $this->plugin_folder?>images/twitter.png" alt="ZigPress on Twitter" title="ZigPress on Twitter" /> ZigPress on Twitter</a></p>
			</td></tr>
			</table>
			</div><!--wrap-right-->
			<div class="clearer">&nbsp;</div>
			</div><!--/wrap-->
			<?php
		}
	
	
		# UTILITIES
	
	
		public function get_params() {
			$this->params = array();
			foreach ($_REQUEST as $key=>$value) {
				$this->params[$key] = $value;
				if (!is_array($this->params[$key])) { $this->params[$key] = strip_tags(stripslashes(trim($this->params[$key]))); }
				# need to sanitise arrays as well really
			}
			if (!is_numeric($this->params['zigpage'])) { $this->params['zigpage'] = 1; }
			if (($this->params['zigaction'] == '') && ($this->params['zigaction2'] != '')) { $this->params['zigaction'] = $this->params['zigaction2']; }
			$this->result = '';
			$this->result_type = '';
			$this->result_message = '';
			if ($this->result = base64_decode($this->params['r'])) list($this->result_type, $this->result_message) = explode('|', $this->result); # base64 for ease of encoding
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
	
	
		function get_all_user_meta($id = 0) {
			if ($id == 0) {
				global $current_user;
				get_currentuserinfo();
				$id = $current_user->ID;
			}
			$data = array();
			global $wpdb;
			$wpdb->query("SELECT meta_key, meta_value FROM {$wpdb->usermeta} WHERE user_id = {$id} ");
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
