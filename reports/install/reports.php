<?php
function register_reports_requirement()
{
	$MODULE_VARS = [
		"uid" => "reports",
		"translatable_title" => _("Reports Module"),
		"dependencies_array" => [ "db_tools", "have_read_write_access_to_config", "modules_to_use", "usage", "have_default_db_user" ],
		"required" => false,
		"wants" => [],
		"sharedInfo" => [
			"database" => [
				"title" => _("Reports Database"),
				"default_value" => "coral_reports"
			],
			"config_file" => [
				"path" => "reports/admin/configuration.ini",
			]
		]
	];
	return array_merge( $MODULE_VARS, [
		"installer" => function($shared_module_info) use ($MODULE_VARS) {
			$return = new stdClass();
			$return->yield = new stdClass();
			$return->success = false;
			$return->yield->title = _("Reports Module");
			$return->yield->messages[] = "Incomplete Installer";


			$this_db_name = $shared_module_info[ $MODULE_VARS["uid"] ]["db_name"];
			$dbconnection = $shared_module_info["provided"]["get_db_connection"]( $this_db_name );

			$result = $shared_module_info["provided"]["check_db"]($MODULE_VARS["uid"], $dbconnection, $shared_module_info[$MODULE_VARS["uid"]], "Resource", $MODULE_VARS["translatable_title"]);
			if ($result)
				return $result;

			$sql_files_to_process = ["reports/install/test_create.sql", "reports/install/create_tables_data.sql"];
			$ret = $shared_module_info["provided"]["process_sql_files"]( $dbconnection, $sql_files_to_process, $MODULE_VARS["uid"] );
			if (!$ret["success"])
			{
				$return->success = false;
				$return->yield->messages = array_merge($return->yield->messages, $ret["messages"]);
				return $return;
			}

			//set up config file
			$configFile = $MODULE_VARS["sharedInfo"]["config_file"]["path"];
			$iniData = array();
			$iniData["settings"] = [
				"baseURL" => $shared_module_info["usage"]["baseUrl"]
			];
			//config file: database
			$iniData["database"] = [
				"type" => "mysql",
				"host" => Config::dbInfo("host"),
				"name" => $this_db_name,
				"username" => $shared_module_info["have_default_db_user"]["username"],
				"password" => $shared_module_info["have_default_db_user"]["password"],
				//TODO: yes, this is horrible but when it's unified it will go away - it should at least not be under "database"
				"usageDatabaseName" => $shared_module_info["usage"]["db_name"]
			];
			//config file: ldap
			if (isset($shared_module_info["modules_to_use"]["auth"]["useModule"]) && $shared_module_info["modules_to_use"]["auth"]["useModule"])
			{
				if ($shared_module_info["auth"]["ldap_enabled"])
				{
					$iniData["ldap"] = [
						"host"				=> $shared_module_info["auth"]["host"],
						"search_key"	=> $shared_module_info["auth"]["search_key"],
						"base_dn"		=> $shared_module_info["auth"]["base_dn"],
						"fname_field"	=> $shared_module_info["auth"]["fname"],
						"lname_field"	=> $shared_module_info["auth"]["lname"]
					];
				}
			}
			//config file: write out
			$shared_module_info["provided"]["write_config_file"]($configFile, $iniData);

			$return->success = true;
			return $return;
		}
	]);
}
