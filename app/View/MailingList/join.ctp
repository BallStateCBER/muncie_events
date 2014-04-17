<div id="mailing_list_settings">
	<h1 class="page_title">
		<?php echo $title_for_layout; ?>
	</h1>
	<?php echo $this->Form->create('MailingList', array(
		'url' => array('controller' => 'mailing_list', 'action' => 'join'),
		'id' => 'MailingListForm'
	)); ?>
	<?php echo $this->Form->input('email', array(
		'label' => 'Email: ',
		'value' => isset($default_email) ? $default_email : null
	)); ?>
	<div id="mailing_list_basic_options">
		<?php echo $this->Form->input(
			'settings',
			array(
				'type' => 'radio',
				'options' => array(
					'default' => 'Default Settings',
					'custom' => 'Custom'
				),
				'default' => 'default',
				'class' => 'settings_options',
				'legend' => false
			)
		); ?>
	</div>
	
	<div id="custom_options" style="display: none;">
		<?php echo $this->element('mailing_list/frequency_options'); ?>
		<?php echo $this->element('mailing_list/category_options'); ?>
	</div>
	<?php echo $this->Form->submit('Join Event Mailing List', array(
		'url' => array(
			'controller' => 'mailing_list',
			'action' => 'join'
		)
	)); ?>
	<?php echo $this->Form->end(); ?>
</div>
<?php $this->Js->buffer("setupMailingListForm();"); ?>