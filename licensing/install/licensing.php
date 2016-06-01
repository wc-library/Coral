<?php
function register_licensing_requirement()
{
	$MODULE_VARS = [
		"uid" => "licensing",
		"translatable_title" => _("Licensing Module"),
		"dependencies_array" => [ "have_database_access", "have_read_write_access_to_config", "auth", "modules_to_use" ],
		"required" => true,
	];
	return array_merge( $MODULE_VARS, [
		"getSharedInfo" => function () {
			return [
				"database" => [
					"title" => _("Licensing Database"),
					"default_value" => "coral_licensing"
				],
				"config_file" => [
					"path" => "auth/admin/configuration.ini",
				]
			];
		},
		"installer" => function($shared_module_info) use ($MODULE_VARS) {
			$return = new stdClass();
			$return->yield = new stdClass();
			$return->success = false;
			$return->yield->title = _("Licensing Module");
			$return->yield->messages[] = "<b>Installer Incomplete</b>";

			$this_db_name = $shared_module_info[ $MODULE_VARS["uid"] ]["db_name"];
			$dbconnection = $shared_module_info["provided"]["get_db_connection"]( $this_db_name );

			//make sure the tables don't already exist - otherwise this script will overwrite all of the data!
			if ($shared_module_info[$MODULE_VARS["uid"]]["db_feedback"] == 'already_existed')
			{
				try
				{
					$query = "SELECT count(*) count FROM information_schema.`COLUMNS` WHERE table_schema = `{$shared_module_info[$MODULE_VARS['uid']]['db_name']}` AND table_name=`License`";
					$result = $dbconnection->processQuery($query);
					// TODO: offer to do this (drop tables)
					if ($result->numRows() > 0 )
					{
						$return->success = false;
						$return->yield->messages[] = _("The Licensing tables already exist.  If you intend to upgrade, please run upgrade.php instead.  If you would like to perform a fresh install you will need to manually drop all of the Licensing tables in this schema first.");
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
			$sql_files_to_process = ["protected/test_create.sql", "protected/install.sql"];
			$processSql = function($db, $sql_file){
				$ret = [ "success" => true, "messages" => [] ];

				if (!file_exists($sql_file))
				{
					$ret["messages"][] = "Could not open sql file: " . $sql_file . ".<br />If this file does not exist you must download new install files.";
					$ret["success"] = false;
				}
				else
				{
					// Run the file - checking for errors at each SQL execution
					$f = fopen($sql_file,"r");
					$sqlFile = fread($f,filesize($sql_file));
					$sqlArray = explode(";",$sqlFile);
					// Process the sql file by statements
					foreach ($sqlArray as $stmt)
					{
						if (strlen(trim($stmt))>3)
						{
							try
							{
								$db->processQuery($stmt);
							}
							catch (Exception $e)
							{
								$ret["messages"][] = $db->getError() . "<br />For statement: " . $stmt;
								$ret["success"] = false;
							}
						}
					}
				}
				return $ret;
			};

			foreach ($sql_files_to_process as $sql_file)
			{
				if (isset($_SESSION["auth_installed"]["sql_files"][$sql_file]) &&
					$_SESSION["auth_installed"]["sql_files"][$sql_file])
					continue;

				$result = $processSql($dbconnection, "licensing/install/" . $sql_file);
				if (!$result["success"]) {
					$return->success = false;
					$return->yield->messages = array_merge($return->yield->messages, $result["messages"]);
					return $return;
				}
				else
				{
					$_SESSION["auth_installed"]["sql_files"][$sql_file] = true;
				}
			}

			$admin_login = $shared_module_info["common"]["default_user"]["username"];
			//delete admin user if they exist, then set them back up
			$query = "SELECT privilegeID FROM Privilege WHERE shortName like '%admin%';";
			//we've just inserted this and there was no error - we assume selection will succeed.
			$result = $dbconnection->processQuery($query);
			$privilegeID = $result->fetchRow()[0];
			$query = "DELETE FROM User WHERE loginID = '$admin_login';";
			$dbconnection->processQuery($query);
			$query = "INSERT INTO User (loginID, privilegeID) values ('$admin_login', $privilegeID);";
			$dbconnection->processQuery($query);

			// TODO: configure these locations better? Although may be wasted effort if a unified common is achieved
			$configFile = "licensing/admin/configuration.ini";

			$iniData = array();
			$iniData["settings"] = [];

			$cooperating_modules = [
				"auth" => "needs_db",
				"organizations" => "needs_db",
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

			// 	"useTermsToolFunctionality" => $useTermsToolFunctionality,

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
