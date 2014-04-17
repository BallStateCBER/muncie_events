<?php
/* Dummy model used in validating forms that do not use 
 * real models, e.g. the contact form */
App::uses('AppModel', 'Model');
class Dummy extends AppModel {
	public $name = 'Dummy';
	public $useTable = false;
}