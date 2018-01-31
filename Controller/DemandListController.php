<?php
App::uses('AppController', 'Controller');

class DemandListController extends AppController {

	public $name = 'DemandList';
	public $helpers = array('Csv');
	//ORANGE-161 iwai CHG S
	public $components = array('Session', 'Csv', 'RequestHandler', 'DemandUtil');
	//ORANGE-161 iwai CHG E
	public $uses = array('DemandInfo', 'CommissionInfo', 'MCorp', 'MSite', 'MGenre', 'MCategory', 'MUser');

	public function beforeFilter() {

		$this->set('default_display', false);

		// 性能対策のため、アソシエーションを解除
		$this->DemandInfo->unbindModel(
			array(
				'hasMany' => array('CommissionInfo', 'IntroduceInfo', 'DemandCorrespondHistory'),
				'hasAndBelongsToMany' => array('MInquiry'),
			));

		$this->User = $this->Auth->user();

		// ドロップダウン用リストの取得
		$this->set("site_list", $this->MSite->getList());

		parent::beforeFilter();
	}

	// 初期表示（一覧）
	public function index() {

		$default_display = true;

		if ($this->RequestHandler->isGet()) {
			$data = $this->params['url'];

			// CTIより呼びだされた場合は検索を行う。
			if (!empty($data['cti'])) {
				if(empty($data["customer_tel"])){
//					$this->redirect("/demand/detail");
					$url = '/demand/cti/%s/%s';
					return $this->redirect(sprintf($url, '0', $data['site_tel']));
				}
				// 2016.02.20 h.hanaki ORANGE-1165
				if($data["customer_tel"] == '非通知'){
					$url = '/demand/cti/%s/%s';
					return $this->redirect(sprintf($url, '非通知', $data['site_tel']));
				}

				//ORANGE-186 CHG S
				$affiliation_results = $this->__searchAffiliationInfoAll($data['customer_tel']);
				if ($affiliation_results){
					if(count($affiliation_results) == 1){
						$url = '/affiliation/detail/%s';
						return $this->redirect(sprintf($url, $affiliation_results[0]['MCorp']['id']));
					}else{
						$affiliation_search = array(
								"tel1" => $data['customer_tel'],
								//以下はUndefined Index対策
								"support24hour" => "",
								"data[affiliation_status]" => ""
						);
						$this->Session->write(self::$__sessionKeyForAffiliationSearch, $affiliation_search);
						return $this->redirect("/affiliation/search");
					}
				}
				//ORANGE-186 CHG E

				$site_tel = $data["site_tel"];
				unset($data["site_tel"]);
				$conditions = self::setSearchConditions($data);
				$results = $this->__searchDemandInfo($conditions);
				$data["site_tel"] = $site_tel;
				//ORANGE-186 CHG S
				if(empty($results)){
//					$affiliation_results = $this->__searchAffiliationInfoAll($data['customer_tel']);
//					if (!$affiliation_results){
//						$this->redirect("/demand/detail");
						$url = '/demand/cti/%s/%s';
						return $this->redirect(sprintf($url, $data['customer_tel'], $data['site_tel']));
//					}else{
//						if(count($affiliation_results) == 1){
//							$url = '/affiliation/detail/%s';
//							return $this->redirect(sprintf($url, $affiliation_results[0]['MCorp']['id']));
//						}else{
//							$affiliation_search = array(
//								"tel1" => $data['customer_tel'],
//								//以下はUndefined Index対策
//								"support24hour" => "",
//								"data[affiliation_status]" => ""
//							);
//							$this->Session->write(self::$__sessionKeyForAffiliationSearch, $affiliation_search);
//							return $this->redirect("/affiliation/search");
//						}
//					}
				}
				//ORANGE-186 CHG E
				// 検索条件の設定
				$conditions = self::setSearchConditions($data);
				$results = $this->__searchDemandInfo($conditions);

				if (empty($data['site_tel']) || empty($results)){
//					$this->redirect("/demand/detail");
					// 2016.02.23 h.hanaki ORANGE-571 ADD(S)
					// サイトは存在するか
					$results = $this->__searchMsite($data['site_tel']);
					if (0 == count($results)) {
						// 電話番号は存在するか
						$results = $this->__searchCustmerTel($data['customer_tel']);
						if (0 < count($results)) {
							//検索パラメータの保存
							$data['site_tel'] = "";
							$this->Session->write(self::$__sessionKeyForDemandSearch, $data);
							$this->redirect("/demand_list/search");
						}
					}
					// 2016.02.23 h.hanaki ORANGE-571 ADD(E)
					$url = '/demand/cti/%s/%s';
					return $this->redirect(sprintf($url, $data['customer_tel'], $data['site_tel']));
				}else{
					if(count($results) == 1){
						$this->redirect(sprintf("/demand/detail/%s", $results[0]["DemandInfo"]["id"]));
					}else{
						//検索パラメータの保存
						$this->Session->write(self::$__sessionKeyForDemandSearch, $data);
						$this->redirect("/demand_list/search");
					}
				}

				// 2015.12.09 削除

				/*
			// 2015.5.17 n.kai ADD start
			// 非通知着信(customer_telがNULL)の場合、案件管理新規登録画面を表示する(ORANGE-460)
			if (empty($data['customer_tel'])) {
			$this->set('results', Sanitize::clean($results));
			$default_display = false;
			$url = '/demand/cti/%s/%s';
			return $this->redirect(sprintf($url, $data['customer_tel'], $data['site_tel']));
			}
			// 2015.5.17 n.kai ADD end

			// 情報があった場合は画面表示。ない場合は新規登録画面へ。
			if (!empty($results)) {
			$this->set('results', Sanitize::clean($results));
			$default_display = false;
			} else {
			//
			$affiliation_results = $this->__searchAffiliationInfo($data['customer_tel']);
			if (!empty($affiliation_results)) {
			$url = '/affiliation/index/%s/%s';
			return $this->redirect(sprintf($url, $data['customer_tel'] , $affiliation_results['MCorp']['affiliation_status']));
			}

			$url = '/demand/cti/%s/%s';
			return $this->redirect(sprintf($url, $data['customer_tel'], $data['site_tel']));
			}
			 */

			}
			//ORANGE-140 ADD S
			//PC画面からの顧客検索
			elseif(!empty($data['customer_tel'])){
				$this->Session->write(self::$__sessionKeyForDemandSearch, $data);
				$this->redirect("/demand_list/search");
			}
			//ORANGE-140 ADD E

		}

		$this->set('default_display', $default_display);
	}

