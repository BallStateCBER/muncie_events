<?php
	$logged_in = (boolean) $this->Session->read('Auth.User.id');
	$user_role = $this->Session->read('Auth.User.role');
?>
	<?php if ($logged_in): ?>
		<li class="nav-item"><?php echo $this->Html->link('Account', array('plugin' => false, 'controller' => 'users', 'action' => 'account'), array('class'=>'nav-link')); ?></li>
		<li class="nav-item">
			<?php if ($facebook_user): ?>
				<?php echo $this->Facebook->disconnect(array(
					'redirect' => array('controller' => 'users', 'action' => 'logout'),
					'label' => 'Logout'
				)); ?>
			<?php else: ?>
				<?php echo $this->Html->link('Log out', array('plugin' => false, 'controller' => 'users', 'action' => 'logout'), array('class'=>'nav-link')); ?>
			<?php endif; ?>
		</li>
	<?php else: ?>
		<li class="nav-item"><?php echo $this->Html->link('Log in', array('plugin' => false, 'controller' => 'users', 'action' => 'login'), array('class'=>'nav-link')); ?></li>
		<li class="nav-item"><?php echo $this->Html->link('Register', array('plugin' => false, 'controller' => 'users', 'action' => 'register'), array('class'=>'nav-link')); ?></li>
	<?php endif; ?>
	<li class="nav-item"><?php echo $this->Html->link('Contact', array('plugin' => false, 'controller' => 'pages', 'action' => 'contact'), array('class'=>'nav-link')); ?></li>
	<li class="nav-item"><?php echo $this->Html->link('About', array('plugin' => false, 'controller' => 'pages', 'action' => 'about'), array('class'=>'nav-link')); ?></li>
