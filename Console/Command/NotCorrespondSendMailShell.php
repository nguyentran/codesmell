<?php
App::uses('ComponentCollection', 'Controller');
App::uses('CustomEmailComponent', 'Controller/Component');

class NotCorrespondSendMailShell extends AppShell{

	public $uses = array(
			'NotCorrespond',
			'MGenre',
			'MPost',
			'NotCorrespondSearchLog',
			'NotCorrespondSearchResult',
			'NotCorrespondSearchExclution',
			'NotCorrespondSendMailLog',
			'NotCorrespondItem'
	);

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

		$this->search_setting = Configure::read('not_correspond_search_setting');
	}

	/**
	 * 開拓企業自動メール送信
	 */
	public function main() {
		try{

			$this->log('自動開拓検索結果メール送信', SHELL_LOG);
			$this->execute();
			$this->log('自動開拓検索結果メール送信 終了', SHELL_LOG);

		}catch(Exception $e){
			$this->log($e, SHELL_LOG);
		}
	}

	/**
	 * 開拓企業自動メールを送信する
	 */
	private function execute(){

		// 対応エリア加盟店なし案件を取得
		$corresponds = $this->__find_notCorrespond();

		// Google検索結果を取得
		$mail_data = array();
		foreach($corresponds as $correspond){
			$search_results = $this->__custom_search($correspond);

			foreach($search_results as $result){

				// ドメイン毎にジャンル、エリアをまとめる
				foreach($mail_data as $key=>$val){
					if($val['domain'] == $result['domain']){
						$data = $val['domain'];
						// ジャンル
						$genre_id = $result['genre_id'];
						if(!array_key_exists($genre_id, $val['genres']))
							$mail_data[$key]['genres'][$genre_id] = $result['genre_name'];

						// エリア
						$jis_cd = $result['jis_cd'];
						if(!array_key_exists($jis_cd, $val['addresses']))
							$mail_data[$key]['addresses'][$jis_cd] = $result['address'];
						break;
					}else{
						$data = null;
					}
				}

				if(empty($data)){
					$mail_data[] = array(
							'not_correspond_search_log_id' => $result['not_correspond_search_log_id'],
							'domain' => $result['domain'],
							'genres' => array($result['genre_id']=> $result['genre_name']),
							'addresses' => array($result['jis_cd'] => $result['address']),

					);
				}
			}
		}
		if(!empty($mail_data)){
			foreach ($mail_data as $val)
				$this->__send_mail($val);
		}
	}

	/**
	 * 対応エリア加盟店なし案件を取得
	 */
	private function __find_notCorrespond(){

		// 対応エリア加盟店検索上限
		$limit = $this->search_setting['limit'];

		// 取得対象とする案件数の下限値を取得
		$items = $this->NotCorrespondItem->find('first', array(
				'order' => array('id' => 'desc')
		));

		$dbo = $this->NotCorrespond->getDataSource();
		$MPost = $dbo->buildStatement(array(
				'fields' => array('jis_cd', 'address1', 'address2'),
				'table' => 'm_posts',
				'alias' => 'MPostA',
				'group' => array('jis_cd', 'address1', 'address2')
		), $this->MPost);
		$NotCorrespondSearchLog = $dbo->buildStatement(array(
				'fields' => array(
						'max(id) as "id"',
						'jis_cd',
						'genre_id',
						'max(search_datetime) as "search_datetime"'
				),
				'table' => 'not_correspond_search_logs',
				'alias' => 'NotCorrespondSearchLogA',
				'conditions' => array(
						// 自動開拓メール送信ログのみ抽出
						'NotCorrespondSearchLogA.jis_cd is not null',
						'NotCorrespondSearchLogA.genre_id is not null'
				),
				'group' => array('jis_cd', 'genre_id')
		), $this->NotCorrespondSearchLog);

		$result = $this->NotCorrespond->find('all', array(
				'fields' => array(
						'NotCorrespond.jis_cd',
						'NotCorrespond.genre_id',
						'MGenre.genre_name',
						'MPost.address1',
						'MPost.address2',
						'NotCorrespondSearchLog.search_datetime'
				),
				'joins' => array(
						array(
								'type' => 'inner',
								'table' => 'm_genres',
								'alias' => 'MGenre',
								'conditions' => array('MGenre.id = NotCorrespond.genre_id')
						),
						array(
								'type' => 'inner',
								'table' => "({$MPost})",
								'alias' => 'MPost',
								'conditions' => array('MPost.jis_cd = NotCorrespond.jis_cd')
						),
						array(
								'type' => 'left',
								'table' => "({$NotCorrespondSearchLog})",
								'alias' => 'NotCorrespondSearchLog',
								'conditions' => array(
										'NotCorrespondSearchLog.jis_cd = NotCorrespond.jis_cd',
										'NotCorrespondSearchLog.genre_id = NotCorrespond.genre_id'
								)
						)
				),
				'conditions' => array(
						'or' => array(
								'NotCorrespond.not_correspond_count_year >=' => $items['NotCorrespondItem']['small_lower_limit'],
								'NotCorrespond.not_correspond_count_latest >=' => $items['NotCorrespondItem']['immediate_lower_limit'],
						),
				),
				'order' => array(
						'NotCorrespondSearchLog.search_datetime IS NULL' => 'desc',
						'NotCorrespondSearchLog.search_datetime' => 'asc',
				),
				'limit' => $limit
		));
		return $result;
	}

	/**
	 * 自動開拓メールを送信する
	 * @param array $data ドメイン名
	 */
	private function __send_mail($data){

		$this->NotCorrespondSendMailLog->create();

		// メール設定
		$setting = Configure::read('not_correspond_mail_setting');
		$from_mail = $setting['from_address'];
		$from_name = $setting['from_name'];
		$subject = $setting['subject'];
		$template = $setting['template'];
		$mail_address = 'info@'.$data['domain'];
		$to_mail = $mail_address;
		$bcc_mail = Util::getDivText('bcc_mail', 'to_address');
		if(!empty($setting['to_address'])) { // Debug用
			$data['to_address'] = $mail_address;
			$to_mail = $setting['to_address'];
		}
		if(!empty($setting['bcc_address'])) $bcc_mail = $setting['bcc_address']; // Debug用


		$data['genre_names'] = '';
		$data['address_names'] = '';
		foreach ($data['genres'] as $val){
			$genre_name = $this->__convert_genre_name($val);
			if(strpos($data['genre_names'], '《'.$genre_name.'》') === false){
				$data['genre_names'] .= '《'.$genre_name.'》';
			}
		}
		foreach ($data['addresses'] as $val)
			$data['address_names'] .= '《'.$val.'》';

		// 過去メール送信時にエラーとなったかどうか
		$past_fail = $this->NotCorrespondSendMailLog->find('first', array(
				'conditions' => array(
						'mail_address' => $to_mail,
						'send_result' => 0,
				)
		));

		// 過去にエラーとなっていない、未送信の場合のみメールを送信
		if(empty($past_fail)){

			// メール送信
			$result = $this->CustomEmail->bcc_send($subject, $template, $data, $to_mail, null, $from_mail, $from_name, $bcc_mail);

			// 送信結果を登録
			$save_data = array(
					'not_correspond_search_log_id' => $data['not_correspond_search_log_id'],
					'mail_address' => $to_mail,
					'subject' => $subject,
					'message' => !empty($result['mail']['message']) ? $result['mail']['message'] : null,
					'send_result' => !empty($result['result']) ? 1 : 0,
					'send_datetime' => date("Y/m/d H:i:s"),
					'modified_user_id' => self::$user,
					'modified' => date("Y/m/d H:i:s"),
					'created_user_id' => self::$user,
					'created' => date("Y/m/d H:i:s")
			);
			$this->NotCorrespondSendMailLog->save($save_data, array('callbacks' => false));
		}
	}

	/**
	 * ジャンルID、市区町村コードでgoogle custom searchを使用して検索する
	 *
	 * @param array $data
	 */
	private function __custom_search($data){

		$genre_name = $data['MGenre']['genre_name'];
		$address = $data['MPost']['address1'].$data['MPost']['address2'];
		$query = $this->__convert_genre_name($genre_name).' '.$address;

		$result = $this->CustomSearch
			->numMax($this->search_setting['num_max'])
			->execute(array(
					'query' => $query,
					'jis_cd' => $data['NotCorrespond']['jis_cd'],
					'genre_id' => $data['NotCorrespond']['genre_id'],
			));

		// 検索キーワードとして指定したジャンル名、市区町村名を設定
		foreach($result as $key => $val){
			$result[$key]['genre_name'] = $genre_name;
			$result[$key]['address'] = $address;
		}

		return $result;
	}

	/**
	 * JBRリフォームなどのJBRの場合、リフォームなどに変換する
	 * @param string $genre_name ジャンル名
	 * @return ジャンル名
	 */
	private function __convert_genre_name($genre_name){
		return str_replace('JBR', '', $genre_name);
	}

}