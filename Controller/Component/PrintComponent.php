<?php
App::uses('Component', 'Controller');
App::import( 'Vendor', 'PHPWord', array('file'=>'PHPWord.php') );

class PrintComponent extends Component {
	public $current_controller;

	public function startup(Controller $controller) {
		$this->current_controller = $controller;
	}

	// 取次票印刷
	public function print_commission($id, &$make_file) {

		if ($id == null) {
			throw new Exception();
		}

		$conditions = array('CommissionInfo.id' => $id);

		$joins = array(
					array(
						'table' => 'demand_infos',
						'alias' => "DemandInfo",
						'type' => 'inner',
						'conditions' => array('CommissionInfo.demand_id = DemandInfo.id')
					),
					array(
						'table' => 'm_sites',
						'alias' => "MSite",
						'type' => 'inner',
						'conditions' => array('DemandInfo.site_id = MSite.id')
					),
					array(
						'table' => 'm_users',
						'alias' => "MUser",
						'type' => 'left',
						'conditions' => array('DemandInfo.receptionist = MUser.id')
					)
		);

		// データ取得
		$params = array(
				'fields' => '*, MCorp.*,
							DemandInfo.id, DemandInfo.customer_name, DemandInfo.customer_name, DemandInfo.address1, DemandInfo.address2,  DemandInfo.address3, DemandInfo.address4,
							DemandInfo.building, DemandInfo.room, DemandInfo.tel1, DemandInfo.tel2, DemandInfo.contents, DemandInfo.contact_desired_time, DemandInfo.receptionist, MSite.site_name, MSite.note, MUser.user_name,
							DemandInfo.site_id, DemandInfo.jbr_order_no, DemandInfo.jbr_category, DemandInfo.jbr_work_contents, MSite.jbr_flg, DemandInfo.construction_class',
				'conditions' => $conditions,
				'joins' => $joins
		);

		$data = $this->current_controller->CommissionInfo->find('first', $params);

		$InquiryData = array();

		if(!empty($data['DemandInfo']['id'])){
			$conditions = array ('DemandInquiryAnswer.demand_id' => $data ['DemandInfo'] ['id']);
			$InquiryData = $this->current_controller->DemandInquiryAnswer->find ( 'all', array (
					'fields' => "* ,MInquiry.inquiry_name",
					'joins' => array (
							array (
									'fields' => '*',
									'type' => 'inner',
									"table" => "m_inquiries",
									"alias" => "MInquiry",
									"conditions" => array (
											"MInquiry.id = DemandInquiryAnswer.inquiry_id"
									)
							)
					),
					'conditions' => $conditions,
					'order' => array (
							'DemandInquiryAnswer.id' => 'ASC'
					)
			) );
		}

		$data['InquiryData'] = '';
		if(!empty($InquiryData)){
			$count = count($InquiryData)-1;
			foreach ($InquiryData as $key => $val){
				$data['InquiryData'] .= $val['MInquiry']['inquiry_name'].'：'.$val['DemandInquiryAnswer']['answer_note'];
				if($count != $key){
					$data['InquiryData'] .= ', ';
				}
			}
		}

		// ファイル作成
		return $this->makeWordFile($data, $make_file, false);

	}

