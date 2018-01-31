<?php 
App::uses('AppShell', 'Console/Command');

class CommissionAlertCreateShell extends AppShell
{
	public $uses = array(
					'MCommissionAlertSetting',
					'MCommissionAlert',
					'CommissionInfo'
	);

	/**
	 * main
	 * コマンドに --verboseを付与して実行するとメッセージを出力します
	 * ex) cake commission_alert_create --verbose
	 *
	 */
	public function main() {
		$now = new DateTime();
		$_SESSION['Auth']['User']['user_id'] = 'system';

		$this->out("<info>【{$now->format('Y-m-d H:i:s')}】起動</info>", 1, Shell::VERBOSE);

		//m_alertsの削除
		$this->out("<info>削除処理を開始します</info>", 1, Shell::VERBOSE);

		$del_alerts = $this->__getDelTargetMCommissionAlert();
		if ($del_alerts) {
			foreach ($del_alerts as $alert) {
				$this->out("<info>削除ID=" . $alert['MCommissionAlert']['commission_id'] . "</info>", 1, Shell::VERBOSE);
				$this->MCommissionAlert->delete($alert['MCommissionAlert']['id']);
			}
		}

		//アラートの設定を取得
		$m_settings = $this->MCommissionAlertSetting->find('all');

		//m_commission_alertsの更新
		$this->out("<info>更新処理を開始します</info>", 1, Shell::VERBOSE);
		foreach ($m_settings as $m_setting) {
			$query = sprintf("update m_commission_alerts set condition_value = %d, condition_unit = '%s', rits_follow_datetime = %d where phase_id = %d and correspond_status = %d",
								$m_setting['MCommissionAlertSetting']['condition_value'],
							 	$m_setting['MCommissionAlertSetting']['condition_unit'],
								$m_setting['MCommissionAlertSetting']['rits_follow_datetime'],
								$m_setting['MCommissionAlertSetting']['phase_id'],
								$m_setting['MCommissionAlertSetting']['correspond_status']);
			$this->out($query, 1, Shell::VERBOSE);
			$this->MCommissionAlertSetting->query($query);
		}

		$this->out("<info>登録処理を開始します</info>", 1, Shell::VERBOSE);

		$commission_infos = $this->__getInsTargetCommissionInfo();
		foreach ($commission_infos as $info) {
			foreach ($m_settings as $m_setting) {
				//データの存在チェックを行い、データがなければinsert
				$m_commission_alert = $this->MCommissionAlert->find('all', array(
																	'conditions' => array(
																		'commission_id = ' . $info['CommissionInfo']['id'],
																		'phase_id = ' . $m_setting['MCommissionAlertSetting']['phase_id'],
																		'correspond_status = ' . $m_setting['MCommissionAlertSetting']['correspond_status']
																)
				));
				if ($m_commission_alert) {
					continue;
				}
				$this->out("<info><<{$info['CommissionInfo']['id']}>>phase={$m_setting['MCommissionAlertSetting']['phase_id']} status={$m_setting['MCommissionAlertSetting']['correspond_status']}</info>", 1, Shell::VERBOSE);

				$m_commission = array('MCommissionAlert' => array(
						'commission_id' => $info['CommissionInfo']['id'],
						'phase_id' => $m_setting['MCommissionAlertSetting']['phase_id'],
						'correspond_status' => $m_setting['MCommissionAlertSetting']['correspond_status'],
						'condition_value' => $m_setting['MCommissionAlertSetting']['condition_value'],
						'condition_unit' => $m_setting['MCommissionAlertSetting']['condition_unit'],
						'rits_follow_datetime' => $m_setting['MCommissionAlertSetting']['rits_follow_datetime']
				));
				//print_r($m_commission);
				$this->MCommissionAlert->create();
				$this->MCommissionAlert->save($m_commission);
				//print_r($this->MCommissionAlert->validationErrors);
			}			
		}
		$now = new DateTime();
		$this->out("<info>【{$now->format('Y-m-d H:i:s')}】終了</info>", 1, Shell::VERBOSE);
	}
	
	private function __getDelTargetMCommissionAlert() {
		$param = array(
					'conditions' => array(
						'OR' => array(
							'CommissionInfo.commit_flg != 1',
							'CommissionInfo.commission_status = 3',
							'CommissionInfo.commission_status = 4',
							'CommissionInfo.commission_status = 5',
							'CommissionInfo.lost_flg != 0',
							'CommissionInfo.del_flg != 0',
							"DemandInfo.del_flg != 0",
							'DemandInfo.demand_status = 6', //失注
						)
					),
					'joins' => array(
						array(
							'table' => 'commission_infos',
							'alias' => 'CommissionInfo',
							'type' => 'inner',
							'conditions' => array(
										'MCommissionAlert.commission_id = CommissionInfo.id',
							)
						),
						// DemandInfo.demand_statusが失注も削除対象
						array (
							'fields' => 'DemandInfo.demand_status,DemandInfo.receive_datetime',
							'type' => 'inner',
							"table" => "demand_infos",
							"alias" => "DemandInfo",
							"conditions" => array (
								"DemandInfo.id = CommissionInfo.demand_id",
							)
						),
					)
		);
		return $this->MCommissionAlert->find('all', $param);
	}
	
	private function __getInsTargetCommissionInfo() {
		return $this->CommissionInfo->find('all', array(
				'conditions' => array(
						'CommissionInfo.commit_flg = 1',
						'CommissionInfo.commission_status != 3', //施工完了
						'CommissionInfo.commission_status != 4', //失注
						'CommissionInfo.commission_status != 5', //紹介済
						'CommissionInfo.lost_flg = 0',
						'CommissionInfo.del_flg = 0',
						'DemandInfo.demand_status != 6', //失注
						// 過去分のデータは対象外
						'DemandInfo.receive_datetime >=' => '2016-01-05 00:00:00',
				),
				// DemandInfo.demand_statusが失注も除外
				'joins' => array(
					array (
						'fields' => 'DemandInfo.demand_status,DemandInfo.receive_datetime',
						'type' => 'inner',
						"table" => "demand_infos",
						"alias" => "DemandInfo",
						"conditions" => array (
							"DemandInfo.id = CommissionInfo.demand_id",
							"DemandInfo.del_flg != 1"
						)
					),
				)
		));		
	}
}
