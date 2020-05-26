<?php
/**
 * @var \View $this
 * @var array $event
 */
$address = $event['Event']['address'];
$location = $event['Event']['location'];
$locationDetails = $event['Event']['location_details'];
?>
<h1 class="page_title">
	<?php echo $event['Event']['title']; ?>
</h1>

<div class="event">
	<?php
		echo $this->element('events/actions', compact('event'));
		$this->Js->buffer("setupEventActions('.event');");
	?>

	<div class="header_details">
		<table class="details">
			<tr>
				<th>When</th>
				<td>
					<?php echo $this->Calendar->date($event); ?>
					<br />
					<?php echo $this->Calendar->time($event); ?>
				</td>
			</tr>
			<tr>
				<th>Where</th>
				<td>
                    <?= $this->Html->link(
                        $location,
                        [
                            'controller' => 'events',
                            'action' => 'location',
                            $location,
                        ]
                    ) ?>
                    <?php if ($location == 'Virtual Event'): ?>
                        <br />
                        <?= $address ? $this->Text->autoLinkUrls($address) : 'URL not provided' ?>
                    <?php else: ?>
                        <?= $locationDetails ? '<br />' . $locationDetails : null ?>
                        <?= $address ? '<br />' . $address : null ?>
                    <?php endif; ?>
				</td>
			</tr>
			<tr>
				<th>What</th>
				<td class="what">
					<?php
						echo $this->Html->link(
							$this->Icon->category($event['Category']['name']).$event['Category']['name'],
							array('controller' => 'events', 'action' => 'category', $event['Category']['slug']),
							array('escape' => false, 'title' => 'View this category')
						);
						if (! empty($event['Tag'])) {
							$linked_tags = array();
							foreach ($event['Tag'] as $tag) {
								$linked_tags[] = $this->Html->link(
									$tag['name'],
									array(
										'controller' => 'events',
										'action' => 'tag',
										'slug' => $tag['id'].'_'.Inflector::slug($tag['name'])
									),
									array('title' => 'View this tag')
								);
							}
							echo '<span> - '.implode(', ', $linked_tags).'</span>';
						}
					?>
				</td>
			</tr>
			<?php if ($event['Event']['cost']): ?>
				<tr>
					<th>Cost</th>
					<td><?php echo $event['Event']['cost']; ?></td>
				</tr>
			<?php endif; ?>
			<?php if ($event['Event']['age_restriction']): ?>
				<tr>
					<th>Ages</th>
					<td><?php echo $event['Event']['age_restriction']; ?></td>
				</tr>
			<?php endif; ?>
			<?php if ($event['Event']['series_id'] && $event['EventSeries']['title']): ?>
				<tr>
					<th>Series</th>
					<td>
						<?php echo $this->Html->link(
							$event['EventSeries']['title'],
							array('controller' => 'event_series', 'action' => 'view', 'id' => $event['Event']['series_id'])
						); ?>
					</td>
				</tr>
			<?php endif; ?>
		</table>
	</div>
	<div class="description">
		<?php if (! empty($event['EventsImage'])): ?>
			<div class="images">
				<?php foreach ($event['EventsImage'] as $image): ?>
					<?php echo $this->Calendar->thumbnail('small', array(
						'filename' => $image['Image']['filename'],
						'caption' => $image['caption'],
						'group' => 'event'.$event['Event']['id']
					)); ?>
					<?php if ($image['caption']): ?>
						<span class="caption">
							<?php echo $image['caption']; ?>
						</span>
					<?php endif; ?>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
		<?php echo $this->Text->autoLink($event['Event']['description'], array(
			'escape' => false
		)); ?>
	</div>

	<div class="footer_details">
		<p>
			<?php if (! $event['User']['id']): ?>
				Added anonymously
			<?php elseif (! $event['User']['name']): ?>
				Added by a user whose account no longer exists
			<?php else: ?>
				Author: <?php echo $this->Html->link(
					$event['User']['name'],
					array('controller' => 'users', 'action' => 'view', 'id' => $event['User']['id'])
				); ?>
			<?php endif; ?>

			<?php if ($event['Event']['source']): ?>
				<br />
				Source:
				<?php echo $this->Text->autoLink($event['Event']['source']); ?>
			<?php endif; ?>
		</p>
	</div>
</div>