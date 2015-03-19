<?php
App::uses('AppModel', 'Model');
class Event extends AppModel {
	public $displayField = 'title';
	public $actsAs = array(
		'Containable',
		'Search.Searchable'
	);
	public $hasMany = array(
		'EventsImage' => array(
			'className' => 'EventsImage',
			'order' => 'EventsImage.weight ASC',
			'foreignKey' => 'event_id',
			'dependent' => true
		)
	);
	public $belongsTo = array(
		'User' => array(
			'fields' => array('User.id', 'User.name')
		),
		'Category' => array(
			'className' => 'Category',
			'foreignKey' => 'category_id'
		),
		'EventSeries' => array(
			'className' => 'EventSeries',
			'foreignKey' => 'series_id'
		)
	);
	public $hasAndBelongsToMany = array(
		'Tag' => array(
			'className'              => 'Tag',
			'joinTable'              => 'events_tags',
			'foreignKey'             => 'event_id',
			'associationForeignKey'  => 'tag_id',
			'order'                  => 'Tag.name ASC',
			'fields'				 => array('Tag.id', 'Tag.name', 'Tag.parent_id', 'Tag.listed', 'Tag.selectable')
		)
	);
	public $validate = array(
		'title' => array(
			'notEmpty' => array(
				'rule' => 'notEmpty',
				'message' => 'Title required.'
			)
		),
		'date' => array(
			'notEmpty' => array(
				'rule' => 'notEmpty',
				'message' => 'Please select a date'
			)
		),
		'location' => array(
			'notEmpty' => array(
				'rule' => 'notEmpty',
				'message' => 'Location required.'
			),
		),
		/* The TinyMCE input form doesn't work with the 'required' HTML 5 attribute */
		'description' => array(
			'notEmpty' => array(
				'rule' => 'notEmpty',
				'message' => 'Please describe this event.'
			)
		),
		'category_id' => array(
			'notEmpty' => array(
				'rule' => 'notEmpty',
				'message' => 'Please categorize this event.'
			)
		)
	);

	// If this is changed, also change /View/Elements/tinymce_input.ctp
	public $allowed_tags = '<p><br><a><strong><b><i><em><u>';


	/**
	 * Used by the Search plugin
	 * @var array
	 */
	public $filterArgs = array(
		'filter' => array('type' => 'query', 'method' => 'searchEventConditions'),
		'direction' => array('type' => 'query', 'method' => 'searchEventDirection'),
		//'filter' => array('type' => 'like', 'field' => 'Event.title', 'connectorAnd' => '+', 'connectorOr' => ','),
        //'title' => array('type' => 'like'),
		//'description' => array('type' => 'like'),
		//'location' => array('type' => 'like'),
		//'address' => array('type' => 'like'),
		//'source' => array('type' => 'like'),
		//'username' => array('type' => 'like', 'field' => array('User.name', 'UserInfo.email')),
		//'tags' => array('type' => 'subquery', 'method' => 'findByTags', 'field' => 'Event.id')
    );

	/**
	 * Custom find types
	 * @var array
	 */
	public $findMethods = array(
		'upcomingWithTag' => true,
		'pastWithTag' => true
	);

	protected function _findInDirectionWithTag($direction, $state, $query, $results = array()) {
		if ($state == 'before') {
			$event_ids = $this->EventsTag->find('list', array(
				'conditions' => array(
					'event_id' => ($direction == 'future')
						? $this->getFutureEventIDs()
						: $this->getPastEventIDs(),
					'tag_id' => $query['conditions']['Tag.id']
				),
				'fields' => array('EventsTag.event_id'),
				'contain' => false
			));
			unset($query['conditions']['Tag.id']);
			$query['conditions']['Event.id'] = array_values($event_ids);
			return $query;
		}
		return $results;
	}

	protected function _findUpcomingWithTag($state, $query, $results = array()) {
		return $this->_findInDirectionWithTag('future', $state, $query, $results);
	}

	protected function _findPastWithTag($state, $query, $results = array()) {
		return $this->_findInDirectionWithTag('past', $state, $query, $results);
	}

