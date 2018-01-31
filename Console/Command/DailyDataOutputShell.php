<?php
App::uses('AppShell', 'Console/Command');

class DailyDataOutputShell extends AppShell
{
	public $uses = array('AppModel');

	const IFDEBUG = true;
//	const DIR_BASE = "/var/www/htdocs/app/tmp/file/dailylist/";
	const DIR_BASE = "/var/www/htdocs_rits/app/tmp/file/dailylist/";
	const DIR_OUT1 = "list1/";
	const DIR_OUT2 = "list2/";
	const DIR_OUT3 = "list3/";
	const DIR_OUT4 = "list4/";
	const DIR_OUT5 = "list5/";

	public function main()
	{
		$this->__prepare();
		//案件一覧(月単位)
		$this->__demandListOfTheCurrentMonth();
		//案件一覧(過去全部)
		$this->__demandListOfThePast();
		//加盟企業一覧(月単位)
		$this->__MCorpOfTheCurrentMonth();
		//未加盟企業一覧(月単位)
		$this->__NoAffiliationMCorpOfTheCurrentMonth();
		//進捗表用一覧(月単位)
		$this->__ProgressOfTheCurrentMonth();

	}

	private function __prepare()
	{
		$this->AppModel->useTable = false;
	}

	const FILE_NAME_DEMANDLIST_CURRENT_MONTH = "ORANGE案件・取次一覧";

