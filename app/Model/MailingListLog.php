<?php
App::uses('AppModel', 'Model');
class MailingListLog extends AppModel {
    var $name = 'MailingListLog';
    var $displayField = 'result';
    var $useTable = 'mailing_list_log';
	var $belongsTo = array(
		'MailingList' => array(
			'foreignKey' => 'recipient_id'
		)
	);
	
	/*	0: Email sent
	 * 	1: Error sending email
	 * 	2: No events today
	 * 	3: No applicable events today
	 * 	4: Settings forbid sending email today
	 */
	function addLogEntry($recipient_id, $result, $flavor, $testing = false) {
		$this->create();
		$testing = $testing ? 1 : 0;
		return $this->save(compact('recipient_id', 'result', 'flavor', 'testing'));
	}
}