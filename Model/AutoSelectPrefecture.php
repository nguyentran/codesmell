<?php
class AutoSelectPrefecture extends AppModel {

	public $validate = array();




	/**
	 * ジャンルIDに紐づくデータを取得
	 * @param unknown $genre_id
	 * @return unknown
	 */
	public function getAutoSelectPrefecture($genre_id){

		$list = $this->find('all', array('conditions' => array('genre_id' => $genre_id)));

		return $list;

	}


	public $csvFormat = array(
	);
}