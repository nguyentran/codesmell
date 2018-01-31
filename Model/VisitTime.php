<?php
App::uses('CommonHelper', '/Lib/View/Helper/');

class VisitTime extends AppModel {

	public $validate = array(
		'visit_time' => array(
			'NotDateTime' => array(
				'rule' => 'datetime',
				'last' => true,
				'allowEmpty' => true
			),
			'PastDateTime' => array(
				'rule' => 'checkVisitTime',
			),
			//ORANGE-998 2015.11.14
			//ORANGE-998 2015.11.19 n.kai 一時対応保留
			//'DoChangeDemandType' => array(
			//	'rule' => 'checkDoChangeDemandType',
			//	'message' => '連絡期限日時が設定されているため、訪問希望日時の設定はできません。'
			//),
		),
		'visit_time_from' => array(
			'NotDateTime' => array(
				'rule' => 'datetime',
				'last' => true,
				'allowEmpty' => true
			),
			'PastDateTime' => array(
				'rule' => 'checkVisitTimeFrom',
			),
			'RequireInputTo' => array(
				'rule' => 'checkRequireTo',
				'message' => "開始日時入力時は終了日時も入力してください。"
			)
		),
		'visit_time_to' => array(
			'NotDateTime' => array(
				'rule' => 'datetime',
				'last' => true,
				'allowEmpty' => true
			),
			'PastDateTime' => array(
				'rule' => 'checkVisitTimeTo',
			),
			'PastDateTime	2' => array(
				'rule' => 'checkVisitTimeTo2',
				"message" => "開始日時より過去の日時が入力されています。"
			),
			'RequireInputFrom' => array(
				'rule' => 'checkRequireFrom',
				'message' => "終了日時入力時は開始日時も入力してください。"
			),
		),
		'visit_adjust_time' => array(
			'RequireInputAdjust' => array(
				'rule' => 'checkRequireAdjust',
				'last' => true,
				'message' => "要時間調整を選択して、期間を入力するときは訪問日時要調整時間も入力して下さい。"
			),
			'NotDateTime' => array(
				'rule' => 'datetime',
				'last' => true,
				'allowEmpty' => true
			),
			'PastDateTime' => array(
				'rule' => 'checkVisitAdjustTime',
			),
		)


	);

	/**
	 * 訪問日時のチェック
	 *
	 * 訪問日時が過去日付を入力されているとエラー
	 *
	 * @return boolean
	 */
	public function checkVisitTime(){
		if(!empty($this->data['VisitTime']['visit_time'])){
			//var_dump($this->data['VisitTime']);
			if(!empty($this->data['VisitTime']['do_auction'])){
				if(strtotime($this->data['VisitTime']['visit_time']) < strtotime(date('Y/m/d H:i'))) {
					return false;
				}
			}
		}
		return true;
	}

	/**
	 * 訪問日時fromのチェック
	 *
	 * 訪問日時が過去日付を入力されているとエラー
	 *
	 * @return boolean
	 */
	public function checkVisitTimeFrom(){
		if(!empty($this->data['VisitTime']['visit_time_from'])){
			if(!empty($this->data['VisitTime']['do_auction'])){
				if(strtotime($this->data['VisitTime']['visit_time_from']) < strtotime(date('Y/m/d H:i'))) {
					return false;
				}
			}
		}
		return true;
	}

	/**
	 * 訪問日時toのチェック
	 *
	 * 訪問日時が過去日付を入力されているとエラー
	 *
	 * @return boolean
	 */
	public function checkVisitTimeTo(){
		if(!empty($this->data['VisitTime']['visit_time_to'])){
			if(!empty($this->data['VisitTime']['do_auction'])){
				if(strtotime($this->data['VisitTime']['visit_time_to']) < strtotime(date('Y/m/d H:i'))) {
					return false;
				}
			}
		}
		return true;
	}


	/**
	 * 訪問日時toのチェック
	 *
	 * fromの日時より過去に設定されていないかのチェック
	 *
	 * @return boolean
	 */
	public function checkVisitTimeTo2(){
		if(!empty($this->data['VisitTime']['visit_time_to']) && !empty($this->data['VisitTime']['visit_time_from'])){
			if(strtotime($this->data['VisitTime']['visit_time_to']) < strtotime($this->data['VisitTime']['visit_time_from'])) {
				return false;
			}
		}
		return true;
	}

