<?php
/*
 * CSVインポート用model
 */
class BillInfoCsv extends AppModel {
    
        public $useTable = 'bill_infos';
    
        //ステータス
        public $status_arr = array();
        
	public $validate = array(
            
                //案件ID
		'demand_id' => array(
                    'NotEmpty' => array(
			'rule' => 'notEmpty',
			'last' => true
                    ),
                    'NoNumeric' => array(
                        'rule' => 'numeric',
                    ),
		),
                
                //取次ID
                'commission_id' => array(
                    'NotEmpty' => array(
                    	'rule' => 'notEmpty',
			'last' => true
                    ),
                    'NoNumeric' => array(
                        'rule' => 'numeric',
                    ),
                ),
                
                //請求ID
                //バリデーションの際のみ、名称を変更する
                'bill_info_id' => array(
                    'NotEmpty' => array(
                    	'rule' => 'notEmpty',
			'last' => true
                    ),
                    'NoNumeric' => array(
                        'rule' => 'numeric',
                    ),
                ),
                
                //請求状況
		'bill_status' => array(
                    'NotEmpty' => array(
                    	'rule' => 'notEmpty',
                        'last' => true,
                    ),
                    'BillStatusError' => array(
                        'rule' => 'checkBillStatus',
                        'message' => '正しく入力してください。', 			
                    ),
                ),

                //手数料請求日
		'fee_billing_date' => array(
                    'NotEmpty' => array(
                    	'rule' => 'notEmpty',
			'last' => true
                    ),
                    'NotDate' => array(
                        'rule' => 'date',
                    ),
		),

                //手数料入金日
		'fee_payment_date' => array(
                    'NotEmpty' => array(
                    	'rule' => 'notEmpty',
			'last' => true
                    ),
                    'NotDate' => array(
                        'rule' => 'date',
                    ),
		),
	);
        
        /*
         * 請求状況バリデーション
         * return trueまたはfalse
         */
        public function checkBillStatus(){
            if(empty($this->status_arr)){
                App::import('MItem');
                $MItem = New MItem();
                
                $conditions = array('請求状況');
                $fields_list = 'item_id, item_name';
            
                //ステータスの変換用データの取得
                $this->status_arr = $MItem->find('list', array(
                        'fields' => $fields_list,
                        'conditions' => array('item_category' => $conditions),
                ));
            }
            
            if(!Hash::get($this->status_arr, $this->data['BillInfoCsv']['bill_status'])){
                return false;
            }
            
            return true;
	}
}