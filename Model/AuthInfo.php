<?php
class AuthInfo extends AppModel{

	private $__expiration_limit = 5; //分

	/**
	 * 認証キー作成
	 */
	public function create_auth_key($user_id){

		try{
			$save_data = array();
			$save_data['AuthInfo']['create_system'] = 'ORANGE';
			$save_data['AuthInfo']['use_system'] = 'AGREEMENT';
			$save_data['AuthInfo']['user_id'] = $user_id;
			$save_data['AuthInfo']['expiration_date'] = date('Y-m-d H:i:s', strtotime('+'.$this->__expiration_limit.' minute'));
			$save_data['AuthInfo']['create_user_id'] = $user_id;
			$save_data['AuthInfo']['modifie_user_id'] = $user_id;

			while(true){
				$save_data['AuthInfo']['key'] = $this->__create_key($save_data['AuthInfo']['user_id'].':'.microtime());
				if($this->__check_unique($save_data['AuthInfo']['key']))break;
			}

			if($this->__save_auth_info($save_data)){
				return $save_data['AuthInfo']['key'];
			}

			return null;
		}catch(Exception $e){
			$this->log($e->getMessage(), 'error');
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
		if($this->save($save_data)){
			App::import('Model', 'AuthInfoLog');
			$this->AuthInfoLog = new AuthInfoLog;

			$save_data['AuthInfo']['auth_info_id'] = $this->getLastInsertID();
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
		if($this->find('count', array('conditions' => $conditions)) == 0){
			return true;
		}
		return false;
	}

}