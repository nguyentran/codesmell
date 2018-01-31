<?php
class Refusal extends AppModel {

	public $validate = array(

// 		// murata.s ORANGE-539 CHG(S)
// 		'checkbox' => array(
// 				'NotCheckReason' => array(
// 				'rule' => 'checkRefusalReason',
// 			),
// 		),
// 		// murata.s ORANGE-539 CHG(E)

		'corresponds_time1' => array(
			'NotEmpty' => array(
				'rule' => 'checkCorrespondsTime',
			),
			'NotDateTime' => array(
				'rule' => 'datetime',
				'last' => true,
				'allowEmpty' => true
			),

		),

		'corresponds_time2' => array(
			'NotDateTime' => array(
				'rule' => 'datetime',
				'last' => true,
				'allowEmpty' => true
			),
		),

		'corresponds_time3' => array(
			'NotDateTime' => array(
				'rule' => 'datetime',
				'last' => true,
				'allowEmpty' => true
			),
		),

		'cost_from' => array(
			'NotEmpty' => array(
				'rule' => 'checkCostFrom',
			),
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'last' => true,
				'allowEmpty' => true
			),

		),

		'cost_to' => array(
			'NotEmpty' => array(
				'rule' => 'checkCostTo',
			),
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'last' => true,
				'allowEmpty' => true
			),
		),

		'other_contens' => array(
			'NotEmpty' => array(
				'rule' => 'checkOtherContens',
			),
			'OverMaxLength1000' => array(
				'rule' => array('maxLength', 1000),
				'last' => true,
				'allowEmpty' => true
			),
		),

		// murata.s ORANGE-539 ADD(S)
		'estimable_time_from' => array(
// 			'NotEmpty' => array(
// 				'rule' => 'checkEstimableTime',
// 			),
			'NotDateTime' => array(
				'rule' => 'datetime',
				'last' => true,
				'allowEmpty' => true
			),
		),

		'contactable_time_from' => array(
// 			'NotEmpty' => array(
// 				'rule' => 'checkContactableTime',
// 			),
			'NotDateTime' => array(
				'rule' => 'datetime',
				'last' => true,
				'allowEmpty' => true,
			),
		),
		// murata.s ORANGE-539 ADD(E)

	);

	// murata.s ORANGE=539 ADD(S)
	public function checkRefusalReason(){
		if(empty($this->data['Refusal']['checkbox']) && empty($this->data['Refusal']['not_available_flg'])){
			return false;
		}
		return true;
	}

	public function checkEstimableTime(){
		if($this->data['Refusal']['checkbox'] == 4){
			if(empty($this->data['Refusal']['estimable_time_from'])){
				return false;
			}
		}
		return true;
	}

	public function checkContactableTime(){
		if($this->data['Refusal']['checkbox'] == 5){
			if(empty($this->data['Refusal']['contactable_time_from'])){
				return false;
			}
		}
		return true;
	}
	// murata.s ORANGe-539 ADD(E)

	/**
	 *
	 *
	 * @return boolean
	 */
	public function checkCorrespondsTime(){
		if($this->data['Refusal']['checkbox'] == 1){
			if(empty($this->data['Refusal']['corresponds_time1']) && empty($this->data['Refusal']['corresponds_time2']) && empty($this->data['Refusal']['corresponds_time3'])){
				return false;
			}
		}
		return true;
	}

	/**
	 *
	 * @return boolean
	 */
	public function checkCostFrom(){
		if($this->data['Refusal']['checkbox'] == 2){
			if(empty($this->data['Refusal']['cost_from'])){
				return false;
			}
		}
		return true;
	}

	/**
	 *
	 * @return boolean
	 */
	public function checkCostTo(){
		if($this->data['Refusal']['checkbox'] == 2){
			if(empty($this->data['Refusal']['cost_to'])){
				return false;
			}
		}
		return true;
	}

	/**
	 *
	 * @return boolean
	 */
	public function checkOtherContens(){
		if($this->data['Refusal']['checkbox'] == 3){
			if(empty($this->data['Refusal']['other_contens'])){
				return false;
			}
		}
		return true;
	}

	public $csvFormat = array(
	);
}