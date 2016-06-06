<?php

function organizations_module_template($ldap_title, $ldap_fields)
{
	$submit           = _("Continue Installing");

	$cards = function($ldap_fields) {
		return join(array_reduce($ldap_fields, function($carry, $item){
			$carry[] = <<<HEREDOC
			<div class="card-half">
				<label for="db_{$item["key"]}_name">{$item["title"]}</label>
				<input class="u-full-width" value="{$item["default_value"]}" name="db_{$item["key"]}_name">
			</div>
HEREDOC;
			return $carry;
		}));
	};

	return <<<HEREDOC
<form class="pure-form pure-form-aligned">
	<fieldset>
		<div class="row">
			$ldap_title
		</div>
		<div class="row">
			{$cards($shared_database_info)}
		</div>
		<div class="row">
			<input type="submit" value="$submit" />
		</div>
	</fieldset>
</form>
HEREDOC;
}
