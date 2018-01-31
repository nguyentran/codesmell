<?php
App::uses('AppController', 'Controller');

class CommissionSelectController extends AppController {

	public $name = 'CommissionSelect';
	// 2016.02.16 ORANGE-1247 k.iwai CHG(S).
	// 2016.04.15 ORANGE-1210 murata.s CHG(S).
	//public $helpers = array('Credit');
	public $helpers = array('Credit', 'Agreement');
	// 2016.04.15 ORANGE-1210 murata.s CHG(E).
	// 2016.02.16 ORANGE-1247 k.iwai CHG(E).

	// 2017.09.27 ORANGE-420 m.kawamoto CHG(S)
	public $components = array('PDF','Session','Commission');
	// 2017.09.27 ORANGE-420 m.kawamoto CHG(E)
	// 2015.07.17 s.harada MOD start ORANGE-271
    // 2017/11/28 ORANGE-459 m-kawamoto ADD(S) MItem追加
	public $uses = array('MPost','DemandInfo', 'CommissionInfo', 'MCorp', 'MUser', 'MCorpCategory', 'MTaxRate',
	  'MTargetArea', 'AffiliationInfo', 'MCategory', 'MSite', 'JbrCategoryComparison', 'AutoSelectSetting', 'AutoSelectCorp', 'AffiliationAreaStat','MCorpNewYear', 'MItem');
	// public $uses = array('MPost','DemandInfo', 'CommissionInfo', 'MCorp', 'MUser', 'MCorpCategory', 'MTaxRate', 'MTargetArea', 'AffiliationInfo', 'MCategory', 'MSite', 'JbrCategoryComparison', 'AutoSelectSetting', 'AutoSelectCorp');
    // 2017/11/28 ORANGE-459 m-kawamoto ADD(E) MItem追加
	// 2015.07.17 s.harada MOD end ORANGE-271

	public function beforeFilter(){

		$this->layout = 'subwin';
		$this->count = null;
		// 2015.06.06 h.hara ORANGE-496(S)
		$this->User = $this->Auth->user();
		// 2015.06.06 h.hara ORANGE-496(E)

		parent::beforeFilter();
	}

	/**
	 * 取次先選定
	 *
	 * jQueryで値を取得 POST送信
	 *
	 * @param string $select_on
	 */
	public function index($select_on = null) {

		// 値をセット
		$this->set('no', $select_on);

	}

