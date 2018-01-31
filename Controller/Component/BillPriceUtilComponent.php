<?php
App::uses('Component', 'Controller');

class BillPriceUtilComponent extends Component{

	public function initialize($controller){
		$this->controller = $controller;

		$this->CommissionInfo = $this->controller->CommissionInfo;
		$this->MCategory = $this->controller->MCategory;
		$this->MTaxRate = $this->controller->MTaxRate;
	}

	/**
	 * 手数料を計算する
	 *
	 * @param int $commission_id 取次ID
	 * @param int $commission_status 取次状況
	 * @param string $complete_date 施工完了日
	 * @param int $construction_price_tax_exculude 施工金額(税抜)
	 */
	public function calc_bill_price($commission_id, $commission_status, $complete_date, $construction_price_tax_exculude){
		// commission_infosの取得
		$data = $this->__find_commission_info($commission_id);
		$data['CommissionInfo']['commission_status'] = $commission_status;
		$data['CommissionInfo']['complete_date'] = $complete_date;
		$data['CommissionInfo']['construction_price_tax_exclude'] = $construction_price_tax_exculude;

		return $this->calculate_bill_price($data);
	}

	/**
	 * 手数料を計算する
	 *
	 * @param array $data 取次情報
	 */
	public function calculate_bill_price($data){

		// 消費税
		$tax_rate = $this->__set_m_tax_rate($data['CommissionInfo']['complete_date']);
		$data['MTaxRate']['tax_rate'] = $tax_rate['MTaxRate']['tax_rate'];

		if($data['CommissionInfo']['commission_status'] != Util::getDivValue('construction_status','introduction')){

			// 施工金額(税抜)
			$construction_price_tax_exclude = $data['CommissionInfo']['construction_price_tax_exclude'];
			if(empty($construction_price_tax_exclude))
				$construction_price_tax_exclude = 0;

			// 出張費(税抜)  => commission_infos.business_trip_amount
			if(empty($data['CommissionInfo']['business_trip_amount']))
				$data['CommissionInfo']['business_trip_amount'] = 0;

			// 控除金額(税込) => commission_infos.deduction_tax_include
			if(empty($data['CommissionInfo']['deduction_tax_include']))
				$data['CommissionInfo']['deduction_tax_include'] = 0;

			if($tax_rate['MTaxRate']['tax_rate_val'] != ''){

				// 施工金額(税抜)が空白の場合、無条件に計算すると税込に「0」がセットされるため、
				// 税込が0以上の場合のみ計算する
				if(!empty($data['CommissionInfo']['construction_price_tax_exclude'])){
					$data['CommissionInfo']['construction_price_tax_include']
						= round($construction_price_tax_exclude * (1 + $tax_rate['MTaxRate']['tax_rate_val']));
				}else{
					$data['CommissionInfo']['construction_price_tax_include'] = $data['CommissionInfo']['construction_price_tax_exclude'];
				}

				// 控除金額(税抜)
				if(!empty($data['CommissionInfo']['deduction_tax_include'])){
					$data['CommissionInfo']['deduction_tax_exclude']
						= round($data['CommissionInfo']['deduction_tax_include'] / (1 + $tax_rate['MTaxRate']['tax_rate_val']));
				}else{
					$data['CommissionInfo']['deduction_tax_exclude'] = 0;
				}
			}else{
				$data['CommissionInfo']['construction_price_tax_include'] = $data['CommissionInfo']['construction_price_tax_exclude'];
				$data['CommissionInfo']['deduction_tax_exclude'] = $data['CommissionInfo']['deduction_tax_include'];
			}

			// 控除金額(税抜)
			if(empty($data['CommissionInfo']['deduction_tax_exclude']))
				$data['CommissionInfo']['deduction_tax_exclude'] = 0;

			// 手数料対象金額の計算
			if($construction_price_tax_exclude != 0){
				// 手数料対象金額 = 施工金額(税抜) - 控除金額(税抜)
				$data['BillInfo']['fee_target_price']
					= $construction_price_tax_exclude - $data['CommissionInfo']['deduction_tax_exclude'];
			}else{
				$data['BillInfo']['fee_target_price'] = 0;
			}

			// 保険料の計算
			if($data['MGenre']['insurant_flg'] == 1
					&& $data['AffiliationInfo']['liability_insurance'] == 2){
				// 保険料 = 施工金額(税抜) × 0.01
				$data['BillInfo']['insurance_price'] = round($construction_price_tax_exclude * 0.01);
			}else{
				$data['BillInfo']['insurance_price'] = 0;
			}
		}

		// 受注手数料の計算
		if(is_null($data['CommissionInfo']['order_fee_unit'])){

			// m_corp_categories.order_fee_unitを取得
			if(is_null($data['MCorpCategory']['order_fee_unit'])){
				// m_categories.category_default_fee_unitを取得
				$default_category = $this->MCategory->getDefault_fee($data['DemandInfo']['category_id']);
				$data['CommissionInfo']['order_fee_unit'] = $default_category['category_default_fee_unit'];
			}else{
				$data['CommissionInfo']['order_fee_unit'] = $data['MCorpCategory']['order_fee_unit'];
			}
		}

		if($data['CommissionInfo']['order_fee_unit'] != 0
				&& $data['CommissionInfo']['commission_status'] != Util::getDivValue('construction_status', 'introduction')){
			// 手数料単位が"円"以外、取次状況が"紹介済み"以外の場合

			// 確定手数料率の計算
			if(!empty($data['CommissionInfo']['irregular_fee_rate'])){
				// イレギュラー手数料率が設定されている場合
				$data['CommissionInfo']['confirmd_fee_rate'] = $data['CommissionInfo']['irregular_fee_rate'];
			}else{
				// murata.s ORANGE-488 CHG(S)
				if(empty($data['CommissionInfo']['confirmd_fee_rate'])){
					$data['CommissionInfo']['confirmd_fee_rate'] = $data['CommissionInfo']['commission_fee_rate'];
				}
				// murata.s ORANGE-488 CHG(E)
			}

			// 取次先手数料の計算
			if(!empty($data['CommissionInfo']['irregular_fee'])){
				// イレギュラー手数料が設定されている場合
				$data['BillInfo']['fee_tax_exclude'] = $data['CommissionInfo']['irregular_fee'];
			}else{
				// 取次先手数料 = 手数料対象金額 × 確定手数料率
				$data['BillInfo']['fee_tax_exclude'] = round($data['BillInfo']['fee_target_price'] * $data['CommissionInfo']['confirmd_fee_rate'] * 0.01);
			}
			if(!empty($data['BillInfo']['fee_tax_exclude']))
				$data['CommissionInfo']['corp_fee'] = $data['BillInfo']['fee_tax_exclude'];
		}else{
			// 手数料単位が"円"の場合
			if(!empty($data['CommissionInfo']['irregular_fee'])){
				// イレギュラー手数料が設定されている場合
				$data['BillInfo']['fee_tax_exclude'] = $data['CommissionInfo']['irregular_fee'];
			}else{
				$data['BillInfo']['fee_tax_exclude'] = $data['CommissionInfo']['corp_fee'];
			}

			// 取次状況が"紹介済み"の場合
			if($data['CommissionInfo']['commission_status'] == Util::getDivValue('construction_status','introduction')){
				$data['BillInfo']['fee_target_price'] = $data['BillInfo']['fee_tax_exclude'];
				if($data['CommissionInfo']['introduction_free'] == 1){
					$data['BillInfo']['fee_tax_exclude'] = 0;
				}
			}
		}

		// 消費税
		if(!empty($tax_rate['MTaxRate']['tax_rate_val'])){
			$data['BillInfo']['tax'] = round($data['BillInfo']['fee_tax_exclude'] * $tax_rate['MTaxRate']['tax_rate_val']);
		}else{
			$data['BillInfo']['tax'] = 0;
		}

		// 合計請求金額の計算
		$fee_tax_exclude = !empty($data['BillInfo']['fee_tax_exclude']) ? $data['BillInfo']['fee_tax_exclude'] : 0;
		$data['BillInfo']['total_bill_price']
			= $fee_tax_exclude + $data['BillInfo']['tax'] + $data['BillInfo']['insurance_price'];

		return $data;
	}

