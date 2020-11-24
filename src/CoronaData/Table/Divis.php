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

class Divis extends Base
{
    protected $locations_uid = null;
    protected $reporting_areas = null;
    protected $cases_covid = null;
    protected $cases_covid_ventilated = null;
    protected $locations_count = null;
    protected $beds_free = null;
    protected $beds_occupied = null;
    protected $beds_total = null;
    protected $timestamp_represent = null;
    protected $date_rep = null;
    protected $day_of_week = null;
    protected $day = null;
    protected $month = null;
    protected $year = null;
    protected $geo_id = null;
    protected $district_hash = null;
    protected $state_hash = null;
    protected $country_hash = null;
    protected $continent_hash = null;
    protected $divi_hash = null;

    protected function get_install_sql()
    {
      return "CREATE TABLE IF NOT EXISTS `divis` (
        `uid` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
        `locations_uid` bigint UNSIGNED NULL DEFAULT NULL,
        `timestamp_last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        `timestamp_last_write` timestamp NOT NULL,
        `timestamp_registration` timestamp NOT NULL,
        `timestamp_deleted` timestamp NULL DEFAULT NULL,
        `timestamp_undeleted` timestamp NULL DEFAULT NULL,
        `timestamp_disabled` timestamp NULL DEFAULT NULL,
        `timestamp_enabled` timestamp NULL DEFAULT NULL,
        `timestamp_represent` timestamp NULL DEFAULT NULL,
        `date_rep` DATE NULL DEFAULT NULL,
        `day_of_week` smallint UNSIGNED NOT NULL DEFAULT '0',
        `day` smallint UNSIGNED NOT NULL DEFAULT '0',
        `month` smallint UNSIGNED NOT NULL DEFAULT '0',
        `year` YEAR NOT NULL DEFAULT '0',
        `geo_id` VARCHAR(8) NOT NULL,
        `continent_hash` VARCHAR(32) NOT NULL,
        `country_hash` VARCHAR(32) NOT NULL,
        `state_hash` VARCHAR(32) NOT NULL,
        `district_hash` VARCHAR(32) NOT NULL,
        `divi_hash` VARCHAR(32) NOT NULL,
        `reporting_areas` INT NOT NULL DEFAULT '0',
        `cases_covid` INT NOT NULL DEFAULT '0',
        `cases_covid_ventilated` INT NOT NULL DEFAULT '0',
        `locations_count` INT NOT NULL DEFAULT '0',
        `beds_free` INT NOT NULL DEFAULT '0',
        `beds_occupied` INT NOT NULL DEFAULT '0',
        `beds_total` INT NOT NULL DEFAULT '0',
        `data_checksum` VARCHAR(40) NOT NULL,
        `update_count` int UNSIGNED NOT NULL DEFAULT '0',
        `flag_updated` tinyint(1) NOT NULL DEFAULT '0',
        `flag_disabled` tinyint(1) NOT NULL DEFAULT '0',
        `flag_deleted` tinyint(1) NOT NULL DEFAULT '0',
        PRIMARY KEY (`uid`,`month`),
        UNIQUE KEY `divi_hash` (`divi_hash`,`month`),
        KEY `data_checksum` (`data_checksum`),
        KEY `date_rep` (`date_rep`),
        KEY `locations_uid` (`locations_uid`),
        KEY `district_hash` (`district_hash`),
        KEY `state_hash` (`state_hash`),
        KEY `country_hash` (`country_hash`),
        KEY `continent_hash` (`continent_hash`)
      ) PARTITION BY RANGE ( `month` ) (
        PARTITION jan VALUES LESS THAN (2),
        PARTITION feb VALUES LESS THAN (3),
        PARTITION mar VALUES LESS THAN (4),
        PARTITION apr VALUES LESS THAN (5),
        PARTITION may VALUES LESS THAN (6),
        PARTITION jun VALUES LESS THAN (7),
        PARTITION jul VALUES LESS THAN (8),
        PARTITION aug VALUES LESS THAN (9),
        PARTITION sep VALUES LESS THAN (10),
        PARTITION oct VALUES LESS THAN (11),
        PARTITION nov VALUES LESS THAN (12),
        PARTITION `dec` VALUES LESS THAN MAXVALUE
      );
      
      ALTER TABLE `divis` ADD CONSTRAINT `divis_locations_uid` FOREIGN KEY (`locations_uid`) REFERENCES `locations`(`uid`) ON DELETE CASCADE ON UPDATE CASCADE;
      ";
    }
}
