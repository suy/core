<!--[if IE 8]><style>input[type="checkbox"]{padding:0;}</style><![endif]-->
<form method="post">
	<fieldset>
		<?php if(!empty($_['redirect'])): ?>
		<input type="hidden" name="redirect_url" value="'.$_['redirect'].'" />
		<?php endif; ?>
		<ul>
		<?php if(isset($_['invalidcookie']) && ($_['invalidcookie'])): ?>
			<li class="errors">
				<?php echo $l->t('Automatic logon rejected!'); ?><br>
				<small><?php echo $l->t('If you did not change your password recently, your account may be compromised!'); ?></small><br>
				<small><?php echo $l->t('Please change your password to secure your account again.'); ?></small>
			</li>
		<?php endif; ?>
		<?php if(isset($_['invalidpassword']) && ($_['invalidpassword'])): ?>
			<a href="<?php echo OC_Helper::linkToRoute('core_lostpassword_index') ?>"><li class="errors">
				<?php echo $l->t('Lost your password?'); ?>
			</li></a>
		<?php endif; ?>
		</ul>
		<p>
			<input type="text" name="user" id="user"
				placeholder="<?php echo $l->t( 'Username' ); ?>"
				value="<?php echo $_['username']; ?>"
				autocomplete="on"
				required
				<?php echo $_['user_autofocus']?' autofocus':''; ?>
			/>
		</p>
		
		<p>
			<input type="password" name="password" id="password"
				placeholder="<?php echo $l->t( 'Password' ); ?>"
				required
				<?php echo $_['user_autofocus']?'':' autofocus'; ?>
			/>
		</p>
		
		<input type="checkbox" name="remember_login" id="remember_login" value="1" />
		<label for="remember_login"><?php echo $l->t('remember'); ?></label>
		
		<input type="submit" id="submit" class="login" value="<?php echo $l->t( 'Log in' ); ?>" />
	</fieldset>
</form>
