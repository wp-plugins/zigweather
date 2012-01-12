<?php


class zigweather_option_builder
	{
	function __construct()
		{
		if (!$this->options = get_option('zigweather2_options')) 
			{ 
			$this->options = array(); 
			add_option('zigweather2_options', $this->options);
			$this->options['key'] = '';
			$this->options['load_css'] = 1;
			}
		$this->options['delete_options_next_deactivate'] = 0; # always reset this
		update_option("zigweather2_options", $this->options);
		}
	}


# EOF
