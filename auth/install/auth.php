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



			//We need a session timeout variable - are we just going to assume it? 3600
			// $session_timeout = (isset($_POST['session_timeout']) ? trim($_POST['session_timeout']) : null);
			if (!isset($_SESSION["auth_installed"]["ldap"]["ldap_enabled"]))
			{
				if (!isset($_POST['ldap_enabled']))
				{
					$ldap_fields = [
						[ "key" => "ldap_host"			, "type" => "text",		"title" => _("Host") ],
						[ "key" => "ldap_port"			, "type" => "text",		"title" => _("Port") ],
						[ "key" => "ldap_search_key"	, "type" => "text",		"title" => _("Search Key") ],
						[ "key" => "ldap_base_dn"		, "type" => "text",		"title" => _("Base DN") ],
						[ "key" => "ldap_bind_account"	, "type" => "text",		"title" => _("Bind Account") ],
						[ "key" => "ldap_bind_password"	, "type" => "password",	"title" => _("Bind Password") ]
					];
					require_once "install/templates/auth_module_template.php";
					$session_timeout_default = 3600;
					$return->yield->body = auth_module_template($ldap_fields, $session_timeout_default);
				}
			}
			else {
				$_SESSION["auth_installed"]["ldap"]["ldap_enabled"]	= isset($_POST['ldap_enabled'])			? 'Y'							: 'N';
				$_SESSION["auth_installed"]["ldap"]["host"]			= isset($_POST['ldap_host'])			? $_POST['ldap_host']			: null;
				$_SESSION["auth_installed"]["ldap"]["port"]			= isset($_POST['ldap_port'])			? $_POST['ldap_port']			: null;
				$_SESSION["auth_installed"]["ldap"]["search_key"]	= isset($_POST['ldap_search_key'])		? $_POST['ldap_search_key']		: null;
				$_SESSION["auth_installed"]["ldap"]["base_dn"]		= isset($_POST['ldap_base_dn'])			? $_POST['ldap_base_dn']		: null;
				$_SESSION["auth_installed"]["ldap"]["bindAccount"]	= isset($_POST['ldap_bind_account'])	? $_POST['ldap_bind_account']	: null;
				$_SESSION["auth_installed"]["ldap"]["bindPass"]		= isset($_POST['ldap_bind_password'])	? $_POST['ldap_bind_password']	: null;
			}

			$return->yield->messages[] = "we're still busy writing the installer okay";
			$return->success = false;
			return $return;

			if ($ldap['ldap_enabled']=='Y') {
				if (!$ldap['host']) $errorMessage[] = "LDAP Host is required for LDAP";
				if (!$ldap['search_key']) $errorMessage[] = "LDAP Search Key is required for LDAP";
				if (!$ldap['base_dn']) $errorMessage[] = "LDAP Base DN is required for LDAP";
			}

			$dbcheck = mysqli_select_db($link, "$database_name");
			if (!$dbcheck) {
				$errorMessage[] = "Unable to access the database '" . $database_name . "'.  Please verify it has been created.<br />MySQL Error: " . mysqli_error($link);
			}else{
				//passed db host, name check, test that user can select from Auth database
				$result = mysqli_query($link, "SELECT loginID FROM " . $database_name . ".User WHERE loginID like '%coral%';");
				if (!$result){
					$errorMessage[] = "Unable to select from the User table in database '" . $database_name . "' with user '" . $database_username . "'.  Error: " . mysqli_error($link);
				}

			}

			/**
			 * TODO: [unified_installer] abstract writing config file out
			 */
				//write the config file
			$configFile = "../admin/configuration.ini";
			$fh = fopen($configFile, 'w');

			if (!$fh){
				$errorMessage[] = "Could not open file " . $configFile . ".  Please verify you can write to the /admin/ directory.";
			}else{

				$iniData = array();
				$iniData[] = "[settings]";
				$iniData[] = "timeout=" . $session_timeout;

				$iniData[] = "\n[database]";
				$iniData[] = "type = \"mysql\"";
				$iniData[] = "host = \"" . $database_host . "\"";
				$iniData[] = "name = \"" . $database_name . "\"";
				$iniData[] = "username = \"" . $database_username . "\"";
				$iniData[] = "password = \"" . $database_password . "\"";

				$iniData[] = "\n[ldap]";
				foreach ($ldap as $fname => $fvalue) {
					$iniData[] = "$fname = \"$fvalue\"";
				}
				fwrite($fh, implode("\n",$iniData));
				fclose($fh);
			}

			/**
			 * NOTE: [unified_installer] These details might be handy:
			 */
			// 	This installation will:
			// 	<ul>
			// 		<li>Check that you are running PHP 5</li>
			// 		<li>Connect to MySQL and create the CORAL Auth tables</li>
			// 		<li>Test the database connection the CORAL Auth application will use </li>
			// 		<li>Set up the config file with settings you choose</li>
			// 	</ul>
			//
			// 	<br />
			// 	To get started you should:
			// 	<ul>
			// 		<li>Create a MySQL Schema created for CORAL Auth Module - recommended name is coral_auth_prod.  Each CORAL module has separate user permissions and requires a separate schema.</li>
			// 		<li>Know your host, username and password for MySQL with permissions to create tables</li>
			// 		<li>It is recommended for security to have a different username and password for CORAL with only select, insert, update and delete privileges to CORAL schemas</li>
			// 		<li>Verify that your /admin/ directory is writable by server during the installation process (chmod 777).  After installation you should chmod it back.</li>
			// 	</ul>
			//
			//


				// session timeout is the cookie expiration timeout for logged in users

			if (!isset($session_timeout))
			{
				$session_timeout='3600';
			}

			$ldap = array('host'=>'', 'port'=>'', 'search_key'=>'', 'base_dn'=>'', 'bindAccount'=>'','bindPass'=>'');
			if (isset($_POST['ldap_enabled'])) {
				$ldap['ldap_enabled'] = 'Y';
				if (isset($_POST['ldap_host']))
					$ldap['host'] = $_POST['ldap_host'];
				if (isset($_POST['ldap_port']))
					$ldap['port'] = $_POST['ldap_port'];
				if (isset($_POST['ldap_search_key']))
					$ldap['search_key'] = $_POST['ldap_search_key'];
				if (isset($_POST['ldap_base_dn']))
					$ldap['base_dn'] = $_POST['ldap_base_dn'];
				if (isset($_POST['ldap_bind_account']))
					$ldap['bindAccount'] = $_POST['ldap_bind_account'];
				if (isset($_POST['ldap_bind_password']))
					$ldap['bindPass'] = $_POST['ldap_bind_password'];
			} else {
				$ldap['ldap_enabled'] = 'N';
			}

			/**
			 * TODO: [unified_installer] we may need to do some of this kind of cleanup stuff
			 */
				// <h3>CORAL Authentication installation is now complete!</h3>
				// It is recommended you now:
				// <ul>
				// 	<li>Set up your .htaccess file</li>
				// 	<li>Remove the /install/ directory for security purposes</li>
				// 	<li>Set up your users on the <a href='../admin.php'>admin screen</a>.  You may log in initially with coral/admin.</li>
				// </ul>
		}
	];
}
