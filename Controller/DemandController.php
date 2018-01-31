<?php
App::uses('AppController', 'Controller');

class DemandController extends AppController {

	public $name = 'Demand';
	public $helpers = array('Credit');
// 2016.11.17 murata.s ORANGE-185 CHG(S)
// 2017.09.27 ORANGE-420 m.kawamoto CHG(S)
	public $components = array('Print', 'AutoCommissionSelect', 'AuctionInfoUtil', 'Commission');
// 2017.09.27 ORANGE-420 m.kawamoto CHG(E)
// 2016.11.17 murata.s ORANGE-185 CHG(E)
	// 2016.05.17 murata.s ORANGE-1210 CHG(S)
	//public $uses = array('DemandInfo', 'DemandInquiryAnswer', 'CommissionInfo', 'BillInfo', 'DemandCorrespond', 'MAnswer', 'MUser', 'MSite', 'MInquiry', 'MCorpCategory', 'MTaxRate', 'MSiteGenre', 'MSiteCategory', 'MCorp', 'MCategory', 'MGenre', 'AutoSelectSetting', 'AutoSelectCorp', 'DeviceInfo', 'VisitTime', 'AuctionInfo', 'MPost', 'MTime');
	// 2016.08.03 iwai ORANGE-135 CHG S
	// murata.s ORANGE-537 CHG(S)
        // 2017/06/08  ichino ORANGE-420 ADD start AutoCommissionCorp、MCorpNewYear追加
        // 2017/07/11 ichino ORANGE-459 ADD start MItem追加
	public $uses = array('DemandInfo', 'DemandInquiryAnswer', 'CommissionInfo', 'BillInfo', 'DemandCorrespond', 'MAnswer', 'MUser', 'MSite', 'MInquiry', 'MCorpCategory', 'MTaxRate', 
            'MSiteGenre', 'MSiteCategory', 'MCorp', 'MCategory', 'MGenre', 'AutoSelectSetting', 'AutoSelectCorp', 'DeviceInfo', 'VisitTime', 'AuctionInfo', 'MPost', 'MTime', 'CorpAgreement', 
            'MCommissionType', 'SelectGenrePrefecture', 'SelectGenre', 'DemandAttachedFile', 'AutoCommissionCorp', 'MCorpNewYear', 'MItem', 'AutoCallItem');
        // 2017/07/11 ichino ORANGE-459 ADD end
        // 2017/06/08  ichino ORANGE-420 ADD end
	// murata.s ORANGE-357 CHG(E)
	// 2016.08.03 iwai ORANGE-135 CHG E
	// 2016.05.17 murata.s ORANGE-1210 CHG(E)
	public function beforeFilter(){
		parent::beforeFilter();
                
                // 2017/07/11 ichino ORANGE-459 ADD start
                //休業日をm_itemsから取得する
                $vacation = $this->MItem->getList('長期休業日');
                
                //viewへ渡す
                $this->set("vacation", $vacation);
                // 2017/07/11 ichino ORANGE-459 ADD end
              
	}

	public function index() {
		return $this->redirect('/demand/detail');
	}

	/**
	 * 詳細画面
	 *
	 * @param unknown_type $id
	 */
	public function detail($id = null, $sub_flg = null) {
		$regist_enabled = true;		// 案件状況活性制御
		$bid_situation = false;		// 入札状況ボタン
		$again_enabled = false;		//再表示ボタン活性制御
		$this->set('again_enabled', $again_enabled);//再表示ボタン活性制御
		$clear_enabled = false;
		$this->set('clear_enabled', $clear_enabled);//クリア
                $corp_data = array();
                $demand_attached_file_cert = array();
                
                // 企業情報を取得
                $corp_data = $this->MCorp->find ( 'first', array (
                    'fields' => '*',
                    'conditions' => array (
                        'MCorp.id' => $id
                    ),
                    'order' => array (
                        'MCorp.id' => 'asc'
                    )
                ));
                // 案件ファイルを取得
		$demand_attached_file_cert = $this->DemandAttachedFile->find('all',array(
                    'fields'=>'*',
                    'conditions' => array(
			'DemandAttachedFile.demand_id' => $id
                    ),
                    'order' => array (
                        'DemandAttachedFile.id' => 'asc'
                    )
		));
                
                $this->set('corp_data' ,$corp_data);
                $this->set('demand_attached_file_cert' ,$demand_attached_file_cert);

		// 案件IDがある場合
		if (!empty($id)) {
			// 案件データの取得
			$this->data = $this->__set_demand($id);

			if(array_key_exists('MInquiry', $this->data)){
				// ヒアリング回答項目データの取得
				$answer_list = array();
				for($i = 0; $i < count($this->data['MInquiry']); $i++) {
					$answer_list[$i] = $this->__set_answer($this->data['MInquiry'][$i]['id']);
				}
				$this->data += array('MAnswer' => $answer_list);
			}

			// 2015.09.28 Inokuchi(S)
			$AuctionInfo_data = $this->AuctionInfo->findAllByDemandId($id);
			if(!empty($AuctionInfo_data)){
				$bid_situation = true;
			}
			// 2015.09.28 Inokuchi(E)

			// ORANGE-1019 【案件管理】取次先情報の「取次時手数料率」の登録を自動化 Add Start
			// kishimoto@tobila
			if(empty($this->data['DemandInfo']['category_id']) == false) {
				$category_default_fee = $this->MCategory->getDefault_fee($this->data['DemandInfo']['category_id']);
				$this->set('category_default_fee', $category_default_fee);
			}
			// ORANGE-1019 【案件管理】取次先情報の「取次時手数料率」の登録を自動化 Add End

			// ORANGE-140 iwai 2016.08.17 S
			$identically_cnt = $this->__check_identically_customer($this->data['DemandInfo']['customer_tel']);

			//ORANGE-140 ADD S;
			if(!empty($identically_cnt) && $identically_cnt > 1) {
				$this->set('identically_customer', 1);
			}
			// ORANGE-140 iwai 2016.08.17 E

// 2016.12.15 murata.s ORANGE-283 ADD(S)
			$selection_system_list = $this->__get_selection_system_list($this->data['DemandInfo']['genre_id'], $this->data['DemandInfo']['address1']);
			$this->set('selection_system_list', $selection_system_list);
// 2016.12.15 murata.s ORANGE-283 ADD(E)
		}


		$this->set("over_limit_time" , $this->_getOverLimitTime());

		// ドロップダウン用リストの取得
		$this->set("site_list" , $this->MSite->getList());		// サイト一覧
		$this->set("user_list" , $this->MUser->dropDownUser());		// ユーザー一覧
		// カテゴリ一覧、【クロスセル】元カテゴリ一覧
		if(array_key_exists('DemandInfo', $this->data)){
			// TODO: ジャンルとサイトの紐付きの見直し
// murata.s ORANGE-480 CHG(S)
			// 2014.12.13 h.hara
			//$this->set("genre_list" , $this->MSiteCategory->getCategoriesBySite($this->data['DemandInfo']['site_id']));						// ジャンル一覧
			$this->set("genre_list" , $this->MSiteGenre->getGenreBySiteStHide($this->data['DemandInfo']['site_id']));
// murata.s ORANGE-480 CHG(E)

			//ランク一覧
			$this->set("rank_list" , $this->MSiteGenre->getGenreByRank($this->data['DemandInfo']['site_id']));


			// ジャンル一覧
			// 2014.12.26 h.hara
			//$this->set('category_list', $this->MSiteCategory->getCategoriesBySite($this->data['DemandInfo']['site_id']));					// カテゴリ一覧
			// 2015.1.5 inokuchi
// murata.s ORANGE-480 CHG(S)
// 2016.07.14 murata.s ORANGE-121 CHG(S)
			$this->set('category_list', $this->MCategory->getListStHide($this->data['DemandInfo']['genre_id'], !empty($id)));									// カテゴリ一覧
// 2016.07.14 murata.s ORANGE-121 CHG(E)
// murata.s ORANGE-480 CHG(E)

			// 2015.4.7 n.kai ADD start
			if(!empty($this->data['DemandInfo']['cross_sell_source_site'])){
				$this->set('cross_sell_genre_list', $this->MSiteGenre->getGenreBySite($this->data['DemandInfo']['cross_sell_source_site']));
			} else {
				$this->set('cross_sell_genre_list', array());
			}
			// 2015.4.7 n.kai ADD end
			if(!empty($this->data['DemandInfo']['cross_sell_source_site'])){
				$this->set('cross_sell_category_list', $this->MSiteCategory->getCategoriesBySite($this->data['DemandInfo']['cross_sell_source_site']));
			} else {
				$this->set('cross_sell_category_list', array());
			}

		}else{
			$this->set('genre_list', array());
			$this->set('category_list', array());
			// 2015.4.7 n.kai ADD start
			$this->set('cross_sell_genre_list', array());
			// 2015.4.7 n.kai ADD end
			$this->set('cross_sell_category_list', array());
// 2016.12.15 murata.s ORANGE-283 ADD(S)
			$this->set('selection_system_list', Util::getDivList('selection_type'));
// 2016.12.15 murata.s ORANGE-283 ADD(E)
		}

		// 取次詳細からのサブウィンドウの為の処理
		if(!empty($sub_flg)){
			$this->layout = 'subwin';
			$this->render('sub_detail');
		}

		$this->set('regist_enabled', $regist_enabled);

		// 2015.09.28 Inokuchi(S)
		$this->set('bid_situation', $bid_situation);
		// 2015.09.28 Inokuchi(E)

	}

	/**
	 * 対象の案件状況場合、現時刻が取次完了リミット日時を超えているか判定
	 * 取次完了リミット日時 = 受信日時 ＋ ジャンルごとに設定したある時間
	 *
	 * @return int
	 */
	protected function _getOverLimitTime() {
		if (!isset($this->data['DemandInfo']['demand_status']) || !in_array($this->data['DemandInfo']['demand_status'], array(1,2,3), true)) {
			return 0;
		}

		$genre = $this->MGenre->findById($this->data['DemandInfo']['genre_id']);

		if ($genre['MGenre']['commission_limit_time'] < 1) {
			return 0;
		}

		$limit_timestamp = strtotime($this->data['DemandInfo']['receive_datetime']. '+ '. $genre['MGenre']['commission_limit_time']. ' minute');

		$overLimitTime = strtotime("now") - $limit_timestamp;

		return ($overLimitTime > 0) ? $overLimitTime : 0;
	}

	/**
	 * CTI連携による検索画面からのリダイレクト用
	 *
	 * @param unknown_type $customer_tel
	 * @param unknown_type $site_tel
	 */
	public function cti($customer_tel = null, $site_tel = null){

		$regist_enabled = true;		// 案件状況活性制御
		$this->set('regist_enabled', $regist_enabled);
		$again_enabled = false;		//再表示ボタン活性制御
		$this->set('again_enabled', $again_enabled);//再表示ボタン活性制御
		$clear_enabled = false;
		$this->set('clear_enabled', $clear_enabled);//クリア
		// *****CTI連携処理*****
		// 引数がない場合

//		if(empty($customer_tel) || empty($site_tel)){
		if(empty($site_tel)){
			// 2015.5.17 n.kai MOD start
			// 非通知着信(customer_telがNULL)の場合、案件管理新規登録画面を表示する(ORANGE-460)
			//$this->redirect('/demand');
			$this->redirect('/demand/detail');
			// 2015.5.17 n.kai MOD end
		}

		// 2016.02.20 h.hanaki ORANGE-1165
		$customer_tel_save = '';

		if ($customer_tel == '0') {
			$customer_tel_save= '0';
			$customer_tel= '';
		}

		if ($customer_tel == '非通知') {
			$customer_tel_save= '非通知';
			$customer_tel= '';
		}

		// 案件データの既定値を取得
		$this->data = $this->__set_pre_demand($customer_tel, $site_tel);

		//ORANGE-140 ADD S
		$this->request->data['DemandInfo']['commission_limitover_time'] = 0;
		if(!empty($this->request->data['DemandInfo']['demand_status']) && $this->request->data['DemandInfo']['demand_status'] != 0){
			$this->set('cti_customer_tel', $customer_tel);
		}
		//ORANGE-140 ADD E

//		if ($this->data['DemandInfo']['customer_tel'] == '非通知') {
//			$this->request->data['DemandInfo']['customer_tel'] = '';
//		}
		// 2016.02.20 h.hanaki ORANGE-1165
		$this->request->data['DemandInfo']['demand_status'] = 0;
		if ($customer_tel_save == '非通知'){
			$this->request->data['DemandInfo']['customer_tel'] = '非通知';
		}
		// ドロップダウン用リストの取得
		$this->set("site_list" , $this->MSite->getList());																// サイト一覧
		// TODO: ジャンルとサイトの紐付きの見直し
// murata.s ORANGE-480 CHG(S)
		// 2014.12.13 h.hara
		//$this->set("genre_list" , $this->MSiteCategory->getCategoriesBySite($this->data['DemandInfo']['site_id']));		// ジャンル一覧
		$this->set("genre_list" , $this->MSiteGenre->getGenreBySiteStHide($this->data['DemandInfo']['site_id']));				// ジャンル一覧
// murata.s ORANGE-480 CHG(E)
		// 2015.1.5 inokuchi
		//$this->set('category_list', $this->MSiteCategory->getCategoriesBySite($this->data['DemandInfo']['site_id']));	// カテゴリ一覧
//		$this->set('category_list', $this->MCategory->getList($this->data['DemandInfo']['genre_id']));					// カテゴリ一覧
//		$this->set('category_list', $this->MCategory->getList(key($this->MSiteGenre->getGenreBySite($this->data['DemandInfo']['site_id']))));					// カテゴリ一覧
// murata.s ORANGE-480 CHG(S)
		$this->set('category_list', $this->MCategory->getListStHide());
// murata.s ORANGE-480 CHG(E)

		// 2015.4.7 n.kai ADD start
		$this->set('cross_sell_genre_list', array());																	// 【クロスセル】元ジャンル一覧
		// 2015.4.7 n.kai ADD end
		$this->set('cross_sell_category_list', array());																// 【クロスセル】元カテゴリ一覧
		$this->set("user_list" , $this->MUser->dropDownUser());															// ユーザー一覧

		$this->render('detail');
	}

	/**
	 * 案件コピー
	 *
	 * @param unknown_type $id
	 */
	public function copy($id = null){

		// 2015.5.7 n.kai ADD start
		$regist_enabled = true;		// 案件状況活性制御
		$this->set('regist_enabled', $regist_enabled);
		$again_enabled = false;
		$this->set('again_enabled', $again_enabled);//再表示ボタン活性制御
		$clear_enabled = true;
		$this->set('clear_enabled', $clear_enabled);//クリア
		// 2015.5.7 n.kai ADD end

		$data = array();

		if (!empty($id)) {
			// 案件IDがある場合
			// コピー元案件データの取得
			$data = $this->__set_demand($id);

			if(array_key_exists('MInquiry', $data)){
				// ヒアリング回答項目データの取得
				$answer_list = array();
				for($i = 0; $i < count($data['MInquiry']); $i++) {
					$answer_list[$i] = $this->__set_answer($data['MInquiry'][$i]['id']);
				}
				$data += array('MAnswer' => $answer_list);
			}

			// 案件IDを除去
			if(array_key_exists('DemandInfo', $data) && array_key_exists('id', $data['DemandInfo'])){
				unset($data['DemandInfo']['id']);
			}
			// 直前の選定方式クリア
			unset($data['DemandInfo']['selection_system_before']);
			// 取次データ・紹介データはコピーしない
			unset($data['CommissionInfo']);
			unset($data['IntroduceInfo']);
			// 2015.10.30 n.kai ADD start ORANGE-933 案件状況を「未選定」
			$data['DemandInfo']['demand_status'] = 1;
			// 2015.10.30 n.kai ADD end ORANGE-933 案件状況を「未選定」
			// murata.s ORANGE-486 ADD(S)
			$data['DemandInfo']['demand_status_before'] = $data['DemandInfo']['demand_status'];
			// murata.s ORANGE-486 ADD(E)
			// murata.s ORANGE-502 ADD(S)
			unset($data['DemandInfo']['auction']);
			// murata.s ORANGE-502 ADD(E)

			// 2017.01.26 murata.s ORANGE-317 ADD(S)
			$selection_system_list = $this->__get_selection_system_list($data['DemandInfo']['genre_id'], $data['DemandInfo']['address1']);
			$this->set('selection_system_list', $selection_system_list);
			// 2017.01.26 murata.s ORANGE-317 ADD(E)

		} else {
			// データが無い場合
			return $this->redirect('/demand/detail');
			return;
		}

		$this->data = $data;


		// ドロップダウン用リストの取得
		$this->set("site_list" , $this->MSite->getList());			// サイト一覧

		$this->set("user_list" , $this->MUser->dropDownUser());		// ユーザー一覧
		// カテゴリ一覧、【クロスセル】元カテゴリ一覧
		if(array_key_exists('DemandInfo', $this->data)){
			// TODO: ジャンルとサイトの紐付きの見直し
// murata.s ORANGE-480 CHG(S)
			// 2014.12.13 h.hara
			//$this->set('genre_list', $this->MSiteCategory->getCategoriesBySite($this->data['DemandInfo']['site_id']));				// ジャンル一覧
			$this->set("genre_list" , $this->MSiteGenre->getGenreBySiteStHide($this->data['DemandInfo']['site_id']));						// ジャンル一覧
// murata.s ORANGE-480 CHG(E)
// murata.s ORANGE-480 CHG(S)
			// 2015.1.5 inokuchi
			//$this->set('category_list', $this->MSiteCategory->getCategoriesBySite($this->data['DemandInfo']['site_id']));			// カテゴリ一覧
			$this->set('category_list', $this->MCategory->getListStHide($this->data['DemandInfo']['genre_id']));							// カテゴリ一覧
// murata.s ORANGE-480 CHG(E)
			// 2015.4.7 n.kai ADD start
			$this->set('cross_sell_genre_list', $this->MSiteGenre->getGenreBySite($this->data['DemandInfo']['cross_sell_source_site']));
			// 2015.4.7 n.kai ADD end
			$this->set('cross_sell_category_list', $this->MSiteCategory->getCategoriesBySite($this->data['DemandInfo']['cross_sell_source_site']));
		}else{
			$this->set('genre_list', array());
			$this->set('category_list', array());
			// 2015.4.7 n.kai ADD start
			$this->set('cross_sell_genre_list', array());
			// 2015.4.7 n.kai ADD end
			$this->set('cross_sell_category_list', array());
		}

		$this->render('detail');

	}

	/**
	 * クロスセル専用
	 *
	 * @param unknown_type $id
	 */
	public function cross($id = null){

		// 2015.5.7 n.kai ADD start
		$regist_enabled = false;		// 案件状況活性制御
		$this->set('regist_enabled', $regist_enabled);
		// 2015.5.7 n.kai ADD end
		$clear_enabled = true;
		$this->set('clear_enabled', $clear_enabled);//クリア
		$again_enabled = false;
		$this->set('again_enabled', $again_enabled);//再表示ボタン活性制御

		$data = array();

		if (!empty($id)) {
			// 案件IDがある場合
			// コピー元案件データの取得
			$data = $this->__set_demand($id);

			if(array_key_exists('MInquiry', $data)){
				// ヒアリング回答項目データの取得
				$answer_list = array();
				for($i = 0; $i < count($data['MInquiry']); $i++) {
					$answer_list[$i] = $this->__set_answer($data['MInquiry'][$i]['id']);
				}
				$data += array('MAnswer' => $answer_list);
			}

			//サイト名を元サイトに代入
			$data['DemandInfo']['cross_sell_source_site'] = $data['DemandInfo']['site_id'];
			//サイト名をクロスセル案件を指定 861;
			$data['DemandInfo']['site_id'] = $this->__set_site_id('クロスセル案件');
			//ジャンルを元ジャンルに代入
			$data['DemandInfo']['cross_sell_source_genre'] = $data['DemandInfo']['genre_id'];
			$data['DemandInfo']['genre_id'] = '0';
			$data['DemandInfo']['category_id'] = '0';
			//案件IDを元案件番号に代入
			$data['DemandInfo']['source_demand_id'] = $data['DemandInfo']['id'];
			//案件番号を表示URL、同一顧客案件URLに代入
			$data['DemandInfo']['same_customer_demand_url'] = Router::url('/demand/detail/' .$data['DemandInfo']['id'], true);
			$data['DemandInfo']['contents'] = '';
			//ご相談内容にテキスト挿入
//			if (!in_array('クロスセル案件番号：ジャンル：同じ加盟店を希望or違う加盟店でも良い',$data)) {
//				$data['DemandInfo']['contents'] = $data['DemandInfo']['contents'] . 'クロスセル案件番号：ジャンル：同じ加盟店を希望or違う加盟店でも良い';
//			}
			// 案件IDを除去
			if(array_key_exists('DemandInfo', $data) && array_key_exists('id', $data['DemandInfo'])){
				unset($data['DemandInfo']['id']);
			}
			// 直前の選定方式クリア
			unset($data['DemandInfo']['selection_system_before']);
			// 取次データ・紹介データはコピーしない
			unset($data['CommissionInfo']);
			unset($data['IntroduceInfo']);
			// 2015.10.30 n.kai ADD start ORANGE-933 案件状況を「未選定」
			$data['DemandInfo']['demand_status'] = 1;
			// 2015.10.30 n.kai ADD end ORANGE-933 案件状況を「未選定」
			// murata.s ORANGE-486 ADD(S)
			$data['DemandInfo']['demand_status_before'] = $data['DemandInfo']['demand_status'];
			// murata.s ORANGE-486 ADD(E)
			// murata.s ORANGE-502 ADD(S)
			unset($data['DemandInfo']['auction']);
			// murata.s ORANGE-502 ADD(E)

			// 2017.01.26 murata.s ORANGE-317 ADD(S)
			$selection_system_list = $this->__get_selection_system_list($data['DemandInfo']['genre_id'], $data['DemandInfo']['address1']);
			$this->set('selection_system_list', $selection_system_list);
			// 2017.01.26 murata.s ORANGE-317 ADD(E)

		} else {
			// データが無い場合
			return $this->redirect('/demand/detail');
			return;
		}

		$this->data = $data;

		// ドロップダウン用リストの取得
		$this->set("site_list" , $this->MSite->getList());			// サイト一覧

		$this->set("user_list" , $this->MUser->dropDownUser());		// ユーザー一覧
		// カテゴリ一覧、【クロスセル】元カテゴリ一覧
		if(array_key_exists('DemandInfo', $this->data)){
			// TODO: ジャンルとサイトの紐付きの見直し
// murata.s ORANGE-480 CHG(S)
			// 2014.12.13 h.hara
			//$this->set('genre_list', $this->MSiteCategory->getCategoriesBySite($this->data['DemandInfo']['site_id']));				// ジャンル一覧
			$this->set("genre_list" , $this->MSiteGenre->getGenreBySiteStHide($this->data['DemandInfo']['site_id']));						// ジャンル一覧
// murata.s ORANGE-480 CHG(E)
// murata.s ORANGE-480 CHG(S)
			// 2015.1.5 inokuchi
			//$this->set('category_list', $this->MSiteCategory->getCategoriesBySite($this->data['DemandInfo']['site_id']));			// カテゴリ一覧
			$this->set('category_list', $this->MCategory->getListStHide($this->data['DemandInfo']['genre_id']));							// カテゴリ一覧
// murata.s ORANGE-480 CHG(E)
			// 2015.4.7 n.kai ADD start
			$this->set('cross_sell_genre_list', $this->MSiteGenre->getGenreBySite($this->data['DemandInfo']['cross_sell_source_site']));
			// 2015.4.7 n.kai ADD end
			$this->set('cross_sell_category_list', $this->MSiteCategory->getCategoriesBySite($this->data['DemandInfo']['cross_sell_source_site']));
		}else{
			$this->set('genre_list', array());
			$this->set('category_list', array());
			// 2015.4.7 n.kai ADD start
			$this->set('cross_sell_genre_list', array());
			// 2015.4.7 n.kai ADD end
			$this->set('cross_sell_category_list', array());
		}

		$this->render('detail');

	}

