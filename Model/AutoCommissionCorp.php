<?php
/*
 * 2017/06/08  ichino ORANGE-420 ADD
 * 自動取次先加盟店設定
 */

App::uses('AppModel', 'Model');

/*
 * AutoCommissionCorp Model
 */
class AutoCommissionCorp extends AppModel {
    
        public $useTable = 'auto_commission_corp';
    
	/**
	 * Validation rules
	 *
	 * @var array
	 */
	public $validate = array(
		// 2017.11.30 ORANGE-603 h.miyake ADD(S)
		'corp_id' => array(
			'NotEmpty' => array(
				'rule' => 'notEmpty',
				'last' => true
			),
		),
		'category_id1' => array(
			'NotEmpty' => array(
				'rule' => 'notEmpty',
				'last' => true
			),
		),
		'jis_cd' => array(
			'NotEmpty' => array(
				'rule' => 'notEmpty',
				'last' => true
			),
		),	
		'process_type' => array(
			'NotEmpty' => array(
				'rule' => 'notEmpty',
				'last' => true
			),
		),
		// 2017.11.30 ORANGE-603 h.miyake ADD(E)
	);
        
        /* 
         * 都道府県コードとカテゴリーIDをもとに、加盟店を検索する
         * @param pref_cd 2桁の都道府県コード
         * @param category_id カテゴリーのID
         * @return array data
         */
        public function getAutoCommissionCorpByPrefCategory($pref_cd = null, $category_id = null, $corp_id = null){
            $results = array();
            
            if(!empty($pref_cd) && !empty($category_id)){
                //1桁の都道府県コードがあった場合は0埋めする
                $pref_cd = sprintf('%02d', $pref_cd);
                
                //バーチャルフィールドで、jis_cdの先頭2桁を指定する
                $this->virtualFields['pref_cd'] = 'substring(AutoCommissionCorp.jis_cd from 1 for 2)';

                // 2017/10/04 ORANGE-509 m-kawamoto CHG(S) process_type追加
                //自動取次先加盟店を取得
                //カラム
                $option['fields'] = array(
                    'DISTINCT AutoCommissionCorp.corp_id',
                    'AutoCommissionCorp.category_id',
                    'AutoCommissionCorp.pref_cd',
                    'AutoCommissionCorp.sort',
                    'AutoCommissionCorp.process_type',
                );
                // 2017/10/04 ORANGE-509 m-kawamoto CHG(E)

                //結合条件
                $option['joins'][] = array(
                    'type' => 'INNER',
                    'table' => 'm_corps',
                    'alias' => 'MCorp',
                    'conditions' => 'AutoCommissionCorp.corp_id = MCorp.id',
                );

                // 2017/10/04 ORANGE-509 m-kawamoto CHG(S) process_typeを最優先とする
                //並び順
                $option['order'][] = array(
                    'AutoCommissionCorp.process_type' => 'desc',
                    'AutoCommissionCorp.sort' => 'asc',
                );
                // 2017/10/04 ORANGE-509 m-kawamoto CHG(E)

                //条件
                $option['conditions'][] = array('MCorp.del_flg' => 0);
                
                if(!empty($pref_cd)){
                    $option['conditions'][] = array('AutoCommissionCorp.pref_cd' => $pref_cd);
                }
                
                if(!empty($category_id)){
                    $option['conditions'][] = array('AutoCommissionCorp.category_id' => $category_id);
                }
                
                if(!empty($corp_id)){
                    $option['conditions'][] = array('AutoCommissionCorp.corp_id' => $corp_id);
                }
                
                $results = $this->find('all', $option);
            }
        
            return $results;
        }
}
