alter table t_0 rename "ESDC Wage Rate High
2021" to esdc_wage_rate_high_2021;
alter table t_0 rename "ESDC Wage Rate Low
2021" to esdc_wage_rate_low_2021;
alter table t_0 rename "ESDC Wage Rate Median
2021" to esdc_wage_rate_median_2021;
alter table t_0 rename "NOC" to noc;
alter table t_0 rename "Occupation Title" to occupation_title;
alter table t_0 rename "Source Information" to source_information;
-- WARNING! There's a space following "Calculated Median Annual Salary ".
-- Make sure your text editor does not trim spaces from line endings.
alter table t_0 rename "Calculated Median Annual Salary 
2021" to calculated_median_annual_salary_2021;
create unique index noc_index on t_0(noc);
alter table t_0 rename to wages_2021;
