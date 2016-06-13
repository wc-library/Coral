<?php
function have_default_coral_admin_user_template($instruction, $field)
{
	$submit = _("Continue Installing");
	return <<<HEREDOC
<form class="pure-form pure-form-aligned">
	<div class="row">
		$instruction
	</div>
	<div class="row">
		<label for="{$field["uid"]}">{$field["title"]}</label>
		<input class="u-full-width" type="{$field["type"]}" name="{$field["uid"]}" value="{$field["default_value"]}">
	</div>
	<div class="row">
		<input type="submit" value="$submit" />
	</div>
</fieldset>
</form>
HEREDOC;
}
