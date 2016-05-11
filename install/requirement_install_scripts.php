<?php
class Installer {

	/* This is the order of installation (important for modules) */
	protected $checkList = [
		"meets_system_requirements",
		"have_available_modules",
		"have_database_access",
		"databases_created",
		"have_default_user"
	];

	public function getCheckList()
	{
		return $checkList;
	}
	public function test($test)
	{
		if (!in_array($test, $checkList))
			throw new OutOfBoundsException("Test '$test' not found in checklist.", 100);

		call_user_func($test);
	}

	private function meets_system_requirements()
	{
		$return = new stdClass();
		$return->yield = new stdClass();

		$return->success = true;
		$return->yield->title = _("Meets system requirements");

		$php_version = strtok(phpversion(),'.');
		if ($php_version != 5)
		{
			$return->yield->messages[] = _("PHP 5 is required for CORAL");
			$return->success = false;
		}
		return $return;
	}

	private function have_database_access()
	{
		$return = new stdClass();
		$return->yield = new stdClass();

		if (isset($_POST["dbusername"]))
		{
			// Form has just been submitted
			$_SESSION["dbusername"] = $_POST["dbusername"];
			$_SESSION["dbpassword"] = $_POST["dbpassword"];
			$_SESSION["dbhost"] = $_POST["dbhost"];
		}

		//try connection
		// NEED TO THINK ABOUT HOW THE REST OF THIS THING IS GOING TO HANDLE DB ACCESS - MAY AS WELL START HERE
		$dbconnection = @mysqli_connect($_SESSION["dbhost"], $_SESSION["dbusername"], $_SESSION["dbpassword"]);
		if (!$this->db) {

			$this->error = mysql_error();
			if (!$this->error) {
				$this->error = "Access denied for user '$username'";
			}
		} else {
			$databaseName = $this->config->database->name;
			mysql_select_db($databaseName, $this->db);
			$this->error = mysql_error($this->db);
		}

		if ($this->error) {
			$this->statusNotes['database_connection'] = "Database connection failed: ".$this->error;
			$this->db = null;
		} else {
			$this->statusNotes['database_connection'] = "Database connection successful";
		}


		require "install/templates/database_details.php";
		$return->yield->body = database_details();
		$return->yield->messages[] = _("To begin with, we need a username and password create the databases CORAL and its modules will be using.");

		$return->success = false;
		return $return;
	}
	private function databases_created()
	{
		$return = new stdClass();
		$return->success = true;
		return $return;
	}

	//i.e. user with just select insert etc. on the dbs
	private function have_default_user()
	{
		// -> need to support multiple users here
		$return = new stdClass();
		$return->success = true;
		return $return;
	}


	/* Lets keep this the order of module installation */
	private function have_installed_organizations()
	{
		$return = new stdClass();
		$return->success = true;
		return $return;
	}
	private function have_installed_auth()
	{
		$return = new stdClass();
		$return->success = true;
		return $return;
	}
	private function have_installed_management()
	{
		$return = new stdClass();
		$return->success = true;
		return $return;
	}
	private function have_installed_licensing()
	{
		$return = new stdClass();
		$return->success = true;
		return $return;
	}
	private function have_installed_reports()
	{
		$return = new stdClass();
		$return->success = true;
		return $return;
	}
	private function have_installed_usage()
	{
		$return = new stdClass();
		$return->success = true;
		return $return;
	}
	private function have_installed_resources()
	{
		$return = new stdClass();
		$return->success = true;
		return $return;
	}

	private function successful_install()
	{
		$return = new stdClass();
		$return->success = true;
		return $return;
	}
}
