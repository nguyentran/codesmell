<?php

App::uses('AppModel', 'Model');

/**
 * MTargetAreasTemp Model
 *
 * @property Corp $Corp
 * @property CorpCategory $CorpCategory
 * @property ModifiedUser $ModifiedUser
 * @property CreatedUser $CreatedUser
 */
class MTargetAreasTemp extends AppModel {

	/**
	 * Use table
	 *
	 * @var mixed False or table name
	 */
	public $useTable = 'm_target_areas_temp';

	/**
	 * Validation rules
	 *
	 * @var array
	 */
	public $validate = array(
			'corp_id' => array(
					'numeric' => array(
							'rule' => array('numeric'),
							//'message' => 'Your custom message here',
							'allowEmpty' => false,
					//'required' => false,
					//'last' => false, // Stop validation after this rule
					//'on' => 'create', // Limit validation to 'create' or 'update' operations
					),
			),
			'corp_category_id' => array(
					'numeric' => array(
							'rule' => array('numeric'),
							//'message' => 'Your custom message here',
							'allowEmpty' => false,
					//'required' => false,
					//'last' => false, // Stop validation after this rule
					//'on' => 'create', // Limit validation to 'create' or 'update' operations
					),
			),
			'jis_cd' => array(
					'notEmpty' => array(
							'rule' => array('notEmpty'),
							//'message' => 'Your custom message here',
							'allowEmpty' => false,
					//'required' => false,
					//'last' => false, // Stop validation after this rule
					//'on' => 'create', // Limit validation to 'create' or 'update' operations
					),
			),
	);

	public function findAllAreaCountByCorpId($corp_id) {
		$m_posts = array();
		foreach (Util::getDivList('prefecture_div') as $ken_cd => $ken_name) {
			if ($ken_cd !== 99) {
				$ret = $this->findAreaCount($ken_cd, $ken_name, 'MTargetAreasTemp.corp_id', $corp_id);
				$m_posts['MTargetAreasTemp'][] = Hash::extract($ret, 'MTargetAreasTemp');
			}
		}
		return $m_posts;
	}

	public function findAllAreaCountByGenreId($corp_id, $genre_id, $m_corp_categories_temp_id) {
		$dbo = $this->getDataSource();
		$m_posts = array();
		foreach (Util::getDivList('prefecture_div') as $ken_cd => $ken_name) {
			if ($ken_cd !== 99) {
				$subQuery = $dbo->buildStatement(array(
						'table' => 'm_posts',
						'alias' => 'MPost',
						'fields' => array('DISTINCT jis_cd'),
						'conditions' => array('MPost.address1' => $ken_name)
								), $this);
				$this->virtualFields = array(
						'ken_cd' => sprintf("'%02d'", $ken_cd),
						'ken_name' => "'{$ken_name}'",
						'area_count' => 'COUNT(MPost.jis_cd)',
						'corp_count' => 'COUNT(MTargetAreasTemp.jis_cd)',
						'coverage' => 'ROUND(100 * COUNT(MTargetAreasTemp.jis_cd) / COUNT(MPost.jis_cd))',
				);
				$ret = $this->find('first', array(
						'fields' => array(
								'ken_cd',
								'ken_name',
								'area_count',
								'corp_count',
								'coverage',
						),
						'joins' => array(
								array(
										'type' => 'RIGHT',
										'table' => "({$subQuery})",
										'alias' => 'MPost',
										'conditions' => array(
												'MPost.jis_cd = MTargetAreasTemp.jis_cd',
												'MTargetAreasTemp.genre_id' => $genre_id,
												'MTargetAreasTemp.corp_id' => $corp_id,
                                                                                                'MTargetAreasTemp.corp_category_id' => $m_corp_categories_temp_id,
										),
								),
						),
				));
				$m_posts['MTargetAreasTemp'][] = Hash::extract($ret, 'MTargetAreasTemp');
			}
		}
		return $m_posts;
	}

