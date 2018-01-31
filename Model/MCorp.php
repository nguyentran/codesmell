<?php
class MCorp extends AppModel {

	public $validate = array(

		'corp_name' => array(
			'NotEmpty' => array(
				'rule' => 'notEmpty',
				'last' => true
			),
			'OverMaxLength100' => array(
				'rule' => array('maxLength', 100),
				'last' => true,
			),
		),

		'corp_name_kana' => array(
			'NotEmpty' => array(
				'rule' => 'notEmpty',
				'last' => true
			),
			'OverMaxLength200' => array(
				'rule' => array('maxLength', 200),
				'last' => true,
			),
		),

		'official_corp_name' => array(
			'NotEmpty' => array(
				'rule' => 'notEmpty',
				'last' => true
			),
			'OverMaxLength200' => array(
				'rule' => array('maxLength', 200),
				'last' => true,
			),
		),

		'responsibility'=> array(
			'NotEmptyResponsibility' => array(
				'rule' => 'notEmpty',
				'last' => true
			),
			'OverMaxLength20' => array(
				'rule' => array('maxLength', 20),
				'last' => true,
				'allowEmpty' => true
			),
		),

		'corp_person'=> array(
			'NotEmptyCorpPerson' => array(
				'rule' => 'notEmpty',
				'last' => true
			),
			'OverMaxLength20' => array(
				'rule' => array('maxLength', 20),
				'last' => true,
				'allowEmpty' => true
			),
		),
// murata.s ORANGE-434 CHG(S)
		'postcode' => array(
			'OverMaxLength7' => array(
					'rule' => array('maxLength', 7),
					'last' => true,
					'allowEmpty' => true
			),
			'NoNumeric' => array(
					'rule' => array('numeric'),
					'last' => true,
					'allowEmpty' => true
			)
		),
// murata.s ORANGE-434 CHG(E)
		'address1' => array(
			'NotEmptyAddress1' => array(
				'rule' => 'notEmpty',
				'last' => true
			),
			'OverMaxLength10' => array(
				'rule' => array('maxLength', 10),
				'last' => true,
			),
		),

		'address2' => array(
			'NotEmptyAddress2' => array(
				'rule' => 'notEmpty',
				'last' => true
			),
			'OverMaxLength20' => array(
				'rule' => array('maxLength', 20),
				'last' => true,
			),
		),

		'address3' => array(
			'NotEmptyAddress3' => array(
				'rule' => 'notEmpty',
				'last' => true
			),
			'OverMaxLength100' => array(
				'rule' => array('maxLength', 100),
				'last' => true,
				'allowEmpty' => true
			),
		),

		'address4' => array(
			'OverMaxLength100' => array(
				'rule' => array('maxLength', 100),
				'last' => true,
				'allowEmpty' => true
			),
		),

		'building' => array(
			'OverMaxLength100' => array(
				'rule' => array('maxLength', 100),
				'last' => true,
				'allowEmpty' => true
			),
		),

		'room' => array(
			'OverMaxLength20' => array(
				'rule' => array('maxLength', 20),
				'last' => true,
				'allowEmpty' => true
			),
		),

		'trade_name1' => array(
			'OverMaxLength100' => array(
				'rule' => array('maxLength', 100),
				'last' => true,
				'allowEmpty' => true
			),
		),

		'trade_name2' => array(
			'OverMaxLength100' => array(
				'rule' => array('maxLength', 100),
				'last' => true,
				'allowEmpty' => true
			),
		),

		'commission_dial' => array(
			'NotEmpty' => array(
				'rule' => 'NotEmptyCommissionDial',
				'last' => true
			),
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

		'tel1' => array(
			'NotEmpty' => array(
				'rule' => 'notEmpty',
				'last' => true
			),
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'last' => true,
			),
			'OverMaxLength11' => array(
				'rule' => array('maxLength', 11),
				'last' => true,
			),
		),

		'tel2' => array(
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

		'mobile_tel' => array(
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

		'fax' => array(
			// 2015.06.02 h.hara ORANGE-502(S)
			'DisallowFaxEmpty' => array(
					'rule' => 'checkFaxByCoodinationMethod'
			// 2015.06.02 h.hara ORANGE-502(E)
			),

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

		'mailaddress_pc' => array(
			// 2015.06.02 h.hara ORANGE-502(S)
			'DisallowPcMailaddressEmpty' => array(
					'rule' => 'checkPcMailAddressByCoodinationMethod'
			// 2015.06.02 h.hara ORANGE-502(E)
			),
			'InvalidEmail' => array(
				'rule' => 'checkPcMailAddress',
				'allowEmpty' => true
			),
			'OverMaxLength255' => array(
				'rule' => array('maxLength', 255),
				'last' => true,
				'allowEmpty' => true
			),
		),

		'mailaddress_mobile' => array(
			'NotEmptyMailaddressMobile' => array(
				'rule' => 'checkMobileMailAddressByCoodinationMethod'
			),
			'InvalidEmail' => array(
				'rule' => 'checkMobileMailAddress',
				'allowEmpty' => true
			),
			'OverMaxLength255' => array(
				'rule' => array('maxLength', 255),
				'last' => true,
				'allowEmpty' => true
			),
		),

		'url' => array(
			'InvalidUrl' => array(
				'rule' => array('url'),
				'last' => true,
				'allowEmpty' => true
			),
			'OverMaxLength255' => array(
				'rule' => array('maxLength', 2048),
				'last' => true,
				'allowEmpty' => true
			),
		),

		'target_range' => array(
			'OverMaxLength20' => array(
				'rule' => array('maxLength', 20),
				'last' => true,
				'allowEmpty' => true
			),
		),

		'support24hour' => array(
			'NotSelect' => array(
				'rule' => 'checkSupport24hourNotSelect'
			),
			'BothSelect' => array(
				'rule' => 'checkSupport24hourBothSelect'
			),
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'allowEmpty' => true
			),
		),

		'available_time' => array(
			'OverMaxLength50' => array(
				'rule' => array('maxLength', 50),
				'last' => true,
				'allowEmpty' => true
			),
		),

		'available_time_from' => array(
			'NotEmptyFromTime' => array(
				'rule' => 'checkAvailableTimeFromNotEmpty'
			),
		),

		'available_time_to' => array(
			'NotEmptyToTime' => array(
				'rule' => 'checkAvailableTimeToNotEmpty'
			),
		),

		'contactable_support24hour' => array(
			'NotSelect' => array(
				'rule' => 'checkContactableSupport24hourNotSelect'
			),
			'BothSelect' => array(
				'rule' => 'checkContactableSupport24hourBothSelect'
			),
		),

		'contactable_time' => array(
			'OverMaxLength50' => array(
				'rule' => array('maxLength', 50),
				'last' => true,
				'allowEmpty' => true
			),
		),

		'contactable_time_from' => array(
			'NotEmptyFromTime' => array(
				'rule' => 'checkContactableTimeFromNotEmpty'
			),
		),

		'contactable_time_to' => array(
			'NotEmptyToTime' => array(
				'rule' => 'checkContactableTimeToNotEmpty'
			),
		),

		'free_estimate' => array(
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'allowEmpty' => true
			),
		),

		'portalsite' => array(
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'allowEmpty' => true
			),
		),

		'reg_send_date' => array(
			'NotDate' => array(
				'rule' => 'date',
				'last' => true,
				'allowEmpty' => true
			),
		),

		'reg_send_method' => array(
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'allowEmpty' => true
			),
		),

