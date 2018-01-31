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
// 2016.07.14 murata.s ORANGE-121 CHG(S)
// 	public function getList($genre_id = null){
	public function getList($genre_id = null, $all_catgory = false){
// 2016.07.14 murata.s ORANGE-121 CHG(E)
		// 検索条件の設定
		if(!empty($genre_id)){
			$conditions = array('genre_id' => $genre_id);
		} else {
			$conditions = array();
		}
// 2016.07.13 murata.s ORANGE-121 ADD(S)
		if(!$all_catgory)
			$conditions['disable_flg'] = false;
// 2016.07.13 murata.s ORANGE-121 ADD(E)
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

// murata.s ORANGE-480 ADD(S)
	/**
	 * カテゴリ一覧(SelectBox用)を返します
	 * @param int $genre_id
	 * @param boolean $all_category
	 */
	public function getListStHide($genre_id = null, $all_catgory = false){
		// 検索条件の設定
		$conditions = array();
		if(!empty($genre_id)){
			$conditions = array('genre_id' => $genre_id);
		}

		if(!$all_catgory){
			$conditions['disable_flg'] = false;
		}
		$conditions['st_hide_flg'] = 0;

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
// murata.s ORANGE-480 ADD(E)

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

	/**
	 * 指定されたカテゴリのデフォルト取次時手数料、デフォルト手数料単位を取得する
	 * ORANGE-1019 【案件管理】取次先情報の「取次時手数料率」の登録を自動化してください。
	 * kishimoto@tobila 2016.02.19
	 * @param unknown $id
	 * @return string
	 */
	public function getDefault_fee($id) {
		$category_default_fee = '';
		$conditions = array('id' => $id);
		$mCategory = $this->find('first', array('conditions'=>$conditions, 'order'=>'MCategory.id asc'));
		if(empty($mCategory) == false) {
			$category_default_fee['category_default_fee'] = $mCategory['MCategory']['category_default_fee'];
			$category_default_fee['category_default_fee_unit'] = $mCategory['MCategory']['category_default_fee_unit'];
		}
		return $category_default_fee;
	}

	// 2015.08.17 s.harada MOD start 画面デザイン変更対応
	/**
	 * すべてのカテゴリ一覧を返します
	 *
	 */
	public function getAllList($corp_id = null){

		$list = $this->find('all', array(
			  // 2015.12.17 h.hanaki CHG ORANGE-1022
			  //'fields' => '*, MGenre.id, MGenre.genre_name, MGenre.commission_type, MGenre.genre_group, MGenre.default_fee, MGenre.default_fee_unit, MGenre.registration_mediation, MCorpCategory.id, MCorpCategory.modified, MCorpCategory.select_list',
				'fields' => array('*', 'MGenre.id', 'MGenre.genre_name', 'MGenre.commission_type', 'MGenre.genre_group', 'MGenre.default_fee',
								  'MGenre.default_fee_unit', 'MGenre.registration_mediation', 'MCorpCategory.id', 'MCorpCategory.modified', 'MCorpCategory.select_list',
								  '(select m_sites.commission_type from m_site_genres inner join m_sites on m_site_genres.site_id = m_sites.id where m_site_genres.genre_id = "MGenre"."id" order by m_sites.id limit 1) AS "m_sites_commission_type"'
								  ),
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

	// 2016.06.06 murata.s ORANGE-102 ADD(S)
	/**
	 * すべてのカテゴリ一覧を返します
	 *
	 */
	public function getTempAllList($corp_id = null, $temp_id = null){

		$list = $this->find('all', array(
				// murata.s ORANGE-261 CHG(S)
				'fields' => array('*', 'MGenre.id', 'MGenre.genre_name', 'MGenre.commission_type', 'MGenre.genre_group', 'MGenre.default_fee',
						'MGenre.default_fee_unit', 'MGenre.registration_mediation', 'MCorpCategoriesTemp.id', 'MCorpCategoriesTemp.modified', 'MCorpCategoriesTemp.select_list', 'MCorpCategoriesTemp.corp_commission_type',
						'(select m_sites.commission_type from m_site_genres inner join m_sites on m_site_genres.site_id = m_sites.id where m_site_genres.genre_id = "MGenre"."id" order by m_sites.id limit 1) AS "m_sites_commission_type"'
				),
				// murata.s ORANGE-261 CHG(E)
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
								'table' => 'm_corp_categories_temp',
								'alias' => 'MCorpCategoriesTemp',
								'conditions' => array(
										'MCorpCategoriesTemp.genre_id = MCategory.genre_id',
										'MCorpCategoriesTemp.category_id = MCategory.id',
										'MCorpCategoriesTemp.corp_id = '. $corp_id,
										'MCorpCategoriesTemp.temp_id = '. $temp_id,
										'MCorpCategoriesTemp.delete_flag = false'
								)
						),
				),
				'conditions' => array(
						'MGenre.valid_flg = 1',
						'MCategory.hide_flg = 0',
// 2016.07.14 murata.s ORANGE-121 ADD(S)
						'MCategory.disable_flg = false'
// 2016.07.14 murata.s ORANGE-121 ADD(E)
				),
				'order' => array('MGenre.genre_group' => 'asc', 'MCategory.display_order' => 'asc'),
		));

		return $list;

	}

	// 2016.06.06 murata.s ORANGE-102 ADD(E)

// 2016.06.30 murata.s ORANGE-22, ORANGE-116 ADD(S)
// 2017/10/19 ORANGE-541 m-kawamoto DEL(S)
//	public function checkLisense($corp_id = null, $category_id = null){
//
//		// 2016.07.19 murata.s ORANGE-22 ADD(S)
//		// do_check_license == falseの場合はライセンスチェックを行わない
//		// 全てライセンスOKとして処理する
//		if(!Configure::read('do_check_license')) return true;
//		// 2016.07.19 murata.s ORANGE-22 ADD(E)
//
//		// 以下の企業IDの場合はライセンスチェックを行わない
//		if($corp_id == 1751 // 【開拓依頼中】
//				|| $corp_id == 1755 // 【要ヒアリングor連絡待ち】
//				|| $corp_id == 3539){ // 【SF用】取次ぎ前失注用(質問のみ等)
//			return true;
//		}
//
//		$options = array(
//				// murata.s ORANGE-429 CHG(S)
//				'fields' => array(
//						'License.certificate_required_flag',
//						'CorpLisenseLink.have_lisense',
//						'CorpLisenseLink.lisense_check',
//						'CorpLisenseLink.license_expiration_date',
//						'MCategory.license_condition_type'
//				),
//				// murata.s ORANGE-429 CHG(E)
//				'conditions' => array(
//						'MCategory.id' => $category_id
//				),
//				'joins' => array(
//						array(
//								'fields' => '*',
//								'type' => 'left',
//								'table' => 'category_license_link',
//								'alias' => 'CategoryLicenseLink',
//								'conditions' => array(
//										'CategoryLicenseLink.category_id = MCategory.id'
//								)
//						),
//						array(
//								'fields' => '*',
//								'type' => 'left',
//								'table' => 'license',
//								'alias' => 'License',
//								'conditions' => array(
//										'License.id = CategoryLicenseLink.license_id'
//								)
//						),
//						array(
//								'fields' => '*',
//								'type' => 'left',
//								'table' => 'corp_lisense_link',
//								'alias' => 'CorpLisenseLink',
//								'conditions' => array(
//										'CorpLisenseLink.lisense_id = CategoryLicenseLink.license_id',
//										'CorpLisenseLink.corps_id' => $corp_id,
//										'CorpLisenseLink.delete_flag = false'
//								)
//						)
//				)
//		);
//
//		$categories = $this->find('all', $options);
//		foreach ($categories as $category){
//
//// murata.s ORANGE-429 CHG(S)
//			// カテゴリに紐付くライセンスがある
//			if(isset($category['License']['certificate_required_flag'])){
//				// ライセンスが必須
//				if($category['License']['certificate_required_flag']){
//					$lisense_check = ($category['CorpLisenseLink']['lisense_check'] == 'OK');
//					$expiration_datetime = !empty($category['CorpLisenseLink']['license_expiration_date'])
//						?  new DateTime($category['CorpLisenseLink']['license_expiration_date']) : null;
//					$license_expiration_date = !empty($expiration_datetime)
//						?  $expiration_datetime->format("Y/m/d") >= date("Y/m/d", time()) : true;
//					if($category['MCategory']['license_condition_type'] === 1){
//						$required_license = isset($required_license) ? ($required_license && $lisense_check) : $lisense_check;
//						$expired_license = isset($expired_license) ? ($expired_license && $license_expiration_date) : $license_expiration_date;
//					}elseif($category['MCategory']['license_condition_type'] === 2){
//						$required_license = isset($required_license) ? ($required_license || $lisense_check) : $lisense_check;
//						$expired_license = isset($expired_license) ? ($expired_license || $license_expiration_date) : $license_expiration_date;
//					}
//				}
//			}
//		}
//		if(isset($required_license) && isset($expired_license)) return $required_license && $expired_license;
//		else return true;
//// murata.s ORANGE-429 CHG(E)
//
//	}
// 2017/10/19 ORANGE-541 m-kawamoto DEL(E)
// 2016.06.30 murata.s ORANGE-22, ORANGE-116 ADD(E)

// murata.s ORANGE-261 ADD(S)
	/**
	 * カテゴリの取次形態を取得する
	 * @param int $category_id カテゴリID
	 * @return 取次形態
	 */
	function getCommissionType($category_id=null){

		$category = $this->find('first', array(
				'fields' => array('MGenre.commission_type'),
				'joins' => array(
						array(
								'table' => 'm_genres',
								'alias' => 'MGenre',
								'conditions' => array(
										'MGenre.id = MCategory.genre_id'
								)
						)
				),
				'conditions' => array(
						'MCategory.id' => $category_id
				)
		));

		return !empty($category) ? $category['MGenre']['commission_type'] : '';
	}
// murata.s ORANGE-261 ADD(E)

}