	/**
	 * ページメイトのGETパラメーター処理
	 *
	 * @return Ambigous <multitype:Ambigous <NULL, mixed, multitype:> , mixed, boolean, NULL, unknown, array>
	 */
	private function demandListGet() {

		$data = $this->Session->read(self::$__sessionKeyForDemandSearch);

		if (isset($data)) {
			$page = $this->__getPageNum();
			$this->data = $data;
		}

		if (!empty($data)) {
			$data += array('page' => $page);
		}

		return $data;
	}

	/**
	 * ページメイトのPOSTパラメーター処理
	 *
	 * @return multitype:
	 */
	private function demandListPost() {

		// 入力値取得
		$data = $this->request->data;
		unset($data['csv_out']);
		// セッションに検索条件を保存
		$this->Session->delete(self::$__sessionKeyForDemandSearch);
		$this->Session->write(self::$__sessionKeyForDemandSearch, $data);

		return $data;
	}

	/**
	 * 検索ページ
	 *
	 * @param string $id
	 * @throws Exception
	 */
	public function search($id = null) {

		if (!empty($id)) {
			$this->__get_m_corp($id);
			$data = $this->demandListPost();
		}

		try {
			if ($this->RequestHandler->isPost()) {
				$data = $this->demandListPost();
			} else {
				$data = $this->demandListGet();
			}
		} catch (Exception $e) {
			throw $e;
		}

		self::__set_parameter_session();

		// 検索条件の設定
		$conditions = self::setSearchConditions($data);

		// CSV出力ボタン
		if (isset($this->request->data['csv_out']) && ($this->User["auth"] == 'system' || $this->User["auth"] == 'admin'|| $this->User['auth'] == 'accounting_admin')) {
			$data_list = $this->DemandUtil->getDataCsv($conditions);
			$file_name = mb_convert_encoding(__('demand', true) . '_' . $this->User['user_id'], 'SJIS-win', 'UTF-8');
			$this->Csv->download('DemandInfo', 'default', $file_name, $data_list);

		} else {

			$results = $this->__searchDemandInfo($conditions);

			$this->set('results', Sanitize::clean($results));

			$this->render('index');

		}
	}

