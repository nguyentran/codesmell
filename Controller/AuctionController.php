<?php
App::uses('AppController', 'Controller');
App::uses('CakeEmail', 'Network/Email');

class AuctionController extends AppController {

	public $name = 'Auction';
	public $helpers = array();
	public $components = array('PDF','Session');
	// 2016.07.11 murata.s ORANGE-1 CHG(S)
	//public $uses = array('DemandInfo','AuctionInfo', 'Refusal', 'VisitTime', 'CommissionInfo', 'MTime', 'MCorp', 'MCorpCategory', 'MCategory', 'AffiliationAreaStat');
        /* 2017/08/03  ichino ORANGE-456 ADD AccumulatedInformation追加 start */
	public $uses = array('DemandInfo','AuctionInfo', 'Refusal', 'VisitTime', 'CommissionInfo', 'MTime', 'MCorp', 'MCorpCategory', 'MCategory', 'AffiliationAreaStat', 'BillInfo', 'MTaxRate', 'AuctionAgreement', 'AuctionAgreementProvisions', 'AuctionAgreementLink', 'AccumulatedInformation');
        /* 2017/08/03  ichino ORANGE-456 ADD end */
	// 2016.07.11 murata.s ORANGE-1 CHG(E)

	public function beforeFilter(){
		parent::beforeFilter();
		$this->set('default_display', false);
		$this->User = $this->Auth->user();

// 2016.10.13 murata.s ORANGE-206 ADD(S) 脆弱性 権限外の操作対応
		if($this->User['auth'] == 'affiliation'){
			$actionName = strtolower($this->action);
			switch ($actionName){
				case 'search': // 検索ページ
					if(isset($this->request->data['delete'])){
						if(!empty($this->request->data['AuctionInfo'])){
							foreach ($this->request->data['AuctionInfo'] as $auction_info){
								$check_data = $this->AuctionInfo->findByIdAndCorpId($auction_info['id'], $this->User['affiliation_id']);
								if(empty($check_data))
									throw new ApplicationException(__('NoReferenceAuthority', true));
							}
						}
					}
					break;
				case 'support': // 対応ページ
				case 'supportjson': // 入札ダイアログ Ajax
					if(isset($this->request->data['completion'])){
						$check_data = $this->AuctionInfo->findByIdAndDemandIdAndCorpId($this->request->data['AuctionInfo']['id'], $this->request->data['AuctionInfo']['demand_id'], $this->User['affiliation_id']);
						if(empty($check_data)){
							throw new ApplicationException(__('NoReferenceAuthority', true));
						}
					}
					break;
				case 'refusal': // 辞退ページ
				case 'refusaljson': // 辞退ダイアログ Ajax
					if(isset($this->request->data['completion'])){
						$auction_id = $this->request->data['Refusal']['auction_id'];
						$check_data = $this->AuctionInfo->findByIdAndCorpId($auction_id, $this->User['affiliation_id']);
						if(empty($check_data)){
							throw new ApplicationException(__('NoReferenceAuthority', true));
						}
					}
					break;
				default:
					break;
			}
		}
// 2016.10.13 murata.s ORANGE-206 ADD(E) 脆弱性 権限外の操作対応
	}

	/**
	 * 初期表示（一覧）
	 *
	 */
	public function index() {

		// 加盟店でログイン時
		if($this->User["auth"] == "affiliation"){
			$auction_already_data = self::__get_auction_already_list();
			$this->set("auction_already_data" , $auction_already_data);

			// ORANGE-1223
			// カレンダーに表示するイベントデータを取得
			$calender_event_data = self::__get_calender_event_data($auction_already_data);
			$this->set("calender_event_data" , $calender_event_data);
		}

		// 住所開示時間リスト取得
		$address_disclosure = self::__getDisclosureData('address_disclosure');
		// 電話番号開示時間リスト取得
		$tel_disclosure = self::__getDisclosureData('tel_disclosure');
		// 他社が対応しましたメッセージが消える時間設定取得
		$support_message_time = self::__getSupportMessageTime();


		$data = array();
		$conditions = self::__setSearchAuction($data);
		$auction_data = self::__get_auction_list($conditions);

		$this->set("results" , $auction_data);

		$this->set("address_disclosure" , $address_disclosure);
		$this->set("tel_disclosure" , $tel_disclosure);
		$this->set("support_message_time" , $support_message_time);

		// モバイル端末用View判定 2015.05.10 y.tanaka
		if(Util::isMobile() && $this->User['auth'] == 'affiliation') {
			$this->layout = 'default_m';
			$this->render('index_m');
		}
	}

	/**
	 * 検索ページ
	 *
	 * @throws Exception
	 */
	public function search() {

		if (isset($this->request->data['delete'])) {
			if(!empty($this->request->data['AuctionInfo'])){
				if($this->AuctionInfo->saveAll($this->request->data['AuctionInfo'])){
					$this->Session->setFlash (__('SuccessRegist', true), 'default', array ('class' => 'message_inner'));
				} else {
					$this->Session->setFlash (__('FailRegist', true), 'default', array ('class' => 'error_inner'));
				}
			}
			$data = self::__auctionListGet();
		} else {
			try {
				if ($this->request->is('Post') || $this->request->is('put')) {
					$data = self::__auctionListPost();
				} else {
					$data = self::__auctionListGet();
				}
			} catch (Exception $e) {
				throw $e;
			}
		}

		// 加盟店でログイン時
		if($this->User["auth"] == "affiliation"){
			$auction_already_data = self::__get_auction_already_list();
			$this->set("auction_already_data" , $auction_already_data);

			// カレンダーに表示するイベントデータを取得
			$calender_event_data = self::__get_calender_event_data($auction_already_data);
			$this->set("calender_event_data" , $calender_event_data);
		}

		// 住所開示時間リスト取得
		$address_disclosure = self::__getDisclosureData('address_disclosure');
		// 電話番号開示時間リスト取得
		$tel_disclosure = self::__getDisclosureData('tel_disclosure');
		// 他社が対応しましたメッセージが消える時間設定取得
		$support_message_time = self::__getSupportMessageTime();

		$conditions = self::__setSearchAuction($data);
		$auction_data = self::__get_auction_list($conditions);

		$this->set("results" , $auction_data);
		$this->set("address_disclosure" , $address_disclosure);
		$this->set("tel_disclosure" , $tel_disclosure);
		$this->set("support_message_time" , $support_message_time);

		// モバイル端末用View判定 2015.05.10 y.tanaka
		if(Util::isMobile()  && $this->User['auth'] == 'affiliation') {
			$this->layout = 'default_m';
			$this->render('index_m');
		} else {
			$this->render('index');
		}
	}

	/**
	 * 案件詳細ページ
	 *
	 * @param string $id
	 */
	public function proposal($id = null) {

		// 加盟店でログイン時
		if($this->User['auth'] == 'affiliation') {
			$auction_data = $this->AuctionInfo->findByDemandIdAndCorpId($id, $this->User["affiliation_id"]);
			if(empty($auction_data["AuctionInfo"]['first_display_time'])){
				$edit_data['AuctionInfo']['id'] = $auction_data["AuctionInfo"]['id'];
				$edit_data['AuctionInfo']['first_display_time'] = date('Y-m-d H:i');
				$this->AuctionInfo->save($edit_data['AuctionInfo']);
			}
		}

		$data = $this->DemandInfo->findById($id);
		$this->set("results" , $data);

		if(Util::isMobile()  && $this->User['auth'] == 'affiliation') {
			$this->layout = 'subwin_m';
			$this->render('proposal_m');
		} else {
			$this->layout = 'subwin';
		}
	}

	/**
	 * 案件詳細JSON
	 *
	 * @param string $id
	 */
	public function proposalJson($id = null) {

		// 加盟店でログイン時
		if($this->User['auth'] == 'affiliation') {
			$auction_data = $this->AuctionInfo->findByDemandIdAndCorpId($id, $this->User["affiliation_id"]);
			if(empty($auction_data["AuctionInfo"]['first_display_time'])){
				$edit_data['AuctionInfo']['id'] = $auction_data["AuctionInfo"]['id'];
				$edit_data['AuctionInfo']['first_display_time'] = date('Y-m-d H:i');
				$this->AuctionInfo->save($edit_data['AuctionInfo']);
			}
		}

		$data = $this->DemandInfo->findById($id);
		$this->set("results" , $data);

		$this->viewClass = 'Json';
		$this->set('_serialize', array('results'));
	}

