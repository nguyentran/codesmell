<?php
/*
 * 2017/05/10  ichino ORANGE-31 ADD
 * 下記のCSVをインポートするコントローラー
 * 手数料請求日CSVインポート
 * 取次管理CSVインポート
 * 加盟店与信限度額CSVインポート
 */

App::uses('AppController', 'Controller');

class CsvImportController extends AppController {
    public $name = 'CsvImport';
    public $components = array('Session', 'RequestHandler');
    public $uses = array('BillInfoCsv', 'CommissionInfoCsv', 'AffiliationInfoCsv', 'CommissionCorrespondCsv', 'AffiliationCorrespond', 'MItem');
    
    //メッセージ
    public $msg_arr = array(
        'normal' => 'アップロードが完了しました。',
        'not_file' => 'アップロードが正常に行えませんでした。ファイルの確認をしてください。',
        'not_file_type' => 'ファイルの種類を確認してください。',
        'line_number_error' => '%s行目：CSVの項目数が異なります。',
        'no_update_data' => '%s行目：対象データが存在しません。更新処理を終了します。',
        'error' => '更新時、エラーが発生しました。',
        'correspond' => 'データインポートにより与信限度額が%s円に更新されました',
        
        'log_file_err' => 'CSVインポート：投入されたファイルの形式が正しくありません。',
        'log_line_number_err' => 'CSVインポート：項目数エラーです。',
        'log_update_err' => '%s CSVインポート：更新対象のデータがありません。',
        'log_validation_err' => '%s CSVインポート：バリデーションエラーです。',
        'log_import_err' => '%s CSVインポート：インポート処理に失敗しました。',
        
        'log_update_set_commission_err' => '%s CSVインポート：更新対象のデータがありません。実行結果：%s行目 %s',
    );
    
    //複数のエラーが発生した場合のメッセージ格納用
    public $csv_error_msg = array();
    
    //成功メッセージクラス
    public $s_msg_class    = array('class' => 'message_inner');
    //エラーメッセージクラス
    public $e_msg_class    = array('class' => 'error_inner');         
    
    //許可するファイルタイプ
    public $allow_type_arr = array(                                     
	'text/csv'       => true,
        'application/vnd.ms-excel' => true,
    );
    
    //csvの項目数
    public $csv_lines = array(
        'bill_infos_csv' => 7,
        'commission_infos_csv' => 17,
        'affiliation_infos_csv' => 2,
        
    );
    
    //ステータスの変換用
    public $status_arr = array();
    
    //CSVの項目順とカラム名を関連づけ
    public $table_column = array(
        'BillInfoCsv' => array(
            0 => 'corp_id',                             //加盟店ID
            1 => 'demand_id',                           //案件ID
            2 => 'commission_id',                       //取次ID	
            3 => 'bill_info_id',                        //請求ID
            4 => 'bill_status',                         //請求状況
            5 => 'fee_billing_date',                    //手数料請求日
            6 => 'fee_payment_date',                    //手数料入金日
        ),
        'BillInfo' => 'bill_info_id',                   //請求ID
        'CommissionInfoCsv' => array(
            0 => 'corp_id',                             //加盟店ID
            1 => 'demand_id',                           //案件ID
            2 => 'commission_info_id',                  //取次ID
            3 => 'commission_status',                   //取次状況
            4 => 'complete_date',			//施工完了日
            5 => 'order_fail_date',			//失注日
            6 => 'commission_order_fail_reason',	//取次失注理由
            7 => 'construction_price_tax_exclude',	//施工金額(税抜)
            8 => 'construction_price_tax_include',	//控除金額(税込)
            9 => 'commission_fee_rate',			//取次時手数料率
            10 => 'irregular_fee_rate',                 //イレギュラー手数料率
            11 => 'irregular_fee',			//イレギュラー手数料金額(税抜)
            12 => 'progress_reported',			//進捗表回収
            13 => 'progress_report_datetime',		//進捗表回収日時
        ),
        'CommissionInfo' => 'commission_info_id',       //取次ID
        'CommissionCorrespondCsv' => array(
            14 => 'responders',                         //対応者
            15 => 'corresponding_contens',              //対応内容
            16 => 'correspond_datetime',                //対応日時
        ),
        'AffiliationInfoCsv' => array(
            0 => 'corp_id',                             //加盟店ID
            1 => 'credit_limit',                        //与信限度額
        ),
        'AffiliationCorrespond' => array(
            2 => 'responders',                          //対応者
            3 => 'corresponding_contens',               //対応内容
            4 => 'progress_report_datetime',            //対応日時
        ),
    );
    
