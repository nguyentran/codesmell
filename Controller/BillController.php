<?php
App::uses('AppController', 'Controller');

class BillController extends AppController {

	public $name = 'Bill';
	public $helpers = array('Csv');
	public $components = array('PDF','Session', 'Csv', 'Excel', 'RequestHandler');
	public $uses = array('BillInfo', 'MCategory', 'MCorp', 'MoneyCorrespond');

	public function beforeFilter(){
		parent::beforeFilter();
		$this->set('default_display', false);
		$this->User = $this->Auth->user();
	}

	/**
	 *
	 * 初期表示ページ
	 *
	 */
	public function index() {
	}

	/**
	 *
	 * 請求情報加盟店一覧ページ
	 *
	 */
	public function mcop_list() {
		$default_display = true;
		$this->set ( 'default_display', $default_display );
	}

	/**
	 *
	 * 請求情報加盟店一覧ページ（ボタンクリック後）
	 *
	 * @throws Exception
	 */
	public function mcop_search() {

		try {
			if ($this->RequestHandler->isPost()) {
				$data = $this->__mcopListPost();
			} else {
				$data = $this->__mcopListGet();
			}
		} catch (Exception $e) {
			throw $e;
		}

		// 加盟店情報検索条件の設定
		$conditions = self::__setSearchConditions($data);
		// 検索用Join句作成
		$joins = self::__setSearchJoin($data);
		// 加盟店情報を検索する
		$results = $this->__searchMCorp($conditions , $joins);
		$this->set ( 'results', $results );

		// レイアウトの指定
		$this->render ( 'mcop_list' );
	}

	/**
	 *
	 * 請求情報一覧ページ
	 *
	 * @param string $mcorp_id
	 */
	public function bill_list($mcorp_id = null) {

		if(empty($mcorp_id)){
			return $this->redirect('mcop_search');
		}

		$mcop_data = $this->MCorp->findById($mcorp_id);
		$list_data = $this->Session->read(self::$__sessionKeyForBillMcopSearch);

		$data['bill_status'] = $list_data['bill_status'];
		$data['corp_id'] = $mcorp_id;
		$data['official_corp_name'] = $mcop_data['MCorp']['official_corp_name'];
		$this->data = $data;

		$conditions = self::__setSearchBillList($this->data);

		// 検索条件のデータを取得
		if($data ['bill_status'] == Util::getDivValue ( 'bill_status', 'payment' )){  // 入金済のみページ制御
			$bill_list = self::__get_bill_page_list ($conditions);
		} else {
			$bill_list = self::__get_bill_list ($conditions);
		}
		// 値をセット
		$this->set ( "results", $bill_list );
		$this->set ( 'category_list', $this->MCategory->getList () );
	}

	/**
	 *
	 * 請求情報一覧ページ（ボタンクリック後）
	 *
	 * @throws Exception
	 */
	public function bill_search($corp_id = null) {

		/*
		 * ボタンの処理
		 */
		if (isset( $this->request->data ['save'] )) {					// 保存
			self::__bill_updata ( $this->request->data);
		} else if (isset( $this->request->data ['bill_download'] )) {	// ダウンロ－ド
			self::__bill_download ( $this->request->data);
		}

		// 検索ボタンの処理
		if (isset ( $this->request->data ['search'] )) {
			$data = self::__billListPost ();
		} else {
			$data = self::__billListGet ();
		}

		// セットデータが無い場合
		if (empty ( $data )) {
			return $this->redirect ( 'bill_list/'.$corp_id );
			return;
		}

		// 入力チェック
		if (empty ( $data ['corp_id'] ) || empty ( $data ['bill_status'] )) {
			throw new Exception();
			return;
		}

		// 検索条件取得
		$conditions = self::__setSearchBillList($data);

		// 検索条件のデータを取得
		if($data ['bill_status'] == Util::getDivValue ( 'bill_status', 'payment' )){  // 入金済のみページ制御
			$bill_list = self::__get_bill_page_list ($conditions);
		} else {
			$bill_list = self::__get_bill_list ($conditions);
		}
		// 値をセット
		$this->set ( "results", $bill_list );
		$this->set ( 'category_list', $this->MCategory->getList () );

		// レイアウトの指定
		$this->render ( 'bill_list' );
	}

