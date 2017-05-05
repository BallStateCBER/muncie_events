<h1 class="page_title">
	<?php echo $title_for_layout; ?>
</h1>

<p>
	This form will bulk-add email addresses to the event mailing list with the default settings
	(weekly, all categories). If there is an error with an email address, it will remain in
	the text box below after submitting the form. Otherwise, addresses successfully added
	will be removed from the text box.
</p>

<?php echo $this->Form->create(false); ?>
<?php echo $this->Form->input('email_addresses', array('type' => 'textarea')); ?>
<?php echo $this->Form->end('Bulk add'); ?>