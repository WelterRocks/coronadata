<?php namespace WelterRocks\CoronaData;

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

use WelterRocks\CoronaData\Config;
use WelterRocks\CoronaData\DataHandler;
use WelterRocks\CoronaData\Database;
use WelterRocks\CoronaData\Genesis;
use WelterRocks\CoronaData\Exception;
    
class Client
{
    private $config = null;
    private $config_file = null;
    
    private $stores_loaded_count = null;
    private $stores_loaded_bytes = null;
        
    private $eu_datacast = null;    
    private $rki_positive = null;    
    private $rki_nowcast = null;    
    private $rki_rssfeed = null;    
    private $cov_infocast = null;
    
    private $gen_territory_area = null;
    private $gen_territory_district_area = null;
    private $gen_population = null;
    private $gen_population_by_state = null;
    private $gen_population_by_district = null;
    
    private $database = null;    
    private $transaction_name = null;
    
    private $continents = null;
    private $countries = null;
    private $states = null;
    private $districts = null;
    private $locations = null;
    
    private $datasets = null;
    private $testresults = null;
    
    public static function clean_str($str)
    {
        $allowed = "abcdefghijklmnopqrstuvwxyz";
        $retval = "";
        
        $str = trim($str);
        
        for ($i = 0; $i < strlen($str); $i++)
        {
            $c = substr($str, $i, 1);
            
            if (!stristr($allowed, $c))
                continue;
                
            $retval .= strtoupper($c);             
        }

        return $retval;
    }
    
    public static function hash_name($str)
    {
        $retval = md5(self::clean_str($str));
        
        return $retval;
    }
    
    public static function threeletter_encode($str)
    {
        $retval = substr(str_pad(self::clean_str($str), 3, "0"), 0, 3);
        
        return $retval;
    }
    
    public static function timestamp($resolution = 1000)
    {
        return round((microtime(true) * $resolution));
    }
    
    public function install($force_install = false, &$error = null, &$sql = null)
    {
        return $this->database->install($force_install, $error, $sql);
    }
    
    public function get_data_store()
    {
        return realpath($this->config->data_store);
    }
    
    private function create_datastore()
    {
        if (!is_dir($this->config->data_store))
        {
            if (file_exists($this->config->data_store))
                throw new Exception("Data store path is a regular file. Change data store path or move it out of the way.");
                
            if (!@mkdir($this->config->data_store, 0777, true))
                throw new Exception("Unable to create data store path. Insufficient permissions.");
        }
        
        if (@touch($this->config->data_store."/.create_datastore"))
            @unlink($this->config->data_store."/.create_datastore");
        else
            throw new Exception("Data store path is not writable. Insufficient permissions.");
            
        return true;
    }
    
    private function get_template(DataHandler $handler)
    {
        $tmpl = new \stdClass;
        $tmpl->handler = $handler;
        $tmpl->timestamp = -1;
        $tmpl->size = -1;
        $tmpl->filename = $tmpl->handler->get_cache_filename();
        
        return $tmpl;
    }
    
    private function retrieve_obj_data(\stdClass $obj, $transform = null, $cache_timeout = 14400, $target_filename = true, $target_compression_level = 9, $not_json_encoded = false)
    {
       if ($retval = $obj->handler->retrieve($target_filename, $cache_timeout, $target_compression_level, $not_json_encoded))
       {
           if ($transform)
           {
               if (!$obj->handler->$transform())
                   return null;
           }
               
           $obj->timestamp = self::timestamp();
           $obj->size = $retval;
           
           return $retval;
       }
       
       return null;
    }
    
    public function create_error_dump($prefix = "errordump-", $data)
    {
        $filename = $this->config->data_store."/".$prefix.self::timestamp().".log";
        
        $fd = @fopen($filename, "w");
        
        if (!is_resource($fd))
            return null;
            
        @fwrite($fd, print_r($data, 1));
        @fclose($fd);
        
        return $filename;
    }

    public function retrieve_gen_territory_area($cache_timeout = 14400)
    {
       return $this->retrieve_obj_data($this->gen_territory_area, "transform_gen_territory_area", $cache_timeout);
    }
    
    public function retrieve_gen_territory_district_area($cache_timeout = 14400)
    {
       return $this->retrieve_obj_data($this->gen_territory_district_area, "transform_gen_territory_district_area", $cache_timeout);
    }
    
    public function retrieve_gen_population($cache_timeout = 14400)
    {
       return $this->retrieve_obj_data($this->gen_population, "transform_gen_population", $cache_timeout);
    }
    
    public function retrieve_gen_population_by_state($cache_timeout = 14400)
    {
       return $this->retrieve_obj_data($this->gen_population_by_state, "transform_gen_population_by_state", $cache_timeout);
    }
    
    public function retrieve_gen_population_by_district($cache_timeout = 14400)
    {
       return $this->retrieve_obj_data($this->gen_population_by_district, "transform_gen_population_by_district", $cache_timeout);
    }
    
    public function retrieve_eu_datacast($cache_timeout = 14400)
    {
       return $this->retrieve_obj_data($this->eu_datacast, "transform_eu_datacast", $cache_timeout);
    }
    
    public function retrieve_rki_positive($cache_timeout = 14400)
    {
       return $this->retrieve_obj_data($this->rki_positive, "transform_rki_positive", $cache_timeout);
    }
    
    public function retrieve_rki_nowcast($cache_timeout = 14400)
    {
       return $this->retrieve_obj_data($this->rki_nowcast, "transform_rki_nowcast", $cache_timeout);       
    }
    
    public function retrieve_rki_rssfeed($cache_timeout = 14400, $target_filename = null)
    {
       if (!$target_filename)
           $target_filename = realpath($this->config->data_store)."/rki_rssfeed.xml";
           
       return $this->retrieve_obj_data($this->rki_rssfeed, null, $cache_timeout, $target_filename, -1, true);
    }
    
    public function retrieve_cov_infocast($cache_timeout = 14400)
    {
       return $this->retrieve_obj_data($this->cov_infocast, "transform_cov_infocast", $cache_timeout);       
    }
    
    public function export_eu_datacast(&$length = null, &$timestamp = null)
    {
        $length = $this->eu_datacast->handler->get_length();
        $timestamp = $this->eu_datacast->handler->get_timestamp();
        
        return $this->eu_datacast->handler->get_data();
    }
    
    public function export_rki_nowcast(&$length = null, &$timestamp = null)
    {
        $length = $this->rki_nowcast->handler->get_length();
        $timestamp = $this->rki_nowcast->handler->get_timestamp();
        
        return $this->rki_nowcast->handler->get_data();
    }
    
    public function export_rki_positive(&$length = null, &$timestamp = null)
    {
        $length = $this->rki_positive->handler->get_length();
        $timestamp = $this->rki_positive->handler->get_timestamp();
        
        return $this->rki_positive->handler->get_data();
    }
    
    public function export_rki_rssfeed(&$length = null, &$timestamp = null)
    {
        $length = $this->rki_rssfeed->handler->get_length();
        $timestamp = $this->rki_rssfeed->handler->get_timestamp();
        
        return $this->rki_rssfeed->handler->get_data();
    }
    
    public function export_cov_infocast(&$length = null, &$timestamp = null)
    {
        $length = $this->cov_infocast->handler->get_length();
        $timestamp = $this->cov_infocast->handler->get_timestamp();
        
        return $this->cov_infocast->handler->get_data();
    }
    
    public function database_check_installation(&$error = null)
    {
        $error = null;
        
        return $this->database->install($error);
    }
    
    public function database_clear_records()
    {
        return $this->database->clear_records();
    }
    
    public function get_eu_datacast()
    {
        return $this->eu_datacast;
    }
    
    public function get_rki_positive()
    {
       return $this->rki_positive; 
    }
    
    public function get_rki_nowcast()
    {
       return $this->rki_nowcast;
    }
    
    public function get_rki_rssfeed()
    {
       return $this->rki_rssfeed; 
    }
    
    public function get_cov_infocast()
    {
       return $this->cov_infocast;
    }
    
    public function get_gen_territory_area()
    {
       return $this->gen_territory_area;
    }
    
    public function get_table_status()
    {
        return $this->database->get_table_status();
    }
    
