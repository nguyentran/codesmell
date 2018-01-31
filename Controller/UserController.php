<?php
App::uses('AppController', 'Controller');

class UserController extends AppController {

	public $name = 'User';
	// murata.s ORANGE-430 CHG(S)
	public $helpers = array('Csv');
	public $components = array('Session', 'RequestHandler', 'Csv');
	// murata.s ORANGE-430 CHG(E)
	public $uses = array('MUser', 'MCorp');

	public function beforeFilter(){

		$this->set('default_display', false);

		parent::beforeFilter();
	}


	// 初期表示（一覧）
	public function index() {
		$this->set('default_display', true);
	}

	private function userListGet() {

		$data = $this->Session->read(self::$__sessionKeyForUserSearch);

		if (isset($data)) {
			$page = $this->__getPageNum();
			$this->data = $data;
		}

		if(!empty($data)){
			$data += array('page' => $page);
		}

		return $data;
	}

	private function userListPost() {

		// 入力値取得
		$data = $this->request->data;

		// セッションに検索条件を保存
		$this->Session->delete(self::$__sessionKeyForUserSearch);
		$this->Session->write(self::$__sessionKeyForUserSearch, $data);

		return $data;
	}

	public function search() {

		try {
			if ($this->RequestHandler->isPost()) {
				$data = $this->userListPost();
			} else {
				$data = $this->userListGet();
			}
		} catch (Exception $e) {
			throw $e;
		}

		// 検索条件の設定
		$conditions = self::setSearchConditions($data);

// murata.s ORANGE-430 CHG(S)
		if(isset( $this->request->data['csv_out'])){
			// CSV出力
			$data_list = $this->__get_csv_data($conditions);
			$file_name = mb_convert_encoding('ユーザー_'.$this->User['user_id'], 'SJIS-win', 'UTF-8');
			$this->Csv->download('MUser', 'default', $file_name, $data_list);
		}else{
			$results = $this->__searchUserInfo($conditions);

			$this->set('results', $results);
			// [CSV出力]の可否で使用
			$this->set('searched', true);

			$this->render('index');
		}
// murata.s ORANGE-430 CHG(E)
	}


	/**
	 * ユーザー情報を検索する
	 * @param unknown $conditions
	 * @return unknown
	 */
	private function __searchUserInfo($conditions = array()) {

		$joins = array();
		$joins = self::setSearchJoin();

		$param = array();

		// 検索する
		$this->paginate = array(
				'conditions' => $conditions,
				'fields' => '*, MCorp.id, MCorp.official_corp_name',
				'joins' => $joins,
				'limit' => Configure::read('list_limit'),
				'order' => array('MUser.id' => 'desc'),
		);

		$results = $this->paginate('MUser');

		return $results;
	}

	/***
	 * 検索条件設定
	 */
	private function setSearchConditions($data = null) {
		$conditions = array();

		// 加盟店名
		if (!empty($data['corp_name'])) {
			$conditions['z2h_kana(MCorp.corp_name) like'] = '%'. Util::chgSearchValue($data['corp_name']) .'%';
		}

		// ユーザー名
		if (!empty($data['user_name'])) {
			$conditions['z2h_kana(MUser.user_name) like'] = '%'. Util::chgSearchValue($data['user_name']) .'%';
		}

		// 権限
		if (!empty($data['auth'])) {
			$conditions['MUser.auth'] = $data['auth'];
		}

		return $conditions;
	}

	/***
	 * 検索用Join句作成
	 */
	private function setSearchJoin() {


		$joins = array();
		$joins = array(
				array(
						'table' => 'm_corps',
						'alias' => "MCorp",
						'type' => 'left',
						'conditions' => array('MUser.affiliation_id = MCorp.id')
				),
		);

		return $joins;

	}

	// 詳細画面表示
	public function detail($id = null) {

		if ($this->RequestHandler->isGet()) {
			if (!empty($id)) {

				$result = $this->MUser->findById($id);

				if (empty($result)) {
					throw new ApplicationException();
				}

				$result['MUser']['password'] = null;
				$this->data = $result;

				$corp_id = $result['MUser']['affiliation_id'];

				if (!empty($corp_id)) {
					$corp = $this->MCorp->findById($corp_id);

					$this->data += $corp;
				}

			}
		} else {

			$data = $this->request->data;
			$newflg = false;

			if (empty($data['MUser']['id'])) {
				$newflg = true;
			} else {
				$id = $data['MUser']['id'];
			}

			// パスワードが入力されていない場合は更新対象外
			if (!$newflg && empty($data['MUser']['password'])) {
				unset($data['MUser']['password']);
				unset($data['MUser']['password_confirm']);
			} else {
				$data['MUser']['password_modify_date'] = date('Y/m/d H:i:s', time());
			}

			// 加盟店ユーザー以外は加盟店IDをクリア
			if ($data['MUser']['auth'] != 'affiliation') {
				unset($data['MUser']['affiliation_id']);
			}

			// データを設定
			$this->MUser->set($data);

			// エラーチェック
			if (!$this->MUser->validates()) {
				return;
			}

			// パスワードを暗号化
			if(isset($data['MUser']['password'])){
				$data['MUser']['password'] =  hash('sha256', $data['MUser']['password'], false);
			}

			// 2015.12.11 n.kai ADD start ORANGE-1028
			// 予期せぬエラー対応 begin/commit/rollback追加
			$this->MUser->begin();
			if (!$this->MUser->save($data, false)) {
				// 登録失敗
				$this->MUser->rollback();
				return;
			}
			$this->MUser->commit();

			if ($newflg) {
				$id = $this->MUser->getLastInsertID();
				$this->Session->setFlash(__('Inserted', true), 'default', array('class' => 'message_inner'));
			} else {
				$this->Session->setFlash(__('Updated', true), 'default', array('class' => 'message_inner'));
			}

			$this->redirect('/user/detail/'. $id);

		}

	}

	/**
	 * キャンセル
	 *
	 */
	public function cancel() {
		return $this->redirect('/user/search');
	}

// murata.s ORANGE-430 ADD(S)
	/**
	 * CSV出力データを取得する
	 * @param array $conditions
	 */
	public function __get_csv_data($conditions){

		$joins = self::setSearchJoin();

		// 検索する
		$results = $this->MUser->find('all', array(
				'conditions' => $conditions,
				'fields' => array(
						'MUser.user_id',
						'MUser.user_name',
						'MUser.auth',
						'MUser.last_login_date',
						'MCorp.id',
						'MCorp.official_corp_name'
				),
				'joins' => $joins,
				'order' => array('MUser.id' => 'desc')
		));

		$csv_data = array();
		foreach($results as $key => $val){
			$csv_data[] = array(
					'MUser' => array(
							'user_id' => $val['MUser']['user_id'],
							'user_name' => $val['MUser']['user_name'],
							'auth' => Util::getDivTextJP('auth_list', $val['MUser']['auth']),
							'last_login_date' => $val['MUser']['last_login_date']
					),
					'MCorp' => array(
							'id' => $val['MCorp']['id'],
							'official_corp_name' => $val['MCorp']['official_corp_name']
					)
			);
		}

		return $csv_data;
	}
// murata.s ORANGE-430 ADD(E)


}
