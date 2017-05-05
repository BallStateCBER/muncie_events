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
	<p class="alert alert-success">
		Your Muncie Events account is no longer synced to your Facebook account. This can be undone at any
		time by returning to the 'Account' page and clicking 'Sync Account with Facebook'.
	</p>
<?php else: ?>
	<p class="alert alert-danger">
		There was an error removing the sync with your Facebook account. If you need assistance,
		<?php echo $this->Html->link('contact an administrator', array(
			'controller' => 'pages', 'action' => 'contact'
		)); ?>.
	</p>
<?php endif; ?>
