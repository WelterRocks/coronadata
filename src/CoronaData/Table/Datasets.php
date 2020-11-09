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

class Datasets extends Base
{
    protected $dataset_hash = null;
    protected $location_hash = null;
    protected $district_hash = null;
    protected $state_hash = null;
    protected $country_hash = null;
    protected $continent_hash = null;
    protected $day_of_week = null;
    protected $day = null;
    protected $month = null;
    protected $year = null;
    protected $cases = null;
    protected $deaths = null;
    protected $recovered = null;
    protected $timestamp_represent = null;
    protected $hosp_patients = null;
    protected $hosp_patients_per_million = null;
    protected $icu_patients = null;
    protected $icu_patients_per_million = null;
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
    protected $weekly_hosp_admissions = null;
    protected $weekly_hosp_admissions_per_million = null;
    protected $weekly_icu_admissions = null;
    protected $weekly_icu_admissions_per_million = null;
    protected $incidence_7day = null;
    protected $incidence_14day = null;
    protected $incidence_7day_smoothed = null;
    protected $incidence_14day_smoothed = null;
    protected $exponence_7day = null;
    protected $exponence_14day = null;
    protected $exponence_7day_smoothed = null;
    protected $exponence_14day_smoothed = null;

    protected function get_install_sql()
    {
      return "CREATE TABLE IF NOT EXISTS `datasets` (
        `uid` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
        `timestamp_last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        `timestamp_registration` timestamp NOT NULL,
        `timestamp_deleted` timestamp NULL DEFAULT NULL,
        `timestamp_undeleted` timestamp NULL DEFAULT NULL,
        `timestamp_disabled` timestamp NULL DEFAULT NULL,
        `timestamp_enabled` timestamp NULL DEFAULT NULL,
        `timestamp_represent` timestamp NULL DEFAULT NULL,
        `dataset_hash` VARCHAR(32) NULL DEFAULT NULL,
        `continent_hash` VARCHAR(32) NULL DEFAULT NULL,
        `country_hash` VARCHAR(32) NULL DEFAULT NULL,
        `state_hash` VARCHAR(32) NULL DEFAULT NULL,
        `district_hash` VARCHAR(32) NULL DEFAULT NULL,
        `location_hash` VARCHAR(32) NULL DEFAULT NULL,
        `day_of_week` smallint UNSIGNED NOT NULL DEFAULT '0',
        `day` smallint UNSIGNED NOT NULL DEFAULT '0',
        `month` smallint UNSIGNED NOT NULL DEFAULT '0',
        `year` YEAR NOT NULL DEFAULT '0',
        `cases` INT NOT NULL DEFAULT '0',
        `deaths` INT NOT NULL DEFAULT '0',
        `recovered` INT NOT NULL DEFAULT '0',
        `hosp_patients` INT NOT NULL DEFAULT '0',
        `hosp_patients_per_million` FLOAT NOT NULL DEFAULT '0',
        `icu_patients` INT NOT NULL DEFAULT '0',
        `icu_patients_per_million` FLOAT NOT NULL DEFAULT '0',
        `new_cases` INT NOT NULL DEFAULT '0',
        `new_cases_per_million` FLOAT NOT NULL DEFAULT '0',
        `new_cases_smoothed` INT NOT NULL DEFAULT '0',
        `new_cases_smoothed_per_million` FLOAT NOT NULL DEFAULT '0',
        `new_deaths` INT NOT NULL DEFAULT '0',
        `new_deaths_per_million` FLOAT NOT NULL DEFAULT '0',
        `new_deaths_smoothed` INT NOT NULL DEFAULT '0',
        `new_deaths_smoothed_per_million` FLOAT NOT NULL DEFAULT '0',
        `new_recovered` INT NOT NULL DEFAULT '0',
        `new_recovered_per_million` FLOAT NOT NULL DEFAULT '0',
        `new_recovered_smoothed` INT NOT NULL DEFAULT '0',
        `new_recovered_smoothed_per_million` FLOAT NOT NULL DEFAULT '0',
        `new_tests` INT NOT NULL DEFAULT '0',
        `new_tests_per_thousand` FLOAT NOT NULL DEFAULT '0',
        `new_tests_smoothed` INT NOT NULL DEFAULT '0',
        `new_tests_smoothed_per_thousand` FLOAT NOT NULL DEFAULT '0',
        `positive_rate` FLOAT NOT NULL DEFAULT '0',
        `stringency_index` FLOAT NOT NULL DEFAULT '0',
        `tests_per_case` FLOAT NOT NULL DEFAULT '0',
        `tests_units` VARCHAR(32) DEFAULT NULL,
        `total_cases` INT NOT NULL DEFAULT '0',
        `total_cases_per_million` FLOAT NOT NULL DEFAULT '0',
        `total_deaths` INT NOT NULL DEFAULT '0',
        `total_deaths_per_million` FLOAT NOT NULL DEFAULT '0',
        `total_tests` INT NOT NULL DEFAULT '0',
        `total_tests_per_thousand` FLOAT NOT NULL DEFAULT '0',
        `weekly_hosp_admissions` INT NOT NULL DEFAULT '0',
        `weekly_hosp_admissions_per_million` FLOAT NOT NULL DEFAULT '0',
        `weekly_icu_admissions` INT NOT NULL DEFAULT '0',
        `weekly_icu_admissions_per_million` FLOAT NOT NULL DEFAULT '0',
        `incidence_7day` FLOAT NOT NULL DEFAULT '0',
        `incidence_14day` FLOAT NOT NULL DEFAULT '0',
        `incidence_7day_smoothed` FLOAT NOT NULL DEFAULT '0',
        `incidence_14day_smoothed` FLOAT NOT NULL DEFAULT '0',
        `exponence_7day` FLOAT NOT NULL DEFAULT '0',
        `exponence_14day` FLOAT NOT NULL DEFAULT '0',
        `exponence_7day_smoothed` FLOAT NOT NULL DEFAULT '0',
        `exponence_14day_smoothed` FLOAT NOT NULL DEFAULT '0',        
        `update_count` int UNSIGNED NOT NULL DEFAULT '0',
        `flag_updated` tinyint(1) NOT NULL DEFAULT '0',
        `flag_disabled` tinyint(1) NOT NULL DEFAULT '0',
        `flag_deleted` tinyint(1) NOT NULL DEFAULT '0',
        PRIMARY KEY (`uid`),
        UNIQUE KEY `dataset_hash` (`dataset_hash`),
        KEY `continent_hash` (`continent_hash`),
        KEY `country_hash` (`country_hash`),
        KEY `state_hash` (`state_hash`),
        KEY `district_hash` (`district_hash`),
        KEY `location_hash` (`location_hash`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    }
}
