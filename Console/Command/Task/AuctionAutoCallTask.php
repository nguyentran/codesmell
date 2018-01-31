<?php
App::uses('AppShell', 'Console/Command');

class AuctionAutoCallTask extends AppShell{

	public $uses = array('DemandInfo', 'AuctionInfo'
            // 2017.10.24 ORANGE-543 h.miyake ADD(S)
            , 'MCorp', 'MCorpNewYear', 'MCorpSub'
            // 2017.10.24 ORANGE-543 h.miyake ADD(E)
            );
	private static $user = 'system';

	/**
	 * オートコールを実施する
	 * @param boolean $auto_called
	 */
	public function execute($auto_called = false){

		$this->log('オートコール処理(AuctionAutoCallTask) 開始', AUTO_CALL_LOG);

		// オートコールの設定情報を取得
		$this->config = Configure::read('auto_call_setting');

		try{
			// オートコール対象の取得
			$auto_call_list = $this->__get_auto_call_list($auto_called);
            
            // 2017.10.24 ORANGE-543 h.miyake ADD(S)
            // 休業日はオートコールは実施しない
            $auto_call_list = $this->__get_auto_call_list_Holiday($auto_call_list);

            if(Configure::read('boolSalesFlag')) {
                    // 営業時間外ではオートコールは実施しない 
                    $auto_call_list = $this->__get_auto_call_list_eigyo($auto_call_list);
            }
            // 2017.10.24 ORANGE-543 h.miyake ADD(E)
            
			// 取次用ダイヤルからCSVファイルを作成
			$csv_path = $this->__make_csv($auto_call_list);
                        if(!empty($csv_path)){
				// オートコールの開始
				$this->__execute_auto_call($csv_path);

				// オークション情報の更新
				$save_data = array();
                foreach($auto_call_list as $key => $row){
					if($row['AuctionInfo']['auto_call_flg'] !== 1){
						$save_data['AuctionInfo'][$key]['id'] = $row['AuctionInfo']['id'];
						$save_data['AuctionInfo'][$key]['auto_call_flg'] = 1;
						$save_data['AuctionInfo'][$key]['modified_user_id'] = self::$user;
					}
				}

				if(!empty($save_data)){
					// 更新
					$this->AuctionInfo->saveAll($save_data['AuctionInfo']);
				}
			}else{
				// オートコール対象加盟店なし
				$this->log('オートコール対象加盟店なし', AUTO_CALL_LOG);
			}
		}catch(Exception $e){
			$this->log('AuctionAutoCallTask execute error. '.$e->getMessage(), SHELL_LOG);
		}

		$this->log('オートコール処理(AuctionAutoCallTask) 終了', AUTO_CALL_LOG);

	}

	/**
	 * オートコール対象一覧を取得する
	 */
	private function __get_auto_call_list($auto_called = false){

		$this->DemandInfo->unbindModelAll(false);
		$fields = array('AuctionInfo.id', 'AuctionInfo.auto_call_flg', 'MCorp.id', 'MCorp.official_corp_name' ,'MCorp.commission_dial', 'MCorp.auto_call_flag', 'MGenre.auto_call_flag');
		$conditions = array(
				'DemandInfo.selection_system' => array(
						Util::getDivValue('selection_type', 'AuctionSelection'),
						Util::getDivValue('selection_type', 'AutomaticAuctionSelection')
				),
				'DemandInfo.push_stop_flg' => 0,
				'AuctionInfo.refusal_flg' => 0,
				'AuctionInfo.auto_call_time <' => date('Y/m/d H:i'),
				'AuctionInfo.auto_call_flg' => $auto_called ? 1 : 0,
				'MCorp.auto_call_flag' => 1,
				'MGenre.auto_call_flag' => 1,
		);
		$joins = array(
				array(
						'table' => 'auction_infos',
						'alias' => 'AuctionInfo',
						'type' => 'inner',
						'conditions' => array('DemandInfo.id = AuctionInfo.demand_id')
				),
				array(
						'table' => 'm_corps',
						'alias' => 'MCorp',
						'type' => 'inner',
						'conditions' => array('AuctionInfo.corp_id = MCorp.id')
				),
				array(
						'table' => 'm_genres',
						'alias' => 'MGenre',
						'type' => 'inner',
						'conditions' => array('DemandInfo.genre_id = MGenre.id')
				)
		);

		$result = $this->DemandInfo->find('all', array(
				'fields' => $fields,
				'conditions' => $conditions,
				'joins' => $joins
		));

		return $result;
	}

