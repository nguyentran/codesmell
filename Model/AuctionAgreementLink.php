<?php
class AuctionAgreementLink extends AppModel{
// 2016.07.11 murata.s ORANGE-1 ADD(S)
	public $useTable = 'auction_agreement_links';

	public $validate = array(
			'agreement_check' => array(
					'NotEmpty' => array(
						'rule' => array('comparison', '==', 1),
						'message' => '入札には入札手数料同意書への同意が必要です。')
			)
	);
// 2016.07.11 murata.s ORANGE-1 ADD(E)
}