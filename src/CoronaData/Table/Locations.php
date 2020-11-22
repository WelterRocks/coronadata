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
    protected $population_density = null;
    protected $area = null;
    
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
        `location_type` ENUM('continent','country','state','district','location') NOT NULL DEFAULT 'location',
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
        `population_year` YEAR NULL DEFAULT NULL,
        `population_count` BIGINT UNSIGNED NULL DEFAULT '0',
        `population_females` BIGINT UNSIGNED NULL DEFAULT '0',
        `population_males` BIGINT UNSIGNED NULL DEFAULT '0',
        `population_density` FLOAT NOT NULL DEFAULT '0',
        `area` FLOAT DEFAULT NULL,
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