		'reg_collect_date' => array(
			'NotDate' => array(
				'rule' => 'date',
				'last' => true,
				'allowEmpty' => true
			),
		),

		'ps_app_send_date' => array(
			'NotDate' => array(
				'rule' => 'date',
				'last' => true,
				'allowEmpty' => true
			),
		),

		'ps_app_collect_date' => array(
			'NotDate' => array(
				'rule' => 'date',
				'last' => true,
				'allowEmpty' => true
				),
		),

		'coordination_method' => array(
			'NotEmptyCoordinationMethod' => array(
				'rule' => 'notEmpty',
				'last' => true
			),
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'allowEmpty' => true
			),
		),

		'prog_send_method' => array(
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'allowEmpty' => true
			),
		),
		//ORANGE-218 CHG S
//		'prog_send_address' => array(
//			'OverMaxLength500' => array(
//				'rule' => array('maxLength', 500),
//				'last' => true,
//				'allowEmpty' => true
//			),
//		),
		'prog_send_mail_address' => array(
				'OverMaxLength500' => array(
						'rule' => array('maxLength', 500),
						'last' => true,
						'allowEmpty' => true
				),
		),
		'prog_send_fax' => array(
				'OverMaxLength500' => array(
						'rule' => array('maxLength', 500),
						'last' => true,
						'allowEmpty' => true
					),
		),
		//ORANGE-218 CHG E
		'prog_irregular' => array(
			'OverMaxLength1000' => array(
				'rule' => array('maxLength', 1000),
				'last' => true,
				'allowEmpty' => true
			),
		),

		'bill_send_method' => array(
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'allowEmpty' => true
			),
		),

		'bill_send_address' => array(
			'OverMaxLength500' => array(
				'rule' => array('maxLength', 500),
				'last' => true,
				'allowEmpty' => true
			),
		),

		'bill_irregular' => array(
			'OverMaxLength1000' => array(
				'rule' => array('maxLength', 1000),
				'last' => true,
				'allowEmpty' => true
			),
		),

		'special_agreement' => array(
			'OverMaxLength1000' => array(
				'rule' => array('maxLength', 1000),
				'last' => true,
				'allowEmpty' => true
			),
		),

		'contract_date' => array(
			'NotDate' => array(
				'rule' => 'date',
				'last' => true,
				'allowEmpty' => true
			),
		),

		'order_fail_date' => array(
			'NotDate' => array(
				'rule' => 'date',
				'last' => true,
				'allowEmpty' => true
			),
		),

		'geocode_lat' => array(
			'OverMaxLength12' => array(
				'rule' => array('maxLength', 12),
				'last' => true,
				'allowEmpty' => true
			),
		),

		'geocode_long' => array(
			'OverMaxLength13' => array(
				'rule' => array('maxLength', 13),
				'last' => true,
				'allowEmpty' => true
			),
		),

		'note' => array(
			'OverMaxLength5000' => array(
				'rule' => array('maxLength', 5000),
				'last' => true,
				'allowEmpty' => true
			),
		),

		'follow_date' => array(
			'NotDateTime' => array(
				'rule' => 'datetime',
				'last' => true,
				'allowEmpty' => true
			),
		),

		'document_send_request_date' => array(
				'NotDate' => array(
						'rule' => 'date',
						'last' => true,
						'allowEmpty' => true
				),
		),

		'progress_check_tel' => array(
				'NoNumeric' => array(
						'rule' => array('numeric'),
						'last' => true,
						'allowEmpty' => true,
				),
				'OverMaxLength11' => array(
						'rule' => array('maxLength', 11),
						'last' => true,
				),
		),

		'progress_check_person' => array(
				'OverMaxLength200' => array(
						'rule' => array('maxLength', 200),
						'last' => true,
				),
		),

		'advertising_send_date' => array(
				'NotDate' => array(
						'rule' => 'date',
						'last' => true,
						'allowEmpty' => true
				),
		),

		'listed_media' => array(
				'OverMaxLength255' => array(
						'rule' => array('maxLength', 255),
						'last' => true,
				),
		),

		'affiliation_status' => array(
				'NotEmpty' => array(
						'rule' => 'notEmpty',
						'last' => true
				),
		),

		'special_agreement_check' => array(		// 2014.04.29 y.tanaka start
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'allowEmpty' => true
			),
		),						// 2014.04.29 y.tanaka end

		'mailaddress_auction' => array(
				'InvalidEmail' => array(
					'rule' => 'checkAuctionMailAddress',
					'allowEmpty' => true
				),
				'OverMaxLength255' => array(
						'rule' => array('maxLength', 255),
						'last' => true,
						'allowEmpty' => true
				),
		),

		'mobile_tel_type' => array(
			'NotEmptyMobileTelType' => array(
					'rule' => 'checkNotEmptyMobileTelType',
					'last' => true
			),
		),
		//ORANGE-83 iwai 2016.05.24 ADD(S)
		'responsibility_sei' => array(
				'NotEmpty' => array(
						'rule' => 'notEmpty',
						'last' => true,
						'message' => '代表者 姓は必須入力です'
				),
		),
		'responsibility_mei' => array(
				'NotEmpty' => array(
						'rule' => 'notEmpty',
						'last' => true,
						'message' => '代表者 名は必須入力です'
				),
		),
		//ORANGE-83 iwai 2016.05.24 ADD(E)
