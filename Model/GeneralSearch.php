<?php
class GeneralSearch extends AppModel
{
	public $name = 'GeneralSearch';
	public $useTable = false;

	//機能名
	const FUNCTION_CASE_MANAGEMENT  = "0";
	const FUNCTION_AGENCY_MANAGEMENT = "1";
	const FUNCTION_CHARGE_MANAGEMENT = "2";
	const FUNCTION_MEMBER_MANAGEMENT = "3";

	//関係モデル
	private $_m_general_search;
	private $_general_search_item;
	private $_general_search_condition;
	private $_general_search_history;
	private $_m_site;
	private $_m_commission_type;
	private $_m_genre;
	private $_m_category;
	private $_m_item;
	private $_m_user;
	private $_m_address1;
	//変換用のリスト
	private $_m_site_list;
	private $_m_commission_type_list;
	private $_m_genre_list;
	private $_m_category_list;
	private $_m_item_wcontents_list;
	// 2015.07.24 s.harada ADD start ORANGE-659
	private $_m_item_pet_tombstone_demand_list;
	private $_m_item_sms_demand_list;
	// 2015.07.24 s.harada ADD end ORANGE-659
	private $_m_item_jbr_category_list;
	private $_m_item_jbr_estimate_list;
	private $_m_item_jbr_receipt_list;
	private $_m_user_list;
	private $_m_user_list2;
	private $_m_address1_list;
	private $_m_item_bill_status_list;
	private $_m_item_bill_send_method_list;
	private $_m_item_coordination_method_list;
	private $_m_item_prog_send_method_list;
	private $_m_item_advertising_status_list;
	private $_m_item_payment_site_list;
	private $_m_item_demand_status_list;
	private $_m_item_demand_order_fail_reason_list;
	private $_m_item_commission_status_list;
	private $_m_item_commission_order_fail_reason_list;
	private $_m_corp_status_list;
	private $_m_corp_affiliation_status_list;
	private $_m_corp_corp_commission_status_list;
	private $_m_target_areas_list;
	private $_m_corp_categories_order_fee_unit_list;
// murata.s ORANGE-261 ADD(S)
	private $_m_corp_categories_corp_commission_type_list;
// murata.s ORANGE-261 ADD(E)
	// 2015.07.24 h.hanaki ADD start ORANGE-659
	private $_m_item_reg_send_method_list;
	private $_m_item_corp_status_list;
	private $_m_item_corp_order_fail_reason_list;
	// 2015.07.24 h.hanaki ADD end   ORANGE-659
	// 2015.08.31 h.hanaki ADD start ORANGE_AUCTION-11
	private $_m_item_special_measures_list;
	// 2015.10.08 n.kai ADD start ORANGE-910
	private $_demand_infos_priority_list;
	// 2015.12.09 h.hanaki ORANGE-1087
	private $_m_corps_auction_status_list;
	private $_m_corps_corp_commission_type;
	// 2015.12.13 h.hanaki ORANGE-1086
	private $_commission_infos_reform_upsell_ic_list;
	// 2016.01.13 h.hanaki ORANGE-1183
	private $_commission_infos_re_commission_exclusion_status;
	// ORANGE-1334  iwai 2016/3/30 ADD(S)
	private $_commission_tel_supports_correspond_status;
	private $_commission_visit_supports_correspond_status;
	private $_commission_order_supports_correspond_status;
	private $_commission_tel_supports_order_fail_reason;
	private $_commission_visit_supports_order_fail_reason;
	private $_commission_order_supports_order_fail_reason;

	// ORANGE-1334 iwai 2016/3/30 ADD(E)
	// ORANGE-13 iwai S
	private $_commission_infos_irregular_reason_list;
	// ORANGE-13 iwai E

	//ORANGE-126 2016.8.16 iwai S
	private $_m_corps_jbr_available_status;
	//ORANGE-126 2016.8.16 iwai E
	// murata.s ORANGE-478 ADD(S)
	private $_demand_infos_selection_system;
	// murata.s ORANGE-478 ADD(E)

	// murata.s ORANGE-537 ADD(S)
	private $_m_corps_auto_call_flag;
	// murata.s ORANGE-537 ADD(E)

	/*
	 * 機能IDと機能名のリスト
	 */
	private $_function_list = array( array('value' => '0', 'name' => '案件管理'), array('value' => '1', 'name' => '取次管理'), array('value' => '2', 'name' => '請求管理'), array('value' => '3', 'name' => '加盟店管理'));

	// 2016.3.29 sasaki@tobila.com MOD start ORANGE-1332
	private $_function_security_list = array('', '', '', '');
	// 2016.3.29 sasaki@tobila.com MOD end ORANGE-1332

	/*
	 * ファイル名
	 */
	private $_csv_file_id = "総合検索";

