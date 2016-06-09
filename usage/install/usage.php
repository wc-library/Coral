<?php
function register_usage_requirement()
{
	$MODULE_VARS = [
		"uid" => "usage",
		"translatable_title" => _("Usage Module"),
		"dependencies_array" => [ "db_tools", "have_read_write_access_to_config", "modules_to_use" ],
		"required" => false,
		"wants" => [],
		"getSharedInfo" => function () {
			return [
				"database" => [
					"title" => _("Usage Database"),
					"default_value" => "coral_usage"
				],
				"config_file" => [
					"path" => "usage/admin/configuration.ini",
				]
			];
		}
	];
	return array_merge( $MODULE_VARS, [
		"installer" => function($shared_module_info) use ($MODULE_VARS) {
			$return = new stdClass();
			$return->yield = new stdClass();
			$return->success = false;
			$return->yield->title = _("Usage Module");

			$this_db_name = $shared_module_info[ $MODULE_VARS["uid"] ]["db_name"];
			$dbconnection = $shared_module_info["provided"]["get_db_connection"]( $this_db_name );

			$result = $shared_module_info["provided"]["check_db"]($dbconnection, $shared_module_info[$MODULE_VARS["uid"]], "Publisher", $MODULE_VARS["translatable_title"]);
			if ($result)
				return $result;

			$sql_files_to_process = ["usage/install/protected/test_create.sql", "usage/install/protected/install.sql"];
			$ret = $shared_module_info["provided"]["process_sql_files"]( $dbconnection, $sql_files_to_process, $MODULE_VARS["uid"] );
			if (!$ret["success"])
			{
				$return->success = false;
				$return->yield->messages = array_merge($return->yield->messages, $ret["messages"]);
				return $return;
			}

			$shared_module_info["provided"]["set_up_admin_in_db"]($dbconnection, $shared_module_info["common"]["default_user"]["username"]);


			$_SESSION[$MODULE_VARS["uid"]]["useOutliers"] = isset($_POST["useOutliers"]) ? $_POST["useOutliers"] : true;
			$_SESSION[$MODULE_VARS["uid"]]["baseURL"] = isset($_POST["baseURL"]) ? $_POST["baseURL"] : "";
			$usage_fields = [
				[
					"key" => "baseURL",
					"title" => _("Link Resolver Base URL (optional)"),
					"type" => "text",
					"default_value" => $_SESSION[$MODULE_VARS["uid"]]["baseURL"]
				],[
					"key" => "useOutliers",
					"title" => _("Use Outlier Flagging Feature"),
					"type" => "checkbox",
					"default_value" => $_SESSION[$MODULE_VARS["uid"]]["useOutliers"]
				]
			];
			if (!isset($_SESSION[$MODULE_VARS["uid"]]["formCompleted"]) || !$_SESSION[$MODULE_VARS["uid"]]["formCompleted"])
			{
				require_once "install/templates/usage_module_template.php";
				$title = _("Please set up the following options for the Usage module.");
				$return->yield->body = usage_module_template($title, $usage_fields);
				$return->success = false;
				$_SESSION[$MODULE_VARS["uid"]]["formCompleted"] = true;
				return $return;
			}
			// To validate form - put validation code here and set `$_SESSION[$MODULE_VARS["uid"]]["formCompleted"] = false`
			$shared_module_info["setSharedModuleInfo"]($MODULE_VARS["uid"], "baseUrl", $_SESSION[$MODULE_VARS["uid"]]["baseURL"]);


			//set up config file
			$configFile = $MODULE_VARS["getSharedInfo"]()["config_file"]["path"];
			$iniData = array();
			//config file: settings
			$iniData["settings"] = [
				"useOutliers" => $_SESSION[$MODULE_VARS["uid"]]["useOutliers"] ? "Y" : "N",
				"baseURL" => $_SESSION[$MODULE_VARS["uid"]]["baseURL"]
			];
			$cooperating_modules = [
				"licensing" => "doesnt_need_db",
				"organizations" => "needs_db",
				"auth" => "needs_db",
				"resources" => "doesnt_need_db",
				"reporting" => "doesnt_need_db"
			];
			foreach ($cooperating_modules as $key => $value) {
				if (isset($shared_module_info["modules_to_use"][$key]["useModule"]))
				{
					$iniData["settings"]["{$key}Module"] = $shared_module_info["modules_to_use"][$key]["useModule"] ? 'Y' : 'N';
					if ($value == "needs_db" && $shared_module_info["modules_to_use"][$key]["useModule"])
						$iniData["settings"]["{$key}DatabaseName"] = $shared_module_info[$key]["db_name"];
				}
			}
			if ($iniData["settings"]["authModule"] == 'N')
			{
				$iniData["settings"]["remoteAuthVariableName"] = $shared_module_info["auth"]["alternative"]["remote_auth_variable_name"];
			}
			//config file: database
			$iniData["database"] = [
				"type" => "mysql",
				"host" => Config::dbInfo("host"),
				"name" => $this_db_name,
				"username" => Config::dbInfo("username"),
				"password" => Config::dbInfo("password")
			];
			//config file: write out
			$shared_module_info["provided"]["write_config_file"]($configFile, $iniData);

			$return->success = true;
			return $return;
		}
	]);
}
