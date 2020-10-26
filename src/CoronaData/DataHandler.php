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

    private $timestamp = null;
    private $datasource = null;
    
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
                    
                    // Because EU datacast has no location field, we set it to current country.
                    if ($new_key == "country")
                        $this->data->records[$id]->location = $val;
                        
                    if ($new_key == "date_rep")
                    {
                        $val = substr($val, 6, 4)."-".substr($val, 3, 2)."-".substr($val, 0, 2);
                        
                        $this->data->records[$id]->timestamp_represent = $val." 00:00:00";
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

		if ($key == "date_rep")
		{
		    $obj->$key = substr($val, 6, 4)."-".substr($val, 3, 2)."-".substr($val, 0, 2);	    
		    $obj->timestamp_represent = $obj->$key." 00:00:00";
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
    
    public function retrieve($target_file = null, $cache_time = 14400)
    {
        if ($target_file)
        {
            if (file_exists($target_file))
            {
                $this->timestamp = filemtime($target_file);
                
                if (($this->timestamp + $cache_time) < time())
                    @unlink($target_file);
            }
            
            if (file_exists($target_file))
            {
                $data = gzdecode(file_get_contents($target_file));
                
                $length = strlen($data);
                
                if ($length > 0)
                {            
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
            $fd = @gzopen($target_file, "w9");
            
            if (!is_resource($fd))
                throw new Exception("Unable to open target file '".$target_file."'");
        }
        
        $this->data = $this->http_handler->get_result();
        
        $this->timestamp = time();
        $this->length = $length;
        $this->datasource = "url";
        
        if ($target_file)
        {    
            @gzwrite($fd, json_encode($this->data));
            @gzclose($fd);
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
