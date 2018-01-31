<?php
App::uses('AppShell', 'Console/Command');

class CreateNotCorrespondShell extends AppShell{

	public $uses = array('DemandInfo', 'MPost', 'NotCorrespond', 'NotCorrespondLog', 'NotCorrespondItem');
	public $tasks = array();

	private static $user = 'system';

	/**
	 * エリア対応加盟店なし案件データ作成
	 */
	public function main(){

		try{
			$this->log('エリア対応加盟店なし案件データの作成', SHELL_LOG);
			$this->execute();
			$this->log('エリア対応加盟店なし案件データの作成 終了', SHELL_LOG);

		}catch(Exception $e){
			$this->log($e, SHELL_LOG);
		}
	}

	/**
	 * エリア対応加盟店なし案件を抽出し、テーブルに登録する
	 */
	private function execute(){
		$result = true;
		try{
			$this->NotCorrespond->begin();
			$this->NotCorrespondLog->begin();

			// 登録済みエリア対応加盟店なし案件を取得
			$registed_not_corresponds = $this->NotCorrespond->find('all');

			// エリア対応加盟店なし案件を取得
			$not_corresponds = $this->__get_demandInfos();
			$save_data = array();
			$update_ids = array();
			if(!empty($not_corresponds)){
				// エリア対応加盟店なし案件情報を登録
				foreach($not_corresponds as $val){

					if(!empty($val['NotCorrespond']['id'])){
						// 更新の場合
						$save_data[] = array(
								'id' => $val['NotCorrespond']['id'],
								'prefecture_cd' => $val['DemandInfo']['address1'],
								'jis_cd' => $val['MPost']['jis_cd'],
								'genre_id' => $val['DemandInfo']['genre_id'],
								'not_correspond_count_year' => $val['DemandInfo']['not_correspond_count_year'],
								'not_correspond_count_latest' => $val['DemandInfo']['not_correspond_count_latest'],
								'import_date' => date('Y-m-d'),
								'modified_user_id' => self::$user
						);
						// 更新対象となるIDを一時取得
						$update_ids[] = $val['NotCorrespond']['id'];
					}else{
						// 新規追加の場合
						$save_data[] = array(
								'prefecture_cd' => $val['DemandInfo']['address1'],
								'jis_cd' => $val['MPost']['jis_cd'],
								'genre_id' => $val['DemandInfo']['genre_id'],
								'not_correspond_count_year' => $val['DemandInfo']['not_correspond_count_year'],
								'not_correspond_count_latest' => $val['DemandInfo']['not_correspond_count_latest'],
								'import_date' => date('Y-m-d'),
								'modified_user_id' => self::$user,
								'created_user_id' => self::$user
						);
					}
				}
				// 新規登録 or 更新
				foreach($save_data as $val){
					$this->NotCorrespond->create();
					$this->NotCorrespondLog->create();
					if($this->NotCorrespond->save($val, array('callbacks' => false))){
						$log = array(
								'not_correspond_id' => !empty($val['id']) ? $val['id'] : $this->NotCorrespond->getInsertID(),
								'prefecture_cd' => $val['prefecture_cd'],
								'jis_cd' => $val['jis_cd'],
								'genre_id' => $val['genre_id'],
								'not_correspond_count_year' => $val['not_correspond_count_year'],
								'not_correspond_count_latest' => $val['not_correspond_count_latest'],
								'import_date' => $val['import_date'],
								'modified_user_id' => self::$user,
								'created_user_id' => self::$user
						);
						if(!$this->NotCorrespondLog->save($log, array('callbacks'=>false))){
							$result = false;
							break;
						}
					}else{
						$result = false;
						break;
					}
				}
			}

			if($result){
				// 削除対象となるデータを削除
				$delete_data = array();
				foreach($registed_not_corresponds as $val){
					if(array_search($val['NotCorrespond']['id'], $update_ids) === false)
						$delete_data[] = $val['NotCorrespond']['id'];
				}
				if(!empty($delete_data)){
					if(!$this->NotCorrespond->deleteAll(array('id'=>$delete_data))){
						$result = false;
						break;
					}
				}
			}

			if($result){
				$this->NotCorrespond->commit();
				$this->NotCorrespondLog->commit();
			}else{
				$this->NotCorrespond->rollback();
				$this->NotCorrespondLog->rollback();
				$this->log('登録済みエリア対応加盟店更新エラー', SHELL_LOG);
			}


		}catch(Exception $e){
			$this->NotCorrespond->rollback();
			$this->NotCorrespondLog->rollback();
			$this->log('登録済みエリア対応加盟店登録時に例外が発生', SHELL_LOG);
			throw $e;
		}
	}

