<?php
App::uses('AppController', 'Controller');
App::uses('CakeEmail', 'Network/Email');

class AuctionSettingController extends AppController {

	public $name = 'AuctionSetting';
	// 2016.06.13 ota.r@tobila MOD START ORANGE-88 経理管理者の使用できる管理者機能を制限
//	public $helpers = array('Csv');
	public $helpers = array(
			'Csv',
			'Form' => array(
					'className' => 'AdminForm',
			),
	);
	// 2016.06.13 ota.r@tobila MOD START ORANGE-88 経理管理者の使用できる管理者機能を制限
	public $components = array('PDF','Session', 'Csv');
	public $uses = array('MGenre','MTime' ,'ExclusionTime', 'PublicHoliday', 'AutoSelectGenre', 'AuctionGenre', 'AuctionGenreArea', 'DemandInfo', 'CommissionInfo');

	public function beforeFilter(){
		parent::beforeFilter();
	}

	/**
	 * 全体設定ページ
	 *
	 */
	public function index() {

		if (isset($this->request->data['regist'])) {

			$data = $this->request->data;
			if ($this->MTime->saveAll($data['MTime'])){
				$this->Session->setFlash (__('SuccessRegist', true), 'default', array ('class' => 'message_inner'));
			} else {
				$this->Session->setFlash (__('FailRegist', true), 'default', array ('class' => 'error_inner'));
			}
		} else {
			$data = self::__getMTimeDate();
		}

		$this->data = $data;

		$this->set('hour_list', self::__getTimelist(1, 99));
		$this->set('minute_list', self::__getTimelist(0, 59));

	}

	/**
	 * 祝日/除外時間設定ページ
	 *
	 */
	public function exclusion() {

		if (isset($this->request->data['regist'])) {
			$data = $this->request->data;
			// 祝日/除外時間の更新
			if (self::__editExclusion(&$data)){
				$this->Session->setFlash (__('SuccessRegist', true), 'default', array ('class' => 'message_inner'));
			} else {
				$this->Session->setFlash (__('FailRegist', true), 'default', array ('class' => 'error_inner'));
			}

		} else {
			// 祝日リストの取得
			$data = self::__getPublicHoliday();
			// 除外時間データの取得
			$data += self::__getExclusionTime();
		}
		$this->data = $data;

	}

	/**
	 * オークションジャンル一覧ページ
	 *
	 */
	public function genre() {

		// オークション選定ジャンル一覧を取得
		$list = $this->MGenre->getAuctionGenre();
		$this->set('genres', $list);
	}

	/**
	 * オークションジャンル詳細ページ
	 *
	 * @param string $genre_id
	 */
	public function genre_detail($genre_id = null) {

		if (isset($this->request->data['regist'])) {
			$data = $this->request->data;
			$genre_id = $data['AuctionGenre']['genre_id'];
			if ($this->AuctionGenre->save($data['AuctionGenre'])){
				$this->Session->setFlash (__('SuccessRegist', true), 'default', array ('class' => 'message_inner'));
			} else {
				$this->Session->setFlash (__('FailRegist', true), 'default', array ('class' => 'error_inner'));
			}
		} else {
			$data = $this->AuctionGenre->findByGenreId($genre_id);
		}

		if(empty($genre_id)){
			return $this->redirect('/auction_setting/genre');
			return;
		}

		$this->data = $data;

		$exclusion_time_list = $this->ExclusionTime->getList();

		$this->set('exclusion_time_list', $exclusion_time_list);
		$this->set('genre_id', $genre_id);
		$this->set('genre_name', $this->MGenre->getListText($genre_id));
	}

	/**
	 * 都道府県一覧ページ
	 *
	 * @param string $genre_id
	 */
	public function prefecture($genre_id = null) {

		$this->set('genre_id', $genre_id);
		$this->set('genre_name', $this->MGenre->getListText($genre_id));

	}

