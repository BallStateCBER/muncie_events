<?php
App::uses('AppModel', 'Model');
class MailingList extends AppModel {
	public $name = 'MailingList';
	public $displayField = 'email';
	public $useTable = 'mailing_list';
	public $hasOne = array(
		'User' => array(
			'fields' => array('User.id', 'User.name', 'User.email')
		)
	);
	public $hasMany = array(
		'MailingListLog' => array(
			'foreignKey' => 'recipient_id'
		)
	);
	public $hasAndBelongsToMany = array('Category');
	public $validate = array(
		'email' => array(
			'nonempty' => array(
				'rule' => 'notEmpty',
				'message' => 'Please enter your email address.'
			),
			'is_email' => array(
				'rule' => 'email',
				'message' => 'Sorry, that doesn\'t look like a valid email address.'
			)
		)
	);
	
	
	/**
	 * If set to true, then emails will only be sent to the subscriber with MailingList.id == 1
	 * @var boolean
	 */
	public $testing_mode = false;
	
	public function getMemberCount() {
		$cache_key = "MailingList.getMemberCount()";
		if ($cached = Cache::read($cache_key)) {
			return $cached;
		}
		$result = $this->find('count');
		Cache::write($cache_key, $result);
		return $result;
	}
	
	public function resetMemberCount() {
		Cache::delete("MailingList.getMemberCount()");
	}
	
	public function getTodayYMD() {
		return array(date('Y'), date('m'), date('d'));
	}
	
	public function getDailyRecipients() {
		list($y, $m, $d) = $this->getTodayYMD();
		$conditions = array(
			'MailingList.daily_'.strtolower(date('D')) => 1,
			'OR' => array(
				'MailingList.processed_daily' => null,
				'MailingList.processed_daily <' => "$y-$m-$d 00:00:00"
			)
		);
		if ($this->testing_mode) {
			$conditions['MailingList.id'] = 1;
		}
		return $this->find('all', array(
			'conditions' => $conditions,
			'contain' => array('Category' => array('fields' => array('id', 'name'))),
			'limit' => 10
		));
	}
	
	public function isWeeklyDeliveryDay() {
		return date('l') == 'Thursday';
	}
	
	public function getWeeklyRecipients() {
		list($y, $m, $d) = $this->getTodayYMD();
		$conditions = array(
			'MailingList.weekly' => 1,
			'OR' => array(
				'MailingList.processed_weekly' => null,
				'MailingList.processed_weekly <' => "$y-$m-$d 00:00:00"
			)
		);
		if ($this->testing_mode) {
			$conditions['MailingList.id'] = 1;
		}
		return $this->find('all', array(
			'conditions' => $conditions,
			'contain' => array('Category' => array('fields' => array('id', 'name'))),
			'limit' => 10
		));
	}
	
	public function getDays() {
		return array(
			'sun' => 'Sunday',
			'mon' => 'Monday',
			'tue' => 'Tuesday',
			'wed' => 'Wednesday',
			'thu' => 'Thursday',
			'fri' => 'Friday',
			'sat' => 'Saturday'
		);
	}

	public function emailUnclaimed($data) {
		$existing_recipient = $this->find('first', array(
			'conditions' => array('email' => trim(strtolower($data['email']))), 
			'fields' => array('id'),
			'contain' => false
		));
		if ($existing_recipient && $existing_recipient['MailingList']['id'] != $this->id) {
			return false;
		}
		return true;
	}
	
	public function markDailyAsProcessed($recipient_id, $result) {
		$this->MailingListLog->save(array(
			'recipient_id' => $recipient_id, 
			'result' => $result,
			'is_daily' => 1
		));
		$this->id = $recipient_id;
		return (
			$this->saveField('processed_daily', date('Y-m-d H:i:s')) && 
			$this->saveField('new_subscriber', 0)
		);
	}
	
	public function markWeeklyAsProcessed($recipient_id, $result) {
		$this->MailingListLog->save(array(
			'recipient_id' => $recipient_id, 
			'result' => $result,
			'is_weekly' => 1
		));
		$this->id = $recipient_id;
		return (
			$this->saveField('processed_weekly', date('Y-m-d H:i:s')) &&
			$this->saveField('new_subscriber', 0)
		);
	}
	
