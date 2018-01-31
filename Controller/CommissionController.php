<?php
App::uses('AppController', 'Controller');
App::uses('CakeEmail', 'Network/Email');
//for debug
App::uses('AppShell', 'Console/Command');
App::uses('CommissionAlertShell', 'Console/Command');
//

class CommissionController extends AppController {

	public $name = 'Commission';
// 2017.02.20 murata.s ORANGE-328 CHG(S)
	public $helpers = array('Csv', 'NotCorrespond');
// 2017.02.20 murata.s ORANGE-328 CHG(E)
// 2017.01.04 murata.s ORANGE-244 CHG(S)
	//ORANGE-161 iwai CHG S
	public $components = array('PDF','Session', 'DemandUtil', 'Csv', 'BillPriceUtil');
	//ORANGE-161 iwai CHG S
// 2017.01.04 murata.s ORANGE-244 CHG(E)

//ORANGE-13 chg
	public $uses = array(
		'DemandInfo','DemandInquiryAnswer', 'CommissionInfo','MCorp', 'MUser',
		'CommissionCorrespond', 'BillInfo', 'MCorpCategory', 'MTaxRate', 'MCategory',
		'MGenre', 'AffiliationInfo', 'MSite', 'DemandCorrespond', 'MTime',
		'AuctionInfo', 'MCorpTargetArea', 'VisitTime', 'CommissionOrderSupport',
		'CommissionTelSupport', 'CommissionVisitSupport','MCommissionAlertSetting',
		'CommissionApplication', 'Approval', 'DemandAttachedFiles'
	);
//ORANGE-13 chg
	public function beforeFilter(){
		parent::beforeFilter();
		$this->set('default_display', false);
		$this->User = $this->Auth->user();

//		// 2015.04.29 h.hara ADD start
//		$this->CommissionInfo->unbindModel(
//			array(
//				'belongsTo' => array('DemandInfo')
//			),false
//		);
//		// 2015.04.29 h.hara ADD end
	}

	// for debug
	/*
	public function goShell() {
		$this->autoRender = false;

		try {
		$shell = new CommissionAlertShell();
		$shell->startup();
		$shell->main();

		} catch (Exception $e) {
			echo $e->getMessage();
		}

		return null;
	}
	*/
	//

	/**
	 * 初期表示（一覧）
	 *
	 * @param string $affiliation_id
	 */
	public function index($affiliation_id = null) {
		if(!empty($affiliation_id)){
			self::__updata_affiliation_id($affiliation_id);
		}

		//todo 追加施工案件入力
		if (isset($this->request->data['Input'])){
			return $this->redirect('/addition/');
		}

		// 追加施工案件確認
		else if (isset($this->request->data['Confirmation'])){
			return $this->redirect('/report/addition');
		}
		// 帳票出力ボタン
		else if (isset($this->request->data['Output']) && !empty($this->request->data['checkbox'])){
			self::__pdf_commission();
		}
		else {
			if (isset($this->request->data['Output'])){
				$this->Session->setFlash(__('NoCheck', true), 'default', array('class' => 'error_inner'));
			}
			try {
				if ($this->request->is('Post') || $this->request->is('put')) {
					$data = self::__commissionListPost();
					self::__updata_affiliation_id($affiliation_id);
					//ORANGE-161 ADD S
					$this->set(array('default_display' => true));
					//ORANGE_161 ADD E
				} else {
					$data = self::__commissionListGet();
				}
			} catch (Exception $e) {
				throw $e;
			}

			if(empty($affiliation_id)){
				$affiliation_id = self::__get_affiliation_id();
				self::__set_parameter_session();
			}

			if ($this->User['auth'] == 'affiliation')
				$this->set ( "not_enough", $this->__doNotHavaEnoughData($this->User['affiliation_id']));

			$conditions = self::__setSearchConditions($data , $affiliation_id);

			//ORANGE-161 iwai CHG S
			if ($this->request->is('Post') || $this->request->is('put')) {
				// CSV出力ボタン
				if (isset($this->request->data['csv_out']) && ($this->User["auth"] == 'system' || $this->User["auth"] == 'admin'|| $this->User['auth'] == 'accounting_admin')) {
					$data_list = $this->DemandUtil->getDataCsv($conditions, 'Commission');
					$file_name = mb_convert_encoding(__('取次管理', true) . '_' . $this->User['user_id'], 'SJIS-win', 'UTF-8');
					$this->Csv->download('DemandInfo', 'default', $file_name, $data_list);
					return;
				}
			}
			//ORANGE-161 iwai CHG E

			// ORANGE-46 iwai 2016.6.6 S
			if($this->User['auth'] != 'affiliation' && $this->request->is('Get') && !empty($this->params['named']['none']) && $this->params['named']['none'])
				$commission_data = array();
			else $commission_data = self::__get_commission_list($conditions , $affiliation_id);
			// ORANGE-46 iwai 2016.6.6 E

			// 住所開示時間リスト取得
			$address_disclosure = self::__getDisclosureData('address_disclosure');
			// 電話番号開示時間リスト取得
			$tel_disclosure = self::__getDisclosureData('tel_disclosure');

			// カレンダーに表示するイベントデータを取得
			$calender_event_data = self::__get_calender_event_data($commission_data);
			$this->set("calender_event_data" , $calender_event_data);

			$this->set("results" , $commission_data);
			$this->set("affiliation_id" , $affiliation_id);
			$this->set("site_list" , $this->MSite->getList());
			$this->set("address_disclosure" , $address_disclosure);	// 住所開示時間
			$this->set("tel_disclosure" , $tel_disclosure);			// 電話番号開示時間


		}

		// ユーザーが加盟店の場合
		if ($this->User['auth'] == 'affiliation') {
			// 基本情報更新日時取得
			$corp_last_upday = $this->MCorp->find('first', array(
					'fields' => 'MCorp.modified',
					'conditions' => array(
							'MCorp.id = '. $this->User['affiliation_id']
					),
			));
			$this->set('corp_last_upday', $corp_last_upday['MCorp']['modified']);

			// 対応可能ジャンル更新日時取得
			$genre_last_upday = $this->MCorpCategory->find('first', array(
					'fields' => 'MCorpCategory.modified',
					'conditions' => array(
							'MCorpCategory.corp_id = '. $this->User['affiliation_id']
					),
					'order' => array (
							'MCorpCategory.modified' => 'desc'
					),
			));
			$this->set('genre_last_upday', $genre_last_upday['MCorpCategory']['modified']);

			// 基本対応エリア更新日時取得
			$corparea_last_upday = $this->MCorpTargetArea->find('first', array(
					'fields' => 'MCorpTargetArea.modified',
					'conditions' => array(
							'MCorpTargetArea.corp_id = '. $this->User['affiliation_id']
					),
					'order' => array (
							'MCorpTargetArea.modified' => 'desc'
					),
			));
			$this->set('corparea_last_upday', $corparea_last_upday['MCorpTargetArea']['modified']);
		}


		if ($this->User['auth'] == 'affiliation') {
		    $this->set ( "not_enough", $this->__doNotHavaEnoughData($this->User['affiliation_id']));
		}

		$this->set ( "support_cnt", 0);

		// モバイル端末用View判定 2015.05.10 y.tanaka
		if(Util::isMobile()  && $this->User['auth'] == 'affiliation') {
			$this->layout = 'default_m';
			$this->render('index_m');
		} else {
			$this->render('index');
		}

		// ORANGE-46 iwai 2016.6.6 S
		if ($this->User['auth'] == 'affiliation'){
	 		// 問題あり案件の抽出用
	 		$conditions = self::__setSearchConditions(array("1"));
	 		$commission_data = self::__get_commission_list($conditions , null);
	 		$support_cnt = 0;
	 		foreach ($commission_data as $data)
	 			$support_cnt = ($data['CommissionInfo']['status'] == 1) ? $support_cnt + 1 : $support_cnt;
	 		$this->set ( "support_cnt", $support_cnt);
			// 加盟店の必須項目チェック

 		//if ($this->User['auth'] == 'affiliation')
			$this->set ( "not_enough", $this->__doNotHavaEnoughData($this->User['affiliation_id']));
		}
		// ORANGE-46 iwai 2016.6.6 E
	}

	/**
	 * 初期表示（一覧ボタンクリック後）
	 *
	 * @param string $affiliation_id
	 * @throws Exception
	 */
	public function search($affiliation_id = null) {

		if(!empty($affiliation_id)){
			self::__updata_affiliation_id($affiliation_id);
		}

		//todo 追加施工案件入力
		if (isset($this->request->data['Input'])){
			return $this->redirect('/addition/');
			return;
		}

		// 追加施工案件確認
		else if (isset($this->request->data['Confirmation'])){
			return $this->redirect('/report/addition');
			return;
		}
		// 帳票出力ボタン
		else if (isset($this->request->data['Output']) && !empty($this->request->data['checkbox'])){
			self::__pdf_commission();
		}
		else {
			if (isset($this->request->data['Output'])){
				$this->Session->setFlash(__('NoCheck', true), 'default', array('class' => 'error_inner'));
			}
			try {
				if ($this->request->is('Post') || $this->request->is('put')) {
					$data = self::__commissionListPost();
					self::__updata_affiliation_id($affiliation_id);
				} else {
					$data = self::__commissionListGet();
				}
			} catch (Exception $e) {
				throw $e;
			}

			if(empty($affiliation_id)){
				$affiliation_id = self::__get_affiliation_id();
				self::__set_parameter_session();
			}
			if ($this->User['auth'] == 'affiliation')
				$this->set ( "not_enough", $this->__doNotHavaEnoughData($this->User['affiliation_id']));

			$conditions = self::__setSearchConditions($data , $affiliation_id);
			$commission_data = self::__get_commission_list($conditions , $affiliation_id);

			// 住所開示時間リスト取得
			$address_disclosure = self::__getDisclosureData('address_disclosure');
			// 電話番号開示時間リスト取得
			$tel_disclosure = self::__getDisclosureData('tel_disclosure');

			// カレンダーに表示するイベントデータを取得
			$calender_event_data = self::__get_calender_event_data($commission_data);
			$this->set("calender_event_data" , $calender_event_data);

			$this->set("results" , $commission_data);
			$this->set("affiliation_id" , $affiliation_id);
			$this->set("site_list" , $this->MSite->getList());
			$this->set("address_disclosure" , $address_disclosure);	// 住所開示時間
			$this->set("tel_disclosure" , $tel_disclosure);			// 電話番号開示時間

		}

		// ユーザーが加盟店の場合
		if ($this->User['auth'] == 'affiliation') {
			// 基本情報更新日時取得
			$corp_last_upday = $this->MCorp->find('first', array(
					'fields' => 'MCorp.modified',
					'conditions' => array(
							'MCorp.id = '. $this->User['affiliation_id']
					),
			));
			$this->set('corp_last_upday', $corp_last_upday['MCorp']['modified']);

			// 対応可能ジャンル更新日時取得
			$genre_last_upday = $this->MCorpCategory->find('first', array(
					'fields' => 'MCorpCategory.modified',
					'conditions' => array(
							'MCorpCategory.corp_id = '. $this->User['affiliation_id']
					),
					'order' => array (
							'MCorpCategory.modified' => 'desc'
					),
			));
			$this->set('genre_last_upday', $genre_last_upday['MCorpCategory']['modified']);

			// 基本対応エリア更新日時取得
			$corparea_last_upday = $this->MCorpTargetArea->find('first', array(
					'fields' => 'MCorpTargetArea.modified',
					'conditions' => array(
							'MCorpTargetArea.corp_id = '. $this->User['affiliation_id']
					),
					'order' => array (
							'MCorpTargetArea.modified' => 'desc'
					),
			));
			$this->set('corparea_last_upday', $corparea_last_upday['MCorpTargetArea']['modified']);
		}


        if ($this->User['auth'] == 'affiliation') {
            $this->set ( "not_enough", $this->__doNotHavaEnoughData($this->User['affiliation_id']));
	}

        $this->set ( "support_cnt", 0);

		// モバイル端末用View判定 2015.05.10 y.tanaka
		if(Util::isMobile()  && $this->User['auth'] == 'affiliation') {
			$this->layout = 'default_m';
			$this->render('index_m');
		} else {
			$this->render('index');
		}



	}