	/**
	 * 地域別詳細ページ
	 *
	 * @param string $genre_id
	 * @param string $prefecture_cd
	 */
	public function prefecture_detail($genre_id = null, $prefecture_cd = null){

		if (isset($this->request->data['regist'])) {
			$data = $this->request->data;
			$genre_id = $data['AuctionGenreArea']['genre_id'];
			$prefecture_cd = $data['AuctionGenreArea']['prefecture_cd'];
			if ($this->AuctionGenreArea->save($data['AuctionGenreArea'])){
				$this->Session->setFlash (__('SuccessRegist', true), 'default', array ('class' => 'message_inner'));
			} else {
				$this->Session->setFlash (__('FailRegist', true), 'default', array ('class' => 'error_inner'));
			}
		} else {
			$data = $this->AuctionGenreArea->findByGenreIdAndPrefectureCd($genre_id, $prefecture_cd);
		}

		$this->data = $data;

		$exclusion_time_list = $this->ExclusionTime->getList();

		$this->set('exclusion_time_list', $exclusion_time_list);
		$this->set('genre_id', $genre_id);
		$this->set('prefecture_cd', $prefecture_cd);
		$this->set('genre_name', $this->MGenre->getListText($genre_id));
	}

	/**
	 * 各種時間の設定データの取得
	 *
	 * @return multitype:
	 */
	private function __getMTimeDate(){

		$list = $this->MTime->find('all',
				array(
						'order' => array('id' => 'asc'),
				)
		);
		$result = array();
		foreach ($list as $key => $val){
			$result['MTime'][$key]['id'] = $val['MTime']['id'];
			$result['MTime'][$key]['item_id'] = $val['MTime']['item_id'];
			$result['MTime'][$key]['item_detail'] = $val['MTime']['item_detail'];
			$result['MTime'][$key]['item_category'] = $val['MTime']['item_category'];
			$result['MTime'][$key]['item_type'] = $val['MTime']['item_type'];
			$result['MTime'][$key]['item_hour_date'] = $val['MTime']['item_hour_date'];
			$result['MTime'][$key]['item_minute_date'] = $val['MTime']['item_minute_date'];
		}
		return $result;
	}

	/**
	 * 時間のドロップダウンリストを作成
	 *
	 * @param string $from
	 * @param string $to
	 * @return multitype:string
	 */
	private function __getTimelist($from = null, $to = null){

		$array = array();
		for($i=$from; $i<=$to; $i++){
			$array[$i] = $i;
		}

		return $array;
	}

	/**
	 * 祝日リストの取得
	 *
	 * @return unknown
	 */
	private function __getPublicHoliday(){
		$list = $this->PublicHoliday->find('all',
				array(
						'conditions' => array('PublicHoliday.holiday_date >=' => date('Y/m/d')),
						'order' => array('holiday_date' => 'asc'),
				)
		);

		$result = array();
		foreach ($list as $key => $val){
			$result['PublicHoliday'][$key]['id'] = $val['PublicHoliday']['id'];
			$result['PublicHoliday'][$key]['holiday_date'] = $val['PublicHoliday']['holiday_date'];
		}
		return $result;
	}

	/**
	 * 除外時間データの取得
	 *
	 * @return unknown
	 */
	private function __getExclusionTime(){
		$list = $this->ExclusionTime->find('all',
				array(
						'order' => array('pattern' => 'asc'),
				)
		);

		$result = array();
		foreach ($list as $key => $val){
			$result['ExclusionTime'][$key]['id'] = $val['ExclusionTime']['id'];
			$result['ExclusionTime'][$key]['exclusion_time_from'] = $val['ExclusionTime']['exclusion_time_from'];
			$result['ExclusionTime'][$key]['exclusion_time_to'] = $val['ExclusionTime']['exclusion_time_to'];
			$result['ExclusionTime'][$key]['exclusion_day'] = $val['ExclusionTime']['exclusion_day'];
		}
		return $result;
	}

