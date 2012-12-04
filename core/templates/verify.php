<form method="post">
	<fieldset>
		<ul>
			<li class="errors">
				<?php echo $l->t('Security Warning!'); ?><br>
				<small><?php echo $l->t("Please verify your password. <br/>For security reasons you may be occasionally asked to enter your password again."); ?></small>
			</li>
		</ul>
		
		<p>
			<input type="text"  value="<?php echo $_['username']; ?>" disabled="disabled" />
		</p>
		
		<p>
			<input type="password" name="password" id="password"
				placeholder="<?php echo $l->t( 'Password' ); ?>"
				required
				<?php echo $_['user_autofocus']?'':' autofocus'; ?>
			/>
		</p>
		
		<input type="submit" id="submit" class="login" value="<?php echo $l->t( 'Verify' ); ?>" />
	</fieldset>
</form>
