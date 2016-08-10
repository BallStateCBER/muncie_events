<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en-US" xmlns:fb="https://www.facebook.com/2008/fbml">
<head prefix="og: http://ogp.me/ns# muncieevents: http://ogp.me/ns/apps/muncieevents#">
	<link rel="dns-prefetch" href="//ajax.googleapis.com" />
	<?php echo $this->Html->charset(); ?>
	<title>
		<?php if ($title_for_layout): ?>
			<?php echo strip_tags($title_for_layout); ?> -
		<?php endif; ?>
		Muncie Events
	</title>
	<link rel="shortcut icon" type="image/x-icon" href="/favicon.ico" />
	<?php echo $this->element('og_meta_tags'); ?>
	<?php
		echo $this->Html->meta('icon');
		echo $this->fetch('meta');
		echo $this->Html->css('/magnific-popup/magnific-popup.css');
		echo $this->Html->css('/jquery_ui/css/smoothness/jquery-ui-1.10.2.custom.min.css');
		echo $this->Html->css('style');
		echo $this->fetch('css');
	?>
</head>
<body class="layout_<?php echo $this->layout; ?>">
	<?php echo $this->element('flash_messages'); ?>
	<div id="header">
		<div class="clearfix">
			<?php echo $this->element('header'); ?>
		</div>
	</div>
	<div id="divider"></div>
	<div id="body">
		<?php if ($this->params['controller'] == 'pages' && $this->action == 'home'): ?>
			<?php echo $this->element('front_page_announcement'); ?>
		<?php endif; ?>
		<?php echo $this->fetch('content'); ?>
	</div>
	<div id="footer">
		<?php echo $this->element('footer'); ?>
	</div>
	<noscript>
		<div id="noscript">
			JavaScript is currently disabled in your browser.
			For full functionality of this website, JavaScript must be enabled.
			If you need assistance, <a href="http://www.enable-javascript.com/" target="_blank">Enable-JavaScript.com</a> provides instructions.
		</div>
	</noscript>
	<?php
		/*
		// Grab Google CDN's jQuery, with a protocol relative URL; fall back to local if offline ?>
		<script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js"></script>
		<script>window.jQuery || document.write('<script src="/js/jquery-1.8.3.min.js"><\/script>')</script>
		*/
	?>
	<script src="/js/jquery-3.1.0.min.js"></script>
	<script src="/jquery_ui/js/jquery-ui-1.10.2.custom.js"></script>
	<script src="/js/jquery.watermark.min.js"></script>
	<script src="/js/script.js"></script>
	<?php echo $this->fetch('script'); ?>
	<script type="text/javascript" src="/magnific-popup/jquery.magnific-popup.min.js"></script>
	<script type="text/javascript" src="/js/image_popups.js"></script>
	<?php
		$this->Js->buffer("muncieEventsImagePopups.prepare();");
		echo $this->Js->writeBuffer();
		echo $this->element('analytics');
		echo $this->Facebook->init();
	?>
</body>
</html>
