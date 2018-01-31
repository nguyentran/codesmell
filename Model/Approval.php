<?php
class Approval extends AppModel {

	public $validate = array(

	);

	/**
	 * 保存前処理
	 * {@inheritDoc}
	 * @see AppModel::beforeSave()
	 */
	public function beforeSave($options=array()){
		$user = parent::__getLoginUser();
		if(!is_array($user)){
			return false;
		}

		if (!$this->id && empty($this->data[$this->alias][$this->primaryKey])) {
			//insert
			$this->data[$this->alias]['application_user_id'] = $user['user_id'];
			$this->data[$this->alias]['application_datetime'] = date('Y-m-d H:i:s');

		}else{
			//update

			//承認ステータスチェック -1:申請中 2:却下 1：承認 -2:申請内容変更
			if($this->data[$this->alias]['status'] == 1 || $this->data[$this->alias]['status'] == 2){

				$this->data[$this->alias]['approval_user_id'] = $user['user_id'];
				$this->data[$this->alias]['approval_datetime'] = date('Y-m-d H:i:s');
			}

		}
		return parent::beforeSave($options);
	}

	public $csvFormat = array(
			'default' => array(
					'Approval.id' => '申請番号',
					'custom.application_section' => '申請区分',
					'CommissionApplication.commission_id' => '取次ID',
					'CommissionApplication.demand_id' => '案件ID',
					'CommissionApplication.corp_id' => '企業ID',
					'MCorp.official_corp_name' => '対象加盟店',
					'Approval.application_user_id' => '申請者',
					'Approval.application_datetime' => '申請日時',
					'Approval.application_reason' => '申請理由',
					'CommissionApplication.deduction_tax_include' => '控除金額(税込)',
					'CommissionApplication.irregular_fee_rate' => 'イレギュラー手数料率',
					'CommissionApplication.irregular_fee' => 'イレギュラー手数料金額',
					'custom.irregular_reason' => 'イレギュラー理由',
					'custom.introduction_free' => '紹介無料',
					//ORANGE-198 ADD S
					'custom.ac_commission_exclusion_flg' => '入札手数料【除外】',
					//ORANGE-198 ADD E
					'custom.status' => '可否',
					'Approval.approval_user_id' => '承認者',
					'Approval.approval_datetime' => '承認日時',
			),
	);
}