    public function get_datetime_diff($date1, $date2 = "now")
    {
        $datetime1 = new \DateTime($date1);
        $datetime2 = new \DateTime($date2);
        
        $datediff = $datetime1->diff($datetime2);

        return $datediff;    
    }
    
    public function database_transaction_begin($transaction_name, $flags = 0)
    {
        if ($this->transaction_name)
            throw new Exception("Transaction '".$this->transaction_name."' already open");
            
        $retval = $this->database->begin_transaction($transaction_name, $flags);
        
        if ($retval)
            $this->transaction_name = $transaction_name;
            
        return $retval;
    }
    
    public function database_transaction_commit($transaction_name, $flags = 0)
    {
        if (!$this->transaction_name)
            throw new Exception("No open transaction to commit");
            
        $retval = $this->database->commit($transaction_name, $flags);
        
        if ($retval)
            $this->transaction_name = null;
            
        return $retval;
    }
    
    public function database_transaction_rollback($transaction_name, $flags = 0)
    {
        if (!$this->transaction_name)
            throw new Exception("No open transaction to rollback");
            
        $retval = $this->database->rollback($transaction_name, $flags);
        
        if ($retval)
            $this->transaction_name = null;
            
        return $retval;
    }
    
    public static function get_infocast_filter($type = "outer")
    {
       $filter = null;

        if ($type == "outer")
            $filter = array(
                "aged_65_older",
                "aged_70_older",
                "cardiovasc_death_rate",
                "diabetes_prevalence",
                "extreme_poverty",
                "female_smokers",
                "gdp_per_capita",
                "handwashing_facilities",
                "hospital_beds_per_thousand",
                "human_development_index",
                "life_expectancy",
                "male_smokers",
                "median_age",
                "population_density"
            );
        
        if ($type == "inner")
            $filter = array(
                "hosp_patients" => "sum",
                "hosp_patients_per_million" => "avg",
                "icu_patients" => "sum",
                "icu_patients_per_million" => "avg",
                "new_cases" => "sum",
                "new_cases_per_million" => "avg",
                "new_cases_smoothed" => "sum",
                "new_cases_smoothed_per_million" => "avg",
                "new_deaths" => "sum",
                "new_deaths_per_million" => "avg",
                "new_deaths_smoothed" => "sum",
                "new_deaths_smoothed_per_million" => "avg",
                "new_tests" => "sum",
                "new_tests_per_thousand" => "avg",
                "new_tests_smoothed" => "sum",
                "new_tests_smoothed_per_thousand" => "avg",
                "positive_rate" => "avg",
                "stringency_index" => "avg",
                "tests_per_case" => "sum",
                "tests_units" => "str",
                "total_cases" => "sum",
                "total_cases_per_million" => "avg",
                "total_deaths" => "sum",
                "total_deaths_per_million" => "avg",
                "total_tests" => "sum",
                "total_tests_per_thousand" => "avg",
                "weekly_hosp_admissions" => "sum",
                "weekly_hosp_admissions_per_million" => "avg",
                "weekly_icu_admissions" => "sum",
                "weekly_icu_admissions_per_million" => "avg" 
            );
            
       return $filter;     
    }
    
