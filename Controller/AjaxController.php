<?php
App::uses('AppController', 'Controller');
App::uses('DetectView', 'Lib/View');
App::import('Vendor', 'Pear');
App::import('Vendor', 'Calendar_Month_Weekdays', array('file' => 'PEAR' . DS . 'Calendar' . DS . 'Month' . DS . 'Weekdays.php'));

class AjaxController extends AppController {
	public $name = 'Ajax';
	public $helpers = array();
	// 2015.06.04 y.fujimori MOD start
	//public $uses = array('MCorp', 'MSite', 'MPost', 'MTaxRate', 'MCorpCategory', 'MCategory', 'MSiteGenre', 'MSiteCategory', 'MTargetArea', 'MAnswer', 'MInquiry', 'MCommissionType', 'GeneralSearch');
	public $uses = array(
						'MCorp',
						'MSite',
						'MPost',
						'MTaxRate',
						'MCorpCategory',
						'MGenre',
						'MCategory',
						'MSiteGenre',
						'MSiteCategory',
						'MTargetArea',
						'MAnswer',
						'MInquiry',
						'MCommissionType',
						'GeneralSearch',
						'SelectGenrePrefecture',
						'SelectGenre',
						'ExclusionTime',
						'CommissionTelSupport',
						'CommissionOrderSupport',
						'CommissionVisitSupport',
						'CommissionInfo',
						'PublicHoliday',
					//ORANGE-234 ADD S
						'CommissionCorrespond',
					//ORANGE-234 ADD E
                                                'CommissionSearchItems',
                                                'Report',
                                                'MUser'
	);
	// 2015.06.04 y.fujimori MOD end
// 2017.01.04 murata.s ORANGE-244 CHG(S)
	public $components = array('BillPriceUtil');
// 2017.01.04 murata.s ORANGE-244 CHG(E)
	public $viewClass = 'Detect';


	/**
	 * beforeFilter
	 */
	public function beforeFilter() {
		$this->autoRender = false;
		$this->Security->csrfCheck = false;

		// 2016.04.12 murata.s ADD start 権限によるURLの制限
		$this->checkAuth(strtolower($this->name));
		// 2016.04.12 murata.s ADD end   権限によるURLの制限

// 2016.10.06 murata.s ORANGE-206 ADD(S) 脆弱性 権限外の操作対応
		$this->User = $this->Auth->user();
		if($this->User['auth'] == 'affiliation'){
			$actionName = strtolower($this->action);
			switch ($actionName) {
				case 'searchtargetarea':
					$check_data = $this->MCorpCategory->findById($this->request->params['pass'][0]);
					if(!empty($check_data) && $check_data['MCorpCategory']['corp_id'] != $this->User['affiliation_id'])
						throw new ApplicationException(__('NoReferenceAuthority', true));
					break;
				default:
					break;
			}
		}
// 2016.01.06 murata.s ORANGE-206 ADD(E) 脆弱性 権限外の操作対応

	}

	/**
	 * afterFilter
	 */
	public function afterFilter() {
	}

	/**
	 * 郵便番号(住所)取得用
	 */
	public function searchAddressByZip() {

		try {
			if ($this->request->is('ajax')) {

				// 郵便番号を取得
				$zipCode = isset($this->request->query['zip']) ? $this->request->query['zip'] : null;
				$zipCode = str_replace('-', '', $zipCode);

				$params = Array(
						'conditions' => Array('MPost.post_cd' => $zipCode),
						'fields' => Array('substr("MPost"."jis_cd", 1, 2) as "MPost__jis_cd"', 'MPost.address2', 'MPost.address3'),);

				// 取得 & アサイン
				$results = $this->MPost->find('all', $params);
				$results = array_shift($results);

				$this->__renderJson(Sanitize::clean($results));
			}
		} catch (Exception $e) {
			//ORANGE-360 ADD S
			$this->log($e->getMessage(), LOG_ERR);
			//ORANGE-360 ADD E
			throw $e;
		}
	}

	/**
	 * 消費税の取得
	 *
	 * @param string $data
	 */
	public function searchTaxRate($data = null) {

		$tax_rate = '';

		if(!empty($data)){
			$data = str_replace("-","/", $data);
			if( preg_match('/^([1-9][0-9]{3})\/(0[1-9]{1}|1[0-2]{1})\/(0[1-9]{1}|[1-2]{1}[0-9]{1}|3[0-1]{1})$/', $data)){
			$conditions = array('start_date <=' => $data ,
					'or' => array("end_date = ''",
							'end_date >=' => $data),
			);
			$results = $this->MTaxRate->find('first',
					array( 'conditions' => $conditions,)
			);

			//if(!empty($results)){
				$tax_rate = $results['MTaxRate']['tax_rate']*100;
			//}
			}
		}


		$this->layout = false;
		$this->set("tax_rate" , $tax_rate);
// 2016.08.25 murata.s ORANGE-130 CHG(S)
//		$this->render('tax_rate');
		if(Util::isMobile()  && $_SESSION['Auth']['User']['auth'] == 'affiliation') {
			$this->render('tax_rate_m');
		} else {
			$this->render('tax_rate');
		}
// 2016.08.25 murata.s ORANGE-130 CHG(E)
	}

	public function searchTaxRateOnly($data = null) {

		$tax_rate = '';

		if(!empty($data)){
			$data = str_replace("-","/", $data);
			if( preg_match('/^([1-9][0-9]{3})\/(0[1-9]{1}|1[0-2]{1})\/(0[1-9]{1}|[1-2]{1}[0-9]{1}|3[0-1]{1})$/', $data)){
				$conditions = array('start_date <=' => $data ,
						'or' => array("end_date = ''",
								'end_date >=' => $data),
				);
				$results = $this->MTaxRate->find('first',
						array( 'conditions' => $conditions,)
				);

				//if(!empty($results)){
				$tax_rate = $results['MTaxRate']['tax_rate']*100;
				//}
			}
		}


		$this->layout = false;
		return $tax_rate;
	}

	/**
	 * 対応エリア取得
	 *
	 * @param string $data
	 */
	public function searchTargetArea($id = null , $address1 = null) {

		$conditions['MPost.address1'] = $address1;

		$results = $this->MPost->find('all',
// 2016.08.04 murata.s ORANGE-118 CHG(S)
// 				array('fields' => 'MPost.address2 , MPost.jis_cd , MTargetArea.id',
				array('fields' => 'MPost.address2 , MPost.jis_cd , max(MTargetArea.id) as "MTargetArea__id"',
// 2016.08.04 murata.s ORANGE-118 CHG(S)
						'joins' =>  array(
							array('fields' => '*',
									'type' => 'LEFT',
									"table" => "m_target_areas",
									"alias" => "MTargetArea",
									"conditions" => array("MTargetArea.jis_cd = MPost.jis_cd" , "MTargetArea.corp_category_id = ".$id)
							),
						),
						'conditions' => $conditions ,
// 2016.08.04 murata.s ORANGE-118 CHG(S)
// 						'group' => 'MPost.jis_cd , MPost.address2 , MTargetArea.id',
						'group' => 'MPost.jis_cd , MPost.address2',
// 2016.08.04 murata.s ORANGE-118 CHG(E)
						'order' => array('MPost.jis_cd'=> 'asc'),
				)
		);

		$this->layout = false;
		$this->set("list" , $results);
		$this->render('target_area');
	}

	/**
	 * サイト情報取得
	 *
	 * @param string $id
	 * @param unknown $content 1:サイトURL 2:取次形態(id) 3:取次形態(内容)
	 */
	public function siteData($id = null , $content = null) {

		$params = array('fields' => 'MSite.* , MCommissionType.commission_type_div, MCommissionType.commission_type_name',
							'joins' =>  array(
								array('fields' => '*',
										'type' => 'inner',
										"table" => "m_commission_types",
										"alias" => "MCommissionType",
										"conditions" => array("MSite.commission_type = MCommissionType.id")
								),
							),
							'conditions' => array('MSite.id' => $id),
					);

		if($content == 1){
			$results = $this->MSite->findById($id);
			$this->set("list" , $results);
		}
		else if($content == 2) {
			$result = $this->MSite->find('first', $params);
			$this->set("list" , $result);
		} else {
			$result = $this->MSite->find('first', $params);
			$this->set("text" , $result['MCommissionType']['commission_type_name']);
		}

		$this->set("content" , $content);
		$this->layout = false;
		$this->render('site_data');
	}

	/**
	 * カテゴリの削除
	 *
	 * @param string $id
	 */
	public function deleteCategory($id = null) {
		$resultsFlg = $this->MCorpCategory->delete($id);
		exit();
	}

	/**
	 * JsonViewを返す場合のレンダー処理です
	 */
	private function __renderJson($contents = array(), $params = array()) {
		$params = Set::merge(array('header' => true, 'debugOff' => true,),
				$params);
		if ($params['debugOff']) {
			Configure::write('debug', 0);
		}
		if ($params['header']) {
			$this->RequestHandler->setContent('json');
			$this->RequestHandler->respondAs('application/json; charset=UTF-8');
		}

		$this->layout = false;
		$this->set(compact('contents'));
		$this->render('json');
	}

	/**
	 * ジャンルリストの取得
	 * (サイトに紐づくジャンルを取得)
	 *
	 * @param unknown_type $site
	 */
	public function genre_list($site) {

		$this->layout = false;
		// murata.s ORANGE-480 CHG(S)
		$this->set('genre_list', $this->MSiteGenre->getGenreBySiteStHide($site));
		// murata.s ORANGE-480 CHG(E)
		$this->render('genre_list');

	}

