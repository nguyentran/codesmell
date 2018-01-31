<?php
App::uses('AppShell', 'Console/Command');

/**
 *
 * args[0] = 取次データのロック [lock]
 * args[1] = ファイル名
 * @author iwai.k
 *
 */
class ProgImportShell extends AppShell {

	public $uses = array('CommissionInfo', 'ProgImportFile', 'ProgCorp', 'ProgDemandInfo');
	private static $user = 'system';

	/**
	 * メイン処理
	 */
	public function main() {

		try{

			$this->log('進捗表管理インポートシェルstart', PROG_IMPORT_LOG);

			$this->CommissionInfo->begin();
			$this->ProgImportFile->begin();
			$this->ProgCorp->begin();
			$this->ProgDemandInfo->begin();

			$this->log('進捗表管理インポートファイル作成start', PROG_IMPORT_LOG);
			$import_file = $this->insert_prog_import_files();
			$this->log('進捗表管理インポートファイル作成end', PROG_IMPORT_LOG);


			$this->log('案件情報の取得start', PROG_IMPORT_LOG);
			$commission_infos = $this->find_commission_infos();
			$this->log('案件情報の取得end', PROG_IMPORT_LOG);

			$this->log('進捗管理案件情報の作成start', PROG_IMPORT_LOG);
			foreach($commission_infos as $commission_info){
				// 進捗表管理企業の作成
				$progress_corp = $this->insert_prog_corp($commission_info[0], $import_file['ProgImportFile']['id']);

				// 進捗管理案件情報の登録
				// ORANGE_218 CHG S
				$this->insert_prog_demand_info($commission_info[0], $import_file['ProgImportFile']['id'], $progress_corp['ProgCorp']['id'], $import_file['ProgImportFile']['lock_flag']);
				// ORANGE_218 CHG E
			}

			$this->CommissionInfo->commit();
			$this->ProgImportFile->commit();
			$this->ProgCorp->commit();
			$this->ProgDemandInfo->commit();

			$this->log('進捗管理案件情報の作成end', PROG_IMPORT_LOG);


			$this->log('進捗表管理インポートシェルend', PROG_IMPORT_LOG);

		}catch(Exception $e){
			$this->log($e, PROG_IMPORT_LOG);

			$this->CommissionInfo->rollback();
			$this->ProgImportFile->rollback();
			$this->ProgCorp->rollback();
			$this->ProgDemandInfo->rollback();
		}
	}

	/**
	 * 進捗表管理インポートファイルを作成
	 */
	private function insert_prog_import_files(){
		if(!empty($this->args[1])){
			$title = $this->args[1];
		}else{
			$title = "進捗表".date('Y')."年".date('m')."月分";
		}

		//ORANGE-218 ADD S
		$lock_flag = 0;
		$release_flag = 0;

		if(!empty($this->args[0])){
			if($this->args[0] === 'lock'){
				$lock_flag = 1;
				$release_flag = 1;
			}
		}
		//ORANGE-218 ADD E

		$save_data = array(
				'file_name' => $title,
				'original_file_name' => $title,
				'import_date' => date("Y-m-d H:i:s"),
				'delete_flag' => 0,
				//ORANGE-218 ADD S
				'lock_flag' => $lock_flag,
				'release_flag' => $release_flag,
				//ORANGE-218 ADD E
				'created_user_id' => 'system',
				'modified_user_id' => self::$user
		);

		// AppModelのBeforeSaveでcreated_user_id, modified_user_idを空文字で上書きしてしまうため、
		// 一時的にコールバックを無効にする
		return $this->ProgImportFile->save($save_data, array(
				'validate' => false,
				'callbacks' => false
		));
	}

