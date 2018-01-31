<?php
class PublicHoliday extends AppModel {

	public $validate = array(

		'holiday_date' => array(
			'NotDate' => array(
				'rule' => 'date',
				'last' => true,
				'allowEmpty' => true
			),
		),
	);


	public $csvFormat = array(
	);

	/**
	 * 祝日かどうかチェックを行う
	 * @param unknown $date
	 */
	public function checkHoliday($date) {

		$results = $this->find('all');

		foreach ($results as $row) {
			if ($row['PublicHoliday']['holiday_date'] == $date) {
				return true;
			}
		}

		return false;
	}
}