	/**
	 * 対応ページ
	 *
	 * @param string $id
	 */
	public function support($auction_id = null) {

		if(Util::isMobile()  && $this->User['auth'] == 'affiliation') {
			$this->layout = 'subwin_m';
		} else {
			$this->layout = 'subwin';
		}

		if(isset($this->request->data['completion'])){
			$data = $this->request->data;
			// 入札期限のチェック
			$deadLineTime = $this->DemandInfo->field('auction_deadline_time', array(
				'id' => $data['AuctionInfo']['demand_id']
			));
			if (strtotime($deadLineTime) <= strtotime(date('Y-m-d H:i:s'))) {
				return $this->render('support_past_time');
			}
			try {
				// トランザクション開始
				$this->CommissionInfo->begin();
				$this->CommissionInfo->query('LOCK TABLE commission_infos IN EXCLUSIVE MODE');

				$commission_data = self::__getCommissionData($data['AuctionInfo']['id']);
				if(!empty($commission_data)){
					// このタイミングで取次データがあるということは他社が入札したことになる
					$this->set("results" , $commission_data);
					if(Util::isMobile()  && $this->User['auth'] == 'affiliation') {
						$this->render('support_already_m');
					} else {
						$this->render('support_already');
					}
				} else {
					if(self::__editSupport()){

						// 住所開示時間リスト取得
						$address_disclosure = self::__getDisclosureData('address_disclosure');
						// 電話番号開示時間リスト取得
						$tel_disclosure = self::__getDisclosureData('tel_disclosure');
						$demandData = $this->DemandInfo->findById($this->request->data['AuctionInfo']['demand_id']);
						// 2016.07.11 murata.s ORANGE-1 ADD(S)
						$auction_fee = $this->AuctionInfo->getAuctiolnFee($this->request->data['AuctionInfo']['id']);
						$auction_provisions = $this->AuctionAgreementProvisions->findAuctionAgreementProvisions();
						// 2016.07.11 murata.s ORANGE-1 ADD(E)

						$this->set("demand_data" , $demandData);
						$this->set("address_disclosure" , $address_disclosure);
						$this->set("tel_disclosure" , $tel_disclosure);
						// 2016.07.11 murata.s ORANGE-1 ADD(S)
						$this->set('auction_fee', $auction_fee);
						$this->set('auction_provisions', $auction_provisions);
						// 2016.07.11 murata.s ORANGE-1 ADD(E)

						$mcorp = $this->MCorp->findById($this->User['affiliation_id']);
						$popup_stop_flg = $mcorp['MCorp']['popup_stop_flg'];

						$this->CommissionInfo->commit();
                        
						//2017/08/22  kawamoto ORANGE-456 ADD start
                        $this->_update_accumulated_info_regist_date($data['AuctionInfo']['demand_id'], $this->User['affiliation_id']);
						//2017/08/22  kawamoto ORANGE-456 ADD end
                                                
						if(Util::isMobile()  && $this->User['auth'] == 'affiliation') {
							if ($popup_stop_flg) {
								$this->render('complete_m');
							} else {
								$this->render('support_end_m');
							}
						} else {
							if ($popup_stop_flg) {
								$this->render('complete');
							} else {
								$this->render('support_end');
							}
						}

					} else {
						$this->CommissionInfo->rollback();

						$data = $this->request->data;
						$visit_list = $this->VisitTime->findAllByDemandId($data['AuctionInfo']['demand_id']);
						// 2016.07.11 murata.s ORANGE-1 ADD(S)
						$auction_fee = $this->AuctionInfo->getAuctiolnFee($data['AuctionInfo']['id']);
						$auction_provisions = $this->AuctionAgreementProvisions->findAuctionAgreementProvisions();
						// 2016.07.11 murata.s ORANGE-1 ADD(E)
						$this->set("visit_list" , $visit_list);
						// 2016.07.11 murata.s ORANGE-1 ADD(S)
						$this->set('auction_fee', $auction_fee);
						$this->set('auction_provisions', $auction_provisions);
						// 2016.07.11 murata.s ORANGE-1 ADD(E)

// 2016.07.11 murata.s ORANGE-1 ADD(S) 入力チェック後再表示の場合Noticeエラーが発生する
						$rtn_data = self::__getAuctionInfoDemandInfo($this->data['AuctionInfo']['id']);
						$rtn_data['AuctionAgreementLink']['agreement_check'] = $data['AuctionAgreementLink']['agreement_check'];
						$rtn_data['AuctionInfo']['responders'] = $data['AuctionInfo']['responders'];
						$this->data = $rtn_data;
// 2016.07.11 murata.s ORANGE-1 ADD(E)

						if(Util::isMobile()  && $this->User['auth'] == 'affiliation') {
							$this->render('support_m');
						} else {
							$this->render('support');
						}
					} // __editSupport
				}
			} catch (Exception $e) {
				$this->CommissionInfo->rollback();
			}

		} else { // not completion
			$commission_data = self::__getCommissionData($auction_id);
			if(empty($commission_data)){

				$data = self::__getAuctionInfoDemandInfo($auction_id);
				if(strtotime($data['DemandInfo']['auction_deadline_time']) <= strtotime(date('Y-m-d H:i:s'))) {
					// 対応期限を過ぎている
					if(Util::isMobile()  && $this->User['auth'] == 'affiliation') {
						$this->render('support_limit_m');
					} else {
						$this->render('support_limit');
					}
				} else {
					// 案件に対応でき、他業者が居ない場合
					$this->data = $data;
					$visit_list = $this->VisitTime->findAllByDemandId($data['AuctionInfo']['demand_id']);
					// 2016.07.11 murata.s ORANGE-1 ADD(S)
					$auction_fee = $this->AuctionInfo->getAuctiolnFee($data['AuctionInfo']['id']);
					$auction_provisions = $this->AuctionAgreementProvisions->findAuctionAgreementProvisions();
					// 2016.07.11 murata.s ORANGE-1 ADD(E)
					$this->set("visit_list" , $visit_list);
					// 2016.07.11 murata.s ORANGE-1 ADD(S)
					$this->set('auction_fee', $auction_fee);
					$this->set('auction_provisions', $auction_provisions);
					// 2016.07.11 murata.s ORANGE-1 ADD(E)

					if(Util::isMobile()  && $this->User['auth'] == 'affiliation') {
						$this->render('support_m');
					} else {
						$this->render('support');
					}
				}
			} else {
				// 他社が対応した場合
				$this->set("results" , $commission_data);
				if(Util::isMobile()  && $this->User['auth'] == 'affiliation') {
					$this->render('support_already_m');
				} else {
					$this->render('support_already');
				}
			}
		} // [completion]
	} // function

	/**
	 * 入札ダイアログ Ajax
	 *
	 * @param string $id
	 */
	public function supportJson($auction_id = null) {

		// Viewのレンダーを無効化
		$this->autoRender = false;
		// ContentTypeをJSONにする
		$this->response->type('json');
		// Viewを生成。Controllerの状態が引き継がれる
		$View = new View($this);

		if (isset($this->request->data['completion'])) {
			$data = $this->request->data;
			// 入札期限のチェック
			$deadLineTime = $this->DemandInfo->field('auction_deadline_time', array(
				'id' => $data['AuctionInfo']['demand_id']
			));
			if (strtotime($deadLineTime) <= strtotime(date('Y-m-d H:i:s'))) {
				return json_encode(array(
					'status' => 'success',
					'html' => $View->render('support_past_time_m', false)
				));
			}
			try {
				// トランザクション開始
				$this->CommissionInfo->begin();
				$this->CommissionInfo->query('LOCK TABLE commission_infos IN EXCLUSIVE MODE');

				// ORANGE-1150 MOD 【至急】【入札式】複数加盟店様が同時に入札できている
				$commission_data = self::__getCommissionData($data['AuctionInfo']['id']);
				if (!empty($commission_data)) {

					// このタイミングで取次データがあるということは他社が入札したことになる
					$View->set('results', $commission_data);
					$html = $View->render('support_already_m', false);
					return json_encode(array(
						'status' => 'success',
						'html' => $html
					));

				} else {

					if (self::__editSupport()) {

						// 住所開示時間リスト取得
						$address_disclosure = self::__getDisclosureData('address_disclosure');
						// 電話番号開示時間リスト取得
						$tel_disclosure = self::__getDisclosureData('tel_disclosure');
						$demandData = $this->DemandInfo->findById($this->request->data['AuctionInfo']['demand_id']);

						$this->set("demand_data", $demandData);
						$this->set("address_disclosure", $address_disclosure);
						$this->set("tel_disclosure", $tel_disclosure);
						// 2016.07.11 murata.s ORANGE-1 ADD(S)
						$auction_fee = $this->AuctionInfo->getAuctiolnFee($auction_id);
						$View->set('auction_fee', $auction_fee);
						$auction_provisions = $this->AuctionAgreementProvisions->findAuctionAgreementProvisions();
						$View->set('auction_provisions', $auction_provisions);
						// 2016.07.11 murata.s ORANGE-1 ADD(E)

						$mcorp = $this->MCorp->findById($this->User['affiliation_id']);
						$popup_stop_flg = $mcorp['MCorp']['popup_stop_flg'];

						$this->CommissionInfo->commit();

						//2017/08/22  kawamoto ORANGE-456 ADD start
                        $this->_update_accumulated_info_regist_date($data['AuctionInfo']['demand_id'], $this->User['affiliation_id']);
						//2017/08/22  kawamoto ORANGE-456 ADD end

						$html = $View->render('complete_m', false);
						return json_encode(array(
							'status' => 'success',
							'html' => $html
						));

//			if ($popup_stop_flg) {
//					$this->render('complete_m');
//			} else {
//					$this->render('support_end_m');
//			}

					} else {
						$this->CommissionInfo->rollback();

						$data = $this->request->data;
						$visit_list = $this->VisitTime->findAllByDemandId($data['AuctionInfo']['demand_id']);
						$View->set("visit_list", $visit_list);

						// 住所開示時間リスト取得
						$address_disclosure = self::__getDisclosureData('address_disclosure');
						// 電話番号開示時間リスト取得
						$tel_disclosure = self::__getDisclosureData('tel_disclosure');
						$demandData = $this->DemandInfo->findById($this->request->data['AuctionInfo']['demand_id']);

						$View->set("demand_data", $demandData);
						$View->set("address_disclosure", $address_disclosure);
						$View->set("tel_disclosure", $tel_disclosure);
						// 2016.07.11 murata.s ORANGE-1 ADD(S)
						$auction_fee = $this->AuctionInfo->getAuctiolnFee($data['AuctionInfo']['id']);
						$View->set('auction_fee', $auction_fee);
						$auction_provisions = $this->AuctionAgreementProvisions->findAuctionAgreementProvisions();
						$View->set('auction_provisions', $auction_provisions);
						// 2016.07.11 murata.s ORANGE-1 ADD(E)

// 2016.07.11 murata.s ORANGE-1 ADD(S) 入力チェックエラー後のNoticeエラー対応
						$rtn_data = self::__getAuctionInfoDemandInfo($data['AuctionInfo']['id']);
						$rtn_data['AuctionAgreementLink']['agreement_check'] = $data['AuctionAgreementLink']['agreement_check'];
						$rtn_data['AuctionInfo']['responders'] = $data['AuctionInfo']['responders'];
						$this->data = $rtn_data;
// 2016.07.11 murata.s ORANGE-1 ADD(E)

						$html = $View->render('support_m', false);
						return json_encode(array(
							'status' => 'success',
							'html' => $html
						));
					} // __editSupport
				}
			} catch (Exception $e) {
				$this->CommissionInfo->rollback();
			}

		} else { // not completion

			$commission_data = self::__getCommissionData($auction_id);
			if (empty($commission_data)) {

				$data = self::__getAuctionInfoDemandInfo($auction_id);
				if (strtotime($data['DemandInfo']['auction_deadline_time']) <= strtotime(date('Y-m-d H:i:s'))) {
					// 対応期限を過ぎている
					$html = $View->render('support_limit_m', false);
					return json_encode(array(
						'status' => 'success',
						'html' => $html
					));
				} else {
					// 案件に対応でき、他業者が居ない場合
					$this->data = $data;
					$visit_list = $this->VisitTime->findAllByDemandId($data['AuctionInfo']['demand_id']);
					$View->set('visit_list', $visit_list);

					// 住所開示時間リスト取得
					$address_disclosure = self::__getDisclosureData('address_disclosure');
					// 電話番号開示時間リスト取得
					$tel_disclosure = self::__getDisclosureData('tel_disclosure');
					$demandData = $this->DemandInfo->findById($this->request->data['AuctionInfo']['demand_id']);

					$View->set("demand_data", $demandData);
					$View->set("address_disclosure", $address_disclosure);
					$View->set("tel_disclosure", $tel_disclosure);
					// 2016.07.11 murata.s ORANGE-1 ADD(S)
					$auction_fee = $this->AuctionInfo->getAuctiolnFee($auction_id);
					$View->set('auction_fee', $auction_fee);
					$auction_provisions = $this->AuctionAgreementProvisions->findAuctionAgreementProvisions();
					$View->set('auction_provisions', $auction_provisions);
					// 2016.07.11 murata.s ORANGE-1 ADD(E)

					$html = $View->render('support_m', false);
					return json_encode(array(
						'status' => 'success',
						'html' => $html
					));
				}
			} else {
				// 他社が対応した場合
				$html = $View->render('support_already_m', false);
				return json_encode(array(
					'status' => 'success',
					'html' => $html
				));
			}
		} // [completion]
	} // function

