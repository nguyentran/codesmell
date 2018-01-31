<?php
App::uses('AppShell', 'Console/Command');
App::uses('ComponentCollection', 'Controller');
App::uses('AuctionInfoUtilComponent', 'Controller/Component');

class AutomaticAuctionSelectionTask extends AppShell{

	public $uses = array(
			'DemandInfo',
			'CommissionInfo',
			'MCorp',
			'MCategory',
			'MCorpCategory',
			'AuctionInfo',
			'AffiliationInfo',
// 2016.12.08 murata.s ORANGE-250 ADD(S)
			'VisitTime',
			'MPost'
// 2016.12.08 murata.s ORANGE-250 ADD(E)
	);

	private static $user = 'system';

	//ORANGE-259 CHG S
	/**
	 * 自動選定(入札式選定自動)を行う
	 * @param unknown $data
	 */
	public function execute($data){
		return $this->__get_auction_commission_list($data);
	}
	//ORANGE-259 CHG E

// 2016.12.08 murata.s ORANGE-250 CHG(S)
// 	/**
// 	 * オークション情報から取次情報を作成する
// 	 *
// 	 * @param unknown $data
// 	 */
// 	private function __get_auction_commission_list($data){
// 		$commission_infos = array();
// 		$auction_infos = $this->__get_auction_infos($data);
//
// // 2016.11.29 murata.s ORANGE-259 ADD(S)
// 		$corresponding_contens = '';
// 		$corresponding_contens2 = '';
// // 2016.11.29 murata.s ORANGE-259 ADD(E)
//
// 		foreach($auction_infos as $key=>$auction_info){
//
// 			//ORANGE-259 ADD S
// 			if ($data['DemandInfo']['selection_system'] == Util::getDivValue('selection_type', 'AuctionSelection')
// 					&& $auction_info['AuctionInfo']['refusal_flg'] !=1){
// 				continue;
// 			}
// 			//ORANGE-259 ADD E
//
// 			if(empty($auction_info['MCorpCategory']) || !isset($auction_info['MCorpCategory']['order_fee_unit'])){
// 				$category = $this->MCategory->find('first', array(
// 						'fields' => 'category_default_fee, category_default_fee_unit',
// 						'conditions' => array('id' => $auction_info['DemandInfo']['category_id'])
// 				));
//
// 				if(!$category){
// 					// m_categoriesからも取得できなかった場合、空白とする。
// 					$category['MCorpCategory'] = array(
// 							'order_fee' => '',
// 							'order_fee_unit' => ''
// 					);
// 				}
//
// 				$auction_info['MCorpCategory']['order_fee'] = $category['MCategory']['category_default_fee'];
// 				$auction_info['MCorpCategory']['order_fee_unit'] = $category['MCategory']['category_default_fee_unit'];
// 			}
//
// // 2016.11.29 murata.s ORANGE-259 CHG(S)
// 			// commission_infosを設定
// 			$commission_infos['CommissionInfo'][$key] = array(
// 					'demand_id' => $data['DemandInfo']['id'],
// 					'corp_id' => $auction_info['AuctionInfo']['corp_id'],
// 					'commit_flg' => 0,
// 					'commission_type' => Util::getDivValue('commission_type', 'normal_commission'),
// 					'lost_flg' => $auction_info['AuctionInfo']['refusal_flg'],
// 					'commission_status' => 1,
// 					'unit_price_exclude' => 0,
// 					'corp_fee' => $auction_info['MCorpCategory']['order_fee_unit'] == 0 ? $auction_info['MCorpCategory']['order_fee'] : '',
// 					'commission_fee_rate' => $auction_info['MCorpCategory']['order_fee_unit'] == 1 ? $auction_info['MCorpCategory']['order_fee'] : '',
// 					'business_trip_amount' => !empty($auction_info['DemandInfo']['business_trip_amount'])
// 													? $auction_info['DemandInfo']['business_trip_amount'] : 0,
// 					'select_commission_unit_price_rank' => !empty($auction_info['AffiliationAreaStat']['commission_unit_price_rank'])
// 													? $auction_info['AffiliationAreaStat']['commission_unit_price_rank'] : '',
// 					'select_commission_unit_price' => !empty($auction_info['AffiliationAreaStat']['commission_unit_price_category'])
// 													? $auction_info['AffiliationAreaStat']['commission_unit_price_category'] : '',
// 					'modified_user_id' => self::$user,
// 					'modified' => date('Y-m-d H:i:s'),
// 					'created_user_id' => self::$user,
// 					'created' => date('Y-m-d H:i:s'),
//
// 			);
//
// 			//選定方式　自動 OR 手動
// 			if (!empty($data['DemandInfo']['selection_system']) && $data['DemandInfo']['selection_system'] == Util::getDivValue('selection_type', 'AuctionSelection'))$corresponding_contens .= '入札流れ 手動選定 ';
// 			else $corresponding_contens .= '入札流れ 自動選定 ';
//
// 			//企業名追加
// 			$corresponding_contens .= '['.$auction_info['MCorp']['official_corp_name'].'] ';
//
// 			//辞退理由
// 			if(!empty($auction_info['Refusal']['corresponds_time1']) || !empty($auction_info['Refusal']['corresponds_time2']) || !empty($auction_info['Refusal']['corresponds_time3'])){
// 				$corresponding_contens .= '【辞退】対応時間が合わず ';
// 				if(!empty($auction_info['Refusal']['corresponds_time1']))$corresponding_contens .= $this->__dateTimeFormat($auction_info['Refusal']['corresponds_time1']);
// 				if(!empty($auction_info['Refusal']['corresponds_time2']))$corresponding_contens .= $this->__dateTimeFormat($auction_info['Refusal']['corresponds_time2']);
// 				if(!empty($auction_info['Refusal']['corresponds_time3']))$corresponding_contens .= $this->__dateTimeFormat($auction_info['Refusal']['corresponds_time3']);
//
// 			}
// 			if(!empty($auction_info['Refusal']['cost_from']) || !empty($auction_info['Refusal']['cost_to'])){
// 				$corresponding_contens .= '【辞退】価格が合わず '.$this->__yenFormat2($auction_info['Refusal']['cost_from']).'～'.$this->__yenFormat2($auction_info['Refusal']['cost_to']);
// 			}
// 			if(!empty($auction_info['Refusal']['other_contens'])){
// 				$corresponding_contens .= '【辞退】その他 '.$auction_info['Refusal']['other_contens'];
// 			}
// 			if(!empty($corresponding_contens))
// 				$corresponding_contens .= "\n";
//
// // 2016.12.08 murata.s ORANGE-185 DEL(S)
// // 			if (!empty($data['DemandInfo']['selection_system'])
// // 					&& $data['DemandInfo']['selection_system'] == Util::getDivValue('selection_type', 'AutomaticAuctionSelection')){
// //
// // 				$corresponding_contens2 .= '自動選定 ';
// // 				$corresponding_contens2 .= '['.$auction_info['MCorp']['official_corp_name']."]\n";
// // 			}
// // 2016.12.08 murata.s ORANGE-185 DEL(E)
// 		}
//
// 		if(!empty($corresponding_contens))
// 			$commission_infos['corresponding_contens'][0] = $corresponding_contens;
//
// 		if(!empty($corresponding_contens2))
// 			$commission_infos['corresponding_contens'][1] = $corresponding_contens2;
//
// // 2016.11.29 murata.s ORANGE-259 CHG(E)
// 		return $commission_infos;
// 	}

