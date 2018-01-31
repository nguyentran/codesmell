<?php

// 2016.06.06 murata.s ORANGE-102 ADD(S)
class MCorpCategoriesTemp extends AppModel {

	public $useTable = 'm_corp_categories_temp';
	public $validate = array(
			'order_fee' => array(
					'NoNumeric' => array(
							'rule' => array('numeric'),
							'allowEmpty' => true
					)
			),
			'introduce_fee' => array(
					'NoNumeric' => array(
							'rule' => array('numeric'),
							'allowEmpty' => true
					)
			)
	);
	// ORANGE-128 iwai ADD S
	public $csvFormat = array(
			// murata.s ORANGE-261 CHG(S)
			'default' => array(
					'MCorpCategoriesTemp.id' => '履歴ID',
					'CorpAgreementTempLink.corp_agreement_id' => '契約ID',
					'MCorp.id' => '企業ID',
					'MCorp.official_corp_name' => '正式加盟店名',
					'MGenre.id' => 'ジャンルID',
					'MGenre.genre_name' => 'ジャンル名',
					'MCategory.id' => 'カテゴリID',
					'MCategory.category_name' => 'カテゴリ名',
					'MCorpCategoriesTemp.order_fee' => '受注手数料',
					'custom.order_fee_unit' => '受注手数料単価',
					'MCorpCategoriesTemp.introduce_fee' => '紹介手数料',
					'MCorpCategoriesTemp.note' => '備考',
					'MCorpCategoriesTemp.select_list' => '専門性',
					'custom.corp_commission_type' => '取次形態',
					'custom.action_type' => '更新種別',
					'custom.action' => '更新内容',
					'MCorpCategoriesTemp.modified' => '更新日時',
			),
					// murata.s ORANGE-261 CHG(E)
	);

	// ORANGE-128 iwai ADD E

