<?php
function try_again_template()
{
	$submit = _("Try Again");
	return <<<HEREDOC
<form class="pure-form pure-form-aligned">
	<div class="row">
		<input type="button" id="submit" value="$submit" />
	</div>
</fieldset>
</form>
HEREDOC;
}
