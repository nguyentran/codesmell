<?php
class MCorpCategory extends AppModel {

	public $belongsTo = array(
		'MCorp'=>array(
			'className' => 'MCorp',
			'foreignKey'=> 'corp_id',
			'type'=>'inner',
			'fields' => array('MCorp.*'),
		),
	);

	public $validate = array(

		'corp_id' => array(
			'NotEmpty' => array(
				'rule' => 'notEmpty',
				'last' => true
			),
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'allowEmpty' => true
			),
		),

		'genre_id' => array(
			'NotGenreEmpty' => array(
				'rule' => 'notEmpty',
				'last' => true
			),
			'NoNumeric' => array(
				'rule' => array('numeric'),
			),
		),

		'category_id' => array(
			'NotCategoryEmpty' => array(
				'rule' => 'notEmpty',
				'last' => true
			),
			'NoNumeric' => array(
				'rule' => array('numeric'),
			),
		),

		'order_fee' => array(
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'allowEmpty' => true
			),
		),

		'order_fee_unit' => array(
			'NotEmptyFeeUnit' => array(
				'rule' => 'checkFeeUnit',
				'last' => true
			),
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'allowEmpty' => true
			),
		),

		'introduce_fee' => array(
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'allowEmpty' => true
			),
		),

		'note' => array(
			'OverMaxLength1000' => array(
				'rule' => array('maxLength', 1000),
				'last' => true,
			),
		),

	);

	/**
	 * 重複参照
	 *
	 * @return boolean
	 */
	public function checkCategoryId(){

		$conditions = array('MCorpCategory.corp_id'=>$this->data['MCorpCategory']['corp_id'] , 'MCorpCategory.category_id'=>$this->data['MCorpCategory']['category_id']);
		$category_count = $this->find( 'count', array(
				'conditions' => $conditions,
		));
		//var_dump($this->data[]);
		//exit();

		return true;

//		return false;
	}


	/**
	 * 受注手数料、紹介手数料どちらかは必須。
	 *
	 * @return boolean
	 */
	public function checkFee(){
		if(empty($this->data['MCorpCategory']['order_fee']) &&
			empty($this->data['MCorpCategory']['introduce_fee'])){
			return false;
		}
		return true;
	}

	/**
	 * 受注手数料単位
	 *
	 * 受注手数料が入力された場合必須。
	 *
	 * @return boolean
	 */
	public function checkFeeUnit(){
		if(!empty($this->data['MCorpCategory']['order_fee']) &&
		$this->data['MCorpCategory']['order_fee_unit'] == ''){
			return false;
		}
		return true;
	}

	/**
	 * 専門性ランク
	 *
	 * 対応可能にチェックされた場合、専門性は必須。
	 *
	 * @return boolean
	 */
	public function checkSelectList(){
		if( !empty($this->data['MCorpCategory']['category_id']) && (!empty($this->data['MCorpCategory']['select_list']) || $this->data['MCorpCategory']['select_list'] == '') ){
			return false;
		}
		return true;
	}

	// 2015.08.17 s.harada ADD start 画面デザイン変更対応
	/**
	 * 加盟店が選択したジャンル一覧を返します
	 *
	 */
	public function getCorpSelectGenreList($corp_id = null){

		$list = $this->find('all', array(
				'fields' => 'MGenre.id, MGenre.genre_name',
				'joins' => array(array(
						'type' => 'left',
						'table' => 'm_genres',
						'alias' => 'MGenre',
						'conditions' => array(
								'MGenre.id = MCorpCategory.genre_id'
						)),
				),
				'conditions' => array(
						'MCorpCategory.corp_id = '. $corp_id
				),
				'group' => 'MCorpCategory.genre_id, MGenre.id',
				'order' => array('MGenre.genre_group' => 'asc', 'MGenre.genre_name' => 'asc'),
		));

		return $list;

	}
	/**
	 * 指定されたIDからジャンル名を返します
	 *
	 */
	public function getGenreName($id = null){

		$row = $this->find('first', array(
				'fields' => '*, MGenre.id, MGenre.genre_name',
				'joins' => array(array(
						'type' => 'left',
						'table' => 'm_genres',
						'alias' => 'MGenre',
						'conditions' => array(
								'MGenre.id = MCorpCategory.genre_id'
						)),
				),
				'conditions' => array(
						'MCorpCategory.id = '. $id
				),
		));

		return $row;

	}

	/**
	 * 指定された「corp_id」「genre_id」からIDを一つ返します
	 *
	 */
	public function getMCorpCategoryID($corp_id = null, $genre_id = null){

		$row = $this->find('first', array(
				'fields' => 'MCorpCategory.id',
				'conditions' => array(
						'MCorpCategory.corp_id = '. $corp_id, 'MCorpCategory.genre_id = '. $genre_id
				),
		));

		return $row;

	}

	/**
	 * 指定された「corp_id」「genre_id」からIDリストを返します
	 *
	 */
	public function getMCorpCategoryIDList($corp_id = null, $genre_id = null){

		$list = $this->find('all', array(
				'fields' => 'MCorpCategory.id',
				'conditions' => array(
						'MCorpCategory.corp_id = '. $corp_id, 'MCorpCategory.genre_id = '. $genre_id
				),
		));

		return $list;

	}

	/**
	 * 指定された「corp_id」からIDリストを返します
	 *
	 */
	public function getMCorpCategoryIDList2($corp_id = null){

		$list = $this->find('all', array(
				'fields' => 'MCorpCategory.id',
				'conditions' => array(
						'MCorpCategory.corp_id = '. $corp_id
				),
		));

		return $list;

	}

	/**
	 * 指定された「corp_id」から対応可能ジャンルリストを返します
	 *
	 */
	public function getMCorpCategoryGenreList($id = null){

		$list = $this->find ('all', array (
				'fields' => '*, MGenre.genre_name, MGenre.commission_type, MCategory.category_name',
				'joins' => array (
							array (
								'fields' => '*',
								'type' => 'left',
								'table' => 'm_genres',
								'alias' => 'MGenre',
								'conditions' => array (
										'MGenre.id = MCorpCategory.genre_id'
								)
							),
							array (
								'fields' => '*',
								'type' => 'left',
								'table' => 'm_categories',
								'alias' => 'MCategory',
									'conditions' => array (
											'MCategory.id = MCorpCategory.category_id'
									)
								)
		),
		'conditions' => array (
					'MCorpCategory.corp_id' => $id
			),
		'order' => array (
					'MCorpCategory.id' => 'asc'
			),
			'hasMany'=>array('MCorp'),
		));

		return $list;

	}
	// 2015.08.17 s.harada ADD start 画面デザイン変更対応

}