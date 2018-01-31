<?php
App::uses('Controller', 'Controller');

class AppLoginController extends Controller {

//	public $helpers = array('Session');
	public $components = array('Session','RequestHandler','Cookie',
		'Auth' => array(
			'authenticate' => array(
				'Form' => array(
					'userModel' => 'MUser',
					'fields' => array('username' => 'user_id', 'password'=>'password')
				)
			),
//			'loginAction' => array('controller' => 'applogin','action' => 'app_login'),
//			'logoutAction' => array('controller' => 'signin','action' => 'signout'),
			)
	);

	public $name = 'AppLogin';
//	public $uses = array('Auth');
	public $autoRender = false;

	public function beforeFilter(){
	}

	public function app_login($userid = null, $password = null) {

		$this->request->data = array(
				'username' => $userid,
				'password' => $password
			);
		if($this->Auth->login()) {
			$session_id = $this->Session->id;
			return $session_id;
		} else {
			return false;
		}
	}
}
