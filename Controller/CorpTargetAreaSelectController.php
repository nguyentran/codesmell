<?php
App::uses('AppController', 'Controller');

class CorpTargetAreaSelectController extends AppController {

	public $name = 'CorpTargetAreaSelect';
	public $helpers = array();
	public $components = array('Session');
	public $uses = array('MCorp', 'MCorpTargetArea', 'MPost');

	public function beforeFilter(){

		$this->layout = 'subwin';

		parent::beforeFilter();
	}

	/**
	 * 初期表示（一覧）
	 *
	 * @param string $id  MCorpのID
	 * @throws Exception
	 */
	public function index($id = null) {

		if ($id == null) {
			throw new Exception();
		}

		$resultsFlg = true;

		if (isset($this->request->data['regist'])){

			// データを削除・登録処理
			try {
				$this->MCorpTargetArea->begin();
				// 企業別対応カテゴリマスタの登録
				$resultsFlg = $this->__edit_target_area($id , $this->request->data);

				if($resultsFlg){
					$this->Session->setFlash(__('Updated', true), 'default', array('class' => 'message_inner'));
					$this->MCorpTargetArea->commit();
				} else {
					$this->MCorpTargetArea->rollback();
					$this->Session->setFlash(__('InputError', true), 'default', array('class' => 'error_inner'));
				}
			} catch (Exception $e) {
				$this->MCorpTargetArea->rollback();
				$this->Session->setFlash(__('InputError', true), 'default', array('class' => 'error_inner'));
				var_dump($e);
			}
			// エリアのリストを取得
			$conditions['MPost.address1'] = $this->request->data['address1_text'];
			$list = $this->__get_target_area_list($id , $conditions);

			$this->data = $this->request->data;
			$this->set("list" , $list);

		} else if (isset($this->request->data['all_regist'])){

			// データを削除・登録処理
			try {
				$this->MCorpTargetArea->begin();

				// 企業別対応カテゴリマスタの登録
				$resultsFlg = $this->__all_regist_target_area($id);

				// 企業別対応カテゴリマスタの登録
				if($resultsFlg){
					$this->Session->setFlash(__('Updated', true), 'default', array('class' => 'message_inner'));
					$this->MCorpTargetArea->commit();
				} else {
					$this->MCorpTargetArea->rollback();
					$this->Session->setFlash(__('InputError', true), 'default', array('class' => 'error_inner'));
				}
			} catch (Exception $e) {
				$this->MCorpTargetArea->rollback();
				$this->Session->setFlash(__('InputError', true), 'default', array('class' => 'error_inner'));
			}

		// 2015.6.19 y.fujimori ADD start ORANGE-622
		} else if (isset($this->request->data['all_remove'])){
			// データを削除・登録処理
			try {
				$this->MCorpTargetArea->begin();

				// 企業別対応カテゴリマスタの登録
				$resultsFlg = $this->__all_remove_target_area($id);

				// 企業別対応カテゴリマスタの登録
				if($resultsFlg){
					$this->Session->setFlash(__('Updated', true), 'default', array('class' => 'message_inner'));
					$this->MCorpTargetArea->commit();
				} else {
					$this->MCorpTargetArea->rollback();
					$this->Session->setFlash(__('InputError', true), 'default', array('class' => 'error_inner'));
				}
			} catch (Exception $e) {
				$this->MCorpTargetArea->rollback();
				$this->Session->setFlash(__('InputError', true), 'default', array('class' => 'error_inner'));
			}
		}
		// 2015.6.19 y.fujimori ADD end ORANGE-622

		$this->set("id" , $id);

	}

	/**
	 * エリアのリストを取得
	 *
	 * @param string $id
	 * @param unknown $conditions
	 * @return unknown
	 */
	private function __get_target_area_list($id = null , $conditions = array()){

		$results = $this->MPost->find('all',
				array('fields' => 'MPost.address2 , MPost.jis_cd , MCorpTargetArea.corp_id',
						'joins' =>  array(
								array('fields' => '*',
										'type' => 'LEFT',
										"table" => "m_corp_target_areas",
										"alias" => "MCorpTargetArea",
										"conditions" => array("MCorpTargetArea.jis_cd = MPost.jis_cd" , "MCorpTargetArea.corp_id = ".$id)
								),
						),
						'conditions' => $conditions ,
						'group' => 'MPost.jis_cd , MPost.address2 , MCorpTargetArea.corp_id',
						'order' => array('MPost.jis_cd'=> 'asc'),
				)
		);

		return $results;
	}

