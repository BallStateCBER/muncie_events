<?php
App::uses('Component', 'Controller');
class FlashComponent extends Component {
	public $components = array('Session');

	public function beforeRender(Controller $controller) {
		$this->__prepareFlashMessages($controller);
	}

	// Adds a string message with a class of 'success', 'danger', or 'info' (default)
	// OR adds a variable to dump and the class 'dump'
	public function set($message, $class = 'alert-info') {
		// Dot notation doesn't seem to allow for the equivalent of $messages['error'][] = $message
		$stored_messages = $this->Session->read('FlashMessage');
		$stored_messages[] = compact('message', 'class');
		$this->Session->write('FlashMessage', $stored_messages);
	}

	public function success($message) {
		$this->set($message, 'alert-success');
	}

	public function error($message) {
		$this->set($message, 'alert-danger');
	}

	public function notification($message) {
		$this->set($message, 'alert-info');
	}

	public function dump($variable) {
		$this->set($variable, 'dump');
	}

	// Sets an array to be displayed by the element 'flash_messages'
	private function __prepareFlashMessages($controller) {
		$stored_messages = $this->Session->read('FlashMessage');
		$this->Session->delete('FlashMessage');
		if ($auth_error = $this->Session->read('Message.auth')) {
			$stored_messages[] = array(
				'message' => $auth_error['message'],
				'class' => 'alert-danger'
			);
			$this->Session->delete('Message.auth');
		}
		if ($stored_messages) {
			foreach ($stored_messages as &$message) {
				if ($message['class'] == 'dump') {
					$message = array(
						'message' => '<pre>'.print_r($message['message'], true).'</pre>',
						'class' => 'alert-info'
					);
				}
			}
		}
		$controller->set('flash_messages', $stored_messages);
	}
}
