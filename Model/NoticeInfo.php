<?php
App::uses('AppModel', 'Model');

/**
 * NoticeInfo Model
 */
class NoticeInfo extends AppModel {

	public $recursive = -1;

	// 2017.03.16 izumi.s ORANGE-350 CHG(S)
	public $virtualFields = array(
			'status' => '1',
			'unread' => '1',
			'unanswered' => '1'
	);

	// 2017.03.16 izumi.s ORANGE-350 CHG(E)
	// 2017.03.16 izumi.s ORANGE-350 CHG(S)
	/**
	 * Validation rules
	 *
	 * @var array
	 */
	public $validate = array(
			'info_title' => array(
					'NotEmpty' => array(
							'required' => true,
							'allowEmpty' => false,
							'rule' => array('notEmpty'),
					),
					'OverMaxLength100' => array(
							'rule' => array('maxLength', 100),
							'last' => true, // Stop validation after this rule
					),
			),
			'info_contents' => array(
					'NotEmpty' => array(
							'required' => true,
							'allowEmpty' => false,
							'rule' => array('notEmpty'),
					),
					'OverMaxLength2000' => array(
							'rule' => array('maxLength', 2000),
							'last' => true, // Stop validation after this rule
					),
			),
			'choices' => array(
					'NotEmpty' => array(
							'required' => false,
							'allowEmpty' => true,
							'rule' => array('notEmpty'),
					),
					'OverMaxLength100' => array(
							'rule' => array('maxLength', 100),
							'last' => true, // Stop validation after this rule
					),
			)
	);
	// 2017.03.16 izumi.s ORANGE-350 CHG(E)

	/**
	 * 記事を操作するユーザーの情報
	 * @var type 
	 */
	private $_user = array();

	/**
	 * 記事を操作するユーザーの加盟店情報
	 * @var type 
	 */
	private $_corp = array();

	/**
	 * 応答メッセージ
	 * @var type 
	 */
	private $_flash_message = "";

	/**
	 * 操作結果によるメッセージを取得する
	 * @return type
	 */
	public function getFlashMessage() {
		return $this->_flash_message;
	}

	/**
	 * ユーザー情報をセットする。同時に加盟店情報も登録される。
	 * @param type $user
	 * @return boolean
	 */
	public function setUser($user) {
		if (empty($user)) {
			return false;
		}
		$this->_user = $user;
		App::import('Model', 'MCorp');
		$MC = new MCorp();
		$this->_corp = $MC->find('first', array(
				'fields' => array('id', 'corp_commission_type'),
				'conditions' => array(
						'id' => Hash::get($this->_user, 'affiliation_id')
				)
		));
	}

	/**
	 * 検索前の条件補正をかける
	 * @param string $queryData
	 * @return type
	 */
	public function beforeFind($queryData) {
		if ($this->findQueryType != 'count') {
			//第二ソート条件にid降順を追加
			$queryData['order'][] = array('NoticeInfo.id' => 'desc');
		}
		// 2017.03.16 izumi.s ORANGE-350 CHG(S)
		if ($this->isPoster()) {
			return parent::beforeFind($this->beforeFindForPoster($queryData));
		} elseif ($this->isReader()) {

			return parent::beforeFind($this->beforeFindForReader($queryData));
		} else {
			throw new UnauthorizedException(__('NoReferenceAuthority', true));
		}
		// 2017.03.16 izumi.s ORANGE-350 CHG(E)
	}

	/**
	 * 投稿者かどうかを判定する。
	 * @return boolean
	 */
	public function isPoster() {
		// 2017.03.16 izumi.s ORANGE-350 CHG(S)
		switch (Hash::get($this->_user, 'auth')) {
			case 'system':
			case 'admin':
			case 'accounting_admin':
				return true;
			default:
				return false;
		}
		// 2017.03.16 izumi.s ORANGE-350 CHG(E)
	}

	// 2017.03.16 izumi.s ORANGE-350 ADD(S)
	/**
	 * 投稿者かどうかを判定する。
	 * @return boolean
	 */
	public function isReader() {
		switch (Hash::get($this->_user, 'auth')) {
			case 'affiliation':
				return true;
			default:
				return false;
		}
	}

	// 2017.03.16 izumi.s ORANGE-350 ADD(E)

