<?php
	$default_og_meta_tags = array(
		'og:title' => $title_for_layout,
		'og:type' => 'website', // was muncieevents:website
		'og:url' => Router::url(array(), true),
		'og:image' => 'http://muncieevents.com/img/facebook_logo.png',
		'og:site_name' => 'Muncie Events',
		'fb:admins' => array(
			'20721049', // Graham Watson
			'681411028' // Mary Ogle
		),
		'fb:app_id' => '496726620385625',
		'og:description' => 'Upcoming events in Muncie, IN',
		'og:locale' => 'en_US'
	);
	
	if (isset($og_meta_tags)) {
		foreach ($og_meta_tags as $property => $contents) {
			if (! is_array($contents)) {
				$contents = array($contents);	
			}
			foreach ($contents as $content) {
				switch ($property) {
					case 'og:description':
						$content = $this->Text->truncate(strip_tags($content), 1000, array(
							'exact' => false
						));
						break;
				}
				echo '<meta property="'.$property.'" content="'.htmlentities($content).'" />';
			}
		}
	}
	
	foreach ($default_og_meta_tags as $property => $default_contents) {
		if (isset($og_meta_tags[$property])) {
			continue;
		}
		if (! is_array($default_contents)) {
			$default_contents = array($default_contents);	
		}
		foreach ($default_contents as $content) {
			switch ($property) {
				case 'og:title':
					if ($title_for_layout) {
						$content = trim(strip_tags($title_for_layout));
					} else {
						$content = 'Muncie Events';	
					}
					break;
			}
			echo '<meta property="'.$property.'" content="'.htmlentities($content).'" />';
		}
	}