<?php
class AutoCallItem extends AppModel{
	public $useTable = 'auto_call_items';

	public $validate = array(
			'asap' => array(
					'NoNumeric' => array(
							'rule' => array('numeric'),
							'last' => true,
							'allowEmpty' => true
					),
					'range' => array(
							'rule' => array('range', -1, 61),
							'last' => true,
							'message' => '0～60分以内で設定してください。',
							'allowEmpty' => true
					)
			),
			'immediately' => array(
					'NoNumeric' => array(
							'rule' => array('numeric'),
							'last' => true,
							'allowEmpty' => true
					),
					'range' => array(
							'rule' => array('range', -1, 61),
							'last' => true,
							'message' => '0～60分以内で設定してください。',
							'allowEmpty' => true
					)
			),
			'normal' => array(
					'NoNumeric' => array(
							'rule' => array('numeric'),
							'last' => true,
							'allowEmpty' => true
					),
					'range' => array(
							'rule' => array('range', -1, 61),
							'last' => true,
							'message' => '0～60分以内で設定してください。',
							'allowEmpty' => true
					)
			)
	);
}