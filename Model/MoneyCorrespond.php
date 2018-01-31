<?php
class MoneyCorrespond extends AppModel {

	public $validate = array(

		'corp_id' => array (
			'NotEmpty' => array (
				'rule' => 'notEmpty',
				'last' => true
			)
		),

		'payment_date' => array (
			'NotEmpty' => array (
				'rule' => 'notEmpty',
				'last' => true
			),
			'NotDate' => array (
				'rule' => 'date',
				'last' => true,
			)
		),

		'nominee' => array (
			'NotEmpty' => array (
				'rule' => 'notEmpty',
				'last' => true
			),
		),


		'payment_amount' => array(
			'NotEmpty' => array (
				'rule' => 'notEmpty',
				'last' => true
			),
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'last' => true,
			),
		),
	);
}