	/**
	 * オークション情報から取次情報を作成する
	 *
	 * @param unknown $data
	 */
	private function __get_auction_commission_list($data){

		$commission_infos = array();
		// 入札流れ用履歴
		$corresponding_contens = '';
		// 自動選定用履歴
		$corresponding_contens2 = '';

		// 登録済みオークション情報を取得する
		$auction_infos = $this->__get_auction_infos($data);

// 2016.12.09 ADD(S)
		// 登録済み取次情報を取得する
		$registed_commission = $this->CommissionInfo->find('all', array(
				'conditions' => array('demand_id' => $data['DemandInfo']['id'])
		));
// 2016.12.09 ADD(E)

		$results = array();
		foreach($auction_infos as $v){
			if ($data['DemandInfo']['selection_system'] == Util::getDivValue('selection_type', 'AuctionSelection')
					&& $v['AuctionInfo']['refusal_flg'] !=1){
				continue;
			}

			//選定方式　自動 OR 手動
			if (!empty($data['DemandInfo']['selection_system'])
					&& $data['DemandInfo']['selection_system'] == Util::getDivValue('selection_type', 'AuctionSelection'))
						$corresponding_contens .= '入札流れ 手動選定 ';
			else
				$corresponding_contens .= '入札流れ 自動選定 ';

			//企業名追加
			// murata.s ORANGE-475 CHG(S)
			$corresponding_contens .= '['.$v['MCorp']['corp_name'].'] ';
			// murata.s ORANGE-475 CHG(E)
// murata.s ORANGE-539 CHG(S)
			//辞退理由
			if($v['AuctionInfo']['refusal_flg'] == 1){
				$corresponding_contens .= "＜辞退＞\n";
			}else{
				$corresponding_contens .= "\n";
			}
			if(!empty($v['Refusal']['corresponds_time1']) || !empty($v['Refusal']['corresponds_time2']) || !empty($v['Refusal']['corresponds_time3'])){
				$corresponding_contens .= '  ■対応時間が合わず ';
				if(!empty($v['Refusal']['corresponds_time1']))$corresponding_contens .= $this->__dateTimeFormat($v['Refusal']['corresponds_time1']);
				if(!empty($v['Refusal']['corresponds_time2']))$corresponding_contens .= $this->__dateTimeFormat($v['Refusal']['corresponds_time2']);
				if(!empty($v['Refusal']['corresponds_time3']))$corresponding_contens .= $this->__dateTimeFormat($v['Refusal']['corresponds_time3']);
				$corresponding_contens .= "\n";
			}
			if(!empty($v['Refusal']['cost_from']) || !empty($v['Refusal']['cost_to'])){
				$corresponding_contens .= '  ■価格が合わず '.$this->__yenFormat2($v['Refusal']['cost_from']).'～'.$this->__yenFormat2($v['Refusal']['cost_to']);
				$corresponding_contens .= "\n";
			}
			if(!empty($v['Refusal']['estimable_time_from'])){
				$corresponding_contens .= '  ■見積もり日程が対応不可 '.$this->__dateTimeFormat($v['Refusal']['estimable_time_from']);
				$corresponding_contens .= "\n";
			}
			if(!empty($v['Refusal']['contactable_time_from'])){
				$corresponding_contens .= '  ■連絡希望日時が対応不可 '.$this->__dateTimeFormat($v['Refusal']['contactable_time_from']);
				$corresponding_contens .= "\n";
			}
			if(!empty($v['Refusal']['other_contens'])){
				$corresponding_contens .= '  ■その他対応不可理由 '.$v['Refusal']['other_contens'];
				$corresponding_contens .= "\n";
			}
// murata.s ORANGE-539 CHG(E)

			// すでに登録済みの取次先は登録しない
			if(!empty($commission_infos['CommissionInfo'])){
				$has_commission = array_filter($commission_infos['CommissionInfo'], function($item) use($v){
					return $item['corp_id'] == $v['AuctionInfo']['corp_id'];
				});
			}
// 2016.12.09 ADD(S)
			if(!empty($registed_commission)){
				$has_commission_db = array_filter($registed_commission, function($item) use($v){
					return $item['CommissionInfo']['corp_id'] == $v['AuctionInfo']['corp_id'];
				});
			}
// 2016.12.09 ADD(E)
// murata.s ORANGE-261 CHG(S)
			if(empty($has_commission)
					&& empty($has_commission_db)
					&& $v['AuctionInfo']['refusal_flg'] == 1){
				if($v['MCorpCategory']['corp_commission_type'] != 2){
					// 成約ベース
					$order_fee = $v['MCorpCategory']['order_fee'];
					$order_fee_unit = $v['MCorpCategory']['order_fee_unit'];
					$commission_type = Util::getDivValue('commission_type', 'normal_commission');
					$commission_status = Util::getDivValue('construction_status','progression');
				}else{
					// 紹介ベース
					$order_fee = $v['MCorpCategory']['introduce_fee'];
					//$order_fee_unit = $v['MCorpCategory']['order_fee_unit'];
					$order_fee_unit = 0;
					$commission_type = Util::getDivValue('commission_type', 'package_estimate');
					$commission_status = Util::getDivValue('construction_status','introduction');
				}

				$commission_infos['CommissionInfo'][] = array(
						'demand_id' => $data['DemandInfo']['id'],
						'corp_id' => $v['AuctionInfo']['corp_id'],
						'commit_flg' => 0,
						'commission_type' => $commission_type,
						'lost_flg' => $v['AuctionInfo']['refusal_flg'],
						'commission_status' => $commission_status,
						'unit_price_exclude' => 0,
						'corp_fee' => $order_fee_unit == 0 ? $order_fee : '',
						'commission_fee_rate' => $v['MCorpCategory']['corp_commission_type'] != 2
								? ($order_fee_unit == 1 ? $order_fee : '') : 100,
						'business_trip_amount' => !empty($v['DemandInfo']['business_trip_amount'])
							? $v['DemandInfo']['business_trip_amount'] : 0,
						'select_commission_unit_price_rank' => !empty($v['AffiliationAreaStat']['commission_unit_price_rank'])
							? $v['AffiliationAreaStat']['commission_unit_price_rank'] : '',
						'select_commission_unit_price' => !empty($v['AffiliationAreaStat']['commission_unit_price_category'])
							? $v['AffiliationAreaStat']['commission_unit_price_category'] : '',
// 2017.01.04 murata.s ORANGE-244 ADD(S)
						'order_fee_unit' => $order_fee_unit,
// 2017.01.04 murata.s ORANGE-244 ADD(E)
						'modified_user_id' => self::$user,
						'modified' => date('Y-m-d H:i:s'),
						'created_user_id' => self::$user,
						'created' => date('Y-m-d H:i:s'),

						// ソート用
						'sort_push_time' => strtotime($v['AuctionInfo']['push_time']),
						'sort_commission_unit_price_category' => $v['AffiliationAreaStat']['commission_unit_price_category'],
						'sort_commission_count_category' => $v['AffiliationAreaStat']['commission_count_category'],
				);
			}
// murata.s ORANGE-261 CHG(E)
		}

		if(!empty($data['DemandInfo']['selection_system'])
				&& $data['DemandInfo']['selection_system'] == Util::getDivValue('selection_type', 'AutomaticAuctionSelection')){
			// 自動選定用オークション情報を取得する
			$demand_info = $this->__get_demand($data['DemandInfo']['id']);
			$auto_auction_infos = $this->__get_auto_commission($demand_info);

			$default_fee = $this->MCategory->getDefault_fee($demand_info['DemandInfo']['category_id']);

			foreach($auto_auction_infos['AuctionInfo'] as $v){
				// すでに登録済みの取次先は登録しない
				if(!empty($commission_infos['CommissionInfo'])){
					$has_commission = array_filter($commission_infos['CommissionInfo'], function($item) use($v){
						return $item['corp_id'] == $v['MCorp']['id'];
					});
				}

// 2016.12.09 ADD(S)
				// すでにDBに登録済みの場合は登録しない
				if(!empty($registed_commission)){
					$has_commission_db = array_filter($registed_commission, function($item) use($v){
						return $item['CommissionInfo']['corp_id'] == $v['MCorp']['id'];
					});
				}
// 2016.12.09 ADD(E)

// murata.s ORANGE-261 CHG(S)
				if(empty($has_commission) && empty($has_commission_db)){

					if($v['MCorpCategory']['corp_commission_type'] != 2){
						// 成約ベース
						$order_fee = $v['MCorpCategory']['order_fee'];
						$order_fee_unit = $v['MCorpCategory']['order_fee_unit'];
						$commission_type = Util::getDivValue('commission_type', 'normal_commission');
						$commission_status = Util::getDivValue('construction_status','progression');
					}else{
						// 紹介ベース
						$order_fee = $v['MCorpCategory']['introduce_fee'];
						//$order_fee_unit = $v['MCorpCategory']['order_fee_unit'];
						$order_fee_unit = 0;
						$commission_type = Util::getDivValue('commission_type', 'package_estimate');
						$commission_status = Util::getDivValue('construction_status','introduction');
					}

					$order_fee = !empty($order_fee) ? $order_fee : $default_fee['category_default_fee'];
					$order_fee_unit = (!empty($v['MCorpCategory']['order_fee']) || !empty($v['MCorpCategory']['introduce_fee']))
						? $order_fee_unit : $default_fee['category_default_fee_unit'];

					$commission_infos['CommissionInfo'][] = array(
							'demand_id' => $data['DemandInfo']['id'],
							'corp_id' => $v['MCorp']['id'],
							'commit_flg' => 0,
							'commission_type' => $commission_type,
							'lost_flg' => 0,
							'commission_status' => $commission_status,
							'unit_price_exclude' => 0,
							'corp_fee' => $order_fee_unit == 0 ? $order_fee : null,
							'commission_fee_rate' => $order_fee_unit == 0 ? null : $order_fee,
							'business_trip_amount' => !empty($demand_info['DemandInfo']['business_trip_amount'])
								? $demand_info['DemandInfo']['business_trip_amount'] : 0,
							'select_commission_unit_price_rank' => $v['AffiliationAreaStat']['commission_unit_price_rank'],
							'select_commission_unit_price' => $v['AffiliationAreaStat']['commission_unit_price_category'],
// 2017.01.04 murata.s ORANGE-244 ADD(S)
							'order_fee_unit' => $order_fee_unit,
// 2017.01.04 murata.s ORANGE-244 ADD(E)
							'modified_user_id' => self::$user,
							'modified' => date('Y-m-d H:i:s'),
							'created_user_id' => self::$user,
							'created' => date('Y-m-d H:i:s'),

							// ソート用
							'sort_push_time' => strtotime($v['push_time']),
							'sort_commission_unit_price_category' => $v['AffiliationAreaStat']['commission_unit_price_category'],
							'sort_commission_count_category' => $v['AffiliationAreaStat']['commission_count_category'],
					);
				}
// murata.s ORANGE-261 CHG(E)

				$corresponding_contens2 .= '自動選定 ';
				$corp = $this->MCorp->findById($v['MCorp']['id']);
				// murata.s ORANGE-475 CHG(S)
				$corresponding_contens2 .= '['.$corp['MCorp']['corp_name']."]\n";
				// murata.s ORANGE-475 CHG(E)
			}
		}

		// 入札辞退と自動選定を合わせてソートを実施
		if(!empty($commission_infos['CommissionInfo'])){
			uasort($commission_infos['CommissionInfo'], function($a, $b){
				if($a['sort_push_time'] != $b['sort_push_time']){
					// AuctionInfo.push_time asc
					return $a['sort_push_time'] < $b['sort_push_time'] ? -1 : 1;
				}else if($a['sort_commission_unit_price_category'] != $b['sort_commission_unit_price_category']){
					// AffiliationAreaStat.commission_unit_price_category IS NULL
					if(empty($a['sort_commission_unit_price_category']) && !empty($b['sort_commission_unit_price_category']))
						return 1;
					else if(!empty($a['sort_commission_unit_price_category']) && empty($b['sort_commission_unit_price_category']))
						return -1;
					else
						// AffiliationAreaStat.commission_unit_price_category desc
						return $a['sort_commission_unit_price_category'] > $b['sort_commission_unit_price_category'] ? -1 : 1;
				}else if($a['sort_commission_count_category'] != $b['sort_commission_count_category']){
					// AffiliationAreaStat.commission_count_category desc
					return $a['sort_commission_count_category'] > $b['sort_commission_count_category'] ? -1 : 1;
				}else{
					return 0;
				}
			});
		}
		if(!empty($corresponding_contens))
			$commission_infos['corresponding_contens'][0] = $corresponding_contens;

		if(!empty($corresponding_contens2))
			$commission_infos['corresponding_contens'][1] = $corresponding_contens2;

		return $commission_infos;
	}
// 2016.12.08 murata.s ORANGE-250 CHG(E)