    public function master_locations($hold_data = false)
    {
        // After stores are loaded, extract locations from all data sources and build a standardized tree
        
        if ($this->stores_loaded_count < 10)
            return false;
            
        // Create in-memory stores
        $continents = array();
        $countries = array();
        $districts = array();
        $states = array();
        $locations = array();
        
        // Create an index for districts, so that RKI positives can be assigned faster
        $district_index = array();
        
        // Create a template
        $tmpl = new \stdClass;
        $tmpl->continent_id = null;
        $tmpl->continent_hash = null;
        $tmpl->continent_name = null;
        $tmpl->country_id = null;
        $tmpl->country_hash = null;
        $tmpl->country_name = null;
        $tmpl->state_id = null;
        $tmpl->state_hash = null;
        $tmpl->state_name = null;
        $tmpl->district_id = null;
        $tmpl->district_hash = null;
        $tmpl->district_name = null;
        $tmpl->district_type = null;
        $tmpl->district_fullname = null;
        $tmpl->location_id = null;
        $tmpl->location_hash = null;
        $tmpl->location_name = null;
        $tmpl->location_type = null;
        $tmpl->geo_id = null;
        $tmpl->population_year = 0;
        $tmpl->population_count = 0;
        $tmpl->population_females = 0;
        $tmpl->population_males = 0;
        $tmpl->area = 0;
        $tmpl->deaths_count = 0;
        $tmpl->deaths_min = 999999999999;
        $tmpl->deaths_max = 0;
        $tmpl->cases_count = 0;
        $tmpl->cases_min = 999999999999;
        $tmpl->cases_max = 0;
        $tmpl->timestamp_min = (time() + 86400);
        $tmpl->timestamp_max = 0;

        // First, we use the EU datacast
        foreach ($this->eu_datacast->handler->get_data()->records as $id => $record)
        {
            $continent_hash = self::hash_name($record->continent);
            $country_hash = self::hash_name($record->country);
            
            if (!isset($continents[$continent_hash]))                
                $continent = clone $tmpl;
            else
                $continent = $continents[$continent_hash];
            
            $continent->location_hash = md5('continent'.$continent_hash);
            $continent->continent_id = self::threeletter_encode($record->continent);
            $continent->geo_id = substr($continent->continent_id, 0, 2);
            $continent->continent_hash = $continent_hash;
            $continent->continent_name = $record->continent;
            $continent->location_type = 'continent';
            $continent->population_count += $record->population;
            $continent->deaths_count += $record->deaths;
            $continent->cases_count += $record->cases;
            
            if ($continent->population_year > $record->population_year)
                $continent->population_year = $record->population_year;
            
            $timestamp = strtotime($record->timestamp_represent);
            
            if ($continent->timestamp_min > $timestamp)
                $continent->timestamp_min = $timestamp;
                
            if ($continent->timestamp_max < $timestamp)
                $continent->timestamp_max = $timestamp;
                
            if ($continent->deaths_min > $record->deaths)
                $continent->deaths_min = $record->deaths;

            if ($continent->deaths_max < $record->deaths)
                $continent->deaths_max = $record->deaths;

            if ($continent->cases_min > $record->cases)
                $continent->cases_min = $record->cases;

            if ($continent->cases_max < $record->cases)
                $continent->cases_max = $record->cases;
                
            if (!isset($countries[$country_hash]))
                $country = clone $tmpl;
            else
                $country = $countries[$country_hash];
                
            $country->location_hash = md5('country'.$continent_hash.$country_hash);
            $country->continent_id = self::threeletter_encode($record->continent);
            $country->continent_hash = $continent_hash;
            $country->continent_name = $record->continent;
            $country->country_id = $record->country_code ?: self::threeletter_encode($record->country);
            $country->country_hash = $country_hash;
            $country->country_name = $record->country;
            $country->location_type = 'country';
            $country->geo_id = $record->geo_id ?: substr($record->country, 0, 2);
            $country->population_count = $record->population;
            $country->deaths_count = $record->deaths;
            $country->cases_count = $record->cases;
            
            if ($country->population_year > $record->population_year)
                $country->population_year = $record->population_year;
            
            $timestamp = strtotime($record->timestamp_represent);
            
            if ($country->timestamp_min > $timestamp)
                $country->timestamp_min = $timestamp;
                
            if ($country->timestamp_max < $timestamp)
                $country->timestamp_max = $timestamp;
                
            if ($country->deaths_min > $record->deaths)
                $country->deaths_min = $record->deaths;

            if ($country->deaths_max < $record->deaths)
                $country->deaths_max = $record->deaths;

            if ($country->cases_min > $record->cases)
                $country->cases_min = $record->cases;

            if ($country->cases_max < $record->cases)
                $country->cases_max = $record->cases;            
                
            $countries[$country_hash] = $country;
            $continents[$continent_hash] = $continent;
        }
        
        // Setup inner and outer iteration filters
        $filter_outer = self::get_infocast_filter("outer");        
        $filter_inner = self::get_infocast_filter("inner");
        
        // Second, we use the COV infocast to extend, but EU has always preference, because it is an official data source
        foreach ($this->cov_infocast->handler->get_data() as $country_code => $record)
        {
            // This will also skip "WORLD", but we dont need it
            if (!isset($record->continent))
                continue;
                
            // We have to make sure, America is set properly, so we merge north and south together for EU datacast compat
            if (stristr($record->continent, "America"))
                $record->continent = "America";
            
            $continent_hash = self::hash_name($record->continent);
            $country_hash = self::hash_name($record->location);
            
            $continent_created = false;
            $country_created = false;
            
            if (!isset($continents[$continent_hash]))
            {
                $continent = clone $tmpl;
                $continent_created = true;
            }
            else
            {
                $continent = $continents[$continent_hash];
            }
            
            if ($continent_created)
            {
                $continent->location_hash = md5('continent'.$continent_hash);
                $continent->continent_id = self::threeletter_encode($record->continent);
                $continent->geo_id = substr($continent->continent_id, 0, 2);
                $continent->continent_hash = $continent_hash;
                $continent->continent_name = $record->continent;
                $continent->location_type = 'continent';
            }

            $continent->population_count += $record->population;
            
            if (!isset($countries[$country_hash]))
            {
                $country = clone $tmpl;
                $country_created = true;
            }
            else
            {
                $country = $countries[$country_hash];
            }
            
            if ($country_created)
            {
                $country->location_hash = md5('country'.$continent_hash.$country_hash);
                $country->continent_id = self::threeletter_encode($record->continent);
                $country->geo_id = substr($country->continent_id, 0, 2);
                $country->continent_hash = $continent_hash;
                $country->continent_name = $record->continent;
                $country->country_hash = $country_hash;
                $country->country_id = $country_code ?: self::threeletter_encode($record->location);
                $country->country_name = $record->location;
                $country->location_type = 'country';
                $country->population_count = $record->population;
            }

            foreach ($filter_outer as $filter)
            {
                if (!isset($record->$filter))
                    continue;
                    
                if (!isset($continent->$filter))
                    $continent->$filter = $record->$filter;
                    
                $continent->$filter = (($continent->$filter + $record->$filter) / 2);

                if (!isset($country->$filter))
                    $country->$filter = $record->$filter;
                    
                $country->$filter = (($country->$filter + $record->$filter) / 2);
            }
            
            // We only need the last dataset to update the location objects
            $last_index = (count($record->data) - 1);            
            $data = $record->data[$last_index];
            
            foreach ($filter_inner as $filter => $type)
            {
                if (!isset($data->$filter))
                    continue;
                    
                if (!isset($continent->$filter))
                {
                    if ($type != "sum")
                        $continent->$filter = $data->$filter;
                    else
                        $continent->$filter = 0;
                }
            
                if (is_numeric($data->$filter))
                {
                    if ($type == "avg")
                        $continent->$filter = (($continent->$filter + $data->$filter) / 2);
                    elseif ($type == "sum")
                        $continent->$filter += $data->$filter;
                }

                if (!isset($country->$filter))
                {
                    if ($type != "sum")
                        $country->$filter = $data->$filter;
                    else
                        $country->$filter = 0;
                }
                    
                if (is_numeric($data->$filter))
                {
                    if ($type == "avg")
                        $country->$filter = (($country->$filter + $data->$filter) / 2);
                    elseif ($type == "sum")
                        $country->$filter += $data->$filter;
                }
            }
            
            unset($data);
            unset($last_index);

            // Try to calculate contamination status
            if ((isset($country->total_cases)) && ($country->total_cases > 0))
            {
                $country->contamination_total = ($country->population_count / $country->total_cases);
                $country->contamination_rundays = ((time() - $country->timestamp_min) / 60 / 60 / 24);

                if ($country->contamination_rundays > 0)
                    $country->contamination_per_day = ($country->total_cases / $country->contamination_rundays);
                else
                    $country->contamination_per_day = 0;

                if ($country->contamination_per_day > 0)
                    $country->contamination_target = (($country->population_count - $country->total_cases) / $country->contamination_per_day);
            }

            if ((isset($continent->total_cases)) && ($continent->total_cases > 0))
            {
                $continent->contamination_total = ($continent->population_count / $continent->total_cases);
                $continent->contamination_rundays = ((time() - $continent->timestamp_min) / 60 / 60 / 24);

                if ($continent->contamination_rundays > 0)
                    $continent->contamination_per_day = ($continent->total_cases / $continent->contamination_rundays);
                else
                    $continent->contamination_per_day = 0;

                if ($continent->contamination_per_day > 0)
                    $continent->contamination_target = (($continent->population_count - $continent->total_cases) / $continent->contamination_per_day);
            }
            
            // Try to set infection status
            if ($country->area > 0)
            {
                $country->infection_density = ($country->cases_count / $country->area);
                $country->infection_area = (1 / $country->infection_density);
                $country->infection_probability = (100 / ($country->infection_area * $country->population_density));
            }            

            if ($continent->area > 0)
            {
                $continent->infection_density = ($continent->cases_count / $continent->area);
                $continent->infection_area = (1 / $continent->infection_density);
                $continent->infection_probability = (100 / ($continent->infection_area * $continent->population_density));
            }            

            // We must override the used addressed space, if objects just have been created, they will get lost if we dont force this
            $countries[$country_hash] = $country;
            $continents[$continent_hash] = $continent;
        }

        // No longer needed, so clean them up        
        unset($filter_inner);
        unset($filter_outer);
        
        // Third, we add the german states
        $europe_hash = self::hash_name("Europe");
        $germany_hash = self::hash_name("Germany");
                
        $europe = $continents[$europe_hash];
        $germany = $countries[$germany_hash];

        // If we dont have europe or germany at this point, things went wrong
        if ((!$europe) || (!$germany))
            throw new Exception("Europe and/or Germany could not be found in data stores");
        
        // Set area size
        $germany->area = $this->gen_territory_area->handler->get_data()->area;
        
        $german_states_area = $this->gen_territory_area->handler->get_data()->states_area;
        
        if (is_array($german_states_area))
        {
            foreach ($german_states_area as $state_name => $area)
            {
                $state_hash = self::hash_name($state_name);
                
                if (!isset($states[$state_hash]))
                    $state = clone $tmpl;
                else
                    $state = $states[$state_hash];
                
                $real_state_name = null;
                    
                $state->location_hash = md5('state'.$europe->continent_hash.$germany->country_hash.$state_hash);
                $state->geo_id = DataHandler::german_state_id_by_name($state_name, $real_state_name);
                $state->continent_id = $europe->continent_id;
                $state->continent_hash = $europe->continent_hash;
                $state->continent_name = $europe->continent_name;
                $state->country_id = $germany->country_id;
                $state->country_hash = $germany->country_hash;
                $state->country_name = $germany->country_name;
                $state->state_id = self::threeletter_encode($state_name);
                $state->state_hash = $state_hash;
                $state->state_name = $real_state_name ?: $state_name;
                $state->location_type = 'state';
                $state->area = $area;
                
                if (!isset($germany->area))
                    $germany->area = 0;
                                
                $germany->area += $area;
                
                $states[$state_hash] = $state;
            }
        }
        
        unset($german_states_area);
        
        $german_states_population = $this->gen_population_by_state->handler->get_data()->states;

        if (is_array($german_states_population))
        {
            foreach ($german_states_population as $state_name => $data)
            {
                $state_hash = self::hash_name($state_name);
                $state_created = false;
                
                if (!isset($states[$state_hash]))
                {
                    $state = clone $tmpl;
                    $state_created = true;
                }
                else
                {
                    $state = $states[$state_hash];
                }
                
                if ($state_created)
                {
                    $real_state_name = null;
                        
                    $state->location_hash = md5('state'.$europe->continent_hash.$germany->country_hash.$state_hash);
                    $state->geo_id = DataHandler::german_state_id_by_name($state_name, $real_state_name);
                    $state->continent_id = $europe->continent_id;
                    $state->continent_hash = $europe->continent_hash;
                    $state->continent_name = $europe->continent_name;
                    $state->country_id = $germany->country_id;
                    $state->country_hash = $germany->country_hash;
                    $state->country_name = $germany->country_name;
                    $state->state_id = self::threeletter_encode($state_name);
                    $state->state_hash = $state_hash;
                    $state->state_name = $real_state_name ?: $state_name;
                    $state->location_type = 'state';
                }
                
                $state->population_males = $data->males;
                $state->population_females = $data->females;
                $state->population_count = $data->totals;
                
                if ($state->area > 0)
                    $state->population_density = ($state->population_count / $state->area);
                
                if (!isset($germany->population_females))
                    $germany->population_females = 0;
                    
                if (!isset($germany->population_males))
                    $germany->population_males = 0;
                    
                $germany->population_females += $data->females;
                $germany->population_males += $data->males;
                
                $state->population_year = $data->date->format("Y");
                
                $states[$state_hash] = $state;
            }            
        }
        
        unset($german_states_population);
        
        // Forth, we add the german districts
        $german_districts_area = $this->gen_territory_district_area->handler->get_data()->districts_area;
        $german_districts_population = $this->gen_population_by_district->handler->get_data()->districts;

        if (is_array($german_districts_area))
        {
            foreach ($german_districts_area as $district_id => $data)
            {	
                $state_name = DataHandler::german_state_by_district_geo_id($data->id);                
                $state_hash = self::hash_name($state_name);
                
                $district_hash = self::hash_name($state_name.$data->name);
                
                if (!isset($states[$state_hash]))
                    continue;
                else
                    $state = $states[$state_hash];
                
                if (!isset($districts[$district_hash]))
                    $district = clone $tmpl;
                else
                    $district = $districts[$district_hash];
                    
                $district_index[$district_id] = $district_hash;
                    
                $district->location_hash = md5('district'.$europe->continent_hash.$germany->country_hash.$state->state_hash.$district_hash);
                $district->geo_id = $district_id;
                $district->continent_id = $europe->continent_id;
                $district->continent_hash = $europe->continent_hash;
                $district->continent_name = $europe->continent_name;
                $district->country_id = $germany->country_id;
                $district->country_hash = $germany->country_hash;
                $district->country_name = $germany->country_name;
                $district->state_id = $state->state_id;
                $district->state_hash = $state->state_hash;
                $district->state_name = $state->state_name;
                $district->district_id = self::threeletter_encode($data->name);
                $district->district_hash = $district_hash;
                $district->district_name = $data->name;
                $district->district_type = $data->type;
                $district->district_fullname = $data->fullname;
                $district->location_type = 'district';
                $district->area = $data->area;
                
                if (is_array($german_districts_population))
                {
                    if (isset($german_districts_population[$district_id]))
                    {
                        $data2 = $german_districts_population[$district_id];
                        
                        $district->population_females = $data2->females;
                        $district->population_males = $data2->males;
                        $district->population_count = $data2->totals;
                        $district->population_year = $data2->date->format("Y");
                        
                        if ($district->area > 0)
                            $district->population_density = ($district->population_count / $district->area);
                        
                        unset($data2);
                    }
                }
                                
                $districts[$district_hash] = $district;
            }
        }
        
        unset($german_districts_area);
        unset($german_districts_population);        

        // Fifth, get the RKI nowcasting data and push the latest entry to the germany object
        $max_timestamp = -1;
        $max_data = null;

        $esteem_reproduction_r = 0;
        $lower_reproduction_r = 0;
        $upper_reproduction_r = 0;

        $esteem_7day_r_value = 0;
        $lower_7day_r_value = 0;
        $upper_7day_r_value = 0;
        
        $nowcast = $this->rki_nowcast->handler->get_data();
        
        foreach ($nowcast as $id => $data)
        {
            $ts = strtotime($data->timestamp_represent);
            
            foreach ($data as $key => $val)
            {
                switch ($key)
                {
                    case "esteem_reproduction_r":
                        if ($val > 0) $esteem_reproduction_r = $val; elseif ($val == 0) $val = $esteem_reproduction_r;
                        break;
                    case "lower_reproduction_r":
                        if ($val > 0) $lower_reproduction_r = $val; elseif ($val == 0) $val = $lower_reproduction_r;
                        break;
                    case "upper_reproduction_r":
                        if ($val > 0) $upper_reproduction_r = $val; elseif ($val == 0) $val = $upper_reproduction_r;
                        break;
                    case "esteem_7day_r_value":
                        if ($val > 0) $esteem_7day_r_value = $val; elseif ($val == 0) $val = $esteem_7day_r_value;
                        break;
                    case "lower_7day_r_value":
                        if ($val > 0) $lower_7day_r_value = $val; elseif ($val == 0) $val = $lower_7day_r_value;
                        break;
                    case "upper_7day_r_value":
                        if ($val > 0) $upper_7day_r_value = $val; elseif ($val == 0) $val = $upper_7day_r_value;
                        break;
                }
                
                $nowcast[$id]->$key = $val;
            }
                
            if ($ts > $max_timestamp)
            {
                $max_timestamp = $ts;
                $max_data = clone $data;
            }
        }
        
        unset($nowcast);
        
        foreach ($max_data as $key => $val)
        {
            if ($key == "timestamp_represent")
                continue;
                
            if ($key == "date_rep")
                $key = "date_nowcast";
                
            $germany->$key = $val;
        }
        
        unset($max_data);
        
        // Force update of the "germany" object
        $countries[$germany_hash] = $germany;
        
        $this->continents = $continents;
        $this->countries = $countries;
        $this->states = $states;
        $this->districts = $districts;
        $this->locations = $locations;
        
        // Free the memory, which is no longer need (if hold data is not requested)
        if (!$hold_data)
        {
            $this->gen_territory_area->handler->free();
            $this->gen_territory_district_area->handler->free();
            $this->gen_population->handler->free();
            $this->gen_population_by_state->handler->free();
            $this->gen_population_by_district->handler->free();
        }
    
        return true;
    }
    
