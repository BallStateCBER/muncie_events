<?php
	// Times
	$start = $event['Event']['date'].' '.$event['Event']['time_start'];
	if ($event['Event']['time_end']) {
		if ($event['Event']['time_start'] < $event['Event']['time_end']) {
			$end = $event['Event']['date'].' '.$event['Event']['time_end'];
		} else {
			$end_date = date('Y-m-d', strtotime($event['Event']['date'].' +1 day'));
			$end = $end_date.' '.$event['Event']['time_end'];
		}
	} else {
		$end = $start;
	}
	
	$summary = $event['Event']['title'];
	
	// Description
	$description = $event['Event']['description'];
	$description = strip_tags($description);
	$description = str_replace('&nbsp;', '', $description);
	$description = Sanitize::clean($description, array(
		'odd_spaces', 'carriage'
	));
	
	$extras = array();
	$extras['UID'] = $event['Event']['id'].'@muncieevents.com';
	$extras['location'] = $event['Event']['location'];
	if ($event['Event']['location_details']) {
		$extras['location'] .= ', '.$event['Event']['location_details'];
	}
	if (trim($event['Event']['address']) != '') {
		$extras['location'] .= ' ('.trim($event['Event']['address']).')';
	}
	$extras['categories'] = $event['Category']['name'];
	if ($event['Event']['source']) {
		$extras['comment'] = 'Info source: '.$event['Event']['source'];
	}
	if ($event['User']['email']) {
		$extras['organizer'] = $event['User']['email'];
	}
	
	$this->iCal->addEvent(
		$start, 
		$end, 
		$summary, 
		$description,
		$extras
	);