<?php

class Holiday extends AppModel {

	public $virtualFields = array(
			'checked' => '(CorpHoliday.id IS NOT NULL)',
			'corp_id' => 'CorpHoliday.corp_id',
			'id' => 'CorpHoliday.id',
	);
	public $fields = array();
	public $table = null;
	public $useTable = false;
	public $alias = 'Holiday';
	public $validate = array(
			'checked' => array(
					'rule' => array('multiple', array('min' => 1)),
					'required' => true,
					'message' => '必須入力です。',
			),
	);

	public function beforeFind($queryData) {
		$this->table = $this->useTable = 'm_items';
		$corp_id_condition = Hash::get($queryData, 'conditions.corp_id');
		$queryData = Hash::remove($queryData, 'conditions.corp_id');

		if (!Hash::check($queryData, 'joins.{n}[table=m_corp_subs]')) {
			$this->virtualFields['corp_id'] = $corp_id_condition;
			$queryData['joins'][] = array(
					'type' => 'LEFT',
					'table' => 'm_corp_subs',
					'alias' => 'CorpHoliday',
					'conditions' => array(
							'Holiday.item_id = CorpHoliday.item_id',
							'Holiday.item_category = CorpHoliday.item_category',
							'CorpHoliday.corp_id' => $corp_id_condition
					),
			);
		}
		if (!Hash::check($queryData, 'conditions.{n}.item_category')) {
			$queryData['conditions'][] = array('Holiday.item_category' => '休業日');
		}
		return parent::beforeFind($queryData);
	}

	public function saveAll($data = array(), $options = array()) {

		$checked = array('checked' => Hash::extract($data, '{n}.checked'));
		$this->set($checked);
		if (!$this->validates(array('fieldList' => array('checked')))) {
			return false;
		}

		App::import('Model', 'MCorpSub');
		$MCS = new MCorpSub();

		$del_ids = Hash::Filter(Hash::extract($data, '{n}[checked=false].id'));
		if (!empty($del_ids) && $MCS->deleteAll(array('id' => $del_ids), false) === false) {
			throw New Exception('MCorpSub delete error.');
		}
		$ins_data_array = array($MCS->name => Hash::map($data, '{n}[checked=true]', function($d) {
									if (Hash::get($d, 'id') !== null) {
										return array(
												'id' => Hash::get($d, 'id'),
												'corp_id' => Hash::get($d, 'corp_id'),
												'item_category' => '休業日',
												'item_id' => Hash::get($d, 'item_id'),
										);
									} else {
										return array(
												'corp_id' => Hash::get($d, 'corp_id'),
												'item_category' => '休業日',
												'item_id' => Hash::get($d, 'item_id'),
										);
									}
								}));
		foreach ($ins_data_array as $ins_data) {
			if ($MCS->saveAll($ins_data) === false) {
				throw New Exception('MCorpSub save error.');
			}
		}
		return true;
	}

	public function save($data = null, $validate = true, $fieldList = array()) {
		if (Hash::get($validate, 'validate') === 'only') {
			return parent::save($data, $validate, $fieldList);
		}
		throw new FatalErrorException('Holidayモデルはsaveをサポートしていません。saveAllを利用してください。');
	}

}
