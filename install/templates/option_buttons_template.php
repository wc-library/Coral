<?php
function option_buttons_template($instruction, $buttons, $namespace)
{
	$cards = function($buttons) use ($namespace){
		return join(array_reduce($buttons, function($carry, $item) use ($namespace){
			$custom_js = !empty($item["custom_javascript"]) ? $item["custom_javascript"] : "document.querySelector(\"#{$namespace}_option_button\").value=\"{$item["name"]}\"";
			$carry[] = <<<HEREDOC
			<div class="row">
				<input class="u-full-width" type='submit' value='{$item["title"]}' onclick='$custom_js' />
			</div>
HEREDOC;
			return $carry;
		}));
	};

	return <<<HEREDOC
<form class="pure-form pure-form-aligned">
	<div class="row">
		$instruction
	</div>
	<div class="row">
		&nbsp;
	</div>
	<div class="row">
		<input type="hidden" name='{$namespace}_option_button' id='{$namespace}_option_button' value=false />
	</div>
	{$cards($buttons)}
</fieldset>
</form>
HEREDOC;
}
