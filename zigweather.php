<?php
/*
Plugin Name: ZigWeather
Plugin URI: http://www.zigpress.com/wordpress/plugins/zigweather/
Description: Adds a sidebar widget to show your current weather. Data is provided by the weather.com XOAP feed.
Author: ZigPress
Version: 0.8.2
Author URI: http://www.zigpress.com/
License: GPLv2
*/


/*
Copyright (c) 2010-2011 ZigPress, All Rights Reserved

This program is free software; you can redistribute it and/or modify 
it under the terms of the GNU General Public License as published by 
the Free Software Foundation; version 2 of the License.

This program is distributed in the hope that it will be useful, 
but WITHOUT ANY WARRANTY; without even the implied warranty of 
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the 
GNU General Public License for more details. 

You should have received a copy of the GNU General Public License 
along with this program; if not, write to the Free Software 
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA 
*/


/*
ZigPress PHP code uses Whitesmiths indent style: http://en.wikipedia.org/wiki/Indent_style#Whitesmiths_style
*/


# VERSION CHECK


global $wp_version;
if (version_compare($wp_version, "3.0", "<")) 
	{ 
	exit(__('ZigWeather requires WordPress 3.0 or newer. Please update your installation.', 'zigweather')); 
	}
if (floatval(phpversion()) < 5)
	{
	exit(__('ZigWeather requires PHP 5 or newer. Please update your server.', 'zigweather')); 
	}


# DEFINE PLUGIN


