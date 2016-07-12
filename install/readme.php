<?php

/**
 * Installers have a single function (although this is not a rule) and its name
 * is register_{filename-without-extension}_requirement
 *
 * They return an associative array with at least the following elements:
 *  - uid					-> String: Unique identifier for this installer.
 *  - translatable_title	-> String: Title (that supports translation) that
 *  							users will understand.
 *  - installer				-> Function: Will be passed $shared_module_info
 *  							parameter and a well formed return variable is
 *  							expected (see below).
 *
 * In addition, optional elements include:
 *  - required					-> Bool: Whether or not the installer must run
 *  								for the installation to complete.
 *  - dependencies_array		-> Array (String): Uids of installers that must
 *  								run before this installer can run.
 *  - hide_from_completion_list	-> Bool: Whether or not the list of completed
 *  								installers should include this title (the
 *  								list is for the end user).
 *  - post_installation			-> Bool: Whether or not this installer should
 *  								only be run after installation is complete
 *  								(to do cleanup, for example). Useful to set
 *  								within a module installer to ensure that
 *  								anything that should be cleaned up is done
 *  								(e.g. config files are not writable).
 *  - shared_info				-> Assoc Array: Information to provide on the
 *  								$shared_module_info variable that will be
 *  								accessible to other installers before this
 *  								installer runs (such as request for a
 *  								database or a config file).
 *  - alternative				-> Assoc Array: Specifically for modules_to_use
 *  								installer - allows simple alternatives to
 *  								`required: true` installers that
 *  								modules_to_use can coordinate. Note, if this
 *  								value is set, the required value is handled
 *  								differently.
 */
function register_readme_requirement()
{
	/**
	 * Sometimes its useful to use $MODULE_VARS inside the installer function so
	 * a recommended approach is to set them here and then merge them together
	 * at the return, passing them in to the installer with "use".
	 */
	$MODULE_VARS = [
		// Normally the uid is the same as the filename.
		// Uids use lowercase alphabet and underscores
		// (nothing beyond that is tested).
		"uid" => "readme",
		"translatable_title" => _("Readme"),
	];

	// Note that we are merging the required variables with the installer here.
	return array_merge( $MODULE_VARS, [
		/**
		 * Note that we are passing in $MODULE_VARS with `use` which, for
		 * example, allows the installer to know its uid using:
		 * $MODULE_VARS["uid"]
		 */
		"installer" => function($shared_module_info) use ($MODULE_VARS) {
			// The simplest possible return variable:
			$return = new stdClass();
			$return->success = true;
			return $return;

			/**
			 *  It is the installer's responsibility to help the user pass
			 *  through to the next requirement. This function will be passed
			 *  through every time the user tries to progress and the process
			 *  gets this far. If necessary, therefore, session variables should
			 *  be used to keep track of the user's decisions.
			 */

			// Sometimes there is nothing that can be done but to fail
			$return->success = false;
			// If so, set a title and a message to explain what went wrong
			$return->yield = new stdClass();
			$return->yield->title = _("Readme could not complete");
			$return->yield->messages = [];
			// Each element in the array will be prepended to the body
			$return->yield->messages[] = _("These messages have red boxes and so can look like error messages - use them wisely");
			$return->yield->messages[] = _("Nothing went wrong here, it's a conspiracy to prevent you from installing");
			$return->yield->messages[] = sprintf(_("Use sprintnf to get substitute data (like the php version: %s) into translatable strings"), phpversion());
			/**
			 *  Maybe it's something that the user can resolve in the background.
			 *  There are a few templates (and you can make your own) this one
			 *  will simply give a "Try Again" button so that the user doesn't
			 *  have to hit refresh and feel like something's gone wrong.
			 */
			// Include templates using require_once
 			require_once "install/templates/try_again_template.php";
			// Template files have a single function called
			// {filename-without-extension}_template
			// You don't need to follow this convention but please do...
 			$return->yield->body = try_again_template();
			return $return;

			/**
			 * Let's try a template that allows user interaction.
			 */
			$return->success = false;
			require_once "install/templates/option_buttons_template.php";
			/**
			 * option_buttons_template takes three parameters:
			 *  - instruction	-> String: A string to be displayed explaining
			 *  					the choices to the user.
			 *  - buttons		-> Array (Array): Each button has a "name" and
			 *  					"title" element. The title is what the user
			 *  					will read, the name will be the return value
			 *  					if the user chooses it.
			 *  - namespace		-> The user's selection will be posted back. To
			 *  					avoid mixing the values posted to different
			 *  					installers that use option_buttons_template
			 *  					a unique namespace is required. The return
			 *  					value will be in:
			 *  					`$_POST[$namespace . "_option_button"]`.
			 */

			$instruction = _("What is the air-speed velocity of an unladen swallow?");
			$namespace = "readme";
			$option_buttons = [
				[ "name" => "ten_kmph",			"title" => _("10km/h") ],
				[ "name" => "ten_mph",			"title" => _("10mph") ],
				[ "name" => "thats_not_fair",	"title" => _("That's not fair") ],
				[ "name" => "what_do_you_mean",	"title" => _("What do you mean, African or European?") ]
			];
			//Before returning we should check whether the user has already answered the question
			if (isset($_POST[$namespace . "_option_button"]))
			{
				if ($_POST[$namespace . "_option_button"] == "what_do_you_mean")
				{
					// Only succeed if the user picks the what_do_you_mean option.
					$return->success = true;
					return $return;
				}
			}

			if (isset($_SESSION[$namespace . "_option_button"]))
			{
				$return->messages[] = _("Okay, there's only one right answer to this question so just try again...");
			}
			$return->success = false;
			$return->yield->body = option_buttons_template($instruction, $option_buttons, $namespace);
			return $return;
		}
	]);
}