	/*
	 * 機能毎のテーブル名
	 */
	private $_function_tables = array(
		array('demand_infos' => array(
									'id', 'follow_date', 'demand_status', 'order_fail_reason',
									// 2015.07.15 h.hara ORANGE-711(S)
									//''mail_demand', 'nighttime_takeover', 'low_accuracy', 'remand',
									'reservation_demand', 'mail_demand', 'nighttime_takeover', 'low_accuracy', 'remand', 'auction', 'priority',
									// 2015.07.15 h.hara ORANGE-711(E)
									'immediately', 'corp_change', 'receive_datetime', 'site_id', 'genre_id',
									// 2015.07.23 n.kai ORANGE-733(S)
									//'category_id', 'cross_sell_source_site', 'cross_sell_source_genre', 'cross_sell_source_category',
									'category_id', 'cross_sell_source_site', 'cross_sell_source_genre', 'cross_sell_source_category', 'source_demand_id',
									// 2015.07.23 n.kai ORANGE-733(S)
				                    'receptionist', 'customer_name', 'customer_tel', 'customer_mailaddress',
									'postcode', 'address1', 'address2', 'address3', 'address4', 'building',
									// 2015.07.24 s.harada ADD start ORANGE-659
									//'room', 'tel1', 'tel2', 'contents', 'contact_desired_time', 'jbr_order_no',
									'room', 'tel1', 'tel2', 'contents', 'contact_desired_time', 'selection_system', 'pet_tombstone_demand', 'sms_demand', 'jbr_order_no',
									// 2015.07.24 s.harada ADD start ORANGE-659
									// ORANGE-114 2016.08.01 iwai CHG S
									'jbr_work_contents', 'jbr_category', 'jbr_receipt_price',
									'mail', 'order_date', 'complete_date',
									// ORANGE-114 2016.08.01 iwai CHG E
									'order_fail_date', 'jbr_estimate_status', 'jbr_receipt_status', 'development_request',
									'share_notice', 'del_flg', 'riro_kureka',
									'modified_user_id', 'modified', 'created_user_id', 'created', 'order_no_marriage',
									// 2015.07.28 h.hanaki ADD start ORANGE-728
									'order_loss', 'same_customer_demand_url', 'upload_estimate_file_name', 'upload_receipt_file_name',
									// 2015.08.26 h.hanaki ADD start ORANGE-819
									'cost_customer_question', 'cost_from', 'cost_to',
									// 2015.08.31 h.hanaki ADD start ORANGE_AUCTION-11
									'special_measures',
									// 2015.09.07 h.hanaki ADD start ORANGE-841
									'cost_customer_answer','call_back_time',
									// 2015.10.02 h.hanaki ADD start ORANGE-897
									'acceptance_status',
									// 2015.12.01 h.hanaki ADD ORANGE-988
									'cross_sell_implement',
									//ORANGE-369 ADD S
									'cross_sell_call',
									//ORANGE-369 ADD E
									'commission_limitover_time',
									// 2017.08.24 e.takeuchi@SharingTechnology ORANGE-516 ADD(S)
									'sms_reorder',
									// 2017.08.24 e.takeuchi@SharingTechnology ORANGE-516 ADD(E)
									// 2016.11.02 murata.s ORANGE-189 ADD(S)
									'business_trip_amount', 'auction_deadline_time', 'auction_start_time', 'construction_class', 'contact_desired_time_from', 'contact_desired_time_to', 'customer_corp_name', 'nitoryu_flg'
									// 2016.11.02 murata.s ORANGE-189 ADD(E)
								),
/*
			  'demand_corresponds' => array(
							  		'id', 'demand_id', 'correspond_datetime', 'responders', 'corresponding_contens'
								)
*/
		// 2015.08.26 h.hanaki ADD start ORANGE-819
			  'visit_times' => array(
//									'id', 'demand_id', 'visit_time', 'modified_user_id', 'modified', 'created_user_id', 'created'
									'visit_time'
								)
		// 2015.08.26 h.hanaki ADD end   ORANGE-819
		),
		array('commission_infos' => array(
									'id', 'demand_id', 'corp_id', 'commit_flg', 'commission_type', 'lost_flg', 'appointers',
									'first_commission', 'corp_fee', 'waste_collect_oath', 'attention', 'commission_dial',
									'tel_commission_datetime', 'tel_commission_person', 'commission_fee_rate', 'commission_note_send_datetime',
									'commission_note_sender', 'commission_status', 'commission_order_fail_reason', 'complete_date', 'order_fail_date',
									'estimate_price_tax_exclude', 'construction_price_tax_exclude', 'construction_price_tax_include',
									'deduction_tax_include', 'deduction_tax_exclude', 'confirmd_fee_rate', 'unit_price_calc_exclude',
									// 2015.07.24 h.hanaki ORANGE-728(S)
									'introduction_free',
									// 2015.07.24 h.hanaki ORANGE-728(E)
									// ORANGE-13 iwai S
									'report_note', 'del_flg', 'checked_flg', 'irregular_fee_rate', 'irregular_fee', 'irregular_reason', 'falsity', 'follow_date',
									// ORANGE-13 iwai E
									'introduction_not', 'lock_status', 'commission_status_last_updated', 'progress_reported', 'progress_report_datetime',
									'modified_user_id', 'modified', 'created_user_id', 'created',
									// 2015.07.28 h.hanaki ORANGE-680(S), 2015.09.15 n.kai ADD start ORANGE-807
									'send_mail_fax', 'send_mail_fax_datetime', 'select_commission_unit_price_rank',
									// 2015.07.28 h.hanaki ORANGE-680(E), 2015.09.15 n.kai ADD end ORANGE-807
									// 2015.12.13 j.hanaki ORANGE-1086
									'reform_upsell_ic', 'remand_flg',
									// 2016.01.13 h.hanaki ORANGE-1183
									're_commission_exclusion_status','re_commission_exclusion_user_id','re_commission_exclusion_datetime','send_mail_fax_sender',
									// 2016.11.02 murata.s ORANGE-189 ADD(S)
									'business_trip_amount', 'tel_support', 'visit_support', 'order_support', 'remand_reason', 'remand_correspond_person',
									'visit_desired_time', 'order_respond_datetime', 'select_commission_unit_price', 'send_mail_fax_othersend', 'ac_commission_exclusion_flg',
									// 2016.11.02 murata.s ORANGE-189 ADD(E)
									// 2017.01.04 murata.s ORANGE-244 ADD(S)
									'order_fee_unit'
									// 2017.01.04 murata.s ORANGE-244 ADD(E)
									// 2017.11.08 e.takeuchi@SharingTechnology ADD(S)
									, 'fee_billing_date'
									// 2017.11.08 e.takeuchi@SharingTechnology ADD(E)

								),
				// 2016.11.02 murata.s ORANGE-189 ADD(S)
				'bill_infos' => array(
						'auction_id'
				),
				// 2016.11.02 murata.s ORANGE-189 ADD(E)
				// 2016.03.23 murata.s ADD start ORANGE-1334
				'demand_infos' => array(
						'contact_desired_time'
				),
				'commission_tel_supports' => array(
						'correspond_status', 'order_fail_reason'
				),
				'visit_times' => array(
						'visit_time'
				),
				'commission_visit_supports' => array(
						'correspond_status', 'order_fail_reason'
				),
				'commission_order_supports' => array(
						'correspond_datetime', 'correspond_status', 'order_fail_reason'
				),
//				// 2016.03.23 murata.s ADD end   ORANGE-1334
		),
		array('bill_infos' => array(
									'id', 'demand_id', 'bill_status', 'irregular_fee_rate', 'irregular_fee', 'deduction_tax_include',
									'deduction_tax_exclude', 'indivisual_billing', 'comfirmed_fee_rate', 'fee_target_price', 'fee_tax_exclude',
									'tax', 'insurance_price', 'total_bill_price', 'fee_billing_date', 'fee_payment_date', 'fee_payment_price',
									'fee_payment_balance', 'report_note', 'commission_id',
									'modified_user_id', 'modified', 'created_user_id', 'created',
									// 2016.11.02 murata.s ORANGE-189 ADD(S)
									'business_trip_amount'
									// 2016.11.02 murata.s ORANGE-189 ADD(E)
								),
		),
		array('m_corps' => array(
									'id', 'corp_name', 'corp_name_kana', 'official_corp_name', 'affiliation_status', 'responsibility', 'postcode',
									'address1', 'address2', 'address3', 'address4', 'building', 'room', 'trade_name1', 'trade_name2', 'commission_dial',
									'tel1', 'tel2', 'mobile_tel', 'fax', 'mailaddress_pc', 'mailaddress_mobile', 'url', 'target_range', 'available_time',
									'support24hour', 'contactable_time', 'free_estimate', 'portalsite', 'reg_send_date', 'reg_collect_date', 'ps_app_send_date',
									'ps_app_collect_date', 'coordination_method', 'prog_send_method',
									//ORANGE-218 CHG S
									'prog_send_mail_address', 'prog_send_fax',
									//ORANGE-218 CHG E
									'prog_irregular', 'bill_send_method',
									'bill_send_address', 'bill_irregular', 'special_agreement', 'contract_date', 'order_fail_date', 'commission_ng_date', 'note',
									'document_send_request_date', 'follow_person', 'advertising_status', 'advertising_send_date',
									'progress_check_tel', 'progress_check_person', 'payment_site', 'del_flg', 'rits_person',
									// 2015.07.28 h.hanaki ADD start ORANGE-728
									'reg_send_method' ,'geocode_lat' ,'geocode_long' ,'follow_date' ,'corp_status' ,'order_fail_reason' ,'corp_commission_status',
									'listed_media',
									// 2015.12.09 h.hanaki ADD start ORANGE-1087
									// ORANGE-126 2016.8.16 iwai S
									'jbr_available_status',
									// ORANGE-126 2016.8.16 iwai E
									'mailaddress_auction' ,'auction_status','corp_commission_type',
									// 2016.11.02 murata.s ORANGE-189 ADD(S)
									'corp_person', 'available_time_from', 'available_time_to', 'contactable_time_from', 'contactable_time_to',
 									'contactable_support24hour', 'contactable_time_other', 'available_time_other', 'seikatsu110_id', 'mobile_mail_none', 'mobile_tel_type',  'auction_masking',
									'commission_accept_flg', 'commission_accept_date', 'commission_accept_user_id', 'representative_postcode', 'representative_address1',
									'representative_address2', 'representative_address3', 'refund_bank_name', 'refund_branch_name', 'refund_account_type', 'refund_account',
									'support_language_en', 'support_language_zh', 'support_language_employees',
									// 2016.11.02 murata.s ORANGE-189 ADD(E)
									// murata.s ORANGE-537 ADD(S)
									'auto_call_flag',
									// murata.s ORANGE-537 ADD(E)
								),
			  'affiliation_infos' => array(
			  						'id', 'corp_id', 'employees', 'max_commission', 'collection_method', 'collection_method_others', 'liability_insurance',
			  						'reg_follow_date1', 'reg_follow_date2', 'reg_follow_date3', 'waste_collect_oath', 'transfer_name', 'claim_count',
			  						'claim_history', 'commission_count', 'weekly_commission_count', 'orders_count', 'orders_rate',
			  						'construction_cost', 'fee', 'bill_price', 'payment_price', 'balance', 'construction_unit_price', 'commission_unit_price',
			  						'sf_construction_unit_price', 'sf_construction_count', 'reg_info', 'reg_pdf_path', 'attention',
			  						'corp_id',
							  		'modified_user_id', 'modified', 'created_user_id', 'created',
			  						// 2016.11.02 murata.s ORANGE-189 ADD(S)
			  						'credit_limit', 'listed_kind', 'default_tax', 'capital_stock', 'virtual_account', 'add_month_credit'
			  						// 2016.11.02 murata.s ORANGE-189 ADD(E)
								),
			  // murata.s ORANGE-261 ADD(S)
			  'm_corp_categories' => array(
									'genre_id', 'category_id', 'order_fee', 'order_fee_unit', 'introduce_fee', 'introduce_fee_unit', 'note',
							  		'modified_user_id', 'modified', 'created_user_id', 'created', 'corp_commission_type'
								),
			  // murata.s ORANGE-261 ADD(E)
			  'm_target_areas' => array(
									'jis_cd', 'address1_cd'
								)
		)
/*		// 2015.08.26 h.hanaki ADD start ORANGE-819
		array('visit_times' => array(
									'id', 'demand_id', 'visit_time', 'modified_user_id', 'modified', 'created_user_id', 'created'
								)
		)
		// 2015.08.26 h.hanaki ADD end   ORANGE-819 */
	);

