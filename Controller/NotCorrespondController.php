<?php
App::uses('AppController', 'Controller');

class NotCorrespondController extends AppController{

// murata.s ORANGE-251 CHG(S)
	public $uses = array('NotCorrespondItem', 'NotCorrespondSearchExclution');
// murata.s ORANGE-251 CHG(E)
	public $helpers = array();
	public $components = array();

	/**
	 * 自動開拓施工エリア表示設定
	 */
	public function index() {

		if(isset($this->request->data['regist-item'])){
			// 自動開拓案件数更新
			if($this->__edit_NotCorrespondItem($this->request->data)){
				$this->Session->setFlash (__ ('Updated', true),
						'default', array('class' => 'message_inner')
				);
				$this->redirect('/not_correspond');
			}else{
				$this->Session->setFlash (__ ('FailRegist', true),
						'default', array('class' => 'error_inner')
				);
			}
		}else{
			// エリア対応加盟店なし案件設定
			$data = $this->NotCorrespondItem->find('first', array(
					'order' => array('id' => 'desc')
			));
			$this->data = $data;
		}
	}

	/**
	 * エリア対応加盟店なし案件設定項目を更新する
	 * @param array $data
	 */
	private function __edit_NotCorrespondItem($data){
		$result = true;

		try{
			if(!$this->NotCorrespondItem->save($data)){
				$result = false;
			}
		}catch(Exception $e){
			$result = false;
		}

		return $result;
	}

// murata.s ORANGE-251 ADD(S)
	/**
	 * 開拓企業自動メール設定
	 */
	public function mail_setting() {

		if(isset($this->request->data['regist-exclusion'])){
			// 除外ドメイン更新
			if($this->__edit_exclution($this->request->data)){
				$this->Session->setFlash (__ ('Updated', true),
						'default', array('class' => 'message_inner')
				);
				$this->redirect('/not_correspond/mail_setting');
			}else{
				$this->Session->setFlash (__ ('FailRegist', true),
						'default', array('class' => 'error_inner')
				);
			}
		}

		// 除外URL
		$excutions = $this->NotCorrespondSearchExclution->find('all', array(
				'order' => array('id')
		));

		$excution_array = array();
		foreach($excutions as $excution){
			$excution_array[] = $excution['NotCorrespondSearchExclution']['exclude_url'];
		}
		$this->data = array(
				'NotCorrespondSearchExclution' => array(
						'exclutions' => implode("\n", $excution_array)
				));
	}

	/**
	 * 除外ドメインを登録する
	 * @param array $data
	 */
	private function __edit_exclution($data){
		$result = true;

		try{
			$this->NotCorrespondSearchExclution->begin();

			// 除外ドメインの更新
			$data['NotCorrespondSearchExclution']['exclutions']
				= str_replace("\r", "", $data['NotCorrespondSearchExclution']['exclutions']);
			$exclutions = explode("\n", $data['NotCorrespondSearchExclution']['exclutions']);
			$save_datas = array();
			foreach($exclutions as $val){
				if(!empty($val)){
					$registed = $this->NotCorrespondSearchExclution->findByExcludeUrl($val);
					if(!empty($registed)){
						$save_data = $registed['NotCorrespondSearchExclution'];
					}else{
						$save_data = array('exclude_url' => trim($val));
					}
					$save_datas[] = $save_data;
				}
			}
			$this->NotCorrespondSearchExclution->deleteAll(array('1=1'));
			if(!empty($save_datas)){
				$result = $this->NotCorrespondSearchExclution->saveAll($save_datas);
			}

			if($result){
				$this->NotCorrespondSearchExclution->commit();
			}else{
				$this->NotCorrespondSearchExclution->rollback();
			}
		}catch(Exception $e){
			$this->NotCorrespondSearchExclution->rollback();
			$result = false;
		}

		return $result;
	}
// murata.s ORANGE-251 ADD(E)

}