	/**
	 * 祝日/除外時間の更新
	 *
	 * @param unknown $data
	 * @return unknown
	 */
	private function __editExclusion($data = array()){

		try {
			// トランザクション開始
			$this->PublicHoliday->begin();
			$this->ExclusionTime->begin();

			// 一旦削除
			$conditions ['modified_user_id is not'] = null;
			$resultsFlg = $this->PublicHoliday->deleteAll($conditions, false);

			// 祝日テーブルの更新
			$i = 0;
			$edit_data = array();
			foreach ($data['PublicHoliday'] as $key => $val){
				if(!empty($data['PublicHoliday'][$key]['holiday_date'])){
					$edit_data['PublicHoliday'][$i]['id'] = $data['PublicHoliday'][$key]['id'];
					$edit_data['PublicHoliday'][$i]['holiday_date'] = $data['PublicHoliday'][$key]['holiday_date'];
					$i++;
				}
			}
			// 登録が1件以上の場合のみsaveする
			if ( $i > 0 ) {
				$resultsFlg = $this->PublicHoliday->saveAll($edit_data['PublicHoliday']);
			}

			// 除外時間テーブルの更新
			if($resultsFlg){
				$i = 0;
				$edit_data = array();
				foreach ($data["ExclusionTime"] as $key => $val){
					$data['ExclusionTime'][$key]['exclusion_day'] = '';
					$exclusion_day = 0;
					if(isset($val['exclusion_day'])){
						$exclusion_day = self::__setExclusionDay($val['exclusion_day']);
						$data['ExclusionTime'][$key]['exclusion_day'] = $exclusion_day;
					}

					$edit_data['ExclusionTime'][$i]['id'] = $data['ExclusionTime'][$key]['id'];
					$edit_data['ExclusionTime'][$i]['pattern'] = $data['ExclusionTime'][$key]['pattern'];
					$edit_data['ExclusionTime'][$i]['exclusion_time_from'] = $data['ExclusionTime'][$key]['exclusion_time_from'];
					$edit_data['ExclusionTime'][$i]['exclusion_time_to'] = $data['ExclusionTime'][$key]['exclusion_time_to'];
					$edit_data['ExclusionTime'][$i]['exclusion_day'] = $exclusion_day;
					$i++;

				}

				if(!empty($edit_data)){
					$resultsFlg = $this->ExclusionTime->saveAll($edit_data['ExclusionTime']);
				}
			}

			if($resultsFlg){
				$this->PublicHoliday->commit();
				$this->ExclusionTime->commit();
			} else {
				$this->PublicHoliday->rollback();
				$this->ExclusionTime->rollback();
			}

		} catch (Exception $e) {
			$this->PublicHoliday->rollback();
			$this->ExclusionTime->rollback();
			$resultsFlg = false;
		}

		return $resultsFlg;
	}

	/**
	 * 土日祝日判定
	 *
	 * @param unknown $actions
	 * @return number
	 */
	private function __setExclusionDay($actions = array()){
		$result = 0x00;
		$result = sprintf('0b%08b', hexdec($result));

		foreach ((array) $actions as $action) {

			// 2進数に変換
			$val = sprintf('0b%08b', hexdec($action));

			// 論理和を求める
			$result = $result | $val;

		}

		// 10進数に変換して返却
		return bindec($result);

	}

	/**
	 * フォロー案件一覧
	 */
	public function follow() {

		// フォロー時間設定を取得
		$follow_data = $this->MTime->find('all', array('conditions' => array('item_category' => 'follow_tel')));

		$spare_time = null;

		foreach($follow_data as $row) {

			if ($row['MTime']['item_detail'] == 'spare_time') {
				$spare_time = $row['MTime']['item_hour_date'];
			}
		}

		$data = array();
		$page = null;

		$joins = array(
				array(
						'table' => 'auction_infos'
						,'alias' => "AuctionInfo"
						,'type' => 'inner'
						,'conditions' => array('DemandInfo.id = AuctionInfo.demand_id','AuctionInfo.responders IS NOT NULL')
				),
				// 2015.12.29 ohta ORANGE-1027 visit_time変更による修正 コメントアウト
//				array(
//						'table' => 'visit_times',
//						'alias' => "VisitTime",
//						'type' => 'inner',
//						'conditions' => array('DemandInfo.id = VisitTime.demand_id', 'AuctionInfo.visit_time_id = VisitTime.id')
//				),
//  2015.09.21 h.hanaki ADD(S) ORANGE_AUCTION-13
				array(
					'table' => 'm_sites'
					,'alias' => "MSite"
					,'type' => 'inner'
					,'conditions' => array('DemandInfo.site_id = MSite.id')
				),
				array(
						'table' => 'm_categories'
						,'alias' => "MCategory"
						,'type' => 'left'
						,'conditions' => array('DemandInfo.category_id = MCategory.id')
				),
				array('fields' => '*',
						'type' => 'left',
						'table' => 'commission_infos',
						'alias' => 'CommissionInfo',
						'conditions' => array('CommissionInfo.demand_id = DemandInfo.id', 'CommissionInfo.commit_flg = 1')
				),
				array(
						'table' => 'm_corps',
						'alias' => "MCorp",
						'type' => 'left',
						'conditions' => array('CommissionInfo.corp_id = MCorp.id')
				),
				array(
						'table' => 'visit_time_view_sort',
						'alias' => "VisitTime",
						'type' => 'left',
						'conditions' => array('CommissionInfo.commission_visit_time_id = VisitTime.id')
				),
		);

		$conditions = array();

		// 確定フラグが立っているかつ、施工完了・失注以外
		$sql = 'exists(SELECT demand_id FROM commission_infos INNER JOIN demand_infos ON commission_infos.demand_id = demand_infos.id
					WHERE commission_infos.commit_flg = 1 and commission_infos.commission_status not in ('. Util::getDivValue('construction_status', 'construction'). ','. Util::getDivValue('construction_status', 'order_fail'). '))';


