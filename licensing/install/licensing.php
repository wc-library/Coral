<?php
function licensing_register_installation_requirement()
{
	return [
		"uid" => "licensing_installed",
		"translatable_title" => _("Licensing module installed"),
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