	/**
	 * 案件情報の取得
	 */
	private function find_commission_infos(){

// 2017.01.04 murata.s ORANGE-244 CHG(S)
		//ORANGE-218 CHG S
		$sql = "
			SELECT
				commission_infos.id as commission_infos_id,
				demand_infos.id as demand_infos_id,
				commission_infos.commission_status as commission_status,
				demand_infos.receive_datetime as receive_datetime,
				demand_infos.customer_name as customer_name,
				m_corps.id as corp_id,
				m_corps.corp_name as corp_name,
				m_corps.official_corp_name as official_corp_name,
				affiliation_infos.construction_unit_price,
				demand_infos.customer_tel,
				demand_infos.customer_mailaddress,
				m_categories.category_name,
				commission_infos.commission_status,
				commission_infos.commission_order_fail_reason,
				commission_infos.complete_date,
				commission_infos.order_fail_date,
				commission_infos.construction_price_tax_exclude,
				commission_infos.construction_price_tax_include,
				m_corps.fax,
				m_corps.prog_send_method,

				m_corps.prog_send_mail_address,
				m_corps.prog_send_fax,

				m_corps.prog_irregular,
				m_corps.commission_dial,
				commission_infos.report_note,
				m_corps.mailaddress_pc,
				m_corps.bill_send_method,
				bill_infos.fee_billing_date,
				commission_infos.order_fee_unit,
				commission_infos.irregular_fee,
				commission_infos.irregular_fee_rate,
				commission_infos.commission_fee_rate,
				commission_infos.corp_fee,
				bill_infos.fee_target_price,
				m_genres.genre_name,
				demand_infos.tel1
			FROM
				commission_infos
			inner JOIN
				m_corps ON (commission_infos.corp_id = m_corps.id AND m_corps.del_flg = 0)
			inner JOIN
				demand_infos ON (demand_infos.id = commission_infos.demand_id AND demand_infos.del_flg != 1)
			left JOIN
				m_categories ON (m_categories.id = demand_infos.category_id)
			left JOIN
				m_items ON (m_items.item_category = '取次状況' AND m_items.item_id = commission_infos.commission_status)
			LEFT JOIN
				affiliation_infos ON affiliation_infos.corp_id = m_corps.id
			LEFT JOIN
				bill_infos ON bill_infos.demand_id = demand_infos.id AND bill_infos.commission_id = commission_infos.id AND bill_infos.auction_id is null
			LEFT JOIN
				m_corp_categories ON m_corp_categories.corp_id = commission_infos.corp_id AND m_corp_categories.category_id=demand_infos.category_id
			LEFT JOIN
				m_genres ON m_genres.id = demand_infos.genre_id
			WHERE
				(demand_infos.demand_status=4 OR demand_infos.demand_status=5)
				AND
					commission_infos.commission_type=0
				AND
					commission_infos.lost_flg=0
				AND
					demand_infos.del_flg=0
				AND
					commission_infos.del_flg=0
				AND
					commission_infos.commission_status IN (3,4)
				AND
					(commission_infos.progress_reported != 1  OR commission_infos.progress_reported IS NULL)
				".PM_NOT_CORP."
				AND
					(commission_note_send_datetime < '".date('Y-m-d')."' OR commission_note_send_datetime IS NULL)
		  UNION SELECT
				commission_infos.id as commission_infos_id,
				demand_infos.id as demand_infos_id,
				commission_infos.commission_status as commission_status,
				demand_infos.receive_datetime as receive_datetime,
				demand_infos.customer_name as customer_name,
				m_corps.id as corp_id,
				m_corps.corp_name as corp_name,
				m_corps.official_corp_name as official_corp_name,
				affiliation_infos.construction_unit_price,
				demand_infos.customer_tel,
				demand_infos.customer_mailaddress,
				m_categories.category_name,
				commission_infos.commission_status,
				commission_infos.commission_order_fail_reason,
				commission_infos.complete_date,
				commission_infos.order_fail_date,
				commission_infos.construction_price_tax_exclude,
				commission_infos.construction_price_tax_include,
				m_corps.fax,
				m_corps.prog_send_method,

				m_corps.prog_send_mail_address,
				m_corps.prog_send_fax,

				m_corps.prog_irregular,
				m_corps.commission_dial,
				commission_infos.report_note,
				m_corps.mailaddress_pc,
				m_corps.bill_send_method,
				bill_infos.fee_billing_date,
				commission_infos.order_fee_unit,
				commission_infos.irregular_fee,
				commission_infos.irregular_fee_rate,
				commission_infos.commission_fee_rate,
				commission_infos.corp_fee,
				bill_infos.fee_target_price,
				m_genres.genre_name,
				demand_infos.tel1
			FROM
				commission_infos
			inner JOIN
				m_corps ON (commission_infos.corp_id = m_corps.id AND m_corps.del_flg = 0)
			inner JOIN
				demand_infos ON (demand_infos.id = commission_infos.demand_id AND demand_infos.del_flg != 1)
			left JOIN
				m_categories ON (m_categories.id = demand_infos.category_id)
			left JOIN
				m_items ON (m_items.item_category = '取次状況' AND m_items.item_id = commission_infos.commission_status)
			LEFT JOIN
				affiliation_infos ON affiliation_infos.corp_id = m_corps.id
			LEFT JOIN
				bill_infos ON bill_infos.demand_id = demand_infos.id AND bill_infos.commission_id = commission_infos.id AND bill_infos.auction_id is null
			LEFT JOIN
				m_corp_categories ON m_corp_categories.corp_id = commission_infos.corp_id AND m_corp_categories.category_id=demand_infos.category_id
			LEFT JOIN
				m_genres ON m_genres.id = demand_infos.genre_id
			WHERE
				(demand_infos.demand_status=4 OR demand_infos.demand_status=5)
				AND
					commission_infos.commission_type=0
				AND
					commission_infos.lost_flg=0
				AND
					demand_infos.del_flg=0
				AND
					commission_infos.del_flg=0
				AND
					commission_infos.commission_status IN (1,2)
				".PM_NOT_CORP."
				AND
					(commission_note_send_datetime < '".date('Y-m-d')."' OR commission_note_send_datetime IS NULL)
		  ";
		//ORANGE-218 CHG E
// 2017.01.04 murata.s ORANGE-244 CHG(E)
		return $this->CommissionInfo->query($sql);
	}