	/**
	 *
	 * 入金履歴ページ
	 *
	 * @param string $corp_id
	 * @throws Exception
	 */
	public function money_correspond($corp_id = null) {

		// 登録ボタンクリック
		if (isset ( $this->request->data ['regist'] )) {
			if(self::__updata_money_correspond($this->request->data)){
				$this->Session->setFlash ( __( 'Updated', true ), 'default', array ('class' => 'message_inner') );
			} else {
				$this->Session->setFlash ( __( 'InputError', true ), 'default', array ('class' => 'error_inner') );
			}
		}

		// 検索ボタンの処理
		if (isset ( $this->request->data ['search'] ) || !empty($corp_id) || isset ($this->request->data ['regist'])) {
			$data = self::__MoneyCorrespondListPost ($corp_id);
		} else {
			$data = self::__MoneyCorrespondGet ();
		}

		if (empty ( $data['corp_id'] )) {
			throw new Exception();
			return;
		}

		// 検索条件取得
		$conditions = self::__setSearchMoneyCorrespondList($data);

		$results = self::__get_money_correspond_list ($conditions);


		$this->layout = 'subwin';
		$this->set('corp_id', $data['corp_id']);
		$this->set('results', $results);
	}

	/**
	 *
	 * 入金履歴削除
	 *
	 * @param string $delete_id
	 */
	public function money_correspond_delete($delete_id = null) {
		if(!empty($delete_id)){
			$this->MoneyCorrespond->delete($delete_id);
		}
		return $this->redirect('/bill/money_correspond');
		return;
	}


	/**
	 *
	 * 請求詳細ページ
	 *
	 */
	public function bill_detail($id = null) {

		// 請求IDが無い場合
		if (empty ( $id )) {
			throw new Exception();
			return;
		}

		// 登録ボタンクリック
		if (isset ( $this->request->data ['regist'] )) {
			// 更新日付のチェック
			if (self::__check_modified_bill ( $id, $this->request->data ['BillInfo'] ['modified'] )) {
				try {
					$this->BillInfo->begin ();

					// 請求情報更新
					$resultsFlg = self::__edit_bill ( $id, $this->request->data ['BillInfo'] );

					if ($resultsFlg) { // エラーが無い場合
						$this->Session->setFlash ( __( 'Updated', true ), 'default', array ('class' => 'message_inner') );
						$this->BillInfo->commit ();
					} else {
						$this->Session->setFlash ( __( 'InputError', true ), 'default', array ('class' => 'error_inner') );
						$this->BillInfo->rollback ();
					}
				} catch ( Exception $e ) {
					$this->BillInfo->rollback ();
				}
			} else {
				$this->Session->setFlash ( __( 'ModifiedNotCheck', true ), 'default', array ('class' => 'error_inner') );
			}
			$this->data = $this->request->data;
		} else {
			// データを取得
			$this->data = self::__set_bill_data ( $id );
		}
		$results = self::__set_bill_data ( $id );

		$this->set('results',$results);
	}

	/**
	 *
	 * 請求情報出力ページ
	 *
	 */
	public function bill_output() {

		// 請求データ出力ボタンクリック
		if (isset ( $this->request->data ['output'] )) {
			if (empty ( $this->request->data ['from_complete_date'] ) && empty ( $this->request->data ['to_complete_date'] )) {
				$this->Session->setFlash ( __( 'BillOutputError', true ), 'default', array ('class' => 'error_inner') );
				return $this->redirect ( 'bill_output/' );
			}

			// 検索条件取得
			$conditions = self::__setSearchBillOutput($this->request->data);

			// 検索条件のデータを取得
			$results = self::__get_bill_list ($conditions);

			if(empty($results)){	// データが無い場合エラー
				$this->Session->setFlash (__('NotData' , true), 'default', array ('class' => 'error_inner') );
			} else {
				// CSV出力の為の値を修正
				$list = self::__edit_csv_bill_list ($results);
				// CSV出力ファイル名
				$file_name = mb_convert_encoding(__('bill_information' , true).'_'.$this->User['user_id'], 'SJIS-win', 'UTF-8');
				// CSV出力
				$this->Csv->download('BillInfo', 'default', $file_name, $list);
			}
		}
	}

	/**
	 * ページメイトのPOSTパラメーター処理
	 *
	 * @return multitype:
	 */
	private function __mcopListPost() {

		// 入力値取得
		$data = $this->request->data;
		unset($data['csv_out']);
		// セッションに検索条件を保存
		$this->Session->delete(self::$__sessionKeyForBillMcopSearch);
		$this->Session->write(self::$__sessionKeyForBillMcopSearch, $data);

		return $data;
	}

