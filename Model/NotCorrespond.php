<?php
class NotCorrespond extends AppModel {
	public $useTable = 'not_corresponds';

	public function findNotCorrespond($corp_id = null){
		App::import('Model', 'MPost');
		App::import('Model', 'MCorpTargetArea');
		App::import('Model', 'NearPrefecture');
		App::import('Model', 'NotCorrespondItem');
		$m_posts = new MPost();
		$m_corp_target_areas = new MCorpTargetArea();
		$near_prefectures = new NearPrefecture();
		$not_correspond_items = new NotCorrespondItem();

		// 取得対象とする案件数の下限値を取得
		$items = $not_correspond_items->find('first', array(
				'order' => array('id' => 'desc')
		));

		$dbo = $this->getDataSource();
		$MPost = $dbo->buildStatement(array(
				'fields' => array('jis_cd', 'address1', 'address2'),
				'table' => 'm_posts',
				'alias' => 'MPostA',
				'group' => array('jis_cd', 'address1', 'address2')
		), $m_posts);

		// 加盟店の対応エリアを取得
		$corp_prefecture = $m_corp_target_areas->find('all', array(
				'fields' => array('distinct substring(jis_cd, 1, 2) as "MCorpTargetArea__prefecture_cd"'),
				'conditions' => array('corp_id' => $corp_id)
		));
		$prefectures = array();
		foreach($corp_prefecture as $val){
			$prefectures[] = (int)$val['MCorpTargetArea']['prefecture_cd'];
		}
		// 対応エリアの近隣都道府県を取得
		$near_prefecture = $near_prefectures->find('all', array(
				'fields' => array('near_prefecture_cd'),
				'conditions' => array('prefecture_cd' => $prefectures)
		));
		foreach($near_prefecture as $val){
			if(!array_search($val['NearPrefecture']['near_prefecture_cd'], $prefectures))
				$prefectures[] = (int)$val['NearPrefecture']['near_prefecture_cd'];
		}

		// エリア対応加盟店なし案件の取得
		$fields = array(
				'NotCorrespond.id',
				'NotCorrespond.prefecture_cd',
				'NotCorrespond.jis_cd',
				'MPost.address1',
				'MPost.address2',
				'NotCorrespond.genre_id',
				'MGenre.genre_name',
				'MGenre.development_group',
				'NotCorrespond.not_correspond_count_year',
				'NotCorrespond.not_correspond_count_latest',
				'min(NotCorrespondLog.import_date) as "NotCorrespond__min_import_date"',
				'count("MCorpCategory".*) as "NotCorrespond__target_category"',
				'count("MTargetArea".*) as "NotCorrespond__target_area"',
				'count("MCorpTargetArea".*) as "NotCorrespond__target_corp_area"'
		);
		$joins = array(
				array(
						'type' => 'inner',
						'table' => 'm_genres',
						'alias' => 'MGenre',
						'conditions' => array(
								'MGenre.id = NotCorrespond.genre_id',
								'MGenre.valid_flg' => 1
						)
				),
				array(
						'type' => 'left',
						'table' => 'm_corp_categories',
						'alias' => 'MCorpCategory',
						'conditions' => array(
								'MCorpCategory.genre_id = NotCorrespond.genre_id',
								'MCorpCategory.corp_id' => $corp_id
						)
				),
				array(
						'type' => 'left',
						'table' => 'm_target_areas',
						'alias' => 'MTargetArea',
						'conditions' => array(
								'MTargetArea.corp_category_id = MCorpCategory.id',
								'MTargetArea.jis_cd = NotCorrespond.jis_cd'
						)
				),

				array(
						'type' => 'left',
						'table' => 'm_corp_target_areas',
						'alias' => 'MCorpTargetArea',
						'conditions' => array(
								'MCorpTargetArea.jis_cd = NotCorrespond.jis_cd',
								'MCorpTargetArea.corp_id' => $corp_id,
						)
				),
				array(
						'type' => 'inner',
						'table' => "({$MPost})",
						'alias' => 'MPost',
						'conditions' => array(
								'MPost.jis_cd = NotCorrespond.jis_cd'
						)
				),
				array(
						'type' => 'inner',
						'table' => 'not_correspond_logs',
						'alias' => 'NotCorrespondLog',
						'conditions' => array(
								'NotCorrespondLog.not_correspond_id = NotCorrespond.id',
								'or' => array(
										//'NotCorrespondLog.not_correspond_count_year >=' => 10,
										//'NotCorrespondLog.not_correspond_count_latest >=' => 2,
										'NotCorrespondLog.not_correspond_count_year >=' => $items['NotCorrespondItem']['small_lower_limit'],
										'NotCorrespondLog.not_correspond_count_latest >=' => $items['NotCorrespondItem']['immediate_lower_limit'],
								)
						)
				)
		);
		$conditions = array(
				'or' => array(
						//'NotCorrespond.not_correspond_count_year >=' => 10,
						//'NotCorrespond.not_correspond_count_latest >=' => 2,
						'NotCorrespond.not_correspond_count_year >=' => $items['NotCorrespondItem']['small_lower_limit'],
						'NotCorrespond.not_correspond_count_latest >=' => $items['NotCorrespondItem']['immediate_lower_limit'],
				),
				'NotCorrespond.prefecture_cd' => $prefectures
		);
		$group = array(
				'NotCorrespond.id, NotCorrespond.jis_cd, NotCorrespond.prefecture_cd, '
				.'MPost.address1, MPost.address2, '
				.'NotCorrespond.genre_id, '
				.'MGenre.genre_name, MGenre.development_group, '
				.'NotCorrespond.not_correspond_count_year, NotCorrespond.not_correspond_count_latest '
				.'HAVING count(MTargetArea.id) < 1'
		);
		$order = array(
				'MGenre.development_group',
				'NotCorrespond.genre_id',
				'NotCorrespond.jis_cd',
				'NotCorrespond.not_correspond_count_latest',
				'NotCorrespond.not_correspond_count_year'
		);

		$results = $this->find('all', array(
				'fields' => $fields,
				'joins' => $joins,
				'conditions' => $conditions,
				'group' => $group,
				'order' => $order
		));

		return $results;

	}
}