<h1 class="page_title">
    <?php echo $title_for_layout; ?>
</h1>

<p>
    <?php echo $this->Html->link(
        '&larr; Back to Account',
        array('action' => 'account'),
        array('escape' => false, 'class' => 'under_header_back')
    ); ?>
</p>

<div class="content_box">
	<?php
        echo $this->Form->create('User', array('url' => array(
            'controller' => 'users',
            'action' => 'change_pass'
        )));
        echo $this->Form->input('new_password', array(
            'label' => 'New Password',
            'type' => 'password',
            'autocomplete' => 'off',
            'between' => '<br />'
        ));
        echo $this->Form->input('confirm_password', array(
            'label' => 'Confirm Password',
            'type' => 'password',
            'autocomplete' => 'off',
            'between' => '<br />'
        ));
        echo $this->Form->submit('Change password', array('class' => 'btn'));
        echo $this->Form->end();
    ?>
</div>