	/*
	 * カラム毎の表示制限
	 */
	private $_security_table_column = array(
// 2016.3.29 sasaki@tobila.com MOD start ORANGE-1332
// 		'bill_infos_id' => array('system', 'accounting'),
// 		'bill_infos_id' => array('system', 'accounting'),
// 		'bill_infos_demand_id' => array('system', 'accounting'),
// 		'bill_infos_bill_status' => array('system', 'accounting'),
// 		'bill_infos_irregular_fee_rate' => array('system', 'accounting'),
// 		'bill_infos_irregular_fee' => array('system', 'accounting'),
// 		'bill_infos_deduction_tax_include' => array('system', 'accounting'),
// 		'bill_infos_deduction_tax_exclude' => array('system', 'accounting'),
// 		'bill_infos_indivisual_billing' => array('system', 'accounting'),
// 		'bill_infos_comfirmed_fee_rate' => array('system', 'accounting'),
// 		'bill_infos_fee_target_price' => array('system', 'accounting'),
// 		'bill_infos_fee_tax_exclude' => array('system', 'accounting'),
// 		'bill_infos_tax' => array('system', 'accounting'),
// 		'bill_infos_insurance_price' => array('system', 'accounting'),
// 		'bill_infos_total_bill_price' => array('system', 'accounting'),
// 		'bill_infos_fee_billing_date' => array('system', 'accounting'),
// 		'bill_infos_fee_payment_date' => array('system', 'accounting'),
// 		'bill_infos_fee_payment_price' => array('system', 'accounting'),
// 		'bill_infos_fee_payment_balance' => array('system', 'accounting'),
// 		'bill_infos_report_note' => array('system', 'accounting'),
// 		'bill_infos_commission_id' => array('system', 'accounting'),
// 		'bill_infos_modified_user_id' => array('system', 'accounting'),
// 		'bill_infos_modified' => array('system', 'accounting'),
// 		'bill_infos_created_user_id' => array('system', 'accounting'),
// 		'bill_infos_created' => array('system', 'accounting')
// 2016.3.29 sasaki@tobila.com MOD end ORANGE-1332
	);

	/*
	 * 機能毎の項目変換テーブル
	 */
	private $_transfer_reference_table = array(
		'demand_infos_demand_status' => array('v' => '_m_item_demand_status_list'),
		'demand_infos_order_fail_reason' => array('v' => '_m_item_demand_order_fail_reason_list'),
		'demand_infos_site_id' => array('v' => '_m_site_list'),
		'demand_infos_genre_id' => array('v' => '_m_genre_list'),
		'demand_infos_receptionist' => array('v' => '_m_user_list'),
		'demand_infos_category_id' => array('v' => '_m_category_list'),
		'demand_infos_cross_sell_source_site' => array('v' => '_m_site_list'),
		'demand_infos_cross_sell_source_genre' => array('v' => '_m_genre_list'),
		'demand_infos_cross_sell_source_category' => array('v' => '_m_category_list'),
		'demand_infos_jbr_work_contents' => array('v' => '_m_item_wcontents_list'),
	    'demand_infos_jbr_category' => array('v' => '_m_item_jbr_category_list'),
		'demand_infos_jbr_estimate_status' => array('v' => '_m_item_jbr_estimate_list'),
		'demand_infos_jbr_receipt_status' => array('v' => '_m_item_jbr_receipt_list'),
		'demand_corresponds_responders' => array('v' => '_m_user_list'),
		'demand_infos_modified_user_id' => array('v' => '_m_user_list2'),
		'demand_infos_created_user_id' => array('v' => '_m_user_list2'),
		'demand_infos_address1' => array('v' => '_m_address1_list'),
		// 2015.07.24 s.harada ADD start ORANGE-659
		'demand_infos_pet_tombstone_demand' => array('v' => '_m_item_pet_tombstone_demand_list'),
		'demand_infos_sms_demand' => array('v' => '_m_item_sms_demand_list'),
		// 2015.07.24 s.harada ADD end ORANGE-65
		// 2015.08.31 h.hanaki ADD start ORANGE_AUCTION-11
		'demand_infos_special_measures' => array('v' => '_m_item_special_measures_list'),
		// 2015.10.02 h.hanaki ADD       ORANGE-897
		'demand_infos_acceptance_status' => array('v' => '_m_item_acceptance_status_list'),
		// 2015.10.08 n.kai ADD start ORANGE-910
		'demand_infos_priority' => array('v' => '_demand_infos_priority_list'),


		'commission_infos_commission_type' => array('v' => '_m_commission_type_list'),
		'commission_infos_appointers' => array('v' => '_m_user_list'),
		'commission_infos_tel_commission_person' => array('v' => '_m_user_list'),
		'commission_infos_commission_note_sender' => array('v' => '_m_user_list'),
		'commission_infos_commission_status' => array('v' => '_m_item_commission_status_list'),
		'commission_infos_commission_order_fail_reason' => array('v' => '_m_item_commission_order_fail_reason_list'),
		'commission_infos_modified_user_id' => array('v' => '_m_user_list2'),
		'commission_infos_created_user_id' => array('v' => '_m_user_list2'),
		'bill_infos_bill_status' => array('v' => '_m_item_bill_status_list'),
		'bill_infos_modified_user_id' => array('v' => '_m_user_list2'),
		'bill_infos_created_user_id' => array('v' => '_m_user_list2'),
		'm_corps_address1' => array('v' => '_m_address1_list'),
		'm_corps_coordination_method' => array('v' => '_m_item_coordination_method_list'),
		'm_corps_prog_send_method' => array('v' => '_m_item_prog_send_method_list'),
		'm_corps_bill_send_method' => array('v' => '_m_item_bill_send_method_list'),
		'm_corps_follow_person' => array('v' => '_m_user_list'),
		'm_corps_advertising_status' => array('v' => '_m_item_advertising_status_list'),
		'm_corps_payment_site' => array('v' => '_m_item_payment_site_list'),
		'm_corps_rits_person' => array('v' => '_m_user_list'),
		'm_corps_corp_status' => array('v' => '_m_corp_status_list'),
		'm_corps_corp_commission_status' => array('v' => '_m_corp_corp_commission_status_list'),
		'm_corps_affiliation_status' => array('v' => '_m_corp_affiliation_status_list'),
		// 2015.07.28 h.hanaki ADD start ORANGE-728
		'm_corps_reg_send_method' => array('v' => '_m_item_reg_send_method_list'),
		'm_corps_corp_status' => array('v' => '_m_item_corp_status_list'),
		'm_corps_order_fail_reason' => array('v' => '_m_item_corp_order_fail_reason_list'),
		// 2015.07.28 h.hanaki ADD end   ORANGE-728
		'affiliation_infos_modified_user_id' => array('v' => '_m_user_list2'),
		'affiliation_infos_created_user_id' => array('v' => '_m_user_list2'),
		'affiliation_infos_liability_insurance' => array('v' => '_m_item_liability_insurance'),
		'affiliation_infos_waste_collect_oath' => array('v' => '_m_item_waste_collect_oath'),
		'm_corp_categories_genre_id' => array('v' => '_m_genre_list'),
		'm_corp_categories_category_id' => array('v' => '_m_category_list'),
		'm_corp_categories_modified_user_id' => array('v' => '_m_user_list2'),
		'm_corp_categories_created_user_id' => array('v' => '_m_user_list2'),
		'm_corp_categories_order_fee_unit' => array('v' => '_m_corp_categories_order_fee_unit_list'),
// murata.s ORANGE-261 ADD(S)
		'm_corp_categories_corp_commission_type' => array('v' => '_m_corp_categories_corp_commission_type_list'),
// murata.s ORANGE-261 ADD(E)
		'm_target_areas_jis_cd' => array('v' => '_m_target_areas_list'),
		// 1025.12.09 h.hanaki ORANGE-1087
		'm_corps_auction_status' => array('v' => '_m_corps_auction_status_list'),
		'm_corps_corp_commission_type' => array('v' => '_m_corps_corp_commission_type'),
		// 1025.12.13 h.hanaki ORANGE-1086
		'commission_infos_reform_upsell_ic' => array('v' => '_commission_infos_reform_upsell_ic_list'),
		// 2016.01.13 h.hanaki ORANGE-1183
		'commission_infos_re_commission_exclusion_status' => array('v' => '_commission_infos_re_commission_exclusion_status'),
		'commission_infos_re_commission_exclusion_user_id' => array('v' => '_m_user_list2'),
		'commission_infos_send_mail_fax_sender' => array('v' => '_m_user_list'),
// TODO: ここに変換テーブルを追加する
		// ORANGE-1334  iwai 2016/3/30 ADD(S)
		'commission_tel_supports_correspond_status' => array('v' => '_commission_tel_supports_correspond_status'),
		'commission_visit_supports_correspond_status' => array('v' => '_commission_visit_supports_correspond_status'),
		'commission_order_supports_correspond_status' => array('v' => '_commission_order_supports_correspond_status'),
		'commission_tel_supports_order_fail_reason' => array('v' => '_commission_tel_supports_order_fail_reason'),
		'commission_visit_supports_order_fail_reason' => array('v' => '_commission_visit_supports_order_fail_reason'),
		'commission_order_supports_order_fail_reason' => array('v' => '_commission_order_supports_order_fail_reason'),
		// ORANGE-1334 iwai 2016/3/30 ADD(E)
		// ORANGE-13 iwai S
		'commission_infos_irregular_reason' => array('v' => '_commission_infos_irregular_reason_list'),
		// ORANGE-13 iwai E
		// ORANGE-126 2016.8.16 iwai S
		'm_corps_jbr_available_status' => array('v' => '_m_corps_jbr_available_status'),
		// ORANGE-126 2016.8.16 iwai E
		// 2016.11.02 murata.s ORANGE-189 ADD(S)
		'demand_infos_construction_class' => array('v' => '_demand_infos_construction_class'),
		'm_corps_mobile_tel_type' => array('v' => '_m_corps_mobile_tel_type'),
		'm_corps_mobile_tel_type' => array('v' => '_m_corps_mobile_tel_type'),
		'm_corps_auction_masking' => array('v' => '_m_corps_auction_masking'),
		'm_corps_commission_accept_flg' => array('v' => '_m_corps_commission_accept_flg'),
		'm_corps_commission_accept_user_id' => array('v' => '_m_user_list2'),
		'm_corps_support_language_en' => array('v' => '_m_corps_support_language_en'),
		'm_corps_support_language_zh' => array('v' => '_m_corps_support_language_zh'),
		'm_corps_representative_address1' => array('v' => '_m_address1_list'),
		'affiliation_infos_listed_kind' => array('v' => '_affiliation_infos_listed_kind'),
		'affiliation_infos_default_tax' => array('v' => '_affiliation_infos_default_tax'),
		// 2016.11.02 murata.s ORANGE-189 ADD(E)
		// 2017.01.04 murata.s ORANGE-244 ADD(S)
		'commission_infos_order_fee_unit' => array('v' => '_m_corp_categories_order_fee_unit_list'),
		// 2017.01.04 murata.s ORANGE-244 ADD(E)
		// murata.s ORANGE-478 ADD(S)
		'demand_infos_selection_system' => array('v' => '_demand_infos_selection_system'),
		// murata.s ORANGE-478 ADD(E)
		// murata.s ORANGE-537 ADD(S)
		'm_corps_auto_call_flag' => array('v' => '_m_corps_auto_call_flag'),
		// murata.s ORANGE-537 ADD(E)
	);