	/**
	 * ページメイトのGETパラメーター処理
	 *
	 * @return Ambigous <multitype:Ambigous <NULL, mixed, multitype:> , mixed, boolean, NULL, unknown, array>
	 */
	private function __mcopListGet() {

		$data = $this->Session->read(self::$__sessionKeyForBillMcopSearch);

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
	 * 加盟店検索条件設定
	 *
	 * @param string $data
	 * @return multitype:string
	 */
	private function __setSearchConditions($data = null) {
		$conditions = array();

		$conditions['MCorp.affiliation_status'] = 1;

		// 加盟店名
		if (!empty($data['corp_name'])) {
			$conditions['z2h_kana(MCorp.corp_name) like'] = '%'. Util::chgSearchValue($data['corp_name']) .'%';
		}

		// 加盟店ID
		if (!empty($data['corp_id'])) {
			$conditions['MCorp.id'] = Util::chgSearchValue($data['corp_id']);
		}

		// 請求状況
		if (!empty($data['bill_status'])) {
			$conditions['BillInfo.bill_status'] = $data['bill_status'];
		}

		// 請求ID
		if (!empty($data['bill_id'])) {
			$conditions['BillInfo.id'] = Util::chgSearchValue($data['bill_id']);
		}

		// 手数料請求日 From
		if (!empty($data['from_fee_billing_date'])) {
			$conditions['BillInfo.fee_billing_date >='] = $data['from_fee_billing_date'];
		}

		// 手数料請求日 To
		if (!empty($data['to_fee_billing_date'])) {
			$conditions['BillInfo.fee_billing_date <='] = $data['to_fee_billing_date'];
		}

		return $conditions;
	}

	/**
	 * 検索用Join句作成
	 *
	 * @param string $data
	 * @return multitype:multitype:string multitype:string
	 */
	private function __setSearchJoin($data = null) {

		$joins = array();

		$joins = array (
						array (
								'fields' => '*',
								'type' => 'inner',
								"table" => "commission_infos",
								"alias" => "CommissionInfo",
								"conditions" => array (
										"CommissionInfo.corp_id = MCorp.id",
// 2016.07.11 murata.s ORANGE-1 DEL(S)
// 入札手数料のレコードを表示させるためコメントアウト
// 										"CommissionInfo.complete_date != ''",
// 2016.07.11 murata.s ORANGE-1 DEL(E)
										"CommissionInfo.del_flg != 1",
										"CommissionInfo.introduction_not != 1",
										'or' => array(
												array ('CommissionInfo.commission_status' => Util::getDivValue ( 'construction_status', 'construction' )),
												array ('CommissionInfo.commission_status' => Util::getDivValue ( 'construction_status', 'introduction' )),
										// 2015.05.27 h.hara(S) ORANGE-451(S)
										"CommissionInfo.introduction_free != 1",
										// 2015.05.27 h.hara(S) ORANGE-451(E)
										)
								),
						),
						array (
								'fields' => '*',
								'type' => 'inner',
								"table" => "bill_infos",
								"alias" => "BillInfo",
								"conditions" => array (
// 2016.07.11 murata.s ORANGE-1 CHG(S)
//										"BillInfo.commission_id = CommissionInfo.id",
 										'or' => array(
 												array(
 														"BillInfo.commission_id = CommissionInfo.id",
 														"BillInfo.auction_id is null",
 														"CommissionInfo.complete_date != ''"
 												),
 												array(
 														"BillInfo.commission_id = CommissionInfo.id",
 														"BillInfo.auction_id is not null"
 												)
 										),
// 2016.07.11 murata.s ORANGE-1 CHG(E)
								)
						),
						// 2015.6.18 n.kai ADD start ORANGE-443, ORANGE-450, ORANGE-596, ORANGE-607
						array (
								'fields' => '*',
								'type' => 'inner',
								"table" => "demand_infos",
								"alias" => "DemandInfo",
								"conditions" => array (
										"DemandInfo.id = CommissionInfo.demand_id",
										"DemandInfo.del_flg != 1",
										"DemandInfo.demand_status != 6",
										"DemandInfo.riro_kureka != 1"
								)
						),
						// 2015.6.18 n.kai ADD end ORANGE-443, ORANGE-450, ORANGE-596, ORANGE-607
				);

		// 名義人
		if (!empty($data['nominee'])) {
			$subQuery = '( SELECT corp_id FROM money_corresponds WHERE nominee LIKE \'%' . Sanitize::escape($data['nominee']). '%\' GROUP BY corp_id)';
			$correspond_joins = array (
					"type" => 'inner',
					"alias" => 'Correspond',
					"table" => $subQuery,
					"conditions" => 'MCorp.id = Correspond.corp_id'
			);
			array_push ( $joins, $correspond_joins );
		}

		return $joins;
	}

	/**
	 * 加盟店情報を検索する
	 * @param unknown $conditions
	 * @return unknown
	 */
	private function __searchMCorp($conditions = array() , $joins = array()) {
		// 検索する
		$this->paginate = array(
				'conditions' => $conditions,
				'fields' => 'MCorp.id , MCorp.official_corp_name',
				'joins' => $joins,
				'group' => array('MCorp.id' , 'MCorp.official_corp_name'),
				'limit' => Configure::read('list_limit'),
				'order' => array('MCorp.id' => 'asc'),
		);

		$results = $this->paginate('MCorp');

		return $results;
	}

	/**
	 * 請求書ダウンロード
	 *
	 * @param unknown $data
	 * @return boolean
	 */
	private function __bill_download($data = array()){

		// 対象のチェックボックスをチェック
		if(!isset($data['target'])){
			$this->Session->setFlash ( __ ( 'BillDataNotCheck', true ), 'default', array (
					'class' => 'error_inner'
			) );
			return false;
		}

		if(empty($data['corp_id']) || empty($data['official_corp_name'])){
			throw new Exception();
			return;
		}

		$mcorp_data['mcorp_id'] = $data['corp_id'];								// 企業ID
		$mcorp_data['official_corp_name'] = $data['official_corp_name'];		// 正式企業名

		foreach ( $data ['target'] as $i ) {
			if($data['bill_status'] == Util::getDivValue ( 'bill_status', 'not_issue' )){  // 未発行の場合
				// ステータス変更
				self::__bill_status_change($data ['BillInfo'] ['id'] [$i] , Util::getDivValue ( 'bill_status', 'issue' ) , date('Y/m/d'));
			}
			$conditions_data[] = array('BillInfo.id' =>$data ['BillInfo'] ['id'] [$i]);
		}

		// 指定の月のリストの条件
		$conditions['or'] = $conditions_data;
		$conditions['CommissionInfo.complete_date >='] = date('Y/m/01' , strtotime('-1 month'));
		$conditions['CommissionInfo.complete_date <'] = date('Y/m/01');
		// 指定の月のリスト 取得
		$bill_list = self::__get_bill_list ($conditions);

		// 前月繰越残高の条件
		if($data['bill_status'] == Util::getDivValue ( 'bill_status', 'issue' )){  // 発行済みの場合
			$conditions_past['or'] = $conditions_data;
		}
		$conditions_past['MCorp.id'] = $data['corp_id'];
		$conditions_past['BillInfo.bill_status !='] = 3;
		$conditions_past['CommissionInfo.complete_date <'] = date('Y/m/01' , strtotime('-1 month'));
		// 前月繰越残高 取得
		$mcorp_data['past_bill_price'] = self::__get_bill_past_list ($conditions_past);
		Configure::write('debug', '0');
		$this->layout = false;
		// Excelファイル名
		$file_name = __('bill_information' , true) . '_' . $mcorp_data['official_corp_name'];
		// Excel作成
		$this->Excel->bill_download($bill_list , $mcorp_data , $file_name);
	}

	/**
	 * CSV出力変換
	 *
	 * @param unknown $results
	 * @return Ambigous <unknown, string, array, ArrayAccess, mixed, NULL, multitype:Ambigous <array, ArrayAccess, unknown> Ambigous <mixed, array, NULL, ArrayAccess, unknown> , multitype:NULL , multitype:multitype:NULL  unknown , multitype:Ambigous <array, ArrayAccess, unknown> Ambigous <mixed, array, NULL, multitype:Ambigous <array, ArrayAccess, unknown> Ambigous <mixed, array, NULL, ArrayAccess, unknown> , ArrayAccess, unknown> , multitype:Ambigous <array, ArrayAccess, unknown> Ambigous <mixed, array, NULL, multitype:Ambigous <array, ArrayAccess, unknown> Ambigous <mixed, array, NULL, ArrayAccess, unknown> , multitype:Ambigous <array, ArrayAccess, unknown> Ambigous <mixed, array, NULL, multitype:Ambigous <array, ArrayAccess, unknown> Ambigous <mixed, array, NULL, ArrayAccess, unknown> , ArrayAccess, unknown> , ArrayAccess, unknown> , multitype:, string, multitype:multitype: , multitype:Ambigous <multitype:multitype: , multitype:> >
	 */
	private function __edit_csv_bill_list($list){

		$data = array();

		$bill_status_list = Util::getDropList(__('bill_status' , true));				// 請求状況

		$data = $list;
		foreach ($list as $key => $val){
			$data[$key]['BillInfo']['bill_status'] = !empty($val['BillInfo']['bill_status']) ? $bill_status_list[$val['BillInfo']['bill_status']] : '';
			$data[$key]['BillInfo']['indivisual_billing'] = !empty($val['BillInfo']['indivisual_billing']) ?  __('maru', true) : __('batu', true) ;
			if(empty($data[$key]['BillInfo']['fee_payment_balance'])){
				$data[$key]['BillInfo']['fee_payment_balance'] = $data[$key]['BillInfo']['total_bill_price'] - $data[$key]['BillInfo']['fee_payment_price'];
			}
			// ステータス変更
			if($val['BillInfo']['bill_status'] == Util::getDivValue ( 'bill_status', 'not_issue' )){
				$save_data[] = array('id'=>$val['BillInfo']['id'], 'bill_status'=>Util::getDivValue ( 'bill_status', 'issue' ), 'fee_billing_date' => date('Y/m/d'));
			}
			$data[$key]['MSite']['commission_type'] = !empty($val['MSite']['commission_type']) ? Util::getDivTextJP('', $val['MSite']['commission_type']) : '';
		}

		// 請求データ出力のみ
		if (!empty( $this->request->data ['fee_billing_date'] )) {
			// ステータス変更
			if(!empty($save_data)){
				$this->BillInfo->saveAll($save_data);
			}
		}
		return $data;
	}

	/**
	 *
	 * 請求データ一覧の条件をセット
	 *
	 *
	 * @param string $corp_name
	 * @param string $bill_status
	 * @return multitype:string
	 */
	private function __setSearchBillList($data = array()){

		$conditions = array();

		// 加盟店
		$conditions['MCorp.id'] = $data['corp_id'];

		// 取次状況
		$conditions['or'] =  array (
					array ('CommissionInfo.commission_status' => Util::getDivValue ( 'construction_status', 'construction' ), 'BillInfo.auction_id is null'),
					array ('CommissionInfo.commission_status' => Util::getDivValue ( 'construction_status', 'introduction' ), 'BillInfo.auction_id is null'),
// 2016.07.11 murata.s ORANGE-1 ADD(S)
//ORANGE-233 CHG S
					// 入札手数料も表示する
					array ('CommissionInfo.ac_commission_exclusion_flg = false', 'BillInfo.auction_id is not null'),
//ORANGE-233 CHG E
// 2016.07.11 murata.s ORANGE-1 ADD(E)
				);

		// 請求状況
		$conditions['BillInfo.bill_status'] = $data['bill_status'];

		// 手数料請求日 From
 		if (!empty($data['from_fee_billing_date'])) {
 			$conditions['BillInfo.fee_billing_date >='] = $data['from_fee_billing_date'];
 		}

 		// 手数料請求日 To
 		if (!empty($data['to_fee_billing_date'])) {
 			$conditions['BillInfo.fee_billing_date <='] = $data['to_fee_billing_date'];
 		}

		return $conditions;
	}

	/**
	 *
	 * 請求データ出力の条件をセット
	 *
	 * @param unknown $data
	 * @return multitype:unknown
	 */
	private function __setSearchBillOutput($data = array()){

		$conditions = array();

		// 請求状況
		$conditions['BillInfo.bill_status !='] = Util::getDivValue ( 'bill_status', 'payment' );

		// 取次状況
		$conditions['or'] =  array (
								//ORANGE-233 CHG S
								array ('CommissionInfo.commission_status' => Util::getDivValue ( 'construction_status', 'construction' ), 'BillInfo.auction_id is null'),
								array ('CommissionInfo.commission_status' => Util::getDivValue ( 'construction_status', 'introduction' ), 'BillInfo.auction_id is null'),

								// 入札手数料も表示する
								array ('CommissionInfo.ac_commission_exclusion_flg = false', 'BillInfo.auction_id is not null'),
								//ORANGE-233 CHG E
							);

		// 施工完了日 From
		if (!empty($data['from_complete_date'])) {
			$conditions['CommissionInfo.complete_date >='] = $data['from_complete_date'];
		}

		// 施工完了日 To
		if (!empty($data['to_complete_date'])) {
			$conditions['CommissionInfo.complete_date <='] = $data['to_complete_date'];
		}

		return $conditions;
	}

	/**
	 * リストの結合条件取得
	 *
	 * @return multitype:multitype:string multitype:string
	 */
	private function __get_bill_joins(){
		$joins = array (
				array (
						'fields' => '*',
						'type' => 'inner',
						"table" => "demand_infos",
						"alias" => "DemandInfo",
						"conditions" => array (
								"DemandInfo.id = BillInfo.demand_id",
								"DemandInfo.del_flg != 1",
								// 2015.6.18 n.kai ADD start ORANGE-443, ORANGE-450, ORANGE-596, ORANGE-607
								"DemandInfo.demand_status != 6",
								"DemandInfo.riro_kureka != 1"
								// 2015.6.18 n.kai ADD end ORANGE-443, ORANGE-450, ORANGE-596, ORANGE-607
						)
				),
				array (
						'fields' => '*',
						'type' => 'inner',
						"table" => "m_sites",
						"alias" => "MSite",
						"conditions" => array (
								"MSite.id = DemandInfo.site_id",
						)
				),
				array (
						'fields' => '*',
						'type' => 'inner',
						"table" => "m_items",
						"alias" => "MItem",
						"conditions" => array (
								"MItem.item_id = MSite.commission_type",
								"MItem.item_category = '取次形態'",
						)
				),
				array (
						'fields' => '*',
						'type' => 'inner',
						"table" => "commission_infos",
						"alias" => "CommissionInfo",
						"conditions" => array (
								"CommissionInfo.demand_id = BillInfo.demand_id",
								"CommissionInfo.id = BillInfo.commission_id",
// 2016.07.11 murata.s ORANGE-1 CHG(S)
// 								"CommissionInfo.complete_date != ''",
								'or' => array(
										"CommissionInfo.complete_date != ''",
										'BillInfo.auction_id is not null'
								),
// 2016.07.11 murata.s ORANGE-1 CHG(E)
								"CommissionInfo.del_flg != 1",
								"CommissionInfo.introduction_not != 1",
								// 2015.05.27 h.hara(S) ORANGE-451(S)
								"CommissionInfo.introduction_free != 1"
								// 2015.05.27 h.hara(S) ORANGE-451(E)
						)
				),
				array (
						'fields' => '*',
						'type' => 'inner',
						"table" => "m_corps",
						"alias" => "MCorp",
						"conditions" => array (
								"MCorp.id = CommissionInfo.corp_id",
								"MCorp.del_flg != 1"
						)
				),
		);

		return $joins;
	}

	/**
	 *
	 * 請求情報取得
	 * (請求情報,企業マスタ,案件情報,取次情報)
	 *
	 * @param unknown $conditions 検索条件
	 * @return unknown
	 */
	private function __get_bill_list($conditions = array()){

		$joins = self::__get_bill_joins();

		$results = $this->BillInfo->find ( 'all', array (
				// 2015.04.30 h.hara(S)
				//'fields' => '* , DemandInfo.id , DemandInfo.customer_name, DemandInfo.category_id, MItem.item_name, CommissionInfo.complete_date, CommissionInfo.tel_commission_datetime, MCorp.id, MCorp.official_corp_name',
				'fields' => '* , DemandInfo.id, DemandInfo.riro_kureka , DemandInfo.customer_name, DemandInfo.category_id, MItem.item_name, CommissionInfo.complete_date, CommissionInfo.tel_commission_datetime, MCorp.id, MCorp.official_corp_name',
				// 2015.04.30 h.hara(E)
				'conditions' => $conditions,
				'joins' => $joins,
				'order' => array (
						'BillInfo.id' => 'desc'
				)
		) );

		return $results;
	}

	/**
	 *  請求情報取得  ページネーション
	 * (請求情報,企業マスタ,案件情報,取次情報)
	 *
	 * @param unknown $conditions
	 * @return unknown
	 */
	private function __get_bill_page_list($conditions = array()){

		$joins = self::__get_bill_joins();

		$this->paginate = array(
				'fields' => '* , DemandInfo.id , DemandInfo.customer_name, DemandInfo.category_id, CommissionInfo.complete_date, MCorp.id, MCorp.official_corp_name',
				'conditions' => $conditions,
				'joins' => $joins,
				'limit' => Configure::read('list_limit'),
				'order' => array ('BillInfo.id' => 'desc'),
		);

		$results = $this->paginate('BillInfo');

		return $results;
	}

	/**
	 * 前月繰越残高取得
	 *
	 * @param unknown $conditions
	 */
	private function __get_bill_past_list($conditions = array()){

		$results = $this->BillInfo->find ( 'first', array (
				'fields' =>  'SUM(("BillInfo"."total_bill_price" - "BillInfo"."fee_payment_price"))AS "past_bill_price"',
				'conditions' => $conditions,
				'joins' => array (
						array (
								'fields' => '*',
								'type' => 'inner',
								"table" => "demand_infos",
								"alias" => "DemandInfo",
								"conditions" => array (
										"DemandInfo.id = BillInfo.demand_id"
								)
						),
						array (
								'fields' => '*',
								'type' => 'inner',
								"table" => "commission_infos",
								"alias" => "CommissionInfo",
								"conditions" => array (
										"CommissionInfo.demand_id = BillInfo.demand_id",
										"CommissionInfo.complete_date != ''"
								)
						),
						array (
								'fields' => '*',
								'type' => 'inner',
								"table" => "m_corps",
								"alias" => "MCorp",
								"conditions" => array (
										"MCorp.id = CommissionInfo.corp_id"
								)
						)
				),
		) );

		$past_bill_price = 0;
		if(!empty($results[0]['past_bill_price'])){
			$past_bill_price = $results[0]['past_bill_price'];
		}
		return $past_bill_price;
	}

	/**
	 *
	 * ID別請求情報取得
	 * (請求情報,企業マスタ,案件情報,取次情報)
	 *
	 * @param string $id 請求ID
	 * @return unknown
	 */
	private function __set_bill_data($id = null) {

		$results = $this->BillInfo->find ( 'first', array (
				'fields' => '*, MCorp.id, MCorp.official_corp_name, MCorp.commission_dial, MCorp.tel1, MCorp.tel2, DemandInfo.customer_name, DemandInfo.address1, DemandInfo.address2, DemandInfo.address3, DemandInfo.customer_tel, DemandInfo.tel1, DemandInfo.tel2, DemandInfo.postcode, CommissionInfo.complete_date,MSite.site_name,MGenre.genre_name',
				"alias" => "BillInfo",
				'conditions' => array ('BillInfo.id' => $id),
				'joins' => array (
						array (
								'fields' => '*',
								'type' => 'inner',
								"table" => "demand_infos",
								"alias" => "DemandInfo",
								"conditions" => array (
										"DemandInfo.id = BillInfo.demand_id"
								)
						),
						array (
								'fields' => '*',
								'type' => 'inner',
								"table" => "commission_infos",
								"alias" => "CommissionInfo",
								"conditions" => array (
										"CommissionInfo.demand_id = BillInfo.demand_id",
// 2016.07.11 murata.s ORANGE-1 CHG(S)
// 										"CommissionInfo.complete_date != ''"
										'or' => array(
												"CommissionInfo.complete_date != ''",
												'BillInfo.auction_id is not null'
										)
// 2016.07.11 murata.s ORANGE-1 CHG(E)
								)
						),
						array (
								'fields' => '*',
								'type' => 'inner',
								"table" => "m_corps",
								"alias" => "MCorp",
								"conditions" => array (
										"MCorp.id = CommissionInfo.corp_id"
								)
						),
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
						)
				),
		) );

		return $results;
	}

