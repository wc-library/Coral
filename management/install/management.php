<?php
function register_management_requirement()
{
	$MODULE_VARS = [
		"uid" => "management",
		"translatable_title" => _("Management Module"),
		"dependencies_array" => [ "have_database_access", "have_read_write_access_to_config", "modules_to_use" ],
		"required" => true,
		"getSharedInfo" => function () {
			return [
				"database" => [
					"title" => _("Management Database"),
					"default_value" => "coral_management"
				],
				"config_file" => [
					"path" => "management/admin/configuration.ini",
				]
			];
		}
	];
	return array_merge( $MODULE_VARS, [
		"installer" => function($shared_module_info) use ($MODULE_VARS) {
			$return = new stdClass();
			$return->yield = new stdClass();
			$return->success = false;
			$return->yield->title = _("Management Module");
			$return->yield->messages[] = "<b>Installer Incomplete</b>";

			$this_db_name = $shared_module_info[ $MODULE_VARS["uid"] ]["db_name"];
			$dbconnection = $shared_module_info["provided"]["get_db_connection"]( $this_db_name );


			$configFile = $MODULE_VARS["getSharedInfo"]()["config_file"]["path"];

			$iniData = array();
			$iniData["settings"] = [];

			// TODO: Do any of these settings get used?
			//
			// if (isset($shared_module_info["modules_to_use"]["auth"]["useModule"]) && $shared_module_info["modules_to_use"]["auth"]["useModule"])
			// {
			// 	$iniData["settings"]["authModule"] = 'Y';
			// 	$iniData["settings"]["authDatabaseName"] = $shared_module_info["auth"]["db_name"];
			// }
			// else
			// {
			// 	$iniData["settings"]["authModule"] = 'N';
			// 	$iniData["settings"]["remoteAuthVariableName"] = $shared_module_info["auth"]["alternative"]["remote_auth_variable_name"];
			// }
			//
			// $cooperating_modules = [
			// 	"auth" => "needs_db",
			// 	"organizations" => "needs_db",
			// 	"resources" => "needs_db",
			// 	"usage" => "doesnt_need_db"
			// ];
			// foreach ($cooperating_modules as $key => $value) {
			// 	if (isset($shared_module_info["modules_to_use"][$key]["useModule"]))
			// 	{
			// 		$iniData["settings"]["{$key}Module"] = $shared_module_info["modules_to_use"][$key]["useModule"] ? 'Y' : 'N';
			// 		if ($value == "needs_db" && $shared_module_info["modules_to_use"][$key]["useModule"])
			// 			$iniData["settings"]["{$key}DatabaseName"] = $shared_module_info[$key]["db_name"];
			// 	}
			// }
			// if ($iniData["settings"]["authModule"] == 'N')
			// {
			// 	$iniData["settings"]["remoteAuthVariableName"] = $shared_module_info["auth"]["alternative"]["remote_auth_variable_name"];
			// }

			return $return;
		}
	]);
}