	// FAX用取次票
	public function fax_commission($demand_id, $corp_id, &$make_file) {

		if ($demand_id == null || $corp_id == null) {
			throw new Exception();
		}

		$conditions = array(array('DemandInfo.id' => $demand_id), array('CommissionInfo.corp_id' => $corp_id));

		$joins = array(
				array(
						'table' => 'demand_infos',
						'alias' => "DemandInfo",
						'type' => 'inner',
						'conditions' => array('CommissionInfo.demand_id = DemandInfo.id')
				),
				array(
						'table' => 'm_sites',
						'alias' => "MSite",
						'type' => 'inner',
						'conditions' => array('DemandInfo.site_id = MSite.id')
				),
				array(
						'table' => 'm_users',
						'alias' => "MUser",
						'type' => 'left',
						'conditions' => array('DemandInfo.receptionist = MUser.id')
				)
		);

		// データ取得
		$params = array(
				'fields' => '*, MCorp.*,
							DemandInfo.id, DemandInfo.customer_name, DemandInfo.customer_name, DemandInfo.address1, DemandInfo.address2,  DemandInfo.address3, DemandInfo.address4,
							DemandInfo.building, DemandInfo.room, DemandInfo.tel1, DemandInfo.tel2, DemandInfo.contents, DemandInfo.contact_desired_time, DemandInfo.receptionist, MSite.site_name, MSite.note, MUser.user_name,
							DemandInfo.site_id, DemandInfo.jbr_order_no, DemandInfo.jbr_category, DemandInfo.jbr_work_contents, DemandInfo.construction_class, MSite.jbr_flg',
				'conditions' => $conditions,
				'joins' => $joins
		);

		$data = $this->current_controller->CommissionInfo->find('first', $params);

		$InquiryData = array();

		if(!empty($data['DemandInfo']['id'])){
			$conditions = array ('DemandInquiryAnswer.demand_id' => $data ['DemandInfo'] ['id']);
			$InquiryData = $this->current_controller->DemandInquiryAnswer->find ( 'all', array (
					'fields' => "* ,MInquiry.inquiry_name",
					'joins' => array (
							array (
									'fields' => '*',
									'type' => 'inner',
									"table" => "m_inquiries",
									"alias" => "MInquiry",
									"conditions" => array (
											"MInquiry.id = DemandInquiryAnswer.inquiry_id"
									)
							)
					),
					'conditions' => $conditions,
					'order' => array (
							'DemandInquiryAnswer.id' => 'ASC'
					)
			) );
		}

		$data['InquiryData'] = '';
		if(!empty($InquiryData)){
			$count = count($InquiryData)-1;
			foreach ($InquiryData as $key => $val){
				$data['InquiryData'] .= $val['MInquiry']['inquiry_name'].'：'.$val['DemandInquiryAnswer']['answer_note'];
				if($count != $key){
					$data['InquiryData'] .= ', ';
				}
			}
		}

		// ファイル作成
		return $this->makeWordFile($data, $make_file);

	}