	/*
	 * 機能毎の検索用JOIN TABLE
	 */
	/*
	private $_function_tables_join_order = array(
		'demand_infos', 'demand_corresponds', 'commission_infos', 'bill_infos', 'm_corps', 'affiliation_infos', 'm_corp_categories', 'm_target_areas'
	);
	*/
		// 2015.08.26 h.hanaki ADD end   ORANGE-819 visit_times追加
	// 2016.03.23 murata.s CHG start ORANGE-1334
	private $_function_tables_join_order = array(
			'demand_infos', 'visit_times', 'commission_infos', 'commission_tel_supports', 'commission_visit_supports', 'commission_order_supports', 'bill_infos', 'm_corps', 'affiliation_infos', 'm_corp_categories', 'm_target_areas',
	);
	// 2016.03.23 murata.s CHG end ORANGE-1334
	private $_function_tables_join_rule = array(
		'demand_infos' => array('cond' => null),
/*
		'demand_corresponds' => array(
			'cond' => 'demand_infos.id = demand_corresponds.demand_id'
		),
*/
		// 2015.08.26 h.hanaki ADD start ORANGE-819
		'visit_times' => array(
			'cond' => 'demand_infos.id = visit_times.demand_id'
		),
		// 2015.08.26 h.hanaki ADD end   ORANGE-819
		'commission_infos' => array(
			'cond' => 'demand_infos.id = commission_infos.demand_id'
		),
// 2016.11.02 murata.s ORANGE-189 CHG(S)
// 		'bill_infos' => array(
// 			'cond' => 'demand_infos.id = bill_infos.demand_id and commission_infos.id = bill_infos.commission_id'
// 		),
		'bill_infos' => array(
			'cond' => 'demand_infos.id = bill_infos.demand_id and commission_infos.id = bill_infos.commission_id and bill_infos.auction_id IS NULL'
		),
// 2016.11.02 murata.s ORANGE-189 CHG(E)
		'm_corps' => array(
			'cond' => 'commission_infos.corp_id = m_corps.id'
		),
		'affiliation_infos' => array(
			'cond' => 'm_corps.id = affiliation_infos.corp_id'
		),
		'm_corp_categories' => array(
			'cond' => 'm_corps.id = m_corp_categories.corp_id'
		),
		'm_target_areas' => array(
			'cond' => 'm_corp_categories.id = m_target_areas.corp_category_id'
		),
// TODO: 結合条件の見直し
		// 2016.03.23 murata.s ADD start ORANGE-1334
		'commission_tel_supports' => array(
			'cond' => "commission_infos.id = commission_tel_supports.commission_id
				AND commission_tel_supports.id =
				(select id from commission_tel_supports ct where ct.commission_id = commission_infos.id order by ct.created desc limit 1)"
		),
		'commission_visit_supports' => array(
			'cond' => "commission_infos.id = commission_visit_supports.commission_id
				AND commission_visit_supports.id =
				(select id from commission_visit_supports cv where cv.commission_id = commission_infos.id order by cv.created desc limit 1)"
		),
		'commission_order_supports' => array(
			'cond' => "commission_infos.id = commission_order_supports.commission_id
				 AND commission_order_supports.id =
				(select id from commission_order_supports co where co.commission_id = commission_infos.id order by co.created desc limit 1)"
		),
		// 2016.03.23 murata.s ADD end   ORANGE-1334
	);

	private $_default_selected_items = array('demand_infos.id');

// TODO: カラム名と別名称を表示するための変換TBL
// 2016.03.23 murata.s ADD start ORANGE-1334
	private $_replace_function_table_column_name = array(
			1 => array(
					'demand_infos.contact_desired_time' => '[電話対応]初回連絡希望日時',
					'commission_tel_supports.correspond_status' => '[電話対応]最新状況',
					'commission_tel_supports.order_fail_reason' => '[電話対応]失注理由',
					'visit_times.visit_time' => '[訪問対応]訪問日時',
					'commission_visit_supports.correspond_status' => '[訪問対応]最新状況',
					'commission_visit_supports.order_fail_reason' => '[訪問対応]失注理由',
					'commission_order_supports.correspond_datetime' => '[受注対応]受注対応日時',
					'commission_order_supports.correspond_status' => '[受注対応]最新状況',
					'commission_order_supports.order_fail_reason' => '[受注対応]失注理由',
					// 2016.11.02 murata.s ORANGE-189
					'bill_infos.auction_id' => '入札手数料'
					// 2016.11.02 murata.s ORANGE-189
					// 2017.11.08 e.takeuchi@SharingTechnology ADD(S)
					,'commission_infos.fee_billing_date' => 'お試し手数料請求日'
					// 2017.11.08 e.takeuchi@SharingTechnology ADD(S)
			)
	);
// 2016.03.23 murata.s ADD end ORANGE-1334

