<?php
/*
 * 2017/06/08  ichino ORANGE-420 ADD
 * 自動取次先加盟店設定
 */

App::uses('AppController', 'Controller');

class AutoCommissionCorpController extends AppController{
	public $name = 'AutoCommissionCorp';
	public $components = array('Session', 'RequestHandler');
	public $uses = array('MCorp', 'MGenre', 'MCategories', 'AutoCommissionCorp', 'MPost', 'MTargetArea', 'MCorpCategory');
	
	public $prefecture_list;

	public function beforeFilter(){
		parent::beforeFilter();
		$this->User = $this->Auth->user();
		
		//configから都道府県を取得
		$this->prefecture_list = Configure::read('STATE_LIST');
		$this->set('prefecture_list', $this->prefecture_list);
	}
	
	/*
	 * 初期画面
	 */
	public function index(){
		//全ジャンル カテゴリ取得
		$genre_category_list = $this->_get_genre_category();

		//選定方式取得
		foreach(Util::getDivList('selection_type') as $key => $val){
			$selection_system_list[$key] = $val;
		}
		$this->set('genre_select_list', $this->_genre_list_convert($genre_category_list));
		$this->set('selection_system_list', $selection_system_list);
	}
	
	/*
	 * 加盟店追加画面
	 */
	// 2017.11.10 ORANGE-578 h.miyake CHG(S)
	public function corp_select(){
	//public function corp_add(){
	// 2017.11.10 ORANGE-578 h.miyake CHG(E)
	// 2017.11.10 ORANGE-578 h.miyake ADD(S)
		//post送信判定
				$javascriptData = "";
	// 2017.11.10 ORANGE-578 h.miyake ADD(E)
		if ($this->request->is('post')){
			
			//データ保持
			$this->data = $this->request->data;
			
			// 2017.11.10 ORANGE-578 h.miyake ADD(S)
			// 一覧表示
			if($this->data["category"] == 'genreSelect') {
				if(!empty($this->data["AutoCommissionCorp"]["category_id"])) {
					$javascriptData .= "var data1 = [];" . "\n";
					foreach($this->data["AutoCommissionCorp"]["category_id"] as $key => $value) {
						$javascriptData .= "data1[" . $key . "] = " . $value . ";" . "\n";
					}
				}
			// 2017.11.10 ORANGE-578 h.miyake ADD(E)
			// 2017.11.10 ORANGE-578 h.miyake DEL(S)
			//if(Hash::get($this->request->data, 'corp_select')){
			// 2017.11.10 ORANGE-578 h.miyake DEL(E)
				//加盟店取得
				$corp_list =array();
                // 2017.12.01 ORANGE-603 h.miyake CHG(S)
				//$corp_list_result = $this->_get_auto_corp_list($this->data);
				$corp_list_result = $this->_get_auto_corp_list1($this->data);
                // 2017.12.01 ORANGE-603 h.miyake CHG(E)
				//リストの形式に整形 idとcorp_name
				$corp_list = $this->_corp_list_convert($corp_list_result);
				
				// 2017/10/04 ORANGE-509 m-kawamoto CHG(S)
				//登録済みの加盟店をセット
				$this->set('corp_selection_list', $corp_list[1]); // 処理種別 1:自動選定
				$this->set('corp_commission_list', $corp_list[2]); // 処理種別 2:自動取次
				// 2017/10/04 ORANGE-509 m-kawamoto CHG(E)
				
				// 2017.11.10 ORANGE-578 h.miyake DEL(S)
				//viewの指定
				//$this->render('corp_select');
				// 2017.11.10 ORANGE-578 h.miyake DEL(E)

			// 2017/10/04 ORANGE-509 m-kawamoto CHG(S)
			} elseif (Hash::get($this->request->data, 'searchkey')){
			// 2017/10/04 ORANGE-509 m-kawamoto CHG(E)
				
				//保存済の内容の削除を行う
				// 2017.11.10 ORANGE-578 h.miyake DEL(S)
//				$delete_result = $this->_delete_corp_list($this->data);
				//$this->render('corp_select');
//				if(!$delete_result){
					//リダイレクト
//					$this->redirect('/auto_commission_corp/');
//				}
				// 2017.11.10 ORANGE-578 h.miyake DEL(E)
				// 2017.11.10 ORANGE-578 h.miyake ADD(S)
				foreach($this->data["AutoCommissionCorp"]["address1"] as $keyAddressCommission => $valAddressCommission) {
					foreach($this->data["AutoCommissionCorp"]["category_id"] as $keyCategoryIdCommission =>$valCategoryIdCommission) {
						$this->AutoCommissionCorp->deleteAll(array('category_id' => $valCategoryIdCommission, 'jis_cd LIKE ' => $valAddressCommission . "%"));
				}
				}
				// 2017.11.10 ORANGE-578 h.miyake ADD(E)
				
				// 2017.11.10 ORANGE-578 h.miyake DEL(S)
				//都道府県からjis_cdを取得
				//$jiscd_result = $this->_get_jiscd($this->data);
				// 2017.11.10 ORANGE-578 h.miyake DEL(E)
				
				//取得したjis_cdと加盟店でjis_cd分データを作成
				$save_data = array();
				
				// 2017/10/04 ORANGE-509 m-kawamoto CHG(S)
				if(is_array(Hash::get($this->data, 'AutoCommissionCorp.commission_corp_id')))
				{
					// 2017.11.10 ORANGE-578 h.miyake CHG(S)
					foreach($this->data["AutoCommissionCorp"]["address1"] as $keyAddressCommission => $valAddressCommission) {
						//都道府県からjis_cdを取得
						$Prefecture["AutoCommissionCorp"]["address1"] = $valAddressCommission;
						$jiscd_result = $this->_get_jiscd($Prefecture);
						foreach($this->data["AutoCommissionCorp"]["category_id"] as $keyCategoryIdCommission =>$valCategoryIdCommission) {
							foreach (Hash::get($this->data, 'AutoCommissionCorp.commission_corp_id') as $corp_id_key => $corp_id_value) {
								foreach ($jiscd_result as $key => $value) {
									$save_data['AutoCommissionCorp'][] = array(
										'corp_id' => $corp_id_value,
												'category_id' => $valCategoryIdCommission,
										'sort' => $corp_id_key,
										'jis_cd' => $value['MPost']['jis_cd'],
										'created_user_id' => $this->User['user_id'],
										'modified_user_id' => $this->User['user_id'],
										'process_type' => 2, // 処理種別 2:自動取次 
									);
								}
							}
						}
					}
					// 2017.11.10 ORANGE-578 h.miyake CHG(E)
				}

				if(is_array(Hash::get($this->data, 'AutoCommissionCorp.selection_corp_id')))
				{
					// 2017.11.10 ORANGE-578 h.miyake CHG(S)
					foreach($this->data["AutoCommissionCorp"]["address1"] as $keyAddressSelection => $valAddressSelection) {
						//都道府県からjis_cdを取得
						$Prefecture["AutoCommissionCorp"]["address1"] = $valAddressSelection;
						$jiscd_result = $this->_get_jiscd($Prefecture);
						foreach($this->data["AutoCommissionCorp"]["category_id"] as $keyCategoryIdSelection =>$valCategoryIdSelection) {
							foreach (Hash::get($this->data, 'AutoCommissionCorp.selection_corp_id') as $corp_id_key => $corp_id_value) {
								foreach ($jiscd_result as $key => $value) {
									$save_data['AutoCommissionCorp'][] = array(
										'corp_id' => $corp_id_value,
												'category_id' => $valCategoryIdSelection,
										'sort' => $corp_id_key,
										'jis_cd' => $value['MPost']['jis_cd'],
										'created_user_id' => $this->User['user_id'],
										'modified_user_id' => $this->User['user_id'],
										'process_type' => 1, // 処理種別 1:自動選定 
									);
								}
							}
						}
					}
					// 2017.11.10 ORANGE-578 h.miyake CHG(E)
				}
				
				if(isset($save_data['AutoCommissionCorp']))
				{
					//保存
					try{
						$this->AutoCommissionCorp->begin();
						$this->AutoCommissionCorp->saveAll($save_data['AutoCommissionCorp'], array('validate' => false, 'atomic' => false));
						$this->AutoCommissionCorp->commit();
											
						$this->Session->setFlash(__('Updated', true), 'default', array('class' => 'message_inner'));
					} catch (Exception $e) {
						//エラー
						$this->AutoCommissionCorp->rollback();
						$this->Session->setFlash(__('FailRegist', true), 'default', array('class' => 'error_inner'));
						$this->log('auto_commission_corp 保存エラー' . $e->getMessage());
					}
				}
				// 2017/10/04 ORANGE-509 m-kawamoto CHG(E)
				
				//リダイレクト
				$this->redirect('/auto_commission_corp/');
			}
		}
		// 2017.11.10 ORANGE-578 h.miyake ADD(S)
		else {
			$this->set('corp_selection_list', ""); // 処理種別 1:自動選定
			$this->set('corp_commission_list', ""); // 処理種別 2:自動取次
		}
		$this->set("javascriptData", $javascriptData);
		// 2017.11.10 ORANGE-578 h.miyake ADD(E)
	}

