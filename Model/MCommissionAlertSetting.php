<?php

class MCommissionAlertSetting extends AppModel
{

	public $validate = array(

		'condition_value' => array(
			'rule' => array('comparison', '>=', 1),
			'message' => '1以上を入力してください。',
			'allowEmpty' => true,
			'last' => true
		),

		'rits_follow_datetime' => array(
			'rule' => array('comparison', '>=', 1),
			'message' => '1以上を入力してください。',
			'allowEmpty' => true,
			'last' => true
		)

	);

}
