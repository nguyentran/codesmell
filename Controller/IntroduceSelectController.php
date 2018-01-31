<?php
App::uses('AppController', 'Controller');
App::import( 'Vendor', 'PHPWord', array('file'=>'PHPWord.php') );

class IntroduceSelectController extends AppController {

	public $name = 'IntroduceSelect';
	// 2016.02.16 ORANGE-1247 k.iwai ADD(S).
	// 2016.05.17 murata.s ORANGE-1210 CHG(S)
	//public $helpers = array('Credit');
	public $helpers = array('Credit', 'Agreement');
	// 2016.05.17 murata.s ORANGE-1210 CHG(E)
	// 2016.02.16 ORANGE-1247 k.iwai ADD(E).
	public $components = array('Session');
	public $uses = array('MPost','MCorp', 'MTargetArea', 'MCategory', 'CommissionInfo', 'AffiliationInfo');

	public function beforeFilter(){

		$this->layout = 'subwin';

		parent::beforeFilter();
	}

	/**
	 * 初期表示（一覧）
	 *
	 * @param unknown_type $address1
	 * @param unknown_type $address2
	 * @param unknown_type $category
	 * @throws Exception
	 */
	public function index() {

	}

	public function display() {

		$results = array();

		// 値をチェック
		if(empty($this->request->data)){
			throw new Exception();
		}

		// 2016.01.11 n.kai ADD start ORANGE-1194
		if(isset($this->request->data['address2'])){
			// address2の先頭と末尾のスペースを除去する
			$date_address2 = $this->request->data['address2'];
			$data_address2 = preg_replace( '/^[ 　]+/u', '', $date_address2);
			$data_address2 = preg_replace( '/[ 　]+$/u', '', $data_address2);
			$this->request->data['address2'] = $data_address2;
		}
		// 2016.01.11 n.kai ADD end ORANGE-1194

		$data = $this->request->data;

		if(!isset($this->request->data['search'])){
			$data['jis_cd'] = '';
			// 郵便マスタからエリアコード(JIS CODE)を取得
			if($this->request->data['address1'] != '99'){
				$m_post_data = $this->__get_jis_cd(Util::getDivTextJP('prefecture_div', $this->request->data['address1']), $this->request->data['address2']);
				if(!empty($m_post_data)){
					$data['jis_cd'] = $m_post_data ["MPost"]["jis_cd"];
				}
			}
			// 現場住所
			$data['address'] = Util::getDivTextJP('prefecture_div', $this->request->data['address1']) . $this->request->data['address2'];
			// カテゴリ名
			$category_data = $this->__get_category_name($this->request->data['category_id']);
			$data['category_name'] = $category_data['MCategory']['category_name'];
		}

		// 紹介先一覧を取得
		$results = $this->__get_introduce_list($data);

		// 登録済み情報取得
		$commission_list = array();
		if(!empty($data['demand_id'])){
			$commission_list = $this->__get_already_commission_list($data['demand_id']);
		}

		// 画面項目セット
		$this->set('results', $results);
		$this->set('commission_list', $commission_list);
		$this->data = $data;
	}

	/**
	 * カテゴリ名を取得
	 *
	 * @param unknown $category_cd
	 * @return unknown
	 */
	private function __get_category_name($category_cd) {

		$result = $this->MCategory->findById($category_cd);

		return $result;
	}

