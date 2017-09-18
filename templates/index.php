<?php
\OCP\Util::addStyle('registration', 'style');
if (\OCP\Util::getVersion()[0] >= 12) {
	\OCP\Util::addStyle('core', 'guest');
}
\OCP\Util::addScript('registration', 'status');
\OCP\Util::addScript('registration', 'index');
?>

<div>

<ul class="success msg hide-unless-success">
	<li>
		<?php p($l->t('Thank you for registering, you should receive a verification link in a few minutes.'));?>
	</li>
</ul>

<ul class="error msg hide-unless-error"></ul>

<form action="#" method="post" class="register hide-on-success">
	<fieldset>
		<p class="groupofone">
			<input type="email" id="email" name="email" placeholder="<?php p($l->t('Email'));?>" required autofocus />
			<label for="email" class="infield"><?php p($l->t('Email'));?></label>
			<img class="icon email-icon svg" src="<?php print_unescaped(image_path('', 'actions/mail.svg'));?>" alt=""/>
		</p>

		<input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']);?>" />
		<input type="submit" value="<?php p($l->t('Request verification link'));?>" />
	</fieldset>
</form>

</div>
