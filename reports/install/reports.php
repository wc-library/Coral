<?php
function register_reports_requirement()
{
	$MODULE_VARS = [
		"uid" => "reports",
		"translatable_title" => _("Reports Module"),
		"dependencies_array" => [ "db_tools", "have_read_write_access_to_config", "modules_to_use", "usage" ],
		"required" => false,
		"wants" => [],
		"getSharedInfo" => function () {
			return [
				"database" => [
					"title" => _("Reports Database"),
					"default_value" => "coral_reports"
				],
				"config_file" => [
					"path" => "reports/admin/configuration.ini",
				]
			];
		}
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

			$result = $shared_module_info["provided"]["check_db"]($dbconnection, $shared_module_info[$MODULE_VARS["uid"]], "Resource", $MODULE_VARS["translatable_title"]);
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





			//next check the usage database exists
			$dbcheck = @mysql_select_db("$usage_database_name");
			if (!$dbcheck) {
				$errorMessage[] = "Unable to access the usage database '" . $usage_database_name . "'.  Please verify it has been created.<br />MySQL Error: " . mysql_error();
			}else{

				//passed db host, name check, test that user can select from License database
				$result = mysql_query("SELECT outlierID FROM " . $usage_database_name . ".Outlier WHERE outlierLevel = '1';");
				if (!$result){
					$errorMessage[] = "Unable to select from the Outlier table in database '" . $usage_database_name . "' with user '" . $database_username . "'.  Please complete the Usage install and verify the database has been set up.  Error: " . mysql_error();
				}
			}





			//set up config file
			$configFile = $MODULE_VARS["getSharedInfo"]()["config_file"]["path"];
			$iniData = array();
			$iniData["settings"] = [
				"defaultCurrency" 		=> $_SESSION[$MODULE_VARS["uid"]]["defaultCurrency"],
				"enableAlerts" 			=> $_SESSION[$MODULE_VARS["uid"]]["enableAlerts"],
				"catalogURL" 			=> $_SESSION[$MODULE_VARS["uid"]]["catalogURL"],
				"feedbackEmailAddress" 	=> $_SESSION[$MODULE_VARS["uid"]]["feedbackEmailAddress"]
			];
			//config file: settings
			$cooperating_modules = [
				"licensing"		=> "needs_db",
				"auth"			=> "needs_db",
				"reports"		=> "needs_db",
				"usage"			=> "doesnt_need_db",
				"organizations"	=> "needs_db"
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
			//config file: database
			$iniData["database"] = [
				"type" => "mysql",
				"host" => Config::dbInfo("host"),
				"name" => $this_db_name,
				"username" => Config::dbInfo("username"),
				"password" => Config::dbInfo("password")
			];
			//config file: ldap
			if (isset($shared_module_info["modules_to_use"]["auth"]["useModule"]) && $shared_module_info["modules_to_use"]["auth"]["useModule"])
			{
				if ($shared_module_info["auth"]["ldap_enabled"])
				{
					$iniData["ldap"] = [
						"host"			=> $shared_module_info["auth"]["host"],
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