		$conditions = array($sql);

// 2016.11.28 ORANGE-185 CHG(S)
		// オークション選定
		$conditions['DemandInfo.selection_system'] = array(
			Util::getDivValue('selection_type', 'AuctionSelection'),
			Util::getDivValue('selection_type', 'AutomaticAuctionSelection'));
// 2016.11.28 ORANGE-185 CHG(E)
		$conditions['DemandInfo.follow !='] = 1;

		// フォロー時間設定以上
		$conditions['"VisitTime"."visit_time" - "DemandInfo"."auction_start_time" > '] = $spare_time.' hours';

//  2015.09.21 h.hanaki ADD(S) ORANGE_AUCTION-13
//  2015.12.29 ohta ORANGE-1027 visit_time変更による修正
		$fields = 'DemandInfo.*,MSite.site_name, MCategory.category_name, MCorp.id, MCorp.corp_name, VisitTime.id, VisitTime.demand_id, VisitTime.visit_time, VisitTime.is_visit_time_range_flg, VisitTime.visit_time_to, VisitTime.visit_adjust_time';
// 2015.12.29 ohta ORANGE-1027 visit_time変更による修正コメントアウト
		//$fields .= ', (SELECT ARRAY_TO_STRING(ARRAY( SELECT visit_time FROM visit_times WHERE visit_times.demand_id = "DemandInfo"."id" ORDER BY visit_times.visit_time ASC ),\'｜\')) as "visit_time" ';
//  2015.09.21 h.hanaki ADD(E) ORANGE_AUCTION-13

		$this->paginate = array(
//  2015.09.21 h.hanaki ADD(S) ORANGE_AUCTION-13
		//				'fields' => 'DemandInfo.*, VisitTime.visit_time'
				'fields' => $fields
//  2015.09.21 h.hanaki ADD(E) ORANGE_AUCTION-13
				,'conditions' => $conditions
				,'joins' => $joins
				,'limit' => Configure::read('report_list_limit')
				,'order' => 'DemandInfo.auction_start_time asc'

		);

		$this->DemandInfo->virtualFields = array(
				'follow_tel_date' => 'DemandInfo.auction_start_time',
				'corp_name' => 'MCorp.corp_name',
		);
		$sortKey = array(
			'DemandInfo.id',
			'DemandInfo.customer_name',
			'DemandInfo.follow_tel_date',
			'VisitTime.visit_time',
			'DemandInfo.site_id',
			'DemandInfo.category_id',
			'DemandInfo.address1',
			'DemandInfo.corp_name',
		);
		$results = $this->paginate('DemandInfo', array(), $sortKey);

		// フォロー日時を計算
		foreach($results as &$row) {
			$row['DemandInfo']['follow_tel_date'] = $this->MTime->getFollowTimeWithData($row['DemandInfo']['auction_start_time'], $row['VisitTime']['visit_time'], $follow_data);
		}