	public function findAllAreaCountByCorpCategoryId($corp_category_id) {
		$dbo = $this->getDataSource();
		$m_posts = array();
		foreach (Util::getDivList('prefecture_div') as $ken_cd => $ken_name) {
			if ($ken_cd !== 99) {
				$subQuery = $dbo->buildStatement(array(
						'table' => 'm_posts',
						'alias' => 'MPost',
						'fields' => array('DISTINCT jis_cd'),
						'conditions' => array('MPost.address1' => $ken_name)
								), $this);
				$this->virtualFields = array(
						'ken_cd' => sprintf("'%02d'", $ken_cd),
						'ken_name' => "'{$ken_name}'",
						'area_count' => 'COUNT(DISTINCT MPost.jis_cd)',
						'corp_count' => 'COUNT(DISTINCT MTargetAreasTemp.jis_cd)',
						'coverage' => 'ROUND(100 * COUNT(DISTINCT MTargetAreasTemp.jis_cd) / COUNT(DISTINCT MPost.jis_cd))',
				);
				$ret = $this->find('first', array(
						'fields' => array(
								'ken_cd',
								'ken_name',
								'area_count',
								'corp_count',
								'coverage',
						),
						'joins' => array(
								array(
										'type' => 'RIGHT',
										'table' => "({$subQuery})",
										'alias' => 'MPost',
										'conditions' => array(
												'MPost.jis_cd = MTargetAreasTemp.jis_cd',
												'MTargetAreasTemp.corp_category_id' => $corp_category_id,
										),
								),
						),
				));
				$m_posts['MTargetAreasTemp'][] = Hash::extract($ret, 'MTargetAreasTemp');
			}
		}
		return $m_posts;
	}

	private function findAreaCount($ken_cd, $ken_name, $add_path, $add_value) {
		$dbo = $this->getDataSource();
		$subQuery = $dbo->buildStatement(array(
				'table' => 'm_posts',
				'alias' => 'MPost',
				'fields' => array('DISTINCT jis_cd'),
				'conditions' => array('MPost.address1' => $ken_name)
						), $this);
		$this->virtualFields = array(
				'ken_cd' => sprintf("'%02d'", $ken_cd),
				'ken_name' => "'{$ken_name}'",
				'area_count' => 'COUNT(DISTINCT MPost.jis_cd)',
				'corp_count' => 'COUNT(DISTINCT MTargetAreasTemp.jis_cd)',
				'coverage' => 'ROUND(100 * COUNT(DISTINCT MTargetAreasTemp.jis_cd) / COUNT(DISTINCT MPost.jis_cd))',
		);
		$query = array(
				'fields' => array(
						'ken_cd',
						'ken_name',
						'area_count',
						'corp_count',
						'coverage',
				),
				'joins' => array(
						array(
								'type' => 'RIGHT',
								'table' => "({$subQuery})",
								'alias' => 'MPost',
								'conditions' => array(
										'MPost.jis_cd = MTargetAreasTemp.jis_cd',
										$add_path => $add_value
								),
						),
				),
		);
		return $this->find('first', $query);
	}

	public function updateAreaGroupByGenre($corp_id, $temp_id, $genre_id, $jis_cd_array) {
		App::import('Model', 'MCorpCategoriesTemp');
		$MCorpCategoriesTemp = New MCorpCategoriesTemp();
		$MCorpCategoriesTemp->find('list', array(
				'fields' => array('id', 'genre_id'),
				'conditions' => array(
						'corp_id' => $this->corp_id,
						'genre_id' => Hash::get($this->request->data, 'genre_id'),
						'temp_id' => $this->temp_id,
		)));
	}

	/**
	 * 指定されたエリアをカテゴリに適用する
	 * @param type $corp_id 加盟店ID
	 * @param type $temp_id 契約申請ID
	 * @param type $category_id_array カテゴリIDの配列 ( m_categories.id )
	 * @param type $ken_cd 対象となる県コード(2桁)
	 * @param type $select_jis_cd_array 選択した市区町村コード(5桁)
	 */
	public function updateAreaGroupByCategory($corp_id, $temp_id, $category_id_array, $ken_cd, $select_jis_cd_array) {
		App::import('Model', 'MCorpCategoriesTemp');
		$MCorpCategoriesTemp = new MCorpCategoriesTemp();

		// 修正対象IDとジャンルIDを取得
		$m_corp_categories_temp_id_array = $MCorpCategoriesTemp->find('list', array(
				'fields' => array('id', 'genre_id'),
				'conditions' => array(
						'corp_id' => $corp_id,
						'temp_id' => $temp_id,
						'category_id' => $category_id_array,
		)));

		// ＤＢ登録済みの同一県内市区町村コードを取得
		$existing_jis_cd_array = Hash::extract($this->find('all', array(
												'fields' => array('DISTINCT jis_cd'),
												'conditions' => array(
														'corp_category_id' => array_keys($m_corp_categories_temp_id_array),
														'jis_cd LIKE' => $ken_cd . '%',
								))), '{n}.MTargetAreasTemp.jis_cd');

		// 「登録済 － 選択済」 で 今回削除するべき市区町村コードを取得
		$remove_jis_cd_array = array_diff($existing_jis_cd_array, $select_jis_cd_array);
		// 「選択済 － 登録済」 で 今回追加するべき市区町村コードを取得
		$append_jis_cd_array = array_diff($select_jis_cd_array, $existing_jis_cd_array);

		$append_data = array();
		foreach ($m_corp_categories_temp_id_array as $corp_category_id => $genre_id) {
			foreach ($append_jis_cd_array as $jis_cd) {
				$append_data[] = array(
						'corp_id' => $corp_id,
						'genre_id' => $genre_id,
						'corp_category_id' => $corp_category_id,
						'jis_cd' => $jis_cd,
				);
			}
		}

		$this->begin();

		// 削除
		if (!empty($remove_jis_cd_array)) {
			if (!$this->deleteAll(array('corp_id' => $corp_id, 'corp_category_id' => array_keys($m_corp_categories_temp_id_array), 'jis_cd' => $remove_jis_cd_array))) {
				$this->rollback();
				return false;
			}
		}

		// 追加
		if (!empty($append_data)) {
			if (!$this->saveMany($append_data)) {
				$this->rollback();
				return false;
			}
		}

		// target_area_typeを修正する
		if ($MCorpCategoriesTemp->fixTargetAreaType($corp_id, $temp_id) === false) {
			$this->rollback();
			return false;
		}

		//全部成功したらコミット
		$this->commit();
		return true;
	}

