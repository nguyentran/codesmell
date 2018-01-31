<?php

class AuctionInfoUtilComponent extends Component{
	public $controller;

	public function startup($controller){
		$this->controller = $controller;

		$this->AuctionInfo = $this->controller->AuctionInfo;
		$this->CommissionInfo = $this->controller->CommissionInfo;
		$this->MCategory = $this->controller->MCategory;
		$this->MCorp = $this->controller->MCorp;
		$this->MPost = $this->controller->MPost;

	}

	public function startup4shell(){
		App::import('Model', 'AuctionInfo');
		App::import('Model', 'CommissionInfo');
		App::import('Model', 'MCategory');
		App::import('Model', 'MCorp');
		App::import('Model', 'MPost');

		$this->AuctionInfo = new AuctionInfo();
		$this->CommissionInfo = new CommissionInfo();
		$this->MCategory = new MCategory();
		$this->MCorp = new MCorp();
		$this->MPost = new MPost();

	}

	/**
	 * 自動選定用のオークション情報を取得
	 *
	 * @param unknown $data 案件情報
	 */
	public function get_auctionInfo_for_autoCommission($demand_id, $data){
		// 訪問日時、連絡希望日時 を取得
		$visit_time_list = array();

		if(!empty($data['VisitTime'])){
			foreach ($data['VisitTime'] as $val){
				if ($val['is_visit_time_range_flg'] == 0 && strlen($val['visit_time']) > 0)
					$visit_time_list[] = $val['visit_time'];
				if ($val['is_visit_time_range_flg'] == 1 && strlen($val['visit_time_from']) > 0)
					$visit_time_list[] = $val['visit_time_from'];
			}
		}

		if(!empty($visit_time_list)){
			// 訪問日時を使用する場合
			$preferred_date = Util::getMinVisitTime($visit_time_list);
			$data['DemandInfo']['method'] = 'visit';
		} else {
			// 連絡希望日時を使用する場合
			if ($data['DemandInfo']['is_contact_time_range_flg'] == 0)
				$preferred_date = $data['DemandInfo']['contact_desired_time'];
			if ($data['DemandInfo']['is_contact_time_range_flg'] == 1)
				$preferred_date = $data['DemandInfo']['contact_desired_time_from'];
			$data['DemandInfo']['method'] = 'tel';
		}

		// メール送信時間を取得のため
		// 一時的にDemandInfo.auction_start_time、DemandInfo.auction_deadline_timeを設定
		if(empty($data['DemandInfo']['auction_start_time']))
			$data['DemandInfo']['auction_start_time'] = date('Y-m-d H:i:s');
		if(empty($data['DemandInfo']['auction_deadline_time']))
			$data['DemandInfo']['auction_deadline_time'] = '';
		$priority = $data['DemandInfo']['priority'];

		// 優先度が未設定の場合
		if(empty($data['DemandInfo']['priority'])){
			$judge_result = Util::judgeAuction($data['DemandInfo']['auction_start_time'], $preferred_date, $data['DemandInfo']['genre_id'], $data['DemandInfo']['address1'], $data['DemandInfo']['auction_deadline_time'], $priority);
		}
		// 優先度が大至急の場合
		else if($data['DemandInfo']['priority'] == Util::getDivValue('priority', 'asap')){
			$judge_result = Util::judgeAsap($data['DemandInfo']['auction_start_time'], $preferred_date, $data['DemandInfo']['genre_id'], $data['DemandInfo']['address1'], $data['DemandInfo']['auction_deadline_time'], $priority);
		}
		// 優先度が至急の場合
		else if($data['DemandInfo']['priority'] == Util::getDivValue('priority', 'immediately')){
			$judge_result = Util::judgeImmediately($data['DemandInfo']['auction_start_time'], $preferred_date, $data['DemandInfo']['genre_id'], $data['DemandInfo']['address1'], $data['DemandInfo']['auction_deadline_time'], $priority);
		}
		// 優先度が通常の場合
		else {
			$judge_result = Util::judgeNormal($data['DemandInfo']['auction_start_time'], $preferred_date, $data['DemandInfo']['genre_id'], $data['DemandInfo']['address1'], $data['DemandInfo']['auction_deadline_time'], $priority);
		}
		if ($judge_result['result_flg'] == 1){
			// オークション開始日時
			if(!empty($judge_result['result_date']))
				$data['DemandInfo']['auction_start_time'] = $judge_result['result_date'];
			//優先度が変更された場合は優先度毎の判定を再度行う
			$judge_result = Util::judgeAuction($data['DemandInfo']['auction_start_time'], $preferred_date, $data['DemandInfo']['genre_id'], $data['DemandInfo']['address1'], $data['DemandInfo']['auction_deadline_time'], $priority);
		}
		$auto_commissions = $this->update_auction_infos($demand_id, $data, true);

// 		// 一時的に設定したDemandInfo.auction_start_time、DemandInfo.auction_deadline_timeを削除
// 		unset($data['DemandInfo']['auction_start_time']);
// 		unset($data['DemandInfo']['auction_deadline_time']);

		// ソートを実行
		if(!empty($auto_commissions['AuctionInfo'])){
			uasort($auto_commissions['AuctionInfo'], function($a, $b){
				if($a['push_time'] != $b['push_time']){
					// AuctionInfo.push_time asc
					return $a['push_time'] < $b['push_time'] ? -1 : 1;
				}else if($a['AffiliationAreaStat']['commission_unit_price_category'] != $b['AffiliationAreaStat']['commission_unit_price_category']){
					// AffiliationAreaStat.commission_unit_price_category IS NULL
					if(empty($a['AffiliationAreaStat']['commission_unit_price_category']) && !empty($b['AffiliationAreaStat']['commission_unit_price_category']))
						return 1;
					else if(!empty($a['AffiliationAreaStat']['commission_unit_price_category']) && empty($b['AffiliationAreaStat']['commission_unit_price_category']))
						return -1;
					else
						// AffiliationAreaStat.commission_unit_price_category desc
						return $a['AffiliationAreaStat']['commission_unit_price_category'] > $b['AffiliationAreaStat']['commission_unit_price_category'] ? -1 : 1;
				}else if($a['AffiliationAreaStat']['commission_count_category'] != $b['AffiliationAreaStat']['commission_count_category']){
					// AffiliationAreaStat.commission_count_category desc
					return $a['AffiliationAreaStat']['commission_count_category'] > $b['AffiliationAreaStat']['commission_count_category'] ? -1 : 1;
				}else{
					return 0;
				}
			});
		}
		return $auto_commissions;

	}