if (!class_exists('ZigWeather'))
	{
	class ZigWeather
		{
		public $PluginFolder;
		public $NL;
		public $Params;
		public $Options;
		public $DB;
		private $IconFolder;
		private $CacheDurations;


		public function __construct() # executed whenever plugin is instantiated
			{
			global $wpdb;
			$this->DB = &$wpdb;
			$this->Name = 'ZigWeather';
			$this->NL = "\n";
			$this->Params = array();
			$this->GetParams();
			$this->PluginFolder = get_bloginfo('url') . '/' . PLUGINDIR.'/'.dirname(plugin_basename(__FILE__)) . '/'; # override due to other zig plugin
			add_action('init', array($this, 'ActionInit'));
			add_action('wp_head', array($this, 'ActionWpHead'));
			add_action('admin_init', array($this, 'ActionAdminInit'));
			add_action('admin_head', array($this, 'ActionAdminHead'));
			add_action('admin_menu', array($this, 'ActionAdminMenu'));
			add_filter('plugin_row_meta', array($this, 'FilterPluginRowMeta'), 10, 2 );
			$this->Options = get_option('zigweather_options');
			$this->IconFolder = $this->PluginFolder . 'images/';
			$this->CacheDurations = array(
				900 => '15 minutes',
				1800 => '30 minutes',
				2700 => '45 minutes',
				3600 => '1 hour',
				7200 => '2 hours'
				);
			}


		# ACTIVATION & DEACTIVATION


		public function Activate()
			{
			if (!$this->Options = get_option('zigweather_options')) 
				{ 
				$this->Options = array(); 
				add_option('zigweather_options', $this->Options);
				}
			if (!isset($this->Options['widgettitle'])) { $this->Options['widgettitle'] = 'ZigWeather'; }
			if (!isset($this->Options['partnerid'])) { $this->Options['partnerid'] = '1036672568'; }
			if (!isset($this->Options['licensekey'])) { $this->Options['licensekey'] = '561e1a02298548de'; }
			if (!isset($this->Options['cachetime'])) { $this->Options['cachetime'] = 3600; }
			if (!isset($this->Options['location'])) { $this->Options['location'] = 'MTXX0001'; }
			if (!isset($this->Options['locationname'])) { $this->Options['locationname'] = 'Valletta, Malta'; }
			if (!isset($this->Options['unit'])) { $this->Options['unit'] = 'C'; }
			if (!isset($this->Options['showwind'])) { $this->Options['showwind'] = 1; }
			if (!isset($this->Options['showhumidity'])) { $this->Options['showhumidity'] = 1; }
			$this->Options['cache'] = ''; # clear cache when activating plugin
			$this->Options['lastcheck'] = 0;
			update_option("zigweather_options", $this->Options); # re-save in case we added anything
			}


		public function Deactivate()
			{
			$this->Options['cache'] = ''; # clear cache when deactivating plugin
			$this->Options['lastcheck'] = 0;
			update_option("zigweather_options", $this->Options);
			}


		# ACTIONS


		public function ActionInit()
			{
			load_plugin_textdomain('zigweather', null, dirname(plugin_basename(__FILE__)) . '/languages/');
			register_sidebar_widget('ZigWeather', array($this, 'DoWidget'));
			register_widget_control('ZigWeather', array($this, 'DoWidgetControl'));
			}


		public function ActionWpHead()
			{
			?>
			<!-- BEGIN ZigWeather HEAD Insert -->
			<link rel="stylesheet" href="<?php echo $this->PluginFolder?>css/zigweather.css?<?php echo rand(10000,99999)?>" type="text/css" media="screen" />
			<!-- END ZigWeather HEAD Insert -->
			<?php
			}


		public function ActionAdminInit()
			{
			load_plugin_textdomain('zigweather', null, dirname(plugin_basename(__FILE__)) . '/languages/');
			}


		public function ActionAdminHead()
			{
			?>
			<link rel="stylesheet" href="<?php echo $this->PluginFolder?>css/zigweather.css?<?php echo rand()?>" type="text/css" media="screen" />
			<?php
			}


		public function ActionAdminMenu()
			{
			add_options_page('ZigWeather Options', 'ZigWeather', 'manage_options', 'zigweather-settings', array($this, 'DoAdminPage'));
			}


		public function FilterPluginRowMeta($links, $file) 
			{
			$plugin = plugin_basename(__FILE__);
			if ($file == $plugin) return array_merge($links, array('<a target="_blank" href="http://www.zigpress.com/donations/">Donate</a>'));
			return $links;
			}


		# UTILITIES


		public function GetParams()
			{
			$this->Params = array();
			foreach ($_REQUEST as $key=>$value)
				{
				$this->Params[$key] = $value;
				if (!is_array($this->Params[$key])) { $this->Params[$key] = strip_tags(stripslashes(trim($this->Params[$key]))); }
				# should sanitise arrays as well really
				}
			}


		public function ShowMessage($strMessage)
			{
			echo '<div id="message" class="updated" title="Click to hide"><p>' . $strMessage . '</p></div>';
			}


		# ADMIN UI


		public function DoAdminSidebar()
			{
			?>
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
			<p>Suggested donation: &euro;10 or an amount of your choice. Thanks!</p>
			</td></tr>
			</table>
			<table class="widefat donate" cellspacing="0">
			<thead>
			<tr><th><img class="icon floatRight" src="<?php echo $this->PluginFolder?>images/zigpress.ico" alt="Yes" title="Yes" />Brought to you by ZigPress</th></tr>
			</thead>
			<tr><td>
			<p><a href="http://www.zigpress.com/">ZigPress</a> is a web agency specialising in WordPress-based solutions. We have also released a number of free plugins to support the WordPress community.</p>
			<p><a target="_blank" href="http://www.zigpress.com/wordpress/plugins/zigweather/"><img class="icon" src="<?php echo $this->PluginFolder?>images/weather-few-clouds.png" alt="ZigWeather WordPress plugin ZigPress" title="ZigWeather WordPress plugin by ZigPress" /> ZigWeather page</a> (comments welcome!)</p>
			<p><a target="_blank" href="http://www.zigpress.com/wordpress/plugins/"><img class="icon" src="<?php echo $this->PluginFolder?>images/plugin.png" alt="WordPress plugins by ZigPress" title="WordPress plugins by ZigPress" /> Other ZigPress plugins</a></p>
			<p><a target="_blank" href="http://www.facebook.com/pages/ZigPress/171766958751"><img class="icon" src="<?php echo $this->PluginFolder?>images/facebook.png" alt="ZigPress on Facebook" title="ZigPress on Facebook" /> ZigPress on Facebook</a></p>
			<p><a target="_blank" href="http://twitter.com/ZigPress"><img class="icon" src="<?php echo $this->PluginFolder?>images/twitter.png" alt="ZigPress on Twitter" title="ZigPress on Twitter" /> ZigPress on Twitter</a></p>
			</td></tr>
			</table>
			<?php
			}


		public function DoAdminPage()
			{
			if ($this->Params['zigaction'] == 'citysearch')
				{
				# ajax city search
				if (current_user_can('manage_options')) # this is how we protect this call
					{
					$xml = file_get_contents('http://xoap.weather.com/search/search?where=' . htmlentities($this->Params['where']));
					ob_clean();
					header('Content-Type: text/xml');
					echo $xml;
					exit();
					}
				}
			if (isset($this->Params['zigweather-settings-submit']))
				{
				check_admin_referer('zigpress_nonce');
				$this->Options['partnerid'] = htmlspecialchars($this->Params['partnerid']);
				$this->Options['licensekey'] = htmlspecialchars($this->Params['licensekey']);
				$this->Options['unit'] = htmlspecialchars($this->Params['unit']);
				$this->Options['cachetime'] = htmlspecialchars($this->Params['cachetime']);
				if (strpos(htmlspecialchars($this->Params['location']), '|') !== false)
					{
					list($this->Options['location'], $this->Options['locationname']) = explode('|', htmlspecialchars($this->Params['location']));
					}
				$this->Options['cache'] = ''; # clear cache when updating widget options
				$this->Options['lastcheck'] = 0;
				$this->Options['showwind'] = (htmlspecialchars($this->Params['showwind']) == '1') ? 1 : 0;
				$this->Options['showhumidity'] = (htmlspecialchars($this->Params['showhumidity']) == '1') ? 1 : 0;
				update_option("zigweather_options", $this->Options);
				ob_clean();
				wp_redirect($_SERVER['PHP_SELF'] . '?page=zigweather-settings&message=1');
				exit();
				}
			if ($this->Params['message'] == 1)
				{
				$this->ShowMessage(__('Settings saved - now go and place the widget!', 'zigweather'));
				}
			?>
			<div class="wrap zigweather-admin">
			<div id="icon-zigweather" class="icon32"><br /></div>
			<h2><?php _e('ZigWeather Settings', 'zigweather')?></h2>
			<div class="wrap-left">
			<div class="col-pad">
			<form id="frmZigWeather" action="<?php echo $_SERVER['REQUEST_URI']?>" method="post">
			<input type="hidden" name="zigaction" value="update" />
			<input type="hidden" id="zigweather-settings-submit" name="zigweather-settings-submit" value="1" />
			<?php wp_nonce_field('zigpress_nonce'); ?>
			<table class="form-table">
			<tr valign="top">
			<th scope="row"><label for="partnerid"><?php _e('Weather.com Partner ID', 'zigweather')?>:</label></th>
			<td><input name="partnerid" type="text" id="partnerid" value="<?php echo esc_attr($this->Options['partnerid']) ?>" class="medium-text" /> <span class="description">Plugin supplied with: 1036672568</span></td>
			</tr>
			<tr valign="top">
			<th scope="row"><label for="licensekey"><?php _e('Weather.com License Key', 'zigweather')?>:</label></th>
			<td><input name="licensekey" type="text" id="licensekey" value="<?php echo esc_attr($this->Options['licensekey']) ?>" class="medium-text" /> <span class="description">Plugin supplied with: 561e1a02298548de</span></td>
			</tr>
			<tr valign="top">
			<th scope="row"><label for="showwind"><?php _e('Show wind information', 'zigweather')?>:</label></th>
			<td><input name="showwind" type="checkbox" id="showwind" value="1" <?php echo ($this->Options['showwind'] == 1) ? 'checked="checked"' : '' ?> /> <span class="description">&nbsp;</span></td>
			</tr>
			<tr valign="top">
			<th scope="row"><label for="showhumidity"><?php _e('Show humidity', 'zigweather')?>:</label></th>
			<td><input name="showhumidity" type="checkbox" id="showhumidity" value="1" <?php echo ($this->Options['showhumidity'] == 1) ? 'checked="checked"' : '' ?> /> <span class="description">&nbsp;</span></td>
			</tr>
			<tr valign="top">
			<th scope="row"><label for="unit"><?php _e('Temperature units', 'zigweather')?>:</label></th>
			<td><select name="unit" id="unit">
				<option value="C" <?php echo ($this->Options['unit'] == 'C') ? 'selected="selected"' : ''?> >Celsius</option>
				<option value="F" <?php echo ($this->Options['unit'] == 'F') ? 'selected="selected"' : ''?> >Fahrenheit</option>
			</select></td>
			</tr>
			<tr valign="top">
			<th scope="row"><label for="unit"><?php _e('Cache time', 'zigweather')?>:</label></th>
			<td><select name="cachetime" id="cachetime"><?php
			foreach ($this->CacheDurations as $intSeconds=>$strDuration)
				{
				?>
				<option value="<?php echo $intSeconds?>" <?php if ($intSeconds == $this->Options['cachetime']) { echo('selected="selected"'); } ?> ><?php _e($strDuration, 'zigweather')?></option>
				<?php
				}
			?></select></td>
			</tr>

				<tr valign="top" style="background:#e7e7e7;">
				<th scope="row"><label><?php _e('Current city', 'zigweather')?>:</label></th>
				<td><span class="description"><?php echo esc_attr($this->Options['locationname']) . ' [' . esc_attr($this->Options['location']) . ']'?></span></td>
				</tr>
				<tr valign="top" style="background:#e7e7e7;">
				<th scope="row"><label for="search"><?php _e('Live city search', 'zigweather')?>:</label></th>
				<td>
					<input name="search" type="text" id="search" value="" class="medium-text" /> 
					<input class="button-secondary" type="button" id="btnZigWeatherSearch" value="Search" />
					<select name="location" id="location" style="display:none;">
					<option value="" selected="selected">Leave as is [<?php echo esc_attr($this->Options['location']) ?>]</option>
					</select> 
					<img id="imgLoader" style="margin-top:4px; display:none;" src="<?php echo $this->PluginFolder?>/images/ajax-loader.gif" alt="" />
				</td>
				</tr>
			</table>
			<p class="submit"> 
			<input type="submit" name="Submit" class="button-primary" value="<?php _e('Save Changes')?>" /> 
			</p> 
			</form>
			<p>The cache is cleared each time you save changes.</p>
			<p>To get your own Weather.com Partner ID and License Key, <a href="https://registration.weather.com/ursa/xmloap/step1">sign up</a> then go to <a href="https://registration.weather.com/ursa/xmloap/step2">this URL</a>.</p>
			<p>Deactivating the plugin will <strong>not</strong> delete your settings.</p>
			</div><!--col-pad-->
			</div><!--wrap-left-->
			<div class="wrap-right">
			<?php
			$this->DoAdminSidebar();
			?>
			</div><!--wrap-right-->
			<div class="clearer">&nbsp;</div>
			</div><!--wrap-->
			<script type="text/javascript">
			jQuery(document).ready(function(){
				jQuery('#message').click(function(){jQuery(this).hide();});
				jQuery('#btnZigWeatherSearch').click(function(){
					jQuery('#search, #btnZigWeatherSearch').hide();
					jQuery('#imgLoader').show();
					where = jQuery('#search').val();
					jQuery.get('<?php echo $_SERVER['REQUEST_URI']?>', {'zigaction' : 'citysearch', 'where' : where}, function(objXML){
						jQuery(objXML).find('loc').each(function(){
							options = jQuery('#location').attr('options');
							options[options.length] = new Option(jQuery(this).text() + ' [' + jQuery(this).attr('id') + ']', jQuery(this).attr('id') + '|' + jQuery(this).text());
						});
						jQuery('#location').show();
						jQuery('#imgLoader').hide();
					}, 'xml');
				});
			});
			</script>
			<?php
			}


		# WIDGET METHODS


		public function DoWidget($args)
			{
			extract($args);
			echo $before_widget . $this->NL . $before_title . $this->Options['widgettitle'] . $after_title . $this->NL;
			echo '<div class="zigweather-wrap">' . $this->NL;
			$strURL = 'http://xoap.weather.com/weather/local/' . $this->Options['location'] . '?cc=*&link=xoap&prod=xoap&unit=' . (($this->Options['unit']) == 'C' ? 'm' : 's') . '&par=' . $this->Options['partnerid'] . '&key=' . $this->Options['licensekey'];
			#echo $strURL;
			if ((time() - $this->Options['cachetime'] < $this->Options['lastcheck']) && ($this->Options['cache'] != '')) 
				{
				# display from cache
				echo $this->Options['cache'];
				}
			else
				{
				# get and display fresh
				$strData = file_get_contents($strURL);
				if($strData != "") 
					{
					$intPos = strpos($strData, "<cc>");
					$strReceivedContent = substr($strData, $intPos, strpos($strData,"</cc>") - $intPos);
					if($strReceivedContent != "") 
						{
						$intPos = strpos($strReceivedContent,"<obst>") + 6;
						$strReceivedLocation = substr($strReceivedContent, $intPos, strpos($strReceivedContent, "</obst>") - $intPos);
						$intPos = strpos($strReceivedContent, "<tmp>") + 5;
						$strReceivedTemp = substr($strReceivedContent, $intPos, strpos($strReceivedContent, "</tmp>") - $intPos);
						$intPos = strpos($strReceivedContent, "<flik>") + 6;
						$strReceivedFeelsLike = substr($strReceivedContent, $intPos, strpos($strReceivedContent, "</flik>") - $intPos);
						$intPos = strpos($strReceivedContent, "<t>") + 3;
						$strReceivedConditions = substr($strReceivedContent, $intPos, strpos($strReceivedContent, "</t>") - $intPos);
						$intPos = strpos($strReceivedContent, "<icon>") + 6;
						$intIconNumber = substr($strReceivedContent, $intPos, strpos($strReceivedContent, "</icon>") - $intPos);
						$intPos = strpos($strReceivedContent, "<s>") + 3;
						$strReceivedWindSpeed = substr($strReceivedContent, $intPos, strpos($strReceivedContent, "</s>") - $intPos);
						$intPos = strpos($strReceivedContent, "<wind>") + 6;
						$strReceivedWindBearing = substr($strReceivedContent, $intPos);
						$intPos = strpos($strReceivedWindBearing, "<t>") + 3;
						$strReceivedWindBearing = substr($strReceivedWindBearing, $intPos, strpos($strReceivedWindBearing, "</t>") - $intPos);
						$intPos = strpos($strReceivedContent, "<hmid>") + 6;
						$strReceivedHumidity = substr($strReceivedContent, $intPos, strpos($strReceivedContent, "</hmid>") - $intPos);
						}
					}
				$strContent = '';
				$strContent .= '<img class="icon" src="' . $this->IconFolder . $intIconNumber . '.png" alt="' . $strReceivedConditions . '" title="' . $strReceivedConditions . '" />';
				$strContent .= '<div class="location">' . $strReceivedLocation . '</div>';
				$strContent .= '<div class="conditions">' . $strReceivedConditions . '</div>';
				$strContent .= '<span class="actualtemp">' . $strReceivedTemp . '&deg;' . $this->Options['unit'] . '</span>';
				$strContent .= '<span class="feelslike">Feels like ' . $strReceivedFeelsLike . '&deg;' . $this->Options['unit'] . '</span>';
				if ($this->Options['showwind'] == 1)
					{
					$strContent .= '<div class="wind">Wind: ' . $strReceivedWindSpeed . ' knots, ' . $strReceivedWindBearing . '</div>';
					}
				if ($this->Options['showhumidity'] == 1)
					{
					$strContent .= '<div class="humidity">Humidity: ' . $strReceivedHumidity . '%</div>';
					}
				$strContent .= '<div class="acknowledgement"><a href="http://www.weather.com/?prod=xoap&par=' . $this->Options['partnerid'] . '">Data provided by weather.com&reg;</a></div>';
				echo $strContent . $this->NL;
				$this->Options['cache'] = $strContent;
				$this->Options['lastcheck'] = time();
				update_option('zigweather_options',$this->Options);
				}
			echo '</div><!--/zigweather-wrap-->' . $this->NL;
			echo $after_widget . $this->NL;
			}


		public function DoWidgetControl()
			{
			if ($this->Params['zigweather-widgetsubmit']) 
				{
				$this->Options['widgettitle'] = htmlspecialchars($this->Params['zigweather-widgettitle']);
				update_option("zigweather_options", $this->Options);
				}
			?>
			<p><label for="zigweather-widgettitle"><?php _e('Title', 'zigweather')?>:</label><br /><input class="widefat" type="text" id="zigweather-widgettitle" name="zigweather-widgettitle" value="<?php echo $this->Options['widgettitle'];?>" /></p>
			<input type="hidden" id="zigweather-widgetsubmit" name="zigweather-widgetsubmit" value="1" />
			<?php
			}


		} # end of class

	}
else
	{
	exit('Class ZigWeather already declared!');
	}


# INSTANTIATE PLUGIN


$objZigWeather = new ZigWeather();


# INTEGRATE PLUGIN


if (isset($objZigWeather))
	{
	register_activation_hook(__FILE__, array(&$objZigWeather, 'Activate'));
	register_deactivation_hook(__FILE__, array(&$objZigWeather, 'Deactivate'));
	}


# EOF

