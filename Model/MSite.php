<?php
class MSite extends AppModel {

	public $validate = array(

		'site_name' => array(
			'NotEmpty' => array(
				'rule' => 'notEmpty',
				'last' => true
			),
			'OverMaxLength200' => array(
				'rule' => array('maxLength', 200),
				'last' => true,
			),
		),

		'site_tel' => array(
			'OverMaxLength20' => array(
				'rule' => array('maxLength', 20),
				'last' => true,
				'allowEmpty' => true
			),
		),

		'note' => array(
			'OverMaxLength1000' => array(
				'rule' => array('maxLength', 1000),
				'last' => true,
				'allowEmpty' => true
			),
		),

	);

	/**
	 * ドロップダウンリストを返します
	 */
	public function getList(){
		$conditions = array();

		// 2014.12.19 h.hara
		//$list = $this->find('all', array('conditions'=>$conditions, 'order'=>'MSite.id asc'));
		$list = $this->find('all', array('conditions'=>$conditions, 'order'=>'MSite.site_name asc'));

		foreach ($list as $val) {

			$list_[] = array(
					"MSite" => array(
							"id" => $val['MSite']['id'],
							"name" => $val['MSite']['site_name']
					)
			);
		}

		$data = Set::Combine(@$list_, '{n}.MSite.id', '{n}.MSite.name');
		return $data;
	}

	/**
	 * 指定されたIDの名称を取得します
	 *
	 * @param unknown_type $id
	 */
	public function getListText($id){
		$conditions = array();

		$conditions = array('id' => $id);

		$list = $this->find('all', array('conditions'=>$conditions, 'order'=>'MSite.id asc'));

		$name ='';

		foreach ($list as $val) {

			if($id == $val['MSite']['id']) {
				$name = $val['MSite']['site_name'];
			}
		}

		return $name;
	}

	/**
	 * findMaxLimit
	 * 確定、入札の上限数を取得
	 *
	 * @param $data
	 * @return int
	 * @throws Exception
	 */
	public function findMaxLimit($data)
	{
		// 空の場合は新規登録時
		if (empty($data)) {
			return 0;
		}
		if (isset($data['DemandInfo'])) {
			$data = $data['DemandInfo'];
		}
		if (!isset($data['site_id']) || !isset($data['selection_system'])) {
			throw  new Exception('site_idとselection_systemが必要です');
		}

		$result = Hash::extract($this->find('first',
			array(
				'fields' => array(
					'manual_selection_limit',
					'auction_selection_limit'
				),
				'conditions' => array(
					$this->primaryKey => $data['site_id'],
				)
			)), $this->alias);
		if (!$result) {
			throw  new Exception('確定上限数が取得できませんでした');
		}
		// 2016.11.28 murata.s ORANGE-185 CHG(S)
		if ($data['selection_system'] == 2 || $data['selection_system'] == 3) {
			return $result['auction_selection_limit'];
		} else {
			return $result['manual_selection_limit'];
		}
		// 2016.11.28 murata.s ORANGE-185 CHG(E)
	}

	// 2017/12/07 h.miyake ORANGE-602 ADD(S)
	/**
	 * getCrossSiteFlg
	 * パラメータで指定されたクロスセルサイト判定(cross_site_flg)と一致するサイトidを配列で返す
	 *
	 * @param $CrossSiteFlg(cross_site_flg = 0 or 1)
	 * @return array
	 * @throws Exception
	 */
	public function getCrossSiteFlg($CrossSiteFlg) {
		$conditions = array('cross_site_flg' => $CrossSiteFlg );
		$list = $this->find('all', array('conditions'=>$conditions));
		foreach($list as $key => $value) {
			$result[] = $value["MSite"]["id"];
		}
		return $result;
	}
	// 2017/12/07 h.miyake ORANGE-602 ADD(S)
}