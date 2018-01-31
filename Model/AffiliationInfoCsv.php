<?php
/*
 * CSVインポート用model
 */

class AffiliationInfoCsv extends AppModel {
    public $useTable = 'affiliation_infos';
    
    public $validate = array(
                //加盟店ID
		'corp_id' => array(
			'NotEmpty' => array(
                            'rule' => 'notEmpty',
                            'last' => true
			),
                        'NoNumeric' => array(
                            'rule' => 'numeric',
                    ),
		),
                
                //与信限度額
                'credit_limit' => array(
                        'NotEmpty' => array(
                            'rule' => 'notEmpty',
                            'last' => true
			),
			'NoNumeric' => array(
				'rule' => 'numeric',
				'allowEmpty' => false
			),
		),
    );
}