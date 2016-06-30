<?php
require_once("common/DBService.php");
require_once("common/DBResult.php");

class Installer {
	const CAUSE_DEPENDENCY_NOT_FOUND = 20041;
	const CAUSE_ALREADY_EXISTED = 20043;
	const ERR_CIRCULAR_DEPENDENCIES = 20044;
	const ERR_CIRCULAR_WANTS = 20045;
	const ERR_MODULE_DOES_NOT_EXIST = 20046;
	const ERR_INVALID_TEST_RESULT = 20047;
	const ERR_RUNNING_POST_INSTALLATION_TEST_BEFORE_INSTALLATION_COMPLETE = 20048;

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
		$this->scanForModuleInstallers();
		$this->applyRequired();
		$this->post_installation_mode = isset($_SESSION["installer_post_installation"]) && $_SESSION["installer_post_installation"];
	}
	private function getKeyFromUid($test_uid, $haystack = null)
	{
		$haystack = $haystack === null ? $this->checklist : $haystack;

		require_once("common/array_column.php");
		$key = array_search($test_uid, array_column($haystack, 'uid'));
		if ($key === false)
			throw new OutOfBoundsException("Test '$test_uid' not found in checklist.", $this::ERR_MODULE_DOES_NOT_EXIST);

		return $key;
	}
	public function getCheckListUids()
	{
		$arr = $this->checklist;
		usort($arr, function($a, $b){
		    if (isset($a["required"]) && $a["required"] && !isset($a["alternative"])) {
		        return isset($b["required"]) && $b["required"] && !isset($b["alternative"]) ? 0 : -1;
		    }
			else {
				return isset($b["required"]) && $b["required"] && !isset($b["alternative"]) ? 1 : 0;
			}
		});
		require_once("common/array_column.php");
		return array_column($arr, "uid");
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
	public function isRequired($uid)
	{
		$alt = isset($this->checklist[ $this->getKeyFromUid($uid) ]["alternative"]) ? true : false;
		$req = isset($this->checklist[ $this->getKeyFromUid($uid) ]["required"]) ? $this->checklist[ $this->getKeyFromUid($uid) ]["required"] && !$alt : false;
		if (isset($this->shared_module_info["modules_to_use"]["useModule"][$uid]))
		{
			$req |= $this->shared_module_info["modules_to_use"]["useModule"][$uid];
		}
		return $req;
	}
	private function applyRequiredToDependencies($uid)
	{
		$key = $this->getKeyFromUid($uid);
		if (!isset($this->checklist[$key]["required"]) || !$this->checklist[$key]["required"])
			return;

		if (isset($this->checklist[$key]["dependencies_array"]))
		{
			foreach ($this->checklist[$key]["dependencies_array"] as $dep)
			{
				try
				{
					$this->checklist[ $this->getKeyFromUid($dep) ]["required"] = true;
					$this->applyRequiredToDependencies($dep);
				}
				catch (Exception $e)
				{
					if ($e->getCode() == $this::ERR_MODULE_DOES_NOT_EXIST)
					{
						$mod_title = $this->checklist[$key]["translatable_title"];
						$this->messages[] = "<b>Warning:</b> There is a problem with the installer for the '$mod_title' module. Dependency '$dep' not found (ignoring).";
					}
					else
					{
						throw $e;
					}
				}
			}
		}
	}
	private function applyRequired()
	{
		foreach ($this->checklist as $test)
		{
			if (isset($test["required"]) && $test["required"])
			{
				$this->applyRequiredToDependencies($test["uid"]);
			}
		}
	}

	public function register_installation_requirement($installer_object, $module_name)
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
		$function_name = "register_${module_name}_requirement";
		if (is_callable($function_name))
		{
			$installer_object = call_user_func($function_name);
			$this->register_installation_requirement($installer_object, $module_name);
			if (!$core_module)
			{
				$mod = [
					"directory" => $module_name,
					"uid" => $installer_object["uid"],
					"title" => $installer_object["translatable_title"],
					"required" => isset($installer_object["required"]) ? $installer_object["required"] : false
				];
				if (isset($installer_object["alternative"]))
				{
					$mod["alternative"] = $installer_object["alternative"];
				}
				if (isset($installer_object["dependencies_array"]))
				{
					$mod["dependencies_array"] = $installer_object["dependencies_array"];
				}
				$this->shared_module_info["module_list"][] = $mod;
			}
			if (isset($installer_object["sharedInfo"]))
			{
				$this->shared_module_info[ $installer_object["uid"] ] = $installer_object["sharedInfo"];
			}
		}
		else
		{
			$this->messages[] = "<b>Warning:</b> There is a problem with the installer for the '$module_name' module (ignoring).";
		}
	}
	private function scanForModuleInstallers()
	{
		// Core Requirements
		$core_requirements_path = "install/requirements/";
		$core_requirements = scandir($core_requirements_path);
		foreach ($core_requirements as $req_module)
		{
			if (trim($req_module, ".") !== "")
			{
				$module_name = basename($req_module, ".php");
				$path = $core_requirements_path . $req_module;
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
			throw new OutOfBoundsException("Test '{$this->getTitleFromUid($test_uid)}' not found in checklist.", $this::ERR_MODULE_DOES_NOT_EXIST);

		if (isset($this->checklist[$key]["result"]))
		{
			$return = new stdClass();
			$return->skipped = true;
			$return->cause = $this::CAUSE_ALREADY_EXISTED;
			return $return;
		}
		if (!$this->shared_module_info["getPostInstallationMode"]() && isset($this->checklist[$key]["post_installation"]) && $this->checklist[$key]["post_installation"])
		{
			throw new RuntimeException("Error: You're trying to run the '$test_uid' post-installation test before the installation is complete.", $this::ERR_RUNNING_POST_INSTALLATION_TEST_BEFORE_INSTALLATION_COMPLETE);
		}

		foreach ($this->getDependenciesAndRequiredWants($test_uid) as $dependency) {
			$dep_key = array_search($dependency, array_column($this->checklist, 'uid'));
			if ($dep_key === false)
			{
				$return = new stdClass();
				$return->skipped = false;
				$return->cause = $this::CAUSE_DEPENDENCY_NOT_FOUND;
				$return->missing_dependency = $dependency;
				return $return;
			}

			if (!isset($this->checklist[$dep_key]["result"]))
			{
				if (in_array($dependency, $required_for))
				{
					$required_array = var_export($required_for, true);
					throw new RuntimeException("Error: Circular dependencies ('$test_uid' in $required_array)", $this::ERR_CIRCULAR_DEPENDENCIES);
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
	private function getDependenciesAndRequiredWants($uid)
	{
		$key = $this->getKeyFromUid($uid);
		$dependencies_array = isset($this->checklist[$key]["dependencies_array"]) ? $this->checklist[$key]["dependencies_array"] : [];
		$wants_array = [];
		if (isset($this->checklist[$key]["wants"]) && is_array($this->checklist[$key]["wants"]))
		{
			foreach ($this->checklist[$key]["wants"] as $wuid) {
				$wkey = $this->getKeyFromUid($wuid);
				if ($this->checklist[$wkey]["required"])
					$wants_array[] = $wuid;
			}
		}
		return array_merge($dependencies_array, $wants_array);
	}
	private function actuallyRunTest($uid)
	{
		$key = $this->getKeyFromUid($uid);
		$result = call_user_func( $this->checklist[$key]["installer"], $this->shared_module_info );
		// TODO: we need to test this throw
		if ($result === null)
			throw new UnexpectedValueException("The script for '{$this->getTitleFromUid($installer["uid"])}' has returned a null result (which is not allowed).", $this::ERR_INVALID_TEST_RESULT);

		$this->checklist[$key]["result"] = $result;
		if ($result->success)
			$this->successfully_completed_tests[] = $uid;

		return $result;
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
		// $tests_to_complete = array_filter($this->getCheckListUids, function ($uid) use ($isRequired) {
		// 	return $isRequired($uid);
		// });
		// var_dump(array_diff($tests_to_complete, $completed_tests));
		// exit();
		// TODO: Perhaps we should check that all the (required) getCheckListUids
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
