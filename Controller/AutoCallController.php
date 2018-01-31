<?php
App::uses('AppController', 'Controller');

class AutoCallController extends AppController {

	public $uses = array('AutoCallItem');

	/**
	 * 初期表示
	 */
	public function index() {

		if(isset($this->request->data['regist'])){
			if($this->__edit_AutoCallItem($this->request->data)){
				$this->Session->setFlash(__('Updated', true), 'default', array('class' => 'message_inner'));
				$this->redirect('/auto_call');
			}else{
				$this->Session->setFlash(__('FailRegist', true), 'default', array('class' => 'error_inner'));
			}
		}else{
			// オートコール設定
			$data = $this->AutoCallItem->find('first');
			$this->data = $data;
		}
	}

	/**
	 * オートコール設定項目を更新する
	 * @param unknown $data
	 */
	private function __edit_AutoCallItem($data){
		$result = true;
		try{
			if(!$this->AutoCallItem->save($data)){
				$result = false;
			}
		}catch(Exception $e){
			$result = false;
		}

		return $result;
	}
}