<?php
function register_set_installed_variable_requirement()
{
	return [
		"uid" => "set_installed_variable",
		"translatable_title" => _("Installation Variable Set"),
		"dependencies_array" => [ "modules_to_use_helper", "have_read_write_access_to_config" ],
		"required" => true,
		"hide_from_completion_list" => true,
		"installer" => function($shared_module_info) {
			$return = new stdClass();
			$return->yield = new stdClass();
			$return->success = true;
			$return->yield->messages = [];
			$return->yield->title = _("Installation Variable Set");

			// require_once "common/Config.php";
			$shared_module_info["provided"]["write_config_file"](Config::CONFIG_FILE_PATH, [
				"installation_details" => [
					"version" => INSTALLATION_VERSION
				]
			]);

			return $return;
		}
	];
}
