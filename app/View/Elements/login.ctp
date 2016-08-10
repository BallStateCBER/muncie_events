<?php
	$password_error = isset($password_error) ? '<div class="error-message">'.$password_error.'</div>' : '';
?>
<div id="login">
	<?php 
		echo $this->Form->create('User', array('url' => array('controller' => 'users', 'action' => 'login')));
		echo $this->Form->input('email');
		echo $this->Form->input('password', array(
			'after' => $password_error.'<br />'.$this->Html->link(
				'Forgot password?',
				array(
					'controller' => 'users',
					'action' => 'forgot_password'
				)
			)
		));
		echo $this->Form->input('remember_me', array(
			'type' => 'checkbox', 
			'label' => array(
				'text' => ' Remember me', 
				'style' => 'display: inline;'
			),
			'checked' => true
		));
		echo $this->Form->submit('Login', array(
			'after' => ''
		));
		echo $this->Form->end();
	?>
	Or log in with Facebook: <?php echo $this->Facebook->login(array(
		'label' => 'Log in with Facebook',
		'img' => 'fb_login.png',
		'show-faces' => false,
		'perms' => 'email,user_events,create_event,rsvp_event',
		'redirect' => "/users/confirm_facebook_login?redirect=$redirect"
	)); ?>
</div>

Don't have an account yet? 

<?php echo $this->Html->link(
	'Register',
	array(
		'controller' => 'users',
		'action' => 'register'
	)
); ?>
