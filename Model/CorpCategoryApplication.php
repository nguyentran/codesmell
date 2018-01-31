<?php
class CorpCategoryApplication extends AppModel{

	public $validate = array(
			'order_fee' => array(
					'NoNumeric' => array(
							'rule' => array('numeric'),
							'allowEmpty' => true
					)
			),
			'introduce_fee' => array(
					'NoNumeric' => array(
							'rule' => array('numeric'),
							'allowEmpty' => true
					)
			),
			'note' => array(
					'OverMaxLength1000' => array(
							'rule' => array('maxLength', 1000),
							'allowEmpty' => true
					)
			),
	);

	public function saveCorpCategoryTemp($corp_id = null, $approvals = null, $approval_status = -1){

		if(empty($approvals))return false;

		//一時データモデルインポート
		App::import('Model', 'CorpAgreementTempLink');
		$CorpAgreementTempLink = new CorpAgreementTempLink();

		$options = array(
						'conditions' => array(
								'CorpAgreementTempLink.corp_id' => $corp_id,
						),
						'order' => 'CorpAgreementTempLink.id desc',
					);

		//企業カテゴリデータをアソシエーション
		$CorpAgreementTempLink->bindModel(
				array(
						'hasMany' => array('MCorpCategoriesTemp' => array('foreignKey' => 'temp_id')),
						'belongsTo' => array('CorpAgreement' => array('foreignKey' => 'corp_agreement_id')),
				)
		);

		//最新の企業カテゴリデータを取得
		$corp_category = $CorpAgreementTempLink->find('first', $options);

		//一時情報がなければエラー
		if($corp_category){
			$agreement_flag = false;
			//契約IDが存在する場合、契約フラグをTrue
			if(!empty($corp_category['CorpAgreementTempLink']['corp_agreement_id'])
					&& in_array($corp_category['CorpAgreement']['status'], array('Complete', 'Application'))){
				$agreement_flag = true;
			}

			//トランザクション開始
			$datasource = $this->getDataSource();
			$datasource->begin();

			$temp_id = null;

			//契約フラグがTrue、かつ申請承認なら一時データを新規作成
			if($agreement_flag && $approval_status == 1){
				//一時データの新規作成
				$CorpAgreementTempLink->create();

				//加盟店IDを保存データに追加
				$save_agreement_link = array();
				$save_agreement_link['CorpAgreementTempLink']['corp_id'] = $corp_id;

				//一時データの保存
				if(!$CorpAgreementTempLink->save($save_agreement_link)){
					//保存に失敗したらロールバック
					$datasource->rollback();

					return false;
				}else{
					//新規追加IDの取得
					$temp_id = $CorpAgreementTempLink->getLastInsertID();
				}
			}

			//初期宣言
			$save_corp_category = array();
			$save_approval = array();
			$i = 0;
			$n = 0;

			//ログインユーザを取得
			$user = $this->__getLoginUser();

			foreach($corp_category['MCorpCategoriesTemp'] as $m_corp_category){

				$up_flg = false;

				//pr($m_corp_category);
				foreach($approvals as $approval){
					//pr($approval);
					if($m_corp_category['corp_id'] == $approval['CorpCategoryApplication']['corp_id'] && $m_corp_category['category_id'] == $approval['CorpCategoryApplication']['category_id']){
						//更新フラグ
						$up_flg = true;

						//申請ユーザと承認ユーザが同一
						//通常環境では選択できないため、かなりのイレギュラー時
						if($user['user_id'] == $approval['Approval']['application_user_id']){
							$this->log('カテゴリ手数料申請 ユーザ重複エラー: approvals_id: '.$approval['Approval']['id'].' user_id: '.$user['user_id']);
							return false;
						}

						//pr($m_corp_category);
						//元データをコピーして、申請データを上書き
						$save_corp_category['MCorpCategoriesTemp'][$i] = $m_corp_category;
						$save_corp_category['MCorpCategoriesTemp'][$i]['order_fee'] = $approval['CorpCategoryApplication']['order_fee'];
						$save_corp_category['MCorpCategoriesTemp'][$i]['order_fee_unit'] = $approval['CorpCategoryApplication']['order_fee_unit'];
						$save_corp_category['MCorpCategoriesTemp'][$i]['introduce_fee'] = $approval['CorpCategoryApplication']['introduce_fee'];
						$save_corp_category['MCorpCategoriesTemp'][$i]['note'] = $approval['CorpCategoryApplication']['note'];
						$save_corp_category['MCorpCategoriesTemp'][$i]['corp_commission_type'] = $approval['CorpCategoryApplication']['corp_commission_type'];
						$save_corp_category['MCorpCategoriesTemp'][$i]['modified'] = null;
						$save_corp_category['MCorpCategoriesTemp'][$i]['modified_user_id'] = null;

						//一時IDがあれば保存データを初期化
						if(!empty($temp_id)){
							$save_corp_category['MCorpCategoriesTemp'][$i] = $m_corp_category;
							$save_corp_category['MCorpCategoriesTemp'][$i]['temp_id'] = $temp_id;
							$save_corp_category['MCorpCategoriesTemp'][$i]['id'] = null;
							$save_corp_category['MCorpCategoriesTemp'][$i]['created'] = null;
							$save_corp_category['MCorpCategoriesTemp'][$i]['created_user_id'] = null;
							$save_corp_category['MCorpCategoriesTemp'][$i]['create_date'] = null;
							$save_corp_category['MCorpCategoriesTemp'][$i]['create_user_id'] = null;
							$save_corp_category['MCorpCategoriesTemp'][$i]['update_date'] = null;
							$save_corp_category['MCorpCategoriesTemp'][$i]['update_user_id'] = null;
							$save_corp_category['MCorpCategoriesTemp'][$i]['delete_date'] = null;
							$save_corp_category['MCorpCategoriesTemp'][$i]['delete_flag'] = false;
						}

						//承認・却下データの保存データ作成
						$save_approval['Approval'][$n]['id'] = $approval['Approval']['id'];
						$save_approval['Approval'][$n]['application_section'] = $approval['Approval']['application_section'];
						$save_approval['Approval'][$n]['approval_user_id'] = $user['user_id'];
						$save_approval['Approval'][$n]['approval_datetime'] = date('Y-m-d h:i:s');
						$save_approval['Approval'][$n]['status'] = $approval_status;

						$i++;
						$n++;
					}
				}

				//承認申請　以外のデータを初期化して保存データ作成
				if(!empty($temp_id) && !$up_flg){
					$save_corp_category['MCorpCategoriesTemp'][$i] = $m_corp_category;
					$save_corp_category['MCorpCategoriesTemp'][$i]['temp_id'] = $temp_id;
					$save_corp_category['MCorpCategoriesTemp'][$i]['id'] = null;
					$save_corp_category['MCorpCategoriesTemp'][$i]['created'] = null;
					$save_corp_category['MCorpCategoriesTemp'][$i]['modified'] = null;
					$save_corp_category['MCorpCategoriesTemp'][$i]['created_user_id'] = null;
					$save_corp_category['MCorpCategoriesTemp'][$i]['modified_user_id'] = null;
					$save_corp_category['MCorpCategoriesTemp'][$i]['create_date'] = null;
					$save_corp_category['MCorpCategoriesTemp'][$i]['create_user_id'] = null;
					$save_corp_category['MCorpCategoriesTemp'][$i]['update_date'] = null;
					$save_corp_category['MCorpCategoriesTemp'][$i]['update_user_id'] = null;
					$save_corp_category['MCorpCategoriesTemp'][$i]['delete_date'] = null;
					$save_corp_category['MCorpCategoriesTemp'][$i]['delete_flag'] = false;

					$i++;
				}

			}

			$result_flg = false;

			//承認・却下データがあれば
			if(count($save_approval) > 0){
				//pr($save_approval);
				App::import('Model', 'Approval');
				$Approval = new Approval();

				//申請承認
				if($Approval->saveAll($save_approval['Approval'])){
					$result_flg = true;
				}
			}

			//承認・却下保存成功 & 承認
			//企業カテゴリ情報の更新
			if($result_flg && $approval_status == 1){
				App::import('Model', 'MCorpCategoriesTemp');
				$MCorpCategoriesTemp = new MCorpCategoriesTemp();
				//申請承認
				if(!$MCorpCategoriesTemp->saveAll($save_corp_category['MCorpCategoriesTemp'])){
					$result_flg = false;
				}

			}

			//保存フラグがTrue　
			//データコミット
			if($result_flg){

				$datasource->commit();

			}else{

				$datasource->rollback();
			}

			return true;
		}else{

			return false;
		}
	}

}
