<h1 class="page_title">
	Change Password
</h1>

<div class="col-lg-8">
	<?php echo $this->Form->create('User', array(
		'url' => array(
			'class' => 'form-group',
			'controller' => 'users',
			'action' => 'change_pass'
		)
	)); ?>
	<?php echo $this->Form->input('new_password', array(
		'class' => 'form-control',
		'label' => 'New Password',
		'type' => 'password',
		'value' => ''
	)); ?>
	<?php echo $this->Form->input('confirm_password', array(
		'class' => 'form-control',
		'type' => 'password',
		'value' => ''
	)); ?>
	<?php echo $this->Form->submit('Change password', array(
		'class' => 'btn btn-sm btn-secondary'
	)); ?>
	<?php echo $this->Form->end(); ?>
</div>
