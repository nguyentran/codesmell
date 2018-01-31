<?php
class AuctionAgreementProvisions extends AppModel{
// 2016.07.11 murata.s ORANGE-1 ADD(S)
	public $useTable = 'auction_agreement_provisions';

	public function findAuctionAgreementProvisions($auction_agreement_id = null){
		$this->bindModel(array(
				'hasMany' => array(
						'AuctionAgreementProvisionsItem' => array(
								'foreignKey' => 'auction_agreement_provisions_id',
								'order' => 'AuctionAgreementProvisionsItem.sort_no'
						)
				)
		));

		// 最新の入札手数料同意書を取得
		if(empty($auction_agreement_id)){
			App::import('Model', 'AuctionAgreement');
			$auctionAgreement = new AuctionAgreement();
			$id = $auctionAgreement->find('first', array(
					'fields' => array('id'),
					'order' => array('id' => 'desc')
			));
			$auction_agreement_id = isset($id['AuctionAgreement']['id']) ? $id['AuctionAgreement']['id'] : null;
		}

		return $this->find('all', array(
				'fields' => '*',
				'conditions' => array(
						'AuctionAgreementProvisions.auction_agreement_id' => $auction_agreement_id
				),
				'order' => array('sort_no' => 'asc')
		));
	}
// 2016.07.11 murata.s ORANGE-1 ADD(E)
}