	/**
	 * JBR対応状況更新（Ajax）
	 *
	 * @param  int $corp_id
	 */
	public function update_jbr_available_status($corp_id) {

		$this->MCorp->updateAll(
			array('MCorp.jbr_available_status' => 2),
			array('MCorp.id' => $corp_id)
		);

		$json_data = array(
			'update' => true
		);

		$this->set(compact('json_data'));

		$this->viewClass = 'Json';
		$this->set('_serialize', array('json_data'));
	}

	/**
	 * 辞退ページ
	 *
	 * @param string $id
	 */
	public function refusal($auction_id = null) {

		$window_close = false;
		if(isset($this->request->data['completion'])){
			$auction_id = $this->request->data['Refusal']['auction_id'];

// 2016.12.07 murata.s CHG(S) 辞退済みかどうかチェックする
			$auction = $this->AuctionInfo->findById($auction_id);
			if($auction['AuctionInfo']['refusal_flg'] != 1){

				if (self::__editRefusal()){
                                        //2017/08/03  ichino ORANGE-456 ADD start
                                        //辞退を行ったので、蓄積情報に辞退登録日時を登録する
                                        $this->_update_accumulated_info_refusal_date(Hash::get($auction, 'AuctionInfo.demand_id'), Hash::get($auction, 'AuctionInfo.corp_id'));
                                        //2017/08/03  ichino ORANGE-456 ADD end
                                        
					$window_close = true;
				} else {
					$this->Session->setFlash (__('FailRegist', true), 'default', array ('class' => 'error_inner'));
					$this->data = $this->request->data;
				}
			}else{
				// すでに辞退されている場合は何もしない
				$window_close = true;
			}
// 2016.12.07 murata.s CHG(E) 辞退済みかどうかチェックする
			// 案件に対応でき、他業者が居ない場合
			$this->set("window_close" , $window_close);
			$this->set("auction_id" , $auction_id);
			// murata.s ORANGE-539 ADD(S)
			$data = self::__getAuctionInfoDemandInfo($auction_id);
			$this->set("demand_status" , $data['DemandInfo']['demand_status']);
			// murata.s ORANGE-539 ADD(E)

			if(Util::isMobile()  && $this->User['auth'] == 'affiliation') {
				$this->layout = 'subwin_m';
				$this->render('refusal_m');
			} else {
				$this->layout = 'subwin';
			}

		} else {
			$commission_data = self::__getCommissionData($auction_id);
			if(empty($commission_data)){
				$data = self::__getAuctionInfoDemandInfo($auction_id);

				if(strtotime($data['DemandInfo']['auction_deadline_time']) <= strtotime(date('Y-m-d H:i:s'))) {
					// 対応期限を過ぎている
					if(Util::isMobile()  && $this->User['auth'] == 'affiliation') {
						$this->render('support_limit_m');
					} else {
						$this->render('support_limit');
					}

				} else {
					// 案件に対応でき、他業者が居ない場合
					$this->set("window_close" , $window_close);
					$this->set("auction_id" , $auction_id);
					$this->set("demand_status" , $data['DemandInfo']['demand_status']);

					if(Util::isMobile()  && $this->User['auth'] == 'affiliation') {
						$this->layout = 'subwin_m';
						$this->render('refusal_m');
					} else {
						$this->layout = 'subwin';
					}

				}
			} else {
				// 他社が対応した場合
				$this->set("results" , $commission_data);
				if(Util::isMobile()  && $this->User['auth'] == 'affiliation') {
					$this->render('support_already_m');
				} else {
					$this->render('support_already');
				}
			}
		}
	}

	/**
	 * 辞退ダイアログ Ajax
	 *
	 * @param string $id
	 */
	public function refusalJson($auction_id = null) {

	// Viewのレンダーを無効化
	$this->autoRender = false;
	// ContentTypeをJSONにする
	$this->response->type('json');
	// Viewを生成。Controllerの状態が引き継がれる
	$View = new View($this);

	$window_close = false;
		if(isset($this->request->data['completion'])){
			$auction_id = $this->request->data['Refusal']['auction_id'];

// 2016.12.07 murata.s CHG(S) 辞退済みかどうかチェックする
			$auction = $this->AuctionInfo->findById($auction_id);
			if($auction['AuctionInfo']['refusal_flg'] != 1){
				if (self::__editRefusal()){
                                        //2017/08/03  ichino ORANGE-456 ADD start
                                        //辞退を行ったので、蓄積情報に辞退登録日時を登録する
                                        $this->_update_accumulated_info_refusal_date(Hash::get($auction, 'AuctionInfo.demand_id'), Hash::get($auction, 'AuctionInfo.corp_id'));
                                        //2017/08/03  ichino ORANGE-456 ADD end
                                        
					$window_close = true;
				} else {
					$this->Session->setFlash (__('FailRegist', true), 'default', array ('class' => 'error_inner'));
					$this->data = $this->request->data;
				}
			}else{
				// すでに辞退されている場合は何もしない
				$window_close = true;
			}
// 2016.12.07 murata.s CHG(S) 辞退済みかどうかチェックする

			// 案件に対応でき、他業者が居ない場合
			$View->set('window_close', $window_close);
			$View->set('auction_id', $auction_id);
			$html = $View->render('refusal_m', false);
			return json_encode(array(
		'status' => 'success',
		'html' => $html
			));
		} else {
			$commission_data = self::__getCommissionData($auction_id);
			if(empty($commission_data)){
				$data = self::__getAuctionInfoDemandInfo($auction_id);

				if(strtotime($data['DemandInfo']['auction_deadline_time']) <= strtotime(date('Y-m-d H:i:s'))) {
					// 対応期限を過ぎている
				$html = $View->render('support_limit_m', false);
				return json_encode(array(
			'status' => 'success',
			'html' => $html
				));
				} else {
					// 案件に対応でき、他業者が居ない場合
				$View->set('window_close', $window_close);
				$View->set('auction_id', $auction_id);
					$View->set("demand_status" , $data['DemandInfo']['demand_status']);
				$html = $View->render('refusal_m', false);
				return json_encode(array(
			'status' => 'success',
			'html' => $html
				));
		}
			} else {
				// 他社が対応した場合
		$View->set('results', $commission_data);
		$html = $View->render('support_already_m', false);
		return json_encode(array(
				'status' => 'success',
				'html' => $html
		));
			}
		}
	}

