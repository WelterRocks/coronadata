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
use WelterRocks\CoronaData\Genesis;

class DataHandler
{
    private $config = null;
    
    private $genesis = null;
    private $http_handler = null;
    
    private $data = null;
    private $length = null;
    private $is_json = null;

    private $timestamp = null;
    private $datasource = null;
    private $use_genesis = null;
    
    private $genesis_code = null;
    private $genesis_subcode = null;
    private $genesis_area = null;
    
    private $http_url = null;
    private $http_ssl_verifyhost = null;
    private $http_ssl_verifypeer = null;
    private $http_user_agent = null;
    
    public static function german_geo_id_table()
    {
        // Due to missing IDs in genesis state response, we must hard code this
        
        $transform = array(
            "1" => "Schleswig-Holstein",
            "2" => "Hamburg",
            "3" => "Niedersachsen",
            "4" => "Bremen",
            "5" => "Nordrhein-Westfalen",
            "6" => "Hessen",
            "7" => "Rheinland-Pfalz",
            "8" => "Baden-Württemberg",
            "9" => "Bayern",
            "10" => "Saarland",
            "11" => "Berlin",
            "12" => "Brandenburg",
            "13" => "Mecklenburg-Vorpommern",
            "14" => "Sachsen",
            "15" => "Sachsen-Anhalt",
            "16" => "Thüringen"
        );
        
        return $transform;
    }
    
    public static function german_state_by_district_geo_id($district_geo_id, &$found_id = null)
    {
        $found_id = (double)substr($district_geo_id, 0, 2);
        
        foreach (self::german_geo_id_table() as $id => $name)
        {
            if ($id == $found_id)
                return $name;
        }
        
        $found_id = null;
        
        return null;
    }
    
    public static function german_state_id_by_name($state_name, &$real_name = null, &$exact_id = null, &$near_id = null)
    {
        // We do this in two phases: exact match and near match
        $exact_id = null;
        $near_id = null;
        
        $real_name = null;
        
        foreach (self::german_geo_id_table() as $id => $name)
        {
            if (strtolower($name) == strtolower($state_name))
            {
                $exact_id = $id;
                $real_name = $name;
            }
            
            if (soundex($name) == soundex($state_name))
            {
                $near_id = $id;
                $real_name = $name;
            }
        }
        
        return (($exact_id) ? $exact_id : $near_id);
    }
    
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
    
    public function free()
    {
        $this->timestamp = -1;
        $this->length = -1;
        $this->is_json = null;
        $this->data = null;
    }
    
    public function genesis_table_cache_filename($code, $subcode, $area)
    {
        return realpath($this->config->data_store)."/genesis_".sha1($code."-".$subcode."/".$area).".jgz";
    }

    public function genesis_logincheck()
    {
        return $this->genesis->logincheck();
    }

    public function genesis_whoami()
    {
        return $this->genesis->whoami();
    }

    public function genesis_get_table_data($code, $subcode, $area = "all", &$json = null, &$csv = null)
    {
        $json = null;
        $csv = null;
        
        $ident = array();

        switch($code)
        {
            case "territory":
                array_push($ident, "11111");
                switch ($subcode)
                {
                    case "area":
                        array_push($ident, "0001");
                        break;
                    case "district_area":
                        array_push($ident, "0002");
                        break;
                    default:
                        return null;
                }
                break;
            case "population":
                array_push($ident, "12411");
                switch ($subcode)
                {
                    case "total":
                        array_push($ident, "0003");
                        break;
                    case "by_state":
                        array_push($ident, "0011");
                        break;
                    case "by_district":
                        array_push($ident, "0016");
                        break;
                    default:
                        return null;
                }
                break;
            default:
                array_push($ident, $code);
                array_push($ident, $subcode);
                break;
        }

        if (!$this->genesis_logincheck())
            return null;
            
        $res = $this->genesis->data_table(implode("-", $ident), $area);

        if (!$res)
            return null;

        if (!is_object($res))
            return null;

        if ((!isset($res->Object)) || (!isset($res->Object->Content)))
            return false;

        $csv = $res->Object->Content;
        $json = $this->genesis->parse_csv($csv);

        return strlen(json_encode($json));
    }
    
    public function http_handler_cache_filename($url, $ssl_verifyhost, $ssl_verifypeer, $user_agent)
    {
        return realpath($this->config->data_store)."/http_handler_".sha1($url."#".$ssl_verifyhost."#".$ssl_verifypeer."#".$user_agent).".jgz";
    }
    
    public function http_handler_get_result(&$data = null)
    {
        $length = $this->http_handler->retrieve();
        $data = $this->http_handler->get_result();
        
        return $length;
    }
    
