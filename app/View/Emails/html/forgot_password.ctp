<h1>
	<a href="http://muncieevents.com">
		<img src="http://muncieevents.com/img/email_logo.png" alt="Muncie Events" />
	</a>
</h1>

<p>
	<?php echo $email_address; ?>,
</p>

<p>
	Someone (presumably you) just requested that your password for MuncieEvents.com be reset 
	so you can log in again. We would just <i>tell</i> you what your password is, but since 
	it's stored in a one-way encrypted format, we actually have no way to figure out what it is.
</p>

<p>
	Anyway, just click on this link and you'll be prompted to enter in a new password to overwrite 
	your old one.
</p>

<p>
	<a href="<?php echo $reset_url; ?>">
		<?php echo $reset_url; ?>
	</a>
</p>

<p>
	<strong>NOTE: That link will only work for the rest of <?php echo date('F Y'); ?>.</strong>
	If you need to reset your password in <?php echo date('F', strtotime('+1 month')); ?>, you'll need
	to request another password reset link. That's so if you forget to delete this email and some creep 
	finds it later, they won't be able to use it to get into your account. Still, it would be a good 
	idea to delete this email as soon as you've reset your password.
</p>

<p>
	Love,
	<br /> 
	<a href="http://MuncieEvents.com">Muncie Events</a>
</p>