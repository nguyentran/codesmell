insert into shell_work_ci
with t_date as (    -- 企業別ジャンルごとかつエリアごとの、さかのぼった場合とそうでない場合の期限取得
	select t2.corp_id
	     , t2.genre_id
	     , t2.new_genre_flg
	     , t2.address1
		 , sum(t2.complete_cnt1)         as complete_cnt1
		 , sum(t2.complete_cnt2)         as complete_cnt2
		 , sum(t2.complete_cnt3)         as complete_cnt3
		 , sum(t2.commission_cnt1)       as commission_cnt1
		 , sum(t2.commission_cnt2)       as commission_cnt2
		 , sum(t2.commission_cnt3)       as commission_cnt3
		 , case t2.new_genre_flg 
		        when 1 then ( case when ( sum(t2.complete_cnt1) >= 10 and sum(t2.commission_cnt1) >= 50 )  then ( cast( date_trunc('month', now() )  - cast(' 2 month' as interval) as date) )
		                           when ( sum(t2.complete_cnt2) >= 10 and sum(t2.commission_cnt2) >= 50 )  then ( cast( date_trunc('month', now() )  - cast(' 6 month' as interval) as date) )
				                   when ( sum(t2.complete_cnt3) >= 10 and sum(t2.commission_cnt3) >= 50 )  then ( cast( date_trunc('month', now() )  - cast('12 month' as interval) as date) )
				                   else cast('2000/01/01' as date) end )
				else        ( case when ( sum(t2.complete_cnt1) >= 30 and sum(t2.commission_cnt1) >= 100 ) then ( cast( date_trunc('month', now() )  - cast(' 2 month' as interval) as date) )
		                           when ( sum(t2.complete_cnt2) >= 30 and sum(t2.commission_cnt2) >= 100 ) then ( cast( date_trunc('month', now() )  - cast(' 6 month' as interval) as date) )
				                   when ( sum(t2.complete_cnt3) >= 30 and sum(t2.commission_cnt3) >= 100 ) then ( cast( date_trunc('month', now() )  - cast('12 month' as interval) as date) )
				                   else cast('2000/01/01' as date) end )
		   end as date_limit
	from 
		(
		    -- 施工完了で期限(2ヶ月)
			select ci.corp_id
			     , di.genre_id
			     , mg.new_genre_flg
			     , di.address1
			     , count(*)                            as complete_cnt1
				 , 0                                   as complete_cnt2
				 , 0                                   as complete_cnt3
				 , 0                                   as complete_cnt4
				 , 0                                   as commission_cnt1
				 , 0                                   as commission_cnt2
				 , 0                                   as commission_cnt3
				 , 0                                   as commission_cnt4
			  from commission_infos ci
			       inner join demand_infos di on ( ci.demand_id = di.id and di.del_flg = 0 )
			       inner join m_genres mg on ( di.genre_id = mg.id and mg.valid_flg = 1 )
			 where 1 = 1 
			   and ci.commission_status = 3
			   and ci.unit_price_calc_exclude = 0
			   and ci.lost_flg = 0
			   and ci.del_flg = 0
			   and di.demand_status <> 6
			   and ci.construction_price_tax_exclude <> 0 and ci.construction_price_tax_exclude is not null
			   and ci.corp_fee <> 0                       and ci.corp_fee is not null
			   and cast(ci.complete_date as date)  <= ( select cast( date_trunc('month', now()) as date) - 1 ) 
			   and cast(ci.complete_date as date)  >= ( select cast( date_trunc('month', now() )  - cast('2 month' as interval) as date) ) 
			   and cast(ci.commission_note_send_datetime as date)  <= ( select cast( date_trunc('month', now()) as date) - 1 ) 
			   and cast(ci.commission_note_send_datetime as date)  >= ( select cast( date_trunc('month', now() )  - cast('2 month' as interval) as date) ) 
			group by ci.corp_id, di.genre_id, mg.new_genre_flg, di.address1

		union all

		    -- 施工完了で期限（6ヶ月）
			select ci.corp_id
			     , di.genre_id
			     , mg.new_genre_flg
			     , di.address1
			     , 0                                   as complete_cnt1
				 , count(*)                            as complete_cnt2
				 , 0                                   as complete_cnt3
				 , 0                                   as complete_cnt4
				 , 0                                   as commission_cnt1
				 , 0                                   as commission_cnt2
				 , 0                                   as commission_cnt3
				 , 0                                   as commission_cnt4
			  from commission_infos ci
			       inner join demand_infos di on ( ci.demand_id = di.id and di.del_flg = 0 )
			       inner join m_genres mg on ( di.genre_id = mg.id and mg.valid_flg = 1 )
			 where 1 = 1 
			   and ci.commission_status = 3
			   and ci.unit_price_calc_exclude = 0
			   and ci.lost_flg = 0
			   and ci.del_flg = 0
			   and di.demand_status <> 6
			   and ci.construction_price_tax_exclude <> 0 and ci.construction_price_tax_exclude is not null
			   and ci.corp_fee <> 0                       and ci.corp_fee is not null
			   and cast(ci.complete_date as date)  <= ( select cast( date_trunc('month', now()) as date) - 1 ) 
			   and cast(ci.complete_date as date)  >= ( select cast( date_trunc('month', now() )  - cast('6 month' as interval) as date) ) 
			   and cast(ci.commission_note_send_datetime as date)  <= ( select cast( date_trunc('month', now()) as date) - 1 ) 
			   and cast(ci.commission_note_send_datetime as date)  >= ( select cast( date_trunc('month', now() )  - cast('6 month' as interval) as date) ) 
			group by ci.corp_id, di.genre_id, mg.new_genre_flg, di.address1

		union all

		    -- 施工完了で期限（12ヶ月）
			select ci.corp_id
			     , di.genre_id
			     , mg.new_genre_flg
			     , di.address1
			     , 0                                    as complete_cnt1
				 , 0                                    as complete_cnt2
				 , count(*)                             as complete_cnt3
				 , 0                                    as complete_cnt4
				 , 0                                    as commission_cnt1
				 , 0                                    as commission_cnt2
				 , 0                                    as commission_cnt3
				 , 0                                    as commission_cnt4
			  from commission_infos ci
			       inner join demand_infos di on ( ci.demand_id = di.id and di.del_flg = 0 )
			       inner join m_genres mg on ( di.genre_id = mg.id and mg.valid_flg = 1 )
			 where 1 = 1 
			   and ci.commission_status = 3
			   and ci.unit_price_calc_exclude = 0
			   and ci.lost_flg = 0
			   and ci.del_flg = 0
			   and di.demand_status <> 6
			   and ci.construction_price_tax_exclude <> 0 and ci.construction_price_tax_exclude is not null
			   and ci.corp_fee <> 0                       and ci.corp_fee is not null
			   and cast(ci.complete_date as date)  <= ( select cast( date_trunc('month', now()) as date) - 1 ) 
			   and cast(ci.complete_date as date)  >= ( select cast( date_trunc('month', now() )  - cast('12 month' as interval) as date) ) 
			   and cast(ci.commission_note_send_datetime as date)  <= ( select cast( date_trunc('month', now()) as date) - 1 ) 
			   and cast(ci.commission_note_send_datetime as date)  >= ( select cast( date_trunc('month', now() )  - cast('12 month' as interval) as date) ) 
			group by ci.corp_id, di.genre_id, mg.new_genre_flg, di.address1

		union all

		    -- 施工完了で期限（無期限）
			select ci.corp_id
			     , di.genre_id
			     , mg.new_genre_flg
			     , di.address1
			     , 0                                    as complete_cnt1
				 , 0                                    as complete_cnt2
				 , 0                                    as complete_cnt3
				 , count(*)                             as complete_cnt4
				 , 0                                    as commission_cnt1
				 , 0                                    as commission_cnt2
				 , 0                                    as commission_cnt3
				 , 0                                    as commission_cnt4
			  from commission_infos ci
			       inner join demand_infos di on ( ci.demand_id = di.id and di.del_flg = 0 )
			       inner join m_genres mg on ( di.genre_id = mg.id and mg.valid_flg = 1 )
			 where 1 = 1 
			   and ci.commission_status = 3
			   and ci.unit_price_calc_exclude = 0
			   and ci.lost_flg = 0
			   and ci.del_flg = 0
			   and di.demand_status <> 6
			   and ci.construction_price_tax_exclude <> 0 and ci.construction_price_tax_exclude is not null
			   and ci.corp_fee <> 0                       and ci.corp_fee is not null
			   and cast(ci.complete_date as date)  <= ( select cast( date_trunc('month', now()) as date) - 1 ) 
			   

			group by ci.corp_id, di.genre_id, mg.new_genre_flg, di.address1

union all  -- 以降、取次件数取得

		    -- 取次件数-期限（2ヶ月）
			select ci.corp_id
			     , di.genre_id
			     , mg.new_genre_flg
			     , di.address1
			     , 0                                   as complete_cnt1
				 , 0                                   as complete_cnt2
				 , 0                                   as complete_cnt3
				 , 0                                   as complete_cnt4
				 , count(*)                            as commission_cnt1
				 , 0                                   as commission_cnt2
				 , 0                                   as commission_cnt3
				 , 0                                   as commission_cnt4
			  from commission_infos ci
			       inner join demand_infos di on ( ci.demand_id = di.id and di.del_flg = 0 )
			       inner join m_genres mg on ( di.genre_id = mg.id and mg.valid_flg = 1 )
			 where 1 = 1 
			   and ci.unit_price_calc_exclude = 0
			   and ci.lost_flg = 0
			   and ci.del_flg = 0
			   and di.demand_status <> 6
			   and cast(ci.commission_note_send_datetime as date)  <= ( select cast( date_trunc('month', now()) as date) - 1 ) 
			   and cast(ci.commission_note_send_datetime as date)  >= ( select cast( date_trunc('month', now() )  - cast('2 month' as interval) as date) ) 
			group by ci.corp_id, di.genre_id, mg.new_genre_flg, di.address1

		union all

		    -- 取次件数-期限（6ヶ月）
			select ci.corp_id
			     , di.genre_id
			     , mg.new_genre_flg
			     , di.address1
			     , 0                                   as complete_cnt1
				 , 0                                   as complete_cnt2
				 , 0                                   as complete_cnt3
				 , 0                                   as complete_cnt4
				 , 0                                   as commission_cnt1
				 , count(*)                            as commission_cnt2
				 , 0                                   as commission_cnt3
				 , 0                                   as commission_cnt4
			  from commission_infos ci
			       inner join demand_infos di on ( ci.demand_id = di.id and di.del_flg = 0 )
			       inner join m_genres mg on ( di.genre_id = mg.id and mg.valid_flg = 1 )
			 where 1 = 1 
			   and ci.unit_price_calc_exclude = 0
			   and ci.lost_flg = 0
			   and ci.del_flg = 0
			   and di.demand_status <> 6
			   and cast(ci.commission_note_send_datetime as date)  <= ( select cast( date_trunc('month', now()) as date) - 1 ) 
			   and cast(ci.commission_note_send_datetime as date)  >= ( select cast( date_trunc('month', now() )  - cast('6 month' as interval) as date) ) 
			group by ci.corp_id, di.genre_id, mg.new_genre_flg, di.address1

		union all

		    -- 取次件数-期限（12ヶ月）
			select ci.corp_id
			     , di.genre_id
			     , mg.new_genre_flg
			     , di.address1
			     , 0                                   as complete_cnt1
				 , 0                                   as complete_cnt2
				 , 0                                   as complete_cnt3
				 , 0                                   as complete_cnt4
				 , 0                                   as commission_cnt1
				 , 0                                   as commission_cnt2
				 , count(*)                            as commission_cnt3
				 , 0                                   as commission_cnt4
			  from commission_infos ci
			       inner join demand_infos di on ( ci.demand_id = di.id and di.del_flg = 0 )
			       inner join m_genres mg on ( di.genre_id = mg.id and mg.valid_flg = 1 )
			 where 1 = 1 
			   and ci.unit_price_calc_exclude = 0
			   and ci.lost_flg = 0
			   and ci.del_flg = 0
			   and di.demand_status <> 6
			   and cast(ci.commission_note_send_datetime as date)  <= ( select cast( date_trunc('month', now()) as date) - 1 ) 
			   and cast(ci.commission_note_send_datetime as date)  >= ( select cast( date_trunc('month', now() )  - cast('12 month' as interval) as date) ) 
			group by ci.corp_id, di.genre_id, mg.new_genre_flg, di.address1

		union all

		    -- 取次件数-期限（無期限）
			select ci.corp_id
			     , di.genre_id
			     , mg.new_genre_flg
			     , di.address1
			     , 0                                   as complete_cnt1
				 , 0                                   as complete_cnt2
				 , 0                                   as complete_cnt3
				 , 0                                   as complete_cnt4
				 , 0                                   as commission_cnt1
				 , 0                                   as commission_cnt2
				 , 0                                   as commission_cnt3
				 , count(*)                            as commission_cnt4
			  from commission_infos ci
			       inner join demand_infos di on ( ci.demand_id = di.id and di.del_flg = 0 )
			       inner join m_genres mg on ( di.genre_id = mg.id and mg.valid_flg = 1 )
			 where 1 = 1 
			   and ci.unit_price_calc_exclude = 0
			   and ci.lost_flg = 0
			   and ci.del_flg = 0
			   and di.demand_status <> 6
			   and cast(ci.commission_note_send_datetime as date)  <= ( select cast( date_trunc('month', now()) as date) - 1 ) 

			group by ci.corp_id, di.genre_id, mg.new_genre_flg, di.address1

	) t2
	group by t2.corp_id, t2.genre_id, t2.new_genre_flg, t2.address1
)