	/**
	 * エリア対応加盟店なし案件をジャンル、市町村コード別に抽出する
	 */
	private function __get_demandInfos(){

		// 抽出期間の取得
		$setting = $this->NotCorrespondItem->find('first', array('order'=>array('id'=>'desc')));

		// DemandInfoのアソシエーションを一旦解除
		$this->DemandInfo->unbindModelAll();

		$dbo = $this->DemandInfo->getDataSource();
		$MPost = $dbo->buildStatement(array(
				'fields' => array('jis_cd', 'address1', 'address2'),
				'table' => 'm_posts',
				'alias' => 'MPostA',
				'group' => array('jis_cd', 'address1', 'address2')
		), $this->MPost);

		$fields = array(
				'MPost.jis_cd',
				'DemandInfo.address1',
				'DemandInfo.address2',
				'DemandInfo.genre_id',
				'NotCorrespond.id',
				'count("DemandInfo".*) as "DemandInfo__not_correspond_count_year"',
				'count("DemandInfoLatest".*) as "DemandInfo__not_correspond_count_latest"'
		);
		$joins = array(
				array(
						'type' => 'left',
						'table' => 'demand_infos',
						'alias' => 'DemandInfoLatest',
						'conditions' => array(
								'DemandInfoLatest.id = DemandInfo.id',
								'DemandInfoLatest.order_fail_reason' => 38,
								//"cast(DemandInfoLatest.receive_datetime as date) > (select cast( now() as date)  - cast('1 month' as interval) as date)"
								"cast(DemandInfoLatest.receive_datetime as date) > (select cast( now() as date)  - cast('".$setting['NotCorrespondItem']['immediate_date']." day' as interval) as date)"
						)
				),
				array(
						'table' => 'm_address1',
						'alias' => 'MAddress1',
						'conditions' => array(
								'MAddress1.address1_cd = DemandInfo.address1'
						)
				),
				array(
						'table' => "({$MPost})",
						'alias' => 'MPost',
						'conditions' => array(
								'MPost.address1 = MAddress1.address1',
								'MPost.address2 = DemandInfo.address2'
							)
				),
				array(
						'type' => 'left',
						'table' => 'not_corresponds',
						'alias' => 'NotCorrespond',
						'conditions' => array(
								'NotCorrespond.jis_cd = MPost.jis_cd',
								'NotCorrespond.prefecture_cd = DemandInfo.address1',
								'NotCorrespond.genre_id = DemandInfo.genre_id'
						)
				),
		);
		$conditions = array(
				'DemandInfo.address1 != ' => Util::getDivValue('prefecture_div', 'humei'),
 				'DemandInfo.order_fail_reason' => 38,
				"cast(DemandInfo.receive_datetime as date) > (select cast( now() as date)  - cast('1 years' as interval) as date)"
		);
		$group = array(
				'MPost.jis_cd',
				'DemandInfo.address1',
				'DemandInfo.address2',
				'DemandInfo.genre_id',
				'NotCorrespond.id'
		);

		$this->DemandInfo->virtualFields['not_correspond_count_year'] = 0;
		$this->DemandInfo->virtualFields['not_correspond_count_latest'] = 0;


		$results = $this->DemandInfo->find('all', array(
				'fields' => $fields,
				'joins' => $joins,
				'conditions' => $conditions,
				'group' => $group
		));
		return $results;

	}
}