	/*
	 * CSV用タイトル
	 */
	public $csvFormat = array(
			'default' => array());

	public function __construct() {
		App::import('Model', 'MGeneralSearch');
		App::import('Model', 'MCommissionType');
		App::import('Model', 'GeneralSearchItem');
		App::import('Model', 'GeneralSearchCondition');
		App::import('Model', 'GeneralSearchHistory');
		App::import('Model', 'MSite');
		App::import('Model', 'MUser');
		App::import('Model', 'MAddress1');

		$this->_m_general_search = new MGeneralSearch();
		$this->_general_search_item = new GeneralSearchItem();
		$this->_general_search_condition = new GeneralSearchCondition();
		$this->_general_search_history = new GeneralSearchHistory();
		$this->_m_site = new MSite();
		$this->_m_commission_type = new MCommissionType();
		$this->_m_user = new MUser();
		$this->_m_address1 = new MAddress1();

		$this->_m_site_list = $this->_m_site->getList();
		$this->_m_address1_list = $this->_m_address1->getList();
		$this->_m_genre_list = Util::getDropListGenre();
		$this->_m_commission_type_list = $this->_m_commission_type->getList();
		$this->_m_category_list = Util::getDropListCategory();
		$this->_m_item_demand_status_list = Util::getDropList(__('demand_status', true));
		$this->_m_item_demand_order_fail_reason_list = Util::getDropList(__('order_fail_reason', true));
		$this->_m_item_commission_status_list = Util::getDropList(__('commission_status', true));
		$this->_m_item_commission_order_fail_reason_list = Util::getDropList(__('commission_order_fail_reason', true));
		// 2015.07.24 s.harada ADD start ORANGE-659
		$this->_m_item_pet_tombstone_demand_list = Util::getDropList(__('pet_tombstone_demand' , true));
		$this->_m_item_sms_demand_list = Util::getDropList(__('sms_demand' , true));
		// 2015.07.24 s.harada ADD end ORANGE-659

		$this->_m_item_wcontents_list = Util::getDropList(__('jbr_work_contents', true));
		$this->_m_item_jbr_category_list = Util::getDropList(__('jbr_category', true));
		$this->_m_item_jbr_estimate_list = Util::getDropList(__('jbr_estimate_status', true));
		$this->_m_item_jbr_receipt_list = Util::getDropList(__('jbr_receipt_status', true));
		$this->_m_user_list = $this->_m_user->dropDownUser();
		$this->_m_user_list2 = $this->_m_user->dropDownUser2();
		$this->_m_item_bill_status_list = Util::getDropList(__('bill_status',  true));
		$this->_m_item_coordination_method_list = Util::getDropList(__('coordination_method', true));
		$this->_m_item_prog_send_method_list = Util::getDropList(__('prog_send_method',  true));
		$this->_m_item_bill_send_method_list = Util::getDropList(__('bill_send_method', true));
		$this->_m_item_advertising_status_list = Util::getDropList(__('出稿型サイト状況', true));
		$this->_m_item_payment_site_list = Util::getDropList(__('支払サイト', true));
		$this->_m_corp_status_list = Util::getDropList('開拓状況');
		// ORANGE-359 CHG S
		$this->_m_corp_affiliation_status_list = array('0'=>'未加盟', '1'=>'加盟', '-1'=>'解約');
		// ORANGE-359 CHG E
		$this->_m_corp_corp_commission_status_list = Util::getDropList('開拓取次状況');
		$this->_m_target_areas_list = Util::getDivList('prefecture_div');
		$this->_m_item_liability_insurance = Util::getDropList(__('liability_insurance', true));
		$this->_m_item_waste_collect_oath = Util::getDropList(__('不用品回収誓約書', true));
		$this->_m_corp_categories_order_fee_unit_list = array('0'=>'円', '1'=>'％' );
		//print_r($this->_m_item_payment_site_list);
		// 2015.07.28 h.hanaki ADD start ORANGE-728
		$this->_m_item_reg_send_method_list = Util::getDropList(__('reg_send_method' , true));
		$this->_m_item_corp_status_list = Util::getDropList(__('corp_status' , true));
		$this->_m_item_corp_order_fail_reason_list = Util::getDropList(__('開拓失注理由' , true));
		// 2015.07.28 h.hanaki ADD end   ORANGE-728
		// 2015.08.31 h.hanaki ADD start ORANGE_AUCTION-11
		$this->_m_item_special_measures_list = Util::getDropList(__('案件特別施策' , true));
		// 2015.10.02 h.hanaki ADD       ORANGE-897
		$this->_m_item_acceptance_status_list = Util::getDropList(__('受付ステータス' , true));
		// 2015.10.08 n.kai ADD start ORANGE-910
		$this->_demand_infos_priority_list = array('0'=>'-', '1'=>'大至急', '2'=>'至急', '3'=>'通常' );
		// 2015.10.08 n.kai ADD end
		// 2015.12.09 h.hanaki ORANGE-1087

		//ORANGE-250 CHG S
		$this->_m_corps_auction_status_list = array('1'=>'通常選定＋入札式選定', '2'=>'通常選定のみ', '3'=>'入札式選定のみ' );
		//ORANGE-250 CHG E

		$this->_m_corps_corp_commission_type = Util::getDropList(__('企業取次形態' , true));
		// 2015.12.13 h.hanaki ORANGE-1086
		$this->_commission_infos_reform_upsell_ic_list = array('1'=>'申請', '2'=>'認証', '3'=>'非認証' );
		// 2016.01.13 h.hanaki ORANGE-1183
		$this->_commission_infos_re_commission_exclusion_status = array('0'=>'', '1'=>'成功', '2'=>'失敗' );
		// 2016.03.29 murata.s ADD start ORANGE-1334
		$this->_commission_tel_supports_correspond_status = Util::getDropList(__('電話対応状況', true));
		$this->_commission_visit_supports_correspond_status = Util::getDropList(__('訪問対応状況', true));
		$this->_commission_order_supports_correspond_status = Util::getDropList(__('受注対応状況', true));
		$commission_tel_supports_order_fail_reason = Util::getDropList(__('電話対応失注理由', true));
		$commission_visit_supports_order_fail_reason = Util::getDropList(__('訪問対応失注理由', true));
		$commission_order_supports_order_fail_reason = Util::getDropList(__('受注対応失注理由', true));
		//失注なし（0）の場合
		$this->_commission_tel_supports_order_fail_reason =array_merge(array(0 => ''), $commission_tel_supports_order_fail_reason);
		$this->_commission_visit_supports_order_fail_reason = array_merge(array(0 => ''), $commission_visit_supports_order_fail_reason);
		$this->_commission_order_supports_order_fail_reason = array_merge(array(0 => ''), $commission_order_supports_order_fail_reason);
		// 2016.03.29 murata.s ADD end   ORANGE-1334

		// ORANGE-13 iwai S
		$this->_commission_infos_irregular_reason_list =  Util::getDropList(__('イレギュラー理由' , true));
		// ORANGE-13 iwai E

		//ORANGE-126 2016.8.16 iwai S
		$this->_m_corps_jbr_available_status = Util::getDropList(__('JBR対応状況' , true));
		//ORANGE-126 2016.8.16 iwai E

		// 2016.11.02 murata.s ORANGE-189 ADD(S)
		$this->_demand_infos_construction_class = Util::getDropList('建物種別');
		//$this->_m_corps_contactable_support24hour = array(0=>'', 1=>'24H対応');
		//$this->_m_corps_contactable_other = array(0=>'', '1'=>'その他');
		//$this->_m_corps_mobile_mail_none = array('0'=>'', 1=>'なし');
		$mobile_tel_type = Util::getDropList('携帯電話タイプ', true);
		$this->_m_corps_mobile_tel_type = array_merge(array('0' => 'なし'), $mobile_tel_type);
		$this->_m_corps_auction_masking = Util::getDivList('auction_masking');
		$this->_m_corps_commission_accept_flg = array(0=>'未契約', 1=>'契約完了', 2=>'契約未更新', 3=>'未更新STOP');
		$this->_m_corps_support_language_en = array(0=>'未対応', 1=>'対応');
		$this->_m_corps_support_language_zh = array(0=>'未対応', 1=>'対応');
		$this->_affiliation_infos_listed_kind = array('listed'=>'上場', 'unlisted'=>'非上場');
		$this->_affiliation_infos_default_tax = array('NULL'=> '', '0'=>'滞納なし', '1'=>'滞納あり');
		// 2016.11.02 murata.s ORANGE-189 ADD(E)
// murata.s ORANGE-261 ADD(S)
		$corp_commission_type = Util::getDivList('corp_commission_type');
		$this->_m_corp_categories_corp_commission_type_list = array_merge(array('0' => ''), $corp_commission_type);
// murata.s ORANGE-261 ADD(E)
		// murata.s ORANGE-478 ADD(S)
		$this->_demand_infos_selection_system = Util::getDivList('selection_type');
		// murata.s ORANGE-478 ADD(E)
		// murata.s ORANGE-537 ADD(S)
		$this->_m_corps_auto_call_flag = Util::getDropList('オートコール区分');
		// murata.s ORANGE-537 ADD(E)

	}

