<?php
App::uses('AppController', 'Controller');

class DownloadController extends AppController {

	public $name = 'Download';
	public $helpers = array();
	public $components = array();
	public $uses = array();

	public function beforeFilter(){

		parent::beforeFilter();
	}


	public function index($target, $file_name) {

		$file_path = '';

		switch ($target) {

			case 'registration':
				$file_path = Configure::read('registration_file_path') . $file_name;
				break;

			case 'estimate':
				$file_path = Configure::read('estimate_file_path') . $file_name;
				break;

			case 'receipt':
				$file_path = Configure::read('receipt_file_path') . $file_name;
				break;
		}

		// 出力
		header ("Content-type: application/octet-stream");
		header ("Content-disposition: attachment; filename=" . $file_name);
		readfile($file_path);
 		exit;

	}

}