	/**
	 * 取次先選定
	 *
	 * 画面 表示
	 *
	 */
	public function display() {
		$corp_list = array();
		$new_corp_list = array();

		// 2016.01.11 n.kai ADD start ORANGE-1194
		if(isset($this->request->data['address2'])){
			// address2の先頭と末尾のスペースを除去する
			$date_address2 = $this->request->data['address2'];
			$data_address2 = preg_replace( '/^[ 　]+/u', '', $date_address2);
			$data_address2 = preg_replace( '/[ 　]+$/u', '', $data_address2);
			$this->request->data['address2'] = $data_address2;
		}
		// 2016.01.11 n.kai ADD end ORANGE-1194

		$data = $this->request->data;

		if(!isset($this->request->data['search'])){
			$data['jis_cd'] = '';
			if(!empty($this->request->data['address1']) && $this->request->data['address1'] != '99'){
				// 住所からエリアコードを取得
				$data['jis_cd'] = self::__get_target_areas($this->request->data);
			}
			// 20106.05.19 murata.s ORANGE-67 DEL(S)
			// 選定先候補検索時は案件のカテゴリにそのまま一致する加盟店を表示させるため、
			// JBRカテゴリの場合のカテゴリIDの変換は行わないようにする
			// $data['category_id'] = self::__jbrCategoryCheck($this->request->data);
			//  20106.05.19 murata.s ORANGE-67 DEL(E)
		}
		// リストを取得（5件以上）
		// 2017.09.27 ORANGE-420 m.kawamoto CHG(S)
		//$corp_list = self::__get_corp_list($data , '');
		$corp_list = $this->Commission->GetCorpList($data , '');
		// 2017.09.27 ORANGE-420 m.kawamoto CHG(S)

		// 2015.07.17 s.harada ADD start ORANGE-271
		// 目標取次単価ﾗﾝｸ表示処理の追加
		$corp_list2 = array();
		for ($i = 0; $i < count($corp_list); $i++){
			$obj = array();
			$obj = $corp_list[$i];
			// 通常
			if (empty($obj[0]['targer_commission_unit_price'])) {
				// 2015.08.10 n.kai MOD start ORANGE-AUCTION-6 ＊取次単価算出shellにあわせる
				// ジャンルマスタの目標取次単価が空の場合、ﾗﾝｸ「a」
				//$obj[0]['commission_unit_price_rank_1'] = '-';
				$obj[0]['commission_unit_price_rank_1'] = 'a';
				// 2015.08.10 n.kai MOD end ORANGE-AUCTION-6
			} else if (empty($obj['AffiliationAreaStat']['commission_unit_price_category'])) {
				// 取次単価が空の場合、ﾗﾝｸ「d」
				$obj[0]['commission_unit_price_rank_1'] = 'd';
			} else {
				$rank_f = $obj['AffiliationAreaStat']['commission_unit_price_category'] / $obj[0]['targer_commission_unit_price'] * 100;
				if ($rank_f >= 100){
					// 取次単価が目標取次単価に対して100%以上の場合、ﾗﾝｸ「a」
					$obj[0]['commission_unit_price_rank_1'] = 'a';
				} else if ($rank_f >= 80){
					// 取次単価が目標取次単価に対して80%以上の場合、ﾗﾝｸ「b」
					$obj[0]['commission_unit_price_rank_1'] = 'b';
				} else if ($rank_f >= 65){
					// 取次単価が目標取次単価に対して65%以上の場合、ﾗﾝｸ「c」
					$obj[0]['commission_unit_price_rank_1'] = 'c';
				} else {
					// 取次単価が目標取次単価に対して64%以下の場合、ﾗﾝｸ「d」
					$obj[0]['commission_unit_price_rank_1'] = 'd';
				}
			}
			// カテゴリ・対応可能エリア条件解除にチェック付きの場合
			if (empty($obj[0]['targer_commission_unit_price'])) {
				// 2015.08.10 n.kai MOD start ORANGE-AUCTION-6 ＊取次単価算出shellにあわせる
				// ジャンルマスタの目標取次単価が空の場合、ﾗﾝｸ「a」
				//$obj[0]['commission_unit_price_rank_2'] = '-';
				$obj[0]['commission_unit_price_rank_2'] = 'a';
				// 2015.08.10 n.kai MOD end ORANGE-AUCTION-6
			} else if (empty($obj['AffiliationInfo']['commission_unit_price'])) {
				// 取次単価が空の場合、ﾗﾝｸ「d」
				$obj[0]['commission_unit_price_rank_2'] = 'd';
			} else {
				$rank_f = $obj['AffiliationInfo']['commission_unit_price'] / $obj[0]['targer_commission_unit_price'] * 100;
				if ($rank_f >= 100){
					// 取次単価が目標取次単価に対して100%以上の場合、ﾗﾝｸ「a」
					$obj[0]['commission_unit_price_rank_2'] = 'a';
				} else if ($rank_f >= 80){
					// 取次単価が目標取次単価に対して80%以上の場合、ﾗﾝｸ「b」
					$obj[0]['commission_unit_price_rank_2'] = 'b';
				} else if ($rank_f >= 65){
					// 取次単価が目標取次単価に対して65%以上の場合、ﾗﾝｸ「c」
					$obj[0]['commission_unit_price_rank_2'] = 'c';
				} else {
					// 取次単価が目標取次単価に対して64%以下の場合、ﾗﾝｸ「d」
					$obj[0]['commission_unit_price_rank_2'] = 'd';
				}
			}
			$corp_list2[] = $obj;
		}
		// 2015.07.17 s.harada ADD end ORANGE-271

		$this->count = count($corp_list);
		// 2017.09.27 ORANGE-420 m.kawamoto CHG(S)
		// リスト取得 [初]
		//$new_corp_list = self::__get_corp_list($data , 'new');
		$new_corp_list = $this->Commission->GetCorpList($data , 'new');
		// 2017.09.27 ORANGE-420 m.kawamoto CHG(E)

		// 2015.07.17 s.harada ADD start ORANGE-271
		// 目標取次単価ﾗﾝｸ表示処理の追加
		$new_corp_list2 = array();
		for ($i = 0; $i < count($new_corp_list); $i++){
			$obj = array();
			$obj = $new_corp_list[$i];
			// 通常
			/* 2015.08.10 n.kai MOD start ORANGE-AUCTION-6 ＊取次単価算出shellにあわせる
			if (empty($obj[0]['targer_commission_unit_price'])) {
				// ジャンルマスタの目標取次単価が空の場合、ﾗﾝｸ「-」
				$obj[0]['commission_unit_price_rank_1'] = '-';
			} else if (empty($obj['AffiliationAreaStat']['commission_unit_price_category'])) {
				// 取次単価が空の場合、ﾗﾝｸ「d」
				$obj[0]['commission_unit_price_rank_1'] = 'd';
			} else {
				$rank_f = $obj['AffiliationAreaStat']['commission_unit_price_category'] / $obj[0]['targer_commission_unit_price'] * 100;
				if ($rank_f >= 100){
					// 取次単価が目標取次単価に対して100%以上の場合、ﾗﾝｸ「a」
					$obj[0]['commission_unit_price_rank_1'] = 'a';
				} else if ($rank_f >= 80){
					// 取次単価が目標取次単価に対して80%以上の場合、ﾗﾝｸ「b」
					$obj[0]['commission_unit_price_rank_1'] = 'b';
				} else if ($rank_f >= 65){
					// 取次単価が目標取次単価に対して65%以上の場合、ﾗﾝｸ「c」
					$obj[0]['commission_unit_price_rank_1'] = 'c';
				} else {
					// 取次単価が目標取次単価に対して64%以下の場合、ﾗﾝｸ「d」
					$obj[0]['commission_unit_price_rank_1'] = 'd';
				}
			}
			2015.08.10 n.kai ORANGE-AUCTION-6 */
			$obj[0]['commission_unit_price_rank_1'] = 'z';
			// 2015.08.10 n.kai MOD end ORANGE-AUCTION-6

			// カテゴリ・対応可能エリア条件解除にチェック付きの場合
			/* 2015.08.10 n.kai MOD start ORANGE-AUCTION-6 ＊取次単価算出shellにあわせる
			if (empty($obj[0]['targer_commission_unit_price'])) {
				$obj[0]['commission_unit_price_rank_2'] = '-';
			} else if (empty($obj['AffiliationInfo']['commission_unit_price'])) {
				// 取次単価が空の場合、ﾗﾝｸ「d」
				$obj[0]['commission_unit_price_rank_2'] = 'd';
			} else {
				$rank_f = $obj['AffiliationInfo']['commission_unit_price'] / $obj[0]['targer_commission_unit_price'] * 100;
				if ($rank_f >= 100){
					// 取次単価が目標取次単価に対して100%以上の場合、ﾗﾝｸ「a」
					$obj[0]['commission_unit_price_rank_2'] = 'a';
				} else if ($rank_f >= 80){
					// 取次単価が目標取次単価に対して80%以上の場合、ﾗﾝｸ「b」
					$obj[0]['commission_unit_price_rank_2'] = 'b';
				} else if ($rank_f >= 65){
					// 取次単価が目標取次単価に対して65%以上の場合、ﾗﾝｸ「c」
					$obj[0]['commission_unit_price_rank_2'] = 'c';
				} else {
					// 取次単価が目標取次単価に対して64%以下の場合、ﾗﾝｸ「d」
					$obj[0]['commission_unit_price_rank_2'] = 'd';
				}
			}
			2015.08.10 n.kai ORANGE-AUCTION-6 */
			$obj[0]['commission_unit_price_rank_2'] = 'z';
			// 2015.08.10 n.kai MOD end ORANGE-AUCTION-6

			$new_corp_list2[] = $obj;
		}
		// 2015.07.17 s.harada ADD end ORANGE-271

		// カテゴリ名
		// ORANGE-1019 【案件管理】取次先情報の「取次時手数料率」の登録を自動化してください。 CHG Start
		// カテゴリのデフォルト取次時手数料率を取得
		// kishimoto@tobila 2016.02.19
		$data['category_name'] = '';
		$data['category_default_fee'] = '';
		$date['category_default_fee_unit'] = '';
// murata.s ORANGE-261 ADD(S)
		$data['category_default_commission_type'] = '';
// murata.s ORANGE-261 ADD(E)
		if(!empty($data['category_id'])){
			$data['category_name'] = $this->MCategory->getListText($data['category_id']);
			$category_default_fee = $this->MCategory->getDefault_fee($data['category_id']);
			$data['category_default_fee'] = $category_default_fee['category_default_fee'];
			$data['category_default_fee_unit'] = $category_default_fee['category_default_fee_unit'];

// murata.s ORANGE-261 ADD(S)
			// ジャンルの取次形態を取得
			$data['category_default_commission_type'] = $this->MCategory->getCommissionType($data['category_id']);
// murata.s ORANGE-261 ADD(E)
		}
		// ORANGE-1019 【案件管理】取次先情報の「取次時手数料率」の登録を自動化してください。 CHG End

        // 2017/11/28 ORANGE-459 m-kawamoto ADD(S)
        //休業日をm_itemsから取得する
        $vacation = $this->MItem->getList('長期休業日');
        
        //viewへ渡す
        $this->set("vacation", $vacation);
        // 2017/11/28 ORANGE-459 m-kawamoto ADD(E)

		// 値をセット
		// 2015.07.17 s.harada MOD start ORANGE-271
		$max_price = self::__get_max_commission_unit_price_category($data);
		if (is_array($max_price) && $max_price != null) {
			$this->set('max_price', Sanitize::clean($max_price[0][0]));
		} else {
			$max_price = array();
			$max_price['commission_unit_price_category'] = 0;
			$max_price['corp_name'] = "";
			$this->set('max_price', Sanitize::clean($max_price));
		}
		$this->set('list', Sanitize::clean($corp_list2));
		$this->set('new_list', Sanitize::clean($new_corp_list2));
		//$this->set('list', Sanitize::clean($corp_list));
		//$this->set('new_list', Sanitize::clean($new_corp_list));
		// 2015.07.17 s.harada MOD end ORANGE-271

		$this->set('data', $data);

	}

	/**
	 * 自動選定
	 *
	 * jQueryで値を取得 POST送信
	 *
	 * @param unknown $prefecture_cd
	 * @param unknown $num
	 */
	public function auto_select($prefecture_cd , $num, $count){

		$this->set('prefecture_cd', $prefecture_cd);
		$this->set('num', $num);
		$this->set('count', $count);

	}