select   ci.id
		,ci.demand_id
		,ci.corp_id
		,ci.commit_flg
		,ci.commission_type
		,ci.lost_flg
		,ci.appointers
		,ci.first_commission
		,ci.corp_fee
		,ci.waste_collect_oath
		,ci.attention
		,ci.commission_dial
		,ci.tel_commission_datetime
		,ci.tel_commission_person
		,ci.commission_fee_rate
		,ci.commission_note_send_datetime
		,ci.commission_note_sender
		,ci.commission_status
		,ci.commission_order_fail_reason
		,ci.complete_date
		,ci.order_fail_date
		,ci.estimate_price_tax_exclude
		,ci.construction_price_tax_exclude
		,ci.construction_price_tax_include
		,ci.deduction_tax_include
		,ci.deduction_tax_exclude
		,ci.confirmd_fee_rate
		,ci.unit_price_calc_exclude
		,ci.report_note
		,ci.modified_user_id
		,ci.modified
		,ci.created_user_id
		,ci.created
		,ci.del_flg
		,ci.checked_flg
		,ci.reported_flg
		,ci.irregular_fee_rate
		,ci.irregular_fee
		,ci.falsity
		,ci.follow_date
		,ci.introduction_not
		,ci.lock_status
		,ci.commission_status_last_updated
		,ci.progress_reported
		,ci.progress_report_datetime
		,ci.introduction_free
		,td.date_limit
  from commission_infos ci
       inner join demand_infos di on ( ci.demand_id = di.id and di.del_flg = 0 )
       inner join m_genres mg on ( di.genre_id = mg.id and mg.valid_flg = 1 )
	   left  join t_date td on ( ci.corp_id = td.corp_id and td.genre_id = di.genre_id and td.address1 = di.address1 )
 where 1 = 1 
   and ci.unit_price_calc_exclude = 0
   and ci.lost_flg = 0
   and ci.del_flg = 0
   and ( case ci.commission_status
              when 1 then ci.commission_note_send_datetime is not null
			  when 2 then di.order_date              is not null and di.order_date <> '' 
			  when 3 then ci.complete_date           is not null and ci.complete_date <> '' 
			  when 4 then ci.order_fail_date         is not null and ci.order_fail_date <> '' 
			  else true
         end )
         
   and ( case ci.commission_status
--              when 1 then ( cast(ci.commission_note_send_datetime as date)  <= ( cast( date_trunc('month', now()) as date) - 1) and cast(ci.commission_note_send_datetime as date) >= cast(td.date_limit as date) )
			  when 2 then ( cast(di.order_date as date)               <= ( cast( date_trunc('month', now()) as date) - 1) and cast(di.order_date              as date) >= cast(td.date_limit as date) )
			  when 3 then ( cast(ci.complete_date as date)            <= ( cast( date_trunc('month', now()) as date) - 1) and cast(ci.complete_date           as date) >= cast(td.date_limit as date) )
			  when 4 then ( cast(ci.order_fail_date as date)          <= ( cast( date_trunc('month', now()) as date) - 1) and cast(ci.order_fail_date         as date) >= cast(td.date_limit as date) )
			  else true
         end )
   and  cast(ci.commission_note_send_datetime as date)  <=  cast( date_trunc('month', now()) as date) - 1
   and  cast(ci.commission_note_send_datetime as date)  >=  cast(td.date_limit as date) 

;