	private function __demandListOfTheCurrentMonth()
	{
		echo "<" . date('Y-m-d H:i:s') . ">案件取次一覧月次--start--\n";
		$now = new DateTime();
		$file_name = $now->format('Ymd') . self::FILE_NAME_DEMANDLIST_CURRENT_MONTH . "(" . $now->format('n') . "月).csv";
		$self = $this;
		$this->__output(self::DIR_BASE . self::DIR_OUT1,
			$file_name,
			function ($limit, $offset) use ($now, $self) {
				$query = <<<EOF
SELECT
	DI.id AS 案件ID
	, (SELECT item_name FROM m_items WHERE item_category = '案件状況' AND item_id = DI.demand_status) AS 案件状況
	, (SELECT item_name FROM m_items WHERE item_category = '案件失注理由' AND item_id = DI.order_fail_reason) AS 失注理由
	, receive_datetime AS 受信日時
	, (SELECT site_name FROM m_sites WHERE id = DI.site_id) AS サイト
	, (SELECT genre_name FROM m_genres WHERE id = DI.genre_id) AS ジャンル
	, (SELECT category_name FROM m_categories WHERE id = DI.category_id) AS カテゴリ
	, (SELECT site_name FROM m_sites WHERE id = DI.cross_sell_source_site) AS 【クロスセル】元サイト
	, (SELECT genre_name FROM m_genres WHERE id = DI.cross_sell_source_genre) AS 【クロスセル】元ジャンル
	, (SELECT category_name FROM m_categories WHERE id = DI.cross_sell_source_category) AS 【クロスセル】元カテゴリ
	, source_demand_id AS 元案件番号
	, (SELECT user_name FROM m_users WHERE id = receptionist) AS 受付者
	, customer_name AS お客様名
	, (SELECT address1 FROM m_posts WHERE SUBSTRING(jis_cd, 1, 2) = CASE WHEN LENGTH(DI.address1) = 1 THEN '0' || DI.address1 ELSE DI.address1 END GROUP BY address1) AS 都道府県
	, address2 AS 市区町村
	, address3 AS 町域
	, address4 AS 丁目番地
	, building AS 建物名
	, room AS 部屋号数
	, order_no_marriage AS 注文番号※婚活のみ
	, jbr_order_no AS "[JBR様]受付No"
	, case
		WHEN   DI.id < 400000 THEN  DI.jbr_work_contents
		ELSE                        (SELECT item_name FROM m_items WHERE item_category = '[JBR様]作業内容' AND item_id = CAST(CASE WHEN DI.jbr_work_contents = '' THEN '0' ELSE DI.jbr_work_contents END AS integer))
		END  AS "[JBR様]作業内容"
	, case
		WHEN   DI.id < 400000 THEN   DI.jbr_category
		ELSE                        (SELECT item_name FROM m_items WHERE item_category = '[JBR様]カテゴリ' AND item_id = CAST(CASE WHEN DI.jbr_category = '' THEN '0' ELSE DI.jbr_category END AS integer))
		END  AS "[JBR様]カテゴリ"
	, CASE WHEN DI.commission_limitover_time > 0
		THEN (DI.commission_limitover_time / 60)::varchar || '時間' || (DI.commission_limitover_time % 60)::varchar || '分'
		ELSE NULL END AS 取次完了リミット超過時間
	-- 取次情報
	, CI.id AS 取次ID
	, CI.corp_id AS 取次先企業ID
	, (SELECT corp_name FROM m_corps WHERE id = CI.corp_id) AS 取次先企業名
	, (SELECT official_corp_name FROM m_corps WHERE id = CI.corp_id) AS 取次先正式企業名
	, commit_flg AS 確定フラグ
	, commission_type AS 取次種別
	, lost_flg AS 取次前失注フラグ
	, (SELECT user_name FROM m_users WHERE id = CAST(CASE WHEN appointers = '' THEN '0' ELSE appointers END AS integer)) AS 選定者
	, first_commission AS 初取次チェック
	, corp_fee AS 取次先手数料
	, commission_fee_rate AS 取次時手数料率
	, commission_note_send_datetime AS 取次票送信日時
	, (SELECT user_name FROM m_users WHERE id = CAST(CASE WHEN commission_note_sender = '' THEN '0' ELSE commission_note_sender END AS integer)) AS 取次票送信者
	, (SELECT item_name FROM m_items WHERE item_category = '取次状況' AND item_id = commission_status) AS 取次状況
	, (SELECT item_name FROM m_items WHERE item_category = '取次失注理由' AND item_id = commission_order_fail_reason) AS 取次失注理由
	, CI.complete_date AS 施工完了日
	, CI.order_fail_date AS 失注日
	, estimate_price_tax_exclude AS "見積金額(税抜)"
	, construction_price_tax_exclude AS "施工金額(税抜)"
	, construction_price_tax_include AS "施工金額(税込)"
	, deduction_tax_include AS "控除金額(税込)"
	, deduction_tax_exclude AS "控除金額(税抜)"
	, (SELECT irregular_fee_rate FROM bill_infos WHERE demand_id = DI.id AND commission_id = CI.id ORDER BY irregular_fee_rate NULLS LAST LIMIT 1) AS イレギュラー手数料率
	, (SELECT irregular_fee FROM bill_infos WHERE demand_id = DI.id AND commission_id = CI.id ORDER BY irregular_fee_rate NULLS LAST LIMIT 1) AS イレギュラー手数料金額

	, (SELECT comfirmed_fee_rate FROM bill_infos WHERE demand_id = DI.id AND commission_id = CI.id ORDER BY irregular_fee_rate NULLS LAST LIMIT 1) AS 確定手数料率
	, (SELECT fee_target_price FROM bill_infos WHERE demand_id = DI.id AND commission_id = CI.id ORDER BY irregular_fee_rate NULLS LAST LIMIT 1) AS 手数料対象金額
	, (SELECT fee_tax_exclude FROM bill_infos WHERE demand_id = DI.id AND commission_id = CI.id ORDER BY irregular_fee_rate NULLS LAST LIMIT 1) AS "手数料(税抜)"
	, (SELECT tax FROM bill_infos WHERE demand_id = DI.id AND commission_id = CI.id ORDER BY irregular_fee_rate NULLS LAST LIMIT 1) AS 消費税
	, (SELECT insurance_price FROM bill_infos WHERE demand_id = DI.id AND commission_id = CI.id ORDER BY irregular_fee_rate NULLS LAST LIMIT 1) AS 保険料金額
	, (SELECT total_bill_price FROM bill_infos WHERE demand_id = DI.id AND commission_id = CI.id ORDER BY irregular_fee_rate NULLS LAST LIMIT 1) AS 合計請求金額
	, (SELECT fee_billing_date FROM bill_infos WHERE demand_id = DI.id AND commission_id = CI.id ORDER BY irregular_fee_rate NULLS LAST LIMIT 1) AS 手数料請求日

	, unit_price_calc_exclude AS 取次単価対象外
	, report_note AS 報告備考欄
	, reported_flg AS 加盟店報告
	, DI.del_flg AS 案件削除フラグ
	, CI.introduction_not AS 紹介不可
	, CI.introduction_free AS 紹介無料
	, CI.del_flg AS 取次先情報削除フラグ
	,CI.progress_reported AS 進捗表回収
	,CI.progress_report_datetime AS 進捗表回収日時
	,CI.select_commission_unit_price_rank AS 取次単価ランク
	,DI.reservation_demand AS リザベ案件
	,DI.special_measures AS 特別施策
	,DI.riro_kureka AS リロ・クレカ案件
	,DI.selection_system AS 選定方式
	,DI.auction AS 入札流れ案件
	,case
		when DI.priority = 1 then '大至急'
		when DI.priority = 2 then '至急'
		when DI.priority = 3 then '通常'
		else '-'
		end AS "優先度"

FROM demand_infos DI
LEFT OUTER JOIN commission_infos CI
ON CI.demand_id = DI.id
WHERE receive_datetime BETWEEN '{$now->format('Y/m/01')} 0:00:00' AND '{$now->format('Y/m/d')} 23:59:59'
ORDER BY DI.id, CI.id
LIMIT {$limit} OFFSET {$offset}					
EOF;
				return $self->AppModel->query($query);
			}, 1000);
		echo "<" . date('Y-m-d H:i:s') . ">案件取次一覧月次--end--\n";
	}
	
