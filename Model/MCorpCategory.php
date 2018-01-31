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
		// 2017.05.22 CHG S ORANGE-347 数値の0が未入力と判定されないよう修正
		if(!empty($this->data['MCorpCategory']['order_fee']) &&
		$this->data['MCorpCategory']['order_fee_unit'] !== 0 &&
		$this->data['MCorpCategory']['order_fee_unit'] == ''){
			return false;
		}
		return true;
		// 2017.05.22 CHG E ORANGE-347
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
// 2016.10.05 murata.s CHG(S) 脆弱性 SQLインジェクション対応
// 				'conditions' => array(
// 						'MCorpCategory.corp_id = '. $corp_id
// 				),
				'conditions' => array(
						'MCorpCategory.corp_id' => $corp_id
				),
// 2016.10.05 murata.s CHG(S) 脆弱性 SQLインジェクション対応
				'group' => 'MCorpCategory.genre_id, MGenre.id',
				'order' => array('MGenre.genre_group' => 'asc', 'MGenre.genre_name' => 'asc'),
		));

		return $list;

	}
	/**
	 * 指定されたIDからジャンル名を返します
	 *
	 */
	public function getWithGenreAndCategory($id = null){

		$row = $this->find('first', array(
				'fields' => '*, MGenre.genre_name, MCategory.category_name',
				'joins' => array(
					array(
						'type' => 'left',
						'table' => 'm_genres',
						'alias' => 'MGenre',
						'conditions' => array(
								'MGenre.id = MCorpCategory.genre_id'
						)
					),
					array(
						'type' => 'left',
						'table' => 'm_categories',
						'alias' => 'MCategory',
						'conditions' => array(
								'MCategory.id = MCorpCategory.category_id'
						)
					),
				),
// 2016.10.05 murata.s CHG(S) 脆弱性 SQLインジェクション対応
// 				'conditions' => array(
// 						'MCorpCategory.id = '. $id
// 				),
				'conditions' => array(
						'MCorpCategory.id' => $id
				),
		// 2016.10.05 murata.s CHG(E) 脆弱性 SQLインジェクション対応
		));

		return $row;

	}

	/**
	 * 指定された「corp_id」「genre_id」からIDを一つ返します
	 *
	 */
	public function getMCorpCategoryID($corp_id = null, $genre_id = null){

// 2016.10.05 murata.s CHG(S) 脆弱性 SQLインジェクション対応
// 		$row = $this->find('first', array(
// 				'fields' => 'MCorpCategory.id',
// 				'conditions' => array(
// 						'MCorpCategory.corp_id = '. $corp_id, 'MCorpCategory.genre_id = '. $genre_id
// 				),
// 		));
		$row = $this->find('first', array(
				'fields' => 'MCorpCategory.id',
				'conditions' => array(
						'MCorpCategory.corp_id' => $corp_id,
						'MCorpCategory.genre_id' => $genre_id
				),
		));
// 2016.10.05 murata.s CHG(S) 脆弱性 SQLインジェクション対応

		return $row;

	}

	/**
	 * 指定された「corp_id」「genre_id」からIDリストを返します
	 *
	 */
	public function getMCorpCategoryIDList($corp_id = null, $genre_id = null){
// 2016.10.05 murata.s CHG(S) SQLインジェクション対応
// 		$list = $this->find('all', array(
// 				'fields' => 'MCorpCategory.id, MCorpCategory.target_area_type',
// 				'conditions' => array(
// 						'MCorpCategory.corp_id = '. $corp_id, 'MCorpCategory.genre_id = '. $genre_id
// 				),
// 		));
		$list = $this->find('all', array(
				'fields' => 'MCorpCategory.id, MCorpCategory.target_area_type',
				'conditions' => array(
						'MCorpCategory.corp_id' => $corp_id,
						'MCorpCategory.genre_id' => $genre_id
				),
		));
// 2016.10.05 murata.s CHG(E) SQLインジェクション対応

		return $list;

	}

	/**
	 * 指定された「corp_id」からIDリストを返します
	 *
	 */
	public function getMCorpCategoryIDList2($corp_id = null){
// 2016.10.05 murata.s CHG(S) SQLインジェクション対応
// 		$list = $this->find('all', array(
// 				'fields' => 'MCorpCategory.id, MCorpCategory.genre_id',
// 				'conditions' => array(
// 						'MCorpCategory.corp_id = '. $corp_id
// 				),
// 		));
		$list = $this->find('all', array(
				'fields' => 'MCorpCategory.id, MCorpCategory.genre_id',
				'conditions' => array(
						'MCorpCategory.corp_id' => $corp_id
				),
		));
// 2016.10.05 murata.s CHG(E) SQLインジェクション対応

		return $list;

	}

	/**
	 * 指定された「corp_id」から対応可能ジャンルリストを返します
	 *
	 */
	public function getMCorpCategoryGenreList($id = null){

		$list = $this->find ('all', array (
			  // 2015.12.17 h.hanaki CHG ORANGE-1022
			  // 'fields' => '*, MGenre.id, MGenre.genre_name, MGenre.commission_type, MCategory.category_name',
				// 2016.01.12 n.kai MOD ORANGE-926
				// 2016.07.14 murata.s ORANGE-121 CHG(S)
// 				'fields' => array('*', 'MGenre.id', 'MGenre.genre_name', 'MGenre.commission_type', 'MCategory.category_name', 'MCategory.hide_flg',
				'fields' => array('*', 'MGenre.id', 'MGenre.genre_name', 'MGenre.commission_type', 'MCategory.category_name', 'MCategory.hide_flg', 'MCategory.disable_flg',
				// 2016.07.14 murata.s ORANGE-121 CHG(E)
								  '(select m_sites.commission_type from m_site_genres inner join m_sites on m_site_genres.site_id = m_sites.id where m_site_genres.genre_id = "MGenre"."id" order by m_sites.id limit 1) AS "m_sites_commission_type"',
								 ),
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
											'MCategory.id = MCorpCategory.category_id',
// 2016.07.14 murata.s ORANGE-121 ADD(S)
//											'MCategory.disable_flg = false'
// 2016.07.14 murata.s ORANGE-121 ADD(E)
									)
								)
		),
		'conditions' => array (
					'MCorpCategory.corp_id' => $id
			),
		'order' => array (
					'MCategory.category_name' => 'asc'
			),
			'hasMany'=>array('MCorp'),
		));

		return $list;

	}

	/**
	 * 指定された「corp_id」「genre_id」「category_id」からIDを一つ返します
	 *
	 */
	public function getMCorpCategoryID2($corp_id = null, $genre_id = null, $category_id = null){

// 2016.10.05 murata.s CHG(S) 脆弱性 SQLインジェクション対応
// 		$row = $this->find('first', array(
// 				'fields' => 'MCorpCategory.id',
// 				'conditions' => array(
// 						'MCorpCategory.corp_id = '. $corp_id, 'MCorpCategory.genre_id = '. $genre_id, 'MCorpCategory.category_id = '. $category_id
// 				),
// 		));
		$row = $this->find('first', array(
				'fields' => 'MCorpCategory.id',
				'conditions' => array(
						'MCorpCategory.corp_id' => $corp_id,
						'MCorpCategory.genre_id' => $genre_id,
						'MCorpCategory.category_id' => $category_id
				),
		));
// 2016.10.05 murata.s CHG(S) 脆弱性 SQLインジェクション対応

		return $row;

	}
	// 2015.08.17 s.harada ADD start 画面デザイン変更対応
	// 2016.04.20 ORANGE-1210 murata.s ADD(S)
