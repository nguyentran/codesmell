<?php
class AutoSelectCorp extends AppModel {

	public $validate = array(

		'genre_id' => array(
			'NotEmpty' => array(
				'rule' => 'notEmpty',
				'last' => true
			),
		),

		'prefecture_cd' => array(
			'NotEmpty' => array(
				'rule' => 'notEmpty',
				'last' => true
			),
		),

		'corp_id' => array(
			'CorpNotEmpty' => array(
				'rule' => 'notEmpty',
				'last' => true
			),
		),

		'limit_per_day' => array(
			'LimitPerDayNotEmpty' => array(
				'rule' => 'notEmpty',
				'last' => true
			),
			'LimitPerDayNoNumeric' => array(
				'rule' => array('numeric'),
				'last' => true,
			),
		),

		'limit_per_month' => array(
			'LimitPerMonthNotEmpty' => array(
				'rule' => 'notEmpty',
				'last' => true
			),
			'LimitPerMonthNoNumeric' => array(
				'rule' => array('numeric'),
				'last' => true,
			),
		),

		'ratio' => array(
			'ErrorRatio' => array(
				'rule' => 'checkRatio',
			),
			'NotEmpty' => array(
				'rule' => 'notEmpty',
				'last' => true
			),
		),

		'display_order' => array(
			'NotEmpty' => array(
				'rule' => 'notEmpty',
				'last' => true
			),
		),
	);

	/**
	 * 比率のチェック
	 *
	 *
	 * @return boolean
	 */
	public function checkRatio() {
		App::uses('CakeSession', 'Model/Datasource');
		$Session = new CakeSession();
		$data = $Session->read('datas@AutoSelect');
		$ratio = 0;
		foreach ($data['AutoSelect'] AS $val){
			if($val['AutoSelectCorp']['prefecture_cd'] == $this->data['AutoSelectCorp']['prefecture_cd']){
				if(!isset($val['AutoSelectPrefecture']['delete'])){
					$ratio = $ratio + $val['AutoSelectCorp']['ratio'];
				}
			}
		}
		foreach ($data['AutoSelectPrefecture'] AS $val){
			if($val['AutoSelectPrefecture']['prefecture_cd'] == $this->data['AutoSelectCorp']['prefecture_cd']){
				$ratio = $ratio + $val['AutoSelectPrefecture']['manual_ratio'];
			}
		}
		if($ratio != 100){
			return false;
		}
		return true;
	}
}