	/**
	 *
	 * 請求情報一覧からのデーター更新
	 *
	 * @param unknown $data
	 * @return boolean
	 */
	private function __bill_updata($data = array()) {

		if(!isset($data['target'])){
			$this->Session->setFlash ( __ ( 'BillDataNotCheck', true ), 'default', array (
					'class' => 'error_inner'
			) );
			return false;
		}

		try {
			$resultsFlg = true;
			$this->BillInfo->begin ();

			foreach ( $data ['target'] as $i ) {
				if (! empty ( $data ['BillInfo'] ['id'] [$i] ) && ! empty ( $data ['BillInfo'] ['modified'] [$i] )) {
					// 更新日付のチェック
					$resultsFlg = self::__check_modified_bill ( $data ['BillInfo'] ['id'] [$i], $data ['BillInfo'] ['modified'] [$i] );
					// 更新日でのエラーの場合
					if ($resultsFlg == false) {
						$this->Session->setFlash ( __ ( 'ModifiedNotCheck', true ), 'default', array (
								'class' => 'error_inner'
						) );
						break;
					}

					$updata ['fee_payment_price'] = $data ['BillInfo'] ['fee_payment_price'] [$i];
					$updata ['fee_payment_balance'] = $data ['BillInfo'] ['fee_payment_balance'] [$i];
					$resultsFlg = self::__edit_bill ( $data ['BillInfo'] ['id'] [$i], $updata );

					if ($resultsFlg == false) {
						$this->Session->setFlash ( __ ( 'InputError', true ), 'default', array (
								'class' => 'error_inner'
						) );
						break;
					}
				}
			}

			if ($resultsFlg) {
				$this->BillInfo->commit ();
				$this->Session->setFlash ( __ ( 'Updated', true ), 'default', array (
						'class' => 'message_inner'
				) );
			} else {
				$this->BillInfo->rollback ();
				return false;
			}
		} catch ( Exception $e ) {
			$this->BillInfo->rollback ();
		}

		return true;
	}

