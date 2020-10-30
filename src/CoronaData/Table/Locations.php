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
    protected $timestamp_last_dataset = null;
    protected $timestamp_virus_free = null;
    protected $timestamp_virus_back = null;
    protected $timestamp_data_complete = null;
    protected $parent_uid = null;
    protected $location_type = null;
    protected $geo_id = null;
    protected $country_code = null;
    protected $continent = true;
    protected $country = true;
    protected $location = true;
    protected $population = null;
    protected $population_year = null;
    protected $population_density = null;
    protected $population_expected = null;
    protected $population_expected_worldwide = null;
    protected $population_expected_share = null;
    protected $median_age = null;
    protected $aged_65_older = null;
    protected $aged_70_older = null;
    protected $life_expectancy = null;
    protected $human_development_index = null;
    protected $gdp_per_capita = null;
    protected $handwashing_facilities = null;
    protected $hospital_beds_per_thousand = null;
    protected $male_smokers = null;
    protected $female_smokers = null;
    protected $cardiovasc_death_rate = null;
    protected $diabetes_prevalence = null;
    protected $extreme_poverty = null;
    protected $infection_density = null;
    protected $average_cases_per_day = null;
    protected $average_cases_per_week = null;
    protected $average_cases_per_month = null;
    protected $average_cases_per_year = null;
    protected $contamination_runtime = null;
    protected $contamination_value = null;
    protected $contamination_target = null;
    protected $flag_data_incomplete = null;
    protected $flag_no_longer_updated = null;
    protected $flag_virus_free = null;
    
    protected function get_install_sql()
    {
      return "CREATE TABLE IF NOT EXISTS `locations` (
        `uid` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
        `timestamp_last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        `timestamp_last_dataset` timestamp NULL DEFAULT NULL,
        `timestamp_registration` timestamp NOT NULL,
        `timestamp_deleted` timestamp NULL DEFAULT NULL,
        `timestamp_undeleted` timestamp NULL DEFAULT NULL,
        `timestamp_disabled` timestamp NULL DEFAULT NULL,
        `timestamp_enabled` timestamp NULL DEFAULT NULL,
        `timestamp_virus_free` timestamp NULL DEFAULT NULL,
        `timestamp_virus_back` timestamp NULL DEFAULT NULL,
        `timestamp_data_complete` timestamp NULL DEFAULT NULL,
        `parent_uid` bigint UNSIGNED DEFAULT NULL,
        `location_type` ENUM('continent','country','state','district','location') NOT NULL DEFAULT 'location',
        `geo_id` varchar(16) CHARACTER SET utf8mb4 DEFAULT NULL,
        `country_code` varchar(16) CHARACTER SET utf8mb4 DEFAULT NULL,
        `continent` varchar(32) NOT NULL,
        `country` varchar(64) NOT NULL,
        `location` varchar(64) NOT NULL,
        `population` bigint DEFAULT NULL,
        `population_year` year DEFAULT NULL,
        `population_density` float DEFAULT NULL,
        `population_expected` bigint DEFAULT NULL,
        `population_expected_worldwide` bigint DEFAULT NULL,
        `population_expected_share` float DEFAULT NULL,
        `median_age` float DEFAULT NULL,
        `aged_65_older` float DEFAULT NULL,
        `aged_70_older` float DEFAULT NULL,
        `life_expectancy` float DEFAULT NULL,
        `human_development_index` float DEFAULT NULL,
        `gdp_per_capita` float DEFAULT NULL,
        `handwashing_facilities` float DEFAULT NULL,
        `hospital_beds_per_thousand` float DEFAULT NULL,
        `male_smokers` float DEFAULT NULL,
        `female_smokers` float DEFAULT NULL,
        `cardiovasc_death_rate` float DEFAULT NULL,
        `diabetes_prevalence` float DEFAULT NULL,
        `extreme_poverty` float DEFAULT NULL,
        `infection_density` FLOAT NOT NULL DEFAULT '0',
        `average_cases_per_day` float NOT NULL DEFAULT '0',
        `average_cases_per_week` float NOT NULL DEFAULT '0',
        `average_cases_per_month` float NOT NULL DEFAULT '0',
        `average_cases_per_year` float NOT NULL DEFAULT '0',
        `contamination_runtime` INT UNSIGNED NOT NULL DEFAULT '0',
        `contamination_value` float NOT NULL DEFAULT '0',
        `contamination_target` date DEFAULT NULL,
        `update_count` int UNSIGNED NOT NULL DEFAULT '0',
        `flag_updated` tinyint(1) NOT NULL DEFAULT '0',
        `flag_disabled` tinyint(1) NOT NULL DEFAULT '0',
        `flag_deleted` tinyint(1) NOT NULL DEFAULT '0',
        `flag_data_incomplete` tinyint(1) NOT NULL DEFAULT '0',
        `flag_no_longer_updated` tinyint(1) NOT NULL DEFAULT '0',
        `flag_virus_free` tinyint(1) NOT NULL DEFAULT '0',
        PRIMARY KEY (`uid`),
        UNIQUE KEY `location_unique_identifier` (`continent`,`country`,`location`,`location_type`),
        KEY `country_code` (`country_code`),
        KEY `location_type` (`location_type`),
        KEY `continent` (`continent`),
        KEY `location` (`location`),
        KEY `parent_uid` (`parent_uid`),
        KEY `geo_id` (`geo_id`),
        KEY `country` (`country`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    }
    
    public function calculate_child_values($filter = null)
    {
        $this->check_view_and_uid();
        
        if (($filter) && ($filter != $this->location_type))
          return null;
        
        switch ($this->location_type)
        {
          case "continent":
            $sublevel = "country";
            break;
          case "country":
            $sublevel = "state";
            break;
          case "state":
            $sublevel = "district";
            break;
          case "district":
            $sublevel = "location";
            break;
          case "location":
            return null;
          default:
            throw new Exception("Unknown location type detected '".$this->location_type."'");
            return;
        }
        
        $sql = "";
        
        $fields = array(
          "uid" => "count",
          "population" => "sum",
          "population_year" => "as is",
          "population_density" => "avg",
          "population_expected" => "sum",
          "population_expected_worldwide" => "sum",
          "population_expected_share" => "sum",
          "median_age" => "avg",
          "aged_65_older" => "sum",
          "aged_70_older" => "sum",
          "life_expectancy" => "avg",
          "human_development_index" => 0,
          "gdp_per_capita" => 0,
          "handwashing_facilities" => 0,
          "hospital_beds_per_thousand" => 0,
          "male_smokers" => 0,
          "female_smokers" => 0,
          "cardiovasc_death_rate" => 0,
          "diabetes_prevalence" => 0,
          "extreme_poverty" => 0,
          "infection_density" => 0,
          "average_cases_per_day" => 0,
          "average_cases_per_week" => 0,
          "average_cases_per_month" => 0,
          "average_cases_per_year" => 0,
          "contamination_runtime" => 0,
          "contamination_value" => 0,
          "contamination_target" => 0
        );
        
        foreach ($fields as $field => $type)
        {
          switch ($type)
          {
            case "count":
            case "avg":
            case "sum":
            case "max":
            case "min":
              $sql .= ", ".strtoupper($type)."(`".$field."`) as '".$field."'";
              break;
            default:
              $sql .= ", `".$field."`";
              break;
          }
        }
        
        $sql = "SELECT `parent_uid`".$sql." FROM `".$this->get_tablename()."` WHERE `parent_uid` = ".$this->uid." AND location_type = '".$sublevel."' GROUP BY `parent_uid`";
        
        $result = $this->get_db()->query($sql);
        
        if (!$result)
          return null;
          
        if ($result->num_rows == 0)
          return false;
          
        if ($obj = $result->fetch_object())
        {
          foreach ($obj as $key => $val)
            $this->$key = $val;
        }
        
        $result->free();
                
        return true;
    }
    
    public function calculate_contamination()
    {
        $sql = "SELECT SUM(cases) AS cases_total, AVG(population_used) as population_average, AVG(exponence_1day) as exponence_average, COUNT(uid) AS days FROM `datacasts` WHERE `flag_disabled` = 0 AND `flag_deleted` = 0 AND `locations_uid` = ".$this->uid;
        
        $res = $this->get_db()->query($sql);
        
        if ((!$res) || ($res->num_rows == 0))
          return null;
          
        $obj = $res->fetch_object();
        $res->free();
        
        if ($obj->days == 0)
          return null;
          
        $this->contamination_runtime = $obj->days;
          
        $this->average_cases_per_day = ($obj->cases_total / $obj->days);
        $this->average_cases_per_week = ($this->average_cases_per_day * 7);
        $this->average_cases_per_month = ($this->average_cases_per_day * 30);
        $this->average_cases_per_year = ($this->average_cases_per_day * 365);
        
        if ($obj->population_average == 0)
          $obj->population_average = $this->population;
          
        if ($obj->population_average > 0)
          $this->contamination_value = (100 / $obj->population_average * $obj->cases_total);
        else
          $this->contamination_value = 0;
          
        if (!$obj->exponence_average)
          $obj->exponence_average = 0.667;
        
        if ($this->population_density > 0)
          $this->infection_density = $this->average_cases_per_day / $this->population_density;
        else
          $this->infection_density = 0;

        if ($this->human_development_index == 0)
          $hdi = 0.1;
        else
          $hdi = $this->human_development_index;
                
        $contamination_target_seconds = (($obj->population_average / $this->average_cases_per_day * $obj->exponence_average) * 24 * 60 * 60) * $hdi;
        
        $this->contamination_target = date("Y-m-d", (time() + $contamination_target_seconds));
    }
    
    public function is_data_incomplete()
    {
        $validate = array(
          "geo_id",
          "country_code",
          "continent",
          "location",
          "population",
          "population_density",
          "median_age",
          "aged_65_older",
          "aged_70_older",
          "life_expectancy",
          "human_development_level",
          "gdp_per_capita",
          "handwashing_facilities",
          "hospital_beds_per_thousand",
          "male_smokers",
          "female_smokers",
          "cardiovasc_death_rate",
          "diabetes_prevalence",
          "exteme_poverty"
        );
        
        $vcount = 0;
        
        foreach ($validate as $field)
        {
          if ((isset($this->$field)) && (!empty($this->$field)) && ($this->$field !== null))
            $vcount++;
        }
        
        if ($vcount == count($validate))
          return true;
          
        return false;    
    }
    
    public function autoset_data_incomplete_flag(&$error = null, &$sql = null)
    {
        $error = null;
        $sql = null;
        
        $this->check_view_and_uid();

        $sqlstmt = "UPDATE `".$this->get_tablename()."` SET `flag_data_incomplete` = %d%s WHERE `uid` = '".$this->uid."' LIMIT 1";
        
        if ($this->is_data_incomplete())
        {
          if (!$this->flag_data_incomplete)
            $sql = sprintf($sqlstmt, 1, ", `timestamp_data_complete` = '".$this->get_sql_timestamp()."'");
        }
        else
        {
          if ($this->flag_data_incomplete)
            $sql = sprintf($sqlstmt, 0, "");
        }        

        if ($sql)
        {
          if (!$this->get_db()->query($sql))
          {
            $error = $this->get_db()->error;
            
            return null;
          }
          
          return true;
        }
        
        return false;
    }
    
    public function autoset_no_longer_updated_flag(&$error = null, &$sql = null)
    {
        $error = null;
        $sql = null;
        
        $this->check_view_and_uid();
        
        $datetime1 = new \DateTime($this->timestamp_last_dataset);
        $datetime2 = new \DateTime("now");
        
        $datediff = $datetime1->diff($datetime2);
        $sqlstmt = "UPDATE `".$this->get_tablename()."` SET `flag_no_longer_updated` = %d WHERE `uid` = '".$this->uid."' LIMIT 1";
        
        if (($datediff->days > 1) && ($datediff->invert == 0))
        {
          if (!$this->flag_no_longer_updated)
            $sql = sprintf($sqlstmt, 1);
        }
        else
        {
          if ($this->flag_no_longer_updated)
            $sql = sprintf($sqlstmt, 0);
        }
        
        if ($sql)
        {
          if (!$this->get_db()->query($sql))
          {
            $error = $this->get_db()->error;
            
            return null;
          }
          
          return true;
        }
        
        return false;
    }
    
    public function set_virus_free_flag($has_virus = false, &$error = null, &$sql = null)
    {
        $error = null;
        $sql = null;

        $this->check_view_and_uid();
        
        if (($has_virus) && ($this->flag_virus_free == 1))
          return null;
          
        if ((!$has_virus) && ($this->flag_virus_free != 0))
          return null;
          
        $sql = "UPDATE `".$this->get_tablename()."` SET `flag_virus_free` = ".(($has_virus) ? 0 : 1).", `timestamp_virus_".(($has_virus) ? "back" : "free")."` = '".$this->get_sql_timestamp()."' WHERE `uid` = '".$this->uid."' LIMIT 1";
        
        if (!$this->get_db()->query($sql))
        {
          $error = $this->get_db()->error;
          
          return false;
        }
        
        return true;
    }
    
    public function inject_averages_from_positives(&$error = null, &$sql = null)
    {
        $error = null;
        $sql = null;
        
        $this->check_view_and_uid();
        
        $sql = "SELECT * FROM `positivestat` WHERE ".$this->location_type."_uid = ".$this->uid;
        
        $result = $this->get_db()->query($sql);
        
        if (!$result)
        {
          $error = $this->get_db()->error;
          
          return null;
        }
        
        if (!$result->num_rows)
        {
          $error = "No positive entry found";
          
          return false;
        }
        
        $stat = $result->fetch_object();        
        $result->free();

        $this->average_cases_per_day = ($stat->cases_average / $stat->days_total);
        $this->average_cases_per_week = ($stat->cases_average / $stat->days_total * 7);
        $this->average_cases_per_month = ($stat->cases_average / $stat->days_total * 30);
        $this->average_cases_per_year = ($stat->cases_average / $stat->days_total * 365);
                
        $this->contamination_runtime = $stat->days_total;
        
        return true;
    }
    
    public function autoexec_insert_after($retval)
    {
        if ($this->autoexec_is_disabled()) return;
        
        if ($this->uid)
        {
          $this->autoset_no_longer_updated_flag();
          $this->autoset_data_incomplete_flag();
        }
    }
}