    public function transform_gen_population()
    {
        if ((is_object($this->data)) && (isset($this->data->date)))
            return true;
        
        if (!is_array($this->data))
            return false;
            
        if (!isset($this->data[7][3]))
            return false;
       
        $data = new \stdClass;
        $data->date = new \DateTime($this->data[7][0]);
        $data->males = (int)$this->data[7][1];
        $data->females = (int)$this->data[7][2];
        $data->population = (int)$this->data[7][3];
        
        $this->data = clone $data;
        
        unset($data);
        
        return true;
    }
    
    public function transform_gen_population_by_state()
    {
        if ((is_object($this->data)) && (isset($this->data->date)))
            return true;
        
        if (!is_array($this->data))
            return false;
            
        if (!isset($this->data[5][4]))
            return false;
       
        $result = new \stdClass; 
        $result->states = array();

        foreach ($this->data as $index => $data)
        {
            if ($index < 6)
                continue;
            
            if (count($data) != 5)
                break;
            
            $state = new \stdClass;
            $state->date = new \DateTime($data[0]);
            $state->state = $data[1];
            $state->males = $data[2];
            $state->females = $data[3];
            $state->totals = $data[4];
            
            $result->states[$state->state] = clone $state;
        }
        
        $this->data = clone $result;

        return true;
    }
    
    public function transform_gen_population_by_district()
    {
        if ((is_object($this->data)) && (isset($this->data->date)))
            return true;
        
        if (!is_array($this->data))
            return false;
            
        if (!isset($this->data[5][2]))
            return false;
       
        $result = new \stdClass; 
        $result->districts = array();

        foreach ($this->data as $index => $data)
        {
            if ($index < 6)
                continue;
            
            if (count($data) != 6)
                break;                
                
            $district = new \stdClass;                            
            $district->date = new \DateTime($data[0]);
            $district->id = $data[1];
            $district->fullname = $data[2];
            
            $expl = explode("(", $data[2]);
            
            $name = explode(",", $data[2], 2);
            $district->name = trim($name[0]);
            
            if (count($name) > 1)
                $district->type = trim($name[1]);
            else
                $district->type = "Kreis";
            
            $district->males = (float)str_replace(",", ".", $data[3]);
            $district->females = (float)str_replace(",", ".", $data[4]);
            $district->totals = (float)str_replace(",", ".", $data[5]);
            
            $result->districts[$district->id] = clone $district;
            
            unset($district);
        }

        $this->data = clone $result;
        
        return true;        
    }
    
    public function transform_gen_territory_area()
    {
        if ((is_object($this->data)) && (isset($this->data->date)))
            return true;
        
        if (!is_array($this->data))
            return false;
            
        if (!isset($this->data[5][1]))
            return false;
       
        $result = new \stdClass; 
        $result->date = new \DateTime($this->data[5][1]);        
        $result->states_area = array();
        $result->area = 0;

        foreach ($this->data as $index => $data)
        {
            if ($index < 6)
                continue;
            
            if (count($data) != 2)
                break;
                
            $state = $data[0];
            $area = (float)str_replace(",", ".", $data[1]);
            
            if ($state == "Insgesamt")
            {
                $result->area = $area;
                continue;
            }
                
            $result->states_area[$state] = $area;
        }
        
        $this->data = clone $result;
        
        return true;
    }
    
    public function transform_gen_territory_district_area()
    {
        if ((is_object($this->data)) && (isset($this->data->date)))
            return true;
        
        if (!is_array($this->data))
            return false;
            
        if (!isset($this->data[5][2]))
            return false;
       
        $result = new \stdClass; 
        $result->date = new \DateTime($this->data[5][2]);
        $result->districts_area = array();

        foreach ($this->data as $index => $data)
        {
            if ($index < 6)
                continue;
            
            if (count($data) != 3)
                break;                
                
            $district = new \stdClass;                            
            $district->id = $data[0];
            $district->fullname = $data[1];
            
            $expl = explode("(", $data[1]);
            
            $name = explode(",", $data[1], 2);
            $district->name = trim($name[0]);
            
            if (count($name) > 1)
                $district->type = trim($name[1]);
            else
                $district->type = "Kreis";
            
            $district->area = (float)str_replace(",", ".", $data[2]);
            
            $result->districts_area[$district->id] = clone $district;
            
            unset($district);
        }
        
        $this->data = clone $result;
        
        return true;        
    }
    
