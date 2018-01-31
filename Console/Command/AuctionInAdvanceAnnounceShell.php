<?php
App::uses('AppShell', 'Console/Command');

class AuctionInAdvanceAnnounceShell extends AppShell {

	public $uses = array('DemandInfo', 'MTime', 'AuctionInfo', 'VisitTime', 'MGenre', 'MSite');

	private static $user = 'system';

	public function main() {

		try {

			$this->log('事前周知メール処理start', AUCTION_LOG);

			$this->log('至急案件処理start', AUCTION_LOG);
			$this->executeImmediately();
			$this->log('至急案件処理end', AUCTION_LOG);


			$this->log('通常案件処理start', AUCTION_LOG);
			$this->executeNormal();
			$this->log('通常案件処理end', AUCTION_LOG);

			$this->log('事前周知メール処理end', AUCTION_LOG);

		} catch (Exception $e) {
			$this->log($e, SHELL_LOG);
		}

	}

	/**
	 * 至急案件の事前通知メール送信処理
	 */
	private function executeImmediately() {

		// 至急案件の設定を取得
		$conditions = array();

		$item = $this->MTime->findByItemDetailAndItemCategory('immediately', 'send_mail');

		// 設定がない場合は、処理終了
		if (!isset($item['MTime']['item_hour_date']) && !isset($item['MTime']['item_minute_date'])) {
			return;
		}

		// システム日付より基準日時を算出する。
		$hours = isset($item['MTime']['item_hour_date']) ? $item['MTime']['item_hour_date'] : 0;
		$minutes =  isset($item['MTime']['item_minute_date']) ? $item['MTime']['item_minute_date'] : 0;

		$minutes = $hours * 60 + $minutes;

		$base_date = date('Y-m-d H:i', strtotime( date("Y/m/d H:i"). ' + '. $minutes .' minute'));

		$joins = array(
				array(
						'fields' => '*',
						'table' => 'auction_infos',
						'alias' => "AuctionInfo",
						'type' => 'inner',
						'conditions' => array('DemandInfo.id = AuctionInfo.demand_id')
				),
				array(
						'fields' => '*',
						'table' => 'visit_times',
						'alias' => "VisitTime",
						'type' => 'inner',
						'conditions' => array('DemandInfo.id = VisitTime.demand_id', 'AuctionInfo.visit_time_id = VisitTime.id')
				),
				//ORANGE-250 CHG S
				array(
						'fields' => '*',
						'table' => 'm_corps',
						'alias' => "Mcorp",
						'type' => 'inner',
						'conditions' => array (
								'Mcorp.id = AuctionInfo.corp_id',
								// 'Mcorp.auction_status in (1, 3)'
						)
				),
				//ORANGE-250 CHG E
		);

		$sql = 'exists(SELECT demand_id FROM commission_infos INNER JOIN demand_infos ON commission_infos.demand_id = demand_infos.id
                        WHERE commission_infos.commit_flg = 1 AND commission_infos.commission_status != '.Util::getDivValue("construction_status", "order_fail").' AND demand_id = DemandInfo.id )';

		$conditions = array($sql);
		//$conditions = array();

		// 通知メール未送信
		$conditions['AuctionInfo.before_push_flg'] = 0;

// 2016.11.17 murata.s ORANGE-185 CHG(S)
		// オークション選定方式案件
// 		$conditions['DemandInfo.selection_system'] = Util::getDivValue('selection_type', 'AuctionSelection');
		$conditions['DemandInfo.selection_system'] = array(
				Util::getDivValue('selection_type', 'AuctionSelection'),
				Util::getDivValue('selection_type', 'AutomaticAuctionSelection')
		);
// 2016.11.17 murata.s ORANGE-185 CHG(E)

		// 至急案件
		$conditions['DemandInfo.priority'] = Util::getDivValue('priority', 'immediately');

		// 周知メール送信日時を過ぎたもの
		$conditions['VisitTime.visit_time < '] = $base_date;

