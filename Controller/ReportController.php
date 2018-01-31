<?php
App::uses('AppController', 'Controller');

class ReportController extends AppController {

	public $name = 'Report';
	public $helpers = array('Csv', 'Html');
	public $components = array('Session', 'Csv',
		'Paginator'=> array(
			'className' => 'ExtendPaginator'
		)
		//ORANGE-393 ADD S
		,'Search.Prg'
		//ORANGE-3939 ADD E

	);

	// ORANGE-128 iwai CHG S
    // ORANGE-444 CHG S
	public $uses = array(
		//ORANGE-393 ADD S
		'CorpCategoryGroupApplication',
		//ORANGE-393 ADD E
            'DemandInfo', 'CommissionInfo', 'AdditionInfo', 'MCorpCategory',
            'MCorp', 'MGenre', 'MItem', 'Approval', 'MCorpCategoriesTemp', 'CommissionSearchItems', 'Report', 'CorpCategoryApplication',
            'GeneralSearch'
	);
    // ORANGE-444 CHG E
	// ORANGE-128 iwai CHG E

	//ORANGE-393 ADD S
	public $presetVars = array(
			'corp_id' => array('type' => 'value', 'empty' => false, 'encode' => false), //企業ID
			'corp_name' => array('type' => 'value', 'empty' => false, 'encode' => true), //企業名
			'group_id' => array('type' => 'value', 'empty' => false, 'encode' => false), //グループID
			'application_date_from' => array('type' => 'value', 'empty' => false, 'encode' => false), //申請日FROM
			'application_date_to' => array('type' => 'value', 'empty' => false, 'encode' => false), //申請日TO
	);
	//ORANGE-393 ADD E
        
        //ORANGE-444 ADD S
        private $_err_message = "";
	private $_comp_message = "";
        private $_commission_search_name = '';
        //ORANGE-444 ADD E

	public function beforeFilter(){

		// 性能対策のため、アソシエーションを解除
		$this->DemandInfo->unbindModel(
				array(
						'hasMany' => array('CommissionInfo', 'IntroduceInfo', 'DemandCorrespondHistory'),
						'hasAndBelongsToMany' => array('MInquiry')
				));
//		// 2015.4.29 n.kai ADD start *JBR領収書後追いレポートのSQLでDemandInfoが多重になるため対象外にする
//		$this->CommissionInfo->unbindModel(
//				array(
//						'belongsTo' => array('DemandInfo')
//				),
//				false
//		);
//		// 2015.4.29 n.kai ADD end

		parent::beforeFilter();
	}


	public function index() {
	}

	/**
	 * 業者取次一覧
	 */
//2017.11.27 mochizuki.m ORANGE-495-mod1 ADD(S) 
//  注意：クエリーを変更する時はリアルタイムレコード側も合わせてください。
//          ReportController::real_time_report()
//          DemandInfo::getRealTimeReport_HearLossNum1()
//2017.11.27 mochizuki.m ORANGE-495-mod1 ADD(E) 
    // ORANGE-444 CHG S
	public function corp_commission($id='') {
		$data = array();
                $search_id = $id;

		// セッションに検索条件を保存
		$data['url'] = '/report/corp_commission';
		$data['named'] = $this->params['named'];
		$this->Session->delete(self::$__sessionKeyForReport);
		$this->Session->write(self::$__sessionKeyForReport, $data);

        if (isset( $this->request->data['regist'] )) {
            $flag = false;
            $filters = $this->request->data('filter');
            // フィルターの条件が1つでも入力されたか確認
            foreach ($filters as $key => $value) {
                if (empty($value)) {
                    //値が0も存在するため通す
                    if($value === "0"){
                        $flag = true;
                    }
                    continue;
                }
                $flag = true;
            }
            if($flag){
                $id =$this->__registCommissionSearch($this->request->data);
                $this->_comp_message = "保存が完了しました。";
                $search_id = $id;
            }else{
                $this->_err_message = "フィルター条件を1つ以上選択してください。";
            }
            
		}

		if (isset( $this->request->data['delete'] )) {
                    if ($this->__deleteCommissionSearch($this->data['CommissionSearch']['commission_search_id']) === true) {
                        $this->_comp_message = "削除が完了しました。";
                        $search_id = '';
                    }
		}
                
        //ORANGE-444 CHG E
		//ORANGE_215 CHG S
		//ORANGE-300 CHG S
		//ORANGE-345 CHG S
		$sortOptions = array(
			'demand_follow_date' => '後追い日',
			//'lock_user_id' => '取次者',
			'detect_contact_desired_time' => '連絡希望日時',
			'visit_time' => '訪問日時',// virtual
			'corp_name' => '取次先１',// virtual
			'commission_rank' => 'ジャンルランク',
			'site_id' => 'サイト名',
			'customer_name' => 'お客様名',
			// 2017.06.27 takeuchi.e ORANGE-461 ADD(S) 【レポート機能】業者取次用一覧レポートに案件の都道府県を表示
			'DemandInfo.address1' => '都道府県',
			// 2017.06.27 takeuchi.e ORANGE-461 ADD(E) 【レポート機能】業者取次用一覧レポートに案件の都道府県を表示
			'contactable' => '連絡可能時間',
			'holiday' => '休業日',
			//'nighttime_takeover' => '夜間案件',
			'first_commission' => '初取次チェック',
			'user_name' => '最終履歴更新者',// virtual
			'modified2' => '履歴更新時間',// virtual
			'auction' => '入札落ち',
			//ORANGE-369 ADD S
			'cross_sell_implement' => 'クロスセル獲得',
			//ORANGE-369 ADD E
		);
		//ORANGE-345 CHG E
		//ORANGE-300 CHG E
		//ORANGE_215 CHG E

		$whiteList = array();
		$defaultOrder = array(
			'order' => array(
				1 => 'corp_name',
				2 => 'contact_desired_time',
				3 => 'commission_rank',
			),
			'direction' => array(
				1 => 'asc',
				2 => 'asc',
				3 => 'asc',
			)
		);
                
                //ORANGE-444 ADD S
                
                $filterOptions = array(
                            1 => 'なし',
                            2 => 'あり',
                );
                $contactRequest = array(
                            1 => '当日',
                            2 => '明日',
                            3 => 'それ以降',
                );
                $genreRank = array(
                            1 => '1',
                            2 => '2',
                            3 => '3',
                            4 => '4',
                            5 => '5',
                            6 => '6',
                );
                $dayOfTheWeek = array(
                            '2' => '月曜日',
                            '3' => '火曜日',
                            '4' => '水曜日',
                            '5' => '木曜日',
                            '6' => '金曜日',
                            '7' => '土曜日',
                            '8' => '日曜日',
                );
                $historyUpdate = array(
                            1 => '1時間以内',
                            2 => '1時間以降',
                );
                $buildFilter = array(
                            'filter_name' => array(
                                1 => 'DemandInfo.follow_date',
                                2 => 'DemandInfo.contact_desired_time',
                                3 => 'MGenre.commission_rank',
                                4 => 'MSite.site_name',
                                5 => 'MCorp.corp_name',
                                6 => 'DemandInfo.holiday',
                                7 => 'CommissionInfo.first_commission',
                                8 => 'DemandInfo.user_name',
                                9 => 'CommissionInfo.modified',
                                10 => 'DemandInfo.auction',
                                11 => 'DemandInfo.cross_sell_implement',
                            )
                );
                $defaultFilter = array();
                
                if (!empty($id) && !empty($search_id)) {
                    $defaultFilter = $this->__searchCommissionSearch($id);
                    if($this->request->data('CommissionSearchItems.commission_search_name')){
                        $this->_commission_search_name = $this->request->data('CommissionSearchItems.commission_search_name');
                    }
		}
                
                //  2017.10.04 ORANGE-542 h.miyake ADD(S)  
                // 業者取次用一覧タイトルをクリックした場合、フィルタ条件の保持
                // 後追い日
                if($this->request->query('demand_follow_date')) {
                        $defaultFilter = array_merge($defaultFilter, array(
                                'filter1' => $this->request->query('demand_follow_date'),
                        ));
                }
                
                // 連絡希望日時
                if($this->request->query('detect_contact_desired_time')) {
                        $defaultFilter = array_merge($defaultFilter,array(
                                'filter2' => $this->request->query('detect_contact_desired_time'),
                        ));
                }
                
                // ジャンルランク
                if($this->request->query('commission_rank')) {
                        $defaultFilter = array_merge($defaultFilter,array(
                                'filter3' => $this->request->query('commission_rank'),
                        ));
                }
                
                // サイト名
                if($this->request->query('site_id')) {
                        $defaultFilter = array_merge($defaultFilter,array(
                                'filter4' => $this->request->query('site_id'),
                        ));
                }
                
                // 取次先１
                if($this->request->query('corp_name')) {
                        $defaultFilter = array_merge($defaultFilter,array(
                                'filter5' => $this->request->query('corp_name'),
                        ));
                }
                
                // 営業日
                if($this->request->query('business_day')) {
                        $defaultFilter = array_merge($defaultFilter,array(
                                'filter6' => $this->request->query('business_day'),
                        ));
                }
                
                // 初取次チェック
                if($this->request->query('first_commission')) {
                        $defaultFilter = array_merge($defaultFilter,array(
                                'filter7' => $this->request->query('first_commission'),
                        ));
                }
                
                // 最終履歴更新者
                if($this->request->query('user_name')) {
                        $defaultFilter = array_merge($defaultFilter,array(
                                'filter8' => $this->request->query('user_name'),
                        ));
                }
                
                // 履歴更新時間
                if($this->request->query('modified2')) {
                        $defaultFilter = array_merge($defaultFilter,array(
                                'filter9' => $this->request->query('modified2'),
                        ));
                }
                
                // 入札落ち
                if($this->request->query('auction')) {
                        $defaultFilter = array_merge($defaultFilter,array(
                                'filter10' => $this->request->query('auction'),
                        ));
                }
                
                // クロスセル獲得                
                if($this->request->query('cross_sell_implemen')) {
                        $defaultFilter = array_merge($defaultFilter,array(
                                'filter11' => $this->request->query('cross_sell_implemen'),
                        ));
                }

                //  2017.10.03 ORANGE-542 h.miyake ADD(E)

                // 2017/10/12 ORANGE-542 m-kawamoto ADD(S) 相互にソートと検索の条件をマージ
                if(isset($this->request->query['filter'][3]) && !empty($this->request->query['filter'][3]))
                {
                     $this->request->query['filter'][3] = explode(" ", $this->request->query['filter'][3]);
                }
                if(isset($this->request->query['filter'][6]) && !empty($this->request->query['filter'][6]))
                {
                     $this->request->query['filter'][6] = explode(" ", $this->request->query['filter'][6]);
                }
                
                $this->request->data = array_merge($this->request->data, $this->request->query);
                $this->request->query = array_merge($this->request->query, $this->request->data);
                // 2017/10/12 ORANGE-542 m-kawamoto ADD(E) 

                if($this->request->data('filter')){
                    $filter = $this->request->data('filter');
                    // オーダーの作成
                    foreach ($filter as $key => $value) {
                        if (empty($value)) {
                            continue;
                        }
                        $buildFilter['value'][$key] = $value;
                    }
                }
                //  2017.10.04 ORANGE-542 h.miyake ADD(S)
                // 後追い日
                if($this->request->query('demand_follow_date')) {
                        $buildFilter['value'][1] = $this->request->query('demand_follow_date');
                }
                
                // 連絡希望日時
                if($this->request->query('detect_contact_desired_time')) {
                        $buildFilter['value'][2] = $this->request->query('detect_contact_desired_time');
                }

                // ジャンルランク
                if($this->request->query('commission_rank')) {
                        $buildFilter['value'][3] = $this->request->query('commission_rank');
                }
                
                // サイト名
                if($this->request->query('site_id')) {
                        $buildFilter['value'][4] = $this->request->query('site_id');
                }
                
                // 取次先１
                if($this->request->query('corp_name')) {
                        $buildFilter['value'][5] = $this->request->query('corp_name');
                }
                
                // 営業日
                if($this->request->query('business_day')) {
                        $buildFilter['value'][6] = $this->request->query('business_day');
                }
                
                // 初取次チェック
                if($this->request->query('first_commission')) {
                        $buildFilter['value'][7] = $this->request->query('first_commission');
                }
 
                // 最終履歴更新者
                if($this->request->query('user_name')) {
                        $buildFilter['value'][8] = $this->request->query('user_name');
                }
                
                // 履歴更新時間
                if($this->request->query('modified2')) {
                        $buildFilter['value'][9] = $this->request->query('modified2');
                }
                
                // 入札落ち
                if($this->request->query('auction')) {
                        $buildFilter['value'][10] = $this->request->query('auction');
                }
                
                // クロスセル獲得
                if($this->request->query('cross_sell_implemen')) {
                        $buildFilter['value'][11] = $this->request->query('cross_sell_implemen');
                }    
                //  2017.10.04 ORANGE-542 h.miyake ADD(E)
                //ORANGE-444 ADD E
		$query = $this->DemandInfo->getCorpCommissionPaginationCondition($buildFilter);
		// 入力値の抽出
		if (!$this->request->query('sort') && $this->request->query('order')) {
			$whiteList = array_keys($sortOptions);
			$order = $this->request->query('order');
			$direction = $this->request->query('direction');
			// デフォルト値の設定
			if (empty($order)) {
				$order = $defaultOrder['order'];
				$direction = $defaultOrder['direction'];
				$this->request->query = $defaultOrder;
			}
			// オーダーの作成
			$buildOrder = array();
			foreach ($order as $key => $value) {
				if (empty($value) || !isset($direction[$key])) {
					continue;
				}
				if (in_array($direction[$key], array('asc', 'desc'))) {
					$buildOrder[$order[$key]] = $direction[$key];
				}
			}
			if (!empty($buildOrder)) {
				$query['order'] = $buildOrder;
			}
		}else{
			if($this->request->query('sort') =='DemandInfo.auction'){
				if($this->request->query('direction') == 'desc'){
				}
			}
		}

		$this->Paginator->settings = Hash::merge($query, array(
				'paramType' => 'querystring'
		));
		$this->Paginator->appendOrder = array(
			'DemandInfo.auction' => array(
				'asc' => 'NULLS FIRST',
				'desc' => 'NULLS LAST',
			)
		);
		$results = $this->Paginator->paginate('DemandInfo', array(), $whiteList);
                
                //ORANGE-444 ADD S
                $this->set('err_message', $this->_err_message);
		$this->set('comp_message', $this->_comp_message);
                $this->set('commission_search_name', $this->_commission_search_name);
		$this->set(compact('sortOptions', 'results', 'defaultOrder', 'filterOptions', 'genreRank',
                        'contactRequest', 'dayOfTheWeek', 'historyUpdate', 'defaultFilter', 'search_id'));
                //ORANGE-444 ADD E

                //  2017.10.03 ORANGE-542 h.miyake ADD(S)
                $strGetParam['demand_follow_date'] = '';
                $strGetParam['detect_contact_desired_time'] = '';
                $strGetParam['commission_rank'] = '';
                $strGetParam['site_id'] = '';
                $strGetParam['corp_name'] = '';
                $strGetParam['business_day'] = '';
                $strGetParam['first_commission'] = '';
                $strGetParam['user_name'] = '';
                $strGetParam['modified2'] = '';
                $strGetParam['auction'] = '';
                $strGetParam['cross_sell_implemen'] = '';
                
                if (!empty($buildFilter['value'])) {
                        foreach($buildFilter['value'] as $key => $value) {
                                //$strGetParam = "," . $key . " => '"  . $value . "'";
                                if($key == '1') {
                                        $strGetParam['demand_follow_date'] = $value;
                                }
                                if($key == '2') {
                                        $strGetParam['detect_contact_desired_time'] = $value;
                                }
                                if($key == '3') {
                                        $strGetParam['commission_rank'] = $value;
                                }
                                if($key == '4') {
                                        $strGetParam['site_id'] = $value;
                                }
                                if($key == '5') {
                                        $strGetParam['corp_name'] = $value;
                                }
                                if($key == '6') {
                                        $strGetParam['business_day'] = $value;
                                }
                                if($key == '7') {
                                        $strGetParam['first_commission'] = $value;
                                }
                                if($key == '8') {
                                        $strGetParam['user_name'] = $value;
                                }
                                if($key == '9') {
                                        $strGetParam['modified2'] = $value;
                                }
                                if($key == '10') {
                                        $strGetParam['auction'] = $value;
                                }
                                if($key == '11') {
                                        $strGetParam['cross_sell_implemen'] = $value;
                                }
                        }
                }
                $this->set('strGetParam', $strGetParam);
                //  2017.10.03 ORANGE-542 h.miyake ADD(E)
	}