	public function getMCorpCategoryGenreList($id = null, $temp_id = null) {

		$list = $this->find('all', array(
				// 2016.07.14 murata.s ORANGE-121 CHG(S)
				//'fields' => array('*', 'MGenre.id', 'MGenre.genre_name', 'MGenre.commission_type', 'MCategory.category_name', 'MCategory.hide_flg',
				'fields' => array('*', 'MGenre.id', 'MGenre.genre_name', 'MGenre.commission_type', 'MCategory.category_name', 'MCategory.hide_flg', 'MCategory.disable_flg',
						// 2016.07.14 murata.s ORANGE-121 CHG(E)
						'(select m_sites.commission_type from m_site_genres inner join m_sites on m_site_genres.site_id = m_sites.id where m_site_genres.genre_id = "MGenre"."id" order by m_sites.id limit 1) AS "m_sites_commission_type"',
				),
				'joins' => array(
						array(
								'fields' => '*',
								'type' => 'left',
								'table' => 'm_genres',
								'alias' => 'MGenre',
								'conditions' => array(
										'MGenre.id = MCorpCategoriesTemp.genre_id'
								)
						),
						array(
								'fields' => '*',
								'type' => 'left',
								'table' => 'm_categories',
								'alias' => 'MCategory',
								'conditions' => array(
										'MCategory.id = MCorpCategoriesTemp.category_id',
// 2016.07.14 murata.s ORANGE-121 ADD(S)
//										'MCategory.disable_flg = false'
// 2016.07.14 murata.s ORANGE-121 ADD(E)
								)
						)
				),
				'conditions' => array(
						'MCorpCategoriesTemp.corp_id' => $id,
						'MCorpCategoriesTemp.temp_id' => $temp_id,
						'MCorpCategoriesTemp.delete_flag' => false
				),
				'order' => array(
						'MCategory.category_name' => 'asc'
				),
				'hasMany' => array('MCorp'),
		));

		return $list;
	}

// murata.s ORANGE-356 CHG(S)
	/**
	 * 指定された「corp_id」「genre_id」「category_id」からIDを一つ返します
	 *
	 */
	public function getMCorpCategoryID2($corp_id = null, $genre_id = null, $category_id = null, $temp_id = null) {

		$row = $this->find('first', array(
				'fields' => 'MCorpCategoriesTemp.id',
				'conditions' => array(
						'MCorpCategoriesTemp.corp_id' => $corp_id,
						'MCorpCategoriesTemp.genre_id' => $genre_id,
						'MCorpCategoriesTemp.category_id' => $category_id,
						'MCorpCategoriesTemp.temp_id' => $temp_id,
						'MCorpCategoriesTemp.delete_flag' => false
				),
		));

		return $row;
	}

// murata.s ORANGE-356 CHG(E)
	// ORANGE-347 ADD (S)
	public function findAllCategoryByCorpId($corp_id, $temp_id, $is_strict_check = true) {
		App::import('Model', 'MCorpTargetArea');
		App::import('Model', 'MTargetAreasTemp');
		$this->MCorpTargetArea = new MCorpTargetArea();
		$this->MTargetAreasTemp = new MTargetAreasTemp();
		$this->virtualFields['genre_name'] = '(SELECT genre_name FROM m_genres WHERE m_genres.id = MCorpCategoriesTemp.genre_id)';
		$this->virtualFields['commission_type'] = '(SELECT commission_type FROM m_genres WHERE m_genres.id = MCorpCategoriesTemp.genre_id)';
		$this->virtualFields['category_name'] = '(SELECT category_name FROM m_categories WHERE m_categories.id = MCorpCategoriesTemp.category_id)';
		$self = $this;
		$m_corp_categories = Hash::map(
										$this->find('all', array('conditions' => array(
														'MCorpCategoriesTemp.corp_id' => $corp_id,
														'MCorpCategoriesTemp.temp_id' => $temp_id,
														'MCorpCategoriesTemp.delete_flag' => false
												), 'recursive' => -1,
												'order' => array('category_name' => 'asc')
										))
										, '{n}.MCorpCategoriesTemp'
										, function($category) use($self, $is_strict_check) {
							if (!$is_strict_check) {
								return $category;
							}
							$corp_area = $self->MCorpTargetArea->find('list', array('fields' => array('jis_cd', 'jis_cd'), 'conditions' => array('corp_id' => Hash::get($category, 'corp_id'))));
							$category_area = $self->MTargetAreasTemp->find('list', array('fields' => array('jis_cd', 'jis_cd'), 'conditions' => array('corp_category_id' => Hash::get($category, 'id'))));
							if (Hash::get($category, 'target_area_type') < 2) {
								$category['target_area_type'] = (Hash::contains($corp_area, $category_area)) ? 1 : 2;
							}
							return $category;
						});
		return array('MCorpCategoriesTemp' => $m_corp_categories);
	}

	public function findAllGenreByCorpId($corp_id, $temp_id) {
		App::import('Model', 'MGenre');
		App::import('Model', 'MCorpTargetArea');
		App::import('Model', 'MTargetAreasTemp');
		$this->MCorpTargetArea = new MCorpTargetArea();
		$this->MGenre = new MGenre();
		$this->MTargetAreasTemp = new MTargetArea();
		$this->virtualFields['genre_name'] = '(SELECT genre_name FROM m_genres WHERE m_genres.id = MCorpCategoriesTemp.genre_id)';
		$this->virtualFields['commission_type'] = '(SELECT commission_type FROM m_genres WHERE m_genres.id = MCorpCategoriesTemp.genre_id)';
		$this->virtualFields['category_name'] = '(SELECT category_name FROM m_categories WHERE m_categories.id = MCorpCategoriesTemp.category_id)';
		$self = $this;
		$m_corp_categories = Hash::map(
										$this->find('all', array('conditions' => array(
														'MCorpCategoriesTemp.corp_id' => $corp_id,
														'MCorpCategoriesTemp.temp_id' => $temp_id,
												), 'recursive' => -1))
										, '{n}.MCorpCategoriesTemp'
										, function($category) use($self) {
							$corp_area = $self->MCorpTargetArea->find('list', array('fields' => array('jis_cd', 'jis_cd'), 'conditions' => array('corp_id' => Hash::get($category, 'corp_id'))));
							$category_area = $self->MTargetAreasTemp->find('list', array('fields' => array('jis_cd', 'jis_cd'), 'conditions' => array('corp_category_id' => Hash::get($category, 'id'))));
							if (Hash::get($category, 'target_area_type') === 0) {
								$category['target_area_type'] = (Hash::contains($corp_area, $category_area)) ? 1 : 2;
							}
							return $category;
						});
		return array('MCorpCategoriesTemp' => $m_corp_categories);
	}

