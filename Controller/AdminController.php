<?php
App::uses('AppController', 'Controller');

class AdminController extends AppController {

	public $name = 'Admin';
	public $helpers = array();
	public $components = array();
	public $uses = array();

	//ログインが必要な処理か？
	//public $is_auth_page = true;

	public function beforeFilter(){
		parent::beforeFilter();
	}

	public function index() {

	}
}
