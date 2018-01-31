-- 取次手数料算出期限内データ(shell_work_ci)より
-- 企業別ジャンルごとかつエリアごと 施工単価算出 総手数料額/取次数
insert into shell_work_result
				select ci.corp_id
				     , di.genre_id
				     , ''
					 , count(ci.*) as cnt
					 -- murata.s ORANGE-374 CHG(S)
					 , sum(COALESCE(ci.corp_fee,0) + COALESCE(ci.corp_source_corp_fee,0)) as corp_fee
					 , sum(COALESCE(ci.corp_fee,0) + COALESCE(ci.corp_source_corp_fee,0)) / count(ci.*) as unit_price
					 -- murata.s ORANGE-374 CHG(E)
					 , now()
				  from shell_work_ci ci
					   inner join demand_infos di on ( ci.demand_id = di.id and di.del_flg = 0 )
					   inner join m_genres mg on ( di.genre_id = mg.id and mg.valid_flg = 1 )
				 where 1 = 1 


				 group by ci.corp_id, di.genre_id