    //カラム名と項目名を関連づけ
    public $item_name = array(
        'BillInfoCsv' => array(
            'bill_info_id' => '請求ID',		
            'corp_id' => '加盟店ID',
            'demand_id' => '案件ID',
            'commission_id' => '取次ID',
            'bill_status' => '請求状況',
            'fee_billing_date' => '手数料請求日',
            'fee_payment_date' => '手数料入金日',
        ),
        'CommissionInfoCsv' => array(
            'corp_id' => '加盟店ID',
            'demand_id' => '案件ID',
            'commission_info_id' => '取次ID',
            'commission_status' => '取次状況',
            'complete_date' => '施工完了日',
            'order_fail_date' => '失注日',
            'commission_order_fail_reason' => '取次失注理由',
            'construction_price_tax_exclude' => '施工金額(税抜)',
            'construction_price_tax_include' => '控除金額(税込)',
            'commission_fee_rate' => '取次時手数料率',
            'irregular_fee_rate' => 'イレギュラー手数料率',
            'irregular_fee' => 'イレギュラー手数料金額(税抜)',
            'progress_reported' => '進捗表回収',                //進捗表回収済みフラグ
            'progress_report_datetime' => '進捗表回収日時',
        ),
        'CommissionCorrespondCsv' => array(
            'responders' => '対応者',
            'corresponding_contens' => '対応内容',
            'correspond_datetime' => '対応日時',
        ),
        'AffiliationInfoCsv' => array(
            'corp_id' => '加盟店ID',
            'credit_limit' => '与信限度額',
        ),
        'AffiliationCorrespond' => array(
            'responders' => '対応者',
            'corresponding_contens' => '対応内容',
            'correspond_datetime' => '対応日時',
        ),
        );
    


    //csvを取り出した後の結果格納用
    public $records = array();
    
    public function beforeFilter(){
        parent::beforeFilter();
        $this->User = $this->Auth->user();
    }
    
