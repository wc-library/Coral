<?php

function usage_module_template($usage_title, $fields)
{
	$submit = _("Continue Installing");

	//TODO: This cards stuff should definitely be abstracted
	$cards = function($fields) {
		return join(array_reduce($fields, function($carry, $item){
			if ($item["type"] == "checkbox")
			{
				$default = $item["default_value"] ? "checked" : "";

				$carry[] = <<<HEREDOC
				<div class="card-half">
					<label>{$item["title"]}</label>
					<input type="checkbox" id="{$item["key"]}" name="{$item["key"]}" $default>
					<label for="{$item["key"]}">
						<span class="label-body">{$item["title"]}</span>
					</label>
				</div>
HEREDOC;
			}
			else
			{
				$carry[] = <<<HEREDOC
				<div class="card-half">
					<label for="{$item["key"]}">{$item["title"]}</label>
					<input class="u-full-width" type={$item["type"]} value="{$item["default_value"]}" name="{$item["key"]}">
				</div>
HEREDOC;
			}
			return $carry;
		}));
	};

	return <<<HEREDOC
<form class="pure-form pure-form-aligned">
	<fieldset>
		<div class="row">
			$usage_title
		</div>
		<div class="row">
			{$cards($fields)}
		</div>
		<div class="row">
			<input type="submit" value="$submit" />
		</div>
	</fieldset>
</form>
HEREDOC;
}
