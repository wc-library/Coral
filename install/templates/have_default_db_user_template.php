<?php

function have_default_db_user_template($instruction, $fields)
{
	$submit     = _("Continue Installing");

	//sanitize
	$fields["username"]["default_value"] = htmlspecialchars($fields["username"]["default_value"]);
	$fields["password"]["default_value"] = htmlspecialchars($fields["password"]["default_value"]);

	return <<<HEREDOC
<form class="pure-form pure-form-aligned">
	<fieldset>
		<div class="twelve columns">
			<div class="row">
				$instruction
			</div>
			<div class="row">
				<div class="six columns">
					<label for="{$fields["username"]["uid"]}">{$fields["username"]["title"]}</label>
					<input class="u-full-width" type="text" value="{$fields["username"]["default_value"]}" name="{$fields["username"]["uid"]}">
				</div>
				<div class="six columns">
					<label for="{$fields["password"]["uid"]}">{$fields["password"]["title"]}</label>
					<input class="u-full-width" type="text" value="{$fields["password"]["default_value"]}" name="{$fields["password"]["uid"]}">
				</div>
			</div>
		</span>
		<div class="row">
			<input type="submit" value="$submit" />
		</div>
	</fieldset>
</form>
HEREDOC;
}