	/**
	 * 閲覧者向けの検索条件補正をかける
	 * @param array $queryData
	 * @return array
	 */
	private function beforeFindForReader($queryData) {
		// 閲覧者は削除済の記事は参照できない
		$queryData['conditions']['NoticeInfo.del_flg'] = 0;
		// 閲覧者は企業取次形態の指定が無いか、自分の指定と同じでなければならない
		$queryData['conditions']['OR'] = array(
				array('AND' => array(
								'NoticeInfo.corp_commission_type' => Hash::get($this->_corp, 'MCorp.corp_commission_type'),
								'NoticeInfo.is_target_selected' => false,
						)),
				array('AND' => array(
								'NoticeInfo.corp_commission_type' => null,
								'NoticeInfo.is_target_selected' => false,
						)),
				array('AND' => array(
								'NoticeInfo.is_target_selected' => true,
								'EXISTS (SELECT 1 FROM notice_info_targets WHERE notice_info_targets.notice_info_id = NoticeInfo.id AND notice_info_targets.corp_id = ' . Hash::get($this->_corp, 'MCorp.id') . ')'
						)),
		);
		// 2017.03.16 izumi.s ORANGE-350 CHG(S)
		// VirtualFieldを設定
		$this->virtualFields['status'] = 'CASE '
						. ' WHEN (NoticeInfo.choices IS NOT NULL AND MCorpsNoticeInfo.answer_value IS NULL) THEN 3 '
						. ' WHEN MCorpsNoticeInfo.id IS NULL THEN 2 '
						. ' ELSE 1 END';
		$this->virtualFields['unread'] = 'MCorpsNoticeInfo.id IS NULL';
		$this->virtualFields['unanswered'] = '(NoticeInfo.choices IS NOT NULL AND MCorpsNoticeInfo.answer_value IS NULL)';
		if (!in_array('MCorpNoticeInfo', Hash::extract($queryData, 'joins.{n}.alias'))) {
			$queryData['joins'][] = array(
					'type' => 'LEFT',
					'table' => 'm_corps_notice_infos',
					'alias' => 'MCorpsNoticeInfo',
					'conditions' => array(
							'NoticeInfo.id = MCorpsNoticeInfo.notice_info_id',
							'MCorpsNoticeInfo.m_corp_id' => Hash::get($this->_corp, 'MCorp.id'),
					),
			);
			$queryData['fields'] = array(
					'NoticeInfo.id',
					'NoticeInfo.info_title',
					'NoticeInfo.info_contents',
					'NoticeInfo.created',
					'NoticeInfo.status',
					'NoticeInfo.unread',
					'NoticeInfo.unanswered',
					'NoticeInfo.choices',
					'MCorpsNoticeInfo.answer_value',
					'MCorpsNoticeInfo.answer_user_id',
					'MCorpsNoticeInfo.answer_date',
			);
		}
		// 2017.03.16 izumi.s ORANGE-350 CHG(E)
		return $queryData;
	}

	/**
	 * 投稿者向けの検索条件補正をかける
	 * @param array $queryData
	 * @return array
	 */
	private function beforeFindForPoster($queryData) {
		if (empty($queryData['conditions']['NoticeInfo.del_flg'])) {
			$queryData['conditions']['NoticeInfo.del_flg'] = 0;
		}
		return $queryData;
	}

	/**
	 * 登録後2週間以内の未読の掲示板の件数を取得する
	 * @param array $corp
	 * @deprecated since version ALL corp_idから取得できる countUnreadByCorpId を利用してください
	 */
	public function countByUnread($corp) {
		return $this->countUnreadByCorpId(Hash::get($corp, 'MCorp.id'));
	}

