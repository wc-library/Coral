<?php

class DBAccess {
	const DB_FAILED = 30001;
	const DB_ALREADY_EXISTED = 30002;
	const DB_CREATED = 30003;
}

function register_have_database_access_requirement()
{
	$MODULE_VARS = [
		"uid" => "have_database_access",
		"translatable_title" => _("Database Access"),
		"dependencies_array" => ["meets_system_requirements", "modules_to_use"],
		"required" => true
	];

	return array_merge( $MODULE_VARS, [
		"installer" => function($shared_module_info) use ($MODULE_VARS) {
			$return = new stdClass();
			$return->yield = new stdClass();
			$return->success = true;
			$return->yield->title = _("Have database access");

			if (!empty($_POST))
			{
				if (!isset($_SESSION["POSTDATA"]))
				{
					$_SESSION["POSTDATA"] = [];
				}
				// $_POST takes priority when merging arrays
				$_SESSION["POSTDATA"] = array_merge($_SESSION["POSTDATA"], $_POST);
			}

			try
			{
				Config::dbInfo("dbusername");
			}
			catch (Exception $e)
			{
				switch ($e->getCode()) {
					case Config::ERR_FILE_NOT_READABLE:
					case Config::ERR_VARIABLES_MISSING:
						// Config file not yet set up
						if (isset($_SESSION["POSTDATA"]["dbusername"]))
						{
							Config::loadTemporaryDBSettings([
								"host" => $_SESSION["POSTDATA"]["dbhost"],
								"username" => $_SESSION["POSTDATA"]["dbusername"],
								"password" => $_SESSION["POSTDATA"]["dbpassword"]
							]);
						}
						break;

					default:
						throw new LogicException("I don't know what error you managed to get so you need to debug more deeply", 1001);
						break;
				}
			}

			// Get list of chosen modules - the dependecies are handles by modules_to_use - it will force the user to choose the modules that are required.
			$modules_to_use = array_keys(array_filter($shared_module_info["modules_to_use"]["useModule"], function($item) {
				return $item;
			}));
			$modules_to_use_with_database_requirements = array_filter($shared_module_info, function($value, $key) use ($modules_to_use){
				return is_array($value) && isset($value["database"]) && in_array($key, $modules_to_use);
			}, ARRAY_FILTER_USE_BOTH);
			$shared_database_info = array_map(function($key, $item) {
				$to_return = $item["database"];
				$to_return["key"] = $key;
				return $to_return;
			}, array_keys($modules_to_use_with_database_requirements), $modules_to_use_with_database_requirements);

			require "install/templates/database_details_template.php";
			$return->yield->body = database_details_template($shared_database_info);

			// Try to connect
			try
			{
				$dbconnection = new DBService(false);
			}
			catch (Exception $e)
			{
				$return->success = false;

				switch ($e->getCode()) {
					case DBService::ERR_ACCESS_DENIED:
						$return->yield->messages[] = _("Unfortunately, although we could find the database, access was denied.");
						$return->yield->messages[] = _("Please review your settings.");
						break;

					case DBService::ERR_COULD_NOT_CONNECT:
						$return->yield->messages[] = _("Unfortunately we could not connect to the host.");
						$return->yield->messages[] = _("Please review your settings.");
						break;

					case Config::ERR_FILE_NOT_READABLE:
					case Config::ERR_VARIABLES_MISSING:
						if (!empty($_SESSION["POSTDATA"]["dbusername"]))
						{
							$return->yield->messages[] = _("Unfortunately we were not able to access the database with the details you provided.");
							$return->yield->messages[] = _("Please review your settings.");
						}
						else
						{
							$return->yield->messages[] = _("To begin with, we need a username and password to create the databases CORAL and its modules will be using.");
						}
						break;

					default:
						var_dump($shared_module_info["debug"]);
						echo "We haven't prepared for the following error (have_database_access.php #1):<br />\n<pre>";
						var_dump($e);
						echo "</pre>";
						throw $e;
						break;
				}
				return $return;
			}

			// Go through the databases and try to create them all (or see if they already exist)
			foreach ($shared_database_info as $db)
			{
				// $db["key"] is the module uid - dbtools uses this fact so if it changes dbtools will need to be fixed as well
				$dbfeedback = "db_" . $db["key"] . "_feedback";
				$dbnamestr = "db_" . $db["key"] . "_name";
				$dbname = empty($_SESSION[$dbnamestr]) ? $db["default_value"] : $_SESSION[$dbnamestr];
				$_SESSION[$dbfeedback] = !empty($_SESSION[$dbfeedback]) ? $_SESSION[$dbfeedback] : DBAccess::DB_FAILED;
				try
				{
					$dbconnection->selectDB($dbname);
					$result = $dbconnection->processQuery("SELECT * FROM `information_schema`.`tables` WHERE `table_schema`='$dbname';");
					// If DB is empty, pretend we created it
					if ($result && $result->numRows() == 0)
					{
						$_SESSION[$dbfeedback] = DBAccess::DB_CREATED;
					}
					else
					{
						if ($_SESSION[$dbfeedback] == DBAccess::DB_CREATED)
						{
							$_SESSION["db_tools"]["use_tables"] = isset($_SESSION["db_tools"]["use_tables"]) ? $_SESSION["db_tools"]["use_tables"] : [];
							$_SESSION["db_tools"]["use_tables"][] = $db["key"];
						}
						$_SESSION[$dbfeedback] = DBAccess::DB_ALREADY_EXISTED;
					}
				}
				catch (Exception $e)
				{
					$_SESSION[$dbfeedback] == DBAccess::DB_FAILED;
					switch ($e->getCode())
					{
						case DBService::ERR_COULD_NOT_SELECT_DATABASE:
							try {
								// The commented line is preferable (see http://stackoverflow.com/a/766996/123415) but we need to be backwards compatible to mysql 5.5
								// $result = $dbconnection->processQuery("CREATE DATABASE `$dbname` DEFAULT CHARACTER SET utf8mb4 DEFAULT COLLATE utf8mb4_unicode_ci;");
								$result = $dbconnection->processQuery("CREATE DATABASE `$dbname` DEFAULT CHARACTER SET utf8 DEFAULT COLLATE utf8_unicode_ci;");
								$_SESSION[$dbfeedback] = DBAccess::DB_CREATED;

								// If we have actually just created it, make sure that use_tables is not set because process Sql needs to happen!
								if (isset($_SESSION["db_tools"]["use_tables"]) && in_array($db["key"], $_SESSION["db_tools"]["use_tables"]))
									unset($_SESSION["db_tools"]["use_tables"][$db["key"]]);
							} catch (Exception $e) {
								$return->yield->messages[] = _("We tried to select a database with the name $dbname but failed. We also could not create it.");
								$return->yield->messages[] = _("In order to proceed, we need access rights to create databases or you need to manually create the databases and provide their names and the credentials for a user with access rights to them.");
								$return->success = false;
								return $return;
							}
							// THIS SHOULDN'T FAIL BECAUSE WE'VE JUST CREATED THE DB SUCCESSFULLY.
							$result = $dbconnection->selectDB( $dbname );
							break;

						default:
							echo "We haven't prepared for the following error (have_database_access.php #2):<br />\n";
							var_dump($e);
							break;
					}
				}
				$shared_module_info["setSharedModuleInfo"]($db["key"], "db_name", $dbname);
				$shared_module_info["setSharedModuleInfo"]($db["key"], "db_feedback", $_SESSION[$dbfeedback]);
			}

			try
			{
				$temporary_test_table_name = "temp_test";
				$result = $dbconnection->processQuery("DROP TABLE IF EXISTS `$temporary_test_table_name`;");
				$result = $dbconnection->processQuery("CREATE TABLE `$temporary_test_table_name` (id int);");
				$result = $dbconnection->processQuery("INSERT INTO `$temporary_test_table_name` VALUES (0);");
				$result = $dbconnection->processQuery("DROP TABLE IF EXISTS `$temporary_test_table_name`;");
			}
			catch (Exception $e)
			{
				$return->yield->messages[] = _("We were unable to create/delete a table. Please check your user rights. ({$e->getCode()})");
				$return->success = false;
				return $return;
			}
			$shared_module_info["setSharedModuleInfo"](
				"provided",
				"get_db_connection",
				function($db_name) use ($dbconnection) {
					$dbconnection->selectDB($db_name);
					return $dbconnection;
				}
			);
			return $return;
		}
	]);
}
