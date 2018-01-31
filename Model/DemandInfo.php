<?php
class DemandInfo extends AppModel {

// murata.s ORANGE-261 CHG(S)
	public $hasMany = array(
		'CommissionInfo'=>array(
			'className' => 'CommissionInfo',
			'foreignKey'=>'demand_id',
			//'conditions'=>array('CommissionInfo.commission_type = 0'),
			'order'=>'CommissionInfo.id ASC',
		),
		'IntroduceInfo'=>array(
			'className' => 'CommissionInfo',
			'foreignKey'=>'demand_id',
			'conditions'=>array('IntroduceInfo.commission_type = 1'),
			'order'=>'IntroduceInfo.id ASC',
		),
		'DemandCorrespondHistory'=>array(
			'className' => 'DemandCorrespond',
			'foreignKey'=>'demand_id',
			'order'=>'DemandCorrespondHistory.id DESC',
		),
	);
// murata.s ORANGE-261 CHG(E)

	public $hasAndBelongsToMany = array(
		'MInquiry' => array(
			'className' => 'MInquiry',
			'joinTable' => 'DemandInquiryAnswer',
			'with'=>'DemandInquiryAnswer',
			'foreignKey' => 'demand_id',
			'associationForeignKey' => 'inquiry_id',
			'unique' => false,
		),
	);

	public $validate = array(

		'follow_date' => array(
		// 2015.06.21 h.hanaki (s) ORANGE-567 【案件管理】【取次管理】後追い日の必須入力の解除をお願い致します。
		//	'NotEmpty' => array(
		//		'rule' => 'notEmpty',
		//		'last' => true,
		//	),
		// 2015.06.21 h.hanaki (e) ORANGE-567 【案件管理】【取次管理】後追い日の必須入力の解除をお願い致します。
		// 2016.04.06 ota.r@tobila MOD START ORANGE-1329 【案件管理】「後追い日時」の復活　【TS対応中】
			'NotDate' => array(
				'rule' => 'datetime',
				'last' => true,
				'allowEmpty' => true
			),
		// 2015.11.19 h.hamalo DELETE ORANGE-811 【案件管理】【取次管理】　後追い日非表示
			'PastDate' => array(
				'rule' => 'checkDateFollowDate',
			),
		// 2016.04.06 ota.r@tobila MOD END ORANGE-1329 【案件管理】「後追い日時」の復活　【TS対応中】
		),

		'demand_status' => array(
			'NotEmpty' => array(
				'rule' => 'notEmpty',
				'last' => true,
			),
			'ErrorDemandStatus' => array(
				'rule' => 'checkDemandStatus',
				'last' => true,
			),
			'ErrorDemandStatusAdvance' => array(
				'rule' => 'checkDemandStatusAdvance',
				'last' => true,
			),
			'ErrorDemandStatusIntroduce' => array(
				'rule' => 'checkDemandStatusIntroduce',
				'last' => true,
			),
			'ErrorDemandStatusIntroduceMail' => array(
				'rule' => 'checkDemandStatusIntroduceMail',
				'last' => true,
			),
			'ErrorDemandStatusSelectionType' => array(
				'rule' => 'checkDemandStatusSelectionType',
				'last' => true,
			),
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'last' => true,
				'allowEmpty' => true
			),
			'ErrorDemandStatusConfirm' => array(
				'rule' => 'checkDemandStatusConfirm',
				'last' => true,
			),
		),

