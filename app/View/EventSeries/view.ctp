<?php
	$user_id = $this->Session->read('Auth.User.id');
	$user_role = $this->Session->read('Auth.User.role');
	$can_edit = $user_id && ($user_role == 'admin' || $user_id == $event['EventSeries']['user_id']);
?> 

<h1 class="page_title">
	<?php echo $title_for_layout; ?>
</h1>

<div class="event_series">
	<?php if ($can_edit): ?>
		<div class="controls">
			<?php echo $this->Html->link(
				$this->Html->image('/img/icons/pencil.png').'Edit',
				array('controller' => 'event_series', 'action' => 'edit', 'id' => $series['EventSeries']['id']),
				array('escape' => false)
			); ?>
			&nbsp;
			<?php echo $this->Form->postLink(
				$this->Html->image('/img/icons/cross.png').'Delete',
				array('controller' => 'event_series', 'action' => 'delete', 'id' => $series['EventSeries']['id']),
				array('escape' => false),
				'Are you sure that you want to delete this event series? All events will be permanently deleted.'
			); ?>
		</div>
	<?php endif; ?>
	
	<?php
		$divided_events = array('upcoming' => array(), 'past' => array());
		foreach ($series['Event'] as $key => $event) {
			if ($event['date'] < date('Y-m-d')) {
				$divided_events['past'][] = $event;
			} else {
				$divided_events['upcoming'][] = $event;
			}
		}
		rsort($divided_events['past']);
	?>
	<?php foreach ($divided_events as $section => $events): ?>
		<?php if (empty($events)) continue; ?>
		<h2>
			<?php echo ucwords($section); ?> Events
		</h2>
		<table>
			<tbody>
				<?php foreach ($events as $key => $event): ?>
					<tr>
						<td>
							<?php echo date('M j, Y', strtotime($event['date'])); ?>
						</td>
						<td>
							<?php echo date('g:ia', strtotime($event['time_start'])); ?>
						</td>
						<td>
							<?php echo $this->Html->link($event['title'],
								array('controller' => 'events', 'action' => 'view', 'id' => $event['id'])
							); ?>
						</td>
					</tr>
				<?php endforeach;?>
			</tbody>
		</table>
	<?php endforeach; ?>
	
	<p class="author">
		<?php if (isset($series['User']['name'])): ?>
			Author:
			<?php echo $this->Html->link($series['User']['name'], array(
				'controller' => 'users', 'action' => 'view', 'id' => $series['User']['id']
			)); ?>
		<?php else: ?>
			This event series was posted anonymously.
		<?php endif; ?>
	</p>
</div>