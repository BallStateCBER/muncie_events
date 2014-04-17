<h1 class="page_title">
	Change Password
</h1>

<?php echo $this->Form->create('User', array(
	'url' => array(
		'controller' => 'users', 
		'action' => 'change_pass'
	)
)); ?>
<?php echo $this->Form->input('new_password', array(
	'label' => 'New Password', 
	'type' => 'password',
	'value' => ''
)); ?>
<?php echo $this->Form->input('confirm_password', array(
	'type' => 'password',
	'value' => ''
)); ?>
<?php echo $this->Form->end('Change password'); ?>