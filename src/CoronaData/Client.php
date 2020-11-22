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
        
    private $eu_coviddata = null;    
    private $rki_positive = null;    
    private $rki_nowcast = null;    
    private $rki_rssfeed = null;    
    private $divi_intens = null;
    
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
    
    private $location_index = null;
    
    private $datasets = null;
    private $testresults = null;
    private $divis = null;
    
    public static function object_checksum($obj, $prefix = null)
    {
        if ((!is_object($obj)) && (!is_array($obj)))
            return null;
            
        $buffer = array();
            
        foreach ($obj as $key => $val)
        {
            if ((is_object($val)) || (is_array($val)))
                array_push($buffer, $key.":".self::object_checksum($val));
            else
                array_push($buffer, $key.":".$val);
        }
        
        return sha1($prefix.implode("\n", $buffer));
    }
    
    public static function result_object_merge(&$result, $obj)
    {
        if (!is_object($obj))
            return false;
            
        if (!is_object($result))
            return false;
            
        foreach ($obj as $key => $val)
            $result->$key = $val;
            
        return true;
    }

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
    
    public static function hash_name($prefix, $name, $suffix = null)
    {
        return md5($prefix."#".self::clean_str($name)."#".$suffix);
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
    
    private function retrieve_obj_data(\stdClass $obj, $transform = null, $cache_timeout = 14400, $target_filename = true, $target_compression_level = 9, $not_json_encoded = false, $force_content_type = null)
    {
       if ($retval = $obj->handler->retrieve($target_filename, $cache_timeout, $target_compression_level, $not_json_encoded, $force_content_type))
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
    
    public function retrieve_eu_coviddata($cache_timeout = 14400)
    {
       return $this->retrieve_obj_data($this->eu_coviddata, "transform_eu_coviddata", $cache_timeout);
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
    
    public function retrieve_divi_intens($cache_timeout = 14400)
    {
       return $this->retrieve_obj_data($this->divi_intens, "transform_divi_intens", $cache_timeout, true, 9, false, "application/csv");
    }
    
    public function export_eu_coviddata(&$length = null, &$timestamp = null)
    {
        $length = $this->eu_coviddata->handler->get_length();
        $timestamp = $this->eu_coviddata->handler->get_timestamp();
        
        return $this->eu_coviddata->handler->get_data();
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
    
    public function export_divi_intens(&$length = null, &$timestamp = null)
    {
        $length = $this->divi_intens->handler->get_length();
        $timestamp = $this->divi_intens->handler->get_timestamp();
        
        return $this->divi_intens->handler->get_data();
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
    
    public function get_eu_coviddata()
    {
        return $this->eu_coviddata;
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
    
    public function get_divi_intens()
    {
       return $this->divi_intens; 
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
        $divis = array();
        
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
        $tmpl->location_id = null;
        $tmpl->location_hash = null;
        $tmpl->location_name = null;
        $tmpl->location_type = null;
        $tmpl->location_tags = null;
        $tmpl->geo_id = null;
        $tmpl->population_year = 0;
        $tmpl->population_count = 0;
        $tmpl->population_females = 0;
        $tmpl->population_males = 0;
        $tmpl->area = 0;
        $tmpl->data_checksum = null;

        // First, we use the EU coviddata, but skip any kind of cases data.
        // This will be a static table in future versions, to speed things up massivly.
        foreach ($this->eu_coviddata->handler->get_data()->records as $id => $record)
        {
            $continent_hash = self::hash_name("continent", $record->continent);
            $country_hash = self::hash_name("country", $record->country, $continent_hash);
            
            if (!isset($continents[$continent_hash]))                
                $continent = clone $tmpl;
            else
                $continent = $continents[$continent_hash];
            
            $continent->location_hash = self::hash_name("location", "continent", $continent_hash);
            $continent->location_tags = "continent, ".$record->continent;
            $continent->continent_id = self::threeletter_encode($record->continent);
            $continent->geo_id = substr($continent->continent_id, 0, 2);
            $continent->continent_hash = $continent_hash;
            $continent->continent_name = $record->continent;
            $continent->location_type = 'continent';
            $continent->population_count += $record->population;
            
            if ($continent->population_year > $record->population_year)
                $continent->population_year = $record->population_year;
            
            if (!isset($countries[$country_hash]))
                $country = clone $tmpl;
            else
                $country = $countries[$country_hash];
                
            $country->location_hash = self::hash_name("location", "country", $country_hash);
            $country->location_tags = "country, ".$record->continent.", ".$record->country;
            $country->continent_id = self::threeletter_encode($record->continent);
            $country->continent_hash = $continent_hash;
            $country->continent_name = $record->continent;
            $country->country_id = $record->country_code ?: self::threeletter_encode($record->country);
            $country->country_hash = $country_hash;
            $country->country_name = $record->country;
            $country->location_type = 'country';
            $country->geo_id = $record->geo_id ?: substr($record->country, 0, 2);
            $country->population_count = $record->population;
            
            if ($country->population_year > $record->population_year)
                $country->population_year = $record->population_year;
                
            $country->data_checksum = self::object_checksum($country);
            $continent->data_checksum = self::object_checksum($continent);
            
            $countries[$country_hash] = $country;
            $continents[$continent_hash] = $continent;
        }
        
        // Second, we would use the OWID source, but this is disabled because of invalid data
        // We will skip this, until a new official data source is found.
        
        // Third, we add the german states
        $europe_hash = self::hash_name("continent", "Europe");
        $germany_hash = self::hash_name("country", "Germany", $europe_hash);
                
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
                $state_hash = self::hash_name("state", $state_name, $germany_hash);
                
                if (!isset($states[$state_hash]))
                    $state = clone $tmpl;
                else
                    $state = $states[$state_hash];
                
                $real_state_name = null;
                    
                $state->location_hash = self::hash_name("location", "state", $state_hash);
                $state->location_tags = "state, ".$europe->continent_name.", ".$germany->country_name.", ".$real_state_name ?: $state_name;
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
                
                $state->data_checksum = self::object_checksum($state);
                
                $states[$state_hash] = $state;
            }
        }
        
        unset($german_states_area);
        
        $german_states_population = $this->gen_population_by_state->handler->get_data()->states;

        if (is_array($german_states_population))
        {
            foreach ($german_states_population as $state_name => $data)
            {
                $state_hash = self::hash_name("state", $state_name, $germany_hash);
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
                        
                    $state->location_hash = self::hash_name("location", "state", $state_hash);
                    $state->location_tags = "state, ".$europe->continent_name.", ".$germany->country_name.", ".$real_state_name ?: $state_name;
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
                
                $state->data_checksum = self::object_checksum($state);
                
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
                $state_hash = self::hash_name("state", $state_name, $germany_hash);                
                $district_hash = self::hash_name("district", $data->name, $state_hash);
                
                if (!isset($states[$state_hash]))
                    continue;
                else
                    $state = $states[$state_hash];
                
                if (!isset($districts[$district_hash]))
                    $district = clone $tmpl;
                else
                    $district = $districts[$district_hash];
                    
                $district_index[$district_id] = $district_hash;
                    
                $district->location_hash = self::hash_name("location", "district", $district_hash);
                $district->location_tags = "district, ".$europe->continent_name.", ".$germany->country_name.", ".$state->state_name.", ".$data->type.", ".$data->name;
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
                
                $district->data_checksum = self::object_checksum($district);
                                
                $districts[$district_hash] = $district;
            }
        }
        
        // Recalculate the checksums for the germany and europe object
        $germany->data_checksum = self::object_checksum($germany);
        $europe->data_checksum = self::object_checksum($europe);
        
        // Fifth, merge the district informations into divi data
        // DEPRECATED! We will merge the data later into datasets
        /*
        $german_divi = $this->divi_intens->handler->get_data();

        if (is_array($german_divi))
        {
            foreach ($german_divi as $divi)
            {
                if (isset($district_index[$divi->district_id]))
                {
                    $district_hash = $district_index[$divi->district_id];
                    
                    if (!$district_hash)
                        continue;
                        
                    if (!isset($districts[$district_hash]))
                        continue;
                        
                    $district = $districts[$district_hash];
                    
                    $merge = array(
                        "district_hash",
                        "state_hash",
                        "country_hash",
                        "continent_hash"
                    );
                    
                    // Move district_id out of the way
                    $divi->geo_id = $divi->district_id;
                    
                    foreach ($merge as $key)
                    {
                        if (!isset($district->$key))
                            continue;
                            
                        $divi->$key = $district->$key;
                    }
                    
                    $divi->divi_hash = self::hash_name("divi", $district_hash, $divi->geo_id.$divi->date_rep);
                    $divi->beds_total = ($divi->beds_free + $divi->beds_occupied);
                    
                    // Remove no longer needed things
                    unset($divi->district_id);
                    unset($divi->state_id);
                    
                    // Write divi data to upstream locations
                    $district->divi_beds_free += $divi->beds_free;
                    $district->divi_beds_occupied += $divi->beds_occupied;
                    $district->divi_beds_total += $divi->beds_total;
                    $district->divi_cases_covid += $divi->cases_covid;
                    $district->divi_cases_covid_ventilated += $divi->cases_covid_ventilated;
                    $district->divi_reporting_areas += $divi->reporting_areas;
                    $district->divi_locations_count += $divi->locations_count;
                    
                    $states[$district->state_hash]->divi_beds_free += $divi->beds_free;
                    $states[$district->state_hash]->divi_beds_occupied += $divi->beds_occupied;
                    $states[$district->state_hash]->divi_beds_total += $divi->beds_total;
                    $states[$district->state_hash]->divi_cases_covid += $divi->cases_covid;
                    $states[$district->state_hash]->divi_cases_covid_ventilated += $divi->cases_covid_ventilated;
                    $states[$district->state_hash]->divi_reporting_areas += $divi->reporting_areas;
                    $states[$district->state_hash]->divi_locations_count += $divi->locations_count;

                    $countries[$district->country_hash]->divi_beds_free += $divi->beds_free;
                    $countries[$district->country_hash]->divi_beds_occupied += $divi->beds_occupied;
                    $countries[$district->country_hash]->divi_beds_total += $divi->beds_total;
                    $countries[$district->country_hash]->divi_cases_covid += $divi->cases_covid;
                    $countries[$district->country_hash]->divi_cases_covid_ventilated += $divi->cases_covid_ventilated;
                    $countries[$district->country_hash]->divi_reporting_areas += $divi->reporting_areas;
                    $countries[$district->country_hash]->divi_locations_count += $divi->locations_count;                    
                    
                    $divis[$divi->divi_hash] = $divi;
		}
            }
        }
                
        unset($german_divi);
        */
        
        unset($german_districts_area);
        unset($german_districts_population);        

        // Sixth, get the RKI nowcasting data and push the latest entry to the germany object
        // DEPRECATED: This will be moved to datasets
        /*
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
        */
        
        // Force update of the "germany" and "europe" objects
        $countries[$germany_hash] = $germany;
        $continents[$europe_hash] = $europe;
        
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
    
    public function get_last_x_days($cases, $deaths, $dates, $days = 7, $skip_days = 0, &$reproduction_available = null)
    {
        if ($days <= 0)
            return null;
            
        if (!is_array($cases))
            return null;
            
        if (!is_array($deaths))
            return null;
            
        if (!is_array($dates))
            return null;
            
        if ($skip_days < 0)
            return null;
            
        $reproduction_available = ((($days + $skip_days) <= count($cases)) ? true : false);
        $xindex = ($days + $skip_days);
        
        if (count($cases) < $xindex)
        {
            for ($i = count($cases); $i < $xindex; $i++)
            {
                $cases[$i] = (int)0;
            }
        }
    
        if (count($deaths) < $xindex)
        {
            for ($i = count($deaths); $i < $xindex; $i++)
            {
                $deaths[$i] = (int)0;
            }
        }
    
        if (count($dates) < $xindex)
        {
            for ($i = count($dates); $i < $xindex; $i++)
            {
                $dates[$i] = (int)0;
            }
        }
    
        $result = array();
        
        for ($i = 0; $i < $days; $i++)
        {
            $tmp = new \stdClass;
            $tmp->day = $i + 1;
            $tmp->date = (int)0;
            $tmp->cases = (int)0;
            $tmp->deaths = (int)0;
            $tmp->set_date = false;
            $tmp->set_case = false;
            $tmp->set_deaths = false;
            
            $result[$i] = $tmp;
        }
        
        $n = 0;
        
        for ($i = $skip_days; $i < $xindex; $i++)
        {
            if (!isset($cases[$i]))
                break;
                
            $result[$n]->cases = $cases[$i];
            $result[$n]->set_case = true;
            
            if (!isset($dates[$i]))
                break;
                
            $result[$n]->date = $dates[$i];
            $result[$n]->set_date = true;
            
            if (!isset($deaths[$i]))
                break;
                
            $result[$n]->deaths = $deaths[$i];
            $result[$n]->set_deaths = true;
            
            $n++;
        }
        
        return $result;
    }
    
    public function calculate_x_day_fields($cases, $deaths, $population, $area, $dates, $days = 7, $skip_days = 0, $incidence_factor = 100000)
    {
        if ($days <= 0)
            return null;
            
        $reproduction_available = false;
        
        $last_x = $this->get_last_x_days($cases, $deaths, $dates, $days, $skip_days);
        
        $cases_now = $cases[0];
        $deaths_now = $deaths[0];

        if (!$last_x)
            return null;

        $result = new \stdClass;
        $result->cases = (int)0;
        $result->deaths = (int)0;

        $cases1 = (int)0;
        $n = 0;

        foreach ($last_x as $obj)
        {
            $result->cases += (int)$obj->cases ?: 0;
            $result->deaths += (int)$obj->deaths ?: 0;
            
            if ($n > 0)
            {
                $cases1 += (int)$obj->cases ?: 0;
            }
            
            $n++;
        }

        if ($cases1 > 0)
            $result->exponence = ($cases_now / ($cases1 / $days));
        else
            $result->exponence = 0;

        if ($population > 0)
            $result->incidence = ($result->cases / $population * $incidence_factor);
        else
            $result->incidence = 0;
            
        if (($area > 0) && ($population > 0))
        {
            $density = ($population / $area);
            $case_density = ($result->cases / $area);
            
            if ($density > 0)
                $result->incidence2 = ($density * $case_density);
            else
                $result->incidence2 = 0;
        }
        else
        {
            $result->incidence2 = 0;
        }

        $result->incidence_factor = $incidence_factor;

        if ($result->incidence < 0)
            $result->alert_condition = -1;
        elseif ($result->incidence == 0)
            $result->alert_condition = 0;
        elseif ($result->incidence >= 200)
            $result->alert_condition = 7;
        elseif ($result->incidence >= 150)
            $result->alert_condition = 6;
        elseif ($result->incidence >= 100)
            $result->alert_condition = 5;
        elseif ($result->incidence >= 75)
            $result->alert_condition = 4;
        elseif ($result->incidence >= 50)
            $result->alert_condition = 3;
        elseif ($result->incidence >= 35)
            $result->alert_condition = 2;
        else
            $result->alert_condition = 1;

        if ($result->incidence2 < 0)
            $result->alert_condition2 = -1;
        elseif ($result->incidence2 == 0)
            $result->alert_condition2 = 0;
        elseif ($result->incidence2 >= 50)
            $result->alert_condition2 = 7;
        elseif ($result->incidence2 >= 40)
            $result->alert_condition2 = 6;
        elseif ($result->incidence2 >= 30)
            $result->alert_condition2 = 5;
        elseif ($result->incidence2 >= 20)
            $result->alert_condition2 = 4;
        elseif ($result->incidence2 >= 10)
            $result->alert_condition2 = 3;
        elseif ($result->incidence2 >= 5)
            $result->alert_condition2 = 2;
        else
            $result->alert_condition2 = 1;

        return $result;
    }

    public function calculate_x_day_r_value($cases, $deaths, $dates, $days = 7, $skip_days = 0, &$reproduction_available = null)
    {
        if ($days <= 0)
            return null;
            
        $reproduction_available = false;
        
        $result = new \stdClass;

        $result->prefix = $this->get_last_x_days($cases, $deaths, $dates, $days, $skip_days);
        $result->suffix = $this->get_last_x_days($cases, $deaths, $dates, $days, ($skip_days + $days), $reproduction_available);
        
        if (!$reproduction_available)
            return false;        

        if ((!$result->prefix) || (count($result->prefix) != $days))
            return false;

        if ((!$result->suffix) || (count($result->suffix) != $days))
            return false;

        $result->prefix_value = 0;
        $result->suffix_value = 0;

        foreach ($result->prefix as $case)
            $result->prefix_value += $case->cases;

        unset($result->prefix);

        foreach ($result->suffix as $case)
            $result->suffix_value += $case->cases;

        unset($result->suffix);

        $result->prefix_average = ($result->prefix_value / $days);
        $result->suffix_average = ($result->suffix_value / $days);

        if ($result->prefix_average == 0)
            $result->r_value = 0;
        else
            $result->r_value = ($result->suffix_average / $result->prefix_average);

        return $result;
    }
    
    public function calculate_14day_r_value($cases, $deaths, $dates, $skip_days = 0)
    {
        $reproduction_available = false;
        
        $obj = $this->calculate_x_day_r_value($cases, $deaths, $dates, 14, $skip_days, $reproduction_available);
        
        if (!$reproduction_available)
            return null;

        if (!$obj)
            return null;
            
        $result = new \stdClass;
        $result->reproduction_14day = $obj->r_value;

        return $result;
    }

    public function calculate_7day_r_value($cases, $deaths, $dates, $skip_days = 0)
    {
        $reproduction_available = false;
        
        $obj = $this->calculate_x_day_r_value($cases, $deaths, $dates, 7, $skip_days, $reproduction_available);
        
        if (!$reproduction_available)
            return null;

        if (!$obj)
            return null;

        $result = new \stdClass;
        $result->reproduction_7day = $obj->r_value;

        return $result;
    }

    public function calculate_4day_r_value($cases, $deaths, $dates, $skip_days = 0)
    {
        $reproduction_available = false;
        
        $obj = $this->calculate_x_day_r_value($cases, $deaths, $dates, 4, $skip_days, $reproduction_available);
        
        if (!$reproduction_available)
            return null;

        if (!$obj)
            return null;

        $result = new \stdClass;
        $result->reproduction_4day = $obj->r_value;

        return $result;
    }

    public function calculate_alert_condition($alert_condition_4day, $alert_condition_7day, $alert_condition_14day)
    {
        $result = new \stdClass;
        
        $result->alert_condition = round((($alert_condition_4day + $alert_condition_7day + $alert_condition_14day) / 3));
        
        if (($alert_condition_4day > $alert_condition_7day) && ($alert_condition_7day > $alert_condition_14day))
            $result->alert_condition_pointer = "asc";
        elseif (($alert_condition_4day == $alert_condition_7day) && ($alert_condition_7day > $alert_condition_14day))
            $result->alert_condition_pointer = "asc";
        elseif (($alert_condition_4day < $alert_condition_7day) && ($alert_condition_7day > $alert_condition_14day) && ($alert_condition_4day > $alert_condition_14day))
            $result->alert_condition_pointer = "asc";
        elseif (($alert_condition_4day > $alert_condition_7day) && ($alert_condition_7day == $alert_condition_14day))
            $result->alert_condition_pointer = "asc";
        elseif (($alert_condition_4day > $alert_condition_7day) && ($alert_condition_7day < $alert_condition_14day) && ($alert_condition_4day > $alert_condition_14day))
            $result->alert_condition_pointer = "asc";
        elseif (($alert_condition_4day < $alert_condition_7day) && ($alert_condition_7day < $alert_condition_14day))
            $result->alert_condition_pointer = "desc";
        elseif (($alert_condition_4day < $alert_condition_7day) && ($alert_condition_7day > $alert_condition_14day) && ($alert_condition_4day < $alert_condition_14day))
            $result->alert_condition_pointer = "desc";
        elseif (($alert_condition_4day == $alert_condition_7day) && ($alert_condition_7day < $alert_condition_14day))
            $result->alert_condition_pointer = "desc";
        elseif (($alert_condition_4day < $alert_condition_7day) && ($alert_condition_7day == $alert_condition_14day))
            $result->alert_condition_pointer = "desc";
        else
            $result->alert_condition_pointer = "sty";
            
        return $result;
    }
                
    public function calculate_alert_condition2($alert_condition_4day, $alert_condition_7day, $alert_condition_14day)
    {
        $result = new \stdClass;
        
        $result->alert_condition2 = round((($alert_condition_4day + $alert_condition_7day + $alert_condition_14day) / 3));
        
        if (($alert_condition_4day > $alert_condition_7day) && ($alert_condition_7day > $alert_condition_14day))
            $result->alert_condition2_pointer = "asc";
        elseif (($alert_condition_4day == $alert_condition_7day) && ($alert_condition_7day > $alert_condition_14day))
            $result->alert_condition2_pointer = "asc";
        elseif (($alert_condition_4day < $alert_condition_7day) && ($alert_condition_7day > $alert_condition_14day) && ($alert_condition_4day > $alert_condition_14day))
            $result->alert_condition2_pointer = "asc";
        elseif (($alert_condition_4day > $alert_condition_7day) && ($alert_condition_7day == $alert_condition_14day))
            $result->alert_condition2_pointer = "asc";
        elseif (($alert_condition_4day > $alert_condition_7day) && ($alert_condition_7day < $alert_condition_14day) && ($alert_condition_4day > $alert_condition_14day))
            $result->alert_condition2_pointer = "asc";
        elseif (($alert_condition_4day < $alert_condition_7day) && ($alert_condition_7day < $alert_condition_14day))
            $result->alert_condition2_pointer = "desc";
        elseif (($alert_condition_4day < $alert_condition_7day) && ($alert_condition_7day > $alert_condition_14day) && ($alert_condition_4day < $alert_condition_14day))
            $result->alert_condition2_pointer = "desc";
        elseif (($alert_condition_4day == $alert_condition_7day) && ($alert_condition_7day < $alert_condition_14day))
            $result->alert_condition2_pointer = "desc";
        elseif (($alert_condition_4day < $alert_condition_7day) && ($alert_condition_7day == $alert_condition_14day))
            $result->alert_condition2_pointer = "desc";
        else
            $result->alert_condition2_pointer = "sty";
            
        // These are example recommendations!!!! NOT A STRICT TO DO LIST!!!
        // Suggestions are welcome.
        $force_defaults = array(
            "flag_enforce_daily_need_deliveries" => (int)0,
            "flag_enforce_treatment_priorization" => (int)0,
            "flag_lockdown_primary_infrastructure" => (int)0,
            "flag_isolate_executive_staff" => (int)0,
            "flag_enforce_federation_control" => (int)0,
            "flag_limit_fundamental_rights" => (int)0,
            "flag_lockdown_schools" => (int)0,
            "flag_lockdown_gastronomy" => (int)0,
            "flag_lockdown_secondary_infrastructure" => (int)0,
            "flag_enforce_local_crisis_team_control" => (int)0,
            "flag_enforce_gastronomy_rules" => (int)0,
            "flag_lockdown_leisure_activities" => (int)0,
            "flag_isolate_medium_risk_group" => (int)0,
            "flag_reserve_icu_units" => (int)0,
            "flag_enforce_shopping_rules" => (int)0,
            "flag_isolate_high_risk_group" => (int)0,
            "flag_general_caution" => (int)0,
            "flag_attention_on_symptoms" => (int)0,
            "flag_wash_hands" => (int)0,
            "flag_recommend_mask_wearing" => (int)0,
            "flag_enforce_critical_mask_wearing" => (int)0, 
            "flag_enforce_public_mask_wearing" => (int)0, 
            "flag_isolate_low_risk_group" => (int)0,
            "enforce_distance_meters" => (int)-1,
            "enforce_household_plus_persons_to" => (int)-1,
            "enforce_public_groups_to" => (int)-1,
            "enforce_public_events_to" => (int)-1
        );
        
        foreach ($force_defaults as $key => $val)
            $result->$key = (int)$val;
        
        switch ($result->alert_condition2)
        {
            case 7:
                $result->flag_enforce_daily_need_deliveries = 1;
                $result->flag_enforce_treatment_priorization = 1;
                $result->flag_lockdown_primary_infrastructure = 1;
                $result->flag_isolate_executive_staff = 1;
                $result->flag_enforce_federation_control = 1;
                $result->flag_limit_fundamental_rights = 1;
                $result->flag_isolate_low_risk_group = 1;
                $result->enforce_distance_meters = 3;
                $result->enforce_household_plus_persons_to = (int)0;
                $result->enforce_public_groups_to = (int)0;
                $result->enforce_public_events_to = (int)0;
            case 6:
                $result->flag_lockdown_schools = 1;
                $result->flag_lockdown_gastronomy = 1;
                $result->flag_lockdown_secondary_infrastructure = 1;
                $result->flag_enforce_local_crisis_team_control = 1;
                if ($result->alert_condition2 == 6)
                {
                    $result->enforce_distance_meters = 3;
                    $result->enforce_household_plus_persons_to = 1;
                    $result->enforce_public_groups_to = (int)0;
                    $result->enforce_public_events_to = (int)0;
                }
            case 5:
                $result->flag_enforce_gastronomy_rules = 1;
                $result->flag_lockdown_leisure_activities = 1;
                $result->flag_isolate_medium_risk_group = 1;
                $result->flag_enforce_public_mask_wearing = 1;
                $result->flag_reserve_icu_units = 1;
                if ($result->alert_condition2 == 5)
                {
                    $result->enforce_distance_meters = 3;
                    $result->enforce_household_plus_persons_to = 2;
                    $result->enforce_public_groups_to = 5;
                    $result->enforce_public_events_to = 100;
                }
            case 4:
                $result->flag_enforce_shopping_rules = 1;
                $result->flag_isolate_high_risk_group = 1;
                if ($result->alert_condition2 == 4)
                {
                    $result->enforce_distance_meters = 2;
                    $result->enforce_household_plus_persons_to = 5;
                    $result->enforce_public_groups_to = 25;
                    $result->enforce_public_events_to = 1000;
                }
            case 3:
                if ($result->alert_condition2 == 3)
                {
                    $result->enforce_distance_meters = 2;
                    $result->enforce_household_plus_persons_to = 10;
                    $result->enforce_public_groups_to = 50;
                    $result->enforce_public_events_to = 2500;
                }
            case 2:
                $result->flag_enforce_critical_mask_wearing = 1;
                if ($result->alert_condition2 == 2)
                {
                    $result->enforce_distance_meters = 2;
                    $result->enforce_household_plus_persons_to = 10;
                    $result->enforce_public_groups_to = 50;
                    $result->enforce_public_events_to = 5000;
                }
            case 1:
                $result->flag_general_caution = 1;
                $result->flag_attention_on_symptoms = 1;
                $result->flag_recommend_mask_wearing = 1;
                $result->flag_wash_hands = 1;
                if ($result->alert_condition2 == 1)
                {
                    $result->enforce_distance_meters = 2;
                    $result->enforce_household_plus_persons_to = 15;
                    $result->enforce_public_groups_to = 100;
                    $result->enforce_public_events_to = 10000;
                }
            case 0;
                $result->flag_general_caution = 1;
                $result->flag_wash_hands = 1;
                $result->flag_attention_on_symptoms = 1;
                break;
        }
        
        return $result;    
    }

    public function calculate_14day_fields($cases, $deaths, $population, $area, $dates, $incidence_factor = 100000)
    {
        $obj = $this->calculate_x_day_fields($cases, $deaths, $population, $area, $dates, 14, 0, $incidence_factor);
        $obj2 = $this->calculate_x_day_fields($cases, $deaths, $population, $area, $dates, 14, 3, $incidence_factor);

        if (!$obj)
            return null;

        $result = new \stdClass;

        if ($obj->cases == 0)
            $result->flag_case_free = 0;
        
        $result->cases_14day_average = $obj->cases;
        $result->deaths_14day_average = $obj->deaths;
        $result->exponence_14day = $obj->exponence;
        $result->exponence_14day_smoothed = ((!$obj2) ? $obj->exponence : $obj2->exponence);
        $result->incidence_14day = $obj->incidence;
        $result->incidence_14day_smoothed = ((!$obj2) ? $obj->incidence : $obj2->incidence);
        $result->incidence2_14day = $obj->incidence2;
        $result->incidence2_14day_smoothed = ((!$obj2) ? $obj->incidence2 : $obj2->incidence2);
        $result->alert_condition_14day = ((!$obj2) ? $obj->alert_condition : $obj2->alert_condition);
        $result->alert_condition2_14day = ((!$obj2) ? $obj->alert_condition2 : $obj2->alert_condition2);

        return $result;
    }

    public function calculate_7day_fields($cases, $deaths, $population, $area, $dates, $incidence_factor = 100000)
    {
        $obj = $this->calculate_x_day_fields($cases, $deaths, $population, $area, $dates, 7, 0, $incidence_factor);
        $obj2 = $this->calculate_x_day_fields($cases, $deaths, $population, $area, $dates, 7, 3, $incidence_factor);

        if (!$obj)
            return null;

        $result = new \stdClass;

        $result->cases_7day_average = $obj->cases;
        $result->deaths_7day_average = $obj->deaths;
        $result->exponence_7day = $obj->exponence;
        $result->exponence_7day_smoothed = ((!$obj2) ? $obj->exponence : $obj2->exponence);
        $result->incidence_7day = $obj->incidence;
        $result->incidence_7day_smoothed = ((!$obj2) ? $obj->incidence : $obj2->incidence);
        $result->incidence2_7day = $obj->incidence2;
        $result->incidence2_7day_smoothed = ((!$obj2) ? $obj->incidence2 : $obj2->incidence2);
        $result->alert_condition_7day = ((!$obj2) ? $obj->alert_condition : $obj2->alert_condition);
        $result->alert_condition2_7day = ((!$obj2) ? $obj->alert_condition2 : $obj2->alert_condition2);

        return $result;
    }

    public function calculate_4day_fields($cases, $deaths, $population, $area, $dates, $incidence_factor = 100000)
    {
        $obj = $this->calculate_x_day_fields($cases, $deaths, $population, $area, $dates, 4, 0, $incidence_factor);
        $obj2 = $this->calculate_x_day_fields($cases, $deaths, $population, $area, $dates, 4, 3, $incidence_factor);

        if (!$obj)
            return null;

        $result = new \stdClass;

        $result->cases_4day_average = $obj->cases;
        $result->deaths_4day_average = $obj->deaths;
        $result->exponence_4day = $obj->exponence;
        $result->exponence_4day_smoothed = ((!$obj2) ? $obj->exponence : $obj2->exponence);
        $result->incidence_4day = $obj->incidence;
        $result->incidence_4day_smoothed = ((!$obj2) ? $obj->incidence : $obj2->incidence);
        $result->incidence2_4day = $obj->incidence2;
        $result->incidence2_4day_smoothed = ((!$obj2) ? $obj->incidence2 : $obj2->incidence2);
        $result->alert_condition_4day = ((!$obj2) ? $obj->alert_condition : $obj2->alert_condition);
        $result->alert_condition2_4day = ((!$obj2) ? $obj->alert_condition2 : $obj2->alert_condition2);

        return $result;
    }

    public function calculate_case_and_death_ascension($cases, $deaths, $dates)
    {
        if (!is_array($cases))
            return null;
            
        if (!is_array($deaths))
            return null;
            
        $cases_now = $cases[0];
        $deaths_now = $deaths[0];

        $result = new \stdClass;
        $result->cases_ascension = 0;
        $result->deaths_ascension = 0;

        if (isset($cases[1]))
            $result->cases_ascension = (int)($cases_now - $cases[1]) ?: 0;

        if (isset($deaths[1]))
            $result->deaths_ascension = (int)($deaths_now - $deaths[1]) ?: 0;

        $yesterday = ($cases_now - $result->cases_ascension);

        if ($yesterday != 0)
            $result->exponence_yesterday = ($cases_now / $yesterday);

        if ($result->cases_ascension > 0)
            $result->cases_pointer = "asc";
        elseif ($result->cases_ascension < 0)
            $result->cases_pointer = "desc";
        else
            $result->cases_pointer = "sty";

        if ($result->deaths_ascension > 0)
            $result->deaths_pointer = "asc";
        elseif ($result->deaths_ascension < 0)
            $result->deaths_pointer = "desc";
        else
            $result->deaths_pointer = "sty";

        return $result;
    }

    public function calculate_case_and_death_rates($cases, $deaths, $population, $dates)
    {
        if ($population == 0)
            return false;
            
        $cases_now = $cases[0];
        $deaths_now = $deaths[0];
            
        $result = new \stdClass;

        // This is a snapshot of the current days rate of cases AND NOT THE POSITIVE RATE BY PERFORMED TESTS
        $result->cases_rate = (100 / $population * $cases_now);
        
        // The rate of deaths for the current day
        $result->deaths_rate = (100 / $population * $deaths_now);

        return $result;
    }
    
    public function calculate_dataset_fields($cases, $deaths, $population, $area, $dates, $incidence_factor = 100000)
    {
        if (!is_array($cases))
            return null;
            
        if (!is_array($deaths))
            return null;
            
        if (!is_array($dates))
            return null;
        
        $result = new \stdClass;
        
        self::result_object_merge($result, $this->calculate_4day_fields($cases, $deaths, $population, $area, $dates, $incidence_factor));
        self::result_object_merge($result, $this->calculate_7day_fields($cases, $deaths, $population, $area, $dates, $incidence_factor));
        self::result_object_merge($result, $this->calculate_14day_fields($cases, $deaths, $population, $area, $dates, $incidence_factor));
        
        self::result_object_merge($result, $this->calculate_case_and_death_rates($cases, $deaths, $population, $dates));
        self::result_object_merge($result, $this->calculate_case_and_death_ascension($cases, $deaths, $dates));
        
        self::result_object_merge($result, $this->calculate_4day_r_value($cases, $deaths, $dates));
        self::result_object_merge($result, $this->calculate_7day_r_value($cases, $deaths, $dates));
        self::result_object_merge($result, $this->calculate_14day_r_value($cases, $deaths, $dates));

        if (isset($result->alert_condition_4day))
            $alert_condition_4day = $result->alert_condition_4day;
        else
            $alert_condition_4day = -1;
        
        if (isset($result->alert_condition_7day))
            $alert_condition_7day = $result->alert_condition_7day;
        else
            $alert_condition_7day = -1;
        
        if (isset($result->alert_condition_14day))
            $alert_condition_14day = $result->alert_condition_14day;
        else
            $alert_condition_14day = -1;
        
        if (isset($result->alert_condition2_4day))
            $alert_condition2_4day = $result->alert_condition2_4day;
        else
            $alert_condition2_4day = -1;
        
        if (isset($result->alert_condition2_7day))
            $alert_condition2_7day = $result->alert_condition2_7day;
        else
            $alert_condition2_7day = -1;
        
        if (isset($result->alert_condition2_14day))
            $alert_condition2_14day = $result->alert_condition2_14day;
        else
            $alert_condition2_14day = -1;
        
        self::result_object_merge($result, $this->calculate_alert_condition($alert_condition_4day, $alert_condition_7day, $alert_condition_14day));
        self::result_object_merge($result, $this->calculate_alert_condition2($alert_condition2_4day, $alert_condition2_7day, $alert_condition2_14day));
        
        return $result;        
    }
    
    public function master_datasets($hold_data = false)
    {
        // After stores are loaded, create the data pool with common fields
        $datasets = array();
        
        if ($this->stores_loaded_count < 10)
            return false;
            
        $dataset_index = array();
            
        foreach ($this->eu_coviddata->handler->get_data()->records as $id => $record)
        {
            $continent_hash = self::hash_name("continent", $record->continent);
            $country_hash = self::hash_name("country", $record->country, $continent_hash);

            $dataset_hash = self::hash_name("dataset-continent", $continent_hash, $record->date_rep);
            
            if (!isset($datasets[$dataset_hash]))
                $dataset = $this->create_dataset_template();
            else
                $dataset = $datasets[$dataset_hash];
                
            $dataset->dataset_hash = $dataset_hash;
            $dataset->continent_hash = $continent_hash;
            $dataset->day_of_week = $record->day_of_week;
            $dataset->day = $record->day;
            $dataset->month = $record->month;
            $dataset->year = $record->year;
            $dataset->cases_count += $record->cases;
            $dataset->deaths_count += $record->deaths;
            $dataset->timestamp_represent = $record->timestamp_represent;
            $dataset->location_type = "continent";
            
            $index = "N".$dataset->continent_hash;
            
            if (!isset($dataset_index[$index]))
                $dataset_index[$index] = array();
                
            $date = date("Ymd", strtotime($record->timestamp_represent));
                
            $dataset_index[$index][$date] = $dataset_hash;
            
            $datasets[$dataset_hash] = $dataset;

            $dataset_hash = self::hash_name("dataset-country", $country_hash, $record->date_rep);

            if (!isset($datasets[$dataset_hash]))
                $dataset = $this->get_dataset_template();
            else
                $dataset = $datasets[$dataset_hash];
                
            $dataset->dataset_hash = $dataset_hash;
            $dataset->country_hash = $country_hash;
            $dataset->continent_hash = $continent_hash;
            $dataset->day_of_week = $record->day_of_week;
            $dataset->day = $record->day;
            $dataset->month = $record->month;
            $dataset->year = $record->year;
            $dataset->cases_count += $record->cases;
            $dataset->deaths_count += $record->deaths;
            $dataset->timestamp_represent = $record->timestamp_represent;
            $dataset->location_type = "country";
            
            $index = "C".$dataset->continent_hash.$dataset->country_hash;
            
            if (!isset($dataset_index[$index]))
                $dataset_index[$index] = array();
                
            $date = date("Ymd", strtotime($record->timestamp_represent));
                
            $dataset_index[$index][$date] = $dataset_hash;
            
            $datasets[$dataset_hash] = $dataset;
        }
        
        foreach ($dataset_index as $index => $data)
        {
            krsort($data);
            
            foreach ($data as $date => $hash)
            {                
                $cases = array();
                $deaths = array();
                $dates = array();
            
                foreach ($data as $date2 => $hash2)
                {
                    if ($date2 > $date)
                        continue;
                        
                    array_push($cases, $datasets[$hash2]->cases_count);
                    array_push($deaths, $datasets[$hash2]->deaths_count);
                    array_push($dates, $date2);
                
                    if (count($cases) > 32)
                        break;
                }
                
                for ($i = (count($cases) - 1); $i < 32; $i++)
                {
                    $cases[$i] = (int)0;
                    $deaths[$i] = (int)0;
                    $dates[$i] = (int)0;
                }
                
                $country_hash = $datasets[$hash]->country_hash;
                
                self::result_object_merge($datasets[$hash], $this->calculate_dataset_fields($cases, $deaths, $this->countries[$country_hash]->population_count, $this->countries[$country_hash]->area, $dates));
            }
        }
                            
        // Free the memory, which is no longer need (if hold data is not requested)
        if (!$hold_data)
        {
            $this->eu_coviddata->handler->free();
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
    
    public function get_age_groups()
    {
        return array(
            "0:4",
            "5:14",
            "15:34",
            "35:59",
            "60:79",
            "80:plus",
            "unknown"
        );    
    }
    
    public function create_dataset_template()
    {
        // Create a dataset template
        $tmpl = new \stdClass;
        $tmpl->location_type = null;
        $tmpl->dataset_hash = null;
        $tmpl->district_hash = null;
        $tmpl->state_hash = null;
        $tmpl->country_hash = null;
        $tmpl->continent_hash = null;
        $tmpl->day_of_week = null;
        $tmpl->day = null;
        $tmpl->month = null;
        $tmpl->year = null;
        $tmpl->timestamp_represent = null;
        
        $age_groups = $this->get_age_groups();
        
        foreach (array("cases", "deaths", "recovered") as $prefix)
        {
            $suffixes = array(
                "new",
                "count",
                "delta",
                "today",
                "yesterday",
                "total",
                "pointer",
                "4day_average",
                "7day_average",
                "14day_average"
            );
            
            foreach ($suffixes as $suffix)
            {
                $key = $prefix."_".$suffix;
                
                if (substr($suffix, -7) == "average")
                    $tmpl->$key = (float)0;
                elseif ($suffix == "pointer")
                    $tmpl->$key = "sty";
                else
                    $tmpl->$key = (int)0;
            }
            
            foreach (array("new", "count", "delta", "today", "total", "pointer") as $suffix)
            {
                foreach ($age_groups as $agegroup)
                {
                    $ag = str_replace(":", "_", $age_group);
                    
                    $key = $prefix."_".$suffix."_agegroup_".$ag;
                    
                    if ($suffix == "pointer")
                        $tmpl->$key = "sty";
                    else
                        $tmpl->$key = (int)0;
                }
            }
        }
        
        return $tmpl;    
    }
    
    public function master_testresults($hold_data = false, &$unknown_states = null, &$unknown_districts = null)
    {
        // After stores are loaded, create the testresult pool with common fields
        $testresults = array();
        
        if ($this->stores_loaded_count < 10)
            return false;
            
        $europe_hash = self::hash_name("continent", "Europe");
        $germany_hash = self::hash_name("country", "Germany", $europe_hash);
        
        // Get the country and zero some fields
        $germany = $this->countries[$germany_hash];
        
        $datasets = array();
        $age_groups = $this->get_age_groups();
        
        $unknown_districts = array();
        $unknown_states = array();
                        
        // No need for templates here, just clone data and add the hashes
        foreach($this->rki_positive->handler->get_data() as $data)
        {
            // Before we do anything, we must map the district name!
            $data->district_name = $this->district_map($data->district_name);

            // Get hashes        
            $state_hash = self::hash_name("state", $data->state, $germany_hash);
            $district_hash = self::hash_name("district", $data->district_name, $state_hash);

            // The result hash must have another part to be unique, date is not sufficient here
            // So maybe its a good idea to use the foreign identifier, delivered by the data itself
            $result_hash = self::hash_name("result", $district_hash, $data->date_rep."#".$data->foreign_identifier);
            
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
            
            // The location type for a testresult is always 'district' (for now), no need to use resources on hash type validations
            $testresult->location_type = 'district';
            
            $index = $testresult->district_hash;
            $ts = strtotime($testresult->timestamp_represent);
            $date = date("Ymd", $ts);

            // Create or update district dateset
            if (!isset($datasets[$index][$date]))
            {
                $dataset = $this->create_dataset_template();
                
                $dataset->dataset_hash = self::hash_name("dataset-district", $district_hash, $data->date_rep);
                $dataset->district_hash = $district_hash;
                $dataset->state_hash = $state_hash;
                $dataset->country_hash = $germany_hash;
                $dataset->continent_hash = $europe_hash;
                $dataset->day_of_week = $data->day_of_week;
                $dataset->day = $data->day;
                $dataset->month = $data->month;
                $dataset->year = $data->year;
                $dataset->timestamp_represent = $data->timestamp_represent;
                $dataset->date_rep = $data->date_rep;
                $dataset->location_type = "district";
            }
            else
            {
                $dataset = $datasets[$index][$date];
            }
            
            /* METADATA by 2020-11-21
                        
            Beschreibung der Daten des RKI Covid-19-Dashboards (https://corona.rki.de)

            Dem Dashboard liegen aggregierte Daten der gemäß IfSG von den Gesundheitsämtern an das RKI übermittelten Covid-19-Fälle zu Grunde
            Mit den Daten wird der tagesaktuelle Stand (00:00 Uhr) abgebildet und es werden die Veränderungen bei den Fällen und Todesfällen zum Vortag dargstellt
            In der Datenquelle sind folgende Parameter enthalten:
            IdBundesland: Id des Bundeslands des Falles mit 1=Schleswig-Holstein bis 16=Thüringen
            Bundesland: Name des Bundeslanes
            Landkreis ID: Id des Landkreises des Falles in der üblichen Kodierung 1001 bis 16077=LK Altenburger Land
            Landkreis: Name des Landkreises
            Altersgruppe: Altersgruppe des Falles aus den 6 Gruppe 0-4, 5-14, 15-34, 35-59, 60-79, 80+ sowie unbekannt
            Altersgruppe2: Altersgruppe des Falles aus 5-Jahresgruppen 0-4, 5-9, 10-14, ..., 75-79, 80+ sowie unbekannt
            Geschlecht: Geschlecht des Falles M0männlich, W=weiblich und unbekannt
            AnzahlFall: Anzahl der Fälle in der entsprechenden Gruppe
            AnzahlTodesfall: Anzahl der Todesfälle in der entsprechenden Gruppe
            Meldedatum: Datum, wann der Fall dem Gesundheitsamt bekannt geworden ist
            Datenstand: Datum, wann der Datensatz zuletzt aktualisiert worden ist
            NeuerFall: 
            0: Fall ist in der Publikation für den aktuellen Tag und in der für den Vortag enthalten
            1: Fall ist nur in der aktuellen Publikation enthalten
            -1: Fall ist nur in der Publikation des Vortags enthalten
            damit ergibt sich: Anzahl Fälle der aktuellen Publikation als Summe(AnzahlFall), wenn NeuerFall in (0,1); Delta zum Vortag als Summe(AnzahlFall) wenn NeuerFall in (-1,1)
            NeuerTodesfall:
            0: Fall ist in der Publikation für den aktuellen Tag und in der für den Vortag jeweils ein Todesfall
            1: Fall ist in der aktuellen Publikation ein Todesfall, nicht jedoch in der Publikation des Vortages
            -1: Fall ist in der aktuellen Publikation kein Todesfall, jedoch war er in der Publikation des Vortags ein Todesfall
            -9: Fall ist weder in der aktuellen Publikation noch in der des Vortages ein Todesfall
            damit ergibt sich: Anzahl Todesfälle der aktuellen Publikation als Summe(AnzahlTodesfall) wenn NeuerTodesfall in (0,1); Delta zum Vortag als Summe(AnzahlTodesfall) wenn NeuerTodesfall in (-1,1)
            Referenzdatum: Erkrankungsdatum bzw. wenn das nicht bekannt ist, das Meldedatum
            AnzahlGenesen: Anzahl der Genesenen in der entsprechenden Gruppe
            NeuGenesen:
            0: Fall ist in der Publikation für den aktuellen Tag und in der für den Vortag jeweils Genesen
            1: Fall ist in der aktuellen Publikation Genesen, nicht jedoch in der Publikation des Vortages
            -1: Fall ist in der aktuellen Publikation nicht Genesen, jedoch war er in der Publikation des Vortags Genesen
            -9: Fall ist weder in der aktuellen Publikation noch in der des Vortages Genesen 
            damit ergibt sich: Anzahl Genesen der aktuellen Publikation als Summe(AnzahlGenesen) wenn NeuGenesen in (0,1); Delta zum Vortag als Summe(AnzahlGenesen) wenn NeuGenesen in (-1,1)
            IstErkrankungsbeginn: 1, wenn das Refdatum der Erkrankungsbeginn ist, 0 sonst
            */

            if (($data->cases_new == 0) || ($data->cases_new == 1))
                $dataset->cases_new += $data->cases_count;
            if (($data->cases_new == -1) || ($data->cases_new == 1))
                $dataset->cases_delta += $data->cases_count;
            if ($data->cases_new == 0)
                $dataset->cases_today += $data->cases_count;
            if ($data->cases_new == -1)
                $dataset->cases_yesterday += $data->cases_count;
                
            if ($dataset->cases_today == $dataset->cases_yesterday)
                $dataset->cases_pointer = "sty";
            elseif ($dataset->cases_today > $dataset->cases_yesterday)
                $dataset->cases_pointer = "asc";
            elseif ($dataset->cases_today < $dataset->cases_yesterday)
                $dataset->cases_pointer = "desc";                
                
            $dataset->cases_count = ($dataset->cases_delta - $dataset->cases_new);
            
            if (($data->deaths_new == 0) || ($data->deaths_new == 1))
                $dataset->deaths_new += $data->deaths_count;
            if (($data->deaths_new == -1) || ($data->deaths_new == 1))
                $dataset->deaths_delta += $data->deaths_count;
            if ($data->deaths_new == 0)
                $dataset->deaths_today += $data->deaths_count;
            if ($data->deaths_new == -1)
                $dataset->deaths_yesterday += $data->deaths_count;

            if ($dataset->deaths_today == $dataset->deaths_yesterday)
                $dataset->deaths_pointer = "sty";
            elseif ($dataset->deaths_today > $dataset->deaths_yesterday)
                $dataset->deaths_pointer = "asc";
            elseif ($dataset->deaths_today < $dataset->deaths_yesterday)
                $dataset->deaths_pointer = "desc";                
                
            $dataset->deaths_count = ($dataset->deaths_delta - $dataset->deaths_new);
            
            if (($data->recovered_new == 0) || ($data->recovered_new == 1))
                $dataset->recovered_new += $data->recovered_count;
            if (($data->recovered_new == -1) || ($data->recovered_new == 1))
                $dataset->recovered_delta += $data->recovered_count;
            if ($data->recovered_new == 0)
                $dataset->recovered_today += $data->recovered_count;
            if ($data->recovered_new == -1)
                $dataset->recovered_yesterday += $data->recovered_count;
                
            if ($dataset->recovered_today == $dataset->recovered_yesterday)
                $dataset->recovered_pointer = "sty";
            elseif ($dataset->recovered_today > $dataset->recovered_yesterday)
                $dataset->recovered_pointer = "asc";
            elseif ($dataset->recovered_today < $dataset->recovered_yesterday)
                $dataset->recovered_pointer = "desc";                
                
            $dataset->recovered_count = ($dataset->recovered_delta - $dataset->recovered_new);
            
            if ((isset($data->age_group)) || (isset($data->age_group2)))
            {
                if (is_object($data->age_group2))
                {
                    $age_low = $data->age_group2->lower;
                    $age_high = $data->age_group2->upper;
                }
                elseif (is_object($data->age_group))
                {
                    $age_low = $data->age_group->lower;
                    $age_high = $data->age_group->upper;
                }
                else
                {
                    $age_low = -1;
                    $age_high = -1;
                }
                
                if (($age_low == -1) && ($age_high == -1))
                    $age_index = "unknown";
                elseif ($age_low == 80)
                    $age_index = "80:plus";
                else
                    $age_index = $age_low.":".$age_high;
                    
                $set_suffix = null;
                    
                if (isset($age_groups[$age_index]))
                {
                    $set_suffix = str_replace(":", "_", $age_index);
                }
                else
                {
                    $set_suffix = "unknown";
                    
                    foreach ($age_groups as $age_group)
                    {
                        $lowhigh = explode(":", $age_group);
                        
                        $alow = $lowhigh[0];
                        $ahigh = ((isset($lowhigh[1])) ? $lowhigh[1] : -1);
                        
                        if ($ahigh == "plus")
                            $ahigh = 999;
                            
                        if ($age_low >= $alow)
                        {
                            if ($age_high <= $ahigh)
                            {
                                $set_suffix = str_replace(":", "_", $age_group);
                                break;
                            }
                        }
                    }
                }
                
                if ($set_suffix)
                {
                    $key_cases_new = "cases_new_agegroup_".$set_suffix;
                    $key_cases_count = "cases_count_agegroup_".$set_suffix;
                    $key_cases_delta = "cases_delta_agegroup_".$set_suffix;
                    $key_cases_today = "cases_today_agegroup_".$set_suffix;
                    $key_cases_total = "cases_total_agegroup_".$set_suffix;
                    $key_cases_pointer = "cases_total_agegroup_".$set_suffix;
                
                    if (($data->cases_new == 0) || ($data->cases_new == 1))
                        $dataset->$key_cases_new += $data->cases_count;
                    if (($data->cases_new == -1) || ($data->cases_new == 1))
                        $dataset->$key_cases_delta += $data->cases_count;
                    if ($data->cases_new == 0)
                        $dataset->$key_cases_today += $data->cases_count;
                    if ($data->cases_new == -1)
                        $dataset->$key_cases_yesterday += $data->cases_count;

                    if ($dataset->$key_cases_today == $dataset->$key_cases_yesterday)
                        $dataset->$key_cases_pointer = "sty";
                    elseif ($dataset->$key_cases_today > $dataset->$key_cases_yesterday)
                        $dataset->$key_cases_pointer = "asc";
                    elseif ($dataset->$key_cases_today < $dataset->$key_cases_yesterday)
                        $dataset->$key_cases_pointer = "desc";                
                
                    $dataset->$key_cases_count = ($dataset->$key_cases_delta - $dataset->$key_cases_new);

                    $key_deaths_new = "deaths_new_agegroup_".$set_suffix;
                    $key_deaths_count = "deaths_count_agegroup_".$set_suffix;
                    $key_deaths_delta = "deaths_delta_agegroup_".$set_suffix;
                    $key_deaths_today = "deaths_today_agegroup_".$set_suffix;
                    $key_deaths_total = "deaths_total_agegroup_".$set_suffix;
                    $key_deaths_pointer = "deaths_total_agegroup_".$set_suffix;
                
                    if (($data->deaths_new == 0) || ($data->deaths_new == 1))
                        $dataset->$key_deaths_new += $data->deaths_count;
                    if (($data->deaths_new == -1) || ($data->deaths_new == 1))
                        $dataset->$key_deaths_delta += $data->deaths_count;
                    if ($data->deaths_new == 0)
                        $dataset->$key_deaths_today += $data->deaths_count;
                    if ($data->deaths_new == -1)
                        $dataset->$key_deaths_yesterday += $data->deaths_count;

                    if ($dataset->$key_deaths_today == $dataset->$key_deaths_yesterday)
                        $dataset->$key_deaths_pointer = "sty";
                    elseif ($dataset->$key_deaths_today > $dataset->$key_deaths_yesterday)
                        $dataset->$key_deaths_pointer = "asc";
                    elseif ($dataset->$key_deaths_today < $dataset->$key_deaths_yesterday)
                        $dataset->$key_deaths_pointer = "desc";                
                
                    $dataset->$key_deaths_count = ($dataset->$key_deaths_delta - $dataset->$key_deaths_new);

                    $key_recovered_new = "recovered_new_agegroup_".$set_suffix;
                    $key_recovered_count = "recovered_count_agegroup_".$set_suffix;
                    $key_recovered_delta = "recovered_delta_agegroup_".$set_suffix;
                    $key_recovered_today = "recovered_today_agegroup_".$set_suffix;
                    $key_recovered_total = "recovered_total_agegroup_".$set_suffix;
                    $key_recovered_pointer = "recovered_total_agegroup_".$set_suffix;
                
                    if (($data->deaths_new == 0) || ($data->deaths_new == 1))
                        $dataset->$key_recovered_new += $data->deaths_count;
                    if (($data->deaths_new == -1) || ($data->deaths_new == 1))
                        $dataset->$key_recovered_delta += $data->deaths_count;
                    if ($data->deaths_new == 0)
                        $dataset->$key_recovered_today += $data->deaths_count;
                    if ($data->deaths_new == -1)
                        $dataset->$key_recovered_yesterday += $data->deaths_count;

                    if ($dataset->$key_recovered_today == $dataset->$key_recovered_yesterday)
                        $dataset->$key_recovered_pointer = "sty";
                    elseif ($dataset->$key_recovered_today > $dataset->$key_recovered_yesterday)
                        $dataset->$key_recovered_pointer = "asc";
                    elseif ($dataset->$key_recovered_today < $dataset->$key_recovered_yesterday)
                        $dataset->$key_recovered_pointer = "desc";                
                
                    $dataset->$key_recovered_count = ($dataset->$key_recovered_delta - $dataset->$key_recovered_new);
                }
            }
            
            if (!isset($datasets[$index]))
                $datasets[$index] = array();
                                
            $datasets[$index][$date] = $dataset;
            $testresults[$result_hash] = $testresult;
            
            krsort($datasets[$index]);
        }

        // Global counters        
        $states_cases = array();
        $states_deaths = array();
        $states_recovered = array();
            
        $germany_cases = 0;
        $germany_deaths = 0;
        $germany_recovered = 0;
            
        // Merge district datasets and main datasets
        foreach ($datasets as $index => $data)
        {
            $cases = 0;
            $deaths = 0;
            $recovered = 0;
            
            $cases_last = array();
            $deaths_last = array();
            $dates_last = array();
            
            // Zero fill cases and deaths array
            for ($i = 0; $i < 32; $i++)
            {
                $cases_last[$i] = 0;
                $deaths_last[$i] = 0;
                $dates_last[$i] = 0;
            }
            
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
                    
                if (isset($district->area))
                    $area = $district->area;
                else
                    $area = 0;
            }
            else
            {
                $population = 0;
                $area = 0;
            }
                        
            if (is_object($district))
            {
                if (!isset($state_cases[$district->state_hash]))
                {
                    $state_cases[$district->state_hash] = 0;
                    $state_deaths[$district->state_hash] = 0;
                    $state_recovered[$district->state_hash] = 0;
                }
            }
            
            foreach ($data as $date => $dataset)
            {
                $cases += $dataset->cases;
                $deaths += $dataset->deaths;
                $recovered += $dataset->recovered;

                if (is_object($district))
                {                
                    $state_cases[$district->state_hash] += $dataset->cases;
                    $state_deaths[$district->state_hash] += $dataset->deaths;
                    $state_recovered[$district->state_hash] += $dataset->recovered;
                }
                
                $germany_cases += $dataset->cases;
                $germany_deaths += $dataset->deaths;
                $germany_recovered += $dataset->recovered;
                
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
                
                // Before shifting and adding new values to array, check the date linearity and zero fill missing days
                $last_date = $dates_last[count($dates_last)-1];
                
                $last_ts = mktime(0, 0, 0, (double)substr($last_date, 4, 2), (double)substr($last_date, 6, 2), (double)substr($last_date, 0, 4));
                $this_ts = mktime(0, 0, 0, (double)substr($date, 4, 2), (double)substr($date, 6, 2), (double)substr($date, 0, 4));

                $date_before = date("Ymd", ($this_ts - 86400));
                
                if ($last_ts != $date_before)
                {
                    $steps = ceil((($last_ts - $this_ts) / 86400));
                    
                    for ($i = 0; $i < $steps; $i++)
                    {
                        $next_ts = ($last_ts - (86400 * ($i+1)));
                        $date_next = date("Ymd", $next_ts);
                        
                        if ($date_next == $date)
                            break;
                            
                        array_shift($cases_last);
                        array_shift($deaths_last);
                        array_shift($dates_last);
                            
                        array_push($cases_last, 0);
                        array_push($deaths_last, 0);
                        array_push($dates_last, $date_next);
                    }
                }                
                
                array_shift($cases_last);
                array_shift($deaths_last);
                array_shift($dates_last);
                
                array_push($cases_last, $dataset->cases);
                array_push($deaths_last, $dataset->deaths);
                array_push($dates_last, $date);
                
                self::result_object_merge($dataset, $this->calculate_dataset_fields($cases_last, $deaths_last, $population, $area, $dates_last));

                $dataset_hash = self::hash_name("dataset-district", $dataset->district_hash, $dataset->date_rep);
                                
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
                
                $timestamp = strtotime($dataset->timestamp_represent);

                $this->datasets[$dataset_hash] = $dataset;
                
                if (is_object($district))
                {
                    // Now, push the results to corresponding district and its parent locations                
                    $district->cases_count = $dataset->cases;
                    $district->deaths_count = $dataset->deaths;
                    $district->recovered_count = $dataset->recovered;                   
            
                    if ($district->timestamp_min > $timestamp)
                        $district->timestamp_min = $timestamp;
                
                    if ($district->timestamp_max < $timestamp)
                        $district->timestamp_max = $timestamp;
                        
                    $district->day_of_week = date("w", $district->timestamp_max);
                    $district->day = date("j", $district->timestamp_max);
                    $district->month = date("n", $district->timestamp_max);
                    $district->year = date("Y", $district->timestamp_max);
                
                    if ((isset($district->total_cases)) && ($district->total_cases > 0))
                    {
                        if ($district->population_count)
                            $district->contamination_total = (100 / $district->population_count * $district->total_cases);
                            
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
                        
                        if ($district->infection_density > 0)
                            $district->infection_area = (1 / $district->infection_density);
                        else
                            $district->infection_area = 0;
                        
                        if ($district->infection_area > 0)
                            $district->infection_probability = (100 / ($district->infection_area * $district->population_density));
                        else
                            $district->infection_probability = 0;
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
                        $state->cases_count = $state_cases[$district->state_hash];
                        $state->deaths_count = $state_deaths[$district->state_hash];
                        $state->recovered_count = $state_recovered[$district->state_hash];
                        
                        if ($state->timestamp_min > $timestamp)
                            $state->timestamp_min = $timestamp;
                    
                        if ($state->timestamp_max < $timestamp)
                            $state->timestamp_max = $timestamp;
                
                        $state->day_of_week = date("w", $state->timestamp_max);
                        $state->day = date("j", $state->timestamp_max);
                        $state->month = date("n", $state->timestamp_max);
                        $state->year = date("Y", $state->timestamp_max);
                
                        if ((isset($state->total_cases)) && ($state->total_cases > 0))
                        {
                            if ($state->population_count)
                                $state->contamination_total = (100 / $state->population_count * $state->total_cases);
                                
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
                            
                            if ($state->infection_density > 0)
                                $state->infection_area = (1 / $state->infection_density);
                            else
                                $state->infection_area = 0;
                            
                            if ($state->infection_area > 0)
                                $state->infection_probability = (100 / ($state->infection_area * $state->population_density));
                            else
                                $state->infection_probability = 0;
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
                            $germany->cases_count = $germany_cases;
                            $germany->deaths_count = $germany_deaths;
                            $germany->recovered_count = $germany_recovered;
                            
                            if ((isset($germany->total_cases)) && ($germany->total_cases > 0))
                            {
                                if ($germany->population_count)
                                    $germany->contamination_total = (100 / $germany->population_count * $germany->total_cases);
                                    
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
                                
                                if ($germany->infection_density > 0)
                                    $germany->infection_area = (1 / $germany->infection_density);
                                else
                                    $germany->infection_area = 0;
                                
                                if ($germany->infection_area > 0)    
                                    $germany->infection_probability = (100 / ($germany->infection_area * $germany->population_density));
                                else
                                    $germany->infection_probability = 0;
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
            
        // Because of "strange" behaviour of the testresults foreign_identifier (see timestamps and id), we are forced to clear the table to get rid of duplicate datasets
        $this->database->new_testresult()->clear_records();
        
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
                    case "state":
                    case "state_id":
                        continue(2);
                }
                
                $db_obj->$key = $val;
            }
            
            switch($obj->location_type)
            {
                case "continent":
                    $x_hash = "N".$obj->continent_hash;
                    break;
                case "country":
                    $x_hash = "C".$obj->country_hash;
                    break;
                case "state":
                    $x_hash = "S".$obj->state_hash;
                    break;
                case "district":
                    $x_hash = "D".$obj->district_hash;
                    break;
                case "location":
                    $x_hash = "L".$obj->location_hash;
                    break;
                default:
                    $x_hash = $hash;
                    break;
            }
                    
            $db_obj->locations_uid = $this->location_index[$x_hash];
                    
            $error = null;
                    
            if ($db_obj->save(null, null, false, false, $error))
                $count++;
            else
                array_push($errors, $error);

            $any++;
        }
        
        $this->database_transaction_commit("save_testresult");
        
        return $count;
    }
    
    public function save_divis(&$count = null, &$any = null, &$errors = null)
    {
        $count = 0;
        $any = 0;
        
        $errors = array();
        
        if (!is_array($this->divis))
            return null;
            
        $this->database_transaction_begin("save_divi");
        
        foreach ($this->divis as $hash => $obj)
        {
            $db_obj = $this->database->new_divi();
            
            foreach ($obj as $key => $val)
                $db_obj->$key = $val;
                
            $x_hash = "D".$obj->district_hash;
            
            $db_obj->locations_uid = $this->location_index[$x_hash];
            
            $error = null;
            
            if ($db_obj->save(null, null, false, false, $error))
                $count++;
            else
                array_push($errors, $error);

            $any++;
        }
        
        $this->database_transaction_commit("save_divi");
        
        return $count;
    }
    
    public function save_datasets(&$count = null, &$any = null, &$errors = null)
    {
        $count = 0;
        $any = 0;
        
        $errors = array();
        
        if (!is_array($this->datasets))
            return null;
            
        $this->database_transaction_begin("save_dataset");
        
        foreach ($this->datasets as $hash => $obj)
        {
            $db_obj = $this->database->new_dataset();

            foreach ($obj as $key => $val)
                $db_obj->$key = $val;
                
            switch($obj->location_type)
            {
                case "continent":
                    $x_hash = "N".$obj->continent_hash;
                    break;
                case "country":
                    $x_hash = "C".$obj->country_hash;
                    break;
                case "state":
                    $x_hash = "S".$obj->state_hash;
                    break;
                case "district":
                    $x_hash = "D".$obj->district_hash;
                    break;
                case "location":
                    $x_hash = "L".$obj->location_hash;
                    break;
                default:
                    $x_hash = $hash;
                    break;
            }
                    
            $db_obj->locations_uid = $this->location_index[$x_hash];
                    
            $error = null;
            
            if ($db_obj->save(null, null, false, false, $error))
                $count++;
            else
                array_push($errors, $error);

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
        
        $europe_hash = self::hash_name("continent", "Europe");
        $germany_hash = self::hash_name("country", "Germany", $europe_hash);
        
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
            
            $error = null;
                    
            if ($db_obj->save(null, null, false, false, $error))
                $count++;
            else
                array_push($errors, $error);

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
        
        $this->location_index = array();
        
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
                    
                switch($obj->location_type)
                {
                    case "continent":
                        $x_hash = "N".$obj->continent_hash;
                        break;
                    case "country":
                        $x_hash = "C".$obj->country_hash;
                        break;
                    case "state":
                        $x_hash = "S".$obj->state_hash;
                        break;
                    case "district":
                        $x_hash = "D".$obj->district_hash;
                        break;
                    case "location":
                        $x_hash = "L".$obj->location_hash;
                        break;
                    default:
                        $x_hash = $hash;
                        break;
                }
                
                $error = null;

                if ($this->location_index[$x_hash] = $db_obj->save(null, null, false, false, $error))
                    $count++;
                else
                    array_push($errors, $error);
                
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
            $this->stores_loaded_bytes += $this->retrieve_eu_coviddata($cache_timeout);
            $this->stores_loaded_count++;
            
            $this->stores_loaded_bytes += $this->retrieve_rki_rssfeed($cache_timeout);
            $this->stores_loaded_count++;
                       
            $this->stores_loaded_bytes += $this->retrieve_rki_nowcast($cache_timeout);
            $this->stores_loaded_count++;

            $this->stores_loaded_bytes += $this->retrieve_rki_positive($cache_timeout);
            $this->stores_loaded_count++;
                                   
            $this->stores_loaded_bytes += $this->retrieve_divi_intens($cache_timeout);
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
            $this->eu_coviddata = $this->get_template(new DataHandler($this->config, $this->config->url_eu_coviddata));
            $this->rki_positive = $this->get_template(new DataHandler($this->config, $this->config->url_rki_positive));
            $this->rki_nowcast = $this->get_template(new DataHandler($this->config, $this->config->url_rki_nowcast));
            $this->rki_rssfeed = $this->get_template(new DataHandler($this->config, $this->config->url_rki_rssfeed));
            $this->divi_intens = $this->get_template(new DataHandler($this->config, $this->config->url_divi_intens));
            
            // This is important, because divi has a different delimiter!
            $this->divi_intens->handler->http_handler_set_csv_delimiter(",");
            
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
