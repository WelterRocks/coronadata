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

use WelterRocks\CoronaData\Exception;

class HttpHandler
{
    private $url = null;
    private $handle = null;
    
    private $inittime = null;
    private $closetime = null;
    private $runtime = null;
    
    private $error_number = null;
    private $error_string = null;
    
    private $return_header = null;

    private $result = null;
    private $length = null;
    private $timestamp = null;
    private $transfertime = null;
    
    private $user_agent = null;
    private $ssl_verifyhost = null;
    private $ssl_verifypeer = null;
    
    public $csv_delimiter = ";";
    public $csv_enclosure = '"';
    public $csv_escape = "\\";
    
    public static function timestamp($resolution = 1000)
    {
	return round((microtime(true) * $resolution));
    }
    
    private function query_iterate($query)
    {
        $iter = explode("&", $query);
        
        foreach ($iter as $i)
        {
            $kv = explode("=", $i, 2);
            $key = $kv[0];
            
            if (isset($kv[1]))
                $val = $kv[1];
            else
                $val = "";
                
            $iter[$key] = $val;
        }
        
        return $iter;
    }
    
    private function get_url($query = null)
    {
        $url = ((isset($this->url->scheme)) ? $this->url->scheme : "http")."://";
        $url .= ((isset($this->url->user)) ? $this->url->user.((isset($this->url->pass)) ? ":".$this->url->pass : "")."@" : "");
        $url .= ((isset($this->url->host)) ? $this->url->host : "localhost");
        $url .= ((isset($this->url->path)) ? $this->url->path : "/");
        
        if ($query)
        {
            if ((is_array($query)) || (is_object($query)))
                $query = http_build_query($query);
                
            if (isset($this->url->query))
            {
                $url_query = $this->query_iterate($this->url->query);
                $var_query = $this->query_iterate($query);
                $new_query = array_merge($url_query, $var_query);
                
                if (count($new_query) > 0)
                    $url .= "?".http_build_query($new_query);
            }
            else
            {
                $url .= "?".$query;
            }
        }
        elseif (isset($this->url->query))
        {
            $url .= "?".$this->url->query;
        }
                
        return $url;
    }
    
    private function set_error()
    {
        $this->error_number = null;
        $this->error_string = null;
        
        if (!is_resource($this->handle))
            throw new Exception("Uninitialized");
            
        $this->error_number = curl_errno($this->handle);
        $this->error_string = curl_error($this->handle);
        
        return $this->error_number;
    }
    
    public function set_return_header()
    {
        if (!is_resource($this->handle))
            throw new Exception("Uninitialized");
            
        $this->return_header = curl_getinfo($this->handle);
        
        return $this->return_header;
    }
    
    public function get_options()
    {
        $options = array(
            CURLOPT_RETURNTRANSFER => true,         
            CURLOPT_HEADER         => false,
            CURLOPT_FOLLOWLOCATION => true,         
            CURLOPT_ENCODING       => "utf-8",
            CURLOPT_USERAGENT      => $this->user_agent,     
            CURLOPT_AUTOREFERER    => true,         
            CURLOPT_CONNECTTIMEOUT => 120,          
            CURLOPT_TIMEOUT        => 300,          
            CURLOPT_MAXREDIRS      => 30,           
            CURLOPT_SSL_VERIFYHOST => $this->ssl_verifyhost,            
            CURLOPT_SSL_VERIFYPEER => $this->ssl_verifypeer,        
            CURLOPT_VERBOSE        => 0
        );
        
        return $options;
    }
    
    public function set_options($add_options = null)
    {
        if (!is_resource($this->handle))
            throw new Exception("Uninitialized");
            
        $options = $this->get_options();
        
        if ((is_array($add_options)) && (count($add_options) > 0))
        {
            foreach ($add_options as $key => $val)
            {
                $options[$key] = $val;
            }
        }
        
        return curl_setopt_array($this->handle, $options);
    }
    
    public function put($jsondata = null)
    {
        $is_json = null;
        
        return $this->post($jsondata, $is_json, "PUT");
    }
    
    public function objget($obj)
    {
        curl_setopt($this->handle, CURLOPT_URL, $this->get_url($obj));
            
        return $this->get();
    }
    