	// 2015.4.7 n.kai ADD start
	/**
	 * ジャンルリストの取得
	 * (【クロスセル】サイトに紐づくジャンルを取得)
	 *
	 * @param unknown_type $site
	 */
	public function genre_list2($site) {

		$this->layout = false;
		$this->set('genre_list', $this->MSiteGenre->getGenreBySite($site));
		$this->render('genre_list');

	}
	// 2015.4.7 n.kai ADD end

	/**
	 * カテゴリリストの取得
	 * (ジャンルに紐づくカテゴリを取得)
	 *
	 * @param string $genre_id
	 */
	public function category_list($genre_id = null) {

		$this->layout = false;
		// murata.s ORANGE-480 CHG(S)
		$this->set('category_list', $this->MCategory->getListStHide($genre_id));
		// murata.s ORANGE-480 CHG(E)
		$this->render('category_list');
    }

	// 2017/12/05 h.miyake ORANGE-603 ADD(S)
	/**
	 * ジャンルリストの取得
	 * (加盟店IDに紐づくジャンルを取得)
	 *
	 * @param string $corp_id
	 */
	public function genre_list3($corp_id = null) {
		$this->layout = false;
		$list = $this->MCorpCategory->getGenreIdNameMCorpList($corp_id);
		$this->set('category_list', $list);
		$this->render('category_list');
	}

	/**
	 * カテゴリリストの取得
	 * (加盟店ID・ジャンルに紐づくジャンルを取得)
	 *
	 * @param string $corp_id, $genre_id
	 */
	public function category_list3($corp_id = null, $genre_id = null) {
		$this->layout = false;
		if(!empty($corp_id) && !empty($genre_id)) {
			$list = $this->MCorpCategory->getcategoryNameMCorpGenreIdList($corp_id, $genre_id);
			$this->set('category_list', $list);
			$this->render('category_list');
		}
	}
	// 2017/12/05 h.miyake ORANGE-603 ADD(E)

	/**
	 * カテゴリリストの取得
	 * (【クロスセル】サイトに紐づくカテゴリを取得)
	 *
	 * @param string $site
	 */
	public function category_list2($site = null) {

		$this->layout = false;
		$this->set('category_list', $this->MSiteCategory->getCategoriesBySite($site));
		$this->render('category_list');

	}

	// 2015.06.04 y.fujimori ADD start ORANGE-485
	/**
	 * カテゴリリストの取得
	 * (ジャンルに紐づくカテゴリを取得)
	 *
	 * @param string $genre_id
	 */
	public function order_fee($genre_id = null) {

		$this->layout = false;
		$this->set('default_fee', $this->MGenre->getDefaultFee($genre_id));
		$this->render('order_fee');

	}

	/**
	 * カテゴリリストの取得
	 * (ジャンルに紐づくカテゴリを取得)
	 *
	 * @param string $genre_id
	 */
	public function order_fee_unit($genre_id = null) {

		$this->layout = false;
		$this->set('default_fee_unit', $this->MGenre->getDefaultFeeUnit($genre_id));
		$this->render('order_fee_unit');

	}
	// 2015.06.04 y.fujimori ADD end

	// 2015.07.31 s.harada ADD start ORANGE-627
	/**
	 * ヒアリング項目の取得
	 * (ジャンルに紐づくヒアリング項目を取得)
	 *
	 * @param string $genre_id
	 */
	public function inquiry_item_data($genre_id = null) {

		$this->layout = false;
		$MGenreData = $this->MGenre->findById($genre_id);
		$inquiry_item = $MGenreData["MGenre"]['inquiry_item'];
		$this->set('inquiry_item', $inquiry_item);
		$this->render('inquiry_item');
	}

	/**
	 * リッツ側注意事項の取得
	 * (ジャンルに紐づくリッツ側注意事項を取得)
	 *
	 * @param string $genre_id
	 */
	public function attention_data($genre_id = null) {

		$this->layout = false;
		$MGenreData = $this->MGenre->findById($genre_id);
		$attention = $MGenreData["MGenre"]['attention'];
		$this->set('attention', $attention);
		$this->render('attention');
	}
	// 2015.07.31 s.harada ADD end ORANGE-627

	// 2015.08.02 s.harada ADD start ORANGE-643
	/**
	 * 現在日時の取得
	 */
	public function now_datetime() {

		$this->layout = false;
		$now_datetime = date("Y/m/d H:i");
		$this->set('now_datetime', $now_datetime);
		$this->render('now_datetime');
	}
	// 2015.08.02 s.harada ADD end ORANGE-643

	/**
	 * ヒアリング項目の取得(Ajax通信用)
	 *
	 * @param unknown_type $site
	 */
	public function inquiry_list($category = null) {

		$conditions = array('category_id' => $category);

		$inquiry_list = $this->MInquiry->find('all', array('conditions' => $conditions, 'order' => array('MInquiry.id' => 'asc')));

		for ($i = 0; $i < count($inquiry_list); $i++) {
			$inquiry_list[$i]['MAnswer'] = $this->MAnswer->dropDownAnswer($inquiry_list[$i]['MInquiry']['id']);
		}

		$this->layout = false;
		$this->set('inquiry_list', $inquiry_list);
		$this->render('inquiry_list');

	}

	/**
	 * 案件管理のカテゴリ修正時の取次情報変更
	 *
	 * @param unknown $dis_num
	 * @param unknown $id
	 */
	public function commission_change($num , $category = null , $id = null){

		if(empty($category)){
			$category = 0;
		}

		// 加盟状態 (加盟)
		$conditions['MCorp.affiliation_status'] = 1;

		// 企業名
		if(!empty($id)){
			$conditions['MCorp.id'] = $id;
		}

		// 休業日
		$fields_holiday = '(SELECT ARRAY_TO_STRING(ARRAY( SELECT item_name FROM m_items INNER JOIN m_corp_subs ON m_corp_subs.item_category = m_items.item_category AND m_corp_subs.item_id = m_items.item_id WHERE m_corp_subs.item_category = \''.__('holiday' , true).'\' AND m_corp_subs.corp_id = "MCorp"."id" ORDER BY m_items.sort_order ASC ),\'｜\') as "MCorp__holiday" )';

		$results = $this->MCorp->find('first',
				array( 'fields' => 'MCorp.id, MCorp.corp_name, MCorp.commission_dial, MCorp.coordination_method, MCorp.mailaddress_pc, MCorp.fax, MCorp.contactable_time, MCorp.note, AffiliationInfo.fee, AffiliationInfo.commission_unit_price, AffiliationInfo.attention, MCorpCategory.order_fee, MCorpCategory.order_fee_unit, MCorpCategory.note, AffiliationStat.commission_unit_price_category, '.$fields_holiday,
						'joins' =>  array(
								array('fields' => '*',
										'type' => 'inner',
										"table" => "affiliation_infos",
										"alias" => "AffiliationInfo",
										"conditions" => array("AffiliationInfo.corp_id = MCorp.id")
								),
								array('fields' => '*',
										'type' => 'left',
										"table" => "m_corp_categories",
										"alias" => "MCorpCategory",
										"conditions" => array("MCorpCategory.corp_id = MCorp.id" , 'MCorpCategory.category_id ='. $category)
								),
								array('fields' => '*',
										'type' => 'left',
										"table" => "affiliation_stats",
										"alias" => "AffiliationStat",
										"conditions" => array("AffiliationStat.corp_id = MCorp.id" , "AffiliationStat.genre_id = MCorpCategory.genre_id"),
								),

						),
						'conditions' => $conditions,
						'hasMany'=>array('MTargetArea'),
				)
		);

		$this->layout = false;
		$this->set ( 'data', $results );
		$this->set ( 'num', $num );
		$this->render ( 'commission_change' );
	}

	/**
	 * 企業対応エリア取得
	 *     2015.02.22 企業エリアマスタ追加に伴い追加
	 * @param string $data
	 */
	public function searchCorpTargetArea($id = null , $address1 = null) {

		$conditions['MPost.address1'] = $address1;

		$results = $this->MPost->find('all',
				array('fields' => 'MPost.address2 , MPost.jis_cd , MCorpTargetArea.corp_id',
						'joins' =>  array(
								array('fields' => '*',
										'type' => 'LEFT',
										"table" => "m_corp_target_areas",
										"alias" => "MCorpTargetArea",
										"conditions" => array("MCorpTargetArea.jis_cd = MPost.jis_cd" , "MCorpTargetArea.corp_id = ".$id)
								),
						),
						'conditions' => $conditions ,
						'group' => 'MPost.jis_cd , MPost.address2 , MCorpTargetArea.corp_id',
						'order' => array('MPost.jis_cd'=> 'asc'),
				)
		);

		$this->layout = false;
		$this->set("list" , $results);
		$this->render('corp_target_area');
	}

	public function searchMGeneralSearch() {
		try {
			if (!$this->request->is('ajax')) {
				return;
			}

			// ログインユーザーの権限によって公開範囲条件を指定
			switch ($this->Auth->user('auth')) {
				case 'popular':
					$auth_condition = "auth_popular = 1";
					break;
				case 'admin':
				case 'system':
					$auth_condition = "auth_admin = 1 or auth_popular = 1";
					break;
				case 'accounting_admin':
					$auth_condition = "auth_accounting_admin = 1 or auth_popular = 1";
					break;
				case 'accounting':
					$auth_condition = "auth_accounting = 1 or auth_popular = 1";
					break;
				case 'affiliation':
				default :
					$auth_condition = "";
			}

			if (strlen($auth_condition) > 0) {
				$results = $this->GeneralSearch->findMGeneralSearch('all', array(
					'conditions' => array($auth_condition),
					'order' => array('MGeneralSearch.id' => 'desc'),
				));
			} else {
				$results = array();
			}
			$this->__renderJson(Sanitize::clean($results));
		} catch (Exception $e) {
			throw $e;
		}
	}
        