	function __get($v) {
		if (property_exists("GeneralSearch", $v)) {
			return $this->{$v};
		}

		return null;
	}

	/*
	 * DropDown用の機能ID一覧の取得
	 */
	public function dropDownFunctionId() {
		$list[] = array();

		foreach ($this->_function_list as $row) {
			if (is_array($this->_function_security_list[$row['value']]))
				if (!in_array($_SESSION['Auth']['User']['auth'], $this->_function_security_list[$row['value']]))
					continue;
			$list[] = $row;
		}

		return $list;
	}

	/*
	 * テーブル名と機能IDの変換
	 */
	private function __convertTableNameToFunctionId($table_name) {

		foreach ($this->_function_tables as $k => $v) {
			if (array_key_exists( $table_name, $v )) return $k;
		}

		return null;
	}

	/*
	 * 機能毎のカラム名取得
	 */
	public function getFunctionColumnList($function_id) {
		$result = $this->findFunctionTableColumn($function_id);

		$lists = array();
		foreach ($result as $row) {
			if (isset($this->_security_table_column[$row[0]['table_name'] . "_" . $row[0]['column_name']]))
				if (!in_array($_SESSION['Auth']['User']['auth'], $this->_security_table_column[$row[0]['table_name'] . "_" . $row[0]['column_name']])) continue;
// TODO: カラム名が重複してしまうかもしれない対応
// 2016.03.23 murata.s ADD start ORANGE-1334
			$key = $row[0]['table_name'] . "." . $row[0]['column_name'];
			if(isset($this->_replace_function_table_column_name[$function_id][$key]))
				$row[0]['column_comment'] = $this->_replace_function_table_column_name[$function_id][$key];
			if($function_id == 1){
				if($row[0]['table_name'] == 'visit_times' && $row[0]['column_name'] == 'visit_time') $key = $key.'.'.$function_id;
				if($row[0]['table_name'] == 'demand_infos' && $row[0]['column_name'] == 'contact_desired_time') $key = $key.'.'.$function_id;
			}
// 2016.03.23 murata.s ADD end ORANGE-1334
			$lists[] = "[\"" . $key . "\",\"" . $row[0]['column_comment'] . "\"]";
		}

		return implode(',', $lists);
	}

	/*
	 * CSVファイル名の取得
	 */
	public function getCsvFileName($function_id) {
		$now = new DateTime();
		return $this->_csv_file_id . $now->format('YmdHis') . ".csv";
	}

	/*
	 * テーブルカラム名をコメントから取得
	 * @function_name 機能名
	 */
	public function findFunctionTableColumn($function_name)
	{

		$retColumns = array();

		foreach ($this->_function_tables[$function_name] as $key => $value) {
			//print_r($value) . "<br>";
        	$retColumns = array_merge($retColumns, $this->__findTableColumn($key, $value));
		}

		return $retColumns;
	}

	/*
	 * 項目コメント取得用
	 */
	private function __findTableColumn($tableName, $columns) {
		$param = "'" . implode('\',\'', $columns) . "'";

		$sql = <<<EOF
select
	psat.relname as table_name,
	pa.attname as column_name,
	pd.description as column_comment
from
	pg_stat_all_tables psat,
	pg_description pd,
	pg_attribute pa
where
	1 = 1
	and psat.relname = '{$tableName}'
	and pa.attname in ( $param )
	and psat.relid = pd.objoid
	and pd.objsubid <> 0
	and pd.objoid = pa.attrelid
	and pd.objsubid = pa.attnum
order by
	pd.objsubid
EOF;
		return $this->query($sql, array(), false);
	}

	/*
	 * GeneralSearch検索用
	 */
	public function findGeneralSearch($type, $params = array()) {
		return $this->_m_general_search->find($type, $params);
	}

	/*
	 * MGeneralSearch検索用(単体)
	 */
	public function findMGeneralSearch($type, $params = array()) {
		$this->_m_general_search->unbindModel(array('hasMany' => array('G_S_Item', 'G_S_Condition')));
		$hasOne = array(
				'MUser' => array('className' => 'MUser',
						'foreignKey' => false,
						'fields' => array('user_name'),
						'conditions' => array('MUser.user_id = MGeneralSearch.created_user_id'))
		);
		$this->_m_general_search->bindModel(array('hasOne' => $hasOne));
		return $this->_m_general_search->find($type, $params);
	}

	/*
	 *
	 */
	public function findGeneralSearchItem($type, $params = array()) {
		$this->_general_search_item->unbindModel(array('belongsTo' => array('MGeneralSearch')));
		return $this->_general_search_item->find($type, $params);
	}

	/*
	 *
	*/
	public function findGeneralSearchCondition($type, $params = array()) {
		$this->_general_search_condition->unbindModel(array('belongsTo' => array('MGeneralSearch')));
		return $this->_general_search_condition->find($type, $params);
	}

	/*
	 * GeneralSearch保存用
	 */
	public function saveGeneralSearch($params) {
		$data = $this->__validateDataGeneralSearch($params);
		$id = "";
		if (isset($data['MGeneralSearch']['id'])) {
			$id = $data['MGeneralSearch']['id'];
			$this->_general_search_item->deleteAll(array("general_search_id" => $id), false);
			$this->_general_search_condition->deleteAll(array("general_search_id" => $id), false);
		}
		$this->_m_general_search->saveAssociated($data, array());
		if (strlen($id) === 0)
			$id = $this->_m_general_search->getLastInsertID ();

		return $id;
	}
	/*
	 * validate
	 */
	private function __validateDataGeneralSearch($params) {
		$data = array();
		$data['MGeneralSearch'] = $this->__validateDataMGeneralSearch($params);
		$data['G_S_Item'] = $this->__validateDataGeneralSearchItem($params);
		$data['G_S_Condition'] = $this->__validateDataGeneralSearchCondition($params);

		return $data;
	}

	private function __validateDataMGeneralSearch($params) {
		$data = array();
		if (strlen($params['MGeneralSearch']['id']) > 0) $data['id'] = $params['MGeneralSearch']['id'];
		$data['definition_name'] = (isset($params['MGeneralSearch']['definition_name'])) ? $params['MGeneralSearch']['definition_name'] : "";
		$data['auth_popular'] = (isset($params['MGeneralSearch']['auth_popular'])) ? 1 : 0;
		$data['auth_admin'] = (isset($params['MGeneralSearch']['auth_admin'])) ? 1 : 0;
		$data['auth_accounting_admin'] = (isset($params['MGeneralSearch']['auth_accounting_admin'])) ? 1 : 0;
		$data['auth_accounting'] = (isset($params['MGeneralSearch']['auth_accounting'])) ? 1 : 0;

		$this->_m_general_search->set($data);
		if (! $this->_m_general_search->validates())
			throw new Exception ("");

		return $data;
	}

