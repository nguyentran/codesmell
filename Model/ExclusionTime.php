<?php
class ExclusionTime extends AppModel {

	public $validate = array(

		'exclusion_time_from' => array(
			'ExclusionTimeFromError' => array(
				'rule' => 'checkExclusionTimeFrom',
			),
			'NotTime' => array(
				'rule' => array( 'time'),
				'last' => true,
				'allowEmpty' => true
			),
		),

		'exclusion_time_to' => array(
			'ExclusionTimeToError' => array(
				'rule' => 'checkExclusionTimeTo',
			),
			'NotTime' => array(
				'rule' => array( 'time'),
				'last' => true,
				'allowEmpty' => true
			),
		),
	);

	public function checkExclusionTimeFrom(){
		if(empty($this->data['ExclusionTime']['exclusion_time_from'])){
			if(!empty($this->data['ExclusionTime']['exclusion_time_to'])){
				return false;
			}
		}
		return true;
	}

	public function checkExclusionTimeTo(){
		if(empty($this->data['ExclusionTime']['exclusion_time_to'])){
			if(!empty($this->data['ExclusionTime']['exclusion_time_from'])){
				return false;
			}
		}
		return true;
	}

	/**
	 * カテゴリ一覧(SelectBox用)を返します
	 *
	 */
	public function getList(){

		$conditions = array();

		$list = $this->find('all', array('conditions'=>$conditions, 'order'=>'ExclusionTime.pattern asc'));

		// リストの作成
		foreach ($list as $val) {

			$list_[] = array(
					"ExclusionTime" => array(
							"pattern" => $val['ExclusionTime']['pattern'],
							"name" => 'パターン'.$val['ExclusionTime']['pattern']
					)
			);
		}

		$data = Set::Combine(@$list_, '{n}.ExclusionTime.pattern', '{n}.ExclusionTime.name');
		return $data;
	}


	/**
	 * ジャンルおよび都道府県に紐づく除外設定を取得します。
	 * @param unknown $genre_cd
	 * @param unknown $prefecture_cd
	 */
	public function getData($genre_id, $prefecture_cd) {

		// オークション地域別詳細テーブルより除外時間を取得
		$conditions = array('AuctionGenreArea.genre_id' => $genre_id, 'prefecture_cd' => $prefecture_cd);

		$results = $this->find ( 'first', array (
				'fields' => '*',
				'conditions' => $conditions,
				'joins' => array (
						array (
								'fields' => '*',
								'type' => 'inner',
								"table" => "auction_genre_areas",
								"alias" => "AuctionGenreArea",
								"conditions" => array (
										"ExclusionTime.pattern = AuctionGenreArea.exclusion_pattern",
								)
						)
				)
		) );

		if (!empty($results)) {
			return $results;
		}

		// オークション地域別詳細テーブルで見つからない場合、オークションジャンル情報テーブルより除外時間を取得
		$conditions = array('AuctionGenre.genre_id' => $genre_id);

		$results = $this->find ( 'first', array (
				'fields' => '*',
				'conditions' => $conditions,
				'joins' => array (
						array (
								'fields' => '*',
								'type' => 'inner',
								"table" => "auction_genres",
								"alias" => "AuctionGenre",
								"conditions" => array (
										"ExclusionTime.pattern = AuctionGenre.exclusion_pattern",
								)
						)
				)
		) );

		return $results;

	}
}
