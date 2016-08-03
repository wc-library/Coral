<?php
function register_upgrade_helper_provider()
{
	try
	{
		require_once("common/Config.php");
		$dynamic_dependencies = array_merge(["have_read_write_access_to_config"], Config::getInstalledModules());
	}
	catch (Exception $e)
	{
		// not installed!
		/// TODO: figure out what to do...
		$dynamic_dependencies = [];
	}


	return [
		"uid" => "upgrade_helper",
		"translatable_title" => _("Incremental Upgrade"),
		"required_for" => [Installer::REQUIRED_FOR_UPGRADE],
		"hide_from_completion_list" => true,
		"bundle" => function($version) use ($dynamic_dependencies){
			return [
				"dependencies_array" => $dynamic_dependencies,
				"function" => function($shared_module_info) use ($version){
					$return = new stdClass();
					$return->yield = new stdClass();
					$return->success = true;
					$return->yield->messages = [];
					$return->yield->title = _("Incremental Upgrade: ") . $version;

					// $confData = [
					// 	"installation_details" => [
					// 		"version" => $version
					// 	]
					// ];
					// foreach ($shared_module_info["modules_to_use"]["useModule"] as $key => $value) {
					// 	$confData[$key] = [
					// 		"enabled" => $value ? "Y" : "N",
					// 		"installed" => $value ? "Y" : "N",
					// 	];
					// }
					//
					// require_once "common/Config.php";
					// $shared_module_info["provided"]["write_config_file"](Config::CONFIG_FILE_PATH, $confData);

					return $return;
				}
			];
		}
	];
}
