<?php
function licensing_register_installation_requirement()
{
	return [
		"uid" => "licensing_installed",
		"translatable_title" => _("Licensing module installed"),
		"dependencies_array" => [ "usage", "licensing" ],
		"required" => true,
		"getSharedInfo" => function () {
			return [
				"database" => [
					"title" => _("Licensing Database"),
					"default_value" => "coral_licensing"
				]
			];
		},
		"installer" => function($shared_module_info) {
			$return = new stdClass();
			$return->yield = new stdClass();
			$return->success = false;
			$return->yield->title = _("Licensing module installation");
			$return->yield->messages[] = "<b>You broke something</b>";
			return $return;
		}
	];
}
