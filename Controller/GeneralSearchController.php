<?php
App::uses('AppController', 'Controller');

class GeneralSearchController extends AppController {

	public $name = 'GeneralSearch';
	public $helpers = array('Csv');
	public $components = array('Session', 'Csv', 'RequestHandler');
// 2015.07.24 h.hanaki  ORANGE-728 'MGenre'追加
	public $uses = array('GeneralSearch', 'MGenre', 'MSite');

	private $_err_message = "";
	private $_comp_message = "";

	public function beforeFilter(){

		$this->set('default_display', false);
		parent::beforeFilter();

// 2015.07.24 h.hanaki ADD(S) ORANGE-728
		// ドロップダウン用リストの取得
		$this->set("site_list" , $this->MSite->getList());
//		$this->set("m_genre_list" , $this->MGenre->getList());
// 2015.07.24 h.hanaki ADD(E) ORANGE-728


	}

	// 初期表示（一覧）
	public function index($id = null) {

		$default_display = true;

		$this->set('selected_item', "");

		$this->_comp_message = $this->Session->read("datas@generalSearch");
		$this->Session->delete("datas@generalSearch");

		if (isset( $this->request->data['regist'] )) {
			$id =$this->__registGeneralSearch($this->request->data);
			if (is_null($id) === false ) {
				$this->Session->write ( "datas@generalSearch", "保存が完了しました。" );

				return $this->redirect ( '/general_search/index/' . $id );
			}
		}

		if (isset( $this->request->data['delete'] )) {
			if ($this->__deleteGeneralSearch($this->data['MGeneralSearch']['id']) === true) {
				$this->_comp_message = "削除が完了しました。";
			}
		}

		if (isset( $this->request->data['csv'] )) {
// murata.s ORANGE-416 CHG(S)
			try{
				$dbo = $this->GeneralSearch->query('SET statement_timeout = '.GENERAL_SEARCH_CSV_TIMEOUT);

				Configure::write('debug', '0');
				$this->current_controller->layout = false;
				$data_list = $this->__getDataCsv($this->data['MGeneralSearch']['id'], $this->data['MGeneralSearch']['function_id']);

				$file_name = mb_convert_encoding($this->GeneralSearch->getCsvFileName($this->data['MGeneralSearch']['function_id']), 'SJIS-win', 'UTF-8');
				//$this->GeneralSearch->saveGeneralSearchHistory($this->data['MGeneralSearch']['id'], $file_name);
				$this->Csv->download('GeneralSearch', 'default', $file_name, $data_list);
				return;
			}catch(PDOException $e){
				if($e->getCode() == '57014'){
					// タイムアウトエラー
					$id = $this->request->data['MGeneralSearch']['id'];
					$this->_err_message = 'データ量が膨大なためデータベースへの接続がタイムアウトしました。<br>出力条件を変更してください。';
					$this->log($e->getMessage()."\n".$e->getTraceAsString());
				}else{
					throw $e;
				}
			}
// murata.s ORANGE-416 CHG(E)
		}

		if (!empty($id)) {
			$this->__searchGeneralSearch($id);
		} else {
			//初期表示
			$this->set("selected_item", $this->GeneralSearch->getDefaultSelectedItem());
		}

		/*
		echo "<pre>";
		print_r($this->GeneralSearch->findFunctionTableColumn(GeneralSearch::FUNCTION_CASE_MANAGEMENT));
		echo "</pre>";
		*/
		/*
		echo "<pre>";
		print_r($this->data);
		echo "</pre>";
		*/

		$this->set('err_message', $this->_err_message);
		$this->set('comp_message', $this->_comp_message);
		//$this->set('default_display', $default_display);
		$this->set('function_list', $this->GeneralSearch->dropDownFunctionId());
		$this->set('m_site_list', $this->GeneralSearch->_m_site_list);
		$this->set('user_list', $this->GeneralSearch->_m_user_list);
		$this->set('permissionSaveDel', $this->GeneralSearch->isEnabledDisplaySaveAndDel());
		$this->set('permissionBillInfo', $this->GeneralSearch->isEnabledDisplayBillInfo());
		$this->set('function_list0', $this->GeneralSearch->getFunctionColumnList(GeneralSearch::FUNCTION_CASE_MANAGEMENT));
		$this->set('function_list1', $this->GeneralSearch->getFunctionColumnList(GeneralSearch::FUNCTION_AGENCY_MANAGEMENT));
		$this->set('function_list2', $this->GeneralSearch->getFunctionColumnList(GeneralSearch::FUNCTION_CHARGE_MANAGEMENT));
		$this->set('function_list3', $this->GeneralSearch->getFunctionColumnList(GeneralSearch::FUNCTION_MEMBER_MANAGEMENT));
// 2015.07.24 h.hanaki ADD(S) ORANGE-728
//		$site_list = $this->MSite->getList();																		// サイト
//		$m_genre_list = $this->MGenre->getList();																// ジャンル
// 2015.07.24 h.hanaki ADD(E) ORANGE-728
	}

