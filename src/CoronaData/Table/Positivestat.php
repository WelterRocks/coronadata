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

class Positivestat extends Base
{
    protected $__is_view = true;
    
    protected $continent_uid = null;
    protected $country_uid = null;
    protected $state_uid = null;
    protected $district_uid = null;
    protected $location_uid = null;
    protected $datasets_total = null;
    protected $days_total = null;
    protected $date_rep_first = null;
    protected $date_rep_last = null;
    protected $cases_total = null;
    protected $deaths_total = null;
    protected $recovered_total = null;
    protected $cases_max = null;
    protected $deaths_max = null;
    protected $recovered_max = null;
    protected $cases_average = null;
    protected $deaths_average = null;
    protected $recovered_average = null;
    protected $new_cases_total = null;
    protected $new_deaths_total = null;
    protected $new_recovered_total = null;
    
    protected function get_install_sql()
    {
      return "CREATE VIEW `positivestat`  AS  
      select 
        `positives`.`continent_uid` AS `continent_uid`,
        `positives`.`country_uid` AS `country_uid`,
        `positives`.`state_uid` AS `state_uid`,
        `positives`.`district_uid` AS `district_uid`,
        `positives`.`location_uid` AS `location_uid`,
        count(`positives`.`uid`) AS `datasets_total`,
        count(distinct `positives`.`date_rep`) AS `days_total`,
        min(`positives`.`date_rep`) AS `date_rep_first`,
        max(`positives`.`date_rep`) AS `date_rep_last`,
        sum(`positives`.`cases`) AS `cases_total`,
        sum(`positives`.`deaths`) AS `deaths_total`,
        sum(`positives`.`recovered`) AS `recovered_total`,
        avg(`positives`.`cases`) AS `cases_average`,
        avg(`positives`.`deaths`) AS `deaths_average`,
        avg(`positives`.`recovered`) AS `recovered_average`,
        max(`positives`.`cases`) AS `cases_max`,
        max(`positives`.`deaths`) AS `deaths_max`,
        max(`positives`.`recovered`) AS `recovered_max`,
        sum(`positives`.`new_cases`) AS `new_cases_total`,
        sum(`positives`.`new_deaths`) AS `new_deaths_total`,
        sum(`positives`.`new_recovered`) AS `new_recovered_total` 
      from `positives` 
      where 
        `positives`.`flag_disabled` = 0 and 
        `positives`.`flag_deleted` = 0 
      group by `positives`.`continent_uid`,
        `positives`.`country_uid`,
        `positives`.`state_uid`,
        `positives`.`district_uid`,
        `positives`.`location_uid` ;";
    }    
}