	// 2017.12.01 ORANGE-603 h.miyake ADD(S)
	/*
	 * 加盟店追加画面
	 */
	public function corp_add(){
		//送信判定
		if($this->request->is('get')) {
			//データ保持
			$this->data = $this->request->query;

			//加盟店取得
			$corp_list =array();
			$corp_list_result = $this->_get_auto_corp_list($this->data);

			//リストの形式に整形 idとcorp_name
			$corp_list = $this->_corp_list_convert($corp_list_result);

			//登録済みの加盟店をセット
			$this->set('corp_selection_list', $corp_list[1]); // 処理種別 1:自動選定
			$this->set('corp_commission_list', $corp_list[2]); // 処理種別 2:自動取次

		} elseif (Hash::get($this->request->data, 'searchkey' && $this->request->is('post'))){
			$this->data = $this->request->data;

			//保存済の内容の削除を行う
			$delete_result = $this->_delete_corp_list($this->data);
			if(!$delete_result){
				//リダイレクト
				$this->redirect('/auto_commission_corp/');
			}

			//都道府県からjis_cdを取得
			$jiscd_result = $this->_get_jiscd($this->data);

			//取得したjis_cdと加盟店でjis_cd分データを作成
			$save_data = array();

			if(is_array(Hash::get($this->data, 'AutoCommissionCorp.commission_corp_id'))) {
				foreach (Hash::get($this->data, 'AutoCommissionCorp.commission_corp_id') as $corp_id_key => $corp_id_value) {
					foreach ($jiscd_result as $key => $value) {
						$save_data['AutoCommissionCorp'][] = array(
							'corp_id' => $corp_id_value,
							'category_id' => $this->data['category_id'],
							'sort' => $corp_id_key,
							'jis_cd' => $value['MPost']['jis_cd'],
							'created_user_id' => $this->User['user_id'],
							'modified_user_id' => $this->User['user_id'],
							'process_type' => 2, // 処理種別 2:自動取次 
						);
					}
				}
			}

			if(is_array(Hash::get($this->data, 'AutoCommissionCorp.selection_corp_id'))) {
				foreach (Hash::get($this->data, 'AutoCommissionCorp.selection_corp_id') as $corp_id_key => $corp_id_value) {
					foreach ($jiscd_result as $key => $value) {
						$save_data['AutoCommissionCorp'][] = array(
							'corp_id' => $corp_id_value,
							'category_id' => $this->data['category_id'],
							'sort' => $corp_id_key,
							'jis_cd' => $value['MPost']['jis_cd'],
							'created_user_id' => $this->User['user_id'],
							'modified_user_id' => $this->User['user_id'],
							'process_type' => 1, // 処理種別 1:自動選定 
						);
					}
				}
	}

			if(isset($save_data['AutoCommissionCorp'])) {
				//保存
				try {
					$this->AutoCommissionCorp->begin();
					$this->AutoCommissionCorp->saveAll($save_data['AutoCommissionCorp'], array('validate' => false, 'atomic' => false));
					$this->AutoCommissionCorp->commit();
					$this->Session->setFlash(__('Updated', true), 'default', array('class' => 'message_inner'));
				} 
				catch (Exception $e) {
					//エラー
					$this->AutoCommissionCorp->rollback();
					$this->Session->setFlash(__('FailRegist', true), 'default', array('class' => 'error_inner'));
					$this->log('auto_commission_corp 保存エラー' . $e->getMessage());
				}
			}
			//リダイレクト
			$this->redirect('/auto_commission_corp/');
		}
	}
	// 2017.12.01 ORANGE-603 h.miyake ADD(E)

