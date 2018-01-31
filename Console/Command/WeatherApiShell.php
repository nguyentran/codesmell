<?php
App::uses('ComponentCollection', 'Controller');
App::uses('CustomEmailComponent', 'Controller/Component');

class WeatherApiShell extends AppShell
{

    public $uses = array('WeatherForecast', 'DemandInfo', 'DemandActual', 'DemandForecast', 'Weather');

    /** ----- メッセージ ------ **/
    private $__msg_arg0_ng = '天気予報API 第1引数が正しくない';
    private $__msg_arg1_ng = '天気予報API 第2引数が正しくない';

    //天気予報メッセージ
    private $__msg_wf_start = '天気予報データ作成 START';
    private $__msg_wf_end = '天気予報データ作成 END';
    private $__msg_wf_ok_ftp = '天気予報データ取得 アクセス成功';
    private $__msg_wf_ng_ftp = '天気予報データ取得 アクセス失敗';
    private $__msg_wf_ng_list = '天気予報データ取得 ファイル一覧の取得に失敗 EC001';
    private $__msg_wf_ok_file = '天気予報データ取得 ファイル取得成功';
    private $__msg_wf_ng_file = '天気予報データ取得 ファイル取得失敗 EC003';
    private $__msg_wf_ok_db = '天気予報データ取得 DB更新成功';
    private $__msg_wf_ng_db = '天気予報データ取得 DB更新失敗 EC004';
    private $__msg_wf_ok_xml = '天気予報データ取得 XML解析成功';
    private $__msg_wf_ng_xml = '天気予報データ取得 XML解析失敗 EC005';
    private $__msg_wf_ok_up = '天気予報データ更新 データ更新成功';
    private $__msg_wf_ng_up = '天気予報データ更新 データ更新失敗 EC006';
    private $__msg_wf_start_up = '天気予報データ更新 START';
    private $__msg_wf_end_up = '天気予報データ更新 END';
    //過去天気メッセージ
    private $__msg_w_start = '過去天気データ取得 START';
    private $__msg_w_end = '過去天気データ取得 END';
    private $__msg_w_ok_ftp = '過去天気データ取得 アクセス成功';
    private $__msg_w_ng_ftp = '過去天気データ取得 アクセス失敗 EC007';
    private $__msg_w_ok_file = '過去天気データ取得 ファイル取得成功';
    private $__msg_w_ng_file = '過去天気データ取得 ファイル取得失敗 EC008';
    private $__msg_w_ok_db = '過去天気データ取得 DB更新成功';
    private $__msg_w_ng_db = '過去天気データ取得 DB更新失敗 EC009';
    private $__msg_w_ok_xml = '過去天気データ取得 ファイル解析成功';
    private $__msg_w_ng_xml = '過去天気データ取得 ファイル解析失敗 EC010';
    private $__msg_w_ng_gz = '過去天気データ取得 ファイル展開失敗 EC011';
    private $__msg_w_ok_up = '過去天気データ更新 データ更新成功';
    private $__msg_w_ng_up = '過去天気データ更新 データ更新失敗 EC012';
    private $__msg_w_start_up = '過去天気データ更新 START';
    private $__msg_w_end_up = '過去天気データ更新 END';
    //メインメッセージ
    private $__msg_m_start = '案件数予測シェル START';
    private $__msg_m_end = '案件数予測シェル END';
    //案件数予測メッセージ
	private $__msg_dw_start = '案件数予測データ登録 START';
	private $__msg_dw_ok = '案件数予測データ登録 DB更新成功';
	private $__msg_dw_ng = '案件数予測データ登録 DB更新失敗 EC013';
	private $__msg_dw_end = '案件数予測データ登録 END';
	private $__msg_dw_start_up = '案件数予測データ更新 START';
	private $__msg_dw_end_up = '案件数予測データ更新 END';
	private $__msg_dw_ok_up = '案件数予測データ更新 データ更新成功';
	private $__msg_dw_ng_up = '案件数予測データ更新 データ更新失敗 EC014';
	//案件数実測メッセージ
	private $__msg_da_start = '案件数実測データ登録 START';
	private $__msg_da_ok = '案件数実測データ登録 DB更新成功';
	private $__msg_da_ng = '案件数実測データ登録 DB更新失敗 EC015';
	private $__msg_da_end = '案件数実測データ登録 END';
	private $__msg_da_start_up = '案件数実測データ更新 START';
	private $__msg_da_end_up = '案件数実測データ更新 END';
	private $__msg_da_ok_up = '案件数実測データ登録 データ更新成功';
	private $__msg_da_ng_up = '案件数実測データ登録 データ更新失敗 EC016';
	//データチェックメッセージ
	private $__msg_chk_start = '予測データチェック START';
	private $__msg_chk_ok = '予測データチェック 成功';
	private $__msg_chk_ng = '予測データチェック 失敗 EC017';
	private $__msg_chk_end = '予測データチェック END';
	private $__msg_chk_ng2 = '過去天気データ NG EC018';
	private $__msg_chk_ng3 = '天気予報データ NG EC019';
	private $__msg_chk_ng4 = '案件数実測データ NG EC020';
	private $__msg_chk_ng5 = '案件数予測データ NG EC021';
	private $__msg_chk_ok2 = '過去天気データ OK';
	private $__msg_chk_ok3 = '天気予報データ OK';
	private $__msg_chk_ok4 = '案件数実測データ OK';
	private $__msg_chk_ok5 = '案件数予測データ OK';
	//ファイルの削除
	private $__msg_rm_start = '天気ファイル削除 START';
	private $__msg_rm_end = '天気ファイル削除 END';
	private $__msg_rm_ok = '天気ファイル削除 成功';
	private $__msg_rm_ng = '天気ファイル削除 失敗 EC022';
	// 予測データの修正
	private $__msg_dw_start_r = '案件数予測データ修正 START';
	private $__msg_dw_end_r = '案件数予測データ修正 END';
	private $__msg_dw_ok_r = '案件数予測データ修正 DB更新成功';
	private $__msg_dw_ng_r = '案件数予測データ修正 DB更新失敗';