        public function searchMCommissionSearch() {
            try {
                if (!$this->request->is('ajax')) {
                    return;
                }

                $results = $this->Report->findCorpCommissionSearch('all', array(
                    'fields' => 'CommissionSearchItems.*, MUser.user_name',
                    'joins' =>  array(
                        array(
                                'fields' => '*',
                                'type' => 'INNER',
                                "table" => "m_users",
                                "alias" => "MUser",
                                "conditions" => array("MUser.id = CommissionSearchItems.update_user_id")
                        ),
                    ),
                    'order' => array('CommissionSearchItems.id' => 'desc'),
                ));
                $this->__renderJson(Sanitize::clean($results));
            } catch (Exception $e) {
                throw $e;
            }
	}

	public function csvPreview($id = null) {
		try {
			//if ($this->request->is('ajax')) {
			//保存名称と権限を仮設定
			$this->request->data['MGeneralSearch']['definition_name'] = "temporary";
			$this->request->data['MGeneralSearch']['auth_admin'] = 1;
			$this->request->data['MGeneralSearch']['id'] = "";
			//TODO:データの仮保存
			$id = $this->GeneralSearch->saveGeneralSearch($this->request->data);
			//TODO:仮保存したデータからデータを取得
			$results = $this->GeneralSearch->getCsvPreview($id);
			//TODO:仮保存データの削除
			$this->GeneralSearch->deleteGeneralSearch($id);

			$this->__renderJson(Sanitize::clean($results));
			//}
		} catch (Exception $e) {
			throw $e;
		}
	}

	/**
	 * 出張費の取得
	 *
	 * @param unknown_type $site
	 */
	public function travel_expenses($genre_id = null, $address1 = null) {

		if(!empty($genre_id) && !empty($genre_id)){
			$data = $this->SelectGenrePrefecture->findByGenreIdAndPrefectureCd($genre_id, $address1);
			if(!empty($data)){
				echo $data['SelectGenrePrefecture']['business_trip_amount'];
			}
		}
		exit();
	}

	/**
	 * 選定方式のセレクトボックス
	 *
	 * @param string $genre_id
	 * @param string $address1
	 */
	public function selection_system_list($genre_id = null, $address1 = null) {

		// 2015/09/28 Inokuchi (S)
// 		$flg = true;
// 		$genre_data = $this->SelectGenre->findByGenreId($genre_id);
// 		if(empty($genre_data)){
// 			$flg = false;
// 		} else {
// 			if($genre_data['SelectGenre']['select_type'] != Util::getDivValue('selection_type', 'AuctionSelection')){
// 				$flg = false;
// 			} else {
// 				if(!empty($address1)){
// 					$genre_prefecture_data = $this->SelectGenrePrefecture->findByGenreIdAndPrefectureCd($genre_id, $address1);
// 					if(!empty($genre_prefecture_data)){
// 						if($genre_prefecture_data['SelectGenrePrefecture']['selection_type'] != "" && $genre_prefecture_data['SelectGenrePrefecture']['selection_type'] != NULL && $genre_prefecture_data['SelectGenrePrefecture']['selection_type'] != Util::getDivValue('selection_type', 'AuctionSelection')){
// 							$flg = false;
// 						}
// 					}
// 				}
// 			}
// 		}
		$flg = false;
// 2016.11.17 murata.s ORANGE-185 ADD(S)
		$selection_system = Util::getDivValue('selection_type', 'ManualSelection');
// 2016.11.17 murata.s ORANGE-185 ADD(E)
		if(!empty($address1)){
			$genre_prefecture_data = $this->SelectGenrePrefecture->findByGenreIdAndPrefectureCd($genre_id, $address1);
			if(!empty($genre_prefecture_data)){
				if(!empty($genre_prefecture_data['SelectGenrePrefecture']['selection_type']) && $genre_prefecture_data['SelectGenrePrefecture']['selection_type'] == Util::getDivValue('selection_type', 'AuctionSelection')){
					$flg = true;
				}
// 2016.11.17 murata.s ORANGE-185 ADD(S)
				if(!empty($genre_prefecture_data['SelectGenrePrefecture']['selection_type']))
					$selection_system = $genre_prefecture_data['SelectGenrePrefecture']['selection_type'];
// 2016.11.17 murata.s ORANGE-185 ADD(E)
			} else {
				$genre_data = $this->SelectGenre->findByGenreId($genre_id);
				if(!empty($genre_data)){
					if($genre_data['SelectGenre']['select_type'] == Util::getDivValue('selection_type', 'AuctionSelection')){
						$flg = true;
					}
// 2016.11.17 murata.s ORANGE-185 ADD(S)
					$selection_system = $genre_data['SelectGenre']['select_type'];
// 2016.11.17 murata.s ORANGE-185 ADD(E)
				}
			}
		}
		// 2016.12.15 murata.s ORANGE-283 ADD(S)
		else{
			// エリア(都道府県)が選択されていない場合
			$genre_data = $this->SelectGenre->findByGenreId($genre_id);
			if(!empty($genre_data)){
				if($genre_data['SelectGenre']['select_type'] == Util::getDivValue('selection_type', 'AuctionSelection')){
					$flg = true;
				}
				$selection_system = $genre_data['SelectGenre']['select_type'];
			}
		}
		// 2016.12.15 murata.s ORANGE-283 ADD(E)
		// 2015/09/28 Inokuchi (E)

		$this->layout = false;
		$this->set('flg', $flg);
// 2016.11.17 murata.s ORANGE-185 ADD(S)
		$this->set('selection_system', $selection_system);
// 2016.11.17 murata.s ORANGE-185 ADD(E)
		$this->render('selection_system_list');
	}

	/**
	 * 除外時間の詳細
	 *
	 * @param string $pattern
	 */
	public function exclusion_pattern($pattern = null) {

		if(empty($pattern)) exit();

		$data = $this->ExclusionTime->findByPattern($pattern);
		if(empty($data)) exit();

		$this->layout = false;
		$this->set('data', $data);
		$this->render('exclusion_pattern');
	}


	const PARAM_NO_VALUE = "_99999999_";
	const FORMAT_DATETIME = "Y_-_-m_-_-d H-_-_i";
	const FORMAT_DATE = "Y_-_-m_-_-d";
	private $SEARCH = array('_-_-', '-_-_');
	private $REPLACE = array('/', ':');

	//ORANGE-935
	//取次状況
	const COMMISSION_STATUS_IN_PROGRESS = "1";      	// 進行中
	const COMMISSION_STATUS_RECEIPT_ORDER = "2";    	// 受注
	const COMMISSION_STATUS_COMPLETION = "3"; 			// 施工完了
	const COMMISSION_STATUS_LOST_ORDER = "4";			// 失注
	//失注理由(取次状況)
	const COMMISSION_LOST_REASON_OWN_SOLUTION = 1;  	// お客様自己解決
	const COMMISSION_LOST_REASON_NO_CONTACT = 2;    	// お客様と連絡がとれず失注
	const COMMISSION_LOST_REASON_WITHOUT_SCHEDULE = 3; 	// 目処が立たない
	const COMMISSION_LOST_REASON_MEETING_ESTIMATE = 4;  // 相見積もりにより失注
	const COMMISSION_LOST_REASON_LACK_BUDGET = 5;   	// お客様の予算が合わず失注
	const COMMISSION_LOST_REASON_DELAY = 6;				// 対応が遅れたため、失注

	// 電話対応状況
	const TEL_STATUS_NOT_CORRESPOND = 1;   					// 未対応
	const TEL_STATUS_ABSENCE = 2;          					// 不在
	const TEL_STATUS_CONSIDERATION_WITH_SERVICE = 3;  		// 検討中(サービス自体)
	const TEL_STATUS_CONSIDERATION_ADJUSTMENT = 4;    		// 検討中(日程調整)
	const TEL_STATUS_EXPECTED_FIX = 5;				  		// 確定予定
	const TEL_STATUS_FIX = 6;						  		// 確定
	const TEL_STATUS_LOST = 7;						  		// 失注
	const TEL_STATUS_OTHER = 8;						  		// その他
	// sasaki@tobila 2016.04.21 Mod Start ORANGE-1288
	const TEL_STATUS_CONSIDERATION_AFFILIATION = 9;		  	// 検討（加盟店様対応中）
	const TEL_STATUS_CONSIDERATION_SUPPORT = 10;			// 検討（営業支援対象）
	// sasaki@tobila 2016.04.21 Mod End ORANGE-1288

	// 電話対応状況失注理由
	const TEL_LOST_REASON_NOT_CONTACT = 1;					// 連絡取れない
	const TEL_LOST_REASON_OWN_SOLUTION = 2;					// お客様自己解決
	const TEL_LOST_REASON_ONLY_QUESTION_WITH_FAIR = 3;		// 質問のみ(料金)
	const TEL_LOST_REASON_ONLY_QUESTION_WITHOUT_FAIR = 4;   // 質問のみ(料金以外)
	const TEL_LOST_REASON_DELAY = 5;						// 対応遅れにより
	const TEL_LOST_REASON_NOT_ADJUSTMENT = 6;				// スケジュール調整合わず
	const TEL_LOST_REASON_NEGATIVE = 7;						// ネガティブ検討
	const TEL_LOST_REASON_OTHER = 8;						// その他
	// ORANGE-373 ADD S
	const TEL_LOST_REASON_MEETING_ESTIMATE = 9;
	// ORANGE-373 ADD E

