<?php
App::uses('AppShell', 'Console/Command');

class DeleteTempFileShell extends AppShell {


	/**
	 * 作成日から24時間以前の一時ファイルを削除する。
	 */
	public function main() {

		$expire = strtotime("24 hours ago");

		$dir = Configure::read('temporary_file_path');

		$list = scandir($dir);
		foreach($list as $value){
			$file = $dir . $value;
			if(!is_file($file)) continue;
			$mod = filemtime( $file );
			if($mod < $expire){
				unlink($file);
			}
		}


	}

}