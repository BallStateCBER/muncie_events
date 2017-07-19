<?php
App::uses('AppModel', 'Model');
/**
 * Join model to join the models Event and Image
 */
class EventsImage extends AppModel {
	public $name = 'EventsImage';
	public $displayField = 'image_id';
	public $belongsTo = array(
        'Event', 
        'Image'
    );
}