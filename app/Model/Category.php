<?php
App::uses('AppModel', 'Model');
class Category extends AppModel {
    public $name = 'Category';
	public $displayField = 'name';
	public $hasMany = array('Event');
	public $hasAndBelongsToMany = array('MailingList');
	
	public function getAll() {
		$cache_key = "all_categories";
		if ($cached = Cache::read($cache_key)) {
			return $cached;
		}
		$result = $this->find('all', array(
			'fields' => array('Category.id', 'Category.name', 'Category.slug'),
			'contain' => false,
			'order' => 'Category.weight ASC'
		));
		if (empty($result)) {
			throw new InternalErrorException("No categories found");
		} else {
			Cache::write($cache_key, $result);
			return $result;
		}
	}
	
	public function getList() {
		$categories = $this->getAll();
		$list = array();
		foreach ($categories as $category) {
			$list[$category['Category']['id']] = $category['Category']['name'];
		}
		return $list;
	}
	
	public function getName($id) {
		$result = $this->find('first', array(
			'conditions' => array('Category.id' => $id),
			'fields' => array('Category.name'),
			'contain' => false
		));
		if (empty($result)) {
			return false;
		} else {
			return $result['Category']['name'];
		}
	}
	
	/**
	 * Returns an array with the total upcoming events for each category
	 * @return array
	 */
	public function getAllUpcomingEventCounts() {
		$results = $this->Event->find('all', array(
			'conditions' => array('Event.date >=' => date('Y-m-d')),
			'fields' => array(
				'DISTINCT Event.category_id',
				'COUNT(Event.id) as count'
			),
			'group' => 'Event.category_id',
			'contain' => false
		));
		$retval = array();
		foreach ($results as $result) {
			$cat_id = $result['Event']['category_id'];
			$count = $result[0]['count'];
			$retval[$cat_id] = $count;
		}
		return $retval;
	}
}