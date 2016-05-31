<?php
function register_modules_to_use_requirement()
{
	return [
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
			foreach ($module_list as $mod)
			{
				// We can only auto-set if there is no alternative and mod is required
				if ($mod["required"] && !isset($mod["alternative"]))
				{
					if (!isset($_SESSION["modules_to_use"][ $mod["uid"] ]))
						$_SESSION["modules_to_use"][ $mod["uid"] ] = [];
					$_SESSION["modules_to_use"][ $mod["uid"] ]["useModule"] = true;
					$return->success &= true;
				}
				else if (isset($_POST[$mod["uid"]]))
				{
					$_SESSION["modules_to_use"][ $mod["uid"] ]["useModule"] = $_POST[$mod["uid"]] == 1;
					$return->success &= true;
				}
				else
				{
					// If the associated session variable is still unset the setup has failed but why?
					if (!isset($_SESSION["modules_to_use"][ $mod["uid"] ]["useModule"]))
					{
						$return->messages[] = "For some reason at least one of these variables is not set. There may a problem with the installer please contact the programmers with this error message.";
						$return->success &= false;
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
					if (isset($_SESSION["modules_to_use"][ $mod["uid"] ]["useModule"]) && !$_SESSION["modules_to_use"][ $mod["uid"] ]["useModule"])
					{
						$alternative_vars = array_map(function($key){
							return $key;
						}, array_keys($mod["alternative"]));
						foreach ($alternative_vars as $v) {
							if (!empty($_POST[ "$mod[uid]_$v" ]))
							{
								$_SESSION["modules_to_use"][ $mod["uid"] ][$v] = $_POST[ "$mod[uid]_$v" ];
							}
							if (!isset($_SESSION["modules_to_use"][ $mod["uid"] ][$v]))
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
	];
}
