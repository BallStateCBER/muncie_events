<?php
    $multiple_dates_allowed = ($this->action == 'add' || $this->action == 'edit_series');
    $logged_in = (boolean) $this->Session->read('Auth.User.id');
    $this->Html->script('event_form.js', array('inline' => false));
?>

<h1 class="page_title">
	<?php echo $title_for_layout; ?>
</h1>

<a href="#posting_rules" id="posting_rules_toggler" data-toggle="collapse">
	Rules for Posting Events
</a>

<div id="posting_rules" class="alert alert-info collapse" aria-expanded="false">
	<?php echo $this->element('rules'); ?>
</div>

<?php if (! $logged_in): ?>
	<div class="alert alert-info">
		<p>
			<strong>You're not currently logged in</strong>. You can still submit this event, but...
		</p>
		<ul>
			<li>you will not be able to edit it,</li>
			<li>you will not be able to add custom tags,</li>
			<li>you will not be able to include images,</li>
			<li>you'll have to fill out one of those annoying CAPTCHA challenges, and</li>
			<li>it won't be published until an administrator reviews it.</li>
		</ul>
		<p>
			You can
			<strong>
				<?php echo $this->Html->link('register an account', array('controller' => 'users', 'action' => 'register')); ?>
			</strong>
			and
			<strong>
				<?php echo $this->Html->link('log in', array('controller' => 'users', 'action' => 'login')); ?>
			</strong>
			to skip the hassle.
		</p>
	</div>
<?php elseif ($this->action == 'add' && ! $autopublish): ?>
	<div class="alert alert-info">
		<p>
			<strong>Thanks for registering an account!</strong> Unfortunately, to combat spam, your first event will need to be
			approved by an administrator before it gets published. This typically happens in less than 24 hours. But after that,
			all of your events will go directly to the calendar network.
		</p>
	</div>
<?php endif; ?>

<?php
    echo $this->Form->create('Event');
    if (isset($event_id) && $event_id) {
        echo $this->Form->input('Event.id', array('type' => 'hidden'));
    }
