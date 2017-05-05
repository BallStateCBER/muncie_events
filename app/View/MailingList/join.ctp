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
		'class' => 'form-control',
		'div' => array(
			'class'=>'form-group col-lg-8 col-xs-12'
		),
		'value' => isset($default_email) ? $default_email : null
	)); ?>
	<div id="mailing_list_basic_options" class="form-group col-lg-8 col-xs-12">
		<?php echo $this->Form->input(
			'settings',
			array(
				'type' => 'radio',
				'options' => array(
					'default' => 'Default Settings',
				),
				'default' => 'default',
				'class' => 'settings_options',
				'div' => array(
					'class'=>'form-control mailing-options'
				),
				'legend' => false
			)
		); ?>
		<?php echo $this->Form->input(
			'settings',
			array(
				'type' => 'radio',
				'options' => array(
					'custom' => 'Custom'
				),
				'default' => 'default',
				'class' => 'settings_options',
				'div' => array(
					'class'=>'form-control mailing-options'
				),
				'legend' => false
			)
		); ?>
	</div>
	<div id="custom_options" style="display: none;" class="row">
		<?php echo $this->element('mailing_list/frequency_options'); ?>
		<?php echo $this->element('mailing_list/category_options'); ?>
	</div>
	<?php echo $this->Form->submit('Join Event Mailing List', array(
		'class' => 'btn btn-secondary btn-sm',
		'url' => array(
			'controller' => 'mailing_list',
			'action' => 'join'
		)
	)); ?>
	<?php echo $this->Form->end(); ?>
</div>
<?php $this->Js->buffer("setupMailingListForm();"); ?>
