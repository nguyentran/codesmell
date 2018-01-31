<?php
/*
 * CSVインポート用model
 */

App::import('MItem');
$MItem = new MItem();

class CommissionInfoCsv extends AppModel {
        
        public $useTable = 'commission_infos';

        //ステータス
        public $status_arr = array();
        
	public $validate = array(
            //取次ID
            //バリデーションの際のみ、名称を変更する
            'commission_info_id' => array(
                'NotEmpty' => array(
                    'rule' => 'notEmpty',
                    'last' => true
                ),
                'NoNumeric' => array(
                    'rule' => 'numeric',
                ),
            ),
            
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
            
            //取次状況
            'commission_status' => array(
                'NotEmpty' => array(
                    'rule' => 'notEmpty',
                    'last' => true,
                ),
                'CommissionStatusError' => array(
                    'rule' => 'checkCommissionStatus',
                    'message' => '正しく入力してください。', 	
                ),
            ),
            
            /*
             * 施工完了日
             * 取次状況が施工完了の場合、必須とする
             * それ以外の場合は値が空でもエラーとはしない
             */
            'complete_date' => array(
                'CompleteDateError' => array(
                    'rule' => 'checkCompleteDate',
                    'message' => '取次状況が「施工完了」の場合は必須です。',
                    'last' => true,
                ),
                'NotDate' => array(
                    'rule' => 'date',
                    'allowEmpty' => true,
                ),
            ),
            
            /*
             * 失注日
             * 取次状況が失注の場合、必須とする
             * それ以外の場合は値が空でもエラーとはしない
             */
            'order_fail_date' => array(
                'OrderFailDateError' => array(
                    'rule' => 'checkOrderFailDate',
                    'message' => '取次状況が「失注」の場合は必須です。',
                    'last' => true,
                ),
                'NotDate' => array(
                    'rule' => 'date',
                    'allowEmpty' => true,
                ),
            ),
            
            /*
             * 取次失注理由
             * 取次状況が失注の場合、必須とする
             * それ以外の場合は値が空でもエラーとはしない
             */
            'commission_order_fail_reason' => array(
                'CommissionOrderFailReasonError' => array(
                    'rule' => 'checkCommissionOrderFailReason',
                    'message' => '取次状況が「失注」の場合は必須です。',
                    'last' => true,
                ),
                'CommissionOrderFailReasonStatusError' => array(
                    'rule' => 'checkCommissionOrderFailReasonStatus',
                    'message' => '正しく入力してください。',
                    'allowEmpty' => true,
                ),
                
            ),
            
            /*
             * 施工金額(税抜)
             * 取次状況が施工完了、紹介済の場合、必須とする
             * それ以外の場合は値が空でもエラーとはしない
             */
            'construction_price_tax_exclude' => array(
                'ConstructionPriceTaxExcludeError' => array(
                    'rule' => 'checkConstructionPriceTaxExclude',
                    'message' => '取次状況が「施工完了」または「紹介済」の場合は必須です。',
                    'last' => true,
                ),
                'NoNumeric' => array(
                    'rule' => 'numeric',
                    'allowEmpty' => true,
                ),
            ),
            
            /*
             * 控除金額(税抜)
             * 取次状況が施工完了、紹介済の場合、必須とする
             * それ以外の場合は値が空でもエラーとはしない
             */
            'construction_price_tax_include' => array(
                'ConstructionPriceTaxIncludeError' => array(
                    'rule' => 'checkConstructionPriceTaxInclude',
                    'message' => '取次状況が「施工完了」の場合は必須です。',
                    'last' => true,
                ),
                'NoNumeric' => array(
                    'rule' => 'numeric',
                    'allowEmpty' => true,
                ),
            ),
            
            
            /*
             * 取次時手数料率
             * 取次状況が施工完了、紹介済の場合、必須とする
             * それ以外の場合は値が空でもエラーとはしない
             */
            'commission_fee_rate' => array(
                'CommissionFeeRateError' => array(
                    'rule' => 'checkCommissionFeeRate',
                    'message' => '取次状況が「施工完了」の場合は必須です。',
                    'last' => true,
                ),
                'NoDouble' => array(
                    'rule' => 'checkDoubleCommissionFeeRate',
                    'message' => '数値で入力してください。',
                    'allowEmpty' => true,
                ),
            ),
            
            /*
             * イレギュラー手数料率
             */
            'irregular_fee_rate' => array(
                'NoDouble' => array(
                    'rule' => 'checkDoubleIrregularFeeRate',
                    'message' => '数値で入力してください。',
                    'allowEmpty' => true,
                ),
            ),
            
            /*
             * イレギュラー手数料金額
             */
            'irregular_fee' => array(
                'NoNumeric' => array(
                    'rule' => 'numeric',
                    'allowEmpty' => true,
                    'last' => true,
                ),
            ),
            
            /*
             * 進捗表回収
             */
            'progress_reported' => array(
                'ProgressReportedError' => array(
                    'rule' => 'checkProgressStatus',
                    'message' => '正しく入力してください。',
                    'last' => true,
                    'allowEmpty' => true,
                    ),
                'NoEmpty' => array(
                    'rule' => 'checkProgress',
                    'message' => '進捗表回収または進捗表回収日時を入力した場合は、進捗表回収と進捗表回収日時は必須入力です。',
                ),
            ),
            
            /*
             * 進捗表回収日時
             */
            'progress_report_datetime' => array(
                'NoEmpty' => array(
                    'rule' => 'checkProgress',
                    'message' => '進捗表回収または進捗表回収日時を入力した場合は、進捗表回収と進捗表回収日時は必須入力です。',
                    'last' => true,
                    'allowEmpty' => true,
                ),
                'NotDate' => array(
                    'rule' => 'date',
                ),
            ),
            
            
        );

        
        
        
        /*
         * 取次状況 ステータスバリデーション
         * return trueまたはfalse
         */
        public function checkCommissionStatus(){
            if(empty($this->status_arr)){
                App::import('MItem');
                $MItem = New MItem();
                
                $conditions = array('取次状況', '取次失注理由', '進捗表状況');
                $fields_list = 'item_id, item_name';
            
                //ステータスの変換用データの取得
                $this->status_arr = $MItem->find('list', array(
                        'fields' => $fields_list,
                        'conditions' => array('item_category' => $conditions),
                ));
            }
            
            if(!Hash::get($this->status_arr, $this->data['CommissionInfoCsv']['commission_status'])){
                return false;
            }
            
            return true;
	}
        
