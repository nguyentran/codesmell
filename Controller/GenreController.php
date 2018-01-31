<?php
App::uses('AppController', 'Controller');

class GenreController extends AppController {

	public $name = 'Genre';
	// 2016.06.13 ota.r@tobila MOD START ORANGE-88 経理管理者の使用できる管理者機能を制限
//	public $helpers = array();
	public $helpers = array(
			'Form' => array(
					'className' => 'AdminForm',
			),
	);
	// 2016.06.13 ota.r@tobila MOD END ORANGE-88 経理管理者の使用できる管理者機能を制限
	public $components = array('Session', 'RequestHandler');
	public $uses = array('MGenre');

	public function beforeFilter(){

		$this->set('default_display', false);

		parent::beforeFilter();
	}


	// 初期表示（一覧）
	public function index() {
		if (isset ( $this->request->data ['regist'] )) { // 登録
			/*
			 if (self::__check_modified_category ( $this->request->data ['MCorpCategory'] [0] ['id'], $this->request->data ['MCorpCategory'] [0] ['modified'] )) {

			 } else {
			 $this->Session->setFlash ( __ ( 'ModifiedNotCheck', true ), 'default', array (
			 'class' => 'error_inner'
			 ) );
			 }
			 */
			try {
				$this->MGenre->begin ();
				// 企業別対応カテゴリマスタの登録
				$resultsFlg = $this->__edit_genre($this->request->data );
				if ($resultsFlg) {
					$this->MGenre->commit ();
					//
					$this->Session->setFlash(__('Updated', true), 'default', array('class' => 'message_inner'));
				} else {
					$this->MGenre->rollback ();
					$this->set ( "errorData" , $this->request->data );
					$this->set( "error" , true );
					//
					$this->Session->setFlash ( __ ( 'InputError', true ), 'default', array (
							'class' => 'error_inner'
					) );
				}
			} catch ( Exception $e ) {
				$this->MGenre->rollback ();
				//
				$this->Session->setFlash ( __ ( 'InputError', true ), 'default', array (
						'class' => 'error_inner'
				) );
			}
		} else if (isset ( $this->request->data ['cancel'] )) { // キャンセル

			return $this->redirect ( '/admin/' . $id );

		}

		// 変数初期化
		$all_genre = array ();

		// ALLカテゴリ 検索する
		$all_genre = $this->MGenre->find('all',array(
											'order' => array('MGenre.genre_group', 'MGenre.genre_name', 'MGenre.id')
										));
		$this->set ( "all_genre", $all_genre );

	}

	/**
	 * ジャンルマスタの登録
	 *
	 * @param unknown $data
	 */
	private function __edit_genre($data) {

		$del_id_array = array();
		$chk_id = $data['chk_id'];
		if (strlen($chk_id) > 0) {
			$del_id_array = split('-', $chk_id);
		}

		//2016.02.25 k.iwai ORANGE-1247 CHG(s)
		// ALLカテゴリ 検索する
		$all_genre = $this->MGenre->find('all');
		//2016.02.25 k.iwai ORANGE-1247 CHG(e)

		$save_data = array();
		foreach ( $data ['MGenre'] as $v ) {

			if (!isset($v['id'])) {
				continue;
			}

			// 削除用id配列から除外
			foreach ($del_id_array as $key => $value) {
				if ($value == $v['id']) {
					if (!isset($v['registration_mediation'])) {
						$v['registration_mediation'] = 0;
					}
					break;
				}
			}

			if (isset($v['registration_mediation'])) {
				$save_data[] = array (
						'MGenre' => $v
				);
			}

//2016.02.25 k.iwai ORANGE-1247 CHG(s)
			foreach ($all_genre as $value) {
				//変更があったIDのみ更新
				if ($value['MGenre']['id'] == $v['id']){
// 2016.07.11 murata.s ORANGE-1 CHG(S)
// 					if((string)$value['MGenre']['credit_unit_price'] != (string)$v['credit_unit_price']) {
// 							$save_data[] = array (
// 								'MGenre' => array(
// 									'id' => $v['id'],
// 									'credit_unit_price' => $v['credit_unit_price'],
// 									'modified' => date('Y-m-d H:i:s')
// 								)
// 						);
// 					}
					// murata.s ORANGE-537 CHG(S)
					if((string)$value['MGenre']['credit_unit_price'] != (string)$v['credit_unit_price']
							|| (string)$value['MGenre']['auction_fee'] != (string)$v['auction_fee']
							|| (string)$value['MGenre']['auto_call_flag'] != (string)$v['auto_call_flag']) {
							$save_data[] = array (
								'MGenre' => array(
									'id' => $v['id'],
									'credit_unit_price' => $v['credit_unit_price'],
									'auction_fee' => $v['auction_fee'],
									'auto_call_flag' => $v['auto_call_flag'],
									'modified' => date('Y-m-d H:i:s')
								)
						);
					}
					// murata.s ORANGE-537 CHG(E)
					// 2016.07.11 murata.s ORANGE-1 CHG(E)
				}
			}
//2016.02.25 k.iwai ORANGE-1247 CHG(e)
		}

		if (! empty ( $save_data )) {

			// 登録
			if ($this->MGenre->saveAll ( $save_data )) {
				return true;
			}
			return false;
		} else {
			return true;
		}
	}

}