	// 2017.11.10 ORANGE-603 h.miyake ADD(S)
	/*
	 * 加盟店取得アクション
	 * ajaxのみ許可
	 */
	public function get_corp_add_list(){
		if (!$this->request->isAll(array('post', 'ajax'))) {
			throw new BadRequestException();
		}
		
		$this->autoRender = false;
		$this->response->type('json');
		try {
			switch (Hash::get($this->request->data, 'searchkey')) {
				case 'search_corp_name':
					$patterns = preg_split('/[\s]/', str_replace('　', ' ', Hash::get($this->request->data, 'search_corp_name')), -1, PREG_SPLIT_NO_EMPTY);
					$searchkey = array();
					array_walk($patterns, function($value) use(&$searchkey) {
						if (mb_substr($value, 0, 1) === '-') {
							$searchkey[] = array('MCorp.corp_name NOT LIKE' => '%' . mb_substr($value, 1) . '%');
						} 
						else {
							$searchkey[] = array('MCorp.corp_name LIKE' => '%' . $value . '%');
						}
					});
					break;
				case 'search_corp_id':
					$patterns = Hash::filter(
						preg_split('/[\s\n,]/', Hash::get($this->request->data, 'search_corp_id'), -1, PREG_SPLIT_NO_EMPTY)
							, function(	$part) {
								return is_numeric($part);
				});
					$searchkey = array('MCorp.id' => (array) $patterns);
					break;
			}

			//すでに選択済加盟店
			$knowns = array();
			$commission_knows = Hash::get($this->request->data, 'AutoCommissionCorp.commission_corp_id');
			$selection_knows = Hash::get($this->request->data, 'AutoCommissionCorp.selection_corp_id');
			$commission_knows = $commission_knows === "" ? array() : $commission_knows;
			$selection_knows = $selection_knows === "" ? array() : $selection_knows;
			$knowns = array_merge($commission_knows, $selection_knows);

			//カテゴリーID
			$category_id = Hash::get($this->request->data, 'category_id');

			//都道府県コード 
			$pref_cd = Hash::get($this->request->data, "pref_cd");
			if(!ctype_digit($pref_cd)) {
				throw new BadRequestException();
			}

			$fields = array('MCorp.id', 'MCorp.corp_name');
			$joins = array(
				array (
					'type' => 'inner',
					'table' => 'm_corp_categories',
					'alias' => 'MCorpCategory',
					'conditions' => array (
						'MCorpCategory.corp_id = MCorp.id',
					),
				),
				array (
					'type' => 'inner',
					'table' => "(SELECT corp_category_id, SUBSTRING(jis_cd, 1, 2) FROM m_target_areas WHERE SUBSTRING(jis_cd, 1, 2) = '" . $pref_cd . "' GROUP BY corp_category_id, SUBSTRING(jis_cd, 1, 2))",
					'alias' => 'MTargetArea',
					'conditions' => array (
						'MTargetArea.corp_category_id = MCorpCategory.id',
					)
				),
			);

			$query = array(
				'conditions' => array(
					(array) $searchkey,
					'MCorp.del_flg' => 0,
					'MCorpCategory.category_id' => $category_id,
					'NOT' => array(
						'MCorp.id' => $knowns,
					),
				),
				'fields' => $fields,
				'joins' => $joins,
				'recursive' => -1,
				'order' => array('MCorp.id'),
				'limit' => 50,
			);

			$status = true;
			$count = $this->MCorp->find('count', $query);
			$data = $this->MCorp->find('list', $query);

			return json_encode(compact('status', 'count', 'data'));
		} 
		catch (Exception $e) {
			$this->response->statusCode(500);
			return json_encode(array('message' => $e->getMessage()));
		}
	}
	// 2017.12.01 ORANGE-603 h.miyake ADD(E)

