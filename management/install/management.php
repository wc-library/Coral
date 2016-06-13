<?php
function register_management_requirement()
{
	$MODULE_VARS = [
		"uid" => "management",
		"translatable_title" => _("Management Module"),
		"dependencies_array" => [ "db_tools", "have_read_write_access_to_config", "modules_to_use" ],
		"sharedInfo" => [
			"database" => [
				"title" => _("Management Database"),
				"default_value" => "coral_management"
			],
			"config_file" => [
				"path" => "management/admin/configuration.ini",
			]
		]
	];
	return array_merge( $MODULE_VARS, [
		"installer" => function($shared_module_info) use ($MODULE_VARS) {
			$return = new stdClass();
			$return->yield = new stdClass();
			$return->success = true;
			$return->yield->title = _("Management Module");

			$this_db_name = $shared_module_info[ $MODULE_VARS["uid"] ]["db_name"];
			$dbconnection = $shared_module_info["provided"]["get_db_connection"]( $this_db_name );


			$result = $shared_module_info["provided"]["check_db"]($dbconnection, $shared_module_info[$MODULE_VARS["uid"]], "Management", $MODULE_VARS["translatable_title"]);
			if ($result)
				return $result;


			// Process sql files
			$sql_files_to_process = ["management/install/protected/test_create.sql", "management/install/protected/install.sql"];
			$ret = $shared_module_info["provided"]["process_sql_files"]( $dbconnection, $sql_files_to_process, $MODULE_VARS["uid"] );
			if (!$ret["success"])
			{
				$return->success = false;
				$return->yield->messages = array_merge($return->yield->messages, $ret["messages"]);
				return $return;
			}

			$shared_module_info["provided"]["set_up_admin_in_db"]($dbconnection, $shared_module_info["common"]["default_user"]["username"]);

			$configFile = $MODULE_VARS["sharedInfo"]["config_file"]["path"];

			// TODO: check that missing settings are not looked for in the management module (and so an error will be throw, e.g., if organizations is not set t/f here)
			$iniData = array();
			$iniData["settings"] = [];
			if (isset($shared_module_info["modules_to_use"]["auth"]["useModule"]) && $shared_module_info["modules_to_use"]["auth"]["useModule"])
			{
				$iniData["settings"]["authModule"] = 'Y';
				$iniData["settings"]["authDatabaseName"] = $shared_module_info["auth"]["db_name"];
			}
			else
			{
				$iniData["settings"]["authModule"] = 'N';
				$iniData["settings"]["remoteAuthVariableName"] = $shared_module_info["auth"]["alternative"]["remote_auth_variable_name"];
			}
			$iniData["database"] = [
				"type" => "mysql",
				"host" => Config::dbInfo("host"),
				"name" => $this_db_name,
				"username" => Config::dbInfo("username"),
				"password" => Config::dbInfo("password")
			];
			$shared_module_info["provided"]["write_config_file"]($configFile, $iniData);

			return $return;
		}
	]);
}