	/**
	 * 詳細画面
	 *
	 * @param string $id
	 */
	public function detail($id = null) {

		$error['falsity'] = '';
		$again_enabled = false;		//再表示ボタン活性制御

		// 取次IDが無い場合
		if(empty($id)) {
			return $this->redirect('/commission');
		}

		// 2015.07.05 n.kai ADD start スマホアプリ未読フラグOFF
		self::__sp_detail_read($id);
		// 2015.07.05 n.kai ADD end

		// 取次詳細データの取得
		$commission_data = self::__set_commission($id);
		// 訪問時間テーブルからデータを取得する

		// 取次詳細データがない場合
		if(empty($commission_data)){
			throw new ApplicationException(__('NoReferenceAuthority', true));
		}

		// 2016.12.26 murata.s ADD(S)
		// 加盟店ログインのみ
		if($this->User["auth"] == "affiliation"){
			if($commission_data['CommissionInfo']['lost_flg'] != 0
					|| $commission_data['CommissionInfo']['del_flg'] != 0
					|| $commission_data['CommissionInfo']['introduction_not'] != 0){
				throw new ApplicationException(__('NoReferenceAuthority', true));
			}
		}
		// 2016.12.26 murata.s ADD(E)

		// 消費税率取得
		$tax_rate = self::__set_m_tax_rates($commission_data['CommissionInfo']['complete_date']);

		// 登録ボタン
		if (isset($this->request->data['regist'])){

			$data = $this->request->data;

			//登録ボタンを押す、エラーメッセージ出た時、消費税率取得
			$tax_rate = self::__set_m_tax_rates($data['CommissionInfo']['complete_date']);

			// [JBR]見積書、領収書ファイルの一時アップロード
			if (!empty($this->request->data['DemandInfo']['jbr_estimate']['name'])) {
				$data['DemandInfo']['tmp_estimate_file'] = $this->__uplaod_file_tmp($this->request->data['DemandInfo']['jbr_estimate']['tmp_name']);
			}
			if (!empty($this->request->data['DemandInfo']['jbr_receipt']['name'])) {
				$data['DemandInfo']['tmp_receipt_file'] = $this->__uplaod_file_tmp($this->request->data['DemandInfo']['jbr_receipt']['tmp_name']);
			}

			if (!empty($this->request->data['DemandInfo']['upload_file_name']['name'])) {

				//$data['send'] == '1';

				$data['DemandInfo']['tmp_upload_file_name'] = $this->__uplaod_file_tmp($this->request->data['DemandInfo']['upload_file_name']['tmp_name']);
			}
			// 虚偽報告なし チェック
			//if(self::__falsity_check()){
				// 更新日付のチェック
				if(self::__check_modified_commission($id , $this->request->data['modified'])){

					try {

						$this->CommissionInfo->begin();
						$this->DemandInfo->begin();
						$this->CommissionCorrespond->begin();
						$this->BillInfo->begin();

						//ORANGE-234 ADD S
						$correspond = $this->CommissionInfo->get_correspond($id, $this->request->data);
						//ORANGE-234 ADD E

						// 取次情報の編集
						$resultsFlg = self::__edit_commission($id , $this->request->data);

						if ($resultsFlg){
							// 案件情報の編集
							$resultsFlg = self::__edit_demand($this->request->data);
						}

						// 2015-3-3 inokuchi 処理変更の為削除
// 						if ($resultsFlg){
// 							// 加盟店でログイン時、確認済フラグを更新
// 							if($this->User["auth"] == "affiliation"){
// 								$this->CommissionInfo->id = $id;
// 								$this->CommissionInfo->saveField('reported_flg', 1);
// 								$commission_data = self::__set_commission($id);
// 							}
// 						}

						if ($resultsFlg && isset($this->request->data['CommissionCorrespond'])){
								// スマホ版は「CommissionCorrespond」がない為、あれば登録処理を行うように変更
							// 対応履歴を登録
							$resultsFlg = self::__regist_history($id , $this->request->data['CommissionCorrespond']);
						}

						//ORANGE-234 ADD S
						if ($resultsFlg && !empty($correspond)){
							$this->CommissionCorrespond->create();
							// 2017.01.04 ORANGE-244 CHG(S)
							// スマホ版は「CommissionCorrespond」がなくwarningが発生するため、
							// ない場合はarrayを新規作成して使用
							$cd = isset($this->request->data['CommissionCorrespond']) ? $this->request->data['CommissionCorrespond'] : array();
							// 2017.01.04 ORANGE-244 CHG(E)
							$cd['corresponding_contens'] = $correspond;
							$cd['responders'] = '自動登録['.$this->User["user_name"].']';
							$cd['rits_responders'] = null;
							$cd['commission_id'] = $id;
							$cd['created_user_id'] = 'system';
							$cd['modified_user_id'] = 'system';
							$cd['correspond_datetime'] = date('Y-m-d H:i:s');

							$this->CommissionCorrespond->save($cd,
									array('validate' => false, 'callbacks' => false));

						}
						//ORANGE_234 ADD E

						if ($resultsFlg){
							// 請求情報の登録
							if($this->request->data['CommissionInfo']['commission_status'] == Util::getDivValue('construction_status','construction') || $this->request->data['CommissionInfo']['commission_status'] == Util::getDivValue('construction_status','introduction')){
								// 2015.6.18 n.kai MOD start ORANGE-596,ORANGE-607 リロ・クレカ案件も請求生成(請求管理での抽出時リロ･クレカ案件は対象外にする)
								// リロ・クレカ案件のチェック
								//if(empty($this->request->data['DemandInfo']['riro_kureka'])){
									$resultsFlg = self::__regist_bill_info($id , $this->request->data);
								//} else {
								//	if(!empty($this->request->data['BillInfo']['id'])){
								//		$resultsFlg = self::__delete_bill_info($this->request->data['BillInfo']['id']);
								//	}
								//}
								// 2015.6.18 n.kai MOD end
							}

						}

						// 2015.04.24 h.hara
						if ($resultsFlg){
							if ($this->request->data['CommissionInfo']['commission_status'] == Util::getDivValue('construction_status','construction')) {
								// 施工完了の場合、他の取次先の取次単価対象外フラグを更新する
								$resultsFlg = self::__set_other_commission($id , $this->request->data['CommissionInfo']['demand_id']);
							}
						}

						if ($resultsFlg){
							// [JBR]見積書、領収書ファイルのアップロード
							$resultsFlg = self::__uplaod_file($data);
						}

						if ($resultsFlg) {
							$resultsFlg = self::__update_upload_file_name($data);
						}

						self::send($data, $id);

						if ($resultsFlg){  // エラーが無い場合

							$message = __('Updated', true);
							if($this->request->data['CommissionInfo']['commission_status'] == Util::getDivValue('construction_status','received')){
								$message .= '<br>'.__('ReceivedMessage', true);
							}
							$this->Session->setFlash($message, 'default', array('class' => 'message_inner alert alert-info'));
							$this->CommissionInfo->commit();
							$this->DemandInfo->commit();
							$this->CommissionCorrespond->commit();
							$this->BillInfo->commit();
							$commission_data = self::__set_commission($id);
							$this->data = $commission_data;

						} else {    // エラーが有り場合
							$this->CommissionInfo->rollback();
							$this->DemandInfo->rollback();
							$this->CommissionCorrespond->rollback();
							$this->BillInfo->rollback();
						}
					} catch (Exception $e) {
						$this->CommissionInfo->rollback();
						$this->DemandInfo->rollback();
						$this->CommissionCorrespond->rollback();
						$this->BillInfo->rollback();
					}

					if(!empty($this->CommissionInfo->validationErrors) || !empty($this->CommissionCorrespond->validationErrors) || !empty($this->DemandInfo->validationErrors) || !empty($this->BillInfo->validationErrors)){
						$this->data = $data;
						$this->Session->setFlash(__('InputError', true), 'default', array('class' => 'error_inner alert alert-danger'));
					}
				} else {
					$this->data = $data;
					$this->Session->setFlash(__('ModifiedNotCheck', true), 'default', array('class' => 'error_inner alert alert-danger'));

					$again_enabled = true;
				}
			$this->set('again_enabled', $again_enabled);	//再表示ボタン活性制御
//h.hanaki		$commission_data = self::__set_commission($id);
//h.hanaki		$this->data = $commission_data;
// 			} else {
// 				$this->data = $data;
// 				$error['falsity'] = __('FalsityNotCheck', true);
// 				$this->Session->setFlash(__('InputError', true), 'default', array('class' => 'error_inner'));
// 			}
		} else if (isset($this->request->data['regist-ret'])) {
			//差戻処理 ORANGE-560
			$data = $this->request->data;

			// 2015.6.19 n.kai MOD start 正式企業名出力へ変更
			//$data['MCorp']['corp_name'] = $commission_data['MCorp']['corp_name'];
			$data['MCorp']['official_corp_name'] = $commission_data['MCorp']['official_corp_name'];
			// 2015.6.19 n.kai MOD end

			$result = self::__demand_send_back($id, $data);
			if ($result) {
				$message = __('Updated', true);
				$this->Session->setFlash($message, 'default', array('class' => 'message_inner alert alert-info'));

				//差戻処理完了後はindexへ遷移
				return $this->redirect('/commission');
			} else {
			}
			//$commission_data = self::__set_commission($id);
			//$this->data = $commission_data;
		} else {
			// 加盟店でログイン時、確認済フラグを更新
			if($this->User["auth"] == "affiliation"){
				if(empty($commission_data['CommissionInfo']['checked_flg'])){
					$this->CommissionInfo->id = $id;
					$this->CommissionInfo->saveField('checked_flg', Util::getDivValue('checked_flg', 'sumi'));
					$commission_data = self::__set_commission($id);
				}
				$commission_data['CommissionInfo']['reported_flg'] = 0;
			}

// 2017.01.04 murata.s ORANGE-244 ADD(S)
			// 初期表示時に請求情報を再計算する(View側で行っていたval_check()をController側へ移動)
			$commission_data = $this->__price_calc($commission_data);
// 2017.01.04 murata.s ORANGE-244 ADD(E)

			//差戻処理完了後はindexに戻す
			// 値をセット
			$this->data = $commission_data;
		}
		// 見積書・領収書ファイル
		self::__set_file_url($commission_data['DemandInfo']['id']);

		// 取次先対応履歴データの取得
		$history_data = self::__set_commission_corresponds($id);
		// ヒヤリング項目の取得
		$inquiry_data = self::__set_inquiry_data($commission_data['DemandInfo']['id']);
                
                //2017-06-23 inokuma ORANGE-32 ADD start
                //案件に紐付くファイルを取得
                $demand_attached_files = self::__set_demand_attached_files($commission_data['DemandInfo']['id']);
                //2017-06-23 inokuma ORANGE-32 ADD stop
                
		// JBR初期値設定 2015.06.08 ORANGE-522 tanaka
		$commission_data = $this->__setDefaultJbrinfo($commission_data);

		// 状況データ追加
		$commission_data = $this->__setSupport($id, $commission_data);

		// 住所開示時間リスト取得
		$address_disclosure = self::__getDisclosureData('address_disclosure');
		// 電話番号開示時間リスト取得
		$tel_disclosure = self::__getDisclosureData('tel_disclosure');

		// m_commission_alert_settingsのtelのデータを取得
		if (isset($commission_data['CommissionTelSupport']['correspond_status'])) {
			$m_commission_alert_settings_tel = self::__mCommissionAlertSettingsTel($commission_data['CommissionTelSupport']['correspond_status']);
		}

		// m_commission_alert_settingsのvisitのデータを取得
		if (isset($commission_data['CommissionVisitSupport']['correspond_status'])) {
			$m_commission_alert_settings_visit = self::__mCommissionAlertSettingsVisit($commission_data['CommissionVisitSupport']['correspond_status']);
		}

		// m_commission_alert_settingsのorderのデータを取得
		if (isset($commission_data['CommissionOrderSupport']['correspond_status'])) {
			$m_commission_alert_settings_order = self::__mCommissionAlertSettingsOrder($commission_data['CommissionOrderSupport']['correspond_status']);
		}

		//tel 業者後追い時間
		$date_tel = new DateTime($commission_data['CommissionInfo']['modified']);
		if (isset($m_commission_alert_settings_tel['MCommissionAlertSetting']['condition_value_min']) && isset($m_commission_alert_settings_tel['MCommissionAlertSetting']['rits_follow_datetime'])) {
			$data_tel_list = $m_commission_alert_settings_tel['MCommissionAlertSetting']['condition_value_min'] + $m_commission_alert_settings_tel['MCommissionAlertSetting']['rits_follow_datetime'];
			$date_tel->add(new DateInterval('PT' . $data_tel_list . 'M'));
		}

		//visit 業者後追い時間
		$date_visit = new DateTime($commission_data['CommissionInfo']['modified']);
		if (isset($m_commission_alert_settings_visit['MCommissionAlertSetting']['condition_value_min']) && isset($m_commission_alert_settings_visit['MCommissionAlertSetting']['rits_follow_datetime'])) {
			$data_visit_list = $m_commission_alert_settings_visit['MCommissionAlertSetting']['condition_value_min'] + $m_commission_alert_settings_visit['MCommissionAlertSetting']['rits_follow_datetime'];
			$date_visit->add(new DateInterval('PT' . $data_visit_list . 'M'));
		}

		//order 業者後追い時間
		$date_order = new DateTime($commission_data['CommissionInfo']['modified']);
		if (isset($m_commission_alert_settings_order['MCommissionAlertSetting']['condition_value_min']) && isset($m_commission_alert_settings_order['MCommissionAlertSetting']['rits_follow_datetime'])) {
			$data_order_list = $m_commission_alert_settings_order['MCommissionAlertSetting']['condition_value_min'] + $m_commission_alert_settings_order['MCommissionAlertSetting']['rits_follow_datetime'];
			$date_order->add(new DateInterval('PT' . $data_order_list . 'M'));
		}

		$this->set('again_enabled', $again_enabled);			//再表示ボタン活性制御
		$this->set("history_list" , $history_data);                     // 取次先対応履歴のデータをSET
		$this->set("results" , $commission_data);                       // 取次詳細のデータをSET
                $this->set("demand_attached_files",$demand_attached_files);     //案件に紐付くファイルデータをSET

		// m_commission_alert_settingsのtelのデータをSET
		$m_commission_alert_settings_tel['MCommissionAlertSetting']['display_time'] = $date_tel->format('Y-m-d H:i:s');
		$this->set("m_commission_alert_settings_tel" , $m_commission_alert_settings_tel);

		// m_commission_alert_settingsのvisitのデータをSET
		$m_commission_alert_settings_visit['MCommissionAlertSetting']['display_time'] = $date_visit->format('Y-m-d H:i:s');
		$this->set("m_commission_alert_settings_visit" , $m_commission_alert_settings_visit);

		// m_commission_alert_settingsのorderのデータをSET
		$m_commission_alert_settings_order['MCommissionAlertSetting']['display_time'] = $date_order->format('Y-m-d H:i:s');
		$this->set("m_commission_alert_settings_order" , $m_commission_alert_settings_order);

		$this->set("inquiry_list" , $inquiry_data);
		$this->set("user_list" , $this->MUser->dropDownUser());  // ユーザーのデータをSET
		$this->set("tax_rate" , $tax_rate);                      // 消費税のデータSET
		$this->set("error" , $error);                            // エラー値のデータSET
		// 2015.08.17 s.harada MOD start 画面デザイン変更対応
		// $this->set("site_list" , $this->MSite->getList());		// サイト一覧
		$this->set("site_list" , $this->MSite->findById($commission_data['DemandInfo']['site_id']));
		// 2015.08.17 s.harada MOD end 画面デザイン変更対応
		$this->set("address_disclosure" , $address_disclosure);	// 住所開示時間
		$this->set("tel_disclosure" , $tel_disclosure);			// 電話番号開示時間

		//訪問日時
		$visit_time = $this->VisitTime->findById($commission_data['CommissionInfo']['commission_visit_time_id']);
		$this->set('visit_time', $visit_time);

		//	2015.01.04 h.hanhaki ORANGE-985 CHG(S) 日付を編集
		App::uses('CommonHelper', 'Lib/View/Helper');
		$CommonHelper = new CommonHelper(new View());

		// 連絡希望日時設定
		$contact_desired_time_hope = "-";
		if(isset($commission_data['DemandInfo']['contact_desired_time'])) {
			$contact_desired_time_hope = $CommonHelper->dateTimeFormat($commission_data['DemandInfo']['contact_desired_time']);
		} elseif (isset($commission_data['DemandInfo']['contact_desired_time_from'])) {
			$contact_desired_time_hope = $CommonHelper->dateTimeFormat($commission_data['DemandInfo']['contact_desired_time_from']). ' ～ '. $CommonHelper->dateTimeFormat($commission_data['DemandInfo']['contact_desired_time_to']);
		} elseif (isset($visit_time['VisitTime']['visit_adjust_time'])) {
			// 訪問案件で希望がFrom-Toの場合は、調整日時をセットする。2016.01.07 n.kai ORANGE-1027
			$contact_desired_time_hope = $CommonHelper->dateTimeFormat($visit_time['VisitTime']['visit_adjust_time']);
		}
		$this->set('contact_desired_time_hope', $contact_desired_time_hope);

		$contact_desired_time = "";
		if (isset($visit_time['VisitTime']['visit_adjust_time'])) {
			$contact_desired_time = $CommonHelper->dateTimeFormat($visit_time['VisitTime']['visit_adjust_time']);
		} else {
			$contact_desired_time = $contact_desired_time_hope;
		}

		$this->set('contact_desired_time', $contact_desired_time);

		// 表示用訪問対応日時設定
		$visit_time_display = "-";
		$visit_time_of_hope = "-";
		if (isset($commission_data['CommissionInfo']['visit_desired_time'])) {
			$visit_time_display = $CommonHelper->dateTimeFormat($commission_data['CommissionInfo']['visit_desired_time']);
		} elseif (isset($visit_time['VisitTime']['visit_time'])) {
			$visit_time_display = $CommonHelper->dateTimeFormat($visit_time['VisitTime']['visit_time']);
		} elseif (isset($visit_time['VisitTime']['visit_time_from'])) {
			$visit_time_of_hope = $CommonHelper->dateTimeFormat($visit_time['VisitTime']['visit_time_from']) . ' ～ '. $CommonHelper->dateTimeFormat($visit_time['VisitTime']['visit_time_to']);
		}

		if (isset($visit_time['VisitTime']['visit_time'])) {
			$visit_time_of_hope = $CommonHelper->dateTimeFormat($visit_time['VisitTime']['visit_time']);
		} elseif (isset($visit_time['VisitTime']['visit_time_from'])) {
			$visit_time_of_hope = $CommonHelper->dateTimeFormat($visit_time['VisitTime']['visit_time_from']). ' ～ '. $CommonHelper->dateTimeFormat($visit_time['VisitTime']['visit_time_to']);
		}

		$this->set('visit_time_display', $visit_time_display);
		$this->set('visit_time_of_hope', $visit_time_of_hope);

		//	2015.01.04 h.hanhaki ORANGE-985 CHG(E) 日付を編集

		//2015.03.31 y.kurokawa
		if(($commission_data['CommissionInfo']['lock_status'] == 1) and ($this->User["auth"] == "affiliation")){
			$this->Session->setFlash(__('AffiliationAndLockStatusIsValid', true), 'default', array('class' => 'error_inner'));
		}

		// sasaki@tobila 2016.03.09 Mod Start ORANGE-1019
		// モバイル端末用Viewで下記の変数を参照できなかったため、モバイル端末用View判定の前に移動。
		// ORANGE-1019 【案件管理】取次先情報の「取次時手数料率」の登録を自動化 Add Start
		// kishimoto@tobila 2016.02.24
		if(empty($commission_data['DemandInfo']['category_id']) == false) {
			$category_default_fee = $this->MCategory->getDefault_fee($commission_data['DemandInfo']['category_id']);
			$this->set('category_default_fee', $category_default_fee);
		}
		// ORANGE-1019 【案件管理】取次先情報の「取次時手数料率」の登録を自動化 Add End
		// sasaki@tobila 2016.03.09 Mod End ORANGE-1019

		// モバイル端末用View判定 2015.05.10 y.tanaka
		if(Util::isMobile()  && $this->User['auth'] == 'affiliation') {
			$this->layout = 'default_m';
			$this->render('detail_m');
		}

		if ($this->User['auth'] == 'affiliation') {
			$this->set ( "not_enough", $this->__doNotHavaEnoughData($this->User['affiliation_id']));
		}

		// ORANGE-13 iwai ADD S
		$this->set('applications', self::__getApplicationData($id));
		// ORANGE-13 iwai ADD E
	}

        public function commission_file_download($id=null){
            if(!ctype_digit($id)) throw new NotFoundException();

            $this->autoRender = false;

            $options['conditions'][] = array('DemandAttachedFiles.id' => $id);
            
            //加盟店ユーザーの場合に条件追加
            if($this->User['auth'] == 'affiliation'){
                $options['conditions'][] = array('CommissionInfo.corp_id' => $this->User['affiliation_id']);
                $options['conditions'][] = array('CommissionInfo.commit_flg' => 1);
            }

            // 加盟店ユーザー整合性
            $options['joins'] = array (
                array (
                    'fields' => 'DemandInfo.id',
                    'type' => 'inner',
                    "table" => "demand_infos",
                    "alias" => "DemandInfo",
                    "conditions" => array (
                        "DemandInfo.id = DemandAttachedFiles.demand_id"
                    )
                ),
                array (
                    'fields' => 'CommissionInfo.commit_flg',
                    'type' => 'inner',
                    "table" => "commission_infos",
                    "alias" => "CommissionInfo",
                    "conditions" => array (
                        "CommissionInfo.demand_id = DemandInfo.id",
                    ),
                ),
            );

            $aaf = $this->DemandAttachedFiles->find('first', $options);

            //ファイルがない場合は404
            if(!$aaf)throw new NotFoundException();
            
            //ローカルファイルが存在すれば表示
            if (file_exists($aaf['DemandAttachedFiles']['path'])) {
              $this->response->file(
                $aaf['DemandAttachedFiles']['path'],
                array('name' => $aaf['DemandAttachedFiles']['name'], 'download'=>true,)
               );
            }else{
              echo 'ファイルがありません。';
            }

          }
        
	/**
	 * キャンセル
	 *
	 */
	public function cancel() {

		// 2015.4.14 n.kai ADD start JBR様領収書後追いレポートからの遷移
		$report = false;
		// 2015.4.14 n.kai ADD end

		$para_data = '';
		$data = $this->Session->read(self::$__sessionKeyForCommissionAffiliation);
		if(!empty($data)){
			$para_data .= '/'.$data;
		}

		$data = $this->Session->read(self::$__sessionKeyForCommissionParameter);

		// 2015.4.14 n.kai ADD start JBR様領収書後追いレポートからの遷移
		// レポート管理から遷移した場合
		if (!isset($data)) {
			$ses = $this->Session->read(self::$__sessionKeyForReport);
			if(isset($ses)){
				$report = true;
				$data = $ses['named'];
			}
		}
		// 2015.4.14 n.kai ADD end

		if(isset($data['page'])){
			$para_data .= '/page:'.$data['page'];
		}
		if(isset($data['sort'])){
			$para_data .= '/sort:'.$data['sort'];
		}
		if(isset($data['direction'])){
			$para_data .= '/direction:'.$data['direction'];
		}

		// 2015.4.14 n.kai ADD start JBR様領収書後追いレポートからの遷移
		if (!$report) {
		// 2015.4.14 n.kai ADD end
			return $this->redirect('/commission/search'.$para_data);
			return;
		// 2015.4.14 n.kai ADD start JBR様領収書後追いレポートからの遷移
		} else {
			// JBR様領収書後追いレポートへ戻る
			return $this->redirect($ses['url'].$para_data);
		}
		// 2015.4.14 n.kai ADD end

	}

