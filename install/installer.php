<?php
session_start();
class Installer {

	protected $checklist = [];
	protected $messages = [];

	function __construct() {

		$this->checklist = [
			[
				"uid" => "meets_system_requirements",
				"translatable_title" => _("Meets system requirements"),
				"dependencies_array" => [],
				"required" => true,
				"installer" => function() {
					$return = new stdClass();
					$return->yield = new stdClass();

					$return->success = true;
					$return->yield->title = _("Meets system requirements");

					/**
					 *  PHP_MAJOR_VERSION is only defined from 5.2.7 onwards but
					 *  we are past 5.2.7's end of life (so if this test fails,
					 *  that's okay because PHP needs to be updated anyway).
					 */
					if (defined('PHP_MAJOR_VERSION') && PHP_MAJOR_VERSION >= 5 && PHP_MINOR_VERSION >= 4)
					{
						if (PHP_MAJOR_VERSION > 5)
						{
							$return->yield->messages[] = sprintf( _("PHP is required for CORAL but you have version %s. CORAL will install anyway but may not function correctly."), PHP_MAJOR_VERSION );
						}
					}
					else
					{
						$return->yield->messages[] = _("PHP 5.4 or greater is required for CORAL");
						$return->success = false;
					}
					return $return;
				}
			],[
				"uid" => "have_read_write_access_to_config",
				"translatable_title" => _("Config file writable or set up"),
				"dependencies_array" => [""],
				"required" => true,
				"installer" => function() {
					$return = new stdClass();
					$return->yield = new stdClass();
					$return->success = true;

					$return->yield->title = _("Can read config file");
					require_once("common/Config.php");
					if (is_readable(Config::CONFIG_FILE_PATH))
					{
						try {
							// Force Config to init
							Config::dbInfo("");
							return $return;
						}
						catch (Exception $e) {
							// $e->getCode() == Config::CONFIG_FILE_PATH)
							// We'll have to create it
						}
					}

					$return->yield->title = _("Can create config file");
					if ($handle = fopen(Config::CONFIG_FILE_PATH, 'w')) {
						fclose($handle);
						return $return;
					}
					$return->yield->messages[] = sprintf( _("In order to proceed with the installation, we must be able to write to '%s'."), Config::CONFIG_FILE_PATH );
					$return->success = false;
					return $return;
				}
			],[
				"uid" => "have_database_access",
				"translatable_title" => _("Have database access"),
				"dependencies_array" => ["have_read_write_access_to_config"],
				"required" => true,
				"installer" => function() {
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

					try {
						Config::dbInfo("dbusername");
					} catch (Exception $e) {
						if ($e->getCode() == Config::ERR_VARIABLES_MISSING)
						{
							// Config file not yet set up
							if (isset($_SESSION["POSTDATA"]["dbusername"]))
							{
								Config::loadTemporaryDBSettings((object) [
									"host" => $_SESSION["POSTDATA"]["dbhost"],
									"username" => $_SESSION["POSTDATA"]["dbusername"],
									"password" => $_SESSION["POSTDATA"]["dbpassword"]
								]);
							}
						}
						else {
							throw new LogicException("I don't know what error you managed to get so you need to debug more deeply", 1001);
						}
					}

					require "install/templates/database_details.php";
					$return->yield->body = database_details();

					// Try to connect
					require_once("common/DBService.php");
					try {
						$dbconnection = new DBService(false);
					} catch (Exception $e) {
						$return->success = false;

						switch ($e->getCode()) {
							case DBService::ERR_ACCESS_DENIED:
								# code...
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
									$return->yield->messages[] = _("To begin with, we need a username and password create the databases CORAL and its modules will be using.");
								}
								break;
							default:
								echo "We haven't prepared for the following error (installer.php):<br />\n";
								var_dump($e);
								break;
						}
						return $return;
					}

					// TODO: check if there are any filled in db names to use here:
					// Find the name of any database we need to connect to
					// -> currently just grabs the first one that is set
					$db_to_select = "coral_organizations";
					$names = [ "dbauth", "dborganizations", "dbmanagement", "dblicensing", "dbreports", "dbresources", "dbusage" ];
					foreach ($names as $name)
					{
						if (!empty($_SESSION["POSTDATA"][$name]))
						{
							$db_to_select = DBService::escapeString($_SESSION["POSTDATA"][$name]);
							break;
						}
					}

					try {
						$dbconnection->selectDB( $db_to_select );
					}
					catch (Exception $e) {
						switch ($e->getCode()) {
							case DBService::ERR_COULD_NOT_SELECT_DATABASE:
								# code...
								try {
									$result = $dbconnection->processQuery("CREATE DATABASE `$db_to_select`;");
								} catch (Exception $e) {
									$return->yield->body = database_details();
									if ($db_to_select !== "coral_organizations")
									{
										$return->yield->messages[] = _("We tried to select a database with the name $db_to_select but failed. We also could not create it.");
									}
									$return->yield->messages[] = _("In order to proceed, we need access rights to create databases or you need to manually create the databases and provide their names and the credentials for a user with access rights to them.");
									$return->success = false;
									return $return;
								}
								// THIS SHOULDN'T FAIL BECAUSE WE'VE JUST CREATED THE DB SUCCESSFULLY.
								$result = $dbconnection->selectDB( $db_to_select );
								break;

							default:
								# code...
								echo "We haven't prepared for the following error (installer.php):<br />\n";
								var_dump($e);
								break;
						}
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
					return $return;
				}
			],[
				"uid" => "have_default_user",
				"translatable_title" => _("Have default user"),
				"dependencies_array" => ["have_database_access"],
				"required" => true,
				"installer" => function() {
					$return = new stdClass();
					$return->yield = new stdClass();

					$return->success = true;
					$return->yield->title = _("Have default user");
					return $return;
				}
			]
		];

		$this->scanForModuleInstallers();
	}
	private function getKeyFromUid($test_uid)
	{
		require_once("common/array_column.php");
		$key = array_search($test_uid, array_column($this->checklist, 'uid'));
		if ($key === false)
			throw new OutOfBoundsException("Test '$test' not found in checklist.", 100);

		return $key;
	}
	public function getCheckListUids()
	{
		require_once("common/array_column.php");
		return array_column($this->checklist, "uid");
	}
	public function getTitleFromUid($test_uid)
	{
		return $this->checklist[ $this->getKeyFromUid($test_uid) ]["translatable_title"];
	}

