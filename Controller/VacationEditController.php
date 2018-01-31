<?php
/*
 * 2017/07/11 ichino ORANGE-459 ADD
 * 休業日設定画面
 */

App::uses('AppController', 'Controller');

class VacationEditController extends AppController{
    public $name = 'VacationEdit';
    public $components = array('Session', 'RequestHandler');
    public $uses = array('MItem', 'MCorpNewYear');
    
    public $prefecture_list;

    public function beforeFilter(){
        parent::beforeFilter();
        $this->User = $this->Auth->user();
    }
    
    /*
     * 初期画面
     */
    public function index(){
        //m_itemsに保存されている休業日を取得し、viewへ引き渡す
        $vacations = $this->MItem->getList('長期休業日');
        
        //post送信判定 削除、保存処理
        if ($this->request->is('post')){
            //データ受け取り(10個)
            $data = $this->request->data;

            //保存データの作成
            foreach (Hash::get($data, 'MItem') as $key => $value) {
                $vacations[$key] = $data['MItem'][$key]['item_name'];
                if(!empty($data['MItem'][$key]['item_name'])){
                    $data['MItem'][$key]['item_id'] = $key;
                    $data['MItem'][$key]['item_category'] = '長期休業日'; //固定
                    $data['MItem'][$key]['sort_order'] = $key;
                    $data['MItem'][$key]['enabled_start'] = date('Y/m/d'); 
                    $data['MItem'][$key]['created_user_id'] = $this->User['user_id'];
                    $data['MItem'][$key]['modified_user_id'] = $this->User['user_id'];
                }
            }

            //データの整形
            foreach(Hash::get($data, 'MItem') as $key => $value) {
                if(!empty($value['item_name'])){
                } else {
                    unset($data['MItem'][$key]);
                }
            }
            
            try{
                $this->MItem->begin();
                $this->MCorpNewYear->begin();
                
                //すでに保存されている長期休業日を削除
                if($this->MItem->deleteAll(array('MItem.item_category' => '長期休業日'), false) && $this->MCorpNewYear->deleteAll(array('1=1', false))){
                //if($this->MItem->deleteAll(array('MItem.item_category' => '長期休業日'), false) && $this->MCorpNewYear->deleteAll(array('MCorpNewYear.corp_id' => '167128'), false)){
                    //入力された内容を保存
                    if(!empty($data['MItem'])){
                        //バリデーション追加
                        $validate = array(
                            'item_name' => array(
                                'date' => array(
                                    'rule' => array('custom', '/^(([1-9])|(0[1-9])|1[0-2])\/((0[1-9]|[1-9])|[12][0-9]|3[01])$/'),
                                    'allowEmpty' => true,
                                ),
                            ),
                        );
                        $this->MItem->validate = $validate;
                        
                        if($this->MItem->saveAll($data['MItem'], array('validate' => true))){
                            $this->MItem->commit();
                            $this->MCorpNewYear->commit();
                            
                            //保存後、m_itemsの長期休業日を取得し、viewへ引き渡す
                            $this->set('vacation', $this->MItem->getList('長期休業日'));
                            $this->Session->setFlash ( __ ('SuccessRegist', true), 'default', array('class' => 'message_inner'));
                        } else {
                            //1件でも保存エラーがあればエラーとする
                            $this->Session->setFlash ( __ ('FailRegist', true) . '入力日付の形式を確認してください。', 'default', array('class' => 'error_inner'));
                        }
                    } else {
                        //削除完了、保存はなし
                        $this->MItem->commit();
                        $this->MCorpNewYear->commit();
                        
                        //m_itemsの長期休業日を取得し、viewへ引き渡す
                        $this->set('vacation', $this->MItem->getList('長期休業日'));
                        $this->Session->setFlash ( __ ('SuccessRegist', true), 'default', array('class' => 'message_inner'));
                    }
                } else {
                    //保存エラー
                    $this->MItem->rollback();
                    $this->MCorpNewYear->rollback();
                    
                    $this->set('vacation', $data);
                    
                    $this->Session->setFlash ( __ ('FailRegist', true), 'default', array('class' => 'error_inner'));
                }
            } catch (Exception $e) {
                $this->Session->setFlash ( __ ('FailRegist', true), 'default', array('class' => 'error_inner'));
		$this->log('m_items 削除・保存エラー' . $e->getMessage());
            }
        }
        $this->set('vacation', $vacations);
    }
}