	const FILE_NAME_DEMANDLIST_PAST = "ORANGE案件・取次一覧";
	
	private function __demandListOfThePast()
	{
		echo "<" . date('Y-m-d H:i:s') . ">案件取次一覧--start--\n";
		$now = new DateTime();
		$file_name = $now->format('Ymd') . self::FILE_NAME_DEMANDLIST_PAST . ".csv";
		//起動日の一日前まで
		$now->sub(new DateInterval('P1D'));
		$self = $this;
		$this->__output(self::DIR_BASE . self::DIR_OUT2,
				$file_name,
				function ($limit, $offset) use ($now, $self) {
					$query = <<<EOF
SELECT
	DI.id AS 案件ID
	, (SELECT item_name FROM m_items WHERE item_category = '案件状況' AND item_id = DI.demand_status) AS 案件状況
	, (SELECT item_name FROM m_items WHERE item_category = '案件失注理由' AND item_id = DI.order_fail_reason) AS 失注理由
	, receive_datetime AS 受信日時
	, (SELECT site_name FROM m_sites WHERE id = DI.site_id) AS サイト
	, (SELECT genre_name FROM m_genres WHERE id = DI.genre_id) AS ジャンル
	, (SELECT category_name FROM m_categories WHERE id = DI.category_id) AS カテゴリ
	, (SELECT site_name FROM m_sites WHERE id = DI.cross_sell_source_site) AS 【クロスセル】元サイト
	, (SELECT genre_name FROM m_genres WHERE id = DI.cross_sell_source_genre) AS 【クロスセル】元ジャンル
	, (SELECT category_name FROM m_categories WHERE id = DI.cross_sell_source_category) AS 【クロスセル】元カテゴリ
	, source_demand_id AS 元案件番号
	, (SELECT user_name FROM m_users WHERE id = receptionist) AS 受付者
	, customer_name AS お客様名
	, (SELECT address1 FROM m_posts WHERE SUBSTRING(jis_cd, 1, 2) = CASE WHEN LENGTH(DI.address1) = 1 THEN '0' || DI.address1 ELSE DI.address1 END GROUP BY address1) AS 都道府県
	, address2 AS 市区町村
	, address3 AS 町域
	, address4 AS 丁目番地
	, building AS 建物名
	, room AS 部屋号数
	, order_no_marriage AS 注文番号※婚活のみ
	, jbr_order_no AS "[JBR様]受付No"
	, case
		WHEN   DI.id < 400000 THEN  DI.jbr_work_contents
		ELSE                        (SELECT item_name FROM m_items WHERE item_category = '[JBR様]作業内容' AND item_id = CAST(CASE WHEN DI.jbr_work_contents = '' THEN '0' ELSE DI.jbr_work_contents END AS integer))
		END  AS "[JBR様]作業内容"
	, case
		WHEN   DI.id < 400000 THEN   DI.jbr_category
		ELSE                        (SELECT item_name FROM m_items WHERE item_category = '[JBR様]カテゴリ' AND item_id = CAST(CASE WHEN DI.jbr_category = '' THEN '0' ELSE DI.jbr_category END AS integer))
		END  AS "[JBR様]カテゴリ"
	, CASE WHEN DI.commission_limitover_time > 0
		THEN (DI.commission_limitover_time / 60)::varchar || '時間' || (DI.commission_limitover_time % 60)::varchar || '分'
		ELSE NULL END AS 取次完了リミット超過時間
	-- 取次情報
	, CI.id AS 取次ID
	, CI.corp_id AS 取次先企業ID
	, (SELECT corp_name FROM m_corps WHERE id = CI.corp_id) AS 取次先企業名
	, (SELECT official_corp_name FROM m_corps WHERE id = CI.corp_id) AS 取次先正式企業名
	, commit_flg AS 確定フラグ
	, commission_type AS 取次種別
	, lost_flg AS 取次前失注フラグ
	, (SELECT user_name FROM m_users WHERE id = CAST(CASE WHEN appointers = '' THEN '0' ELSE appointers END AS integer)) AS 選定者
	, first_commission AS 初取次チェック
	, corp_fee AS 取次先手数料
	, commission_fee_rate AS 取次時手数料率
	, commission_note_send_datetime AS 取次票送信日時
	, (SELECT user_name FROM m_users WHERE id = CAST(CASE WHEN commission_note_sender = '' THEN '0' ELSE commission_note_sender END AS integer)) AS 取次票送信者
	, (SELECT item_name FROM m_items WHERE item_category = '取次状況' AND item_id = commission_status) AS 取次状況
	, (SELECT item_name FROM m_items WHERE item_category = '取次失注理由' AND item_id = commission_order_fail_reason) AS 取次失注理由
	, CI.complete_date AS 施工完了日
	, CI.order_fail_date AS 失注日
	, estimate_price_tax_exclude AS "見積金額(税抜)"
	, construction_price_tax_exclude AS "施工金額(税抜)"
	, construction_price_tax_include AS "施工金額(税込)"
	, deduction_tax_include AS "控除金額(税込)"
	, deduction_tax_exclude AS "控除金額(税抜)"
	, (SELECT irregular_fee_rate FROM bill_infos WHERE demand_id = DI.id AND commission_id = CI.id ORDER BY irregular_fee_rate NULLS LAST LIMIT 1) AS イレギュラー手数料率
	, (SELECT irregular_fee FROM bill_infos WHERE demand_id = DI.id AND commission_id = CI.id ORDER BY irregular_fee_rate NULLS LAST LIMIT 1) AS イレギュラー手数料金額

	, (SELECT comfirmed_fee_rate FROM bill_infos WHERE demand_id = DI.id AND commission_id = CI.id ORDER BY irregular_fee_rate NULLS LAST LIMIT 1) AS 確定手数料率
	, (SELECT fee_target_price FROM bill_infos WHERE demand_id = DI.id AND commission_id = CI.id ORDER BY irregular_fee_rate NULLS LAST LIMIT 1) AS 手数料対象金額
	, (SELECT fee_tax_exclude FROM bill_infos WHERE demand_id = DI.id AND commission_id = CI.id ORDER BY irregular_fee_rate NULLS LAST LIMIT 1) AS "手数料(税抜)"
	, (SELECT tax FROM bill_infos WHERE demand_id = DI.id AND commission_id = CI.id ORDER BY irregular_fee_rate NULLS LAST LIMIT 1) AS 消費税
	, (SELECT insurance_price FROM bill_infos WHERE demand_id = DI.id AND commission_id = CI.id ORDER BY irregular_fee_rate NULLS LAST LIMIT 1) AS 保険料金額
	, (SELECT total_bill_price FROM bill_infos WHERE demand_id = DI.id AND commission_id = CI.id ORDER BY irregular_fee_rate NULLS LAST LIMIT 1) AS 合計請求金額
	, (SELECT fee_billing_date FROM bill_infos WHERE demand_id = DI.id AND commission_id = CI.id ORDER BY irregular_fee_rate NULLS LAST LIMIT 1) AS 手数料請求日

	, unit_price_calc_exclude AS 取次単価対象外
	, report_note AS 報告備考欄
	, reported_flg AS 加盟店報告
	, DI.del_flg AS 案件削除フラグ
	, CI.introduction_not AS 紹介不可
	, CI.introduction_free AS 紹介無料
	, CI.del_flg AS 取次先情報削除フラグ
	,CI.progress_reported AS 進捗表回収
	,CI.progress_report_datetime AS 進捗表回収日時
	,CI.select_commission_unit_price_rank AS 取次単価ランク
	,DI.reservation_demand AS リザベ案件
	,DI.special_measures AS 特別施策
	,DI.riro_kureka AS リロ・クレカ案件
	,DI.selection_system AS 選定方式
	,DI.auction AS 入札流れ案件
	,case
		when DI.priority = 1 then '大至急'
		when DI.priority = 2 then '至急'
		when DI.priority = 3 then '通常'
		else '-'
		end AS "優先度"

FROM demand_infos DI
LEFT OUTER JOIN commission_infos CI
ON CI.demand_id = DI.id
--WHERE receive_datetime BETWEEN '2014/12/01 0:00:00' AND '{$now->format('Y/m/d')} 23:59:59'
WHERE receive_datetime BETWEEN '2015/09/01 0:00:00' AND '{$now->format('Y/m/d')} 23:59:59'
ORDER BY DI.id, CI.id
LIMIT {$limit} OFFSET {$offset}
EOF;
				return $self->AppModel->query($query);
				}, 1000);
				echo "<" . date('Y-m-d H:i:s') . ">案件取次一覧--end--\n";
	}


