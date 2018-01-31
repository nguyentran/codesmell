<?php
App::uses('Component', 'Controller');
// Excel出力用ライブラリ
App::import( 'Vendor', 'PHPExcel', array('file'=>'PHPExcel.php') );
App::import( 'Vendor', 'PHPExcel_IOFactory', array('file'=>'PHPExcel' . DS . 'IOFactory.php') );

class ExcelComponent extends Component
{
	var $components = array();

	/**
	 * 請求書ダウンロード
	 *
	 * @param unknown $results
	 * @param unknown $filename
	 */
	public function bill_download($data , $mcorp_data , $filename) {

		// 企業コード
		$mcorp_id = $mcorp_data['mcorp_id'];

		// 企業名
		$official_corp_name = $mcorp_data['official_corp_name'];

		// テンプレートの場所を指定
		$template_path =  Configure::read('bill_template');

		// エクセルの設定
		$book = $this->_excel_creating($template_path);

		// シートの設定を行う
		$book->setActiveSheetIndex(0);
		$sheet = $book->getActiveSheet();
		// シート名を変更する
		$sheet->setTitle($official_corp_name);

		/*
		 *  セルに値をセットする
		 */
		$createdate = PHPExcel_Shared_Date::PHPToExcel(new DateTime( date("Y/m/d")));
		$sheet->setCellValue("G1",$createdate);						// 作成日
		$sheet->setCellValue("A5", $official_corp_name);			// 会社名
		$sheet->setCellValue("A13", __('ExcelString1', true) . date("n" , strtotime('-1 month')) . __('ExcelString2', true));

		$count = 0;
		$total_fee_tax_exclude = 0;
		$total_tax = 0;
		$total_insurance_price = 0;
		foreach ($data as $val){

			$total_fee_tax_exclude = $total_fee_tax_exclude + $val['BillInfo']['fee_tax_exclude'];
			$total_tax = $total_tax + $val['BillInfo']['tax'];
			$total_insurance_price = $total_insurance_price + $val['BillInfo']['insurance_price'];

			// セルの追加
			$sheet->insertNewRowBefore( 19, 1 );
			// セルの高さの指定
			$sheet->getRowDimension( 19 )->setRowHeight( 21 );

			$sheet->setCellValue("A19", $val['BillInfo']['demand_id']);					// 受注No.
			$sheet->setCellValue("B19", $val['CommissionInfo']['complete_date']);		// 施工日
			$sheet->setCellValue("C19", $val['DemandInfo']['customer_name']);			// お客様名
			$sheet->setCellValue("D19", $val['BillInfo']['fee_target_price']);			// 手数料対象金額(税抜)
			$sheet->setCellValue("E19", $val['BillInfo']['comfirmed_fee_rate']);		// 手数料率(%)
			$sheet->setCellValue("F19", $val['BillInfo']['fee_tax_exclude']);			// 手数料
			$sheet->setCellValue("G19", $val['BillInfo']['tax']);						// 消費税
			$sheet->setCellValue("H19", $val['BillInfo']['insurance_price']);			// 賠償責任保険料

			// 文字サイズの指定
			$sheet->getStyle( 'A19' )->getFont()->setSize( 11 );
			$sheet->getStyle( 'B19' )->getFont()->setSize( 11 );
			$sheet->getStyle( 'C19' )->getFont()->setSize( 11 );
			$sheet->getStyle( 'D19' )->getFont()->setSize( 11 );
			$sheet->getStyle( 'E19' )->getFont()->setSize( 11 );
			$sheet->getStyle( 'F19' )->getFont()->setSize( 11 );
			$sheet->getStyle( 'G19' )->getFont()->setSize( 11 );
			$sheet->getStyle( 'H19' )->getFont()->setSize( 11 );
			// セルの横位置の指定
			$sheet->getStyle( 'D19' )->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_GENERAL);
			$sheet->getStyle( 'F19' )->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_GENERAL);
			$sheet->getStyle( 'G19' )->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_GENERAL);
			$sheet->getStyle( 'H19' )->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_GENERAL);
			// 日付表示
			$sheet->getStyle('B19')->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_DATE_DDMMYYYY);
			// 通貨表示
			$sheet->getStyle('D19')->getNumberFormat()->setFormatCode('#,##0;[Red]-#,##0');
			$sheet->getStyle('F19')->getNumberFormat()->setFormatCode('#,##0;[Red]-#,##0');
			$sheet->getStyle('G19')->getNumberFormat()->setFormatCode('#,##0;[Red]-#,##0');
			$sheet->getStyle('H19')->getNumberFormat()->setFormatCode('#,##0;[Red]-#,##0');
			// 文字列のインデント & 縮小して全体を表示
			$sheet->getStyle('A19')->getAlignment()->setShrinkToFit(true);
			$sheet->getStyle('B19')->getAlignment()->setShrinkToFit(true);
			$sheet->getStyle('C19')->getAlignment()->setShrinkToFit(true);

			$count++;
		}

		$num = $count+19;

		// 前月繰越残高
		if(!empty($mcorp_data['past_bill_price'])){
			// セルの追加
			$sheet->insertNewRowBefore( $num, 1 );
			// セルの高さの指定
			$sheet->getRowDimension( $num )->setRowHeight( 21 );
			// セル結合
			$sheet->mergeCells('D'.$num.':E'.$num);
			$sheet->mergeCells('F'.$num.':H'.$num);

			$sheet->setCellValue("A".$num , 'その他');							// 受注No.
			$sheet->setCellValue("C".$num , '前月繰越残高');	// お客様名

			// 文字サイズの指定
			$sheet->getStyle( "A".$num )->getFont()->setSize( 11 );
			$sheet->getStyle( "C".$num )->getFont()->setSize( 11 );

			// 文字列のインデント & 縮小して全体を表示
			$sheet->getStyle( "A".$num )->getAlignment()->setShrinkToFit(true);
			$sheet->getStyle( "C".$num )->getAlignment()->setShrinkToFit(true);
			// セルの横位置の指定
			$sheet->getStyle( "F".$num )->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_GENERAL);
			$sheet->getStyle( "F".$num )->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
			// 通貨表示
			$sheet->getStyle("F".$num)->getNumberFormat()->setFormatCode('#,##0;[Red]-#,##0');

			$sheet->setCellValue("F".$num, $mcorp_data['past_bill_price']);
			$count++;
		}

		$num = $count+19;
		$sheet->setCellValue("F".$num, $total_fee_tax_exclude);			// 手数料(小計)
		$sheet->setCellValue("G".$num, $total_tax);						// 消費税(小計)
		$sheet->setCellValue("H".$num, $total_insurance_price);			// 賠償責任保険料(小計)

		$num = $count+20;
		$all_money = $mcorp_data['past_bill_price'] + $total_fee_tax_exclude + $total_tax + $total_insurance_price;
		$sheet->setCellValue("G".$num, $all_money);						// ご請求金額合計

		$string = __('ExcelString3', true) . date("n" , strtotime('+1 month')) . __('ExcelString4', true) . PHP_EOL . __('ExcelString5', true);
		$num = $count+23;
		$sheet->setCellValue("A".$num, $string);

		$num = $count+26;
		$sheet->setCellValue("A".$num, __('ExcelString6', true) . $mcorp_id);				// 御社企業コード

		$this->_excel_download($filename.'.xlsx' , $book);
	}

	/**
	 * エクセルの設定
	 *
	 * @param unknown $template_path
	 * @return unknown
	 */
	public function _excel_creating($template_path){
		$reader = PHPExcel_IOFactory::createReader('Excel2007');
		$book = $reader->load($template_path);
		return $book;
	}

	/**
	 * エクセルダウンロード
	 * @param unknown $filename
	 * @param unknown $book
	 */
	public function _excel_download($filename , $book){

		$filename = mb_convert_encoding($filename, "SJIS-WIN", "UTF-8");
		$objWriter = PHPExcel_IOFactory::createWriter($book, 'Excel2007');

		header("Pragma: public");
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Content-Type: application/force-download");
		header("Content-Type: application/octet-stream name='".$filename."'");
		header("Content-Type: application/download");
		header("Content-Disposition: attachment;filename=".$filename."");
		header("Content-Transfer-Encoding: binary ");

		$objWriter->save('php://output');
		exit();
	}
}
?>