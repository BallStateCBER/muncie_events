<?php
class ValidationBehavior extends ModelBehavior {
	function beforeValidate(&$model) {
		$model->validate['recaptcha_response_field'] = array(
			'checkRecaptcha' => array(
				'rule' => array('checkRecaptcha', 'recaptcha_challenge_field'),
				'required' => true,
				'allowEmpty' => false,
				'message' => 'CAPTCHA challenge words incorrect. Please try again. (Sorry about this, but it keeps us from being flooded with spam)',
			)
		);
		return true;
	}

	function checkRecaptcha(&$model, $data, $target) {
		App::import('Vendor', 'Recaptcha.recaptchalib');
		$privatekey = Configure::read('Recaptcha.Private');
		$res = recaptcha_check_answer(
			$privatekey, 							$_SERVER['REMOTE_ADDR'],
			$model->data[$model->alias][$target], 	$data['recaptcha_response_field']
		);
		return $res->is_valid;
	}
}