	private $__msg_dt_ok = '天気予測データ トランザクション成功';
	private $__msg_dt_ng = '天気予測データ トランザクション失敗';
    private $__day_range = 2;

    private $__to_address = 'iwai@tobila.com';
    private $__from_address = 'mailback@rits-c.jp';
    private $__subject = 'WeatherApiエラー';

	/**
	 * スタートアップ
	 * {@inheritDoc}
	 * @see Shell::startup()
	 */
    public function startup() {
    	$collection = new ComponentCollection();
    	$this->CustomEmail = new CustomEmailComponent($collection); //コンポーネントをインスタンス化
    	parent::startup();
    }

    /**
     * mainメソッド
     */
    public function main(){

    	$cmd = 'I';
    	$type = 0;
    	$target_date = null;
    	$rtn = 1;

    	$this->log($this->__msg_m_start, SHELL_LOG);

    	if(isset($this->args[0])) {
			//I: INSERT
    		//U: UPDATE
    		//FC: FILE CLEAN 毎週月曜を予定
    		//D: DELETE 予備
    		$cmd = $this->args[0];
    	}else {
    		$this->log($this->__msg_arg0_ng, SHELL_LOG);
    		$rtn =0;
    	}

    	if(isset($this->args[1])) {
			//1: ALL -- NORMAL
			//2: WEATHER OLD ONLY
			//3: WEATHER FORECAST ONLY
			//4: DEMAND ACTUAL ONLY
			//5: DEMAND FORECAST ONLY
			$type = $this->args[1];
    	}
    	else if($cmd != 'FC'){
			$this->log($this->__msg_arg1_ng, SHELL_LOG);
			$rtn =0;
		}

		if(isset($this->args[2])) {
			$date = $this->args[2];
			list($Y, $m, $d) = explode('-', $date);

			if (checkdate($m, $d, $Y) === true) {
				$target_date = $date;
			}
		}

		if($cmd == 'I'){

			$this->WeatherForecast->begin();

	    	//天気過去
	    	if($rtn && ($type == 1 || $type == 2)) {
		    	$this->log($this->__msg_w_start, SHELL_LOG);
		    	$rtn = $this->__get_old_weather();
		    	$this->log($this->__msg_w_end, SHELL_LOG);
	    	}

	    	//天気予報
	    	if($rtn && ($type == 1 || $type == 3)){
		    	$this->log($this->__msg_wf_start, SHELL_LOG);
				$rtn = $this->__get_weather_forecast();
				$this->log($this->__msg_wf_end, SHELL_LOG);
	    	}

	    	//実績登録
	    	if($rtn && ($type == 1 || $type == 4)){
				$rtn = $this->__insert_demand_actual();
	    	}

	    	//予測登録
	    	if($rtn && ($type == 1 || $type == 5)){
	    		$rtn  = $this->__insert_demand_forecast();

	    		if($rtn)$rtn = $this->__revise_demand_forecast();
	    	}

	    	//チェックデータ
	    	if($rtn){
	    		$this->log($this->__msg_chk_start, SHELL_LOG);
	    		$this->__check_data($type, $target_date);
	    		$this->log($this->__msg_chk_end, SHELL_LOG);
	    	}

	    	if($rtn){
	    		//トランザクション成功
	    		$this->WeatherForecast->commit();
	    		$this->log($this->__msg_dt_ok, SHELL_LOG);
	    	}else{
	    		//トランザクション失敗
	    		$this->WeatherForecast->rollback();
	    		$this->log($this->__msg_dt_ng, SHELL_LOG);
	    	}
		}elseif($cmd == 'U'){

			//過去天気更新
			if($type == 2){
				$this->log($this->__msg_w_start_up, SHELL_LOG);
				$this->log($this->__msg_w_end_up, SHELL_LOG);
			}

			//天気予測更新
			if($type == 3){
				$this->log($this->__msg_wf_start_up, SHELL_LOG);
				$this->log($this->__msg_wf_end_up, SHELL_LOG);
			}

			//実績更新
			if($type == 4){
				$this->log($this->__msg_da_start_up, SHELL_LOG);
				$this->__update_demand_actual($target_date);
				$this->log($this->__msg_da_end_up, SHELL_LOG);
			}
			//予測更新
			if($type == 5){
				$this->log($this->__msg_wf_start_up, SHELL_LOG);
				$this->__update_demand_forecast($target_date);
				$this->log($this->__msg_wf_end_up, SHELL_LOG);
			}

		}elseif($cmd == 'FC'){
			//ファイルデータの削除
			$this->log($this->__msg_rm_start, SHELL_LOG);
			$this->__remove_file();
			$this->log($this->__msg_rm_end, SHELL_LOG);
		}

		$this->log($this->__msg_m_end, SHELL_LOG);

    	//$this->__test();
    }
/*
    private function __test(){

    		$file = new File("C:\Users\iwai.k\Desktop\VPFD\s5.xml");
    		$contents = $file->read();
    		$area_list = Configure::read('AREA_STATE');
$i = 0;
$result_list = array();
$region_list = Configure::read('STATE_REGION');
$ipn = Configure::read('INTERNATIONL_POINT_NUMBER');
    		//XMLサンプル
    		$xmlArray = Xml::toArray(Xml::build($contents));
    		//var_dump(TMP.$file_name);
    		if(!empty($xmlArray['Report']['Body']['MeteorologicalInfos'])){

    			//$this->log($file_name.' '.$this->__msg_wf_ok_xml, SHELL_LOG);
    			$infos = $xmlArray['Report']['Body']['MeteorologicalInfos'];

    			foreach($infos as $info){
    				if($info['@type'] == '区域予報'){

    					$state_flg = false;

    					if(!empty($info['TimeSeriesInfo']['Item'])){
    						foreach($info['TimeSeriesInfo']['Item'] as $key => $item){
    							//$this->log($key);$this->log($item);
    							$state_id = '';
    							if(!empty($item['Area']['Code'])){
    								if(!empty($area_list[$item['Area']['Code']])){
    									$state_id = $area_list[$item['Area']['Code']];
    								}
    							}elseif(!empty($item['Code'])){
    								//var_dump($item['Code']);
    								if(!empty($area_list[$item['Code']])){
    									$state_id = $area_list[$item['Code']];
    								}
    							}

    							if(!empty($state_id)){
    								$state_flg = true;
    								$region_id = $region_list[$state_id];

    								$idx = $i;
    								//if($file_name == 'VPFD50_JPTH_312000_02_201701311931350_001.xml')
    								//$this->log($item['Kind']);
    								if(!empty($item['Kind'][1]['Property']['WindSpeedPart']['WindSpeedLevel'])){
    									//if(!empty($info['TimeSeriesInfo']['Item']['Kind'][1]['Property']['WindSpeedPart']['WindSpeedLevel'])){

    									//foreach($item['Kind'][1]['Property']['WindSpeedPart']['/'] as $key2 => $val){
    									foreach($item['Kind'][1]['Property']['WindSpeedPart']['WindSpeedLevel'] as $key2 => $val){
    										//foreach($info['TimeSeriesInfo']['Item']['Kind'][1]['Property']['WindSpeedPart']['WindSpeedLevel'] as $key2 => $val){
    										//$this->log($val);
    										$idx = $i + $key2;
    										$result_list[$idx]['wind_speed_level'] = $val["@"];
    										$result_list[$idx]['forecast_day_range'] = $this->__day_range;
    										$result_list[$idx]['state_id'] = $state_id;
    										$result_list[$idx]['region_id'] = $region_id;
    									}
    								}elseif(!empty($info['TimeSeriesInfo']['Item']['Kind'][1]['Property']['WindSpeedPart']['WindSpeedLevel'])){

    									foreach($info['TimeSeriesInfo']['Item']['Kind'][1]['Property']['WindSpeedPart']['WindSpeedLevel'] as $key2 => $val){
    										//$this->log($val);
    										$idx = $i + $key2;
    										$result_list[$idx]['wind_speed_level'] = $val["@"];
    										$result_list[$idx]['forecast_day_range'] = $this->__day_range;
    										$result_list[$idx]['state_id'] = $state_id;
    										$result_list[$idx]['region_id'] = $region_id;
    									}
    								}

    							}

    						}
    					}

    					//都道府県が存在すれば時間を設定
    					if($state_flg){
    						if(!empty($info['TimeSeriesInfo']['TimeDefines']['TimeDefine'][0]['DateTime'])){
    							$times = $info['TimeSeriesInfo']['TimeDefines']['TimeDefine'];
    							foreach($times as $key => $val){
    								$idx = $i + $key;
    								$datetime = date('Y-m-d H:i:s', strtotime($val['DateTime']));
    								$time = date('H:i:s', strtotime($val['DateTime']));
    								$result_list[$idx]['forecast_datetime'] = $datetime;
    								$result_list[$idx]['forecast_time'] = $time;
    							}
    						}
    						//インデックスをカウントアップ
    						$i = $idx+1;
    					}

    				}
    			}
    			//$last[] = $result_list;
    			$this->log($result_list);
    		}else{
    			$this->log($this->__msg_wf_ng_xml, SHELL_LOG);
    		}

    }
*/