    public function calculate_dataset_fields($cases)
    {
        if (!is_array($cases))
            return null;
        
        $top7 = 8;
        $top14 = 15;
        $top7_smoothed = 11;
        $top14_smoothed = 18;
                
        $incidence7 = 0;
        $incidence14 = 0;

        $incidence7_smoothed = 0;
        $incidence14_smoothed = 0;
                
        $casecount = 0;
        $casecount_smoothed = 0;
                                        
        if (count($cases) < 8)
            $top7 = count($cases) - 1;
                    
        if (count($cases) < 15)
            $top14 = count($cases) - 1;
                    
        if (count($cases) < 11)
            $top7_smoothed = count($cases) - 1;
                
        if (count($cases) < 18)
            $top14_smoothed = count($cases) - 1;
            
        $casebase = 0;
        $casebase_smoothed = 3;
                
        if ((count($cases) + 1) < $casebase_smoothed)
            $casebase_smoothed = (count($cases) - 1);

        for ($i = $casebase; $i < $top7; $i++)
            $incidence7 += $cases[$i];
                            
        for ($i = $casebase; $i < $top14; $i++)
            $incidence14 += $cases[$i];
                            
        for ($i = $casebase_smoothed; $i < $top7_smoothed; $i++)
            $incidence7_smoothed += $cases[$i];
                            
        for ($i = $casebase_smoothed; $i < $top14_smoothed; $i++)
            $incidence14_smoothed += $cases[$i];
                            
        $casecount = $cases[0];
                
        if (count($cases) > 3)
            $casecount_smoothed = $cases[3];
        else
            $casecount_smoothed = count($cases);
                                    
        $exp7 = (($incidence7 - $casecount) / 6);
        $exp14 = (($incidence14 - $casecount) / 13);
                
        if ($exp7 == 0)
            $exp7 = 1;
                
        if ($exp14 == 0)
            $exp14 = 1;
                
        $exp7_smoothed = (($incidence7_smoothed - $casecount_smoothed) / 6);
        $exp14_smoothed = (($incidence14_smoothed - $casecount_smoothed) / 13);
                                
        if ($exp7_smoothed == 0)
            $exp7_smoothed = 1;
                
        if ($exp14_smoothed == 0)
            $exp14_smoothed = 1;
        
        $result = new \stdClass;
                
        $result->incidence_7day = $incidence7 / 7;
        $result->incidence_14day = $incidence14 / 14;
                
        $result->incidence_7day_smoothed = $incidence7_smoothed / 7;
        $result->incidence_14day_smoothed = $incidence14_smoothed / 14;
                
        $result->exponence_7day = ($casecount / $exp7);
        $result->exponence_14day = ($casecount / $exp14);

        $result->exponence_7day_smoothed = ($casecount_smoothed / $exp7_smoothed);
        $result->exponence_14day_smoothed = ($casecount_smoothed / $exp14_smoothed);
        
        return $result;    
    }
    
