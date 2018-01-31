<?php
class ProgDemandInfo extends AppModel {

	public $order ='receive_datetime';

	//通常案件バリデート ※取次状態による必須状況はdisabledで対応
	public $validate = array(
		'commission_status_update' => array(
			'NotEmpty' => array(
				'rule' => 'notEmpty',
				'message' => '選択してください',
				'on' => 'update',
				//'last' => true
			),
			'custom1' => array(
				'rule' => array('valid_fail_reason'),
				'message' => '失注理由を選択してください',
				'on' => 'update',
			),
		),
		'complete_date_update' => array(
			'NotEmpty' => array(
				'rule' => 'notEmpty',
				'message' => '入力してください',
				'on' => 'update',
				//'last' => true
			),
		),
		'diff_flg' => array(
			'custom' => array(
				'rule' => '/^[2-3]+$/i',
				//'last' => true,
				'required' => true,
				'allowEmpty' => false,
				'message' => '選択してください',
				'on' => 'update',
			),
		),
		'construction_price_tax_exclude_update' => array(
			'NotEmpty' => array(
					'rule' => 'notEmpty',
					'message' => '入力してください',
					'on' => 'update',
			),
			'naturalNumber' => array(
					'rule' => array('naturalNumber', true),
					'message' => '金額を入力してください',
					'on' => 'update',
			),
		),
		'commission_order_fail_reason_update' => array(
				'NotEmpty' => array(
						'rule' => 'notEmpty',
						'message' => '選択してください',
						'on' => 'update',
				),
		),

	);

	//失注理由用 カスタムバリデート
	public function valid_fail_reason($check){
		if($check['commission_status_update'] == 4){
			if(empty($this->data['ProgDemandInfo']['commission_order_fail_reason_update']))return false;
		}
		return true;
	}
}
?>