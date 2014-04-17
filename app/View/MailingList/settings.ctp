<?php
	$form_url = array('controller' => 'mailing_list', 'action' => 'settings');
	if ($recipient_id && $hash) {
		$form_url[] = $recipient_id;
		$form_url[] = $hash;
	}
?>

<div id="mailing_list_settings">
	<h1 class="page_title">
		<?php echo $title_for_layout; ?>
	</h1>
	
	<?php echo $this->Form->create('MailingList', array(
		'url' => $form_url,
		'id' => 'MailingListForm'
	)); ?>
	
	<fieldset>
		<legend>Email Address</legend>
		<?php echo $this->Form->input('MailingList.email', array(
			'label' => 'Email'
		)); ?>
	</fieldset>
	
	<?php echo $this->element('mailing_list/frequency_options'); ?>
	<?php echo $this->element('mailing_list/category_options'); ?>
	
	<fieldset>
		<legend>Unsubscribe</legend>
		<?php echo $this->Form->input(
			'unsubscribe',
			array(
				'type' => 'checkbox',
				'label' => 'Remove me from the mailing list'
			)
		); ?>
	</fieldset>
	
	<?php echo $this->Js->submit('Update', array(
		'update' => '#mailing_list_settings', 
		'evalScripts' => true,
		'before' => 'return mailingListFormValidate();'
	)); ?>
</div>

<?php $this->Js->buffer("setupMailingListForm();"); ?>