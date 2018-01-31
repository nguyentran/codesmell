<?php

class MTargetArea extends AppModel {

	public $belongsTo = array(
			'MCorpCategory' => array(
					'className' => 'MCorpCategory',
					'foreignKey' => 'corp_category_id',
					'type' => 'inner',
					'fields' => array('MCorpCategory.corp_id', 'MCorpCategory.category_id'),
			),
	);
	public $validate = array(
	);

	// 2015.08.17 s.harada MOD start 画面デザイン変更対応
	/**
	 * 指定した「corp_category_id」のエリア数を返します
	 *
	 */
	public function getCorpCategoryTargetAreaCount($id = null) {
// 2016.10.05 murata.s CHG(S) SQLインジェクション対応
// 		$count = $this->find('count', array(
// 				'fields' => 'MTargetArea.id',
// 				'conditions' => array(
// 						'MTargetArea.corp_category_id = ' . $id
// 				)
// 		)
// 		);
		$count = $this->find('count', array(
				'fields' => 'MTargetArea.id',
				'conditions' => array(
						'MTargetArea.corp_category_id' => $id
				)
		));
// 2016.10.05 murata.s CHG(E) SQLインジェクション対応
		return $count;
	}

	/**
	 * 指定した「corp_category_id」と「jis_cd」のエリア数を返します
	 *
	 */
	public function getCorpCategoryTargetAreaCount2($id = null, $jis_cd = null) {

// 2016.10.05 murata.s CHG(S) 脆弱性 SQLインジェクション対応
// 		$count = $this->find('count', array(
// 				'fields' => 'MTargetArea.id',
// 				'conditions' => array(
// 						'MTargetArea.corp_category_id = ' . $id,
// 						'MTargetArea.jis_cd = ' .'\''.$jis_cd.'\''
// 				)
// 		)
// 		);
		$count = $this->find('count', array(
				'fields' => 'MTargetArea.id',
				'conditions' => array(
						'MTargetArea.corp_category_id' => $id,
						'MTargetArea.jis_cd' => $jis_cd
				)
		));
// 2016.10.05 murata.s CHG(S) 脆弱性 SQLインジェクション対応
		return $count;
	}

	/**
	 * 指定した「corp_category_id」と「jis_cd」のエリア数を返します
	 *
	 * @param  int  $corpCategoryId
	 * @param  array  $jisCds
	 * @return int 
	 */
	public function countHasJisCdsOfCorpCategory($corpCategoryId, $jisCds) {
		return $this->find('count', array(
								'conditions' => array(
										'corp_category_id' => $corpCategoryId,
										'jis_cd' => $jisCds,
								)
										)
		);
	}

	/**
	 * 指定した「corp_category_id」の最終「modified」を返します
	 *
	 */
	public function getTargetAreaLastModified($id = null) {

// 2016.10.05 murata.s CHG(S) 脆弱性 SQLインジェクション対応
// 		$row = $this->find('first', array(
// 				'fields' => 'MTargetArea.modified',
// 				'conditions' => array(
// 						'MTargetArea.corp_category_id = ' . $id
// 				),
// 				'order' => array('MTargetArea.modified'=> 'desc'),
// 				'limit' => 1,
// 		));
		$row = $this->find('first', array(
				'fields' => 'MTargetArea.modified',
				'conditions' => array(
						'MTargetArea.corp_category_id' => $id
				),
				'order' => array('MTargetArea.modified' => 'desc'),
				'limit' => 1,
		));
// 2016.10.05 murata.s CHG(E) 脆弱性 SQLインジェクション対応
		return $row;
	}

	// 2015.08.17 s.harada MOD end 画面デザイン変更対応
	// ORANGE=347 ADD S
	public function findAllAreaCountByGenreId($genre_id) {
		App::import('Model', 'MPost');
		$this->MPost = new MPost();
		App::import('Model', 'MCorpCategoriesTemp');
		$this->MCorpCategoriesTemp = new MCorpCategoriesTemp();
		$m_corp_categories_ids = $this->MCorpCategoriesTemp->find('list', array('conditions' => array('genre_id' => $genre_id)));
		$m_posts = array();
		foreach (Util::getDivList('prefecture_div') as $ken_cd => $ken_name) {
			if ($ken_cd !== 99) {
				$this->MPost->virtualFields = array(
						'ken_cd' => sprintf("'%02d'", $ken_cd),
						'ken_name' => "'{$ken_name}'",
						'area_count' => 'COUNT(DISTINCT MPost.jis_cd)',
						'corp_count' => 'COUNT(DISTINCT MTargetArea.jis_cd)',
						'coverage' => 'ROUND(100 * COUNT(DISTINCT MTargetArea.jis_cd) / COUNT(DISTINCT MPost.jis_cd))',
				);
				$ret = $this->MPost->find('first', array(
						'fields' => array(
								'ken_cd',
								'ken_name',
								'area_count',
								'corp_count',
								'coverage',
						),
						'joins' => array(
								array(
										'type' => 'LEFT',
										'table' => 'm_target_areas',
										'alias' => 'MTargetArea',
										'conditions' => array(
												'MTargetArea.jis_cd = MPost.jis_cd',
												'MTargetArea.corp_category_id' => $m_corp_categories_ids
										),
								),
						),
						'conditions' => array(
								'MPost.address1' => $ken_name,
						),
				));
				$m_posts['MPost'][] = Hash::extract($ret, 'MPost');
			}
		}
		return $m_posts;
	}

	public function resetSupportAreaByCorpCategoryId($corp_category_id) {
		
	}

	// ORANGE=347 ADD E
}