    public function master_datasets($hold_data = false)
    {
        // After stores are loaded, create the data pool with common fields
        $datasets = array();
        
        if ($this->stores_loaded_count < 10)
            return false;
            
        // Create a template
        $tmpl = new \stdClass;
        $tmpl->dataset_hash = null;
        $tmpl->country_hash = null;
        $tmpl->continent_hash = null;
        $tmpl->day_of_week = null;
        $tmpl->day = null;
        $tmpl->month = null;
        $tmpl->year = null;
        $tmpl->cases = null;
        $tmpl->deaths = null;
        $tmpl->timestamp_represent = null;
        
        $filter = self::get_infocast_filter("inner");
        
        foreach ($filter as $key => $type)
            $tmpl->$key = null;
            
        $dataset_index = array();
            
        foreach ($this->eu_datacast->handler->get_data()->records as $id => $record)
        {
            $country_hash = self::hash_name($record->country);
            $continent_hash = self::hash_name($record->continent);
            $dataset_hash = md5(self::hash_name($record->country).$record->date_rep);
            
            if (!isset($datasets[$dataset_hash]))
                $dataset = clone $tmpl;
            else
                $dataset = $datasets[$dataset_hash];
                
            $dataset->dataset_hash = $dataset_hash;
            $dataset->country_hash = $country_hash;
            $dataset->continent_hash = $continent_hash;
            $dataset->day_of_week = $record->day_of_week;
            $dataset->day = $record->day;
            $dataset->month = $record->month;
            $dataset->year = $record->year;
            $dataset->cases = $record->cases;
            $dataset->deaths = $record->deaths;
            $dataset->timestamp_represent = $record->timestamp_represent;
            
            $index = $dataset->continent_hash.$dataset->country_hash;
            
            if (!isset($dataset_index[$index]))
                $dataset_index[$index] = array();
                
            $date = date("Ymd", strtotime($record->timestamp_represent));
                
            $dataset_index[$index][$date] = $dataset_hash;
            
            krsort($dataset_index[$index]);
            
            $datasets[$dataset_hash] = $dataset;
        }
        
        foreach ($dataset_index as $index => $data)
        {
            $cases = array();
            
            foreach ($data as $date => $hash)
            {
                foreach ($data as $date2 => $hash2)
                {
                    if ($date2 < $date)
                        continue;
                        
                    array_push($cases, $datasets[$hash2]->cases);
                
                    if (count($cases) == 18)
                    {
                        break;
                    }
                }
                
                $result = $this->calculate_dataset_fields($cases);
                
                if (is_object($result))
                {
                    foreach ($result as $key => $val)
                        $datasets[$hash]->$key = $val;
                }
            }
        }
                    
        foreach ($this->cov_infocast->handler->get_data() as $country_code => $record)
        {
            if (!isset($record->continent))
                continue;
                
            $country_hash = self::hash_name($record->location);
            $continent_hash = self::hash_name($record->continent);
            
            foreach ($record->data as $data)
            {
                $dataset_hash = md5(self::hash_name($record->location).$data->date);
                $dataset_created = false;
                
                if (!isset($datasets[$dataset_hash]))
                {
                    $dataset = clone $tmpl;
                    $dataset_created = true;
                }
                else
                {
                    $dataset = $datasets[$dataset_hash];
                }
        
                if ($dataset_created)
                {
                    $ts = strtotime($data->date." 23:59:59");
                        
                    $dataset->dataset_hash = $dataset_hash;
                    $dataset->country_hash = $country_hash;
                    $dataset->continent_hash = $continent_hash;
                    $dataset->day_of_week = date("w", $ts);
                    $dataset->day = date("j", $ts);
                    $dataset->month = date("m", $ts);
                    $dataset->year = date("Y", $ts);
                    $dataset->timestamp_represent = date("Y-m-d H:i:s", $ts);
                }
                
                foreach ($filter as $key => $type)
                {
                    if (!isset($data->$key))
                        continue;
                        
                    if ($key == "date")
                        continue;
                        
                    $dataset->$key = $data->$key;
                }
                
                $datasets[$dataset_hash] = $dataset;
            }
        }
        
        // Free the memory, which is no longer need (if hold data is not requested)
        if (!$hold_data)
        {
            $this->eu_datacast->handler->free();
            $this->cov_infocast->handler->free();
        }
        
        $this->datasets = $datasets;

        return true;
    }
    
    public function district_map($district_name, &$mapped = false)
    {
        // Ohh yes, we need this map, because of the well-payed germans, which are unable to use a common standard for district names (or unique ids)
        // But see yourself: on the left the stuff send in testresults and on the right the stuff coming from DESTATIS
        // Also we see a special case for Berlin! They splitted Berlin into different parts. Nowhere in germany they did the same thing.
        // Because of non existing Berlin Neukölln or Lichtenberg or so on as a district, we are forced to combine them into the district BERLIN.
        // This is a good example on how they work and how statistics are obfuscated. Without this mapping, we would miss many datasets and things go wrong.
        
        $map = array(
            "Mülheim a.d.Ruhr" => "Mülheim an der Ruhr",
            "Altenkirchen" => "Altenkirchen (Westerwald)",
            "Bitburg-Prüm" => "Eifelkreis Bitburg-Prüm",
            "Frankenthal" => "Frankenthal (Pfalz)",
            "Landau i.d.Pfalz" => "Landau in der Pfalz",
            "Ludwigshafen" => "Ludwigshafen am Rhein",
            "Neustadt a.d.Weinstraße" => "Neustadt an der Weinstraße",
            "Freiburg i.Breisgau" => "Freiburg im Breisgau",
            "Landsberg a.Lech" => "Landsberg am Lech",
            "Mühldorf a.Inn" => "Mühldorf am Inn",
            "Pfaffenhofen a.d.Ilm" => "Pfaffenhofen an der Ilm",
            "Weiden i.d.OPf." => "Weiden in der Oberpfalz",
            "Neumarkt i.d.OPf." => "Neumarkt in der Oberpfalz",
            "Neustadt a.d.Waldnaab" => "Neustadt an der Waldnaab",
            "Wunsiedel i.Fichtelgebirge" => "Wunsiedel im Fichtelgebirge",
            "Neustadt a.d.Aisch-Bad Windsheim" => "Neustadt an der Aisch-Bad Windsheim",
            "Kempten" => "Kempten (Allgäu)",
            "Dillingen a.d.Donau" => "Dillingen an der Donau",
            "Lindau" => "Lindau (Bodensee)",
            "Stadtverband Saarbrücken" => "Regionalverband Saarbrücken",
            "Berlin Mitte" => "Berlin",
            "Berlin Friedrichshain-Kreuzberg" => "Berlin",
            "Berlin Pankow" => "Berlin",
            "Berlin Charlottenburg-Wilmersdorf" => "Berlin",
            "Berlin Spandau" => "Berlin",
            "Berlin Steglitz-Zehlendorf" => "Berlin",
            "Berlin Tempelhof-Schöneberg" => "Berlin",
            "Berlin Neukölln" => "Berlin",
            "Berlin Treptow-Köpenick" => "Berlin",
            "Berlin Marzahn-Hellersdorf" => "Berlin",
            "Berlin Lichtenberg" => "Berlin",
            "Berlin Reinickendorf" => "Berlin",
            "Brandenburg a.d.Havel" => "Brandenburg an der Havel",
            "Halle" => "Halle (Saale)"
        );
        
        $mapped = true;
        
        if (isset($map[$district_name]))
            return $map[$district_name];
            
        $mapped = false;
            
        return $district_name;
    }
    
