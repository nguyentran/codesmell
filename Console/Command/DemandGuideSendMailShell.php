<?php
App::uses('AppShell', 'Console/Command');
App::uses('CommonHelper', '/Lib/View/Helper/');

class DemandGuideSendMailShell extends AppShell {
    
        /* 2017/08/03  ichino ORANGE-456 ADD AccumulatedInformation追加 start */
	public $uses = array('DemandInfo', 'MTime', 'AuctionInfo', 'MGenre', 'MSite', 'MUser','AccumulatedInformation');
        /* 2017/08/03  ichino ORANGE-456 ADD end */
	// murata.s ORANGE-537 ADD(S)
	public $tasks = array('AuctionAutoCall');
	// murata.s ORANGE-537 ADD(E)
	private static $user = 'system';

	public function main() {


		try {

			$this->log('案件案内メール処理start', AUCTION_LOG);

			$this->execute();

			// murata.s ORANGE-537 ADD(S)
			$this->execute_auto_call();
			// murata.s ORANGE-537 ADD(E)

			$this->log('案件案内メール処理end', AUCTION_LOG);

		} catch (Exception $e) {
			$this->log($e, SHELL_LOG);
		}

	}

	/**
	 * 案件案内メール送信
	 */
	private function execute() {

		$joins = array(
				array(
						'table' => 'auction_infos',
						'alias' => "AuctionInfo",
						'type' => 'inner',
						'conditions' => array('DemandInfo.id = AuctionInfo.demand_id')
				),
				array(
						'fields' => '*',
						'table' => 'm_corps',
						'alias' => "Mcorp",
						'type' => 'inner',
						'conditions' => array (
								'Mcorp.id = AuctionInfo.corp_id',
						)
				),
		);

		// 通知メール未送信
		$conditions['AuctionInfo.push_flg'] = 0;

// 2016.11.17 murata.s ORANGE-185 CHG(S)
		// オークション選定方式案件
//		$conditions['DemandInfo.selection_system'] = Util::getDivValue('selection_type', 'AuctionSelection');
		$conditions['DemandInfo.selection_system'] = array(
				Util::getDivValue('selection_type', 'AuctionSelection'),
				Util::getDivValue('selection_type', 'AutomaticAuctionSelection')
		);
// 2016.11.17 murata.s ORANGE-185 CHG(E)

		// オークションメールSTOPフラグ「0」
		$conditions['DemandInfo.push_stop_flg'] = 0;

		// 送信日時を過ぎている
		$conditions['AuctionInfo.push_time < '] =  date("Y/m/d H:i");

		//ORANGE-250 DEL S
		// // オークション配信先状況
		// $conditions['Mcorp.auction_status in (1, 3)'];
		//ORANGE-250 DEL E

		$fields = array('DemandInfo*', 'AuctionInfo.id', 'AuctionInfo.push_flg', 'Mcorp.mailaddress_auction', 'Mcorp.official_corp_name', 'Mcorp.coordination_method', 'Mcorp.id');

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
                            $edit_list['AuctionInfo'][$key]['push_flg'] = 1;
                            
                            //企業の取次方法が6または7のとき
                            if ($row['Mcorp']['coordination_method'] == 6 || $row['Mcorp']['coordination_method'] == 7) {
                                $corpId = $row['Mcorp']['id'];

                                //加盟店IDに紐づくユーザIDを取得
                                $options = array();
                                $options['conditions'] = array('MUser.affiliation_id' => $corpId);
                                $users = $this->MUser->find('all', $options);

                                $amazonSnsUtil = new AmazonSnsUtil();
                                foreach ($users as $user) {
                                    //通知送信
                                    $pushMessage = '新しい案件があります。';
                                    $amazonSnsUtil->publish($user['MUser']['user_id'], $pushMessage);
                                }
                            }
                                
                                /* 2017/08/03  ichino ORANGE-456 ADD start */
                                //情報蓄積テーブルへの保存データ
                                $info_data['AccumulatedInformation'][$key]['demand_id'] = $row['DemandInfo']['id'];
                                $info_data['AccumulatedInformation'][$key]['corp_id'] = $row['Mcorp']['id'];
                                $info_data['AccumulatedInformation'][$key]['demand_regist_date'] = $row['DemandInfo']['created'];
                                $info_data['AccumulatedInformation'][$key]['mail_send_date'] = date('Y-m-d H:i');
                                $info_data['AccumulatedInformation'][$key]['created_user_id'] = 'SYSTEM';
                                $info_data['AccumulatedInformation'][$key]['modified_user_id'] = 'SYSTEM';
                                /* 2017/08/03  ichino ORANGE-456 ADD end */
                        }
                }
		
		if(!empty($edit_list)){
			// 更新
			$this->AuctionInfo->saveAll($edit_list['AuctionInfo']);
		}
                
                /* 2017/08/03  ichino ORANGE-456 ADD start */
                if(!empty($info_data)){
                    // 更新
                    //AppModelのBeforeSaveでcreated_user_id, modified_user_idを空文字で上書きしてしまうためコールバックを無効にする
                    $this->AccumulatedInformation->saveAll($info_data['AccumulatedInformation'], array(
				'validate' => false,
				'callbacks' => false
                    ));
                }
                /* 2017/08/03  ichino ORANGE-456 ADD end */

	}

	// murata.s ORANGE-537 ADD(S)
	/**
	 * オートコール処理
	 */
	private function execute_auto_call(){
		// オートコールの実行
		$this->AuctionAutoCall->execute();
	}
	// murata.s ORANGE-537 ADD(E)

	/**
	 * メール送信処理
	 *
	 * @param unknown $data
	 * @return boolean
	 */
	private function __send_mail($data) {

		$this->Common = new CommonHelper(new View());
                
                /* 2017/08/03  ichino ORANGE-456 ADD start */
                $corp_id = $data["Mcorp"]['id'];
                /* 2017/08/03  ichino ORANGE-456 ADD end */
                
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
		$auction_deadline_time = $this->Common->dateTimeWeekJP($data['DemandInfo']['auction_deadline_time']);
		$customer_mailaddress = !empty($data["DemandInfo"]['customer_mailaddress']) ? substr_replace($data['DemandInfo']['customer_mailaddress'], "******", 0, 6) : '';

		// 2017/12/13 m-kawamoto ORANGE-456 CHG(S)
		//mb_language('ja');
		mb_language('uni');
		// 2017/12/13 m-kawamoto ORANGE-456 CHG(E)
		mb_internal_encoding('UTF-8');

		$header = 'From:' . Util::getDivText('auction_mail_setting', 'from_address');
		// murata.s ORANGE-455 CHG(S)
		$subject = sprintf(Util::getDivText('auction_mail_setting', 'title'),$genre_name, $address1, $data["DemandInfo"]['id']);
		// murata.s ORANGE-455 CHG(E)
// 2015.10.10 h.hanaki CHG       ORANGE_AUCTION-17(追加:DemandInfo.construction_class)
//		$body = sprintf(Util::getDivText('auction_mail_setting', 'contents'), $corp_name,$data["DemandInfo"]['id'],$site_name, $genre_name, $data["DemandInfo"]['customer_name'], $address, $tel1, $tel2, $data["DemandInfo"]['customer_mailaddress'], $data["DemandInfo"]['contents'], $auction_deadline_time);
		/* 2017/08/08  ichino ORANGE-456 ADD start */
		$body = sprintf(Util::getDivText('auction_mail_setting', 'contents'), $corp_name,$data["DemandInfo"]['id'],$site_name, $genre_name, $data["DemandInfo"]['customer_name'], $address, Util::getDropText('建物種別',$data['DemandInfo']['construction_class']), $tel1, $tel2, $customer_mailaddress, $data["DemandInfo"]['contents'], $auction_deadline_time, $data["DemandInfo"]['id'], $corp_id);
		/* 2017/08/08  ichino ORANGE-456 ADD end */

		// BCCアドレス
		$header .= PHP_EOL."Bcc:".Util::getDivText('bcc_mail', 'to_address');
		/* 2017/08/08  ichino ORANGE-456 ADD start */
		// 2017/12/13 m-kawamoto ORANGE-456 CHG(S)
		//$header .= PHP_EOL.'Content-Type: text/html;';
		$header .= PHP_EOL.'Content-Type: text/html; charset=UTF-8';
		// 2017/12/13 m-kawamoto ORANGE-456 CHG(E)
		/* 2017/08/08  ichino ORANGE-456 ADD end */
		// 2017/12/13 m-kawamoto ORANGE-456 ADD(S)
		$header .= PHP_EOL.'Content_Language: ja';
		// 2017/12/13 m-kawamoto ORANGE-456 ADD(E)
                
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