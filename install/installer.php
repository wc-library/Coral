<?php
require_once("common/DBService.php");
require_once("common/DBResult.php");

class Installer {
	const CAUSE_DEPENDENCY_NOT_FOUND = 20041;
	const CAUSE_ALREADY_EXISTED = 20042;

	const ERR_CIRCULAR_DEPENDENCIES = 20044;
	const ERR_MODULE_DOES_NOT_EXIST = 20045;
	const ERR_UPGRADE_DOES_NOT_EXIST = 20046;
	const ERR_INVALID_TEST_RESULT = 20047;
	const ERR_RUNNING_POST_INSTALLATION_TEST_BEFORE_INSTALLATION_COMPLETE = 20048;

	const REQUIRED_FOR_INSTALL = 501;
	const REQUIRED_FOR_UPGRADE = 502;
	const REQUIRED_FOR_MODIFY  = 503;

	protected $checklist = [];
	protected $shared_module_info = [];
	protected $messages = [];
	protected $successfully_completed_tests = [];
	protected $post_installation_mode = false;

	function __construct() {
		$this_shared_module_info  = &$this->shared_module_info;
		$this_checklist           = &$this->checklist;
		$this_post_installation_mode = &$this->post_installation_mode;
		$this->shared_module_info = [
			"setSharedModuleInfo" => function($for_module, $key, $value) use (&$this_shared_module_info) {
				$this_shared_module_info[$for_module][$key] = $value;
			},
			"registerInstallationTest" => function($installer_object) use (&$this_checklist) {
				// TODO: What happens if we register a regular old test [and it's required]? Will it run?
				$required_variables = [
					"uid",
					"translatable_title",
					"installer"
				];
				foreach ($required_variables as $req)
				{
					if (!isset($installer_object[$req]))
					{
						$this->messages[] = _("<b>Warning:</b> A post installion test is malformed.");
						return;
					}
				}
				$this_checklist[] = $installer_object;
			},
			"getPostInstallationMode" => function() use (&$this_post_installation_mode) {
				return isset($_SESSION["installer_post_installation"]) && $_SESSION["installer_post_installation"];
			}
		];
		$this->scanForInstallerProviders();
		$this->post_installation_mode = isset($_SESSION["installer_post_installation"]) && $_SESSION["installer_post_installation"];
	}
	private function getKeyFromUid($test_uid, $haystack = null)
	{
		$haystack = $haystack === null ? $this->checklist : $haystack;

		require_once("common/array_column.php");
		$key = array_search($test_uid, array_column($haystack, 'uid'));
		if ($key === false)
			throw new OutOfBoundsException("Test '$test_uid' not found in checklist.", self::ERR_MODULE_DOES_NOT_EXIST);

		return $key;
	}
	public function getRequiredProviders($what_for = self::REQUIRED_FOR_INSTALL)
	{
		require_once("common/array_column.php");
		return array_column(array_filter($this->checklist, function($item) use ($what_for) {
			return isset($item["required_for"]) && in_array($what_for, $item["required_for"]);
		}), "uid");
	}
	public function getPostInstallationUids()
	{
		return array_filter($this->checklist, function($item){
			return isset($item["post_installation"]) && $item["post_installation"];
		});
	}
	public function getTitleFromUid($uid)
	{
		return $this->checklist[ $this->getKeyFromUid($uid) ]["translatable_title"];
	}

	public function register_installation_provider($installer_object, $module_name)
	{
		$required_variables = [
			"uid",
			"translatable_title",
			"installer"
		];
		foreach ($required_variables as $req)
		{
			if (!isset($installer_object[$req]))
			{
				$this->messages[] = "<b>Warning:</b> The installer for '$module_name' is broken (ignoring). [Missing '$req']";
				return;
			}
		}
		$this->checklist[] = $installer_object;
	}