	public function markAllDailyAsProcessed($recipients, $result) {
		foreach ($recipients as $r) {
			$this->markDailyAsProcessed($r['MailingList']['id'], $result);
		}
	}
	
	public function markAllWeeklyAsProcessed($recipients, $result) {
		foreach ($recipients as $r) {
			$this->markWeeklyAsProcessed($r['MailingList']['id'], $result);
		}
	}
	
	public function filterWeeksEvents($recipient, $events) {
		if (! $recipient['MailingList']['all_categories']) {
			$selected_categories = explode(',', $recipient['MailingList']['categories']);
			foreach ($events as $timestamp => $days_events) {
				foreach ($days_events as $k => $event) {
					if (! in_array($event['Category']['id'], $selected_categories)) {
						unset($events[$timestamp][$k]);
					}
				}
			}
			foreach ($events as $timestamp => $days_events) {
				if (empty($days_events)) {
					unset($events[$timestamp]);
				}
			}
		}
		return $events;	
	}
	
	public function filterDaysEvents($recipient, $events) {
		if (! $recipient['MailingList']['all_categories']) {
			$selected_categories = explode(',', $recipient['MailingList']['categories']);
			foreach ($events as $k => $event) {
				if (! in_array($event['Category']['id'], $selected_categories)) {
					unset($events[$k]);
				}
			}
		}
		return $events;	
	}
	
	/**
	 * A duplication of the TextHelper method with serial comma added
	 * @param array $list
	 * @param string $and
	 * @param string $separator
	 * @return string
	 */
	public function toList($list, $and = 'and', $separator = ', ') {
		if (count($list) > 1) {
			$and = count($list > 2) ? (', ') : (' ');
			$retval = implode($separator, array_slice($list, null, -1));
			$retval .= $and;
			$retval .= array_pop($list);
			return $retval;
		} else {
			return array_pop($list);
		}
	}
	
	public function getSettingsDisplay($recipient) {
		// Categories
		if ($recipient['MailingList']['all_categories']) {
			$event_types = 'All events';
		} else {
			$selected_categories = $recipient['Category'];
			$category_names = array();
			foreach ($selected_categories as $sc) {
				$category_names[] = $sc['name'];
			}
			$event_types = 'Only '.$this->toList($category_names);
		}
		
		// Frequency
		$days = $this->getDays();
		$selected_days = array();
		foreach (array_keys($days) as $day) {
			if ($recipient['MailingList']["daily_$day"]) {
				$selected_days[] = $days[$day];
			}
		}
		$day_count = count($selected_days);
		if ($day_count == 7) {
			$frequency = 'Daily';
			if ($recipient['MailingList']['weekly']) {
				$frequency .= ' and weekly';
			}
		} elseif ($day_count > 0) {
			$frequency = 'Daily on '.$this->toList($selected_days);
			if ($recipient['MailingList']['weekly']) {
				$frequency .= ' and weekly';
			}
		} else {
			$frequency = $recipient['MailingList']['weekly'] ? 'Weekly' : '?';
		}
		
		return compact('event_types', 'frequency');
	}
	
	/**
	 * Returns a welcome message if $recipient_id is not set or 
	 * corresponds to a user who hasn't received any emails yet, 
	 * null otherwise.
	 * @param int $recipient_id
	 * @return NULL|string
	 */
	public function getWelcomeMessage($recipient_id = null) {
		if ($recipient_id && $this->isNewSubscriber($recipient_id)) {
			$message = 'Thanks for signing up for the Muncie Events ';
			$message .= 'mailing list! Don\'t hesitate to contact us ';
			$message .= '('.Router::url(array('controller' => 'pages', 'action' => 'contact'), true).') ';
			$message .= 'if you have any questions, comments, or suggestions. ';
			$message .= 'Remember that at any time, you can adjust your settings and ';
			$message .= 'change how often you receive these emails and what types of ';
			$message .= 'events you\'re interested in hearing about.';
			return $message;
		}
		return null;
	}
	
	public function getHash($recipient_id) {
		return md5('recipient'.$recipient_id);	
	}
	
	public function getAssociatedUserId($id = null) {
		if (! $id) {
			$id = $this->id;	
		}
		$result = $this->User->find('list', array(
			'conditions' => array('mailing_list_id' => $id),
			'fields' => array('id'),
			'contain' => false
		));
		return isset($result['User']['id']) ? $result['User']['id'] : false;
	}
	