    /**
     * 天気ファイルの削除
     */
    private function __remove_file(){
    	try{
    		//過去データ
    		system('rm -rf '.TMP.'file'.DS.'forecast'.DS.FC_FTP_USER3.DS.'* '.TMP.'file'.DS.'forecast'.DS.FC_FTP_USER1.DS.'*' , $ret);
    		if($ret != '0'){
				$this->log($this->__msg_rm_ng, SHELL_LOG);
    		}else{
    			$this->log($this->__msg_rm_ok, SHELL_LOG);
    		}
    	}catch(Exception $e){
    		$this->log($this->__msg_rm_ng, SHELL_LOG);
    		$this->log($e->getMessage(), SHELL_LOG);
    	}
    }

    /**
     * 案件数予測データの登録
     */
    private function __insert_demand_forecast(){
    	$this->log($this->__msg_dw_start, SHELL_LOG);
    	$rtn = 1;

		try{
		    $query = $this->__sql('insert_demand_forecast.sql');
		    $this->DemandForecast->query($query);

		    $this->log($this->__msg_dw_ok, SHELL_LOG);
		}catch(Exception $e){
			$this->log($this->__msg_dw_ng, SHELL_LOG);
			$this->log($e->getMessage(), SHELL_LOG);
			$rtn = 0;
		}

    	$this->log($this->__msg_dw_end, SHELL_LOG);
    	return $rtn;
    }