	/**
	 * 案件情報を検索する
	 * @param unknown $conditions
	 * @return unknown
	 */
	private function __searchDemandInfo($conditions = array()) {

		$data = $this->request->data;

		// 企業名 または 企業名ふりがな が検索条件にあった場合
		// 		if(!empty($data['corp_name']) || !empty($data['corp_name_kana'])){

		// 			$db = $this->CommissionInfo->getDataSource();
		// 			$subQuery = $db->buildStatement(
		// 					array(
		// 							'fields' => array('"demand_id"'),
		// 							'table' => $db->fullTableName($this->CommissionInfo),
		// 							'alias' => 'CommissionInfo',
		// 							'joins' =>	array(array(
		// 									'table' => 'm_corps',
		// 									'alias' => "MCorp",
		// 									'type' => 'inner',
		// 									'conditions' => array('commissioninfo.corp_id = mcorp.id'))
		// 							),
		// 							'conditions' => $conditions,

		// 					),
		// 					$this->CommissionInfo
		// 			);

		// 			$subQuery = ' "DemandInfo"."id" in ('. $subQuery . ') ';
		// 			$subQueryExpression = $db->expression($subQuery);

		// 			$conditions = (array)$subQueryExpression->value;
		// 		}

		$joins = array();
		$joins = self::setSearchJoin();

		$param = array();

		// 検索する
		$this->paginate = array('conditions' => $conditions,
			'fields' => '*, MSite.site_name, MCategory.category_name',
			'joins' => $joins, 'limit' => Configure::read('list_limit'),
// 2015.07.16 h.hanaki ADD (S) ORANGE-658
			//				'order' => array('DemandInfo.contact_desired_time' => 'asc'),);
			'order' => array('DemandInfo.id' => 'desc'));
// 2015.07.16 h.hanaki ADD (E) ORANGE-658
		//$this->DemandInfo->setVirtualDetectContactDesiredTime();
		$results = $this->paginate('DemandInfo');

		return $results;
	}


	/**
	 * 検索条件設定
	 *
	 * @param string $data
	 * @return multitype:string
	 */
	private function setSearchConditions($data = null) {
		$conditions = array();

// 		// 企業名
		// 		if (!empty($data['corp_name'])) {
		// //			$conditions['z2h_kana(mcorp.corp_name) like'] = '%'. Util::chgSearchValue($data['corp_name']) .'%';
		// 			$conditions['mcorp.corp_name like'] = '%'. $data['corp_name'] .'%';
		// 		}

// 		// 企業名ふりがな
		// 		if (!empty($data['corp_name_kana'])) {
		// //			$conditions['z2h_kana(mcorp.corp_name_kana) like'] = '%'. Util::chgSearchValue($data['corp_name_kana']) .'%';
		// 			$conditions['mcorp.corp_name_kana like'] = '%'. $data['corp_name_kana'] .'%';
		// 		}

		// 企業名または、企業名ふりがな
		if (!empty($data['corp_name']) || !empty($data['corp_name_kana'])) {
			$sql = 'exists( SELECT demand_id FROM commission_infos INNER JOIN m_corps ON ( commission_infos.corp_id = m_corps.id )
                        WHERE %s AND DemandInfo.id = commission_infos.demand_id)';

			if (!empty($data['corp_name']) && !empty($data['corp_name_kana'])) {
				$value = 'm_corps.corp_name LIKE \'%' . Sanitize::escape($data['corp_name']) . '%\' and m_corps.corp_name_kana LIKE \'%' . Sanitize::escape($data['corp_name_kana']) . '%\'';
			} else if (!empty($data['corp_name'])) {
				$value = 'm_corps.corp_name LIKE \'%' . Sanitize::escape($data['corp_name']) . '%\'';
			} else if (!empty($data['corp_name_kana'])) {
				$value = 'm_corps.corp_name_kana LIKE \'%' . Sanitize::escape($data['corp_name_kana']) . '%\'';
			}

			$sql = sprintf($sql, $value);
			$conditions = array($sql);
		}