    public function post($postfields = null, &$is_json = null, $method = "POST")
    {
        if (!is_resource($this->handle))
            throw new Exception("Uninitialized");
            
        $is_json = false;
           
        if (is_object($postfields))
            $is_json = true;
        elseif (!is_array($postfields))
            throw new Exception("Post fields must be given as indexed array");
        else
            $postfields = http_build_query($postfields);
            
        if ($is_json)
            $postfields = json_encode($postfields);
        
        if ((!$method) && ($method == "POST"))
            curl_setopt($this->handle, CURLOPT_POST, 1);
        else
            curl_setopt($this->handle, CURLOPT_CUSTOMREQUEST, $method);
            
        curl_setopt($this->handle, CURLOPT_SAFE_UPLOAD, true);
        curl_setopt($this->handle, CURLOPT_POSTFIELDS, $postfields);
        
        if ($is_json)
            curl_setopt($this->handle, CURLOPT_HTTPHEADER, array("Content-Type: application/json", "Content-Length: ".strlen($postfields)));
            
        $this->result = curl_exec($this->handle);
        $this->length = strlen($this->result);
        $this->timestamp = self::timestamp();
        $this->transfertime = ($this->timestamp - $this->inittime);
        
        return $this->length;
    }
    
    public function get()
    {
        if (!is_resource($this->handle))
            throw new Exception("Uninitialized");
            
        $this->result = curl_exec($this->handle);
        $this->length = strlen($this->result);
        $this->timestamp = self::timestamp();
        $this->transfertime = ($this->timestamp - $this->inittime);
        
        return $this->length;
    }
    
    public function init($query = null)
    {
        if (is_resource($this->handle))
            throw new Exception("Already initialized");
            
        $this->handle = curl_init($this->get_url($query));
        $this->inittime = self::timestamp();
        
        return $this->handle;
    }
    
    public function close()
    {
        if (is_resource($this->handle))
        {
            $res = curl_close($this->handle);
            
            $this->closetime = self::timestamp();
            $this->runtime = ($this->closetime - $this->inittime);
            
            return $res;
        }
            
        return true;
    }
    
    public function parse_csv($csv_buffer, $eol = "\n")
    {
        $csv_data = str_getcsv($csv_buffer, $eol, $this->csv_enclosure, $this->csv_escape);
        
        foreach ($csv_data as &$row)
            $row = str_getcsv($row, $this->csv_delimiter, $this->csv_enclosure, $this->csv_escape);
        
        return $csv_data;
    }
    
    public function get_error_number()
    {
        return $this->error_number;
    }
    
    public function get_error_string()
    {
        return $this->error_string;
    }
    
    public function get_return_header()
    {
        return $this->return_header;
    }
    
    public function get_length()
    {
        return $this->length;
    }
    
    public function get_data(&$content_type = null)
    {
        return json_decode($this->get_result(true, $content_type));
    }
    
    public function get_result($raw = false, &$content_type = null, $force_content_null = null)
    {        
        if ($force_content_type)
            $content_type = $force_content_type;
        else
            $content_type = strtolower(explode(";", $this->return_header["content_type"])[0]);

        if ($raw)	
            return $this->result;            
                
        switch ($content_type)
        {
            case "application/json":
            case "text/json":
                return json_decode($this->result);
            case "application/csv":
            case "text/csv":
                return $this->parse_csv($this->result);
            default:
                return $this->result;
        }
    }
    
    public function get_inittime()
    {
        return $this->inittime;
    }
    
    public function get_closetime()
    {
        return $this->closetime;
    }
    
    public function get_runtime()
    {
        return $this->runtime;
    }
    
    public function get_timestamp()
    {
        return $this->timestamp;
    }
    
    public function get_transfertime()
    {
        return $this->transfertime;
    }
    
    public function retrieve($method = "get", $query = null, $postdata = null, $add_options = null, &$data = null, $force_content_type = null, $force_raw = false, &$content_type = null)
    {
        $data = null;
        
        switch ($method)
        {
            case "get":
            case "post":
                if (is_resource($this->init($query)))
                    $this->set_options($add_options);
                break;
            default:
                throw new Exception("Invalid method requested");
                return;
        }        
        
        if ($method == "post")
            $length = $this->post($postdata);
        else
            $length = $this->get();
        
        $this->set_error();
        $this->set_return_header();
        $this->close();
        
        $content_type = null;
        
        $data = $this->get_result($force_raw, $content_type, $force_content_type);
                
        return $length;
    }
    
    function __construct($url, $ssl_verifyhost = 2, $ssl_verifypeer = 1, $user_agent = null)
    {
        $this->url = json_decode(json_encode(parse_url($url)));
        
        if (!$this->url)
            throw new Exception("Unable to parse URL");

        if ((isset($this->url->path)) && ($this->url->path == $url))
            throw new Exception("Invalid URL");
            
        $this->ssl_verifyhost = $ssl_verifyhost;
        $this->ssl_verifypeer = $ssl_verifypeer;
        
        if ($user_agent == null)
            $this->user_agent = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.102 Safari/537.36 Edge/18.19582 Edge 18.19577";
        else
            $this->user_agent = $user_agent;
    }
    
    function __destruct()
    {
        $this->close();
    }
}