	const FILE_NAME_MCORPLIST_CURRENT_MONTH = "ORANGE企業一覧(加盟)";

	private function __MCorpOfTheCurrentMonth()
	{
		echo "<" . date('Y-m-d H:i:s') . ">加盟企業一覧月次--start--\n";
		$now = new DateTime();
		$file_name = $now->format('Ymd') . self::FILE_NAME_MCORPLIST_CURRENT_MONTH . ".csv";
		$self = $this;
		$this->__output(self::DIR_BASE . self::DIR_OUT3,
			$file_name,
			function ($limit, $offset) use ($now, $self) {
				$query = <<<EOF
SELECT
	MC.id as 企業ID
	,MC.corp_name as 企業名
	,MG.genre_name as ジャンル
	,(SELECT category_name FROM m_categories WHERE id = MCC.category_id) AS カテゴリ
	,MP.address1 as 対応エリア（都道府県）
	, MCC.order_fee AS 受注手数料
	, CASE MCC.order_fee_unit WHEN 1 THEN '%' WHEN 0 THEN '円' ELSE '' END AS 受注手数料単位
	, CASE WHEN MCC.introduce_fee IS NULL THEN '' ELSE MCC.introduce_fee || '円' END AS 紹介手数料
	, MCC.note AS 紹介手数料備考
	, MC.reg_send_date AS 登録書発送日
	, MC.reg_collect_date AS 登録書回収日
	, (SELECT item_name FROM m_items WHERE item_category = '開拓状況' AND item_id = MC.corp_status) AS 開拓状況
	, MC.commission_dial AS "取次用ダイヤル"
	, MC.tel1 AS "電話番号1"
	, MC.tel2 AS "電話番号2"
	, MC.mobile_tel AS "携帯電話番号"
	, MC.fax AS FAX番号
	,(SELECT item_name FROM m_items MI WHERE item_category = '開拓取次状況' and item_id = corp_commission_status) AS 取次状況
	,CASE WHEN EXISTS(SELECT 0 FROM affiliation_subs WHERE affiliation_id = AI.id AND item_id = MCC.category_id AND item_category = '取次STOPカテゴリ') THEN '取次STOPカテゴリ' ELSE '' END AS 取次STOPカテゴリ
	, (SELECT item_name FROM m_items WHERE item_category = '企業取次形態' AND item_id = MC.corp_commission_type) AS 企業取次形態
	,AAS.commission_unit_price_rank AS 単価ランク
	,AAS.commission_unit_price_category AS 取次単価
	,AAS.commission_count_category AS 取次数
	,AAS.orders_count_category AS 受注数

FROM m_corp_categories MCC
INNER JOIN m_corps MC
 ON MCC.corp_id = MC.id
INNER JOIN m_genres MG
 ON MCC.genre_id = MG.id
LEFT OUTER JOIN
  (SELECT corp_category_id, SUBSTRING(jis_cd, 1, 2) AS jis_cd from m_target_areas MTA_SUB GROUP BY corp_category_id, SUBSTRING(jis_cd, 1, 2)) MTA
 ON MCC.id = MTA.corp_category_id
LEFT OUTER JOIN
  (SELECT SUBSTRING(jis_cd, 1, 2) AS jis_cd, address1 FROM m_posts GROUP BY address1, SUBSTRING(jis_cd, 1, 2)) AS MP
 ON MTA.jis_cd = MP.jis_cd
LEFT OUTER JOIN affiliation_infos AI
 ON MC.id = AI.corp_id
LEFT JOIN affiliation_area_stats AAS
 on AAS.corp_id = MCC.corp_id and AAS.genre_id = MCC.genre_id and cast(AAS.prefecture as integer) = cast(SUBSTRING(MTA.jis_cd, 1, 2) as integer)
WHERE MC.affiliation_status = 1 -- 加盟
AND COALESCE(MC.del_flg, 0) = 0
ORDER BY 企業名 , ジャンル
LIMIT {$limit} OFFSET {$offset}	
;
EOF;
				return $self->AppModel->query($query, false);
			}, 1000);
		echo "<" . date('Y-m-d H:i:s') . ">加盟企業一覧月次--end--\n";
	}

