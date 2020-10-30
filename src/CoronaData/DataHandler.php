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

use WelterRocks\CoronaData\Execption;
use WelterRocks\CoronaData\HttpHandler;

class DataHandler
{
    private $http_handler = null;
    
    private $data = null;
    private $length = null;
    private $is_json = null;

    private $timestamp = null;
    private $datasource = null;
    
    public static function time_to_timestamp($time)
    {
        // This "weird" function is required, because of the "german gründlichkeit"
        // Who in this world sets multiple date formats within one single dataset???
        $retval = null;
        
        $time = str_replace(" Uhr", "", $time);
        $time = str_replace("Uhr", "", $time);
        $time = str_replace(", ", " ", $time);
            
        $known_formats = array(
            "/d{2}\.d{2}\.d{4} d{2}:d{2}:d{2}/"   => array("german_date_time", "."),
            "/d{4}\/d{2}\/d{2} d{2}:d{2}:d{2}/"   => array("english_date_time", "."),
            "/d{4}-d{2}-d{2} d{2}:d{2}:d{2}/" 	  => array("english_date_time", "."),
            "/d{2}:d{2}:d{2} d{2}\.d{2}\.d{4}/"   => array("german_time_date", "."),
            "/d{2}:d{2}:d{2} d{4}\/d{2}\/d{2}/"   => array("english_time_date", "."),
            "/d{2}:d{2}:d{2} d{4}-d{2}-d{2}/" 	  => array("english_time_date", "."),
            "/d{2}\.d{2}\.d{4} d{2}:d{2}/" 	  => array("german_date_time", "."),
            "/d{4}\/d{2}\/d{2} d{2}:d{2}/" 	  => array("english_date_time", "."),
            "/d{4}-d{2}-d{2} d{2}:d{2}/" 	  => array("english_date_time", "."),
            "/d{2}:d{2} d{2}\.d{2}\.d{4}/" 	  => array("german_time_date", "."),
            "/d{2}:d{2} d{4}\/d{2}\/d{2}/" 	  => array("english_time_date", "."),
            "/d{2}:d{2} d{4}-d{2}-d{2}/" 	  => array("english_time_date", "."),
            "/d{2}\.d{2}\.d{4}/" 		  => array("german_date", "."),
            "/d{4}\/d{2}\/d{2}/" 		  => array("english_date", "."),
            "/d{4}-d{2}-d{2}/" 			  => array("english_date", "."),
            "/d{2}:d{2}:d{2}/" 			  => array("time", "."),
            "/d{2}:d{2}/" 			  => array("time", ".")
        );
            
        foreach ($known_formats as $format => $mode)
        {
            $matches = array();
            
            @preg_match($known_formats, $time, $matches);
            
            if (!$matches)
                continue;
            
            if (($mode[0] != "time") && ($mode[0] != "english_date") && ($mode[0] != "german_date"))
            {
                if (count($matches) != 2)
                    continue;
            }
            else
            {
                if (count($matches) != 1)
                    continue;
            }
            
            switch ($mode[0])
            {
                case "german_date_time":
                    $d = explode($mode[1], $matches[0]);
                    $day = (double)$d[0];
                    $month = (double)$d[1];
                    $year = (double)$d[2];
                    
                    $t = explode(":", $matches[1]);
                    $hour = (double)$t[0];
                    $minute = (double)$t[1];
                    $second = (double)((isset($t[2])) ? $t[2] : 0);
                    
                    $retval = mktime($hour, $minute, $second, $month, $day, $year);
                    break;
                case "english_date_time":
                    $d = explode($mode[1], $matches[0]);
                    $day = (double)$d[2];
                    $month = (double)$d[1];
                    $year = (double)$d[0];
                    
                    $t = explode(":", $matches[1]);
                    $hour = (double)$t[0];
                    $minute = (double)$t[1];
                    $second = (double)((isset($t[2])) ? $t[2] : 0);
                    
                    $retval = mktime($hour, $minute, $second, $month, $day, $year);
                    break;
                case "german_date":
                    $d = explode($mode[1], $matches[0]);
                    $day = (double)$d[0];
                    $month = (double)$d[1];
                    $year = (double)$d[2];
                                        
                    $retval = mktime(0, 0, 0, $month, $day, $year);
                    break;
                case "english_date":
                    $d = explode($mode[1], $matches[0]);
                    $day = (double)$d[2];
                    $month = (double)$d[1];
                    $year = (double)$d[0];
                                        
                    $retval = mktime(0, 0, 0, $month, $day, $year);
                    break;
                case "time":
                    $ts = time();
                    $day = (double)date("d", $ts);
                    $month = (double)date("m", $ts);
                    $year = (double)date("Y", $ts);
                                        
                    $t = explode(":", $matches[1]);
                    $hour = (double)$t[0];
                    $minute = (double)$t[1];
                    $second = (double)((isset($t[2])) ? $t[2] : 0);
                    
                    $retval = mktime($hour, $minute, $second, $month, $day, $year);
                    break;
            }
        }
        
        if (!$retval)
            $retval = strtotime($time);
        
        return $retval;
    }
    
