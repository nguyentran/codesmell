<?php
App::uses('AppShell', 'Console/Command');

class CustomSearchTask extends AppShell{

	public $uses = array(
			'NotCorrespondSearchLog',
			'NotCorrespondSearchResult',
			'NotCorrespondSearchExclution'
	);

	private static $user = 'system';

	/**
	 * 取得最大件数
	 * @var int
	 */
	protected $_num_max = 10;

	public function __construct(){
		parent::__construct();

		// 各種設定の取得
		$this->cse_setting = Configure::read('cse_setting');
	}

	/**
	 * 取得最大件数の設定
	 * @param integer $num_max
	 */
	public function numMax($num_max = null){
		if($num_max === null){
			return $this->_num_max;
		}
		$this->_num_max = $num_max;
		return $this;
	}

	/**
	 * google custom searchを行う
	 * @param array $search_data ['query', 'jis_cd'=null, 'genre_id'=null]
	 */
	public function execute($search_data){

		$this->NotCorrespondSearchLog->create();

		// 各種設定の取得
		$this->cse_setting = Configure::read('cse_setting');

		// 検索日時等を設定
		$search_data['search_datetime'] = date("Y/m/d H:i:s");
		$search_data['modified_user_id'] = self::$user;
		$search_data['modified'] = date("Y/m/d H:i:s");
		$search_data['created_user_id'] = $search_data['modified_user_id'];
		$search_data['created'] = $search_data['modified'];

		// custom searchの設定
		$url = $this->cse_setting['url'];
		$key = $this->cse_setting['key'];
		$cx = $this->cse_setting['cx'];
		$num = $this->cse_setting['num'];
		$q = urlencode($search_data['query']);

		$cse_url = sprintf($url, $q, $key, $cx);

		//// 除外対象URLを取得
		//$site_exclusions = array();
		//$exclusions = $this->NotCorrespondSearchExclution->find('all', array(
		//		'fields' => array('exclude_url'),
		//));
		//foreach($exclusions as $exclusion)
		//	$site_exclusions[] = $exclusion['NotCorrespondSearchExclution']['exclude_url'];
		//if(!empty($site_exclusions))
		//	$cse_url .= sprintf('&siteSearch=%s&siteSearchFilter=e', urlencode(implode(' ', $site_exclusions)));
// 2017.08.21 ADD(S)
		if(!empty($this->cse_setting['exclusions'])){
			$cse_url .= sprintf('&siteSearch=%s&siteSearchFilter=e', urlencode(implode(' ', $this->cse_setting['exclusions'])));
		}
// 2017.08.21 ADD(E)

		$result = array();
		for($startIndex = 1; $startIndex < $this->_num_max;){
			$search_url = $cse_url.'&start='.$startIndex.'&num='.$num;
			$contents = file_get_contents($search_url, true);

			if(empty($contents)){
				// Google検索結果取得に失敗
				if(count($http_response_header) > 0){
					$status_code = explode(' ', $http_response_header[0]);
					$this->log('NotCorrespondSendMail custom_search Error url:'.$search_url.' status_code:'.$status_code[1]);
				}else{
					$this->log('NotCorrespondSendMail custom_search Error url:'.$search_url.' timeout');
				}
				break;
			}

			// 検索内容をDBに保存
			if(empty($search_data['id'])){
				if($this->NotCorrespondSearchLog->save($search_data, array('callbacks'=>false))){
					$search_data['id'] = $this->NotCorrespondSearchLog->getInsertID();
				}
			}

			// 検索結果を取得
			$search_results = json_decode($contents, true);
			if(!empty($search_results['items'])){
				foreach($search_results['items'] as $item){
					$host = parse_url($item['link'], PHP_URL_HOST);
					$result[] = array(
							'not_correspond_search_log_id' => $search_data['id'],
							'jis_cd' => !empty($search_data['jis_cd']) ? $search_data['jis_cd'] : null,
							'genre_id' => !empty($search_data['genre_id']) ? $search_data['genre_id'] : null,
							'query' => $search_results['queries']['request'][0]['searchTerms'],
							'title' => $item['title'],
							'snippet' => $item['snippet'],
							'link' => $item['link'],
							'host' => $host,
							'domain' => $this->__get_domain($host),
							'modified_user_id' => self::$user,
							'modified' => date("Y/m/d H:i:s"),
							'created_user_id' => self::$user,
							'created' => date("Y/m/d H:i:s"),
					);
				}
			}

			if(!empty($search_results['queries']['nextPage'])){
				$startIndex = $search_results['queries']['nextPage'][0]['startIndex'];
			}else{
				// 次に取得するデータが存在しない
				break;
			}
		}

		if(!empty($result)){
			// 検索結果をDBに保存
			$this->NotCorrespondSearchResult->saveAll($result, array('callbacks'=>false));
		}
		return $result;
	}

	/**
	 * URLからドメインを取得する
	 * @param string $url URL
	 * @return URLのドメイン
	 */
	private function __get_domain($url){
		$base_url = $url;
		preg_match_all('/\.([a-z0-9\-_]*)/m', $base_url, $matches);

		$match = $matches[0];
		$match_sub =  $matches[1];
		$matches_count = count($match);

		switch($matches_count){
			case 0:
			case 1:
				break;
			case 2:
				$is_match = preg_match('/\.(co|or|gr|ne|go|lg|ac|ed|ad)$/m', $match[0]);
				if(!$is_match) $base_url =  implode('.', array($match_sub[0], $match_sub[1]));
				break;
			default:
				$is_match = preg_match('/\.(co|or|gr|ne|go|lg|ac|ed|ad)$/m', $match[$matches_count-2]);
				if(!$is_match)
					$base_url =  implode('.', array($match_sub[$matches_count-2], $match_sub[$matches_count-1]));
				else
					$base_url =  implode('.', array($match_sub[$matches_count-3], $match_sub[$matches_count-2], $match_sub[$matches_count-1]));
				break;
		}

		return $base_url;
	}


}