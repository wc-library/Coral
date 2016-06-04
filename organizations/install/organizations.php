<?php
function register_organizations_requirement()
{
	$MODULE_VARS = [
		"uid" => "organizations",
		"translatable_title" => _("Organizations Module"),
		"dependencies_array" => [],
		"getSharedInfo" => function () {
			return [
				"database" => [
					"title" => _("Organizations Database"),
					"default_value" => "coral_organizations"
				],
				"config_file" => [
					"path" => "auth/admin/configuration.ini",
				]
			];
		}
	];
	return array_merge( $MODULE_VARS, [
		"installer" => function($shared_module_info) use ($MODULE_VARS) {
			$return = new stdClass();
			$return->yield = new stdClass();
			$return->success = false;
			$return->yield->title = _("Organizations Module");
			$return->yield->messages[] = "<b>Installer Incomplete</b>";


			$this_db_name = $shared_module_info[ $MODULE_VARS["uid"] ]["db_name"];
			$dbconnection = $shared_module_info["provided"]["get_db_connection"]( $this_db_name );


			return $return;
		}
	]);
}
