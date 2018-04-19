<?php $recaptcha_error = $recaptcha_error ?? null; ?>
<div class="input required recaptcha <?php if ($recaptcha_error) echo 'error'; ?>">
	<?php if (! isset($label)) $label = 'Spam Protection'; ?>
	<?php if ($label !== false): ?>
		<label for="recaptcha_response_field">
			<?php echo $label; ?>
		</label>
	<?php endif; ?>
	<?php echo $this->Recaptcha->display(); ?>
	<?php echo $recaptcha_error; ?>
</div>
