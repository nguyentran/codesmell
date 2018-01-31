insert into shell_work_ci
select   ci.id
		,ci.demand_id
		,ci.corp_id
		,di.genre_id
		,di.address1
		,di.source_demand_id
		,ci.lost_flg
		--	ORANGE-144 ADD S iwai
		,CASE ci.commission_status WHEN 3 THEN ci.corp_fee ELSE 0 END AS corp_fee
		--	ORANGE-144 ADD E iwai
		,t1.source_corp_fee
		,di.receive_datetime
		,ci.commission_status
		,ci.unit_price_calc_exclude
		,ci.del_flg
-- 2017.03.22 murata.s ORANGE-374 ADD(S)
		,t2.source_corp_fee AS corp_source_corp_fee
-- 2017.03.22 murata.s ORANGE-374 ADD(E)
  from commission_infos ci
       inner join demand_infos di on ( ci.demand_id = di.id and di.del_flg = 0 )
       inner join m_genres mg on ( di.genre_id = mg.id and mg.valid_flg = 1 )
       left join (select  di2.source_demand_id
					    , sum(ci2.corp_fee) as source_corp_fee
				  from commission_infos ci2
				       inner join demand_infos di2 on ( ci2.demand_id = di2.id and di2.del_flg = 0 )
				       inner join m_genres mg2 on ( di2.genre_id = mg2.id and mg2.valid_flg = 1 )
				 where 1 = 1

				   and (di2.source_demand_id is not null and trim(di2.source_demand_id) <> '')
			  group by di2.source_demand_id
       ) t1 on ( cast(di.id as varchar ) = t1.source_demand_id)
-- 2017.03.22 murata.s ORANGE-374 ADD(S)
		left join (select  di2.source_demand_id
						, sum(ci2.corp_fee) as source_corp_fee
						, corp_id
					from commission_infos ci2
						inner join demand_infos di2 on ( ci2.demand_id = di2.id and di2.del_flg = 0 )
						inner join m_genres mg2 on ( di2.genre_id = mg2.id and mg2.valid_flg = 1 )
					where 1 = 1
					  and ci2.commission_status = 3
					  and (di2.source_demand_id is not null and trim(di2.source_demand_id) <> '')
				group by di2.source_demand_id, corp_id
       ) t2 on ( cast(di.id as varchar ) = t2.source_demand_id) and ci.corp_id = t2.corp_id
-- 2017.03.22 murata.s ORANGE-374 ADD(E)
 where 1 = 1
--   and ci.corp_id = 1
   and ci.unit_price_calc_exclude = 0
   and ci.lost_flg = 0
   and ci.del_flg = 0
   and (di.source_demand_id is null or trim(di.source_demand_id) = '')
   and  cast(di.receive_datetime as date)  >=  (select cast( now() as date)  - cast('12 month' as interval) as date)

union all

select   ci.id
		,ci.demand_id
		,ci.corp_id
		,di.genre_id
		,di.address1
		,di.source_demand_id
		,ci.lost_flg
		--	ORANGE-144 ADD S iwai
		,CASE ci.commission_status WHEN 3 THEN ci.corp_fee ELSE 0 END AS corp_fee
		--	ORANGE-144 ADD E iwai
		,0
		,di.receive_datetime
		,ci.commission_status
		,ci.unit_price_calc_exclude
		,ci.del_flg
-- 2017.03.22 murata.s ORANGE-374 ADD(S)
		,0 AS corp_source_corp_fee
-- 2017.03.22 murata.s ORANGE-374 ADD(E)
      from commission_infos ci
	     inner join demand_infos di on ( ci.demand_id = di.id and di.del_flg = 0 )
		 inner join m_genres mg on ( di.genre_id = mg.id and mg.valid_flg = 1 )

	 where 1 = 1
--	   and corp_id = 1
	   and ci.lost_flg = 0
	   and ci.del_flg = 0
	   and ci.unit_price_calc_exclude = 0
	   and ( di.source_demand_id is not null and trim(di.source_demand_id) <> '' )
	   and  cast(di.receive_datetime as date)  >=  (select cast( now() as date)  - cast('12 month' as interval) as date)

	   and not exists ( select 1 from ( select di.id as di_id from commission_infos ci
										       inner join demand_infos di on ( ci.demand_id = di.id and di.del_flg = 0 )
										       inner join m_genres mg on ( di.genre_id = mg.id and mg.valid_flg = 1 )
                                         where 1 = 1
										 		--	ORANGE-144 ADD S iwai
											   and ci.commission_status = 3
												--	ORANGE-144 ADD E iwai
											   and ci.unit_price_calc_exclude = 0
											   and ci.lost_flg = 0
											   and ci.del_flg = 0
											   and cast(di.receive_datetime as date)  >=  (select cast( now() as date)  - cast('12 month' as interval) as date)
										) t1
                         where cast(t1.di_id as varchar) = di.source_demand_id )