	/**
	 * 登録
	 *
	 */
	public function regist(){

		$regist_enabled = true;		// 案件状況活性制御
		$again_enabled = false;//再表示ボタン活性制御
		// 2015.04.10 h.hara(S)
		// 情報送信済みの場合、管理者以外は登録ボタン非表示
// 		$user = $this->Auth->user();
// 		if ($this->data['DemandInfo']['demand_status'] == Util::getDivValue('demand_status', 'information_sent') && !($user['auth'] == 'system' || $user['auth'] == 'admin')) {
// 			$regist_enabled = false;
// 		}
		// 2015.04.10 h.hara(E)

		$clear_enabled = false;
		$this->set('clear_enabled', $clear_enabled);//クリア

		// 入力値取得
		$data = $this->request->data;

		//2017/06/08  ichino ORANGE-420 ADD start 対象案件が「新規登録」「未選定」に都道府県とカテゴリに一致する加盟店があれば、選定先に追加する
		//ワンタッチ失注でない場合

		// 案件IDの取得
		$demand_id = (array_key_exists('id', $data['DemandInfo'])) ? $data['DemandInfo']['id'] : $this->DemandInfo->id;

		//取次上限数格納用変数
		$auto_commission_selection_limit = 0;

		//地域×カテゴリで加盟店を指定したという選定フラグを初期化する
		$data['DemandInfo']['do_auto_selection_category'] = 0;
		// 2017/12/07 h.miyake ORANGE-602 ADD(S)
		// クロスセルサイト判定
		$resultCrossSiteFlg = $this->MSite->getCrossSiteFlg(1);
		// 2017/12/07 h.miyake ORANGE-602 ADD(E)
		if( //empty($demand_id) 
		           $data['DemandInfo']['demand_status'] == Util::getDivValue('demand_status', 'no_selection')
		        && (!isset($data['quick_order_fail']) &&  $data["DemandInfo"]["quick_order_fail_reason"] == "")
		        // 2017/12/07 h.miyake ORANGE-602 ADD(S)
		        && (!in_array($data["DemandInfo"]["site_id"], $resultCrossSiteFlg))
		        && (!in_array($data["DemandInfo"]["site_id"], Configure::read('arrSiteId')))
		        // 2017/12/07 h.miyake ORANGE-602 ADD(E)
		        ){
		    
		    //保存用データに使用
		    $user = $this->Auth->user();
		    
		    //選定対象の取得
		    $jis_cd = $this->MPost->getTargetJisCd(Hash::get($data, 'DemandInfo.address1', Hash::get($data, 'DemandInfo.address2')));
		    $commission_conditions = array(
		    	'category_id' => Hash::get($data, 'DemandInfo.category_id'),
		    	'jis_cd' => $jis_cd,
		    );
		    $commission_corps = $this->Commission->GetCorpList($commission_conditions, '');
		    $new_commission_corps = $this->Commission->GetCorpList($commission_conditions, 'new');
		    
		    //案件の都道府県、カテゴリーを取得
		    //対象加盟店を取得
		    $auto_commission_corp = $this->AutoCommissionCorp->getAutoCommissionCorpByPrefCategory(
		        Hash::get($data, 'DemandInfo.address1'),
		        Hash::get($data, 'DemandInfo.category_id'),
		        null
		    );
		    $auto_commission_corp_ids = Hash::extract($auto_commission_corp, "");
		    
		    //確定上限数の参照
		    $auto_commission_selection_limit = $this->MSite->findMaxLimit($data);

		    //データの整形
		    $auto_commission_corp_data = array();
		    
		    //ユーザーによって選択されていた加盟店が、自動選定の加盟店と被っていたか
		    $alreadyCommissions = false;
		    
		    //指定されたカテゴリのデフォルト取次時手数料、デフォルト手数料単位を取得する
		    $default_fee = $this->MCategory->getDefault_fee(Hash::get($data, 'DemandInfo.category_id'));
		    
		    //$dataへ加盟店データの追加格納を行う
		    
		    //確定上限数カウント用
		    $auto_commission_selection_limit_count = 0;
		    
			App::uses('CreditHelper', 'View/Helper');
			$credit_helper = new CreditHelper(new View());

			App::uses('AgreementHelper', 'View/Helper');
			$agreement_helper = new AgreementHelper(new View());

		    foreach ($auto_commission_corp_ids as $key => $value) {
		    	//既にユーザーにより選択されている加盟店はスキップする
		    	if(count(Hash::Extract($this->data['CommissionInfo'], '{n}[corp_id='. $value['AutoCommissionCorp']['corp_id'] .']')) >= 1)
		    	{
		    		$alreadyCommissions = true;
		    		continue;
		    	}
		    
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
				////ライセンスチェック
				//$license_check = true;
				//if($data['DemandInfo']['site_id'] != CREDIT_EXCLUSION_SITE_ID)
				//{
				//	$license_check = $agreement_helper->checkLicense($target_commission_corp['MCorp']['id'], Hash::get($data, 'DemandInfo.category_id'));
				//}
				//if(!$license_check)
				//{
				//	continue;
				//}
				// 2017/10/19 ORANGE-541 m-kawamoto DEL(E)

				//与信チェック
				$result_credit = CREDIT_NORMAL;
				if(!in_array($data['DemandInfo']['site_id'], Configure::read('credit_check_exclusion_site_id'))){
					$result_credit = $credit_helper->checkCredit($target_commission_corp['MCorp']['id'], Hash::get($data, 'DemandInfo.genre_id'), false, true);
				}
				if($result_credit == CREDIT_DANGER){
					continue;
				}

		        $commit_flg = 0;

		        // 2017/10/04 ORANGE-509 m-kawamoto CHG(S) 確定条件に処理種別を追加
		        if($target_commission_corp['MCorpCategory']['corp_commission_type'] != 2){
		                // 成約ベース
		                $order_fee = $target_commission_corp['MCorpCategory']['order_fee'];
		                $order_fee_unit = $target_commission_corp['MCorpCategory']['order_fee_unit'];
		                $commission_status = Util::getDivValue('construction_status','progression');
		                $lost_flg = 0;
		                $introduction_not = null;
		                $commission_type = Util::getDivValue('commission_type', 'normal_commission');
		        }else{
		                // 紹介ベース
		                $order_fee = $target_commission_corp['MCorpCategory']['introduce_fee'];
		                $order_fee_unit = 0;
		                $commission_status = Util::getDivValue('construction_status','introduction');
		                // 2017/10/12 ORANGE-566 m-kawamoto CHG(S) Nullの場合メール情報取得の条件に合致しない為修正
		                $lost_flg = 0;
		                // 2017/10/12 ORANGE-566 m-kawamoto CHG(E)
		                $introduction_not = 0;
		                $commission_type = Util::getDivValue('commission_type', 'package_estimate');
		        }

		        if($value['AutoCommissionCorp']['process_type'] == "2"  // 1:自動選定 2:自動取次
		           || $auto_commission_selection_limit_count > 0) 
		        {
			        if($auto_commission_selection_limit > $auto_commission_selection_limit_count)
			        {
			            $commit_flg = 1;
			        }
			        else
			        {
						$lost_flg = 1;
			        }
			    }
		        // 2017/10/04 ORANGE-509 m-kawamoto CHG(E)
		        
		        //手数料
		        $order_fee = !empty($order_fee) ? $order_fee : $default_fee['category_default_fee'];
		        $order_fee_unit = !empty($order_fee) ? $order_fee_unit : $default_fee['category_default_fee_unit'];
		        
		        $auto_commission_corp_data[] = array(
		            'MCorp' => array(
		                'fax' => Hash::get($target_commission_corp, 'MCorp.fax'),
		                'mailaddress_pc' => Hash::get($target_commission_corp, 'MCorp.mailaddress_pc'),
		                'coordination_method'  => Hash::get($target_commission_corp, 'MCorp.coordination_method'),
		                'contactable_time' => Hash::get($target_commission_corp, 'MCorp.contactable_time_from') . ' - ' . Hash::get($target_commission_corp, 'MCorp.contactable_time_to'),
		                'holiday' => Hash::get($target_commission_corp, 'MCorp.holiday'),
		                'corp_name' => Hash::get($target_commission_corp, 'MCorp.corp_name'),
		                'commission_dial' => Hash::get($target_commission_corp, 'MCorp.commission_dial'),
		            ),
		            'corp_fee' => $order_fee_unit == 0 ? $order_fee : null, // 取次先手数料
		            'commission_fee_rate' => $order_fee_unit == 0 ? null : $order_fee, // 取次時手数料率
		            'order_fee_unit' => $order_fee_unit,
		            'commission_status' => $commission_status,
		            'lost_flg' => $lost_flg , // 取次前失注
		            'introduction_not' => $introduction_not, // 紹介不可
		            'commission_type' => $commission_type,
		            'corp_id' => Hash::get($target_commission_corp, 'MCorp.id'),
		            'del_flg' => 0, //削除
		            'appointers'  => $user['id'], // 選定者
		            'first_commission' => 0,
		            'unit_price_calc_exclude' => 0,
		            // 2017.10.12 noguchi ORANGE-503 DEL,ADD(S)
		            //									'tel_commission_datetime' => null, // 電話取次日時
		            //									'tel_commission_person' => null, // 電話取次者
		            'corp_claim_flg' => 0, //代理店クレーム
		            // 2017.10.12 noguchi ORANGE-503 DEL,ADD(D)

		            'remand_flg' => 0, // 差し戻し
		            'commit_flg' => $commit_flg, // 確定
		            'commission_note_sender' => $commit_flg === 1 ? $user['id'] : null, // 取次票送信者
		            'commission_note_send_datetime' => $commit_flg === 1 ? date('Y/m/d H:i:s', time()) : null,// 取次票送信日時
		            'select_commission_unit_price_rank' => Hash::get($target_commission_corp, 'AffiliationAreaStat.commission_unit_price_rank'),
		            'select_commission_unit_price' => Hash::get($target_commission_corp, 'AffiliationAreaStat.commission_unit_price_category'),
		            'created_user_id' => 'AutoCommissionCorp',
		            'modified_user_id' => 'AutoCommissionCorp',
	                // 2017/10/12 ORANGE-566 m-kawamoto ADD(S) 
					'send_mail_fax' => 1,
					'send_mail_fax_othersend' => 0,
	                // 2017/10/12 ORANGE-566 m-kawamoto ADD(E)
		        );

		        // 2017/10/04 ORANGE-509 m-kawamoto CHG(S) カウントを制限する必要はないが念のため
		        //確定上限数用のカウントを+1
		        if($commit_flg === 1)
		        {
		            $auto_commission_selection_limit_count++;
		        }
		        // 2017/10/04 ORANGE-509 m-kawamoto CHG(E)
		    }
		    if(!empty($auto_commission_corp_data)){
		        //エラー発生時にリストアすべき項目を格納
		        $data['restore_at_error'] = array();

		        // 2017/10/04 ORANGE-509 m-kawamoto CHG(S)
		        if($auto_commission_selection_limit_count > 0)
		        {
			        //自動取次先があるので、メール送信のフラグを立てる
			        $data['restore_at_error']['send_commission_info'] = $data['send_commission_info'];
			        $data['send_commission_info'] = 1;

			        //自動取次を行うため、案件状況の更新
			        $data['restore_at_error']['DemandInfo']['demand_status'] = $data['DemandInfo']['demand_status'];
			        $data['DemandInfo']['demand_status'] = Util::getDivValue('demand_status', 'information_sent');
		        }
		        else
		        {
			        $data['DemandInfo']['demand_status'] = Util::getDivValue('demand_status', 'agency_before');
		        }
		        // 2017/10/04 ORANGE-509 m-kawamoto CHG(E)
		        
		        //自動取次の施設を優先するため、$data['CommissionInfo']の前にデータを挿入する
		        $data['restore_at_error']['CommissionInfo'] = $data['CommissionInfo'];
		        $data['CommissionInfo'] = array_merge($auto_commission_corp_data, $data['CommissionInfo']);
		        
		        //地域×カテゴリで加盟店を指定したという選定フラグをオンにする
		        $data['restore_at_error']['DemandInfo']['do_auto_selection_category'] = $data['DemandInfo']['do_auto_selection_category'];
		        $data['DemandInfo']['do_auto_selection_category'] = 1;
		        
				if($data['DemandInfo']['selection_system'] == Util::getDivValue('selection_type', 'AutoSelection'))
				{
			        $data['restore_at_error']['DemandInfo']['selection_system'] = $data['DemandInfo']['selection_system'];
					$data['DemandInfo']['selection_system'] = Util::getDivValue('selection_type', 'ManualSelection');
		        }
		    }
		    else
		    {
		    	//画面上では自動取次先がある旨を表示していたが何らかのイレギュラーで存在しなかった場合
		    	//ただし、ユーザーによって既に選択されていたことによって確定できない場合は除外する
				if($data['display_auto_commission_message'] == "1" && !$alreadyCommissions)
				{
					$this->set('again_enabled', true);
					$this->Session->setFlash(__('NotExistsAutoCommissionCorp', true), 'default', array('class' => 'error_inner'));
					$this->backToDetail($data);
					return;
				}
		    }
		    //$dataへ加盟店データの追加格納完了
		                           
		}
		//2017/06/08  ichino ORANGE-420 ADD end


        $corp_data = array();
        $demand_attached_file_cert = array();
        
        if(isset($data['DemandInfo']['id'])){
            // 企業情報を取得
            $corp_data = $this->MCorp->find ( 'first', array (
                'fields' => '*',
                'conditions' => array (
                    'MCorp.id' => $data['DemandInfo']['id']
                ),
                'order' => array (
                    'MCorp.id' => 'asc'
                )
            ));
            // 案件ファイルを取得
            $demand_attached_file_cert = $this->DemandAttachedFile->find('all',array(
                'fields'=>'*',
                'conditions' => array(
                    'DemandAttachedFile.demand_id' => $data['DemandInfo']['id']
                ),
                'order' => array (
                    'DemandAttachedFile.id' => 'asc'
                )
            ));
            $this->set('corp_data' ,$corp_data);
            $this->set('demand_attached_file_cert' ,$demand_attached_file_cert);
        
        }
        
        $corp_data = array();
        $demand_attached_file_cert = array();
        
        if(isset($data['DemandInfo']['id'])){
            // 企業情報を取得
            $corp_data = $this->MCorp->find ( 'first', array (
                'fields' => '*',
                'conditions' => array (
                    'MCorp.id' => $data['DemandInfo']['id']
                ),
                'order' => array (
                    'MCorp.id' => 'asc'
                )
            ));
            // 案件ファイルを取得
            $demand_attached_file_cert = $this->DemandAttachedFile->find('all',array(
                'fields'=>'*',
                'conditions' => array(
                    'DemandAttachedFile.demand_id' => $data['DemandInfo']['id']
                ),
                'order' => array (
                    'DemandAttachedFile.id' => 'asc'
                )
            ));
            $this->set('corp_data' ,$corp_data);
            $this->set('demand_attached_file_cert' ,$demand_attached_file_cert);
        
        }
                
		// 2016.01.11 n.kai ADD start ORANGE-1194
		// DemandInfoの各項目の先頭と末尾のスペースを除去する
		array_walk( $data['DemandInfo'], function( &$demand_item) {
			$demand_item = preg_replace( '/^[ 　]+/u', '', $demand_item);
			$demand_item = preg_replace( '/[ 　]+$/u', '', $demand_item);
		});
		// 2016.01.11 n.kai ADD end ORANGE-1194

// murata.s ORANGE-367 CHG(S)
		// データが無い場合
		if(empty($data) || empty($data['DemandInfo'])){
			return $this->redirect('/demand/detail');
		}
// murata.s ORANGE-367 CHG(E)

		if(isset($data['cancel'])){
			// キャンセル
			$this->redirect('/demand');
		} else if (isset($data['delete'])){
			// 削除
			self::__delete_demand($data);
		} else if (isset($data['copy'])){
			// コピー
			$this->redirect('/demand/copy/' . $data['DemandInfo']['id']);
		} else if (isset($data['cross'])){
			//クロスセル専用
			$this->redirect('/demand/cross/' . $data['DemandInfo']['id']);
		} else if (isset($data['clear'])){
			$this->redirect('/demand/detail');	//クリア
		} else if (isset($data['again'])){
			$this->redirect('/demand/detail/' . $data['DemandInfo']['id']);
		}

		/*
		 * エラーチェック
		*
		* (validationの実行)
		*/
		$err_flg = false;


		/**
		 * ワンタッチ失注
		 */
		$quick_order_fail = false;
		if(isset($data['quick_order_fail']) &&  $data["DemandInfo"]["quick_order_fail_reason"] != ""){
			$quick_order_fail = true;
			//案件情報
			$data['DemandInfo']["site_id"] = (strlen($data['DemandInfo']["site_id"]) == 0) ? "647" : $data['DemandInfo']["site_id"];
			$data['DemandInfo']['genre_id'] = (strlen($data['DemandInfo']['genre_id']) == 0) ? "673" : $data['DemandInfo']['genre_id'];
			$data['DemandInfo']['category_id'] = (strlen($data['DemandInfo']['category_id']) == 0) ? "470" : $data['DemandInfo']['category_id'];
			$data['DemandInfo']['customer_name'] = (strlen($data['DemandInfo']['customer_name']) == 0) ? "不明" : $data['DemandInfo']['customer_name'];
			if (strlen($data['DemandInfo']['customer_tel'] == 0) || ($data['DemandInfo']['customer_tel'] == '非通知')){
				$data['DemandInfo']['customer_tel'] = '9999999999';
			}
//			$data['DemandInfo']['customer_tel'] = (strlen($data['DemandInfo']['customer_tel']) == 0) ? "9999999999" : $data['DemandInfo']['customer_tel'];

			$data['DemandInfo']['tel1'] = (strlen($data['DemandInfo']['tel1']) == 0) ? "9999999999" : $data['DemandInfo']['tel1'];
			$data["DemandInfo"]["address1"] = (strlen($data["DemandInfo"]["address1"]) == 0) ? "99" : $data["DemandInfo"]["address1"]; //都道府県不明
			$data["DemandInfo"]["address2"] = (strlen($data["DemandInfo"]["address2"]) == 0) ? "不明" : $data["DemandInfo"]["address2"];
			$data["DemandInfo"]["construction_class"] = (strlen($data["DemandInfo"]["construction_class"]) == 0) ? 7 : $data["DemandInfo"]["construction_class"]; //建物種別不明
			$data['DemandInfo']['is_contact_time_range_flg'] = 0;
			$data['DemandInfo']['contact_desired_time'] = date('Y/m/d H:i');


			//ORANGE-291 CHG S
			$data['DemandInfo']['quick_order_fail'] = $data['quick_order_fail'];

			if ($data["DemandInfo"]["quick_order_fail_reason"] == 2){
				//【受付時】間違い電話
				$data["DemandInfo"]["order_fail_reason"] = NULL;
				$data["DemandInfo"]["acceptance_status"] = 3;//除外対象
				$data["DemandInfo"]["demand_status"] = 9; //【削除対象】登録ミス
			}elseif ($data["DemandInfo"]["quick_order_fail_reason"] == 3){
				//【受付時】無言電話
				$data["DemandInfo"]["order_fail_reason"] = 35; //【受付時】受付失敗
				$data["DemandInfo"]["acceptance_status"] = 3;//除外対象
				$data["DemandInfo"]["demand_status"] = 6; //失注
			}elseif ($data["DemandInfo"]["quick_order_fail_reason"] == 4){
				//【受付時】受け漏れ
				$data["DemandInfo"]["order_fail_reason"] = 37; //【受付時】受け漏れ対応失敗
				$data["DemandInfo"]["acceptance_status"] = 3;//除外対象
				$data["DemandInfo"]["demand_status"] = 6; //失注
			}elseif ($data["DemandInfo"]["quick_order_fail_reason"] == 5){
				//【受付時】対象外案件
				$data["DemandInfo"]["order_fail_reason"] = 36; //【受付時】受付対象外
				$data["DemandInfo"]["acceptance_status"] = 3;//除外対象
				$data["DemandInfo"]["demand_status"] = 6; //失注
			}else{
				$data["DemandInfo"]["order_fail_reason"] = 35; //【受付時】受付失敗
				//ORANGE-330 CHG S
				$data["DemandInfo"]["acceptance_status"] = 2;//$data["DemandInfo"]["acceptance_status"] != "" ? $data["DemandInfo"]["acceptance_status"] : 2; //受付ステータス「受付時失注」
				//ORANGE-330 CHG E
				$data["DemandInfo"]["demand_status"] = 6; //失注
			}

			if($data["DemandInfo"]["demand_status"] == 6){
				$data['DemandInfo']['order_fail_date'] = date('Y/m/d');
			}

			//ORANGE-291 CHG E

//			if ($data["DemandInfo"]["quick_order_fail_reason"] != 34){
//				$data['DemandCorrespond']['corresponding_contens'] = "ワンタッチ失注で登録";
//			} else if($data["DemandInfo"]["quick_order_fail_reason"] == 34 && !empty($data['DemandCorrespond']['corresponding_contens'])){
			if ($data['DemandCorrespond']['corresponding_contens'] !="") {
				$data['DemandCorrespond']['corresponding_contens'] = "ワンタッチ失注で登録\r\n" . $data['DemandCorrespond']['corresponding_contens'];//Orange-1155
			} else {
				$data['DemandCorrespond']['corresponding_contens'] = "ワンタッチ失注で登録";
			}
//			}
// murata.s ORANGE-261 CHG(S)
			if (array_key_exists('CommissionInfo', $data)) {
				$commission_type = $this->MCategory->getCommissionType($data['DemandInfo']['category_id']);
				for ($cnt = 0; $cnt < count($data["CommissionInfo"]); $cnt++){
					if($data["CommissionInfo"][$cnt]["corp_id"] == 3539) {
						if ($commission_type != 2){
							// 取次形態 = 成約ベース
							$data["CommissionInfo"][$cnt]['commission_fee_rate'] = 999;
						}
						break;
					}else if((int)$data["CommissionInfo"][$cnt]["corp_id"] == 0){
						$data["CommissionInfo"][$cnt]["corp_id"] = 3539;
						$data["CommissionInfo"][$cnt]["commit_flg"] = 1;
						$data["CommissionInfo"][$cnt]["MCorp"]["corp_name"] = "【SF用】取次前失注用(質問のみ等)";
						if ($commission_type != 2){
							// 取次形態 = 成約ベース
							$data["CommissionInfo"][$cnt]['commission_fee_rate'] = 999;
							$data["CommissionInfo"][$cnt]['corp_commission_type'] = 1;
						}else{
							// 取次形態 = 紹介ベース
							$data["CommissionInfo"][$cnt]['corp_commission_type'] = 2;
						}
						break;
					}
				}
			}
// murata.s ORANGE-261 CHG(E)
			/*
			$is_error = false;
			foreach(array("site_id", "genre_id", "category_id") as $key){
				if($data["DemandInfo"][$key] == "" || !is_numeric($data["DemandInfo"][$key])){
					$this->set('again_enabled', true);
					$this->Session->setFlash(__('InputError', true), 'default', array('class' => 'error_inner'));
					// 入力エラーがあれば詳細画面へ
					$this->DemandInfo->validationErrors[$key] = array("必須入力です。");
					$is_error = true;
				}
			}
			if($is_error){
				$this->backToDetail($data);
				return;
			}

			$found = false;
			$pos = 0;
			foreach($data["CommissionInfo"] as $ci){
				if($ci["corp_id"] == 3539){
					$found = true;
				}
				if((int)$ci["corp_id"] != 0){
					$pos++;
				}
			}
			if(!$found){
				$data["CommissionInfo"][$pos]["corp_id"] = 3539;
				$data["CommissionInfo"][$pos]["MCorp"]["corp_name"] = "【SF用】取次前失注用(質問のみ等)";
			}
			*/
		}

		// 2016.01.07 n.kai MOD start ORANGE-1027
		if($data['DemandInfo']['selection_system'] == Util::getDivValue('selection_type', 'AuctionSelection')){
			// 案件新規登録時、入札式の場合は、入札を行なう。
			// (案件登録後は、「再入札」にチェックをいれた場合のみ入札式を行なう。)
			if($data['DemandInfo']['selection_system_before'] === '') {
				$data['DemandInfo']['do_auction'] = 1;
			}
		}
// 2016.11.17 murata.s ORANGE-185 ADD(S)
		if($data['DemandInfo']['selection_system'] == Util::getDivValue('selection_type', 'AutomaticAuctionSelection')
				&& $data['DemandInfo']['selection_system_before'] === ''){
			if($data['DemandInfo']['selection_system'] == Util::getDivValue('selection_type', 'AutomaticAuctionSelection')) {
				$data['DemandInfo']['do_auction'] = 1;
			}
		}
// 2016.11.17 murata.s ORANGE-185 ADD(E)
// 2016.12.15 murata.s ORANGE-283 ADD(S)
		if($data['DemandInfo']['selection_system'] == Util::getDivValue('selection_type', 'AutoSelection')){
			$data['DemandInfo']['do_auto_selection'] = 1;
		}
// 2016.12.15 murata.s ORANGE-283 ADD(E)

// murata.s ORANGE-261 DEL(S)
// 取次形態 = 一括見積(紹介ベース)の場合は手動選定に戻していたが、
// 企業別ジャンルの取次形態によって成約 or 紹介を判断するため、ここでは選定方式は手動選定に戻さない
// 		if ($data['DemandInfo']['commission_type_div'] == 2) {
// 			$data['DemandInfo']["selection_system"] = Util::getDivValue('selection_type', 'ManualSelection');
// 		}
// murata.s ORANGE-261 DEL(E)

// 2016.12.15 murata.s ORANGE-283 CHG(S)
// 		//ワンタッチ失注のときは入札式の時でも入札処理は行わない ORANGE-1015
// 		if ($quick_order_fail) $data['DemandInfo']['do_auction'] = 0;
		// ワンタッチ失注の時入札処理、自動選定は行わない
		if ($quick_order_fail) {
			$data['DemandInfo']['do_auction'] = 0;
			$data['DemandInfo']['do_auto_selection'] = 0;
		}
// 2016.12.15 murata.s ORANGE-283 CHG(E)

		// 取次送信時、全ての取次先情報の確定がチェックされていなければ、送信エラーとする
		$sendErr =false;
		$commission_flg_count = 0;
// murata.s ORANGE-261 CHG(S)
		if (Hash::get($data,'send_commission_info')) {
			foreach ($data['CommissionInfo'] as $commisionData) {
				if ($commisionData['corp_id'] && isset($commisionData['commit_flg']) && $commisionData['commit_flg']) {
					$commission_flg_count = $commission_flg_count + 1;
				}
			}
			if ($commission_flg_count == 0) {
				// 確定が0の場合エラー
				$err_flg = true;
				$sendErr = true;
			} else {
				// 確定が1以上の場合OK
				$err_flg = false;
				$sendErr = false;
			}
		}
// murata.s ORANGE-261 CHG(E)
		$this->set('sendErr', $sendErr);

// murata.s ORANGE-261 CHG(S)
		// 案件情報
		$set_data = array('DemandInfo' => $data['DemandInfo'] , 'VisitTime' => $data['VisitTime'], 'CommissionInfo' => $data['CommissionInfo']);
// murata.s ORANGE-261 CHG(E)

		$this->DemandInfo->set($set_data);

		// 取次情報送信ボタンを押した場合か、メール/FAX送信不要にチェックをつけた場合バリデーションを実行
        // 2017.04.18 判定にHash::getを追加
		if (Hash::get($this->data,'not-send') == 0
			&& Hash::get($this->data,'send_commission_info') == 0) {
			$this->DemandInfo->validator()
				->add('demand_status', 'ErrorDemandStatusIntroduceMail2', array(
					'rule' => 'checkDemandStatusIntroduceMail2',
					'last' => true,
				));
		}

		if (!$this->DemandInfo->validates()) {
			$err_flg = true;
		}

		// 案件別ヒアリング項目
		if (array_key_exists('DemandInquiryAnswer', $data)) {
			$this->DemandInquiryAnswer->set($data['DemandInquiryAnswer']);
			if (!$this->DemandInquiryAnswer->validates()) {
				$err_flg = true;
			}
		}

		// 訪問日付
		if (array_key_exists('VisitTime', $data)) {
			$v_data = array('VisitTime' => array());
			foreach ($data['VisitTime'] as $v ){
				// ORANGE-1252 2016.02.12 kishimoto@tobila CHG S
				// チェック次にもdo_auctionを設定するように修正
				$add_array = array();
				$add_array['demand_info_id'] = isset($data['DemandInfo']['id']) ? $data['DemandInfo']['id'] : 0;
				if (!empty($data['DemandInfo']['do_auction'])) {
					$add_array['do_auction'] = 1;
				}

				$v_data['VisitTime'][] = $v + $add_array;
				// ORANGE-1252 2016.02.12 kishimoto@tobila CHG E
			}
			// チェックのみを実施(保存しない)
			if (!$this->VisitTime->saveAll($v_data['VisitTime'], array('validate' => 'only'))) {
				$err_flg = true;
			}
			/*
			$this->VisitTime->set($data['VisitTime']);
			if (!$this->VisitTime->validates()) {
				$err_flg = true;
			}
			*/
		}

        // 取次先情報
		if (array_key_exists('CommissionInfo', $data)) {
			$this->CommissionInfo->set($data['CommissionInfo']);
			// 動的なバリデーションルールを追加
            // 2017.04.18 判定にHash::getを追加
			if (!$this->CommissionInfo->addValidateDemand(Hash::get($this->data,'DemandInfo.demand_status'))->validates()) {
				$err_flg = true;
			}
			// 取次先の確定上限をチェック
			if (!$this->CommissionInfo->validateCheckLimitCommitFlg($data)) {
				$this->CommissionInfo->invalidate('commit_flg_limit', '確定できる上限数を超えています');
				$err_flg = true;
			}
		}


// murata.s ORANGE-261 DEL(S)
// 		// 紹介先情報
// 		if (array_key_exists('IntroduceInfo', $data)) {
// 			$this->CommissionInfo->set($data['IntroduceInfo']);
// 			if (!$this->CommissionInfo->validates()) {
// 				$err_flg = true;
// 			}
// 		}
// murata.s ORANGE-261 DEL(E)

		// 対応履歴情報
		// 取次情報送信ボタンを押した場合は、データをセットせずバリデーションも行わない。
		if (array_key_exists('DemandCorrespond', $data) && $data['send_commission_info'] == 0) {
			$this->DemandCorrespond->set($data['DemandCorrespond']);
			if (!$this->DemandCorrespond->validates()) {
				$err_flg = true;
			}
		}

// murata.s ORANGE-261 CHG(S)
		// 2015.08.12 s.harada ADD start ORANGE-783
		// 紹介ベースで案件状況が【進行中】で紹介先を未選択の場合はエラー
        // 2017.04.18 判定にHash::getを追加
		if ($this->data['DemandInfo']['commission_type_div'] == 2
			&& !array_key_exists('CommissionInfo', $data)
			&& (Hash::get($this->data,'DemandInfo.demand_status') == Util::getDivValue('demand_status','telephone_already')
			|| Hash::get($this->data,'DemandInfo.demand_status') == Util::getDivValue('demand_status','information_sent'))) {
			$err_flg = true;
			$this->Session->setFlash(__('NotEmptyIntroduceInfo', true), 'default', array('class' => 'error_inner'));
			$this->backToDetail($data);
			return;
		}
		// 2015.08.12 s.harada ADD end ORANGE-783
// murata.s ORANGE-261 CHG(E)

		// 案件情報の更新日時チェック

		if (!$this->__check_modified_demand($data['DemandInfo'])) {
			$again_enabled = true;
			$this->set('again_enabled', $again_enabled);
			$err_flg = true;
			$this->Session->setFlash(__('ModifiedNotCheck', true), 'default', array('class' => 'error_inner'));
			$this->backToDetail($data);
			return;
		}
		$this->set('again_enabled', $again_enabled);

		// 2016.MM.DD ota.r@tobila ADD START  ORANGE-78 【取次管理】「入札流れ」フラグが立っている場合の処理変更
		/*
		  * 入札流れフラグがたっており、選定方式が入札選定の場合、
		  * 再入札 + 案件状況「未選定」でないと登録を行わせない
		  */
		if(($data['DemandInfo']['selection_system'] == 2 || $data['DemandInfo']['selection_system'] == 3)
			&& (isset($data['DemandInfo']['auction']) && $data['DemandInfo']['auction'] == 1)) {
			if($data['DemandInfo']['demand_status'] != 1 || $data['DemandInfo']['do_auction'] != 2){
				$err_flg = true;
				$error_message = '入札流れ案件を入札選定に変更する場合、「再入札」のチェックと、案件状況を「未選定」に変更してください。　';
				$this->Session->setFlash($error_message, 'default', array('class' => 'error_inner'));
				$this->backToDetail($data);
				return;
			}
		}
		// 2016.MM.DD ota.r@tobila ADD END  ORANGE-78 【取次管理】「入札流れ」フラグが立っている場合の処理変更

		// 2016.05.30 ota.r@tobila ADD START ORANGE-85 【案件管理】入札式選定にて、「加盟店確認中」での登録を規制する
		/* 手動選定から入札選定に変更した場合 */
		if (($data['DemandInfo']['selection_system'] == 2 || $data['DemandInfo']['selection_system'] == 3)
				&& ($data['DemandInfo']['selection_system_before'] != 2 && $data['DemandInfo']['selection_system_before'] != 3 && $data['DemandInfo']['selection_system_before'] != '')) {
			if($data['DemandInfo']['demand_status'] != 1 || $data['DemandInfo']['do_auction'] != 2){
				$err_flg = true;
				$error_message = '手動選定から入札式選定に変更する場合、「再入札」のチェックと案件状況を「未選定」に変更してください。';
				$this->Session->setFlash($error_message, 'default', array('class' => 'error_inner'));
				$this->backToDetail($data);
				return;
			}

		}
		// 2016.05.30 ota.r@tobila ADD END ORANGE-85 【案件管理】入札式選定にて、「加盟店確認中」での登録を規制する

// 2016.12.15 murata.s ORANGE-283 ADD(S)
		if($data['DemandInfo']['selection_system'] == Util::getDivValue('selection_type', 'AutoSelection')
				&& !empty($data['DemandInfo']['do_auto_selection'])){
// 2017.09.29 m-kawamoto ORANGE-420 ADD(S)
			if($data['DemandInfo']['demand_status'] != 1 && $data['DemandInfo']['do_auto_selection_category'] != 1){
// 2017.09.29 m-kawamoto ORANGE-420 ADD(S)
				$err_flg = true;
				$error_message = '自動選定にする場合、案件状況を「未選定」に変更してください。';
				$this->Session->setFlash($error_message, 'default', array('class' => 'error_inner'));
				$this->backToDetail($data);
				return;
			}
		}
// 2016.12.15 murata.s ORANGE-283 ADD(E)

		//if ($err_flg && !$quick_order_fail) {
		if ($err_flg) {
			$this->Session->setFlash(__('InputError', true), 'default', array('class' => 'error_inner'));
			// 入力エラーがあれば詳細画面へ
			$this->backToDetail($data);

		} else {
			// [JBR]見積書、領収書ファイルの一時アップロード
			// ※ 確認画面を経由するとアップロードファイルが削除される為、
			//    一時ファイルとしてアップロードしておく
			//    (正式なアップロードは本登録後に行う)
			// 2015.2.14 inokuchi (取次管理に移動)
// 			$tmp_estimate_file = "";
// 			$tmp_receipt_file = "";
// 			if (!empty($data['DemandInfo']['jbr_estimate']['name'])) {
// 				$tmp_estimate_file = $this->__uplaod_file_tmp($data['DemandInfo']['jbr_estimate']['tmp_name']);
// 			}
// 			if (!empty($data['DemandInfo']['jbr_receipt']['name'])) {
// 				$tmp_receipt_file = $this->__uplaod_file_tmp($data['DemandInfo']['jbr_receipt']['tmp_name']);
// 			}
// 			// 一時ファイル名を入力値データにセット
// 			$data['DemandInfo']['tmp_estimate_file'] = $tmp_estimate_file;
// 			$data['DemandInfo']['tmp_receipt_file'] = $tmp_receipt_file;

			$auction_flg = true;
			$auction_none_flg = true;
			// 2016.09.05 murata.s ORANGE-176 ADD(S)
			$has_start_time_err = false;
			// 2016.09.05 murata.s ORANGE-176 ADD(E)

// 2016.11.17 murata.s ORANGE-185 CHG(S)
			// オークション選定のみ
			if($data['DemandInfo']['selection_system'] == Util::getDivValue('selection_type', 'AuctionSelection')
					|| $data['DemandInfo']['selection_system'] == Util::getDivValue('selection_type', 'AutomaticAuctionSelection')){
// 2016.11.17 murata.s ORANGE-185 CHG(E)
				// オークション処理実行の場合のみ
                                //2017/07/11  ichino ORANGE-420 ADD start 自動取次した場合は、オークション選定しない
				if(!empty($data['DemandInfo']['do_auction']) && $data['DemandInfo']['do_auto_selection_category'] == 0){
                                //2017/07/11  ichino ORANGE-420 ADD end
					// ORANGE-932 【入札式】再入札の場合、優先度を再計算する h.hanaki 2015.10.31
					if($data['DemandInfo']['do_auction'] == 2){
						$data['DemandInfo']['priority'] = '';
					}
					// オークション開始日時
					$data['DemandInfo']['auction_start_time'] = date('Y-m-d H:i:s');
					// オークション締切日時
					$data['DemandInfo']['auction_deadline_time'] = '';
					// 案件状況
					$data['DemandInfo']['demand_status'] = Util::getDivValue('demand_status', 'agency_before');

					// 最小の訪問日時、連絡希望日時 を取得
					$visit_time_list = array();
					foreach ($data['VisitTime'] as $val){
						if ($val['is_visit_time_range_flg'] == 0 && strlen($val['visit_time']) > 0)
							$visit_time_list[] = $val['visit_time'];
						if ($val['is_visit_time_range_flg'] == 1 && strlen($val['visit_time_from']) > 0)
							$visit_time_list[] = $val['visit_time_from'];
						/*
						if(!empty($val['visit_time'])){
							$visit_time_list[] = $val['visit_time'];
						}
						*/
					}
					if(!empty($visit_time_list)){
						// 訪問日時を使用する場合
						$preferred_date = Util::getMinVisitTime($visit_time_list);
						$data['DemandInfo']['method'] = 'visit';
					} else {
						// 連絡希望日時を使用する場合
						if ($data['DemandInfo']['is_contact_time_range_flg'] == 0)
							$preferred_date = $data['DemandInfo']['contact_desired_time'];
						if ($data['DemandInfo']['is_contact_time_range_flg'] == 1)
							$preferred_date = $data['DemandInfo']['contact_desired_time_from'];
						//$preferred_date = $data['DemandInfo']['contact_desired_time'];
						$data['DemandInfo']['method'] = 'tel';
					}

					$judge_result = array(
						"result_flg"  => 0,              // 0 = 変更なし , 1 = 変更あり(通常案件で除外時間の対象となった)
						"result_date" => null            // 開始日
					);
// 2016.09.05 murata.s ORANGE-176 CHG(S)
					$c = strtotime($data['DemandInfo']['auction_start_time']);
					$p = strtotime($preferred_date);

					// 案件作成日と希望日が逆転している場合は手動選定とする
					if($p < $c){
						$auction_flg = false;
						$auction_none_flg = false;
						$has_start_time_err = true;
					}else{
						// ORANGE-14 iwai S
						// 優先度が未設定の場合
						if(empty($data['DemandInfo']['priority'])){
							$judge_result = Util::judgeAuction($data['DemandInfo']['auction_start_time'], $preferred_date, $data['DemandInfo']['genre_id'], $data['DemandInfo']['address1'], $data['DemandInfo']['auction_deadline_time'], $data['DemandInfo']['priority']);
/*						if ($judge_result['result_flg'] == 0) {
								$auction_flg = true;
							} else	{
								$auction_flg = false;
							}

							//****************************************************************************************************************************
							// ORANGE-1236 優先度が通常で、除外時間内であれば、除外空け時間をオークション開始時間に設定して、再度judgeAuctionを実行する。
							//****************************************************************************************************************************
							if($data['DemandInfo']['priority'] == Util::getDivValue('priority', 'normal')){
								if ($judge_result['result_flg'] == 1){
									// オークション開始日時
									$data['DemandInfo']['auction_start_time'] = $judge_result['result_date'];
									//$auction_flg = Util::judgeAuction($data['DemandInfo']['auction_start_time'], $preferred_date, $data['DemandInfo']['genre_id'], $data['DemandInfo']['address1'], $data['DemandInfo']['auction_deadline_time'], $data['DemandInfo']['priority']);
									$judge_result = Util::judgeAuction($data['DemandInfo']['auction_start_time'], $preferred_date, $data['DemandInfo']['genre_id'], $data['DemandInfo']['address1'], $data['DemandInfo']['auction_deadline_time'], $data['DemandInfo']['priority']);
									if ($judge_result['result_flg'] == 0) {
										$auction_flg = true;
									}	else {
										$auction_flg = false;
									}
								}
							}
*/
						}
						// 優先度が大至急の場合
						else if($data['DemandInfo']['priority'] == Util::getDivValue('priority', 'asap')){
							//$auction_flg = Util::judgeAsap($data['DemandInfo']['auction_start_time'], $preferred_date, $data['DemandInfo']['genre_id'], $data['DemandInfo']['address1'], $data['DemandInfo']['auction_deadline_time']);
							$judge_result = Util::judgeAsap($data['DemandInfo']['auction_start_time'], $preferred_date, $data['DemandInfo']['genre_id'], $data['DemandInfo']['address1'], $data['DemandInfo']['auction_deadline_time'], $data['DemandInfo']['priority']);
						}
						// 優先度が至急の場合
						else if($data['DemandInfo']['priority'] == Util::getDivValue('priority', 'immediately')){
							//$auction_flg = Util::judgeImmediately($data['DemandInfo']['auction_start_time'], $preferred_date, $data['DemandInfo']['genre_id'], $data['DemandInfo']['address1'], $data['DemandInfo']['auction_deadline_time']);
							$judge_result = Util::judgeImmediately($data['DemandInfo']['auction_start_time'], $preferred_date, $data['DemandInfo']['genre_id'], $data['DemandInfo']['address1'], $data['DemandInfo']['auction_deadline_time'], $data['DemandInfo']['priority']);
						}
						// 優先度が通常の場合
						else {
//							$auction_flg = Util::judgeNormal($data['DemandInfo']['auction_start_time'], $preferred_date, $data['DemandInfo']['genre_id'], $data['DemandInfo']['address1'], $data['DemandInfo']['auction_deadline_time'], $data['DemandInfo']['priority'], $new_date);
							$judge_result = Util::judgeNormal($data['DemandInfo']['auction_start_time'], $preferred_date, $data['DemandInfo']['genre_id'], $data['DemandInfo']['address1'], $data['DemandInfo']['auction_deadline_time'], $data['DemandInfo']['priority']);
							//****************************************************************************************************************************
							// ORANGE-1236 優先度が通常で、除外時間内であれば、除外空け時間をオークション開始時間に設定して、再度judgeAuctionを実行する。
							//****************************************************************************************************************************
/*							if ($judge_result['result_flg'] == 1){
								// オークション開始日時
								$data['DemandInfo']['auction_start_time'] = $judge_result['result_date'];
								//$auction_flg = Util::judgeAuction($data['DemandInfo']['auction_start_time'], $preferred_date, $data['DemandInfo']['genre_id'], $data['DemandInfo']['address1'], $data['DemandInfo']['auction_deadline_time'], $data['DemandInfo']['priority']);
								//TODO : 優先度再計算用処理
								//優先度が変更された場合は優先度毎の判定を再度行う
								$judge_result = Util::judgeAuction($data['DemandInfo']['auction_start_time'], $preferred_date, $data['DemandInfo']['genre_id'], $data['DemandInfo']['address1'], $data['DemandInfo']['auction_deadline_time'], $data['DemandInfo']['priority']);
								if ($judge_result['result_flg'] == 0) {
									$auction_flg = true;
								}	else {
									$auction_flg = false;
								}
							}
*/						}
						if ($judge_result['result_flg'] == 1){
							// オークション開始日時
	// 2016.09.05 murata.s ORANGE-176 CHG(S)
							if(!empty($judge_result['result_date']))
								$data['DemandInfo']['auction_start_time'] = $judge_result['result_date'];
	// 2016.09.05 murata.s ORANGE-176 CHG(E)
							//$auction_flg = Util::judgeAuction($data['DemandInfo']['auction_start_time'], $preferred_date, $data['DemandInfo']['genre_id'], $data['DemandInfo']['address1'], $data['DemandInfo']['auction_deadline_time'], $data['DemandInfo']['priority']);
							//TODO : 優先度再計算用処理
							//優先度が変更された場合は優先度毎の判定を再度行う
							$judge_result = Util::judgeAuction($data['DemandInfo']['auction_start_time'], $preferred_date, $data['DemandInfo']['genre_id'], $data['DemandInfo']['address1'], $data['DemandInfo']['auction_deadline_time'], $data['DemandInfo']['priority']);
							if ($judge_result['result_flg'] == 0) {
								$auction_flg = true;
							}	else {
								$auction_flg = false;
							}
						}
						// ORANGE-14 iwai E
						// オークション対象の加盟店の有無チェック(0件はfalse)
						$auction_none_flg = $this->__check_number_auction_infos($data);
					}
// 2016.09.05 murata.s ORANGE-176 CHG(E)
					// オークション選定時間外または対象加盟店0件の場合
					if( (!$auction_flg) || (!$auction_none_flg) ) {
						// 選定方式
						$data['DemandInfo']['selection_system'] = Util::getDivValue('selection_type', 'ManualSelection');
						// 案件状況
						$data['DemandInfo']['demand_status'] = Util::getDivValue('demand_status', 'no_selection');
						// オークション開始日時
						$data['DemandInfo']['auction_start_time'] ='';
						// オークション開始日時
						$data['DemandInfo']['auction_deadline_time'] ='';
						// 2015.10.09 n.kai ADD start ORANGE_AUCTION-25
						// オークション実行フラグを空にし、auction_infosを生成しないようにする
						$data['DemandInfo']['do_auction'] ='';
						// 2015.10.09 n.kai ADD end
					} else {
						// オークション流れ案件フラグ
						$data['DemandInfo']['auction'] = 0;
						// オークションメールSTOPフラグ
						$data['DemandInfo']['push_stop_flg'] = 0;
						// 案件状況
						$data['DemandInfo']['demand_status'] = Util::getDivValue('demand_status', 'agency_before');
					}
				}
			}

// 2016.11.17 murata.s ORANGE-185 ADD(S)
// 2016.12.15 murata.s ORANGE-283 CHG(S)
			// 自動選定で未選定の場合のみ
// 			if($data['DemandInfo']['selection_system'] == Util::getDivValue('selection_type', 'AutoSelection')
// 					&& $data['DemandInfo']['selection_system_before'] === ''
// 					&& $data['DemandInfo']['demand_status'] == Util::getDivValue('demand_status', 'no_selection')){
			if($data['DemandInfo']['selection_system'] == Util::getDivValue('selection_type', 'AutoSelection')
					&& $data['DemandInfo']['demand_status'] == Util::getDivValue('demand_status', 'no_selection')){
// 2016.12.15 murata.s ORANGE-283 CHG(E)

// 2016.12.15 murata.s ORANGE-283 CHG(S)

				// 自動選定処理実行の場合のみ
                                //2017/07/11  ichino ORANGE-420 ADD start 自動取次した場合は、オークション選定しない
				if(!empty($data['DemandInfo']['do_auto_selection']) && $data['DemandInfo']['do_auto_selection_category'] == 0){
                                //2017/07/11  ichino ORANGE-420 ADD end
					$user = $this->Auth->user();

// 2016.12.08 murata.s ORANGE-250 CHG(S)
// 				// 市町村コードの取得
// 				$jis_cd = $this->MPost->getTargetJisCd($data['DemandInfo']['address1'], $data['DemandInfo']['address2']);
//
// // 2016.11.24 murata.s ORANGE-185 CHG(S)
// // 				$auto_commissions = $this->AutoCommissionSelect->get_auto_commission_info($data['DemandInfo']['genre_id'], $data['DemandInfo']['category_id'], $jis_cd);
// 				// 訪問日時、連絡希望日時 を取得
// 				$visit_time_list = array();
// 				foreach ($data['VisitTime'] as $val){
// 					if ($val['is_visit_time_range_flg'] == 0 && strlen($val['visit_time']) > 0)
// 						$visit_time_list[] = $val['visit_time'];
// 					if ($val['is_visit_time_range_flg'] == 1 && strlen($val['visit_time_from']) > 0)
// 						$visit_time_list[] = $val['visit_time_from'];
// 				}
//
// 				if(!empty($visit_time_list)){
// 					// 訪問日時を使用する場合
// 					$preferred_date = Util::getMinVisitTime($visit_time_list);
// 					$data['DemandInfo']['method'] = 'visit';
// 				} else {
// 					// 連絡希望日時を使用する場合
// 					if ($data['DemandInfo']['is_contact_time_range_flg'] == 0)
// 						$preferred_date = $data['DemandInfo']['contact_desired_time'];
// 					if ($data['DemandInfo']['is_contact_time_range_flg'] == 1)
// 						$preferred_date = $data['DemandInfo']['contact_desired_time_from'];
// 					$data['DemandInfo']['method'] = 'tel';
// 				}
//
// 				// メール送信時間を取得のため
// 				// 一時的にDemandInfo.auction_start_time、DemandInfo.auction_deadline_timeを設定
// 				$data['DemandInfo']['auction_start_time'] = date('Y-m-d H:i:s');
// 				$data['DemandInfo']['auction_deadline_time'] = '';
// 				$priority = $data['DemandInfo']['priority'];
//
// 				// 優先度が未設定の場合
// 				if(empty($data['DemandInfo']['priority'])){
// 					$judge_result = Util::judgeAuction($data['DemandInfo']['auction_start_time'], $preferred_date, $data['DemandInfo']['genre_id'], $data['DemandInfo']['address1'], $data['DemandInfo']['auction_deadline_time'], $priority);
// 				}
// 				// 優先度が大至急の場合
// 				else if($data['DemandInfo']['priority'] == Util::getDivValue('priority', 'asap')){
// 					$judge_result = Util::judgeAsap($data['DemandInfo']['auction_start_time'], $preferred_date, $data['DemandInfo']['genre_id'], $data['DemandInfo']['address1'], $data['DemandInfo']['auction_deadline_time'], $priority);
// 				}
// 				// 優先度が至急の場合
// 				else if($data['DemandInfo']['priority'] == Util::getDivValue('priority', 'immediately')){
// 					$judge_result = Util::judgeImmediately($data['DemandInfo']['auction_start_time'], $preferred_date, $data['DemandInfo']['genre_id'], $data['DemandInfo']['address1'], $data['DemandInfo']['auction_deadline_time'], $priority);
// 				}
// 				// 優先度が通常の場合
// 				else {
// 					$judge_result = Util::judgeNormal($data['DemandInfo']['auction_start_time'], $preferred_date, $data['DemandInfo']['genre_id'], $data['DemandInfo']['address1'], $data['DemandInfo']['auction_deadline_time'], $priority);
// 				}
// 				if ($judge_result['result_flg'] == 1){
// 					// オークション開始日時
// 					if(!empty($judge_result['result_date']))
// 						$data['DemandInfo']['auction_start_time'] = $judge_result['result_date'];
// 					//優先度が変更された場合は優先度毎の判定を再度行う
// 					$judge_result = Util::judgeAuction($data['DemandInfo']['auction_start_time'], $preferred_date, $data['DemandInfo']['genre_id'], $data['DemandInfo']['address1'], $data['DemandInfo']['auction_deadline_time'], $priority);
// 				}
//
// 				$auto_commissions = $this->__update_auction_infos($data, true);
//
// 				// 一時的に設定したDemandInfo.auction_start_time、DemandInfo.auction_deadline_timeを削除
// 				unset($data['DemandInfo']['auction_start_time']);
// 				unset($data['DemandInfo']['auction_deadline_time']);
//
// 				if(!empty($auto_commissions['AuctionInfo'])){
// 					uasort($auto_commissions['AuctionInfo'], function($a, $b){
// 						if($a['push_time'] != $b['push_time']){
// 							// AuctionInfo.push_time asc
// 							return $a['push_time'] < $b['push_time'] ? -1 : 1;
// 						}else if($a['AffiliationAreaStat']['commission_unit_price_category'] != $b['AffiliationAreaStat']['commission_unit_price_category']){
// 							// AffiliationAreaStat.commission_unit_price_category IS NULL
// 							if(empty($a['AffiliationAreaStat']['commission_unit_price_category']) && !empty($b['AffiliationAreaStat']['commission_unit_price_category']))
// 								return 1;
// 							else if(!empty($a['AffiliationAreaStat']['commission_unit_price_category']) && empty($b['AffiliationAreaStat']['commission_unit_price_category']))
// 								return -1;
// 							else
// 								// AffiliationAreaStat.commission_unit_price_category desc
// 								return $a['AffiliationAreaStat']['commission_unit_price_category'] > $b['AffiliationAreaStat']['commission_unit_price_category'] ? -1 : 1;
// 						}else if($a['AffiliationAreaStat']['commission_count_category'] != $b['AffiliationAreaStat']['commission_count_category']){
// 							// AffiliationAreaStat.commission_count_category desc
// 							return $a['AffiliationAreaStat']['commission_count_category'] > $b['AffiliationAreaStat']['commission_count_category'] ? -1 : 1;
// 						}else{
// 							return 0;
// 						}
// 					});
// 				}
					// 案件IDの取得
					$demand_id = (array_key_exists('id', $data['DemandInfo'])) ? $data['DemandInfo']['id'] : $this->DemandInfo->id;

					// 既存処理をComponent化、AuctionInfoUtil->update_auction_infosへ移動
					$auto_commissions = $this->AuctionInfoUtil->get_auctionInfo_for_autoCommission($demand_id, $data);
// 2016.12.08 murata.s ORANGE-250 CHG(E)

					$default_fee = $this->MCategory->getDefault_fee($data['DemandInfo']['category_id']);
					$commission_infos = array();

					// 手動選定済みの取次先を抽出
					foreach ($data['CommissionInfo'] as $key => $val){
						if(!empty($val['corp_id']))
							$commission_infos[] = $val;
					}

					// 2016.12.16 murata.s ADD(S)
					$is_select = false;
					// 2016.12.16 murata.s ADD(E)
					if(!empty($auto_commissions['AuctionInfo'])){
// murata.s ORANGE-261 CHG(S)
						foreach ($auto_commissions['AuctionInfo'] as $val){

							if($val['MCorpCategory']['corp_commission_type'] != 2){
								// 成約ベース
								$order_fee = $val['MCorpCategory']['order_fee'];
								$order_fee_unit = $val['MCorpCategory']['order_fee_unit'];
								$commission_status = Util::getDivValue('construction_status','progression');
								$commission_type = Util::getDivValue('commission_type', 'normal_commission');

							}else{
								// 紹介ベース
								$order_fee = $val['MCorpCategory']['introduce_fee'];
								//$order_fee_unit = $val['MCorpCategory']['order_fee_unit'];
								$order_fee_unit = 0;
								$commission_status = Util::getDivValue('construction_status','introduction');
								$commission_type = Util::getDivValue('commission_type', 'package_estimate');
							}

							$order_fee = !empty($order_fee) ? $order_fee : $default_fee['category_default_fee'];
							$order_fee_unit = !empty($order_fee) ? $order_fee_unit : $default_fee['category_default_fee_unit'];

							// 自動選定された取次先がすでに登録されている場合は登録しない
							$has_commissions = array_filter($commission_infos, function($v) use($val){
								return $v['corp_id'] == $val['MCorp']['id'];
							});
							if(!empty($has_commissions)) continue;

							$commission_infos[] = array(
									'corp_id' => $val['MCorp']['id'], // 企業ID
									'first_commission' => 0, // 初取次チェック
									'unit_price_calc_exclude' => 0, // 取次単価対象外
									'remand_flg' => 0, // 差し戻し
									'commit_flg' => 0, // 確定
									'lost_flg' => 0, // 取次前失注
									'corp_fee' => $order_fee_unit == 0 ? $order_fee : null, // 取次先手数料
									'commission_fee_rate' => $order_fee_unit == 0 ? null : $order_fee, // 取次時手数料率
									'select_commission_unit_price_rank' => $val['AffiliationAreaStat']['commission_unit_price_rank'], // 単価ランク
									'select_commission_unit_price' => $val['AffiliationAreaStat']['commission_unit_price_category'], // 取次単価
// 2017.01.04 murata.s ORANGE-244 ADD(S)
									'order_fee_unit' => $order_fee_unit,
// 2017.01.04 murata.s ORANGE-244 ADD(E)
									'appointers' => $user['id'], // 選定者
// 2017.10.12 noguchi ORANGE-503 DEL,ADD(S)
//									'tel_commission_datetime' => null, // 電話取次日時
//									'tel_commission_person' => null, // 電話取次者
    							    'corp_claim_flg' => 0, //代理店クレーム
// 2017.10.12 noguchi ORANGE-503 DEL,ADD(D)
									'commission_note_send_datetime' => null, // 取次票送信日時
									'commission_note_sender' => null, // 取次票送信者
									'del_flg' => 0, // 削除
									//ORANGE-259 ADD S
									'created_user_id' => 'AutomaticAuction',
									'modified_user_id' => 'AutomaticAuction',
									//ORANGE-259 ADD E
									'commission_status' => $commission_status,
									'commission_type' => $commission_type,
							);
							$is_select = true;
						}
// murata.s ORANGE-261 CHG(E)
					}
// 2016.11.24 murata.s ORANGE-185 CHG(E)
					if(!empty($commission_infos)){
						// 2016.12.16 CHG(S)
						if($is_select){
							$data['DemandInfo']['demand_status'] = Util::getDivValue('demand_status', 'agency_before');
						}
						// 2016.12.16 CHG(E)
						$data['CommissionInfo'] = $commission_infos;
					}
				}
				// 自動選定の場合は選定後に手動選定に戻す
				$data['DemandInfo']['selection_system'] = Util::getDivValue('selection_type', 'ManualSelection');
// 2016.12.15 murata.s ORANGE-283 ADD(E)
			}
// 2016.11.17 murata.s ORANGE-185 ADD(E)

			// 確認画面削除の為
			$err_flg = 1;
			// 2015.02.18 k.yamada
			// エラーがある取次先を保持する
			$error_no = array();

			try {
				// トランザクション開始
				$this->DemandInfo->begin();
				// 案件情報の登録
				if ($this->__update_demand($data)) {
					// 案件別ヒアリング項目の登録
					if ($this->__update_demand_inquiry_answer($data)) {

						// 訪問日付の登録
						if ($this->__update_visit_time($data)) {
							// 取次先情報の登録
							if ($this->__update_commission($data, $error_no)) {
								// 紹介先情報の登録
								$this->log("1@call __update_introduce()",LOG_DEBUG);
								if ($this->__update_introduce($data)) {
									// オークション情報の登録
									if ($this->__update_auction_infos($data)) {
										// 案件対応履歴の登録
										if ($this->__update_demand_correspond($data)) {
											// 全ての更新に成功すればエラーフラグを倒す
											$err_flg = 0;
										}
									}
								}
							}
						}
					}
				}

				$this->set('regist_enabled', $regist_enabled);

				$err_flg = $err_flg && !$quick_order_fail;
				if (!$err_flg) {
					$this->DemandInfo->commit();
					if (array_key_exists('id', $set_data['DemandInfo'])) {
						// 更新の時
						$demandId = $set_data['DemandInfo']['id'];
					} else {
						// 新規登録の時
						$demandId = $this->DemandInfo->getLastInsertID();
					}
                    //「取次情報送信」ボタンが押下された時
                    // 2017.04.18 判定にHash::getを追加
                    if (Hash::get($data,'send_commission_info') == 1) {
					// demand_idよりcommission_infosのレコードを取得
					$commissionInfosDatas = $this->CommissionInfo->find('all', array('conditions' => array('CommissionInfo.demand_id' => $demandId)));
					foreach ($commissionInfosDatas as $commissionInfosData) {
                            if ($commissionInfosData['CommissionInfo']['commit_flg'] == 1) {
                                //プッシュ通知フラグが0のとき、かつ、企業の取次方法が6または7のとき送信する
                                if ($commissionInfosData['CommissionInfo']['app_push_flg'] == 0
                                        && ($commissionInfosData['MCorp']['coordination_method'] == 6 || $commissionInfosData['MCorp']['coordination_method'] == 7)) {
							$corpId = $commissionInfosData['CommissionInfo']['corp_id'];

							//加盟店IDに紐づくユーザIDを取得
							$options = array();
							$options['conditions'] = array('MUser.affiliation_id' => $corpId);
							$users = $this->MUser->find('all', $options);

							$amazonSnsUtil = new AmazonSnsUtil();
							foreach ($users as $user) {
								//通知送信
								$pushMessage = '新しい案件があります。';
								$amazonSnsUtil->publish($user['MUser']['user_id'], $pushMessage);
							}
							//送信後app_push_flgを1にする
							$updata = array('id' => $commissionInfosData['CommissionInfo']['id'], 'app_push_flg' => 1);
							$ci_setdata = array('CommissionInfo' => $updata);
							// 登録する項目（フィールド指定）
							$ci_up_fields = array_keys($updata);
							// 更新
							$result = $this->CommissionInfo->save($ci_setdata, false, $ci_up_fields);
							if (is_array($result)) {
								$this->log(array('update app_push_flg success', $ci_setdata, $result), LOG_DEBUG);
							} else {
								$this->log(array('update app_push_flg error', $ci_setdata, $result), LOG_ERR);
							}
						}
					}
                        }
                    }
				} else  {
					$this->DemandInfo->rollback();
					// 2015.02.18 k.yamada
					//$this->Session->setFlash(__('InputError', true), 'default', array('class' => 'error_inner'));
					if (count($error_no) > 0) {
						$error_message = "";
						foreach ( $error_no as $key => $val ) {
							$error_message .= ' 取次先' . $val;
						}
						$this->Session->setFlash (__('NotEmptyMailFax' , true).'['.$error_message.' ]', 'default', array ('class' => 'error_inner') );
					} else {
						$this->Session->setFlash(__('InputError', true), 'default', array('class' => 'error_inner'));
					}
				}

			} catch (Exception $e) {
				$this->DemandInfo->rollback();
				echo $e->getMessage();
			}

			// メール/FAX送信
            // 2017.04.18 判定にHash::getを追加
                        if (!$err_flg && Hash::get($data,'send_commission_info') == 1) {
				if (!$this->__send_mail_fax($data)) {
					$err_flg = 1;
					$this->Session->setFlash(__('SendError', true), 'default', array('class' => 'error_inner'));
				}
			}

			if (!$err_flg) {

				// 取次票送信日時の更新
				// $this->_update_commission_note_send_datetime($data);

				// 2015.07.28 s.harada ADD start ORANGE-680
                // 2017.04.18 判定にHash::getを追加
				if (Hash::get($data,'send_commission_info') == 1) {
					$this->_update_commission_send_mail_fax($data);
				}
				// 2015.07.28 s.harada ADD end ORANGE-680

// 2016.09.05 murata.s ORANGE-176 CHG(S)
				if($has_start_time_err){
					// 案件作成日と希望日が逆転
					$this->Session->setFlash(__('start_date_past', true), 'default', array('class' => 'warning_inner'));
				}else if(!$auction_none_flg){
					// 加盟店0件
					$this->Session->setFlash(__('AuctionAffNothing', true), 'default', array('class' => 'warning_inner'));
				} else {
					// 時間外
					if(!$auction_flg){
						$this->Session->setFlash(__('AuctionNgUpdated', true), 'default', array('class' => 'warning_inner'));
					} else {
						$this->Session->setFlash(__('Updated', true), 'default', array('class' => 'message_inner'));
					}
				}
// 2016.09.05 murata.s ORANGE-176 CHG(E)

				// 取次送信時はリダイレクト先のアンカーを指定
                // 2017.04.18 判定にHash::getを追加
				$anchor = (Hash::get($data,'send_commission_info') == 1) ? '' : '' ;

				$this->redirect('/demand/detail/' . $this->DemandInfo->id. $anchor);
			} else {
				$this->backToDetail($data);
			}
		}
	}

