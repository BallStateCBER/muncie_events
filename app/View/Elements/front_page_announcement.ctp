<div id="front_page_announcement" class="alert alert-info">
	<h2>What is Muncie Events?</h2>
	<p>
		A free service that distributes event information for Muncie, IN through a
		<?php echo $this->Html->link('network of online calendars', array(
			'controller' => 'widgets',
			'action' => 'index'
		)); ?>,
        a
        <a href="https://play.google.com/store/apps/details?id=com.muncieevents.MuncieEvents&hl=en_US">mobile app</a>,
		and a personalized
		<?php echo $this->Html->link('mailing list', array(
			'controller' => 'mailing_list',
			'action' => 'join'
		)); ?>.
		If you like what we're doing, please
		<a href="http://facebook.com/MuncieEvents">like us on Facebook</a>,
		<a href="/contact">give us your feedback</a>, and
		<a href="/about">read more</a> about the project.
	</p>
</div>