	/**
	 *
	 * 請求情報更新日時のチェック
	 *
	 * @param unknown $id
	 * @param unknown $modified
	 * @return boolean
	 */
	private function __check_modified_bill($id, $modified) {
		$results = $this->BillInfo->findByid ( $id );

		if ($modified == $results ['BillInfo'] ['modified']) {
			return true;
		}
		return false;
	}

	/**
	 *
	 * 請求情報の編集
	 *
	 * @param string $id
	 * @param string $fee_payment_price
	 * @param string $fee_payment_balance
	 * @return boolean
	 */
	private function __edit_bill($id = null, $data = array()) {

		$data ['id'] = $id; // ID
		// 請求書出力（請求状況 未発行⇒発行済）
		if(!isset($data ['bill_status'])){
			if ($data ['fee_payment_balance'] == 0) {
				$data ['bill_status'] = Util::getDivValue ( 'bill_status', 'payment' ); // 請求状況
			}
		}
		// 一覧からの変更（手数料入金日 システム日付）
		if(!empty($data ['fee_payment_price']) && !isset($data ['fee_payment_date'])){
			$data ['fee_payment_date'] = date('Y/m/d');
		}
		$save_data = array ('BillInfo' => $data);
		// 更新
		if ($this->BillInfo->save ( $save_data )) {
			return true;
		}
		return false;

	}