	/**
	 * 
	 * @param type $temp_id MCorpCategoriesTemp.id
	 * @param type $append_query findAllに追加したいクエリ。削除はできない。
	 * @return type MCorpCategoriesTemp.{n}.{MGenre,MCategory,License}
	 */
	public function findAllRequiredLicensesByTempId($temp_id, $append_query = array()) {
		$this->virtualFields['commission_type'] = 'MGenre.commission_type';
		$default_query = array(
				'fields' => array(
						'MCorpCategoriesTemp' => '*',
						'MGenre.commission_type',
						'MGenre.genre_name',
						'MCategory.category_name',
						'License.name',
				),
				'joins' => array(
						array(
								'type' => 'INNER',
								'table' => 'm_genres',
								'alias' => 'MGenre',
								'conditions' => array(
										'MCorpCategoriesTemp.genre_id = MGenre.id',
								),
						),
						array(
								'type' => 'INNER',
								'table' => 'm_categories',
								'alias' => 'MCategory',
								'conditions' => array(
										'MCorpCategoriesTemp.category_id = MCategory.id',
								),
						),
						array(
								'type' => 'LEFT',
								'table' => 'category_license_link',
								'alias' => 'CategoryLicenseLink',
								'conditions' => array(
										'MCorpCategoriesTemp.category_id = CategoryLicenseLink.category_id',
								),
						),
						array(
								'type' => 'LEFT',
								'table' => 'license',
								'alias' => 'License',
								'conditions' => array(
										'CategoryLicenseLink.license_id = License.id',
								),
						),
				),
				'conditions' => array(
						'MCorpCategoriesTemp.temp_id' => $temp_id
				),
		);
		$results = $this->find('all', Hash::merge($default_query, $append_query));
		unset($this->virtualFields['commission_type']);
		$return_array = array();
		foreach ($results as $i => $result) {
			$return_array[$this->name][$i] = $result[$this->name];
			unset($result[$this->name]);
			$return_array[$this->name][$i] += $result;
		}
		return $return_array;
	}

	/**
	 * 
	 * @param type $temp_id MCorpCategoriesTemp.id
	 * @param type $append_query findAllに追加したいクエリ。削除はできない。
	 * @return type License.{n}.{,,}
	 */
	public function findAllHeldLicenseByTempId($temp_id) {
		App::import('Model', 'License');
		$this->License = new License();
		$this->License->virtualFields['have_lisense'] = 'bool_or(CorpLisenseLink.have_lisense)';
		$query = array(
				'fields' => array(
						'License.id',
						'License.name',
						'have_lisense'
				),
				'joins' => array(
						array(
								'type' => 'INNER',
								'table' => 'category_license_link',
								'alias' => 'CategoryLicenseLink',
								'conditions' => array(
										'License.id = CategoryLicenseLink.license_id',
								),
						),
						array(
								'type' => 'INNER',
								'table' => 'm_corp_categories_temp',
								'alias' => 'MCorpCategoriesTemp',
								'conditions' => array(
										'CategoryLicenseLink.category_id = MCorpCategoriesTemp.category_id',
								),
						),
						array(
								'type' => 'LEFT',
								'table' => 'corp_lisense_link',
								'alias' => 'CorpLisenseLink',
								'conditions' => array(
										'License.id = CorpLisenseLink.lisense_id',
										'MCorpCategoriesTemp.corp_id = CorpLisenseLink.corps_id',
								),
						),
				),
				'group' => array('License.id', 'License.name',),
				'conditions' => array(
						'MCorpCategoriesTemp.temp_id' => $temp_id
				),
		);
		$licenses = $this->License->find('all', $query);
		$licenses = array('License' => Hash::extract($licenses, '{n}.License'));
		foreach ($licenses['License'] as &$license) {
			$license = Hash::merge($license, $this->findAllAttachedFile($temp_id, Hash::get($license, 'id')));
		}
		return $licenses;
	}