	private function __validateDataGeneralSearchItem($params) {
		$datas = array();

		if ( ! isset($params['GeneralSearchItem']['item']) )
			throw new Exception('抽出項目は1つ以上必ず選択して下さい');

		for ($i = 0; $i < count($params['GeneralSearchItem']['item']); $i++) {
			$elems = explode('.', $params['GeneralSearchItem']['item'][$i]);
			if (strlen($params['MGeneralSearch']['id']) > 0) $data['general_search_id'] = $params['MGeneralSearch']['id'];
// TODO: カラムが重複しているかもしれない対応
// 2016.03.23 murata.s ADD start ORANGE-1334
			if(isset($elems[2])){
				$data['function_id'] = $elems[2];
			}else{
				$data['function_id'] = $this->__convertTableNameToFunctionId( $elems[0] );
			}
			//$data['function_id'] = $this->__convertTableNameToFunctionId( $elems[0] );
// 2016.03.23 murata.s ADD end ORANGE-1334
			$data['table_name'] = $elems[0];
			$data['column_name'] = $elems[1];
			$data['output_order'] = $i;

			$datas[] = $data;
		}

		return $datas;
	}

	private function __validateDataGeneralSearchCondition($params) {

		$datas = array();
		foreach ($params['GeneralSearchCondition'] as $condition_expression => $val) {
			foreach ($val as $k => $v) {
				$e = $v;
				if ($condition_expression == 2)
					$e = ($v[0] !== "" || $v[1] !== "") ? implode('^', $v) : "";
				if ($condition_expression == 3)
					$e = (is_array($v)) ? implode('^', $v) : "";
				if ($condition_expression == 4)
					$e = (is_array($v)) ? implode('^', $v) : "";
				if ($condition_expression == 9) {
					if ($k == 'm_target_areas-jis_cd') {
						$e = (is_array($v)) ? implode('^', $v) : "";
					}
				}
				//echo $condition_expression . " " . $k . "(";
				//print_r($e);
				//echo ")<br>";
				if (strlen($e) > 0) {
					list($table_name, $column_name) = explode('-', $k);
					if (strlen($params['MGeneralSearch']['id']) > 0) $data['general_search_id'] = $params['MGeneralSearch']['id'];
					$data['table_name'] = $table_name;
					$data['column_name'] = $column_name;
					$data['condition_expression'] = $condition_expression;
					$data['condition_value'] = $e;
					$data['condition_type'] = 0;

					$datas[] = $data;
				}
			}
		}
		/*
		echo "---<br>";
		print_r($datas);
		echo "---<br>";
		*/
		return $datas;
	}

	/*
	 * GeneralSearchHistory保存用
	*/
	public function saveGeneralSearchHistory($id, $file_name) {
		try {
		$sql = $this->__buildQuery($id, $params);
		$sql_param =  Sanitize::escape($sql, "default"); //. "(" . implode(',', $params) . ")";

		$data = array('GeneralSearchHistory' => array(
							'general_search_id' => $id,
							'output_file_name' => $file_name,
							'query' => $sql_param)
				);

		$this->_general_search_history->save($data, array());
		} catch (Exception $e) {
			echo $e->getMessage();
			die();
		}
		return "";// $this->_general_search_history->save($data, array());
	}

	/*
	 * GeneralSearch削除用
	*/
	public function deleteGeneralSearch($id) {
		return $this->_m_general_search->delete($id,  true);
	}

	/*
	 * 総合検索結果出力用
	 */
	public function findGeneralSearchToCsv($id, $function_id, $limit = 0 ) {
		$this->__setCsvFormat($id);

		$sql = $this->__buildQuery($id, $params, $limit);
		$result = $this->query($sql, $params, false);
		/*
		print_r($result);
		die();
		*/
		$csv_datas = array();
		foreach ($result as $rrow) {
			$csv_datas_row = array();
			foreach ($rrow[0] as $key => $value) {
				if ( array_key_exists($key, $this->_transfer_reference_table)) {
					if ( array_key_exists($value, $this->{$this->_transfer_reference_table[$key]['v']}))
						$value = $this->{$this->_transfer_reference_table[$key]['v']}[$value];
					//if (strlen($value) == 0) $value = "-";
				}
				//$this->log($key, 'error');
				//ORANGE-90 S
				if($limit == 0){
					$user = parent::__getLoginUser();

					if($user["auth"] != 'system' && $user["auth"] != 'admin' && $user['auth'] != 'accounting_admin' ){
						if($key == 'demand_infos_address3')$value = Util::maskingAll($value);
						if($key == 'demand_infos_customer_tel')$value = Util::maskingAll($value);
						if($key == 'demand_infos_customer_name')$value = Util::maskingAll($value);
						if($key == 'demand_infos_tel1')$value = Util::maskingAll($value);
						if($key == 'demand_infos_tel2')$value = Util::maskingAll($value);
					}
				}
				//ORANGE-90 E

				$csv_datas_row[] = $value;
			}
			$csv_datas[] = $csv_datas_row;
		}
		//print_r($csv_datas);
		return $csv_datas;
	}

	private function __setCsvFormat($id) {
		$result = $this->findGeneralSearchItem('all', array('conditions' => array('general_search_id =' . $id)));

		$this->csvFormat['default'] = array();
		foreach ($result as $elem) {
			$comments = $this->__findTableColumn($elem['GeneralSearchItem']['table_name'], array($elem['GeneralSearchItem']['column_name']));
// TODO: CSV用の名称設定
// 2016.03.23 murata.s ADD start ORANGE-1334
			$key = $elem['GeneralSearchItem']['table_name'].'.'.$elem['GeneralSearchItem']['column_name'];
			if(isset($this->_replace_function_table_column_name[$elem['GeneralSearchItem']['function_id']][$key])){
				$comments[0][0]['column_comment'] = $this->_replace_function_table_column_name[$elem['GeneralSearchItem']['function_id']][$key];
			}
// 2016.03.23 murata.s ADD end ORANGE-1334
			$this->csvFormat['default'][] = $comments[0][0]['column_comment'];
		}
	}

