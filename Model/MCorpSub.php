<?php
class MCorpSub extends AppModel {

	public $validate = array(

		'corp_id' => array(
			'NotEmpty' => array(
				'rule' => 'notEmpty',
				'last' => true
			),
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'allowEmpty' => true
			),
		),

		'item_category' => array(
			'OverMaxLength50' => array(
				'rule' => array('maxLength', 50),
				'last' => true,
				'allowEmpty' => true
			),
		),

		'item_id' => array(
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'allowEmpty' => true
			),
		),
	);

}