<?php
class MAddress1 extends AppModel {
	public $useTable = "m_address1";
	
	/**
	 * 県一覧(SelectBox用)を返します
	 *
	 */
	public function getList(){

		// 検索条件の設定
		$conditions = array();

		// DB検索
		$list = $this->find('all', array('conditions'=>$conditions, 'order'=>'MAddress1.address1_cd asc'));

		// リストの作成
		foreach ($list as $val) {

			$list_[] = array(
					"MAddress1" => array(
							"id" => $val['MAddress1']['address1_cd'],
							"name" => $val['MAddress1']['address1']
					)
			);
		}

		$data = Set::Combine(@$list_, '{n}.MAddress1.id', '{n}.MAddress1.name');
		return $data;
	}

}