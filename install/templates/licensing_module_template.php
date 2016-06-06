<?php
function licensing_module_template($useTermsToolFunctionality)
{
	$submit = _("Continue Installing");
	$default = $useTermsToolFunctionality["default"] ? "checked" : "";
	return <<<HEREDOC
<form class="pure-form pure-form-aligned">
	<div class="row">
		<input type="checkbox" id="{$useTermsToolFunctionality["name"]}" name="{$useTermsToolFunctionality["name"]}" $default>
		<label for="{$useTermsToolFunctionality["name"]}">
			<span class="label-body">{$useTermsToolFunctionality["label"]}</span>
		</label>
	</div>
	<div class="row">
		<input type="submit" value="$submit" />
	</div>
</fieldset>
</form>
HEREDOC;
}