	// 取次票作成
	private function makeWordFile($data, &$make_file, $isMailFile = true) {

		$PHPWord = new PHPWord();

		$site = $this->current_controller->MSite->findById($data['DemandInfo']['site_id']);

		// 帳票テンプレート振り分け
		//JBR(ガラス)、JBR、その他
		if ($site['MSite']['jbr_flg'] == 1 && $data['DemandInfo']['jbr_work_contents'] == Configure::read('jbr_glass_category')) {
			$template = Configure::read('commission_template_jbrglass');
		} elseif ($site['MSite']['jbr_flg'] == 1) {
			$template = Configure::read('commission_template_jbr');
		} else {
// murata.s ORANGE-261 CHG(S)
			//ORANGE-135 iwai 2016.08.03 CHG S
			if(isset($data['CommissionInfo']['commission_type'])
				&& $data['CommissionInfo']['commission_type'] == 1){
				$template = Configure::read('commission_template_introduce');
			}else{
				$template = Configure::read('commission_template');
			}
			//ORANGE-135 iwai 2016.08.03 CHG E
// murata.s ORANGE-261 CHG(E)
		}

		$document = $PHPWord->loadTemplate($template);

//		2016.01.09 h.hanaki ORANGE-1171 (S)
		//変換元文字(文字が原因でWORDが開けない場合、変換前と変換後の文字を追加していく)
		$org=Array("“","”","−");
		//変換後文字
 		$new=Array("\"","\"","-");
//		2016.01.09 h.hanaki ORANGE-1171 (E)

		// データの設定
		$document->setValue('corp_name', Sanitize::html($data['MCorp']['official_corp_name']));
		// 2015.05.19 h.hara ORANGE-461(S)
// 		$document->setValue('confirmd_fee_rate', $data['CommissionInfo']['confirmd_fee_rate']);
		$document->setValue('confirmd_fee_rate', $data['CommissionInfo']['commission_fee_rate']);
		// 2015.05.19 h.hara ORANGE-461(E)
		$document->setValue('demand_id', $data['DemandInfo']['id']);
		$document->setValue('site_name', Sanitize::html($data['MSite']['site_name']));
		$document->setValue('note', Sanitize::html($data['MSite']['note']));
//		2016.01.09 h.hanaki ORANGE-1171 (S)
//		$document->setValue('customer_name', Sanitize::html($data['DemandInfo']['customer_name']));
//		$document->setValue('address', Util::getDivTextJP('prefecture_div', $data['DemandInfo']['address1']). $data['DemandInfo']['address2']. $data['DemandInfo']['address3']. $data['DemandInfo']['address4']. $data['DemandInfo']['building']. $data['DemandInfo']['room']);
//		$document->setValue('address', Sanitize::html(Util::getDivTextJP('prefecture_div', $data['DemandInfo']['address1']). $data['DemandInfo']['address2']. $data['DemandInfo']['address3']. $data['DemandInfo']['address4']. $data['DemandInfo']['building']. $data['DemandInfo']['room']));
		$document->setValue('customer_name', Sanitize::html(str_replace($org,$new,$data['DemandInfo']['customer_name'])));
		$customer_address = Util::getDivTextJP('prefecture_div', $data['DemandInfo']['address1']). $data['DemandInfo']['address2']. $data['DemandInfo']['address3']. $data['DemandInfo']['address4']. $data['DemandInfo']['building']. $data['DemandInfo']['room'];
		$customer_address = str_replace($org,$new,$customer_address);
		$document->setValue('address', Sanitize::html($customer_address));
//		2016.01.09 h.hanaki ORANGE-1171 (E)
		$document->setValue('construction_class', Util::getDropText('建物種別', $data['DemandInfo']['construction_class']));

		$document->setValue('tel1', $data['DemandInfo']['tel1']);
		$document->setValue('tel2', $data['DemandInfo']['tel2']);
//		2016.01.09 h.hanaki ORANGE-1171 (S)
//		$document->setValue('contents', str_replace("\n","<w:br/>", Sanitize::html($data['DemandInfo']['contents'])));
		$document->setValue('contents', str_replace("\n","<w:br/>", Sanitize::html(str_replace($org,$new,$data['DemandInfo']['contents']))));
//		2016.01.09 h.hanaki ORANGE-1171 (E)
		$document->setValue('contents1', $data['InquiryData']);
		//$document->setValue('contact_desired_time', $data['DemandInfo']['contact_desired_time']);
		$document->setValue('receptionist', Sanitize::html($data['MUser']['user_name']));
		// 2015.12.10 h.hanaki ORANGE-1013 【取次表修正】取次表FAXに取次IDの表記追加
		$document->setValue('commission_id', $data['CommissionInfo']['id']);

		$document->setValue('jbr_order_no', Sanitize::html($data['DemandInfo']['jbr_order_no']));
		// 2015.04.16 hj.hara(S)
		//$document->setValue('jbr_work_contents', Sanitize::html(Util::getDropText('[JBR様]カテゴリ', $data['DemandInfo']['jbr_work_contents'])));
		$document->setValue('jbr_work_contents', Sanitize::html(Util::getDropText('[JBR様]作業内容', $data['DemandInfo']['jbr_work_contents'])));
		// 2015.04.16 h.har(E)

		$make_file = Configure::read('print_tmp_dir'). sprintf('commission_%s_%s.docx', $data['DemandInfo']['id'], $data['CommissionInfo']['id']);

		// 保存
		$document->save($make_file);

		// 2015.12.26 ohta
		// ORANGE-1018 添付ファイルの拡張子がない場合のバグ
		if($isMailFile){
			$file_name = mb_encode_mimeheader(mb_convert_encoding(sprintf('%s_%s_%s.docx', __('CommissionPrintName', true), $data['MCorp']['official_corp_name'], $data['DemandInfo']['id']), 'ISO-2022-JP', 'UTF-8'));
		}else{
			$file_name = mb_convert_encoding(sprintf('%s_%s_%s.docx', __('CommissionPrintName', true), $data['MCorp']['official_corp_name'], $data['DemandInfo']['id']), 'SJIS-win', 'UTF-8');
		}
		return $file_name;
	}

}