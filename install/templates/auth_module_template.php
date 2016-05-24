<?php

function auth_module_template($ldap_fields, $session_timeout_default)
{
	$submit = _("Continue Installing");

	$session_timeout_title = _("Session Timeout (cookie expiration for logged in users)");
	$use_ldap = _("Use LDAP for authenticaion instead of Auth Module");

	$leave_blank_instruction = _("Leave fields blank if you do not intend to install respective modules.");

	$cards = function($ldap_field_array) {
		return join(array_reduce($ldap_field_array, function($carry, $item){
			$default_value = isset($item["default_value"]) ? "value='" . $item["default_value"] . "'" : "";
			$carry[] = <<<HEREDOC
			<div class="card-half">
				<label for="{$item["key"]}">{$item["title"]}</label>
				<input class="u-full-width" type="{$item["type"]}" name="{$item["key"]}" $default_value>
			</div>
HEREDOC;
			return $carry;
		}));
	};

	return <<<HEREDOC
<form class="pure-form pure-form-aligned">
	<fieldset>
		<div class="row">
			<label for="session_timeout">$session_timeout_title</label>
			<input class="u-full-width" type="text" name="session_timeout" value=$session_timeout_default>
		</div>

		<div class="row">
			<input type="checkbox" id="ldap_enabled" name="ldap_enabled" class="toggleSection" data-toggle-section=".ldapSettings" data-toggle-default="false">
			<label for="ldap_enabled">
				<span class="label-body">$use_ldap</span>
			</label>
		</div>

		<div class="row ldapSettings" style="display: none;">
			{$cards($ldap_fields)}
		</div>

		<div class="row">
			<input type="submit" value="$submit" />
		</div>
		<style type="text/css" scoped>
			.card-half {
				width: 48%;
				float: left;
			}
			.card-half:nth-child(2n+1) {
				margin-right: 2%;
			}
			.card-half:nth-child(2n) {
				margin-left: 2%;
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