	/**
	 * 対応履歴編集画面
	 *
	 * @param string $id
	 */
	public function history_input($id = null) {

		$error = array();
		// 取次先対応履歴データの検索条件
		$conditions = array('CommissionCorrespond.id' => $id);
		// 取次先対応履歴データの取得
		$history_data = $this->__get_commission_corresponds($conditions);

		$this->set("user_list" , $this->MUser->dropDownUser());

		if (isset($this->request->data['edit'])){ // 編集
			// 更新日付のチェック
			if(self::__check_modified_commission_corresponds($id , $this->request->data['modified'])){
				try {
					$this->CommissionCorrespond->begin();
					// 対応履歴の編集
					if(self::__edit_history($id , $this->request->data)){
						$this->set("end" , $id);  //ID
						$this->CommissionCorrespond->commit();
					} else {
						$this->CommissionCorrespond->rollback();
					}
				} catch (Exception $e) {
					$this->CommissionCorrespond->rollback();
				}
			} else {
				$this->data = $this->request->data;
				$error['modified'] = __('ModifiedNotCheck', true);
			}
		} else {
			$this->data = $history_data;
		}

		// モバイル端末用View判定 2015.05.10 y.tanaka
		if(Util::isMobile() && $this->User['auth'] == 'affiliation') {
			$this->layout = 'default_m';
			$this->render('history_input_m');
		}else {
			$this->layout = 'subwin';
			$this->set("error" , $error);
		}

	}

	// 2015.07.05 n.kai ADD start スマホアプリ既読時処理
	/**
	 * スマホアプリ既読処理
	 *
	 * @param string $id
	 */
	private function __sp_detail_read($id = null) {
		// スマホアプリで取次情報(詳細画面)を表示時、commission_infosのapp_notreadを「0」にする
		// 加盟店ログイン時のみ
		if($this->User["auth"] == "affiliation"){
			// 取次詳細データの取得
			$commission_data = $this->CommissionInfo->findById($id);
			// 取次詳細データがあった場合のみ処理
			if(!empty($commission_data) && $commission_data['CommissionInfo']['app_notread'] == 1){
				// DB更新
				$commission_data['CommissionInfo']['id'] = $id;
				$commission_data['CommissionInfo']['app_notread'] = 0;
				$commission_data['CommissionInfo']['modified'] = false;
				$this->CommissionInfo->begin();
				if($this->CommissionInfo->save($commission_data, false)){
					$this->CommissionInfo->commit();
				} else {    // エラー有りの場合
					$this->CommissionInfo->rollback();
					return false;
				}
			}
		}
		return true;
	}
	// 2015.07.05 n.kai ADD end

	/**
	 * 虚偽報告なし チェック
	 *
	 * @param unknown $data
	 * @return boolean
	 */
// 	private function __falsity_check(){

// 		if($this->User["auth"] == "affiliation"){
// 			if(empty($this->request->data['CommissionInfo']['reported_flg'])){
// 				return false;
// 			}
// 		}
// 		return true;
// 	}

	/**
	 * PDF作成
	 *
	 * @return boolean
	 */
	private function __pdf_commission(){

		$conditions['or'] = array();
		$check_data = $this->request->data['checkbox'];
		foreach ($check_data as $val){
			$conditions['or'][] = 'CommissionInfo.id = '.$val;
		}

		$joins = self::__setSearchJoin();

		$results = $this->CommissionInfo->find('all',
				array('fields' => '* , DemandInfo.id , DemandInfo.customer_name , DemandInfo.category_id , MCorp.corp_name, MCorp.official_corp_name , MCorp.commission_dial, MCategory.category_name, MItem.item_name',
						'conditions' => $conditions,
						'joins' =>  $joins,
						'order' => array('DemandInfo.id' => 'desc','CommissionInfo.id' => 'desc'),
				)
		);

		if(!empty($results)){
			$this->PDF->commission($results);
			return true;
		}
		return false;
	}

	/**
	 * 検索条件をセッションに書き込む
	 *
	 * @param unknown $affiliation_id
	 */
	private function __updata_affiliation_id($affiliation_id){

		$this->Session->delete(self::$__sessionKeyForCommissionAffiliation);
		$this->Session->write(self::$__sessionKeyForCommissionAffiliation, $affiliation_id);

	}

	/**
	 * 企業IDをセッションに書き込む
	 *
	 * @return Ambigous <mixed, boolean, NULL, unknown, array>
	 */
	private function __get_affiliation_id() {

		$affiliation_id = $this->Session->read(self::$__sessionKeyForCommissionAffiliation);

		return $affiliation_id;
	}