	public function findAllAttachedFile($temp_id, $license_id) {
		App::import('Model', 'AgreementAttachedFile');
		$this->AgreementAttachedFile = new AgreementAttachedFile();
		$query = array(
				'fields' => array('*'),
				'joins' => array(
						array(
								'type' => 'INNER',
								'table' => 'corp_agreement_temp_link',
								'alias' => 'CorpAgreementTempLink',
								'conditions' => array(
										'AgreementAttachedFile.corp_id = CorpAgreementTempLink.corp_id',
								),
						),
				),
				'conditions' => array(
						'AgreementAttachedFile.license_id' => $license_id,
						'CorpAgreementTempLink.id' => $temp_id,
				),
		);
		$attached_files = $this->AgreementAttachedFile->find('all', $query);
		return array('AgreementAttachedFile' => Hash::extract($attached_files, '{n}.AgreementAttachedFile'));
	}

	/**
	 * 現在の本番データ（MCorpCategory、MCorpTargetArea）を契約用の仮データとしてコピーする
	 * @param type $corp_id
	 * @param type $temp_id
	 * @return boolean
	 * @throws Exception
	 */
	public function copyFromMCorpTargetAndMCorpCategory($corp_id, $temp_id) {
		if ($this->find('count', array('conditions' => array('temp_id' => $temp_id))) !== 0) {
			throw new Exception('既に契約申請中の対応カテゴリが作成されています。');
		}
		App::import('Model', 'MCorpCategory');
		$MCorpCategory = New MCorpCategory();
		App::import('Model', 'MTargetAreasTemp');
		$MTargetAreasTemp = New MTargetAreasTemp();

		$this->begin();
		$MCorpCategory->unbindModelAll();
		$current_mcc_array = $MCorpCategory->find('all', array(
				'fields' => array('id', 'genre_id', 'category_id', 'order_fee', 'order_fee_unit', 'introduce_fee', 'note', 'select_list', 'select_genre_category', 'target_area_type'),
				'recursive' => 1,
				'conditions' => array('corp_id' => $corp_id,)));

		$create_user_id = Hash::get($_SESSION, 'Auth.User.user_id', Hash::get($_SESSION, 'Auth.User.id'));

		foreach ($current_mcc_array as $mcc) {
			//MCorpCategoriesTempへ登録
			$mccid = Hash::get($mcc, 'MCorpCategory.id');
			$mcctemp = Hash::remove($mcc['MCorpCategory'], 'id');
			$mcctemp['version_no'] = 1;
			$mcctemp['temp_id'] = $temp_id;
			$mcctemp['corp_id'] = $corp_id;
			$this->create();
			if (!$this->save($mcctemp)) {
				$this->rollback();
				return false;
			}

			//MTargetArea→MTargetAreasTempへコピー(MCorpCategoriesTemp.idが必要)
			$sql = '';
			$sql[] = 'INSERT INTO m_target_areas_temp';
			$sql[] = '(   corp_id,     genre_id,   corp_category_id,     jis_cd, modified_user_id,          modified, created_user_id, created )';
			$sql[] = 'SELECT DISTINCT';
			$sql[] = 'MCC.corp_id, MCC.genre_id, CAST(? AS Integer), MTA.jis_cd,                ?, current_timestamp,               ?, current_timestamp ';
			$sql[] = 'FROM';
			$sql[] = '  m_target_areas MTA';
			$sql[] = '  INNER JOIN m_corp_categories MCC ON MTA.corp_category_id = MCC.id';
			$sql[] = 'WHERE';
			$sql[] = '  MCC.id = ?;';
			if ($this->query(implode("\n", $sql), array($this->getLastInsertID(), $create_user_id, $create_user_id, $mccid), false) === false) {
				$this->rollback();
				return false;
			}
		}
		$this->commit();
		return true;
	}

