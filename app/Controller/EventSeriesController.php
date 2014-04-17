<?php
App::uses('AppController', 'Controller');
class EventSeriesController extends AppController {
	public $name = 'EventSeries';
	public $components = array();
	public $helpers = array();
	public $admin_actions = array('approve');
	
	public function beforeFilter() {
		parent::beforeFilter();
		$this->Auth->deny('delete', 'edit', 'publish');
	}
	
	public function beforeRender() {
		parent::beforeRender();
	}
	
	public function isAuthorized() {
		// Admins can access everything
		if ($this->Auth->user('role') == 'admin') {
			return true;
		
		// Some actions are admin-only
		} elseif (in_array($this->action, $this->admin_actions)) {
			return false;	
		}
		
		// Otherwise, only authors can modify authored content
		$author_only = array('edit', 'delete');
		if (in_array($this->action, $author_only)) {
			return $this->__isAdminOrAuthor($this->request->params['named']['id']);	
		}
		
		// Logged-in users can access everything else
		return true;
	}
	
	public function index() {
			
	}
		
	public function view($id) {
		$series = $this->EventSeries->find('first', array(
			'conditions' => array('EventSeries.id' => $id),
			'fields' => array('EventSeries.id', 'EventSeries.title'),
			'contain' => array(
				'User' => array('fields' => array('User.id', 'User.name')),
				'Event' => array(
					'fields' => array('Event.id', 'Event.title', 'Event.date', 'Event.time_start'),
					'order' => 'Event.date ASC'
				)
			)
		));
		if (empty($series)) {
			throw new NotFoundException("Event series not found");
		}
		$this->set(array(
			'title_for_layout' => $series['EventSeries']['title'],
			'series' => $series,
		));
	}
	
	public function edit($series_id) {
		$this->EventSeries->id = $series_id;
		if (! $this->EventSeries->exists()) {
			return $this->renderMessage(array(
				'title' => 'Event Series Not Found',
				'message' => 'Sorry, we couldn\'t find that series. It may have been deleted.',
				'class' => 'error',
				'back' => $this->referer()
			));
		}
		
		$series_deleted = false;
		if ($this->request->is('put') || $this->request->is('post')) {
			$error_flag = false;
			if ($this->request->data['EventSeries']['delete']) {
				if ($this->EventSeries->delete()) {
					$this->Flash->success('Event series deleted.');
					$this->logAction('delete', array('id' => $series_id));
					$series_deleted = true;
				} else {
					$error_flag = true;
					$this->Flash->error('There was an error deleting this event series.');
				}
			} else {
				// Process data
				$this->request->data['EventSeries']['title'] = trim($this->request->data['EventSeries']['title']);
				
				$this->EventSeries->set($this->request->data);
				if ($this->EventSeries->save()) {
					$this->logAction('edit');
					$events_deleted = false;
					if (isset($this->request->data['Event'])) {
						foreach ($this->request->data['Event'] as $event_id => $event) {
							if (! $event['edited']) {
								continue;
							}
							$this->EventSeries->Event->create();
							$this->EventSeries->Event->id = $event_id;
							if ($event['delete']) {
								if ($this->EventSeries->Event->delete()) {
									$events_deleted = true;
								} else {
									$error_flag = true;
								}
							} else{
								$save_result = $this->EventSeries->Event->save($event, array(
									'fieldList' => array(
										'date',
										'time_start',
										'title'
									)
								));
								if (! $save_result) {
									$error_flag = true;
								}
							}
						}
					}
					
					// If all events were deleted, delete series
					if ($events_deleted) {
						$event_count = $this->EventSeries->Event->find('count', array(
							'conditions' => array('series_id' => $series_id)
						));
						if ($event_count == 0) {
							if ($this->EventSeries->delete()) {
								$series_deleted = true;
								$this->logAction('delete', array('id' => $series_id));
							} else {
								$error_flag = true;
								$this->Flash->error('There was an error deleting this event series.');
							}
						}
					}
				}
			}
			
			if ($series_deleted) {
				return $this->renderMessage(array(
					'title' => 'Event Series Removed',
					'message' => 'If you kill a series, does that make you a serial killer?',
					'class' => 'success'
				));
			} elseif ($error_flag) {
				$this->Flash->error('There was an error updating this event series');
			} else {
				$this->Flash->success('Event series updated');
				$this->redirect(array('action' => 'view', 'id' => $series_id));
			}
		} else {
			// Prevents unpublished events from being ignored
			$this->EventSeries->hasMany['Event']['conditions'] = array();
			
			$this->request->data = $this->EventSeries->read();
		}
		
		// Order events by date/time
		if (! empty($this->request->data['Event'])) {
			$ordered_events = array();
			foreach ($this->request->data['Event'] as $event) {
				$ordered_events[$event['date']][$event['time_start']][] = $event;
			}
			ksort($ordered_events);
			$final_events = array();
			foreach ($ordered_events as $date => $times) {
				ksort($times);
				foreach ($times as $time => $events) {
					$final_events = array_merge($final_events, $events);
				}
			}
			$this->request->data['Event'] = $final_events;
		}
		
		// Used by JS
		$event_ids = array();
		foreach ($this->request->data['Event'] as $event) {
			$event_ids[] = $event['id'];
		}
		
		$this->set(array(
			'title_for_layout' => 'Edit '.$this->request->data['EventSeries']['title'],
			'event_ids' => $event_ids
		));
	}
	