	/**
	 * 業者選定一覧
	 */
	public function corp_selection() {
		// セッションに検索条件を保存
		$data['url'] = '/report/corp_selection';
		$data['named'] = $this->params['named'];
		$this->Session->delete(self::$__sessionKeyForReport);
		$this->Session->write(self::$__sessionKeyForReport, $data);

		$query = $this->DemandInfo->getCorpSelectionPaginationCondition();
		$this->Paginator->settings = Hash::merge($query, array(
				'paramType' => 'querystring'
		));

		$this->DemandInfo->setVirtualDetectContactDesiredTime();
		$this->Paginator->appendOrder = array(
			'DemandInfo.auction' => array(
				'asc' => 'NULLS FIRST',
				'desc' => 'NULLS LAST',
			)
		);
		$results = $this->Paginator->paginate('DemandInfo');

		$this->set('results', Sanitize::clean($results));
	}

//  2015.09.21 h.hanaki ADD(S) ORANGE_AUCTION-13  作ったが使わない
	/**
	 * 業者選定一覧（オークション落ち案件）
	 */
	public function auction_fall() {

		// セッションに検索条件を保存
		$data['url'] = '/report/auction_fall';
		$data['named'] = $this->params['named'];
		$this->Session->delete(self::$__sessionKeyForReport);
		$this->Session->write(self::$__sessionKeyForReport, $data);

		$joins = array();
		$joins = array(
				array(
						'table' => 'm_sites'
						,'alias' => "MSite"
						,'type' => 'left'
						,'conditions' => array('DemandInfo.site_id = MSite.id')
				),
				array(
						'table' => 'm_categories'
						,'alias' => "MCategory"
						,'type' => 'left'
						,'conditions' => array('DemandInfo.category_id = MCategory.id')
				),
		);


		$conditions = array();

		// 生活救急車以外
		$conditions['MSite.jbr_flg'] = 0;

		// 「【未選定】」
		$conditions['DemandInfo.demand_status'] = Util::getDivValue('demand_status', 'no_selection');

		// 「オークション落ち」
		$conditions['DemandInfo.auction'] = 1;

		$this->paginate = array(
				'fields' => 'DemandInfo.*, MSite.site_name, MCategory.category_name'
				,'conditions' => $conditions
				,'joins' => $joins
				,'limit' => Configure::read('report_list_limit')
				,'order' => 'DemandInfo.contact_desired_time asc'
		);

		$results = $this->paginate('DemandInfo');

		$this->set('results', Sanitize::clean($results));
	}
//  2015.09.21 h.hanaki ADD(E) ORANGE_AUCTION-13

	/**
	 * メール・FAX未送信一覧
	 */
	public function unsent_list() {

		// セッションに検索条件を保存
		$data['url'] = '/report/unsent_list';
		$data['named'] = $this->params['named'];
		$this->Session->delete(self::$__sessionKeyForReport);
		$this->Session->write(self::$__sessionKeyForReport, $data);

		$joins = array();
		$joins = array(
				array(
						'table' => 'm_sites'
						,'alias' => "MSite"
						,'type' => 'inner'
						,'conditions' => array('DemandInfo.site_id = MSite.id')
				),
				array(
						'table' => '(select * from commission_infos where commit_flg = 1)'
						,'alias' => "CommissionInfo"
						,'type' => 'inner'
						,'conditions' => array('DemandInfo.id = CommissionInfo.demand_id')
				),
				array(
						'table' => 'm_corps'
						,'alias' => "MCorp"
						,'type' => 'inner'
						,'conditions' => array('CommissionInfo.corp_id = MCorp.id')
				),
		);


		$conditions = array();

		// 生活救急車以外
		$conditions['MSite.jbr_flg'] = 0;

		// 「【進行中】電話取次済」
		$conditions['DemandInfo.demand_status'] = Util::getDivValue('demand_status', 'telephone_already');

		$this->paginate = array(
				'fields' => 'DemandInfo.*, MCorp.id, MCorp.corp_name, MSite.site_name'
				,'conditions' => $conditions
				,'joins' => $joins
				,'limit' => Configure::read('report_list_limit')
				,'order' => 'DemandInfo.contact_desired_time asc'
		);
		$this->DemandInfo->virtualFields = array(
				'corp_name' => 'MCorp.corp_name',
		);
		// ORANGE-1027 ohta 連絡期限日時複数選択対応
		$this->DemandInfo->setVirtualDetectContactDesiredTime();

		$results = $this->paginate('DemandInfo');

		$this->set('results', $results);
	}

