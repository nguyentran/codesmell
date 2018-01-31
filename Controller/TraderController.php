<?php
App::uses('AppController', 'Controller');

class TraderController extends AppController {

	public $name = 'Trader';
// 2017.02.20 murata.s ORANGE-328 CHG(S)
	public $helpers = array('NotCorrespond');
// 2017.02.20 murata.s ORANGE-328 CHG(E)
	public $components = array();

	//ORANGE-276 CHG S
	public $uses = array('MCorpCategory', 'MTargetArea', 'DemandForecast');
	//ORANGE-276 CHG E

	public function beforeFilter(){
		parent::beforeFilter();
		$this->User = $this->Auth->user();
	}

	public function index() {
		//ORANGE-276 ADD S
		if($this->User['auth'] == 'affiliation'){
			$this->__get_forecast();
		}
		//ORANGE-276 ADD E
		if(Util::isMobile() && $this->User['auth'] == 'affiliation') {
			$this->layout = 'default_m';
			$this->render('index_m');
		}
	}

	//ORANGE-276 ADD S
	private function __get_forecast(){
		$options = array(
				'conditions' => array(
						'MCorpCategory.corp_id' => $this->User['affiliation_id'],
						'MCorpCategory.genre_id' => Configure::read('FORECAST_CATEGORY_ID')
				),
				'recursive' => -1,
				'joins' => array(
					 array(
								'table' => 'm_target_areas',
								'alias' => 'MTargetArea',
								'type' => 'inner',
								'conditions' => 'MTargetArea.corp_category_id = MCorpCategory.id',
						),

				),
				'fields' => array('MCorpCategory.genre_id', 'substr("MTargetArea"."jis_cd", 1, 2)AS "MCorpCategory__state_id"'),
				'group' => array('MCorpCategory.genre_id', 'substr("MTargetArea"."jis_cd", 1, 2)'),
				'order' => array('MCorpCategory.genre_id', 'substr("MTargetArea"."jis_cd", 1, 2)')
		);

		$corp_category = $this->MCorpCategory->find('all', $options);

		$region_list = Configure::read('STATE_REGION');
		$region = array();
		$category = array();
		//result
		$forecast_list = array();

		if($corp_category){
			foreach($corp_category as $data){
				//地方
				if(array_search($data['MCorpCategory']['state_id'], $region) === FALSE){
					array_push($region, $region_list[$data['MCorpCategory']['state_id']]);
				}
				//カテゴリ
				if(array_search($data['MCorpCategory']['genre_id'], $category) === FALSE){
					array_push($category, $data['MCorpCategory']['genre_id']);
				}
			}

			if($region && $category){
				$options = array(
						'conditions' => array('DemandForecast.region_id' => $region, 'DemandForecast.display_date' => date('Y-m-d')),
						'order' => array('state_id', 'forecast_date'),
				);
				$forecast_list = $this->DemandForecast->find('all', $options);

				$options = array(
						'conditions' => array('DemandForecast.region_id' => $region, 'DemandForecast.display_date' => date('Y-m-d')),
						'order' => array('region_id', 'forecast_date'),
						'group' => array('region_id', 'forecast_date'),
						'fields' => array(
								'region_id',
								'forecast_date',
								'sum(CASE WHEN demand_count0_min <= demand_count0_max THEN demand_count0_min ELSE demand_count0_max END) as "DemandForecast__demand_count0_min"',
								'sum(CASE WHEN demand_count0_max >= demand_count0_min THEN demand_count0_max ELSE demand_count0_min END) as "DemandForecast__demand_count0_max"',
								'sum(CASE WHEN demand_count1_min <= demand_count1_max THEN demand_count1_min ELSE demand_count1_max END) as "DemandForecast__demand_count1_min"',
								'sum(CASE WHEN demand_count1_max >= demand_count1_min THEN demand_count1_max ELSE demand_count1_min END) as "DemandForecast__demand_count1_max"',
								'sum(CASE WHEN demand_count2_min <= demand_count2_max THEN demand_count2_min ELSE demand_count2_max END) as "DemandForecast__demand_count2_min"',
								'sum(CASE WHEN demand_count2_max >= demand_count2_min THEN demand_count2_max ELSE demand_count2_min END) as "DemandForecast__demand_count2_max"',
						),
				);
				$region_forecast_list = $this->DemandForecast->find('all', $options);
			}
		}
//pr($region_forecast_list);
//pr($forecast_list);

		$this->set(compact('forecast_list', 'category', 'region_forecast_list'));

	}
	//ORANGE-276 ADD E
}