	/**
	 * オークション情報の取得
	 *
	 * @param unknown $data
	 */
	private function __get_auction_infos($data){

		$conditions = array('AuctionInfo.demand_id' => $data['DemandInfo']['id']);

		return $this->AuctionInfo->find('all', array(
				'fields' => array(
								'*',
								'DemandInfo.category_id',
								'DemandInfo.business_trip_amount',
								'MCorpCategory.order_fee_unit',
								'MCorpCategory.order_fee',
								// murata.s ORANGE-261 ADD(S)
								'MCorpCategory.introduce_fee',
								'MCorpCategory.corp_commission_type',
								// murata.s ORANGE-261 ADD(E)
								'AffiliationAreaStat.commission_unit_price_category',
								'AffiliationAreaStat.commission_count_category',
								'AffiliationAreaStat.commission_unit_price_rank',
								'(SELECT m_genres.targer_commission_unit_price FROM m_genres WHERE m_genres.id = "MCorpCategory"."genre_id") AS "targer_commission_unit_price"',
								//ORANGE-259 ADD S
								'MCorp.official_corp_name',
								// murata.s ORANGE-475 ADD(S)
								'MCorp.corp_name',
								// murata.s ORANGE-475 ADD(E)
								'Refusal.corresponds_time1',
								'Refusal.corresponds_time2',
								'Refusal.corresponds_time3',
								'Refusal.cost_from',
								'Refusal.cost_to',
								'Refusal.other_contens',
								//ORANGE-259 ADD E
								// murata.s ORANGE-539 ADD(S)
								'Refusal.estimable_time_from',
								'Refusal.contactable_time_from',
								// murata.s ORANGE-539 ADD(E)
						),
				'joins' => array(
						array(
								'fields' => '*',
								'type' => 'inner',
								'table' => 'affiliation_infos',
								'alias' => 'AffiliationInfo',
								'conditions' => array(
										'AffiliationInfo.corp_id = AuctionInfo.corp_id'
								)
						),
						array(
								'fields' => '*',
								'type' => 'inner',
								'table' => 'demand_infos',
								'alias' => 'DemandInfo',
								'conditions' => array(
										'DemandInfo.id = AuctionInfo.demand_id'
								)
						),
						array(
								'fields' => '*',
								'type' => 'inner',
								'table' => 'm_corp_categories',
								'alias' => 'MCorpCategory',
								'conditions' => array(
										'MCorpCategory.corp_id = AuctionInfo.corp_id',
										'MCorpCategory.category_id = DemandInfo.category_id'
								)
						),
						array(
								'fields' => '*',
								'type' => 'inner',
								'table' => 'affiliation_area_stats',
								'alias' => 'AffiliationAreaStat',
								'conditions' => array(
										'AffiliationAreaStat.corp_id = AuctionInfo.corp_id',
										'AffiliationAreaStat.genre_id = MCorpCategory.genre_id',
										'AffiliationAreaStat.prefecture = DemandInfo.address1'
								)
						),
						//ORANGE-259 ADD S
						array(
								'fields' => '*',
								'type' => 'inner',
								'table' => 'm_corps',
								'alias' => 'MCorp',
								'conditions' => array(
										'MCorp.id = AuctionInfo.corp_id',
								)
						),
						array(
								'fields' => '*',
								'type' => 'left',
								'table' => 'refusals',
								'alias' => 'Refusal',
								'conditions' => array(
										'Refusal.auction_id = AuctionInfo.id',
								)
						),
						//ORANGE-259 ADD E
				),
				'conditions' => $conditions,
				'order' => array(
						'AuctionInfo.push_time',
						'AffiliationAreaStat.commission_unit_price_category IS NULL',
						'AffiliationAreaStat.commission_unit_price_category DESC',
						'AffiliationAreaStat.commission_count_category DESC'
				)

		));
	}

// 2016.12.08 murata.s ORANGE-250 ADD(S)
	/**
	 * 自動選定用オークション情報を取得する
	 *
	 * @param unknown $demand_id
	 */
	private  function __get_auto_commission($demand_info){

		$collection = new ComponentCollection();
		$auctionInfoUtil = new AuctionInfoUtilComponent($collection);
		$auctionInfoUtil->startup4shell();

		return $auctionInfoUtil->get_auctionInfo_for_autoCommission($demand_info['DemandInfo']['id'], $demand_info);
	}