    public static function compress($data, $compression_level = 9)
    {
        if ($compression_level < 0)
            return $data;
            
        return gzencode($data, $compression_level);
    }
    
    public static function uncompress($data, $not_compressed = false)
    {
        if ($not_compressed)
            return $data;
            
        return gzdecode($data);
    }
    
    public function get_timestamp()
    {
        return $this->timestamp;
    }    
    
    public function get_datasource()
    {
        return $this->datasource;
    }
    
    public function get_length()
    {
        return $this->length;
    }
    
    public function get_data()
    {
        return $this->data;
    }
    
    public function transform_cov_infocast()
    {
        // Sometimes the content type is not application/json, so the HttpHandler could not detect the correct decoding mechanism.
        // We will do this manually, if this->data is not an object.
        
        if (is_object($this->data))
            return true;
            
        $data = json_decode($this->data);
        
        if (!$data)
            return false;
            
        $this->data = clone $data;
        
        unset($data);
        
        return true;
    }
    
    public function transform_eu_datacast()
    {       
        $transform_keys = array(
            "dateRep" => "date_rep",
            "countriesAndTerritories" => "country",
            "geoId" => "geo_id",
            "countryterritoryCode" => "country_code",
            "continentExp" => "continent",
            "Cumulative_number_for_14_days_of_COVID-19_cases_per_100000" => "incidence_14day_given"
        );

        foreach ($this->data->records as $id => $data)
        {
            foreach ($data as $key => $val)
            {
                if (isset($transform_keys[$key]))
                {
                    $new_key = $transform_keys[$key];
                    
                    if ($val === null)
                        $val = "";
                    
                    // Because EU datacast has no location field, we set it to current country and replace the _ to space
                    if ($new_key == "country")
                    {
                        $val = str_replace("_", " ", $val);
                        $this->data->records[$id]->location = $val;
                    }
                        
                    if ($new_key == "date_rep")
                    {
                        $val = substr($val, 6, 4)."-".substr($val, 3, 2)."-".substr($val, 0, 2);
                        
                        $ts = strtotime($val." 23:59:59");
                        
                        $this->data->records[$id]->timestamp_represent = date("Y-m-d H:i:s", $ts);
                        $this->data->records[$id]->day = (int)date("j", $ts);
                        $this->data->records[$id]->month = (int)date("n", $ts);
                        $this->data->records[$id]->year = (int)date("Y", $ts);
                        $this->data->records[$id]->day_of_week = (int)date("w", $ts);
                    }
                    
                    $this->data->records[$id]->$new_key = $val;
                    
                    unset($this->data->records[$id]->$key);
                }
                elseif (substr($key, 0, 7) == "popData")
                {
                    $population = $val;
                    $population_year = substr($key, 7);
                    
                    $this->data->records[$id]->population = $population;
                    $this->data->records[$id]->population_year = $population_year;
                    
                    unset($this->data->records[$id]->$key); 

                    unset($population);
                    unset($population_year);
                }
            }
        }
        
        return true;
    }
    
