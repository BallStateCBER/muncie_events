<?php
	$total_pages = $this->Paginator->counter(array('format' => '{:pages}'));
	$current_page = $this->Paginator->counter(array('format' => '{:page}'));
	$paginator_url = urldecode(Router::url(array_merge(
		$this->request->params['named'],
		$this->request->params['pass'],
		array('page' => '{page}')	// Replaced by JS with the appropriate page number
	), true));
?>
<?php if ($total_pages > 1): ?>
	<div class="pagination">
	
		<?php
			$first = $this->Paginator->first('&laquo;', array('escape' => false));
			echo $first ? $first : '<span>&laquo;</span>';
		?>
		
		<?php
			if (true || $this->Paginator->hasPrev()) {
				echo $this->Paginator->prev('&lsaquo;', array(
					'escape' => false
				));
			}
		?>
		
		<select class="paginator_select" data-url="<?php echo $paginator_url; ?>">
			<?php for ($p = 1; $p <= $total_pages; $p++): ?>
				<option <?php if ($p == $current_page): ?>selected="selected"<?php endif; ?>>
					<?php echo $p; ?>
				</option>
			<?php endfor; ?>
		</select>
		<?php $this->Js->buffer("setupPagination();"); ?>
		
		<?php
			if (true || $this->Paginator->hasNext()) {
				echo $this->Paginator->next('&rsaquo;', array(
					'escape' => false
				));
			}
		?>
		
		<?php 
			$last = $this->Paginator->last("$total_pages &raquo;", array('escape' => false));
			echo $last ? $last : "<span>$total_pages &raquo;</span>";
		?>
	</div>
<?php endif; ?>