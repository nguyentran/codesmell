<?php
App::uses('AppController', 'Controller');

class AuthInfosController extends AppController {

	public $uses = array ('AuthInfo', 'AuthInfoLog');
	private $__salt = '';
	private $__expiration_limit = 5; //分

	/**
	 * 契約システムとの連携
	 */
	public function agreement_link(){

		if($this->request->is('post') || $this->request->is('put')){
			try{
				$save_data = array();
				$user = $this->Auth->user();
				$save_data['AuthInfo']['create_system'] = 'ORANGE';
				$save_data['AuthInfo']['use_system'] = 'AGREEMENT';
				$save_data['AuthInfo']['create_user_id'] = $user['id'];
				$save_data['AuthInfo']['modifie_user_id'] = $user['id'];
				$save_data['AuthInfo']['user_id'] = $user['id'];
				$save_data['AuthInfo']['expiration_date'] = date('Y-m-d H:i:s', strtotime('+'.$this->__expiration_limit.' minute'));

				while(true){
					$save_data['AuthInfo']['key'] = $this->__create_key($save_data['AuthInfo']['user_id'].':'.microtime());
					$this->log($save_data['AuthInfo']['key'], 'error');
					if($this->__check_unique($save_data['AuthInfo']['key']))break;
				}

				if($this->__save_auth_info($save_data)){
					$this->redirect(Configure::read('agreement_login') .$save_data['AuthInfo']['user_id'].'&key='.$save_data['AuthInfo']['key']);
				}
			}catch(Exception $e){
				$this->log($e->getMessage(), 'error');
				throw new ApplicationException(__('NoReferenceAuthority', true));
			}
		}else{
			throw new ApplicationException(__('NoReferenceAuthority', true));
		}
	}

	/**
	 * 認証KEYの作成
	 */
	private  function __create_key($val=null){
		return Security::hash( $val, 'sha256', true);
	}

	/**
	 * 認証情報登録
	 */
	private function __save_auth_info($save_data=null){
		if($this->AuthInfo->save($save_data)){
			$save_data['AuthInfo']['auth_info_id'] = $this->AuthInfo->getLastInsertID();
			$save_data['AuthInfoLog'] = $save_data['AuthInfo'];
			return $this->AuthInfoLog->save($save_data);
		}
		return false;
	}

	/**
	 * 認証KEYの重複確認
	 */
	private function __check_unique($key){
		$conditions = array('AuthInfo.key' => $key);
		if($this->AuthInfo->find('count', array('conditions' => $conditions)) == 0){
			return true;
		}
		return false;
	}


}
?>