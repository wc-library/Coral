<?php
function register_remote_auth_provider()
{
	return [
		"uid" => "remote_auth",
		"translatable_title" => _("Remote Auth"),
		"hide_from_completion_list" => true,
		"installer" => function($shared_module_info) {
			$return = new stdClass();
			$return->yield = new stdClass();
			$return->success = true;
			$return->yield->messages = [];
			$return->yield->title = _("Installation Variable Set");

			$field = [
				"uid" => "remote_auth_variable",
				"title" => _("Remote Auth Variable"),
				"default_value" => ""
			];
			require_once "install/templates/text_field_template.php";

			// If !set
			// If !valid
				//make sure variable name has matched number of ', otherwise it will bomb the program
				// if((substr_count($remoteAuthVariableName, "'") % 2)!==0){
				// 	$errorMessage[] = 'Make sure Remote Auth Variable Name has matched single or double quotes';
				// }

			return $return;
		}
	];
}