    public function transform_rki_positive()
    {
        $buffer = array();
        
        $header_transform = array(
            "ObjectId" => "foreign_identifier",
            "IdBundesland" => "state_id",
            "Bundesland" => "state",
            "Landkreis" => "district",
            "Altersgruppe" => "age_group",
            "Geschlecht" => "gender",
            "AnzahlFall" => "cases_count",
            "AnzahlTodesfall" => "deaths_count",
            "Meldedatum" => "timestamp_reported",
            "IdLandkreis" => "district_id",
            "Datenstand" => "timestamp_dataset",
            "NeuerFall" => "cases_new",
            "NeuerTodesfall" => "deaths_new",
            "Refdatum" => "timestamp_referenced",
            "NeuGenesen" => "recovered_new",
            "AnzahlGenesen" => "recovered_count",
            "IstErkrankungsbeginn" => "flag_is_disease_beginning",
            "Altersgruppe2" => "age_group2"
        );
        
        if (($this->data->type != "FeatureCollection") || ($this->data->name != "RKI_COVID19") || (isset($this->data->features) == false))
            throw new Exception("Possible cache poisoning attack detected");
        
        foreach ($this->data->features as $id => $record)
        {
            if ($record->type != "Feature")
                continue;
                
            if (!isset($record->properties))
                continue;
                
            $prop = $record->properties;
            $obj = new \stdClass;
            
            foreach ($prop as $key => $val)
            {
                if (isset($header_transform[$key]))
                    $key = $header_transform[$key];
                    
                switch ($key)
                {
                    case "gender":
                        $gender = strtolower($val);
                        
                        switch ($gender)
                        {
                            case "mann":
                            case "male":
                            case "men":
                            case "m":
                                $val = "male";
                                break;
                            case "frau":
                            case "female":
                            case "woman":
                            case "f":
                            case "w":
                                $val = "female";
                                break;
                            default:
                                $val = "asterisk";
                                break;
                        }
                        break;
                    case "timestamp_reported":
                    case "timestamp_dataset":
                    case "timestamp_referenced":
                        $timets = self::time_to_timestamp($val);
                        
                        if ((!$timets) && ($val))
                            break;
                            
                        $val = date("Y-m-d H:i:s", $timets);
                        
                        if ($key == "timestamp_reported")
                        {
                            $obj->year = (int)date("Y", $timets);
                            $obj->month = (int)date("n", $timets);
                            $obj->day = (int)date("j", $timets);
                            $obj->day_of_week = (int)date("w", $timets);
                            $obj->date_rep = date("Y-m-d", $timets);
                            $obj->timestamp_represent = date("Y-m-d H:i:s", $timets);
                        }
                        
                        unset($timets);
                        break;
                    case "age_group":
                    case "age_group2":
                        $obj->$key = new \stdClass;
                        
                        if (trim($val) == "")
                        {
                            $obj->$key->lower = -1;
                            $obj->$key->upper = -1;
                            
                            continue(2);
                        }
                        
                        $ages = explode("-", $val);
                        
                        if (count($ages) > 0)
                        {
                            if (substr($ages[0], 0, 1) == "A")
                            {
                                $age1 = substr($ages[0], 1);
                                
                                if (isset($ages[1]))
                                    $age2 = substr($ages[1], 1);
                                else
                                    $age2 = $age1;
                                    
                                if (($age1 == $age2) && (substr($age1, -1) == "+"))
                                {
                                    $age1 = substr($age1, 0, -1);
                                    $age2 = 999;
                                }
                                    
                                $obj->$key->lower = $age1;
                                $obj->$key->upper = $age2;
                            }
                            else
                            {
                                $obj->$key->lower = -1;
                                $obj->$key->upper = -1;
                            }
                        }
                        else
                        {
                            $obj->$key->lower = -1;
                            $obj->$key->upper = -1;
                        }
                        
                        continue(2);
                }
                    
                $obj->$key = $val;
            }
            
            $buffer[$id] = clone $obj;
            
            unset($obj);
        }
        
        $json = json_encode($buffer);
        
        unset($buffer);
        
        $this->length = strlen($json);
        $this->data = json_decode($json);
        
        unset($json);
        
        return true;
    }
    
