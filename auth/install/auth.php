<?php
function register_auth_requirement()
{
	$MODULE_VARS = [
		"uid" => "auth",
		"translatable_title" => _("Auth Module"),
		"dependencies_array" => [ "have_database_access" ],
		"required" => false
	];
	return array_merge( $MODULE_VARS,[
			"getSharedInfo" => function () {
			return [
				// We will find the name in the sharedInfo variable under "$MODULE_VARS["uid"]" as "db_name"
				// We will also have a "db_feedback" variable with "created", "already_existed" (or "failed" - though that shouldn't happen)
				"database" => [
					"title" => _("Auth Database"),
					"default_value" => "coral_auth"
				],
				"config_file" => [
					"path" => "auth/admin/configuration.ini",
				]
			];
		},
		"installer" => function($shared_module_info) use ($MODULE_VARS) {
			$return = new stdClass();
			$return->yield = new stdClass();
			$return->yield->messages = [];
			$return->yield->title = _("Auth module installation");

			// TODO: This could potentially be abstracted out (cf. licensing)
			// Check that the database exists
			// We assume success - if not, it should have been handled in have_database_access
			$dbconnection = new DBService($shared_module_info[$MODULE_VARS["uid"]]["db_name"]);

			//make sure the tables don't already exist - otherwise this script will overwrite all of the data!
			if ($shared_module_info[$MODULE_VARS["uid"]]["db_feedback"] == 'already_existed')
			{
				try
				{
					$query = "SELECT count(*) count FROM information_schema.`TABLES` WHERE table_schema = `{$shared_module_info[$MODULE_VARS["uid"]]['db_name']}` AND table_name=`User` and table_rows > 0";
					$result = $dbconnection->processQuery($query);
					// TODO: offer to do this (drop tables)
					if ($result->numRows() > 0 )
					{
						$return->success = false;
						$return->yield->messages[] = _("The Authentication tables already exist.  If you intend to upgrade, please run upgrade.php instead.  If you would like to perform a fresh install you will need to manually drop all of the Authentication tables in this schema first.");
						require_once "install/templates/try_again_template.php";
						$return->yield->body = try_again_template();
						return $return;
					}
				}
				catch (Exception $e)
				{
					//TODO: This may indicate a halfway done installation at some point
					$return->success = false;
					$return->yield->messages[] = _("Please verify your database user has access to select from the information_schema MySQL metadata database.");
					require_once "install/templates/try_again_template.php";
					$return->yield->body = try_again_template();
					return $return;
				}
			}

			// Process sql files
			$sql_files_to_process = ["test_create.sql", "create_tables_data.sql"];
			$processSql = function($db, $sql_file){
				$ret = [
					"success" => true,
					"messages" => []
				];

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
				if (isset($_SESSION[$MODULE_VARS["uid"]]["sql_files"][$sql_file]) &&
					$_SESSION[$MODULE_VARS["uid"]]["sql_files"][$sql_file])
					continue;

				$result = $processSql($dbconnection, "auth/install/" . $sql_file);
				if (!$result["success"]) {
					$return->success = false;
					$return->yield->messages = array_merge($return->yield->messages, $result["messages"]);
					return $return;
				}
				else
				{
					$_SESSION[$MODULE_VARS["uid"]]["sql_files"][$sql_file] = true;
				}
			}

			$ldap_session_var_by_reference = &$_SESSION[$MODULE_VARS["uid"]]["ldap"];
			$ldap_fields = [
				[
					"key" => "ldap_host",
					"type" => "text",
					"title" => _("Host"),
					"default_value" => isset($ldap_session_var_by_reference["ldap_host"]) ? $ldap_session_var_by_reference["ldap_host"] : ""
				],[
					"key" => "ldap_port",
					"type" => "text",
					"title" => _("Port"),
					"default_value" => isset($ldap_session_var_by_reference["ldap_port"]) ? $ldap_session_var_by_reference["ldap_port"] : ""
				],[
					"key" => "ldap_search_key",
					"type" => "text",
					"title" => _("Search Key"),
					"default_value" => isset($ldap_session_var_by_reference["ldap_search_key"]) ? $ldap_session_var_by_reference["ldap_search_key"] : ""
				],[
					"key" => "ldap_base_dn",
					"type" => "text",
					"title" => _("Base DN"),
					"default_value" => isset($ldap_session_var_by_reference["ldap_base_dn"]) ? $ldap_session_var_by_reference["ldap_base_dn"] : ""
				],[
					"key" => "ldap_bind_account",
					"type" => "text",
					"title" => _("Bind Account"),
					"default_value" => isset($ldap_session_var_by_reference["ldap_bind_account"]) ? $ldap_session_var_by_reference["ldap_bind_account"] : ""
				],[
					"key" => "ldap_bind_password",
					"type" => "password",
					"title" => _("Bind Password"),
					"default_value" => isset($ldap_session_var_by_reference["ldap_bind_password"]) ?  $ldap_session_var_by_reference["ldap_bind_password"]: ""
				]
			];
			require_once "install/templates/auth_module_template.php";
			$session_timeout_default = 3600;
			$return->yield->body = auth_module_template($ldap_fields, $session_timeout_default);
			if (!isset($_POST['ldap_enabled']))
			{
				if (!isset($ldap_session_var_by_reference["ldap_enabled"]))
				{
					//We set the body just before entering the if
					$return->success = false;
					return $return;
				}
			}
			else {
				$_SESSION[$MODULE_VARS["uid"]]["session_timeout"]		= $_POST['session_timeout'];

				$ldap_session_var_by_reference["ldap_enabled"]	= $_POST['ldap_enabled'] == 1			? 'Y'							: 'N';
				$ldap_session_var_by_reference["host"]			= isset($_POST['ldap_host'])			? $_POST['ldap_host']			: null;
				$ldap_session_var_by_reference["port"]			= isset($_POST['ldap_port'])			? $_POST['ldap_port']			: null;
				$ldap_session_var_by_reference["search_key"]	= isset($_POST['ldap_search_key'])		? $_POST['ldap_search_key']		: null;
				$ldap_session_var_by_reference["base_dn"]		= isset($_POST['ldap_base_dn'])			? $_POST['ldap_base_dn']		: null;
				$ldap_session_var_by_reference["bindAccount"]	= isset($_POST['ldap_bind_account'])	? $_POST['ldap_bind_account']	: null;
				$ldap_session_var_by_reference["bindPass"]		= isset($_POST['ldap_bind_password'])	? $_POST['ldap_bind_password']	: null;
			}

			if ($ldap_session_var_by_reference["ldap_enabled"] == 'Y') {
				if (!$ldap_session_var_by_reference['host'])
					$return->yield->messages[] = _("LDAP Host is required for LDAP");
				if (!$ldap_session_var_by_reference['search_key'])
					$return->yield->messages[] = _("LDAP Search Key is required for LDAP");
				if (!$ldap_session_var_by_reference['base_dn'])
					$return->yield->messages[] = _("LDAP Base DN is required for LDAP");

				$return->success = false;
				return $return;
			}

			// This should be successful because our database check passed (it will throw an error otherwise)
			$result = $dbconnection->processQuery("SELECT loginID FROM User WHERE loginID like '%coral%';");

			// Write the config file
			$configFile = "auth/admin/configuration.ini";
			$iniData = array();
			$iniData["settings"] = [ "timeout" => $_SESSION[$MODULE_VARS["uid"]]["session_timeout"] ];
			$iniData["ldap"] = $ldap_session_var_by_reference;
			$shared_module_info["provided"]["write_config_file"]($configFile, $iniData);

			$return->yield->completionMessages[] = _("Set up your <span class='highlight'>.htaccess</span> file");
			$return->yield->completionMessages[] = _("Remove the <span class='highlight'>/auth/install/</span> directory for security purposes");
			$return->yield->completionMessages[] = _("Set up your users on the <a href='auth/admin.php'>admin screen</a>.  You may log in initially with coral/admin.");

			$return->success = true;
			return $return;
		}
	]);
}