	private function addModule($path, $module_name, $core_module = false)
	{
		require $path;
		$function_name = "register_${module_name}_provider";
		if (is_callable($function_name))
		{
			$installer_object = call_user_func($function_name);
			$this->register_installation_provider($installer_object, $module_name);
			if (!$core_module)
			{
				$mod = [
					"directory" => $module_name,
					"uid" => $installer_object["uid"],
					"title" => $installer_object["translatable_title"],
				];
				$this->shared_module_info["module_list"][] = $mod;
			}
			if (isset($installer_object["sharedInfo"]))
			{
				$this->shared_module_info[ $installer_object["uid"] ] = $installer_object["sharedInfo"];
			}
		}
		else
		{
			$this->messages[] = "<b>Warning:</b> There is a problem with the installer for the '$module_name' module (ignoring). The required function '$function_name' is not callable.";
		}
	}
	private function scanForInstallerProviders()
	{
		// Core Requirements
		$core_install_provider_path = "install/install_providers/";
		$core_install_providers = scandir($core_install_provider_path);
		foreach ($core_install_providers as $provider)
		{
			if (trim($provider, ".") !== "")
			{
				$module_name = basename($provider, ".php");
				$path = $core_install_provider_path . $provider;
				$this->addModule($path, $module_name, true);
			}
		}

		// Module Installers
		$MODULE_ROOT = ".";
		$module_directories = scandir($MODULE_ROOT);
		foreach ($module_directories as $dir)
		{
			if (is_dir("$MODULE_ROOT/$dir"))
			{
				$installation_root_file = "$MODULE_ROOT/$dir/install/$dir.php";
				if (file_exists($installation_root_file))
				{
					$this->addModule($installation_root_file, $dir);
				}
			}
		}
	}

	public function runTestForResult($test_uid, $required_for = [])
	{
		$key = $this->getKeyFromUid($test_uid);
		if ($key === false)
			throw new OutOfBoundsException("Test '{$this->getTitleFromUid($test_uid)}' not found in checklist.", self::ERR_MODULE_DOES_NOT_EXIST);

		if (isset($this->checklist[$key]["result"]))
		{
			$return = new stdClass();
			$return->skipped = true;
			$return->cause = self::CAUSE_ALREADY_EXISTED;
			return $return;
		}
		if (!$this->shared_module_info["getPostInstallationMode"]() && isset($this->checklist[$key]["post_installation"]) && $this->checklist[$key]["post_installation"])
		{
			throw new RuntimeException("Error: You're trying to run the '$test_uid' post-installation test before the installation is complete.", self::ERR_RUNNING_POST_INSTALLATION_TEST_BEFORE_INSTALLATION_COMPLETE);
		}

		foreach ($this->getDependencies($test_uid) as $dependency) {
			$dep_key = array_search($dependency, array_column($this->checklist, 'uid'));
			if ($dep_key === false)
			{
				$return = new stdClass();
				$return->skipped = false;
				$return->cause = self::CAUSE_DEPENDENCY_NOT_FOUND;
				$return->missing_dependency = $dependency;
				return $return;
			}

			if (!isset($this->checklist[$dep_key]["result"]))
			{
				if (in_array($dependency, $required_for))
				{
					$required_array = var_export($required_for, true);
					throw new RuntimeException("Error: Circular dependencies ('$test_uid' in $required_array)", self::ERR_CIRCULAR_DEPENDENCIES);
				}
				$required_for[] = $dependency;
				$result = $this->runTestForResult($dependency, $required_for);
				// If one of the requirements fails, we need its result to be yielded
				if (!$result->success)
					return $result;
			}
		}
		return $this->actuallyRunTest($test_uid, $this->checklist[$key]["installer"]);
	}
	private function getDependencies($uid)
	{
		$key = $this->getKeyFromUid($uid);
		return isset($this->checklist[$key]["dependencies_array"]) ? $this->checklist[$key]["dependencies_array"] : [];
	}
	private function actuallyRunTest($uid)
	{
		$key = $this->getKeyFromUid($uid);
		$result = call_user_func( $this->checklist[$key]["installer"], $this->shared_module_info );
		// TODO: we need to test this throw
		if ($result === null)
			throw new UnexpectedValueException("The script for '{$this->getTitleFromUid($installer["uid"])}' has returned a null result (which is not allowed).", self::ERR_INVALID_TEST_RESULT);

		$this->shared_module_info["debug"][] = $uid;
		$this->checklist[$key]["result"] = $result;
		if ($result->success)
			$this->successfully_completed_tests[] = $uid;

		return $result;
	}

