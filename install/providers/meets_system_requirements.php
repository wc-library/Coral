<?php
function register_meets_system_requirements_requirement()
{
	return [
		"uid" => "meets_system_requirements",
		"translatable_title" => _("Meets system requirements"),
		"installer" => function($shared_module_info) {
			$return = new stdClass();
			$return->yield = new stdClass();

			$return->success = true;
			$return->yield->title = _("System Requirements");

			/**
			 *  PHP_MAJOR_VERSION is only defined from 5.2.7 onwards but
			 *  we are past 5.2.7's end of life (so if this test fails,
			 *  that's okay because PHP needs to be updated anyway).
			 */
			if (defined('PHP_MAJOR_VERSION') && PHP_MAJOR_VERSION >= 5 && PHP_MINOR_VERSION >= 4)
			{
				if (PHP_MAJOR_VERSION > 5)
				{
					$return->yield->messages[] = sprintf( _("PHP is required for CORAL but you have version %s. CORAL will install anyway but may not function correctly."), PHP_MAJOR_VERSION );
				}
			}
			else
			{
				$return->yield->messages[] = _("PHP 5.4 or greater is required for CORAL");
				$return->success = false;
			}
			return $return;
		}
	];
}
