<?php
	$logged_in = (boolean) $this->Session->read('Auth.User.id');
	$user_role = $this->Session->read('Auth.User.role');
?>
<ul>
	<?php if ($logged_in): ?>
		<li><?php echo $this->Html->link('Account', array('plugin' => false, 'controller' => 'users', 'action' => 'account')); ?></li>
		<li>
			<?php if ($facebook_user): ?>
				<?php echo $this->Facebook->disconnect(array(
					'redirect' => array('controller' => 'users', 'action' => 'logout'),
					'label' => 'Logout'
				)); ?>
			<?php else: ?>
				<?php echo $this->Html->link('Log out', array('plugin' => false, 'controller' => 'users', 'action' => 'logout')); ?>
			<?php endif; ?>
		</li>
	<?php else: ?>
		<li><?php echo $this->Html->link('Log in', array('plugin' => false, 'controller' => 'users', 'action' => 'login')); ?></li>
		<li><?php echo $this->Html->link('Register', array('plugin' => false, 'controller' => 'users', 'action' => 'register')); ?></li>
	<?php endif; ?>
	<li><?php echo $this->Html->link('Contact', array('plugin' => false, 'controller' => 'pages', 'action' => 'contact')); ?></li>
	<li><?php echo $this->Html->link('About', array('plugin' => false, 'controller' => 'pages', 'action' => 'about')); ?></li>
</ul>