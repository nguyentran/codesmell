<?php
class CorpCategoryGroupApplication extends AppModel{
	public $actsAs = array('Search.Searchable');

	// 検索対象のフィルタ設定
	public $filterArgs = array(

			'corp_id' => array(
					'type'  => 'value',
					'field' => array('CorpCategoryGroupApplication.corp_id'),
			),
			'corp_name' => array(
					'type'  => 'like',
					'field' => array('MCorp.official_corp_name'),
			),
			'group_id' => array(
					'type'  => 'value',
					'field' => array('CorpCategoryGroupApplication.id'),
			),
			'application_date_from' => array(
					'type'  => 'value',
					'field' => array("to_char(CorpCategoryGroupApplication.created, 'YYYY/MM/DD') >="),
			),
			'application_date_to' => array(
					'type'  => 'value',
					'field' => array("to_char(CorpCategoryGroupApplication.created, 'YYYY/MM/DD') <="),
			),
	);

	public $csvFormat = array(
			'default' => array(
					'CorpCategoryGroupApplication.id' => '申請グループID',
					'custom.application_section' => '申請区分',
					'CorpCategoryGroupApplication.corp_id' => '企業ID',
					'MCorp.official_corp_name' => '対象加盟店',
					'Approval.application_user_id' => '申請者',
					'Approval.application_datetime' => '申請日時',
					'Approval.id' => '申請番号',
					'CorpCategoryApplication.genre_id' => 'ジャンルID',
					'MGenre.genre_name' => 'ジャンル名',
					'CorpCategoryApplication.category_id' => 'カテゴリID',
					'MCategory.category_name' => 'カテゴリ名',
					'CorpCategoryApplication.order_fee' => '受注手数料',
					'custom.order_fee_unit' => '受注手数料単位',
					'custom.introduce_fee' => '紹介手数料',
					'custom.corp_commission_type' => '取次形態',
					'CorpCategoryApplication.note'=>'備考',
					'Approval.application_reason' => '申請理由',
					'custom.status' => '可否',
					'Approval.approval_user_id' => '承認者',
					'Approval.approval_datetime' => '承認日時'
			)
	);
}