<?php
App::uses('AppController', 'Controller');
class PagesController extends AppController {
	public $name = 'Pages';
	public $uses = array();
	public $components = array();

	public function home() {
		$this->loadModel('Event');
		$events = $this->Event->getPage();
		$this->set(array(
			'title_for_layout' => '',
			'events' => $events,
			'next_start_date' => $this->Event->getNextStartDate($events)
		));
	}

	public function clear_cache($key = null) {
		if ($key) {
			if (Cache::delete($key)) {
				$message = 'Cache cleared ('.$key.')';
				$class = 'success';
			} else {
				$message = 'Error clearing cache ('.$key.')';
				$class = 'error';
			}
		} else {
			if (Cache::clear() && clearCache()) {
				$message = 'Cache cleared';
				$class = 'success';

				// Remove model cache
				App::uses('Folder', 'Utility');
				App::uses('File', 'Utility');
				$dir = TMP.DS.'cache'.DS.'models';
				$dir_obj = new Folder($dir);
				$files = $dir_obj->find('.*cake_model.*');
				foreach ($files as $file) {
					$file_obj = new File($dir.DS.$file);
					$file_obj->delete();
				}
			} else {
				$message = 'Error clearing cache';
				$class = 'error';
			}
		}
		return $this->renderMessage(array(
			'title' => 'Cache Clearing Result',
			'message' => $message,
			'class' => $class
		));
	}

	public function about() {
	    $this->loadModel('Event');
		$this->set(array(
			'title_for_layout' => 'About',
            'eventCount' => $this->Event->find('count'),
            'yearsCount' => date('Y') - 2009
		));
	}

	public function terms() {
		$this->set(array(
			'title_for_layout' => 'Web Site Terms and Conditions of Use'
		));
	}

	public function contact() {
		App::uses('CakeEmail', 'Network/Email');

		$this->modelClass = 'Dummy';
		// Use a dummy model for validation
		$this->loadModel('Dummy');
		$this->Dummy->validate = array(
			'name' => array(
				'rule'    => 'notEmpty',
				'message' => 'Please tell us who you are.'
			),
			'email' => array(
				'rule' => 'email',
				'message' => 'Please provide a valid email address. Otherwise, we can\'t respond back.'
			),
			'body' => array(
				'rule'    => 'notEmpty',
				'message' => 'This field cannot be left blank.'
			)
		);

		$logged_in = (boolean) $this->Auth->user('id');

		$categories = array('General', 'Website errors');
		if ($this->request->is('post')) {
		    if ($logged_in || $this->Recaptcha->verify()) {
                $this->Dummy->set($this->request->data);

                if ($this->Dummy->validates()) {
                    $email = new CakeEmail('contact_form');
                    $category = $categories[$this->request->data['Dummy']['category']];
                    $email->from(array($this->request->data['Dummy']['email'] => $this->request->data['Dummy']['name']))
                        ->to(Configure::read('admin_email'))
                        ->subject('Muncie Events contact form: '.$category);
                    if ($email->send($this->request->data['Dummy']['body'])) {
                        return $this->renderMessage(array(
                            'title' => 'Message Sent',
                            'message' => 'Thanks for contacting us. We will try to respond to your message soon.',
                            'class' => 'success'
                        ));
                    } else {
                        $this->Flash->error('There was some problem sending your email.
						It could be a random glitch, or something could be permanently
						broken. Please contact <a href="mailto:'.Configure::read('admin_email').'">'
                            .Configure::read('admin_email').'</a> for assistance.');
                    }
                }
            } else {
                $this->set(array('recaptcha_error' => $this->Recaptcha->error));
                $this->Flash->error('There was an error validating your CAPTCHA response. Please try again.');
            }
		}
		$this->set(array(
			'title_for_layout' => 'Contact Us',
			'categories' => $categories
		));
	}

	public function robots() {
		$this->RequestHandler = $this->Components->load('RequestHandler');
		$this->RequestHandler->respondAs('text');
		$this->layout = 'blank';
	}
}