	/**
	 * (JBR)取次前一覧
	 */
//2017.11.27 mochizuki.m ORANGE-495-mod1 ADD(S) 
//  注意：クエリーを変更する時はリアルタイムレコード側も合わせてください。
//          ReportController::real_time_report()
//          DemandInfo::getRealTimeReport_HearLossNum2()
//2017.11.27 mochizuki.m ORANGE-495-mod1 ADD(E) 
//ORANGE-309 CHG S corp_commissionに仕様を合わせる
	public function jbr_commission() {
		$data = array();

		// セッションに検索条件を保存
		$data['url'] = '/report/corp_commission';
		$data['named'] = $this->params['named'];
		$this->Session->delete(self::$__sessionKeyForReport);
		$this->Session->write(self::$__sessionKeyForReport, $data);

		//ORANGE_215 CHG S
		//ORANGE-300 CHG S
		$sortOptions = array(
				'demand_follow_date' => '後追い日',
				//'lock_user_id' => '取次者',
				'detect_contact_desired_time' => '連絡希望日時',
				'visit_time' => '訪問日時',// virtual
				'corp_name' => '取次先１',// virtual
				'commission_rank' => 'ジャンルランク',
				'site_id' => 'サイト名',
				'customer_name' => 'お客様名',
				// 2017.06.27 takeuchi.e ORANGE-461 ADD(S) 【レポート機能】業者取次用一覧レポートに案件の都道府県を表示
				'DemandInfo.address1' => '都道府県',
				// 2017.06.27 takeuchi.e ORANGE-461 ADD(E) 【レポート機能】業者取次用一覧レポートに案件の都道府県を表示
				'contactable' => '連絡可能時間',
				'holiday' => '休業日',
				//'nighttime_takeover' => '夜間案件',
				'first_commission' => '初取次チェック',
				'user_name' => '最終履歴更新者',// virtual
				'modified2' => '履歴更新時間',// virtual
				'auction' => '入札落ち',
		);
		//ORANGE-300 CHG E
		//ORANGE_215 CHG E

		$whiteList = array();
		$defaultOrder = array(
				'order' => array(
						1 => 'corp_name',
						2 => 'contact_desired_time',
						3 => 'commission_rank',
				),
				'direction' => array(
						1 => 'asc',
						2 => 'asc',
						3 => 'asc',
				)
		);

		$query = $this->DemandInfo->getJBRCommissionPaginationCondition();
		// 入力値の抽出
		if (!$this->request->query('sort') && $this->request->query('order')) {
			$whiteList = array_keys($sortOptions);
			$order = $this->request->query('order');
			$direction = $this->request->query('direction');
			// デフォルト値の設定
			if (empty($order)) {
				$order = $defaultOrder['order'];
				$direction = $defaultOrder['direction'];
				$this->request->query = $defaultOrder;
			}
			// オーダーの作成
			$buildOrder = array();
			foreach ($order as $key => $value) {
				if (empty($value) || !isset($direction[$key])) {
					continue;
				}
				if (in_array($direction[$key], array('asc', 'desc'))) {
					$buildOrder[$order[$key]] = $direction[$key];
				}
			}
			if (!empty($buildOrder)) {
				$query['order'] = $buildOrder;
			}
		}else{
			if($this->request->query('sort') =='DemandInfo.auction'){
				if($this->request->query('direction') == 'desc'){
				}
			}
		}

		$this->Paginator->settings = Hash::merge($query, array(
				'paramType' => 'querystring'
		));
		$this->Paginator->appendOrder = array(
				'DemandInfo.auction' => array(
						'asc' => 'NULLS FIRST',
						'desc' => 'NULLS LAST',
				)
		);
		$results = $this->Paginator->paginate('DemandInfo', array(), $whiteList);
		$this->set(compact('sortOptions', 'results', 'defaultOrder'));
	}
/*
	public function jbr_commission() {

		// セッションに検索条件を保存
		$data['url'] = '/report/jbr_commission';
		$data['named'] = $this->params['named'];
		$this->Session->delete(self::$__sessionKeyForReport);
		$this->Session->write(self::$__sessionKeyForReport, $data);

		$joins = array();
		$joins = array(
				array(
						'table' => 'm_sites'
						,'alias' => "MSite"
						,'type' => 'inner'
						,'conditions' => array('DemandInfo.site_id = MSite.id')
				),
				array(
						'table' => '(select * from commission_infos inner join (select min(id) as id from commission_infos where lost_flg = 0 group by demand_id) m on commission_infos.id = m.id)'
						//'table' => '(select * from commission_infos left outer join (select min(id) as id from commission_infos where lost_flg = 0 group by demand_id) m on commission_infos.id = m.id)'
						,'alias' => "CommissionInfo"
						,'type' => 'left'
						,'conditions' => array('DemandInfo.id = CommissionInfo.demand_id')
				),
				array(
						'table' => 'm_corps'
						,'alias' => "MCorp"
						//,'type' => 'inner'
						,'type' => 'left'
						,'conditions' => array('CommissionInfo.corp_id = MCorp.id')
				),
				array(
						'table' => "(select * from m_items where item_category = '[JBR様]作業内容')"
						,'alias' => "MItem"
						,'type' => 'inner'
						,'conditions' => array('DemandInfo.jbr_work_contents = cast(MItem.item_id as varchar)')
				),
		);


		$conditions = array();

		// 生活救急車
		$conditions['MSite.jbr_flg'] = 1;

		// 「未選定」、「お客様不在」、「【取次前】加盟店確認中」、「【進行中】電話取次済」
		$conditions['or'] = array(array('DemandInfo.demand_status' => Util::getDivValue('demand_status', 'no_selection')),array('DemandInfo.demand_status' => Util::getDivValue('demand_status', 'no_guest')), array('DemandInfo.demand_status' => Util::getDivValue('demand_status', 'agency_before')), array('DemandInfo.demand_status' => Util::getDivValue('demand_status', 'telephone_already')));
// 2016.11.24 murata.s ORANGE-185 CHG(S)
// // 2018.08.25 murata.s ORANGE-160 ADD(S)
// 		$conditions['DemandInfo.selection_system !='] = Util::getDivValue('selection_type', 'AuctionSelection');
// // 2016.08.25 murata.s ORANGE-160 ADD(E)
		$conditions[] = array(
			array('DemandInfo.selection_system !=' => Util::getDivValue('selection_type', 'AuctionSelection')),
			array('DemandInfo.selection_system !=' => Util::getDivValue('selection_type', 'AutomaticAuctionSelection'))
		);
// 2016.11.24 murata.s ORANGE-185 CHG(E)
		$this->paginate = array(
				// 2015.07.31 n.kai MOD start ORANGE-750  取次用ダイヤル追加
				'fields' => 'DemandInfo.*, MCorp.id, MCorp.corp_name, MCorp.commission_dial, MItem.item_name'
				// 2015.07.31 n.kai MOD end ORANGE-750
				,'conditions' => $conditions
				,'joins' => $joins
				,'limit' => Configure::read('report_list_limit')
				,'order' => 'DemandInfo.contact_desired_time asc'
		);

		$this->DemandInfo->virtualFields = array(
				'corp_name' => 'MCorp.corp_name',
				// 2015.07.31 n.kai MOD start ORANGE-750  取次用ダイヤル追加
				'commission_dial' => 'MCorp.commission_dial',
				// 2015.07.31 n.kai MOD end ORANGE-750
				'item_name' => 'MItem.item_name',
		);
		// ORANGE-1027 ohta 連絡期限日時複数選択対応
		$this->DemandInfo->setVirtualDetectContactDesiredTime();

		$results = $this->paginate('DemandInfo');

		$this->set('results', $results);
	}
*/
//ORANGE-309 CHG E

	/**
	 * (JBR)進行中後追い一覧
	 */
	public function jbr_ongoing() {

		// セッションに検索条件を保存
		$data['url'] = '/report/jbr_ongoing';
		$data['named'] = $this->params['named'];
		$this->Session->delete(self::$__sessionKeyForReport);
		$this->Session->write(self::$__sessionKeyForReport, $data);

		$joins = array();
		$joins = array(
				array(
						'table' => 'm_sites'
						,'alias' => "MSite"
						,'type' => 'inner'
						,'conditions' => array('DemandInfo.site_id = MSite.id')
				),
				array(
						'table' => '(select * from commission_infos where commit_flg = 1)'
						,'alias' => "CommissionInfo"
						,'type' => 'inner'
						,'conditions' => array('DemandInfo.id = CommissionInfo.demand_id')
				),
				array(
						'table' => 'm_corps'
						,'alias' => "MCorp"
						,'type' => 'inner'
						,'conditions' => array('CommissionInfo.corp_id = MCorp.id')
				),
				array(
						'table' => "(select * from m_items where item_category = '[JBR様]作業内容')"
						,'alias' => "MItem"
						,'type' => 'inner'
						,'conditions' => array('DemandInfo.jbr_work_contents = cast(MItem.item_id as varchar)')
				),
				array(
						'table' => "(select * from m_items where item_category = '[JBR様]見積書状況')"
						,'alias' => "MItem2"
						,'type' => 'inner'
						,'conditions' => array('DemandInfo.jbr_estimate_status = MItem2.item_id')
				),
				array(
						'table' => "(select * from m_items where item_category = '[JBR様]領収書状況')"
						,'alias' => "MItem3"
						,'type' => 'inner'
						,'conditions' => array('DemandInfo.jbr_receipt_status = MItem3.item_id')
				),
		);


		$conditions = array();

		// murata.s ORANGE-447 CHG(S)
		$conditions['or'] = array(
				'MSite.jbr_flg' => 1,	// 生活救急車
				'MSite.id' => 1314		// JBRリピート案件
		);
		// murata.s ORANGE-447 CHG(E)

		// 2015.4.29 n.kai ADD start
		// 「[JBR様]作業内容」の「引越し」(5)以外
		//$conditions['coalesce(cast(DemandInfo.jbr_work_contents as integer), 0) <>'] = 5;
		$conditions["coalesce(DemandInfo.jbr_work_contents, '0') <>"] = '5';

		//取次状況「施工完了」(3) と「 失注」(4)は表示しない
		$conditions['coalesce(CommissionInfo.commission_status, 0) not in'] =
		array(
					Util::getDivValue('construction_status', 'construction'),
					Util::getDivValue('construction_status', 'order_fail')
			);
		// 2015.4.29 n.kai ADD end

		// 「【進行中】情報送信済」
		$conditions['DemandInfo.demand_status'] = Util::getDivValue('demand_status', 'information_sent');

		$this->paginate = array(
				// 2015.4.29 n.kai MOD start　*commission_idを追加
				//'fields' => 'DemandInfo.*, MCorp.id, MCorp.corp_name, MItem.item_name, MItem2.item_name, MItem3.item_name'
				'fields' => 'DemandInfo.*, CommissionInfo.id, MCorp.id, MCorp.corp_name, MItem.item_name, MItem2.item_name, MItem3.item_name'
				// 2015.4.29 n.kai MOD end
				,'conditions' => $conditions
				,'joins' => $joins
				,'limit' => Configure::read('report_list_limit')
				,'order' => 'DemandInfo.contact_desired_time asc'
		);

		$this->DemandInfo->virtualFields = array(
				'corp_name' => 'MCorp.corp_name'
				,'item_name' => 'MItem.item_name'
				,'item_name2' => 'MItem2.item_name'
				,'item_name3' => 'MItem3.item_name'
		);

		$results = $this->paginate('DemandInfo');

		$this->set('results', $results);
	}


