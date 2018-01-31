<?php
App::uses('AppController', 'Controller');

class SelectionController extends AppController {

	public $name = 'Selection';
	// 2016.06.13 ota.r@tobila ADD START ORANGE-88 経理管理者の使用できる管理者機能を制限
	public $helpers = array(
			'Form' => array(
					'className' => 'AdminForm',
			),
	);
	// 2016.06.13 ota.r@tobila ADD END ORANGE-88 経理管理者の使用できる管理者機能を制限
	public $components = array('Session', 'RequestHandler');
	public $uses = array('SelectGenre', 'MGenre', 'SelectGenrePrefecture');

	public function beforeFilter(){
		parent::beforeFilter();

		$this->User = $this->Auth->user();
	}

	/**
	 *
	 * ジャンル一覧ページ
	 *
	 */
	public function index() {
		// 登録処理
		if (isset($this->request->data['regist'])) {
			if ($this->SelectGenre->saveAll($this->data['SelectGenre'])){
				$this->Session->setFlash (__('SuccessRegist', true), 'default', array ('class' => 'message_inner'));
			} else {
				$this->Session->setFlash (__('FailRegist', true), 'default', array ('class' => 'error_inner'));
			}
		}
		// オークション選定ジャンル一覧を取得
		$list = $this->MGenre->getSelectionGenre();
		$this->set('genres', $list);
	}

	/**
	 * 都道府県一覧ページ
	 *
	 * @param string $genre_id
	 */
	public function prefecture($genre_id = null) {

		if (isset($this->request->data['regist'])) {
			$genre_id = $this->request->data['genre_id'];
			$list = $this->request->data['SelectGenrePrefecture'];
			if (self::__editGenrePrefecture($this->request->data)){
				$this->Session->setFlash (__('SuccessRegist', true), 'default', array ('class' => 'message_inner'));
			} else {
				$this->Session->setFlash (__('FailRegist', true), 'default', array ('class' => 'error_inner'));
			}

		} else {
			// オークションジャンル都道府県情報データを取得
			$list = $this->SelectGenrePrefecture->getSelectGenrePrefecture($genre_id);
		}

		$genre = $this->MGenre->findById($genre_id);

// 2016.11.17 murata.s ORANGE-185 ADD(S)
		$select_genre = $this->SelectGenre->find('first', array(
				'fields' => 'select_type',
				'conditions' => array('genre_id' => $genre_id)
		));
		$default_selection_type = !empty($select_genre['SelectGenre']['select_type'])
			? $select_genre['SelectGenre']['select_type'] : 0;
// 2016.11.17 murata.s ORANGE-185 ADD(E)

		$this->set('genres', $list);
		$this->set('genre_id', $genre_id);
		$this->set('genre_name', $genre['MGenre']['genre_name']);
// 2016.11.17 murata.s ORANGE-185 ADD(S)
		$this->set('default_selection_type', $default_selection_type);
// 2016.11.17 murata.s ORANGE-185 ADD(E)

	}

	/**
	 * オークションジャンル都道府県情報テーブルの更新
	 *
	 * @param unknown $data
	 * @return boolean
	 */
	private function __editGenrePrefecture($data = array()){
		$edit_data = array();
		foreach ($data['SelectGenrePrefecture'] as $key => $val){
			if(!empty($val['SelectGenrePrefecture']['prefecture_cd'])){
				$edit_data['SelectGenrePrefecture'][$key]['genre_id'] = $val['SelectGenrePrefecture']['genre_id'];
				$edit_data['SelectGenrePrefecture'][$key]['prefecture_cd'] = $val['SelectGenrePrefecture']['prefecture_cd'];
				$edit_data['SelectGenrePrefecture'][$key]['selection_type'] = $val['SelectGenrePrefecture']['selection_type'];
				$edit_data['SelectGenrePrefecture'][$key]['business_trip_amount'] = $val['SelectGenrePrefecture']['business_trip_amount'];
// 2016.09.29 murata.s ORANGE-192 ADD(S)
				$edit_data['SelectGenrePrefecture'][$key]['auction_fee'] = $val['SelectGenrePrefecture']['auction_fee'];
// 2016.09.29 murata.s ORANGE-192 ADD(E)
			}
		}

		try {
			// トランザクション開始
			$this->SelectGenrePrefecture->begin();
			// 削除
			$resultsFlg = $this->SelectGenrePrefecture->deleteAll(array('genre_id' => $data['genre_id']));

			if($resultsFlg){
				if(!empty($edit_data)){
					$resultsFlg = $this->SelectGenrePrefecture->saveAll($edit_data['SelectGenrePrefecture']);
				}
			}

			if($resultsFlg){
				$this->SelectGenrePrefecture->commit();
			} else {
				$this->SelectGenrePrefecture->rollback();
			}

		} catch (Exception $e) {
			$this->SelectGenrePrefecture->rollback();
			$resultsFlg = false;
		}

		return $resultsFlg;

	}


}
