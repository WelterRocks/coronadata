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

class Nowcasts extends Base
{
    protected $continent_hash = null;
    protected $country_hash = null;
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
    
    protected function get_install_sql()
    {
      return "CREATE TABLE IF NOT EXISTS `nowcasts` (
        `uid` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
        `timestamp_last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
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
        `update_count` int UNSIGNED NOT NULL DEFAULT '0',
        `flag_updated` tinyint(1) NOT NULL DEFAULT '0',
        `flag_disabled` tinyint(1) NOT NULL DEFAULT '0',
        `flag_deleted` tinyint(1) NOT NULL DEFAULT '0',
        PRIMARY KEY (`uid`),
        UNIQUE KEY `location_date_rep` (`continent_hash`,`country_hash`,`date_rep`),
        KEY `country_hash` (`country_hash`),
        KEY `continent_hash` (`continent_hash`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
      ";
      /*
      ALTER TABLE `nowcasts` ADD CONSTRAINT `nowcast_location_continent` FOREIGN KEY (`continent_hash`) REFERENCES `locations`(`continent_hash`) ON DELETE CASCADE ON UPDATE CASCADE;
      ALTER TABLE `nowcasts` ADD  CONSTRAINT `nowcast_location_country` FOREIGN KEY (`country_hash`) REFERENCES `locations`(`country_hash`) ON DELETE CASCADE ON UPDATE CASCADE;
      ";
      */
    }
}