	/**
	 * (JBR)領収書後追いレポート
	 */
	public function jbr_receipt_follow() {

		if ($this->RequestHandler->isGet()) {
			$data = $this->Session->read(self::$__sessionKeyForReport);
			$this->data = $data;
		} else {
			$data = $this->request->data;
		}

		// セッションに検索条件を保存
		$data['url'] = '/report/jbr_receipt_follow';
		$data['named'] = $this->params['named'];
		$this->Session->delete(self::$__sessionKeyForReport);
		$this->Session->write(self::$__sessionKeyForReport, $data);

		$joins = array();
		$joins = array(
				array(
						'table' => 'demand_infos'
						,'alias' => "DemandInfo"
						,'type' => 'inner'
						,'conditions' => array('CommissionInfo.demand_id = DemandInfo.id')
				),
				array(
						'table' => 'm_sites'
						,'alias' => "MSite"
						,'type' => 'inner'
						,'conditions' => array('DemandInfo.site_id = MSite.id')
				),
				array(
						'table' => 'm_genres'
						,'alias' => "MGenre"
						,'type' => 'inner'
						,'conditions' => array('DemandInfo.genre_id = MGenre.id')

				),
				array (
						'type' => 'left',
						"table" => "m_items",
						"alias" => "MItem",
						"conditions" => array (
								"MItem.item_category = '[JBR様]見積書状況'",
								"MItem.item_id = DemandInfo.jbr_estimate_status"
						)
				),
				array (
						'fields' => 'MItem2.item_name as jbr_receipt_status_text',
						'type' => 'left',
						"table" => "m_items",
						"alias" => "MItem2",
						"conditions" => array (
								"MItem2.item_category = '[JBR様]領収書状況'",
								"MItem2.item_id = DemandInfo.jbr_receipt_status"
						)
				),
		);

		$conditions = array();

		// murata.s ORANGE-447 CHG(S)
		$conditions[] = array(
				'or' => array(
						'MSite.jbr_flg' => 1,	// 生活救急車
						'MSite.id' => 1314		// JBRリピート案件
				)
		);
		$conditions['DemandInfo.del_flg'] = 0;
		// murata.s ORANGE-447 CHG(E)
		$conditions['DemandInfo.genre_id != '] = Configure::read('jbr_moving_genre_id');

		$conditions['CommissionInfo.commission_status'] = Util::getDivValue('construction_status', 'construction');


		// 後追い日From
		if (!empty($data['follow_date_from'])) {
			$conditions['CommissionInfo.follow_date >='] = $data['follow_date_from'];
		}

		// 後追い日To
		if (!empty($data['follow_date_to'])) {
			$conditions['CommissionInfo.follow_date <='] = $data['follow_date_to'];
		}

		$conditions['or'] = array(
				// 2015.03.17 h.hara
//				array('DemandInfo.genre_id' => Configure::read('jbr_glass_genre_id'), 'DemandInfo.jbr_estimate_status !=' => Util::getDivValue('estimate_status', 'sumi')),
//				array('DemandInfo.genre_id != ' => Configure::read('jbr_glass_genre_id'), 'DemandInfo.jbr_receipt_status !=' => Util::getDivValue('receipt_status', 'sumi'))
				// 2015.5.22 n.kai MOD start
				//array('DemandInfo.genre_id' => Configure::read('jbr_glass_genre_id'), "COALESCE(DemandInfo.jbr_estimate_status, '0') !=" => Util::getDivValue('estimate_status', 'sumi')),
				// ジャンルが[JBRガラス]で見積書状況が[未回収]または[(空欄)]
				array('DemandInfo.genre_id' => Configure::read('jbr_glass_genre_id'),
						"COALESCE(DemandInfo.jbr_estimate_status, '0') not in " => array(Util::getDivValue('estimate_status', 'sumi'), Util::getDivValue('estimate_status', 'send_sumi'))
						),
				// 2015.5.22 n.kai MOD end
				// 2015.04.06 n.kai ADD start
				// ジャンルが[JBRガラス]で見積書情報が[回収済み]且つ領収書状況が[未回収]または[(空欄)]
				array('DemandInfo.genre_id' => Configure::read('jbr_glass_genre_id'),
						'DemandInfo.jbr_estimate_status' => Util::getDivValue('estimate_status', 'sumi'),
						"COALESCE(DemandInfo.jbr_receipt_status, '0') not in " => array(Util::getDivValue('receipt_status', 'sumi'), Util::getDivValue('receipt_status', 'send_sumi'))
						),
				// 2015.04.06 n.kai ADD end
				// 2015.5.22 n.kai ADD start
				// ジャンルが[JBRガラス]で見積書情報が[送信済み]且つ領収書状況が[未回収]または[(空欄)]
				array('DemandInfo.genre_id' => Configure::read('jbr_glass_genre_id'),
						'DemandInfo.jbr_estimate_status' => Util::getDivValue('estimate_status', 'send_sumi'),
						"COALESCE(DemandInfo.jbr_receipt_status, '0') not in " => array(Util::getDivValue('receipt_status', 'sumi'), Util::getDivValue('receipt_status', 'send_sumi'))
						),
				// 2015.5.22 n.kai ADD end
				// 2015.5.22 n.kai MOD start
				//array('DemandInfo.genre_id != ' => Configure::read('jbr_glass_genre_id'), "COALESCE(DemandInfo.jbr_receipt_status, '0') !=" => Util::getDivValue('receipt_status', 'sumi'))
				// ジャンルが[JBRガラス]以外で領収書状況が[未回収]または[(空欄)]
				array('DemandInfo.genre_id != ' => Configure::read('jbr_glass_genre_id'),
						"COALESCE(DemandInfo.jbr_receipt_status, '0') not in " => array(Util::getDivValue('receipt_status', 'sumi'), Util::getDivValue('receipt_status', 'send_sumi'))
						)
				// 2015.5.22 n.kai MOD end
				);

		// 2015.4.13 n.kai MOD start ＊施工金額(税抜)を(税込)に変更
		//$fields = 'DemandInfo.id, CommissionInfo.id, MCorp.id, MCorp.official_corp_name, MGenre.id, MGenre.genre_name, DemandInfo.jbr_order_no, DemandInfo.customer_name, CommissionInfo.complete_date, CommissionInfo.construction_price_tax_exclude, DemandInfo.jbr_estimate_status, DemandInfo.jbr_receipt_status, MItem.item_name, MItem2.item_name';
		// 2015.5.22 n.kai MOD start ＊取次管理画面の初期値制御に合わせるため、DemandInfo.genre_idを追加
		//$fields = 'DemandInfo.id, CommissionInfo.id, MCorp.id, MCorp.official_corp_name, MGenre.id, MGenre.genre_name, DemandInfo.jbr_order_no, DemandInfo.customer_name, CommissionInfo.complete_date, CommissionInfo.construction_price_tax_include, DemandInfo.jbr_estimate_status, DemandInfo.jbr_receipt_status, MItem.item_name, MItem2.item_name';
		$fields = 'DemandInfo.id, CommissionInfo.id, MCorp.id, MCorp.official_corp_name, MGenre.id, MGenre.genre_name, DemandInfo.jbr_order_no, DemandInfo.customer_name, CommissionInfo.complete_date, CommissionInfo.construction_price_tax_include, DemandInfo.genre_id, DemandInfo.jbr_estimate_status, DemandInfo.jbr_receipt_status, MItem.item_name, MItem2.item_name';
		// 2015.5.22 n.kai MOD end
		// 2015.4.13 n.kai MOD end

		if (isset($data['csv_out'])) {

			$params= array(
					'fields' => $fields
					,'conditions' => $conditions
					,'joins' => $joins
					,'limit' => Configure::read('report_list_limit')
					,'order' => 'CommissionInfo.follow_date asc'
			);

			$results = $this->CommissionInfo->find('all', $params);

			$file_name = mb_convert_encoding(__('receipt_follow_csv' , true).'_'.date('YmdHis', time()), 'SJIS-win', 'UTF-8');
			$this->Csv->download('CommissionInfo', 'receipt_report', $file_name, $results);

		} else {

			$this->paginate = array(
					'fields' => $fields
					,'conditions' => $conditions
					,'joins' => $joins
					,'limit' => Configure::read('report_list_limit')
					,'order' => 'CommissionInfo.follow_date asc'
			);

			$this->CommissionInfo->virtualFields = array(
					'genre_id' => 'MGenre.id',
					'demand_id' => 'DemandInfo.id',
					'jbr_order_no' => 'DemandInfo.jbr_order_no'
			);

			$results = $this->paginate('CommissionInfo');


			// 企業数を取得
			$corp_count = 0;
			$corp_count = $this->CommissionInfo->find('count', array(
					'fields' => 'MCorp.id'
					,'conditions' => $conditions
					,'joins' => $joins
					,'group' => 'MCorp.id'
			));


			$this->set('corp_count', $corp_count);
			$this->set('results', $results);

		}
	}

	/**
	 * 追加施工案件一覧
	 */
	public function addition() {

		// 登録ボタンの処理
		if (isset( $this->request->data['regist'] )) {
			// 追加施工案件更新 (ALL)
			if(self::__regist_all_addition($this->request->data)){
				$this->Session->setFlash(__('Updated', true), 'default', array('class' => 'message_inner'));
				$para_data = self::__get_parameter_session();
				return $this->redirect('/report/addition/'.$para_data);
			}
		} else if(isset( $this->request->data['csv_out'] )) {
			$this->__addition_csv_data();
			//return;
		}
		if ($this->RequestHandler->isGet()) {
			$data = $this->Session->read(self::$__sessionKeyForReport);
			$this->data = $data;
		} else {
			$data = $this->request->data;
		}

		// セッションに検索条件を保存
		$data['url'] = '/report/addition';
		$data['named'] = $this->params['named'];
		$this->Session->delete(self::$__sessionKeyForReport);
		$this->Session->write(self::$__sessionKeyForReport, $data);

		// 検索条件の設定
		$conditions = array();
		if (empty($data['demand_flg'])) {
			$conditions['AdditionInfo.demand_flg'] = 0;
		}

		// 追加施工案件のリストを取得
		$results = self::__get_addition_page_list($conditions);

		// 値をセット
		$this->set('results',$results);

	}

	/**
	 * 開拓対象レポート
	 */
	public function development() {

		$data = $this->Session->read(self::$__sessionKeyForReport);
		if(!empty($data)){
			$this->Session->delete(self::$__sessionKeyForReport);
			$this->data = $data;
		}
		$this->set('default_display', false);

	}

	/**
	 * 開拓対象レポート（検索後）
	 *
	 * @param string $status
	 * @param string $address1
	 */
	public function development_search($status = null, $address1 = null){

		$this->set('default_display', true);

		// GETでページ表示の場合
		if ($this->RequestHandler->isGet()) {
			$data = $this->Session->read(self::$__sessionKeyForReport);
			$this->data = $data;
		}
		// POSTでページ表示の場合
		else {
			// 戻るボタン
			if(isset($this->request->data['return'])){
				$data = $this->Session->read(self::$__sessionKeyForReport);
				$this->data = $data;
				if($data['address1']){
					return $this->redirect('/report/development/');
				}
			}

			$data = $this->request->data;
		}

		// ジャンル選択必須 エラー
		if(empty($data['genre_id'])){
			$this->Session->delete(self::$__sessionKeyForReport);
			$this->Session->setFlash ( __( 'ReportGenreError', true ), 'default', array ('class' => 'error_inner') );
			return $this->redirect('/report/development/');
		}

		// セッションに検索条件を保存
		$data['url'] = '/report/development_search';
		$data['named'] = $this->params['named'];
		$this->Session->delete(self::$__sessionKeyForReport);
		$this->Session->write(self::$__sessionKeyForReport, $data);

		if(!empty($address1)){
			$data['address1'] = (int)$address1;
			$data['status'] = $status;
		}

		// 都道府県別の数字を表示
		if(empty($data['address1'])){
			// 条件作成
			$conditions = array (
					'MCorpCategory.genre_id' => $data['genre_id'],
					'MCorp.affiliation_status' => 0,
					'MCorp.corp_status' => 1,
			);
			// 未アタックの情報取得
			$no_attack_list = self::__get_development_list($conditions);
			// 条件作成
			$conditions = array (
					'MCorpCategory.genre_id' => $data['genre_id'],
					'MCorp.affiliation_status' => 0,
					'and' => array (
							array (
									'MCorp.corp_status !=' => 1
							),
							array (
									'MCorp.corp_status !=' => 6
							)
					),
			);
			// 進行中の情報取得
			$advance_list = self::__get_development_list($conditions);

			// 値をセット
			$this->set('no_attack_list', $no_attack_list);
			$this->set('advance_list', $advance_list);
			$this->set('advance_list', $advance_list);

			// レイアウト指定
			$this->render('development');

		}
		// 都道府県縛りの加盟店情報表示
		else {

			// 条件作成
			$conditions = array();
			$conditions['MCorp.affiliation_status'] = 0;
			$conditions['MCorp.address1'] = $data['address1'];
			$conditions['MCorpCategory.genre_id'] = $data['genre_id'];

			if(!empty($data['status'])){
				// 未アタックのみ
				if($data['status'] == 1){
					$conditions['MCorp.corp_status'] = 1;
					// 未アタックの情報取得（カウント）
					$no_attack_list = self::__get_development_list($conditions);
					// 値をセット
					$this->set('no_attack_count',isset($no_attack_list[$data['address1']])?$no_attack_list[$data['address1']]:0);
				}
				// 進行中のみ
				else if($data['status'] == 2){

					$conditions ['and'] = array (
							array (
									'MCorp.corp_status !=' => 1
							),
							array (
									'MCorp.corp_status !=' => 6
							)
					);

					// 進行中の情報取得（カウント）
					$advance_list = self::__get_development_list($conditions);
					// 値をセット
					$this->set('advance_count',isset($advance_list[$data['address1']])?$advance_list[$data['address1']]:0);
				}
			} else {
				$conditions['MCorp.corp_status !='] = 6;

				// 未アタックの条件作成（カウント）
				$no_attack_conditions = array (
						'MCorpCategory.genre_id' => $data['genre_id'],
						'MCorp.affiliation_status' => 0,
						'MCorp.corp_status' => 1,
				);
				// 未アタックの情報取得（カウント）
				$no_attack_list = self::__get_development_list($no_attack_conditions);
				// 値をセット
				$this->set('no_attack_count',isset($no_attack_list[$data['address1']])?$no_attack_list[$data['address1']]:0);

				// 進行中の条件作成（カウント）
				$advance_conditions = array (
						'MCorpCategory.genre_id' => $data['genre_id'],
						'MCorp.affiliation_status' => 0,
						'and' => array (
								array (
										'MCorp.corp_status !=' => 1
								),
								array (
										'MCorp.corp_status !=' => 6
								)
						),
				);
				// 進行中の情報取得（カウント）
				$advance_list = self::__get_development_list($advance_conditions);
				// 値をセット
				$this->set('advance_count',isset($advance_list[$data['address1']])?$advance_list[$data['address1']]:0);

			}

			$results = self::__get_prefecture_development_list($conditions);

			// 値をセット
			$this->set('results',$results);

			// レイアウト指定
			$this->render('prefecture_development');
		}
	}