	/**
	 * 請求情報ステータス変更
	 *
	 * @param unknown $id  請求ID
	 * @param string $bill_status  請求状況
	 * @param string $fee_billing_date  手数料請求日
	 * @return boolean
	 */
	private function __bill_status_change($id , $bill_status = null , $fee_billing_date = null){

		$data['id'] = $id;
		if(!empty($bill_status)){
			$data['bill_status'] = $bill_status;
		}
		if(!empty($fee_billing_date)){
			$data['fee_billing_date'] = $fee_billing_date;
		}
		$save_data = array('BillInfo' => $data);

		// 更新
		if($this->BillInfo->save($save_data)){
			return true;
		}
		return false;

	}

	/**
	 *
	 * 一覧の検索結果をセッションにセット
	 *
	 * @return multitype:
	 */
	private function __billListPost() {
		$data = $this->request->data;

		$this->Session->delete ( self::$__sessionKeyForBillSearch );
		$this->Session->write ( self::$__sessionKeyForBillSearch, $data );

		return $data;
	}

	/**
	 *
	 * 一覧の検索結果を取得しセット
	 *
	 * @return multitype:Ambigous <NULL, mixed, multitype:>
	 */
	private function __billListGet() {

		$data = $this->Session->read ( self::$__sessionKeyForBillSearch );

		if (isset ( $data )) {
			$page = $this->__getPageNum ();
			$this->data = $data;
		}

		if (! empty ( $data )) {
			$data += array ('page' => $page);
		}

		return $data;
	}

