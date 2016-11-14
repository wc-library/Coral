<?php
function register_resources_provider()
{
	$protected_module_data = [
		"config_file_path" => "resources/admin/configuration.ini"
	];
	$MODULE_VARS = [
		"uid" => "resources",
		"translatable_title" => _("Resources Module"),
	];
	return array_merge( $MODULE_VARS, [
		"bundle" => function($version) use ($MODULE_VARS, $protected_module_data) {
			switch ($version) {
				case Installer::VERSION_STRING_INSTALL:
					return [
						"dependencies_array" => ["db_tools", "have_read_write_access_to_config", "modules_to_use", "have_default_coral_admin_user", "have_default_db_user", "some_kind_of_auth"],
						"sharedInfo" => [
							"database" => [
								"title" => _("Resources Database"),
								"default_value" => "coral_resources"
							],
							"config_file" => [
								"path" => $protected_module_data["config_file_path"],
							]
						],
						"function" => function($shared_module_info) use ($MODULE_VARS, $protected_module_data) {
							$return = new stdClass();
							$return->yield = new stdClass();
							$return->success = false;
							$return->yield->title = _("Resources Module");

							$this_db_name = $shared_module_info[ $MODULE_VARS["uid"] ]["db_name"];
							$dbconnection = $shared_module_info["provided"]["get_db_connection"]( $this_db_name );

							$result = $shared_module_info["provided"]["check_db"]($MODULE_VARS["uid"], $dbconnection, $shared_module_info[$MODULE_VARS["uid"]], "Resource", $MODULE_VARS["translatable_title"]);
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

							$shared_module_info["provided"]["set_up_admin_in_db"]($dbconnection, $shared_module_info["have_default_coral_admin_user"]["default_user"]);

							$defaultDefaultCurrency = isset($_SESSION[$MODULE_VARS["uid"]]["defaultCurrency"]) ? $_SESSION[$MODULE_VARS["uid"]]["defaultCurrency"] : "USD";
							$defaultEnableAlerts = isset($_SESSION[$MODULE_VARS["uid"]]["enableAlerts"]) ? $_SESSION[$MODULE_VARS["uid"]]["enableAlerts"] : true;
							$defaultCatalogURL = isset($_SESSION[$MODULE_VARS["uid"]]["catalogURL"]) ? $_SESSION[$MODULE_VARS["uid"]]["catalogURL"] : "";
							$defaultFeedbackEmailAddress = isset($_SESSION[$MODULE_VARS["uid"]]["feedbackEmailAddress"]) ? $_SESSION[$MODULE_VARS["uid"]]["feedbackEmailAddress"] : "";

							$defaultCurrencyOptions = [ "ARS","AUD","CAD","EUR","GBP","SEK","USD" ];
							$_SESSION[$MODULE_VARS["uid"]]["defaultCurrency"] = isset($_POST["defaultCurrency"]) && in_array($_POST["defaultCurrency"], $defaultCurrencyOptions) ? $_POST["defaultCurrency"] : $defaultDefaultCurrency;
							$_SESSION[$MODULE_VARS["uid"]]["enableAlerts"] = isset($_POST["enableAlerts"]) ? $_POST["enableAlerts"] : $defaultEnableAlerts;
							$_SESSION[$MODULE_VARS["uid"]]["catalogURL"] = isset($_POST["catalogURL"]) ? $_POST["catalogURL"] : $defaultCatalogURL;
							$_SESSION[$MODULE_VARS["uid"]]["feedbackEmailAddress"] = isset($_POST["feedbackEmailAddress"]) ? $_POST["feedbackEmailAddress"] : $defaultFeedbackEmailAddress;

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

							if (!isset($_SESSION[$MODULE_VARS["uid"]]["formCompleted"]) || !$_SESSION[$MODULE_VARS["uid"]]["formCompleted"])
							{
								require_once "install/templates/resources_module_template.php";
								$title = _("Please set up the following options for the resources module.");
								$return->yield->body = resources_module_template($title, $resources_fields);
								$return->success = false;
								$_SESSION[$MODULE_VARS["uid"]]["formCompleted"] = true;
								return $return;
							}
							// To validate form - put validation code here and set `$_SESSION[$MODULE_VARS["uid"]]["formCompleted"] = false`


							//set up config file
							$configFile = $protected_module_data["config_file_path"];
							$iniData = array();
							//config file: settings
							$iniData["settings"] = [
								"defaultCurrency" 		=> $_SESSION[$MODULE_VARS["uid"]]["defaultCurrency"],
								"enableAlerts" 			=> $_SESSION[$MODULE_VARS["uid"]]["enableAlerts"] ? "Y" : "N",
								"catalogURL" 			=> $_SESSION[$MODULE_VARS["uid"]]["catalogURL"],
								"feedbackEmailAddress" 	=> $_SESSION[$MODULE_VARS["uid"]]["feedbackEmailAddress"]
							];
							$installed_module_details = $shared_module_info["provided"]["get_modules_to_use_config"]($shared_module_info);
							$iniData["settings"] = array_merge($iniData["settings"], $installed_module_details);
							//config file: database
							$iniData["database"] = [
								"type" => "mysql",
								"host" => Config::dbInfo("host"),
								"name" => $this_db_name,
								"username" => $shared_module_info["have_default_db_user"]["username"],
								"password" => $shared_module_info["have_default_db_user"]["password"]
							];
							//config file: ldap
							if (isset($shared_module_info["modules_to_use"]["useModule"]["auth"]) && $shared_module_info["modules_to_use"]["useModule"]["auth"])
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
					];


				/*
				* For more on this upgrader, see the licensing module.
				* Additionally, the auth module has helpfully commented code.
				*/
				case "2.0.0":
					$conf_data = parse_ini_file($protected_module_data["config_file_path"], true);
					return [
						"dependencies_array" => [ "db_tools", "have_read_write_access_to_config" ],
						"sharedInfo" => [
							"config_file" => [
								"path" => $protected_module_data["config_file_path"],
							],
							"database_name" => $conf_data["database"]["name"]
						],
						"function" => function($shared_module_info) use ($MODULE_VARS, $protected_module_data, $version) {
							$return = new stdClass();
							$return->success = true;
							$return->yield = new stdClass();
							$return->yield->title = _("Resources Module");
							$return->yield->messages = [];

							$conf_data = parse_ini_file($protected_module_data["config_file_path"], true);

							// Process sql files
							$sql_files_to_process = ["resources/install/protected/update_$version.sql"];
							$db_name = $conf_data["database"]["name"];
							$dbconnection = $shared_module_info["provided"]["get_db_connection"]( $db_name );
							$ret = $shared_module_info["provided"]["process_sql_files"]( $dbconnection, $sql_files_to_process, $MODULE_VARS["uid"] );
							if (!$ret["success"])
							{
								$return->success = false;
								$return->yield->messages = array_merge($return->yield->messages, $ret["messages"]);
								return $return;
							}

							return $return;
						}
					];


				default:
					return null;
			}
		}
	]);
}
