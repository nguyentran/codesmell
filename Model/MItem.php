<?php
class MItem extends AppModel {

	public $validate = array(

	);


	// 2016.07.14 ota.r@tobila CHG(S) ORANGE-108
//	public function getList($category){
	public function getList($category, $item_id = null){
	// 2016.07.14 ota.r@tobila CHG(E) ORANGE-108
		$conditions = array();

		$conditions = array('item_category' => $category);
		$conditions += array('enabled_start <= ' => date('Y/m/d'));
		$conditions['or'] = array(array('enabled_end >=' =>  date('Y/m/d')), array('enabled_end' =>  null));

		$list = $this->find('all', array('conditions'=>$conditions, 'order'=>'MItem.sort_order asc'));

		// 2016.07.14 ota.r@tobila ADD(S) ORANGE-108
		if ($item_id == 0) {
			$item_id = null;
		}
		$exist_flg = false;
		// 2016.07.14 ota.r@tobila ADD(E) ORANGE-108

		foreach ($list as $val) {
			$list_[] = array(
					"MItem" => array(
							"id" => $val['MItem']['item_id'],
							"name" => $val['MItem']['item_name']
					)
			);
			// 2016.07.14 ota.r@tobila ADD(S) ORANGE-108
			// すでに選択されている項目が取得したドロップダウンリストに含まれているかを確認
			if ($item_id != null && $item_id == $val['MItem']['item_id']) {
				$exist_flg = true;
			}
			// 2016.07.14 ota.r@tobila ADD(E) ORANGE-108
		}

		// 2016.07.14 ota.r@tobila ADD(S) ORANGE-108
		// 選択された項目が取得したドロップダウンリストに存在しない場合、item_idで再度DBを引き直す
		if ($item_id != null && !$exist_flg) {
			$conditions = array(
					'item_category' => $category,
					'item_id' => $item_id,
			);
			$oldlist = $this->find('first', array('conditions'=>$conditions, 'order'=>'MItem.sort_order asc'));
			$oldlist_[] = array(
					"MItem" => array(
							"id" => $oldlist['MItem']['item_id'],
							"name" => $oldlist['MItem']['item_name']
					)
			);
			// 旧項目を先頭にしてリストを再作成
			$list_ = array_merge($oldlist_, $list_);
		}
		// 2016.07.14 ota.r@tobila ADD(E) ORANGE-108

		$data = Set::Combine(@$list_, '{n}.MItem.id', '{n}.MItem.name');
		return $data;
	}

	public function getListOneTouch($category){
		$conditions = array();

		$conditions = array('item_category' => $category);
// 2016.07.13 ota.r@tobila MOD(S) ORANGE-108
//		$conditions += array('item_id' => array('29','31', '32', '34'));
//ORANGE-291 CHG S
		//$conditions += array('item_id' => array('35', '36', '37', '44', '45'));
//ORANGE-291 CHG E
// 2016.07.13 ota.r@tobila MOD(S) ORANGE-108
		$conditions += array('enabled_start <= ' => date('Y/m/d'));
		$conditions['or'] = array(array('enabled_end >=' =>  date('Y/m/d')), array('enabled_end' =>  null));

		$list = $this->find('all', array('conditions'=>$conditions, 'order'=>'MItem.sort_order asc'));

		foreach ($list as $val) {

			$list_[] = array(
				"MItem" => array(
					"id" => $val['MItem']['item_id'],
					"name" => $val['MItem']['item_name']
				)
			);
		}

		$data = Set::Combine(@$list_, '{n}.MItem.id', '{n}.MItem.name');
		return $data;
	}

	public function getListText($category, $value){
		$conditions = array();

		$conditions = array('item_category' => $category);

		$list = $this->find('all', array('conditions'=>$conditions, 'order'=>'MItem.sort_order asc'));

		$name ='';

		foreach ($list as $val) {
			if($value == $val['MItem']['item_id']) {
				$name = $val['MItem']['item_name'];
			}
		}
		return $name;
	}

	/**
	 * 複数同時
	 *
	 * @since  2015.10.20
	 * @param  array  $categories
	 * @return array
	 */
	public function getMultiList($categories) {
		return $this->find('list', array(
			'fields' => array('MItem.item_id', 'MItem.item_name', 'MItem.item_category'),
			'conditions' => array(
				'item_category' => $categories,
			),
			'order'=>'MItem.sort_order asc',
		));
	}
}