	/**
	 * 自動選定
	 *
	 * 画面 表示
	 *
	 */
	public function auto_select_display(){

		$data = $this->request->data;
		$corp_list = self::__getAutoSelectCorpList($data);

		$this->data = $data;
		$this->set('list', Sanitize::clean($corp_list));
	}

	/**
	 * 加盟店検索
	 *
	 * jQueryで値を取得 POST送信
	 *
	 */
	public function mcop_search() {

	}

	/**
	 * 加盟店検索
	 *
	 * 画面 表示
	 *
	 * @throws Exception
	 */
	public function mcop_display() {
		$corp_name = '';
		try {
			if ($this->request->is('Post') || $this->request->is('put')) {
				$data = self::__affiliationListPost();
			} else {
				$data = self::__affiliationListGet();
			}
		} catch (Exception $e) {
			throw $e;
		}
		// 加盟店リストの取得
		$corp_list = self::__get_search_corp_list($data);
		// 値をセット
		$this->set('list', Sanitize::clean($corp_list));
	}

	/**
	 * 加盟店検索 一覧の検索結果を取得しセット
	 *
	 * @return multitype:Ambigous <NULL, mixed, multitype:>
	 */
	private function __affiliationListGet() {

		$data = $this->Session->read(self::$__sessionKeyForCommissionSelect);

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
	 * 加盟店検索 検索結果をセッションにセット
	 *
	 * @return multitype:
	 */
	private function __affiliationListPost() {

		$data = $this->request->data;

		$this->Session->delete(self::$__sessionKeyForCommissionSelect);
		$this->Session->write(self::$__sessionKeyForCommissionSelect, $data);

		return $data;
	}

	/**
	 * 取次先選定 エリアコード取得
	 *
	 * @param unknown $data
	 * @return string
	 */
	private function __get_target_areas($data = array()){

		if(!empty($data['address1'])){
			$conditions['MPost.address1'] = Util::getDivTextJP('prefecture_div',$data['address1']);
		}
		if(!empty($data['address2'])){
			// ORANGE-1072 市区町村に含まれる文字を大文字・小文字を検索対象にする(m_posts.address2)
			$upperAddress2 = $data['address2'];
			$upperAddress2 = str_replace('ヶ', 'ケ', $upperAddress2);
			$upperAddress2 = str_replace('ﾉ', 'ノ', $upperAddress2);
			$upperAddress2 = str_replace('ﾂ', 'ツ', $upperAddress2);
			$lowerAddress2 = $data['address2'];
			$lowerAddress2 = str_replace('ケ', 'ヶ', $lowerAddress2);
			$lowerAddress2 = str_replace('ノ', 'ﾉ', $lowerAddress2);
			$lowerAddress2 = str_replace('ツ', 'ﾂ', $lowerAddress2);
			$conditions[] = array(
				'OR' => array(
					array('MPost.address2' => $upperAddress2),
					array('MPost.address2' => $lowerAddress2),
				)
			);
		}

		$results = $this->MPost->find('first',
				array( 'fields' => 'MPost.jis_cd',
						'conditions' => $conditions,
						'group' => array('MPost.jis_cd'),
				)
		);
		if(!empty($results)){
			return $results['MPost']['jis_cd'];
		}
		return '';
	}

	/**
	 * 自動選定企業リスト取得
	 *
	 * @param unknown $data
	 * @return unknown
	 */
	private function __getAutoSelectCorpList($data = array()){
		// 2015.06.26 カテゴリ・対応可能エリア条件解除機能追加 h.hara(S)
		//$fields = 'MCorp.id, MCorp.official_corp_name, MCorp.support24hour, MCorpCategory.select_list, MCorp.address1,MCorp.address2, AffiliationAreaStat.commission_unit_price_category , AffiliationAreaStat.commission_count_category';
		// 2015.06.26 カテゴリ・対応可能エリア条件解除機能追加 h.hara(E)
		$fields = 'MCorp.id, MCorp.official_corp_name, MCorp.support24hour, MCorp.available_time_from, MCorp.available_time_to, MCorp.available_time, MCorp.contactable_support24hour, MCorp.contactable_time_from, MCorp.contactable_time_to, MCorp.contactable_time, MCorpCategory.select_list, MCorp.address1,MCorp.address2, AffiliationStat.commission_unit_price_category , AffiliationStat.commission_count_category';
		$fields .= ',(SELECT COUNT(0) FROM commission_infos WHERE corp_id = "MCorp".id AND commission_status = 1 ) AS in_progress';
		$fields .= ',(SELECT COUNT(0) FROM commission_infos WHERE corp_id = "MCorp".id AND commission_status = 2 ) AS in_order';
		$fields .= ',(SELECT COUNT(0) FROM commission_infos WHERE corp_id = "MCorp".id AND commission_status = 3 ) AS complete';
		$fields .= ',(SELECT COUNT(0) FROM commission_infos WHERE corp_id = "MCorp".id AND commission_status = 4 ) AS failed';
		$fields .= ',(SELECT count(*) FROM commission_infos INNER JOIN demand_infos ON demand_infos.id = commission_infos.demand_id WHERE commission_infos.corp_id = "MCorp".id AND commission_infos.auto_select_flg = 1 AND demand_infos.genre_id = '.$data ['genre_id'].' AND commission_infos.commission_note_send_datetime <= DATE_TRUNC(\'month\', now()) AND commission_infos.commission_note_send_datetime > DATE_TRUNC(\'month\',CURRENT_DATE - INTERVAL \'1 month\') ) AS last_month_count';
		$fields .= ',(SELECT count(*) FROM commission_infos INNER JOIN demand_infos ON demand_infos.id = commission_infos.demand_id WHERE commission_infos.corp_id = "MCorp".id AND commission_infos.auto_select_flg = 1 AND demand_infos.genre_id = '.$data ['genre_id'].' AND commission_infos.commission_note_send_datetime >= DATE_TRUNC(\'month\', now()) ) AS present_month_count';
		$fields .= ',(SELECT count(*) FROM commission_infos INNER JOIN demand_infos ON demand_infos.id = commission_infos.demand_id WHERE commission_infos.corp_id = "MCorp".id AND commission_infos.auto_select_flg = 1 AND demand_infos.genre_id = '.$data ['genre_id'].' AND date_trunc(\'day\' , commission_infos.commission_note_send_datetime) = CURRENT_DATE ) AS today_count';

		// 2015.06.26 カテゴリ・対応可能エリア条件解除機能追加 h.hara(S)
		// 対応可能エリア・カテゴリ参照フラグ
		$target_check_flg = false;

		// joinsタイプ初期化
		$joins_type = 'left';

		// 画面のチェックボックス(カテゴリ・対応可能エリア条件解除)
		if(empty($data['target_check'])){
			// カテゴリID・対応可能エリアコード　チェック
			if(!empty($data['category_id']) && !empty($data['jis_cd'])){
				$target_check_flg = true;
				$joins_type = 'inner';
			} else {
				// エラー 表示
				$this->Session->setFlash(__('ErrorCommissionSelect', true), 'default', array('class' => 'error-message'));
				return array();
			}
		}
		// 2015.06.26 カテゴリ・対応可能エリア条件解除機能追加 h.hara(E)

		/******************** 結合条件作成 ******************/
		$joins = array (

				array (
						'fields' => '*',
						'type' => 'inner',
						"table" => "m_corp_categories",
						"alias" => "MCorpCategory",
						"conditions" => array (
								"MCorpCategory.corp_id = MCorp.id",
								'MCorpCategory.genre_id =' . $data ['genre_id']
						),
				),

				array (
						'fields' => '*',
						'type' => 'inner',
						"table" => "affiliation_stats",
						"alias" => "AffiliationStat",
						"conditions" => array (
								"AffiliationStat.corp_id = MCorp.id",
								"AffiliationStat.genre_id = MCorpCategory.genre_id"
						)
				),

		// 2015.06.26 カテゴリ・対応可能エリア条件解除機能追加 h.hara(S)
//				array (
//						'fields' => '*',
//						'type' => 'inner',
//						"table" => "(SELECT corp_category_id, SUBSTRING(jis_cd, 1, 2) FROM m_target_areas WHERE SUBSTRING(jis_cd, 1, 2) = '" . $data ['prefecture_cd'] . "' GROUP BY corp_category_id, SUBSTRING(jis_cd, 1, 2))",
//						"alias" => "MTargetArea",
//						"conditions" => array (
//								"MTargetArea.corp_category_id = MCorpCategory.id"
//						)
//				),

//				array (
//						'fields' => '*',
//						'type' => 'inner',
//						"table" => "affiliation_area_stats",
//						"alias" => "AffiliationAreaStat",
//						"conditions" => array (
//								'AffiliationAreaStat.corp_id = MCorp.id',
//								'AffiliationAreaStat.genre_id = MCorpCategory.genre_id',
//								"AffiliationAreaStat.prefecture = '" . $data ['prefecture_cd'] . "'"
//						)
//				),
		// 2015.06.26 カテゴリ・対応可能エリア条件解除機能追加 h.hara(E)

		);

		// 2015.06.26 カテゴリ・対応可能エリア条件解除機能追加 h.hara(S)
		if($target_check_flg){

			$fields = $fields . ', AffiliationAreaStat.commission_unit_price_category , AffiliationAreaStat.commission_count_category';

			$target_areas_joins = array (
					'fields' => '*',
					'type' => 'inner',
					"table" => "(SELECT corp_category_id, SUBSTRING(jis_cd, 1, 2) FROM m_target_areas WHERE SUBSTRING(jis_cd, 1, 2) = '" . $data ['prefecture_cd'] . "' GROUP BY corp_category_id, SUBSTRING(jis_cd, 1, 2))",
					"alias" => "MTargetArea",
					"conditions" => array (
							"MTargetArea.corp_category_id = MCorpCategory.id")
			);
			array_push ( $joins, $target_areas_joins );

 			$stat_joins = array (
					'fields' => '*',
					'type' => 'inner',
					"table" => "affiliation_area_stats",
					"alias" => "AffiliationAreaStat",
					"conditions" => array (
							'AffiliationAreaStat.corp_id = MCorp.id',
							'AffiliationAreaStat.genre_id = MCorpCategory.genre_id',
							"AffiliationAreaStat.prefecture = '" . $data ['prefecture_cd'] . "'"
					)
			);
			array_push ( $joins, $stat_joins );

			/******************** グループ条件作成 end  ******************/
			$group =array('MCorp.id', 'MCorp.official_corp_name', 'MCorp.support24hour', 'MCorpCategory.select_list', 'MCorp.address1','MCorp.address2', 'AffiliationAreaStat.commission_unit_price_category' , 'AffiliationAreaStat.commission_count_category');
			/******************** グループ条件作成 end  ******************/

			/******************** ソートの作成 ******************/
			$order = array (
					'AffiliationAreaStat.commission_unit_price_category IS NULL',
					'AffiliationAreaStat.commission_unit_price_category DESC',
					'AffiliationAreaStat.commission_count_category DESC'
			);
			/******************** ソートの作成 end ******************/

		}
		else {

			/******************** グループ条件作成 end  ******************/
			$group =array('MCorp.id', 'MCorp.official_corp_name', 'MCorp.support24hour', 'MCorpCategory.select_list', 'MCorp.address1','MCorp.address2', 'AffiliationStat.commission_unit_price_category' , 'AffiliationStat.commission_count_category');
			/******************** グループ条件作成 end  ******************/


			/******************** ソートの作成 ******************/
			$order = array (
					'AffiliationStat.commission_unit_price_category IS NULL',
					'AffiliationStat.commission_unit_price_category DESC',
					'AffiliationStat.commission_count_category DESC'
			);
			/******************** ソートの作成 end ******************/

		}
		// 2015.06.26 カテゴリ・対応可能エリア条件解除機能追加 h.hara(E)
		/******************** 結合条件作成 end  ******************/



		/******************** 条件の作成 ******************/
		// 加盟状態 (加盟)
		$conditions['MCorp.affiliation_status'] = 1;

		// 企業名
		if(!empty($data['official_corp_name'])){
			$conditions['z2h_kana(MCorp.official_corp_name) like'] = '%'. Util::chgSearchValue($data['official_corp_name']) .'%';
		}

		// 2015.04.16 n.kai ADD start 交渉中(1)、取次NG(2) と 取次STOP(4)は表示しない
		// 2015.08.21 n.kai ADD start ORANGE-808 クレーム企業(5)も表示しない
		$conditions ['coalesce(MCorp.corp_commission_status, 0) not in'] = array (1, 2, 4, 5);
		/******************** 条件の作成 end ******************/

		$results = $this->MCorp->find('all',
				array( 'fields' => $fields,
						'joins' =>  $joins,
						'conditions' => $conditions,
						'group' => $group,
						'limit' => 10,
						'order' => $order,
				)
		);

		return $results;
	}

	/**
	 * 自動選定ボタン表示の為
	 *
	 * @return CakeResponse
	 */
	public function auto_select_button(){

		$address1 = $this->request->data['address1'];
		$genre_id = $this->request->data['genre_id'];

		$dataCount = $this->AutoSelectCorp->find ( 'count', array (
				'joins' => array (
						array (
								'fields' => '*',
								'type' => 'inner',
								"table" => "auto_select_prefectures",
								"alias" => "AutoSelectPrefecture",
								"conditions" => array (
										"AutoSelectPrefecture.prefecture_cd = AutoSelectCorp.prefecture_cd",
										"AutoSelectPrefecture.genre_id = AutoSelectCorp.genre_id"
								)
						),
						array (
								'fields' => '*',
								'type' => 'inner',
								"table" => "auto_select_genres",
								"alias" => "AutoSelectGenre",
								"conditions" => array (
										"AutoSelectGenre.select_type = 1",
										"AutoSelectGenre.genre_id = AutoSelectCorp.genre_id"
								)
						),
				),
				'conditions' => array (
						"AutoSelectCorp.prefecture_cd" => $address1,
						"AutoSelectCorp.genre_id" => $genre_id,
				)
		));

		$json = array (
				'count' => $dataCount,
		);


		$this->autoRender = false;

		return new CakeResponse(array('body' => json_encode($json)));

	}

	/**
	 * 自動選定ボタン
	 *
	 * @return CakeResponse
	 */
	public function auto_select_commission(){

		$address1 = $this->request->data['address1'];
		$genre_id = $this->request->data['genre_id'];
		$category_id = $this->request->data['category_id'];

		// 条件に合った自動選定データが存在するか確認する
		$dataCount = $this->AutoSelectSetting->find ( 'count', array (
				'joins' => array (
						array (
								'fields' => '*',
								'type' => 'inner',
								"table" => "auto_select_prefectures",
								"alias" => "AutoSelectPrefecture",
								"conditions" => array (
										"AutoSelectPrefecture.prefecture_cd = AutoSelectSetting.prefecture_cd",
										"AutoSelectPrefecture.genre_id = AutoSelectSetting.genre_id"
								)
						),
						array (
								'fields' => '*',
								'type' => 'inner',
								"table" => "auto_select_genres",
								"alias" => "AutoSelectGenre",
								"conditions" => array (
										"AutoSelectGenre.select_type = 1",
										"AutoSelectGenre.genre_id = AutoSelectSetting.genre_id"
								)
						),
						array (
								'fields' => '*',
								'type' => 'inner',
								"table" => "auto_select_corps",
								"alias" => "AutoSelectCorp",
								"conditions" => array (
										"AutoSelectCorp.corp_id = AutoSelectSetting.corp_id",
										"AutoSelectCorp.prefecture_cd = AutoSelectSetting.prefecture_cd",
										"AutoSelectCorp.genre_id = AutoSelectSetting.genre_id"
								)
						),
						array (
								'fields' => '*',
								'type' => 'inner',
								"table" => "m_corp_categories",
								"alias" => "MCorpCategory",
								"conditions" => array (
										"MCorpCategory.corp_id = AutoSelectSetting.corp_id",
										"MCorpCategory.genre_id = AutoSelectSetting.genre_id"
								)
						),
						array (
								'fields' => '*',
								'type' => 'inner',
								"table" => "m_target_areas",
								"alias" => "MTargetArea",
								"conditions" => array (
										"MTargetArea.corp_category_id = MCorpCategory.id",
										"SUBSTRING(MTargetArea.jis_cd, 1, 2) = AutoSelectSetting.prefecture_cd"
								)
						),
						array (
								'fields' => '*',
								'type' => 'left',
								"table" => '(SELECT corp_id, count(*) AS "count" FROM commission_infos INNER JOIN demand_infos ON "demand_infos"."id" = "commission_infos"."demand_id" WHERE "commission_infos"."auto_select_flg" = 1 AND "demand_infos"."genre_id" = '.$genre_id.' AND "commission_infos"."commission_note_send_datetime" > DATE_TRUNC(\'month\', now()) GROUP BY commission_infos.corp_id )',
								"alias" => "MonthCount",
								"conditions" => array (
										"MonthCount.corp_id = AutoSelectSetting.corp_id",
								)
						),
						array (
								'fields' => '*',
								'type' => 'left',
								"table" => '(SELECT corp_id, count(*) AS "count" FROM commission_infos INNER JOIN demand_infos ON "demand_infos"."id" = "commission_infos"."demand_id" WHERE "commission_infos"."auto_select_flg" = 1 AND "demand_infos"."genre_id" = '.$genre_id.' AND "commission_infos"."commission_note_send_datetime" > CURRENT_DATE GROUP BY commission_infos.corp_id )',
								"alias" => "TodayCount",
								"conditions" => array (
										"TodayCount.corp_id = AutoSelectSetting.corp_id",
								)
						),
				),
				'conditions' => array (
						"AutoSelectSetting.prefecture_cd" => $address1,
						"AutoSelectSetting.genre_id" => $genre_id,
						"MCorpCategory.category_id" => $category_id,
						"AutoSelectCorp.limit_per_month > COALESCE(MonthCount.count, 0)",
						"AutoSelectCorp.limit_per_day > COALESCE(TodayCount.count, 0)",
				)
		));

		$json = array();

		if(!empty($dataCount)){
			// 当月取次件数
			$fieldPresentMonth = '(SELECT count(*) FROM commission_infos INNER JOIN demand_infos ON demand_infos.id = commission_infos.demand_id WHERE commission_infos.corp_id = "AutoSelectSetting"."corp_id" AND commission_infos.auto_select_flg = 1 AND demand_infos.genre_id = "AutoSelectSetting"."genre_id" AND commission_infos.commission_note_send_datetime >= DATE_TRUNC(\'month\', now()) ) AS "AutoSelectSetting__present_month_count"';
			// 本日取次件数
			$fieldToday = '(SELECT count(*) FROM commission_infos INNER JOIN demand_infos ON demand_infos.id = commission_infos.demand_id WHERE commission_infos.corp_id = "AutoSelectSetting"."corp_id" AND commission_infos.auto_select_flg = 1 AND demand_infos.genre_id = "AutoSelectSetting"."genre_id" AND date_trunc(\'day\' , commission_infos.commission_note_send_datetime) = CURRENT_DATE ) AS "AutoSelectSetting__today_count"';
			// 指定のカテゴリ件数
			$fieldCategory = '(SELECT count(*) FROM m_corp_categories INNER JOIN m_target_areas ON (m_target_areas.corp_category_id = m_corp_categories.id AND SUBSTRING(m_target_areas.jis_cd, 1, 2) = "AutoSelectSetting"."prefecture_cd") WHERE corp_id = "AutoSelectSetting"."corp_id" AND genre_id = "AutoSelectSetting"."genre_id" AND category_id = '.$category_id.' ) AS "AutoSelectSetting__category_count"';

			$list = $this->AutoSelectSetting->find ( 'all', array (
					'fields' => '* ,AutoSelectCorp.limit_per_day ,AutoSelectCorp.limit_per_month, '.$fieldToday.', '.$fieldPresentMonth.', '.$fieldCategory,
					'joins' => array (
							array (
									'fields' => '*',
									'type' => 'left',
									"table" => "auto_select_corps",
									"alias" => "AutoSelectCorp",
									"conditions" => array (
											"AutoSelectCorp.corp_id = AutoSelectSetting.corp_id",
											"AutoSelectCorp.prefecture_cd = AutoSelectSetting.prefecture_cd",
											"AutoSelectCorp.genre_id = AutoSelectSetting.genre_id"
									)
							),
							array (
									'fields' => '*',
									'type' => 'left',
									"table" => "auto_select_genres",
									"alias" => "AutoSelectGenre",
									"conditions" => array (
											"AutoSelectGenre.select_type = 1",
											"AutoSelectGenre.genre_id = AutoSelectSetting.genre_id"
									)
							),
					),
					'conditions' => array (
							"AutoSelectSetting.prefecture_cd" => $address1,
							"AutoSelectSetting.genre_id" => $genre_id,
					),
					'order' => array (
							'AutoSelectSetting.seq_no' => 'ASC'
					)
			));

			$selectFlg = false;  // 選定完了フラグ
			for($i = 0; $i<count($list); $i++){
				if(!empty($list[$i]["AutoSelectSetting"]["select_flg"])){
					$selectFlg = true;
				}
				if($selectFlg){
					if($list[$i]["AutoSelectSetting"]["corp_id"] == 0){
						$corp_id = $list[$i]["AutoSelectSetting"]["corp_id"];
						break;
					} else {
						if ($list[$i]["AutoSelectSetting"]["present_month_count"] < $list[$i]["AutoSelectCorp"]["limit_per_month"] &&
							$list[$i]["AutoSelectSetting"]["today_count"] < $list[$i]["AutoSelectCorp"]["limit_per_day"] &&
							!empty($list[$i]["AutoSelectSetting"]["category_count"] )) {
							$corp_id = $list[$i]["AutoSelectSetting"]["corp_id"];
							break;
						}
					}
				}
				if($i == (count($list) - 1)){
					$i = 0;
				}
			}

			if(!empty($corp_id)){
				$conditions = array("MCorp.id"=> $corp_id);
				$fields = "MCorp.id, MCorp.corp_name, MCorp.commission_dial, MCorp.coordination_method, MCorp.mailaddress_pc, MCorp.fax, MCorp.contactable_time, MCorp.note, MCorp.support24hour, MItem.item_name, AffiliationInfo.attention, MCorpCategory.order_fee, MCorpCategory.order_fee_unit, MCorpCategory.note";
				// 休業日
				$fields .= ', (SELECT ARRAY_TO_STRING(ARRAY( SELECT item_name FROM m_items INNER JOIN m_corp_subs ON m_corp_subs.item_category = m_items.item_category AND m_corp_subs.item_id = m_items.item_id WHERE m_corp_subs.item_category = \''.__('holiday' , true).'\' AND m_corp_subs.corp_id = "MCorp"."id" ORDER BY m_items.sort_order ASC ),\'｜\') as "MCorp__holiday" )';

				$joins = array(
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
								'type' => 'inner',
								"table" => "m_items",
								"alias" => "MItem",
								"conditions" => array (
										"MItem.item_category = '".__('coordination_method',true)."'",
										"MItem.item_id = MCorp.coordination_method"
								),
						),
						array (
								'fields' => '*',
								'type' => 'inner',
								"table" => "m_corp_categories",
								"alias" => "MCorpCategory",
								"conditions" => array (
										"MCorpCategory.corp_id = MCorp.id",
										'MCorpCategory.category_id =' . $category_id
								),
						),
				);

				$results = $this->MCorp->find('first',
						array( 'fields' => $fields,
								'joins' =>  $joins,
								'conditions' => $conditions,
						)
				);

				$json = array (
						'corp_id' => $results["MCorp"]["id"],																		// ID
						'corp_name' => $results["MCorp"]["corp_name"],																// 企業名
						'commission_dial' => $results["MCorp"]["commission_dial"],													// 取次用ダイヤル
						'coordination_method' => $results["MCorp"]["coordination_method"],											// 顧客情報連絡手段
						'coordination_method_display' => $results['MItem']['item_name'],											// 顧客情報連絡手段（表示）
						'mailaddress_pc' => $results["MCorp"]["mailaddress_pc"],													// PCメール
						'fax' => $results["MCorp"]["fax"],																			// FAX
						'contactable_time' => $results["MCorp"]["contactable_time"],												// 連絡可能時間
						'attention' => str_replace(array("\r\n","\r","\n"), "<br>", $results['AffiliationInfo']['attention']),		// 備考欄
						'holiday' => $results['MCorp']['holiday'],																	// 休業日
						'order_fee' => $results['MCorpCategory']['order_fee'],														// 受注手数料
						'order_fee_unit' => $results['MCorpCategory']['order_fee_unit'],											// 受注単位
						'm_corp_category_note' => str_replace(array("\r\n","\r","\n"), "<br>", $results['MCorpCategory']['note']),	// メモ
				);
			} else {
				$json = array (
						'corp_id' => 0,
				);
			}
		}

		$this->autoRender = false;

		return new CakeResponse(array('body' => json_encode($json)));
	}