	/**
	 * 指定されたジャンルのエリアを基本対応エリアにリセットする
	 */
	public function resetAreaGroupByGenreId($corp_id, $temp_id, $genre_id_array) {
		App::import('Model', 'MCorpCategoriesTemp');
		$MCorpCategoriesTemp = new MCorpCategoriesTemp();

		// 修正対象を取得
		$m_corp_categories_temp_array = $MCorpCategoriesTemp->find('all', array(
				'fields' => array('id'),
				'conditions' => array(
						'corp_id' => $corp_id,
						'temp_id' => $temp_id,
						'genre_id' => $genre_id_array,
		)));
		return $this->resetArea($corp_id, $temp_id, $m_corp_categories_temp_array);
	}

	/**
	 * 指定されたカテゴリーのエリアを基本対応エリアにリセットする
	 */
	public function resetAreaGroupByCategoryId($corp_id, $temp_id, $category_id_array) {
		App::import('Model', 'MCorpCategoriesTemp');
		$MCorpCategoriesTemp = new MCorpCategoriesTemp();

		// 修正対象を取得
		$m_corp_categories_temp_array = $MCorpCategoriesTemp->find('all', array(
				'fields' => array('id'),
				'conditions' => array(
						'corp_id' => $corp_id,
						'temp_id' => $temp_id,
						'category_id' => $category_id_array,
		)));
		return $this->resetArea($corp_id, $temp_id, $m_corp_categories_temp_array);
	}

	/**
	 * 指定されたMCorpCategoriesTemp.idのtarget_area_typeを1にした上でMTargetAreaと同期する
	 * @param type $corp_id
	 * @param type $temp_id 
	 * @param type $m_corp_categories_temp_array {n}.MCorpCategoriesTemp.id
	 * @return boolean
	 * @throws InvalidArgumentException
	 */
	private function resetArea($corp_id, $temp_id, $m_corp_categories_temp_array) {

		if (!Hash::check($m_corp_categories_temp_array, '{n}.MCorpCategoriesTemp.id')) {
			throw new InvalidArgumentException('指定された引数の型が誤っています。');
		}

		// target_area_type = 1：基本対応エリア を指定
		$update_data = Hash::insert(
										Hash::remove($m_corp_categories_temp_array, '{n}.MCorpCategoriesTemp.target_area_type')
										, '{n}.MCorpCategoriesTemp.target_area_type', 1);

		App::import('Model', 'MCorpCategoriesTemp');
		$MCorpCategoriesTemp = new MCorpCategoriesTemp();
		App::import('Model', 'MCorpTargetArea');
		$MCorpTargetArea = new MCorpTargetArea();

		$this->begin();

		// 基本対応エリア適用フラグを更新
		if (!$MCorpCategoriesTemp->saveMany($update_data)) {
			$this->rollback();
			return false;
		}

		// 基本対応エリアからカテゴリ別対応エリアに同期する
		if (!$MCorpTargetArea->syncToMTargetAreasTemp($corp_id, $temp_id)) {
			$this->rollback();
			return false;
		}

		$this->commit();
		return true;
	}

}
