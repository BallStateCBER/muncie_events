<h1 class="page_title">
	<?php echo $title_for_layout; ?>
</h1>

<p>
	<?php echo $this->Html->link(
		'&larr; Back to Account', 
		array(
			'controller' => 'users', 
			'action' => 'account'
		), 
		array('escape' => false)
	); ?>
</p>

<?php if ($success): ?>
	<p class="success_message">
		Your Muncie Events account has been synced with your Facebook account. From now on, you have the option to 
		log in to MuncieEvents.com via Facebook by clicking the <img src="/Facebook/img/fb_login.png" /> button
		instead of logging in with your email address and password.
	</p>
<?php else: ?>
	<p class="error_message">
		There was an error syncing your Muncie Events account to your Facebook account. Make sure that you are currently
		logged in to <a href="http://facebook.com">Facebook</a>. If you need assistance, 
		<?php echo $this->Html->link('contact an administrator', array(
			'controller' => 'pages', 'action' => 'contact'
		)); ?>.
	</p>
<?php endif; ?>