<?php
	/*
	if (! isset($upcoming_event_tags)) {
		$upcoming_event_tags = $this->requestAction(array('controller' => 'events', 'action' => 'upcoming_tags'));
	}
	*/
?>
<div class="tag_cloud">
	<?php if (empty($upcoming_event_tags)): ?>
		<p class="empty">
			Sorry, no tags were found for upcoming events in the selected
			<?php echo count($filter['categories']) == 1 ? 'category' : 'categories'; ?>.
		</p>
	<?php else: ?>
		<?php
			$min_count = $max_count = null;
			foreach ($upcoming_event_tags as $tag_name => $tag_info) {
				if ($min_count == null) {
					$min_count = $max_count = $tag_info['count'];
				}
				if ($tag_info['count'] < $min_count) {
					$min_count = $tag_info['count'];
				}
				if ($tag_info['count'] > $max_count) {
					$max_count = $tag_info['count'];
				}
			}
			$count_range = max($max_count - $min_count, 1);
			$min_font_size = 80;
			$max_font_size = 170;
			$font_size_range = $max_font_size - $min_font_size;
		?>
		<?php foreach ($upcoming_event_tags as $tag_name => $tag_info): ?>
			<?php
				// Alternate sizing method
				//$interval = ($max_font_size - $min_font_size) / 10;
				//$font_size = min($max_font_size, round($min_font_size + ($tag_info['count'] * $interval)));
				$font_size = $min_font_size + round($font_size_range * (($tag_info['count'] - $min_count) / $count_range));
				echo $this->Html->link(
					'<span style="font-size: '.$font_size.'%;">'.$tag_name.'</span>',
					array(
						'controller' => 'events', 
						'action' => 'index', 
						'tag' => $tag_info['id'].'_'.Inflector::slug($tag_name)
					),
					array(
						'escape' => false, 
						'id' => 'filter_tag_'.$tag_info['id'],
						'data-tag-slug' => $tag_info['id'].'_'.Inflector::slug($tag_name)
					)
				);
			?>
			<?php 
				// $this->Js->buffer("setTagFilterListener('filter_tag_{$tag_info['id']}', '{$tag_info['id']}_".Inflector::slug($tag_name)."');");
			?>
		<?php endforeach; ?>
	<?php endif; ?>
</div>
<?php $this->Js->buffer("setupTagCloud();"); ?>