	/**
	 * オークション情報を更新、または取得する
	 *
	 * @param array $data
	 * @param string $auto_selection 自動選定かどうか
	 *
	 * @return 更新結果、またはオークション情報($auto_selection = trueの場合)
	 */
	public function update_auction_infos($demand_id, $data, $auto_selection = false){
                //2017/07/11  ichino ORANGE-420 ADD start 自動取次した場合は、オークション選定しない
		if((!empty($data['DemandInfo']['do_auction']) || $auto_selection) && $data['DemandInfo']['do_auto_selection_category'] == 0){
                //2017/07/11  ichino ORANGE-420 ADD end
			// ランク毎送信時刻取得
			$rank_time = array();
			if($data['DemandInfo']['method'] == 'visit'){
				$rank_time = Util::getPushSendTimeOfVisitTime($data['DemandInfo']['auction_start_time'], $data['DemandInfo']['auction_deadline_time'], $data['DemandInfo']['genre_id'], $data['DemandInfo']['address1']);
			} else {
				$rank_time = Util::getPushSendTimeOfContactDesiredTime($data['DemandInfo']['auction_start_time'], $data['DemandInfo']['auction_deadline_time'], $data['DemandInfo']['genre_id'], $data['DemandInfo']['address1']);
			}

			// 案件にあたる企業リストの取得
			$address2 = $data['DemandInfo']['address2'];
			// ORANGE-1072 市区町村に含まれる文字を大文字・小文字を検索対象にする(m_posts.address2)
			$upperAddress2 = $address2;
			$upperAddress2 = str_replace('ヶ', 'ケ', $upperAddress2);
			$upperAddress2 = str_replace('ﾉ', 'ノ', $upperAddress2);
			$upperAddress2 = str_replace('ﾂ', 'ツ', $upperAddress2);
			$lowerAddress2 = $address2;
			$lowerAddress2 = str_replace('ケ', 'ヶ', $lowerAddress2);
			$lowerAddress2 = str_replace('ノ', 'ﾉ', $lowerAddress2);
			$lowerAddress2 = str_replace('ツ', 'ﾂ', $lowerAddress2);

			$conditions = array(
					'MPost.address1' => Util::getDivTextJP('prefecture_div', $data ['DemandInfo'] ['address1']),
					array(
							'OR' => array(
									array('MPost.address2' => $upperAddress2),
									array('MPost.address2' => $lowerAddress2),
							)
					)
			);

			$results = $this->MPost->find('first',
					array( 'fields' => 'MPost.jis_cd',
							'conditions' => $conditions,
							'group' => array('MPost.jis_cd'),
					)
			);

			if (empty($results["MPost"]["jis_cd"])) {
				// 存在しないエリアの場合、オークションできないのでここで抜ける。
				// すでに対象0件として別処理で手動選定になるようにしている。
				return true;
			}

			$jis_cd = $results["MPost"]["jis_cd"];

			if(!empty($jis_cd)){

				$conditions = array();
				$conditions['MCorp.affiliation_status'] = 1;
				$conditions['coalesce(MCorp.corp_commission_status, 0) not in'] = array(1, 2, 4, 5);
				$conditions['AffiliationSubs.affiliation_id is'] = NULL;
				$conditions['AffiliationSubs.item_id is'] = NULL;
				if( $data ['DemandInfo'] ['site_id'] == 585 ) {
// 					// 生活救急車案件の場合、JBR対応状況が「対応不可」の場合、オークション対象外にする
// 					$conditions['coalesce(MCorp.jbr_available_status, 0) not in '] = array(3);
					$conditions['MCorp.jbr_available_status'] = 2;
				}

				$joins = array (
						array (
								'fields' => '*',
								'type' => 'inner',
								"table" => "m_corp_categories",
								"alias" => "MCorpCategory",
								"conditions" => array (
										"MCorpCategory.corp_id = MCorp.id",
										'MCorpCategory.genre_id =' . $data['DemandInfo']['genre_id'],
										// ジャンルまで一致でオークション対象とする 2015.09.22 n.kai 一旦取り消し
										'MCorpCategory.category_id =' . $data['DemandInfo']['category_id']
								)
						),
						array (
								'fields' => '*',
								'type' => 'inner',
								"table" => "m_target_areas",
								"alias" => "MTargetArea",
								"conditions" => array (
										"MTargetArea.corp_category_id = MCorpCategory.id",
										"MTargetArea.jis_cd = '" . $jis_cd . "'"
								)
						),
						array (
								'fields' => '*',
								'type' => 'inner',
								"table" => "affiliation_area_stats",
								"alias" => "AffiliationAreaStat",
								"conditions" => array (
										'AffiliationAreaStat.corp_id = MCorp.id',
										'AffiliationAreaStat.genre_id = MCorpCategory.genre_id',
										"AffiliationAreaStat.prefecture = '" . $data['DemandInfo']['address1'] . "'"
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
								'type' => 'left outer',
								"table" => "affiliation_subs",
								"alias" => "AffiliationSubs",
								"conditions" => array (
										'AffiliationSubs.affiliation_id = AffiliationInfo.id',
										'AffiliationSubs.item_id = '  . $data['DemandInfo']['category_id']
								)
						),
				);
				$results = $this->MCorp->find('all',
						array( 'fields' => array(
										'MCorp.id',
										'MCorp.auction_status',
										'AffiliationAreaStat.commission_unit_price_category',
										'AffiliationAreaStat.commission_count_category',
										'AffiliationAreaStat.commission_unit_price_rank',
										'MCorpCategory.order_fee',
										'MCorpCategory.order_fee_unit',
										// 2017/05/30  ichino ORANGE-421 ADD
										'MCorpCategory.auction_status',
										// 2017/05/30  ichino ORANGE-421 END
										// murata.s ORANGE-261 ADD(S)
										'MCorpCategory.introduce_fee',
										'MCorpCategory.corp_commission_type',
										// murata.s ORANGE-261 ADD(E)
								),
								'joins' =>  $joins,
								'conditions' => $conditions,
						)
				);

				if (0 < count($results)) {

					$before_list = array();
					$before_data = $this->AuctionInfo->findAllByDemandId($demand_id);
					foreach ($before_data as $key => $val){
						$before_list[$val['AuctionInfo']['corp_id']] = $val['AuctionInfo']['id'];
					}

					$i = 0;
					$auction_data = array();
					$auto_auction_data = array();

					foreach ($results as $key => $val){
						// 2017/05/30  ichino ORANGE-421 CHG ジャンル別取次方法がある場合(0でない)は、変数を上書きする。
						if(Hash::get($val, 'MCorpCategory.auction_status') != 0){
							$auction_status_flg = $val['MCorpCategory']['auction_status'];
						} else {
							$auction_status_flg = $val['MCorp']['auction_status'];
						}
						// 2017/05/30  ichino ORANGE-421 CHG

						//取次データの与信単価の積算が限度額に達していないかチェック
						App::uses('CreditHelper', 'View/Helper');
						$credit_helper = new CreditHelper(new View());
						$result_credit = CREDIT_NORMAL;
						// murata.s ORANGE-485 CHG(S)
						if(!in_array($data['DemandInfo']['site_id'], Configure::read('credit_check_exclusion_site_id'))){
							$result_credit = $credit_helper->checkCredit($val['MCorp']['id'], $data['DemandInfo']['genre_id'], false, true);
						}
						// murata.s ORANGE-485 CHG(E)
						if($result_credit == CREDIT_DANGER){
							//与信限度額を超えている場合、以降の処理を行わない
							continue;
						}
						if($data['DemandInfo']['site_id'] != CREDIT_EXCLUSION_SITE_ID ){
							if($this->__check_agreement_and_license($val['MCorp']['id'], $data['DemandInfo']['category_id']) === false){
								continue;
							}
						}

// 2017.01.04 murata.s ORANGE-244 ADD(S)
						// デフォルト手数料率とデフォルト手数料率単位を取得する
						if( empty($val['MCorpCategory']['order_fee']) || is_null($val['MCorpCategory']['order_fee_unit'])){
							// m_corp_categoriesから取得できなかった場合、m_categoriesから取得する
							$mc_category_data = self::__get_fee_data_m_categories($data['DemandInfo']['category_id']);
							if(empty($mc_category_data)){
								// m_categoriesからも取得できなかった場合、空白とする。
								$mc_category_data['MCategory'] = array('category_default_fee' => '', 'category_default_fee_unit' => '');
							}
							$val['MCorpCategory']['order_fee'] = $mc_category_data['MCategory']['category_default_fee'];
							$val['MCorpCategory']['order_fee_unit'] = $mc_category_data['MCategory']['category_default_fee_unit'];
						}
// 2017.01.04 murata.s ORANGE-244 ADD(E)

						// Commission_infosに登録されているlost_flg,del_flgが「1」の場合は入札対象にしない
						$target_commission_data = $this->CommissionInfo->findByDemandIdAndCorpId($demand_id, $val['MCorp']['id']);
						// 取り出した取次データのlost_flg、del_flgが「0」の加盟店は入札対象とする
						if( !isset($target_commission_data['CommissionInfo']) ||
								($target_commission_data['CommissionInfo']['lost_flg'] == 0) && ($target_commission_data['CommissionInfo']['del_flg'] == 0) ) {

							if(!empty($val['AffiliationAreaStat']['commission_unit_price_rank'])){
								$rank = mb_strtolower($val['AffiliationAreaStat']['commission_unit_price_rank']);
								if(isset($rank_time[$rank])){
									//ORANGE-250 CHG S
									$as = false;
									// 2017/05/30  ichino ORANGE-421 CHG $val['MCorp']['auction_status'] を $auction_status_flg 変更
									if(empty($auction_status_flg)
											|| $auction_status_flg == Util::getDivValue('auction_delivery_status', 'delivery')
											|| $auction_status_flg == Util::getDivValue('auction_delivery_status', 'deny'))$as = true;
									if(!empty($rank_time[$rank]) && $as){
										//ORANGE-250 CHG E
										$auction_data['AuctionInfo'][$i]['id'] = isset($before_list[$val['MCorp']['id']]) ? $before_list[$val['MCorp']['id']]: '' ;
										$auction_data['AuctionInfo'][$i]['demand_id'] = $demand_id;
										$auction_data['AuctionInfo'][$i]['corp_id'] = $val['MCorp']['id'];
										$auction_data['AuctionInfo'][$i]['push_time'] = $rank_time[$rank];
										$auction_data['AuctionInfo'][$i]['push_flg'] = 0;
										$auction_data['AuctionInfo'][$i]['before_push_flg'] = 0;
										$auction_data['AuctionInfo'][$i]['display_flg'] = 0;
										$auction_data['AuctionInfo'][$i]['refusal_flg'] = 0;
										$auction_data['AuctionInfo'][$i]['rank'] = $rank; // ランク毎抽出用
										$i++;
									}
									// 2016.12.01 murata.s ORANGE-250 CHG(S)
									// 自動選定用にデータを作成
									if(!empty($rank_time[$rank])
											&& (empty($auction_status_flg)
													|| $auction_status_flg == Util::getDivValue('auction_delivery_status', 'delivery')
													|| $auction_status_flg == Util::getDivValue('auction_delivery_status', 'ng'))){
										$auto_auction_data['AuctionInfo'][] = array(
												'id' => isset($before_list[$val['MCorp']['id']]) ? $before_list[$val['MCorp']['id']]: '',
												'demand_id' => $demand_id,
												'corp_id' => $val['MCorp']['id'],
												'push_time' => $rank_time[$rank],
												'push_flg' => 0,
												'before_push_flg' => 0,
												'display_flg' => 0,
												'refusal_flg' => 0,
												'rank' => $rank,
												'AffiliationAreaStat' => array(
														'commission_unit_price_category' => $val['AffiliationAreaStat']['commission_unit_price_category'],
														'commission_count_category' => $val['AffiliationAreaStat']['commission_count_category'],
														'commission_unit_price_rank' => $val['AffiliationAreaStat']['commission_unit_price_rank']
												),
												'MCorpCategory' => array(
														'order_fee' => $val['MCorpCategory']['order_fee'],
														'order_fee_unit' => $val['MCorpCategory']['order_fee_unit'],
														// murata.s ORANGE-261 ADD(S)
														'introduce_fee' => $val['MCorpCategory']['introduce_fee'],
														'corp_commission_type' => $val['MCorpCategory']['corp_commission_type']
														// murata.s ORANGE-261 ADD(E)
												),
												'MCorp' => array(
														'id' => $val['MCorp']['id'],
												)
										);
									}
									// 2016.12.01 murata.s ORANGE-250 CHG(E)
								}
							} else {
								// ランクが空白の場合は、初回取次扱いとし、オークション対象とする。
								$rank = 'z';
								if(isset($rank_time[$rank])){
									//ORANGE-250 CHG S
									$as = false;
									if(empty($auction_status_flg)
											|| $auction_status_flg == Util::getDivValue('auction_delivery_status', 'delivery')
											|| $auction_status_flg == Util::getDivValue('auction_delivery_status', 'deny'))$as = true;
									if(!empty($rank_time[$rank]) && $as){
										//ORANGE-250 CHG E
										$auction_data['AuctionInfo'][$i]['id'] = isset($before_list[$val['MCorp']['id']]) ? $before_list[$val['MCorp']['id']]: '' ;
										$auction_data['AuctionInfo'][$i]['demand_id'] = $demand_id;
										$auction_data['AuctionInfo'][$i]['corp_id'] = $val['MCorp']['id'];
										$auction_data['AuctionInfo'][$i]['push_time'] = $rank_time[$rank];
										$auction_data['AuctionInfo'][$i]['push_flg'] = 0;
										$auction_data['AuctionInfo'][$i]['before_push_flg'] = 0;
										$auction_data['AuctionInfo'][$i]['display_flg'] = 0;
										$auction_data['AuctionInfo'][$i]['refusal_flg'] = 0;
										$auction_data['AuctionInfo'][$i]['rank'] = $rank; // ランク毎抽出用
										$i++;
									}
									// 2016.12.01 murata.s ORANGE-250 CHG(S)
									// 自動選定用にデータを作成
									if(!empty($rank_time[$rank])
											&& (empty($auction_status_flg)
													|| $auction_status_flg == Util::getDivValue('auction_delivery_status', 'delivery')
													|| $auction_status_flg == Util::getDivValue('auction_delivery_status', 'ng'))){
										$auto_auction_data['AuctionInfo'][] = array(
												'id' => isset($before_list[$val['MCorp']['id']]) ? $before_list[$val['MCorp']['id']]: '',
												'demand_id' => $demand_id,
												'corp_id' => $val['MCorp']['id'],
												'push_time' => $rank_time[$rank],
												'push_flg' => 0,
												'before_push_flg' => 0,
												'display_flg' => 0,
												'refusal_flg' => 0,
												'rank' => $rank,
												'AffiliationAreaStat' => array(
														'commission_unit_price_category' => $val['AffiliationAreaStat']['commission_unit_price_category'],
														'commission_count_category' => $val['AffiliationAreaStat']['commission_count_category'],
														'commission_unit_price_rank' => $val['AffiliationAreaStat']['commission_unit_price_rank']
												),
												'MCorpCategory' => array(
														'order_fee' => $val['MCorpCategory']['order_fee'],
														'order_fee_unit' => $val['MCorpCategory']['order_fee_unit'],
														// murata.s ORANGE-261 ADD(S)
														'introduce_fee' => $val['MCorpCategory']['introduce_fee'],
														'corp_commission_type' => $val['MCorpCategory']['corp_commission_type'],
														// murata.s ORANGE-261 ADD(E)
												),
												'MCorp' => array(
														'id' => $val['MCorp']['id'],
												)
										);
									}
									// 2016.12.01 murata.s ORANGE-250 CHG(E)
								}
							}
						} else { // lost_flg,del_flg check
							if ( isset( $before_list[$val['MCorp']['id']] ) ) {
								// 再入札時、lost_flg,del_flgが1の加盟店は、辞退したものとして、辞退フラグを1にする
								$auction_refusal_data['AuctionInfo']['id'] = isset($before_list[$val['MCorp']['id']]) ? $before_list[$val['MCorp']['id']] : '';
								$auction_refusal_data['AuctionInfo']['refusal_flg'] = 1;
								if(!$auto_selection)
									// 書き込む
									$this->AuctionInfo->save($auction_refusal_data['AuctionInfo']);
							}
						} // lost_flg,del_flg
					}

					if(!$auto_selection){
						// 入札式選定(手動 or 自動)の場合
						if (0 < count($auction_data)) {
							$auction_data['DemandInfo']['genre_id'] = $data['DemandInfo']['genre_id'];
							$auction_data['DemandInfo']['prefecture'] = $data['DemandInfo']['address1'];
							$this->__carry_auction_push_time($auction_data, $rank_time);
							return $this->AuctionInfo->saveAll($auction_data['AuctionInfo']);
						}
					}else{
						// 自動選定の場合
						if (0 < count($auto_auction_data)) {
							$auto_auction_data['DemandInfo']['genre_id'] = $data['DemandInfo']['genre_id'];
							$auto_auction_data['DemandInfo']['prefecture'] = $data['DemandInfo']['address1'];
							$this->__carry_auction_push_time($auto_auction_data, $rank_time);
						}
						return $auto_auction_data;
					}
				}
			}
		}

		// 入札対象外の場合はtrueとする
		return true;
	}

	/**
	 * 上位ランクの加盟店がいない場合、送信予定時間の繰上げを行う
	 *
	 * @param array $auction_data オークション情報
	 * @param array $rank_time ランク毎送信時刻
	 */
	private function __carry_auction_push_time(&$auction_data, $rank_time){
		if(empty($auction_data)) return;

		$rank_time_sort = array(
				array(
						'rank' => 'a',
						'rank_time' => $rank_time['a']),
				array(
						'rank' => 'b',
						'rank_time' => $rank_time['b']),
				array(
						'rank' => 'c',
						'rank_time' => $rank_time['c']),
				array(
						'rank' => 'd',
						'rank_time' => $rank_time['d']),
				array(
						'rank' => 'z',
						'rank_time' => $rank_time['z']),
		);
		// rank_timeが未設定のrankは取り除く
		$rank_time_sort = array_filter($rank_time_sort, function($v){
			return !empty($v['rank_time']);
		});

		uasort($rank_time_sort, function($a, $b){
			if($a['rank_time'] == $b['rank_time']) {
				return ($a['rank'] < $b['rank']) ? -1 : 1;
			}
			return ($a['rank_time'] < $b['rank_time']) ? -1 : 1;

		});

		foreach($rank_time_sort as $rank){
			$ranks = array_filter($auction_data['AuctionInfo'], function($data) use($rank){
				return $data['rank'] == $rank['rank'];
			});
			if(empty($ranks)){
				// ランクに該当する加盟店がいない
				if(empty($highest_empty_rank))
					$highest_empty_rank = $rank['rank'];
			}else{
				// 上位ランクの加盟店がいない場合は繰上げ送信(=push_timeの繰上げ)を行う
				if(!empty($highest_empty_rank)){
					foreach($auction_data['AuctionInfo'] as $key => $val){
						if($val['rank'] == $rank['rank'])
							$auction_data['AuctionInfo'][$key]['push_time'] = $rank_time[$highest_empty_rank];
					}
				}
				break;
			}
		}
	}

	/**
	 * 契約確認フラグとライセンスのチェックを行う
	 *
	 * @param int $corp_id 企業ID
	 * @param int $category_id カテゴリID
	 */
	private function __check_agreement_and_license($corp_id = null, $category_id = null){
		if($category_id == null)
			return true;
		else
			// 2017/09/13 m-kawamoto ORANGE-512 CHG(S)
			return $this->MCorp->isCommissionStop($corp_id);
			// 2017/09/13 m-kawamoto ORANGE-512 CHG(E)
	}

// 2017.01.04 murata.s ORANGE-244 ADD(S)
	/**
	 * 手数料を取得(m_categoriesより取得)
	 *
	 * @param int $category_id カテゴリID
	 */
	private function __get_fee_data_m_categories($category_id){

		$results = array();

		if (empty($category_id)) {
			return $results;
		}

		$results = $this->MCategory->find ( 'first', array (
				'conditions' => array ('id' => $category_id),
				'fields' => 'category_default_fee, category_default_fee_unit'
		) );

		return $results;
	}
// 2017.01.04 murata.s ORANGE-244 ADD(E)




}