	/**
	 * Returns TRUE if the user with id $id has not been sent any emails
	 * @param int $id
	 * @return boolean
	 */
	public function isNewSubscriber($id) {
		return (boolean) $this->field('new_subscriber', array('MailingList.id' => $id));
	}
	
	/**
	 * Sends the daily version of the event email.
	 * @param array $recipient
	 * @param array $events
	 * @return array:boolean string 
	 */
	public function sendDaily($recipient, $events, $testing = false) {
		$recipient_id = $recipient['MailingList']['id'];
		
		if ($this->testing_mode && $recipient_id != 1) {
			return array(false, 'Email not sent to '.$recipient['MailingList']['email'].' because the mailing list is in testing mode.');
		}
		
		// Make sure there are events to begin with
		if (empty($events)) {
			$this->markDailyAsProcessed($recipient_id, 2);
			return array(false, 'Email not sent to '.$recipient['MailingList']['email'].' because there are no events to report.');
		}
		
		// Eliminate any events that this user isn't interested in
		$events = $this->filterDaysEvents($recipient, $events);
		
		// Make sure there are events left
		if (empty($events)) {
			$event_categories = array();
			foreach ($events as $k => $event) {
				$event_categories[] = $event['Category']['id'];
			}
			$this->markDailyAsProcessed($recipient_id, 3);
			$message = 'No events to report, resulting from '.$recipient['MailingList']['email'].'\'s settings<br />';
			$message .= 'Selected: '.$recipient['MailingList']['categories'].'<br />';
			$message .= 'Available: '.(empty($event_categories) ? 'None' : implode(',', $event_categories));
			return array(false, $message);
		}
		
		// Fake sending an email if testing
		if ($testing) {
			$event_titles = array();
			foreach ($events as $e) {
				$event_titles[] = $e['Event']['title'];
			}
			$message = 'Email would have been sent to '.$recipient['MailingList']['email'].'<br />Events: '.implode('; ', $event_titles);
			return array(true, $message);
		}
		
		// Send real email
		$recipient_id = $recipient['MailingList']['id'];
		App::uses('CakeEmail', 'Network/Email');
		$email = new CakeEmail('mailing_list');
		$subject = 'Today in Muncie: '.date("l, M j");
		$email->to($recipient['MailingList']['email'])
			->subject($subject)
			->template('daily')
			->emailFormat('both')
			->helpers(array('Html', 'Text'))
			->viewVars(array(
				'title_for_layout' => $subject,
				'events' => $events,
				'recipient_email' => $recipient['MailingList']['email'],
				'recipient_id' => $recipient['MailingList']['id'],
				'date' => date("l, F jS, Y"),
				'hash' => $this->getHash($recipient_id),
				'welcome_message' => $this->getWelcomeMessage($recipient_id),
				'settings_display' => $this->getSettingsDisplay($recipient)
			));
		if ($email->send()) {
			$this->markDailyAsProcessed($recipient_id, 0);
			return array(true, 'Email sent to '.$recipient['MailingList']['email']);
		} else {
			$this->markDailyAsProcessed($recipient_id, 1);
			return array(false, 'Error sending email to '.$recipient['MailingList']['email']);
		}
	}
	