	/**
	 * MCorpCategoriesTemp と 
	 * @param type $corp_id
	 * @param type $temp_id
	 */
	public function fixTargetAreaType($corp_id, $temp_id) {
		$sql[] = 'UPDATE m_corp_categories_temp';
		$sql[] = 'SET';
		$sql[] = '    target_area_type = FIX.target_area_type,';
		$sql[] = '    modified = now()';
		$sql[] = 'FROM';
		$sql[] = '(';
		$sql[] = '  SELECT';
		$sql[] = '    MCCT.id';
		$sql[] = '  , CASE (';
		$sql[] = '      SELECT COUNT(*)';
		$sql[] = '      FROM';
		$sql[] = '        ( SELECT MCTA.jis_cd FROM m_corp_target_areas AS MCTA WHERE MCTA.corp_id = ? ) AS MCTA';
		$sql[] = '        FULL OUTER JOIN';
		$sql[] = '        ( SELECT MTAT.jis_cd FROM m_target_areas_temp AS MTAT WHERE MTAT.corp_category_id = MCCT.id ) AS MTAT';
		$sql[] = '        ON MCTA.jis_cd = MTAT.jis_cd';
		$sql[] = '      WHERE';
		$sql[] = '        MCTA.jis_cd IS NULL OR MTAT.jis_cd IS NULL';
		$sql[] = '    )';
		$sql[] = '    WHEN 0 THEN 1';
		$sql[] = '    ELSE 2';
		$sql[] = '    END AS target_area_type';
		$sql[] = '  FROM m_corp_categories_temp AS MCCT';
		$sql[] = '  WHERE MCCT.corp_id = ?';
		$sql[] = '    AND MCCT.temp_id = ?';
		$sql[] = ') AS FIX';
		$sql[] = 'WHERE m_corp_categories_temp.id = FIX.id';
		$sql[] = '  AND m_corp_categories_temp.target_area_type <> FIX.target_area_type;';
		return $this->query(implode("\n", $sql), array($corp_id, $corp_id, $temp_id), false);
	}

	public function saveAllCategories($data, $corp_id, $temp_id) {

		App::import('Model', 'MCategory');
		App::import('Model', 'MGenre');
		$MCategory = new MCategory();
		$MGenre = new MGenre();

		$remove_id_array = array();
		$append_category_array = array();

		foreach (Hash::get($data, 'MCorpCategory') as $genre_id => $categories) {
			foreach ($categories as $category_id => $category) {
				if (Hash::get($category, 'check') === '0') {
					if (Hash::get($category, 'id') !== '') {
						// チェックされていないがm_corp_categories_temp.id があるものは削除対象
						$remove_id_array[] = Hash::get($category, 'id');
					}
				} else {
					// チェックされているものは更新・追加対象
					if (Hash::get($category, 'id') !== '') {
						// idがあるものは専門性のみ変更
						// TODO:手数料変更前に契約途中だった場合、変更後に契約を進めると変更前の手数料で進行できるが問題無いか？
						$append_category = array(
								'id' => Hash::get($category, 'id'),
								'select_list' => Hash::get($category, 'select_list'),
						);
					} else {
						// idがないものは新規登録、手数料のためジャンルとカテゴリを取得
						$m_genre = $MGenre->findById($genre_id);
						$m_category = $MCategory->findById($category_id);
						$append_category = array(
								'corp_id' => $corp_id,
								'category_id' => $category_id,
								'genre_id' => $genre_id,
								'select_list' => Hash::get($category, 'select_list'),
								'temp_id' => $temp_id,
								'target_area_type' => 1,
						);
						if (Hash::get($m_genre, 'MGenre.commission_type') === '2') {
							$append_category['introduce_fee'] = Hash::get($m_category, 'MCategory.category_default_fee');
						} else {
							$append_category['order_fee'] = Hash::get($m_category, 'MCategory.category_default_fee');
							$append_category['order_fee_unit'] = Hash::get($m_category, 'MCategory.category_default_fee_unit');
						}
					}
					$append_category_array[] = $append_category;
				}
			}
		}

		$this->begin();
		// 削除
		if (!empty($remove_id_array) && !$this->deleteAll(array('id' => $remove_id_array))) {
			$this->rollback();
			return false;
		}
		// 登録
		if (!empty($append_category_array) && !$this->saveMany($append_category_array)) {
			$this->rollback();
			return false;
		}
		// 基本エリア適用対象となるカテゴリの対応エリアを同期する
		App::import('Model', 'MCorpTargetArea');
		$MCorpTargetArea = new MCorpTargetArea();
		if (!$MCorpTargetArea->syncToMTargetAreasTemp($corp_id, $temp_id)) {
			$this->rollback();
			return false;
		}
		$this->Commit();
		return true;
	}

