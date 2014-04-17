<div id="edit_basic_profile">
	<h1 class="page_title">
		<?php echo $title_for_layout; ?>
	</h1>
	<p>
		<?php echo $this->Html->link('&larr; Back to Account', array('controller' => 'people', 'action' => 'my_account'), array('escape' => false)); ?>
	</p>
	<?php echo $this->Form->create('Person', array(
		'url' => array(
			'controller' => 'people', 
			'action' => 'edit_basic_profile',
			$user_id
		)
	)); ?>
	<?php echo $this->Form->input('bio', array(
		'label' => 'Bio', 
		'between' => '<div class="footnote">ENTER double-spaces. SHIFT + ENTER single-spaces.</div>',
		'style' => 'height: 300px; width: 400px;'
	)); ?>
	<?php echo $this->element('tags/tag_editing', array(
		array('available_tags' => $available_tags, 'selected_tags' => $selected_tags, 'tree' => $tree)
	)); ?>
	<?php echo $this->Form->end('Update'); ?>
	<br class="clear" />
</div>
<?php echo $this->element('tinymce_init'); ?>