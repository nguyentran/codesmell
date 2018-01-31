<?php

class AutoCommissionSelectComponent extends Component{

	public $controller;

	public $count = 0;

	public function startup($controller){
		App::import('Model', 'MCorp');
		App::import('Model', 'MCategory');
		App::uses('CreditHelper', 'View/Helper');

		$this->controller = $controller;
		$this->MCorp = new MCorp();
		$this->MCategory = new MCategory();
		$this->Credit = new CreditHelper(new View());

	}

	/**
	 * 自動選定可能な取次先一覧を取得する
	 *
	 * @param array $genre_id ジャンルID
	 * @param int $category_id カテゴリID
	 * @param string $jis_cd 市町村コード
	 */
	public function get_auto_commission_info($genre_id, $category_id, $jis_cd){

		// リストを取得(5件以上)
		$corp_list = self::__get_corp_list($category_id, $jis_cd, null, false);
		$this->count = count($corp_list);
		// リストを取得(初取次)
		$corp_list_new = self::__get_corp_list($category_id, $jis_cd, null, true);

		$results = array_merge($corp_list, $corp_list_new);

		return $results;

	}

	/**
	 * 取次先企業一覧を取得する
	 *
	 * @param int $category_id カテゴリID
	 * @param string $jis_cd 市町村コード
	 * @param string $exclude_corp_id 除外企業IDリスト
	 * @param string $check 初取次フラグ
	 */
	public function __get_corp_list($category_id, $jis_cd = null, $exclude_corp_id = null, $check = false){

		$limit = null;
		// エラーチェック
		empty($category_id) ? $category_id = 0 : '';

		// 都道府県コード
		$prefecture = (int)substr($jis_cd, 0, 2);

		// ジャンル別の目標取次単価
		$fields_commission_unit_price = '(SELECT m_genres.targer_commission_unit_price
				FROM m_genres
				WHERE m_genres.id = "MCorpCategory"."genre_id") AS "targer_commission_unit_price"';

		$fields = 'MCorp.id, MCorp.corp_name, MCorp.commission_dial, MCorp.coordination_method, MCorp.mailaddress_pc,'
				.' MCorp.fax, MCorp.note, MCorp.support24hour, MCorp.available_time_from, MCorp.available_time_to, MCorp.available_time,'
				.' MCorp.contactable_support24hour, MCorp.contactable_time_from, MCorp.contactable_time_to, MCorp.contactable_time, MCorp.address1,'
				.' MCorp.address2, MCorp.address3,'
				.' AffiliationInfo.fee, AffiliationInfo.commission_unit_price, AffiliationInfo.attention, AffiliationInfo.commission_count, AffiliationInfo.sf_construction_count,'
				.' AffiliationInfo.attention,'
				.' MCorpCategory.category_id, MCorpCategory.order_fee, MCorpCategory.order_fee_unit, MCorpCategory.note, MCorpCategory.select_list,'
				.' AffiliationStat.commission_unit_price_category, AffiliationStat.commission_count_category, AffiliationStat.orders_count_category,'
				.' MItem.item_name,'
				.' AffiliationAreaStat.commission_unit_price_category, AffiliationAreaStat.commission_count_category, '
				.$fields_commission_unit_price;

