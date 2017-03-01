<?php $this->extend('default_wrapper'); ?>

<?php echo $this->element('sidebar'); ?>

<div id="content_wrapper" class="col-md-8">
	<div id="content" class="clearfix">
		<?php echo $this->fetch('content'); ?>
	</div>
</div>