    //ORANGE-444 ADD S
        /*
	 * 画面からの総合検索情報を保存
	 *  @$params 画面からのPOSTデータ
	 */
	private function __registCommissionSearch($params) {
		try {
			$id = $this->Report->saveCommissionSearchItems($params);
			return $id;
		} catch (Exception $e) {
			$this->_err_message = $e->getMessage();
			return null;
		}
	}
        
        /*
	 * 取次検索IDで情報を検索
	 */
	private function __searchCommissionSearch($id) {
            $results = $this->Report->findCorpCommissionSearch('all',
                array('conditions' => array('CommissionSearchItems.commission_search_id = ' . $id))
            );
            
            if(isset($results[0]['CommissionSearchItems']['commission_search_name'])){
                $this->_commission_search_name = $results[0]['CommissionSearchItems']['commission_search_name'];
            }
            
            //Item
            $defalultFilter = array();
            foreach ($results as $key => $value) {
                if(isset($defalultFilter[$value['CommissionSearchItems']['column_name']])){
                    $defalultFilter[$value['CommissionSearchItems']['column_name']][$i+1]
                         = $value['CommissionSearchItems']['condition_value'];
                    $i++;
                }else{
                    $i = 0;
                    $defalultFilter[$value['CommissionSearchItems']['column_name']][$i]
                         = $value['CommissionSearchItems']['condition_value'];
                }
            }
            return $defalultFilter;
	}

	/*
	 * 指定された総合検索情報を削除
	*  @$params 画面からのPOSTデータ
	*/
	private function __deleteCommissionSearch($id) {
            try {
                $this->Report->deleteCommissionSearch($id);
                return true;
            } catch (Exception $e) {
                $this->_err_message = $e->getMessage();
                return false;
            }
	}
    //ORANGE-444 ADD E
        
	/**
	 * 追加施工案件のリストを取得
	 *
	 * @param unknown $conditions
	 * @return unknown
	 */
	private function __get_addition_page_list($conditions = array()){

		// 検索する
		$this->paginate = array('conditions' => $conditions,
				'fields' => '* , MGenre.genre_name, MCorp.id, MCorp.official_corp_name',
				'joins' => array (
						array (
								'fields' => '*',
								'type' => 'inner',
								"table" => "m_genres",
								"alias" => "MGenre",
								"conditions" => array (
										"MGenre.id = AdditionInfo.genre_id",
								)
						),
						array (
								'fields' => '*',
								'type' => 'inner',
								"table" => "m_corps",
								"alias" => "MCorp",
								"conditions" => array (
										"MCorp.id = AdditionInfo.corp_id",
								)
						)
				),
				'limit' => Configure::read('list_limit'),
				'order' => array('AdditionInfo.id' => 'desc'),
		);

		$this->AdditionInfo->virtualFields = array(
				'official_corp_name' => 'MCorp.official_corp_name',
		);

		$results = $this->paginate('AdditionInfo');

		return $results;
	}

	//ORANGE-262 ADD S
	private function __addition_csv_data(){
		$options = array(
				'fields' => '* , MGenre.genre_name',
				'conditions' => array('AdditionInfo.del_flg' => 0),
				'order' => array('AdditionInfo.id' => 'desc'),
				'joins' => array (
						array (
								'fields' => '*',
								'type' => 'inner',
								"table" => "m_genres",
								"alias" => "MGenre",
								"conditions" => array (
										"MGenre.id = AdditionInfo.genre_id",
								)
						),
				),
		);

		$this->AdditionInfo->virtualFields = array(
				'falsity' => "CASE WHEN falsity_flg = 1 THEN '有' ELSE '無' END",
				'demand' => "CASE WHEN demand_flg = 1 THEN 'チェック有' ELSE 'チェック無' END",
				'demand_type_update' => "CASE WHEN demand_type_update = 1 THEN '復活案件' "
                                                        . "WHEN demand_type_update = 2 THEN '追加施工' "
                                                        . "WHEN demand_type_update = 3 THEN 'その他' "
                                                        . "ELSE '' END",
		);

		$data_list = $this->AdditionInfo->find('all', $options);

		$file_name = mb_convert_encoding(__('addition' , true).'_'.$this->User['user_id'], 'SJIS-win', 'UTF-8');
		$this->Csv->download('AdditionInfo', 'default', $file_name, $data_list);
	}
	//ORANGE-262 ADD E

	/**
	 * 追加施工案件更新 (ALL)
	 *
	 * @param unknown $data
	 * @return boolean
	 */
	private function __regist_all_addition($data){

		foreach ( $data ['AdditionInfo'] as $key => $val){
			$addition_data[$key]['id'] = $val['id'];
			$addition_data[$key]['demand_flg'] = $val['demand_flg'];
			//ORANGE-262 ADD S
			if(!empty($val['memo']))$addition_data[$key]['memo'] = $val['memo'];
			//ORANGE-262 ADD E
		}

		if (0 < count($addition_data)) {
			return $this->AdditionInfo->saveAll($addition_data);
		} else {
			// 登録対象データがない場合も正常終了
			return true;
		}

	}

	/**
	 * GETパラメーターを取得
	 *
	 * @return string
	 */
	private function __get_parameter_session(){

		$data = $this->Session->read(self::$__sessionKeyForReport);

		$para_data = '';
		if(isset($data['named']['page'])){
			$para_data .= '/page:'.$data['named']['page'];
		}
		if(isset($data['named']['sort'])){
			$para_data .= '/sort:'.$data['named']['sort'];
		}
		if(isset($data['named']['direction'])){
			$para_data .= '/direction:'.$data['named']['direction'];
		}

		return $para_data;
	}

	/**
	 * 開拓対象レポートリストの取得 (県リスト)
	 *
	 * @param unknown $conditions 検索条件
	 * @param unknown $genre_id ジャンルID
	 * @return unknown
	 */
	private function __get_development_list($conditions = array()){

		$results = $this->MCorp->find ( 'all', array (
				'fields' => 'MCorp.address1, count(*)',
				'joins' => array (
						array (
								'table' => 'm_corp_categories',
								'alias' => "MCorpCategory",
								'type' => 'inner',
								'conditions' => array (
										'MCorpCategory.corp_id = MCorp.id',
								)
						)
				),
				'conditions' => $conditions,
				'group' => array (
						'MCorp.address1'
				),
				'order' => array (
						'MCorp.address1' => 'asc'
				)
		) );

		$list = array();
		foreach ($results as $v){
			$list[(int)$v['MCorp']['address1']] = $v[0]['count'];
		}

		return $list;

	}

	/**
	 * 開拓対象レポートリストの取得 (県別詳細)
	 *
	 * @param unknown $conditions 検索条件
	 * @param unknown $data データ
	 */
	private function __get_prefecture_development_list($conditions = array()){

		// 検索する
		$this->paginate = array (
				'conditions' => $conditions,
				'fields' => 'MCorp.address1, MUser.user_name, MCorp.id, MCorp.official_corp_name, MItem.item_name, MCorp.note',
				'joins' => array (
						array (
								'table' => 'm_corp_categories',
								'alias' => "MCorpCategory",
								'type' => 'inner',
								'conditions' => array (
										'MCorpCategory.corp_id = MCorp.id',
								)
						),
						array (
								'table' => 'm_users',
								'alias' => "MUser",
								'type' => 'left',
								'conditions' => array (
										'MUser.id = MCorp.rits_person',
								)
						),
						array (
								'table' => 'm_items',
								'alias' => "MItem",
								'type' => 'left',
								'conditions' => array (
										'MItem.item_id = MCorp.corp_status',
										'MItem.item_category' => '開拓状況'
								)
						)
				),
				'group' => array('MCorp.address1', 'MCorpCategory.genre_id', 'MUser.user_name', 'MCorp.official_corp_name' , 'MItem.item_name', 'MCorp.id', 'MCorp.note'),
				'limit' => Configure::read('report_list_limit'),
				'order' => array (
						'MCorp.id' => 'asc'
				)
		);

		$results = $this->paginate ( 'MCorp' );

		return $results;
	}

	/**
	 * 営業支援対象案件レポート
	 */
	public function sales_support() {

		$request = $this->request->query;

		// 初回表示の場合、最終ステップ最新状況表示条件にデフォルト値を設定、
		if (!isset($request['last_step_status'])) {
			$request['last_step_status'] = array(3, 6, 7);
		}

		$results = $this->CommissionInfo
			        ->getSalseSupport($request, $this->request->params['named']);

		// 最終ステップの日本語ラベルを取得
		$surpportKindLabel = Configure::read('surpport_kind_label');

		$itemsStatus = $this->MItem->getMultiList(array('電話対応状況', '受注対応状況', '訪問対応状況'));

		// 2016.01.01 n.kai MOD ORANGE-1164
		$itemsLost = $this->MItem->getMultiList(array('電話対応失注理由', '訪問対応失注理由', '受注対応失注理由'));

		// セレクトボックス用の最終STEP最新状態リスト
		// sasaki@tobila 2016.04.22 MOD Start ORANGE-1288
		$lastStepStatusList = array(
			//1 => '[電話対応]「検討(サービス自体)」',
			//2 => '[電話対応]「検討(日程調整)」',
			8 => '[電話対応]「検討（加盟店様対応中）」',
			9 => '[電話対応]「検討（営業支援対象）」',
			3 => '[電話対応]「失注」',
			//4 => '[訪問対応]「検討(サービス自体)」',
			//5 => '[訪問対応]「検討(日程調整)」',
			10 => '[訪問対応]「検討（加盟店様対応中）」',
			11 => '[訪問対応]「検討（営業支援対象）」',
			6 => '[訪問対応]「失注」',
			7 => '[受注対応]「キャンセル」',
		);
		// sasaki@tobila 2016.04.22 MOD End ORANGE-1288

		$this->set(compact('results', 'surpportKindLabel', 'itemsStatus', 'itemsLost', 'lastStepStatusList'));

		$this->render('sales_support');
	}

