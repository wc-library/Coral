<?php
function register_some_kind_of_auth_provider()
{
	$dynamic_dependencies = ["modules_to_use"];
	if (isset($_SESSION["modules_to_use"]["useModule"]["auth"]) && $_SESSION["modules_to_use"]["useModule"]["auth"] != true)
	{
		$dynamic_dependencies[] = "remote_auth";
	}
	else
	{
		$dynamic_dependencies[] = "auth";
	}

	return [
		"uid" => "some_kind_of_auth",
		"translatable_title" => _("Some Kind of Auth"),
		"hide_from_completion_list" => true,
		"bundle" => function($version = 0) use ($dynamic_dependencies) {
			return [
				"dependencies_array" => $dynamic_dependencies,
				"function" => function($shared_module_info) {
					$return = new stdClass();
					$return->success = true;
					return $return;
				}
			];
		}
	];
}
