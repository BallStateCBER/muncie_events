<?php $this->extend('default_wrapper'); ?>

<div id="content_wrapper" class="col-lg-9 col-md-8">
	<div id="content" class="clearfix">
		<?php echo $this->fetch('content'); ?>
	</div>
</div>

<?php echo $this->element('sidebar'); ?>
