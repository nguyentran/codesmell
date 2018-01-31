<?php
class DemandInquiryAnswer extends AppModel {

	public $validate = array(

		'demand_id' => array(
			'NotEmpty' => array(
				'rule' => 'notEmpty',
				'last' => true,
			),
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'last' => true,
				'allowEmpty' => true
			),
		),

		'inquiry_id' => array(
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'last' => true,
				'allowEmpty' => true
			),
		),
	);

}