	/**
	 * Sends the weekly version of the event email.
	 * @param array $recipient
	 * @param array $events
	 * @return array:boolean string 
	 */
	public function sendWeekly($recipient, $events, $testing = false) {
		$recipient_id = $recipient['MailingList']['id'];
		
		if ($this->testing_mode && $recipient_id != 1) {
			return array(false, 'Email not sent to '.$recipient['MailingList']['email'].' because the mailing list is in testing mode.');
		}
		
		// Make sure there are events to begin with
		$events_count = 0;
		foreach ($events as $day => $d_events) {
			$events_count += count($d_events);
		}
		if (! $events_count) {
			$this->markWeeklyAsProcessed($recipient_id, 2);
			return array(false, 'Email not sent to '.$recipient['MailingList']['email'].' because there are no events to report.');
		}
		
		// Eliminate any events that this user isn't interested in
		$events = $this->filterWeeksEvents($recipient, $events);
		
		// Make sure there are events left
		if (empty($events)) {
			// No events to report to this user today.
			$event_categories = array();
			foreach ($events as $k => $event) {
				$event_categories[] = $event['Category']['id'];
			}
			$this->markWeeklyAsProcessed($recipient_id, 3);
			$message = 'No events to report, resulting from '.$recipient['MailingList']['email'].'\'s settings<br />';
			$message .= 'Selected: '.$recipient['MailingList']['categories'].'<br />';
			$message .= 'Available: '.(empty($event_categories) ? 'None' : implode(',', $event_categories));
			return array(false, $message);
		}
		
		// Fake sending an email if testing
		if ($testing) {
			$event_titles = array();
			foreach ($events as $timestamp => $days_events) {
				foreach ($days_events as $k => $e) {
					$event_titles[] = $e['Event']['title'];
				}
			}
			$message = 'Email would have been sent to '.$recipient['MailingList']['email'].'<br />Events: '.implode('; ', $event_titles);
			return array(true, $message);
		}
		
		// Send real email
		$recipient_id = $recipient['MailingList']['id'];
		App::uses('CakeEmail', 'Network/Email');
		$email = new CakeEmail('mailing_list');
		$subject = 'Upcoming Week in Muncie: '.date("M j");
		$email->to($recipient['MailingList']['email'])
			->subject($subject)
			->template('weekly')
			->emailFormat('both')
			->helpers(array('Html', 'Text'))
			->viewVars(array(
				'title_for_layout' => $subject,
				'events' => $events,
				'recipient_email' => $recipient['MailingList']['email'],
				'recipient_id' => $recipient['MailingList']['id'],
				'date' => date("l, F jS, Y"),
				'hash' => $this->getHash($recipient_id),
				'welcome_message' => $this->getWelcomeMessage($recipient_id),
				'settings_display' => $this->getSettingsDisplay($recipient)
			));
		if ($email->send()) {
			$this->markWeeklyAsProcessed($recipient_id, 0);
			return array(true, 'Email sent to '.$recipient['MailingList']['email']);
		} else {
			$this->markWeeklyAsProcessed($recipient_id, 1);
			return array(false, 'Error sending email to '.$recipient['MailingList']['email']);
		}	
	}
	
	/**
	 * Determined the values that MailingListController->request->data should be prepopulated with for the 'join' and 'settings' pages 
	 * @param array $recipient
	 * @return array
	 */
	public function getDefaultFormValues($recipient = null) {
		$data = array();
		$days = $this->getDays();
		
		// Settings page: Recipient data provided
		if ($recipient) {
			$days_selected = 0;
			foreach ($days as $day_abbrev => $day_name) {
				$days_selected += $recipient['MailingList']['daily_'.$day_abbrev];
			}
			if ($recipient['MailingList']['weekly'] && $days_selected == 0) {
				$data['MailingList']['frequency'] = 'weekly';
			} elseif (! $recipient['MailingList']['weekly'] && $days_selected == 7) {
				$data['MailingList']['frequency'] = 'daily';
			} else {
				$data['MailingList']['frequency'] = 'custom';
			}
			if ($recipient['MailingList']['all_categories']) {
				$data['MailingList']['event_categories'] = 'all';
			} else {
				$data['MailingList']['event_categories'] = 'custom';
			}
			foreach ($recipient['Category'] as $category) {
				$data['MailingList']['selected_categories'][$category['id']] = true;
			}
			foreach ($days as $code => $day) {
				$data['MailingList']["daily_$code"] = $recipient['MailingList']["daily_$code"];
			}
			$data['MailingList']['weekly'] = $recipient['MailingList']['weekly'];
			$data['MailingList']['email'] = $recipient['MailingList']['email'];
			if (isset($_GET['unsubscribe'])) {
				$data['unsubscribe'] = 1;
			}
			
		// Join page: No recipient data
		} else {	
			$data['MailingList']['frequency'] = 'weekly';
			$data['MailingList']['event_categories'] = 'all';
			foreach ($days as $code => $day) {
				$data['MailingList']['daily'][$code] = true;	
			}
			$categories = $this->Category->getList();
			foreach ($categories as $category_id => $category_name) {
				$data['MailingList']['selected_categories'][$category_id] = true;
			}
		}
		
		return $data;
	}
}