    /*
     * CSVのインポート
     */
    public function index(){
        /* 共通変数の初期化 */
        $today          = date('Y-m-d H:i:s');                       // 実行日の日付
	
        //post送信判定
        if ($this->request->is('post')){
            //ステータスの変換用データの取得
            $this->status_arr = $this->getStatusList(array('請求状況', '取次状況', '取次失注理由', '進捗表状況'));
            
            /*
             * ボタンのname属性でどのボタンが押されたかを判別する
             *押されたボタンに該当するファイルが選択・送信されているか判別する
             */
            if(Hash::get($this->request->data, 'bill_infos_btn') && Hash::get($this->request->data, 'bill_infos_csv.size') !== 0){
                //ファイルの種類を判定する
                if($this->_file_type_check($this->request->data['bill_infos_csv']['type'])){
                    //CSVの中身を取り出す 各行の項目数に問題がないかも同時にチェックを行う
                    if(!$this->_get_csv_data('bill_infos_csv')){
                        //エラー
                        $this->_set_message($this->csv_error_msg, $this->e_msg_class);
                        $this->log($this->msg_arr['log_file_err']);
                    } else {  
                        if(!empty($this->records)){
                            //ヘッダーの削除
                            array_shift($this->records);

                            /*
                             * 各項目をバリデーションチェックを行う
                             * エラーがあった場合は、CSVの何行目がエラーかを配列に格納
                             * テーブルのカラムを判定するため、モデル名を渡す
                             */
                            $save_data = $this->_csv_data_validate('BillInfoCsv', 'BillInfo');
                            
                            if(!empty($this->csv_error_msg)){
                                $this->_set_message($this->csv_error_msg, $this->e_msg_class);
                                //$this->log(sprintf($this->msg_arr['log_validation_err'], 'BillInfo'));
                            }

                            if(!empty($save_data)){
                                //更新対象対象のデータがテーブルにあるか確認する
                                
                                //更新対象の検索用条件
                                foreach ($save_data as $keys => $values) {
                                    foreach ($values as $key => $value) {
                                        $conditions[] = $value['id'];
                                    }
                                }
                                
                                /*
                                 * 更新対象の検索
                                 * find listでidを取得し、csvのidと比較する
                                 * 更新対象があるかどうか保存前に確認する
                                 */
                                $save_data_id_list = $this->BillInfoCsv->find('list', Array('conditions' => Array('id' => $conditions)));
                                
                                foreach ($save_data as $keys => $values) {
                                    //更新対象のデータがDB内に存在するか検索
                                    if(!array_search(Hash::get($values, "BillInfoCsv.id"), $save_data_id_list)){
                                        //更新対象なし
                                        $this->csv_error_msg[][] = sprintf($this->msg_arr['no_update_data'], $key + 2);
                                    }
                                }
                                
                                if(!empty($this->csv_error_msg)){
                                    //更新対象がないデータがあるのでエラー
                                    $this->_set_message($this->csv_error_msg, $this->e_msg_class);
                                    $this->log(sprintf($this->msg_arr['log_update_err'], 'BillInfo'));
                                } else {
                                    //データの保存
                                    try{
                                        $this->BillInfoCsv->begin();
                                        
                                        if ($this->BillInfoCsv->saveAll($save_data, array('validate' => false, 'atomic' => false))) {
                                            $this->BillInfoCsv->commit();
                                            
                                            //保存を行ったデータの取得を行う
                                            //加盟店ID取得のため、commission_infosと結合する
                                            $option['fields'] = array(
                                                    'BillInfoCsv.id',
                                                    'BillInfoCsv.commission_id',
                                                    'BillInfoCsv.demand_id',
                                                    'BillInfoCsv.bill_status',
                                                    'BillInfoCsv.fee_billing_date',
                                                    'BillInfoCsv.fee_payment_date',
                                                    'CommissionInfoCsv.id',
                                                    'CommissionInfoCsv.corp_id',
                                            );
                                            $option['conditions'] = array('BillInfoCsv.id' => $conditions);
                                            $option['joins'][] = array(
                                                'type' => 'INNER',
                                                'table' => 'commission_infos',
                                                'alias' => 'CommissionInfoCsv',
                                                'conditions' => 'BillInfoCsv.commission_id = CommissionInfoCsv.id',
                                            );
                                            
                                            //ページネーション設定
                                            $this->paginate = $option;

                                            //データの取得
                                            $save_data_list = $this->paginate('BillInfoCsv');
                                            
                                            //ステータス変換用データをviewへ渡す
                                            //'取次状況', '取次失注理由', '進捗表状況'
                                            $this->set('bill_status_arr', $this->getStatusList(array('請求状況')));
                                            
                                            //データをviewへ渡す
                                            $this->set('save_data_list', $save_data_list);
                                            
                                            //どのCSVを送信したかの判定用
                                            $this->set('csv_type', 'bill_infos');
                                            
                                            $this->_set_message($this->msg_arr['normal'], $this->s_msg_class);
                                        } else {
                                            $this->BillInfoCsv->rollback();
                                            $this->_set_message($this->msg_arr['error'], $this->e_msg_class);
                                        }
                                        
                                    }catch(Exception $e){
                                        $this->BillInfoCsv->rollback();
                                        
                                        $this->_set_message($this->msg_arr['error'], $this->e_msg_class);
                                        $this->log(sprintf($this->msg_arr['log_import_err'], 'BillInfo') . $e->getMessage());
                                    }
                                }
                            }
                        }
                    }
                }
            } else if(Hash::get($this->request->data, 'commission_infos_btn') && Hash::get($this->request->data, 'commission_infos_csv.size') !== 0){
                //ファイルの種類を判定する
                if($this->_file_type_check($this->request->data['commission_infos_csv']['type'])){
                    //CSVの中身を取り出す 各行の項目数に問題がないかも同時にチェックを行う
                    if(!$this->_get_csv_data('commission_infos_csv')){
                        //エラー
                        $this->_set_message($this->csv_error_msg, $this->e_msg_class);
                        $this->log($this->msg_arr['log_file_err']);
                    } else {
                        if(!empty($this->records)){
                            //ヘッダーの削除
                            array_shift($this->records);
                            
                            /*
                             * 各項目をバリデーションチェックを行う
                             * エラーがあった場合は、CSVの何行目がエラーかを配列に格納
                             * テーブルのカラムを判定するため、モデル名を渡す
                             */
                            $save_data = $this->_csv_data_validate('CommissionInfoCsv', 'CommissionInfo');
                            $save_data2 = $this->_csv_data_validate('CommissionCorrespondCsv');
                            
                            if(!empty($this->csv_error_msg)){
                                $this->_set_message($this->csv_error_msg, $this->e_msg_class);
                                //$this->log(sprintf($this->msg_arr['log_validation_err'], 'CommissionInfo'));
                            }
                            
                            //対応内容はない場合があるため、save_dataのみ確認
                            if(!empty($save_data)){
                                //更新対象対象のデータがテーブルにあるか確認する
                                
                                //set_commission2を実行し、対象データが存在するかを判定
                                foreach ($save_data as $keys => $values) {
                                    $sql = "select set_commission2(?,?,?,?,'',NULL,?,'','',0)";
                                    $param = array(
                                        Hash::get($values, "CommissionInfoCsv.demand_id"),
                                        Hash::get($values, "CommissionInfoCsv.id"),
                                        Hash::get($values, "CommissionInfoCsv.commission_status"),
                                        Hash::get($values, "CommissionInfoCsv.complete_date"),
                                        (int) Hash::get($values, "CommissionInfoCsv.construction_price_tax_exclude"),
                                    );
                                    $set_commission_status = $this->CommissionInfoCsv->query($sql, $param, false);
                                    
                                    //返り値が0以外のため、エラーとする
                                    if(Hash::get($set_commission_status, "0.0.set_commission2") != 0){
                                        $this->csv_error_msg[]['CommissionInfoCsv'] = sprintf($this->msg_arr['no_update_data'], $keys + 2);
                                        
                                        //set_commission2 を使用するので、ログを出力
                                        $this->log(sprintf($this->msg_arr['log_update_set_commission_err'], 'set_commission', $keys + 2, $set_commission_status[0][0]['set_commission2']));
                                    }
                                }
                                
                                if(!empty($this->csv_error_msg)){
                                    //エラー
                                    $this->_set_message($this->csv_error_msg, $this->e_msg_class);
                                } else {
                                    //データの保存
                                    try{
                                        $this->CommissionInfoCsv->begin();
                                        $this->CommissionCorrespondCsv->begin();
                                        
                                        //更新内容の整形 取次IDを追加
                                        foreach ($save_data2 as $key => $value) {
                                            if(!empty($value['CommissionCorrespondCsv']['responders'])){
                                                $save_data2[$key]['CommissionCorrespondCsv']['commission_id'] = $save_data[$key]['CommissionInfoCsv']['id'];
                                            } else {
                                                //対象データ無しのため、配列から削除
                                                unset($save_data2[$key]);
                                            }
                                        }
                                        
                                        
                                        //saveallでatomic=falseを指定しているため、別途結果格納、結果精査用を用意
                                        $save_data_result = false;
                                        $save_data_result_tmp = array();
                                        $save_data2_result = false;
                                        $save_data2_result_tmp = array();
                                        
                                        if(!empty($save_data) && !empty($save_data2)){
                                            $save_data_result_tmp = $this->CommissionInfoCsv->saveAll($save_data, array('validate' => false, 'atomic' => false));
                                            $save_data2_result_tmp = $this->CommissionCorrespondCsv->saveAll($save_data2, array('validate' => false, 'atomic' => false));
                                        } else if(!empty($save_data) && empty($save_data2)){
                                            //対応内容は登録しない場合あり
                                            $save_data_result_tmp = $this->CommissionInfoCsv->saveAll($save_data, array('validate' => false, 'atomic' => false));
                                        }
                                        
                                        foreach ($save_data_result_tmp as $key => $value) {
                                            if(!$value){
                                                $save_data_result = false;
                                                break;
                                            } else {
                                                $save_data_result = true;
                                            }
                                        }
                                        
                                        if(!empty($save_data2)){
                                            foreach ($save_data2_result_tmp as $key => $value) {
                                                if(!$value){
                                                    $save_data2_result = false;
                                                    break;
                                                } else {
                                                    $save_data2_result = true;
                                                }
                                            }
                                        } else {
                                            $save_data2_result = true;
                                        }
                                        
                                        if ($save_data_result === true && $save_data2_result === true) {
                                            $this->CommissionInfoCsv->commit();
                                            
                                            if(!empty($save_data) && !empty($save_data2)){
                                                $this->CommissionCorrespondCsv->commit();
                                            }
                                            
                                            //保存を行ったデータの取得を行う
                                            //更新対象の検索用条件
                                            foreach ($save_data as $key => $value) {
                                                $conditions[] = $value['CommissionInfoCsv']['id'];
                                            }
                                            
                                            //保存を行ったデータの取得を行う
                                            //加盟店ID取得のため、commission_infosと結合する
                                            $option['fields'] = array(
                                                'corp_id',
                                                'demand_id',
                                                'id',
                                                'commission_status',
                                                'complete_date',
                                                'order_fail_date',
                                                'commission_order_fail_reason',
                                                'construction_price_tax_exclude',
                                                'construction_price_tax_include',
                                                'commission_fee_rate',
                                                'irregular_fee_rate',
                                                'irregular_fee',
                                                'progress_reported',
                                                'progress_report_datetime',
                                            );
                                            $option['conditions'] = array('CommissionInfoCsv.id' => $conditions);
                                            
                                            //ページネーション設定
                                            $this->paginate = $option;
                                            
                                            //データの取得
                                            $save_data_list = $this->paginate('CommissionInfoCsv');
                                            
                                            //ステータス変換用データをviewへ渡す
                                            $this->set('commission_status_arr', $this->getStatusList(array('取次状況')));
                                            $this->set('commission_order_fail_reason_arr', $this->getStatusList(array('取次失注理由')));
                                            $this->set('progress_reported_arr', $this->getStatusList(array('進捗表状況')));
                                            
                                            //データをviewへ渡す
                                            $this->set('save_data_list', $save_data_list);
                                            
                                            //どのCSVを送信したかの判定用
                                            $this->set('csv_type', 'commission_infos_csv');
                                            
                                            $this->_set_message($this->msg_arr['normal'], $this->s_msg_class);
                                        } else {
                                            $this->CommissionInfoCsv->rollback();
                                            $this->CommissionCorrespondCsv->rollback();
                                            
                                            $this->_set_message($this->msg_arr['error'], $this->e_msg_class);
                                        }
                                    } catch (Exception $e){
                                        $this->CommissionInfoCsv->rollback();
                                        $this->CommissionCorrespondCsv->rollback();
                                        
                                        $this->_set_message($this->msg_arr['error'], $this->e_msg_class);
                                        $this->log(sprintf($this->msg_arr['log_import_err'], 'CommissionInfo,CommissionCorrespond') . $e->getMessage());
                                    }
                                }

                                
                            }
                        }
                    }
                }
            } else if(Hash::get($this->request->data, 'affiliation_infos_btn') && Hash::get($this->request->data, 'affiliation_infos_csv.size') !== 0){
                //ファイルの種類を判定する
                if($this->_file_type_check($this->request->data['affiliation_infos_csv']['type'])){
                    //CSVの中身を取り出す 各行の項目数に問題がないかも同時にチェックを行う
                    if(!$this->_get_csv_data('affiliation_infos_csv')){
                        //エラー
                        $this->_set_message($this->csv_error_msg, $this->e_msg_class);
                        $this->log($this->msg_arr['log_file_err']);
                    } else {
                        if(!empty($this->records)){
                            //ヘッダーの削除
                            array_shift($this->records);

                            /*
                             * 各項目をバリデーションチェックを行う
                             * エラーがあった場合は、CSVの何行目がエラーかを配列に格納
                             * テーブルのカラムを判定するため、モデル名を渡す
                             */
                            $save_data = $this->_csv_data_validate('AffiliationInfoCsv');
                            if(!empty($this->csv_error_msg)){
                                    $this->_set_message($this->csv_error_msg, $this->e_msg_class);
                                    //$this->log(sprintf($this->msg_arr['log_validation_err'], 'AffiliationInfo'));
                            }
                            
                            if(!empty($save_data)){
                                //更新対象対象のデータがテーブルにあるか確認する
                                
                                //更新対象の検索用条件
                                foreach ($save_data as $keys => $values) {
                                    foreach ($values as $key => $value) {
                                        $conditions[] = $value['corp_id'];
                                    }
                                }
                                
                                /*
                                 * 更新対象の検索
                                 * find listでcorp_idを取得し、csvのcorp_idと比較する
                                 * 更新対象があるかどうか保存前に確認する
                                 */
                                $save_data_id_list = $this->AffiliationInfoCsv->find('list',
                                        Array(
                                            'fields' => 'id, corp_id',
                                            'conditions' => Array('corp_id' => $conditions),
                                        )
                                );
                                
                                foreach ($save_data as $keys => $values) {
                                    //更新対象のデータがDB内に存在するか検索
                                    //idを一旦変数に格納する
                                    $id = array_search(Hash::get($values, "AffiliationInfoCsv.corp_id"), $save_data_id_list);
                                    
                                    if(!$id){
                                        //更新対象なし
                                        $this->csv_error_msg[][] = sprintf($this->msg_arr['no_update_data'], $keys + 2);
                                    } else {
                                        //idを保存用データに追加
                                        $save_data[$keys]['AffiliationInfoCsv']['id'] = $id;
                                    }
                                }
                                                             
                                if(!empty($this->csv_error_msg)){
                                    //更新対象がないデータがあるのでエラー
                                    $this->_set_message($this->csv_error_msg, $this->e_msg_class);
                                    $this->log(sprintf($this->msg_arr['log_update_err'], AffiliationInfo));
                                } else {
                                    //更新履歴用の保存データを作成 AffiliationCorrespond
                                    $save_data2 = array();
                                    foreach ($save_data as $key => $value) {
                                        $save_data2[$key]['AffiliationCorrespond']['corp_id'] = $value['AffiliationInfoCsv']['corp_id'];
                                        $save_data2[$key]['AffiliationCorrespond']['correspond_datetime'] = $today; //対応日時
                                        $save_data2[$key]['AffiliationCorrespond']['responders'] = $this->User['id'];        //対応ユーザー
                                        $save_data2[$key]['AffiliationCorrespond']['corresponding_contens'] = sprintf($this->msg_arr['correspond'], $value['AffiliationInfoCsv']['credit_limit']);
                                    }
                                    
                                    //データの保存
                                    try{
                                        $this->AffiliationInfoCsv->begin();
                                        $this->AffiliationCorrespond->begin();
                                        
                                        if ($this->AffiliationInfoCsv->saveAll($save_data, array('validate' => false, 'atomic' => false)) && $this->AffiliationCorrespond->saveAll($save_data2, array('validate' => false, 'atomic' => false))) {
                                            $this->AffiliationInfoCsv->commit();
                                            $this->AffiliationCorrespond->commit();
                                        
                                        
                                        $option['fields'] = array(
                                                'AffiliationInfoCsv.id',
                                                'AffiliationInfoCsv.corp_id',
                                                'AffiliationInfoCsv.credit_limit',
                                        );
                                        $option['conditions'] = array('AffiliationInfoCsv.corp_id' => $conditions);
                                        
                                        //ページネーション設定
                                        $this->paginate = $option;
                                        
                                        //データの取得
                                        $save_data_list = $this->paginate('AffiliationInfoCsv');
                                        
                                        //データをviewへ渡す
                                        $this->set('save_data_list', $save_data_list);
                                         
                                        //どのCSVを送信したかの判定用
                                        $this->set('csv_type', 'affiliation_infos');
                                            
                                        $this->_set_message($this->msg_arr['normal'], $this->s_msg_class);
                                        } else {
                                            $this->AffiliationInfoCsv->rollback();
                                            $this->AffiliationCorrespond->rollback();
                                            $this->_set_message($this->msg_arr['error'], $this->e_msg_class);
                                        }
                                        
                                    } catch (Exception $e){
                                        $this->AffiliationInfoCsv->rollback();
                                        $this->AffiliationCorrespond->rollback();
                                        
                                        $this->_set_message($this->msg_arr['error'], $this->e_msg_class);
                                        $this->log(sprintf($this->msg_arr['log_import_err'], 'AffiliationInfo,AffiliationCorrespond') . $e->getMessage());
                                    }
                                }
                            }
                        }
                    }
                }
            } else {
                //ファイル未送信
                $this->_set_message($this->msg_arr['not_file'], $this->e_msg_class);
                $this->log($this->msg_arr['not_file']);
            }
        }
    }
    