	const FILE_NAME_NOAFFILIATIONMCORP_CURRENT_MONTH = "ORANGE企業一覧(未加盟)";

	private function __NoAffiliationMCorpOfTheCurrentMonth()
	{
		echo "<" . date('Y-m-d H:i:s') . ">未加盟企業一覧月次--start--\n";
		$now = new DateTime();
		$file_name = $now->format('Ymd') . self::FILE_NAME_NOAFFILIATIONMCORP_CURRENT_MONTH . ".csv";
		$self = $this;
		$this->__output(self::DIR_BASE . self::DIR_OUT4,
			$file_name,
			function ($limit, $offset) use ($now, $self) {
				$query = <<<EOF
SELECT
	MC.id AS 企業ID
	,MC.corp_name AS 企業名
	,(SELECT address1 FROM m_address1 WHERE address1_cd = CASE WHEN LENGTH(MC.address1) = 1 THEN '0' || MC.address1 ELSE MC.address1 END) AS 都道府県
	,(SELECT genre_name FROM m_genres WHERE id = MCC.genre_id) AS ジャンル
	,(SELECT category_name FROM m_categories WHERE id = MCC.category_id) AS カテゴリ
	,(SELECT address1 FROM m_address1 WHERE address1_cd = SUBSTRING(MTA.jis_cd, 1, 2)) AS "対応エリア(都道府県)"
	,(SELECT item_name FROM m_items WHERE m_items.item_category = '開拓状況' AND item_id = MC.corp_status) AS 開拓状況
	, MCC.order_fee AS 受注手数料
	, CASE MCC.order_fee_unit WHEN 1 THEN '%' WHEN 0 THEN '円' ELSE '' END AS 受注手数料単位
	, CASE WHEN MCC.introduce_fee IS NULL THEN '' ELSE MCC.introduce_fee || '円' END AS 紹介手数料
	, MC.commission_dial AS "取次用ダイヤル"
	, MC.tel1 AS "電話番号1"
	, MC.tel2 AS "電話番号2"
	, MC.mobile_tel AS "携帯電話番号"
	, MC.fax AS FAX番号
	, (SELECT item_name FROM m_items WHERE item_category = '企業取次形態' AND item_id = MC.corp_commission_type) AS 企業取次形態
FROM m_corps MC
LEFT OUTER JOIN  m_corp_categories MCC
ON MC.id = MCC.corp_id
LEFT OUTER JOIN (SELECT MTA_SUB.corp_category_id, SUBSTRING(MTA_SUB.address1_cd, 1, 2) AS jis_cd from m_target_areas MTA_SUB GROUP BY MTA_SUB.corp_category_id, SUBSTRING(MTA_SUB.address1_cd, 1, 2)) MTA
ON MCC.id = MTA.corp_category_id
WHERE mc.affiliation_status = 0
AND COALESCE(mc.del_flg, 0) = 0
ORDER BY corp_id
LIMIT {$limit} OFFSET {$offset}	
EOF;
				return $self->AppModel->query($query, false);
			}, 1000);
		echo "<" . date('Y-m-d H:i:s') . ">未加盟企業一覧月次--end--\n";
	}

