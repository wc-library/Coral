<?php
function register_some_kind_of_auth_provider()
{
	$MODULE_VARS = [
		"uid" => "some_kind_of_auth",
		"translatable_title" => _("Some Kind of Auth"),
		"dependencies_array" => [ "modules_to_use" ],
		"hide_from_completion_list" => true,
	];

	if (isset($_SESSION["modules_to_use"]["useModule"]["auth"]) && $_SESSION["modules_to_use"]["useModule"]["auth"] != true)
	{
		$MODULE_VARS["dependencies_array"][] = "remote_auth";
	}
	else
	{
		$MODULE_VARS["dependencies_array"][] = "auth";
	}

	return array_merge( $MODULE_VARS,[
		"installer" => function($shared_module_info) use ($MODULE_VARS) {
			$return = new stdClass();
			$return->success = true;
			return $return;
		}
	]);
}
