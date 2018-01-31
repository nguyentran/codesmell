<?php
App::uses('AppController', 'Controller');

class TopController extends AppController {

	public $name = 'Top';
	public $helpers = array();
	public $components = array();
	public $uses = array();

	//ログインが必要な処理か？
	//public $is_auth_page = true;

	public function beforeFilter(){
		parent::beforeFilter();
		$this->User = $this->Auth->user();
	}

	public function index() {

		if($this->User["auth"] == "affiliation"){
			if(Util::isMobile()) {
				// 2017.08.14 e.takeuchi@SharingTechnology ORANGE-489 CHG(S)
				return $this->redirect('/auction');
				// return $this->redirect('/commission');
				// 2017.08.14 e.takeuchi@SharingTechnology ORANGE-489 CHG(E)
			} else {
				// 2017.08.14 e.takeuchi@SharingTechnology ORANGE-489 CHG(S)
				return $this->redirect('/auction');
				// return $this->redirect('/trader');
				// 2017.08.14 e.takeuchi@SharingTechnology ORANGE-489 CHG(E)
			}
			return;
		}

	}
}