?>
<table class="event_form">
	<tbody>
		<tr>
			<th>Event</th>
			<td><?php
                echo $this->Form->input('title', array(
                    'label' => false,
                    'class' => 'form-control',
                    'div' => array(
                        'class'=>'form-group col-lg-8 col-xs-12'
                    )
                ));
            ?></td>
		</tr>
		<?php /* if ($this->request->data['Event']['series_id']): ?>
            <tr>
                <th>Part of Series</th>
                <td class="in_series">
                    <?php if (! empty($this->data['EventSeries']['title'])): ?>
                        <?php echo $this->data['EventSeries']['title']; ?>
                    <?php else: ?>
                        (Unnamed series)
                    <?php endif; ?>
                    <?php echo $this->Html->link(
                        'view',
                        array(
                            'controller' => 'event_series',
                            'action' => 'view',
                            'id' => $this->data['Event']['series_id']
                        )
                    ); ?>
                    |
                    <?php echo $this->Html->link(
                        'edit',
                        array(
                            'controller' => 'event_series',
                            'action' => 'edit',
                            'id' => $this->data['Event']['series_id']
                        )
                    ); ?>
                </td>
            </tr>
        <?php endif; */ ?>
		<tr>
			<th>Category</th>
			<td><?php
                echo $this->Form->input('category_id', array(
                    'label' => false,
                    'options' => $categories,
                    'empty' => false,
                    'default' => 13,
                    'class' => 'form-control',
                    'div' => array(
                        'class'=>'form-group col-lg-8 col-xs-12'
                    )
                ));
            ?></td>
		</tr>
		<tr>
			<th>Date(s)</th>
			<td>
				<div class="col-xs-12 col-lg-8">
					<div id="datepicker" class="<?php echo($multiple_dates_allowed ? 'multi' : 'single'); ?>"></div>
					<?php
                        if ($multiple_dates_allowed) {            // Multiple dates can be selected
                            $this->Html->script('jquery-ui.multidatespicker.js', array('inline' => false));
                            $this->Js->buffer("
								var default_date = '$default_date';
								var preselected_dates = $datepicker_preselected_dates;
								setupDatepickerMultiple(default_date, preselected_dates);
							");
                        } else {    // Only one date can be selected
                            $this->Js->buffer("
								var default_date = '".$this->request->data['Event']['date']."';
								setupDatepickerSingle(default_date);
							");
                        }
                        echo $this->Form->input('date', array(
                            'type' => 'hidden',
                            'id' => 'datepicker_hidden'
                        ));
                    ?>
					<?php if ($multiple_dates_allowed): ?>
						<div class="text-muted">
							Select more than one date to create multiple events connected by a series.
						</div>
					<?php endif; ?>
					<?php if (isset($this->validationErrors['Event']['date'][0])): ?>
						<div class="alert alert-danger">
							<?php echo $this->validationErrors['Event']['date'][0]; ?>
						</div>
					<?php endif; ?>
			</div>
		</td>
		</tr>
		<?php if ($multiple_dates_allowed): ?>
			<tr id="series_row" <?php if (! $has['series']): ?>style="display: none;"<?php endif; ?>>
				<th>Series Name</th>
				<td>
					<?php echo $this->Form->input('EventSeries.title', array(
                        'label' => false,
                        'after' => '<div class="text-muted">By default, the series and its events have the same title.</div>',
                        'class' => 'form-control',
                        'div' => array(
                            'class'=>'form-group col-lg-8 col-xs-12'
                        )
                    )); ?>
					<?php echo $this->Form->input('series_id', array(
                        'type' => 'hidden'
                    )); ?>
				</td>
			</tr>
		<?php endif; ?>
		<tr>
			<th>Time</th>
			<td>
				<?php
                    echo $this->Form->input('time_start', array(
                        'label' => false,
                        'interval' => 5,
                        'timeFormat' => '12',
                        'div' => array(
                            'id' => 'eventform_timestart_div',
                            'class'=>'form-group col-md-10 col-xs-12'
                        ),
                        'class' => 'form-control event_time_form',
                        'after' => '<span id="eventform_noendtime" '.($has['end_time'] ? 'style="display: none;"' : '').'><a id="add_end_time" href="#">Add end time</a></span>'
                    ));
                ?>
				<div id="eventform_hasendtime" class="form-group col-md-10 col-xs-12" <?php if (! $has['end_time']): ?>style="display: none;"<?php endif; ?>>
					<?php
                        if (isset($this->data['Event']['time_end'])) {
                            $selected_end_time = ($this->data['Event']['time_end'] == '00:00:00')
                                ? '24:00:00'    // Fixes bug where midnight is represented as noon
                                : $this->data['Event']['time_end'];
                        } else {
                            $selected_end_time = array(
                                'hour' => '1',
                                'min' => '00',
                                'meridian' => 'pm'
                            );
                        }
                        echo $this->Form->input('time_end', array(
                            'label' => false,
                            'interval' => 5,
                            'timeFormat' => '12',
                            'selected' => $selected_end_time,
                            'class' => 'form-control event_time_form'
                        ));
                    ?>
					<?php echo $this->Form->hidden('has_end_time', array(
                        'id' => 'eventform_hasendtime_boolinput',
                        'value' => $has['end_time'] ? 1 : 0
                    )); ?>
					<a href="#" id="remove_end_time">Remove end time</a>
				</div>
			</td>
		</tr>
		<tr>
			<th>Location</th>
			<td>
				<div class="form-group">
					<?php echo $this->Form->input('location', array(
                        'label' => false,
                        'class' => 'form-control col-lg-8',
                        'div' => array(
                            'class' => 'col-lg-12'
                        ),
                        'after' => '<a href="#" id="eventform_noaddress" <?php echo $has["address"] ? "style=\'display: none;\'" : ""?>Add address</a>'
                    )); ?>
					<br />
					<?php echo $this->Form->input('location_details', array(
                        'label' => false,
                        'class' => 'form-control col-lg-8',
                        'placeholder' => 'Location details (upstairs, room 149, etc.)',
                        'div' => array(
                            'class' => 'col-lg-12'
                        )
                    )); ?>
				</div>
			</td>
		</tr>
		<tr id="eventform_address" <?php if (! $has['address']): ?>style="display: none;"<?php endif; ?>>
			<th>Address</th>
			<td>
				<?php echo $this->Form->input('address', array(
                    'label' => false,
                    'class' => 'form-control',
                    'div' => array(
                        'class'=>'form-group col-lg-8 col-xs-12'
                    )
                )); ?>
			</td>
		</tr>
		<tr>
			<th>Description</th>
			<td>
				<div class="form-group col-lg-8 col-xs-12">
					<?php echo $this->element('tinymce_input', array(
                        'field' => 'Event.description',
                        'div' => false
                    )); ?>
				</div>
				</div>
			</td>
		</tr>
		<tr>
			<th>Tags</th>
			<td id="eventform_tags">
				<div class="form-group col-lg-8 col-xs-12">
					<?php
                        echo $this->element('tags/tag_editing', array(
                            'available_tags' => $available_tags,
                            'selected_tags' => isset($this->request->data['Tag']) ? $this->request->data['Tag'] : array(),
                            'hide_label' => true,
                            'allow_custom' => $logged_in
                        ));
                    ?>
				</div>
			</td>
		</tr>
		<?php if ($logged_in): ?>
			<tr>
				<th>Images</th>
				<td>
					<?php echo $this->element('images/form'); ?>
				</td>
			</tr>
		<?php endif; ?>
		<tr id="eventform_nocost" <?php if ($has['cost']): ?>style="display: none;"<?php endif; ?>>
			<td>
				<a href="#" id="event_add_cost">
					Add cost
				</a>
			</td>
			<td></td>
		</tr>
		<tr id="eventform_hascost" <?php if (! $has['cost']): ?>style="display: none;"<?php endif; ?>>
			<th>Cost</th>
			<td>
				<div class="form-group">
					<?php echo $this->Form->input('cost', array(
                        'maxLength' => 200,
                        'label' => false,
                        'class' => 'form-control col-lg-8',
                        'div' => array(
                            'class'=>'col-lg-12'
                        ),
                        'after' => ' <a href="#" id="event_remove_cost">Remove</a><div class="text-muted">Just leave this blank if the event is free.</div>'
                    )); ?>
				</div>
			</td>
		</tr>
		<tr id="eventform_noages" <?php if ($has['ages']): ?>style="display: none;"<?php endif; ?>>
			<td>
				<a href="#" id="event_add_age_restriction">
					Add&nbsp;age&nbsp;restriction
				</a>
			</td>
			<td></td>
		</tr>
		<tr id="eventform_hasages" <?php if (! $has['ages']): ?>style="display: none;"<?php endif; ?>>
			<th>Age&nbsp;Restriction</th>
			<td>
				<div class="form-group">
					<?php echo $this->Form->input('age_restriction', array(
                        'label' => false,
                        'class' => 'form-control col-lg-8',
                        'div' => array(
                            'class'=>'col-lg-12'
                        ),
                        'maxLength' => 30,
                        'after' => ' <a href="#" id="event_remove_age_restriction">Remove</a><div class="text-muted">Leave this blank if this event has no age restrictions.</div>'
                    )); ?>
				</div>
			</td>
		</tr>
		<tr id="eventform_nosource" <?php if ($has['source']): ?>style="display: none;"<?php endif; ?>>
			<td>
				<a href="#" id="event_add_source">
					Add info source
				</a>
			</td>
			<td></td>
		</tr>
		<tr id="eventform_hassource" <?php if (! $has['source']): ?>style="display: none;"<?php endif; ?>>
			<th>Source</th>
			<td>
				<div class="form-group">
					<?php echo $this->Form->input('source', array(
                        'label' => false,
                        'after' => ' <a href="#" id="event_remove_source">Remove</a><div class="text-muted">Did you get this information from a website, newspaper, flyer, etc?</div>',
                        'class' => 'form-control col-lg-8',
                        'div' => array(
                            'class'=>'col-lg-12'
                        )
                    )); ?>
				</div>
			</td>
		</tr>
		<?php if ($this->action == 'add' && ! $this->Session->read('Auth.User.id')): ?>
			<tr>
				<th>Spam Protection</th>
				<td>
					<?php echo $this->element('recaptcha', array('label' => false)); ?>
				</td>
			</tr>
		<?php endif; ?>
		<tr>
			<th></th>
			<td>
				<?php
                    switch ($this->action) {
                        case 'add':
                            $label = 'Add Event';
                            break;
                        case 'edit':
                            $label = 'Update Event';
                            break;
                        case 'edit_series':
                            $label = 'Update Series';
                            break;
                    }
                    echo $this->Form->submit($label, array('class'=>'btn btn-secondary'));
                ?>
			</td>
		</tr>
	</tbody>
</table>

<?php
    $previous_locations_for_autocomplete = array();
    foreach ($previous_locations as $location => $address) {
        $previous_locations_for_autocomplete[] = array(
            'label' => $location,
            'value' => $address
        );
    }
    $this->Js->buffer('
		eventForm.previousLocations = '.$this->Js->object($previous_locations_for_autocomplete).';
		setupEventForm();
	');
?>