	/*
	 *  MGeneralSearch、GeneralSearchItems、GeneralSearchConditionsからqueryを作成
	 */
		private function __buildQuery($id, &$params, $limit = 0) {
		$result = $this->findGeneralSearch('all', array('conditions' => array('id =' . $id)));
		if (count($result) == 0) return "";

		$table_deep = 0;

		$columns = array();
		foreach ($result[0]['G_S_Item'] as $item) {
			$columns[] = $this->_buildColumn($item);
			$table_deep = (array_search($item['table_name'], $this->_function_tables_join_order) > $table_deep) ?
								array_search($item['table_name'], $this->_function_tables_join_order) : $table_deep;
		}

		$wheres = array('1 = 1');
		$params = array();
		foreach ($result[0]['G_S_Condition'] as $cond) {
			if ($cond['condition_expression'] === 0) {
				$wheres[] = $cond['table_name'] . "." . $cond['column_name'] . " = ?";
				$params[] = $cond['condition_value'];
			}
			if ($cond['condition_expression'] === 1) {
				$wheres[] = $cond['table_name'] . "." . $cond['column_name'] . " like '%" . $cond['condition_value'] . "%'";
			}
			if ($cond['condition_expression'] === 2) {
				$sp_val = explode('^', $cond['condition_value']);
				$where = "";
				if (strlen($sp_val[0]) > 0) {
					$where .= $cond['table_name'] . "." . $cond['column_name'] . " >= ? ";
					$params[] = $sp_val[0];
				}
				if (strlen($sp_val[1]) > 0) {
					if (strlen( $where ) > 0) $where .= " and ";
					$where .= $cond['table_name'] . "." . $cond['column_name'] . " <= ?";
					$params[] = $sp_val[1];
				}
				$wheres[] = "(" . $where . ")";
			}
			if ($cond['condition_expression'] === 3) {
				//$wheres[] = $cond['table_name'] . "." . $cond['column_name'] . " in (" . implode(',', explode('^', $cond['condition_value'])) . ")";
				$wheres[] = $cond['table_name'] . "." . $cond['column_name'] . " in (" . implode(',', explode('^', $cond['condition_value'])) . ")";
			}
			if ($cond['condition_expression'] === 4) {
				//$wheres[] = $cond['table_name'] . "." . $cond['column_name'] . " in (" . implode(',', explode('^', $cond['condition_value'])) . ")";
				$wheres[] = $cond['table_name'] . "." . $cond['column_name'] . " in ('" . implode("','", explode('^', $cond['condition_value'])) .  "')";
			}
			if ($cond['condition_expression'] === 9) {
				if ($cond['table_name'] . "." . $cond['column_name'] == 'demand_infos.customer_tel') {
					$wheres[] = '(demand_infos.customer_tel = \'' . $cond['condition_value'] . '\' or demand_infos.tel1 = \'' . $cond['condition_value'] . '\')';
				}
				if ($cond['table_name'] . "." . $cond['column_name'] == 'm_corps.tel1') {
					$wheres[] = '(m_corps.commission_dial = \'' . $cond['condition_value'] . '\' or m_corps.tel1 = \'' . $cond['condition_value'] . '\' or m_corps.tel2 = \'' . $cond['condition_value'] . '\')';
				}
				if ($cond['table_name'] . "." . $cond['column_name'] == 'm_corps-free_text') {
					$wheres[] = '(m_corps.note like \'%' . $cond['condition_value'] . '%\' or affiliation_infos.attention like \'%' . $cond['condition_value'] . '%\')';
					$table_deep = ($table_deep < array_search('affiliation_infos' ,$this->_function_tables_join_order)) ? array_search('affiliation_infos' ,$this->_function_tables_join_order) : $table_deep;
				}
				if ($cond['table_name'] . "." . $cond['column_name'] == 'mony_corresponds.nominee') {
					$wheres[] = "m_corps.corp_id in (select corp_id from money_corresponds where nominee LIKE \'%)" . $cond['condition_value'] . "%\'";
				}
				if ($cond['table_name'] . "." . $cond['column_name'] == 'm_target_areas.jis_cd') {
					$wheres[] = "substring(m_target_areas.jis_cd, 1, 2)::integer in (" . implode(',', explode('^', $cond['condition_value'])) . ")";
					$table_deep = ($table_deep < array_search('m_target_areas' ,$this->_function_tables_join_order)) ? array_search('m_target_areas' ,$this->_function_tables_join_order) : $table_deep;
				}
			}
			$table_deep = (array_search($cond['table_name'], $this->_function_tables_join_order) > $table_deep) ?
							array_search($cond['table_name'], $this->_function_tables_join_order) : $table_deep;
		}

		$from_table = "demand_infos ";
		for ($i = 1; $i < $table_deep + 1; $i++) {
			$from_table .= " left join " . $this->_function_tables_join_order[$i] . " ON " .
							$this->_function_tables_join_rule[$this->_function_tables_join_order[$i]]['cond'];
		}

		$query = "select " . implode(',', $columns) . " from " . $from_table . " where " . implode(' and ', $wheres) . (($limit > 0) ? " limit " . $limit : "");

		return $query;
	}

	/**
	 * 抽出カラムを加工
	 *
	 * @param type $item
	 * @return type
	 */
	protected function _buildColumn($item) {
		if ($item['table_name'] == 'demand_infos' && $item['column_name'] == 'commission_limitover_time') {
			// 案件情報テーブルの取次完了リミット超過時間をフォーマット
			$col_data = <<< QUERY
CASE WHEN demand_infos.commission_limitover_time > 0
         THEN (demand_infos.commission_limitover_time / 60)::varchar || '時間' || (demand_infos.commission_limitover_time % 60)::varchar || '分'
     ELSE NULL END
QUERY;
// 2016.11.02 murata.s ORANGE-189 ADD(S)
		} else if ($item['table_name'] == 'affiliation_infos' && $item['column_name'] == 'default_tax') {
			// NULLの場合、エラーが発生するため一旦加工する
			$col_data = <<<EOF
CASE WHEN affiliation_infos.default_tax = false THEN '0'
WHEN affiliation_infos.default_tax = true THEN '1'
ELSE 'NULL' END
EOF;
		} else if ($item['table_name'] == 'bill_infos' && $item['column_name'] == 'auction_id') {
			// auction_idから入札手数料を取得するために加工する
			$col_data = '(SELECT total_bill_price FROM bill_infos auction_bill_infos WHERE auction_bill_infos.commission_id = commission_infos.id AND auction_bill_infos.auction_id IS NOT NULL LIMIT 1)';
// 2016.11.02 murata.s ORANGE-189 ADD(E)
		} else {
			$col_data = $item['table_name'] . "." . $item['column_name'];
		}

		return $col_data . " as " . $item['table_name'] . "_" . $item['column_name'];
	}

	public function isEnabledDisplayBillInfo() {
		if ($_SESSION['Auth']['User']['auth'] == 'system') return true;
		if ($_SESSION['Auth']['User']['auth'] == 'accounting') return true;
		// 2016.3.29 sasaki@tobila.com MOD start ORANGE-1332
		if ($_SESSION['Auth']['User']['auth'] == 'accounting_admin') return true;
		if ($_SESSION['Auth']['User']['auth'] == 'admin') return true;
		if ($_SESSION['Auth']['User']['auth'] == 'popular') return true;
		// 2016.3.29 sasaki@tobila.com MOD end ORANGE-1332

		return false;
	}


	public function isEnabledDisplaySaveAndDel() {
		// 2016.3.29 sasaki@tobila.com MOD start ORANGE-1332
		if ($_SESSION['Auth']['User']['auth'] == 'affiliation') return false;
		// 2016.3.29 sasaki@tobila.com MOD end ORANGE-1332

		return true;
	}

	public function getCsvPreview($id) {
		//TODO:条件表示部分の作成
		$conditions = $this->__getCsvPreviewCondition($id);
		//TODO:ヘッダ部分の作成
		$table_headers =  $this->__getCsvPreviewHeader($id);
		//TODO:データメイン部の作成(最大100件)
		$table_data = $this->__getCsvPreviewData($id);

		return array('conditions' => $conditions, 'headers' => $table_headers, 'datas' => $table_data);
	}

	private function __getCsvPreviewCondition($id) {
		$result = $this->findGeneralSearchCondition('all', array('conditions' => array('general_search_id =' . $id)));

		$conditions = array();

		foreach ($result as $elem) {
			$comments = $this->__findTableColumn($elem['GeneralSearchCondition']['table_name'], array($elem['GeneralSearchCondition']['column_name']));
			//タイトル
			$title = $comments[0][0]['column_comment'];
			//値
			$value = $elem['GeneralSearchCondition']['condition_value'];
			$key = $elem['GeneralSearchCondition']['table_name'] . "_" . $elem['GeneralSearchCondition']['column_name'];
			if ( array_key_exists($key, $this->_transfer_reference_table)) {
				$values = explode('^', $value);
				$value = "";
				foreach ($values as $v) {
					if ( array_key_exists($v, $this->{$this->_transfer_reference_table[$key]['v']})) {
						if (strlen($value) > 0) $value .= "^";
						$value .= $this->{$this->_transfer_reference_table[$key]['v']}[$v];
					}
				}
			}

			$conditions[] = array('title' => $title, 'value' => $value);
		}

		return $conditions;
	}

	private function __getCsvPreviewHeader($id) {
		$result = $this->findGeneralSearchItem('all', array('conditions' => array('general_search_id =' . $id)));

		$headers = array();

		foreach ($result as $elem) {
			$comments = $this->__findTableColumn($elem['GeneralSearchItem']['table_name'], array($elem['GeneralSearchItem']['column_name']));
// TODO: CSV用の名称設定
// 2016.03.23 murata.s ADD start ORANGE-1334
			$key = $elem['GeneralSearchItem']['table_name'].'.'.$elem['GeneralSearchItem']['column_name'];
			if(isset($this->_replace_function_table_column_name[$elem['GeneralSearchItem']['function_id']][$key])){
				$comments[0][0]['column_comment'] = $this->_replace_function_table_column_name[$elem['GeneralSearchItem']['function_id']][$key];
			}
// 2016.03.23 murata.s ADD end ORANGE-1334
			$headers[] = array($comments[0][0]['column_comment']);
		}

		return $headers;
	}

	private function __getCsvPreviewData($id) {
		return $this->findGeneralSearchToCsv($id, null, 100 );
	}

	public function getDefaultSelectedItem() {
		return '"' . implode('","', $this->_default_selected_items) . '"';
	}
}