	/**
	 * 取次先選定 企業リスト取得
	 *
	 * @param unknown $data
	 * @return unknown
	 */
	private function __get_corp_list($data = array() , $check){

		$limit = null;
		// エラーチェック
		empty($data['category_id'])? $data['category_id'] = 0:'';

		// 休業日
		$fields_holiday = '(SELECT ARRAY_TO_STRING(ARRAY( SELECT item_name FROM m_items INNER JOIN m_corp_subs ON m_corp_subs.item_category = m_items.item_category AND m_corp_subs.item_id = m_items.item_id WHERE m_corp_subs.item_category = \''.__('holiday' , true).'\' AND m_corp_subs.corp_id = "MCorp"."id" ORDER BY m_items.sort_order ASC ),\'｜\') as "MCorp__holiday" )';

		// ジャンルの目標取次単価
		$fields_commission_unit_price = '(SELECT m_genres.targer_commission_unit_price FROM m_genres WHERE m_genres.id = "MCorpCategory"."genre_id") AS "targer_commission_unit_price"';

		// murata.s ORANGE-261 CHG(S)
		//2016.07.13 ORANGE-9 CHG S
		// 開拓状況
		// 2015.12.03 ORANGE-1039 対応漏れ分
		$fields = 'MCorp.id, MCorp.corp_name, MCorp.commission_dial, MCorp.coordination_method, MCorp.mailaddress_pc, MCorp.fax, MCorp.note, MCorp.support24hour,
				MCorp.available_time_from, MCorp.available_time_to, MCorp.available_time, MCorp.contactable_support24hour, MCorp.contactable_time_from, MCorp.contactable_time_to,
				MCorp.contactable_time, AffiliationInfo.fee, AffiliationInfo.commission_unit_price, AffiliationInfo.attention, MCorpCategory.order_fee, MCorpCategory.order_fee_unit,
				MCorpCategory.note, AffiliationStat.commission_unit_price_category, AffiliationInfo.commission_count, AffiliationInfo.sf_construction_count, MItem.item_name, MCorpCategory.select_list, MCorpCategory.introduce_fee, MCorpCategory.corp_commission_type,'
				.$fields_holiday.", ".$fields_commission_unit_price.",MCorp.address1,MCorp.address2,MCorp.address3,AffiliationInfo.attention, AffiliationStat.commission_count_category, AffiliationStat.orders_count_category";
		//2016.07.13 ORANGE-9 CHG E
		// murata.s ORANGE-261 CHG(E)

		$fields .= ',(SELECT COUNT(0) FROM commission_infos WHERE corp_id = "MCorp".id AND commission_status = 1 ) AS in_progress';
		$fields .= ',(SELECT COUNT(0) FROM commission_infos WHERE corp_id = "MCorp".id AND commission_status = 2 ) AS in_order';
		$fields .= ',(SELECT COUNT(0) FROM commission_infos WHERE corp_id = "MCorp".id AND commission_status = 3 ) AS complete';
		$fields .= ',(SELECT COUNT(0) FROM commission_infos WHERE corp_id = "MCorp".id AND commission_status = 4 ) AS failed';

		// 年末年始対応状況
		$fields .= ','.implode(',',array_map(function($value){
			return 'MCorpNewYear.'.$value;
		},array_keys($this->MCorpNewYear->schema())));

		$order = array (
				'AffiliationInfo.commission_unit_price IS NULL',
				'AffiliationInfo.commission_unit_price DESC' ,
				'AffiliationInfo.commission_count DESC'
		);

		// 対応可能エリア・カテゴリ参照フラグ
		$target_check_flg = false;

		// joinsタイプ初期化
		$joins_type = 'left';

		// 画面のチェックボックス(カテゴリ・対応可能エリア条件解除)
		if(empty($data['target_check'])){
			// カテゴリID・対応可能エリアコード　チェック
			if(!empty($data['category_id']) && !empty($data['jis_cd'])){
				$target_check_flg = true;
				$joins_type = 'inner';
			} else {
				// エラー 表示
				$this->Session->setFlash(__('ErrorCommissionSelect', true), 'default', array('class' => 'error-message'));
				return array();
			}
		}

		// 結合条件作成
		$joins = array (
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
						'type' => 'inner',
						"table" => "m_items",
						"alias" => "MItem",
						"conditions" => array (
								"MItem.item_category = '".__('coordination_method',true)."'",
								"MItem.item_id = MCorp.coordination_method"
						),
				),

				array (
						'fields' => '*',
						'type' => $joins_type,
						"table" => "m_corp_categories",
						"alias" => "MCorpCategory",
						"conditions" => array (
								"MCorpCategory.corp_id = MCorp.id",
								'MCorpCategory.category_id =' . $data ['category_id']
						),
				),

				array (
						'fields' => '*',
						'type' => 'left',
						"table" => "affiliation_stats",
						"alias" => "AffiliationStat",
						"conditions" => array (
								"AffiliationStat.corp_id = MCorp.id",
								"AffiliationStat.genre_id = MCorpCategory.genre_id"
						)
				),

				// 2015.08.16 s.harada ADD start ORANGE-790
				array (
						'fields' => '*',
						'type' => 'left outer',
						"table" => "affiliation_subs",
						"alias" => "AffiliationSubs",
						"conditions" => array (
								"AffiliationSubs.affiliation_id = AffiliationInfo.id",
								"AffiliationSubs.item_id = "  . $data ['category_id']
						)
				),
				// 2015.08.16 s.harada ADD end ORANGE-790
				array (
						'fields' => '*',
						'type' => 'left',
						'table' => 'm_corp_new_years',
						'alias' => 'MCorpNewYear',
						'conditions' => array (
								'MCorp.id = MCorpNewYear.corp_id'
						)
				),
		);

