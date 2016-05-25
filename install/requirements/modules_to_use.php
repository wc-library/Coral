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
	];
}
