<?php
/*
 * 2017/08/03  ichino ORANGE-456 ADD
 * 入札案内メール開封時処理
 */

/*
 * AccumulatedInformation Model
 */
class AccumulatedInformation extends AppModel {
    
    //使用テーブル
    public $useTable = 'accumulated_informations';
    
    //バリデーション
    public $validate = array();
    
    
    /*
     * 指定した案件IDと企業IDから、データを取得し返却
     * 企業ID、メールフラグは引数になくてもよい
     */
    public function getInfos($corp_id = null, $demand_id, $mail_open_flag = null) {
         //条件を作成
        $option['conditions'][] = array(
            'AccumulatedInformation.demand_id' => $demand_id,    //案件ID
        );
        
        if(!is_null($corp_id)){
            $option['conditions'][] = array(
                'AccumulatedInformation.corp_id' => $corp_id,    //企業ID
            );
        }
        
        if(!is_null($mail_open_flag)){
            $option['conditions'][] = array(
                'AccumulatedInformation.mail_open_flag' => $mail_open_flag,    //開封フラグ
            );
        }
        
        return $this->find('all', $option);  
    }
    
}
