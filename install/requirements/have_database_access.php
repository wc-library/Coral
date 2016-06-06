<?php

class DBAccess {
	const DB_FAILED = 30001;
	const DB_ALREADY_EXISTED = 30002;
	const DB_CREATED = 30003;
}

function register_have_database_access_requirement()
{
	return [
		"uid" => "have_database_access",
		"translatable_title" => _("Database Access"),
		"dependencies_array" => ["have_read_write_access_to_config", "modules_to_use"],
		"required" => true,
		"installer" => function($shared_module_info) {
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
					case Config::ERR_VARIABLES_MISSING:
						// Config file not yet set up
						if (isset($_SESSION["POSTDATA"]["dbusername"]))
						{
							Config::loadTemporaryDBSettings((object) [
								"host" => $_SESSION["POSTDATA"]["dbhost"],
								"username" => $_SESSION["POSTDATA"]["dbusername"],
								"password" => $_SESSION["POSTDATA"]["dbpassword"]
							]);
						}
						break;
					case Config::ERR_FILE_NOT_READABLE:
						# code...
						break;

					default:
						throw new LogicException("I don't know what error you managed to get so you need to debug more deeply", 1001);
						break;
				}
			}

			$modules_with_database_requirements = array_filter($shared_module_info, function($item){
				return is_array($item) && isset($item["database"]);
			});
			$shared_database_info = array_map(function($key, $item) {
				$to_return = $item["database"];
				$to_return["key"] = $key;
				return $to_return;
			}, array_keys($modules_with_database_requirements), $modules_with_database_requirements);

			require "install/templates/database_details_template.php";
			$return->yield->body = database_details_template($shared_database_info);

			// Try to connect
			try {
				$dbconnection = new DBService(false);
			} catch (Exception $e) {
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
						echo "We haven't prepared for the following error (installer.php):<br />\n<pre>";
						var_dump($e);
						echo "</pre>";
						throw $e;
						break;
				}
				return $return;
			}

			//TODO: write settings to config

			// Go through the databases and try to create them all (or see if they already exist)
			foreach ($shared_database_info as $db)
			{
				$dbfeedback = "db_" . $db["key"] . "_feedback";
				$dbnamestr = "db_" . $db["key"] . "_name";
				$dbname = empty($_SESSION[$dbnamestr]) ? $db["default_value"] : $_SESSION[$dbnamestr];
				if (empty($_SESSION[$dbfeedback]))
					$_SESSION[$dbfeedback] = DBAccess::DB_FAILED;
				try {
					$dbconnection->selectDB( $dbname );
					if ($_SESSION[$dbfeedback] == DBAccess::DB_FAILED)
						$_SESSION[$dbfeedback] = DBAccess::DB_ALREADY_EXISTED;
				}
				catch (Exception $e) {
					switch ($e->getCode()) {
						case DBService::ERR_COULD_NOT_SELECT_DATABASE:
							try {
								$result = $dbconnection->processQuery("CREATE DATABASE `$dbname`;");
								$_SESSION[$dbfeedback] = DBAccess::DB_CREATED;
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
							echo "We haven't prepared for the following error (installer.php):<br />\n";
							var_dump($e);
							break;
					}
				}
				$shared_module_info["setSharedModuleInfo"]($db["key"], "db_name", $dbname);
				$shared_module_info["setSharedModuleInfo"]($db["key"], "db_feedback", $_SESSION[$dbfeedback]);
			}

			try {
				$temporary_test_table_name = "temp_test";
				$result = $dbconnection->processQuery("DROP TABLE IF EXISTS `$temporary_test_table_name`;");
				$result = $dbconnection->processQuery("CREATE TABLE `$temporary_test_table_name` (id int);");
				$result = $dbconnection->processQuery("INSERT INTO `$temporary_test_table_name` VALUES (0);");
				$result = $dbconnection->processQuery("DROP TABLE IF EXISTS `$temporary_test_table_name`;");
			} catch (Exception $e) {
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
	];
}
