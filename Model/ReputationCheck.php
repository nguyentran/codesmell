<?php

class ReputationCheck extends AppModel {

	public $name = 'ReputationCheck';

	public $recursive = -1;

	/**
	 * 更新時の検証ルール
	 * @var type 
	 */
	public $validate = array(
			'date' => array(
					'NotEmpty' => array(
							'rule' => 'notEmpty',
							'last' => true,
					),
			),
	);

	/**
	 * find関連の操作の前に呼ばれる。paginateが複数条件のソートに対応していないためここで指定する。
	 * @param string $queryData
	 * @return string
	 */
	public function beforeFind($queryData) {
		parent::beforeFind($queryData);
		if ($this->findQueryType != 'count' && isset($this->virtualFields['last_reputation_date'])) {
			$queryData['order'] = array('reputation_date_is_null' => 'desc', 'last_reputation_date' => 'asc', 'corp_id' => 'asc');
		}
		return $queryData;
	}

	/**
	 * 風評チェック一覧用のオプションを取得する
	 * @return array
	 */
	public function getOptions() {
		$this->virtualFields = array(
				'last_reputation_date' => 'MAX(ReputationCheck.date)',
				'reputation_check_month' => "concat(((MCorp.antisocial_check_month+4)%12+1),'月')",
				'reputation_date_is_null' => '(MAX(ReputationCheck.date) Is NULL)',
				'corp_id' => 'MCorp.id',
		);
		$options = array(
				'ReputationCheck' => array(
						'fields' => array(
								'MCorp.id',
								'MCorp.official_corp_name',
								'MCorp.commission_dial',
								'reputation_check_month',
								'last_reputation_date',
						),
						'joins' => array(
								array(
										'type' => 'RIGHT',
										'table' => 'm_corps',
										'alias' => 'MCorp',
										'conditions' => 'MCorp.id = ReputationCheck.corp_id'
								)
						),
						'conditions' => array(
								'MCorp.id not in (1751, 1755, 3539)',
								'MCorp.affiliation_status' => 1,
								'NOT' => array('COALESCE(MCorp.corp_commission_status,0)' => 2),
								'MCorp.del_flg' => 0,
								'MCorp.last_antisocial_check' => 'OK',
								'OR' => array(
										"now() >= to_timestamp((date_part('year', ReputationCheck.date)+CASE WHEN date_part('month', ReputationCheck.date) < ((MCorp.antisocial_check_month+4)%12+1) THEN 0 ELSE 1 END) || '/' || ((MCorp.antisocial_check_month+4)%12+1) || '/1','YYYY/MM/DD')",
										'AND' => array(
												'ReputationCheck.id IS NULL',
												"now() >= (SELECT MIN(DATE_TRUNC('month',antisocial_checks.date + interval '4 month')) FROM antisocial_checks WHERE MCorp.id = antisocial_checks.corp_id )",
										)
								),
								'NOT EXISTS( SELECT 1 FROM reputation_checks WHERE reputation_checks.corp_id = ReputationCheck.corp_id AND reputation_checks.created > ReputationCheck.created )'
						),
						'group' => array('corp_id'),
				),
				'MCorp' => array(
				),
		);
		return $options;
	}

	/**
	 * 更新を行う
	 * @param type $data コントローラーから$this->Request->data
	 * @param type $auth コントローラーから$this->Auth->user('auth')
	 * @return type
	 * @throws ApplicationException
	 */
	public function update($data, $auth) {
		if (!$this->is_update_authority($auth)) {
			throw new ApplicationException(__('NoReferenceAuthority', true));
		}
		if (!isset($data['check'])) {
			return false;
		}
		$datasource = $this->getDataSource();
		$datasource->begin();
		$result = true;
		foreach ($data['check'] as $corp_id) {
			$checkdata = array('corp_id' => $corp_id, 'date' => date('Y-m-d H:i:s'));
			$this->create();
			$result &= $this->save($checkdata);
		}
		if ($result) {
			$datasource->commit();
		} else {
			$datasource->rollback();
		}
		return $result;
	}

	public function findListByCorpId($corp_id) {
		return $this->find('list', array('conditions' => array('corp_id' => $corp_id), 'fields' => array('date')));
	}

	/**
	 * paginate用のオプションを返す
	 */
	public function getPaginate() {
		$options = $this->getOptions();
		$options['ReputationCheck']['limit'] = Configure::read('list_limit');
		return $options;
	}

	/**
	 * 権限が更新可能かどうかを返す
	 * @param type $auth
	 * @return type
	 */
	public function is_update_authority($auth) {
		return in_array($auth, array('system', 'admin', 'accounting_admin'));
	}

	/**
	 * 指定した加盟店のチェック履歴を返す
	 * @param type $corp_id
	 * @return type
	 */
	public function findHistoryByCorpId($corp_id, $type = 'first') {
		$this->virtualFields = array(
				'created_user' => 'COALESCE(MUser.user_name, ReputationCheck.created_user_id)'
		);
		return $this->find($type, array(
								'fields' => array(
										'date',
										'created_user'
								),
								'joins' => array(
										array(
												'type' => 'LEFT',
												'table' => 'm_users',
												'alias' => 'MUser',
												'conditions' => 'MUser.user_id = ReputationCheck.created_user_id'
										),
								),
								'conditions' => array(
										'corp_id' => $corp_id
								),
								'order' => array(
										'date' => 'desc'
								),
		));
	}

	/**
	 * CSVダウンロード用のデータを返す
	 * @return type
	 */
	public function findCSV() {
		$options = $this->getOptions();
		$options['ReputationCheck']['fields'] = array_keys($this->csvFormat['reputation_follow']);
		return $this->find('all', $options['ReputationCheck']);
	}

	/**
	 * CSV項目、CSVComponentで利用される
	 * @var array $csvFormat
	 */
	public $csvFormat = array(
			'reputation_follow' => array(
					'MCorp.id' => '企業ID',
					'MCorp.official_corp_name' => '正式企業名',
					'MCorp.corp_name_kana' => '企業名ふりがな',
					'ReputationCheck.last_reputation_date' => '風評チェック更新日時（前回）',
					'MCorp.commission_dial' => '取次用ダイヤル',
			)
	);

}
