<?php

function database_details()
{
	$dbusername = _("Database Username");
	$dbpassword = _("Database Password");
	$uname = _("Username");
	$pass = _("Password");
	$submit = _("Start Installing");
	return <<<HEREDOC
<form class="pure-form pure-form-aligned">
	<fieldset>
		<div class="six columns">
			<label for="dbusername">$dbusername</label>
			<input class="u-full-width" type="text" placeholder="$username" name="dbusername">
		</div>

		<div class="six columns">
			<label for="dbpassword">$dbpassword</label>
			<input class="u-full-width" type="password" placeholder="$password" name="dbpassword">
		</div>

		<button id="submit">$submit</button>
	</fieldset>
</form>
HEREDOC;
}
