<?php
App::uses('AppShell', 'Console/Command');

class CalculateCommissionShell extends AppShell {

	public $uses = array('CommissionInfo', 'AffiliationStat', 'MCorpCategory', 'AffiliationInfo', 'AffiliationAreaStat');
	private static $user = 'system';

	public function main() {


		try {

			$this->log('取次平均単価計算シェルstart', SHELL_LOG);

// 			$this->log('加盟店カテゴリ別統計情報テーブルに未登録のカテゴリデータを作成', SHELL_LOG);
// 			$this->setCreateAffiliationStatData();
// 			$this->log('加盟店カテゴリ別統計情報テーブルに未登録のカテゴリデータを作成終了', SHELL_LOG);

// 			$this->log('ジャンル別取次数更新', SHELL_LOG);
// 			$this->setCommissionGroupCateogyCount();
// 			$this->log('ジャンル別取次数更新終了', SHELL_LOG);

//			$this->log('ジャンル別受注数更新', SHELL_LOG);
//			$this->setCommissionGroupCateogyOrderCount();
//			$this->log('ジャンル別受注数更新終了', SHELL_LOG);

//			$this->log('平均取次単価更新', SHELL_LOG);
//			$this->setCommissionInfo();
//			$this->log('平均取次単価更新終了', SHELL_LOG);

//			$this->log('加盟店情報の取次件数更新', SHELL_LOG);
//			$this->setCommissionCountOfAffiliation();
//			$this->log('加盟店情報の取次件数更新終了', SHELL_LOG);

//			$this->log('加盟店情報の取次件数(1週間)更新', SHELL_LOG);
//			$this->setCommissionWeekCountOfAffiliation();
//			$this->log('加盟店情報の取次件数(1週間)更新終了', SHELL_LOG);

//			$this->log('加盟店情報の施工単価と受注数更新', SHELL_LOG);
//			$this->setReceiptCount();
//			$this->log('加盟店情報の施工単価と受注数更新終了', SHELL_LOG);

//			$this->log('加盟店情報の取次単価更新', SHELL_LOG);
//			$this->setCommissionPrice();
//			$this->log('加盟店情報の取次単価更新', SHELL_LOG);

//			$this->log('加盟店情報の受注率更新', SHELL_LOG);
//			$this->setReceiptRate();
//			$this->log('加盟店情報の受注率更新', SHELL_LOG);

// 			$this->log('加盟店カテゴリ都道府県別統計情報テーブルに未登録のカテゴリデータを作成', SHELL_LOG);
// 			$this->setCreateAffiliationAreaStatData();
// 			$this->log('加盟店カテゴリ都道府県別統計情報テーブルに未登録のカテゴリデータを作成終了', SHELL_LOG);

			$this->log('都道府県ジャンル別取次数更新', SHELL_LOG);
			$this->setAreaCommissionGroupCateogyCount();
			$this->log('都道府県ジャンル別取次数更新終了', SHELL_LOG);

//			$this->log('都道府県別平均取次単価更新', SHELL_LOG);
//			$this->setAreaCommissionInfo();
//			$this->log('都道府県平均取次単価更新終了', SHELL_LOG);

			$this->log('取次平均単価計算シェルend', SHELL_LOG);

		} catch (Exception $e) {
			$this->log($e, SHELL_LOG);
		}

	}

	/**
	 * 取次単価を更新
	 */
	private function setAreaCommissionInfo() {

		// 2015.04.09 k.yamada
		/*
		$conditions = array();

		$joins = array(
				array(
						'table' => 'demand_infos',
						'alias' => "DemandInfo",
						'type' => 'inner',
						'conditions' => array('CommissionInfo.demand_id = DemandInfo.id')
				),
				array(
						'table' => 'affiliation_area_stats',
						'alias' => "AffiliationAreaStat",
						'type' => 'left',
						'conditions' => array('CommissionInfo.corp_id = AffiliationAreaStat.corp_id', 'DemandInfo.genre_id = AffiliationAreaStat.genre_id', 'DemandInfo.address1 = AffiliationAreaStat.prefecture')
				),
		);

		// 取次前失注でない
		$conditions['CommissionInfo.lost_flg'] = 0;

		// 取次単価対象外でない
		$conditions['CommissionInfo.unit_price_calc_exclude'] = 0;


		$fields = array(
				'CommissionInfo.corp_id',
				'DemandInfo.genre_id',
				'DemandInfo.address1',
				'count(*) as "CommissionInfo__orders_count_category"',
				'avg(coalesce("CommissionInfo"."corp_fee", 0)) as "CommissionInfo__corp_fee"',
				'min("AffiliationAreaStat"."id") as "CommissionInfo__affiliation_area_stats_id"',
		);

		$group = array(
				'CommissionInfo.corp_id',
				'DemandInfo.genre_id',
				'DemandInfo.address1'
		);

		$params = array(
				'fields' => $fields,
				'conditions' => $conditions,
				'joins' => $joins,
				'group' => $group,
		);


		// 集計用のバーチャルフィールドを作成
		$this->CommissionInfo->virtualFields['orders_count_category'] = 0;
		$this->CommissionInfo->virtualFields['corp_fee'] = 0;
		$this->CommissionInfo->virtualFields['affiliation_area_stats_id'] = 0;

		// 検索
		$list = $this->CommissionInfo->find('all', $params);
		*/

		// 2015.04.09 k.yamada
		$sql = 'truncate table shell_work_ci';
		$this->CommissionInfo->query($sql);

		$sql = 'truncate table shell_work_result';
		$this->CommissionInfo->query($sql);

		$query = $this->sql('insert_shell_work_ci.sql');
		$this->CommissionInfo->query($query);

		$query = $this->sql('insert_shell_work_result_area.sql');
		$this->CommissionInfo->query($query);

// murata.s ORANGE-363 CHG(S)
		$sql = 'select ';
		$sql .= '  t1.corp_id as "CommissionInfo__corp_id" ';
		$sql .= '  , t1.genre_id as "DemandInfo__genre_id" ';
		$sql .= '  , t1.address1 as "DemandInfo__address1" ';
		$sql .= '  , t1.commission_unit_price as "CommissionInfo__corp_fee" ';
		$sql .= '  , t1.commission_unit_price_rank as "CommissionInfo__unit_price_rank" ';
		$sql .= '  , t2.id as "CommissionInfo__affiliation_area_stats_id"  ';
		$sql .= 'from ';
		$sql .= '  shell_work_result t1  ';
		$sql .= '  left join affiliation_area_stats t2  ';
		$sql .= '    on t2.corp_id = t1.corp_id  ';
		$sql .= '    and t2.genre_id = t1.genre_id  ';
		$sql .= '    and t2.prefecture = t1.address1  ';
// 		$sql .= 'order by ';
// 		$sql .= '  t1.corp_id ';
// 		$sql .= '  , t1.genre_id ';
// 		$sql .= '  , t1.address1 ';
		// カテゴリ別都道府県統計情報の取次単価が登録済みで、直近1年間の取次データがない統計データを結合
		$sql .= 'union all ';
		$sql .= 'select ';
		$sql .= '  AffiliationAreaStat.corp_id as "CommissionInfo__corp_id" ';
		$sql .= ' , AffiliationAreaStat.genre_id as "DemandInfo__genre_id" ';
		$sql .= ' , AffiliationAreaStat.prefecture as "DemandInfo__address1" ';
		$sql .= ' , 0 as "CommissionInfo__corp_fee" ';
		$sql .= ' , case ';
		$sql .= '     when (select max(commission_count_category) from affiliation_area_stats where corp_id = AffiliationAreaStat.corp_id and genre_id = AffiliationAreaStat.genre_id and prefecture = AffiliationAreaStat.prefecture)  <= 5 ';
		$sql .= '       then \'z\' ';
		$sql .= '     when (max(mg.targer_commission_unit_price) IS NULL) OR (max(mg.targer_commission_unit_price) = 0) ';
		$sql .= '       then \'a\' ';
		$sql .= '     else \'d\' ';
		$sql .= '   end as "CommissionInfo__unit_price_rank" ';
		$sql .= ' , AffiliationAreaStat.id as "CommissionInfo__affiliation_area_stats_id" ';
		$sql .= 'from  ';
		$sql .= '  affiliation_area_stats AffiliationAreaStat ';
		$sql .= 'left join shell_work_result t1 ';
		$sql .= '  on t1.corp_id = AffiliationAreaStat.corp_id ';
		$sql .= '  and t1.genre_id = AffiliationAreaStat.genre_id ';
		$sql .= '  and t1.address1 = AffiliationAreaStat.prefecture ';
		$sql .= 'left join m_genres mg ';
		$sql .= '  on mg.id = AffiliationAreaStat.genre_id ';
		$sql .= '  and mg.valid_flg = 1 ';
		$sql .= 'where ';
		$sql .= '  AffiliationAreaStat.commission_unit_price_category > 0 ';
		$sql .= '  and t1.corp_id is null ';
		$sql .= 'group by AffiliationAreaStat.corp_id, ';
		$sql .= '  AffiliationAreaStat.genre_id, ';
		$sql .= '  AffiliationAreaStat.prefecture, ';
		$sql .= '  AffiliationAreaStat.id ';
		$sql .= 'order by ';
		$sql .= '  "CommissionInfo__corp_id" ';
		$sql .= '  , "DemandInfo__genre_id" ';
		$sql .= '  , "DemandInfo__address1" ';
// murata.s ORANGE-363 CHG(E)
		$list = $this->CommissionInfo->query($sql);

		$datas = $this->area_calculation($list);

		// 取次平均単価を更新
		$this->AffiliationAreaStat->saveAll($datas['AffiliationAreaStat']);

	}