	const FILE_NAME_PROGRESSLIST_CURRENT_MONTH = "ORANGE進捗表用";

	private function __ProgressOfTheCurrentMonth()
	{
		echo "<" . date('Y-m-d H:i:s') . ">進捗表用一覧月次--start--\n";
		$now = new DateTime();
		$file_name = $now->format('Ymd') . self::FILE_NAME_PROGRESSLIST_CURRENT_MONTH . ".csv";
		$self = $this;
		$this->__output(self::DIR_BASE . self::DIR_OUT5,
			$file_name,
			function ($limit, $offset) use ($now, $self) {
				$query = <<<EOF
SELECT
	DISTINCT on (ci.id) ci.id AS 取次id
	, bi.id AS 請求ID
	, di.id AS 案件id
	, mc.id AS 企業ID
	,mc.corp_name AS 取次先企業名
	,mc.official_corp_name AS 正式企業名
	,mc.commission_dial AS 取次用ダイヤル
	,(SELECT item_name FROM m_items WHERE item_category = '進捗表送付方法' AND item_id = mc.prog_send_method) AS 進捗表送付方法
	,mc.prog_send_address AS 進捗表送付先
	,mc.prog_irregular AS 進捗表イレギュラー
	,(SELECT construction_unit_price FROM affiliation_infos WHERE corp_id = mc.id limit 1) AS 施工単価
	,di.receive_datetime AS 受信日時
	,di.customer_name AS お客様名
	,(SELECT site_name FROM m_sites WHERE id = di.site_id) AS サイト名
	,(SELECT category_name FROM m_categories WHERE id = di.category_id) AS カテゴリ
	,CASE ci.commission_type WHEN 0 THEN '通常取次' ELSE '一括見積' END AS 取次種別
	,ci.complete_date AS 施工完了日
	,ci.order_fail_date AS 失注日
	,ci.construction_price_tax_exclude AS 施工金額（税抜）
	,(SELECT item_name FROM m_items WHERE item_category = '取次状況' AND item_id = ci.commission_status) AS 取次状況
	,ci.reported_flg AS 【虚偽なしフラグ】
	,ci.falsity AS 【虚偽報告なし確認済み】
	,bi.fee_billing_date AS 【請求月】
	,ci.introduction_not AS 紹介不可
	,ci.introduction_free AS 紹介無料
	,ci.modified AS 取次管理最終更新日時
	,ci.progress_reported AS 進捗表回収
	,ci.progress_report_datetime AS 進捗表回収日時

FROM demand_infos di
LEFT OUTER JOIN commission_infos ci
on di.id = ci.demand_id
INNER JOIN m_corps mc
ON mc.id = ci.corp_id
LEFT OUTER JOIN bill_infos bi
ON bi.demand_id = di.id and bi.commission_id = ci.id
WHERE di.demand_status IN (4, 5)
AND ci.lost_flg = 0 AND di.del_flg = 0 AND ci.del_flg = 0
LIMIT {$limit} OFFSET {$offset}	
EOF;
				return $self->AppModel->query($query, false);
			}, 1000);
		echo "<" . date('Y-m-d H:i:s') . ">進捗表用一覧月次--end--\n";
	}

