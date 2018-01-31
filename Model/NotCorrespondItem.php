<?php
class NotCorrespondItem extends AppModel{
	public $useTable = 'not_correspond_items';

	public $validate = array(
			'immediate_lower_limit' => array(
					'NotEmpty' => array(
							'rule' => 'notEmpty',
							'last' => true
					),
					'NoNumeric' => array(
							'rule' => array('numeric'),
							'last' => true
					)
			),
			'large_lower_limit' => array(
					'NotEmpty' => array(
							'rule' => 'notEmpty',
							'last' => true
					),
					'NoNumeric' => array(
							'rule' => array('numeric'),
							'last' => true
					)
			),
			'midium_lower_limit' => array(
					'NotEmpty' => array(
							'rule' => 'notEmpty',
							'last' => true,
					),
					'NoNumeric' => array(
							'rule' => array('numeric'),
							'last' => true
					)
			),
			'small_lower_limit' => array(
					'NotEmpty' => array(
							'rule' => 'notEmpty',
							'last' => true,
					),
					'NoNumeric' => array(
							'rule' => array('numeric'),
							'last' => true
					)
			),
			'immediate_date' => array(
					'NotEmpty' => array(
							'rule' => 'notEmpty',
							'last' => true,
					),
					'NoNumeric' => array(
							'rule' => array('numeric'),
							'last' => true
					)
			)
	);
}