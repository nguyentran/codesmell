<?php
class MCategory extends AppModel {

	public $validate = array(

		'category_name' => array(
			'NotEmpty' => array(
				'rule' => 'notEmpty',
				'last' => true
			),
			'OverMaxLength200' => array(
				'rule' => array('maxLength', 200),
				'last' => true,
				'allowEmpty' => true
			),
		),

		'site_id' => array(
			'NotEmpty' => array(
				'rule' => 'notEmpty',
				'last' => true
			),
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'last' => true,
				'allowEmpty' => true
			),
		),

		'note' => array(
			'OverMaxLength1000' => array(
				'rule' => array('maxLength', 1000),
				'last' => true,
				'allowEmpty' => true
			),
		),

	);

	/**
	 * カテゴリ一覧(SelectBox用)を返します
	 *
	 */
	public function getList($genre_id = null){

		// 検索条件の設定
		if(!empty($genre_id)){
			$conditions = array('genre_id' => $genre_id);
		} else {
			$conditions = array();
		}
		// DB検索
		$list = $this->find('all', array('conditions'=>$conditions, 'order'=>'MCategory.id asc'));

		// リストの作成
		foreach ($list as $val) {

			$list_[] = array(
					"MCategory" => array(
							"id" => $val['MCategory']['id'],
							"name" => $val['MCategory']['category_name']
					)
			);
		}

		$data = Set::Combine(@$list_, '{n}.MCategory.id', '{n}.MCategory.name');
		return $data;
	}

	/**
	 * 指定されたIDの名称を取得します
	 *
	 * @param unknown_type $id
	 */
	public function getListText($id){
		$conditions = array();

		$conditions = array('id' => $id);

		$list = $this->find('all', array('conditions'=>$conditions, 'order'=>'MCategory.id asc'));

		$name ='';

		foreach ($list as $val) {

			if($id == $val['MCategory']['id']) {
				$name = $val['MCategory']['category_name'];
			}
		}

		return $name;
	}

	// 2015.08.17 s.harada MOD start 画面デザイン変更対応
	/**
	 * すべてのカテゴリ一覧を返します
	 *
	 */
	public function getAllList($corp_id = null){

		$list = $this->find('all', array(
				'fields' => '*, MGenre.id, MGenre.genre_name, MGenre.commission_type, MGenre.genre_group, MGenre.default_fee, MGenre.default_fee_unit, MGenre.registration_mediation, MCorpCategory.id, MCorpCategory.modified, MCorpCategory.select_list',
				'joins' => array(array(
						'type' => 'left',
						'table' => 'm_genres',
						'alias' => 'MGenre',
						'conditions' => array(
								'MGenre.id = MCategory.genre_id'
						)
				),
						array(
								'type' => 'left outer',
								'table' => 'm_corp_categories',
								'alias' => 'MCorpCategory',
								'conditions' => array(
									'MCorpCategory.genre_id = MCategory.genre_id',
									'MCorpCategory.category_id = MCategory.id',
									'MCorpCategory.corp_id = '. $corp_id
								)
						),
				),
				'conditions' => array(
						'MGenre.valid_flg = 1',
						'MCategory.hide_flg = 0'
				),
				'order' => array('MGenre.genre_group' => 'asc', 'MCategory.display_order' => 'asc'),
		));

		return $list;

	}
	// 2015.08.17 s.harada MOD start 画面デザイン変更対応

}