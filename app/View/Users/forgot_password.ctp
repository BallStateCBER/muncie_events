<h1 class="page_title">
	<?php echo $title_for_layout; ?>
</h1>

<div class="content_box">
	<p>
		If you have forgotten the password to your MuncieEvents.com account, you can enter your email address below (the same one that
		you used to register an account) and we'll send you an email with a link to reset your password.
		If you need assistance, please
		<?php echo $this->Html->link('contact us', array(
            'controller' => 'pages',
            'action' => 'contact'
        )); ?>.
	</p>
	<?php
        echo $this->Form->create('User');
        echo $this->Form->input('email', array('label' => false));
        echo $this->Form->submit('Send password-resetting email', array('class' => 'btn'));
        echo $this->Form->end();
    ?>
	<br class="clear" />
</div>
