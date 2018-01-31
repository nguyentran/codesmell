<?php
class AdditionInfo extends AppModel {

	public $validate = array(

		'corp_id' => array(
			'NotEmpty' => array(
				'rule' => 'notEmpty',
				'last' => true
			),
		),

//		'demand_id' => array(
//			'NotEmpty' => array(
//				'rule' => 'notEmpty',
//				'last' => true
//			),
//			'ErrorAdditionInfoDemandId' => array(
//				'rule' => 'checkDemandId',
//			),
//			'NoNumeric' => array(
//				'rule' => array('numeric'),
//				'allowEmpty' => true
//			),
//		),

		'customer_name' => array(
			'NotEmpty' => array(
				'rule' => 'notEmpty',
				'last' => true
			),
			'OverMaxLength50' => array(
				'rule' => array('maxLength', 50),
				'last' => true,
			),
		),

		'genre_id' => array(
			'NotEmpty' => array(
				'rule' => 'notEmpty',
				'last' => true
			),
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'allowEmpty' => true
			),
		),

		'construction_price_tax_exclude' => array(
			'NotEmpty' => array(
				'rule' => 'notEmpty',
				'last' => true
			),
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'last' => true,
				'allowEmpty' => true
			),
		),

		'complete_date' => array(
			'NotEmpty' => array(
				'rule' => 'notEmpty',
				'last' => true
			),
			'NotDate' => array(
				'rule' => 'date',
				'last' => true,
				'allowEmpty' => true
			),
		),

		'note' => array(
			'OverMaxLength1000' => array(
				'rule' => array('maxLength', 1000),
				'last' => true,
				'allowEmpty' => true
			),
		),

		'falsity_flg' => array(
			'FalsityNotCheck' => array(
				'rule' => 'checkFalsity',
			),
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'last' => true,
			),
		),

		'demand_type_update' => array(
			'NotEmpty' => array(
				'rule' => 'notEmpty',
				'last' => true
			),
		),
	);

	/**
	 * 案件IDのチェック
	 *
	 * 企業IDに紐づく案件IDが存在しない場合エラー
	 *
	 * @return boolean
	 */
	public function checkDemandId(){
		App::import('Model','CommissionInfo');
		$CommissionInfo = new CommissionInfo;
		$params = array(
				'conditions' => array(
						'demand_id' => $this->data['AdditionInfo']['demand_id'],
						'corp_id' => $this->data['AdditionInfo']['corp_id'],
				)
		);
		$count = $CommissionInfo->find('count', $params);
		if($count == 0){
			return false;
		}
		return true;
	}

	/**
	 * 虚偽報告なしのチェック
	 *
	 * チェックが入っていない場合エラー（加盟店のみ表示）
	 *
	 * @return boolean
	 */
	public function checkFalsity() {
		if (empty ( $this->data ['AdditionInfo'] ['falsity_flg'] )) {
			return false;
		}
		return true;
	}

	//ORANGE-262 ADD S
	/**
	 * CSV行
	 * @var unknown
	 */
	public $csvFormat = array(
		'default' => array(
				'AdditionInfo.id' => 'ID',
				'AdditionInfo.corp_id' => '企業ID',
				'AdditionInfo.demand_id' => '案件ID',
				'AdditionInfo.customer_name' => 'お客様名',
				'MGenre.genre_name' => 'ジャンル名',
				'AdditionInfo.demand_type_update' => '案件属性',
				'AdditionInfo.construction_price_tax_exclude' => '施工金額(税抜)',
				'AdditionInfo.complete_date' => '施工完了日',
				'AdditionInfo.note' => '備考欄',
				'AdditionInfo.falsity' => '虚偽報告確認',
				'AdditionInfo.demand' => '案件発行済',
				'AdditionInfo.memo' => 'メモ',
				'AdditionInfo.created' => '作成日時',
				'AdditionInfo.created_user_id' => '作成者ID',
				'AdditionInfo.modified' => '更新日時',
				'AdditionInfo.modified_user_id' => '更新者ID',
		),
	);
	//ORANGE-262 ADD E


}