    /**
     * 案件数実測データの登録
     */
    private function __insert_demand_actual(){
    	$this->log($this->__msg_da_start, SHELL_LOG);
    	$rtn = 1;

		try{
    		$query = $this->__sql('insert_demand_actuals.sql');
    		$this->DemandActual->query($query);

    		$this->log($this->__msg_da_ok, SHELL_LOG);
		}catch(Exception $e){
    		$this->log($this->__msg_da_ng, SHELL_LOG);
    		$this->log($e->getMessage(), SHELL_LOG);
    		$rtn = 0;
		}

		$this->log($this->__msg_da_end, SHELL_LOG);

		return $rtn;
    }

    /**
     * 予測データ 対象レベルが存在しない場合の修正
     */
    private function __revise_demand_forecast($target_date = null){
    	$this->log($this->__msg_dw_start_r, SHELL_LOG);
    	$date = date('Y-m-d');
    	$rtn = 1;

    	try{
    		if(!empty($target_date)){
    			$date = $target_date;
    		}

    		$query = $this->__sql('revise_demand_forecast.sql');
    		$query = str_replace('$target_date', "'".$date."'", $query);
    		$this->DemandForecast->query($query);

    		$this->log($this->__msg_dw_ok_r, SHELL_LOG);
    	}catch(Exception $e){
    		$this->log($this->__msg_dw_ng_r, SHELL_LOG);
    		$this->log($e->getMessage(), SHELL_LOG);
    		$rtn = 0;
    	}

    	$this->log($this->__msg_dw_end_r, SHELL_LOG);

    	return $rtn;
    }

    /**
     * 案件数予測データの更新
     * @param unknown $target_date
     */
    private function __update_demand_forecast($target_date = null){
    	$date = date('Y-m-d');
    	$rtn = 1;

    	try{
    		if(!empty($target_date)){
    			$date = $target_date;
    		}

	    	$query = $this->__sql('update_demand_forecast.sql');
	    	$query = str_replace('$target_date', "'".$date."'", $query);

	    	$this->DemandForecast->query($query);
	    	$this->log($this->__msg_dw_ok_up, SHELL_LOG);

    	}catch(Exception $e){

    		$this->log($this->__msg_dw_ng_up, SHELL_LOG);
    		$this->log($e->getMessage(), SHELL_LOG);
    		$rtn = 0;
    	}

    	return $rtn;
    }