// 2016.07.28 murata.s ORANGE-132 ADD(S)
// TODO: 2016.07.29 murata.s 一時的に返金先口座のチェックをOFFとするためコメントアウト
// 		'refund_bank_name' => array(
// 				'NotEmpty' => array(
// 						'rule' => 'notEmpty',
// 						'allowEmpty' => false,
// 						'message' => '銀行名は必須です。'
// 				),
// 		),
// 		'refund_branch_name' => array(
// 				'NotEmpty' => array(
// 						'rule' => 'notEmpty',
// 						'allowEmpty' => false,
// 						'message' => '支店名は必須です。'
// 				)
// 		),
// 		'refund_account_type' => array(
// 				'NotEmpty' => array(
// 						'rule' => 'notEmpty',
// 						'allowEmpty' => false,
// 						'message' => '預金種別は必須です。'
// 				)
// 		),
// 		'refund_account' => array(
// 				'NotEmpty' => array(
// 						'rule' => 'notEmpty',
// 						'allowEmpty' => false,
// 						'message' => '口座番号は必須です。'
// 				)
// 		),
// TODO 2016.07.29 murata.s 返金先口座のチェックOFFのコメントアウト ここまで
		'support_language_employees' => array(
				'NoNumeric' => array(
						'rule' => array('numeric'),
						'allowEmpty' => true,
						'message' => '対応可能従業員数は半角数字で入力してください。'
				)
		),