	/**
	 * 平均単価を計算後、加盟店カテゴリ別統計情報テーブルの配列を返却
	 * @param unknown $datas
	 * @return Ambigous <multitype:, number>
	 */
	private function area_calculation($datas) {

		$list['AffiliationAreaStat'] = array();
		$i = 0;

		foreach($datas as $r) {

			// 登録・更新判定
			if (($r['CommissionInfo']['affiliation_area_stats_id']) != null) {
				$list['AffiliationAreaStat'][$i]['id'] = $r['CommissionInfo']['affiliation_area_stats_id'];
				$list['AffiliationAreaStat'][$i]['modified_user_id'] = self::$user;
				$list['AffiliationAreaStat'][$i]['modified'] =  date("Y/m/d H:i:s", time());

			} else {

				$list['AffiliationAreaStat'][$i]['id'] = null;
				$list['AffiliationAreaStat'][$i]['created_user_id'] = self::$user;
				$list['AffiliationAreaStat'][$i]['created'] =  date("Y/m/d H:i:s", time());
			}

			$list['AffiliationAreaStat'][$i]['corp_id'] = $r['CommissionInfo']['corp_id'];
			$list['AffiliationAreaStat'][$i]['genre_id'] = (int)$r['DemandInfo']['genre_id'];
			$list['AffiliationAreaStat'][$i]['prefecture'] = (int)$r['DemandInfo']['address1'];
			$list['AffiliationAreaStat'][$i]['commission_unit_price_category'] = (int)$r['CommissionInfo']['corp_fee'];
			$list['AffiliationAreaStat'][$i]['commission_unit_price_rank'] = $r['CommissionInfo']['unit_price_rank'];

			$i++;
		}

		return $list;
	}

	/**
	 * 取次単価を更新
	 */
	private function setCommissionInfo() {

		// 2015.04.09 k.yamada
		/*
		$conditions = array();

		$joins = array(
				array(
						'table' => 'demand_infos',
						'alias' => "DemandInfo",
						'type' => 'inner',
						'conditions' => array('CommissionInfo.demand_id = DemandInfo.id')
				),
				array(
						'table' => 'affiliation_stats',
						'alias' => "AffiliationStat",
						'type' => 'left',
						'conditions' => array('CommissionInfo.corp_id = AffiliationStat.corp_id', 'DemandInfo.genre_id = AffiliationStat.genre_id')
				),
		);

		// 取次種別は通常取次
		//$conditions['CommissionInfo.commission_type'] = 0;

		// 取次前失注でない
		$conditions['CommissionInfo.lost_flg'] = 0;

		// 取次単価対象外でない
		$conditions['CommissionInfo.unit_price_calc_exclude'] = 0;


		$fields = array(
				'CommissionInfo.corp_id',
				'DemandInfo.genre_id',
				'count(*) as "CommissionInfo__orders_count_category"',
				'avg(coalesce("CommissionInfo"."corp_fee", 0)) as "CommissionInfo__corp_fee"',
				'min(coalesce("AffiliationStat"."sf_commission_count_category", 0)) as "CommissionInfo__sf_commission_count_category"',
				'min(coalesce("AffiliationStat"."sf_commission_unit_price_category", 0)) as "CommissionInfo__sf_commission_unit_price_category"',
				'min("AffiliationStat"."id") as "CommissionInfo__affiliation_stat_id"',
		);

		$group = array(
				'CommissionInfo.corp_id',
				'DemandInfo.genre_id'
		);

		$params = array(
				'fields' => $fields,
				'conditions' => $conditions,
				'joins' => $joins,
				'group' => $group,
		);


		// 集計用のバーチャルフィールドを作成
		$this->CommissionInfo->virtualFields['orders_count_category'] = 0;
		$this->CommissionInfo->virtualFields['corp_fee'] = 0;
		$this->CommissionInfo->virtualFields['sf_commission_count_category'] = 0;
		$this->CommissionInfo->virtualFields['sf_commission_unit_price_category'] = 0;
		$this->CommissionInfo->virtualFields['affiliation_stat_id'] = 0;

		// 検索
		$list = $this->CommissionInfo->find('all', $params);
		*/

		// 2015.04.09 k.yamada
		$sql = 'truncate table shell_work_ci';
		$this->CommissionInfo->query($sql);

		$sql = 'truncate table shell_work_result';
		$this->CommissionInfo->query($sql);

		$query = $this->sql('insert_shell_work_ci.sql');
		$this->CommissionInfo->query($query);

		$query = $this->sql('insert_shell_work_result.sql');
		$this->CommissionInfo->query($query);

// murata.s ORANGE-363 CHG(S)
		$sql = 'select ';
		$sql .= '  t1.corp_id as "CommissionInfo__corp_id" ';
		$sql .= '  , t1.genre_id as "DemandInfo__genre_id" ';
		$sql .= '  , t1.commission_unit_price as "CommissionInfo__corp_fee" ';
		$sql .= '  , 0 as "CommissionInfo__sf_commission_unit_price_category"  ';
		$sql .= '  , t2.id as "CommissionInfo__affiliation_stat_id"  ';
		$sql .= 'from ';
		$sql .= '  shell_work_result t1  ';
		$sql .= '  left join affiliation_stats t2  ';
		$sql .= '    on t2.corp_id = t1.corp_id  ';
		$sql .= '    and t2.genre_id = t1.genre_id  ';
// 		$sql .= 'order by ';
// 		$sql .= '  t1.corp_id ';
// 		$sql .= '  , t1.genre_id ';
		// 統計情報の取次単価が登録済みで、直近1年間の取次データがない加盟店データを結合
		$sql .= 'union all ';
		$sql .= 'select ';
		$sql .= '  AffiliationStat.corp_id as "CommissionInfo__corp_id" ';
		$sql .= ' ,AffiliationStat.genre_id as "DemandInfo__genre_id" ';
		$sql .= ' ,0 as "CommissionInfo__corp_fee" ';
		$sql .= ' ,0 as "CommissionInfo__sf_commission_unit_price_category" ';
		$sql .= ' ,AffiliationStat.id as "CommissionInfo__affiliation_stat_id" ';
		$sql .= 'from ';
		$sql .= ' affiliation_stats AffiliationStat ';
		$sql .= ' left join shell_work_result t1 ';
		$sql .= '   on t1.corp_id = AffiliationStat.corp_id ';
		$sql .= '   and t1.genre_id = AffiliationStat.genre_id ';
		$sql .= 'where ';
		$sql .= '  AffiliationStat.commission_unit_price_category > 0 ';
		$sql .= '  and t1.corp_id is null ';
		$sql .= 'order by ';
		$sql .= '  "CommissionInfo__corp_id" ';
		$sql .= '  , "DemandInfo__genre_id" ';
// murata.s ORANGE-363 CHG(E)

		$list = $this->CommissionInfo->query($sql);

		$datas = $this->calculation($list);

		// 取次平均単価を更新
		$this->AffiliationStat->saveAll($datas['AffiliationStat']);

	}


