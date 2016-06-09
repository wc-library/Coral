<?php

function modules_to_use_template($module_list)
{
	$submit     = _("Continue Installing");

	$leave_blank_instruction = _("Leave fields blank if you do not intend to install respective modules.");

	$cards = function($item_array) {
		return join(array_reduce($item_array, function($carry, $item){
			$altField = "";
			$toggleSection = "";
			if (isset($item["alternative"]))
			{
				foreach ($item["alternative"] as $key => $value) {
					$toggleSection = " class=\"toggleSection\" data-toggle-section=\".{$key}Alternative\" data-toggle-invert=\"true\" data-toggle-default=\"false\"";
					$altField = <<<HEREDOC
					<div class="row {$key}Alternative" style="display: none;">
						<label for="{$item["uid"]}_{$key}">{$value}</label>
						<input class="u-full-width" type="text" name="{$item["uid"]}_{$key}">
					</div>
HEREDOC;
				}
			}
			$default = isset($item["default_value"]) ? $item["default_value"] : true;
			$select_and_enable = $default ? "checked" : "";
			$select_and_enable = $item["required"] && !isset($item["alternative"]) ? "checked disabled" : $select_and_enable;
			$carry[] = <<<HEREDOC
			<div class="row">
				<input type="checkbox" id="{$item["uid"]}" name="{$item["uid"]}" $select_and_enable $toggleSection>
				<label for="{$item["uid"]}">
					<span class="label-body">{$item["title"]}</span>
				</label>
			</div>$altField
HEREDOC;
			return $carry;
		}));
	};

	return <<<HEREDOC
<form class="pure-form pure-form-aligned">
	<fieldset>

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
