<?php
\OCP\Util::addStyle('registration', 'style');
if (\OCP\Util::getVersion()[0] >= 12) {
	\OCP\Util::addStyle('core', 'guest');
}

function getEnteredData($_, $key) {
	if (!isset($_['entered_data'][$key])) {
		return '';
	}
	return $_['entered_data'][$key];
}

?><form action="<?php print_unescaped(\OC::$server->getURLGenerator()->linkToRoute('registration.register.createAccount', array('token' => $_['token'])))?>" method="post">
	<input type="hidden" name="requesttoken" value="<?php p($_['requesttoken'])?>" />
	<fieldset>
		<?php if (isset($_['errormsgs'])) {
	?>
		<ul class="error">
			<?php foreach ($_['errormsgs'] as $errormsg) {
		echo "<li>$errormsg</li>";
	}?>
		</ul>
		<?php } else {?>
		<ul class="msg">
			<li><?php p($l->t('Welcome, you can create your account below.'));?></li>
		</ul>
		<?php }?>
		<p class="grouptop">
		<input type="email" id="email" name="email" value="<?php p($_['email']);?>" disabled />
		<label for="email" class="infield"><?php p($_['email']);?></label>
		<img class="icon email-icon svg" src="<?php print_unescaped(image_path('', 'actions/mail.svg'));?>" alt=""/>
		</p>

		<p class="groupmiddle">
		<input type="text" id="fullname" name="fullname" value="<?php p(getEnteredData($_, 'fullname'));?>" placeholder="<?php p($l->t('Full name'));?>" />
		<label for="fullname" class="infield"><?php p($l->t('Full name'));?></label>
		<img class="icon username-icon svg" src="<?php print_unescaped(image_path('', 'actions/user.svg'));?>" alt=""/>
		</p>

		<p class="groupmiddle">
		<input type="text" id="username" name="username" value="<?php p(getEnteredData($_, 'username'));?>" placeholder="<?php p($l->t('Username'));?>" />
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

		<input type="submit" value="<?php p($l->t('Create account'));?>" />
	</fieldset>
</form>