	/*
	 * 加盟店取得アクション
	 * ajaxのみ許可
	 */
	public function get_corp_list(){
		if (!$this->request->isAll(array('post', 'ajax'))) {
			throw new BadRequestException();
		}
		$this->autoRender = false;
		$this->response->type('json');
		
		try{
			switch (Hash::get($this->request->data, 'searchkey')) {
				case 'search_corp_name':
						$patterns = preg_split('/[\s]/', str_replace('　', ' ', Hash::get($this->request->data, 'search_corp_name')), -1, PREG_SPLIT_NO_EMPTY);
						$searchkey = array();
						array_walk($patterns, function($value) use(&$searchkey) {
							if (mb_substr($value, 0, 1) === '-') {
									$searchkey[] = array('MCorp.corp_name NOT LIKE' => '%' . mb_substr($value, 1) . '%');
							} else {
									$searchkey[] = array('MCorp.corp_name LIKE' => '%' . $value . '%');
							}
						});
						break;
				case 'search_corp_id':
						$patterns = Hash::filter(
							preg_split('/[\s\n,]/', Hash::get($this->request->data, 'search_corp_id'), -1, PREG_SPLIT_NO_EMPTY)
							, function($part) {
								return is_numeric($part);
							});
						$searchkey = array('MCorp.id' => (array) $patterns);
						break;
			}
			
			// 2017/10/04 ORANGE-509 m-kawamoto CHG(S)
			//すでに選択済加盟店
			$knowns = array();
			$commission_knows = Hash::get($this->request->data, 'AutoCommissionCorp.commission_corp_id');
			$selection_knows = Hash::get($this->request->data, 'AutoCommissionCorp.selection_corp_id');
			$commission_knows = $commission_knows === "" ? array() : $commission_knows;
			$selection_knows = $selection_knows === "" ? array() : $selection_knows;
			$knowns = array_merge($commission_knows, $selection_knows);
			// 2017/10/04 ORANGE-509 m-kawamoto CHG(E)
			
			//カテゴリーID
			$category_id = Hash::get($this->request->data, 'AutoCommissionCorp.category_id');

			//都道府県コード 
			// 2017/10/18 ORANGE-571 m-kawamoto CHG(S)
			// 2017.11.10 ORANGE-578 h.miyake CHG(S)
			$pref_cd1 = Hash::get($this->request->data, "AutoCommissionCorp.address1");
			$pref_cd = "";
			foreach($pref_cd1 as $key => $value) {
				if(!ctype_digit($value))
				{
					throw new BadRequestException();
				}
				else {
					$pref_cd[$key] =  $value;
				}
			}
			// 2017.11.10 ORANGE-578 h.miyake CHG(E)
			// 2017/10/18 ORANGE-571 m-kawamoto CHG(E)
			// 2017.11.10 ORANGE-578 h.miyake CHG(S)
			$count = 0;
			$arrDiff = array();
			$boolDiff = false;
			foreach($pref_cd as $keyPrefCd => $valPrefCd ) {
				foreach($category_id as $keyCategoryId => $valCategoryId) {
					$fields = array('MCorp.id', 'MCorp.corp_name');
					
					$joins = array(
						array (
							'type' => 'inner',
							'table' => 'm_corp_categories',
							'alias' => 'MCorpCategory',
							'conditions' => array (
								'MCorpCategory.corp_id = MCorp.id',
							),
						),
						array (
							'type' => 'inner',
							'table' => "(SELECT corp_category_id, SUBSTRING(jis_cd, 1, 2) 
								FROM m_target_areas 
								WHERE SUBSTRING(jis_cd, 1, 2) = '" . $valPrefCd . "' 
								GROUP BY corp_category_id, SUBSTRING(jis_cd, 1, 2))",
							'alias' => 'MTargetArea',
							'conditions' => array (
							'MTargetArea.corp_category_id = MCorpCategory.id',
						)
					),
					);
					
					$query = array(
						'conditions' => array(
							(array) $searchkey,
							'MCorp.del_flg' => 0,
									'MCorpCategory.category_id' => $valCategoryId,
							'NOT' => array(
								'MCorp.id' => $knowns,
							),
						),
						'fields' => $fields,
						'joins' => $joins,
						'recursive' => -1,
						'order' => array('MCorp.id'),
						//'limit' => 50,
					);
					
					$status = true;
					//$count = $this->MCorp->find('count', $query) + $count;

					$data = $this->MCorp->find('list', $query);
					if(empty($arrDiff) && !$boolDiff) {
						$arrDiff = $data;
						$boolDiff = true;
					}
					if(count($arrDiff) > 0) {
						$arrDiff1 =array();
						foreach($arrDiff as $keyDiff => $valDiff) {
							foreach($data as $keyData => $valData) {
								if($keyDiff == $keyData) {
									$arrDiff1[$keyDiff] = $valDiff;
								}
							}
						}
					}
					$arrDiff = $arrDiff1;
				}
				// 2017.11.10 ORANGE-578 h.miyake CHG(E)
			}
			// 2017.11.10 ORANGE-578 h.miyake ADD(S)
			$count = count($arrDiff);
			$i=0;
			$data = array();
			foreach($arrDiff as $key => $value) {
				if($i < 50) {
					$data[$key] =  $value;
					$i++;
				}
			}
			// 2017.11.10 ORANGE-578 h.miyake ADD(E)
			return json_encode(compact('status', 'count', 'data'));
		} catch (Exception $e) {
			$this->response->statusCode(500);
			return json_encode(array('message' => $e->getMessage()));
		}
	}

	// 2017.12.01 ORANGE-603 h.miyake ADD(S)
	/*
	 * 自動取次に登録済みの加盟店を取得
	 * $typeにより、取得フィールドを変更する
	 */
	private function _get_auto_corp_list($data, $type = null){
		$results = array();
		if($type == 'auto_corp'){
			//取得カラム
			$option['fields'] = array(
				'DISTINCT AutoCommissionCorp.id', 'AutoCommissionCorp.sort',
			);
		} else {
			//取得カラム
			$option['fields'] = array(
				'DISTINCT MCorp.id',
				'MCorp.corp_name',
				'AutoCommissionCorp.sort',
				'AutoCommissionCorp.process_type',
			);
			//並び順
			$option['order'][] = array(
				'AutoCommissionCorp.process_type' => 'asc',
				'AutoCommissionCorp.sort' => 'asc',
			);
		}

		//結合条件
		$option['joins'][] = array(
			'type' => 'INNER',
			'table' => 'm_corps',
			'alias' => 'MCorp',
			'conditions' => 'AutoCommissionCorp.corp_id = MCorp.id',
		);
		$option['joins'][] = array(
			'type' => 'INNER',
			'table' => 'm_categories',
			'alias' => 'MCategories',
			'conditions' => 'AutoCommissionCorp.category_id = MCategories.id',
		);
		$option['joins'][] = array(
			'type' => 'INNER',
			'table' => 'm_posts',
			'alias' => 'MPost',
			'conditions' => 'AutoCommissionCorp.jis_cd = MPost.jis_cd',
		);

		//検索条件
		$option['conditions'] = array(
			'AutoCommissionCorp.category_id' => $data['category_id'], //対象カテゴリー
			'MPost.address1' =>  Hash::get($this->prefecture_list, Hash::get($data, 'pref_cd')),
			'MCorp.del_flg' => 0,     //削除されていない加盟店
		);
		$results = $this->AutoCommissionCorp->find('all', $option);
		return $results;
	}
	// 2017.12.01 ORANGE-603 h.miyake ADD(E)

	/*
	 * 自動取次に登録済みの加盟店を取得
	 * $typeにより、取得フィールドを変更する
	 */
	private function _get_auto_corp_list1($data, $type = null){
		$results = array();
		// 2017.11.10 ORANGE-578 h.miyake ADD(S)
		foreach(Hash::get($data, 'AutoCommissionCorp.address1') as $key => $value) {
			$addres[] = Hash::get($this->prefecture_list, Hash::get($data, 'AutoCommissionCorp.address1.' . $key));
		}
		// 2017.11.10 ORANGE-578 h.miyake ADD(E)
		if($type == 'auto_corp'){
			//取得カラム
			$option['fields'] = array(
				'DISTINCT AutoCommissionCorp.id', 'AutoCommissionCorp.sort',
			);
		} else {
			// 2017/10/04 ORANGE-509 m-kawamoto CHG(S)
			//取得カラム
			$option['fields'] = array(
				'DISTINCT MCorp.id',
				'MCorp.corp_name',
				'AutoCommissionCorp.sort',
				'AutoCommissionCorp.process_type',
			);
			//並び順
			$option['order'][] = array(
				'AutoCommissionCorp.process_type' => 'asc',
				'AutoCommissionCorp.sort' => 'asc',
			);
			// 2017/10/04 ORANGE-509 m-kawamoto CHG(S)
		}
		
		//結合条件
		$option['joins'][] = array(
			'type' => 'INNER',
			'table' => 'm_corps',
			'alias' => 'MCorp',
			'conditions' => 'AutoCommissionCorp.corp_id = MCorp.id',
		);
		$option['joins'][] = array(
			'type' => 'INNER',
			'table' => 'm_categories',
			'alias' => 'MCategories',
			'conditions' => 'AutoCommissionCorp.category_id = MCategories.id',
		);
		$option['joins'][] = array(
			'type' => 'INNER',
			'table' => 'm_posts',
			'alias' => 'MPost',
			'conditions' => 'AutoCommissionCorp.jis_cd = MPost.jis_cd',
		);
		
		//検索条件
		// 2017.11.10 ORANGE-578 h.miyake CHG(S)
		foreach($addres as $keyAddres => $valAddres) {
			foreach($data['AutoCommissionCorp']['category_id'] as $keyCategoryId => $valCategoryId) {
		$option['conditions'] = array(
					array('AutoCommissionCorp.category_id' => $valCategoryId), //対象カテゴリー
					array('MPost.address1' =>  $valAddres),
					// array('MPost.address1' => 1),
					array('MCorp.del_flg' => 0),   //削除されていない加盟店
		);
				$results[] = $this->AutoCommissionCorp->find('all', $option);
			}
		}
		$arrDiff = array();
		foreach($results as $key => $value) {
			if(empty($arrDiff)) {
				$arrDiff = $value;
				continue;
			}
			$arrDiff1 = array();
			foreach($value as $key1 => $value1) {
				foreach($arrDiff as $keyDiff => $valDiff) {
					if($valDiff["MCorp"]["id"] == $value1["MCorp"]["id"] &&
							$valDiff["AutoCommissionCorp"]["process_type"] == $value1["AutoCommissionCorp"]["process_type"]) {
						$arrDiff1[] = $value1;
					}
				}
			}
			$arrDiff = $arrDiff1;
		}
		return $arrDiff;
		// 2017.11.10 ORANGE-578 h.miyake CHG(E)
	}
   
	/*
	 * 都道府県名からjis_cdを取得する
	 */
	private function _get_jiscd($data){
		$results = array();
		
		//取得カラム
		$option['fields'] = array(
			'DISTINCT MPost.jis_cd',
		);
		//検索条件
		// 2017.12.01 ORANGE-603 h.miyake CHG(S)
		//$option['conditions'] = array(
		//	'MPost.address1' =>	 Hash::get($this->prefecture_list, Hash::get($data, 'AutoCommissionCorp.address1')),	//都道府県絞り込み
		//);
		$address = Hash::get($data, 'AutoCommissionCorp.address1');
		if(!empty($address)) {
			$dataAddress1 = Hash::get($this->prefecture_list, Hash::get($data, 'AutoCommissionCorp.address1'));
		}
		else {
			$dataAddress1 = Hash::get($this->prefecture_list, Hash::get($data, 'pref_cd'));
		}
		$option['conditions'] = array(
				'MPost.address1' => $dataAddress1,          //都道府県絞り込み
		);
		// 2017.12.01 ORANGE-603 h.miyake CHG(E)
		$results = $this->MPost->find('all', $option);
		
		return $results;
	}
	
	/*
	 * 保存済の内容の削除を行う
	 */
	private function _delete_corp_list($data){
		$corp_list_result = array();
		$delete_corp_list_result = array();
		
		try{
			//削除対象のidを取得
			$corp_list_result = $this->_get_auto_corp_list($data, 'auto_corp');

			if(!empty($corp_list_result)){
				//削除の形式に整形 idのみの配列
				foreach ($corp_list_result as $key => $value) {
					$delete_corp_list_result[] = $value['AutoCommissionCorp']['id'];
				}
				
				//削除実施
				return $this->AutoCommissionCorp->deleteAll(array('AutoCommissionCorp.id' => $delete_corp_list_result), false);
			}
			
			//削除対象がないため、削除をスルー
			return true;
		} catch (Exception $e){
			//エラー
			$this->Session->setFlash(__('FailRegist', true), 'default', array('class' => 'error_inner'));
			$this->log('auto_commission_corp 保存エラー' . $e->getMessage());
			return false;
		}
	}
	
	/*
	 * 自動取次先加盟店を取得
	 * ジャンルで絞り込み
	 */
	public function get_auto_commission(){
		if (!$this->request->isAll(array('get', 'ajax'))) {
			throw new BadRequestException();
		}

		$genre_id = null;
		if(Hash::get($this->request->query, 'genre_id')){
			$genre_id = $this->request->query['genre_id'];
		}
		
		$this->autoRender = false;
		$this->layout = false;
		$this->response->type('json');
		$option = $this->set_sql_option($genre_id);
		
		//都道府県
		$pref_list = array();
		foreach ($this->prefecture_list as $key => $value) {
			$pref_list[] = array(
				'pref_id' => $key,
				'pref_name' => $value,
			);
		}
		
		try{
			//取得
			$auto_commission_corp_list = $this->AutoCommissionCorp->find('all', $option);  
			$genre_category_list = $this->_get_genre_category($genre_id);
			
			$status = true;
			$count = count($auto_commission_corp_list);
			return json_encode(compact('status', 'count', 'auto_commission_corp_list', 'genre_category_list', 'pref_list'));
		} catch (Exception $e) {
			$this->response->statusCode(500);
			return json_encode(array('message' => $e->getMessage()));
		}
	}
	
	/*
	 * 加盟店取得アクション
	 * ajaxのみ許可
	 * 登録済み全て
	 */
	public function get_auto_commission_all(){
		if (!$this->request->isAll(array('get', 'ajax'))) {
			throw new BadRequestException();
		}

		$this->autoRender = false;
		$this->layout = false;
		$this->response->type('json');
		
		//都道府県
		$pref_list = array();
		foreach ($this->prefecture_list as $key => $value) {
			$pref_list[] = array(
				'pref_id' => $key,
				'pref_name' => $value,
			);
		}
				
		//登録済みデータの取得
		$option = $this->set_sql_option();
		$auto_commission_corp_list = $this->AutoCommissionCorp->find('all', $option);
		
		//加盟店登録済みのジャンルとカテゴリ
		$category_id_list = array();
		foreach ($auto_commission_corp_list as $key => $value) {
			$category_id_list[$value['AutoCommissionCorp']['category_id']] = $value['AutoCommissionCorp']['category_id'];
		}
		$genre_category_list = $this->_get_genre_category(null, null, $category_id_list);
		
		$status = true;
		$count = count($auto_commission_corp_list);
		return json_encode(compact('status', 'count', 'auto_commission_corp_list', 'genre_category_list', 'pref_list'));
	}
	
	
	/* 
	 * SQL取得設定
	 * field、join、order
	 */
	public function set_sql_option($genre_id = null){
		$option = array();
		
		//バーチャルフィールドで、jis_cdの先頭2桁を指定する
		$this->AutoCommissionCorp->virtualFields['pref_cd'] = 'substring(AutoCommissionCorp.jis_cd from 1 for 2)';

		// 2017/10/04 ORANGE-509 m-kawamoto ADD(S) カラム process_type追加
		//自動取次先加盟店を取得
		//カラム
		$option['fields'] = array(
			'DISTINCT AutoCommissionCorp.corp_id',
			'AutoCommissionCorp.category_id',
			'AutoCommissionCorp.pref_cd',
			'AutoCommissionCorp.sort',
			'AutoCommissionCorp.process_type',
			'MCorp.corp_name',
			'MCorp.official_corp_name',
			'MCategories.genre_id',
			'MCategories.category_name',
		);
		// 2017/10/04 ORANGE-509 m-kawamoto ADD(E)
		
		//結合条件
		$option['joins'][] = array(
			'type' => 'INNER',
			'table' => 'm_corps',
			'alias' => 'MCorp',
			'conditions' => 'AutoCommissionCorp.corp_id = MCorp.id',
		);
		$option['joins'][] = array(
			'type' => 'INNER',
			'table' => 'm_categories',
			'alias' => 'MCategories',
			'conditions' => 'AutoCommissionCorp.category_id = MCategories.id',
		);
		
        // 2017/10/04 ORANGE-509 m-kawamoto ADD(S) カラム process_type追加
		//並び順
		$option['order'][] = array(
			'AutoCommissionCorp.pref_cd' => 'asc',
			'MCategories.genre_id' => 'asc',
			'AutoCommissionCorp.process_type' => 'desc',
			'AutoCommissionCorp.sort' => 'asc',
		);
        // 2017/10/04 ORANGE-509 m-kawamoto ADD(E)
		
		//ジャンルでの絞り込み条件
		if(!empty($genre_id)){
			$option['conditions'][] = array(
				'MCategories.genre_id' => $genre_id,
				'MCorp.del_flg' => 0,
			);
		}
		
		return $option;
	}

	/* 
	 * 自動取次に登録済みの加盟店をidとcorp_nameのリスト形式に変換
	 */
	private function _corp_list_convert($corp_list_result){
		$results = array();
		// 2017/10/04 ORANGE-509 m-kawamoto CHG(S)
		$results[1] = array(); //自動選定
		$results[2] = array(); //自動取次
		
		foreach ($corp_list_result as $key => $value) {
			$results[$value['AutoCommissionCorp']['process_type']][$value['MCorp']['id']] = $value['MCorp']['corp_name']; 
		}
		// 2017/10/04 ORANGE-509 m-kawamoto CHG(E)
		
		return $results;
	}
	
	/*
	 * ジャンルとカテゴリーの配列をジャンルのみのリスト形式に変換
	 */
	private function _genre_list_convert($corp_list_result){
		$results = array();

		foreach ($corp_list_result as $key => $value) {
			$results[Hash::get($value, 'MGenre.id')] = Hash::get($value, 'MGenre.genre_name');
		}
		
		return $results;
	}


	/*
	 * ジャンルとそれに紐づくカテゴリーを取得する
	 */
	private function _get_genre_category($genre_id = null, $category_id = null, $category_id_list = array()){
		$results = array();
		
		$option['fields'] = array(
			'MGenre.id',
			'MGenre.genre_name',
			'MCategories.id',
			'MCategories.category_name',
			'SelectGenres.select_type',
		);
		$option['joins'][] = array(
			'type' => 'INNER',
			'table' => 'm_categories',
			'alias' => 'MCategories',
			'conditions' => 'MGenre.id = MCategories.genre_id',
		);
		$option['joins'][] = array(
			'type' => 'LEFT',
			'table' => 'select_genres',
			'alias' => 'SelectGenres',
			'conditions' => 'SelectGenres.genre_id = MGenre.id',
		);

		
		$option['conditions'][] = array(
			'MGenre.valid_flg' => '1',
			'MCategories.disable_flg' => false,
		);
		
		if(!empty($genre_id)){
			$option['conditions'][] = array(
			   'MGenre.id' => $genre_id,
			);
		}
		if(!empty($category_id)){
			$option['conditions'][] = array(
			   'MCategories.id' => $category_id,
			);
		}
		
		if(!empty($category_id_list)){
			$option['conditions'][] = array(
			   'MCategories.id' => $category_id_list,
			);
		}
		
		$option['order'][] = array(
			'MGenre.genre_kana',
			'MCategories.id',
		);
		
		$results = $this->MGenre->find('all', $option);
		
		return $results;
	}
	
	/*
	 * 2017/11/30 h.miyake ORANGE-603 ADD(S)
	 * 取次先加盟店追加画面
	 */
	public function add() {
		// 初期値
		$javascriptData = "";
		$javascriptCorpList = "var dataCorpList;" . "\n";
		//post送信判定
		if ($this->request->is('post')){
			//データ保持
			$this->data = $this->request->data;
			if($this->data["category"] == 'genreSelect') {
				if(!empty($this->data["AutoCommissionCorp"]["category_id"])) {
					$javascriptData .= "var data1 = [];" . "\n";
					foreach($this->data["AutoCommissionCorp"]["category_id"] as $key => $value) {
						$javascriptData .= "data1[" . $key . "] = " . $value . ";" . "\n";
					}
				}
			} elseif (Hash::get($this->request->data, 'searchkey')){
				$mes = "";
				if(!empty($this->data["AutoCommissionCorp"]["corp_list"])) {
					$data1["corp_id"] = $this->data["AutoCommissionCorp"]["corp_list"];
				}
				else {
					$data1["corp_id"] = "";
					$mes .= "加盟店・";
				}
				if(!empty($this->data["AutoCommissionCorp"]["category_id"][0])) {
					$data1["category_id1"] = $this->data["AutoCommissionCorp"]["category_id"][0];
				}
				else {
					$data1["category_id1"] = "";
					$mes .= "カテゴリー・";
				}
				if(!empty($this->data["AutoCommissionCorp"]["address1"][0])) {
					$data1["jis_cd"] = $this->data["AutoCommissionCorp"]["address1"][0];
				}
				else {
					$data1["jis_cd"] = "";
					$mes .= "都道府県・";
				}
				if(!empty($this->data["AutoCommissionCorp"]["auto_select"])) {
					$data1["process_type"] = $this->data["AutoCommissionCorp"]["auto_select"];
				}
				else {
					$data1["process_type"] = "";
					$mes .= "自動取次/自動選定・";
				}

				$this->AutoCommissionCorp->set($data1);
				// エラーチェック
				if (!$this->AutoCommissionCorp->validates()) {
					// 加盟店再検索
					switch (Hash::get($this->request->data, 'searchkey')) {
						case 'search_corp_name':
							$patterns = preg_split('/[\s]/', str_replace('　', ' ', Hash::get($this->request->data, 'search_corp_name')), -1, PREG_SPLIT_NO_EMPTY);
							$searchkey = array();
							array_walk($patterns, function($value) use(&$searchkey) {
								if (mb_substr($value, 0, 1) === '-') {
									$searchkey[] = array('MCorp.corp_name NOT LIKE' => '%' . mb_substr($value, 1) . '%');
								} 
								else {
									$searchkey[] = array('MCorp.corp_name LIKE' => '%' . $value . '%');
								}
							});
							break;
						case 'search_corp_id':
							$patterns = Hash::filter(
							preg_split('/[\s\n,]/', Hash::get($this->request->data, 'search_corp_id'), -1, PREG_SPLIT_NO_EMPTY)
							, function($part) {
								return is_numeric($part);
							});
							$searchkey = array('MCorp.id' => (array) $patterns);
							break;
					}
					$fields = array('MCorp.id', 'MCorp.corp_name');

					$query = array(
						'conditions' => array(
							(array) $searchkey,
							'MCorp.del_flg' => 0,
						),
						'fields' => $fields,
						'recursive' => -1,
						'order' => array('MCorp.id'),
						'limit' => 50,
					);

					$status = true;
					$count = $this->MCorp->find('count', $query);
					$corp_list = $this->MCorp->find('list', $query);

					$javascriptCorpList .= "dataCorpList = {" . "\n";
					foreach($corp_list as $keyCorpList => $valCorpList) {
						$javascriptCorpList .= $keyCorpList . " : '" . $valCorpList . "',";
					}
					$javascriptCorpList .= "}" . "\n";
					$this->set('javascriptCorpList', $javascriptCorpList);

					if(!empty($this->data["AutoCommissionCorp"]["category_id"])) {
						$javascriptData .= "var data1 = [];" . "\n";
						foreach($this->data["AutoCommissionCorp"]["category_id"] as $key => $value) {
							$javascriptData .= "data1[" . $key . "] = " . $value . ";" . "\n";
						}
					}
					$this->set("javascriptData", $javascriptData);

					$mes = rtrim($mes, '・');
					$mes .= "は未入力です。";
					//$this->Session->setFlash(__('InputError', true), 'default', array('class' => 'error_inner'));
					$this->Session->setFlash(__('入力項目を確認してください。<br>' . $mes, true), 'default', array('class' => 'error_inner'));
					return false;
				}

				//保存済の内容の削除を行う
				foreach($this->data["AutoCommissionCorp"]["address1"] as $keyAddressCommission => $valAddressCommission) {
					foreach($this->data["AutoCommissionCorp"]["category_id"] as $keyCategoryIdCommission =>$valCategoryIdCommission) {
						$this->AutoCommissionCorp->deleteAll(array('corp_id' => $this->data["AutoCommissionCorp"]["corp_list"], 'category_id' => $valCategoryIdCommission, 'jis_cd LIKE ' => $valAddressCommission . "%"));
					}
				}

				//取得したjis_cdと加盟店でjis_cd分データを作成
				$save_data = array();
				foreach($this->data["AutoCommissionCorp"]["address1"] as $keyAddressCommission => $valAddressCommission) {
					//都道府県からjis_cdを取得
					$Prefecture["AutoCommissionCorp"]["address1"] = $valAddressCommission;
					$jiscd_result = $this->_get_jiscd($Prefecture);
					foreach($this->data["AutoCommissionCorp"]["category_id"] as $keyCategoryIdCommission =>$valCategoryIdCommission) {
						foreach ($jiscd_result as $key => $value) {
							$save_data['AutoCommissionCorp'][] = array(
								'corp_id' => Hash::get($this->data, 'AutoCommissionCorp.corp_list'),
								'category_id' => $valCategoryIdCommission,
//								'sort' => $corp_id_key,
								'sort' => 1,
								'jis_cd' => $value['MPost']['jis_cd'],
								'created_user_id' => $this->User['user_id'],
								'modified_user_id' => $this->User['user_id'],
//								'process_type' => 2, // 処理種別 2:自動取次
								'process_type' => Hash::get($this->data, 'AutoCommissionCorp.auto_select'),
							);
						}
					}
				}


				if(isset($save_data['AutoCommissionCorp'])) {
					//保存
					try{
						$this->AutoCommissionCorp->begin();
						$this->AutoCommissionCorp->saveAll($save_data['AutoCommissionCorp'], array('validate' => false, 'atomic' => false));
						$this->AutoCommissionCorp->commit();
						$this->Session->setFlash(__('Updated', true), 'default', array('class' => 'message_inner'));
					} catch (Exception $e) {
						//エラー
						$this->AutoCommissionCorp->rollback();
						$this->Session->setFlash(__('FailRegist', true), 'default', array('class' => 'error_inner'));
						$this->log('auto_commission_corp 保存エラー' . $e->getMessage());
						$this->redirect('/auto_commission_corp/');
					}
				}
				//リダイレクト
				$this->redirect('/auto_commission_corp/');
			}
		}
	$this->set('javascriptCorpList', $javascriptCorpList);
		$this->set("javascriptData", $javascriptData);
	}
	
	/*
	 * 加盟店取得アクション
	 * ajaxのみ許可
	 */
	public function get_corp_list_add() {
		if (!$this->request->isAll(array('post', 'ajax'))) {
			throw new BadRequestException();
		}
		$this->autoRender = false;
		$this->response->type('json');
		try{
			switch (Hash::get($this->request->data, 'searchkey')) {
				case 'search_corp_name':
					$patterns = preg_split('/[\s]/', str_replace('　', ' ', Hash::get($this->request->data, 'search_corp_name')), -1, PREG_SPLIT_NO_EMPTY);
					$searchkey = array();
					array_walk($patterns, function($value) use(&$searchkey) {
						if (mb_substr($value, 0, 1) === '-') {
							$searchkey[] = array('MCorp.corp_name NOT LIKE' => '%' . mb_substr($value, 1) . '%');
						} else {
							$searchkey[] = array('MCorp.corp_name LIKE' => '%' . $value . '%');
						}
					});
					break;
				case 'search_corp_id':
					$patterns = Hash::filter(
						preg_split('/[\s\n,]/', Hash::get($this->request->data, 'search_corp_id'), -1, PREG_SPLIT_NO_EMPTY)
						, function($part) {
						return is_numeric($part);
					});
					$searchkey = array('MCorp.id' => (array) $patterns);
					break;
			}
			$fields = array('MCorp.id', 'MCorp.corp_name');
			$query = array(
				'conditions' => array(
					(array) $searchkey,
					'MCorp.del_flg' => 0,
				),
				'fields' => $fields,
				'recursive' => -1,
				'order' => array('MCorp.id'),
				'limit' => 50,
			);

					$status = true;
					$count = $this->MCorp->find('count', $query);

					$data = $this->MCorp->find('list', $query);

			return json_encode(compact('status', 'count', 'data'));
		} 
		catch (Exception $e) {
			$this->response->statusCode(500);
			return json_encode(array('message' => $e->getMessage()));
		}
	}
	// 2017/11/30 h.miyake ORANGE-603 ADD(E)
}