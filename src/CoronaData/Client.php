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

use \WelterRocks\CoronaData\Config;
use \WelterRocks\CoronaData\DataHandler;
use \WelterRocks\CoronaData\Database;
use \WelterRocks\CoronaData\Exception;
    
class Client
{
    private $config = null;
    private $config_file = null;
    
    private $eu_datacast = null;
    private $eu_datacast_size = null;
    private $eu_datacast_timestamp = null;
    private $eu_datacast_filename = null;
    
    private $rki_positive = null;
    private $rki_positive_size = null;
    private $rki_positive_timestamp = null;
    private $rki_positive_filename = null;
    
    private $rki_nowcast = null;
    private $rki_nowcast_size = null;
    private $rki_nowcast_timestamp = null;
    private $rki_nowcast_filename = null;
    
    private $rki_rssfeed = null;
    private $rki_rssfeed_size = null;
    private $rki_rssfeed_timestamp = null;
    private $rki_rssfeed_filename = null;
    
    private $cov_infocast = null;
    private $cov_infocast_size = null;
    private $cov_infocast_timestamp = null;
    private $cov_infocast_filename = null;
    
    private $database = null;
    
    private $transaction_name = null;
    
    public static function threeletter_encode($str)
    {
        $allowed = "abcdefghijklmnopqrstuvwxyz";
        $retval = "";
        
        $str = trim($str);
        
        for ($i = 0; $i < strlen($str); $i++)
        {
            $c = substr($str, 0, 1);
            
            if (!stripos($allowed, $str))
                continue;
                
            $retval = strtoupper($c);
            
            if (strlen($retval) == 3)
                break;
        }
        
        $retval = str_pad($retval, 3, "0");
        
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
    
    public function retrieve_eu_datacast($cache_timeout = 14400)
    {
       if ($retval = $this->eu_datacast->retrieve($this->eu_datacast_filename, $cache_timeout))
       {
           if (!$this->eu_datacast->transform_eu_datacast())
               return null;
               
           $this->eu_datacast_timestamp = self::timestamp();
           $this->eu_datacast_size = $retval;
           
           return $retval;
       }
    }
    
    public function retrieve_rki_positive($cache_timeout = 14400)
    {
        if ($retval = $this->rki_positive->retrieve($this->rki_positive_filename, $cache_timeout))
        {
            if (!$this->rki_positive->transform_rki_positive())
                return null;
            
            $this->rki_positive_timestamp = self::timestamp();
            $this->rki_positive_size = $retval;
            
            return $retval;
        }
        
        return null;
    }
    
    public function retrieve_rki_nowcast($cache_timeout = 14400)
    {
        if ($retval = $this->rki_nowcast->retrieve($this->rki_nowcast_filename, $cache_timeout))
        {
            if (!$this->rki_nowcast->transform_rki_nowcast())
                return null;
            
            $this->rki_nowcast_timestamp = self::timestamp();
            $this->rki_nowcast_size = $retval;
            
            return $retval;
        }
        
        return null;
    }
    
    public function retrieve_rki_rssfeed($cache_timeout = 14400)
    {
        if ($retval = $this->rki_rssfeed->retrieve($this->rki_rssfeed_filename, $cache_timeout, -1, true))
        {
            $this->rki_rssfeed_timestamp = self::timestamp();
            $this->rki_rssfeed_size = $retval;
            
            return $retval;
        }
        
        return null;
    }
    
    public function retrieve_cov_infocast($cache_timeout = 14400)
    {
        if ($retval = $this->cov_infocast->retrieve($this->cov_infocast_filename, $cache_timeout))
        {
            if (!$this->cov_infocast->transform_cov_infocast())
                return null;
            
            $this->cov_infocast_timestamp = self::timestamp();
            $this->cov_infocast_size = $retval;
            
            return $retval;
        }
        
        return null;
    }
    
    public function export_eu_datacast(&$length = null, &$timestamp = null)
    {
        $length = $this->eu_datacast->get_length();
        $timestamp = $this->eu_datacast->get_timestamp();
        
        return $this->eu_datacast->get_data();
    }
    
    public function export_rki_nowcast(&$length = null, &$timestamp = null)
    {
        $length = $this->rki_nowcast->get_length();
        $timestamp = $this->rki_nowcast->get_timestamp();
        
        return $this->rki_nowcast->get_data();
    }
    
    public function export_rki_positive(&$length = null, &$timestamp = null)
    {
        $length = $this->rki_positive->get_length();
        $timestamp = $this->rki_positive->get_timestamp();
        
        return $this->rki_positive->get_data();
    }
    
    public function export_rki_rssfeed(&$length = null, &$timestamp = null)
    {
        $length = $this->rki_rssfeed->get_length();
        $timestamp = $this->rki_rssfeed->get_timestamp();
        
        return $this->rki_rssfeed->get_data();
    }
    
    public function export_cov_infocast(&$length = null, &$timestamp = null)
    {
        $length = $this->cov_infocast->get_length();
        $timestamp = $this->cov_infocast->get_timestamp();
        
        return $this->cov_infocast->get_data();
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
    
    public function get_eu_datacast_timestamp()
    {
        return $this->eu_datacast_timestamp;
    }
    
    public function get_eu_datacast_size()
    {
        return $this->eu_datacast_size;
    }
    
    public function get_eu_datacast_filename()
    {
        return $this->eu_datacast_filename;
    }
    
    public function get_rki_positive_timestamp()
    {
       return $this->rki_positive_timestamp; 
    }
    
    public function get_rki_positive_size()
    {
        return $this->rki_positive_size;
    }
    
    public function get_rki_positive_filename()
    {
        return $this->rki_positive_filename;
    }
    
    public function get_rki_nowcast_timestamp()
    {
       return $this->rki_nowcast_timestamp; 
    }
    
    public function get_rki_rssfeed_timestamp()
    {
       return $this->rki_rssfeed_timestamp; 
    }
    
    public function get_rki_rssfeed_filename()
    {
        return $this->rki_rssfeed_filename;
    }
    
    public function get_rki_rssfeed_size()
    {
        return $this->rki_rssfeed_size;
    }
    
    public function get_rki_nowcast_size()
    {
        return $this->rki_nowcast_size;
    }
    
    public function get_rki_nowcast_filename()
    {
        return $this->rki_nowcast_filename;
    }
    
    public function get_cov_infocast_timestamp()
    {
       return $this->cov_infocast_timestamp; 
    }
    
    public function get_cov_infocast_size()
    {
        return $this->cov_infocast_size;
    }
    
    public function get_cov_infocast_filename()
    {
        return $this->cov_infocast_filename;
    }
    
    public function get_table_status()
    {
        return $this->database->get_table_status();
    }
    
    public function is_ready_for_calculation(&$results = null)
    {
        $results = new \stdClass;
        $results->datacasts = false;
        $results->infocasts = false;
        $results->nowcasts = false;
        
        $this->database->analyze();
        
        $status = $this->get_table_status();
        
        $min_rows = 0;
        
        foreach ($status as $name => $table)
        {
            if ($name == "locations")
            {
                $min_rows = ($table->Rows * 15);
                break;
            }
        }
        
        if (!$min_rows)
            return false;
            
        foreach ($status as $name => $table)
        {
            switch ($name)
            {
                case "datacasts":
                case "infocasts":
                case "nowcasts":
                    if ($table->Rows >= $min_rows)
                        $results->$name = true;
                    break;
            }
        }
        
        return $results->datacasts;
    }
    
    public function recalculate_location_store_fields($transaction_name = null, $autocommit = false, &$result_count = null, &$update_results = null, &$error = null, &$sql = null)
    {
        $error = null;
        $sql = null;
        
        $result_count = -1;
        $result_count1 = -1;
        $result_count2 = -1;
        
        $update_results = null;
        
        if ($transaction_name)
        {
            if ($this->transaction_name)
                throw new Exception("Transaction already open with ID '".$this->transaction_name."'");
                
            $this->transaction_name = $transaction_name;
            $this->database->begin_transaction($transaction_name);
        }
        
        // We calculate the contamination in phase 1
        $callbacks = new \stdClass;
        $callbacks->calculate_contamination = array();
        $callbacks->save = array(null, null, false);

        $none = null;
        
        $update_results1 = $this->database->select("locations", "*", '1', $callbacks, false, false, false, $none, $result_count1, $error, $sql);
        
        // In phase 2 we calculate the childs of continents to surly have the contamination in parent fields
        $callbacks = new \stdClass;
        $callbacks->calculate_child_values = array("continent");
        $callbacks->save = array(null, null, false);

        $none = null;
        
        $update_results2 = $this->database->select("locations", "*", "location_type = 'continent'", $callbacks, false, false, false, $none, $result_count2, $error, $sql);
        
        $update_results = array_merge($update_results1, $update_results2);
        $result_count = $result_count1 + $result_count2;
            
        if (($this->transaction_name) && ($autocommit))
            return $this->database_transaction_commit($transaction_name);

        return true;    
    }

    public function recalculate_eu_datacast_store_fields($transaction_name = null, $autocommit = false, $incidence_factor = 100000, $r_value_skip_days = 3, &$result_count = null, &$update_results = null, &$error = null, &$sql = null)
    {
        $error = null;
        $sql = null;
        
        $result_count = -1;
        $update_results = null;
        
        if ($transaction_name)
        {
            if ($this->transaction_name)
                throw new Exception("Transaction already open with ID '".$this->transaction_name."'");
                
            $this->transaction_name = $transaction_name;
            $this->database->begin_transaction($transaction_name);
        }
        
        $callbacks = new \stdClass;
        $callbacks->recalculate = array($incidence_factor, $r_value_skip_days);
        $callbacks->save = array(null, null, false);

        $none = null;
        
        $update_results = $this->database->select("datacasts", "*", '1', $callbacks, false, false, false, $none, $result_count, $error, $sql);
            
        if (($this->transaction_name) && ($autocommit))
            return $this->database_transaction_commit($transaction_name);

        return true;    
    }
    
    public function get_datetime_diff($date1, $date2 = "now")
    {
        $datetime1 = new \DateTime($date1);
        $datetime2 = new \DateTime($date2);
        
        $datediff = $datetime1->diff($datetime2);

        return $datediff;    
    }
    
    public function update_eu_datacast_store($transaction_name = null, $filter_continent = null, $filter_country = null, $filter_location = null, $autocommit = false, $throttle_usecs = 1, &$totalcount = null, &$successcount = null, &$errcount = null, &$errstore = null, &$filtercount = null, &$disable_datacast_autoexec = null)
    {
        $successcount = 0;
        $totalcount = 0;
        $filtercount = 0;
        $errcount = 0;
        $errstore = array();
        
        if ($this->eu_datacast_size == 0)
            throw new Exception("EU datacast store is empty");
        
        $disable_datacast_autoexec = false;
        
        if (!$this->is_ready_for_calculation())
            $disable_datacast_autoexec = true;
        
        $no_result = null;
        $db_empty = false;
        
        $latest_ts = $this->database->get_latest("datacasts", "'1'", $no_result);
        
        if ($no_result)
        {
            // For future use
            $db_empty = true;
        }
        else
        {        
            $earliest_ts = $this->database->get_earliest("datacasts", "flag_calculated = 0", $no_result);
        
            // We have uncalculated datasets, set earliest date_rep as reference for latest timestamp, to make sure, all relevant datasets are updated
            if (!$no_result)
                $latest_ts = $earliest_ts;
        }
        
        if ($transaction_name)
        {
            if ($this->transaction_name)
                throw new Exception("Transaction already open with ID '".$this->transaction_name."'");
                
            $this->transaction_name = $transaction_name;
            $this->database->begin_transaction($transaction_name);
        }
            
        foreach ($this->eu_datacast->get_data()->records as $id => $record)
        {
            $error = null;
            $sql = null;
            
            if ($throttle_usecs)
                usleep($throttle_usecs);
                
            $totalcount++;
            
            $recorddate = $record->date_rep;
            
            if (!$recorddate)
            {
                $errcount++;
                
                $errobj = new \stdClass;
                $errobj->error = "Missing record date";
                
                array_push($errstore, $errobj);
                continue;
            }
            
            $datediff = $this->get_datetime_diff($latest_ts, $recorddate." 00:00:00");

            if ($datediff->invert == 1)
            {
                $filtercount++;
                continue;
            }
                            
            if (($filter_continent) && ($filter_continent != $record->continent))
            {
                if (!$filter_country)
                {
                    $filtercount++;
                    continue;
                }
                elseif ($filter_country != $record->country)
                {
                    if (!$filter_location)
                    {
                        $filtercount++;
                        continue;
                    }
                    elseif ($filter_location != $record->country)
                    {
                        // Yes! filter_location = record->country is correct, because eu datacast has no location value set.
                        // Therefore location is automatically set to country, to tell the database, that the hole country is meant.
                        $filtercount++;
                        continue;
                    }
                }
            }
            
            // Check, if the continent exists in database
            $continent_count = 0;
            $continent = $this->database->select("*", "location = '".$this->database->esc($record->continent)."' AND country = location AND continent = location AND location_type = 'continent'", null, true, false, false, $continent_count, $error, $sql);

            if ((!$continent) || (is_object($continent) == false))
            {
                // Do not create an error object, just create the continent
                $continent = new \stdClass;
                $continent->continent = $record->continent;
                $continent->country = $record->continent;
                $continent->location = $record->continent;
                $continent->location_type = 'continent';
                $continent->geo_id = self::threeletter_encode($record->continent);
                $continent->country_code = $continent->geo_id;
                
                if (true === $continent->uid = $this->database->register_object("Locations", $continent, true, false, false, $error, $sql))
                {
                    $errcount++;
                    
                    $errobj->error = "Unable to fetch UID of location object";
                    $errobj->sql = $sql;
                    
                    array_push($errstore, $errobj);
                }	
                elseif (($continent->uid === false) || ($continent->uid === null))
                {
                    $errcount++;
                
                    $errobj->error = $error;
                    $errobj->sql = $sql;
                    
                    array_push($errstore, $errobj);                
                }         
            }            
            
            $location = new \stdClass;
            $location->parent_uid = $continent->uid;
            $location->continent = $record->continent;
            $location->country = $record->country;
            $location->location = $record->location;
            $location->country_code = $record->country_code;
            $location->geo_id = $record->geo_id;
            $location->population = $record->population;
            $location->population_year = $record->population_year;
            $location->location_type = 'country';
            
            $datacast = new \stdClass;

            $errobj = new \stdClass;
            $errobj->id = $id;
            $errobj->location = $location;
            $errobj->record = $record;
            
            if (true === $datacast->locations_uid = $this->database->register_object("Locations", $location, true, false, false, $error, $sql))
            {
                $errcount++;
                
                $errobj->error = "Unable to fetch UID of location object";
                $errobj->sql = $sql;
                
                array_push($errstore, $errobj);
            }
            elseif (($datacast->locations_uid === false) || ($datacast->locations_uid === null))
            {
                $errcount++;
                
                $errobj->error = $error;
                $errobj->sql = $sql;
                
                array_push($errstore, $errobj);                
            }
            else
            {                
                foreach ($record as $key => $val)
                {
                    if (($key == "uid") || ($key == "locations_uid"))
                        continue;
                        
                    switch ($key)
                    {
                        case "population":
                            $datacast->population_used = $val;
                            continue(2);
                        case "continent":
                        case "country":
                        case "location":
                        case "country_code":
                        case "geo_id":
                        case "population_year":
                            continue(2);
                        default:
                            break;
                    }
                        
                    $datacast->$key = $val;
                }
                
                if (true === $datacast->uid = $this->database->register_object("Datacasts", $datacast, true, $disable_datacast_autoexec, false, $error, $sql))
                {
                    $errcount++;
                    
                    $errobj->error = $error;
                    $errobj->sql = $sql;
                    $errobj->datacast = $datacast;
                    
                    array_push($errstore, $errobj);
                }
                elseif (($datacast->uid !== false) && ($datacast->uid !== null))
                {
                    $successcount++;
                }
                else
                {                
                    $errcount++;
                    
                    $errobj->error = $error;
                    $errobj->sql = $sql;
                    $errobj->datacast = $datacast;
                    
                    array_push($errstore, $errobj);
                }
            }
        }
        
        if (($this->transaction_name) && ($autocommit))
            return $this->database_transaction_commit($transaction_name);

        return true;
    }
    
    public function update_rki_positive_store($transaction_name = null, $continent = "Europe", $country = "Germany", $autocommit = false, $throttle_usecs = 1, &$totalcount = null, &$successcount = null, &$errcount = null, &$errstore = null, &$filtercount = null)
    {
        $successcount = 0;
        $totalcount = 0;
        $filtercount = 0;
        
        $errcount = 0;
        $errstore = array();
        
        if ($this->rki_positive_size == 0)
            throw new Exception("RKI positive store is empty");
            
        $latest_ts = $this->database->get_latest("positives");
        
        if ($transaction_name)
        {
            if ($this->transaction_name)
                throw new Exception("Transaction already open with ID '".$this->transaction_name."'");
                
            $this->transaction_name = $transaction_name;
            $this->database->begin_transaction($transaction_name);
        }
        
        // Shadow store for internal stats
        $shadow_store = new \stdClass;
        $shadow_store->states = array();
        $shadow_store->districts = array();
        
        // Phase 1: Get the main location object to set states parent UIDs
        $error = null;
        $sql = null;
        $resultcount = 0;
        
        $location = $this->database->select("locations", "*", "continent = '".$this->database->esc($continent)."' AND country = '".$this->database->esc($country)."' AND location = '".$this->database->esc($country)."'", null, true, false, false, $resultcount, $error, $sql);
        
        if (!$resultcount)
            throw new Exception("Unable to find parent location for positive data");
                                
        // Phase 2: Get states and districts and create all data objects
        foreach ($this->rki_positive->get_data() as $id => $record)
        {
            // Check, whether we have to hrottle the processing
            if ($throttle_usecs)
                usleep($throttle_usecs);
                
            $totalcount++;
                
            $recorddate = $record->timestamp_represent;
            
            if (!$recorddate)
            {
                $errcount++;
                
                $errobj = new \stdClass;
                $errobj->error = "Missing record date";
                
                array_push($errstore, $errobj);
                continue;
            }
            
            $datediff = $this->get_datetime_diff($latest_ts, $recorddate);

            if ($datediff->invert == 1)
            {
                $filtercount++;
                continue;
            }
                            
            if (!isset($shadow_store->states[$record->state_id]))
            {
                $state = new \stdClass;
                
                $state->uid = null;
                $state->location = $record->state;
                $state->country = $country;
                $state->continent = $continent;
                $state->geo_id = self::threeletter_encode($record->state).$record->state_id;
                $state->parent_uid = $location->uid;
                $state->location_type = 'state';
                
                if (true === $state->uid = $this->database->register_object("Locations", $state, true, false, false, $error, $sql))
                {
                    $errcount++;
                    
                    $errobj = new \stdClass;
                    
                    $errobj->error = "Unable to fetch UID of location object";
                    $errobj->sql = $sql;
                    
                    array_push($errstore, $errobj);
                }
                elseif (($state->uid === false) || ($state->uid === null))
                {
                    $errcount++;
                    
                    $errobj = new \stdClass;
                    
                    $errobj->error = $error;
                    $errobj->sql = $sql;
                    
                    array_push($errstore, $errobj);                
                }

                $shadow_store->states[$record->state_id] = clone $state;
                
                unset($state);
            }
                
            if (!isset($shadow_store->districts[$record->state_id]))
                $shadow_store->districts[$record->state_id] = array();
            
            if ((!isset($shadow_store->districts[$record->state_id][$record->district_id])) && ($shadow_store->states[$record->state_id]->uid))
            {
                $district = new \stdClass;
                
                $district->uid = null;
                $district->location = $record->district;
                $district->country = $country;
                $district->continent = $continent;
                $district->geo_id = self::threeletter_encode($record->state).$record->state_id.".".$record->district_id;
                $district->parent_uid = $shadow_store->states[$record->state_id]->uid;
                $district->location_type = 'district';
                
                if (true === $district->uid = $this->database->register_object("Locations", $district, true, false, false, $error, $sql))
                {
                    $errcount++;
                    
                    $errobj = new \stdClass;
                    
                    $errobj->error = "Unable to fetch UID of location object";
                    $errobj->sql = $sql;
                    
                    array_push($errstore, $errobj);
                }
                elseif (($district->uid === false) || ($district->uid === null))
                {
                    $errcount++;
                    
                    $errobj = new \stdClass;
                    
                    $errobj->error = $error;
                    $errobj->sql = $sql;
                    
                    array_push($errstore, $errobj);                
                }

                $shadow_store->districts[$record->state_id][$record->district_id] = clone $district;
                
                unset($district);
            }            
        
            if ($shadow_store->districts[$record->state_id][$record->district_id]->uid)
            {
                $obj = new \stdClass;
                
                $obj->continent_uid = $location->parent_uid;
                $obj->country_uid = $location->uid;
                $obj->state_uid = $shadow_store->states[$record->state_id]->uid;
                $obj->district_uid = $shadow_store->districts[$record->state_id][$record->district_id]->uid;
                $obj->foreign_identifier = $record->foreign_identifier;
                $obj->timestamp_dataset = $record->timestamp_dataset;
                $obj->timestamp_reported = $record->timestamp_reported;
                $obj->timestamp_referenced = $record->timestamp_referenced;
                $obj->date_rep = $record->date_rep;
                $obj->day_of_week = $record->day_of_week;
                $obj->day = $record->day;
                $obj->month = $record->month;
                $obj->year = $record->year;
                $obj->age_group_low = $record->age_group->lower;
                $obj->age_group_high = $record->age_group->upper;
                $obj->age_group2_low = $record->age_group2->lower;
                $obj->age_group2_high = $record->age_group2->upper;
                $obj->gender = $record->gender;
                $obj->cases = $record->cases_count;
                $obj->deaths = $record->deaths_count;
                $obj->recovered = $record->recovered_count;
                $obj->new_cases = $record->cases_new;
                $obj->new_deaths = $record->deaths_new;
                $obj->new_recovered = $record->recovered_new;
                $obj->flag_is_disease_beginning = $record->flag_is_disease_beginning;
                
                if (true === $obj->uid = $this->database->register_object("Positives", $obj, true, false, false, $error, $sql))
                {
                    $errcount++;
                    
                    $errobj = new \stdClass;
                    
                    $errobj->error = "Unable to fetch UID of positive object";
                    $errobj->sql = $sql;
                    
                    array_push($errstore, $errobj);
                }
                elseif (($obj->uid === false) || ($obj->uid === null))
                {
                    $errcount++;
                    
                    $errobj = new \stdClass;
                    
                    $errobj->error = $error;
                    $errobj->sql = $sql;
                    
                    array_push($errstore, $errobj);                
                }
                else
                {
                    $successcount++;
                }
            }
        }
        
        if (($this->transaction_name) && ($autocommit))
            return $this->database_transaction_commit($transaction_name);

        return true;            
    }
    
    public function update_rki_nowcast_store($transaction_name = null, $continent = "Europe", $country = "Germany", $location = "Germany", $autocommit = false, $throttle_usecs = 1, &$totalcount = null, &$successcount = null, &$errcount = null, &$errstore = null, &$filtercount = null)
    {
        $successcount = 0;
        $totalcount = 0;
        $filtercount = 0;
        
        $errcount = 0;
        $errstore = array();
        
        if ($this->rki_nowcast_size == 0)
            throw new Exception("RKI nowcast store is empty");

        // Seems that the RKI data is corrected after some days, so we will write all datasets, not only the latest        
        // $latest_ts = $this->database->get_latest("nowcasts");
        
        if ($transaction_name)
        {
            if ($this->transaction_name)
                throw new Exception("Transaction already open with ID '".$this->transaction_name."'");
                
            $this->transaction_name = $transaction_name;
            $this->database->begin_transaction($transaction_name);
        }

        // We load the location outside the loop, because we need this only once for the RKI stuff.            
        $loc = new \stdClass;
        
        $loc->continent = $continent;
        $loc->country = $country;
        $loc->location = $location;
        
        $error = null;
        $sql = null;
        
        $location = $this->database->get_object("locations", $loc, $error, $sql);
        
        if (!$location)
        {
            $errcount++;
            
            $errobj = new \stdClass;
            $errobj->error = $error;
            $errobj->sql = $sql;
            
            array_push($errstore, $errobj);
            
            return false;
        }

        foreach ($this->rki_nowcast->get_data() as $id => $record)
        {
            $error = null;
            $sql = null;
            
            $errobj = new \stdClass;
            $errobj->error = $error;
            $errobj->sql = $sql;
            $errobj->id = $id;
            $errobj->location = $location;
            $errobj->record = $record;
            
            $nowcast = new \stdClass;
            $nowcast->locations_uid = $location->uid;
            
            if ($throttle_usecs)
                usleep($throttle_usecs);
                
            foreach ($record as $key => $val)
            {
                if (($key == "uid") || ($key == "locations_uid"))
                    continue;
                     
                if (isset($location->$key))
                    continue;
                
                if ($val !== null)
                    $nowcast->$key = $val;
            }
            
            $totalcount++;
            
            $recorddate = $record->date_rep;
            
            if (!$recorddate)
            {
                $errcount++;
                
                $errobj = new \stdClass;
                $errobj->error = "Missing record date";
                
                array_push($errstore, $errobj);
                continue;
            }
/* DISABLED, BECAUSE OF THE RKI CORRECTED OLD DATASETS, THAT MUST BE UPDATED            
            $datediff = $this->get_datetime_diff($latest_ts, $recorddate." 00:00:00");
            
            if ($datediff->invert == 1)
            {
                $filtercount++;
                continue;
            }
*/                
            if (true === $nowcast->uid = $this->database->register_object("Nowcasts", $nowcast, true, false, true, $error, $sql))
            {
                $errcount++;
                
                $errobj->error = $error;
                $errobj->sql = $sql;
                $errobj->nowcast = $nowcast;
                
                array_push($errstore, $errobj);
            }
            elseif (($nowcast->uid !== false) && ($nowcast->uid !== null))
            {
                $successcount++;
            }
            else
            {
                $errcount++;
                
                $errobj->error = $error;
                $errobj->sql = $sql;
                $errobj->nowcast = $nowcast;
                
                array_push($errstore, $errobj);
            }
        }
        
        if (($this->transaction_name) && ($autocommit))
            return $this->database_transaction_commit($transaction_name);
            
        return true;
    }
    
    public function update_cov_infocast_store($transaction_name = null, $filter_continent = null, $filter_country = null, $filter_location = null, $autocommit = false, $throttle_usecs = 1, &$totalcount = null, &$successcount = null, &$errcount = null, &$errstore = null, &$filtercount = null)
    {
        $successcount = 0;
        $totalcount = 0;
        $filtercount = 0;
        $errcount = 0;
        $errstore = array();
        
        if ($this->cov_infocast_size == 0)
            throw new Exception("COV infocast store is empty");
            
        $latest_ts = $this->database->get_latest("infocasts");
        
        if ($transaction_name)
        {
            if ($this->transaction_name)
                throw new Exception("Transaction already open with ID '".$this->transaction_name."'");
                
            $this->transaction_name = $transaction_name;
            $this->database->begin_transaction($transaction_name);
        }
            
        foreach ($this->cov_infocast->get_data() as $country_code => $data)
        {
            $error = null;
            $sql = null;
            
            if ($throttle_usecs)
                usleep($throttle_usecs);
                
            if (($filter_continent) && ($filter_continent != $data->continent))
            {
                if (!$filter_country)
                {
                    $filtercount += count($data->data);
                    $totalcount += count($data->data);
                    continue;
                }
                elseif ($filter_country != $data->location)
                {
                    // Yes! filter_country = data->location is correct, because cov infocast has no location value set.
                    // Therefore country is automatically set to location, to tell the database, that the hole country is meant.
                    if (!$filter_location)
                    {
                        $filtercount += count($data->data);
                        $totalcount += count($data->data);
                        continue;
                    }
                    elseif ($filter_location != $data->location)
                    {
                        $filtercount += count($data->data);
                        $totalcount += count($data->data);
                        continue;
                    }
                }
            }

            $location = new \stdClass;
            
            // Check, if the continent exists in database, if object is set
            if (isset($data->continent))
            {
                $continent_count = 0;
                $continent = $this->database->select("*", "location = '".$this->database->esc($data->continent)."' AND country = location AND continent = location AND location_type = 'continent'", null, true, false, false, $continent_count, $error, $sql);
                
                if ((!$continent) || (is_object($continent) == false))
                {
                    // Do not create an error object, just create the continent
                    $continent = new \stdClass;
                    $continent->continent = $data->continent;
                    $continent->country = $data->continent;
                    $continent->location = $data->continent;
                    $continent->location_type = 'continent';
                    $continent->geo_id = self::threeletter_encode($data->continent);
                    $continent->country_code = $continent->geo_id;
                    
                    if (true === $continent->uid = $this->database->register_object("Locations", $continent, true, false, false, $error, $sql))
                    {
                        $errcount++;
                        
                        $errobj->error = "Unable to fetch UID of location object";
                        $errobj->sql = $sql;
                        
                        array_push($errstore, $errobj);
                    }	
                    elseif (($continent->uid === false) || ($continent->uid === null))
                    {
                        $errcount++;
                    
                        $errobj->error = $error;
                        $errobj->sql = $sql;
                        
                        array_push($errstore, $errobj);                
                    }         
                }            
            }
            
            // We only set location, country, country code, geo id (generated) and continent (if it exists) manually
            $data->location = str_replace("_", " ", $data->location);

            $location->location = $data->location;
            $location->country = $data->location;
            $location->country_code = $country_code;
            $location->geo_id = substr($country_code, 0, 2);
            $location->location_type = 'country';
            
            if (isset($data->continent))
            {
                $location->continent = $data->continent;
                
                if (isset($continent->uid))
                    $location->parent_uid = $continent->uid;
            }
                        
            foreach ($data as $key => $val)
            {
                switch ($key)
                {
                    case "continent":
                    case "location":
                    case "data":
                        continue(2);
                    default:
                        break;
                }
                
                if (($val !== null) && ($val != ""))
                    $location->$key = $val;
            }
            
            $infocast = new \stdClass;

            $errobj = new \stdClass;
            $errobj->country_code = $country_code;
            $errobj->location = $location;
//            $errobj->data = $data;
            
            if (true === $infocast->locations_uid = $this->database->register_object("Locations", $location, true, false, false, $error, $sql))
            {
                $errcount += count($data->data);
                
                $errobj->error = "Unable to fetch UID of location object";
                $errobj->sql = $sql;
                
                $totalcount += count($data->data);
                array_push($errstore, $errobj);
            }
            elseif (($infocast->locations_uid === false) || ($infocast->locations_uid === null))
            {
                $errcount += count($data->data);
                
                $errobj->error = $error;
                $errobj->sql = $sql;
                
                $totalcount += count($data->data);
                array_push($errstore, $errobj);                
            }
            else
            {                
                foreach ($data->data as $id => $obj)
                {
                    $errobj = new \stdClass;
                    $errobj->country_code = $country_code;
                    $errobj->location = $location;
//                    $errobj->data = $data;
                    $errobj->id = $id;
                    
                    $totalcount++;
            
                    $recorddate = $obj->date;
            
                    if (!$recorddate)
                    {
                        $errcount++;
                        
                        $errobj->error = "Missing record date";
                        
                        array_push($errstore, $errobj);
                        continue;
                    }
            
                    $datediff = $this->get_datetime_diff($latest_ts, $recorddate." 00:00:00");
            
                    if ($datediff->invert == 1)
                    {
                        $filtercount++;
                        continue;
                    }
                
                    foreach ($obj as $key => $val)
                    {
                        // Preventive protection against uid override attacks
                        if (($key == "uid") || ($key == "locations_uid"))
                            continue;
                            
                        // Because of global warming and efficiency reasons, we transform the date field at this point and not within a DataHandlers transform routine :-)
                        if ($key == "date")
                        {
                            $infocast->date_rep = $val;
                            
                            $ts = strtotime($val." 23:59:59");
                            
                            $infocast->timestamp_represent = date("Y-m-d H:i:s", $ts);
                            $infocast->day_of_week = (int)date("w", $ts);
                            $infocast->day = (int)date("j", $ts);
                            $infocast->month = (int)date("n", $ts);
                            $infocast->year = (int)date("Y", $ts);
                        }
                        elseif (($val !== null) && ($val != ""))
                        {
                            $infocast->$key = $val;
                        }
                    }

                    if (true === $infocast->uid = $this->database->register_object("Infocasts", $infocast, true, false, false, $error, $sql))
                    {
                        $errcount++;
                    
                        $errobj->error = $error;
                        $errobj->sql = $sql;
                        $errobj->infocast = $infocast;
                        
                        array_push($errstore, $errobj);
                    }
                    elseif (($infocast->uid !== false) && ($infocast->uid !== null))
                    {
                        $successcount++;
                    }
                    else
                    {
                        $errcount++;
                    
                        $errobj->error = $error;
                        $errobj->sql = $sql;
                        $errobj->infocast = $infocast;
                        
                        array_push($errstore, $errobj);                    
                    }
                }                    
            }
        }
        
        if (($this->transaction_name) && ($autocommit))
            return $this->database_transaction_commit($transaction_name);
            
        return true;
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
    
    function __construct($config = ".coronadatarc", $eu_datacast_filename = "eu_datacast.jgz", $rki_positive_filename = "rki_positive.jgz", $rki_nowcast_filename = "rki_nowcast.jgz", $rki_rssfeed_filename = "rki_rssfeed.xml", $cov_infocast_filename = "cov_infocast.jgz")
    {
        $this->config_file = $config;
        
        $this->config = new Config($this->config_file);
        
        $this->create_datastore();
        
        $this->eu_datacast_filename = $this->config->data_store."/".$eu_datacast_filename;
        $this->rki_positive_filename = $this->config->data_store."/".$rki_positive_filename;
        $this->rki_nowcast_filename = $this->config->data_store."/".$rki_nowcast_filename;
        $this->rki_rssfeed_filename = $this->config->data_store."/".$rki_rssfeed_filename;
        $this->cov_infocast_filename = $this->config->data_store."/".$cov_infocast_filename;
        
        $this->eu_datacast = new DataHandler($this->config->url_eu_datacast);
        $this->rki_positive = new DataHandler($this->config->url_rki_positive);
        $this->rki_nowcast = new DataHandler($this->config->url_rki_nowcast);
        $this->rki_rssfeed = new DataHandler($this->config->url_rki_rssfeed);
        $this->cov_infocast = new DataHandler($this->config->url_cov_infocast);
        
        $this->eu_datacast_timestamp = 0;
        $this->eu_datacast_size = 0;
        
        $this->rki_positive_timestamp = 0;
        $this->rki_positive_size = 0;
        
        $this->rki_nowcast_timestamp = 0;
        $this->rki_nowcast_size = 0;
        
        $this->rki_rssfeed_timestamp = 0;
        $this->rki_rssfeed_size = 0;
        
        $this->cov_infocast_timestamp = 0;
        $this->cov_infocast_size = 0;
        
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