		$this->set('results', Sanitize::clean($results));
	}

	/**
	 * 対応ボタン回数ランキング
	 */
	public function ranking() {

		// 検索条件の設定
		$conditions = array();

		if (!isset($this->request->data['Ranking']['aggregate_date'])) {
			$aggregate_date = date('Y-m-d');
			$this->request->data['Ranking']['aggregate_date'] = $aggregate_date;
			$this->request->data['Ranking']['aggregate_period'] = 'day';
		} else {
			$aggregate_date = $this->request->data['Ranking']['aggregate_date'];
		}

// 2016.11.17 murata.s ORANGE-185 CHG(S)
		// オークション選定
// 		$conditions['DemandInfo.selection_system'] = Util::getDivValue('selection_type', 'AuctionSelection');
		$conditions['DemandInfo.selection_system'] = array(
				Util::getDivValue('selection_type', 'AuctionSelection'),
				Util::getDivValue('selection_type', 'AutomaticAuctionSelection'),
		);
// 2016.11.17 murata.s ORANGE-185 CHG(E)

		// 確定フラグ「1」
		$conditions['CommissionInfo.commit_flg'] = 1;

		$conditions["to_char(CommissionInfo.created, 'yyyy-mm-dd') <="] = $aggregate_date;

		$aggregate_period = null;
		if ($this->request->data['Ranking']['aggregate_period'] == 'day') {
			$aggregate_period = date('Y-m-d', strtotime($aggregate_date. ' - 1 day'));
		} elseif ($this->request->data['Ranking']['aggregate_period'] == 'week') {
			$aggregate_period = date('Y-m-d', strtotime($aggregate_date. ' - 7 day'));
		} else {
			$aggregate_period = date('Y-m-d', strtotime($aggregate_date. ' - 30 day'));
		}

		$conditions["to_char(CommissionInfo.created, 'yyyy-mm-dd') >="] = $aggregate_period;

		// 対応ボタン回数ランキング取得
		$params = array('conditions' => $conditions,
				'fields' => 'CommissionInfo.corp_id, MCorp.official_corp_name, count(*) as "CommissionInfo__ranking"',
				'joins' => array (
						array (
								'type' => 'inner',
								"table" => "demand_infos",
								"alias" => "DemandInfo",
								"conditions" => array (
										"CommissionInfo.demand_id = DemandInfo.id",
								)
						),
				),
				'limit' => Configure::read('list_limit'),
				'group' => array (
						'CommissionInfo.corp_id',
						'MCorp.official_corp_name',
				),
				'order' => array('CommissionInfo__ranking' => 'desc'),
		);

		$this->CommissionInfo->virtualFields = array(
				'ranking' => 'CommissionInfo.ranking',
		);

		if (isset($this->request->data['csv'])) {

			unset($params['limit']);
			$data_list = $this->CommissionInfo->find('all', $params);
			$file_name = 'auction_ranking_'. date('YmdHis');
			$this->Csv->download('CommissionInfo', 'auction_ranking', $file_name, $data_list);

		} else {

			$this->paginate = $params;
			$results = $this->paginate('CommissionInfo');

			// 値をセット
			$this->set('results', Sanitize::clean($results));

		}

	}

	/**
	 * オークション流れランキング
	 */
	public function flowing() {

		// 検索条件の設定
		$conditions = array();

		// 集計用にアソシエーションを解除
		$this->DemandInfo->unbindModelAll(false);

		$results = array();

		if (!empty($this->request->data)) {

			if ( !is_array($this->request->data['genre_id']) ) {
				$this->request->data['genre_id'] = array();
			}

			foreach ($this->request->data['genre_id'] as $val) {

				$conditions = array();

				// ジャンル
				//$conditions["DemandInfo.genre_id"] = $this->request->data['genre_id'];
				$conditions["DemandInfo.genre_id"] = $val;

				// オークション選定
				//$conditions['DemandInfo.selection_system'] = Util::getDivValue('selection_type', 'AuctionSelection');

				// 年
				$conditions["DemandInfo.years"] = $this->request->data['year'];

				// 当該年のオークション件数を取得
				$params = array('conditions' => $conditions,
						'fields' => 'DemandInfo.address1, count(*) as "DemandInfo__auction_count"',
						'group' => array (
								'DemandInfo.address1',
						),
				);

				$this->DemandInfo->virtualFields = array(
						'auction_count' => 'DemandInfo.auction_count',
						'years' => 'date_part(\'year\', DemandInfo.created)',
				);

				$year_data = $this->DemandInfo->find('all', $params);

				// オークション流れ案件
				$conditions['DemandInfo.auction'] = 1;

				// 当該年のオークション流れ件数を取得
				$params = array('conditions' => $conditions,
						'fields' => 'DemandInfo.address1, count(*) as "DemandInfo__auction_count"',
						'group' => array (
								'DemandInfo.address1',
						),
				);

				$year_flowing_data = $this->DemandInfo->find('all', $params);


				// 当該月のオークション件数を取得
				$conditions = array();

				// ジャンル
				$conditions["DemandInfo.genre_id"] = $this->request->data['genre_id'];

// 2016.11.17 murata.s ORANGE-185 CHG(S)
				// オークション選定
// 				$conditions['DemandInfo.selection_system'] = Util::getDivValue('selection_type', 'AuctionSelection');
				$conditions['DemandInfo.selection_system'] = array(
						Util::getDivValue('selection_type', 'AuctionSelection'),
						Util::getDivValue('selection_type', 'AutomaticAuctionSelection')
				);
// 2016.11.17 murata.s ORANGE-185 CHG(E)

				// 年
				$conditions["DemandInfo.years"] = $this->request->data['year'];

				// 月
				$conditions["DemandInfo.months"] = $this->request->data['month'];

				// 当該月のオークション件数を取得
				$params = array('conditions' => $conditions,
						'fields' => 'DemandInfo.address1, count(*) as "DemandInfo__auction_count"',
						'group' => array (
								'DemandInfo.address1',
						),
				);

				$this->DemandInfo->virtualFields = array(
						'auction_count' => 'DemandInfo.auction_count',
						'years' => 'date_part(\'year\', DemandInfo.created)',
						'months' => 'date_part(\'month\', DemandInfo.created)',
				);

				$month_data = $this->DemandInfo->find('all', $params);

				// オークション流れ案件
				$conditions['DemandInfo.auction'] = 1;

				// 当該月のオークション流れ件数を取得
				$params = array('conditions' => $conditions,
						'fields' => 'DemandInfo.address1, count(*) as "DemandInfo__auction_count"',
						'group' => array (
								'DemandInfo.address1',
						),
				);

				$month_flowing_data = $this->DemandInfo->find('all', $params);

				// データの整形
				for ($i = 1; $i <= 47; $i++) {
					$row = array();

					// 2015.09.15 n.kai ADD start オークション選定
					$row['genre_id'] = $i;
					$row['genre_name'] = $this->MGenre->getListText($val);
					// 2015.09.15 n.kai ADD end
					$row['prefecture_cd'] = $i;
					$row['prefecture_name'] = Util::getDivTextJP('prefecture_div', $i);
					$row['year_count'] = 0;
					$row['year_flowing_ratio'] = '0%';
					$row['month_count'] = 0;
					$row['month_flowing_ratio'] = '0%';

					foreach ($year_data as $r1) {
						$row['year_count'] = $i == $r1['DemandInfo']['address1'] ? $r1['DemandInfo']['auction_count'] : 0;
					}

					foreach ($year_flowing_data as $r2) {
						if ($row['year_count'] != 0) {
							$row['year_flowing_ratio'] = floor($r2['DemandInfo']['auction_count'] / $row['year_count'] * 100). '%';
						} else {
							$row['year_flowing_ratio'] = '0%';
						}
					}

					foreach ($month_data as $r1) {
						$row['month_count'] = $i == $r1['DemandInfo']['address1'] ? $r1['DemandInfo']['auction_count'] : 0;
					}

					foreach ($month_flowing_data as $r2) {
						if ($row['month_count'] != 0) {
							$row['month_flowing_ratio'] = floor($r2['DemandInfo']['auction_count'] / $row['month_count'] * 100). '%';
						} else {
							$row['month_flowing_ratio'] = '0%';
						}
					}

					$results[] = $row;

				}  // -- end for

			} // -- end foreach

		}
		if (isset($this->request->data['csv'])) {

			$file_name = 'auction_flowing_'. date('YmdHis');
 			$this->Csv->download('DemandInfo', 'auction_flowing', $file_name, $results);

		} else {

 			// 値をセット
 			$this->set('results', Sanitize::clean($results));

		}

		// 年ドロップダウン
		$base_year = Configure::read('dropdown_base_year');
		$year_list = array();
		for ($i = $base_year; $i <= date('Y'); $i++) {
			$year_list[$i] = $i. __('year_title', true);
		}

		// 月ドロップダウン
		$month_list = array();
		for ($i = 1; $i <= 12; $i++) {
			$month_list[$i] = $i. __('month_title', true);
		}

		$this->set('year_list', $year_list);
		$this->set('month_list', $month_list);
	}
}
