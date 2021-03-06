<div id="my_account">
	<h1 class="page_title">
		<?php echo $title_for_layout; ?>
	</h1>

	<ul>
		<li>
			<?php echo $this->Html->link('Change Password', array('controller' => 'users', 'action' => 'change_pass')); ?>
		</li>
		<?php if ($mailing_list_id): ?>
			<li>
				<?php echo $this->Html->link(
                    'Update Mailing List Settings',
                    Router::url(array(
                        'controller' => 'mailing_list',
                        'action' => 'settings',
                        $mailing_list_id,
                        $mailing_list_hash
                    ), true)
                ); ?>
			</li>
		<?php endif; ?>
	</ul>

	<?php echo $this->Form->create('User', array(
        'url' => array(
            'controller' => 'users',
            'action' => 'account'
        )
    )); ?>
	<?php echo $this->Form->input('name', array(
        'label' => 'Name',
        'class' => 'form-control',
        'div' => array(
            'class'=>'form-group col-lg-8 col-xs-12'
        ),
        'after' => '<div class="text-muted">Your first and last actual name, please</div>'
    )); ?>
	<?php echo $this->Form->input('email', array(
        'label' => 'Email',
        'class' => 'form-control',
        'div' => array(
            'class'=>'form-group col-lg-8 col-xs-12'
        )
    )); ?>
	<?php echo $this->Form->submit(__('Update'), array('class'=>'btn btn-secondary')); ?>
</div>
