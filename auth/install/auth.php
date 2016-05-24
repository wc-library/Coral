<?php
function auth_register_installation_requirement()
{
	return [
		"uid" => "auth_installed",
		"translatable_title" => _("Auth Module"),
		"dependencies_array" => [ "usage", "licensing", "have_database_access" ],
		"required" => false,
		"getSharedInfo" => function () {
			return [
				// We will find the name in the sharedInfo variable under "auth_installed" as "db_name"
				// We will also have a "db_feedback" variable with "created", "already_existed" (or "failed" - though that shouldn't happen)
				"database" => [
					"title" => _("Auth Database"),
					"default_value" => "coral_auth"
				]
			];
		},
		"installer" => function($shared_module_info) {
			$return = new stdClass();
			$return->yield = new stdClass();
			$return->yield->messages = [];
			$return->yield->title = _("Auth module installation");

			// Check that the database exists
			// We assume success - if not, it should have been handled in have_database_access
			$dbconnection = new DBService($shared_module_info["auth_installed"]["db_name"]);

			//make sure the tables don't already exist - otherwise this script will overwrite all of the data!
			if ($shared_module_info["auth_installed"]["db_feedback"] == 'already_existed')
			{
				$query = "SELECT count(*) count FROM information_schema.`TABLES` WHERE table_schema = '" . $shared_module_info["auth_installed"]["db_name"] . "' AND table_name='User' and table_rows > 0";
				if (!$result = $dbconnection->processQuery($query))
				{
					$return->success = false;
					$return->yield->messages[] = _("Please verify your database user has access to select from the information_schema MySQL metadata database.");
					return $return;
				}
				else
				{
					//TODO: offer to do this (drop tables)
					if ($result->numRows() > 0 ){
						$return->success = false;
						$return->yield->messages[] = _("The Authentication tables already exist.  If you intend to upgrade, please run upgrade.php instead.  If you would like to perform a fresh install you will need to manually drop all of the Authentication tables in this schema first.");
						return $return;
					}
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
				if (isset($_SESSION["auth_installed"]["sql_files"][$sql_file]) &&
					$_SESSION["auth_installed"]["sql_files"][$sql_file])
					continue;

				$result = $processSql($dbconnection, "auth/install/" . $sql_file);
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

			$ldap_fields = [
				[
					"key" => "ldap_host",
					"type" => "text",
					"title" => _("Host"),
					"default_value" => isset($_SESSION["auth_installed"]["ldap"]["ldap_host"]) ? $_SESSION["auth_installed"]["ldap"]["ldap_host"] : ""
				],[
					"key" => "ldap_port",
					"type" => "text",
					"title" => _("Port"),
					"default_value" => isset($_SESSION["auth_installed"]["ldap"]["ldap_port"]) ? $_SESSION["auth_installed"]["ldap"]["ldap_port"] : ""
				],[
					"key" => "ldap_search_key",
					"type" => "text",
					"title" => _("Search Key"),
					"default_value" => isset($_SESSION["auth_installed"]["ldap"]["ldap_search_key"]) ? $_SESSION["auth_installed"]["ldap"]["ldap_search_key"] : ""
				],[
					"key" => "ldap_base_dn",
					"type" => "text",
					"title" => _("Base DN"),
					"default_value" => isset($_SESSION["auth_installed"]["ldap"]["ldap_base_dn"]) ? $_SESSION["auth_installed"]["ldap"]["ldap_base_dn"] : ""
				],[
					"key" => "ldap_bind_account",
					"type" => "text",
					"title" => _("Bind Account"),
					"default_value" => isset($_SESSION["auth_installed"]["ldap"]["ldap_bind_account"]) ? $_SESSION["auth_installed"]["ldap"]["ldap_bind_account"] : ""
				],[
					"key" => "ldap_bind_password",
					"type" => "password",
					"title" => _("Bind Password"),
					"default_value" => isset($_SESSION["auth_installed"]["ldap"]["ldap_bind_password"]) ?  $_SESSION["auth_installed"]["ldap"]["ldap_bind_password"]: ""
				]
			];
			require_once "install/templates/auth_module_template.php";
			$session_timeout_default = 3600;
			$return->yield->body = auth_module_template($ldap_fields, $session_timeout_default);
			if (!isset($_POST['ldap_enabled']))
			{
				if (!isset($_SESSION["auth_installed"]["ldap"]["ldap_enabled"]))
				{
					//We set the body just before entering the if
					$return->success = false;
					return $return;
				}
			}
			else {
				$_SESSION["auth_installed"]["session_timeout"]		= $_POST['session_timeout'];

				$_SESSION["auth_installed"]["ldap"]["ldap_enabled"]	= $_POST['ldap_enabled'] == 1			? 'Y'							: 'N';
				$_SESSION["auth_installed"]["ldap"]["host"]			= isset($_POST['ldap_host'])			? $_POST['ldap_host']			: null;
				$_SESSION["auth_installed"]["ldap"]["port"]			= isset($_POST['ldap_port'])			? $_POST['ldap_port']			: null;
				$_SESSION["auth_installed"]["ldap"]["search_key"]	= isset($_POST['ldap_search_key'])		? $_POST['ldap_search_key']		: null;
				$_SESSION["auth_installed"]["ldap"]["base_dn"]		= isset($_POST['ldap_base_dn'])			? $_POST['ldap_base_dn']		: null;
				$_SESSION["auth_installed"]["ldap"]["bindAccount"]	= isset($_POST['ldap_bind_account'])	? $_POST['ldap_bind_account']	: null;
				$_SESSION["auth_installed"]["ldap"]["bindPass"]		= isset($_POST['ldap_bind_password'])	? $_POST['ldap_bind_password']	: null;
			}

			$session_ldap = $_SESSION["auth_installed"]["ldap"];
			if ($session_ldap["ldap_enabled"] == 'Y') {
				if (!$session_ldap['host'])
					$return->yield->messages[] = _("LDAP Host is required for LDAP");
				if (!$session_ldap['search_key'])
					$return->yield->messages[] = _("LDAP Search Key is required for LDAP");
				if (!$session_ldap['base_dn'])
					$return->yield->messages[] = _("LDAP Base DN is required for LDAP");

				$return->success = false;
				return $return;
			}

			// This should be successful because our database check passed (it will throw an error otherwise)
			$result = $dbconnection->processQuery("SELECT loginID FROM User WHERE loginID like '%coral%';");

			// Write the config file
			$configFile = "auth/admin/configuration.ini";
			$iniData = array();
			$iniData["settings"] = [ "timeout" => $_SESSION["auth_installed"]["session_timeout"] ];
			$iniData["ldap"] = $_SESSION["auth_installed"]["ldap"];
			$shared_module_info["provided"]["write_config_file"]($configFile, $iniData);

			$return->yield->completionMessages[] = _("Set up your <span class='highlight'>.htaccess</span> file");
			$return->yield->completionMessages[] = _("Remove the <span class='highlight'>/auth/install/</span> directory for security purposes");
			$return->yield->completionMessages[] = _("Set up your users on the <a href='auth/admin.php'>admin screen</a>.  You may log in initially with coral/admin.");

			$return->success = true;
			return $return;
		}
	];
}
