<?php
function register_meets_system_requirements_provider()
{
	return [
		"uid" => "meets_system_requirements",
		"translatable_title" => _("Meets system requirements"),
		"bundle" => function($version = 0) {
			return [
				"function" => function($shared_module_info) {
					$return = new stdClass();
					$return->yield = new stdClass();

					$return->yield->title = _("System Requirements");

					/**
					 *  PHP_MAJOR_VERSION is only defined from 5.2.7 onwards but
					 *  we are past 5.2.7's end of life (so if this test fails,
					 *  that's okay because PHP needs to be updated anyway).
					 */
					if (version_compare(PHP_VERSION, '5.5.0', '>='))
					{
						$return->success = true;
					}
					else if (version_compare(PHP_VERSION, '5.0.0', '>='))
					{
						$return->yield->messages[] = _("Although you have PHP 5 installed, to install CORAL you will need to update your version of PHP to at least version 5.5 (the latest version of 5.6.x is recommended).");
						$return->success = false;
					}
					else
					{
						$return->yield->messages[] = _("PHP 5.5 or greater is required for CORAL (the latest version of 5.6.x is recommended).");
						$return->success = false;
					}
					return $return;
				}
			];
		}
	];
}