	public function upgrade_to_version($destination_version)
	{
		// Need to figure out a modular way of handling this:
		/**
		 * Maybe we need to consider installation modes:
		 * 		upgrade
		 * 		modify
		 * 		install
		 * 	 along with the flag "post_mode" => for post-installation/modification/upgrade scripts to run
		 *
		 * Maybe upgrader should return an installer array with dependencies and
		 * everything depending on the version we give it and maybe we should
		 * have an installer that upgraders can depend on that will process sql
		 * files and update conf files but how do we get it to run after them?
		 * that implies we have functional "required" flag but they only work
		 * for the installer...
		 *
		 * So new plan:
		 * 	We check the required_for var which will tell us whether needed for
		 * 	upgrade, modify or install. Installers with required_for set are
		 * 	basically doing all the heavy lifting (fancy type stuff that
		 * 	modules_to_use_helper does). When we are installing, we look for
		 * 	inarray(required_for, install)...
		 *
		 * It depends on everything needed for that thing...
		 *
		 */
		require_once("common/Config.php");
		$modules_to_install = Config::getInstalledModules();
		$_SESSION["modules_to_use"]["useModule"] = array_map(function($item){
			return [$item => true];
		}, $modules_to_install);

		// Check that all the modules can be upgraded
		$cannot_upgrade = [];
		foreach ($modules_to_install as $uid)
		{
			try {
				// See if the module is in our installer checklist
				$key = $this->getKeyFromUid($uid);
				// TODO: Check if there is an upgrader set for this version...
				// echo $uid . " -(to_v)-> " . $destination_version;
			} catch (Exception $e) {
				if ($e->getCode() == self::ERR_MODULE_DOES_NOT_EXIST)
				{
					// The module is installed but its installer is not in our
					// checklist (so nor is its upgrade)
					$cannot_upgrade[] = $e->getMessage();
				}
				else
				{
					// $cannot_upgrade[] = unknown reason
					throw $e;
				}
			}
		}

		if (count($cannot_upgrade) > 0)
		{
			var_dump($cannot_upgrade);
		}
		else
		{
			foreach ($modules_to_install as $uid)
			{
				$key = $this->getKeyFromUid($uid);
				if (!isset($this->checklist[$key]["upgrader"]))
				{
					throw new Exception("Sorry, the upgrader for $uid does not exist and so the ugrade cannot be completed", self::ERR_UPGRADE_DOES_NOT_EXIST);
				}
				else
				{
					$this->checklist[$key]["upgrader"]($destination_version);
				}
			}
		}

	}

	public function getMessages()
	{
		$messages = $this->messages;
		$this->messages = [];
		return $messages;
	}
	public function getSuccessfullyCompletedTests()
	{
		$uids = [];
		foreach ($this->successfully_completed_tests as $uid)
		{
			// Only return uid if it's not hid[den]_from_completion_list
			// And it's not post_installation
			$hide = isset($this->checklist[ $this->getKeyFromUid($uid) ]["hide_from_completion_list"]) ? $this->checklist[ $this->getKeyFromUid($uid) ]["hide_from_completion_list"] : false;
			$post_installation = isset($this->checklist[ $this->getKeyFromUid($uid) ]["post_installation"]) ? $this->checklist[ $this->getKeyFromUid($uid) ]["post_installation"] : false;
			if (!$hide && !$post_installation)
				$uids[] = $uid;
		}
		return $uids;
	}
	public function getSuccessfullyCompletedTestTitles()
	{
		$titles = [];
		foreach ($this->getSuccessfullyCompletedTests() as $uid) {
			$titles[] = $this->getTitleFromUid($uid);
		}
		return $titles;
	}
	public function getApproxiamateCompletion()
	{
		return count($this->successfully_completed_tests) / (float) count($this->checklist);
	}

	public function declareInstallationComplete()
	{
		// $completed_tests = $this->successfully_completed_tests;
		// // $isRequired = $this->isRequired;
		// $tests_to_complete = array_filter($this->getRequiredProviders, function ($uid) use ($isRequired) {
		// 	return $isRequired($uid);
		// });
		// var_dump(array_diff($tests_to_complete, $completed_tests));
		// exit();
		// TODO: Perhaps we should check that all the (required) getRequiredProviders
		//       are installed before we allow this.
		$this->shared_module_info["post_installation_mode"] = true;
		$_SESSION["installer_post_installation"] = true;
	}
	public function postInstallationTest()
	{
		foreach ($this->getPostInstallationUids() as $test)
		{
			$return = $this->runTestForResult($test["uid"]);
			if (!$return->success)
				return $return;
		}
		return false;
	}
}