		if($target_check_flg){

			$fields = $fields . ', AffiliationAreaStat.commission_unit_price_category , AffiliationAreaStat.commission_count_category';

// 2016.06.30 murata.s ORANGE-22 ADD(S)
			$fields = $fields.', MCorpCategory.category_id';
// 2016.06.30 murata.s ORANGE-22 ADD(E)
//
			// 2017/05/30  ichino ORANGE-421 ADD start
			$fields = $fields.', MCorpCategory.auction_status';
			// 2017/05/30  ichino ORANGE-421 ADD end

			$target_areas_joins = array (
					'fields' => '*',
					'type' => 'inner',
					// 2015.05.30 h.hara ORANGE-531(S) 2015.06.29 h.hara ORANGE-577(S)戻し
 					"table" => "m_target_areas",
 					"alias" => "MTargetArea",
 					"conditions" => array (
 							"MTargetArea.corp_category_id = MCorpCategory.id",
 							"MTargetArea.jis_cd = '" . $data ['jis_cd'] . "'"
 					)
//					"table" => "(SELECT corp_category_id, SUBSTRING(jis_cd, 1, 2) FROM m_target_areas WHERE SUBSTRING(jis_cd, 1, 2) = '" . substr($data ['jis_cd'], 0, 2) . "' GROUP BY corp_category_id, SUBSTRING(jis_cd, 1, 2))",
//					"alias" => "MTargetArea",
//					"conditions" => array (
//							"MTargetArea.corp_category_id = MCorpCategory.id")
					// 2015.05.30 h.hara ORANGE-531(E) 2015.06.29 h.hara ORANGE-577(E)戻し

			);
			array_push ( $joins, $target_areas_joins );

			$prefecture = (int)substr($data ['jis_cd'], 0, 2);

 			$stat_joins = array (
					'fields' => '*',
					'type' => 'inner',
					"table" => "affiliation_area_stats",
					"alias" => "AffiliationAreaStat",
					"conditions" => array (
							'AffiliationAreaStat.corp_id = MCorp.id',
							'AffiliationAreaStat.genre_id = MCorpCategory.genre_id',
							"AffiliationAreaStat.prefecture = '" . $prefecture . "'"
					)
			);
			array_push ( $joins, $stat_joins );

			// ソート
			$order = array (
					'AffiliationAreaStat.commission_unit_price_category IS NULL',
					'AffiliationAreaStat.commission_unit_price_category DESC',
					'AffiliationAreaStat.commission_count_category DESC'
			);

		}

