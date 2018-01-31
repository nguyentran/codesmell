<?php
App::uses('AppController', 'Controller');

class ProgressManagementController extends AppController {
	public $name = 'ProgressManagement';
	public $helpers = array('ProgressManagement', 'Csv');
	public $components = array('Paginator', 'Mpdf', 'CustomEmail', 'Csv',
			'Search.Prg' => array(
					'commonProcess' => array(
							//'filterEmpty' =>  true,
					),
			), );
	public $uses = array('ProgCorp', 'ProgImportFile', 'User', 'CommissionInfo',
			'ProgDemandInfo', 'MCorp', 'ProgAddDemandInfo',
			'ProgDemandInfoLog', 'ProgAddDemandInfoLog', 'ProgItem',
			'ProgDemandInfoTmp', 'ProgAddDemandInfoTmp', 'ProgDemandInfoOtherTmp'
	);


	public $presetVars = array(
			'cid' => array('type' => 'value', 'empty' => false, 'encode' => false), //企業ID
			'pf' => array('type' => 'value', 'empty' => false, 'encode' => false), //進捗状況
			'cn' => array('type' => 'value', 'empty' => false, 'encode' => true), //企業名
			'ctl' => array('type' => 'value', 'empty' => false, 'encode' => false), //送付方法
			'at' => array('type' => 'value', 'empty' => false, 'encode' => false), //後追いTEL
			'cbpf' => array('type' => 'value', 'empty' => false, 'encode' => false), //>架電後フラグ
			'atds' => array('type' => 'value', 'empty' => false, 'encode' => true), //後追いTEL日付
			'atde' => array('type' => 'value', 'empty' => false, 'encode' => true), //後追いTEL日付
			'up' => array('type' => 'value', 'empty' => false, 'encode' => true), //単価
			'cds' => array('type' => 'value', 'empty' => false, 'encode' => true), //回収日
			'cde' => array('type' => 'value', 'empty' => false, 'encode' => true), //回収日
			'crk' => array('type' => 'value', 'empty' => false, 'encode' => false), //後追い履歴記載
			'cr' => array('type' => 'value', 'empty' => false, 'encode' => true), //後追い履歴
			'limit' => array('type' => 'value', 'empty' => false, 'encode' => false), //表示件数
	);

	private $_user = null;

	public function beforeFilter() {
		parent::beforeFilter();

		//ORANGE-240 ADD S
		if($this->action == 'admin_demand_detail')$this->Security->csrfCheck = false;
		//ORANGE-240 ADD E

		$this->_user = $this->Auth->user();

		//加盟店ログイン時、アクセス制御
		if($this->_user['auth'] == 'affiliation'){
			//ORANGE-239 CHG S
			$affiliation_actions = array(1 => 'demand_detail', 2 => 'update_confirm', 3=> 'update_end', 4=> 'ajax_set_session');
			//ORANGE-239 CHG E
			if(!array_search($this->action, $affiliation_actions))throw new ApplicationException(__('NoReferenceAuthority', true));
		}

		//file用引数チェック
		$check_actions = array(1 => 'output_file_csv', 2 => 'corp_index', 3 => 'delete', 4 => 'demand_detail',);
		if(array_search($this->action, $check_actions)){

			if(empty($this->params['pass'][0]) || !ctype_digit($this->params['pass'][0]))
				throw new ApplicationException(__('NoReferenceAuthority', true));
			else{
				if($this->__check_import_file_delete($this->params['pass'][0]) == 0)
					throw new ApplicationException(__('NoReferenceAuthority', true));
			}

		}

		//corp用引数チェック
		$check_corp_actions = array(5 => 'admin_demand_detail', 6 => 'output_corp_pdf', 7 => 'output_corp_csv');
		if(array_search($this->action, $check_corp_actions)){
			if(empty($this->params['pass'][0]) || !ctype_digit($this->params['pass'][0])){
				throw new ApplicationException(__('NoReferenceAuthority', true));
			}
		}

		//システム管理者のみ
		if($this->_user['auth'] != 'system' && $this->action == 'delete')throw new ApplicationException(__('NoReferenceAuthority', true));
		if($this->_user['auth'] != 'system' && $this->_user['auth'] != 'admin' && $this->_user['auth'] != 'accounting_admin' && $this->action == 'import_commission_infos')throw new ApplicationException(__('NoReferenceAuthority', true));
		if($this->_user['auth'] != 'system' && $this->_user['auth'] != 'admin' && $this->_user['auth'] != 'accounting_admin' && $this->action == 'delete_commission_infos')throw new ApplicationException(__('NoReferenceAuthority', true));
	}

	/**
	 * 一覧
	 */
	public function index() {

		$this->Prg->commonProcess();

		$this->paginate =array(
				'page' =>1,
				'limit' => 30,
				'order' => array('ProgImportFile.id' => 'desc'),
				'fields' => array('*'),
				'conditions' => array('ProgImportFile.delete_flag' => 0,),
		);

		$this->set('import_files', $this->Paginator->paginate('ProgImportFile'));
		$this->set('user', $this->_user);

	}

	/**
	 * CSVインポートファイル出力
	 * @param unknown $file_id
	 */
	public function output_file_csv($file_id=null) {
		Configure::write('debug', 0);

		if(!$this->ProgImportFile->exists($file_id)){
			return;
		}

		$csv_data = array_merge($this->__get_csv_data($file_id, 'file'), $this->__get_add_csv_data($file_id, 'file'));
		$file_name = date('Ymd_His');
		//$this->log($csv_data, LOG_ERR);
		$this->Csv->download('ProgCorp', 'default', $file_name, $csv_data);
	}

