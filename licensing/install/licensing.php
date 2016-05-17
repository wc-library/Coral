<?php
function licensing_register_installation_requirement()
{
	return [
		"uid" => "auth_installed",
		"translatable_title" => _("Auth module installed"),
		"dependencies_array" => [ "usage", "licensing" ],
		"required" => true,
		"installer" => function() {
			$return = new stdClass();
			$return->success = true;
			$return->yield = new stdClass();
			return $return;
		}
	];
}
