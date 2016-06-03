<?php
function register_management_requirement()
{
	$MODULE_VARS = [
		"uid" => "management",
		"translatable_title" => _("Management Module"),
		"dependencies_array" => [ "db_tools", "have_read_write_access_to_config", "modules_to_use" ],
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
			$return->success = true;
			$return->yield->title = _("Management Module");

			$this_db_name = $shared_module_info[ $MODULE_VARS["uid"] ]["db_name"];
			$dbconnection = $shared_module_info["provided"]["get_db_connection"]( $this_db_name );

			// TODO: abstract this code (cf. auth)
			//make sure the tables don't already exist - otherwise this script will overwrite all of the data!
			if ($shared_module_info[$MODULE_VARS["uid"]]["db_feedback"] == 'already_existed')
			{
				try
				{
					$query = "SELECT count(*) count FROM information_schema.`COLUMNS` WHERE table_schema = `{$shared_module_info[$MODULE_VARS['uid']]['db_name']}` AND table_name=`Management`";
					$result = $dbconnection->processQuery($query);
					// TODO: offer to do this (drop tables)
					if ($result->numRows() > 0 )
					{
						$return->success = false;
						$return->yield->messages[] = _("The Management tables already exist. If you intend to upgrade, please run upgrade.php instead.  If you would like to perform a fresh install you will need to manually drop all of the Management tables in this schema first.");
						require_once "install/templates/try_again_template.php";
						$return->yield->body = try_again_template();
						return $return;
					}
				}
				catch (Exception $e)
				{
					$return->success = false;
					$return->yield->messages[] = _("Please verify your database user has access to select from the information_schema MySQL metadata database.");
					require_once "install/templates/try_again_template.php";
					$return->yield->body = try_again_template();
					return $return;
				}
				$query = "SELECT count(*) count FROM information_schema.`TABLES` WHERE table_schema = '{$shared_module_info[$MODULE_VARS['uid']]['db_name']}' AND table_name='User' and table_rows > 0";
			}

			// Process sql files
			$sql_files_to_process = ["management/install/protected/test_create.sql", "management/install/protected/install.sql"];
			$ret = $shared_module_info["provided"]["process_sql_files"]( $dbconnection, $sql_files_to_process, $MODULE_VARS["uid"] );
			if (!$ret["success"])
			{
				$return->success = false;
				$return->yield->messages = array_merge($return->yield->messages, $ret["messages"]);
				return $return;
			}


			// TODO: this can possibly be abstracted - cf. licensing
			$admin_login = $shared_module_info["common"]["default_user"]["username"];
			//delete admin user if they exist, then set them back up with correct username
			$query = "SELECT privilegeID FROM Privilege WHERE shortName like '%admin%';";
			//we've just inserted this and there was no error - we assume selection will succeed.
			$result = $dbconnection->processQuery($query);
			$privilegeID = $result->fetchRow()[0];
			$query = "DELETE FROM User WHERE loginID = '$admin_login';";
			$dbconnection->processQuery($query);
			$query = "INSERT INTO User (loginID, privilegeID) values ('$admin_login', $privilegeID);";
			$dbconnection->processQuery($query);


			$configFile = $MODULE_VARS["getSharedInfo"]()["config_file"]["path"];

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
