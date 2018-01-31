<?php
class MGeneralSearch extends AppModel
{
	public $hasMany = array(
		'G_S_Item' => array(
			'className' => 'GeneralSearchItem',
			'foreignKey' => 'general_search_id',
			'dependent' => true
		),
		'G_S_Condition' => array(
			'className' => 'GeneralSearchCondition',
			'foreignKey' => 'general_search_id',
			'dependent' => true			
		)
	);

	public $validate = array(
		'definition_name' => array(
			'rule' => 'notEmpty'
		),
		'auth_accounting' => array(
			'rule' => array('isItChosenOne'),
			'message' => '必ず公開範囲を一つは選択して下さい'	
		)
	);

	function isItChosenOne() {
		if (
			$this->data['MGeneralSearch']['auth_popular'] == 1 ||
			$this->data['MGeneralSearch']['auth_admin'] == 1 ||
			$this->data['MGeneralSearch']['auth_accounting_admin'] == 1 ||
			$this->data['MGeneralSearch']['auth_accounting'] ==1
		) {
			return true;
		}

		return false;
	}

}