	/**
	 * 登録後2週間以内の未読の掲示板の件数を取得する
	 * @param string $corp_id 加盟店ID
	 */
	public function countUnreadByCorpId($corp_id) {
		// 2017.03.16 izumi.s ORANGE-350 CHG(S)
		App::import('Model', 'MCorp');
		$MC = new MCorp();
		$corp = $MC->find('first', array('fields' => array('corp_commission_type', 'created'), 'conditions' => array('id' => $corp_id)));
		$query = array(
				'conditions' => array(
						array('NoticeInfo.created >= ' => date('Y-m-d', strtotime('-2 week'))),
				),
		);
		if (Hash::check($corp, 'MCorp.created')) {
			$query = Hash::merge($query, array(
									'conditions' => array(
											array('NoticeInfo.created >= ' => date('Y-m-d', strtotime('-2 week'))),
											array('NoticeInfo.created >= ' => $corp['MCorp']['created']),
											'OR' => array(
													'NoticeInfo.corp_commission_type IS NULL',
													'NoticeInfo.corp_commission_type' => $corp['MCorp']['corp_commission_type'],
											),
											'MCorpNoticeInfo.id' => NULL,
									),
									'joins' => array(
											array(
													'type' => 'left',
													'table' => 'm_corps_notice_infos',
													'alias' => 'MCorpNoticeInfo',
													'conditions' => array(
															'NoticeInfo.id = MCorpNoticeInfo.notice_info_id',
															'MCorpNoticeInfo.m_corp_id' => $corp_id,
													)
											),
									),
			));
		}
		// 2017.03.16 izumi.s ORANGE-350 CHG(E)

		$count = $this->find('count', $query);
		return $count;
	}

// murata.s ORANGE-517 ADD(S)
	/**
	 * 未回答の掲示板数を取得する
	 * @param int $corp_id 企業ID
	 */
	public function countUnansweredByCorpId($corp_id = null){
		App::import('Model', 'MCorp');

		$mc = new MCorp();
		$corp = $mc->find('first', array(
				'fields' => array('corp_commission_type', 'created'),
				'conditions' => array('id' => $corp_id)
		));

		$query = array(
				'conditions' => array(
						array(
								'NoticeInfo.created >= ' => $corp['MCorp']['created'],
								'NoticeInfo.choices != ' => null,
						),
						'OR' => array(
								'NoticeInfo.corp_commission_type' => null,
								'NoticeInfo.corp_commission_type' => $corp['MCorp']['corp_commission_type'],
						),
						'MCorpNoticeInfo.answer_value' => null,
				),
				'joins' => array(
						array(
								'type' => 'left',
								'table' => 'm_corps_notice_infos',
								'alias' => 'MCorpNoticeInfo',
								'conditions' => array(
										'NoticeInfo.id = MCorpNoticeInfo.notice_info_id',
										'MCorpNoticeInfo.m_corp_id' => $corp_id,
								)
						)
				)
		);

		return $this->find('count', $query);
	}
// murata.s ORANGE-517 ADD(E)

        /**
         * 2017.4.14 ichino ORANGE-381 ADD start
	 * 未読、未回答の掲示版の件数を取得する
	 * @param string $corp_id 加盟店ID
	 */
        public function countUnreadUnreadByCorpId($corp_id) {
            $query = array(
		'conditions' => array(                    
                    'or' => array(
                        'and' => array(
                            'not' => array("NoticeInfo.choices" => null),
                            "MCorpNoticeInfo.answer_value" => null
                        ),
                        array(
                           "MCorpNoticeInfo.id" => null,
                        ),
                    ),
               ),
               'joins' => array(
                   array(
				'type' => 'left',
				'table' => 'm_corps_notice_infos',
				'alias' => 'MCorpNoticeInfo',
				'conditions' => array(
						'NoticeInfo.id = MCorpNoticeInfo.notice_info_id',
						'MCorpNoticeInfo.m_corp_id' => $corp_id,
				)
                    ),
               ),
           );
            $count = $this->find('count', $query);
            return $count;
        }
        /*
         * 2017.4.14 ichino ORANGE-381 ADD end
         */

	/**
	 * 記事が未読かどうかを判定する
	 * @param type $id
	 * @return type
	 */
	public function isUnread($id) {
		App::import('Model', 'MCorpsNoticeInfo');
		$MCNI = new MCorpsNoticeInfo();
		$conditions = array('m_corp_id' => Hash::get($this->_corp, 'MCorp.id'), 'notice_info_id' => $id);
		return !$MCNI->hasAny($conditions);
	}

	/**
	 * 記事を既読にする
	 * @param type $id
	 * @return boolean
	 */
	public function markRead($id) {
		if ($this->isPoster()) {
			//投稿者は記事を開いても既読にしない
			return false;
		}
		if (empty($this->_corp['MCorp']['id'])) {
			//会社IDが無ければ既読にしない（できない）
			return false;
		}
		App::import('Model', 'MCorpsNoticeInfo');
		$MCNI = new MCorpsNoticeInfo();
		$conditions = array('m_corp_id' => Hash::get($this->_corp, 'MCorp.id'), 'notice_info_id' => $id);
		// 2017.03.16 izumi.s ORANGE-350 CHG(S)
		$MCNI->create();
		$data = $MCNI->find('first', array('conditions' => $conditions));
		$MCNI->set($data);
		// 2017.03.16 izumi.s ORANGE-350 CHG(E)
		return $MCNI->save($conditions);
	}