    public function transform_rki_nowcast()
    {
        $header = array();
        $buffer = array();

        $header_transform = array(
            "Datum" => "date_rep",
            "Schätzer_Neuerkrankungen" => "esteem_new_diseases",
            "UG_PI_Neuerkrankungen" => "lower_new_diseases",
            "OG_PI_Neuerkrankungen" => "upper_new_diseases",
            "Schätzer_Neuerkrankungen_ma4" => "esteem_new_diseases_ma4",
            "UG_PI_Neuerkrankungen_ma4" => "lower_new_diseases_ma4",
            "OG_PI_Neuerkrankungen_ma4" => "upper_new_diseases_ma4",
            "Schätzer_Reproduktionszahl_R" => "esteem_reproduction_r",
            "UG_PI_Reproduktionszahl_R" => "lower_reproduction_r",
            "OG_PI_Reproduktionszahl_R" => "upper_reproduction_r",
            "Schätzer_7_Tage_R_Wert" => "esteem_7day_r_value",
            "UG_PI_7_Tage_R_Wert" => "lower_7day_r_value",
            "OG_PI_7_Tage_R_Wert" => "upper_7day_r_value"
        );
        
        $row = $this->data[0];
        
        foreach ($row as $id => $key)
        {
            if (isset($header_transform[$key]))
                $key = $header_transform[$key];
                
            $header[$id] = $key;
        }
        
        if ((!isset($header[0])) || ($header[0] != "date_rep"))
            return false;

        for ($i = 1; $i < count($this->data); $i++)
        {
            $row = $this->data[$i];
            
            if (($row[0] == "") && ($row[1] == "") && ($row[2] == ""))
                break;
                
            $obj = new \stdClass;

            foreach ($row as $id => $val)
            {
		$key = $header[$id];
		
		// Why the fck hll these RKI guys send a 0 and not a NULL in their crappy table
		// This causes grafana to "crash the curves", so we do, what they should do!
                if ($val === 0)
                    $val = null;

		if ($key == "date_rep")
		{
		    $obj->$key = substr($val, 6, 4)."-".substr($val, 3, 2)."-".substr($val, 0, 2);	    
		    
		    $ts = strtotime($obj->$key." 23:59:59");
		    
		    $obj->timestamp_represent = date("Y-m-d H:i:s", $ts);
		    $obj->day_of_week = (int)date("w", $ts);
		    $obj->day = (int)date("j", $ts);
		    $obj->month = (int)date("n", $ts);
		    $obj->year = (int)date("Y", $ts);
                }
		elseif (strstr($val, ","))
		{
		    $obj->$key = (float)str_replace(",", ".", str_replace(".", "", $val));
                }
		else
		{
		    $obj->$key = (double)str_replace(",", ".", str_replace(".", "", $val));
                }
            }
	
            array_push($buffer, $obj); 
        }
        
        $json = json_encode($buffer);
        
        unset($buffer);
        unset($header);
        unset($header_transform);
        
        $this->data = json_decode($json);
        $this->length = strlen($json);
        
        unset($json);
        
        return true;
    }
    
    public function retrieve($target_file = null, $cache_time = 14400, $compression_level = 9, $not_json_encoded = false)
    {
        if ($target_file)
        {
            if (file_exists($target_file))
            {
                $this->timestamp = filemtime($target_file);
                
                if (($this->timestamp + $cache_time) < time())
                    @unlink($target_file);
            }
            
            $this->is_json = (($not_json_encoded) ? false : true);
            
            if (file_exists($target_file))
            {
                $data = $this->uncompress(file_get_contents($target_file), (($compression_level == -1) ? true : false));
                
                $length = strlen($data);
                
                if ($length > 0)
                {   
                    if ($not_json_encoded)
                        $this->data = $data;
                    else 	        
                        $this->data = json_decode($data);
                        
                    $this->length = $length;
                    $this->datasource = "cache";
                
                    return strlen($data);;
                }
                else
                {
                    @unlink($target_file);
                }
            }
        }
    
        $length = $this->http_handler->retrieve();
        
        if ($length == 0)
            throw new Exception("No data received");
        
        if ($target_file)
        {    
            if ($compression_level < 0)
                $fd = @fopen($target_file, "w");
            else
                $fd = @gzopen($target_file, "w".$compression_level);
            
            if (!is_resource($fd))
                throw new Exception("Unable to open target file '".$target_file."'");
        }
        
        $this->data = $this->http_handler->get_result();
        
        $this->timestamp = time();
        $this->length = $length;
        $this->datasource = "url";
        
        if ($target_file)
        {   
            if ($compression_level < 0)
            {
                if ($not_json_encoded)
                    @fwrite($fd, $this->data);
                else
                    @fwrite($fd, json_encode($this->data));
                    
                @fclose($fd);
            }
            else
            {
                if ($not_json_encoded)
                    @gzwrite($fd, $this->data);
                else
                    @gzwrite($fd, json_encode($this->data));
                    
                @gzclose($fd);
            }
        }
        
        return $length;
    }
    
    function __construct($url, $ssl_verifyhost = 2, $ssl_verifypeer = 1, $user_agent = null)
    {
        try
        {
            $this->http_handler = new HttpHandler($url, $ssl_verifyhost, $ssl_verifypeer, $user_agent);
        }
        catch (Exception $ex)
        {
            throw new Exception("HTTP handler failed to construct.", 0, $ex);
        }
    }
}