	/**
	 * 平均単価を計算後、加盟店カテゴリ別統計情報テーブルの配列を返却
	 * @param unknown $datas
	 * @return Ambigous <multitype:, number>
	 */
	private function calculation($datas) {

		$list['AffiliationStat'] = array();
		$i = 0;

		foreach($datas as $r) {

			// 登録・更新判定
			if (($r['CommissionInfo']['affiliation_stat_id']) != null) {
				$list['AffiliationStat'][$i]['id'] = $r['CommissionInfo']['affiliation_stat_id'];
				$list['AffiliationStat'][$i]['modified_user_id'] = self::$user;
				$list['AffiliationStat'][$i]['modified'] =  date("Y/m/d H:i:s", time());

			} else {

				$list['AffiliationStat'][$i]['id'] = null;
				$list['AffiliationStat'][$i]['created_user_id'] = self::$user;
				$list['AffiliationStat'][$i]['created'] =  date("Y/m/d H:i:s", time());
			}

			$list['AffiliationStat'][$i]['corp_id'] = $r['CommissionInfo']['corp_id'];
			$list['AffiliationStat'][$i]['genre_id'] = (int)$r['DemandInfo']['genre_id'];
			//$list['AffiliationStat'][$i]['orders_count_category'] = (int)$r['CommissionInfo']['orders_count_category'];

			// SF取次単価が入っている場合は、プラスして/2
			if ($r['CommissionInfo']['sf_commission_unit_price_category'] != 0) {
				$list['AffiliationStat'][$i]['commission_unit_price_category'] = (int)($r['CommissionInfo']['corp_fee'] + (int)$r['CommissionInfo']['sf_commission_unit_price_category']) / 2;
			} else {
				$list['AffiliationStat'][$i]['commission_unit_price_category'] = (int)$r['CommissionInfo']['corp_fee'];
			}

			$i++;
		}

		return $list;
	}

	/**
	 * ジャンル別取次数を更新
	 */
	private function setCommissionGroupCateogyCount() {

		$conditions = array();

		$joins = array(
				array(
						'table' => 'demand_infos',
						'alias' => "DemandInfo",
						'type' => 'inner',
						'conditions' => array('CommissionInfo.demand_id = DemandInfo.id')
				),
				array(
						'table' => 'affiliation_stats',
						'alias' => "AffiliationStat",
						'type' => 'left',
						'conditions' => array('CommissionInfo.corp_id = AffiliationStat.corp_id', 'DemandInfo.genre_id = AffiliationStat.genre_id')
				),
		);

		// 取次種別は通常取次
		//$conditions['CommissionInfo.commission_type'] = 0;

		// 取次前失注でない
		$conditions['CommissionInfo.lost_flg'] = 0;

		// 取次単価対象外でない
		$conditions['CommissionInfo.unit_price_calc_exclude'] = 0;

		$fields = array(
				'CommissionInfo.corp_id',
				'DemandInfo.genre_id',
				'count(*) as "CommissionInfo__commission_count_category"',
				'min("AffiliationStat"."id") as "CommissionInfo__affiliation_stat_id"',
		);

		$group = array(
				'CommissionInfo.corp_id',
				'DemandInfo.genre_id'
		);

		$params = array(
				'fields' => $fields,
				'conditions' => $conditions,
				'joins' => $joins,
				'group' => $group,
		);


		$this->CommissionInfo->virtualFields['commission_count_category'] = 0;
		$this->CommissionInfo->virtualFields['affiliation_stat_id'] = 0;


		$list = $this->CommissionInfo->find('all', $params);

		$datas = $this->getCommissionGroupCategoryCount($list);

		$this->AffiliationStat->saveAll($datas['AffiliationStat']);

// murata.s ORANGE-390 ADD(S)
		// ジャンル別取次数抽出対象外の統計情報を取得して更新
		$reset_list = $this->getCommissionGroupCategoryCountInitialize();
		$reset_datas = $this->getCommissionGroupCategoryCount($reset_list);
		$this->AffiliationStat->saveAll($reset_datas['AffiliationStat']);
// murata.s ORANGE-390 ADD(E)

	}