	/**
	 * 対応可能地域の登録・削除
	 *
	 * @param unknown $id
	 * @param unknown $data
	 * @return boolean
	 */
	private function __edit_target_area($id , $data){

		// 指定の都道府県のjis_cdの範囲を取得
		$conditions = array('MPost.address1' => $data['address1_text']);
		$conditions_results = $this->MPost->find('first',
				array('fields' => 'max(MPost.jis_cd) , min(MPost.jis_cd)',
					'conditions' => $conditions ,
				)
		);

		if(empty($conditions_results) || empty($id)) return false;

		// 対応可能地域の削除
		$conditions = array('MCorpTargetArea.corp_id' =>  $id ,
						'MCorpTargetArea.jis_cd >=' =>  $conditions_results[0]['min'] ,
						'MCorpTargetArea.jis_cd <=' =>  $conditions_results[0]['max'] ,
					  );

// 		if(empty($data['jis_cd'])){
// 			return false;
// 		}
		// 2015.08.06 s.harada DEL start ORANGE-713
		/*
		if(isset($data['jis_cd'])){
			foreach ($data['jis_cd'] as $val){
				$conditions['MCorpTargetArea.jis_cd !='][] = $val;
			}
		}
		*/
		// 2015.08.06 s.harada DEL start ORANGE-713

		// 削除
		$resultsFlg = $this->MCorpTargetArea->deleteAll($conditions, false);

		if(!empty($data['jis_cd'])){
			// 対応可能地域の登録
			foreach ($data['jis_cd'] as $val){
				// 2015.08.06 s.harada MOD start ORANGE-713
				// $set_data['id'] = $data[$val];
				$set_data = array();
				// 2015.08.06 s.harada MOD end ORANGE-713
				$set_data['corp_id'] = $id;
				$set_data['jis_cd'] = $val;
				$save_data[] = array('MCorpTargetArea' => $set_data);
			}

			if ( !empty($save_data) ){
				// 登録
				if ( $this->MCorpTargetArea->saveAll($save_data) ) {
					return true;
				} else {
					return false;
				}
			}
		}
		return true;
	}

	/**
	 * 対応可能地域の全登録
	 *
	 * @param unknown $id
	 */
	private function __all_regist_target_area($id){

		// 対応可能地域の削除
		$conditions = array('MCorpTargetArea.corp_id' =>  $id );

		$regist_already_data = $this->MCorpTargetArea->find('all',
				array('fields' => '*',
						'conditions' => $conditions ,
				)
		);

		$conditions = array();
		foreach ($regist_already_data as $val){
			$conditions['MPost.jis_cd !='][] = $val['MCorpTargetArea']['jis_cd'];
		}

		$results = $this->MPost->find('all',
				array('fields' => 'MPost.jis_cd',
						'joins' =>  array(
								array('fields' => '*',
										'type' => 'LEFT',
										"table" => "m_corp_target_areas",
										"alias" => "MCorpTargetArea",
										"conditions" => array("MCorpTargetArea.jis_cd = MPost.jis_cd" , "MCorpTargetArea.corp_id = ".$id)
								),
						),
						'conditions' => $conditions ,
						'group' => 'MPost.jis_cd , MPost.address2 , MCorpTargetArea.id',
						'order' => array('MPost.jis_cd'=> 'asc'),
				)
		);

		foreach ($results as $v){
			$data['MCorpTargetArea']['id'] = null;
			$data['MCorpTargetArea']['corp_id'] = $id;
			$data['MCorpTargetArea']['jis_cd'] = $v['MPost']['jis_cd'];
			$save_data[] = array('MCorpTargetArea' => $data['MCorpTargetArea']);
		}

		// 登録
		if ( !empty($save_data) ){
			if ( $this->MCorpTargetArea->saveAll($save_data) ) {
				return true;
			} else {
				return false;
			}
		} else {
			return true;
		}
	}


	// 2015.6.19 y.fujimori ADD start ORANGE-622
	/**
	 * 対応可能地域の全削除
	 *
	 * @param unknown $id
	 */
	private function __all_remove_target_area($id){

		// 対応可能地域の削除
		$conditions = array('MCorpTargetArea.corp_id' =>  $id );

		// 		if(empty($data['jis_cd'])){
		// 			return false;
		// 		}
		if(isset($data['jis_cd'])){
			foreach ($data['jis_cd'] as $val){
				$conditions['MCorpTargetArea.jis_cd !='][] = $val;
			}
		}

		// 削除
		$resultsFlg = $this->MCorpTargetArea->deleteAll($conditions, false);


		// 登録
		if ( !empty($save_data) ){
			if ( $this->MCorpTargetArea->saveAll($save_data) ) {
				return true;
			} else {
				return false;
			}
		} else {
			return true;
		}
	}
	// 2015.6.19 y.fujimori ADD end ORANGE-622

}
