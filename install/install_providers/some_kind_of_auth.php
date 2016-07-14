<?php
function register_some_kind_of_auth_provider()
{
	$MODULE_VARS = [
		"uid" => "some_kind_of_auth",
		"translatable_title" => _("Some Kind of Auth"),
		"hide_from_completion_list" => true,
	];

	$dynamic_dependencies = ["modules_to_use"];
	if (isset($_SESSION["modules_to_use"]["useModule"]["auth"]) && $_SESSION["modules_to_use"]["useModule"]["auth"] != true)
	{
		$dynamic_dependencies[] = "remote_auth";
	}
	else
	{
		$dynamic_dependencies[] = "auth";
	}

	return array_merge( $MODULE_VARS,[
		"bundle" => function($version = 0) {
			return [
				"dependencies_array" => $dynamic_dependencies,
				"function" => function($shared_module_info) {
					$return = new stdClass();
					$return->success = true;
					return $return;
				}
			];
		}
	]);
}
