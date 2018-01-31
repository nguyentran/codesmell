<?php
App::uses('AppModel', 'Model');
/**
 * MCorpNewYear Model
 *
 * @property Corp $Corp
 */
class MCorpNewYear extends AppModel {


/**
 * belongsTo associations
 *
 * @var array
 */
	public $belongsTo = array(
		'MCorp' => array(
			'className' => 'MCorp',
			'foreignKey' => 'corp_id',
			'conditions' => '',
			'fields' => '',
			'order' => ''
		)
	);
}
