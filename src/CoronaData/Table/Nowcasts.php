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
    protected $locations_uid = true;
    protected $timestamp_represent = null;
    protected $date_rep = true;
    protected $esteem_new_diseases = null;
    protected $esteem_new_diseases_ma4 = null;
    protected $esteem_reproduction_r = null;
    protected $esteem_7day_r_value = null;
    protected $lower_new_diseases = null;
    protected $lower_new_diseases_ma4 = null;
    protected $lower_reproduction_r = null;
    protected $lower_7day_r_value = null;
    protected $upper_new_diseases = null;
    protected $upper_new_diseases_ma4 = null;
    protected $upper_reproduction_r = null;
    protected $upper_7day_r_value = null;

    protected function get_install_sql()
    {
      return "CREATE TABLE IF NOT EXISTS `nowcasts` (
        `uid` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
        `locations_uid` bigint UNSIGNED NOT NULL,
        `timestamp_last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        `timestamp_registration` timestamp NOT NULL,
        `timestamp_deleted` timestamp NULL DEFAULT NULL,
        `timestamp_undeleted` timestamp NULL DEFAULT NULL,
        `timestamp_disabled` timestamp NULL DEFAULT NULL,
        `timestamp_enabled` timestamp NULL DEFAULT NULL,
        `timestamp_represent` timestamp NOT NULL,
        `date_rep` date NOT NULL,
        `esteem_new_diseases` int NULL DEFAULT NULL,
        `lower_new_diseases` int NULL DEFAULT NULL,
        `upper_new_diseases` int NULL DEFAULT NULL,
        `esteem_new_diseases_ma4` int NULL DEFAULT NULL,
        `lower_new_diseases_ma4` int NULL DEFAULT NULL,
        `upper_new_diseases_ma4` int NULL DEFAULT NULL,
        `esteem_reproduction_r` float NULL DEFAULT NULL,
        `lower_reproduction_r` float NULL DEFAULT NULL,
        `upper_reproduction_r` float NULL DEFAULT NULL,
        `esteem_7day_r_value` float NULL DEFAULT NULL,
        `lower_7day_r_value` float NULL DEFAULT NULL,
        `upper_7day_r_value` float NULL DEFAULT NULL,
        `update_count` int UNSIGNED NOT NULL DEFAULT '0',
        `flag_updated` tinyint(1) NOT NULL DEFAULT '0',
        `flag_disabled` tinyint(1) NOT NULL DEFAULT '0',
        `flag_deleted` tinyint(1) NOT NULL DEFAULT '0',
        PRIMARY KEY (`uid`),
        UNIQUE KEY `locations_uid_date_rep` (`locations_uid`,`date_rep`),
        KEY `locations_uid` (`locations_uid`),
        KEY `timestamp_represent` (`timestamp_represent`),
        KEY `date_rep` (`date_rep`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
      
      ALTER TABLE `nowcasts`
        ADD CONSTRAINT `nowcasts_datacasts_date_rep` FOREIGN KEY (`date_rep`) REFERENCES `datacasts` (`date_rep`) ON DELETE CASCADE ON UPDATE CASCADE,
        ADD CONSTRAINT `nowcasts_locations_uid` FOREIGN KEY (`locations_uid`) REFERENCES `locations` (`uid`) ON DELETE CASCADE ON UPDATE CASCADE;";
    }    
}
