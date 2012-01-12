<?php


class zigweather_admincallbacks
	{


	public function __construct($zigaction)
		{
		if ($zigaction == 'zigweather-admin-options-update') { $this->update_options(); }
		}


	public function update_options()
		{
		if (!current_user_can('manage_options')) { wp_die('You are not allowed to do this.'); }
		global $zigweather;
		check_admin_referer('zigpress_nonce');
		$zigweather->options['load_css'] = $zigweather->validate_as_integer(htmlspecialchars($zigweather->params['load_css']), 0, 0, 1);
		$zigweather->options['delete_options_next_deactivate'] = $zigweather->validate_as_integer(htmlspecialchars($zigweather->params['delete_options_next_deactivate']), 0, 0, 1);
		$zigweather->options['key'] = htmlspecialchars($zigweather->params['key']);
		# re-save options
		update_option("zigweather2_options", $zigweather->options);
		$zigweather->result = 'OK|Options saved.'; 
		ob_clean();
		wp_redirect($_SERVER['PHP_SELF'] . '?page=zigweather-options&r=' . base64_encode($zigweather->result));
		exit();
		}


	}


# EOF