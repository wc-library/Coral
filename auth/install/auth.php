<?php
Installer.register_installation_requirement ([
	"translatable_title" => _("Have installed auth module"),
	"dependencies_array" => ["usage", "licensing"],
	"required" => true,
	"installer" => function() {
		$return = new stdClass();
		$return->success = true;
		return $return;
	}
]);
