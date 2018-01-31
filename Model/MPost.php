<?php
class MPost extends AppModel {

	public $validate = array(
	);


	public function getList($category){
		$conditions = array();

		$conditions = array('item_category' => $category);
		$conditions += array('enabled_start <= ' => date('Y/m/d'));
		$conditions['and'] += array(array( 'or'=>array('enabled_end >=' =>  date('Y/m/d')), array('enabled_end' =>  null)));

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

	// 2015.08.17 s.harada MOD start 画面デザイン変更対応
	/**
	 * 指定した都道府県のエリア数を返します
	 *
	 */
	public function getPrefAreaCount($address1 = null){

		$conditions['MPost.address1'] = $address1;

		$count = $this->find('count',
				array('fields' => 'MPost.jis_cd',
						'conditions' => $conditions ,
						'group' => 'MPost.jis_cd , MPost.address2',
						'order' => array('MPost.jis_cd'=> 'asc'),
				)
		);

		return $count;
	}

	/**
	 * 指定した都道府県の加盟店が設定した基本対応エリア数を返します
	 *
	 */
	public function getCorpPrefAreaCount($id = null , $address1 = null){

		$conditions['MPost.address1'] = $address1;

		$count = $this->find('count',
				array('fields' => 'MPost.jis_cd',
						'joins' =>  array(
								array('fields' => '*',
										'type' => 'RIGHT',
										"table" => "m_corp_target_areas",
										"alias" => "MCorpTargetArea",
										"conditions" => array("MCorpTargetArea.jis_cd = MPost.jis_cd" , "MCorpTargetArea.corp_id = ".$id)
								),
						),
						'conditions' => $conditions ,
						'group' => 'MPost.jis_cd , MPost.address2 , MCorpTargetArea.corp_id',
						'order' => array('MPost.jis_cd'=> 'asc'),
				)
		);

		return $count;
	}

	/**
	 * 指定した都道府県の加盟店が設定したカテゴリ別対応エリア数を返します
	 *
	 */
	public function getCorpCategoryAreaCount($id = null , $address1 = null){

		$conditions['MPost.address1'] = $address1;

		$count = $this->find('count',
				array('fields' => 'MPost.jis_cd',
						'joins' =>  array(
								array('fields' => '*',
										'type' => 'RIGHT',
										"table" => "m_target_areas",
										"alias" => "MTargetArea",
										"conditions" => array("MTargetArea.jis_cd = MPost.jis_cd" , "MTargetArea.corp_category_id = ".$id)
								),
						),
						'conditions' => $conditions ,
						'group' => 'MPost.jis_cd , MPost.address2 , MTargetArea.id',
						'order' => array('MPost.jis_cd'=> 'asc'),
				)
		);

		return $count;
	}
	// 2015.08.17 s.harada MOD start 画面デザイン変更対応

// 2016.11.17 murata.s ORANGE-185 ADD(S)
	/**
	 * address1, address2から市町村コードを返します
	 *
	 * @param string $address1 都道府県
	 * @param string $address2 市町村
	 */
	public function getTargetJisCd($address1 = null, $address2 = null){
		if(!empty($address1))
			$conditions['address1'] = Util::getDivTextJP('prefecture_div',$address1);

		if(!empty($address2)){
			$upperAddress2 = $address2;
			$upperAddress2 = str_replace('ヶ', 'ケ', $upperAddress2);
			$upperAddress2 = str_replace('ﾉ', 'ノ', $upperAddress2);
			$upperAddress2 = str_replace('ﾂ', 'ツ', $upperAddress2);
			$lowerAddress2 = $address2;
			$lowerAddress2 = str_replace('ケ', 'ヶ', $lowerAddress2);
			$lowerAddress2 = str_replace('ノ', 'ﾉ', $lowerAddress2);
			$lowerAddress2 = str_replace('ツ', 'ﾂ', $lowerAddress2);
			$conditions[] = array(
					'OR' => array(
							array('MPost.address2' => $upperAddress2),
							array('MPost.address2' => $lowerAddress2),
					)
			);
		}

		$results = $this->find('first', array(
				'fields' => 'jis_cd',
				'conditions' => $conditions,
				'group' => array('jis_cd')
		));

		if(!empty($results))
			return $results['MPost']['jis_cd'];
		else
			return '';
	}
// 2016.11.17 murata.s ORANGE-185 ADD(E)

}