<?php
class MSiteCategory extends AppModel {

	public $validate = array(

	);

	/**
	 * 指定したサイトIDに紐付くカテゴリ一覧(SelectBox用)を返します
	 *
	 * @param unknown_type $site_id
	 */
	public function getCategoriesBySite($site_id){

		if (empty($site_id)) {
			return null;
		}

		App::import('Model', 'MSite');
		$MSite = new MSite();

		// サイトマスタを検索
		$site = $MSite->findById($site_id);

		// 検索条件の設定(クロスサイトの場合は、全カテゴリ取得)
		if ($site['MSite']['cross_site_flg'] == 1) {
			$conditions = null;
		} else {
			$conditions = array('MSiteCategory.site_id' => $site_id);
		}

		// DB検索
		$list = $this->find('all',
				array('fields' => 'MCategory.id, MCategory.category_name',
						'conditions'=>$conditions,
						'joins' => array(
								array('fields' => '*',
										'type' => 'inner',
										'table' => 'm_categories',
										'alias' => 'MCategory',
										'conditions' => array('MSiteCategory.category_id = MCategory.id')
								),
						),
						'order'=>'MCategory.id asc'
				)
		);

		// リストの作成
		foreach ($list as $val) {

			$list_[] = array(
					'MCategory' => array(
							'id' => $val['MCategory']['id'],
							'name' => $val['MCategory']['category_name']
					)
			);
		}

		$data = Set::Combine(@$list_, '{n}.MCategory.id', '{n}.MCategory.name');
		return $data;
	}

}