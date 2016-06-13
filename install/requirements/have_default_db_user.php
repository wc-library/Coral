<?php
function register_have_default_db_user_requirement()
{
	return [
		"uid" => "have_default_db_user",
		"translatable_title" => _("Default Database User Configured"),
		"dependencies_array" => ["have_database_access"],
		"hide_from_completion_list" => true,
		"required" => true,
		"installer" => function($shared_module_info) {
			$return = new stdClass();
			$return->yield = new stdClass();
			$return->success = false;

			$user     = "coral_regular_user";
			// $password = generate_password();
			//TODO: Handle individually assinged username/passwords
			// foreach ($listofDatabases as $dbname)
			// {
			// 	// "GRANT SELECT, INSERT, UPDATE, DELETE ON $dbname TO $user@{Config::dbInfo('host')} IDENTIFIED BY '$password'";
			// }

			$return->yield->messages[] = "incomplete installer";
			$return->yield->title = _("Have default user");
			return $return;
		}
	];
}
