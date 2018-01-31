<?php
App::uses('AppShell', 'Console/Command');

class CheckDeadlinePastAuctionShell extends AppShell {

// 2016.11.29 murata.s ORANGE-259 CHG(S)
// 2016.11.17 murata.s ORANGE-185 CHG(S)
	public $uses = array('DemandInfo', 'CommissionInfo', 'MSite', 'DemandCorrespond');
// 2016.11.17 murata.s ORANGE-185 CHG(E)
// 2016.11.29 murata.s ORANGE-259 CHG(E)
// 2016.11.17 murata.s ORANGE-185 ADD(S)
	public $tasks = array('AutomaticAuctionSelection');
// 2016.11.17 murata.s ORANGE-185 ADD(E)
	private static $user = 'system';

	public function main() {


		try {

			$this->log('オークション流れ案件処理start', AUCTION_LOG);

			$this->execute();

			$this->log('オークション流れ案件処理end', AUCTION_LOG);

		} catch (Exception $e) {
			$this->log($e, SHELL_LOG);
		}

	}

	/**
	 * オークション流れ対象案件を抽出し、フラグを設定する。
	 */
	private function execute() {

		$conditions = array();

		$this->DemandInfo->unbindModelAll(false);

// murata.s ORANGE-437 CHG(S)
		//取次確定ではない案件をすべて取得
		$dbo = $this->CommissionInfo->getDataSource();
		$commissionInfo = $dbo->buildStatement(array(
				'fields' => array('demand_id'),
				'table' => 'commission_infos',
				'alias' => 'CommissionInfoSub',
				'conditions' => array(
						'CommissionInfoSub.commit_flg' => 1
				)
		), $this->CommissionInfo);
		$joins = array(
				array(
						'type' => 'left',
						'table' => "({$commissionInfo})",
						'alias' => 'CommissionInfo',
						'conditions' => array('CommissionInfo.demand_id = DemandInfo.id')
				)
		);
		$conditions['CommissionInfo.demand_id'] = null;
// murata.s ORANGE-437 CHG(E)

		// オークション流れ案件以外
		$conditions['DemandInfo.auction'] = 0;

// 2016.11.17 murata.s ORANGE-185 CHG(S)
		// オークション選定方式案件
//		$conditions['DemandInfo.selection_system'] = Util::getDivValue('selection_type', 'AuctionSelection');
		$conditions['DemandInfo.selection_system'] = array(
				Util::getDivValue('selection_type', 'AuctionSelection'),
				Util::getDivValue('selection_type', 'AutomaticAuctionSelection'));
// 2016.11.17 murata.s ORANGE-185 CHG(E)

		// オークション締切日時 < システム日付
		$conditions['DemandInfo.auction_deadline_time <'] = date("Y/m/d H:i");

// 2016.11.10 murata.s ORNAGE-185 CHG(S)
// 		$fields = array('DemandInfo.id', 'DemandInfo.auction');
		$fields = array('DemandInfo.id', 'DemandInfo.auction', 'DemandInfo.selection_system', 'DemandInfo.site_id');
// 2016.11.17 murata.s ORANGE-185 CHG(E)

// murata.s ORANGE-437 CHG(S)
		$params = array(
				'fields' => $fields,
				'conditions' => $conditions,
				'joins' => $joins
		);
// murata.s ORANGE-437 CHG(E)

		// 検索
		$list = $this->DemandInfo->find('all', $params);

		$tmp = array();
		// オークション流れ案件とする。
		foreach ($list as $key=>&$row) {
// 2016.11.17 murata.s ORANGE-185 CHG(S)
// 			$row['DemandInfo']['auction'] = 1;
// 			$row['DemandInfo']['selection_system'] = Util::getDivValue('selection_type', 'ManualSelection');
// 			$row['DemandInfo']['demand_status'] = Util::getDivValue('demand_status', 'no_selection');
// 			$row['DemandInfo']['priority'] = 0;
//			$tmp['DemandInfo'][] = $row['DemandInfo'];

			//ORANGE-259 CHG S
// 2016.11.29 murata.s ORANGE-259 DEL(S)
//			$tmp['DemandInfo'][$key] = array('DemandInfo' => $row['DemandInfo']);
// 2016.11.29 murata.s ORANGE-259 DEL(S)
//			if($row['DemandInfo']['selection_system'] == Util::getDivValue('selection_type', 'AutomaticAuctionSelection')){


				// commission_type_divの取得
				$commission_type = $this->MSite->find('first', array(
						'fields' => 'MCommissionType.commission_type_div',
						'joins' => array(
								array(
										'fields' => '*',
										'type' => 'inner',
										'table' => 'm_commission_types',
										'alias' => 'MCommissionType',
										'conditions' => array(
												'MSite.commission_type = MCommissionType.id'
										)
								)
						),
						'conditions' => array('MSite.id' => $row['DemandInfo']['site_id'])
				));
				$row['DemandInfo']['commission_type_div'] = $commission_type['MCommissionType']['commission_type_div'];

// 2016.11.29 murata.s ORANGE-259 CHG(S)
				// 入札を配信した順番に手動選定する
				$commission_infos = $this->AutomaticAuctionSelection->execute($row);
				if($row['DemandInfo']['selection_system'] == Util::getDivValue('selection_type', 'AutomaticAuctionSelection')){
					$user_id = 'AutomaticAuction';
				}else{
					$user_id = self::$user;
				}
				$row['DemandInfo']['modified_user_id'] = $user_id;
				$row['DemandInfo']['modified'] = date('Y-m-d H:i:s');

				// 自動選定(入札式自動)の場合
				$row['DemandInfo']['auction'] = 1;
				$row['DemandInfo']['selection_system'] = Util::getDivValue('selection_type', 'ManualSelection');
				// $commission_infos
				$demand_status = Util::getDivValue('demand_status', 'no_selection');
				if(!empty($commission_infos['CommissionInfo'])){
					foreach ($commission_infos['CommissionInfo'] as $value) {
						if($value['lost_flg'] == 0){
							$demand_status = Util::getDivValue('demand_status', 'agency_before');
							break;
						}
					}
				}

				$row['DemandInfo']['demand_status'] = $demand_status;
				$row['DemandInfo']['priority'] = 0;

				//$tmp['DemandInfo'][$key] = array(
				//		'DemandInfo' => $row['DemandInfo'],
				//		'CommissionInfo' => $commission_infos['CommissionInfo']
				//);
				$tmp['DemandInfo'][$key]['DemandInfo'] = $row['DemandInfo'];
				if(!empty($commission_infos['CommissionInfo']))
					$tmp['DemandInfo'][$key]['CommissionInfo'] = $commission_infos['CommissionInfo'];

				if(isset($commission_infos['CommissionInfo'])){
					foreach($commission_infos['CommissionInfo'] as $commission_info)
						$tmp['CommissionInfo'][] = $commission_info;
				}

				if(!empty($commission_infos['corresponding_contens'][0])){
					$tmp['DemandCorrespond'][] = array(
							'demand_id' => $row['DemandInfo']['id'],
							'corresponding_contens' => $commission_infos['corresponding_contens'][0],
							'responders' => '入札流れ',
							'correspond_datetime' => date('Y-m-d H:i:s'),
							// 'rits_responders' => null,
							'created_user_id' => $user_id,
							'created' => date('Y-m-d H:i:s'),
							'modified_user_id' => $user_id,
							'modified' => date('Y-m-d H:i:s'),
					);
				}
				if(!empty($commission_infos['corresponding_contens'][1])){
					$tmp['DemandCorrespond'][] = array(
							'demand_id' => $row['DemandInfo']['id'],
							'corresponding_contens' => $commission_infos['corresponding_contens'][1],
							'responders' => '自動選定',
							'correspond_datetime' => date('Y-m-d H:i:s'),
							'created_user_id' => $user_id,
							'created' => date('Y-m-d H:i:s'),
							'modified_user_id' => $user_id,
							'modified' => date('Y-m-d H:i:s'),
					);
				}
// 2016.11.29 murata.s ORANGE-259 CHE(E)
/*			}else{
				// 入札式選定(入札式手動)の場合
				$row['DemandInfo']['auction'] = 1;
				$row['DemandInfo']['selection_system'] = Util::getDivValue('selection_type', 'ManualSelection');
				$row['DemandInfo']['demand_status'] = Util::getDivValue('demand_status', 'no_selection');
				$row['DemandInfo']['priority'] = 0;

				$tmp['DemandInfo'][] = array('DemandInfo' => $row['DemandInfo']);
			}
*/
			//ORANGE-259 CHG E
// 2016.11.17 murata.s ORANGE-185 CHG(E)
		}
// 2016.11.29 murata.s ORANGE-259 CHG(S)
// 2016.11.17 murata.s ORANGE-185 CHG(S)
// 		if (!empty($tmp)) {
// 			// 更新
// 			$this->DemandInfo->saveAll($tmp['DemandInfo']);
// 		}
		if (!empty($tmp['DemandInfo'])) {
			// 更新
			$this->DemandInfo->saveAll($tmp['DemandInfo'], array('callbacks' => false));
		}
		if(!empty($tmp['CommissionInfo'])){
			// 更新
			$this->CommissionInfo->saveAll($tmp['CommissionInfo'],array('callbacks' => false));
		}

		if(!empty($tmp['DemandCorrespond'])){
			$this->DemandCorrespond->saveAll($tmp['DemandCorrespond'],array('callbacks' => false));
		}
// 2016.11.17 murata.s ORANGE-185 CHG(E)
// 2016.11.29 murata.s ORANGE-259 CHG(E)
	}

}