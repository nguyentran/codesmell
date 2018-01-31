<?php
class MTime extends AppModel {

	public $validate = array(
		'item_hour_date' => array(
			'NotEmpty' => array(
				'rule' => 'checkItemHourDate',
			),
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'last' => true,
				'allowEmpty' => true
			),
		),

		'item_minute_date' => array(
			'NotEmpty' => array(
				'rule' => 'checkItemMinuteDate',
			),
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'last' => true,
				'allowEmpty' => true
			),
		),
	);

	/**
	 * 時のエラーチェック
	 *
	 * @return boolean
	 */
	public function checkItemHourDate(){
		if(empty($this->data['MTime']['item_hour_date'])){
			// 業者向け事前周知メール送信日時の設定でない場合のみ
			if($this->data['MTime']['item_category'] != 'send_mail'){
				if((isset($this->data['MTime']['item_type']) && $this->data['MTime']['item_type'] == 0 && empty($this->data['MTime']['item_minute_date'])) || (isset($this->data['MTime']['item_type']) && $this->data['MTime']['item_type'] == 1)){

					return false;
				}
			}
		}
		return true;
	}

	/**
	 * 分のエラーチェック
	 *
	 * @return boolean
	 */
	public function checkItemMinuteDate(){
		if(empty($this->data['MTime']['item_minute_date'])){
			// 業者向け事前周知メール送信日時の設定でない場合のみ
			if($this->data['MTime']['item_category'] != 'send_mail'){
				if((isset($this->data['MTime']['item_type']) && $this->data['MTime']['item_type'] == 0 && empty($this->data['MTime']['item_hour_date'])) || (isset($this->data['MTime']['item_type']) && $this->data['MTime']['item_type'] == 2)){
					return false;
				}
			}
		}
		return true;
	}

	public $csvFormat = array(
	);


	public function getFollowTimeWithData($create_date, $visit_time, $follow_data = array()) {

		$spare_time = null;
		$before_visit = null;
		$follow_from = null;
		$follow_to = null;
		$before_day = null;

		foreach($follow_data as $row) {

			if ($row['MTime']['item_detail'] == 'spare_time') {
				$spare_time = $row['MTime']['item_hour_date'];
			}

			if ($row['MTime']['item_detail'] == 'before_visit') {
				$before_visit = $row['MTime']['item_hour_date'];
			}

			if ($row['MTime']['item_detail'] == 'follow_from') {
				$follow_from =  $row['MTime']['item_hour_date'];
			}

			if ($row['MTime']['item_detail'] == 'follow_to') {
				$follow_to =  $row['MTime']['item_hour_date'];
			}

			if ($row['MTime']['item_detail'] == 'before_day_first_half') {
				$before_day_first_half =  $row['MTime']['item_hour_date'];
			}

			if ($row['MTime']['item_detail'] == 'before_day') {
				$before_day =  $row['MTime']['item_hour_date'];
			}

		}

		$c = strtotime($create_date);
		$v = strtotime($visit_time);
		$diff = abs($v - $c);

		// 時間に直す
		$hours = floor($diff / 60 / 60);

		if ($spare_time > $hours) {
			return null;
		}

		// 訪問時刻の12時間前の日時
		$follow_date = date('Y-m-d H:i', strtotime($visit_time. " - ". $before_visit ." hour"));

		// フォロー日時(除外日時)
		$follow_start_date = Util::dateFormat($follow_date). ' '. $follow_from. ':00';
		$follow_end_date = Util::dateFormat($follow_date). ' '. $follow_to. ':00';


		// 日付が変わるまでの範囲でチェック
		$check_date = Util::dateFormat($follow_date). ' '. '23:59';
		if (strtotime($follow_start_date) <= strtotime($follow_date) && strtotime($follow_date) <= strtotime($check_date)) {
			// 2015.10.07 n.kai MOD start フォロー日時が除外開始より大きく、フォロー日時が23:59より小さい場合、訪問日前日に設定する
			//$follow_date = date('Y-m-d', strtotime($follow_date. " - 1 day"));
			//$follow_date = $follow_date. ' '. $before_day. ':00';
			$follow_date = date('Y-m-d', strtotime($visit_time. " - 1 day"));
			$follow_date = $follow_date. ' '. $before_day_first_half. ':00';
			// 2015.10.07 n.kai MOD end

			return $follow_date;
		}

		// Fromの日付が小さい場合は、-1日
		if (strtotime($follow_start_date) > strtotime($follow_end_date)) {
			$follow_start_date = date('Y-m-d H:i', strtotime($follow_start_date. " - 1 day"));
		}

		// 設定範囲内の場合、前日の設定時間にて設定
		if (strtotime($follow_start_date) <= strtotime($follow_date) && strtotime($follow_date) <= strtotime($follow_end_date)) {
			$follow_date = date('Y-m-d', strtotime($follow_date. " - 1 day"));
			$follow_date = $follow_date. ' '. $before_day. ':00';
		}

		return $follow_date;


	}

	public function getFollowTime($create_date, $visit_time) {

		$results = $this->find('all', array('conditions' => array('item_category' => 'follow_tel')));

		return $this->getFollowTime($create_date, $visit_time, $results);

	}
}