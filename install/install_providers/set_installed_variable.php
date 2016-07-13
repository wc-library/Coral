<?php
function register_set_installed_variable_provider()
{
	return [
		"uid" => "set_installed_variable",
		"translatable_title" => _("Installation Variable Set"),
		"dependencies_array" => [ "modules_to_use_helper", "have_read_write_access_to_config" ],
		"required_for" => [Installer::REQUIRED_FOR_INSTALL],
		"hide_from_completion_list" => true,
		"installer" => function($shared_module_info) {
			$return = new stdClass();
			$return->yield = new stdClass();
			$return->success = true;
			$return->yield->messages = [];
			$return->yield->title = _("Installation Variable Set");

			$confData = [
				"installation_details" => [
					"version" => INSTALLATION_VERSION
				]
			];
			foreach ($shared_module_info["modules_to_use"]["useModule"] as $key => $value) {
				$confData[$key] = [
					"enabled" => $value ? "Y" : "N",
					"installed" => $value ? "Y" : "N",
				];
			}

			require_once "common/Config.php";
			$shared_module_info["provided"]["write_config_file"](Config::CONFIG_FILE_PATH, $confData);

			return $return;
		}
	];
}
