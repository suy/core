<form action="<?php echo $_['URL']; ?>" method="post">
	<fieldset>
		<p>
			<input type="password" name="password" id="password"
				placeholder="<?php echo $l->t('Password'); ?>"
				autofocus required
			/>
			
			<input type="submit" value="<?php echo $l->t('Submit'); ?>" />
		</p>
	</fieldset>
</form>