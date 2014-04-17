<h1 class="page_title">
	<?php echo $title_for_layout; ?>
</h1>

<ul id="register_options">
	<li>
		<a href="#" id="register_handle">
			The Old-Fashioned Way
		</a>
	</li>
	<li>
		<a href="#" id="register_facebook_handle">
			With Your Facebook Account
		</a>
	</li>
</ul>


<div id="register">
	<?php 
		echo $this->Form->create('User', array('controller' => 'users', 'action' => 'register'));
		echo $this->Form->input('name', array('after' => '<div class="footnote">Your real first and last name, please. (e.g. Jane Smith)</div>'));
		echo $this->Form->input('email', array('after' => '<div class="footnote">This is what you will use to log in.</div>'));
		echo $this->Form->input('new_password', array('label' => 'Password', 'type' => 'password'));
		echo $this->Form->input('confirm_password', array('type' => 'password', 'label' => 'Confirm Pass'));
		echo $this->Form->input('join_mailing_list', array('label' => 'Join mailing list *', 'type' => 'checkbox', 'checked' => true));
	
		$mailing_list_footnote = '<p class="footnote">* The personalized mailing list delivers daily or weekly emails about all upcoming events 
			or only the categories that you\'re interested in. After registering, you\'ll be taken 
			to a page where you can customize your subscription.</p>'; 
		echo $mailing_list_footnote;
	
		echo $this->element('recaptcha');
		echo $this->Form->end('Register');
	?>
</div>

<div id="register_facebook" style="display: none;">
	<?php echo $this->Facebook->registration(array(
	    'fields' => '[
	    	{"name":"name"},
	    	{"name":"email"},
	    	{
	    		"name":"join_mailing_list", 
	    		"description":"Join Mailing List*", 
	    		"type":"checkbox",
	    		"default":true
	    	},
	    ]',
	    'width' => 350,
		'redirect-uri' => Router::url(array(
			'controller' => 'users', 
			'action' => 'register_facebook'
		), true)
	)); ?>
	<?php echo $mailing_list_footnote; ?>
</div>

<?php $this->Js->buffer("
	$('#register_handle').click(function(event) {
		event.preventDefault();
		var register_container = $('#register');
		if (register_container.is(':visible')) {
			return;
		}
		$('#register_facebook').fadeOut(300, function() {
			register_container.fadeIn(300);
		});
	});
	
	$('#register_facebook_handle').click(function(event) {
		event.preventDefault();
		var register_fb_container = $('#register_facebook');
		if (register_fb_container.is(':visible')) {
			return;
		}
		$('#register').fadeOut(300, function() {
			register_fb_container.fadeIn(300);
		});
	});
"); ?>