    /*
     * 指定した形式でメッセージを返却する
     * 指定したアドレスへリダイレクトする
     * param メッセージ自体または配列
     * param 正常メッセージかエラーメッセージ
     */
    private function _set_message($msg, $msg_type){
        if(is_array($msg)){
            $msg_tmp = array();
            
            //配列に格納されているエラーメッセージを整形する
            foreach ($msg as $keys => $values) {
                foreach ($values as $key => $value) {
                    $msg_tmp[] = $value;
                }
            }
            $this->Session->setFlash(implode('<br>', $msg_tmp), 'default', $msg_type);
        } else {
            $this->Session->setFlash($msg, 'default', $msg_type);
        }
    }


    /*
     * ファイルの種類を判定する
     * param 送信データの種類
     * return trueまたはfalse
     */
    private function _file_type_check($file_type){
       if(isset($this->allow_type_arr[$file_type])){
           return true;
       } else {
            //ファイル無し
            $this->_set_message($this->msg_arr['not_file_type'], $this->e_msg_class);
            $this->log($this->msg_arr['log_file_err']);
       }
    }
    
    /*
     * CSVの中身を取り出す
     * 各行の項目数に問題がないかも同時にチェックを行う
     * param csvの内容を取り出すための添え字。取り込んだcsvにより異なるため呼び出し元から取得
     * return trueまたはfalse
     */
    private function _get_csv_data($index_name){
        //fileオブジェクトに送信された内容を格納
        $splFileObject = new SplFileObject($this->request->data[$index_name]['tmp_name']);

        //CSVモードに変更
        $splFileObject->setFlags(SplFileObject::READ_CSV);

        //中身の取り出し
        foreach ( $splFileObject as $key => $line )
        {
            if(!is_null($line[0])){
                $this->records[] = $line;
            } else{
                //最後の行などの空の場合はスキップする
                continue;
            }
            
            //カラム数判定
            if (count($line) !== $this->csv_lines[$index_name] && $key != 0) {
                //CSVの何行目がエラーかを配列に格納
                $this->csv_error_msg[][] = sprintf($this->msg_arr['line_number_error'], $key + 1);
            }
        }
        if(!empty($this->csv_error_msg)){
            //エラーあり
            return false;
        } else {
            //エラーなし
            //CSVのヘッダーから文字コードを判定する
            $top_record = array_shift(Hash::get($this->records , 0));          
            $csv_encod = mb_detect_encoding($top_record, 'JIS, SJIS, eucjp-win, sjis-win');
            
            //CSVの内容をUTF-8に変換 多次元に対応してないため、foreachでCSVを一行ずつ変換する
            foreach ($this->records as $key => $value) {
                //変換
                mb_convert_variables('UTF-8', $csv_encod , $value);   
                    //元の配列へ再格納
                    $this->records[$key] = $value;
                }
                
                return true;
        }
    }
    
