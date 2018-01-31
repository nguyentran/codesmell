<?php
class Report extends AppModel {
    public $name = 'Report';
    public $useTable = false;
    //関係モデル
    private $_m_commission_search;
    private $search_id = '';
    
    public function __construct() {
	App::import('Model', 'CommissionSearchItems');
        $this->_m_commission_search = new CommissionSearchItems();
    }
    /*
     * MCorpCommissionSearch検索用(単体)
     */
    public function findCorpCommissionSearch($type, $params = array()) {
        return $this->_m_commission_search->find($type, $params);
    }
    
    /*
     * CommissionSearchItems保存用
     */
    public function saveCommissionSearchItems($queryfilter) {
        $data = $this->__validateDataCommissionSearchItems($queryfilter);
        if (Hash::get($queryfilter,'CommissionSearch.commission_search_id') !== '') {
            $this->_m_commission_search->deleteAll(array("commission_search_id" => $this->search_id), false);
        }
        $this->_m_commission_search->saveMany($data['MCommissionSearchItems'], array());
        return $this->search_id;
    }
    /*
     * validate
     */
    private function __validateDataCommissionSearchItems($queryfilter) {
        $data = array();
        $data['MCommissionSearchItems'] = $this->__validateDataCommissionSearchItem($queryfilter);
        return $data;
    }

    private function __validateDataCommissionSearchItem($queryfilters) {
        $datas = array();

        if(!isset($queryfilters['filter']) ){
            throw new Exception('フィルター項目は1つ以上必ず選択して下さい');
        }
        
        $maxid = $this->_m_commission_search->find('first',array('fields' =>array('MAX(commission_search_id)')));
        
        //取次検索IDがあれば取得
        
        if(Hash::get($queryfilters,'CommissionSearch.commission_search_id')==''){
            $commission_search_id =  Hash::get($maxid,'0.max',0) + 1;
        }else{
            $commission_search_id = Hash::get($queryfilters,'CommissionSearch.commission_search_id');
        }
        $this->search_id = $commission_search_id;
        
        foreach($queryfilters['filter'] as $i => $queryfilter){
            if(!empty($queryfilter)){
                if(is_array($queryfilter)){
                    foreach($queryfilter as $condition_value){
                        $data = $this->_m_commission_search->create();
                        $data['commission_search_id'] = $commission_search_id;
                        $data['commission_search_name'] = $queryfilters['CommissionSearchItems']['commission_search_name'];
                        $data['column_name'] = 'filter'.$i;
                        $data['condition_value'] = $condition_value;
                        $datas[] = $data;
                    }
                }else{
                    $data = $this->_m_commission_search->create();
                    $data['commission_search_id'] = $commission_search_id;
                    $data['commission_search_name'] = $queryfilters['CommissionSearchItems']['commission_search_name'];
                    $data['column_name'] = 'filter'.$i;
                    $data['condition_value'] = $queryfilter;
                    $datas[] = $data;
                }
            }
        }
        
        return $datas;
    }

    /*
     * CommissionSearch削除用
    */
    public function deleteCommissionSearch($commission_search_id) {
            return $this->_m_commission_search->deleteAll(array(
                'commission_search_id'=>$commission_search_id,
            ));
    }
}
