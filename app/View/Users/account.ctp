<div id="my_account">
	<h1 class="page_title">
		<?php echo $title_for_layout; ?>
	</h1>
	
	<ul>
		<li>
			<?php echo $this->Html->link('Change Password', array('controller' => 'users', 'action' => 'change_pass')); ?>
		</li>
		<?php if ($mailing_list_id): ?>
			<li>
				<?php echo $this->Html->link(
					'Update Mailing List Settings', 
					Router::url(array(
						'controller' => 'mailing_list',
						'action' => 'settings',
						$mailing_list_id,
						$mailing_list_hash
					), true)
				); ?>
			</li>
		<?php endif; ?>
		<?php if ($facebook): ?>
			<li>
				<?php echo $this->Form->postLink(
					'Remove sync with Facebook', 
					array(
						'controller' => 'users',
						'action' => 'facebook_unsync'
					), 
					null, 
					'You will be logged out and will need to log back in. Are you sure?'
				); ?>
				<br />
				<span class="fb_footnote">
					Currently synced to <?php echo $facebook['name']; ?>
					(<a href="<?php echo $facebook['url']; ?>"><?php echo $facebook['url']; ?></a>)
				</span>
			</li>
		<?php else: ?>
			<li>
				<?php echo $this->Facebook->login(array(
					'label' => 'Sync Account with Facebook',
					'show-faces' => false,
					'perms' => 'email,user_events,create_event,rsvp_event',
					'redirect' => array(
						'controller' => 'users',
						'action' => 'facebook_sync'
					)
				)); ?>
			</li>
		<?php endif; ?>
	</ul>
	
	<?php echo $this->Form->create('User', array(
		'url' => array(
			'controller' => 'users', 
			'action' => 'account'
		)
	)); ?>
	<?php echo $this->Form->input('name', array(
		'label' => 'Name', 
		'after' => '<div class="footnote">Your first and last actual name, please</div>'
	)); ?>
	<?php echo $this->Form->input('email', array(
		'label' => 'Email'
	)); ?>
	<?php echo $this->Form->end('Update'); ?>
</div>