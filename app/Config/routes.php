<?php
Router::parseExtensions('ics');

Router::connect('/', 			array('controller' => 'pages', 'action' => 'home'));
Router::connect('/login', 		array('controller' => 'users', 'action' => 'login'));
Router::connect('/logout', 		array('controller' => 'users', 'action' => 'logout'));
Router::connect('/account/*', 	array('controller' => 'users', 'action' => 'account'));
Router::connect('/register', 	array('controller' => 'users', 'action' => 'register'));
Router::connect('/contact', 	array('controller' => 'pages', 'action' => 'contact'));
Router::connect('/about', 		array('controller' => 'pages', 'action' => 'about'));
Router::connect('/terms', 		array('controller' => 'pages', 'action' => 'terms'));
Router::connect('/clear_cache', array('controller' => 'pages', 'action' => 'clear_cache'));
Router::connect('/widget', 		array('controller' => 'widgets', 'action' => 'index'));
Router::connect('/today', 		array('controller' => 'events', 'action' => 'today'));
Router::connect('/tomorrow', 	array('controller' => 'events', 'action' => 'tomorrow'));
Router::connect('/moderate', 	array('controller' => 'events', 'action' => 'moderate'));
Router::connect('/reset_password/*',	array('controller' => 'users', 'action' => 'reset_password'));
Router::connect('/past_locations', 		array('controller' => 'events', 'action' => 'past_locations'));
Router::connect('/robots.txt', 	array('controller' => 'pages', 'action' => 'robots'));

// The following content types will have /type/id route to /types/view/id
$models = array('event', 'user', 'tag', 'event_series');
foreach ($models as $model) {
	Router::connect(
		"/$model/:id/*",
		array('controller' => Inflector::pluralize($model), 'action' => 'view'),
		array('id' => '[0-9]+', 'pass' => array('id'))
	);
}

// Events controller
foreach (array('edit', 'edit_series', 'publish', 'approve', 'delete') as $action) {
	Router::connect(
		"/event/$action/:id",
		array('controller' => 'events', 'action' => $action),
		array('id' => '[0-9]+', 'pass' => array('id'))
	);
}

// Event series controller
foreach (array('approve', 'delete', 'edit') as $action) {
	Router::connect(
		"/event_series/$action/:id",
		array('controller' => 'event_series', 'action' => $action),
		array('id' => '[0-9]+', 'pass' => array('id'))
	);
}

// Categories
$category_slugs = array('music', 'art', 'theater', 'film', 'activism', 'general', 'education', 'government', 'sports', 'religion');
foreach ($category_slugs as $slug) {
	Router::connect("/$slug/*", array('controller' => 'events', 'action' => 'category', $slug));
}

// Tag
Router::connect(
	"/tag/:slug/:direction",
	array('controller' => 'events', 'action' => 'tag'),
	array('pass' => array('slug', 'direction'))
);
Router::connect(
	"/tag/:slug",
	array('controller' => 'events', 'action' => 'tag'),
	array('pass' => array('slug'))
);
Router::connect(
	"/tag/*",
	array('controller' => 'events', 'action' => 'tag')
);

// Tags
Router::connect('/tags', 		array('controller' => 'tags', 'action' => 'index', 'future'));
Router::connect('/tags/past', 	array('controller' => 'tags', 'action' => 'index', 'past'));

// Locations
Router::connect(
	"/location/:location/:direction",
	array('controller' => 'events', 'action' => 'location'),
	array('pass' => array('location', 'direction'))
);
Router::connect(
	"/location/:location",
	array('controller' => 'events', 'action' => 'location'),
	array('pass' => array('location'))
);
Router::connect(
	"/location/*",
	array('controller' => 'events', 'action' => 'location')
);

// Widgets
Router::connect(
	"/widgets/customize/feed",
	array('controller' => 'widgets', 'action' => 'customize_feed')
);
Router::connect(
	"/widgets/customize/month",
	array('controller' => 'widgets', 'action' => 'customize_month')
);

CakePlugin::routes();
require CAKE . 'Config' . DS . 'routes.php';