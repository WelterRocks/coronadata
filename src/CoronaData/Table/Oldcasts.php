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

class Oldcasts extends Base
{
    protected $locations_uid = null;
    protected $location_type = null;
    protected $continent_hash = null;
    protected $country_hash = null;
    protected $state_hash = null;
    protected $district_hash = null;
    protected $location_hash = null;
    protected $timestamp_represent = null;
    protected $date_rep = null;
    protected $day = null;
    protected $month = null;
    protected $day_of_week = null;
    protected $year = null;
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
    protected $flag_casted_r_values = null;
    
    protected function get_install_sql()
    {
      return "CREATE TABLE IF NOT EXISTS `oldcasts` (
        `uid` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
        `locations_uid` bigint UNSIGNED NULL DEFAULT '0',
        `location_type` ENUM('continent','country','state','district','location') NOT NULL DEFAULT 'location',
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
        `continent_hash` VARCHAR(32) NOT NULL,
        `country_hash` VARCHAR(32) NOT NULL,
        `state_hash` VARCHAR(32) NULL DEFAULT NULL,
        `district_hash` VARCHAR(32) NULL DEFAULT NULL,
        `location_hash` VARCHAR(32) NULL DEFAULT NULL,
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
        `data_checksum` VARCHAR(40) NULL DEFAULT NULL,
        `update_count` int UNSIGNED NOT NULL DEFAULT '0',
        `flag_updated` tinyint(1) NOT NULL DEFAULT '0',
        `flag_disabled` tinyint(1) NOT NULL DEFAULT '0',
        `flag_deleted` tinyint(1) NOT NULL DEFAULT '0',
        `flag_casted_r_values` tinyint(1) NOT NULL DEFAULT '0',
        PRIMARY KEY (`uid`,`month`),
        UNIQUE KEY `data_checksum` (`data_checksum`,`month`),
        KEY `locations_uid` (`locations_uid`),
        KEY `location_type` (`location_type`),
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
      ";
    }
}
