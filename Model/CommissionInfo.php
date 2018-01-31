<?php
class CommissionInfo extends AppModel {

	public $belongsTo = array(
		'MCorp'=>array(
		'className' => 'MCorp',
		'foreignKey'=> 'corp_id',
		'type'=>'inner',
		'fields' => array('MCorp.*'),
		)
	);

	public $validate = array(

		'appointers' => array(
			'OverMaxLength20' => array(
				'rule' => array('maxLength', 20),
				'last' => true,
				'allowEmpty' => true
			),
		),

		'first_commission' => array(
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'last' => true,
				'allowEmpty' => true
			),
		),

		'corp_fee' => array(
			'ErrorEmptyCorpFee' => array(
				'rule' => 'checkEmptyCorpFee',
			),
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'last' => true,
				'allowEmpty' => true
			),
		),

		'waste_collect_oath' => array(
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'last' => true,
				'allowEmpty' => true
			),
		),

		'attention' => array(
			'OverMaxLength1000' => array(
				'rule' => array('maxLength', 1000),
				'last' => true,
				'allowEmpty' => true
			),
		),

		'commission_dial' => array(
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'last' => true,
				'allowEmpty' => true
			),
			'OverMaxLength11' => array(
				'rule' => array('maxLength', 11),
				'last' => true,
				'allowEmpty' => true
			),
		),

		'official_name' => array(
			'OverMaxLength200' => array(
				'rule' => array('maxLength', 200),
				'last' => true,
				'allowEmpty' => true
			),
		),

		'tel_commission_datetime' => array(
			'NotDateTime' => array(
				'rule' => 'datetime',
				'last' => true,
				'allowEmpty' => true
			),
		),

		'tel_commission_person' => array(
			'OverMaxLength20' => array(
				'rule' => array('maxLength', 20),
				'last' => true,
				'allowEmpty' => true
			),
		),

		'commission_fee_rate' => array(
			'ErrorEmptyCommissionFeeRate' => array(
				'rule' => 'checkEmptyCommissionFeeRate',
			),
			// 2015.08.01 s.harada DEL start ORANGE-725
			/*
			'ErrorCommissionFeeRate' => array(
				'rule' => 'checkCommissionFeeRate',
			),
			*/
			// 2015.08.01 s.harada DEL start ORANGE-725
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'last' => true,
				'allowEmpty' => true
			),
		),

		'commission_note_sender' => array(
			'OverMaxLength20' => array(
				'rule' => array('maxLength', 20),
				'last' => true,
				'allowEmpty' => true
			),
		),

		'commission_note_send_datetime' => array(
			'NotDateTime' => array(
				'rule' => 'datetime',
				'last' => true,
				'allowEmpty' => true
			),
		),

		'commission_status' => array(
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'last' => true,
				'allowEmpty' => true
			),
			'ErrorCommissionStatusIntrocude' => array(
				'rule' => 'checkCommissionIntroduce',
			),
			'ErrorCommissionStatusComplete' => array(
				'rule' => 'checkCommissionStatusComplete',
			),
			'ErrorCommissionStatusOrderFail' => array(
				'rule' => 'checkCommissionStatusOrderFail',
			),
		),

		'commission_order_fail_reason' => array(
			'NotEmptyOrderFailDate' => array(
				'rule' => 'checkCommissionOrderFailReason',
			),
		),

		'construction_price_tax_exclude' => array(
			'EmptyConstructionPrice' => array(
				'rule' => 'checkCommissionOrderFailConstructionPrice',
				),
			'NotEmptyConstructionPrice' => array(
				'rule' => 'checkConstructionPrice',
			),
			'NoNumeric' => array(
					'rule' => array('numeric'),
					'last' => true,
					'allowEmpty' => true
			),
		),

		'tel_commission_person' => array(
			'OverMaxLength10' => array(
				'rule' => array('maxLength', 10),
				'last' => true,
				'allowEmpty' => true
			),
		),

		'complete_date' => array(
			'NotEmptyCompleteDate' => array(
				'rule' => 'checkCompleteDate',
			),
			// 2015.07.15 tanaka ORANGE-639 未来日入力制限無効化
			// 2015.07.27 s.harada MOD start ORANGE-639
			'ErrorFutureDate' => array(
				'rule' => 'checkFutureCompleteDate',
			),
			// 2015.07.27 s.harada MOD end ORANGE-639
			'OverMaxLength10' => array(
				'rule' => array('maxLength', 10),
				'last' => true,
				'allowEmpty' => true
			),
			// 2015.06.09 h.hara ORANGE-589(S)
// 			'NotDate' => array(
// 				'rule' => 'date',
// 				'last' => true,
// 				'allowEmpty' => true
// 			),
			'NotDateFormat' => array (
					'rule' => 'date',
					'rule' => 'checkCompleteDateFormat',
					'last' => true,
					'allowEmpty' => true
			),
			// 2015.06.09 h.hara ORANGE-589(S)
		),

		'order_fail_date' => array(
			'NotEmptyOrderFailDate' => array(
				'rule' => 'checkOrderFailDate',
			),
			'ErrorFutureDate' => array(
				'rule' => 'checkFutureOrderFailDate',
			),
			'OverMaxLength10' => array(
				'rule' => array('maxLength', 10),
				'last' => true,
				'allowEmpty' => true
			),
			// 2015.06.09 h.hara ORANGE-589(S)
// 			'NotDate' => array(
// 				'rule' => 'date',
// 				'last' => true,
// 				'allowEmpty' => true
// 			),
			'NotDateFormat' => array (
					'rule' => 'date',
					'rule' => 'checkOrderFailDateFormat',
					'last' => true,
					'allowEmpty' => true
			),
			// 2015.06.09 h.hara ORANGE-589(S)
		),

		'estimate_price_tax_exclude' => array(
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'last' => true,
				'allowEmpty' => true
			),
		),

		// 2015.5.1 n.kai MOD start ＊「0」とNULLを区別し、「0」はNGとしない
		//'construction_price_tax_include' => array(
		//	'NoNumeric' => array(
		//		'rule' => array('numeric'),
		//		'last' => true,
		//		'allowEmpty' => true
		//	),
		//),
		'construction_price_tax_include' => array(
			'NotEmptyConstructionPrice' => array(
				'rule' => 'checkConstructionPriceTaxInclude',
			),
			'NoNumeric' => array(
					'rule' => array('numeric'),
					'last' => true,
					'allowEmpty' => true
			),
		),
		// 2015.5.1 n.kai MOD end

		'deduction_tax_include' => array(
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'last' => true,
				'allowEmpty' => true
			),
		),

		'deduction_tax_exclude' => array(
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'last' => true,
				'allowEmpty' => true
			),
		),

		'confirmd_fee_rate' => array(
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'last' => true,
				'allowEmpty' => true
			),
		),

		'unit_price_calc_exclude' => array(
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'last' => true,
				'allowEmpty' => true
			),

		),

		'report_note' => array(
			'OverMaxLength1000' => array(
				'rule' => array('maxLength', 1000),
				'last' => true,
				'allowEmpty' => true
			),
		),

		'irregular_fee_rate' => array(
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'last' => true,
				'allowEmpty' => true
			),
		),

		'irregular_fee' => array(
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'last' => true,
				'allowEmpty' => true
			),
		),

		'reported_flg' => array(
			'FalsityNotCheck' => array(
				'rule' => 'checkFalsity',
			),
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'last' => true,
					),
			),
		'follow_date' => array(
			'NotDate' => array(
					'rule' => 'date',
					'last' => true,
					'allowEmpty' => true
			),
		),

		'business_trip_amount' => array(
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'last' => true,
				'allowEmpty' => true
			),
		),

	);

	public $csvFormat = array(
			'receipt_report' => array(
					// 2015.5.22 n.kai ADD start ＊案件ID 出力位置移動
					'DemandInfo.id' => '案件ID',
					// 2015.5.22 n.kai ADD end
					'MCorp.official_corp_name' => '企業名',
					'MGenre.genre_name' => 'ジャンル',
					// 2015.5.22 n.kai DEL ＊案件ID 出力位置移動
					// 2015.4.14 n.kai ADD start
					'CommissionInfo.id' => '取次ID',
					// 2015.4.14 n.kai ADD start
					'DemandInfo.jbr_order_no' => '[JBR様]受付No',
					'DemandInfo.customer_name' => 'お客様名',
					'CommissionInfo.complete_date' => '施工完了日',
					// 2015.4.13 n.kai MOD start
					//'CommissionInfo.construction_price_tax_exclude' => '施工金額(税抜)',
					'CommissionInfo.construction_price_tax_include' => '施工金額(税込)',
					// 2015.4.13 n.kai MOD end
					'MItem.item_name' => '見積書',
					'MItem2.item_name' => '領収書',
			),
			'auction_ranking' => array(
					'MCorp.official_corp_name' => '企業名',
					'CommissionInfo.corp_id' => '企業ID',
					'CommissionInfo.ranking' => '回数',
			),
	);



	/**
	 * 取次状況のチェック
	 *
	 * 通常取次の案件で紹介済みの場合はエラー
	 *
	 * @return boolean
	 */
	public function checkCommissionIntroduce() {
		if ($this->data ['CommissionInfo'] ['commission_type'] == Util::getDivValue ( 'commission_type', 'normal_commission' )) {
			if ($this->data ['CommissionInfo'] ['commission_status'] == Util::getDivValue ( 'construction_status', 'introduction' )) {
				return false;
			}
		}
		return true;
	}

	/**
	 * 取次状況のチェック
	 *
	 * 状況が【施工完了】でなくかつ、施工完了日または施工金額が入力されている場合エラー
	 *
	 * @return boolean
	 */
	public function checkCommissionStatusComplete() {
		if ($this->data ['CommissionInfo'] ['commission_status'] != Util::getDivValue ( 'construction_status', 'construction' ) &&
			$this->data ['CommissionInfo'] ['commission_status'] != Util::getDivValue ( 'construction_status', 'introduction' ) &&
			$this->data ['CommissionInfo'] ['commission_status'] != Util::getDivValue ( 'construction_status', 'order_fail' )) {
			if (! empty ( $this->data ['CommissionInfo'] ['complete_date'] ) || ! empty ( $this->data ['CommissionInfo'] ['construction_price_tax_exclude'] )) {
				return false;
			}
		}
		return true;
	}

	/**
	 * 取次状況のチェック
	 *
	 * 状況が【失注】でなくかつ、失注日が入力されている場合エラー
	 *
	 * @return boolean
	 */
	public function checkCommissionStatusOrderFail() {
		if ($this->data ['CommissionInfo'] ['commission_status'] != Util::getDivValue ( 'construction_status', 'order_fail' )) {
			if (! empty ( $this->data ['CommissionInfo'] ['order_fail_date'] )) {
				return false;
			}
		}
		return true;
	}

	/**
	 * 施工完了日のチェック
	 *
	 * 状況が【施工完了】かつ、施工完了日が未入力の場合エラー
	 *
	 * @return boolean
	 */
	public function checkCompleteDate() {
		if (empty ( $this->data ['CommissionInfo'] ['complete_date'] )) {
			if ($this->data ['CommissionInfo'] ['commission_status'] == Util::getDivValue ( 'construction_status', 'construction' )) {
				return false;
			}
		}
		return true;
	}

	/**
	 * 施工完了日のチェック
	 *
	 * 施工完了日が未来日の場合エラー
	 *
	 * @return boolean
	 */
	public function checkFutureCompleteDate(){
		// 2015.07.27 s.harada MOD start ORANGE-639
		// if (!empty ( $this->data ['CommissionInfo'] ['complete_date'] )) {
		$Session = new CakeSession();
		$user = $Session->read('Auth.User.auth');
		if (!empty ( $this->data ['CommissionInfo'] ['complete_date'] ) && $user != 'accounting' && $user != 'system') {
		// 2015.07.27 s.harada MOD end ORANGE-639
			if (strtotime($this->data ['CommissionInfo'] ['complete_date']) > strtotime(date('Y-m-d'))) {
				return false;
			}
		}
		return true;
	}

	/**
	 * 施工金額のチェック
	 *
	 * 状況が【施工完了】かつ、施工金額が未入力の場合エラー(「0」はエラーとしない)
	 *
	 * @return boolean
	 */
	public function checkConstructionPrice() {
		// 2015.5.1 n.kai MOD ＊「0」はエラーとしないようlengthチェックを追加
		if (empty ( $this->data ['CommissionInfo'] ['construction_price_tax_exclude'] ) && strlen($this->data ['CommissionInfo'] ['construction_price_tax_exclude'] ) == 0) {
			if ($this->data ['CommissionInfo'] ['commission_status'] == Util::getDivValue ( 'construction_status', 'construction' )) {
				return false;
			}
		}
		return true;
	}

	// 2015.5.1 n.kai ADD start
	/**
	 * 施工金額(税込)のチェック
	 *
	 * 状況が【施工完了】かつ、施工金額(税込)が未入力の場合エラー(「0」はエラーとしない)
	 *
	 * @return boolean
	 */
	public function checkConstructionPriceTaxInclude() {
		if (empty ( $this->data ['CommissionInfo'] ['construction_price_tax_include'] ) && strlen($this->data ['CommissionInfo'] ['construction_price_tax_include'] ) == 0) {
			if ($this->data ['CommissionInfo'] ['commission_status'] == Util::getDivValue ( 'construction_status', 'construction' )) {
				return false;
			}
		}
		return true;
	}
	// 2015.5.1 n.kai ADD end

	/**
	 * 失注日のチェック
	 *
	 * 状況が【失注】かつ、失注日が未入力の場合エラー
	 *
	 * @return boolean
	 */
	public function checkOrderFailDate() {
		if (empty ( $this->data ['CommissionInfo'] ['order_fail_date'] )) {
			if ($this->data ['CommissionInfo'] ['commission_status'] == Util::getDivValue ( 'construction_status', 'order_fail' )) {
				return false;
			}
		}
		return true;
	}

	/**
	 * 失注日のチェック
	 *
	 * 失注日が未来日の場合エラー
	 *
	 * @return boolean
	 */
	public function checkFutureOrderFailDate(){
		if (!empty ( $this->data ['CommissionInfo'] ['order_fail_date'] )) {
			if (strtotime($this->data ['CommissionInfo'] ['order_fail_date']) > strtotime(date('Y-m-d'))) {
				return false;
			}
		}
		return true;
	}

	/**
	 * 取次失注理由のチェック
	 *
	 * 状況が【失注】かつ、取次失注理由が未入力の場合エラー
	 *
	 * @return boolean
	 */
	public function checkCommissionOrderFailReason() {
		if (empty ( $this->data ['CommissionInfo'] ['commission_order_fail_reason'] )) {
			if ($this->data ['CommissionInfo'] ['commission_status'] == Util::getDivValue ( 'construction_status', 'order_fail' )) {
				return false;
			}
		}
		return true;
	}

	/**
	 * 施工金額のチェック
	 *
	 * 状況が【失注】かつ、施工金額が入力されている場合エラー
	 *
	 * @return boolean
	 */
	public function checkCommissionOrderFailConstructionPrice() {
		if (! empty ( $this->data ['CommissionInfo'] ['construction_price_tax_exclude'] )) {
			if ($this->data ['CommissionInfo'] ['commission_status'] == Util::getDivValue ( 'construction_status', 'order_fail' )) {
				return false;
			}
		}
		return true;
	}

	/**
	 * 取次先手数料のチェック
	 *
	 * 確定にチェックかつ取次先手数料が未入力の場合エラー
	 *
	 * @return boolean
	 */
	public function checkEmptyCorpFee() {
		if (isset ( $this->data ['CommissionInfo'] ['commit_flg'] )) {
			if (! empty ( $this->data ['CommissionInfo'] ['commit_flg'] )) {
				if (empty ( $this->data ['CommissionInfo'] ['corp_fee'] )) {
					return false;
				}
			}
		}
		return true;
	}

	/**
	 * 取次時手数料率のチェック
	 *
	 * 確定にチェックかつ取次時手数料率が未入力の場合エラー
	 *
	 * @return boolean
	 */
	public function checkEmptyCommissionFeeRate() {
		if (isset ( $this->data ['CommissionInfo'] ['commit_flg'] )) {
			if (! empty ( $this->data ['CommissionInfo'] ['commit_flg'] )) {
				if (empty ( $this->data ['CommissionInfo'] ['commission_fee_rate'] )) {
					return false;
				}
			}
		}
		return true;
	}

	/**
	 * 取次時手数料率のチェック
	 *
	 * 取次時手数料率が入力かつ、イレギュラー手数料金額も入力されている場合エラー
	 *
	 * @return boolean
	 */
	public function checkCommissionFeeRate() {
		if (isset ( $this->data ['CommissionInfo'] ['irregular_fee'] )) {
			if (! empty ( $this->data ['CommissionInfo'] ['irregular_fee'] ) && ! empty ( $this->data ['CommissionInfo'] ['commission_fee_rate'] )) {
				return false;
			}
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
		if (empty ( $this->data ['CommissionInfo'] ['reported_flg'] )) {
			return false;
		}
		return true;
	}

	/**
	 * 取次状況のチェック
	 *
	 * 状況が【施工完了】でなくかつ、イレギュラー手数料率またはイレギュラー手数料金額が入力されている場合エラー
	 *
	 * @return boolean
	 */
// 	public function checkCommissionStatus() {
// 		if ($this->data ['CommissionInfo'] ['commission_status'] != Util::getDivValue ( 'construction_status', 'construction' )) {
// 			if (isset ( $this->data ['BillInfo'] ['irregular_fee_rate'] )) {
// 				if (! empty ( $this->data ['BillInfo'] ['irregular_fee_rate'] )) {
// 					return false;
// 				}
// 			}
// 			if (isset ( $this->data ['BillInfo'] ['irregular_fee'] )) {
// 				if (! empty ( $this->data ['BillInfo'] ['irregular_fee'] )) {
// 					return false;
// 				}
// 			}
// 		}
// 		return true;
// 	}

	/**
	 * 電話取次日時のチェック
	 *
	 * 確定フラグが真の場合、電話取次日時が未入力はエラー
	 *
	 * @author y.tanaka
	 * @since  2015.05.13
	 * @return boolean
	 */
	public function checkTelCommissionDatetime(){
		if(empty($this->data['CommissionInfo']['tel_commission_datetime']) && $this->data['CommissionInfo']['commit_flg'] == 1){
			return false;
		}
		return true;
	}

	/**
	 * 取次票送信日時のチェック
	 *
	 * 確定フラグが真の場合、確定取次先の取次票送信日時が未入力はエラー
	 *
	 * @author y.tanaka
	 * @since  2015.05.13
	 * @return boolean
	 */
	public function checkCommissionNoteSendDatetime(){
		if(empty($this->data['CommissionInfo']['commission_note_send_datetime']) && $this->data['CommissionInfo']['commit_flg'] == 1){
			return false;
		}
		return true;
	}

	/**
	 * 電話取次日時のチェック
	 *
	 * 状況が【進行中】かつ、電話取次日時が未入力の場合エラー
	 *
	 * @return boolean
	 */
// 	public function checkTelCommission() {
// 		if (empty ( $this->data ['CommissionInfo'] ['tel_commission_datetime'] )) {
// 			if ($this->data ['CommissionInfo'] ['commission_status'] == Util::getDivValue ( 'construction_status', 'progression' )) {
// 				return false;
// 			}
// 		}
// 		return true;
// 	}

	/**
	 * 取次票送信日時のチェック
	 *
	 * 状況が【進行中】かつ、取次票送信日時が未入力の場合エラー
	 *
	 * @return boolean
	 */
// 	public function checkCommissionNoteSendDatetime() {
// 		if (empty ( $this->data ['CommissionInfo'] ['commission_note_send_datetime'] )) {
// 			if ($this->data ['CommissionInfo'] ['commission_status'] == Util::getDivValue ( 'construction_status', 'progression' )) {
// 				return false;
// 			}
// 		}
// 		return true;
// 	}

	/**
	 * 案件管理で案件状況によってバリデーションルールを追加する
	 *
	 * @author y.tanaka
	 * @since  2015.05.13
	 * @return CommissionInfo
	 */
	public function addValidateDemand($status) {

		$validator = $this->validator();

		if($status == Util::getDivValue('demand_status', 'telephone_already')) {	// 案件状況が【進行中】電話取次済の場合
			$validator['tel_commission_datetime'] = array(
			    'NotEmpty' => array(
				'rule' => 'checkTelCommissionDatetime'
			    )
			);
		}elseif($status == Util::getDivValue('demand_status','information_sent')) {	// 案件状況が【進行中】情報送信済の場合
			$validator['commission_note_send_datetime'] = array(
			    'NotEmpty' => array(
				'rule' => 'checkCommissionNoteSendDatetime'
			    )
			);
		}

		return $this;
	}

    /**
     * validateCheckLimitCommitFlg
     * MSiteの手動選定上限リミットをチェック
     *
     * @param $data
     * @return bool
     */
    public function validateCheckLimitCommitFlg($data)
    {
        $MSite = ClassRegistry::init('MSite');
        $maxNum = $MSite->findMaxLimit($data);
        $commissionInfoData = $data['CommissionInfo'];
        // hiddenで30件データが入っているので不要なデータは削除
        $checkData = array();
        foreach ($commissionInfoData as $item) {
            if (!empty($item['corp_id'])) {
                $checkData[] = $item;
            }
        }

        $commitFlgCount = 0;
        $commitFlgs = Hash::extract($checkData, '{n}.commit_flg');
        foreach ($commitFlgs as $item) {
            if ($item == '1') {
                $commitFlgCount++;
            }
        }

        return $maxNum >= $commitFlgCount;
    }


	public function beforeFind($query){
		parent::beforeFind($query);
		if (@$query['order']){
			// 並び替え設定あり
			foreach ($query['order'] as $order){
				if (strtoupper(@$order["{$this->name}.construction_price_tax_include"]) == 'ASC'){
					// construction_price_tax_include is null ASC を追加
					array_unshift($query['order'], array("({$this->name}.construction_price_tax_include is not null)" => 'asc'));
					break;
				}elseif (strtoupper(@$order["{$this->name}.construction_price_tax_include"]) == 'DESC'){
					// construction_price_tax_include is null ASC を追加
					array_unshift($query['order'], array("({$this->name}.construction_price_tax_include is not null)" => 'DESC'));
					break;
				}
				// 2015.04.10 y.kurokawa start
				elseif (strtoupper(@$order["{$this->name}.complete_date"]) == 'ASC'){
					array_unshift($query['order'], array("({$this->name}.complete_date is not null)" => 'ASC'));
					break;
				}elseif (strtoupper(@$order["{$this->name}.complete_date"]) == 'DESC'){
					array_unshift($query['order'], array("({$this->name}.complete_date is not null)" => 'DESC'));
					break;
				}elseif (strtoupper(@$order["{$this->name}.order_fail_date"]) == 'ASC'){
					array_unshift($query['order'], array("({$this->name}.order_fail_date is not null)" => 'ASC'));
					break;
				}elseif (strtoupper(@$order["{$this->name}.order_fail_date"]) == 'DESC'){
					array_unshift($query['order'], array("({$this->name}.order_fail_date is not null)" => 'DESC'));
					break;
				}
				// 2015.04.10 y.kurokawa end
			}
		}

		return $query;
	}

	// 2015.06.09 h.hara ORANGE-589(S)
	public function checkCompleteDateFormat() {
		return $this->checkDateFormat($this->data ['CommissionInfo'] ['complete_date']);
	}

	public function checkOrderFailDateFormat() {
		return $this->checkDateFormat($this->data ['CommissionInfo'] ['order_fail_date']);
	}

	// 日付形式チェック
	function checkDateFormat($date_value) {
		$date_part = explode('/', $date_value);
		switch (true) {
			case count($date_part) < 3:	// 「/」で分割した個数が3より少ない
				return false;
				break;
			case strlen($date_part[0]) < 4:	// 年が4桁未満
				return false;
				break;
			case strlen($date_part[1]) < 2:	// 月が4桁未満
				return false;
				break;
			case strlen($date_part[2]) < 2: // 日が4桁未満
				return false;
				break;
		}
		return true;
	}
	// 2015.06.09 h.hara ORANGE-589(E)

	/**
	 * 再取次レポート用データを取得
	 *
	 * @since  2015.10.19
	 * @return array
	 */
	protected function _findRecommission($state, $query, $results = array()) {
		$this->bindModel(array(
			'belongsTo' => array(
				'DemandInfo' => array(
					'className'  => 'DemandInfo',
					'foreignKey' => 'demand_id',
				)
			)
		));
		if ($state == 'before') {
			$query = array_merge($query, array(
				'fields' => array(
					'DemandInfo.id',
					'DemandInfo.tel1',
					'MCorp.official_corp_name',
					'MCorp.commission_dial',
					'MGenre.genre_name',
					'DemandInfo.customer_name',
					'CommissionInfo.commission_status',
					'CommissionSupport.support_kind',
					'CommissionSupport.correspond_status',
					'CommissionSupport.correspond_datetime',
					'CommissionSupport.order_fail_reason',
					'CommissionSupport.modified',
					'CommissionInfo.id',
					'CommissionInfo.re_commission_exclusion_status',
				),
				'conditions' => array(
					'CommissionInfo.commission_status' => array(1, 2, 4),
					'CommissionInfo.del_flg' => 0,
					'CommissionInfo.lost_flg' => 0,
				),
				'joins' => array(
					array(
						'table' => 'm_genres',
						'alias' => 'MGenre',
						'type' => 'inner',
						'conditions' => array(
							'DemandInfo.genre_id = MGenre.id',
						)
					),
					array(
						'table' => 'rits_commission_supports',
						'alias' => 'CommissionSupport',
						'type' => 'inner',
						'conditions' => array(
							'CommissionInfo.id = CommissionSupport.commission_id',
						),
					),
				)
			));

			return $query;
		}

		return $results;
	}

	/**
	 * 再取次レポート用データを取得
	 *
	 * @since  2015.10.19
	 * @param  array  $params
	 * @param  array  $sortPrarams
	 * @return array
	 */
	public function getSalseSupport($params = array(), $sortPrarams = array()) {

		$this->bindModel(array(
			'belongsTo' => array(
				'DemandInfo' => array(
					'className'  => 'DemandInfo',
					'foreignKey' => 'demand_id',
				)
			)
		));

		return $this->find('all', array(
			'fields' => array(
				'DemandInfo.id',
				'DemandInfo.tel1',
				'MCorp.official_corp_name',
				'MCorp.commission_dial',
				'MGenre.genre_name',
				'DemandInfo.customer_name',
				'MItem.item_name',
				'CommissionSupport.support_kind',
				'CommissionSupport.correspond_status',
				'CommissionSupport.correspond_datetime',
				'CommissionSupport.order_fail_reason',
				'CommissionSupport.modified',
				'CommissionInfo.id',
				'CommissionInfo.re_commission_exclusion_status',
				"MGenre.exclusion_flg",
			),
			'conditions' => $this->_salseSupportConditions($params),
			'joins' => array (
				array (
					'table'      => 'm_genres',
					'alias'      => 'MGenre',
					'type'       => 'inner',
					'conditions' => array (
						'DemandInfo.genre_id = MGenre.id',
					)
				),
				array (
					'table'      => 'rits_commission_supports',
					'alias'      => 'CommissionSupport',
					'type'       => 'inner',
					'conditions' => array (
						'CommissionInfo.id = CommissionSupport.commission_id',
						'OR' => array(
							array(
								'CommissionSupport.support_kind' => 'tel',
								// sasaki@tobila 2016.04.22 Mod Start ORANGE-1288
								// 9,10を追加。
								'CommissionSupport.correspond_status' => array(3, 4, 7, 8, 9, 10),
								// sasaki@tobila 2016.04.22 Mod End ORANGE-1288
							),
							array(
								'CommissionSupport.support_kind' => 'visit',
								// sasaki@tobila 2016.04.22 Mod Start ORANGE-1288
								// 9,10を追加。
								'CommissionSupport.correspond_status' => array(3, 4, 7, 8, 9, 10),
								// sasaki@tobila 2016.04.22 Mod End ORANGE-1288
							),
							array(
								'CommissionSupport.support_kind' => 'order',
								'CommissionSupport.correspond_status' => array(4, 5),
							),
						)
					),
				),
				array(
					'type'  => 'left',
					'table' => 'm_items',
					'alias' => 'MItem',
					'conditions' => array (
						'MItem.item_id = CommissionInfo.commission_status',
						'MItem.item_category' => __('commission_status', true),
					)
				)
			),
			'order' => $this->_sortOrder($sortPrarams),
		));
	}

	/**
	 * 再取次レポート用クエリー条件を取得
	 *
	 * @since  2015.10.19
	 * @param array  $params
	 * @return array
	 */
	protected function _salseSupportConditions($params = array()) {
		$conditions = array(
			'CommissionInfo.commission_status'              => array(1, 2, 4),
			'CommissionInfo.del_flg'                        => 0,
			'CommissionInfo.lost_flg'                       => 0,
			'CommissionInfo.re_commission_exclusion_status' => 0,
		);

		$lastStepStatus = Hash::get($params, 'last_step_status');

		if (!empty($lastStepStatus) && is_array($lastStepStatus)) {
			$conditions['OR'] = $this->_lastStepStatusCondition($lastStepStatus);
		}

		$conditions['MGenre.exclusion_flg'] = "0";
		if ($genre_id = Hash::get($params, 'genre_id')) {
			$conditions['DemandInfo.genre_id'] = $genre_id;
		}
		if ($support_kind = Hash::get($params, 'support_kind')) {
			$conditions['CommissionSupport.support_kind'] = $support_kind;
		}

		return $conditions;
	}

	/**
	 * 再取次最終STEP最新状況のクエリー条件を取得
	 * 1 : [電話対応]「検討(サービス自体)」
	 * 2 : [電話対応]「検討(日程調整)」
	 * 3 : [電話対応]「失注」
	 * 4 : [訪問対応]「検討(サービス自体)」
	 * 5 : [訪問対応]「検討(日程調整)」
	 * 6 : [訪問対応]「失注」
	 * 7 : [受注対応]「キャンセル」
	 *
	 * 2016/04/22 sasaki@tobila ORANGE-1288
	 * 3STEPでの入力項目変更に対応して以下を追加。
	 * 8 : [受注対応]「検討（加盟店様対応中）」
	 * 9 : [受注対応]「検討（営業支援対象）」
	 * 10: [訪問対応]「検討（加盟店様対応中）」
	 * 11: [訪問対応]「検討（営業支援対象）」
	 *
	 * @since  2016.01.12
	 * @param  array  $params
	 * @return array
	 */
	protected function _lastStepStatusCondition($params) {
		$condition = array();
		foreach ($params as $status) {
			switch ($status) {
// sasaki@tobila 2016.04.22 DEL Start ORANGE-1288
// 1,2については検索項目として非表示としたためこちらも削除。
// 				case 1:
// 					$condition[] = array(
// 						'CommissionSupport.support_kind'      => 'tel',
// 						'CommissionSupport.correspond_status' => 3,
// 					);
// 					break;
// 				case 2:
// 					$condition[] = array(
// 						'CommissionSupport.support_kind'      => 'tel',
// 						'CommissionSupport.correspond_status' => 4,
// 					);
// 					break;
// sasaki@tobila 2016.04.22 DEL End ORANGE-1288
				// sasaki@tobila 2016.04.22 Add Start ORANGE-1288
				case 8:
					$condition[] = array(
						'CommissionSupport.support_kind'      => 'tel',
						'CommissionSupport.correspond_status' => 9,
					);
					break;
				case 9:
					$condition[] = array(
						'CommissionSupport.support_kind'      => 'tel',
						'CommissionSupport.correspond_status' => 10,
					);
					break;
				// sasaki@tobila 2016.04.22 Add End ORANGE-1288
				case 3:
					$condition[] = array(
						'CommissionSupport.support_kind'      => 'tel',
						'CommissionSupport.correspond_status' => 7,
					);
					break;
// sasaki@tobila 2016.04.22 DEL Start ORANGE-1288
// 1,2については検索項目として非表示としたためこちらも削除。
// 				case 4:
// 					$condition[] = array(
// 						'CommissionSupport.support_kind'      => 'visit',
// 						'CommissionSupport.correspond_status' => 3,
// 					);
// 					break;
// 				case 5:
// 					$condition[] = array(
// 						'CommissionSupport.support_kind'      => 'visit',
// 						'CommissionSupport.correspond_status' => 4,
// 					);
// 					break;
// sasaki@tobila 2016.04.22 DEL End ORANGE-1288
				// sasaki@tobila 2016.04.22 Add Start ORANGE-1288
				case 10:
					$condition[] = array(
						'CommissionSupport.support_kind'      => 'visit',
						'CommissionSupport.correspond_status' => 9,
					);
					break;
				case 11:
					$condition[] = array(
						'CommissionSupport.support_kind'      => 'visit',
						'CommissionSupport.correspond_status' => 10,
					);
					break;
				// sasaki@tobila 2016.04.22 Add End ORANGE-1288
				case 6:
					$condition[] = array(
						'CommissionSupport.support_kind'      => 'visit',
						'CommissionSupport.correspond_status' => 7,
					);
					break;
				case 7:
					$condition[] = array(
						'CommissionSupport.support_kind'      => 'order',
						'CommissionSupport.correspond_status' => 4,
					);
					break;
			}
		}

		return $condition;
	}

	/**
	 * 再取次レポート用データを取得
	 *
	 * @since  2015.10.19
	 * @param  array  $sortPrarams
	 * @return array
	 */
	public function getAffiliationFollow($sortPrarams = array()) {

		$this->bindModel(array(
			'belongsTo' => array(
				'DemandInfo' => array(
					'className'  => 'DemandInfo',
					'foreignKey' => 'demand_id',
				)
			)
		));

		return $this->find('all', array(
			'fields' => array(
				'DemandInfo.id',
				'DemandInfo.tel1',
				'MCorp.official_corp_name',
				'MCorp.commission_dial',
				'DemandInfo.customer_name',
				'CommissionInfo.commission_status',
				'CommissionSupport.support_kind',
				'CommissionSupport.correspond_status',
				'CommissionSupport.correspond_datetime',
				'CommissionSupport.order_fail_reason',
				'CommissionSupport.modified',
				'CommissionInfo.id',
				'CommissionInfo.tel_support',
				'CommissionInfo.visit_support',
				'CommissionInfo.order_support',
				'EXTRACT(EPOCH FROM (NOW() - "CommissionSupport"."modified")/60) AS elapsed_time'
			),
			'conditions' => array(
				'CommissionInfo.commission_status' => array(1,2),
				'CommissionInfo.del_flg'           => 0,
				'CommissionInfo.lost_flg'          => 0,
				'OR' => array(
					'CommissionInfo.tel_support' => 1,
					'CommissionInfo.visit_support' => 1,
					'CommissionInfo.order_support' => 1,
				),
			),
			'joins' => array (
				array (
					'table'      => 'rits_commission_supports',
					'alias'      => 'CommissionSupport',
					'type'       => 'inner',
					'conditions' => array (
						'CommissionInfo.id = CommissionSupport.commission_id',
					),
				),
				array(
					'type' => 'left',
					'table' => 'm_items',
					'alias' => 'MItem',
					'conditions' => array (
						'MItem.item_id = CommissionInfo.commission_status',
						'MItem.item_category' => __('commission_status', true),
					)
				),
				array (
					'table'      => 'm_commission_alert_settings',
					'alias'      => 'AlertSetting',
					'type'       => 'inner',
					'conditions' => array (
						'CommissionSupport.support_kind = AlertSetting.phase_name',
						'CommissionSupport.correspond_status = AlertSetting.correspond_status',
						"NOW() > CommissionSupport.modified + cast(AlertSetting.rits_follow_datetime || ' minutes' as interval)  + cast(AlertSetting.condition_value_min || ' minutes' as interval)"
					),
				),
			),
			'order' => $this->_sortOrder($sortPrarams),
		));
	}

	/**
	 * ソート条件を取得
	 *
	 * @since  2015.10.19
	 * @param  array  $params
	 * @return array
	 */
	protected function _sortOrder($params = array()) {
		$order = array('CommissionInfo.id' => 'asc');

		if (!empty($params)) {
			$order = array($params['sort'] => $params['direction']);
		}

		return $order;
	}

	// 2016.02.16 ORANGE-1247 k.iwai ADD(S).
	/**
	 * 与信単価の合計を取得
	 *
	 */
	public function checkCreditSumPrice($corp_id = null, $genre_id = null, $display_price = false){
		App::import('Model', 'AffiliationInfo');
		App::import('Model', 'MGenre');
		$affiliation_info = new AffiliationInfo();
		$genre = new MGenre();
		$ai = $affiliation_info->findByCorpId($corp_id);

		$options = array(
			'fields' => array(
				'corp_id',
				'sum(CASE WHEN "CommissionInfo"."commission_type" = 0 THEN "MGenre"."credit_unit_price" '.
					'WHEN "CommissionInfo"."commission_type" = 1 AND "CommissionInfo"."introduction_not" = 0 THEN "MGenre"."credit_unit_price" '.
					'END)as sum_credit'
			),
			'conditions' => array(
				'CommissionInfo.corp_id' => $corp_id,
				'CommissionInfo.del_flg' => 0,
				'CommissionInfo.lost_flg' => 0,
				'CommissionInfo.commission_status !=' => 2,
				"to_char(CommissionInfo.commission_note_send_datetime, 'YYYY/MM') = '".date('Y/m')."'"
			),
			'joins' => array (
					array (
							'fields' => '*',
							'type' => 'inner',
							"table" => "demand_infos",
							"alias" => "DemandInfo",
							"conditions" => array (
//ORANGE-124 CHG(S)
									"CommissionInfo.demand_id = DemandInfo.id AND DemandInfo.del_flg = 0 AND DemandInfo.site_id != ".CREDIT_EXCLUSION_SITE_ID,
//ORANGE-124 CHG(E)
							)
					),
					array (
							'fields' => '*',
							'type' => 'inner',
							"table" => "m_genres",
							"alias" => "MGenre",
							"conditions" => array (
									"MGenre.id = DemandInfo.genre_id",
							)
					),
			),
			'group' => array('CommissionInfo.corp_id'),
		);

		// 2016.02.29 ORANGE-1247 k.iwai CHG(S).
		$sum_credit = 0;
		$result = CREDIT_NORMAL;

		$ci = $this->find('all', $options);

		if(!empty($ci[0][0]['sum_credit'])){
			$sum_credit = (int)$ci[0][0]['sum_credit'];
		}

		if(!empty($genre_id)){
			$g = $genre->findById($genre_id);
			$sum_credit += $g['MGenre']['credit_unit_price'];
		}

// 2016.08.25 murata.s ORANGE-151 CHG(S)
		if(is_null($ai['AffiliationInfo']['credit_limit'])){
			//与信限度額入力なし(NULL)に関しては、チェック対象外とする。
			$result = CREDIT_NORMAL;
		}elseif($sum_credit >= (int)$ai['AffiliationInfo']['credit_limit'] + (int)$ai['AffiliationInfo']['add_month_credit']){
			$result = CREDIT_DANGER;
		}elseif((((int)$ai['AffiliationInfo']['credit_limit'] + (int)$ai['AffiliationInfo']['add_month_credit']) * WARNING_CREDIT_RATE) <= $sum_credit){
			$result = CREDIT_WARNING;
		}
// 2016.08.25 murata.s ORANGE-151 CHG(E)

		// 2016.02.29 ORANGE-1247 k.iwai CHG(E).

		// 2016.05.30 通常状態に戻ったらメール送信フラグを初期化
		//if($result == CREDIT_NORMAL && $ai['AffiliationInfo']['credit_mail_send_flg'] <> 0){
		//	$affiliation_info->read(null, $ai['AffiliationInfo']['id']);
		//	$affiliation_info->set('credit_mail_send_flg', 0);
		//	if(!$affiliation_info->save()){
		//		//エラー処理
		//		$this->log('DefualtSet FAILURE: CreditMailSendFlg AffiliationInfo_Id: '.$ai['AffiliationInfo']['id'], LOG_ERR);
		//	}
		//}

//$this->log($ai, 'error');
//$this->log($sum_credit, 'error');
		//料金表示フラグがあれば料金を返す
		if($display_price){
			$result = $sum_credit;
		}

		return $result;
	}
	// 2016.02.16 ORANGE-1247 k.iwai ADD(E).

	// ORANGE-234 ADD S
	/**
	 * 保存前処理
	 * @param unknown $options
	 */
	public function get_correspond($id = null, $data=null){

		//pr($this->read());
		$correspond = '';

		//取次管理
		if(!empty($data['CommissionInfo'])){
			$columns = $this->__get_column();
			App::import('Model','CommissionInfo');
			$cinfo = new CommissionInfo();
			$ci = $cinfo->read(null, $id);

			foreach($data['CommissionInfo'] as $new_key => $new_value){
				foreach($ci['CommissionInfo'] as $old_key => $old_value){
					if($new_key == 'modified' || $new_key == 'created' || $new_key == 'modifiedby' || $new_key == 'createdby'){
						break;
					}

					if($new_key == $old_key){
						//日付フォーマット
						if($new_key == 'commission_note_send_datetime' || $new_key == 'tel_commission_datetime'){
							if(!empty($new_value)){
								$new_value = str_replace('/', '-', $new_value);
								$new_value = $new_value.':00';
							}
						}elseif($new_key == 'commission_status_last_updated'){
							break;
						}

						//pr($new_key.': '.$old_value.' → '.$new_value);
						if($new_value != $old_value){
							$comment = '';
							$col = '';
							$new_text = '';
							$old_text = '';

							foreach($columns as $column){
								if($column[0]['column_name'] == $new_key){
									$comment = $column[0]['column_comment'];
									$col = $column[0]['column_name'];

									$new_text = $this->__get_value('CommissionInfo', $column[0]['column_name'], $new_value);
									$old_text = $this->__get_value('CommissionInfo', $column[0]['column_name'], $old_value);
									break;
								}
							}
							$new = $new_value;
							$old = $old_value;
							if(!empty($new_text))$new = $new_text;
							if(!empty($old_text))$old = $old_text;
							$correspond .= $comment.' : '.$old.' → '.$new."\n";
						}
						break;
					}
				}
			}
		}

		//案件管理
		if(!empty($data['DemandInfo'])){
			$columns = $this->__get_column('demand_infos');
			App::import('Model','DemandInfo');
			$dinfo = new DemandInfo();
			$di = $dinfo->read(null, $data['DemandInfo']['id']);

			foreach($data['DemandInfo'] as $new_key => $new_value){
				foreach($di['DemandInfo'] as $old_key => $old_value){
					if($new_key == 'modified' || $new_key == 'created' || $new_key == 'modifiedby' || $new_key == 'createdby' || $new_key == 'demand_status'){
						break;
					}

					if($new_key == $old_key){


						//pr($new_key.': '.$old_value.' → '.$new_value);
						if($new_value != $old_value){
							$comment = '';
							$col = '';
							$new_text = '';
							$old_text = '';

							foreach($columns as $column){
								if($column[0]['column_name'] == $new_key){
									$comment = $column[0]['column_comment'];
									$col = $column[0]['column_name'];

									$new_text = $this->__get_value('DemandInfo', $column[0]['column_name'], $new_value);
									$old_text = $this->__get_value('DemandInfo', $column[0]['column_name'], $old_value);
									break;
								}
							}
							$new = $new_value;
							$old = $old_value;
							if(!empty($new_text))$new = $new_text;
							if(!empty($old_text))$old = $old_text;
							$correspond .= $comment.' : '.$old.' → '.$new."\n";
						}
						break;
					}
				}
			}
		}

		return $correspond;
	}

	/**
	 * カラム名を取得
	 * @return mixed
	 */
	private function __get_column($table_name = 'commission_infos'){

		$sql =
"select
	psat.relname as table_name,
	pa.attname as column_name,
	pd.description as column_comment,
	format_type(pa.atttypid, pa.atttypmod) as column_type
from
	pg_stat_all_tables psat,
	pg_description pd,
	pg_attribute pa
where
	psat.relname = '".$table_name."'
	and psat.relid = pd.objoid
	and pd.objsubid <> 0
	and pd.objoid = pa.attrelid
	and pd.objsubid = pa.attnum
order by
	pd.objsubid";
		return $this->query($sql, array(), false);
	}

	/**
	 * 値の取得
	 * @param unknown $col
	 */
	private function __get_value($table=null, $col = null, $val = null){

		// 2017.01.05 murata.s ORANGE-303 CHG(S)
		//if(empty($table) || empty($col) || !isset($val))return '';
		if(empty($table) || empty($col) || !isset($val) || $val == '')return '';
		// 2017.01.05 murata.s ORANGE-303 CHG(E)

		if($table == 'CommissionInfo'){
			if($col == 'irregular_reason'){$rtn = Util::getDropList(__('イレギュラー理由' , true));return $rtn[$val];}
			elseif($col == 're_commission_exclusion_status'){$rtn = array('0'=>'', '1'=>'成功', '2'=>'失敗' );return $rtn[$val];}
			elseif($col == 'reform_upsell_ic'){$rtn = array('1'=>'申請', '2'=>'認証', '3'=>'非認証' );return $rtn[$val];}
			elseif($col == 'commission_type'){
				App::import('Model', 'MCommissionType');
				$m_commission_type = new MCommissionType();
				$rtn = $m_commission_type->getList();
				return $rtn[$val];
			}
			elseif($col == 'commission_status'){$rtn = Util::getDropList(__('commission_status', true));return $rtn[$val];}
			elseif($col == 'commission_order_fail_reason'){$rtn = Util::getDropList(__('commission_order_fail_reason', true));return $rtn[$val];}
			elseif($col == 'progress_reported' || $col == 'unit_price_calc_exclude' || $col == 'first_commission' || $col == 'introduction_free' || $col == 'ac_commission_exclusion_flg' )
				{ $rtn=array('0' => 'チェック無', '1' => 'チェック有'); return $rtn[$val];}
			elseif($col == 'tel_support' || $col == 'visit_support' || $col == 'order_support'){ $rtn=array('0' => '対応中', '1' => '非対応'); return $rtn[$val]; }
			elseif($col == 'order_fee_unit'){ $rtn=array('0' => '円', '1' => '%'); return $rtn[$val]; }
			elseif($col == 'appointers' || $col == 'tel_commission_person' || $col == 'commission_note_sender'){
				App::import('Model', 'MUser');
				$m_user = new MUser();
				$rtn = $m_user->dropDownUser();
				return $rtn[$val];
			}

		}elseif($table == 'DemandInfo'){
			if($col == 'construction_class'){ $rtn = Util::getDropList('建物種別');return $rtn[$val];}
			elseif($col == 'demand_status'){ $rtn = Util::getDropList(__('demand_status', true));return $rtn[$val];}
			elseif($col == 'order_fail_reason'){ $rtn = Util::getDropList(__('order_fail_reason', true));return $rtn[$val];}
			elseif($col == 'jbr_work_contents'){ $rtn = Util::getDropList(__('jbr_work_contents', true));return $rtn[$val];}
			elseif($col == 'jbr_category'){ $rtn = Util::getDropList(__('jbr_category', true));return $rtn[$val];}
			elseif($col == 'jbr_estimate_status'){ $rtn = Util::getDropList(__('jbr_estimate_status', true));return $rtn[$val];}
			elseif($col == 'jbr_receipt_status'){ $rtn = Util::getDropList(__('jbr_receipt_status', true));return $rtn[$val];}
			elseif($col == 'pet_tombstone_demand'){ $rtn = Util::getDropList(__('pet_tombstone_demand' , true));return $rtn[$val];}
			elseif($col == 'sms_demand'){ $rtn = Util::getDropList(__('sms_demand' , true));return $rtn[$val];}
			elseif($col == 'special_measures'){ $rtn = Util::getDropList(__('案件特別施策' , true));return $rtn[$val];}
			elseif($col == 'acceptance_status'){ $rtn = Util::getDropList(__('受付ステータス' , true));return $rtn[$val];}
			elseif($col == 'priority'){ $rtn = array('0'=>'-', '1'=>'大至急', '2'=>'至急', '3'=>'通常' );return $rtn[$val];}
			elseif($col == 'riro_kureka'){ $rtn=array('0' => 'チェック無', '1' => 'チェック有'); return $rtn[$val];}
		}

		return '';
	}
	// ORANGE-234 ADD E

	//ORANGE-358 ADD S
	public function beforeSave($options = array()){

		//IDが存在する　かつ　Aliasが取次管理
		if(!empty($this->data['CommissionInfo']['id'])){
			//ユーザアカウントが加盟店 かつ　取次ステータスが失注
			if($_SESSION['Auth']['User']["auth"] == 'affiliation' && isset($this->data['CommissionInfo']['commission_status']) && $this->data['CommissionInfo']['commission_status'] == 4){
				App::import('Model','CommissionInfo');
				$cinfo = new CommissionInfo();
				$ci = $cinfo->read(null, $this->data['CommissionInfo']['id']);
				//元データの取次ステータスが進行中の場合のみ営業支援除外フラグを変更
				if($ci['CommissionInfo']['commission_status'] == 1 && !empty($ci['CommissionInfo']['re_commission_exclusion_status']) && $ci['CommissionInfo']['re_commission_exclusion_status'] == 2 )
					$this->data[$this->alias]['re_commission_exclusion_status'] = 0;
			}
		}

		parent::beforeSave($options);
	}
	//ORANGE-358 ADD E
}