	/**
	 * 最終ページ処理
	 *
	 * @param string $id
	 */
	public function complete() {

		if(Util::isMobile()  && $this->User['auth'] == 'affiliation') {
			$this->layout = 'subwin_m';
		} else {
			$this->layout = 'subwin';
		}

		// ポップアップ非表示フラグを更新
		if ($this->request->data['popup_stop_flg'] === '1') {
			$data['MCorp'] = array('id' => $this->User['affiliation_id'], 'popup_stop_flg' => 1);

			$this->MCorp->save($data);

			$this->request->data['complete'] = 1;

		}

		if(Util::isMobile()  && $this->User['auth'] == 'affiliation') {
			$this->render('complete_m');
		} else {
			$this->render('complete');
		}

	}

	/**
	 * 他社が対応しましたメッセージが消える時間設定取得
	 *
	 * @return number
	 */
	private function __getSupportMessageTime(){
		$results = $this->MTime->findByitemCategory('support_message');
		return !empty($results["MTime"]["item_hour_date"])? $results["MTime"]["item_hour_date"] : 0 ;
	}

	/**
	 * 開示時間リスト取得
	 *
	 * @param unknown $item_category
	 * @return multitype:
	 */
	private function __getDisclosureData($item_category){
		$results = $this->MTime->find('all',
				array( 'fields' => '*',
						'conditions' => array('MTime.item_category' => $item_category),
						'order' => array('MTime.item_id' => 'desc'),
				)
		);

		$list = array();
		foreach ($results as $key => $val){
			$list[$key+ 1]['item_hour_date'] = $val["MTime"]['item_hour_date'];
			$list[$key+ 1]['item_minute_date'] = $val["MTime"]['item_minute_date'];
		}

		return $list;
	}

	/**
	 * オークションIDから案件情報オークション案件情報を取得
	 *
	 * @param unknown $auction_id
	 * @return unknown
	 */
	private function __getAuctionInfoDemandInfo($auction_id){

		$results = $this->AuctionInfo->find( 'first', array (
				'fields' => 'AuctionInfo.*, DemandInfo.contact_desired_time, DemandInfo.contact_desired_time_from, DemandInfo.contact_desired_time_to, DemandInfo.auction_deadline_time, DemandInfo.business_trip_amount, DemandInfo.cost_from, DemandInfo.cost_to, DemandInfo.demand_status, DemandInfo.site_id, MCorp.jbr_available_status',
				'joins' => array (
						array (
								'fields' => '*',
								'type' => 'inner',
								"table" => "demand_infos",
								"alias" => "DemandInfo",
								"conditions" => array (
										"DemandInfo.id = AuctionInfo.demand_id"
								)
						),
						array (
								'fields' => '*',
								'type' => 'inner',
								"table" => "m_corps",
								"alias" => "MCorp",
								"conditions" => array (
										"MCorp.id = AuctionInfo.corp_id"
								)
						),
				),
				'conditions' => array (
						'AuctionInfo.id' => $auction_id
				)
		) );

		return $results;
	}

	/**
	 * オークション案件が既に対応されているチェック
	 *
	 * @param $auction_id
	 * @return array|false
	 */
	private function __getCommissionData($auction_id)
	{
		//上限数を取得
		$max = $this->AuctionInfo->findMaxLimit($auction_id);
		//入札数を取得
		$currentNum = $this->AuctionInfo->findCurrentCommitNum($auction_id);
		//上限数に達していたら最後に確定したデータを取得
		$this->AuctionInfo->findLastCommission($auction_id);
		if ($currentNum >= $max) {
			return $this->AuctionInfo->findLastCommission($auction_id);
		}
		return false;
	}

	/**
	 * 対応ボタン済み対応案件一覧
	 *
	 * @return unknown
	 */
	private function __get_auction_already_list(){
            //2017-07-19 ichino ORANGE-32 ADD start 案件添付ファイルの有無追加
            $subQuery = '(select distinct demand_id from demand_attached_files)';
            //2017-07-19 ichino ORANGE-32 ADD end

		$conditions = array (
				'AuctionInfo.corp_id' => $this->User["affiliation_id"],
				'AuctionInfo.display_flg' => 0,
				'AuctionInfo.refusal_flg' => 0,
				'CommissionInfo.commit_flg' => 1,
		);

		$joins = array (
// 2016.11.17 murata.s ORANGE-185 CHG(S)
// 				array (
// 						'fields' => '*',
// 						'type' => 'inner',
// 						"table" => "demand_infos",
// 						"alias" => "DemandInfo",
// 						"conditions" => array (
// 								"AuctionInfo.demand_id = DemandInfo.id",
// 								"DemandInfo.selection_system =".Util::getDivValue('selection_type', 'AuctionSelection')
// 						)
// 				),
				array (
						'fields' => '*',
						'type' => 'inner',
						"table" => "demand_infos",
						"alias" => "DemandInfo",
						"conditions" => array (
								"AuctionInfo.demand_id = DemandInfo.id",
								"DemandInfo.selection_system" => array(
										Util::getDivValue('selection_type', 'AuctionSelection'),
										Util::getDivValue('selection_type', 'AutomaticAuctionSelection')
								)
						)
				),
// 2016.11.17 murata.s ORANGE-185 CHG(E)
				array (
					'tyle' => 'inner',
					'table' => 'm_sites',
					'alias' => 'MSite',
					'conditions' => array(
						'DemandInfo.site_id = MSite.id'
					)
				),
				array (
						'fields' => '*',
						'type' => 'inner',
						"table" => "commission_infos",
						"alias" => "CommissionInfo",
						"conditions" => array (
								"AuctionInfo.demand_id = CommissionInfo.demand_id",
								"AuctionInfo.corp_id = CommissionInfo.corp_id",
								// 2017.08.15 e.takeuchi@SharingTechnology ORANGE-489 ADD(S)
								"AuctionInfo.push_time >= (now() - interval '1 week')"
								// 2017.08.15 e.takeuchi@SharingTechnology ORANGE-489 ADD(E)
						)
				),
				array (
						'fields' => '*',
						'type' => 'left',
						"table" => "visit_times",
						"alias" => "VisitTime",
						"conditions" => array (
								"AuctionInfo.visit_time_id = VisitTime.id"
						)
				),
				array (
						'fields' => '*',
						'type' => 'left',
						"table" => "m_genres",
						"alias" => "MGenre",
						"conditions" => array (
								"MGenre.id = DemandInfo.genre_id"
						)
				),
				array (
						'fields' => '*',
						'type' => 'inner',
						"table" => "m_corps",
						"alias" => "MCorp",
						"conditions" => array (
								"MCorp.id = AuctionInfo.corp_id"
						)
				),
                                //2017-07-19 ichino ORANGE-32 ADD start 案件添付ファイルの有無追加
                                array(
                                        'fields' => array('DemandAttachedFiles.demand_id'),
                                        'type' => 'left',
                                        'table' => $subQuery,
                                        'alias' => 'DemandAttachedFiles',
                                        'conditions' => array(
                                                "DemandInfo.id = DemandAttachedFiles.demand_id"
                                        )
                                ),
                                //2017-07-19 ichino ORANGE-32 ADD end 案件添付ファイルの有無追加

		);

		// 並び順の指定 2015.09.19 tanaka
		// AuctionInfo.demand_idを追加 2016.02.07 h.hanaki ORANGE-985
		$sort = isset($this->params->query['sort']) ? $this->params->query['sort'] : null;
		$orderType = isset($this->params->query['order']) ? $this->params->query['order'] : 'asc';

		switch ($sort) {
			case 'demand_id':
				$order = array('AuctionInfo.demand_id' => $orderType);
				break;
			case 'visit_time':
				$order = array('VisitTime.visit_time' => $orderType);
				break;
			case 'contact_desired_time':
				$order = array('DemandInfo.contact_desired_time' => $orderType);
				break;
			case 'genre_name':
				$order = array('MGenre.genre_name' => $orderType);
				break;
			case 'customer_name':
				$order = array('DemandInfo.customer_name' => $orderType);
				break;
			case 'tel1':
				$order = array('DemandInfo.tel1' => $orderType);
				break;
			case 'address':
				$order = array(
					'DemandInfo.address1' => $orderType,
					'DemandInfo.address2' => $orderType,
					'DemandInfo.address3' => $orderType,
				);
				break;
			default:
				$order = array('VisitTime.visit_time' => $orderType);
				break;
		}

		$results = $this->AuctionInfo->find('all',
				// 2015.12.23 ORANGE-1027
				// // 2015.10.10 h.hanaki CHG	   ORANGE_AUCTION-17(追加:DemandInfo.construction_class)
                                //2017-07-19 ichino ORANGE-32 ADD start DemandAttachedFiles.demand_id追加
				array( 'fields' => '*, VisitTime.visit_time, MGenre.genre_name, DemandInfo.customer_name, DemandInfo.customer_tel, DemandInfo.tel1, DemandInfo.address1, DemandInfo.address2, DemandInfo.address3, DemandInfo.contact_desired_time, DemandInfo.priority, DemandInfo.construction_class, CommissionInfo.id, CommissionInfo.demand_id, CommissionInfo.visit_desired_time, CommissionInfo.order_respond_datetime, MSite.site_name, MCorp.auction_masking, DemandInfo.is_contact_time_range_flg, DemandInfo.contact_desired_time_from, DemandInfo.contact_desired_time_to, VisitTime.is_visit_time_range_flg, VisitTime.visit_time_from, VisitTime.visit_time_to, VisitTime.visit_adjust_time, DemandAttachedFiles.demand_id',
						'joins' =>  $joins,
						'conditions' => $conditions,
						'order' => $order,
				)
		);

		return $results;

// 		$this->paginate = array(
//			'conditions' => $conditions,
//			'fields' => '*, VisitTime.visit_time, MGenre.genre_name, DemandInfo.customer_name, DemandInfo.customer_tel, DemandInfo.address1, DemandInfo.address2, DemandInfo.address3, DemandInfo.contact_desired_time, DemandInfo.priority',
//			'joins' => $joins,
//			'order' => array('AuctionInfo.id' => 'desc'),
//		);
//		$results = $this->paginate('AuctionInfo');

	}

