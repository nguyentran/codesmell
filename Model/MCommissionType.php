<?php
class MCommissionType extends AppModel {

	public $validate = array(
	);

	/**
	 * コミッションタイプ一覧(SelectBox用)を返します
	 *
	 */
	public function getList(){
	
		// DB検索
		$list = $this->find('all', array('conditions'=>array(), 'order'=>'MCommissionType.id asc'));
	
		// リストの作成
		foreach ($list as $val) {
	
			$list_[] = array(
					"MCommissionTypes" => array(
							"id" => $val['MCommissionType']['id'],
							"name" => $val['MCommissionType']['commission_type_name']
					)
			);
		}
	
		$data = Set::Combine(@$list_, '{n}.MCommissionTypes.id', '{n}.MCommissionTypes.name');
		return $data;
	}
}