	/**
	 * 営業支援除外一括登録
	 */
	public function sales_support_update() {
		// 登録用配列を整形
		if ($exclusionStatus = $this->request->data['CommissionInfo']['re_commission_exclusion_status']) {
			$saveDatas = array();
			foreach ($exclusionStatus as $id => $status) {
				// 除外される場合、ユーザーID、日時を記録
				$saveDatas[] = array(
					'id'                               => $id,
					're_commission_exclusion_status'   => $status,
					're_commission_exclusion_user_id'  => ($status > 0) ? $this->Auth->user('user_id') : '',
					're_commission_exclusion_datetime' => ($status > 0) ? date('Y/m/d H:i:s', time()) : '',
				);
			}

			if (!empty($saveDatas)) {
				$this->CommissionInfo->saveMany($saveDatas);
			}
		}

		$this->Session->setFlash(__('Updated', true), 'default', array('class' => 'message_inner'));

		$this->redirect(array('action' => 'sales_support'));
	}

	/**
	 * 加盟店フォロー用レポート
	 */
	public function affiliation_follow() {

		$results = $this->CommissionInfo
			        ->getAffiliationFollow($this->request->params['named']);

		$items = $this->MItem->getMultiList(array('電話対応状況', '受注対応状況', '訪問対応状況'));

		$this->set(compact('results', 'items'));

		$this->render('affiliation_follow');

	}

	/**
	 * 申請用レポート（管理者）
	 */
	public function application_admin(){

		$conditions = array('Approval.status' => -1);

		//ORANGE-198 CHG S
		// 検索する
		$this->paginate = array('conditions' => $conditions,
				'fields' => '*, CommissionApplication.chg_deduction_tax_include, CommissionApplication.deduction_tax_include, CommissionApplication.chg_irregular_fee_rate, CommissionApplication.irregular_fee_rate,
				CommissionApplication.chg_irregular_fee, CommissionApplication.irregular_fee,  CommissionApplication.irregular_reason, CommissionApplication.introduction_free, CommissionApplication.chg_introduction_free,
				CommissionApplication.chg_ac_commission_exclusion_flg, CommissionApplication.ac_commission_exclusion_flg',
				'joins' => array (
						array (
								'fields' => '*',
								'table' => 'commission_applications',
								'alias' => 'CommissionApplication',
								'type' => 'left',
								'conditions' => array('Approval.relation_application_id = CommissionApplication.id', 'Approval.application_section' => 'CommissionApplication'),

						),
						array(
								'fields' => '*',
								'table' => 'm_corps',
								'alias' => 'MCorp',
								'type' => 'inner',
								'conditions' => array('CommissionApplication.corp_id = MCorp.id'),
						)
				),
				'limit' => Configure::read('list_limit'),
				'order' => array('Approval.id' => 'asc'),
		);
		//ORANGE-198 CHG E

		$this->Approval->virtualFields = array(
				'official_corp_name' => 'MCorp.official_corp_name',
				'corp_id' => 'MCorp.id',
				'commission_id' => 'CommissionApplication.commission_id'
		);

		$results = $this->paginate('Approval');

		$this->set(compact('results'));
	}

	/**
	 * 申請用レポート（アンサー）
	 */
	public function application_answer(){
		$conditions = array();

		if($this->request->is('post') || $this->request->is('put')){
			if (isset ( $this->request->data ['csv_out'] ))  { // CSV出力
				Configure::write('debug', '0');
				$this->layout = false;
//ORANGE-198 CHG S
				$options = array(
						'fields' => "Approval.id,
							CASE WHEN application_section = 'CommissionApplication' THEN '取次管理' END AS custom__application_section,
							Approval.application_user_id, Approval.application_datetime, Approval.application_reason,
							CommissionApplication.deduction_tax_include, CommissionApplication.irregular_fee_rate, CommissionApplication.irregular_fee,
							(SELECT item_name FROM m_items WHERE item_category = 'イレギュラー理由' AND item_id = irregular_reason) AS custom__irregular_reason,
							CASE WHEN introduction_free = 1 THEN '有効' ELSE '無効' END AS custom__introduction_free,
							CASE WHEN ac_commission_exclusion_flg = TRUE THEN '除外する' ELSE '除外しない' END AS custom__ac_commission_exclusion_flg,
							MCorp.official_corp_name, CommissionApplication.commission_id, CommissionApplication.demand_id, CommissionApplication.corp_id,
							(SELECT item_name FROM m_items WHERE item_category = '申請' AND item_id = status) AS custom__status, Approval.approval_user_id, Approval.approval_datetime",
						'joins' => array (
								array (
										'fields' => '*,',
										'table' => 'commission_applications',
										'alias' => 'CommissionApplication',
										'type' => 'left',
										'conditions' => array('Approval.relation_application_id = CommissionApplication.id', 'Approval.application_section' => 'CommissionApplication'),

								),
								array(
										'fields' => '*',
										'table' => 'm_corps',
										'alias' => 'MCorp',
										'type' => 'inner',
										'conditions' => array('CommissionApplication.corp_id = MCorp.id'),
								)
						),
						'order' => array('Approval.id' => 'desc'),
				);
//ORANGE-198 CHG E
				$data_list = $this->Approval->find('all', $options);

				//$this->log($data_list, 'error');
				$file_name = mb_convert_encoding(__('application_answer' , true).'_'.$this->User['user_id'], 'SJIS-win', 'UTF-8');
				$this->Csv->download('Approval', 'default', $file_name, $data_list);
				return;
			}
		}
//ORANGE-198 CHG S
		// 検索する
		$this->paginate = array('conditions' => $conditions,
				'fields' => '*, CommissionApplication.chg_deduction_tax_include, CommissionApplication.deduction_tax_include, CommissionApplication.chg_irregular_fee_rate, CommissionApplication.irregular_fee_rate,
				CommissionApplication.chg_irregular_fee, CommissionApplication.irregular_fee,  CommissionApplication.irregular_reason, CommissionApplication.introduction_free, CommissionApplication.chg_introduction_free,
				CommissionApplication.chg_ac_commission_exclusion_flg, CommissionApplication.ac_commission_exclusion_flg',
				'joins' => array (
						array (
								'fields' => '*',
								'table' => 'commission_applications',
								'alias' => 'CommissionApplication',
								'type' => 'left',
								'conditions' => array('Approval.relation_application_id = CommissionApplication.id', 'Approval.application_section' => 'CommissionApplication'),

						),
						array(
								'fields' => '*',
								'table' => 'm_corps',
								'alias' => 'MCorp',
								'type' => 'inner',
								'conditions' => array('CommissionApplication.corp_id = MCorp.id'),
						)
				),
				'limit' => Configure::read('list_limit'),
				'order' => array('Approval.id' => 'desc'),
		);
//ORANGE-198 CHG E
		$this->Approval->virtualFields = array(
				'official_corp_name' => 'MCorp.official_corp_name',
				'corp_id' => 'MCorp.id',
				'commission_id' => 'CommissionApplication.commission_id'
		);

		$results = $this->paginate('Approval');

		$this->set(compact('results'));

	}

	//ORANGE-128 iwai ADD S
	public function corp_agreement_category(){
		$conditions = array();

		if($this->request->is('post') || $this->request->is('put')){
			if (isset ( $this->request->data ['csv_out'] ))  { // CSV出力
				Configure::write('debug', '0');
				$this->current_controller->layout = false;

// murata.s ORANGE-261 CHG(S)
				$options = array(
						'fields' => "MCorpCategoriesTemp.id,
							CorpAgreementTempLink.corp_agreement_id,
							MCorp.id, MCorp.official_corp_name,
							MGenre.id, MGenre.genre_name,
							MCategory.id, MCategory.category_name,
							MCorpCategoriesTemp.order_fee, CASE WHEN order_fee_unit = 0 THEN '円' WHEN order_fee_unit = 1 THEN '%' ELSE '' END AS custom__order_fee_unit,
							MCorpCategoriesTemp.introduce_fee, MCorpCategoriesTemp.note, MCorpCategoriesTemp.select_list,
							CASE WHEN \"MCorpCategoriesTemp\".\"corp_commission_type\" = 1 THEN '成約ベース' WHEN \"MCorpCategoriesTemp\".\"corp_commission_type\" = 2 THEN '紹介ベース' ELSE '' END AS custom__corp_commission_type,
							CASE when action like 'Add%' then '追加' when action like 'Update%' then '変更' when action like 'Delete%' then '削除' else '' end AS custom__action_type,
							CASE when action like 'Update%' THEN
								replace(replace(replace(replace(replace(replace(replace(action, 'Update:', ''), 'order_fee_unit', '受注手数料単位'), 'order_fee', '受注手数料'), 'note', '備考'), 'select_list', '専門性'), 'introduce_fee', '紹介手数料'), 'corp_commission_type', '取次形態')
							ELSE '' END AS custom__action, MCorpCategoriesTemp.modified",
						'joins' => array (
								array (
										'fields' => '*,',
										'table' => 'corp_agreement_temp_link',
										'alias' => 'CorpAgreementTempLink',
										'type' => 'left',
										'conditions' => array('MCorpCategoriesTemp.temp_id = CorpAgreementTempLink.id'),
								),
								array(
										'fields' => '*',
										'table' => 'm_corps',
										'alias' => 'MCorp',
										'type' => 'inner',
										'conditions' => array('MCorpCategoriesTemp.corp_id = MCorp.id'),
								),
								array (
										'fields' => '*,',
										'table' => 'm_genres',
										'alias' => 'MGenre',
										'type' => 'left',
										'conditions' => array('MCorpCategoriesTemp.genre_id = MGenre.id'),
								),
								array (
										'fields' => '*,',
										'table' => 'm_categories',
										'alias' => 'MCategory',
										'type' => 'left',
										'conditions' => array('MCorpCategoriesTemp.category_id = MCategory.id'),
								),
						),
						'conditions' => array("(MCorpCategoriesTemp.action <> '' or MCorpCategoriesTemp.action IS NOT NULL)"),
						'order' => array('MCorpCategoriesTemp.id' => 'desc'),
				);
// murata.s ORANGE-261 CHG(E)
				$data_list = $this->MCorpCategoriesTemp->find('all', $options);

				//$this->log($data_list, 'error');
				$file_name = mb_convert_encoding(__('corp_agreement_category' , true).'_'.$this->User['user_id'], 'SJIS-win', 'UTF-8');
				$this->Csv->download('MCorpCategoriesTemp', 'default', $file_name, $data_list);
				return;
			}
		}

// murata.s ORANGE-261 CHG(S)
		// 検索する
		$this->paginate = array('conditions' => $conditions,
				'fields' => "MCorpCategoriesTemp.id,
							CorpAgreementTempLink.corp_agreement_id,
							MCorp.id, MCorp.official_corp_name,
							MGenre.id, MGenre.genre_name,
							MCategory.id, MCategory.category_name,
							MCorpCategoriesTemp.order_fee, CASE WHEN order_fee_unit = 0 THEN '円' WHEN order_fee_unit = 1 THEN '%' ELSE '' END AS custom__order_fee_unit,
							MCorpCategoriesTemp.introduce_fee, MCorpCategoriesTemp.note, MCorpCategoriesTemp.select_list,
							CASE WHEN \"MCorpCategoriesTemp\".\"corp_commission_type\" = 1 THEN '成約ベース' WHEN \"MCorpCategoriesTemp\".\"corp_commission_type\" = 2 THEN '紹介ベース' ELSE '' END AS custom__corp_commission_type,
							CASE when action like 'Add%' then '追加' when action like 'Update%' then '変更' when action like 'Delete%' then '削除' else '' end AS custom__action_type,
							CASE when action like 'Update%' THEN
								replace(replace(replace(replace(replace(replace(replace(action, 'Update:', ''), 'order_fee_unit', '受注手数料単位'), 'order_fee', '受注手数料'), 'note', '備考'), 'select_list', '専門性'), 'introduce_fee', '紹介手数料'), 'corp_commission_type', '取次形態')
							ELSE '' END AS custom__action, MCorpCategoriesTemp.modified",
				'joins' => array (
						array (
								'fields' => '*,',
								'table' => 'corp_agreement_temp_link',
								'alias' => 'CorpAgreementTempLink',
								'type' => 'left',
								'conditions' => array('MCorpCategoriesTemp.temp_id = CorpAgreementTempLink.id'),
						),
						array(
								'fields' => '*',
								'table' => 'm_corps',
								'alias' => 'MCorp',
								'type' => 'inner',
								'conditions' => array('MCorpCategoriesTemp.corp_id = MCorp.id'),
						),
						array (
								'fields' => '*,',
								'table' => 'm_genres',
								'alias' => 'MGenre',
								'type' => 'left',
								'conditions' => array('MCorpCategoriesTemp.genre_id = MGenre.id'),
						),
						array (
								'fields' => '*,',
								'table' => 'm_categories',
								'alias' => 'MCategory',
								'type' => 'left',
								'conditions' => array('MCorpCategoriesTemp.category_id = MCategory.id'),
						),
				),
				'order' => array('MCorpCategoriesTemp.id' => 'desc'),
				'conditions' => array("(MCorpCategoriesTemp.action <> '' or MCorpCategoriesTemp.action IS NOT NULL)"),
				'limit' => Configure::read('list_limit'),

		);
// murata.s ORANGE-261 CHG(E)

		$this->MCorpCategoriesTemp->virtualFields = array(
				'official_corp_name' => 'MCorp.official_corp_name',
				'corp_id' => 'MCorp.id',
				'corp_agreement_id' => 'CorpAgreementTempLink.corp_agreement_id',
				'genre_id' => 'MGenre.id',
				'genre_name' => 'MGenre.genre_name',
				'category_id' => 'MCategory.id',
				'category_name' => 'MCategory.category_name',
				'order_fee_unit' => 'custom.order_fee_unit',
				'action_type' => 'custom.action_type',
				'action' => 'custom.action',
		);

		$results = $this->paginate('MCorpCategoriesTemp');

		$this->set(compact('results'));
	}
	//ORANGE-128 iwai ADD E