	/**
	 * 入金履歴の登録
	 *
	 * @param unknown $corp_id
	 * @param unknown $data
	 * @return boolean
	 */
	private function __updata_money_correspond($data = array()){

		$data['MoneyCorrespond']['corp_id'] = $data['corp_id'];
		if($this->MoneyCorrespond->save($data['MoneyCorrespond'])){
			return true;
		}
		return false;
	}

	/**
	 *
	 * 入金履歴データ一覧の条件をセット
	 *
	 * @param unknown $data
	 * @return multitype:unknown
	 */
	private function __setSearchMoneyCorrespondList($data = array()){

		$conditions = array();

		$conditions['MoneyCorrespond.corp_id'] = $data['corp_id'];

		if(!empty($data['nominee'])){
			$conditions['z2h_kana(MoneyCorrespond.nominee) like'] = '%'. Util::chgSearchValue($data['nominee']) .'%';
		}
		return $conditions;
	}

	/**
	 *
	 * 入金履歴一覧の検索結果をセッションにセット
	 *
	 * @return multitype:
	 */
	private function __MoneyCorrespondListPost($corp_id = null) {

		if(!empty($corp_id)){
			$data['corp_id'] = $corp_id;
		} else {
			$data = $this->request->data;
		}

		$this->Session->delete ( self::$__sessionKeyForMoneyCorrespondSearch );
		$this->Session->write ( self::$__sessionKeyForMoneyCorrespondSearch, $data );

		return $data;
	}

	/**
	 *
	 * 入金履歴一覧の検索結果を取得しセット
	 *
	 * @return multitype:Ambigous <NULL, mixed, multitype:>
	 */
	private function __MoneyCorrespondGet() {

		$data = $this->Session->read ( self::$__sessionKeyForMoneyCorrespondSearch );


		if (isset ( $data )) {
			$page = $this->__getPageNum ();
			$this->data = $data;
		}

		if (! empty ( $data )) {
			$data += array ('page' => $page);
		}

		return $data;
	}

	/**
	 * 入金履歴一覧情報取得
	 *
	 * @param unknown $conditions
	 * @return multitype:
	 */
	private function __get_money_correspond_list($conditions = array()){

		$this->paginate = array(
				'fields' => '*',
				'conditions' => $conditions,
				'limit' => Configure::read('list_limit'),
				'order' => array ('MoneyCorrespond.payment_date' => 'desc' , 'MoneyCorrespond.id' => 'desc'),
		);

		$results = $this->paginate('MoneyCorrespond');

		return $results;
	}
}
