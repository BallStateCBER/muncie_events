<h1 class="page_title">
	<?php echo $title_for_layout; ?>
</h1>

<div class="prev_next_day">
	<?= $this->Calendar->prevDay(mktime(0, 0, 0, (int)$month, (int)$day - 1, (int)$year)) ?>
	<?= $this->Calendar->nextDay(mktime(0, 0, 0, (int)$month, (int)$day + 1, (int)$year)) ?>
</div>
<?php echo $this->element('events/date_picker', array('default' => "$month/$day/$year")); ?>

<?php if (empty($events)): ?>
	<p class="alert alert-info">
		Sorry, but no events
		<?php if ("$month$day$year" >= date('mdY')): ?>
			have been
		<?php else: ?>
			were
		<?php endif; ?>
		posted for this date.
		<br />
		If you know of an event happening on this date,
		<?php echo $this->Html->link('tell us about it', array(
			'controller' => 'events',
			'action' => 'add',
			'm' => $month,
			'd' => $day,
			'y' => $year
		)); ?>.
	</p>
<?php else: ?>
	<?php echo $this->element('events/accordion', array('open_only_event' => true)); ?>
<?php endif; ?>