    /*
     * CSVで取り込んだデータのバリデーションを行う
     * param csvの内容を取り出すための添え字。取り込んだcsvにより異なるため呼び出し元から取得
     * return 整形済みデータ配列
     */
    private function _csv_data_validate($index_name, $save_model_column  = null){
            $data = array();

            //取得したデータを配列[対象カラム]に格納していく
            foreach ($this->records as $keys => $values) {
                foreach ($values as $key => $value) {
                    if(Hash::get($this->table_column, "{$index_name}.{$key}")){
                        //ステータスは、数値に変換しておく
                        if($this->table_column[$index_name][$key] == 'bill_status'
                                || $this->table_column[$index_name][$key] == 'commission_status'
                                || $this->table_column[$index_name][$key] == 'commission_order_fail_reason'
                                || $this->table_column[$index_name][$key] == 'progress_reported'){
                            //請求状況を数値に変換する
                            $status_tmp = Hash::get($this->status_arr, $value, null);
                            if(!empty($status_tmp)){
                                $data[$keys][$index_name][$this->table_column[$index_name][$key]] = $this->status_arr[$value];
                            } else {
                                //変換できない場合は、CSVの値をそのまま格納しておく
                                $data[$keys][$index_name][$this->table_column[$index_name][$key]] = $value;
                            }
                        } else {
                            $data[$keys][$index_name][$this->table_column[$index_name][$key]] = $value;
                        }
                    }
                }
            }
            
            try{
                if ($this->$index_name->saveAll($data, array('validate' => 'only'))) {
                    // バリデーションOKの場合の処理
                    
                    //バリデーション実行の際に、idのみ列名を変更したため、idに戻す
                    if(!empty($save_model_column)){
                        $data_tmp = array();
                        
                        //正しい列名を取得
                        $column = $this->table_column[$save_model_column];
                        
                        foreach ($data as $key => $value) {
                            $data[$key][$index_name]['id'] = $data[$key][$index_name][$column];
                            //不要なカラムの削除
                            unset($data[$key][$index_name][$column]);
                        }
                    }
                    
                    return $data;
                } else {
                    //エラーメッセージの格納
                    foreach($this->$index_name->validationErrors as $keys => $values) {
                        foreach ($values as $key => $value) {
                           //ヘッダー分があるので、エラー行は+2
                           $this->csv_error_msg[$keys][] = $keys + 2 . "行目：" . Hash::get($this->item_name, "{$index_name}.{$key}"). '　' . Hash::get($value, 0);
                        }
                    }
                }
            } catch (Exception $e){
                //エラーメッセジ
                $this->log($index_name . ' ' . $e->getMessage());
            }   
    }
    
    /*
     * 各ステータスの値の変換を行う
     */
    private function getStatusList($conditions){
        $fields_list = 'item_name, item_id';
            
        //ステータスの変換用データの取得
        $status_arr_tmp = $this->MItem->find('list', array(
                'fields' => $fields_list,
                'conditions' => array('item_category' => $conditions),
        ));
        
        if(empty($status_arr_tmp)){
            return false;
        }
        
        return $status_arr_tmp;
    }
}
