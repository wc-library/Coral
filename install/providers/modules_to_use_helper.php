<?php
/**
 * This module is required because it requires installation of chosen modules
 * It needs modules_to_use to find the chosen modules
 * It sets up those chosen modules as its own dependencies
 */

function register_modules_to_use_helper_provider()
{
	$PARENT_MODULE = "modules_to_use";
	return [
		"uid" => "modules_to_use_helper",
		"translatable_title" => _("Modules to Use Helper"),
		"hide_from_completion_list" => true,
		"bundle" => function($version = 0) use ($PARENT_MODULE){

			$dynamic_dependencies = [ $PARENT_MODULE ];
			if (isset($_SESSION[$PARENT_MODULE]))
			{
				foreach ($_SESSION[$PARENT_MODULE]["useModule"] as $key => $val)
				{
					if ($val)
					{
						$dynamic_dependencies[] = $key;
					}
				}
			}

			return [
				"dependencies_array" => $dynamic_dependencies,
				"function" => function($shared_module_info){
					$return = new stdClass();
					$return->success = true;
					return $return;
				}
			];
		}
	];
}
