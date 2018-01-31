-- 取次手数料算出期限内データ(shell_work_ci)より
-- 企業別ジャンルごとかつエリアごと 施工単価算出 総手数料額/取次数
insert into shell_work_result
-- murata.s ORANGE-374 CHG(S)
select ci.corp_id
	, di.genre_id
	, di.address1
	, count(ci.*) as cnt
	, sum(COALESCE(ci.corp_fee,0) + COALESCE(ci.corp_source_corp_fee,0)) as corp_fee
	, sum(COALESCE(ci.corp_fee,0) + COALESCE(ci.corp_source_corp_fee,0)) / count(ci.*) as unit_price
	, now()
	, case when (select max(commission_count_category) from affiliation_area_stats where corp_id = ci.corp_id and genre_id = di.genre_id and prefecture = di.address1)  <= 5
		then 'z'
	       when (max(mg.targer_commission_unit_price) IS NULL) OR (max(mg.targer_commission_unit_price) = 0)
		then 'a'
	       when cast(sum(COALESCE(ci.corp_fee,0) + COALESCE(ci.corp_source_corp_fee,0)) / count(ci.*)  as numeric(10, 3)) / cast( max(mg.targer_commission_unit_price) as numeric(10,3)) * 100 >= 100
		then 'a'
	       when cast(sum(COALESCE(ci.corp_fee,0) + COALESCE(ci.corp_source_corp_fee,0)) / count(ci.*)  as numeric(10, 3)) / cast( max(mg.targer_commission_unit_price) as numeric(10,3)) * 100 >= 80
		then 'b'
	       when cast(sum(COALESCE(ci.corp_fee,0) + COALESCE(ci.corp_source_corp_fee,0)) / count(ci.*)  as numeric(10, 3)) / cast( max(mg.targer_commission_unit_price) as numeric(10,3)) * 100 >= 65
		then 'c'
	       else 'd'
	end as unit_price_rank
-- murata.s ORANGE-374 CHG(E)
from shell_work_ci ci
	inner join demand_infos di on ( ci.demand_id = di.id and di.del_flg = 0 )
	inner join m_genres mg on ( di.genre_id = mg.id and mg.valid_flg = 1 )
where 1 = 1 
group by ci.corp_id, di.genre_id, di.address1
