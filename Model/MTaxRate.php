<?php
class MTaxRate extends AppModel {

	public $validate = array(

		'start_date' => array(
			'NotEmpty' => array(
				'rule' => 'notEmpty',
				'last' => true
			),
			'NotDate' => array(
				'rule' => 'datet',
				'last' => true,
				'allowEmpty' => true
			),
		),

		'end_date' => array(
			'NotDate' => array(
				'rule' => 'datet',
				'last' => true,
				'allowEmpty' => true
			),
		),

		'tax_rate' => array(
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'last' => true,
				'allowEmpty' => true
			),
		),
	);

}