	public function getCountInDirectionWithTag($direction, $tag_id) {
		$conditions = array('tag_id' => $tag_id);
		if ($direction == 'future') {
			$conditions['event_id'] = $this->getFutureEventIDs();
		} else {
			// Since there are always more past events than future, this is quicker
			// than pulling the IDs of all past events
			$conditions['event_id NOT'] = $this->getFutureEventIDs();
		}
		return $this->EventsTag->find('count', array('conditions' => $conditions));
	}

	public function getCountUpcomingWithTag($tag_id) {
		return $this->getCountInDirectionWithTag('future', $tag_id);
	}

	public function getCountPastWithTag($tag_id) {
		return $this->getCountInDirectionWithTag('past', $tag_id);
	}

	public function searchEventConditions($data = array()) {
		$filter = $data['filter'];
		$conditions = array('OR' => array());
		$fields = array(
			'Event.title',
			'Event.description',
			'Event.location',
			'Event.address',
			'Event.source'
		);
		foreach ($fields as $field) {
			$conditions['OR']["$field LIKE"] = '%'.$filter.'%';
		}
		$tags = $this->Tag->find('list', array(
			'conditions' => array('Tag.name LIKE'  => '%'.$filter.'%')
		));

		// If there are any matching tags with associated events,
		// let that association qualify those events for inclusion
		if (! empty($tags)) {
			$events = $this->EventsTag->find('all', array(
				'conditions' => array('EventsTag.tag_id' => array_keys($tags)),
				'fields' => array('EventsTag.event_id')
			));
			if (! empty($events)) {
				$conditions['OR']['Event.id'] = array();
				foreach ($events as $event) {
					$conditions['OR']['Event.id'][] = $event['EventsTag']['event_id'];
				}
			}
		}
		return $conditions;
	}

	public function searchEventDirection($data = array()) {
		$conditions = array();
		if (! isset($data['direction'])) {
			return $conditions;
		}
		if ($data['direction'] == 'past') {
			$conditions['Event.date <'] = date('Y-m-d');
		} elseif ($data['direction'] == 'future') {
			$conditions['Event.date >='] = date('Y-m-d');
		}
		return $conditions;
	}

	public function getFilterName($filters) {
		if (isset($filters['tag'])) {
			$tag_id = $this->Tag->getIdFromSlug($filters['tag']);
			$this->Tag->id = $tag_id;
			if ($this->Tag->exists()) {
				return $this->Tag->field('name');
			}
		}
		return '';
	}

	function getPreppedEvent($id) {
		$this->id = $id;
		$event = $this->find('first', array(
			'conditions' => array('Event.id' => $id),
			'contain' => array(
				'User',
				'Category' => array(
					'fields' => array('Category.id', 'Category.name', 'Category.slug')
				),
				'EventsImage' => array(
					'fields' => array('EventsImage.id', 'EventsImage.caption'),
					'Image' => array(
						'fields' => array('Image.id', 'Image.filename')
					)
				),
				'EventSeries' => array(
					'fields' => array('EventSeries.id', 'EventSeries.title')
				),
				'Tag'
			)
		));
		if (! $event) {
			return false;
		}
		$date_split = explode('-', $event['Event']['date']);
		$year = $date_split[0];
		$month = ($date_split[1] < 10) ? substr($date_split[1], 1, 1) : $date_split[1];
		$day = ($date_split[2] < 10) ? substr($date_split[2], 1, 1) : $date_split[2];
		$date_string = date('l, F j, Y', mktime(0, 0, 0, $month, $day, $year));
		$event['header_details'] = array(
			'Date' => $date_string,
			'Time' => date('g:ia', strtotime($event['Event']['time_start'])),
			'Location' => $event['Event']['location'],
			'Address' => $event['Event']['address'],
			'Cost' => $event['Event']['cost'],
			'Age Restriction' => $event['Event']['age_restriction']
		);
		if ($event['Event']['time_end']) {
			$event['header_details']['Time'] .= ' to '.date('g:ia', strtotime($event['Event']['time_end']));
		}
		if ($event['User']['id']) {
			if ($event['User']['name']) {
				$url = Router::url(array(
					'controller' => 'users',
					'action' => 'view',
					'id' => $event['User']['id']
				));
				$added_by = '<a href="'.$url.'">'.$event['User']['name'].'</a>';
			} else {
				$added_by = '(someone whose account no longer exists)';
			}
		} else {
			$added_by = '(anonymous)';
		}
		$event['footer_details'] = array(
			'Added by' => $added_by,
			'Source' => $event['Event']['source']
		);
		if ($event['Event']['series_id']) {
			$url = Router::url(array(
				'controller' => 'event_series',
				'action' => 'view',
				'id' => $event['Event']['series_id']
			));
			$title = $event['EventSeries']['title'] ? $event['EventSeries']['title'] : '(unnamed series)';
			$event['footer_details']['Part of series'] = '<a href="'.$url.'">'.$title.'</a>';
		}
		return $event;
	}

