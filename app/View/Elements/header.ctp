<div class="row">
	<div class="col-sm-12 col-lg-4">
		<h1>
			<a href="/">
				<i class="icon-me-logo"></i>
				<div class="title"><span>Muncie</span><span>Events</span></div>
			</a>
		</h1>
	</div>
	<div class="col-sm-12 col-lg-8">
		<div id="search_form" class="col-sm-12 col-md-6">
			<?php echo $this->element('header/search'); ?>
		</div>
		<div id="header_corner" class="col-sm-12 col-md-6">
			<span class="tagline">
				<?php echo $this->element('header/tagline'); ?>
			</span>
				<?php echo $this->element('header/links_secondary'); ?>
		</div>
		<div id="header_nav">
			<?php echo $this->element('header/links_primary'); ?>
		</div>
	</div>
</div>
