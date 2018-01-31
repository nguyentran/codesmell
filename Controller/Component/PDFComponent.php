<?php
App::uses('Component', 'Controller');
// PDF出力用ライブラリ
App::import('Vendor', 'tcpdf/tcpdf');

// フォントfamily
define("COM_CONST_PDF_FONT_NAME", 'kozminproregular');
// 幅
define("CONST_AGENCY_TBLCOL_WIDTH_0", 120);
define("CONST_AGENCY_TBLCOL_WIDTH_1", 77);
// 高さ
define("CONST_AGENCY_TBLCOL_HEIGHT_0",   6);
define("CONST_AGENCY_TBLCOL_HEIGHT_1",   10);

define("CONST_AGENCY_PAGE_POS_X", 142);//285
define("CONST_AGENCY_PAGE_POS_Y", 200);



class PDFComponent extends Component
{
	//var $components = array('Status');

	public function commission($list){

		$official_corp_name = $list[0]['MCorp']['official_corp_name'];

		try {

			// PDF初期化
			$pdf = new TCPDF("L", PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
			// デフォルトヘッダー 使用しない
			$pdf->setPrintHeader(false);
			// デフォルトヘッダー フッター
			$pdf->setPrintFooter(false);
			// 自動改ページの抑止
			$pdf->SetAutoPageBreak(false, 0);
			// ページを追加
			$pdf->AddPage();

			$pdf->SetFont('kozgopromedium');

			$page_cnt = 1;
			$all_cnt = 0;

			// フォント数
			$pdf->SetFont(COM_CONST_PDF_FONT_NAME);
			$pdf->SetDrawColor(160,160,160);

			$html = $this->setPageHeader();

			$html .= '<table class = "table" border="1" cellspacing="0" cellpadding="5">';
			$html .= '  <tr>';
			$html .= '    <th width="380" class="titel_th">加盟店様名</th>';
			$html .= '    <th width="200" class="titel_th">電話番号</th>';
			$html .= '    <th width="200" class="titel_th">発行日</th>';
			$html .= '  </tr>';
			$html .= '  <tr>';
			$html .= '    <td class="data_td">'.$official_corp_name.'</td>';
			$html .= '    <td class="data_td">'.$list[0]['MCorp']['commission_dial'].'</td>';
			$html .= '    <td class="data_td">'.date("Y/m/d").'</td>';
			$html .= '  </tr>';
			$html .= '</table>';
			$html .= '<br>';
			$html .= '<img width="720" src="./../webroot/img/pdf.png">';
			$html .= '<br>';
			$html .= '<table style="width:100%;">';
			$html .= '  <tr>';
			$html .= '    <td width="650">';
			$html .= $official_corp_name.' 御中<br>';
			$html .= 'いつもお世話になっております。株式会社リッツでございます。<br>';
			$html .= '前月以前にご紹介させて頂きましたお客様のうち、案件決着状況の報告を頂きたいお客様の一覧を送らせて頂きます。<br>';
			$html .= '現在の進行状況、並びに施工完了されましたお客様につきましては施工完了日と施工金額をご記入いただき、期限内<br>';
			$html .= 'にご返信をお願い致します。<br>';
			$html .= '<br>';
			$html .= '※尚、弊社へ既に取次表にて報告頂いている案件においても双方の摺り合わせの意味も含め、<br>';
			$html .= '再度チェックして頂き返送いただけると幸いでございます。<br>';
			$html .= '<br>';
			$html .= '大変お手数お掛け致しますが御協力のほど、宜しくお願いいたします。<br>';
			$html .= '<br>';
			$html .= '    </td>';
			$html .= '    <td width="130" align="right"  class = "font-22">';
			$html .= '<br>';
			$html .= '<br>';
			$html .= '<br>';
			$html .= '<br>';
			$html .= '106102<br>';
			$html .= '株式会社リッツ<br>';
			$html .= '〒460-0002<br>';
			$html .= '愛知県名古屋市中区丸の内3-23-20<br>';
			$html .= '桜通MIDビル2F<br>';
			$html .= '電話：0120-949-092<br>';
			$html .= 'FAX：052-971-9922<br>';
			$html .= '    </td>';
			$html .= '  </tr>';
			$html .= '</table>';
			$html .= '<img src="./../webroot/img/pdf2.png">';

			$pdf->writeHTML($html, false, false, false, false, 'L');
			$pdf->Text(CONST_AGENCY_PAGE_POS_X, CONST_AGENCY_PAGE_POS_Y, $page_cnt);
			$page_cnt++;

			// ページを追加
			$pdf->AddPage();

			$html = $this->setPageHeader();
			$html .= '<table class = "table" border="1" cellspacing="0" cellpadding="5">';
			$html .= '  <tr>';
			$html .= '    <th width="380" class="titel_th">加盟店様名</th>';
			$html .= '    <th width="200" class="titel_th">電話番号</th>';
			$html .= '    <th width="200" class="titel_th">発行日</th>';
			$html .= '  </tr>';
			$html .= '  <tr>';
			$html .= '    <td class="data_td">'.$official_corp_name.'</td>';
			$html .= '    <td class="data_td">'.$list[0]['MCorp']['commission_dial'].'</td>';
			$html .= '    <td class="data_td">'.date("Y/m/d").'</td>';
			$html .= '  </tr>';
			$html .= '  </table>';
			$html .= '<br>';
			$html .= '<table style="width:100%;">';
			$html .= '  <tr>';
			$html .= '    <td width="720"><img width="720" src="./../webroot/img/pdf3.png"></td>';
			$html .= '  </tr>';
			$html .= '  <tr>';
			$html .= '    <td width="720">'.$official_corp_name.' 御中</td>';
			$html .= '  </tr>';
			$html .= '  <tr>';
			$html .= '    <td width="720"><span class = "font-bold">※送付しました書面のうち、送付状以外を記入後ご返送いただきますよう宜しくお願い致します。</span></td>';
			$html .= '  </tr>';
			$html .= '  </table>';
			$html .= '<br>';

			$html .= $this->setTableHeader();

			$count = 0;

			foreach ($list as $val) {

				if($count == 9 + ( $all_cnt * 13 )){ // $count == 9 || $count == 22 || $count == 35 || $count == 48
					$html .= '</table>';
					$html .= '<br>';
					$pdf->writeHTML($html, false, false, false, false, 'L');
					$pdf->Text(CONST_AGENCY_PAGE_POS_X, CONST_AGENCY_PAGE_POS_Y, $page_cnt);
					$page_cnt++;
					// ページを追加
					$pdf->AddPage();
					$html = $this->setPageHeader();
					$html .= $this->setTableHeader();
					$all_cnt++;
				}

				// ループ
				$commission_note_send_datetime = $val['CommissionInfo']['commission_note_send_datetime']; // 日付
				$work = $this->__edit_string_display($val['MCategory']['category_name'] , 6); //作業内容
				$demand_id = $val['CommissionInfo']['demand_id'];  // 案件番号
				$customer_name = $this->__edit_string_display($val['DemandInfo']['customer_name'] , 4); // お客様名
				$complete_date = $val['CommissionInfo']['complete_date'];  // 施工完了日
				$amount = $val['CommissionInfo']['construction_price_tax_include'];  //
				$commission_status = $val['MItem']['item_name'];

				$html .= '  <tr>';
				$html .= '    <td class="data-td-center font-20">'.$this->dateYMDFormat($commission_note_send_datetime).'</td>';
				$html .= '    <td class="data-td-center font-20 font-bold">'.$work.'</td>';
				$html .= '    <td class="data-td-center font-20 font-bold">'.$demand_id.'<br><span style = "text-align: left;">'.$customer_name.'</span></td>';
				$html .= '    <td class="data-td-center font-20">'.$complete_date.'</td>';
				$html .= '    <td class="data-td-center font-20">'.$amount.'</td>';
				$html .= '    <td class="data-td-center font-20 font-bold">'.$commission_status.'</td>';
				$html .= '    <td class="data-td-center font-20">変更はない・変更がある</td>';
				$html .= '    <td class="data-td-center font-20">進行中・施工完了・失注</td>';
				$html .= '    <td></td>';
				$html .= '    <td></td>';
				$html .= '    <td></td>';
				$html .= '    <td></td>';
				$html .= '  </tr>';
				$count++;

			}

			// ループ -end-
			$html .= '</table>';
			$html .= '<br>';
			if(($count >= 5 && $count < 10) || ($all_cnt != 0 && $count >= 5+( $all_cnt * 13 ) && $count < 10 + ( $all_cnt * 13 ))){
				$pdf->writeHTML($html, false, false, false, false, 'L');
				$pdf->Text(CONST_AGENCY_PAGE_POS_X, CONST_AGENCY_PAGE_POS_Y, $page_cnt);
				$page_cnt++;
				// ページを追加
				$pdf->AddPage();
				$html = $this->setPageHeader();
			}

			$html .= '<table style="width:100%;">';
			$html .= '  <tr>';
			$html .= '    <td width="720"><img width="720" src="./../webroot/img/pdf4.png"></td>';
			$html .= '  </tr>';
			$html .= '  <tr>';
			$html .= '    <td width="720"><span class = "font-bold">弊社から'.$official_corp_name.'様への取次案件に関して、追加施工が発生した案件はございますでしょうか。</span></td>';
			$html .= '  </tr>';
			$html .= '  <tr>';
			$html .= '    <td width="720"><span class = "font-bold">□ 追加工事はない&nbsp;&nbsp;&nbsp;□ 追加工事あり(ありの場合下記に記載願います)</span></td>';
			$html .= '  </tr>';
			$html .= '</table>';
			$html .= '<br>';

			$html .= '<table style="width:100%;">';
			$html .= '  <tr>';
			$html .= '    <td width="720">';
			$html .= '<span class = "font-bold font-20">';
			$html .= '以上の進捗状況と、追加施工状況の報告内容に虚偽がないものとする。なお本紙に虚偽の記載があった場合、株式会社リッツはカギの佐渡様に<br>';
			$html .= '紹介した過去全案件の調査を行い、その結果虚偽報告があり、且つ悪質であると判断した場合、'.$official_corp_name.'様は違約金を株式会社リッツに支払うものとする。<br>';
			$html .= '※取次表とは別の用紙になります。過去、虚偽報告のありました加盟店様がいた為、大変恐縮ではございますが、進捗表には上記案件状況の最終確認をさせて頂いております。大変恐縮ではございますが、<br>';
			$html .= 'ご理解のほど何卒、宜しくお願い致します。';
			$html .= '</span>';
			$html .= '    </td>';
			$html .= '  </tr>';
			$html .= '</table>';
			$html .= '<img src="./../webroot/img/pdf5.png">&nbsp;<br>';

			if(($count >= 3 && $count < 5) || ($all_cnt != 0 && $count >= 15+(($all_cnt-1)*13) && $count < 5+($all_cnt*13))){
				$pdf->writeHTML($html, false, false, false, false, 'L');
				$pdf->Text(CONST_AGENCY_PAGE_POS_X, CONST_AGENCY_PAGE_POS_Y, $page_cnt);
				$page_cnt++;
				// ページを追加
				$pdf->AddPage();
				$html = $this->setPageHeader();
			}

			$html .= '<table style="width:100%;">';
			$html .= '  <tr>';
			$html .= '    <td width="720"><span class = "font-40">平成&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;年&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;月&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;日</span></td>';
			$html .= '  </tr>';
			$html .= '  <tr>';
			$html .= '    <td width="720">&nbsp;&nbsp;&nbsp;</td>';
			$html .= '  </tr>';
			$html .= '  <tr>';
			$html .= '    <td width="720">'.$official_corp_name.'様 責任者様サイン</td>';
			$html .= '  </tr>';
			$html .= '  <tr>';
			$html .= '    <td width="720"><img src="./../webroot/img/pdf7.png"></td>';
			$html .= '  </tr>';
			$html .= '</table>';


			$pdf->writeHTML($html, false, false, false, false, 'L');
			$pdf->Text(CONST_AGENCY_PAGE_POS_X, CONST_AGENCY_PAGE_POS_Y, $page_cnt);

			$make_file = Configure::read('print_tmp_dir'). 'pdf_'. date('Y-m-d-H-i'). '.pdf';

			// ダウンロード
			$pdf->Output($make_file, 'F');

			$file_name = mb_convert_encoding(__('CommissionPDFName', true).'_'.$official_corp_name.'.pdf' , "SJIS-win" , 'UTF-8');
			// 出力
			header ("Content-type: application/octet-stream");
			header ("Content-disposition: attachment; filename=" . $file_name);
			readfile($make_file);
			exit();

		} catch (Exception $e) {
			return false;
		}
		return true;
	}

	/**
	 * 指定の文字数で改行を行う
	 *
	 *
	 * @param unknown $str 文字列
	 * @param unknown $num  文字数
	 * @return unknown
	 */
	private function __edit_string_display($str , $split_len) {

		mb_internal_encoding('UTF-8');
		mb_regex_encoding('UTF-8');

		if ($split_len <= 0) {
			$split_len = 1;
		}

		$strlen = mb_strlen($str, 'UTF-8');

    	$ret = array();

    	for ($i = 0; $i < $strlen; $i += $split_len) {
        	$ret[] = mb_substr($str, $i, $split_len);
    	}

		$edit_str = null;
		$count = count($ret)-1;

		for($i=0; $i<$count; $i++){
			$edit_str .= $ret[$i].'<br>';
		}
		$edit_str .= $ret[$count];
		return $edit_str;
	}

	/**
	 * ページヘッダ描画
	 *
	 * @return string
	 */
	private function setPageHeader() {

		$Request = '<style>
.table {
	border-color: Grey;
	width:100%;
}
.titel_th {
	font-size:25px;
	text-align: left;
	font-weight:bold;
}
.data_td {
	font-size:35px;
	text-align: left;
	font-weight:bold;
}

.font-bold {
	font-weight:bold;
}

.font-10 {
	font-size:10px;
}

.font-15 {
	font-size:15px;
}

.font-20 {
	font-size:20px;
}

.font-22 {
	font-size:22px;
}

.font-24 {
	font-size:24px;
}

.font-40 {
	font-size:40px;
}

.titel_th_center {
	text-align: center;
	display:table-cell;
	vertical-align: middle;
}

.data-td-center {
	text-align: center;
	display:table-cell;
	vertical-align: middle;
	height: 35px;
}

.table_titel_span {
	font-size:15px;
}

</style>
';// style="font-size:22px;"
		return $Request;
	}

	/**
	 * テーブルヘッダ描画
	 *
	 * @return string
	 */
	private function setTableHeader() {

		$request = '<table class = "table" border="1" cellspacing="0" cellpadding="5">';
		$request .= '  <tr>';
		$request .= '    <th width="52" rowspan="2" class="titel_th_center font-24">日付</th>';
		$request .= '    <th width="55" rowspan="2" class="titel_th_center font-24">作業内容</th>';
		$request .= '    <th width="45" rowspan="2" class="titel_th_center font-24">案件番号<br>お客様名</th>';
		$request .= '    <th width="140" colspan="3" class="titel_th_center font-24">リッツ内管理状況</th>';
		$request .= '    <th width="90" class="titel_th_center font-24" >必須項目</th>';
		$request .= '    <th width="400" colspan="5"class="titel_th_center font-24" >リッツ内管理状況に変更はないですか？との問いに「変更がある」と答えた場合記入</th>';
		$request .= '  </tr>';
		$request .= '  <tr>';
		$request .= '    <th width="52" class="titel_th_center font-24">施工<br>完了日</th>';
		$request .= '    <th width="42" class="titel_th_center font-24" >金額<br>(税抜)</th>';
		$request .= '    <th width="46"class="titel_th_center font-24">状況</th>';
		$request .= '    <th class="titel_th_center font-24">リッツ内管理状況に<br>変更はないですか？<br><span class="font-15">(どちらかに○を付けてください)</span></th>';
		$request .= '    <th width="95" class="titel_th_center font-24">現在の状況</th>';
		$request .= '    <th width="40" class="titel_th_center font-20">施工<br>完了日<br>失注日</th>';
		$request .= '    <th width="90" class="titel_th_center font-24">施工金額(税抜)<br><span class="font-15">(施工完了時のみ記入)</span></th>';
		$request .= '    <th width="90" class="titel_th_center font-24">失注理由<br><span class="font-15">(失注時のみ記入)</span></th>';
		$request .= '    <th width="85" class="titel_th_center font-24">備考欄</th>';
		$request .= '  </tr>';

		return $request;
	}

	/**
	 * 日付形式(日本語)にフォーマット(年月)
	 **/
	public function dateYMDFormat($date = null){

		if(!empty($date)){
			return date("Y/m/d",strtotime($date . ""));
		}
		return $date;
	}

	// 2016.05.30 ota.r@tobila ADD START ORANGE-30 【契約管理】契約規約の特約や条文の変更・削除・追加部分が分かる出力PDFの作成
	/**
	 * 契約規約確認PDFの出力
	 *
	 * @param unknown $org_terms
	 * @param unknown $custom_terms
	 * @param unknown $corp_data
	 */
	public function agreement_terms($corp_data, $agreement_data, $diff) {

		/*
		 * 必要変数の宣言
		 * PDFのテンプレートはヒアドキュメントで宣言
		 */
		$corp_id        = $corp_data['MCorp']['id'];
		$corp_name      = $corp_data['MCorp']['official_corp_name'];
		$agreement_id   = isset($agreement_data['CorpAgreement']['id']) ? $agreement_data['CorpAgreement']['id'] : "";
		$org_terms      = empty($agreement_data['CorpAgreement']['original_agreement']) ? "" : str_replace(array("\n", "\r\n"), "<br>", $agreement_data['CorpAgreement']['original_agreement']);
		$cst_terms      = empty($agreement_data['CorpAgreement']['customize_agreement']) ? "" : str_replace(array("\n", "\r\n"), "<br>", $agreement_data['CorpAgreement']['customize_agreement']);
		$file_name_tmpl = __('AgreementProvisionPDFName', true);         // ファイル名の固定部分の取り出し
		$file_name      = "【" . $corp_name . "】" . $file_name_tmpl . ".pdf";  // ファイル名
		$to_enc         = 'SJIS-win';                                    // 変換後のファイル名のエンコード
		$from_enc       = 'UTF-8';                                       // 変換前のファイル名のエンコード
		$pdf_title      = 'WEB契約書・契約条文変更管理システム';         // PDFの先頭に記載するタイトル
		$title_style    = 'style="font-size: 48px; font-weight: 700;"';
		$item_style     = 'style="font-size: 36px; font-weight: 700; text-align: center;"';
//		$frame_style    = 'style="border: solid 1px black; border-collapse: collapse;"';
		$frame_style    = 'style=""';
		$sentense_style = 'style="font-size: 24px;"';
		$diff_style     = 'style="font-size: 36px;"';

		/* 契約日の整形 */
		$tmp_date       = isset($agreement_data['CorpAgreement']['acceptation_date']) ? str_replace('-', '/', $agreement_data['CorpAgreement']['acceptation_date']) : "";
		$tmp_arr        = explode(':', $tmp_date, -1);
		$agreement_date = $tmp_arr[0] . ':' . $tmp_arr[1];

		/* 外枠 */
// タイトルを表示する場合は以下を<table>の上に追加
//<br>
//<div $title_style>
//$pdf_title
//</div>
//<br>
		$outline_tmpl = <<<HERE
<table>
	<tr>
		<td width="80">
		  企業ID
		</td>
		<td>
		  ：&nbsp;&nbsp;$corp_id
		</td>
	</tr>
	<tr>
		<td>
		  商号
		</td>
		<td>
		  ：&nbsp;&nbsp;$corp_name
		</td>
	</tr>
	<tr>
		<td>
		  契約ID
		</td>
		<td>
		  ：&nbsp;&nbsp;$agreement_id
		</td>
	</tr>
	<tr>
		<td>
		  契約承認日時
		</td>
		<td>
		  ：&nbsp;&nbsp;$agreement_date
		</td>
	</tr>
</table>
<br>
<table>
  <tr>
    <th $item_style>
      基本契約約款
    </th>
    <th $item_style>
      加盟店特約
    </th>
  </tr>
  <tr>
    <td width="560" $frame_style>
      <div $sentense_style>
%s
      </div>
    </td>
    <td width="20">
      &nbsp;
    </td>
    <td width="560" $frame_style>
      <div $sentense_style>
%s
      </div>
    </td>
  </tr>
</table>
HERE;
		$diff_table_tmpl = <<<HERE
<div $title_style>
― 特約条項一覧
</div>
<br>
<div $diff_style>
%s
</div>
HERE;

		/* 規約をHTMLに記載 */
		$terms_table = sprintf($outline_tmpl, $org_terms, $cst_terms);

		/* 差分をHTMLに記載 */
		$diff_titles = "";
		foreach ($diff as $line) {
			$diff_titles .= "・" . $line . "<br>";
		}
		if (!empty($diff_titles)) {
			$diff_table = sprintf($diff_table_tmpl, $diff_titles);
		}

		try {
			/* PDF初期化 */
			$pdf = new TCPDF("L", PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
			$pdf->setPrintHeader(false);
			$pdf->setPrintFooter(false);

			/* フォント数 */
			$pdf->SetFont(COM_CONST_PDF_FONT_NAME);
			$pdf->SetDrawColor(160,160,160);

			/* タイトルの設定 */
			$pdf->SetTitle($file_name);

			/* 変更後を出力 */
			$pdf->AddPage('LANDSCAPE', 'A3');
			$pdf->writeHTML($terms_table, false, false, false, false, 'L');
			$pdf->Text(CONST_AGENCY_PAGE_POS_X, CONST_AGENCY_PAGE_POS_Y);

			/* 改ページして差分を出力 */
			if (!empty($diff_table)) {
				$pdf->AddPage('LANDSCAPE', 'A3');
				$pdf->writeHTML($diff_table, false, false, false, false, 'L');
				$pdf->Text(CONST_AGENCY_PAGE_POS_X, CONST_AGENCY_PAGE_POS_Y);
			}

//			$make_file = Configure::read('print_tmp_dir') . 'agreement_pdf_'. date('Y-m-d-H-i') . '.pdf';

			/* ダウンロード */
			$file_name = $corp_id . ".pdf";
			$pdf->Output($file_name, 'I');

			/* 出力 */
//			header ("Content-type: application/octet-stream; charset=utf-8");
//			$file_name = mb_convert_encoding($file_name, $to_enc, $from_enc);
//			header ("Content-disposition: attachment; filename=" . $file_name);
//			readfile($make_file);
			exit();

		} catch (Exception $e) {
			return false;
		}

		return true;
	}
	// 2016.05.30 ota.r@tobila ADD END ORANGE-30 【契約管理】契約規約の特約や条文の変更・削除・追加部分が分かる出力PDFの作成
}
?>