    /**
     * 案件数実測データの更新
     * @param unknown $target_date
     */
    private function __update_demand_actual($target_date = null){
    	$date = date('Y-m-d');
    	$rtn = 1;

    	try{
    		if(!empty($target_date)){
    			$date = $target_date;
    		}

	    	$query = $this->__sql('update_demand_actuals.sql');
	    	$query = str_replace('$target_date', "'".$date."'", $query);

	    	$this->DemandForecast->query($query);
	    	$this->log($this->__msg_da_ok_up, SHELL_LOG);

    	}catch(Exception $e){

    		$this->log($this->__msg_da_ng_up, SHELL_LOG);
    		$this->log($e->getMessage(), SHELL_LOG);
    		$rtn = 0;
    	}

    	return $rtn;
    }

    /**
     * 過去の天気予報取得
     *  AM7:00に前日データが配布されるため２日前のデータを取得
     */
    private function __get_old_weather(){
     	App::uses('File', 'Utility');
     	App::uses('Folder', 'Utility');

     	$rtn = 1;
 		$result_list = array();
 		$area_list = Configure::read('AREA_STATE');
 		$region_list = Configure::read('STATE_REGION');
 		$ipn = Configure::read('INTERNATIONL_POINT_NUMBER');
		$i = 0;

 		$remote_file_name = 'Z__C_JMBS_'.date("Ymd",strtotime("-2 day")).'220000_STA_SURF_Rjp.tar.gz';
 		$local_file_name = TMP.'file'.DS.'forecast'.DS.FC_FTP_USER3.DS.$remote_file_name;

 		try{

	 		//ローカルにtar.gzファイルが存在しなければFTPで取得する
	 		if(!file_exists($local_file_name)){

	 			$ftpValue = array(
	 					'ftp_server' => FC_FTP_ADDRESS,
	 					'ftp_user_name' => FC_FTP_USER3,
	 					'ftp_user_pass' => FC_FTP_PASS
	 			);

	 			$connection = ftp_connect($ftpValue['ftp_server']);

	 			if(ftp_login(
	 					$connection,
	 					$ftpValue['ftp_user_name'],
	 					$ftpValue['ftp_user_pass']
	 			)){
	 				$this->log($this->__msg_w_ok_ftp, SHELL_LOG);
	 			}else{
	 				$this->log($this->__msg_w_ng_ftp, SHELL_LOG);
	 				return;
	 			}

	 			ftp_pasv($connection, true);

	 			//リモートでのファイル存在チェック
	 			if(ftp_size($connection, $remote_file_name)){
	 				//ftp_pasv($connection, true);
	 				$ftpResult = ftp_get($connection, $local_file_name, $remote_file_name, FTP_BINARY, false);

	 				if ($ftpResult) {
	 					$this->log($remote_file_name.' '.$this->__msg_w_ok_file, SHELL_LOG);
	 				}else{
	 					$this->log($remote_file_name.' '.$this->__msg_w_ng_file, SHELL_LOG);
	 				}
	 			}else{
	 				$this->log($remote_file_name.' '.$this->__msg_w_ng_file, SHELL_LOG);
	 			}

	 		}

			//展開ファイルが無ければ、tar.gzファイルを解凍
	 		if(!file_exists(TMP.'file'.DS.'forecast'.DS.FC_FTP_USER3.'/surface/daily/'.date("Y",strtotime("-2 day")).DS.date("m",strtotime("-2 day")))){
		 		//ファイルの解凍 解凍先とフォルダを指定
		 		system( "tar xvfz ".$local_file_name.' -C '. TMP.'file'.DS.'forecast'.DS.FC_FTP_USER3. ' ./surface/daily/ ', $ret);

		 		//解凍に失敗
		 		if($ret != "0"){
		 			//エラー
		 			$this->log($remote_file_name.' '.$this->__msg_w_ng_gz, SHELL_LOG);
		 			return;
		 		}
	 		}

	 		//対象日のデータが既にあるか確認
	 		$conditions = array('Weather.weather_datetime' => "'".date('Y-m-d', strtotime('-2 day'))."'");
	 		$weather_count = $this->Weather->find('count', array('conditions' => $conditions));

	 		//無ければファイルをループして値を取得
	 		if($weather_count == 0){

		 		$idx = 0;

		 		//国際地点番号一覧から対象のファイルを探して処理
		 		foreach($ipn as $key => $val){
		 			//echo $i;
		 			//都道府県番号
		 			$state_id = $key;

		 			//ファイルが存在しない場合スキップ
		 			if(!file_exists(TMP.'file'.DS.'forecast'.DS.FC_FTP_USER3.'/surface/daily/'.date("Y",strtotime("-2 day")).DS.date("m",strtotime("-2 day")).DS.'sfc_d_'.date("Ym",strtotime("-2 day")).'.'.$val )){
		 				continue;
		 			}
					$file = new File(TMP.'file'.DS.'forecast'.DS.FC_FTP_USER3.'/surface/daily/'.date("Y",strtotime("-2 day")).DS.date("m",strtotime("-2 day")).DS.'sfc_d_'.date("Ym",strtotime("-2 day")).'.'.$val);

					//16進数に変換すると1バイト2文字になるため、1454バイトの2倍の文字数を指定する
					$data =wordwrap(bin2hex($file->read()), 2908, ',', true);
					//配列化
					$ar_data = split(',', $data);

					//$day = date("d",strtotime("-1 day"));
					$yd = date("Ynj",strtotime("-2 day"));
					if($ar_data){
						foreach($ar_data as $key => $val){
							$y = mb_strcut($val, 16, 4);
							$m = mb_strcut($val, 20, 4);
							$j = mb_strcut($val, 24, 4);

							$y = hexdec(mb_strcut($y, 2, 2).mb_strcut($y, 0, 2));
							$m = hexdec(mb_strcut($m, 2, 2).mb_strcut($m, 0, 2));
							$j = hexdec(mb_strcut($j, 2, 2).mb_strcut($j, 0, 2));


							//前日が対象
							if($yd == $y.$m.$j){
								//16進数に変換すると1バイト2文字になるため、対象バイトの2倍の文字数を指定する
								$d = mb_strcut($val, 484, 8);
								//リトルエンディアンのため、1バイト毎に逆さにしてから10進数へ変換後、「.」をつける
								$wa = substr_replace(sprintf('%02d',hexdec(mb_strcut($d, 6, 2).mb_strcut($d, 4, 2).mb_strcut($d, 2, 2).mb_strcut($d, 0, 2))), '.', 1, 0);

								$result_list[$idx]['weather_datetime'] = date("Y-m-d",strtotime("-2 day"));
								$result_list[$idx]['referer'] = FC_REFERER;
								$result_list[$idx]['state_id'] = $state_id;
								$result_list[$idx]['wind_speed_avg'] = $wa;

								$idx++;
								//echo $y.'-'.$m.'-'.$j."\n";
							}
						}
					}
		 		}

		 		if($result_list){
		 			//DB登録テスト
		 			if($this->Weather->saveall($result_list)){
		 				$this->log($this->__msg_w_ok_db, SHELL_LOG);
		 			}else{
		 				$this->log($this->__msg_w_ng_db, SHELL_LOG);
		 				$rtn = 0;
		 			}
		 		}
	 		}
 		}catch(Exception $e){
 			$rtn = 0;
 			$this->log($e->getMessage(), SHELL_LOG);
 		}

		return $rtn;
 		//var_dump($result_list);
    }

