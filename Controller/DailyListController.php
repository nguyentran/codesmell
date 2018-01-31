<?php
App::uses('AppController', 'Controller');

class DailyListController extends AppController {

	public $name = 'DailyList';

	public function index() {
// 2016.10 iwai webroot 参照パス変更 CHG S
		$default_display = true;
			$this->set ( 'default_display', $default_display );
			//案件一覧当月ファイル先
			$dir_1 = WWW_ROOT."list1/";
			$files_1 = Array();
			$no = Array();
			;
			if (is_dir($dir_1) && $handle = opendir($dir_1)) {
				while (($file = readdir($handle)) != false) {
					if (filetype($path = $dir_1 . $file) == "file") {
						$files_1[]=$file;
					}
				}
				$this->set ( 'files_1', $files_1);
		}
		//案件一覧ALLファイル先
		$dir_2=WWW_ROOT."list2/";
		$files_2=Array();
		if(is_dir($dir_2)&&$handle=opendir($dir_2) ) {
			while (($file = readdir($handle)) != false) {
				if (filetype($path = $dir_2 . $file) == "file") {
					$files_2[]=$file;
				}
			}
			$this->set ( 'files_2', $files_2);
		}
		//加盟店一覧ファイル先
		$dir_3=WWW_ROOT."list3/";
		$files_3=Array();
		if(is_dir($dir_3)&&$handle=opendir($dir_3) ) {
			while (($file = readdir($handle)) != false) {
				if (filetype($path = $dir_3 . $file) == "file") {
					$files_3[]=$file;
				}
			}
			$this->set ( 'files_3', $files_3);
		}
		//未加盟店一覧ファイル先
		$dir_4=WWW_ROOT."list4/";
		$files_4=Array();
		if(is_dir($dir_4)&&$handle=opendir($dir_4) ) {
			while (($file = readdir($handle)) != false) {
				if (filetype($path = $dir_4 . $file) == "file") {
					$files_4[]=$file;
				}
			}
			$this->set ( 'files_4', $files_4);
		}
		//進捗一覧ファイル先
		$dir_5=WWW_ROOT."list5/";
		$files_5=Array();
		if(is_dir($dir_5)&&$handle=opendir($dir_5) ) {
			while (($file = readdir($handle)) != false) {
				if (filetype($path = $dir_5 . $file) == "file") {
					$files_5[]=$file;
				}
			}
			$this->set ( 'files_5', $files_5);
		}
		//加盟店契約一覧ファイル先
		$dir_6=WWW_ROOT."list6/";
		$files_6=Array();
		if(is_dir($dir_6)&&$handle=opendir($dir_6) ) {
			while (($file = readdir($handle)) != false) {
				if (filetype($path = $dir_6 . $file) == "file") {
					$files_6[]=$file;
				}
			}
			$this->set ( 'files_6', $files_6);
		}
		// ORANGE-134 iwai ADD S
		//加盟店契約一覧ファイル先
		$dir_7=WWW_ROOT."list7/";
		$files_7=Array();
		if(is_dir($dir_7)&&$handle=opendir($dir_7) ) {
			while (($file = readdir($handle)) != false) {
				if (filetype($path = $dir_7 . $file) == "file") {
					$files_7[]=$file;
				}
			}
			$this->set ( 'files_7', $files_7);
		}
		// ORANGE-134 iwai ADD E
		// ORANGE-214 iwai ADD S
		//加盟店契約一覧ファイル先
		$dir_8=WWW_ROOT."list8/";
		$files_8=Array();
		if(is_dir($dir_8)&&$handle=opendir($dir_8) ) {
			while (($file = readdir($handle)) != false) {
				if (filetype($path = $dir_8 . $file) == "file") {
					$files_8[]=$file;
				}
			}
			$this->set ( 'files_8', $files_8);
		}
		// ORANGE-214 iwai ADD E
		// ORANGE-279 ADD S
		$dir_9=WWW_ROOT."list9/";
		$files_9=Array();
		if(is_dir($dir_9)&&$handle=opendir($dir_9) ) {
			while (($file = readdir($handle)) != false) {
				if (filetype($path = $dir_9 . $file) == "file") {
					$files_9[]=$file;
				}
			}
			$this->set ( 'files_9', $files_9);
		}
		// ORANGE-279 ADD E

// 2016.10 iwai Webroot参照パス変更 CHG E
	}

}
