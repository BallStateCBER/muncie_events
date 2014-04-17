<?php echo $this->Form->create('Person', array('url' => array('controller' => 'people', 'action' => 'edit')));?>
<?php echo $this->Form->input('name', array('label' => 'Name', 'between' => '<div class="footnote">First and last, please</div>')); ?>
<?php echo $this->Form->input('email', array('label' => 'Email')); ?>
<?php echo $this->Form->input('sex', array('label' => 'Sex', 'options' => array('', 'm' => 'Male', 'f' => 'Female'))); ?>
<?php echo $this->Form->input('bio', array('label' => 'Bio', 'style' => 'height: 300px; width: 400px;', 'between' => '<div class="footnote">ENTER double-spaces. SHIFT + ENTER single-spaces.</div>')); ?>
<?php echo $this->element('tags/tag_editing', compact('available_tags', 'selected_tags')); ?>
<?php echo $this->element('recaptcha_input'); ?>
<?php echo $this->Form->end('Submit'); ?>
<?php echo $this->element('tinymce_init'); ?>