	// ORANGE-347 ADD (E)

	// ORANGE-393 ADD(S)
	/**
	 * 一時IDに対応する企業別カテゴリマスタ(一時用)を登録
	 *
	 * @param int $corp_id
	 * @param int $temp_id
	 * @return 処理結果
	 */
	public function copyCorpCatgoryByTempId($corp_id, $temp_id = null){
		$corpCategories = $this->findCategoryTempCopy($corp_id, $temp_id);
		$registed = false;
		foreach($corpCategories as $v){
			if(!empty($v['MCorpCategoriesTemp']['id'])){
				$registed = true;
				break;
			}
		}
		if(!$registed){
			// 一時用カテゴリデータが未登録
			$data = Hash::extract($corpCategories, '{n}.MCorpCategoriesTemp');

			if(!empty($data)){
				return $this->saveAll($data);
			}
		}else{
			// 一時用カテゴリデータが登録済み
			return true;
		}
	}

	/**
	 * 一時IDに対応する企業別カテゴリマスタ(一時用)を取得
	 * データが未登録の場合は1世代前のデータを対応するデータとして登録する
	 *
	 * @param int $corp_id
	 * @param int $temp_id
	 * @return 対応する企業別カテゴリマスタ(一時用)
	 */
	public function findCategoryTempCopy($corp_id, $temp_id = null){
		$results = array();
		$count = $this->find('count', array(
				'conditions' => array('temp_id' => $temp_id)
		));
		if($count == 0){
			App::import('Model', 'CorpAgreemenetTempLink');
			$corpAgreementTempLink = new CorpAgreementTempLink();
			$latestTempLink = $corpAgreementTempLink->find('first', array(
					'fields' => 'id',
					'conditions' => array(
							'corp_id' => $corp_id,
							'id != ' => $temp_id),
					'order' => array('id' => 'desc')
			));
			if(!empty($latestTempLink)){
				$results = $this->getMCorpCategoryGenreList($corp_id, $latestTempLink['CorpAgreementTempLink']['id']);
				foreach($results as $k=>$v){
					$results[$k]['MCorpCategoriesTemp']['id'] = null;
					$results[$k]['MCorpCategoriesTemp']['temp_id'] = $temp_id;
					$results[$k]['MCorpCategoriesTemp']['action'] = null;
				}
			}else{
				App::import('Model', 'MCorpCategory');
				$mCorpCategory = new MCorpCategory();
				$corpCategories = $mCorpCategory->getMCorpCategoryGenreList($corp_id);
				foreach($corpCategories as $val){
					$results[] = array(
							'MCorpCategoriesTemp' => array(
									'id' => null,
									'temp_id' => $temp_id,
									'corp_id' => $val['MCorpCategory']['corp_id'],
									'genre_id' => $val['MCorpCategory']['genre_id'],
									'category_id' => $val['MCorpCategory']['category_id'],
									'order_fee' => $val['MCorpCategory']['order_fee'],
									'order_fee_unit' => $val['MCorpCategory']['order_fee_unit'],
									'introduce_fee' => $val['MCorpCategory']['introduce_fee'],
									'note' => $val['MCorpCategory']['note'],
									'select_list' => $val['MCorpCategory']['select_list'],
									'select_genre_category' => $val['MCorpCategory']['select_genre_category'],
									'target_area_type' => $val['MCorpCategory']['target_area_type'],
									'corp_commission_type' => $val['MCorpCategory']['corp_commission_type']
							),
							'MGenre' => $val['MGenre'],
							'MCategory' => $val['MCategory']
					);
				}
			}
		}else{
			$results = $this->getMCorpCategoryGenreList($corp_id, $temp_id);
		}
		return $results;
	}
	// ORANGE-393 ADD(E)
}

// 2016.06.06 murata.s ORANGE-102 ADD(E)