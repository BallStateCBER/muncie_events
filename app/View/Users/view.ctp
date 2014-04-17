<?php 
	$logged_in = (boolean) $this->Session->read('Auth.User.id');
	$user_role = $this->Session->read('Auth.User.role');
	$this->Paginator->options(array(
		'url' => array(
			'controller' => 'users', 
			'action' => 'view', 
			'id' => $user['User']['id']
		)
	));
?>
<div id="user_view">
	<h1 class="page_title">
		<?php echo $user['User']['name']; ?>
	</h1>
	
	<span class="email">
		<?php if ($logged_in): ?>
			<a href="mailto:<?php echo $user['User']['email']; ?>">
				<?php echo $user['User']['email']; ?>
			</a>
		<?php else: ?>
			<?php echo $this->Html->link('Log in', array(
				'controller' => 'users', 'action' => 'login'
			)); ?> to view email address.
		<?php endif; ?>
	</span>
	
	<?php if ($event_count): ?>
		<h2>
			<?php echo $event_count; ?> Contributed Event<?php echo $event_count == 1 ? '' : 's'; ?>:
		</h2>
		
		<?php echo $this->element('pagination'); ?>
		
		<?php foreach ($events as $date => $days_events): ?>
			<?php echo $this->Calendar->dayHeaders($date); ?>
			<?php echo $this->element('events/accordion_day', array(
				'events' => $days_events
			)); ?>
		<?php endforeach; ?>
			
		<?php echo $this->element('pagination'); ?>
			
		<?php $this->Js->buffer("setupEventAccordion();"); ?>
	<?php else: ?>
		<p class="notification_message">
			This user has not posted any events.
		</p>
	<?php endif; ?>
</div>