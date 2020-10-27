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
use WelterRocks\CoronaData\Table\Locations;

class Datacasts extends Base
{
    protected $locations_uid = true;
    protected $timestamp_last_calculated = null;
    protected $timestamp_represent = null;
    protected $date_rep = true;
    protected $day = null;
    protected $month = null;
    protected $year = null;
    protected $cases = null;
    protected $cases_7day = null;
    protected $cases_14day = null;
    protected $cases_rate = null;
    protected $cases_pointer = null;
    protected $cases_ascension = null;
    protected $deaths = null;
    protected $deaths_7day = null;
    protected $deaths_14day = null;
    protected $deaths_rate = null;
    protected $deaths_pointer = null;
    protected $deaths_ascension = null;
    protected $population_used = null;
    protected $reproduction_4day = null;
    protected $reproduction_7day = null;
    protected $reproduction_14day = null;
    protected $exponence_1day = null;
    protected $exponence_7day = null;
    protected $exponence_14day = null;
    protected $incidence_7day = null;
    protected $incidence_14day = null;
    protected $incidence_14day_given = null;
    protected $condition_7day = null;
    protected $condition_14day = null;
    protected $alert_condition = null;    
    protected $flag_calculated = null;

    protected function get_install_sql()
    {
        return "CREATE TABLE IF NOT EXISTS `datacasts` (
          `uid` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
          `locations_uid` bigint UNSIGNED NOT NULL,
          `timestamp_last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          `timestamp_last_calculated` timestamp NULL DEFAULT NULL,
          `timestamp_registration` timestamp NOT NULL,
          `timestamp_deleted` timestamp NULL DEFAULT NULL,
          `timestamp_undeleted` timestamp NULL DEFAULT NULL,
          `timestamp_disabled` timestamp NULL DEFAULT NULL,
          `timestamp_enabled` timestamp NULL DEFAULT NULL,
          `timestamp_represent` timestamp NOT NULL,
          `date_rep` date NOT NULL,
          `day` smallint UNSIGNED NOT NULL,
          `month` smallint UNSIGNED NOT NULL,
          `year` year NOT NULL,
          `cases` bigint NOT NULL DEFAULT '0',
          `deaths` bigint NOT NULL DEFAULT '0',
          `cases_ascension` int NOT NULL DEFAULT '0',
          `deaths_ascension` int NOT NULL DEFAULT '0',
          `cases_7day` int NOT NULL DEFAULT '0',
          `deaths_7day` int NOT NULL DEFAULT '0',
          `cases_14day` int NOT NULL DEFAULT '0',
          `deaths_14day` int NOT NULL DEFAULT '0',
          `cases_pointer` enum('asc','desc','standby') NOT NULL DEFAULT 'standby',
          `deaths_pointer` enum('asc','desc','standby') NOT NULL DEFAULT 'standby',
          `cases_rate` float NOT NULL DEFAULT '0',
          `deaths_rate` float NOT NULL DEFAULT '0',
          `population_used` bigint DEFAULT NULL,
          `reproduction_4day` float NOT NULL DEFAULT '0',
          `reproduction_7day` float NOT NULL DEFAULT '0',
          `reproduction_14day` float NOT NULL DEFAULT '0',
          `exponence_1day` float NOT NULL DEFAULT '0',
          `exponence_7day` float NOT NULL DEFAULT '0',
          `exponence_14day` float NOT NULL DEFAULT '0',
          `incidence_7day` float NOT NULL DEFAULT '0',
          `incidence_14day` float NOT NULL DEFAULT '0',
          `incidence_14day_given` float NOT NULL DEFAULT '0',
          `condition_7day` enum('white','green','yellow','orange','red','darkred','black') NOT NULL DEFAULT 'green',
          `condition_14day` enum('white','green','yellow','orange','red','darkred','black') NOT NULL DEFAULT 'green',
          `alert_condition` enum('white','green','yellow','orange','red','darkred','black') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'green',
          `update_count` int UNSIGNED NOT NULL DEFAULT '0',
          `flag_updated` tinyint(1) NOT NULL DEFAULT '0',
          `flag_disabled` tinyint(1) NOT NULL DEFAULT '0',
          `flag_deleted` tinyint(1) NOT NULL DEFAULT '0',
          `flag_calculated` tinyint(1) NOT NULL DEFAULT '0',
          PRIMARY KEY (`uid`),
          UNIQUE KEY `locations_uid_date_rep` (`locations_uid`,`date_rep`),
          KEY `date` (`day`,`month`,`year`),
          KEY `locations_uid` (`locations_uid`),
          KEY `timestamp_represent` (`timestamp_represent`),
          KEY `date_rep` (`date_rep`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
        
        ALTER TABLE `datacasts`
          ADD CONSTRAINT `datacasts_locations_uid` FOREIGN KEY (`locations_uid`) REFERENCES `locations` (`uid`) ON DELETE CASCADE ON UPDATE CASCADE;";
    }
    
    protected function get_last_x_days($days, $skip_days = null)
    {
        $sql = "SELECT * FROM `".$this->get_tablename()."` WHERE ";
        $sql .= "locations_uid = ".$this->locations_uid." AND (";
        
        if ($skip_days)
        {
            $sql .= "date_rep < timestampadd(SQL_TSI_DAY, ".(0 - $skip_days).", '".$this->date_rep." 00:00:00') AND ";
            $days += $skip_days;
        }
        
        $sql .= "date_rep >= timestampadd(SQL_TSI_DAY, ".(0 - $days).", '".$this->date_rep." 00:00:00')";        
        $sql .= ")";
                
        $result = $this->get_db()->query($sql);
        
        if (!$result)
            return null;

        if (!$result->num_rows)
            return null;
            
        $retval = array();
        
        while ($obj = $result->fetch_object("WelterRocks\\CoronaData\\Table\\Datacasts", array($this->get_db())))
        {
            array_push($retval, $obj);
        }
        
        $result->free();
        
        return $retval;
    }
    
    protected function calculate_x_day_fields($days, $skip_days, $incidence_factor = 100000)
    {
        $last_x = $this->get_last_x_days($days, $skip_days);
        
        if (!$last_x)
            return false;
        
        $result = new \stdClass;
        $result->cases = (int)0;
        $result->deaths = (int)0;
        
        foreach ($last_x as $obj)
        {
            $result->cases += (int)$obj->cases ?: 0;
            $result->deaths += (int)$obj->deaths ?: 0;
        }
        
        if ($result->cases != 0)
            $result->exponence = ($this->cases / ($result->cases / $days));
        else
            $result->exponence = 0;
        
        $population = $this->population_used ?: 0;
        
        if ($population != 0)
            $result->incidence = ($result->cases / $population * $incidence_factor);
        else
            $result->incidence = 0;
            
        $result->incidence_factor = $incidence_factor;
        
        if ($result->incidence < 0)
            $result->condition = "white";
        elseif ($result->incidence == 0)
            $result->condition = "green";
        elseif ($result->incidence >= 150)
            $result->condition = "black";
        elseif ($result->incidence >= 75)
            $result->condition = "darkred";
        elseif ($result->incidence >= 50)
            $result->condition = "red";
        elseif ($result->incidence >= 35)
            $result->condition = "orange";
        else
            $result->condition = "yellow";

        return $result;
    }
    
    protected function calculate_x_day_r_value($days, $skip_days)
    {
        $cases = new \stdClass;

        $cases->suffix = $this->get_last_x_days($days, $skip_days);
        $cases->prefix = $this->get_last_x_days($days, ($skip_days + $days));

        if ((!$cases->prefix) || (count($cases->prefix) != $days))
            return false;

        if ((!$cases->suffix) || (count($cases->suffix) != $days))
            return false;
            
        $cases->prefix_value = 0;
        $cases->suffix_value = 0;
            
        foreach ($cases->prefix as $case)
            $cases->prefix_value += $case->cases;
            
        unset($cases->prefix);

        foreach ($cases->suffix as $case)
            $cases->suffix_value += $case->cases;
            
        unset($cases->suffix);
        
        $cases->prefix_average = ($cases->prefix_value / $days);
        $cases->suffix_average = ($cases->suffix_value / $days);
        
        if ($cases->suffix_average == 0)
            $cases->r_value = 0;
        else
            $cases->r_value = ($cases->suffix_average / $cases->prefix_average);

        return $cases;
    }
    
    public function calculate_14day_r_value($skip_days = 3)
    {
        $obj = $this->calculate_x_day_r_value(14, $skip_days);

        if (!$obj)
            return false;
            
        $this->reproduction_14day = $obj->r_value;
        
        return true;
    }
    
    public function calculate_7day_r_value($skip_days = 3)
    {
        $obj = $this->calculate_x_day_r_value(7, $skip_days);

        if (!$obj)
            return false;
            
        $this->reproduction_7day = $obj->r_value;
        
        return true;
    }
    
    public function calculate_4day_r_value($skip_days = 3)
    {
        $obj = $this->calculate_x_day_r_value(4, $skip_days);
        
        if (!$obj)
            return false;
            
        $this->reproduction_4day = $obj->r_value;
        
        return true;
    }

    public function calculate_alert_condition($conditions)
    {
        if (!is_array($conditions))
            $conditions = array($conditions);
            
        $numeric_condition = array(
            "white"   => 0,
            "green"   => 1,
            "yellow"  => 2,
            "orange"  => 3,
            "red"     => 4,
            "darkred" => 5,
            "black"   => 6        
        );
        
        $numeric_value = 0;
            
        foreach ($conditions as $condition)
        {
           if (!isset($numeric_condition[$condition]))
               continue;
           
           $numeric_value += $numeric_condition[$condition];     
        }
        
        $condition_value = round(($numeric_value / count($conditions)));
        $return_condition = "white";
        
        foreach ($numeric_condition as $condition => $value)
        {
            if ($value == $condition_value)
            {
                $return_condition = $condition;
                break;
            }
        }
        
        $this->alert_condition = $return_condition;
        
        return $return_condition;
    }
    
    public function calculate_14day_fields($incidence_factor = 100000)
    {
        $obj = $this->calculate_x_day_fields(14, null, $incidence_factor);
        
        if (!$obj)
            return false;
            
        if ($obj->cases == 0)
        {
            // No cases within the last 14 days. Flag the location as virus free.
            $loc = new Locations($this->get_db());
            
            $loc->uid = $this->locations_uid;
            
            if ($loc->select())
                $loc->set_virus_free_flag(true);
            
        }

        $this->cases_14day = $obj->cases;
        $this->deaths_14day = $obj->deaths;
        $this->exponence_14day = $obj->exponence;
        $this->incidence_14day = $obj->incidence;
        $this->condition_14day = $obj->condition;
        
        return true;
    }

    public function calculate_7day_fields($incidence_factor = 100000)
    {
        $obj = $this->calculate_x_day_fields(7, null, $incidence_factor);
        
        if (!$obj)
            return false;
            
        if ($obj->cases > 0)
        {
            // Cases found within the last 7 days. Flag the location as non virus free.
            $loc = new Locations($this->get_db());
            
            $loc->uid = $this->locations_uid;
            
            if ($loc->select())
                $loc->set_virus_free_flag(false);
            
        }

        $this->cases_7day = $obj->cases;
        $this->deaths_7day = $obj->deaths;
        $this->exponence_7day = $obj->exponence;
        $this->incidence_7day = $obj->incidence;
        $this->condition_7day = $obj->condition;
        
        return true;
    }

    public function calculate_ascension()
    {
        $last = $this->get_last_x_days(1);
    
        if ((!$last) || (count($last) == 0))
            return false;
            
        $this->cases_ascension = (int)($this->cases - $last[0]->cases) ?: 0;
        $this->deaths_ascension = (int)($this->deaths - $last[0]->deaths) ?: 0;
        
        $yesterday = ($this->cases - $this->cases_ascension);
        
        if ($yesterday != 0)
            $this->exponence_1day = ($this->cases / $yesterday);
        
        if ($this->cases_ascension > 0)
            $this->cases_pointer = "asc";
        elseif ($this->cases_ascension < 0)
            $this->cases_pointer = "desc";
        else
            $this->cases_pointer = "standby";
            
        if ($this->deaths_ascension > 0)
            $this->deaths_pointer = "asc";
        elseif ($this->deaths_ascension < 0)
            $this->deaths_pointer = "desc";
        else
            $this->deaths_pointer = "standby";
        
        return true;
    }
    
    public function calculate_rates()
    {
        $population = $this->population_used ?: 0;
        
        if ($population == 0)
            return false;
            
        $this->cases_rate = (100 / $population * $this->cases);
        $this->deaths_rate = (100 / $population * $this->deaths);
        
        return true;
    }
    
    public function recalculate($incidence_factor = 100000, $r_value_skip_days = 3)
    {
        $this->calculate_rates();
        $this->calculate_ascension();
        
        $this->calculate_7day_fields($incidence_factor);
        $this->calculate_14day_fields($incidence_factor);
        
        $this->calculate_alert_condition(
            array(
                $this->condition_7day,
                $this->condition_14day
            )
        );
        
        $this->calculate_4day_r_value($r_value_skip_days);
        $this->calculate_7day_r_value($r_value_skip_days);
        $this->calculate_14day_r_value($r_value_skip_days);
        
        $this->timestamp_last_calculated = date("Y-m-d H:i:s");
        $this->flag_calculated = 1;
        
        return true;
    }
    
    public function autoexec_insert_before()
    {
        if ($this->autoexec_is_disabled()) return;
        
        if (!$this->flag_calculated)
        {
            $this->recalculate();
        }

        return;
    }
    
    public function autoexec_insert_after($return_value = null)
    {
        if ($this->autoexec_is_disabled()) return;

        return;
    }
    
    public function autoexec_update_before()
    {
        if ($this->autoexec_is_disabled()) return;

        return;
    }
    
    public function autoexec_update_after()
    {
        if ($this->autoexec_is_disabled()) return;

        return;
    }
    
    public function autoexec_select_before()
    {
        if ($this->autoexec_is_disabled()) return;

        return;
    }
    
    public function autoexec_select_after()
    {
        if ($this->autoexec_is_disabled()) return;

        return;
    }
    
    public function autoexec_delete_before($undelete = false)
    {
        if ($this->autoexec_is_disabled()) return;

        return $undelete;
    }
    
    public function autoexec_delete_after($undelete = false)
    {
        if ($this->autoexec_is_disabled()) return;

        return $undelete;
    }
    
    public function autoexec_on_construct()
    {
        if ($this->autoexec_is_disabled()) return;

        return;
    }
    
    public function autoexec_on_destruct()
    {
        if ($this->autoexec_is_disabled()) return;

        return;
    }
}
