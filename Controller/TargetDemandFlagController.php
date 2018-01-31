<?php
App::uses('AppController', 'Controller');

class TargetDemandFlagController extends AppController {

	public $name = 'TargetDemandFlag';
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


	// 一覧
	public function index() {
		if (isset ( $this->request->data ['regist'] )) { // 登録
			try {
				$this->MGenre->begin();
				$old_list = explode(",", $this->request->data["MGenre"]["old_list"]);
				$checked_data = array();
				if(isset($this->request->data["MGenre"]["new"])){
					$checked_data = (array)$this->request->data["MGenre"]["new"];
				}
				$save_data = array();
				foreach(array_diff($old_list, $checked_data) as $deleted){
					if($deleted == "") continue;
					$save_data[] = array("id" => $deleted, "exclusion_flg" => "0");
				}
				foreach(array_diff($checked_data, $old_list) as $added){
					if($added == "") continue;
					$save_data[] = array("id" => $added, "exclusion_flg" => "1");
				}
				$resultsFlg = $this->MGenre->saveAll($save_data);

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
		}
		// 変数初期化
		$all_genre = array ();

		// ALLカテゴリ 検索する
		$all_genre = $this->MGenre->find('all',array(
											'conditions' => array('MGenre.valid_flg = 1'),
											'order' => array('MGenre.genre_name asc')
										));

		$checked_list = array();
		foreach($all_genre as $g){
			if($g["MGenre"]["exclusion_flg"]){
				$checked_list[] = $g["MGenre"]["id"];
			}
		}


		$c = count($all_genre);
		$this->set ( "all_genres", array_chunk($all_genre, $c / 2 + ($c % 2 == 0 ? 0 : 1)));
		$this->set ( "checked_list", $checked_list );

	}

}