	public function registTelSupports($id, $datetime, $status, $responder, $fail_reason, $contents, $hope_datetime) {
		try {

			//ORANGE-234 ADD S
			//トランザクション追加
			$this->CommissionInfo->begin();
			$this->CommissionTelSupport->begin();
			$this->CommissionCorrespond->begin();
			$result_flg = true;
			//ORANGE-234 ADD E

		//if (!empty($hope_datetime)) {
/*		if ($hope_datetime != self::PARAM_NO_VALUE) {
			$cdata = array('CommissionInfo' => array(
				'id' => $id,
				'visit_desired_time' => DateTime::createFromFormat('Y_-_-m_-_-d H-_-_i', $hope_datetime)->format('Y-m-d H:i')
			));
			$fields = array('visit_desired_time');

			$this->CommissionInfo->save($cdata,false,$fields);
		}
*/
		$data = array('CommissionTelSupport' => array(
			'commission_id' => $id,
			'correspond_status' => $status,
			'correspond_datetime' => DateTime::createFromFormat('Y_-_-m_-_-d H-_-_i', $datetime)->format('Y-m-d H:i'), //todo 日時式変更
			'order_fail_reason' => intval($fail_reason),
			'responders' => $responder,
			'corresponding_contens' => str_replace($this->SEARCH, $this->REPLACE, $contents)
		));

		//ORANGE-234 CHG S
		if(!$this->CommissionTelSupport->save($data)){
			$result_flg = false;
		}
		//ORANGE-234 CHG E

		// ORANGE-935
		$get_commission_status = function () use ($status) {
			if ($status == constant(__CLASS__ . "::TEL_STATUS_NOT_CORRESPOND")) return constant(__CLASS__ . "::COMMISSION_STATUS_IN_PROGRESS");
			if ($status == constant(__CLASS__ . "::TEL_STATUS_ABSENCE")) return constant(__CLASS__ . "::COMMISSION_STATUS_IN_PROGRESS");
			if ($status == constant(__CLASS__ . "::TEL_STATUS_CONSIDERATION_WITH_SERVICE")) return constant(__CLASS__ . "::COMMISSION_STATUS_IN_PROGRESS");
			if ($status == constant(__CLASS__ . "::TEL_STATUS_CONSIDERATION_ADJUSTMENT")) return constant(__CLASS__ . "::COMMISSION_STATUS_IN_PROGRESS");
			if ($status == constant(__CLASS__ . "::TEL_STATUS_EXPECTED_FIX")) return constant(__CLASS__ . "::COMMISSION_STATUS_IN_PROGRESS");
			if ($status == constant(__CLASS__ . "::TEL_STATUS_FIX")) return constant(__CLASS__ . "::COMMISSION_STATUS_IN_PROGRESS");
			if ($status == constant(__CLASS__ . "::TEL_STATUS_LOST")) return constant(__CLASS__ . "::COMMISSION_STATUS_LOST_ORDER");
			if ($status == constant(__CLASS__ . "::TEL_STATUS_OTHER")) return constant(__CLASS__ . "::COMMISSION_STATUS_IN_PROGRESS");
			// sasaki@tobila 2016.04.21 Mod Start ORANGE-1288
			if ($status == constant(__CLASS__ . "::TEL_STATUS_CONSIDERATION_AFFILIATION")) return constant(__CLASS__ . "::COMMISSION_STATUS_IN_PROGRESS");
			if ($status == constant(__CLASS__ . "::TEL_STATUS_CONSIDERATION_SUPPORT")) return constant(__CLASS__ . "::COMMISSION_STATUS_IN_PROGRESS");
			// sasaki@tobila 2016.04.21 Mod End ORANGE-1288
		};
		$get_commission_fail_reason = function () use ($fail_reason) {
			if ($fail_reason == constant(__CLASS__ . "::TEL_LOST_REASON_NOT_CONTACT")) return constant(__CLASS__ . "::COMMISSION_LOST_REASON_NO_CONTACT");
			if ($fail_reason == constant(__CLASS__ . "::TEL_LOST_REASON_OWN_SOLUTION")) return constant(__CLASS__ . "::COMMISSION_LOST_REASON_OWN_SOLUTION");
			if ($fail_reason == constant(__CLASS__ . "::TEL_LOST_REASON_ONLY_QUESTION_WITH_FAIR")) return constant(__CLASS__ . "::COMMISSION_LOST_REASON_OWN_SOLUTION");
			if ($fail_reason == constant(__CLASS__ . "::TEL_LOST_REASON_ONLY_QUESTION_WITHOUT_FAIR")) return constant(__CLASS__ . "::COMMISSION_LOST_REASON_OWN_SOLUTION");
			if ($fail_reason == constant(__CLASS__ . "::TEL_LOST_REASON_DELAY")) return constant(__CLASS__ . "::COMMISSION_LOST_REASON_DELAY");
			if ($fail_reason == constant(__CLASS__ . "::TEL_LOST_REASON_NOT_ADJUSTMENT")) return constant(__CLASS__ . "::COMMISSION_LOST_REASON_WITHOUT_SCHEDULE");
			if ($fail_reason == constant(__CLASS__ . "::TEL_LOST_REASON_NEGATIVE")) return constant(__CLASS__ . "::COMMISSION_LOST_REASON_WITHOUT_SCHEDULE");
			if ($fail_reason == constant(__CLASS__ . "::TEL_LOST_REASON_OTHER")) return constant(__CLASS__ . "::COMMISSION_LOST_REASON_WITHOUT_SCHEDULE");
			//ORANGE-373 ADD S
			if ($fail_reason == constant(__CLASS__ . "::TEL_LOST_REASON_MEETING_ESTIMATE")) return constant(__CLASS__ . "::COMMISSION_LOST_REASON_MEETING_ESTIMATE");
			//ORANGE-373 ADD E
		};

		//問題あり状態の解除
		$allow_column = array();
		$data = array('CommissionInfo' => array('id' => $id, 'tel_support' => 0));
		$allow_column[] = 'tel_support';

		// ORANGE-935 取次状況の更新
		$data['CommissionInfo']['commission_status'] = $get_commission_status();
		$allow_column[] = 'commission_status';

		if ($hope_datetime != self::PARAM_NO_VALUE) {
			$allow_column[] = 'visit_desired_time';
			$data['CommissionInfo']['visit_desired_time'] =DateTime::createFromFormat('Y_-_-m_-_-d H-_-_i', $hope_datetime)->format('Y-m-d H:i');
		}

		// ORANGE-935 失注の場合、失注理由と、失注日付の更新
		if ($data['CommissionInfo']['commission_status'] == self::COMMISSION_STATUS_LOST_ORDER) {
			$data['CommissionInfo']['commission_order_fail_reason'] = $get_commission_fail_reason();
			$allow_column[] = 'commission_order_fail_reason';
			$data['CommissionInfo']['order_fail_date'] = DateTime::createFromFormat('Y_-_-m_-_-d H-_-_i', $datetime)->format('Y/m/d');
			$allow_column[] = 'order_fail_date';
		}

		//ORANGE-234 CHG S
		if($result_flg){
			$this->__regist_correspond($id, $data);

			if(!$this->CommissionInfo->save($data, false, $allow_column)){
				$result_flg = false;
			}
		}

		if($result_flg){
			$this->CommissionInfo->commit();
			$this->CommissionTelSupport->commit();
			$this->CommissionCorrespond->commit();
		}else{
			$this->CommissionInfo->rollback();
			$this->CommissionTelSupport->rollback();
			$this->CommissionCorrespond->rollback();
			$this->log('ERORR 3ステップ 電話対応登録失敗 取次ID： '.$id,LOG_ERR);
		}
		//ORANGE-234 CHG E

		$results = $this->CommissionTelSupport->find(
									'all',
									array(
										'conditions' => array("commission_id" => $id),
										'order' => array('correspond_datetime desc')
									)
		);

		$this->layout = false;
		$this->set("lists" , $results);
		$this->render('commission_tel_support');

		} catch (Exception $e) {
			//ORANGE-234 ADD S
			$this->CommissionInfo->rollback();
			$this->CommissionTelSupport->rollback();
			$this->CommissionCorrespond->rollback();
			//ORANGE-234 ADD E
			//return $e->getMessage();
			$this->log('ERORR 3ステップ 電話対応登録失敗 取次ID： '.$id,LOG_ERR);
			$this->log($e->getMessage() ,LOG_ERR);
		}
	}

	//取次電話対応履歴の取得
	public function listTelSupports($id) {
		try {

			if(!ctype_digit($id)){
				throw new Exception('idが不正です');
			}

			$results = $this->CommissionTelSupport->find(
				'all',
				array(
					'conditions' => array("commission_id" => $id),
					'order' => array('correspond_datetime desc')
				)
			);

			$this->layout = false;
			$this->set("lists" , $results);

			if(Util::isMobile()  && $_SESSION['Auth']['User']['auth'] == 'affiliation') {
				$this->render('commission_tel_support_m');
			} else {
				$this->render('commission_tel_support');
			}

		} catch (Exception $e) {
			return h($e->getMessage());
		}
	}

