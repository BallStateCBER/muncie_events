<style>
	<?php include('..'.DS.'webroot'.DS.'css'.DS.'email.css'); ?>
</style>

<h1>
	<a href="http://muncieevents.com">
		<img src="http://muncieevents.com/img/email_logo.png" alt="Muncie Events" />
	</a>
</h1>

<?php if ($welcome_message): ?>
	<p>
		<?php echo $this->Text->autoLink($welcome_message); ?>
	</p>
<?php endif; ?>

<div>
	<h3 class="day">
		<?php echo date('l').' <span class="date">'.date('F j').'<sup>'.date('S').'</sup></span>'; ?>
	</h3>
	<?php foreach ($events as $event): ?>
		<p class="event">
			<?php echo $this->Icon->category($event['Category']['name'], 'email'); ?>

			<?php echo $this->Html->link(
				$event['Event']['title'], 
				Router::url(array(
					'controller' => 'events',
					'action' => 'view',
					'id' => $event['Event']['id']
				), true)
			); ?>
			<br />
			<?php echo date('g:ia', strtotime($event['Event']['time_start'])); ?>
			<?php if ($event['Event']['time_end']): ?>
				- <?php echo date('g:ia', strtotime($event['Event']['time_end'])); ?>
			<?php endif; ?>
			@
			<?php echo $event['Event']['location']; ?>
		</p>
	<?php endforeach; ?>
</div>

<p class="footnote">
	<strong>Your settings...</strong><br />
	Frequency: <?php echo $settings_display['frequency']; ?><br />
	Events: <?php echo $settings_display['event_types']; ?>
</p>

<p class="footnote">
	This email was sent to <?php echo $recipient_email; ?> 
	on behalf of <a href="http://muncieevents.com">MuncieEvents.com</a>
	<br />
	<?php echo $this->Html->link(
		'Add Event', 
		Router::url(array(
			'controller' => 'events',
			'action' => 'add'
		), true)
	); ?>
	&nbsp; | &nbsp; 
	<?php echo $this->Html->link(
		'Change Settings', 
		Router::url(array(
			'controller' => 'mailing_list',
			'action' => 'settings',
			$recipient_id,
			$hash
		), true)
	); ?>
	&nbsp; | &nbsp; 
	<?php echo $this->Html->link(
		'Unsubscribe',
		Router::url(array(
			'controller' => 'mailing_list',
			'action' => 'settings',
			$recipient_id,
			$hash,
			'?' => 'unsubscribe'
		), true)
	); ?>
</p>