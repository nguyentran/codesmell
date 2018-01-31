<?php
class ProgAddDemandInfo extends AppModel {

	/**
	 * 追加案件 バリデート
	 * @param unknown $data
	 * @return boolean
	 */
	public function addValidate($data = null){

		if(empty($data['display']))return false;

		$rtn_flg = false;
		if(!empty($data['demand_id_update']))return true;
		if(!empty($data['customer_name_update']))return true;
		if(!empty($data['category_name_update']))return true;
		if(!empty($data['commission_datus_update']))return true;
		if(!empty($data['complete_date_update']))return true;
		if(!empty($data['construction_price_tax_exclude_update']))return true;
		if(!empty($data['comment_update']))return true;
		// murata.s ORANGE-422 ADD(S)
		if(!empty($data['demand_type_update'])) return true;
		// murata.s ORANGE-422 ADD(E)
	}

	/**
	 * 追加対象の取得
	 * @param unknown $list
	 */
	public function addValidateAll($list=null){
		$rtn_list = array();

		if(!empty($list)){
			foreach($list as $data){
				if($this->addValidate($data))$rtn_list[] = $data;
			}
		}

		return $rtn_list;
	}

	/**
	 * 削除
	 * @param unknown $list
	 */
	public function DeleteById($list=null, $corp_id=null, $file_id=null, $modified=null){
		$valid_list = array();

		if(!empty($list)){
			foreach($list as $data){
				//ディスプレイ表示がなくIDが存在するデータが対象
				if(!empty($data['display']) && !empty($data['id'])){
					if($this->exists($data['id']))$valid_list[] = $data['id'];
				}
			}
		}

		$conditions = array(
			'ProgAddDemandInfo.prog_corp_id' => $corp_id,
			'ProgAddDemandInfo.prog_import_file_id' => $file_id,
			'ProgAddDemandInfo.modified <' => $modified,
			'NOT' => array('ProgAddDemandInfo.id' => $valid_list));
		//$this->log($conditions, LOG_ERR);
		if(!$this->deleteAll($conditions)){
			return false;
		}
		return true;
	}
}
?>