	/**
	 * 反社チェック 後追いレポート
	 */
	//ORANGE-199 ADD S, ORANGE-346 CHG S
	public function antisocial_follow() {
		$this->loadModel('AntisocialCheck');
		if ($this->request->is('post') || $this->request->is('put')) {
			if (isset($this->request->data['csv_out'])) {
				$this->layout = false;
				$file_name = mb_convert_encoding(__('antisocial_follow', true) . '_' . $this->User['user_id'], 'SJIS-win', 'UTF-8');
				$this->Csv->download('AntisocialCheck', 'antisocial_follow', $file_name, $this->AntisocialCheck->findCSV());
			} elseif (isset($this->request->data['update'])) {
				if ($this->AntisocialCheck->update($this->request->data, $this->Auth->user('auth'))) {
					$this->Session->setFlash(__('Updated', true), 'default', array('class' => 'message_inner'));
				} else {
					$this->Session->setFlash(__('NotData', true), 'default', array('class' => 'warning_inner'));
				}
				$this->redirect($this->referer());
			}
		} else {
			$this->paginate = $this->AntisocialCheck->getPaginate();
			$results = $this->paginate('AntisocialCheck');
			$this->set('is_update_authority', $this->AntisocialCheck->is_update_authority($this->Auth->user('auth')));
			$this->set(compact('results'));
		}
	}
	//ORANGE-199 ADD E, ORANGE-346 CHG E

	//ORANGE-346 ADD S
	/**
	 * 風評チェック 後追いレポート
	 * ORANGE-346 ADD
	 */
	public function reputation_follow() {
		$this->loadModel('ReputationCheck');
		if ($this->request->is('post') || $this->request->is('put')) {
			if (isset($this->request->data['csv_out'])) {
				$this->layout = false;
				$file_name = mb_convert_encoding(__('reputation_follow', true) . '_' . $this->User['user_id'], 'SJIS-win', 'UTF-8');
				$this->Csv->download('ReputationCheck', 'reputation_follow', $file_name, $this->ReputationCheck->findCSV());
			} elseif (isset($this->request->data['update'])) {
				if ($this->ReputationCheck->update($this->request->data, $this->Auth->user('auth'))) {
					$this->Session->setFlash(__('Updated', true), 'default', array('class' => 'message_inner'));
				} else {
					$this->Session->setFlash(__('NotData', true), 'default', array('class' => 'warning_inner'));
				}
				$this->redirect($this->referer());
			}
		} else {
			$this->paginate = $this->ReputationCheck->getPaginate();
			$results = $this->paginate('ReputationCheck');
			$this->set('is_update_authority', $this->ReputationCheck->is_update_authority($this->Auth->user('auth')));
			$this->set(compact('results'));
		}
	}
	//ORANGE-346 ADD E

    // 2017/10/19 ORANGE-541 m-kawamoto DEL(S)
    //// 2017/4/25 ichino ORANGE-402 ADD start
    ///*
    // * ライセンス後追い用レポート
    // */
    //public function license_follow() {
    //    if ($this->request->is('post') || $this->request->is('put')) {
    //        //csvボタン押下時
    //        if (isset($this->request->data['csv_out'])) {
    //            //レイアウト無効
    //            $this->layout = false;
    //
    //            //csvファイルのファイル名を指定
    //            $file_name = mb_convert_encoding(__('license_follow', true) . '_' . $this->User['user_id'], 'SJIS-win', 'UTF-8');
    //
    //            //csvファイルは1行目にヘッダーを出力し、以降対象データ全件を出力
    //            $this->Csv->download('CorpLisenseLink', 'license_follow', $file_name, $this->CorpLisenseLink->findCSV());
    //        }
    //    } else {
    //        //通常遷移
    //        //ページネーションの設定
    //        $this->paginate = $this->CorpLisenseLink->licenseExpirationDate();
    //
    //        //データの取得
    //        $license_data = $this->paginate('CorpLisenseLink');
    //
    //        //データセット
    //        $this->set('license_data', $license_data);
    //    }
    //}
    //// 2017/4/25 ichino ORANGE-402 ADD end
    // 2017/10/19 ORANGE-541 m-kawamoto DEL(E)

    //ORANGE-393 ADD S
    public function corp_category_group_application_admin(){

		//申請状態が「申請中」
		$conditions = array(
				'Approval.status' => -1,
				//'Approval.application_user_id !=' => $this->User['user_id']
		);

		//ORANGE-198 CHG S
		// 検索する
		$this->paginate = array('conditions' => $conditions,
				'fields' => 'CorpCategoryGroupApplication.id, MCorp.id, CorpCategoryGroupApplication.created, CorpCategoryGroupApplication.created_user_id, MCorp.official_corp_name,count(CorpCategoryApplication.id)application_count',
				'joins' => array (
						array(
								'fields' => '*',
								'table' => 'corp_category_applications',
								'alias' => 'CorpCategoryApplication',
								'type' => 'left',
								'conditions' => array('Approval.relation_application_id = CorpCategoryApplication.id', 'Approval.application_section' => 'CorpCategoryApplication'),
						),
						array (
								'fields' => '*',
								'table' => 'corp_category_group_applications',
								'alias' => 'CorpCategoryGroupApplication',
								'type' => 'left',
								'conditions' => array('CorpCategoryApplication.group_id = CorpCategoryGroupApplication.id'),
						),
						array(
								'fields' => '*',
								'table' => 'm_corps',
								'alias' => 'MCorp',
								'type' => 'inner',
								'conditions' => array('CorpCategoryGroupApplication.corp_id = MCorp.id'),
						),
				),
				'order' => 'CorpCategoryGroupApplication.id asc',
				'limit' => Configure::read('list_limit'),
				'group' => array('CorpCategoryGroupApplication.id', 'MCorp.id'),
		);
		//ORANGE-198 CHG E
		$results = $this->paginate('Approval');
		$this->set(compact('results'));
    }

	public function corp_category_application_admin($group_id = null){
		if(empty($group_id)){
			throw new ApplicationException(__('NoReferenceAuthority', true));
		}

		//更新時の処理
		if ($this->request->is('post') || $this->request->is('put')){
			if(!empty($this->request->data['check'])){
				$approval_ids = array();

				foreach($this->request->data['check'] as $approval_id){
					$approval_ids[] = $approval_id;
				}

				$options = array(
						'fields' => '*, CorpCategoryApplication.id, CorpCategoryApplication.corp_id, CorpCategoryApplication.group_id,
								CorpCategoryApplication.category_id, CorpCategoryApplication.order_fee, CorpCategoryApplication.order_fee_unit, CorpCategoryApplication.introduce_fee, CorpCategoryApplication.note, CorpCategoryApplication.corp_commission_type',
						'conditions' => array(
								'Approval.status' => -1,
								'CorpCategoryApplication.group_id' => $group_id,
								'Approval.id' => $approval_ids,
								'Approval.application_user_id !=' => $this->User['user_id']),
						'joins' => array(
								array(
										'fields' => '*',
										'table' => 'corp_category_applications',
										'alias' => 'CorpCategoryApplication',
										'type' => 'left',
										'conditions' => array(
												'Approval.relation_application_id = CorpCategoryApplication.id',
												'Approval.application_section' => 'CorpCategoryApplication'),
								)
						)
				);

				$result = $this->Approval->find('all', $options);
				$corp_id = !empty($result[0]['CorpCategoryApplication']['corp_id']) ? $result[0]['CorpCategoryApplication']['corp_id'] : null;

				//pr($this->request->data);
				$status = -1;

				if(!empty($this->request->data['submit1'])){
					$status = 1;
				}elseif(!empty($this->request->data['submit2'])){
					$status = 2;
				}

				$rtn = false;
				if($status > 0)$rtn = $this->CorpCategoryApplication->saveCorpCategoryTemp($corp_id, $result, $status);
//pr($result);
				if($rtn){
					//成功メッセージ
					$this->Session->setFlash(__('Updated', true), 'default', array('class' => 'message_inner'));

				}else{
					//失敗メッセージ
					$this->Session->setFlash('申請に失敗しました。', 'default', array('class' => 'error_inner'));
				}
			}else{
				// 選択されているカテゴリ手数料申請がない
				$this->Session->setFlash('承認、または却下対象のカテゴリ手数料を選択してください。', 'default', array('class' => 'error_inner'));
			}
		}

		$conditions = array(
				'Approval.status' => -1,
				'CorpCategoryApplication.group_id' => $group_id,
				//'Approval.application_user_id !=' => $this->User['user_id']
		);

		//ORANGE-198 CHG S
		// 検索する
		$this->paginate = array('conditions' => $conditions,
				'fields' => '*, CorpCategoryApplication.id, MGenre.genre_name, MCategory.category_name, CorpCategoryApplication.corp_id, CorpCategoryApplication.group_id,
						CorpCategoryApplication.order_fee, CorpCategoryApplication.order_fee_unit, CorpCategoryApplication.introduce_fee, CorpCategoryApplication.note, CorpCategoryApplication.corp_commission_type',
				'joins' => array (
						array(
								'fields' => '*',
								'table' => 'corp_category_applications',
								'alias' => 'CorpCategoryApplication',
								'type' => 'left',
								'conditions' => array('Approval.relation_application_id = CorpCategoryApplication.id', 'Approval.application_section' => 'CorpCategoryApplication'),
						),
						array(
								'fields' => '*',
								'table' => 'm_corps',
								'alias' => 'MCorp',
								'type' => 'inner',
								'conditions' => array('CorpCategoryApplication.corp_id = MCorp.id'),
						),
						array(
								'fields' => '*',
								'table' => 'm_genres',
								'alias' => 'MGenre',
								'type' => 'left',
								'conditions' => array('CorpCategoryApplication.genre_id = MGenre.id'),
						),
						array(
								'fields' => '*',
								'table' => 'm_categories',
								'alias' => 'MCategory',
								'type' => 'left',
								'conditions' => array('CorpCategoryApplication.category_id = MCategory.id'),
						),
				),
				'order' => array('Approval.id' => 'asc'),
		);
		//ORANGE-198 CHG E

		$this->Approval->virtualFields = array(
				'official_corp_name' => 'MCorp.official_corp_name',
				'corp_id' => 'MCorp.id',
		);

		$results = $this->paginate('Approval');
//pr($results);
		$this->set(compact('results'));
	}