	public function delete($id) {
		if ($this->request->is('post')) {
			$this->EventSeries->id = $id;
			$exists = $this->EventSeries->exists();
			if ($exists) {
				$result = $this->EventSeries->delete();
			} else {
				$result = false;
			}
			$this->__redirectAfterDelete($id, $exists, $result);
		} else { 
			$referer = $this->referer();
			return $this->renderMessage(array(
				'title' => 'Invalid Attempt to Delete Event Series',
				'message' => 'Events can not be deleted that way. A proper link must be followed.',
				'class' => 'error',
				'back' => ($referer == '/') ? false : $referer
			));	
		}
	}
	
	private function __redirectAfterDelete($id, $exists, $delete_successful) {
		$referer = $this->referer();
		if ($referer == Router::url(array('controller' => 'event_series', 'action' => 'view', 'id' => $id), true)) {
			if (! $exists) {
				return $this->renderMessage(array(
					'title' => 'Event Series Not Found',
					'message' => 'The event series you were trying to remove may have already been removed.',
					'class' => 'error'
				));
			} elseif ($delete_successful) {
				return $this->renderMessage(array(
					'title' => 'Removed',
					'message' => 'That event has been successfully removed.',
					'class' => 'success'
				));
			} else {
				return $this->renderMessage(array(
					'title' => 'Error Removing Event Series',
					'message' => 'There was a problem removing that event series. Please try again.',
					'class' => 'error'
				));
			}				
		} else {
			if (! $exists) {
				$this->Flash->error('Event series not found. It may have already been removed.');
			} elseif ($delete_successful) {
				$this->Flash->success('Event series removed.');	
			} else {
				$this->Flash->error('Error removing event series.');
			}
			$this->redirect($referer);
		}
	}
	
	/**
	 * Publishes the specified EventSeries and publishes and approves all associated events
	 * @param int $id
	 */
	public function approve($id = null) {
		$this->loadModel('Event');
		$this->EventSeries->id = $id;
		if (! $id) {
			$this->Flash->error('Cannot publish. No event series ID specified.');
		} elseif (! $this->EventSeries->exists()) {
			$this->Flash->error('Cannot publish. Event series with ID '.$id.' does not exist.'); 
		} elseif ($this->EventSeries->saveField('published', 1)) {
			$events = $this->Event->find('list', array(
				'conditions' => array('series_id' => $id)
			));
			foreach ($events as $event_id => $event_title) {
				$this->Event->id = $event_id;
				$this->Event->saveField('published', 1);
				$this->Event->saveField('approved_by', $this->Auth->user('id'));
			}
			$url = Router::url(array(
				'controller' => 'event_series', 
				'action' => 'view', 
				'id' => $id
			));
			$this->Flash->success('Event series approved and published. <a href="'.$url.'">Go to series</a>');
		} else {
			$this->Flash->error('Error approving event series.');
		}
		$this->redirect($this->referer());
	}
}