<?php
class MSiteGenre extends AppModel {

	public $validate = array(

	);

	/**
	 * 指定したサイトIDに紐付くカテゴリ一覧(SelectBox用)を返します
	 *
	 * @param unknown_type $site_id
	 */
	public function getGenreBySite($site_id){

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
			$conditions = array('MSiteGenre.site_id' => $site_id);
		}

		// DB検索
		$list = $this->find('all',
				array('fields' => 'MGenre.id, MGenre.genre_name',
						'conditions'=>$conditions,
						'joins' => array(
								array('fields' => '*',
										'type' => 'inner',
										'table' => 'm_genres',
										'alias' => 'MGenre',
										'conditions' => array('MSiteGenre.genre_id = MGenre.id')
								),
						),
						'order'=>'MGenre.id asc'
				)
		);

		// リストの作成
		foreach ($list as $val) {

			$list_[] = array(
					'MGenre' => array(
							'id' => $val['MGenre']['id'],
							'name' => $val['MGenre']['genre_name']
								
					),
			);
		}

		$data = Set::Combine(@$list_, '{n}.MGenre.id', '{n}.MGenre.name');

		return $data;
	}

// murata.s ORANGE-480 ADD(S)
	/**
	 * 指定したサイトIDに紐付くカテゴリ一覧(SelectBox用)を返します
	 * @param int $site_id
	 */
	public function getGenreBySiteStHide($site_id){

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
			$conditions = array('MSiteGenre.site_id' => $site_id);
		}

		// DB検索
		$list = $this->find('all',
				array('fields' => 'MGenre.id, MGenre.genre_name',
						'conditions'=>$conditions,
						'joins' => array(
								array('fields' => '*',
										'type' => 'inner',
										'table' => 'm_genres',
										'alias' => 'MGenre',
										'conditions' => array(
												'MSiteGenre.genre_id = MGenre.id',
												'MGenre.st_hide_flg' => 0
										)
								),
						),
						'order'=>'MGenre.id asc'
				)
		);

		// リストの作成
		foreach ($list as $val) {
			$list_[] = array(
					'MGenre' => array(
							'id' => $val['MGenre']['id'],
							'name' => $val['MGenre']['genre_name']

					),
			);
		}

		$data = Set::Combine(@$list_, '{n}.MGenre.id', '{n}.MGenre.name');

		return $data;
	}
// murata.s ORANGE-480 ADD(E)

	/****
		ジャンル ランク
	
	****/
	public function getGenreByRank($site_id){

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
			$conditions = array('MSiteGenre.site_id' => $site_id);
		}

		// DB検索
		$list = $this->find('all',
				array('fields' => 'MGenre.id, MGenre.commission_rank',
						'conditions'=>$conditions,
						'joins' => array(
								array('fields' => '*',
										'type' => 'inner',
										'table' => 'm_genres',
										'alias' => 'MGenre',
										'conditions' => array('MSiteGenre.genre_id = MGenre.id')
								),
						),
						'order'=>'MGenre.id asc'
				)
		);

		// リストの作成
		foreach ($list as $val) {

			$list_[] = array(
					'MGenre' => array(
							'id' => $val['MGenre']['id'],
							'rank' => $val['MGenre']['commission_rank']
								
					)
			);
		}

		$rank = Set::Combine(@$list_, '{n}.MGenre.id', '{n}.MGenre.rank');
	
	
		return $rank;
	}

}