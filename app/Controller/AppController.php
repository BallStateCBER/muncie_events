<?php
App::uses('Sanitize', 'Utility');
App::uses('Controller', 'Controller');
class AppController extends Controller {
	public $components = array(
		'DebugKit.Toolbar',
		'Flash',
		'Session',
		'Auth' => array(
			'authenticate' => array(
				'Form' => array(
					'fields' => array(
						'username' => 'email'
					)
				)
			)
		),
		'Cookie',
		'Facebook.Connect' => array(
			'model' => 'User',

			// Selectively turn on when appropriate
			'noAuth' => true,
			'createUser' => false
		)
	);
	public $helpers = array(
		'Calendar',
		'Icon',
		'Facebook.Facebook',
		'ICal'
	);
	public $uses = array('User');

	public function beforeFilter() {
		$this->Auth->allow();

		// By default, prevent __syncFacebookUser from being called
		$this->Connect->noAuth = true;

		// Using "rijndael" encryption because the default "cipher" type of encryption fails to decrypt when PHP has the Suhosin patch installed.
        // See: http://cakephp.lighthouseapp.com/projects/42648/tickets/471-securitycipher-function-cannot-decrypt
		$this->Cookie->type('rijndael');

		// When using "rijndael" encryption the "key" value must be longer than 32 bytes.
		$this->Cookie->key = '"Then it doesn\'t matter which way you go."';

		// Prevents cookies from being accessible in Javascript
		$this->Cookie->httpOnly = true;

    	// Log in with cookie
		if (! $this->Auth->loggedIn() && $this->Cookie->read('remember_me_cookie')) {
			$cookie = $this->Cookie->read('remember_me_cookie');
			if (isset($cookie['email']) && isset($cookie['password'])) {
				$user = $this->User->find('first', array(
					'conditions' => array(
						'User.email' => $cookie['email'],
						'User.password' => $cookie['password']
					),
					'fields' => array('id', 'role'),
					'contain' => array(
						'MailingList' => array('fields' => array('id'))
					)
				));

				// Include user data
				$cookie['id'] = $user['User']['id'];
				$cookie['role'] = $user['User']['role'];
				/*
				if (isset($user['MailingList']['id'])) {
					$cookie['MailingList']['id'] = $user['MailingList']['id'];
				} else {
					$cookie['MailingList']['id'] = null;
				}
				*/

				$login_successful = $this->Auth->login($cookie);
				if ($user && ! $login_successful) {
					$this->redirect(array('controller' => 'users', 'action' => 'logout'));
				}
			}
		}

		// Provide Facebook connectivity information in $facebook_user
		$this->set('facebook_user', $this->Connect->user());
	}

	public function beforeRender() {
		$this->loadModel('Category');
		$this->loadModel('Event');

		// Get the populated dates for the next 3 months for the datePicker
		/*
		$populated_dates = array();
		for ($n = 0; $n < 3; $n++) {
			$timestamp = strtotime("today +$n months");
			$month = date('m', $timestamp);
			$year = date('Y', $timestamp);
			$results = $this->Event->getPopulatedDates($month, $year);
			$populated_dates["$month-$year"] = array();
			foreach ($results as $result) {
				$populated_dates["$month-$year"][] = end(explode('-', $result));
			}
		}
		*/
		$results = $this->Event->getPopulatedDates();
		foreach ($results as $result) {
			list($year, $month, $day) = explode('-', $result);
			// $day must be a string so the zero-padding is retained
			$populated_dates["$month-$year"][] = $day;
		}

		$this->set(array(
			'header_vars' => array(
				'categories' => $this->Category->getAll(),
				'populated_dates' => $populated_dates
			)
		));
		if ($this->layout == 'default') {
			$this->loadModel('Tag');
			$this->set(array(
				'sidebar_vars' => array(
					'locations' => $this->Event->getLocations('future'),
					'upcoming_tags' => $this->Tag->getUpcoming(),
					'upcoming_event_totals_by_category' => $this->Category->getAllUpcomingEventCounts()
				)
			));
			if ($this->Auth->loggedIn() && $this->Auth->user('role') == 'admin') {
				$this->loadModel('Event');
				$this->set(array(
					'unapproved_count' => $this->Event->getUnapproved('count')
				));
			}
		}
	}

	/**
	 * Sets up everything that the Recaptcha plugin depends on
	 */
	protected function prepareRecaptcha() {
		$this->helpers[] = 'Recaptcha.Recaptcha';
    	$this->Components->load('Recaptcha.Recaptcha')->startup($this);
		Configure::load('Recaptcha.key');
	}

	/**
	 * Renders a page that displays $params['message']
	 * with optional $params['class'], optional title $params['title'], and
	 * optional link back to $params['back'] (which can be a URL or array).
	 * Should be called as 'return $this->renderMessage($params);'
	 * @param array $params
	 */
	protected function renderMessage($params) {
		if (isset($params['message'])) {
			$this->set('message', $params['message']);
		}
		if (isset($params['title'])) {
			$this->set('title_for_layout', $params['title']);
		}
		if (isset($params['class'])) {
			$this->set('class', $params['class']);
		}
		if (isset($params['back'])) {
			$this->set('back', $params['back']);
		}
		if (isset($params['layout'])) {
			$this->layout = $params['layout'];
		}
		$this->render('/Pages/message');
	}

	protected function logAction($action = 'add', $overrides = array()) {
		/*
		$Log =& ClassRegistry::init('Log');
		$Log->create();
		$person_id = array_key_exists('person_id', $overrides) ? $overrides['person_id'] : $this->Auth->user('id');
		$model = array_key_exists('model', $overrides) ? $overrides['model'] : $this->modelClass;
		$id = array_key_exists('id', $overrides) ? $overrides['id'] : $this->$model->id;
		$Log->save(array(
			'person_id' => $person_id,
			'action' => $action,
			'model' => $model,
			'content_id' => $id
		));
		*/
	}


	/**
	 * Prevents Facebook from saving a user outside of the UsersController
	 * @return boolean
	 */
	public function beforeFacebookSave() {
		return false;
	}
}