	// ORANGE-935
	// 受注対応状況
	const ORDER_STATUS_NOT_CORRESPOND = 1;   				// 未対応
	const ORDER_STATUS_FIX = 2;          					// 受注対応完了
	const ORDER_STATUS_FIX_AND_MORE = 3;  					// 受注対応完了(追加受注予定)
	const ORDER_STATUS_CANCEL = 4;    						// キャンセル
	const ORDER_STATUS_OTHER = 5;						  	// その他
	// 受注対応状況失注理由
	const ORDER_LOST_REASON_OWN_SOLUTION = 1;				// お客様自己解決
	const ORDER_LOST_REASON_MEETING_ESTIMATE = 2; 			// 相見積もり
	const ORDER_LOST_REASON_DELAY = 3;						// 対応遅れにより
	const ORDER_LOST_REASON_OTHER = 4;						// その他

	public function registOrderSupports() {
		try {
			//ORANGE-234 ADD S
			//トランザクション追加
			$this->CommissionInfo->begin();
			$this->CommissionOrderSupport->begin();
			$this->CommissionCorrespond->begin();
			$result_flg = true;
			//ORANGE-234 ADD E

			$id = $this->request->query['commission_id'];
			$status = $this->request->query['correspond_status'];
			$fail_reason = intval($this->request->query['order_fail_reason']);

			$data = array('CommissionOrderSupport' => array(
					'commission_id' => $id,
					'correspond_status' => $status,
					'correspond_datetime' => $this->request->query['correspond_datetime'],
					//			'correspond_datetime' => DateTime::createFromFormat('Y_-_-m_-_-d H-_-_i', $datetime)->format('Y-m-d H:i'),
					'order_fail_reason' => $fail_reason,
					'responders' => $this->request->query['responders'],
					'corresponding_contens' => $this->request->query['corresponding_contens'],
			));

			//ORANGE-234 ADD S
			if(!$this->CommissionOrderSupport->save($data)){
				$result_flg = false;
			}
			//ORANGE-234 ADD E

			//問題あり状態の解除
			$allow_column = array();


			// ORANGE-935
			$get_commission_status = function () use ($status) {
				if ($status == constant(__CLASS__ . "::ORDER_STATUS_NOT_CORRESPOND")) return constant(__CLASS__ . "::COMMISSION_STATUS_IN_PROGRESS");
				if ($status == constant(__CLASS__ . "::ORDER_STATUS_FIX")) return constant(__CLASS__ . "::COMMISSION_STATUS_COMPLETION");
				if ($status == constant(__CLASS__ . "::ORDER_STATUS_FIX_AND_MORE")) return constant(__CLASS__ . "::COMMISSION_STATUS_COMPLETION");
				if ($status == constant(__CLASS__ . "::ORDER_STATUS_CANCEL")) return constant(__CLASS__ . "::COMMISSION_STATUS_LOST_ORDER");
				if ($status == constant(__CLASS__ . "::ORDER_STATUS_OTHER")) return constant(__CLASS__ . "::COMMISSION_STATUS_IN_PROGRESS");
			};
			$get_commission_fail_reason = function () use ($fail_reason) {
				if ($fail_reason == constant(__CLASS__ . "::ORDER_LOST_REASON_OWN_SOLUTION")) return constant(__CLASS__ . "::COMMISSION_LOST_REASON_OWN_SOLUTION");
				if ($fail_reason == constant(__CLASS__ . "::ORDER_LOST_REASON_MEETING_ESTIMATE")) return constant(__CLASS__ . "::COMMISSION_LOST_REASON_MEETING_ESTIMATE");
				if ($fail_reason == constant(__CLASS__ . "::ORDER_LOST_REASON_DELAY")) return constant(__CLASS__ . "::COMMISSION_LOST_REASON_DELAY");
				if ($fail_reason == constant(__CLASS__ . "::ORDER_LOST_REASON_OTHER")) return constant(__CLASS__ . "::COMMISSION_LOST_REASON_WITHOUT_SCHEDULE");
			};

			//it is ececuted when the value pointed 2 or 3
/*
  			if ($status == self::ORDER_STATUS_FIX || $status == self::ORDER_STATUS_FIX_AND_MORE) {
				$this->__updateCommissionInfoForOrderCompletion($this->request->query);
			}

			if ($status == self::ORDER_STATUS_CANCEL) {
				$this->__updateCommissionInfoForOrderCompletion($this->request->query);
			}
*/

			// ORANGE-935 取次状況の更新
			$data = array('CommissionInfo' => array('id' => $id, 'order_support' => 0));
			$allow_column[] = 'order_support';

			$data['CommissionInfo']['commission_status'] = $get_commission_status();
			$allow_column[] = 'commission_status';

			if ($data['CommissionInfo']['commission_status'] == self::COMMISSION_STATUS_COMPLETION){
				if (!empty($this->request->query['completion_datetime'])) {
					$data['CommissionInfo']['complete_date'] = $this->request->query['completion_datetime'];
					$allow_column[] = 'complete_date';
				}
				if (!empty($this->request->query['construction_price_tax_exclude'])) {
					$data['CommissionInfo']['construction_price_tax_exclude'] = $this->request->query['construction_price_tax_exclude'];
					$allow_column[] = 'construction_price_tax_exclude';
				}
				if (!empty($this->request->query['construction_price_tax_include'])) {
					$data['CommissionInfo']['construction_price_tax_include'] = $this->request->query['construction_price_tax_include'];
					$allow_column[] = 'construction_price_tax_include';
				}
			}

			// ORANGE-935 失注の場合、失注理由と、失注日付の更新
			if ($data['CommissionInfo']['commission_status'] == self::COMMISSION_STATUS_LOST_ORDER ) {
				$data['CommissionInfo']['commission_order_fail_reason'] = $get_commission_fail_reason();
				$allow_column[] = 'commission_order_fail_reason';
				$order_fail_date = explode(' ', $this->request->query['correspond_datetime']);
				$data['CommissionInfo']['order_fail_date'] = str_replace("-", "/", $order_fail_date[0]);
				$allow_column[] = 'order_fail_date';
			}

			//ORANGE-234 ADD S
			if($result_flg){
				//$save_data['CommissionInfo'] = $data['CommissionInfo'];
				$save_data['CommissionInfo'] = $this->request->query;
				foreach($data['CommissionInfo'] as $key => $val){
					$save_data['CommissionInfo'][$key] = $val;
				}
				$this->__regist_correspond($id, $save_data);
				if(!$this->CommissionInfo->save($data, false, $allow_column)){
					$result_flg = false;
				}
			}

			if($result_flg){
				$this->CommissionInfo->commit();
				$this->CommissionOrderSupport->commit();
				$this->CommissionCorrespond->commit();
			}else{
				$this->CommissionInfo->rollback();
				$this->CommissionOrderSupport->rollback();
				$this->CommissionCorrespond->rollback();
				$this->log('ERORR 3ステップ 訪問対応登録失敗 取次ID： '.$id,LOG_ERR);
			}
			//ORANGE-234 ADD E

			$results = $this->CommissionOrderSupport->find(
					'all',
					array(
							'conditions' => array("commission_id" => $id),
							'order' => array('correspond_datetime desc')
					)
					);

			$this->layout = false;
			$this->set("lists" , $results);
			$this->render('commission_order_support');
		} catch (Exception $e) {
			//ORANGE-234 ADD S
			$this->CommissionInfo->rollback();
			$this->CommissionOrderSupport->rollback();
			$this->CommissionCorrespond->rollback();
			//ORANGE-234 ADD E
			$this->log('ERORR 3ステップ 訪問対応登録失敗 取次ID： '.$id,LOG_ERR);
			$this->log($e->getMessage() ,LOG_ERR);
			//return $e->getMessage();
		}
	}

	//取次受注対応履歴の取得
	public function listOrderSupports($id) {
		try {

			if(!ctype_digit($id)){
				throw new Exception('idが不正です');
			}

			$results = $this->CommissionOrderSupport->find(
				'all',
				array(
					'conditions' => array("commission_id" => $id),
					'order' => array('correspond_datetime desc')
				)
			);

			$this->layout = false;
			$this->set("lists" , $results);

			if(Util::isMobile()  && $_SESSION['Auth']['User']['auth'] == 'affiliation') {
				$this->render('commission_order_support_m');
			} else {
				$this->render('commission_order_support');
			}

		} catch (Exception $e) {
			return h($e->getMessage());
		}
	}

	private function __updateCommissionInfoForOrderCompletion() {

		$info = $this->CommissionInfo->findByid($this->request->query['commission_id']);

		$commission = array('CommissionInfo' => array());

		if (!empty($this->request->query['completion_datetime'])) {
			$commission['CommissionInfo']['complete_date'] = $this->request->query['completion_datetime'];
                }
		if (!empty($this->request->query['construction_price_tax_exclude'])) {
			$commission['CommissionInfo']['construction_price_tax_exclude'] = $this->request->query['construction_price_tax_exclude'];
                }
		if (!empty($this->request->query['construction_price_tax_exclude'])) {
			$commission['CommissionInfo']['construction_price_tax_include'] = $this->request->query['construction_price_tax_exclude'];
                }

		if (count($commission['CommissionInfo']) == 0) return false;

		$commission['CommissionInfo']['id'] = $this->request->query['commission_id'];
		$commission['CommissionInfo']['commission_type'] = $info['CommissionInfo']['commission_type'];
		//$commission['CommissionInfo']['commission_status'] = $info['CommissionInfo']['commission_status'];
		$commission['CommissionInfo']['commission_status'] = '3';
		$commission['CommissionInfo']['modified_user_id'] = 'system';
		$commission['CommissionInfo']['modified'] = date('Y-m-d H:i:s');

		//$this->CommissionInfo->save($commission, false, array('construction_price_tax_include', 'construction_price_tax_exclude', 'complete_date'));
		$this->CommissionInfo->save($commission, false, array('construction_price_tax_include', 'construction_price_tax_exclude', 'complete_date', 'commission_status'));
//		$this->CommissionInfo->save($commission);

	}

