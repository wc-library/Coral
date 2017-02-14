<?php

function auth_module_template($session_timeout_default, $ldap_enabled_default, $ldap_fields)
{
	$submit = _("Continue Installing");

	$session_timeout_title = _("Session Timeout (cookie expiration for logged in users)");
	$use_ldap = _("Use LDAP for authentication instead of Auth Module");
	$ldap_enabled_default = $ldap_enabled_default ? "true" : "false";

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
			<input type="checkbox" id="ldap_enabled" name="ldap_enabled" class="toggleSection" data-toggle-section=".ldapSettings" data-toggle-default="$ldap_enabled_default">
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
	</fieldset>
</form>
HEREDOC;
}