	/**
	 * 進捗管理企業を登録する
	 * @param unknown $rows 登録データ
	 * @param unknown $import_file_id 進捗管理インポートファイルID
	 */
	private function insert_prog_corp($rows, $import_file_id){

		// 同一進捗管理インポートファイルIDのデータを検索
		$partner_data = $this->ProgCorp->find('first', array(
				'conditions' => array(
						'corp_id' => $rows['corp_id'],
						'prog_import_file_id' => $import_file_id
				)
		));
		if(empty($partner_data)){
			$this->ProgCorp->create();

			$insert = array();
			$insert['corp_id'] = $rows['corp_id'];
			$insert['progress_flag'] = "1";
			$insert['mail_last_send_date'] = NULL; // '0000-00-00 00:00:00'
			$insert['collect_date'] = NULL;
			$insert['sf_register_date'] = NULL;
			//ORANGE-240 CHG S
			$insert['call_back_phone_flag'] = '1';
			//ORANGE-240 CHG E
			$insert['note'] = '';
			$insert['unit_cost'] = $rows['construction_unit_price'];
			$insert['mail_count'] = 0;
			//$insert['modified'] = date("Y-m-d H:i:s");
			//$insert['created'] = date("Y-m-d H:i:s");
			$insert['call_back_phone_date'] = NULL; // '0000-00-00 00:00:00'
			$insert['fax_count'] = 0;
			$insert['fax_last_send_date'] = '';
			$insert['prog_import_file_id'] = $import_file_id;

			//ORANGE-218 CHG S
			$insert['contact_type'] = $rows['prog_send_method'];
			$insert['fax'] = $rows['prog_send_fax'];
			$insert['mail_address'] = $rows['prog_send_mail_address'];
			/*
			if($rows['prog_send_method'] == 1 || $rows['prog_send_method'] == 5){
				$insert['mail_address'] = $rows['prog_send_address'];
			}elseif($rows['prog_send_method'] == 2){
				$insert['fax'] = $rows['prog_send_address'];
			}

			if($rows['prog_send_method'] == 1 || $rows['prog_send_method'] == 2 || $rows['prog_send_method'] == 3){
				$insert['contact_type'] = $rows['prog_send_method'];
			}elseif($rows['prog_send_method'] == 4){
				$insert['contact_type'] = 4;
			}elseif($rows['prog_send_method'] == 5){
				$insert['contact_type'] = 1;
			}elseif($rows['prog_send_method'] == 6){
				$insert['contact_type'] = 4;
			}
			*/
			//ORANGE-218 CHG E

			$insert['irregular_method'] = $rows['prog_irregular'];
			$insert['created_user_id'] = self::$user;
			$insert['modified_user_id'] = self::$user;

			// AppModelのBeforeSaveでcreated_user_id, modified_user_idを空文字で上書きしてしまうため、
			// 一時的にコールバックを無効にする
			return $this->ProgCorp->save($insert, array(
					'validate' => false,
					'callbacks' => false
			));
		}else{
			return $partner_data;
		}
	}

