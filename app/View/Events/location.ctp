<h1 class="page_title">
	<?php
		$total = $this->Paginator->counter(array('format' => '{:count}'));
		echo "$total ";
		if ($direction == 'future') {
			echo 'Upcoming ';
		} elseif ($direction == 'past') {
			echo 'Past ';
		}
		$isVirtual = ($location == 'Virtual Event');
		$eventNoun = __n('Event', 'Events', $total);
		echo $isVirtual ? "Virtual $eventNoun" : "$eventNoun at $location";
	?>
</h1>

<?php if ($count_other_direction !== false): ?>
	<?php if ($count_other_direction): ?>
		<?php
            $eventNoun = __n(' event', ' events', $count_other_direction);
            $linkText = sprintf(
                '%s %s %s',
                $count_other_direction,
                ($direction == 'future') ? 'past' : 'upcoming',
                $isVirtual ? "virtual $eventNoun" : "$eventNoun at $location"
            );
			echo $this->Html->link($linkText, array(
				'controller' => 'events',
				'action' => 'location',
				'location' => $location,
				'direction' => ($direction == 'future') ? 'past' : 'future'
			));
		?>
	<?php else: ?>
		<p class="light_text">
			There are no <?php echo (($direction == 'future') ? 'past' : 'upcoming'); ?>
            <?= $isVirtual ? 'virtual events' : "events at $location" ?>
		</p>
	<?php endif; ?>
<?php endif; ?>

<?php if (isset($events) && ! empty($events)): ?>

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
	<p class="alert alert-info">
		No events found.
	</p>
<?php endif; ?>
