<?php
/*
 * 2017/08/03  ichino ORANGE-456 ADD
 * 入札案内メール開封時処理
 */


class AccumulatedInformationController extends Controller {
    public $name = 'AccumulatedInformation';
    
    public $helpers = array();

    public $uses = array('AccumulatedInformation',);
    
    public function beforeFilter(){
        
    }

    public function mail_open() {
        //画面なし
        $this->autoRender = false;
        
        //ヘッダー指定
        header("Content-Type: image/gif");
        
        //画像保存先
        $img_url = Configure::read('mail_img_url');
        
        if (!$this->request->isAll(array('get'))) {
            //throw new BadRequestException();
            
            //処理を行わず、画像を返す
            return readfile($img_url);
        }
        
        //案件ID
        $demand_id = Hash::get($this->request->query, 'demand_id');
        
        //企業ID
        $corp_id = Hash::get($this->request->query, 'corp_id');
        
        if(empty($demand_id) || empty($corp_id)){
            //処理を行わず、画像を返す
            return readfile($img_url);
        }
        
        try{
            //更新対象を取得
            $result = $this->AccumulatedInformation->getInfos($corp_id, $demand_id, 0);
            //IDを取得
            $info_id = Hash::get($result, '0.AccumulatedInformation.id');
            
            if(!empty($info_id)){
                //更新内容を作成
                $save_data['AccumulatedInformation'] = array(
                    'id' => $info_id,    //ID
                    'mail_open_flag' => 1,    //開封フラグ
                    'mail_open_date' => date('Y-m-d H:i'),    //メール開封日時
                    'modified_user_id' => $corp_id,    //更新者
                );
                
                //AppModelのBeforeSaveでcreated_user_id, modified_user_idを空文字で上書きしてしまうためコールバックを無効にする
                $this->AccumulatedInformation->save($save_data, array(
                    'validate' => false,
                    'callbacks' => false
		));
            }
        } catch (Exception $e) {
             $this->log('AccumulatedInformation 保存エラー' . $e->getMessage());
        }

        
        //画像を返す URL
        return readfile($img_url);
    }
}
