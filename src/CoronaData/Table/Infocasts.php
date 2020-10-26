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

class Infocasts extends Base
{
    protected $locations_uid = true;
    protected $date_rep = true;    
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
    
    protected function get_install_sql()
    {
      return "CREATE TABLE IF NOT EXISTS `infocasts` (
        `uid` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
        `locations_uid` bigint UNSIGNED NOT NULL,
        `timestamp_last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        `timestamp_registration` timestamp NOT NULL,
        `timestamp_deleted` timestamp NULL DEFAULT NULL,
        `timestamp_undeleted` timestamp NULL DEFAULT NULL,
        `timestamp_disabled` timestamp NULL DEFAULT NULL,
        `timestamp_enabled` timestamp NULL DEFAULT NULL,
        `date_rep` date NOT NULL,
        `new_cases` float NOT NULL DEFAULT '0',
        `new_cases_per_million` float NOT NULL DEFAULT '0',
        `new_cases_smoothed` float NOT NULL DEFAULT '0',
        `new_cases_smoothed_per_million` float NOT NULL DEFAULT '0',
        `new_deaths` float NOT NULL DEFAULT '0',
        `new_deaths_per_million` float NOT NULL DEFAULT '0',
        `new_deaths_smoothed` float NOT NULL DEFAULT '0',
        `new_deaths_smoothed_per_million` float NOT NULL DEFAULT '0',
        `new_tests` float NOT NULL DEFAULT '0',
        `new_tests_per_thousand` float NOT NULL DEFAULT '0',
        `new_tests_smoothed` float NOT NULL DEFAULT '0',
        `new_tests_smoothed_per_thousand` float NOT NULL DEFAULT '0',
        `positive_rate` float NOT NULL DEFAULT '0',
        `stringency_index` float NOT NULL DEFAULT '0',
        `tests_per_case` float NOT NULL DEFAULT '0',
        `tests_units` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '0',
        `total_cases` float NOT NULL DEFAULT '0',
        `total_cases_per_million` float NOT NULL DEFAULT '0',
        `total_deaths` float NOT NULL DEFAULT '0',
        `total_deaths_per_million` float NOT NULL DEFAULT '0',
        `total_tests` float NOT NULL DEFAULT '0',
        `total_tests_per_thousand` float NOT NULL DEFAULT '0',
        `update_count` int UNSIGNED NOT NULL DEFAULT '0',
        `flag_updated` tinyint(1) NOT NULL DEFAULT '0',
        `flag_disabled` tinyint(1) NOT NULL DEFAULT '0',
        `flag_deleted` tinyint(1) NOT NULL DEFAULT '0',
        PRIMARY KEY (`uid`),
        UNIQUE KEY `locations_uid_date_rep` (`locations_uid`,`date_rep`),
        KEY `locations_uid` (`locations_uid`),
        KEY `date_rep` (`date_rep`),
        KEY `tests_units` (`tests_units`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

      ALTER TABLE `infocasts`
        ADD CONSTRAINT `infocasts_locations_uid` FOREIGN KEY (`locations_uid`) REFERENCES `locations` (`uid`) ON DELETE CASCADE ON UPDATE CASCADE;";
    }    
}
