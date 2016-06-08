

<?php
function register_organizations_requirement()
{
	$MODULE_VARS = [
		"uid" => "organizations",
		"translatable_title" => _("Organizations Module"),
		"dependencies_array" => [ "have_database_access" ],
		"wants" => [ "auth" ],
		"getSharedInfo" => function () {
			return [
				"database" => [
					"title" => _("Organizations Database"),
					"default_value" => "coral_organizations"
				],
				"config_file" => [
					"path" => "organizations/admin/configuration.ini",
				]
			];
		}
	];
	return array_merge( $MODULE_VARS, [
		"installer" => function($shared_module_info) use ($MODULE_VARS) {
			$return = new stdClass();
			$return->yield = new stdClass();
			$return->success = false;
			$return->yield->title = _("Organizations Module");

			$this_db_name = $shared_module_info[ $MODULE_VARS["uid"] ]["db_name"];
			$dbconnection = $shared_module_info["provided"]["get_db_connection"]( $this_db_name );

			$result = $shared_module_info["provided"]["check_db"]($dbconnection, $shared_module_info[$MODULE_VARS["uid"]], "Organization", $MODULE_VARS["translatable_title"]);
			if ($result)
				return $result;

			// Process sql files
			$sql_files_to_process = ["organizations/install/test_create.sql", "organizations/install/create_tables_data.sql"];
			$ret = $shared_module_info["provided"]["process_sql_files"]( $dbconnection, $sql_files_to_process, $MODULE_VARS["uid"] );
			if (!$ret["success"])
			{
				$return->success = false;
				$return->yield->messages = array_merge($return->yield->messages, $ret["messages"]);
				return $return;
			}

			$shared_module_info["provided"]["set_up_admin_in_db"]($dbconnection, $shared_module_info["common"]["default_user"]["username"]);

			// BUILD AND WRITE CONFIG FILE
			$configFile = $MODULE_VARS["getSharedInfo"]()["config_file"]["path"];
			$iniData = array();
			//config file: database
			$iniData["database"] = [
				"type" => "mysql",
				"host" => Config::dbInfo("host"),
				"name" => $this_db_name,
				"username" => Config::dbInfo("username"),
				"password" => Config::dbInfo("password")
			];
			//config file: ldap
			if ($shared_module_info["auth"]["ldap_enabled"])
			{
				$iniData["ldap"] = [
					"host" => $shared_module_info["auth"]["host"],
					"search_key" => $shared_module_info["auth"]["search_key"],
					"base_dn" => $shared_module_info["auth"]["base_dn"],
					"fname_field" => $shared_module_info["auth"]["fname"],
					"lname_field" => $shared_module_info["auth"]["lname"]
				];
			}
			//config file: settings
			$cooperating_modules = [
				"licensing" => "needs_db",
				"auth" => "needs_db",
				"resources" => "needs_db",
				"usage" => "doesnt_need_db"
			];
			foreach ($cooperating_modules as $key => $value) {
				if (isset($shared_module_info["modules_to_use"][$key]["useModule"]))
				{
					$iniData["settings"]["{$key}Module"] = $shared_module_info["modules_to_use"][$key]["useModule"] ? 'Y' : 'N';
					if ($value == "needs_db" && $shared_module_info["modules_to_use"][$key]["useModule"])
						$iniData["settings"]["{$key}DatabaseName"] = $shared_module_info[$key]["db_name"];
				}
			}
			if ($iniData["settings"]["authModule"] == 'N')
			{
				$iniData["settings"]["remoteAuthVariableName"] = $shared_module_info["auth"]["alternative"]["remote_auth_variable_name"];
			}
			//write out config file
			$shared_module_info["provided"]["write_config_file"]($configFile, $iniData);

			$return->success = true;
			return $return;
		}
	]);
}