	function getEventsOnDay($year, $month, $day, $only_approved = false) {
		$conditions = array(
			'Event.published' => 1,
			'Event.date' => "$year-$month-$day"
		);
		if ($only_approved) {
			$conditions['not']['Event.approved_by'] = null;
		}
		$events = $this->find('all', array(
			'conditions' => $conditions,
		 	'contain' => array(
		 		'Category' => array(
		 			'fields' => array('Category.id', 'Category.name', 'Category.slug')
				),
				'EventsImage' => array(
					'fields' => array('EventsImage.id', 'EventsImage.caption'),
					'Image' => array(
						'fields' => array('Image.id', 'Image.filename')
					)
				)
			),
			'fields' => array(
				'Event.id',
				'Event.title',
				'Event.time_start',
				'Event.time_end',
				'Event.location',
				'Event.location_details',
				'Event.date',
				'Event.approved_by'
			),
			'order' => 'time_start'
		));
		return $this->orderEventsByAdjustedTime($events);
	}

	function orderEventsByAdjustedTime($events) {
		if (empty($events)) {
			return array();
		}
		$ordered_events = array();
		foreach ($events as $event) {
			$time = intval(str_replace(':', '', $event['Event']['time_start']));
			// Adjust time so that 12:00am to 4:00am is considered late night, rather than early morning
			$time += ($time < 40000) ? 240000 : 0;
			$ordered_events[$time][] = $event;
		}
		ksort($ordered_events);
		$final_events = array();
		foreach ($ordered_events as $time => $t_events) {
			foreach ($t_events as $event) {
				$final_events[] = $event;
			}
		}
		return $final_events;
	}

	function getEventsUpcomingWeek($y, $m, $d, $only_approved = false) {
		$events = array();
		for ($n = 0; $n < 7; $n++) {
			$timestamp = mktime(0, 0, 0, $m, ($d + $n), $y);
			$this_y = date('Y', $timestamp);
			$this_m = date('m', $timestamp);
			$this_d = date('d', $timestamp);
			$days_events = $this->getEventsOnDay($this_y, $this_m, $this_d, $only_approved);
			if (! empty($days_events)) {
				$events[$timestamp] = $days_events;
			}
		}
		return $events;
	}

	function getTeasers($year, $month, $limit = 15) {
		$events = $this->find('all', array(
			'conditions' => array(
				'Event.date >=' => "$year-$month-01",
				'Event.date <=' => "$year-$month-31",
				'Event.published' => 1
			),
			'fields' => array(
				'Event.date AS date',
				'Event.title AS title'
			),
			'order' => array(
				'Event.time_start' => 'asc',
				'Event.title' => 'asc'
			),
			'contain' => array(
				'Category' => array(
					'fields' => array('Category.name')
				)
			)
		));
		$teasers = array();
		foreach ($events as $key => $event) {
			$date_split = explode('-', $event['Event']['date']);
			$day = ($date_split[2][0] == 0) ? $date_split[2][1] : $date_split[2]; // Removes leading zero
			$teasers[$day][] = array(
				'title' => $event['Event']['title'],
				'icon' => $event['Category']['name'],
				'class' => ''
			);
		}
		foreach ($teasers as $day => $titles) {
			$count = count($titles);
			if ($count > $limit) {
				$teasers[$day] = array_slice($titles, 0, $limit);
				$more = $count - $limit;
				$teasers[$day][] = array(
					'title' => $more.' more event'.($more > 1 ? 's' : ''),
					'icon' => '',
					'class' => 'more'
				);
			}
		}
		return $teasers;
	}