	/**
	 * オートコール発信用のCSVを作成する
	 * @param array $data
	 */
	private function __make_csv($data){
		$outpath = null;

		// オートコール発信用CSVの作成
		$csv_data = array();
		foreach($data as $v){
			$corp_id = $v['MCorp']['id'];
			$csv_data[$corp_id] = array(
					'corp_name' => $v['MCorp']['official_corp_name'],
					'commission_dial' => $v['MCorp']['commission_dial']
			);
		}

		if(!empty($csv_data)){
			// ファイル名の決定
			$csv_dir = Hash::get($this->config, 'add_contact.csv_dir');
			$outpath = $csv_dir.'auto_call_'.date('YmdHis').'.csv';

			// csvファイルの作成
			$fp = fopen($outpath, 'w');
			fputcsv($fp, array('csv_id', 'name', 'number'));
			foreach($csv_data as $v){
				fputcsv($fp, array('', $v['corp_name'], $v['commission_dial']));
			}
			fclose($fp);
		}
		return $outpath;
	}

	/**
	 * オートコールを実施する
	 * @param string $csvfile CSVファイルパス
	 */
	private function __execute_auto_call($csvfile){

		// コンタクトの追加と開始
		$result = $this->_api_add_contacts($csvfile);

		// API実行結果を確認
		$status = Hash::get($result, 'status');
		$id = Hash::get($result, 'id');
		$name = Hash::get($result, 'name');
		$filename = Hash::get($result, 'filename');

		if($status !== '開始'){
			// コンタクトの開始に失敗(ステータスが開始となっていない)
			$error = "コンタクトの開始に失敗 result:{contact_id:{$id}, contact_name:{$name}, status:{$status}}";
			$this->log($error, AUTO_CALL_LOG);

			throw new Exception($error);
		}

		$this->log("オートコールを実行  result:{contact_id:{$id}, contact_name:{$name}, status:{$status}, filename:{$filename}}", AUTO_CALL_LOG);
	}

	/**
	 * API コンタクトの追加を行う
	 * @param string $csvfile CSVファイルパス
	 * @return int 追加したコンタクトのID
	 */
	private function _api_add_contacts($csvfile){
		// APIのURLを取得
		$url = Hash::get($this->config, 'api.add_contact');
		if($url === null){
			// URL(コンタクト追加)が設定されていない
			throw new Exception('AuctionAutoCallTask URL is empty. (api.add_contact)');
		}

		// POSTデータの設定
		$data = array();

		$campain_id = Hash::get($this->config, 'add_contact.campain_id', null);
		$status = Hash::get($this->config, 'add_contact.status', null);
		$channels = Hash::get($this->config, 'add_contact.number_of_channels', null);

		$data['name'] = '入札案内オートコール_'.date('YmdHis');
		if($campain_id !== null) $data['campain_id'] = $campain_id;
		if($status !== null) $data['status'] = $status;
		if($channels !== null) $data['number_of_channels'] = $channels;

		// CSVファイルの設定
		$files = array('attachment' => $csvfile);

		// コンタクトの追加
		$contents = $this->_get_contents('POST', $url, $data, $files);
		$resutls = json_decode($contents, true);
		return $resutls;
	}

	/**
	 * API コンタクトを開始する
	 * @param int id コンタクトID
	 */
	private function _api_start_contact($id){
		// APIのURLを取得
		$url = Hash::get($this->config, 'api.operate');
		if($url === null){
			// URL(コンタクト開始)が設定されていない
			throw new Exception('AuctionAutoCallTask URL is empty. (api.operate)');
		}
		$url = sprintf($url, $id);

		// コンタクトの開始
		$contents = $this->_get_contents('POST', $url, null, null);
		$results = json_decode($contents, true);
		return $results;
	}

	/**
	 * URLを実行してレスポンスを取得する
	 *
	 * @param string $method method
	 * @param string $url url
	 * @param array $params パラメータ
	 * @param array $files ファイル
	 */
	private function _get_contents($method, $url, $params = null, $files = null){

		// フォームデータの作成
		$formdata = $this->_make_multipart_formdata($params, $files);

		// ヘッダ情報の設定
		$header = array(
				$formdata['contentType'],
				"Content-Length: ".strlen($formdata['data'])
		);

		// Contextの設定
		$context = array(
				'http' => array(
						'method' => $method,
						'header' => $header,
						'content' => $formdata['data'],
				)
		);

		$this->log('指定したURLへ接続を開始 url:'.$url, AUTO_CALL_LOG);
		$response = file_get_contents($url, false, stream_context_create($context));

		// レスポンスの確認
		if(!empty($http_response_header)){
			$status_code = preg_split("<[[:space:]]+>", $http_response_header[0]);

			// ステータスの確認
			if($status_code[1] != 200){
				$error = 'AuctionAutoCallTask file_get_contents error url:'.$url.' status_code:'.$status_code[1];
				$this->log($error, AUTO_CALL_LOG);
				throw new Exception($error);
			}
		}else{
			// 指定したURLに接続できなかった
			$error = 'AuctionAutoCallTask file_get_contents error url:'.$url.' timeout';
			$this->log($error, AUTO_CALL_LOG);

			throw new Exception($error);
		}

		return $response;
	}