	// ORANGE-935
	// 訪問対応状況
	const VISIT_STATUS_NOT_CORRESPOND = 1;   				// 未対応
	const VISIT_STATUS_ABSENCE = 2;          				// 会えず
	const VISIT_STATUS_CONSIDERATION_WITH_SERVICE = 3;  	// 検討中(サービス自体)
	const VISIT_STATUS_CONSIDERATION_ADJUSTMENT = 4;    	// 検討中(日程調整)
	const VISIT_STATUS_EXPECTED_FIX = 5;				  	// 受注予定
	const VISIT_STATUS_FIX = 6;						  		// 受注
	const VISIT_STATUS_LOST = 7;						  	// 失注
	const VISIT_STATUS_OTHER = 8;						  	// その他
	// sasaki@tobila 2016.04.21 Mod Start ORANGE-1288
	const VISIT_STATUS_CONSIDERATION_AFFILIATION = 9;	  	// 検討（加盟店様対応中）
	const VISIT_STATUS_CONSIDERATION_SUPPORT = 10;			// 検討（営業支援対象）
	// sasaki@tobila 2016.04.21 Mod End ORANGE-1288
	// 訪問対応状況失注理由
	const VISIT_LOST_REASON_OWN_SOLUTION = 1;				// お客様自己解決
	const VISIT_LOST_REASON_LACK_BUDGET = 2;				// 予算不足
	const VISIT_LOST_REASON_MEETING_ESTIMATE = 3; 			// 相見積もり
	const VISIT_LOST_REASON_DELAY = 4;						// 対応遅れにより
	const VISIT_LOST_REASON_NOT_ADJUSTMENT = 5;				// スケジュール調整合わず
	const VISIT_LOST_REASON_NEGATIVE = 6;					// ネガティブ検討
	const VISIT_LOST_REASON_OTHER = 7;						// その他

	public function registVisitSupports($id, $datetime, $status, $responder, $fail_reason, $contents, $support_datetime) {
		try {

			//ORANGE-234 ADD S
			//トランザクション追加
			$this->CommissionInfo->begin();
			$this->CommissionVisitSupport->begin();
			$this->CommissionCorrespond->begin();
			$result_flg = true;
			//ORANGE-234 ADD E

			//if (!empty($support_datetime)) {
/*			if ($support_datetime != self::PARAM_NO_VALUE) {
				$cdata = array('CommissionInfo' => array(
					'id' => $id,
					'order_respond_datetime' => DateTime::createFromFormat('Y_-_-m_-_-d H-_-_i', $support_datetime)->format('Y-m-d H:i')
				));
				$fields = array('order_respond_datetime');
				$this->CommissionInfo->save($cdata,false,$fields);
			}
*/

			$data = array('CommissionVisitSupport' => array(
				'commission_id' => $id,
				'correspond_status' => $status,
				'correspond_datetime' => DateTime::createFromFormat('Y_-_-m_-_-d H-_-_i', $datetime)->format('Y-m-d H:i'),
				'order_fail_reason' => intval($fail_reason),
				'responders' => $responder,
				'corresponding_contens' => str_replace($this->SEARCH, $this->REPLACE, $contents)
			));

			//ORANGE-234 CHG S
			if(!$this->CommissionVisitSupport->save($data)){
				$result_flg = false;
			}
			//ORANGE-234 CHG E

			// ORANGE-935
			$get_commission_status = function () use ($status) {
				if ($status == constant(__CLASS__ . "::VISIT_STATUS_NOT_CORRESPOND")) return constant(__CLASS__ . "::COMMISSION_STATUS_IN_PROGRESS");
				if ($status == constant(__CLASS__ . "::VISIT_STATUS_ABSENCE")) return constant(__CLASS__ . "::COMMISSION_STATUS_IN_PROGRESS");
				if ($status == constant(__CLASS__ . "::VISIT_STATUS_CONSIDERATION_WITH_SERVICE")) return constant(__CLASS__ . "::COMMISSION_STATUS_IN_PROGRESS");
				if ($status == constant(__CLASS__ . "::VISIT_STATUS_CONSIDERATION_ADJUSTMENT")) return constant(__CLASS__ . "::COMMISSION_STATUS_IN_PROGRESS");
				if ($status == constant(__CLASS__ . "::VISIT_STATUS_EXPECTED_FIX")) return constant(__CLASS__ . "::COMMISSION_STATUS_IN_PROGRESS");
				if ($status == constant(__CLASS__ . "::VISIT_STATUS_FIX")) return constant(__CLASS__ . "::COMMISSION_STATUS_RECEIPT_ORDER");
				if ($status == constant(__CLASS__ . "::VISIT_STATUS_LOST")) return constant(__CLASS__ . "::COMMISSION_STATUS_LOST_ORDER");
				if ($status == constant(__CLASS__ . "::VISIT_STATUS_OTHER")) return constant(__CLASS__ . "::COMMISSION_STATUS_IN_PROGRESS");
				// sasaki@tobila 2016.04.21 Mod Start ORANGE-1288
				if ($status == constant(__CLASS__ . "::VISIT_STATUS_CONSIDERATION_AFFILIATION")) return constant(__CLASS__ . "::COMMISSION_STATUS_IN_PROGRESS");
				if ($status == constant(__CLASS__ . "::VISIT_STATUS_CONSIDERATION_SUPPORT")) return constant(__CLASS__ . "::COMMISSION_STATUS_IN_PROGRESS");
				// sasaki@tobila 2016.04.21 Mod End ORANGE-1288
			};
			$get_commission_fail_reason = function () use ($fail_reason) {
				if ($fail_reason == constant(__CLASS__ . "::VISIT_LOST_REASON_OWN_SOLUTION")) return constant(__CLASS__ . "::COMMISSION_LOST_REASON_OWN_SOLUTION");
				if ($fail_reason == constant(__CLASS__ . "::VISIT_LOST_REASON_LACK_BUDGET")) return constant(__CLASS__ . "::COMMISSION_LOST_REASON_LACK_BUDGET");
				if ($fail_reason == constant(__CLASS__ . "::VISIT_LOST_REASON_MEETING_ESTIMATE")) return constant(__CLASS__ . "::COMMISSION_LOST_REASON_MEETING_ESTIMATE");
				if ($fail_reason == constant(__CLASS__ . "::VISIT_LOST_REASON_DELAY")) return constant(__CLASS__ . "::COMMISSION_LOST_REASON_DELAY");
				if ($fail_reason == constant(__CLASS__ . "::VISIT_LOST_REASON_NOT_ADJUSTMENT")) return constant(__CLASS__ . "::COMMISSION_LOST_REASON_WITHOUT_SCHEDULE");
				if ($fail_reason == constant(__CLASS__ . "::VISIT_LOST_REASON_NEGATIVE")) return constant(__CLASS__ . "::COMMISSION_LOST_REASON_WITHOUT_SCHEDULE");
				if ($fail_reason == constant(__CLASS__ . "::VISIT_LOST_REASON_OTHER")) return constant(__CLASS__ . "::COMMISSION_LOST_REASON_WITHOUT_SCHEDULE");
			};

			//問題あり状態の解除
			$allow_column = array();
			$data = array('CommissionInfo' => array('id' => $id, 'visit_support' => 0));
			$allow_column[] = 'visit_support';

			// ORANGE-935 取次状況の更新
			$data['CommissionInfo']['commission_status'] = $get_commission_status();
			$allow_column[] = 'commission_status';

			//ORANGE-234 CHG S
			if ($support_datetime != self::PARAM_NO_VALUE) {
				$allow_column[] = 'order_respond_datetime';
				$data['CommissionInfo']['order_respond_datetime'] = DateTime::createFromFormat('Y_-_-m_-_-d H-_-_i', $support_datetime)->format('Y-m-d H:i');
			}

			// ORANGE-935 失注の場合、失注理由と、失注日付の更新
			if ($data['CommissionInfo']['commission_status'] == self::COMMISSION_STATUS_LOST_ORDER) {
				$data['CommissionInfo']['commission_order_fail_reason'] = $get_commission_fail_reason();
				$allow_column[] = 'commission_order_fail_reason';
				$data['CommissionInfo']['order_fail_date'] = DateTime::createFromFormat('Y_-_-m_-_-d H-_-_i', $datetime)->format('Y/m/d');
				$allow_column[] = 'order_fail_date';
			}

			if($result_flg){
				$this->__regist_correspond($id, $data);

				if(!$this->CommissionInfo->save($data, false, $allow_column)){
					$result_flg = false;
				}
			}

			if($result_flg){
				$this->CommissionInfo->commit();
				$this->CommissionVisitSupport->commit();
				$this->CommissionCorrespond->commit();
			}else{
				$this->CommissionInfo->rollback();
				$this->CommissionVisitSupport->rollback();
				$this->CommissionCorrespond->rollback();
				$this->log('ERORR 3ステップ 受注対応登録失敗 取次ID： '.$id,LOG_ERR);
			}
			//ORANGE-234 CHG E

			$results = $this->CommissionVisitSupport->find(
				'all',
				array(
					'conditions' => array("commission_id" => $id),
					'order' => array('correspond_datetime desc')
				)
			);

			$this->layout = false;
			$this->set("lists" , $results);
			$this->render('commission_visit_support');
		} catch (Exception $e) {
			//ORANGE-234 ADD S
			$this->CommissionInfo->rollback();
			$this->CommissionVisitSupport->rollback();
			$this->CommissionCorrespond->rollback();
			//ORANGE-234 ADD E
			$this->log('ERORR 3ステップ 受注対応登録失敗 取次ID： '.$id,LOG_ERR);
			$this->log($e->getMessage() ,LOG_ERR);
			//return $e->getMessage();
		}
	}