    public function master_testresults($hold_data = false, &$unknown_states = null, &$unknown_districts = null)
    {
        // After stores are loaded, create the testresult pool with common fields
        $testresults = array();
        
        if ($this->stores_loaded_count < 10)
            return false;
            
        $europe_hash = self::hash_name("Europe");
        $germany_hash = self::hash_name("Germany");
        
        // Get the country and zero some fields
        $germany = $this->countries[$germany_hash];
        
        $germany->cases_count = 0;
        $germany->deaths_count = 0;
        $germany->recovered_count = 0;
        $germany->cases_min = 0; 
        $germany->deaths_min = 0;
        $germany->recovered_min = 0;
        $germany->cases_max = 0; 
        $germany->deaths_max = 0;
        $germany->recovered_max = 0;
        
        // Create a dataset template
        $tmpl = new \stdClass;
        $tmpl->dataset_hash = null;
        $tmpl->district_hash = null;
        $tmpl->state_hash = null;
        $tmpl->country_hash = null;
        $tmpl->continent_hash = null;
        $tmpl->day_of_week = null;
        $tmpl->day = null;
        $tmpl->month = null;
        $tmpl->year = null;
        $tmpl->cases = null;
        $tmpl->deaths = null;
        $tmpl->recovered = null;
        $tmpl->timestamp_represent = null;
        
        $filter = self::get_infocast_filter("inner");
        
        foreach ($filter as $key => $type)
            $tmpl->$key = null;
            
        $datasets = array();
        
        $unknown_districts = array();
        $unknown_states = array();
                
        // No need for templates here, just clone data and add the hashes
        foreach($this->rki_positive->handler->get_data() as $data)
        {
            // Before we do anything, we must map the district name!
            $data->district_name = $this->district_map($data->district_name);
        
            // The result hash must have another part to be unique, date is not sufficient here
            // So maybe its a good idea to use the foreign identifier, delivered by the data itself
            $result_hash = md5(self::hash_name($data->district_name).$data->date_rep."#".$data->foreign_identifier);

            $district_hash = self::hash_name($data->state.$data->district_name);
            $state_hash = self::hash_name($data->state);
            
            if (!isset($this->districts[$district_hash]))
            {
                // Log unknown districts
                if (!isset($unknown_districts[$district_hash]))
                    $unknown_districts[$district_hash] = $data->district_name;
            }
            
            if (!isset($this->states[$state_hash]))
            {
                // Log unknown states
                if (!isset($unknown_states[$state_hash]))
                    $unknown_states[$state_hash] = $data->state;
            }
            
            $testresult = clone $data;
            $testresult->result_hash = $result_hash;
            $testresult->district_hash = $district_hash;
            $testresult->state_hash = $state_hash;
            $testresult->country_hash = $germany_hash;
            $testresult->continent_hash = $europe_hash;
            
            $index = $testresult->district_hash;
            $ts = strtotime($testresult->timestamp_represent);
            $date = date("Ymd", $ts);

            // Create or update dateset
            if (!isset($datasets[$index][$date]))
            {
                $dataset = clone $tmpl;
                
                $dataset->dataset_hash = md5("positive#".self::hash_name($data->district_name).$data->date_rep);
                $dataset->district_hash = $district_hash;
                $dataset->state_hash = $state_hash;
                $dataset->country_hash = $germany_hash;
                $dataset->continent_hash = $europe_hash;
                $dataset->day_of_week = $data->day_of_week;
                $dataset->day = $data->day;
                $dataset->month = $data->month;
                $dataset->year = $data->year;
                $dataset->cases = 0;
                $dataset->deaths = 0;
                $dataset->recovered = 0;
                $dataset->new_cases = 0;
                $dataset->new_deaths = 0;
                $dataset->new_recovered = 0;
                $dataset->new_cases_smoothed = 0;
                $dataset->new_deaths_smoothed = 0;
                $dataset->new_recovered_smoothed = 0;
                $dataset->timestamp_represent = $data->timestamp_represent;
                $dataset->date_rep = $data->date_rep;
            }
            else
            {
                $dataset = $datasets[$index][$date];
            }
            
            $dataset->cases += $data->cases_count;
            $dataset->deaths += $data->deaths_count;
            $dataset->recovered += $data->recovered_count;
            
            $dataset->new_cases += $data->cases_new;
            $dataset->new_deaths += $data->deaths_new;
            $dataset->new_recovered += $data->recovered_new;
            
            if ($data->flag_is_disease_beginning)
            {
                $dataset->new_cases_smoothed += $data->cases_new;
                $dataset->new_deaths_smoothed += $data->deaths_new;
                $dataset->new_recovered_smoothed += $data->recovered_new;
            }
                        
            if (!isset($datasets[$index]))
                $datasets[$index] = array();
                                
            $datasets[$index][$date] = $dataset;
            $testresults[$result_hash] = $testresult;
            
            ksort($datasets[$index]);
        }

        // Define a "million" to prevent typos
        $mil = 1000000;
        
        // Merge district datasets and main datasets
        foreach ($datasets as $index => $data)
        {
            $cases = 0;
            $deaths = 0;
            $recovered = 0;
            
            $cases_last18 = array();
            
            // Zero fill cases array
            for ($i = 0; $i < 19; $i++)
                $cases_last18[$i] = 0;
            
            // We need the population from the corresponding location object
            if (isset($this->districts[$index]))
            {
                $district = $this->districts[$index];
    
                // And also its parent
                if (isset($this->states[$district->state_hash]))
                    $state = $this->states[$district->state_hash];
                else
                    $state = null;
            }
            else
            {
                // Spooky, the corresponding district wasnt found.
                // Seems, that the district is not yet existing...
                                
                $district = null;
                $state = null;
            }
                
            if (is_object($district))
            {
                if (isset($district->population_count))
                    $population = $district->population_count;
                else
                    $population = 0;
            }
            else
            {
                $population = 0;
            }
            
            foreach ($data as $date => $dataset)
            {
                $cases += $dataset->cases;
                $deaths += $dataset->deaths;
                $recovered += $dataset->recovered;
                
                $dataset->total_cases = $cases;
                $dataset->total_deaths = $deaths;
                $dataset->total_recovered = $recovered;
                
                $dataset->total_cases_per_million = ($dataset->total_cases / $mil * $population);
                $dataset->total_deaths_per_million = ($dataset->total_deaths / $mil * $population);
                $dataset->total_recovered_per_million = ($dataset->total_recovered / $mil * $population);
                
                $dataset->new_cases_per_million = ($dataset->new_cases / $mil * $population);
                $dataset->new_deaths_per_million = ($dataset->new_deaths / $mil * $population);
                $dataset->new_recovered_per_million = ($dataset->new_recovered / $mil * $population);
                
                $dataset->new_cases_smoothed_per_million = ($dataset->new_cases_smoothed / $mil * $population);
                $dataset->new_deaths_smoothed_per_million = ($dataset->new_deaths_smoothed / $mil * $population);
                $dataset->new_recovered_smoothed_per_million = ($dataset->new_recovered_smoothed / $mil * $population);
                
                array_push($cases_last18, $cases);
                array_shift($cases_last18);
                                
                $result = $this->calculate_dataset_fields($cases_last18);
                
                if (is_object($result))
                {
                    foreach ($result as $key => $val)
                        $dataset->$key = $val;
                }
                
                $dataset_hash = md5($dataset->district_hash.$dataset->date_rep);
                                
                if (isset($this->datasets[$dataset_hash]))
                {
                    // Override existing dataset with all non-null values
                    foreach ($dataset as $key => $val)
                    {
                        if ($val !== null)
                        {
                            $this->datasets[$dataset_hash]->$key = $val;
                        }                        
                    }
                    
                    continue;
                }
                
                $this->datasets[$dataset_hash] = $dataset;
                
                if (is_object($district))
                {
                    // Now, push the results to corresponding district and its parent locations                
                    $district->cases_count = $cases;
                    $district->deaths_count = $deaths;
                    $district->recovered_count = $recovered;
                    
                    if ($district->total_cases > 0)
                    {
                        $district->contamination_total = ($district->population_count / $district->total_cases);
                        $district->contamination_rundays = ((time() - $district->timestamp_min) / 60 / 60 / 24);
                        
                        if ($district->contamination_rundays > 0)
                            $district->contamination_per_day = ($district->total_cases / $district->contamination_rundays); 
                        else
                            $district->contamination_per_day = 0;
   
                        if ($district->contamination_per_day > 0)
                            $district->contamination_target = (($district->population_count - $district->total_cases) / $district->contamination_per_day);
                    }
                    
                    if ($district->area > 0)
                    {
                        $district->infection_density = ($district->cases_count / $district->area);
                        $district->infection_area = (1 / $district->infection_density);
                        $district->infection_probability = (100 / ($district->infection_area * $district->population_density));
                    }
                    
                    // Due to missing data in retrieved files, it could be that some fields are missing right now
                    $fix_missing = array(
                        "recovered_min",
                        "recovered_max",
                        "total_cases",
                        "total_deaths",
                        "total_recovered",
                        "new_cases",
                        "new_deaths",
                        "new_recovered",
                        "new_cases_smoothed",
                        "new_deaths_smoothed",
                        "new_recovered_smoothed"
                    );
                    
                    foreach ($fix_missing as $fix)
                    {
                        if (!isset($district->$fix))
                            $district->$fix = 0;
                            
                        if (!isset($state->$fix))
                            $state->$fix = 0;
                    }
                    
                    if ($district->cases_min > $cases)
                        $district->cases_min = $cases;
                    if ($district->cases_max < $cases)
                        $district->cases_max = $cases;
                    if ($district->deaths_min > $deaths)
                        $district->deaths_min = $deaths;
                    if ($district->deaths_max < $deaths)
                        $district->deaths_max = $deaths;
                    if ($district->recovered_min > $recovered)
                        $district->recovered_min = $recovered;
                    if ($district->recovered_max < $recovered)
                        $district->recovered_max = $recovered;
                        
                    $district->total_cases = $cases;
                    $district->total_deaths = $deaths;
                    $district->total_recovered = $recovered;
                    
                    $district->total_cases_per_million = ($district->total_cases / $mil * $population);
                    $district->total_deaths_per_million = ($district->total_deaths / $mil * $population);
                    $district->total_recovered_per_million = ($district->total_recovered / $mil * $population);
                    
                    $district->new_cases_per_million = ($district->new_cases / $mil * $population);
                    $district->new_deaths_per_million = ($district->new_deaths / $mil * $population);
                    $district->new_recovered_per_million = ($district->new_recovered / $mil * $population);
                    
                    $district->new_cases_smoothed_per_million = ($district->new_cases_smoothed / $mil * $population);
                    $district->new_deaths_smoothed_per_million = ($district->new_deaths_smoothed / $mil * $population);
                    $district->new_recovered_smoothed_per_million = ($district->new_recovered_smoothed / $mil * $population);
                    
                    if (is_object($state))
                    {
                        $state->cases_count = $cases;
                        $state->deaths_count = $deaths;
                        $state->recovered_count = $recovered;
                        
                        if ($state->total_cases > 0)
                        {
                            $state->contamination_total = ($state->population_count / $state->total_cases);
                            $state->contamination_rundays = ((time() - $state->timestamp_min) / 60 / 60 / 24);
                            
                            if ($state->contamination_rundays > 0)
                                $state->contamination_per_day = ($state->total_cases / $state->contamination_rundays); 
                            else
                                $state->contamination_per_day = 0;
       
                            if ($state->contamination_per_day > 0)
                                $state->contamination_target = (($state->population_count - $state->total_cases) / $state->contamination_per_day);
                        }
                    
                        if ($state->area > 0)
                        {
                            $state->infection_density = ($state->cases_count / $state->area);
                            $state->infection_area = (1 / $state->infection_density);
                            $state->infection_probability = (100 / ($state->infection_area * $state->population_density));
                        }
                    
                        if ($state->cases_min > $cases)
                            $state->cases_min = $cases;
                        if ($state->cases_max < $cases)
                            $state->cases_max = $cases;
                        if ($state->deaths_min > $deaths)
                            $state->deaths_min = $deaths;
                        if ($state->deaths_max < $deaths)
                            $state->deaths_max = $deaths;
                        if ($state->recovered_min > $recovered)
                            $state->recovered_min = $recovered;
                        if ($state->recovered_max < $recovered)
                            $state->recovered_max = $recovered;
                            
                        $state->total_cases = $cases;
                        $state->total_deaths = $deaths;
                        $state->total_recovered = $recovered;
                        
                        $state->total_cases_per_million = ($district->total_cases / $mil * $state->population_count);
                        $state->total_deaths_per_million = ($district->total_deaths / $mil * $state->population_count);
                        $state->total_recovered_per_million = ($district->total_recovered / $mil * $state->population_count);
                        
                        $state->new_cases_per_million = ($district->new_cases / $mil * $state->population_count);
                        $state->new_deaths_per_million = ($district->new_deaths / $mil * $state->population_count);
                        $state->new_recovered_per_million = ($district->new_recovered / $mil * $state->population_count);
                    
                        $state->new_cases_smoothed_per_million = ($district->new_cases_smoothed / $mil * $state->population_count);
                        $state->new_deaths_smoothed_per_million = ($district->new_deaths_smoothed / $mil * $state->population_count);
                        $state->new_recovered_smoothed_per_million = ($district->new_recovered_smoothed / $mil * $state->population_count);
                        
                        if (is_object($germany))
                        {
                            $germany->cases_count = $cases;
                            $germany->deaths_count = $deaths;
                            $germany->recovered_count = $recovered;
                            
                            if ($germany->total_cases > 0)
                            {
                                $germany->contamination_total = ($germany->population_count / $germany->total_cases);
                                $germany->contamination_rundays = ((time() - $germany->timestamp_min) / 60 / 60 / 24);
                                
                                if ($germany->contamination_rundays > 0)
                                    $germany->contamination_per_day = ($germany->total_cases / $germany->contamination_rundays); 
                                else
                                    $germany->contamination_per_day = 0;
           
                                if ($germany->contamination_per_day > 0)
                                    $germany->contamination_target = (($germany->population_count - $germany->total_cases) / $germany->contamination_per_day);
                            }
                            
                            if ($germany->area > 0)
                            {
                                $germany->infection_density = ($germany->cases_count / $germany->area);
                                $germany->infection_area = (1 / $germany->infection_density);
                                $germany->infection_probability = (100 / ($germany->infection_area * $germany->population_density));
                            }
                    
                            if ($germany->cases_min > $cases)
                                $germany->cases_min = $cases;
                            if ($germany->cases_max < $cases)
                                $germany->cases_max = $cases;
                            if ($germany->deaths_min > $deaths)
                                $germany->deaths_min = $deaths;
                            if ($germany->deaths_max < $deaths)
                                $germany->deaths_max = $deaths;
                            if ($germany->recovered_min > $recovered)
                                $germany->recovered_min = $recovered;
                            if ($germany->recovered_max < $recovered)
                                $germany->recovered_max = $recovered;        
                        }
                    }
                }
            }            
            
            if (is_object($state))
                $this->states[$district->state_hash] = $state;
            
            if (is_object($district))
                $this->districts[$index] = $district;
        }
                        
        // Free the memory, which is no longer need (if hold data is not requested)
        if (!$hold_data)
            $this->rki_positive->handler->free();
        
        $this->testresults = $testresults;
        
        return true;        
    }
    