		/******************** 条件の作成 ******************/
		// 加盟状態 (加盟)
		$conditions['MCorp.affiliation_status'] = 1;

		// 企業名
		if(!empty($data['corp_name'])){
			$conditions['z2h_kana(MCorp.corp_name) like'] = '%'. Util::chgSearchValue($data['corp_name']) .'%';
		}

		if($target_check_flg){
			// 取次件数
			// 2015.07.19 s.harada ORANGE-629 全件出力するとメモリオーバーするので上限を増やす
			if(empty($check)){
				$limit = 1500;
				$conditions['AffiliationAreaStat.commission_count_category >='] = 5;
			} else {
				$limit = 2000 - $this->count;
				$conditions['AffiliationAreaStat.commission_count_category <'] = 5;
			}
// 2016.12.01 murata.s ORANGE-250 ADD(S)
			// 入札式配信状況(取次状況)
			$conditions['or'] = array(
					array('MCorp.auction_status' => null),
					array(
							'MCorp.auction_status != ' => 3,
							// 2017/05/30  ichino ORANGE-421 ADD start
							'MCorpCategory"."auction_status != ' => 3,
							// 2017/05/30  ichino ORANGE-421 ADD end
					),
					// 2017/06/12 ichino ORANGE-421 ADD start
					array(
							'MCorp.auction_status = ' => 1,
							'MCorpCategory"."auction_status != ' => 3,
					),
					array(
							'MCorp.auction_status = ' => 3,
							'MCorpCategory"."auction_status = ' => 1,
					),
					array(
							'MCorp.auction_status = ' => 3,
							'MCorpCategory"."auction_status = ' => 2,
					),
					// 2017/06/12 ichino ORANGE-421 ADD end
			);
			// 2016.12.01 murata.s ORANGE-250 ADD(E)
		} else {
			// 取次件数
			if(empty($check)){
				$limit = 1500;
				$conditions['AffiliationInfo.commission_count >='] = 5;
			} else {
				$limit = 2000 - $this->count;
				$conditions['AffiliationInfo.commission_count <'] = 5;
			}
		}