	/**
	 * 都道府県、市区町村からエリアコードを取得します
	 *
	 * @param unknown_type $address1
	 * @param unknown_type $address2
	 */
	private function __get_jis_cd($address1, $address2){

		// 郵便マスタからエリアコード(JIS CODE)を取得
		$conditions['MPost.address1'] = $address1;
		// ORANGE-1072 市区町村に含まれる文字を大文字・小文字を検索対象にする(m_posts.address2)
		$upperAddress2 = $address2;
		$upperAddress2 = str_replace('ヶ', 'ケ', $upperAddress2);
		$upperAddress2 = str_replace('ﾉ', 'ノ', $upperAddress2);
		$upperAddress2 = str_replace('ﾂ', 'ツ', $upperAddress2);
		$lowerAddress2 = $address2;
		$lowerAddress2 = str_replace('ケ', 'ヶ', $lowerAddress2);
		$lowerAddress2 = str_replace('ノ', 'ﾉ', $lowerAddress2);
		$lowerAddress2 = str_replace('ツ', 'ﾂ', $lowerAddress2);
		$conditions[] = array(
			'OR' => array(
				array('MPost.address2' => $upperAddress2),
				array('MPost.address2' => $lowerAddress2),
			)
		);

		$result = $this->MPost->find('first',
				array('fields' => 'jis_cd',
						'conditions' => $conditions
				)
		);

		return $result;
	}

