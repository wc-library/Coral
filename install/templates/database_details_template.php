<?php

function database_details_template($db_access_vars, $shared_database_info)
{
	$submit     = _("Continue Installing");

	$cards = function($shared_database_info) {
		return join(array_reduce($shared_database_info, function($carry, $item){
			$carry[] = <<<HEREDOC
			<div class="card-half">
				<label for="{$item["name"]}">{$item["title"]}</label>
				<input class="u-full-width" type="text" placeholder="{$item["default_value"]}" value="{$item["default_value"]}" name="{$item["name"]}">
			</div>
HEREDOC;
			return $carry;
		}));
	};

	return <<<HEREDOC
<form class="pure-form pure-form-aligned">
	<fieldset>
		<div class="row">
			<div class="six columns">
				<label for="dbusername">{$db_access_vars["username"]["title"]}</label>
				<input class="u-full-width" type="text" placeholder="{$db_access_vars["username"]["placeholder"]}" name="{$db_access_vars["username"]["name"]}">
			</div>

			<div class="six columns">
				<label for="dbpassword">{$db_access_vars["password"]["title"]}</label>
				<input class="u-full-width" type="password" placeholder="{$db_access_vars["password"]["placeholder"]}" name="{$db_access_vars["password"]["name"]}">
			</div>
		</div>
		<div class="row">
			<div class="twelve columns">
				<label for="dbhost">{$db_access_vars["host"]["title"]}</label>
				<input class="u-full-width" type="text" placeholder="{$db_access_vars["host"]["placeholder"]}" name="{$db_access_vars["host"]["name"]}">
			</div>
		</div>

		<div class="twelve columns">
			<a href="#" class="toggleSection" data-alternate-message="hide advanced" data-toggle-section=".advancedSection" data-toggle-default="false">show advanced</a>
		</div>
		<span class="advancedSection">
			<div class="row">
				{$cards($shared_database_info)}
			</div>
		</span>
		<div class="row">
			<input type="submit" value="$submit" />
		</div>
	</fieldset>
</form>
HEREDOC;
}
