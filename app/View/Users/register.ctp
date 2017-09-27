<h1 class="page_title">
	<?php echo $title_for_layout; ?>
</h1>

<div id="register">
	<?php
        echo $this->Form->create('User', array('url' => array('controller' => 'users', 'action' => 'register')));
        echo $this->Form->input('name', array(
        'class' => 'form-control',
        'div' => array(
            'class'=>'form-group col-lg-8 col-xs-12'
        ),
        'after' => '<div class="text-muted">Your real first and last name, please. (e.g. Jane Smith)</div>'));
        echo $this->Form->input('email', array(
        'class' => 'form-control',
        'div' => array(
            'class'=>'form-group col-lg-8 col-xs-12'
        ),
        'after' => '<div class="text-muted">This is what you will use to log in.</div>'));
        echo $this->Form->input('new_password', array(
        'class' => 'form-control',
        'div' => array(
            'class'=>'form-group col-lg-8 col-xs-12'
        ),
        'label' => 'Password', 'type' => 'password'));
        echo $this->Form->input('confirm_password', array(
        'class' => 'form-control',
        'div' => array(
            'class'=>'form-group col-lg-8 col-xs-12'
        ),
        'type' => 'password', 'label' => 'Confirm Pass'));
        echo $this->Form->input('join_mailing_list', array(
        'class' => 'form-control',
        'div' => array(
            'class'=>'form-group col-lg-8 col-xs-12'
        ),
        'label' => 'Join mailing list *', 'type' => 'checkbox', 'checked' => true));

        $mailing_list_footnote = '<p class="text-muted">* The personalized mailing list delivers daily or weekly emails about all upcoming events
			or only the categories that you\'re interested in. After registering, you\'ll be taken
			to a page where you can customize your subscription.</p>';
        echo $mailing_list_footnote;

        echo $this->element('recaptcha');
        echo $this->Form->submit('Register', array('class' => 'btn'));
        echo $this->Form->end();
    ?>
</div>