		// 2015.04.16 n.kai ADD start 交渉中(1)、取次NG(2) と 取次STOP(4)は表示しない
		// 2015.08.21 n.kai ADD start ORANGE-808 クレーム企業(5)も表示しない
		$conditions['coalesce(MCorp.corp_commission_status, 0) not in'] =
		array(
					1,
					2,
					4,
					5
			);
		// 2015.08.21 n.kai ADD end ORANGE-808
		// 2015.04.16 n.kai ADD end

		// 2015.08.16 s.harada ADD start ORANGE-790
		$conditions['AffiliationSubs.affiliation_id is'] = null;
		$conditions['AffiliationSubs.item_id is'] = null;
		// 2015.08.16 s.harada ADD end ORANGE-790

		// 2015.07.22 s.harada ADD start ORANGE-584
		if (isset($data['exclude_corp_id'])) {
			$exclude_corp_array = explode("," , $data['exclude_corp_id']);
			$exclude_corp_id = array();
			for ($i=0; $i<count($exclude_corp_array); $i++) {
				if ($exclude_corp_array[$i] != null && $i != $data['no']) {
					$exclude_corp_id[] = $exclude_corp_array[$i];
				}
			}
			if (count($exclude_corp_id) >= 2) {
				$conditions['MCorp.id not in'] = $exclude_corp_id;
			} else if (count($exclude_corp_id) == 1) {
				$conditions['MCorp.id <>'] = $exclude_corp_id[0];
			}
		}
		// 2015.07.22 s.harada ADD end ORANGE-584

