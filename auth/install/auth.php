<?php
function register_auth_requirement()
{
	$MODULE_VARS = [
		"uid" => "auth",
		"translatable_title" => _("Authentication Module"),
		"dependencies_array" => [ "db_tools", "have_read_write_access_to_config" ],
		"required" => true,
		"alternative" => ["remote_auth_variable_name" => _("Remote Auth Variable Name")],
		"getSharedInfo" => function () {
			return [
				"database" => [
					"title" => _("Auth Database"),
					"default_value" => "coral_auth"
				],
				"config_file" => [
					"path" => "auth/admin/configuration.ini",
				]
			];
		}
	];
	return array_merge( $MODULE_VARS,[
		"installer" => function($shared_module_info) use ($MODULE_VARS) {
			$return = new stdClass();
			$return->yield = new stdClass();
			$return->yield->messages = [];
			$return->yield->title = _("Auth module installation");

			// TODO: This could potentially be abstracted out (cf. licensing)
			// Check that the database exists
			// We assume success - if not, it should have been handled in have_database_access
			$this_db_name = $shared_module_info[ $MODULE_VARS["uid"] ]["db_name"];
			$dbconnection = $shared_module_info["provided"]["get_db_connection"]( $this_db_name );

			// TODO: abstract this code (cf. licensing, management)
			//make sure the tables don't already exist - otherwise this script will overwrite all of the data!
			$table_to_check = "User";
			$module_name = "Authentication"; // %s ...
			$non_empty_fail_message = _("The tables for %s already exist. If you intend to upgrade, please run upgrade.php instead. If you would like to perform a fresh install you will need to manually drop all of the tables in this schema first.");
			$dbconn;
			$dbname = $shared_module_info[$MODULE_VARS["uid"]]['db_name'];


			if ($shared_module_info[$MODULE_VARS["uid"]]["db_feedback"] == 'already_existed')
			{
				try
				{
					$query = "SELECT count(*) count FROM information_schema.`TABLES` WHERE table_schema = `{$shared_module_info[$MODULE_VARS["uid"]]['db_name']}` AND table_name=`User` AND table_rows > 0";
					$result = $dbconnection->processQuery($query);
					// TODO: offer to do this (drop tables)
					if ($result->numRows() > 0 )
					{
						$return->success = false;
						$return->yield->messages[] = _("The tables for Authentication already exist. If you intend to upgrade, please run upgrade.php instead.  If you would like to perform a fresh install you will need to manually drop all of the Authentication tables in this schema first.");
						require_once "install/templates/try_again_template.php";
						$return->yield->body = try_again_template();
						return $return;
					}
				}
				catch (Exception $e)
				{
					//TODO: this should be handled much better! if the table already existed we need to figure out more about it...
					// SOLUTION: we're going to ask if the user meant to do an update and then redirect or just use the existing db.
					// "wow, hang on - you already have tables in a database for %s"
					$return->yield->messages[] = var_export($e, 1);
					//TODO: This may indicate a halfway done installation at some point

					$return->success = false;
					$return->yield->messages[] = _("Please verify your database user has access to select from the information_schema MySQL metadata database.");
					require_once "install/templates/try_again_template.php";
					$return->yield->body = try_again_template();
					return $return;
				}
			}

			// Process sql files
			$sql_files_to_process = ["auth/install/test_create.sql", "auth/install/create_tables_data.sql"];
			$ret = $shared_module_info["provided"]["process_sql_files"]( $dbconnection, $sql_files_to_process, $MODULE_VARS["uid"] );
			if (!$ret["success"])
			{
				$return->success = false;
				$return->yield->messages = array_merge($return->yield->messages, $ret["messages"]);
				return $return;
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
				],[
					"key" => "ldap_confirm_bind_password",
					"type" => "password",
					"title" => _("Confirm Bind Password"),
					"default_value" => isset($ldap_session_var_by_reference["ldap_confirm_bind_password"]) ?  $ldap_session_var_by_reference["ldap_confirm_bind_password"]: ""
				]
				//TODO: Confirm password?
			];
			require_once "install/templates/auth_module_template.php";
			$session_timeout_default = 3600;
			$return->yield->body = auth_module_template($ldap_fields, $session_timeout_default);
			if (!isset($_POST['ldap_enabled']))
			{
				if (!isset($ldap_session_var_by_reference["ldap_enabled"]))
				{
					//We set the return body just before entering the if so we can return now
					$return->success = false;
					return $return;
				}
			}
			else {
				$_SESSION[$MODULE_VARS["uid"]]["session_timeout"]	= $_POST['session_timeout'];

				$ldap_session_var_by_reference["ldap_enabled"]		= $_POST['ldap_enabled'] == 1					? 'Y'									: 'N';
				$ldap_session_var_by_reference["host"]				= isset($_POST['ldap_host'])					? $_POST['ldap_host']					: null;
				$ldap_session_var_by_reference["port"]				= isset($_POST['ldap_port'])					? $_POST['ldap_port']					: null;
				$ldap_session_var_by_reference["search_key"]		= isset($_POST['ldap_search_key'])				? $_POST['ldap_search_key']				: null;
				$ldap_session_var_by_reference["base_dn"]			= isset($_POST['ldap_base_dn'])					? $_POST['ldap_base_dn']				: null;
				$ldap_session_var_by_reference["bindAccount"]		= isset($_POST['ldap_bind_account'])			? $_POST['ldap_bind_account']			: null;
				$ldap_session_var_by_reference["bindPass"]			= isset($_POST['ldap_bind_password'])			? $_POST['ldap_bind_password']			: null;
				$ldap_session_var_by_reference["bindPassConfirm"]	= isset($_POST['ldap_confirm_bind_password'])	? $_POST['ldap_confirm_bind_password']	: null;
				if ($ldap_session_var_by_reference["bindPass"] != $ldap_session_var_by_reference["bindPassConfirm"])
				{
					$return->success = false;
					$return->yield->messages[] = _("Your Bind Passwords do not match.");
				}
			}


			if ($ldap_session_var_by_reference["ldap_enabled"] == 'Y') {
				if (!$ldap_session_var_by_reference['host'])
					$return->yield->messages[] = _("LDAP Host is required for LDAP");
				if (!$ldap_session_var_by_reference['search_key'])
					$return->yield->messages[] = _("LDAP Search Key is required for LDAP");
				if (!$ldap_session_var_by_reference['base_dn'])
					$return->yield->messages[] = _("LDAP Base DN is required for LDAP");

				$return->success = false;
			}

			if (!$return->success)
				return $return;

			// This should be successful because our database check passed (it will throw an error otherwise)
			$result = $dbconnection->processQuery("SELECT loginID FROM User WHERE loginID like '%coral%';");

			// Write the config file
			$configFile = $MODULE_VARS["getSharedInfo"]()["config_file"]["path"];
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
