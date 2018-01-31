<?php
App::uses('AppController', 'Controller');

class SampleController extends AppController {

	public $name = 'Sample';
	public $helpers = array();
	public $components = array();
	public $uses = array('DemandInfo', 'CommissionInfo', 'BillInfo', 'DemandCorrespond', 'MAnswer', 'MUser', 'MSite', 'MInquiry', 'MCorpCategory', 'MTaxRate', 'MSiteCategory');

	public function beforeFilter(){
		parent::beforeFilter();
	}

	public function index() {

	}

	/**
	 * サブ詳細画面
	 *
	 * @param unknown_type $id
	 */
	public function sub_detail($id = null) {
		// 案件IDが無い場合
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

			// JBR見積書、JBR領収書のリンク表示判定
			if (file_exists(Configure::read('estimate_file_path') . 'estimate_' . $id . '.pdf')) {
				$this->set('estimate_file_url', '/download/estimate/estimate_' . $id . '.pdf');
			}
			if (file_exists(Configure::read('receipt_file_path') . 'receipt_' . $id . '.pdf')) {
				$this->set('receipt_file_url', '/download/receipt/receipt_' . $id . '.pdf');
			}
		}

		// ドロップダウン用リストの取得
		$this->set("site_list" , $this->MSite->getList());			// サイト一覧
		$this->set("user_list" , $this->MUser->dropDownUser());		// ユーザー一覧

