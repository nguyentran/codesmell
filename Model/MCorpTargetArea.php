<?php

class MCorpTargetArea extends AppModel {

	public $belongsTo = array(
			'MCorp' => array(
					'className' => 'MCorp',
					'foreignKey' => 'corp_id',
					'type' => 'inner',
					'fields' => array('MCorp.id'),
			),
	);

//	public $validate = array(
//	);
	// 2015.08.17 s.harada MOD start 画面デザイン変更対応
	/**
	 * 指定した加盟店の最終「modified」を返します
	 *
	 */
	public function getCorpTargetAreaLastModified($id = null) {

		$row = $this->find('first', array(
				'fields' => 'MCorpTargetArea.modified',
				'conditions' => array(
						'MCorpTargetArea.corp_id = ' . $id
				),
				'order' => array('MCorpTargetArea.modified' => 'desc'),
				'limit' => 1,
		));

		return $row;
	}

	/**
	 * 指定した「corp_id」のエリア数を返します
	 *
	 */
	public function getCorpTargetAreaCount($id = null) {

		$count = $this->find('count', array(
				'fields' => 'MCorpTargetArea.id',
				'conditions' => array(
						'MCorpTargetArea.corp_id = ' . $id
				)
						)
		);

		return $count;
	}

	// 2015.08.17 s.harada MOD end 画面デザイン変更対応
	// ORANGE-347 ADD S

	/**
	 * 加盟店の基本対応エリアを更新（１都道府県分）
	 * @param type $corp_id m_corps.id
	 * @param type $address1_text 都道府県名 ex:愛知県
	 * @param type $new_jis_cd_array 全国地方公共団体コード
	 * @param type $temp_id 契約申請中であれば corp_agreement_temp_link.id
	 * @return boolean
	 */
	public function changeBasicSupportArea($corp_id, $address1_text, $new_jis_cd_array, $temp_id = null) {

		// 県内の指定されなかった全国地方公共団体コードを削除対象として取得。
		// 連想配列では使いづらいため、Hash::extractで開いて普通の配列にする
		App::import('Model', 'MPost');
		$MPost = new MPost();
		$remove_jis_cd_array = Hash::extract($MPost->find('all', array(
                    'fields' => array('DISTINCT jis_cd'), 
                    'conditions' => array(
                        'address1' => $address1_text, 
                        'NOT' => array(
                            'jis_cd' => $new_jis_cd_array
                        )
                    )
                    )), '{n}.MPost.jis_cd');

		// 既存レコードの指定された郵便番号一覧（更新対象）を取得
		$update_jis_cd_list = $this->find('list', array('fields' => array('jis_cd','id'), 'conditions' => array('corp_id' => $corp_id, 'jis_cd' => $new_jis_cd_array)));
		// 追加＆更新用データを作成
		$m_corp_target_area = array();
		foreach ((array)$new_jis_cd_array as $jis_cd) {
			$m_corp_target_area[] = array('id' => Hash::get($update_jis_cd_list, $jis_cd), 'corp_id' => $corp_id, 'jis_cd' => $jis_cd,);
		}

		$this->begin();

		if (!empty($remove_jis_cd_array) && !$this->deleteAll(array('corp_id' => $corp_id, 'jis_cd' => $remove_jis_cd_array))) {
			$this->rollback();
			return false;
		}
		if (!empty($m_corp_target_area) && !$this->saveMany($m_corp_target_area)) {
			$this->rollback();
			return false;
		}

		$this->syncToMTargetArea($corp_id);
		$this->syncToMTargetAreasTemp($corp_id, $temp_id);

		$this->commit();
		return true;
	}

