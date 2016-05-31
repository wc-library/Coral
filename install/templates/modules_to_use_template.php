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
			$required = $item["required"] && !isset($item["alternative"]) ? "disabled" : "";
			$carry[] = <<<HEREDOC
			<div class="row">
				<input type="checkbox" id="{$item["uid"]}" name="{$item["uid"]}" checked $required $toggleSection>
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
		<style type="text/css" scoped>
			.spacer {
				height: 10px;
			}
			input[type=checkbox] {
				display: none;
			}
			input[type=checkbox] + label {
				display: inline-block;
				cursor: pointer;
				position: relative;
				padding-left: 25px;
				margin-right: 15px;
			}
			input[type=checkbox] + label {
				margin: 8px 0;
			}
			input[type=checkbox] + label:before,
			input[type=checkbox] + label:after {
				content: "";
				display: inline-block;

				width: 18px;
				height: 18px;

				margin-right: 10px;
				position: absolute;
				left: 0;
				bottom: 3px;
				background-color: #ddd;
				box-shadow: inset 0px 2px 3px 0px rgba(0, 0, 0, .3), 0px 1px 0px 0px rgba(255, 255, 255, .8);
				border-radius: 3px;
				transition: background-color 150ms ease;
			}
			input[type=checkbox] + label:hover:before {
				background-color: #eed;
			}
			input[type=checkbox] + label:after {
				margin-right: 10px;
				position: absolute;
				left: 0;
				content: " ";
				background-image: url("images/checkmark.png");
				background-size: 100%;

				text-align: center;
			    line-height: 15px;
				opacity: 0;
				transition: opacity 150ms ease;
			}
			input[type=checkbox]:checked + label:after {
				opacity: 1;
			}

			input[type=checkbox]:disabled + label:after {
				opacity: 0.4;
			}
			input[type=checkbox]:disabled + label:before,
			input[type=checkbox]:disabled + label:hover:before {
				background-color: #ccc;
			}
		</style>
	</fieldset>
</form>
HEREDOC;
}