	/**
	 * 都道府県ジャンル別取次数を更新
	 */
	private function setAreaCommissionGroupCateogyCount() {

		$conditions = array();

		$joins = array(
				array(
						'table' => 'demand_infos',
						'alias' => "DemandInfo",
						'type' => 'inner',
						'conditions' => array('CommissionInfo.demand_id = DemandInfo.id')
				),
				array(
						'table' => 'affiliation_area_stats',
						'alias' => "AffiliationAreaStat",
						'type' => 'left',
						'conditions' => array('CommissionInfo.corp_id = AffiliationAreaStat.corp_id', 'DemandInfo.genre_id = AffiliationAreaStat.genre_id', 'DemandInfo.address1 = AffiliationAreaStat.prefecture')
				),
		);

		// 取次前失注でない
		$conditions['CommissionInfo.lost_flg'] = 0;

		// 取次単価対象外でない
		$conditions['CommissionInfo.unit_price_calc_exclude'] = 0;

		$fields = array(
				'CommissionInfo.corp_id',
				'DemandInfo.genre_id',
				'DemandInfo.address1',
				'count(*) as "CommissionInfo__commission_count_category"',
				'min("AffiliationAreaStat"."id") as "CommissionInfo__affiliation_area_stat_id"',
		);

		$group = array(
				'CommissionInfo.corp_id',
				'DemandInfo.genre_id',
				'DemandInfo.address1'
		);

		$params = array(
				'fields' => $fields,
				'conditions' => $conditions,
				'joins' => $joins,
				'group' => $group,
		);


		$this->CommissionInfo->virtualFields['commission_count_category'] = 0;
		$this->CommissionInfo->virtualFields['affiliation_area_stat_id'] = 0;


		$list = $this->CommissionInfo->find('all', $params);

		$datas = $this->getAreaCommissionGroupCategoryCount($list);

		$this->AffiliationAreaStat->saveAll($datas['AffiliationAreaStat']);

// murata.s ORANGE-390 ADD(S)
		// 都道府県ジャンル別取次数の抽出数対象外の統計情報を取得して更新
		$reset_list = $this->getAreaCommissionGroupCateogyCountInitialize();
		$reset_datas = $this->getAreaCommissionGroupCategoryCount($reset_list);
		$this->AffiliationAreaStat->saveAll($reset_datas['AffiliationAreaStat']);
// murata.s ORANGE-390 ADD(E)

	}

	/**
	 * 都道府県ジャンル別取次数の取得
	 * @param unknown $datas
	 * @return Ambigous <multitype:, number>
	 */
	private function getAreaCommissionGroupCategoryCount($datas) {

		$list['AffiliationAreaStat'] = array();
		$i = 0;

		foreach($datas as $r) {

			// 登録・更新判定
			if (($r['CommissionInfo']['affiliation_area_stat_id']) != null) {
				$list['AffiliationAreaStat'][$i]['id'] = $r['CommissionInfo']['affiliation_area_stat_id'];
				$list['AffiliationAreaStat'][$i]['modified_user_id'] = self::$user;
				$list['AffiliationAreaStat'][$i]['modified'] =  date("Y/m/d H:i:s", time());

			} else {
				$list['AffiliationAreaStat'][$i]['id'] = null;
				$list['AffiliationAreaStat'][$i]['created_user_id'] = self::$user;
				$list['AffiliationAreaStat'][$i]['created'] =  date("Y/m/d H:i:s", time());
			}

			$list['AffiliationAreaStat'][$i]['corp_id'] = $r['CommissionInfo']['corp_id'];
			$list['AffiliationAreaStat'][$i]['genre_id'] = (int)$r['DemandInfo']['genre_id'];
			$list['AffiliationAreaStat'][$i]['prefecture'] = (int)$r['DemandInfo']['address1'];
			$list['AffiliationAreaStat'][$i]['commission_count_category'] = (int)$r['CommissionInfo']['commission_count_category'];

			$i++;
		}

		return $list;

	}

	/**
	 * ジャンル別取次数の取得
	 * @param unknown $datas
	 * @return Ambigous <multitype:, number>
	 */
	private function getCommissionGroupCategoryCount($datas) {

		$list['AffiliationStat'] = array();
		$i = 0;

		foreach($datas as $r) {

			// 登録・更新判定
			if (($r['CommissionInfo']['affiliation_stat_id']) != null) {
				$list['AffiliationStat'][$i]['id'] = $r['CommissionInfo']['affiliation_stat_id'];
				$list['AffiliationStat'][$i]['modified_user_id'] = self::$user;
				$list['AffiliationStat'][$i]['modified'] =  date("Y/m/d H:i:s", time());

			} else {
				$list['AffiliationStat'][$i]['id'] = null;
				$list['AffiliationStat'][$i]['created_user_id'] = self::$user;
				$list['AffiliationStat'][$i]['created'] =  date("Y/m/d H:i:s", time());
			}

			$list['AffiliationStat'][$i]['corp_id'] = $r['CommissionInfo']['corp_id'];
			$list['AffiliationStat'][$i]['genre_id'] = (int)$r['DemandInfo']['genre_id'];
			$list['AffiliationStat'][$i]['commission_count_category'] = (int)$r['CommissionInfo']['commission_count_category'];

			$i++;
		}

		return $list;
	}

	/**
	 * ジャンル別受注数を更新
	 */
	private function setCommissionGroupCateogyOrderCount() {

		$conditions = array();

		$joins = array(
				array(
						'table' => 'demand_infos',
						'alias' => "DemandInfo",
						'type' => 'inner',
						'conditions' => array('CommissionInfo.demand_id = DemandInfo.id')
				),
				array(
						'table' => 'affiliation_stats',
						'alias' => "AffiliationStat",
						'type' => 'left',
						'conditions' => array('CommissionInfo.corp_id = AffiliationStat.corp_id', 'DemandInfo.genre_id = AffiliationStat.genre_id')
				),
		);

		// 取次状況が完了
		$conditions['CommissionInfo.commission_status'] = Util::getDivValue('construction_status', 'construction');

		// 取次種別は通常取次
		//$conditions['CommissionInfo.commission_type'] = 0;

		// 取次単価対象外でない
		$conditions['CommissionInfo.unit_price_calc_exclude'] = 0;

// murata.s ORANGE-390 ADD(S)
		// 取次前失注でない
		$conditions['CommissionInfo.lost_flg'] = 0;
// murata.s ORANGE-390 ADD(E)

		$fields = array(
				'CommissionInfo.corp_id',
				'DemandInfo.genre_id',
				'count(*) as "CommissionInfo__orders_count_category"',
				'min("AffiliationStat"."id") as "CommissionInfo__affiliation_stat_id"',
		);

		$group = array(
				'CommissionInfo.corp_id',
				'DemandInfo.genre_id'
		);

		$params = array(
				'fields' => $fields,
				'conditions' => $conditions,
				'joins' => $joins,
				'group' => $group,
		);


		$this->CommissionInfo->virtualFields['orders_count_category'] = 0;
		$this->CommissionInfo->virtualFields['affiliation_stat_id'] = 0;


		$list = $this->CommissionInfo->find('all', $params);

		$datas = $this->getCommissionGroupCategoryOrderCount($list);

		$this->AffiliationStat->saveAll($datas['AffiliationStat']);

// murata.s ORANGE-390 ADD(S)
		// ジャンル別受注数の抽出対象外の統計情報を取得して更新
		$reset_list = $this->getCommissionGroupCateogyOrderCountInitialize();
		$reset_datas = $this->getCommissionGroupCategoryOrderCount($reset_list);
		$this->AffiliationStat->saveAll($reset_datas['AffiliationStat']);
// murata.s ORANGE-390 ADD(E)

	}

