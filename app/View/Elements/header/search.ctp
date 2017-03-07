<?php
	echo $this->Form->create('Event', array(
		'id' => 'EventSearchForm',
		'url' => array_merge(array('action' => 'search'), $this->params['pass'])
	));
	echo $this->Form->input('filter', array(
		'div' => false,
		'label' => false,
		'before' => '<img src="/img/loading_small_dark.gif" id="search_autocomplete_loading" />',
		'after' => $this->Form->submit(__('Search'), array('div' => false, 'class' => 'btn btn-secondary btn-sm')).' <a href="#" id="search_options_toggler">Advanced</a>',
	));
?>
<div id="search_options" style="display: none;">
	<div>
		<?php
			echo $this->Form->input('direction', array(
				'options' => array(
					'future' => 'Upcoming Events',
					'past' => 'Past Events',
					'all' => 'All Events'
				),
				'default' => 'future',
				'type' => 'radio',
				'legend' => false,
				'separator' => '<br />'
			));
		?>
	</div>
</div>
<?php echo $this->Form->end(); ?>
<?php $this->Js->buffer("setupSearch();"); ?>