	/**
	 * オークション情報の更新
	 *
	 */
	private function __editSupport(){
		$data = $this->request->data;
		try {
			// トランザクション開始
			$this->AuctionInfo->begin();
			$this->CommissionInfo->begin();
			$this->DemandInfo->begin();
			// 2016.07.11 murata.s ORANGE-1 ADD(S)
			$this->BillInfo->begin();
			$this->AuctionAgreementLink->begin();
			// 2016.07.11 murata.s ORANGE-1 ADD(E)

			$resultsFlg = $this->AuctionInfo->save($data['AuctionInfo']);

			if($resultsFlg){
				$edit = array();

				$demand_data = $this->DemandInfo->findById($data['AuctionInfo']['demand_id']);

				$category_data = $this->MCorpCategory->findByCorpIdAndGenreIdAndCategoryId($data['AuctionInfo']['corp_id'], $demand_data['DemandInfo']['genre_id'], $demand_data['DemandInfo']['category_id']);
// murata.s ORANGE-261 CHG(S)
				if($category_data['MCorpCategory']['corp_commission_type'] != 2){
					// 成約ベース
					$commission_type = Util::getDivValue('commission_type', 'normal_commission');
					$commission_status = Util::getDivValue('construction_status','progression');
				}else{
					// 紹介ベース
					$commission_type = Util::getDivValue('commission_type', 'package_estimate');
					$commission_status = Util::getDivValue('construction_status','introduction');
					$category_data['MCorpCategory']['order_fee'] = $category_data['MCorpCategory']['introduce_fee'];
					$category_data['MCorpCategory']['order_fee_unit'] = 0;
					$edit['CommissionInfo']['confirmd_fee_rate'] = 100;
					$edit['CommissionInfo']['commission_fee_rate'] = 100;
					$edit['CommissionInfo']['complete_date'] = date('Y/m/d');
				}

				// デフォルト手数料率とデフォルト手数料率単位を取得する
// 2017.01.04 murata.s ORANGE-244 CHG(S)
// 2016.08.09 murata.s ORANGE-151 CHG(S)
				if( empty($category_data['MCorpCategory']['order_fee']) || is_null($category_data['MCorpCategory']['order_fee_unit'])){
// 2016.08.09 murata.s ORANGE-151 CHG(E)
// 2017.01.04 murata.s ORANGE-244 CHG(E)
					// m_corp_categoriesから取得できなかった場合、m_categoriesから取得する
					$mc_category_data = self::__get_fee_data_m_categories($demand_data['DemandInfo']['category_id']);
					if(empty($mc_category_data)){
						// m_categoriesからも取得できなかった場合、空白とする。
						$mc_category_data['MCategory'] = array('category_default_fee' => '', 'category_default_fee_unit' => '');
					}
					$category_data['MCorpCategory']['order_fee'] = $mc_category_data['MCategory']['category_default_fee'];
					$category_data['MCorpCategory']['order_fee_unit'] = $mc_category_data['MCategory']['category_default_fee_unit'];
					$category_data['MCorpCategory']['note'] = '';
				}

				$edit['CommissionInfo']['demand_id'] = $data['AuctionInfo']['demand_id'];
				$edit['CommissionInfo']['corp_id'] = $data['AuctionInfo']['corp_id'];
				$edit['CommissionInfo']['commit_flg'] = 1;
				$edit['CommissionInfo']['commission_type'] = $commission_type;
				$edit['CommissionInfo']['commission_status'] = $commission_status;
				$edit['CommissionInfo']['unit_price_calc_exclude'] = 0;
				$edit['CommissionInfo']['commission_note_send_datetime'] = date('Y/m/d H:i', time());
				// 2015.12.29 n.kai ADD start ORANGE-1027
				if (isset($data['AuctionInfo']['visit_time_id'])) {
					$edit['CommissionInfo']['commission_visit_time_id'] = $data['AuctionInfo']['visit_time_id'];
				}
				// 2015.12.29 n.kai ADD end ORANGE-1027

				if ($category_data['MCorpCategory']['order_fee_unit'] == 0) {
					$edit['CommissionInfo']['corp_fee'] = $category_data['MCorpCategory']['order_fee'];
				} else if ($category_data['MCorpCategory']['order_fee_unit'] == 1) {
					$edit['CommissionInfo']['commission_fee_rate'] = $category_data['MCorpCategory']['order_fee'];
				}

				$edit['CommissionInfo']['business_trip_amount'] = !empty($data['DemandInfo']['business_trip_amount']) ? $data['DemandInfo']['business_trip_amount'] : 0 ;

				// 単価ランクを取得し、取次データにセットする
				$affiliation_area_data = $this->AffiliationAreaStat->findByCorpIdAndGenreIdAndPrefecture($data['AuctionInfo']['corp_id'], $demand_data['DemandInfo']['genre_id'], $demand_data['DemandInfo']['address1']);
				$edit['CommissionInfo']['select_commission_unit_price_rank'] = !empty($affiliation_area_data['AffiliationAreaStat']['commission_unit_price_rank']) ? $affiliation_area_data['AffiliationAreaStat']['commission_unit_price_rank'] : '-' ;
				// 取次単価を取得し、取次データにセットする
				$edit['CommissionInfo']['select_commission_unit_price'] = !empty($affiliation_area_data['AffiliationAreaStat']['commission_unit_price_category']) ? $affiliation_area_data['AffiliationAreaStat']['commission_unit_price_category'] : '' ;
// 2017.01.04 murata.s ORANGE-244 ADD(S)
				// 手数料単位を取次データにセットする
				$edit['CommissionInfo']['order_fee_unit'] = $category_data['MCorpCategory']['order_fee_unit'];
// 2017.01.04 murata.s ORANGE-244 ADD(E)
// murata.s ORANGE-261 CHG(E)
				// commission_infos格納
				$resultsFlg = $this->CommissionInfo->save($edit['CommissionInfo']);

			}

			if($resultsFlg){
				// 2016.07.11 murata.s ORANGE-1 ADD(S)
				$commission_info = $resultsFlg;
				// 2016.07.11 murata.s ORANGE-1 ADD(E)

				$edit = array();
				$edit['DemandInfo']['id'] = $data['AuctionInfo']['demand_id'];
				$edit['DemandInfo']['push_stop_flg'] = 1;
				$edit['DemandInfo']['demand_status'] = Util::getDivValue('demand_status', 'information_sent');
				$resultsFlg = $this->DemandInfo->save($edit['DemandInfo'], false);
			}

// murata.s ORANGE-261 ADD(S)
			if($resultsFlg
					&& $commission_info['CommissionInfo']['commission_type'] == Util::getDivValue('commission_type', 'package_estimate')){
				// 請求情報の作成
				$resultsFlg = $this->__editBillInfo($commission_info);
			}
// murata.s ORANGE-261 ADD(E)

// 2016.07.11 murata.s ORANGE-1 ADD(S)
			if($resultsFlg){
				$auction_fee = $this->AuctionInfo->getAuctiolnFee($data['AuctionInfo']['id']);
				if(!empty($auction_fee)){
					$m_tax = $this->MTaxRate->find('first', array(
							'conditions' => array(
									'start_date <=' => date('Y-m-d H:i:s'),
									'or' => array(
											"end_date = ''" ,
											'end_date >=' => date('Y-m-d H:i:s'))
							)
					));

					$this->BillInfo->create();
					$bill = array();
					$bill['BillInfo']['demand_id'] = $data['AuctionInfo']['demand_id'];
					$bill['BillInfo']['bill_status'] = 1;
					$bill['BillInfo']['comfirmed_fee_rate'] = 100;
					$bill['BillInfo']['fee_target_price'] = $auction_fee;
					$bill['BillInfo']['fee_tax_exclude'] = $auction_fee;
					$bill['BillInfo']['tax'] = $bill['BillInfo']['fee_tax_exclude'] * $m_tax['MTaxRate']['tax_rate'];
					$bill['BillInfo']['total_bill_price'] = $bill['BillInfo']['fee_tax_exclude'] + $bill['BillInfo']['tax'];
					$bill['BillInfo']['fee_payment_price'] = 0;
					$bill['BillInfo']['fee_payment_balance'] = $bill['BillInfo']['total_bill_price'];
					$bill['BillInfo']['commission_id'] = $commission_info['CommissionInfo']['id'];
					$bill['BillInfo']['auction_id'] = $data['AuctionInfo']['id'];
					$resultsFlg = $this->BillInfo->save($bill['BillInfo'], false);
				}
			}

			if($resultsFlg){
				if(!empty($auction_fee)){
					// 入札時の入札手数料同意書ID(= 最新の入札手数料同意書ID)を取得
					$auction_agreement = $this->AuctionAgreement->find('first', array('order' => array('id' => 'desc')));


					$auction_link = array();
					$auction_link['AuctionAgreementLink']['auction_id'] = $data['AuctionInfo']['id'];
					$auction_link['AuctionAgreementLink']['corp_id'] = $data['AuctionInfo']['corp_id'];
					$auction_link['AuctionAgreementLink']['auction_agreement_id'] = isset($auction_agreement['AuctionAgreement']['id']) ?  $auction_agreement['AuctionAgreement']['id'] : 1;
					$auction_link['AuctionAgreementLink']['demand_id'] = $data['AuctionInfo']['demand_id'];
					$auction_link['AuctionAgreementLink']['commission_id'] = $commission_info['CommissionInfo']['id'];
					$auction_link['AuctionAgreementLink']['auction_fee'] = $auction_fee;
					$auction_link['AuctionAgreementLink']['agreement_check'] = $data['AuctionAgreementLink']['agreement_check'];
					$auction_link['AuctionAgreementLink']['responders'] = $data['AuctionInfo']['responders'];
					$resultsFlg = $this->AuctionAgreementLink->save($auction_link['AuctionAgreementLink']);
				}
 			}
// 2016.07.11 murata.s ORANGE-1 ADD(E)

			if($resultsFlg){
				$this->AuctionInfo->commit();
				$this->CommissionInfo->commit();
				$this->DemandInfo->commit();
				// 2016.07.11 murata.s ORANGE-1 ADD(S)
				$this->BillInfo->commit();
				$this->AuctionAgreementLink->commit();
				// 2016.07.11 murata.s ORANGE-1 ADD(E)
			} else {
				$this->AuctionInfo->rollback();
				$this->CommissionInfo->rollback();
				$this->DemandInfo->rollback();
				// 2016.07.11 murata.s ORANGE-1 ADD(S)
				$this->BillInfo->rollback();
				$this->AuctionAgreementLink->rollback();
				// 2016.07.11 murata.s ORANGE-1 ADD(E)
			}
		} catch (Exception $e) {
			$this->AuctionInfo->rollback();
			$this->CommissionInfo->rollback();
			$this->DemandInfo->rollback();
			// 2016.07.11 murata.s ORANGE-1 ADD(S)
			$this->BillInfo->rollback();
			$this->AuctionAgreementLink->rollback();
			// 2016.07.11 murata.s ORANGE-1 ADD(E)
			$resultsFlg = false;
		}

		return $resultsFlg;

	}

