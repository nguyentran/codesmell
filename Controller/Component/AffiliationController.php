<?php
App::uses ( 'AppController', 'Controller' );
class AffiliationController extends AppController {
	public $name = 'Affiliation';
	public $helpers = array('Csv');
	public $components = array('Session', 'Csv');
	// 2015.02.23 企業エリアマスタついかのためMCorpTargetAreaを追加
	public $uses = array ('MCorp', 'MItem', 'MCorpSub', 'AffiliationInfo', 'AffiliationSub', 'AffiliationStat', 'MCategory', 'MGenre', 'MTargetArea', 'MPost', 'MCorpCategory', 'AffiliationCorrespond', 'MCorpTargetArea');

	public function beforeFilter() {
		$this->set ( 'default_display', false );
		$this->User = $this->Auth->user();
		// ユーザー一覧
		$this->set ("user_list", $this->MUser->dropDownUser());

		parent::beforeFilter ();
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
		} else if (isset ( $this->request->data ['csv_out'] )) { // CSV出力
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

		// 企業名
		if (! empty ( $data ['corp_name'] )) {
			$conditions ['z2h_kana(MCorp.corp_name) like'] = '%' . Util::chgSearchValue($data ['corp_name']) . '%';
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

		// 電話番号
		if (! empty ( $data ['tel1'] ) &&  ! empty ( $data ['free_text'] )) {
			$conditions = array (
					'or' => array (
							array (
									'z2h_kana(MCorp.commission_dial)' => Util::chgSearchValue($data ['tel1'])
							),
							array (
									'z2h_kana(MCorp.tel1)' => Util::chgSearchValue($data ['tel1'])
							),
							array (
									'z2h_kana(MCorp.tel2)' => Util::chgSearchValue($data ['tel1'])
							)
					),
					'and' =>array (
						'or' => array (
							array (
									'z2h_kana(MCorp.note) like' => '%'. Util::chgSearchValue($data['free_text']). '%'
							),
							array (
									'z2h_kana(AffiliationInfo.attention) like' => '%'. Util::chgSearchValue($data['free_text']). '%'
							),
						)
					)
			);
		}
		elseif (! empty ( $data ['tel1'] )) {
			$conditions = array (
					'or' => array (
							array (
									'z2h_kana(MCorp.commission_dial)' => Util::chgSearchValue($data ['tel1'])
							),
							array (
									'z2h_kana(MCorp.tel1)' => Util::chgSearchValue($data ['tel1'])
							),
							array (
									'z2h_kana(MCorp.tel2)' => Util::chgSearchValue($data ['tel1'])
							)
					)
			);
		}
		// フリーテキスト
		elseif (! empty ( $data ['free_text'] )) {
			$conditions = array (
					'or' => array (
							array (
									'z2h_kana(MCorp.note) like' => '%'. Util::chgSearchValue($data['free_text']). '%'
							),
							array (
									'z2h_kana(AffiliationInfo.attention) like' => '%'. Util::chgSearchValue($data['free_text']). '%'
							),
					)
			);
		}
		// リスト元媒体
		elseif (! empty ( $data ['listed_media'] )) {
			$conditions = array (
					'or' => array (
							array (
									'z2h_kana(MCorp.listed_media) like' => '%'. Util::chgSearchValue($data['listed_media']). '%'
							),
					)
			);
		}


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
		if ($data ['affiliation_status'] != NULL) {
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
				)
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
					$this->AffiliationInfo->begin ();
					$this->AffiliationSub->begin ();
					$this->AffiliationCorrespond->begin ();

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
					}

					if ($resultsFlg) {
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

		$this->set ( "history_list", $history_data );
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
						}

						if ($resultsFlg) {
							$this->MCorp->commit ();
							$this->MCorpSub->commit ();
							$this->Session->setFlash ( __ ( 'Updated', true ), 'default', array (
									'class' => 'message_inner'
							) );
						} else {
							$this->MCorp->rollback ();
							$this->MCorpSub->rollback ();
							$this->Session->setFlash ( __ ( 'NotEmptyAffiliationBaseItem', true ), 'default', array (
									'class' => 'error_inner'
							) );
						}
					} catch ( Exception $e ) {
						$this->MCorp->rollback ();
						$this->MCorpSub->rollback ();
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

//				$this->data = $this->request->data;

			} else {
				// 入力値をセット
				$this->request->data ['MCorp'] ['modified'] = $this->request->data ['modified_data'];
				//$this->request->data ['MCorp'] = $this->request->data ['MCorp'];
				$this->request->data ['MCorp']['id'] = $this->request->data ['corp_id'];
				$this->request->data ['MCorp']['official_corp_name'] = $this->request->data ['official_corp_name'];
				//$this->data = $this->request->data;
				$corp_data = $this->request->data;
			}

		}else{
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
		}

		// 企業情報セット
		$this->set ("corp_data", $corp_data );

		// 企業マスタ付帯情報の取得
		$this->__get_m_corp_subs_list ( $id );

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

				// 対応可能ジャンルリストの取得
				$results = $this->MCorpCategory->getMCorpCategoryGenreList($id);
				$this->set ( "results", $results );

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
								 //2015.06.30 y.fujimori ADD start
								 $this->set ( "results", $results );
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

								return true;
							} else {
								return $this->redirect ( '/affiliation/detail/' . $id );
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

					// 対応可能ジャンルリストの取得
					$results = $this->MCorpCategory->getMCorpCategoryGenreList($id);
					$this->set ( "results", $results );

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

			// 対応可能ジャンルリストの取得
			$results = $this->MCorpCategory->getMCorpCategoryGenreList($id);
			$this->set ( "results", $results );

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

		} else {  // 初期表示

			// 企業マスタ付帯情報の取得
			$this->__get_m_corp_subs_list ( $id );

			// 対応可能ジャンルリストの取得
			$results = $this->MCorpCategory->getMCorpCategoryGenreList($id);

			$this->set ( "results", $results );


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
					// 専門性必須チェック
					$this->Session->setFlash ( __ ( 'NotSelectList', true ), 'default', array (
					'class' => 'error_inner'
					) );
					$resultsFlg = false;

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
					$resultsFlg = $this->__edit_corp_category_genre( $id, $this->request->data );
					if ($resultsFlg) {
						$this->MCorpCategory->commit ();
						// 2015.02.24 初回のみ企業エリアマスタからエリアをセット
						$this->MTargetArea->begin();
						$resultsFlg = $this->__edit_target_area_genre( $id, $this->request->data );
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
	public function corptargetarea($id = null, $init_pref = null) {

		if ($id == null) {
			throw new Exception();
		}

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
				var_dump($e);
			}
			// エリアのリストを取得
			/*
			$conditions['MPost.address1'] = $this->request->data['address1_text'];
			$list = $this->__get_target_area_list($id , $conditions);

			$this->data = $this->request->data;
			$this->set("list" , $list);
			*/

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

		// 加盟店選択済みジャンルリスト
		$select_genre_list = $this->MCorpCategory->getCorpSelectGenreList($id);
		// [MCorpCategory][id]の取得
		$genre_list = array();
		foreach ($select_genre_list as $key => $val) {
			$obj = array();
			$obj['genre_name'] = $val['MGenre']['genre_name'];
			$row_data = $this->MCorpCategory->getMCorpCategoryID($id, $val['MGenre']['id']);
			$obj['id'] = $row_data['MCorpCategory']['id'];
			$genre_list[] = $obj;
		}

		$this->set("genre_list" , $genre_list);

		$this->set("init_pref" , $init_pref);
		// 最終更新日の取得
		$last_modified = $this->MCorpTargetArea->getCorpTargetAreaLastModified($id);
		$this->set("last_modified" , $last_modified);
		$this->set("id" , $id);
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

		$resultsFlg = true;

		if (isset($this->request->data['regist'])){

			// データを削除・登録処理
			try {
				$this->MTargetArea->begin();
				// 企業別対応カテゴリマスタの登録
				if (!empty($this->request->data['corp_id']) && !empty($this->request->data['genre_id'])) {
					// 同一カテゴリを全て更新
					$id_list = $this->MCorpCategory->getMCorpCategoryIDList($this->request->data['corp_id'], $this->request->data['genre_id']);
					foreach ($id_list as $key => $val) {
						$resultsFlg = $this->__edit_target_area3($val['MCorpCategory']['id'] , $this->request->data);
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
			// エリアのリストを取得
			/*
			$conditions['MPost.address1'] = $this->request->data['address1_text'];
			$list = $this->__get_target_area_list($id , $conditions);

			$this->data = $this->request->data;
			$tis->set("list" , $list);
			*/

		} else if (isset($this->request->data['all_regist'])){

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
		$data = $this->MCorpCategory->getGenreName($id);
		$this->set("data" , $data);
		// 最終更新日の取得
		$last_modified = $this->MTargetArea->getTargetAreaLastModified($id);
		$this->set("last_modified" , $last_modified);
		// 「MCorpCategory」のIDをセット
		$this->set("id" , $id);

	}
	// 2015.08.27 s.harada ADD end 画面デザイン変更対応

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
		$results = $this->MCorp->find ( 'first', array (
				'fields' => '*, AffiliationInfo.id, AffiliationInfo.employees, AffiliationInfo.max_commission, AffiliationInfo.collection_method, AffiliationInfo.collection_method_others, AffiliationInfo.liability_insurance, AffiliationInfo.reg_follow_date1, AffiliationInfo.reg_follow_date2, AffiliationInfo.reg_follow_date3, AffiliationInfo.waste_collect_oath, AffiliationInfo.transfer_name, AffiliationInfo.claim_history, AffiliationInfo.claim_count, AffiliationInfo.commission_count, AffiliationInfo.weekly_commission_count, AffiliationInfo.orders_count, AffiliationInfo.orders_rate, AffiliationInfo.construction_cost, AffiliationInfo.fee, AffiliationInfo.bill_price, AffiliationInfo.payment_price, AffiliationInfo.balance, AffiliationInfo.construction_unit_price, AffiliationInfo.commission_unit_price, AffiliationInfo.reg_info, AffiliationInfo.reg_pdf_path, AffiliationInfo.attention, AffiliationStat.commission_count_category, AffiliationStat.orders_count_category, AffiliationStat.commission_unit_price_category',
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
						)
				),
				'conditions' => array (
						'MCorp.id' => $id
				)
		) );
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

		$save_data = array (
				'MCorp' => $data ['MCorp']
		);

		// 登録
		if ($this->MCorp->save ( $save_data )) {
			$last_id = $this->MCorp->getLastInsertID ();
			return true;
		} else {
			return false;
		}
	}

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

        $count = 0;
        foreach ( $data ['MCorpCategory'] as $v ) {

            $v ['corp_id'] = $id;
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
		$fields_affiliation_info = 'AffiliationInfo.id, AffiliationInfo.employees, AffiliationInfo.max_commission, AffiliationInfo.collection_method, AffiliationInfo.collection_method_others, AffiliationInfo.liability_insurance, AffiliationInfo.reg_follow_date1, AffiliationInfo.reg_follow_date2, AffiliationInfo.reg_follow_date3, AffiliationInfo.waste_collect_oath, AffiliationInfo.transfer_name, AffiliationInfo.claim_count, AffiliationInfo.claim_history, AffiliationInfo.commission_count, AffiliationInfo.weekly_commission_count, AffiliationInfo.orders_count, AffiliationInfo.orders_rate, AffiliationInfo.construction_cost, AffiliationInfo.fee, AffiliationInfo.bill_price, AffiliationInfo.payment_price, AffiliationInfo.balance, AffiliationInfo.construction_unit_price, AffiliationInfo.commission_unit_price, AffiliationInfo.reg_info, AffiliationInfo.reg_pdf_path, AffiliationInfo.attention';
		// 休業日
		$fields_holiday = '(SELECT ARRAY_TO_STRING(ARRAY( SELECT item_name FROM m_items INNER JOIN m_corp_subs ON m_corp_subs.item_category = m_items.item_category AND m_corp_subs.item_id = m_items.item_id WHERE m_corp_subs.item_category = \''.__('holiday' , true).'\' AND m_corp_subs.corp_id = "MCorp"."id" ORDER BY m_items.sort_order ASC ),\'｜\') as "MCorp__holiday" )';
		// 開拓時の反応
		$fields_development_response = '(SELECT ARRAY_TO_STRING(ARRAY( SELECT item_name FROM m_items INNER JOIN m_corp_subs ON m_corp_subs.item_category = m_items.item_category AND m_corp_subs.item_id = m_items.item_id WHERE m_corp_subs.item_category = \''.__('development_reaction' , true).'\' AND m_corp_subs.corp_id = "MCorp"."id" ORDER BY m_items.sort_order ASC ),\'｜\') as "MCorp__development_response" )';
		// 取次STOPカテゴリ
		$fields_stop_category_name = '(SELECT ARRAY_TO_STRING(ARRAY( SELECT category_name FROM m_categories INNER JOIN affiliation_subs ON affiliation_subs.item_id = m_categories.id AND affiliation_subs.item_category = \''.__('stop_category' , true).'\' AND affiliation_subs.affiliation_id = "AffiliationInfo"."id" ),\'｜\') as "AffiliationInfo__stop_category_name" )';
		// カテゴリ
		$fields_category_name = '(SELECT ARRAY_TO_STRING(ARRAY( SELECT category_name FROM m_categories INNER JOIN m_corp_categories ON m_corp_categories.category_id = m_categories.id AND m_corp_categories.corp_id = "MCorp"."id" ),\'｜\') as "MCorp__category_name" )';

		$results = $this->MCorp->find ( 'all', array (
				'conditions' => $conditions,
				'fields' => '* , '.$fields_affiliation_info.', '.$fields_category_name.', '.$fields_holiday.', '.$fields_development_response.', '.$fields_stop_category_name ,
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
	private function __edit_corp_category_genre($id, $data) {
		$resultsFlg = false; // 更新結果フラグ

		$count = 0;

		// 2015.08.27 s.harada ADD start 画面デザイン変更対応
		$del_mcc_id_array = array();
		$chk_mcc_id = $data['chk_mcc_id'];
		if (strlen($chk_mcc_id) > 0) {
			$del_mcc_id_array = split('-', $chk_mcc_id);
		}
		// 2015.08.27 s.harada ADD end 画面デザイン変更対応

		foreach ( $data ['MCorpCategory'] as $v ) {

			$v ['corp_id'] = $id;

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
				// 新規ジャンル登録の場合は、デフォルト手数料とデフォルト手数料単位を受注手数料と受注手数料単位に書き込む
				$v['order_fee']      = $v['default_fee'];
				$v['order_fee_unit'] = $v['default_fee_unit'];
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
	private function __edit_target_area_genre($id, $data) {
		$resultsFlg = false; // 更新結果フラグ

		// チェック済みからチェックをはずしたカテゴリのエリアの削除処理開始
		$del_mcc_id_array = array();
		$chk_mcc_id = $data['chk_mcc_id'];
		if (strlen($chk_mcc_id) > 0) {
			$del_mcc_id_array = split('-', $chk_mcc_id);
		}

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

		// チェック済みからチェックをはずしたカテゴリのエリアを全削除
		foreach ($del_mcc_id_array as $key => $value) {
			// エリアを全削除
			$result = $this->MTargetArea->deleteAll(array('corp_category_id = ' . $value));
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

	// 2015.08.27 s.harada ADD end 画面デザイン変更対応

}