	public function auction_detail($demand_id){

		$conditions = array('AuctionInfo.demand_id'=>$demand_id);

		$joins = array (
				array (
						'fields' => '*',
						'type' => 'inner',
						"table" => "m_corps",
						"alias" => "MCorp",
						"conditions" => array (
								"AuctionInfo.corp_id = MCorp.id",
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
						"table" => "refusals",
						"alias" => "Refusal",
						"conditions" => array (
								"AuctionInfo.id = Refusal.auction_id"
						)
				),
		);

// 2016.11.10 murata.s ORANGE-236 CHG(S)
		// murata.s ORANGE-539 CHG(S)
		$results = $this->AuctionInfo->find('all',
				array( 'fields' => '*, MCorp.corp_name, MCorp.official_corp_name, VisitTime.visit_time, '
						.'Refusal.corresponds_time1, Refusal.corresponds_time2, Refusal.corresponds_time3, Refusal.cost_from, Refusal.cost_to, Refusal.other_contens,'
						.'Refusal.not_available_flg, Refusal.estimable_time_from, Refusal.contactable_time_from',
						'joins' =>  $joins,
						'conditions' => $conditions,
						'order' => array('AuctionInfo.id' => 'desc'),
				)
		);
		// murata.s ORANGE-539 CHG(E)
// 2016.11.10 murata.s ORANGE-236 CHG(E)

		$this->set("results" , $results);

		$this->layout = 'subwin';

	}

	/**
	 * カテゴリリストの取得(Ajax通信用)
	 *
	 * @param unknown_type $site
	 */
	// TODO: 使用していないので2次で削除
	/*
	public function category_list($site) {

		// Ajax通信以外は受け付けない
		if (!$this->request->is('ajax')) {
			throw new Exception();
		}

		$this->set('category_list', $this->MSiteCategory->getCategoriesBySite($site));

		$this->layout = 'ajax';
	}*/

	/**
	 * ヒアリング項目の取得(Ajax通信用)
	 *
	 * @param unknown_type $site
	 */
	// TODO: 使用していないので2次で削除
	/*
	public function inquiry_list($category = null) {

		// Ajax通信以外は受け付けない
		if (!$this->request->is('ajax')) {
			throw new Exception();
		}

		$conditions = array('category_id' => $category);

		$inquiry_list = $this->MInquiry->find('all', array('conditions' => $conditions, 'order' => array('MInquiry.id' => 'asc')));

		for ($i = 0; $i < count($inquiry_list); $i++) {
			$inquiry_list[$i]['MAnswer'] = $this->MAnswer->dropDownAnswer($inquiry_list[$i]['MInquiry']['id']);
		}

		$this->set('inquiry_list', $inquiry_list);

		$this->layout = 'ajax';
	}
	*/

	/**
	 * 削除
	 *
	 *
	 */
	private function __delete_demand($data){

		if(!empty($data['DemandInfo']['id'])){
			if(!$this->DemandInfo->deleteLogical ( $data['DemandInfo']['id'] )){
				return $this->redirect('/demand/detail/'.$data['DemandInfo']['id']);
			}
		}
		return $this->redirect('/demand/cancel');

	}

	/**
	 * 確認画面→詳細画面の戻り処理
	 *
	 * @param unknown_type $data
	 */
	private function backToDetail($data){

		// 2017.09.28 m-kawamoto ORANGE-420 ADD(S)
		//自動選定にて変更した内容をリストアする
		if(isset($data['restore_at_error']))
		{
			$data = array_replace_recursive($data, $data['restore_at_error']);
		}
		// 2017.09.28 m-kawamoto ORANGE-420 ADD(E)

		$regist_enabled = true;		// 案件状況活性制御
		// 詳細画面用にデータを整形
		$demand_info = $data['DemandInfo'];																			// 案件情報

		$commission_info = (array_key_exists('CommissionInfo', $data)) ? $data['CommissionInfo'] : array();			// 取次情報

		$visit_time = (array_key_exists('VisitTime', $data)) ? $data['VisitTime'] : array();						// 訪問日時

		$introduce_info = (array_key_exists('IntroduceInfo', $data)) ? $data['IntroduceInfo'] : array();			// 紹介先情報
		$m_inquiry = array();																						// ヒアリング項目
		if(array_key_exists('MInquiry', $data)){
			foreach($data['MInquiry'] as $key => $val){
				$m_inquiry[$key] = $val;
				$m_inquiry[$key] += array('DemandInquiryAnswer'=>$data['DemandInquiryAnswer'][$key]);
			}
		}
		$demand_correspond = (array_key_exists('DemandCorrespond', $data)) ? $data['DemandCorrespond'] : array();	// 案件対応履歴

		// 案件データの取得
		$disp_data = array();
		if (array_key_exists('id', $demand_info)) {
			$disp_data = $this->__set_demand($demand_info['id']);

			// JBR見積書、JBR領収書のリンク表示判定
			if (file_exists(Configure::read('estimate_file_path') . 'estimate_' . $demand_info['id'] . '.pdf')) {
				$this->set('estimate_file_url', '/download/estimate/estimate_' . $demand_info['id'] . '.pdf');
			}
			if (file_exists(Configure::read('receipt_file_path') . 'receipt_' . $demand_info['id'] . '.pdf')) {
				$this->set('receipt_file_url', '/download/receipt/receipt_' . $demand_info['id'] . '.pdf');
			}
		}
		if (array_key_exists('id', $demand_info)) {
			$regist_enabled = true;		// 案件状況活性制御
		} else if(!empty($demand_info['source_demand_id'])){
			$regist_enabled = false;		// 案件状況活性制御
			//案件番号を表示URL、同一顧客案件URLに代入
			$demand_info['same_customer_demand_url'] = Router::url('/demand/detail/' .$demand_info['source_demand_id'], true);
		}

		// 入力値で上書き
		$disp_data['DemandInfo'] = $demand_info;
		$disp_data['VisitTime'] = $visit_time;
		$disp_data['CommissionInfo'] = $commission_info;
// murata.s ORANGE-261 DEL(S)
// 成約ベースと紹介ベースを統合したことにより、$data['IntroduceInfo']は未使用となるため削除
// 		$disp_data['IntroduceInfo'] = $introduce_info;
// murata.s ORANGE-261 DEL(E)
		$disp_data['MInquiry'] = $m_inquiry;
		$disp_data['DemandCorrespond'] = $data['DemandCorrespond'];

		// ヒアリング回答項目データの取得
		$answer_list = array();
		for($i = 0; $i < count($disp_data['MInquiry']); $i++) {
			$answer_list[$i] = $this->__set_answer($disp_data['MInquiry'][$i]['DemandInquiryAnswer']['inquiry_id']);
		}
		$disp_data['MAnswer'] = $answer_list;

		// メール/FAX送信
		// 取次票送信ボタンに変わったためチェックボックスはなし。
		//if(array_key_exists('send', $data) && $data['send'] == 1){
		//	$disp_data['send'] = '1';
		//}

		$disp_data['regist'] = "regist";

// 2016.08.04 murata.s ORANGE-111 ADD(S)
		$disp_data['CommissionInfo_tmp_corp_id'] = $data['CommissionInfo_tmp_corp_id'];
// murata.s ORANGE-261 DEL(S)
// 		$disp_data['IntroduceInfo_tmp_corp_id'] = $data['IntroduceInfo_tmp_corp_id'];
// murata.s ORANGE-261 DEL(E)
// 2016.08.04 murata.s ORANGE-111 ADD(E)

		$this->data = $disp_data;

		$this->set('regist_enabled', $regist_enabled);				// 案件状況活性制御
		// ドロップダウン用リストの取得
		$this->set("site_list" , $this->MSite->getList());			// サイト一覧
		// TODO: ジャンルとサイトの紐付きの見直し
// murata.s ORANGE-480 CHG(S)
		// 2014.12.13 h.hara
		//$this->set("genre_list" , $this->MSiteCategory->getCategoriesBySite($this->data['DemandInfo']['site_id']));		// ジャンル一覧
		$this->set("genre_list" , $this->MSiteGenre->getGenreBySiteStHide($this->data['DemandInfo']['site_id']));					// ジャンル一覧
// murata.s ORANGE-480 CHG(E)
		$this->set("user_list" , $this->MUser->dropDownUser());		// ユーザー一覧
		// 2015.1.5 inokuchi
		//$this->set('category_list', $this->MSiteCategory->getCategoriesBySite($this->data['DemandInfo']['site_id']));
// murata.s ORANGE-480 CHG(S)
// 2016.07.14 murata.s ORANGE-121 CHG(S)
		$this->set('category_list', $this->MCategory->getListStHide($this->data['DemandInfo']['genre_id'], !empty($demand_info['id'])));				// カテゴリ一覧
// 2016.07.14 murata.s ORANGE-121 CHG(E)
// murata.s ORANGE-480 CHG(E)
		// 2015.4.7 n.kai ADD start
		// カテゴリ一覧、【クロスセル】元ジャンル一覧
		if(array_key_exists('cross_sell_source_site', $this->data['DemandInfo'])){
			$this->set('cross_sell_genre_list', $this->MSiteGenre->getGenreBySite($this->data['DemandInfo']['cross_sell_source_site']));
		} else {
			$this->set('cross_sell_genre_list', array());
		}
		// 2015.4.7 n.kai ADD end
		// カテゴリ一覧、【クロスセル】元カテゴリ一覧
		if(array_key_exists('cross_sell_source_site', $this->data['DemandInfo'])){
			$this->set('cross_sell_category_list', $this->MSiteCategory->getCategoriesBySite($this->data['DemandInfo']['cross_sell_source_site']));
		}else{
			$this->set('cross_sell_category_list', array());
		}

		// 2015.09.28 Inokuchi(S)
		$bid_situation = false;
		if(!empty($demand_info['id'])){
			$AuctionInfo_data = $this->AuctionInfo->findAllByDemandId($demand_info['id']);
			if(!empty($AuctionInfo_data)){
				$bid_situation = true;
			}
		}
		$this->set('bid_situation', $bid_situation);
		// 2015.09.28 Inokuchi(E)

// 2016.12.15 murata.s ORANGE-283 ADD(S)
		$selection_system_list = $this->__get_selection_system_list($this->data['DemandInfo']['genre_id'], $this->data['DemandInfo']['address1']);
		$this->set('selection_system_list', $selection_system_list);
// 2016.12.15 murata.s ORANGE-283 ADD(E)

		// 詳細画面表示
		$this->render('detail');
	}

	/**
	 * 対応履歴編集画面
	 *
	 */
	public function history_input($id = null) {

		// 取次先対応履歴データの検索条件
		$conditions = array('DemandCorrespond.id' => $id);
		// 取次先対応履歴データの取得
		$history_data = self::__get_demand_corresponds($conditions);

		if (isset($this->request->data['edit'])){
			// 編集
			// 更新日付のチェック
			if(self::__check_modified_demand_corresponds($id , $this->request->data['modified'])){
				$this->DemandCorrespond->begin();
				try {
					// 対応履歴の編集
					if(self::__edit_history($id , $this->request->data)){
						$this->set("end" , $id);  //ID
					}
					$this->DemandCorrespond->commit();
				} catch (Exception $e) {
					$this->DemandCorrespond->rollback();
				}
			} else {
				$this->data = $this->request->data;
				$this->Session->setFlash(__('ModifiedNotCheck', true), 'default', array('class' => 'error_inner'));
			}
		} else {
			$this->data = $history_data;
		}
		$this->layout = 'subwin';
		$this->set("user_list" , $this->MUser->dropDownUser());
	}

	/**
	 * キャンセル
	 *
	 */
	public function cancel() {
		$report = false;

		$data = $this->Session->read(self::$__sessionKeyForDemandParameter);

		// レポート管理から遷移した場合
		if (!isset($data)) {
			$ses = $this->Session->read(self::$__sessionKeyForReport);
			if(isset($ses)){
				$report = true;
				$data = $ses['named'];
			}
		}

		$para_data = '';
		if(isset($data['page'])){
			$para_data .= '/page:'.$data['page'];
		}
		if(isset($data['sort'])){
			$para_data .= '/sort:'.$data['sort'];
		}
		if(isset($data['direction'])){
			$para_data .= '/direction:'.$data['direction'];
		}

		if (!$report) {
			return $this->redirect('/demand_list/search'.$para_data);
		} else {
			return $this->redirect($ses['url'].$para_data);
		}


	}
        
        public function demand_file_download($id=null){
            if(!ctype_digit($id)) throw new NotFoundException();

            $this->autoRender = false;

            $options = array(
              'conditions' => array('DemandAttachedFile.id' => $id),
            );

            $aaf = $this->DemandAttachedFile->find('first', $options);

            //ファイルがない場合は404
            if(!$aaf)throw new NotFoundException();
            
            //ローカルファイルが存在すれば表示
            if (file_exists($aaf['DemandAttachedFile']['path'])) {
              $this->response->file(
                $aaf['DemandAttachedFile']['path'],
                array('name' => $aaf['DemandAttachedFile']['name'], 'download'=>true,)
               );
            }else{
              echo 'ファイルがありません。';
            }

          }
          
          /**
           * ファイルの削除処理
           *
           * @param unknown $corp_id
           * @param unknown $corp_agreement_id
           * @param number $file_id
           * @throws InternalErrorException
           */
          public function demand_file_delete($demand_id=null){

            $success_msg    = 'ファイルを削除しました。';                // 成功メッセージ
            $error_msg      = 'ファイルが存在しません。';                // ユーザエラーのメッセージ
            $s_msg_class    = array('class' => 'message_inner');         // 成功メッセージクラス
            $e_msg_class    = array('class' => 'error_inner');           // エラーメッセージクラス

            /* idの取得 */
            $file_id = $this->request->data['delete_demand_file'];

            /* 削除したいファイル情報をDBから取得 */
            $options = array(
                'conditions' => array(
                    'DemandAttachedFile.id'                => $demand_id,
                ),
            );
            $aaf = $this->DemandAttachedFile->find('first', $options);
            if (count($aaf) == 0) {
              throw new InternalErrorException();
            }

            /* 情報を変数に代入 */
            $file_path   = $aaf['DemandAttachedFile']['path'];
            $create_date = strtotime($aaf['DemandAttachedFile']['create_date']);
            
            /* トランザクション開始 */
            $this->DemandAttachedFile->begin();
            $this->CorpLisenseLink->begin();

            $del_attached_file = $this->DemandAttachedFile->findById($demand_id);

            /*
             *  DBからデータを削除
             * (片一方の情報がロストするのを防ぐため, DB->ファイルの順に削除し、こけたときrollback)
             */
            if (!$this->DemandAttachedFile->delete($demand_id)) {
              $this->DemandAttachedFile->rollback();
              $this->CorpLisenseLink->rollback();
              throw new InternalErrorException();
            }

            /*
             * 実ファイルの存在を確認
             * ファイルが存在しない場合は、ここでトランザクションを完了して、
             * ユーザエラーにする
             */
            if (!is_file($file_path)) {
              $this->DemandAttachedFile->commit();
              $this->CorpLisenseLink->commit();
              $error_msg = 'ファイルが存在しません。';
              $this->Session->setFlash ($error_msg, 'default', $e_msg_class);
              return $this->redirect('/demand/detail/'.$aaf['DemandAttachedFile']['demand_id']);
            }

            /* 実ファイルを削除 */
            if (!unlink($file_path)) {
              $this->DemandAttachedFile->rollback();
              $this->CorpLisenseLink->rollback();
              throw new InternalErrorException();
            }

            $this->DemandAttachedFile->commit();
            $this->CorpLisenseLink->commit();

            $this->Session->setFlash ($success_msg, 'default', $s_msg_class);
            return $this->redirect('/demand/detail/'.$aaf['DemandAttachedFile']['demand_id']);
          }
          
          /**
           * ファイルアップロード後の処理
           * ライセンスモードの時は該当のライセンスと紐づけて登録を行う
           *
           * @param unknown $corp_id
           * @param unknown $corp_agreement_id
           * @param number $license_mode
           * @throws InternalErrorException
           * @throws ForbiddenException
           */
          public function demand_file_upload($demand_id=null, $license_mode=0){

            /* 共通変数の初期化 */
            $today          = date('Y-m-d H:i:s');                       // 実行日の日付(なるべくユニークにするため秒まで)
            $user_id        = getmyuid();                                // 実行ユーザID
            $prefix         = '/var/www/htdocs_rits/rits-files';              // ファイル保存先のprefix
            $maxsize        = 20971520;                                  // アップロードの最大サイズ(20MB)
            $success_msg    = 'ファイルをアップロードしました。';        // 成功メッセージ
            $s_msg_class    = array('class' => 'message_inner');         // 成功メッセージクラス
            $e_msg_class    = array('class' => 'error_inner');           // エラーメッセージクラス
            $allow_type_arr = array(                                     // 許可するファイルタイプ
                'image/bmp'       => true,
                'image/jpeg'      => true,
                'image/png'       => true,
                'application/pdf' => true,
            );


// 2017.10.12 ozaki ORANGE-484 CHE,ADD(S)
            // アップロードファイルチェック
            $fileCnt = 0;
            for($x = 1; $x <= 5; $x++) {
                $input_data = '';
                $input_data = $this->request->params['form']['upload_file_path'.$x];     // 必要書類アップロード
                //選択ファイルがあれば加算
                if (!empty($input_data['name'])) {
                    $fileCnt++;
                }
            }

            /*
             * IE, Edge対策
             * ファイル名が指定されていない状況でアップロードボタンが押された場合ユーザエラー
             */
            if ($fileCnt == 0) {
                $msg = 'アップロード対象ファイルが選択されていません。';
                $this->Session->setFlash ($msg, 'default', $e_msg_class);
                return $this->redirect('/demand/detail/'.$demand_id);
            }

            /* トランザクション開始 */
            $this->DemandAttachedFile->begin();
            $this->CorpLisenseLink->begin();

            for($x = 1; $x <= 5; $x++) {
            /*
             * 
             * データの取得
             * 必要書類アップロードモードとライセンスアップロードモードで取得データを変える
             */
            $savedata = array();  // DB登録用配列の初期化
                $input_data = $this->request->params['form']['upload_file_path'.$x];     // 必要書類アップロード

            /*
                 * 選択ファイルが無ければ次の処理
             */
            if (empty($input_data['name'])) {
                    continue;
            }

            $ext = end(explode('.', $input_data['name']));  // 拡張子の取得

            /*
             * temporaryファイルのチェック
             * temporaryファイルが存在しないしない場合、読み込み不可の場合はシステムエラー
             */
            if (!is_file($input_data['tmp_name'])) {
              throw new InternalErrorException();
            }
            if (!is_readable($input_data['tmp_name'])) {
              throw new ForbiddenException();  // 表示されるのは404
            }

            /*
             * サイズ、ファイル形式が不正な場合はユーザエラー
             * 許可するファイル形式はPDF, JPEG, PNG, bitmap
             */
            if ($input_data['size'] > $maxsize) {
              $msg       = 'アップロードするファイルのサイズが大きすぎます。';
              $this->Session->setFlash ($msg, 'default', $e_msg_class);
              return $this->redirect('/demand/detail/'.$demand_id);
            }
            if (!isset($allow_type_arr[$input_data['type']])) {
              $msg = 'アップロードするファイルの形式が不正です。';
              $this->Session->setFlash ($msg, 'default', $e_msg_class);
              return $this->redirect('/demand/detail/'.$demand_id);
            }

            $savedir = $prefix . '/' . $demand_id . '/';

            if (!is_dir($savedir)){
              /* 保存ディレクトリがない場合は作成 */
              if (!mkdir($savedir, 0755, true)) {
                throw new InternalErrorException();
              }
            }
            if (!is_writable($savedir)) {
              throw new ForbiddenException();  // 表示されるのは404
            }

                // max id取得
                $box = $this->DemandAttachedFile->find('first', array("fields" => "MAX(id) as max_id"));

                if(count($box) > 0) $id = $box[0]['max_id'] + 1;
                else $id = 1;

            /* DB更新 */
                $savedata['DemandAttachedFile']['id']                = $id; // id取得
            $savedata['DemandAttachedFile']['demand_id']         = $demand_id;
            $savedata['DemandAttachedFile']['path']              = '';        // ここでは確定していないので空
            $savedata['DemandAttachedFile']['name']              = $input_data['name'];
            $savedata['DemandAttachedFile']['content_type']      = $input_data['type'];
            $savedata['DemandAttachedFile']['create_date']       = $today;    // 登録日は実行日になる
            $savedata['DemandAttachedFile']['create_user_id']    = $user_id;  // 登録ユーザは実行ユーザになる
            $savedata['DemandAttachedFile']['update_date']       = $today;    // 更新日は実行日時になる
            $savedata['DemandAttachedFile']['update_user_id']    = $user_id;  // 更新ユーザは実行ユーザになる

            /* DB登録 */
            if (!$this->DemandAttachedFile->save($savedata)) {
              $this->DemandAttachedFile->rollback();
              $this->CorpLisenseLink->rollback();
              throw new InternalErrorException();
            }

            $filepath = $savedir . $id . '.' . $ext;

            /* temporaryファイルをリネーム */
            if (!rename($input_data['tmp_name'], $filepath)) {
              $this->DemandAttachedFile->rollback();
              $this->CorpLisenseLink->rollback();
              throw new InternalErrorException();
            }

            /* DBにフルパスを登録 */
            $update_data = array();
            $update_data['DemandAttachedFile']['id']   = $id;
            $update_data['DemandAttachedFile']['path'] = $filepath;
            if (!$this->DemandAttachedFile->save($update_data)) {
              $this->DemandAttachedFile->rollback();
              $this->CorpLisenseLink->rollback();
              unlink($filepath);
              throw new InternalErrorException();
            }
            }

            // すべてが問題ないならコミットする
            $this->DemandAttachedFile->commit();
            $this->CorpLisenseLink->commit();
// 2017.10.12 ozaki ORANGE-484 CHE,ADD(E)


            // 2017/08/09 ichino start ファイルアップロード後のメール送信を無効化
            //$this->__send_upload_file_alert($demand_id);
            // 2017/08/09 ichino end
            $this->Session->setFlash ($success_msg, 'default', $s_msg_class);
            return $this->redirect('/demand/detail/'.$demand_id);
          }
        
        private function __send_upload_file_alert($demand_id) {
            /* 値の設定 */
            $url      = "https://".DOMAIN_NAME."/affiliation/agreement/" . $demand_id;
            $to_addr  = Util::getDivText('agreement_upload_mail_setting', 'to_address');
            $header   = 'From: ' . Util::getDivText('agreement_upload_mail_setting', 'from_address');
            $tmp_sub  = Util::getDivText('agreement_upload_mail_setting', 'title');
            $template = Util::getDivText('agreement_upload_mail_setting', 'contents');
            $subject = sprintf($tmp_sub, $demand_id);
            $body = sprintf($template, $url);
            /* メールの送信 */
            mb_send_mail($to_addr, $subject, $body, $header);
            return;
          }
        
	/**
	 * クロスセル案件のidを取得
	 * MSite
	 * @param unknown クロスセル案件を指定
	 * @return unknown
	 */
	private function __set_site_id($site_name){

		$conditions = array('site_name' => $site_name);

		$site_name = $this->MSite->find('first',
			array('conditions' => $conditions,
				'fields' => 'id',
			)
		);

		return $site_name['MSite']['id'];
	}

	/**
	 * 案件データの既定値設定
	 *
	 * @return unknown
	 */
	private function __set_pre_demand($customer_tel, $site_tel){

		// CTI連携項目から既定値を取得・設定する

		// サイト電話番号からサイトIDを取得
		$conditions = array('site_tel' => $site_tel);
		$site_info = $this->MSite->find('first',
							 array('conditions'=>$conditions,
							 		'fields' => array('id'),
									'order' => 'MSite.id asc'
							)
		);
		$conditions_cus_tel = array('customer_tel' => $customer_tel);
		$demand_status_info = $this->DemandInfo->find('first',
								array('conditions' => $conditions_cus_tel,
										'fields' => array('demand_status'),
										'order' => 'DemandInfo.demand_status asc'
								)
		);

		if (0 < count($demand_status_info)) {
			$d_status_info = $demand_status_info['DemandInfo']['demand_status'];
		} else {
			$d_status_info = "";
		}

		if (0 < count($site_info)) {
			$site_id = $site_info['MSite']['id'];
		} else {
			$site_id = "";
		}
		// 表示データ設定
		$user = $this->Auth->user();
		$results = array(
				'DemandInfo' => array(
					'customer_tel' => $customer_tel,
					'site_id' => $site_id,
					'receptionist' => $user['id'],
					'demand_status' => $d_status_info,
				),
		);

		return $results;
	}

	/**
	 * ID別に案件データの取得
	 *
	 * @param unknown $id
	 * @return unknown
	 */
	private function __set_demand($id){

		$this->DemandInfo->recursive = 2;

		$results = $this->DemandInfo->findById($id);

		if(array_key_exists('CommissionInfo', $results)){
			foreach ($results['CommissionInfo'] as $key => $value){
// 				 $data = self::__get_holiday_and_fee_data($value['MCorp']['id'] , $results['DemandInfo']['category_id']);
				// ジャンル・カテゴリが空の場合の為に修正
				$data = self::__get_holiday_data($value['MCorp']['id']);

				$category_data = self::__get_fee_data($value['MCorp']['id'] , $results['DemandInfo']['category_id']);

				if(empty($category_data)){
//				if( empty($category_data['MCorpCategory']['order_fee']) || empty($category_data['MCorpCategory']['order_fee_unit'])){

					$mcategory_data['MCorpCategory'] = array('order_fee' => '' , 'order_fee_unit' => '', 'note' => '');
//					// m_corp_categoriesから取得できなかった場合、m_categoriesから取得する
//					$mc_category_data = self::__get_fee_data_m_categories($results['DemandInfo']['category_id']);
//					if(empty($mc_category_data)){
//						// m_categoriesからも取得できなかった場合、空白とする。
//						$mc_category_data['MCategory'] = array('category_default_fee' => '' , 'category_default_fee_unit' => '');
//					}
//					$category_data['MCorpCategory']['order_fee'] = $mc_category_data['MCategory']['category_default_fee'];
//					$category_data['MCorpCategory']['order_fee_unit'] = $mc_category_data['MCategory']['category_default_fee_unit'];
//					$category_data['MCorpCategory']['note'] = '';
				}
				$data['MCorpCategory'] = $category_data['MCorpCategory'];
				$results['CommissionInfo'][$key]['AffiliationInfo']['attention'] = $data['AffiliationInfo']['attention'];
				$results['CommissionInfo'][$key]['MCorp']['holiday'] = $data['MCorp']['holiday'];
				// 2017.04.12 s.izumi ORANGE-383 CHG(S)【加盟店管理】年末年始状況のGWへの変更
				$results['CommissionInfo'][$key]['MCorpNewYear'] = $data['MCorpNewYear'];
				// 2017.04.12 s.izumi ORANGE-383 CHG(E)【加盟店管理】年末年始状況のGWへの変更
				$results['CommissionInfo'][$key]['MCorpCategory']['order_fee'] = $data['MCorpCategory']['order_fee'];
				$results['CommissionInfo'][$key]['MCorpCategory']['order_fee_unit'] = $data['MCorpCategory']['order_fee_unit'];
				$results['CommissionInfo'][$key]['MCorpCategory']['note'] = $data['MCorpCategory']['note'];
				// murata.s ORANGE-261 ADD(S)
				$results['CommissionInfo'][$key]['MCorpCategory']['introduce_fee'] = $data['MCorpCategory']['introduce_fee'];
				$results['CommissionInfo'][$key]['MCorpCategory']['corp_commission_type'] = $data['MCorpCategory']['corp_commission_type'];
				// murata.s ORANGE-261 ADD(E)
			}
		}

		// 訪問日時の取得
		$data = $this->VisitTime->find ( 'all', array (
				'fields' => '*, AuctionInfo.id',
				'joins' => array (
						array (
								'fields' => '*',
								'type' => 'left',
								"table" => "auction_infos",
								"alias" => "AuctionInfo",
								"conditions" => array (
									'VisitTime.id = AuctionInfo.visit_time_id'
								)
						)
				),
				'conditions' => array('VisitTime.demand_id' => $id),
				'order' => array (
						'VisitTime.visit_time' => 'asc'
				)
		) );

		$visit_time_data = '';
		foreach ($data as $key => $value){
			$results['VisitTime'][$key]['id'] = $value['VisitTime']['id'];
			$results['VisitTime'][$key]['visit_time'] = $value['VisitTime']['visit_time'];
			$results['VisitTime'][$key]['is_visit_time_range_flg'] = $value['VisitTime']['is_visit_time_range_flg'];
			$results['VisitTime'][$key]['visit_time_from'] = $value['VisitTime']['visit_time_from'];
			$results['VisitTime'][$key]['visit_time_to'] = $value['VisitTime']['visit_time_to'];
			$results['VisitTime'][$key]['visit_adjust_time'] = $value['VisitTime']['visit_adjust_time'];
			$results['VisitTime'][$key]['visit_time_before'] = ($value['VisitTime']['is_visit_time_range_flg'] == 0) ? $value['VisitTime']['visit_time'] : $value['VisitTime']['visit_time_from'];
			$results['VisitTime'][$key]['commit_flg'] = !empty($value['AuctionInfo']['id'])? 1 : 0 ;
			if(!empty($value['AuctionInfo']['id'])){
				$visit_time_data = $value['VisitTime']['visit_time'];
			}
		}

		$results['DemandInfo']['priority_before'] = $results['DemandInfo']['priority'];
		$results['DemandInfo']['contact_desired_time_before'] = $results['DemandInfo']['contact_desired_time'];
		$results['DemandInfo']['selection_system_before'] = $results['DemandInfo']['selection_system'];
		// murata.s ORANGE-486 ADD(S)
		$results['DemandInfo']['demand_status_before'] = $results['DemandInfo']['demand_status'];
		// murata.s ORANGE-486 ADD(E)

		$results['DemandInfo']['follow_tel_date'] = '';
		if(!empty($visit_time_data)){
			// フォロー時間設定を取得
			$follow_data = $this->MTime->find('all', array('conditions' => array('item_category' => 'follow_tel')));
			$results['DemandInfo']['follow_tel_date'] = $this->MTime->getFollowTimeWithData($results['DemandInfo']['auction_start_time'], $visit_time_data, $follow_data);
		}

		return $results;
	}

	/**
	 * ID別にヒアリング内容データの取得
	 *
	 * @param unknown $id
	 * @return unknown
	 */
	private function __set_answer($id){

		$results = $this->MAnswer->dropDownAnswer($id);

		return $results;
	}

	/**
	 * 案件情報更新日時のチェック
	 *
	 * @param unknown $data
	 * @return boolean
	 */
	private function __check_modified_demand($data){


		// 更新時のみ(入力データに案件IDを保持している場合のみ)チェック
		if (array_key_exists('id', $data)) {
			// 現在の案件情報レコードを取得
			$current_data = $this->DemandInfo->findById($data['id']);

			// 更新日時をチェック
			if ($data['modified'] != $current_data['DemandInfo']['modified']) {
				return false;
			}
		}

		return true;
	}

	/**
	 * 案件情報の更新
	 *
	 * @param unknown $data
	 */
	private function __update_demand($data){

		// 案件情報の登録
		$save_data = $data['DemandInfo'];

		// 更新用データの整形
		// 案件状況が失注以外の場合、登録されている「失注理由」はNULL更新(失注以外の場合は失注理由はPOSTされない)
		if (!array_key_exists('order_fail_reason', $save_data)) {
			$save_data['order_fail_reason'] = null;
		}
		// サイト名がクロスセル案件以外の場合、登録されている「【クロスセル】元サイト」「【クロスセル】元カテゴリ」はNULL更新(クロスセル案件以外の場合はPOSTされない)
		if (!array_key_exists('cross_sell_source_site', $save_data)) {
			$save_data['cross_sell_source_site'] = null;
		}
		// 2015.4.7 n.kai ADD start
		// サイト名がクロスセル案件以外の場合、登録されている「【クロスセル】元ジャンル」はNULL更新(クロスセル案件以外の場合はPOSTされない)
		if (!array_key_exists('cross_sell_source_genre', $save_data)) {
			$save_data['cross_sell_source_genre'] = null;
		}
		// 2015.4.7 n.kai ADD end
		if (!array_key_exists('cross_sell_source_category', $save_data)) {
			$save_data['cross_sell_source_category'] = null;
		}
		// TODO: ジャンルIDにカテゴリIDをコピー(フェーズ1の暫定対応)
		// TODO: 使用していないので2次で削除
		//$save_data['genre_id'] = $save_data['category_id'];
		// 更新日時のクリア
		if (array_key_exists('modified', $save_data)) {
			unset($save_data['modified']);
		}

		// 案件状況を進行中に変更した場合
                // 2017.04.18 判定にHash::getを追加
                //2017/07/11  ichino ORANGE-420 ADD start 自動取次した場合は、対象外とする
		if (Hash::get($save_data,'demand_status') == 4 || Hash::get($save_data,'demand_status') == 5 && Hash::get($save_data,'do_auto_selection_category') == 0) {
                //2017/07/11  ichino ORANGE-420 ADD end
			if ($limitover_time = $this->__get_limitover_time($save_data['id'])) {
				$save_data['commission_limitover_time'] = $limitover_time;
			}
		}

		$result = $this->DemandInfo->save($save_data, false);

// 2016.11.29 murata.s ORANGE-259 CHG(S)
// 2016.12.15 murata.s ORANGE-283 CHG(S)
		if(($save_data['selection_system'] == Util::getDivValue('selection_type', 'AutomaticAuctionSelection')
				&& $data['DemandInfo']['selection_system_before'] === '')
				|| !empty($data['DemandInfo']['do_auto_selection'])){
// 2016.12.15 murata.s ORANGE-283 CHG(E)
			$save_data2 = array();
			$save_data2['id'] = $result['DemandInfo']['id'];
			$save_data2['modified_user_id'] = 'AutomaticAuction';
			//$save_data2['modified'] = date('Y-m-d H:i:s');
			$save_data2['created_user_id'] = 'AutomaticAuction';
			//$save_data2['created'] = date('Y-m-d H:i:s');

			$this->DemandInfo->save($save_data2, array('callbacks' => false));
		}
// 2016.11.29 murata.s ORANGE-259 CHG(E)

                //2017/06/08  ichino ORANGE-420 ADD start 
                //地域×カテゴリで加盟店を指定した事をあらわす
                if(Hash::get($data , 'DemandInfo.do_auto_selection_category') == 1){
                    
                    $save_data3 = array();
                    $save_data3['id'] = $result['DemandInfo']['id'];
                    $save_data3['modified_user_id'] = 'AutoCommissionCorp';
                    $save_data3['created_user_id'] = 'AutoCommissionCorp';
		
                    $this->DemandInfo->save($save_data3, array('callbacks' => false));
                }
                //2017/06/08  ichino ORANGE-420 ADD end
                
		// saveメソッドは更新成功時、登録データ、またはtrueを返す
		if (is_array($result) || $result == true) {
			// [JBR]見積書、領収書ファイルのアップロード
			// 2015.2.14 inokuchi (取次管理に移動)
// 			if (!array_key_exists('id', $data['DemandInfo'])) {
// 				$data['DemandInfo']['id'] = $this->DemandInfo->id;
// 			}
// 			if (!$this->__uplaod_file($data)) {
// 				return false;
// 			}
			return true;
		} else {
			return false;
		}
	}

	/**
	 * 取次リミット超過時間を分換算で習得
	 *
	 * @param  int  $id
	 * @return int
	 */
	private function __get_limitover_time($id) {
		$this->DemandInfo->bindModel(
			array('belongsTo' => array(
				'MGenre' => array(
					'className' => 'MGenre',
					'foreignKey' => 'genre_id'
				)
			    )
			)
		);

		$this->DemandInfo->setVirtualOverLimit();
		$demand = $this->DemandInfo->findById($id);
		$this->DemandInfo->setVirtualOverLimit(false);

		// 取次リミット超過秒を分に変換して返す
		return round($demand['DemandInfo']['limit_over_sec'] / 60);
	}

	/**
	 * 案件別ヒアリング項目の更新
	 *
	 * @param unknown $data
	 */
	private function __update_demand_inquiry_answer($data){

		// 案件別ヒアリング項目が未入力ならなにもしない
		if (!array_key_exists('DemandInquiryAnswer', $data)) {
			return true;
		}

		// 案件IDの取得
		$demand_id = (array_key_exists('id', $data['DemandInfo'])) ? $data['DemandInfo']['id'] : $this->DemandInfo->id;

		// 取次先情報の登録
		$save_data = array();
		foreach ($data['DemandInquiryAnswer'] as $key => $val){
			// ヒアリング項目IDの有無で登録要否を判断
			if(!empty($val['inquiry_id'])) {
				// 案件ID、ヒアリング項目IDで登録済みデータの有無を確認
				$current_data = $this->DemandInquiryAnswer->find('first', array('conditions'=>array('demand_id'=>$demand_id, 'inquiry_id'=>$val['inquiry_id'])));

				// 更新 or 登録判定
				if (0 < count($current_data)) {
					// 更新の場合
					$save_data[$key]['id'] = $current_data['DemandInquiryAnswer']['id'];
					$save_data[$key]['answer_note'] = $val['answer_note'];
				} else {
					// 新規登録の場合
					$save_data[$key]['demand_id'] = $demand_id;
					$save_data[$key]['inquiry_id'] = $val['inquiry_id'];
					$save_data[$key]['answer_note'] = $val['answer_note'];
				}
			}
		}

		if (0 < count($save_data)) {
			return $this->DemandInquiryAnswer->saveAll($save_data);
		}

	}

	/**
	 * 紹介手数料の取得
	 *
	 * @param unknown_type $corp_id
	 * @param unknown_type $category_id
	 */
	private function __get_introduce_fee($corp_id, $category_id) {

		// 企業別対応カテゴリマスタを企業ID、カテゴリIDで検索
		$conditions = array('corp_id'=>$corp_id, 'category_id'=>$category_id);

		$result = $this->MCorpCategory->find('first',
				array(
					'conditions' => $conditions,
					'fields' => 'introduce_fee',
				)
		);

		// 2016.3.29 sasaki@tobila.com MOD start ORANGE-1336
		if (count($result) && !is_null($result['MCorpCategory']['introduce_fee'])) {
			//return $result['MCorpCategory']['introduce_fee'];
			return intval($result['MCorpCategory']['introduce_fee']);
		} else {
			$default_fee = $this->MCategory->getDefault_fee($category_id);
			if(
				!is_null($default_fee['category_default_fee'])
				&& $default_fee['category_default_fee_unit'] == 0
			){
				return intval($default_fee['category_default_fee']);
			}
			// 手数料の単位が1：手数料率の場合はゼロを返す。
			return 0;
		}
		// 2016.3.29 sasaki@tobila.com MOD end ORANGE-1336
	}

	/**
	 * 消費税率の取得
	 *
	 */
	private function __get_tax_rate() {

		// 企業別対応カテゴリマスタを企業ID、カテゴリIDで検索
		$conditions = array('start_date <= '=>date('Y/m/d'), 'end_date >= '=>date('Y/m/d'));

		$result = $this->MTaxRate->find('first',
				array(
					'conditions' => $conditions,
					'fields' => 'tax_rate',
				)
		);

		if (count($result)) {
			return $result['MTaxRate']['tax_rate'];
		} else {
			return 0;
		}
	}

	/**
	 * 休業日を取得
	 *
	 * @param unknown $id
	 */
	private function __get_holiday_and_fee_data($id , $category_id){

		$conditions = array('MCorp.id'=> $id);
		$fields_holiday = '(SELECT ARRAY_TO_STRING(ARRAY( SELECT item_name FROM m_items INNER JOIN m_corp_subs ON m_corp_subs.item_category = m_items.item_category AND m_corp_subs.item_id = m_items.item_id WHERE m_corp_subs.item_category = \''.__('holiday' , true).'\' AND m_corp_subs.corp_id = "MCorp"."id" ORDER BY m_items.sort_order ASC ),\'｜\') as "MCorp__holiday" )';
		$results = $this->MCorp->find ( 'first', array (
				'conditions' => $conditions,
				'joins' => array (
						array (
								'table' => 'm_corp_categories',
								'alias' => "MCorpCategory",
								'type' => 'left',
								'conditions' => array (
										'MCorp.id = MCorpCategory.corp_id',
										'MCorpCategory.category_id ='.$category_id
								)
						),
						array (
								'table' => 'affiliation_infos',
								'alias' => "AffiliationInfo",
								'type' => 'left',
								'conditions' => array (
										'MCorp.id = AffiliationInfo.corp_id',
								)
						)
				),
				'fields' => $fields_holiday.', AffiliationInfo.attention, MCorpCategory.order_fee, MCorpCategory.order_fee_unit, MCorpCategory.note'
		) );

		return $results;
	}

	/**
	 * 休業日を取得
	 *
	 * @param unknown $id
	 */
	private function __get_holiday_data($id){
		// 2017.04.12 s.izumi ORANGE-383 CHG(S)【加盟店管理】年末年始状況のGWへの変更
		$conditions = array('MCorp.id'=> $id);
		$this->MCorp->virtualFields['holiday'] = 'SELECT ARRAY_TO_STRING(ARRAY( SELECT item_name FROM m_items INNER JOIN m_corp_subs ON m_corp_subs.item_category = m_items.item_category AND m_corp_subs.item_id = m_items.item_id WHERE m_corp_subs.item_category = \''.__('holiday' , true).'\' AND m_corp_subs.corp_id = "MCorp"."id" ORDER BY m_items.sort_order ASC ),\'｜\')';
		$results = $this->MCorp->find ( 'first', array (
				'conditions' => $conditions,
				'joins' => array (
						array (
								'table' => 'affiliation_infos',
								'alias' => "AffiliationInfo",
								'type' => 'left',
								'conditions' => array (
										'MCorp.id = AffiliationInfo.corp_id',
								)
						),
						array (
								'table' => 'm_corp_new_years',
								'alias' => "MCorpNewYear",
								'type' => 'left',
								'conditions' => array (
										'MCorp.id = MCorpNewYear.corp_id',
								)
						),
				),
				'fields' => array(
						'MCorp.holiday',
						'MCorpNewYear.label_01',
						'MCorpNewYear.status_01',
						'MCorpNewYear.label_02',
						'MCorpNewYear.status_02',
						'MCorpNewYear.label_03',
						'MCorpNewYear.status_03',
						'MCorpNewYear.label_04',
						'MCorpNewYear.status_04',
						'MCorpNewYear.label_05',
						'MCorpNewYear.status_05',
						'MCorpNewYear.label_06',
						'MCorpNewYear.status_06',
						'MCorpNewYear.label_07',
						'MCorpNewYear.status_07',
						'MCorpNewYear.label_08',
						'MCorpNewYear.status_08',
						'MCorpNewYear.label_09',
						'MCorpNewYear.status_09',
						'MCorpNewYear.label_10',
						'MCorpNewYear.status_10',
						'MCorpNewYear.note',
						'AffiliationInfo.attention',
				)
		) );
		// 2017.04.12 s.izumi ORANGE-383 CHG(E)【加盟店管理】年末年始状況のGWへの変更
		return $results;
	}

	/**
	 * 手数料を取得
	 *
	 * @param unknown $id
	 */
	private function __get_fee_data($id, $category_id){

		$results = array();

		if (empty($category_id)) {
			return $results;
		}

		$conditions = array('MCorp.id'=> $id);
// murata.s ORANGE-261 CHG(S)
		$results = $this->MCorp->find ( 'first', array (
				'conditions' => $conditions,
				'joins' => array (
						array (
								'table' => 'm_corp_categories',
								'alias' => "MCorpCategory",
								'type' => 'left',
								'conditions' => array (
										'MCorp.id = MCorpCategory.corp_id',
										'MCorpCategory.category_id ='.$category_id
								)
						),
				),
				'fields' => 'MCorpCategory.order_fee, MCorpCategory.order_fee_unit, MCorpCategory.note, MCorpCategory.introduce_fee, MCorpCategory.corp_commission_type'
		) );
// murata.s ORANGE-261 CHG(E)

		return $results;
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
	 * 請求情報取得
	 *
	 * @param string $demand_id
	 * @return multitype:|unknown
	 */
	private function __get_bill_info_data($demand_id = null){
		if (empty ( $demand_id )) {
			return '';
		}
// 2016.11.07 murata.s ORANGE-241 CHG(S)
		$conditions = array (
				'BillInfo.demand_id' => $demand_id,
				'BillInfo.auction_id' => null
		);
// 2016.11.07 murata.s ORANGE-241 CHG(E)
		$results = $this->BillInfo->find ( 'first', array (
				'conditions' => $conditions
		) );

		return $results;
	}

	/**
	 * 取次情報取得
	 *
	 * @param string $demand_id
	 * @return multitype:|unknown
	 */
	private function __get_commission_info_data($demand_id = null){
		if (empty ( $demand_id )) {
			return '';
		}

		$conditions = array (
				'CommissionInfo.demand_id' => $demand_id
		);

		$results = $this->CommissionInfo->find ('all',
				array(
						'conditions' => $conditions
				)
		);

		return $results;
	}

	/**
	 * 施工完了日から消費税の取得
	 *
	 * @param string $data
	 * @return Ambigous <number, string>
	 */
	private function __get_complete_tax_rate($data = null) {

		if(!empty($data)){
			$conditions = array('start_date <=' => $data ,
					'or' => array("end_date = ''",
							'end_date >=' => $data),
			);
			$results = $this->MTaxRate->find('first',
					array( 'conditions' => $conditions,)
			);
			$tax_rate = $results['MTaxRate']['tax_rate']*100;
		} else {
			$tax_rate = '';
		}
		return $tax_rate;
	}

	/**
	 * 請求更新
	 *
	 * @param unknown $before_data  請求過去データ
	 * @param string $complete_date  施工完了日
	 * @param string $data  対象データ
	 * @param string $type  更新タイプ 0:円, 1:％
	 * @return boolean
	 */
	private function __updata_bill_info_data($before_data = array() , $complete_date = null , $data = null , $type = null){

		$tax_rate = self::__get_complete_tax_rate($complete_date);

		if(empty($tax_rate)){
			return false;
		}

		if($type == 0){
			$bill_data['fee_tax_exclude'] = $data;

		} else if($type == 1){
			$bill_data['comfirmed_fee_rate'] = $data;
			$bill_data['fee_tax_exclude'] = round($before_data['fee_target_price']*($data*0.01));
		}
		$bill_data['tax'] = round($bill_data['fee_tax_exclude']*($tax_rate/100));
		$bill_data['total_bill_price'] = $bill_data['fee_tax_exclude'] + $bill_data['tax'];
		$bill_data['id'] = $before_data['id'];

		if($this->BillInfo->save($bill_data)){
			return true;
		}

		return false;
	}

	/**
	 * 訪問日付の更新
	 *
	 * @param unknown $data
	 * @return boolean
	 */
	private function __update_visit_time($data){

		// 訪問日付が未入力ならなにもしない
		if (!array_key_exists('VisitTime', $data)) {
			return true;
		}
		// 案件IDの取得
		$demand_id = (array_key_exists('id', $data['DemandInfo'])) ? $data['DemandInfo']['id'] : $this->DemandInfo->id;

		// 訪問日付情報の登録
		$visit_time_data = array();

		foreach ($data['VisitTime'] as $key => $val) {
			if ($val['is_visit_time_range_flg'] == 0) {
				if (!empty($val['visit_time'])) {
					$visit_time_data[$key]['id'] = $val['id'];
					$visit_time_data[$key]['demand_id'] = $demand_id;
					$visit_time_data[$key]['visit_time'] = $val['visit_time'];
					$visit_time_data[$key]['is_visit_time_range_flg'] = $val['is_visit_time_range_flg'];
					if (!empty($data['DemandInfo']['do_auction'])) {
						$visit_time_data[$key]['do_auction'] = 1;
					}
				} else if (!empty($val['id']) && empty($val['visit_time'])) {
					$this->VisitTime->delete($val['id']);
				}
			} else if ($val['is_visit_time_range_flg'] == 1) {
				if (!empty($val['visit_time_from']) && !empty($val['visit_time_to'])) {
					$visit_time_data[$key]['id'] = $val['id'];
					$visit_time_data[$key]['demand_id'] = $demand_id;
					$visit_time_data[$key]['visit_time_from'] = $val['visit_time_from'];
					$visit_time_data[$key]['visit_time_to'] = $val['visit_time_to'];
					$visit_time_data[$key]['is_visit_time_range_flg'] = $val['is_visit_time_range_flg'];
					$visit_time_data[$key]['visit_adjust_time'] = $val['visit_adjust_time'];

					if (!empty($data['DemandInfo']['do_auction'])) {
						$visit_time_data[$key]['do_auction'] = 1;
					}
				} else if (!empty($val['id']) && (empty($val['visit_time_from']) || empty($val['visit_time_to']))) {
					$this->VisitTime->delete($val['id']);
				}
			}
		}

		if (0 < count($visit_time_data)) {
			return $this->VisitTime->saveAll($visit_time_data);
		} else {
			// 登録対象データがない場合も正常終了
			return true;
		}

	}

	/**
	 * 取次先情報の更新
	 *
	 * @param unknown $data
	 * @param unknown $error_no
	 */
	// 2015.02.18 k.yamada
	//private function __update_commission($data){
	private function __update_commission($data, &$error_no){

// murata.s ORANGE-261 CHG(S)
		// 取次先情報が未入力ならなにもしない
		if (!array_key_exists('CommissionInfo', $data)) {
			return true;
		}
		// 取次形態 = "成約ベース"がなければなにもしない
		$commission_info = array_filter($data['CommissionInfo'], function($v){
			return $v['commission_type'] != 1;
		});
		if(empty($commission_info)) return true;
// murata.s ORANGE-261 CHG(E)

		// 案件IDの取得
		$demand_id = (array_key_exists('id', $data['DemandInfo'])) ? $data['DemandInfo']['id'] : $this->DemandInfo->id;

		$bill_info_data = self::__get_bill_info_data($demand_id);

		// 2015.12.29 n.kai ADD start ORANGE-1027
		// 訪問日付が未入力ならなにもしない
		if (array_key_exists('VisitTime', $data)) {
			// 訪問希望日時のIDをcommission_infosに格納するため登録したvisit_timesを取得
			$visittime_data = $this->VisitTime->findByDemandId($demand_id);
		}
		// 2015.12.29 n.kai ADD end ORANGE-1027

		// 取次先情報の登録
		$commission_data = array();
		// 2015.02.18 k.yamada
		$cnt = 0;
		$saveSelectFlg = true;
// murata.s ORANGE-261 CHG(S)
		foreach ($commission_info as $key => $val){
			// 2015.02.18 k.yamada
			$cnt++;
			// 取次先企業IDの有無で登録要否を判断
			if(!empty($val['corp_id'])) {
				/*
				// 案件ID、企業IDで登録済みデータの有無を確認
				$current_data = $this->CommissionInfo->find('first', array('conditions'=>array('demand_id'=>$demand_id, 'corp_id'=>$val['corp_id'], 'commission_type'=>Util::getDivValue('commission_type', 'normal_commission'))));
				*/
				// 失注フラグ
				$lost_flg = false;
				$corp = $this->MCorp->findById($val['corp_id']);

				// 企業名が失注用の場合、取次ステータスを失注で作成
				if ($corp['MCorp']['corp_name'] == Configure::read('lost_corp_name')) {
					$lost_flg = true;
				}

				// 2015.02.18 k.yamada
				// 企業マスタのFAX番号、PCメールが設定されているかチェックする
				if ($data['send_commission_info'] == 1) {
					$coordination_method = $corp['MCorp']['coordination_method'];
					if ($coordination_method == Util::getDivValue('coordination_method', 'mail_fax')) {
						if (empty($corp['MCorp']['fax']) && empty($corp['MCorp']['mailaddress_pc'])) {
							array_push($error_no, $cnt);
						}
					} else if ($coordination_method == Util::getDivValue('coordination_method', 'mail')) {
						if (empty($corp['MCorp']['mailaddress_pc'])) {
							array_push($error_no, $cnt);
													}
					} else if ($coordination_method == Util::getDivValue('coordination_method', 'fax')) {
						if (empty($corp['MCorp']['fax'])) {
							array_push($error_no, $cnt);
						}
					}
				}

				/*
				// 更新 or 登録判定
				if (0 < count($current_data)) {
				*/
				if(!empty($val['id'])){
					// 更新の場合
					$commission_data[$key]['id'] = $val['id'];
					$commission_data[$key]['corp_id'] = $val['corp_id'];
					$commission_data[$key]['commit_flg'] = $val['commit_flg'];
					$commission_data[$key]['appointers'] = $val['appointers'];
// 2017.10.12 noguchi ORANGE-503 DEL,ADD(S)
//					$commission_data[$key]['tel_commission_datetime'] = $val['tel_commission_datetime'];
//					$commission_data[$key]['tel_commission_person'] = $val['tel_commission_person'];
					$commission_data[$key]['corp_claim_flg'] = $val['corp_claim_flg'];
// 2017.10.12 noguchi ORANGE-503 DEL,ADD(D)
					$commission_data[$key]['commission_note_send_datetime'] = $val['commission_note_send_datetime'];
					$commission_data[$key]['commission_note_sender'] = $val['commission_note_sender'];
					$commission_data[$key]['first_commission'] = $val['first_commission'];
					$commission_data[$key]['unit_price_calc_exclude'] = $val['unit_price_calc_exclude'];
					$commission_data[$key]['lost_flg'] = $val['lost_flg'];
					$commission_data[$key]['del_flg'] = $val['del_flg'];
					$commission_data[$key]['select_commission_unit_price_rank'] = $val['select_commission_unit_price_rank'];
					$commission_data[$key]['select_commission_unit_price'] = $val['select_commission_unit_price'];  //ORANGE-999
					// 2015.12.29 n.kai ADD start ORANGE-1027
					if(isset($visittime_data['VisitTime'])){
						$commission_data[$key]['commission_visit_time_id'] = $visittime_data['VisitTime']['id'];  //ORANGE-1027
					}
					// 2015.12.29 n.kai ADD end ORANGE-1027

					if(isset($val['auto_select_flg'])){
						$commission_data[$key]['auto_select_flg'] = $val['auto_select_flg'];
					}

					//
					if(isset($val['corp_fee'])){
						$commission_data[$key]['corp_fee'] = $val['corp_fee'];
// 2017.01.04 murata.s ORANGE-244 DEL(S)
// 						// 2015/3/10 inokuchi
// 						//$commission_data[$key]['commission_fee_rate'] = '';
// 						if(!empty($bill_info_data) && !empty($val['complete_date']) && $val['commission_status'] == Util::getDivValue('construction_status', 'construction')){
// 							if(empty($bill_info_data['BillInfo']['irregular_fee'])){
// 								self::__updata_bill_info_data($bill_info_data['BillInfo'], $val['complete_date'], $val['corp_fee'] , 0);
// 							}
// 						}
// 2017.01.04 murata.s ORANGE-244 DEL(E)
					} else if(isset($val['commission_fee_rate'])){
						// 2015/3/10 inokuchi
						//$commission_data[$key]['corp_fee'] = '';
						$commission_data[$key]['commission_fee_rate'] = $val['commission_fee_rate'];
// 2017.01.04 murata.s ORANGE-244 DEL(S)
// 						if(!empty($bill_info_data) && !empty($val['complete_date']) && $val['commission_status'] == Util::getDivValue('construction_status', 'construction')){
// 							if(empty($bill_info_data['BillInfo']['irregular_fee_rate'])){
// 								self::__updata_bill_info_data($bill_info_data['BillInfo'], $val['complete_date'], $val['commission_fee_rate'] , 1);
// 							}
// 						}
// 2017.01.04 murata.s ORANGE-244 DEL(E)
					}
				} else {
					// 新規登録の場合
					$commission_data[$key]['demand_id'] = $demand_id;
					$commission_data[$key]['corp_id'] = $val['corp_id'];
					$commission_data[$key]['commit_flg'] = $val['commit_flg'];
					$commission_data[$key]['commission_type'] = Util::getDivValue('commission_type', 'normal_commission');	// 取次種別:0(通常取次)
					$commission_data[$key]['appointers'] = $val['appointers'];
// 2017.10.12 noguchi ORANGE-503 DEL,ADD(S)
//                  $commission_data[$key]['tel_commission_datetime'] = $val['tel_commission_datetime'];
//                  $commission_data[$key]['tel_commission_person'] = $val['tel_commission_person'];
					$commission_data[$key]['corp_claim_flg'] = $val['corp_claim_flg'];
// 2017.10.12 noguchi ORANGE-503 DEL,ADD(E)

					$commission_data[$key]['commission_note_send_datetime'] = $val['commission_note_send_datetime'];
					$commission_data[$key]['commission_note_sender'] = $val['commission_note_sender'];
					$commission_data[$key]['first_commission'] = $val['first_commission'];
					$commission_data[$key]['unit_price_calc_exclude'] = $val['unit_price_calc_exclude'];
					$commission_data[$key]['commission_status'] = Util::getDivValue('construction_status','progression'); // 取次状況
					$commission_data[$key]['unit_price_calc_exclude'] = 0;		// 取次単価対象外:0
					$commission_data[$key]['lost_flg'] = $val['lost_flg'];
					$commission_data[$key]['del_flg'] = $val['del_flg'];
					$commission_data[$key]['select_commission_unit_price_rank'] = $val['select_commission_unit_price_rank'];
					$commission_data[$key]['select_commission_unit_price'] = $val['select_commission_unit_price']; //ORANGE-999
// 2017.10.12 noguchi ORANGE-503 ADD start
					$commission_data[$key]['corp_claim_flg'] = $val['corp_claim_flg'];
// 2017.10.12 noguchi ORANGE-503 ADD end

					// 2015.12.29 n.kai ADD start ORANGE-1027
					if(isset($visittime_data['VisitTime'])){
						$commission_data[$key]['commission_visit_time_id'] = $visittime_data['VisitTime']['id'];  //ORANGE-1027
					}
					// 2015.12.29 n.kai ADD end ORANGE-1027
					if(isset($val['auto_select_flg'])){
						$commission_data[$key]['auto_select_flg'] = $val['auto_select_flg'];
					}
					if(isset($val['corp_fee'])){
						$commission_data[$key]['corp_fee'] = $val['corp_fee'];
						// 2015/3/10 inokuchi
						//$commission_data[$key]['commission_fee_rate'] = '';
					} else if(isset($val['commission_fee_rate'])){
						// 2015/3/10 inokuchi
						//$commission_data[$key]['corp_fee'] = '';
						$commission_data[$key]['commission_fee_rate'] = $val['commission_fee_rate'];
					}
// 2017.01.04 murata.s ORANGE-244 ADD(S)
					$commission_data[$key]['order_fee_unit'] = $val['order_fee_unit'];
// 2017.01.04 murata.s ORANGE-244 ADD(E)
				}

				// 取次表を個別送信する場合
				if($val['commit_flg'] == 1&& $data['not-send'] == 1){
					$commission_data[$key]['send_mail_fax_othersend'] = 1;
				}

				// 自動選定フラグの更新
				if($saveSelectFlg && isset($val['auto_select_flg'])){
					// 当月件数チェック
					self::__AutoSelectNumCheck($val['corp_id'], $data['DemandInfo']['genre_id'], $data['DemandInfo']['address1']);
					$saveSelectFlg = false;
				}

				if ($lost_flg) {
					$commission_data[$key]['commission_status'] = Util::getDivValue('construction_status', 'order_fail');
				}
			}
		}
// murata.s ORANGE-261 ADD(E)

		// 自動選定select_flgの更新
		if(!empty($data['updata_auto_select'])){
			$this->AutoSelectSetting->saveSelectFlg($data['DemandInfo']['genre_id'], $data['DemandInfo']['address1']);
		}

		// 2015.02.18 k.yamada
		if (count($error_no) > 0) {
			return false;
		}

		if (0 < count($commission_data)) {
			return $this->CommissionInfo->saveAll($commission_data);
		} else {
			// 登録対象データがない場合も正常終了
			return true;
		}
	}

	// 2015.07.28 s.harada ADD start ORANGE-680
	/**
	 * 取次先情報のメール/FAX送信済みフラグ更新
	 *
	 * @param unknown $data
	 * @param unknown $error_no
	 */
	//
	private function _update_commission_send_mail_fax($data){

		// 取次先情報が未入力ならなにもしない
		// 2015.08.07 s.harada ADD start ORANGE-766
		// if (!array_key_exists('CommissionInfo', $data)) {
		if (!array_key_exists('CommissionInfo', $data) && !array_key_exists('IntroduceInfo', $data)) {
		// 2015.08.07 s.harada ADD end ORANGE-766
			return true;
		}

		// 案件IDの取得
		$demand_id = (array_key_exists('id', $data['DemandInfo'])) ? $data['DemandInfo']['id'] : $this->DemandInfo->id;

		$commission_info_data = self::__get_commission_info_data($demand_id);

		$commission_data = array();
		foreach ($commission_info_data as $key => $val) {
			// 2015.08.07 s.harada MOD start ORANGE-766
			// 成約ベースで取次先確定フラグがチェックありの場合と紹介ベースの場合
			// if (!empty($val['CommissionInfo']['id']) && $val['CommissionInfo']['commit_flg'] == 1) {
// murata.s ORANGE-261 CHG(S)
// 2016.08.04 murata.s ORANGE-122 CHG(S)
			if (!empty($val['CommissionInfo']['id'])
					&& $val['CommissionInfo']['commit_flg'] == 1
					&& $val['CommissionInfo']['introduction_not'] != 1
					&& $val['CommissionInfo']['lost_flg'] != 1
					&& array_key_exists('CommissionInfo', $data)) {
// 2016.08.04 murata.s ORANGE-122 CHG(S)
				// 2015.08.07 s.harada MOD end ORANGE-766
				$corp_info = $this->MCorp->findById($val['CommissionInfo']['corp_id']);
				// 加盟店情報の顧客情報連絡手段が｢メール＋FAX」「メール」「FAX」の場合
				if (!empty($corp_info['MCorp']['coordination_method'])
					&& (($corp_info['MCorp']['coordination_method'] == Util::getDivValue('coordination_method', 'mail_fax'))
					|| ($corp_info['MCorp']['coordination_method'] == Util::getDivValue('coordination_method', 'mail'))
					|| ($corp_info['MCorp']['coordination_method'] == Util::getDivValue('coordination_method', 'fax'))
					|| ($corp_info['MCorp']['coordination_method'] == Util::getDivValue('coordination_method', 'mail_app'))
					|| ($corp_info['MCorp']['coordination_method'] == Util::getDivValue('coordination_method', 'mail_fax_app'))
					)) {
					$commission_data[$key]['CommissionInfo']['id'] = $val['CommissionInfo']['id'];
					$commission_data[$key]['CommissionInfo']['send_mail_fax'] = 1;
					$commission_data[$key]['CommissionInfo']['send_mail_fax_datetime'] = date('Y/m/d H:i:s', time());
					$user = $this->Auth->user();
					$commission_data[$key]['CommissionInfo']['send_mail_fax_sender'] = $user['id'];  //ORANGE-1001
					$commission_data[$key]['CommissionInfo']['send_mail_fax_othersend'] = 0;
				}
			}
// murata.s ORANGE-261 CHG(E)
		}

		if (count($commission_data) > 0) {
			$this->CommissionInfo->saveAll($commission_data);
		}
	}
	// 2015.07.28 s.harada ADD end ORANGE-680

	/**
	 * 紹介先情報の更新
	 *
	 * @param unknown $data
	 */
	private function __update_introduce($data){

// murata.s ORANGE-261 CHG(S)
		// 取次形態 = "紹介ベース"がなければなにもしない
		$introduce_info = array_filter($data['CommissionInfo'], function($v){
			return $v['commission_type'] == 1;
		});
		if(empty($introduce_info)) return true;
// murata.s ORANGE-261 CHG(E)

		// 新規登録した企業IDリスト(請求情報登録用)
		$inserted = array();
		// 登録されていた企業IDリスト(請求データの削除）
		$inserted_del = array();

		// 案件IDの取得
		$demand_id = (array_key_exists('id', $data['DemandInfo'])) ? $data['DemandInfo']['id'] : $this->DemandInfo->id;

		// 紹介先情報の登録
		$introduce_data = array();
		foreach ($introduce_info as $key => $val){
			// 案件ID、企業IDで登録済みデータの有無を確認
			$current_data = $this->CommissionInfo->find('first', array('conditions'=>array('demand_id'=>$demand_id, 'corp_id'=>$val['corp_id'], 'commission_type'=>Util::getDivValue('commission_type', 'package_estimate'))));

			// 更新 or 登録判定
			if (0 < count($current_data)) {

// murata.s ORANGE-261 ADD(S)
$this->log("2@call 更新",LOG_DEBUG);
				// 更新の場合
				$introduce_data[$key]['id'] = $current_data['CommissionInfo']['id'];								// ID
				$introduce_data[$key]['commission_note_send_datetime'] = $val['commission_note_send_datetime'];		// 紹介票送信日時
				$introduce_data[$key]['commission_note_sender'] = $val['commission_note_sender'];					// 紹介票送信者
				// h.hanaki 2015.12.04 ORANGE-1038
				//$introduce_data[$key]['del_flg'] = $val['del_flg'];												// 削除フラグ
				$introduce_data[$key]['del_flg'] = $val['del_flg'];													// 削除フラグ
				$introduce_data[$key]['introduction_not'] = $val['introduction_not'];								// 紹介不可
				$introduce_data[$key]['lost_flg'] = $val['lost_flg'];

				$introduce_data[$key]['commit_flg'] = $val['commit_flg'];
				$introduce_data[$key]['appointers'] = $val['appointers'];
// 2017.10.12 noguchi ORANGE-503 DEL,ADD(S)
//              $introduce_data[$key]['tel_commission_datetime'] = $val['tel_commission_datetime'];
//              $introduce_data[$key]['tel_commission_person'] = $val['tel_commission_person'];
				$introduce_data[$key]['corp_claim_flg'] = $val['corp_claim_flg'];
// 2017.10.12 noguchi ORANGE-503 DEL,ADD(E)

				$introduce_data[$key]['first_commission'] = $val['first_commission'];
				$introduce_data[$key]['unit_price_calc_exclude'] = $val['unit_price_calc_exclude'];

				if(!empty($val['commit_flg'])){
					$introduce_data[$key]['confirmd_fee_rate'] = 100;
					$introduce_data[$key]['complete_date'] = date('Y/m/d');
				}

				if(!empty($val['introduction_not']) || empty($val['commit_flg'])){
					array_push($inserted_del, $current_data['CommissionInfo']['id']);
				} else {
					$count = $this->BillInfo->find ( "count", array ('conditions' => array ('BillInfo.commission_id' => $current_data ['CommissionInfo'] ['id'], 'BillInfo.demand_id' => $demand_id )));
					if($count == 0){
						array_push($inserted, $val['corp_id']);
					}
				}
// murata.s ORANGE-261 CHG(E)
			} else {
$this->log("2@call 新規",LOG_DEBUG);
				$user = $this->Auth->user();

// murata.s ORANGE-261 CHG(S)
				// 新規登録の場合
				$introduce_data[$key]['demand_id'] = $demand_id;																		// 案件ID
				$introduce_data[$key]['corp_id'] = $val['corp_id'];																		// 加盟店ID
				if(isset($val['commission_note_send_datetime'])){
					$introduce_data[$key]['commission_note_send_datetime'] = $val['commission_note_send_datetime'];							// 紹介票送信日時
				}
				if(isset($val['commission_note_sender'])){
					$introduce_data[$key]['commission_note_sender'] = $val['commission_note_sender'];										// 紹介票送信者
				}
				$introduce_data[$key]['commission_type'] = Util::getDivValue('commission_type','package_estimate');						// 取次種別:1(一括見積)
				$introduce_data[$key]['commit_flg'] = $val['commit_flg'];																				// 確定フラグ:0(未確定)
				$introduce_data[$key]['appointers'] = $val['appointers'];																		// 選定者:ログインユーザー
				$introduce_data[$key]['first_commission'] = $val['first_commission'];																			// 初取次チェック:0
				$introduce_data[$key]['corp_fee'] = $this->__get_introduce_fee($val['corp_id'], $data['DemandInfo']['category_id']);	// 取次先手数料:企業別対応ジャンルマスタ.紹介手数料
// 2017.10.12 noguchi ORANGE-503 DEL,ADD(S)
//              $introduce_data[$key]['tel_commission_datetime'] = $val['tel_commission_datetime'];											// 電話取次日時:現在日時
//              $introduce_data[$key]['tel_commission_person'] = $val['tel_commission_person'];
				$introduce_data[$key]['corp_claim_flg'] = $val['corp_claim_flg'];
// 2017.10.12 noguchi ORANGE-503 DEL,ADD(E)

				$introduce_data[$key]['commission_fee_rate'] = 100;																		// 取次時手数料率:100
				$introduce_data[$key]['commission_status'] = Util::getDivValue('construction_status','introduction');					// 取次状況:5(紹介済)
				//$introduce_data[$key]['confirmd_fee_rate'] = 100;																		// 確定手数料率:100
				$introduce_data[$key]['unit_price_calc_exclude'] = 0;																	// 取次単価対象外:0
				$introduce_data[$key]['lost_flg'] = $val['lost_flg'];																					// 取次前失注フラグ
				//$introduce_data[$key]['complete_date'] = date('Y/m/d');																	// 施工完了日
				// h.hanaki 2015.12.04 ORANGE-1038
				//$introduce_data[$key]['del_flg'] = $val['del_flg'];																	// 削除フラグ
				$introduce_data[$key]['del_flg'] = $val['del_flg'];																// 削除フラグ
				if(isset($val['introduction_not'])){
					$introduce_data[$key]['introduction_not'] = $val['introduction_not'];													// 紹介不可
				}
				//if(!empty($val['introduction_not']) && !empty($val['del_flg'])){
				// h.hanaki 2015.12.04 ORANGE-1038
				//if(empty($val['introduction_not']) && empty($val['del_flg'])){
				if(empty($val['introduction_not']) && empty($val['del_flg']) && !empty($val['commit_flg'])){
					array_push($inserted, $val['corp_id']);
				}

				if(!empty($val['commit_flg'])){
					$introduce_data[$key]['confirmd_fee_rate'] = 100;
					$introduce_data[$key]['complete_date'] = date('Y/m/d');
				}

				$introduce_data[$key]['order_fee_unit'] = $val['order_fee_unit'];
// murata.s ORANGE-261 CHG(E)
			}

			// 個別送信を行う場合
			if(in_array($data['DemandInfo']['demand_status'], array(4, 5)) && $data['not-send'] == 1){
				$introduce_data[$key]['send_mail_fax_othersend'] = 1;
			}
		}

		// 紹介先情報の更新
		if (0 < count($introduce_data)) {
$this->log("3@call CommissionInfo->saveAll",LOG_DEBUG);
			if (!$this->CommissionInfo->saveAll($introduce_data)) {
				return false;
			}
		}
		// リロ・クレカ案件
		if(empty($data['DemandInfo']['riro_kureka'])){
			if (0 < count($inserted)) {
$this->log("4@call __update_bill",LOG_DEBUG);
				if(!self::__update_bill($demand_id, $data['DemandInfo']['category_id'], $inserted)){
$this->log("4@エラー __update_bill",LOG_DEBUG);
					return false;
				}
			}
			if (0 < count($inserted_del)) {
$this->log("4@call __delete_bill",LOG_DEBUG);
				if(!self::__delete_bill($demand_id, $inserted_del)){
$this->log("4@エラー __delete_bill",LOG_DEBUG);
					return false;
				}
			}
		}
		return true;
	}

	/**
	 * 請求情報の削除
	 *
	 * @param unknown $demand_id
	 * @param unknown $ins_data
	 * @return boolean
	 */
	private function __delete_bill($demand_id, $ins_data){

		foreach ($ins_data as $key => $val) {
			// 削除条件作成
			$conditions = array('BillInfo.demand_id' => $demand_id , 'BillInfo.commission_id' => $val);
			// 削除処理
			if(!$this->BillInfo->deleteAll($conditions, false)){
				return false;
			}
		}
		return true;
	}

	/**
	 * 請求情報の更新
	 *
	 * @param unknown_type $demand_id
	 * @param unknown_type $category_id
	 * @param unknown_type $ins_data
	 */
	private function __update_bill($demand_id, $category_id, $ins_data){

		// 消費税率の取得
		$tax_rate = $this->__get_tax_rate();

		// 請求情報の登録
		$save_data = array();
		foreach ($ins_data as $key => $val) {

			//取次情報の取得
			$current_data = $this->CommissionInfo->find('first', array('conditions'=>array('demand_id'=>$demand_id, 'corp_id'=>$val, 'commission_type'=>Util::getDivValue('commission_type', 'package_estimate'))));

			// 紹介手数料の取得
			$introduce_fee = $this->__get_introduce_fee($val, $category_id);

			$save_data[$key]['commission_id'] =  $current_data['CommissionInfo']['id'];									// 取次ID
			$save_data[$key]['demand_id'] = $demand_id;																	// 案件ID
			$save_data[$key]['bill_status'] = Util::getDivValue ( 'bill_status', 'not_issue' );							// 請求状況:1(未発行)
			$save_data[$key]['comfirmed_fee_rate'] = 100;																// 確定手数料率:100
			$save_data[$key]['fee_target_price'] = $introduce_fee;														// 手数料対象金額:企業別対応ジャンルマスタ.紹介手数料
			$save_data[$key]['fee_tax_exclude'] = ($save_data[$key]['comfirmed_fee_rate'] / 100) * $introduce_fee;		// 手数料:(確定手数料率 / 100) * 手数料対象金額
			$save_data[$key]['tax'] = floor($save_data[$key]['fee_tax_exclude'] * $tax_rate);							// 消費税:手数料 * 消費税率
			$save_data[$key]['total_bill_price'] = $save_data[$key]['fee_tax_exclude'] + $save_data[$key]['tax'];		// 合計請求金額:手数料 + 消費税
			$save_data[$key]['fee_payment_price'] = 0;																	// 手数料入金金額
			$save_data[$key]['fee_payment_balance'] = $save_data[$key]['fee_tax_exclude'] + $save_data[$key]['tax'];	// 合計請求金額:手数料 + 消費税
		}

		return $this->BillInfo->saveAll($save_data);
	}

	/**
	 * 案件対応履歴の登録
	 *
	 * @param unknown_type $data
	 */
	private function __update_demand_correspond($data){

		// 案件対応履歴が未入力ならなにもしない
		if (!array_key_exists('DemandCorrespond', $data) || empty($data['DemandCorrespond']['corresponding_contens'])) {
			return true;
		}

		// 案件IDの取得
		$demand_id = (array_key_exists('id', $data['DemandInfo'])) ? $data['DemandInfo']['id'] : $this->DemandInfo->id;

		// 紹介先情報の登録
		$save_data = $data['DemandCorrespond'];

		// 新規登録の場合
		$save_data['demand_id'] = $demand_id;


// 2016.11.30 ORANGE-259 ADD(S)
		// 紹介先情報の更新
		if (!$this->DemandCorrespond->save($save_data)) {

			return false;
		}else{
			App::import('Model', 'DemandCorrespond');
			$auto_correspond = new DemandCorrespond();

// 2016.12.15 murata.s ORANGE-283 CHG(S)
			if(!empty($data['DemandInfo']['do_auto_selection'])){
				$save_data['responders'] = '自動選定';

				$corresponding_contens = '';
				// 加盟店名を取得
				foreach($data['CommissionInfo'] as $commission){
					if(!empty($commission['corp_id'])
							&& (!empty($commission['created_user_id']))
									&& $commission['created_user_id'] == 'AutomaticAuction'){
						$has_commission = true;
						$corp = $this->MCorp->findById($commission['corp_id']);
						// murata.s ORANGE-475 CHG(S)
						$corresponding_contens .= '自動選定 ['.$corp['MCorp']['corp_name'] . "]\n";
						// murata.s ORANGE-475 CHG(E)
					}
				}
				if(empty($corresponding_contens))
					$corresponding_contens = '加盟店なし';

				$save_data['corresponding_contens'] = $corresponding_contens;
				$auto_correspond->save($save_data);
			}
// 2016.12.15 murata.s ORANGE-283 CHG(E)

			//2017/06/08  ichino ORANGE-420 ADD start 
			//地域×カテゴリで加盟店を指定した事をあらわす
			if( Hash::get($data, 'DemandInfo.do_auto_selection_category') == 1 ){
				App::import('Model', 'DemandCorrespond');
				$auto_correspond2 = new DemandCorrespond();

				//案件ID
				$save_data2['demand_id'] = $demand_id;

				//対応日時 $save_dataに合わせる
				$save_data2['correspond_datetime'] = $save_data['correspond_datetime'];

				//担当者部分
				$save_data2['responders'] = '地域・カテゴリ別自動選定';

				$corresponding_contens = '';
				// 加盟店名を取得
				foreach($data['CommissionInfo'] as $commission){
					if(!empty($commission['corp_id'])
                        && (!empty($commission['created_user_id']))
						&& $commission['created_user_id'] == 'AutoCommissionCorp'){
						$has_commission = true;
						$corp = $this->MCorp->findById($commission['corp_id']);
						$corresponding_contens .= '地域・カテゴリ別自動選定 ['.$corp['MCorp']['official_corp_name'] . "]\n";
					}
				}
				if(empty($corresponding_contens)){
					$corresponding_contens = '自動選定加盟店なし';
				}

				$save_data2['corresponding_contens'] = $corresponding_contens;
				$auto_correspond2->save($save_data2);
                                
			}
			//2017/06/08  ichino ORANGE-420 ADD end 
		}
// 2016.11.30 ORANGE-259 ADD(E)
		return true;
	}

	/**
	 * ID別対応履歴情報データの取得
	 *
	 * @param unknown $conditions
	 * @return unknown
	 */
	private function __get_demand_corresponds($conditions = array()){

		$results = $this->DemandCorrespond->find('first',
				array( 'fields' => "*",
						'conditions' => $conditions,
						'order' => array('DemandCorrespond.id'=> 'DESC'),
				)
		);
		return $results;
	}

	/**
	 * 対応履歴更新日時のチェック
	 *
	 * @param unknown $id
	 * @param unknown $modified
	 * @return boolean
	 */
	private function __check_modified_demand_corresponds($id , $modified){

		$results = $this->DemandCorrespond->findByid($id);

		if($modified == $results['DemandCorrespond']['modified']){
			return true;
		} else {
			return false;
		}
	}

	/**
	 * 対応履歴の編集
	 *
	 * @param string $id
	 * @param unknown $data
	 * @return boolean
	 */
	private function __edit_history($id = null , $data = array()){

		$set_data = $data['DemandCorrespond'];
		$set_data['id'] = $id;
		$save_data = array('DemandCorrespond' => $set_data);
		// 更新
		if($this->DemandCorrespond->save($save_data)){
			return true;
		}
		return false;
	}

	/**
	 * PDFファイルの一時アップロード処理
	 *
	 * @param unknown_type $tempfile
	 */
	// 2015.2.14 inokuchi (取次管理に移動)
	/*
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
	*/

	/**
	 * PDFファイルのアップロード処理(一時ファイル→本ファイル)
	 *
	 * @param unknown_type $data
	 */
	// 2015.2.14 inokuchi (取次管理に移動)
	/*
	private function __uplaod_file($data) {

		// 案件IDの取得
		$demand_id = $data['DemandInfo']['id'];

		// [JBR]見積書、領収書ファイルのアップロード
		if (!empty($data['DemandInfo']['tmp_estimate_file'])) {
			$file_name = 'estimate_' . $demand_id . '.pdf';
			$uploadfile = Configure::read('estimate_file_path') . $file_name;
			$tempfile = Configure::read('temporary_file_path') . $data['DemandInfo']['tmp_estimate_file'];
			// ファイルアップロード
			if (!rename($tempfile, $uploadfile)){
				return false;
			}
		}
		if (!empty($data['DemandInfo']['tmp_receipt_file'])) {
			$file_name = 'receipt_' . $demand_id . '.pdf';
			$uploadfile = Configure::read('receipt_file_path') . $file_name;
			$tempfile = Configure::read('temporary_file_path') . $data['DemandInfo']['tmp_receipt_file'];
			// ファイルアップロード
			if (!rename($tempfile, $uploadfile)) {
				return false;
			}
		}

		return true;
	}
	*/

	/**
	 * 取次先、紹介先へメール or FAX送信を行う
	 *
	 * @param unknown_type $data
	 */
	private function __send_mail_fax($data) {

		$result = true;
		$mail_list = array();
		$fax_list = array();

		// 取次先
		if (array_key_exists('CommissionInfo', $data)) {
			foreach ($data['CommissionInfo'] as $val) {
				// 削除、取次前失注のいずれかにもチェックが入ってない場合のみメール/FAX送信先に指定する 2015.06.16 tanaka
				// 2015.09.21 n.kai MOD start ORANGE-877 ※取次確定の加盟店を対象にする
				//if (!empty($val['corp_id']) && empty($val['del_flg']) && empty($val['lost_flg']) ) {
				if (!empty($val['corp_id']) && empty($val['del_flg']) && empty($val['lost_flg']) && $val['commit_flg'] == 1 ) {
				// 2015.09.21 n.kai MOD end ORANGE-877
					$corp_info = $this->MCorp->findById($val['corp_id']);
					if (0 < count($corp_info)) {
						switch ($corp_info['MCorp']['coordination_method']) {
							// 2015.09.08 n.kai ADD start ORANGE-816
							case Util::getDivValue('coordination_method', 'mail_app'):
								$mail_list[] = $corp_info;
								// アプリ通知未実装
								break;
							case Util::getDivValue('coordination_method', 'mail_fax_app'):
								$mail_list[] = $corp_info;
								$fax_list[] = $corp_info;
								// アプリ通知未実装
								break;
							// 2015.09.08 n.kai ADD end ORANGE-816
							case Util::getDivValue('coordination_method', 'mail_fax'):
								$mail_list[] = $corp_info;
								$fax_list[] = $corp_info;
								break;
							case Util::getDivValue('coordination_method', 'mail'):
								$mail_list[] = $corp_info;
								break;
							case Util::getDivValue('coordination_method', 'fax'):
								$fax_list[] = $corp_info;
								break;
							default:
								break;
						}
					}
				}
			}
		}

// murata.s ORANGE-261 DEL(S)
// 取次先と紹介先を統一したことにより、 $data['IntroduceInfo']は未使用となったため、以下削除
// 		// 紹介先
// 		if (array_key_exists('IntroduceInfo', $data)) {
// 			foreach ($data['IntroduceInfo'] as $val) {
// 				// 削除、紹介不可のいずれかにもチェックが入ってない場合のみメール/FAX送信先に指定する 2015.06.16 tanaka
// 				if (!empty($val['corp_id']) && empty($val['del_flg']) && empty($val['introduction_not'])) {
// 					$corp_info = $this->MCorp->findById($val['corp_id']);
// 					if (0 < count($corp_info)) {
// 						switch ($corp_info['MCorp']['coordination_method']) {
// 							// 2015.09.08 n.kai ADD start ORANGE-816
// 							case Util::getDivValue('coordination_method', 'mail_app'):
// 								$mail_list[] = $corp_info;
// 								// アプリ通知未実装
// 								break;
// 							case Util::getDivValue('coordination_method', 'mail_fax_app'):
// 								$mail_list[] = $corp_info;
// 								$fax_list[] = $corp_info;
// 								// アプリ通知未実装
// 								break;
// 							// 2015.09.08 n.kai ADD end ORANGE-816
// 							case Util::getDivValue('coordination_method', 'mail_fax'):
// 								$mail_list[] = $corp_info;
// 								$fax_list[] = $corp_info;
// 								break;
// 							case Util::getDivValue('coordination_method', 'mail'):
// 								$mail_list[] = $corp_info;
// 								break;
// 							case Util::getDivValue('coordination_method', 'fax'):
// 								$fax_list[] = $corp_info;
// 								break;
// 							default:
// 								break;
// 						}
// 					}
// 				}
// 			}
// 		}
// murata.s ORANGE-261 DEL(E)

		// メール送信
		if (0 < count($mail_list)) {
			if (!$this->__send_mail($data, $mail_list)) {
				$result = false;
			}
		}
		// FAX送信
		if (0 < count($fax_list)) {
			if (!$this->__send_fax($data, $fax_list)) {
				$result = false;
			}
		}

		return $result;
	}

	/**
	 * メール送信
	 *
	 * @param unknown_type $corp_list
	 */


	private function __send_mail($data, $corp_list) {
		$demand_id = (array_key_exists('id', $data['DemandInfo'])) ? $data['DemandInfo']['id'] : $this->DemandInfo->id;

		// 必要な情報を取得
		$conditions = array('DemandInfo.id' => $demand_id);
		$mail_info = $this->__get_mail_fax_data($conditions);

		// 必要な情報が取得できない場合はメール送信を行わない
		if (count($mail_info) < 1) {
			return false;
		}

		// メールヘッダー設定
		mb_language('Ja');
		mb_internal_encoding('UTF-8');

		// 2015.06.17 h.hanaki 変更 start ＊ヒアリング内容をメールに反映しない ORANGE-565
		// 2015.5.12 n.kai MOD start ＊ヒアリング内容をメールに反映する
		//$inquiry_data = $this->__get_mail_demandInquiry_answer_list($demand_id);
		$inquiry_data = "";
		// 2015.5.12 n.kai MOD end
		// 2015.06.15 h.hanaki 変更 end

                // 2017.06.23 inokuma ORANGE-32 ADD start
                $demand_file_info = !empty($mail_info['DemandAttachedFiles']['id']) ? '添付資料あり（取次管理から確認して下さい。）' : '';
                // 2017.06.23 inokuma ORANGE-32 ADD end
                
		// メールテンプレート振り分け
		//JBR(ガラス)、JBR、その他
		$site = $this->MSite->findById($data['DemandInfo']['site_id']);

		//	JBR(ガラス)
		if ($site['MSite']['jbr_flg'] == 1 && $data['DemandInfo']['jbr_work_contents'] == Configure::read('jbr_glass_category')) {
			// 2015.04.28 h.hara(S)
			//$header = 'From:' . mb_encode_mimeheader(Util::getDivText('jbr_glass_mail_setting', 'from_name')) . Util::getDivText('mail_setting', 'from_address');
			$header = 'From:' . Util::getDivText('mail_setting', 'from_address');
			// 2015.04.28 h.hara(E)
			$subject = sprintf(Util::getDivText('jbr_glass_mail_setting', 'title'), $demand_id);
			// 2015.10.10 h.hanaki CHG       ORANGE_AUCTION-17(追加:DemandInfo.construction_class)
			$body = sprintf(Util::getDivText('jbr_glass_mail_setting', 'contents'), $data['DemandInfo']['jbr_order_no'], Router::url('/login', true), $demand_id, $mail_info['MSite']['site_name'], $mail_info['MCategory']['category_name'], $mail_info['DemandInfo']['customer_name'], "〒".$mail_info['DemandInfo']['postcode'] , Util::getDivTextJP('prefecture_div',$mail_info['DemandInfo']['address1']).$mail_info['DemandInfo']['address2'].$mail_info['DemandInfo']['address3'],Util::getDropText('建物種別',$mail_info['DemandInfo']['construction_class']), $mail_info['DemandInfo']['tel1'], $mail_info['DemandInfo']['tel2'], $mail_info['DemandInfo']['customer_mailaddress'], $mail_info['DemandInfo']['contents'].$inquiry_data, $demand_file_info);

		//	JBRその他
		} elseif ($site['MSite']['jbr_flg'] == 1) {
			// 2015.04.28 h.hara(S)
			//$header = 'From:' . mb_encode_mimeheader(Util::getDivText('jbr_mail_setting', 'from_name')) . Util::getDivText('mail_setting', 'from_address');
			$header = 'From:' . Util::getDivText('mail_setting', 'from_address');
			// 2015.04.28 h.hara(E)
			$subject = sprintf(Util::getDivText('jbr_mail_setting', 'title'), $demand_id);
			// 2015.10.10 h.hanaki CHG       ORANGE_AUCTION-17(追加:DemandInfo.construction_class)
			$body = sprintf(Util::getDivText('jbr_mail_setting', 'contents'), $data['DemandInfo']['jbr_order_no'], Router::url('/login', true), $demand_id, $mail_info['MSite']['site_name'], $mail_info['MCategory']['category_name'], $mail_info['DemandInfo']['customer_name'], "〒".$mail_info['DemandInfo']['postcode'] , Util::getDivTextJP('prefecture_div',$mail_info['DemandInfo']['address1']).$mail_info['DemandInfo']['address2'].$mail_info['DemandInfo']['address3'], Util::getDropText('建物種別',$mail_info['DemandInfo']['construction_class']), $mail_info['DemandInfo']['tel1'], $mail_info['DemandInfo']['tel2'], $mail_info['DemandInfo']['customer_mailaddress'], $mail_info['DemandInfo']['contents'].$inquiry_data, $demand_file_info);

		//	紹介ベース
		} elseif ($mail_info['CommissionInfo']['commission_type'] == 1) {
			// 2015.06.20 h.hanaki 紹介ベースのメール書式
			// 2015.04.28 h.hara(S)
			//$header = 'From:' . mb_encode_mimeheader(Util::getDivText('mail_setting', 'from_name')) . Util::getDivText('mail_setting', 'from_address');
			$header = 'From:' . Util::getDivText('package_estimate_mail_setting', 'from_address');
			// 2015.04.28 h.hara(E)
			$subject = sprintf(Util::getDivText('package_estimate_mail_setting', 'title'), $demand_id);
			// 2015.06.20 h.hanaki MOD start ＊メールフォーマット変更
			// 2015.5.19 n.kai MOD start ＊メールフォーマット変更
			//$body = sprintf(Util::getDivText('mail_setting', 'contents'), Router::url('/login', true), $demand_id, $mail_info['MSite']['site_name'], $mail_info['MCategory']['category_name'], $mail_info['DemandInfo']['customer_name'], "〒".$mail_info['DemandInfo']['postcode'] , Util::getDivTextJP('prefecture_div',$mail_info['DemandInfo']['address1']).$mail_info['DemandInfo']['address2'].$mail_info['DemandInfo']['address3'], $mail_info['DemandInfo']['tel1'], $mail_info['DemandInfo']['tel2'], $mail_info['DemandInfo']['customer_mailaddress'], $mail_info['DemandInfo']['contents'].$inquiry_data);
			//$body = sprintf(Util::getDivText('mail_setting', 'contents'), Router::url('/login', true), $demand_id, $mail_info['MSite']['site_name'], $mail_info['MSite']['site_url'], $mail_info['MSite']['note'], $mail_info['MCategory']['category_name'], $mail_info['DemandInfo']['customer_name'], "〒".$mail_info['DemandInfo']['postcode'] , Util::getDivTextJP('prefecture_div',$mail_info['DemandInfo']['address1']).$mail_info['DemandInfo']['address2'].$mail_info['DemandInfo']['address3'], $mail_info['DemandInfo']['tel1'], $mail_info['DemandInfo']['tel2'], $mail_info['DemandInfo']['customer_mailaddress'], $mail_info['DemandInfo']['contents'].$inquiry_data);
			// 2015.10.10 h.hanaki CHG       ORANGE_AUCTION-17(追加:DemandInfo.construction_class)
			$body = sprintf(Util::getDivText('package_estimate_mail_setting', 'contents'), $demand_id,$mail_info['DemandInfo']['receive_datetime'],$mail_info['MUsers']['user_name'],$mail_info['MSite']['site_name'],$mail_info['MSite']['site_url'],$mail_info['MSite']['note'],$mail_info['MCategory']['category_name'],$mail_info['DemandInfo']['customer_name'],"〒".$mail_info['DemandInfo']['postcode'],Util::getDivTextJP('prefecture_div',$mail_info['DemandInfo']['address1']).$mail_info['DemandInfo']['address2'].$mail_info['DemandInfo']['address3'], Util::getDropText('建物種別',$mail_info['DemandInfo']['construction_class']), $mail_info['DemandInfo']['tel1'],$mail_info['DemandInfo']['tel2'],$mail_info['DemandInfo']['customer_mailaddress'],$mail_info['MSite']['site_name'],$mail_info['DemandInfo']['contents'], $demand_file_info ,Router::url('/commission/detail/', true) . $mail_info['CommissionInfo']['id']);
			// 2015.5.19 n.kai MOD end
			// 2015.06.20 h.hanaki MOD end

		//	成約ベース
		} else {
			// 2015.06.20 h.hanaki 成約ベースのメール書式
			// 2015.10.10 h.hanaki CHG       ORANGE_AUCTION-17(追加:DemandInfo.construction_class)
			$header = 'From:' . Util::getDivText('normal_commission_mail_setting', 'from_address');
			$subject = sprintf(Util::getDivText('normal_commission_mail_setting', 'title'), $demand_id);
			//ORANGE-313 CHG S
			$body = sprintf(Util::getDivText('normal_commission_mail_setting', 'contents'),
                $demand_id,
                $mail_info['DemandInfo']['receive_datetime'],
                $mail_info['MUsers']['user_name'],
                $mail_info['MSite']['site_name'],
                $mail_info['MSite']['site_url'],
                $mail_info['MSite']['note'],
                $mail_info['MCategory']['category_name'],
                $mail_info['DemandInfo']['customer_name'],
                "〒".$mail_info['DemandInfo']['postcode'],
                Util::getDivTextJP('prefecture_div',$mail_info['DemandInfo']['address1']).$mail_info['DemandInfo']['address2'].$mail_info['DemandInfo']['address3'],
                Util::getDropText('建物種別',$mail_info['DemandInfo']['construction_class']),
                $mail_info['DemandInfo']['customer_corp_name'],
                $mail_info['DemandInfo']['tel1'],
                $mail_info['DemandInfo']['tel2'],
                $mail_info['DemandInfo']['customer_mailaddress'],
                $mail_info['MSite']['site_name'],
                $mail_info['DemandInfo']['contents'],
                $demand_file_info ,
                Router::url('/commission/detail/', true) . $mail_info['CommissionInfo']['id']);
			//ORANGE-313 CHG E
		}


		// BCCアドレス
		$header .= PHP_EOL."Bcc:".Util::getDivText('bcc_mail', 'to_address');

		// 全ての宛先にメール送信
		$result = true;
		foreach ($corp_list as $val) {
			if (!empty($val['MCorp']['mailaddress_pc'])) {
				$to_address_list = explode(';', $val['MCorp']['mailaddress_pc']);

				foreach ($to_address_list as $to_address) {
					if (!mb_send_mail(trim($to_address), $subject, $body, $header, "-forange@rits-orange.jp")) {
						$result = false;
					}
				}
			}
			// 2015.09.09 n.kai ADD start ORANGE-816
			if (!empty($val['MCorp']['mailaddress_mobile'])) {
				$to_address_list = explode(';', $val['MCorp']['mailaddress_mobile']);
				foreach ($to_address_list as $to_address) {
					if (!mb_send_mail(trim($to_address), $subject, $body, $header, "-forange@rits-orange.jp")) {
						$result = false;
					}
				}
			}
			// 2015.09.09 n.kai ADD end ORANGE-816
		}
		return $result;
	}

	/**
	 * FAX送信
	 *
	 * @param unknown_type $corp_list
	 */
	private function __send_fax($data, $corp_list) {

		// ***** 添付ファイル(FAX内容)の生成 *****
		$demand_id = (array_key_exists('id', $data['DemandInfo'])) ? $data['DemandInfo']['id'] : $this->DemandInfo->id;

		// 必要な情報を取得
		$conditions = array('DemandInfo.id' => $demand_id);
		$fax_info = $this->__get_mail_fax_data($conditions);

		// 必要な情報が取得できない場合はFAX送信を行わない
		if (count($fax_info) < 1) {
			return false;
		}

//		$fax_contents = sprintf(Util::getDivText('mail_setting', 'contents'), Router::url('/login', true), $demand_id, $fax_info['MSite']['site_name'], $fax_info['MCategory']['category_name'], $fax_info['DemandInfo']['contact_desired_time'], $fax_info['DemandInfo']['customer_name'], "〒".$fax_info['DemandInfo']['postcode'], Util::getDivTextJP('prefecture_div',$fax_info['DemandInfo']['address1']).$fax_info['DemandInfo']['address2'].$fax_info['DemandInfo']['address3'], $fax_info['DemandInfo']['tel1'], $fax_info['DemandInfo']['tel2'], $mail_info['DemandInfo']['customer_mailaddress'], $fax_info['DemandInfo']['contents']);
		$fax_contents = sprintf(Util::getDivText('mail_setting', 'contents'), "","","","","","","","","","","","","","");

		// 添付ファイル名(fax_[案件ID]_[インデックス].txt)
// 		$idx = 1;
// 		while (file_exists(Configure::read('temporary_file_path') . sprintf(Util::getDivText('fax_setting', 'file_name'), $demand_id, $idx))) {
// 			$idx++;
// 		}
// 		$file_name = sprintf(Util::getDivText('fax_setting', 'file_name'), $demand_id, $idx);
// 		$file_path = Configure::read('temporary_file_path') . $file_name;

// 		$handle = fopen( $file_path, 'w');
// 		$honbun = mb_convert_encoding($fax_contents, "SJIS","UTF-8");
// 		fwrite( $handle, $honbun);
// 		fclose( $handle );

 		// ***** メール送信 *****
 		mb_language('Ja');
 		mb_internal_encoding('UTF-8');

 		// TOアドレス
 		$to_address = Util::getDivText('fax_setting', 'to_address');
 		// 件名
 		$subject = Util::getDivText('fax_setting', 'title');
 		$body = "--__PHPRECIPE__\r\n"
 				. "Content-Type: text/plain; charset=\"ISO-2022-JP\"\r\n"
 				. "\r\n"
 				. "%s\r\n"
 				. "--__PHPRECIPE__\r\n";

//		// 添付ファイル
// 		$handle = fopen($file_path, 'rb');

// 		$attach_file = fread($handle, filesize($file_path));
// 		fclose($handle);
// 		$attach_encode = base64_encode($attach_file);

// 		$attache_body = "Content-Type: text/plain; name=\"" . $file_name . "\"\r\n"
// 						. "Content-Transfer-Encoding: base64\r\n"
// 						. "Content-Disposition: attachment; filename=\"" . $file_name . "\"\r\n"
// 						. "\r\n"
// 						. chunk_split($attach_encode) . "\r\n"
// 						. "--__PHPRECIPE__--\r\n";

		// 全てのFAX送信対象分、メール送信
		$result = true;
		$i = 0;

		foreach ($corp_list as $val) {
			if (!empty($val['MCorp']['fax'])) {

				// メールヘッダー設定
				$header = "From: " . mb_encode_mimeheader(Util::getDivText('mail_setting', 'from_name')) . Util::getDivText('mail_setting', 'from_address') . "\r\n"
						. "MIME-Version: 1.0\r\n"
						. "Content-Type: multipart/mixed; boundary=\"__PHPRECIPE__\"\r\n"
								. "\r\n";
				$body = "--__PHPRECIPE__\r\n"
						. "Content-Type: text/plain; charset=\"ISO-2022-JP\"\r\n"
						. "\r\n"
								. "%s\r\n"
										. "--__PHPRECIPE__\r\n";

				// 取次票を作成して添付
				$file_path = '';
				$file_name = $this->Print->fax_commission($demand_id, $val['MCorp']['id'], $file_path);

				// 添付ファイル
				$handle = fopen($file_path, 'rb');

				$attach_file = fread($handle, filesize($file_path));
				fclose($handle);
				$attach_encode = base64_encode($attach_file);

				$attache_body = "Content-Type: text/plain; name=\"" . $file_name . "\"\r\n"
						. "Content-Transfer-Encoding: base64\r\n"
						. "Content-Disposition: attachment; filename=\"" . $file_name . "\"\r\n"
								. "\r\n"
										. chunk_split($attach_encode) . "\r\n"
												. "--__PHPRECIPE__--\r\n";

				// 本文
				$mail_contents = sprintf(Util::getDivText('fax_setting', 'contents'), $val['MCorp']['fax'], $val['MCorp']['corp_name']);
				$body = sprintf($body, $mail_contents) . $attache_body;
 				if (!mb_send_mail($to_address, $subject, $body, $header)) {
 					$result = false;
 				}
			}

			$i++;
		}

		return $result;
	}

	/**
	 * メールやファックスで必要なデータを取得
	 *
	 * @param unknown $conditions
	 * @return unknown
	 */
	private function __get_mail_fax_data($conditions = array()) {

		// 必要な情報を取得
		$request = $this->DemandInfo->find('first',
		// 2015.06.20 h.hanaki MOD start ＊メールフォーマット変更
		// 2015.5.19 n.kai MOD start ＊メールフォーマット変更
		// 2015.10.10 h.hanaki CHG       ORANGE_AUCTION-17(追加:DemandInfo.construction_class)
				//array( 'fields' => 'DemandInfo.id, DemandInfo.contact_desired_time, DemandInfo.customer_name, DemandInfo.postcode, DemandInfo.address1, DemandInfo.address2, DemandInfo.address3, DemandInfo.tel1, DemandInfo.tel2, DemandInfo.customer_mailaddress, DemandInfo.contents, MSite.site_name, MCategory.category_name',
				//array( 'fields' => 'DemandInfo.id, DemandInfo.contact_desired_time, DemandInfo.customer_name, DemandInfo.postcode, DemandInfo.address1, DemandInfo.address2, DemandInfo.address3, DemandInfo.tel1, DemandInfo.tel2, DemandInfo.customer_mailaddress, DemandInfo.contents, MSite.site_name, MSite.site_url, MSite.note, MCategory.category_name',
				//array( 'fields' => 'DemandInfo.id, DemandInfo.contact_desired_time, DemandInfo.customer_name, DemandInfo.postcode, DemandInfo.address1, DemandInfo.address2, DemandInfo.address3, DemandInfo.tel1, DemandInfo.tel2, DemandInfo.customer_mailaddress, DemandInfo.contents, DemandInfo.receive_datetime, MSite.site_name, MSite.site_url, MSite.note, MCategory.category_name,MUsers.user_name,CommissionInfo.id,CommissionInfo.commission_type',
				//ORANGE-32 CHG S
				array( 'fields' => 'DemandInfo.id, DemandInfo.contact_desired_time, DemandInfo.customer_name, DemandInfo.postcode, DemandInfo.address1, DemandInfo.address2, DemandInfo.address3, DemandInfo.tel1, DemandInfo.tel2, DemandInfo.customer_mailaddress, DemandInfo.contents, DemandInfo.receive_datetime, DemandInfo.construction_class, MSite.site_name, MSite.site_url, MSite.note, MCategory.category_name,MUsers.user_name,CommissionInfo.id,CommissionInfo.commission_type,DemandInfo.customer_corp_name,DemandAttachedFiles.id',
				//ORANGE-32 CHG E
				// 2015.5.19 n.kai MOD end
				// 2015.06.20 h.hanaki MOD end
				'conditions' => $conditions,
						'joins' => array(
								array('fields' => '*',
										'type' => 'inner',
										'table' => 'm_sites',
										'alias' => 'MSite',
										'conditions' => array('DemandInfo.site_id = MSite.id')
								),
								array('fields' => '*',
										'type' => 'inner',
										'table' => 'm_categories',
										'alias' => 'MCategory',
										'conditions' => array('DemandInfo.category_id = MCategory.id')
								),
				// 2015.06.20 h.hanaki MOD start ＊メールフォーマット変更
								array('fields' => '*',
										'type' => 'inner',
										'table' => 'm_users',
										'alias' => 'MUsers',
										'conditions' => array('DemandInfo.receptionist = MUsers.id')
								),
								// murata.s ORANGE-261 CHG(S)
								array('fields' => '*',
										'type' => 'inner',
										'table' => 'commission_infos',
										'alias' => 'CommissionInfo',
										'conditions' => array(
												'CommissionInfo.demand_id = DemandInfo.id',
												'CommissionInfo.commit_flg' => 1,
												'CommissionInfo.del_flg <>' => 1,
												'CommissionInfo.introduction_not <>' => 1,
												'CommissionInfo.lost_flg <>' => 1
										)
								),
								// murata.s ORANGE-261 CHG(E)
								// inokuma ORANGE-32 ADD(S)
								array('fields' => '*',
										'type' => 'left',
										'table' => 'demand_attached_files',
										'alias' => 'DemandAttachedFiles',
										'conditions' => array(
												'DemandAttachedFiles.demand_id = DemandInfo.id'
										)
								),
								// inokuma ORANGE-32 ADD(E)
				// 2015.06.20 h.hanaki MOD end
						),
						'order' => array('DemandInfo.id' => 'ASC'),
				)
		);
		return $request;
	}

	/**
	 * 取次先情報の取次票送信日時を更新
	 * @param unknown $data
	 * @return boolean
	 */
	private function _update_commission_note_send_datetime($data) {

		// 取次先情報が未入力ならなにもしない
		if (!array_key_exists('CommissionInfo', $data)) {
			return true;
		}

		// 案件IDの取得
		$demand_id = (array_key_exists('id', $data['DemandInfo'])) ? $data['DemandInfo']['id'] : $this->DemandInfo->id;

		$commission_info_data = self::__get_commission_info_data($demand_id);

		$commission_data = array();
		foreach ($commission_info_data as $key => $val) {
			if (empty($val['CommissionInfo']['commission_note_send_datetime'])) {
				$commission_data[$key]['CommissionInfo']['id'] = $val['CommissionInfo']['id'];
				$commission_data[$key]['CommissionInfo']['commission_note_send_datetime'] = date('Y/m/d H:i');
			}
		}

		if (count($commission_data) > 0) {
			$this->CommissionInfo->saveAll($commission_data);
		}

	}

	/**
	 * ヒアリング項目取得
	 *
	 * @param unknown $demand_id
	 * @return string
	 */
	private function __get_mail_demandInquiry_answer_list($demand_id){
		$InquiryData = array();

		if(!empty($demand_id)){
			$conditions = array ('DemandInquiryAnswer.demand_id' => $demand_id);
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
		}

		$data = "\r\n";
		if(!empty($InquiryData)){
			$count = count($InquiryData)-1;
			foreach ($InquiryData as $key => $val){
				$data .= $val['MInquiry']['inquiry_name'].'：'.$val['DemandInquiryAnswer']['answer_note'];
				if($count != $key){
					$data .= "\r\n";
				}
			}
		}

		return $data;
	}

	/**
	 * 自動選定上限件数チェック
	 *
	 * 上限に達した場合メール処理
	 *
	 * @param unknown $corp_id
	 * @param unknown $genre_id
	 * @param unknown $prefecture_cd
	 * @return boolean
	 */
	private function __AutoSelectNumCheck($corp_id, $genre_id, $prefecture_cd){

		// 当月取次件数
		$fieldPresentMonth = '(SELECT count(*) FROM commission_infos INNER JOIN demand_infos ON demand_infos.id = commission_infos.demand_id WHERE commission_infos.corp_id = "AutoSelectCorp"."corp_id" AND commission_infos.auto_select_flg = 1 AND demand_infos.genre_id = "AutoSelectCorp"."genre_id" AND commission_infos.created > DATE_TRUNC(\'month\', now())) AS "AutoSelectCorp__present_month_count"';

		$data = $this->AutoSelectCorp->find('first', array (
				'fields' => '*, '.$fieldPresentMonth,
				'conditions' => array (
						'AutoSelectCorp.corp_id' => $corp_id,
						'AutoSelectCorp.genre_id' => $genre_id,
						'AutoSelectCorp.prefecture_cd' => $prefecture_cd,
				)
		) );

		$result = true;

		if(!empty($data)){
			if($data["AutoSelectCorp"]['limit_per_month'] <= ($data["AutoSelectCorp"]['present_month_count'] + 1)){
				$MCorpData = $this->MCorp->findById($corp_id);
				if (!empty($MCorpData["MCorp"]['mailaddress_pc'])) {
					$to_address_list = explode(';', $MCorpData["MCorp"]['mailaddress_pc']);
					$prefecture = Util::getDivTextJP("prefecture_div", $prefecture_cd);
					$MGenreData = $this->MGenre->findById($genre_id);
					$genre_name = $MGenreData["MGenre"]['genre_name'];

					mb_language('Ja');
					mb_internal_encoding('UTF-8');

					$header = 'From:' . Util::getDivText('auto_select_mail_setting', 'from_address');
					$subject = Util::getDivText('auto_select_mail_setting', 'title');
					$body = sprintf(Util::getDivText('auto_select_mail_setting', 'contents'), $prefecture, $genre_name);

					// BCCアドレス
					$header .= PHP_EOL."Bcc:".Util::getDivText('bcc_mail', 'to_address');

					// 全ての宛先にメール送信
					foreach ($to_address_list as $to_address) {
						if (!mb_send_mail(trim($to_address), $subject, $body, $header, "-forange@rits-orange.jp")) {
							$result = false;
						}
					}
				}
			}
		}

		return $result;

	}

// 2016.11.24 murata.s ORANGE-185 CHG(S)
	/**
	 * オークション情報の登録
	 *
	 * @param unknown $data
	 * @param boolean $auto_selection true: 自動選定用かどうか
	 * @return boolean
	 */
	private function __update_auction_infos($data, $auto_selection = false){

// 2016.12.08 murata.s ORANGE-250 CHG(S)
// // 2016.11.24 murata.s ORANGE-185 CHG(S)
// 		if(!empty($data['DemandInfo']['do_auction']) || $auto_selection){
// // 2016.11.24 murata.s ORANGE-185 CHG(S)
// 			// 案件IDの取得
// 			$demand_id = (array_key_exists('id', $data['DemandInfo'])) ? $data['DemandInfo']['id'] : $this->DemandInfo->id;
//
// 			// ランク毎送信時刻取得
// 			$rank_time = array();
// 			if($data['DemandInfo']['method'] == 'visit'){
// 				$rank_time = Util::getPushSendTimeOfVisitTime($data['DemandInfo']['auction_start_time'], $data['DemandInfo']['auction_deadline_time'], $data['DemandInfo']['genre_id'], $data['DemandInfo']['address1']);
// 			} else {
// 				$rank_time = Util::getPushSendTimeOfContactDesiredTime($data['DemandInfo']['auction_start_time'], $data['DemandInfo']['auction_deadline_time'], $data['DemandInfo']['genre_id'], $data['DemandInfo']['address1']);
// 			}
//
// 			// 案件にあたる企業リストの取得
// 			$address2 = $data['DemandInfo']['address2'];
// 			// ORANGE-1072 市区町村に含まれる文字を大文字・小文字を検索対象にする(m_posts.address2)
// 			$upperAddress2 = $address2;
// 			$upperAddress2 = str_replace('ヶ', 'ケ', $upperAddress2);
// 			$upperAddress2 = str_replace('ﾉ', 'ノ', $upperAddress2);
// 			$upperAddress2 = str_replace('ﾂ', 'ツ', $upperAddress2);
// 			$lowerAddress2 = $address2;
// 			$lowerAddress2 = str_replace('ケ', 'ヶ', $lowerAddress2);
// 			$lowerAddress2 = str_replace('ノ', 'ﾉ', $lowerAddress2);
// 			$lowerAddress2 = str_replace('ツ', 'ﾂ', $lowerAddress2);
//
// 			$conditions = array(
// 				'MPost.address1' => Util::getDivTextJP('prefecture_div', $data ['DemandInfo'] ['address1']),
// 				array(
// 					'OR' => array(
// 						array('MPost.address2' => $upperAddress2),
// 						array('MPost.address2' => $lowerAddress2),
// 					)
// 				)
// 			);
//
// 			$results = $this->MPost->find('first',
// 					array( 'fields' => 'MPost.jis_cd',
// 							'conditions' => $conditions,
// 							'group' => array('MPost.jis_cd'),
// 					)
// 			);
//
// 			if (empty($results["MPost"]["jis_cd"])) {
// 				// 存在しないエリアの場合、オークションできないのでここで抜ける。
// 				// すでに対象0件として別処理で手動選定になるようにしている。
// 				return true;
// 			}
//
// 			$jis_cd = $results["MPost"]["jis_cd"];
//
// 			if(!empty($jis_cd)){
//
// 				$conditions = array();
// 				$conditions['MCorp.affiliation_status'] = 1;
// 				$conditions['coalesce(MCorp.corp_commission_status, 0) not in'] = array(1, 2, 4, 5);
// 				$conditions['AffiliationSubs.affiliation_id is'] = NULL;
// 				$conditions['AffiliationSubs.item_id is'] = NULL;
// 				if( $data ['DemandInfo'] ['site_id'] == 585 ) {
// // 2016.08.18 murata.s ORANGE-126 CHG(S)
// // 					// 生活救急車案件の場合、JBR対応状況が「対応不可」の場合、オークション対象外にする
// // 					$conditions['coalesce(MCorp.jbr_available_status, 0) not in '] = array(3);
// 					$conditions['MCorp.jbr_available_status'] = 2;
// // 2016.08.18 murata.s ORANGE-126 CHG(E)
// 				}
//
// 				$joins = array (
// 						array (
// 								'fields' => '*',
// 								'type' => 'inner',
// 								"table" => "m_corp_categories",
// 								"alias" => "MCorpCategory",
// 								"conditions" => array (
// 										"MCorpCategory.corp_id = MCorp.id",
// 										'MCorpCategory.genre_id =' . $data['DemandInfo']['genre_id'],
// 										// ジャンルまで一致でオークション対象とする 2015.09.22 n.kai 一旦取り消し
// 										'MCorpCategory.category_id =' . $data['DemandInfo']['category_id']
// 								)
// 						),
// 						array (
// 								'fields' => '*',
// 								'type' => 'inner',
// 								"table" => "m_target_areas",
// 								"alias" => "MTargetArea",
// 								"conditions" => array (
// 										"MTargetArea.corp_category_id = MCorpCategory.id",
// 										"MTargetArea.jis_cd = '" . $jis_cd . "'"
// 								)
// 						),
// 						array (
// 								'fields' => '*',
// 								'type' => 'inner',
// 								"table" => "affiliation_area_stats",
// 								"alias" => "AffiliationAreaStat",
// 								"conditions" => array (
// 										'AffiliationAreaStat.corp_id = MCorp.id',
// 										'AffiliationAreaStat.genre_id = MCorpCategory.genre_id',
// 										"AffiliationAreaStat.prefecture = '" . $data['DemandInfo']['address1'] . "'"
// 								)
// 						),
// 						array (
// 								'fields' => '*',
// 								'type' => 'inner',
// 								"table" => "affiliation_infos",
// 								"alias" => "AffiliationInfo",
// 								"conditions" => array (
// 										"AffiliationInfo.corp_id = MCorp.id"
// 								)
// 						),
// 						array (
// 								'fields' => '*',
// 								'type' => 'left outer',
// 								"table" => "affiliation_subs",
// 								"alias" => "AffiliationSubs",
// 								"conditions" => array (
// 										'AffiliationSubs.affiliation_id = AffiliationInfo.id',
// 										'AffiliationSubs.item_id = '  . $data['DemandInfo']['category_id']
// 								)
// 						),
// 				);
// // 2016.11.24 murata.s ORANGE-185 CHG(S)
// // 				$results = $this->MCorp->find('all',
// // 						array( 'fields' => 'MCorp.id, MCorp.auction_status, AffiliationAreaStat.commission_unit_price_category, AffiliationAreaStat.commission_unit_price_rank',
// // 								'joins' =>  $joins,
// // 								'conditions' => $conditions,
// // 						)
// // 				);
// 				$results = $this->MCorp->find('all',
// 						array( 'fields' => array(
// 								'MCorp.id',
// 								'MCorp.auction_status',
// 								'AffiliationAreaStat.commission_unit_price_category',
// 								'AffiliationAreaStat.commission_count_category',
// 								'AffiliationAreaStat.commission_unit_price_rank',
// 								'MCorpCategory.order_fee',
// 								'MCorpCategory.order_fee_unit'
// 						),
// 								'joins' =>  $joins,
// 								'conditions' => $conditions,
// 						)
// 				);
// // 2016.11.24 murata.s ORANGE-185 CHG(E)
// 				if (0 < count($results)) {
//
// 					$before_list = array();
// 					$before_data = $this->AuctionInfo->findAllByDemandId($demand_id);
// 					foreach ($before_data as $key => $val){
// 						$before_list[$val['AuctionInfo']['corp_id']] = $val['AuctionInfo']['id'];
// 					}
//
// 					$i = 0;
// 					$auction_data = array();
// // 2016.11.24 murata.s ORANGE-185 ADD(S)
// 					$auto_auction_data = array();
// // 2016.11.24 murata.s ORANGE-185 ADD(E)
//
// 					foreach ($results as $key => $val){
//
// 						// 2016.02.16 ORANGE-1247 k.iwai ADD(S).
// 						//取次データの与信単価の積算が限度額に達していないかチェック
// 						App::uses('CreditHelper', 'View/Helper');
// 						$credit_helper = new CreditHelper(new View());
// //ORANGE-124 CHG(S)
// 						$result_credit = CREDIT_NORMAL;
// 						if($data['DemandInfo']['site_id'] != CREDIT_EXCLUSION_SITE_ID ){
// 							$result_credit = $credit_helper->checkCredit($val['MCorp']['id'], $data['DemandInfo']['genre_id'], false, true);
// 						}
// 						if($result_credit == CREDIT_DANGER){
// 							//与信限度額を超えている場合、以降の処理を行わない
// 							continue;
// 						}
// 						// 2016.02.16 ORANGE-1247 k.iwai ADD(E).
// //ORANGE-124 CHG(E)
// 						// 2016.05.17 ORANGE-1210 murata.s ADD(S)
// 						// 未承認の加盟店の場合、以降の処理は行わない
// // 2016.06.30 murata.s ORANGE-124 CHG(S)
// // 						if($this->__check_agreement_and_license($val['MCorp']['id'], $data['DemandInfo']['category_id']) === false){
// // 							continue;
// // 						}
// 						if($data['DemandInfo']['site_id'] != CREDIT_EXCLUSION_SITE_ID ){
// 							if($this->__check_agreement_and_license($val['MCorp']['id'], $data['DemandInfo']['category_id']) === false){
// 								continue;
// 							}
// 						}
// // 2016.06.30 murata.s ORANGE-124 CHG(E)
// 						// 2016.04.20 ORANGE-1210 murata.s ADD(E)
//
// 						// 2015.12.26 n.kai ADD start ORANGE-1114
// 						// Commission_infosに登録されているlost_flg,del_flgが「1」の場合は入札対象にしない
// 						$target_commission_data = $this->CommissionInfo->findByDemandIdAndCorpId($demand_id, $val['MCorp']['id']);
// 						// 取り出した取次データのlost_flg、del_flgが「0」の加盟店は入札対象とする
// 						if( !isset($target_commission_data['CommissionInfo']) ||
// 							($target_commission_data['CommissionInfo']['lost_flg'] == 0) && ($target_commission_data['CommissionInfo']['del_flg'] == 0) ) {
// 						// 2015.12.26 n.kai ADD end ORANGE-1114
//
// 							if(!empty($val['AffiliationAreaStat']['commission_unit_price_rank'])){
// 								$rank = mb_strtolower($val['AffiliationAreaStat']['commission_unit_price_rank']);
// 								if(isset($rank_time[$rank])){
// 									//ORANGE-250 CHG S
// 									$as = false;
// 									if(empty($val['MCorp']['auction_status'])
// 										|| $val['MCorp']['auction_status'] == Util::getDivValue('auction_delivery_status', 'delivery')
// 										|| $val['MCorp']['auction_status'] == Util::getDivValue('auction_delivery_status', 'deny'))$as = true;
// 									if(!empty($rank_time[$rank]) && $as){
// 									//ORANGE-250 CHG E
// 										$auction_data['AuctionInfo'][$i]['id'] = isset($before_list[$val['MCorp']['id']]) ? $before_list[$val['MCorp']['id']]: '' ;
// 										$auction_data['AuctionInfo'][$i]['demand_id'] = $demand_id;
// 										$auction_data['AuctionInfo'][$i]['corp_id'] = $val['MCorp']['id'];
// 										$auction_data['AuctionInfo'][$i]['push_time'] = $rank_time[$rank];
// 										$auction_data['AuctionInfo'][$i]['push_flg'] = 0;
// 										$auction_data['AuctionInfo'][$i]['before_push_flg'] = 0;
// 										$auction_data['AuctionInfo'][$i]['display_flg'] = 0;
// 										$auction_data['AuctionInfo'][$i]['refusal_flg'] = 0;
// // 2016.08.18 murata.s ORANGE-8 ADD(S)
// 										$auction_data['AuctionInfo'][$i]['rank'] = $rank; // ランク毎抽出用
// // 2016.08.18 murata.s ORANGE-8 ADD(E)
// 										$i++;
// 									}
// // 2016.11.24 murata.s ORANGE-185 ADD(S)
// 									// 2016.12.01 murata.s ORANGE-250 CHG(S)
// 									// 自動選定用にデータを作成
// 									if(!empty($rank_time[$rank])
// 											&& (empty($val['MCorp']['auction_status'])
// 													|| $val['MCorp']['auction_status'] == Util::getDivValue('auction_delivery_status', 'delivery')
// 													|| $val['MCorp']['auction_status'] == Util::getDivValue('auction_delivery_status', 'ng'))){
// 										$auto_auction_data['AuctionInfo'][] = array(
// 												'id' => isset($before_list[$val['MCorp']['id']]) ? $before_list[$val['MCorp']['id']]: '',
// 												'demand_id' => $demand_id,
// 												'corp_id' => $val['MCorp']['id'],
// 												'push_time' => $rank_time[$rank],
// 												'push_flg' => 0,
// 												'before_push_flg' => 0,
// 												'display_flg' => 0,
// 												'refusal_flg' => 0,
// 												'rank' => $rank,
// 												'AffiliationAreaStat' => array(
// 														'commission_unit_price_category' => $val['AffiliationAreaStat']['commission_unit_price_category'],
// 														'commission_count_category' => $val['AffiliationAreaStat']['commission_count_category'],
// 														'commission_unit_price_rank' => $val['AffiliationAreaStat']['commission_unit_price_rank']
// 												),
// 												'MCorpCategory' => array(
// 														'order_fee' => $val['MCorpCategory']['order_fee'],
// 														'order_fee_unit' => $val['MCorpCategory']['order_fee_unit']
// 												),
// 												'MCorp' => array(
// 														'id' => $val['MCorp']['id'],
// 												)
// 										);
// 									}
// 									// 2016.12.01 murata.s ORANGE-250 CHG(E)
// // 2016.11.24 murata.s ORANGE-185 ADD(E)
// 								}
// 							} else {
// 								// ランクが空白の場合は、初回取次扱いとし、オークション対象とする。
// 								$rank = 'z';
// 								if(isset($rank_time[$rank])){
// 									//ORANGE-250 CHG S
// 									$as = false;
// 									if(empty($val['MCorp']['auction_status'])
// 											|| $val['MCorp']['auction_status'] == Util::getDivValue('auction_delivery_status', 'delivery')
// 											|| $val['MCorp']['auction_status'] == Util::getDivValue('auction_delivery_status', 'deny'))$as = true;
// 									if(!empty($rank_time[$rank]) && $as){
// 									//ORANGE-250 CHG E
// 										$auction_data['AuctionInfo'][$i]['id'] = isset($before_list[$val['MCorp']['id']]) ? $before_list[$val['MCorp']['id']]: '' ;
// 										$auction_data['AuctionInfo'][$i]['demand_id'] = $demand_id;
// 										$auction_data['AuctionInfo'][$i]['corp_id'] = $val['MCorp']['id'];
// 										$auction_data['AuctionInfo'][$i]['push_time'] = $rank_time[$rank];
// 										$auction_data['AuctionInfo'][$i]['push_flg'] = 0;
// 										$auction_data['AuctionInfo'][$i]['before_push_flg'] = 0;
// 										$auction_data['AuctionInfo'][$i]['display_flg'] = 0;
// 										$auction_data['AuctionInfo'][$i]['refusal_flg'] = 0;
// // 2016.08.18 murata.s ORANGE-8 ADD(S)
// 										$auction_data['AuctionInfo'][$i]['rank'] = $rank; // ランク毎抽出用
// // 2016.08.18 murata.s ORANGE-8 ADD(E)
// 										$i++;
// 									}
// // 2016.12.01 murata.s ORANGE-250 CHG(S)
// // 2016.11.24 murata.s ORANGE-185 ADD(S)
// 									// 自動選定用にデータを作成
// 									if(!empty($rank_time[$rank])
// 											&& (empty($val['MCorp']['auction_status'])
// 													|| $val['MCorp']['auction_status'] == Util::getDivValue('auction_delivery_status', 'delivery')
// 													|| $val['MCorp']['auction_status'] == Util::getDivValue('auction_delivery_status', 'ng'))){
// 										$auto_auction_data['AuctionInfo'][] = array(
// 												'id' => isset($before_list[$val['MCorp']['id']]) ? $before_list[$val['MCorp']['id']]: '',
// 												'demand_id' => $demand_id,
// 												'corp_id' => $val['MCorp']['id'],
// 												'push_time' => $rank_time[$rank],
// 												'push_flg' => 0,
// 												'before_push_flg' => 0,
// 												'display_flg' => 0,
// 												'refusal_flg' => 0,
// 												'rank' => $rank,
// 												'AffiliationAreaStat' => array(
// 														'commission_unit_price_category' => $val['AffiliationAreaStat']['commission_unit_price_category'],
// 														'commission_count_category' => $val['AffiliationAreaStat']['commission_count_category'],
// 														'commission_unit_price_rank' => $val['AffiliationAreaStat']['commission_unit_price_rank']
// 												),
// 												'MCorpCategory' => array(
// 														'order_fee' => $val['MCorpCategory']['order_fee'],
// 														'order_fee_unit' => $val['MCorpCategory']['order_fee_unit']
// 												),
// 												'MCorp' => array(
// 														'id' => $val['MCorp']['id'],
// 												)
// 										);
// 									}
// // 2016.11.24 murata.s ORANGE-185 ADD(E)
// // 2016.12.01 murata.s ORANGE-185 CHG(E)
// 								}
// 							}
// 						} else { // lost_flg,del_flg check
// 							if ( isset( $before_list[$val['MCorp']['id']] ) ) {
// 								// 再入札時、lost_flg,del_flgが1の加盟店は、辞退したものとして、辞退フラグを1にする
// 								$auction_refusal_data['AuctionInfo']['id'] = isset($before_list[$val['MCorp']['id']]) ? $before_list[$val['MCorp']['id']] : '';
// 								$auction_refusal_data['AuctionInfo']['refusal_flg'] = 1;
// // 2016.11.24 murata.s ORANGE-185 CHG(S)
// 								if(!$auto_selection)
// 									// 書き込む
// 									$this->AuctionInfo->save($auction_refusal_data['AuctionInfo']);
// // 2016.11.24 murata.s ORANGE-185 CHG(E)
// 							}
// 						} // lost_flg,del_flg
// 					}
//
// // 2016.11.24 murata.s ORANGE-185 CHG(S)
// // 					if (0 < count($auction_data)) {
// // // 2016.08.18 murata.s ORANGE-8 ADD(S)
// // // 2016.11.17 murata.s ORANGE-185 ADD(S)
// // 						$auction_data['DemandInfo']['genre_id'] = $data['DemandInfo']['genre_id'];
// // 						$auction_data['DemandInfo']['prefecture'] = $data['DemandInfo']['address1'];
// // // 2016.11.17 murata.s ORANGE-185 ADD(E)
// // 						$this->__carry_auction_push_time($auction_data, $rank_time);
// // // 2016.08.18 murata.s ORANGE-8 ADD(E)
// // 						return $this->AuctionInfo->saveAll($auction_data['AuctionInfo']);
// // 					}
// 					if(!$auto_selection){
// 						// 入札式選定(手動 or 自動)の場合
// 						if (0 < count($auction_data)) {
// 							$auction_data['DemandInfo']['genre_id'] = $data['DemandInfo']['genre_id'];
// 							$auction_data['DemandInfo']['prefecture'] = $data['DemandInfo']['address1'];
// 							$this->__carry_auction_push_time($auction_data, $rank_time);
// 							return $this->AuctionInfo->saveAll($auction_data['AuctionInfo']);
// 						}
// 					}else{
// 						// 自動選定の場合
// 						if (0 < count($auto_auction_data)) {
// 							$auto_auction_data['DemandInfo']['genre_id'] = $data['DemandInfo']['genre_id'];
// 							$auto_auction_data['DemandInfo']['prefecture'] = $data['DemandInfo']['address1'];
// 							$this->__carry_auction_push_time($auto_auction_data, $rank_time);
// 						}
// 						return $auto_auction_data;
// 					}
// // 2016.11.24 murata.s ORANGE-185 CHG(E)
// 				}
// 			}
// 		}
// 		return true;
		// 案件IDの取得
		$demand_id = (array_key_exists('id', $data['DemandInfo'])) ? $data['DemandInfo']['id'] : $this->DemandInfo->id;
		// 既存処理をComponent化、AuctionInfoUtil->update_auction_infosへ移動
		return $this->AuctionInfoUtil->update_auction_infos($demand_id, $data, $auto_selection);
// 2016.12.08 murata.s ORANGE-250 CHG(E)
	}
// 2016.11.24 murata.s ORANGE-185 CHG(E)

	/**
	 * オークション対象加盟店の有無チェック
	 *
	 * @param unknown $data
	 * @return boolean
	 */
	private function __check_number_auction_infos($data){

		if(!empty($data['DemandInfo']['do_auction'])){

			// 案件IDの取得
			$demand_id = (array_key_exists('id', $data['DemandInfo'])) ? $data['DemandInfo']['id'] : $this->DemandInfo->id;

			// ランク毎送信時刻取得
			$rank_time = array();
			if($data['DemandInfo']['method'] == 'visit'){
				$rank_time = Util::getPushSendTimeOfVisitTime($data['DemandInfo']['auction_start_time'], $data['DemandInfo']['auction_deadline_time'], $data['DemandInfo']['genre_id'], $data['DemandInfo']['address1']);
			} else {
				$rank_time = Util::getPushSendTimeOfContactDesiredTime($data['DemandInfo']['auction_start_time'], $data['DemandInfo']['auction_deadline_time'], $data['DemandInfo']['genre_id'], $data['DemandInfo']['address1']);
			}

			// 案件にあたる企業リストの取得
			$address2 = $data['DemandInfo']['address2'];
			// ORANGE-1072 市区町村に含まれる文字を大文字・小文字を検索対象にする(m_posts.address2)
			$upperAddress2 = $address2;
			$upperAddress2 = str_replace('ヶ', 'ケ', $upperAddress2);
			$upperAddress2 = str_replace('ﾉ', 'ノ', $upperAddress2);
			$upperAddress2 = str_replace('ﾂ', 'ツ', $upperAddress2);
			$lowerAddress2 = $address2;
			$lowerAddress2 = str_replace('ケ', 'ヶ', $lowerAddress2);
			$lowerAddress2 = str_replace('ノ', 'ﾉ', $lowerAddress2);
			$lowerAddress2 = str_replace('ツ', 'ﾂ', $lowerAddress2);

			$conditions = array (
				'MPost.address1' => Util::getDivTextJP ( 'prefecture_div', $data ['DemandInfo'] ['address1'] ),
				array(
					'OR' => array(
						array('MPost.address2' => $upperAddress2),
						array('MPost.address2' => $lowerAddress2),
					)
				)
			);

			$results = $this->MPost->find('first',
					array( 'fields' => 'MPost.jis_cd',
							'conditions' => $conditions,
							'group' => array('MPost.jis_cd'),
					)
			);

			if (empty($results["MPost"]["jis_cd"])) {
				// 存在しないエリアの場合、手動選定に戻す。
				return false;
			}

			$jis_cd = $results["MPost"]["jis_cd"];

			if(!empty($jis_cd)){

				$conditions = array();
				$conditions['MCorp.affiliation_status'] = 1;
				$conditions['coalesce(MCorp.corp_commission_status, 0) not in'] = array(1, 2, 4, 5);
				$conditions['AffiliationSubs.affiliation_id is'] = NULL;
				$conditions['AffiliationSubs.item_id is'] = NULL;
				if( $data ['DemandInfo'] ['site_id'] == 585 ) {
// 2016.08.18 murata.s ORANGE-126 CHG(S)
// 					// 生活救急車案件の場合、JBR対応状況が「対応不可」の場合、オークション対象外にする
// 					$conditions['coalesce(MCorp.jbr_available_status, 0) not in '] = array(3);
					$conditions['MCorp.jbr_available_status'] = 2;
// 2016.08.18 murata.s ORANGE-126 CHG(E)
				}

				$joins = array (
						array (
								'fields' => '*',
								'type' => 'inner',
								"table" => "m_corp_categories",
								"alias" => "MCorpCategory",
								"conditions" => array (
										"MCorpCategory.corp_id = MCorp.id",
										'MCorpCategory.genre_id =' . $data['DemandInfo']['genre_id'],
										// ジャンルまで一致でオークション対象とする 2015.09.22 n.kai 一旦取り消し
										'MCorpCategory.category_id =' . $data['DemandInfo']['category_id']
								)
						),
						array (
								'fields' => '*',
								'type' => 'inner',
								"table" => "m_target_areas",
								"alias" => "MTargetArea",
								"conditions" => array (
										"MTargetArea.corp_category_id = MCorpCategory.id",
										"MTargetArea.jis_cd = '" . $jis_cd . "'"
								)
						),
						array (
								'fields' => '*',
								'type' => 'inner',
								"table" => "affiliation_area_stats",
								"alias" => "AffiliationAreaStat",
								"conditions" => array (
										'AffiliationAreaStat.corp_id = MCorp.id',
										'AffiliationAreaStat.genre_id = MCorpCategory.genre_id',
										"AffiliationAreaStat.prefecture = '" . $data['DemandInfo']['address1'] . "'"
								)
						),
						array (
								'fields' => '*',
								'type' => 'inner',
								"table" => "affiliation_infos",
								"alias" => "AffiliationInfo",
								"conditions" => array (
										"AffiliationInfo.corp_id = MCorp.id"
								)
						),
						array (
								'fields' => '*',
								'type' => 'left outer',
								"table" => "affiliation_subs",
								"alias" => "AffiliationSubs",
								"conditions" => array (
										'AffiliationSubs.affiliation_id = AffiliationInfo.id',
										'AffiliationSubs.item_id = '  . $data['DemandInfo']['category_id']
								)
						),
				);

				//2017/05/30  ichino ORANGE-421 CHG  取得項目にMCorpCategory.id、MCorpCategory.auction_statusを追加
				$results = $this->MCorp->find('all',
						array( 'fields' => 'MCorp.id, MCorp.auction_status, AffiliationAreaStat.commission_unit_price_category, AffiliationAreaStat.commission_unit_price_rank, MCorpCategory.id, MCorpCategory.auction_status',
								'joins' =>  $joins,
								'conditions' => $conditions,
						)
				);
				//2017/05/30  ichino ORANGE-421 END

				if (count($results) == 0) {
					// ジャンルとエリアに一致する加盟店が0件の場合、手動選定に戻す。
					return false;
				} else {
					$before_list = array();
					$before_data = $this->AuctionInfo->findAllByDemandId($demand_id);
					foreach ($before_data as $key => $val){
						$before_list[$val['AuctionInfo']['corp_id']] = $val['AuctionInfo']['id'];
					}

					$i = 0;
					$auction_data = array();
					foreach ($results as $key => $val){

// murata.s ORANGE-493 ADD(S)
						//取次データの与信単価の積算が限度額に達していないかチェック
						App::uses('CreditHelper', 'View/Helper');
						$credit_helper = new CreditHelper(new View());
						$result_credit = CREDIT_NORMAL;
						// murata.s ORANGE-485 CHG(S)
						if(!in_array($data['DemandInfo']['site_id'], Configure::read('credit_check_exclusion_site_id'))){
							$result_credit = $credit_helper->checkCredit($val['MCorp']['id'], $data['DemandInfo']['genre_id'], false, true);
						}
						// murata.s ORANGE-485 CHG(E)
						if($result_credit == CREDIT_DANGER){
							//与信限度額を超えている場合、以降の処理を行わない
							continue;
						}
// murata.s ORANGE-493 ADD(E)
// murata.s ORANGE-493 CHG(S)
						// 2016.05.17 murata.s ORANGE-1210 ADD(S)
						// 未承認の加盟店の場合、以降の処理は行わない
						if($data['DemandInfo']['site_id'] != CREDIT_EXCLUSION_SITE_ID ){
							if($this->__check_agreement_and_license($val['MCorp']['id'], $data['DemandInfo']['category_id']) === false){
								continue;
							}
						}
						// 2016.05.17 murata.s ORANGE-1210 ADD(E)
// murata.s ORANGE-493 CHG(E)

						// Commission_infosに登録されているlost_flg,del_flgが「1」の場合は入札対象にしない
						$target_commission_data = $this->CommissionInfo->findByDemandIdAndCorpId($demand_id, $val['MCorp']['id']);
						// 取り出した取次データのlost_flg、del_flgが「0」の加盟店は入札対象とする
						if( !isset($target_commission_data['CommissionInfo']) ||
							($target_commission_data['CommissionInfo']['lost_flg'] == 0) && ($target_commission_data['CommissionInfo']['del_flg'] == 0) ) {

							//2017/05/30  ichino ORANGE-421 ADD
							//カテゴリ別取次方法が設定されている場合は、そちらを判定に使用する

							//加盟店取次方法を変数に格納
							$auction_status_flg = Hash::get($val, 'MCorp.auction_status');

							//ジャンル別取次方法がある場合(0でない)は、変数を上書きする。
							if(Hash::get($val, 'MCorpCategory.auction_status') != 0){
								$auction_status_flg = $val['MCorpCategory']['auction_status'];
							}

							//判定式の変更
							if(empty($auction_status_flg)
									|| $auction_status_flg == Util::getDivValue('auction_delivery_status', 'delivery')
									|| $auction_status_flg == Util::getDivValue('auction_delivery_status', 'deny')){

							//ORANGE-250 CHG S
							//if(empty($val['MCorp']['auction_status']) || $val['MCorp']['auction_status'] == Util::getDivValue('auction_delivery_status', 'delivery') || $val['MCorp']['auction_status'] == Util::getDivValue('auction_delivery_status', 'deny')){
							//ORANGE-250 CHG E
// murata.s ORANGE-493 CHG(S)
								if(!empty($val['AffiliationAreaStat']['commission_unit_price_rank'])){
									$rank = mb_strtolower($val['AffiliationAreaStat']['commission_unit_price_rank']);
								}else{
									$rank = 'z';
								}
								if(!empty($rank_time[$rank])){
									$i++;
								}
// murata.s ORANGE-493 CHG(E)
							}
							//2017/05/30  ichino ORANGE-421 END

						} else { // lost_flg,del_flg check
							if ( isset( $before_list[$val['MCorp']['id']] ) ) {
								// 再入札時、lost_flg,del_flgが1の加盟店は、辞退したものとして、辞退フラグを1にする
								$auction_refusal_data['AuctionInfo']['id'] = isset($before_list[$val['MCorp']['id']]) ? $before_list[$val['MCorp']['id']] : '';
								$auction_refusal_data['AuctionInfo']['refusal_flg'] = 1;
								// 書き込む
								$this->AuctionInfo->save($auction_refusal_data['AuctionInfo']);
							}
						}
					}
					if ($i == 0) {
						// 単価ランクに一致する加盟店が無い場合、手動選定に戻す。
						return false;
					}
				}
			}
		}
		return true;
	}

// 2016.08.18 murata.s ORANGE-8 ADD(S)
	/**
	 * 上位ランクの加盟店がいない場合、送信予定時間の繰上げを行う
	 *
	 * @param unknown $auction_data
	 * @param unknown $rank_time
	 */
	private function __carry_auction_push_time(&$auction_data, $rank_time){
		if(empty($auction_data)) return;

// 2016.11.17 murata.s ORANGE-185 CHG(S)
		$rank_time_sort = array(
				array(
						'rank' => 'a',
						'rank_time' => $rank_time['a']),
				array(
						'rank' => 'b',
						'rank_time' => $rank_time['b']),
				array(
						'rank' => 'c',
						'rank_time' => $rank_time['c']),
				array(
						'rank' => 'd',
						'rank_time' => $rank_time['d']),
				array(
						'rank' => 'z',
						'rank_time' => $rank_time['z']),
				);
		// rank_timeが未設定のrankは取り除く
		$rank_time_sort = array_filter($rank_time_sort, function($v){
			return !empty($v['rank_time']);
		});

		uasort($rank_time_sort, function($a, $b){
			if($a['rank_time'] == $b['rank_time']) {
				return ($a['rank'] < $b['rank']) ? -1 : 1;
			}
			return ($a['rank_time'] < $b['rank_time']) ? -1 : 1;

		});

		foreach($rank_time_sort as $rank){
			$ranks = array_filter($auction_data['AuctionInfo'], function($data) use($rank){
				return $data['rank'] == $rank['rank'];
			});
			if(empty($ranks)){
				// ランクに該当する加盟店がいない
				if(empty($highest_empty_rank))
					$highest_empty_rank = $rank['rank'];
			}else{
				// 上位ランクの加盟店がいない場合は繰上げ送信(=push_timeの繰上げ)を行う
				if(!empty($highest_empty_rank)){
					foreach($auction_data['AuctionInfo'] as $key => $val){
						if($val['rank'] == $rank['rank'])
							$auction_data['AuctionInfo'][$key]['push_time'] = $rank_time[$highest_empty_rank];
					}
				}
				break;
			}
		}
// 2016.11.17 murata.s ORANGE-185 CHG(E)
	}
// 2016.08.18 murata.s ORANGE-8 ADD(E)

	// 2016.05.24 murata.s ORANGE-75 ADD(S)
	private function __check_agreement_and_license($corp_id = null, $category_id = null){
// 2016.06.30 murata.s ORANGE-22 CHG(S)
// 		return $this->MCorp->isCommissionStop($corp_id);
		if($category_id == null)
			return true;
		else
// 2017/10/19 ORANGE-541 m-kawamoto CHG(S) checkLisense削除
			return $this->MCorp->isCommissionStop($corp_id);
// 2017/10/19 ORANGE-541 m-kawamoto CHG(E)
// 2016.06.30 murata.s ORANGE-22 CHG(E)
	}
	// 2016.05.24 murata.s ORANGE-75 ADD(E)

	//ORANGE-140 iwai 2016.08.17 S
	/***
	 * 同一電話番号案件確認
	 * @param unknown $customer_tel
	 */
	private function __check_identically_customer($customer_tel=null){

		if(!empty($customer_tel)){
			$conditions = array('customer_tel' => $customer_tel, 'demand_status !=' => 0);
			return  $this->DemandInfo->find('count', array('conditions' => $conditions,));

		}
		return null;
	}
	//ORANGE-140 iwai 2016.08.17 E

// 2016.12.15 murata.s ORANGE-283 ADD(S)
	/**
	 * 登録可能な選定方式を取得
	 *
	 * @param unknown $genre_id
	 * @param unknown $address1
	 */
	function __get_selection_system_list($genre_id, $address1){
		$selection_system = Util::getDivValue('selection_type', 'ManualSelection');
		if(!empty($address1)){
			$genre_prefecture_data = $this->SelectGenrePrefecture->findByGenreIdAndPrefectureCd($genre_id, $address1);
			if(!empty($genre_prefecture_data)){
				if(!empty($genre_prefecture_data['SelectGenrePrefecture']['selection_type']))
					$selection_system = $genre_prefecture_data['SelectGenrePrefecture']['selection_type'];
			} else {
				$genre_data = $this->SelectGenre->findByGenreId($genre_id);
				if(!empty($genre_data)){
					$selection_system = $genre_data['SelectGenre']['select_type'];
				}
			}
		}
		else{
			// エリア(都道府県)が選択されていない場合
			$genre_data = $this->SelectGenre->findByGenreId($genre_id);
			if(!empty($genre_data)){
				$selection_system = $genre_data['SelectGenre']['select_type'];
			}
		}
		$selection_system_list = array();
		foreach(Util::getDivList('selection_type') as $key => $val){
			if ($key == Util::getDivValue('selection_type', 'ManualSelection') || $key == $selection_system){
				$selection_system_list[$key] = $val;
			}
		}

		return $selection_system_list;
	}
// 2016.12.15 murata.s ORANGE-283 ADD(S)
}
