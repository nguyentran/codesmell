<?php
App::uses('AppModel', 'Model');

/**
 * NoticeInfo Model
 */
class NoticeInfoTarget extends AppModel {

	public $recursive = -1;

	public function saveByNoticeInfoIdAndCorpIds($notice_info_id, $corp_ids) {
		if (!is_array($corp_ids)) {
			return false;
		}
		try {
			$this->begin();
			// 送信データがIDを持たず、更新のコストが非常に高いので全件再作成する
			$this->deleteAll(array('notice_info_id' => $notice_info_id));
			foreach ($corp_ids as $corp_id) {
				$this->create();
				$this->save(array('notice_info_id' => $notice_info_id, 'corp_id' => $corp_id,));
			}
			$result = $this->commit();
		} catch (Exception $e) {
			$this->rollback();
			$result = false;
		}
		return $result;
	}

	public function findCorpListByNoticeInfoId($notice_info_id) {
		$this->virtualFields['corp_name'] = 'MCorp.corp_name';
		$query = array(
				'fields' => array('corp_id', 'corp_name'),
				'joins' => array(
						array(
								'type' => 'LEFT',
								'table' => 'm_corps',
								'alias' => 'MCorp',
								'conditions' => array(
										'NoticeInfoTarget.corp_id = MCorp.id',
								),
						),
				),
				'conditions' => array(
						'notice_info_id' => $notice_info_id
				),
		);
		$result = $this->find('list', $query);
		if ($result) {
			return array('NoticeInfoTarget' => $result);
		} else {
			return $result;
		}
	}

	public function findCorpListByCorpIds($corp_ids) {
		if (!is_array($corp_ids)) {
			return false;
		}
		$this->virtualFields['corp_name'] = 'MCorp.corp_name';
		$query = array(
				'fields' => array('corp_id', 'corp_name'),
				'joins' => array(
						array(
								'type' => 'LEFT',
								'table' => 'm_corps',
								'alias' => 'MCorp',
								'conditions' => array(
										'NoticeInfoTarget.corp_id = MCorp.id',
								),
						),
				),
				'conditions' => array(
						'corp_id' => (array) $corp_ids
				),
		);
		$result = $this->find('list', $query);
		if ($result) {
			return array('NoticeInfoTarget' => $result);
		} else {
			return $result;
		}
	}

}