	/**
	 * 一覧の検索結果を取得しセット
	 *
	 * @return multitype:Ambigous <NULL, mixed, multitype:>
	 */
	private function __auctionListGet() {

		$data = $this->Session->read(self::$__sessionKeyForAuctionSearch);

		if (isset($data)) {
			$page = $this->__getPageNum();
			$this->data = $data;
		}

		if(!empty($data)){
			$data += array('page' => $page);
		}

		return $data;
	}

	/**
	 * 検索結果をセッションにセット
	 *
	 * @return multitype:
	 */
	private function __auctionListPost() {

		$data = $this->request->data;

		$this->Session->delete(self::$__sessionKeyForAuctionSearch);
		$this->Session->write(self::$__sessionKeyForAuctionSearch, $data);

		return $data;
	}

	/**
	 * 一覧検索条件作成
	 *
	 * @param string $data
	 * @return multitype:string
	 */
	private function __setSearchAuction($data = null) {

		$support_message_time = self::__getSupportMessageTime();

		$conditions = array (
				array(
					"DemandInfo.demand_status != 6",
					"DemandInfo.demand_status != 9",
				),
				array (
						'OR' => array (
								array (
										'VisitTime.visit_time_min >' => date ( 'Y-m-d H:i' )
								),
								/*
								array (
										'VisitTime.visit_time_min' => NULL,
										'DemandInfo.contact_desired_time >' => date ( 'Y-m-d H:i' )
								)
								*/
								array (
									'VisitTime.visit_time_min' => NULL,
									array('OR' => array(
														array('DemandInfo.is_contact_time_range_flg' => 0,
															  'DemandInfo.contact_desired_time >' => date ( 'Y-m-d H:i' )),
														array('DemandInfo.is_contact_time_range_flg' => 1,
															  'DemandInfo.contact_desired_time_from >' => date ( 'Y-m-d H:i' ))
													)
									)
								)
						)
				),
		);

// 2016.11.17 murata.s ORANGE-185 CHG(S)
//		$conditions['DemandInfo.selection_system'] = Util::getDivValue('selection_type', 'AuctionSelection');
		$conditions['DemandInfo.selection_system'] = array(
				Util::getDivValue('selection_type', 'AuctionSelection'),
				Util::getDivValue('selection_type', 'AutomaticAuctionSelection')
		);
// 2016.11.17 murata.s ORANGE-185 CHG(E)

		// ジャンルID
		if (!empty($data['genre_id'])) {
			$conditions['DemandInfo.genre_id'] = $data['genre_id'];
		}

		// 都道府県
		if (!empty($data['address1'])) {
			$conditions['DemandInfo.address1'] = $data['address1'];
		}

		// 表示案件
		if (!empty($data['display'])) {
			$conditions['CommissionInfo.id'] = '';
		}

		return $conditions;
	}

	/**
	 * 検索用Join句作成
	 *
	 * @return multitype:multitype:string multitype:string
	 */
	private function __setSearchJoin() {

		$conditions = array (
				"AuctionInfo.demand_id = DemandInfo.id",
		);
		$conditions['AuctionInfo.refusal_flg !='] = 1;
		//$conditions['AuctionInfo.responders'] = NULL;
		$conditions['AuctionInfo.push_flg'] = 1;

		// 加盟店でログイン時
		if($this->User["auth"] == "affiliation"){
			$conditions['AuctionInfo.corp_id'] = $this->User["affiliation_id"];
		}

		$joins = array (
				array (
						'fields' => '*',
						'type' => 'left',
						"table" => "m_sites",
						"alias" => "MSite",
						"conditions" => array (
								"MSite.id = DemandInfo.site_id"
						)
				),
				array (
						'fields' => '*',
						'type' => 'left',
						"table" => "m_genres",
						"alias" => "MGenre",
						"conditions" => array (
								"MGenre.id = DemandInfo.genre_id"
						)
				),
				array (
						'fields' => '*',
						'type' => 'inner',
						"table" => "auction_infos",
						"alias" => "AuctionInfo",
						"conditions" => $conditions,
				),
				//ORANGE-1027 訪問日時が範囲指定と時間指定の選択制になったため変更
				array (
						'fields' => '*',
						'type' => 'left',
						//"table" => '(SELECT demand_id, MIN(visit_time) as visit_time_min FROM visit_times GROUP BY demand_id)',
						"table" => '(SELECT demand_id, MIN(visit_time) as visit_time_min FROM (SELECT demand_id, case when is_visit_time_range_flg = 0 then visit_time else visit_time_from end as visit_time from visit_times) as A GROUP BY demand_id)',
						"alias" => "VisitTime",
						"conditions" => array (
								"VisitTime.demand_id = DemandInfo.id"
						)
				),
				array (
						'fields' => '*',
						'type' => 'left',
						'table' => '(SELECT demand_id, min(concat(case when is_visit_time_range_flg = 0 then visit_time else visit_time_from end, \'|\', is_visit_time_range_flg, \'|\', visit_time_to, \'|\', visit_adjust_time)) as prop from visit_times as A group by demand_id)',
						'alias' => 'VisitTimeProp',
						'conditions' => array(
							'VisitTimeProp.demand_id = DemandInfo.id'
						)
				),

				array (
						'fields' => '*',
						'type' => 'inner',
						"table" => "m_corps",
						"alias" => "MCorp",
						"conditions" => array (
								"MCorp.id = AuctionInfo.corp_id"
						)
				),
// 2016.09.29 murata.s ORANGE-192 ADD(S)
				array(
						'fields' => '*',
						'type' => 'left',
						'table' => 'select_genre_prefectures',
						'alias' => 'SelectGenrePrefecture',
						'conditions' => array(
								'SelectGenrePrefecture.genre_id = DemandInfo.genre_id',
								'SelectGenrePrefecture.prefecture_cd = DemandInfo.address1'
						)
				),
// 2016.09.29 murata.s ORANGE-192 ADD(E)
// 2016.10.27 murata.s ORANGE-226 ADD(S)
				array(
						'fields' => '*',
						'type' => 'left',
						'table' => 'commission_infos',
						'alias' => 'CommissionInfo',
						'conditions' => array(
								'CommissionInfo.demand_id = DemandInfo.id'
						)
				),
// 2016.10.27 murata.s ORANGE-226 ADD(E)
// murata.s ORANGE-483 CHG(S)
// 2017/6/16 inokuma ORANGE-32 ADD start
				array(
						'fields' => 'id',
						'type' => 'left',
						'table' => '(select max(id) as id, demand_id from demand_attached_files where delete_flag = false group by demand_id)',
						'alias' => 'DemandAttachedFiles',
						'conditions' => array(
								'DemandAttachedFiles.demand_id = DemandInfo.id',
						)
				)
// 2017/6/16 inokuma ORANGE-32 ADD end
// murata.s ORANGE-483 CHG(E)
		);
		return $joins;
	}

