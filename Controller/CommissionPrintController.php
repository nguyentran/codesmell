<?php
App::uses('AppController', 'Controller');
App::import( 'Vendor', 'PHPWord', array('file'=>'PHPWord.php') );

class CommissionPrintController extends AppController {

	public $name = 'CommissionPrint';
	public $helpers = array();
	public $components = array('Session', 'Print');
	public $uses = array('CommissionInfo','DemandInquiryAnswer', 'MSite', 'MCommissionType');

	public function beforeFilter(){

		$this->layout = 'subwin';

		parent::beforeFilter();
//		// 2015.04.29 h.hara ADD start
//		$this->CommissionInfo->unbindModel(
//			array(
//				'belongsTo' => array('DemandInfo')
//			),false
//		);
//		// 2015.04.29 h.hara ADD end
	}


	// 初期表示（一覧）
	public function index($demand_id = null) {

		if ($demand_id == null) {
			throw new Exception();
		}

		$params = array();
		$conditions = array();

		$conditions['CommissionInfo.demand_id'] = $demand_id;

		$params = array(
					'conditions' => $conditions,
				);

		$results = $this->CommissionInfo->find('all', $params);

		$this->set('results', $results);

	}

	// 取次票印刷
	public function print_commission($id = null) {

		if ($id == null) {
			throw new Exception();
		}

		// レイアウトなし
		$this->layout = false;

// 		$conditions = array('CommissionInfo.id' => $id);

// 		$joins = array(
// 					array(
// 						'table' => 'demand_infos',
// 						'alias' => "DemandInfo",
// 						'type' => 'inner',
// 						'conditions' => array('CommissionInfo.demand_id = DemandInfo.id')
// 					),
// 					array(
// 						'table' => 'm_sites',
// 						'alias' => "MSite",
// 						'type' => 'inner',
// 						'conditions' => array('DemandInfo.site_id = MSite.id')
// 					),
// 					array(
// 						'table' => 'm_users',
// 						'alias' => "MUser",
// 						'type' => 'left',
// 						'conditions' => array('DemandInfo.receptionist = MUser.id')
// 					)
// 		);

// 		// データ取得
// 		$params = array(
// 				'fields' => '*, MCorp.*,
// 							DemandInfo.id, DemandInfo.customer_name, DemandInfo.customer_name, DemandInfo.address1, DemandInfo.address2,  DemandInfo.address3, DemandInfo.address4,
// 							 DemandInfo.building, DemandInfo.room, DemandInfo.tel1, DemandInfo.tel2, DemandInfo.contents, DemandInfo.contact_desired_time, DemandInfo.receptionist, MSite.site_name, MSite.note, MUser.user_name',
// 				'conditions' => $conditions,
// 				'joins' => $joins
// 		);

// 		$data = $this->CommissionInfo->find('first', $params);

// 		$InquiryData = array();
// 		if(!empty($data['DemandInfo']['id'])){
// 			$conditions = array ('DemandInquiryAnswer.demand_id' => $data ['DemandInfo'] ['id']);
// 			$InquiryData = $this->DemandInquiryAnswer->find ( 'all', array (
// 					'fields' => "* ,MInquiry.inquiry_name",
// 					'joins' => array (
// 							array (
// 									'fields' => '*',
// 									'type' => 'inner',
// 									"table" => "m_inquiries",
// 									"alias" => "MInquiry",
// 									"conditions" => array (
// 											"MInquiry.id = DemandInquiryAnswer.inquiry_id"
// 									)
// 							)
// 					),
// 					'conditions' => $conditions,
// 					'order' => array (
// 							'DemandInquiryAnswer.id' => 'ASC'
// 					)
// 			) );
// 		}
// 		$inquiry_data = '';
// 		if(!empty($InquiryData)){
// 			$count = count($InquiryData)-1;
// 			foreach ($InquiryData as $key => $val){
// 				$inquiry_data .= $val['MInquiry']['inquiry_name'].'：'.$val['DemandInquiryAnswer']['answer_note'];
// 				if($count != $key){
// 					$inquiry_data .= ', ';
// 				}
// 			}
// 		}

// 		$PHPWord = new PHPWord();

// 		// 帳票テンプレート取得
// 		$template = Configure::read('commission_template');
// 		$document = $PHPWord->loadTemplate($template);

// 		// データの設定
// 		$document->setValue('${corp_name}', Sanitize::html($data['MCorp']['official_corp_name']));
// 		$document->setValue('confirmd_fee_rate', $data['CommissionInfo']['confirmd_fee_rate']);
// 		$document->setValue('demand_id', $data['DemandInfo']['id']);
// 		$document->setValue('site_name', Sanitize::html($data['MSite']['site_name']));
// 		$document->setValue('note', Sanitize::html($data['MSite']['note']));
// 		$document->setValue('customer_name', Sanitize::html($data['DemandInfo']['customer_name']));
//  		$document->setValue('address', Util::getDivTextJP('prefecture_div', $data['DemandInfo']['address1']). $data['DemandInfo']['address2']. $data['DemandInfo']['address3']. $data['DemandInfo']['address4']. $data['DemandInfo']['building']. $data['DemandInfo']['room']);

// 		$document->setValue('tel1', $data['DemandInfo']['tel1']);
// 		$document->setValue('tel2', $data['DemandInfo']['tel2']);
// 		$document->setValue('contents', str_replace("\n","<w:br/>", Sanitize::html($data['DemandInfo']['contents'])));
// 		$document->setValue('contents1', $inquiry_data);
// 		$document->setValue('contact_desired_time', $data['DemandInfo']['contact_desired_time']);
// 		$document->setValue('receptionist', $data['MUser']['user_name']);

// 		$make_file = Configure::read('print_tmp_dir'). 'commission_'. $data['DemandInfo']['id']. '.docx';

// 		// 保存
// 		$document->save($make_file);

// 		$file_name = mb_convert_encoding(sprintf('%s_%s_%s.docx', __('CommissionPrintName', true), $data['MCorp']['official_corp_name'], $data['DemandInfo']['id']), 'SJIS-win', 'UTF-8');

 		$make_file = '';
 		$file_name = $this->Print->print_commission($id, $make_file);

		// 出力
		header ("Content-type: application/octet-stream");
		header ("Content-disposition: attachment; filename=" . $file_name);
		readfile($make_file);
 		exit;
	}

}