	function getUsersNonSeriesEvents($user_id) {
		return $this->find('all', array(
			'conditions' => array(
				'Event.user_id' => $user_id,
				'Event.series_id' => null
			),
			'order' => array('Event.created DESC, Event.date ASC'),
			'fields' => array('Event.id', 'Event.title', 'Event.date'),
			'contain' => false
		));
	}

	function getBigCalendarEvents($month = null, $year = null) {
		$year = $year ? $year : date('Y');
		$month = $month ? str_pad($month, 2, '0', STR_PAD_LEFT) : date('m');
		$events = $this->find('all', array(
			'conditions' => array(
				'Event.date >=' => "$year-$month-01",
				'Event.date <=' => "$year-$month-31",
				'Event.published' => 1
			),
			'fields' => array(
				'Event.id', 'Event.title', 'Event.date'
			),
			'contain' => array(
				'Tag' => array(
					'fields' => array(
						'Tag.id', 'Tag.name'
					)
				),
				'Category' => array(
					'fields' => array(
						'Category.id', 'Category.name', 'Category.slug'
					)
				)
			),
			'order' => array(
				'Event.id ASC'
			)
		));
		$arranged_events = array();
		foreach ($events as $event) {
			$date_split = explode('-', $event['Event']['date']);
			$day = ($date_split[2] < 10) ? substr($date_split[2], 1, 1) : $date_split[2];
			$arranged_events[$day][] = $event;
		}
		return $arranged_events;
	}

	// Returns an array of days (e.g. [1, 2, 4, 7]) for the selected month that have events
	function getDaysWithEvents($month, $year) {
		$events = $this->find('all', array(
			'conditions' => array(
				'Event.date >=' => "$year-$month-01",
				'Event.date <=' => "$year-$month-31",
				'Event.published' => 1
			),
			'fields' => array('Event.id', 'Event.date'),
			'contain' => false,
		));
		$days_with_events = array();
		foreach ($events as $event) {
			$date_split = explode('-', $event['Event']['date']);
			$day = ($date_split[2] < 10) ? substr($date_split[2], 1, 1) : $date_split[2];
			$days_with_events[] = $day;
		}
		return $days_with_events;
	}

	function getNextInCategory($category_id) {
		$result = $this->find('all', array(
			'conditions' => array(
				'Event.category_id' => $category_id,
				'Event.date >=' => date('Y-m-d'),
				'Event.published' => 1
			),
			'limit' => 5,
			'order' => array('Event.date ASC', 'Event.time_start ASC'),
			'contain' => false,
			'fields' => array('Event.id', 'Event.title', 'Event.location', 'Event.location_details', 'Event.date', 'Event.time_start')
		));
		return $result;
	}

	public function getUnapproved($find_type = 'all') {
		return $this->find($find_type, array(
			'conditions' => array(
				'Event.approved_by' => null
			),
			'order' => array('Event.created' => 'asc')
		));
	}

	public function applyFiltersToFindParams($find_params, $filters = array(), $start_date = null) {
		if (isset($filters['category']) && ! empty($filters['category'])) {
			$find_params['conditions']['Event.category_id'] = $filters['category'];
		}
		if (isset($filters['location']) && ! empty($filters['location'])) {
			$find_params['conditions']['Event.location LIKE'] = '%'.$filters['location'].'%';
		}

		// If there are included/excluded tags, retrieve all potentially
		// applicable event IDs that must / must not be part of the final results
		$event_ids = array();
		foreach(array('included', 'excluded') as $foocluded) {
			if (isset($filters["tags_$foocluded"])) {
				$results = $this->Tag->find('all', array(
					'conditions' => array('Tag.id' => $filters["tags_$foocluded"]),
					'fields' => array('Tag.id'),
					'contain' => array('Event' => array(
						'fields' => array('Event.id'),
						'conditions' => $find_params['conditions']
					))
				));
				$event_ids[$foocluded] = array();
				foreach ($results as $result) {
					foreach ($result['Event'] as $event) {
						$event_ids[$foocluded][] = $event['id'];
					}
				}
			}
		}
		if (isset($event_ids['included'])) {
			$find_params['conditions']['Event.id'] = $event_ids['included'];
		}
		if (isset($event_ids['excluded'])) {
			$find_params['conditions']['Event.id NOT'] = $event_ids['excluded'];
		}
		return $find_params;
	}

