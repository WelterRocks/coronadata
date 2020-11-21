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


class Locations extends Base
{
    protected $timestamp_min = null;
    protected $timestamp_max = null;      
    protected $continent_id = null;
    protected $continent_hash = null;
    protected $continent_name = null;
    protected $country_id = null;
    protected $country_hash = null;
    protected $country_name = null;
    protected $state_id = null;
    protected $state_hash = null;
    protected $state_name = null;
    protected $district_id = null;
    protected $district_hash = null;
    protected $district_name = null;
    protected $district_type = null;
    protected $district_fullname = null;
    protected $location_id = null;
    protected $location_hash = null;
    protected $location_name = null;
    protected $location_type = null;
    protected $geo_id = null;
    protected $population_year = null;
    protected $population_count = null;
    protected $population_females = null;
    protected $population_males = null;
    protected $area = null;
    protected $deaths_count = null;
    protected $deaths_min = null;
    protected $deaths_max = null;
    protected $cases_count = null;
    protected $cases_min = null;
    protected $cases_max = null;
    protected $recovered_count = null;
    protected $recovered_min = null;
    protected $recovered_max = null;
    protected $divi_reporting_areas = null;
    protected $divi_locations_count = null;
    protected $divi_beds_free = null;
    protected $divi_beds_occupied = null;
    protected $divi_beds_total = null;
    protected $divi_cases_covid = null;
    protected $divi_cases_covid_ventilated = null;
    protected $aged_65_older = null;
    protected $aged_70_older = null;
    protected $cardiovasc_death_rate = null;
    protected $diabetes_prevalence = null;
    protected $gdp_per_capita = null;
    protected $handwashing_facilities = null;
    protected $hospital_beds_per_thousand = null;
    protected $human_development_index = null;
    protected $life_expectancy = null;
    protected $median_age = null;
    protected $contamination_total = null;
    protected $contamination_rundays = null;
    protected $contamination_per_day = null;
    protected $contamination_target = null;
    protected $population_density = null;
    protected $infection_density = null;
    protected $infection_probability = null;
    protected $infection_area = null;
    protected $new_cases = null;
    protected $new_cases_per_million = null;
    protected $new_cases_smoothed = null;
    protected $new_cases_smoothed_per_million = null;
    protected $new_deaths = null;
    protected $new_deaths_per_million = null;
    protected $new_deaths_smoothed = null;
    protected $new_deaths_smoothed_per_million = null;
    protected $new_recovered = null;
    protected $new_recovered_per_million = null;
    protected $new_recovered_smoothed = null;
    protected $new_recovered_smoothed_per_million = null;
    protected $total_cases = null;
    protected $total_cases_per_million = null;
    protected $total_deaths = null;
    protected $total_deaths_per_million = null;
    protected $total_recovered = null;
    protected $total_recovered_per_million = null;
    protected $female_smokers = null;
    protected $male_smokers = null;
    protected $extreme_poverty = null;
    protected $new_tests = null;
    protected $new_tests_per_thousand = null;
    protected $new_tests_smoothed = null;
    protected $new_tests_smoothed_per_thousand = null;
    protected $positive_rate = null;
    protected $reproduction_rate = null;
    protected $tests_per_case = null;
    protected $tests_units = null;
    protected $total_tests = null;
    protected $total_tests_per_thousand = null;
    protected $stringency_index = null;
    protected $esteem_new_diseases = null;
    protected $lower_new_diseases = null;
    protected $upper_new_diseases = null;
    protected $esteem_new_diseases_ma4 = null;
    protected $lower_new_diseases_ma4 = null;
    protected $upper_new_diseases_ma4 = null;
    protected $esteem_reproduction_r = null;
    protected $lower_reproduction_r = null;
    protected $upper_reproduction_r = null;
    protected $esteem_7day_r_value = null;
    protected $lower_7day_r_value = null;
    protected $upper_7day_r_value = null;
    protected $day = null;
    protected $month = null;
    protected $day_of_week = null;
    protected $year = null;
    protected $date_nowcast = null;
    