	/**
	 * 案件データの取得
	 *
	 * @param int $demand_id 案件ID
	 */
	private function __get_demand($demand_id){

		// 案件データの取得
		$this->DemandInfo->recursive = 2;
		$results = $this->DemandInfo->findById($demand_id);

		// 訪問日時の取得
		$data = $this->VisitTime->find ( 'all', array (
				'fields' => '*, AuctionInfo.id',
				'joins' => array (
						array (
								'fields' => '*',
								'type' => 'left',
								"table" => "auction_infos",
								"alias" => "AuctionInfo",
								"conditions" => array (
										'VisitTime.id = AuctionInfo.visit_time_id'
								)
						)
				),
				'conditions' => array('VisitTime.demand_id' => $demand_id),
				'order' => array (
						'VisitTime.visit_time' => 'asc'
				)
		));
		foreach ($data as $key => $value){
			$results['VisitTime'][$key]['id'] = $value['VisitTime']['id'];
			$results['VisitTime'][$key]['visit_time'] = $value['VisitTime']['visit_time'];
			$results['VisitTime'][$key]['is_visit_time_range_flg'] = $value['VisitTime']['is_visit_time_range_flg'];
			$results['VisitTime'][$key]['visit_time_from'] = $value['VisitTime']['visit_time_from'];
			$results['VisitTime'][$key]['visit_time_to'] = $value['VisitTime']['visit_time_to'];
			$results['VisitTime'][$key]['visit_adjust_time'] = $value['VisitTime']['visit_adjust_time'];
			$results['VisitTime'][$key]['visit_time_before'] = ($value['VisitTime']['is_visit_time_range_flg'] == 0) ? $value['VisitTime']['visit_time'] : $value['VisitTime']['visit_time_from'];
			$results['VisitTime'][$key]['commit_flg'] = !empty($value['AuctionInfo']['id'])? 1 : 0 ;
		}

		return $results;
	}
// 2016.12.08 murata.s ORANGE-250 ADD(E)

	private function __dateTimeFormat($date, $format = 'Y/m/d H:i'){
		if(empty($date)) return "";


		try{
			$wk = new DateTime($date);
		}catch (Exception $e) {
			return $date;
		}

		$error = DateTime::getLastErrors();

		if ($error['warning_count'] != 0 || $error['error_count'] != 0) {
			return $date;
		}

		$d = date_create($date);

		return date_format($d, $format);
	}

	private function __yenFormat2($amount){
		if(is_numeric($amount)){
			return number_format($amount).__('en', true);
		} else {
			return '0'.__('en', true);
		}
	}
}