	/* Unused
	public function getFilterTagIds($filters = array()) {
		$tag_ids = array();
		foreach (array('included', 'excluded') as $foocluded) {
			if (isset($filters["tags_$foocluded"])) {
				foreach ($filters["tags_$foocluded"] as $tag_name) {
					$tag_id = $this->Tag->getIdFromName($tag_name);
					if ($tag_id) {
						$tag_ids[$foocluded][] = $tag_id;
					}
				}
			}
		}
		return $tag_ids;
	}
	*/

	public function getNextPopulatedDays($start_date, $limit, $filters = array()) {
		$find_params = array(
			'conditions' => array(
				'Event.published' => 1,
				'Event.date >=' => $start_date
			),
			'fields' => array('DISTINCT Event.date'),
			'contain' => array(),
			'limit' => $limit,
			'order' => array('Event.date ASC')
		);
		$find_params = $this->applyFiltersToFindParams($find_params, $filters, $start_date);
		$date_results = $this->find('all', $find_params);
		$dates = array();
		foreach ($date_results as $result) {
			if (isset($result['Event']['date'])) {
				$dates[] = $result['Event']['date'];
			}
		}
		return $dates;
	}

	public function getLocations($direction = 'future') {
		$find_params = array(
			'conditions' => array('Event.published' => 1),
			'fields' => array('DISTINCT Event.location'),
			'contain' => false,
			'order' => 'Event.location ASC'
		);
		if ($direction == 'future') {
			$find_params['conditions']['Event.date >='] = date('Y-m-d');
		} elseif ($direction == 'past') {
			$find_params['conditions']['Event.date <'] = date('Y-m-d');
		}
		$results = $this->find('all', $find_params);
		$retval = array();
		foreach ($results as $result) {
			$location = $result['Event']['location'];
			$trimmed_location = trim($result['Event']['location']);
			if (stripos($trimmed_location, 'the ') === 0) {
				$key = substr($trimmed_location, 4).', '.substr($trimmed_location, 0, 3);
			} else {
				$key = $location;
			}
			$retval[$key] = $location;
		}
		uksort($retval, 'strcasecmp');
		return $retval;
	}

	/**
	 * Returns an array of events arranged by date
	 * @param string|array $dates Can be 'yyyy-mm-dd' or an array of strings in same format
	 * @param array $filters Optional
	 * @param boolean $for_widget TRUE minimizes fetched fields
	 * @return array
	 */
	public function getFilteredEventsOnDates($dates, $filters = array(), $for_widget = false) {
		$find_params = array(
			'conditions' => array(
				'Event.published' => 1,
				'Event.date' => $dates
			),
			'order' => array('Event.date ASC', 'Event.time_start ASC'),
			'contain' => array(
				'Category' => array(
					'fields' => array('Category.id', 'Category.name', 'Category.slug')
				),
				'EventsImage' => array(
					'fields' => array('EventsImage.id', 'EventsImage.caption'),
					'Image' => array(
						'fields' => array('Image.id', 'Image.filename')
					)
				)
			)
		);
		if (is_array($dates)) {
			sort($dates);
			$start_date = reset($dates);
		} else {
			$start_date = $dates;
		}
		$find_params = $this->applyFiltersToFindParams($find_params, $filters, $start_date);

		// Request full fields or minimal fields (for widget)
		if ($for_widget) {
			$find_params['fields'] = array(
				'Event.id',
				'Event.title',
				'Event.location',
				'Event.date',
				'Event.time_start'
			);
		} else {
			$find_params['fields'] = array(
				'Event.id',
				'Event.title',
				'Event.description',
				'Event.location',
				'Event.location_details',
				'Event.address',
				'Event.series_id',
				'Event.date',
				'Event.time_start',
				'Event.time_end',
				'Event.age_restriction',
				'Event.cost',
				'Event.source',
				'Event.user_id',
				'Event.approved_by'
			);
			$find_params['contain']['User'] = array('fields' => array('User.id', 'User.name'));
			$find_params['contain']['Tag'] = array('fields' => array('Tag.id', 'Tag.name'));
			$find_params['contain']['EventSeries'] = array('fields' => array('EventSeries.id', 'EventSeries.title'));
		}

		$events = $this->find('all', $find_params);
		$events = $this->orderEventsByAdjustedTime($events);
		return $this->arrangeByDate($events);
	}