// 2016.07.28 murata.s ORANGE-132 ADD(E)
// murata.s ORANGE-434 CHG(S)
			'representative_postcode' => array(
					'OverMaxLength7' => array(
							'rule' => array('maxLength', 7),
							'last' => true,
							'allowEmpty' => true
					),
			'NoNumeric' => array(
					'rule' => array('numeric'),
					'last' => true,
					'allowEmpty' => true
			)
		),
// murata.s ORANGE-434 CHG(E)
// 2016.07.28 murata.s ORANGE-138 ADD(S)
		'representative_address1' => array(
			'NotEmptyAddress1' => array(
				'rule' => 'notEmpty',
				'last' => true
			),
			'OverMaxLength10' => array(
				'rule' => array('maxLength', 10),
				'last' => true,
			),
		),

		'representative_address2' => array(
			'NotEmptyAddress2' => array(
				'rule' => 'notEmpty',
				'last' => true
			),
			'OverMaxLength20' => array(
				'rule' => array('maxLength', 20),
				'last' => true,
			),
		),

		'representative_address3' => array(
			'NotEmptyAddress3' => array(
				'rule' => 'notEmpty',
				'last' => true
			),
			'OverMaxLength100' => array(
				'rule' => array('maxLength', 100),
				'last' => true,
				'allowEmpty' => true
			),
		),
