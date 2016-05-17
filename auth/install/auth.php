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
			$return->yield = new stdClass();
			$return->success = false;
			$return->yield->title = _("Auth module installation");
			$return->yield->messages[] = "<b>You broke something</b>";
			return $return;
		}
	];
}