	/**
	 * フォームデータを作成する
	 * @param array $params パラメータ(name=>value)
	 */
	private function _make_urlencoded_formdata($params = null){
		$contentType = 'Content-Type: application/x-www-form-urlencoded';
		$data = '';

		if(!empty($params)){
			$data = http_build_query($params);
		}

		return array(
				'contentType' => $contentType,
				'data' => $data
		);
	}

	/**
	 * フォームデータを作成する
	 * @param array $params パラメータ(name=>value)
	 * @param array $files 添付ファイル(name=>filepath)
	 */
	private function _make_multipart_formdata($params = null, $files = null){
		$boundaryString = '---------------------------'.time();
		$contentType = "Content-Type:multipart/form-data;boundary=".$boundaryString;
		$data = '';

		// POSTパラメータの設定
		if(!empty($params)){
			foreach($params as $name => $value){
				$data .= "--".$boundaryString."\r\n";
				$data .= 'Content-Disposition: form-data; name='.$name."\r\n";
				$data .= "\r\n";
				$data .= $value."\r\n";
			}
		}

		// 送信ファイルの設定
		foreach($files as $name => $file){
			$data .= "--".$boundaryString."\r\n";
			$data .= sprintf("Content-Disposition: form-data; name=\"%s\"; filename=\"%s\"\r\n", $name, basename($file));
			$data .= 'Content-Type: application/octet-stream'."\r\n";
			$data .= "\r\n";
			$data .= file_get_contents($file)."\r\n";
		}
		$data .= "--".$boundaryString."--\r\n";

		return array(
				'contentType' => $contentType,
				'data' => $data
		);
	}
        
	// 2017.10.24 ORANGE-543 h.miyake ADD(S)
	/**
	 * 休業日オートコール除外処理
	 * @param array $auto_call_list
	 */
	private function __get_auto_call_list_Holiday($auto_call_list) {
			$date_zero = date('n/d');
			$week = Configure::read('arrWeekZList');
			$date_week = $week[date('w')];
			$kyugyobi = Util::getDropList('休業日');
			// 休業日(日付指定)
			// 休みのみオートコール除外
			$fields = array('*');
			foreach($auto_call_list as $keyList => $valueList) {
					$conditions = array(
							'corp_id' => $valueList["MCorp"]["id"]
					);
					$result = $this->MCorpNewYear->find('first', array(
							'fields' => $fields, 
							'conditions' => $conditions
					));
					$autoCallFlg = true;		 // ture:オートコール実施 false:オートコールしない
					for($i = 1; $i <= 10 ;$i++) {
						   if(!empty($result["MCorpNewYear"]["label_" . sprintf('%02d', $i)])) {
									if($result["MCorpNewYear"]["label_" . sprintf('%02d', $i)] == $date_zero 
											&& $result["MCorpNewYear"]["status_" . sprintf('%02d', $i)] == "休み") {
											$autoCallFlg = false;
									}
						   }
					}
					if($autoCallFlg) {
							$fields = array('*');
							$conditions = array(
									'corp_id' => $valueList["MCorp"]["id"]
							);
							$resultSub = $this->MCorpSub->find('all', array(
									'fields' => $fields, 
									'conditions' => $conditions
							));
							
							foreach($resultSub as $key => $value) {
									// 無休の場合はオートコール対象
									if($value["MCorpSub"]["item_id"] == '1') {
											
											$autoCallFlg = true;
											break;
									}
									else {
											$checkWeek = $kyugyobi[$value["MCorpSub"]["item_id"]];
											// 曜日にチェックがあればオートフラグ対象外
											if($checkWeek == $date_week) {
													$autoCallFlg = false;
											}
									}
							}
					}
					// オートコール対象外の場合$auto_call_listの配列を削除
					if(!$autoCallFlg) {
							 unset($auto_call_list[$keyList]);
					}		 
			}
			return $auto_call_list;
	}
	