	public function findAllAreaCountByCorpId($corp_id) {
		$m_posts = array();
		foreach (Util::getDivList('prefecture_div') as $ken_cd => $ken_name) {
			if ($ken_cd !== 99) {
				$ret = $this->findAreaCount($ken_cd, $ken_name, 'MCorpTargetArea.corp_id', $corp_id);
				$m_posts['MCorpTargetArea'][] = Hash::extract($ret, 'MCorpTargetArea');
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
				'corp_count' => 'COUNT(DISTINCT MCorpTargetArea.jis_cd)',
				'coverage' => 'ROUND(100 * COUNT(DISTINCT MCorpTargetArea.jis_cd) / COUNT(DISTINCT MPost.jis_cd))',
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
										'MPost.jis_cd = MCorpTargetArea.jis_cd',
										$add_path => $add_value
								),
						),
				),
		);
		return $this->find('first', $query);
	}

	/**
	 * MCorpTargetAreaとMTargetAreaを比較して、MCorpCategoryの基本エリア利用フラグを適切な値に修正します。
	 * @param type $corp_id
	 */
	private function fixToMCorpCategory($corp_id) {
		$sql[] = 'UPDATE m_corp_categories';
		$sql[] = 'SET';
		$sql[] = '    target_area_type = FIX.target_area_type,';
		$sql[] = '    modified = now()';
		$sql[] = 'FROM';
		$sql[] = '(';
		$sql[] = '  SELECT';
		$sql[] = '    MCC.id';
		$sql[] = '  , CASE (';
		$sql[] = '      SELECT COUNT(*)';
		$sql[] = '      FROM';
		$sql[] = '        ( SELECT MCTA.jis_cd FROM m_corp_target_areas AS MCTA WHERE MCTA.corp_id = ? ) AS MCTA';
		$sql[] = '        FULL OUTER JOIN';
		$sql[] = '        ( SELECT MTA.jis_cd FROM m_target_areas AS MTA WHERE MTA.corp_category_id = MCC.id ) AS MTA';
		$sql[] = '        ON MCTA.jis_cd = MTA.jis_cd';
		$sql[] = '      WHERE';
		$sql[] = '        MCTA.jis_cd IS NULL OR MCTA.jis_cd IS NULL';
		$sql[] = '    )';
		$sql[] = '    WHEN 0 THEN 1';
		$sql[] = '    ELSE 2';
		$sql[] = '    END AS target_area_type';
		$sql[] = '  FROM m_corp_categories AS MCC';
		$sql[] = '  WHERE MCC.corp_id = ?';
		$sql[] = ') AS FIX';
		$sql[] = 'WHERE m_corp_categories.id = FIX.id;';
		$sql[] = '  AND m_corp_categories.target_area_type <> FIX.target_area_type;';
		return $this->query(implode("\n", $sql), array($corp_id, $corp_id), false);
	}

	/**
	 * MCorpTargetArea → MTargetArea の同期を行います。
	 * @param type $corp_id
	 */
	public function syncToMTargetArea($corp_id) {
		$this->syncToMTargetAreaDelete($corp_id);
		$this->syncToMTargetAreaInsert($corp_id);
	}

	/**
	 * 基本エリア設定を利用する各カテゴリに対し、不要な市区町村コードを削除します。
	 * @param type $corp_id
	 * @return type
	 */
	private function syncToMTargetAreaDelete($corp_id) {
		$sql[] = 'DELETE';
		$sql[] = 'FROM PUBLIC.m_target_areas';
		$sql[] = 'WHERE EXISTS (';
		$sql[] = '  SELECT MTA.id';
		$sql[] = '  FROM PUBLIC.m_target_areas MTA';
		$sql[] = '  INNER JOIN PUBLIC.m_corp_categories MCC';
		$sql[] = '          ON MCC.id = MTA.corp_category_id';
		$sql[] = '  WHERE MCC.corp_id = ?';
		$sql[] = '    AND MCC.target_area_type = 1';
		$sql[] = '    AND MTA.id = m_target_areas.id';
		$sql[] = '    AND NOT EXISTS (';
		$sql[] = '      SELECT 1';
		$sql[] = '      FROM PUBLIC.m_corp_target_areas MCTA';
		$sql[] = '      WHERE MCTA.jis_cd = MTA.jis_cd';
		$sql[] = '        AND MCTA.corp_id = MCC.corp_id';
		$sql[] = '    )';
		$sql[] = '  );';
		return $this->query(implode("\n", $sql), array($corp_id), false);
	}

	/**
	 * 基本エリア設定を利用する各カテゴリに対し、不足する市区町村コードを追加します。
	 * @param type $corp_id
	 * @return type
	 */
	private function syncToMTargetAreaInsert($corp_id) {
		$sql[] = 'INSERT INTO m_target_areas';
		$sql[] = '(';
		$sql[] = '    corp_category_id';
		$sql[] = '  , jis_cd';
		$sql[] = '  , address1_cd';
		$sql[] = ')';
		$sql[] = 'SELECT';
		$sql[] = '    MCC.id AS corp_category_id';
		$sql[] = '  , MCTA.jis_cd AS jis_cd';
		$sql[] = '  , SUBSTR(MCTA.jis_cd, 1, 2) AS address1_cd';
		$sql[] = 'FROM';
		$sql[] = '    public.m_corp_target_areas AS MCTA';
		$sql[] = '    INNER JOIN public.m_corp_categories AS MCC';
		$sql[] = '            ON MCTA.corp_id = MCC.corp_id';
		$sql[] = '    LEFT OUTER JOIN public.m_target_areas AS MTA';
		$sql[] = '            ON MCC.id = MTA.corp_category_id';
		$sql[] = '           AND MCTA.jis_cd = MTA.jis_cd';
		$sql[] = 'WHERE MCTA.corp_id = ?';
		$sql[] = '  AND MCC.target_area_type = 1';
		$sql[] = '  AND MTA.id IS NULL';
		return $this->query(implode("\n", $sql), array($corp_id), false);
	}

	/**
	 * MCorpTargetArea → MTargetAreasTemp の同期を行います。
	 * @param type $corp_id
	 * @param type $temp_id
	 */
	public function syncToMTargetAreasTemp($corp_id, $temp_id) {
		$this->begin();
		if ($this->syncToMTargetAreasTempDelete($corp_id, $temp_id) === false) {
			$this->rollback();
			return false;
		}
		if ($this->syncToMTargetAreasTempInsert($corp_id, $temp_id) === false) {
			$this->rollback();
			return false;
		}
		$this->commit();
		return true;
	}

	/**
	 * 基本エリア設定を利用する各カテゴリに対し、不要な市区町村コードを削除します。
	 * @param type $corp_id
	 * @param type $temp_id
	 * @return type
	 */
	private function syncToMTargetAreasTempDelete($corp_id, $temp_id) {
		$sql[] = 'DELETE';
		$sql[] = 'FROM m_target_areas_temp';
		$sql[] = 'WHERE EXISTS (';
		$sql[] = '  SELECT MTA.id';
		$sql[] = '  FROM m_target_areas_temp MTA';
		$sql[] = '  INNER JOIN m_corp_categories_temp MCC';
		$sql[] = '          ON MCC.id = MTA.corp_category_id';
		$sql[] = '  WHERE MCC.corp_id = ?';
		$sql[] = '    AND MCC.temp_id = ?';
		$sql[] = '    AND MCC.target_area_type = 1';
		$sql[] = '    AND MTA.id = m_target_areas_temp.id';
		$sql[] = '    AND NOT EXISTS (';
		$sql[] = '      SELECT 1';
		$sql[] = '      FROM m_corp_target_areas MCTA';
		$sql[] = '      WHERE MCTA.jis_cd = MTA.jis_cd';
		$sql[] = '        AND MCTA.corp_id = MCC.corp_id';
		$sql[] = '    )';
		$sql[] = ');';
		return $this->query(implode("\n", $sql), array($corp_id, $temp_id), false);
	}

	/**
	 * 基本エリア設定を利用する各カテゴリに対し、不足する市区町村コードを追加します。
	 * @param type $corp_id
	 * @param type $temp_id
	 * @return type
	 */
	private function syncToMTargetAreasTempInsert($corp_id, $temp_id) {
                $user = $this->__getLoginUser();
                $time = date('Y-m-d H:i:s');
		$sql[] = 'INSERT INTO m_target_areas_temp (';
		$sql[] = '    corp_id';
		$sql[] = '  , genre_id';
		$sql[] = '  , corp_category_id';
		$sql[] = '  , jis_cd';
		$sql[] = '  , modified_user_id';
		$sql[] = '  , modified';
		$sql[] = '  , created_user_id';
		$sql[] = '  , created';
		$sql[] = ')';
		$sql[] = 'SELECT MCC.corp_id  AS corp_id';
		$sql[] = '     , MCC.genre_id AS genre_id';
		$sql[] = '     , MCC.id       AS corp_category_id';
		$sql[] = '     , MCTA.jis_cd  AS jis_cd';
		$sql[] = '     , ?  AS modified_user_id';
		$sql[] = '     , ?  AS modified';
		$sql[] = '     , ?  AS created_user_id';
		$sql[] = '     , ?  AS created';
		$sql[] = 'FROM m_corp_target_areas MCTA';
		$sql[] = 'INNER JOIN m_corp_categories_temp MCC';
		$sql[] = '        ON  MCTA.corp_id = MCC.corp_id';
		$sql[] = 'LEFT JOIN m_target_areas_temp MTA';
		$sql[] = '       ON MCC.id = MTA.corp_category_id';
		$sql[] = '      AND MCTA.jis_cd = MTA.jis_cd';
		$sql[] = 'WHERE MCTA.corp_id = ?';
		$sql[] = '  AND MCC.temp_id = ?';
		$sql[] = '  AND MCC.target_area_type = 1';
		$sql[] = '  AND MTA.id IS NULL;';
		return $this->query(implode("\n", $sql), array($user['user_id'],$time,$user['user_id'],$time,$corp_id, $temp_id), false);
	}

	// ORANGE-347 ADD E
}