	public function corp_category_group_application_answer(){

		if($this->request->is('post') || $this->request->is('put')){
			if(isset($this->request->data['csv_out'])){
				// CSV出力
				Configure::write('debug', 0);
				$this->layout = false;

				// SearchPluginを使用して検索条件を取得した場合redirectされてCSV出力か検索か判断できなくなるため、
				// ここではSearchPluginを使用しないようにする
				$parsedParams = array(
						'corp_id' => Hash::get($this->request->data, "CorpCategoryGroupApplication.corp_id"),
						'corp_name' => Hash::get($this->request->data, "CorpCategoryGroupApplication.corp_name"),
						'group_id' => Hash::get($this->request->data, "CorpCategoryGroupApplication.group_id"),
						'application_date_from' => Hash::get($this->request->data, "CorpCategoryGroupApplication.application_date_from"),
						'application_date_to' => Hash::get($this->request->data, "CorpCategoryGroupApplication.application_date_to")
				);

				$options = array(
						'conditions' => $this->CorpCategoryGroupApplication->parseCriteria($parsedParams),
						'fields' => 'CorpCategoryGroupApplication.id, CorpCategoryGroupApplication.corp_id, CorpCategoryApplication.category_id, CorpCategoryApplication.introduce_fee, '
								.'CorpCategoryApplication.id, CorpCategoryApplication.genre_id, CorpCategoryApplication.order_fee, CorpCategoryApplication.note, '
								.'Approval.id, Approval.application_user_id, Approval.application_datetime, Approval.approval_user_id, Approval.approval_datetime, Approval.application_reason, '
								.'MCorp.official_corp_name,'
								.'MGenre.genre_name,'
								.'MCategory.category_name,'
								."CASE WHEN \"Approval\".application_section = 'CorpCategoryApplication' THEN 'カテゴリ手数料' END AS custom__application_section,"
								."CASE \"CorpCategoryApplication\".order_fee_unit WHEN 1 THEN '%' WHEN 0 THEN '円' ELSE '' END AS custom__order_fee_unit,"
								."CASE WHEN \"CorpCategoryApplication\".introduce_fee IS NULL THEN '' ELSE \"CorpCategoryApplication\".introduce_fee || '円' END AS custom__introduce_fee,"
								."CASE WHEN \"CorpCategoryApplication\".corp_commission_type != 2 THEN '成約ベース' ELSE '紹介ベース' END AS custom__corp_commission_type, "
								."(SELECT item_name FROM m_items WHERE item_category = '申請' AND item_id = \"Approval\".status) AS custom__status",
						'joins' => array(
								array(
										'fields' => '*',
										'table' => 'corp_category_applications',
										'alias' => 'CorpCategoryApplication',
										'type' => 'left',
										'conditions' => array('CorpCategoryApplication.group_id = CorpCategoryGroupApplication.id')
								),
								array(
										'fields' => '*',
										'table' => 'approvals',
										'alias' => 'Approval',
										'type' => 'left',
										'conditions' => array(
												'Approval.relation_application_id = CorpCategoryApplication.id',
												'Approval.application_section' => 'CorpCategoryApplication'
										)
								),
								array(
										'fields' => '*',
										'table' => 'm_corps',
										'alias' => 'MCorp',
										'conditions' => array('CorpCategoryGroupApplication.corp_id = MCorp.id')
								),
								array(
										'fields' => '*',
										'table' => 'm_genres',
										'alias' => 'MGenre',
										'type' => 'left',
										'conditions' => array('CorpCategoryApplication.genre_id = MGenre.id')
								),
								array(
										'fields' => '*',
										'table' => 'm_categories',
										'alias' => 'MCategory',
										'type' => 'left',
										'conditions' => array('CorpCategoryApplication.category_id = MCategory.id')
								)
						),
						'order' => array(
								'CorpCategoryGroupApplication.id' => 'desc',
								'Approval.id' => 'asc')
				);

				$data_list = $this->CorpCategoryGroupApplication->find('all', $options);

				$file_name = mb_convert_encoding(__('corp_category_application_answer' , true).'_'.$this->User['user_id'], 'SJIS-win', 'UTF-8');
				$this->Csv->download('CorpCategoryGroupApplication', 'default', $file_name, $data_list);
				return;
			}
		}

		// セッションに検索条件を保存
		//$data['url'] = '/report/corp_category_group_application_answer';
		//$data['named'] = $this->params['named'];
		//$this->Session->delete(self::$__sessionKeyForReport);
		//$this->Session->write(self::$__sessionKeyForReport, $data);
		$this->Prg->commonProcess();

		$this->CorpCategoryGroupApplication->bindModel(
				array(
						'belongsTo' => array(
								'MCorp' => array(
										'className' => 'MCorp',
										'foreignKey' => 'corp_id',
										'fields' => array('official_corp_name'),
								),
						),
				)
				, false);

		//$conditions = $this->CorpCategoryGroupApplication->parseCriteria($this->Prg->parsedParams());

		//ORANGE-198 CHG S
		// 検索する
		$this->paginate = array('conditions' => array($this->CorpCategoryGroupApplication->parseCriteria($this->Prg->parsedParams())),
				'fields' => 'CorpCategoryGroupApplication.id, MCorp.id, CorpCategoryGroupApplication.created, CorpCategoryGroupApplication.created_user_id, MCorp.official_corp_name,count(CorpCategoryApplication.id)application_count,'
						.' SUM(CASE WHEN "Approval"."status" = -1 THEN 1 ELSE 0 END) AS unapproved_count, SUM(CASE WHEN "Approval"."status" = 1 THEN 1 ELSE 0 END) AS approval_count, SUM(CASE WHEN "Approval"."status" = 2 THEN 1 ELSE 0 END) AS reject_count',
				'joins' => array (
						array (
								'fields' => '*',
								'table' => 'corp_category_applications',
								'alias' => 'CorpCategoryApplication',
								'type' => 'left',
								'conditions' => array('CorpCategoryApplication.group_id = CorpCategoryGroupApplication.id'),
						),array(
								'fields' => '*',
								'table' => 'approvals',
								'alias' => 'Approval',
								'type' => 'LEFT',
								'conditions' => array(
										'CorpCategoryApplication.id = Approval.relation_application_id',
										'Approval.application_section' => 'CorpCategoryApplication'
								),
						),/*
						array(
								'fields' => '*',
								'table' => 'm_corps',
								'alias' => 'MCorp',
								'type' => 'inner',
								'conditions' => array('CorpCategoryApplication.corp_id = MCorp.id'),
						),*/
				),
				'order' => 'CorpCategoryGroupApplication.id desc',
				'limit' => Configure::read('list_limit'),

				'group' => array('CorpCategoryGroupApplication.id', 'MCorp.id'),

		);
		//ORANGE-198 CHG E
		$results = $this->paginate('CorpCategoryGroupApplication');
		$this->set(compact('results'));
	}

	public function corp_category_application_answer($group_id = null){
		if(empty($group_id)){
			throw new ApplicationException(__('NoReferenceAuthority', true));
		}

		$conditions = array('CorpCategoryApplication.group_id' => $group_id);

		//ORANGE-198 CHG S
		// 検索する
		$this->paginate = array('conditions' => $conditions,
				'fields' => '*, CorpCategoryApplication.id, MGenre.genre_name, MCategory.category_name, CorpCategoryApplication.corp_id, CorpCategoryApplication.group_id,
						CorpCategoryApplication.order_fee, CorpCategoryApplication.order_fee_unit, CorpCategoryApplication.introduce_fee, CorpCategoryApplication.note, CorpCategoryApplication.corp_commission_type',
				'joins' => array (
						array(
								'fields' => '*',
								'table' => 'corp_category_applications',
								'alias' => 'CorpCategoryApplication',
								'type' => 'left',
								'conditions' => array('Approval.relation_application_id = CorpCategoryApplication.id', 'Approval.application_section' => 'CorpCategoryApplication'),
						),
						array(
								'fields' => '*',
								'table' => 'm_corps',
								'alias' => 'MCorp',
								'type' => 'inner',
								'conditions' => array('CorpCategoryApplication.corp_id = MCorp.id'),
						),
						array(
								'fields' => '*',
								'table' => 'm_genres',
								'alias' => 'MGenre',
								'type' => 'left',
								'conditions' => array('CorpCategoryApplication.genre_id = MGenre.id'),
						),
						array(
								'fields' => '*',
								'table' => 'm_categories',
								'alias' => 'MCategory',
								'type' => 'left',
								'conditions' => array('CorpCategoryApplication.category_id = MCategory.id'),
						),
				),
				'order' => array('Approval.id' => 'asc'),
		);
		//ORANGE-198 CHG E

		$this->Approval->virtualFields = array(
				'official_corp_name' => 'MCorp.official_corp_name',
				'corp_id' => 'MCorp.id',
		);

		$results = $this->paginate('Approval');
		//pr($results);
		$this->set(compact('results'));
	}
	//ORANGE-393 ADD E

    // 2017/6/26 inokuma ORANGE-443 ADD start
    /*
     * リアルタイムレポート
     */
    public function real_time_report() {
        //条件式取得
        $query = $this->DemandInfo->findReportDemandStatus();
        
        $results = $this->DemandInfo->find('all',$query);
        
        //架電可能内/要ヒア数・断り数取得
        $results1 = $this->DemandInfo->getRealTimeReport_HearLossNum1();

        //(JBR)架電可能内/要ヒア数・断り数取得
        $results2 = $this->DemandInfo->getRealTimeReport_HearLossNum2();

        //結果セットに項目追加
        $results[0][0]['CallHearNum'] = $results1[0][0]['CallHearNum1'] + $results2[0][0]['CallHearNum2'];
        $results[0][0]['CallLossNum'] = $results1[0][0]['CallLossNum1'] + $results2[0][0]['CallLossNum2'];

        $this->set('results', $results);
    }
    // 2017/6/26 inokuma ORANGE-443 ADD end
}
