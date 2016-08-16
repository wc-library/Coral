<?php

function modules_to_use_template($module_list, $instruction)
{
	$submit     = _("Continue Installing");

	$cards = function($item_array) {
		return join(array_reduce($item_array, function($carry, $item){
			$default = isset($item["default_value"]) ? $item["default_value"] : true;
			$select_and_enable = $default ? "checked" : "";
			$carry[] = <<<HEREDOC
			<div class="row">
				<input type="checkbox" id="{$item["uid"]}" name="{$item["uid"]}" $select_and_enable>
				<label for="{$item["uid"]}">
					<span class="label-body">{$item["title"]}</span>
				</label>
			</div>
HEREDOC;
			return $carry;
		}));
	};

	return <<<HEREDOC
<form class="pure-form pure-form-aligned">
	<fieldset>
		<div class="row">
			$instruction
		</div>
		<div class="row">
			{$cards($module_list)}
		</div>
		<div class="row spacer"></div>
		<div class="row">
			<input type="submit" value="$submit" />
		</div>
	</fieldset>
</form>
HEREDOC;
}
