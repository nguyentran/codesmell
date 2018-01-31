<?php
App::uses('AppController', 'Controller');

class GuidelineController extends AppController {
	public function beforeFilter(){
		parent::beforeFilter();
		$this->Auth->allow('index');
	}

	public function index(){
		$this->layout = 'ajax';
		if(Util::isMobile()) {
			$this->render('index_m');
		}
	}
}
?>