    public function save_testresults(&$count = null, &$any = null, &$errors = null)
    {
        $count = 0;
        $any = 0;
        
        if (!is_array($this->testresults))
            return null;
            
        $this->database_transaction_begin("save_testresult");
        
        $errors = array();
        
        foreach ($this->testresults as $hash => $obj)
        {
            $db_obj = $this->database->new_testresult();

            foreach ($obj as $key => $val)
            {
                switch ($key)
                {
                    case "age_group":
                        $db_obj->age_group_lower = $obj->age_group->lower;
                        $db_obj->age_group_upper = $obj->age_group->upper;
                        continue(2);
                    case "age_group2":
                        $db_obj->age_group2_lower = $obj->age_group2->lower;
                        $db_obj->age_group2_upper = $obj->age_group2->upper;
                        continue(2);
                    case "district_id":
                    case "district_type":
                    case "district_name":
                    case "district_fullname":
                    case "state":
                    case "state_id":
                        continue(2);
                }
                
                $db_obj->$key = $val;
            }
                    
            if ($db_obj->save())
                $count++;
            else
                array_push($errors, $db_obj->get_error());

            $any++;
        }
        
        $this->database_transaction_commit("save_testresult");
        
        return $count;
    }
    
    public function save_datasets(&$count = null, &$any = null)
    {
        $count = 0;
        $any = 0;
        
        if (!is_array($this->datasets))
            return null;
            
        $this->database_transaction_begin("save_dataset");
        
        foreach ($this->datasets as $hash => $obj)
        {
            $db_obj = $this->database->new_dataset();

            foreach ($obj as $key => $val)
                $db_obj->$key = $val;
                    
            if ($db_obj->save())
                $count++;
            else
                array_push($errors, $db_obj->get_error());

            $any++;
        }
        
        $this->database_transaction_commit("save_dataset");
        
        return $count;
    }
    