		// 案件状況が進行中のみ
		$conditions['DemandInfo.demand_status'] = array(Util::getDivValue("demand_status", "telephone_already"), Util::getDivValue("demand_status", "information_sent"));

		$fields = array('DemandInfo.*', 'AuctionInfo.id','AuctionInfo.before_push_flg', 'VisitTime.visit_time', 'Mcorp.mailaddress_auction', 'Mcorp.official_corp_name');

		$params = array(
				'fields' => $fields,
				'conditions' => $conditions,
				'joins' => $joins,
		);

		// 検索
		$list = $this->DemandInfo->find('all', $params);

		$edit_list = array();
		foreach ($list as $key => $row) {
			// メール送信
			if(self::__send_mail($row)){
				// 送信済みにする
				$edit_list['AuctionInfo'][$key]['id'] = $row['AuctionInfo']['id'];
				$edit_list['AuctionInfo'][$key]['before_push_flg'] = 1;
			}
		}

		if(!empty($edit_list)){
			// 更新
			$this->AuctionInfo->saveAll($edit_list['AuctionInfo']);
		}

	}

	/**
	 * 通常案件の事前通知メール送信処理
	 */
	private function executeNormal() {

		// 通常案件の設定を取得
		$conditions = array();

		$item = $this->MTime->findByItemDetailAndItemCategory('normal', 'send_mail');

		// 設定がない場合は、処理終了
		if (!isset($item['MTime']['item_hour_date']) && !isset($item['MTime']['item_minute_date'])) {
			return;
		}

		// システム日付より基準日時を算出する。
		$hours = isset($item['MTime']['item_hour_date']) ? $item['MTime']['item_hour_date'] : 0;
		$minutes =  isset($item['MTime']['item_minute_date']) ? $item['MTime']['item_minute_date'] : 0;

		$minutes = $hours * 60 + $minutes;

		$base_date = date('Y-m-d H:i', strtotime( date("Y/m/d H:i"). ' + '. $minutes .' minute'));

		$joins = array(
				array(
						'table' => 'auction_infos',
						'alias' => "AuctionInfo",
						'type' => 'inner',
						'conditions' => array('DemandInfo.id = AuctionInfo.demand_id')
				),
				array(
						'table' => 'visit_times',
						'alias' => "VisitTime",
						'type' => 'inner',
						'conditions' => array('DemandInfo.id = VisitTime.demand_id', 'AuctionInfo.visit_time_id = VisitTime.id')
				),
				//ORANGE-250 CHG S
				array(
						'fields' => '*',
						'table' => 'm_corps',
						'alias' => "Mcorp",
						'type' => 'inner',
						'conditions' => array (
								'Mcorp.id = AuctionInfo.corp_id',
								// 'Mcorp.auction_status in (1, 3)'
						)
				),
				//ORANGE-250 CHE E
		);

		$sql = 'exists(SELECT demand_id FROM commission_infos INNER JOIN demand_infos ON commission_infos.demand_id = demand_infos.id
                        WHERE commission_infos.commit_flg = 1 AND commission_infos.commission_status != '.Util::getDivValue("construction_status", "order_fail").' AND demand_id = DemandInfo.id)';

		$conditions = array($sql);

		// 通知メール未送信
		$conditions['AuctionInfo.before_push_flg'] = 0;

// 2016.11.17 murata.s ORANGE-185 CHG(S)
		// オークション選定方式案件
// 		$conditions['DemandInfo.selection_system'] = Util::getDivValue('selection_type', 'AuctionSelection');
		$conditions['DemandInfo.selection_system'] = array(
				Util::getDivValue('selection_type', 'AuctionSelection'),
				Util::getDivValue('selection_type', 'AutomaticAuctionSelection')
		);