		'order_fail_reason' => array(
			'NotEmptyOrderFailReason' => array(
				'rule' => 'checkOrderFailReason',
			),
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'last' => true,
				'allowEmpty' => true
			),
		),

                // 2015.08.01 s.harada MOD start ORANGE-756
                /*
		'reservation_demand' => array(
				'NotEmpty' => array(
						'rule' => 'notEmpty',
						'last' => true,
						'allowEmpty' => false
				),
		),
		*/
		'reservation_demand' => array(
			// サイトが「000_生活救急主」の場合必須チェック解除
			'NotEmpty' => array(
				'rule' => 'checkReservationDemandNotEmpty',
			),
		),
		// 2015.08.01 s.harada MOD end ORANGE-756

		'mail_demand' => array(
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'last' => true,
				'allowEmpty' => true
			),
		),

		'nighttime_takeover' => array(
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'last' => true,
				'allowEmpty' => true
			),
		),

		'low_accuracy' => array(
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'last' => true,
				'allowEmpty' => true
			),
		),

		'remand' => array(
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'last' => true,
				'allowEmpty' => true
			),
		),

		'corp_change' => array(
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'last' => true,
				'allowEmpty' => true
			),
		),

		// 2017.08.24 e.takeuchi@SharingTechnology ORANGE-516 ADD(S)
		'sms_reorder' => array(
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'last' => true,
				'allowEmpty' => true
			),
		),
		// 2017.08.24 e.takeuchi@SharingTechnology ORANGE-516 ADD(E)

		'receive_datetime' => array(
			// 2015.08.31 h.hanaki ADD start ORANGE-833
			'NotEmpty' => array(
				'rule' => 'notEmpty',
				'last' => true,
			),
			// 2015.08.31 h.hanaki ADD end   ORANGE-833
			'NotDateTime' => array(
				'rule' => 'datetime',
				'last' => true,
				'allowEmpty' => true
			),
		),

		'site_id' => array(
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

		'genre_id' => array(
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

		'category_id' => array(
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

		'cross_sell_source_site' => array(
			// 2015.4.24 n.kai ADD start
			// サイトが「クロスセル案件」の場合必須チェック
			'NotEmpty' => array(
				'rule' => 'checkCrossSellSiteNotEmpty',
			),
			// 2015.4.24 n.kai ADD end
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'last' => true,
				'allowEmpty' => true
			),
		),

		// 2015.4.24 n.kai ADD start
		'cross_sell_source_genre' => array(
			// サイトが「クロスセル案件」の場合必須チェック
			'NotEmpty' => array(
				'rule' => 'checkCrossSellGenreNotEmpty',
			),
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'last' => true,
				'allowEmpty' => true
			),
		),
		// 2015.4.24 n.kai ADD end

		// 2015.06.15 h.hara ORANGE-526(S)
		'source_demand_id' => array(
			// サイトが「クロスセル案件」の場合必須チェック
			'NotEmpty' => array(
				'rule' => 'checkSourceDemandIdNotEmpty',
			),
		),
		// 2015.06.15 h.hara ORANGE-526(E)

		'cross_sell_source_category' => array(
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'last' => true,
				'allowEmpty' => true
			),
		),

		'receptionist' => array(
			'OverMaxLength20' => array(
				'rule' => array('maxLength', 20),
				'last' => true,
				'allowEmpty' => true
			),
		),

		'customer_name' => array(
			'NotEmpty' => array(
				'rule' => 'notEmpty',
				'last' => true,
			),
			'OverMaxLength50' => array(
				'rule' => array('maxLength', 50),
				'last' => true,
				'allowEmpty' => true
			),
		),

		// 2016.04.11 ota.r@tobila ADD START ORANGE-1308 【案件管理】ヒアリング情報に「法人名」の入力項目を追加お願いします。任意項目です。
		'customer_corp_name' => array(
			'OverMaxLength50' => array(
				'rule' => array('maxLength', 50),
				'last' => true,
				'allowEmpty' => true
			),
		),
		// 2016.04.11 ota.r@tobila ADD END ORANGE-1308 【案件管理】ヒアリング情報に「法人名」の入力項目を追加お願いします。任意項目です。

		'customer_tel' => array(
			'NotEmpty' => array(
				'rule' => 'notEmpty',
				'last' => true,
			),
			'OverMaxLength11' => array(
				'rule' => array('maxLength', 11),
				'last' => true,
				'allowEmpty' => true
			),
			'NoNumericAndNotification' => array(
				'rule' => 'checkCustomerTel',
			),
			/*
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'last' => true,
				'allowEmpty' => true
			),
			*/
		),

		'customer_mailaddress' => array(
			'OverMaxLength11' => array(
				'rule' => array('maxLength', 255),
				'last' => true,
				'allowEmpty' => true
			),
			'InvalidEmail' => array(
				'rule' => array('email'),
				'last' => true,
				'allowEmpty' => true
			),
		),

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
			),
		),

		'address1' => array(
			'NotEmpty' => array(
				'rule' => 'notEmpty',
				'last' => true,
			),
			'OverMaxLength10' => array(
				'rule' => array('maxLength', 10),
				'last' => true,
				'allowEmpty' => true
			),
		),

		'address2' => array(
			'NotEmpty' => array(
				'rule' => 'notEmpty',
				'last' => true,
			),
			'OverMaxLength20' => array(
				'rule' => array('maxLength', 20),
				'last' => true,
				'allowEmpty' => true
			),
		),

		'address3' => array(
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

		'tel1' => array(
			'NotEmptyTel1' => array(
				'rule' => 'checkTel1',
			),
			'OverMaxLength11' => array(
				'rule' => array('maxLength', 11),
				'last' => true,
				'allowEmpty' => true
			),
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'last' => true,
				'allowEmpty' => true
			),
		),

		'tel2' => array(
			'OverMaxLength11' => array(
				'rule' => array('maxLength', 11),
				'last' => true,
				'allowEmpty' => true
			),
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'last' => true,
				'allowEmpty' => true
			),
		),

		'contents' => array(
			'NotWordBanError' => array(
				'rule' => 'checkContentsString',
			),
			'OverMaxLength1000' => array(
				'rule' => array('maxLength', 1000),
				'last' => true,
				'allowEmpty' => true
			),
		),

		'contact_desired_time' => array(
			/*
			'NotEmptyContactDesiredTime' => array(
				'rule' => 'checkContactDesiredTime',
			),
			*/
			'NotEmpty' => array(
				'rule' => 'checkContactDesiredTime2',
			),
			'PastDateTime' => array(
				'rule' => 'checkContactDesiredTime3',
			),
			//ORANGE-998 2015.11.14
			//ORANGE-998 2015.11.19 n.kai 一時対応保留
			//'DoChangeDemandType' => array(
			//	'rule' => 'checkDoChangeDemandType',
			//	'message' => '訪問希望日時が設定されているため、連絡期限日時の設定はできません。'
			//),
			'OverMaxLength100' => array(
				'rule' => array('maxLength', 100),
				'last' => true,
				'allowEmpty' => true
			),
		),
		'contact_desired_time_from' => array(
			'PastDateTime' => array(
				'rule' => 'checkContactDesiredTime4',
			),
			'OverMaxLength100' => array(
				'rule' => array('maxLength', 100),
				'last' => true,
				'allowEmpty' => true
			),
			'RequireInputTo' => array(
				'rule' => 'checkRequireTo',
				'message' => "開始日時を入力した時は終了日時も必須入力です。"
			)
		),
		'contact_desired_time_to' => array(
			'PastDateTime' => array(
				'rule' => 'checkContactDesiredTime5',
			),
			'PastDateTime2' => array(
				'rule' => 'checkContactDesiredTime6',
				"message" => "開始日時より過去の日時が入力されています。"
			),
			'OverMaxLength100' => array(
				'rule' => array('maxLength', 100),
				'last' => true,
				'allowEmpty' => true
			),
			'RequireInputFrom' => array(
				'rule' => 'checkRequireFrom',
				'message' => "終了日時を入力した時は開始日時も必須入力です。"
			),
		),

		// 2015.07.24 s.harada ADD start ORANGE-659
		'pet_tombstone_demand' => array(
			'NotEmptyPetTombstoneDemand' => array(
				'rule' => 'checkPetTombstoneDemandNotEmpty',
			),
		),
		'sms_demand' => array(
			'NotEmptySmsDemand' => array(
				'rule' => 'checkSmsDemandNotEmpty',
			),
		),
		'order_no_marriage' => array(
			'NotEmptyOrderNoMarriage' => array(
				'rule' => 'checkOrderNoMarriageNotEmpty',
			),
		),
		// 2015.07.24 s.harada ADD end ORANGE-659

		'jbr_order_no' => array(
			'NotEmptyJbr' => array(
				'rule' => 'checkJbrOrderNo',
			),
		),

		'jbr_work_contents' => array(
			'NotEmptyJbr' => array(
			'rule' => 'checkJbrWorkContents',
			),
		),

		'jbr_category' => array(
			'NotEmptyJbrCategory' => array(
				'rule' => 'checkJbrCategory',
			),
			'NotEmptyJbr' => array(
				'rule' => 'checkJbrCategory2',
			),
		),

		'mail' => array(
			'OverMaxLength1000' => array(
				'rule' => array('maxLength', 1000),
				'last' => true,
				'allowEmpty' => true
			),
		),

		// 2015.06.17 h.hara ORANGE-589(S)
		'order_date' => array(
// 			'NotDate' => array(
// 				'rule' => 'date',
// 				'last' => true,
// 				'allowEmpty' => true
// 			),
			'NotDateFormat' => array (
					'rule' => 'date',
					'rule' => 'checkDateFormat',
					'last' => true,
					'allowEmpty' => true
			),
		),
		// 2015.06.17 h.hara ORANGE-589(E)

		'complete_date' => array(
			'NotDate' => array(
				'rule' => 'date',
				'last' => true,
				'allowEmpty' => true
			),
		),

		'order_fail_date' => array(
			'NotEmptyOorderFailDate' => array(
				'rule' => 'checkOorderFailDate',
			),
			'NotDate' => array(
				'rule' => 'date',
				'last' => true,
				'allowEmpty' => true
			),
		),

		'jbr_estimate_status' => array(
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'last' => true,
				'allowEmpty' => true
			),
		),

		'jbr_receipt_status' => array(
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'last' => true,
				'allowEmpty' => true
			),
		),
		'share_notice' => array(
				'OverMaxLength1000' => array(
						'rule' => array('maxLength', 1000),
						'last' => true,
						'allowEmpty' => true
				),
		),

		'selection_system' => array(
			'NotEmpty' => array(
				'rule' => 'notEmpty',
				'last' => true,
			),
			'ErrorSelectionSystem' => array(
				'rule' => 'checkSelectionSystem',
				'last' => true,
			),
			'ErrorAuctionSettingGenre' => array(
				'rule' => 'checkAuctionSettingGenre',
				'last' => true,
			),
		),

		'business_trip_amount' => array(
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'last' => true,
				'allowEmpty' => true
			),
		),

		'cost_from' => array(
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'last' => true,
				'allowEmpty' => true
			),
		),

		'cost_to' => array(
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'last' => true,
				'allowEmpty' => true
			),
		),

		// 2015.01.07 n.kai ORANGE-1027
		// 選定方式が手動の状態で再入札チェックを入れて案件登録はエラー
		'do_auction' => array(
			'ErrorDoAuction' => array(
				'rule' => 'checkDoAuction',
				'last' => true,
			),
		),

		// 2015.10.02 h.hanaki ADD       ORANGE-897
		'acceptance_status' => array(
			'NotEmpty' => array(
				'rule' => 'notEmpty',
				'last' => true,
			),
		),
		// 2015.10.10 h.hanaki ADD       ORANGE_AUCTION-17
		'construction_class' => array(
			'NotEmpty' => array(
				'rule' => 'notEmpty',
				'last' => true,
			),
		),
		// ORANGE-114 ADD S 2016.8.1
		'jbr_receipt_price' => array(
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'last' => true,
				'allowEmpty' => true
			),
		)
		// ORANGE-114 ADD E 2016.8.1
	);

	/**
	 * 再オークションチェック
	 *
	 * @return boolean
	 */
	public function checkDoAuction(){
		// 2015.01.07 n.kai MOD start ORANGE-1027
		// 選定方式が手動の状態(入札式以外)で再入札チェックを入れて案件登録はエラー
		if(!empty($this->data['DemandInfo']['do_auction'])){
// 2016.11.17 murata.s ORANGE-185 CHG(S)
//			if($this->data['DemandInfo']['selection_system'] != Util::getDivValue('selection_type', 'AuctionSelection')){
//				return false;
//			}
		if($this->data['DemandInfo']['selection_system'] != Util::getDivValue('selection_type', 'AuctionSelection')
			&& $this->data['DemandInfo']['selection_system'] != Util::getDivValue('selection_type', 'AutomaticAuctionSelection')){
			return false;
		}
// 2016.11.17 murata.s ORANGE-185 CHG(E)
		}

		return true;
	}

	/**
	 * オークション選定チェック
	 *
	 * ※ジャンルがオークション選定に設定されていなければエラー
	 *
	 * @return boolean
	 */
	public function checkSelectionSystem(){
		// 2016.01.12 n.kai ADD ORANGE-1198 ※入札が実行される場合のみチェックする
		if ( !empty($this->data['DemandInfo']['do_auction'])) {
			if ( $this->data['DemandInfo']['do_auction'] == 1 || $this->data['DemandInfo']['do_auction'] == 2 ) {
// 2016.11.17 murata.s ORANGE-185 CHG(S)
				if($this->data['DemandInfo']['selection_system'] == Util::getDivValue('selection_type', 'AuctionSelection')
						|| $this->data['DemandInfo']['selection_system'] == Util::getDivValue('selection_type', 'AutomaticAuctionSelection')){
					App::import('Model','SelectGenre'); //SelectGenreモデルを呼び出し
					App::import('Model','SelectGenrePrefecture'); //SelectGenrePrefectureモデルを呼び出し
					$SelectGenre = new SelectGenre;
					$SelectGenrePrefecture = new SelectGenrePrefecture;

					$genre_data = $SelectGenre->findByGenreId($this->data['DemandInfo']['genre_id']);
					if(empty($genre_data)){
						return false;
					}

// 					if($genre_data['SelectGenre']['select_type'] != Util::getDivValue('selection_type', 'AuctionSelection')){
// 						return false;
// 					} else {
// 						$genre_prefecture_data = $SelectGenrePrefecture->findByGenreIdAndPrefectureCd($this->data['DemandInfo']['genre_id'], $this->data['DemandInfo']['address1']);
// 						if(!empty($genre_prefecture_data)){
// 							if($genre_prefecture_data['SelectGenrePrefecture']['selection_type'] != "" && $genre_prefecture_data['SelectGenrePrefecture']['selection_type'] != NULL && $genre_prefecture_data['SelectGenrePrefecture']['selection_type'] != Util::getDivValue('selection_type', 'AuctionSelection')){
// 								return false;
// 							}
// 						}
// 					}
					if($genre_data['SelectGenre']['select_type'] != Util::getDivValue('selection_type', 'AuctionSelection')
							&& $genre_data['SelectGenre']['select_type'] != Util::getDivValue('selection_type', 'AutomaticAuctionSelection')){
						return false;
					} else {
						$genre_prefecture_data = $SelectGenrePrefecture->findByGenreIdAndPrefectureCd($this->data['DemandInfo']['genre_id'], $this->data['DemandInfo']['address1']);
						if(!empty($genre_prefecture_data)){
							if($genre_prefecture_data['SelectGenrePrefecture']['selection_type'] != ""
									&& $genre_prefecture_data['SelectGenrePrefecture']['selection_type'] != NULL
									&& ($genre_prefecture_data['SelectGenrePrefecture']['selection_type'] != Util::getDivValue('selection_type', 'AuctionSelection')
											&& $genre_prefecture_data['SelectGenrePrefecture']['selection_type'] != Util::getDivValue('selection_type', 'AutomaticAuctionSelection'))){
								return false;
							}
						}
					}
				}
// 2016.11.17 murata.s ORANGE-185 CHG(E)
			}
		}
		return true;
	}

	/**
	 * オークション選定チェック
	 *
	 * ※オークションジャンル別設定がされていなければエラー
	 *
	 * @return boolean
	 */
	public function checkAuctionSettingGenre(){
// 2016.11.17 murata.s OANGE-185 CHG(S)
		if($this->data['DemandInfo']['selection_system'] == Util::getDivValue('selection_type', 'AuctionSelection')
				|| $this->data['DemandInfo']['selection_system'] == Util::getDivValue('selection_type', 'AutomaticAuctionSelection')
				|| $this->data['DemandInfo']['selection_system'] == Util::getDivValue('selection_type', 'AutoSelection')){
			App::import('Model','AuctionGenre'); //AuctionGenreモデルを呼び出し
			$AuctionGenre = new AuctionGenre;
			$auction_genre_data = $AuctionGenre->findByGenreId($this->data['DemandInfo']['genre_id']);
			if(empty($auction_genre_data)){
				return false;
			}
		}
// 2016.11.17 murata.s ORANGE-185 CHG(E)
		return true;
	}

	/**
	 * 案件状況のチェック
	 *
	 * @return boolean
	 */
	public function checkDemandStatus(){

		if(isset($this->data['DemandInfo']['selection_system']) && $this->data['DemandInfo']['selection_system'] == Util::getDivValue('selection_type', 'AuctionSelection')){
			return true;
		}

// 2016.11.17 murata.s ORANGE-185 ADD(S)
		if(isset($this->data['DemandInfo']['selection_system']) && $this->data['DemandInfo']['selection_system'] == Util::getDivValue('selection_type', 'AutomaticAuctionSelection')){
			return true;
		}
// 2016.11.17 murata.s ORANGE-185 ADD(E)

		// 2015.01.25 inokuchi
		if($this->data['DemandInfo']['demand_status'] != Util::getDivValue('demand_status','no_selection') && $this->data['DemandInfo']['demand_status'] != Util::getDivValue('demand_status','no_guest')){
// murata.s ORANGE-261 DEL(S)
// 			// 2015.01.22 h.hara
// 			switch ($this->data['DemandInfo']['commission_type_div']) {
// 				case "2":
// 					return true;
// 					break;
// 				default:
// 					break;
// 			}
// murata.s ORANGE-261 DEL(E)

			for ($i = 0; $i < 30; $i++){
				if(isset($this->data['CommissionInfo'][$i])){
					if(!empty($this->data['CommissionInfo'][$i]['corp_id'])){
						if(empty($this->data['CommissionInfo'][$i]['lost_flg'])){
							return true;
						}
					}
				}
			}
// murata.s ORANGE-261 DEL(S)
// 			if(isset($this->data['IntroduceInfo'])){
// 				return true;
// 			}
// murata.s ORANGE-261 DEL(E)
			return false;
		}
		return true;
	}

	/**
	 * 案件状況のチェック
	 *加盟店が選定されている場合、「未選択」を選択できない。
	 * @return boolean
	 */
	public function checkDemandStatusIntroduce(){
		if(isset($this->data['DemandInfo']['demand_status']) && $this->data['DemandInfo']['demand_status'] == Util::getDivValue('demand_status','no_selection')){
// murata.s ORANGE-261 DEL(S)
// 			if(isset($this->data['IntroduceInfo'])){
// 				return false;
// 			}
// murata.s ORANGE-261 DEL(E)
			// 2016.01.05 n.kai ADD start ORANGE-1114
			$corp_cnt = 0;
			$lost_cnt = 0;
			$del_cnt  = 0;

			for ($i = 0; $i < 30; $i++) {
				if ( isset($this->data['CommissionInfo']) && !empty($this->data['CommissionInfo'][$i]['corp_id']) ) {
					// 加盟店が選定されている場合、カウントアップ。
					$corp_cnt++;
				}
				if ( isset($this->data['CommissionInfo']) && !empty($this->data['CommissionInfo'][$i]['corp_id']) && $this->data['CommissionInfo'][$i]['lost_flg'] == 1 ) {
					// 取次前失注にチェックされている加盟店の場合、カウントアップ。
					$lost_cnt++;
				} elseif ( isset($this->data['CommissionInfo']) && !empty($this->data['CommissionInfo'][$i]['corp_id']) && $this->data['CommissionInfo'][$i]['del_flg'] == 1 ) {
					// 削除にチェックされている加盟店の場合、カウントアップ。
					$del_cnt++;
				}
			}
			if ( $corp_cnt != ($lost_cnt + $del_cnt) ) {
				// 選定されている加盟店のうち、取次前失注・削除以外の加盟店が存在する場合、未選定状態ではない。
				return false;
			}
			// 2016.01.05 n.kai ADD end ORANGE-1114

			return true;
		}
		return true;
	}

	/**
	 * 案件状況のチェック
	 *案件状況で「加盟店確認中」する時
	 * @return boolean
	 */
	public function checkDemandStatusIntroduceMail(){
		//取次情報送信ボタン押下の場合は処理なし
		if ( isset($this->data['DemandInfo']['send_commission_info']) && $this->data['DemandInfo']['send_commission_info'] == 1 ) return true;

		if (isset($this->data['DemandInfo']['demand_status']) && $this->data['DemandInfo']['demand_status'] == Util::getDivValue('demand_status', 'agency_before')) {

			for ($i = 0; $i < 30; $i++) {
// murata.s ORANGE-261 DEL(S)
// 				if (isset($this->data['IntroduceInfo'][$i])) {
// 					if (!empty($this->data['IntroduceInfo'][$i]['send_mail_fax'])) {
// 						if ($this->data['IntroduceInfo'][$i]['send_mail_fax'] == 1) {
// 							return false;
// 						}
// 					}
// 				}
// murata.s ORANGE-261 DEL(E)
				if (isset($this->data['CommissionInfo'][$i])) {
					if (!empty($this->data['CommissionInfo'][$i]['send_mail_fax'])) {
						if ($this->data['CommissionInfo'][$i]['send_mail_fax'] == 1) {
							// 2016.01.21 n.kai ADD ※取次前失注・削除の加盟店考慮
							// メールFAX送信したが取次前失注・削除になった加盟店はNGとしない
							if (($this->data['CommissionInfo'][$i]['lost_flg'] == 0) && ($this->data['CommissionInfo'][$i]['del_flg'] == 0)) {
								return false;
							}
						}
					}
				}
			}
			return true;
		}
		return true;
	}

	/**
	 * 案件状況のチェック
	 *案件状況で「進行中」する時
	 * @return boolean
	 */
	public function checkDemandStatusIntroduceMail2(){
		// 2015.01.13 n.kai 入札式の場合は処理なし ORANGE_AUCTION-28
		if(isset($this->data['DemandInfo']['selection_system']) && $this->data['DemandInfo']['selection_system'] == Util::getDivValue('selection_type', 'AuctionSelection')){
			return true;
		}

// 2016.11.17 murata.s ORANGE-185 ADD(S)
		if(isset($this->data['DemandInfo']['selection_system']) && $this->data['DemandInfo']['selection_system'] == Util::getDivValue('selection_type', 'AutomaticAuctionSelection')){
			return true;
		}
// 2016.11.17 murata.s ORANGE-185 ADD(E)

		if (isset($this->data['DemandInfo']['demand_status']) && ($this->data['DemandInfo']['demand_status'] == Util::getDivValue('demand_status', 'telephone_already') || $this->data['DemandInfo']['demand_status'] == Util::getDivValue('demand_status', 'information_sent'))) {
			for ($i = 0; $i < 30; $i++) {
// murata.s ORANGE-261 DEL(S)
// 				if (isset($this->data['IntroduceInfo'][$i])) {
// 					// 2016.02.04 n.kai ORANGE-1266 メール/FAX送信済み、または個別送信しているか？
// 					if ( ($this->data['IntroduceInfo'][$i]['send_mail_fax'] == 1)
// 						or ($this->data['IntroduceInfo'][$i]['send_mail_fax_othersend'] == 1) ) {
// 						return true;
// 					}
// 				}
// murata.s ORANGE-261 DEL(E)
				if (isset($this->data['CommissionInfo'][$i])) {
					// 2016.02.04 n.kai ORANGE-1266,1270 確定の取次先が、メール/FAX送信済み、または個別送信しているか？
					if ( ($this->data['CommissionInfo'][$i]['commit_flg'] == 1) &&
						(($this->data['CommissionInfo'][$i]['send_mail_fax'] == 1) ||
						 ($this->data['CommissionInfo'][$i]['send_mail_fax_othersend'] == 1)) ) {
						return true;
					}
				}
			}
			return false;
		}
		return true;
	}

	// 2015.4.24 n.kai ADD start
	/**
	 * クロスセル元サイト必須チェック　※サイトが「クロスセル案件」時のみチェック
	 *
	 * @return boolean
	 */
	public function checkCrossSellSiteNotEmpty() {
		// サイトが「クロスセル案件」？
		// 2017.05.01 murata.s ORANGE-407, ORANGE-410 CHG(S)
		// 2015.06.15 h.hara ORANGE-526(S)
		//if ($this->data['DemandInfo']['site_id'] == 861) {
		if ($this->data['DemandInfo']['site_id'] == 861
				|| $this->data['DemandInfo']['site_id'] == 863
				|| $this->data['DemandInfo']['site_id'] == 889
				|| $this->data['DemandInfo']['site_id'] == 890
				|| $this->data['DemandInfo']['site_id'] == 1312
				|| $this->data['DemandInfo']['site_id'] == 1313
				|| $this->data['DemandInfo']['site_id'] == 1314) {
		// 2015.06.15 h.hara ORANGE-526(E)
			if ($this->data['DemandInfo']['cross_sell_source_site']  == null) {
				// 未選択の場合必須チェックエラー
				return false;
			}
		}
		// 2017.05.01 murata.s ORANGE-407, ORANGE-410 CHG(E)
		return true;
	}

	/**
	 * クロスセル元ジャンル必須チェック　※サイトが「クロスセル案件」時のみチェック
	 *
	 * @return boolean
	 */
	public function checkCrossSellGenreNotEmpty() {
		// サイトが「クロスセル案件」？
		// 2017.05.01 murata.s ORANGE-407, ORANGE-410 CHG(S)
		// 2015.06.15 h.hara ORANGE-526(S)
		//if ($this->data['DemandInfo']['site_id'] == 861) {
		if ($this->data['DemandInfo']['site_id'] == 861
				|| $this->data['DemandInfo']['site_id'] == 863
				|| $this->data['DemandInfo']['site_id'] == 889
				|| $this->data['DemandInfo']['site_id'] == 890
				|| $this->data['DemandInfo']['site_id'] == 1312
				|| $this->data['DemandInfo']['site_id'] == 1313
				|| $this->data['DemandInfo']['site_id'] == 1314) {
		// 2015.06.15 h.hara ORANGE-526(E)
			if ($this->data['DemandInfo']['cross_sell_source_genre'] == null) {
				// 未選択の場合必須チェックエラー
				return false;
			}
		}
		// 2017.05.01 murata.s ORANGE-407, ORANGE-410 CHG(E)
		return true;
	}
	// 2015.4.24 n.kai ADD end

	// 2015.06.15 h.hara ORANGE-526(S)
	/**
	 * 元案件番号必須チェック　※サイトが「クロスセル案件」時のみチェック
	 *
	 * @return boolean
	 */
	public function checkSourceDemandIdNotEmpty() {
		// サイトが「クロスセル案件」？
		// 2017.05.01 murata.s ORANGE-407, ORANGE-410 CHG(S)
		//if ($this->data['DemandInfo']['site_id'] == 861) {
		if ($this->data['DemandInfo']['site_id'] == 861
				|| $this->data['DemandInfo']['site_id'] == 863
				|| $this->data['DemandInfo']['site_id'] == 889
				|| $this->data['DemandInfo']['site_id'] == 890
				|| $this->data['DemandInfo']['site_id'] == 1312
				|| $this->data['DemandInfo']['site_id'] == 1313
				|| $this->data['DemandInfo']['site_id'] == 1314) {
			if ($this->data['DemandInfo']['source_demand_id'] == null) {
				// 未選択の場合必須チェックエラー
				return false;
			}
		}
		// 2017.05.01 murata.s ORANGE-407, ORANGE-410 CHG(E)
		return true;
	}
	// 2015.06.15 h.hara ORANGE-526(E)

	/**
	 * 案件状況のチェック
	 *
	 * @return boolean
	 */
	/**
	 * 案件状況のチェック
	 *
	 * @return boolean
	 */
	public function checkDemandStatusAdvance(){

		if(isset($this->data['DemandInfo']['selection_system']) && $this->data['DemandInfo']['selection_system'] == Util::getDivValue('selection_type', 'AuctionSelection')){
			return true;
		}

// 2016.11.17 murata.s ORANGE-185 ADD(S)
		if(isset($this->data['DemandInfo']['selection_system']) && $this->data['DemandInfo']['selection_system'] == Util::getDivValue('selection_type', 'AutomaticAuctionSelection')){
			return true;
		}
// 2016.11.17 murata.s ORANGE-185 ADD(E)

		if($this->data['DemandInfo']['demand_status'] == Util::getDivValue('demand_status','telephone_already') || $this->data['DemandInfo']['demand_status'] == Util::getDivValue('demand_status','information_sent')){
			for ($i = 0; $i < 30; $i++){
				if(isset($this->data['CommissionInfo'][$i])){
					if(!empty($this->data['CommissionInfo'][$i]['corp_id'])){
						if(empty($this->data['CommissionInfo'][$i]['commit_flg']) && empty($this->data['CommissionInfo'][$i]['lost_flg'])){
							return false;
						}
					}
				}
			}
		}
		return true;
	}

	/**
	 * 案件状況のチェック
	 *
	 * 選定方式が「入札式」で案件状況が「未選定」でない場合
	 *
	 * @return boolean
	 */
	public function checkDemandStatusSelectionType(){

		if(isset($this->data['DemandInfo']['selection_system']) && $this->data['DemandInfo']['selection_system'] != Util::getDivValue('selection_type', 'AuctionSelection')){
			return true;
		}
// 2016.11.17 murata.s ORANGE-185 ADD(S)
		if(isset($this->data['DemandInfo']['selection_system']) && $this->data['DemandInfo']['selection_system'] != Util::getDivValue('selection_type', 'AutomaticAuctionSelection')){
			return true;
		}
// 2016.11.17 murata.s ORANGE-185 ADD(E)
		// 2015/12/11 ADD K.Inokuchi オークション実行時のみ
		//if($this->data['DemandInfo']['demand_status'] != Util::getDivValue('demand_status','no_selection') && $this->data['DemandInfo']['demand_status'] != Util::getDivValue('demand_status','information_sent')){
		if(!empty($this->data['DemandInfo']['do_auction']) && $this->data['DemandInfo']['demand_status'] != Util::getDivValue('demand_status','no_selection')){
			return false;
		}
		return true;
	}

	/**
	 * ご相談内容のチェック
	 *
	 * 使用不可文字が入っていないか確認
	 *
	 * @return boolean
	 */
	public function checkContentsString(){
		if(!empty($this->data['DemandInfo']['contents'])){
			foreach (Util::getDivList('word_ban') as $keyword) {
				if (strstr($this->data['DemandInfo']['contents'] , $keyword)) {
					return false;
				}
			}
		}
		return true;
	}

	/**
	 * 数字又は「非通知」であるか確認
	 *
	 * @return boolean
	 */
	public function checkCustomerTel(){
		if(!empty($this->data['DemandInfo']['customer_tel'])){
			if(!ctype_digit($this->data['DemandInfo']['customer_tel']) && $this->data['DemandInfo']['customer_tel'] != '非通知'){
				return false;
			}
		}
		return true;
	}

	/**
	 * 連絡希望日時のチェック
	 *
	 * 状況が【失注】か【お客様不在】以外で、連絡希望日時が未入力ならエラー
	 *
	 * @return boolean
	 */
	public function checkContactDesiredTime(){
		// オークション選定の場合の処理
		if(isset($this->data['DemandInfo']['selection_system']) && $this->data['DemandInfo']['selection_system'] == Util::getDivValue('selection_type', 'AuctionSelection')){
			return true;
		}

// 2016.11.17 murata.s ORANGE-185 ADD(S)
		if(isset($this->data['DemandInfo']['selection_system']) && $this->data['DemandInfo']['selection_system'] == Util::getDivValue('selection_type', 'AutomaticAuctionSelection')){
			return true;
		}
// 2016.11.17 murata.s ORANGE-185 ADD(E)

		if(empty($this->data['DemandInfo']['contact_desired_time']) && (empty($this->data['DemandInfo']['contact_desired_time_from']) || empty($this->data['DemandInfo']['contact_desired_time_to']))){
			if($this->data['DemandInfo']['demand_status'] != Util::getDivValue('demand_status','no_guest') &&
			$this->data['DemandInfo']['demand_status'] != Util::getDivValue('demand_status','order_fail')){
				return false;
			}
		}
		return true;
	}

	/**
	 * 連絡希望日時のチェック
	 *
	 * 訪問日時、連絡希望日時が空の場合
	 *
	 * @return boolean
	 */
	public function checkContactDesiredTime2(){

		if(empty($this->data['DemandInfo']['contact_desired_time']) && empty($this->data['DemandInfo']['contact_desired_time_from'])) {
			foreach ($this->data['VisitTime'] as $val){
				if(!empty($val['visit_time']) || (!empty($val['visit_time_from']) && !empty($val['visit_time_to']))){
					return true;
				}
			}
			return false;
		}
		return true;
	}

	/**
	 * 連絡希望日時のチェック
	 *
	 * 選定方式がオークション選定の場合の過去日チェック
	 *
	 * @return boolean
	 */
	public function checkContactDesiredTime3(){
		if(!empty($this->data['DemandInfo']["contact_desired_time"])){
			if(!empty($this->data['DemandInfo']['do_auction'])){
				if(strtotime($this->data['DemandInfo']["contact_desired_time"]) < strtotime(date('Y/m/d H:i'))){
					return false;
				}
			}
		}
		return true;
	}
	/**
	 * 連絡希望日時fromのチェック
	 *
	 * 選定方式がオークション選定の場合の過去日チェック
	 *
	 * @return boolean
	 */
	public function checkContactDesiredTime4(){
		if(!empty($this->data['DemandInfo']["contact_desired_time_from"])){
			if(!empty($this->data['DemandInfo']['do_auction'])){
				if(strtotime($this->data['DemandInfo']["contact_desired_time_from"]) < strtotime(date('Y/m/d H:i'))){
					return false;
				}
			}
		}
		return true;
	}

	/**
	 * 連絡希望日時toのチェック
	 *
	 * 選定方式がオークション選定の場合の過去日チェック
	 *
	 * @return boolean
	 */
	public function checkContactDesiredTime5(){
		if(!empty($this->data['DemandInfo']["contact_desired_time_to"])){
			if(!empty($this->data['DemandInfo']['do_auction'])){
				if(strtotime($this->data['DemandInfo']["contact_desired_time_to"]) < strtotime(date('Y/m/d H:i'))){
					return false;
				}
			}
		}
		return true;
	}

	/**
	 * 連絡希望日時toのチェック
	 *
	 * fromの日時より過去に設定されていないかのチェック
	 *
	 * @return boolean
	 */
	public function checkContactDesiredTime6(){
		if(!empty($this->data['DemandInfo']["contact_desired_time_to"]) && !empty($this->data['DemandInfo']["contact_desired_time_from"])){
			if(strtotime($this->data['DemandInfo']["contact_desired_time_to"]) < strtotime($this->data['DemandInfo']["contact_desired_time_from"])){
				return false;
			}
		}
		return true;
	}

	/**
	 * 後追い日のチェック
	 *
	 * 後追い日が過去日付を入力されているとエラー
	 *
	 * @return boolean
	 */
	public function checkDateFollowDate(){
		if(strtotime($this->data['DemandInfo']['follow_date']) < strtotime(date('Y/m/d'))) {
			return false;
		}
		return true;
	}

	/**
	 * 連絡先①のチェック
	 *
	 * 状況が【失注】以外で、お客様指定の電話連絡先①が未入力だとエラー
	 *
	 * @return boolean
	 */
	public function checkTel1(){
		if(empty($this->data['DemandInfo']['tel1'])){
			if($this->data['DemandInfo']['demand_status'] != Util::getDivValue('demand_status','order_fail')){
				return false;
			}
		}
		return true;
	}

	/**
	 * 失注理由のチェック
	 *
	 * 状況が【失注】の場合、失注理由が選択されていなければエラー
	 *
	 * @return boolean
	 */
	public function checkOrderFailReason(){
		if(empty($this->data['DemandInfo']['order_fail_reason'])){
			if($this->data['DemandInfo']['demand_status'] == Util::getDivValue('demand_status','order_fail')){
				return false;
			}
		}
		return true;
	}

	/**
	 * 失注日のチェック
	 *
	 * 状況が【失注】の場合、受失注日がブランクはエラー
	 *
	 * @return boolean
	 */
	public function checkOorderFailDate(){
		if(empty($this->data['DemandInfo']['order_fail_date'])){
			if($this->data['DemandInfo']['demand_status'] == Util::getDivValue('demand_status','order_fail')){
				return false;
			}
		}
		return true;

	}

	/**
	 * JBRカテゴリのチェック
	 *
	 * JBRの作業内容が害虫駆除なら、JBRカテゴリ未入力でエラー
	 *
	 * @return boolean
	 */
	public function checkJbrCategory(){
		if(empty($this->data['DemandInfo']['jbr_category'])){
			if($this->data['DemandInfo']['jbr_work_contents'] == Util::getDivValue('jbr_work','pest_extermination')){
				return false;
			}
		}
		return true;
	}

	/**
	 * [JBR様]受付Noのチェック
	 *
	 * サイト名がJBR生活救急車 の場合、JBR情報未入力でエラー
	 *
	 * @return boolean
	 */
	public function checkJbrOrderNo(){
		if(empty($this->data['DemandInfo']['jbr_order_no'])){

			$jbr = $this->checkJbrSite($this->data['DemandInfo']['site_id']);

			if($jbr){
				return false;
			}
		}
		return true;
	}

	/**
	 * [JBR様]作業内容
	 *
	 * サイト名がJBR生活救急車 の場合、JBR情報未入力でエラー
	 *
	 * @return boolean
	 */
	public function checkJbrWorkContents(){
		if(empty($this->data['DemandInfo']['jbr_work_contents'])){

			$jbr = $this->checkJbrSite($this->data['DemandInfo']['site_id']);

			if($jbr){
				return false;
			}
		}

		return true;
	}

	/**
	 * [JBR様]カテゴリ
	 *
	 * サイト名がJBR生活救急車 の場合、JBR情報未入力でエラー
	 *
	 * @return boolean
	 */
	public function checkJbrCategory2(){
		if(empty($this->data['DemandInfo']['jbr_category'])){

			$jbr = $this->checkJbrSite($this->data['DemandInfo']['site_id']);

			if($jbr){
				return false;
			}
		}

		return true;
	}


	function beforeValidate($options){

		if (!empty($this->data['DemandInfo']['jbr_estimate']['name'])){

			$this->validate['jbr_estimate'] = array(
					'InvalidExtension' => array(
							'rule' => array('extension', array('pdf', 'jpg', 'jpeg', 'png', 'gif', 'bmp')),
							'last' => true
					),
			);
		} else {
			$this->data['DemandInfo']['jbr_estimate'] = null;
		}

		if (!empty($this->data['DemandInfo']['jbr_receipt']['name'])){

			$this->validate['jbr_receipt'] = array(
					'InvalidExtension' => array(
							'rule' => array('extension', array('pdf', 'jpg', 'jpeg', 'png', 'gif', 'bmp')),
							'last' => true
					),
			);
		} else {
			$this->data['DemandInfo']['jbr_receipt'] = null;
		}

		parent::beforeValidate($options);

	}

	/**
	 * JBRサイト(生活救急車)かどうか判定する。
	 * @param unknown $site_id
	 * @return boolean
	 */
	function checkJbrSite($site_id) {
		$rslt = false;

		if (empty($site_id)) {
			return $rslt;
		}

		App::import('Model', 'MSite');
		$MSite = new MSite();

		$site = $MSite->findById($site_id);

		if ($site['MSite']['jbr_flg'] == 1) {
			$rslt = true;
		}

		return $rslt;
	}

	public $csvFormat = array(
			'default' => array(
					'DemandInfo.id' => '案件ID',
					'DemandInfo.follow_date' => '後追い日',
					'DemandInfo.demand_status' => '案件状況',
					'DemandInfo.order_fail_reason' => '失注理由',
					'DemandInfo.mail_demand' => 'メール案件',
					'DemandInfo.nighttime_takeover' => '夜間引継案件',
					'DemandInfo.low_accuracy' => '低確度案件',
					'DemandInfo.remand' => '差し戻し案件',
					'DemandInfo.immediately' => '至急案件',
					'DemandInfo.corp_change' => '加盟店変更希望',
					'DemandInfo.receive_datetime' => '受信日時',
					'DemandInfo.site_name' => 'サイト名',
					'DemandInfo.genre_name' => 'ジャンル名',
					'DemandInfo.category_name' => 'カテゴリ名',
					'DemandInfo.cross_sell_source_site' => '【クロスセル】元サイト',
					// 2015.4.7 n.kai ADD start
					// 詳細画面上は「【クロスセル】元カテゴリ」と差し替えているが、過去データに
					// 「【クロスセル】元カテゴリ」があるため、追加とする。
					'DemandInfo.cross_sell_source_genre' => '【クロスセル】元ジャンル',
					// 2015.4.7 n.kai ADD end
					'DemandInfo.cross_sell_source_category' => '【クロスセル】元カテゴリ',
					// 2015.6.15 n.kai ADD start　ORANGE-608
					'DemandInfo.source_demand_id' => '元案件番号',
					// 2015.6.15 n.kai ADD end
					// 2015.07.14 h.hanaki ADD start　ORANGE-583
					'DemandInfo.same_customer_demand_url' => '同一顧客案件ＵＲＬ',
					// 2015.07.14 h.hanaki ADD end
					'DemandInfo.receptionist' => '受付者',
					'DemandInfo.customer_name' => 'お客様名',
					// ORANGE-36 S
					'DemandInfo.customer_corp_name' => '法人名',
					// ORANGE-36 E
					'DemandInfo.customer_tel' => 'お客様電話番号',
					'DemandInfo.customer_mailaddress' => 'お客様メールアドレス',
					'DemandInfo.postcode' => '郵便番号',
					'DemandInfo.address1' => '都道府県',
					'DemandInfo.address2' => '市区町村',
					'DemandInfo.address3' => 'それ以降の住所',
					'DemandInfo.tel1' => '連絡先①',
					'DemandInfo.tel2' => '連絡先②',
					'DemandInfo.contents' => 'ご相談内容',
					'DemandInfo.contact_desired_time' => '連絡希望日時',
					'DemandInfo.selection_system' => '選定方式',
					// 2015.07.24 s.harada ADD start ORANGE-659
					'DemandInfo.pet_tombstone_demand' => 'ペット墓石の案内',
					'DemandInfo.sms_demand' => 'SMS案内',
					'DemandInfo.order_no_marriage' => '注文番号※婚活のみ',
					// 2015.07.24 s.harada ADD end ORANGE-659
					'DemandInfo.jbr_order_no' => '[JBR様]受付No',
					'DemandInfo.jbr_work_contents' => '[JBR様]作業内容',
					'DemandInfo.jbr_category' => '[JBR様]カテゴリ',
					'DemandInfo.mail' => 'メール本文',
					'DemandInfo.order_date' => '受注日',
					'DemandInfo.complete_date' => '施工完了日',
					'DemandInfo.order_fail_date' => '失注日',
					'DemandInfo.jbr_estimate_status' => '[JBR様]見積書状況',
					'DemandInfo.jbr_receipt_status' => '[JBR様]領収書状況',
					// 2016.01.19 n.kai ADD start ORANGE-1222
					'DemandInfo.acceptance_status' => '受付ステータス',
					// 2016.01.19 n.kai ADD end ORANGE-1222
					// 2016.10.27 murata.s ORANGE-216 ADD(S)
					'DemandInfo.nitoryu_flg' => '二刀流',
					// 2016.10.27 murata.s ORANGE-216 ADD(E)
					'CommissionInfo.id' => '取次ID',
					// 2015.04.30 h.hara(S)
					'CommissionInfo.corp_id' => '取次先ID',
					// 2015.04.30 h.hara(E)
					'MCorp.corp_name' => '取次先企業名',
					// 2015.04.30 h.hara(S)
					'MCorp.official_corp_name' => '取次先正式企業名',
					// 2015.04.30 h.hara(E)
					'CommissionInfo.commit_flg' => '確定フラグ',
					'CommissionInfo.commission_type' => '取次種別',
					'CommissionInfo.appointers' => '選定者',
					'CommissionInfo.first_commission' => '初取次チェック',
					'CommissionInfo.corp_fee' => '取次先手数料',
					'CommissionInfo.attention' => '注意事項',
					'CommissionInfo.commission_dial' => '取次用ダイヤル',
					'CommissionInfo.tel_commission_datetime' => '電話取次日時',
					'CommissionInfo.tel_commission_person' => '電話取次者',
					'CommissionInfo.commission_fee_rate' => '取次時手数料率',
					'CommissionInfo.commission_note_send_datetime' => '取次票送信日時',
					'CommissionInfo.commission_note_sender' => '取次票送信者',
					// 2015.07.28 s.harada ADD start ORANGE-680
					'CommissionInfo.send_mail_fax' => 'メール/FAX送信',
					'CommissionInfo.send_mail_fax_datetime' => 'メール/FAX送信日時',
					// 2015.07.28 s.harada ADD end ORANGE-680
					'CommissionInfo.commission_status' => '取次状況',
					'CommissionInfo.commission_order_fail_reason' => '取次失注理由',
					'CommissionInfo.complete_date' => '施工完了日',
					'CommissionInfo.order_fail_date' => '失注日',
					'CommissionInfo.estimate_price_tax_exclude' => '見積金額(税抜)',
					'CommissionInfo.construction_price_tax_exclude' => '施工金額(税抜)',
					'CommissionInfo.construction_price_tax_include' => '施工金額(税込)',
					'CommissionInfo.deduction_tax_include' => '控除金額(税込)',
					'CommissionInfo.deduction_tax_exclude' => '控除金額(税抜)',
					'CommissionInfo.confirmd_fee_rate' => '確定手数料率',
					'CommissionInfo.unit_price_calc_exclude' => '取次単価対象外',
					'CommissionInfo.report_note' => '報告備考欄',
			),
			'auction_flowing' => array(
					'DemandInfo.prefecture_name' => '都道府県',
					'DemandInfo.year_count' => '累計件数',
					'DemandInfo.year_flowing_ratio' => '累計率',
					'DemandInfo.month_count' => '件数',
					'DemandInfo.month_flowing_ratio' => '率',
			),
	);
	// 2015.06.09 h.hara ORANGE-589(S)
	// 日付形式チェック
	public function checkDateFormat() {
		$date_part = explode('/', $this->data ['DemandInfo'] ['order_date']);
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

	// 2015.08.01 s.harada ADD start ORANGE-756
	/**
	 * リザベ案件 必須チェック　※サイト名が「000_生活救急車」の時のみチェック解除
	 *
	 * @return boolean
	 */
	public function checkReservationDemandNotEmpty() {
		if ($this->data['DemandInfo']['site_id'] != 585) {
			if ($this->data['DemandInfo']['reservation_demand'] == null) {
				// 未選択の場合必須チェックエラー
				return false;
			}
		}
		return true;
	}
	// 2015.08.01 s.harada ADD end ORANGE-756

	// 2015.07.24 s.harada ADD start ORANGE-659
	/**
	 * ペット墓石の案内 必須チェック　※ジャンルが「ペット葬儀」の時のみチェック
	 *
	 * @return boolean
	 */
	public function checkPetTombstoneDemandNotEmpty() {
		if ($this->data['DemandInfo']['genre_id'] == 509) {
			if ($this->data['DemandInfo']['pet_tombstone_demand']  == null) {
				// 未選択の場合必須チェックエラー
				return false;
			}
		}
		return true;
	}

	/**
	 * SMS案内 必須チェック　※案件状況が【未選定】又は【取次前】加盟店確認中 又は【進行中】電話取次済 又は【進行中】情報送信済 の時のみチェック
	 *
	 * @return boolean
	 */
	public function checkSmsDemandNotEmpty() {
		// ORANGE-756のコメントの対応のため条件解除
		return true;

		if ($this->data['DemandInfo']['demand_status'] == 1 || $this->data['DemandInfo']['demand_status'] == 3 || $this->data['DemandInfo']['demand_status'] == 4 || $this->data['DemandInfo']['demand_status'] == 5) {
			if ($this->data['DemandInfo']['sms_demand']  == null) {
				// 未選択の場合必須チェックエラー
				return false;
			}
		}
		return true;
	}

	/**
	 * 注文番号※婚活のみ 必須チェック　※ジャンルが「婚活」時のみチェック
	 *
	 * @return boolean
	 */
	public function checkOrderNoMarriageNotEmpty() {
		// murata.s ORANGE-448 CHG(S)
		$check_sites = array(477, 492, 906, 917, 460, 494, 727, 743, 758, 760, 907, 919);
		if ($this->data['DemandInfo']['genre_id'] == 620 // 婚活
				|| in_array($this->data['DemandInfo']['site_id'], $check_sites)) {
			if ($this->data['DemandInfo']['order_no_marriage']  == null) {
				// 未選択の場合必須チェックエラー
				return false;
			}
		}
		// murata.s ORANGE-448 CHG(E)
		return true;
	}
	// 2015.07.24 s.harada ADD end ORANGE-659

	// ORANGE-998 2015.11.14
	/**
	 * すでに連絡期限日時が登録されている場合は、連絡期限日時の削除は許可しない
	 *
	 * @return boolean
	 */
	public function checkDoChangeDemandType() {
		if (!isset($this->data['DemandInfo']['id'])) return true;
		//初回登録時はOK
		if (strlen($this->data['DemandInfo']['id']) == 0) return true;
		//contact_desired_timeが設定されていない場合はOK
		if (strlen($this->data['DemandInfo']['contact_desired_time']) == 0) return true;
		//同時にVisitTimeもリクエストに含まれていなければチェックを行わない
		if (!isset($this->data['VisitTime'])) return true;


		//VisitTimeとのクロスチェック
		App::import('Model', 'VisitTime');
		$VisitModel = new VisitTime();
		//contact_desired_timeが設定されている時に同一案件番号の訪問日時が設定されている場合はエラー
		$v_time = $VisitModel->find('all', array('conditions' => array('demand_id = ' . $this->data['DemandInfo']['id'])));
		if (count($v_time) > 0) return false;

		return true;
	}

	// 2015.12.09 h.hanaki ORANGE-1045 【案件管理】入力規制エラー 追加仕様「「確定」が入った場合は、案件状況が【進行中】か【失注】のみ。それ以外はエラーとする」
	/**
	 * 確定している場合の案件状況のチェック
	 *
	 * @return boolean
	 */
	public function checkDemandStatusConfirm(){

		$commission_flg_count = 0;
		// 2016.05.19 murata.s CHG(S) ComissionInfoが取得されていない場合は処理しない
		if(!empty($this->data['CommissionInfo'])) {
			foreach ($this->data['CommissionInfo'] as $commisionData) {
				if ($commisionData['corp_id'] && isset($commisionData['commit_flg']) && $commisionData['commit_flg']) {
					$commission_flg_count = $commission_flg_count + 1;
				}
			}
		}

		//ORANGE-291 ADD S
		if(isset($this->data["DemandInfo"]['quick_order_fail']) &&  $this->data["DemandInfo"]["quick_order_fail_reason"] != ""){
			return true;
		}
		//ORANGE-291 ADD E

		// 2016.05.19 murata.s CHG(S) ComissionInfoが取得されていない場合は処理しない
		if ($commission_flg_count == 0) {
			// 確定が0の場合正常(判定しない)
			return true;
		} else {
			// 確定が1以上の場合案件状況を判定
			switch ($this->data['DemandInfo']['demand_status']) {
			case 4:
			case 5:
			case 6:
				return true;
				break;
			default:
				return false;
				break;
			}
		}
	}

    //  2017.09.14 m-kawamoto CHG(S) ORANGE-444
    /**
     * 業者取次用一覧
     *
     */
    public function getCorpCommissionPaginationCondition($queryfilter = array())
    {
    //  2017.09.14 m-kawamoto CHG(E) ORANGE-444
// murata.s ORANGE-414 CHG(S)
	 	$joins = array(
	 			array(
	 					'table' => 'm_sites',
	 					'alias' => "MSite",
	 					'type' => 'inner',
	 					'conditions' => array('DemandInfo.site_id = MSite.id')
	 			),
				array(
						//ORANGE-15 CHG S 紹介不可フラグ/OFF 追加
						'table' => '(select min(id) as id, demand_id from commission_infos where lost_flg = 0 and del_flg = 0 and introduction_not = 0 group by demand_id)',
						//ONRAGE-15 CHG E
						'alias' => 'DemandCommissionInfo',
						'type' => 'left',
						'conditions' => array('DemandInfo.id = DemandCommissionInfo.demand_id')
				),
	 			array(
	 					'table' => 'commission_infos',
	 					'alias' => 'CommissionInfo',
	 					'type' => 'left',
	 					'conditions' => array('DemandCommissionInfo.id = CommissionInfo.id')
	 			),
				array(
						'table' => 'm_corps',
						'alias' => "MCorp",
						'type' => 'left',
						'conditions' => array('CommissionInfo.corp_id = MCorp.id')
				),
				array(
						'table' => 'affiliation_infos',
						'alias' => 'AffiliationInfo',
						'type' => 'left',
						'conditions' => array('AffiliationInfo.corp_id = MCorp.id')
				),
				array(
						'table' => 'm_users',
						'alias' => "MUser",
						'type' => 'left',
						'conditions' => array('CommissionInfo.modified_user_id = MUser.user_id')
				),
				array(
						'table' => 'm_genres',
						'alias' => "MGenre",
						'type' => 'left',
						'conditions' => array('DemandInfo.genre_id = MGenre.id', 'MGenre.valid_flg=1')
				),
				array(
						'table' => 'm_address1',
						'alias' => 'MAddress',
						'type' => 'left',
						'conditions' => array("lpad(DemandInfo.address1, 2, '0') = MAddress.address1_cd")
				),
				array(
						'table' => 'm_users',
						'alias' => "LockUser",
						'type' => 'left',
						'conditions' => array('DemandInfo.lock_user_id = LockUser.id')
				),
				//  2015.09.21 h.hanaki ADD(S) ORANGE_AUCTION-13 訪問日時SORT用
				//  2015.12.28 ohta ORANGE-1027 visit_time変更による修正
				// visit_time_viewの最小値をソート用にjoin
				// 但しパフォーマンスが良くないので要調査と対応
				// 2015.12.29 ohta ORANGE-1027 commission_infos.commission_visit_time_idが入るので不要
// 				array(
// 						'table' => '(select demand_id,visit_time from (select min(visit_time) as visit_time, demand_id from visit_time_view group by demand_id) as visit_min)',
// 						'alias' => 'VisitTimeMin',
// 						'type' => 'left',
// 						'conditions' => array(
// 								"DemandInfo.id = VisitTimeMin.demand_id"
// 						),
// 				),
		);
// murata.s ORANGE-414 CHG(E)
        // 2015.12.28 ohta ORANGE-1027 visit_time変更による修正
		// 訪問日時のViewをbind
		$this->bindModel(
			array(
				'hasOne' => array(
					'VisitTime' => array(
						'className' => 'VisitTimeView',
						'foreignKey' => 'demand_id',
					)
				)
			)
		);

		$conditions = array();

        // 生活救急車以外
        $conditions['MSite.jbr_flg'] = 0;

        // 「お客様不在」または「【取次前】加盟店確認中」
        //  2015.12.13 h.hanaki ADD(S) ORANGE-939
        //$conditions['or'] = array(array('DemandInfo.demand_status' => Util::getDivValue('demand_status', 'no_guest')), array('DemandInfo.demand_status' => Util::getDivValue('demand_status', 'agency_before')), array('DemandInfo.demand_status' => Util::getDivValue('demand_status', 'demand_development')), array('DemandInfo.demand_status' => Util::getDivValue('demand_status', 'need_hearing')));
        // murata.s ORANGE-506 CHG(S)
        //  2017.09.14 m-kawamoto CHG(S) ORANGE-444
        $conditions[0]['or'][0]['DemandInfo.demand_status'] = Util::getDivValue('demand_status', 'demand_development');
        $conditions[0]['or'][1]['DemandInfo.demand_status'] = Util::getDivValue('demand_status', 'need_hearing');
        $conditions[0]['or'][2][0]['or'][0]['DemandInfo.demand_status'] = Util::getDivValue('demand_status', 'agency_before');
        $conditions[0]['or'][2][0]['or'][0]['DemandInfo.selection_system not in'] = array('2', '3');
        $conditions[0]['or'][2][0]['or'][1]['DemandInfo.demand_status'] = Util::getDivValue('demand_status', 'agency_before');
        $conditions[0]['or'][2][0]['or'][1]['DemandInfo.selection_system'] = '';
        //  2017.09.14 m-kawamoto CHG(E) ORANGE-444
        // murata.s ORANGE-506 CHG(E)
        //  2015.12.13 h.hanaki ADD(E) ORANGE-939

        //  2017.06.27 inokuma ADD(S) ORANGE-444
        //フィルター設定に必要なため関数に格納
        $holidayQuery = "SELECT mi.item_name FROM m_corp_subs mcs "
                . "INNER JOIN m_items mi ON mi.item_category = mcs.item_category AND mi.item_id = mcs.item_id "
                . "WHERE mcs.item_category = '休業日' AND mcs.corp_id = MCorp.id";
        
        if(isset($queryfilter['value'])){
            foreach($queryfilter['value'] as $key => $value){
                $exclusion = '';
                //フィルターを掛ける要素によって条件を変える
                //後追い日
                if($queryfilter['filter_name'][$key] == 'DemandInfo.follow_date'){
                    //後追い日あり
                    if($value == 2){
                        $exclusion = '<>';
                        $value = '';
                    }else{
                        //後追い日なし
                        $value = '';
                        $conditions[51]['or'][0][$queryfilter['filter_name'][$key]] = $value;
                        $conditions[51]['or'][1][$queryfilter['filter_name'][$key]] = null;
                        continue;
                    }
                }
                //連絡希望日時
                if($queryfilter['filter_name'][$key] == 'DemandInfo.contact_desired_time'){
                    if($value == 1) {
                        //当日
                        $valueBefore = date('Y/m/d 00:00:00');
                        $valueAfter = date('Y/m/d 23:59:59');
                    }elseif($value == 2){
                        //翌日
                        $valueBefore = date('Y/m/d 00:00:00', strtotime("+ 1 day"));
                        $valueAfter = date('Y/m/d 23:59:59', strtotime("+ 1 day"));
                    }else{
                        //それ以降
                        $value = date('Y/m/d 00:00:00', strtotime("+ 2 day"));
                        $exclusion = '>=';
                    }
                    
                    if(empty($exclusion)){
                        $conditions[52]['or'][0][0][$queryfilter['filter_name'][$key].' >='] = $valueBefore;
                        $conditions[52]['or'][0][1][$queryfilter['filter_name'][$key].' <='] = $valueAfter;
                        $conditions[52]['or'][1][0]['DemandInfo.contact_desired_time_from >='] = $valueBefore;
                        $conditions[52]['or'][1][1]['DemandInfo.contact_desired_time_from <='] = $valueAfter;
                    }else{
                        $conditions[52]['or'][0][$queryfilter['filter_name'][$key].' '.$exclusion] = $value;
                        $conditions[52]['or'][1]['DemandInfo.contact_desired_time_from '.$exclusion ] = $value;
                    }
                    continue;
                }
                
                //営業日
                if($queryfilter['filter_name'][$key] == 'DemandInfo.holiday'){
                    if(is_array($value) && count($value) === 1){
                        $value = implode(',',$value);
                        $conditions[6] = 'NOT EXISTS('.$holidayQuery.' AND mcs.item_id = '.$value.')';
                        //$conditions = Hash::merge($conditions,array(
                        //    'NOT EXISTS('.$holidayQuery.' AND mcs.item_id = '.$value.')'
                        //));
                    }else{
                        $value = implode(',',$value);
                        $conditions[6] = 'NOT EXISTS('.$holidayQuery.' AND mcs.item_id IN ('.$value.'))';
                    }
                    continue;
                }
                
                //履歴更新時間
                if($queryfilter['filter_name'][$key] == 'CommissionInfo.modified'){
                    if($value == 1){
                        //1時間以内
                        //システム日付の条件を先に追加しておく
                        $conditions[$queryfilter['filter_name'][$key].' <='] = date('Y/m/d H:i:s');
                        $value = date('Y/m/d H:i:s', strtotime("- 1 hours"));
                        $exclusion = '>=';
                    }else{
                        //1時間以降
                        $value = date('Y/m/d H:i:s', strtotime("- 1 hours"));
                        $exclusion = '<=';
                    }
                }
                
                //フリーワード検索時
                if($queryfilter['filter_name'][$key] == 'DemandInfo.user_name'
                        || $queryfilter['filter_name'][$key] == 'MCorp.corp_name'
                        || $queryfilter['filter_name'][$key] == 'MSite.site_name'
                        || $queryfilter['filter_name'][$key] == 'MUser.user_name'){
                    $exclusion = 'LIKE';
                    $value = '%'.$value.'%';
                }
                
                //初取次チェック
                if($queryfilter['filter_name'][$key] == 'CommissionInfo.first_commission'){
                    if($value == 1) {
                        //なし
                        $conditions[3]['or'][0][$queryfilter['filter_name'][$key]] = '0';
                        $conditions[3]['or'][1][$queryfilter['filter_name'][$key]] = null;
                        continue;
                    }else{
                        $value = '1';
                    }
                }
                
                //入札落ち
                if($queryfilter['filter_name'][$key] == 'DemandInfo.auction'){
                    if($value == 1) {
                        $conditions[4]['or'][0][$queryfilter['filter_name'][$key]] = '0';
                        $conditions[4]['or'][1][$queryfilter['filter_name'][$key]] = null;
                        continue;
                    }else{
                        $value = '1';
                    }
                }
                
                //クロスセル獲得 
                if($queryfilter['filter_name'][$key] == 'DemandInfo.cross_sell_implement'){
                    if($value == 1) {
                        $conditions[5]['or'][0][$queryfilter['filter_name'][$key]] = '0';
                        $conditions[5]['or'][1][$queryfilter['filter_name'][$key]] = null;
                        continue;
                    }else{
                        $value = '1';
                    }
                }
                
                if(empty($exclusion)){
                    if(is_array($value) && count($value) === 1){
                        $conditions[$queryfilter['filter_name'][$key]] = $value[0];
                    }else{
                        $conditions[$queryfilter['filter_name'][$key]] = $value;
                    }
                }else{
                    $conditions[$queryfilter['filter_name'][$key].' '.$exclusion] = $value;
                }
                
            }
        }
        //  2017.06.27 inokuma ADD(E) ORANGE-444

        //  2015.09.21 h.hanaki ADD(S) ORANGE_AUCTION-13 訪問日時を取得するため
        $fields = 'DemandInfo.*, VisitTime.*, MCorp.id, MCorp.corp_name, MCorp.official_corp_name, MCorp.commission_dial, MSite.site_name, MUser.user_name, MGenre.commission_rank, MAddress.address1, CommissionInfo.first_commission, AffiliationInfo.commission_count, CommissionInfo.modified, LockUser.user_name';
        //  2015.12.28 ohta ORANGE-1027 visit_time変更による修正 下部をコメントアウト
        // $fields .= ', (SELECT ARRAY_TO_STRING(ARRAY( SELECT visit_time FROM visit_times WHERE visit_times.demand_id = "DemandInfo"."id" ORDER BY visit_times.visit_time ASC ),\'｜\')) as "visit_time" ';

        $this->virtualFields = array(
            // 2015.5.28 m.katsuki ADD start ＊取次先1追加
            'corp_name' => 'MCorp.corp_name',
            // 2015.5.28 m.katsuki ADD end
            'official_corp_name' => 'MCorp.official_corp_name',
            'commission_dial' => 'MCorp.commission_dial',
            'commission_rank' => 'MGenre.commission_rank',
            // 2017.7.4 inokuma ORANGE-444 ADD start
            'user_name' => "CASE WHEN DemandInfo.modified_user_id = 'AutomaticAuction' THEN '自動選定' ELSE MUser.user_name END",
            // 2017.7.4 inokuma ORANGE-444 ADD stop
            'first_commission' => 'CommissionInfo.first_commission',
            'commission_count' => 'AffiliationInfo.commission_count',
            'modified2' => 'CommissionInfo.modified',
            //  2015.09.21 h.hanaki ADD     ORANGE_AUCTION-13
            //  2015.12.28 ohta ORANGE-1027
            'visit_time' => 'VisitTime.visit_time',
            'lock_user_name' => 'LockUser.user_name',
        	// ORANGE-215 ADD S
        	'contactable' => "CASE MCorp.contactable_support24hour WHEN 1 THEN '24H対応' ELSE MCorp.contactable_time_from || '～' || MCorp.contactable_time_to END",
        	'holiday' => "(ARRAY_TO_STRING(ARRAY(".$holidayQuery."), ','))",
        	// ORANGE-215 ADD E
        	//ORANGE-300 CHG S
        	//NULL,空文字を後ろに持っていくため、アルファベットに変換
        	'demand_follow_date' => "(case when DemandInfo.follow_date is null then 'Z' when DemandInfo.follow_date = '' then 'Y' else DemandInfo.follow_date end)",
        	//ORANGE-300 CHG E
        );
        $this->setVirtualDetectContactDesiredTime(true);
	$this->setVirtualOverLimit();

        //  2017.09.14 m-kawamoto CHG(S) ORANGE-444
        $query = array();
        //  2017.09.14 m-kawamoto CHG(E) ORANGE-444
        //  2015.09.21 h.hanaki ADD(E) ORANGE_AUCTION-13
        $query = Hash::merge($query, array(
            //  2015.09.21 h.hanaki CHG(S) ORANGE_AUCTION-13 訪問日時を取得するため
            'fields' => $fields,
            //  2015.09.21 h.hanaki CHF(E) ORANGE_AUCTION-13
            'conditions' => $conditions,
            'joins' => $joins,
            'limit' => Configure::read('report_list_limit'),
            // 2015.12.19 デフォルトは訪問日時の昇順かつ入札流れ案件から出力
            'order' => array(
                'visit_time' => 'asc',
                'detect_contact_desired_time' => 'asc',
                'auction' => 'desc NULLS LAST',
            )
        ));

        return $query;
    }

    /**
     * JBR取次用一覧
     *
     */
//ORANGE-309 CHG A CorpCommissionPaginationConditionを流用
    public function getJBRCommissionPaginationCondition($query = array())
    {
// murata.s ORANGE-414 CHG(S)
		$joins = array(
				// murata.s ORANGE-447 CHG(S)
				array(
						'table' => 'm_sites',
						'alias' => "MSite",
						'type' => 'inner',
						'conditions' => array(
								'DemandInfo.site_id = MSite.id',
								'or' => array(
										'MSite.jbr_flg' => 1,
										'MSite.id' => 1314	// JBRリピート案件
								)
						)
				),
				// murata.s ORANGE-447 CHG(E)
				array(
						//ORANGE-15 CHG S 紹介不可フラグ/OFF 追加
						'table' => '(select min(id) as id, demand_id from commission_infos where lost_flg = 0 and del_flg = 0 and introduction_not = 0 group by demand_id)',
						//ONRAGE-15 CHG E
						'alias' => 'DemandCommissionInfo',
						'type' => 'left',
						'conditions' => array('DemandInfo.id = DemandCommissionInfo.demand_id')
				),
	 			array(
	 					'table' => 'commission_infos',
	 					'alias' => 'CommissionInfo',
	 					'type' => 'left',
	 					'conditions' => array('DemandCommissionInfo.id = CommissionInfo.id')
	 			),
				array(
						'table' => 'm_corps',
						'alias' => "MCorp",
						'type' => 'left',
						'conditions' => array('CommissionInfo.corp_id = MCorp.id')
				),
				array(
						'table' => 'affiliation_infos',
						'alias' => 'AffiliationInfo',
						'type' => 'left',
						'conditions' => array('AffiliationInfo.corp_id = MCorp.id')
				),
				array(
						'table' => 'm_users',
						'alias' => "MUser",
						'type' => 'left',
						'conditions' => array('CommissionInfo.modified_user_id = MUser.user_id')
				),
				array(
						'table' => 'm_genres',
						'alias' => "MGenre",
						'type' => 'left',
						'conditions' => array('DemandInfo.genre_id = MGenre.id', 'MGenre.valid_flg=1')
				),
				array(
						'table' => 'm_address1',
						'alias' => 'MAddress',
						'type' => 'left',
						'conditions' => array("lpad(DemandInfo.address1, 2, '0') = MAddress.address1_cd")
				),
				array(
						'table' => 'm_users',
						'alias' => "LockUser",
						'type' => 'left',
						'conditions' => array('DemandInfo.lock_user_id = LockUser.id')
				),
		);
// murata.s ORANGE-414 CHG(E)
    	// 2015.12.28 ohta ORANGE-1027 visit_time変更による修正
    	// 訪問日時のViewをbind
    	$this->bindModel(
    			array(
    					'hasOne' => array(
    							'VisitTime' => array(
    									'className' => 'VisitTimeView',
    									'foreignKey' => 'demand_id',
    							)
    					)
    			)
    			);

    	$conditions = array();

    	// 「未選定」、「お客様不在」、「【取次前】加盟店確認中」、「【進行中】電話取次済」
		$conditions['or'] = array(array('DemandInfo.demand_status' => Util::getDivValue('demand_status', 'no_selection')),
				array('DemandInfo.demand_status' => Util::getDivValue('demand_status', 'no_guest')),
				array('DemandInfo.demand_status' => Util::getDivValue('demand_status', 'agency_before')),
				array('DemandInfo.demand_status' => Util::getDivValue('demand_status', 'telephone_already')));

    	$conditions[] = array(
				array('DemandInfo.selection_system !=' => Util::getDivValue('selection_type', 'AuctionSelection')),
				array('DemandInfo.selection_system !=' => Util::getDivValue('selection_type', 'AutomaticAuctionSelection'))
    	);


    	//  2015.09.21 h.hanaki ADD(S) ORANGE_AUCTION-13 訪問日時を取得するため
    	$fields = 'DemandInfo.*, VisitTime.*, MCorp.id, MCorp.corp_name, MCorp.official_corp_name, MCorp.commission_dial, MSite.site_name, MUser.user_name, MGenre.commission_rank, MAddress.address1, CommissionInfo.first_commission, AffiliationInfo.commission_count, CommissionInfo.modified, LockUser.user_name';
    	//  2015.12.28 ohta ORANGE-1027 visit_time変更による修正 下部をコメントアウト
    	// $fields .= ', (SELECT ARRAY_TO_STRING(ARRAY( SELECT visit_time FROM visit_times WHERE visit_times.demand_id = "DemandInfo"."id" ORDER BY visit_times.visit_time ASC ),\'｜\')) as "visit_time" ';

    	$this->virtualFields = array(
    			// 2015.5.28 m.katsuki ADD start ＊取次先1追加
    			'corp_name' => 'MCorp.corp_name',
    			// 2015.5.28 m.katsuki ADD end
    			'official_corp_name' => 'MCorp.official_corp_name',
    			'commission_dial' => 'MCorp.commission_dial',
    			'commission_rank' => 'MGenre.commission_rank',
    			'user_name' => 'MUser.user_name',
    			'first_commission' => 'CommissionInfo.first_commission',
    			'commission_count' => 'AffiliationInfo.commission_count',
    			'modified2' => 'CommissionInfo.modified',
    			//  2015.09.21 h.hanaki ADD     ORANGE_AUCTION-13
    			//  2015.12.28 ohta ORANGE-1027
    			'visit_time' => 'VisitTime.visit_time',
    			'lock_user_name' => 'LockUser.user_name',
    			// ORANGE-215 ADD S
    			'contactable' => "CASE MCorp.contactable_support24hour WHEN 1 THEN '24H対応' ELSE MCorp.contactable_time_from || '～' || MCorp.contactable_time_to END",
    			'holiday' => "(ARRAY_TO_STRING(ARRAY(SELECT mi.item_name FROM m_corp_subs mcs INNER JOIN m_items mi ON mi.item_category = mcs.item_category AND mi.item_id = mcs.item_id WHERE mcs.item_category = '休業日' AND mcs.corp_id = MCorp.id), ','))",
    			// ORANGE-215 ADD E
    			//ORANGE-300 CHG S
    			//NULL,空文字を後ろに持っていくため、アルファベットに変換
    			'demand_follow_date' => "(case when DemandInfo.follow_date is null then 'Z' when DemandInfo.follow_date = '' then 'Y' else DemandInfo.follow_date end)",
    			//ORANGE-300 CHG E
    	);
    	$this->setVirtualDetectContactDesiredTime(true);
    	$this->setVirtualOverLimit();

    	//  2015.09.21 h.hanaki ADD(E) ORANGE_AUCTION-13
    	$query = Hash::merge($query, array(
    			//  2015.09.21 h.hanaki CHG(S) ORANGE_AUCTION-13 訪問日時を取得するため
    			'fields' => $fields,
    			//  2015.09.21 h.hanaki CHF(E) ORANGE_AUCTION-13
    			'conditions' => $conditions,
    			'joins' => $joins,
    			'limit' => Configure::read('report_list_limit'),
    			// 2015.12.19 デフォルトは訪問日時の昇順かつ入札流れ案件から出力
    			'order' => array(
    					'visit_time' => 'asc',
    					'detect_contact_desired_time' => 'asc',
    					'auction' => 'desc NULLS LAST',
    			)
    	));

    	return $query;
    }
//ORANGE-309 CHG E

    /**
     * 業者選定用一覧
     *
     */
    public function getCorpSelectionPaginationCondition($query = array())
    {
        $joins = array(
            array(
                'table' => 'm_sites',
                'alias' => "MSite",
                'type' => 'left',
                'conditions' => array('DemandInfo.site_id = MSite.id')
            ),
            array(
                'table' => 'm_categories',
                'alias' => "MCategory",
                'type' => 'left',
                'conditions' => array('DemandInfo.category_id = MCategory.id')
            ),
            //  2015.09.21 h.hanaki ADD(S) ORANGE_AUCTION-13 訪問日時SORT用
            array(
                'table' => 'm_genres',
                'alias' => "MGenre",
                'type' => 'left',
                'conditions' => array('DemandInfo.genre_id = MGenre.id', 'MGenre.valid_flg=1')
            ),
            //  2015.12.28 ohta ORANGE-1027 visit_time変更による修正
            // visit_time_viewの最小値をソート用にjoin
            // 但しパフォーマンスが良くないので要調査と対応
            // 2015.12.29 ohta ORANGE-1027 commission_infos.commission_visit_time_idが入るので不要
//            array(
//                'table' => '(select demand_id,visit_time from (select min(visit_time) as visit_time, demand_id from visit_time_view group by demand_id) as visit_min)',
//                'alias' => 'VisitTimeMin',
//                'type' => 'left',
//                'conditions' => array(
//                    "DemandInfo.id = VisitTimeMin.demand_id"
//                ),
//            ),
        );
        // 2015.12.28 ohta ORANGE-1027 visit_time変更による修正
		// 訪問日時のViewをbind
		$this->bindModel(
			array(
				'hasOne' => array(
					'VisitTime' => array(
						'className' => 'VisitTimeViewSort',
						'foreignKey' => 'demand_id',
					)
				)
			)
		);

        $conditions = array();

        // 生活救急車以外
        $conditions['MSite.jbr_flg'] = 0;

        // 「【未選定】」
        $conditions['DemandInfo.demand_status'] = Util::getDivValue('demand_status', 'no_selection');

        //  2015.09.21 h.hanaki ADD(S) ORANGE_AUCTION-13
        // 「オークション落ち」
        //$conditions['coalesce(DemandInfo.auction, 0) ='] = 0;
        //  2015.09.21 h.hanaki ADD(S) ORANGE_AUCTION-13 訪問日時を取得するため
        $fields = 'DemandInfo.*, VisitTime.*, MSite.site_name, MCategory.category_name, MGenre.commission_rank';
        //  2015.12.28 ohta ORANGE-1027 visit_time変更による修正 下部をコメントアウト
        //$fields .= ', (SELECT ARRAY_TO_STRING(ARRAY( SELECT visit_time FROM visit_times WHERE visit_times.demand_id = "DemandInfo"."id" ORDER BY visit_times.visit_time ASC ),\'｜\')) as "visit_time" ';

        $query = Hash::merge($query, array(
//				'fields' => 'DemandInfo.*, MSite.site_name, MCategory.category_name'
            'fields' => $fields,
            'conditions' => $conditions,
            'joins' => $joins,
            'limit' => Configure::read('report_list_limit'),
            // 2015.12.19 デフォルトは訪問日時の昇順かつ入札流れ案件から出力
            'order' => array(
                'visit_time' => 'asc',
                'auction' => 'desc NULLS LAST',
            )
        ));

		//  2015.09.21 h.hanaki ADD(S) ORANGE_AUCTION-13
		$this->virtualFields = array(
				'commission_rank' => 'MGenre.commission_rank',
				//  2015.12.28 ohta ORANGE-1027
				'visit_time' => 'VisitTime.visit_time',
				// murata.s ORANGE-423 ADD(S)
				//NULL,空文字を後ろに持っていくため、アルファベットに変換
				'demand_follow_date' => "(case when DemandInfo.follow_date is null then 'Z' when DemandInfo.follow_date = '' then 'Y' else DemandInfo.follow_date end)"
				// murata.s ORANGE-423 ADD(E)
		);
        return $query;
    }

    /**
     * 開始日時が入力されている場合は、終了日時を入力するように要求します。
     */
    public function checkRequireFrom() {
    	if (!isset($this->data['DemandInfo']['contact_desired_time_to'])) return true;
    	if (strlen($this->data['DemandInfo']['contact_desired_time_to']) == 0) return true;
    	if (!isset($this->data['DemandInfo']['contact_desired_time_from'])) return false;
    	if (strlen($this->data['DemandInfo']['contact_desired_time_from']) == 0) return false;

    	return true;
    }

    /**
     * 範囲指定が選択されていて開始日時が、終了日時を入力するように要求します。
     */
    public function checkRequireTo() {
    	if (!isset($this->data['DemandInfo']['contact_desired_time_from'])) return true;
    	if (strlen($this->data['DemandInfo']['contact_desired_time_from']) == 0) return true;
    	if (!isset($this->data['DemandInfo']['contact_desired_time_to'])) return false;
    	if (strlen($this->data['DemandInfo']['contact_desired_time_to']) == 0) return false;

    	return true;
    }

	/**
	 * setVirtualDetectContactDesiredTime
	 * 連絡期限日時のソート用バーチャルフィールドを設定
	 *
	 */
	public function setVirtualDetectContactDesiredTime($includeVisitTime = false)
	{
		if($this->hasField('detect_contact_desired_time', true) == false){
			if ($includeVisitTime) {
				$quesry = <<<'QUERY'
CASE WHEN VisitTime.is_visit_time_range_flg = 1
	 THEN VisitTime.visit_adjust_time
     WHEN DemandInfo.is_contact_time_range_flg = 1
	 THEN DemandInfo.contact_desired_time_from
     ELSE DemandInfo.contact_desired_time END
QUERY;
			} else {
				$quesry = <<<'QUERY'
CASE WHEN DemandInfo.is_contact_time_range_flg = 1
	 THEN DemandInfo.contact_desired_time_from
     ELSE DemandInfo.contact_desired_time END
QUERY;
			}

			$this->virtualFields['detect_contact_desired_time'] = $quesry;
		}
	}

	/**
	 * 取次リミット超過時間のバーチャルフィールドを設定
	 * ※m_genresへのJOINが必要
	 *
	 * @param  bool $set
	 * @return void
	 */
	public function setVirtualOverLimit($set = true)
	{
		if ($set) {
			$this->virtualFields['limit_over_sec'] = <<< 'QUERY'
CASE WHEN demand_status IN (1, 2, 3)
         THEN (
	     CASE WHEN MGenre.commission_limit_time IS NULL THEN 0
	          WHEN DemandInfo.receive_datetime + cast(MGenre.commission_limit_time || ' minutes' as interval) < NOW()
	              THEN ROUND(EXTRACT(EPOCH FROM NOW() - DemandInfo.receive_datetime - cast(MGenre.commission_limit_time || ' minutes' as interval)))
	          ELSE 0 END
         )
     ELSE 0 END
QUERY;
		} else {
			unset($this->virtualFields['limit_over_sec']);
		}
	}

        // ORANGE-443 2017/07/03
	/**
	 * リアルタイムレポート用の案件状況を取得
	 */
	public function findReportDemandStatus($query = array()) {

            //フィールドの設定
            $Uncounted = '"DemandInfo"."demand_status" IN(1,2,3)';
            $Bidding = '"DemandInfo"."demand_status" IN(1,2,3) AND "DemandInfo"."selection_system" IN(2,3) ';
            $MissingMail = '"DemandInfo"."demand_status" = 2 ';
            $FollowDate = '"DemandInfo"."demand_status" IN(2,3) AND '
                    . ' ("DemandInfo"."follow_date" is not null AND "DemandInfo"."follow_date" <> \'\')';
            $SelectionWaiting = '"DemandInfo"."demand_status" = 1 AND '
                    . ' ("DemandInfo"."follow_date" is not null AND "DemandInfo"."follow_date" <> \'\')';
//2017.11.22 mochizuki.m ORANGE-495 ADD(S)
//2017.11.27 mochizuki.m ORANGE-495-mod1 ADD(S) 
//  業者取次用一覧画面と(JBR様案件)取次前一覧の抽出クエリーに合わせたいとの要望により、
//  要ヒア数と断り数のクエリーを変更
                        //架電可能内/要ヒア数（corp_id = 1755） 用
                        //架電可能内/断り数  （corp_id = 3539） 用
//2017.11.27 mochizuki.m ORANGE-495-mod1 ADD(E) 
            $TodayCommissionNum = '"TodayCommissionInfo"."TodayCommissionFlg" = 1 ';
            $TodayDemandNum     =     '"DemandInfo"."demand_status" <> 9'
                                . ' AND date_trunc(\'day\', "DemandInfo"."created") = current_date ';
//2017.11.27 mochizuki.m ORANGE-495-mod1 DEL(S) 
//            $CallHearNum        = '"CallCommissionInfo"."CallFlg" = 1 ';
//            $CallLossNum        = '"CallCommissionInfo"."CallFlg" = 2 ';
//2017.11.27 mochizuki.m ORANGE-495-mod1 DEL(E) 

            //テーブル連結
            $joins = array(
                        //当日取次総数 用
                        array(
                            'table' => '(SELECT '
                                    .       '"demand_id" '
                                    .       ', 1 AS "TodayCommissionFlg" '
                                    .   'FROM "commission_infos" '
                                    .   'WHERE '
                                    .       '"commission_note_send_datetime" >= to_timestamp(to_char(current_date, \'yyyy-mm-dd\') || \' 09:00:00\', \'yyyy-mm-dd hh24:mi:ss\') '
                                    .   'AND "commission_note_send_datetime" <  to_timestamp(to_char(current_date, \'yyyy-mm-dd\') || \' 22:00:00\', \'yyyy-mm-dd hh24:mi:ss\') '
                                    .   'AND "del_flg" = 0 '
                                    .   'GROUP BY "demand_id"'
                                    .  ')',
                            'alias' => '"TodayCommissionInfo"',
                            'type'  => 'LEFT OUTER',
                            'conditions' => array(
                                                '"DemandInfo"."id" = "TodayCommissionInfo"."demand_id" '
                                         .      'AND "DemandInfo"."demand_status" <> 9'
                                            )
                        ),
//2017.11.27 mochizuki.m ORANGE-495-mod1 DEL(S) 
//                        array(
//                            'table' => '(SELECT '
//                                    .       '"demand_id" '
//                                    .       ', ('
//                                    .       'CASE '
//                                    .       ' WHEN "corp_id" = 1755'
//                                    .       ' THEN 1'
//                                    .       ' ELSE 2'
//                                    .       ' END'
//                                    .       ') AS "CallFlg"'
//                                    .   'FROM "commission_infos" '
//                                    .   'WHERE '
//                                    .       '"corp_id" IN (1755, 3539) '
//                                    .   'AND "lost_flg" <> 0 '
//                                    .   'AND "del_flg" = 0 '
//                                    .   'GROUP BY "demand_id", "corp_id"'
//                                    .  ')',
//                            'alias' => '"CallCommissionInfo"',
//                            'type'  => 'LEFT OUTER',
//                            'conditions' => array(
//                                                '"DemandInfo"."id" = "CallCommissionInfo"."demand_id" '
//                                         .      'AND "DemandInfo"."demand_status" IN (1, 3) '
//                                         .      'AND ("DemandInfo"."follow_date" IS NULL '
//                                         .       ' OR "DemandInfo"."follow_date" = \'\') '
//                                         .      'AND NOT "DemandInfo"."selection_system" IN (2, 3) '
//                                            )
//                        ),
//2017.11.27 mochizuki.m ORANGE-495-mod1 DEL(E) 
                    );
//2017.11.22 mochizuki.m ORANGE-495 ADD(E)

            //取得項目の設定
            $fields = 
//2017.11.22 mochizuki.m ORANGE-495 ADD(S)
                    //当日取次総数
                      'SUM(CASE WHEN '.$TodayCommissionNum.' THEN 1 ELSE 0 END) AS "TodayCommissionNum",'
                    //当日登録案件
                    . 'SUM(CASE WHEN '.$TodayDemandNum.' THEN 1 ELSE 0 END) AS "TodayDemandNum",'
//2017.11.27 mochizuki.m ORANGE-495-mod1 DEL(S) 
//                    //架電可能内/要ヒア数
//                    . 'SUM(CASE WHEN '.$CallHearNum.' THEN 1 ELSE 0 END) AS "CallHearNum",'
//                    //架電可能内/断り数
//                    . 'SUM(CASE WHEN '.$CallLossNum.' THEN 1 ELSE 0 END) AS "CallLossNum",'
//2017.11.27 mochizuki.m ORANGE-495-mod1 DEL(E) 
                    .
//2017.11.22 mochizuki.m ORANGE-495 ADD(E)
                    //未取次総数
                    'SUM(CASE WHEN '.$Uncounted.' THEN 1 ELSE 0 END) AS Uncounted,'
                    //入札中案件
                    . 'SUM(CASE WHEN '.$Bidding.' THEN 1 ELSE 0 END) AS Bidding,'
                    //メール不在
                    . 'SUM(CASE WHEN '.$MissingMail.' THEN 1 ELSE 0 END) AS MissingMail,'
                    //後追い日案件
                    . 'SUM(CASE WHEN '.$FollowDate.' THEN 1 ELSE 0 END) AS FollowDate,'
                    //選定待機案件
                    . 'SUM(CASE WHEN '.$SelectionWaiting.' THEN 1 ELSE 0 END) AS SelectionWaiting,'
                    //架電可能案件
                    . 'SUM(CASE WHEN '.$Uncounted.' AND NOT('.$Bidding.')  AND NOT('.$MissingMail.')AND NOT('.$FollowDate.') '
                    . 'AND NOT('.$SelectionWaiting.') THEN 1 ELSE 0 END) AS PossibleCall';

            //後に取得条件が増えるかもしれないため以下の記載とする
            $query = Hash::merge($query, array(
                'fields' => $fields,
//2017.11.22 mochizuki.m ORANGE-495 ADD(S)
                'joins' => $joins,
//2017.11.22 mochizuki.m ORANGE-495 ADD(E)
            ));
            return $query;
	}

//2017.11.27 mochizuki.m ORANGE-495-mod1 ADD(S) 
    /**
     * リアルタイムレポート（架電可能内/要ヒア数・断り数）を取得
     *      ※業者取次用一覧と同じクエリーにする
     */
    public function getRealTimeReport_HearLossNum1() {

        //クエリー
        $sql = 
              'SELECT '
            .   'SUM("TblCall"."CallHearNum") as "CallHearNum1" '       //架電可能内/要ヒア数
            . ', SUM("TblCall"."CallLossNum") as "CallLossNum1" '       //架電可能内/断り数
            . 'FROM ('
            . 'SELECT '
            .        'SUM(CASE WHEN "CommissionInfo"."corp_id" = 1755 THEN 1 ELSE 0 END) AS "CallHearNum" '
            .      ', SUM(CASE WHEN "CommissionInfo"."corp_id" = 3539 THEN 1 ELSE 0 END) AS "CallLossNum" '
            . 'FROM '
            . '"public"."demand_infos" AS "DemandInfo" '
            . 'INNER JOIN "public"."m_sites" AS "MSite" '
            .   'ON ("DemandInfo"."site_id" = "MSite"."id") '
            . 'LEFT OUTER JOIN ( '
            .   'SELECT '
            .     'min(id) as id '
            .     ', demand_id '
            .   'FROM '
            .     'commission_infos '
            .   'WHERE '
            .     'lost_flg = 0 '
            .     'AND del_flg = 0 '
            .     'AND introduction_not = 0 '
            .   'GROUP BY '
            .     'demand_id '
            . ') AS "DemandCommissionInfo" '
            .   'ON ( '
            .     '"DemandInfo"."id" = "DemandCommissionInfo"."demand_id"'
            .   ') '
            . 'LEFT OUTER JOIN "public"."commission_infos" AS "CommissionInfo" '
            .   'ON ( '
            .     '"DemandCommissionInfo"."id" = "CommissionInfo"."id" '
            .   ') '
            . 'WHERE '
            .   '"MSite"."jbr_flg" = 0 '
            .   'AND ( '
            .     '"DemandInfo"."demand_status" = 7 '
            .     'OR "DemandInfo"."demand_status" = 8 '
            .     'OR ( '
            .           '( '
            .             '"DemandInfo"."demand_status" = 3 '
            .             'AND "DemandInfo"."selection_system" not in (2, 3) '
            .           ') '
            .         'OR ( '
            .             '"DemandInfo"."demand_status" = 3 '
            .             'AND "DemandInfo"."selection_system" IS NULL '
            .           ')'
            .     ')'
            .   ') '
            .   'AND "CommissionInfo"."corp_id" in (1755, 3539) '
            .   'AND "DemandInfo"."del_flg" = 0 '
            . 'GROUP BY  "DemandInfo"."id"'
            . ') AS "TblCall" ';

            //実行
            $data = $this->query($sql);

        return $data;
    }

    /**
     * リアルタイムレポート（(JBR)架電可能内/要ヒア数・断り数）を取得
     *      ※(JBR様案件)取次前一覧と同じクエリーにする
     */
    public function getRealTimeReport_HearLossNum2() {
        //クエリー
        $sql = 
              'SELECT '
            .   'SUM("TblCall"."CallHearNum") as "CallHearNum2" '       //(JBR)架電可能内/要ヒア数
            . ', SUM("TblCall"."CallLossNum") as "CallLossNum2" '       //(JBR)架電可能内/断り数
            . 'FROM ('
            .  'SELECT '
            .        'SUM(CASE WHEN "CommissionInfo"."corp_id" = 1755 THEN 1 ELSE 0 END) AS "CallHearNum" '
            .      ', SUM(CASE WHEN "CommissionInfo"."corp_id" = 3539 THEN 1 ELSE 0 END) AS "CallLossNum" '
            .  'FROM '
            .    '"public"."demand_infos" AS "DemandInfo" '
            .    'INNER JOIN "public"."m_sites" AS "MSite" '
            .      'ON ("DemandInfo"."site_id" = "MSite"."id" '
            .        'AND ("MSite"."jbr_flg" = 1 OR "MSite"."id" = 1314)'
            .      ') '
            .    'LEFT JOIN ( '
            .      'select '
            .        'min(id) as id '
            .        ', demand_id '
            .      'from '
            .        'commission_infos '
            .      'where '
            .        'lost_flg = 0 '
            .        'and del_flg = 0 '
            .        'and introduction_not = 0 '
            .      'group by '
            .        'demand_id'
            .    ') AS "DemandCommissionInfo" '
            .      'ON ("DemandInfo"."id" = "DemandCommissionInfo"."demand_id") '
            .    'LEFT JOIN "public"."commission_infos" AS "CommissionInfo" '
            .      'ON ("DemandCommissionInfo"."id" = "CommissionInfo"."id") '
            .    'LEFT JOIN "public"."m_corps" AS "MCorp" '
            .      'ON ("CommissionInfo"."corp_id" = "MCorp"."id") '
            .  'WHERE '
            .    '(("DemandInfo"."demand_status" = 1) '
            .      'OR ("DemandInfo"."demand_status" = 2) '
            .      'OR ("DemandInfo"."demand_status" = 3) '
            .      'OR ("DemandInfo"."demand_status" = 4)'
            .    ') '
            .    'AND ("DemandInfo"."selection_system" != 2 '
            .     'AND "DemandInfo"."selection_system" != 3) '
            .    'AND "CommissionInfo"."corp_id" in (1755, 3539) '
            .    'AND "DemandInfo"."del_flg" = 0 '
            .  'GROUP BY '
            .    '"DemandInfo"."id" '
            . ') AS "TblCall" ';

            //実行
            $data = $this->query($sql);

        return $data;
    }
//2017.11.27 mochizuki.m ORANGE-495-mod1 ADD(E) 
}
