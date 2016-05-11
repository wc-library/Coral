<?php
$installer = function have_installed_resources() {
	$return = new stdClass();
	$return->success = true;
	return $return;
};

register_installer(
	"auth",
	["usage", "licensing"],
	"have_installed_auth",
	$installer
);