		// 2016.05.30 murata.s ORANGE-75 ADD(S)
		// 契約更新フラグ
		$conditions['MCorp.commission_accept_flg not in'] = array(0, 3);
		// 2016.05.30 murata.s ORANGE-75 ADD(E)


		/******************** 条件の作成 end ******************/

		$results = $this->MCorp->find('all',
				array( 'fields' => $fields,
						'joins' =>  $joins,
						'conditions' => $conditions, 
						'limit' => $limit,
						'order' => $order,
		)
		);

		return $results;

// 2015.3.6 inokuchi
//		$list = $results;
// 		if($target_check_flg){
// 			foreach ($results as $key => $v){
// 				$list[$key]['AffiliationInfo']['commission_count'] = $v['AffiliationStat']['commission_count_category'];
// 				$list[$key]['AffiliationInfo']['sf_construction_count'] = $v['AffiliationStat']['sf_commission_count_category'];
// 				$list[$key]['AffiliationInfo']['commission_unit_price_category'] = $v['AffiliationStat']['commission_unit_price_category'];
// 			}
// 		}
//		return $list;
// 2015.1.26 inokuchi
// 		$results = $this->MCorp->find('all',
// 				array( //'fields' => 'MCorp.id, MCorp.corp_name, MCorp.commission_dial, AffiliationInfo.commission_unit_price, MCorp.coordination_method, MCorpCategory.category_id, MCorp.mailaddress_pc, MCorp.fax, MCorp.contactable_time, MCorp.note, AffiliationInfo.commission_count, AffiliationInfo.fee, AffiliationStat.commission_unit_price_category'.$fields_holiday,
// 					//'fields' => 'MCorp.id, MCorp.corp_name, MCorp.commission_dial, MCorp.coordination_method, MCorp.mailaddress_pc, MCorp.fax, MCorp.contactable_time, MCorp.note, AffiliationInfo.fee, AffiliationInfo.commission_unit_price, AffiliationInfo.attention, MCorpCategory.order_fee, MCorpCategory.order_fee_unit, MCorpCategory.note, '.$fields_holiday,
// 						'fields' => 'MCorp.id, MCorp.corp_name, MCorp.commission_dial, MCorp.coordination_method, MCorp.mailaddress_pc, MCorp.fax, MCorp.contactable_time, MCorp.note, AffiliationInfo.fee, AffiliationInfo.commission_unit_price, AffiliationInfo.attention, MCorpCategory.order_fee, MCorpCategory.order_fee_unit, MCorpCategory.note, AffiliationStat.commission_unit_price_category, AffiliationInfo.commission_count, AffiliationInfo.sf_construction_count, '.$fields_holiday,

// 						'joins' =>  array(
// 								array('fields' => '*',
// 										'type' => 'inner',
// 										"table" => "affiliation_infos",
// 										"alias" => "AffiliationInfo",
// 										"conditions" => array("AffiliationInfo.corp_id = MCorp.id")
// 								),
// 								array('fields' => '*',
// 										'type' => 'left',
// 										"table" => "m_corp_categories",
// 										"alias" => "MCorpCategory",
// 										"conditions" => array("MCorpCategory.corp_id = MCorp.id" , 'MCorpCategory.category_id ='. $data['category_id'])
// 								),
// 								/*
// 								array('fields' => '*',
// 										'type' => 'inner',
// 										"table" => "m_target_areas",
// 										"alias" => "MTargetArea",
// 										"conditions" => array("MTargetArea.corp_category_id = MCorpCategory.id"),
// 								),
// 								*/
// 								array('fields' => '*',
// 										'type' => 'left',
// 										"table" => "affiliation_stats",
// 										"alias" => "AffiliationStat",
// 										"conditions" => array("AffiliationStat.corp_id = MCorp.id" , "AffiliationStat.genre_id = MCorpCategory.genre_id"),
// 								),

// 						),
// 						'conditions' => $conditions,
// 						'hasMany'=>array('MTargetArea'),
// 						'limit' => $limit,
// 						//'order' => array('AffiliationStat.commission_unit_price_category IS NULL', 'AffiliationStat.commission_unit_price_category DESC'),
// 						'order' => array('AffiliationInfo.commission_unit_price IS NULL', 'AffiliationInfo.commission_unit_price DESC'),
// 				)
// 		);

// 		return $results;
	}

	/**
	 * 加盟店検索 企業リスト取得
	 *
	 * @param unknown $data
	 * @return unknown
	 */
	private function __get_search_corp_list($data = null){

		$conditions = array();
		if (!empty($data['corp_name'])) {
			$conditions['z2h_kana(MCorp.official_corp_name) like'] = '%'. Util::chgSearchValue($data['corp_name']) .'%';
		}

		$this->paginate = array( 'fields' => 'MCorp.id, MCorp.official_corp_name',
						'conditions' => $conditions,
						'limit' => Configure::read('list_limit'),
						'order' => array('MCorp.id'=>'ASC'),
		);

		$results = $this->paginate('MCorp');

		return $results;

	}

	/**
	 * JBR判定
	 *
	 * @param unknown $data
	 * @return unknown
	 */
	private function __jbrCategoryCheck($data = array()){

		// エラーチェック
		empty($data['category_id'])? $data['category_id'] = 0:'';

		$category_id = $data['category_id'];
		$results = $this->MSite->findById($data['site_id']);
		if(!empty($results['MSite']['jbr_flg'])){
			$category_data = $this->JbrCategoryComparison->findByJbrCategoryId($data['category_id']);
			if(!empty($category_data)){
				$category_id = $category_data['JbrCategoryComparison']['category_id'];
			}
		}

		return $category_id;
	}

	/**
	 * ジャンル別最大取次単価取得
	 *
	 * @param unknown $data
	 * @return unknown
	 */
	// 2015.07.19 s.harada ADD start ORANGE-271
	private function __get_max_commission_unit_price_category($data = null){

		$fields = '"AffiliationAreaStat"."commission_unit_price_category" AS "commission_unit_price_category", "MCorp"."corp_name" AS "corp_name"';

		// 2015.09.07 n.kai MOD start ORANGE-797
		//$conditions = '"AffiliationAreaStat"."commission_unit_price_category" > 0';
		$conditions = array(
							'"AffiliationAreaStat"."commission_unit_price_category" > 0',
							'"AffiliationAreaStat"."commission_count_category" >= 5'
							);
		// 2015.09.07 n.kai MOD end ORANGE-797

		$joins = array (

				array (
						'type' => 'inner',
						"table" => "m_corps",
						"alias" => "MCorp",
						"conditions" => array (
								"AffiliationAreaStat.corp_id = MCorp.id",
						)
				),
				array (
						'type' => 'inner',
						"table" => "m_corp_categories",
						"alias" => "MCorpCategory",
						"conditions" => array (
								"MCorpCategory.corp_id = MCorp.id",
								'MCorpCategory.category_id =' . $data ['category_id'],
								'MCorpCategory.genre_id =' ."AffiliationAreaStat.genre_id"
						),
				),

		);

		$order = array (
				'AffiliationAreaStat.commission_unit_price_category DESC'
		);

		$results = $this->AffiliationAreaStat->find('all',
				array( 'fields' => $fields,
						'joins' =>  $joins,
						'conditions' => $conditions,
						'limit' => 1,
						'order' => $order,
				)
		);

		return $results;

	}
	// 2015.07.19 s.harada ADD end ORANGE-271

}