	/**
	 * 取次データの取得
	 * (基本情報・取次情報)
	 *
	 * @return unknown
	 */
	private function __get_auction_list($conditions = array() , $id = null){

		// アソシエーションを解除
		$this->DemandInfo->unbindModelAll(false);

		$joins = self::__setSearchJoin();

		$this->DemandInfo->virtualFields = array(
				'visit_time_min' => 'VisitTime.visit_time_min',
		);

		//	2015.11.02 h.hanaki CHG ORANGE-943 (CommissionInfo.commit_flg追加)
		//  2015.12.22 ORANGE-1027 VisitTimeProp.prop追加
// 2016.09.29 murata.s ORANGE-192 CHG(S)
// // 2016.07.11 murata.s ORANGE-1 CHG(S)
// // 		$fields = '*, MSite.site_name, MSite.site_url, MGenre.genre_name, VisitTime.visit_time_min, AuctionInfo.push_time, VisitTimeProp.prop';
// // 		$group = array('DemandInfo.id', 'MSite.site_name', 'MSite.site_url', 'MGenre.genre_name', 'VisitTime.visit_time_min, AuctionInfo.push_time, VisitTimeProp.prop');
// 		$fields = '*, MSite.site_name, MSite.site_url, MGenre.genre_name, VisitTime.visit_time_min, AuctionInfo.push_time, VisitTimeProp.prop, MGenre.auction_fee';
// 		$group = array('DemandInfo.id', 'MSite.site_name', 'MSite.site_url', 'MGenre.genre_name', 'VisitTime.visit_time_min, AuctionInfo.push_time, VisitTimeProp.prop, MGenre.auction_fee');
// // 2016.07.11 murata.s ORANGE-1 CHG(E)
		$fields = '*, MSite.site_name, MSite.site_url, MGenre.genre_name, VisitTime.visit_time_min, AuctionInfo.push_time, VisitTimeProp.prop, MGenre.auction_fee, SelectGenrePrefecture.auction_fee, DemandAttachedFiles.id';
		$group = array('DemandInfo.id', 'MSite.site_name', 'MSite.site_url', 'MGenre.genre_name', 'VisitTime.visit_time_min, AuctionInfo.push_time, VisitTimeProp.prop, MGenre.auction_fee, SelectGenrePrefecture.auction_fee, DemandAttachedFiles.id');
// 2016.09.29 murata.s ORANGE-192 CHG(E)
		if($this->User["auth"] == "affiliation"){
			//	2015.11.02 h.hanaki CHG ORANGE-943 (CommissionInfo.commit_flg追加)
// 2016.09.29 murata.s ORANGE-192 CHG(S)
// // 2016.07.11 murata.s ORANGE-1 CHG(S)
// 			//$fields = '*, MSite.site_name, MSite.site_url, MGenre.genre_name, AuctionInfo.id, VisitTime.visit_time_min, MCorp.auction_masking, VisitTimeProp.prop';
// 			//$group = array('DemandInfo.id', 'MSite.site_name', 'MSite.site_url', 'MGenre.genre_name', 'AuctionInfo.id', 'VisitTime.visit_time_min, MCorp.auction_masking, VisitTimeProp.prop');
// 			$fields = '*, MSite.site_name, MSite.site_url, MGenre.genre_name, AuctionInfo.id, VisitTime.visit_time_min, MCorp.auction_masking, VisitTimeProp.prop, MGenre.auction_fee';
// 			$group = array('DemandInfo.id', 'MSite.site_name', 'MSite.site_url', 'MGenre.genre_name', 'AuctionInfo.id', 'VisitTime.visit_time_min, MCorp.auction_masking, VisitTimeProp.prop, MGenre.auction_fee');
// // 2016.07.11 murata.s ORANGE-1 CHG(E)
			$fields = '*, MSite.site_name, MSite.site_url, MGenre.genre_name, AuctionInfo.id, VisitTime.visit_time_min, MCorp.auction_masking, VisitTimeProp.prop, MGenre.auction_fee, SelectGenrePrefecture.auction_fee, DemandAttachedFiles.id';
			$group = array('DemandInfo.id', 'MSite.site_name', 'MSite.site_url', 'MGenre.genre_name', 'AuctionInfo.id', 'VisitTime.visit_time_min, MCorp.auction_masking, VisitTimeProp.prop, MGenre.auction_fee, SelectGenrePrefecture.auction_fee, DemandAttachedFiles.id');
// 2016.09.29 murata.s ORANGE-192 CHG(E)
		}

		//$fields .= ', (SELECT ARRAY_TO_STRING(ARRAY( SELECT visit_time FROM visit_times WHERE visit_times.demand_id = "DemandInfo"."id" ORDER BY visit_times.visit_time ASC ),\'｜\') as "DemandInfo__visit_time" )';
		//$fields .= ', (SELECT ARRAY_TO_STRING(ARRAY( SELECT visit_time FROM visit_times WHERE visit_times.demand_id = "DemandInfo"."id" ORDER BY visit_times.visit_time ASC ),\'｜\')) as "visit_time" ';
		$fields .= ', (SELECT ARRAY_TO_STRING(ARRAY( SELECT visit_time FROM visit_times WHERE visit_times.demand_id = "DemandInfo"."id" ORDER BY visit_times.visit_time ASC ),\'｜\')) as "visit_time" ';

		$this->paginate = array(
			'conditions' => $conditions,
			'fields' => $fields,
			'joins' => $joins,
			'limit' => Configure::read('list_limit'),
			'group' => $group,
			'order' => array('DemandInfo.auction_deadline_time' => 'asc'),
		);

		$results = $this->paginate('DemandInfo');

		return $results;
	}

	/**
	 * 辞退理由の登録
	 */
	private function __editRefusal(){

		$resultsFlg = false;
		$edit_data = $this->request->data;
		try {
			// トランザクション開始
			$this->Refusal->begin();
			$this->AuctionInfo->begin();
			$resultsFlg = $this->Refusal->save($edit_data['Refusal']);
			if($resultsFlg){
				$resultsFlg = $this->AuctionInfo->save($edit_data['AuctionInfo']);
			}

			if($resultsFlg){
				$this->Refusal->commit();
				$this->AuctionInfo->commit();
			} else {
				$this->Refusal->rollback();
				$this->AuctionInfo->rollback();
			}

		} catch (Exception $e) {
			$this->Refusal->rollback();
			$this->AuctionInfo->rollback();
			$resultsFlg = false;
		}

		return $resultsFlg;
	}

	/**
	 * 手数料を取得(m_categoriesより取得)
	 *
	 * @param unknown $id
	 */
	private function __get_fee_data_m_categories($category_id){

		$results = array();

		if (empty($category_id)) {
			return $results;
		}

		$results = $this->MCategory->find ( 'first', array (
						'conditions' => array ('MCategory.id ='.$category_id),
						'fields' => 'MCategory.category_default_fee, MCategory.category_default_fee_unit'
		) );

		return $results;
	}

