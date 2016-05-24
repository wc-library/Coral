<?php
require_once("common/DBService.php");
require_once("common/DBResult.php");

session_start();
class Installer {

	protected $checklist = [];
	protected $shared_module_info = [];
	protected $messages = [];

	function __construct() {

		$this_shared_module_info = &$this->shared_module_info;

		$this->shared_module_info = [
			"setSharedModuleInfo" => function($for_module, $key, $value) use (&$this_shared_module_info) {
				$this_shared_module_info[$for_module][$key] = $value;
			},
		];

		//TODO: remove "required"?
		$this->checklist = [
			[
				"uid" => "meets_system_requirements",
				"translatable_title" => _("Meets system requirements"),
				"dependencies_array" => [],
				"required" => true,
				"installer" => function($shared_module_info) {
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
				"dependencies_array" => [],
				"required" => true,
				"installer" => function($shared_module_info) {
					$return = new stdClass();
					$return->yield = new stdClass();
					$return->success = false;

					require_once "install/templates/try_again_template.php";
					$return->yield->body = try_again_template();

					require_once("common/Config.php");

					$return->yield->title = "<b>" . _('Current Test:') . "</b> " . _('Trying to write configuration file');

					$return->success = true;
					// If file exists, see if it's writable - otherwise see if directory is writable (we can create it)
					$config_files = array_map(function($cfg) {
						$root_dir = dirname($_SERVER["REQUEST_URI"]);
						return [ "path" => "$cfg[directory]/admin/configuration.ini", "title" => $cfg["title"] ];
					}, $shared_module_info["module_list"]);
					array_unshift($config_files, [ "path" => Config::CONFIG_FILE_PATH, "title" => "Core Configuration"]);
					foreach ($config_files as $cfg) {
						$file_exists = file_exists($cfg["path"]);
						$writable_test = $cfg["path"];
						$writable_test = $file_exists ? $cfg["path"] : dirname($cfg["path"]);

						if (is_writable($writable_test))
						{
							if (is_readable($cfg["path"]) || !$file_exists)
							{
								continue; // Success!
							}
							else
							{
								$return->yield->messages[] = sprintf( _("In order to proceed with the installation, we must be able to read the '%s' configuration file at '<span class=\"highlight\">%s</span>'."), $cfg["title"], $cfg["path"] );
								$return->success = false;
							}
							$return->yield->messages[] = sprintf( _("We can write to the '%s' configuration file at '<span class=\"highlight\">%s</span>' but we cannot read from it."), $cfg["title"], $cfg["path"] );
							$return->success = false;
						}
						else {
							$return->yield->messages[] = sprintf( _("In order to proceed with the installation, we must be able to write to the '%s' configuration file at '<span class=\"highlight\">%s</span>'."), $cfg["title"], $cfg["path"] )
														.sprintf( "<br /><b>" . _("Try") . ":</b> <span class=\"highlight\">chmod 777 %s</span>", $cfg["path"] );
							$return->success = false;
						}
					}

					if ($return->success)
					{
						$shared_module_info["setSharedModuleInfo"](
							"provided",
							"write_config_file",
							function($path, $settingsObject){
								$file = fopen($path, 'w');
								foreach ($settingsObject as $key => $value) {
									$dataToWrite[] = "[$key]";
									foreach ($variable as $key => $value) {
										$dataToWrite[] = "$key=$value";
									}
									$dataToWrite[] = "";
								}
								fwrite($file, implode("\n",$dataToWrite));
								fclose($file);
							}
						);
					}
					else
					{
						$return->yield->title = "<b>" . _('Current Test:') . "</b> " . _('Trying to read and write configuration files');
					}
					return $return;
				}
			],[
				"uid" => "modules_to_use",
				"translatable_title" => _("Modules to use"),
				"dependencies_array" => [],
				"required" => true,
				"installer" => function($shared_module_info) {
					$return = new stdClass();
					$return->yield = new stdClass();
					$return->yield->title = _("Modules to use");
					$return->success = true;

					$module_list = $shared_module_info["module_list"];
					foreach ($module_list as $mod) {
						if ($mod["required"])
						{
							$_SESSION["modules_to_use"][$mod["uid"]] = true;
							$return->success &= true;
						}
						else if (isset($_POST[$mod["uid"]]))
						{
							$_SESSION["modules_to_use"][$mod["uid"]] = $_POST[$mod["uid"]] === 1;
							$return->success &= true;
						}
						else
						{
							// If the associated session variable is still unset the setup has failed but why?
							if (!isset($_SESSION["modules_to_use"][$mod["uid"]]))
							{
								$return->messages[] = "For some reason at least one of these variables is not set. There may a problem with the installer please contact the programmers with this error message.";
								$return->success &= false;
							}
						}
					}

					if (!$return->success)
					{
						require "install/templates/modules_to_use_template.php";
						$return->yield->body = modules_to_use_template($module_list);
					}
					return $return;
				}
			],[
				"uid" => "have_database_access",
				"translatable_title" => _("Have database access"),
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

					// Go through the databases and try to create them all (or see if they already exist)
					foreach ($shared_database_info as $db)
					{
						$dbfeedback = "db_" . $db["key"] . "_feedback";
						$dbnamestr = "db_" . $db["key"] . "_name";
						$dbname = empty($_SESSION[$dbnamestr]) ? $db["default_value"] : $_SESSION[$dbnamestr];
						if (empty($_SESSION[$dbfeedback]))
							$_SESSION[$dbfeedback] = "failed";
						try {
							$dbconnection->selectDB( $dbname );
							if ($_SESSION[$dbfeedback] == "failed")
								$_SESSION[$dbfeedback] = "already_existed";
						}
						catch (Exception $e) {
							switch ($e->getCode()) {
								case DBService::ERR_COULD_NOT_SELECT_DATABASE:
									try {
										$result = $dbconnection->processQuery("CREATE DATABASE `$dbname`;");
										$_SESSION[$dbfeedback] = "created";
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
					return $return;
				}
			],[
				"uid" => "have_default_user",
				"translatable_title" => _("Have default user"),
				"dependencies_array" => ["have_database_access"],
				"required" => true,
				"installer" => function($shared_module_info) {
					$return = new stdClass();
					$return->yield = new stdClass();

					$listofDatabases = ["coral_organizations", "coral_auth", "coral_usage"];
					$user     = "coral_regular_user";
					// $password = generate_password();
					//TODO: Handle individually assinged username/passwords
					foreach ($listofDatabases as $dbname)
					{
						// "GRANT SELECT, INSERT, UPDATE, DELETE ON $dbname TO $user@{Config::dbInfo('host')} IDENTIFIED BY '$password'";
					}

					//TODO: don't just return true...
					$return->success = true;
					$return->yield->messages[] = "incomplete installer";
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

	/** TODO: actually describe the $installer_object
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
						$this->register_installation_requirement($installer_object);
						$this->shared_module_info[ "module_list" ][] = [
							"directory" => $dir,
							"uid" => $installer_object["uid"],
							"title" => $installer_object["translatable_title"],
							"required" => $installer_object["required"]
						];
						if (isset($installer_object["getSharedInfo"]))
						{
							$this->shared_module_info[ $installer_object["uid"] ] = $installer_object["getSharedInfo"]();
						}
					}
					else
					{
						//TODO: do something with these messages
						$this->messages[] = "<b>Warning:</b> There is a problem with the installer for the '$dir' module (ignoring).";
					}
				}
			}
		}
	}

	public function runTestForResult($test_uid)
	{
		//TODO: check that dependencies are met
		$key = $this->getKeyFromUid($test_uid);
		if ($key === false)
			throw new OutOfBoundsException("Test '{$this->getTitleFromUid($test_uid)}' not found in checklist.", 100);

		$result = call_user_func( $this->checklist[$key]["installer"], $this->shared_module_info );
		if ($result === null)
			throw new UnexpectedValueException("The install script for '{$this->getTitleFromUid($test_uid)}' has returned a null result (which is not allowed).", 101);

		return $result;
	}
	public function getMessages()
	{
		$messages = $this->messages;
		$this->messages = [];
		return $messages;
	}

	public function successful_install()
	{
		$return = new stdClass();
		$return->title = _("Installation Complete");
		$return->body = _("Congratulations. Installation has been successful.");
		$return->redirect_home = true;
		return $return;
	}
}