	/**
	 * ジャンル別受注数の取得
	 * @param unknown $datas
	 * @return Ambigous <multitype:, number>
	 */
	private function getCommissionGroupCategoryOrderCount($datas) {

		$list['AffiliationStat'] = array();
		$i = 0;

		foreach($datas as $r) {

			// 登録・更新判定
			if (($r['CommissionInfo']['affiliation_stat_id']) != null) {
				$list['AffiliationStat'][$i]['id'] = $r['CommissionInfo']['affiliation_stat_id'];
				$list['AffiliationStat'][$i]['modified_user_id'] = self::$user;
				$list['AffiliationStat'][$i]['modified'] =  date("Y/m/d H:i:s", time());

			} else {
				$list['AffiliationStat'][$i]['id'] = null;
				$list['AffiliationStat'][$i]['created_user_id'] = self::$user;
				$list['AffiliationStat'][$i]['created'] =  date("Y/m/d H:i:s", time());
			}

			$list['AffiliationStat'][$i]['corp_id'] = $r['CommissionInfo']['corp_id'];
			$list['AffiliationStat'][$i]['genre_id'] = (int)$r['DemandInfo']['genre_id'];
			$list['AffiliationStat'][$i]['orders_count_category'] = (int)$r['CommissionInfo']['orders_count_category'];

			$i++;
		}

		return $list;
	}

	/**
	 * 加盟店カテゴリ別統計情報テーブルのデータを作成
	 * @return Ambigous <multitype:, number>
	 */
	private function setCreateAffiliationStatData() {

		$conditions = array();

// 		$joins = array(
// 				array(
// 						'table' => 'm_corps',
// 						'alias' => "MCorp",
// 						'type' => 'inner',
// 						'conditions' => array('MCorpCategory.corp_id = MCorp.id')
// 				),
// 		);

		// 加盟状態 = 加盟
		$conditions['MCorp.affiliation_status'] = 1;

		$fields = array(
				'MCorp.id',
				'MCorpCategory.genre_id',
		);

		$params = array(
				'fields' => $fields,
				'conditions' => $conditions,
//				'joins' => $joins,
		);

		// 検索
		$list = $this->MCorpCategory->find('all', $params);

		foreach($list as $r) {

			$params = array('conditions' => array('corp_id' => $r['MCorp']['id'], 'genre_id' => $r['MCorpCategory']['genre_id']));

			$data = $this->AffiliationStat->find('first', $params);

			if (count($data) === 0) {

				$this->AffiliationStat->save(
						array(
							'corp_id' => $r['MCorp']['id'],
							'genre_id' => $r['MCorpCategory']['genre_id'],
							'commission_count_category' => 0,
							'orders_count_category' => 0,
							'created' => date("Y/m/d H:i:s", time()),
							'created_user_id' => self::$user,
				 		),
					 false);

				$this->AffiliationStat->create();
			}
		}

	}

	/**
	 * 加盟店カテゴリ都道府県別統計情報テーブルのデータを作成
	 * @return Ambigous <multitype:, number>
	 */
	private function setCreateAffiliationAreaStatData() {

		$conditions = array();

		// 加盟状態 = 加盟
		$conditions['MCorp.affiliation_status'] = 1;

		$fields = array(
				'MCorp.id',
				'MCorpCategory.genre_id',
		);

// 2016.09.29 murata.s ORANGE-188 ADD(S)
		$group = array(
				'MCorp.id',
				'MCorpCategory.genre_id',
		);
// 2016.09.29 murata.s ORANGE-188 ADD(E)

// 2016.09.29 murata.s ORANGE-188 CHG(S)
		$params = array(
				'fields' => $fields,
				'conditions' => $conditions,
				'group' => $group,
		);
// 2016.09.29 murata.s ORANGE-188 CHG(E)

		// 検索
		$list = $this->MCorpCategory->find('all', $params);

		foreach($list as $r) {

// 2016.09.29 murata.s ORANGE-188 CHG(S)
// 			for($i = 1; $i <= 47; $i ++) {
// 				$params = array (
// 						'conditions' => array (
// 								'corp_id' => $r ['MCorp'] ['id'],
// 								'genre_id' => $r ['MCorpCategory'] ['genre_id'],
// 								'prefecture' => $i
// 						)
// 				);
//
// 				$data = $this->AffiliationAreaStat->find ( 'first', $params );
//
// 				if (count ( $data ) === 0) {
//
// 					$this->AffiliationAreaStat->save ( array (
// 							'corp_id' => $r ['MCorp'] ['id'],
// 							'genre_id' => $r ['MCorpCategory'] ['genre_id'],
// 							'prefecture' => $i,
// 							'commission_count_category' => 0,
// 							'orders_count_category' => 0,
// 							'created' => date ( "Y/m/d H:i:s", time () ),
// 							'created_user_id' => self::$user
// 					), false );
//
// 					$this->AffiliationAreaStat->create ();
// 				}
// 			}

			$params = array(
					'conditions' => array(
							'corp_id' => $r['MCorp']['id'],
							'genre_id' => $r['MCorpCategory']['genre_id']
					)
			);
			$area_data  =$this->AffiliationAreaStat->find('all', $params);
			$save_data = array();
			for($i=1; $i<=47; $i++){
				$data = array_filter($area_data, function($v) use($i){
					return $v['AffiliationAreaStat']['prefecture'] == $i;
				});

				if(empty($data)){
					$save_data[] = array(
							'AffiliationAreaStat' => array(
									'corp_id' => $r ['MCorp'] ['id'],
									'genre_id' => $r ['MCorpCategory'] ['genre_id'],
									'prefecture' => $i,
									'commission_count_category' => 0,
									'orders_count_category' => 0,
									'created' => date ( "Y/m/d H:i:s", time () ),
									'created_user_id' => self::$user
							)
					);
				}
			}
			if(!empty($save_data)){
				$this->AffiliationAreaStat->saveAll($save_data);
			}
// 2016.09.29 murata.s ORANGE-188 CHG(E)
		}
	}


	/**
	 * 加盟店情報の取次件数を更新
	 */
	private function setCommissionCountOfAffiliation() {

		$conditions = array();

		// 取次種別は通常取次
		//$conditions['CommissionInfo.commission_type'] = 0;

		// 取次前失注でない
		$conditions['CommissionInfo.lost_flg'] = 0;

		// 取次単価対象外でない
		$conditions['CommissionInfo.unit_price_calc_exclude'] = 0;


		$fields = array(
				'CommissionInfo.corp_id',
				'count(*) as "CommissionInfo__count"'
		);

		$group = array(
				'CommissionInfo.corp_id',
		);

		$params = array(
				'fields' => $fields,
				'conditions' => $conditions,
				'group' => $group,
		);

		// 集計用のバーチャルフィールドを作成
		$this->CommissionInfo->virtualFields['count'] = 0;

		// 検索
		$list = $this->CommissionInfo->find('all', $params);

		foreach($list as $r) {

			$this->AffiliationInfo->updateAll(
						array('commission_count' => $r['CommissionInfo']['count']),
						array('corp_id' => $r['CommissionInfo']['corp_id'])
					);
		}

// murata.s ORANGE-390 ADD(S)
		// 加盟店情報の取次件数抽出対象外の加盟店を取得して更新
		$reset_list = $this->getCommissionCountOfAffiliationInitialize();
		foreach($reset_list as $r) {
			$this->AffiliationInfo->updateAll(
					array('commission_count' => $r['CommissionInfo']['count']),
					array('corp_id' => $r['CommissionInfo']['corp_id'])
			);
		}
// murata.s ORANGE-390 ADD(E)

	}

