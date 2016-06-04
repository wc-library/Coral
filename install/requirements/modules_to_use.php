<?php
function register_modules_to_use_requirement()
{
	$MODULE_VARS = [
		"uid" => "modules_to_use",
		"translatable_title" => _("Modules to use"),
		"hide_from_completion_list" => true,
	];

	return array_merge( $MODULE_VARS,[
		"installer" => function($shared_module_info) use ($MODULE_VARS) {
			$return = new stdClass();
			$return->yield = new stdClass();
			$return->yield->title = _("Modules to use");
			$return->success = true;

			$module_list = $shared_module_info["module_list"];
			foreach ($module_list as $mod)
			{
				// We can only auto-set if there is no alternative and mod is required
				if ($mod["required"] && !isset($mod["alternative"]))
				{
					$_POST[$mod["uid"]] = 1;
					$return->success &= true;
				}
				if (isset($_POST[$mod["uid"]]))
				{
					if (!isset($_SESSION[ $MODULE_VARS["uid"] ][ $mod["uid"] ]))
					{
						$_SESSION[ $MODULE_VARS["uid"] ][ $mod["uid"] ] = [];
					}
					$_SESSION[ $MODULE_VARS["uid"] ][ $mod["uid"] ]["useModule"] = $_POST[$mod["uid"]] == 1;
					$shared_module_info["setSharedModuleInfo"]($MODULE_VARS["uid"], $mod["uid"], ["useModule" => $_POST[$mod["uid"]] == 1]);
					$return->success &= true;
				}
				else
				{
					// If the associated session variable is still unset the setup has failed but why?
					if (!isset($_SESSION[ $MODULE_VARS["uid"] ][ $mod["uid"] ]["useModule"]))
					{
						$return->messages[] = "For some reason at least one of these variables is not set. There may a problem with the installer please contact the programmers with this error message.";
						$return->success &= false;
					}
					else
					{
						$shared_module_info["setSharedModuleInfo"]($MODULE_VARS["uid"], $mod["uid"], ["useModule" => $_SESSION[ $MODULE_VARS["uid"] ][ $mod["uid"] ]["useModule"]]);
					}
				}
			}
			// Ensure that required modules that are not enabled have alternatives set
			foreach ($module_list as $mod)
			{
				//only bother if the module is required and has an alternative
				if (isset($mod["alternative"]))
				{
					//only check if the alternative is being invoked (i.e. module not used)
					if (isset($_SESSION[ $MODULE_VARS["uid"] ][ $mod["uid"] ]["useModule"]) && !$_SESSION[ $MODULE_VARS["uid"] ][ $mod["uid"] ]["useModule"])
					{
						$alternative_vars = array_map(function($key){
							return $key;
						}, array_keys($mod["alternative"]));
						foreach ($alternative_vars as $v) {
							if (!empty($_POST[ "$mod[uid]_$v" ]))
							{
								$_SESSION[ $MODULE_VARS["uid"] ][ $mod["uid"] ][$v] = $_POST[ "$mod[uid]_$v" ];
								$shared_module_info["setSharedModuleInfo"]( $mod["uid"], "alternative", [$v => $_POST[ "$mod[uid]_$v" ]]);
							}
							if (isset($_SESSION[ $MODULE_VARS["uid"] ][ $mod["uid"] ][$v]))
							{
								$shared_module_info["setSharedModuleInfo"]( $mod["uid"], "alternative", [  $v => $_SESSION[ $MODULE_VARS["uid"] ][ $mod["uid"] ][$v]  ]);
							}
							else
							{
								if ($mod["required"])
								{
									$return->yield->messages[] = sprintf(_("You must either enable the module %s or provide alternative details."), $mod["title"]);
									$return->success &= false;
								}
							}
						}
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
	]);
}
