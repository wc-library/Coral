<?php
function register_have_default_user_requirement()
{
	return [
		"uid" => "have_default_user",
		"translatable_title" => _("Have default user"),
		"dependencies_array" => ["have_database_access"],
		"required" => true,
		"installer" => function($shared_module_info) {
			$return = new stdClass();
			$return->yield = new stdClass();

			$user     = "coral_regular_user";
			// $password = generate_password();
			//TODO: Handle individually assinged username/passwords
			// foreach ($listofDatabases as $dbname)
			// {
			// 	// "GRANT SELECT, INSERT, UPDATE, DELETE ON $dbname TO $user@{Config::dbInfo('host')} IDENTIFIED BY '$password'";
			// }

			//TODO: don't just return true...
			$return->success = true;
			$return->yield->messages[] = "incomplete installer";
			$return->yield->title = _("Have default user");
			return $return;
		}
	];
}
