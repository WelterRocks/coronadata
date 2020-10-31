<?php namespace WelterRocks\CoronaData\Table;

/******************************************************************************

    CoronaData is a php class to analyse the worldwide corona situation
    Copyright (C) 2020  Oliver Welter  <oliver@welter.rocks>

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <https://www.gnu.org/licenses/>.

*******************************************************************************/

use WelterRocks\CoronaData\Exception;
use WelterRocks\CoronaData\Table\Base;

class Combined extends Base
{
    protected $__is_view = true;
    
    protected $uid = null;
    protected $locations_uid = null;
    protected $timestamp_represent = null;
    protected $date_rep = null;
    protected $day_of_week = null;
    protected $day = null;
    protected $month = null;
    protected $year = null;
    protected $cases = null;
    protected $deaths = null;
    protected $cases_ascension = null;
    protected $deaths_ascension = null;
    protected $cases_7day = null;
    protected $deaths_7day = null;
    protected $cases_14day = null;
    protected $deaths_14day = null;
    protected $cases_pointer = null;
    protected $deaths_pointer = null;
    protected $cases_rate = null;
    protected $deaths_rate = null;
    protected $population_used = null;
    protected $reproduction_4day = null;
    protected $reproduction_7day = null;
    protected $reproduction_14day = null;
    protected $exponence_1day = null;
    protected $exponence_7day = null;
    protected $exponence_14day = null;
    protected $incidence_7day = null;
    protected $incidence_14day = null;
    protected $incidence_14day_given = null;
    protected $condition_7day = null;
    protected $condition_14day = null;
    protected $alert_condition = null;
    protected $new_cases = null;
    protected $new_cases_per_million = null;
    protected $new_cases_smoothed = null;
    protected $new_cases_smoothed_per_million = null;
    protected $new_deaths = null;
    protected $new_deaths_per_million = null;
    protected $new_deaths_smoothed = null;
    protected $new_deaths_smoothed_per_million = null;
    protected $new_tests = null;
    protected $new_tests_per_thousand = null;
    protected $new_tests_smoothed = null;
    protected $new_tests_smoothed_per_thousand = null;
    protected $positive_rate = null;
    protected $stringency_index = null;
    protected $tests_per_case = null;
    protected $tests_units = null;
    protected $total_cases = null;
    protected $total_cases_per_million = null;
    protected $total_deaths = null;
    protected $total_deaths_per_million = null;
    protected $total_tests = null;
    protected $total_tests_per_thousand = null;
    protected $timestamp_virus_free = null;
    protected $timestamp_virus_back = null;
    protected $timestamp_data_complete = null;
    protected $parent_uid = null;
    protected $geo_id = null;
    protected $country_code = null;
    protected $continent = null;
    protected $country = null;
    protected $location = null;
    protected $population = null;
    protected $population_year = null;
    protected $population_density = null;
    protected $population_expected = null;
    protected $population_expected_worldwide = null;
    protected $population_expected_share = null;
    protected $median_age = null;
    protected $aged_65_older = null;
    protected $aged_70_older = null;
    protected $life_expectancy = null;
    protected $human_development_index = null;
    protected $gdp_per_capita = null;
    protected $handwashing_facilities = null;
    protected $hospital_beds_per_thousand = null;
    protected $male_smokers = null;
    protected $female_smokers = null;
    protected $cardiovasc_death_rate = null;
    protected $diabetes_prevalence = null;
    protected $extreme_poverty = null;
    protected $hosp_patients = null;
    protected $hosp_patients_per_million = null;
    protected $icu_patients = null;
    protected $icu_patients_per_million = null;
    protected $weekly_hosp_admissions = null;
    protected $weekly_hosp_admissions_per_million = null;
    protected $weekly_icu_admissions = null;
    protected $weekly_icu_admissions_per_million = null;
    protected $infection_density = null;
    protected $average_cases_per_day = null;
    protected $average_cases_per_week = null;
    protected $average_cases_per_month = null;
    protected $average_cases_per_year = null;
    protected $average_deaths_per_day = null;
    protected $average_deaths_per_week = null;
    protected $average_deaths_per_month = null;
    protected $average_deaths_per_year = null;
    protected $average_recovered_per_day = null;
    protected $average_recovered_per_week = null;
    protected $average_recovered_per_month = null;
    protected $average_recovered_per_year = null;
    protected $contamination_runtime = null;
    protected $contamination_value = null;
    protected $contamination_target = null;
    protected $flag_data_incomplete = null;
    protected $flag_no_longer_updated = null;
    protected $flag_virus_free = null;