// 2016.11.17 murata.s ORANGE-185 CHG(E)

		// 至急案件
		$conditions['DemandInfo.priority'] = Util::getDivValue('priority', 'normal');

		// 周知メール送信日時を過ぎたもの
		$conditions['VisitTime.visit_time < '] = $base_date;

		// 案件状況が進行中のみ
		$conditions['DemandInfo.demand_status'] = array(Util::getDivValue("demand_status", "telephone_already"), Util::getDivValue("demand_status", "information_sent"));

		$fields = array('DemandInfo.*', 'AuctionInfo.id','AuctionInfo.before_push_flg', 'VisitTime.visit_time', 'Mcorp.mailaddress_auction', 'Mcorp.official_corp_name');

		$params = array(
				'fields' => $fields,
				'conditions' => $conditions,
				'joins' => $joins,
		);

		// 検索
		$list = $this->DemandInfo->find('all', $params);

		$edit_list = array();
		foreach ($list as $key => $row) {
			// メール送信
			if(self::__send_mail($row)){
				// 送信済みにする
				$edit_list['AuctionInfo'][$key]['id'] = $row['AuctionInfo']['id'];
				$edit_list['AuctionInfo'][$key]['before_push_flg'] = 1;
			}
		}

		if(!empty($edit_list)){
			// 更新
			$this->AuctionInfo->saveAll($edit_list['AuctionInfo']);
		}

	}

	/**
	 * メール送信処理
	 *
	 * @param unknown $data
	 * @return boolean
	 */
	private function __send_mail($data) {

		$corp_name = $data["Mcorp"]['official_corp_name'];
		$to_address_list = explode(';', $data["Mcorp"]['mailaddress_auction']);
		$prefecture = Util::getDivTextJP("prefecture_div", $data["DemandInfo"]['address1']);
		$genre_name = $this->MGenre->getListText($data["DemandInfo"]['genre_id']);
		$site_name = $this->MSite->getListText($data["DemandInfo"]['site_id']);
		$postcode = !empty($data["DemandInfo"]['postcode'])? '〒'.$data["DemandInfo"]['postcode'].'　' : '';
		$address1 = Util::getDivTextJP('prefecture_div',$data['DemandInfo']['address1']);
		$address2 = $data['DemandInfo']['address2'];
		// 2016.3.29 sasaki@tobila.com MOD start ORANGE-1343 「それ以降の住所」マスキング仕様変更
		$address3 = Util::maskingAddress3($data['DemandInfo']['address3']);
		// 2016.3.29 sasaki@tobila.com MOD end ORANGE-1343 「それ以降の住所」マスキング仕様変更
		$address = $postcode.$address1.$address2.$address3;
		$tel1 = !empty($data['DemandInfo']['tel1']) ? substr_replace($data['DemandInfo']['tel1'], "******", -6,6) : '';
		$tel2 = !empty($data['DemandInfo']['tel2']) ? substr_replace($data['DemandInfo']['tel2'], "******", -6,6) : '';

		mb_language('Ja');
		mb_internal_encoding('UTF-8');

		$header = 'From:' . Util::getDivText('before_auction_mail_setting', 'from_address');
		$subject = sprintf(Util::getDivText('before_auction_mail_setting', 'title'), $data["DemandInfo"]['id']);

// 2015.10.10 h.hanaki CHG       ORANGE_AUCTION-17(追加:DemandInfo.construction_class)
//		$body = sprintf(Util::getDivText('before_auction_mail_setting', 'contents'), $corp_name, $data["DemandInfo"]['id'], $site_name, $genre_name, $data["DemandInfo"]['customer_name'], $address, $tel1, $tel2, $data["DemandInfo"]['contents']);
		$body = sprintf(Util::getDivText('before_auction_mail_setting', 'contents'), $corp_name, $data["DemandInfo"]['id'], $site_name, $genre_name, $data["DemandInfo"]['customer_name'], $address, Util::getDropText('建物種別',$data['DemandInfo']['construction_class']), $tel1, $tel2, $data["DemandInfo"]['contents']);

		// BCCアドレス
		$header .= PHP_EOL."Bcc:".Util::getDivText('bcc_mail', 'to_address');

		// 全ての宛先にメール送信
		foreach ($to_address_list as $to_address) {
			if(!empty($to_address)){
				if (!mb_send_mail(trim($to_address), $subject, $body, $header, "-forange@rits-orange.jp")) {
					$result = false;
				}
			}
		}
		return true;
	}
}