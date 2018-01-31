<?php
App::uses('AppController', 'Controller');

class AdditionController extends AppController {

	public $name = 'Addition';
	public $helpers = array('Csv');
	public $components = array('Session', 'RequestHandler');
	public $uses = array('AdditionInfo', 'DemandInfo', 'MGenre');

	public function beforeFilter(){
		parent::beforeFilter();
		$this->set('default_display', false);
		$this->User = $this->Auth->user();

// 2016.10.20 murata.s ORANGE-208 ADD(S)
		$this->Security->requirePost('regist', 'delete');
// 2016.10.20 murata.s ORANGE-208 ADD(E)

// 2016.10.13 murata.s ORANGE-206 ADD(S) 脆弱性 権限外の操作対応
		if($this->User['auth'] == 'affiliation'){
			$actionName = strtolower($this->action);
			switch ($actionName) {
				case 'delete':
					$check_data = $this->AdditionInfo->findByIdAndCorpId($this->request->params['pass'][0], $this->User['affiliation_id']);
					if(empty($check_data))
						throw new ApplicationException(__('NoReferenceAuthority', true));
					break;
				default:
					break;
			}
		}
// 2016.10.13 murata.s ORANGE-206 ADD(S) 脆弱性 権限外の操作対応
	}

	/**
	 * 初期ページ
	 *
	 */
	public function index($demand_id = null)
	{

		// 一覧検索条件作成
		if (empty($demand_id)) {
			$conditions = array (
				'AdditionInfo.corp_id' => $this->User ["affiliation_id"],
				'AdditionInfo.demand_flg' => 0
			);
		} else {
			$conditions = array(
				'AdditionInfo.corp_id' => $this->User ["affiliation_id"],
				'AdditionInfo.demand_id' => $demand_id,
				'AdditionInfo.demand_flg' => 0
			);
		}

		// 追加施工案件のリストを取得
		if(Util::isMobile()  && $this->User['auth'] == 'affiliation') {
			$results = self::__get_addition_list_pagenate($conditions);
		} else {
			$results = self::__get_addition_list($conditions);
		}
		$demand_infos = self::__get_demand_info($demand_id);
		$results[0]['DemandInfo'] = $demand_infos[0]['DemandInfo'];
                
                //ジャンル一覧取得
                $genre_list = self::__get_genre_list();
                
		// 値をセット
		$this->set('results',$results);
		$this->set('demand_id', $demand_id);
		$this->set('genre_list', $genre_list);

		// モバイル端末用View判定 2015.05.10 y.tanaka
		if(Util::isMobile() && $this->User['auth'] == 'affiliation') {
			$this->layout = 'default_m';
			$this->render('index_m');
		}

	}

	/**
	 * 登録ボタン後
	 *
	 * @throws Exception
	 */
	public function regist($demand_id = null) {
		$this->set('demand_id', $demand_id);

		// エラーチェック
		if (!isset( $this->request->data )) {
			throw new Exception();
			return;
		}

		try {
			$this->AdditionInfo->begin();
			// 登録処理
			if ($this->__regist_addition($this->request->data)) {

				$this->AdditionInfo->commit ();
				$this->Session->setFlash(__('Updated', true), 'default', array('class' => 'message_inner alert alert-info'));

//				return $this->redirect('/addition');
				return ((strlen($this->request->data['demand_id']) == 0) ? $this->redirect('/addition') : $this->redirect('/addition/index/' . $this->request->data[demand_id]));
			}

			$this->AdditionInfo->rollback();

		} catch ( Exception $e ) {
			$this->AdditionInfo->rollback ();
		}

		$this->Session->setFlash(__('InputError', true), 'default', array('class' => 'error_inner alert alert-danger'));

		// 追加施工案件のリストを取得
		$conditions = array (
				'AdditionInfo.corp_id' => $this->User ["affiliation_id"],
				'AdditionInfo.demand_flg' => 0
		);

		// 追加施工案件のリストを取得
		if(Util::isMobile()  && $this->User['auth'] == 'affiliation') {
			$results = self::__get_addition_list_pagenate($conditions);
		} else {
			$results = self::__get_addition_list($conditions);
		}
		$results = $results + self::__get_demand_info(null);
                
                //ジャンル一覧取得
                $genre_list = self::__get_genre_list();
                
		// 値をセット
		$this->data = $this->request->data;
		$this->set('results',$results);
		$this->set('genre_list', $genre_list);

		// レイアウトの指定
		// モバイル端末用View判定 2015.05.10 y.tanaka
		if(Util::isMobile()  && $this->User['auth'] == 'affiliation') {
			$this->layout = 'default_m';
			$this->render('index_m');
		} else {
			$this->render ('index');
		}


	}

