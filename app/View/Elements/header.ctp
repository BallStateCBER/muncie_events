<div class="row">
	<div class="col-xs-12 col-md-6 col-lg-4 col-xl-3">
		<h1>
			<a href="/">
				<i class="icon-me-logo"></i>
				<div class="title"><span>Muncie</span><span>Events</span></div>
			</a>
		</h1>
	</div>
	<div class="col-xs-12 col-md-6 col-lg-8 col-xl-9">
		<div id="header_corner" class="col-sm-12">
			<span class="tagline">
				<?php echo $this->element('header/tagline'); ?>
			</span>
			<?php echo $this->element('header/links_secondary'); ?>
		</div>
		<div id="header_nav" class="col-xl-8">
			<?php echo $this->element('header/links_primary'); ?>
		</div>
		<div id="search_form" class="col-sm-12 col-xl-4">
			<?php echo $this->element('header/search'); ?>
		</div>
	</div>
</div>
