Events for <?php echo $date; ?>

brought to you by http://MuncieEvents.com

<?php if ($welcome_message) echo "$welcome_message\n\n"; ?>

<?php
	foreach ($events as $event) {
		echo 
			strtoupper($event['Category']['name']).
			': '.
			$event['Event']['title'].
			"\n".
			'['.
			Router::url(array(
				'controller' => 'events',
				'action' => 'view',
				'id' => $event['Event']['id']
			), true).
			']'.
			"\n".
			date('g:ia', strtotime($event['Event']['time_start']));
		if ($event['Event']['time_end']) {
			echo ' - '.date('g:ia', strtotime($event['Event']['time_end']));
		}
		echo 
			' @ '.
			$event['Event']['location'].
			"\n\n";
	}
?>

Your settings...
Frequency: <?php echo $settings_display['frequency']; ?>

Events: <?php echo $settings_display['event_types']; ?>


This email was sent to <?php echo $recipient_email; ?> on behalf of http://MuncieEvents.com
		
Add Event: <?php echo Router::url(array(
	'controller' => 'events',
	'action' => 'add'
), true); ?>

Change Settings: <?php echo Router::url(array(
	'controller' => 'mailing_list',
	'action' => 'settings',
	$recipient_id,
	$hash
), true); ?>

Unsubscribe: <?php echo Router::url(array(
	'controller' => 'mailing_list',
	'action' => 'settings',
	$recipient_id,
	$hash,
	'?' => 'unsubscribe'
), true); ?>