	/**
	 * 削除ボタン後
	 *
	 * @throws Exception
	 */
	public function delete($id = null, $demand_id = null) {

		// エラーチェック
		if(empty($id)){
			throw new Exception();
			return;
		}

		$data['AdditionInfo']['id'] = $id;
		$data['AdditionInfo']['del_flg'] = 1;

		// 追加施工案件更新
		if(self::__regist_addition($data)){
			return (is_null($demand_id) ? $this->redirect('/addition') : $this->redirect('/addition/index/' . $demand_id));
		}
	}


	/**
	 * 追加施工案件更新
	 *
	 * @param unknown $data
	 * @return boolean
	 */
	private function __regist_addition($data){

		$data['AdditionInfo']['corp_id'] = $this->User["affiliation_id"];

		if($this->AdditionInfo->save($data)){
			return true;
		}
		return false;

	}

	/**
	 * 追加施工案件のリストを取得
	 *
	 * @param unknown $conditions
	 * @return unknown
	 */
	private function __get_addition_list_pagenate($conditions = array()){

            $fields = '* , MGenre.genre_name';
            $joins = array (
                array (
                    'fields' => '*',
                    'type' => 'inner',
                    "table" => "m_genres",
                    "alias" => "MGenre",
                    "conditions" => array (
                        'and' => array(
                            "MGenre.id = AdditionInfo.genre_id",
                        )
                    )
                )
            );
            $orders = array (
                'AdditionInfo.id' => 'desc'
            );

            $list_limit = Configure::read('list_limit_mobile');
            $list_limit = 2;

            $this->paginate = array(
                'fields' => $fields
                ,'conditions' => $conditions
                ,'joins' => $joins
                ,'limit' => $list_limit
                ,'order' => $orders
            );

            $results = $this->paginate('AdditionInfo');

            return $results;
	}

	/**
	 * 追加施工案件のリストを取得
	 *
	 * @param unknown $conditions
	 * @return unknown
	 */
	private function __get_addition_list($conditions = array()){

            $results = $this->AdditionInfo->find ( 'all', array (
                'fields' => '* , MGenre.genre_name',
                'conditions' => $conditions,
                'joins' => array (
                    array (
                        'fields' => '*',
                        'type' => 'inner',
                        "table" => "m_genres",
                        "alias" => "MGenre",
                        "conditions" => array (
                            'and' => array(
                                "MGenre.id = AdditionInfo.genre_id",
                            )
                        )
                    )
                ),
                'order' => array (
                    'AdditionInfo.id' => 'desc'
                )
            ) );

            return $results;
	}

	/**
	 * ジャンルリストを取得
	 *
	 * @param unknown $conditions
	 * @return unknown
	 */
	private function __get_genre_list(){

            $results = $this->MGenre->find ( 'list', array (
                'fields' => 'genre_name',
                'conditions' => array(
                    "MGenre.valid_flg = 1",
                    "MGenre.commission_type <> 2",
                ),
                'order' => array (
                    'MGenre.genre_kana' => 'asc'
                )
            ) );

            return $results;
	}

	private function __get_demand_info($demand_id = null) {
		if (is_null($demand_id)) return array(array('DemandInfo' => array('id' => '', 'customer_name' => '')));

		return $this->DemandInfo->find('all',
			array('conditions' => array('id =' . $demand_id))
		);
	}

}