		// お客様電話番号
		if (!empty($data['customer_tel'])) {
			//ORANGE-4 CHG S
//			$conditions = array(
//				'or' => array(
//					array(
////									'z2h_kana(DemandInfo.customer_tel)' => Util::chgSearchValue($data['customer_tel'])
//						'DemandInfo.customer_tel' => $data['customer_tel']
//					),
//					array(
////									'z2h_kana(DemandInfo.tel1)' => Util::chgSearchValue($data['customer_tel'])
//						'DemandInfo.tel1' => $data['customer_tel']
//					),
//		//	2016.02.19 h.hanaki ORANGE-571 連絡先2を検索対象とする
//					array(
//						'DemandInfo.tel2' => $data['customer_tel']
//					)
//				)
//			);
			//$conditions['DemandInfo.customer_tel'] = $data['customer_tel'];

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

		// お客様名
		if (!empty($data['customer_name'])) {
//			$conditions['z2h_kana(DemandInfo.customer_name) like'] = '%'. Util::chgSearchValue($data['customer_name']) .'%';
			$conditions['DemandInfo.customer_name like'] = '%' . $data['customer_name'] . '%';
		}

		// 案件ID
		if (!empty($data['id'])) {
			$conditions['DemandInfo.id'] = Util::chgSearchValue($data['id']);
		}

		// サイト電話番号
		if (!empty($data['site_tel'])) {
// 			$conditions['z2h_kana(MSite.site_tel)'] = Util::chgSearchValue($data['site_tel']);
			// 2015.03.19 h.hara CTI連携時はサイト電話番号を条件にしない
			/* 2015.12.09 削除
			if (empty($data['cti'])) {
			$conditions['MSite.site_tel'] = Util::chgSearchValue($data['site_tel']);
			}
			 */
			// 2015.12.09 cti経由かどうかの条件を削除
			$conditions['MSite.site_tel'] = Util::chgSearchValue($data['site_tel']);
		}

		// 連絡希望日時 From
		if (!empty($data['from_contact_desired_time'])) {
			$conditions['DemandInfo.detect_contact_desired_time >='] = $data['from_contact_desired_time'];
		}

		// 連絡希望日時 To
		if (!empty($data['to_contact_desired_time'])) {
			$conditions['DemandInfo.detect_contact_desired_time <='] = $data['to_contact_desired_time'];
		}

		// 案件状況
		if (!empty($data['demand_status'])) {
			$conditions['DemandInfo.demand_status'] = $data['demand_status'];
		}

		// サイト名
		if (!empty($data['site_id'])) {
			$conditions['DemandInfo.site_id'] = $data['site_id'];
		}

		// JBR様受付No
		if (!empty($data['jbr_order_no'])) {
// 			$conditions['z2h_kana(DemandInfo.jbr_order_no)'] = Util::chgSearchValue($data['jbr_order_no']);
			$conditions['DemandInfo.jbr_order_no'] = $data['jbr_order_no'];
		}

		// 受信日時 From
		if (!empty($data['from_receive_datetime'])) {
			$conditions['DemandInfo.receive_datetime >='] = $data['from_receive_datetime'];
		}

		// 受信日時 To
		if (!empty($data['to_receive_datetime'])) {
			$conditions['DemandInfo.receive_datetime <='] = $data['to_receive_datetime'];
		}

		// 2015.5.18 n.kai ADD start
		// 後追い日 From
		if (!empty($data['from_follow_date'])) {
			$conditions['DemandInfo.follow_date >='] = $data['from_follow_date'];
		}

		// 後追い日 To
		if (!empty($data['to_follow_date'])) {
			$conditions['DemandInfo.follow_date <='] = $data['to_follow_date'];
		}
		// 2015.5.18 n.kai ADD end

		return $conditions;
	}

