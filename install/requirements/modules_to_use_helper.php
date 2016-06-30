<?php
/**
 * This module is required because it requires installation of chosen modules
 * It needs modules_to_use to find the chosen modules
 * It sets up those chosen modules as its own dependencies
 */

function register_modules_to_use_helper_requirement()
{
	$PARENT_MODULE = "modules_to_use";

	$MODULE_VARS = [
		"uid" => "modules_to_use_helper",
		"translatable_title" => _("Modules to Use Helper"),
		"dependencies_array" => [ $PARENT_MODULE, "meets_system_requirements" ],
		"hide_from_completion_list" => true,
		"required" => true
	];

	if (isset($_SESSION[$PARENT_MODULE]))
	{
		$dynamic_dependencies = $MODULE_VARS["dependencies_array"];
		foreach ($_SESSION[$PARENT_MODULE]["useModule"] as $key => $val)
		{
			if ($key)
				$dynamic_dependencies[] = $val;
		}
		if (!empty($dynamic_dependencies))
			$MODULE_VARS["dependencies_array"] = $dynamic_dependencies;
	}

	return array_merge( $MODULE_VARS,[
		"installer" => function($shared_module_info) use ($MODULE_VARS) {
			$return = new stdClass();
			$return->success = true;
			return $return;
		}
	]);
}
