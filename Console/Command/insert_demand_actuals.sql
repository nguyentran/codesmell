insert into
	demand_actuals
	(
		state_id,
		region_id,
		actual_datetime,
		genre0,
		genre1,
		genre2,
		demand_count0,
		demand_count1,
		demand_count2,
		wind_speed_avg,
		created,
		modified
	)
select
	w.state_id,
	case
	 when w.state_id in ('01') then 1
	 when w.state_id in ('02','03','04','05','06','07') then 2
	 when w.state_id in ('08','09','10','11','12','13','14') then 3
	 when w.state_id in ('15','16','17','18','19','20','21','22','23') then 4
	 when w.state_id in ('24','25','26','27','28','29','30') then 5
	 when w.state_id in ('31','32','33','34','35') then 6
	 when w.state_id in ('36','37','38','39') then 7
	 when w.state_id in ('40','41','42','43','44','45','46') then 8
	 when w.state_id in ('47') then 9
	end region_id,
	weather_datetime,
	490 category0,
	519 category1,
	551 category2,
	sum(coalesce(demand_count0, 0)),
	sum(coalesce(demand_count1, 0)),
	sum(coalesce(demand_count2, 0)),
	w.wind_speed_avg,
	now(),
	now()

from
	weathers w
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
	and receive_datetime < now()
group by
	to_char(receive_datetime, 'YYYY-MM-DD'),
	address1,
	genre_id
)a
on w.state_id = a.state_id
and w.weather_datetime = a.actual_datetime
where (weather_datetime > (select max(COALESCE(actual_datetime, '1900-01-01'))  from demand_actuals) or (select max(COALESCE(actual_datetime, '1900-01-01'))  from demand_actuals) is null)
group by
	weather_datetime,
	w.state_id,
	w.wind_speed_avg
order by
	weather_datetime,
	w.state_id