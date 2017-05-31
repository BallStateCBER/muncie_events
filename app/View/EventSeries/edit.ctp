<?php
    /* TO DO:
     * 		If event->delete is checked, have confirmation dialogue box pop up upon hitting submit
     * 		Add (functionally useless) [done] buttons to compliment [edit] buttons
     */
?>
<h1 class="page_title">
	<?php echo $title_for_layout; ?>
</h1>

<p class="alert alert-info">
	Here, you can edit the name of your event series and edit basic information about each event.
	To edit other details of
	<?php echo $this->Html->link(
        'your events',
        array(
            'controller' => 'events',
            'action' => 'mine'
        )
    ); ?>, you'll have to go to each event's individual edit page.
</p>

<?php echo $this->Form->create('EventSeries', array('url' => array(
    'controller' => 'event_series', 'action' => 'edit'
))); ?>
<table class="event_form event_series_form">
	<tbody>
		<tr>
			<th>Series</th>
			<td><?php
                echo $this->Form->input('title', array(
                    'label' => false,
                    'class' => 'form-control',
                    'div' => false,
                ));
            ?></td>
		</tr>
		<tr>
			<th>Events</th>
			<td>
				<?php if (empty($this->request->data['Event'])): ?>
					Weird. This event series doesn't actually have any events linked to it.
				<?php else: ?>
					<table id="events_in_series">
						<tbody>
							<?php foreach ($this->request->data['Event'] as $event): ?>
								<tr class="display" id="eventinseries_display_<?php echo $event['id']; ?>">
									<td class="action">
										<a href="#" class="toggler" data-event-id="<?php echo $event['id']; ?>">
											Edit
										</a>
									</td>
									<td class="date" id="eventinseries_display_<?php echo $event['id']; ?>_date">
										<?php echo date('M j, Y', strtotime($event['date'])); ?>
									</td>
									<td class="time" id="eventinseries_display_<?php echo $event['id']; ?>_time">
										<?php echo date('g:ia', strtotime($event['time_start'])); ?>
									</td>
									<td class="title" id="eventinseries_display_<?php echo $event['id']; ?>_title">
										<?php echo $event['title']; ?>
									</td>
								</tr>
								<tr class="edit" id="eventinseries_edit_<?php echo $event['id']; ?>" style="display: none;">
									<td class="action">
										<a href="#" class="toggler" data-event-id="<?php echo $event['id']; ?>">
											Done
										</a>
									</td>
									<td colspan="3">
										<table class="edit_event_in_series event_form">
											<tr>
												<th>Date</th>
												<td>
													<?php echo $this->Form->input('Event.'.$event['id'].'.date', array(
                                                        'class' => 'form-control event_time_form',
                                                        'div' => array(
                                                            'id' => 'eventform_timestart_div',
                                                        ),
                                                        'label' => false,
                                                        'type' => 'date',
                                                        'dateFormat' => 'MDY',
                                                        'minYear' => min(date('Y'), substr($event['date'], 0, 4)),
                                                        'maxYear' => date('Y') + 1,
                                                        'default' => $event['date']
                                                    )); ?>
												</td>
											</tr>
											<tr>
												<th>Time</th>
												<td>
													<?php echo $this->Form->input('Event.'.$event['id'].'.time_start', $options = array(
                                                        'class' => 'form-control event_time_form',
                                                        'label' => false,
                                                        'interval' => 5,
                                                        'div' => array(
                                                            'id' => 'eventform_timestart_div',
                                                        ),
                                                        'default' => $event['time_start']
                                                    )); ?>
												</td>
											</tr>
											<tr>
												<th>Title</th>
												<td>
													<?php echo $this->Form->input('Event.'.$event['id'].'.title', array(
                                                        'class' => 'form-control',
                                                        'div' => false,
                                                        'label' => false,
                                                        'default' => $event['title'],
                                                        //'maxLength' => 100
                                                    )); ?>
												</td>
											</tr>
											<tr>
												<th>
													<label for="eventinseries_delete_<?php echo $event['id']; ?>">Delete</label>
												</th>
												<td>
													<?php echo $this->Form->checkbox('Event.'.$event['id'].'.delete', array(
                                                        'id' => 'eventinseries_delete_'.$event['id'],
                                                        'class' => 'delete_event',
                                                        'data-event-id' => $event['id']
                                                    )); ?>
													<?php echo $this->Form->hidden('Event.'.$event['id'].'.edited', array(
                                                        'id' => 'eventinseries_edited_'.$event['id'],
                                                        'value' => 0
                                                    )); ?>
												</td>
											</tr>
										</table>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</td>
		</tr>
		<tr>
			<th>Delete</th>
			<td>
				<?php echo $this->Form->checkbox('delete', array(
                    'id' => 'event_series_delete_confirm',
                    'after' => '<div class="footnote">Click to delete all events.</div>',
                )); ?>
			</td>
		</tr>
		<tr>
			<th></th>
			<td>
				<?php echo $this->Form->end('Update Series'); ?>
			</td>
	</tbody>
</table>

<?php
    $this->Html->script('event_form.js', array('inline' => false));
    $this->Js->buffer("
		event_ids = ".$this->Js->object($event_ids).";
		setup_eventseries_edit_form();
	");
?>
