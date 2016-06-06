<?php
function register_organizations_requirement()
{
	$MODULE_VARS = [
		"uid" => "organizations",
		"translatable_title" => _("Organizations Module"),
		"dependencies_array" => [ "have_database_access" ],
		"getSharedInfo" => function () {
			return [
				"database" => [
					"title" => _("Organizations Database"),
					"default_value" => "coral_organizations"
				],
				"config_file" => [
					"path" => "auth/admin/configuration.ini",
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
			$return->yield->messages[] = "<b>Installer Incomplete</b>";

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

			// Organization can use ldap
			$authIni = parse_ini_file($shared_module_info["auth"]["config_file"]["path"]);

			// TODO: Find out whether ldap here is the same as in auth
			if ($authIni["ldap_enabled"])
			{
				$ldap_explanation = "The following are optional for LDAP if you wish to have user's first and last name automatically populated";
				$ldap_fields = [
					// [
					// 	"key" => "ldap_host",
					// 	"title" => _("Host"),
					// 	"default_value" => isset($authIni["ldap"]["host"]) ? $authIni["ldap"]["host"] : ""
					// ],[
					// 	"key" => "ldap_port",
					// 	"title" => _("Port"),
					// 	"default_value" => isset($authIni["ldap"]["port"]) ? $authIni["ldap"]["port"] : ""
					// ],[
					// 	"key" => "ldap_search_key",
					// 	"title" => _("Search Key"),
					// 	"default_value" => isset($authIni["ldap"]["search_key"]) ? $authIni["ldap"]["search_key"] : ""
					// ],[
					// 	"key" => "ldap_base_dn",
					// 	"title" => _("Base DN"),
					// 	"default_value" => isset($authIni["ldap"]["base_dn"]) ? $authIni["ldap"]["base_dn"] : ""
					// ],
					[
						"key" => "ldap_fname_field",
						"title" => _("First Name"),
						"default_value" => isset($authIni["ldap"]["fname"]) ? $authIni["ldap"]["fname"] : ""
					],[
						"key" => "ldap_lname_field",
						"title" => _("Last Name"),
						"default_value" => isset($authIni["ldap"]["lname"]) ? $authIni["ldap"]["lname"] : ""
					]
				];
			}

			// $iniData = array();
			// $iniData["settings"] =[
			// 	"licensingModule" => $licensingModule,
			// 	"licensingDatabaseName" => $licensingDatabaseName,
			// 	"authModule" => $authModule,
			// 	"authDatabaseName" => $authDatabaseName,
			// 	"usageModule" => $usageModule,
			// 	"resourcesModule" => $resourcesModule,
			// 	"resourcesDatabaseName" => $resourcesDatabaseName,
			// 	"remoteAuthVariableName" => $remoteAuthVariableName
			// ];
			//
			// $iniData["database"] = [
			// 	"type" => "mysql",
			// 	"host" => $database_host,
			// 	"name" => $database_name,
			// 	"username" => $database_username,
			// 	"password" => $database_password
			// ];
			//
			// $iniData["ldap"] = [
			// 	"host" => $ldap_host,
			// 	"search_key" => $search_key,
			// 	"base_dn" => $base_dn,
			// 	"fname_field" => $fname_field,
			// 	"lname_field" => $lname_field
			// ];

			return $return;
		}
	]);
}
