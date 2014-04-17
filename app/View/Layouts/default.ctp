<?php $this->extend('default_wrapper'); ?>

<?php echo $this->element('sidebar'); ?>

<div id="content_wrapper" class="clearfix">
	<div id="content" class="clearfix">
		<?php echo $this->fetch('content'); ?>
	</div>
</div>