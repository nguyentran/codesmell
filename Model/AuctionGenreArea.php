<?php
class AuctionGenreArea extends AppModel {

	public $validate = array(
		'genre_id' => array(
			'NotEmpty' => array(
				'rule' => 'notEmpty',
				'last' => true
			),
		),

		'prefecture_cd' => array(
			'NotEmpty' => array(
				'rule' => 'notEmpty',
				'last' => true
			),
		),

		'exclusion_pattern' => array(
			'NotEmpty' => array(
				'rule' => 'notEmpty',
				'last' => true
			),
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'last' => true,
			),
		),

		'limit_asap' => array(
			'NotEmpty' => array(
				'rule' => 'notEmpty',
				'last' => true
			),
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'last' => true,
			),
		),

		'limit_immediately' => array(
			'NotEmpty' => array(
				'rule' => 'notEmpty',
				'last' => true
			),
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'last' => true,
			),
		),

		'asap' => array(
			'NotEmpty' => array(
				'rule' => 'notEmpty',
				'last' => true
			),
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'last' => true,
			),
		),

		'immediately' => array(
			'NotEmpty' => array(
				'rule' => 'notEmpty',
				'last' => true
			),
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'last' => true,
			),
		),

		'normal1' => array(
			'NotEmpty' => array(
				'rule' => 'notEmpty',
				'last' => true
			),
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'last' => true,
			),
		),

		'normal2' => array(
			'NotEmpty' => array(
				'rule' => 'notEmpty',
				'last' => true
			),
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'last' => true,
			),
		),

		'open_rank_a' => array(
			'NotEmpty' => array(
				'rule' => 'notEmpty',
				'last' => true
			),
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'last' => true,
			),
			'Range0to100' => array(
				'rule' => array('comparison', '<=', 100),
				'last' => true,
			)
		),

		'open_rank_b' => array(
			'NotEmpty' => array(
				'rule' => 'notEmpty',
				'last' => true
			),
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'last' => true,
			),
			'Range0to100' => array(
				'rule' => array('comparison', '<=', 100),
				'last' => true,
			)
		),

		'open_rank_c' => array(
			'NotEmpty' => array(
				'rule' => 'notEmpty',
				'last' => true
			),
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'last' => true,
			),
			'Range0to100' => array(
					'rule' => array('comparison', '<=', 100),
					'last' => true,
			)
		),

		'open_rank_d' => array(
			'NotEmpty' => array(
				'rule' => 'notEmpty',
				'last' => true
			),
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'last' => true,
			),
			'Range0to100' => array(
				'rule' => array('comparison', '<=', 100),
				'last' => true,
			)
		),

		'open_rank_z' => array(
			'NotEmpty' => array(
				'rule' => 'notEmpty',
				'last' => true
			),
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'last' => true,
			),
			'Range0to100' => array(
				'rule' => array('comparison', '<=', 100),
				'last' => true,
			)
		),

		'tel_hope_a' => array(

			'NotEmpty' => array(
				'rule' => 'notEmpty',
				'last' => true
			),
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'last' => true,
			),
			'Range0to100' => array(
				'rule' => array('comparison', '<=', 100),
				'last' => true,
			)
		),

		'tel_hope_b' => array(
			'NotEmpty' => array(
				'rule' => 'notEmpty',
				'last' => true
			),
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'last' => true,
			),
			'Range0to100' => array(
				'rule' => array('comparison', '<=', 100),
				'last' => true,
			)
		),

		'tel_hope_c' => array(
			'NotEmpty' => array(
				'rule' => 'notEmpty',
				'last' => true
			),
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'last' => true,
			),
			'Range0to100' => array(
				'rule' => array('comparison', '<=', 100),
				'last' => true,
			)
		),

		'tel_hope_d' => array(
			'NotEmpty' => array(
				'rule' => 'notEmpty',
				'last' => true
			),
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'last' => true,
			),
			'Range0to100' => array(
				'rule' => array('comparison', '<=', 100),
				'last' => true,
			)
		),

		'tel_hope_z' => array(
			'NotEmpty' => array(
				'rule' => 'notEmpty',
				'last' => true
			),
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'last' => true,
			),
			'Range0to100' => array(
				'rule' => array('comparison', '<=', 100),
				'last' => true,
			)
		),

// 2016.08.18 murata.s ORANGE-5 ADD(S)
		'normal3' => array(
			// 2016.12.19 murata.s CHG(S)
			'NotEmpty' => array(
				'rule' => 'notEmpty',
				'last' => true
			),
			// 2016.12.19 murata.s CHG(E)
			'NoNumeric' => array(
				'rule' => array('numeric'),
				'last' => true
			)
		),
// 2016.08.18 murata.s ORANGE-5 ADD(E)

	);

	public $csvFormat = array(
	);
}