<?php
    $this->Html->script('tag_manager.js', array('inline' => false));
?>

<div class="form-control">
	<input type="checkbox" name="use_custom_categories" value="1" id="WidgetFilterToggler_categories" class="filter_toggler" />
	<label for="WidgetFilterToggler_categories">Only specific categories...</label>
	<div id="WidgetFilter_categories" style="display: none;">
		<input type="checkbox" value="" id="WidgetCatAll" checked="checked" />
		<label for="WidgetCatAll">
			Select All
		</label>
		<br />
		<?php foreach ($categories as $category): ?>
			<input type="checkbox" value="<?php echo $category['Category']['id']; ?>" id="WidgetCat<?php echo $category['Category']['id']; ?>" class="category" checked="checked" />
			<label for="WidgetCat<?php echo $category['Category']['id']; ?>">
				<?php echo $this->Icon->category($category['Category']['name']); ?>
				<?php echo $category['Category']['name']; ?>
			</label>
			<br />
		<?php endforeach; ?>
	</div>
</div>
<div class="form-control">
	<input type="checkbox" name="use_custom_location" value="1" id="WidgetFilterToggler_location" class="filter_toggler" />
	<label for="WidgetFilterToggler_location">Only a specific location...</label>
	<div id="WidgetFilter_location" style="display: none;">
		<input type="text" name="custom_location" id="WidgetFilter_location_input" class="form-control" />
		<p class="text-muted">
			Only events whose locations match the above name will be included.
		</p>
	</div>
</div>
<div class="form-control">
	<input type="checkbox" name="use_custom_tag_include" value="1" id="WidgetFilterToggler_tag_include" class="filter_toggler" />
	<label for="WidgetFilterToggler_tag_include">Must have one of these tags...</label>
	<div id="WidgetFilter_tag_include" style="display: none;">
		<input type="text" id="WidgetFilter_tag_include_input" class="ui-autocomplete-input form-control" autocomplete="off" />
		<img src="/img/loading_small.gif" class="loading" />
		<p class="text-muted">
			Write out tags, separated by commas
		</p>
		<?php $this->Js->buffer("
			TagManager.setupAutosuggest('#WidgetFilter_tag_include_input');
		"); ?>
	</div>
</div>
<div class="form-control">
	<input type="checkbox" name="use_custom_tag_exclude" value="1" id="WidgetFilterToggler_tag_exclude" class="filter_toggler" />
	<label for="WidgetFilterToggler_tag_exclude">Must NOT have these tags...</label>
	<div id="WidgetFilter_tag_exclude" style="display: none;">
		<input type="text" id="WidgetFilter_tag_exclude_input" class="ui-autocomplete-input form-control" autocomplete="off" />
		<img src="/img/loading_small.gif" class="loading" />
		<p class="text-muted">
			Write out tags, separated by commas
		</p>
		<?php $this->Js->buffer("
			TagManager.setupAutosuggest('#WidgetFilter_tag_exclude_input');
		"); ?>
	</div>
</div>
