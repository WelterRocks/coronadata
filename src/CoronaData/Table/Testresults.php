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

class Testresults extends Base
{
    protected $locations_uid = null;
    protected $location_type = null;
    protected $result_hash = null;
    protected $location_hash = null;
    protected $district_hash = null;
    protected $state_hash = null;
    protected $country_hash = null;
    protected $continent_hash = null;
    protected $foreign_identifier = null;
    protected $age_group_lower = null;
    protected $age_group_upper = null;
    protected $age_group2_lower = null;
    protected $age_group2_upper = null;
    protected $gender = null;
    protected $year = null;
    protected $month = null;
    protected $day = null;
    protected $day_of_week = null;
    protected $cases_count = null;
    protected $cases_new = null;
    protected $deaths_count = null;
    protected $deaths_new = null;
    protected $recovered_count = null;
    protected $recovered_new = null;
    protected $timestamp_represent = null;
    protected $timestamp_reported = null;
    protected $timestamp_dataset = null;
    protected $timestamp_referenced = null;
    protected $date_rep = null;
    protected $flag_is_disease_beginning = null;

    protected function get_install_sql()
    {
      return "CREATE TABLE IF NOT EXISTS `testresults` (
        `uid` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
        `locations_uid` bigint UNSIGNED NULL DEFAULT '0',
        `location_type` ENUM('continent','country','state','district','location') NOT NULL DEFAULT 'location',
        `foreign_identifier` BIGINT NOT NULL,
        `timestamp_last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        `timestamp_registration` timestamp NOT NULL,
        `timestamp_deleted` timestamp NULL DEFAULT NULL,
        `timestamp_undeleted` timestamp NULL DEFAULT NULL,
        `timestamp_disabled` timestamp NULL DEFAULT NULL,
        `timestamp_enabled` timestamp NULL DEFAULT NULL,
        `timestamp_represent` timestamp NULL DEFAULT NULL,
        `timestamp_reported` timestamp NULL DEFAULT NULL,
        `timestamp_dataset` timestamp NULL DEFAULT NULL,
        `timestamp_referenced` timestamp NULL DEFAULT NULL,
        `date_rep` DATE NULL DEFAULT NULL,
        `result_hash` VARCHAR(32) NULL DEFAULT NULL,
        `continent_hash` VARCHAR(32) NULL DEFAULT NULL,
        `country_hash` VARCHAR(32) NULL DEFAULT NULL,
        `state_hash` VARCHAR(32) NULL DEFAULT NULL,
        `district_hash` VARCHAR(32) NULL DEFAULT NULL,
        `location_hash` VARCHAR(32) NULL DEFAULT NULL,
        `day_of_week` smallint UNSIGNED NOT NULL DEFAULT '0',
        `day` smallint UNSIGNED NOT NULL DEFAULT '0',
        `month` smallint UNSIGNED NOT NULL DEFAULT '0',
        `year` YEAR NOT NULL DEFAULT '0',
        `gender` ENUM('male','female','asterisk') NOT NULL DEFAULT 'asterisk',
        `age_group_lower` INT NOT NULL DEFAULT '-1',
        `age_group_upper` INT NOT NULL DEFAULT '-1',
        `age_group2_lower` INT NOT NULL DEFAULT '-1',
        `age_group2_upper` INT NOT NULL DEFAULT '-1',
        `cases_count` INT NOT NULL DEFAULT '0',
        `cases_new` INT NOT NULL DEFAULT '0',
        `deaths_count` INT NOT NULL DEFAULT '0',
        `deaths_new` INT NOT NULL DEFAULT '0',
        `recovered_count` INT NOT NULL DEFAULT '0',
        `recovered_new` INT NOT NULL DEFAULT '0',
        `update_count` int UNSIGNED NOT NULL DEFAULT '0',
        `flag_updated` tinyint(1) NOT NULL DEFAULT '0',
        `flag_disabled` tinyint(1) NOT NULL DEFAULT '0',
        `flag_deleted` tinyint(1) NOT NULL DEFAULT '0',
        `flag_is_disease_beginning` tinyint(1) NOT NULL DEFAULT '0',
        PRIMARY KEY (`uid`),
        UNIQUE KEY `location_unique_identifier` (`foreign_identifier`),
        UNIQUE KEY `result_hash` (`result_hash`),
        KEY `locations_uid` (`locations_uid`),
        KEY `location_type` (`location_type`),
        KEY `location_hash` (`location_hash`),
        KEY `district_hash` (`district_hash`),
        KEY `state_hash` (`state_hash`),
        KEY `country_hash` (`country_hash`),
        KEY `continent_hash` (`continent_hash`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

      ALTER TABLE `testresults` ADD CONSTRAINT `testresult_location` FOREIGN KEY (`locations_uid`) REFERENCES `locations`(`uid`) ON DELETE CASCADE ON UPDATE CASCADE;
      ";
    }
}