	/**
	 * Accepts an array of date => eventsOnDate and returns the date after its last date
	 * @param array $events
	 * @return string
	 */
	public function getNextStartDate($events) {
		$event_keys = array_keys($events);
		$last_date = end($event_keys);
		return date('Y-m-d', strtotime("$last_date + 1 day"));
	}

	/**
	 * Returns events for the next seven populated days
	 * @param string $start_date YYYY-MM-D
	 * @return array
	 */
	public function getPage_prev($start_date = null, $filters = array()) {
		if (! $start_date) {
			$start_date = date('Y-m-d');
		}
		$dates_per_page = 7;

		// Get list of populated dates
		$dates = $this->getNextPopulatedDays($start_date, $dates_per_page);
		if (empty($dates)) {
			return array();
		}

		$events = $this->find('all', array(
			'conditions' => array('Event.published' => 1, 'Event.date' => $dates),
			'fields' => array('Event.id', 'Event.title', 'Event.description', 'Event.location', 'Event.location_details', 'Event.address', 'Event.series_id', 'Event.date', 'Event.time_start', 'Event.time_end', 'Event.age_restriction', 'Event.cost', 'Event.source', 'Event.user_id'),
			'order' => array('Event.date ASC', 'Event.time_start ASC'),
			'contain' => array(
				'Category' => array('fields' => array('Category.id', 'Category.name', 'Category.slug')),
				'User' => array('fields' => array('User.id', 'User.name')),
				'Tag' => array('fields' => array('Tag.id', 'Tag.name')),
				'EventSeries' => array('fields' => array('EventSeries.id', 'EventSeries.title')),
				'EventsImage' => array(
					'fields' => array('EventsImage.id', 'EventsImage.caption'),
					'Image' => array(
						'fields' => array('Image.id', 'Image.filename')
					)
				)
			)
		));

		return $this->arrangeByDate($events);
	}

	/**
	 * Get a set of events for a 'page' of the calendar
	 * @param string $start_date
	 * @param array $filters
	 * @return array
	 */
	public function getPage($start_date = null, $filters = array(), $for_widget = false) {
		if (! $start_date) {
			$start_date = date('Y-m-d');
		}
		$dates_per_page = 7;
		$dates = $this->getNextPopulatedDays($start_date, $dates_per_page, $filters);
		return $this->getFilteredEventsOnDates($dates, $filters, $for_widget);
	}

	/**
	 * Get a set of events for a 'page' of the widget.
	 * This is like Event::getPage(), but pulls less data
	 * @param string $start_date
	 * @param array $filters
	 * @return array
	 */
	public function getWidgetPage($start_date = null, $filters = array()) {
		$filters = $this->formatWidgetFilters($filters);
		return $this->getPage($start_date, $filters, true);
	}

	/**
	 * Takes $filters provided by a Widget page, converts
	 * valid tag names into tag IDs, and removes invalid
	 * tag names, and returns the formatted $filters.
	 * @param array $filters
	 * @return array $filters:
	 */
	public function formatWidgetFilters($filters) {
		foreach(array('included', 'excluded') as $foocluded) {
			if (isset($filters["tags_$foocluded"])) {
				foreach ($filters["tags_$foocluded"] as $k => $tag_name) {
					$tag_id = $this->Tag->getIdFromName($tag_name);
					if ($tag_id) {
						$filters["tags_$foocluded"][$k] = $tag_id;
					} else {
						unset($filters["tags_$foocluded"][$k]);
					}
				}
				$filters["tags_$foocluded"] = array_values($filters["tags_$foocluded"]);
			}
		}
		return $filters;
	}

	/**
	 * Arranges a flat array of events by date => array(events in date)
	 * @param array $events
	 * @return array
	 */
	public function arrangeByDate($events) {
		$arranged_events = array();
		foreach ($events as $event) {
			$date = $event['Event']['date'];
			$arranged_events[$date][] = $event;
		}
		ksort($arranged_events);
		return $arranged_events;
	}