    protected function get_install_sql()
    {
      return "CREATE TABLE IF NOT EXISTS `locations` (
        `uid` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
        `timestamp_last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        `timestamp_registration` timestamp NOT NULL,
        `timestamp_deleted` timestamp NULL DEFAULT NULL,
        `timestamp_undeleted` timestamp NULL DEFAULT NULL,
        `timestamp_disabled` timestamp NULL DEFAULT NULL,
        `timestamp_enabled` timestamp NULL DEFAULT NULL,
        `timestamp_min` bigint unsigned NOT NULL DEFAULT '0',
        `timestamp_max` bigint unsigned NOT NULL DEFAULT '0',
        `location_type` ENUM('continent','country','state','district','location') NOT NULL DEFAULT 'location',
        `day_of_week` smallint UNSIGNED NOT NULL DEFAULT '0',
        `day` smallint UNSIGNED NOT NULL DEFAULT '0',
        `month` smallint UNSIGNED NOT NULL DEFAULT '0',
        `year` YEAR NOT NULL DEFAULT '0',
        `date_nowcast` DATE NULL DEFAULT NULL,
        `esteem_new_diseases` INT NOT NULL DEFAULT '0',
        `lower_new_diseases` INT NOT NULL DEFAULT '0',
        `upper_new_diseases` INT NOT NULL DEFAULT '0',
        `esteem_new_diseases_ma4` INT NOT NULL DEFAULT '0',
        `lower_new_diseases_ma4` INT NOT NULL DEFAULT '0',
        `upper_new_diseases_ma4` INT NOT NULL DEFAULT '0',
        `esteem_reproduction_r` FLOAT NOT NULL DEFAULT '0',
        `lower_reproduction_r` FLOAT NOT NULL DEFAULT '0',
        `upper_reproduction_r` FLOAT NOT NULL DEFAULT '0',
        `esteem_7day_r_value` FLOAT NOT NULL DEFAULT '0',
        `lower_7day_r_value` FLOAT NOT NULL DEFAULT '0',
        `upper_7day_r_value` FLOAT NOT NULL DEFAULT '0',
        `divi_reporting_areas` INT NOT NULL DEFAULT '0',
        `divi_locations_count` INT NOT NULL DEFAULT '0',
        `divi_cases_covid` INT NOT NULL DEFAULT '0',
        `divi_cases_covid_ventilated` INT NOT NULL DEFAULT '0',
        `divi_beds_free` INT NOT NULL DEFAULT '0',
        `divi_beds_occupied` INT NOT NULL DEFAULT '0',
        `divi_beds_total` INT NOT NULL DEFAULT '0',
        `aged_65_older` INT NOT NULL DEFAULT '0',
        `aged_70_older` INT NOT NULL DEFAULT '0',
        `cardiovasc_death_rate` FLOAT NOT NULL DEFAULT '0',
        `diabetes_prevalence` FLOAT NOT NULL DEFAULT '0',
        `gdp_per_capita` FLOAT NOT NULL DEFAULT '0',
        `handwashing_facilities` FLOAT NOT NULL DEFAULT '0',
        `hospital_beds_per_thousand` FLOAT NOT NULL DEFAULT '0',
        `human_development_index` FLOAT NOT NULL DEFAULT '0',
        `life_expectancy` FLOAT NOT NULL DEFAULT '0',
        `median_age` FLOAT NOT NULL DEFAULT '0',
        `new_cases` BIGINT NOT NULL DEFAULT '0',
        `new_cases_per_million` FLOAT NOT NULL DEFAULT '0',
        `new_cases_smoothed` BIGINT NOT NULL DEFAULT '0',
        `new_cases_smoothed_per_million` FLOAT NOT NULL DEFAULT '0',
        `new_deaths` BIGINT NOT NULL DEFAULT '0',
        `new_deaths_per_million` FLOAT NOT NULL DEFAULT '0',
        `new_deaths_smoothed` BIGINT NOT NULL DEFAULT '0',
        `new_deaths_smoothed_per_million` FLOAT NOT NULL DEFAULT '0',
        `new_recovered` BIGINT NOT NULL DEFAULT '0',
        `new_recovered_per_million` FLOAT NOT NULL DEFAULT '0',
        `new_recovered_smoothed` BIGINT NOT NULL DEFAULT '0',
        `new_recovered_smoothed_per_million` FLOAT NOT NULL DEFAULT '0',
        `total_cases` BIGINT NOT NULL DEFAULT '0',
        `total_cases_per_million` FLOAT NOT NULL DEFAULT '0',
        `total_deaths` BIGINT NOT NULL DEFAULT '0',
        `total_deaths_per_million` FLOAT NOT NULL DEFAULT '0',
        `total_recovered` BIGINT NOT NULL DEFAULT '0',
        `total_recovered_per_million` FLOAT NOT NULL DEFAULT '0',
        `female_smokers` INT NOT NULL DEFAULT '0',
        `male_smokers` INT NOT NULL DEFAULT '0',
        `extreme_poverty` INT NOT NULL DEFAULT '0',
        `new_tests` INT NOT NULL DEFAULT '0',
        `new_tests_per_thousand` FLOAT NOT NULL DEFAULT '0',
        `new_tests_smoothed` INT NOT NULL DEFAULT '0',
        `new_tests_smoothed_per_thousand` FLOAT NOT NULL DEFAULT '0',
        `positive_rate` FLOAT NOT NULL DEFAULT '0',
        `reproduction_rate` FLOAT NOT NULL DEFAULT '0',
        `tests_per_case` FLOAT NOT NULL DEFAULT '0',
        `tests_units` VARCHAR(16) NULL DEFAULT NULL,
        `total_tests` INT NOT NULL DEFAULT '0',
        `total_tests_per_thousand` FLOAT NOT NULL DEFAULT '0',
        `stringency_index` FLOAT NOT NULL DEFAULT '0',        
        `continent_id` VARCHAR(16) NULL DEFAULT NULL,
        `continent_hash` VARCHAR(32) NULL DEFAULT NULL,
        `continent_name` VARCHAR(64) NULL DEFAULT NULL,
        `country_id` VARCHAR(16) NULL DEFAULT NULL,
        `country_hash` VARCHAR(32) NULL DEFAULT NULL,
        `country_name` VARCHAR(64) NULL DEFAULT NULL,
        `state_id` VARCHAR(16) NULL DEFAULT NULL,
        `state_hash` VARCHAR(32) NULL DEFAULT NULL,
        `state_name` VARCHAR(64) NULL DEFAULT NULL,
        `district_id` VARCHAR(16) NULL DEFAULT NULL,
        `district_hash` VARCHAR(32) NULL DEFAULT NULL,
        `district_name` VARCHAR(64) NULL DEFAULT NULL,
        `district_type` VARCHAR(64) NULL DEFAULT NULL,
        `district_fullname` VARCHAR(64) NULL DEFAULT NULL,
        `location_id` VARCHAR(16) NULL DEFAULT NULL,
        `location_hash` VARCHAR(32) NULL DEFAULT NULL,
        `location_name` VARCHAR(64) NULL DEFAULT NULL,
        `geo_id` VARCHAR(16) NULL DEFAULT NULL,
        `contamination_total` FLOAT NOT NULL DEFAULT '0',
        `contamination_rundays` FLOAT NOT NULL DEFAULT '0',
        `contamination_per_day` FLOAT NOT NULL DEFAULT '0',
        `contamination_target` FLOAT NOT NULL DEFAULT '0',
        `population_year` YEAR NULL DEFAULT NULL,
        `population_count` BIGINT UNSIGNED NULL DEFAULT '0',
        `population_females` BIGINT UNSIGNED NULL DEFAULT '0',
        `population_males` BIGINT UNSIGNED NULL DEFAULT '0',
        `population_density` FLOAT NOT NULL DEFAULT '0',
        `infection_density` FLOAT NOT NULL DEFAULT '0',
        `infection_probability` FLOAT NOT NULL DEFAULT '0',
        `infection_area` FLOAT NOT NULL DEFAULT '0',        
        `area` FLOAT DEFAULT NULL,
        `deaths_count` bigint NOT NULL DEFAULT '0',
        `deaths_min` bigint NOT NULL DEFAULT '0',
        `deaths_max` bigint NOT NULL DEFAULT '0',
        `cases_count` bigint NOT NULL DEFAULT '0',
        `cases_min` bigint NOT NULL DEFAULT '0',
        `cases_max` bigint NOT NULL DEFAULT '0',        
        `recovered_count` bigint NOT NULL DEFAULT '0',
        `recovered_min` bigint NOT NULL DEFAULT '0',
        `recovered_max` bigint NOT NULL DEFAULT '0',        
        `update_count` int UNSIGNED NOT NULL DEFAULT '0',
        `flag_updated` tinyint(1) NOT NULL DEFAULT '0',
        `flag_disabled` tinyint(1) NOT NULL DEFAULT '0',
        `flag_deleted` tinyint(1) NOT NULL DEFAULT '0',
        PRIMARY KEY (`uid`),
        UNIQUE KEY `location_hash` (`location_hash`),
        KEY `location_type` (`location_type`),
        KEY `geo_id` (`geo_id`),
        KEY `continent_name` (`continent_name`),
        KEY `country_name` (`country_name`),
        KEY `state_name` (`state_name`),
        KEY `district_name` (`district_name`),
        KEY `location_name` (`location_name`),
        KEY `district_hash` (`district_hash`),
        KEY `state_hash` (`state_hash`),
        KEY `country_hash` (`country_hash`),
        KEY `continent_hash` (`continent_hash`)        
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
      ";
    }
}
