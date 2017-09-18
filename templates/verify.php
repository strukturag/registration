<?php
\OCP\Util::addStyle('registration', 'style');
if (\OCP\Util::getVersion()[0] >= 12) {
	\OCP\Util::addStyle('core', 'guest');
}
\OCP\Util::addScript('registration', 'status');
\OCP\Util::addScript('registration', 'verify');
?>

<div data-token="<?php p($_['token']);?>">

<ul class="success msg hide-unless-success">
	<li>
		<?php p($l->t('Thank you for registering, you should receive a verification link in a few minutes.'));?>
	</li>
</ul>

<ul class="error msg hide-unless-error"></ul>

<form action="#" method="post" class="verify hide-on-success">
	<fieldset>
		<p class="grouptop">
			<input type="email" id="email" name="email" value="<?php p($_['email']);?>" disabled />
			<label for="email" class="infield"><?php p($l->t('Email'));?></label>
			<img class="icon email-icon svg" src="<?php print_unescaped(image_path('', 'actions/mail.svg'));?>" alt=""/>
		</p>

		<p class="groupmiddle">
			<input type="text" id="fullname" name="fullname" value="" placeholder="<?php p($l->t('Full name'));?>" />
			<label for="fullname" class="infield"><?php p($l->t('Full name'));?></label>
			<img class="icon username-icon svg" src="<?php print_unescaped(image_path('', 'actions/user.svg'));?>" alt=""/>
		</p>

		<p class="groupmiddle">
			<input type="text" id="username" name="username" value="" placeholder="<?php p($l->t('Username'));?>" />
			<label for="username" class="infield"><?php p($l->t('Username'));?></label>
			<img class="icon username-icon svg" src="<?php print_unescaped(image_path('', 'actions/user.svg'));?>" alt=""/>
		</p>

		<p class="groupbottom">
			<input type="password" id="password" name="password" placeholder="<?php p($l->t('Password'));?>"/>
			<label for="password" class="infield"><?php p($l->t('Password'));?></label>
			<img class="icon password-icon svg" src="<?php print_unescaped(image_path('', 'actions/password.svg'));?>" alt=""/>
			<input type="checkbox" id="show" name="show" />
			<label style="display: inline;" for="show"></label>
		</p>

		<input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']);?>" />
		<input type="submit" value="<?php p($l->t('Create account'));?>" />
	</fieldset>
</form>

</div>