	/**
	 * 取次情報を取得する
	 *
	 * @param int $commission_id 取次ID
	 */
	private function __find_commission_info($commission_id){
		return  $this->CommissionInfo->find('first', array(
				'fields' => '*,
					DemandInfo.id, DemandInfo.category_id,
					MGenre.insurant_flg,
					BillInfo.id, BillInfo.irregular_fee_rate, BillInfo.irregular_fee, BillInfo.fee_target_price, BillInfo.fee_tax_exclude, BillInfo.total_bill_price, BillInfo.tax, BillInfo.insurance_price,
					MCorpCategory.id, MCorpCategory.order_fee, MCorpCategory.order_fee_unit,
					AffiliationInfo.liability_insurance',
				'joins' => array(
						array(
								'fields' => '*',
								'type' => 'inner',
								'table' => 'demand_infos',
								'alias' => 'DemandInfo',
								'conditions' => array('DemandInfo.id = CommissionInfo.demand_id')
						),
						array(
								'fields' => '*',
								'type' => 'inner',
								'table' => 'm_genres',
								'alias' => 'MGenre',
								'conditions' => array(
										'MGenre.id = DemandInfo.genre_id'
								)
						),
						array(
								'fields' => '*',
								'type' => 'left',
								'table' => 'bill_infos',
								'alias' => 'BillInfo',
								'conditions' => array(
										'BillInfo.demand_id = CommissionInfo.demand_id',
										'BillInfo.commission_id = CommissionInfo.id',
										'BillInfo.auction_id' => null
								)
						),
						array(
								'fields' => '*',
								'type' => 'left',
								'table' => 'm_corp_categories',
								'alias' => 'MCorpCategory',
								'conditions' => array(
										'MCorpCategory.corp_id = MCorp.id',
										'MCorpCategory.category_id = DemandInfo.category_id'
								)
						),
						array(
								'fields' => '*',
								'type' => 'inner',
								'table' => 'affiliation_infos',
								'alias' => 'AffiliationInfo',
								'conditions' => array(
										'AffiliationInfo.corp_id = CommissionInfo.corp_id'
								)
						)
				),
				'conditions' => array(
						'CommissionInfo.id' => $commission_id
				)
		));

	}

	/**
	 * 消費税率取得
	 *
	 * @param string $date 基準日
	 */
	private function __set_m_tax_rate($date = null){

		if(empty($date))
			$date = date('Y-m-d');

		if(!empty($date)){
			$results = $this->MTaxRate->find('first', array(
					'conditions' => array(
							'start_date <=' => $date,
							'or' => array(
									'end_date' => '',
									'end_date >=' => $date
							)
					)
			));
			$results['MTaxRate']['tax_rate_val'] = $results['MTaxRate']['tax_rate'];
			$results['MTaxRate']['tax_rate'] = $results['MTaxRate']['tax_rate']*100;


		}else{
			$results = array(
					'MTaxRate' => array(
							'tax_rate' => '',
							'tax_rate_val' => ''
					)
			);
		}

		return $results;
	}

}