	/**
	 * 一覧の検索結果を取得しセット
	 *
	 * @return multitype:Ambigous <NULL, mixed, multitype:>
	 */
	private function __commissionListGet() {

		$data = $this->Session->read(self::$__sessionKeyForCommissionSearch);

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
	private function __commissionListPost() {

		$data = $this->request->data;

		$this->Session->delete(self::$__sessionKeyForCommissionSearch);
		$this->Session->write(self::$__sessionKeyForCommissionSearch, $data);

		return $data;
	}

	/**
	 * 一覧検索条件作成
	 *
	 * @param string $data
	 * @return multitype:string
	 */
	private function __setSearchConditions($data = null , $id = null) {

		$conditions = array();
		// 取次前失注フラグ
		$conditions['CommissionInfo.lost_flg !='] = 1;

		// 2015.03.17 h.hara
		// 削除フラグ
		$conditions['CommissionInfo.del_flg ='] = 0;

		// 紹介不可フラグ
		$conditions['CommissionInfo.introduction_not !='] = 1;

		// お客様名
		if (!empty($data['customer_name'])) {
			$conditions['z2h_kana(DemandInfo.customer_name) like'] = '%'. Util::chgSearchValue($data['customer_name']) .'%';
		}
		// 案件番号
		if (!empty($data['demand_id'])) {
			$conditions['CommissionInfo.demand_id'] = Util::chgSearchValue($data['demand_id']);
		}
		// お客様電話番号
		if (!empty($data['customer_tel'])) {
			//ORANGE-4 CHG S
			//$conditions['z2h_kana(DemandInfo.customer_tel)'] = Util::chgSearchValue($data['customer_tel']);
			array_push($conditions, array (
				'or' => array (
								'DemandInfo.customer_tel' => $data['customer_tel'],
								'DemandInfo.tel1' => $data['customer_tel'],
								'DemandInfo.tel2' => $data['customer_tel']
						)
				)
			);
			//ORANGE-4 CHG E
		}
		// 状況
		if (!empty($data['commission_status'])) {
			$conditions['CommissionInfo.commission_status'] = $data['commission_status'];
		}
		// 企業ID
		if(!empty($id)){
			$conditions['MCorp.id'] = $id;
		}

		// ジャンル名
		if (!empty($data['genre_id'])) {
			$conditions['DemandInfo.genre_id'] = $data['genre_id'];
		}

		// サイト名
		if (!empty($data['site_id'])) {
			$conditions['DemandInfo.site_id'] = $data['site_id'];
		}

		// JBR様受付No
		if (!empty($data['jbr_order_no'])) {
			$conditions['z2h_kana(DemandInfo.jbr_order_no)'] = Util::chgSearchValue($data['jbr_order_no']);
		}

		// 初期条件
		if(empty($data)){
			$conditions['CommissionInfo.commission_status'] = array(
				Util::getDivValue('construction_status','received'),
				Util::getDivValue('construction_status','progression'),
			);
		}

		// 加盟店ログイン者のみ
		if($this->User["auth"] == "affiliation"){
			$conditions['MCorp.id'] = $this->User["affiliation_id"];
		}

		// 取次日 From
		if (!empty($data['commission_date1'])) {
			//$conditions['DemandInfo.receive_datetime >='] = $data['commission_date1'] . " 00:00:00";
			$conditions['CommissionInfo.commission_note_send_datetime >='] = $data['commission_date1'] . " 00:00:00";
		}

		// 取次日 To
		if (!empty($data['commission_date2'])) {
			//$conditions['DemandInfo.receive_datetime <='] = $data['commission_date2'] . " 23:59:59";
			$conditions['CommissionInfo.commission_note_send_datetime <='] = $data['commission_date2'] . " 23:59:59";
		}

		// 後追い日 From
		if (!empty($data['from_follow_date'])) {
			$conditions['CommissionInfo.follow_date >='] = $data['from_follow_date'];
		}

		// 後追い日 To
		if (!empty($data['to_follow_date'])) {
			$conditions['CommissionInfo.follow_date <='] = $data['to_follow_date'];
		}

		// 訪問日
		if (!empty($data['visit_desired_time'])) {
			//$conditions['DemandInfo.receive_datetime <='] = $data['commission_date2'] . " 23:59:59";
			$conditions['VisitTime.visit_time >='] = $data['visit_desired_time'] . " 00:00:00";
			$conditions['VisitTime.visit_time <='] = $data['visit_desired_time'] . " 23:59:59";
		}

		// 電話希望日時
		if (!empty($data['contact_desired_time'])) {
			//$conditions['DemandInfo.receive_datetime <='] = $data['commission_date2'] . " 23:59:59";
			$conditions['DemandInfo.contact_desired_time >='] = $data['contact_desired_time'] . " 00:00:00";
			$conditions['DemandInfo.contact_desired_time <='] = $data['contact_desired_time'] . " 23:59:59";
		}

		//$conditions['or'] = array(array('DemandInfo.demand_status' => Util::getDivValue('demand_status', 'telephone_already')), array('DemandInfo.demand_status' => Util::getDivValue('demand_status', 'information_sent')));
		//ORANGE-4 CHG S
		array_push($conditions, array (
				'or' => array (
						'DemandInfo.demand_status' => array(Util::getDivValue('demand_status', 'telephone_already'),Util::getDivValue('demand_status', 'information_sent'))
						)
				)
		);
		//ORANGE-4 CHG E
		return $conditions;
	}

	/**
	 * 検索用Join句作成
	 *
	 * @return multitype:multitype:string multitype:string
	 */
	private function __setSearchJoin() {
            //2017-07-19 ichino ORANGE-32 ADD start 案件添付ファイルの有無追加
            $subQuery = '(select distinct demand_id from demand_attached_files)';
            //2017-07-19 ichino ORANGE-32 ADD end
            
		return array (
			array (
				'fields' => '*',
				'type' => 'inner',
				"table" => "demand_infos",
				"alias" => "DemandInfo",
				"conditions" => array (
				"DemandInfo.id = CommissionInfo.demand_id",
				"DemandInfo.del_flg != 1"
				)
			),
			// 2015.5.15 n.kai ADD start ＊検索結果にサイト名を追加
			array (
				'fields' => '*',
				'type' => 'left',
				"table" => "m_sites",
				"alias" => "MSite",
				"conditions" => array (
					"MSite.id = DemandInfo.site_id"
				)
			),
			// 2015.5.15 n.kai ADD end
			array (
				'fields' => '*',
				'type' => 'left',
				"table" => "m_categories",
				"alias" => "MCategory",
				"conditions" => array (
					"MCategory.id = DemandInfo.category_id"
				)
			),
			array (
				'fields' => '*',
				'type' => 'left',
				"table" => "m_items",
				"alias" => "MItem",
				"conditions" => array (
					"MItem.item_category = '" . __ ( 'commission_status', true ) . "'",
					"MItem.item_id = CommissionInfo.commission_status"
				)
			),
			// 2015.09.26 VisitTimeを追加
			// 2015.12.24 範囲指定を追加 ORANGE-1027
			array(
				'fields' => array('*'),
				'type' => 'left',
				"table" => 'visit_time_view',
				'alias' => 'VisitTime',
				'conditions' => array(
					"CommissionInfo.commission_visit_time_id = VisitTime.id"
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
	}

	/**
	 * 取次データの取得
	 * (基本情報・取次情報)
	 *
	 * @return unknown
	 */
	private function __get_commission_list($conditions = array() , $id = null){

		$joins = self::__setSearchJoin();

		$this->CommissionInfo->virtualFields = array(
				// 2015.5.15 n.kai MOD start ＊検索結果をカテゴリからサイト名へ変更
				//'category_id' => 'MCategory.id',
				'site_name' => 'MSite.site_name',
				// 2015.5.15 n.kai MOD end
				// 2015.09.25 ソート条件の追加 ※案件として完了しているものも問題なしとする
				'status' => "case when (CommissionInfo.tel_support + CommissionInfo.visit_support + CommissionInfo.order_support > 0) and
						       CommissionInfo.commission_status in (1,2)
						      then 1
						  else 0
						  end" ,
				'selection_system' => 'DemandInfo.selection_system',  			//案件種別
				'visit_time_min' => 'VisitTime.visit_time',      				//訪問日時
				'corp_name' => 'MCorp.corp_name',								//お客様名
// 2016.04.11 ota.r@tobila ADD START ORANGE-1308 【案件管理】ヒアリング情報に「法人名」の入力項目を追加お願いします。任意項目です。
				'customer_corp' => 'DemandInfo.customer_corp_name',             //法人名
// 2016.04.11 ota.r@tobila ADD END ORANGE-1308 【案件管理】ヒアリング情報に「法人名」の入力項目を追加お願いします。任意項目です。
				'tel1' => 'DemandInfo.tel1',									//連絡先①
				'address1' => 'DemandInfo.address1',							//都道府県
				'receive_datetime' => 'DemandInfo.receive_datetime',			//受付日時
				'item_name' => 'MItem.item_name'								//案件状況
		);

		// 2015.5.15 n.kai MOD start
		//if(empty($id)){   // 一般の表示の場合
		if( empty($id) || (!empty($id) && isset($this->request->data['Output']) == FALSE)){   // 一般の表示の場合
		// 2015.5.15 n.kai MOD end

			$list_limit = 100;
			if(Util::isMobile()  && $this->User['auth'] == 'affiliation') {
				$list_limit = Configure::read('list_limit_mobile');
			} else {
				$list_limit = Configure::read('list_limit');
			}


			$this->paginate = array(
				'conditions' => $conditions,
				// 2015.5.15 n.kai MOD start ＊検索結果に企業名、サイト名を追加
				//'fields' => '* , DemandInfo.id , DemandInfo.customer_name , DemandInfo.category_id, MCategory.category_name, MItem.item_name',
				//'fields' => '* , DemandInfo.id , DemandInfo.customer_name , DemandInfo.category_id, DemandInfo.site_id, CommissionInfo.address1, DemandInfo.address1, CommissionInfo.tel1, DemandInfo.tel1, DemandInfo.tel2, CommissionInfo.selection_system, DemandInfo.selection_system, MSite.site_name, MSite.site_url, MCategory.category_name, CommissionInfo.item_name, MItem.item_name, CommissionInfo.corp_id, CommissionInfo.corp_name, MCorp.corp_name ',
                                //2017-07-19 ichino ORANGE-32 ADD 案件添付ファイルの有無追加
				'fields' => array('*' ,
                                                                        'DemandAttachedFiles.demand_id' ,   //案件添付ファイルの有無
									'DemandInfo.id' ,                   //案件ID
									'DemandInfo.customer_name' ,        //顧客名
                                    // 2016.04.11 ota.r@tobila ADD START ORANGE-1308 【案件管理】ヒアリング情報に「法人名」の入力項目を追加お願いします。任意項目です。
                                     'DemandInfo.customer_corp_name' ,  //法人名
                                    // 2016.04.11 ota.r@tobila ADD END ORANGE-1308 【案件管理】ヒアリング情報に「法人名」の入力項目を追加お願いします。任意項目です。
									'DemandInfo.category_id',           //カテゴリID
									'DemandInfo.site_id',               //サイトID
									'DemandInfo.address1',              //都道府県
									'DemandInfo.tel1',                  //連絡先1
						 			'DemandInfo.tel2',                  //連絡先2
									'DemandInfo.selection_system',      //案件種別
									'DemandInfo.contact_desired_time',	//連絡希望日時
									'DemandInfo.contact_desired_time_from',//連絡希望日時from
									'DemandInfo.contact_desired_time_to',//連絡希望日時to
									'DemandInfo.selection_system',      //案件種別
									'MSite.site_name',                  //サイト名
									'MSite.site_url',                   //サイトURL
									'MCategory.category_name',          //カテゴリ名
									'MItem.item_name',                  //案件状況
									'CommissionInfo.corp_id',           //企業ID
									'MCorp.corp_name',                  //企業名
									//'DemandInfo.contact_desired_time',  //連絡期限日時
									'VisitTime.id',                     //訪問日ID
									'VisitTime.visit_time',             //訪問日時
									'VisitTime.visit_time_to',          //訪問希望日時to
									'VisitTime.visit_adjust_time',      //訪問日時要調整時間
									'DemandInfo.receive_datetime',      //受付日時
									'MCorp.auction_masking',            //入札式マスキング除外
									'DemandInfo.priority',              //優先度
									'DemandInfo.is_contact_time_range_flg',
									'VisitTime.is_visit_time_range_flg'
									//'(case when DemandInfo.is_contact_time_range_flg = 0 then DemandInfo.contact_desired_time end) as contact_desired_time_min'
								),
				// 2015.5.15 n.kai MOD end
				'joins' => $joins,
				'limit' => $list_limit,
				'order' => array(
					'CommissionInfo.status' => 'desc',
					'CommissionInfo.demand_id' => 'desc',
					'CommissionInfo.id' => 'desc'
				),
			);

			$results = $this->paginate('CommissionInfo');

		} else {      // 帳票出力ボタン有の場合
			$results = $this->CommissionInfo->find('all',
					// 2015.5.15 n.kai MOD start ＊検索結果に企業名、サイト名を追加
					//array( 'fields' => '* , DemandInfo.id , DemandInfo.customer_name , DemandInfo.category_id, MCategory.category_name, MItem.item_name',
					array( 'fields' => '* , DemandInfo.id , DemandInfo.customer_name , DemandInfo.category_id, DemandInfo.site_id, DemandInfo.address1, DemandInfo.tel1, DemandInfo.tel2, DemandInfo.selection_system, MSite.site_name, MSite.site_url, MCategory.category_name, MItem.item_name, CommissionInfo.corp_id, MCorp.corp_name ',
					// 2015.5.15 n.kai MOD end
							'joins' =>  $joins,
							'conditions' => $conditions,
							'order' => array('DemandInfo.id' => 'desc','CommissionInfo.id' => 'desc'),
					)
			);
		}

		return $results;
	}

	/**
	 * 取次ID別に取次データの取得
	 * (基本情報・取次情報)
	 *
	 * @param unknown $id
	 * @return unknown
	 */
	private function __set_commission($id = null){

		// 検索条件
		$conditions = array('CommissionInfo.id' => $id);

		// 加盟店ログイン者のみ
		if($this->User["auth"] == "affiliation"){
			$conditions['CommissionInfo.corp_id'] = $this->User["affiliation_id"];
		}

		$results = $this->CommissionInfo->find('first',
				//array( 'fields' => '* , DemandInfo.id , DemandInfo.demand_status , DemandInfo.customer_name , DemandInfo.customer_tel, DemandInfo.address1, DemandInfo.address2, DemandInfo.address3, DemandInfo.address4, DemandInfo.building, DemandInfo.room, DemandInfo.tel1, DemandInfo.tel2, DemandInfo.customer_mailaddress, DemandInfo.contents, DemandInfo.contact_desired_time, DemandInfo.site_id, DemandInfo.order_date, DemandInfo.jbr_estimate_status, DemandInfo.jbr_receipt_status, DemandInfo.riro_kureka, DemandInfo.low_accuracy, BillInfo.id, BillInfo.irregular_fee_rate, BillInfo.irregular_fee, BillInfo.bill_status, BillInfo.fee_target_price, BillInfo.fee_tax_exclude, BillInfo.fee_billing_date, BillInfo.total_bill_price, BillInfo.tax, BillInfo.insurance_price, MCorp.id, MCorp.corp_name, MCorp.official_corp_name, MCorpCategory.order_fee, MCorpCategory.order_fee, MCorpCategory.order_fee_unit, MCorpCategory.introduce_fee, MCorpCategory.note, MCorp.commission_dial, MCorp.progress_check_tel, AffiliationInfo.liability_insurance , MGenre.insurant_flg, DemandInfo.genre_id, MGenre.genre_name, DemandInfo.jbr_order_no, DemandInfo.follow_date',
				//array( 'fields' => '* , DemandInfo.id , DemandInfo.demand_status , DemandInfo.customer_name , DemandInfo.customer_tel, DemandInfo.address1, DemandInfo.address2, DemandInfo.address3, DemandInfo.address4, DemandInfo.building, DemandInfo.room, DemandInfo.tel1, DemandInfo.tel2, DemandInfo.customer_mailaddress, DemandInfo.contents, DemandInfo.contact_desired_time, DemandInfo.site_id, DemandInfo.order_date, DemandInfo.jbr_estimate_status, DemandInfo.jbr_receipt_status, DemandInfo.riro_kureka, DemandInfo.low_accuracy, BillInfo.id, BillInfo.irregular_fee_rate, BillInfo.irregular_fee, BillInfo.bill_status, BillInfo.fee_target_price, BillInfo.fee_tax_exclude, BillInfo.fee_billing_date, BillInfo.total_bill_price, BillInfo.tax, BillInfo.insurance_price, MCorp.id, MCorp.corp_name, MCorp.official_corp_name, MCorpCategory.order_fee, MCorpCategory.order_fee, MCorpCategory.order_fee_unit, MCorpCategory.introduce_fee, MCorpCategory.note, MCorp.commission_dial, MCorp.progress_check_tel, AffiliationInfo.liability_insurance , MGenre.insurant_flg, DemandInfo.genre_id, MGenre.genre_name, DemandInfo.jbr_order_no, DemandInfo.follow_date, DemandInfo.upload_estimate_file_name, DemandInfo.upload_receipt_file_name',
				//array( 'fields' => '* , DemandInfo.id , DemandInfo.demand_status , DemandInfo.customer_name , DemandInfo.receive_datetime , DemandInfo.customer_tel, DemandInfo.address1, DemandInfo.address2, DemandInfo.address3, DemandInfo.address4, DemandInfo.building, DemandInfo.room, DemandInfo.tel1, DemandInfo.tel2, DemandInfo.customer_mailaddress, DemandInfo.contents, DemandInfo.contact_desired_time, DemandInfo.site_id, DemandInfo.order_date, DemandInfo.jbr_estimate_status, DemandInfo.jbr_receipt_status, DemandInfo.riro_kureka, DemandInfo.low_accuracy, BillInfo.id, BillInfo.irregular_fee_rate, BillInfo.irregular_fee, BillInfo.bill_status, BillInfo.fee_target_price, BillInfo.fee_tax_exclude, BillInfo.fee_billing_date, BillInfo.total_bill_price, BillInfo.tax, BillInfo.insurance_price, MCorp.id, MCorp.corp_name, MCorp.official_corp_name, MCorpCategory.order_fee, MCorpCategory.order_fee, MCorpCategory.order_fee_unit, MCorpCategory.introduce_fee, MCorpCategory.note, MCorp.commission_dial, MCorp.progress_check_tel, AffiliationInfo.liability_insurance , MGenre.insurant_flg, DemandInfo.genre_id, MGenre.genre_name, DemandInfo.jbr_order_no, DemandInfo.follow_date, DemandInfo.upload_estimate_file_name, DemandInfo.upload_receipt_file_name, DemandInfo.selection_system, DemandInfo.priority, VisitTime.visit_time, AuctionInfo.responders',
				// 2015.10.10 h.hanaki CHG       ORANGE_AUCTION-17
				//array( 'fields' => '* , DemandInfo.id , DemandInfo.demand_status , DemandInfo.customer_name , DemandInfo.receive_datetime , DemandInfo.customer_tel, DemandInfo.address1, DemandInfo.address2, DemandInfo.address3, DemandInfo.address4, DemandInfo.building, DemandInfo.room, DemandInfo.tel1, DemandInfo.tel2, DemandInfo.customer_mailaddress, DemandInfo.contents, DemandInfo.contact_desired_time, DemandInfo.site_id, DemandInfo.order_date, DemandInfo.jbr_estimate_status, DemandInfo.jbr_receipt_status, DemandInfo.riro_kureka, DemandInfo.low_accuracy, BillInfo.id, BillInfo.irregular_fee_rate, BillInfo.irregular_fee, BillInfo.bill_status, BillInfo.fee_target_price, BillInfo.fee_tax_exclude, BillInfo.fee_billing_date, BillInfo.total_bill_price, BillInfo.tax, BillInfo.insurance_price, MCorp.id, MCorp.corp_name, MCorp.official_corp_name, MCorpCategory.order_fee, MCorpCategory.order_fee, MCorpCategory.order_fee_unit, MCorpCategory.introduce_fee, MCorpCategory.note, MCorp.commission_dial, MCorp.progress_check_tel, AffiliationInfo.liability_insurance , MGenre.insurant_flg, DemandInfo.genre_id, MGenre.genre_name, DemandInfo.jbr_order_no, DemandInfo.follow_date, DemandInfo.upload_estimate_file_name, DemandInfo.upload_receipt_file_name, DemandInfo.selection_system, DemandInfo.priority, AuctionInfo.visit_time_id, AuctionInfo.responders',
				// 2015.11.25 h.hanaki CHG       ORANGE-1056 【取次管理】案件管理にある「出張費提示」「サービス価格提示」の項目を取次管理に追加
				//array( 'fields' => '* , DemandInfo.id , DemandInfo.demand_status , DemandInfo.customer_name , DemandInfo.receive_datetime , DemandInfo.customer_tel, DemandInfo.address1, DemandInfo.address2, DemandInfo.address3, DemandInfo.address4, DemandInfo.building, DemandInfo.room, DemandInfo.tel1, DemandInfo.tel2, DemandInfo.customer_mailaddress, DemandInfo.contents, DemandInfo.contact_desired_time, DemandInfo.site_id, DemandInfo.order_date, DemandInfo.jbr_estimate_status, DemandInfo.jbr_receipt_status, DemandInfo.riro_kureka, DemandInfo.low_accuracy, DemandInfo.construction_class, BillInfo.id, BillInfo.irregular_fee_rate, BillInfo.irregular_fee, BillInfo.bill_status, BillInfo.fee_target_price, BillInfo.fee_tax_exclude, BillInfo.fee_billing_date, BillInfo.total_bill_price, BillInfo.tax, BillInfo.insurance_price, MCorp.id, MCorp.corp_name, MCorp.official_corp_name, MCorpCategory.order_fee, MCorpCategory.order_fee, MCorpCategory.order_fee_unit, MCorpCategory.introduce_fee, MCorpCategory.note, MCorp.commission_dial, MCorp.progress_check_tel, AffiliationInfo.liability_insurance , MGenre.insurant_flg, DemandInfo.genre_id, MGenre.genre_name, DemandInfo.jbr_order_no, DemandInfo.follow_date, DemandInfo.upload_estimate_file_name, DemandInfo.upload_receipt_file_name, DemandInfo.selection_system, DemandInfo.priority, AuctionInfo.visit_time_id, AuctionInfo.responders',
				//array( 'fields' => '* , DemandInfo.id , DemandInfo.demand_status , DemandInfo.customer_name , DemandInfo.receive_datetime , DemandInfo.customer_tel, DemandInfo.address1, DemandInfo.address2, DemandInfo.address3, DemandInfo.address4, DemandInfo.building, DemandInfo.room, DemandInfo.tel1, DemandInfo.tel2, DemandInfo.customer_mailaddress, DemandInfo.contents, DemandInfo.contact_desired_time, DemandInfo.site_id, DemandInfo.order_date, DemandInfo.jbr_estimate_status, DemandInfo.jbr_receipt_status, DemandInfo.riro_kureka, DemandInfo.low_accuracy, DemandInfo.construction_class, DemandInfo.business_trip_amount, DemandInfo.cost_from, DemandInfo.cost_to, BillInfo.id, BillInfo.irregular_fee_rate, BillInfo.irregular_fee, BillInfo.bill_status, BillInfo.fee_target_price, BillInfo.fee_tax_exclude, BillInfo.fee_billing_date, BillInfo.total_bill_price, BillInfo.tax, BillInfo.insurance_price, MCorp.id, MCorp.corp_name, MCorp.official_corp_name, MCorpCategory.order_fee, MCorpCategory.order_fee, MCorpCategory.order_fee_unit, MCorpCategory.introduce_fee, MCorpCategory.note, MCorp.commission_dial, MCorp.progress_check_tel, AffiliationInfo.liability_insurance , MGenre.insurant_flg, DemandInfo.genre_id, MGenre.genre_name, DemandInfo.jbr_order_no, DemandInfo.follow_date, DemandInfo.upload_estimate_file_name, DemandInfo.upload_receipt_file_name, DemandInfo.selection_system, DemandInfo.priority, AuctionInfo.visit_time_id, AuctionInfo.responders, MCorp.auction_masking',
				//array( 'fields' => '* , DemandInfo.id , DemandInfo.demand_status , DemandInfo.customer_name , DemandInfo.receive_datetime , DemandInfo.customer_tel, DemandInfo.address1, DemandInfo.address2, DemandInfo.address3, DemandInfo.address4, DemandInfo.building, DemandInfo.room, DemandInfo.tel1, DemandInfo.tel2, DemandInfo.customer_mailaddress, DemandInfo.contents, DemandInfo.contact_desired_time, DemandInfo.site_id, DemandInfo.order_date, DemandInfo.jbr_estimate_status, DemandInfo.jbr_receipt_status, DemandInfo.riro_kureka, DemandInfo.low_accuracy, DemandInfo.construction_class, DemandInfo.business_trip_amount, DemandInfo.cost_from, DemandInfo.cost_to, BillInfo.id, BillInfo.irregular_fee_rate, BillInfo.irregular_fee, BillInfo.bill_status, BillInfo.fee_target_price, BillInfo.fee_tax_exclude, BillInfo.fee_billing_date, BillInfo.total_bill_price, BillInfo.tax, BillInfo.insurance_price, MCorp.id, MCorp.corp_name, MCorp.official_corp_name, MCorpCategory.order_fee, MCorpCategory.order_fee, MCorpCategory.order_fee_unit, MCorpCategory.introduce_fee, MCorpCategory.note, MCorp.commission_dial, MCorp.progress_check_tel, AffiliationInfo.liability_insurance , MGenre.insurant_flg, DemandInfo.genre_id, MGenre.genre_name, DemandInfo.jbr_order_no, DemandInfo.follow_date, DemandInfo.upload_estimate_file_name, DemandInfo.upload_receipt_file_name, DemandInfo.selection_system, DemandInfo.priority, AuctionInfo.visit_time_id, AuctionInfo.responders, MCorp.auction_masking, DemandInfo.is_contact_time_range_flg, DemandInfo.contact_desired_time_from, DemandInfo.contact_desired_time_to',
				// 2006.02.24 kishimoto@tobila CHG ORANGE-1019 【案件管理】取次先情報の「取次時手数料率」の登録を自動化
                // 2016.04.11 ota.r@tobila MOD START ORANGE-1308 【案件管理】ヒアリング情報に「法人名」の入力項目を追加お願いします。任意項目です。
//				array( 'fields' => '* , DemandInfo.id , DemandInfo.demand_status , DemandInfo.customer_name , DemandInfo.receive_datetime , DemandInfo.customer_tel, DemandInfo.address1, DemandInfo.address2, DemandInfo.address3, DemandInfo.address4, DemandInfo.building, DemandInfo.room, DemandInfo.tel1, DemandInfo.tel2, DemandInfo.customer_mailaddress, DemandInfo.contents, DemandInfo.contact_desired_time, DemandInfo.site_id, DemandInfo.order_date, DemandInfo.jbr_estimate_status, DemandInfo.jbr_receipt_status, DemandInfo.riro_kureka, DemandInfo.low_accuracy, DemandInfo.construction_class, DemandInfo.business_trip_amount, DemandInfo.cost_from, DemandInfo.cost_to, BillInfo.id, BillInfo.irregular_fee_rate, BillInfo.irregular_fee, BillInfo.bill_status, BillInfo.fee_target_price, BillInfo.fee_tax_exclude, BillInfo.fee_billing_date, BillInfo.total_bill_price, BillInfo.tax, BillInfo.insurance_price, MCorp.id, MCorp.corp_name, MCorp.official_corp_name, MCorpCategory.order_fee, MCorpCategory.order_fee, MCorpCategory.order_fee_unit, MCorpCategory.introduce_fee, MCorpCategory.note, MCorp.commission_dial, MCorp.progress_check_tel, AffiliationInfo.liability_insurance , MGenre.insurant_flg, DemandInfo.genre_id, MGenre.genre_name, DemandInfo.jbr_order_no, DemandInfo.follow_date, DemandInfo.upload_estimate_file_name, DemandInfo.upload_receipt_file_name, DemandInfo.selection_system, DemandInfo.priority, AuctionInfo.visit_time_id, AuctionInfo.responders, MCorp.auction_masking, DemandInfo.is_contact_time_range_flg, DemandInfo.contact_desired_time_from, DemandInfo.contact_desired_time_to, DemandInfo.category_id',
				//ORANGE-114 CHG S
				//array( 'fields' => '* , DemandInfo.id , DemandInfo.demand_status , DemandInfo.customer_name , DemandInfo.customer_corp_name , DemandInfo.receive_datetime , DemandInfo.customer_tel, DemandInfo.address1, DemandInfo.address2, DemandInfo.address3, DemandInfo.address4, DemandInfo.building, DemandInfo.room, DemandInfo.tel1, DemandInfo.tel2, DemandInfo.customer_mailaddress, DemandInfo.contents, DemandInfo.contact_desired_time, DemandInfo.site_id, DemandInfo.order_date, DemandInfo.jbr_estimate_status, DemandInfo.jbr_receipt_status, DemandInfo.jbr_receipt_price, DemandInfo.riro_kureka, DemandInfo.low_accuracy, DemandInfo.construction_class, DemandInfo.business_trip_amount, DemandInfo.cost_from, DemandInfo.cost_to, BillInfo.id, BillInfo.irregular_fee_rate, BillInfo.irregular_fee, BillInfo.bill_status, BillInfo.fee_target_price, BillInfo.fee_tax_exclude, BillInfo.fee_billing_date, BillInfo.total_bill_price, BillInfo.tax, BillInfo.insurance_price, MCorp.id, MCorp.corp_name, MCorp.official_corp_name, MCorpCategory.order_fee, MCorpCategory.order_fee, MCorpCategory.order_fee_unit, MCorpCategory.introduce_fee, MCorpCategory.note, MCorp.commission_dial, MCorp.progress_check_tel, AffiliationInfo.liability_insurance , MGenre.insurant_flg, DemandInfo.genre_id, MGenre.genre_name, DemandInfo.jbr_order_no, DemandInfo.follow_date, DemandInfo.upload_estimate_file_name, DemandInfo.upload_receipt_file_name, DemandInfo.selection_system, DemandInfo.priority, AuctionInfo.visit_time_id, AuctionInfo.responders, MCorp.auction_masking, DemandInfo.is_contact_time_range_flg, DemandInfo.contact_desired_time_from, DemandInfo.contact_desired_time_to, DemandInfo.category_id',
				//ORANGE-114 CHG E
				// murata.s ORANGE-261 CHG(S)
                //ORANGE-198 iwai CHG S
				array( 'fields' =>
						'* , DemandInfo.id , DemandInfo.demand_status , DemandInfo.customer_name , DemandInfo.customer_corp_name , DemandInfo.receive_datetime , DemandInfo.customer_tel, DemandInfo.address1, DemandInfo.address2,
						DemandInfo.address3, DemandInfo.address4, DemandInfo.building, DemandInfo.room, DemandInfo.tel1, DemandInfo.tel2, DemandInfo.customer_mailaddress, DemandInfo.contents, DemandInfo.contact_desired_time,
						DemandInfo.site_id, DemandInfo.order_date, DemandInfo.jbr_estimate_status, DemandInfo.jbr_receipt_status, DemandInfo.jbr_receipt_price, DemandInfo.riro_kureka, DemandInfo.low_accuracy,
						DemandInfo.construction_class, DemandInfo.business_trip_amount, DemandInfo.cost_from, DemandInfo.cost_to, BillInfo.id, BillInfo.irregular_fee_rate, BillInfo.irregular_fee, BillInfo.bill_status,
						BillInfo.fee_target_price, BillInfo.fee_tax_exclude, BillInfo.fee_billing_date, BillInfo.total_bill_price, BillInfo.tax, BillInfo.insurance_price, MCorp.id, MCorp.corp_name, MCorp.official_corp_name,
						MCorpCategory.order_fee, MCorpCategory.order_fee, MCorpCategory.order_fee_unit, MCorpCategory.introduce_fee, MCorpCategory.corp_commission_type, MCorpCategory.note, MCorp.commission_dial, MCorp.progress_check_tel,
						AffiliationInfo.liability_insurance , MGenre.insurant_flg, DemandInfo.genre_id, MGenre.genre_name, DemandInfo.jbr_order_no, DemandInfo.follow_date, DemandInfo.upload_estimate_file_name,
						DemandInfo.upload_receipt_file_name, DemandInfo.selection_system, DemandInfo.priority, AuctionInfo.id, AuctionInfo.visit_time_id, AuctionInfo.responders, MCorp.auction_masking, DemandInfo.is_contact_time_range_flg,
						DemandInfo.contact_desired_time_from, DemandInfo.contact_desired_time_to, DemandInfo.category_id',
				//ORANGE-198 iwai CHG E
				// murata.s ORANGE-261 CHG(E)
                // 2016.04.11 ota.r@tobila MOD END ORANGE-1308 【案件管理】ヒアリング情報に「法人名」の入力項目を追加お願いします。任意項目です。
						'joins' =>  array(
								array('fields' => '*',
										'type' => 'inner',
										"table" => "demand_infos",
										"alias" => "DemandInfo",
										"conditions" => array("DemandInfo.id = CommissionInfo.demand_id")
								),
								array('fields' => '*',
										'type' => 'left',
										"table" => "auction_infos",
										"alias" => "AuctionInfo",
										"conditions" => array(
											"AuctionInfo.demand_id = DemandInfo.id",
											"AuctionInfo.corp_id = CommissionInfo.corp_id"
										)
								),
								/*
								array (
										'type' => 'left',
										"table" => "visit_times",
										"alias" => "VisitTime",
										"conditions" => array (
										"VisitTime.id = AuctionInfo.visit_time_id"
									)
								),*/
								array('fields' => '*',
										'type' => 'inner',
										"table" => "m_genres",
										"alias" => "MGenre",
										"conditions" => array("MGenre.id = DemandInfo.genre_id")
								),
								array('fields' => '*',
										'type' => 'LEFT',
										"table" => "bill_infos",
										"alias" => "BillInfo",
// 2016.07.11 murata.s ORANGE-1 CHG(S)
// 										"conditions" => array("BillInfo.demand_id = CommissionInfo.demand_id" , "BillInfo.commission_id = CommissionInfo.id")
										// 入札手数料でないレコードの取得
										// 選定方式が入札の場合、以降の処理で入札手数料を上書きされるため
										// 入札手数料でないレコード(auction_id == null)をここでは取得する
										"conditions" => array(
												"BillInfo.demand_id = CommissionInfo.demand_id" ,
												"BillInfo.commission_id = CommissionInfo.id",
												"BillInfo.auction_id" => null
										)
// 2016.07.11 murata.s ORANGE-1 CHG(E)

								),
								array('fields' => '*',
										'type' => 'LEFT',
										"table" => "m_corp_categories",
										"alias" => "MCorpCategory",
										"conditions" => array("MCorpCategory.corp_id = MCorp.id" , 'MCorpCategory.category_id = DemandInfo.category_id')
								),
								array('fields' => '*',
										'type' => 'inner',
										"table" => "affiliation_infos",
										"alias" => "AffiliationInfo",
										"conditions" => array("AffiliationInfo.corp_id = CommissionInfo.corp_id")
								),
						),
						'hasMany'=>array('CommissionInfo','IntroduceInfo','DemandCorrespondHistory'),
						'conditions' => $conditions,
				)
		);

		//ORANGE-198 iwai ADD S
		if(!empty($results['AuctionInfo']['id']))$results = $this->__set_auction_commission($results['AuctionInfo']['id'], $results);
		//ORANGE-198 iwai ADD E


		return $results;
	}

	/**
	 * 見積書・領収書
	 *
	 * @param string $id 案件ID
	 */
	private function __set_file_url($id = null){

		// JBR見積書、JBR領収書のリンク表示判定
		/*
		if (file_exists(Configure::read('estimate_file_path') . 'estimate_' . $id . '.pdf')) {
			$this->set('estimate_file_url', '/download/estimate/estimate_' . $id . '.pdf');
		}
		*/
		$estimate_file = self::__findFileByFileId(Configure::read('estimate_file_path'), 'estimate_' . $id);
		if (strlen($estimate_file) > 0)
			$this->set('estimate_file_url', '/download/estimate/' . $estimate_file);

		/*
		if (file_exists(Configure::read('receipt_file_path') . 'receipt_' . $id . '.pdf')) {
			$this->set('receipt_file_url', '/download/receipt/receipt_' . $id . '.pdf');
		}
		*/
		$receipt_file = self::__findFileByFileId(Configure::read('receipt_file_path'), 'receipt_' . $id);
		if (strlen($receipt_file) > 0)
			$this->set('receipt_file_url', '/download/receipt/' . $receipt_file);

	}

	/**
	 * 取次先対応履歴データの取得
	 *
	 * @param unknown $conditions
	 * @return unknown
	 */
	private function __set_commission_corresponds($id = null){

		// 取次先対応履歴データの検索条件
		$conditions['CommissionCorrespond.commission_id'] = $id;
		// 加盟店
		if($this->User["auth"] == "affiliation"){
			$conditions['CommissionCorrespond.modified_user_id'] = $this->User["user_id"];
		}

		$joins = array(array (
					'fields' => '*',
					'type' => 'left',
					"table" => "m_users",
					"alias" => "MUser",
					"conditions" => array (
							"cast(CommissionCorrespond.rits_responders as integer) = MUser.id"
					))
		);

		$results = $this->CommissionCorrespond->find('all',
						array( 'fields' => "*, MUser.user_name",
							'conditions' => $conditions,
							'joins' => $joins,
							'order' => array('CommissionCorrespond.id'=> 'DESC'),
						)
		);
		return $results;
	}

	/**
	 * ID別取次先対応履歴データの取得
	 *
	 * @param unknown $conditions
	 * @return unknown
	 */
	private function __get_commission_corresponds($conditions = array()){

		$results = $this->CommissionCorrespond->find('first',
				array( 'fields' => "*",
						'conditions' => $conditions,
						'order' => array('CommissionCorrespond.id'=> 'DESC'),
				)
		);
		return $results;
	}

	/**
	 * ヒヤリング内容の取得
	 *
	 * @param string $id
	 * @return unknown
	 */
	private function __set_inquiry_data($id = null){

		$conditions = array ('DemandInquiryAnswer.demand_id' => $id);
		$InquiryData = $this->DemandInquiryAnswer->find ( 'all', array (
				'fields' => "* ,MInquiry.inquiry_name",
				'joins' => array (
						array (
								'fields' => '*',
								'type' => 'inner',
								"table" => "m_inquiries",
								"alias" => "MInquiry",
								"conditions" => array (
										"MInquiry.id = DemandInquiryAnswer.inquiry_id"
								)
						)
				),
				'conditions' => $conditions,
				'order' => array (
						'DemandInquiryAnswer.id' => 'ASC'
				)
		) );

		return $InquiryData;
	}

        /* 2017-06-23 inokuma ORANGE-32 ADD start */
	/**
	 * 取次先対応履歴データの取得
	 *
	 * @param unknown $conditions
	 * @return unknown
	 */
	private function __set_demand_attached_files($id = null){

		// 取次先対応履歴データの検索条件
		$conditions['DemandAttachedFiles.demand_id'] = $id;

		$results = $this->DemandAttachedFiles->find('all',
						array( 'fields' => "*",
							'conditions' => $conditions,
							'order' => array('DemandAttachedFiles.id' => 'ASC'),
						)
		);
		return $results;
	}
        /* 2017-06-23 inokuma ORANGE-32 ADD end */
        
	/**
	 * 取次先対応履歴の登録
	 *
	 * @param string $commission_id
	 * @param unknown $data
	 * @return boolean
	 */
	private function __regist_history($commission_id = null , $data = array()){

		// 取次IDの指定
		$data['commission_id'] = $commission_id;

		if(!empty($data['responders']) || !empty($data['rits_responders']) || !empty($data['corresponding_contens'])){
			// 新規登録
			if($this->CommissionCorrespond->save($data)){
				return true;
			}
			return false;
		} else {
			return true;
		}
	}

	/**
	 * 取次情報更新日時のチェック
	 *
	 * @param unknown $id
	 * @param unknown $modified
	 * @return boolean
	 */
	private function __check_modified_commission($id , $modified){

		$results = $this->CommissionInfo->findByid($id);

		if($modified == $results['CommissionInfo']['modified']){
			return true;
		}
		return false;
	}

	/**
	 * 取次先対応履歴更新日時のチェック
	 *
	 * @param unknown $id
	 * @param unknown $modified
	 * @return boolean
	 */
	private function __check_modified_commission_corresponds($id , $modified){

		$results = $this->CommissionCorrespond->findByid($id);

		if($modified == $results['CommissionCorrespond']['modified']){
			return true;
		}
		return false;
	}

	/**
	 * 取次情報の編集
	 *
	 * @param string $id
	 * @param unknown $data
	 * @return boolean
	 */
	private function __edit_commission($id = null , $data = array()){

		$set_data = $data['CommissionInfo'];
		$data['CommissionInfo']['id'] = $id;

		// リッツユーザーにて、ステータスを進行中にする場合は、虚偽報告チェックをOFF
		if($this->User["auth"] != "affiliation") {
			unset($this->CommissionInfo->validate['reported_flg']);

			$old_data = $this->CommissionInfo->findById($id);
			$old_status = $old_data['CommissionInfo']['commission_status'];
 			$new_status = $data['CommissionInfo']['commission_status'];

			if ($old_status != Util::getDivValue('construction_status', 'progression') && $new_status == Util::getDivValue('construction_status', 'progression')) {
				$data['CommissionInfo']['reported_flg'] = 0;
			}
		}

		if(empty($data['CommissionInfo']['first_commission'])){
			$data['CommissionInfo']['first_commission'] = 0;
		}
		if(empty($data['CommissionInfo']['unit_price_calc_exclude'])){
			$data['CommissionInfo']['unit_price_calc_exclude'] = 0;
		}
		if(empty($data['CommissionInfo']['commission_order_fail_reason'])){
			$data['CommissionInfo']['commission_order_fail_reason'] = 0;
		}

                // 日付のフォーマット
                $data['CommissionInfo']['complete_date'] = str_replace("-", "/", $data['CommissionInfo']['complete_date']);
                $data['CommissionInfo']['order_fail_date'] = str_replace("-", "/", $data['CommissionInfo']['order_fail_date']);

		//2015.03.31 y.kurokawa
		if($this->request->data['hidden_last_updated'] == 1){
			$data['CommissionInfo']['commission_status_last_updated'] = date("Y-m-d G:i:s");
		}

		$save_data = array('CommissionInfo' => $data['CommissionInfo'], 'DemandInfo' => $data['DemandInfo'], 'BillInfo' => $data['BillInfo']);

		// 更新
		if($this->CommissionInfo->save($save_data)){
			return true;
		}
		return false;
	}

	/**
	 * 案件情報の編集
	 *
	 * @param unknown $data
	 */
	private function __edit_demand($data = array()){

		unset($data['DemandInfo']['demand_status']);
                // 日付のフォーマット
                $data['DemandInfo']['order_date'] = str_replace("-", "/", $data['DemandInfo']['order_date']);

		$save_data = array('DemandInfo' => $data['DemandInfo']);

		// 更新
		if($this->DemandInfo->save($save_data)){
			return true;
		}
		return false;

	}

	/**
	 * 取次先対応履歴の編集
	 *
	 * @param string $id
	 * @param unknown $data
	 * @return boolean
	 */
	private function __edit_history($id = null , $data = array()){

		$set_data = $data['CommissionCorrespond'];
		$set_data['id'] = $id;
		$save_data = array('CommissionCorrespond' => $set_data);
		// 更新
		if($this->CommissionCorrespond->save($save_data)){
			return true;
		}
		return false;
	}

	/**
	 * 消費税率取得
	 *
	 * @param unknown $data
	 * @return unknown
	 */
	private function __set_m_tax_rates($data = null){

		if(!empty($data)){
			$conditions = array('start_date <=' => $data ,
								'or' => array("end_date = ''",
												'end_date >=' => $data),
								);
			$results = $this->MTaxRate->find('first',
					array( 'conditions' => $conditions,)
			);
			$results['MTaxRate']['tax_rate'] = $results['MTaxRate']['tax_rate']*100;
		} else {
			$results['MTaxRate']['tax_rate'] = '';
		}
		return $results;
	}

	/**
	 * m_commission_alert_settings_telのデータを取得
	 *
	 * @param unknown $data
	 * @return unknown
	 */
	private function __mCommissionAlertSettingsTel($data = null){
		// 検索条件
		$conditions = array('MCommissionAlertSetting.correspond_status' => $data,
			'and' => array('MCommissionAlertSetting.phase_id' => '0')
		);
		$results_data = $this->MCommissionAlertSetting->find('first',
			array( 'fields' => '*',
				'conditions' => $conditions,
			)
		);
		return $results_data;
	}
	/**
	 * m_commission_alert_settings_visitのデータを取得
	 *
	 * @param unknown $data
	 * @return unknown
	 */
	private function __mCommissionAlertSettingsVisit($data = null){
		// 検索条件
		$conditions = array('MCommissionAlertSetting.correspond_status' => $data,
			'and' => array('MCommissionAlertSetting.phase_id' => '1')
		);
		$results_data = $this->MCommissionAlertSetting->find('first',
			array( 'fields' => '*',
				'conditions' => $conditions,
			)
		);
		return $results_data;
	}
	/**
	 * m_commission_alert_settings_orderのデータを取得
	 *
	 * @param unknown $data
	 * @return unknown
	 */
	private function __mCommissionAlertSettingsOrder($data = null){
		// 検索条件
		$conditions = array('MCommissionAlertSetting.correspond_status' => $data,
			'and' => array('MCommissionAlertSetting.phase_id' => '2')
		);
		$results_data = $this->MCommissionAlertSetting->find('first',
			array( 'fields' => '*',
				'conditions' => $conditions,
			)
		);
		return $results_data;
	}

	/**
	 * 請求情報の更新
	 *
	 * @param string $id
	 * @param unknown $data
	 * @return boolean
	 */
	private function __regist_bill_info($id = null , $data = array()){

		$set_data = $data['BillInfo'];

// 2016.08.25 murata.s ORANGE-130 ADD(S)
		if($data['CommissionInfo']['commission_status'] == Util::getDivValue('construction_status','introduction')
				&& $data['CommissionInfo']['introduction_free'] == 1){
			$set_data['fee_target_price'] = 0;
			$set_data['fee_tax_exclude'] = 0;
		}
// 2016.08.26 murata.s ORANGE-130 ADD(E)

		$set_data['demand_id'] = $data['CommissionInfo']['demand_id'];													//案件ID
		$set_data['commission_id'] = $id;																				// 取次ID
		if(isset($data['CommissionInfo']['deduction_tax_include'])){
			$set_data['deduction_tax_include'] = $data['CommissionInfo']['deduction_tax_include'];  					// 控除金額(税込)
		}
		if(isset($data['CommissionInfo']['deduction_tax_exclude'])){
			$set_data['deduction_tax_exclude'] = $data['CommissionInfo']['deduction_tax_exclude'];  					// 控除金額(税抜)
		}
		// 個別請求処理案件
		if(isset($data['CommissionInfo']['irregular_fee_rate'])){
			$set_data['irregular_fee_rate'] = $data['CommissionInfo']['irregular_fee_rate'];  							// イレギュラー手数料率
		}
		if(isset($data['CommissionInfo']['irregular_fee'])){
			$set_data['irregular_fee'] = $data['CommissionInfo']['irregular_fee'];  									// イレギュラー手数料金額
		}
		if(isset($data['CommissionInfo']['confirmd_fee_rate'])){
			$set_data['comfirmed_fee_rate'] = $data['CommissionInfo']['confirmd_fee_rate']; 							// 確定手数料率
		}
		$set_data['tax'] = $set_data['fee_tax_exclude']*($data['MTaxRate']['tax_rate']/100);  							// 消費税
		if(isset($data['insurance_price'])){
			$set_data['insurance_price'] = $data['insurance_price']; 													// 保険料金額
		} else {
			$set_data['insurance_price'] = 0;
		}
		$set_data['total_bill_price'] = $set_data['fee_tax_exclude']+$set_data['tax']+$set_data['insurance_price'];		// 合計請求金額
		// 初期設定
		if(empty($data['BillInfo']['id'])){
			$set_data['bill_status'] = 1;  																				//ステータス
			$set_data['fee_payment_price'] = 0;																			// 手数料入金金額
			$set_data['fee_payment_balance'] = $set_data['total_bill_price'];											// 手数料入金残高
		}
		// 2015.08.16 s.harada ADD end ORANGE-777
		else {
			if (empty($set_data['fee_payment_price'])) {
				$set_data['fee_payment_price'] = 0;
			}
			$set_data['fee_payment_balance'] = $set_data['total_bill_price'] - $set_data['fee_payment_price'];
		}

		// 2015.08.16 s.harada ADD end ORANGE-777
		unset($this->BillInfo->validate['bill_status']);
		if($this->BillInfo->save($set_data)){
			return true;
		}
		return false;
	}

	/**
	 * 請求情報の削除
	 *
	 * @param string $id
	 * @return boolean
	 */
	private function __delete_bill_info($id = null){
		if($this->BillInfo->delete($id)){
			return true;
		}
		return false;
	}


	/**
	 * GETパラメーターをセッションに保存
	 *
	 */
	private function __set_parameter_session(){
		$this->Session->delete(self::$__sessionKeyForCommissionParameter);
		$this->Session->write(self::$__sessionKeyForCommissionParameter, $this->params['named']);
	}

	/**
	 * PDFファイルの一時アップロード処理
	 *
	 * @param unknown_type $tempfile
	 */
	private function __uplaod_file_tmp($tempfile) {

		// 一時ファイル名の生成(uniqueな文字列)
		$file_name = uniqid(rand(), true);
		$uploadfile = Configure::read('temporary_file_path') . $file_name;

		// ファイルアップロード
		if (!move_uploaded_file($tempfile, $uploadfile)) {
			$file_name = "";
		}

		return $file_name;
	}

	/**
	 * PDFファイルのアップロード処理(一時ファイル→本ファイル)
	 *
	 * @param unknown_type $data
	 */
	private function __uplaod_file($data) {
		// 案件IDの取得
		$demand_id = $data['DemandInfo']['id'];
		// [JBR]見積書、領収書ファイルのアップロード
		if (!empty($data['DemandInfo']['tmp_estimate_file'])) {
			$extension = pathinfo($data['DemandInfo']['jbr_estimate']['name'], PATHINFO_EXTENSION);
			$file_id = 'estimate_' . $demand_id;
			$file_name = $file_id . (strlen($extension) > 0 ? '.' . $extension : "");    //'.pdf';
			$uploadfile = Configure::read('estimate_file_path') . $file_name;
			$tempfile = Configure::read('temporary_file_path') . $data['DemandInfo']['tmp_estimate_file'];
			//同一IDのファイルが存在する場合は削除
			$current_file = self::__findFileByFileId(Configure::read('estimate_file_path') ,$file_id);
			if (strlen($current_file) > 0) @unlink(Configure::read('estimate_file_path') . $current_file);

			// ファイルアップロード
			if (!rename($tempfile, $uploadfile)){
				return false;
			}
		}
		if (!empty($data['DemandInfo']['tmp_receipt_file'])) {
			$extension = pathinfo($data['DemandInfo']['jbr_receipt']['name'], PATHINFO_EXTENSION );
			$file_id = "receipt_" . $demand_id;
			$file_name = $file_id . (strlen($extension) > 0 ? '.' . $extension : "");
			$uploadfile = Configure::read('receipt_file_path') . $file_name;
			$tempfile = Configure::read('temporary_file_path') . $data['DemandInfo']['tmp_receipt_file'];
			//同一IDのファイルが存在する場合は削除
			$current_file = self::__findFileByFileId(Configure::read('receipt_file_path') ,$file_id);
			if (strlen($current_file) > 0) @unlink(Configure::read('receipt_file_path') . $current_file);

			// ファイルアップロード
			if (!rename($tempfile, $uploadfile)) {
				return false;
			}

		}

		return true;
	}

	private function __findFileByFileId($path, $id) {
		$ret = "";

		if ($dh = opendir($path)) {
			$pattern = '/^' . $id . '/';
			while (($file = readdir($dh)) != false) {
				if (preg_match($pattern, $file)) {
					$ret = $file;
					break;
				}
			}
			closedir($dh);
		}
		return $ret;
	}
	/**
	 *
	 *
	 * @param unknown $conditions
	 * @return unknown
	 */
	private function __set_other_commission($id = null, $demand_id = null){

		// 2015.05.23 h.hara(S)
		$result = false;
		// 2015.05.23 h.hara(E)

// 		// 取次先対応履歴データの検索条件
 		$conditions['CommissionInfo.demand_id'] = $demand_id;
 		$conditions['CommissionInfo.id != '] = $id;

 		$data = $this->CommissionInfo->find('all',
 				array( 'fields' => "*",
 						'conditions' => $conditions,
 				)
 		);
		// 2015.05.23 h.hara(S)
		if (count($data) == 0) {
			$result = true;
		}
		else {
	 		for($i = 0; $i < count($data); $i++) {
				$this->CommissionInfo->id = $data[$i]['CommissionInfo']['id'];
				$this->CommissionInfo->saveField('unit_price_calc_exclude','1');
				$result = true;
	 		}
		}
		// 2015.05.23 h.hara(E)
		if ($result) {
	 		return true;
		}
		return false;

	}

	/**
	 * JBR情報初期値設定 ORANGE-522
	 *
	 * @author tanaka
	 * @since 2015.06.08
	 * @param array $data
	 * @return array Commission Data
	 */
	private function __setDefaultJbrinfo($data) {
		// JBR見積書状況の初期値を設定

		if (empty($this->request->data['DemandInfo']['jbr_estimate_status']) && !$this->RequestHandler->isPost()) {
			switch ($data['DemandInfo']['genre_id']) {
				case Configure::read('jbr_glass_genre_id'):
					$data['DemandInfo']['jbr_estimate_status'] = Util::getDivValue ( 'estimate_status', 'mikaisyu' );
					break;
				case Configure::read('jbr_moving_genre_id'):
					$data['DemandInfo']['jbr_estimate_status'] = '';
					break;
				default:
					$data['DemandInfo']['jbr_estimate_status'] = '';
					break;
			}
		} else {
			$data['DemandInfo']['jbr_estimate_status'] = $this->request->data['DemandInfo']['jbr_estimate_status'];
		}

		// 領収書状況の初期値を設定
		if (empty($this->request->data['DemandInfo']['jbr_receipt_status']) && !$this->RequestHandler->isPost()) {
			switch ($data['DemandInfo']['genre_id']) {
				case Configure::read('jbr_glass_genre_id'):
					$data['DemandInfo']['jbr_receipt_status'] = Util::getDivValue ( 'receipt_status', 'mikaisyu' );
					break;
				case Configure::read('jbr_moving_genre_id'):
					$data['DemandInfo']['jbr_receipt_status'] = '';
					break;
				default:
					$data['DemandInfo']['jbr_receipt_status'] = Util::getDivValue ( 'receipt_status', 'mikaisyu' );
					break;
			}
		} else {
			$data['DemandInfo']['jbr_receipt_status'] = $this->request->data['DemandInfo']['jbr_receipt_status'];
		}

		return $data;
	}

	/**
	 * 対応履歴をセット
	 *
	 * @author tanaka
	 * @since 2015.10.13
	 * @param int   $id
	 * @param array $data
	 * @return array
	 */
	private function __setSupport($id, $data) {

		$data += $this->CommissionOrderSupport->find('first', array(
			'conditions' => array('CommissionOrderSupport.commission_id' => $id),
			'order' => array('CommissionOrderSupport.modified' => 'desc')
		));
		$data += $this->CommissionTelSupport->find('first', array(
			'conditions' => array('CommissionTelSupport.commission_id' => $id),
			'order' => array('CommissionTelSupport.modified' => 'desc')
		));

		$data += $this->CommissionVisitSupport->find('first', array(
			'conditions' => array('CommissionVisitSupport.commission_id' => $id),
			'order' => array('CommissionVisitSupport.modified' => 'desc')
		));

		return $data;
	}

	/**
	 * 案件差し戻し処理 ORANGE-560
	 *
	 * @author absystems
	 * @since 2015.06.13
	 * @param int   $id
	 * @param array $data
	 * @return array boolean
	 */
	private function __demand_send_back($id, $data) {
		$this->CommissionInfo->begin();
		$this->DemandInfo->begin();
		//$this->CommissionCorrespond->begin();
		$this->DemandCorrespond->begin();
		try {
			//TODO:CommissionInfoの更新
			// 2015.10.10 更新データに差戻担当者と差戻理由を追加
			// 2016.01.04 n.kai ORANGE-978 取次データに差し戻しフラグを追加
			$commission_data = array('CommissionInfo' => array(
											'id' => $id,
											'lost_flg' => 1,
											'commit_flg' => 0,
											'remand_flg' => 1,
											'remand_correspond_person' => $data['sd_remand_responder'],
											'remand_reason' => $data['sd_remand_msg'])
			);
			$this->CommissionInfo->save($commission_data);
			//TODO:DemandInfoの更新
			// 2016.01.04 n.kai ORANGE-978 案件の差し戻しフラグを「1」にする。合わせてバリデーションしないよう対応
			$demand_data = array('DemandInfo' => array('id' => $data['DemandInfo']['id'],
					'demand_status' => 1,
					'remand' => 1));
			$this->DemandInfo->save($demand_data, false);
			//TODO:CommissionCorrespondの更新
			//$commission_correspond_data = array('CommissionCorrespond' => array('commission_id' => $id,
			//		'correspond_datetime' => $data['CommissionCorrespond']['correspond_datetime'],
			//		'responders' => $this->User['user_name'],
			//		'corresponding_contens' => $data['MCorp']['official_corp_name'] . '様から差戻しボタン押印されました。'));
			//$this->CommissionCorrespond->save($commission_correspond_data);
			//TODO:DemandCorrespondの更新
			$demand_correspond_data = array('DemandCorrespond' => array('demand_id' => $data['DemandInfo']['id'],
					'correspond_datetime' => date("Y-m-d H:i:s"),
					'responders' => $this->User['id'],
					'corresponding_contens' => $data['MCorp']['official_corp_name'] . '様から差戻しボタン押印されました。' . "\r\n" .
												'担当者：' .  $data['sd_remand_responder'] . "  差戻し理由：" . $data['sd_remand_msg'] ));
			$this->DemandCorrespond->save($demand_correspond_data);

			$this->CommissionInfo->commit();
			$this->DemandInfo->commit();
			// 2015.6.19 n.kai MOD start
			//$this->CommissionCorrespond->commit();
			$this->DemandCorrespond->commit();
			// 2015.6.19 n.kai MOD end

			// メール配信
			// 2016.01.05 n.kai ORANGE-978 コメントアウトを解除
			self::__send_mail_demand_sendback($data['DemandInfo']['id']);
		} catch(Exception $e) {
			$this->CommissionInfo->rollback();
			$this->DemandInfo->rollback();
			// 2015.6.19 n.kai MOD start
			//$this->CommissionCorrespond->rollback();
			$this->DemandCorrespond->rollback();
			// 2015.6.19 n.kai MOD end

			return false;
		}

		return true;
	}

	private function __update_upload_file_name($data) {
		try {
			$demand_info = array('DemandInfo' => array('id' => $data['DemandInfo']['id']));
			if (strlen($data['DemandInfo']['jbr_estimate']['name']) > 0) {
				$demand_info['DemandInfo']['jbr_estimate'] = $data['DemandInfo']['jbr_estimate'];
				$demand_info['DemandInfo']['upload_estimate_file_name'] = $data['DemandInfo']['jbr_estimate']['name'];
			}
			if (strlen($data['DemandInfo']['jbr_receipt']['name']) > 0) {
				$demand_info['DemandInfo']['jbr_receipt'] = $data['DemandInfo']['jbr_receipt'];
				$demand_info['DemandInfo']['upload_receipt_file_name'] = $data['DemandInfo']['jbr_receipt']['name'];
			}
			if (count($demand_info['DemandInfo']) > 1) {
				$this->DemandInfo->save($demand_info);
			}

		} catch (Exception $e) {
			return false;
		}

		return true;
	}

	private function __send_mail_demand_sendback($demand_id) {
		$to = "sashimodoshi@rits-c.jp";
		$from = "sashimodoshi@rits-c.jp";
		$subject = "《" . $demand_id .  "》加盟店より案件の差戻しがありました。";
		$body = "案件を確認し、至急の対応をお願いします。";

		mb_send_mail($to, $subject, $body, "From: " . $from);
	}

	public function send($data, $id) {
		$to = "mailback@rits-c.jp";
		$from = "mailback@rits-c.jp";
		$subject = "《" . $data["DemandInfo"]["id"] . "》加盟店からの画像アップロードがありました。";
		$body = "取次ぎ管理のURL: " . Router::url('/commission/detail/', true) . $id;
		$attachments = array();

		if (!empty($data['DemandInfo']['tmp_estimate_file'])) {
			$file_id = 'estimate_' . $data["DemandInfo"]["id"];
			$current_file = self::__findFileByFileId(Configure::read('estimate_file_path') ,$file_id);
			self::__imageResize(Configure::read('estimate_file_path'), $current_file, $data['DemandInfo']['jbr_estimate']['name'], 1000000);
			$attachments[] = Configure::read('estimate_file_path') . $data['DemandInfo']['jbr_estimate']['name'];
		}

		if (!empty($data['DemandInfo']['tmp_receipt_file'])) {
			$file_id = "receipt_" . $data["DemandInfo"]["id"];
			$current_file = self::__findFileByFileId(Configure::read('receipt_file_path') ,$file_id);
			self::__imageResize(Configure::read('receipt_file_path'), $current_file, $data['DemandInfo']['jbr_receipt']['name'], 1000000);
			$attachments[] = Configure::read('receipt_file_path') . $data['DemandInfo']['jbr_receipt']['name'];
		}

		if (count($attachments) == 0) return false;

		$email = new CakeEmail();
		$email->from($from);
		$email->to($to);
		$email->subject($subject);
		$email->attachments($attachments);
		$email->send($body);

		//メール送信完了後、リサイズされたファイルは削除
		foreach ($attachments as $f) {
			@unlink($f);
		}

	}

	private function __imageResize($path, $file_name, $new_file_name, $targetSize) {
		//PDF
		$extension = pathinfo($path . $file_name, PATHINFO_EXTENSION );
		if (strtoupper($extension) == 'PDF') {
			if (!copy($path . $file_name, $path . $new_file_name)) return false;
			return true;
		}

		//画像タイプの取得
		$type = exif_imagetype($path . $file_name);
		$image = self::__imageCreateAll($type, $path, $file_name);
		if (!$image) return false;

		$image_x = imagesx($image);
		$image_y = imagesy($image);

		//最大サイズ 1Kとする
		$size = filesize($path . $file_name);
		if ( $size > $targetSize) {
			$resize_rate = $targetSize / $size;
			$resize_image_x = sqrt($resize_rate) * $image_x * 1.25;
			$resize_image_y = sqrt($resize_rate) * $image_y * 1.25;

			$nimage = imagecreatetruecolor($resize_image_x, $resize_image_y);
			if (!$nimage) {
				imagedestroy($image);
				return false;
			}

			if (!imagecopyresampled($nimage, $image, 0, 0, 0, 0, $resize_image_x, $resize_image_y, $image_x, $image_y)) {
				imagedestroy($image);
				imagedestroy($nimage);
				return false;
			}
			if (!self::__imageWrite($type, $nimage, $path, $new_file_name)) {
				imagedestroy($image);
				imagedestroy($nimage);
				return false;
			}
			imagedestroy($image);
			imagedestroy($nimage);
		} else {
			imagedestroy($image);

			if (!copy($path . $file_name, $path . $new_file_name)) return false;
		}

		return true;
	}

	private function __imageCreateAll($type, $path, $file_name) {
		switch ($type) {
			case 1:
				$image = @imageCreateFromGif($path . $file_name);
				break;
			case 2:
				$image = @imageCreateFromJpeg($path . $file_name);
				break;
			case 3:
				$image = @imageCreateFromPng($path . $file_name);
				break;
			case 6:
				$image = @self::__imageCreateFromBMP($path . $file_name);
				break;
			default:
				return null;
		}

		return $image;
	}

	private function __imageWrite($type, $image, $path, $file_name) {
		switch ($type) {
			case 1:
				$result = imagegif($image, $path . $file_name);
				break;
			case 2:
				$result = imagejpeg($image, $path . $file_name);
				break;
			case 3:
				$result = imagepng($image, $path . $file_name);
				break;
			case 6:
				$result = imagejpeg($image, $path . $file_name);
				break;
			default: //エラー処理
				return false;
		}

		return $result;
	}

	private function __ImageCreateFromBMP($filename){
		//  画像ファイルをバイナリーモードでopen
		if (! $f1 = fopen($filename,"rb")) return FALSE;

		//1 : 概要データのロード：file_type, file_size, reserved, bitmap_offset
		$FILE = unpack("vfile_type/Vfile_size/Vreserved/Vbitmap_offset", fread($f1,14));

		//1' : file type のチェック
		if ($FILE['file_type'] != 19778) return FALSE;
		// 19778=> 0x4D42 ->`MB`  先頭２バイトに BM と入っている、リトルエンディアンで読み出すとMB となる

		//2 : BMPデータのロード：
		$BMP = unpack('Vheader_size/Vwidth/Vheight/vplanes/vbits_per_pixel'.
				'/Vcompression/Vsize_bitmap/Vhoriz_resolution'.
				'/Vvert_resolution/Vcolors_used/Vcolors_important', fread($f1,40));
		$BMP['colors'] = pow(2,$BMP['bits_per_pixel']);

		//2' : pixel情報のセット
		if ($BMP['size_bitmap'] == 0) $BMP['size_bitmap'] = $FILE['file_size'] - $FILE['bitmap_offset'];
		$BMP['bytes_per_pixel'] = $BMP['bits_per_pixel']/8;
		$BMP['bytes_per_pixel2'] = ceil($BMP['bytes_per_pixel']);
		$BMP['decal'] = ($BMP['width']*$BMP['bytes_per_pixel']/4);
		$BMP['decal'] -= floor($BMP['width']*$BMP['bytes_per_pixel']/4);
		$BMP['decal'] = 4-(4*$BMP['decal']);
		if ($BMP['decal'] == 4) $BMP['decal'] = 0;

		//3 : paletteデータのロード：
		//  16 bit images (= color 65536 )以上ではパレットを持っていないので、8bit colorまでを対象とする
		$PALETTE = array();
		if ($BMP['colors'] <  65536){
			$PALETTE = unpack('V'.$BMP['colors'], fread($f1,$BMP['colors']*4));
		}

		//4 : imageデータのロード：ビットごとの色情報読みとり
		$IMG = fread($f1,$BMP['size_bitmap']);
		//4' : file からの読みとり完了
		fclose($f1);

		//5 : GD による TrueColor イメージ作成
		$res = imagecreatetruecolor($BMP['width'],$BMP['height']);
		$P = 0;
		$Y = $BMP['height']-1;
		$VIDE = chr(0);	//  桁合わせ用
		//5' :  TrueColor イメージの各ビットに色設定
		while ($Y >= 0){
		$X=0;
		while ($X < $BMP['width']){
				if ($BMP['bits_per_pixel'] == 24){
				$COLOR = unpack("V",substr($IMG,$P,3).$VIDE);
		}elseif ($BMP['bits_per_pixel'] == 16){
		$COLOR = unpack("v",substr($IMG,$P,2));
		$blue = ($COLOR[1] & 0x001f) << 3;
		$green = ($COLOR[1] & 0x07e0) >> 3;
		$red = ($COLOR[1] & 0xf800) >> 8;
		$COLOR[1] = $red * 65536 + $green * 256 + $blue;
		}elseif ($BMP['bits_per_pixel'] == 8){
		// 8bit palette mode, 256colors
		$COLOR = unpack("n",$VIDE.substr($IMG,$P,1));
		$COLOR[1] = $PALETTE[$COLOR[1]+1];
		}elseif ($BMP['bits_per_pixel'] == 4){
		$COLOR = unpack("n",$VIDE.substr($IMG,floor($P),1));
		if (($P*2)%2 == 0) $COLOR[1] = ($COLOR[1] >> 4) ; else $COLOR[1] = ($COLOR[1] & 0x0F);
		$COLOR[1] = $PALETTE[$COLOR[1]+1];
		}elseif ($BMP['bits_per_pixel'] == 1){
		$COLOR = unpack("n",$VIDE.substr($IMG,floor($P),1));
			if (($P*8)%8 == 0) $COLOR[1] = $COLOR[1] >>7;
			elseif (($P*8)%8 == 1) $COLOR[1] = ($COLOR[1] & 0x40)>>6;
			elseif (($P*8)%8 == 2) $COLOR[1] = ($COLOR[1] & 0x20)>>5;
			elseif (($P*8)%8 == 3) $COLOR[1] = ($COLOR[1] & 0x10)>>4;
			elseif (($P*8)%8 == 4) $COLOR[1] = ($COLOR[1] & 0x8)>>3;
			elseif (($P*8)%8 == 5) $COLOR[1] = ($COLOR[1] & 0x4)>>2;
			elseif (($P*8)%8 == 6) $COLOR[1] = ($COLOR[1] & 0x2)>>1;
			elseif (($P*8)%8 == 7) $COLOR[1] = ($COLOR[1] & 0x1);
			$COLOR[1] = $PALETTE[$COLOR[1]+1];
		}else{
		return FALSE;
		}
		imagesetpixel($res,$X,$Y,$COLOR[1]);
		// 1 dot 処理完了
		$X++;
		$P += $BMP['bytes_per_pixel'];
	}
	//  x 方向完了
	$Y--;
	$P+=$BMP['decal'];
	}
	//  all line end

	//6 : 作業終了： TrueColor イメージを返す
	return $res;
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

	// 2015.09.14 s.harada ADD start 画面デザイン変更対応
	/**
	 * 入力計算
	 */
	private function __create_request_data() {

		if ($this->request->data['CommissionInfo']['commission_status'] != Util::getDivValue('construction_status','introduction')) {

			// 施工金額(税抜)
			if (isset($this->request->data['CommissionInfo']['construction_price_tax_exclude']) && $this->request->data['CommissionInfo']['construction_price_tax_exclude'] != "") {
				$construction_price_tax_exclude = $this->request->data['CommissionInfo']['construction_price_tax_exclude'];
			} else {
				$construction_price_tax_exclude = 0;
			}
			// 出張費(税抜)
			if (isset($this->request->data['CommissionInfo']['business_trip_amount']) && $this->request->data['CommissionInfo']['business_trip_amount'] != "") {
				$business_trip_amount = $this->request->data['CommissionInfo']['business_trip_amount'];
			} else {
				$business_trip_amount = 0;
			}
			// 控除金額(税込)
			if (isset($this->request->data['CommissionInfo']['deduction_tax_include']) && $this->request->data['CommissionInfo']['deduction_tax_include'] != "") {
				$deduction_tax_include = $this->request->data['CommissionInfo']['deduction_tax_include'];
			} else {
				$deduction_tax_include = 0;
			}
			// 消費税
			if (isset($this->request->data['MTaxRate']['tax_rate']) && $this->request->data['MTaxRate']['tax_rate'] != "") {
				$tax_rate = $this->request->data['MTaxRate']['tax_rate'];
			} else {
				$tax_rate_data = self::__set_m_tax_rates($this->request->data['CommissionInfo']['complete_date']);
				$tax_rate = $tax_rate_data['MTaxRate']['tax_rate'];
			}
			// 控除金額(税抜)
			$this->request->data['CommissionInfo']['deduction_tax_exclude'] = 0;
			if ($tax_rate != '') {
				// 施工金額(税抜)が空白の場合、無条件に計算すると税込に「0」がセットされるため、税込が0以上の場合のみ計算する
				if ($construction_price_tax_exclude != '' && $construction_price_tax_exclude > 0) {
					/***** 施工金額(税込) 計算 ******/
					$tax_rate_val = $tax_rate * 0.01;
					$this->request->data['CommissionInfo']['construction_price_tax_include'] = round($construction_price_tax_exclude * (1 + $tax_rate_val));
				}

				/***** 控除金額(税抜) 計算 ******/
				$this->request->data['CommissionInfo']['deduction_tax_exclude'] = 0;
				if(isset($this->request->data['CommissionInfo']['deduction_tax_include']) && $this->request->data['CommissionInfo']['deduction_tax_include'] != 0){
					if(preg_match("/^[0-9]+$/", $tax_rate) && preg_match("/^[0-9]+$/", $this->request->data['CommissionInfo']['deduction_tax_include'])) {
						$tax_rate_val = $tax_rate * 0.01;
						$this->request->data['CommissionInfo']['deduction_tax_exclude'] = round($this->request->data['CommissionInfo']['deduction_tax_include'] / (1 + $tax_rate_val));
					}
				}
			}

			/***** 手数料対象金額 計算 ******/
			$fee_target_price = 0;
			// if ($construction_price_tax_exclude != 0 && $deduction_tax_exclude != 0) {
			if ($construction_price_tax_exclude != 0) {
				if(preg_match("/^[0-9]+$/", $construction_price_tax_exclude) && preg_match("/^[0-9]+$/", $this->request->data['CommissionInfo']['deduction_tax_exclude'])) {
					$fee_target_price = $construction_price_tax_exclude + $business_trip_amount - $this->request->data['CommissionInfo']['deduction_tax_exclude'];	// 手数料対象金額 = 施工金額(税抜) - 控除金額(税抜)
				}
			}
			$this->request->data['BillInfo']['fee_target_price'] = $fee_target_price;

			if ($this->request->data['MGenre']['insurant_flg'] == 1 && $this->request->data['AffiliationInfo']['liability_insurance'] == 2) {
				/***** 保険料金額 計算 ******/
				$insurance_price = round(($construction_price_tax_exclude + $business_trip_amount) * 0.01);		// 保険料金額 = 施工金額(税抜)× 0.01
				$this->request->data['insurance_price'] = $insurance_price;
			}
		}

		// ----------------------------------------------------- 受注手数料単位 -----------------------------------------------------
		// *************************************** 受注手数料単位が%の時 ***************************************
		if ($this->request->data['MCorpCategory']['order_fee_unit'] !== 0 && $this->request->data['CommissionInfo']['commission_status'] != Util::getDivValue('construction_status','introduction')) {

			/***** 値を取得 ******/
			$commission_fee_rate = $this->request->data['CommissionInfo']['commission_fee_rate'];		// 取次時手数料率
			$irregular_fee_rate = $this->request->data['CommissionInfo']['irregular_fee_rate'];		// イレギュラー手数料率
			$irregular_fee = $this->request->data['CommissionInfo']['irregular_fee'];					// イレギュラー手数料

			/***** 確定手数料率 ******/
			if($irregular_fee_rate != '' && $irregular_fee_rate != 0){
				$confirmd_fee_rate = $irregular_fee_rate;
			} else {
				$confirmd_fee_rate = $commission_fee_rate;
			}
			$this->request->data['CommissionInfo']['confirmd_fee_rate'] = $confirmd_fee_rate;

			if($irregular_fee != '' && $irregular_fee != 0){
				$fee_tax_exclude = $irregular_fee;
			} else {
				/***** 取次先手数料 計算 ******/
				$fee_tax_exclude = round($fee_target_price * $confirmd_fee_rate * 0.01);  // 取次先手数料 = 手数料対象金額 × 確定手数料率
			}
			$this->request->data['CommissionInfo']['corp_fee'] = $fee_tax_exclude;
			$this->request->data['BillInfo']['fee_tax_exclude'] = $fee_tax_exclude;

		} else {
			// *************************************** 受注手数料単位が円の時 ***************************************

			/***** 値を取得 ******/
			$corp_fee = $this->request->data['CommissionInfo']['corp_fee'];				// 確定手数料
			$irregular_fee = $this->request->data['CommissionInfo']['irregular_fee'];		// イレギュラー手数料

			if($irregular_fee != '' && $irregular_fee != 0){
				/***** 取次先手数料 入力＆表示 ******/
				$fee_tax_exclude = $irregular_fee;
			} else {
				$fee_tax_exclude = $corp_fee;
			}
			$this->request->data['BillInfo']['fee_tax_exclude'] = $fee_tax_exclude;

			// 取次状況が紹介済みの場合
			if ($this->request->data['CommissionInfo']['commission_status'] == Util::getDivValue('construction_status','introduction')) {
				$this->request->data['BillInfo']['fee_target_price'] = $fee_tax_exclude;
			}

		}

	}
	// 2015.09.14 s.harada ADD end 画面デザイン変更対応

	/*
	 * データが不足している場合はtrue/データが足りている場合はfalse
	 */
	private function __doNotHavaEnoughData($affiliation_id) {
		$m_corps = $this->MCorp->findById($affiliation_id);
		$m_corp_categories =$this->MCorpCategory->find('all',
        	array(
				'conditions' => array(
						'corp_id =' . $affiliation_id
				)
        	)
        );

		//この下に必須チェックを追加
		// 担当者
		if (strlen($m_corps['MCorp']['corp_person']) == 0) return true;
		// 責任者
		if (strlen($m_corps['MCorp']['responsibility']) == 0) return true;
		// 住所
		if (strlen($m_corps['MCorp']['address1']) == 0) return true;
		if (strlen($m_corps['MCorp']['address2']) == 0) return true;
		if (strlen($m_corps['MCorp']['address3']) == 0) return true;
		// 電話番号
		if (strlen($m_corps['MCorp']['tel1']) == 0) return true;
		// PCメール
		//if (strlen($m_corps['MCorp']['mailaddress_pc']) == 0) return true;
		switch ($m_corps['MCorp']['coordination_method']) {
			case Util::getDivValue('coordination_method', 'mail_fax'):
			case Util::getDivValue('coordination_method', 'mail'):
			case Util::getDivValue('coordination_method', 'mail_app'):
			case Util::getDivValue('coordination_method', 'mail_fax_app'):
				if (empty($m_corps['MCorp']['mailaddress_pc'])) {
					return true;
				}
				break;
			default:
				break;
		}
		// 携帯メール
		if ($m_corps['MCorp']['corp_commission_type'] != 2) {
			switch ($m_corps['MCorp']['coordination_method']) {
				case Util::getDivValue('coordination_method', 'mail_fax'):
				case Util::getDivValue('coordination_method', 'mail'):
					if ( $m_corps['MCorp']['mobile_mail_none'] != 1 && empty($m_corps['MCorp']['mailaddress_mobile']) ) {
						return true;
					}
					break;
				case Util::getDivValue('coordination_method', 'mail_app'):
				case Util::getDivValue('coordination_method', 'mail_fax_app'):
					if ( empty($m_corps['MCorp']['mailaddress_mobile']) ) {
						return true;
					}
					break;
				default:
					break;
			}
		}
		// 取次用ダイヤル
		if ($m_corps['MCorp']['corp_commission_type'] != 2) {
			if ( empty($m_corps['MCorp']['commission_dial']) ) {
				return true;
			}
		}
		// 取次方法
		if (strlen($m_corps['MCorp']['coordination_method']) == 0) return true;
		// FAX番号
		switch ($m_corps['MCorp']['coordination_method']) {
			case Util::getDivValue('coordination_method', 'mail_fax'):
			case Util::getDivValue('coordination_method', 'fax'):
			case Util::getDivValue('coordination_method', 'mail_fax_app'):
				if (empty($m_corps['MCorp']['fax'])) {
					return true;
				}
				break;
			default:
				break;
		}
		// 営業時間
		if ( ($m_corps['MCorp']['support24hour'] != 1) && ($m_corps['MCorp']['available_time_other'] != 1) ) {
			// 24時間対応、その他ともに未チェックの場合エラー
			return true;
		}
		if ( ($m_corps['MCorp']['support24hour'] == 1) && ($m_corps['MCorp']['available_time_other'] == 1) ) {
			// 24時間対応、その他ともにチェックありの場合エラー(どちらか一方選択)
			return true;
		}
		if ( ($m_corps['MCorp']['support24hour'] != 1) && empty($m_corps['MCorp']['available_time_from'] ) ) {
			return true;
		}
		if ( ($m_corps['MCorp']['support24hour'] != 1) && empty($m_corps['MCorp']['available_time_to'] ) ) {
			return true;
		}
		// 連絡可能時間
		if ( ($m_corps['MCorp']['contactable_support24hour'] != 1) && ($m_corps['MCorp']['contactable_time_other'] != 1) ) {
			// 24時間対応、その他ともに未チェックの場合エラー
			return true;
		}
		if ( ($m_corps['MCorp']['contactable_support24hour'] == 1) && ($m_corps['MCorp']['contactable_time_other'] == 1) ) {
			// 24時間対応、その他ともにチェックありの場合エラー(どちらか一方選択)
			return true;
		}
		if ( ($m_corps['MCorp']['contactable_support24hour'] != 1) && empty($m_corps['MCorp']['contactable_time_from'] ) ) {
			return true;
		}
		if ( ($m_corps['MCorp']['contactable_support24hour'] != 1) && empty($m_corps['MCorp']['contactable_time_to'] ) ) {
			return true;
		}
		//if (strlen($m_corps['MCorp']['registration_details_check']) == 0) return true;
		if (empty($m_corp_categories)) return true;

		return false;


	}


	/**
	 * カレンダーに表示するイベント表示用のデータを取得(ORANGE-1223)
	 * @param $commission_data
	 * @return array
	 */
	private function __get_calender_event_data($commission_data){
		$results = array();

		App::uses('CommonHelper', 'Lib/View/Helper');
		$CommonHelper = new CommonHelper(new View());

		$visit_hope_str = '訪問希望日時：';
		$tell_hope_str = '電話希望日時：';
		$orders_correspondence_str = '受注対応日時：';
		for($i =0; $i < count($commission_data); $i++) {
			$data_array = array();
			$data_array['display_date'] = null; // カレンダーに表示する日にち
			$commission_info = $commission_data[$i]['CommissionInfo'];
			$demand_info = $commission_data[$i]['DemandInfo'];
			$visit_time = $commission_data[$i]['VisitTime'];
			$m_site = $commission_data[$i]['MSite'];

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
				isset($visit_time['visit_time']) ) {

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
			$data_array['demand_id'] = $commission_info['demand_id'];
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
			$key = (count($display_date_split_arr) > 0) ? $display_date_split_arr[0] : 'key'; // 日にち部分のみ取得(yyyy/mm/dd形式)
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
	//ORANGE-13 iwai ADD S
	/**
	 * 申請フォーム処理
	 */
	function application(){

		$save_data = null;

		if($this->request->is('post') || $this->request->is('put')){
			try{
				$this->CommissionApplication->begin();
				$this->Approval->begin();

				//save_data
				if(!empty($this->request->data['chk1']))$save_data['CommissionApplication']['chg_deduction_tax_include'] = 1;
				if(!empty($this->request->data['deduction_tax_include']))$save_data['CommissionApplication']['deduction_tax_include'] = $this->request->data['deduction_tax_include'];
				if(!empty($this->request->data['chk2']))$save_data['CommissionApplication']['chg_irregular_fee_rate'] = 1;
				if(!empty($this->request->data['irregular_fee_rate']))$save_data['CommissionApplication']['irregular_fee_rate'] = $this->request->data['irregular_fee_rate'];
				if(!empty($this->request->data['chk3']))$save_data['CommissionApplication']['chg_irregular_fee'] = 1;
				if(!empty($this->request->data['irregular_fee']))$save_data['CommissionApplication']['irregular_fee'] = $this->request->data['irregular_fee'];

				if(!empty($this->request->data['chk2']) || !empty($this->request->data['chk3'])){
					$save_data['CommissionApplication']['irregular_reason'] = $this->request->data['irregular_reason'];
				}

				if(!empty($this->request->data['chk4']))$save_data['CommissionApplication']['chg_introduction_free'] = 1;
				if(!empty($this->request->data['introduction_free']))$save_data['CommissionApplication']['introduction_free'] = 1;else $save_data['CommissionApplication']['introduction_free'] = 0;

				//ORANGE-198 ADD S
				if(!empty($this->request->data['chk5']))$save_data['CommissionApplication']['chg_ac_commission_exclusion_flg'] = 1;
				if(!empty($this->request->data['ac_commission_exclusion_flg']))$save_data['CommissionApplication']['ac_commission_exclusion_flg'] = 1;else $save_data['CommissionApplication']['ac_commission_exclusion_flg'] = 0;
				//ORANGE-198 ADD E

                                // 2017.4.18 ichino ORANGE-396 ADD start 紹介不可を追加
                                if(!empty($this->request->data['chk6']))$save_data['CommissionApplication']['chg_introduction_not'] = 1;
				if(!empty($this->request->data['introduction_not']))$save_data['CommissionApplication']['introduction_not'] = 1;else $save_data['CommissionApplication']['introduction_not'] = 0;
                                // 2017.4.18 ichino ORANGE-396 ADD end
                                
				if(!empty($this->request->data['commission_id']))$save_data['CommissionApplication']['commission_id'] = $this->request->data['commission_id'];
				if(!empty($this->request->data['demand_id']))$save_data['CommissionApplication']['demand_id'] = $this->request->data['demand_id'];
				if(!empty($this->request->data['corp_id']))$save_data['CommissionApplication']['corp_id'] = $this->request->data['corp_id'];

				$this->CommissionApplication->create();
				if($this->CommissionApplication->save($save_data)){
					$save_data['Approval']['application_section'] = 'CommissionApplication';
					$save_data['Approval']['relation_application_id'] = $this->CommissionApplication->getLastInsertID();
					if(!empty($this->request->data['application_reason']))$save_data['Approval']['application_reason'] = $this->request->data['application_reason'];
					if($this->Approval->save($save_data)){
						$this->CommissionApplication->commit();
						$this->Approval->commit();
						$message = __('application', true);
						$this->Session->write('application_message', $message);

					}else{
						$this->CommissionApplication->rollback();
					}
				}
			}catch(Exception $e){
				$this->CommissionApplication->rollback();
				$this->Approval->rollback();
				$this->log($e->getMessage(), 'error');
			}
			$this->redirect(array('action' => 'detail', $this->request->data['commission_id'], '#' => 'app'));
		}
	}

	/**
	 * 承認フォーム処理
	 */
	function approval(){

		//権限処理
		if($this->User['auth'] == 'system' || $this->User['auth'] == 'admin'){

			if($this->request->is('post') || $this->request->is('put')){
				if(!empty($this->request->data)) {
					if(isset($this->request->data['action_name'])){
						try{
							$message = '';
							$approval = $this->Approval->read(null, $this->request->data['approval_id']);
							$ca = $this->CommissionApplication->read(null, $approval['Approval']['relation_application_id']);
							// ORANGE-391 ADD (S)
							if(Hash::get($approval,'Approval.application_user_id') == $this->User['user_id']){
								$message = '自分の申請を自分で承認することはできません。';
								$this->Session->write('approval_message', $message);
								throw new ApplicationException($message);
							}
							// ORANGE-391 ADD (E)
							if($this->request->data['action_name'] == 'rejected'){
								$this->Approval->set('status', 2);
								$message = __('rejected', true);
							}elseif($this->request->data['action_name'] == 'approval'){
								$this->Approval->set('status', 1);
								$message = __('approval', true);
							}

							// 取次詳細データの取得
							$commission_data = self::__set_commission($ca['CommissionApplication']['commission_id']);
							$commission_data = $this->__setSupport($ca['CommissionApplication']['commission_id'], $commission_data);
							$this->request->data['hidden_last_updated'] = 0;
							unset($commission_data['CommissionInfo']['commit_flg']);
							$modified = $commission_data['CommissionInfo']['modified'];
							unset($commission_data['CommissionInfo']['modified']);

							if($ca['CommissionApplication']['chg_deduction_tax_include'])$commission_data['CommissionInfo']['deduction_tax_include'] = $ca['CommissionApplication']['deduction_tax_include'];
							if($ca['CommissionApplication']['chg_irregular_fee_rate'])$commission_data['CommissionInfo']['irregular_fee_rate'] = $ca['CommissionApplication']['irregular_fee_rate'];
							if($ca['CommissionApplication']['chg_irregular_fee'])$commission_data['CommissionInfo']['irregular_fee'] = $ca['CommissionApplication']['irregular_fee'];
							if($ca['CommissionApplication']['chg_introduction_free'])$commission_data['CommissionInfo']['introduction_free'] = $ca['CommissionApplication']['introduction_free'];
							if($ca['CommissionApplication']['chg_irregular_fee_rate'] || $ca['CommissionApplication']['chg_irregular_fee'])$commission_data['CommissionInfo']['irregular_reason'] = $ca['CommissionApplication']['irregular_reason'];
							//ORANGE-198 ADD S
							if($ca['CommissionApplication']['chg_ac_commission_exclusion_flg'])$commission_data['CommissionInfo']['ac_commission_exclusion_flg'] = $ca['CommissionApplication']['ac_commission_exclusion_flg'];
							//ORANGE-198 ADD E
                                                        // 2017.4.18 ichino ORANGE-396 ADD start 紹介不可を追加
                                                        if($ca['CommissionApplication']['chg_introduction_not'])$commission_data['CommissionInfo']['introduction_not'] = $ca['CommissionApplication']['introduction_not'];
                                                        // 2017.4.18 ichino ORANGE-396 ADD end

							$commission_data = $this->__price_calc($commission_data);

							// 更新日付のチェック
							if(self::__check_modified_commission($ca['CommissionApplication']['commission_id'] , $modified)){

								$this->CommissionInfo->begin();
								//$this->DemandInfo->begin();
								//$this->CommissionCorrespond->begin();
								$this->BillInfo->begin();
								$this->Approval->begin();

								$resultsFlg = $this->Approval->save();

								if(!$resultsFlg){
									$message = '申請に失敗しました';
								}

								//ORANGE-234 ADD S
								if(!empty($commission_data['CommissionInfo']['commission_note_send_datetime']))
									$commission_data['CommissionInfo']['commission_note_send_datetime'] = substr($commission_data['CommissionInfo']['commission_note_send_datetime'],0 , strlen($commission_data['CommissionInfo']['commission_note_send_datetime']) - 3);
								if(!empty($commission_data['CommissionInfo']['tel_commission_datetime']))
									$commission_data['CommissionInfo']['tel_commission_datetime'] = substr($commission_data['CommissionInfo']['tel_commission_datetime'],0 ,strlen($commission_data['CommissionInfo']['tel_commission_datetime']) - 3);
								$correspond = $this->CommissionInfo->get_correspond($ca['CommissionApplication']['commission_id'], $commission_data);
								//ORANGE-234 ADD E

								//承認時のみ、取次データの更新を行う
								if($resultsFlg && $this->request->data['action_name'] == 'approval'){
									// 取次情報の編集
									$resultsFlg = self::__edit_commission($ca['CommissionApplication']['commission_id'] , $commission_data);

									if ($resultsFlg){
										// 請求情報の登録
										if($commission_data['CommissionInfo']['commission_status'] == Util::getDivValue('construction_status','construction') || $commission_data['CommissionInfo']['commission_status'] == Util::getDivValue('construction_status','introduction')){
											$resultsFlg = self::__regist_bill_info($ca['CommissionApplication']['commission_id'] , $commission_data);
											if(!$resultsFlg)$message = '請求データの更新に失敗しました';
										}

										//ORANGE-234 ADD S
										if ($resultsFlg && !empty($correspond)){
											$this->CommissionCorrespond->create();
											$cd['corresponding_contens'] = $correspond;
											$cd['responders'] = '自動登録['.$this->User["user_name"].']';
											$cd['rits_responders'] = null;
											$cd['commission_id'] = $ca['CommissionApplication']['commission_id'];
											$cd['created_user_id'] = 'system';
											$cd['modified_user_id'] = 'system';
											$cd['correspond_datetime'] = date('Y-m-d H:i:s');

											$this->CommissionCorrespond->save($cd,
													array('validate' => false, 'callbacks' => false));

										}
										//ORANGE_234 ADD E

									}else{
										$message = '取次データの更新に失敗しました';
									}

								}



								if ($resultsFlg){  // エラーが無い場合
									$this->Approval->commit();
									$this->CommissionInfo->commit();
									//$this->DemandInfo->commit();
									//$this->CommissionCorrespond->commit();
									$this->BillInfo->commit();

									$this->Session->write('approval_message', $message);

								} else {    // エラーが有る場合
									$this->CommissionInfo->rollback();
									//$this->DemandInfo->rollback();
									//$this->CommissionCorrespond->rollback();
									$this->BillInfo->rollback();
									$this->Approval->rollback();

									$this->Session->write('approval_message', $message);
								}
							}

						}catch(Exception $e){
							$this->Approval->rollback();
							$this->CommissionInfo->rollback();
							//$this->DemandInfo->rollback();
							//$this->CommissionCorrespond->rollback();
							$this->BillInfo->rollback();
							$this->log($e->getMessage(), LOG_ERR);
						}
					}
				}
			}
			$this->redirect(array('action' => 'detail', $ca['CommissionApplication']['commission_id'], '#' => 'app'));
		}
		$this->redirect(HTTP_REFERER);
	}

	/**
	 * 申請承認データ取得
	 * @param unknown $commission_id
	 * @return boolean|unknown
	 */
	private function __getApplicationData($commission_id=null){
		if(empty($commission_id))return false;
		$options = array(
				'fields' => '*,Approval.application_user_id, Approval.application_datetime, Approval.status, Approval.application_reason, Approval.id',
				'conditions' => array('CommissionApplication.commission_id' => $commission_id),
				'order' => array('CommissionApplication.id' => 'desc'),
				'recursive' => -1,
				'joins' => array(
					 array(
							'type' => 'inner',
							'table' => 'approvals',
							'alias' => 'Approval',
							'conditions' => "Approval.application_section = 'CommissionApplication' AND Approval.relation_application_id = CommissionApplication.id",
							'fields' => '*'
					)
				),
		);

		$result = $this ->CommissionApplication->find('all', $options);
		$arr_status =  Util::getDropList('申請');

		foreach($result as &$data){
			$data['Approval']['status_disp'] = $arr_status[$data['Approval']['status']];
		}
		return $result;
	}

// 2017.01.04 murata.s ORANGE-244 CHG(S)
// 	/**
// 	 * 料金計算用
// 	 */
// 	private function __price_calc($data = null){
//
// 		$tax_rate = self::__set_m_tax_rates($data['CommissionInfo']['complete_date']);
// 		$data['MTaxRate']['tax_rate'] = $tax_rate['MTaxRate']['tax_rate'];
// 		//レスポンスデータに合わせて配列を追加
// 		$data['insurance_price'] = $data['BillInfo']['insurance_price'];
//
// //		if($data['CommissionInfo']['commission_status'] != Util::getDivValue('construction_status','introduction')){
//
// 			if (!isset($data['CommissionInfo']['construction_price_tax_exclude'])) {
// 				$data['CommissionInfo']['construction_price_tax_exclude'] = 0;
// 			}
//
// 			if (!isset($data['CommissionInfo']['deduction_tax_include'])) {
// 				$data['CommissionInfo']['deduction_tax_include'] = 0;
// 			}
//
// 			if ($tax_rate['MTaxRate']['tax_rate'] != '') {
//
//
// 				$tax_rate_val = (int)$tax_rate['MTaxRate']['tax_rate'] * 0.01;
//
// 				// 施工金額(税抜)が空白の場合、無条件に計算すると税込に「0」がセットされるため、税込が0以上の場合のみ計算する
// 				if (!empty($data['CommissionInfo']['construction_price_tax_exclude'])){
// 					// 2015.5.1 n.kai ADD end
// 					/***** 施工金額(税込) 計算 ******/
// 					$data['CommissionInfo']['construction_price_tax_include'] = round($data['CommissionInfo']['construction_price_tax_exclude'] * (1 + $tax_rate_val));
// 				}
//
// 				/***** 控除金額(税抜) 計算 ******/
// 				$deduction_tax_exclude = 0;
// 				if ($data['CommissionInfo']['deduction_tax_include'] != 0) {
// 					if (preg_match('/^[0-9]+$/', $tax_rate['MTaxRate']['tax_rate']) && preg_match('/^[0-9]+$/', $data['CommissionInfo']['deduction_tax_include']) ) {
// 						$deduction_tax_exclude = round($data['CommissionInfo']['deduction_tax_include'] / (1 + $tax_rate_val));
// 					}
// 				}
//
// 				// 2015.08.17 s.harada MOD end 取次画面デザイン変更対応
// 				$data['CommissionInfo']['deduction_tax_exclude'] = $deduction_tax_exclude;
// 			}
//
// 			/***** 値を取得 ******/
// 			if (empty($data['CommissionInfo']['deduction_tax_exclude'])) {
// 				$data['CommissionInfo']['deduction_tax_exclude'] = 0;
// 			}
//
// 			/***** 手数料対象金額 計算 ******/
// 			$data['BillInfo']['fee_target_price'] = 0;
//
// 			if ($data['CommissionInfo']['construction_price_tax_include'] != 0) {
// 				if (preg_match('/^[0-9]+$/', $data['CommissionInfo']['construction_price_tax_exclude'])
// 						&& preg_match('/^[0-9]+$/', $data['CommissionInfo']['deduction_tax_exclude'])) {
//
// 					// 手数料対象金額 = 施工金額(税抜) - 控除金額(税抜)
// 					$data['BillInfo']['fee_target_price'] = $data['CommissionInfo']['construction_price_tax_exclude'] - $data['CommissionInfo']['deduction_tax_exclude'];
// 				}
// 			}
//
//
// 			if($data['MGenre']['insurant_flg'] == 1 && $data['AffiliationInfo']['liability_insurance'] == 2){
//
// 				/***** 保険料金額 計算 ******/
// 				// 保険料金額 = 施工金額(税抜)× 0.01
// 				$data['BillInfo']['insurance_price'] = round($data['CommissionInfo']['construction_price_tax_exclude'] * 0.01);
// 			}
//
// 			// ----------------------------------------------------- 受注手数料単位 -----------------------------------------------------
// 			// *************************************** 受注手数料単位が%の時 ***************************************
//
// 			if(empty($data['DemandInfo']['category_id']) == false)
// 				$category_default_fee = $this->MCategory->getDefault_fee($data['DemandInfo']['category_id']);
//
// 			$fee_unit = $data['MCorpCategory']['order_fee_unit'];
// 			if(is_null($fee_unit) == true && is_null($category_default_fee) == false) {
// 				$fee_unit = $category_default_fee['category_default_fee_unit'];
// 			}l
// 			if($fee_unit !== 0 && $data['CommissionInfo']['commission_status'] != Util::getDivValue('construction_status','introduction')){
//
// 				/***** 確定手数料率 ******/
// 				if (!empty($data['CommissionInfo']['irregular_fee_rate'])) {
// 					$data['CommissionInfo']['confirmd_fee_rate'] = $data['CommissionInfo']['irregular_fee_rate'];
// 				} else {
// 					$data['CommissionInfo']['confirmd_fee_rate'] = $data['CommissionInfo']['commission_fee_rate'];
// 				}
//
// 				if (!empty($data['CommissionInfo']['irregular_fee'])) {
// 					$data['BillInfo']['fee_tax_exclude'] = $data['CommissionInfo']['irregular_fee'];
// 				} else {
// 					/***** 取次先手数料 計算 ******/
// 					// 取次先手数料 = 手数料対象金額 × 確定手数料率
// 					$data['BillInfo']['fee_tax_exclude'] = round($data['BillInfo']['fee_target_price'] * $data['CommissionInfo']['confirmd_fee_rate'] * 0.01);
// 				}
//
// 				/***** 取次先手数料 入力＆表示 ******/
// 				if(!empty($data['BillInfo']['fee_tax_exclude']))$data['CommissionInfo']['corp_fee'] = $data['BillInfo']['fee_tax_exclude'];
//
//
// 			// *************************************** 受注手数料単位が円の時 ***************************************
// 			}else{
//
// 				/***** 値を取得 ******/
// 				// 確定手数料
// 				$corp_fee = $data['CommissionInfo']['corp_fee'];
// 				// イレギュラー手数料
// 				$irregular_fee = $data['CommissionInfo']['irregular_fee'];
//
// 				if ($irregular_fee != '' && $irregular_fee != 0) {
// 					/***** 取次先手数料 入力＆表示 ******/
// 					$data['BillInfo']['fee_tax_exclude'] = $irregular_fee;
// 				} else {
// 					$data['BillInfo']['fee_tax_exclude'] = $corp_fee;
// 				}
//
// 				// 取次状況が紹介済みの場合
// 				if($data['CommissionInfo']['commission_status'] == Util::getDivValue('construction_status','introduction')){
//
// 					// 2015.08.17 s.harada MOD end 取次画面デザイン変更対応
// 					$data['BillInfo']['fee_target_price'] = $data['BillInfo']['fee_tax_exclude'];
//
// // 2016.08.25 murata.s ORANGE-130 ADD(S)
// 					if($data['CommissionInfo']['introduction_free'] == 1){
// 						$data['BillInfo']['fee_target_price'] = 0;
// 						$data['BillInfo']['fee_tax_exclude'] = 0;
// 					}
// // 2016.08.25 murata.s ORANGE-130 ADD(E)
//
// 				}
// 			}
//
// //		}
// //$this->log($data, 'error');
// 		return $data;
//
// 	}
	/**
	 * 料金計算用
	 */
	private function __price_calc($data = null){

		$tax_rate = self::__set_m_tax_rates($data['CommissionInfo']['complete_date']);
		$data['MTaxRate']['tax_rate'] = $tax_rate['MTaxRate']['tax_rate'];
		//レスポンスデータに合わせて配列を追加
		$data['insurance_price'] = $data['BillInfo']['insurance_price'];

		// Componentを使用して料金計算を行う
		$calc_data = $this->BillPriceUtil->calculate_bill_price($data);

		// 料金計算結果をセット
		$data['CommissionInfo']['corp_fee'] = $calc_data['CommissionInfo']['corp_fee'];
		$data['CommissionInfo']['construction_price_tax_exclude'] = $calc_data['CommissionInfo']['construction_price_tax_exclude'];
		$data['CommissionInfo']['construction_price_tax_include'] = $calc_data['CommissionInfo']['construction_price_tax_include'];
		$data['CommissionInfo']['deduction_tax_exclude'] = $calc_data['CommissionInfo']['deduction_tax_exclude'];
		$data['CommissionInfo']['deduction_tax_include'] = $calc_data['CommissionInfo']['deduction_tax_include'];
		$data['CommissionInfo']['confirmd_fee_rate'] = $calc_data['CommissionInfo']['confirmd_fee_rate'];

		$data['BillInfo']['fee_target_price'] = $calc_data['BillInfo']['fee_target_price'];
		$data['BillInfo']['fee_tax_exclude'] = $calc_data['BillInfo']['fee_tax_exclude'];
		$data['BillInfo']['total_bill_price'] = $calc_data['BillInfo']['total_bill_price'];
		$data['BillInfo']['tax'] = $calc_data['BillInfo']['tax'];
		$data['BillInfo']['insurance_price'] = $calc_data['BillInfo']['insurance_price'];

		return $data;
	}
// 2017.01.04 murata.s ORANGE-244 CHG(E)
	//ORANGE-13 iwai ADD E

	//ORANGE-198 ADD S
	private function __set_auction_commission($auction_id, $data){
		//オークションIDが存在しない場合は、そのままdataを返す
		if(empty($auction_id))return $data;

		//請求データが存在するため別名aliasで取得
		$bill_info = $this->BillInfo;
		$bill_info->alias = 'AuctionBillInfo';
		$options = array(
				'conditions' => array($bill_info->alias.'.auction_id' => $auction_id),
				'fields' => array('total_bill_price')
		);

		$data += $bill_info->find('first', $options);

		return $data;
	}
	//ORANGE-198 ADD E
}
