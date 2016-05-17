<?php
function auth_register_installation_requirement()
{
	return [
		"uid" => "auth_installed",
		"translatable_title" => _("Auth module installed"),
		"dependencies_array" => [ "usage", "licensing" ],
		"required" => true,
		"installer" => function() {
			$return = new stdClass();
			$return->success = false;
			$return->messages[] = Config::dbInfo("all");
			$return->title = "Auth module installation";
			return $return;
		}
	];
}
