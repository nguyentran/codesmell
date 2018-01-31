<?php

class CorpAgreement extends AppModel {

	public $useTable = 'corp_agreement';
	// ORANGE-347 ADD (S)
	public $virtualFields = array('status_jp' => '',);

	/**
	 * 契約の進捗状況
	 * @var array
	 */
	public $status_list = array(
			'Step0' => array('name' => 'Step0', 'jpname' => '未確認', 'prev' => null, 'next' => 'step1'),
			'Step1' => array('name' => 'Step1', 'jpname' => '契約内容確認中', 'prev' => 'Step0', 'next' => 'step2'),
			'Step2' => array('name' => 'Step2', 'jpname' => '契約内容確認中', 'prev' => 'Step1', 'next' => 'step3'),
			'Step3' => array('name' => 'Step3', 'jpname' => '契約内容確認中', 'prev' => 'Step2', 'next' => 'step4'),
			'Step4' => array('name' => 'Step4', 'jpname' => '契約内容確認中', 'prev' => 'Step3', 'next' => 'step5'),
			'Step5' => array('name' => 'Step5', 'jpname' => '契約内容確認中', 'prev' => 'Step4', 'next' => 'step6'),
			'Step6' => array('name' => 'Step6', 'jpname' => '契約内容確認中', 'prev' => 'Step5', 'next' => 'Confirm'),
			'Confirm' => array('name' => 'Confirm', 'jpname' => '契約内容最終確認', 'prev' => 'Step6', 'next' => 'Application'),
			'Application' => array('name' => 'Application', 'jpname' => '同意申請完了', 'prev' => 'Confirm', 'next' => null),
			'Review' => array('name' => 'Review', 'jpname' => '申請審査中', 'prev' => null, 'next' => null),
			'PassBack' => array('name' => 'PassBack', 'jpname' => '差戻し中', 'prev' => null, 'next' => null),
			'Complete' => array('name' => 'Complete', 'jpname' => '契約完了', 'prev' => null, 'next' => null),
			'NotSigned' => array('name' => 'NotSigned', 'jpname' => '未締結', 'prev' => null, 'next' => null),
			'Reconfirmation' => array('name' => 'Reconfirmation', 'jpname' => '契約再確認申請', 'prev' => null, 'next' => null),
			'Resigning' => array('name' => 'Resigning', 'jpname' => '再契約申請', 'prev' => null, 'next' => null),
	);
	public $corp_id;

	public function __construct($id = false, $table = null, $ds = null) {
		parent::__construct($id, $table, $ds);
		$this->virtualFields['status_jp'] = 'CASE status ' . implode('', Hash::format($this->status_list, array('{s}.name', '{s}.jpname'), 'WHEN \'%1$s\' THEN \'%2$s\' ')) . 'ELSE \' \' END';
	}

	public function getLastAcceptationDateByCorpId($corp_id) {
		$this->virtualFields['last_acceptation_date'] = 'MAX(acceptation_date)';
		$result = $this->find('first', array('fields' => array('last_acceptation_date'),));
		unset($this->virtualFields['last_acceptation_date']);
		return Hash::get($result, 'CorpAgreement.last_acceptation_date', '1900-01-01 00:00:00');
	}

	public function setProcessingDataByCorpId($corp_id, $or_create = false) {
		$this->hasOne = array('CorpAgreementTempLink' => array('foreignKey' => 'corp_agreement_id'));
		$agreements = $this->find('first', array(
				'fields' => array('CorpAgreement.id', 'CorpAgreement.status', 'CorpAgreementTempLink.id', 'CorpAgreementTempLink.corp_id', 'CorpAgreementTempLink.corp_agreement_id'),
				'conditions' => array('CorpAgreement.corp_id' => $corp_id,),
				'order' => array('CorpAgreement.id' => 'desc'),
				'recursive' => 1)
		);
		if (!$agreements || Hash::get($agreements, 'CorpAgreement.status') == 'Complete') {
			if (!$or_create) {
				throw new ApplicationException('契約データが見つかりませんでした。');
			} else {
				$agreements = $this->create();
				$agreements['CorpAgreement']['corp_id'] = $corp_id;
				$agreements['CorpAgreement']['agreement_history_id'] = 0;
				$agreements['CorpAgreement']['ticket_no'] = 0;
				$agreements['CorpAgreementTempLink']['corp_id'] = $corp_id;
			}
		}
		return ($this->data = $agreements);
	}

