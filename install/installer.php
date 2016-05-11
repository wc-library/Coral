<?php
class Installer {

	protected $checkList;

	function __construct() {
		$checkList = [
			[
				"translatable_title" => _("Meets system requirements"),
				"dependencies_array" => [],
				"required" => true,
				"installer" => function() {
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
			],[
				"translatable_title" => _("have_available_modules"),
				"dependencies_array" => [],
				"required" => true,
				"installer" => function() {

				}
			],[
				"translatable_title" => _("have_database_access"),
				"dependencies_array" => [],
				"required" => true,
				"installer" => function() {

				}
			],[
				"translatable_title" => _("databases_created"),
				"dependencies_array" => [],
				"required" => true,
				"installer" => function() {

				}
			],[
				"translatable_title" => _("have_default_user"),
				"dependencies_array" => [],
				"required" => true,
				"installer" => function() {

				}
			],
		];
	}
	public function getCheckList()
	{
		return $checkList;
	}

	/**
	 *
	 * @param  [type] $installer_array
	 *                    $translatable_title
	 *                    $dependencies_array
	 *                    $required
	 *                    $installation_callback
	 * @return [type]
	 */
	public function register_installation_requirement($installer_object)
	{
		$checkList[] = [$translatable_title => $installation_callback];
		//sort according to dependencies_array
		//
	}

	public function test($test)
	{
		if (!in_array($test, $checkList))
			throw new OutOfBoundsException("Test '$test' not found in checklist.", 100);

		call_user_func($test);
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

	private function successful_install()
	{
		$return = new stdClass();
		$return->success = true;
		return $return;
	}
}
