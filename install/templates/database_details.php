<?php

function database_details()
{
	$dbusername = _("Database Username");
	$dbpassword = _("Database Password");
	$dbhost     = _("Database Host");
	$username   = isset($_SESSION["POSTDATA"]["dbusername"]) ? $_SESSION["POSTDATA"]["dbusername"] : _("Username");
	$password   = isset($_SESSION["POSTDATA"]["dbpassword"]) ? _("leave blank to leave unchanged") : _("Password");
	$host       = isset($_SESSION["POSTDATA"]["dbhost"]) ? $_SESSION["POSTDATA"]["dbhost"] : _("Hostname");
	$submit     = _("Start Installing");

	$database_name = _("Database Name");
	$auth_database_name = _("Auth Database Name");
	$licensing_database_name = _("Licensing Database Name");
	$management_database_name = _("Management Database Name");
	$organizations_database_name = _("Organizations Database Name");
	$reports_database_name = _("Reports Database Name");
	$resources_database_name = _("Resources Database Name");
	$usage_database_name = _("Usage Database Name");

	$leave_blank_instruction = _("Leave fields blank if you do not intend to install respective modules.");

	return <<<HEREDOC
<form class="pure-form pure-form-aligned">
	<fieldset>
		<div class="row">
			<div class="six columns">
				<label for="dbusername">$dbusername</label>
				<input class="u-full-width" type="text" placeholder="$username" name="dbusername">
			</div>

			<div class="six columns">
				<label for="dbpassword">$dbpassword</label>
				<input class="u-full-width" type="password" placeholder="$password" name="dbpassword">
			</div>
		</div>
		<div class="row">
			<div class="twelve columns">
				<label for="dbhost">$dbhost</label>
				<input class="u-full-width" type="text" placeholder="$host" name="dbhost">
			</div>
		</div>

		<div class="twelve columns">
			<a href="#" class="toggleSection" data-alternate-message="hide advanced" data-toggle-section=".advancedSection">show advanced</a>
		</div>
		<div class="advancedSection" style="display: none;">
			<div class="row">
				$leave_blank_instruction
			</div>
			<div class="row">
				<div class="six columns">
					<label for="dbauth">$auth_database_name</label>
					<input class="u-full-width" type="text" placeholder="$database_name" name="dbauth">
				</div>
				<div class="six columns">
					<label for="dborganizations">$organizations_database_name</label>
					<input class="u-full-width" type="text" placeholder="$database_name" name="dborganizations">
				</div>
			</div>
			<div class="row">
				<div class="six columns">
					<label for="dbmanagement">$management_database_name</label>
					<input class="u-full-width" type="text" placeholder="$database_name" name="dbmanagement">
				</div>
				<div class="six columns">
					<label for="dblicensing">$licensing_database_name</label>
					<input class="u-full-width" type="text" placeholder="$database_name" name="dblicensing">
				</div>
			</div>
			<div class="row">
				<div class="six columns">
					<label for="dbreports">$reports_database_name</label>
					<input class="u-full-width" type="text" placeholder="$database_name" name="dbreports">
				</div>
				<div class="six columns">
					<label for="dbresources">$resources_database_name</label>
					<input class="u-full-width" type="text" placeholder="$database_name" name="dbresources">
				</div>
			</div>
			<div class="row">
				<div class="six columns">
					<label for="dbusage">$usage_database_name</label>
					<input class="u-full-width" type="text" placeholder="$database_name" name="dbusage">
				</div>
			</div>
		</div>
		<div class="row">
			<input type="button" id="submit" value="$submit" />
		</div>
	</fieldset>
</form>
HEREDOC;
}