	public function step0Done($request_data) {
		App::import('Model', 'Agreement');
		$this->Agreement = new Agreement();
		$agreement = $this->Agreement->find('first', array('order' => array('id' => 'desc')));
		$corp_agreement = $this->setProcessingDataByCorpId($this->corp_id, true);
		if (!Hash::get($corp_agreement, 'CorpAgreement.status')) {
			$corp_agreement['CorpAgreement']['status'] = 'Step0';
		}
		$corp_agreement['CorpAgreement']['ticket_no'] = $agreement['Agreement']['ticket_no'];
		$corp_agreement['CorpAgreement']['agreement_history_id'] = $agreement['Agreement']['last_history_id'];
		$corp_agreement['CorpAgreement']['version_no'] = $agreement['Agreement']['version_no'];
		$corp_agreement['CorpAgreement']['original_agreement'] = $this->Agreement->findFullPlainText();
		$corp_agreement['CorpAgreement']['customize_agreement'] = $this->Agreement->findCustomizedPlainText($this->corp_id);
		return $this->saveAssociated($corp_agreement);
	}

	public function step1Done($request_data) {
		$corp_agreement = $this->setProcessingDataByCorpId($this->corp_id, false);
		if (in_array($corp_agreement['CorpAgreement']['status'], array('Step0', 'Reconfirmation', 'Resigning'))) {
			$corp_agreement['CorpAgreement']['status'] = 'Step1';
		}
		return $this->save($corp_agreement);
	}

	public function step2Done($request_data) {
		$corp_agreement = $this->setProcessingDataByCorpId($this->corp_id, false);
		if ($corp_agreement['CorpAgreement']['status'] === 'Step1') {
			$corp_agreement['CorpAgreement']['status'] = 'Step2';
		}
		$corp_agreement['CorpAgreement']['corp_kind'] = Hash::get($request_data, 'MCorp.corp_kind');
		return $this->save($corp_agreement);
	}

	public function step3Done($request_data) {
		$corp_agreement = $this->setProcessingDataByCorpId($this->corp_id, false);
		if ($corp_agreement['CorpAgreement']['status'] === 'Step2') {
			$corp_agreement['CorpAgreement']['status'] = 'Step3';
		}
		return $this->save($corp_agreement);
	}

	public function step4Done($request_data) {
		$corp_agreement = $this->setProcessingDataByCorpId($this->corp_id, false);
		if ($corp_agreement['CorpAgreement']['status'] === 'Step3') {
			$corp_agreement['CorpAgreement']['status'] = 'Step4';
		}
		return $this->save($corp_agreement);
	}

	public function step5Done($request_data) {
		$corp_agreement = $this->setProcessingDataByCorpId($this->corp_id, false);
		if ($corp_agreement['CorpAgreement']['status'] === 'Step4') {
			$corp_agreement['CorpAgreement']['status'] = 'Step5';
		}
		return $this->save($corp_agreement);
	}

	public function step6Done($request_data) {
		$corp_agreement = $this->setProcessingDataByCorpId($this->corp_id, false);
		if ($corp_agreement['CorpAgreement']['status'] === 'Step5') {
			$corp_agreement['CorpAgreement']['status'] = 'Step6';
		}
		return $this->save($corp_agreement);
	}

	public function confirmDone($request_data) {
		App::import('Model', 'MCorp');
		$this->MCorp = new MCorp();
		if (!($mcorp = $this->MCorp->find('first', array('conditions' => array('id' => $this->corp_id), 'recursive' => -1)))) {
			throw new ApplicationException('加盟店データが見つかりませんでした。');
		}
		if (in_array($mcorp['MCorp']['commission_accept_flg'], array('2', '3'))) {
			//契約更新フラグが「2:契約未更新」あるいは「3:未更新STOP」の場合、ステータスを「Complete:契約完了」に、WEB規約を「契約済」に変更する。
			if (!$this->acceptDone($request_data)) {
				throw new ApplicationException('自動承認に失敗しました。');
			}
		} else {
			$corp_agreement = $this->setProcessingDataByCorpId($this->corp_id, false);
			if ($corp_agreement['CorpAgreement']['status'] === 'Step6') {
				$corp_agreement['CorpAgreement']['status'] = 'Application';
			}
			if (false === $this->save($corp_agreement)) {
				throw new ApplicationException('申請に失敗しました。');
			}

			$config_name = 'agreement_application_mail_setting';
			$to_mail = Util::getDivText($config_name, 'to_address');
			$from_mail = Util::getDivText($config_name, 'from_address');
			$subject = sprintf(Util::getDivText($config_name, 'title'), $this->corp_id);

			App::uses('HtmlHelper', 'View/Helper');
			App::uses('View', 'View');
			$Html = new HtmlHelper(new View());

			App::import('Component', 'Url');
			$agreement_full_url = $Html->url(array('controller' => 'affiliation', 'action' => 'agreement'), true);

			$body = sprintf(Util::getDivText($config_name, 'contents'), $agreement_full_url, $this->corp_id);

			$collection = new ComponentCollection();
			App::import('Component', 'CustomEmail');
			$CustomEmail = new CustomEmailComponent($collection);
			$CustomEmail->simple_send($subject, $body, $to_mail, $from_mail);
		}
	}