    public function transform_cov_infocast()
    {
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
                    case "district":
                        $dst = explode(" ", $val, 2);
                        
                        if (count($dst) == 1)
                        {
                            $obj->district_name = $val;
                            $obj->district_type = "Kreis";
                            break;
                        }
                            
                        $type = $dst[0];
                        $name = $dst[1];
                        
                        switch (strtolower($type))
                        {
                            case "sk":
                                $type = "Kreis";
                                break;
                            case "lk":
                                $type = "Landkreis";
                                break;
                            case "stadtregion":
                                $type = "Kreisfreie Stadt";
                                break;
                            default:
                                break;
                        }
                        
                        $obj->district_type = $type;
                        $obj->district_name = $name;
                        $obj->district_fullname = $val;
                        continue(2);
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
                            $obj->timestamp_represent = date("Y-m-d", $timets)." 23:59:59";
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
    
    public function transform_divi_intens()
    {
        $header = array();
        $buffer = array();

        $header_transform = array(
            "bundesland" => "state_id",
            "gemeindeschluessel" => "district_id",
            "anzahl_meldebereiche" => "reporting_areas",
            "faelle_covid_aktuell" => "cases_covid",
            "faelle_covid_aktuell_beatmet" => "cases_covid_ventilated",
            "anzahl_standorte" => "locations_count",
            "betten_frei" => "beds_free",
            "betten_belegt" => "beds_occupied",
            "daten_stand" => "timestamp_represent"
        );
        
        $row = $this->data[0];
        
        foreach ($row as $id => $key)
        {
            if (isset($header_transform[$key]))
                $key = $header_transform[$key];
                
            $header[$id] = $key;
        }
        
        if ((!isset($header[0])) || ($header[0] != "timestamp_represent"))
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
		
                if ($val === 0)
                    $val = null;

		if ($key == "timestamp_represent")
		{
		    $ts = strtotime($val);

		    $obj->$key = date("Y-m-d H:i:s", $ts);
		    $obj->date_rep = date("Y-m-d", $ts);
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
    
    public function get_cache_filename()
    {
        return 
        (
            ($this->use_genesis) 
                ? 
                $this->genesis_table_cache_filename($this->genesis_code, $this->genesis_subcode, $this->genesis_area) 
                : 
                $this->http_handler_cache_filename($this->http_url, $this->http_ssl_verifypeer, $this->http_ssl_verifyhost, $this->http_user_agent)
        );
    }
    
    public function retrieve($target_file = null, $cache_time = 14400, $compression_level = 9, $not_json_encoded = false)
    {
        // Use autogenerated cache id
        if ($target_file === true)
            $target_file = $this->get_cache_filename();         
        
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
        
        $data = null;
        $this->data = $data;
    
        if ($this->use_genesis)
            $length = $this->genesis_get_table_data($this->genesis_code, $this->genesis_subcode, $this->genesis_area, $data);
        else
            $length = $this->http_handler->retrieve("get", null, null, null, $data);
        
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
        
        $this->data = $data;
        $this->timestamp = time();
        $this->length = $length;
        $this->datasource = (($this->use_genesis) ? "genesis" : "url");
        
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
    
    public function set_retrieve($use_genesis = false)
    {
        $this->use_genesis = $use_genesis;
    }
    
    public function init_genesis($code, $subcode, $area = "all")
    {
        try
        {
            $this->genesis_code = $code;
            $this->genesis_subcode = $subcode;
            $this->genesis_area = $area;
            
            $this->genesis = new Genesis($this->config);
            
            $this->set_retrieve(true);
        }
        catch (Exception $ex)
        {
            throw new Exception("Genesis failed to construct.", 0, $ex);
        }
    }
    
    public function init_http_handler($url, $ssl_verifyhost = 2, $ssl_verifypeer = 1, $user_agent = null)
    {
        try
        {
            $this->http_url = $url;
            $this->http_ssl_verifyhost = $ssl_verifypeer;
            $this->http_ssl_verifypeer = $ssl_verifyhost;
            $this->http_user_agent = $user_agent;
            
            $this->http_handler = new HttpHandler($url, $ssl_verifyhost, $ssl_verifypeer, $user_agent);
            
            $this->set_retrieve(false);
        }
        catch (Exception $ex)
        {
            throw new Exception("HTTP handler failed to construct.", 0, $ex);
        }
    }
    
    function __construct(Config $config, $url = null, $code = null, $subcode = null, $area = "all", $ssl_verifyhost = 2, $ssl_verifypeer = 1, $user_agent = null)
    {
        $this->config = $config; 

        if ($url)
        {
            $this->init_http_handler($url, $ssl_verifyhost, $ssl_verifypeer, $user_agent);
        }
        elseif (($code) && ($subcode))
        {
            $this->init_genesis($code, $subcode, $area);
        }
    }
}