	function getUpcomingTags($filter = array()) {
		return $this->Tag->getUpcoming($filter);
	}

	/**
	 * Takes an array of options and returns any event filters included
	 * after validating and correcting any formatting errors
	 * @param array $options
	 * @return array
	 */
	public function getValidFilters($options) {
		// Correct formatting of $options
		$corrected_options = array();
		foreach ($options as $var => $val) {
			if (is_string($val)) {
				$val = trim($val);
			}
			if (stripos($var, 'amp;') === 0) {
				$var = str_replace('amp;', '', $var);
			}

			// Turn specified options into arrays if they're comma-delimited strings
			$expected_arrays = array('category', 'tags_included', 'tags_excluded');
			if (in_array($var, $expected_arrays) && ! is_array($val)) {
				$val = explode(',', $val);
				$corrected_array = array();
				foreach ($val as $member) {
					$member = trim($member);
					if ($member != '') {
						$corrected_array[] = $member;
					}
				}
				$val = $corrected_array;
			}

			// Only include if not empty
			/* Note: A value of 0 is a valid Widget parameter elsewhere (e.g. the
			 * boolean 'outerBorder'), but not valid for any event filters. */
			if (! empty($val)) {
				$corrected_options[$var] = $val;
			}
		}
		$options = $corrected_options;

		// Pull event filters out of options
		$filters = array();
		$filter_types = array('category', 'location', 'tags_included', 'tags_excluded');
		foreach ($filter_types as $type) {
			if (isset($options[$type])) {
				$filters[$type] = $options[$type];
			}
		}

		// Remove categories filter if it specifies all categories
		if (isset($filters['category'])) {
			sort($filters['category']);
			$all_category_ids = array_keys($this->Category->find('list', array('order' => 'id ASC')));
			$excluded_categories = array_diff($all_category_ids, $filters['category']);
			if (empty($excluded_categories)) {
				unset($filters['category']);
			}
		}

		// If a tag is both excluded and included, favor excluding
		if (isset($filters['tags_included']) && isset($filters['tags_excluded'])) {
			foreach ($filters['tags_included'] as $k => $id) {
				if (in_array($id, $filters['tags_excluded'])) {
					unset($filters['tags_included'][$k]);
				}
			}
			if (empty($filters['tags_included'])) {
				unset($filters['tags_included']);
			}
		}

		return $filters;
	}

	/**
	 * Returns the IDs of all events taking place before today
	 * @return array
	 */
	public function getPastEventIDs() {
		$result = $this->find('list', array(
			'conditions' => array('Event.date <' => date('Y-m-d'))
		));
		return array_keys($result);
	}

	/**
	 * Returns the IDs of all events taking place today and in the future
	 * @return array
	 */
	public function getFutureEventIDs() {
		$result = $this->find('list', array(
			'conditions' => array('Event.date >=' => date('Y-m-d'))
		));
		return array_keys($result);
	}

	/**
	 * Returns a MySQL query that returns the IDs of all events taking place before today
	 * @return array
	 */
	public function getDirectionCondition($direction = 'future') {
		$db = $this->getDataSource();
		$conditions = ($direction == 'future')
			? array('Event.date <' => date('Y-m-d'))
			: array('Event.date >=' => date('Y-m-d'));
		$subQuery = $db->buildStatement(
		    array(
		        'fields'     => array('Event.id'),
		        'table'      => $db->fullTableName($this),
		        'alias'      => 'Event',
		        'limit'      => null,
		        'offset'     => null,
		        'joins'      => array(),
		        'conditions' => $conditions,
		        'order'      => null,
		        'group'      => null
		    ),
		    $this
		);
		$subQuery = ' Event.id IN (' . $subQuery . ') ';
		$subQueryExpression = $db->expression($subQuery);
		return $subQueryExpression;
	}

	/**
	 * Removes all associations that the specified event has with any images
	 * @param integer $id
	 */
	public function removeImageAssociations($id) {
		return $this->EventsImage->deleteAll(array('event_id' => $id));
	}

