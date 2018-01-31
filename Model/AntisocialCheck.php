<?php

class AntisocialCheck extends AppModel {

	public $name = 'AntisocialCheck';

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
	 * 反社チェックのリストを返す
	 * @return string
	 */
	public function getResultList() {
		$results = array(
				'None' => '未実施',
				'OK' => '実施済(OK)',
				'NG' => '実施済(NG)',
				'Inadequate' => '書類不備'
		);
		return $results;
	}

	/**
	 * 反社チェック実施月の一覧を返す
	 * @return string
	 */
	public function getMonthList() {
		$months = array(
				1 => '1月',
				2 => '2月',
				3 => '3月',
				4 => '4月',
				5 => '5月',
				6 => '6月',
				7 => '7月',
				8 => '8月',
				9 => '9月',
				10 => '10月',
				11 => '11月',
				12 => '12月',
		);
		return $months;
	}

	/**
	 * find関連の操作の前に呼ばれる。paginateが複数条件のソートに対応していないためここで指定する。
	 * @param string $queryData
	 * @return string
	 */
	public function beforeFind($queryData) {
		parent::beforeFind($queryData);
		if ($this->findQueryType != 'count' && isset($this->virtualFields['last_antisocial_date'])) {
			$queryData['order'] = array('antisocial_date_is_null' => 'desc', 'last_antisocial_date' => 'asc', 'corp_id' => 'asc');
		}
		return $queryData;
	}

	/**
	 * 反社チェック一覧用のオプションを取得する
	 * @return array
	 */
	public function getOptions() {
		$this->virtualFields = array(
				'last_antisocial_date' => 'MAX(AntisocialCheck.date)',
				'antisocial_check_month' => "concat(MCorp.antisocial_check_month,'月')",
				'antisocial_date_is_null' => '(MAX(AntisocialCheck.date) Is NULL)',
				'corp_id' => 'MCorp.id',
		);
		$options = array(
				'AntisocialCheck' => array(
						'fields' => array(
								'MCorp.id',
								'MCorp.official_corp_name',
								'MCorp.commission_dial',
								'antisocial_check_month',
								'last_antisocial_date',
						),
						'joins' => array(
								array(
										'type' => 'RIGHT',
										'table' => 'm_corps',
										'alias' => 'MCorp',
										'conditions' => 'MCorp.id = AntisocialCheck.corp_id'
								)
						),
						'conditions' => array(
								'MCorp.id not in (1751, 1755, 3539)',
								'MCorp.affiliation_status' => 1,
								'NOT' => array('COALESCE(MCorp.corp_commission_status,0)' => 2),
								'MCorp.del_flg' => 0,
								'MCorp.last_antisocial_check' => 'OK',
								'OR' => array(
										"now() >= to_timestamp((date_part('year', AntisocialCheck.date)+CASE WHEN date_part('month', AntisocialCheck.date) < MCorp.antisocial_check_month THEN 0 ELSE 1 END) || '/' || MCorp.antisocial_check_month || '/1','YYYY/MM/DD')",
										'AntisocialCheck.id IS NULL'
								),
								'NOT EXISTS( SELECT 1 FROM antisocial_checks WHERE antisocial_checks.corp_id = AntisocialCheck.corp_id AND antisocial_checks.created > AntisocialCheck.created )'
						),
						'group' => array('MCorp.id'),
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

	/**
	 * paginate用のオプションを返す
	 */
	public function getPaginate() {
		$options = $this->getOptions();
		$options['AntisocialCheck']['limit'] = Configure::read('list_limit');
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
				'created_user' => 'COALESCE(MUser.user_name, AntisocialCheck.created_user_id)'
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
												'conditions' => 'MUser.user_id = AntisocialCheck.created_user_id'
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
		$options['AntisocialCheck']['fields'] = array_keys($this->csvFormat['antisocial_follow']);
		return $this->find('all', $options['AntisocialCheck']);
	}

	/**
	 * CSV項目、CSVComponentで利用される
	 * @var array $csvFormat
	 */
	public $csvFormat = array(
			'antisocial_follow' => array(
					'MCorp.id' => '企業ID',
					'MCorp.official_corp_name' => '正式企業名',
					'MCorp.corp_name_kana' => '企業名ふりがな',
					'AntisocialCheck.last_antisocial_date' => '反社チェック更新日時（前回）',
					'MCorp.commission_dial' => '取次用ダイヤル',
			)
	);

}
