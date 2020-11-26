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
    private $district_index = null;
    private $divi_index = null;
    
    private $datasets = null;
    private $testresults = null;
    private $nowcasts = null;
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
        
        // Create an index for districts, so that RKI positives can be assigned faster
        $this->district_index = array();
        
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
                    
                $state->geo_id = DataHandler::german_state_id_by_name($state_name, $real_state_name);
                $state->location_hash = self::hash_name("location", "state", $state_hash);
                $state->location_tags = "state, ".$europe->continent_name.", ".$germany->country_name.", ".$real_state_name ?: $state_name;
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
                        
                    $state->geo_id = DataHandler::german_state_id_by_name($state_name, $real_state_name);
                    $state->location_hash = self::hash_name("location", "state", $state_hash);
                    $state->location_tags = "state, ".$europe->continent_name.", ".$germany->country_name.", ".$real_state_name ?: $state_name;
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
                    
                $this->district_index[(double)$district_id] = $district_hash;
                    
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
                
                $districts[$district_hash] = $district;
            }
        }
               
        unset($german_districts_area);
        unset($german_districts_population);        

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
    
    public function master_divis($hold_data = false)
    {
        $europe_hash = self::hash_name("continent", "Europe");
        $germany_hash = self::hash_name("country", "Germany", $europe_hash);
                
        $europe = $this->continents[$europe_hash];
        $germany = $this->countries[$germany_hash];

        $german_divi = $this->divi_intens->handler->get_data();
        
        $this->divi_index = array(
            "district" => array(), 
            "state" => array(), 
            "country" => array()
        );
        
        $divis = array();

        if (is_array($german_divi))
        {
            foreach ($german_divi as $divi)
            {
                if (isset($this->district_index[$divi->district_id]))
                {
                    $district_hash = $this->district_index[$divi->district_id];
                    
                    if (!$district_hash)
                        continue;
                        
                    if (!isset($this->districts[$district_hash]))
                        continue;
                        
                    $district = $this->districts[$district_hash];
                    
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
                    
                    $this->divi_index["district"][$district->district_hash] = $divi->divi_hash;
                    
                    if (!isset($this->divi_index["state"][$district->state_hash]))
                        $this->divi_index["state"][$district->state_hash] = array();
                        
                    $this->divi_index["state"][$district->state_hash][$district->district_hash] = $divi->divi_hash;
                    
                    if (!isset($this->divi_index["country"][$district->country_hash]))
                        $this->divi_index["country"][$district->country_hash] = array();
                        
                    $this->divi_index["country"][$district->country_hash][$district->district_hash] = $divi->divi_hash;
                    
                    $divis[$divi->divi_hash] = $divi;
		}
            }
        }
                
        unset($german_divi);
        
        $this->divis = $divis;
        
        // Free the memory, which is no longer need (if hold data is not requested)
        if (!$hold_data)
            $this->divi_intens->handler->free();
    
        return true;    
    }
    
    public function master_nowcasts($hold_data = false)
    {       
        $europe_hash = self::hash_name("continent", "Europe");
        $germany_hash = self::hash_name("country", "Germany", $europe_hash);
                
        $europe = $this->continents[$europe_hash];
        $germany = $this->countries[$germany_hash];

        $max_timestamp = -1;
        $max_data = null;

        $esteem_reproduction_r = 0;
        $lower_reproduction_r = 0;
        $upper_reproduction_r = 0;

        $esteem_7day_r_value = 0;
        $lower_7day_r_value = 0;
        $upper_7day_r_value = 0;
        
        $nowcast = $this->rki_nowcast->handler->get_data();        
        $nowcasts = array();
        
        foreach ($nowcast as $id => $data)
        {
            $ts = strtotime($data->timestamp_represent);
            $nowcast_hash = self::hash_name("nowcast", $germany_hash, $data->date_rep);
            
            $nowcast[$id]->flag_casted_r_values = 0;
            
            foreach ($data as $key => $val)
            {
                switch ($key)
                {
                    case "esteem_reproduction_r":
                        if ($val > 0) $esteem_reproduction_r = $val; elseif ($val == 0) { $nowcast[$id]->flag_casted_r_values = 1; $val = $esteem_reproduction_r; }
                        break;
                    case "lower_reproduction_r":
                        if ($val > 0) $lower_reproduction_r = $val; elseif ($val == 0) { $nowcast[$id]->flag_casted_r_values = 1; $val = $lower_reproduction_r; }
                        break;
                    case "upper_reproduction_r":
                        if ($val > 0) $upper_reproduction_r = $val; elseif ($val == 0) { $nowcast[$id]->flag_casted_r_values = 1; $val = $upper_reproduction_r; }
                        break;
                    case "esteem_7day_r_value":
                        if ($val > 0) $esteem_7day_r_value = $val; elseif ($val == 0) { $nowcast[$id]->flag_casted_r_values = 1; $val = $esteem_7day_r_value; }
                        break;
                    case "lower_7day_r_value":
                        if ($val > 0) $lower_7day_r_value = $val; elseif ($val == 0) { $nowcast[$id]->flag_casted_r_values = 1; $val = $lower_7day_r_value; }
                        break;
                    case "upper_7day_r_value":
                        if ($val > 0) $upper_7day_r_value = $val; elseif ($val == 0) { $nowcast[$id]->flag_casted_r_values = 1; $val = $upper_7day_r_value; }
                        break;
                }
                
                $nowcast[$id]->$key = $val;
            }
            
            $nowcast[$id]->continent_hash = $europe_hash;
            $nowcast[$id]->country_hash = $germany_hash;
            $nowcast[$id]->nowcast_hash = $nowcast_hash;
            $nowcast[$id]->location_type = "country";
            
            $nowcasts[$nowcast_hash] = $nowcast[$id];
                
            if ($ts > $max_timestamp)
            {
                $max_timestamp = $ts;
                $max_data = clone $data;
            }
        }
        
        // The max_data variable holds the latest nowcast. This is for future use.
        
        unset($nowcast);
        unset($max_data);
        
        $this->nowcasts = $nowcasts;
        
        // Free the memory, which is no longer need (if hold data is not requested)
        if (!$hold_data)
            $this->rki_nowcast->handler->free();
    
        return true;    
    }
    
    public function get_last_x_days($cases, $deaths, $recovered, $dates, $days = 7, $skip_days = 0, &$reproduction_available = null)
    {
        if ($days <= 0)
            return null;
            
        if (!is_array($cases))
            return null;
            
        if (!is_array($deaths))
            return null;
            
        if (!is_array($recovered))
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
    
        if (count($recovered) < $xindex)
        {
            for ($i = count($recovered); $i < $xindex; $i++)
            {
                $recovered[$i] = (int)0;
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
            $tmp->recovered = (int)0;
            $tmp->set_date = false;
            $tmp->set_case = false;
            $tmp->set_deaths = false;
            $tmp->set_recovered = false;
            
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
            
            if (!isset($recovered[$i]))
                break;
                
            $result[$n]->recovered = $recovered[$i];
            $result[$n]->set_recovered = true;
            
            $n++;
        }
        
        return $result;
    }
    
    public function calculate_x_day_fields($cases, $deaths, $recovered, $population, $area, $dates, $days = 7, $skip_days = 0, $incidence_factor = 100000)
    {
        if ($days <= 0)
            return null;
            
        $reproduction_available = false;
        
        $last_x = $this->get_last_x_days($cases, $deaths, $recovered, $dates, $days, $skip_days);
        
        $cases_now = $cases[0];
        $deaths_now = $deaths[0];
        $recovered_now = $recovered[0];

        if (!$last_x)
            return null;

        $result = new \stdClass;
        $result->cases = (int)0;
        $result->deaths = (int)0;
        $result->recovered = (int)0;

        $cases1 = (int)0;
        $n = 0;

        foreach ($last_x as $obj)
        {
            $result->cases += (int)$obj->cases ?: 0;
            $result->deaths += (int)$obj->deaths ?: 0;
            $result->recovered += (int)$obj->recovered ?: 0;
            
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

    public function calculate_x_day_r_value($cases, $deaths, $recovered, $dates, $days = 7, $skip_days = 0, &$reproduction_available = null)
    {
        if ($days <= 0)
            return null;
            
        $reproduction_available = false;
        
        $result = new \stdClass;

        $result->prefix = $this->get_last_x_days($cases, $deaths, $recovered, $dates, $days, $skip_days);
        $result->suffix = $this->get_last_x_days($cases, $deaths, $recovered, $dates, $days, ($skip_days + $days), $reproduction_available);
        
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
    
    public function calculate_14day_r_value($cases, $deaths, $recovered, $dates, $skip_days = 0)
    {
        $reproduction_available = false;
        
        $obj = $this->calculate_x_day_r_value($cases, $deaths, $recovered, $dates, 14, $skip_days, $reproduction_available);
        
        if (!$reproduction_available)
            return null;

        if (!$obj)
            return null;
            
        $result = new \stdClass;
        $result->reproduction_14day = $obj->r_value;

        return $result;
    }

    public function calculate_7day_r_value($cases, $deaths, $recovered, $dates, $skip_days = 0)
    {
        $reproduction_available = false;
        
        $obj = $this->calculate_x_day_r_value($cases, $deaths, $recovered, $dates, 7, $skip_days, $reproduction_available);
        
        if (!$reproduction_available)
            return null;

        if (!$obj)
            return null;

        $result = new \stdClass;
        $result->reproduction_7day = $obj->r_value;

        return $result;
    }

    public function calculate_4day_r_value($cases, $deaths, $recovered, $dates, $skip_days = 0)
    {
        $reproduction_available = false;
        
        $obj = $this->calculate_x_day_r_value($cases, $deaths, $recovered, $dates, 4, $skip_days, $reproduction_available);
        
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

    public function calculate_14day_fields($cases, $deaths, $recovered, $population, $area, $dates, $incidence_factor = 100000)
    {
        $obj = $this->calculate_x_day_fields($cases, $deaths, $recovered, $population, $area, $dates, 14, 0, $incidence_factor);
        $obj2 = $this->calculate_x_day_fields($cases, $deaths, $recovered, $population, $area, $dates, 14, 3, $incidence_factor);

        if (!$obj)
            return null;

        $result = new \stdClass;

        if ($obj->cases == 0)
            $result->flag_case_free = 0;
        
        $result->cases_14day_average = $obj->cases;
        $result->deaths_14day_average = $obj->deaths;
        $result->recovered_14day_average = $obj->recovered;
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

    public function calculate_7day_fields($cases, $deaths, $recovered, $population, $area, $dates, $incidence_factor = 100000)
    {
        $obj = $this->calculate_x_day_fields($cases, $deaths, $recovered, $population, $area, $dates, 7, 0, $incidence_factor);
        $obj2 = $this->calculate_x_day_fields($cases, $deaths, $recovered, $population, $area, $dates, 7, 3, $incidence_factor);

        if (!$obj)
            return null;

        $result = new \stdClass;

        $result->cases_7day_average = $obj->cases;
        $result->deaths_7day_average = $obj->deaths;
        $result->recovered_7day_average = $obj->recovered;
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

    public function calculate_4day_fields($cases, $deaths, $recovered, $population, $area, $dates, $incidence_factor = 100000)
    {
        $obj = $this->calculate_x_day_fields($cases, $deaths, $recovered, $population, $area, $dates, 4, 0, $incidence_factor);
        $obj2 = $this->calculate_x_day_fields($cases, $deaths, $recovered, $population, $area, $dates, 4, 3, $incidence_factor);

        if (!$obj)
            return null;

        $result = new \stdClass;

        $result->cases_4day_average = $obj->cases;
        $result->deaths_4day_average = $obj->deaths;
        $result->recovered_4day_average = $obj->recovered;
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

    public function calculate_case_death_and_recov_rates($cases, $deaths, $recovered, $population, $dates)
    {
        if ($population == 0)
            return false;
            
        $cases_now = $cases[0];
        $deaths_now = $deaths[0];
        $recovered_now = $recovered[0];
            
        $result = new \stdClass;

        // This is a snapshot of the current days rate of cases AND NOT THE POSITIVE RATE BY PERFORMED TESTS
        $result->cases_rate = (100 / $population * $cases_now);
        
        // The rate of deaths for the current day
        $result->deaths_rate = (100 / $population * $deaths_now);

        // The rate of recovered for the current day
        $result->recovered_rate = (100 / $population * $recovered_now);

        return $result;
    }
    
    public function calculate_dataset_fields($cases, $deaths, $recovered, $population, $area, $dates, $incidence_factor = 100000)
    {
        if (!is_array($cases))
            return null;
            
        if (!is_array($deaths))
            return null;
            
        if (!is_array($dates))
            return null;
        
        $result = new \stdClass;
        
        self::result_object_merge($result, $this->calculate_4day_fields($cases, $deaths, $recovered, $population, $area, $dates, $incidence_factor));
        self::result_object_merge($result, $this->calculate_7day_fields($cases, $deaths, $recovered, $population, $area, $dates, $incidence_factor));
        self::result_object_merge($result, $this->calculate_14day_fields($cases, $deaths, $recovered, $population, $area, $dates, $incidence_factor));
        
        self::result_object_merge($result, $this->calculate_case_death_and_recov_rates($cases, $deaths, $recovered, $population, $dates));
        
        self::result_object_merge($result, $this->calculate_4day_r_value($cases, $deaths, $recovered, $dates));
        self::result_object_merge($result, $this->calculate_7day_r_value($cases, $deaths, $recovered, $dates));
        self::result_object_merge($result, $this->calculate_14day_r_value($cases, $deaths, $recovered, $dates));

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
    
    public function finalize_datasets($dataset_index, $location_type, &$datasets)
    {
        if ($location_type == "auto")
            $auto_location = true;
        else
            $auto_location = false;
            
        foreach ($dataset_index as $index => $data)
        {
            krsort($data);
            
            foreach ($data as $date => $hash)
            {                
                $cases = array();
                $deaths = array();
                $recovs = array();
                $dates = array();
            
                foreach ($data as $date2 => $hash2)
                {
                    if ($date2 > $date)
                        continue;
                        
                    array_push($cases, $datasets[$hash2]->cases_today);
                    array_push($deaths, $datasets[$hash2]->deaths_today);
                    array_push($recovs, $datasets[$hash2]->recovered_today);
                    array_push($dates, $date2);
                
                    if (count($cases) > 32)
                        break;
                }
                
                for ($i = (count($cases) - 1); $i < 32; $i++)
                {
                    $cases[$i] = (int)0;
                    $deaths[$i] = (int)0;
                    $recovs[$i] = (int)0;
                    $dates[$i] = (int)0;
                }
                
                $population_count = 0;
                $area = 0;
                
                if ($auto_location)
                {
                    switch (substr($index, 0, 1))
                    {
                        case "N":
                            $location_type = "continent";
                            break;
                        case "C":
                            $location_type = "country";
                            break;
                        case "S":
                            $location_type = "state";
                            break;
                        case "D":
                            $location_type = "district";
                            break;
                        case "L":
                            $location_type = "location";
                            break;
                        default:
                            $location_type = null;
                            break;
                    }
                }
                
                switch ($location_type)
                {
                    case "continent":
                    case "country":
                    case "state":
                    case "district":
                    case "location":
                        $loc_key = $location_type."_hash";
                        $loc_obj = $location_type."s";

                        $loc_hash = $datasets[$hash]->$loc_key;
                        
                        if (isset($this->$loc_obj[$loc_hash]))
                        {
                            if (isset($this->$loc_obj[$loc_hash]->population_count))
                                $population_count = $this->$loc_obj[$loc_hash]->population_count;
                            
                            if (isset($this->$loc_obj[$loc_hash]->area))
                                $area = $this->$loc_obj[$loc_hash]->area;
                        }
                        break;
                }
                    
                self::result_object_merge($datasets[$hash], $this->calculate_dataset_fields($cases, $deaths, $recovs, $population_count, $area, $dates));
            }
        }
        
        return true;
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

            $dataset_hash = self::hash_name("dataset-continent", $continent_hash, $record->date_rep."0");
            
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
            
            $index = "N0".$dataset->continent_hash;
            
            if (!isset($dataset_index[$index]))
                $dataset_index[$index] = array();
                
            $date = date("Ymd", strtotime($record->timestamp_represent));
                
            $dataset_index[$index][$date] = $dataset_hash;
            
            $datasets[$dataset_hash] = $dataset;

            $dataset_hash = self::hash_name("dataset-country", $country_hash, $record->date_rep."0");

            if (!isset($datasets[$dataset_hash]))
                $dataset = $this->create_dataset_template();
            else
                $dataset = $datasets[$dataset_hash];
                
            $dataset->dataset_hash = $dataset_hash;
            $dataset->country_hash = $country_hash;
            $dataset->continent_hash = $continent_hash;
            $dataset->day_of_week = $record->day_of_week;
            $dataset->day = $record->day;
            $dataset->month = $record->month;
            $dataset->year = $record->year;
            
            // Only count, if country is not germany. The german counters are set by testresults, later.
            if ($record->country != "Germany")
            {
                $dataset->cases_count += $record->cases;
                $dataset->deaths_count += $record->deaths;
            }
            
            $dataset->timestamp_represent = $record->timestamp_represent;
            $dataset->location_type = "country";

            $index = "C0".$dataset->country_hash;
            
            if (!isset($dataset_index[$index]))
                $dataset_index[$index] = array();
                
            $date = date("Ymd", strtotime($record->timestamp_represent));
                
            $dataset_index[$index][$date] = $dataset_hash;
            
            $datasets[$dataset_hash] = $dataset;
        }
        
        // Finalize dataset calculation
        $this->finalize_datasets($dataset_index, "auto", $datasets);
        
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
        
        $tmpl->divi_cases_covid = (int)0;
        $tmpl->divi_cases_covid_ventilated = (int)0;
        $tmpl->divi_reporting_areas = (int)0;
        $tmpl->divi_locations_count = (int)0;
        $tmpl->divi_beds_free = (int)0;
        $tmpl->divi_beds_occupied = (int)0;
        $tmpl->divi_beds_total = (int)0;

        $tmpl->nowcast_esteem_new_diseases = (int)0;
        $tmpl->nowcast_lower_new_diseases = (int)0;
        $tmpl->nowcast_upper_new_diseases = (int)0;
        $tmpl->nowcast_esteem_new_diseases_ma4 = (int)0;
        $tmpl->nowcast_lower_new_diseases_ma4 = (int)0;
        $tmpl->nowcast_upper_new_diseases_ma4 = (int)0;
        $tmpl->nowcast_esteem_reproduction_r = (float)0;
        $tmpl->nowcast_lower_reproduction_r = (float)0;
        $tmpl->nowcast_upper_reproduction_r = (float)0;
        $tmpl->nowcast_esteem_7day_r_value = (float)0;
        $tmpl->nowcast_lower_7day_r_value = (float)0;
        $tmpl->nowcast_upper_7day_r_value = (float)0;
        
        $tmpl->flag_casted_r_values = 0;
        
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
            
            foreach (array("new", "count", "delta", "today", "yesterday", "total", "pointer") as $suffix)
            {
                foreach ($age_groups as $agegroup)
                {
                    $ag = str_replace(":", "_", $agegroup);
                    
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
        
        $dataset_index = array();
        
        // Create a totals array
        $total = array(
            "district" => array(
                "0_4" => array(
                    "cases" => array(),
                    "deaths" => array(),
                    "recovered" => array()
                ),
                "5_14" => array(
                    "cases" => array(),
                    "deaths" => array(),
                    "recovered" => array()
                ),
                "15_34" => array(
                    "cases" => array(),
                    "deaths" => array(),
                    "recovered" => array()
                ),
                "35_59" => array(
                    "cases" => array(),
                    "deaths" => array(),
                    "recovered" => array()
                ),
                "60_79" => array(
                    "cases" => array(),
                    "deaths" => array(),
                    "recovered" => array()
                ),
                "80_plus" => array(
                    "cases" => array(),
                    "deaths" => array(),
                    "recovered" => array()
                ),
                "unknown" => array(
                    "cases" => array(),
                    "deaths" => array(),
                    "recovered" => array()
                ),
                "any" => array(
                    "cases" => array(),
                    "deaths" => array(),
                    "recovered" => array()
                )
            ),
            "state" => array(
                "0_4" => array(
                    "cases" => array(),
                    "deaths" => array(),
                    "recovered" => array()
                ),
                "5_14" => array(
                    "cases" => array(),
                    "deaths" => array(),
                    "recovered" => array()
                ),
                "15_34" => array(
                    "cases" => array(),
                    "deaths" => array(),
                    "recovered" => array()
                ),
                "35_59" => array(
                    "cases" => array(),
                    "deaths" => array(),
                    "recovered" => array()
                ),
                "60_79" => array(
                    "cases" => array(),
                    "deaths" => array(),
                    "recovered" => array()
                ),
                "80_plus" => array(
                    "cases" => array(),
                    "deaths" => array(),
                    "recovered" => array()
                ),
                "unknown" => array(
                    "cases" => array(),
                    "deaths" => array(),
                    "recovered" => array()
                ),
                "any" => array(
                    "cases" => array(),
                    "deaths" => array(),
                    "recovered" => array()
                )
            ),            
            "country" => array(
                "0_4" => array(
                    "cases" => 0,
                    "deaths" => 0,
                    "recovered" => 0
                ),
                "5_14" => array(
                    "cases" => 0,
                    "deaths" => 0,
                    "recovered" => 0
                ),
                "15_34" => array(
                    "cases" => 0,
                    "deaths" => 0,
                    "recovered" => 0
                ),
                "35_59" => array(
                    "cases" => 0,
                    "deaths" => 0,
                    "recovered" => 0
                ),
                "60_79" => array(
                    "cases" => 0,
                    "deaths" => 0,
                    "recovered" => 0
                ),
                "80_plus" => array(
                    "cases" => 0,
                    "deaths" => 0,
                    "recovered" => 0
                ),
                "unknown" => array(
                    "cases" => 0,
                    "deaths" => 0,
                    "recovered" => 0
                ),
                "any" => array(
                    "cases" => 0,
                    "deaths" => 0,
                    "recovered" => 0
                )
            ),
            
        );
        
        // Create gender_id based counter arrays from total
        $totals = array(
            "0" => $total,
            "1" => $total,
            "2" => $total,
            "3" => $total
        );
        
        unset($total);
                                                
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
            
            $ts = strtotime($testresult->timestamp_represent);
            $date = date("Ymd", $ts);
            
            switch ($testresult->gender)
            {
                case "female":
                    $gender_id = 1;
                    break;
                case "male":
                    $gender_id = 2;
                    break;
                case "asterisk":
                    $gender_id = 3;
                    break;
            }
            
            // We need to store datasets twice! One as summary and another for the given gender. So we do a loop here!
            foreach (array(0, $gender_id) as $selected_gender)
            {   
                // Also set the upstream hierarchie
                foreach (array("district", "state", "country") as $location_type)
                {                
                    switch ($location_type)
                    {
                        case "district":
                            $index = "D".$selected_gender.$testresult->district_hash;
                            $dataset_hash = self::hash_name("dataset-district", $testresult->district_hash, $data->date_rep.$selected_gender);
                            break;
                        case "state":
                            $index = "S".$selected_gender.$testresult->state_hash;
                            $dataset_hash = self::hash_name("dataset-state", $testresult->state_hash, $data->date_rep.$selected_gender);
                            break;
                        case "country":
                            $index = "C".$selected_gender.$testresult->country_hash;
                            $dataset_hash = self::hash_name("dataset-country", $testresult->country_hash, $data->date_rep.$selected_gender);
                            break;
                        default:
                            continue(2);
                    }
                
                    $dataset_index[$index][$date] = $dataset_hash;
                
                    // Create or update dateset
                    if (!isset($datasets[$dataset_hash]))
                    {
                        $dataset = $this->create_dataset_template();
                        
                        $dataset->dataset_hash = $dataset_hash;
                        $dataset->dataset_gender = $selected_gender;
                        
                        if ($location_type == "district")
                            $dataset->district_hash = $district_hash;
    
                        if (($location_type == "state") || ($location_type == "district"))
                            $dataset->state_hash = $state_hash;
                        
                        $dataset->country_hash = $germany_hash;
                        $dataset->continent_hash = $europe_hash;
                        $dataset->day_of_week = $data->day_of_week;
                        $dataset->day = $data->day;
                        $dataset->month = $data->month;
                        $dataset->year = $data->year;
                        $dataset->timestamp_represent = $data->timestamp_represent;
                        $dataset->date_rep = $data->date_rep;
                        $dataset->location_type = $location_type;
                    }
                    else
                    {
                        $dataset = $datasets[$dataset_hash];
                    }
                    
                    // Write corresponding divi registrations to dataset
                    $divis = array();
                    
                    switch ($location_type)
                    {
                        case "district":
                            if (isset($this->divi_index["district"][$dataset->district_hash]))
                            {
                                $divi_hash = $this->divi_index["district"][$dataset->district_hash];
                                
                                if (isset($this->divis[$divi_hash]))
                                    array_push($divis, $divi_hash);
                            }
                            break;
                        case "state":
                            if (isset($this->divi_index["state"][$dataset->state_hash]))
                            {
                                foreach ($this->divi_index["state"][$dataset->state_hash] as $divi_hash)
                                {                                
                                    if (isset($this->divis[$divi_hash]))
                                        array_push($divis, $divi_hash);
                                }
                            }
                            break;
                        case "country":
                            if (isset($this->divi_index["country"][$dataset->country_hash]))
                            {
                                foreach ($this->divi_index["country"][$dataset->country_hash] as $divi_hash)
                                {                                
                                    if (isset($this->divis[$divi_hash]))
                                        array_push($divis, $divi_hash);
                                }
                            }
                            break;
                    }
                    
                    foreach ($divis as $divi_hash)
                    {
                        if (!isset($this->divis[$divi_hash]))
                            continue;
                            
                        $divi = $this->divis[$divi_hash];
                        
                        if ($divi->date_rep != $dataset->date_rep)
                            continue;
                        
                        $dataset->divi_cases_covid += $divi->cases_covid;
                        $dataset->divi_cases_covid_ventilated += $divi->cases_covid_ventilated;
                        $dataset->divi_reporting_areas += $divi->reporting_areas;
                        $dataset->divi_locations_count += $divi->locations_count;
                        $dataset->divi_beds_free += $divi->beds_free;
                        $dataset->divi_beds_occupied += $divi->beds_occupied;
                        $dataset->divi_beds_total += $divi->beds_total;
                    }
                    
                    // Merge the nowcasts
                    foreach ($this->nowcasts as $nowcast_hash => $nowcast)
                    {
                        if ($nowcast->date_rep != $dataset->date_rep)
                            continue;
                            
                        $dataset->nowcast_esteem_new_diseases = $nowcast->esteem_new_diseases;
                        $dataset->nowcast_lower_new_diseases = $nowcast->lower_new_diseases;
                        $dataset->nowcast_upper_new_diseases = $nowcast->upper_new_diseases;
                        $dataset->nowcast_esteem_new_diseases_ma4 = $nowcast->esteem_new_diseases_ma4;
                        $dataset->nowcast_lower_new_diseases_ma4 = $nowcast->lower_new_diseases_ma4;
                        $dataset->nowcast_upper_new_diseases_ma4 = $nowcast->upper_new_diseases_ma4;
                        $dataset->nowcast_esteem_reproduction_r = $nowcast->esteem_reproduction_r;
                        $dataset->nowcast_lower_reproduction_r = $nowcast->lower_reproduction_r;
                        $dataset->nowcast_upper_reproduction_r = $nowcast->upper_reproduction_r;
                        $dataset->nowcast_esteem_7day_r_value = $nowcast->esteem_7day_r_value;
                        $dataset->nowcast_lower_7day_r_value = $nowcast->lower_7day_r_value;
                        $dataset->nowcast_upper_7day_r_value = $nowcast->upper_7day_r_value;
                    }
                    
                    if (($data->cases_new == 0) || ($data->cases_new == 1))
                    {
                        $dataset->cases_new += $data->cases_count;                        
                        
                        switch ($location_type)
                        {
                            case "state":
                            case "district":
                                if ($location_type == "district") $t_hash = $district_hash; else $t_hash = $state_hash;
                                
                                if (!isset($totals[$gender_id][$location_type]["any"]["cases"][$t_hash]))
                                    $totals[$gender_id][$location_type]["any"]["cases"][$t_hash] = 0;
                                    
                                $totals[$gender_id][$location_type]["any"]["cases"][$t_hash]++;
                                
                                $dataset->cases_total = $totals[$gender_id][$location_type]["any"]["cases"][$t_hash];
                                break;
                            case "country":                                    
                                $totals[$gender_id]["country"]["any"]["cases"]++;
                                
                                $dataset->cases_total = $totals[$gender_id]["country"]["any"]["cases"];
                                break;
                        }
                    }
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
                    {
                        $dataset->deaths_new += $data->deaths_count;
                        
                        switch ($location_type)
                        {
                            case "state":
                            case "district":
                                if ($location_type == "district") $t_hash = $district_hash; else $t_hash = $state_hash;
                                
                                if (!isset($totals[$gender_id][$location_type]["any"]["deaths"][$t_hash]))
                                    $totals[$gender_id][$location_type]["any"]["deaths"][$t_hash] = 0;
                                    
                                $totals[$gender_id][$location_type]["any"]["deaths"][$t_hash]++;
                                
                                $dataset->cases_total = $totals[$gender_id][$location_type]["any"]["deaths"][$t_hash];
                                break;
                            case "country":                                    
                                $totals[$gender_id]["country"]["any"]["deaths"]++;
                                
                                $dataset->cases_total = $totals[$gender_id]["country"]["any"]["deaths"];
                                break;
                        }
                    }
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
                    {
                        $dataset->recovered_new += $data->recovered_count;
                        
                        switch ($location_type)
                        {
                            case "state":
                            case "district":
                                if ($location_type == "district") $t_hash = $district_hash; else $t_hash = $state_hash;
                                
                                if (!isset($totals[$gender_id][$location_type]["any"]["recovered"][$t_hash]))
                                    $totals[$gender_id][$location_type]["any"]["recovered"][$t_hash] = 0;
                                    
                                $totals[$gender_id][$location_type]["any"]["recovered"][$t_hash]++;
                                
                                $dataset->cases_total = $totals[$gender_id][$location_type]["any"]["recovered"][$t_hash];
                                break;
                            case "country":                                    
                                $totals[$gender_id]["country"]["any"]["recovered"]++;
                                
                                $dataset->cases_total = $totals[$gender_id]["country"]["any"]["recovered"];
                                break;
                        }
                    }                    
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
                        $age_low = -1;
                        $age_high = -1;
                        
                        if ((is_object($data->age_group2)) && (isset($data->age_group2->upper)) && ($data->age_group2->upper > -1))
                        {
                            $age_low = $data->age_group2->lower;
                            $age_high = $data->age_group2->upper;
                        }
                        
                        if ((is_object($data->age_group)) && (isset($data->age_group->upper)) && ($data->age_group->upper > -1) && ($age_high == -1))
                        {
                            $age_low = $data->age_group->lower;
                            $age_high = $data->age_group->upper;
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
                            $key_cases_yesterday = "cases_yesterday_agegroup_".$set_suffix;
                            $key_cases_total = "cases_total_agegroup_".$set_suffix;
                            $key_cases_pointer = "cases_pointer_agegroup_".$set_suffix;
                    
                            if (($data->cases_new == 0) || ($data->cases_new == 1))
                            {
                                $dataset->$key_cases_new += $data->cases_count;
                        
                                switch ($location_type)
                                {
                                    case "state":
                                    case "district":
                                        if ($location_type == "district") $t_hash = $district_hash; else $t_hash = $state_hash;
                                        
                                        if (!isset($totals[$gender_id][$location_type][$set_suffix]["cases"][$t_hash]))
                                            $totals[$gender_id][$location_type][$set_suffix]["cases"][$t_hash] = 0;
                                            
                                        $totals[$gender_id][$location_type][$set_suffix]["cases"][$t_hash]++;
                                        
                                        $dataset->$key_cases_total = $totals[$gender_id][$location_type][$set_suffix]["cases"][$t_hash];
                                        break;
                                    case "country":                                    
                                        $totals[$gender_id]["country"][$set_suffix]["cases"]++;
                                        
                                        $dataset->$key_cases_total = $totals[$gender_id]["country"][$set_suffix]["cases"];
                                        break;
                                }
                            }
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
                            $key_deaths_yesterday = "deaths_yesterday_agegroup_".$set_suffix;
                            $key_deaths_total = "deaths_total_agegroup_".$set_suffix;
                            $key_deaths_pointer = "deaths_pointer_agegroup_".$set_suffix;
                        
                            if (($data->deaths_new == 0) || ($data->deaths_new == 1))
                            {
                                $dataset->$key_deaths_new += $data->deaths_count;
                        
                                switch ($location_type)
                                {
                                    case "state":
                                    case "district":
                                        if ($location_type == "district") $t_hash = $district_hash; else $t_hash = $state_hash;
                                        
                                        if (!isset($totals[$gender_id][$location_type][$set_suffix]["deaths"][$t_hash]))
                                            $totals[$gender_id][$location_type][$set_suffix]["deaths"][$t_hash] = 0;
                                            
                                        $totals[$gender_id][$location_type][$set_suffix]["deaths"][$t_hash]++;
                                        
                                        $dataset->$key_cases_total = $totals[$gender_id][$location_type][$set_suffix]["deaths"][$t_hash];
                                        break;
                                    case "country":                                    
                                        $totals[$gender_id]["country"][$set_suffix]["deaths"]++;
                                        
                                        $dataset->$key_cases_total = $totals[$gender_id]["country"][$set_suffix]["deaths"];
                                        break;
                                }
                            }
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
                            $key_recovered_yesterday = "recovered_yesterday_agegroup_".$set_suffix;
                            $key_recovered_total = "recovered_total_agegroup_".$set_suffix;
                            $key_recovered_pointer = "recovered_pointer_agegroup_".$set_suffix;
                        
                            if (($data->deaths_new == 0) || ($data->deaths_new == 1))
                            {
                                $dataset->$key_recovered_new += $data->deaths_count;
                        
                                switch ($location_type)
                                {
                                    case "state":
                                    case "district":
                                        if ($location_type == "district") $t_hash = $district_hash; else $t_hash = $state_hash;
                                        
                                        if (!isset($totals[$gender_id][$location_type][$set_suffix]["recovered"][$t_hash]))
                                            $totals[$gender_id][$location_type][$set_suffix]["recovered"][$t_hash] = 0;
                                            
                                        $totals[$gender_id][$location_type][$set_suffix]["recovered"][$t_hash]++;
                                        
                                        $dataset->$key_cases_total = $totals[$gender_id][$location_type][$set_suffix]["recovered"][$t_hash];
                                        break;
                                    case "country":                                    
                                        $totals[$gender_id]["country"][$set_suffix]["recovered"]++;
                                        
                                        $dataset->$key_cases_total = $totals[$gender_id]["country"][$set_suffix]["recovered"];
                                        break;
                                }
                            }
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
                
                    $datasets[$dataset_hash] = $dataset;            
                    krsort($dataset_index[$index]);            
                }
            }
                        
            $testresults[$result_hash] = $testresult;            
        }
        
        // Free some space
        unset($totals);

        // Finalize dataset calculation
        $this->finalize_datasets($dataset_index, "district", $datasets);
        
        // We must create / update the dataset hierarchy and save our new results to objects local store
        foreach ($dataset_index as $index => $date)
        {
            foreach ($date as $hash)
            {
                $this->datasets[$hash] = $datasets[$hash];
            }
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
            $db_obj->data_checksum = true;
                    
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
            $db_obj->data_checksum = true;
            
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
            $db_obj->data_checksum = true;
                    
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
        
        if (!is_array($this->nowcasts))
            return null;
            
        $this->database_transaction_begin("save_nowcast");
        
        $errors = array();
        
        foreach ($this->nowcasts as $hash => $obj)
        {            
            $db_obj = $this->database->new_nowcast();
            
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
            $db_obj->data_checksum = true;
                    
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
                
                $db_obj->data_checksum = true;

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