	//取次訪問対応履歴の取得
	public function listVisitSupports($id) {
		try {

			if(!ctype_digit($id)){
				throw new Exception('idが不正です');
			}

			$results = $this->CommissionVisitSupport->find(
				'all',
				array(
					'conditions' => array("commission_id" => $id),
					'order' => array('correspond_datetime desc')
				)
			);

			$this->layout = false;
			$this->set("lists" , $results);

			if(Util::isMobile()  && $_SESSION['Auth']['User']['auth'] == 'affiliation') {
				$this->render('commission_visit_support_m');
			} else {
				$this->render('commission_visit_support');
			}

		} catch (Exception $e) {
			return h($e->getMessage());
		}
	}

	public function remandReasonOk($id, $remand_correspond_person, $remand_reason)
	{
		try {
			$data = array('CommissionInfo' => array(
				'id' => $id,
				'remand_correspond_person' => $remand_correspond_person,
				'remand_reason' => $remand_reason
			));
			$this->CommissionInfo->save($data);

			$this->__updateCommissionInfoForReasonOk(compact("id", "$remand_correspond_person", "$remand_reason"));

			$results = $this->CommissionInfo->find(
				'all',
				array(
					'conditions' => array("commission_id" => $id)
				)
			);

			$this->layout = false;
			$this->set('lists', $results);


		} catch (Exception $e) {
			return $e->getMessage();
		}
	}

	private function __updateCommissionInfoForReasonOk($p) {
		$commission = array('CommissionInfo' => array());

		if ($p['$remand_correspond_person'] != self::PARAM_NO_VALUE)
			$commission['CommissionInfo']['$remand_correspond_person'] = $p['$remand_correspond_person'];
		if ($p['$remand_reason'] != self::PARAM_NO_VALUE)
			$commission['CommissionInfo']['$remand_reason'] = $p['$remand_reason'];

		if (count($commission['CommissionInfo']) == 0) return false;

		$commission['CommissionInfo']['id'] = $p['id'];
		$commission['CommissionInfo']['modified_user_id'] = 'system';
		$commission['CommissionInfo']['modified'] = date('Y-m-d H:i:s');

		$this->CommissionInfo->save($commission);

	}

	public function getLatestStatusOfTelSupport($id) {
		$telSupports = $this->CommissionTelSupport->find('first',
			array('conditions' => 'commiddion_id = ' . $id,
				  'order' => array('correspond_datetime desc')
			)
		);

		if (!$telSupports) return "-";
		return Util::getDivText('tel_correspond_status', $telSupports['CommissionTelSupport']['correspond_status']);
	}

	public function getHolidays() {
		try {
			$handle = fopen('php://input', 'r');
			$json_in = fgets($handle);

			$request = json_decode($json_in);

			//header("Content-type:application/json;charset=utf-8");
			//echo json_encode($request);exit;

			$ret = new stdClass;
			foreach ($request->req_month as $month => $val) {
				$ym = DateTime::createFromFormat('Ymd', $month . "01");
				$holidays = $this->PublicHoliday->find('all', array(
						'conditions' => array("holiday_date > '" . $ym->format('Y-m-00') . "'",
								"holiday_date < '" . $ym->format('Y-m-99') .  "'"
						)
				));
				$ret->req_month[$month] = array();
				if (!$holidays) continue;
				foreach ($holidays as $holiday) {
					$h = explode('-', $holiday['PublicHoliday']['holiday_date']);
				    $ret->req_month[$month][] = (int)$h[2];
				}
			}

			header("Content-type:application/json;charset=utf-8");
			//header("Content-type:text/plain;charset=utf-8");
			echo json_encode($ret);

			return null;
		} catch (Exception $e) {
			echo $e->getMessage();
		}
	}

	public function getCommissionMaxLimit($id)
	{
		$default = array(
			'manual_selection_limit' => 1,
			'auction_selection_limit' => 1
		);
		$result = Hash::extract($this->MSite->find('first',
			array(
				'fields' => array(
					'manual_selection_limit',
					'auction_selection_limit'
				),
				'conditions' => array(
					$this->MSite->primaryKey => $id,
				)
			)), $this->MSite->alias);
		$this->response->type('json');
		$data = array_merge($default,$result);
		return $this->response->body(json_encode($data));
	}

	public function getCalenderView()
	{
		// POSTされたJSONデータ受け取り
		$handle = fopen('php://input', 'r');
		$json_in = fgets($handle);
		$request = json_decode($json_in);

		// 受け取ったJSONから年、月、データをセット
		$set_year = $request->year;
		$set_month = $request->month;
		$set_data = $request->data;

		// 社内祝日の取得
		$ym = DateTime::createFromFormat('Ymd', $set_year . $set_month . "01");
		$corporate_holidays = $this->PublicHoliday->find('all', array(
			'conditions' => array("holiday_date > '" . $ym->format('Y/m/00') . "'",
				"holiday_date < '" . $ym->format('Y/m/99') .  "'"
			)
		));

		// 指定月の初日タイムスタンプ
		$times['from_time'] = strtotime($set_year . "-" . $set_month . "-1 0:0:0");

		// 指定月の最終日タイムスタンプ
		$times['to_time'] = strtotime($set_year . "-" . $set_month . "-" . date("t", $times['from_time']) . " 23:59:59");

		// カレンダー取得
		$Month = new Calendar_Month_Weekdays($set_year, $set_month, 1 );
		$Month->build();
		$cal_result = '<div class="abs-cal">'."\n";
		$cal_result .= '<table class="cal_table">'."\n";
		$cal_result .= '<caption>'."\n";
		$cal_result .= '<div><span class="month_main">';
		$cal_result .= $set_year.'年'.((int)$set_month).'月';
		$cal_result .= '</span> </div></caption>'."\n";
		$cal_result .=  '<thead>'."\n";
		$cal_result .=  '<tr>'."\n";
		$cal_result .=  '<th id="name">月</th>'."\n";
		$cal_result .=  '<th>火</th>'."\n";
		$cal_result .=  '<th>水</th>'."\n";
		$cal_result .=  '<th>木</th>'."\n";
		$cal_result .=  '<th>金</th>'."\n";
		$cal_result .=  '<th class="suturday">土</th>'."\n";
		$cal_result .=  '<th class="sunday">日</th>'."\n";
		$cal_result .=  '</tr>'."\n";
		$cal_result .=  '</thead>'."\n";
		$cal_result .=  '<tbody>'."\n";

		while($Day = $Month->fetch()) {

			if($Day->isFirst()) {
				$cal_result .=  '<tr>'."\n";
			}

			// 前月/次月のあまりの日
			if($Day->isEmpty()) {
				$cal_result .= '<td class="grayout">'.sprintf("%01d", $Day->thisDay()).' </td>'."\n";
			}else{
				// 曜日取得
				$w = date('w', $Day->getTimestamp());    // 曜日（0：日曜～6：土曜）

				$cal_result .= '<td';

				// クリック可能な日にちにはクラス属性を追加
				$has_event = "";
				foreach ($set_data as $id => $arr) {
					foreach($arr as $key => $val) {
						if(!isset($val->display_date)) continue;

						$display_date = DateTime::createFromFormat('Y-m-d H:i:s', $val->display_date);
						if($set_year == $display_date->format('Y') &&
							$set_month == $display_date->format('m') &&
							$Day->thisDay() == $display_date->format('d')) {

							$cal_result .= ' data-date="'. $val->display_date . '"';
							$has_event = "hasEvents";
						}
					}
				}

				// 日曜
				if ($w == 0) {
					$cal_result .= ' class="sunday ' . $has_event . '">';
				} // 土曜
				elseif ($w == 6) {
					$cal_result .= ' class="satday ' . $has_event . '">';
				} // 平日
				else {
					$is_corporate_holiday = false;
					foreach ($corporate_holidays as $holiday) {
						$h = explode('/', $holiday['PublicHoliday']['holiday_date']);
						if($Day->thisDay() == $h[2]) {
							$is_corporate_holiday = true;
						}
					}
					if($is_corporate_holiday) {
						$cal_result .= ' class="sunday ' . $has_event . '">';
					}else{
						$cal_result .= ' class="day ' . $has_event . '">';
					}
				}
				// 日付
				$cal_result .= sprintf("%01d", $Day->thisDay());

				$cal_result .= '</td>' . "\n";

				if ($Day->isLast()) {
					$cal_result .= '</tr>' . "\n\n";
				}
			}
		}
		$cal_result .=  '</tbody>'."\n";
		$cal_result .=  '</table>'."\n";
		$cal_result .=  '</div>'."\n";

		echo $cal_result;
	}