		// 結合条件作成
		$joins = array(
				array(
						'fields' => '*',
						'type' => 'inner',
						'table' => 'affiliation_infos',
						'alias' => 'AffiliationInfo',
						'conditions' => array(
								'AffiliationInfo.corp_id = MCorp.id'
						)
				),
				array(
						'fields' => '*',
						'type' => 'inner',
						'table' => 'm_items',
						'alias' => 'MItem',
						'conditions' => array(
								'MItem.item_category' => __('coordination_method',true),
								'MItem.item_id = MCorp.coordination_method'
						)
				),
				array(
						'fields' => '*',
						'type' => 'inner',
						'table' => 'm_corp_categories',
						'alias' => 'MCorpCategory',
						'conditions' => array(
								'MCorpCategory.corp_id = MCorp.id',
								'MCorpCategory.category_id' => $category_id
						)
				),
				array(
						'fields' => '*',
						'type' => 'left',
						'table' => 'affiliation_stats',
						'alias' => 'AffiliationStat',
						'conditions' => array(
								'AffiliationStat.corp_id = MCorp.id',
								'AffiliationStat.genre_id = MCorpCategory.genre_id'
						)
				),
				array(
						'fields' => '*',
						'type' => 'left outer',
						'table' => 'affiliation_subs',
						'alias' => 'AffiliationSubs',
						'conditions' => array(
								'AffiliationSubs.affiliation_id = AffiliationInfo.id',
								'AffiliationSubs.item_id' => $category_id
						)
				),
				array(
						'fields' => '*',
						'type' => 'inner',
						'table' => 'm_target_areas',
						'alias' => 'MTargetArea',
						'conditions' => array(
								'MTargetArea.corp_category_id = MCorpCategory.id',
								'MTargetArea.jis_cd' => (string)$jis_cd
						)
				),
				array(
						'fields' => '*',
						'type' => 'inner',
						'table' => 'affiliation_area_stats',
						'alias' => 'AffiliationAreaStat',
						'conditions' => array(
								'AffiliationAreaStat.corp_id = MCorp.id',
								'AffiliationAreaStat.genre_id = MCorpCategory.genre_id',
								'AffiliationAreaStat.prefecture' => (string)$prefecture
						)
				)
		);

		// ソート
		$order = array (
				'AffiliationAreaStat.commission_unit_price_category IS NULL',
				'AffiliationAreaStat.commission_unit_price_category DESC',
				'AffiliationAreaStat.commission_count_category DESC'
		);

		// 条件の作成
		// 加盟状態
		$conditions['MCorp.affiliation_status'] = 1; // 加盟
		// 取次件数
		if(empty($check)){
			$limit = 1500;
			$conditions['AffiliationAreaStat.commission_count_category >='] = 5;
		}else{
			$limit = 2000 - $this->count;
			$conditions['AffiliationAreaStat.commission_count_category <'] = 5;
		}
		// 加盟店取次状況
		$conditions['coalesce(MCorp.corp_commission_status, 0) not in'] = array(1, 2, 4, 5);
		// 加盟店付帯情報
		$conditions['AffiliationSubs.affiliation_id is'] = null;
		$conditions['AffiliationSubs.item_id is'] = null;

		// 企業IDの除外
		if(!empty($exclude_corp_id)){
			$exclude_corp_array = explode("," , $data['exclude_corp_id']);
			$corps = array_filter($exclude_corp_array, function($v){
				return $v != null;
			});
			if(!empty($corps))
				$conditions['MCorp.id not in'] = $corps;
		}

		// 契約更新フラグ
		$conditions['MCorp.commission_accept_flg not in'] = array(0, 3);

		$m_corps = $this->MCorp->find('all', array(
				'fields' => $fields,
				'joins' => $joins,
				'conditions' => $conditions,
				'limit' => $limit,
				'order' => $order
		));

		// 目標取次単価の設定
		foreach($m_corps as $key=>$val){

			if($check){
				$m_corps[$key][0]['commission_unit_price_rank_1'] = 'z';
			}else{
				// 通常
				if(empty($val[0]['targer_commission_unit_price'])){
					$m_corps[$key][0]['commission_unit_price_rank_1'] = 'a';
				}else if(empty($val['AffiliationAreaStat']['commission_unit_price_category'])){
					$m_corps[$key]['commission_unit_price_rank_1'] = 'd';
				}else{
					$rank_f = $val['AffiliationAreaStat']['commission_unit_price_category'] / $val[0]['targer_commission_unit_price'] * 100;
					if($rank_f >= 100){
						$m_corps[$key][0]['commission_unit_price_rank_1'] = 'a';
					}else if($rank_f >= 80){
						$m_corps[$key][0]['commission_unit_price_rank_1'] = 'b';
					}else if($rank_f >= 65){
						$m_corps[$key][0]['commission_unit_price_rank_1'] = 'c';
					}else{
						$m_corps[$key][0]['commission_unit_price_rank_1'] = 'd';
					}
				}
			}
		}

		return $m_corps;
	}
}
