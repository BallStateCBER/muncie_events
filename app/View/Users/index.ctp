<h1 class="page_title">
	<?php echo $title_for_layout; ?>
</h1>

<?php echo $this->element('pagination'); ?>
	
<ul>
	<?php foreach ($users as $user): ?>
		<li>
			<?php echo $this->Html->link(
				$user['User']['name'], 
				array(
					'controller' => 'users', 
					'action' => 'view', 
					$user['User']['id']
				),
				array(
					'escape' => false
				)
			); ?>
		</li>
	<?php endforeach; ?>
</ul>
	
<?php echo $this->element('pagination'); ?>