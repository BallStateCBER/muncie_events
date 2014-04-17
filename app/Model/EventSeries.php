<?php
App::uses('AppModel', 'Model');
class EventSeries extends AppModel {
    var $name = 'EventSeries';
    var $displayField = 'title';
	var $belongsTo = array(
		'User' => array(
			'className' => 'user',
			'foreignKey' => 'user_id',
			'fields' => array('User.id', 'User.name')
		)
	);
	var $hasMany = array(
		'Event' => array(
			'className' => 'Event',
			'foreignKey' => 'series_id',
			'conditions' => array('Event.published' => 1),
			'dependent' => true
		)
	);
	var $validate = array(
		'title' => array(
			'notEmpty' => array(
				'rule' => 'notEmpty',
				'message' => 'Sorry, this event series needs a title.',
			)
		)
	);
	
	function getUsersEventSeries($user_id) {
		return $this->find('all', array(
			'conditions' => array(
				'EventSeries.user_id' => $user_id 
			),
			'order' => array('EventSeries.created DESC'),
			'fields' => array('EventSeries.id', 'EventSeries.title'),
			'contain' => array(
				'Event' => array(
					'fields' => array('Event.id', 'Event.title', 'Event.date'),
					'order' => array('Event.created DESC, Event.date ASC')
				)
			)
		));
	}
	
	function getForEditing($id) {
		return $this->find('first', array(
			'conditions' => array('EventSeries.id' => $id),
			'fields' => array('EventSeries.id', 'EventSeries.title'),
			'contain' => array(
				'Event' => array(
					'fields' => array('Event.id', 'Event.title', 'Event.date', 'Event.time_start'),
					'order' => array('Event.date DESC', 'Event.time_start DESC')
				)
			)
		));	
	}
	
	function getForView($id) {
		return $this->find('first', array(
			'conditions' => array('EventSeries.id' => $id),
			'fields' => array('EventSeries.id', 'EventSeries.title'),
			'contain' => array(
				'User' => array('fields' => array('User.id', 'User.name')),
				'Event' => array('fields' => array('Event.id', 'Event.title', 'Event.date', 'Event.time_start'))
			)
		));
	}
}