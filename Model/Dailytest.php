<?php
class DailyTest extends AppModel {

	public $validate = array(

		'demand_id' => array(
			'NotEmpty' => array(
				'rule' => 'notEmpty',
				'last' => true
			),
		),

		'bill_status' => array(
			'BillStatusError' => array(
				'rule' => 'checkBillStatus',
			),
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'last' => true,
				'allowEmpty' => true
			),
		),

		'irregular_fee_rate' => array(
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'last' => true,
				'allowEmpty' => true
			),
		),

		'irregular_fee' => array(
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'last' => true,
				'allowEmpty' => true
			),
		),

		'deduction_tax_include' => array(
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'last' => true,
				'allowEmpty' => true
			),
		),

		'deduction_tax_exclude' => array(
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'last' => true,
				'allowEmpty' => true
			),
		),

		'indivisual_billing' => array(
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'last' => true,
				'allowEmpty' => true
			),
		),

		 'comfirmed_fee_rate' => array(
			'NoNumeric' => array(
				'rule' => array('numeric'),
					'last' => true,
					'allowEmpty' => true
				),
		 ),

		'fee_target_price' => array(
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'last' => true,
				'allowEmpty' => true
			),
		),

		'fee_tax_exclude' => array(
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'last' => true,
				'allowEmpty' => true
			),
		),

		'tax' => array(
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'last' => true,
				'allowEmpty' => true
			),
		),

		'insurance_price' => array(
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'last' => true,
				'allowEmpty' => true
			),
		),

		'total_bill_price' => array(
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'last' => true,
				'allowEmpty' => true
			),
		),

		'fee_billing_date' => array(
			'NotDate' => array(
				'rule' => 'date',
				'last' => true,
				'allowEmpty' => true
			),
		),

		'fee_payment_date' => array(
			'NotDate' => array(
				'rule' => 'date',
				'last' => true,
				'allowEmpty' => true
			),
		),

		'fee_payment_price' => array(
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'last' => true,
				'allowEmpty' => true
			),
		),

		'fee_payment_balance' => array(
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'last' => true,
				'allowEmpty' => true
			),
		),

		'report_note' => array(
			'OverMaxLength1000' => array(
				'rule' => array('maxLength', 1000),
				'last' => true,
				'allowEmpty' => true
			),
		),
	);

	public function checkBillStatus(){
		if($this->data['BillInfo']['bill_status'] != Util::getDivValue('bill_status','payment') && $this->data['BillInfo']['bill_status'] != Util::getDivValue('bill_status','not_issue')){
			if(isset($this->data['BillInfo']['fee_payment_balance'])){
				if($this->data['BillInfo']['fee_payment_balance'] == 0){
					return false;
				}
			}
		}
		return true;
	}

	public $csvFormat = array(
			'default' => array(
					'MCorp.id' => '企業コード',
					'MCorp.official_corp_name' => '企業名',
					'BillInfo.demand_id' => '案件ID',
					'BillInfo.commission_id' => '取次ID',
					'MItem.item_name' => '取次形態',
					'CommissionInfo.tel_commission_datetime' => '電話取次日時',
					'CommissionInfo.complete_date' => '施工完了日',
					'DemandInfo.customer_name' => 'お客様名',
					// 2015.04.30 h.hara(S)
					'DemandInfo.riro_kureka' => 'リロ・クレカ案件',
					// 2015.04.30 h.hara(E)
					'BillInfo.fee_target_price' => '手数料対象金額',
					'BillInfo.comfirmed_fee_rate' => '確定手数料率',
					'BillInfo.fee_tax_exclude' => '手数料(税抜)',
					'BillInfo.tax' => '消費税',
					'BillInfo.insurance_price' => '保険料金額',
					'BillInfo.fee_billing_date' => '手数料請求日',
					'BillInfo.fee_payment_balance' => '手数料入金残高',
/*
					'BillInfo.bill_status' => '請求状況',
					'BillInfo.irregular_fee_rate' => 'イレギュラー手数料率',
					'BillInfo.irregular_fee' => 'イレギュラー手数料金額',
					'BillInfo.deduction_tax_include' => '控除金額(税込)',
					'BillInfo.deduction_tax_exclude' => '控除金額(税抜)',
					'BillInfo.indivisual_billing' => '個別請求処理案件',
					'BillInfo.total_bill_price' => '合計請求金額',
					'BillInfo.fee_payment_date' => '手数料入金日',
					'BillInfo.fee_payment_price' => '手数料入金金額',
					'BillInfo.report_note' => '報告備考欄',
*/
			),
	);
}