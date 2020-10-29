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

class Positives extends Base
{
    protected $country_uid = true;
    protected $state_uid = true;
    protected $district_uid = true;
    protected $foreign_identifier = true;
    protected $timestamp_dataset = null;
    protected $timestamp_reported = null;
    protected $timestamp_referenced = null;
    protected $date_rep = null;
    protected $day_of_week = null;
    protected $day = null;
    protected $month = null;
    protected $year = null;
    protected $age_group_low = null;
    protected $age_group_high = null;
    protected $age_group2_low = null;
    protected $age_group2_high = null;
    protected $gender = null;
    protected $cases = null;
    protected $deaths = null;
    protected $recovered = null;
    protected $new_cases = null;
    protected $new_deaths = null;
    protected $new_recovered = null;
    protected $flag_is_disease_beginning = null;

    protected function get_install_sql()
    {
      return "CREATE TABLE IF NOT EXISTS `positives` (
        `uid` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
        `country_uid` bigint UNSIGNED NOT NULL,
        `state_uid` bigint UNSIGNED NOT NULL,
        `district_uid` bigint UNSIGNED NOT NULL,
        `foreign_identifier` bigint NOT NULL,
        `timestamp_last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        `timestamp_registration` timestamp NOT NULL,
        `timestamp_deleted` timestamp NULL DEFAULT NULL,
        `timestamp_undeleted` timestamp NULL DEFAULT NULL,
        `timestamp_disabled` timestamp NULL DEFAULT NULL,
        `timestamp_enabled` timestamp NULL DEFAULT NULL,
        `timestamp_dataset` timestamp NOT NULL,
        `timestamp_reported` timestamp NOT NULL,
        `timestamp_referenced` timestamp NOT NULL,
        `date_rep` date NOT NULL,
        `day_of_week` tinyint UNSIGNED NOT NULL,
        `day` tinyint UNSIGNED NOT NULL,
        `month` tinyint UNSIGNED NOT NULL,
        `year` year NOT NULL,
        `age_group_low` int NOT NULL DEFAULT '-1',
        `age_group_high` int NOT NULL DEFAULT '-1',
        `age_group2_low` int NOT NULL DEFAULT '-1',
        `age_group2_high` int NOT NULL DEFAULT '-1',
        `gender` enum('male','female','asterisk') NOT NULL DEFAULT 'asterisk',
        `cases` int NOT NULL DEFAULT '0',
        `deaths` int NOT NULL DEFAULT '0',
        `recovered` int NOT NULL DEFAULT '0',
        `new_cases` int NOT NULL DEFAULT '0',
        `new_deaths` int NOT NULL DEFAULT '0',
        `new_recovered` int NOT NULL DEFAULT '0',
        `update_count` int UNSIGNED NOT NULL DEFAULT '0',
        `flag_updated` tinyint(1) NOT NULL DEFAULT '0',
        `flag_disabled` tinyint(1) NOT NULL DEFAULT '0',
        `flag_deleted` tinyint(1) NOT NULL DEFAULT '0',
        `flag_is_disease_beginning` tinyint(1) NOT NULL DEFAULT '0',
        PRIMARY KEY (`uid`),
        UNIQUE KEY `district_foreign_date_rep` (`district_uid`,`foreign_identifier`,`date_rep`),
        KEY `country_uid` (`country_uid`),
        KEY `state_uid` (`state_uid`),
        KEY `district_uid` (`district_uid`),
        KEY `gender` (`gender`),
        KEY `day_of_week` (`day_of_week`),
        KEY `date` (`day`,`month`,`year`),
        KEY `age_group_low` (`age_group_low`),
        KEY `age_group_high` (`age_group_high`),
        KEY `age_group2_low` (`age_group2_low`),
        KEY `age_group2_high` (`age_group2_high`),
        KEY `date_rep` (`date_rep`),
        KEY `foreign_identifier` (`foreign_identifier`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

      ALTER TABLE `positives`
        ADD CONSTRAINT `positive_country_uid` FOREIGN KEY (`country_uid`) REFERENCES `locations` (`uid`) ON DELETE CASCADE ON UPDATE CASCADE,
        ADD CONSTRAINT `positive_district_uid` FOREIGN KEY (`district_uid`) REFERENCES `locations` (`uid`) ON DELETE RESTRICT ON UPDATE RESTRICT,
        ADD CONSTRAINT `positive_state_uid` FOREIGN KEY (`state_uid`) REFERENCES `locations` (`uid`) ON DELETE CASCADE ON UPDATE CASCADE;";
    }    
}
