<?php

function text_field_template($field, $instruction = "")
{
	$submit     = _("Continue Installing");
	$field["default_value"] = htmlspecialchars($field["default_value"]);

	return <<<HEREDOC
<form class="pure-form pure-form-aligned">
	<fieldset>
		<div class="twelve columns">
			<div class="row">
				$instruction
			</div>
			<div class="row">
				<div class="six columns">
					<label for="{$field["uid"]}">{$field["title"]}</label>
					<input class="u-full-width" type="text" value="{$field["default_value"]}" name="{$field["uid"]}">
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
