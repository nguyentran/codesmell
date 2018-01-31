<?php
class SelectGenrePrefecture extends AppModel {

	public $validate = array(
		'business_trip_amount' => array(
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'last' => true,
				'allowEmpty' => true
			),
		),
// 2016.09.29 murata.s ORANGE-192 ADD(S)
		'auction_fee' => array(
				'NoNumeric' => array(
						'rule' => array('numeric'),
						'last'=> true,
						'allowEmpty' => true
				)
		),
// 2016.09.29 murata.s ORANGE-192 ADD(E)
	);

	/**
	 * ジャンルIDに紐づくデータを取得
	 * @param unknown $genre_id
	 * @return unknown
	 */
	public function getSelectGenrePrefecture($genre_id){
		$list = $this->find('all', array('conditions' => array('genre_id' => $genre_id)));
		return $list;
	}

	public $csvFormat = array(
	);
}