		if(array_key_exists('DemandInfo', $this->data)){
			$this->set('category_list', $this->MSiteCategory->getCategoriesBySite($this->data['DemandInfo']['site_id']));
		}else{
			$this->set('category_list', array());
		}
		$this->layout = 'subwin';
		$this->render('sub_detail');
	}

	/**
	 * 詳細画面
	 *
	 * @param unknown_type $id
	 */
	public function detail($id = null, $err_flg = null) {

		// 案件IDが無い場合
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

			// JBR見積書、JBR領収書のリンク表示判定
			if (file_exists(Configure::read('estimate_file_path') . 'estimate_' . $id . '.pdf')) {
				$this->set('estimate_file_url', '/download/estimate/estimate_' . $id . '.pdf');
			}
			if (file_exists(Configure::read('receipt_file_path') . 'receipt_' . $id . '.pdf')) {
				$this->set('receipt_file_url', '/download/receipt/receipt_' . $id . '.pdf');
			}
		}

		// 処理結果フラグが設定されていればメッセージを表示する
		if (isset($err_flg)){
			if ($err_flg == 0) {
				$this->set('message', __('SuccessRegist', true));
			} else if ($err_flg == 1) {
				$this->set('message', '更新に失敗しました。');
			}
		}

		// ドロップダウン用リストの取得
		$this->set("site_list" , $this->MSite->getList());			// サイト一覧
		$this->set("user_list" , $this->MUser->dropDownUser());		// ユーザー一覧

		if(array_key_exists('DemandInfo', $this->data)){
			$this->set('category_list', $this->MSiteCategory->getCategoriesBySite($this->data['DemandInfo']['site_id']));
		}else{
			$this->set('category_list', array());
		}

	}

	/**
	 * CTI連携による検索画面からのリダイレクト用
	 *
	 * @param unknown_type $customer_tel
	 * @param unknown_type $site_tel
	 */
	public function cti($customer_tel = null, $site_tel = null){

		// *****CTI連携処理*****
		// 引数がない場合
		if(empty($customer_tel) || empty($site_tel)){
			$this->redirect('/demand');
		}

		// 案件データの既定値を取得
		$this->data = $this->__set_pre_demand($customer_tel, $site_tel);

		// ドロップダウン用リストの取得
		$this->set("site_list" , $this->MSite->getList());			// サイト一覧
		$this->set("category_list", array());	// カテゴリ一覧
		$this->set("user_list" , $this->MUser->dropDownUser());		// ユーザー一覧

		$this->render('detail');
	}

	/**
	 * 案件コピー
	 *
	 * @param unknown_type $id
	 */
	public function copy($id){

		$data = array();

		// 案件IDが無い場合
		if (!empty($id)) {
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
		}

		$this->data = $data;

		// ドロップダウン用リストの取得
		$this->set("site_list" , $this->MSite->getList());			// サイト一覧
		$this->set("user_list" , $this->MUser->dropDownUser());		// ユーザー一覧

		if(array_key_exists('DemandInfo', $this->data)){
			$this->set('category_list', $this->MSiteCategory->getCategoriesBySite($this->data['DemandInfo']['site_id']));
		}else{
			$this->set('category_list', array());
		}

		$this->render('detail');
	}

	/**
	 * 確認画面
	 *
	 */
	public function confirm(){

		// 入力値取得
		$data = $this->request->data;

		if(isset($data['cancel'])){
			// キャンセル
			$this->redirect('/demand');
		} else if (isset($data['delete'])){
			// 削除
			$this->redirect('/demand/delete');
		} else if (isset($data['copy'])){
			// コピー
			$this->redirect('/demand/copy/' . $data['DemandInfo']['id']);
		}

		// validationの実行
		$err_flg = true;

		$this->DemandInfo->set($data['DemandInfo']);
		if ($this->DemandInfo->validates()) {
			$this->CommissionInfo->set($data['CommissionInfo']);
			if ($this->CommissionInfo->validates()) {
				$this->CommissionInfo->set($data['IntroduceInfo']);
				if ($this->CommissionInfo->validates()) {
					$this->DemandCorrespond->set($data['DemandCorrespond']);
					if ($this->DemandCorrespond->validates()) {
						$err_flg = false;
					}
				}
			}
		}

		if ($err_flg) {
			// ドロップダウン用リストの取得
			$this->set("site_list" , $this->MSite->getList());			// サイト一覧
			$this->set("user_list" , $this->MUser->dropDownUser());		// ユーザー一覧
			if(array_key_exists('DemandInfo', $data)){
				$this->set('category_list', $this->MSiteCategory->getCategoriesBySite($data['DemandInfo']['site_id']));
			}else{
				$this->set('category_list', array());
			}
			$this->render('detail');
		} else {
			// [JBR]見積書、領収書ファイルの一時アップロード
			// ※ 確認画面を経由するとアップロードファイルが削除される為、
			//    一時ファイルとしてアップロードしておく
			//    (正式なアップロードは本登録後に行う)
			$tmp_estimate_file = "";
			$tmp_receipt_file = "";
			if (!empty($data['DemandInfo']['jbr_estimate']['name'])) {
				$tmp_estimate_file = $this->__uplaod_file_tmp($data['DemandInfo']['jbr_estimate']['tmp_name']);
			}
			if (!empty($data['DemandInfo']['jbr_receipt']['name'])) {
				$tmp_receipt_file = $this->__uplaod_file_tmp($data['DemandInfo']['jbr_receipt']['tmp_name']);
			}
			// 一時ファイル名を入力値データにセット
			$data['DemandInfo']['tmp_estimate_file'] = $tmp_estimate_file;
			$data['DemandInfo']['tmp_receipt_file'] = $tmp_receipt_file;

			// リクエストデータ配列をPOST用に文字列化
			$enc_data = rawurlencode(serialize($data));

			$this->data = $data;
			$this->set('enc_data', $enc_data);

			// ユーザーの一覧をSET
			$this->set("user_list" , $this->MUser->dropDownUser());
		}
	}

	/**
	 * 登録
	 *
	 */
	public function regist(){

		// 入力値取得
		$data = $this->request->data;
		$enc_data = $data['enc_data'];
		$input_data = unserialize(rawurldecode($enc_data));

		if (isset($data['cancel'])) {
			// キャンセル
			$this->backToDetail($input_data);
		} else {
			$err_flg = 1;

			// 案件情報の登録
			if ($this->__update_demand($input_data)) {
				// 取次先情報の登録
				if ($this->__update_commission($input_data)) {
					// 紹介先情報の登録
					if ($this->__update_introduce($input_data)) {
						// 案件対応履歴の登録
						if ($this->__update_demand_correspond($input_data)) {
							// 全ての更新に成功すればエラーフラグを倒す
							$err_flg = 0;
						}
					}
				}
			}

			$this->redirect('/demand/detail/' . $this->DemandInfo->id . '/' . $err_flg);
		}
	}

	/**
	 * カテゴリリストの取得(Ajax通信用)
	 *
	 * @param unknown_type $site
	 */
	public function category_list($site) {

		// Ajax通信以外は受け付けない
		if (!$this->request->is('ajax')) {
			throw new Exception();
		}

		$this->set('category_list', $this->MSiteCategory->getCategoriesBySite($site));

		$this->layout = 'ajax';
	}

	/**
	 * ヒアリング項目の取得(Ajax通信用)
	 *
	 * @param unknown_type $site
	 */
	public function inquiry_list($category) {

		// Ajax通信以外は受け付けない
		if (!$this->request->is('ajax')) {
			throw new Exception();
		}

		$conditions = array('genre_id' => $category);

		$inquiry_list = $this->MInquiry->find('all', array('conditions' => $conditions, 'order' => array('MInquiry.id' => 'asc')));

		for ($i = 0; $i < count($inquiry_list); $i++) {
			$inquiry_list[$i]['MAnswer'] = $this->MAnswer->dropDownAnswer($inquiry_list[$i]['MInquiry']['id']);
		}

		$this->set('inquiry_list', $inquiry_list);

		$this->layout = 'ajax';
	}

	/**
	 * 確認画面→詳細画面の戻り処理
	 *
	 * @param unknown_type $data
	 */
	private function backToDetail($data){

		// 詳細画面用にデータを整形
		$demand_info = $data['DemandInfo'];																			// 案件情報
		$commission_info = (array_key_exists('CommissionInfo', $data)) ? $data['CommissionInfo'] : array();			// 取次情報
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

		// 入力値で上書き
		$disp_data['DemandInfo'] = $demand_info;
		$disp_data['CommissionInfo'] = $commission_info;
		$disp_data['IntroduceInfo'] = $introduce_info;
		$disp_data['MInquiry'] = $m_inquiry;
		$disp_data['DemandCorrespond'] = $data['DemandCorrespond'];

		// ヒアリング回答項目データの取得
		$answer_list = array();
		for($i = 0; $i < count($disp_data['MInquiry']); $i++) {
			$answer_list[$i] = $this->__set_answer($disp_data['MInquiry'][$i]['DemandInquiryAnswer']['inquiry_id']);
		}
		$disp_data['MAnswer'] = $answer_list;

		// メール/FAX送信
		if(array_key_exists('send', $data) && $data['send'] == 1){
			$disp_data['send'] = '1';
		}

		$this->data = $disp_data;

		// ドロップダウン用リストの取得
		$this->set("site_list" , $this->MSite->getList());			// サイト一覧
		$this->set("user_list" , $this->MUser->dropDownUser());		// ユーザー一覧

		if(array_key_exists('DemandInfo', $this->data)){
			$this->set('category_list', $this->MSiteCategory->getCategoriesBySite($this->data['DemandInfo']['site_id']));
		}else{
			$this->set('category_list', array());
		}

		// 詳細画面表示
		$this->render('detail');
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

		if (0 < count($site_info)) {
			$site_id = $site_info['MSite']['id'];
		} else {
			$site_id = "";
		}

		// 表示データ設定
		$results = array(
				'DemandInfo' => array(
					'customer_tel' => $customer_tel,
					'site_id' => $site_id,
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
	 * 案件情報の更新
	 *
	 * @param unknown $data
	 */
	private function __update_demand($data){

		// 案件情報の登録
		$save_data = $data['DemandInfo'];
		$result = $this->DemandInfo->save($save_data);

		// saveメソッドは更新成功時、登録データ、またはtrueを返す
		if (is_array($result) || $result == true) {
			// [JBR]見積書、領収書ファイルのアップロード
			if (!array_key_exists('id', $data['DemandInfo'])) {
				$data['DemandInfo']['id'] = $this->DemandInfo->id;
			}
			if (!$this->__uplaod_file($data)) {
				$this->log("upload failed. [ PDF ]");
				return false;
			}
			return true;
		} else {
			return false;
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

		if (count($result)) {
			return $result['MCorpCategory']['introduce_fee'];
		} else {
			return 0;
		}
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
	 * 取次先情報の更新
	 *
	 * @param unknown $data
	 */
	private function __update_commission($data){

		// 取次先情報が未入力ならなにもしない
		if (!array_key_exists('CommissionInfo', $data)) {
			return true;
		}

		// 案件IDの取得
		$demand_id = (array_key_exists('id', $data['DemandInfo'])) ? $data['DemandInfo']['id'] : $this->DemandInfo->id;

		// 取次先情報の登録
		$commission_data = array();
		foreach ($data['CommissionInfo'] as $key => $val){
			// 取次先企業IDの有無で登録要否を判断
			if(!empty($val['corp_id'])) {
				// 案件ID、企業IDで登録済みデータの有無を確認
				$current_data = $this->CommissionInfo->find('first', array('conditions'=>array('demand_id'=>$demand_id, 'corp_id'=>$val['corp_id'], 'commission_type'=>0)));

				// 更新 or 登録判定
				if (0 < count($current_data)) {
					// 更新の場合
					$commission_data[$key]['id'] = $current_data['CommissionInfo']['id'];
					$commission_data[$key]['commit_flg'] = $val['commit_flg'];
					$commission_data[$key]['appointers'] = $val['appointers'];
					$commission_data[$key]['first_commission'] = $val['first_commission'];
				} else {
					// 新規登録の場合
					$commission_data[$key]['demand_id'] = $demand_id;
					$commission_data[$key]['corp_id'] = $val['corp_id'];
					$commission_data[$key]['commit_flg'] = $val['commit_flg'];
					$commission_data[$key]['commission_type'] = 0;				// 取次種別:0(通常取次)
					$commission_data[$key]['appointers'] = $val['appointers'];
					$commission_data[$key]['first_commission'] = $val['first_commission'];
					$commission_data[$key]['commission_status'] = 1;			// 取次状況:1(進行中)
					$commission_data[$key]['unit_price_calc_exclude'] = 0;		// 取次単価対象外:0
				}
			}
		}

		if (0 < count($commission_data)) {
			return $this->CommissionInfo->saveAll($commission_data);
		}
	}

	/**
	 * 紹介先情報の更新
	 *
	 * @param unknown $data
	 */
	private function __update_introduce($data){

		// 紹介先情報が未入力ならなにもしない
		if (!array_key_exists('IntroduceInfo', $data)) {
			return true;
		}

		// 新規登録した企業IDリスト(請求情報登録用)
		$inserted = array();

		// 案件IDの取得
		$demand_id = (array_key_exists('id', $data['DemandInfo'])) ? $data['DemandInfo']['id'] : $this->DemandInfo->id;

		// 紹介先情報の登録
		$introduce_data = array();
		foreach ($data['IntroduceInfo'] as $key => $val){
			// 案件ID、企業IDで登録済みデータの有無を確認
			$current_data = $this->CommissionInfo->find('first', array('conditions'=>array('demand_id'=>$demand_id, 'corp_id'=>$val['corp_id'], 'commission_type'=>1)));

			// 更新 or 登録判定
			if (0 < count($current_data)) {
				// 更新の場合
				$introduce_data[$key]['id'] = $current_data['CommissionInfo']['id'];
				$introduce_data[$key]['commission_note_send_datetime'] = $val['commission_note_send_datetime'];
				$introduce_data[$key]['commission_note_sender'] = $val['commission_note_sender'];
			} else {
				// 新規登録の場合
				$introduce_data[$key]['demand_id'] = $demand_id;
				$introduce_data[$key]['corp_id'] = $val['corp_id'];
				$introduce_data[$key]['commission_note_send_datetime'] = $val['commission_note_send_datetime'];
				$introduce_data[$key]['commission_note_sender'] = $val['commission_note_sender'];
				$introduce_data[$key]['commission_type'] = 1;									// 取次種別:1(一括見積)
				$introduce_data[$key]['commit_flg'] = 0;										// 確定フラグ:0(未確定)
				$introduce_data[$key]['appointers'] = 0;										// 選定者:ログインユーザー
				$introduce_data[$key]['first_commission'] = 0;									// 初取次チェック:0
				$introduce_data[$key]['corp_fee'] = $this->__get_introduce_fee(
														$val['corp_id'],
														$data['DemandInfo']['category_id']);	// 取次先手数料:企業別対応ジャンルマスタ.紹介手数料
				$introduce_data[$key]['tel_commission_datetime'] = 0;							// 電話取次日時:現在日時
				$introduce_data[$key]['tel_commission_person'] = 0;								// 電話取次者:ログインユーザー
				$introduce_data[$key]['commission_fee_rate'] = 100;								// 取次時手数料率:100
				$introduce_data[$key]['commission_status'] = 1;									// 取次状況:1(進行中)
				$introduce_data[$key]['confirmd_fee_rate'] = 100;								// 確定手数料率:100
				$introduce_data[$key]['unit_price_calc_exclude'] = 0;							// 取次単価対象外:0

				array_push($inserted, $val['corp_id']);
			}
		}

		// 紹介先情報の更新
		if (0 < count($introduce_data)) {
			if (!$this->CommissionInfo->saveAll($introduce_data)) {
				return false;
			}
		}

		if (0 < count($inserted)) {
			$this->__update_bill($demand_id, $data['DemandInfo']['category_id'], $inserted);
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
			// 紹介手数料の取得
			$introduce_fee = $this->__get_introduce_fee($val, $category_id);

			$save_data[$key]['demand_id'] = $demand_id;
			$save_data[$key]['bill_status'] = 1;												// 請求状況:1(未発行)
			$save_data[$key]['comfirmed_fee_rate'] = 100;										// 確定手数料率:100
			$save_data[$key]['fee_target_price'] = $introduce_fee;								// 手数料対象金額:企業別対応ジャンルマスタ.紹介手数料
			$save_data[$key]['fee_tax_exclude'] =
					($save_data[$key]['comfirmed_fee_rate'] / 100) * $introduce_fee;			// 手数料:(確定手数料率 / 100) * 手数料対象金額
			$save_data[$key]['tax'] = floor($save_data[$key]['fee_tax_exclude'] * $tax_rate);	// 消費税:手数料 * 消費税率
			$save_data[$key]['total_bill_price'] =
					$save_data[$key]['fee_tax_exclude'] + $save_data[$key]['tax'];				// 合計請求金額:手数料 + 消費税
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

		// 紹介先情報の更新
		if (!$this->DemandCorrespond->save($save_data)) {
			return false;
		}

		return true;
	}

	/**
	 * PDFファイルの一時アップロード処理
	 *
	 * @param unknown_type $tempfile
	 */
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

	/**
	 * PDFファイルのアップロード処理(一時ファイル→本ファイル)
	 *
	 * @param unknown_type $data
	 */
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
}
