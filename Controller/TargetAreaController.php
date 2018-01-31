<?php
App::uses('AppController', 'Controller');
App::import( 'Vendor', 'PHPWord', array('file'=>'PHPWord.php') );

class TargetAreaController extends AppController {

	public $name = 'TargetArea';
	public $helpers = array();
	public $components = array('Session');
	public $uses = array('MCorpCategory', 'MTargetArea');

	public function beforeFilter(){

		$this->layout = 'subwin';

		parent::beforeFilter();
	}


	// 初期表示（一覧）
	public function index($corp_id = null) {


		if ($corp_id == null) {
			throw new Exception();
		}

		$params = array();
		$conditions = array();

		$conditions['MCorpCategory.corp_id'] = $corp_id;
		$joins = array();
		$joins = array(
				array(
						'table' => 'm_target_areas',
						'alias' => "MTargetArea",
						'type' => 'inner',
						'conditions' => array('MCorpCategory.id = MTargetArea.corp_category_id')
				),
				array(
						'table' => '(select jis_cd, max(address1 || address2) as address from m_posts group by jis_cd)',
						'alias' => "MPost",
						'type' => 'inner',
						'conditions' => array('MTargetArea.jis_cd = MPost.jis_cd')
				),
				array(
						'table' => 'm_categories',
						'alias' => "MCategory",
						'type' => 'inner',
						'conditions' => array('MCorpCategory.category_id = MCategory.id')
				),
		);


		$params = array(
					'fields' => array('MCategory.category_name', 'string_agg("MPost"."address", \',\') as "MPost__address"'),
					'conditions' => $conditions,
					'joins' => $joins,
					'group' => array('MCategory.category_name')
				);

		$results = $this->MCorpCategory->find('all', $params);

		$this->set('results', $results);

	}



}