	//ORANGE-234 ADD S
	/**
	 * 対応履歴　自動登録
	 * @param unknown $id
	 * @param unknown $data
	 */
	private function __regist_correspond($id, $data){
		$correspond = $this->CommissionInfo->get_correspond($id, $data);

		if (!empty($correspond)){
			$this->CommissionCorrespond->create();
			$cd['corresponding_contens'] = $correspond;
			$cd['responders'] = '自動登録['.$this->User["user_name"].']';
			$cd['correspond_datetime'] = date('Y-m-d H:i:s');
			$cd['rits_responders'] = null;
			$cd['commission_id'] = $id;
			$cd['created_user_id'] = 'system';
			$cd['modified_user_id'] = 'system';

			if($this->CommissionCorrespond->save($cd,
					array('validate' => false, 'callbacks' => false))){
				return true;
			}else{
				return false;
			}
		}
		return true;
	}
	//ORANGE-234 ADD E

// 2017.01.04 murata.s ORANGE-244 ADD(S)
	/**
	 * 請求情報を計算する
	 *
	 * @throws Exception
	 */
	public function calcurateBillInfo(){
		try{

			$data = $this->request->data;
			$calc_price = $this->BillPriceUtil->calc_bill_price(
					$data['CommissionInfo']['id'],
					$data['CommissionInfo']['commission_status'],
					!empty($data['CommissionInfo']['complete_date']) ? $data['CommissionInfo']['complete_date'] : null,
					isset($data['CommissionInfo']['construction_price_tax_exclude']) ? $data['CommissionInfo']['construction_price_tax_exclude'] : null);
			$result = array(
					'MTaxRate' => array('tax_rate' => $calc_price['MTaxRate']['tax_rate']),
					'CommissionInfo' => array(
							'construction_price_tax_exclude' => $calc_price['CommissionInfo']['construction_price_tax_exclude'],
							'construction_price_tax_include' => $calc_price['CommissionInfo']['construction_price_tax_include'],
							'corp_fee' => $calc_price['CommissionInfo']['corp_fee'],
							'deduction_tax_exclude' => $calc_price['CommissionInfo']['deduction_tax_exclude'],
							'deduction_tax_include' => $calc_price['CommissionInfo']['deduction_tax_include'],
							'confirmd_fee_rate' => $calc_price['CommissionInfo']['confirmd_fee_rate'],
					),
					'BillInfo' => array(
							'fee_target_price' => $calc_price['BillInfo']['fee_target_price'],
							'fee_tax_exclude' => $calc_price['BillInfo']['fee_tax_exclude'],
							'tax' => $calc_price['BillInfo']['tax'],
							'insurance_price' => $calc_price['BillInfo']['insurance_price'],
							'total_bill_price' => $calc_price['BillInfo']['total_bill_price']
					),
			);
			return json_encode($result);

		}catch(Exception $e){
			throw $e;
		}
	}
// 2017.01.04 murata.s ORANGE-244 ADD(E)

	//ORANGE-248 ADD S
	/**
	 * 案件閲覧の登録
	 * @param unknown $demand_id
	 */
	public function write_browse($demand_id = null){
		//$this->log('polling ajax write');
		try{
			//ユーザがいない場合リターン
			if(empty($this->User))return;
			//キャッシュデータの読み込み
			$data = Cache::read($demand_id, 'browse');
			if($data){
				$user_flg = false;
				//キャッシュ内のアイテムの確認
				foreach($data as &$item){
					//同一ユーザからのデータが存在すれば上書き
					if($item['user_id'] == $this->User['user_id']){
						$item['last_date'] = time();
						$user_flg = true;
					}
				}

				if(!$user_flg)$data[] = array('user_id' => $this->User['user_id'], 'last_date' => time());

			//同一ユーザデータ無し
			}else{
				//データの新規作成
				$data = array();
				$data[] = array('user_id' => $this->User['user_id'], 'last_date' => time());
			}
			//キャッシュデータの登録

			//Cache::write($d, '2', 'browse');
			Cache::write($demand_id, $data, 'browse');

		}catch(Exception $e){
			$this->log($e->getMessage());
		}
		return true;
	}

	/**
	 * 案件閲覧数の取得
	 * @param array $demand_ids
	 * @return number[][]|unknown[][]
	 */
	public function count_browse(){
		$this->layout = 'ajax';
		$rtn_list = array();
		if($this->request->is('post')){
			//$this->log('polling ajax count');
			try{
				//配列がからの場合、空の配列を返す
				if(!$this->request->data){
					return ;
				}

				$demand_ids = $this->request->data;

				//IDリストのループ
				foreach($demand_ids as $demand_id){
					//キャッシュデータの読み込み
					$data = Cache::read($demand_id, 'browse');
					$count = 0;

					//if($demand_id == '475969')
						//$this->log($data);
					//キャッシュデータの確認
					if($data){
						//キャッシュ内のアイテムの確認
						foreach($data as $item){
							if($item['last_date'] >= time()- BROWSE_COUNT_THRESHOLD)$count++;
						}
					}
					//最終閲覧時間がしきい値内のカウントを返す
					$rtn_list[] = array('demand_id' => $demand_id, 'count' => $count);
				}
			}catch(Exception $e){
				$this->log($e->getMessage());
			}
		}
		//配列を返す
		//$this->log($rtn_list);
		echo json_encode($rtn_list);
	}
	//ORANGE-248 ADD E
	//ORANGE-420 m-kawamoto ADD S

	/**
	 * 自動選定の有無を返します
	 * @return number
	 */
	public function exists_auto_commission_corps($site_id = null, $genre_id = null, $category_id = null, $prefecture_code = null){
		if($site_id ===null || $genre_id === null || $category_id === null || $prefecture_code == null){
			echo json_encode(0);
			return;
		}

		// 2017.10.10 ORANGE-420 m-kawamoto ADD(S)
		if(!ctype_digit($site_id) || !ctype_digit($genre_id) || !ctype_digit($category_id) || !ctype_digit($prefecture_code)){
			echo json_encode(0);
			return;
		}
		// 2017.10.10 ORANGE-420 m-kawamoto ADD(E)

		App::uses('AgreementHelper', 'View/Helper');
		$agreement_helper = new AgreementHelper(new View());

		App::uses('CreditHelper', 'View/Helper');
		$credit_helper = new CreditHelper(new View());

		$this->loadModel('MCorpNewYear');
		$this->loadModel('AutoCommissionCorp');

		$commissionComponent = $this->Components->load('Commission');
		$commissionComponent->initialize($this);

		$result = 0;
		try{

		    //選定対象の取得
		    $jis_cd = $this->MPost->getTargetJisCd($prefecture_code);
			// 2017.10.10 ORANGE-420 m-kawamoto ADD(S)
		    if($jis_cd === "")
		    {
				echo json_encode(0);
				return;
		    }
			// 2017.10.10 ORANGE-420 m-kawamoto ADD(E)
		    
		    $commission_conditions = array(
		    	'category_id' => $category_id,
		    	'jis_cd' => $jis_cd,
		    );
			// 2017.10.10 ORANGE-420 m-kawamoto ADD(S)
		    $commission_corps = $commissionComponent->GetCorpList($commission_conditions, '', false);
		    $new_commission_corps = $commissionComponent->GetCorpList($commission_conditions, 'new', false);
		    // 2017.10.10 ORANGE-420 m-kawamoto ADD(E)

		    //案件の都道府県、カテゴリーを取得
		    //対象加盟店を取得
		    $auto_commission_corp = $this->AutoCommissionCorp->getAutoCommissionCorpByPrefCategory(
		        $prefecture_code,
		        $category_id,
		        null
		    );
		    $auto_commission_corp_ids = Hash::extract($auto_commission_corp, "");

		    foreach ($auto_commission_corp_ids as $key => $value) {
				$target_commission_corp = null;
				//カテゴリｘ都道府県に登録されている企業IDに一致する選定情報を探す
				foreach ($new_commission_corps as $key => $new_commission_corp) {
					if($new_commission_corp['MCorp']['id'] == $value['AutoCommissionCorp']['corp_id'])
					{
						$target_commission_corp = $new_commission_corp;
						break;
					}
				}
				if($target_commission_corp === null)
				{
					foreach ($commission_corps as $key => $commission_corp) {
						if($commission_corp['MCorp']['id'] == $value['AutoCommissionCorp']['corp_id'])
						{
							$target_commission_corp = $commission_corp;
							break;
						}
					}
				}
				if($target_commission_corp === null)
				{
					continue;
				}
				
				// 2017/10/19 ORANGE-541 m-kawamoto DEL(S) ライセンスチェック排除
				//ライセンスチェック
				//$license_check = true;
				//if($site_id != CREDIT_EXCLUSION_SITE_ID)
				//{
				//	$license_check = $agreement_helper->checkLicense($target_commission_corp['MCorp']['id'], $category_id);
				//}
				//if(!$license_check)
				//{
				//	continue;
				//}
				// 2017/10/19 ORANGE-541 m-kawamoto DEL(E)

				//与信チェック
				$result_credit = CREDIT_NORMAL;
				if(!in_array($site_id, Configure::read('credit_check_exclusion_site_id'))){
					$result_credit = $credit_helper->checkCredit($target_commission_corp['MCorp']['id'], $genre_id, false, true);
				}
				if($result_credit == CREDIT_DANGER){
					continue;
				}
				// 2017/12/07 h.miyake ORANGE-602 ADD(S)
				// クロスセルサイト判定(cross_site_flg)が1の場合除外
				$resultCrossSiteFlg = $this->MSite->getCrossSiteFlg(1);
				if(in_array($site_id, $resultCrossSiteFlg)) {
					continue;
				}
				// 「その他」「その他（紹介ベース）」の場合除外
				if(in_array($site_id, (Configure::read('arrSiteId')))) {
					continue;
				}
				// 2017/12/07 h.miyake ORANGE-602 ADD(E)
				
				//ORANGE-509 m-kawamoto CHG S
				$result = $value['AutoCommissionCorp']['process_type'];
				//ORANGE-509 m-kawamoto CHG E
				break;
			}

		}catch(Exception $e){
			$this->log($e->getMessage());
		}

		echo json_encode($result);
	}
	//ORANGE-420 m-kawamoto ADD E
}
