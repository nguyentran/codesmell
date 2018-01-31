<?php
class AffiliationStat extends AppModel {

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

		'category_id' => array(
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'allowEmpty' => true
			),
		),

		'commission_count_category' => array(
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'allowEmpty' => true
			),
		),

		'orders_count_category' => array(
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'allowEmpty' => true
			),
		),

		'commission_unit_price_category' => array(
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'allowEmpty' => true
			),
		),

		'sf_commission_unit_price' => array(
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'allowEmpty' => true
			),
		),

		'sf_commission_count_category' => array(
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'allowEmpty' => true
			),
		),

	);

}