    /**
     * 天気予報取得
     */
 	private function __get_weather_forecast(){

 		App::uses('File', 'Utility');

 		$rtn = 1;
 		$result_list = array();
 		$area_list = Configure::read('AREA_STATE');
 		$region_list = Configure::read('STATE_REGION');
		$i = 0;

		 $ftpValue = array(
 				'ftp_server' => FC_FTP_ADDRESS,
 				'ftp_user_name' => FC_FTP_USER1,
 				'ftp_user_pass' => FC_FTP_PASS
 		);

		try{
	 		$connection = ftp_connect($ftpValue['ftp_server']);

	 		if(ftp_login(
	 				$connection,
	 				$ftpValue['ftp_user_name'],
	 				$ftpValue['ftp_user_pass']
	 				)){
	 					$this->log($this->__msg_wf_ok_ftp, SHELL_LOG);
	 		}else{
	 			$this->log($this->__msg_wf_ng_ftp, SHELL_LOG);
	 			return;
	 		}

			ftp_pasv($connection, true);

			$flist = ftp_rawlist($connection, '-t .');
			$result = array();

			//$target_name = '_'.date("d",strtotime("-2 day")).'0800_';
			$target_name = '_'.date("d",strtotime("-1 day")).'2000_';

			foreach($flist as $dl){
				$file_info = preg_split("/[\s]+/", $dl, 9);
				if(strpos($file_info[8],$target_name) !== false
						&& (
								//2017.01.19 長屋さまに仕様確認済み
								//修正ファイルは除外
								strpos($file_info[8],'_CCA_') === false
	//							strpos($file_info[8],'JPWA') === false
	//							&& strpos($file_info[8],'JPWB') === false
	//							&& strpos($file_info[8],'JPSA') === false
	//							&& strpos($file_info[8],'JPOS') === false
	//							&& strpos($file_info[8],'JPMT') === false
						)
					)
							$result[] = $file_info[8];
			}

			if($result){
				//取得したファイル名をダウンロード
				foreach($result as $file_name){
			 		//リモートでのファイル存在チェック
			 		if(ftp_size($connection, $file_name)){
			 			//ftp_pasv($connection, true);
			 			$ftpResult = ftp_get($connection, TMP.'file'.DS.'forecast'.DS.FC_FTP_USER1.DS.$file_name, $file_name, FTP_BINARY, false);

			 			if ($ftpResult) {
			 				$this->log($file_name.' '.$this->__msg_wf_ok_file, SHELL_LOG);
			 			}else{
			 				$this->log($file_name.' '.$this->__msg_wf_ng_file, SHELL_LOG);
			 			}
			 		}else{
			 			$this->log($file_name.' '.$this->__msg_wf_ng_file, SHELL_LOG);
			 		}
			 	}

		 		ftp_close($connection);

		 		foreach($result as $file_name){
		 			//$i = 0;
		 			//ファイルが存在しない場合スキップ
		 			if(!file_exists(TMP.'file'.DS.'forecast'.DS.FC_FTP_USER1.DS.$file_name)){
		 				continue;
		 			}

			 		$file = new File(TMP.'file'.DS.'forecast'.DS.FC_FTP_USER1.DS.$file_name);
			 		$contents = $file->read();

			 		//XMLサンプル
			 		$xmlArray = Xml::toArray(Xml::build($contents));
	//var_dump(TMP.$file_name);
			 		if(!empty($xmlArray['Report']['Body']['MeteorologicalInfos'])){

			 			$this->log($file_name.' '.$this->__msg_wf_ok_xml, SHELL_LOG);
			 			$infos = $xmlArray['Report']['Body']['MeteorologicalInfos'];

				 		foreach($infos as $info){
				 			if($info['@type'] == '区域予報'){

								$state_flg = false;

				 				if(!empty($info['TimeSeriesInfo']['Item'])){
				 					foreach($info['TimeSeriesInfo']['Item'] as $key => $item){

				 						$state_id = '';
				 						if(!empty($item['Area']['Code'])){
				 							if(!empty($area_list[$item['Area']['Code']])){
				 								$state_id = $area_list[$item['Area']['Code']];
				 							}
				 						}elseif(!empty($item['Code'])){
				 							//var_dump($item['Code']);
				 							if(!empty($area_list[$item['Code']])){
				 								$state_id = $area_list[$item['Code']];
				 							}
				 						}

			 							if(!empty($state_id)){
			 								$state_flg = true;
			 								$region_id = $region_list[$state_id];

			 								$idx = $i;
			 								//if($file_name == 'VPFD50_JPTH_312000_02_201701311931350_001.xml')var_dump($item['Kind']);
			 								if(!empty($item['Kind'][1]['Property']['WindSpeedPart']['WindSpeedLevel'])){
			 								//if(!empty($info['TimeSeriesInfo']['Item']['Kind'][1]['Property']['WindSpeedPart']['WindSpeedLevel'])){

				 								//foreach($item['Kind'][1]['Property']['WindSpeedPart']['/'] as $key2 => $val){
			 									foreach($item['Kind'][1]['Property']['WindSpeedPart']['WindSpeedLevel'] as $key2 => $val){
			 									//foreach($info['TimeSeriesInfo']['Item']['Kind'][1]['Property']['WindSpeedPart']['WindSpeedLevel'] as $key2 => $val){
			 										//$this->log($val);
			 										$idx = $i + $key2;
				 									$result_list[$idx]['wind_speed_level'] = $val["@"];
				 									$result_list[$idx]['forecast_day_range'] = $this->__day_range;
				 									$result_list[$idx]['state_id'] = $state_id;
				 									$result_list[$idx]['region_id'] = $region_id;
				 								}
			 								}elseif(!empty($info['TimeSeriesInfo']['Item']['Kind'][1]['Property']['WindSpeedPart']['WindSpeedLevel'])){

			 									foreach($info['TimeSeriesInfo']['Item']['Kind'][1]['Property']['WindSpeedPart']['WindSpeedLevel'] as $key2 => $val){
			 										//$this->log($val);
			 										$idx = $i + $key2;
				 									$result_list[$idx]['wind_speed_level'] = $val["@"];
				 									$result_list[$idx]['forecast_day_range'] = $this->__day_range;
				 									$result_list[$idx]['state_id'] = $state_id;
				 									$result_list[$idx]['region_id'] = $region_id;
				 								}
			 								}

			 							}

				 					}
				 				}

				 				//都道府県が存在すれば時間を設定
				 				if($state_flg){
					 				if(!empty($info['TimeSeriesInfo']['TimeDefines']['TimeDefine'][0]['DateTime'])){
					 					$times = $info['TimeSeriesInfo']['TimeDefines']['TimeDefine'];
					 					foreach($times as $key => $val){
					 						$idx = $i + $key;
					 						$datetime = date('Y-m-d H:i:s', strtotime($val['DateTime']));
					 						$time = date('H:i:s', strtotime($val['DateTime']));
					 						$result_list[$idx]['forecast_datetime'] = $datetime;
					 						$result_list[$idx]['forecast_time'] = $time;
					 					}
					 				}
					 				//インデックスをカウントアップ
					 				$i = $idx+1;
				 				}

				 			}
				 		}
				 		//$last[] = $result_list;
			 		}else{
			 			$this->log($this->__msg_wf_ng_xml, SHELL_LOG);
			 		}
		 		}
			}else{
				$this->log($this->__msg_wf_ng_list, SHELL_LOG);
			}
			//$this->log($last);
			//$this->log($result_list, SHELL_LOG);
			//return;
			if($result_list){
				//DB登録テスト
				if($this->WeatherForecast->saveall($result_list)){
					$this->log($this->__msg_wf_ok_db, SHELL_LOG);
				}else{
					$this->log($this->__msg_wf_ng_db, SHELL_LOG);
					$rtn = 0;
				}
			}
		}catch(Exception $e){
			$rtn = 0;
			$this->log($e->getMessage(), SHELL_LOG);
		}
		return $rtn;

 	}