	/**
	 *
	 * @param  [type] $installer_array
	 *                    $translatable_title
	 *                    $dependencies_array
	 *                    $required
	 *                    $installation_callback
	 * @return [type]
	 */
	public function register_installation_requirement($installer_object)
	{
		$this->checklist[] = $installer_object;
		//sort according to dependencies_array
		//
	}

	private function scanForModuleInstallers()
	{
		$MODULE_ROOT = ".";

		$module_directories = scandir($MODULE_ROOT);
		foreach ($module_directories as $dir)
		{
			if (is_dir("$MODULE_ROOT/$dir"))
			{
				$installation_root_file = "$MODULE_ROOT/$dir/install/$dir.php";
				if (file_exists($installation_root_file))
				{
					$function_name = "${dir}_register_installation_requirement";
					require $installation_root_file;
					if (is_callable($function_name))
					{
						$installer_object = call_user_func($function_name);
						var_dump($installer_object);
						$this->register_installation_requirement($installer_object);
					}
					else
					{
						$this->messages[] = "<b>Warning:</b> There is a problem with the installer for $dir";
					}
				}
			}
		}
	}

	public function runTestForResult($test_uid)
	{
		$key = $this->getKeyFromUid($test_uid);
		if ($key === false)
			throw new OutOfBoundsException("Test '{$this->getTitleFromUid($test_uid)}' not found in checklist.", 100);

		$result = call_user_func( $this->checklist[$key]["installer"] );
		if ($result === null)
			throw new UnexpectedValueException("The install script for '{$this->getTitleFromUid($test_uid)}' has returned a null result (which is not allowed).", 101);

		return $result;
	}
	public function getMessages()
	{
		return $this->messages;
	}

	public function successful_install()
	{
		echo "woot";
	}
}