// 2016.07.28 murata.s ORANGE-138 ADD(E)

	);


	/**
	 * PC【;】区切りのメールアドレスチェック
	 *
	 * @return boolean
	 */
	public function checkPcMailAddress(){
		if(strpos($this->data['MCorp']['mailaddress_pc'], ';')){
			$mailaddress_pc_list = explode(';', $this->data['MCorp']['mailaddress_pc']);
			foreach ($mailaddress_pc_list as $mailaddress_pc) {
				if (!preg_match("/^([a-zA-Z0-9])+([a-zA-Z0-9\._-])*@([a-zA-Z0-9_-])+([a-zA-Z0-9\._-]+)+$/", $mailaddress_pc)) {
					return false;
				}
			}
		} else {
			if (!preg_match("/^([a-zA-Z0-9])+([a-zA-Z0-9\._-])*@([a-zA-Z0-9_-])+([a-zA-Z0-9\._-]+)+$/", $this->data['MCorp']['mailaddress_pc'])) {
				return false;
			}
		}
		return true;
	}

	/**
	 * 携帯【;】区切りのメールアドレスチェック
	 *
	 * @return boolean
	 */
	public function checkMobileMailAddress(){
		if(strpos($this->data['MCorp']['mailaddress_mobile'], ';')){
			$mailaddress_pc_list = explode(';', $this->data['MCorp']['mailaddress_mobile']);
			foreach ($mailaddress_pc_list as $mailaddress_pc) {
				if (!preg_match("/^([a-zA-Z0-9])+([a-zA-Z0-9\._-])*@([a-zA-Z0-9_-])+([a-zA-Z0-9\._-]+)+$/", $mailaddress_pc)) {
					return false;
				}
			}
		} else {
			if (!preg_match("/^([a-zA-Z0-9])+([a-zA-Z0-9\._-])*@([a-zA-Z0-9_-])+([a-zA-Z0-9\._-]+)+$/", $this->data['MCorp']['mailaddress_mobile'])) {
				return false;
			}
		}
		return true;
	}

	/**
	 * オークション【;】区切りのメールアドレスチェック
	 *
	 * @return boolean
	 */
	public function checkAuctionMailAddress(){
		if(strpos($this->data['MCorp']['mailaddress_auction'], ';')){
			$mailaddress_auction_list = explode(';', $this->data['MCorp']['mailaddress_auction']);
			foreach ($mailaddress_auction_list as $mailaddress_auction) {
				if (!preg_match("/^([a-zA-Z0-9])+([a-zA-Z0-9\._-])*@([a-zA-Z0-9_-])+([a-zA-Z0-9\._-]+)+$/", $mailaddress_auction)) {
					return false;
				}
			}
		} else {
			if (!preg_match("/^([a-zA-Z0-9])+([a-zA-Z0-9\._-])*@([a-zA-Z0-9_-])+([a-zA-Z0-9\._-]+)+$/", $this->data['MCorp']['mailaddress_auction'])) {
				return false;
			}
		}
		return true;
	}
	// 2015.06.02 h.hara ORANGE-502(S)
	/**
	 * PCアドレス必須入力チェック
	 *
	 * @return boolean
	 */
	public function checkPcMailAddressByCoodinationMethod(){
		switch ($this->data['MCorp']['coordination_method']) {
			case Util::getDivValue('coordination_method', 'mail_fax'):
			case Util::getDivValue('coordination_method', 'mail'):
			case Util::getDivValue('coordination_method', 'mail_app'):
			case Util::getDivValue('coordination_method', 'mail_fax_app'):
				if (empty($this->data['MCorp']['mailaddress_pc'])) {
					return false;
				}
				break;
			default:
				break;
		}

		return true;
	}

	/**
	 * FAX番号必須入力チェック
	 *
	 * @return boolean
	 */
	public function checkFaxByCoodinationMethod(){
		if ($_SESSION['Auth']['User']['auth'] == 'affiliation') {
			if ($this->data['MCorp']['affiliation_status'] != 0) {
				switch ($this->data['MCorp']['coordination_method']) {
					case Util::getDivValue('coordination_method', 'mail_fax'):
					case Util::getDivValue('coordination_method', 'fax'):
					case Util::getDivValue('coordination_method', 'mail_fax_app'):
						if (empty($this->data['MCorp']['fax'])) {
							return false;
						}
						break;
					default:
						break;
				}
			}
		}

		return true;
	}
	// 2015.06.02 h.hara ORANGE-502(E)

	// 2015.08.30 n.kai ADD start ORANGE-816
	/**
	 * 携帯メール必須入力チェック
	 *
	 * @return boolean
	 */
	public function checkMobileMailAddressByCoodinationMethod(){
		if ($_SESSION['Auth']['User']['auth'] == 'affiliation') {
			if ($this->data['MCorp']['affiliation_status'] != 0) {
				if ($this->data['MCorp']['corp_commission_type'] != 2) {
					switch ($this->data['MCorp']['coordination_method']) {
						case Util::getDivValue('coordination_method', 'mail_fax'):
						case Util::getDivValue('coordination_method', 'mail'):
							if ( $this->data['MCorp']['mobile_mail_none'] != 1 && empty($this->data['MCorp']['mailaddress_mobile']) ) {
								return false;
							}
							break;
						case Util::getDivValue('coordination_method', 'mail_app'):
						case Util::getDivValue('coordination_method', 'mail_fax_app'):
							if ( empty($this->data['MCorp']['mailaddress_mobile']) ) {
								return false;
							}
							break;
						default:
							break;
					}
				}
			}
		}

		return true;
	}

	public function checkNotEmptyMobileTelType(){
		if ($_SESSION['Auth']['User']['auth'] == 'affiliation') {
			if ($this->data['MCorp']['affiliation_status'] != 0) {
				if ($this->data['MCorp']['corp_commission_type'] != 2) {
					switch ($this->data['MCorp']['coordination_method']) {
						case Util::getDivValue('coordination_method', 'mail_fax'):
						case Util::getDivValue('coordination_method', 'mail'):
							if ( $this->data['MCorp']['mobile_mail_none'] != 1 && empty($this->data['MCorp']['mobile_tel_type']) ) {
								return false;
							}
							break;
						case Util::getDivValue('coordination_method', 'mail_app'):
						case Util::getDivValue('coordination_method', 'mail_fax_app'):
							if ( empty($this->data['MCorp']['mobile_tel_type']) ) {
								return false;
							}
							break;
						default:
							break;
					}
				}
			}
		}

		return true;
	}

	public function checkHolidayNotSelect(){
//		for($i = 1; $i < 10; $i++) {
//			if ( $this->data['MCorp']['holiday'][$i] == 1 ) {
//				$select = 1;
//			}
//		}
//		if ( $notselect != 1 ) {
		if (empty($this->data['holiday'])) {
			return false;
		}
		return true;
	}

	/**
	 * 営業時間入力チェック
	 *
	 * @return boolean
	 */
	public function checkSupport24hourNotSelect(){
		if ($_SESSION['Auth']['User']['auth'] == 'affiliation') {
			if ($this->data['MCorp']['affiliation_status'] != 0) {
				if ( ($this->data['MCorp']['support24hour'] != 1) && ($this->data['MCorp']['available_time_other'] != 1) ) {
					// 24時間対応、その他ともに未チェックの場合エラー
					return false;
				}
			}
		}
		return true;
	}

	public function checkSupport24hourBothSelect(){
		if ($_SESSION['Auth']['User']['auth'] == 'affiliation') {
			if ($this->data['MCorp']['affiliation_status'] != 0) {
				if ( ($this->data['MCorp']['support24hour'] == 1) && ($this->data['MCorp']['available_time_other'] == 1) ) {
					// 24時間対応、その他ともにチェックありの場合エラー(どちらか一方選択)
					return false;
				}
			}
		}
		return true;
	}

	public function checkAvailableTimeFromNotEmpty(){
		if ($_SESSION['Auth']['User']['auth'] == 'affiliation') {
			if ($this->data['MCorp']['affiliation_status'] != 0) {
				if ( ($this->data['MCorp']['support24hour'] != 1) && empty($this->data['MCorp']['available_time_from'] ) ) {
					return false;
				}
			}
		}
		return true;
	}

	public function checkAvailableTimeToNotEmpty(){
		if ($_SESSION['Auth']['User']['auth'] == 'affiliation') {
			if ($this->data['MCorp']['affiliation_status'] != 0) {
				if ( ($this->data['MCorp']['support24hour'] != 1) && empty($this->data['MCorp']['available_time_to'] ) ) {
					return false;
				}
			}
		}
		return true;
	}

	/**
	 * 連絡可能時間入力チェック
	 *
	 * @return boolean
	 */
	public function checkContactableSupport24hourNotSelect(){
		if ($_SESSION['Auth']['User']['auth'] == 'affiliation') {
			if ($this->data['MCorp']['affiliation_status'] != 0) {
				if ( ($this->data['MCorp']['contactable_support24hour'] != 1) && ($this->data['MCorp']['contactable_time_other'] != 1) ) {
					// 24時間対応、その他ともに未チェックの場合エラー
					return false;
				}
			}
		}
		return true;
	}

	public function checkContactableSupport24hourBothSelect(){
		if ($_SESSION['Auth']['User']['auth'] == 'affiliation') {
			if ($this->data['MCorp']['affiliation_status'] != 0) {
				if ( ($this->data['MCorp']['contactable_support24hour'] == 1) && ($this->data['MCorp']['contactable_time_other'] == 1) ) {
					// 24時間対応、その他ともにチェックありの場合エラー(どちらか一方選択)
					return false;
				}
			}
		}
		return true;
	}

	public function checkContactableTimeFromNotEmpty(){
		if ($_SESSION['Auth']['User']['auth'] == 'affiliation') {
			if ($this->data['MCorp']['affiliation_status'] != 0) {
				if ( ($this->data['MCorp']['contactable_support24hour'] != 1) && empty($this->data['MCorp']['contactable_time_from'] ) ) {
					return false;
				}
			}
		}
		return true;
	}

	public function checkContactableTimeToNotEmpty(){
		if ($_SESSION['Auth']['User']['auth'] == 'affiliation') {
			if ($this->data['MCorp']['affiliation_status'] != 0) {
				if ( ($this->data['MCorp']['contactable_support24hour'] != 1) && empty($this->data['MCorp']['contactable_time_to'] ) ) {
					return false;
				}
			}
		}
		return true;
	}

	/**
	 * 取次用ダイヤル必須入力チェック
	 *
	 * @return boolean
	 */
	public function NotEmptyCommissionDial(){
		if ($this->data['MCorp']['corp_commission_type'] != 2) {
			if ( empty($this->data['MCorp']['commission_dial']) ) {
				return false;
			}
		}
		return true;
	}

	// 2015.08.30 n.kai ADD end ORANGE-816

	// 2016.05.24 murata.s ORANGE-75 ADD(S)
	public function isCommissionStop($id = null){
		$accept_flg = $this->find('first', array(
				'fields' => array('commission_accept_flg'),
				'conditions' => array('id' => $id)
		));

		if(isset($accept_flg['MCorp']['commission_accept_flg'])
				&& $accept_flg['MCorp']['commission_accept_flg'] != 0
				&& $accept_flg['MCorp']['commission_accept_flg'] != 3){
			return true;
		}else{
			return false;
		}
	}
	// 2016.05.24 murata.s ORANGE-75 ADD(E)

	/**
	 * CSV項目
	 * @var array $csvFormat
	 */
	public $csvFormat = array(
			//ORANGE-199 ADD S, ORANGE-346 DEL S
//			'antisocial_follow' => array(
//					'MCorp.id' => '企業ID',
//					'MCorp.official_corp_name' => '正式企業名',
//					'MCorp.corp_name_kana' => '企業名ふりがな',
//					'MCorp.last_antisocial_check_date' => '反社チェック更新日時（前回）',
//					'MCorp.commission_dial' => '取次用ダイヤル',
//				)
			//ORANGE-199 ADD E, ORANGE-346 DEL S
	);

}