	/**
	 * Removes all associations that the specified event has with any images
	 * @param integer $id
	 */
	public function removeTagAssociations($id) {
		return $this->EventsTag->deleteAll(array('event_id' => $id));
	}

	/**
	 * Returns an array of dates (YYYY-MM-DD) with published events
	 * @param string $month Optional, zero-padded
	 * @param int $year Optional
	 * @param array $filters Optional
	 * @return array
	 */
	public function getPopulatedDates($month = null, $year = null, $filters = null) {
		$find_params = array(
			'conditions' => array('Event.published' => 1),
			'fields' => array('DISTINCT Event.date'),
			'contain' => array(),
			'order' => array('Event.date ASC')
		);

		// Apply optional month/year limits
		if ($month && $year) {
			$month = str_pad($month, 2, '0', STR_PAD_LEFT);
			$find_params['conditions']['Event.date LIKE'] = "$year-$month-%";
			$find_params['limit'] = 31;
		} elseif ($year) {
			$find_params['conditions']['Event.date LIKE'] = "$year-%";
		}

		// Apply optional filters
		if ($filters) {
			$start_date = null;
			$find_params = $this->applyFiltersToFindParams($find_params, $filters, $start_date);
		}

		$date_results = $this->find('all', $find_params);
		$dates = array();
		foreach ($date_results as $result) {
			if (isset($result['Event']['date'])) {
				$dates[] = $result['Event']['date'];
			}
		}
		return $dates;
	}

	public function getMonth($year_month = null, $filters = array()) {
		if (! $year_month) {
			$year_month = date('my');
		}
		$split = explode('-', $year_month);
		$year = reset($split);
		$month = end($split);
		$filters = $this->formatWidgetFilters($filters);
		$dates = $this->getPopulatedDates($month, $year, $filters);
		return $this->getFilteredEventsOnDates($dates, $filters, true);
	}


	/**
	 * Returns the locations and addresses used in events added by this user
	 * @param int $user_id
	 * @return array
	 */
	public function getPreviousLocations($user_id = null) {
		if (! $user_id) {
			return array();
		}
		$locations = $this->find('list', array(
			'conditions' => array('Event.user_id' => $user_id),
			'fields' => array('Event.location', 'Event.address'),
			'group' => array('Event.location'),
			'contain' => false,
			'order' => array('Event.created DESC')
		));

		// Attempt to fill in missing addresses
		foreach ($locations as $location => &$address) {
			if ($address == '') {
				$result = $this->find('first', array(
					'conditions' => array(
						'NOT' => array(
							'Event.address' => ''
						),
						'Event.location' => $location
					),
					'fields' => array('Event.address'),
					'contain' => false,
					// Favor published events over unpublished events
					'order' => array(
						'Event.published DESC'
					)
				));
				if ($result) {
					$address = $result['Event']['address'];
				}
			}
		}
		return $locations;
	}

	/**
	 * Returns the most recently published address
	 * for the provided location name or FALSE if none is found
	 * @param string $location
	 * @return boolean
	 */
	public function getAddress($location) {
		$result = $this->find('first', array(
			'conditions' => array(
				'Event.published' => 1,
				'Event.location' => $location,
				'NOT' => array(
					'Event.address' => ''
				)
			),
			'fields' => array('Event.address'),
			'contain' => false,
			'order' => array('Event.created DESC')
		));
		if (empty($result)) {
			return false;
		}
		return $result['Event']['address'];
	}

	/**
	 * If an event has not yet been approved, approves and publishes this event
	 * @param int $id
	 * @throws InternalErrorException
	 * @throws ForbiddenException
	 */
	public function approve($id = null) {
		if ($id) {
			$this->id = $id;
		} elseif (! $this->id) {
			throw new InternalErrorException('Cannot approve an event with no ID.');
		}
		if (CakeSession::read('Auth.User.role') != 'admin') {
			throw new ForbiddenException('Only logged-in administrators may approve events.');
		}

		// Avoid overwriting one admin's approval with another
		$approved = $this->field('approved_by');
		if (! $approved) {
			$this->saveField('approved_by', CakeSession::read('Auth.User.id'));
			$this->saveField('published', 1);
			return true;
		}
		return false;
	}
}