 	/**
 	 * SQLファイルを読み込む
 	 *
 	 * @param string $fileName SQLファイル名
 	 * @return string SQL
 	 */
 	private function __sql($fileName) {
 		$query = "";
 		// ファイルからSQLを読み込む
 		ob_start();
 		include $fileName;
 		$query = ob_get_clean();
 		return $query;
 	}

 	/**
 	 * データ チェック
 	 */
 	private function __check_data($type = 1, $target_date = null) {
 		try{
	 		if($type == 1 || $type == 2){

	 			$date = date('Y-m-d', strtotime('-2 day'));
	 			if(!empty($target_date)){
	 				$date = $target_date;
	 			}

	 			$conditions = array('Weather.weather_datetime' => "'".$date."'" );
	 			$weather = $this->Weather->find('all', array('conditions' => $conditions));

	 			$this->log(count($weather), SHELL_LOG);

	 			if(count($weather) <> 47){
	 				//エラー処理
	 				$this->log($this->__msg_chk_ng2, SHELL_LOG);

	 			}else{
	 				$this->log($this->__msg_chk_ok2, SHELL_LOG);
	 			}
	 		}

	 		if($type == 1 || $type == 3){

	 			$date1 = date('Y-m-d');
	 			$date2 = date('Y-m-d', strtotime('+2 day'));
	 			if(!empty($target_date)){
	 				$date1 = $target_date;
	 				$date2 = date('Ymd', $target_date.' +2 day');
	 			}

	 			$conditions = array("WeatherForecast.forecast_datetime between '".$date1."' AND  '".$date2."'");
	 			$weather_forecast = $this->WeatherForecast->find('all', array('conditions' => $conditions));

	 			$this->log(count($weather_forecast), SHELL_LOG);

	 			if(count($weather_forecast) < 94){
	 				//エラー処理
					$this->log($this->__msg_chk_ng3, SHELL_LOG);
	 			}else{
	 				$this->log($this->__msg_chk_ok3, SHELL_LOG);
	 			}
	 		}

	 		if($type == 1 || $type == 4){
				$date = date('Y-m-d', strtotime('-2 day'));

	 			if(!empty($target_date)){
	 				$date = $target_date;
	 			}

	 			$conditions = array('DemandActual.actual_datetime' => "'".$date."'");
	 			$demand_actual = $this->DemandActual->find('all', array('conditions' => $conditions));

	 			$this->log(count($demand_actual), SHELL_LOG);

	 			if(count($demand_actual) < 47){
					//エラー処理
					$this->log($this->__msg_chk_ng4, SHELL_LOG);
	 			}else{
	 				$this->log($this->__msg_chk_ok4, SHELL_LOG);
	 			}
	 		}

	 		if($type == 1 || $type == 5){
	 			$date = date('Y-m-d');

	 			if(!empty($target_date)){
	 				$date = $target_date;
	 			}

	 			$conditions = array('DemandForecast.display_date' => "'".$date."'");
	 			$demand_forecast = $this->DemandForecast->find('all', array('conditions' => $conditions));

	 			$this->log(count($demand_forecast), SHELL_LOG);

	 			//予測件数が過去の風速データに伴うため、可変する。
	 			//エラー対象は0件のみとする
	 			if(count($demand_forecast) == 0){
	 				//エラー処理
	 				$this->log($this->__msg_chk_ng5, SHELL_LOG);
	 			}else{
	 				$this->log($this->__msg_chk_ok5, SHELL_LOG);
	 			}
	 		}

 		}catch(Exception $e){
 			$this->log($this->__msg_chk_ng, SHELL_LOG);
 			$this->log($e->getMessage(), SHELL_LOG);
 		}
 	}

 	/**
 	 * ログクラス オーバーライド
 	 * @param unknown $msg
 	 * @param unknown $type
 	 * @param unknown $scope
 	 */
 	public function log($msg = null, $type = null){

 		if(strpos($msg,'EC') !== false ){
 			$data['msg'] = $msg;
 			$this->CustomEmail->send($this->__subject, 'weather_api', $data, $this->__to_address, null, $this->__from_address);
 		}
 		parent::log($msg,$type);
 	}

}