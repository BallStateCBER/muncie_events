<h1 class="page_title">
	<?php echo $title_for_layout; ?>
</h1>
<p>
	Send in any questions or comments through this form and we will do our best
	to respond quickly. If you would prefer to do the emailing yourself,
	you can send a message to a site administrator at
	<a href="mailto:<?php echo Configure::read('admin_email'); ?>"><?php echo Configure::read('admin_email'); ?></a>.
</p>

<?php echo $this->Form->create('Dummy', array('url' => array('controller' => 'pages', 'action' => 'contact'))); ?>
<?php echo $this->Form->input('category', array('label' => 'Category', 'options' => $categories)); ?>
<?php echo $this->Form->input('name', array(
	'default' => $this->Session->read('Auth.User.name')
)); ?>
<?php echo $this->Form->input('email', array(
	'default' => $this->Session->read('Auth.User.email')
)); ?>
<?php echo $this->Form->input('body', array('label' => 'Message', 'type' => 'textarea', 'style' => 'max-width:100%;')); ?>
<?php if (! $this->Session->read('Auth.User.id')): ?>
	<?php echo $this->element('recaptcha', array('label' => false)); ?>
<?php endif; ?>
<?php echo $this->Form->end('Send'); ?>
