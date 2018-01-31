<?php
class AffiliationInfo extends AppModel {

	public $validate = array(

		'corp_id' => array(
			'NotEmpty' => array(
				'rule' => 'notEmpty',
				'last' => true
			),
		),

		'employees' => array(
			'OverMaxLength50' => array(
				'rule' => array('maxLength', 50),
				'last' => true,
				'allowEmpty' => true
			),
		),

		'max_commission' => array(
			'OverMaxLength50' => array(
				'rule' => array('maxLength', 50),
				'last' => true,
				'allowEmpty' => true
			),
		),

		'collection_method' => array(
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'allowEmpty' => true
			),
		),
// 2016.01.04 ORANGE-1247 k.iwai ADD(S)
		'credit_limit' => array(
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'allowEmpty' => false
			),
		),
		'add_month_credit' => array(
					'NoNumeric' => array(
							'rule' => array('numeric'),
							'allowEmpty' => false
					),
		),
		'virtual_account' => array(
					'NoNumeric' => array(
							'rule' => array('numeric'),
							'allowEmpty' => true
					),
		),
// 2016.01.04 ORANGE-1247 k.iwai ADD(E)
		'collection_method_others' => array(
			'OverMaxLength100' => array(
				'rule' => array('maxLength', 100),
				'last' => true,
				'allowEmpty' => true
			),
		),

		'liability_insurance' => array(
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'allowEmpty' => true
			),
		),

		'reg_follow_date1' => array(
			'OverMaxLength10' => array(
				'rule' => array('maxLength', 10),
				'last' => true,
				'allowEmpty' => true
			),
		),

		'reg_follow_date2' => array(
			'OverMaxLength10' => array(
				'rule' => array('maxLength', 10),
				'last' => true,
				'allowEmpty' => true
			),
		),

		'reg_follow_date3' => array(
			'OverMaxLength10' => array(
				'rule' => array('maxLength', 10),
				'last' => true,
				'allowEmpty' => true
			),
		),

		'waste_collect_oath' => array(
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'allowEmpty' => true
			),
		),

		'transfer_name' => array(
			'OverMaxLength100' => array(
				'rule' => array('maxLength', 100),
				'last' => true,
				'allowEmpty' => true
			),
		),

		'claim_count' => array(
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'allowEmpty' => true
			),
		),

		'claim_history' => array(
			'OverMaxLength1000' => array(
				'rule' => array('maxLength', 1000),
				'last' => true,
				'allowEmpty' => true
			),
		),

		'commission_count' => array(
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'allowEmpty' => true
			),
		),

		'weekly_commission_count' => array(
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'allowEmpty' => true
			),
		),

		'orders_count' => array(
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'allowEmpty' => true
			),
		),

		'orders_rate' => array(
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'allowEmpty' => true
			),
		),

		'construction_cost' => array(
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'allowEmpty' => true
			),
		),

		'fee' => array(
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'allowEmpty' => true
			),
		),

		'bill_price' => array(
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'allowEmpty' => true
			),
		),

		'payment_price' => array(
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'allowEmpty' => true
			),
		),

		'balance' => array(
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'allowEmpty' => true
			),
		),

		'construction_unit_price' => array(
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'allowEmpty' => true
			),
		),

		'commission_unit_price' => array(
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

		'sf_commission_count' => array(
			'NoNumeric' => array(
				'rule' => array('numeric'),
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

	);


	function beforeValidate($options){
		if ($this->data['AffiliationInfo']['reg_pdf_path']){

			$this->validate['reg_pdf_path'] = array(
					'InvalidPdfExtension' => array(
							'rule' => array('extension', array('pdf')),
							'last' => true
					),
					'OverMaxLength200' => array(
							'rule' => array('maxLength', 200),
							'last' => true,
							'allowEmpty' => true
					),
			);
		} else {
			$this->data['AffiliationInfo']['reg_pdf_path'] = null;
		}

		parent::beforeValidate($options);

	}

	public $csvFormat = array(
			'default' => array(
					'MCorp.id' => '企業ID',
					'MCorp.corp_name' => '企業名',
					'MCorp.corp_name_kana' => '企業名ふりがな',
					'MCorp.official_corp_name' => '正式企業名',
					// 2015.6.7 m.katsuki ADD start ORANGE-515
					'MCorp.corp_commission_status' => '取次状況',
					// 2015.6.7 m.katsuki ADD end
					'MCorp.affiliation_status' => '加盟状態',
					'MCorp.responsibility' => '責任者　※必須',
					'MCorp.corp_person' => '担当者　※必須',
					'MCorp.postcode' => '郵便番号',
					'MCorp.address1' => '都道府県　※必須',
					'MCorp.address2' => '市区町村　※必須',
					'MCorp.address3' => '町域　※必須',
					'MCorp.address4' => '丁目番地',
					'MCorp.building' => '建物名',
					'MCorp.room' => '部屋号数',
					'MCorp.trade_name1' => '屋号①',
					'MCorp.trade_name2' => '屋号②',
					'MCorp.commission_dial' => '取次用ダイヤル　※必須',
					'MCorp.tel1' => '電話番号①　※必須',
					'MCorp.tel2' => '電話番号②',
					'MCorp.mobile_tel' => '携帯電話番号',
					'MCorp.fax' => 'FAX番号　※必須',
					'MCorp.mailaddress_pc' => 'PCメール　※必須',
					'MCorp.mobile_mail_none' => '携帯メールなし　※必須',
					'MCorp.mobile_tel_type' => '携帯種別　※必須',
					'MCorp.mailaddress_mobile' => '携帯メール　※必須',
					'MCorp.url' => 'URL',
					'MCorp.target_range' => '対応範囲(半径km)',
					'MCorp.available_time' => '現場対応可能時間_旧',
					'MCorp.contactable_time' => '連絡可能時間_旧',
					'MCorp.contactable_support24hour' => '連絡可能時間_24H　※必須',
					'MCorp.contactable_time_other' => '連絡可能時間_その他　※必須',
					'MCorp.contactable_time_from' => '連絡可能時間_From　※必須',
					'MCorp.contactable_time_to' => '連絡可能時間_To　※必須',
					'MCorp.support24hour' => '営業時間_24H　※必須',
					'MCorp.available_time_other' => '営業時間_その他　※必須',
					'MCorp.available_time_from' => '営業時間_From　※必須',
					'MCorp.available_time_to' => '営業時間_To　※必須',
					'MCorp.holiday' => '休業日　※必須',
					'MCorp.free_estimate' => '無料見積対応',
					'MCorp.portalsite' => 'ポータルサイト掲載',
					'MCorp.reg_send_date' => '登録書発送日',
					'MCorp.reg_send_method' => '登録書発送方法',
					'MCorp.reg_collect_date' => '登録書回収日',
					'MCorp.ps_app_send_date' => 'PS申込書発送日',
					'MCorp.ps_app_collect_date' => 'PS申込書回収日',
					'MCorp.coordination_method' => '取次方法　※必須',
					'MCorp.prog_send_method' => '進捗表送付方法',
					//ORANGE-218 CHG S
					//'MCorp.prog_send_address' => '進捗表送付先',
					'MCorp.prog_send_mail_address' => '進捗表メール送付先',
					'MCorp.prog_send_fax' => '進捗表FAX送付先',
					//ORANGE-218 CHG E
					'MCorp.prog_irregular' => '進捗表イレギュラー',
					'MCorp.special_agreement_check' => '請求時特約確認要',
					'MCorp.bill_send_method' => '請求書送付方法',
					'MCorp.bill_send_address' => '請求書送付先',
					'MCorp.bill_irregular' => '請求書イレギュラー',
					'MCorp.special_agreement' => '特約事項',
					'MCorp.development_response' => '開拓時の反応',
					'MCorp.contract_date' => '獲得日',
					'MCorp.order_fail_date' => '失注日',
					'MCorp.geocode_lat' => '緯度',
					'MCorp.geocode_long' => '経度',
					'MCorp.note' => '備考欄',
					// 2015.09.07 n.kai ADD start ORANGE-816
					'MCorp.seikatsu110_id' => '生活110番ID',
					'MCorp.modified_user_id' => '企業情報更新ID',
					'MCorp.modified' => '企業情報更新日',
					'MCorp.corp_commission_type' => '企業取次形態',
					//ORANGE-126 2016.8.16 iwai S
					'MCorp.jbr_available_status' => 'JBR対応状況',
					//ORANGE-126 2016.8.16 iwai E
					// 2015.09.07 n.kai ADD end ORANGE-816
					'AffiliationInfo.id' => '加盟店情報ID',
					//'AffiliationInfo.employees' => '従業員数',
					//'AffiliationInfo.max_commission' => '月間最大取次数',
					//'AffiliationInfo.collection_method' => '代金徴収方法',
					//'AffiliationInfo.collection_method_others' => 'その他代金徴収方法',
					'AffiliationInfo.liability_insurance' => '賠償責任保険',
					'AffiliationInfo.reg_follow_date1' => '登録書後追い日1',
					'AffiliationInfo.reg_follow_date2' => '登録書後追い日2',
					'AffiliationInfo.reg_follow_date3' => '登録書後追い日3',
					'AffiliationInfo.waste_collect_oath' => '不用品回収誓約書',
					'AffiliationInfo.waste_collect_oath' => '振込名義',
					'AffiliationInfo.stop_category_name' => '取次STOPカテゴリ',
					'AffiliationInfo.claim_count' => '顧客クレーム回数',
					'AffiliationInfo.claim_history' => '顧客クレーム履歴',
					'AffiliationInfo.commission_count' => '取次件数',
					'AffiliationInfo.weekly_commission_count' => '取次件数(一週間)',
					'AffiliationInfo.orders_count' => '受注数',
					'AffiliationInfo.orders_rate' => '受注率',
					'AffiliationInfo.construction_cost' => '施工金額',
					'AffiliationInfo.fee' => '手数料金額',
					'AffiliationInfo.bill_price' => '請求金額',
					'AffiliationInfo.payment_price' => '入金金額',
					'AffiliationInfo.balance' => '残高',
					'AffiliationInfo.construction_unit_price' => '施工単価',
					'AffiliationInfo.commission_unit_price' => '取次単価',
					'AffiliationInfo.reg_info' => '登録書情報',
					'AffiliationInfo.reg_pdf_path' => '登録書PDF',
					'AffiliationInfo.attention' => '注意事項',
					// 2015.09.07 s.harada ADD start ORANGE-816
					'MCorp.mcc_modified' => 'ジャンル最終更新日',
					'MCorp.mct_modified' => '基本エリア最終更新日',
					// 2015.09.07 s.harada ADD end ORANGE-816
					// 2016.06.16 ORANGE-109 S
					'MCorp.corp_kind' => '法人・個人',
					'AffiliationInfo.capital_stock' => '資本金',
					'AffiliationInfo.employees' => '従業員数',
					'AffiliationInfo.listed_kind' => '上場',
					'AffiliationInfo.default_tax' => '税金',
					'AffiliationInfo.max_commission' => '月間最大取次数',
					'AffiliationInfo.collection_method' => '代金徴収方法',
					'AffiliationInfo.collection_method_others' => 'その他代金徴収方法',
					//'MCorp.' => '与信限度額残高',
					'AffiliationInfo.credit_limit' => '与信限度額',
					'AffiliationInfo.add_month_credit' => '当月振込前払金',
					'AffiliationInfo.virtual_account' => '与信振込口座番号',
					// 2016.06.16 ORANGE-109 E
					//ORANGE-103 S
					'MCorp.commission_accept_flg' => '契約更新フラグ',
					//ORANGE-103 E
					// 2016.12.08 murata.s ORANGE-250 ADD(S)
					'MCorp.auction_status' => '取次方法',
					// 2016.12.08 murata.s ORANGE-250 ADD(E)

					// 2017.09.22 e.takeuchi@SharingTechnology ORANGE-531 【加盟店管理】CSVの項目に支払サイト追加 CHG(S)
					'CorpAgreement.acceptation_date' => '初回契約承認日時' ,
					'MCorp.payment_site' => '支払サイト',
					// // murata.s ORANGE-400 ADD(S)
					// 'CorpAgreement.acceptation_date' => '初回契約承認日時'
					// // murata.s ORANGE-400 ADD(E)
					// 2017.09.22 e.takeuchi@SharingTechnology ORANGE-531 【加盟店管理】CSVの項目に支払サイト追加 CHG(E)
			),
	);

	// ORANGE-29 iwai 2016.05.24 S
	public function getMCorp($corp_id=null){
		$this->bindModel(array('belongsTo' => array('MCorp' => array('foreignKey' => 'corp_id'))));
		return $this->find('first', array(
				'fields' => array('AffiliationInfo.*', 'MCorp.*'),
				'conditions' => array('AffiliationInfo.corp_id' => $corp_id),
/*				'joins' => array(
						array (
								'fields' => '*',
								'type' => 'inner',
								'table' => 'm_corps',
								'alias' => 'MCorp',
								'conditions' => array (
										'MCorp.id = AffiliationInfo.corp_id'
								)
						),
				),
*/
		));
	}
	// ORANGE-29 iwai 2016.05.24 E
}
