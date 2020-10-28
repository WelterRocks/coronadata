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

final class Config
{
    private $url_eu_datacast = 'https://opendata.ecdc.europa.eu/covid19/casedistribution/json/';
    private $url_rki_positive = 'https://opendata.arcgis.com/datasets/dd4580c810204019a7b8eb3e0b329dd6_0.geojson';
    private $url_rki_nowcast = 'https://www.rki.de/DE/Content/InfAZ/N/Neuartiges_Coronavirus/Projekte_RKI/Nowcasting_Zahlen_csv.csv?__blob=publicationFile';
    private $url_cov_infocast = 'https://github.com/owid/covid-19-data/raw/master/public/data/owid-covid-data.json';
    
    private $data_store = './data';
    
    private $mysql_hostname = "localhost";
    private $mysql_hostport = 3306;

    private $mysql_username = "root";
    private $mysql_password = null;

    private $mysql_database = "corona";
    private $mysql_socket = null;
    
    private $mqtt_hostname = "localhost";
    private $mqtt_hostport = 1883;
    
    private $mqtt_username = null;
    private $mqtt_password = null;
    
    private $mqtt_client_id = "CoronaData";
    private $mqtt_topic = "%prefix%/WelterRocks/CoronaData/Casting/%suffix%";
    
    private $dynamic_keys = null;
    
    private $allow_override = null;
    
    public function write($config_file = ".coronadatarc")
    {
        $fd = @fopen($config_file, "w");
        
        if (!is_resource($fd))
            return false;
            
        foreach ($this->dynamic_keys as $key)
        {
            if ($key == "password_hash")
                $val = base64_encode("\$PW\$".$this->$key);
            else
                $val = $this->$key;
                
            $write = $key."=".$val."\n";
                
            @fwrite($fd, $write);
        }
        
        @fwrite($fd, "timestamp=".time()."\n");        
        @fclose($fd);
        
        return true;
    }
    
    public function override_enable()
    {
        $this->allow_override = true;
    }
    
    public function override_disable()
    {
        $this->allow_override = false;
    }
    
    public function override_urls($url_eu_datacast, $url_rki_nowcast, $url_cov_infocast)
    {
        if (!$this->allow_override) return false;

        $this->url_eu_datacast = $url_eu_datacast;
        $this->url_rki_nowcast = $url_rki_nowcast;
        $this->url_cov_infocast = $url_cov_infocast;
        
        return true;
    }
    
    function __construct($config_file = ".coronadatarc")
    {
        $this->dynamic_keys = array("data_store", "mysql_hostname", "mysql_username", "mysql_password", "mysql_database", "mysql_socket", "mysql_hostport", "mqtt_hostname", "mqtt_hostport", "mqtt_username", "mqtt_password", "mqtt_client_id", "mqtt_topic");
        $this->allow_override = false;
        
        if (file_exists($config_file))
        {
            $ini = json_decode(json_encode(parse_ini_file($config_file)));
            
            if (is_object($ini))
            {
                foreach ($this->dynamic_keys as $key)
                {
                    if (isset($ini->$key))
                    {
                        if ($key == "password_hash")
                        {
                            if (substr(base64_decode($ini->$key), 0, 4) == "\$PW\$")
                                $this->password_hash = substr(base64_decode($ini->$key), 4);
                            else
                                $this->password_hash = md5($ini->$key);
                                
                            continue;
                        }
                        
                        $this->$key = $ini->$key;
                    }
                }
            }
        }
    }

    function __set($key, $val)
    {
        if (in_array($key, $this->dynamic_keys))
            $this->$key = $val;
        
        return;
    }
    
    function __get($key)
    {
        switch ($key)
        {
            default:
                return ((isset($this->$key)) ? $this->$key : null);
        }
    }
}
