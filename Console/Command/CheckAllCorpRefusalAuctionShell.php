<?php
App::uses('AppShell', 'Console/Command');

class CheckAllCorpRefusalAuctionShell extends AppShell {

// 2016.11.29 murata.s ORANGE-259 CHG(S)
// 2016.11.17 murata.s ORANGE-185 CHG(S)
	public $uses = array('DemandInfo','AuctionInfo', 'CommissionInfo', 'DemandCorrespond');
// 2016.11.17 murata.s ORANGE-185 CHG(E)
// 2016.11.29 murata.s ORANGE-259 CHG(E)
// 2016.11.17 murata.s ORANGE-185 ADD(S)
	public $tasks = array('AutomaticAuctionSelection');
// 2016.11.17 murata.s ORANGE-185 ADD(E)
	private static $user = 'system';

	public function main() {


		try {

			$this->log('オークション流れ案件処理(オークション期限前チェック)start', AUCTION_LOG);

			$this->execute();

			$this->log('オークション流れ案件処理(オークション期限前チェック)end', AUCTION_LOG);

		} catch (Exception $e) {
			$this->log($e, SHELL_LOG);
		}

	}

	/**
	 * オークション流れ対象案件(対象加盟店が全辞退)を抽出し、フラグを設定する。
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
				Util::getDivValue('selection_type', 'AutomaticAuctionSelection')
		);
// 2016.11.17 murata.s ORANGE-185 CHG(E)

// 2016.11.17 murata.s ORANGE-185 CHG(S)
// 		$fields = array('DemandInfo.id', 'DemandInfo.auction');
		$fields = array('DemandInfo.id', 'DemandInfo.auction', 'DemandInfo.selection_system');
// 2016.11.17 murata.s ORANGE-185 CHG((E)
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
		foreach ($list as &$row) {

			$sql  = 'select(';
			$sql .= '(select count(0) from auction_infos where demand_id =' . $row['DemandInfo']['id'] . ')';
			$sql .= ' - ';
			$sql .= '(select count(0) from auction_infos where demand_id =' . $row['DemandInfo']['id'] . ' and refusal_flg = 1)';
			$sql .= ') as r_count';

			$refusal_count = $this->AuctionInfo->query($sql);

			if ( $refusal_count[0][0]['r_count'] == 0) {

// 2016.11.17 murata.s ORANGE-185 CHG(S)
// 				// query結果が0の場合、案件IDに対して、全件refusal_flgが1(辞退)になっているので、オークション流れにする。
// 				$row['DemandInfo']['auction'] = '1';
// 				$row['DemandInfo']['selection_system'] = Util::getDivValue('selection_type', 'ManualSelection');
// 				$row['DemandInfo']['demand_status'] = Util::getDivValue('demand_status', 'no_selection');
// 				$row['DemandInfo']['priority'] = '0';
// 				$tmp['DemandInfo'][] = $row['DemandInfo'];
//
// 				if (!empty($tmp)) {
//
// 					// 更新
// 					$this->DemandInfo->saveAll($tmp['DemandInfo']);
// 				}
//
//				$this->DemandInfo->create ();

				//ORANGE-259 CHG S 手動選定の場合も辞退だったら取次失注フラグON
				// 自動選定(入札式自動)の場合は取次情報を設定する
//				if($row['DemandInfo']['selection_system'] == Util::getDivValue('selection_type', 'AutomaticAuctionSelection')){
					// query結果が0の場合、案件IDに対して、全件refusal_flgが1(辞退)になっているので、オークション流れにする。
// 2016.11.29 murata.s ORANGE-259 CHG(S)
					$commission_infos = $this->AutomaticAuctionSelection->execute($row);
					if($row['DemandInfo']['selection_system'] == Util::getDivValue('selection_type', 'AutomaticAuctionSelection')){
						$user_id = 'AutomaticAuction';
					}else{
						$user_id = self::$user;
					}
					$row['DemandInfo']['modified_user_id'] = $user_id;
					$row['DemandInfo']['modified'] = date('Y-m-d H:i:s');

					// 2016.12.09 CHG(S)
					// $row['DemandInfo']['auction'] = '1';
					// $row['DemandInfo']['selection_system'] = Util::getDivValue('selection_type', 'ManualSelection');
					// $row['DemandInfo']['demand_status'] = Util::getDivValue('demand_status', 'no_selection');
					// $row['DemandInfo']['priority'] = '0';
					$row['DemandInfo']['auction'] = '1';
					$row['DemandInfo']['selection_system'] = Util::getDivValue('selection_type', 'ManualSelection');
					$row['DemandInfo']['priority'] = '0';
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
					// 2016.12.09 CHG(E)

// 2016.11.29 murata.s ORANGE-259 CHG(E)

					$tmp['DemandInfo'][] = array(
							'DemandInfo' => $row['DemandInfo'],
							'CommissionInfo' => $commission_infos['CommissionInfo']
					);

					foreach($commission_infos['CommissionInfo'] as $commission_info)
						$tmp['CommissionInfo'][] = $commission_info;

// 2016.11.29 murata.s ORANGE-259 ADD(S)
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
// 2016.11.29 murata.s ORANGE-259 ADD(E)
/*
				}else{
					// query結果が0の場合、案件IDに対して、全件refusal_flgが1(辞退)になっているので、オークション流れにする。
					$row['DemandInfo']['auction'] = '1';
					$row['DemandInfo']['selection_system'] = Util::getDivValue('selection_type', 'ManualSelection');
					$row['DemandInfo']['demand_status'] = Util::getDivValue('demand_status', 'no_selection');
					$row['DemandInfo']['priority'] = '0';

					$tmp['DemandInfo'][] = array('DemandInfo' => $row['DemandInfo']);
				}
*/
				//ORANGE-259 CHG E
// 2016.11.17 murata.s ORANGE-185 CHG(E)
			}
		}
// 2016.11.29 murata.s ORANGE-259 CHG(S)
// 2016.11.17 murata.s ORANGE-185 ADD(S)
		if(!empty($tmp['DemandInfo'])){
			// 更新
			$this->DemandInfo->saveAll($tmp['DemandInfo'], array('callbacks' => false));
		}
		if(!empty($tmp['CommissionInfo'])){
			// 更新
			$this->CommissionInfo->saveAll($tmp['CommissionInfo'], array('callbacks' => false));
		}
		if(!empty($tmp['DemandCorrespond'])){
			$this->DemandCorrespond->saveAll($tmp['DemandCorrespond'],array('callbacks' => false));
		}
// 2016.11.17 murata.s ORANGE-185 ADD(E)
// 2016.11.29 murata.s ORANGE-259 CHG(E)

	} // function

}