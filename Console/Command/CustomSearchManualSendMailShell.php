<?php
App::uses('AppShell', 'Console/Command');
App::uses('ComponentCollection', 'Controller');
App::uses('CustomEmailComponent', 'Controller/Component');

class CustomSearchManualSendMailShell extends AppShell{

	public $uses = array('NotCorrespondSendMailLog', 'NotCorrespondSearchExclution');

	public $tasks = array('CustomSearch');

	private static $user = 'system';

	/**
	 * 初期化
	 * @see Shell::startup()
	 */
	public function startup() {
		parent::startup();
		$collection = new ComponentCollection();
		$this->CustomEmail = new CustomEmailComponent($collection);
	}

	/**
	 * Google検索結果メール送信
	 */
	public function main() {
		try{
			$this->log('Google検索結果メール送信', SHELL_LOG);
			$this->execute();
			$this->log('Google検索結果メール送信 終了', SHELL_LOG);
		}catch(Exception $e){
			$this->log($e, SHELL_LOG);
		}
	}

	/**
	 * Google検索結果を元にメールを送信する
	 */
	private function execute(){
		// 検索するキーワード、メール本文を取得
		$search_settings = Configure::read('manual_search_mail');

		$keywords = $search_settings['keyword'];
		$mail_data = array();

// 		// 除外対象URLを取得
// 		$exclusions = $this->NotCorrespondSearchExclution->find('all', array(
// 				'fields' => array('exclude_url'),
// 		));

		foreach($keywords as $keyword){
			// google検索を実施
			// 手動のため、jis_cdとgenre_idは未設定(null)とする
			$search_results = $this->CustomSearch
				->numMax($search_settings['num'])
				->execute(array('query'=>$keyword));

			foreach($search_results as $result){

				// 除外ドメイン判定(部分一致)
				$exclusion_count = $this->NotCorrespondSearchExclution->find('count', array(
						//'conditions' => array("'".$result['link']."' like" => '%exclude_url%')
						'conditions' => array("'{$result['link']}' like '%' || exclude_url || '%'")
				));
				if($exclusion_count > 0){
					continue;
				}
// 				$is_exclusion = false;
// 				foreach($exclusions as $exclusion){
// 					if (strstr($result['link'], $exclusion['NotCorrespondSearchExclution']['exclude_url'])){
// 						$is_exclusion = true;
// 						break;
// 					}
// 				}
// 				if($is_exclusion){
// 					continue;
// 				}

				// ドメイン毎にまとめる（同一ドメインに何度も送らないようにする）
				foreach($mail_data as $key => $val){
					if($val['domain'] == $result['domain']){
						$data = $val['domain'];
						break;
					}else{
						$data = null;
					}
				}

				if(empty($data)){
					$mail_data[] = array(
							'not_correspond_search_log_id' => $result['not_correspond_search_log_id'],
							'domain' => $result['domain'],
							'to_address' => 'info@'.$result['domain'],
							'subject' => $search_settings['subject'],
							//'message' => $search_settings['message'],
							'template' => $search_settings['template'],
					);
				}
			}
		}

// 2017.09.01 メール送信処理を一旦中止 CHG(S)
//		// 検索結果を元にメール送信
//		if(!empty($mail_data)){
//			foreach($mail_data as $val){
//				$this->__send_mail($val);
//			}
//		}
// 2017.09.01 メール送信処理を一旦中止 CHG(E)
	}

	/**
	 * メールを送信する
	 * @param array $data ドメイン名
	 */
	private function __send_mail($data){

		$this->NotCorrespondSendMailLog->create();

		// メール設定
		$setting = Configure::read('manual_search_mail_setting');
		$from_mail = $setting['from_address'];
		$from_name = $setting['from_name'];
		$to_mail = $data['to_address'];
		$bcc_mail = Util::getDivText('bcc_mail', 'to_address');
		if(!empty($setting['to_address'])) { // Debug用
			$data['to_address'] = $to_mail;
			$to_mail = $setting['to_address'];
		}
		if(!empty($setting['bcc_address'])){ // Debug用
			$bcc_mail = $setting['bcc_address'];
		}

		// 過去にエラーとなっていない、未送信の場合のみメール送信
		$past_fail = $this->NotCorrespondSendMailLog->find('first', array(
				'conditions' => array(
						'mail_address' => $to_mail,
						'send_result' => 0,
				)
		));
		if(empty($past_fail)){
			// メール送信
			//$result = $this->CustomEmail->bcc_simple_send($data['subject'], $data['message'], $to_mail, $from_mail, $bcc_mail);
			$result = $this->CustomEmail->bcc_send($data['subject'], $data['template'], $data, $to_mail, null, $from_mail, $from_name, $bcc_mail);

			// 送信結果を登録
			$save_data = array(
					'not_correspond_search_log_id' => $data['not_correspond_search_log_id'],
					'mail_address' => $to_mail,
					'subject' => $data['subject'],
					'message' => !empty($result['mail']['message']) ? $result['mail']['message'] : null,
					'send_result' => !empty($result['result']) ? 1 : 0,
					'send_datetime' => date("Y/m/d H:i:s"),
					'modified_user_id' => self::$user,
					'modified' => date("Y/m/d H:i:s"),
					'created_user_id' => self::$user,
					'created' => date("Y/m/d H:i:s"),
			);
			$this->NotCorrespondSendMailLog->save($save_data, array('callbacks' => false));
		}
	}
}