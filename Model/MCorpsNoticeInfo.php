<?php
App::uses('AppModel', 'Model');

class MCorpsNoticeInfo extends AppModel {

	public $recursive = -1;

	public function findAnswerListByNoticeInfoId($notice_info_id) {
		$this->virtualFields['corp_name'] = 'MCorp.corp_name';
		$query = array(
				'fields' => array(
						'm_corp_id',
						'corp_name',
						'answer_value',
						'answer_date'
				),
				'joins' => array(
						array(
								'type' => 'LEFT',
								'table' => 'm_corps',
								'alias' => 'MCorp',
								'conditions' => array(
										'MCorpsNoticeInfo.m_corp_id = MCorp.id',
								),
						),
				),
				'conditions' => array(
						'notice_info_id' => $notice_info_id,
						'NOT' => array('answer_value' => null)
				),
		);
		$result = $this->find('all', $query);
		if ($result) {
			return array('MCorpsNoticeInfo' => Hash::extract($result, '{n}.MCorpsNoticeInfo'));
		} else {
			return $result;
		}
	}

}