	/**
	 * 加盟店情報の取次件数(1週間分)を更新
	 */
	private function setCommissionWeekCountOfAffiliation() {

		$conditions = array();

		// 取次種別は通常取次
		//$conditions['CommissionInfo.commission_type'] = 0;

		// 取次前失注でない
		$conditions['CommissionInfo.lost_flg'] = 0;

		// 取次単価対象外でない
		$conditions['CommissionInfo.unit_price_calc_exclude'] = 0;

		// 実行日前日までの1週間
		$conditions['CommissionInfo.created >='] = date('Y/m/d', strtotime('-8 day'));
		$conditions["to_char(CommissionInfo.created, 'yyyy/mm/dd') <="] = date('Y/m/d', strtotime('-1 day'));

		$fields = array(
				'CommissionInfo.corp_id',
				'count(*) as "CommissionInfo__count"'
		);

		$group = array(
				'CommissionInfo.corp_id',
		);
		$params = array(
				'fields' => $fields,
				'conditions' => $conditions,
				'group' => $group,
		);

		// 集計用のバーチャルフィールドを作成
		$this->CommissionInfo->virtualFields['count'] = 0;

		// 検索
		$list = $this->CommissionInfo->find('all', $params);

		foreach($list as $r) {

			$this->AffiliationInfo->updateAll(
					array('weekly_commission_count' => $r['CommissionInfo']['count']),
					array('corp_id' => $r['CommissionInfo']['corp_id'])
			);
		}

// murata.s ORANGE-390 ADD(S)
		// 加盟店情報の取次件数(1週間分)の抽出対象外の加盟店を取得して更新
		$reset_list = $this->getCommissionWeekCountOfAffiliationInitialize();
		foreach($reset_list as $r) {
			$this->AffiliationInfo->updateAll(
					array('weekly_commission_count' => $r['CommissionInfo']['count']),
					array('corp_id' => $r['CommissionInfo']['corp_id'])
			);
		}
// murata.s ORANGE-390 ADD(E)

	}


	/**
	 * 加盟店情報の施工金額と受注数を更新
	 */
	private function setReceiptCount() {

		$conditions = array();

		// 取次種別は通常取次
		//$conditions['CommissionInfo.commission_type'] = 0;

		// 取次状況が完了
		$conditions['CommissionInfo.commission_status'] = Util::getDivValue('construction_status', 'construction');

		// 取次単価対象外でない
		$conditions['CommissionInfo.unit_price_calc_exclude'] = 0;

		// murata.s ORANGE-390 ADD(S)
		// 取次前失注でない
		$conditions['CommissionInfo.lost_flg'] = 0;
		// murata.s ORANGE-390 ADD(E)

		$fields = array(
				'CommissionInfo.corp_id',
				'count(*) as "CommissionInfo__count"',
				'avg(construction_price_tax_exclude) as "CommissionInfo__construction_price_tax_exclude"'
		);

		$group = array(
				'CommissionInfo.corp_id',
		);

		$params = array(
				'fields' => $fields,
				'conditions' => $conditions,
				'group' => $group,
		);

		// 集計用のバーチャルフィールドを作成
		$this->CommissionInfo->virtualFields['count'] = 0;
		$this->CommissionInfo->virtualFields['construction_price_tax_exclude'] = 0;

		// 検索
		$list = $this->CommissionInfo->find('all', $params);

		foreach($list as $r) {

			$this->AffiliationInfo->updateAll(
					array('orders_count' => $r['CommissionInfo']['count'], 'construction_unit_price' => $r['CommissionInfo']['construction_price_tax_exclude']),
					array('corp_id' => $r['CommissionInfo']['corp_id'])
			);

		}

// murata.s ORANGE-390 ADD(S)
		// 加盟店情報の施工金額と受注数の抽出対象外の加盟店を取得して更新
		$reset_list = $this->getReceiptCountInitialize();
		foreach($reset_list as $r) {
			$this->AffiliationInfo->updateAll(
					array(
							'orders_count' => $r['CommissionInfo']['count'],
							'construction_unit_price' => $r['CommissionInfo']['construction_price_tax_exclude']
					),
					array('corp_id' => $r['CommissionInfo']['corp_id'])
			);
		}
// murata.s ORANGE-390 ADD(E)

	}

	/**
	 * 加盟店情報の取次単価を更新
	 */
	private function setCommissionPrice() {

// 2017.02.26 murata.s ORANGE-327 CHG(S)
// 		$conditions = array();
//
// 		// 取次種別は通常取次
// 		$conditions['coalesce("AffiliationStat"."commission_unit_price_category", 0) <> '] = 0;
//
// 		$fields = array(
// 				'AffiliationStat.corp_id',
// 				'avg(commission_unit_price_category) as "AffiliationStat__commission_unit_price_category"'
// 		);
//
// 		$group = array(
// 				'AffiliationStat.corp_id',
// 		);
//
// 		$params = array(
// 				'fields' => $fields,
// 				'conditions' => $conditions,
// 				'group' => $group,
// 		);
//
// 		// 集計用のバーチャルフィールドを作成
// 		$this->AffiliationStat->virtualFields['commission_unit_price_category'] = 0;
//
// 		// 検索
// 		$list = $this->AffiliationStat->find('all', $params);

		$sql = 'truncate table shell_work_ci';
		$this->CommissionInfo->query($sql);

		$sql = 'truncate table shell_work_result';
		$this->CommissionInfo->query($sql);

		$query = $this->sql('insert_shell_work_ci.sql');
		$this->CommissionInfo->query($query);

		$query = $this->sql('insert_shell_work_result.sql');
		$this->CommissionInfo->query($query);

// murata.s ORANGE-363 CHG(S)
		$sql = 'select ';
		$sql .= '  t1.corp_id as "AffiliationStat__corp_id" ';
		$sql .= ' ,sum(COALESCE(t1.total_corp_fee,0)) / sum(t1.target_count) as "AffiliationStat__commission_unit_price_category" ';
		$sql .= 'from ';
		$sql .= '  shell_work_result t1 ';
		$sql .= 'group by t1.corp_id ';
		// 取次単価が登録済みで、直近1年間の取次データがない加盟店データを結合
		$sql .= 'union all ';
		$sql .= 'select ';
		$sql .= '  AffiliationInfo.corp_id as "AffiliationStat__corp_id" ';
		$sql .= ' ,0 as "AffiliationStat__commission_unit_price_category" ';
		$sql .= 'from ';
		$sql .= '  affiliation_infos as AffiliationInfo ';
		$sql .= 'left join shell_work_result as t1 ';
		$sql .= '  on t1.corp_id = AffiliationInfo.corp_id ';
		$sql .= 'where ';
		$sql .= '  AffiliationInfo.commission_unit_price > 0 ';
		$sql .= '  and t1.corp_id is null ';
		$sql .= 'group by AffiliationInfo.corp_id ';
// murata.s ORANGE-363 CHG(E)

		// 集計用のバーチャルフィールドを作成
		$this->AffiliationStat->virtualFields['commission_unit_price_category'] = 0;

		// 検索
		$list = $this->AffiliationStat->query($sql);
// 2017.02.06 murata.s ORANGE-327 CHG(E)

		foreach($list as $r) {

			$this->AffiliationInfo->updateAll(
					array('commission_unit_price' => $r['AffiliationStat']['commission_unit_price_category']),
					array('corp_id' => $r['AffiliationStat']['corp_id'])
			);
		}

	}


