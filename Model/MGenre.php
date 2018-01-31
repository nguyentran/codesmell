<?php
class MGenre extends AppModel {

	public $validate = array(
// 2016.01.04 ORANGE-1247 k.iwai ADD(S)
		'credit_unit_price' => array(
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'allowEmpty' => false
			),
		),
// 2016.01.04 ORANGE-1247 k.iwai ADD(E)
	);

	/**
	 * カテゴリ一覧(SelectBox用)を返します
	 *
	 */
	public function getList($valid_flg = false, $use_exclusion_flg = false){

		// 検索条件の設定
		if ($valid_flg == true) {
			$conditions = array('valid_flg' => 1);
		}
		else {
			$conditions = array();
		}

		if ($use_exclusion_flg == true) {
			$conditions["exclusion_flg"] = "0";
		}

		// DB検索
		// 2015.02.18 h.hara
//		$list = $this->find('all', array('conditions'=>$conditions, 'order'=>'MGenre.id asc'));
		// 2015.04.03 y.kurokawa
//		$list = $this->find('all', array('conditions'=>$conditions, 'order'=>'MGenre.genre_name asc'));
		$list = $this->find('all', array('conditions'=>$conditions, 'order'=>'MGenre.genre_kana asc'));

		// リストの作成
		foreach ($list as $val) {

			$list_[] = array(
					"MGenre" => array(
							"id" => $val['MGenre']['id'],
							"name" => $val['MGenre']['genre_name']
					)
			);
		}

		$data = Set::Combine(@$list_, '{n}.MGenre.id', '{n}.MGenre.name');
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

		// 2015.02.18 h.hara
//		$list = $this->find('all', array('conditions'=>$conditions, 'order'=>'MGenre.id asc'));
		$list = $this->find('all', array('conditions'=>$conditions, 'order'=>'MGenre.genre_name asc'));

		$name ='';

		foreach ($list as $val) {

			if($id == $val['MGenre']['id']) {
				$name = $val['MGenre']['genre_name'];
			}
		}

		return $name;
	}

	// 2015.06.04 y.fujimori ADD start ORANGE-485
	/**
	 * 指定されたIDの名称を取得します
	 *
	 * @param unknown_type $id
	 */
	public function getDefaultFee($id){
		$conditions = array();

		$conditions = array('id' => $id);

		$list = $this->find('all', array('conditions'=>$conditions, 'order'=>'MGenre.genre_name asc'));

		$default_fee ='';

		foreach ($list as $val) {

			if($id == $val['MGenre']['id']) {
				$default_fee = $val['MGenre']['default_fee'];
			}
		}

		return $default_fee;
	}

	/**
	 * 指定されたIDの名称を取得します
	 *
	 * @param unknown_type $id
	 */
	public function getDefaultFeeUnit($id){
		$conditions = array();

		$conditions = array('id' => $id);

		$list = $this->find('all', array('conditions'=>$conditions, 'order'=>'MGenre.genre_name asc'));

		$default_fee_unit ='';

		foreach ($list as $val) {

			if($id == $val['MGenre']['id']) {
				$default_fee_unit = $val['MGenre']['default_fee_unit'];
			}
		}

		return $default_fee_unit;
	}
	// 2015.06.04 y.fujimori ADD end

	/**
	 * 地域別ジャンル設定を取得します。
	 * 選定ジャンル設定がない場合は、ジャンルマスタから取得します。
	 * @return Ambigous <multitype:, NULL>
	 */
	public function getSelectionGenre(){

		$list = $this->find('all', array(
				'fields' => 'MGenre.id, MGenre.genre_name, SelectGenre.id, SelectGenre.genre_id, SelectGenre.select_type',
				'joins' => array(array(
						'type' => 'left',
						'table' => 'select_genres',
						'alias' => 'SelectGenre',
						'conditions' => array(
								'MGenre.id = SelectGenre.genre_id'
						),
				)),
				'conditions' => array(
					'MGenre.valid_flg = 1'
				),
				'order' => 'MGenre.genre_name asc',
		));

		return $list;

	}

	/**
	 * オークション選定ジャンル設定を取得します。
	 *
	 * @return Ambigous <multitype:, NULL>
	 */
	public function getAuctionGenre(){

		$list = $this->find('all', array(
				'fields' => 'MGenre.id, MGenre.genre_name, SelectGenre.id, SelectGenre.genre_id, SelectGenre.select_type',
				'joins' => array(array(
						'type' => 'inner',
						'table' => 'select_genres',
						'alias' => 'SelectGenre',
// 2016.11.17 murata.s ORANGE-185 CHG(S)
// 						'conditions' => array(
// 								'MGenre.id = SelectGenre.genre_id',
// 								'SelectGenre.select_type = 2'
// 						),
						'conditions' => array(
								'MGenre.id = SelectGenre.genre_id',
								'SelectGenre.select_type' => array(
										Util::getDivValue('selection_type', 'AuctionSelection'),
										Util::getDivValue('selection_type', 'AutomaticAuctionSelection'),
										Util::getDivValue('selection_type', 'AutoSelection')
								)
						),
// 2016.11.17 murata.s ORANGE-185 CHG(E)
				)),
				'conditions' => array(
						'MGenre.valid_flg = 1'
				),
				'order' => 'MGenre.genre_name asc',
		));

		return $list;

	}

	/**
	 * 自動選定ジャンル設定を取得します。
	 * 自動選定ジャンル設定がない場合は、ジャンルマスタから取得します。
	 * @return Ambigous <multitype:, NULL>
	 */
	public function getAutoSelectGenre(){

		$list = $this->find('all', array(
				'fields' => 'MGenre.id, MGenre.genre_name, SelectGenre.id, SelectGenre.genre_id, SelectGenre.select_type',
				'joins' => array(array(
						'type' => 'inner',
						'table' => 'select_genres',
						'alias' => 'SelectGenre',
						'conditions' => array(
								'MGenre.id = SelectGenre.genre_id',
								'SelectGenre.select_type = 1'
						),
				)),
				'order' => 'MGenre.genre_name asc',
		));

		return $list;

	}

	/**
	 * 登録斡旋ジャンルを取得します
	 *
	 * @param unknown_type
	 */
	public function getMediationList(){

		$conditions = array('registration_mediation' => 1);

		$list = $this->find('all', array('conditions'=>$conditions));

		return $list;
	}

	// ORANGE-347 ADD (S)

	private function getFindAllCategoriesOptions() {
		$this->recursive = -1;
		$this->virtualFields = array(
				'genre_id' => 'MGenre.id',
				'category_id' => 'MCategory.id',
				'category_name' => 'MCategory.category_name',
				'display_order' => 'MCategory.display_order',
				'category_default_fee' => 'MCategory.category_default_fee',
				'category_default_fee_unit' => 'MCategory.category_default_fee_unit',
		);
		return array(
				'fields' => array(
						'genre_id',
						'genre_name',
						'genre_group',
						'category_id',
						'category_name',
						'default_fee',
						'default_fee_unit',
						'category_default_fee',
						'category_default_fee_unit',
						'commission_type',
						'registration_mediation',
				),
				'joins' => array(
						array(
								'type' => 'LEFT',
								'table' => 'm_categories',
								'alias' => 'MCategory',
								'conditions' => array(
										'MCategory.genre_id = MGenre.id'
								),
						),
				),
				'conditions' => array(
						'MGenre.valid_flg' => 1,
						'MCategory.hide_flg' => 0,
						'MCategory.disable_flg' => false,
				),
				'order' => array(
						'genre_group', 'display_order',
		));
	}

	public function findAllCategoriesByCorpId($corp_id) {
		$options = $this->getFindAllCategoriesOptions();
		$this->virtualFields['id'] = 'MCorpCategory.id';
		$this->virtualFields['corp_id'] = "'{$corp_id}'";
		$this->virtualFields['select_list'] = 'MCorpCategory.select_list';
		$this->virtualFields['note'] = 'MCorpCategory.note';
		$options['fields'] = array_merge($options['fields'], array('id', 'corp_id', 'select_list', 'note'));
		$options['joins'][] = array(
								'type' => 'LEFT',
				'table' => 'm_corp_categories',
				'alias' => 'MCorpCategory',
								'conditions' => array(
						'MCorpCategory.genre_id = MGenre.id',
						'MCorpCategory.category_id = MCategory.id',
						'MCorpCategory.corp_id' => $corp_id,
								),
		);
		return $this->find('all', $options);
	}

	public function findAllTempCategoriesByCorpId($corp_id, $agreement_temp_link_id) {
		$options = $this->getFindAllCategoriesOptions();
		$this->virtualFields['id'] = 'MCorpCategory.id';
		$this->virtualFields['corp_id'] = "'{$corp_id}'";
		$this->virtualFields['select_list'] = 'MCorpCategory.select_list';
		$this->virtualFields['note'] = 'MCorpCategory.note';
		$options['fields'] = array_merge($options['fields'], array('id', 'corp_id', 'select_list', 'note'));
		$options['joins'][] = array(
								'type' => 'LEFT',
								'table' => 'm_corp_categories_temp',
								'alias' => 'MCorpCategory',
								'conditions' => array(
										'MCorpCategory.genre_id = MGenre.id',
										'MCorpCategory.category_id = MCategory.id',
										'MCorpCategory.corp_id' => $corp_id,
										'MCorpCategory.temp_id' => $agreement_temp_link_id,
				),
		);
		return $this->find('all', $options);
	}

	// ORANGE-347 ADD (E)
}
