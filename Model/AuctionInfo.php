<?php
class AuctionInfo extends AppModel {

	public $validate = array(
		'responders' => array(
			'NotEmpty' => array(
				'rule' => 'notEmpty',
				'last' => true
			),
			'OverMaxLength20' => array(
				'rule' => array('maxLength', 20),
				'last' => true,
				'allowEmpty' => true
			),
		),

		'visit_time_id' => array(
			'NotEmpty' => array(
				'rule' => 'notEmpty',
				'last' => true
			),
		),

	);

	public $csvFormat = array(
	);

	/**
	 * findMaxLimit
	 * “üŽD‰Â”\ãŒÀ”‚ÌŽæ“¾
	 *
	 * @param $id
	 * @return int
	 */
	public function findMaxLimit($id)
	{
		$this->bindModel(array(
			'belongsTo' => array(
				'DemandInfo' => array(
					'className' => 'DemandInfo',
					'foreignKey' => 'demand_id'
				)
			)
		));
		$result = $this->findById($id);
		$MSite = ClassRegistry::init('MSite');
		return $MSite->findMaxLimit($result);
	}

	/**
	 * findCurrentCommitNum
	 * Œ»Ý“üŽD‚³‚ê‚Ä‚¢‚é”‚ðŽæ“¾
	 *
	 * @param $id
	 * @return int
	 */
	public function findCurrentCommitNum($id)
	{

		return $this->find('count', array(
			'joins' => array(
				array(
					'type' => 'inner',
					"table" => "commission_infos",
					"alias" => "CommissionInfo",
					"conditions" => array(
						"CommissionInfo.demand_id = AuctionInfo.demand_id",
						"CommissionInfo.commit_flg = 1"
					)
				)
			),
			'conditions' => array('AuctionInfo.id' => $id),
		));
	}

	/**
	 * findLastCommission
	 * ÅŒã‚É“üŽD‚³‚ê‚½ƒf[ƒ^‚ðŽæ“¾
	 *
	 * @param $id
	 * @return mixed
	 */
	public function findLastCommission($id)
	{
		$results = $this->find('first', array(
			'fields' => 'CommissionInfo.created',
			'joins' => array(
				array(
					'fields' => '*',
					'type' => 'inner',
					"table" => "commission_infos",
					"alias" => "CommissionInfo",
					"conditions" => array(
						"CommissionInfo.demand_id = AuctionInfo.demand_id",
						"CommissionInfo.commit_flg = 1"
					)
				)
			),
			'conditions' => array('AuctionInfo.id' => $id),
			'order' => 'CommissionInfo.created desc'
		));
		return $results;
	}

	// 2016.05.13 murata.s ORANGE-1 ADD(S)
	public function getAuctiolnFee($id)
	{
		$result = $this->find('first', array(
// 2016.09.29 murata.s ORANGE-192 CHG(S)
				'fields' => 'MGenre.auction_fee, SelectGenrePrefecture.auction_fee',
// 2016.09.29 murata.s ORANGE-192 CHG(E)
				'joins' => array(
						array(
								'fields' => '*',
								'type' => 'left',
								'table' => 'demand_infos',
								'alias' => 'DemandInfo',
								'conditions' => array(
										'DemandInfo.id = AuctionInfo.demand_id'
								)
						),
						array(
								'fields' => '*',
								'type' => 'left',
								'table' => 'm_genres',
								'alias' => 'MGenre',
								'conditions' => array(
										'MGenre.id = DemandInfo.genre_id'
								)
						),
// 2016.09.29 murata.s ORANGE-192 ADD(S)
						array(
								'fields' => '*',
								'type' => 'left',
								'table' => 'select_genre_prefectures',
								'alias' => 'SelectGenrePrefecture',
								'conditions' => array(
										'SelectGenrePrefecture.genre_id = DemandInfo.genre_id',
										'SelectGenrePrefecture.prefecture_cd = DemandInfo.address1'
								)
						)
// 2016.09.29 murata.s ORANGE-192 ADD(E)
				),
				'conditions' => array(
						'AuctionInfo.id' => $id
				)
		));

// 2016.09.26 murata.s ORANGE-192 CHG(S)
// 		return isset($result['MGenre']['auction_fee']) ? $result['MGenre']['auction_fee'] : 0;
		return isset($result['SelectGenrePrefecture']['auction_fee']) ? $result['SelectGenrePrefecture']['auction_fee'] : 0;
// 2016.09.26 murata.s ORANGE-192 CHG(E)
	}
	// 2016.05.13 murata.s ORANGE-1 ADD(E)
}