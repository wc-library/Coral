<?php

function database_details_template($instruction, $db_access_vars, $shared_database_info)
{
	$submit     = _("Continue Installing");

	$cards = function($shared_database_info) {
		$card_vomit = join(array_reduce($shared_database_info, function($carry, $item){
			$carry[] = <<<HEREDOC
			<div class="card-half">
				<label for="{$item["name"]}">{$item["title"]}</label>
				<input class="u-full-width" type="text" placeholder="{$item["default_value"]}" value="{$item["default_value"]}" name="{$item["name"]}">
			</div>
HEREDOC;
			return $carry;
		}, []));

		if (!empty($card_vomit))
		{
			$card_vomit = <<<HEREDOC
				<div class="twelve columns">
					<a href="#" class="toggleSection" data-alternate-message="hide advanced" data-toggle-section=".advancedSection" data-toggle-default="false">show advanced</a>
				</div>
				<span class="advancedSection twelve columns">
					{$card_vomit}
				</span>
HEREDOC;
		}
		return $card_vomit;
	};

	$db_host_field = "";
	if (!empty($db_access_vars["host"]))
	{
		$db_host_field = <<<HEREDOC
			<div class="row">
				<div class="twelve columns">
					<label for="dbhost">{$db_access_vars["host"]["title"]}</label>
					<input class="u-full-width" type="text" value="{$db_access_vars["host"]["default"]}" placeholder="{$db_access_vars["host"]["placeholder"]}" name="{$db_access_vars["host"]["name"]}">
				</div>
			</div>
HEREDOC;
	}

	return <<<HEREDOC
<div class="row">
	$instruction
</div>
<form class="pure-form pure-form-aligned">
	<fieldset>
		<div class="row">
			<div class="six columns">
				<label for="dbusername">{$db_access_vars["username"]["title"]}</label>
				<input class="u-full-width" type="text" value="{$db_access_vars["username"]["default"]}" placeholder="{$db_access_vars["username"]["placeholder"]}" name="{$db_access_vars["username"]["name"]}">
			</div>

			<div class="six columns">
				<label for="dbpassword">{$db_access_vars["password"]["title"]}</label>
				<input class="u-full-width" type="password" value="{$db_access_vars["password"]["default"]}" placeholder="{$db_access_vars["password"]["placeholder"]}" name="{$db_access_vars["password"]["name"]}">
			</div>
		</div>
		{$db_host_field}
		{$cards($shared_database_info)}
		<div class="row">
			<input type="submit" value="$submit" />
		</div>
	</fieldset>
</form>
HEREDOC;
}
