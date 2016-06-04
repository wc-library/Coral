<?php
function register_modules_to_use_helper_requirement()
{
	$PARENT_MODULE = "modules_to_use";

	$MODULE_VARS = [
		"uid" => "modules_to_use_helper",
		"translatable_title" => _("Dependencies Integrated"),
		"dependencies_array" => [ $PARENT_MODULE ],
		"required" => true
	];

	if (isset($_SESSION[$PARENT_MODULE]))
	{
		$dynamic_dependencies = $MODULE_VARS["dependencies_array"];
		foreach ($_SESSION[$PARENT_MODULE] as $key => $val)
		{
			if (isset($val["useModule"]) && $val["useModule"])
			{
				$dynamic_dependencies[] = $key;
			}
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