// 2016.06.30 ORANGE-22 murata.s DEL(S) 未使用とするため削除（MCategoryを使用する）
// 	public function checkLisense($corp_id = null, $category_id = null){
//
// 		$options = array(
// 				'fields' => array(
// 						'License.certificate_required_flag',
// 						'CorpLisenseLink.have_lisense',
// 						'CorpLisenseLink.lisense_check',
// 						// 2016.06.30 murata.s ORANGE-116 ADD(S)
// 						'MCategory.license_condition_type'
// 						// 2016.06.30 murata.s ORANGE-116 ADD(E)
// 				),
// 				'conditions' => array(
// 						'MCorpCategory.corp_id' => $corp_id,
// 						'MCorpCategory.category_id' => $category_id
// 				),
// 				'joins' => array(
// 						array(
// 								'fields' => '*',
// 								'type' => 'left',
// 								'table' => 'category_license_link',
// 								'alias' => 'CategoryLicenseLink',
// 								'conditions' => array(
// 										'CategoryLicenseLink.category_id = MCorpCategory.category_id'
// 								)
// 						),
// 						array(
// 								'fields' => '*',
// 								'type' => 'left',
// 								'table' => 'license',
// 								'alias' => 'License',
// 								'conditions' => array(
// 										'License.id = CategoryLicenseLink.license_id'
// 								)
// 						),
// 						array(
// 								'fields' => '*',
// 								'type' => 'left',
// 								'table' => 'corp_lisense_link',
// 								'alias' => 'CorpLisenseLink',
// 								'conditions' => array(
// 										'CorpLisenseLink.lisense_id = CategoryLicenseLink.license_id',
// 										'CorpLisenseLink.corps_id = MCorpCategory.corp_id',
// 										'CorpLisenseLink.delete_flag = false'
// 								)
// 						),
// 						// 2016.06.30 murata.s ORANGE-116 ADD(S)
// 						array(
// 								'fields' => '*',
// 								'type' => 'left',
// 								'table' => 'm_categories',
// 								'alias' => 'MCategory',
// 								'conditions' => array(
// 										'MCategory.id = MCorpCategory.category_id'
// 								)
// 						)
// 						// 2016.06.30 murata.s ORANGE-116 ADD(E)
// 				)
// 		);
//
// 		$corpCategories = $this->find('all', $options);
//
//
// // 2016.06.30 murata.s ORANGE-116 CHG(S)
// // 		$result = true;
// // 		foreach ($corpCategories as $corpCategory){
// //
// // 			if(isset($corpCategory['License'])){
// // 				// ライセンスの有無による判断が必要
// // 				if($corpCategory['License']['certificate_required_flag'])
// // 					if($corpCategory['CorpLisenseLink']['lisense_check'] == 'OK')
// // 						$result = $result && true;
// // 					else
// // 						$result = $result && false;
// // 			}
// // 		}
// //
// // 		return $result;
//
// 		foreach ($corpCategories as $corpCategory){
//
// 			// カテゴリに紐付くライセンスがある
// 			if(isset($corpCategory['License']['certificate_required_flag'])){
// 				// ライセンスが必須
// 				if($corpCategory['License']['certificate_required_flag']){
// 					$lisense_check = ($corpCategory['CorpLisenseLink']['lisense_check'] == 'OK');
// 					if($corpCategory['MCategory']['license_condition_type'] === 1)
// 						$required_license = isset($required_license) ? ($required_license && $lisense_check) : $lisense_check;
// 					elseif($corpCategory['MCategory']['license_condition_type'] === 2)
// 					$required_license = isset($required_license) ? ($required_license || $lisense_check) : $lisense_check;
// 				}
// 			}
// 		}
// 		if(isset($required_license)) return $required_license;
// 		else return true;
// // 2016.06.30 murata.s ORANGE-116 CHG(E)
//
// 	}
// 2016.06.30 ORANGE-22 murata.s DEL(E) 未使用とするため削除
	// 2016.04.14 ORANGE-1210 murata.s ADD(E)

	// ORANGE-347 ADD (S)
	public function findAllCategoryByCorpId($corp_id, $is_strict_check = true) {
		App::import('Model', 'MCorpTargetArea');
		App::import('Model', 'MTargetArea');
		$this->MCorpTargetArea = new MCorpTargetArea();
		$this->MTargetArea = new MTargetArea();
		$this->virtualFields['genre_name'] = '(SELECT genre_name FROM m_genres WHERE m_genres.id = MCorpCategory.genre_id)';
		$this->virtualFields['commission_type'] = '(SELECT commission_type FROM m_genres WHERE m_genres.id = MCorpCategory.genre_id)';
		$this->virtualFields['category_name'] = '(SELECT category_name FROM m_categories WHERE m_categories.id = MCorpCategory.category_id)';
		$self = $this;
		$m_corp_categories = Hash::map(
										$this->find('all', array('conditions' => array('MCorpCategory.corp_id' => $corp_id), 'recursive' => -1))
										, '{n}.MCorpCategory'
										, function($category) use($self, $is_strict_check) {
							if (!$is_strict_check) {
								return $category;
							}
							$corp_area = $self->MCorpTargetArea->find('list', array('fields' => array('jis_cd', 'jis_cd'), 'conditions' => array('corp_id' => Hash::get($category, 'corp_id'))));
							$category_area = $self->MTargetArea->find('list', array('fields' => array('jis_cd', 'jis_cd'), 'conditions' => array('corp_category_id' => Hash::get($category, 'id'))));
							if (Hash::get($category, 'target_area_type') < 2) {
								$category['target_area_type'] = (Hash::contains($corp_area, $category_area)) ? 1 : 2;
							}
							return $category;
						});
		return array('MCorpCategory' => $m_corp_categories);
	}

	// ORANGE-347 ADD (E)
	// 2017/12/05 h.miyake ORANGE-603 ADD(S)
	/**
	 * 指定された「corp_id」から対応可能ジャンルIDとジャンル名をかえします。
	 *   $list[genre_id] = ジャンル名;
	 */
	public function getGenreIdNameMCorpList($id = null){
		$row = $this->find ('all', array (
				'fields' => array('DISTINCT MGenre.id', 'MGenre.genre_name'),
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
		),
		'conditions' => array (
					'MCorpCategory.corp_id' => $id
			),
		'order' => array (
					'MGenre.id' => 'asc'
			),
			'hasMany'=>array('MCorp'),
		));
		foreach($row as $key => $value) {
			$list[$value[MGenre][id]] = $value[MGenre][genre_name];
		}
		return $list;

	}
	
	/**
	 * 指定された「corp_id」「genre_id」から対応可能カテゴリIDと名をかえします。
	 *   $list[category_id] = カテゴリー名;
	 */
	public function getcategoryNameMCorpGenreIdList($corpId = null,$genreId = null){
		$row = $this->find ('all', array (
				'fields' => array('DISTINCT MCategory.id', 'MCategory.category_name'),
				'joins' => array (
								array (
									'fields' => '*',
									'type' => 'left',
									'table' => 'm_categories',
									'alias' => 'MCategory',
									'conditions' => array (
										'MCategory.id = MCorpCategory.category_id'
									)
								),
		),
		'conditions' => array (
					'MCorpCategory.corp_id' => $corpId,
					'MCorpCategory.genre_id' => $genreId
			),
		'order' => array (
					'MCategory.id' => 'asc'
			),
			'hasMany'=>array('MCorp'),
		));
		foreach($row as $key => $value) {
			$list[$value[MCategory][id]] = $value[MCategory][category_name];
		}
		return $list;
	}
	// 2017/12/05 h.miyake ORANGE-603 ADD(E)
}
