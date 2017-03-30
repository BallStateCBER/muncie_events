<h1 class="page_title">Event Feed</h1>

<?php echo $this->Html->link(
	'&larr; Back to Widgets Overview',
	array('action' => 'index'),
	array('escape' => false, 'class' => 'under_header_back')
); ?>

<div class="widget_controls_wrapper">
	<div class="widget_controls form-group col-lg-4">
		<h2>Customize Your Widget</h2>
		<form>
			<h3>
				<a href="#">Events</a>
			</h3>
			<div id="WidgetFilterOptions">
				<?php echo $this->element('widgets/customize/events'); ?>
			</div>

			<h3>
				<a href="#">Text</a>
			</h3>
			<div class="text">
				<?php echo $this->element('widgets/customize/text'); ?>
			</div>

			<h3>
				<a href="#">Borders</a>
			</h3>
			<div class="borders">
				<?php echo $this->element('widgets/customize/borders'); ?>
				<div class="form-control">
					<input type="checkbox" name="outerBorder" checked="checked" value="1" class="option" /> Border around widget
				</div>
			</div>

			<h3>
				<a href="#">Backgrounds</a>
			</h3>
			<div class="backgrounds">
				<?php echo $this->element('widgets/customize/backgrounds'); ?>
			</div>

			<h3>
				<a href="#">Size</a>
			</h3>
			<div>
				<?php echo $this->element('widgets/customize/size'); ?>
			</div>

			<br />
			<input class="btn btn-small" type="submit" value="Apply changes" />
		</form>
	</div>
	<div class="widget_demo col-lg-7" id="widget_demo"></div>
</div>

<?php
	$this->Html->script('/jPicker/jpicker-1.1.6.js', array('inline' => false));
	$this->Html->css('/jPicker/css/jPicker-1.1.6.min.css', null, array('inline' => false));
	$this->Html->css('/jPicker/jPicker.css', null, array('inline' => false));
	$this->Html->script('widgets/customize.js', array('inline' => false));
	$this->Js->buffer("widgetCustomizer.setupWidgetDemo('feed');");
?>