    protected function get_install_sql()
    {
      return "CREATE VIEW `combined`  AS  
      select `datacasts`.`uid` AS `uid`,
      `datacasts`.`locations_uid` AS `locations_uid`,
      `datacasts`.`timestamp_represent` AS `timestamp_represent`,
      `datacasts`.`date_rep` AS `date_rep`,      
      `datacasts`.`day_of_week` AS `day_of_week`,
      `datacasts`.`day` AS `day`,
      `datacasts`.`month` AS `month`,
      `datacasts`.`year` AS `year`,
      `datacasts`.`cases` AS `cases`,
      `datacasts`.`deaths` AS `deaths`,
      `datacasts`.`cases_ascension` AS `cases_ascension`,
      `datacasts`.`deaths_ascension` AS `deaths_ascension`,
      `datacasts`.`cases_7day` AS `cases_7day`,
      `datacasts`.`deaths_7day` AS `deaths_7day`,
      `datacasts`.`cases_14day` AS `cases_14day`,
      `datacasts`.`deaths_14day` AS `deaths_14day`,
      `datacasts`.`cases_pointer` AS `cases_pointer`,
      `datacasts`.`deaths_pointer` AS `deaths_pointer`,
      `datacasts`.`cases_rate` AS `cases_rate`,
      `datacasts`.`deaths_rate` AS `deaths_rate`,
      `datacasts`.`population_used` AS `population_used`,
      `datacasts`.`reproduction_4day` AS `reproduction_4day`,
      `datacasts`.`reproduction_7day` AS `reproduction_7day`,
      `datacasts`.`reproduction_14day` AS `reproduction_14day`,
      `datacasts`.`exponence_1day` AS `exponence_1day`,
      `datacasts`.`exponence_7day` AS `exponence_7day`,
      `datacasts`.`exponence_14day` AS `exponence_14day`,
      `datacasts`.`incidence_7day` AS `incidence_7day`,
      `datacasts`.`incidence_14day` AS `incidence_14day`,
      `datacasts`.`incidence_14day_given` AS `incidence_14day_given`,
      `datacasts`.`condition_7day` AS `condition_7day`,
      `datacasts`.`condition_14day` AS `condition_14day`,
      `datacasts`.`alert_condition` AS `alert_condition`,
      `infocasts`.`new_cases` AS `new_cases`,
      `infocasts`.`new_cases_per_million` AS `new_cases_per_million`,
      `infocasts`.`uid` AS `infocasts_uid`,
      `infocasts`.`new_cases_smoothed` AS `new_cases_smoothed`,
      `infocasts`.`new_cases_smoothed_per_million` AS `new_cases_smoothed_per_million`,
      `infocasts`.`new_deaths` AS `new_deaths`,
      `infocasts`.`new_deaths_per_million` AS `new_deaths_per_million`,
      `infocasts`.`new_deaths_smoothed` AS `new_deaths_smoothed`,
      `infocasts`.`new_deaths_smoothed_per_million` AS `new_deaths_smoothed_per_million`,
      `infocasts`.`new_tests` AS `new_tests`,
      `infocasts`.`new_tests_per_thousand` AS `new_tests_per_thousand`,
      `infocasts`.`new_tests_smoothed` AS `new_tests_smoothed`,
      `infocasts`.`new_tests_smoothed_per_thousand` AS `new_tests_smoothed_per_thousand`,
      `infocasts`.`positive_rate` AS `positive_rate`,
      `infocasts`.`stringency_index` AS `stringency_index`,
      `infocasts`.`tests_per_case` AS `tests_per_case`,
      `infocasts`.`tests_units` AS `tests_units`,
      `infocasts`.`total_cases` AS `total_cases`,
      `infocasts`.`total_cases_per_million` AS `total_cases_per_million`,
      `infocasts`.`total_deaths` AS `total_deaths`,
      `infocasts`.`total_deaths_per_million` AS `total_deaths_per_million`,
      `infocasts`.`total_tests` AS `total_tests`,
      `infocasts`.`total_tests_per_thousand` AS `total_tests_per_thousand`,
      `infocasts`.`hosp_patients` AS `hosp_patients`,
      `infocasts`.`hosp_patients_per_million` AS `hosp_patients_per_million`,
      `infocasts`.`icu_patients` AS `icu_patients`,
      `infocasts`.`icu_patients_per_million` AS `icu_patients_per_million`,
      `infocasts`.`weekly_hosp_admissions` AS `weekly_hosp_admissions`,
      `infocasts`.`weekly_hosp_admissions_per_million` AS `weekly_hosp_admissions_per_million`,
      `infocasts`.`weekly_icu_admissions` AS `weekly_icu_admissions`,
      `infocasts`.`weekly_icu_admissions_per_million` AS `weekly_icu_admissions_per_million`,
      `locations`.`timestamp_virus_free` AS `timestamp_virus_free`,
      `locations`.`timestamp_virus_back` AS `timestamp_virus_back`,
      `locations`.`timestamp_data_complete` AS `timestamp_data_complete`,
      `locations`.`parent_uid` AS `parent_uid`,
      `locations`.`geo_id` AS `geo_id`,
      `locations`.`country_code` AS `country_code`,
      `locations`.`continent` AS `continent`,
      `locations`.`country` AS `country`,
      `locations`.`location` AS `location`,
      `locations`.`population` AS `population`,
      `locations`.`population_year` AS `population_year`,
      `locations`.`population_density` AS `population_density`,
      `locations`.`population_expected` AS `population_expected`,
      `locations`.`population_expected_worldwide` AS `population_expected_worldwide`,
      `locations`.`population_expected_share` AS `population_expected_share`,
      `locations`.`median_age` AS `median_age`,
      `locations`.`aged_65_older` AS `aged_65_older`,
      `locations`.`aged_70_older` AS `aged_70_older`,
      `locations`.`life_expectancy` AS `life_expectancy`,
      `locations`.`human_development_index` AS `human_development_index`,
      `locations`.`gdp_per_capita` AS `gdp_per_capita`,
      `locations`.`handwashing_facilities` AS `handwashing_facilities`,
      `locations`.`hospital_beds_per_thousand` AS `hospital_beds_per_thousand`,
      `locations`.`male_smokers` AS `male_smokers`,
      `locations`.`female_smokers` AS `female_smokers`,
      `locations`.`cardiovasc_death_rate` AS `cardiovasc_death_rate`,
      `locations`.`diabetes_prevalence` AS `diabetes_prevalence`,
      `locations`.`extreme_poverty` AS `extreme_poverty`,
      `locations`.`infection_density`,
      `locations`.`average_cases_per_day`,
      `locations`.`average_cases_per_week`,
      `locations`.`average_cases_per_month`,
      `locations`.`average_cases_per_year`,
      `locations`.`average_deaths_per_day`,
      `locations`.`average_deaths_per_week`,
      `locations`.`average_deaths_per_month`,
      `locations`.`average_deaths_per_year`,
      `locations`.`average_recovered_per_day`,
      `locations`.`average_recovered_per_week`,
      `locations`.`average_recovered_per_month`,
      `locations`.`average_recovered_per_year`,
      `locations`.`contamination_runtime`,
      `locations`.`contamination_value`,
      `locations`.`contamination_target`,
      `locations`.`flag_data_incomplete` AS `flag_data_incomplete`,
      `locations`.`flag_no_longer_updated` AS `flag_no_longer_updated`,
      `locations`.`flag_virus_free` AS `flag_virus_free` 
      from (
        (`datacasts` left join `locations` on ((`datacasts`.`locations_uid` = `locations`.`uid`))) 
        left join `infocasts` on (
          ((`datacasts`.`locations_uid` = `infocasts`.`locations_uid`) and (`datacasts`.`date_rep` = `infocasts`.`date_rep`))
        )
      ) 
      where (
        (`locations`.`flag_disabled` = 0) and 
        (`datacasts`.`flag_disabled` = 0) and 
        (`infocasts`.`flag_disabled` = 0) and 
        (`datacasts`.`flag_deleted` = 0) and 
        (`locations`.`flag_deleted` = 0) and 
        (`infocasts`.`flag_deleted` = 0)
      ) ;";
    }    
}
