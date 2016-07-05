<?php
function register_licensing_requirement()
{
	$MODULE_VARS = [
		"uid" => "licensing",
		"translatable_title" => _("Licensing Module"),
		"dependencies_array" => [ "db_tools", "have_read_write_access_to_config", "modules_to_use", "have_default_coral_admin_user", "have_default_db_user" ],
		"required" => true, // TODO: is this module really required?
		"wants" => [ "auth" ], // Doesn't actually want auth but it seems as though auth should come before it.
		"sharedInfo" => [
			"database" => [
				"title" => _("Licensing Database"),
				"default_value" => "coral_licensing"
			],
			"config_file" => [
				"path" => "licensing/admin/configuration.ini",
			]
		]
	];
	return array_merge( $MODULE_VARS, [
		"installer" => function($shared_module_info) use ($MODULE_VARS) {
			$return = new stdClass();
			$return->yield = new stdClass();
			$return->success = false;
			$return->yield->title = _("Licensing Module");

			$this_db_name = $shared_module_info[ $MODULE_VARS["uid"] ]["db_name"];
			$dbconnection = $shared_module_info["provided"]["get_db_connection"]( $this_db_name );

			$result = $shared_module_info["provided"]["check_db"]($MODULE_VARS["uid"], $dbconnection, $shared_module_info[$MODULE_VARS["uid"]], "License", $MODULE_VARS["translatable_title"]);
			if ($result)
				return $result;

			$sql_files_to_process = ["licensing/install/protected/test_create.sql", "licensing/install/protected/install.sql"];
			$ret = $shared_module_info["provided"]["process_sql_files"]( $dbconnection, $sql_files_to_process, $MODULE_VARS["uid"] );
			if (!$ret["success"])
			{
				$return->success = false;
				$return->yield->messages = array_merge($return->yield->messages, $ret["messages"]);
				return $return;
			}

			$shared_module_info["provided"]["set_up_admin_in_db"]($dbconnection, $shared_module_info["have_default_coral_admin_user"]["default_user"]);

			$uttField = [
				"name" => "useTermsToolFunctionality",
				"label" => _("Use Terms Tool Functionality"),
				"default" => isset($_SESSION[ $MODULE_VARS["uid"] ]["useTermsToolFunctionality"]) ? $_SESSION[ $MODULE_VARS["uid"] ]["useTermsToolFunctionality"] : true
			];
			if (isset($_POST[ $uttField["name"] ]))
			{
				$_SESSION[ $MODULE_VARS["uid"] ]["useTermsToolFunctionality"] = $_POST[ $uttField["name"] ];
			}
			if (!isset($_SESSION[ $MODULE_VARS["uid"] ]["useTermsToolFunctionality"]))
			{
				require_once "install/templates/licensing_module_template.php";
				$return->yield->body = licensing_module_template($uttField);
				$return->success = false;
				return $return;
			}

			$configFile = $MODULE_VARS["sharedInfo"]["config_file"]["path"];
			$iniData = array();
			$iniData["settings"] = [
				"useTermsToolFunctionality" => $_SESSION[ $MODULE_VARS["uid"] ]["useTermsToolFunctionality"] ? "Y" : "N"
			];

			$installed_module_details = $shared_module_info["provided"]["get_modules_to_use_config"]($shared_module_info);
			$iniData["settings"] = array_merge($iniData["settings"], $installed_module_details);

			$iniData["database"] = [
				"type" => "mysql",
				"host" => Config::dbInfo("host"),
				"name" => $this_db_name,
				"username" => $shared_module_info["have_default_db_user"]["username"],
				"password" => $shared_module_info["have_default_db_user"]["password"]
			];

			$shared_module_info["provided"]["write_config_file"]($configFile, $iniData);

			$return->success = true;
			return $return;
		},
		"upgrader" => function($version_number){
			$return = new stdClass();
			switch ($version_number) {
				case '2.1.0':
					$return->version    = "2.1.0";
					$return->sql_files  = [
						"path" => "protected/sql_v2.1.0.sql"
					];
					$return->conf_files = [
						[
							"path" => "common/configuration.ini",
							"new_values" => [
								"settings" => [
									"username_for_something" => "fred",
									"ldap_blahblah" => "something else"
								],
								"something_new" => [
									"user_input" => $_POST
								]
							]
						]
					];
					break;
				default:
					return null;
					break;
			}
		}
	]);
}
