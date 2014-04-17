<?php
	$leave_open = (isset($open_only_event) && $open_only_event && count($events) == 1);
?>

<ul class="event_accordion">
	<?php foreach ($events as $event): ?>
		<li <?php if (! empty($event['EventsImage'])): ?>class="with_images"<?php endif; ?>>
			<?php 
				$url = Router::url(array(
					'controller' => 'events',
					'action' => 'view',
					'id' => $event['Event']['id']
				), true);
			?>
			<?php if (! empty($event['EventsImage'])): ?>
				<span class="tiny_thumbnails">
					<?php
						foreach ($event['EventsImage'] as $image) {
							echo $this->Calendar->thumbnail('tiny', array(
								'filename' => $image['Image']['filename'],
								'caption' => $image['caption'],
								'group' => 'event'.$event['Event']['id'].'_tiny_tn'
							));
						}
					?>
				</span>
			<?php endif; ?>
			<a href="<?php echo $url; ?>" title="Click for more info" class="more_info_handle" id="more_info_handle_<?php echo $event['Event']['id']; ?>" data-event-id="<?php echo $event['Event']['id']; ?>">
				<?php echo $this->Icon->category($event['Category']['name']); ?>
				<span class="title">
					<?php echo $event['Event']['title']; ?>
				</span>
				<span class="when">
					<?php echo $this->Calendar->eventTime($event); ?>
					@
				</span>
				<span class="where">
					<?php echo $event['Event']['location'] ? $event['Event']['location'] : '&nbsp;'; ?>
					<?php if ($event['Event']['location_details']): ?>
						<span class="location_details" <?php if (! $leave_open): ?>style="display: none;"<?php endif; ?>>
							<?php echo $event['Event']['location_details']; ?>
						</span>
					<?php endif; ?>
					<?php if ($event['Event']['address']): ?>
						<span class="address" id="address_<?php echo $event['Event']['id']; ?>" <?php if (! $leave_open): ?>style="display: none;"<?php endif; ?>>
							<?php echo $event['Event']['address']; ?>
						</span>
					<?php endif; ?>
				</span>
			</a>
			<div class="more_info" id="more_info_<?php echo $event['Event']['id']; ?>" <?php if (! $leave_open): ?>style="display: none;"<?php endif; ?>>
				<div>
					<?php echo $this->element('events/actions', compact('event')); ?>
					
					<?php if ($event['Event']['cost'] || $event['Event']['age_restriction']): ?>
						<div class="details">
							<table>
								<?php if ($event['Event']['cost']): ?>
									<tr class="cost">
										<th>Cost:</th>
										<td><?php echo $event['Event']['cost']; ?></td>
									</tr>
								<?php endif; ?>
								<?php if ($event['Event']['age_restriction']): ?>
									<tr class="age_restriction detail" id="age_restriction_<?php echo $event['Event']['id']; ?>">
										<th>Ages:</th>
										<td><?php echo $event['Event']['age_restriction']; ?></td>
									</tr>
								<?php endif; ?>
							</table>
						</div>
					<?php endif; ?>
					
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
						<?php if ($event['Event']['description']): ?>
							<?php echo $this->Text->autolink($event['Event']['description'], array('escape' => false)); ?>
						<?php endif; ?>
					</div>
					
					<table class="details">
						<?php if (! empty($event['Tag'])): ?>
							<tr class="tags">
								<th>Tags:</th>
								<td>
									<?php echo $this->Calendar->eventTags($event); ?>
								</td>
							</tr>
						<?php endif; ?>
						<?php if (! empty($event['Event']['series_id']) && ! empty($event['EventSeries']['title'])): ?>
							<tr class="tags">
								<th>Series:</th>
								<td>
									<?php echo $this->Html->link($event['EventSeries']['title'], array(
										'controller' => 'event_series', 
										'action' => 'view', 
										'id' => $event['EventSeries']['id']
									)); ?>
								</td>
							</tr>
						<?php endif; ?>
						<?php if ($event['Event']['source']): ?>
							<tr class="source">
								<th>Source:</th>
								<td><?php echo $this->Text->autoLink($event['Event']['source']); ?></td>
							</tr>
						<?php endif; ?>
						<tr class="link">
							<th>Link:</th>
							<td>
								<?php echo $this->Html->link($url, $url); ?>
							</td>
						</tr>
						<?php if (isset($event['User']['name']) && $event['User']['name']): ?>
							<tr class="author">
								<th>
									Author:
								</th>
								<td>
									 <?php echo $this->Html->link($event['User']['name'], 
									 	array('controller' => 'users', 'action' => 'view', 'id' => $event['User']['id'])
									 ); ?>
								</td>
							</tr>
						<?php endif; ?>
					</table>
				</div>
			</div>
		</li>
	<?php endforeach; ?>
</ul>
<?php 
	if ($leave_open) {
		$this->Js->buffer("
			$('.event_accordion a.tn_tiny').hide();
		");
	}
?>