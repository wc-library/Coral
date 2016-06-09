<?php
function register_resources_requirement()
{
	$MODULE_VARS = [
		"uid" => "resources",
		"translatable_title" => _("Resources Module"),
		"dependencies_array" => [ "db_tools", "have_read_write_access_to_config", "modules_to_use" ],
		"required" => false,
		"wants" => [ "auth" ],
		"getSharedInfo" => function () {
			return [
				"database" => [
					"title" => _("Resources Database"),
					"default_value" => "coral_resources"
				],
				"config_file" => [
					"path" => "resources/admin/configuration.ini",
				]
			];
		}
	];
	return array_merge( $MODULE_VARS, [
		"installer" => function($shared_module_info) use ($MODULE_VARS) {
			$return = new stdClass();
			$return->yield = new stdClass();
			$return->success = false;
			$return->yield->title = _("Resources Module");
			$return->yield->messages[] = "Incomplete Installer";


			$this_db_name = $shared_module_info[ $MODULE_VARS["uid"] ]["db_name"];
			$dbconnection = $shared_module_info["provided"]["get_db_connection"]( $this_db_name );

			$result = $shared_module_info["provided"]["check_db"]($dbconnection, $shared_module_info[$MODULE_VARS["uid"]], "Resource", $MODULE_VARS["translatable_title"]);
			if ($result)
				return $result;

			$sql_files_to_process = ["resources/install/protected/test_create.sql", "resources/install/protected/install.sql"];
			$ret = $shared_module_info["provided"]["process_sql_files"]( $dbconnection, $sql_files_to_process, $MODULE_VARS["uid"] );
			if (!$ret["success"])
			{
				$return->success = false;
				$return->yield->messages = array_merge($return->yield->messages, $ret["messages"]);
				return $return;
			}

			$shared_module_info["provided"]["set_up_admin_in_db"]($dbconnection, $shared_module_info["common"]["default_user"]["username"]);

			$defaultCurrencyOptions = [ "ARS","AUD","CAD","EUR","GBP","SEK","USD" ];
			$_SESSION[$MODULE_VARS["uid"]]["defaultCurrency"] = isset($_POST["defaultCurrency"]) && in_array($_POST["defaultCurrency"], $defaultCurrencyOptions) ? $_POST["defaultCurrency"] : "USD";
			$_SESSION[$MODULE_VARS["uid"]]["enableAlerts"] = isset($_POST["enableAlerts"]) ? $_POST["enableAlerts"] : true;
			$_SESSION[$MODULE_VARS["uid"]]["catalogURL"] = isset($_POST["catalogURL"]) ? $_POST["catalogURL"] : "";
			$_SESSION[$MODULE_VARS["uid"]]["feedbackEmailAddress"] = isset($_POST["feedbackEmailAddress"]) ? $_POST["feedbackEmailAddress"] : "";

			$resources_fields = [
				[
					"key" => "defaultCurrency",
					"title" => _("Default Currency"),
					"options" => $defaultCurrencyOptions,
					"type" => "select",
					"default_value" => $_SESSION[$MODULE_VARS["uid"]]["defaultCurrency"]
				],[
					"key" => "enableAlerts",
					"title" => _("Enable Alerts"),
					"type" => "checkbox",
					"default_value" => $_SESSION[$MODULE_VARS["uid"]]["enableAlerts"]
				],[
					"key" => "catalogURL",
					"type" => "text",
					"title" => _("Catalog URL"),
					"default_value" => $_SESSION[$MODULE_VARS["uid"]]["catalogURL"]
				],[
					"key" => "feedbackEmailAddress",
					"type" => "text",
					"title" => _("Feedback Email Address"),
					"default_value" => $_SESSION[$MODULE_VARS["uid"]]["feedbackEmailAddress"]
				]
			];

			if (!isset($_POST["enableAlerts"]) && !isset($_SESSION[$MODULE_VARS["uid"]]["enableAlerts"]))
			{
				require_once "install/templates/resources_module_template.php";
				$title = _("Please set up the following options for the resources module.");
				$return->yield->body = resources_module_template($title, $resources_fields);
				$return->success = false;
				return $return;
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
				"usage"			=> "doesnt_need_db",
				"organizations"	=> "needs_db"
			];
			foreach ($cooperating_modules as $key => $value)
			{
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
