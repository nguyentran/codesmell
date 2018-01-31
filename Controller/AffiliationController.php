<?php
use Aws\CloudFront\Exception\Exception;
App::uses ( 'AppController', 'Controller' );
App::uses('DetectView', 'Lib/View');

class AffiliationController extends AppController {
	public $name = 'Affiliation';
	// 2016.02.16 ORANGE-1247 k.iwai ADD(S).
	public $helpers = array('Csv', 'Credit');
	// 2016.02.16 ORANGE-1247 k.iwai ADD(E).
	// 2016.05.30 ota.r@tobila MOD END ORANGE-30 【契約管理】契約規約の特約や条文の変更・削除・追加部分が分かる出力PDFの作成
//	public $components = array('Session', 'Csv');
	//ORANGE-337 CHG S
	public $components = array('Session', 'Csv', 'PDF', 'CustomEmail');
	//ORANGE-337 CHG E
	// 2016.05.30 ota.r@tobila MOD END ORANGE-30 【契約管理】契約規約の特約や条文の変更・削除・追加部分が分かる出力PDFの作成
	public $viewClass = 'Detect';

	//2016.4.7 ORANGE-1341 iwai CHG(E)
	// 2015.02.23 企業エリアマスタついかのためMCorpTargetAreaを追加
	public $uses = array ('MCorp', 'MItem', 'MCorpSub', 'AffiliationInfo', 'AffiliationSub', 'AffiliationStat', 'AffiliationAreaStat', 'MCategory', 'MGenre', 'MTargetArea', 'MPost', 'MCorpCategory', 'AffiliationCorrespond',
		'MCorpTargetArea','MCorpNewYear', 'MCategoryCopyrule','CorpAgreement','AgreementAttachedFile','AgreementProvisions','Agreement','AgreementProvisionsItem','AgreementEditHistory', 
		// 2017/10/18 ORANGE-541 m-kawamoto DEL(S)
		//'CorpLisenseLink',
		// 2017/10/18 ORANGE-541 m-kawamoto DEL(E)
		'AgreementCustomize',
		// 2016.06.23 murata.s ORANGE-102 ADD(S)
		'CorpAgreementTempLink', 'MCorpCategoriesTemp',
		// 2016.06.23 murata.s ORANGE-102 ADD(E)
		// 2016.08.23 murata.s ORANGE-169 ADD(S)
		'AuthInfo',
		// 2016.08.23 murata.s ORANGE-169 ADD(E)
		// 2017/10/18 ORANGE-541 m-kawamoto DEL(S)
		//// 2016.09.15 murata.s ORANGE-183 ADD(S)
		//'CategoryLicenseLink',
		//// 2016.09.15 murata.s ORANGE-183 ADD(E)
		//// 2017.01.12 murata.s ORANGE-293 ADD(S)
		//'License',
		//// 2017.01.12 murata.s ORANGE-293 ADD(E)
		// 2017/10/18 ORANGE-541 m-kawamoto DEL(E)
		//ORANGE-393 ADD S
		'CorpCategoryGroupApplication',
		'CorpCategoryApplication',
		'Approval',
		//ORANGE-393 ADD E
		//ORANGE-347 ADD S
		'MTargetAreasTemp',
		//ORANGE-347 ADD E
                //2017/06/08  ichino ORANGE-420 ADD start 
                'AutoCommissionCorp',
                //2017/06/08  ichino ORANGE-420 ADD end 
	);
	//2016.4.7 ORANGE-1341 iwai CHG(E)
	public function beforeFilter() {
		$this->set ( 'default_display', false );
		$this->User = $this->Auth->user();
		// ユーザー一覧
		$this->set ("user_list", $this->MUser->dropDownUser());

		parent::beforeFilter ();

// 2016.10.06 murata.s ADD(S) 脆弱性 権限外の操作対応
		if($this->User['auth'] == 'affiliation'){
			$actionName = strtolower($this->action);
			switch($actionName){
				case 'targetarea':
					$check_data = $this->MCorpCategory->findById($this->request->params['pass'][0]);
					if(!empty($check_data) && $check_data['MCorpCategory']['corp_id'] != $this->User['affiliation_id']){
						throw new ApplicationException(__('NoReferenceAuthority', true));
					}
					break;
				default:
					break;
			}

		}
// 2016.10.06 murata.s ADD(E) 脆弱性 権限外の操作対応
                     
                // 2017/07/11 ichino ORANGE-459 ADD start
                //休業日をm_itemsから取得する
                $vacation = $this->MItem->getList('長期休業日');
                
                //viewへ渡す
                $this->set("vacation", $vacation);
                // 2017/07/11 ichino ORANGE-459 ADD end
                
	}

	/**
	 * 初期表示（一覧）
	 */
	public function index($tel = null , $status = null) {
		if(!empty($tel) && !empty($status)){
			$this->request->data['tel1'] = $tel;
			$this->request->data['affiliation_status'] = $status;
			$this->affiliationListPost ();
			$this->redirect ( '/affiliation/search' );
		}
		$this->set ( 'default_display', true);
	}

	/**
	 * 検索画面ボタン押下時
	 *
	 * @throws Exception
	 */
	public function search() {

		$this->Session->delete(self::$__sessionKeyForAffiliationReturn);

		if (isset ( $this->request->data ['detail'] )) { // 新規登録

			$this->redirect ( '/affiliation/detail' );
			return;
		} else if (isset ( $this->request->data ['csv_out'] ) && ($this->User["auth"] == 'system' || $this->User["auth"] == 'admin'|| $this->User['auth'] == 'accounting_admin')) { // CSV出力
			Configure::write('debug', '0');
			$this->current_controller->layout = false;
			$data_list = $this->__getDataCsv($this->request->data);
			$file_name = mb_convert_encoding(__('affiliation' , true).'_'.$this->User['user_id'], 'SJIS-win', 'UTF-8');
			$this->Csv->download('AffiliationInfo', 'default', $file_name, $data_list);
			return;
		}

		try {
			if ($this->request->is ( 'Post' ) || $this->request->is ( 'put' )) {
				$data = $this->affiliationListPost ();
			} else {
				$data = $this->affiliationListGet ();
			}
		} catch ( Exception $e ) {
			throw $e;
		}
		self::__set_parameter_session ();

		// 検索条件の設定
		$conditions = self::setSearchConditions ( $data );

		$joins = array ();
		$joins = self::setSearchJoin ($data);

		$param = array ();

		// 検索する
		$this->paginate = array (
				'conditions' => $conditions,
				'fields' => '*, MItem.item_name, (select ARRAY_TO_STRING(ARRAY( select category_name From m_categories inner join m_corp_categories on m_corp_categories.category_id = m_categories.id and m_corp_categories.corp_id = "MCorp"."id" ),\',\') as "MCorp__category_name" )',
				'joins' => $joins,
				'limit' => Configure::read ( 'list_limit' ),
// 				'group' => array (
// 						'MCorp.id , MItem.item_name'
// 				),
				'order' => array (
						'MCorp.id' => 'asc'
				)
		);

		$results = $this->paginate ( 'MCorp' );

		$this->set ( 'results', $results );

		$this->render ( 'index' );
	}

	/**
	 * 一覧の検索結果を取得しセット
	 *
	 * @return multitype:Ambigous <NULL, mixed, multitype:>
	 */
	private function affiliationListGet() {
		$data = $this->Session->read ( self::$__sessionKeyForAffiliationSearch );

		if (isset ( $data )) {
			$page = $this->__getPageNum ();
			$this->data = $data;
		}

		if (! empty ( $data )) {
			$data += array (
					'page' => $page
			);
		}

		return $data;
	}

	/**
	 * 検索結果をセッションにセット
	 *
	 * @return multitype:
	 */
	private function affiliationListPost() {

		// 入力値取得
		$data = $this->request->data;

		// セッションに検索条件を保存
		$this->Session->delete ( self::$__sessionKeyForAffiliationSearch );
		$this->Session->write ( self::$__sessionKeyForAffiliationSearch, $data );

		return $data;
	}

	/**
	 * 検索条件設定
	 *
	 * @param string $data
	 * @return multitype:string
	 */
	private function setSearchConditions($data = null) {
		$conditions = array ();

		// 企業ID
		if (! empty ( $data ['id'] )) {
			$conditions ['id'] = $data ['id'];
		}

		//ORANGE-4 CHG S

		// 企業名
		if (! empty ( $data ['corp_name'] )) {
			//ORANGE-4 CHG S
			//$conditions ['z2h_kana(MCorp.corp_name) like'] = '%' . Util::chgSearchValue($data ['corp_name']) . '%';
			array_push($conditions , array (
					'or' => array (
									'z2h_kana(MCorp.corp_name) like' => '%' . Util::chgSearchValue($data ['corp_name']). '%',
									'z2h_kana(MCorp.official_corp_name) like' => '%' . Util::chgSearchValue($data ['corp_name']). '%'
							),
					)
			);

		}

		// 企業名ふりがな
		if (! empty ( $data ['corp_name_kana'] )) {
			$conditions ['z2h_kana(MCorp.corp_name_kana) like'] = '%' . Util::chgSearchValue($data ['corp_name_kana']) . '%';
		}

		// 企業ID
		if (! empty ( $data ['corp_id'] )) {
			//$conditions ['AffiliationInfo.corp_id'] = Util::chgSearchValue($data ['corp_id']);
			$conditions ['MCorp.id'] = Util::chgSearchValue($data ['corp_id']);
		}

		// 都道府県
		if (! empty ( $data ['address1'] )) {
			$conditions ['MCorp.address1'] = $data ['address1'];
		}
//ORANGE-186 CHG S
		if (! empty ( $data ['tel1'] )) {
			array_push($conditions, array (
					'or' => array (
							'MCorp.commission_dial' => Util::chgSearchValue($data ['tel1']),
							'MCorp.tel1' => Util::chgSearchValue($data ['tel1']),
							'MCorp.tel2' => Util::chgSearchValue($data ['tel1']),
							'MCorp.mobile_tel' => Util::chgSearchValue($data ['tel1'])
						)
					)
			);
		}
//ORANGE-186 CHG E
		// フリーテキスト
		if (! empty ( $data ['free_text'] )) {
			array_push($conditions, array (
					'or' => array (
								'z2h_kana(MCorp.note) like' => '%'. Util::chgSearchValue($data['free_text']). '%',
								'z2h_kana(AffiliationInfo.attention) like' => '%'. Util::chgSearchValue($data['free_text']). '%'
							),
					)
			);
		}
		// リスト元媒体
		if (! empty ( $data ['listed_media'] )) {
			$conditions ['z2h_kana(MCorp.listed_media) like'] =  '%'. Util::chgSearchValue($data['listed_media']). '%';
		}
		//ORANGE-4 CHG E

		// FAX番号
		if (! empty ( $data ['fax'] )) {
			$conditions ['z2h_kana(MCorp.fax)'] = Util::chgSearchValue($data ['fax']);
		}

		// PCメール
		if (! empty ( $data ['mailaddress_pc'] )) {
			$conditions ['z2h_kana(MCorp.mailaddress_pc) like'] = '%' . Util::chgSearchValue($data ['mailaddress_pc']) . '%';
		}

		// 携帯メール
		if (! empty ( $data ['mailaddress_mobile'] )) {
			$conditions ['z2h_kana(MCorp.mailaddress_mobile) like'] = '%' . Util::chgSearchValue($data ['mailaddress_mobile']) . '%';
		}

		// 開拓状況
		if (! empty ( $data ['corp_status'] )) {
			$conditions ['MCorp.corp_status'] = $data ['corp_status'];
		}

		// 加盟/未加盟
		if (isset($data ['affiliation_status']) && $data ['affiliation_status'] != NULL) {
			$conditions ['MCorp.affiliation_status'] = $data ['affiliation_status'];
		}

		// 後追い日 From
		if (! empty ( $data ['from_follow_date'] )) {
			$conditions ['MCorp.follow_date >='] = $data ['from_follow_date'];
		}

		// 後追い日 To
		if (! empty ( $data ['to_follow_date'] )) {
			$conditions ['MCorp.follow_date <='] = $data ['to_follow_date'];
		}
/*
		// ジャンル
		if (! empty ( $data ['genre_id'] )) {
			$conditions ['MCorpCategory.genre_id'] = $data ['genre_id'];
		}

		// 2015.01.14 h.hara
		// 対応可能エリア(都道府県)
		if (! empty ( $data ['ta_address1'] )) {
			for ($i =0; $i < count($data ['ta_address1']); $i++) {
				$data ['ta_address1'][$i] = sprintf("%02d", $data ['ta_address1'][$i]);
			}
			if (count($data ['ta_address1']) > 1) {
				$conditions ['SUBSTRING(MTargetArea.jis_cd, 1, 2) IN '] = $data ['ta_address1'];
			}
			else {
				$conditions ['SUBSTRING(MTargetArea.jis_cd, 1, 2) '] = $data ['ta_address1'];
			}
		}
*/
		// 後追い担当者
		if (! empty ( $data ['rits_person'] )) {
			$conditions ['MCorp.rits_person'] = $data ['rits_person'];
		}

		// 開拓取次状況
		if (! empty ( $data ['corp_commission_status'] )) {
			$conditions ['MCorp.corp_commission_status'] = $data ['corp_commission_status'];
		}

		// 2015.4.20 n.kai ADD start
		// 24時間対応
		if ($data ['support24hour'] != NULL) {
			$conditions ['MCorp.support24hour'] = $data ['support24hour'];
		}
		// 2015.4.20 n.kai ADD end

		return $conditions;
	}

	/**
	 * 検索用Join句作成
	 *
	 * @return multitype:multitype:string multitype:string
	 */
	private function setSearchJoin($data) {
		$joins = array ();

// murata.s ORANGE-400 ADD(S)
		$dbo = $this->MCorp->getDataSource();
		$CorpAgreement = $dbo->buildStatement(array(
				'fields' => array('corp_id', 'min(acceptation_date) as acceptation_date'),
				'table' => 'corp_agreement',
				'alias' => 'CorpAgreementA',
				'conditions' => array('status' => 'Complete'),
				'group' => array('corp_id')
		), $this->CorpAgreement);
// murata.s ORANGE-400 ADD(E)

		$joins = array (
				array (
						'table' => 'affiliation_infos',
						'alias' => "AffiliationInfo",
						'type' => 'left',
						'conditions' => array (
								'MCorp.id = AffiliationInfo.corp_id'
						)
				),
/*
				array (
						'table' => 'm_corp_categories',
						'alias' => "MCorpCategory",
						'type' => 'left',
						'hasMany'=>array('MCorp'),
						'conditions' => array (
								'MCorp.id = MCorpCategory.corp_id'
						)
				),

				array (
						'table' => 'm_target_areas',
						'alias' => "MTargetArea",
						'type' => 'left',
						'hasMany'=>array('MCorpCategory'),
						'conditions' => array (
								'MCorpCategory.id = MTargetArea.corp_category_id'
						)
				),
*/
				array (
						'fields' => '*',
						'type' => 'left',
						"table" => "m_items",
						"alias" => "MItem",
						"conditions" => array (
								"MItem.item_category = '" . __ ( 'corp_status', true ) . "'",
								"MItem.item_id = MCorp.corp_status"
						)
				),
// murata.s ORANGE-400 ADD(S)
				array(
						'fields' => '*',
						'type' => 'left',
						'table' => "({$CorpAgreement})",
						'alias' => 'CorpAgreement',
						'conditions' => array(
								'MCorp.id = CorpAgreement.corp_id'
						)
				)
// murata.s ORANGE-400 ADD(E)
		);

		if (! empty ( $data ['ta_address1'] )) {
			for ($i =0; $i < count($data ['ta_address1']); $i++) {
				$data ['ta_address1'][$i] = sprintf("%02d", $data ['ta_address1'][$i]);
			}
			if (count($data ['ta_address1']) > 1) {
				for ($i =0; $i < count($data ['ta_address1']); $i++) {
					$address_array[] =  "SUBSTRING(jis_cd, 1, 2) = '". $data ['ta_address1'][$i]."'";
				}
				$target_areas_str = implode ( ' OR ', $address_array);
			} else {
				$target_areas_str = "SUBSTRING(jis_cd, 1, 2) = '". $data ['ta_address1'][0]."'";
			}

			$subQuery = "(SELECT corp_id FROM m_corp_categories WHERE id in (SELECT corp_category_id FROM m_target_areas WHERE ".$target_areas_str.") GROUP BY corp_id)";

		}

		if (! empty ( $data ['genre_id'] )) {
			$genre_str = implode ( ',', $data ['genre_id'] );
			// 2015.03.19 h.hara ジャンルとエリアは同じサブクエリ内で検索するよう修正
// 			$subQuery = "(SELECT corp_id FROM m_corp_categories WHERE genre_id in (" . $genre_str . ") GROUP BY corp_id)";
			if (!empty($target_areas_str)) {
				$subQuery = "(SELECT corp_id FROM m_corp_categories WHERE genre_id in (" . $genre_str . ") AND id in (SELECT corp_category_id FROM m_target_areas WHERE ".$target_areas_str.") GROUP BY corp_id)";
			}
			else {
				$subQuery = "(SELECT corp_id FROM m_corp_categories WHERE genre_id in (" . $genre_str . ") GROUP BY corp_id)";
			}
			$genre_joins = array (
					"type" => 'inner',
					"alias" => 'MCC',
					"table" => $subQuery,
					"conditions" => 'MCorp.id = MCC.corp_id'
			);
			array_push ( $joins, $genre_joins );
		}

// 		if (! empty ( $data ['ta_address1'] )) {
// 			for ($i =0; $i < count($data ['ta_address1']); $i++) {
// 				$data ['ta_address1'][$i] = sprintf("%02d", $data ['ta_address1'][$i]);
// 			}
// 			if (count($data ['ta_address1']) > 1) {
// 				for ($i =0; $i < count($data ['ta_address1']); $i++) {
// 					$address_array[] =  "SUBSTRING(jis_cd, 1, 2) = '". $data ['ta_address1'][$i]."'";
// 				}
// 				$target_areas_str = implode ( ' OR ', $address_array);
// 			} else {
// 				$target_areas_str = "SUBSTRING(jis_cd, 1, 2) = '". $data ['ta_address1'][0]."'";
// 			}

// 			$subQuery = "(SELECT corp_id FROM m_corp_categories WHERE id in (SELECT corp_category_id FROM m_target_areas WHERE ".$target_areas_str.") GROUP BY corp_id)";

// 			$target_areas_joins = array (
// 					"type" => 'inner',
// 					"alias" => 'MTA',
// 					"table" => $subQuery,
// 					"conditions" => 'MCorp.id = MTA.corp_id'
// 			);
// 			array_push ( $joins, $target_areas_joins );
// 		}

		return $joins;
	}

	/**
	 * 加盟店詳細
	 *
	 * @param string $id
	 */
	public function detail($id = null) {
		$resultsFlg = false; // 更新結果フラグ
		$newFlg = false; // 新規フラグ
		$again_enabled = false;		//再表示ボタン活性制御

		// 加盟店ID
		if (isset ( $this->request->data ['regist'] ) || isset ( $this->request->data ['delete'] )) {
			$affiliation_id = $this->request->data ['affiliationInfo_id'];
		}

		if (isset ( $this->request->data ['regist'] )) { // 登録
                    
			// 更新日付のチェック
			$resultsFlg = self::__check_modified_mcorp ( $id, $this->request->data ['modified_data'] );
			if ($resultsFlg) {
				try {
					$this->MCorp->begin ();
					$this->MCorpSub->begin ();
					$this->MCorpNewYear->begin ();
					$this->AffiliationInfo->begin ();
					$this->AffiliationSub->begin ();
					$this->AffiliationCorrespond->begin ();

// 2016.07.28 murata.s ORANGE-132 ADD(S)
					// 代表者（名）、本社所在地/代表者住所(必須)チェックをOFF
					unset($this->MCorp->validate['responsibility_mei']);
					unset($this->MCorp->validate['representative_address1']['NotEmptyAddress1']);
					unset($this->MCorp->validate['representative_address2']['NotEmptyAddress2']);
					unset($this->MCorp->validate['representative_address3']['NotEmptyAddress3']);

					// 銀行名、支店名、預金種別、口座番号チェックをOFF
					unset($this->MCorp->validate['refund_bank_name']);
					unset($this->MCorp->validate['refund_branch_name']);
					unset($this->MCorp->validate['refund_account_type']);
					unset($this->MCorp->validate['refund_account']);
// 2016.07.28 murata.s ORANGE-132 ADD(E)

					// 企業マスタの登録
					$resultsFlg = $this->__edit_corp ( $id, $this->request->data, $last_id );

					if ($resultsFlg) {
						// 新規登録の場合、企業idを上書き
						if (empty ( $id )) {
							$newFlg = true;
							$id = $last_id;
						}
						// 企業マスタ付帯情報の登録
						$resultsFlg = $this->__edit_corp_subs ( $id, $this->request->data );
						// 年末年始状況の登録
						$resultsFlg = $this->__edit_corp_new_years ( $id, $this->request->data );
					}

					if ($resultsFlg) {
						//与信用メール送信フラグ対応 ORANGE-
						if($this->request->data['AffiliationInfo']['allow_credit_mail_send']){
							$this->request->data['AffiliationInfo']['credit_mail_send_flg'] = 0;
						}

						// 加盟店情報の登録
						$resultsFlg = $this->__edit_affiliation ( $id, $this->request->data, $last_affiliation_id );
					}

					if ($resultsFlg) {
						// 新規登録の場合、加盟店IDを上書き
						if (empty ( $affiliation_id )) {
							$affiliation_id = $last_affiliation_id;
						}
						// 加盟店付帯情報の登録
						$resultsFlg = $this->__edit_affiliation_subs ( $affiliation_id, $this->request->data );
					}

					if ($resultsFlg) {
						// 加盟店履歴
						$resultsFlg = self::__regist_history ( $id, $this->request->data ['AffiliationCorrespond'] );
					}

					if ($resultsFlg) {
						$this->MCorp->commit ();
						$this->MCorpSub->commit ();
						$this->MCorpNewYear->commit ();
						$this->AffiliationInfo->commit ();
						$this->AffiliationSub->commit ();
						$this->AffiliationCorrespond->commit ();
						// 新規登録の場合
						// 2014.12.15 inokuchi
						// if($newFlg){
						// return $this->redirect('/affiliation/category/'.$id);
						// }
						$this->Session->setFlash ( __ ( 'Updated', true ), 'default', array (
								'class' => 'message_inner'
						) );
					} else {
						$this->MCorp->rollback ();
						$this->MCorpSub->rollback ();
						$this->AffiliationInfo->rollback ();
						$this->AffiliationSub->rollback ();
						$this->AffiliationCorrespond->rollback ();
						$this->Session->setFlash ( __ ( 'InputError', true ), 'default', array (
								'class' => 'error_inner'
						) );
					}
				} catch ( Exception $e ) {
					$this->MCorp->rollback ();
					$this->MCorpSub->rollback ();
					$this->AffiliationInfo->rollback ();
					$this->AffiliationSub->rollback ();
					$this->AffiliationCorrespond->rollback ();
				}
			} else {
				$this->Session->setFlash ( __ ( 'ModifiedNotCheck', true ), 'default', array (
						'class' => 'error_inner'
				) );
				$again_enabled = true;

			}

			if ($resultsFlg) {
				// 企業情報の取得
				$this->data = $this->__get_m_corp_data ( $id );
				// 企業マスタ付帯情報の取得
				$this->__get_m_corp_subs_list ( $id );
				// 加盟店付帯情報の取得
				$this->__get_affiliation_subs_list ( $id );
			} else {
				// 入力値をセット
				! empty ( $data ['AffiliationInfo'] ['reg_pdf_path'] ['name'] ) ? $this->request->data ['AffiliationInfo'] ['reg_pdf_path'] = $data ['AffiliationInfo'] ['reg_pdf_path'] ['name'] : $this->request->data ['AffiliationInfo'] ['reg_pdf_path'] = '';
				$this->request->data ['AffiliationInfo'] ['id'] = $this->request->data ['affiliationInfo_id'];
				$this->request->data ['MCorp'] ['modified'] = $this->request->data ['modified_data'];
				$this->data = $this->request->data;
				$this->__input_after_display ( $this->request->data );
			}
			$this->set('again_enabled', $again_enabled);

		} else if (isset ( $this->request->data ['cancel'] )) { // キャンセル

			return $this->redirect ( '/affiliation/' );
		} else if (isset ( $this->request->data ['delete'] )) { // 削除

			// 関連テーブル削除
			if ($this->__delete_corp ( $id, $affiliation_id )) :
				return $this->redirect ( '/affiliation' );

			endif;
		} else if (isset ( $this->request->data ['category'] )) { // カテゴリ選択へ

			return $this->redirect ( '/affiliation/category/' . $id );
		} else {

			// 企業情報の取得
			$this->data = $this->__get_m_corp_data ( $id );

			// 企業マスタ付帯情報の取得
			$this->__get_m_corp_subs_list ( $id );

			// 加盟店付帯情報の取得
			$this->__get_affiliation_subs_list ( $id );
		}

		// 取次STOPカテゴリの取得
		$this->__get_stop_category_list ( $id );

		// 加盟店ジャンル別統計情報の取得
		$this->__get_affiliation_stats_list ( $id );

		if (! empty ( $id )) {
			// 加盟店対応履歴データの検索条件
			$conditions = array (
					'AffiliationCorrespond.corp_id' => $id
			);
			// 加盟店対応履歴データの取得
			$history_data = self::__set_affiliation_correspond ( $conditions );
		} else {
			$history_data = array ();
		}
		$this->set('again_enabled', $again_enabled);
		$this->set ( "history_list", $history_data );

		// ORANGE-346 反社チェック履歴の取得
		$this->loadModel('AntisocialCheck');
		$this->set("antisocial_checks", $this->AntisocialCheck->findHistoryByCorpId($id, 'all'));
		$this->set("antisocial_result_list", $this->AntisocialCheck->getResultList());
		$this->set("antisocial_check_month_list", $this->AntisocialCheck->getMonthList());
		$this->set("antisocial_check_update_authority", $this->AntisocialCheck->is_update_authority($this->User['auth']));
		// ORANGE-346 風評チェック履歴の取得
		$this->loadModel('ReputationCheck');
		$this->set("reputation_checks", $this->ReputationCheck->findHistoryByCorpId($id, 'all'));
		
		// 2017/10/18 ORANGE-541 m-kawamoto ADD(S)
		// 会社毎の契約の取得(corp_agreement)
		$corp_agreement = $this->CorpAgreement->find ( 'first', array (
				'fields' => '*',
				'conditions' => array (
						'CorpAgreement.corp_id' => $id
				),
				'order' => array('CorpAgreement.id' =>'desc'),
		));
		$this->set("corp_agreement", $corp_agreement);
		// 2017/10/18 ORANGE-541 m-kawamoto ADD(E)
		
	}

	/**
	 * カテゴリ選択
	 *
	 * @param string $id
	 */
	public function category($id = null) {
		$resultsFlg = true;
		//2015.06.30 y.fujimori ADD start
		$this->set( "error" , false );
		//2015.06.30 y.fujimori ADD end
		if (empty($id)) {
			throw new Exception();
		}

		// 企業エリアマスターの取得
		$corp_areas = $this->MCorpTargetArea->find('all',
				array(
						'fields' => '*',
						'conditions' => array(
								'corp_id = ' . $id
						)
				)
		);
		// 基本対応エリアの設定エリア数
		$corp_target_area_count = $this->MCorpTargetArea->getCorpTargetAreaCount($id);
		// 対応可能ジャンルリストの取得
		$results = $this->MCorpCategory->getMCorpCategoryGenreList($id);

		$genre_custom_area_list = array();
		$genre_normal_area_list = array();
		foreach ($results as $key => $val) {
			// 基本対応エリアのままのジャンルとカスタマイズしているジャンルの区分け
			$custom_flg = false;
			$mstedt_flg = false;

			// 「target_area_type（対応可能エリアタイプ）」が未設定
			if ($val['MCorpCategory']['target_area_type'] == 0) {
				$mstedt_flg = true;
				$target_area_count = $this->MTargetArea->getCorpCategoryTargetAreaCount($val['MCorpCategory']['id']);
				if ($target_area_count != $corp_target_area_count) {
					$custom_flg = true;
				}
				foreach ($corp_areas as $area_v) {
					$area_count = $this->MTargetArea->getCorpCategoryTargetAreaCount2($val['MCorpCategory']['id'], $area_v['MCorpTargetArea']['jis_cd']);
					if ($area_count <= 0) {
						$custom_flg = true;
						break;
					}
				}

			} else if ($val['MCorpCategory']['target_area_type'] == 2) {
				// 対応可能エリアが基本対応エリアと異なる
				$custom_flg = true;
			}

			//
			if ($custom_flg == true) {
				// 対応エリアをカスタマイズしているジャンルリスト
				$genre_custom_area_list[] = $results[$key];

				if ($mstedt_flg) {
					// 「m_corp_categories」の「target_area_type（対応可能エリアタイプ）」を更新
					foreach ($list_data as $val) {
						self::__edit_corp_category_target_area_type($val['MCorpCategory']['id'], 2);
					}
				}
			} else {
				// 対応エリアが基本対応エリアのままのジャンルリスト
				$genre_normal_area_list[] = $results[$key];

				if ($mstedt_flg) {
					// 「m_corp_categories」の「target_area_type（対応可能エリアタイプ）」を更新
					// 2016.08.04 murata.s CHG(S) empty($list_data) = trueの場合Warningが発生する
					if(!empty($list_data)) {
						foreach ($list_data as $val) {
							self::__edit_corp_category_target_area_type($val['MCorpCategory']['id'], 1);
						}
					}
					// 2016.08.04 murata.s CHG(E)
				}
			}
		}

		$this->set("genre_custom_area_list" , $genre_custom_area_list);
		$this->set("genre_normal_area_list" , $genre_normal_area_list);

		// 企業情報セット
		//$this->set ("corp_data", $corp_data );

		// 企業マスタ付帯情報の取得
		//$this->__get_m_corp_subs_list ( $id );

		if (isset ( $this->request->data ['regist-corp'] )) { // 企業情報登録

			if (empty($this->request->data ['holiday'])) {
				// 休業日必須チェック
				$this->Session->setFlash ( __ ( 'NotEmptyHoliday', true ), 'default', array (
						'class' => 'error_inner'
				) );
				$resultsFlg = false;
			} else {

				// 更新日付のチェック
				$resultsFlg = self::__check_modified_mcorp ( $id, $this->request->data ['modified_data'] );
				if ($resultsFlg) {
					try {
						$this->MCorp->begin ();
						$this->MCorpSub->begin ();
// 2016.07.28 murata.s ORANGE-52 ADD(S)
						$this->MCorpNewYear->begin();
// 2016.07.28 murata.s ORANGE-52 ADD(E)
						// 企業マスタの登録
						$resultsFlg = $this->__edit_corp ( $id, $this->request->data, $last_id );

						if ($resultsFlg) {
							// 新規登録の場合、企業idを上書き
							if (empty ( $id )) {
								$newFlg = true;
								$id = $last_id;
							}
							// 企業マスタ付帯情報の登録
							$resultsFlg = $this->__edit_corp_subs ( $id, $this->request->data );
// 2016.07.28 murata.s ORANGE-52 ADD(S)
							// 年末年始状況の登録
							$resultsFlg = $this->__edit_corp_new_years($id, $this->request->data);
// 2016.07.28 murata.s ORANGE-52 ADD(E)
						}

						if ($resultsFlg) {
							$this->MCorp->commit ();
							$this->MCorpSub->commit ();
// 2016.07.28 murata.s ORANGE-52 ADD(S)
							$this->MCorpNewYear->commit();
// 2016.07.28 murata.s ORANGE-52 ADD(E)
							$this->Session->setFlash ( __ ( 'Updated', true ), 'default', array (
									'class' => 'message_inner'
							) );
						} else {
							$this->MCorp->rollback ();
							$this->MCorpSub->rollback ();
// 2016.07.28 murata.s ORANGE-52 ADD(S)
							$this->MCorpNewYear->rollback();
// 2016.07.28 murata.s ORANGE-52 ADD(E)
							$this->Session->setFlash ( __ ( 'NotEmptyAffiliationBaseItem', true ), 'default', array (
									'class' => 'error_inner'
							) );
						}
					} catch ( Exception $e ) {
						$this->MCorp->rollback ();
						$this->MCorpSub->rollback ();
// 2016.07.28 murata.s ORANGE-52 ADD(S)
						$this->MCorpNewYear->rollback();
// 2016.07.28 murata.s ORANGE-52 ADD(E)
					}
				} else {
					$this->Session->setFlash ( __ ( 'ModifiedNotCheck', true ), 'default', array (
							'class' => 'error_inner'
					) );
				}
			}

			if ($resultsFlg) {
				// 企業情報の取得
				//$this->data = $this->__get_m_corp_data ( $id );
				$corp_data = $this->__get_m_corp_data ( $id );
// 2016.07.28 murata.s ORANGE-100 ADD(S)
				// 企業マスタ付帯情報の取得
				$this->__get_m_corp_subs_list ( $id );
// 2016.07.28 murata.s ORANGE-100 ADD(E)
// 2016.08.04 murata.s ORANGE-17 ADD(S)
				// affiliation_infoの取得
				$this->set("affiliation_info" , $this->AffiliationInfo->find('first', array('conditions' => array('AffiliationInfo.corp_id' => $id))));
// 2016.08.04 murata.s ORANGE-17 ADD(E)

//				$this->data = $this->request->data;

			} else {
				// 入力値をセット
				$this->request->data ['MCorp'] ['modified'] = $this->request->data ['modified_data'];
				//$this->request->data ['MCorp'] = $this->request->data ['MCorp'];
				$this->request->data ['MCorp']['id'] = $this->request->data ['corp_id'];
				$this->request->data ['MCorp']['official_corp_name'] = $this->request->data ['official_corp_name'];
				if (!isset($this->request->data ['MCorp']['mailaddress_mobile'])) {
                    $this->request->data ['MCorp']['mailaddress_mobile'] = "";
                }
				//$this->data = $this->request->data;
				$corp_data = $this->request->data;
// 2016.07.28 murata.s ORANGE-100 ADD(S)
				// 休業日の入力値をセット
				$holiday = array();
				if(isset($this->request->data['holiday'])){
					foreach($this->request->data['holiday'] as $key=>$val){
						$holiday[] = $key;
					}
				}
				$this->set ( "holiday", $holiday );
// 2016.07.28 murata.s ORANGE-100 ADD(E)
// 2016.08.04 murata.s ORANGE-17 ADD(S) 入力チェックエラー時にaffiliation_infoがセットされていない
				// affiliation_infoを再セット
				$this->set("affiliation_info" , $this->AffiliationInfo->find('first', array('conditions' => array('AffiliationInfo.corp_id' => $id))));
// 2016.08.04 murata.s ORANGE-17 ADD(E)
			}

		}else{
			// 2016.02.16 ORANGE-1247 k.iwai ADD(S).
			$this->set("affiliation_info" , $this->AffiliationInfo->find('first', array('conditions' => array('AffiliationInfo.corp_id' => $id))));

			// 2016.02.16 ORANGE-1247 k.iwai ADD(E)
// 2016.07.28 murata.s ORANGE-52 ADD(S)
			// m_corp_new_yearsの取得
			// 取得方法はdetailとあわせる
			$appendFields = implode(',', array_map(function($value){
				return 'MCorpNewYear.'.$value;
			},array_keys($this->MCorpNewYear->schema())));
// 2016.07.28 murata.s ORANGE-52 ADD(E)
			// 企業情報取得
			$corp_data = $this->MCorp->find ( 'first', array (
				//'fields' => 'MCorp.id , MCorp.corp_name, MCorp.official_corp_name, MCorp.corp_name_kana',
// 2016.07.28 murata.s ORANGE-52 CHG(S)
// 				'fields' => '*',
				'fields' => '*, '.$appendFields,
// 2016.07.28 murata.s ORANGE-52 CHG(E)
// 2016.07.28 murata.s ORANGE-52 ADD(S)
				'joins' => array(
						array(
								'fields' => '*',
								'type' => 'left',
								'table' => 'm_corp_new_years',
								'alias' => 'MCorpNewYear',
								'conditions' => array(
										'MCorp.id = MCorpNewYear.corp_id'
								)
						)
				),
// 2016.07.28 murata.s ORANGE-52 ADD(E)
				'conditions' => array (
						'MCorp.id' => $id
				),
				'order' => array (
						'MCorp.id' => 'asc'
				),
			) );

			//ORANGE-83 iwai 2016.05.24 ADD(S)
			$responsibillity = explode(' ', $corp_data['MCorp']['responsibility']);
			if(!empty($responsibillity[0]))$corp_data['MCorp']['responsibility_sei'] = $responsibillity[0];
			if(!empty($responsibillity[1]))$corp_data['MCorp']['responsibility_mei'] = $responsibillity[1];
			// 2016.02.16 ORANGE-1247 k.iwai CHG(E).

// 2016.07.28 murata.s ORANGE-100 DEL(S)
			// 企業マスタ付帯情報の取得
			$this->__get_m_corp_subs_list ( $id );
// 2016.07.28 murata.s ORANGE-100 DEL(E)
		}

		// 企業情報セット
		$this->set ("corp_data", $corp_data );

// 2016.07.28 murata.s ORANGE-100 DEL(S)
// 画面初期表示のみ企業マスタ付帯情報を取得する
//		// 企業マスタ付帯情報の取得
//		$this->__get_m_corp_subs_list ( $id );
// 2016.07.28 murata.s ORANGE-100 DEL(E)

		if (isset ( $this->request->data ['regist'] )) { // 登録

			$error_chk_flg = false;
			// エラーチェック
			foreach ( $this->request->data['MCorpCategory'] as $v ) {
				// 専門性チェック
				if (isset($v['select_list']) && $v['select_list'] == "") {
					$error_chk_flg = true;
					$error_msg = "専門性を選択してください。";
					break;
				}
				if ($this->User['auth'] != 'affiliation') {
					// 単位チェック
					if(!empty($v['order_fee']) &&
						$v['order_fee_unit'] == ''){
						$error_chk_flg = true;
						$error_msg = "単位を選択してください。";
						break;
					}
				}
			}
			// エラー出力
			if ($error_chk_flg) {

				$this->Session->setFlash ( $error_msg, 'default', array (
						'class' => 'error_inner'
				) );

				// 企業マスタ付帯情報の取得
				$this->__get_m_corp_subs_list ( $id );

				$this->data = $this->request->data;
				$this->set ( "errorData" , $this->request->data );
				$this->set( "error" , true );

				// 都道府県リスト（全地域対応・一部地域対応・対応可能設定無し）
				$pref_list = array();
				foreach (Util::getDivList('prefecture_div') as $key => $val) {
					// 99は読み飛ばし
					if ($key == 99) {
						continue;
					}
					$obj = array();
					$obj['id'] = $key;
					$obj['name'] = $val;
					// 指定した都道府県の加盟店が設定したエリア数
					$corp_count = $this->MPost->getCorpPrefAreaCount($id, $val);
					if ($corp_count > 0) {
						// 指定した都道府県のエリア数
						$area_count = $this->MPost->getPrefAreaCount($val);
						if ($corp_count >= $area_count) {
							// 全地域対応
							$obj['rank'] = 2;
						} else {
							// 一部地域対応
							$obj['rank'] = 1;
						}
						$pref_list[] = $obj;
					}
				}
				$this->set("pref_list" , $pref_list);
				$this->set("id" , $id);
				return true;
			}

			if (self::__check_modified_category ( $this->request->data ['MCorpCategory'] [0] ['id'], $this->request->data ['MCorpCategory'] [0] ['modified'] )) {
				try {
					$this->MCorpCategory->begin ();
					// 企業別対応カテゴリマスタの登録
					$resultsFlg = $this->__edit_corp_category ( $id, $this->request->data );
					if ($resultsFlg) {
						$this->MCorpCategory->commit ();

						/*
						// 2015.02.24 初回のみ企業エリアマスタからエリアをセット
						$this->MTargetArea->begin();
						$resultsFlg = $this->__edit_target_area ( $id, $this->request->data );
						if ($resultsFlg) {
							$this->MTargetArea->commit ();
						*/
							//2015.05.29 y.fujimori ADD start
							if ($this->User['auth'] == 'affiliation') {
								$message = __('Updated', true);
								$this->Session->setFlash($message, 'default', array('class' => 'message_inner'));
								// 対応可能ジャンルリストの取得
								$results = $this->MCorpCategory->getMCorpCategoryGenreList($id);
								 $this->request->data ['MCorpCategory'] [0] ['modified'] = $results[0]['MCorpCategory']['modified'];

								// 都道府県リスト（全地域対応・一部地域対応・対応可能設定無し）
								$pref_list = array();
								foreach (Util::getDivList('prefecture_div') as $key => $val) {
									// 99は読み飛ばし
									if ($key == 99) {
										continue;
									}
									$obj = array();
									$obj['id'] = $key;
									$obj['name'] = $val;
									// 指定した都道府県の加盟店が設定したエリア数
									$corp_count = $this->MPost->getCorpPrefAreaCount($id, $val);
									if ($corp_count > 0) {
										// 指定した都道府県のエリア数
										$area_count = $this->MPost->getPrefAreaCount($val);
										if ($corp_count >= $area_count) {
											// 全地域対応
											$obj['rank'] = 2;
										} else {
											// 一部地域対応
											$obj['rank'] = 1;
										}
										$pref_list[] = $obj;
									}
								}
								$this->set("pref_list" , $pref_list);
								$this->set("id" , $id);

								// 2016.05.13 ota.r@tobila MOD START ORANGE-47 【加盟店管理】カテゴリ登録時、「入力項目を確認してください。 」となって登録できない
								$this->Session->setFlash ( __ ('Updated', true), 'default', array('class' => 'message_inner'));
								// 2016.05.13 ota.r@tobila MOD END ORANGE-47 【加盟店管理】カテゴリ登録時、「入力項目を確認してください。 」となって登録できない
								return true;
							} else {
								// 2016.05.13 ota.r@tobila MOD START ORANGE-47 【加盟店管理】カテゴリ登録時、「入力項目を確認してください。 」となって登録できない
//								return $this->redirect ( '/affiliation/detail/' . $id );
								$this->Session->setFlash ( __ ('Updated', true), 'default', array('class' => 'message_inner'));
								return true;
								// 2016.05.13 ota.r@tobila MOD END ORANGE-47 【加盟店管理】カテゴリ登録時、「入力項目を確認してください。 」となって登録できない
							}
							//2015.05.29 y.fujimori ADD end
						/*
						}
						*/
					}
					// $this->MTargetArea->rollback();
					$this->MCorpCategory->rollback ();

					//2015.06.30  y.fujimori ADD start
					$this->set ( "errorData" , $this->request->data );
					$this->set( "error" , true );
					//2015.06.30 y.fujimori ADD end

					// 都道府県リスト（全地域対応・一部地域対応・対応可能設定無し）
					$pref_list = array();
					foreach (Util::getDivList('prefecture_div') as $key => $val) {
						// 99は読み飛ばし
						if ($key == 99) {
							continue;
						}
						$obj = array();
						$obj['id'] = $key;
						$obj['name'] = $val;
						// 指定した都道府県の加盟店が設定したエリア数
						$corp_count = $this->MPost->getCorpPrefAreaCount($id, $val);
						if ($corp_count > 0) {
							// 指定した都道府県のエリア数
							$area_count = $this->MPost->getPrefAreaCount($val);
							if ($corp_count >= $area_count) {
								// 全地域対応
								$obj['rank'] = 2;
							} else {
								// 一部地域対応
								$obj['rank'] = 1;
							}
							$pref_list[] = $obj;
						}
					}
					$this->set("pref_list" , $pref_list);
					$this->set("id" , $id);

				} catch ( Exception $e ) {
					$this->MCorpCategory->rollback ();
				}
				$this->Session->setFlash ( __ ( 'InputError', true ), 'default', array (
						'class' => 'error_inner'
				) );
				$this->set ( "errorData" , $this->request->data );
				$this->set( "error" , true );

			} else {
				$this->Session->setFlash ( __ ( 'ModifiedNotCheck', true ), 'default', array (
						'class' => 'error_inner'
				) );
				$this->set ( "errorData" , $this->request->data );
				$this->set( "error" , true );

			}

			// 企業マスタ付帯情報の取得
			$this->__get_m_corp_subs_list ( $id );

			$this->data = $this->request->data;
			$this->set ( "errorData" , $this->request->data );
			$this->set( "error" , true );

			// 都道府県リスト（全地域対応・一部地域対応・対応可能設定無し）
			$pref_list = array();
			foreach (Util::getDivList('prefecture_div') as $key => $val) {
				// 99は読み飛ばし
				if ($key == 99) {
					continue;
				}
				$obj = array();
				$obj['id'] = $key;
				$obj['name'] = $val;
				// 指定した都道府県の加盟店が設定したエリア数
				$corp_count = $this->MPost->getCorpPrefAreaCount($id, $val);
				if ($corp_count > 0) {
					// 指定した都道府県のエリア数
					$area_count = $this->MPost->getPrefAreaCount($val);
					if ($corp_count >= $area_count) {
						// 全地域対応
						$obj['rank'] = 2;
					} else {
						// 一部地域対応
						$obj['rank'] = 1;
					}
					$pref_list[] = $obj;
				}
			}
			$this->set("pref_list" , $pref_list);
			$this->set("id" , $id);


		} else if (isset ( $this->request->data ['cancel'] )) { // キャンセル

			return $this->redirect ( '/affiliation/detail/' . $id );

		} else if (isset ($this->request->data['status_edit'])) { //取次方法の更新
			// 2017/05/30  ichino ORANGE-421 ADD
			//更新対象のカテゴリーID
			$update_id = Hash::get($this->request->data, "MCorpCategory.auction_status.update_id");
			if(!is_null(Hash::get($this->request->data, "MCorpCategory.{$update_id}.auction_status", NULL))){
				try {
					//保存データの整形
					$save_data = array(
							'id' => $update_id,
							'auction_status' => $this->request->data['MCorpCategory'][$update_id]['auction_status'],
					);
					$this->MCorpCategory->begin ();
					$this->MCorpCategory->save($save_data, array('atomic' => false));
					$this->MCorpCategory->commit();

					//更新メッセージ
					$message = __('Updated', true);
					$this->Session->setFlash($message, 'default', array('class' => 'message_inner'));
				} catch (Exception $e) {
					$this->log('m_corp_category カテゴリ別取次方法の更新エラー');
					$this->MCorpCategory->rollback ();
				}
			} else {
				//エラー
				$this->set("errorData", $this->request->data);
				$this->set("error", true);
				$this->Session->setFlash(__('InputError', true), 'default', array('class' => 'error_inner'));
			}

			//リダイレクト
			return $this->redirect ( '/affiliation/category/' . $id );
			//2017/05/30  ichino ORANGE-421 ADD
		} else {  // 初期表示
// 2016.07.28 murata.s ORANGE-100 DEL(S)
// すでに初期処理として企業マスタ付帯情報を取得しており、
// 入力エラーが発生した場合、変更前の情報に戻るため、ここでは処理しない
// 			// 企業マスタ付帯情報の取得
// 			$this->__get_m_corp_subs_list ( $id );
// 2016.07.28 murata.s ORANGE-100 DEL(E)

			// 都道府県リスト（全地域対応・一部地域対応・対応可能設定無し）
			$pref_list = array();
			foreach (Util::getDivList('prefecture_div') as $key => $val) {
				// 99は読み飛ばし
				if ($key == 99) {
					continue;
				}
				$obj = array();
				$obj['id'] = $key;
				$obj['name'] = $val;
				// 指定した都道府県の加盟店が設定したエリア数
				$corp_count = $this->MPost->getCorpPrefAreaCount($id, $val);
				if ($corp_count > 0) {
					// 指定した都道府県のエリア数
					$area_count = $this->MPost->getPrefAreaCount($val);
					if ($corp_count >= $area_count) {
						// 全地域対応
						$obj['rank'] = 2;
					} else {
						// 一部地域対応
						$obj['rank'] = 1;
					}
					$pref_list[] = $obj;
				}
			}
			$this->set("pref_list" , $pref_list);
			$this->set("id" , $id);


		}
	}

	// 2015.08.27 s.harada ADD start 画面デザイン変更対応
	/**
	 * ジャンル選択
	 *
	 * @param string $id
	 */
	public function genre($id = null) {

		$resultsFlg = true;
		if (isset ( $this->request->data ['regist-genre'] )) { // 登録
			/*
			if (self::__check_modified_category ( $this->request->data ['MCorpCategory'] [0] ['id'], $this->request->data ['MCorpCategory'] [0] ['modified'] )) {

			} else {
				$this->Session->setFlash ( __ ( 'ModifiedNotCheck', true ), 'default', array (
						'class' => 'error_inner'
				) );
			}
			*/
			unset($this->request->data ['regist-genre']);
			foreach ($this->request->data['MCorpCategory'] as $category) {
				if (isset($category['category_id']) && empty($category['select_list'])) {
					$id_array = split('-', $category['category_id']);
					$genre_id = $id_array[0];
					$MGenreData = $this->MGenre->findById($genre_id);
					// 紹介ベースジャンルは専門性必須チェックしない
					if ($MGenreData["MGenre"]['commission_type'] != 2) {
						// 専門性必須チェック
						$this->Session->setFlash ( __ ( 'NotSelectList', true ), 'default', array (
						'class' => 'error_inner'
						) );
						$resultsFlg = false;
					}
				} else if (!isset($category['category_id']) && !empty($category['select_list'])) {
					// 専門性必須チェック
					$this->Session->setFlash ( __ ( 'NotSelectCorrespondCheck', true ), 'default', array (
							'class' => 'error_inner'
					) );
					$resultsFlg = false;
				}
			}
			if ($resultsFlg) {
				try {
					$this->MCorpCategory->begin ();
					// 企業別対応カテゴリマスタの登録
					$del_array = array();
					$resultsFlg = $this->__edit_corp_category_genre( $id, $this->request->data, $del_array );

					if ($resultsFlg) {
						$this->MCorpCategory->commit ();
						// 2015.02.24 初回のみ企業エリアマスタからエリアをセット
						$this->MTargetArea->begin();
						$resultsFlg = $this->__edit_target_area_genre( $id, $this->request->data ,$del_array);
						if ($resultsFlg) {
							$this->MTargetArea->commit ();
							//$this->Session->setFlash(__('Updated', true), 'default', array('class' => 'message_inner'));
							//return $this->redirect ( '/affiliation/category/' . $id );
							// 初回のみ企業別エリア別ジャンルデータを生成する
							$this->AffiliationAreaStat->begin();
							$resultsFlg = $this->__create_affiliation_area_stats_data( $id );
							if ($resultsFlg) {
								$this->AffiliationAreaStat->commit ();
								$this->Session->setFlash(__('Updated', true), 'default', array('class' => 'message_inner'));
							} else {
								$this->AffiliationAreaStat->rollback();
								$this->set ( "errorData" , $this->request->data );
								$this->set( "error" , true );
							}
						} else {
							$this->MTargetArea->rollback();
							$this->set ( "errorData" , $this->request->data );
							$this->set( "error" , true );
						}
					} else {
						$this->MCorpCategory->rollback ();
						$this->set ( "errorData" , $this->request->data );
						$this->set( "error" , true );
					}
				} catch ( Exception $e ) {
					$this->MCorpCategory->rollback ();
				}
				/*
				 $this->Session->setFlash ( __ ( 'InputError', true ), 'default', array (
				 'class' => 'error_inner'
				 ) );
				 */
			}
		} else if (isset ( $this->request->data ['cancel-genre'] )) { // キャンセル

			return $this->redirect ( '/affiliation/category/' . $id );

		}

		// 変数初期化
		$work_category = array ();
		$all_category     = array ();
		// ALLカテゴリ 検索する
		$work_category = $this->MCategory->getAllList($id);

		if (!$resultsFlg) {
			// エラー再表示の場合
			foreach ( $work_category as $key => $v ) {
				foreach ( $this->request->data['MCorpCategory'] as $request ) {
					$id_array = split('-', $request['table_disc_id']);
					$genre_id    = $id_array[0];
					$category_id = $id_array[1];
					if ($genre_id == $v['MGenre']['id'] && $category_id == $v['MCategory']['id']) {
						if (isset($request['category_id'])) {
							$v['check'] = "checked";
						} else {
							$v['check'] = "";
						}
						if (isset($request['select_list'])) {
							$v['MCorpCategory']['select_list'] = $request['select_list'];
						} else {
							$v['MCorpCategory']['select_list'] = "";
						}
						break;
					}
				}
				$all_category[] = $v;
			}
		} else {
			// 初期表示
			foreach ( $work_category as $v ) {
				if (isset($v['MCorpCategory']['id'])) {
					$v['check'] = "checked";
				} else {
					$v['check'] = "";
				}
				$all_category[] = $v;
			}
		}
		$this->set ( "all_category", $all_category );

		// 企業情報取得
		$corp_data = $this->MCorp->find ( 'first', array (
				//'fields' => 'MCorp.id , MCorp.corp_name, MCorp.official_corp_name, MCorp.corp_name_kana',
				'fields' => '*',
				'conditions' => array (
						'MCorp.id' => $id
				),
				'order' => array (
						'MCorp.id' => 'asc'
				)
		) );
		// 企業情報セット
		$this->set ("corp_data", $corp_data );

		// 登録斡旋ジャンルを検索する
		$mediation_genre = $this->MGenre->getMediationList();
		$this->set ( "mediation_genre", $mediation_genre );
	}

	/**
	 * 基本対応エリアの設定
	 *
	 * @param string $id
	 */
	//ORANGE-347 CHG S
	public function corptargetarea($id, $step = null, $param1 = null, $param2 = null) {
	//ORANGE-347 CHG E

		if ($id == null) {
			throw new Exception();
		}
// 2016.10.05 murata.s ADD(S) 脆弱性 SQLインジェクション対応
		if(!ctype_digit($id))
			throw new ApplicationException(__('NoReferenceAuthority', true));
// 2016.10.05 murata.s ADD(E) 脆弱性 SQLインジェクション対応

		$this->edit_area($id, $step, $param1, $param2);
		return;
		/*
		 * ORANGE-347 DELETE
		
		$resultsFlg = true;

		if (isset($this->request->data['regist'])){

			// データを削除・登録処理
			try {
				$this->MCorpTargetArea->begin();
				// 企業別対応カテゴリマスタの登録
				$resultsFlg = $this->__edit_target_area2($id , $this->request->data);

				if($resultsFlg){
					//$this->Session->setFlash(__('Updated', true), 'default', array('class' => 'message_inner'));
					$this->MCorpTargetArea->commit();

					// 基本対応エリアの登録後にエリア未登録ジャンルへ基本対応エリアをエリア登録
					$this->MTargetArea->begin();
					$resultsFlg = $this->__edit_target_area_to_genre($id);
					if ($resultsFlg) {
						$this->MTargetArea->commit ();
						$this->Session->setFlash(__('Updated', true), 'default', array('class' => 'message_inner'));
						//return $this->redirect ( '/affiliation/category/' . $id );
					} else {
						$this->MTargetArea->rollback();
						$this->set ( "errorData" , $this->request->data );
						$this->set( "error" , true );
					}
				} else {
					$this->MCorpTargetArea->rollback();
					$this->Session->setFlash(__('InputError', true), 'default', array('class' => 'error_inner'));
				}
			} catch (Exception $e) {
				$this->MCorpTargetArea->rollback();
				$this->Session->setFlash(__('InputError', true), 'default', array('class' => 'error_inner'));
			}
			// エリアのリストを取得
			//$conditions['MPost.address1'] = $this->request->data['address1_text'];
			//$list = $this->__get_target_area_list($id , $conditions);

			//$this->data = $this->request->data;
			//$this->set("list" , $list);
		} else if (isset($this->request->data['regist-base-update'])){

			// データを削除・登録処理
			try {
				$this->MTargetArea->begin();
				//
				if (!empty($id) && !empty($this->request->data['genre_id'])) {

// 2016.10.05 murata.s ADD(S) 脆弱性 SQLインジェクション対応
					foreach ($this->request->data['genre_id'] as $val) {
						if(!ctype_digit($val)) throw new ApplicationException(__('NoReferenceAuthority', true));
					}
// 2016.10.05 murata.s ADD(E) 脆弱性 SQLインジェクション対応

					foreach ($this->request->data['genre_id'] as $key => $val) {
						$resultsFlg = $this->__edit_target_area_to_category($id, $val);
						// 更新エラーの場合は中断してエラーメッセージを表示
						if (!$resultsFlg) {
							break;
						}
					}
				} else {
					$resultsFlg = false;
				}
				if($resultsFlg){
					$this->Session->setFlash(__('Updated', true), 'default', array('class' => 'message_inner'));
					$this->MTargetArea->commit();
				} else {
					$this->MTargetArea->rollback();
					$this->Session->setFlash(__('InputError', true), 'default', array('class' => 'error_inner'));
				}
			} catch (Exception $e) {
				$this->MTargetArea->rollback();
				$this->Session->setFlash(__('InputError', true), 'default', array('class' => 'error_inner'));
			}
		} else if (isset($this->request->data['all_regist'])){

			// データを削除・登録処理
			try {
				$this->MCorpTargetArea->begin();

				// 企業別対応カテゴリマスタの登録
				$resultsFlg = $this->__all_regist_target_area($id);

				// 企業別対応カテゴリマスタの登録
				if($resultsFlg){
					$this->Session->setFlash(__('Updated', true), 'default', array('class' => 'message_inner'));
					$this->MCorpTargetArea->commit();
				} else {
					$this->MCorpTargetArea->rollback();
					$this->Session->setFlash(__('InputError', true), 'default', array('class' => 'error_inner'));
				}
			} catch (Exception $e) {
				$this->MCorpTargetArea->rollback();
				$this->Session->setFlash(__('InputError', true), 'default', array('class' => 'error_inner'));
			}

			// 2015.6.19 y.fujimori ADD start ORANGE-622
		} else if (isset($this->request->data['all_remove'])){
			// データを削除・登録処理
			try {
				$this->MCorpTargetArea->begin();

				// 企業別対応カテゴリマスタの登録
				$resultsFlg = $this->__all_remove_target_area($id);

				// 企業別対応カテゴリマスタの登録
				if($resultsFlg){
					$this->Session->setFlash(__('Updated', true), 'default', array('class' => 'message_inner'));
					$this->MCorpTargetArea->commit();
				} else {
					$this->MCorpTargetArea->rollback();
					$this->Session->setFlash(__('InputError', true), 'default', array('class' => 'error_inner'));
				}
			} catch (Exception $e) {
				$this->MCorpTargetArea->rollback();
				$this->Session->setFlash(__('InputError', true), 'default', array('class' => 'error_inner'));
			}
		}
		// 2015.6.19 y.fujimori ADD end ORANGE-622

		// 都道府県リスト（全地域対応・一部地域対応・対応可能設定無し）
		$pref_list = array();
		foreach (Util::getDivList('prefecture_div') as $key => $val) {
			// 99は読み飛ばし
			if ($key == 99) {
				continue;
			}
			$obj = array();
			$obj['id'] = $key;
			$obj['name'] = $val;
			// 指定した都道府県の加盟店が設定したエリア数
			$corp_count = $this->MPost->getCorpPrefAreaCount($id, $val);
			if ($corp_count > 0) {
				// 指定した都道府県のエリア数
				$area_count = $this->MPost->getPrefAreaCount($val);
				if ($corp_count >= $area_count) {
					// 全地域対応
					$obj['rank'] = 2;
				} else {
					// 一部地域対応
					$obj['rank'] = 1;
				}
			} else {
				// 対応可能設定無し
				$obj['rank'] = 0;
			}
			$pref_list[] = $obj;
		}
		$this->set("pref_list" , $pref_list);

		// 企業エリアマスターの取得
		$corp_areas = $this->MCorpTargetArea->find('all',
			array(
				'fields' => '*',
				'conditions' => array(
						'corp_id = ' . $id
				)
			)
		);
		// 基本対応エリアの設定エリア数
		$corp_target_area_count = $this->MCorpTargetArea->getCorpTargetAreaCount($id);
		// 加盟店選択済みジャンルリスト
		$select_genre_list = $this->MCorpCategory->getCorpSelectGenreList($id);
		// [MCorpCategory][id]の取得
		$genre_custom_area_list = array();
		$genre_normal_area_list = array();
		foreach ($select_genre_list as $key => $val) {
			// 基本対応エリアのままのジャンルとカスタマイズしているジャンルの区分け
			$list_data = $this->MCorpCategory->getMCorpCategoryIDList($id, $val['MGenre']['id']);
			$custom_flg = false;
			$mstedt_flg = false;
			foreach ($list_data as $cg_val) {
				// 「target_area_type（対応可能エリアタイプ）」が未設定
				if ($cg_val['MCorpCategory']['target_area_type'] == 0 || isset($this->request->data['regist']) || isset($this->request->data['regist-base-update'])) {
					$mstedt_flg = true;
					$target_area_count = $this->MTargetArea->getCorpCategoryTargetAreaCount($cg_val['MCorpCategory']['id']);
					if ($target_area_count != $corp_target_area_count) {
						$custom_flg = true;
						break;
					}
					foreach ($corp_areas as $area_v) {
						$area_count = $this->MTargetArea->getCorpCategoryTargetAreaCount2($cg_val['MCorpCategory']['id'], $area_v['MCorpTargetArea']['jis_cd']);
						if ($area_count <= 0) {
							$custom_flg = true;
							break;
						}
					}
					if ($custom_flg == true) {
						break;
					}
				} else if ($cg_val['MCorpCategory']['target_area_type'] == 1) {
					// 対応可能エリアが基本対応エリアと同じ
					break;
				} else if ($cg_val['MCorpCategory']['target_area_type'] == 2) {
					// 対応可能エリアが基本対応エリアと異なる
					$custom_flg = true;
					break;
				}
			}
			//
			$obj = array();
			$obj['genre_name'] = $val['MGenre']['genre_name'];
			$obj['genre_id'] = $val['MGenre']['id'];
			$row_data = $this->MCorpCategory->getMCorpCategoryID($id, $val['MGenre']['id']);
			$obj['id'] = $row_data['MCorpCategory']['id'];

			if ($custom_flg == true) {
				// 対応エリアをカスタマイズしているジャンルリスト
				$genre_custom_area_list[] = $obj;

				if ($mstedt_flg) {
					// 「m_corp_categories」の「target_area_type（対応可能エリアタイプ）」を更新
					foreach ($list_data as $cg_val) {
						self::__edit_corp_category_target_area_type($cg_val['MCorpCategory']['id'], 2);
					}
				}
			} else {
				// 対応エリアが基本対応エリアのままのジャンルリスト
				$genre_normal_area_list[] = $obj;

				if ($mstedt_flg) {
					// 「m_corp_categories」の「target_area_type（対応可能エリアタイプ）」を更新
					foreach ($list_data as $cg_val) {
						self::__edit_corp_category_target_area_type($cg_val['MCorpCategory']['id'], 1);
					}
				}
			}
		}
		$this->set("genre_custom_area_list" , $genre_custom_area_list);
		$this->set("genre_normal_area_list" , $genre_normal_area_list);

		$this->set("init_pref" , $init_pref);
		// 最終更新日の取得
		$last_modified = $this->MCorpTargetArea->getCorpTargetAreaLastModified($id);
		$this->set("last_modified" , $last_modified);
		$this->set("id" , $id);
		 */
	}

	/**
	 * ジャンル別対応エリアの設定表示（一覧）
	 *
	 * @param string $id  MCorpCategoryのID
	 * @throws Exception
	 */
	public function targetarea($id = null) {

		if ($id == null) {
			throw new Exception();
		}
// 2016.10.05 murata.s ADD(S) 脆弱性 SQLインジェクション対応
		if(!ctype_digit($id))
			throw new ApplicationException(__('NoReferenceAuthority', true));
// 2016.10.05 murata.s ADD(E) 脆弱性 SQLインジェクション対応

		if (isset($this->request->data['regist'])){
// 2016.10.05 murata.s ADD(S) 脆弱性 SQLインジェクション対応
			if(!ctype_digit($this->request->data['corp_id']))
				throw new ApplicationException(__('NoReferenceAuthority', true));
// 2016.10.05 murata.s ADD(E) 脆弱性 SQLインジェクション対応
			// データを削除・登録処理
			if ($this->_registTargetarea($id)) {
				$this->Session->setFlash(__('Updated', true), 'default', array('class' => 'message_inner'));
			} else {
				$this->Session->setFlash(__('InputError', true), 'default', array('class' => 'error_inner'));
			}

		} else if (isset($this->request->data['all_regist'])){

			$resultsFlg = true;
			// データを削除・登録処理
			try {
				$this->MTargetArea->begin();

				// 企業別対応カテゴリマスタの登録
				$resultsFlg = $this->__all_regist_target_area2($id);

				// 企業別対応カテゴリマスタの登録
				if($resultsFlg){
					$this->Session->setFlash(__('Updated', true), 'default', array('class' => 'message_inner'));
					$this->MTargetArea->commit();
				} else {
					$this->MTargetArea->rollback();
					$this->Session->setFlash(__('InputError', true), 'default', array('class' => 'error_inner'));
				}
			} catch (Exception $e) {
				$this->MTargetArea->rollback();
				$this->Session->setFlash(__('InputError', true), 'default', array('class' => 'error_inner'));
			}
		}

		// 都道府県リスト（全地域対応・一部地域対応・対応可能設定無し）
		$pref_list = array();
		foreach (Util::getDivList('prefecture_div') as $key => $val) {
			// 99は読み飛ばし
			if ($key == 99) {
				continue;
			}
			$obj = array();
			$obj['id'] = $key;
			$obj['name'] = $val;
			// 指定した都道府県の加盟店が設定したエリア数
			$corp_count = $this->MPost->getCorpCategoryAreaCount($id, $val);
			if ($corp_count > 0) {
				// 指定した都道府県のエリア数
				$area_count = $this->MPost->getPrefAreaCount($val);
				if ($corp_count >= $area_count) {
					// 全地域対応
					$obj['rank'] = 2;
				} else {
					// 一部地域対応
					$obj['rank'] = 1;
				}
			} else {
				// 対応可能設定無し
				$obj['rank'] = 0;
			}
			$pref_list[] = $obj;
		}
		$this->set("pref_list" , $pref_list);

		// 「MCorpCategory」のIDから会社IDとジャンルIDの取得
		$data = $this->MCorpCategory->getWithGenreAndCategory($id);
		$this->set("data" , $data);
		// 最終更新日の取得
		$last_modified = $this->MTargetArea->getTargetAreaLastModified($id);
		$this->set("last_modified" , $last_modified);
		// 「MCorpCategory」のIDをセット
		$this->set("id" , $id);

	}

	// 2015.08.27 s.harada ADD end 画面デザイン変更対応
	/**
	 * ジャンル別対応エリアの登録
	 *
	 * @param  string $id  MCorpCategoryのID
	 * @return bool
	 */
	protected function _registTargetarea($id) {
		// 指定の都道府県のjis_cdの範囲を取得
		$conditions_results = $this->MPost->find('first',
			array(
				'fields' => 'max(MPost.jis_cd) , min(MPost.jis_cd)',
				'conditions' => array(
					'MPost.address1' => $this->request->data['address1_text']
				),
			)
		);

		if (empty($conditions_results)) {
			return false;
		}

		$this->MTargetArea->begin();
		// 過去データを削除
		$this->MTargetArea->deleteAll(array(
			'MTargetArea.corp_category_id' => $id,
			'MTargetArea.jis_cd >=' => $conditions_results[0]['min'],
			'MTargetArea.jis_cd <=' => $conditions_results[0]['max'],
		), false);

		if (!empty($this->request->data['jis_cd'])) {
			// 対応可能地域の登録用配列を作成
			$save_data = array();
			foreach ($this->request->data['jis_cd'] as $val) {
				$save_data[] = array(
					'MTargetArea' => array(
						'corp_category_id' => $id,
						'jis_cd' => $val,
						'address1_cd' => substr($val, 0, 2),
					)
				);
			}
			// 対応可能地域を登録
			if (!$this->MTargetArea->saveAll($save_data)) {
				$this->MTargetArea->rollback();

				return false;
			}
		}

		// 対応エリアのタイプ（基本エリアと差異があるか）を更新
		$this->_updateCorpCategoryTargetAreaType($id);

		$this->MTargetArea->commit();

		return true;
	}

	/**
	 * 対応エリアのタイプを更新
	 *
	 * @param  string $id  MCorpCategoryのID
	 * @return void
	 */
	protected function _updateCorpCategoryTargetAreaType($id) {
		// 基本対応エリアのJISコードを全て取得
// 2016.10.05 murata.s CHG(S) 脆弱性 SQLインジェクション対応
// 		$defaultJisCds = $this->MCorpTargetArea->find('list',
// 			array(
// 				'fields' => 'jis_cd',
// 				'conditions' => array(
// 					'corp_id = ' . $this->request->data['corp_id']
// 				)
// 			)
// 		);
		$defaultJisCds = $this->MCorpTargetArea->find('list',
				array(
						'fields' => 'jis_cd',
						'conditions' => array(
								'corp_id' => $this->request->data['corp_id']
						)
				)
		);
// 2016.10.05 murata.s CHG(S) 脆弱性 SQLインジェクション対応

		// 基本対応エリアと実対応エリアを比較して、対応エリアタイプを設定
		$defaultCount = count($defaultJisCds);
		$targetAreaCount = $this->MTargetArea->getCorpCategoryTargetAreaCount($id);

		if ($defaultCount === $targetAreaCount) {
			// 基本対応エリアと実対応エリアの全てのJISコードを比較し、
			// 一致する場合のみ基本対応エリアタイプを設定
			$countHasDefault = $this->MTargetArea->countHasJisCdsOfCorpCategory($id, $defaultJisCds);
			if ($defaultCount === $countHasDefault) {
				return $this->__edit_corp_category_target_area_type($id, 1);
			}
		}

		return $this->__edit_corp_category_target_area_type($id, 2);
	}

	/**
	 * キャンセル
	 */
	public function cancel() {

		$return_data = $this->Session->read ( self::$__sessionKeyForAffiliationReturn );

		if(empty($return_data)){

			$data = $this->Session->read ( self::$__sessionKeyForAffiliationParameter );
			$para_data = '';
			if (isset ( $data ['page'] )) {
				$para_data .= '/page:' . $data ['page'];
			}
			if (isset ( $data ['sort'] )) {
				$para_data .= '/sort:' . $data ['sort'];
			}
			if (isset ( $data ['direction'] )) {
				$para_data .= '/direction:' . $data ['direction'];
			}
			$url = '/affiliation/search' . $para_data;

		} else {
			$url = '/'. $return_data['url'];
		}

		return $this->redirect ( $url );
		return;
	}

	/**
	 * 対応履歴編集画面
	 *
	 * @param string $id
	 */
	public function history_input($id = null) {
		$error = array ();
		// 取次先対応履歴データの検索条件
		$conditions = array (
				'AffiliationCorrespond.id' => $id
		);
		// 取次先対応履歴データの取得
		$history_data = $this->__get_affiliation_correspond ( $conditions );

		if (isset ( $this->request->data ['edit'] )) { // 編集
		                                          // 更新日付のチェック
			if (self::__check_modified_affiliation_correspond ( $id, $this->request->data ['modified'] )) {
				try {
					$this->AffiliationCorrespond->begin ();
					// 対応履歴の編集
					if (self::__edit_history ( $id, $this->request->data )) {
						$this->set ( "end", $id ); // ID
						$this->AffiliationCorrespond->commit ();
					} else {
						$this->AffiliationCorrespond->rollback ();
					}
				} catch ( Exception $e ) {
					$this->AffiliationCorrespond->rollback ();
				}
			} else {
				$this->data = $this->request->data;
				$error ['modified'] = __ ( 'ModifiedNotCheck', true );
			}
		} else {
			$this->data = $history_data;
		}

		$this->layout = 'subwin';
		$this->set ( "error", $error );
	}



	/**
	 * 契約約款内容編集画面
	 *
	 * @param string $id
	 */
	public function agreement($id, $agreement_id = null) {
	    $resultsFlg = true;
	    $this->set( "error" , false );
	    if (empty($id)) {
	      throw new Exception();
	    }
	    $corp_data = array();
	    $corp_agreement = array();
	    $agreement_attached_file_cert = array();
		// 2017/10/18 ORANGE-541 m-kawamoto DEL(S)
	    //$agreement_attached_file_license = array();
		// 2017/10/18 ORANGE-541 m-kawamoto DEL(E)
	    $agreement_provisions = array();
	    $agreement_edit_history = array();
	    // 企業情報を取得 $corp_data
			$corp_data = $this->MCorp->find ( 'first', array (
	        'fields' => '*',
	        'conditions' => array (
	            'MCorp.id' => $id
	        ),
	        'order' => array (
	            'MCorp.id' => 'asc'
	        )
	      ) );

	    //2016.4.6 ORANGE-1341 iwai ADD(S)
		$corpAgreementCnt = $this->CorpAgreement->find ('count',
			array (
				'fields' => 'id',
				'conditions' => array (
						'CorpAgreement.corp_id' => $id,
						"CorpAgreement.status != 'Complete' AND CorpAgreement.status != 'Application'"
					)
		));

		//2016.4.6 ORANGE-1341 iwai ADD(E)

		// 企業契約状態を更新
		if(isset($this->request->data['update-corp-agreement']) ){
			try{
				//2016.4.6 ORANGE-1341 iwai CHG(S)
				if(!empty($agreement_id)){
					$corp_agreement = $this->CorpAgreement->find ( 'first', array (
						'fields' => '*',
						'conditions' => array (
								'CorpAgreement.corp_id' => $id,
								'CorpAgreement.id' => $agreement_id,
						)
					));
				}else{
					throw new Exception();
				}
				//2016.4.6 ORANGE-1341 iwai CHG(E)

				// 2016.05.24 murata.s ORANGE-75 ADD(S)
				$this->MCorp->begin();
				if(isset($this->request->data['MCorp']['commission_accept_flg'])){
					if($corp_data['MCorp']['commission_accept_flg'] != $this->request->data['MCorp']['commission_accept_flg']){
						$corp_data['MCorp']['commission_accept_flg'] = $this->request->data['MCorp']['commission_accept_flg'];
						$corp_data['MCorp']['commission_accept_date'] = date('Y-m-d H:i:s');
						$corp_data['MCorp']['commission_accept_user_id'] = $this->User['user_id'];
// ORANGE-199 CHG S
						//$resultsFlg = self::__edit_commission_accept($corp_data);
// ORANGE-199 CHG E
					}
				}
				// 2016.05.24 murata.s ORANGE-75 ADD(E)

				// 2016.05.24 murata.s ORANGE-75 CHG(S)
				if($resultsFlg){
					if(!isset($corp_agreement['CorpAgreement'])){
						// 作成
						$this->CorpAgreement->begin ();
						$corp_agreement['CorpAgreement']['corp_id'] = $id;
						$corp_agreement['CorpAgreement']['status'] = 'NotSigned';

						if(isset($this->request->data['CorpAgreement']['agreement_date']))
							$corp_agreement['CorpAgreement']['agreement_date'] = $this->request->data['CorpAgreement']['agreement_date'];

						if(isset($this->request->data['CorpAgreement']['agreement_flag']))
							$corp_agreement['CorpAgreement']['agreement_flag'] = $this->request->data['CorpAgreement']['agreement_flag'] ? true : false;

						if(isset($this->request->data['CorpAgreement']['corp_kind']))
							$corp_agreement['CorpAgreement']['corp_kind'] = $this->request->data['CorpAgreement']['corp_kind'];

						if(isset($this->request->data['CorpAgreement']['hansha_check'])) {
							$corp_agreement['CorpAgreement']['hansha_check'] = $this->request->data['CorpAgreement']['hansha_check'];
							$corp_agreement['CorpAgreement']['hansha_check_user_id'] = $this->User['user_id'];
							$corp_agreement['CorpAgreement']['hansha_check_date'] = date('Y-m-d H:i:s');
						}

						if(isset($this->request->data['CorpAgreement']['transactions_law'])
								&& $this->request->data['CorpAgreement']['transactions_law'] == 1) {
							$corp_agreement['CorpAgreement']['transactions_law_user_id'] = $this->User['user_id'];
							$corp_agreement['CorpAgreement']['transactions_law_date'] = date('Y-m-d H:i:s');
						}

						if(isset($this->request->data['CorpAgreement']['acceptation'])
								&& $this->request->data['CorpAgreement']['acceptation'] == 1) {
							// 2016.04.06 ORANGE-1347 k.iwai CHG(S)
							// 2016.02.15 ORANGE-1318 k.iwai CHG(S)
							if(!empty($corp_agreement['CorpAgreement']['status']) && $corp_agreement['CorpAgreement']['status'] == "Application"){
								$corp_agreement['CorpAgreement']['status'] = 'Complete';
								$corp_agreement['CorpAgreement']['acceptation_user_id'] = $this->User['user_id'];
								$corp_agreement['CorpAgreement']['acceptation_date'] = date('Y-m-d H:i:s');
							}
							// 2016.02.15 ORANGE-1318 k.iwai CHG(E)
							// 2016.04.06 ORANGE-1347 k.iwai CHG(E)
						}
// ORANGE-199 CHG S
// 2017.03.02 izumi.s ORANGE-346 DEL(S)
//						// 反社チェック日時が存在する
//						if(!empty($corp_agreement['CorpAgreement']['hansha_check_date'])){
//							if(!empty($corp_data['MCorp']['last_antisocial_check_date'])){
//								//反社チェック日時が最終販社チェック日時より大きい
//								if(strtotime($corp_agreement['CorpAgreement']['hansha_check_date']) > strtotime($corp_data['MCorp']['last_antisocial_check_date'])){
//									$corp_data['MCorp']['last_antisocial_check'] = $corp_agreement['CorpAgreement']['hansha_check'];
//									$corp_data['MCorp']['last_antisocial_check_user_id'] = $corp_agreement['CorpAgreement']['hansha_check_user_id'];
//									$corp_data['MCorp']['last_antisocial_check_date'] = $corp_agreement['CorpAgreement']['hansha_check_date'];
//								}
//							}else{
//								$corp_data['MCorp']['last_antisocial_check'] = $corp_agreement['CorpAgreement']['hansha_check'];
//								$corp_data['MCorp']['last_antisocial_check_user_id'] = $corp_agreement['CorpAgreement']['hansha_check_user_id'];
//								$corp_data['MCorp']['last_antisocial_check_date'] = $corp_agreement['CorpAgreement']['hansha_check_date'];
//							}
//						}
// 2017.03.02 izumi.s ORANGE-346 DEL(E)
// 2016.06.23 murata.s ORANGE-102 CHG(S)
// 						if(self::__create_corp_agreement($corp_agreement)){
						if(self::__create_corp_agreement($corp_agreement)
								&& self::__update_original_category_data($corp_agreement)
								&& self::__edit_commission_accept($corp_data)
						){
// 2016.06.23 murata.s ORANGE-102 CHG(E)
// ORANGE-199 CHG E
							$this->MCorp->commit();
							$this->CorpAgreement->commit ();
							$this->Session->setFlash ( __ ( 'Updated', true ), 'default', array (
									'class' => 'message_inner'
							));
						}else{
							$this->log('corp_agreement 会社毎の契約新規作成エラー');
							$this->CorpAgreement->rollback ();
							$this->MCorp->rollback();
							$this->Session->setFlash ( __ ( 'NotEmptyCorpAgreement', true ), 'default', array (
									'class' => 'error_inner'
							));
						}
					}else{
						// 更新
						$this->CorpAgreement->begin ();

						if(isset($this->request->data['CorpAgreement']['agreement_date']))
							$corp_agreement['CorpAgreement']['agreement_date'] = $this->request->data['CorpAgreement']['agreement_date'];

						if(isset($this->request->data['CorpAgreement']['agreement_flag']))
							$corp_agreement['CorpAgreement']['agreement_flag'] = $this->request->data['CorpAgreement']['agreement_flag'] ? true : false;

						if(isset($this->request->data['CorpAgreement']['corp_kind']))
							$corp_agreement['CorpAgreement']['corp_kind'] = $this->request->data['CorpAgreement']['corp_kind'];

						if(isset($this->request->data['CorpAgreement']['hansha_check'])) {
							if (!isset($corp_agreement['CorpAgreement']['hansha_check']) ||
								(isset($corp_agreement['CorpAgreement']['hansha_check'])
									&& $corp_agreement['CorpAgreement']['hansha_check']
									!= $this->request->data['CorpAgreement']['hansha_check'])
							) {
								$corp_agreement['CorpAgreement']['hansha_check'] = $this->request->data['CorpAgreement']['hansha_check'];
								$corp_agreement['CorpAgreement']['hansha_check_user_id'] = $this->User['user_id'];
								$corp_agreement['CorpAgreement']['hansha_check_date'] = date('Y-m-d H:i:s');
							}
						}

						if(isset($this->request->data['CorpAgreement']['transactions_law'])
								&& $this->request->data['CorpAgreement']['transactions_law'] == 1) {
							if (!isset($corp_agreement['CorpAgreement']['transactions_law_date'])) {
								$corp_agreement['CorpAgreement']['transactions_law_user_id'] = $this->User['user_id'];
								$corp_agreement['CorpAgreement']['transactions_law_date'] = date('Y-m-d H:i:s');
							}
						} else {
							$corp_agreement['CorpAgreement']['transactions_law_user_id'] = NULL;
							$corp_agreement['CorpAgreement']['transactions_law_date'] = NULL;
						}

						// 2016.02.15 ORANGE-1318 k.iwai CHG(S)
						if(isset($this->request->data['CorpAgreement']['acceptation'])
								&& $this->request->data['CorpAgreement']['acceptation'] == 1) {
							if (!isset($corp_agreement['CorpAgreement']['acceptation_date'])) {
								$corp_agreement['CorpAgreement']['acceptation_user_id'] = $this->User['user_id'];
								$corp_agreement['CorpAgreement']['acceptation_date'] = date('Y-m-d H:i:s');
							}

							if(!empty($corp_agreement['CorpAgreement']['status']) && $corp_agreement['CorpAgreement']['status'] == "Application"){
								$corp_agreement['CorpAgreement']['status'] = 'Complete';
							}
							// 2016.09.01 murata.s ORANGE-174 ADD(S)
							if(!empty($corp_agreement['CorpAgreement']['kind']) && $corp_agreement['CorpAgreement']['kind'] == 'FAX'){
								$corp_agreement['CorpAgreement']['status'] = 'Complete';
							}
							// 2016.09.01 murata.s ORANGE-174 ADD(E)
						} else {
							// ORANGE-199 CHG S
							// ORANGE-131 iwai 2016.07.19 ADD S
							if(!empty($this->request->data['agreement_remand_flg']) && $this->request->data['agreement_remand_flg']){
								$corp_agreement['CorpAgreement']['status'] = 'Reconfirmation';
							}
							// ORANGE-131 iwai 2016.07.19 ADD E
							// ORANGE-199 CHG E

							$corp_agreement['CorpAgreement']['acceptation_user_id'] = NULL;
							$corp_agreement['CorpAgreement']['acceptation_date'] = NULL;
						}

						//if (isset($corp_agreement['CorpAgreement']['status']) &&
						//		$corp_agreement['CorpAgreement']['status'] == 'Application') {
						//	$corp_agreement['CorpAgreement']['status'] = 'Complete';
						//}
						// 2016.02.15 ORANGE-1318 k.iwai CHG(E)
// ORANGE-199 CHG S
// 2017.03.06 izumi.s ORANGE-346 DEL(S)
//						// 反社チェック日時が存在する
//						if(!empty($corp_agreement['CorpAgreement']['hansha_check_date'])){
//							if(!empty($corp_data['MCorp']['last_antisocial_check_date'])){
//								//反社チェック日時が最終販社チェック日時より大きい
//								if(strtotime($corp_agreement['CorpAgreement']['hansha_check_date']) > strtotime($corp_data['MCorp']['last_antisocial_check_date'])){
//									$corp_data['MCorp']['last_antisocial_check'] = $corp_agreement['CorpAgreement']['hansha_check'];
//									$corp_data['MCorp']['last_antisocial_check_user_id'] = $corp_agreement['CorpAgreement']['hansha_check_user_id'];
//									$corp_data['MCorp']['last_antisocial_check_date'] = $corp_agreement['CorpAgreement']['hansha_check_date'];
//								}
//							}else{
//								$corp_data['MCorp']['last_antisocial_check'] = $corp_agreement['CorpAgreement']['hansha_check'];
//								$corp_data['MCorp']['last_antisocial_check_user_id'] = $corp_agreement['CorpAgreement']['hansha_check_user_id'];
//								$corp_data['MCorp']['last_antisocial_check_date'] = $corp_agreement['CorpAgreement']['hansha_check_date'];
//							}
//						}
// 2017.03.06 izumi.s ORANGE-346 DEL(E)
// 2016.06.23 murata.s ORANGE-102 CHG(S)
// 						if(self::__edit_corp_agreement($corp_agreement)){
						if(self::__edit_corp_agreement($corp_agreement)
								&& self::__update_original_category_data($corp_agreement)
								&& self::__edit_commission_accept($corp_data)
						){
// 2016.06.23 murata.s ORANGE-102 CHG(S)
// ORANGE-199 CHG E
							$this->MCorp->commit();
							$this->CorpAgreement->commit ();
							$this->Session->setFlash ( __ ( 'Updated', true ), 'default', array (
									'class' => 'message_inner'
							));
							// 2016.04.27 ota.r@tobila ADD START
							// ORANGE-1377【契約管理】契約約款内容確認画面(affiliaion/agreement/)で「承認する」で登録した場合に、定型文メールの自動送信
							// 「承認する」にチェックが入っており、DBの登録に成功した場合は定型文メールを送信する
							if(isset($this->request->data['CorpAgreement']['acceptation'])
							        && $this->request->data['CorpAgreement']['acceptation'] == 1) {
// 2016.06.27 murata.s CHG(S) ORANGE-102 連絡手段に"メール"が含まれていない場合は承認メールを送信しない
// 								if (!$this->__send_agreement_mail($corp_data, $corp_agreement)) {
// 									$failed_msg = '承認通知メールの送信に失敗しました。';
// 									$this->Session->setFlash ($failed_msg, 'default', array ('class' => 'error_inner'));
// 								}
								// 顧客連絡手段にメールが含まれている場合のみメール送信を行う
								if($corp_data['MCorp']['coordination_method'] == 1
										|| $corp_data['MCorp']['coordination_method'] == 2
										|| $corp_data['MCorp']['coordination_method'] == 6
										|| $corp_data['MCorp']['coordination_method'] == 7){
									if (!$this->__send_agreement_mail($corp_data, $corp_agreement)) {
										$failed_msg = '承認通知メールの送信に失敗しました。';
										$this->Session->setFlash ($failed_msg, 'default', array ('class' => 'error_inner'));
									}
								}
// 2016.06.27 murata.s CHG(E)
							}
							// 2016.04.27 ota.r@tobila ADD END
							// ORANGE-1377【契約管理】契約約款内容確認画面(affiliaion/agreement/)で「承認する」で登録した場合に、定型文メールの自動送信

						}else{
							$this->log('corp_agreement 会社毎の契約更新エラー');
							$this->MCorp->rollback();
							$this->CorpAgreement->rollback ();
							$this->Session->setFlash ( __ ( 'NotEmptyCorpAgreement', true ), 'default', array (
									'class' => 'error_inner'
							));
						}
					}
				}else{
					// エラー
					$this->log('m_corps 企業マスタ更新エラー');
					$this->MCorp->rollback();
					$this->CorpAgreement->rollback();
					$this->Session->setFlash ( __ ( 'NotEmptyCorpAgreement', true ), 'default', array (
							'class' => 'error_inner'
					));
				}
				// 2016.05.24 murata.s ORANGE-75 CHG(E)
			}catch ( Exception $e ) {
				$this->log($e->getMessages());
				$this->MCorp->rollback();
				$this->CorpAgreement->rollback();
				$this->Session->setFlash ( __ ( 'NotEmptyCorpAgreement', true ), 'default', array (
						'class' => 'error_inner'
				));
			}
		}
		//2016.4.6 ORANGE-1341 iwai ADD(S)
		elseif(isset($this->request->data['update_reconfirmation']) && $this->request->data['update_reconfirmation'] == '契約再確認'){
			try{

				$ca = $this->CorpAgreement->find ( 'first', array (
						'fields' => '*',
						'conditions' => array (
								'CorpAgreement.corp_id' => $id
						),
						'order' => array('id' => 'desc'),
				));

				$a = $this->Agreement->find ( 'first', array (
							'fields' => '*',
							'order' => array('id' => 'desc'),
					));

				if ((empty($corpAgreementCnt) || $corpAgreementCnt == 0) && $a && $ca) {
					$target_data['CorpAgreement']['corp_kind'] = $ca['CorpAgreement']['corp_kind'];
					$target_data['CorpAgreement']['agreement_history_id'] = $a['Agreement']['last_history_id'];
					$target_data['CorpAgreement']['ticket_no'] = $a['Agreement']['ticket_no'];
					$target_data['CorpAgreement']['status'] = 'Reconfirmation';
					$target_data['CorpAgreement']['corp_id'] = $id;
			    	$this->CorpAgreement->create();

					$new_ca = $this->CorpAgreement->save($target_data);
					$corpAgreementCnt = 1;
			    }

			    // 2016.06.23 murata.s ORANGE-102 ADD(S)
			    if(!empty($new_ca))
			    	self::__insert_corp_agreement_temp_link($id, $new_ca['CorpAgreement']['id']);
			    // 2016.06.23 murata.s ORANGE-102 ADD(E)

			    // 2016.05.24 murata.s ORANGE-75 ADD(S)
			    // 契約更新フラグが「1」(契約完了)の場合は「2」(契約未更新)に変更する
			    //if($corp_data['MCorp']['commission_accept_flg'] == 1){
			    //	$corp_data['MCorp']['commission_accept_flg'] = 2;
			    //	$corp_agreement['CorpAgreement']['acceptation_user_id'] = 'SYSTEM';
			    //	$corp_agreement['CorpAgreement']['acceptation_date'] = date('Y-m-d H:i:s');
				//
			    //	self::__edit_commission_accept($corp_data);
			    //}
			    // 2016.05.24 murata.s ORANGE-75 ADD(E)

			}catch(Exception $e){
				$this->log($e->getMessage(), 'error');
			}
		}
		//2016.4.6 ORANGE-1341 iwai ADD(E)
		// 2016.04.20 ORANGE-1210 murata.s ADD(S)
		// 2017/10/18 ORANGE-541 m-kawamoto DEL(S)
		//elseif(isset($this->request->data['update-corp-license'])){
		//	try{
		//		// 企業ライセンスリンク.資格確認を登録
		//		foreach($this->request->data['CorpLisenseLink']['lisense_check'] as $key=>$value){
		//			$corpLisenseLink = $this->CorpLisenseLink->find ( 'first', array (
		//				'fields' => '*',
		//				'conditions' => array (
		//						'CorpLisenseLink.corps_id' => $id,
		//						'CorpLisenseLink.id' => $key,
		//				)
		//			));
        //
		//			if(empty($corpLisenseLink)) throw new Exception();
        //
		//			$corpLisenseLink['CorpLisenseLink']['lisense_check'] = $value;
        //            //2017.4.21 ichino ORANGE-402 ADD ライセンス期限日追加
        //            $corpLisenseLink['CorpLisenseLink']['license_expiration_date'] = $this->request->data['CorpLisenseLink']['license_expiration_date'][$key];
        //            //2017.4.21 ichino ORANGE-402 ADD ライセンス期限日追加 end
		//			$corpLisenseLink['CorpLisenseLink']['update_date'] = date('Y-m-d H:i:s');
		//			$corpLisenseLink['CorpLisenseLink']['update_user_id'] = $this->User['user_id'];
		//			$this->CorpLisenseLink->save($corpLisenseLink);
		//		}
		//	}catch(Exception $e){
		//		$this->log($e->getMessage(), 'error');
		//	}
		//}
		// 2017/10/18 ORANGE-541 m-kawamoto DEL(E)
		// 2016.04.20 ORANGE-1210 murata.s ADD(E)

		//2016.4.6 ORANGE-1341 iwai CHG(S)
		// 企業契約状態を取得 $corp_agreement
		if(!empty($agreement_id)){
			$corp_agreement = $this->CorpAgreement->find ( 'first', array (
					'fields' => '*',
					'conditions' => array (
							'CorpAgreement.corp_id' => $id,
							'CorpAgreement.id' => $agreement_id,
					),

			));
		}else{
			$corp_agreement = $this->CorpAgreement->find ( 'first', array (
					'fields' => '*',
					'conditions' => array (
							'CorpAgreement.corp_id' => $id
					),
					'order' => array('CorpAgreement.id' =>'desc'),
			));
		}

		$corp_agreement_list = $this->CorpAgreement->find ( 'all', array (
					'fields' => '*',
					'conditions' => array (
							'CorpAgreement.corp_id' => $id
					),
					'order' => array('CorpAgreement.id' =>'desc'),
			));


// 2016.06.10 ota.r@tobila DEL ADD ORANGE-30に伴うDBの仕様変更に対応
//		// 契約約款内容取得 $agreement_provisions
//		if(isset($corp_agreement['CorpAgreement']['agreement_id'])){
//			$agreement = $this->Agreement->find ( 'first', array (
//					'fields' => '*',
//					'conditions' => array (
//							'Agreement.id' => $corp_agreement['CorpAgreement']['agreement_id']
//					)
//			));
///*
//			$agreement_provisions = $this->AgreementProvisions->find('all',array(
//					'fields' => '*',
//					'conditions' => array(
//							'AgreementProvisions.agreement_id' =>  $corp_agreement['CorpAgreement']['agreement_id']
//					),
//					'order' => array('sort_no' => 'asc')
//			));
//			if(!empty($agreement_provisions) && is_array($agreement_provisions)){
//				foreach($agreement_provisions as $key => $agreement_provision){
//
//					$agreement_provisions[$key]['item'] = $this->AgreementProvisionsItem->find('all',array(
//					'fields' => '*',
//					'conditions' => array(
//							'AgreementProvisionsItem.agreement_provisions_id' =>  $agreement_provision['AgreementProvisions']['id']
//					),
//					'order' => array('sort_no' => 'asc')
//					));
//
//				}
//			}
//*/
//			//契約マスタ情報取得
//			$this->AgreementProvisions->bindModel(array('hasMany' => array('AgreementProvisionsItem' => array('foreignKey' => 'agreement_provisions_id', 'order' => 'AgreementProvisionsItem.sort_no'))));
//			$agreement_provisions = $this->AgreementProvisions->find('all',array(
//					'fields' => '*',
//					'conditions' => array(
//							'AgreementProvisions.agreement_id' =>  $corp_agreement['CorpAgreement']['agreement_id']
//					),
//					'order' => array('sort_no' => 'asc')
//			));
//
//			$agreement_customize = $this->AgreementCustomize->find('all', array(
//				'fields' => '*',
//				'conditions' => array(
//					'AgreementCustomize.corp_id' =>  $id,
//// 2016.06.09 ota.r@tobila ADD START
//					'AgreementCustomize.corp_agreement_id <=' =>  $corp_agreement['CorpAgreement']['id'],
//// 2016.06.09 ota.r@tobila ADD END
//				),
//				'order' => array('id' => 'asc')
//			));
//
//			//加盟店毎の特約の反映
//			foreach($agreement_customize as $var => $data){
//				// 2016.04.28 murata.s ORANGE-1357 ADD(S)
//				$c_provisions_key = empty($data['AgreementCustomize']['original_provisions_id'])
//						? 'c'.$data['AgreementCustomize']['customize_provisions_id'] : $data['AgreementCustomize']['original_provisions_id'];
//				$c_item_key = empty($data['AgreementCustomize']['original_item_id'])
//						? 'c'.$data['AgreementCustomize']['customize_item_id'] : $data['AgreementCustomize']['original_item_id'];
//				// 2016.04.28 murata.s ORANGE-1357 ADD(E)
//
//				if($data['AgreementCustomize']['table_kind'] == 'AgreementProvisions'){
//					// 2016.04.28 murata.s ORANGE-1357 CHG(S)
//					//foreach($agreement_provisions as $var => $base){
//					//	if($data['AgreementCustomize']['original_id'] == $base['AgreementProvisions']['id']){
//					//		$agreement_provisions[$var]['AgreementProvisions']['provisions'] = $data['AgreementCustomize']['content'];
//					//		$agreement_provisions[$var]['AgreementProvisions']['sort_no'] = $data['AgreementCustomize']['sort_no'];
//					//		break;
//					//	}
//					//}
//					$is_set = false;
//					foreach($agreement_provisions as $var => $base){
//						$base_key = isset($base['AgreementProvisions']['original_key'])
//							? $base['AgreementProvisions']['original_key'] : $base['AgreementProvisions']['id'];
//
//						if($base_key == $c_provisions_key){
//
//							if($data['AgreementCustomize']['edit_kind'] == 'Delete'){
//								unset($agreement_provisions[$var]);
//							}else{
//								$agreement_provisions[$var]['AgreementProvisions']['provisions'] = $data['AgreementCustomize']['content'];
//								$agreement_provisions[$var]['AgreementProvisions']['sort_no'] = $data['AgreementCustomize']['sort_no'];
//								$agreement_provisions[$var]['AgreementProvisions']['AgreementCustomize'] = $data['AgreementCustomize'];
//							}
//							$is_set = true;
//							break;
//						}
//					}
//
//					if(!$is_set && $data['AgreementCustomize']['edit_kind'] != 'Delete'){
//						$customized_provisions = array(
//								'AgreementProvisions' => array(
//										'id' => 0,
//										'provisions' => $data['AgreementCustomize']['content'],
//										'sort_no' => $data['AgreementCustomize']['sort_no'],
//										'original_key' => $c_provisions_key
//								),
//								'AgreementProvisionsItem' => array()
//						);
//						array_push($agreement_provisions, $customized_provisions);
//					}
//					// 2016.04.28 murata.s ORANGE-1357 CHG(E)
//				}elseif($data['AgreementCustomize']['table_kind'] == 'AgreementProvisionsItem'){
//					// 2016.04.28 murata.s ORANGE-1357 CHG(S)
//					//foreach($agreement_provisions as $var1 =>$base){
//					//	foreach($base['AgreementProvisionsItem'] as $var2 => $base_item){
//					//		if($data['AgreementCustomize']['original_id'] == $base_item['id']){
//					//			$agreement_provisions[$var1]['AgreementProvisionsItem'][$var2]['item'] = $data['AgreementCustomize']['content'];
//					//			$agreement_provisions[$var1]['AgreementProvisionsItem'][$var2]['sort_no'] = $data['AgreementCustomize']['sort_no'];
//					//			break;
//					//		}
//					//	}
//					//}
//					$is_set = false;
//					foreach($agreement_provisions as $var1 =>$base){
//						foreach($base['AgreementProvisionsItem'] as $var2 => $base_item){
//							$base_key = isset($base_item['original_key'])
//								? $base_item['original_key'] : $base_item['id'];
//							if($base_key == $c_item_key){
//								if($data['AgreementCustomize']['edit_kind'] == 'Delete'){
//									unset($agreement_provisions[$var1]['AgreementProvisionsItem'][$var2]);
//								}else{
//									$agreement_provisions[$var1]['AgreementProvisionsItem'][$var2]['item'] = $data['AgreementCustomize']['content'];
//									$agreement_provisions[$var1]['AgreementProvisionsItem'][$var2]['sort_no'] = $data['AgreementCustomize']['sort_no'];
//									$agreement_provisions[$var1]['AgreementProvisionsItem'][$var2]['AgreementCustomize'] = $data['AgreementCustomize'];
//								}
//								$is_set = true;
//								break;
//							}
//						}
//					}
//					if(!$is_set && $data['AgreementCustomize']['edit_kind'] != 'Delete'){
//						foreach($agreement_provisions as $var => $base){
//							$p_key = isset($base['AgreementProvisions']['original_key'])
//								? $base['AgreementProvisions']['original_key'] : $base['AgreementProvisions']['id'];
//
//							if($p_key == $c_provisions_key){
//								$customized_item = array(
//										'id' => 0,
//										'item' => $data['AgreementCustomize']['content'],
//										'sort_no' => $data['AgreementCustomize']['sort_no'],
//										'original_key' => $c_item_key
//								);
//								array_push($agreement_provisions[$var]['AgreementProvisionsItem'], $customized_item);
//								break;
//							}
//						}
//					}
//					// 2016.04.28 murata.s ORANGE-1357 CHG(E)
//				}
//			}
//			// 2016.04.28 murata.s ORANGE-1357 ADD(S)
//			// カスタマイズしたソート順で並び替え
//			// ソート順が同じ場合は登録順でソートを行うため、
//			// 一時的にkeyを設定してソート順で並び替え
//			foreach($agreement_provisions as $var1 =>$base){
//				$agreement_provisions[$var1]['key'] = $var1;
//				foreach($base['AgreementProvisionsItem'] as $var2 => $base_item){
//					$agreement_provisions[$var1]['AgreementProvisionsItem'][$var2]['key'] = $var2;
//				}
//				// 項目を並び替え
//				usort($agreement_provisions[$var1]['AgreementProvisionsItem'], function($a, $b){
//					if($a['sort_no'] == $b['sort_no'])
//						return ($a['key'] > $b['key']) ? 1 : -1;
//					return ($a['sort_no'] > $b['sort_no']) ? 1 : -1;
//				});
//			}
//			// 条文を並び替え
//			usort($agreement_provisions, function($a, $b){
//				if($a['AgreementProvisions']['sort_no'] == $b['AgreementProvisions']['sort_no'])
//					return ($a['key'] > $b['key']) ? 1 : -1;
//				return ($a['AgreementProvisions']['sort_no'] > $b['AgreementProvisions']['sort_no']) ? 1 : -1;
//			});
//			// 2016.04.28 murata.s ORANGE-1357 ADD(E)
// 2016.06.10 ota.r@tobila DEL END ORANGE-30に伴うDBの仕様変更に対応
//			//pr($agreement_provisions);
//			// 編集履歴を取得
///*			$agreement_edit_history = $this->AgreementEditHistory->find('all',array(
//				'fields'=>'* ,MUser.user_name',
//				'conditions' => array(
//					'AgreementEditHistory.agreement_id' => $corp_agreement['CorpAgreement']['agreement_id']
//					),
//					'joins' => array(
//						array (
//								'fields' => '*',
//								'type' => 'left',
//								'table' => 'm_users',
//								'alias' => 'MUser',
//								'conditions' => array (
//										'to_number(AgreementEditHistory.create_user_id , \'000000000000\') = MUser.id'
//								)
//						),
//					),
//					'order' => array (
//							'AgreementEditHistory.id' => 'asc'
//					)
//			));
//*/
//		}

		//2016.4.7 ORANGE-1341 iwai CHG(S)
		// 必要書類を取得
		$agreement_attached_file_cert = $this->AgreementAttachedFile->find('all',array(
				'fields'=>'*',
				'conditions' => array(
					'AgreementAttachedFile.corp_id' => $id,
					'AgreementAttachedFile.kind' => 'Cert'
					),
					'order' => array (
							'AgreementAttachedFile.id' => 'asc'
					)
				));
		// 2017/10/18 ORANGE-541 m-kawamoto DEL(S)
		///*
		//// 該当資格情報を取得
		//$agreement_attached_file_license = $this->AgreementAttachedFile->find('all',array(
		//		'fields'=>'* ,MCorpCategory.category_id,MCategory.category_name,MCorpCategory.genre_id,MGenre.genre_name',
		//		'conditions' => array(
		//			'AgreementAttachedFile.corp_id' => $id,
		//			'AgreementAttachedFile.kind' => 'License'
		//			),
		//			'order' => array (
		//					'AgreementAttachedFile.id' => 'asc'
		//			),
		//			'joins' => array(
		//				array (
		//						'fields' => '*',
		//						'type' => 'left',
		//						'table' => 'm_corp_categories',
		//						'alias' => 'MCorpCategory',
		//						'conditions' => array (
		//								'AgreementAttachedFile.m_corp_categories_id = MCorpCategory.id'
		//						)
		//				),
		//				array (
		//						'fields' => '*',
		//						'type' => 'left',
		//						'table' => 'm_categories',
		//						'alias' => 'MCategory',
		//						'conditions' => array (
		//								'MCorpCategory.category_id = MCategory.id'
		//						)
		//				),
		//				array (
		//						'fields' => '*',
		//						'type' => 'left',
		//						'table' => 'm_genres',
		//						'alias' => 'MGenre',
		//						'conditions' => array (
		//								'MCorpCategory.genre_id = MGenre.id'
		//						)
		//				)
		//			)
		//		));
		//*/
		////加盟店に紐付くライセンス
		//$licsnse_link_list = $this->CorpLisenseLink->find('all', array(
		//	// 2016.04.20 ORANGE-1210 murata.s CHG(S)
		//	//'fields' => 'CorpLisenseLink.id, CorpLisenseLink.have_lisense, License.id, License.name',
        //    // 2017.4.21 ichino ORANGE-402 CHG license_expiration_date追加 start
		//	'fields' => 'CorpLisenseLink.id, CorpLisenseLink.have_lisense, License.id, License.name, CorpLisenseLink.lisense_check, CorpLisenseLink.license_expiration_date',
        //    //2017.4.21 ichino ORANGE-402 CHG start
		//	// 2016.04.20 ORANGE-1210 murata.s CHG(E)
		//		'conditions' => array(
		//		'CorpLisenseLink.corps_id' => $id
		//	),
		//	'order' => 'CorpLisenseLink.lisense_id',
		//	'joins' => array(
		//		array(
		//			'fields' => '*',
		//			'type' => 'inner',
		//			'table' => 'license',
		//			'alias' => 'License',
		//			'conditions' => array (
		//				'CorpLisenseLink.lisense_id = License.id'
		//			)
		//		),
		//	)
        //
		//));
        //
		////ライセンスに紐付く画像の取得
		//foreach($licsnse_link_list as $var => $link){
		//	$link_img = $this->AgreementAttachedFile->find('all', array(
		//		'conditions' => array(
		//			'AgreementAttachedFile.corp_id' => $id,
		//			'AgreementAttachedFile.kind' => 'License',
		//			'AgreementAttachedFile.license_id' => $link['License']['id']
		//			),
		//			'order' => array (
		//					'AgreementAttachedFile.id' => 'asc'
		//			),
		//	));
		//	$licsnse_link_list[$var]['AgreementAttachedFile'] = $link_img;
		//}
		// 2017/10/18 ORANGE-541 m-kawamoto DEL(E)
	//2016.4.7 ORANGE-1341 iwai CHG(E)

		// ORANGE-346 ADD S
		$this->loadModel('AntisocialCheck');
		$this->set('last_antisocial_check', $this->AntisocialCheck->findHistoryByCorpId($id,'first'));
		$this->set('antisocial_check', $this->AntisocialCheck->getResultList());
		// ORANGE-346 ADD E

		//2016.4.6 ORANGE-1341 iwai ADD(S)
	$this->set (compact("corpAgreementCnt"));
	$this->set (compact("corp_agreement_list"));
	// 2017/10/18 ORANGE-541 m-kawamoto DEL(S)
	//$this->set (compact("licsnse_link_list"));
	// 2017/10/18 ORANGE-541 m-kawamoto DEL(E)
	//2016.4.6 ORANGE-1341 iwai ADD(E)

    $this->set ("corp_data", $corp_data );
    $this->set ("corp_agreement", $corp_agreement );
    $this->set ("agreement_attached_file_cert", $agreement_attached_file_cert );
    //$this->set ("agreement_attached_file_license", $agreement_attached_file_license );
    // 2016.06.10 ota.r@tobila MOD START ORANGE-30に伴うDBの仕様変更に対応
//    $this->set ("agreement_provisions", $agreement_provisions );
    $agreement_provisions = empty($corp_agreement['CorpAgreement']['customize_agreement'])
                            ? str_replace(array("\n", "\r\n"), "<br>\n", $corp_agreement['CorpAgreement']['original_agreement'])
                            : str_replace(array("\n", "\r\n"), "<br>\n", $corp_agreement['CorpAgreement']['customize_agreement']);
    $this->set ("agreement_provisions", $agreement_provisions);
    // 2016.06.10 ota.r@tobila MOD END ORANGE-30に伴うDBの仕様変更に対応
    //$this->set ("agreement_edit_history", $agreement_edit_history );
    //2016.4.7 ORANGE-1341 iwai CHG(S)
    // 2016.02.15 ORANGE-1318 k.iwai CHG(S)
    if ($this->User['auth'] != 'affiliation' && !empty($corp_agreement['CorpAgreement']['status']) && $corp_agreement['CorpAgreement']['status'] == 'Complete') {
        $this->set ("agreement_report_download_url", Configure::read('agreement_report_download').$id.'&caId='.$corp_agreement['CorpAgreement']['id']);
    }
	// 2016.02.15 ORANGE-1318 k.iwai CHG(E)
	//2016.4.7 ORANGE-1341 iwai CHG(S)
	}

// 2016.08.23 murata.s ORANGE-169 ADD(S)
	/**
	 * 契約書ダウンロード処理
	 *
	 * @param unknown $id
	 * @param string $agreement_id
	 */
	public function agreement_report_download($id, $agreement_id = null){
		try{
			$user = $this->Auth->user();
			$key = $this->AuthInfo->create_auth_key($user['id']);
			if(!empty($key)){
				//  契約システムのレポート出力処理へリダイレクト
				$req_param = $id.(!empty($agreement_id) ? '&caId='.$agreement_id : '').'&userId='.$user['id'].'&key='.$key;
				$this->redirect(Configure::read('agreement_report_download').$req_param);
			}else{
 				throw new ApplicationException(__('NoReferenceAuthority', true));
			}
		}catch(Exception $e){
			$this->log($e->getMessage(), 'error');
 			throw new ApplicationException(__('NoReferenceAuthority', true));
		}
	}
// 2016.08.23 murata.s ORANGE-169 ADD(E)


	/**
	 * 企業別対応カテゴリ情報更新日時のチェック
	 *
	 * @param unknown $id
	 * @param unknown $modified
	 * @return boolean
	 */
	private function __check_modified_category($id, $modified) {
		if (empty ( $id )) {
			return true;
		}
		$results = $this->MCorpCategory->findByid ( $id );

		if (isset ( $results ['MCorpCategory'] ['modified'] )) {
			if ($modified == $results ['MCorpCategory'] ['modified']) {
				return true;
			}
		}
		return false;
	}

	/**
	 * 企業情報更新日時のチェック
	 *
	 * @param unknown $id
	 * @param unknown $modified
	 * @return boolean
	 */
	private function __check_modified_mcorp($id, $modified) {
		if (empty ( $id )) {
			return true;
		}
		$results = $this->MCorp->findByid ( $id );

		if ($modified == $results ['MCorp'] ['modified']) {
			return true;
		}
		return false;
	}

	/**
	 * ID別で企業情報取得
	 *
	 * @param string $id
	 * @return unknown
	 */
	private function __get_m_corp_data($id = null) {
		$appendFields = implode(',',array_map(function($value){
			return 'MCorpNewYear.'.$value;
		},array_keys($this->MCorpNewYear->schema())));

		// 2016.03.11 ORANGE-1175 k.iwai CHG(S)
		$results = $this->MCorp->find ( 'first', array (
				'fields' => '*, AffiliationInfo.id, AffiliationInfo.employees, AffiliationInfo.max_commission, AffiliationInfo.collection_method, AffiliationInfo.collection_method_others, AffiliationInfo.liability_insurance,
					AffiliationInfo.reg_follow_date1, AffiliationInfo.reg_follow_date2, AffiliationInfo.reg_follow_date3, AffiliationInfo.waste_collect_oath, AffiliationInfo.transfer_name, AffiliationInfo.claim_history,
					AffiliationInfo.claim_count, AffiliationInfo.commission_count, AffiliationInfo.weekly_commission_count, AffiliationInfo.orders_count, AffiliationInfo.orders_rate, AffiliationInfo.construction_cost,
					AffiliationInfo.fee, AffiliationInfo.bill_price, AffiliationInfo.payment_price, AffiliationInfo.balance, AffiliationInfo.construction_unit_price, AffiliationInfo.commission_unit_price, AffiliationInfo.reg_info,
					AffiliationInfo.reg_pdf_path, AffiliationInfo.attention, AffiliationStat.commission_count_category, AffiliationStat.orders_count_category, AffiliationStat.commission_unit_price_category, AffiliationInfo.capital_stock,
					AffiliationInfo.listed_kind,AffiliationInfo.default_tax, CorpAgreement.id, AffiliationInfo.credit_limit, AffiliationInfo.add_month_credit, AffiliationInfo.virtual_account, ' . $appendFields,
				'joins' => array (
						array (
								'fields' => '*',
								'type' => 'left',
								'table' => 'affiliation_infos',
								'alias' => 'AffiliationInfo',
								'conditions' => array (
										'MCorp.id = AffiliationInfo.corp_id'
								)
						),
						array (
								'fields' => '*',
								'type' => 'left',
								'table' => 'affiliation_stats',
								'alias' => 'AffiliationStat',
								'conditions' => array (
										'MCorp.id = AffiliationStat.corp_id'
								)
						),
						array (
								'fields' => '*',
								'type' => 'left',
								'table' => 'm_corp_new_years',
								'alias' => 'MCorpNewYear',
								'conditions' => array (
										'MCorp.id = MCorpNewYear.corp_id'
								)
						),
						array (
								'fields' => '*',
								'type' => 'left',
								'table' => 'corp_agreement',
								'alias' => 'CorpAgreement',
								'conditions' => array (
										'MCorp.id = CorpAgreement.corp_id'
								)
						),
				),
				'conditions' => array (
						'MCorp.id' => $id
				)
		) );

		//ORANGE-83 iwai 2016.05.24 ADD(S)
		if(isset($results['MCorp']['responsibility'])){
			$responsibillity = explode(' ', $results['MCorp']['responsibility']);
			if(!empty($responsibillity[0]))$results['MCorp']['responsibility_sei'] = $responsibillity[0];
			if(!empty($responsibillity[1]))$results['MCorp']['responsibility_mei'] = $responsibillity[1];
		}


		// 2016.02.16 ORANGE-1247 k.iwai CHG(E).
		return $results;
	}

	/**
	 * 企業マスタ付帯情報の取得
	 *
	 * @param unknown $id
	 */
	private function __get_m_corp_subs_list($id) {

		// 検索する
		$results = $this->MCorpSub->find ( 'all', array (
				'fields' => '*, MItem.item_name',
				'joins' => array (
						array (
								'fields' => '*',
								'type' => 'left',
								'table' => 'm_items',
								'alias' => 'MItem',
								'conditions' => array (
										'MCorpSub.item_category = MItem.item_category',
										'MCorpSub.item_id = MItem.item_id'
								)
						)
				),
				'conditions' => array (
						'MCorpSub.corp_id' => $id
				),
				'order' => array (
						'MItem.item_category' => 'asc',
						'MItem.sort_order' => 'asc'
				)
		) );
		$get_data = array ();
		$holiday = array ();
		$development_response = array ();

		foreach ( $results as $v ) :
			switch ($v ['MCorpSub'] ['item_category']) {
				case __ ( 'holiday', true ) : // 休業日
					$holiday [] = $v ['MCorpSub'] ['item_id'];
					break;
				case __ ( 'development_reaction', true ) : // 開拓時の反応
					$development_response [] = $v ['MCorpSub'] ['item_id'];
					break;
			}
			;
		endforeach
		;

		$this->set ( "holiday", $holiday );
		$this->set ( "development_response", $development_response );
	}

	/**
	 * 加盟店付帯情報の取得
	 *
	 * @param unknown $id
	 */
	private function __get_affiliation_subs_list($id) {

		// 変数初期化
		$stop_category = array ();

		// STOPカテゴリ 検索する
		$stop_category = $this->AffiliationSub->find ( 'all', array (
				'fields' => 'MCategory.id , MCategory.category_name',
				'joins' => array (
						array (
								'fields' => '*',
								'table' => 'affiliation_infos',
								'alias' => 'AffiliationInfo',
								'conditions' => array (
										'AffiliationInfo.corp_id' => $id,
										'AffiliationInfo.id = AffiliationSub.affiliation_id'
								)
						),
						array (
								'fields' => '*',
								'table' => 'm_categories',
								'alias' => 'MCategory',
								'conditions' => array (
										'MCategory.id = AffiliationSub.item_id'
								)
						)
				),
				'conditions' => array (
						'AffiliationSub.item_category' => __ ( 'stop_category', true )
				),
				'order' => array (
						'MCategory.id' => 'asc'
				)
		) );
		$this->set ( "stop_category", $stop_category );
	}

	/**
	 * 取次STOPカテゴリの取得
	 *
	 * @param unknown $id
	 */
	private function __get_stop_category_list($id) {

		// 検索する
		$results = $this->MCategory->find ( 'all', array (
				'fields' => 'MCategory.id, MCategory.category_name',
				'joins' => array (
						array (
								'fields' => '*',
								'type' => 'left',
								'table' => 'affiliation_infos',
								'alias' => 'AffiliationInfo',
								'conditions' => array (
										'AffiliationInfo.corp_id' => $id
								)
						),
						array (
								'fields' => '*',
								'type' => 'left',
								'table' => 'affiliation_subs',
								'alias' => 'AffiliationSub',
								'conditions' => array (
										'AffiliationSub.affiliation_id = AffiliationInfo.id',
										'AffiliationSub.item_category = \'' . __ ( 'stop_category', true ) . '\'',
										'AffiliationSub.item_id = MCategory.id'
								)
						)
				),
				'conditions' => array (
						'AffiliationSub.id is null'
				),
				'order' => array (
						'MCategory.id' => 'asc'
				)
		) );

		$this->set ( "stop_category_list", $results );
	}

	/**
	 * 加盟店ジャンル別統計情報の取得
	 *
	 * @param unknown $id
	 */
	private function __get_affiliation_stats_list($id) {

		// 検索する
		$results = $this->AffiliationStat->find ( 'all', array (
				'fields' => '*, MGenre.genre_name',
				'joins' => array (
						array (
								'fields' => '*',
								'type' => 'left',
								'table' => 'm_genres',
								'alias' => 'MGenre',
								'conditions' => array (
										'MGenre.id = AffiliationStat.genre_id'
								)
						)
				),
				'conditions' => array (
						'AffiliationStat.corp_id' => $id
				),
				'order' => array (
						'AffiliationStat.id' => 'asc'
				)
		) );

		$this->set ( "affiliation_stats_list", $results );
	}

	/**
	 * 加盟店対応履歴の取得
	 *
	 * @param unknown $conditions
	 * @return unknown
	 */
	private function __set_affiliation_correspond($conditions = array()) {
		$results = $this->AffiliationCorrespond->find ( 'all', array (
				'fields' => "*",
				'conditions' => $conditions,
				'order' => array (
						'AffiliationCorrespond.id' => 'DESC'
				)
		) );
		return $results;
	}

	/**
	 * ID別加盟店対応履歴データの取得
	 *
	 * @param unknown $conditions
	 * @return unknown
	 */
	private function __get_affiliation_correspond($conditions = array()) {
		$results = $this->AffiliationCorrespond->find ( 'first', array (
				'fields' => "*",
				'conditions' => $conditions,
				'order' => array (
						'AffiliationCorrespond.id' => 'DESC'
				)
		) );
		return $results;
	}

	/**
	 * 加盟店対応履歴更新日時のチェック
	 *
	 * @param unknown $id
	 * @param unknown $modified
	 * @return boolean
	 */
	private function __check_modified_affiliation_correspond($id, $modified) {
		$results = $this->AffiliationCorrespond->findByid ( $id );

		if ($modified == $results ['AffiliationCorrespond'] ['modified']) {
			return true;
		}
		return false;
	}

	/**
	 * 加盟店対応履歴の編集
	 *
	 * @param string $id
	 * @param unknown $data
	 * @return boolean
	 */
	private function __edit_history($id = null, $data = array()) {
		$set_data = $data ['AffiliationCorrespond'];
		$set_data ['id'] = $id;
		$save_data = array (
				'AffiliationCorrespond' => $set_data
		);
		// 更新
		if ($this->AffiliationCorrespond->save ( $save_data )) {
			return true;
		}
		return false;
	}



	/**
	 * 企業マスタの登録
	 *
	 * @param unknown $id
	 * @param unknown $data
	 * @param unknown $last_id
	 */
	private function __edit_corp($id, $data, &$last_id) {

// 		// 初回時のみ
// 		if (empty ( $id )) {
// 			$data ['MCorp'] ['affiliation_status'] = 1;
// 		}

		$data ['MCorp'] ['id'] = $id;
		if (empty ( $data ['MCorp'] ['support24hour'] )) {
			$data ['MCorp'] ['support24hour'] = 0;
		}

		$data ['MCorp'] ['id'] = $id;
		if (empty ( $data ['MCorp'] ['contactable_support24hour'] )) {
			$data ['MCorp'] ['contactable_support24hour'] = 0;
		}

		$data ['MCorp'] ['id'] = $id;
		if (empty ( $data ['MCorp'] ['contactable_time_other'] )) {
			$data ['MCorp'] ['contactable_time_other'] = 0;
		}

		$data ['MCorp'] ['id'] = $id;
		if (empty ( $data ['MCorp'] ['available_time_other'] )) {
			$data ['MCorp'] ['available_time_other'] = 0;
		}

		$data ['MCorp'] ['id'] = $id;
		if (empty ( $data ['MCorp'] ['registration_details_check'] )) {
			$data ['MCorp'] ['registration_details_check'] = 0;
		}

		$data ['MCorp'] ['id'] = $id;
		if (empty ( $data ['MCorp'] ['mobile_mail_none'] )) {
			$data ['MCorp'] ['mobile_mail_none'] = 0;
		}

		//ORANGE-83 iwai 2016.05.24 ADD(S)
		$data['MCorp']['responsibility'] =  trim($data['MCorp']['responsibility_sei']).' '.trim($data['MCorp']['responsibility_mei']);

		//ORANGE-337 ADD S
		if(!empty($id) && $this->User['auth'] == 'affiliation'){
			$original_corp = $this->MCorp->findById($id);
		}
		//ORANGE-337 ADD E
		//ORANGE-83 iwai 2016.05.24 ADD(E)

		// 2017.03.03 izuim.s ORANGE-346 ADD(S)
		$is_antisocial_create = false;
		if (empty($data['MCorp']['antisocial_check_month']) && isset($data['MCorp']['last_antisocial_check']) && $data['MCorp']['last_antisocial_check'] === 'OK') {
			$data['MCorp']['antisocial_check_month'] = ((date('n') + 11) % 12);
			$is_antisocial_create = true;
		}
		// 2017.03.03 izuim.s ORANGE-346 ADD(E)

		$save_data = array (
				'MCorp' => $data ['MCorp']
		);

		// 登録
		if ($this->MCorp->save ( $save_data )) {
			$last_id = $this->MCorp->getLastInsertID ();
			//ORANGE-337 ADD S
			if(!empty($id) && $this->User['auth'] == 'affiliation'){

				if($data['MCorp']['responsibility'] != $original_corp['MCorp']['responsibility']){
					//メール配信処理
					$this->__send_responsibility_mail($data);
				}
			}
			//ORANGE-337 ADD E

			// 2017.03.03 izuim.s ORANGE-346 ADD(S)
			if ($is_antisocial_create) {
				if (empty($id)) {
					$id = $last_id;
				}
				$this->loadModel('AntisocialCheck');
				$this->AntisocialCheck->create();
				$this->AntisocialCheck->save(array('corp_id' => $id, 'date' => date('Y-m-d H:i:s')));
			}
			// 2017.03.03 izuim.s ORANGE-346 ADD(E)

			return true;
		} else {
			return false;
		}
	}

	//ORNAGE-337 ADD S
	/**
	 * 代表者変更 メール送信処理
	 * リターンコード無し function内エラー処理
	 * @param unknown $corp
	 */
	private function __send_responsibility_mail($corp = null){

		$to_corp_arr = array();
		$to_corp = $corp;
		$from_corp = ST_MAIL_FROM;
		$subject_corp= '【重要】代表者名変更に伴う証明書類再提出のご案内';
		$t_corp = 'corp_responsibility';

		$to_st = KAMEITEN_MAIL_TO;
		$from_st = ST_MAIL_FROM;
		$subject_st = '加盟店様代表者名が変更されました';
		$t_st = 'st_responsibility';

		if(!empty($corp['MCorp']['mailaddress_pc']) || !empty($corp['MCorp']['mailaddress_mobile'])){
			$tmp_addrs = array();
			if (!empty($corp['MCorp']['mailaddress_pc'])) {
				/* 複数指定されている可能性があるので、セミコロンで分割 */
				$tmp_addrs = explode(";", $corp['MCorp']['mailaddress_pc']);
				/* すべてのメールアドレスを格納 */
				foreach ($tmp_addrs as $one_addr) {
					$to_corp_arr[] = $one_addr;
				}
			}

			$tmp_addrs = array();
			if (!empty($corp['MCorp']['mailaddress_mobile'])) {
				/* 複数指定されている可能性があるので、セミコロンで分割 */
				$tmp_addrs = explode(";", $corp['MCorp']['mailaddress_mobile']);
				/* すべてのメールアドレスを格納 */
				foreach ($tmp_addrs as $one_addr) {
					$to_corp_arr[] = $one_addr;
				}
			}
			foreach($to_corp_arr as $to_corp){
				//加盟店
				//加盟店情報　未使用
				$corp_send_flg = $this->CustomEmail->send($subject_corp, $t_corp, $corp, $to_corp, null, $from_corp);

				//メール送信ログ出力
				if(!$corp_send_flg){
					$msg = 'MailSend: Failure subject:'.$subject_corp."\n to:".$to_corp;
					$this->log($msg);
					$this->CustomEmail->simple_send('ERROR: '.$subject_corp, $msg, $to_st, $from_st);
				}
			}
		}

		//ST用
		$admin_send_flg = $this->CustomEmail->send($subject_st, $t_st, $corp, $to_st, null, $from_st);

		//メール送信ログ出力
		if(!$admin_send_flg)$this->log('MailSend: Failure subject:'.$subject_st."\n toadmin:".$to_st);

	}
	//ONRAGE-337 ADD E

	/**
	 * 企業マスタ付帯情報の登録
	 *
	 * @param unknown $id
	 * @param unknown $data
	 */
	private function __edit_corp_subs($id, $data) {
		$resultsFlg = false; // 更新結果フラグ

		// 休業日
		if (! empty ( $id )) {
			$conditions = array (
					'MCorpSub.corp_id' => $id,
					'MCorpSub.item_category' => __ ( 'holiday', true )
			);
			$resultsFlg = $this->MCorpSub->deleteAll ( $conditions, false );
			if (! $resultsFlg)
				return false;
		}

		if (! empty ( $data ['holiday'] )) {
			foreach ( $data ['holiday'] as $k => $v ) :
				$data ['MCorpSub'] ['id'] = null;
				$data ['MCorpSub'] ['corp_id'] = $id;
				$data ['MCorpSub'] ['item_category'] = __ ( 'holiday', true );
				$data ['MCorpSub'] ['item_id'] = $k;
				$save_data [] = array (
						'MCorpSub' => $data ['MCorpSub']
				);
			endforeach
			;
		}

		// 開拓時の反応
		if (! empty ( $id )) {
			$conditions = array (
					'MCorpSub.corp_id' => $id,
					'MCorpSub.item_category' => __ ( 'development_reaction', true )
			);
			$resultsFlg = $this->MCorpSub->deleteAll ( $conditions, false );
			if (! $resultsFlg)
				return false;
		}
		if (! empty ( $data ['development_response'] )) {
			foreach ( $data ['development_response'] as $k => $v ) :
				$data ['MCorpSub'] ['id'] = null;
				$data ['MCorpSub'] ['corp_id'] = $id;
				$data ['MCorpSub'] ['item_category'] = __ ( 'development_reaction', true );
				$data ['MCorpSub'] ['item_id'] = $v;
				$save_data [] = array (
						'MCorpSub' => $data ['MCorpSub']
				);
			endforeach
			;
		}
		if (! empty ( $save_data )) {
			// 登録
			if ($this->MCorpSub->saveAll ( $save_data )) {
				return true;
			} else {
				return false;
			}
		} else {
			return true;
		}
	}

	/**
	 * 年末年始対応状況の登録
	 * @param $id
	 * @param $data
	 */
	private function __edit_corp_new_years($id, $data) {
		$saveData = Hash::merge($data['MCorpNewYear'],array(
			'corp_id' => $id,
		));
		if($this->MCorpNewYear->save($saveData)){
			return true;
		}
		return false;
	}

	/**
	 * 加盟店情報の登録
	 *
	 * @param unknown $id
	 * @param unknown $data
	 * @param unknown $last_affiliation_id
	 */
	private function __edit_affiliation($id, $data, &$last_affiliation_id) {
		$file_name = "";

		$data ['AffiliationInfo'] ['id'] = $data ['affiliationInfo_id'];
		$data ['AffiliationInfo'] ['corp_id'] = $id;

		if (empty ( $data ['AffiliationInfo'] ['id'] )) {
			$data ['AffiliationInfo'] ['commission_count'] = 0;
		}
		if (empty($data['AffiliationInfo']['default_tax'])) {
			$data['AffiliationInfo']['default_tax'] = FALSE;
		}

		// 登録書PDF
		if (! empty ( $data ['AffiliationInfo'] ['reg_pdf_path'] ['name'] )) :

			// アップロードファイルの拡張子を取得
			$tempfile = $data ['AffiliationInfo'] ['reg_pdf_path'];
			$extension = pathinfo ( $tempfile ['name'], PATHINFO_EXTENSION );

			// アップロードファイル名を編集
			$file_name = "registration_" . $id . "." . $extension;
			$uploadfile = Configure::read ( 'registration_file_path' ) . $file_name;


		endif;

		$data ['AffiliationInfo'] ['reg_pdf_path'] = $file_name;
		$save_data = array (
				'AffiliationInfo' => $data ['AffiliationInfo']
		);
		// 登録
		if ($this->AffiliationInfo->save ( $save_data )) {
			$last_affiliation_id = $this->AffiliationInfo->getLastInsertID ();

			if (! empty ( $uploadfile )) :
				// ファイルアップロード
				move_uploaded_file ( $tempfile ['tmp_name'], $uploadfile );

			endif;

			return true;
		} else {
			return false;
		}
	}

	/**
	 * 加盟店付帯情報の登録
	 *
	 * @param unknown $id
	 * @param unknown $data
	 */
	private function __edit_affiliation_subs($id, $data) {
		$resultsFlg = false; // 更新結果フラグ

		// 取次STOPカテゴリ
		if (! empty ( $id )) {
			$conditions = array (
					'AffiliationSub.affiliation_id' => $id,
					'AffiliationSub.item_category' => __ ( 'stop_category', true )
			);
			$resultsFlg = $this->AffiliationSub->deleteAll ( $conditions, false );
			if (! $resultsFlg)
				return false;
		}

		if (! empty ( $data ['stop_category'] )) {
			foreach ( $data ['stop_category'] as $v ) :
				$data ['AffiliationSub'] ['id'] = null;
				$data ['AffiliationSub'] ['affiliation_id'] = $id;
				$data ['AffiliationSub'] ['item_category'] = __ ( 'stop_category', true );
				$data ['AffiliationSub'] ['item_id'] = $v;
				$save_data [] = array (
						'AffiliationSub' => $data ['AffiliationSub']
				);
			endforeach
			;
		}

		// 登録
		if (! empty ( $save_data )) {
			if ($this->AffiliationSub->saveAll ( $save_data )) {
				return true;
			} else {
				return false;
			}
		} else {
			return true;
		}
	}

	/**
	 * 加盟店対応履歴の登録
	 *
	 * @param string $id
	 * @param unknown $data
	 * @return boolean
	 */
	private function __regist_history($id = null, $data = array()) {

		// 企業IDの指定
		$data ['corp_id'] = $id;
		if (! empty ( $data ['responders'] ) || ! empty ( $data ['corresponding_contens'] )) {
			// 新規登録
			if ($this->AffiliationCorrespond->save ( $data )) {
				return true;
			}
			return false;
		} else {
			return true;
		}
	}

	/**
	 * 関連テーブル削除
	 *
	 * @param unknown $corp_id
	 * @param unknown $affiliation_id
	 */
	private function __delete_corp($corp_id, $affiliation_id) {

		// deleteフラグ処理
		if($this->MCorp->deleteLogical ( $corp_id )){
			return true;
		}
		return false;

		/*
		// 企業マスタの削除
		if ($this->MCorp->delete ( $corp_id )) {
			return true;
		} else {
			return false;
		}

		// 企業マスタ付帯情報の削除
		if ($this->MCorpSub->deleteAll ( array (
				'corp_id' => $corp_id
		) )) {
			return true;
		} else {
			return false;
		}

		// 加盟店情報の削除
		if ($this->AffiliationInfo->delete ( $affiliation_id )) {
			return true;
		} else {
			return false;
		}

		// 加盟店付帯情報の削除
		if ($this->AffiliationSub->deleteAll ( array (
				'affiliation_id' => $affiliation_id
		) )) {
			return true;
		} else {
			return false;
		}
		*/
	}

    /**
     * 企業別対応カテゴリマスタの登録
     *
     * @param unknown $id
     * @param unknown $data
     */
    private function __edit_corp_category($id, $data) {
        $resultsFlg = false; // 更新結果フラグ

// 2016.05.13 ota.r@tobila MOD START ORANGE-47 【加盟店管理】カテゴリ登録時、「入力項目を確認してください。 」となって登録できない
        $count = 0;
//        foreach ( $data ['MCorpCategory'] as $v ) {
        foreach ( $data ['MCorpCategory'] as $k => $v ) {
// 2016.05.13 ota.r@tobila MOD END ORANGE-47 【加盟店管理】カテゴリ登録時、「入力項目を確認してください。 」となって登録できない

            $v ['corp_id'] = $id;
            $save_data [] = array (
                    'MCorpCategory' => $v
            );
// 2016.05.13 ota.r@tobila MOD START ORANGE-47 【加盟店管理】カテゴリ登録時、「入力項目を確認してください。 」となって登録できない
//            $resultsFlg = $this->__edit_corp_category_check ( $data ['MCorpCategory'], $v ['category_id'], $count );
            $resultsFlg = $this->__edit_corp_category_check_for_category ( $data ['MCorpCategory'], $v ['category_id'], $k);
// 2016.05.13 ota.r@tobila MOD END ORANGE-47 【加盟店管理】カテゴリ登録時、「入力項目を確認してください。 」となって登録できない
            if (! $resultsFlg) {
                $category_error [$count] = __ ( 'MCorpCategoryError', true );
                $this->set ( 'category_error', $category_error );
                break;
            }
            $count ++;
        }

        if (! empty ( $save_data )) {
            if ($resultsFlg) {
                // 登録
                if ($this->MCorpCategory->saveAll ( $save_data )) {
                    return true;
                }
            }
            return false;
        } else {
            return true;
        }
    }

	/**
	 * カテゴリー重複チェック
	 *
	 * @param unknown $data
	 * @param unknown $category_id
	 * @param unknown $count
	 * @return boolean
	 */
	private function __edit_corp_category_check($data, $category_id, $count) {
		$resultsFlg = true;

		for($i = 0; $i < $count; $i ++) {
			if (! empty ( $data [$i] ['category_id'] )) {
				if ($data [$i] ['category_id'] == $category_id) {
					$resultsFlg = false;
					break;
				}
			}
		}

		return $resultsFlg;
	}

// 2016.05.13 ota.r@tobila ADD START ORANGE-47 【加盟店管理】カテゴリ登録時、「入力項目を確認してください。 」となって登録できない
	/**
	 * カテゴリー重複チェック2
	 *
	 * 「加盟店管理 -> カテゴリ選択」で重複チェックがうまくできない問題への対応
	 *  自分自身が何番目の要素かを取得し、自分自身は重複チェックを行わない
	 *
	 * @param unknown $data
	 * @param unknown $category_id
	 * @param unknown $count
	 * @return boolean
	 */
	private function __edit_corp_category_check_for_category($data, $category_id, $num) {
		$resultsFlg = true;

		foreach ($data as $key => $val) {
			// チェック対象が自分自身の時はスルー
			if ($key == $num) {
				continue;
			}

			if (!empty($val['category_id'])) {
				// 自分自身以外に同じcorp_idがいたときは重複エラー
				// 実際に起こりうるかは不明
				if ($val['category_id'] == $category_id) {
					$resultsFlg = false;
					break;
				}
			}
		}

		return $resultsFlg;
	}
// 2016.05.13 ota.r@tobila ADD END ORANGE-47 【加盟店管理】カテゴリ登録時、「入力項目を確認してください。 」となって登録できない

	/**
	 * 入力後の表示
	 * （配列が違う為の処理）
	 *
	 * @param unknown $data
	 */
	private function __input_after_display($data) {
		$this->set ( "holiday", isset ( $this->data ['holiday'] ) ? $this->data ['holiday'] : array () );

		$this->set ( "development_response", $data ['development_response'] );

		$count = 0;
		if (isset ( $data ['stop_category'] )) {
			foreach ( $data ['stop_category'] as $v ) {
				$stop_category [$count] ['MCategory'] ['id'] = $v;
				$stop_category [$count] ['MCategory'] ['category_name'] = $this->MCategory->getListText ( $v );
				$count ++;
			}
		} else {
			$stop_category = array ();
		}
		$this->set ( "stop_category", $stop_category );
	}

	/**
	 * カテゴリエリアマスタへ登録
	 *
	 * @param unknown $id
	 * @param unknown $data
	 */
	private function __edit_target_area($id, $data) {
		$resultsFlg = false; // 更新結果フラグ

		//企業エリアマスターの取得
		$corp_areas = $this->MCorpTargetArea->find('all', array(
								'fields' => '*',
								'conditions' => array(
										'corp_id = ' . $id
								)
							)
						);

		//カテゴリ分エリアマスターを取得し、登録する
		$save_data = array();
		foreach ($data['MCorpCategory'] as $m_corp_category) {
			if ( ! empty( $m_corp_category['id'] )) {
				if (! isset($m_corp_category['refresh'])) {
					continue;
				} else {
					//エリアの再登録のため、一度カテゴリのエリアを全削除
					$result = $this->MTargetArea->deleteAll(array('corp_category_id = ' . $m_corp_category['id']));
				}
			}

			$corp_category = $this->MCorpCategory->find('all', array(
										'fields' => '*',
										'conditions' => array(
											'corp_id = ' . $id,
											'category_id = ' . $m_corp_category['category_id']
										)
									)
								);

			if ( empty( $corp_category[0]['MCorpCategory']['id'] ) ) return false;

			foreach ($corp_areas as $area) {
				$set_data = array();
				$set_data['corp_category_id'] = $corp_category[0]['MCorpCategory']['id'];
				$set_data['jis_cd'] = $area['MCorpTargetArea']['jis_cd'];
				$save_data[] = array('MTargetArea' => $set_data);
			}
		}

		if (count($save_data) > 0) {
			if ( $this->MTargetArea->saveAll($save_data) ) {
				return true;
			} else {
				return false;
			}
		// 2015.2.27 inokuchi (バグの為追加しました。)
		} else {
			return true;
		}
	}

	/**
	 * GETパラメーターをセッションに保存
	 */
	private function __set_parameter_session() {
		$this->Session->delete ( self::$__sessionKeyForAffiliationParameter );
		$this->Session->write ( self::$__sessionKeyForAffiliationParameter, $this->params ['named'] );
	}

	/**
	 * CSV出力処理
	 *
	 * @param unknown $conditions
	 * @return unknown
	 */
	private function __getDataCsv($data){

		// 検索条件の設定
		$conditions = self::setSearchConditions ( $data );

		$joins = array ();
		$joins = self::setSearchJoin ($data);

		// 加盟店情報
		//ORANGE-109 CHG
		$fields_affiliation_info = 'AffiliationInfo.id, AffiliationInfo.employees, AffiliationInfo.max_commission, AffiliationInfo.collection_method, AffiliationInfo.collection_method_others, AffiliationInfo.liability_insurance, AffiliationInfo.reg_follow_date1, AffiliationInfo.reg_follow_date2, AffiliationInfo.reg_follow_date3, AffiliationInfo.waste_collect_oath, AffiliationInfo.transfer_name, AffiliationInfo.claim_count, AffiliationInfo.claim_history, ';
		$fields_affiliation_info .= 'AffiliationInfo.commission_count, AffiliationInfo.weekly_commission_count, AffiliationInfo.orders_count, AffiliationInfo.orders_rate, AffiliationInfo.construction_cost, AffiliationInfo.fee, AffiliationInfo.bill_price, AffiliationInfo.payment_price, AffiliationInfo.balance, AffiliationInfo.construction_unit_price, AffiliationInfo.commission_unit_price, AffiliationInfo.reg_info, AffiliationInfo.reg_pdf_path, AffiliationInfo.attention, ';
		$fields_affiliation_info .= 'AffiliationInfo.capital_stock, AffiliationInfo.listed_kind, AffiliationInfo.default_tax,  AffiliationInfo.credit_limit, AffiliationInfo.add_month_credit, AffiliationInfo.virtual_account';
		//ORANGE-109 CHG

		// murata.s ORANGE-400 ADD(S)
		$fields_corp_agreement = 'CorpAgreement.acceptation_date';
		// murata.s ORANGE-400 ADD(E)

		// 休業日
		$fields_holiday = '(SELECT ARRAY_TO_STRING(ARRAY( SELECT item_name FROM m_items INNER JOIN m_corp_subs ON m_corp_subs.item_category = m_items.item_category AND m_corp_subs.item_id = m_items.item_id WHERE m_corp_subs.item_category = \''.__('holiday' , true).'\' AND m_corp_subs.corp_id = "MCorp"."id" ORDER BY m_items.sort_order ASC ),\'｜\') as "MCorp__holiday" )';
		// 開拓時の反応
		$fields_development_response = '(SELECT ARRAY_TO_STRING(ARRAY( SELECT item_name FROM m_items INNER JOIN m_corp_subs ON m_corp_subs.item_category = m_items.item_category AND m_corp_subs.item_id = m_items.item_id WHERE m_corp_subs.item_category = \''.__('development_reaction' , true).'\' AND m_corp_subs.corp_id = "MCorp"."id" ORDER BY m_items.sort_order ASC ),\'｜\') as "MCorp__development_response" )';
		// 取次STOPカテゴリ
		$fields_stop_category_name = '(SELECT ARRAY_TO_STRING(ARRAY( SELECT category_name FROM m_categories INNER JOIN affiliation_subs ON affiliation_subs.item_id = m_categories.id AND affiliation_subs.item_category = \''.__('stop_category' , true).'\' AND affiliation_subs.affiliation_id = "AffiliationInfo"."id" ),\'｜\') as "AffiliationInfo__stop_category_name" )';
		// カテゴリ
		$fields_category_name = '(SELECT ARRAY_TO_STRING(ARRAY( SELECT category_name FROM m_categories INNER JOIN m_corp_categories ON m_corp_categories.category_id = m_categories.id AND m_corp_categories.corp_id = "MCorp"."id" ),\'｜\') as "MCorp__category_name" )';

		// 2015.09.07 s.harada ADD start ORANGE-816
		$fields_mcc_modified = '(( SELECT modified as "MCorp__mcc_modified" FROM m_corp_categories WHERE corp_id = "MCorp"."id" ORDER BY m_corp_categories.modified desc LIMIT 1) )';
		$fields_mct_modified = '(( SELECT modified as "MCorp__mct_modified" FROM m_corp_target_areas WHERE corp_id = "MCorp"."id" ORDER BY m_corp_target_areas.modified desc LIMIT 1) )';
		// 2015.09.07 s.harada ADD end ORANGE-816

		$results = $this->MCorp->find ( 'all', array (
				'conditions' => $conditions,
				// murata.s ORANGE-400 CHG(S)
				// 2015.09.07 s.harada MOD start ORANGE-816
				//'fields' => '* , '.$fields_affiliation_info.', '.$fields_category_name.', '.$fields_holiday.', '.$fields_development_response.', '.$fields_stop_category_name ,
				'fields' => '* , '.$fields_affiliation_info.', '.$fields_category_name.', '.$fields_holiday.', '.$fields_development_response.', '.$fields_stop_category_name.', '.$fields_mcc_modified.', '.$fields_mct_modified.', '.$fields_corp_agreement ,
				// 2015.09.07 s.harada MOD emd ORANGE-816
				// murata.s ORANGE-400 CHG(E)
				'joins' => $joins,
				'order' => array (
						'MCorp.id' => 'asc'
				)
		) );


		$free_estimate_list = Util::getDropList(__('free_estimate', true));					// 無料見積対応
		$portalsite_list = Util::getDropList(__('portalsite', true));						// ポータルサイト掲載
		$reg_send_method_list = Util::getDropList(__('reg_send_method', true));				// 登録書発送方法
		$coordination_method_list = Util::getDropList(__('coordination_method', true));		// 顧客情報連絡手段
		$prog_send_method_list = Util::getDropList(__('prog_send_method', true));			// 進捗表送付方法
		$bill_send_method_list = Util::getDropList(__('bill_send_method', true));			// 請求書送付方法
		$collection_method_list = Util::getDropList(__('collection_method', true));			// 代金徴収方法
		$liability_insurance_list = Util::getDropList(__('liability_insurance', true));		// 賠償責任保険
		$waste_collect_oath_list = Util::getDropList(__('waste_collect_oath', true));		// 不用品回収誓約書
		$claim_count_list = Util::getDropList(__('claim_count', true));						// 顧客クレーム回数
		// 2015.6.7 m.katsuki ADD start ORANGE-515
		$corp_commission_status_list = Util::getDropList(__('開拓取次状況', true));
		// 2015.6.7 m.katsuki ADD start
		//ORANGE-109 S
		$collection_method = Util::getDropList(__('代金徴収方法', true));
		$listed_kind = array('listed'=>'上場', 'unlisted'=>'非上場');
		$corp_kind = array('Corp'=>'法人', 'Person'=>'個人');
		//ORANGE-109 E
		//ORANGE-103 S
		$commission_accept_flg = array(0=>'未契約', 1=>'契約完了', 2=>'契約未更新', 3=>'未更新STOP');
		//ORANGE-103 E

		//ORANGE-126 2016.8.16 iwai S
		$jbr_available_status_list = Util::getDropList(__('JBR対応状況' , true));
		//ORANGE-126 2016.8.16 iwai E

		// 2016.12.08 murata.s ORANGE-250 ADD(S)
		$auction_status = Util::getDivList('auction_delivery_status');
		// 2016.12.08 murata.s ORANGE-250 ADD(E)

		// 2017.09.22 e.takeuchi@SharingTechnology ORANGE-531 【加盟店管理】CSVの項目に支払サイト追加 ADD(S)
		$payment_site_list = Util::getDropList(__('支払サイト', true));						// 支払サイト
		// 2017.09.22 e.takeuchi@SharingTechnology ORANGE-531 【加盟店管理】CSVの項目に支払サイト追加 ADD(E)

		$data = $results;
		foreach ($results as $key => $val){

			// 2015.5.17 n.kai MOD start ＊条件と結果(加盟/未加盟)が逆転しているため修正
			//$data[$key]['MCorp']['affiliation_status'] = !$val['MCorp']['affiliation_status'] == 0 ? __('not_accession', true) : __('accession', true);															// 加盟状態
			$data[$key]['MCorp']['affiliation_status'] = $val['MCorp']['affiliation_status'] == 0 ? __('not_accession', true) : __('accession', true);															// 加盟状態
			// 2015.5.17 n.kai MOD end
			$data[$key]['MCorp']['address1'] = !empty($val['MCorp']['address1']) ? Util::getDivTextJP('prefecture_div', $val['MCorp']['address1']) : '';														// 都道府県
			$data[$key]['MCorp']['support24hour'] = !empty($val['MCorp']['support24hour']) ?  __('maru', true) :__('batu', true) ;																				// 24時間対応
			$data[$key]['MCorp']['free_estimate'] = !empty($val['MCorp']['free_estimate']) ? $free_estimate_list[$val['MCorp']['free_estimate']] : '';															// 無料見積対応
			$data[$key]['MCorp']['portalsite'] = !empty($val['MCorp']['portalsite']) ? $portalsite_list[$val['MCorp']['portalsite']] : '';																		// ポータルサイト掲載
			$data[$key]['MCorp']['reg_send_method'] = !empty($val['MCorp']['reg_send_method']) ? $reg_send_method_list[$val['MCorp']['reg_send_method']] : '';													// 登録書発送方法
			$data[$key]['MCorp']['coordination_method'] = !empty($val['MCorp']['coordination_method']) ? $coordination_method_list[$val['MCorp']['coordination_method']] : '';									// 顧客情報連絡手段
			$data[$key]['MCorp']['prog_send_method'] = !empty($val['MCorp']['prog_send_method']) ? $prog_send_method_list[$val['MCorp']['prog_send_method']] : '';												// 進捗表送付方法
			$data[$key]['MCorp']['bill_send_method'] = !empty($val['MCorp']['bill_send_method']) ? $bill_send_method_list[$val['MCorp']['bill_send_method']] : '';											// 請求書送付方法
			$data[$key]['MCorp']['special_agreement_check'] = !empty($val['MCorp']['special_agreement_check']) ?  __('maru', true) : '';										// 請求時特約確認要 2014.04.29 y.tanaka
			$data[$key]['AffiliationInfo']['collection_method'] = !empty($val['AffiliationInfo']['collection_method']) ? $collection_method_list[$val['AffiliationInfo']['collection_method']] : '';			// 代金徴収方法
			$data[$key]['AffiliationInfo']['liability_insurance'] = !empty($val['AffiliationInfo']['liability_insurance']) ? $liability_insurance_list[$val['AffiliationInfo']['liability_insurance']] : '';	// 賠償責任保険
			$data[$key]['AffiliationInfo']['waste_collect_oath'] = !empty($val['AffiliationInfo']['waste_collect_oath']) ? $waste_collect_oath_list[$val['AffiliationInfo']['waste_collect_oath']] : '';		// 不用品回収誓約書
			$data[$key]['AffiliationInfo']['claim_count'] = !empty($val['AffiliationInfo']['claim_count']) ? $claim_count_list[$val['AffiliationInfo']['claim_count']] : '';									// 顧客クレーム回数
			// 2015.6.7 m.katsuki ADD start ORANGE-515
			$data[$key]['MCorp']['corp_commission_status'] = !empty($val['MCorp']['corp_commission_status']) ? $corp_commission_status_list[$val['MCorp']['corp_commission_status']] : '';
			// 2015.6.7 m.katsuki ADD end
			// 2015.09.07 s.harada ADD start ORANGE-816
			$data[$key]['MCorp']['mct_modified'] = !empty($val['MCorp']['mct_modified']) ? date("Y/m/d H:i:s", strtotime($val['MCorp']['mct_modified'])) : '';
			// 2015.09.07 s.harada ADD end ORANGE-816
			//ORANGE-109 S
			$data[$key]['AffiliationInfo']['collection_method'] = !empty($val['AffiliationInfo']['collection_method']) ? $collection_method[$val['AffiliationInfo']['collection_method']] : '';
			$data[$key]['AffiliationInfo']['listed_kind'] = !empty($val['AffiliationInfo']['listed_kind']) ? $listed_kind[$val['AffiliationInfo']['listed_kind']] : '';
			$data[$key]['MCorp']['corp_kind'] = !empty($val['MCorp']['corp_kind']) ? $corp_kind[$val['MCorp']['corp_kind']] : '';
			//ORANGE-109 E
			//ORANGE-103 S
			$data[$key]['MCorp']['commission_accept_flg'] = !empty($val['MCorp']['commission_accept_flg']) ? $commission_accept_flg[$val['MCorp']['commission_accept_flg']] : '';
			//ORANGE-103 E
			//ORANGE_126 2016.8.16 iwai S
			$data[$key]['MCorp']['jbr_available_status'] = !empty($val['MCorp']['jbr_available_status']) ? $jbr_available_status_list[$val['MCorp']['jbr_available_status']] : '';
			//ORANGE_126 2016.8.16 iwai E
			// 2016.12.08 murata.s ORANGE-250 ADD(S)
			$data[$key]['MCorp']['auction_status'] = !empty($val['MCorp']['auction_status']) ? $auction_status[$val['MCorp']['auction_status']] : '';
			// 2016.12.08 murata.s ORANGE-250 ADD(E)
			// 2017.09.22 e.takeuchi@SharingTechnology ORANGE-531 【加盟店管理】CSVの項目に支払サイト追加 ADD(S)
			$data[$key]['MCorp']['payment_site'] = !empty($val['MCorp']['payment_site']) ? $payment_site_list[$val['MCorp']['payment_site']] : '';
			// 2017.09.22 e.takeuchi@SharingTechnology ORANGE-531 【加盟店管理】CSVの項目に支払サイト追加 ADD(E)
		}

		return $data;
	}

	// 2015.08.27 s.harada ADD start 画面デザイン変更対応

	//-- ジャンルの設定用ファンクション群 start --//

	/**
	 * 企業別対応カテゴリマスタの登録
	 *
	 * @param unknown $id
	 * @param unknown $data
	 */
	private function __edit_corp_category_genre($id, $data, &$del_array) {
		$resultsFlg = false; // 更新結果フラグ

		$count = 0;

		//コピー元のカテゴリーIdを取得1
		$org_category_id_array = array();
		foreach ($data['MCorpCategory'] as $org_mcc_data) {
			if (!empty($org_mcc_data['category_id'])) {
				$org_id_array = split('-', $org_mcc_data['category_id']);
				$org_category_id_array[] = $org_id_array[1];
			}
		}

		// コピー先のカテゴリーIDを取得2
		$copyCategoryIds = $this->MCategoryCopyrule->find('list', array(
			'fields'     => 'MCategoryCopyrule.copy_category_id',
			'conditions' => array('MCategoryCopyrule.org_category_id' => $org_category_id_array),
		));

		// コピー先に該当する企業別対応カテゴリマスタに登録済みのカテゴリーを取得
		$this->MCorpCategory->unbindModel(
			array('belongsTo' => array('MCorp'))
		);

// 2016.06.23 murata.s ORANGE-102 CHG(S)
// 		$copyCorpCategories = $this->MCorpCategory->find('all', array(
// 			'conditions' =>  array(
// 				'MCorpCategory.id' => $copyCategoryIds
// 			),
// 		));
		$copyCorpCategories = $this->MCorpCategory->find('all', array(
			'conditions' =>  array(
				'MCorpCategory.category_id' => $copyCategoryIds
			),
		));
// 2016.06.23 murata.s ORANGE-102 CHG(E)
		// コピー先に該当するカテゴリーを取得
		$categories = $this->MCategory->find('all', array(
			'fields' => '*, MGenre.commission_type',
			'conditions' =>  array(
				'MCategory.id' => $copyCategoryIds
			),
			'joins' => array(
				array(
					'table' => 'm_genres',
					'alias' => 'MGenre',
					'type' => 'LEFT',
					'conditions' => array(
						'MCategory.genre_id = MGenre.id',
					)
				)
			)
		));
		$corpCategoryId = '';
		// コピー先に対応する企業別対応カテゴリ用の配列を挿入
		foreach ($categories as $category) {
// 2016.06.23 murata.s ORANGE-102 ADD(S)
			// 前処理の$corpCategoryIdを引き継ぐ場合があるので、loop毎に初期化する
			$corpCategoryId = '';
// 2016.06.23 murata.s ORANGE-102 ADD(E)
			foreach ($copyCorpCategories as $corpCategory) {

				if ($corpCategory['MCorpCategory']['corp_id'] == $id && $corpCategory['MCorpCategory']['genre_id'] == $category['MCategory']['genre_id'] && $corpCategory['MCorpCategory']['category_id'] ==  $category['MCategory']['id']) {
					$corpCategoryId = '-'. $corpCategory['MCorpCategory']['id'];
					break;
				}
			}
		// コピー元の手数料率と単位をコピーしないといけない。
			$data['MCorpCategory'][] = array(
				'default_fee' => $category['MCategory']['category_default_fee'],
				'default_fee_unit' => $category['MCategory']['category_default_fee_unit'],
				'commission_type' => $category['MGenre']['commission_type'],
				'category_id' => $category['MCategory']['genre_id'] . '-' . $category['MCategory']['id'] . $corpCategoryId,
			);
		}

		// 2015.08.27 s.harada ADD start 画面デザイン変更対応
		$del_mcc_id_array = array();
		$chk_mcc_id = $data['chk_mcc_id'];
		if (strlen($chk_mcc_id) > 0) {
			$mcc_id_array = $del_mcc_id_array = split('-', $chk_mcc_id);
		}

		$tmp_del_array = array();

		foreach ( $data ['MCorpCategory'] as $v ) {

			$v['corp_id'] = $id;

			// 2015.08.27 s.harada ADD start 画面デザイン変更対応
			if (!isset($v['category_id'])) {
				continue;
			}
			$id_array = split('-', $v['category_id']);
			if (count($id_array) == 3) {
				$v['id'] = $id_array[2];
				// 削除用id配列から除外
				foreach ($del_mcc_id_array as $key => $value) {
					if ($value == $v['id']) {
						array_splice($del_mcc_id_array, $key, 1);
						break;
					}
				}
			} else {
				// 重複登録防止チェック
				$check_record = $this->MCorpCategory->getMCorpCategoryID2($id, $id_array[0], $id_array[1]);
				if ($check_record != null) {
					continue;
				}
				// 新規ジャンル登録の場合は、デフォルト手数料とデフォルト手数料単位を受注手数料と受注手数料単位に書き込む
				if ($v['commission_type'] == 2 ) {
					// 紹介ベースジャンルの場合、紹介手数料にセットする。
					$v['introduce_fee']      = $v['default_fee'];
				} else {
					// 受注手数料関連項目にセットする。
					$v['order_fee']      = $v['default_fee'];
					$v['order_fee_unit'] = $v['default_fee_unit'];
				}
// murata.s ORANGE-261 CHG(S)
				$v['corp_commission_type'] = $v['commission_type'];
// murata.s ORANGE-261 CHG(E)
			}
			$v['genre_id'] = $id_array[0];
			$v['category_id'] = $id_array[1];
			//
			unset($v['table_disc_id']);
			unset($v['default_fee']);
			unset($v['default_fee_unit']);
			$v = array_merge($v);
			// 2015.08.27 s.harada ADD end 画面デザイン変更対応

			$save_data [] = array (
					'MCorpCategory' => $v
			);
			$resultsFlg = $this->__edit_corp_category_check ( $data ['MCorpCategory'], $v ['category_id'], $count );
			if (! $resultsFlg) {
				$category_error [$count] = __ ( 'MCorpCategoryError', true );
				$this->set ( 'category_error', $category_error );
				break;
			}
			$count ++;
		}

		foreach ($del_mcc_id_array as $key => $value) {

			$orgCategoryCategoryIds = $this->MCorpCategory->find('list', array(
				'fields' => 'MCorpCategory.category_id',
				'conditions' => array('MCorpCategory.id' => $value),
			));

			$copyCategoryCategoryIds = $this->MCategoryCopyrule->find('list', array(
				'fields' => 'MCategoryCopyrule.copy_category_id',
				'conditions' => array('MCategoryCopyrule.org_category_id' => $orgCategoryCategoryIds),
			));

			$MCorpCategoryIds = $this->MCorpCategory->find('list', array(
				'fields' => 'MCorpCategory.id',
				'conditions' => array('MCorpCategory.category_id' => $copyCategoryCategoryIds,
					'MCorpCategory.corp_id' => $id),
			));

			$tmp_del_array = $tmp_del_array + $MCorpCategoryIds;

		}
		if (isset($tmp_del_array)) {
			$del_array = $tmp_del_array;
			$del_mcc_id_array = $del_mcc_id_array + $tmp_del_array;
		}

		// チェック済みからチェックをはずしたカテゴリを削除
		foreach ($del_mcc_id_array as $key => $value) {
			// カテゴリの削除
			$this->MCorpCategory->delete($value);
		}

		if (! empty ( $save_data )) {
			if ($resultsFlg) {
				// 登録
				if ($this->MCorpCategory->saveAll ( $save_data )) {
					return true;
				}
			}
			return false;
		} else {
			return true;
		}
	}

	/**
	 * カテゴリエリアマスタへ登録
	 *
	 * @param unknown $id
	 * @param unknown $data
	 */
	private function __edit_target_area_genre($id, $data, $del_array) {
		$resultsFlg = false; // 更新結果フラグ

		// チェック済みからチェックをはずしたカテゴリのエリアの削除処理開始
		$del_mcc_id_array = array();
		$chk_mcc_id = $data['chk_mcc_id'];
		if (strlen($chk_mcc_id) > 0) {
			$del_mcc_id_array = split('-', $chk_mcc_id);
		}
		$tmp_del_array = array();
		//カテゴリ分エリアマスターを取得し、登録する
		$save_data = array();
		foreach ($data['MCorpCategory'] as $v) {

			if (!isset($v['category_id'])) {
				continue;
			}
			$id_array = split('-', $v['category_id']);
			if (count($id_array) == 3) {
				$v['id'] = $id_array[2];
				// 削除用id配列から除外
				foreach ($del_mcc_id_array as $key => $value) {
					if ($value == $v['id']) {
						array_splice($del_mcc_id_array, $key, 1);
						break;
					}
				}
			}

		}

		if (isset($del_array)) {
			$del_mcc_id_array = $del_mcc_id_array + $del_array;
		}

		// チェック済みからチェックをはずしたカテゴリのエリアを全削除
		foreach ($del_mcc_id_array as $key => $value) {
			// エリアを全削除
			$result = $this->MTargetArea->deleteAll(array('MTargetArea.corp_category_id' => $value), false);
		}
		// チェック済みからチェックをはずしたカテゴリのエリアの削除処理完了

		// 対応エリアなしカテゴリに基本対応エリアを設定開始

		//企業エリアマスターの取得
		$corp_areas = $this->MCorpTargetArea->find('all', array(
				'fields' => '*',
				'conditions' => array(
						'corp_id = ' . $id
				)
		)
		);
		// 同一会社IDを全て更新
		$id_list = $this->MCorpCategory->getMCorpCategoryIDList2($id);
		foreach ($id_list as $key => $val) {
			// 指定した「corp_category_id」のエリア数
			$area_count = $this->MTargetArea->getCorpCategoryTargetAreaCount($val['MCorpCategory']['id']);
			if ($area_count > 0) {
				continue;
			}

			foreach ($corp_areas as $area) {
				$set_data = array();
				$set_data['corp_category_id'] = $val['MCorpCategory']['id'];
				$set_data['jis_cd'] = $area['MCorpTargetArea']['jis_cd'];
				$save_data[] = array('MTargetArea' => $set_data);
			}

			$g_list = $this->MCorpCategory->getMCorpCategoryIDList($id, $val['MCorpCategory']['genre_id']);
			foreach ($g_list as $g_val) {
				if ($g_val['MCorpCategory']['target_area_type'] > 0) {
					self::__edit_corp_category_target_area_type($val['MCorpCategory']['id'], $g_val['MCorpCategory']['target_area_type']);
					break;
				}
			}
		}
		if (count($save_data) > 0) {
			if ( $this->MTargetArea->saveAll($save_data) ) {
				return true;
			} else {
				return false;
			}
			// 2015.2.27 inokuchi (バグの為追加しました。)
		} else {
			return true;
		}
		// 対応エリアなしカテゴリに基本対応エリアを設定終了
	}

	//-- ジャンルの設定用ファンクション群 end --//

	//-- 基本対応エリアの設定用ファンクション群 start --//
	/**
	 * エリアのリストを取得
	 *
	 * @param string $id
	 * @param unknown $conditions
	 * @return unknown
	 */
	private function __get_target_area_list($id = null , $conditions = array()){

		$results = $this->MPost->find('all',
				array('fields' => 'MPost.address2 , MPost.jis_cd , MCorpTargetArea.corp_id',
						'joins' =>  array(
								array('fields' => '*',
										'type' => 'LEFT',
										"table" => "m_corp_target_areas",
										"alias" => "MCorpTargetArea",
										"conditions" => array("MCorpTargetArea.jis_cd = MPost.jis_cd" , "MCorpTargetArea.corp_id = ".$id)
								),
						),
						'conditions' => $conditions ,
						'group' => 'MPost.jis_cd , MPost.address2 , MCorpTargetArea.corp_id',
						'order' => array('MPost.jis_cd'=> 'asc'),
				)
		);

		return $results;
	}

	/**
	 * 対応可能地域の登録・削除
	 *
	 * @param unknown $id
	 * @param unknown $data
	 * @return boolean
	 */
	private function __edit_target_area2($id , $data){

		// 指定の都道府県のjis_cdの範囲を取得
		$conditions = array('MPost.address1' => $data['address1_text']);
		$conditions_results = $this->MPost->find('first',
				array('fields' => 'max(MPost.jis_cd) , min(MPost.jis_cd)',
					'conditions' => $conditions ,
				)
		);

		if(empty($conditions_results) || empty($id)) return false;

		// 対応可能地域の削除
		$conditions = array('MCorpTargetArea.corp_id' =>  $id ,
						'MCorpTargetArea.jis_cd >=' =>  $conditions_results[0]['min'] ,
						'MCorpTargetArea.jis_cd <=' =>  $conditions_results[0]['max'] ,
					  );

// 		if(empty($data['jis_cd'])){
// 			return false;
// 		}
		// 2015.08.06 s.harada DEL start ORANGE-713
		/*
		if(isset($data['jis_cd'])){
			foreach ($data['jis_cd'] as $val){
				$conditions['MCorpTargetArea.jis_cd !='][] = $val;
			}
		}
		*/
		// 2015.08.06 s.harada DEL start ORANGE-713

		// 削除
		$resultsFlg = $this->MCorpTargetArea->deleteAll($conditions, false);

		if(!empty($data['jis_cd'])){
			// 対応可能地域の登録
			foreach ($data['jis_cd'] as $val){
				// 2015.08.06 s.harada MOD start ORANGE-713
				// $set_data['id'] = $data[$val];
				$set_data = array();
				// 2015.08.06 s.harada MOD end ORANGE-713
				$set_data['corp_id'] = $id;
				$set_data['jis_cd'] = $val;
				$save_data[] = array('MCorpTargetArea' => $set_data);
			}

			if ( !empty($save_data) ){
				// 登録
				if ( $this->MCorpTargetArea->saveAll($save_data) ) {
					return true;
				} else {
					return false;
				}
			}
		}
		return true;
	}

	/**
	 * 基本対応エリアの登録後にエリア未登録ジャンルへ基本対応エリアをエリア登録
	 *
	 * @param unknown $id
	 * @return boolean
	 */
	private function __edit_target_area_to_genre($id){

		$save_data = array();
		//企業エリアマスターの取得
// 2016.10.05 murata.s CHG(S) 脆弱性 SQLインジェクション対応
// 		$corp_areas = $this->MCorpTargetArea->find('all', array(
// 				'fields' => '*',
// 				'conditions' => array(
// 						'corp_id = ' . $id
// 				)
// 		)
// 		);
		$corp_areas = $this->MCorpTargetArea->find('all',
				array(
						'fields' => '*',
						'conditions' => array(
								'corp_id' => $id
						)
				)
		);
// 2016.10.05 murata.s CHG(E) 脆弱性 SQLインジェクション対応
		// 同一会社IDを全て更新
		$id_list = $this->MCorpCategory->getMCorpCategoryIDList2($id);
		foreach ($id_list as $key => $val) {
			// 指定した「corp_category_id」のエリア数
			$area_count = $this->MTargetArea->getCorpCategoryTargetAreaCount($val['MCorpCategory']['id']);
			if ($area_count > 0) {
				continue;
			}

			foreach ($corp_areas as $area) {
				$set_data = array();
				$set_data['corp_category_id'] = $val['MCorpCategory']['id'];
				$set_data['jis_cd'] = $area['MCorpTargetArea']['jis_cd'];
				$save_data[] = array('MTargetArea' => $set_data);
			}
		}
		if (count($save_data) > 0) {
			if ( $this->MTargetArea->saveAll($save_data) ) {
				return true;
			} else {
				return false;
			}
		} else {
			return true;
		}
	}

	/**
	 * 基本対応エリアの上書きボタン実行処理
	 *
	 * @param unknown $id
	 * @return boolean
	 */
	private function __edit_target_area_to_category($id, $genre_id){

		$save_data = array();
		//企業エリアマスターの取得
// 2016.10.05 murata.s CHG(S) 脆弱性 SQLインジェクション対応
// 		$corp_areas = $this->MCorpTargetArea->find('all', array(
// 				'fields' => '*',
// 				'conditions' => array(
// 						'corp_id = ' . $id
// 				)
// 		)
// 		);
		$corp_areas = $this->MCorpTargetArea->find('all',
				array(
						'fields' => '*',
						'conditions' => array(
								'corp_id' => $id
						)
				)
		);
// 2016.10.05 murata.s CHG(S) 脆弱性 SQLインジェクション対応
		// 同一会社IDを全て更新
		$id_list = $this->MCorpCategory->getMCorpCategoryIDList($id, $genre_id);
		foreach ($id_list as $key => $val) {
			// 指定した「corp_category_id」のエリア数
			$area_count = $this->MTargetArea->getCorpCategoryTargetAreaCount($val['MCorpCategory']['id']);
			if ($area_count > 0) {
				//エリアの再登録のため、一度カテゴリのエリアを全削除
				$result = $this->MTargetArea->deleteAll(array('corp_category_id = ' . $val['MCorpCategory']['id']), false);
			}

			foreach ($corp_areas as $area) {
				$set_data = array();
				$set_data['corp_category_id'] = $val['MCorpCategory']['id'];
				$set_data['jis_cd'] = $area['MCorpTargetArea']['jis_cd'];
				$save_data[] = array('MTargetArea' => $set_data);
			}
		}
		if (count($save_data) > 0) {
			if ( $this->MTargetArea->saveAll($save_data) ) {
				return true;
			} else {
				return false;
			}
		} else {
			return true;
		}
	}

	/**
	 * 対応可能地域の全登録
	 *
	 * @param unknown $id
	 */
	private function __all_regist_target_area($id){

		// 対応可能地域の削除
		$conditions = array('MCorpTargetArea.corp_id' =>  $id );

		$regist_already_data = $this->MCorpTargetArea->find('all',
				array('fields' => '*',
						'conditions' => $conditions ,
				)
		);

		$conditions = array();
		foreach ($regist_already_data as $val){
			$conditions['MPost.jis_cd !='][] = $val['MCorpTargetArea']['jis_cd'];
		}

		$results = $this->MPost->find('all',
				array('fields' => 'MPost.jis_cd',
						'joins' =>  array(
								array('fields' => '*',
										'type' => 'LEFT',
										"table" => "m_corp_target_areas",
										"alias" => "MCorpTargetArea",
										"conditions" => array("MCorpTargetArea.jis_cd = MPost.jis_cd" , "MCorpTargetArea.corp_id = ".$id)
								),
						),
						'conditions' => $conditions ,
						'group' => 'MPost.jis_cd , MPost.address2 , MCorpTargetArea.id',
						'order' => array('MPost.jis_cd'=> 'asc'),
				)
		);

		foreach ($results as $v){
			$data['MCorpTargetArea']['id'] = null;
			$data['MCorpTargetArea']['corp_id'] = $id;
			$data['MCorpTargetArea']['jis_cd'] = $v['MPost']['jis_cd'];
			$save_data[] = array('MCorpTargetArea' => $data['MCorpTargetArea']);
		}

		// 登録
		if ( !empty($save_data) ){
			if ( $this->MCorpTargetArea->saveAll($save_data) ) {
				return true;
			} else {
				return false;
			}
		} else {
			return true;
		}
	}


	// 2015.6.19 y.fujimori ADD start ORANGE-622
	/**
	 * 対応可能地域の全削除
	 *
	 * @param unknown $id
	 */
	private function __all_remove_target_area($id){

		// 対応可能地域の削除
		$conditions = array('MCorpTargetArea.corp_id' =>  $id );

		// 		if(empty($data['jis_cd'])){
		// 			return false;
		// 		}
		if(isset($data['jis_cd'])){
			foreach ($data['jis_cd'] as $val){
				$conditions['MCorpTargetArea.jis_cd !='][] = $val;
			}
		}

		// 削除
		$resultsFlg = $this->MCorpTargetArea->deleteAll($conditions, false);


		// 登録
		if ( !empty($save_data) ){
			if ( $this->MCorpTargetArea->saveAll($save_data) ) {
				return true;
			} else {
				return false;
			}
		} else {
			return true;
		}
	}
	// 2015.6.19 y.fujimori ADD end ORANGE-622

	//-- 基本対応エリアの設定用ファンクション群 end --//

	//-- ジャンル別対応エリアの設定用ファンクション群 start --//

	/**
	 * 対応可能地域の登録・削除
	 *
	 * @param unknown $id
	 * @param unknown $data
	 * @return boolean
	 */
	private function __edit_target_area3($id , $data){

		// 指定の都道府県のjis_cdの範囲を取得
		$conditions = array('MPost.address1' => $data['address1_text']);
		$conditions_results = $this->MPost->find('first',
				array('fields' => 'max(MPost.jis_cd) , min(MPost.jis_cd)',
						'conditions' => $conditions ,
				)
		);

		if(empty($conditions_results) || empty($id)) return false;

		// 対応可能地域の削除
		$conditions = array('MTargetArea.corp_category_id' =>  $id ,
				'MTargetArea.jis_cd >=' =>  $conditions_results[0]['min'] ,
				'MTargetArea.jis_cd <=' =>  $conditions_results[0]['max'] ,
		);

		// 		if(empty($data['jis_cd'])){
		// 			return false;
		// 		}
		// ジャンル配下のカテゴリ全て更新のため全削除後追加処理に変更
		/*
		if(isset($data['jis_cd'])){
			foreach ($data['jis_cd'] as $val){
				$conditions['MTargetArea.jis_cd !='][] = $val;
			}
		}
		*/

		// 削除
		$resultsFlg = $this->MTargetArea->deleteAll($conditions, false);

		if(!empty($data['jis_cd'])){
			// 対応可能地域の登録
			foreach ($data['jis_cd'] as $val){
				$set_data = array();
				//$set_data['id'] = $data[$val];
				$set_data['corp_category_id'] = $id;
				$set_data['jis_cd'] = $val;
				$set_data['address1_cd'] = substr($val, 0, 2);
				$save_data[] = array('MTargetArea' => $set_data);
			}

			if ( !empty($save_data) ){
				// 登録
				if ( $this->MTargetArea->saveAll($save_data) ) {
					return true;
				} else {
					return false;
				}
			}
		}
		return true;
	}

	/**
	 * 対応可能地域の全登録
	 *
	 * @param unknown $id
	 */
	private function __all_regist_target_area2($id){

		// 対応可能地域の削除
		$conditions = array('MTargetArea.corp_category_id' =>  $id );

		$regist_already_data = $this->MTargetArea->find('all',
				array('fields' => '*',
						'conditions' => $conditions ,
				)
		);

		$conditions = array();
		foreach ($regist_already_data as $val){
			$conditions['MPost.jis_cd !='][] = $val['MTargetArea']['jis_cd'];
		}

		$results = $this->MPost->find('all',
				array('fields' => 'MPost.jis_cd',
						'joins' =>  array(
								array('fields' => '*',
										'type' => 'LEFT',
										"table" => "m_target_areas",
										"alias" => "MTargetArea",
										"conditions" => array("MTargetArea.jis_cd = MPost.jis_cd" , "MTargetArea.corp_category_id = ".$id)
								),
						),
						'conditions' => $conditions ,
						'group' => 'MPost.jis_cd , MPost.address2 , MTargetArea.id',
						'order' => array('MPost.jis_cd'=> 'asc'),
				)
		);

		foreach ($results as $v){
			$data['MTargetArea']['id'] = null;
			$data['MTargetArea']['corp_category_id'] = $id;
			$data['MTargetArea']['jis_cd'] = $v['MPost']['jis_cd'];
			$data['MTargetArea']['address1_cd'] = substr($v['MPost']['jis_cd'], 0, 2);
			$save_data[] = array('MTargetArea' => $data['MTargetArea']);
		}

		// 登録
		if ( !empty($save_data) ){
			if ( $this->MTargetArea->saveAll($save_data) ) {
				return true;
			} else {
				return false;
			}
		} else {
			return true;
		}
	}
	//-- ジャンル別対応エリアの設定用ファンクション群 end --//

	/**
	 * 「m_corp_categories」の「target_area_type（対応可能エリアタイプ）」を更新
	 *
	 * @param unknown $id
	 */
	private function __edit_corp_category_target_area_type($id, $type) {

		$this->MCorpCategory->begin ();
		$data['MCorpCategory']['id'] = $id;
		$data['MCorpCategory']['target_area_type'] = $type;
		$data['MCorpCategory']['modified'] = false;

		$save_data = array (
				'MCorpCategory' => $data['MCorpCategory']
		);

		// 更新
		if ($this->MCorpCategory->save($save_data,false,array('target_area_type'))) {
			$this->MCorpCategory->commit ();
		} else {
			$this->MCorpCategory->rollback();
		}
	}

	// 2015.08.27 s.harada ADD end 画面デザイン変更対応

	// 2015.09.26 n.kai ADD start オークション選定対応
	/**
	 * オークション選定対象となるよう、夜間バッチを待たずに、affiliation_area_statsを生成する
	 *
	 * @param unknown $id
	 */
//	private function __edit_corp_category_target_area_type($id, $type) {
//
//		$this->MCorpCategory->begin ();
//		$data['MCorpCategory']['id'] = $id;
//		$data['MCorpCategory']['target_area_type'] = $type;
//		$data['MCorpCategory']['modified'] = false;
//
//		$save_data = array (
//				'MCorpCategory' => $data['MCorpCategory']
//		);
//
//		// 更新
//		if ($this->MCorpCategory->save($save_data,false,array('target_area_type'))) {
//			$this->MCorpCategory->commit ();
//		} else {
//			$this->MCorpCategory->rollback();
//		}
//	}
	private function __create_affiliation_area_stats_data($id) {
		$resultsFlg = false; // 更新結果フラグ

		$conditions = array();

		// 加盟状態 = 加盟
		$conditions['MCorp.affiliation_status'] = 1;
		$conditions['MCorp.id'] = $id;

		$fields = array(
				'MCorp.id',
				'MCorpCategory.genre_id',
		);

		$params = array(
				'fields' => $fields,
				'conditions' => $conditions,
		);

		// 検索
		$list = $this->MCorpCategory->find('all', $params);

		foreach($list as $r) {

			for($i = 1; $i <= 47; $i ++) {
				$params = array (
						'conditions' => array (
								'corp_id' => $r ['MCorp'] ['id'],
								'genre_id' => $r ['MCorpCategory'] ['genre_id'],
								'prefecture' => $i
						)
				);

				$data = $this->AffiliationAreaStat->find ( 'first', $params );

				if (count ( $data ) === 0) {
					$this->AffiliationAreaStat->save ( array (
							'corp_id' => $r ['MCorp'] ['id'],
							'genre_id' => $r ['MCorpCategory'] ['genre_id'],
							'prefecture' => $i,
							'commission_count_category' => 0,
							'orders_count_category' => 0,
							'created' => date ( "Y/m/d H:i:s", time () ),
							'created_user_id' => $this->User['user_id'],
							'commission_unit_price_rank' => 'z'
					), false );
					$this->AffiliationAreaStat->create();
				}
			}
		}
		return true;
	}

// 2015.09.26 n.kai ADD end
	private function __edit_corp_agreement($data){
		$this->CorpAgreement->set($data);
		// エラーチェック
		if (!$this->CorpAgreement->validates()) {
			return false;
		}
		if (!$this->CorpAgreement->save($data, false)) {
			// 登録失敗
			$this->CorpAgreement->rollback();
			return false;
		}
		return true;
	}

	private function __create_corp_agreement($data){
		$this->CorpAgreement->set($data);
		// エラーチェック
		if (!$this->CorpAgreement->validates()) {
			return false;
		}
// 2016.06.27 murata.s ORANGE-102 CHG(S)
// 		$this->CorpAgreement->save();

// 		$this->CorpAgreement->create();

// 		return true;

		$ca = $this->CorpAgreement->save();
		$this->CorpAgreement->create();
		return $ca;
// 2016.06.27 murata.s ORANGE-102 CHG(E)
	}

	// 2016,05.24 murata.s ORANGE-75 ADD(S)
	private function __edit_commission_accept($data){
		$this->MCorp->set($data);

		if(!$this->MCorp->save($data, false)){
			// 登録失敗
			return false;
		}
		return true;
	}
	// 2016.05.24 murata.s ORANGE-75 ADD(E)

	// ORANGE-1177 2016-03-28 iwai ADD(S)
	public function agreement_file_download($id=null){

// 2016.11.02 murata.s ORANGE-222 DEL(S)
// 		Configure::write('debug', 2);
// 2016.11.02 murata.s ORANGE-222 DEL(E)
// 2016.11.02 murata.s ORANGE-222 ADD(S)
		if(!ctype_digit($id)) throw new NotFoundException();
// 2016.11.02 murata.s ORANGE-222 ADD(E)

		$this->autoRender = false;

		$options = array(
			'conditions' => array('AgreementAttachedFile.id' => $id),
		);

		$aaf = $this->AgreementAttachedFile->find('first', $options);

		//ファイルがない場合は404
		if(!$aaf)throw new NotFoundException();

		//加盟店チェック
		if ($this->User['auth'] == 'affiliation') {
			//企業IDが異なる場合は404
			if($this->User['affiliation_id'] != $aaf['AgreementAttachedFile']['corp_id']){
				throw new NotFoundException();
			}
		}
		//ローカルファイルが存在すれば表示
		if (file_exists($aaf['AgreementAttachedFile']['path'])) {
			$this->response->file(
			 	$aaf['AgreementAttachedFile']['path'],
			 	array('name' => $aaf['AgreementAttachedFile']['name'], 'download'=>true,)
			 );

		    //$fp   = fopen($aaf['AgreementAttachedFile']['path'],'rb');
		    //$size = filesize($aaf['AgreementAttachedFile']['path']);
		    //$img  = fread($fp, $size);
		    //fclose($fp);
		    //header('Content-Type: application/force-download');

		    //echo $img;
		}else{
			echo 'ファイルがありません。';
		}

	}
	// ORANGE-1177 2016-03-28 iwai ADD(E)

	// 2016.05.16 ota.r@tobila ADD START ORANGE-23 加盟店契約内容入力画面  画像の削除
	/**
	 * ファイルの削除処理
	 *
	 * @param unknown $corp_id
	 * @param unknown $corp_agreement_id
	 * @param number $file_id
	 * @throws InternalErrorException
	 */
	public function agreement_file_delete($corp_id=null, $corp_agreement_id=null){

		$success_msg    = 'ファイルを削除しました。';                // 成功メッセージ
		$error_msg      = 'ファイルが存在しません。';                // ユーザエラーのメッセージ
		$s_msg_class    = array('class' => 'message_inner');         // 成功メッセージクラス
		$e_msg_class    = array('class' => 'error_inner');           // エラーメッセージクラス

		/*
		 * Post値のチェック
		 * 削除ファイルのidが渡ってきていないときは異常な状態のため、システムエラー
		 */
		if (empty($this->request->data['delete_agreement_file'])) {
			throw new InternalErrorException();
		}

		/* idの取得 */
		$file_id = $this->request->data['delete_agreement_file'];

		/* 削除したいファイル情報をDBから取得 */
		$options = array(
				'conditions' => array(
						'AgreementAttachedFile.corp_id'           => $corp_id,
						'AgreementAttachedFile.id'                => $file_id,
				),
		);
		$aaf = $this->AgreementAttachedFile->find('first', $options);
		if (count($aaf) == 0) {
			throw new InternalErrorException();
		}

		/* 情報を変数に代入 */
		$file_path   = $aaf['AgreementAttachedFile']['path'];
		$create_date = strtotime($aaf['AgreementAttachedFile']['create_date']);

		/* 前回承認日を取得 */
		$a_opts = array(
				'conditions' => array(
						'CorpAgreement.corp_id' => $corp_id,
						'OR' => array(
								array('CorpAgreement.status' => 'Complete'),
								array('CorpAgreement.status' => 'Application'),
						),
				),
				'order' => array('CorpAgreement.id DESC'),
		);
		$agreement_data = $this->CorpAgreement->find('first', $a_opts);
		$update_date = empty($agreement_data['CorpAgreement']['update_date']) ? 0 : strtotime($agreement_data['CorpAgreement']['acceptation_date']);

		/*
		 * 前回承認日より前に作成されたファイルは削除させない
		 * (すでに承認済みのファイルは削除させない)
		 */
		if ($create_date < $update_date) {
			/* ユーザエラーにする */
			$error_msg = 'すでに承認されているライセンスは削除できません。';
			$this->Session->setFlash ($error_msg, 'default', $e_msg_class);
			return $this->redirect('/affiliation/agreement/'.$corp_id);
		}

		/* トランザクション開始 */
		$this->AgreementAttachedFile->begin();
// 2016.06.23 murata.s ORANGE-54 ADD(S)
		// 2017/10/18 ORANGE-541 m-kawamoto DEL(S)
		//$this->CorpLisenseLink->begin();
		// 2017/10/18 ORANGE-541 m-kawamoto DEL(E)

		$del_attached_file = $this->AgreementAttachedFile->findById($file_id);
		// 2017/10/18 ORANGE-541 m-kawamoto DEL(S)
		//$savedata_lisense = array();
		//if(!empty($del_attached_file)
		//		&& $del_attached_file['AgreementAttachedFile']['kind'] == 'License'){
		//	$savedata_lisense = $this->CorpLisenseLink->find('first', array(
		//			'conditions' => array(
		//					'corps_id' => $corp_id,
		//					'lisense_id' => $del_attached_file['AgreementAttachedFile']['license_id']
		//			)
		//	));
		//	$savedata_lisense['CorpLisenseLink']['lisense_check'] = 'None';
		//}
		// 2017/10/18 ORANGE-541 m-kawamoto DEL(E)
// 2016.06.23 murata.s ORANGE-54 ADD(E)

		/*
		 *  DBからデータを削除
		 * (片一方の情報がロストするのを防ぐため, DB->ファイルの順に削除し、こけたときrollback)
		 */
		if (!$this->AgreementAttachedFile->delete($file_id)) {
			$this->AgreementAttachedFile->rollback();
// 2016.06.23 murata.s ORANGE-54 ADD(S)
// 2017/10/18 ORANGE-541 m-kawamoto DEL(S)
//			$this->CorpLisenseLink->rollback();
// 2017/10/18 ORANGE-541 m-kawamoto DEL(E)
// 2016.06.23 murata.s ORANGE-54 ADD(E)
			throw new InternalErrorException();
		}

// 2016.06.23 murata.s ORANGE-54 ADD(S)
// 2017/10/18 ORANGE-541 m-kawamoto DEL(S)
//		if(!empty($savedata_lisense)){
//			if(!$this->CorpLisenseLink->save($savedata_lisense)){
//				$this->AgreementAttachedFile->rollback();
//				$this->CorpLisenseLink->rollback();
//				throw new InternalErrorException();
//			}
//		}
// 2017/10/18 ORANGE-541 m-kawamoto DEL(E)
// 2016.06.23 murata.s ORANGE-54 ADD(E)

		/*
		 * 実ファイルの存在を確認
		 * ファイルが存在しない場合は、ここでトランザクションを完了して、
		 * ユーザエラーにする
		 */
		if (!is_file($file_path)) {
			$this->AgreementAttachedFile->commit();
// 2016.06.23 murata.s ORANGE-54 ADD(S)
// 2017/10/18 ORANGE-541 m-kawamoto DEL(S)
//			$this->CorpLisenseLink->commit();
// 2017/10/18 ORANGE-541 m-kawamoto DEL(E)
// 2016.06.23 murata.s ORANGE-54 ADD(E)
			$error_msg = 'ファイルが存在しません。';
			$this->Session->setFlash ($error_msg, 'default', $e_msg_class);
			return $this->redirect('/affiliation/agreement/'.$corp_id);
		}

		/* 実ファイルを削除 */
		if (!unlink($file_path)) {
			$this->AgreementAttachedFile->rollback();
// 2016.06.23 murata.s ORANGE-54 ADD(S)
// 2017/10/18 ORANGE-541 m-kawamoto DEL(S)
//			$this->CorpLisenseLink->rollback();
// 2017/10/18 ORANGE-541 m-kawamoto DEL(E)
// 2016.06.23 murata.s ORANGE-54 ADD(E)
			throw new InternalErrorException();
		}

		$this->AgreementAttachedFile->commit();
// 2016.06.23 murata.s ORANGE-54 ADD(S)
// 2017/10/18 ORANGE-541 m-kawamoto DEL(S)
//		$this->CorpLisenseLink->commit();
// 2017/10/18 ORANGE-541 m-kawamoto DEL(E)
// 2016.06.23 murata.s ORANGE-54 ADD(E)

		$this->Session->setFlash ($success_msg, 'default', $s_msg_class);
		return $this->redirect('/affiliation/agreement/'.$corp_id);
	}
	// 2016.05.16 ota.r@tobila ADD END ORANGE-23 加盟店契約内容入力画面  画像の削除

	// 2016.05.02 ota.r@tobila ADD START ORANGE-1363 契約約款内容確認画面から必要書類のアップロード
	 // 2017/10/18 ORANGE-541 m-kawamoto CHG(S) license引数削除
	/**
	 * ファイルアップロード後の処理
	 * ライセンスモードの時は該当のライセンスと紐づけて登録を行う
	 *
	 * @param unknown $corp_id
	 * @param unknown $corp_agreement_id
	 * @throws InternalErrorException
	 * @throws ForbiddenException
	 */
	public function agreement_file_upload($corp_id=null, $corp_agreement_id=null){
	 // 2017/10/18 ORANGE-541 m-kawamoto CHG(E)

		/* 共通変数の初期化 */
		$today          = date('Y-m-d h:i:s');                       // 実行日の日付(なるべくユニークにするため秒まで)
		$user_id        = getmyuid();                                // 実行ユーザID
		$version        = 1;                                         // 登録バージョン(決め打ち1)
		$prefix         = '/var/www/htdocs/rits-files';              // ファイル保存先のprefix
		$maxsize        = 20971520;                                  // アップロードの最大サイズ(20MB)
		$success_msg    = 'ファイルをアップロードしました。';        // 成功メッセージ
		$s_msg_class    = array('class' => 'message_inner');         // 成功メッセージクラス
		$e_msg_class    = array('class' => 'error_inner');           // エラーメッセージクラス
		$allow_type_arr = array(                                     // 許可するファイルタイプ
				'image/bmp'       => true,
				'image/jpeg'      => true,
				'image/png'       => true,
				'application/pdf' => true,
		);

		// 2016.05.16 ota.r@tobila ADD START ORANGE-23 加盟店契約内容入力画面  画像の削除
		/*
		 * ライセンスの削除を行う場合は、agreement_file_delete()を実行
		 * 削除完了後、終了する
		 */
		if (!empty($this->request->data['delete_agreement_file'])) {
			$this->agreement_file_delete($corp_id, $corp_agreement_id);
			$this->render('agreement');
			return;
		}
		// 2016.05.16 ota.r@tobila ADD START ORANGE-23 加盟店契約内容入力画面  画像の削除

		// 2016.05.10 murata.s ORANGE-1210 ADD(S)
		// ライセンス確認のフォームがagreement_file_uploadと同じになっているため、
		// update-corp-licenseの場合はアップロードは行わないようにする
		if(isset($this->request->data['update-corp-license'])){
			$this->agreement($corp_id, $corp_agreement_id);
			$this->render('agreement');
			return;
		}
		// 2016.05.10 murata.s ORANGE-1210 ADD(E)

		/*
		 * データの取得
		 * 必要書類アップロードモードとライセンスアップロードモードで取得データを変える
		 */
		$savedata = array();                                                      // DB登録用配列の初期化
// 2016.06.23 murata.s ORANGE-54 ADD(S)
// 2017/10/18 ORANGE-541 m-kawamoto DEL(S)
//		$savedata_lisense = array();
// 2017/10/18 ORANGE-541 m-kawamoto DEL(E)
// 2016.06.23 murata.s ORANGE-54 ADD(E)

// 2017/10/18 ORANGE-541 m-kawamoto DEL(S)
//		if ($license_mode != 0) {
//
//			/*
//			 * IE, Edge対策
//			 * ラジオボタンが選択されていない状況でアップロードボタンが押された場合ユーザエラー
//			 *
//			 */
//			if (!isset($this->request->data['license_radio'])) {
//				$msg = 'ライセンスが選択されていません。';
//				$this->Session->setFlash ($msg, 'default', $e_msg_class);
//				return $this->redirect('/affiliation/agreement/'.$corp_id);
//			}
//
//			$kind = 'License';                                                    // ファイル種別(License)
//			$input_data = $this->request->params['form']['upload_license_path'];  // ライセンスアップロード
//			$license_id = $this->request->data['license_radio'];
//			$savedata['AgreementAttachedFile']['license_id']
//			            = $this->request->data['license_radio'];                  // ライセンスID
//// 2016.06.23 murata.s ORANGE-54 ADD(S)
//			$savedata_lisense = $this->CorpLisenseLink->find('first', array(
//					'conditions' => array(
//							'corps_id' => $corp_id,
//							'lisense_id' => $license_id
//					)
//			));
//			$savedata_lisense['CorpLisenseLink']['lisense_check'] = 'None';
//// 2016.06.23 murata.s ORANGE-54 ADD(E)
//		} else {
// 2017/10/18 ORANGE-541 m-kawamoto DEL(E)
			$kind = 'Cert';                                                       // ファイル種別(Cert)
			$input_data = $this->request->params['form']['upload_file_path'];     // 必要書類アップロード

// 2017/10/18 ORANGE-541 m-kawamoto DEL(S)
//		}
// 2017/10/18 ORANGE-541 m-kawamoto DEL(E)

		/*
		 * IE, Edge対策
		 * ファイル名が指定されていない状況でアップロードボタンが押された場合ユーザエラー
		 */
		if (empty($input_data['name'])) {
			$msg = 'アップロード対象ファイルが選択されていません。';
			$this->Session->setFlash ($msg, 'default', $e_msg_class);
			return $this->redirect('/affiliation/agreement/'.$corp_id);
		}

		$ext = end(explode('.', $input_data['name']));  // 拡張子の取得

		/*
		 * temporaryファイルのチェック
		 * temporaryファイルが存在しないしない場合、読み込み不可の場合はシステムエラー
		 */
		if (!is_file($input_data['tmp_name'])) {
			throw new InternalErrorException();
		}
		if (!is_readable($input_data['tmp_name'])) {
			throw new ForbiddenException();  // 表示されるのは404
		}

		/*
		 * サイズ、ファイル形式が不正な場合はユーザエラー
		 * 許可するファイル形式はPDF, JPEG, PNG, bitmap
		 */
		if ($input_data['size'] > $maxsize) {
			$msg       = 'アップロードするファイルのサイズが大きすぎます。';
			$this->Session->setFlash ($msg, 'default', $e_msg_class);
			return $this->redirect('/affiliation/agreement/'.$corp_id);
		}
		if (!isset($allow_type_arr[$input_data['type']])) {
			$msg = 'アップロードするファイルの形式が不正です。';
			$this->Session->setFlash ($msg, 'default', $e_msg_class);
			return $this->redirect('/affiliation/agreement/'.$corp_id);
		}

		$savedir = $prefix . '/' . $corp_id . '/';

// 2016.07.05 ota.r@tobila CHG(S) ORANGE-91
//		/* umaskの退避、一時変更 */
//		$mask = umask();
//		umask(000);
// 2016.07.05 ota.r@tobila CHG(E) ORANGE-91

		if (!is_dir($savedir)){
			/* 保存ディレクトリがない場合は作成 */
// 2016.07.05 ota.r@tobila CHG(S) ORANGE-91
//			if (!mkdir($savedir, 0777, true)) {
			if (!mkdir($savedir, 0755, true)) {
// 2016.07.05 ota.r@tobila CHG(E) ORANGE-91
				throw new InternalErrorException();
			}
		}
		if (!is_writable($savedir)) {
			throw new ForbiddenException();  // 表示されるのは404
		}

		/* umaskの復帰 */
// 2016.07.05 ota.r@tobila CHG(S) ORANGE-91
//		umask($mask);
// 2016.07.05 ota.r@tobila CHG(E) ORANGE-91

		/* DB更新 */
		$savedata['AgreementAttachedFile']['corp_id']           = $corp_id;
		$savedata['AgreementAttachedFile']['corp_agreement_id'] = $corp_agreement_id;
		$savedata['AgreementAttachedFile']['kind']              = $kind;
		$savedata['AgreementAttachedFile']['path']              = '';        // ここでは確定していないので空
		$savedata['AgreementAttachedFile']['name']              = $input_data['name'];
		$savedata['AgreementAttachedFile']['content_type']      = $input_data['type'];
		$savedata['AgreementAttachedFile']['version_no']        = $version;
		$savedata['AgreementAttachedFile']['create_date']       = $today;    // 登録日は実行日になる
		$savedata['AgreementAttachedFile']['create_user_id']    = $user_id;  // 登録ユーザは実行ユーザになる
		$savedata['AgreementAttachedFile']['update_date']       = $today;    // 更新日は実行日時になる
		$savedata['AgreementAttachedFile']['update_user_id']    = $user_id;  // 更新ユーザは実行ユーザになる

		/* トランザクション開始 */
		$this->AgreementAttachedFile->begin();
// 2016.06.23 murata.s ORANGE-54 ADD(S)
// 2017/10/18 ORANGE-541 m-kawamoto DEL(S)
//		$this->CorpLisenseLink->begin();
// 2017/10/18 ORANGE-541 m-kawamoto DEL(E)
// 2016.06.23 murata.s ORANGE-54 ADD(E)

		/* DB登録 */
		if (!$this->AgreementAttachedFile->save($savedata)) {
			$this->AgreementAttachedFile->rollback();
// 2016.06.23 murata.s ORANGE-54 ADD(S)
// 2017/10/18 ORANGE-541 m-kawamoto DEL(S)
//			$this->CorpLisenseLink->rollback();
// 2017/10/18 ORANGE-541 m-kawamoto DEL(E)
// 2016.06.23 murata.s ORANGE-54 ADD(E)
			throw new InternalErrorException();
		}

// 2016.06.23 murata.s ORANGE-54 ADD(S)
// 2017/10/18 ORANGE-541 m-kawamoto DEL(S)
//		if(!empty($savedata_lisense)){
//			if(!$this->CorpLisenseLink->save($savedata_lisense)){
//				$this->AgreementAttachedFile->rollback();
//				$this->CorpLisenseLink->rollback();
//				throw new InternalErrorException();
//			}
//		}
// 2017/10/18 ORANGE-541 m-kawamoto DEL(E)
// 2016.06.23 murata.s ORANGE-54 ADD(E)
		/*
		 * アップロードファイルが登録されているかを確認
		 * なるべく一意に取得するため、登録日も指定して取得
		 */
		$options = array(
				'conditions' => array(
						'AgreementAttachedFile.corp_id'           => $corp_id,
						'AgreementAttachedFile.corp_agreement_id' => $corp_agreement_id,
						'AgreementAttachedFile.name'              => $input_data['name'],
						'AgreementAttachedFile.content_type'      => $input_data['type'],
						'AgreementAttachedFile.create_date'       => $today,
				),
		);
		$aaf = $this->AgreementAttachedFile->find('first', $options);
		if (count($aaf) == 0) {
			$this->AgreementAttachedFile->rollback();
// 2016.06.23 murata.s ORANGE-54 ADD(S)
// 2017/10/18 ORANGE-541 m-kawamoto DEL(S)
//			$this->CorpLisenseLink->rollback();
// 2017/10/18 ORANGE-541 m-kawamoto DEL(E)
// 2016.06.23 murata.s ORANGE-54 ADD(E)
			throw new InternalErrorException();
		}

		/* 登録したIDを取得して、リネーム先のフルパスを作成 */
        $id = $aaf['AgreementAttachedFile']['id'];
		$filepath = $savedir . $id . '.' . $ext;

		/* temporaryファイルをリネーム */
		if (!rename($input_data['tmp_name'], $filepath)) {
			$this->AgreementAttachedFile->rollback();
// 2016.06.23 murata.s ORANGE-54 ADD(S)
// 2017/10/18 ORANGE-541 m-kawamoto DEL(S)
//			$this->CorpLisenseLink->rollback();
// 2017/10/18 ORANGE-541 m-kawamoto DEL(E)
// 2016.06.23 murata.s ORANGE-54 ADD(E)
			throw new InternalErrorException();
		}

// 2016.07.05 ota.r@tobila CHG(S) ORANGE-91
//		/* 権限変更 */
//		if (!chmod($filepath, 0777)) {
//			unlink($filepath);
//			$this->AgreementAttachedFile->rollback();
//// 2016.06.23 murata.s ORANGE-54 ADD(S)
//			$this->CorpLisenseLink->rollback();
//// 2016.06.23 murata.s ORANGE-54 ADD(E)
//			throw new InternalErrorException();
//		}
// 2016.07.05 ota.r@tobila CHG(E) ORANGE-91

		/* DBにフルパスを登録 */
		$update_data = array();
		$update_data['AgreementAttachedFile']['id']   = $id;
		$update_data['AgreementAttachedFile']['path'] = $filepath;
		if (!$this->AgreementAttachedFile->save($update_data)) {
			$this->AgreementAttachedFile->rollback();
// 2016.06.23 murata.s ORANGE-54 ADD(S)
// 2017/10/18 ORANGE-541 m-kawamoto DEL(S)
//			$this->CorpLisenseLink->rollback();
// 2017/10/18 ORANGE-541 m-kawamoto DEL(E)
// 2016.06.23 murata.s ORANGE-54 ADD(E)
			unlink($filepath);
			throw new InternalErrorException();
		}

		$this->AgreementAttachedFile->commit();
// 2016.06.23 murata.s ORANGE-54 ADD(S)
// 2017/10/18 ORANGE-541 m-kawamoto DEL(S)
//		$this->CorpLisenseLink->commit();
// 2017/10/18 ORANGE-541 m-kawamoto DEL(E)
// 2016.06.23 murata.s ORANGE-54 ADD(E)

		// 2016.05.13 ota.r@tobila ADD START ORANGE-57 【契約システム】契約約款確認画面で、必要書類とライセンスファイルをアップした際にメールを送る
		$this->__send_upload_file_alert($corp_id);
		// 2016.05.13 ota.r@tobila ADD END ORANGE-57 【契約システム】契約約款確認画面で、必要書類とライセンスファイルをアップした際にメールを送る

		$this->Session->setFlash ($success_msg, 'default', $s_msg_class);
		return $this->redirect('/affiliation/agreement/'.$corp_id);
	}
	// 2016.05.02 ota.r@tobila ADD END ORANGE-1363 契約約款内容確認画面から必要書類のアップロード

	// 2016.05.13 ota.r@tobila ADD START ORANGE-57 【契約システム】契約約款確認画面で、必要書類とライセンスファイルをアップした際にメールを送る
	private function __send_upload_file_alert($corp_id) {

		/* 値の設定 */
// 2016.09.23 murata.s ORANGE-193 CHG(S)
		$url      = "https://".DOMAIN_NAME."/affiliation/agreement/" . $corp_id;
// 2016.09.23 murata.s ORANGE-193 CHG(E)
		$to_addr  = Util::getDivText('agreement_upload_mail_setting', 'to_address');
		$header   = 'From: ' . Util::getDivText('agreement_upload_mail_setting', 'from_address');
		$tmp_sub  = Util::getDivText('agreement_upload_mail_setting', 'title');
		$template = Util::getDivText('agreement_upload_mail_setting', 'contents');

		$subject = sprintf($tmp_sub, $corp_id);
		$body = sprintf($template, $url);

		/* メールの送信 */
		mb_send_mail($to_addr, $subject, $body, $header);

		return;
	}
	// 2016.05.13 ota.r@tobila ADD END ORANGE-57 【契約システム】契約約款確認画面で、必要書類とライセンスファイルをアップした際にメールを送る

	// 2016.04.27 ota.r@tobila ADD START
	// ORANGE-1377【契約管理】契約約款内容確認画面(affiliaion/agreement/)で「承認する」で登録した場合に、定型文メールの自動送信
	private function __send_agreement_mail($corp_data, $corp_agreement) {

		/* メールヘッダー設定 */
		mb_language('Ja');
		mb_internal_encoding('UTF-8');

		/* メールテンプレートの読み出し */
		$template = Util::getDivText('agreement_alert_mail_setting', 'contents');

		/* 値の設定 */
		$body = sprintf($template, $corp_data['MCorp']['official_corp_name']);

		/*
		 * 必要情報の設定
		 *  - ヘッダ
		 *  - Bcc
		 *  - 件名
		 */
#		$header = 'From: ' . Util::getDivText('agreement_alert_mail_setting', 'from_address')
#		          . PHP_EOL
#		          . "Bcc:" . Util::getDivText('bcc_mail', 'to_address');
		$header = 'From: ' . Util::getDivText('agreement_alert_mail_setting', 'from_address');
		$subject = Util::getDivText('agreement_alert_mail_setting', 'title');

		/*
		 * PCメールと携帯メールを","でつなぎ、
		 * 送信先アドレスリストを作成する
		 */
		$addr_arr = array();  // 初期化
		$tmp_addrs = array();
		if (!empty($corp_data['MCorp']['mailaddress_pc'])) {
			/* 複数指定されている可能性があるので、セミコロンで分割 */
			$tmp_addrs = explode(";", $corp_data['MCorp']['mailaddress_pc']);
			/* すべてのメールアドレスを格納 */
			foreach ($tmp_addrs as $one_addr) {
				$addr_arr[] = $one_addr;
			}
		}
		$tmp_addrs = array();
		if (!empty($corp_data['MCorp']['mailaddress_mobile'])) {
			/* 複数指定されている可能性があるので、セミコロンで分割 */
			$tmp_addrs = explode(";", $corp_data['MCorp']['mailaddress_mobile']);
			/* すべてのメールアドレスを格納 */
			foreach ($tmp_addrs as $one_addr) {
				$addr_arr[] = $one_addr;
			}
		}
		if(count($addr_arr) != 0) {
			$to_addr = implode(",", $addr_arr);
		} else {
			return false;
		}

		/* メール送信 */
		if (!mb_send_mail($to_addr, $subject, $body, $header)) {
			/* メール送信に失敗した場合、ユーザエラーにする */
			return false;
		}

		return true;
	}
	// 2016.04.27 ota.r@tobila ADD END
	// ORANGE-1377【契約管理】契約約款内容確認画面(affiliaion/agreement/)で「承認する」で登録した場合に、定型文メールの自動送信

	// 2016.05.17 ORANGE-64 ADD(S)
	/**
	 * 契約新規追加
	 * @param unknown $corp_id
	 * @param string $kind
	 */
	function add_agreement($corp_id =null, $kind = 'fax'){
		//postのみ登録
		if($this->request->is('post')){

			try{
				//2016.4.6 ORANGE-1341 iwai CHG(E)
				if(!empty($this->request->data['CorpAgreement'])){
					$corp_agreement['CorpAgreement'] = $this->request->data['CorpAgreement'];
					$corp_id = $corp_agreement['CorpAgreement']['corp_id'];

					// Transaction start
					$this->CorpAgreement->begin ();
// 2016.06.27 murata.s ORANGE-102 ADD(S)
					$this->CorpAgreementTempLink->begin();
// 2016.06.27 murata.s ORANGE-102 ADD(E)
// 2016.12.15 murata.s ORANGE-280 ADD(S)
					$this->MCorpCategoriesTemp->begin();
					$this->MCorpCategory->begin();
// 2016.12.15 murata.s ORANGE-280 ADD(E)
					$corp_agreement['CorpAgreement']['status'] = 'NotSigned';
					$corp_agreement['CorpAgreement']['agreement_history_id'] = 1;
					$corp_agreement['CorpAgreement']['ticket_no'] = 1;
					if(isset($this->request->data['CorpAgreement']['agreement_date']))
						$corp_agreement['CorpAgreement']['agreement_date'] = $this->request->data['CorpAgreement']['agreement_date'];

					if(isset($this->request->data['CorpAgreement']['agreement_flag']))
						$corp_agreement['CorpAgreement']['agreement_flag'] = $this->request->data['CorpAgreement']['agreement_flag'] ? true : false;

					if(isset($this->request->data['CorpAgreement']['corp_kind']))
						$corp_agreement['CorpAgreement']['corp_kind'] = $this->request->data['CorpAgreement']['corp_kind'];

					if(isset($this->request->data['CorpAgreement']['hansha_check'])) {
						$corp_agreement['CorpAgreement']['hansha_check'] = $this->request->data['CorpAgreement']['hansha_check'];
						$corp_agreement['CorpAgreement']['hansha_check_user_id'] = $this->User['user_id'];
						$corp_agreement['CorpAgreement']['hansha_check_date'] = date('Y-m-d H:i:s');
					}

					if(isset($this->request->data['CorpAgreement']['transactions_law'])
						&& $this->request->data['CorpAgreement']['transactions_law'] == 1) {
						$corp_agreement['CorpAgreement']['transactions_law_user_id'] = $this->User['user_id'];
						$corp_agreement['CorpAgreement']['transactions_law_date'] = date('Y-m-d H:i:s');
					}

					if(isset($this->request->data['CorpAgreement']['acceptation'])
							&& $this->request->data['CorpAgreement']['acceptation'] == 1) {
						$corp_agreement['CorpAgreement']['status'] = 'Complete';
						$corp_agreement['CorpAgreement']['acceptation_user_id'] = $this->User['user_id'];
						$corp_agreement['CorpAgreement']['acceptation_date'] = date('Y-m-d H:i:s');
					}


					//データ登録
// 2016.12.15 murata.s ORANGE-280 CHG(S)
// // 2016.06.27 murata.s ORANGE-102 CHG(S)
// // 					if(self::__create_corp_agreement($corp_agreement)){
// // 						//transaction success
// // 				        $this->CorpAgreement->commit ();
// // 				        $this->Session->setFlash ( __ ( 'Updated', true ), 'default', array ('class' => 'message_inner'));
// // 				        $this->redirect(array('action' => 'agreement', $corp_id));
// // 					}else{
// // 						//transaction faild
// // 						$this->CorpAgreement->rollback ();
// // 						$this->Session->setFlash ( __ ( 'NotEmptyCorpAgreement', true ), 'default', array ('class' => 'error_inner'));
// // 					}
// 					$ca = self::__create_corp_agreement($corp_agreement);
// 					if($ca
// 							&& self::__insert_corp_agreement_temp_link($corp_id, $ca['CorpAgreement']['id'])){
// 						//transaction success
// 				        $this->CorpAgreement->commit ();
// 				        $this->CorpAgreementTempLink->commit();
// 				        $this->Session->setFlash ( __ ( 'Updated', true ), 'default', array ('class' => 'message_inner'));
// 				        $this->redirect(array('action' => 'agreement', $corp_id));
// 					}else{
// 						//transaction faild
// 						$this->CorpAgreement->rollback ();
// 						$this->CorpAgreementTempLink->rollback();
// 						$this->Session->setFlash ( __ ( 'NotEmptyCorpAgreement', true ), 'default', array ('class' => 'error_inner'));
// 					}
// // 2016.06.27 murata.s ORANGE-102 CHG(E)
					// 一時カテゴリデータを登録
					$temp_link = self::__copy_agreement_tmp($corp_id);
					$temp_id = $temp_link['CorpAgreementTempLink']['id'];
					if($this->__get_category_temp_data_count($corp_id, $temp_id) == 0){
						// 一時カテゴリデータの登録
						$save_data = $this->__find_categories_temp_copy($corp_id, $temp_id);
						if(!empty($save_data))
							$this->MCorpCategoriesTemp->saveAll($save_data);
					}

					// corp_agreementを登録
					$ca = self::__create_corp_agreement($corp_agreement);

					// 一時データとcorp_agreementの紐付け
					$temp_link['CorpAgreementTempLink']['corp_agreement_id'] = $ca['CorpAgreement']['id'];
					$temp_link = $this->CorpAgreementTempLink->save($temp_link);

					// 企業別カテゴリマスタにデータを登録
					self::__update_original_category_data($ca);

					if($ca && $temp_link){
						// transaction success
						$this->CorpAgreement->commit ();
						$this->CorpAgreementTempLink->commit();
						$this->MCorpCategoriesTemp->commit();
						$this->MCorpCategory->commit();
						$this->Session->setFlash ( __ ( 'Updated', true ), 'default', array ('class' => 'message_inner'));
						$this->redirect(array('action' => 'agreement', $corp_id));
					}else{
						// transaction faild
						$this->CorpAgreement->rollback ();
						$this->CorpAgreementTempLink->rollback();
						$this->MCorpCategoriesTemp->rollback();
						$this->MCorpCategory->rollback();
						$this->Session->setFlash ( __ ( 'NotEmptyCorpAgreement', true ), 'default', array ('class' => 'error_inner'));
					}
// 2016.12.15 murata.s ORANGE-280 CHG(E)
				}
			}catch ( Exception $e ) {
				$this->CorpAgreement->rollback();
// 2016.06.27 murata.s ORANGE-102 ADD(S)
				$this->CorpAgreementTempLink->rollback();
// 2016.06.27 murata.s ORANGE-102 ADD(E)
// 2016.12.15 murata.s ORANGE-280 ADD(S)
				$this->MCorpCategoriesTemp->rollback();
				$this->MCorpCategory->rollback();
// 2016.12.15 murata.s ORANGE-280 ADD(E)
				$this->Session->setFlash ( __ ( 'NotEmptyCorpAgreement', true ), 'default', array ('class' => 'error_inner'));
			}

		}elseif($this->request->is('get')){
			//getの場合、企業IDをFormにセットする

		}
		// 企業情報を取得 $corp_data
		$corp_data = $this->MCorp->find ( 'first',
			array (
				'conditions' => array ('MCorp.id' => $corp_id),
			)
		);
		$this->set(compact('corp_data'));

	}
	// 2016.05.17 ORANGE-64 ADD(E)

	// 2016.05.30 ota.r@tobila ADD START ORANGE-30 【契約管理】契約規約の特約や条文の変更・削除・追加部分が分かる出力PDFの作成
	function agreement_terms_download($corp_id =null, $agreement_id=null) {

Configure::write('debug', 0);

		$original_terms = array();
		$custom_terms   = array();
		$tmp_org        = array();
		$tmp_customize  = array();

		/* 企業情報の取得 */
		$corp_data = $this->MCorp->find ( 'first', array (
				'fields'     => '*',
				'conditions' => array (
					'MCorp.id' => $corp_id,
				),
				'order'      => array (
					'MCorp.id' => 'asc'
				),
		) );

		/* 契約情報の取得 */
		if (!empty($agreement_id)) {
			$agreement_data = $this->CorpAgreement->find ( 'first', array (
					'fields' => '*',
					'conditions' => array (
							'CorpAgreement.corp_id' => $corp_id,
							'CorpAgreement.id' => $agreement_id,
					),
			));

		} else {
			$agreement_data = $this->CorpAgreement->find ( 'first', array (
					'fields' => '*',
					'conditions' => array (
							'CorpAgreement.corp_id' => $corp_id
					),
					'order' => array('CorpAgreement.id' =>'desc'),
			));
			$agreement_id = $agreement_data['CorpAgreement']['id'];
		}

		/* 差分のある規約の条項を取得 */
		$diff_arr = $this->__getDiffProv($corp_id, $agreement_id);

		$this->PDF->agreement_terms($corp_data, $agreement_data, $diff_arr);

		return;
	}

	/**
	 *
	 * @param unknown $corp_id
	 * @param unknown $agreement_id
	 * @return string[]
	 */
	private function __getDiffProv ($corp_id, $agreement_id) {

		/* ---------- ---------- ----------
		 * 特約のうち、条項そのものを変更、追加したものを取得
		 * ---------- ---------- ----------
		 */
		$custom_prov = $this->AgreementCustomize->find(
				'all',
				array(
						'fields' => '*',
						'conditions' => array(
								'AgreementCustomize.corp_id'                   => $corp_id,
								'AgreementCustomize.corp_agreement_id <='      => $agreement_id,
								'AgreementCustomize.table_kind'                => 'AgreementProvisions',
						),
						'order' => array('AgreementCustomize.id' => 'asc'),
				)
		);

		$title_list = array();  // 変更された条項リスト
		$cst_list = array();    // 追加された条項リスト
		foreach ($custom_prov as $one) {
			$org_id   = $one['AgreementCustomize']['original_provisions_id'];   // オリジナルの条項ID
			$cst_id   = $one['AgreementCustomize']['customize_provisions_id'];  // カスタマイズの条項ID
			$c_agr_id = $one['AgreementCustomize']['corp_agreement_id'];        // 取得したデータのcorp_agreement_id

			/*
			 * オリジナルの条項のIDをキーにして配列に格納
			 * オリジナルIDが存在しない場合、カスタムリストに追加
			 * すでに存在する場合、corp_agreement_idの大きいほうを採用
			 */
			if ($org_id == 0) {
				if (!isset($cst_list[$cst_id]) || $c_agr_id >= $cst_list[$cst_id]['corp_agreement_id']) {
					if ($one['AgreementCustomize']['edit_kind'] != 'Delete') {
						$cst_list[$cst_id] = $one['AgreementCustomize'];
					}
					/* 削除された条項はリストから消す */
					else {
						unset($cst_list[$cst_id]);
					}
				}
			}
			else if(!isset($title_list[$org_id]) || $c_agr_id >= $title_list[$org_id]['corp_agreement_id']) {
				if ($one['AgreementCustomize']['edit_kind'] != 'Delete') {
					$title_list[$org_id] = $one['AgreementCustomize'];
				}
				/* 削除された条項はリストから消す */
				else {
					unset($title_list[$org_id]);
				}
			}
		}

		/* ---------- ---------- ----------
		 * 本文が変更されている条項を取得
		 * ---------- ---------- ----------
		 */

		/* AgreementCustomizeとAgreementProvisionsを結合して取得 */
		$this->AgreementCustomize->bindModel(array(
				'belongsTo' => array(
						'AgreementProvisions' => array(
								'primaryKey' => 'id',
								'foreignKey' => 'original_provisions_id',
						),
				)
		));
		$this->AgreementCustomize->recursive = 2;
		$mod_item_prov = $this->AgreementCustomize->find(
				'all',
				array(
#						'joins'  => array(
#								array(
#										'type' => 'INNER',
#										'table' => 'agreement_provisions',
#										'alias' => 'AgreementProvisions',
#										'conditions' => 'AgreementCustomize.original_provisions_id = AgreementProvisions.id',
#								),
#						),
						'fields' => '*',
						'conditions' => array(
								'AgreementCustomize.corp_id'                           => $corp_id,
								'AgreementCustomize.corp_agreement_id <='              => $agreement_id,
								'AgreementCustomize.table_kind'                        => 'AgreementProvisionsItem',
								'OR'                                                   => array(
										'AgreementCustomize.original_provisions_id !=' => 0,
										'AgreementCustomize.original_item_id !='       => 0,
								),
						),
						'order' => array('AgreementCustomize.id' => 'asc'),
				)
		);

		/* 本文が変更された条項を取得 */
		foreach ($mod_item_prov as $one) {
			$org_id  = $one['AgreementCustomize']['original_provisions_id'];
			$sort_no = $one['AgreementProvisions']['sort_no'];
			$content = $one['AgreementProvisions']['provisions'];

			/* 同じ条項IDのものがリストにいない場合追加する */
			if (!isset($title_list[$org_id])) {
				/* contentとsort_noを差し替えてリストに追加 */
				$one['AgreementCustomize']['sort_no'] = $sort_no;
				$one['AgreementCustomize']['content'] = $content;
				$title_list[$org_id] = $one['AgreementCustomize'];
			}
		}

		/* sort_idをキーにして、差分リスト追加 */
		$mod_arr = array();
		$tmp_arr  = array();
		$sort_max = 0;
		foreach ($title_list as $title_data) {
			$sort_no = $title_data['sort_no'];
			$content = $title_data['content'];

			if (!isset($diff_arr[$sort_no])) {
				$mod_arr[$sort_no] = $content;
			}
			/* すでにそのsort_idをキーにした配列が存在してしまったら、とりあえずの配列に逃がしておく */
			else {
				$tmp_arr[] = $content;
			}

			/* 最終の条項を求めておく */
			if ($sort_max <= $sort_no) {
				$sort_max = $sort_no;
			}
		}

		/* 追加された条項を差分リストに追加 */
		$added_arr = array();
		foreach ($cst_list as $cst_data) {
			$sort_no = $cst_data['sort_no'];
			$content = $cst_data['content'];

			/* sort_noが0の時は配列の最後に追加 */
			if ($sort_no == 0) {
				$added_arr[] = $content;
			}
			else if (!isset($diff_arr[$sort_no])) {
				$mod_arr[$sort_no] = $content;
			}
			/* すでにそのsort_idをキーにした配列が存在してしまったら、とりあえずの配列に逃がしておく */
			else {
				$tmp_arr[] = $content;
			}
		}

		/* キー(sort_id)でソート */
		ksort($mod_arr);
		$diff_arr = array_merge($added_arr, $mod_arr);

		return $diff_arr;
	}
	// 2016.05.30 ota.r@tobila ADD END ORANGE-30 【契約管理】契約規約の特約や条文の変更・削除・追加部分が分かる出力PDFの作成

// 2016.06.23 murata.s ORANGE-102 ADD(S)
	/**
	 * 再契約条件入力
	 * @param string $id
	 */
	public function resigning($id = null) {
		$resultsFlg = true;
		$this->set( "error" , false );
		if (empty($id)) {
			throw new Exception();
		}


		// 2017.10.10 e.takeuchi@SharingTechnology ORANGE-528 ADD(S)
		if(!empty($this->request->data['MCorpCategoriesTemp']) && is_array($this->request->data['MCorpCategoriesTemp'])){
			// murata.s ORANGE-574 CHG(S)
			foreach ( $this->request->data['MCorpCategoriesTemp'] as $k => $v ) {
				$this->request->data['MCorpCategoriesTemp'][$k]['note'] = preg_replace('/\r\n/', '', trim($v['note']));
			}
			// murata.s ORANGE-574 CHG(E)
		}
		// 2017.10.10 e.takeuchi@SharingTechnology ORANGE-528 ADD(E)

		// 企業エリアマスターの取得
		$corp_areas = $this->MCorpTargetArea->find('all',
				array(
						'fields' => '*',
						'conditions' => array(
								'corp_id = ' . $id
						)
				)
		);
		// 基本対応エリアと個別対応エリアのジャンルリストを設定
		// (元々の処理は別関数に切り出し)

		$temp_link =$this->CorpAgreementTempLink->find('first', array(
				'fields' => 'id',
				'conditions' => array('corp_id' => $id),
				'order' => array('id' => 'desc')
		));
		if(!empty($temp_link))
			$temp_id = $temp_link['CorpAgreementTempLink']['id'];
		else
			$temp_id = null;

		$this->__set_genre_list_tmp($id, $temp_id);

			// 企業情報取得
			$corp_data = $this->MCorp->find ( 'first', array (
					'fields' => '*',
					'conditions' => array (
							'MCorp.id' => $id
					),
					'order' => array (
							'MCorp.id' => 'asc'
					),
			) );

		// 企業情報セット
		$this->set ("corp_data", $corp_data );

// 2016.09.01 murata.s ORANGE-174 ADD(S)
		$this->set('can_resigne', $this->__get_can_resigne($id));
// 2016.09.01 murata.s ORANGE-174 ADD(E)

		if (isset ( $this->request->data ['regist'] )) { // 登録

			// TODO:
			if(empty($this->request->data['MCorpCategoriesTemp'])){
				$this->set("id" , $id);
				$this->set("temp_id", $temp_id);
				$this->Session->setFlash ( __ ('Updated', true), 'default', array('class' => 'message_inner'));
				return true;
			}

// murata.s ORANGE-261 CHG(S)
			$error_chk_flg = false;
			// エラーチェック
			foreach ( $this->request->data['MCorpCategoriesTemp'] as $v ) {
// 				// 専門性チェック
// 				if (isset($v['select_list']) && $v['select_list'] == "") {
// 					$error_chk_flg = true;
// 					$error_msg = "専門性を選択してください。";
// 					break;
// 				}
				if ($this->User['auth'] != 'affiliation') {
					// 単位チェック
					if(!empty($v['order_fee'])
						&& $v['corp_commission_type'] != 2
						&& $v['order_fee_unit'] == ''){
						$error_chk_flg = true;
						$error_msg = "単位を選択してください。";
						break;
					}
					//ORANGE-393 CHG(S)
					if(!empty($v['application_check']) && (empty($v['application_reason']) || $v['application_reason'] == '')){
						$error_chk_flg = true;
						$error_msg = "申請理由を入力してください。";
						break;
					}
					//ORANGE-393 CHG(E)

					// 手数料チェック
					if($v['order_fee'] === ''){
						$error_chk_flg = true;
						$error_msg = "手数料を入力してください。";
						break;
					}

					// 取次形態チェック
					if(empty($v['corp_commission_type'])){
						$error_chk_flg = true;
						$error_msg = "取次形態を選択してください。";
						break;
					}
				}
			}
// murata.s ORANGE-261 CHG(E)
			// エラー出力
			if ($error_chk_flg) {

				$this->Session->setFlash ( $error_msg, 'default', array (
						'class' => 'error_inner'
				) );

				$this->data = $this->request->data;
				$this->set ( "errorData" , $this->request->data );
				$this->set( "error" , true );

				$this->set("id" , $id);
// 2016.09.23 murata.s ORANGE-190 ADD(S) 旧契約状態時のカテゴリデータが表示される
				$this->set("temp_id", $temp_id);
// 2016.09.23 murata.s ORANGE-190 ADD(E) 旧契約状態時のカテゴリデータが表示される
				return true;
			}

			$first = reset($this->request->data ['MCorpCategoriesTemp']);

			//if (self::__check_modified_category_temp( $this->request->data ['MCorpCategoriesTemp'] [0] ['id'], $this->request->data ['MCorpCategoriesTemp'] [0] ['modified'] )) {
			if (self::__check_modified_category_temp( $first['id'], $first['modified'] )) {
				try {
					$this->MCorpCategoriesTemp->begin ();
					$this->CorpAgreement->begin();
					$this->CorpAgreementTempLink->begin();

// 2016.09.23 murata.s ORANGE-190 ADD(S) 旧契約状態時のカテゴリデータが表示される
					$temp_lilnk = self::__copy_agreement_tmp($id);
					$this->request->data['temp_id'] = $temp_lilnk['CorpAgreementTempLink']['id'];
// 2016.09.23 murata.s ORANGE-190 ADD(E) 旧契約状態時のカテゴリデータが表示される

					//ORANGE-393 CHG S
					// 企業別対応カテゴリマスタの登録
					//$resultsFlg = $this->__edit_corp_categories_temp( $id, $this->request->data );
					$this->MCorpCategoriesTemp->copyCorpCatgoryByTempId($id, $temp_lilnk['CorpAgreementTempLink']['id']);

					//申請データの作成
					$target_data = array();
					//pr($this->request->data);
					foreach ($this->request->data['MCorpCategoriesTemp']as $v){
						if(!empty($v['application_check'])){
							$target_data['MCorpCategoriesTemp'][] = $v;
						}
					}

					$resultsFlg = $this->__add_corp_category_application( $id, $target_data );
					//ORANGE-393 CHG E

// 					// 再契約処理
// 					if($resultsFlg){
// 						$resultsFlg = $this->__edit_corp_categories_resigning($id);
// 					}

					if ($resultsFlg) {
						$this->MCorpCategoriesTemp->commit ();
						$this->CorpAgreement->commit();
						$this->CorpAgreementTempLink->commit();

// 						if ($this->User['auth'] == 'affiliation') {
						//ORANGE-393 CHG S
						//$message = __('Updated', true);
						$message = __('カテゴリ手数料の申請を行いました', true);
						//ORANGE-393 CHG E

						$this->Session->setFlash($message, 'default', array('class' => 'message_inner'));
						// 対応可能ジャンルリストの取得
						$results = $this->MCorpCategoriesTemp->getMCorpCategoryGenreList($id);
						//$this->request->data ['MCorpCategoriesTemp'] [0] ['modified'] = $results[0]['MCorpCategoriesTemp']['modified'];
						$first_r = reset($results);
						$first['modified'] = $first_r['MCorpCategoriesTemp']['modified'];

						$this->set("id" , $id);

// 2016.09.23 murata.s ORANGE-190 ADD(S) 旧契約状態時のカテゴリデータが表示される
						$this->set('temp_id', $temp_id);
// 2016.09.23 murata.s ORANGE-190 ADD(E) 旧契約状態時のカテゴリデータが表示される

						// 基本対応エリアと個別対応エリアを再設定
						$this->__set_genre_list_tmp($id, $temp_id);
						//ORANGE-393 CHG S
						//$this->Session->setFlash ( __ ('Updated', true), 'default', array('class' => 'message_inner'));
						$this->Session->setFlash ( __ ('カテゴリ手数料の申請を行いました', true), 'default', array('class' => 'message_inner'));

						//ORANGE-393 ChG E
						return true;
// 						} else {
// 							$this->Session->setFlash ( __ ('Updated', true), 'default', array('class' => 'message_inner'));
// 							return true;
// 						}
					}
					$this->MCorpCategoriesTemp->rollback ();

					$this->set ( "errorData" , $this->request->data );
					$this->set( "error" , true );

					$this->set("id" , $id);

				} catch ( Exception $e ) {
					$this->MCorpCategory->rollback ();
					$this->CorpAgreement->rollback();
					$this->CorpAgreementTempLink->rollback();
				}
				$this->Session->setFlash ( __ ( 'InputError', true ), 'default', array (
						'class' => 'error_inner'
				) );
				$this->set ( "errorData" , $this->request->data );
				$this->set( "error" , true );

			} else {
				$this->Session->setFlash ( __ ( 'ModifiedNotCheck', true ), 'default', array (
						'class' => 'error_inner'
				) );
				$this->set ( "errorData" , $this->request->data );
				$this->set( "error" , true );

			}

			$this->data = $this->request->data;
			$this->set ( "errorData" , $this->request->data );
			$this->set( "error" , true );

			$this->set("id" , $id);

		} else if (isset ( $this->request->data ['cancel'] )) { // キャンセル

			return $this->redirect ( '/affiliation/detail/' . $id );
		} else if (isset ( $this->request->data ['regist-resigning'] )) { // 再契約

// 2016.09.23 murata.s ORANGE-190 CHG(S) 旧契約状態時のカテゴリデータが表示される
// 			// 再契約処理
// 			$resultsFlg = $this->__edit_corp_categories_resigning($id);
			// 一時データの登録
			$temp_link = self::__copy_agreement_tmp($id);
			$temp_id = $temp_link['CorpAgreementTempLink']['id'];
			if($this->__get_category_temp_data_count($id, $temp_id) == 0){
				// 一時カテゴリデータの登録
				$save_data = $this->__find_categories_temp_copy($id, $temp_id);
				if(!empty($save_data))
					$this->MCorpCategoriesTemp->saveAll($save_data);
			}

			// 再契約処理
 			$resultsFlg = $this->__edit_corp_categories_resigning($id);
// 2016.09.23 murata.s ORANGE-190 CHG(E) 旧契約状態時のカテゴリデータが表示される

			if($resultsFlg){
				$this->Session->setFlash ( __ ( 'Updated', true ), 'default', array (
						'class' => 'message_inner'));
			}else{
				$this->Session->setFlash ( __ ( 'InputError', true ), 'default', array (
						'class' => 'error_inner'
				) );
				$this->set ( "errorData" , $this->request->data );
			}

			$this->set("id" , $id);
			// 2016.09.01 murata.s ORANGE-174 ADD(S)
			$this->set('can_resigne', $this->__get_can_resigne($id));
			// 2016.09.01 murata.s ORANGE-174 ADD(E)


		}
// 2016.09.01 murata.s ORANGE-174 ADD(S)
		else if (isset ( $this->request->data ['regist-resigning-fax'] )) { // FAX再契約
// 2016.09.23 murata.s ORANGE-190 CHG(S) 旧契約状態時のカテゴリデータが表示される
// 			// 再契約処理
// 			$resultsFlg = $this->__edit_corp_categories_resigning($id, 'FAX');

			// 一時データの登録
			$temp_link = self::__copy_agreement_tmp($id);
			$temp_id = $temp_link['CorpAgreementTempLink']['id'];
			if($this->__get_category_temp_data_count($id, $temp_id) == 0){
				// 一時カテゴリデータの登録
				$save_data = $this->__find_categories_temp_copy($id, $temp_id);
				if(!empty($save_data))
					$this->MCorpCategoriesTemp->saveAll($save_data);
			}

			// 再契約処理
			$resultsFlg = $this->__edit_corp_categories_resigning($id, 'FAX');
// 2016.09.23 murata.s ORANGE-190 CHG(E) 旧契約状態時のカテゴリデータが表示される

			if($resultsFlg){

// 2016.09.12 murata.s ORANGE-183 ADD(S)
// 2017/10/18 ORANGE-541 m-kawamoto DEL(S)
//				// 未登録のライセンスをDBに登録する
//				$this->__add_corp_license_link_temp($id);
// 2017/10/18 ORANGE-541 m-kawamoto DEL(E)
// 2016.09.12 murata.s ORANGE-183 ADD(E)

				$this->Session->setFlash ( __ ( 'Updated', true ), 'default', array (
						'class' => 'message_inner'));
			}else{
				$this->Session->setFlash ( __ ( 'InputError', true ), 'default', array (
						'class' => 'error_inner'
				) );
				$this->set ( "errorData" , $this->request->data );
			}

			$this->set("id" , $id);
			$this->set('can_resigne', $this->__get_can_resigne($id));

		}
// 2016.09.01 murata.s ORANGE-174 ADD(E)
		else {  // 初期表示

// 2016.09.23 murata.s ORANGE-190 DEL(S) 旧契約状態時のカテゴリデータが表示される
// 			// 再契約に必要なテーブルのコピー
// 			$temp_lilnk = self::__copy_agreement_tmp($id);
// 			$temp_id = $temp_lilnk['CorpAgreementTempLink']['id'];
// 2016.09.23 murata.s ORANGE-190 DEL(E) 旧契約状態時のカテゴリデータが表示される

			// 基本対応エリアと個別対応エリアのジャンルリストを設定
			$this->__set_genre_list_tmp($id, $temp_id);

			$this->set("id" , $id);


		}
// 2016.09.23 murata.s ORANGE-190 ADD(S) 旧契約状態時のカテゴリデータが表示される
		$this->set('temp_id', $temp_id);
// 2016.09.23 murata.s ORANGE-190 ADD(E) 旧契約状態時のカテゴリデータが表示される
	}

// 2016.09.15 murata.s ORANGE-183 ADD(S)
// 2017/10/18 m-kawamoto ORANGE-541 DEL(S)
//	/**
//	 * 企業ライセンスリンクを登録する
//	 *
//	 * @param unknown $corp_id 企業ID
//	 */
//	private function __add_corp_license_link_temp($corp_id){
//
//		// 最新のtemp_idを取得
//		$temp_link =$this->CorpAgreementTempLink->find('first', array(
//				'fields' => 'id',
//				'conditions' => array('corp_id' => $corp_id),
//				'order' => array('id' => 'desc')
//		));
//		$temp_id = $temp_link['CorpAgreementTempLink']['id'];
//
//		// カテゴリに紐付くライセンスを取得
//		$category_licenses = $this->CategoryLicenseLink->find('all', array(
//				'fields' =>'CategoryLicenseLink.license_id',
//				'joins' => array(
//						array(
//								'fields' => '*',
//								'type' => 'inner',
//								'table' => 'm_corp_categories_temp',
//								'alias' => 'MCorpCategoriesTemp',
//								'conditions' => array(
//										'MCorpCategoriesTemp.category_id = CategoryLicenseLink.category_id'
//								)
//						)
//				),
//				'conditions' => array(
//						'MCorpCategoriesTemp.temp_id' => $temp_id,
//						'MCorpCategoriesTemp.delete_flag' => false
//				),
//				'group' => array('CategoryLicenseLink.license_id')
//
//		));
//
//		// 登録済みのカテゴリに紐付くライセンスを取得
//		$corp_licenses = $this->CorpLisenseLink->find('all', array(
//				'fields' => 'CorpLisenseLink.lisense_id',
//				'joins' => array(
//						array(
//								'fields' => '*',
//								'type' => 'inner',
//								'table' => 'category_license_link',
//								'alias' =>'CategoryLicenseLink',
//								'conditions' => array('CategoryLicenseLink.license_id = CorpLisenseLink.lisense_id')
//						),
//						array(
//								'fields' => '*',
//								'type' => 'inner',
//								'table' => 'm_corp_categories_temp',
//								'alias' => 'MCorpCategoriesTemp',
//								'conditions' => array('MCorpCategoriesTemp.category_id = CategoryLicenseLink.category_id')
//						)
//				),
//				'conditions' => array(
//						'CorpLisenseLink.corps_id' => $corp_id,
//						'MCorpCategoriesTemp.temp_id' => $temp_id,
//						'MCorpCategoriesTemp.delete_flag' => false
//				),
//				'group' => array('CorpLisenseLink.lisense_id')
//		));
//
//		foreach($category_licenses as $license){
//			$license_id = $license['CategoryLicenseLink']['license_id'];
//			$registed_license = array_filter($corp_licenses, function($item) use($license_id){
//				return $item['CorpLisenseLink']['lisense_id'] == $license_id;
//			});
//			// 未登録のライセンスがあれば新規登録する
//			if(empty($registed_license)){
//				$save_data[] = array(
//						'CorpLisenseLink' => array(
//								'corps_id' => $corp_id,
//								'lisense_id' => $license_id,
//								'have_lisense' => false,
//								'create_date' => date('Y-m-d H:i:s'),
//								'create_user_id' => $this->User['user_id'],
//								'update_date' => date('Y-m-d H:i:s'),
//								'update_user_id' => $this->User['user_id']
//						)
//				);
//			}
//		}
//
//		if(!empty($save_data)){
//			return $this->CorpLisenseLink->saveAll($save_data);
//		}else{
//			return true;
//		}
//	}
// 2017/10/18 m-kawamoto ORANGE-541 DEL(E)
// 2016.09.15 murata.s ORANGE-183 ADD(E)

// 2016.09.01 murata.s ORANGE-174 ADD(S)
	/**
	 * 最新の契約再確認可能かどうか
	 *
	 * @param string $corp_id 企業ID
	 */
	private function __get_can_resigne($corp_id){
		// 最新の契約データのステータスを取得
		$corpAgreement_status = $this->CorpAgreement->find('first', array(
				'fields' => 'status',
				'conditions' => array(
						'corp_id' => $corp_id
				),
				'order' => array('id' => 'desc')
		));
		$can_resigne = true;
		if(!empty($corpAgreement_status['CorpAgreement']['status'])){
			$can_resigne = ($corpAgreement_status['CorpAgreement']['status'] == 'Complete' || $corpAgreement_status['CorpAgreement']['status'] == 'Application') ? true : false;
		}

		return $can_resigne;
	}
// 2016.09.01 murata.s ORANGE-174 ADD(E)

	/**
	 * 基本対応エリアと個別対応エリアのジャンルリストを設定
	 *
	 * @param string $id 企業ID
	 */
	private function __set_genre_list_tmp($id = null, $temp_id = null){

		// 企業エリアマスターの取得
		$corp_areas = $this->MCorpTargetArea->find('all',
				array(
						'fields' => '*',
						'conditions' => array(
								'corp_id = ' . $id
						)
				)
		);
		// 基本対応エリアの設定エリア数
		$corp_target_area_count = $this->MCorpTargetArea->getCorpTargetAreaCount($id);
// 2016.09.23 murata.s ORANGE-190 CHG(S)  旧契約状態時のカテゴリデータが表示される
// 		// 対応可能ジャンルリストの取得
// 		$results = $this->MCorpCategoriesTemp->getMCorpCategoryGenreList($id, $temp_id);
		// 対応可能ジャンルリストの取得
		$results = $this->__find_categories_temp_copy($id, $temp_id);
		// 一時データとして登録済みのデータを取得
		$registed_data = $hoge = array_filter($results, function($v){
			return !empty($v['MCorpCategoriesTemp']['id']);
		});
// 2016.09.23 murata.s ORANGE-190 CHG(E)  旧契約状態時のカテゴリデータが表示される
		$genre_custom_area_list = array();
		$genre_normal_area_list = array();
		foreach ($results as $key => $val) {
			// 基本対応エリアのままのジャンルとカスタマイズしているジャンルの区分け
			$custom_flg = false;
			$mstedt_flg = false;

			// 「target_area_type（対応可能エリアタイプ）」が未設定
			if ($val['MCorpCategoriesTemp']['target_area_type'] == 0) {
				$mstedt_flg = true;

				// m_corp_categories.idの取得
				$mc = $this->MCorpCategory->find('first', array(
						'fields' => 'id',
						'conditions' => array(
								'corp_id' => $id,
								'category_id' => $val['MCorpCategoriesTemp']['category_id']
						)
				));

				// 再契約入力画面で登録されたカテゴリ以外(=すでに登録済みのカテゴリ)は
				// カテゴリ対応エリアがカスタマイズしているかチェックする
				// 再契約入力画面で登録された場合は、エリアが未登録のため基本対応エリアとする
				if(!empty($mc)){
					$target_area_count = $this->MTargetArea->getCorpCategoryTargetAreaCount($mc['MCorpCategory']['id']);
					if ($target_area_count != $corp_target_area_count) {
						$custom_flg = true;
					}
					if(!empty($corp_areas)){
						foreach ($corp_areas as $area_v) {
							$area_count = $this->MTargetArea->getCorpCategoryTargetAreaCount2($mc['MCorpCategory']['id'], $area_v['MCorpTargetArea']['jis_cd']);
							if ($area_count <= 0) {
								$custom_flg = true;
								break;
							}
						}
					}
				}
			} else if ($val['MCorpCategoriesTemp']['target_area_type'] == 2) {
				// 対応可能エリアが基本対応エリアと異なる
				$custom_flg = true;
			}

// 2016.09.23 murata.s ORANGE-190 CHG(S)  旧契約状態時のカテゴリデータが表示される
// 			if ($custom_flg == true) {
// 				// 対応エリアをカスタマイズしているジャンルリスト
// 				$genre_custom_area_list[$key] = $results[$key];
//
// 				if ($mstedt_flg) {
// 					// 「m_corp_categories」の「target_area_type（対応可能エリアタイプ）」を更新
// // 					foreach ($list_data as $val) {
// 						self::__edit_corp_category_temp_target_area_type($val['MCorpCategoriesTemp']['id'], 2);
// // 					}
// 				}
// 			} else {
// 				// 対応エリアが基本対応エリアのままのジャンルリスト
// 				$genre_normal_area_list[$key] = $results[$key];
//
// 				if ($mstedt_flg) {
// 					// 「m_corp_categories」の「target_area_type（対応可能エリアタイプ）」を更新
// // 					foreach ($list_data as $val) {
// 						self::__edit_corp_category_temp_target_area_type($val['MCorpCategoriesTemp']['id'], 1);
// // 					}
// 				}
// 			}
			if ($custom_flg == true) {
				// 対応エリアをカスタマイズしているジャンルリスト
				$genre_custom_area_list[$key] = $results[$key];

				if ($mstedt_flg && !empty($registed_data)) {
					// 「m_corp_categories」の「target_area_type（対応可能エリアタイプ）」を更新
					self::__edit_corp_category_temp_target_area_type($val['MCorpCategoriesTemp']['id'], 2);
				}
			} else {
				// 対応エリアが基本対応エリアのままのジャンルリスト
				$genre_normal_area_list[$key] = $results[$key];

				if ($mstedt_flg && !empty($registed_data)) {
					// 「m_corp_categories」の「target_area_type（対応可能エリアタイプ）」を更新
					self::__edit_corp_category_temp_target_area_type($val['MCorpCategoriesTemp']['id'], 1);
				}
			}
// 2016.09.23 murata.s ORANGE-190 CHG(E)  旧契約状態時のカテゴリデータが表示される
		}
		$this->set("genre_custom_area_list" , $genre_custom_area_list);
		$this->set("genre_normal_area_list" , $genre_normal_area_list);
	}

	/**
	 * ジャンル選択(再契約)
	 *
	 * @param string $id
	 */
	public function genre_resigning($id = null) {

		$temp_link = $this->CorpAgreementTempLink->find('first', array(
				'fields' => 'id',
				'conditions' => array('corp_id' => $id),
				'order' => array('id' => 'desc')
		));
// 2016.09.23 murata.s ORANGE-190 CHG(S) 旧契約状態時のカテゴリデータが表示される
		if(!empty($temp_link)){
			$temp_id = $temp_link['CorpAgreementTempLink']['id'];
		}else{
			$temp_id = null;
		}
// 2016.09.23 murata.s ORANGE-190 CHG(E) 旧契約状態時のカテゴリデータが表示される

// 2016.09.01 murata.s ORANGE-174 ADD(S)
		$this->set('can_resigne', $this->__get_can_resigne($id));
// 2016.09.01 murata.s ORANGE-174 ADD(E)

		$resultsFlg = true;
		if (isset ( $this->request->data ['regist-genre'] )) { // 登録
			unset($this->request->data ['regist-genre']);
			foreach ($this->request->data['MCorpCategoriesTemp'] as $category) {
				if (isset($category['category_id']) && empty($category['select_list'])) {
					$id_array = split('-', $category['category_id']);
					$genre_id = $id_array[0];
					$MGenreData = $this->MGenre->findById($genre_id);
					// 紹介ベースジャンルは専門性必須チェックしない
					if ($MGenreData["MGenre"]['commission_type'] != 2) {
						// 専門性必須チェック
						$this->Session->setFlash ( __ ( 'NotSelectList', true ), 'default', array (
								'class' => 'error_inner'
						) );
						$resultsFlg = false;
					}
				} else if (!isset($category['category_id']) && !empty($category['select_list'])) {
					// 専門性必須チェック
					$this->Session->setFlash ( __ ( 'NotSelectCorrespondCheck', true ), 'default', array (
							'class' => 'error_inner'
					) );
					$resultsFlg = false;
				}
			}
			if ($resultsFlg) {
				try {
					$this->MCorpCategoriesTemp->begin ();
					$this->CorpAgreementTempLink->begin();

// 2016.09.23 murata.s ORANGE-190 ADD(S) 旧契約状態時のカテゴリデータが表示される
					$temp_link = self::__copy_agreement_tmp($id);
					$temp_id = $temp_link['CorpAgreementTempLink']['id'];
// 2016.09.23 murata.s ORANGE-190 ADD(E) 旧契約状態時のカテゴリデータが表示される

					// 企業別対応カテゴリマスタの登録
					$del_array = array();
					$resultsFlg = $this->__edit_corp_category_temp_genre( $id, $this->request->data, $del_array, $temp_id );

					if ($resultsFlg) {
						$this->MCorpCategoriesTemp->commit ();
						$this->CorpAgreementTempLink->commit();

						// 2016.09.15 murata.s ORANGE-183 ADD(S)
						// 2017/10/18 m-kawamoto ORANGE-541 DEL(S)
						//// 契約種別が"FAX"の場合、未登録のライセンスを登録する
						//$corp_agreement = $this->CorpAgreement->find('first', array(
						//		'fields' => 'CorpAgreement.kind',
						//		'joins' => array(
						//				array(
						//						'fields' => '*',
						//						'type' => 'inner',
						//						'table' => 'corp_agreement_temp_link',
						//						'alias' => 'CorpAgreementTempLink',
						//						'conditions' => array('CorpAgreementTempLink.corp_agreement_id = CorpAgreement.id')
						//				)
						//		),
						//		'conditions' => array(
						//				'CorpAgreementTempLink.id' => $temp_id,
						//				'CorpAgreement.kind' => 'FAX'
						//		)
						//));
						//if(!empty($corp_agreement)){
						//	$this->__add_corp_license_link_temp($id);
						//}
						// 2017/10/18 m-kawamoto ORANGE-541 DEL(E)
						// 2016.09.15 murata.s ORANGE-183 ADD(E)

						$this->Session->setFlash(__('Updated', true), 'default', array('class' => 'message_inner'));
						return $this->redirect ( '/affiliation/resigning/' . $id );
					} else {
						$this->MCorpCategoriesTemp->rollback ();
						$this->set ( "errorData" , $this->request->data );
						$this->set( "error" , true );
					}
				} catch ( Exception $e ) {
					$this->MCorpCategory->rollback ();
					$this->CorpAgreementTempLink->rollback();
				}
			}
		} else if (isset ( $this->request->data ['cancel-genre'] )) { // キャンセル

			return $this->redirect ( '/affiliation/resigning/' . $id );
// 2016.06.23 murata.s ORANGE-102 ADD(S)
		} else if (isset($this->request->data['regist-resigning'])) { // 再確認

			$this->CorpAgreement->begin();
			$this->CorpAgreementTempLink->begin();

// 2016.09.23 murata.s ORANGE-190 CHG(S) 旧契約状態時のカテゴリデータが表示される
// 			$resultsFlg = $this->__edit_corp_categories_resigning($id);
			// 一時データの登録
			$temp_link = self::__copy_agreement_tmp($id);
			$temp_id = $temp_link['CorpAgreementTempLink']['id'];
			if($this->__get_category_temp_data_count($id, $temp_id) == 0){
				// 一時カテゴリデータの登録
				$save_data = $this->__find_categories_temp_copy($id, $temp_id);
				if(!empty($save_data))
					$this->MCorpCategoriesTemp->saveAll($save_data);
			}

			// 再契約処理
			$resultsFlg = $this->__edit_corp_categories_resigning($id);
// 2016.09.23 murata.s ORANGE-190 CHG(E) 旧契約状態時のカテゴリデータが表示される

			if($resultsFlg){
				$this->CorpAgreement->commit();
				$this->CorpAgreementTempLink->commit();

				// 2016.09.01 murata.s ORANGE-174 ADD(S)
				$this->set('can_resigne', $this->__get_can_resigne($id));
				// 2016.09.01 murata.s ORANGE-174 ADD(E)


				$this->Session->setFlash(__('Updated', true), 'default', array('class' => 'message_inner'));
				return $this->redirect ( '/affiliation/resigning/' . $id );
			}else{
				$this->CorpAgreement->rollback();
				$this->CorpAgreementTempLink->rollback();

				$this->set ( "errorData" , $this->request->data );
				$this->set( "error" , true );
			}
		}
		// 2016.09.01 murata.s ORANGE-174 ADD(S)
		else if (isset($this->request->data['regist-resigning-fax'])) { // FAX再確認
			$this->CorpAgreement->begin();
			$this->CorpAgreementTempLink->begin();

// 2016.09.23 murata.s ORANGE-190 CHG(S) 旧契約状態時のカテゴリデータが表示される
// 			$resultsFlg = $this->__edit_corp_categories_resigning($id, 'FAX');

			// 一時データの登録
			$temp_link = self::__copy_agreement_tmp($id);
			$temp_id = $temp_link['CorpAgreementTempLink']['id'];
			if($this->__get_category_temp_data_count($id, $temp_id) == 0){
				// 一時カテゴリデータの登録
				$save_data = $this->__find_categories_temp_copy($id, $temp_id);
				if(!empty($save_data))
					$this->MCorpCategoriesTemp->saveAll($save_data);
			}

			// 再契約処理
			$resultsFlg = $this->__edit_corp_categories_resigning($id, 'FAX');
// 2016.09.23 murata.s ORANGE-190 CHG(S) 旧契約状態時のカテゴリデータが表示される

			if($resultsFlg){
				$this->CorpAgreement->commit();
				$this->CorpAgreementTempLink->commit();

// 2016.09.15 murata.s ORANGE-183 ADD(S)
// 2017/10/18 m-kawamoto ORANGE-541 DEL(S)
//				// 未登録のライセンスをDBに登録する
//				$this->__add_corp_license_link_temp($id);
// 2017/10/18 m-kawamoto ORANGE-541 DEL(E)
// 2016.09.15 murata.s ORANGE-193 ADD(E)

				// 2016.09.01 murata.s ORANGE-174 ADD(S)
				$this->set('can_resigne', $this->__get_can_resigne($id));
				// 2016.09.01 murata.s ORANGE-174 ADD(E)

				$this->Session->setFlash(__('Updated', true), 'default', array('class' => 'message_inner'));
				return $this->redirect ( '/affiliation/resigning/' . $id );
			}else{
				$this->CorpAgreement->rollback();
				$this->CorpAgreementTempLink->rollback();

				$this->set ( "errorData" , $this->request->data );
				$this->set( "error" , true );
			}
		}
		// 2016.09.01 murata.s ORANGE-174 ADD(E)

// 2016.09.23 murata.s ORANGE-190 DEL(S) 旧契約状態時のカテゴリデータが表示される
// 		// 再契約に必要なテーブルのコピー
// 		self::__copy_agreement_tmp($id);
// 2016.09.23 murata.s ORANGE-190 DEL(E) 旧契約状態時のカテゴリデータが表示される

		// 変数初期化
		$work_category = array ();
		$all_category     = array ();
// 2016.09.23 murata.s ORANGE-190 CHG(S) 旧契約状態時のカテゴリデータが表示される
// 		// ALLカテゴリ 検索する
// 		$work_category = $this->MCategory->getTempAllList($id, $temp_id);
		// ALLカテゴリ 検索する
		$work_category = $this->__find_work_all_category_copy($id, $temp_id);
// 2016.09.23 murata.s ORANGE-190 CHG(E) 旧契約状態時のカテゴリデータが表示される

		if (!$resultsFlg) {
			// エラー再表示の場合
			foreach ( $work_category as $key => $v ) {
				foreach ( $this->request->data['MCorpCategoriesTemp'] as $request ) {
					$id_array = split('-', $request['table_disc_id']);
					$genre_id    = $id_array[0];
					$category_id = $id_array[1];
					if ($genre_id == $v['MGenre']['id'] && $category_id == $v['MCategory']['id']) {
						if (isset($request['category_id'])) {
							$v['check'] = "checked";
						} else {
							$v['check'] = "";
						}
						if (isset($request['select_list'])) {
							$v['MCorpCategoriesTemp']['select_list'] = $request['select_list'];
						} else {
							$v['MCorpCategoriesTemp']['select_list'] = "";
						}
						break;
					}
				}
				$all_category[] = $v;
			}
		} else {
			// 初期表示
			foreach ( $work_category as $v ) {
// 2016.09.23 murata.s ORANGE-190 DEL(S) 旧契約状態時のカテゴリデータが表示される
// 一時データとして未登録の場合はidは未設定であるため、ここではチェックしない
// 				if (isset($v['MCorpCategoriesTemp']['id'])) {
// 					$v['check'] = "checked";
// 				} else {
// 					$v['check'] = "";
// 				}
// 2016.09.23 murata.s ORANGE-190 DEL(E) 旧契約状態時のカテゴリデータが表示される
				$all_category[] = $v;
			}
		}
		$this->set ( "all_category", $all_category );

		// 企業情報取得
		$corp_data = $this->MCorp->find ( 'first', array (
				//'fields' => 'MCorp.id , MCorp.corp_name, MCorp.official_corp_name, MCorp.corp_name_kana',
				'fields' => '*',
				'conditions' => array (
						'MCorp.id' => $id
				),
				'order' => array (
						'MCorp.id' => 'asc'
				)
		) );
		// 企業情報セット
		$this->set ("corp_data", $corp_data );

		// 登録斡旋ジャンルを検索する
		$mediation_genre = $this->MGenre->getMediationList();
		$this->set ( "mediation_genre", $mediation_genre );
	}

// 2016.09.01 murata.s ORANGE-174 CHG(S)
	/**
	 * corp_agreementを登録する
	 *
	 * @param string $corp_id 企業ID
	 * @param string $kind 契約種別
	 */
	private function __insert_corp_agreement($corp_id = null, $kind = 'WEB'){

		$corp_agreement = $this->CorpAgreement->find('first', array(
				'fields' => '*',
				'conditions' => array('corp_id' => $corp_id),
				'order' => array('id' => 'desc')
		));
		$agreement = $this->Agreement->find('first', array(
				'fields' => '*',
				'order' => array('id' => 'desc')
		));

		// WEBの場合はResigning、それ以外(FAXの場合)はNotSignedをセット
		$status = $kind == 'WEB' ? 'Resigning' : 'NotSigned';

		// corp_agreementの作成
		$data = array(
				'CorpAgreement' => array(
						'corp_id' => $corp_id,
						'corp_kind' => isset($corp_agreement['CorpAgreement']['corp_kind']) ? $corp_agreement['CorpAgreement']['corp_kind'] : 'Corp',
						'agreement_id' => isset($agreement['Agreement']['id']) ? $agreement['Agreement']['id'] : 1,
						'agreement_history_id' => isset($agreement['Agreement']['agreement_history_id']) ? $agreement['Agreement']['agreement_history_id'] : 1,
						'ticket_no' => isset($agreement['Agreement']['ticket_no']) ? $agreement['Agreement']['ticket_no'] : 1,
						//'status' => 'Resigning',
						'status' => $status,
						'create_date' => date('Y-m-d H:i:s'),
						'create_user_id' => $this->User['id'],
						'update_date' => date('Y-m-d H:i:s'),
						'update_user_id' => $this->User['id'],
						'kind' => $kind
				)
		);

		$this->CorpAgreement->create();
		return $this->CorpAgreement->save($data, false);
	}
// 2016.09.01 murata.s ORANGE-174 CHG(E)

	/**
	 * 再契約入力データコピー処理
	 */
	private function __copy_agreement_tmp($corp_id = null){

		// 契約毎の一時データを取得
		$temp_link = $this->CorpAgreementTempLink->find('first', array(
				'fields' => '*, CorpAgreement.status',
				'joins' => array(
						array(
								'fields' => '*',
								'type' => 'left',
								'table' => 'corp_agreement',
								'alias' => 'CorpAgreement',
								'conditions' => array(
										'CorpAgreement.id = CorpAgreementTempLink.corp_agreement_id'
								)
						)
				),
				'conditions' => array('CorpAgreementTempLink.corp_id' => $corp_id),
				'order' => array('CorpAgreementTempLink.id' => 'desc')
		));

		if(empty($temp_link)
				|| $temp_link['CorpAgreement']['status'] == 'Complete'
				|| $temp_link['CorpAgreement']['status'] == 'Application'){

			// 再契約用一時データと会社毎の契約の紐付けTBLの登録
			$temp_link = self::__insert_corp_agreement_temp_link($corp_id);

			// 企業別対応カテゴリ(一時用)の登録
			//self::__copy_corp_categories($corp_id, $temp_link['CorpAgreementTempLink']['id']);
		}

// 2016.09.23 murata.s ORANGE-190 DEL(S) 旧契約状態時のカテゴリデータが表示される
// 		$mcc_temp_count = $this->MCorpCategoriesTemp->find('count', array(
// 				'conditions' => array(
// 						'corp_id' => $corp_id,
// 						'temp_id' => $temp_link['CorpAgreementTempLink']['id']
// 				)
// 		));
//
// 		if($mcc_temp_count == 0){
// // 2016.09.02 murata.s ADD(S)
// 			$latest_temp_link = $this->CorpAgreementTempLink->find('all', array(
// 					'conditions' => array(
// 							'corp_id' => $corp_id
// 					),
// 					'order' => array('id' => 'desc'),
// 					'limit' => 2
// 			));
// 			if(count($latest_temp_link) == 2) {
// 				$leatest_temp_link = end($latest_temp_link);
// 				$prev_temp_id = $leatest_temp_link['CorpAgreementTempLink']['id'];
// 			}
// // 2016.09.02 murata.s ADD(E)
// // 2016.09.02 murata.s CHG(S)
// 			// 企業別対応カテゴリ(一時用)の登録
// 			if(empty($prev_temp_id))
// 				self::__copy_corp_categories($corp_id, $temp_link['CorpAgreementTempLink']['id']);
// 			else
// 				self::__copy_corp_categories_temp($corp_id, $temp_link['CorpAgreementTempLink']['id'], $prev_temp_id);
// // 2016.09.02 murata.s CHG(E)
// 		}
// 2016.09.23 murata.s ORANGE-190 DEL(E) 旧契約状態時のカテゴリデータが表示される
		return $temp_link;
	}

	/**
	 * m_corp_categoriesを再契約用一時データに上書きする
	 */
	private function __update_original_category_data($corp_agreement){

		// 承認された場合は一時用データを契約データに上書きする
		// 未承認の場合は上書き処理は行わず正常終了とする
		if(empty($corp_agreement)) return true;
		else if(!isset($corp_agreement['CorpAgreement']['status'])
				|| $corp_agreement['CorpAgreement']['status'] !== 'Complete' )
			return true;

		$result = false;

		$this->MCorpCategory->begin();
		$this->MTargetArea->begin();
		$this->MCorpCategoriesTemp->begin();

		try{
			$corp_id = $corp_agreement['CorpAgreement']['corp_id'];
			$corp_agreement_id = $corp_agreement['CorpAgreement']['id'];
			// 一時データを企業別対応カテゴリマスタに登録
			$result = self::__update_corp_category_form_temp($corp_id, $corp_agreement_id);

			if($result){
				$this->MCorpCategory->commit();
				$this->MTargetArea->commit();
				$this->MCorpCategoriesTemp->commit();
			}else{
				$this->MCorpCategory->rollback();
				$this->MTargetArea->rollback();
				$this->MCorpCategoriesTemp->rollback();
			}

		}catch(Exception $e){
			$this->MCorpCategory->rollback();
			$this->MTargetArea->rollback();
			$this->MCorpCategoriesTemp->rollback();

			$result = false;
		}

		return $result;
	}

	/**
	 * 契約毎の一時用データの登録
	 *
	 * @param string $corp_id 企業ID
	 * @param string $corp_agreement_id 契約ID
	 */
	private function __insert_corp_agreement_temp_link($corp_id = null, $corp_agreement_id = null){
		$save_data = array(
				'CorpAgreementTempLink' => array(
						'corp_id' => $corp_id,
						'corp_agreement_id' => $corp_agreement_id
				)
		);
		$result = $this->CorpAgreementTempLink->save($save_data);
		if($result) return $result;
		else return false;
	}

	/**
	 * 企業別対応カテゴリ(一時用)の登録
	 *
	 * @param $corp_id 企業ID
	 */
	private function __copy_corp_categories($corp_id = null, $temp_id = null){

		// 登録対象となる企業別対応カテゴリの取得
		$corp_categories = $this->MCorpCategory->find('all', array(
				'fields' => '*',
				'conditions' => array(
						'corp_id' => $corp_id
				)
		));

		// 企業別対応カテゴリ(一時用)を削除
		// $this->MCorpCategoriesTemp->deleteAll(array('corp_id' => $corp_id), false);

		// 取得データを企業別カテゴリ(一時用)に登録
		$save_data = array();
		foreach ($corp_categories as $val){
			$save_data[] = array(
					'MCorpCategoriesTemp' => array(
							'temp_id' => $temp_id,
							'corp_id' => $val['MCorpCategory']['corp_id'],
							'genre_id' => $val['MCorpCategory']['genre_id'],
							'category_id' => $val['MCorpCategory']['category_id'],
							'order_fee' => $val['MCorpCategory']['order_fee'],
							'order_fee_unit' => $val['MCorpCategory']['order_fee_unit'],
							'introduce_fee' => $val['MCorpCategory']['introduce_fee'],
							'note' => $val['MCorpCategory']['note'],
							'select_list' => $val['MCorpCategory']['select_list'],
							'select_genre_category' => $val['MCorpCategory']['select_genre_category'],
							'target_area_type' => $val['MCorpCategory']['target_area_type'],
// murata.s ORANGE-261 ADD(S)
							'corp_commission_type' => $val['MCorpCategory']['corp_commission_type'],
// murata.s ORANGE-261 ADD(E)
					));
		}
		if(!empty($save_data)){
			if($this->MCorpCategoriesTemp->saveAll($save_data))
				return true;
			else
				return false;
		}
	}

	/**
	 * 企業別対応カテゴリ(一時用)の登録
	 *
	 * @param $corp_id 企業ID
	 * @param $temp_id 一時ID
	 * @param $prev_temp_id 保存対象一時ID
	 */
	private function __copy_corp_categories_temp($corp_id = null, $temp_id = null, $prev_temp_id = null){

		// 登録対象となる企業別対応カテゴリの取得
		$corp_categories = $this->MCorpCategoriesTemp->find('all', array(
				'fields' => '*',
				'conditions' => array(
						'corp_id' => $corp_id,
						'temp_id' => $prev_temp_id,
						'delete_flag' => false
				)
		));

		// 取得データを企業別カテゴリ(一時用)に登録
		$save_data = array();
		foreach ($corp_categories as $val){
// murata.s ORANGE-261 CHG(S)
			$save_data[] = array(
					'MCorpCategoriesTemp' => array(
							'temp_id' => $temp_id,
							'corp_id' => $val['MCorpCategoriesTemp']['corp_id'],
							'genre_id' => $val['MCorpCategoriesTemp']['genre_id'],
							'category_id' => $val['MCorpCategoriesTemp']['category_id'],
							'order_fee' => $val['MCorpCategoriesTemp']['order_fee'],
							'order_fee_unit' => $val['MCorpCategoriesTemp']['order_fee_unit'],
							'introduce_fee' => $val['MCorpCategoriesTemp']['introduce_fee'],
							'note' => $val['MCorpCategoriesTemp']['note'],
							'select_list' => $val['MCorpCategoriesTemp']['select_list'],
							'select_genre_category' => $val['MCorpCategoriesTemp']['select_genre_category'],
							'target_area_type' => $val['MCorpCategoriesTemp']['target_area_type'],
							'delete_flag' => $val['MCorpCategoriesTemp']['delete_flag'],
							'delete_date' => $val['MCorpCategoriesTemp']['delete_date'],
							'corp_commission_type' => $val['MCorpCategoriesTemp']['corp_commission_type']
					));
// murata.s ORANGE-261 CHG(E)
		}
		if(!empty($save_data)){
			if($this->MCorpCategoriesTemp->saveAll($save_data))
				return true;
			else
				return false;
		}
	}

	/**
	 * 「m_corp_categories_temp」の「target_area_type（対応可能エリアタイプ）」を更新
	 *
	 * @param unknown $id
	 */
	private function __edit_corp_category_temp_target_area_type($id, $type) {

		$this->MCorpCategoriesTemp->begin ();
		$data['MCorpCategoriesTemp']['id'] = $id;
		$data['MCorpCategoriesTemp']['target_area_type'] = $type;
		$data['MCorpCategoriesTemp']['modified'] = false;

		$save_data = array (
				'MCorpCategoriesTemp' => $data['MCorpCategoriesTemp']
		);

		// 更新
		if ($this->MCorpCategoriesTemp->save($save_data,false,array('target_area_type'))) {
			$this->MCorpCategoriesTemp->commit ();
		} else {
			$this->MCorpCategoriesTemp->rollback();
		}
	}

	/**
	 * 企業別対応カテゴリ情報(一時用)更新日時のチェック
	 *
	 * @param unknown $id
	 * @param unknown $modified
	 * @return boolean
	 */
	private function __check_modified_category_temp($id, $modified) {
		if (empty ( $id )) {
			return true;
		}
		$results = $this->MCorpCategoriesTemp->findByid ( $id );

		if (isset ( $results ['MCorpCategoriesTemp'] ['modified'] )) {
			if ($modified == $results ['MCorpCategoriesTemp'] ['modified']) {
				return true;
			}
		}
		return false;
	}


	//ORANGE-393 ADD S
	/**
	 * カテゴリ手数料申請データの作成
	 * @param unknown $id
	 * @param unknown $data
	 */
	private function __add_corp_category_application($id, $data) {

		try{

			if(empty($data ['MCorpCategoriesTemp']) || empty($id)) {
				$category_error [0] = '申請するデータが存在しません';
				$this->set ( 'category_error', $category_error );

				return false;
			}

			//申請グループの作成
			$save_group['CorpCategoryGroupApplication']['corp_id'] = $id;

			if (!$this->CorpCategoryGroupApplication->save($save_group)){
				$category_error [0] = 'CorpCategoryGroupApplicationデータの作成に失敗しました';
				$this->set ( 'category_error', $category_error );
				return false;
			}

			$group_id = $this->CorpCategoryGroupApplication->getLastInsertId();

			//申請データの作成
			foreach ( $data ['MCorpCategoriesTemp'] as $k => $v ) {
				//企業カテゴリデータの作成・保存
				$v ['id'] = null;
				$v ['corp_id'] = $id;
				$v ['group_id'] = $group_id;

				if($v['corp_commission_type'] != 2){
					// 取次形態が"成約ベース"の場合
					$v['introduce_fee'] = null;
				}else{
					// 取次形態が"紹介ベース"の場合
					$v['introduce_fee'] = $v['order_fee'];
					$v['order_fee'] = null;
					$v['order_fee_unit'] = null;
				}

				$save_data['CorpCategoryApplication'] = $v;

				if (!$this->CorpCategoryApplication->save ( $save_data )) {
					$category_error [0] = 'CorpCategoryApplicationデータの作成に失敗しました';
					$this->set ( 'category_error', $category_error );
					return false;
				}
				//申請データの作成・保存
				$application['Approval'] = array(
					'relation_application_id' => $this->CorpCategoryApplication->getLastInsertId(),
					'application_section' => 'CorpCategoryApplication',
					'application_reason' => $save_data['CorpCategoryApplication']['application_reason'],
					'application_datetime' => date('Y-m-d h:i:s'),
					'application_user_id' => $this->User['user_id'],
					'status' => -1,
				);
				$this->Approval->create();
				if (!$this->Approval->save ( $application )) {
					$category_error [0] = 'Approvalデータの作成に失敗しました';
					$this->set ( 'category_error', $category_error );
					return false;
				}
			}

			return true;

		}catch(Exception $e){
			$this->log($e->getMessage());
			$category_error [0] = 'データの作成時エラーが発生しました';
			$this->set ( 'category_error', $category_error );
			return false;
		}
	}
	//ORANGE-393 ADD E

	/**
	 * 企業別対応カテゴリマスタ(一時用)の登録
	 *
	 * @param unknown $id
	 * @param unknown $data
	 */
	private function __edit_corp_categories_temp($id, $data) {
		$resultsFlg = false; // 更新結果フラグ

// 2016.09.23 murata.s ORANGE-190 ADD(S) 旧契約状態時のカテゴリデータが表示される
		$temp_data = $this->__find_categories_temp_copy($id, $data['temp_id']);
		$registed_data = array_filter($temp_data, function($v){
			return !empty($v['MCorpCategoriesTemp']['id']);
		});
		if(empty($registed_data)){
			if(!empty($temp_data))
				$this->MCorpCategoriesTemp->saveAll($temp_data);
			// 登録するデータにtemp_idを再設定
			$temp_data = $this->MCorpCategoriesTemp->find('all', array(
					'conditions' => array(
							'corp_id' => $id,
							'temp_id' => $data['temp_id']
					)
			));
			if(!empty($data['MCorpCategoriesTemp'])){
				foreach($data['MCorpCategoriesTemp'] as $k=>$v){
					$find_data = array_filter($temp_data, function($item) use($v){
						return $item['MCorpCategoriesTemp']['genre_id'] == $v['genre_id']
							&& $item['MCorpCategoriesTemp']['category_id'] == $v['category_id'];
					});
					if(!empty($find_data)){
						$shift_data = array_shift($find_data);
						$data['MCorpCategoriesTemp'][$k]['id'] = $shift_data['MCorpCategoriesTemp']['id'];
					}
				}
			}
		}
// 2016.09.23 murata.s ORANGE-190 ADD(E) 旧契約状態時のカテゴリデータが表示される

		if(!empty($data ['MCorpCategoriesTemp'])) {
			$count = 0;
			foreach ( $data ['MCorpCategoriesTemp'] as $k => $v ) {

				$v ['corp_id'] = $id;
// murata.s ORANGE-261 ADD(S)
				if($v['corp_commission_type'] != 2){
					// 取次形態が"成約ベース"の場合
					$v['introduce_fee'] = null;
				}else{
					// 取次形態が"紹介ベース"の場合
					$v['introduce_fee'] = $v['order_fee'];
					$v['order_fee'] = null;
					$v['order_fee_unit'] = null;
				}
// murata.s ORANGE-261 ADD(E)
				$save_data [] = array (
						'MCorpCategoriesTemp' => $v
				);
				$resultsFlg = $this->__edit_corp_category_check_for_category ( $data ['MCorpCategoriesTemp'], $v ['category_id'], $k);
				if (! $resultsFlg) {
					$category_error [$count] = __ ( 'MCorpCategoryError', true );
					$this->set ( 'category_error', $category_error );
					break;
				}
				$count ++;
			}
		}

		if (! empty ( $save_data )) {
			if ($resultsFlg) {
				// 登録
				if ($this->MCorpCategoriesTemp->saveAll ( $save_data )) {
					return true;
				}
			}
			return false;
		} else {
			return true;
		}
	}

// 2016.09.01 murata.s ORANGE-174 CHG(S)
	/**
	 * 再契約処理
	 *
	 * @param string $id 企業ID
	 * @param string $kind 契約種別
	 * @return boolean
	 */
	private function __edit_corp_categories_resigning($id = null, $kind = 'WEB'){
		$results = false;

		// 契約毎の一時データを取得
		$temp_link = $this->CorpAgreementTempLink->find('first', array(
				'fields' => '*, CorpAgreement.status',
				'joins' => array(
						array(
								'fields' => '*',
								'type' => 'left',
								'table' => 'corp_agreement',
								'alias' => 'CorpAgreement',
								'conditions' => array(
										'CorpAgreement.id = CorpAgreementTempLink.corp_agreement_id'
								)
						)
				),
				'conditions' => array('CorpAgreementTempLink.corp_id' => $id),
				'order' => array('CorpAgreementTempLink.id' => 'desc')
		));

		if(empty($temp_link)){
			// corp_agreemenetの作成
			$results = self::__insert_corp_agreement($id, $kind);

			// corp_agreement_temp_linkの作成
			if($results){
				$results = self::__insert_corp_agreement_temp_link($id, $results['CorpAgreement']['id']);
			}
		}else if(empty($temp_link['CorpAgreementTempLink']['corp_agreement_id'])){
			// corp_agreemenetの作成
			$results = self::__insert_corp_agreement($id, $kind);

			if($results){
				// corp_agreement_temp_linkの作成
				$temp_link['CorpAgreementTempLink']['corp_agreement_id'] = $results['CorpAgreement']['id'];
				$results = $this->CorpAgreementTempLink->save($temp_link);
			}
		}else{
			$results = true;
		}

		return $results;
	}
// 2016.09.01 murata.s ORANGE-174 CHG(E)

	/**
	 * 企業別対応カテゴリマスタの登録
	 *
	 * @param unknown $id
	 * @param unknown $data
	 */
	private function __edit_corp_category_temp_genre($id, $data, &$del_array, $temp_id) {
		$resultsFlg = false; // 更新結果フラグ

		$count = 0;

// 2016.09.23 murata.s ORANGE-190 ADD(S) 旧契約状態時のカテゴリデータが表示される
		$temp_data = $this->__find_categories_temp_copy($id, $temp_id);
		$registed_data = array_filter($temp_data, function($v) {
			return !empty($v['MCorpCategoriesTemp']['id']);
		});
		if(empty($registed_data)){
			if(!empty($temp_data)){
				$this->MCorpCategoriesTemp->saveAll($temp_data);
				// 再取得
				$temp_data = $this->__find_categories_temp_copy($id, $temp_id);
			}
			// チェック対象のIDを再設定
			$chk_mcc_id_array = array();
			foreach($temp_data as $v){
				$chk_mcc_id_array[] = $v['MCorpCategoriesTemp']['id'];
			}
			$data['chk_mcc_id'] = implode('-', $chk_mcc_id_array);

 			// 一時用カテゴリIDの再設定
			$data = $this->__set_check_mcc_id($temp_data, $data['corp_commission_type'], $data);
		}
// 2016.09.23 murata.s ORANGE-190 ADD(E) 旧契約状態時のカテゴリデータが表示される

		//コピー元のカテゴリーIdを取得1
		$org_category_id_array = array();
		foreach ($data['MCorpCategoriesTemp'] as $org_mcc_data) {
			if (!empty($org_mcc_data['category_id'])) {
				$org_id_array = split('-', $org_mcc_data['category_id']);
				$org_category_id_array[] = $org_id_array[1];
			}
		}
		// コピー先のカテゴリーIDを取得2
		$copyCategoryIds = $this->MCategoryCopyrule->find('list', array(
				'fields'     => 'MCategoryCopyrule.copy_category_id',
				'conditions' => array('MCategoryCopyrule.org_category_id' => $org_category_id_array),
		));

		// コピー先に該当する企業別対応カテゴリマスタに登録済みのカテゴリーを取得
		$this->MCorpCategoriesTemp->unbindModel(
				array('belongsTo' => array('MCorp'))
		);
		$copyCorpCategories = $this->MCorpCategoriesTemp->find('all', array(
				'conditions' =>  array(
						'MCorpCategoriesTemp.category_id' => $copyCategoryIds,
						'MCorpCategoriesTemp.temp_id' => $temp_id,
						'MCorpCategoriesTemp.delete_flag' => false
				),
		));
		// コピー先に該当するカテゴリーを取得
		$categories = $this->MCategory->find('all', array(
				'fields' => '*, MGenre.commission_type',
				'conditions' =>  array(
						'MCategory.id' => $copyCategoryIds
				),
				'joins' => array(
						array(
								'table' => 'm_genres',
								'alias' => 'MGenre',
								'type' => 'LEFT',
								'conditions' => array(
										'MCategory.genre_id = MGenre.id',
								)
						)
				)
		));
// 		$corpCategoryId = '';
		// コピー先に対応する企業別対応カテゴリ用の配列を挿入
		foreach ($categories as $category) {
			$corpCategoryId = '';
			$corpCommissionType = $category['MGenre']['commission_type'];
			foreach ($copyCorpCategories as $corpCategory) {

				if ($corpCategory['MCorpCategoriesTemp']['corp_id'] == $id
						&& $corpCategory['MCorpCategoriesTemp']['genre_id'] == $category['MCategory']['genre_id']
						&& $corpCategory['MCorpCategoriesTemp']['category_id'] ==  $category['MCategory']['id']) {
					$corpCategoryId = '-'. $corpCategory['MCorpCategoriesTemp']['id'];
					$corpCommissionType = $corpCategory['MCorpCategoriesTemp']['corp_commission_type'];
					break;
				}
			}

// 2016.07.05 murata.s ADD(S) コピー対象カテゴリが重複している場合、同一カテゴリのレコードが重複して登録される
			$added_category = array_filter($data['MCorpCategoriesTemp'], function($val) use($category){
				if(!isset($val['category_id'])) return false;
				$id_array = split('-', $val['category_id']);
				if(count($id_array) >= 2)
					return $category['MCategory']['genre_id'] == $id_array[0] && $category['MCategory']['id'] == $id_array[1];
				else
					return false;
			});
			// すでにコピー先のカテゴリが登録されている場合は登録しない
			if(!empty($added_category)) continue;
// 2016.07.05 murata.s ADD(E) コピー対象カテゴリが重複している場合、同一カテゴリのレコードが重複して登録される

// murata.s ORANGE-261 CHG(S)
			// コピー元の手数料率と単位をコピーしないといけない。
			$data['MCorpCategoriesTemp'][] = array(
					'default_fee' => $category['MCategory']['category_default_fee'],
					'default_fee_unit' => $category['MCategory']['category_default_fee_unit'],
					'commission_type' => $category['MGenre']['commission_type'],
					'category_id' => $category['MCategory']['genre_id'] . '-' . $category['MCategory']['id'] . $corpCategoryId,
					'corp_commission_type' => $corpCommissionType,
			);
// murata.s ORANGE-261 CHG(E)
		}

		// 2015.08.27 s.harada ADD start 画面デザイン変更対応
		$del_mcc_id_array = array();
		$chk_mcc_id = $data['chk_mcc_id'];
		if (strlen($chk_mcc_id) > 0) {
			$mcc_id_array = $del_mcc_id_array = split('-', $chk_mcc_id);
		}

		$tmp_del_array = array();

		foreach ( $data ['MCorpCategoriesTemp'] as $v ) {

			$v['corp_id'] = $id;
			$v['temp_id'] = $temp_id;

			// 2015.08.27 s.harada ADD start 画面デザイン変更対応
			if (!isset($v['category_id'])) {
				continue;
			}
			$id_array = split('-', $v['category_id']);
			if (count($id_array) == 3) {
				$v['id'] = $id_array[2];
				// 削除用id配列から除外
				foreach ($del_mcc_id_array as $key => $value) {
					if ($value == $v['id']) {
						array_splice($del_mcc_id_array, $key, 1);
						break;
					}
				}
			} else {
// murata.s ORANGE-356 DEL(S)
// 				// 重複登録防止チェック
// 				$check_record = $this->MCorpCategoriesTemp->getMCorpCategoryID2($id, $id_array[0], $id_array[1], $temp_id);
// 				if ($check_record != null) {
// 					continue;
// 				}
// murata.s ORANGE-356 DEL(E)
				// 新規ジャンル登録の場合は、デフォルト手数料とデフォルト手数料単位を受注手数料と受注手数料単位に書き込む
				if ($v['commission_type'] == 2 ) {
					// 紹介ベースジャンルの場合、紹介手数料にセットする。
					$v['introduce_fee']      = $v['default_fee'];
				} else {
					// 受注手数料関連項目にセットする。
					$v['order_fee']      = $v['default_fee'];
					$v['order_fee_unit'] = $v['default_fee_unit'];
				}
// murata.s ORANGE-261 ADD(S)
				$v['corp_commission_type'] = $v['commission_type'];
// murata.s ORANGE-261 ADD(E)
			}
			$v['genre_id'] = $id_array[0];
			$v['category_id'] = $id_array[1];
			//
			unset($v['table_disc_id']);
			unset($v['default_fee']);
			unset($v['default_fee_unit']);
			$v = array_merge($v);
			// 2015.08.27 s.harada ADD end 画面デザイン変更対応

			$save_data [] = array (
					'MCorpCategoriesTemp' => $v
			);
			$resultsFlg = $this->__edit_corp_category_check ( $data ['MCorpCategoriesTemp'], $v ['category_id'], $count );
			if (! $resultsFlg) {
				$category_error [$count] = __ ( 'MCorpCategoryError', true );
				$this->set ( 'category_error', $category_error );
				break;
			}
			$count ++;
		}

		foreach ($del_mcc_id_array as $key => $value) {

			$orgCategoryCategoryIds = $this->MCorpCategoriesTemp->find('list', array(
					'fields' => 'MCorpCategoriesTemp.category_id',
					'conditions' => array('MCorpCategoriesTemp.id' => $value),
			));

			$copyCategoryCategoryIds = $this->MCategoryCopyrule->find('list', array(
					'fields' => 'MCategoryCopyrule.copy_category_id',
					'conditions' => array('MCategoryCopyrule.org_category_id' => $orgCategoryCategoryIds),
			));

			$MCorpCategoryIds = $this->MCorpCategoriesTemp->find('list', array(
					'fields' => 'MCorpCategoriesTemp.id',
					'conditions' => array('MCorpCategoriesTemp.category_id' => $copyCategoryCategoryIds,
							'MCorpCategoriesTemp.temp_id' => $temp_id,
							'MCorpCategoriesTemp.corp_id' => $id),
			));

			$tmp_del_array = $tmp_del_array + $MCorpCategoryIds;

		}
		if (isset($tmp_del_array)) {
			$del_array = $tmp_del_array;
			$del_mcc_id_array = $del_mcc_id_array + $tmp_del_array;
		}
		// チェック済みからチェックをはずしたカテゴリを削除
		foreach ($del_mcc_id_array as $key => $value) {
			// カテゴリの削除
//			$this->MCorpCategoriesTemp->delete($value);
			$dell_mcc = $this->MCorpCategoriesTemp->findById($value);
			if(!empty($dell_mcc)){
				$dell_mcc['MCorpCategoriesTemp']['delete_flag'] = true;
				$this->MCorpCategoriesTemp->create();
				$this->MCorpCategoriesTemp->save($dell_mcc);
			}
		}
		if (! empty ( $save_data )) {
			if ($resultsFlg) {
// ORANGE-356 CHG(S)
				// 登録
				foreach($save_data as $v){
					// 新規登録カテゴリの場合、カテゴリ重複チェックを実施
					if(empty($v['MCorpCategoriesTemp']['id'])){
						$check_record = $this->MCorpCategoriesTemp->getMCorpCategoryID2($id,
								$v['MCorpCategoriesTemp']['genre_id'], $v['MCorpCategoriesTemp']['category_id'], $temp_id);

						if(!empty($check_record)){
							$v['MCorpCategoriesTemp']['id'] = $check_record['MCorpCategoriesTemp']['id'];
						}
					}

					$this->MCorpCategoriesTemp->create();
					if(!$this->MCorpCategoriesTemp->save($v)){
						$resultsFlg = false;
						break;
					}
				}
				return $resultsFlg;
// ORANGE-356 CHG(E)
			}
			return false;
		} else {
			return true;
		}
	}

	/**
	 * 一時用データを企業別カテゴリマスタに登録する
	 *
	 * @param string $corp_id 企業ID
	 */
	private function __update_corp_category_form_temp($id = null, $corp_agreement_id = null){
		$resultsFlg = true;
		$corp_data = $this->MCorp->find('first', array(
				'conditions' => array('id' => $id)
		));

		$temp_link = $this->CorpAgreementTempLink->find('first', array(
				'fields' => 'id',
				'conditions' => array(
						'corp_id' => $id,
						'corp_agreement_id' => $corp_agreement_id
				),
				'order' => array('id' => 'desc')
		));
		$temp_id = $temp_link['CorpAgreementTempLink']['id'];

// murata.s ORANGE-261 CHG(S)
		$temp_data = $this->MCorpCategoriesTemp->find('all', array(
				'fields' => '*, MCorpCategory.id, MCorpCategory.order_fee, MCorpCategory.order_fee_unit, MCorpCategory.introduce_fee, MCorpCategory.note, MCorpCategory.select_list, MCorpCategory.corp_commission_type',
				'joins' => array(
						array(
								'fields' => '*',
								'type' => 'left',
								'table' => 'm_corp_categories',
								'alias' => 'MCorpCategory',
								'conditions' => array(
										'MCorpCategory.corp_id = MCorpCategoriesTemp.corp_id',
										'MCorpCategory.category_id = MCorpCategoriesTemp.category_id',
										'MCorpCategory.genre_id = MCorpCategoriesTemp.genre_id'
								)
						)
				),
				'conditions' => array(
						'MCorpCategoriesTemp.corp_id' => $id,
						'MCorpCategoriesTemp.temp_id' => $temp_id,
						//'MCorpCategoriesTemp.delete_flag' => false
				),
				'order' => array('MCorpCategoriesTemp.id' => 'desc')
		));
// murata.s ORANGE-261 CHG(E)
		$data = array();
		foreach($temp_data as $key => $val){

// 2016.07.25 murata.s ADD(S) すでにマスタが削除されている場合、m_corp_categories登録時にDBエラーが発生する
			// m_corp_categories反映時にm_genres, m_categoriesが削除されている場合、
			// DBエラー(外部キー参照エラー)が発生するため、すでに削除されている場合はそのデータは更新しない
			$find_count = $this->MCategory->find('count', array(
					'joins' => array(
							array(
								'fields' => '*',
								'type' => 'inner',
								'table' => 'm_genres',
								'alias' => 'MGenre',
								'conditions' => array(
										'MGenre.id = MCategory.genre_id'
								)
							)
					),
					'conditions' => array(
							'MCategory.id' => $val['MCorpCategoriesTemp']['category_id'],
							'MCategory.genre_id' => $val['MCorpCategoriesTemp']['genre_id']
					)
			));
			// 対応するカテゴリがなければ、m_corp_categoriesへ登録しない
			// (対象レコードを無視し、変更内容チェックも行わない)
			if(empty($find_count))
				continue;
// 2016.07.25 murata.s ADD(E)

			if($val['MCorpCategoriesTemp']['delete_flag']){
				$temp_data[$key]['MCorpCategoriesTemp']['action'] = 'Delete';
				continue;
			}

// murata.s ORANGE-261 CHG(S)
			$data['MCorpCategory'][] = array(
					'id' => isset($val['MCorpCategory']['id']) ? $val['MCorpCategory']['id'] : '',
					'corp_id' => $val['MCorpCategoriesTemp']['corp_id'],
					'genre_id' => $val['MCorpCategoriesTemp']['genre_id'],
					'category_id' => $val['MCorpCategoriesTemp']['category_id'],
					'order_fee' => $val['MCorpCategoriesTemp']['order_fee'],
					'order_fee_unit' => $val['MCorpCategoriesTemp']['order_fee_unit'],
					'introduce_fee' => $val['MCorpCategoriesTemp']['introduce_fee'],
					'note' => $val['MCorpCategoriesTemp']['note'],
					'select_list' => $val['MCorpCategoriesTemp']['select_list'],
					'select_genre_category' => $val['MCorpCategoriesTemp']['select_genre_category'],
					'target_area_type' => $val['MCorpCategoriesTemp']['target_area_type'],
					'modified_user_id' => $val['MCorpCategoriesTemp']['modified_user_id'],
					'modified' => $val['MCorpCategoriesTemp']['modified'],
					'created_user_id' => $val['MCorpCategoriesTemp']['created_user_id'],
					'created' => $val['MCorpCategoriesTemp']['created'],
					'corp_commission_type' => $val['MCorpCategoriesTemp']['corp_commission_type']
			);
// murata.s ORANGE-261 CHG(E)
			// 変更項目の取得
			$action = null;
			if(isset($val['MCorpCategory']['id'])){
				$action = $this->__check_update_column($val['MCorpCategory'], $val['MCorpCategoriesTemp']);
				$action = empty($action) ? null : $action;
			}else{
				$action = 'Add';
			}

			$temp_data[$key]['MCorpCategoriesTemp']['action'] = $action;
		}

		try {
			if(empty($data)) return $resultsFlg;
			// 企業別対応カテゴリマスタの登録
			$del_array = array();
			$resultsFlg = $this->__edit_corp_category_genre2( $id, $data, $del_array );

			if($resultsFlg) {
				$resultsFlg = $this->MCorpCategoriesTemp->saveAll($temp_data);
			}

			if ($resultsFlg) {
				//MCorpCategoriesTempとMCorpCategoryのIDを取り直す
				$mcc_temp_list = $this->MCorpCategoriesTemp->find('all', array(
						'fields' => 'MCorpCategoriesTemp.id, MCorpCategory.id',
						'joins' => array(
								array(
										'fields' => '*',
										'type' => 'left',
										'table' => 'm_corp_categories',
										'alias' => 'MCorpCategory',
										'conditions' => array(
												'MCorpCategory.corp_id = MCorpCategoriesTemp.corp_id',
												'MCorpCategory.category_id = MCorpCategoriesTemp.category_id',
												'MCorpCategory.genre_id = MCorpCategoriesTemp.genre_id'
										)
								)
						),
						'conditions' => array(
								'MCorpCategoriesTemp.corp_id' => $id,
								'MCorpCategoriesTemp.temp_id' => $temp_id,
						),
						'order' => array('MCorpCategoriesTemp.id' => 'desc')
				));
				
				foreach( $mcc_temp_list as $mcc_temp ){
					$resultsFlg = $this->MTargetArea->deleteAll(array('MTargetArea.corp_category_id' => Hash::get($mcc_temp,'MCorpCategory.id')), false);
					if (!$resultsFlg) {
						break;
					}
					$sql = '';
					$sql[] = 'INSERT INTO m_target_areas';
					$sql[] = '(   corp_category_id,     jis_cd, modified_user_id,          modified, created_user_id,           created,               address1_cd )';
					$sql[] = 'SELECT DISTINCT';
					$sql[] = '  CAST(? AS integer), MTT.jis_cd,                ?, current_timestamp,               ?, current_timestamp, SUBSTRING(MTT.jis_cd,1,2) ';
					$sql[] = 'FROM';
					$sql[] = '  m_target_areas_temp MTT';
					$sql[] = 'WHERE';
					$sql[] = '  MTT.corp_category_id = ?;';

					$resultsFlg = ( false !== $this->MTargetArea->query(implode("\n", $sql), array(Hash::get($mcc_temp,'MCorpCategory.id'), $this->Auth->user('id'), $this->Auth->user('id'), Hash::get($mcc_temp, 'MCorpCategoriesTemp.id')), false));
					if (!$resultsFlg) {
						break;
					}
				}
				//$resultsFlg = $this->__edit_target_area_genre2( $id, $data ,$del_array);
				if ($resultsFlg) {
					$this->MTargetArea->commit ();
					$this->AffiliationAreaStat->begin();
					$resultsFlg = $this->__create_affiliation_area_stats_data( $id );
					if (!$resultsFlg) {
						$this->AffiliationAreaStat->rollback();
					}
				} else {
					$resultsFlg = false;
				}
			} else {
				$resultsFlg = false;
			}
		} catch ( Exception $e ) {
			$resultsFlg = false;
		}

		return $resultsFlg;
	}

	private function __check_update_column($src, $dest){
		$update_column = array();

		// すでに差分をチェック済み
		if(!empty($dest['action'])) return $dest['action'];

		// order_fee
		if($src['order_fee'] !== $dest['order_fee'])
			$update_column[] = 'order_fee';
		// order_fee_unit
		if($src['order_fee_unit'] !== $dest['order_fee_unit'])
			$update_column[] = 'order_fee_unit';
		// introduce_fee
		if($src['introduce_fee'] !== $dest['introduce_fee'])
			$update_column[] = 'introduce_fee';
// murata.s ORANGE-261 ADD(S)
		// corp_commission_type
		if($src['corp_commission_type'] !== $dest['corp_commission_type'])
			$update_column[] = 'corp_commission_type';
// murata.s ORANGE-261 ADD(E)

		$src_note = empty($src['note']) ? '' : $src['note'];
		$dest_note = empty($dest['note']) ? '' : $dest['note'];

		$src_select_list = empty($src['select_list']) ? '' : $src['select_list'];
		$dest_select_list = empty($dest['select_list']) ? '' : $dest['select_list'];

		// note
		if($src_note !== $dest_note)
			$update_column[] = 'note';
		// select_list
		if($src_select_list !== $dest_select_list)
			$update_column[] = 'select_list';

		if(count($update_column) > 0){
			$ret_str = implode(',', $update_column);
			if($ret_str !== '') $ret_str = 'Update:'.$ret_str;
		}else{
			$ret_str = null;
		}

		return $ret_str;
	}

	/**
	 * 企業別対応カテゴリマスタの登録
	 *
	 * @param unknown $id
	 * @param unknown $data
	 */
	private function __edit_corp_category_genre2($id, $data, &$del_array) {
		$resultsFlg = false; // 更新結果フラグ

		$count = 0;

		$tmp_del_array = array();

		// MCorpCategoryの全てを一旦削除対象としてセット
		$del_mcc_id_array = $this->MCorpCategory->find('list', array(
				'fields' => 'id',
				'conditions' => array('corp_id' => $id)
		));

		foreach ( $data ['MCorpCategory'] as $v ) {

			if (!isset($v['category_id'])) {
				continue;
			}

			$save_data [] = array (
					'MCorpCategory' => $v
			);
			// 削除用id配列から除外
			foreach ($del_mcc_id_array as $key => $value) {
				if ($value == $v['id']) {
					unset($del_mcc_id_array[$key]);
					break;
				}
			}

			$resultsFlg = $this->__edit_corp_category_check ( $data ['MCorpCategory'], $v ['category_id'], $count );
			if (! $resultsFlg) {
				$category_error [$count] = __ ( 'MCorpCategoryError', true );
				$this->set ( 'category_error', $category_error );
				break;
			}
			$count ++;
		}

		// チェック済みからチェックをはずしたカテゴリを削除
		foreach ($del_mcc_id_array as $key => $value) {
			// カテゴリの削除
			$this->MCorpCategory->delete($value);
		}

		if (! empty ( $save_data )) {
			if ($resultsFlg) {
				// 登録
				if($this->MCorpCategory->saveAll($save_data, array('validate' => false))){
					return true;
				}
			}
			return false;
		} else {
			return true;
		}
	}

	/**
	 * カテゴリエリアマスタへ登録
	 *
	 * @param unknown $id
	 * @param unknown $data
	 */
	private function __edit_target_area_genre2($id, $data, $del_array) {
		$resultsFlg = false; // 更新結果フラグ

		// MCorpCategoryの全てを一旦削除対象としてセット
		$del_mcc_id_array = $this->MCorpCategory->find('list', array(
				'fields' => 'id',
				'conditions' => array('corp_id' => $id)
		));

		//カテゴリ分エリアマスターを取得し、登録する
		$save_data = array();
		foreach ($data['MCorpCategory'] as $v) {

			if (!isset($v['category_id'])) {
				continue;
			}
			foreach ($del_mcc_id_array as $key => $value) {
				if ($value == $v['id']) {
					unset($del_mcc_id_array[$key]);
					break;
				}
			}

		}

		// チェック済みからチェックをはずしたカテゴリのエリアを全削除
		foreach ($del_mcc_id_array as $key => $value) {
			// エリアを全削除
			$result = $this->MTargetArea->deleteAll(array('MTargetArea.corp_category_id' => $value), false);
		}
		// チェック済みからチェックをはずしたカテゴリのエリアの削除処理完了

		// 対応エリアなしカテゴリに基本対応エリアを設定開始

		//企業エリアマスターの取得
		$corp_areas = $this->MCorpTargetArea->find('all', array(
				'fields' => '*',
				'conditions' => array(
						'corp_id = ' . $id
				)
		)
		);
		// 同一会社IDを全て更新
		$id_list = $this->MCorpCategory->getMCorpCategoryIDList2($id);
		foreach ($id_list as $key => $val) {
			// 指定した「corp_category_id」のエリア数
			$area_count = $this->MTargetArea->getCorpCategoryTargetAreaCount($val['MCorpCategory']['id']);
			if ($area_count > 0) {
				continue;
			}

			foreach ($corp_areas as $area) {
				$set_data = array();
				$set_data['corp_category_id'] = $val['MCorpCategory']['id'];
				$set_data['jis_cd'] = $area['MCorpTargetArea']['jis_cd'];
				$save_data[] = array('MTargetArea' => $set_data);
			}

			$g_list = $this->MCorpCategory->getMCorpCategoryIDList($id, $val['MCorpCategory']['genre_id']);
			foreach ($g_list as $g_val) {
				if ($g_val['MCorpCategory']['target_area_type'] > 0) {
					self::__edit_corp_category_target_area_type($val['MCorpCategory']['id'], $g_val['MCorpCategory']['target_area_type']);
					break;
				}
			}
		}
		if (count($save_data) > 0) {
			if ( $this->MTargetArea->saveAll($save_data) ) {
				return true;
			} else {
				return false;
			}
		} else {
			return true;
		}
		// 対応エリアなしカテゴリに基本対応エリアを設定終了
	}
// 2016.06.23 murata.s ORANGE-102 ADD(E)

// 2016.09.23 murata.s ORANGE-190 ADD(S) 旧契約状態時のカテゴリデータが表示される
	/**
	 * temp_idに紐付くカテゴリデータを取得、ない場合は１つ前のカテゴリデータを取得
	 * @param unknown $corp_id 企業ID
	 * @param unknown $temp_id 一時ID
	 */
	private function __find_categories_temp_copy($corp_id, $temp_id = null){

		// ORANGE-393 CHG(S)
		// カテゴリデータ取得処理をModel/MCorpCategoriesTempへ移動
		return $this->MCorpCategoriesTemp->findCategoryTempCopy($corp_id, $temp_id);
		// ORANGE-393 CHG(E)
	}

	/**
	 * 全てのカテゴリをtemp_idに紐付けて取得する
	 * @param unknown $corp_id 企業ID
	 * @param unknown $temp_id 一時ID
	 */
	private function __find_work_all_category_copy($corp_id, $temp_id = null){

		$limit = empty($temp_id) ? 1 : 2;

		$mcc_count = $this->__get_category_temp_data_count($corp_id, $temp_id);

		if($mcc_count == 0){
			$temp_link = $this->CorpAgreementTempLink->find('all', array(
					'conditions' => array('corp_id' => $corp_id),
					'order' => array('id' => 'desc'),
					'limit' => $limit
			));
			if(count($temp_link) == $limit){
				$temp_link = end($temp_link);
				$results = $this->MCategory->getTempAllList($corp_id, $temp_link['CorpAgreementTempLink']['id']);
					foreach($results as $k=>$v){
						if(isset($v['MCorpCategoriesTemp']['id'])){
							$results[$k]['MCorpCategoriesTemp']['id'] = null;
							$results[$k]['check'] = 'checked';
							$results[$k]['init'] = true;
						}else{
							$results[$k]['check'] = "";
						}
					}
			}else{
				$results = $this->MCategory->getAllList($corp_id);
				foreach($results as $k=>$v){
					if(isset($v['MCorpCategory']['id'])){
						// murata.s ORANGE-261 CHG(S)
						$results[$k]['MCorpCategoriesTemp']['id'] = null;
						$results[$k]['MCorpCategoriesTemp']['modified'] = $v['MCorpCategory']['modified'];
						$results[$k]['MCorpCategoriesTemp']['select_list'] = $v['MCorpCategory']['select_list'];
						$results[$k]['MCorpCategoriesTemp']['corp_commission_type'] = $v['MCorpCategory']['corp_commission_type'];
						$results[$k]['check'] = 'checked';
						$results[$k]['init'] = true;
						// murata.s ORANGE-261 CHG(E)
					}else{
						$results[$k]['MCorpCategoriesTemp']['id'] = null;
						$results[$k]['MCorpCategoriesTemp']['modified'] = null;
						$results[$k]['MCorpCategoriesTemp']['select_list'] = null;
						$results[$k]['check'] = "";
					}
				}
			}
		}else{
			$results = $this->MCategory->getTempAllList($corp_id, $temp_id);
			foreach($results as $k=>$v){
				if(isset($v['MCorpCategoriesTemp']['id'])){
					$results[$k]['check'] = 'checked';
					$results[$k]['init'] = true;
				}else{
					$results[$k]['check'] = "";
				}
			}
		}
		return $results;
	}

	/**
	 * 登録済み一時カテゴリデータ件数を取得する
	 * @param unknown $corp_id 企業ID
	 * @param unknown $temp_id 一時ID
	 */
	private function __get_category_temp_data_count($corp_id, $temp_id){
		return $this->MCorpCategoriesTemp->find('count', array(
				'conditions' => array(
						'corp_id' => $corp_id,
						'temp_id' => $temp_id
				)
		));
	}

	/**
	 * 一時カテゴリIDを再設定する
	 *
	 * @param unknown $data
	 * @param unknown $commission_type
	 * @param unknown $temp_data
	 */
	private function __set_check_mcc_id($temp_data, $commission_type, $data){
		// 一時用カテゴリIDを再設定
		foreach($data['MCorpCategoriesTemp'] as $k => $v){
			if(isset($v['category_id'])){
				$id_array = split('-', $v['category_id']);
				$registed_temp_data = array_filter($temp_data, function($item) use($id_array){
					return $item['MCorpCategoriesTemp']['genre_id'] == $id_array[0]
						&& $item['MCorpCategoriesTemp']['category_id'] == $id_array[1];
				});
				if(!empty($registed_temp_data)){
					$registed_temp_data = array_shift($registed_temp_data);
					if($this->User["auth"] == 'affiliation'){
						switch($commission_type){
							case 1:
								if($v['MCorpCategoriesTemp']['commission_type'] == 1){
									$id_array[2] = $registed_temp_data['MCorpCategoriesTemp']['id'];
									$data['MCorpCategoriesTemp'][$k]['category_id'] = implode('-', $id_array);
								}
								break;
							case 2:
								if($v['MCorpCategoriesTemp']['commission_type'] == 2){
									$id_array[2] = $registed_temp_data['MCorpCategoriesTemp']['id'];
									$data['MCorpCategoriesTemp'][$k]['category_id'] = implode('-', $id_array);
								}
								break;
							case 3:
								if($v['MCorpCategoriesTemp']['commission_type'] == 1
									|| $v['MCorpCategoriesTemp']['commission_type'] == 2){
									$id_array[2] = $registed_temp_data['MCorpCategoriesTemp']['id'];
									$data['MCorpCategoriesTemp'][$k]['category_id'] = implode('-', $id_array);
								}
								break;
							default:
								break;
						}
					}else{
						$id_array[2] = $registed_temp_data['MCorpCategoriesTemp']['id'];
						$data['MCorpCategoriesTemp'][$k]['category_id'] = implode('-', $id_array);
					}
				}
			}
		}
		return $data;
	}
// 2016.09.23 murata.s ORANGE-190 ADD(E) 旧契約状態時のカテゴリデータが表示される

// 2017.01.12 murata.s ORANGE-293 ADD(S)
	/**
	 * 契約内容プレビュー表示
	 * @param string $corp_id 企業ID
	 * @param string $corp_agreement_id 契約ID
	 * @throws Exception
	 */
	public function agreement_preview($corp_id = null, $corp_agreement_id = null){

		if(empty($corp_id) || empty($corp_agreement_id))
			throw new Exception();

		if(!ctype_digit($corp_id) || !ctype_digit($corp_agreement_id))
			throw new ApplicationException(__('NoReferenceAuthority', true));

		// 加盟店情報
		$corp = $this->MCorp->find('first', array(
				'fields' => '*, AffiliationInfo.listed_kind, AffiliationInfo.default_tax, AffiliationInfo.capital_stock, AffiliationInfo.employees',
				'joins' => array(
						array(
								'fields' => '*',
								'type' => 'left',
								'table' => 'affiliation_infos',
								'alias' => 'AffiliationInfo',
								'conditions' => array(
										'AffiliationInfo.corp_id = MCorp.id'
								)
						)
				),
				'conditions' => array(
						'MCorp.id' => $corp_id
				)
		));

		// 企業マスタ付帯情報(休業日)の取得
		$corp_sub = $this->MCorpSub->find('all', array(
				'fields' => '*, MItem.item_name',
				'joins' => array(
						array(
								'fields' => '*',
								'type' => 'left',
								'table' => 'm_items',
								'alias' => 'MItem',
								'conditions' => array(
										'MCorpSub.item_category = MItem.item_category',
										'MCorpSub.item_id = MItem.item_id'
								)
						)
				),
				'conditions' => array(
						'MCorpSub.corp_id' => $corp_id,
						'MCorpSub.item_category' => __ ( 'holiday', true )
				)
		));

		// 会社毎の契約の取得(corp_agreement)
		$corp_agreement = $this->CorpAgreement->findById($corp_agreement_id);

		// 基本対応エリアの取得
		$corp_areas = $this->__get_pref_area_list($corp_id);

		// 対応可能ジャンル(一時用)リストの取得
		$temp_link = $this->CorpAgreementTempLink->find('first', array(
				'fields' => 'id',
				'conditions' => array(
						'corp_id' => $corp_id,
						'corp_agreement_id' => $corp_agreement_id
				),
				'order' => array('id' => 'desc')
		));
		if(!empty($temp_link))
			$temp_id = $temp_link['CorpAgreementTempLink']['id'];
		else
			$temp_id = null;

		$categories = $this->__find_categories_temp_copy($corp_id, $temp_id);
		$categories = array_filter($categories, function($v){
			return $v['MCorpCategoriesTemp']['delete_flag'] == false;
		});

		// 2017/10/18 ORANGE-541 m-kawamoto DEL(S)
		//// 必要証明書リストの取得
		//$category_id = array();
		//foreach($categories as $val){
		//	$category_id[] = $val['MCorpCategoriesTemp']['category_id'];
		//}
		//$license = $this->License->find('all', array(
		//		'fields' => 'License.id, License.name, CorpLiseneLink.have_lisense',
		//		'joins' => array(
		//				array(
		//						'fields' => '*',
		//						'type' => 'inner',
		//						'table' => 'category_license_link',
		//						'alias' => 'CategoryLicenseLink',
		//						'conditions' => array(
		//								'CategoryLicenseLink.license_id = License.id',
		//								'CategoryLicenseLink.category_id' => $category_id,
		//						)
		//				),
		//				array(
		//						'fields' => '*',
		//						'type' => 'left',
		//						'table' => 'corp_lisense_link',
		//						'alias' => 'CorpLiseneLink',
		//						'conditions' => array(
		//								'CorpLiseneLink.lisense_id = License.id',
		//								'CorpLiseneLink.corps_id' => $corp_id,
		//						)
		//				)
		//		),
		//		'group' => 'License.id, License.name, CorpLiseneLink.have_lisense',
		//));
        //
		//// ライセンス別契約に紐付くファイルの取得
		//foreach($license as $key=>$val){
		//	$agreement_attached_file = $this->AgreementAttachedFile->find('all', array(
		//			'conditions' => array(
		//					'corp_id' => $corp_id,
		//					'license_id' => $val['License']['id']
		//			)
		//	));
		//	if(!empty($agreement_attached_file))
		//		foreach($agreement_attached_file as $file)
		//			$license[$key]['AgreementAttachedFile'][] = $file['AgreementAttachedFile'];
		//}
		// 2017/10/18 ORANGE-541 m-kawamoto DEL(E)

		$this->layout = 'subwin';
		// 2017/10/18 ORANGE-541 m-kawamoto CHG(S) license変数削除
		$this->set(compact('corp', 'corp_sub', 'corp_agreement', 'corp_areas', 'categories'));
		// 2017/10/18 ORANGE-541 m-kawamoto CHG(E)
	}

	public function agreement_file_preview($id = null){

		if(!ctype_digit($id)) throw new NotFoundException();

		$this->autoRender = false;

		$options = array(
				'conditions' => array('AgreementAttachedFile.id' => $id),
		);

		$aaf = $this->AgreementAttachedFile->find('first', $options);

		//ファイルがない場合は404
		if(!$aaf)throw new NotFoundException();

		//加盟店チェック
		if ($this->User['auth'] == 'affiliation') {
			//企業IDが異なる場合は404
			if($this->User['affiliation_id'] != $aaf['AgreementAttachedFile']['corp_id']){
				throw new NotFoundException();
			}
		}
		//ローカルファイルが存在すれば表示
		if (file_exists($aaf['AgreementAttachedFile']['path'])) {
			$this->response->file(
					$aaf['AgreementAttachedFile']['path'],
					array('name' => $aaf['AgreementAttachedFile']['name'], 'download'=>false,)
			);

		}else{
			echo 'ファイルがありません。';
		}

	}


	/**
	 * 基本対応エリアを取得する
	 * @param string $corp_id 企業ID
	 */
	private function __get_pref_area_list($corp_id = null){

		// 基本対応エリアの取得
		$corp_areas = array();
		foreach(Util::getDivList('prefecture_div') as $key => $val){
			// 99は読み飛ばし
			if($key == 99) continue;

			$pref = array(
					'id' => $key,
					'name' => $val,
					'rank' => 0
			);
			// 指定した都道府県の加盟店が設定したエリア数
			$corp_count = $this->MPost->getCorpPrefAreaCount($corp_id, $val);
			if ($corp_count > 0) {
				// 指定した都道府県のエリア数
				$area_count = $this->MPost->getPrefAreaCount($val);
				if ($corp_count >= $area_count) $pref['rank'] = 2; // 全地域対応
				else $pref['rank'] = 1;

				$corp_areas[] = $pref;
			}
		}

		return $corp_areas;

	}
// 2017.01.12 murata.s ORANGE-293 ADD(E)
	
//ORANGE-347 ADD S	
	private function edit_area($param_corp_id, $step, $param1, $param2) {
		$tasks = array(
				array('value' => 'edit_all', 'name' => '基本エリア設定', 'prev' => null, 'next' => 'list_genre',),
				array('value' => 'list_genre', 'name' => 'ジャンル別エリア設定', 'prev' => 'edit_all', 'next' => 'list_category',
						'subtask' => array(
								array('value' => 'edit_genre', 'name' => 'ジャンル別エリア設定', 'prev' => 'list_genre', 'next' => '',),
						),
				),
				array('value' => 'list_category', 'name' => 'カテゴリ別エリア設定', 'prev' => 'edit_genre', 'next' => '',
						'subtask' => array(
								array('value' => 'edit_category', 'name' => 'カテゴリ別エリア一覧', 'prev' => 'list_category', 'next' => '',),
						),
				),
		);
		$m_corp = $this->MCorp->read(array('id', 'official_corp_name'), $param_corp_id);
		$corp_id = Hash::get($m_corp, 'MCorp.id');
		$this->set(compact('tasks', 'step', 'm_corp'));
		
		if(!empty($step)){
			if ($step !== 'edit_all' && $step !== 'complete'){
				if ($this->MCorpCategoriesTemp->find('count',array('conditions'=>array('corp_id' => $corp_id, 'temp_id' => -$corp_id))) == 0 ){
					throw new ApplicationException(__('NoReferenceAuthority', true));
				}
			}
		}
		switch ($step) {
			case 'edit_all':
				$this->edit_area_base($corp_id);
				break;
			case 'list_genre':
				$this->list_area_genre($corp_id);
				break;
			case 'edit_genre':
				$this->edit_area_genre($corp_id, $param1);
				break;
			case 'list_category':
				$this->list_area_category($corp_id);
				break;
			case 'edit_category':
				$this->edit_area_category($corp_id, $param1);
				break;
			case 'searchTargetAreaTemp':
				$this->searchTargetAreaTemp($corp_id, $param1, $param2);
				break;
			case 'searchTargetAreaTempByCorpCategoryId':
				$this->searchTargetAreaTempByCorpCategoryId($corp_id, $param1, $param2);
				break;
			case 'confirm':
				$this->edit_area_confirm($corp_id);
				break;
			case 'complete':
				$this->edit_area_complete($corp_id);
				break;
			default:
				$this->redirect(array('action' => 'corptargetarea', $corp_id, 'edit_all'));
		}
	}
	
	private function edit_area_base($corp_id) {
		if ($this->request->is('post')) {
			if ($this->MCorpTargetArea->changeBasicSupportArea($corp_id, Hash::get($this->request->data, 'address1_text'), Hash::get($this->request->data, 'jis_cd'), -$corp_id)) {
				$this->Session->setFlash('基本対応エリアを更新しました。', 'default', array('class' => 'notice_inner'));
			} else {
				$this->Session->setFlash('基本対応エリアの更新に失敗しました。', 'default', array('class' => 'error_inner'));
			}
			$this->redirect(array('action' => 'corptargetarea', $corp_id, 'edit_all'));
		}
		// 現在のデータをテンポラリテーブルにコピー
		$this->MCorpCategoriesTemp->deleteAll(array('corp_id' => $corp_id, 'temp_id' => -$corp_id));
		$this->MCorpCategoriesTemp->copyFromMCorpTargetAndMCorpCategory($corp_id, -$corp_id);
		// 基本対応エリアを取得
		$m_corp_target_area = $this->MCorpTargetArea->findAllAreaCountByCorpId($corp_id);
		$this->set(compact('m_corp_target_area'));
		$this->render('edit_area_base');
	}

	private function list_area_genre($corp_id){
		if ($this->request->is('post')) {
			// ジャンル指定のエリアを基本対応エリアにリセット
			if ($this->MTargetAreasTemp->resetAreaGroupByGenreId($corp_id, -$corp_id, Hash::extract($this->request->data, 'MGenre.{n}.id'))) {
				$this->Session->setFlash('基本対応エリアを更新しました。', 'default', array('class' => 'notice_inner'));
			} else {
				$this->Session->setFlash('基本対応エリアの更新に失敗しました。', 'default', array('class' => 'error_inner'));
			}
			$this->redirect(array('action' => 'corptargetarea', $corp_id, 'list_genre'));
		}
		// 基本対応エリアを取得
		$m_corp_target_area = $this->MCorpTargetArea->findAllAreaCountByCorpId($corp_id);
		// 基本対応エリア反映状況を取得
		$m_corp_categories = $this->MCorpCategoriesTemp->findAllCategoryByCorpId($corp_id, -$corp_id);
		// エリア反映状況はカテゴリ別なので、ジャンル別に再集計する
		$m_corp_genres = array();
		foreach (Hash::extract($m_corp_categories, 'MCorpCategoriesTemp.{n}') as $m_corp_category) {
			if (!array_key_exists($m_corp_category['genre_id'], $m_corp_genres)) {
				$m_corp_genres[$m_corp_category['genre_id']] = array(
						'id' => $m_corp_category['genre_id'],
						'genre_name' => $m_corp_category['genre_name'],
						'target_area_type' => $m_corp_category['target_area_type'],
						'count' => 1,
				);
			} elseif ($m_corp_category['target_area_type'] !== 1) {
				$m_corp_genres[$m_corp_category['genre_id']]['target_area_type'] = $m_corp_category['target_area_type'];
				$m_corp_genres[$m_corp_category['genre_id']]['count'] ++;
			}
		}
		$this->set(compact('m_corp_target_area', 'm_corp_genres'));
		$this->render('list_area_genre');
	}
	
	private function edit_area_genre($corp_id, $genre_id){
		if ($this->request->is('post')) {
			if ($this->area_genre_edit_post($corp_id)) {
				$this->Session->setFlash('対応エリアを更新しました。', 'default', array('class' => 'notice_inner'));
			} else {
				$this->Session->setFlash('対応エリアの更新に失敗しました。', 'default', array('class' => 'error_inner'));
			}
			$this->redirect(array('action' => 'corptargetarea', $corp_id, 'edit_genre', $genre_id));
		}
		// ジャンルの存在確認（とジャンル名の取得を兼ねて）
		$m_genre = $this->MGenre->read(array('id', 'genre_name'), $genre_id);
		if (empty($m_genre)) {
			return $this->redirect(array('action' => 'corptargetarea', $corp_id, 'list_genre'));
		}
		// ジャンルに含まれるカテゴリの取得
		$m_categories = $this->MCategory->find('all', array(
				'fields' => array('DISTINCT MCategory.category_name', 'MCorpCategoriesTemp.id'),
				'joins' => array(
						array(
								'type' => 'INNER',
								'table' => 'm_corp_categories_temp',
								'alias' => 'MCorpCategoriesTemp',
								'conditions' => array(
										'MCategory.id = MCorpCategoriesTemp.category_id',
										'MCorpCategoriesTemp.corp_id' => $corp_id,
										'MCorpCategoriesTemp.temp_id' => -$corp_id,
								),
						),
				),
				'conditions' => array(
						'MCorpCategoriesTemp.genre_id' => Hash::get($m_genre, 'MGenre.id'),
		)));
                
                $m_corp_categories_temp_id = Hash::get($m_categories, '0.MCorpCategoriesTemp.id');
                
		// ジャンルの対応エリアの取得
		$m_target_areas_temp = $this->MTargetAreasTemp->findAllAreaCountByGenreId($corp_id, $genre_id, $m_corp_categories_temp_id);
		$this->set(compact('m_categories', 'm_genre', 'm_target_areas_temp'));
		$this->render('edit_area_genre');
	}
	
	private function list_area_category($corp_id){
		if ($this->request->is('post')) {
			// 指定カテゴリのエリアを基本対応エリアにリセット
			if ($this->MTargetAreasTemp->resetAreaGroupByCategoryId($corp_id, -$corp_id, Hash::extract($this->request->data, 'MCorpCategoriesTemp.{n}.category_id'))) {
				$this->Session->setFlash('基本対応エリアを適用しました。', 'default', array('class' => 'notice_inner'));
			} else {
				$this->Session->setFlash('基本対応エリアの適用に失敗しました。', 'default', array('class' => 'error_inner'));
			}
			$this->redirect(array('action' => 'corptargetarea', $corp_id, 'list_category'));
		}
		// 基本対応エリアを取得
		$m_corp_target_area = $this->MCorpTargetArea->findAllAreaCountByCorpId($corp_id);
		// 基本対応エリア反映状況を取得
		$m_corp_categories_temp = $this->MCorpCategoriesTemp->findAllCategoryByCorpId($corp_id, -$corp_id);
		$this->set(compact('m_corp_target_area', 'm_corp_categories_temp'));
		$this->render('list_area_category');
	}
	
	private function edit_area_category($corp_id, $corp_category_id){
		if ($this->request->is('post')) {
			if ($this->area_category_edit_post($corp_id)) {
				$this->Session->setFlash('対応エリアを更新しました。', 'default', array('class' => 'notice_inner'));
			} else {
				$this->Session->setFlash('対応エリアの更新に失敗しました。', 'default', array('class' => 'error_inner'));
			}
			$this->redirect(array('action' => 'corptargetarea', $corp_id, 'edit_category', $corp_category_id));
		}
		$this->MCorpCategoriesTemp->bindModel(array('belongsTo' => array(
						'MCategory' => array(
								'fields' => array('category_name'),
								'className' => 'MCategory',
								'foreignKey' => 'category_id',
						)
		)));
		$m_corp_categories_temp = $this->MCorpCategoriesTemp->find('first', array('conditions' => array('MCorpCategoriesTemp.id' => $corp_category_id), 'recursive' => 0));
		if (empty($m_corp_categories_temp)) {
			$this->redirect(array('action' => 'area_category'));
		}
		$m_target_areas_temp = $this->MTargetAreasTemp->findAllAreaCountByCorpCategoryId($corp_category_id);
		$this->set(compact('m_target_areas_temp', 'm_corp_categories_temp'));
		$this->render('edit_area_category');
	}
	
	private function searchTargetAreaTemp($corp_id, $genre_id = null, $address1 = null) {
		if (!$this->request->isAll(array('get', 'ajax'))) {
			throw new BadRequestException();
		}
		if (is_numeric($address1)) {
			$address1 = Configure::read("STATE_LIST.{$address1}");
		}
		try {
			$this->autoRender = false;
			$this->response->type('html');
			$results = $this->MPost->find('all', array(
					'fields' => array('MPost.address2', 'MPost.jis_cd', 'max(MTargetArea.id) as "MTargetArea__id"'),
					'joins' => array(
							array('fields' => array('id'),
									'type' => 'LEFT',
									'table' => 'm_target_areas_temp',
									'alias' => 'MTargetArea',
									'conditions' => array('MTargetArea.jis_cd = MPost.jis_cd', 'MTargetArea.genre_id' => $genre_id)
							),
					),
					'conditions' => array('MPost.address1' => $address1),
					'group' => array('MPost.jis_cd', 'MPost.address2'),
					'order' => array('MPost.jis_cd' => 'asc'),
			));
			$this->set("list", $results);
			$this->render('/Ajax/target_area', false);
		} catch (Exception $e) {
			$this->response->statusCode(500);
		}
	}

	private function searchTargetAreaTempByCorpCategoryId($corp_id, $corp_category_id = null, $address1 = null) {
		if (!$this->request->isAll(array('get', 'ajax'))) {
			throw new BadRequestException();
		}
		if (is_numeric($address1)) {
			$address1 = Configure::read("STATE_LIST.{$address1}");
		}
		try {
			$this->autoRender = false;
			$this->response->type('html');
			$results = $this->MPost->find('all', array(
					'fields' => array('MPost.address2', 'MPost.jis_cd', 'max(MTargetArea.id) as "MTargetArea__id"'),
					'joins' => array(
							array('fields' => array('id'),
									'type' => 'LEFT',
									'table' => 'm_target_areas_temp',
									'alias' => 'MTargetArea',
									'conditions' => array('MTargetArea.jis_cd = MPost.jis_cd', 'MTargetArea.corp_category_id' => $corp_category_id)
							),
					),
					'conditions' => array('MPost.address1' => $address1),
					'group' => array('MPost.jis_cd', 'MPost.address2'),
					'order' => array('MPost.jis_cd' => 'asc'),
			));
			$this->set("list", $results);
			$this->render('/Ajax/target_area', false);
		} catch (Exception $e) {
			$this->response->statusCode(500);
		}
	}
	
	/**
	 * ジャンルひとつのエリア編集
	 * @see AgreementsController::target_area($id)
	 */
	private function area_genre_edit_post($corp_id) {
		$category_id_array = Hash::extract($this->MCorpCategoriesTemp->find('all', array(
												'fields' => array('category_id'),
												'conditions' => array(
														'corp_id' => $corp_id,
														'temp_id' => -$corp_id,
														'genre_id' => Hash::get($this->request->data, 'genre_id'),
								))), '{n}.MCorpCategoriesTemp.category_id');
		$select_jis_cd_array = (array) Hash::get($this->request->data, 'jis_cd');
		$ken_cd = Hash::get($this->request->data, 'ken_cd');
		return $this->MTargetAreasTemp->updateAreaGroupByCategory($corp_id, -$corp_id, $category_id_array, $ken_cd, $select_jis_cd_array);
	}
	
	/**
	 * カテゴリひとつのエリア編集
	 */
	private function area_category_edit_post($corp_id) {
		$category_id_array = (array) Hash::get($this->request->data, 'category_id');
		$select_jis_cd_array = (array) Hash::get($this->request->data, 'jis_cd');
		$ken_cd = Hash::get($this->request->data, 'ken_cd');
		return $this->MTargetAreasTemp->updateAreaGroupByCategory($corp_id, -$corp_id, $category_id_array, $ken_cd, $select_jis_cd_array);
	}

	private function edit_area_confirm($corp_id) {
		if ($this->request->is('post')) {
			$create_user_id = $this->Auth->user('id');
			// MCorpCategoriesTemp を取得、ついでに既存のMCorpCategoryも確認しておく
			$this->MCorpCategoriesTemp->unbindModelAll();
			$this->MCorpCategoriesTemp->virtualFields = false;
			$this->MCorpCategoriesTemp->bindModel(array(
					'hasOne' => array(
							'MCorpCategory' => array(
									'className' => 'MCorpCategory',
									'foreignKey' => false,
									'conditions' => array(
											'MCorpCategory.corp_id' => $corp_id,
											'MCorpCategoriesTemp.corp_id = MCorpCategory.corp_id',
											'MCorpCategoriesTemp.genre_id = MCorpCategory.genre_id',
											'MCorpCategoriesTemp.category_id = MCorpCategory.category_id',
									),
							),
					)
			));
			$m_corp_categories_temp_array = $this->MCorpCategoriesTemp->find('all', array(
					'conditions' => array('MCorpCategoriesTemp.corp_id' => $corp_id, 'MCorpCategoriesTemp.temp_id' => -$corp_id),
					'recursive' => 1
			));
			// 本番カテゴリの更新対象IDを取得
			$update_corp_category_id_array = Hash::extract($m_corp_categories_temp_array, '{n}.MCorpCategory[id>0].id');
			// 本番カテゴリの削除対象IDを取得
			$delete_corp_category_id_array = Hash::extract($this->MCorpCategory->find('all', array(
													'fields' => array('MCorpCategory.id'),
													'conditions' => array(
															'corp_id' => $corp_id,
															'NOT' => array('MCorpCategory.id' => $update_corp_category_id_array)
													),
													'recursive' => -1
											)), '{n}.MCorpCategory.id');

			// 削除＆更新＆登録開始
			$this->MTargetArea->begin();

			// 削除対象があれば削除を行う
			if (!empty($delete_corp_category_id_array)) {
				if (false === $MCorpCategory->deleteAll(array('MCorpCategory.id' => $delete_corp_category_id_array))) {
					$this->rollback();
					return false;
				}
				if (false === $MTargetArea->deleteAll(array('MTargetArea.corp_category_id' => $update_corp_category_id_array))) {
					$this->rollback();
					return false;
				}
			}

			foreach ($m_corp_categories_temp_array as &$m_corp_categories_temp) {
				$saving_corp_category_data = array(
						'id' => Hash::get($m_corp_categories_temp, 'MCorpCategory.id'),
						'corp_id' => Hash::get($m_corp_categories_temp, 'MCorpCategoriesTemp.corp_id'),
						'genre_id' => Hash::get($m_corp_categories_temp, 'MCorpCategoriesTemp.genre_id'),
						'category_id' => Hash::get($m_corp_categories_temp, 'MCorpCategoriesTemp.category_id'),
						'order_fee' => Hash::get($m_corp_categories_temp, 'MCorpCategoriesTemp.order_fee'),
						'order_fee_unit' => Hash::get($m_corp_categories_temp, 'MCorpCategoriesTemp.order_fee_unit'),
						'introduce_fee' => Hash::get($m_corp_categories_temp, 'MCorpCategoriesTemp.introduce_fee'),
						'note' => Hash::get($m_corp_categories_temp, 'MCorpCategoriesTemp.note'),
						'modified_user_id' => Hash::get($m_corp_categories_temp, 'MCorpCategoriesTemp.modified_user_id'),
						'modified' => Hash::get($m_corp_categories_temp, 'MCorpCategoriesTemp.modified'),
						'created_user_id' => Hash::get($m_corp_categories_temp, 'MCorpCategoriesTemp.created_user_id'),
						'created' => Hash::get($m_corp_categories_temp, 'MCorpCategoriesTemp.created'),
						'select_list' => Hash::get($m_corp_categories_temp, 'MCorpCategoriesTemp.select_list'),
						'select_genre_category' => Hash::get($m_corp_categories_temp, 'MCorpCategoriesTemp.select_genre_category'),
						'target_area_type' => Hash::get($m_corp_categories_temp, 'MCorpCategoriesTemp.target_area_type'),
				);
				// MCorpCategoryの登録（id自動採番のため）
				if (false === $this->MCorpCategory->save($saving_corp_category_data)) {
					$this->rollback();
					return false;
				}
				// m_corp_categories.idを再取得、新規登録した場合もここで取得
				$mcc_id = Hash::get($m_corp_categories_temp, 'MCorpCategory.id', $this->MCorpCategory->getLastInsertID());
				// m_target_areaは重複可能でそれを削除する手段が無いので常に全件削除→登録とする
				if (false === $this->MTargetArea->deleteAll(array('corp_category_id' => $mcc_id))) {
					$this->rollback();
					return false;
				}
				//MTargetAreasTemp→MTargetAreaへコピー(m_corp_categories.idが必要)
				$sql = '';
				$sql[] = 'INSERT INTO m_target_areas';
				$sql[] = '(   corp_category_id,     jis_cd, modified_user_id,          modified, created_user_id,           created,               address1_cd )';
				$sql[] = 'SELECT DISTINCT';
				$sql[] = '  CAST(? AS integer), MTT.jis_cd,                ?, current_timestamp,               ?, current_timestamp, SUBSTRING(MTT.jis_cd,1,2) ';
				$sql[] = 'FROM';
				$sql[] = '  m_target_areas_temp MTT';
				$sql[] = 'WHERE';
				$sql[] = '  MTT.corp_category_id = ?;';
				if (false === $this->MTargetArea->query(implode("\n", $sql), array($mcc_id, $create_user_id, $create_user_id, Hash::get($m_corp_categories_temp, 'MCorpCategoriesTemp.id')), false)) {
					$this->rollback();
					return false;
				}
			}
			//使い終わったテンポラリデータを削除
			$this->MCorpCategoriesTemp->deleteAll(array('corp_id' => $corp_id, 'temp_id' => -$corp_id));
			$this->MTargetArea->commit();
			$this->redirect(array('action' => 'corptargetarea', $corp_id, 'complete'));
		}
		$m_corp_target_area = $this->MCorpTargetArea->findAllAreaCountByCorpId($corp_id);
		$m_corp_categories_temp = $this->MCorpCategoriesTemp->findAllCategoryByCorpId($corp_id, -$corp_id);
		$this->set(compact('m_corp_target_area', 'm_corp_categories_temp'));
		$this->render('edit_area_confirm');
	}

	private function edit_area_complete($corp_id) {
		$this->render('edit_area_complete');
		return true;
	}
// ORANGE-347 ADD E
        
    /*
     * 2017-07-19 ichino ORANGE-420 ADD start 承認チェック時自動取次先と一致しているか確認
     */
    public function check_auto_commission(){
        if (!$this->request->isAll(array('get', 'ajax'))) {
            throw new BadRequestException();
        }
        
        $this->autoRender = false;
        $this->response->type('json');

        //対象加盟店のIDを取得
        $corp_id = Hash::get($this->request->query, 'corp_id');
        
        //m_target_areas(対応可能エリアマスタ)、m_corp_categories(企業別対応カテゴリマスタ)、auto_commission_corp(自動取次)を紐づけて取得
        $this->MCorpCategory->unbindModel(array(
            'belongsTo'=>array('MCorp'),
        ));
        
        //バーチャルフィールド
        $this->MCorpCategory->virtualFields['commission_pref_cd'] = 'AutoCommissionCorp.pref';
        
        $fields = array(
            'DISTINCT MCorpCategory.corp_id',
            'MCorpCategory.category_id',
            'MCorpCategory.commission_pref_cd',
        );
        
        $joins = array(
            array (
                'fields' => '*',
                'type' => 'inner',
                'table' => 'm_target_areas',
                'alias' => 'MTargetArea',
                'conditions' => array (
                    'MCorpCategory.id = MTargetArea.corp_category_id',
                ),
            ),
            array (
                'fields' => '*',
                'type' => 'inner',
                'table' => '(SELECT corp_id, category_id, SUBSTRING(jis_cd, 1, 2) as pref FROM auto_commission_corp)',
                'alias' => 'AutoCommissionCorp',
                'conditions' => array (
                    'MCorpCategory.corp_id = AutoCommissionCorp.corp_id',
                    'MCorpCategory.category_id = AutoCommissionCorp.category_id',
                ),
            ),
        );
        
        $query = array(
            'fields' => $fields,
            'joins' => $joins,
            'conditions' => array(
                'MCorpCategory.corp_id' => $corp_id,
            ),
        );
        $area_corp_list = $this->MCorpCategory->find('all', $query);
        //$this->log($this->MCorpCategory->getDataSource()->getLog());
        $area_corp_list_cnt = count($area_corp_list);
        
        //自動取次先加盟店を取得
        //バーチャルフィールド
        $this->AutoCommissionCorp->virtualFields['pref_cd'] = 'substring(AutoCommissionCorp.jis_cd from 1 for 2)';
        
        //カラム
        $option['fields'] = array(
            'DISTINCT AutoCommissionCorp.corp_id',
            'AutoCommissionCorp.category_id',
            'AutoCommissionCorp.pref_cd',
        );
        $option['conditions'][] = array(
            'AutoCommissionCorp.corp_id' => $corp_id,
        );
        
        $auto_commission_corp_list = $this->AutoCommissionCorp->find('all', $option);
        //$this->log($this->AutoCommissionCorp->getDataSource()->getLog());
        $auto_commission_corp_list_cnt = count($auto_commission_corp_list);
        
        //それぞれの件数を比較する事で、対応可能エリア・カテゴリーと自動取次が一致しているか判定
        if($area_corp_list_cnt != $auto_commission_corp_list_cnt){
            $result = false;
        } else {
            $result = true;
        }
        return json_encode(compact('result'));
    }
    //2017-07-19 ichino ORANGE-420 ADD end
}
