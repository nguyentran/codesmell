<?php

class DeviceInfo extends AppModel {
    public $name = 'DeviceInfo';
    
    /**
     * ユーザIDに紐づくエンドポイント取得
     * @param type $user_id
     * @return type
     */
    public function getEndpoints($user_id) {
        $option = array();
        //重複データ対策
        $option['recursive'] = -1;
        $option['conditions'] = array(
            'DeviceInfo.user_id' => $user_id,
        );
        $results = $this->find('all', $option);
        
        return $results;
    }
    
    /**
     * device_infosにレコードを登録する
     * @param type $datas
     * @return boolean
     */
    public function insertDeviceInfo($datas) {
        $set_data = array();
        
        foreach ($datas as $key => $data) {
            $set_data[$key] = $data;
        }
        $data = array('DeviceInfo' => $set_data);

        // 登録する項目（フィールド指定）
        $fields = array_keys($set_data);

        // 新規登録
        $result = $this->save($data, false, $fields);
        
        return $result;
    }
    
    /**
     * device_infosにレコードを更新する
     * @param type $id
     * @param type $datas
     * @return boolean
     */
    public function updateDeviceInfo($id, $datas) {
        $set_data = array();
        // 更新するID
        $set_data['id'] = $id;
        foreach ($datas as $key => $data) {
            $set_data[$key] = $data;
        }
        $data = array('DeviceInfo' => $set_data);

        // 登録する項目（フィールド指定）
        $fields = array_keys($set_data);

        // 更新
        $result = $this->save($data, false, $fields);
        
        return $result;
    }
    
    /**
     * DBのエンドポイントを削除する
     * @param type $datas
     * @return type
     */
    public function deleteAllDeviceInfo($datas) {
        $set_data = array();
        
        foreach ($datas as $key => $data) {
            $set_data[$key] = $data;
        }
        //削除
        $result = $this->deleteAll($set_data, false);
        
        return $result;
    }
}