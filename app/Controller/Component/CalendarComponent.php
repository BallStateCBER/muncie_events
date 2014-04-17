<?php
App::uses('Component', 'Controller');
App::uses('Event', 'Model');
class CalendarComponent extends Component {
	public $components = array('Cookie');
	public $event_filter = array();
	public $controller = null;
	
	function initialize(&$controller) {
		$this->controller = $controller;
	}
	
	function setEventFilter() {		
		// Collect active filters
		$filter = array();
		if (isset($this->controller->params['named']['tag'])) {
			$tag_id = reset(explode('_', $this->controller->params['named']['tag']));
			$Event = new Event();
			$Event->Tag->id = $tag_id;
			$filter['tag'] = array(
				'name' => $Event->Tag->field('name'),
				'id' => $tag_id
			);
		}
		$filter['categories'] = $this->Cookie->read('EventFilter.categories');
		if (empty($filter['categories'])) {
			App::uses('Category', 'Model');
			$Category = new Category();
			$categories = $Category->getList();
			$filter['categories'] = array_keys($Category->getList());
			$this->Cookie->write('EventFilter.categories', $filter['categories'], false);
		}
		
		$this->event_filter = $filter;
		$this->controller->set(compact('filter'));
	}
	
	// Sets the variables needed for the "event accordion"
	function prepareEventAccordion() {
		// Determine the date boundaries of this page
		if (isset($this->controller->params['named']['start_date'])) {
			$boundary_date = $this->controller->params['named']['start_date'];
			$boundary_side = 'start';
		} elseif (isset($this->controller->params['named']['end_date'])) {
			$boundary_date = $this->controller->params['named']['end_date'];
			$boundary_side = 'end';
		} else {
			$boundary_date = date('Y-m-d');
			$boundary_side = 'start';
		}
		$boundary = array($boundary_side, $boundary_date);
		$Event = new Event();
		$events = $Event->getPage($boundary_side, $boundary_date, $this->event_filter);
		$starting_date = reset(array_keys($events));
		$ending_date = end(array_keys($events));
		$this->controller->set(compact(
			'events', 'starting_date', 'ending_date', 'filter', 'boundary'
		));
	}
	
	function prepareEventAccordionWrapper() {
		App::uses('Category', 'Model');
		$Category = new Category();
		//$Event = new Event();
		$this->controller->set(array(
			'categories' => $Category->getAll(),
			//'upcoming_event_tags' => $Event->getUpcomingTags($this->event_filter)
		));
	}
}