	/**
	 * 加盟店情報の受注率を更新
	 */
	private function setReceiptRate() {

		$conditions = array();

		// 取次種別は通常取次
		//$conditions['CommissionInfo.commission_type'] = 0;

		// 取次前失注でない
		$conditions['CommissionInfo.lost_flg'] = 0;

		// 取次単価対象外でない
		$conditions['CommissionInfo.unit_price_calc_exclude'] = 0;


		$fields = array(
				'CommissionInfo.corp_id',
				'avg(corp_fee) as "CommissionInfo__corp_fee"'
		);

		$group = array(
				'CommissionInfo.corp_id',
		);

		$params = array(
				'fields' => $fields,
				'conditions' => $conditions,
				'group' => $group,
		);

		// 集計用のバーチャルフィールドを作成
		$this->CommissionInfo->virtualFields['count'] = 0;
		$this->CommissionInfo->virtualFields['corp_fee'] = 0;

		// 検索
		$list = $this->CommissionInfo->find('all', $params);

		foreach($list as $r) {

			$this->AffiliationInfo->updateAll(
					array('orders_rate' => 'trunc(cast(coalesce(orders_count, 0) as dec) / cast(coalesce(commission_count, 0) as dec) * 100, 1)'),
					array('corp_id' => $r['CommissionInfo']['corp_id'])
			);

		}

// murata.s ORANGE-390 ADD(S)
		// 加盟店情報の受注率の抽出対象外の加盟店を取得して更新
		$reset_list = $this->getReceiptRateInitialize();
		foreach($reset_list as $r){
			$this->AffiliationInfo->updateAll(
					array('orders_rate' => '0'),
					array('corp_id' => $r['CommissionInfo']['corp_id'])
			);
		}
// murata.s ORANGE-390 ADD(E)

	}

	/**
	 * SQLファイルを読み込む
	 *
	 * @param string $fileName SQLファイル名
	 * @return string SQL
	 */
	function sql($fileName) {
		$query = "";
		// ファイルからSQLを読み込む
		ob_start();
		include $fileName;
		$query = ob_get_clean();
		return $query;
	}

// murata.s ORANGE-390 ADD(S)
	/**
	 * ジャンル別取次数更新対象外(ジャンル別取次数の初期化対象)レコードを取得
	 */
	private function getCommissionGroupCategoryCountInitialize() {

		$dbo = $this->CommissionInfo->getDataSource();
		$subQuery = $dbo->buildStatement(array(
				'fields' => array('corp_id', 'genre_id'),
				'table' => 'commission_infos',
				'alias' => 'CommissionInfo',
				'joins' => array(
						array(
								'table' => 'demand_infos',
								'alias' => 'DemandInfo',
								'type' => 'inner',
								'conditions' => array('CommissionInfo.demand_id = DemandInfo.id')
						),
				),
				'conditions' => array(
						'CommissionInfo.lost_flg' => 0,
						'CommissionInfo.unit_price_calc_exclude' => 0
				),
				'group' => array(
						'CommissionInfo.corp_id',
						'DemandInfo.genre_id'
				)
		), $this->CommissionInfo);

		$result = $this->AffiliationStat->find('all', array(
				'fields' => array(
						'AffiliationStat.corp_id',
						'AffiliationStat.genre_id',
						'AffiliationStat.id'
				),
				'conditions' => array(
						'SubQuery.corp_id' => NULL,
						'AffiliationStat.commission_count_category >' => 0
				),
				'joins' => array(
						array(
								'table' => "({$subQuery})",
								'alias' => 'SubQuery',
								'type' => 'left',
								'conditions' => array(
										'SubQuery.corp_id = AffiliationStat.corp_id',
										'SubQuery.genre_id = AffiliationStat.genre_id'
								)
						)
				)
		));

		// 取得データを整形する
		$list = array();
		foreach ($result as $v){
			$list[] = array(
					'CommissionInfo' => array(
							'corp_id' => $v['AffiliationStat']['corp_id'],
							'commission_count_category' => 0,
							'affiliation_stat_id' => $v['AffiliationStat']['id']
					),
					'DemandInfo' => array(
							'genre_id' => $v['AffiliationStat']['genre_id']
					)
			);
		}
		return $list;
	}

	/**
	 * ジャンル別受注数更新対象外(ジャンル別受注数の初期化対象)レコードを取得
	 */
	private function getCommissionGroupCateogyOrderCountInitialize() {
		$dbo = $this->CommissionInfo->getDataSource();
		$subQuery = $dbo->buildStatement(array(
				'fields' => array('corp_id', 'genre_id'),
				'table' => 'commission_infos',
				'alias' => 'CommissionInfo',
				'joins' => array(
						array(
								'table' => 'demand_infos',
								'alias' => 'DemandInfo',
								'type' => 'inner',
								'conditions' => array('CommissionInfo.demand_id = DemandInfo.id')
						),
				),
				'conditions' => array(
						'CommissionInfo.commission_status' => Util::getDivValue('construction_status', 'construction'),
						'CommissionInfo.lost_flg' => 0,
						'CommissionInfo.unit_price_calc_exclude' => 0
				),
				'group' => array(
						'CommissionInfo.corp_id',
						'DemandInfo.genre_id'
				)
		), $this->CommissionInfo);

		$result = $this->AffiliationStat->find('all', array(
				'fields' => array(
						'AffiliationStat.corp_id',
						'AffiliationStat.genre_id',
						'AffiliationStat.id'
				),
				'conditions' => array(
						'SubQuery.corp_id' => NULL,
						'AffiliationStat.orders_count_category >' => 0
				),
				'joins' => array(
						array(
								'table' => "({$subQuery})",
								'alias' => 'SubQuery',
								'type' => 'left',
								'conditions' => array(
										'SubQuery.corp_id = AffiliationStat.corp_id',
										'SubQuery.genre_id = AffiliationStat.genre_id'
								)
						)
				)
		));

		// 取得データを整形する
		$list = array();
		foreach ($result as $v){
			$list[] = array(
					'CommissionInfo' => array(
							'corp_id' => $v['AffiliationStat']['corp_id'],
							'orders_count_category' => 0,
							'affiliation_stat_id' => $v['AffiliationStat']['id']
					),
					'DemandInfo' => array(
							'genre_id' => $v['AffiliationStat']['genre_id']
					)
			);
		}
		return $list;

	}

	/**
	 * 加盟店情報の取次件数更新対象外(加盟店情報の取次件数の初期化対象)レコードを取得
	 */
	private function getCommissionCountOfAffiliationInitialize(){
		$dbo = $this->CommissionInfo->getDataSource();
		$subQuery = $dbo->buildStatement(array(
				'fields' => array('corp_id'),
				'table' => 'commission_infos',
				'alias' => 'CommissionInfo',
				'conditions' => array(
						'CommissionInfo.lost_flg' => 0,
						'CommissionInfo.unit_price_calc_exclude' => 0
				),
				'group' => array('CommissionInfo.corp_id')
		), $this->CommissionInfo);

		$result = $this->AffiliationInfo->find('all', array(
				'fields' => array('AffiliationInfo.corp_id'),
				'conditions' => array(
						'SubQuery.corp_id' => NULL,
						'AffiliationInfo.commission_count > ' => 0
				),
				'joins' => array(
						array(
								'table' => "({$subQuery})",
								'alias' => 'SubQuery',
								'type' => 'left',
								'conditions' => array(
										'SubQuery.corp_id = AffiliationInfo.corp_id'
								)
						)
				)
		));

		// 取得データを整形する
		$list = array();
		foreach ($result as $v){
			$list[] = array(
					'CommissionInfo' => array(
							'corp_id' => $v['AffiliationInfo']['corp_id'],
							'count' => 0,
					),
			);
		}
		return $list;
	}

