<?php
class DemandUtilComponent extends Component {

	/**
	 * CSV出力処理
	 *
	 * @param unknown $conditions
	 * @return unknown
	 */
	public function getDataCsv($conditions = array(), $type= 'Demand') {

		Configure::write('debug', '0');

		$MSite = ClassRegistry::init('MSite');
		$MGenre = ClassRegistry::init('MGenre');
		$MCategory = ClassRegistry::init('MCategory');
		$MUser = ClassRegistry::init('MUser');
		$DemandInfo = ClassRegistry::init('DemandInfo');

		// 企業名 または 企業名ふりがな が検索条件にあった場合
		// 		if(isset($conditions['mcorp.corp_name like']) || isset($conditions['mcorp.corp_name_kana like'])){

		// 			$db = $this->CommissionInfo->getDataSource();
		// 			$subQuery = $db->buildStatement(
		// 					array(
		// 							'fields' => array('"demand_id"'),
		// 							'table' => $db->fullTableName($this->CommissionInfo),
		// 							'alias' => 'CommissionInfo',
		// 							'joins' =>	array(array(
		// 									'table' => 'm_corps',
		// 									'alias' => "MCorp",
		// 									'type' => 'inner',
		// 									'conditions' => array('commissioninfo.corp_id = mcorp.id'))
		// 							),
		// 							'conditions' => $conditions,

		// 					),
		// 					$this->CommissionInfo
		// 			);

		// 			$subQuery = ' "DemandInfo"."id" in ('. $subQuery . ') ';
		// 			$subQueryExpression = $db->expression($subQuery);

		// 			$conditions = (array)$subQueryExpression->value;
		// 		}

		if($type == 'Demand'){
			$joins = array(
					array(
							'table' => 'm_sites',
							'alias' => "MSite",
							'type' => 'left',
							'conditions' => array('DemandInfo.site_id = MSite.id')
					),
					array(
							'table' => 'm_genres',
							'alias' => "MGenre",
							'type' => 'left',
							'conditions' => array('DemandInfo.genre_id = MGenre.id')
					),
					array(
							'table' => 'm_categories',
							'alias' => "MCategory",
							'type' => 'left',
							'conditions' => array('DemandInfo.category_id = MCategory.id')
					),
					array(
							'table' => 'commission_infos',
							'alias' => "CommissionInfo",
							'type' => 'left',
							// 2015.08.12 s.harada MOD start ORANGE-766
							// 'conditions' => array('DemandInfo.id = CommissionInfo.demand_id' , 'CommissionInfo.commit_flg = 1')
							'conditions' => array(
									'OR' => array(
											array('CommissionInfo.commit_flg = 1'),
											array('CommissionInfo.commission_type = 1'),
									),
									'AND' => array(
											array('DemandInfo.id = CommissionInfo.demand_id')
									)
							)
							// 2015.08.12 s.harada MOD start ORANGE-766
					),
					array(
							'table' => 'm_corps',
							'alias' => "MCorp",
							'type' => 'left',
							'conditions' => array('CommissionInfo.corp_id = MCorp.id')
					)
			);
		}else{
			$joins = array(
					array(
							'table' => 'm_sites',
							'alias' => "MSite",
							'type' => 'left',
							'conditions' => array('DemandInfo.site_id = MSite.id')
					),
					array(
							'table' => 'm_genres',
							'alias' => "MGenre",
							'type' => 'left',
							'conditions' => array('DemandInfo.genre_id = MGenre.id')
					),
					array(
							'table' => 'm_categories',
							'alias' => "MCategory",
							'type' => 'left',
							'conditions' => array('DemandInfo.category_id = MCategory.id')
					),
					array(
							'table' => 'commission_infos',
							'alias' => "CommissionInfo",
							'type' => 'left',
							'conditions' => array('DemandInfo.id = CommissionInfo.demand_id')
					),
					array(
							'table' => 'm_corps',
							'alias' => "MCorp",
							'type' => 'left',
							'conditions' => array('CommissionInfo.corp_id = MCorp.id')
					)
			);
		}

		// 2015.04.30 h.hara(S)
		//$fields_commission_info = 'CommissionInfo.id ,CommissionInfo.commit_flg ,CommissionInfo.commission_type, CommissionInfo.appointers, CommissionInfo.first_commission, CommissionInfo.corp_fee, CommissionInfo.attention, CommissionInfo.commission_dial, CommissionInfo.tel_commission_datetime, CommissionInfo.tel_commission_person, CommissionInfo.commission_fee_rate, CommissionInfo.commission_note_send_datetime, CommissionInfo.commission_note_sender, CommissionInfo.commission_status, CommissionInfo.commission_order_fail_reason, CommissionInfo.complete_date, CommissionInfo.order_fail_date, CommissionInfo.estimate_price_tax_exclude, CommissionInfo.construction_price_tax_exclude, CommissionInfo.construction_price_tax_include, CommissionInfo.deduction_tax_include, CommissionInfo.deduction_tax_exclude, CommissionInfo.confirmd_fee_rate, CommissionInfo.unit_price_calc_exclude, CommissionInfo.report_note';
		//$fields_m_corp = 'MCorp.corp_name';
		// 2015.07.28 s.harada MOD start ORANGE-680
		//$fields_commission_info = 'CommissionInfo.id ,CommissionInfo.commit_flg ,CommissionInfo.commission_type, CommissionInfo.appointers, CommissionInfo.first_commission, CommissionInfo.corp_fee, CommissionInfo.attention, CommissionInfo.commission_dial, CommissionInfo.tel_commission_datetime, CommissionInfo.tel_commission_person, CommissionInfo.commission_fee_rate, CommissionInfo.commission_note_send_datetime, CommissionInfo.commission_note_sender, CommissionInfo.commission_status, CommissionInfo.commission_order_fail_reason, CommissionInfo.complete_date, CommissionInfo.order_fail_date, CommissionInfo.estimate_price_tax_exclude, CommissionInfo.construction_price_tax_exclude, CommissionInfo.construction_price_tax_include, CommissionInfo.deduction_tax_include, CommissionInfo.deduction_tax_exclude, CommissionInfo.confirmd_fee_rate, CommissionInfo.unit_price_calc_exclude, CommissionInfo.report_note,CommissionInfo.corp_id';
		$fields_commission_info = 'CommissionInfo.id ,CommissionInfo.commit_flg ,CommissionInfo.commission_type, CommissionInfo.appointers, CommissionInfo.first_commission, CommissionInfo.corp_fee, CommissionInfo.attention, CommissionInfo.commission_dial, CommissionInfo.tel_commission_datetime, CommissionInfo.tel_commission_person, CommissionInfo.commission_fee_rate, CommissionInfo.commission_note_send_datetime, CommissionInfo.commission_note_sender, CommissionInfo.commission_status, CommissionInfo.commission_order_fail_reason, CommissionInfo.complete_date, CommissionInfo.order_fail_date, CommissionInfo.estimate_price_tax_exclude, CommissionInfo.construction_price_tax_exclude, CommissionInfo.construction_price_tax_include, CommissionInfo.deduction_tax_include, CommissionInfo.deduction_tax_exclude, CommissionInfo.confirmd_fee_rate, CommissionInfo.unit_price_calc_exclude, CommissionInfo.report_note,CommissionInfo.corp_id,CommissionInfo.send_mail_fax,CommissionInfo.send_mail_fax_datetime';
		// 2015.07.28 s.harada MOD end ORANGE-680
		$fields_m_corp = 'MCorp.corp_name, MCorp.official_corp_name';
		// 2015.04.30 h.hara(S)
		$DemandInfo->setVirtualDetectContactDesiredTime();

		// 検索する
		$results = $DemandInfo->find('all',
				array(
						'fields' => '*,' . $fields_commission_info . ', ' . $fields_m_corp . ', MSite.site_name, MGenre.genre_name, MCategory.category_name',
						'conditions' => $conditions,
						'joins' => $joins,
						'order' => array('DemandInfo.id' => 'desc'),
				)
				);

		$demand_status_list = Util::getDropList(__('demand_status', true)); // 案件状況
		$order_fail_reason_list = Util::getDropList(__('order_fail_reason', true)); // 案件失注理由
		$site_list = $MSite->getList(); // サイト
		// 2015.4.7 n.kai ADD start
		$genre_list = $MGenre->getList(); // ジャンル
		// 2015.4.7 n.kai ADD end
		$category_list = $MCategory->getList(); // カテゴリ
		// 2015.07.24 s.harada ADD start ORANGE-659
		$pet_tombstone_demand_list = Util::getDropList(__('pet_tombstone_demand', true)); // ペット墓石の案内
		$sms_demand_list = Util::getDropList(__('sms_demand', true)); // SMS案内
		// 2015.07.24 s.harada ADD end ORANGE-659
		$jbr_work_contents_list = Util::getDropList(__('jbr_work_contents', true)); // [JBR様]作業内容
		$jbr_category_list = Util::getDropList(__('jbr_category', true)); // [JBR様]カテゴリ
		$user_list = $MUser->dropDownUser(); // ユーザー
		$jbr_estimate_status_list = Util::getDropList(__('jbr_estimate_status', true)); // [JBR様]見積書状況
		$jbr_receipt_status_list = Util::getDropList(__('jbr_receipt_status', true)); // [JBR様]領収書状況
		// 2015.07.28 s.harada ADD start ORANGE-680
		$send_mail_fax_list = array('' => '', '0' => '', '1' => '送信済み'); // メール/FAX送信
		// 2015.07.28 s.harada ADD end ORANGE-680
		// 2016.01.19 n.kai ADD start ORANGE-1222
		$acceptance_status_list = Util::getDropList(__('acceptance_status', true)); // 受付ステータス
		// 2016.01.19 n.kai ADD end ORANGE-1222
		$commission_status_list = Util::getDropList(__('commission_status', true)); // 取次状況
		$commission_order_fail_reason_list = Util::getDropList(__('commission_order_fail_reason', true)); // 取次失注理由
		// murata.s ORANGE-478 ADD(S)
		$selection_system_list = Util::getDivList('selection_type'); // 選定方式
		// murata.s ORANGE-478 ADD(E)

		App::uses('CommonHelper', 'Lib/View/Helper');
		$CommonHelper = new CommonHelper(new View());

		// 書き出し変更
		$data = $results;
		// 2017.08.24 e.takeuchi@SharingTechnology ORANGE-516 CHG(S)
		// $change_array = array(0 => 'mail_demand', 1 => 'nighttime_takeover', 2 => 'low_accuracy', 3 => 'remand', 4 => 'immediately', 5 => 'corp_change');
		$change_array = array(0 => 'mail_demand', 1 => 'nighttime_takeover', 2 => 'low_accuracy', 3 => 'remand', 4 => 'immediately', 5 => 'corp_change', 6 => 'sms_reorder');
		// 2017.08.24 e.takeuchi@SharingTechnology ORANGE-516 CHG(E)
		foreach ($results as $key => $val) {
			foreach ($change_array as $v) {
				if ($val['DemandInfo'][$v] == 0) {
					$data[$key]['DemandInfo'][$v] = __('batu', true);
				} else {
					$data[$key]['DemandInfo'][$v] = __('maru', true);
				}
			}
			$data[$key]['DemandInfo']['demand_status'] = !empty($val['DemandInfo']['demand_status']) ? $demand_status_list[$val['DemandInfo']['demand_status']] : '';
			$data[$key]['DemandInfo']['order_fail_reason'] = !empty($val['DemandInfo']['order_fail_reason']) ? $order_fail_reason_list[$val['DemandInfo']['order_fail_reason']] : '';
			$data[$key]['DemandInfo']['site_name'] = !empty($val['MSite']['site_name']) ? $val['MSite']['site_name'] : '';
			$data[$key]['DemandInfo']['genre_name'] = !empty($val['MGenre']['genre_name']) ? $val['MGenre']['genre_name'] : '';
			$data[$key]['DemandInfo']['category_name'] = !empty($val['MCategory']['category_name']) ? $val['MCategory']['category_name'] : '';
			$data[$key]['DemandInfo']['cross_sell_source_site'] = !empty($val['DemandInfo']['cross_sell_source_site']) ? $site_list[$val['DemandInfo']['cross_sell_source_site']] : '';
			// 2015.4.7 n.kai ADD start
			$data[$key]['DemandInfo']['cross_sell_source_genre'] = !empty($val['DemandInfo']['cross_sell_source_genre']) ? $genre_list[$val['DemandInfo']['cross_sell_source_genre']] : '';
			// 2015.4.7 n.kai ADD end
			$data[$key]['DemandInfo']['cross_sell_source_category'] = !empty($val['DemandInfo']['cross_sell_source_category']) ? $category_list[$val['DemandInfo']['cross_sell_source_category']] : '';
			// 2015.07.24 s.harada ADD start ORANGE-659
			$data[$key]['DemandInfo']['pet_tombstone_demand'] = !empty($val['DemandInfo']['pet_tombstone_demand']) ? $pet_tombstone_demand_list[$val['DemandInfo']['pet_tombstone_demand']] : '';
			$data[$key]['DemandInfo']['sms_demand'] = !empty($val['DemandInfo']['sms_demand']) ? $sms_demand_list[$val['DemandInfo']['sms_demand']] : '';
			// 2015.07.24 s.harada ADD end ORANGE-659
			$data[$key]['DemandInfo']['receptionist'] = !empty($val['DemandInfo']['receptionist']) ? $user_list[$val['DemandInfo']['receptionist']] : '';
			$data[$key]['DemandInfo']['address1'] = !empty($val['DemandInfo']['address1']) ? Util::getDivTextJP('prefecture_div', $val['DemandInfo']['address1']) : '';
			$data[$key]['DemandInfo']['jbr_work_contents'] = !empty($val['DemandInfo']['jbr_work_contents']) ? $jbr_work_contents_list[$val['DemandInfo']['jbr_work_contents']] : '';
			$data[$key]['DemandInfo']['jbr_category'] = !empty($val['DemandInfo']['jbr_category']) ? $jbr_category_list[$val['DemandInfo']['jbr_category']] : '';
			$data[$key]['DemandInfo']['jbr_estimate_status'] = !empty($val['DemandInfo']['jbr_estimate_status']) ? $jbr_estimate_status_list[$val['DemandInfo']['jbr_estimate_status']] : '';
			$data[$key]['DemandInfo']['jbr_receipt_status'] = !empty($val['DemandInfo']['jbr_receipt_status']) ? $jbr_receipt_status_list[$val['DemandInfo']['jbr_receipt_status']] : '';
			$data[$key]['DemandInfo']['contact_desired_time'] = $CommonHelper->getContactDesiredTime($val,'〜');
			// 2016.01.19 n.kai ADD start ORANGE-1222
			$data[$key]['DemandInfo']['acceptance_status'] = !empty($val['DemandInfo']['acceptance_status']) ? $acceptance_status_list[$val['DemandInfo']['acceptance_status']] : '';
			// 2016.01.19 n.kai ADD end ORANGE-1222
			// 2016.10.27 murata.s ORANGE-216 ADD(S)
			$data[$key]['DemandInfo']['nitoryu_flg'] = !empty($val['DemandInfo']['nitoryu_flg']) ? __('maru', true) : __('batu', true);
			// 2016.10.27 murata.s ORANGE-216 ADD(E)
			// 2015.07.28 s.harada ADD start ORANGE-680
			$data[$key]['CommissionInfo']['send_mail_fax'] = !empty($val['CommissionInfo']['send_mail_fax']) ? $send_mail_fax_list[$val['CommissionInfo']['send_mail_fax']] : '';
			// 2015.07.28 s.harada ADD end ORANGE-680
			$data[$key]['CommissionInfo']['commit_flg'] = !empty($val['CommissionInfo']['commit_flg']) ? __('maru', true) : __('batu', true);
			$data[$key]['CommissionInfo']['commission_type'] = !empty($val['CommissionInfo']['commission_type']) ? __('bulk_quote', true) : __('normal_commission', true);
			$data[$key]['CommissionInfo']['appointers'] = !empty($val['CommissionInfo']['appointers']) ? $user_list[$val['CommissionInfo']['appointers']] : '';
			$data[$key]['CommissionInfo']['first_commission'] = !empty($val['CommissionInfo']['first_commission']) ? __('maru', true) : __('batu', true);
			$data[$key]['CommissionInfo']['tel_commission_person'] = !empty($val['CommissionInfo']['tel_commission_person']) ? $user_list[$val['CommissionInfo']['tel_commission_person']] : '';
			$data[$key]['CommissionInfo']['commission_note_sender'] = !empty($val['CommissionInfo']['commission_note_sender']) ? $user_list[$val['CommissionInfo']['commission_note_sender']] : '';
			$data[$key]['CommissionInfo']['commission_status'] = !empty($val['CommissionInfo']['commission_status']) ? $commission_status_list[$val['CommissionInfo']['commission_status']] : '';
			$data[$key]['CommissionInfo']['commission_order_fail_reason'] = !empty($val['CommissionInfo']['commission_order_fail_reason']) ? $commission_order_fail_reason_list[$val['CommissionInfo']['commission_order_fail_reason']] : '';
			// murata.s ORANGE-478 ADD(S)
			$data[$key]['DemandInfo']['selection_system'] = !is_null($val['DemandInfo']['selection_system']) ? $selection_system_list[$val['DemandInfo']['selection_system']] : '';;
			// murata.s ORANGE-478 ADD(E)
		}
		return $data;
	}

}