	/**
	 * 進捗管理案件情報を登録する
	 * @param unknown $rows 登録データ
	 * @param unknown $import_file_id 進捗管理インポートファイルID
	 * @param unknown $import_corp_id 進捗管理企業ID
	 * @param unknown $lock_flag ロックフラグ ORANGE-218 ADD
	 */
	private function insert_prog_demand_info($rows, $import_file_id, $import_corp_id, $lock_flag = 0){
		$csv_data = $this->ProgDemandInfo->find('first', array(
				'conditions' => array(
						'corp_id' => $rows['corp_id'],
						'commission_id' => $rows['commission_infos_id'],
						'prog_import_file_id' => $import_file_id
				)
		));

		if(empty($csv_data)){
			$this->ProgDemandInfo->create();

			$insert = array();
			$insert['corp_id'] = $rows['corp_id'];
			$insert['demand_id'] = $rows['demand_infos_id'];
			$insert['genre_name'] = $rows['genre_name'];
			$insert['category_name'] = $rows['category_name'];
			$insert['customer_name'] = $rows['customer_name'];
			$insert['commission_status_update'] = '0';
			$insert['diff_flg'] = 0;
			$insert['complete_date_update'] = "";
			$insert['construction_price_tax_exclude_update'] = "";
			$insert['construction_price_tax_include_update'] = "";
			$insert['comment_update'] = "";
			$insert['prog_import_file_id'] = $import_file_id;
			$insert['prog_corp_id'] = $import_corp_id;
			//$insert['modified'] = date("Y-m-d H:i:s");
			//$insert['created'] = date("Y-m-d H:i:s");
			//if($rows['commission_status'] == 1){
			//	$insert['commission_status'] = "進行中";
			//}else if($rows['commission_status'] == 2){
			//	$insert['commission_status'] = "進行中";
			//}else if($rows['commission_status'] == 3){
			//	$insert['commission_status'] = "施工完了";
			//}else if($rows['commission_status'] == 4){
			//	$insert['commission_status'] = "失注";
			//}
			$insert['commission_status'] = $rows['commission_status'];
			$insert['commission_order_fail_reason'] = $rows['commission_order_fail_reason'];
			if($rows['commission_status'] == 3){
				$insert['complete_date'] = $rows['complete_date'];
			}else if($rows['commission_status'] == 4){
				$insert['complete_date'] = $rows['order_fail_date'];
			}
			$insert['construction_price_tax_exclude'] = $rows['construction_price_tax_exclude'];
			$insert['construction_price_tax_include'] = $rows['construction_price_tax_include'];
			$insert['commission_id'] = $rows['commission_infos_id'];
			$insert['agree_flag'] = "0";
			$insert['receive_datetime'] = $rows['receive_datetime'];

			// 取次手数料単価が円の場合
			if($rows['order_fee_unit'] == "0"){
				if($rows['irregular_fee'] != "" && $rows['irregular_fee'] != 0){
					// イレギュラー手数料金額（税抜）【単位：円】
					$insert['fee'] = $rows['irregular_fee'];
				}else{
					// 取次先手数料【単位：円】
					$insert['fee'] = $rows['corp_fee'];
				}
				// 取次手数料単価が％の場合
			}else if($rows['order_fee_unit'] == "1"){
				if($rows['irregular_fee'] != "" && $rows['irregular_fee'] != 0){
					// イレギュラー手数料金額（税抜）【単位：円】
					$insert['fee'] = $rows['irregular_fee'];
				}else if($rows['irregular_fee_rate'] != "" && $rows['irregular_fee_rate'] != 0){
					// イレギュラー手数料率【単位：％】
					$insert['fee_rate'] = $rows['irregular_fee_rate'];
				}else{
					// 取次時手数料率【単位：％】
					$insert['fee_rate'] = $rows['commission_fee_rate'];
				}
			}else if($rows['order_fee_unit'] == NULL){
				// カテゴリIDが紐づいてない場合
				if($rows['irregular_fee'] != "" && $rows['irregular_fee'] != 0){
					// イレギュラー手数料金額（税抜）【単位：円】
					$insert['fee'] = $rows['irregular_fee'];
				}else if($rows['irregular_fee_rate'] != "" && $rows['irregular_fee_rate'] != 0){
					$insert['fee_rate'] = $rows['irregular_fee_rate'];
				}else if($rows['commission_fee_rate'] != "" && $rows['commission_fee_rate'] != 0){
					$insert['fee_rate'] = $rows['commission_fee_rate'];
				}else if($rows['corp_fee'] != "" && $rows['corp_fee'] != 0){
					$insert['fee'] = $rows['corp_fee'];
				}
			}

			// 手数料対象金額
			$insert['fee_target_price'] = $rows['fee_target_price'];
			$insert['fee_billing_date'] = $rows['fee_billing_date'];
			$insert['created_user_id'] = self::$user;
			$insert['modified_user_id'] = self::$user;

			// AppModelのBeforeSaveでcreated_user_id, modified_user_idを空文字で上書きしてしまうため、
			// 一時的にコールバックを無効にする
			$this->ProgDemandInfo->save($insert, array(
					'validate' => false,
					'callbacks' => false
			));

			//ORANGE-218 CHG S
			if(!empty($lock_flag)){
					$this->CommissionInfo->query("SELECT set_lock_status(".$rows['commission_infos_id'].", 1);");
			}
			//ORANGE-218 CHG E
		}
	}


}