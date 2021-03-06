<?php
    $user_id = $this->Session->read('Auth.User.id');
    $user_role = $this->Session->read('Auth.User.role');
    if (!isset($can_edit)) {
        $can_edit = $user_id && ($user_role == 'admin' || $user_id == $event['Event']['user_id']);
    }
    $event_url = Router::url(array(
        'controller' => 'events',
        'action' => 'view',
        'id' => $event['Event']['id']
    ), true);
?>
<div class="actions">
	<?php echo $this->Facebook->like(array(
        'href' => $event_url,
        'show_faces' => false,
        'layout' => 'button_count',
        'app_id' => '496726620385625'
    )); ?>
	<div class="export_options_container">
		<?php echo $this->Html->link(
            $this->Html->image('/img/icons/calendar--arrow.png').'Export',
            '#',
            array(
                'escape' => false,
                'title' => 'Export to another calendar application',
                'id' => 'export_event_'.$event['Event']['id'],
                'class' => 'export_options_toggler'
            )
        ); ?>
		<div class="export_options" style="display: none;">
			<?php echo $this->Html->link(
                'iCal',
                array(
                    'controller' => 'events',
                    'action' => 'view',
                    'id' => $event['Event']['id'],
                    'ext' => 'ics'
                ),
                array(
                    'title' => 'Download iCalendar (.ICS) file'
                )
            ); ?>
			<?php
                // Determine UTC "YYYYMMDDTHHMMSS" start/end values
                $start_stamp = strtotime($event['Event']['date'].' '.$event['Event']['time_start']);
                $start_est = date('Ymd', $start_stamp).'T'.date('Hi00', $start_stamp);
                $start_utc = gmdate('Ymd', $start_stamp).'T'.gmdate('Hi00', $start_stamp).'Z';
                $end_string = $event['Event']['date'].' ';
                if ($event['Event']['time_end']) {
                    $end_string .= $event['Event']['time_end'];
                } else {
                    $end_string .= $event['Event']['time_start'];
                }
                if ($event['Event']['time_start'] > $event['Event']['time_end']) {
                    $end_string .= ' +1 day';
                }
                $end_stamp = strtotime($end_string);
                $end_est = date('Ymd', $end_stamp).'T'.date('Hi00', $end_stamp);
                $end_utc = gmdate('Ymd', $end_stamp).'T'.gmdate('Hi00', $end_stamp).'Z';

                // Clean up and truncate description
                $description = $event['Event']['description'];
                $description = strip_tags($description);
                $description = str_replace('&nbsp;', '', $description);
                $description = Sanitize::clean($description, array(
                    'odd_spaces', 'carriage'
                ));
                $description = $this->Text->truncate(
                    $description,
                    1000,
                    array(
                        'ellipsis' => "... (continued at $event_url)",
                        'exact' => false
                    )
                );

                /* In parentheses after the location name, the address has
                 * 'Muncie, IN' tacked onto the end if 'Muncie' is not
                 * mentioned in it. */
                $address = trim($event['Event']['address']);
                if ($address == '') {
                    $address = 'Muncie, IN';
                } elseif (stripos($address, 'Muncie') === false) {
                    $address .= ', Muncie, IN';
                }
                $location = $event['Event']['location'];
                if ($event['Event']['location_details']) {
                    $location .= ', '.$event['Event']['location_details'];
                }
                $location .= ' ('.$address.')';

                $google_cal_url = 'http://www.google.com/calendar/event?action=TEMPLATE';
                $google_cal_url .= '&text='.urlencode($event['Event']['title']);
                $google_cal_url .= '&dates='.$start_utc.'/'.$end_utc;
                $google_cal_url .= '&details='.urlencode($description);
                $google_cal_url .= '&location='.urlencode($location);
                $google_cal_url .= '&trp=false';
                $google_cal_url .= '&sprop=Muncie%20Events';
                $google_cal_url .= '&sprop=name:http%3A%2F%2Fmuncieevents.com';

                echo $this->Html->link(
                    'Google',
                    $google_cal_url,
                    array(
                        'title' => 'Add to Google Calendar'
                    )
                );
            ?>
			<?php echo $this->Html->link(
                'Outlook',
                array(
                    'controller' => 'events',
                    'action' => 'view',
                    'id' => $event['Event']['id'],
                    'ext' => 'ics'
                ),
                array(
                    'title' => 'Add to Microsoft Outlook'
                )
            ); ?>
			<?php
                $location = $event['Event']['location'];
                if ($event['Event']['location_details']) {
                    $location .= ', '.$event['Event']['location_details'];
                }
                $yahoo_cal_url = 'http://calendar.yahoo.com/?';
                $yahoo_cal_url .= 'in_loc='.urlencode($location);
                $yahoo_cal_url .= '&in_st='.urlencode($event['Event']['address']);
                $yahoo_cal_url .= '&in_csz='.urlencode('Muncie, IN');
                $yahoo_cal_url .= '&TITLE='.urlencode($event['Event']['title']);
                $yahoo_cal_url .= '&URL='.urlencode($event_url);
                $yahoo_cal_url .= '&ST='.$start_est;
                if ($start_est != $end_est) {
                    $yahoo_cal_url .= '&ET='.$end_est;
                }
                $yahoo_cal_url .= '&DESC='.urlencode($description);
                $yahoo_cal_url .= '&v=60';
                echo $this->Html->link(
                    'Yahoo!',
                    $yahoo_cal_url,
                    array(
                        'title' => 'Add to Yahoo! Calendar'
                    )
                );
            ?>
		</div>
	</div>
	<?php if ($user_role == 'admin' && ! $event['Event']['approved_by']): ?>
		<?php echo $this->Html->link(
            $this->Html->image('/img/icons/tick.png').'Approve',
            array(
                'controller' => 'events',
                'action' => 'approve',
                'id' => $event['Event']['id']
            ),
            array('escape' => false)
        ); ?>
	<?php endif; ?>
	<?php if ($can_edit): ?>
		<?php echo $this->Html->link(
            $this->Html->image('/img/icons/pencil.png').'Edit',
            array(
                'controller' => 'events',
                'action' => 'edit',
                'id' => $event['Event']['id']
            ),
            array('escape' => false)
        ); ?>
		<?php echo $this->Form->postLink(
            $this->Html->image('/img/icons/cross.png').'Delete',
            array(
                'controller' => 'events',
                'action' => 'delete',
                'id' => $event['Event']['id']
            ),
            array('escape' => false),
            'Are you sure that you want to delete this event?'
        ); ?>
	<?php endif; ?>
</div>
