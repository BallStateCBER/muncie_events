<?php if (! $this->params['isAjax']): ?>
	<h1 class="page_title">
		<?php echo $title_for_layout; ?>
	</h1>
<?php endif; ?>

<?php echo $this->element('login'); ?>