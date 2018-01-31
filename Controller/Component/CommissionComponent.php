<?php

/*
 *  選定に関する共通メソッド
 */
class CommissionComponent extends Component {

	public function initialize(Controller $controller) {
		$this->controller = $controller;
	}

	/**
	 * 取次先選定 企業リスト取得
	 *
	 * @param unknown $data
	 * @return unknown
	 */
	public function GetCorpList($data = array() , $check, $flash_error = true){
		$limit = null;
		// エラーチェック
		empty($data['category_id'])? $data['category_id'] = 0:'';

		// 休業日
		$fields_holiday = '(SELECT ARRAY_TO_STRING(ARRAY( SELECT item_name FROM m_items INNER JOIN m_corp_subs ON m_corp_subs.item_category = m_items.item_category AND m_corp_subs.item_id = m_items.item_id WHERE m_corp_subs.item_category = \''.__('holiday' , true).'\' AND m_corp_subs.corp_id = "MCorp"."id" ORDER BY m_items.sort_order ASC ),\'｜\') as "MCorp__holiday" )';

		// ジャンルの目標取次単価
		$fields_commission_unit_price = '(SELECT m_genres.targer_commission_unit_price FROM m_genres WHERE m_genres.id = "MCorpCategory"."genre_id") AS "targer_commission_unit_price"';

		// murata.s ORANGE-261 CHG(S)
		//2016.07.13 ORANGE-9 CHG S
		// 開拓状況
		// 2015.12.03 ORANGE-1039 対応漏れ分
		$fields = 'MCorp.id, MCorp.corp_name, MCorp.commission_dial, MCorp.coordination_method, MCorp.mailaddress_pc, MCorp.fax, MCorp.note, MCorp.support24hour,
				MCorp.available_time_from, MCorp.available_time_to, MCorp.available_time, MCorp.contactable_support24hour, MCorp.contactable_time_from, MCorp.contactable_time_to,
				MCorp.contactable_time, AffiliationInfo.fee, AffiliationInfo.commission_unit_price, AffiliationInfo.attention, MCorpCategory.order_fee, MCorpCategory.order_fee_unit,
				MCorpCategory.note, AffiliationStat.commission_unit_price_category, AffiliationInfo.commission_count, AffiliationInfo.sf_construction_count, MItem.item_name, MCorpCategory.select_list, MCorpCategory.introduce_fee, MCorpCategory.corp_commission_type,'
				.$fields_holiday.", ".$fields_commission_unit_price.",MCorp.address1,MCorp.address2,MCorp.address3,AffiliationInfo.attention, AffiliationStat.commission_count_category, AffiliationStat.orders_count_category";
		//2016.07.13 ORANGE-9 CHG E
		// murata.s ORANGE-261 CHG(E)

		$fields .= ',(SELECT COUNT(0) FROM commission_infos WHERE corp_id = "MCorp".id AND commission_status = 1 ) AS in_progress';
		$fields .= ',(SELECT COUNT(0) FROM commission_infos WHERE corp_id = "MCorp".id AND commission_status = 2 ) AS in_order';
		$fields .= ',(SELECT COUNT(0) FROM commission_infos WHERE corp_id = "MCorp".id AND commission_status = 3 ) AS complete';
		$fields .= ',(SELECT COUNT(0) FROM commission_infos WHERE corp_id = "MCorp".id AND commission_status = 4 ) AS failed';

		// 年末年始対応状況
		$fields .= ','.implode(',',array_map(function($value){
			return 'MCorpNewYear.'.$value;
		},array_keys($this->controller->MCorpNewYear->schema())));

		$order = array (
				'AffiliationInfo.commission_unit_price IS NULL',
				'AffiliationInfo.commission_unit_price DESC' ,
				'AffiliationInfo.commission_count DESC'
		);

		// 対応可能エリア・カテゴリ参照フラグ
		$target_check_flg = false;

		// joinsタイプ初期化
		$joins_type = 'left';

		// 画面のチェックボックス(カテゴリ・対応可能エリア条件解除)
		if(empty($data['target_check'])){
			// カテゴリID・対応可能エリアコード　チェック
			if(!empty($data['category_id']) && !empty($data['jis_cd'])){
				$target_check_flg = true;
				$joins_type = 'inner';
			} else {
				// エラー 表示
				if($flash_error)
				{
					$this->controller->Session->setFlash(__('ErrorCommissionSelect', true), 'default', array('class' => 'error-message'));
				}
				return array();
			}
		}

		// 結合条件作成
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
						'type' => 'inner',
						"table" => "m_items",
						"alias" => "MItem",
						"conditions" => array (
								"MItem.item_category = '".__('coordination_method',true)."'",
								"MItem.item_id = MCorp.coordination_method"
						),
				),

				array (
						'fields' => '*',
						'type' => $joins_type,
						"table" => "m_corp_categories",
						"alias" => "MCorpCategory",
						"conditions" => array (
								"MCorpCategory.corp_id = MCorp.id",
								'MCorpCategory.category_id =' . $data ['category_id']
						),
				),