	/**
	 * 契約申請を承認し、申請中のデータを本番へ適用。
	 * MCorpCategoriesTemp → MCorpCategory
	 * MTargetAreasTemp    → MTargetArea
	 * @param type $request_data
	 */
	public function acceptDone($request_data) {

		// 更新ユーザーは「SYSTEM」固定
		$create_user_id = 'SYSTEM';

		$corp_agreement = $this->setProcessingDataByCorpId($this->corp_id, false);
		$temp_id = Hash::get($corp_agreement, 'CorpAgreementTempLink.id');

		$corp_agreement['CorpAgreement']['status'] = 'Complete';
		$corp_agreement['CorpAgreement']['acceptation_date'] = date('Y-m-d H:i:s');
		$corp_agreement['CorpAgreement']['acceptation_user_id'] = $create_user_id;
		$corp_agreement['CorpAgreement']['agreement_flag'] = true;

		// 必要なモデルオブジェクトの作成
		App::import('Model', 'MCorpCategoriesTemp');
		$MCorpCategoriesTemp = new MCorpCategoriesTemp();
		App::import('Model', 'MTargetAreasTemp');
		$MTargetAreasTemp = new MTargetAreasTemp();
		App::import('Model', 'MCorpCategory');
		$MCorpCategory = new MCorpCategory();
		App::import('Model', 'MTargetArea');
		$MTargetArea = new MTargetArea();
		$dbo = $this->getDataSource();

		// MCorpCategoriesTemp を取得、ついでに既存のMCorpCategoryも確認しておく
		$MCorpCategoriesTemp->unbindModelAll();
		$MCorpCategoriesTemp->virtualFields = false;
		$MCorpCategoriesTemp->bindModel(array(
				'hasOne' => array(
						'MCorpCategory' => array(
								'className' => 'MCorpCategory',
								'foreignKey' => false,
								'conditions' => array(
										'MCorpCategory.corp_id' => $this->corp_id,
										'MCorpCategoriesTemp.corp_id = MCorpCategory.corp_id',
										'MCorpCategoriesTemp.genre_id = MCorpCategory.genre_id',
										'MCorpCategoriesTemp.category_id = MCorpCategory.category_id',
								),
						),
				)
		));
		$m_corp_categories_temp_array = $MCorpCategoriesTemp->find('all', array(
				'conditions' => array('MCorpCategoriesTemp.corp_id' => $this->corp_id, 'MCorpCategoriesTemp.temp_id' => $temp_id),
				'recursive' => 1
		));

		// 本番カテゴリの更新対象IDを取得
		$update_corp_category_id_array = Hash::extract($m_corp_categories_temp_array, '{n}.MCorpCategory[id>0].id');

		// 本番カテゴリの削除対象IDを取得
		$delete_corp_category_id_array = Hash::extract($MCorpCategory->find('all', array(
				'fields' => array('MCorpCategory.id'),
				'conditions' => array(
						'corp_id' => $this->corp_id,
						'NOT' => array('MCorpCategory.id' => $update_corp_category_id_array)
				),
				'recursive' => -1
		)),'{n}.MCorpCategory.id');

		// 削除＆更新＆登録開始
		$this->begin();

		// 削除対象があれば削除を行う
		if (!empty($delete_corp_category_id_array)) {
			if (false === $MCorpCategory->deleteAll(array('MCorpCategory.id' => $delete_corp_category_id_array))) {
				$this->rollback();
				return false;
			}
			if (false === $MTargetArea->deleteAll(array('MTargetArea.corp_category_id' => $update_corp_category_id_array))) {
				$this->rollback();
				return false;
			}
		}

		foreach ($m_corp_categories_temp_array as &$m_corp_categories_temp) {
			$saving_corp_category_data = array(
					'id' => Hash::get($m_corp_categories_temp, 'MCorpCategory.id'),
					'corp_id' => Hash::get($m_corp_categories_temp, 'MCorpCategoriesTemp.corp_id'),
					'genre_id' => Hash::get($m_corp_categories_temp, 'MCorpCategoriesTemp.genre_id'),
					'category_id' => Hash::get($m_corp_categories_temp, 'MCorpCategoriesTemp.category_id'),
					'order_fee' => Hash::get($m_corp_categories_temp, 'MCorpCategoriesTemp.order_fee'),
					'order_fee_unit' => Hash::get($m_corp_categories_temp, 'MCorpCategoriesTemp.order_fee_unit'),
					'introduce_fee' => Hash::get($m_corp_categories_temp, 'MCorpCategoriesTemp.introduce_fee'),
					'note' => Hash::get($m_corp_categories_temp, 'MCorpCategoriesTemp.note'),
					'modified_user_id' => Hash::get($m_corp_categories_temp, 'MCorpCategoriesTemp.modified_user_id'),
					'modified' => Hash::get($m_corp_categories_temp, 'MCorpCategoriesTemp.modified'),
					'created_user_id' => Hash::get($m_corp_categories_temp, 'MCorpCategoriesTemp.created_user_id'),
					'created' => Hash::get($m_corp_categories_temp, 'MCorpCategoriesTemp.created'),
					'select_list' => Hash::get($m_corp_categories_temp, 'MCorpCategoriesTemp.select_list'),
					'select_genre_category' => Hash::get($m_corp_categories_temp, 'MCorpCategoriesTemp.select_genre_category'),
					'target_area_type' => Hash::get($m_corp_categories_temp, 'MCorpCategoriesTemp.target_area_type'),
			);
			// MCorpCategoryの登録（id自動採番のため）
			if (false === $MCorpCategory->save($saving_corp_category_data)) {
				$this->rollback();
				return false;
			}
			// m_corp_categories.idを再取得、新規登録した場合もここで取得
			$mcc_id = Hash::get($m_corp_categories_temp, 'MCorpCategory.id', $MCorpCategory->getLastInsertID());
			// m_target_areaは重複可能でそれを削除する手段が無いので常に全件削除→登録とする
			if (false === $MTargetArea->deleteAll(array('corp_category_id' => $mcc_id))) {
				$this->rollback();
				return false;
			}
			//MTargetAreasTemp→MTargetAreaへコピー(m_corp_categories.idが必要)
			$sql = '';
			$sql[] = 'INSERT INTO m_target_areas';
			$sql[] = '(   corp_category_id,     jis_cd, modified_user_id,          modified, created_user_id,           created,               address1_cd )';
			$sql[] = 'SELECT DISTINCT';
			$sql[] = '  CAST(? AS integer), MTT.jis_cd,                ?, current_timestamp,               ?, current_timestamp, SUBSTRING(MTT.jis_cd,1,2) ';
			$sql[] = 'FROM';
			$sql[] = '  m_target_areas_temp MTT';
			$sql[] = 'WHERE';
			$sql[] = '  MTT.corp_category_id = ?;';
			if (false === $this->query(implode("\n", $sql), array($mcc_id, $create_user_id, $create_user_id, Hash::get($m_corp_categories_temp, 'MCorpCategoriesTemp.id')), false)) {
				$this->rollback();
				return false;
			}
		}

		if (false === $this->save($corp_agreement)) {
			$this->rollback();
			return false;
		}

		$this->commit();
		return true;
	}

	// ORANGE-347 ADD (E)
	// 2016.05.17 murata.s ORANGE-1210 ADD(S)
	public function hasStatusComplete($corp_id = null) {
		$corpAgreement = $this->find('first', array(
				'fields' => array('status'),
				'conditions' => array(
						'corp_id' => $corp_id,
						'status' => 'Complete'
				)
		));
		//return !empty($corpAgreement);
		return true;
	}

	// 2016.05.17 murata.s ORANGE-1210 ADD(E)
}