	/**
	 * 検索ページ
	 *
	 * @param string $id
	 * @throws Exception
	 */
	public function search($id = null) {

		if(!empty($id)){
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
		if (isset($this->request->data['csv_out'])){
			$data_list = $this->__getDataCsv($conditions);
			$file_name = mb_convert_encoding(__('demand' , true).'_'.$this->User['user_id'], 'SJIS-win', 'UTF-8');

			$this->Csv->download('DemandInfo', 'default', $file_name, $data_list);

		} else {

			$results = $this->__searchDemandInfo($conditions);

			$this->set('results', Sanitize::clean($results));

			$this->render('index');

		}
	}

	/**
	 * CSV出力処理
	 *
	 * @param unknown $conditions
	 * @return unknown
	 */
	private function __getDataCsv($id, $function_id){

		return $this->GeneralSearch->findGeneralSearchToCsv($id, $function_id);
	}

	/**
	 * GETパラメーターをセッションに保存
	 *
	 */
	private function __set_parameter_session(){
		$this->Session->delete(self::$__sessionKeyForDemandParameter);
		$this->Session->write(self::$__sessionKeyForDemandParameter, $this->params['named']);
	}

	/*
	 * 総合検索IDで情報を検索
	 */
	private function __searchGeneralSearch($id) {
		$results = $this->GeneralSearch->findGeneralSearch('all', array('conditions' => array('id = ' . $id)));
		$results = array_shift($results);
		//マスター
		$this->data += array('MGeneralSearch' => $results['MGeneralSearch']);
		//Item
		$selected_item = "";
		foreach ($results['G_S_Item'] as $gsItem) {
			if (strlen($selected_item) > 0)
				$selected_item .= ",";
			// ORANGE-1334 2016/3/28 murata CHG(S)
// TODO: カラムが重複してしまうかもしれない対応
				if($gsItem['function_id'] == 1){
					if($gsItem['table_name'] == 'visit_times' && $gsItem['column_name'] == 'visit_time')
						$selected_item .= '"' . $gsItem['table_name'] . "." . $gsItem['column_name'] . '.1"';
					else if($gsItem['table_name'] == 'demand_infos' && $gsItem['column_name'] == 'contact_desired_time')
						$selected_item .= '"' . $gsItem['table_name'] . "." . $gsItem['column_name'] . '.1"';
					else
						$selected_item .= '"' . $gsItem['table_name'] . "." . $gsItem['column_name'] . '"';
				}
				else
					$selected_item .= '"' . $gsItem['table_name'] . "." . $gsItem['column_name'] . '"';
			}
// ORANGE-1334 2016/3/28 murata CHG(E)
		//Condition
		$data = array();
		foreach ($results['G_S_Condition'] as $gsCondition) {
			if ($gsCondition['condition_expression'] == 0) {
				$data[ $gsCondition['condition_expression'] ][$gsCondition['table_name'] . "-" . $gsCondition['column_name']] = $gsCondition['condition_value'];
			}
			if ($gsCondition['condition_expression'] == 1) {
				$data[ $gsCondition['condition_expression'] ][$gsCondition['table_name'] . "-" . $gsCondition['column_name']] = $gsCondition['condition_value'];
			}
			if ($gsCondition['condition_expression'] == 2) {
				$data[ $gsCondition['condition_expression'] ][$gsCondition['table_name'] . "-" . $gsCondition['column_name']] = explode('^', $gsCondition['condition_value']);
			}
			if ($gsCondition['condition_expression'] == 3) {
				$data[ $gsCondition['condition_expression'] ][$gsCondition['table_name'] . "-" . $gsCondition['column_name']] = explode('^', $gsCondition['condition_value']);
			}
			if ($gsCondition['condition_expression'] == 4) {
				$data[ $gsCondition['condition_expression'] ][$gsCondition['table_name'] . "-" . $gsCondition['column_name']] = explode('^', $gsCondition['condition_value']);
			}
			if ($gsCondition['condition_expression'] == 9) {
				if ($gsCondition['table_name'] . "-" . $gsCondition['column_name'] == 'm_target_areas-jis_cd') {
					$data[ $gsCondition['condition_expression'] ][$gsCondition['table_name'] . "-" . $gsCondition['column_name']] = explode('^', $gsCondition['condition_value']);
				} else {
					$data[ $gsCondition['condition_expression'] ][$gsCondition['table_name'] . "-" . $gsCondition['column_name']] = $gsCondition['condition_value'];
				}
			}
		}
		$this->data += array('GeneralSearchCondition' => $data);

		$this->set("selected_item", $selected_item);
	}

	/*
	 * 画面からの総合検索情報を保存
	 *  @$params 画面からのPOSTデータ
	 */
	private function __registGeneralSearch($params) {
		try {
			$id = $this->GeneralSearch->saveGeneralSearch($params);
			return $id;
		} catch (Exception $e) {
			$this->_err_message = $e->getMessage();
			return null;
		}
	}

	/*
	 * 指定された総合検索情報を削除
	*  @$params 画面からのPOSTデータ
	*/
	private function __deleteGeneralSearch($id) {
		try {
			$this->GeneralSearch->deleteGeneralSearch($id);
			return true;
		} catch (Exception $e) {
			$this->_err_message = $e->getMessage();
			return false;
		}
	}
}
