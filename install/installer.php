<?php
class Installer {

	protected $checklist;

	function __construct() {
		session_start();
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

					if (isset($_POST["dbusername"]))
					{
						// Form has just been submitted
						$_SESSION["dbusername"] = empty($_POST["dbusername"]) ? $_SESSION["dbusername"] : $_POST["dbusername"];
						$_SESSION["dbpassword"] = empty($_POST["dbpassword"]) ? $_SESSION["dbpassword"] : $_POST["dbpassword"];
						$_SESSION["dbhost"] = empty($_POST["dbhost"]) ? $_SESSION["dbhost"] : $_POST["dbhost"];
					}
					$return->yield->title = ">>".$_SESSION["dbpassword"]."<<";

					try {
						Config::dbInfo("dbusername");
					} catch (Exception $e) {
						if ($e->getCode() == Config::ERR_VARIABLES_MISSING)
						{
							// Config file not yet set up
							if (isset($_SESSION["dbusername"]))
							{
								Config::loadTemporaryDBSettings((object) [
									"host" => $_SESSION["dbhost"],
									"username" => $_SESSION["dbusername"],
									"password" => $_SESSION["dbpassword"]
								]);
							}
						}
						else {
							throw new LogicException("I don't know what error you managed to get so you need to debug more deeply", 1001);
						}
					}

					// var_dump($_POST);
					// Try to connect

					require_once("common/DBService.php");
					try {
						$dbconnection = new DBService(false);
					} catch (Exception $e) {
						if ($e->getCode() == Config::ERR_VARIABLES_MISSING
											|| $e->getCode() == 1045  // 1045 = Mysqli Access Denied
											|| $e->getCode() == 2002) // 2002 = Mysqli couldn't connect
						{
							require "install/templates/database_details.php";
							$return->yield->body = database_details();
							if ($e->getCode() == 1045)
							{
								$return->yield->messages[] = _("Unfortunately, although we could find the database, access was denied.");
								$return->yield->messages[] = _("Please review your settings.");
							}
							elseif ($e->getCode() == 2002)
							{
								$return->yield->messages[] = _("Unfortunately we could not connect to the host.");
								$return->yield->messages[] = _("Please review your settings.");
							}
							elseif (isset($_SESSION["dbusername"]))
							{
								$return->yield->messages[] = _("Unfortunately we were not able to access the database with the details you provided.");
								$return->yield->messages[] = _("Please review your settings.");
							}
							else
							{
								$return->yield->messages[] = _("To begin with, we need a username and password create the databases CORAL and its modules will be using.");
							}
							$return->success = false;
							return $return;
						}
						else {
							echo "We haven't prepared for the following error (installer.php):<br />\n";
							var_dump($e);
						}
					}

					$we_created_the_database = false;
					try {
						// TODO: check if there are any filled in db names to use here:
						$dbconnection->selectDB("coral_organizations");
					}
					catch (Exception $e) {
						if ($e->getCode() == DBService::ERR_COULD_NOT_SELECT_DATABASE) { // 1046 = couldn't select db
							// Try to create database
							$result = $dbconnection->processQuery("CREATE DATABASE coral_organizations;");
							if ($result)
							{
								$we_created_the_database = true;
							}
							else {
								// What to do if we couldn't create the database
							}
						}
						var_dump($e);
					}

					$result = $dbconnection->processQuery("CREATE TABLE test (id int);");
					if (!$result)
					{
						//TODO: ask if the databases are prepopulated if $we_created_the_database
						// $return->yield->messages[] = _("We were unable to create a table. Please check your user rights.");
						// $return->success = false;
						// return $return;
					}
					$dbconnection->processQuery("DROP TABLE test;");
					return $return;
				}
			],[
				"uid" => _("databases_created"),
				"translatable_title" => _("Databases Created"),
				"dependencies_array" => ["have_database_access"],
				"required" => true,
				"installer" => function() {

				}
			],[
				"uid" => "have_default_user",
				"translatable_title" => _("Have default user"),
				"dependencies_array" => ["have_database_access"],
				"required" => true,
				"installer" => function() {

				}
			],
		];
		//TODO: GET AVAILABLE MODULES AND EXPAND CHECKLIST
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

	public function runTestForResult($test_uid)
	{
		$key = $this->getKeyFromUid($test_uid);
		if ($key === false)
			throw new OutOfBoundsException("Test '{$this->getTitleFromUid($test_uid)}' not found in checklist.", 100);

		$result = call_user_func( $this->checklist[$key]["installer"] );
		if ($result === null)
			throw new UnexpectedValueException("The install script for '{$this->getTitleFromUid($test_uid)}' has not returned a null result (which is not allowed).", 101);

		return $result;
	}

	private function have_database_access()
	{

	}
	private function databases_created()
	{
		$return = new stdClass();
		$return->success = true;
		return $return;
	}

	//i.e. user with just select insert etc. on the dbs
	private function have_default_user()
	{
		// -> need to support multiple users here
		$return = new stdClass();
		$return->success = true;
		return $return;
	}

	private function successful_install()
	{
		$return = new stdClass();
		$return->success = true;
		return $return;
	}
}