        /*
         * 施工完了日 バリデーション
         * return trueまたはfalse
         */
        public function checkCompleteDate(){
            if($this->data['CommissionInfoCsv']['commission_status'] != 3){
                return true;
            }
            
            if(empty($this->data['CommissionInfoCsv']['complete_date'])){
                return false;
            }
            
            return true;
	}
        
        /*
         * 失注日 バリデーション
         * return trueまたはfalse
         */
        public function checkOrderFailDate(){
            if($this->data['CommissionInfoCsv']['commission_status'] != 4){
                return true;
            }
            
            if(empty($this->data['CommissionInfoCsv']['order_fail_date'])){
                return false;
            }
            
            return true;
	}
        
        /*
         * 取次失注理由 ステータスバリデーション
         * return trueまたはfalse
         */
        public function checkCommissionOrderFailReasonStatus(){
            if(!Hash::get($this->status_arr, $this->data['CommissionInfoCsv']['commission_order_fail_reason'])){
                return false;
            }
            
            return true;
        }

        /*
         * 取次失注理由 バリデーション
         * return trueまたはfalse
         */
         public function checkCommissionOrderFailReason(){
            if($this->data['CommissionInfoCsv']['commission_status'] != 4){
                return true;
            }
            
            if(empty($this->data['CommissionInfoCsv']['commission_order_fail_reason'])){
                return false;
            }
            
            return true;
         }
        
        /*
         * 施工金額(税抜) バリデーション
         * return trueまたはfalse
         */
        public function checkConstructionPriceTaxExclude(){
            if($this->data['CommissionInfoCsv']['commission_status'] == 3 || $this->data['CommissionInfoCsv']['commission_status'] == 5){
                if(empty($this->data['CommissionInfoCsv']['construction_price_tax_exclude'])){
                    return false;
                }
            }
            
            return true;
        }
        
        /*
         * 控除金額(税抜) バリデーション
         * return trueまたはfalse
         */
        public function checkConstructionPriceTaxInclude(){
            if($this->data['CommissionInfoCsv']['commission_status'] != 3){
                return true;
            }
            
            if(empty($this->data['CommissionInfoCsv']['construction_price_tax_include'])){
                return false;
            }
            
            return true;
        }
        
        
        
        /*
         * 取次時手数料率　バリデーション
         * return trueまたはfalse
         */
        public function checkCommissionFeeRate(){
            if($this->data['CommissionInfoCsv']['commission_status'] != 3){
                return true;
            }
            
            if(empty($this->data['CommissionInfoCsv']['commission_fee_rate'])){
                return false;
            }
            
            return true;
        }
        
        
        /*
         * 取次時手数料率 double型 バリデーション
         * return trueまたはfalse
         */
        public function checkDoubleCommissionFeeRate(){
            if(!is_numeric($this->data['CommissionInfoCsv']['commission_fee_rate'])){
                return false;
            }
            return true;
        }
        
        /*
         * イレギュラー手数料率 double型 バリデーション
         * return trueまたはfalse
         */
        public function checkDoubleIrregularFeeRate(){
            if(!is_numeric($this->data['CommissionInfoCsv']['irregular_fee_rate'])){
                return false;
            }
            return true;
        }
        
        
        /*
         * 進捗表に関する入力が一つでもあれば、各項目は必須とする
         * return trueまたはfalse
         */
        public function checkProgress(){
            if(empty($this->data['CommissionInfoCsv']['progress_reported']) && empty($this->data['CommissionInfoCsv']['progress_report_datetime'])){
                //進捗表回収、進捗表回収日時が空
                return true;
            } elseif (!empty($this->data['CommissionInfoCsv']['progress_reported']) && !empty($this->data['CommissionInfoCsv']['progress_report_datetime'])){
                //進捗表回収、進捗表回収日時がどちらも入力済み
                return true;
            }
            
            return false;
        }
        
        /*
         * 進捗表状況 ステータスバリデーション
         * return trueまたはfalse
         */
        public function checkProgressStatus(){
            if(empty($this->status_arr)){
                App::import('MItem');
                $MItem = New MItem();
                
                $conditions = array('取次状況', '取次失注理由', '進捗表状況');
                $fields_list = 'item_id, item_name';
            
                //ステータスの変換用データの取得
                $this->status_arr = $MItem->find('list', array(
                        'fields' => $fields_list,
                        'conditions' => array('item_category' => $conditions),
                ));
            }
            
            if(!Hash::get($this->status_arr, $this->data['CommissionInfoCsv']['progress_reported'])){
                return false;
            }
            
            return true;
	}
        
        
}
