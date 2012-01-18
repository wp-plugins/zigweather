<?php


class zigweather_option_builder # done on activate
	{
	function __construct()
		{
		if (!$this->options = get_option('zigweather2_options')) 
			{ 
			$this->options = array(); 
			add_option('zigweather2_options', $this->options);
			$this->options['key'] = '';
			$this->options['which_css'] = 2; # light option
			}
		$this->options['delete_options_next_deactivate'] = 0; # always reset this
		update_option("zigweather2_options", $this->options);
		}
	}


# EOF