    public function save_nowcasts(&$count = null, &$any = null, &$errors = null)
    {
        $count = 0;
        $any = 0;
        
        if (!is_array($this->rki_nowcast->handler->get_data()))
            return null;
            
        $this->database_transaction_begin("save_nowcast");
        
        $esteem_reproduction_r = 0;
        $lower_reproduction_r = 0;
        $upper_reproduction_r = 0;
        
        $esteem_7day_r_value = 0;
        $lower_7day_r_value = 0;
        $upper_7day_r_value = 0;
        
        $europe_hash = self::hash_name("Europe");
        $germany_hash = self::hash_name("Germany");        
        
        $errors = array();
        
        foreach ($this->rki_nowcast->handler->get_data() as $obj)
        {            
            $db_obj = $this->database->new_nowcast();
            
            $db_obj->continent_hash = $europe_hash;
            $db_obj->country_hash = $germany_hash;
            
            // Set casted r values flag to 0
            $obj->flag_casted_r_values = 0;

            foreach ($obj as $key => $val)
            {
                switch ($key)
                {
                    case "esteem_reproduction_r":
                        if ($val > 0) $esteem_reproduction_r = $val; elseif ($val == 0) { $obj->flag_casted_r_values = 1; $val = $esteem_reproduction_r; }
                        break;
                    case "lower_reproduction_r":
                        if ($val > 0) $lower_reproduction_r = $val; elseif ($val == 0) { $obj->flag_casted_r_values = 1; $val = $lower_reproduction_r; }
                        break;
                    case "upper_reproduction_r":
                        if ($val > 0) $upper_reproduction_r = $val; elseif ($val == 0) { $obj->flag_casted_r_values = 1; $val = $upper_reproduction_r; }
                        break;
                    case "esteem_7day_r_value":
                        if ($val > 0) $esteem_7day_r_value = $val; elseif ($val == 0) { $obj->flag_casted_r_values = 1; $val = $esteem_7day_r_value; }
                        break;
                    case "lower_7day_r_value":
                        if ($val > 0) $lower_7day_r_value = $val; elseif ($val == 0) { $obj->flag_casted_r_values = 1; $val = $lower_7day_r_value; }
                        break;
                    case "upper_7day_r_value":
                        if ($val > 0) $upper_7day_r_value = $val; elseif ($val == 0) { $obj->flag_casted_r_values = 1; $val = $upper_7day_r_value; }
                        break;
                }
                
                $db_obj->$key = $val;
            }
                    
            if ($db_obj->save())
                $count++;
            else
                array_push($errors, $db_obj->get_error());

            $any++;
        }
        
        $this->database_transaction_commit("save_nowcast");
        
        return $count;
    }
    
    public function save_locations(&$count = null, &$any = null, &$errors = null)
    {
        $stores = array(
            "continents",
            "countries",
            "states",
            "districts",
            "locations"
        );
        
        $errors = array();
        $count = 0;
        $any = 0;
        
        $this->database_transaction_begin("save_location");
        
        foreach ($stores as $store)
        {
            if (!is_array($this->$store))
                continue;
                
            if (!count($this->$store))
                continue;
                
            foreach ($this->$store as $hash => $obj)
            {
                if (!is_object($obj))
                    continue;
                
                $db_obj = $this->database->new_location();
                
                foreach ($obj as $key => $val)
                    $db_obj->$key = $val;
                    
                if ($db_obj->save())
                    $count++;
                else
                    array_push($errors, $db_obj->get_error());
                
                $any++;
            }
        }
        
        $this->database_transaction_commit("save_location");

        return $count;        
    }
    
    public function load_stores($cache_timeout = 14400)
    {
        // We must speed things up, so the new idea is to preload all stores and make the primary calculations in memory
        // This will consume much more ram (approx. 1g, okay its more than 3g), but will speed up calculations by ~80%
        
        $this->stores_loaded_bytes = 0;
        $this->stores_loaded_count = 0;
        
        try
        {
            $this->stores_loaded_bytes += $this->retrieve_eu_datacast($cache_timeout);
            $this->stores_loaded_count++;
            
            $this->stores_loaded_bytes += $this->retrieve_cov_infocast($cache_timeout);
            $this->stores_loaded_count++;
                       
            $this->stores_loaded_bytes += $this->retrieve_rki_rssfeed($cache_timeout);
            $this->stores_loaded_count++;
                       
            $this->stores_loaded_bytes += $this->retrieve_rki_nowcast($cache_timeout);
            $this->stores_loaded_count++;

            $this->stores_loaded_bytes += $this->retrieve_rki_positive($cache_timeout);
            $this->stores_loaded_count++;
                                   
            $this->stores_loaded_bytes += $this->retrieve_gen_territory_area($cache_timeout);
            $this->stores_loaded_count++;
                       
            $this->stores_loaded_bytes += $this->retrieve_gen_territory_district_area($cache_timeout);
            $this->stores_loaded_count++;
                       
            $this->stores_loaded_bytes += $this->retrieve_gen_population($cache_timeout);
            $this->stores_loaded_count++;
                       
            $this->stores_loaded_bytes += $this->retrieve_gen_population_by_state($cache_timeout);
            $this->stores_loaded_count++;
                       
            $this->stores_loaded_bytes += $this->retrieve_gen_population_by_district($cache_timeout);
            $this->stores_loaded_count++;                                   
        }
        catch(Exception $ex)
        {
            throw new Exception("Unable to retrieve data store.", 0, $ex);
        }
        
        return $this->stores_loaded_bytes;
    }
    
    function __construct($config = ".coronadatarc")
    {
        $this->config_file = $config;
        
        $this->config = new Config($this->config_file);
        
        $this->create_datastore();

        try
        {        
            $this->eu_datacast = $this->get_template(new DataHandler($this->config, $this->config->url_eu_datacast));
            $this->rki_positive = $this->get_template(new DataHandler($this->config, $this->config->url_rki_positive));
            $this->rki_nowcast = $this->get_template(new DataHandler($this->config, $this->config->url_rki_nowcast));
            $this->rki_rssfeed = $this->get_template(new DataHandler($this->config, $this->config->url_rki_rssfeed));
            $this->cov_infocast = $this->get_template(new DataHandler($this->config, $this->config->url_cov_infocast));
            
            $this->gen_territory_area = $this->get_template(new DataHandler($this->config, null, "territory", "area"));
            $this->gen_territory_district_area = $this->get_template(new DataHandler($this->config, null, "territory", "district_area"));
            $this->gen_population = $this->get_template(new DataHandler($this->config, null, "population", "total"));
            $this->gen_population_by_state = $this->get_template(new DataHandler($this->config, null, "population", "by_state"));
            $this->gen_population_by_district = $this->get_template(new DataHandler($this->config, null, "population", "by_district"));
        }
        catch (Exception $ex)
        {
            throw new Exception("Problem creating datahandler object", 0, $ex);
        }
        
        $this->transaction_name = null;
        
        try
        {
            $this->database = new Database($this->config_file);
            
            $this->database->init();
        }
        catch(Exception $ex)
        {
            throw new Exception("Problem creating database object", 0, $ex);
        }
    }
}