	/**
	 * POSTされたデータの追加・更新を行う
	 * @param array $data $this->request->data
	 * @return boolean 追加・更新に成功した場合true
	 * @throws ApplicationException
	 */
	public function merge($data) {
		// 2017.03.16 izumi.s ORANGE-350 CHG(S)
		if (!$this->isPoster()) {
			throw new UnauthorizedException(__('NoReferenceAuthority', true));
		}
		if (Hash::check($data, 'delete')) {
			$data['NoticeInfo']['del_flg'] = 1;
		} elseif ($this->hasAnswer(Hash::get($data, 'NoticeInfo.id'))) {
			$this->_flash_message = "回答データがあるため更新できません。";
			return false;
		}
		// 2017.03.16 izumi.s ORANGE-350 CHG(E)
		// 2017.03.16 izumi.s ORANGE-350 ADD(S)
		switch (Hash::get($data, 'target')) {
			case 'disp_1':
				$data['NoticeInfo']['is_target_selected'] = false;
				$data['NoticeInfoTarget']['corp_id'] = array();
				break;
			case 'disp_2':
				$data['NoticeInfo']['is_target_selected'] = true;
				$data['NoticeInfo']['corp_commission_type'] = null;
				break;
		}
		// 2017.03.16 izumi.s ORANGE-350 ADD(E)

		if (!$this->save($data)) {
			$this->_flash_message = 'InputError';
			return false;
		}

		// 2017.03.16 izumi.s ORANGE-350 CHG(S)
		App::import('Model', 'NoticeInfoTarget');
		if (Hash::get($data, 'delete')) {
			$this->_flash_message = "{$this->getID()}番の記事が削除されました。";
		} else {
			if ($this->getLastInsertID()) {
				$this->id = $this->getLastInsertID();
				$this->_flash_message = 'Inserted';
			} else {
				$this->_flash_message = 'Updated';
			}
			$NIT = new NoticeInfoTarget();
			$NIT->saveByNoticeInfoIdAndCorpIds($this->id, Hash::get($data, 'NoticeInfoTarget.corp_id'));
		}
		// 2017.03.16 izumi.s ORANGE-350 CHG(E)
		return true;
	}

	/**
	 * CSVダウンロード用のデータを返す
	 * @return type
	 */
	public function findAnswerCSV($id) {

		App::import('Model', 'MCorpsNoticeInfo');
		$MCNI = new MCorpsNoticeInfo();
		$data = $MCNI->find('all', array(
				'fields' => array_keys($this->csvFormat['answer']),
				'conditions' => array(
						'MCorpsNoticeInfo.notice_info_id' => $id,
						'NOT' => array('MCorpsNoticeInfo.answer_value' => null)
				),
				'joins' => array(
						array(
								'type' => 'LEFT',
								'table' => 'm_corps',
								'alias' => 'MCorp',
								'conditions' => 'MCorp.id = MCorpsNoticeInfo.m_corp_id'
						),
						array(
								'type' => 'LEFT',
								'table' => 'm_users',
								'alias' => 'MUser',
								'conditions' => 'MUser.user_id = MCorpsNoticeInfo.answer_user_id'
						),
				),
		));
		return $data;
	}

	/**
	 * CSV項目、CSVComponentで利用される
	 * @var array $csvFormat
	 */
	public $csvFormat = array(
			'answer' => array(
					'MCorp.id' => '企業ID',
					'MCorp.official_corp_name' => '正式企業名',
					'MCorp.corp_name_kana' => '企業名ふりがな',
					'MCorpsNoticeInfo.answer_value' => '回答内容',
					'MCorpsNoticeInfo.answer_date' => '回答日時',
					'MCorpsNoticeInfo.answer_value' => '回答内容',
					'MUser.user_name' => '回答者名',
			)
	);

	private function hasAnswer($id) {
		if ($id === null) {
			return false;
		}
		App::import('Model', 'MCorpsNoticeInfo');
		$MCNI = new MCorpsNoticeInfo();
		$data = $MCNI->find('count', array('conditions' => array('MCorpsNoticeInfo.notice_info_id' => $id, 'NOT' => array('MCorpsNoticeInfo.answer_value' => null))));
		return ($data > 0);
	}

}