				array (
						'fields' => '*',
						'type' => 'left',
						"table" => "affiliation_stats",
						"alias" => "AffiliationStat",
						"conditions" => array (
								"AffiliationStat.corp_id = MCorp.id",
								"AffiliationStat.genre_id = MCorpCategory.genre_id"
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
				),
				// 2015.08.16 s.harada ADD end ORANGE-790
				array (
						'fields' => '*',
						'type' => 'left',
						'table' => 'm_corp_new_years',
						'alias' => 'MCorpNewYear',
						'conditions' => array (
								'MCorp.id = MCorpNewYear.corp_id'
						)
				),
		);

		if($target_check_flg){

			$fields = $fields . ', AffiliationAreaStat.commission_unit_price_category , AffiliationAreaStat.commission_count_category';

// 2016.06.30 murata.s ORANGE-22 ADD(S)
			$fields = $fields.', MCorpCategory.category_id';
// 2016.06.30 murata.s ORANGE-22 ADD(E)
//
			// 2017/05/30  ichino ORANGE-421 ADD start
			$fields = $fields.', MCorpCategory.auction_status';
			// 2017/05/30  ichino ORANGE-421 ADD end

			$target_areas_joins = array (
					'fields' => '*',
					'type' => 'inner',
					// 2015.05.30 h.hara ORANGE-531(S) 2015.06.29 h.hara ORANGE-577(S)戻し
 					"table" => "m_target_areas",
 					"alias" => "MTargetArea",
 					"conditions" => array (
 							"MTargetArea.corp_category_id = MCorpCategory.id",
 							"MTargetArea.jis_cd = '" . $data ['jis_cd'] . "'"
 					)
//					"table" => "(SELECT corp_category_id, SUBSTRING(jis_cd, 1, 2) FROM m_target_areas WHERE SUBSTRING(jis_cd, 1, 2) = '" . substr($data ['jis_cd'], 0, 2) . "' GROUP BY corp_category_id, SUBSTRING(jis_cd, 1, 2))",
//					"alias" => "MTargetArea",
//					"conditions" => array (
//							"MTargetArea.corp_category_id = MCorpCategory.id")
					// 2015.05.30 h.hara ORANGE-531(E) 2015.06.29 h.hara ORANGE-577(E)戻し

			);
			array_push ( $joins, $target_areas_joins );

			$prefecture = (int)substr($data ['jis_cd'], 0, 2);

 			$stat_joins = array (
					'fields' => '*',
					'type' => 'inner',
					"table" => "affiliation_area_stats",
					"alias" => "AffiliationAreaStat",
					"conditions" => array (
							'AffiliationAreaStat.corp_id = MCorp.id',
							'AffiliationAreaStat.genre_id = MCorpCategory.genre_id',
							"AffiliationAreaStat.prefecture = '" . $prefecture . "'"
					)
			);
			array_push ( $joins, $stat_joins );

			// ソート
			$order = array (
					'AffiliationAreaStat.commission_unit_price_category IS NULL',
					'AffiliationAreaStat.commission_unit_price_category DESC',
					'AffiliationAreaStat.commission_count_category DESC'
			);

		}

		/******************** 条件の作成 ******************/
		// 加盟状態 (加盟)
		$conditions['MCorp.affiliation_status'] = 1;

		// 企業名
		if(!empty($data['corp_name'])){
			$conditions['z2h_kana(MCorp.corp_name) like'] = '%'. Util::chgSearchValue($data['corp_name']) .'%';
		}

		if($target_check_flg){
			// 取次件数
			// 2015.07.19 s.harada ORANGE-629 全件出力するとメモリオーバーするので上限を増やす
			if(empty($check)){
				$limit = 1500;
				$conditions['AffiliationAreaStat.commission_count_category >='] = 5;
			} else {
				$limit = 2000 - $this->controller->count;
				$conditions['AffiliationAreaStat.commission_count_category <'] = 5;
			}
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
			// 取次件数
			if(empty($check)){
				$limit = 1500;
				$conditions['AffiliationInfo.commission_count >='] = 5;
			} else {
				$limit = 2000 - $this->controller->count;
				$conditions['AffiliationInfo.commission_count <'] = 5;
			}
		}

		// 2015.04.16 n.kai ADD start 交渉中(1)、取次NG(2) と 取次STOP(4)は表示しない
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

		// 2015.07.22 s.harada ADD start ORANGE-584
		if (isset($data['exclude_corp_id'])) {
			$exclude_corp_array = explode("," , $data['exclude_corp_id']);
			$exclude_corp_id = array();
			for ($i=0; $i<count($exclude_corp_array); $i++) {
				if ($exclude_corp_array[$i] != null && $i != $data['no']) {
					$exclude_corp_id[] = $exclude_corp_array[$i];
				}
			}
			if (count($exclude_corp_id) >= 2) {
				$conditions['MCorp.id not in'] = $exclude_corp_id;
			} else if (count($exclude_corp_id) == 1) {
				$conditions['MCorp.id <>'] = $exclude_corp_id[0];
			}
		}
		// 2015.07.22 s.harada ADD end ORANGE-584

		// 2016.05.30 murata.s ORANGE-75 ADD(S)
		// 契約更新フラグ
		$conditions['MCorp.commission_accept_flg not in'] = array(0, 3);
		// 2016.05.30 murata.s ORANGE-75 ADD(E)


		/******************** 条件の作成 end ******************/

		$results = $this->controller->MCorp->find('all',
				array( 'fields' => $fields,
						'joins' =>  $joins,
						'conditions' => $conditions,
						'limit' => $limit,
						'order' => $order,
		)
		);

		return $results;

	}

}