	/**
	 * 検索用Join句作成
	 *
	 * @return multitype:multitype:string multitype:string
	 */
	private function setSearchJoin() {

		$joins = array();
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
			)
		);

		return $joins;

	}

	/**
	 * ID別企業情報を取得
	 *
	 * @param string $id
	 */
	private function __get_m_corp($id = null) {
		$results = $this->MCorp->findByid($id);
		if (!empty($results)) {
			$this->request->data['corp_name'] = $results['MCorp']['corp_name'];
			$this->request->data['corp_name_kana'] = $results['MCorp']['corp_name_kana'];
		}
	}

	/**
	 * GETパラメーターをセッションに保存
	 *
	 */
	private function __set_parameter_session() {
		$this->Session->delete(self::$__sessionKeyForDemandParameter);
		$this->Session->write(self::$__sessionKeyForDemandParameter, $this->params['named']);
	}

	/**
	 * 加盟店CTI検索
	 *
	 * @param unknown $customer_tel
	 * @return unknown
	 */
	private function __searchAffiliationInfo($customer_tel) {
		//2016.04.26 iwai ORANGE-1376 CHG S
		$conditions = array('or' => array(array('commission_dial' => $customer_tel), array('tel1' => $customer_tel), array('tel2' => $customer_tel), array('mobile_tel' => $customer_tel)));
		//2016.04.26 iwai ORANGE-1376 CHG E
		$results = $this->MCorp->find('first',
			array('conditions' => $conditions)
		);
		return $results;
	}

	/**
	 * 加盟店CTI検索
	 *
	 * @param unknown $customer_tel
	 * @return unknown
	 */
	private function __searchAffiliationInfoAll($customer_tel) {
		//2016.04.26 iwai ORANGE-1376 CHG S
		$conditions = array('or' => array(array('commission_dial' => $customer_tel), array('tel1' => $customer_tel), array('tel2' => $customer_tel), array('mobile_tel' => $customer_tel)));
		//2016.04.26 iwai ORANGE-1376 CHG E
		$results = $this->MCorp->find('all',
			array('conditions' => $conditions)
		);
		return $results;
	}


	// 2016.02.23 h.hanaki ORANGE-571 ADD
	/**
	 * サイト電話番号の検索
	 *
	 * @return unknown
	 */
	private function __searchMsite($site_tel){

		// サイト電話番号からサイトIDを取得
		$conditions = array('site_tel' => $site_tel);
		$results = $this->MSite->find('first',
							 array('conditions'=>$conditions,
							 		'fields' => array('id'),
									'order' => 'MSite.id asc'
							)
		);
		return $results;
	}


	// 2016.02.23 h.hanaki ORANGE-571 ADD
	/**
	 * カスタマー電話番号の検索
	 *
	 * @return unknown
	 */
	private function __searchCustmerTel($custmer_tel){
		$customer_tel_fun = $custmer_tel;
		$conditions = array(
			'or' => array(
				array(
					'DemandInfo.customer_tel' => $customer_tel_fun
				),
				array(
					'DemandInfo.tel1' => $customer_tel_fun
				),
				array(
					'DemandInfo.tel2' => $customer_tel_fun
				)
			)
		);
		$this->DemandInfo->unbindModelAll(false); // アソシエーション解除
		$results = $this->DemandInfo->find('all',
							 array('conditions'=>$conditions,
							 		'fields' => id,
									'order' => 'DemandInfo.id asc'
							)
		);
		return $results;
	}

}