	/**
	 * カレンダーに表示するイベント表示用のデータを取得(ORANGE-1223)
	 * @param $auction_already_data
	 * @return array
	 */
	private function __get_calender_event_data($auction_already_data){
		$results = array();

		App::uses('CommonHelper', 'Lib/View/Helper');
		$CommonHelper = new CommonHelper(new View());

		$visit_hope_str = '訪問希望日時：';
		$tell_hope_str = '電話希望日時：';
		$orders_correspondence_str = '受注対応日時：';

		for($i =0; $i < count($auction_already_data); $i++) {
			$data_array = array();
			$data_array['display_date'] = null; // カレンダーに表示する日にち
			$auction_info = $auction_already_data[$i]['AuctionInfo'];
			$commission_info = $auction_already_data[$i]['CommissionInfo'];
			$demand_info = $auction_already_data[$i]['DemandInfo'];
			$visit_time = $auction_already_data[$i]['VisitTime'];
			$m_site = $auction_already_data[$i]['MSite'];

			// ■条件1
			//・訪問希望日時(取次管理で入力)が登録されていない場合、
			//  カレンダーには、連絡期限日時(時間指定)を表示する。
			//・訪問希望日時(取次管理で入力)が登録された場合、
			//  カレンダーには、訪問希望日時(取次管理で入力)を表示する。
			//  連絡期限日時は、表示しない。
			if($demand_info['is_contact_time_range_flg'] == 0 &&
				isset($demand_info['contact_desired_time'])) {

				if(isset($commission_info['visit_desired_time'])) {
					$data_array['display_date'] = $commission_info['visit_desired_time'];
					$data_array['dialog_display_date'] = $visit_hope_str . $CommonHelper->dateTimeWeek($commission_info['visit_desired_time']);
				}else{
					$data_array['display_date'] = $demand_info['contact_desired_time'];
					$data_array['dialog_display_date'] = $tell_hope_str . $CommonHelper->dateTimeWeek($demand_info['contact_desired_time']);
				}
			}
			// ■条件2
			//・訪問希望日時(取次管理で入力)が登録されていない場合、
			//  カレンダーには、連絡期限日時(From)を表示する。
			//・訪問希望日時(取次管理で入力)が登録された場合、
			//  カレンダーには、訪問希望日時(取次管理で入力)を表示する。
			//  連絡期限日時は、表示しない。
			else if($demand_info['is_contact_time_range_flg'] == 1 &&
				isset($demand_info['contact_desired_time_from']) &&
				isset($demand_info['contact_desired_time_to'])) {

				if(isset($commission_info['visit_desired_time'])) {
					$data_array['display_date'] = $commission_info['visit_desired_time'];
					$data_array['dialog_display_date'] = $visit_hope_str . $CommonHelper->dateTimeWeek($commission_info['visit_desired_time']);
				}else{
					$data_array['display_date'] = $demand_info['contact_desired_time_from'];
					$data_array['dialog_display_date'] = $tell_hope_str . $CommonHelper->dateTimeWeek($demand_info['contact_desired_time_from']) . "〜" . $CommonHelper->dateTimeWeek($demand_info['contact_desired_time_to']);
				}
			}
			// ■条件3
			//・訪問希望日時(案件管理で入力)が登録されている場合、
			//  カレンダーには、訪問希望日時(時間指定)を表示する。
			else if($visit_time['is_visit_time_range_flg'] == 0 &&
				isset($visit_time['visit_time'])) {

				$data_array['display_date'] = $visit_time['visit_time'];
				$data_array['dialog_display_date'] = $visit_hope_str . $CommonHelper->dateTimeWeek($visit_time['visit_time']);
			}
			// ■条件4
			//・訪問希望日時(取次管理で入力)が登録されていない場合、
			//  カレンダーには、訪問日時要調整時間(案件管理で入力)を表示する。
			//  訪問希望日時(案件管理で入力している要時間調整)は、表示しない。
			//・訪問希望日時(取次管理で入力)が登録されている場合、
			//  カレンダーには、訪問希望日時(取次管理で入力)を表示する。
			//  訪問日時要調整時間は、表示しない。
			else if($visit_time['is_visit_time_range_flg'] == 1 &&
				isset($visit_time['visit_time_from']) ) {

				if(isset($commission_info['visit_desired_time'])) {
					$data_array['display_date'] = $commission_info['visit_desired_time'];
					$data_array['dialog_display_date'] = $visit_hope_str . $CommonHelper->dateTimeWeek($commission_info['visit_desired_time']);
				}else{
					$data_array['display_date'] = $visit_time['visit_adjust_time'];
					$data_array['dialog_display_date'] = $tell_hope_str . $CommonHelper->dateTimeWeek($visit_time['visit_adjust_time']);
				}
			}

			// ■条件5
			//・受注対応日時がある場合はその日時を表示する
			if(isset($commission_info['order_respond_datetime'])) {
				$data_array['display_date'] = $commission_info['order_respond_datetime'];
				$data_array['dialog_display_date'] = $orders_correspondence_str . $CommonHelper->dateTimeWeek($commission_info['order_respond_datetime']);
			}

			$data_array['commission_id'] = $commission_info['id'];
			$data_array['demand_id'] = $auction_info['demand_id'];
			$data_array['customer_name'] = $demand_info['customer_name']; // お客様名
			$data_array['site_name'] = $m_site['site_name']; // サイト名

			// ダイアログに表示する用の日時からソート用に時刻部分だけ取得
			// 「From」「To」がある場合はFromの方の時刻を取得する
			if(isset($data_array['dialog_display_date']) && strstr($data_array['dialog_display_date'], '〜')) {
				$from_date = mb_substr($data_array['dialog_display_date'], 0, 25);
				$data_array['sort_date'] = (isset($from_date ) ? substr($from_date ,-5) : '');
			}else{
				$data_array['sort_date'] = (isset($data_array['dialog_display_date']) ? substr($data_array['dialog_display_date'] ,-5) : '');
			}

			$display_date_split_arr = preg_split('/[\s]+/', $data_array['display_date'], -1, PREG_SPLIT_NO_EMPTY);


			// 2017.08.15 e.takeuchi@SharingTechnology ORANGE-489 ADD(S)
			if(empty($display_date_split_arr[0])){
				$display_date_split_arr = array();
				$display_date_split_arr[0] = 'dumm';
			}
			// 2017.08.15 e.takeuchi@SharingTechnology ORANGE-489 ADD(E)

			$key = $display_date_split_arr[0]; // 日にち部分のみ取得(yyyy/mm/dd形式)
			if(isset($results[$key])) {
				array_push($results[$key], $data_array);
			}else{
				$results[$key] = array($data_array);
			}
		}

		// 同日の場合は表示時刻の昇順でソート
		$sort_result = array();
		foreach($results as $result_key => $value_arr) {
			if (count($value_arr) == 0) {
				$sort_result[$result_key] = $value_arr;
				continue;
			}

			$key_sort_date = array();
			$key_demand_id = array();
			foreach ($value_arr as $key => $value) {
				$key_sort_date[$key] = $value['sort_date'];
				$key_demand_id[$key] = $value['demand_id'];
			}
			array_multisort($key_sort_date, SORT_ASC, $key_demand_id, SORT_ASC, $value_arr);

			$sort_result[$result_key] = $value_arr;
		}

		return $sort_result;
	}

// murata.s ORANGE-261 ADD(S)
	/**
	 * 請求情報を作成する
	 * @param array $commission_info 取次情報
	 */
	private function __editBillInfo($commission_info){

		// 消費税率の取得
		$m_tax = $this->__getTaxRate();

		// 請求情報の取得
		$this->BillInfo->create();
		$save_data = array();
		$save_data['BillInfo']['commission_id'] = $commission_info['CommissionInfo']['id'];
		$save_data['BillInfo']['demand_id'] = $commission_info['CommissionInfo']['demand_id'];
		$save_data['BillInfo']['bill_status'] = Util::getDivValue ( 'bill_status', 'not_issue' );
		$save_data['BillInfo']['comfirmed_fee_rate'] = 100;
		$save_data['BillInfo']['fee_target_price'] = $commission_info['CommissionInfo']['corp_fee'];
		$save_data['BillInfo']['fee_tax_exclude'] = ($save_data['BillInfo']['comfirmed_fee_rate'] / 100) * $commission_info['CommissionInfo']['corp_fee'];
		$save_data['BillInfo']['tax'] = floor($save_data['BillInfo']['fee_tax_exclude'] * $m_tax['MTaxRate']['tax_rate']);
		$save_data['BillInfo']['total_bill_price'] = $save_data['BillInfo']['fee_tax_exclude'] + $save_data['BillInfo']['tax'];
		$save_data['BillInfo']['fee_payment_price'] = 0;
		$save_data['BillInfo']['fee_payment_balance'] = $save_data['BillInfo']['fee_tax_exclude'] + $save_data['BillInfo']['tax'];

		return $this->BillInfo->save($save_data, false);
	}

	/**
	 * 消費税率の取得
	 */
	private function __getTaxRate(){

		return $this->MTaxRate->find('first', array(
				'conditions' => array(
						'start_date <=' => date('Y-m-d H:i:s'),
						'or' => array(
								"end_date = ''" ,
								'end_date >=' => date('Y-m-d H:i:s'))
				)
		));
	}


// murata.s ORANGE-261 ADD(E)
        
    /*
     * 2017/08/03  ichino ORANGE-456 ADD start
     * 辞退日時の更新
     */
    private function _update_accumulated_info_refusal_date($demand_id, $corp_id){
        if(empty($demand_id) || empty($corp_id)){
            //処理を行わず戻る
            return;
        }
        
        try{
            //更新対象を取得
            $result = $this->AccumulatedInformation->getInfos($corp_id, $demand_id);
            //IDを取得
            $info_id = Hash::get($result, '0.AccumulatedInformation.id');
            
            if(!empty($info_id)){
                //更新内容を作成
                $save_data['AccumulatedInformation'] = array(
                    'id' => $info_id,    //ID
                    'refusal_date' => date('Y-m-d H:i'),    //辞退登録日時
                    'modified_user_id' => $corp_id,    //更新者
                );
                
                //AppModelのBeforeSaveでcreated_user_id, modified_user_idを空文字で上書きしてしまうためコールバックを無効にする
                $this->AccumulatedInformation->save($save_data, array(
                    'validate' => false,
                    'callbacks' => false
		));
            }
        } catch (Exception $e) {
            $this->log('AccumulatedInformation 保存エラー' . $e->getMessage());
        }
        
        return;
    }
    //2017/08/03  ichino ORANGE-456 ADD end

    //2017/08/22  kawamoto ORANGE-456 ADD start
    /*
     * 蓄積情報に入札日時を登録
     */
    private function _update_accumulated_info_regist_date($demand_id, $corp_id){

	    //蓄積情報の入札登録日時を更新する
	    //他の入札対象企業の辞退登録日時を更新する
	    
	    //更新対象を取得
	    $result = $this->AccumulatedInformation->getInfos(null, $demand_id);
	    if(!empty($result)){
	        //更新内容を作成
	        $accumulated_info_save_data = array();
	        foreach ($result as $key => $value) {
	            if($value['AccumulatedInformation']['corp_id'] === $corp_id){
	                //入札した加盟店
	                $accumulated_info_save_data['AccumulatedInformation'][$key]['id'] = $value['AccumulatedInformation']['id'];    //ID
	                $accumulated_info_save_data['AccumulatedInformation'][$key]['bid_regist_date'] = date('Y-m-d H:i');    //入札登録日時
	                $accumulated_info_save_data['AccumulatedInformation'][$key]['modified_user_id'] = $this->User['affiliation_id'];    //更新者
	            } else {
	                //入札した加盟店以外
	                $accumulated_info_save_data['AccumulatedInformation'][$key]['id'] = $value['AccumulatedInformation']['id'];    //ID
	                $accumulated_info_save_data['AccumulatedInformation'][$key]['refusal_date'] = date('Y-m-d H:i');    //辞退登録日時
	                $accumulated_info_save_data['AccumulatedInformation'][$key]['modified_user_id'] = 'SYSTEM';    //更新者
	            }
	        }
	        
	        //保存
	        if(!empty($accumulated_info_save_data)){
	            // 更新
	            //AppModelのBeforeSaveでcreated_user_id, modified_user_idを空文字で上書きしてしまうためコールバックを無効にする
	            $this->AccumulatedInformation->saveAll($accumulated_info_save_data['AccumulatedInformation'], array(
	                        'validate' => false,
	                        'callbacks' => false
	            ));
	        }
	        
	    }
	}
    //2017/08/22  kawamoto ORANGE-456 ADD end

}