	/**
	 * エリアコード、カテゴリコードから紹介先候補の一覧を取得します
	 *
	 * @param unknown $data
	 * @return unknown
	 */
	private function __get_introduce_list($data=array()){

		$joins = array();
		$conditions = array();

		// 加盟状態 (加盟)
		$conditions['MCorp.affiliation_status'] = 1;

		// 企業名
		if(!empty($data['corp_name'])){
			$conditions['z2h_kana(MCorp.corp_name) like'] = '%'. Util::chgSearchValue($data['corp_name']) .'%';
		}

		// 2016.05.30 murata.s ORANGE-75 ADD(S)
		// 契約更新フラグ
		$conditions['MCorp.commission_accept_flg not in'] = array(0, 3);
		// 2016.05.30 murata.s ORANGE-75 ADD(E)

// 2016.06.30 murata.s ORANGE-22 ADD(S)
		$fields = 'MCorp.id, MCorp.corp_name, MCorp.coordination_method';
// 2016.06.30 murata.s ORANGE-22 ADD(E)

		// カテゴリ・対応可能エリア条件解除(チェックなし)
		if(empty($data['target_check'])){

			if(empty($data['category_id']) || empty($data['jis_cd'])){
				// エラー 表示
				$this->Session->setFlash(__('ErrorCommissionSelect', true), 'default', array('class' => 'error-message'));
				return array();
			}
			// カテゴリ
			$conditions['MCorpCategory.category_id'] = $data['category_id'];

			$joins = array (
					array (
							'fields' => '*',
							'type' => 'inner',
							"table" => "m_corp_categories",
							"alias" => "MCorpCategory",
							"conditions" => array (
									"MCorpCategory.corp_id = MCorp.id"
							)
					),
					array (
							'fields' => '*',
							'type' => 'inner',
							"table" => "affiliation_infos",
							"alias" => "AffiliationInfo",
							"conditions" => array (
									"AffiliationInfo.corp_id = MCorp.id"
							)
					),
					array (
							'fields' => '*',
							'type' => 'inner',
							"table" => "m_target_areas",
							"alias" => "MTargetArea",
							"conditions" => array (
									"MTargetArea.corp_category_id = MCorpCategory.id",
									"MTargetArea.jis_cd = '" . $data['jis_cd'] . "'"
							)
					),
					// 2015.08.16 s.harada ADD start ORANGE-790
					array (
							'fields' => '*',
							'type' => 'left outer',
							"table" => "affiliation_subs",
							"alias" => "AffiliationSubs",
							"conditions" => array (
									"AffiliationSubs.affiliation_id = AffiliationInfo.id",
									"AffiliationSubs.item_id = "  . $data ['category_id']
							)
					)
					// 2015.08.16 s.harada ADD end ORANGE-790
				);

// 2016.06.30 murata.s ORANGE-22 ADD(S)
			$fields .= ' ,MCorpCategory.category_id';
// 2016.06.30 murata.s ORANGE-22 ADD(E)

                        // 2017/05/30  ichino ORANGE-421 ADD start
                        $fields .= ', MCorpCategory.auction_status';
                        // 2017/05/30  ichino ORANGE-421 ADD end

// 2016.12.01 murata.s ORANGE-250 ADD(S)
			// 入札式配信状況(取次状況)
			$conditions['or'] = array(
					array('MCorp.auction_status' => null),
                                        array(
                                            'MCorp.auction_status != ' => 3,
                                            // 2017/05/30  ichino ORANGE-421 ADD start
                                            'MCorpCategory"."auction_status != ' => 3,
                                            // 2017/05/30  ichino ORANGE-421 ADD end
                                        ),
                                        // 2017/06/12 ichino ORANGE-421 ADD start
                                        array(
                                            'MCorp.auction_status = ' => 1,
                                            'MCorpCategory"."auction_status != ' => 3,
                                        ),
                                        array(
                                            'MCorp.auction_status = ' => 3,
                                            'MCorpCategory"."auction_status = ' => 1,
                                        ),
                                        array(
                                            'MCorp.auction_status = ' => 3,
                                            'MCorpCategory"."auction_status = ' => 2,
                                        ),
                                        // 2017/06/12 ichino ORANGE-421 ADD end
			);
// 2016.12.01 murata.s ORANGE-250 ADD(E)


		} else {

			$joins = array (
					array (
							'fields' => '*',
							'type' => 'inner',
							"table" => "affiliation_infos",
							"alias" => "AffiliationInfo",
							"conditions" => array (
									"AffiliationInfo.corp_id = MCorp.id"
							)
					),
					array (
							'fields' => '*',
							'type' => 'left outer',
							"table" => "affiliation_subs",
							"alias" => "AffiliationSubs",
							"conditions" => array (
									"AffiliationSubs.affiliation_id = AffiliationInfo.id",
									"AffiliationSubs.item_id = "  . $data ['category_id']
							)
					)
				);
		}

		// 2015.04.16 n.kai ADD start 交渉中(1) 取次NG(2) と 取次STOP(4)は表示しない
		// 2015.08.21 n.kai ADD start ORANGE-808 クレーム企業(5)も表示しない
		$conditions['coalesce(MCorp.corp_commission_status, 0) not in'] =
			array(
					1,
					2,
					4,
					5
			);
		// 2015.08.21 n.kai ADD end ORANGE-808
		// 2015.04.16 n.kai ADD end

		// 2015.08.16 s.harada ADD start ORANGE-790
		$conditions['AffiliationSubs.affiliation_id is'] = null;
		$conditions['AffiliationSubs.item_id is'] = null;
		// 2015.08.16 s.harada ADD end ORANGE-790

		$result = $this->MCorp->find ( 'all', array (
				// 2015.06.02 h.hara ORANGE-502(S)
				//'fields' => 'MCorp.id, MCorp.corp_name',
// 2016.06.30 murata.s ORANGE-22 CHG(S)
// 				'fields' => 'MCorp.id, MCorp.corp_name, MCorp.coordination_method',
				'fields' => $fields,
// 2016.06.30 murata.s ORANGE-22 CHG(E)
				// 2015.06.02 h.hara ORANGE-502(E)
				'joins' => $joins,
				'conditions' => $conditions,
				'order' => array('MCorp.id' => 'asc'),
				'limit' => 11,
		) );

		return $result;
	}

	/**
	 * 登録済み紹介情報を取得
	 *
	 * @param unknown $demand_id
	 * @return unknown
	 */
	private function __get_already_commission_list($demand_id){

		$conditions['CommissionInfo.demand_id'] = $demand_id;
		$conditions['CommissionInfo.del_flg'] = 0;

		$result = $this->CommissionInfo->find('all',
				array( 'fields' => 'corp_id',
						'conditions' => $conditions,
						'hasMany'=>array('MCorp'),
				)
		);

		$list = array();
		foreach ($result as  $value){
			$list[] = $value['CommissionInfo']['corp_id'];
		}

		return $list;
	}

}