	/**
	 * インポートデータ詳細
	 */
	public function corp_index($file_id=null){

		if($this->request->is('post') || $this->request->is('put')){
			//$this->log($this->request->data, LOG_ERR);
			if(!empty($this->request->data['ProgCorp']) && !empty($this->request->data['ProgImportFile'])){
				$file_id = $this->request->data['ProgImportFile']['id'];

				//更新
				if(!empty($this->request->data['indexUpdateForm'])){

					foreach($this->request->data['ProgCorp'] as $save_data){
						if($save_data['id'] == $this->request->data['indexUpdateForm']){
							$this->__update_index_prog_corp($save_data);
							break;
						}
					}
				}

				//ORANGE-218 CHG S メール、FAX送信条件の変更
				//単体メール送信
				if(!empty($this->request->data['indexMailForm']) || !empty($this->request->data['indexMailFaxForm'])){
					if(!empty($this->request->data['indexMailFaxForm']))$this->request->data['indexMailForm'] = $this->request->data['indexMailFaxForm'];

					foreach($this->request->data['ProgCorp'] as $send_data){
						if($send_data['id'] == $this->request->data['indexMailForm']){
							$options = array(
									'conditions' => array('delete_flag' => 0),
									'order' => array('id' => 'desc'), 'limit' => 1);
							$pif = $this->ProgImportFile->find('first', $options);

							$prog_item = $this->ProgItem->read(null, 1);
							$corp = array();
							$corp['official_corp_name'] = $send_data['official_corp_name'];

							$address = explode(';', $send_data['mail_address']);

							$save_flg = true;
							//メールアドレスが複数ある場合の対応
							foreach ($address as $item){
								$corp['mail_address'] = $item;

								// murata.s ORANGE-412 CHG(S)
								if(!$this->__send_admin_corp_mail($corp, $file_id, $prog_item['ProgItem']['return_limit'])){
									$save_flg = false;
								}
								// murata.s ORANGE-412 CHG(E)
							}

							if($save_flg){
								$this->ProgCorp->id = $send_data['id'];
								$cnt = $this->ProgCorp->read(null);
								$this->ProgCorp->saveField('mail_count', $cnt['ProgCorp']['mail_count']+1);
								$this->ProgCorp->saveField('progress_flag', 2);
								$this->ProgCorp->saveField('mail_last_send_date', date("Y-m-d H:i:s"));

								$this->Session->setFlash("加盟店名 「".$send_data['official_corp_name']."」様にメールを送信しました。<br/>", 'default', array(), 'mail');
							}else{
								$this->Session->setFlash("加盟店名 「".$send_data['official_corp_name']."」様へのメール送信に失敗しました。<br/>", 'default', array(), 'mail');
							}
							continue;
						}
					}
				}

				//単体Fax送信
				if(!empty($this->request->data['indexFaxForm']) || !empty($this->request->data['indexMailFaxForm'])){
					if(!empty($this->request->data['indexMailFaxForm']))$this->request->data['indexFaxForm'] = $this->request->data['indexMailFaxForm'];
					set_time_limit(0);
					foreach($this->request->data['ProgCorp'] as $send_data){

						if($send_data['id'] == $this->request->data['indexFaxForm']){
							if($this->__send_admin_corp_fax($send_data)){
								$this->layout = 'default';

								$this->ProgCorp->id = $send_data['id'];
								$cnt = $this->ProgCorp->read('fax_count');
								$this->ProgCorp->saveField('progress_flag', 2);
								$this->ProgCorp->saveField('fax_last_send_date', date("Y-m-d H:i:s"));

								$this->ProgCorp->saveField('fax_count', $cnt['ProgCorp']['fax_count']+1);
								$this->Session->setFlash("加盟店名 「".$send_data['official_corp_name']."」様にFAXを送信しました。", 'default',  array(), 'fax');
							}else{
								$this->Session->setFlash("加盟店名 「".$send_data['official_corp_name']."」様へのFAX送信に失敗しました。。", 'default', array(), 'fax');
							}

							continue;
						}
					}
					$this->layout = 'default';


				}

				//全体メール送信
				if(!empty($this->request->data['indexAllMailForm']) || !empty($this->request->data['indexAllMailFaxForm'])){
					$options = array(
							'conditions' => array('delete_flag' => 0),
							'order' => array('id' => 'desc'), 'limit' => 1);
					$pif = $this->ProgImportFile->find('first', $options);

					$prog_item = $this->ProgItem->read(null, 1);
					foreach($this->request->data['ProgCorp'] as $send_data){
						if($send_data['check'] == 1){
							$corp = array();
							$corp['official_corp_name'] = $send_data['official_corp_name'];
							$address = explode(';', $send_data['mail_address']);

							$save_flg = true;
							//メールアドレスが複数ある場合の対応
							foreach ($address as $item){
								$corp['mail_address'] = $item;
								// murata.s ORANGE-412 CHG(S)
								if(!$this->__send_admin_corp_mail($corp, $file_id, $prog_item['ProgItem']['return_limit'])){
									$save_flg = false;
								}
								// murata.s ORANGE-412 CHG(E)
							}

							if($save_flg){
								$this->ProgCorp->id = $send_data['id'];
								$cnt = $this->ProgCorp->read('mail_count');

								$this->ProgCorp->saveField('progress_flag', 2);
								$this->ProgCorp->saveField('mail_last_send_date', date("Y-m-d H:i:s"));
								$this->ProgCorp->saveField('mail_count', $cnt['ProgCorp']['mail_count']+1);
							}

						}
					}
					$this->Session->setFlash("メールを一括送信しました。<br/>", 'default',  array(), 'mail');
				}


				//全体Fax送信
				if(!empty($this->request->data['indexAllFaxForm']) || !empty($this->request->data['indexAllMailFaxForm'])){
					set_time_limit(0);
					foreach($this->request->data['ProgCorp'] as $send_data){
						if($send_data['check'] == 1){
							if($this->__send_admin_corp_fax($send_data)){
								$this->ProgCorp->id = $send_data['id'];
								$cnt = $this->ProgCorp->read('fax_count');
								$this->ProgCorp->saveField('fax_count', $cnt['ProgCorp']['fax_count']+1);
								$this->ProgCorp->saveField('progress_flag', 2);
								$this->ProgCorp->saveField('fax_last_send_date', date("Y-m-d H:i:s"));
							}
						}
					}
					$this->Session->setFlash("FAXを一括送信しました。", 'default',  array(), 'fax');
					$this->layout = 'default';


				}
				//ORANGE-218 CHG E メール、FAX送信条件の変更

				//検索
				if(!empty($this->request->data['searchForm'])){

					if(!empty($this->request->params['named']['page']))$this->request->params['named']['page'] = 1;
					//$this->Prg->commonProcess();
				}else{
					$this->request->data = null;
				}

				$this->Prg->commonProcess();
			}
		}else{
			if(empty($file_id)){

				return $this->redirect(array('action' => 'index'));
			}
			$this->Prg->commonProcess();
		}

		$this->ProgImportFile->id = $file_id;


		if(!$this->ProgImportFile->exists($file_id)){
			return $this->redirect(array('action' => 'index'));
		}

		//並び順
		$order = 'ProgCorp.id asc';
		if(!empty($this->request->data['ProgCorp']['up'])){
			if($this->request->data['ProgCorp']['up'] == 1)$order = 'ProgCorp.unit_cost is null, ProgCorp.unit_cost asc';
			elseif($this->request->data['ProgCorp']['up'] == 2) $order = 'ProgCorp.unit_cost desc, ProgCorp.unit_cost is null';

		}

		//表示件数
		$limit = 30;
		if(!empty($this->request->data['ProgCorp']['limit'])){
			$limit = $this->request->data['ProgCorp']['limit'];
		}

		$this->ProgCorp->bindModel(
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
                
                // 2017/07/07 ORANGE-451 inokuma ADD(S)
                //休業日取得
                $this->ProgCorp->virtualFields = array(
                    'holiday' => "(ARRAY_TO_STRING(ARRAY(SELECT mi.item_name FROM m_corp_subs mcs "
                    . "INNER JOIN m_items mi ON mi.item_category = mcs.item_category AND mi.item_id = mcs.item_id "
                    . "WHERE mcs.item_category = '休業日' AND mcs.corp_id = MCorp.id), '・'))",
                    );
                // 2017/07/07 ORANGE-451 inokuma ADD(E)
                
		$this->paginate =array(
				'page' =>1,
				'limit' => $limit,
				'order' => $order,
				'fields' => array('*', 'MCorp.official_corp_name', 'MCorp.commission_dial', 'MCorp.prog_irregular'),
				'conditions' => array(
                                    'and' => array(
                                        'ProgCorp.prog_import_file_id' => $file_id,
                                        $this->ProgCorp->parseCriteria($this->Prg->parsedParams()),
                                        )
                                    ),
		);

		$data = $this->Paginator->paginate('ProgCorp');

		$this->set(compact('data'));
		$this->set('import_file', $this->ProgImportFile->read(null));
		$this->render('corp_index');
	}

	/**
	 * 企業情報の更新
	 */
	private function __update_index_prog_corp($save_data = null){

		if($this->ProgCorp->save($save_data)){
			$this->Session->setFlash("加盟店名 「".$save_data['official_corp_name']."」様の情報を更新しました。");
			return true;
		}else{
			$this->Session->setFlash("加盟店名 「".$save_data['official_corp_name']."」様の情報の更新に失敗しました。");
			return false;
		}


	}

	/**
	 * 削除
	 * @param unknown $file_id
	 */
	public function delete($file_id=null){

		if(empty($file_id)){
			$this->Session->setFlash(__('インポートファイルの削除に失敗しました'));
			return $this->redirect(array('action' => 'index'));
		}

		$this->ProgImportFile->id = $file_id;

		if(!$this->ProgImportFile->exists()){
			$this->Session->setFlash(__('インポートファイルの削除に失敗しました'));
			return $this->redirect(array('action' => 'index'));
		}

		$savedata['ProgImportFile']['delete_flag'] = 1;
		$savedata['ProgImportFile']['modified_user_id'] = $this->_user['user_id'];

		if ($this->ProgImportFile->save($savedata)) {
			$this->Session->setFlash(__('インポートファイルの削除に成功しました'));
		} else {
			$this->Session->setFlash(__('インポートファイルの削除に失敗しました'));
		}

		return $this->redirect(array('action' => 'index'));

	}

	/**
	 * 取次データの削除
	 * @param unknown $file_id
	 */
	public function delete_commission_infos($file_id = null){
		$this->layout = 'progress_management';

		if(empty($file_id) || !ctype_digit($file_id) || !$this->ProgImportFile->exists($file_id)){
			throw new ApplicationException(__('NoReferenceAuthority', true));
		}

		$this->ProgImportFile->id = $file_id;
		$import_file = $this->ProgImportFile->read(null);
		$pagetitle = '進捗表データ 削除';
		$this->set(compact('pagetitle', 'import_file'));

		if($this->request->is('post') || $this->request->is('put')){
			$del_ids = '';

			//データの正常正確認
			//削除
			if(!empty($this->request->data['CommissionInfo']['del_ids'])){
				$del_target = $this->request->data['CommissionInfo']['del_ids'];
				$del_target = str_replace(array("\r\n", "\r", "\n", "　", '', ' '), '' ,$del_target);

				$ary_del_ids = explode(',', $del_target);
				foreach($ary_del_ids as $key => $id){
					if(!ctype_digit($id)){
						$this->Session->setFlash('取次IDの入力が正しくありません<br/>数字とカンマを入力してください');
						return;
					}//else{
						//if($key == count($ary_del_ids)-1){
						//	$del_ids .= $id;
						//}else	$del_ids .= $id.',';
					//}
				}

				$this->log('進捗管理案件 手動削除Start', PROG_IMPORT_LOG);
				$this->log('実行ユーザ: '.$this->_user['id'], PROG_IMPORT_LOG);
				$this->log('取次ID: '. $del_target, PROG_IMPORT_LOG);
				$this->log('案件情報の削除start', PROG_IMPORT_LOG);
				//削除処理
				$conditions = array('ProgDemandInfo.commission_id' => $ary_del_ids, 'ProgDemandInfo.prog_import_file_id' => $import_file['ProgImportFile']['id']);
				//pr($conditions);
				try{
					if($this->ProgDemandInfo->deleteAll($conditions, false)){
						$this->Session->setFlash('進捗データの削除に成功しました');
						$this->log('案件情報の削除成功', PROG_IMPORT_LOG);
					}else{
						$this->Session->setFlash('進捗データの削除に失敗しました');
						$this->log('案件情報の削除失敗', PROG_IMPORT_LOG);
					}
				}catch(Exception $e){
					// エラーはインポート用のログに出力
					$this->log($e->getMessage(), PROG_IMPORT_LOG);
					$this->log('案件情報の削除失敗', PROG_IMPORT_LOG);

					$this->Session->setFlash('進捗データの削除に失敗しました');
				}

				$this->log('進捗管理案件 手動削除End', PROG_IMPORT_LOG);
			}else{
				$this->Session->setFlash('取次IDを入力してください');
			}
		}
	}

	/**
	 * 取次データの追加インポート
	 * @param unknown $file_id
	 */
	public function import_commission_infos($file_id = null){
		$this->layout = 'progress_management';

		if(empty($file_id) || !ctype_digit($file_id) || !$this->ProgImportFile->exists($file_id)){
			throw new ApplicationException(__('NoReferenceAuthority', true));
		}

		$this->ProgImportFile->id = $file_id;
		$import_file = $this->ProgImportFile->read(null);
		$locks = array('1' => 'ロックしない', '2' => 'ロックする');
		$pagetitle = '進捗表データ 追加';
		$this->set(compact('pagetitle', 'locks', 'import_file'));

		if($this->request->is('post') || $this->request->is('put')){

			$add_ids = '';


			//データの正常正確認
			//追加
			if(!empty($this->request->data['CommissionInfo']['add_ids']) && !empty($this->request->data['CommissionInfo']['lock'])){
				$add_target = $this->request->data['CommissionInfo']['add_ids'];
				$add_target = str_replace(array("\r\n", "\r", "\n", "　", '', ' '), '' ,$add_target);

				$ary_add_ids = explode(',', $add_target);
				foreach($ary_add_ids as $key => $id){
					if(!ctype_digit($id)){
						$this->Session->setFlash('取次IDの入力が正しくありません<br/>数字とカンマを入力してください');
						return;
					}else{
						if($key == count($ary_add_ids)-1){
							$add_ids .= $id;
						}else	$add_ids .= $id.',';
					}
				}
// 2017.01.04 murata.s ORANGE-244 CHG(S)
				//ORANGE-218 CHG S
				//追加データの取得
				$sql ="
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
				commission_infos.id IN (".$add_ids.");";
				//ORANGE-218 CHG E
// 2017.01.04 murata.s ORANGE-244 ADD(E)

				try{
					$this->CommissionInfo->begin();
					$this->ProgCorp->begin();
					$this->ProgDemandInfo->begin();

					$this->log('進捗管理案件 手動追加Start', PROG_IMPORT_LOG);
					$this->log('実行ユーザ: '.$this->_user['id'], PROG_IMPORT_LOG);
					$this->log('lock: '. $locks[$this->request->data['CommissionInfo']['lock']], PROG_IMPORT_LOG);
					$this->log('取次ID: '. $add_ids, PROG_IMPORT_LOG);
					$this->log('案件情報の取得start', PROG_IMPORT_LOG);
					//追加データセット
					$commission_infos = $this->CommissionInfo->query($sql);
					$this->log('案件情報の取得end', PROG_IMPORT_LOG);

					$this->log('進捗管理案件情報の作成start', PROG_IMPORT_LOG);
					foreach($commission_infos as $commission_info){
						// 進捗表管理企業の作成
						$progress_corp = $this->insert_prog_corp($commission_info[0], $import_file['ProgImportFile']['id']);

						// 進捗管理案件情報の登録
						$this->insert_prog_demand_info($commission_info[0], $import_file['ProgImportFile']['id'], $progress_corp['ProgCorp']['id'], $this->request->data['CommissionInfo']['lock']);
					}

					$this->CommissionInfo->commit();
					$this->ProgCorp->commit();
					$this->ProgDemandInfo->commit();
					$this->log('進捗管理案件情報の作成end', PROG_IMPORT_LOG);
					$this->Session->setFlash('進捗データの追加に成功しました');

				}catch(Exception $e){
					// エラーはインポート用のログに出力
					$this->log($e->getMessage(), PROG_IMPORT_LOG);

					$this->CommissionInfo->rollback();
					$this->ProgCorp->rollback();
					$this->ProgDemandInfo->rollback();

					$this->Session->setFlash('進捗データの追加に失敗しました');

				}
				$this->log('進捗管理案件 手動追加End', PROG_IMPORT_LOG);

			}else{
				$this->Session->setFlash('取次IDを入力してください');
			}
		}
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

			// AppModelのBeforeSaveでcreated_user_id, modified_user_idを空文字で上書きしてしまうため、
			// 一時的にコールバックを無効にする
			return $this->ProgCorp->save($insert, array(
					'validate' => false,
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
	 */
	private function insert_prog_demand_info($rows, $import_file_id, $import_corp_id, $lock){

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

			// AppModelのBeforeSaveでcreated_user_id, modified_user_idを空文字で上書きしてしまうため、
			// 一時的にコールバックを無効にする
			$this->ProgDemandInfo->save($insert, array(
					'validate' => false,
			));

			if(!empty($lock) && $lock == 2){
				$this->CommissionInfo->query("SELECT set_lock_status(".$rows['commission_infos_id'].", 1);");
			}
		}
	}

	/**
	 * PDF出力
	 */
	public function output_corp_pdf($prog_corp_id = null, $type = 'D'){
		Configure::write('debug', 0);

		if(!$this->ProgCorp->exists($prog_corp_id)){
			return;
		}

		$this->layout = 'ajax';
		$this->render(false);

		// import mPDF
		App::import('Vendor', 'mPDF', array('file' => 'mpdf/mpdf.php'));
		if (!class_exists('mPDF')){
			throw new CakeException('Vendor class mPDF not found!');
		}
		/**
		* postデータの受け取り
		* post受信時は、json_encodeされているので
		* json_decodeして、配列に戻してあげる。
		*/

		$options = array('conditions' => array('ProgCorp.id' => $prog_corp_id));
		$this->ProgCorp->bindModel(array('belongsTo' => array('MCorp' => array('foreignKey' => 'corp_id'))));
		$prog_corp = $this->ProgCorp->find('first', $options);

		$options = array('conditions' => array('ProgDemandInfo.prog_corp_id' => $prog_corp_id));
		$prog_demand = $this->ProgDemandInfo->find('all', $options);

// /* 		$csv_data_table = new CsvDataTable();
// 		$csv_list_table   = new CsvListTable();
// 		$partner_table    = new PartnerTable();
// 		$edit_text    = new EditText();

// 		$edit_text_data = $edit_text->query()->filter("id=1")->get();

// 		$edit_text_data->message = str_replace("\r\n","<br>",$edit_text_data->message);
// 		$edit_text_data->news = str_replace("\r\n","<br>",$edit_text_data->news);

// 		$pdfData = $csv_data_table->query()->filter("csv_list_id=".$csvListId." AND csv_data_partner_id=".$partnerId)->order("csv_data_get_date")->fetch();
// 		$partnerData = $partner_table->query()->filter("csv_list_id=".$csvListId." AND partner_id=".$partnerId)->get();
// 		$conditionData = $csv_data_table->csv_data_condition_kv;
// 		$diffData = $csv_data_table->csv_data_diff_kv;
// 		$csvListData = $csv_list_table->query()->filter("csv_list_id=".$csvListId)->get();
// 		$csvImportDate = explode(" " , $csvListData->csv_import_date);

 		$dateObj = new DateTime();
 		$issueDate = $dateObj->format("n月j日");

 		/**
 		 * PDF描画開始
 		 */
 		$pdf = new mPDF('ja','A4-L','8','sjis',10,10,35,8,8,8);

 		$pdf->mirrorMargins = 0;
 		$pdf->defaultfooterfontsize = 12;
 		$pdf->defaultfooterfontstyle = "B";
 		$pdf->defaultfooterline = 1;
 		$footer = array(
 				'C' => array(
 						'content' => '{PAGENO} / {nbpg}',
 						'font-style' => 'B',
 						'font-size' => '9',
 				),
 				'line' => 0,
 		);

 		//$pdf->SetFooter($footer);で、偶数ページ奇数ページ関係なく全ページに出せるはずだが、
 		//出せないので'Odd'と'Even'両方使用する
 		$pdf->SetFooter($footer, 'O');
 		$pdf->SetFooter($footer, 'E');

 		//ob_start();

 		//PDF作成用HTMLの取得
 		$View = new View();
 		$View->viewPath = 'ProgressManagement'; // Viewの下のフォルダ名
 		$View->viewVars['prog_corp'] = $prog_corp; //パラメータ
 		$header = $View->render('pdf_header', false);
 //{$csvImportDate[0]}
 		$pdf->SetHTMLHeader($header);

 		$pi = $this->ProgItem->read(null, 1);

 		$View2 = new View();
 		$View2->viewPath = 'ProgressManagement'; // Viewの下のフォルダ名
 		$View2->viewVars['prog_corp'] = $prog_corp; //パラメータ
 		$View2->viewVars['pi'] = $pi; //パラメータ
 		$body = $View2->render('pdf_body', false);
 		$pdf->WriteHTML($body);
 		$pdf->AddPage();

// $ankensu = count($pdfData);

 /**
  * 追加施工案件についての入力箇所
  */
 		$View3 = new View();
 		$View3->viewPath = 'ProgressManagement'; // Viewの下のフォルダ名
 		$View3->viewVars['prog_corp'] = $prog_corp; //パラメータ
 		$View3->viewVars['prog_demand'] = $prog_demand;
 		$View3->viewVars['pi'] = $pi; //パラメータ
 		$addition = $View3->render('pdf_addition', false);
 		$pdf->WriteHTML($addition);

		if($type == 'D')$pdf->Output($prog_corp['MCorp']['corp_name']."_".$prog_corp['MCorp']['id'].".pdf", "D"); // PDFをブラウザに「Inline表示する」の「I」[D]download
		elseif($type == 'F'){
			$pdf->Output(TMP.'file/prog/'.$prog_corp['ProgCorp']['id'].".pdf", "F");
			return TMP.'file/prog/'.$prog_corp['ProgCorp']['id'].".pdf";
		}
	}

	/**
	 * CSV出力
	 */
	public function output_corp_csv($prog_corp_id = null){
		Configure::write('debug', 0);

		if(!$this->ProgCorp->exists($prog_corp_id)){
			return;
		}

		//$csv_data = $this->__get_csv_data($prog_corp_id);
		$csv_data = array_merge($this->__get_csv_data($prog_corp_id), $this->__get_add_csv_data($prog_corp_id));
		$file_name = date('Ymd_His');
		//$this->log($csv_data, LOG_ERR);
		$this->Csv->download('ProgCorp', 'default', $file_name, $csv_data);

	}

	/**
	 * アイテムの編集
	 */
	public function item_edit(){
		$this->layout = 'progress_management';

		if($this->request->is('post') || $this->request->is('put')){
			if(!empty($this->request->data)){
				if($this->ProgItem->save($this->request->data)){
					//成功メッセージ
					$this->Session->setFlash ( '更新に成功しました', 'default');
				}else{
					//エラーメッセージ
					$this->Session->setFlash ( '更新に失敗しました', 'default');
				}
			}

		}else{
			$this->request->data = $this->ProgItem->find('first', array('conditions' => array('ProgItem.id' => 1)));
		}

		$pagetitle = '加盟店入力画面文言変更';
		$this->set(compact('pagetitle'));
	}

	/**
	 * 案件一覧 管理画面
	 * @param unknown $prog_import_file_id
	 */
	public function admin_demand_detail($prog_corp_id = null){
		$this->layout = 'progress_management';

		$refresh = false;

		if($this->request->is('post') || $this->request->is('put')){
			//追加案件登録
			if(!empty($this->request->data['addDemandUpdate'])){
				//追加案件登録処理
				$save_add_data =$this->ProgAddDemandInfo->addValidateAll($this->request->data['ProgAddDemandInfo']);

				$save_flg = true;
				$modified = date('Y-m-d H:i:s');

				if($save_add_data){

					for($i=0;$i<count($save_add_data);$i++){
						$save_add_data[$i]['prog_corp_id'] = $this->request->data['ProgCorp']['id'];
						$save_add_data[$i]['prog_import_file_id'] = $this->request->data['ProgImportFile']['id'];
						$save_add_data[$i]['corp_id'] = $this->request->data['ProgCorp']['corp_id'];

						if(!empty($_SERVER['X-ClientIP']))$save_add_data[$i]['ip_address_update'] = $_SERVER['X-ClientIP'];
						elseif(!empty($_SERVER['REMOTE_ADDR']))$save_add_data[$i]['ip_address_update'] = $_SERVER['REMOTE_ADDR'];
						$save_add_data[$i]['user_agent_update'] = substr($_SERVER['HTTP_USER_AGENT'], 0, 255);
					}

					try{
						//トランザクション開始
						$this->ProgAddDemandInfo->begin();

						//追加案件登録
						if(!$this->ProgAddDemandInfo->saveAll($save_add_data, array('atomic' => false)))$save_flg = false;

						//更新フラグでトランザクション終了
						if(!$save_flg)$this->ProgAddDemandInfo->rollback();
						else $this->ProgAddDemandInfo->commit();

					}catch(Exception $e){
						$this->log($e->getMessage(), PROG_IMPORT_LOG);
						$save_flg = false;
						$this->ProgAddDemandInfo->rollback();
					}
				}

				//追加案件削除処理
				if($save_flg)$save_flg = $this->ProgAddDemandInfo->DeleteById($this->request->data['ProgAddDemandInfo'], $this->request->data['ProgCorp']['id'], $this->request->data['ProgImportFile']['id'], $modified);

				//ログの追加
				if($save_flg){
					$this->__insert_add_demand_info_log($this->request->data['ProgCorp']['corp_id'], $this->request->data['ProgImportFile']['id']);
					$this->Session->setFlash('追加案件の更新に成功しました');
// murata.s ORANGE-384 ADD(S)
					$refresh = true;
// murata.s ORANGE-384 ADD(E)
				}else{
					$this->Session->setFlash('追加案件の更新に失敗しました');
				}


			//既存案件全体更新
			}elseif(!empty($this->request->data['updateAllForm']) && !empty($this->request->data['ProgDemandInfo'])){
				$save_flg = true;

				try{
					//トランザクション開始
					$this->ProgDemandInfo->begin();

					//ID正常性確認
					for($i=0;$i < count($this->request->data['ProgDemandInfo']);$i++){

						$cnt = $this->ProgDemandInfo->find('count',array(
								'conditions' => array(
										'ProgDemandInfo.id' => $this->request->data['ProgDemandInfo'][$i]['id'],
										'ProgDemandInfo.demand_id' => $this->request->data['ProgDemandInfo'][$i]['demand_id'],
										'ProgDemandInfo.commission_id' => $this->request->data['ProgDemandInfo'][$i]['commission_id'],
										'ProgDemandInfo.corp_id' => $this->request->data['ProgCorp']['corp_id'],
										'ProgDemandInfo.prog_import_file_id' => $this->request->data['ProgImportFile']['id']
								)));

						if($cnt == 0)throw new ApplicationException(__('NoReferenceAuthority', true));

						if(!empty($_SERVER['X-ClientIP']))$this->request->data['ProgDemandInfo'][$i]['ip_address_update'] = $_SERVER['X-ClientIP'];
						elseif(!empty($_SERVER['REMOTE_ADDR']))$this->request->data['ProgDemandInfo'][$i]['ip_address_update'] = $_SERVER['REMOTE_ADDR'];
						$this->request->data['ProgDemandInfo'][$i]['user_agent_update'] = substr($_SERVER['HTTP_USER_AGENT'], 0, 255);

					}
					//pr($this->request->data);
					/*****案件更新****************************************/
					if(!$this->ProgDemandInfo->saveAll($this->request->data['ProgDemandInfo'], array('validate' => 'only')))$save_flg = false;
					if($save_flg){
						if(!$this->ProgDemandInfo->saveAll($this->request->data['ProgDemandInfo'], array('atomic' => false)))$save_flg = false;
					}

					//更新処理に失敗していれば、トランザクションRoolback
					if(!$save_flg)$this->ProgDemandInfo->rollback();
					else $this->ProgDemandInfo->commit();

					/*****案件更新****************************************/

				}catch(Exception $e){
					//トランザクションRoolback
					$this->ProgDemandInfo->rollback();
					$save_flg = false;
					$this->log($e->getMessage(), PROG_IMPORT_LOG);
				}
				//ログの追加
				if($save_flg){
					$this->__insert_demand_info_log_and_update_commission($this->request->data['ProgCorp']['corp_id'], $this->request->data['ProgImportFile']['id'], 'admin');
					$this->Session->setFlash('案件の更新に成功しました');
// murata.s ORANGE-384 ADD(S)
					$refresh = true;
// murata.s ORANGE-384 ADD(E)
				}else{
					$this->Session->setFlash('案件の更新に失敗しました');
				}
			//既存案件単体更新
			}elseif(!empty($this->request->data['updateForm']) && !empty($this->request->data['ProgDemandInfo'])){
				$save_flg = true;
				$save_data = array();

				try{
					//トランザクション開始
					$this->ProgDemandInfo->begin();

					//ID正常性確認
					for($i=0;$i < count($this->request->data['ProgDemandInfo']);$i++){
						//対象ID
						if($this->request->data['ProgDemandInfo'][$i]['id'] == $this->request->data['updateForm']){

							$cnt = $this->ProgDemandInfo->find('count',array(
									'conditions' => array(
											'ProgDemandInfo.id' => $this->request->data['ProgDemandInfo'][$i]['id'],
											'ProgDemandInfo.demand_id' => $this->request->data['ProgDemandInfo'][$i]['demand_id'],
											'ProgDemandInfo.commission_id' => $this->request->data['ProgDemandInfo'][$i]['commission_id'],
											'ProgDemandInfo.corp_id' => $this->request->data['ProgCorp']['corp_id'],
											'ProgDemandInfo.prog_import_file_id' => $this->request->data['ProgImportFile']['id']
									)));

							if($cnt == 0)throw new ApplicationException(__('NoReferenceAuthority', true));

							$save_data[$i] = $this->request->data['ProgDemandInfo'][$i];

							if(!empty($_SERVER['X-ClientIP']))$save_data[$i]['ip_address_update'] = $_SERVER['X-ClientIP'];
							elseif(!empty($_SERVER['REMOTE_ADDR']))$save_data[$i]['ip_address_update'] = $_SERVER['REMOTE_ADDR'];
							$save_data[$i]['user_agent_update'] = substr($_SERVER['HTTP_USER_AGENT'], 0, 255);
							$save_data[$i]['prog_corp_id'] = $this->request->data['ProgCorp']['id'];
							$save_data[$i]['prog_import_file_id'] = $this->request->data['ProgImportFile']['id'];
							$save_data[$i]['corp_id'] = $this->request->data['ProgCorp']['corp_id'];
							break;
						}

					}

					/*****案件更新****************************************/
					if(!$this->ProgDemandInfo->saveAll($save_data, array('validate' => 'only')))$save_flg = false;
					if($save_flg){
						if(!$this->ProgDemandInfo->saveAll($save_data, array('atomic' => false)))$save_flg = false;
					}

					//更新処理に失敗していれば、トランザクションRoolback
					if(!$save_flg)$this->ProgDemandInfo->rollback();
					else $this->ProgDemandInfo->commit();

					/*****案件更新****************************************/
				}catch(Exception $e){
					//トランザクションRoolback
					$this->ProgDemandInfo->rollback();
					$save_flg = false;
					$this->log($e->getMessage(), PROG_IMPORT_LOG);
				}

				//ログの追加
				if($save_flg){
					//取次データの更新
					$tmp_data = $this->ProgDemandInfo->find('first', array('conditions' => array('ProgDemandInfo.id' => $this->request->data['updateForm'])));
					$log_data[] = $tmp_data['ProgDemandInfo'];
					$this->__update_commission_infos($log_data, 'admin');
					$this->__insert_log($log_data);
					$this->Session->setFlash('案件の更新に成功しました');
// murata.s ORANGE-384 ADD(S)
					$refresh = true;
// murata.s ORANGE-384 ADD(E)
				}else{
					$this->Session->setFlash('案件の更新に失敗しました');
				}
			//再取得
			}else if(!empty($this->request->data['reacquisitionForm'])){
				 $rtn = $this->__reacquisition($this->request->data['reacquisitionForm']);

				 //ORANGE-270 ADD S
				 //一時テーブルの更新
				 if($rtn) $rtn = $this->__reacquisition_tmp_data($this->request->data['reacquisitionForm']);
				 //ORANGE-270 ADD E

				 if($rtn)$this->Session->setFlash('案件の再取得に成功しました');
				 else $this->Session->setFlash('案件の再取得に失敗しました');
				 $refresh = true;

			}
		}else{
			$refresh = true;
		}
		if($refresh){
			$options = array(
						'conditions' => array(
							'ProgCorp.id' => $prog_corp_id,
						),
			);

			$this->ProgAddDemandInfo->virtualFields['display'] =1;

			$this->ProgCorp->bindModel(
						array(
							'hasMany' => array(
// murata.s ORANGE-384 DEL(S)
// 								'ProgDemandInfo' => array(
// 										'className' => 'ProgDemandInfo',
// 										'foreignKey' => 'prog_corp_id',
// 										'order' => 'receive_datetime',
// 								),
// murata.s ORANGE-384 DEL(E)
								'ProgAddDemandInfo' => array(
										'className' => 'ProgAddDemandInfo',
										'foreignKey' => 'prog_corp_id',
										'order' => 'id',
								),
							),
							'belongsTo' => array(
								'ProgImportFile' => array(
										'className' => 'ProgImportFile',
										'foreignKey' => 'prog_import_file_id',
								),
								'MCorp' => array(
										'className' => 'MCorp',
										'foreignKey' => 'corp_id',
										'fields' => array('official_corp_name'),
								),
							),
						)
					);
			$data = $this->ProgCorp->find('all', $options);
			if(!empty($data[0]))$this->request->data = $data[0];
// murata.s ORANGE-384 ADD(S)
			$this->paginate = array(
					'conditions' => array('
							ProgDemandInfo.prog_corp_id' => $prog_corp_id
					),
					'limit' => PM_DETAIL_LIST_LIMIT
			);
			$progDemandInfos = $this->paginate('ProgDemandInfo');

			// データを整形
			foreach ($progDemandInfos as $progDemandInfo){
				$this->request->data['ProgDemandInfo'][] = $progDemandInfo['ProgDemandInfo'];
			}
// murata.s ORANGE-384 ADD(E)
		}

		$pagetitle = '進捗管理システム';
		$this->set(compact( 'pagetitle'));
	}

	/**
	 * 案件一覧 加盟店
	 *
	 */
	public function demand_detail($prog_import_file_id = null){
		$this->layout = 'progress_management';

		if(!$this->ProgImportFile->exists(($prog_import_file_id))){
			throw new ApplicationException(__('NoReferenceAuthority', true));
		}else{
			$this->request->data['ProgImportFile']['file_id'] = $prog_import_file_id;
		}

		$corp_id = $this->_user['affiliation_id'];
		$pc = $this->ProgCorp->find('first', array('conditions' => array(
				'ProgCorp.corp_id' => $corp_id,
				'ProgCorp.prog_import_file_id' => $prog_import_file_id,
				'ProgCorp.progress_flag' => 2
		)));

		//ORANGE-239 ADD S
		if(!empty($pc['ProgCorp']['id']))$this->set(array('prog_corp_id' => $pc['ProgCorp']['id']));
		//ORANGE-239 ADD E

		if($this->request->is('post') || $this->request->is('put')){
			if(!empty($this->request->data)){
// murata.s ORANGE-384 CHG(S)
				if(!empty($this->request->data['submitButton'])){

					$this->__set_tmp($this->request->data, $pc['ProgCorp']['id']);

					if($this->ProgDemandInfo->saveAll($this->request->data['ProgDemandInfo'], array('validate' => 'only'))){
						//$this->Session->write('pm_demand_infos', $this->request->data);
						//$this->__set_tmp($this->request->data, $pc['ProgCorp']['id']);
						$this->redirect(array('action' => 'update_confirm', $this->request->data['ProgImportFile']['file_id']));
					}
					//$this->Session->write('pm_demand_infos', $this->request->data);
				}elseif(!empty($this->request->data['prevButton'])){
					$nextPage = $this->request->data['page'];
					//$this->__set_tmp($this->request->data, $pc['ProgCorp']['id']);
					//if($this->ProgDemandInfo->saveAll($this->request->data['ProgDemandInfo'], array('validate' => 'only'))){
					//	$nextPage--;
					//}
					$nextPage--;
				}elseif(!empty($this->request->data['nextButton'])){
					$nextPage = $this->request->data['page'];
					$this->__set_tmp($this->request->data, $pc['ProgCorp']['id']);
					if($this->ProgDemandInfo->saveAll($this->request->data['ProgDemandInfo'], array('validate' => 'only'))){
						$nextPage++;
					}
 				}elseif(!empty($this->request->data['submitSession'])){

					//$this->Session->write('pm_demand_infos', $this->request->data);
					$this->__set_tmp($this->request->data, $pc['ProgCorp']['id']);
					$nextPage = $this->request->data['page'];

				}
// murata.s ORANGE-384 CHG(E)
			}else{
				//データなし
			}
		}

		$corpinfo = $this->MCorp->read('official_corp_name', $corp_id);

		//対象案件が既にない場合。
		if(empty($pc['ProgCorp']['id'])){
			$pagetitle = $corpinfo['MCorp']['official_corp_name'].'様 送信完了';
			$this->set(compact('pagetitle'));
			$this->render('update_end');
			return;
		}

// murata.s ORANGE-384 CHG(S)
		$prog_tmp = $this->__get_tmp($pc['ProgCorp']['id']);
		$prog_data = $this->__get_prog_demand_infos($pc['ProgCorp']['id'], !empty($nextPage) ? $nextPage : 1);
		foreach($prog_data['ProgDemandInfo'] as $key=>$val){
			foreach($prog_tmp as $tmp){
				if($tmp['prog_demand_info_id'] == $val['id']){
					$prog_data['ProgDemandInfo'][$key] = $tmp;
					break;
				}
			}
		}
		$this->request->data['ProgDemandInfo'] = $prog_data['ProgDemandInfo'];

		//Confirmからの戻り
		if($prog_tmp){
// murata.s 2017.06.08 CHG(S)
			$this->request->data['ProgAddDemandInfo'] = $this->__get_tmp($pc['ProgCorp']['id'], 'add_demand_info');
			if(empty($this->request->data['ProgAddDemandInfo']) && !empty($prog_data['ProgAddDemandInfo'])){
				$this->request->data['ProgAddDemandInfo'] = $prog_data['ProgAddDemandInfo'];
			}
// murata.s 2017.06.08 CHG(E)
			$this->request->data['ProgImportFile'] = $this->__get_tmp($pc['ProgCorp']['id'], 'file');
			$this->request->data['ProgDemandInfoOther'] = $this->__get_tmp($pc['ProgCorp']['id'], 'other');
			//$this->request->data = $this->Session->read('pm_demand_infos');
			//$this->Session->delete('pm_demand_infos');
		}elseif($this->request->is('get')){
			// 初回データ格納
			$data = $this->__get_prog_demand_infos($pc['ProgCorp']['id']);

// 			//追加案件データ格納
// 			$options = array(
// 					'conditions' => array('ProgAddDemandInfo.prog_corp_id' => $pc['ProgCorp']['id']),
// 					'order' => array('ProgAddDemandInfo.id'),
// 			);
// 			$add_demand_infos = $this->ProgAddDemandInfo->find('all', $options);
// 			foreach($add_demand_infos as $add_demand_info){
// 				$data['ProgDemandInfoOther']['add_flg'] = 1;
// 				$add_demand_info['ProgAddDemandInfo']['display'] = 1;
// 				$data['ProgAddDemandInfo'][] = $add_demand_info['ProgAddDemandInfo'];
//
// 			}

			if($data)$this->request->data = $data;
		}
// murata.s ORANGE-384 CHG(E)
//pr($pc);
		$pi = $this->ProgItem->read(null, 1);
		$pagetitle = $corpinfo['MCorp']['official_corp_name'].'様 案件一覧';

		$this->set(compact('pagetitle', 'corpinfo', 'pi'));
	}

	public function update_confirm($prog_import_file_id = null){
		$this->layout = 'progress_management';

		$corp_id = $this->_user['affiliation_id'];
		$corpinfo = $this->MCorp->read(null, $corp_id);

		if($this->request->is('post') || $this->request->is('put')){
			//pr($this->request->data);
			if(isset($this->request->data['back'])){
				//$this->Session->write('pm_demand_infos', $this->request->data);
				$this->redirect(array('action' => 'demand_detail', $this->request->data['ProgImportFile']['file_id']));

			}elseif(isset($this->request->data['update'])){
				$save_flg = true;
				$mail_address = '';

				$options = array(
						'conditions' => array('ProgCorp.corp_id' => $corp_id, 'ProgCorp.prog_import_file_id' => $this->data['ProgImportFile']['file_id']),
						'order' => array('id' => 'desc'),
				);
				$pm_corp = $this->ProgCorp->find('first', $options);

				try{
					//トランザクション開始
					$this->ProgDemandInfo->begin();

					//ID正常性確認
					for($i=0;$i < count($this->request->data['ProgDemandInfo']);$i++){

						$cnt = $this->ProgDemandInfo->find('count',array(
								'conditions' => array(
										'ProgDemandInfo.id' => $this->request->data['ProgDemandInfo'][$i]['id'],
										'ProgDemandInfo.demand_id' => $this->request->data['ProgDemandInfo'][$i]['demand_id'],
										'ProgDemandInfo.commission_id' => $this->request->data['ProgDemandInfo'][$i]['commission_id'],
										'ProgDemandInfo.corp_id' => $corp_id,
										'ProgDemandInfo.prog_import_file_id' => $this->request->data['ProgImportFile']['file_id']
								)));

						if($cnt == 0){
							$this->log('Corp_id: '.$corp_id.' 進捗内容不備エラー', PROG_IMPORT_LOG);
							throw new ApplicationException(__('NoReferenceAuthority', true));
						}

						if(!empty($_SERVER['X-ClientIP']))$this->request->data['ProgDemandInfo'][$i]['ip_address_update'] = $_SERVER['X-ClientIP'];
						elseif(!empty($_SERVER['REMOTE_ADDR']))$this->request->data['ProgDemandInfo'][$i]['ip_address_update'] = $_SERVER['REMOTE_ADDR'];
						$this->request->data['ProgDemandInfo'][$i]['user_agent_update'] = substr($_SERVER['HTTP_USER_AGENT'], 0, 255);

					}

					if(!empty($pm_corp['ProgCorp']['mail_address']))$mail_address = $pm_corp['ProgCorp']['mail_address'];
					/*****案件追加・更新****************************************/

					if(!$this->ProgDemandInfo->saveAll($this->request->data['ProgDemandInfo'], array('validate' => 'only'))){
						$this->log('Corp_id: '.$corp_id.' 最終登録エラー', PROG_IMPORT_LOG);
						throw new ApplicationException(__('NoReferenceAuthority', true));
					}

					if($this->ProgDemandInfo->saveAll($this->request->data['ProgDemandInfo'], array('atomic' => false))){
						//削除対象の判定用
						$modified = date('Y-m-d H:i:s');
						if(!empty($this->request->data['ProgAddDemandInfo'])){
							//追加案件登録処理
							$save_add_data =$this->ProgAddDemandInfo->addValidateAll($this->request->data['ProgAddDemandInfo']);

							if($save_add_data){
								for($i=0;$i < count($save_add_data);$i++){

									$save_add_data[$i]['prog_corp_id'] = $pm_corp['ProgCorp']['id'];
									$save_add_data[$i]['prog_import_file_id'] = $this->data['ProgImportFile']['file_id'];
									$save_add_data[$i]['corp_id'] = $corp_id;
									if(!empty($_SERVER['X-ClientIP']))$save_add_data[$i]['ip_address_update'] = $_SERVER['X-ClientIP'];
									elseif(!empty($_SERVER['REMOTE_ADDR']))$save_add_data[$i]['ip_address_update'] = $_SERVER['REMOTE_ADDR'];
									$save_add_data[$i]['user_agent_update'] = substr($_SERVER['HTTP_USER_AGENT'], 0, 255);
								}
								if(!$this->ProgAddDemandInfo->saveAll($save_add_data, array('atomic' => false))){
									$save_flg = false;
								}
							}

						}

						//追加案件削除処理
						if($save_flg && !empty($this->request->data['ProgAddDemandInfo']))$save_flg = $this->ProgAddDemandInfo->DeleteById($this->request->data['ProgAddDemandInfo'], $pm_corp['ProgCorp']['id'], $this->data['ProgImportFile']['file_id'], $modified);
					}else{
						$save_flg = false;
					}

					if($save_flg)$save_flg = $this->__update_prog_corp($pm_corp['ProgCorp']['id']);

					//追加処理に失敗していれば、トランザクションRoolback
					if(!$save_flg)$this->ProgDemandInfo->rollback();
					else $this->ProgDemandInfo->commit();

					/*****案件追加・更新****************************************/

				}catch(Exception $e){
					//トランザクションRoolback
					$this->ProgDemandInfo->rollback();
					$save_flg = false;
					$this->log($e->getMessage(), PROG_IMPORT_LOG);
					throw new ApplicationException(__('NoReferenceAuthority', true));
				}

				/*****ログ追加****************************************/
				if($save_flg){

					$this->__insert_demand_info_log_and_update_commission($corp_id, $this->data['ProgImportFile']['file_id']);
					$this->__insert_add_demand_info_log($corp_id, $this->data['ProgImportFile']['file_id']);

					//メール送信
					if(!empty($mail_address)){
						$address = explode(';', $mail_address);

						$list = array();
						if(!empty($this->request->data['ProgDemandInfo']))$list = $this->request->data['ProgDemandInfo'];
						$add_list = array();
						if(!empty($this->request->data['ProgAddDemandInfo']))$add_list = $this->request->data['ProgAddDemandInfo'];

						//メールアドレスが複数ある場合の対応
						foreach ($address as $item){
							$this->__mail_send($item, $corp_id, $list, $add_list);
						}
					}

					$this->__delete_tmp($pm_corp['ProgCorp']['id']);
					//if($this->Session->check('pm_demand_infos')){
					//	$this->Session->delete('pm_demand_infos');
					//}

				}
				/*****ログ追加****************************************/
				$this->redirect(array('action' => 'update_end'));

			}
		}else{

			$options = array(
					'conditions' => array('ProgCorp.corp_id' => $corp_id, 'ProgCorp.prog_import_file_id' => $prog_import_file_id),
					'order' => array('id' => 'desc'),
			);
			$pc = $this->ProgCorp->find('first', $options);

			$this->request->data['ProgDemandInfo'] = $this->__get_tmp($pc['ProgCorp']['id']);
			//Confirmからの戻り
			if($this->request->data['ProgDemandInfo']){
			//if($this->Session->check('pm_demand_infos')){
				$this->request->data['ProgAddDemandInfo'] = $this->__get_tmp($pc['ProgCorp']['id'], 'add_demand_info');
				$this->request->data['ProgImportFile'] = $this->__get_tmp($pc['ProgCorp']['id'], 'file');
				$this->request->data['ProgDemandInfoOther'] = $this->__get_tmp($pc['ProgCorp']['id'], 'other');
				//表示用データの読み込み
				//$this->request->data = $this->Session->read('pm_demand_infos');
				//$this->Session->delete('pm_demand_infos');
			}else{
				throw new ApplicationException(__('NoReferenceAuthority', true));
			}
		}
//$this->log($this->request->data, PROG_IMPORT_LOG);
		$pagetitle = $corpinfo['MCorp']['official_corp_name'].'様 入力確認';
		$this->set(compact('pagetitle'));
	}

	/**
	 * 案件ログ追加
	 * @param unknown $corp_id
	 * @param unknown $file_id
	 */
	private function __insert_demand_info_log_and_update_commission($corp_id = null, $file_id = null, $type = 'corp'){
		if(empty($corp_id) || empty($file_id))return;

		$options = array(
				'conditions' => array(
						'ProgDemandInfo.corp_id' => $corp_id,
						'ProgDemandInfo.prog_import_file_id' => $file_id
				),
				'order' => array('ProgDemandInfo.id'),
		);
		//進捗管理案件ログ登録
		$demand_infos = $this->ProgDemandInfo->find('all', $options);
		//保存用にリスト形式の変更
		$save_data = array();
		if($demand_infos){
			foreach($demand_infos as $data){
				$save_data[] = $data['ProgDemandInfo'];
			}
		}

// murata.s ORANGE-412 CHG(S)
		$ableUpdateCommission = false;
		if($type == 'corp'){
			// 最新のprog_file_import_file_idの場合のみ取次データを更新
			$current_file_id = $this->ProgImportFile->find('first', array(
					'conditions' => array(
							'delete_flag' => 0,
							'release_flag' => 1
					),
					'order' => array('id' => 'desc')
			));
			if($current_file_id['ProgImportFile']['id'] == $file_id){
				$ableUpdateCommission = true;
			}
		}else{
			$ableUpdateCommission = true;
		}
		if($ableUpdateCommission) {
			//取次データの更新
			$this->__update_commission_infos($save_data, $type);
		}
		$this->__insert_log($save_data);
// murata.s ORANGE-412 CHG(E)

	}

	/**
	 * 追加案件ログ追加
	 * @param unknown $corp_id
	 * @param unknown $file_id
	 */
	private function __insert_add_demand_info_log($corp_id = null, $file_id = null){
		if(empty($corp_id) || empty($file_id))return;

		$options = array(
				'conditions' => array(
						'ProgAddDemandInfo.corp_id' => $corp_id,
						'ProgAddDemandInfo.prog_import_file_id' => $file_id
				),
				'order' => array('ProgAddDemandInfo.id'),
		);
		//進捗管理追加案件ログ登録
		$add_demand_infos = $this->ProgAddDemandInfo->find('all', $options);
		//保存用にリスト形式の変更
		$add_save_data = array();
		if(!empty($add_demand_infos[0]['ProgAddDemandInfo'])){
			foreach($add_demand_infos as $data){
				$add_save_data[] = $data['ProgAddDemandInfo'];
			}
			$this->__insert_log($add_save_data, 'add_demand_info');
		}


	}

	public function update_end(){
		$this->layout = 'progress_management';

		$corp_id = $this->_user['affiliation_id'];
		$corpinfo = $this->MCorp->read(null, $corp_id);

		$pagetitle = $corpinfo['MCorp']['official_corp_name'].'様 送信完了';
		$this->set(compact('pagetitle'));
	}

	/**
	 * FAX送信
	 * 複数送信可能にする
	 */
	private function __send_admin_corp_fax($prog_corp = array()){

		mb_language('Ja');
		mb_internal_encoding('UTF-8');

		$filename = $this->output_corp_pdf($prog_corp['id'], 'F');

		$from_address = PM_ADMIN_MAIL_FROM;
		$to_address = PM_FAX_MAIL_TO;
		$subject = "000020003";

		//ORANGE-231 CHG S
		$rtn = true;
		$address = explode(';', $prog_corp['fax']);

		foreach($address as $item){

			$data = array();
			$data['filebase'] = basename($filename);
			$data['file_contents'] = chunk_split(base64_encode(file_get_contents($filename)));
			$data['boundary'] = '__BOUNDARY__'.md5(rand());
			$data['fax'] = $item;
			$data['corpname'] = $prog_corp['official_corp_name'];

			$header = "Content-Type: multipart/mixed;boundary=".$data['boundary']."\n";
			$header .= "From: ".$from_address."\n";
			$header .= "Bcc: ".PM_ADMIN_MAIL_TO;

			$View = new View();
			$View->viewPath = 'ProgressManagement'; // Viewの下のフォルダ名
			$View->viewVars['data'] = $data; //パラメータ
			$body = $View->render('fax_body', false);

			if(ini_get('safe_mode')){
				if(!mb_send_mail($to_address, $subject, $body, $header)){
					$rtn = false;
					$msg = "FAX送信失敗：progcorp_notice \n".$item." \n".$subject;
					$this->log($msg ,PROG_IMPORT_LOG);
					$this->CustomEmail->simple_send('ERROR: '.$subject, $msg, PM_ADMIN_MAIL_TO, PM_ADMIN_MAIL_FROM);
				}
			}else{
				if(!mb_send_mail($to_address, $subject, $body, $header, "-f " . $from_address)){
					$rtn = false;
					$msg = "FAX送信失敗：progcorp_notice \n".$item." \n".$subject;
					$this->log($msg ,PROG_IMPORT_LOG);
					$this->CustomEmail->simple_send('ERROR: '.$subject, $msg, PM_ADMIN_MAIL_TO, PM_ADMIN_MAIL_FROM);
				}
			}
		}

		return $rtn;
		//ORANGE-231 CHG E

	}

	/**
	 * メール送信
	 * 複数送信可能にする
	 */
	private function __send_admin_corp_mail($mcorp = null, $file_id = null, $date = null){

		$template = 'progcorp_notice';
		$to_address_admin = PM_ADMIN_MAIL_TO;
		$from_address = PM_ADMIN_MAIL_FROM;
		$to_address_corp = $mcorp['mail_address'];
		$admin_subject = PM_SUBJECT_PREFIX."■[管理側：{".$mcorp['official_corp_name']."}御中]<URL確認用メール>";
		$subject = PM_SUBJECT_PREFIX."[".$mcorp['official_corp_name']."御中] <シェアリングテクノロジー株式会社>より進捗管理 報告ご依頼のお知らせ";

		$data = array();
		$data['url'] = PM_NOTICE_URL.$file_id;
		$data['date'] = $date;

		//加盟店
		$corp_send_flg = $this->CustomEmail->send($subject, $template, $data, $to_address_corp, null, $from_address);

		//管理者
		$admin_send_flg = $this->CustomEmail->send($admin_subject, $template, $data, $to_address_admin, null, $from_address);

		if(!$corp_send_flg){
			$msg = "メール送信失敗：progcorp_notice \n".$to_address_corp." \n".$subject;
			$this->log($msg ,PROG_IMPORT_LOG);
			// ORANGE-231 ADD S
			$this->CustomEmail->simple_send('ERROR: '.$subject, $msg, $to_address_admin, $from_address);
			// ORANGE-231 ADD E
		}

		return $corp_send_flg;
	}

	/**
	 * メール送信
	 * @param unknown $corp_id
	 * @param unknown $demand_infos
	 * @param unknown $add_demand_infos
	 */
	private function __mail_send($address = null, $corp_id = null, $demand_infos = null, $add_demand_infos = null){

		$diff_list = Configure::read("PM_DIFF_LIST");
		$commission_status_list = Configure::read("PM_COMMISSION_STATUS");
		$commission_order_fail_reason_list = Util::getDropList(__('commission_order_fail_reason', true));

		$mcorp = $this->MCorp->read(null, $corp_id);
		$template = 'progcorp_update';
		$to_address_admin = PM_ADMIN_MAIL_TO;
		$from_address = PM_ADMIN_MAIL_FROM;
		$to_address_corp = $address;

		$data = array();
		$data['official_corp_name'] = $mcorp['MCorp']['official_corp_name'];

		$admin_subject = PM_SUBJECT_PREFIX."■[管理側：{".$data['official_corp_name']."御中]<進捗管理入力内容メール>";
		$subject = PM_SUBJECT_PREFIX."[".$data['official_corp_name']."御中] ご入力誠にありがとうございました。[進捗管理入力内容確認メール]";

		$list = '';
		if(!empty($demand_infos)){
			foreach($demand_infos as $demand_info){
				$list .= "案件番号              => ".$demand_info['demand_id']."\n";
				$list .= "加盟店様ID            => ".$corp_id."\n";
				$list .= "お客様名              => ".$demand_info['customer_name']."\n";

				$fee = '';
				if(!empty($demand_info['fee']))$fee = number_format($demand_info['fee']).'円';
				else if(!empty($demand_info['fee_rate']))$fee = $demand_info['fee_rate'].'%';
				$list .= "手数料率（手数料金額）=> ".$fee."\n";

				$fee_target_price = '';
				if(!empty($demand_info['fee_target_price']))$fee_target_price = number_format($demand_info['fee_target_price']).'円';
				$list .= "手数料対象金額        => ".$fee_target_price."\n";
				$list .= "情報相違              => ".$diff_list[$demand_info['diff_flg']]."\n";

				$commission_status_update = '';
				//ORANGE-242 ADD S
				if(!empty($demand_info['commission_status']))$commission_status_update = $commission_status_list[$demand_info['commission_status']];
				//ORANGE-242 ADD E
				if(!empty($demand_info['commission_status_update']))$commission_status_update = $commission_status_list[$demand_info['commission_status_update']];
				$list .= "変更後の状況          => ".$commission_status_update."\n";
				//ORANGE-242 CHG S
				$complete_date_update = '';
				if(!empty($demand_info['complete_date']))$complete_date_update = date('Y/m/d', strtotime($demand_info['complete_date']));
				if(!empty($demand_info['complete_date_update']))$complete_date_update = date('Y/m/d', strtotime($demand_info['complete_date_update']));
				$list .= "施工完了日            => ".$complete_date_update."\n";
				//ORANGE-242 CHG E
				$construction_price_tax_exclude_update = '-円';
				$construction_price_tax_include_update = '-円';
				if(!empty($demand_info['construction_price_tax_exclude']))$construction_price_tax_exclude_update = number_format($demand_info['construction_price_tax_exclude']).'円';
				if(!empty($demand_info['construction_price_tax_include']))$construction_price_tax_include_update = number_format($demand_info['construction_price_tax_include']).'円';
				if(!empty($demand_info['construction_price_tax_exclude_update']))$construction_price_tax_exclude_update = number_format($demand_info['construction_price_tax_exclude_update']).'円';
				if(!empty($demand_info['construction_price_tax_include_update']))$construction_price_tax_include_update = number_format($demand_info['construction_price_tax_include_update']).'円';
				$list .= "施工金額(税別)        => ".$construction_price_tax_exclude_update."\n";
				$list .= "施工金額(税込)        => ".$construction_price_tax_include_update."\n";

				$list .= "備考欄                => ".$demand_info['comment_update']."\n";
				$list .= "===========================================================\n";
			}
		}

		$add_list = '';
		if(!empty($add_demand_infos)){
			foreach($add_demand_infos as $add_demand_info){
				$demand_id = '';
				if(!empty($add_demand_info['demand_id_update']))$demand_id = $add_demand_info['demand_id_update'];
				$add_list .= "案件番号          => ".$demand_id."\n";

				$add_list .= "加盟店様ID        => ".$corp_id."\n";

				$customer_name = '';
				if(!empty($add_demand_info['customer_name_update']))$customer_name = $add_demand_info['customer_name_update'];
				$add_list .= "お客様名          => ".$customer_name."\n";

				//ORANGE-242 CHG S
				$complete_date_update = '';
				if(!empty($add_demand_info['complete_date_update']))$complete_date_update = date('Y/m/d', strtotime($add_demand_info['complete_date_update']));
				$add_list .= "施工完了日        => ".$complete_date_update."\n";

				$construction_price_tax_exclude_update = '';
				if(!empty($add_demand_info['construction_price_tax_exclude_update']))$construction_price_tax_exclude_update = number_format($add_demand_info['construction_price_tax_exclude_update']).'円';
				$add_list .= "施工金額(税別)    => ".$construction_price_tax_exclude_update."\n";

				$comment_update = '';
				if(!empty($add_demand_info['comment_update']))$comment_update = $add_demand_info['comment_update'];
				$add_list .= "備考欄            => ".$comment_update."\n";
				$add_list .= "===========================================================\n";
				//ORANGE-242 CHG E
			}
		}

		$data['list'] = $list;
		$data['add_list'] = $add_list;

		//加盟店
		$corp_send_flg = $this->CustomEmail->send($subject, $template, $data, $to_address_corp, null, $from_address);

		//管理者
		$admin_send_flg = $this->CustomEmail->send($admin_subject, $template, $data, $to_address_admin, null, $from_address);

		//メール送信ログ出力
		if(!$corp_send_flg){
			// ORANGE-231 ADD S
			$msg = 'MailSend: Failure subject:'.$subject."\n to:".$to_address_corp;
			$this->log($msg, PROG_IMPORT_LOG);
			$this->CustomEmail->simple_send('ERROR: '.$subject, $msg, $to_address_admin, $from_address);
			// ORANGE-231 ADD E
		}
		if(!$admin_send_flg)$this->log('MailSend: Failure subject:'.$admin_subject."\n toadmin:".$to_address_corp,PROG_IMPORT_LOG);
	}

	/**
	 * 変更履歴取得
	 */
	private function __get_comment($demand_info = null){

		$result = "\r\n";

		$result .= "■取次状況：";
		// 変更前状況（csv_data_conditionが空の時はインポート時のcommission_statusを返す）
		if($demand_info['commission_status'] == 1){
			$result .= "進行中⇒";
		}else if($demand_info['commission_status'] == 2){
			$result .= "受注⇒";
		}else if($demand_info['commission_status'] == 3){
			$result .= "施工完了⇒";
		}else if($demand_info['commission_status'] == 4){
			$result .= "失注⇒";
		}
		// 変更後状況
		if($demand_info['commission_status_update'] == 0){
			if($demand_info['commission_status'] == 1){
				$result .= "進行中\r\n";
			}else if($demand_info['commission_status'] == 2){
				$result .= "受注\r\n";
			}else if($demand_info['commission_status'] == 3){
				$result .= "施工完了\r\n";
			}else if($demand_info['commission_status'] == 4){
				$result .= "失注\r\n";
			}
		}else if($demand_info['commission_status_update'] == 1){
			$result .= "進行中\r\n";
		}else if($demand_info['commission_status_update'] == 2){
			$result .= "受注\r\n";
		}else if($demand_info['commission_status_update'] == 3){
			$result .= "施工完了\r\n";
		}else if($demand_info['commission_status_update'] == 4){
			$result .= "失注\r\n";
		}

		// 変更あり・なし
		$result .= "■変更：";
		if($demand_info['diff_flg'] == 2){
			//変更なしの場合は以下の処理を行わない
			$result .= "変更なし\r\n";
			return $result;
		}else if($demand_info['diff_flg']== 3){
			$result .= "変更あり\r\n";
		}

		// 施工完了日
		if($demand_info['commission_status_update'] == 3){
			$result .= "■施工完了日：";
			$result .= (!empty($demand_info['complete_date'])) ? $demand_info['complete_date'] : "なし";
			$result .= "⇒";
			$result .= (!empty($demand_info['complete_date_update'])) ? $demand_info['complete_date_update'] : "なし";
			$result .= "\r\n";
			$result .= "■施工金額：";
			$result .= (!empty($demand_info['construction_price_tax_exclude'])) ? $demand_info['construction_price_tax_exclude'] : "なし";
			$result .= "⇒";
			$result .= (!empty($demand_info['construction_price_tax_exclude_update'])) ? $demand_info['construction_price_tax_exclude_update'] : "なし";
		}
		// 失注日
		else if($demand_info['commission_status_update'] == 4){
			$result .= "■失注日：";
			$result .= (!empty($demand_info['complete_date'])) ? $demand_info['complete_date'] : "なし";
			$result .= "⇒";
			$result .= (!empty($demand_info['complete_date_update'])) ? $demand_info['complete_date_update'] : "なし";
		}

		return $result;
	}

	/**
	 * 取次情報の更新
	 * @param unknown $data
	 */
	private function __update_commission_infos($data=null, $type = 'corp'){

		$now = date('Y-m-d H:i:s');

		if(empty($data)) return null;

		foreach($data as $item){
			try{

				$order_fail_date = null;
				$complete_date = null;
				$order_fail_reason = 0;
				$diff_flg = 1;
				$commission_status = 0;
				$construction_price_tax_exclude = 0;
				if(!empty($item['construction_price_tax_exclude']))$construction_price_tax_exclude = $item['construction_price_tax_exclude'];

				//ORANGE-218 CHG S
				if(!empty($item['commission_status']))$commission_status = $item['commission_status'];

				//施工完了
				if($commission_status == 3){
					if(!empty($item['complete_date']))$complete_date = $item['complete_date'];
				}
				//失注
				else if($commission_status == 4){
					if(!empty($item['complete_date']))$order_fail_date = $item['complete_date'];
					if(!empty($item['commission_order_fail_reason']))$order_fail_reason = $item['commission_order_fail_reason'];
				}

				//変更あり
				if($item['diff_flg'] == 3){
					//取次状態をセット
					$commission_status = $item['commission_status_update'];

					//施工完了
					if ($commission_status == 3){
						$complete_date = $item['complete_date_update'];
						// 施工金額
						if(!empty($item['construction_price_tax_exclude_update']))$construction_price_tax_exclude = $item['construction_price_tax_exclude_update'];
					}
					//失注
					else if ($commission_status == 4){
						$order_fail_date = $item['complete_date_update'];
						$order_fail_reason = $item['commission_order_fail_reason_update'];
					}
				}
				//ORANGE-218 CHG S

				// 進捗表回収日
				$collect_date = substr($now, 0, 16);

				if(!empty($item['commission_id'])) {

					// 1日から7営業日の24時以降の送信に関してはオレンジ側に反映しないようにする変更（土日以外を営業日とする）
					$eigyoDate = 0;
					if($type == 'corp'){
						for($i=1;$i<=date("d");$i++){
							$week_str_list = array( '日', '月', '火', '水', '木', '金', '土');
							$datetime = new DateTime();
							$datetime->setDate(date("Y"), date("m"), $i);
							$w = (int)$datetime->format('w');
							if($w != 6 && $w != 0){
								$eigyoDate++;
							}
						}
					}

					$db = ConnectionManager::getDataSource('default');

					if($eigyoDate < 8 || !PM_RELEASE){

						//ORANGE-358 ADD S
						$options = array('conditions' => array('CommissionInfo.id' => $item['commission_id']));
						$old = $this->CommissionInfo->find('first', $options);

						if($old['CommissionInfo']['commission_status'] == 1 && $commission_status == 4 && !empty($old['CommissionInfo']['re_commission_exclusion_status']) && $old['CommissionInfo']['re_commission_exclusion_status'] == 2 ){
							$save_data['CommissionInfo']['id'] = $item['commission_id'];
							$save_data['CommissionInfo']['re_commission_exclusion_status'] = 0;
							$this->CommissionInfo->save($save_data);
						}
						//ORANGE-358 ADD E

						$update_sql = "SELECT set_commission(".
								"'".$item['demand_id']."',". // 案件ID
								"'".$item['commission_id']."',". // 取次ID
								"'".$commission_status."',". // 取次状況
								"'".$complete_date."',". // 施工完了日
								"'".$order_fail_date."',". // 失注日
								"'".$order_fail_reason."',". // 失注理由
								"'".$construction_price_tax_exclude."',". // 施工金額
								"'".$collect_date."',". //進捗表回収日
								"'".$item['comment_update'].$this->__get_comment($item)."',".	// 対応履歴
								"'".$diff_flg."');";


						$result = $db->query($update_sql);
						//$this->log($result, LOG_ERR);
					}

					$db->query("SELECT set_lock_status(".$item['commission_id'].", 0);");

				}
			}catch(Exception $e){
				$this->log($e->getMessage(), PROG_IMPORT_LOG);
				// ORANGE-231 ADD S
				$this->CustomEmail->simple_send('ERROR: 【進捗システム】取次情報 更新失敗', $e->getMessage(), PM_ADMIN_MAIL_TO, PM_ADMIN_MAIL_FROM);
				// ORANGE-231 ADD E
				//エラーSQLの記録
				if(!empty($update_sql))$this->log($update_sql, PROG_IMPORT_LOG);
			}
		}
	}

	/**
	 * ログ追加
	 * @param string $type
	 * @param unknown $data
	 */
	private function __insert_log($data = null, $type = 'demand_info'){
		$rtn = false;
		try{
			$this->ProgDemandInfoLog->begin();
			//不要項目の削除
			foreach($data as &$item){
				unset($item['created']);
				unset($item['created_user_id']);
				unset($item['modified']);
				unset($item['modified_user_id']);
				if($type == 'demand_info'){
					$item['prog_demand_info_id'] = $item['id'];
					$item['comment_update'] = $item['comment_update'].$this->__get_comment($item);
				}else if($type == 'add_demand_info'){
					$item['prog_add_demand_info_id'] = $item['id'];
				}
				unset($item['id']);
			}

			if($type == 'demand_info'){
				$rtn = $this->ProgDemandInfoLog->saveAll($data, array('validate' => false, 'atomic' => false));
			}else if($type == 'add_demand_info'){
				$rtn = $this->ProgAddDemandInfoLog->saveAll($data, array('validate' => false, 'atomic' => false));
			}
		}catch(Exception $e){
			$this->log($e->getMessage(), PROG_IMPORT_LOG);
			$this->ProgDemandInfoLog->rollback();
			return false;
		}
		if(!$rtn){
			$this->ProgDemandInfoLog->rollback();
			return false;
		}

		$this->ProgDemandInfoLog->commit();

	}

	//ORANGE-270 ADD S
	/**
	 * 進捗管理企業更新
	 * @param unknown $data
	 */
	private function __update_prog_corp($id = null){
		if(!$this->ProgCorp->exists($id))return false;

		$this->ProgCorp->read(null, $id);
		// 進捗表状況
		$save_data['ProgCorp']['progress_flag'] = 7;
		$save_data['ProgCorp']['collect_date'] = date("Y-m-d H:i:s");

		// 16日以降だったら返送回数カウントアップ
		if(date('d') >= 16){
			$save_data['ProgCorp']['rev_mail_count'] = (int)$this->ProgCorp->read('rev_mail_count') + 1;
		}
		if( $this->ProgCorp->save($save_data) ){
			return true;
		}else{
			return false;
		}

	}
	//ORANGE-270 ADD E

	/**
	 * インポートファイル削除確認
	 */
	private function __check_import_file_delete($id){
		$options = array('conditions' => array(
				'ProgImportFile.id' => $id, 'ProgImportFile.delete_flag' => 0
		));

		return $this->ProgImportFile->find('count', $options);
	}

	/**
	 * 再取得
	 */
	private function __reacquisition($id){

		if(!$this->ProgDemandInfo->exists($id))return false;

		$base_info = $this->ProgDemandInfo->read(null, $id);

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
				commission_infos.id = ".$base_info['ProgDemandInfo']['commission_id']."
		  ";
		$ci =  $this->CommissionInfo->query($sql);
		//ORANGE-218 CHG E
// 2017.01.04 murata.s ORANGE-244 CHG(E)

		if(!$ci)return false;

		$rows = $ci[0][0];

		$base_info['ProgDemandInfo']['corp_id'] = $rows['corp_id'];
		$base_info['ProgDemandInfo']['demand_id'] = $rows['demand_infos_id'];
		$base_info['ProgDemandInfo']['genre_name'] = $rows['genre_name'];
		$base_info['ProgDemandInfo']['category_name'] = $rows['category_name'];
		$base_info['ProgDemandInfo']['customer_name'] = $rows['customer_name'];
		$base_info['ProgDemandInfo']['receive_datetime'] = $rows['receive_datetime'];
		$base_info['ProgDemandInfo']['commission_status'] = $rows['commission_status'];
		$base_info['ProgDemandInfo']['commission_order_fail_reason'] = $rows['commission_order_fail_reason'];
		if($rows['commission_status'] == 3){
			$base_info['ProgDemandInfo']['complete_date'] = $rows['complete_date'];
		}else if($rows['commission_status'] == 4){
			$base_info['ProgDemandInfo']['complete_date'] = $rows['order_fail_date'];
		}
		$base_info['ProgDemandInfo']['construction_price_tax_exclude'] = $rows['construction_price_tax_exclude'];
		$base_info['ProgDemandInfo']['construction_price_tax_include'] = $rows['construction_price_tax_include'];
		$base_info['ProgDemandInfo']['commission_id'] = $rows['commission_infos_id'];

		//2016-10-18 iwai 料金の初期化
		$base_info['ProgDemandInfo']['fee'] = null;
		$base_info['ProgDemandInfo']['fee_rate'] = null;

		// 取次手数料単価が円の場合
		if($rows['order_fee_unit'] == "0"){
			if($rows['irregular_fee'] != "" && $rows['irregular_fee'] != 0){
				// イレギュラー手数料金額（税抜）【単位：円】
				$base_info['ProgDemandInfo']['fee'] = $rows['irregular_fee'];
			}else{
				// 取次先手数料【単位：円】
				$base_info['ProgDemandInfo']['fee'] = $rows['corp_fee'];
			}
			// 取次手数料単価が％の場合
		}else if($rows['order_fee_unit'] == "1"){
			if($rows['irregular_fee'] != "" && $rows['irregular_fee'] != 0){
				// イレギュラー手数料金額（税抜）【単位：円】
				$base_info['ProgDemandInfo']['fee'] = $rows['irregular_fee'];
			}else if($rows['irregular_fee_rate'] != "" && $rows['irregular_fee_rate'] != 0){
				// イレギュラー手数料率【単位：％】
				$base_info['ProgDemandInfo']['fee_rate'] = $rows['irregular_fee_rate'];
			}else{
				// 取次時手数料率【単位：％】
				$base_info['ProgDemandInfo']['fee_rate'] = $rows['commission_fee_rate'];
			}
		}else if($rows['order_fee_unit'] == NULL){
			// カテゴリIDが紐づいてない場合
			if($rows['irregular_fee'] != "" && $rows['irregular_fee'] != 0){
				// イレギュラー手数料金額（税抜）【単位：円】
				$base_info['ProgDemandInfo']['fee'] = $rows['irregular_fee'];
			}else if($rows['irregular_fee_rate'] != "" && $rows['irregular_fee_rate'] != 0){
				$base_info['ProgDemandInfo']['fee_rate'] = $rows['irregular_fee_rate'];
			}else if($rows['commission_fee_rate'] != "" && $rows['commission_fee_rate'] != 0){
				$base_info['ProgDemandInfo']['fee_rate'] = $rows['commission_fee_rate'];
			}else if($rows['corp_fee'] != "" && $rows['corp_fee'] != 0){
				$base_info['ProgDemandInfo']['fee'] = $rows['corp_fee'];
			}
		}

		// 手数料対象金額
		$base_info['ProgDemandInfo']['fee_target_price'] = $rows['fee_target_price'];
		$base_info['ProgDemandInfo']['fee_billing_date'] = $rows['fee_billing_date'];

		try{
			if($this->ProgDemandInfo->save($base_info, false)){
				return true;
			}else{
				return false;
			}
		}catch(Exception $e){
			$this->log($e->getMessage(), PROG_IMPORT_LOG);
			return false;
		}

	}

	/**
	 * 一時テーブルのデフォルトデータ再取得
	 * @param unknown $id
	 */
	private function __reacquisition_tmp_data($id){
		try{
			$options = array('conditions' => array('ProgDemandInfoTmp.prog_demand_info_id' => $id));
			$save_data = $this->ProgDemandInfoTmp->find('first', $options);

			if($this->ProgDemandInfo->exists($id) && $save_data){
				$org = $this->ProgDemandInfo->findById($id);

				$save_data['ProgDemandInfoTmp']['corp_id'] = $org['ProgDemandInfo']['corp_id'];
				$save_data['ProgDemandInfoTmp']['commission_id'] = $org['ProgDemandInfo']['commission_id'];
				$save_data['ProgDemandInfoTmp']['demand_id'] = $org['ProgDemandInfo']['demand_id'];
				$save_data['ProgDemandInfoTmp']['fee_billing_date'] = $org['ProgDemandInfo']['fee_billing_date'];
				$save_data['ProgDemandInfoTmp']['genre_name'] = $org['ProgDemandInfo']['genre_name'];
				$save_data['ProgDemandInfoTmp']['receive_datetime'] = $org['ProgDemandInfo']['receive_datetime'];
				$save_data['ProgDemandInfoTmp']['category_name'] = $org['ProgDemandInfo']['category_name'];
				$save_data['ProgDemandInfoTmp']['customer_name'] = $org['ProgDemandInfo']['customer_name'];
				$save_data['ProgDemandInfoTmp']['fee'] = $org['ProgDemandInfo']['fee'];
				$save_data['ProgDemandInfoTmp']['fee_rate'] = $org['ProgDemandInfo']['fee_rate'];
				$save_data['ProgDemandInfoTmp']['fee_target_price'] = $org['ProgDemandInfo']['fee_target_price'];
				$save_data['ProgDemandInfoTmp']['commission_status'] = $org['ProgDemandInfo']['commission_status'];
				$save_data['ProgDemandInfoTmp']['commission_order_fail_reason'] = $org['ProgDemandInfo']['commission_order_fail_reason'];
				$save_data['ProgDemandInfoTmp']['complete_date'] = $org['ProgDemandInfo']['complete_date'];
				$save_data['ProgDemandInfoTmp']['construction_price_tax_exclude'] = $org['ProgDemandInfo']['construction_price_tax_exclude'];
				$save_data['ProgDemandInfoTmp']['construction_price_tax_include'] = $org['ProgDemandInfo']['construction_price_tax_include'];

				if($this->ProgDemandInfoTmp->save($save_data, false)){
					return true;
				}else{
					return false;
				}
			}
		}catch(Exception $e){
			$this->log($e->getMessage(), PROG_IMPORT_LOG);
			return false;
		}
	}

	/**
	 * CSV出力データの生成
	 * @param unknown $prog_corp_id
	 */
	private function __get_csv_data($id = null, $type = 'corp'){

		if($type == 'corp')$conditions = array('ProgCorp.id' => $id);
		else if($type == 'file')$conditions = array('ProgCorp.prog_import_file_id' => $id);

		//ORANGE-218 CHG S
		$fields = array('MCorp.corp_name', 'MCorp.official_corp_name', 'ProgDemandInfo.corp_id', 'MCorp.commission_dial', 'ProgCorp.contact_type', 'ProgCorp.unit_cost', 'ProgCorp.progress_flag', 'ProgDemandInfo.demand_id',
				'ProgDemandInfo.commission_id', 'ProgDemandInfo.receive_datetime', 'ProgDemandInfo.customer_name', 'ProgDemandInfo.category_name', 'ProgDemandInfo.fee', 'ProgDemandInfo.fee_rate', 'ProgDemandInfo.complete_date',
				'ProgDemandInfo.construction_price_tax_exclude', 'ProgDemandInfo.commission_status', 'ProgDemandInfo.diff_flg', 'ProgDemandInfo.commission_status_update', 'ProgDemandInfo.complete_date_update',
				'ProgDemandInfo.construction_price_tax_exclude_update', 'ProgDemandInfo.fee_target_price', 'ProgDemandInfo.commission_order_fail_reason_update', 'ProgDemandInfo.comment_update',
				'ProgCorp.collect_date', 'ProgCorp.sf_register_date', 'ProgCorp.contact_type', 'ProgCorp.mail_count', 'ProgCorp.fax_count', 'ProgCorp.not_replay_flag', 'MCorp.tel1', 'ProgDemandInfo.agree_flag',
				'ProgDemandInfo.ip_address_update', 'ProgDemandInfo.user_agent_update', 'ProgDemandInfo.host_name_update', 'ProgDemandInfo.genre_name', 'ProgDemandInfo.fee_billing_date',
				'ProgCorp.mail_address', 'ProgCorp.fax', 'ProgCorp.fax_last_send_date', 'ProgCorp.mail_last_send_date', 'ProgCorp.note'
		);
		//ORANGE-218 CHG E

		$options = array(
				'conditions' => $conditions,
				'fields' => $fields,
				'order' => array('ProgCorp.corp_id', 'ProgCorp.id', 'ProgDemandInfo.receive_datetime'),
				'joins' => array(
							array(
								'type' => 'INNER',
								'table' => 'prog_demand_infos',
								'alias' => 'ProgDemandInfo',
								'conditions' => 'ProgCorp.id = ProgDemandInfo.prog_corp_id'
							),
							array(
									'type' => 'INNER',
									'table' => 'm_corps',
									'alias' => 'MCorp',
									'conditions' => 'MCorp.id = ProgCorp.corp_id'
							)
				)
		);
		$result =  $this->ProgCorp->find('all', $options);
		$rtn = $result;

		$commission_status_list = Configure::read("PM_COMMISSION_STATUS");
		$commission_order_fail_reason_list = Util::getDropList(__('commission_order_fail_reason', true));
		$diff_list = Configure::read("PM_DIFF_LIST");
		$agree_list = array(0 => 'なし', 1 => 'あり');
		$contact_type_list = Util::getDropList('進捗表_送付方法');
		$not_reply_list = Util::getDropList('進捗表_未返信理由');
		$progress_list = Util::getDropList('進捗表状況');

		foreach($result as $key => $val){
			$rtn[$key]['ProgDemandInfo']['commission_status'] = $commission_status_list[$val['ProgDemandInfo']['commission_status']];
			$rtn[$key]['ProgDemandInfo']['commission_status_update'] = $commission_status_list[$val['ProgDemandInfo']['commission_status_update']];
			$rtn[$key]['ProgDemandInfo']['diff_flg'] = $diff_list[$val['ProgDemandInfo']['diff_flg']];
			$rtn[$key]['ProgDemandInfo']['agree_flag'] = $agree_list[$val['ProgDemandInfo']['agree_flag']];

			//ORANGE-240 ADD S
			if($val['ProgDemandInfo']['diff_flg'] == 2){
				$rtn[$key]['ProgDemandInfo']['commission_status_update'] = $rtn[$key]['ProgDemandInfo']['commission_status'];
				$rtn[$key]['ProgDemandInfo']['construction_price_tax_exclude_update'] = $rtn[$key]['ProgDemandInfo']['construction_price_tax_exclude'];
				$rtn[$key]['ProgDemandInfo']['complete_date_update'] = $rtn[$key]['ProgDemandInfo']['complete_date'];
			}
			//ORANGE-240 ADD E

			$rtn[$key]['ProgDemandInfo']['commission_order_fail_reason_update'] = $commission_order_fail_reason_list[$val['ProgDemandInfo']['commission_order_fail_reason_update']] ;
			$rtn[$key]['ProgCorp']['progress_flag'] = $progress_list[$val['ProgCorp']['progress_flag']];
			$rtn[$key]['ProgCorp']['not_replay_flag'] = $not_reply_list[$val['ProgCorp']['not_replay_flag']];
			$rtn[$key]['ProgCorp']['contact_type'] = $contact_type_list[$val['ProgCorp']['contact_type']];
			$rtn[$key]['ProgCorp']['last_send_date'] = '';
			if($val['ProgCorp']['contact_type'] == 1){
				$rtn[$key]['ProgCorp']['last_send_date'] = $val['ProgCorp']['mail_last_send_date'];
			}
			else if($val['ProgCorp']['contact_type'] == 2){
				$rtn[$key]['ProgCorp']['last_send_date'] = $val['ProgCorp']['fax_last_send_date'];
			}
			$rtn[$key]['ProgCorp']['koujo'] = "";

			if(!empty($val['ProgDemandInfo']['fee']))$rtn[$key]['ProgDemandInfo']['fee'] = $val['ProgDemandInfo']['fee'].'円';
			elseif(!empty($val['ProgDemandInfo']['fee_rate']))$rtn[$key]['ProgDemandInfo']['fee'] = $val['ProgDemandInfo']['fee_rate'].'%';

		}

		return $rtn;
	}

	/**
	 * 追加案件 CSVデータの作成
	 * @param unknown $id
	 * @param string $type
	 */
	private function __get_add_csv_data($id=null, $type='corp'){

		if($type == 'corp')$conditions = array('ProgCorp.id' => $id);
		else if($type == 'file')$conditions = array('ProgCorp.prog_import_file_id' => $id);

		//ORANGE-218 CHG S
		$fields = array('MCorp.corp_name', 'MCorp.official_corp_name', 'ProgAddDemandInfo.corp_id', 'MCorp.commission_dial', 'ProgCorp.contact_type', 'ProgCorp.unit_cost', 'ProgCorp.progress_flag',
				'ProgAddDemandInfo.comment_update', 'ProgAddDemandInfo.demand_id_update', 'ProgAddDemandInfo.customer_name_update', 'ProgAddDemandInfo.category_name_update', 'ProgAddDemandInfo.commission_status_update',
				'ProgAddDemandInfo.complete_date_update', 'ProgAddDemandInfo.construction_price_tax_exclude_update', 'ProgAddDemandInfo.agree_flag',
				'ProgCorp.collect_date', 'ProgCorp.sf_register_date', 'ProgCorp.contact_type', 'ProgCorp.mail_count', 'ProgCorp.fax_count', 'ProgCorp.not_replay_flag', 'MCorp.tel1',
				'ProgAddDemandInfo.ip_address_update', 'ProgAddDemandInfo.user_agent_update', 'ProgAddDemandInfo.host_name_update',
				'ProgCorp.mail_address', 'ProgCorp.fax', 'ProgCorp.fax_last_send_date', 'ProgCorp.mail_last_send_date', 'ProgCorp.note'
		);
		//ORANGE-218 CHG E

		$options = array(
				'conditions' => $conditions,
				'fields' => $fields,
				'order' => array('ProgCorp.corp_id', 'ProgCorp.id'),
				'joins' => array(
						array(
								'type' => 'INNER',
								'table' => 'prog_add_demand_infos',
								'alias' => 'ProgAddDemandInfo',
								'conditions' => 'ProgCorp.id = ProgAddDemandInfo.prog_corp_id'
						),
						array(
								'type' => 'INNER',
								'table' => 'm_corps',
								'alias' => 'MCorp',
								'conditions' => 'MCorp.id = ProgCorp.corp_id'
						)
				)
		);
		$result =  $this->ProgCorp->find('all', $options);
		$rtn = $result;

		$commission_status_list = Configure::read("PM_COMMISSION_STATUS");
		$commission_order_fail_reason_list = Util::getDropList(__('commission_order_fail_reason', true));
		$diff_list = Configure::read("PM_DIFF_LIST");
		$agree_list = array(0 => 'なし', 1 => 'あり');
		$contact_type_list = Util::getDropList('進捗表_送付方法');
		$not_reply_list = Util::getDropList('進捗表_未返信理由');
		$progress_list = Util::getDropList('進捗表状況');

		foreach($result as $key => $val){
			$rtn[$key]['ProgDemandInfo']['corp_id'] = $rtn[$key]['ProgAddDemandInfo']['corp_id'];
			$rtn[$key]['ProgDemandInfo']['demand_id'] = $rtn[$key]['ProgAddDemandInfo']['demand_id_update'];
			$rtn[$key]['ProgDemandInfo']['commission_id'] = "";
			$rtn[$key]['ProgDemandInfo']['receive_datetime'] = '';
			$rtn[$key]['ProgDemandInfo']['customer_name'] = $rtn[$key]['ProgAddDemandInfo']['customer_name_update'];
			$rtn[$key]['ProgDemandInfo']['category_name'] = $rtn[$key]['ProgAddDemandInfo']['category_name_update'];
			$rtn[$key]['ProgDemandInfo']['fee'] = '';
			$rtn[$key]['ProgDemandInfo']['complete_date'] = '';
			$rtn[$key]['ProgCorp']['progress_flag'] = $progress_list[$val['ProgCorp']['progress_flag']];
			$rtn[$key]['ProgCorp']['not_replay_flag'] = $not_reply_list[$val['ProgCorp']['not_replay_flag']];
			$rtn[$key]['ProgCorp']['contact_type'] = $contact_type_list[$val['ProgCorp']['contact_type']];
			$rtn[$key]['ProgDemandInfo']['construction_price_tax_exclude'] = '';
			$rtn[$key]['ProgDemandInfo']['commission_status'] = '';
			$rtn[$key]['ProgDemandInfo']['diff_flg'] = '追加施工案件';
			$rtn[$key]['ProgDemandInfo']['commission_status_update'] = $commission_status_list[$val['ProgAddDemandInfo']['commission_status_update']];
			$rtn[$key]['ProgDemandInfo']['complete_date_update'] = $rtn[$key]['ProgAddDemandInfo']['complete_date_update'];
			$rtn[$key]['ProgDemandInfo']['construction_price_tax_exclude_update'] = $rtn[$key]['ProgAddDemandInfo']['construction_price_tax_exclude_update'];
			$rtn[$key]['ProgDemandInfo']['fee_target_price'] = '';
			$rtn[$key]['ProgDemandInfo']['commission_order_fail_reason_update'] = '';
			$rtn[$key]['ProgDemandInfo']['comment_update'] = $rtn[$key]['ProgAddDemandInfo']['comment_update'];
			$rtn[$key]['ProgDemandInfo']['genre_name'] = '';
			$rtn[$key]['ProgDemandInfo']['fee_billing_date'] = '';
			$rtn[$key]['ProgDemandInfo']['ip_address_update'] = $rtn[$key]['ProgAddDemandInfo']['ip_address_update'];
			$rtn[$key]['ProgDemandInfo']['user_agent_update'] = $rtn[$key]['ProgAddDemandInfo']['user_agent_update'];
			$rtn[$key]['ProgDemandInfo']['host_name_update'] = $rtn[$key]['ProgAddDemandInfo']['host_name_update'];

			$rtn[$key]['ProgDemandInfo']['agree_flag'] = $agree_list[$val['ProgAddDemandInfo']['agree_flag']];

			$rtn[$key]['ProgCorp']['last_send_date'] = '';
			if($val['ProgCorp']['contact_type'] == 1){
				$rtn[$key]['ProgCorp']['last_send_date'] = $val['ProgCorp']['mail_last_send_date'];
			}
			else if($val['ProgCorp']['contact_type'] == 2){
				$rtn[$key]['ProgCorp']['last_send_date'] = $val['ProgCorp']['fax_last_send_date'];
			}

			$rtn[$key]['ProgCorp']['koujo'] = "";
		}

		return $rtn;
	}

	/**
	 * 一時データテーブルへ保存
	 * @param unknown $data
	 * @param string $type
	 */
	private function __set_tmp($data = null, $prog_corp_id=null){

		try{

// murata.s ORANGE-384 DEL(S)
// 			$this->__delete_tmp($prog_corp_id);
// murata.s ORANGE-384 DEL(E)

			if(!empty($data['ProgDemandInfo'])){

// murata.s ORANGE-384 ADD(S)
				// 一時データ登録対象となる進捗管理案件のみ削除
				$prog_demand_ids = array();
				foreach($data['ProgDemandInfo'] as $key => $item){
					$prog_demand_ids[] = $item['id'];
				}
				$this->__delete_prog_demand_info_tmp($prog_corp_id, $prog_demand_ids);
// murata.s ORANGE-384 ADD(E)

				$savedata = array();

				foreach($data['ProgDemandInfo'] as $key => $item){
					$savedata[$key] = $item;
					$savedata[$key]['prog_corp_id'] = $prog_corp_id;
					if(!empty($item['id']))$savedata[$key]['prog_demand_info_id'] = $item['id'];
					unset($savedata[$key]['id']);
				}

				$this->ProgDemandInfoTmp->saveAll($savedata, array('validate' => false, 'atomic' => false));
			}
// murata.s 2017.06.08 CHG(S)
			if(isset($data['ProgDemandInfoOther']['add_flg'])){
				$this->__delete_prog_add_demand_info_tmp($prog_corp_id);
			}
			if(!empty($data['ProgAddDemandInfo'])){
// // murata.s ORANGE-384 ADD(S)
// 				$this->__delete_prog_add_demand_info_tmp($prog_corp_id);
// // murata.s ORANGE-384 ADD(E)
				$saveadddata = array();

				foreach($data['ProgAddDemandInfo'] as $key => $item){
					$saveadddata[$key] = $item;
					$saveadddata[$key]['prog_corp_id'] = $prog_corp_id;
					if(!empty($item['id']))$saveadddata[$key]['prog_add_demand_info_id'] = $item['id'];
					unset($saveadddata[$key]['id']);
				}

				$this->ProgAddDemandInfoTmp->saveAll($saveadddata, array('validate' => false, 'atomic' => false));
			}
// murata.s 2017.06.08 CHG(E)

			if(!empty($data['ProgImportFile']) && !empty($data['ProgDemandInfoOther'])){
// murata.s ORANGE-384 ADD(S)
				$this->__delete_prog_demand_info_other_tmp($prog_corp_id);
// murata.s ORANGE-384 ADD(E)
				$saveodata = array();

				$saveodata['ProgDemandInfoOtherTmp']['prog_corp_id'] = $prog_corp_id;
				if(!empty($data['ProgImportFile']['file_id']))$saveodata['ProgDemandInfoOtherTmp']['prog_import_file_id'] = $data['ProgImportFile']['file_id'];
				if(!empty($data['ProgDemandInfoOther']['add_flg']))$saveodata['ProgDemandInfoOtherTmp']['add_flg'] = $data['ProgDemandInfoOther']['add_flg'];
				if(!empty($data['ProgDemandInfoOther']['agree_flag']))$saveodata['ProgDemandInfoOtherTmp']['agree_flag'] = $data['ProgDemandInfoOther']['agree_flag'];

				$this->ProgDemandInfoOtherTmp->save($saveodata, array('validate' => false, 'atomic' => false));
			}

			//$this->Session->setFlash('一時データの保存に成功しました');
		}catch(Exception $e){
			$this->log($e->getMessage(), PROG_IMPORT_LOG);
			//$this->Session->setFlash('一時データの保存に失敗しました');
		}
	}

	/**
	 * 一時データテーブルの取得
	 * @param unknown $data
	 * @param string $type
	 */
	private function __get_tmp($prog_corp_id, $type='demand_info'){

		$rtn = array();

		if($type == 'demand_info'){
			$options = array('conditions' => array('ProgDemandInfoTmp.prog_corp_id' => $prog_corp_id), 'order' => 'receive_datetime');
			$tmp = $this->ProgDemandInfoTmp->find('all', $options);
			//データの整形
			foreach($tmp as $key => $item){
				$rtn[$key] = $item['ProgDemandInfoTmp'];
				unset($rtn[$key]['id']);
				if($rtn[$key]['prog_demand_info_id'])$rtn[$key]['id'] = $rtn[$key]['prog_demand_info_id'];
			}
		}else if($type == 'add_demand_info'){
			$options = array('conditions' => array('ProgAddDemandInfoTmp.prog_corp_id' => $prog_corp_id), 'order' => 'id');
			$tmp = $this->ProgAddDemandInfoTmp->find('all', $options);
			//データの整形
			foreach($tmp as $key => $item){
				$rtn[$key] = $item['ProgAddDemandInfoTmp'];
				unset($rtn[$key]['id']);
				if($rtn[$key]['prog_add_demand_info_id'])$rtn[$key]['id'] = $rtn[$key]['prog_add_demand_info_id'];
			}
		}else if($type == 'other'){
			$options = array('conditions' => array('ProgDemandInfoOtherTmp.prog_corp_id' => $prog_corp_id), 'order' => 'id');
			$tmp = $this->ProgDemandInfoOtherTmp->find('all', $options);
			//データの整形
			foreach($tmp as $key => $item){
				$rtn = $item['ProgDemandInfoOtherTmp'];
			}
		}else if($type == 'file'){
			$options = array('conditions' => array('ProgDemandInfoOtherTmp.prog_corp_id' => $prog_corp_id), 'order' => 'id');
			$tmp = $this->ProgDemandInfoOtherTmp->find('all', $options);
			//データの整形
			foreach($tmp as $key => $item){
				$rtn['file_id'] = $item['ProgDemandInfoOtherTmp']['prog_import_file_id'];
			}
		}

		return $rtn;
	}

	/**
	 * 一時データの削除
	 * @param unknown $prog_corp_id
	 */
	private function __delete_tmp($prog_corp_id = null){

		try{
			$conditions = array('ProgDemandInfoTmp.prog_corp_id' => $prog_corp_id);
			$this->ProgDemandInfoTmp->deleteAll($conditions, false);

			$conditions = array('ProgAddDemandInfoTmp.prog_corp_id' => $prog_corp_id);
			$this->ProgAddDemandInfoTmp->deleteAll($conditions, false);

			$conditions = array('ProgDemandInfoOtherTmp.prog_corp_id' => $prog_corp_id);
			$this->ProgDemandInfoOtherTmp->deleteAll($conditions, false);

		}catch(Exception $e){
			$this->log($e->getMessage(), PROG_IMPORT_LOG);
		}
	}

	//ORANGE-239 ADD S
	/**
	 * 一時データの保存（ajax）
	 * @param unknown $prog_corp_id
	 */
	public function ajax_set_session($prog_corp_id = null){
		$this->autoRender = FALSE;

		if($this->request->is('ajax')) {
			$this->__set_tmp($this->request->data, $prog_corp_id);
		}

		return true;
	}
	//ORANGE-239 ADD E

// murata.s ORANGE-384 ADD(S)
	/**
	 * 進捗管理案件情報を取得する
	 * @param int $prog_corp_id 進捗管理企業ID
	 * @param int $page ページ番号
	 */
	private function __get_prog_demand_infos($prog_corp_id, $page = 1) {
		$data = array();

		//案件データ格納
		$options = array(
				'conditions' => array('ProgDemandInfo.prog_corp_id' => $prog_corp_id),
				'page' => $page,
				'limit' => PM_DETAIL_LIST_LIMIT
		);
		$this->paginate = $options;
		$demand_infos = $this->paginate('ProgDemandInfo');
		//データの整形
		foreach($demand_infos as $demand_info){
			$data['ProgDemandInfo'][] = $demand_info['ProgDemandInfo'];
		}

		//追加案件データ格納
		$options = array(
				'conditions' => array('ProgAddDemandInfo.prog_corp_id' => $prog_corp_id),
				'order' => array('ProgAddDemandInfo.id'),
		);
		$add_demand_infos = $this->ProgAddDemandInfo->find('all', $options);
		foreach($add_demand_infos as $add_demand_info){
			$data['ProgDemandInfoOther']['add_flg'] = 1;
			$add_demand_info['ProgAddDemandInfo']['display'] = 1;
			$data['ProgAddDemandInfo'][] = $add_demand_info['ProgAddDemandInfo'];

		}

		return $data;
	}

	/**
	 * 進捗管理案件一時情報を削除する
	 * @param integer $prog_corp_id 進捗管理企業ID
	 * @param array $prog_demand_id 進捗管理案件情報ID
	 */
	private function __delete_prog_demand_info_tmp($prog_corp_id = null, $prog_demand_id = null){
		try{
			$conditions = array(
					'ProgDemandInfoTmp.prog_corp_id' => $prog_corp_id,
					'ProgDemandInfoTmp.prog_demand_info_id' => $prog_demand_id
			);
			$this->ProgDemandInfoTmp->deleteAll($conditions, false);
		}catch(Exception $e){
			$this->log($e->getMessage(), PROG_IMPORT_LOG);
		}
	}

	/**
	 * 進捗管理追加案件一時情報を削除する
	 * @param integer $prog_corp_id 進捗管理企業ID
	 */
	private function __delete_prog_add_demand_info_tmp($prog_corp_id = null){
		try{
			$conditions = array('ProgAddDemandInfoTmp.prog_corp_id' => $prog_corp_id);
			$this->ProgAddDemandInfoTmp->deleteAll($conditions, false);
		}catch(Exception $e){
			$this->log($e->getMessage(), PROG_IMPORT_LOG);
		}
	}

	/**
	 * 進捗管理その他一時情報を削除する
	 * @param integer $prog_corp_id 進捗管理企業ID
	 */
	private function __delete_prog_demand_info_other_tmp($prog_corp_id = null){
		try{
			$conditions = array('ProgDemandInfoOtherTmp.prog_corp_id' => $prog_corp_id);
			$this->ProgDemandInfoOtherTmp->deleteAll($conditions, false);
		}catch(Exception $e){
			$this->log($e->getMessage(), PROG_IMPORT_LOG);
		}
	}
// murata.s ORANGE-384 ADD(E)

}