	/**
	 * 営業時間外オートコール除外処理
	 * @param array $auto_call_list
	 */
		private function __get_auto_call_list_eigyo($auto_call_list) {
				$fields = array('MCorp.id', 
								'MCorp.corp_name', 
								'MCorp.support24hour', 
								'MCorp.available_time_other',
								'MCorp.available_time_from', 
								'MCorp.available_time_to', 
								'MCorp.contactable_support24hour',
								'MCorp.contactable_time_other',
								'MCorp.contactable_time_from', 
								'MCorp.contactable_time_to'
				 );					 

				$joins = array(
			array(
				'table' => 'affiliation_infos', 
				'alias' => 'AffiliationInfo', 
				'type' => 'left', 
				'conditions' => 'MCorp.id = AffiliationInfo.corp_id'
			), 
			array(
				'table' => 'affiliation_stats', 
				'alias' => 'AffiliationStat', 
				'type' => 'left', 
				'conditions' => 'MCorp.id = AffiliationStat.corp_id'
			),
			array(
				'table' => 'm_corp_new_years', 
				'alias' => 'MCorpNewYear', 
				'type' => 'left', 
				'conditions' => 'MCorp.id = MCorpNewYear.corp_id'
			),
			array(
				'table' => 'corp_agreement', 
				'alias' => 'CorpAgreement', 
				'type' => 'left', 
				'conditions' => 'MCorp.id = CorpAgreement.corp_id'
			)
				);
				
			foreach($auto_call_list as $key => $value) {

					$conditions = array(
									'MCorp.id' => $value["MCorp"]["id"], 
									'MCorp.del_flg' => 0
					);
							
				$result = $this->MCorp->find('first', array(
						'fields' => $fields,
							'conditions' => $conditions,
								'joins' => $joins
			));
					$nowTime = date("H:i");
					// 片方が未入力の場合：fromが未入力の場合「0:00」, toが未入力の場合「23:59」
			
					if($result["MCorp"]["available_time_from"] == '' && $result["MCorp"]["available_time_to"] != '') {
						   $result["MCorp"]["available_time_from"] = '00:00';
					}
					if($result["MCorp"]["available_time_from"] != '' && $result["MCorp"]["available_time_to"] == '' ) {
							$result["MCorp"]["available_time_to"] = "23:59";
					}
					if($result["MCorp"]["contactable_time_from"] == '' && $result["MCorp"]["contactable_time_to"] != '') {
							$result["MCorp"]["contactable_time_from"] = '00:00';
					}
					if($result["MCorp"]["contactable_time_from"] != '' && $result["MCorp"]["contactable_time_to"] == '') {
							$result["MCorp"]["contactable_time_to"] = '23:59';
					}

					$autoCallFlg = false;		  // ture:オートコール実施 false:オートコールしない
					if($result["MCorp"]["support24hour"] == '1' || 
							$result["MCorp"]["contactable_support24hour"] == '1' || 
							($result["MCorp"]["support24hour"] == '0' 
							&& $result["MCorp"]["contactable_support24hour"] == '0' 
							&& $result["MCorp"]["available_time_from"] == '' 
							&& $result["MCorp"]["available_time_to"] == '' 
							&& $result["MCorp"]["contactable_time_from"] == '' 
							&& $result["MCorp"]["contactable_time_to"] == '')) {
							// オートコール対象
							$autoCallFlg = true;
					}
					else if($result["MCorp"]["available_time_from"] != ''
							&& $result["MCorp"]["available_time_to"] != ''
							&& $result["MCorp"]["contactable_time_from"] != ''
							&& $result["MCorp"]["contactable_time_to"] != '') {
							if($result["MCorp"]["available_time_from"] <= $result["MCorp"]["contactable_time_from"]) {
									$timeFrom = $result["MCorp"]["available_time_from"];
							}
							else {
									$timeFrom = $result["MCorp"]["contactable_time_from"];
							}
							if($result["MCorp"]["available_time_to"] >= $result["MCorp"]["contactable_time_to"]) {
									$timeTo = $result["MCorp"]["available_time_to"];
							}
							else {
									$timeTo = $result["MCorp"]["contactable_time_to"];
							}
							if($timeFrom <= $nowTime && $timeTo >= $nowTime) {
									$autoCallFlg = true;
							}
							else {
									$autoCallFlg = false;
							}
					}
					else if($result["MCorp"]["available_time_from"] != '' &&  $result["MCorp"]["contactable_time_from"] == '') {
							if($result["MCorp"]["available_time_from"] <= $nowTime 
									&& $result["MCorp"]["available_time_to"] >= $nowTime) {
									$autoCallFlg = true;
							}
							else {
									$autoCallFlg = false;
							}
					}
					else if($result["MCorp"]["available_time_from"] == '' &&  $result["MCorp"]["contactable_time_from"] != '') {
							if($result["MCorp"]["contactable_time_to"] <= $nowTime
									&& $result["MCorp"]["contactable_time_to"] >= $nowTime) {
									$autoCallFlg = true;
							}
							else {
									$autoCallFlg = false;
							}
					}

					// オートコール対象外の場合$auto_call_listの配列を削除
					if(!$autoCallFlg) {
						   unset($auto_call_list[$key]);
			  
					}
			}
			return $auto_call_list;
	}
	// 2017.10.24 ORANGE-543 h.miyake ADD(S)
}