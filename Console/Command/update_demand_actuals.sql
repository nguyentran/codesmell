update demand_actuals
set
	demand_count0 = tmp.demand_count0,
	demand_count1 = tmp.demand_count1,
	demand_count2 = tmp.demand_count2,
	wind_speed_avg = tmp.wind_speed_avg,
	modified = now()
from(
	select
		w.state_id,
		weather_datetime,
		sum(coalesce(demand_count0, 0)) demand_count0,
		sum(coalesce(demand_count1, 0)) demand_count1,
		sum(coalesce(demand_count2, 0)) demand_count2,
		weather_datetime actual_datetime,
		w.wind_speed_avg
	from weathers w
	left join
	(
		select
			count(case
			 when genre_id = 490 then 1
			end) demand_count0,
			count(case
			 when genre_id = 519 then 1
			end) demand_count1,
			count(case
			 when genre_id = 551 then 1
			end) demand_count2,
			lpad(address1, '2', '0') state_id,
			to_timestamp(to_char(receive_datetime, 'YYYY-MM-DD'), 'YYYY-MM-DD') actual_datetime
		from demand_infos
		where
			genre_id in (490,519,551)
			and address1 != '99'
		group by
			to_char(receive_datetime, 'YYYY-MM-DD'),
			address1,
			genre_id
	)a
	on w.state_id = a.state_id
	and w.weather_datetime = a.actual_datetime
	group by
		weather_datetime,
		w.state_id,
		w.wind_speed_avg
	order by
		weather_datetime,
		w.state_id
)tmp
where
	demand_actuals.actual_datetime = $target_date
and demand_actuals.state_id = tmp.state_id
and demand_actuals.actual_datetime = tmp.actual_datetime