	/*
	 *  Directory 
	 */
	private function __existsDirectory($path)
	{
		if (!file_exists($path)) return false;
		if (!is_dir($path)) return false;

		return true;
	}

	/*
	 *  file
	 */
	const ZIP_PASSWORD = "ORANGE9200";
	private function __output($path, $file_name, $callback, $with_limit)
	{
		if (!$this->__existsDirectory($path)) {
			if (self::IFDEBUG) echo "Directory is not found(" . $path . ")\n";
			return false;
		}
		if (!$fp = fopen($path . $file_name, 'w')) {
			if (self::IFDEBUG) echo "file not opened\n";
			return false;
		}
		if (!flock($fp, LOCK_EX)) {
			if (self::IFDEBUG) echo "file lock failed\n";
			fclose($fp);
			return false;
		}

		$header = false;
		$head = array();
		$offset = 0;
		try {
			while (1) {
				$r = $callback($with_limit, $offset);
				foreach ($r as $v) {
					$row = array();
					foreach ($v[0] as $k => $v) {
						if (!$header) $head[] = "\"" . $k . "\"";
						$row[] = "\"" . $v . "\"";
					}
					if (!$header) {
						if (!fwrite($fp, mb_convert_encoding(implode(",", $head) . "\r\n", 'SJIS-win')))
							throw new Exception("failed to write file\n");
						$header = true;
					}
					if (!fwrite($fp, mb_convert_encoding(implode(",", $row) . "\r\n", 'SJIS-win')))
						throw new Exception("failed to write file\n");
				}
				fflush($fp);
				if (count($r) < $with_limit) break;
				$offset += $with_limit;
				echo $offset . "\n";
			}
		} catch (Exception $e) {
			echo $e->getMessage();
		}
		//} finally {
			flock($fp, LOCK_UN);
			fclose($fp);
		//}
		//archihve
		//TODO:パスワード設置 public bool ZipArchive::setPassword ( string $password )  ORANGE9200
		system('cd ' . $path . ';zip -P ' . self::ZIP_PASSWORD . ' "' . $file_name . '".zip "' . $file_name . '"' );
		unlink($path . $file_name);
		//指定フォルダに30日以上前のデータが存在する場合、削除
		$now = new DateTime();
		$now->sub(new DateInterval("P29D"));
		$this->removeBackFile($path, $now->format('Ymd'));
	}
	
	private function removeBackFile($path, $limit) {
		$files = scandir($path);
		
		foreach ($files as $file) {
			if (!is_file($path . $file)) continue;
			if ($limit > substr(basename($file), 0, 8)) 
				unlink($path . $file);
 		}
	}
	
}
