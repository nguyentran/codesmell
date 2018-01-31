<?php
class CommissionCorrespond extends AppModel {

	public $validate = array(

		'commission_id' => array(
			'NotEmpty' => array(
				'rule' => 'notEmpty',
				'last' => true,
			),
		),

		'correspond_datetime' => array(
			'NotDateTime' => array(
				'rule' => 'datetime',
				'last' => true,
				'allowEmpty' => true
			),
		),

		'responders' => array(
			'CorrespondError' => array(
				'rule' => 'checkInputResponders',
			),
			'OverMaxLength20' => array(
				'rule' => array('maxLength', 20),
				'last' => true,
				'allowEmpty' => true
			),
		),
		'rits_responders' => array(
				'CorrespondError' => array(
						'rule' => 'checkInputResponders',
				),
		),
		'corresponding_contens' => array(
			'CorrespondError' => array(
				'rule' => 'checkInputContens',
			),
			'OverMaxLength1000' => array(
				'rule' => array('maxLength', 1000),
				'last' => true,
				'allowEmpty' => true
			),
		),
	);

	/**
	 * 対応内容チェック
	 *
	 * @return boolean
	 */
	public function checkInputContens(){
		if(empty($this->data['CommissionCorrespond']['corresponding_contens'])){
			if(!empty($this->data['CommissionCorrespond']['responders']) || !empty($this->data['CommissionCorrespond']['rits_responders'])){
				return false;
			}
		}
		return true;
	}

	/**
	 * 対応者チェック
	 *
	 * @return boolean
	 */
	public function checkInputResponders(){
		if(empty($this->data['CommissionCorrespond']['responders']) && empty($this->data['CommissionCorrespond']['rits_responders'])){
			if(!empty($this->data['CommissionCorrespond']['corresponding_contens'])){
				return false;
			}
		}
		return true;
	}

}