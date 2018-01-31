<?php
class CommissionVisitSupports extends AppModel {

	public $validate = array(

		'commission_id' => array(
			'NotEmpty' => array(
				'rule' => 'notEmpty',
				'last' => true,
			),
		),


	);



}