	/**
	 * 加盟店情報の取次件数(1週間分)の更新対象外(1週間分の取次件数初期化対象)レコードを取得する
	 */
	private function getCommissionWeekCountOfAffiliationInitialize(){
		$dbo = $this->CommissionInfo->getDataSource();
		$subQuery = $dbo->buildStatement(array(
				'fields' => array('corp_id'),
				'table' => 'commission_infos',
				'alias' => 'CommissionInfo',
				'conditions' => array(
						'CommissionInfo.lost_flg' => 0,
						'CommissionInfo.unit_price_calc_exclude' => 0,
						'CommissionInfo.created >=' => date('Y/m/d', strtotime('-8 day')),
						"to_char(CommissionInfo.created, 'yyyy/mm/dd') <=" => date('Y/m/d', strtotime('-1 day')),
				),
				'group' => array('CommissionInfo.corp_id')
		), $this->CommissionInfo);

		$result = $this->AffiliationInfo->find('all', array(
				'fields' => array('AffiliationInfo.corp_id'),
				'conditions' => array(
						'SubQuery.corp_id' => NULL,
						'AffiliationInfo.weekly_commission_count > ' => 0
				),
				'joins' => array(
						array(
								'table' => "({$subQuery})",
								'alias' => 'SubQuery',
								'type' => 'left',
								'conditions' => array(
										'SubQuery.corp_id = AffiliationInfo.corp_id'
								)
						)
				)
		));

		// 取得データを整形する
		$list = array();
		foreach ($result as $v){
			$list[] = array(
					'CommissionInfo' => array(
							'corp_id' => $v['AffiliationInfo']['corp_id'],
							'count' => 0,
					),
			);
		}
		return $list;
	}

	/**
	 * 加盟店情報の施工金額と受注数の更新対象外(施工金額と受注数の初期化対象)レコードを取得
	 */
	private function getReceiptCountInitialize(){
		$dbo = $this->CommissionInfo->getDataSource();
		$subQuery = $dbo->buildStatement(array(
				'fields' => array('corp_id'),
				'table' => 'commission_infos',
				'alias' => 'CommissionInfo',
				'conditions' => array(
						'CommissionInfo.commission_status' => Util::getDivValue('construction_status', 'construction'),
						'CommissionInfo.lost_flg' => 0,
						'CommissionInfo.unit_price_calc_exclude' => 0
				),
				'group' => array('CommissionInfo.corp_id')
		), $this->CommissionInfo);

		$result = $this->AffiliationInfo->find('all', array(
				'fields' => array('AffiliationInfo.corp_id'),
				'conditions' => array(
						'SubQuery.corp_id' => NULL,
						'AffiliationInfo.orders_count > ' => 0
				),
				'joins' => array(
						array(
								'table' => "({$subQuery})",
								'alias' => 'SubQuery',
								'type' => 'left',
								'conditions' => array(
										'SubQuery.corp_id = AffiliationInfo.corp_id'
								)
						)
				)
		));

		// 取得データを整形する
		$list = array();
		foreach ($result as $v){
			$list[] = array(
					'CommissionInfo' => array(
							'corp_id' => $v['AffiliationInfo']['corp_id'],
							'count' => 0,
							'construction_price_tax_exclude' => 0
					),
			);
		}
		return $list;
	}

	/**
	 * 加盟店情報受注率更新対象外(受注率の初期化対象)レコードを取得
	 */
	private function getReceiptRateInitialize(){
		$dbo = $this->CommissionInfo->getDataSource();
		$subQuery = $dbo->buildStatement(array(
				'fields' => array('corp_id'),
				'table' => 'commission_infos',
				'alias' => 'CommissionInfo',
				'conditions' => array(
						'CommissionInfo.lost_flg' => 0,
						'CommissionInfo.unit_price_calc_exclude' => 0
				),
				'group' => array('CommissionInfo.corp_id')
		), $this->CommissionInfo);

		$result = $this->AffiliationInfo->find('all', array(
				'fields' => array('AffiliationInfo.corp_id'),
				'conditions' => array('SubQuery.corp_id' => NULL),
				'joins' => array(
						array(
								'table' => "({$subQuery})",
								'alias' => 'SubQuery',
								'type' => 'left',
								'conditions' => array('SubQuery.corp_id = AffiliationInfo.corp_id')
						)
				)
		));

		// 取得データを整形する
		$list = array();
		foreach ($result as $v){
			$list[] = array(
					'CommissionInfo' => array(
							'corp_id' => $v['AffiliationInfo']['corp_id'],
					),
			);
		}
		return $list;
	}

	/**
	 * 都道府県ジャンル別取次数更新対象外(都道府県ジャンル別取次数の初期化対象)レコードを取得
	 */
	private function getAreaCommissionGroupCateogyCountInitialize(){
		$dbo = $this->CommissionInfo->getDataSource();
		$subQuery = $dbo->buildStatement(array(
				'fields' => array(
						'corp_id',
						'genre_id',
						'address1'
				),
				'table' => 'commission_infos',
				'alias' => 'CommissionInfo',
				'joins' => array(
						array(
								'table' => 'demand_infos',
								'alias' => 'DemandInfo',
								'type' => 'inner',
								'conditions' => array('CommissionInfo.demand_id = DemandInfo.id')
						)
				),
				'conditions' => array(
						'CommissionInfo.lost_flg' => 0,
						'CommissionInfo.unit_price_calc_exclude' => 0
				),
				'group' => array(
						'CommissionInfo.corp_id',
						'DemandInfo.genre_id',
						'DemandInfo.address1'
				)
		), $this->CommissionInfo);

		$result = $this->AffiliationAreaStat->find('all', array(
				'fields' => array(
						'AffiliationAreaStat.corp_id',
						'AffiliationAreaStat.genre_id',
						'AffiliationAreaStat.prefecture',
						'AffiliationAreaStat.id'
				),
				'conditions' => array(
						'SubQuery.corp_id' => NULL,
						'AffiliationAreaStat.commission_count_category > ' => 0
				),
				'joins' => array(
						array(
								'table' => "({$subQuery})",
								'alias' => 'SubQuery',
								'type' => 'left',
								'conditions' => array(
										'SubQuery.corp_id = AffiliationAreaStat.corp_id',
										'SubQuery.genre_id = AffiliationAreaStat.genre_id',
										'SubQuery.address1 = AffiliationAreaStat.prefecture'
								)
						)
				)
		));

		// 取得データを整形する
		$list = array();
		foreach ($result as $v){
			$list[] = array(
					'CommissionInfo' => array(
							'corp_id' => $v['AffiliationAreaStat']['corp_id'],
							'commission_count_category' => 0,
							'affiliation_area_stat_id' => $v['AffiliationAreaStat']['id']
					),
					'DemandInfo' => array(
							'genre_id' => $v['AffiliationAreaStat']['genre_id'],
							'address1' => $v['AffiliationAreaStat']['prefecture'],
					)
			);
		}
		return $list;

	}
// murata.s ORANGE-390 ADD(E)

}
