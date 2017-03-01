<?php $this->extend('default_wrapper'); ?>

<?php if ($this->params['controller'] == 'pages' && $this->action == 'home'): ?>
	<?php echo $this->element('front_page_announcement'); ?>
<?php endif; ?>

<?php echo $this->element('sidebar'); ?>

<div id="content_wrapper" class="col-sm-12 col-lg-8">
	<div id="content" class="clearfix">
		<?php echo $this->fetch('content'); ?>
	</div>
</div>
