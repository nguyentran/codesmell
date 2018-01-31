<?php
App::uses('AppController', 'Controller');

class AutoSelectController extends AppController {

	public $name = 'AutoSelect';
	public $components = array('Session', 'RequestHandler');
	public $uses = array('AutoSelectGenre', 'MGenre', 'AutoSelectSetting', 'AutoSelectCorp', 'AutoSelectPrefecture');

	public function beforeFilter(){
		parent::beforeFilter();

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
	 * ジャンル別自動選定設定
	 */
	public function genre_setting() {

		// 登録処理
		if (isset($this->request->data['regist'])) {

			if ($this->AutoSelectGenre->saveAll($this->data['AutoSelectGenre'])){
				$this->Session->setFlash (__('SuccessRegist', true), 'default', array ('class' => 'message_inner'));
			} else {
				$this->Session->setFlash (__('FailRegist', true), 'default', array ('class' => 'error_inner'));
			}

		}

		// 自動設定ジャンル一覧を取得
		$list = $this->MGenre->getAutoSelectGenre();

		$this->set('genres', $list);

	}

	/**
	 * ジャンル別都道府県自動選定設定
	 */
	public function prefecture_setting($genre_id = null) {

		// 登録処理
		if (isset($this->request->data['regist'])) {

			$result = true;
			$genre_id = $this->request->data['genre_id'];

			try {

				$data = $this->__getPrefectureData($this->data['AutoSelectPrefecture']);
				$this->AutoSelectPrefecture->begin();

				foreach ($data as $key => $val){
					if(isset($val["prefecture_cd"])){  // データの更新
						if (!$this->AutoSelectPrefecture->save($val)) {
							$result = false;
							break;
						}
					} else {   // データの削除
						if(!$this->AutoSelectPrefecture->delete($val)){
							$result = false;
							break;
						}
					}
				}

			} catch ( Exception $e ) {
			}

			if ($result) {
				$this->AutoSelectPrefecture->commit();
				$this->Session->setFlash (__('SuccessRegist', true), 'default', array ('class' => 'message_inner'));
				$this->redirect('/auto_select/prefecture_setting/'. $genre_id);
			} else {
				$this->AutoSelectPrefecture->rollback();
				$this->Session->setFlash (__('FailRegist', true), 'default', array ('class' => 'error_inner'));
			}
		}

		// ジャンル別都道府県自動選定データを取得
		$list = $this->AutoSelectPrefecture->getAutoSelectPrefecture($genre_id);
		$genre = $this->MGenre->findById($genre_id);

		$this->set('genres', $list);
		$this->set('genre_id', $genre_id);
		$this->set('genre_name', $genre['MGenre']['genre_name']);
	}

	/**
	 * 自動選定都道府県自動選定設定
	 *
	 * @param string $genre_id
	 */
	public function corp_setting($genre_id = null) {

		if (isset($this->request->data['regist'])){
			$genre_id = $this->request->data['genre_id'];
			if(empty($genre_id)){
				$this->redirect('/auto_select/genre_setting/');
			}
			$data = $this->request->data;
			$this->data = $data;
			if(self::__editCorpSetting($data, $genre_id)){
				$this->Session->setFlash (__('SuccessRegist', true), 'default', array ('class' => 'message_inner'));
				$this->redirect('/auto_select/corp_setting/'. $genre_id);
			} else {
				$this->Session->setFlash (__('FailRegist', true), 'default', array ('class' => 'error_inner'));
			}

		} else {

			if(empty($genre_id)){
				$this->redirect('/auto_select/genre_setting/');
			}
			// 自動選定企業情報リストを取得
			$corp_list = self::__getAutoSelectCorpList($genre_id);
			// 自動選定自動選定都道府県リストの取得
			$prefecture_list = self::__getAutoSelectPrefectureList($genre_id);
			$data = array('AutoSelect' => $corp_list, 'AutoSelectPrefecture' => $prefecture_list);
			$this->data = $data;
		}
		$this->set('genre_id', $genre_id);
	}

	/**
	 * 都道府県自動選定更新処理
	 *
	 * @param unknown $data
	 * @param unknown $genre_id
	 * @return boolean
	 */
	private function __editCorpSetting($data, $genre_id){

		$resultsFlg = true; // 更新結果フラグ
		try {
			$this->AutoSelectPrefecture->begin();
			$this->AutoSelectCorp->begin();
			$this->AutoSelectSetting->begin();

			if (!$this->AutoSelectPrefecture->saveAll( $data['AutoSelectPrefecture'] )) {
				$resultsFlg = false;
			}

			$delete_data = array();
			if($resultsFlg){
				$edit_data = self::__updateCreating( $data['AutoSelect'], $delete_data);
				$this->Session->write(self::$__sessionKeyForAutoSelect, $data);
				if (!$this->AutoSelectCorp->saveAll( $edit_data )) {
					$resultsFlg = false;
				}
				$this->Session->delete(self::$__sessionKeyForAutoSelect);
			}

			if($resultsFlg){
				if(!empty($delete_data)){
					foreach ($delete_data as $val){
						if(!empty($val["AutoSelectCorp"]['id'])){
							$this->AutoSelectCorp->delete($val["AutoSelectCorp"]['id']);
						}
					}
				}
			}

			if($resultsFlg){
				if(!self::__editAutoSelectSetting($data, $genre_id)){
					$resultsFlg = false;
				}
			}

			if($resultsFlg){
				$this->AutoSelectPrefecture->commit();
				$this->AutoSelectCorp->commit();
				$this->AutoSelectSetting->commit();
			} else {
				$this->AutoSelectPrefecture->rollback();
				$this->AutoSelectCorp->rollback();
				$this->AutoSelectSetting->rollback();
			}
		} catch ( Exception $e ) {
			$result = false;
		}

		return $resultsFlg;
	}

	/**
	 * 変更レコード作成
	 *
	 * @param unknown $data
	 * @param unknown $delete_data
	 * @return unknown
	 */
	private function __updateCreating($data, &$delete_data){

		$edit_data = $data;
		foreach ($data AS $key => $val){
			if(isset($val["AutoSelectPrefecture"]['delete'])){
				unset($edit_data[$key]);
				$delete_data[] = $val;
			}
		}

		return $edit_data;
	}

	/**
	 * 自動選定パターンテーブルの更新
	 *
	 * @param unknown $data
	 * @param unknown $genre_id
	 * @return boolean
	 */
	private function __editAutoSelectSetting($data, $genre_id){

		foreach ($data['AutoSelectPrefecture'] AS $val){

			// 対象データの削除
			$conditions = array (
					'AutoSelectSetting.genre_id' => $genre_id,
					'AutoSelectSetting.prefecture_cd' => $val ["AutoSelectPrefecture"] ["prefecture_cd"]
			);
			$this->AutoSelectSetting->deleteAll($conditions, false);

			// データの並び変え
			$list = array();
			foreach ($data['AutoSelect'] AS $key => $v){
				if($v['AutoSelectCorp']['prefecture_cd'] == $val['AutoSelectPrefecture']['prefecture_cd'] && !isset($v["AutoSelectPrefecture"]['delete'])){
					$list[]['AutoSelectCorp'] = $v["AutoSelectCorp"];
				}
			}

			$max_display_order = 0;
			if(!empty($list) && 1 < count($list)){
				$key_display_order = array();
				foreach ($list as $key => $value){
					$key_display_order[$key] = $value['AutoSelectCorp']['display_order'];
					if($max_display_order < $value['AutoSelectCorp']['display_order']){
						$max_display_order = $value['AutoSelectCorp']['display_order'];
					}
				}
				array_multisort ($key_display_order, SORT_ASC , $list);
			}

			$manual_ratio = '';
			foreach ($data['AutoSelectPrefecture'] AS $key => $v){
				if($v['AutoSelectPrefecture']['prefecture_cd'] = $val['AutoSelectPrefecture']['prefecture_cd']){
					$manual_ratio = $v['AutoSelectPrefecture']['manual_ratio'];
				}
			}
			if(!empty($manual_ratio)){
				$list [] = array (
						'AutoSelectCorp' => array (
								'id' => '',
								'display_order' => $max_display_order + 1 ,
								'genre_id' => $genre_id ,
								'prefecture_cd' => $val["AutoSelectPrefecture"]["prefecture_cd"] ,
								'corp_id' => 0 ,
								'ratio' => $manual_ratio ,
						)
				);
			}

			// 登録データの作成
			$edit_list = self::__getAutoSelectPatternData($genre_id, $val["AutoSelectPrefecture"]["prefecture_cd"], $val["AutoSelectPrefecture"]["pattern_cd"], $list);
			if (!$this->AutoSelectSetting->saveAll($edit_list)) {
				return false;
			}
		}
		return true;
	}


	/**
	 * 都道府県コードを含まない配列を削除する
	 * @param unknown $data
	 */
	private function __getPrefectureData($data){

		foreach($data as $key => $row) {

			if (!array_key_exists('prefecture_cd', $row) && empty($row['id'])) {
				unset($data[$key]);
			}
		}

		return $data;
	}

	/**
	 * 自動選定自動選定都道府県リストの取得
	 *
	 * @param string $genre_id
	 * @return unknown
	 */
	private function __getAutoSelectPrefectureList($genre_id = null){
		$list = $this->AutoSelectPrefecture->find('all', array (
				'fields' => '*',
				'conditions' => array (
						'AutoSelectPrefecture.genre_id' => $genre_id
				),
				'order' => array (
						'AutoSelectPrefecture.prefecture_cd' => 'ASC',
				)
		) );

		return $list;
	}

	/**
	 * 自動選定企業情報リストを取得
	 *
	 * @param string $genre_id
	 * @return unknown
	 */
	private function __getAutoSelectCorpList($genre_id = null){

		// 前月取次件数
		$fieldLastMonth = '(SELECT count(*) FROM commission_infos INNER JOIN demand_infos ON demand_infos.id = commission_infos.demand_id WHERE commission_infos.corp_id = "AutoSelectCorp"."corp_id" AND commission_infos.auto_select_flg = 1 AND demand_infos.genre_id = "AutoSelectCorp"."genre_id" AND commission_infos.commission_note_send_datetime <= DATE_TRUNC(\'month\', now()) AND commission_infos.commission_note_send_datetime > DATE_TRUNC(\'month\',CURRENT_DATE - INTERVAL \'1 month\') ) AS "AutoSelectPrefecture__last_month_count"';

		// 当月取次件数
		$fieldPresentMonth = '(SELECT count(*) FROM commission_infos INNER JOIN demand_infos ON demand_infos.id = commission_infos.demand_id WHERE commission_infos.corp_id = "AutoSelectCorp"."corp_id" AND commission_infos.auto_select_flg = 1 AND demand_infos.genre_id = "AutoSelectCorp"."genre_id" AND commission_infos.commission_note_send_datetime >= DATE_TRUNC(\'month\', now())) AS "AutoSelectPrefecture__present_month_count"';

		// 本日取次件数
		$fieldToday = '(SELECT count(*) FROM commission_infos INNER JOIN demand_infos ON demand_infos.id = commission_infos.demand_id WHERE commission_infos.corp_id = "AutoSelectCorp"."corp_id" AND commission_infos.auto_select_flg = 1 AND demand_infos.genre_id = "AutoSelectCorp"."genre_id" AND date_trunc(\'day\' , commission_infos.commission_note_send_datetime) = CURRENT_DATE ) AS "AutoSelectPrefecture__today_count"';

		$list = $this->AutoSelectPrefecture->find('all', array (
				'fields' => '*, AutoSelectCorp.id , AutoSelectCorp.genre_id, AutoSelectCorp.genre_id, AutoSelectCorp.prefecture_cd , AutoSelectCorp.corp_id, AutoSelectCorp.limit_per_day, AutoSelectCorp.limit_per_month , AutoSelectCorp.ratio, AutoSelectCorp.display_order, MCorp.official_corp_name, AffiliationAreaStat.commission_unit_price_category, '.$fieldToday.', '.$fieldPresentMonth.', '.$fieldLastMonth,
				'joins' => array (
						array (
								'fields' => '*',
								'type' => 'LEFT',
								"table" => "auto_select_corps",
								"alias" => "AutoSelectCorp",
								"conditions" => array (
										"AutoSelectCorp.prefecture_cd = AutoSelectPrefecture.prefecture_cd",
										"AutoSelectCorp.genre_id = AutoSelectPrefecture.genre_id"
								)
						),
						array (
								'fields' => '*',
								'type' => 'LEFT',
								"table" => "m_corps",
								"alias" => "MCorp",
								"conditions" => array (
										"MCorp.id = AutoSelectCorp.corp_id"
								)
						),
						array (
								'fields' => '*',
								'type' => 'LEFT',
								"table" => "affiliation_area_stats",
								"alias" => "AffiliationAreaStat",
								"conditions" => array (
										"AffiliationAreaStat.corp_id = AutoSelectCorp.corp_id",
										"AffiliationAreaStat.genre_id = AutoSelectPrefecture.genre_id",
										"AffiliationAreaStat.prefecture = AutoSelectCorp.prefecture_cd"
								)
						),
				),
				'conditions' => array (
						'AutoSelectPrefecture.genre_id' => $genre_id
				),
				'order' => array (
						'AutoSelectPrefecture.prefecture_cd' => 'ASC',
						'AutoSelectCorp.display_order' => 'ASC'
				)
		) );

		return $list;
	}

	/**
	 * 自動選定パターンデータを作成し、返却
	 * @param unknown $data
	 * @return NULL
	 */
	private function __getAutoSelectPatternData($genre_id, $prefecture_cd, $pattern_cd, $data = array()){

		$pattern = array();
		$arr = $data;

		if (isset($arr)) {

			$key_id = array();
			foreach ($arr as $key => $value){

				// 比率が「0」のデータを削除
				if ($value['AutoSelectCorp']['ratio'] == 0) {
					unset($arr[$key]);
					continue;
				}

				$key_id[$key] = $value['AutoSelectCorp']['display_order'];

				// 比率を10で除算
				$arr[$key]['AutoSelectCorp']['times'] = $value['AutoSelectCorp']['ratio'] / 10;
			}

			// 表示順でソート
			array_multisort($key_id, SORT_ASC, $arr);

			// パターン別処理振り分け
			switch ($pattern_cd) {
				case 'A':
					$pattern = $this->__createPatternA($genre_id, $prefecture_cd, $pattern_cd, $arr);
					break;
				case 'B':
					$pattern = $this->__createPatternB($genre_id, $prefecture_cd, $pattern_cd, $arr);
					break;
				case 'C':
					$pattern = $this->__createPatternC($genre_id, $prefecture_cd, $pattern_cd, $arr);
					break;
				case 'D':
					$pattern = $this->__createPatternD($genre_id, $prefecture_cd, $pattern_cd, $arr);
					break;

			}

		}

		return $pattern;
	}


	/**
	 * パターンAのデータを作成
	 * (優先順位順に割り当て)
	 * @param unknown $genre_id
	 * @param unknown $prefecture_cd
	 * @param unknown $pattern_cd
	 * @param unknown $data
	 */
	private function __createPatternA($genre_id, $prefecture_cd, $pattern_cd, $data) {

		$pattern = array();
		$seq_no = 1;

		foreach ($data as $row) {

			$times = $row['AutoSelectCorp']['times'];		// 各出現回数
			$corp_id = $row['AutoSelectCorp']['corp_id'];

			while ($times > 0) {
				$arr = array();

				$arr['AutoSelectSetting']['genre_id'] = $genre_id;
				$arr['AutoSelectSetting']['prefecture_cd'] = $prefecture_cd;
				$arr['AutoSelectSetting']['seq_no'] = $seq_no;
				$arr['AutoSelectSetting']['corp_id'] = $corp_id;
				$arr['AutoSelectSetting']['select_flg'] = $seq_no == 1 ? 1 : 0;

				array_push($pattern, $arr);

				$seq_no++;
				$times--;
			}

		}

		return $pattern;
	}

	/**
	 * パターンBのデータを作成
	 * (優先順位順に2回に分けて割り当て)
	 * @param unknown $genre_id
	 * @param unknown $prefecture_cd
	 * @param unknown $pattern_cd
	 * @param unknown $data
	 */
	private function __createPatternB($genre_id, $prefecture_cd, $pattern_cd, $data) {

		$pattern = array();
		$pattern2 = array();
		$seq_no = 1;
		$count = 1;

		foreach ($data as $row) {

			$times = $row['AutoSelectCorp']['times'];		// 各出現回数
			$corp_id = $row['AutoSelectCorp']['corp_id'];
			$count = 1;										// 企業IDごとカウント数

			while ($times > 0) {
				$arr = array();

				$arr['AutoSelectSetting']['genre_id'] = $genre_id;
				$arr['AutoSelectSetting']['prefecture_cd'] = $prefecture_cd;
				$arr['AutoSelectSetting']['seq_no'] = $seq_no;
				$arr['AutoSelectSetting']['corp_id'] = $corp_id;
				$arr['AutoSelectSetting']['select_flg'] = $seq_no == 1 ? 1 : 0;

				if ($count % 2 != 0) {
					array_push($pattern, $arr);
				} else {
					array_push($pattern2, $arr);
				}

				$seq_no++;
				$times--;
				$count++;
			}

		}

		$pattern = array_merge($pattern, $pattern2);

		// seq_noの振り直し
		$seq_no = 1;
		foreach ($pattern as $key => $value) {
			$pattern[$key]['AutoSelectSetting']['seq_no'] = $seq_no;
			$seq_no++;
		}

		return $pattern;
	}

	/**
	 * パターンCのデータを作成
	 * (優先順位順に順番に割り当て)
	 * @param unknown $genre_id
	 * @param unknown $prefecture_cd
	 * @param unknown $pattern_cd
	 * @param unknown $data
	 */
	private function __createPatternC($genre_id, $prefecture_cd, $pattern_cd, $data) {

		$pattern = array();
		$seq_no = 1;

		// 全出現回数を抽出(10回だが、動的にしておく)
		$total_count = 0;
		foreach ($data as $row) {
			$total_count += $row['AutoSelectCorp']['times'];
		}

		$data_size = count($data);	// 元データの最大サイズ
		$position = 0;				// 参照する配列の位置

		while ($seq_no <= $total_count) {

			if ($position == $data_size) {
				$position = 0;
			}

			$row = $data[$position];

			$times = $row['AutoSelectCorp']['times'];
			$corp_id = $row['AutoSelectCorp']['corp_id'];

			if ($times != 0) {

				// 出現回数を減算
				$data[$position]['AutoSelectCorp']['times']--;

				$arr = array();

				$arr['AutoSelectSetting']['genre_id'] = $genre_id;
				$arr['AutoSelectSetting']['prefecture_cd'] = $prefecture_cd;
				$arr['AutoSelectSetting']['seq_no'] = $seq_no;
				$arr['AutoSelectSetting']['corp_id'] = $corp_id;
				$arr['AutoSelectSetting']['select_flg'] = $seq_no == 1 ? 1 : 0;

				array_push($pattern, $arr);

				$seq_no++;
			}

			$position++;

		}

		return $pattern;
	}

	/**
	 * パターンDのデータを作成
	 * (優先順位順に順番に2件ずつ割り当て)
	 * @param unknown $genre_id
	 * @param unknown $prefecture_cd
	 * @param unknown $pattern_cd
	 * @param unknown $data
	 */
	private function __createPatternD($genre_id, $prefecture_cd, $pattern_cd, $data) {

		$pattern = array();
		$seq_no = 1;

		// 全出現回数を抽出(10回だが、動的にしておく)
		$total_count = 0;
		foreach ($data as $row) {
			$total_count += $row['AutoSelectCorp']['times'];
		}

		$data_size = count($data);	// 元データの最大サイズ
		$position = 0;				// 参照する配列の位置

		while ($seq_no <= $total_count) {

			if ($position == $data_size) {
				$position = 0;
			}

			// 2回連続で処理を行う。
			$i = 0;
			for (; $i < 2; $i++) {

				$row = $data[$position];

				$times = $row['AutoSelectCorp']['times'];
				$corp_id = $row['AutoSelectCorp']['corp_id'];

				if ($times != 0) {

					// 出現回数を減算
					$data[$position]['AutoSelectCorp']['times']--;

					$arr = array();

					$arr['AutoSelectSetting']['genre_id'] = $genre_id;
					$arr['AutoSelectSetting']['prefecture_cd'] = $prefecture_cd;
					$arr['AutoSelectSetting']['seq_no'] = $seq_no;
					$arr['AutoSelectSetting']['corp_id'] = $corp_id;
					$arr['AutoSelectSetting']['select_flg'] = $seq_no == 1 ? 1 : 0;

					array_push($pattern, $arr);

					$seq_no++;
				}
			}

			$position++;
		}

		return $pattern;
	}


}