	/**
	 * 訪問日時要調整時間 のチェック
	 *
	 * 日時より過去に設定されていないかのチェック
	 *
	 * @return boolean
	 */
	public function checkVisitAdjustTime(){
		if (!empty($this->data['VisitTime']['visit_adjust_time'])) {
			// ORANGE-1252 2016.02.12 kishimoto@tobila
			// 選定方式が手動の状態(入札式以外)で過去日チェックを行わない
			if(!empty($this->data['VisitTime']['do_auction'])){
				if (strtotime($this->data['VisitTime']['visit_adjust_time']) < strtotime(date('Y/m/d H:i'))) {
					return false;
				}
			}
		}
		return true;
	}

	/**
	 * 訪問日時要調整時間 のチェック
	 *
	 * 前の時間帯をデータがないかのチェック
	 *
	 * @return boolean
	 */
	public function checkRequireAdjust(){
		if (!isset($this->data['VisitTime']['visit_time_from'])) return true;
		if (strlen($this->data['VisitTime']['visit_time_from']) == 0) return true;
		if (strlen($this->data['VisitTime']['visit_adjust_time']) == 0) return false;
		/*
		if (empty($this->data['VisitTime']['visit_time_from']) || empty($this->data['VisitTime']['visit_time_to'])) {
			if (isset($this->data['VisitTime']['visit_adjust_time']) && strlen($this->data['VisitTime']['visit_adjust_time']) != 0) {
				return false;
			}
		}
		*/
		return true;
	}

	/**
	 * 訪問時間一覧(SelectBox用)を返します
	 *
	 */
	public function getList($demand_id){

		// 検索条件の設定
		if (!empty($demand_id)) {
			$conditions = array('demand_id' => $demand_id);
		}
		else {
			$conditions = array();
		}

		$list = $this->find('all', array('conditions'=>$conditions, 'order'=>'VisitTime.visit_time asc'));

		$this->Common = new CommonHelper(new View());

		// リストの作成
		foreach ($list as $val) {

			$list_[] = array(
					"VisitTime" => array(
							"id" => $val['VisitTime']['id'],
							"visit_time" => $this->Common->dateTimeWeek($val['VisitTime']['visit_time'])
					)
			);
		}

		$data = Set::Combine(@$list_, '{n}.VisitTime.id', '{n}.VisitTime.visit_time');
		return $data;
	}

	// ORANGE-998 2015.11.14
	/**
	 * すでに連絡期限日時が登録されている場合は、連絡期限日時の削除は許可しない
	 *
	 * @return boolean
	 */
	 public function checkDoChangeDemandType() {
	 	//同時にDemandInfoもリクエストに含まれていなければチェックを行わない
	 	if (!isset($this->data['VisitTime']['demand_info_id'])) return true;
	 	//案件の初回登録時はチェックを行わない
	 	if (strlen($this->data['VisitTime']['demand_info_id']) == 0) return true;

	 	//DemandInfosとのクロスチェック
	 	App::import('Model', 'DemandInfo');
	 	$DemandModel = new DemandInfo();
	 	$demand = $DemandModel->findById($this->data['VisitTime']['demand_info_id']);
		//データ取得できない時は新規登録なのでOK
	 	if (!$demand) return true;

		if (strlen($demand['DemandInfo']['contact_desired_time']) > 0) return false;

		return true;
	}
	
	/**
	 * 開始日時が入力されている場合は、終了日時を入力するように要求します。
	 */
	public function checkRequireFrom() {
		if (!isset($this->data['VisitTime']['visit_time_to'])) return true;
		if (strlen($this->data['VisitTime']['visit_time_to']) == 0) return true;
		if (!isset($this->data['VisitTime']['visit_time_from'])) return false;
		if (strlen($this->data['VisitTime']['visit_time_from']) == 0) return false;
		
		return true;
	}
	
	/**
	 * 範囲指定が選択されていて開始日時が、終了日時を入力するように要求します。
	 */
	public function checkRequireTo() {
		if (!isset($this->data['VisitTime']['visit_time_from'])) return true;
		if (strlen($this->data['VisitTime']['visit_time_from']) == 0) return true;
		if (!isset($this->data['VisitTime']['visit_time_to'])) return false;
		if (strlen($this->data['VisitTime']['visit_time_to']) == 0) return false;		
		
		return true;
	}

}
