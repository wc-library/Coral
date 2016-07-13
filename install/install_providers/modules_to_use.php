<?php
function register_modules_to_use_provider()
{
	$MODULE_VARS = [
		"uid" => "modules_to_use",
		"translatable_title" => _("Modules to use"),
		"dependencies_array" => ["meets_system_requirements"],
		"hide_from_completion_list" => true,
	];

	return array_merge( $MODULE_VARS,[
		"installer" => function($shared_module_info) use ($MODULE_VARS) {
			$return = new stdClass();
			$return->yield = new stdClass();
			$return->yield->title = _("Modules to use");
			$return->success = true;

			$module_list = $shared_module_info["module_list"];
			$modules_not_to_install = [];

			foreach ($module_list as $i => $mod)
			{
				$mod_chosen = null;
				// We can only auto-set if mod is required
				if ($mod["required"])
				{
					$mod_chosen = true;
					$return->success &= true;
				}
				elseif (isset($_POST[$mod["uid"]]))
				{
					$mod_chosen = $_POST[$mod["uid"]] == 1;
					$return->success &= true;
				}

				if ($mod_chosen !== null || isset($_SESSION[ $MODULE_VARS["uid"] ]["useModule"][ $mod["uid"] ]))
				{
					$mod_chosen = $mod_chosen !== null ? $mod_chosen : $_SESSION[ $MODULE_VARS["uid"] ]["useModule"][ $mod["uid"] ];
					$_SESSION[ $MODULE_VARS["uid"] ]["useModule"] = isset($_SESSION[ $MODULE_VARS["uid"] ]["useModule"]) ? $_SESSION[ $MODULE_VARS["uid"] ]["useModule"] : [];
					$_SESSION[ $MODULE_VARS["uid"] ]["useModule"][ $mod["uid"] ] = $mod_chosen;
					$shared_module_info["setSharedModuleInfo"]($MODULE_VARS["uid"], "useModule", $_SESSION[ $MODULE_VARS["uid"] ]["useModule"]);
					$module_list[$i]["default_value"] = $mod_chosen;
					if (!$mod_chosen)
						$modules_not_to_install[] = $mod["uid"];
				}
				else
				{
					// If the associated session variable is still unset the setup has failed but why?
					$return->messages[] = "For some reason at least one ($mod[uid]) of these variables is not set. There may a problem with the installer please contact the programmers with this error message.";
					$return->success &= false;
				}
			}

			//check that all dependencies are met
			if (count($modules_not_to_install) > 0)
			{
				$dep_list = [];
				// build dependency list
				foreach ($module_list as $mod) {
					if (!isset($mod["dependencies_array"]))
					continue;
					$dep_list = array_unique(array_merge($dep_list, $mod["dependencies_array"]));
				}
				foreach ($modules_not_to_install as $mod) {
					if (in_array($mod, $dep_list))
					{
						$mod_title = array_values(array_filter($module_list, function ($m) use ($mod) {
							return $m["uid"] == $mod;
						}))[0]["title"];
						$return->yield->messages[] = sprintf(_("The modules that you have chosen to install work with additional modules. You need to add '%s'"), $mod_title);
						$return->success = false;
					}
				}
			}

			if (!$return->success)
			{
				require_once "install/templates/modules_to_use_template.php";
				$return->yield->body = modules_to_use_template($module_list);
			}
			else
			{
				$shared_module_info["setSharedModuleInfo"](
					"provided",
					"get_modules_to_use_config",
					function($smi){
						$conf = [];
						$modules_to_use = $smi["modules_to_use"]["useModule"];
						foreach ($modules_to_use as $key => $value) {
							$conf["{$key}Module"] = $value ? 'Y' : 'N';
							if ($value && isset($smi[$key]["db_name"]))
								$conf["{$key}DatabaseName"] = $smi[$key]["db_name"];
						}

						// This assumes too much knowledge of the auth module but since we're going towards a common config file this will go away eventually.
						if ($conf["authModule"] == 'N')
						{
							// TODO: ensure that this actually works
							$conf["remoteAuthVariableName"] = $smi["remote_auth"]["remote_auth_variable"];
						}
						return $conf;
					}
				);
			}

			return $return;
		}
	]);
}
