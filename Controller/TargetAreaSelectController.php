<?php
App::uses('AppController', 'Controller');

class TargetAreaSelectController extends AppController {

	public $name = 'TargetAreaSelect';
	public $helpers = array();
	public $components = array('Session');
	public $uses = array('MCorpCategory', 'MTargetArea', 'MPost');

	public function beforeFilter(){

		$this->layout = 'subwin';

		parent::beforeFilter();
	}

	/**
	 * 初期表示（一覧）
	 *
	 * @param string $id  MCorpCategoryのID
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
				$this->MTargetArea->begin();
				// 企業別対応カテゴリマスタの登録
				$resultsFlg = $this->__edit_target_area($id , $this->request->data);

				if($resultsFlg){
					$this->Session->setFlash(__('Updated', true), 'default', array('class' => 'message_inner'));
					$this->MTargetArea->commit();
				} else {
					$this->MTargetArea->rollback();
					$this->Session->setFlash(__('InputError', true), 'default', array('class' => 'error_inner'));
				}
			} catch (Exception $e) {
				$this->MTargetArea->rollback();
				$this->Session->setFlash(__('InputError', true), 'default', array('class' => 'error_inner'));
			}
			// エリアのリストを取得
			$conditions['MPost.address1'] = $this->request->data['address1_text'];
			$list = $this->__get_target_area_list($id , $conditions);

			$this->data = $this->request->data;
			$this->set("list" , $list);

		} else if (isset($this->request->data['all_regist'])){

			// データを削除・登録処理
			try {
				$this->MTargetArea->begin();

				// 企業別対応カテゴリマスタの登録
				$resultsFlg = $this->__all_regist_target_area($id);

				// 企業別対応カテゴリマスタの登録
				if($resultsFlg){
					$this->Session->setFlash(__('Updated', true), 'default', array('class' => 'message_inner'));
					$this->MTargetArea->commit();
				} else {
					$this->MTargetArea->rollback();
					$this->Session->setFlash(__('InputError', true), 'default', array('class' => 'error_inner'));
				}
			} catch (Exception $e) {
				$this->MTargetArea->rollback();
				$this->Session->setFlash(__('InputError', true), 'default', array('class' => 'error_inner'));
			}
		}

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
				array('fields' => 'MPost.address2 , MPost.jis_cd , MTargetArea.id',
						'joins' =>  array(
								array('fields' => '*',
										'type' => 'LEFT',
										"table" => "m_target_areas",
										"alias" => "MTargetArea",
										"conditions" => array("MTargetArea.jis_cd = MPost.jis_cd" , "MTargetArea.corp_category_id = ".$id)
								),
						),
						'conditions' => $conditions ,
						'group' => 'MPost.jis_cd , MPost.address2 , MTargetArea.id',
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
		$conditions = array('MTargetArea.corp_category_id' =>  $id ,
						'MTargetArea.jis_cd >=' =>  $conditions_results[0]['min'] ,
						'MTargetArea.jis_cd <=' =>  $conditions_results[0]['max'] ,
					  );

// 		if(empty($data['jis_cd'])){
// 			return false;
// 		}
		if(isset($data['jis_cd'])){
			foreach ($data['jis_cd'] as $val){
				$conditions['MTargetArea.jis_cd !='][] = $val;
			}
		}

		// 削除
		$resultsFlg = $this->MTargetArea->deleteAll($conditions, false);

		if(!empty($data['jis_cd'])){
			// 対応可能地域の登録
			foreach ($data['jis_cd'] as $val){
				$set_data['id'] = $data[$val];
				$set_data['corp_category_id'] = $id;
				$set_data['jis_cd'] = $val;
				$set_data['address1_cd'] = substr($val, 0, 2);
				$save_data[] = array('MTargetArea' => $set_data);
			}

			if ( !empty($save_data) ){
				// 登録
				if ( $this->MTargetArea->saveAll($save_data) ) {
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
		$conditions = array('MTargetArea.corp_category_id' =>  $id );

		$regist_already_data = $this->MTargetArea->find('all',
				array('fields' => '*',
						'conditions' => $conditions ,
				)
		);

		$conditions = array();
		foreach ($regist_already_data as $val){
			$conditions['MPost.jis_cd !='][] = $val['MTargetArea']['jis_cd'];
		}

		$results = $this->MPost->find('all',
				array('fields' => 'MPost.jis_cd',
						'joins' =>  array(
								array('fields' => '*',
										'type' => 'LEFT',
										"table" => "m_target_areas",
										"alias" => "MTargetArea",
										"conditions" => array("MTargetArea.jis_cd = MPost.jis_cd" , "MTargetArea.corp_category_id = ".$id)
								),
						),
						'conditions' => $conditions ,
						'group' => 'MPost.jis_cd , MPost.address2 , MTargetArea.id',
						'order' => array('MPost.jis_cd'=> 'asc'),
				)
		);

		foreach ($results as $v){
			$data['MTargetArea']['id'] = null;
			$data['MTargetArea']['corp_category_id'] = $id;
			$data['MTargetArea']['jis_cd'] = $v['MPost']['jis_cd'];
			$data['MTargetArea']['address1_cd'] = substr($v['MPost']['jis_cd'], 0, 2);
			$save_data[] = array('MTargetArea' => $data['MTargetArea']);
		}

		// 登録
		if ( !empty($save_data) ){
			if ( $this->MTargetArea->saveAll($save_data) ) {
				return true;
			} else {
				return false;
			}
		} else {
			return true;
		}
	}

}
