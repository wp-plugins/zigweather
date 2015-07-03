<?php


class widget_zigweather extends WP_Widget 
{


	public function __construct() {
		parent::__construct(false, $name = 'ZigWeather', array('description'=>"Shows a weather panel"), array('width'=>'400'));
	}


	public function widget($args, $instance) {		
		global $zigweather;
		extract($args);
		$title = esc_attr($instance['title']);
		$location = esc_attr($instance['location']);
		$zigweather->maybe_clear_caches();
		$zigweather->maybe_clear_cache_time($widget_id);
		$zigweather->maybe_clear_cache_content($widget_id);
		$zigweather->maybe_fetch_data($widget_id, $location);

		# after all the possible data manipulations, get the proper content
		$cache_content = $zigweather->options['cache_content'][$widget_id];
		
		# if ok, decode json and output the panel
		echo $before_widget; 
		if ($title) echo $before_title . $title . $after_title; 
		$error = false;
		if ($info = json_decode($cache_content, true)) {
			if ($zigweather->get_location($info) != '') {
				?>
				<div class="zigweather-wrapper">
				<div class="location"><?php echo $zigweather->get_location($info)?></div>
				<div class="icon"><img src="<?php echo $zigweather->get_iconurl($info)?>" alt="" /></div>
				<div class="description"><?php echo $zigweather->get_description($info)?></div>
				<?php
				if ($zigweather->options['which_temp'] == 'F') {
					?><div class="temperature">Temperature: <?php echo $zigweather->get_tempf($info)?>&deg;F</div><?php
				} else {
					?><div class="temperature">Temperature: <?php echo $zigweather->get_tempc($info)?>&deg;C</div><?php
				}
				if ($zigweather->options['which_speed'] == 'M') {
					?><div class="wind">Wind: <?php echo $zigweather->get_speedm($info)?> mph <?php echo $zigweather->get_direction($info)?></div><?php
				} else {
					?><div class="wind">Wind: <?php echo $zigweather->get_speedk($info)?> km/h <?php echo $zigweather->get_direction($info)?></div><?php
				}
				?>
				<div class="humidity">Humidity: <?php echo $zigweather->get_humidity($info)?>%</div>
				<?php
				if ($zigweather->options['show_fetched'] == 1) {
					?><div class="fetched">Fetched <?php echo date('H:i', $zigweather->options['cache_time'][$widget_id] + (3600 * get_option('gmt_offset')))?></div><?php
				}
				?>
				<div class="credit credit1">Powered by <a href="http://www.worldweatheronline.com/" title="Free local weather content provider" target="_blank">World Weather Online</a></div>
				<?php
				if ($zigweather->options['hide_credit'] != '1') {
					?><div class="credit credit2">Widget by <a href="http://www.zigpress.com/" title="Words about WordPress from Malta" target="_blank">ZigPress</a></div><?php
				}
				?>
				</div><!--/zigweather-wrapper-->
				<?php
			} else {
				$error = true;
			}
		} else {
			$error = true;
		}
		if ($error) {
			?>
			<div class="zigweather-error">Data cannot be shown</div>
			<?php
			$zigweather->options['clearcaches'] = 1;
			update_option("zigweather2_options", $zigweather->options);
		}
		echo $after_widget; 
	}


	public function update($new_instance, $old_instance) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		# did they change location?
		if ($instance['location'] != strip_tags($new_instance['location'])) {
			global $zigweather;
			$zigweather->options['clearcaches'] = 1;
			update_option("zigweather2_options", $zigweather->options);
		}
		$instance['location'] = strip_tags($new_instance['location']);
		return $instance;
	}


	public function form($instance) {
		global $zigweather;
		$title = esc_attr(@$instance['title']);
		$location = esc_attr(@$instance['location']);
		if ($zigweather->options['key'] == '') {
			?>
			<p>Enter the API key on the <a href="<?php bloginfo('url')?>/wp-admin/options-general.php?page=zigweather-options">settings page</a>!</p>
			<?php
		} else {
			?>
			<p>Title: <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></p>
			<p>Location: <input class="widefat" id="<?php echo $this->get_field_id('location'); ?>" name="<?php echo $this->get_field_name('location'); ?>" type="text" value="<?php echo $location; ?>" /></p>
			<p>You can try a few different formats in order to get the widget to show weather for your desired location:</p>
			<ul class="zigweather_widget_help">
			<li>city</li>
			<li>city, state (USA only)</li>
			<li>city, state, country</li>
			<li>city, country</li>
			<li>postal code (UK, USA, Canada)</li>
			</ul>
			<p>If the widget displays "Data cannot be shown" or shows the wrong location, try a different format or a different nearby location.</p>
			<?php 
		}
	}


} # end of class


# EOF
