<?php
class GeneralSearchItem extends AppModel
{
	public $belongsTo = array(
		'MGeneralSearch'=>array(
			'className' => 'MGeneralSearch',
			'foreignKey'=> 'general_search_id',
			'type'=>'inner',
			'fields' => array('GeneralSearch.*'),
		),
	);
}
