<?php
class ProgCorp extends AppModel {

	public $actsAs = array('Search.Searchable');

	// 検索対象のフィルタ設定
	public $filterArgs = array(

			'cid' => array(
					'type'  => 'value',
					'field' => array('ProgCorp.corp_id'),
			),
			'pf' => array(
					'type'  => 'value',
					'field' => array('ProgCorp.progress_flag'),
			),
			'cn' => array(
					'type'  => 'like',
					'field' => array('MCorp.corp_name', 'MCorp.official_corp_name'),
			),
			'ctl' => array(
					'type'  => 'value',
					'field' => array('ProgCorp.contact_type'),
			),
			'at' => array(
					'type' => 'query',
					'method' => 'check_call_back_phone_date',
			),
			'cbpf' => array(
					'type'  => 'value',
					'field' => array('ProgCorp.call_back_phone_flag'),
			),
			'atds' => array(
					'type'  => 'value',
					'field' => array('ProgCorp.call_back_phone_date >='),
			),
			'atde' => array(
					'type'  => 'value',
					'field' => array('ProgCorp.call_back_phone_date <='),
			),
			//'up' => array(
			//		'type'  => 'value',
			//		'field' => array('ProgCorp.unit_cost'),
			//),
			'cds' => array(
					'type'  => 'value',
					'field' => array('ProgCorp.collect_date >='),
			),
			'cde' => array(
					'type'  => 'value',
					'field' => array('ProgCorp.collect_date <='),
			),
			'crk' => array(
					'type' => 'query',
					'method' => 'check_note',
			),
			'cr' => array(
					'type'  => 'like',
					'field' => array('ProgCorp.note'),
			),

	);

	public function check_call_back_phone_date($data = array()){
		$at = $data['at'];
		$conditions = array();
		if($at == 1){
			$conditions = array(
						array('ProgCorp.call_back_phone_date is null', ),
			);
		}elseif($at == 2){
			$conditions = array(
						array('ProgCorp.call_back_phone_date is not null', ),
			);
		}
		return $conditions;
	}

	public function check_note($data = array()){
		$crk = $data['crk'];
		$conditions = array();
		if($crk == 1){
			$conditions = array(
					'OR' => array(
						array('ProgCorp.note is null', ),
						array("ProgCorp.note = ''" ),
					)
			);
		}elseif($crk == 2){
			$conditions = array(
					'AND' => array(
						array('ProgCorp.note is not null', ),
							array("ProgCorp.note != ''" ),
					)
			);
		}
		return $conditions;
	}
	public $csvFormat = array(
			'default' => array(
					// 2015.5.22 n.kai ADD start ＊案件ID 出力位置移動
					'MCorp.corp_name' => '企業名',
					'MCorp.official_corp_name' => '正式名',
					'ProgDemandInfo.corp_id' => '施工会社番号',
					'MCorp.commission_dial' => '取次要ダイヤル',
					'ProgCorp.mail_address' => '進捗表送付先(メール)',
					'ProgCorp.fax' => '進捗表送付(FAX)',
					'ProgCorp.contact_type' => '取次状況',
					'ProgCorp.unit_cost' => '単価',
					'ProgCorp.progress_flag' => '進捗表状況',
					'ProgDemandInfo.demand_id' =>'案件コード',
					'ProgDemandInfo.commission_id' =>'取次ID',
					'ProgDemandInfo.receive_datetime' =>'受信日時',
					'ProgDemandInfo.customer_name' =>'(ひらがなフルネーム)',
					'ProgDemandInfo.category_name' =>'カテゴリ',
					'ProgDemandInfo.fee' =>'手数料率(手数料金額)',
					'ProgDemandInfo.complete_date' => '施工完了日[失注日](インポート時)',
					'ProgDemandInfo.construction_price_tax_exclude' => '施工金額（税抜）(インポート時)',
					'ProgDemandInfo.commission_status' =>'進捗状況(インポート時)',
					'ProgDemandInfo.diff_flg' =>'情報相違',
					'ProgDemandInfo.complete_date_update' =>'施工完了日[失注日](業者返送時)',
					'ProgDemandInfo.construction_price_tax_exclude_update' =>'施工金額（税抜）(業者返送時)',
					'ProgDemandInfo.commission_status_update' =>'進捗状況(業者返送時)',
					//'ProgDemandInfo.complete_date' =>'施工完了日(業者返送時)',
					//'ProgDemandInfo.complete_date' =>'失注日',
					'ProgDemandInfo.fee_target_price' =>'手数料対象金額',
					'ProgDemandInfo.commission_order_fail_reason_update' =>'失注理由',
					'ProgDemandInfo.comment_update' =>'備考欄',
					'ProgCorp.koujo' => '控除金額',
					'ProgCorp.collect_date' => '回収日',
					'ProgCorp.sf_register_date' => '未送信（焦げ付き）',
					'ProgCorp.contact_type' => '送付方法',
					'ProgCorp.last_send_date' => '最新送信日',
					'ProgCorp.mail_count' => 'メール送付回数',
					'ProgCorp.fax_count' => 'ＦＡＸ送付回数',
					'ProgCorp.note' =>'後追い履歴',//カスタム項目
					'ProgCorp.not_replay_flag' => '未返信理由',
					'ProgDemandInfo.fee_billing_date' => '手数料請求日',//請求データ参照
					'ProgDemandInfo.genre_name' => 'ジャンル',
					'MCorp.tel1' => '連絡先①',
					'ProgDemandInfo.agree_flag' =>'同意チェック',
					'ProgDemandInfo.ip_address_update' =>'IPアドレス',
					'ProgDemandInfo.user_agent_update' =>'ユーザーエージェント',
					'ProgDemandInfo.host_name_update' =>'ホスト名',
			),
	);
/*
	public $csvFormat = array(
			'default' => array(
					// 2015.5.22 n.kai ADD start ＊案件ID 出力位置移動
					//'ProgCorp.id' => 'id',
					//'ProgCorp.corp_id' => '企業ID'
					//'ProgCorp.contact_type' => '取次状況',
					//'ProgCorp.unit_cost' => '単価',
					//'ProgCorp.progress_flag' => '進捗表状況',
					//'ProgCorp.collect_date' => '回収日',
					//'ProgCorp.sf_register_date' => '未送信（焦げ付き）',
					//'ProgCorp.contact_type' => '送付方法',
					//'ProgCorp.mail_count' => 'メール送付回数',
					//'ProgCorp.fax_count' => 'ＦＡＸ送付回数',
					//'ProgCorp.not_reply_flag' => '未返信理由',
			),
	);
*/
}
?>