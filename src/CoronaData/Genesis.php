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
use WelterRocks\CoronaData\Config;
use WelterRocks\CoronaData\HttpHandler;

final class Genesis
{
    private $api = null;
    
    private $config = null;
    
    public $csv_delimiter = ";";
    public $csv_enclosure = '"';
    public $csv_escape = "\\";
    
    private function prepare($url_suffix)
    {
        $this->api = new HttpHandler($this->config->url_genesis_api.$url_suffix);
        
        $this->api->init();
        $this->api->set_options();
    }
    
    private function get_login()
    {
        $login = new \stdClass;
        $login->username = $this->config->genesis_username;
        $login->password = $this->config->genesis_password;
        $login->language = "en";
        
        return $login;    
    }
    
    private function send_api($path, \stdClass $params = null)
    {
        $this->prepare($path);
        
        $login = $this->get_login();
        
        foreach ($params as $key => $val)
        {
            if ($val !== null)
                $login->$key = $val;
        }
        
        if ($this->api->objget($login))
            return $this->api->get_data();
            
        return null;        
    }
    
    protected function catalogue($catalogue, \stdClass $params)
    {
        $path = "catalogue/".$catalogue;
        
        $params->catalogue = $catalogue;
        
        return $this->send_api($path, $params);
    }
    
    protected function data($data, \stdClass $params)
    {
        $path = "data/".$data;
        
        return $this->send_api($path, $params);
    }
    
    protected function metadata($metadata, \stdClass $params)
    {
        $path = "metadata/".$metadata;
        
        return $this->send_api($path, $params);
    }
    
    protected function profile($profile, \stdClass $params)
    {
        $path = "profile/".$profile;
        
        return $this->send_api($path, $params);
    }
    
    public function whoami()
    {
        $this->prepare("helloworld/whoami");
        
        if ($this->api->get() > 0)
            return $this->api->get_data();
        
        return null;
    }
    
    public function logincheck()
    {
        $this->prepare("helloworld/logincheck");
        
        $login = $this->get_login();
        
        if ($this->api->objget($login))
            return $this->api->get_data();
            
        return null;
    }
    
    public function find($term, $category = "all", $pagelength = 100)
    {
        $this->prepare("find/find");
        
        $login = $this->get_login();
        $login->term = $term;
        $login->category = $category;
        $login->pagelength = $pagelength;
        
        if ($this->api->objget($login))
            return $this->api->get_data();
            
        return null;
    }
    
    public function data_chart2result($name, $area = "all", $charttype = 0, $drawpoints = false, $zoom = 0, $focus = false, $tops = false, $format = "png")
    {
        $params = new \stdClass;
        $params->name = $name;
        $params->area = $area;
        $params->chartType = $charttype;
        $params->drawPoints = $drawpoints;
        $params->zoom = $zoom;
        $params->focus = $focus;
        $params->tops = $tops;
        $params->format = $format;
        
        return $this->data("chart2result", $params);
    }
    
    public function data_chart2table($name, $area = "all", $charttype = 0, $drawpoints = false, $zoom = 0, $focus = false, $tops = false, $startyear = null, $endyear = null, $timeslices = 1, $regionalvariable = null, $regionalkey = null, $classifyingvariable1 = null, $classifyingkey1 = null, $classifyingvariable2 = null, $classifyingkey2 = null, $classifyingvariable3 = null, $classifyingkey3 = null, $stand = null, $format = "png")
    {
        $params = new \stdClass;
        $params->name = $name;
        $params->area = $area;
        $params->chartType = $charttype;
        $params->drawPoints = $drawpoints;
        $params->zoom = $zoom;
        $params->focus = $focus;
        $params->tops = $tops;
        $params->startyear = $startyear;
        $params->endyear = $endyear;
        $params->timeslices = $timeslices;
        $params->regionalvariable = $regionalvariable;
        $params->regionalkey = $regionalkey;
        $params->classifyingvariable1 = $classifyingvariable1;
        $params->classifyingkey1 = $classifyingkey1;
        $params->classifyingvariable2 = $classifyingvariable2;
        $params->classifyingkey2 = $classifyingkey2;
        $params->classifyingvariable3 = $classifyingvariable3;
        $params->classifyingkey3 = $classifyingkey3;
        $params->stand = $stand;
        $params->format = $format;
        
        return $this->data("chart2table", $params);
    }
    
    public function data_chart2timeseries($name, $area = "all", $charttype = 0, $drawpoints = false, $zoom = 0, $focus = false, $tops = false, $contents = null, $startyear = null, $endyear = null, $timeslices = 1, $regionalvariable = null, $regionalkey = null, $classifyingvariable1 = null, $classifyingkey1 = null, $classifyingvariable2 = null, $classifyingkey2 = null, $classifyingvariable3 = null, $classifyingkey3 = null, $stand = null, $format = "png")
    {
        $params = new \stdClass;
        $params->name = $name;
        $params->area = $area;
        $params->chartType = $charttype;
        $params->drawPoints = $drawpoints;
        $params->zoom = $zoom;
        $params->focus = $focus;
        $params->tops = $tops;
        $params->contents = $contents;
        $params->startyear = $startyear;
        $params->endyear = $endyear;
        $params->timeslices = $timeslices;
        $params->regionalvariable = $regionalvariable;
        $params->regionalkey = $regionalkey;
        $params->classifyingvariable1 = $classifyingvariable1;
        $params->classifyingkey1 = $classifyingkey1;
        $params->classifyingvariable2 = $classifyingvariable2;
        $params->classifyingkey2 = $classifyingkey2;
        $params->classifyingvariable3 = $classifyingvariable3;
        $params->classifyingkey3 = $classifyingkey3;
        $params->stand = $stand;
        $params->format = $format;
        
        return $this->data("chart2timeseries", $params);
    }
    
    public function data_cube($name, $area = "all", $values = true, $metadata = false, $additionals = false, $charttype = 0, $drawpoints = false, $zoom = 0, $focus = false, $tops = false, $contents = null, $startyear = null, $endyear = null, $timeslices = 1, $regionalvariable = null, $regionalkey = null, $classifyingvariable1 = null, $classifyingkey1 = null, $classifyingvariable2 = null, $classifyingkey2 = null, $classifyingvariable3 = null, $classifyingkey3 = null, $stand = null, $format = "csv")
    {
        $params = new \stdClass;
        $params->name = $name;
        $params->area = $area;
        $params->values = $values;
        $params->metadata = $metadata;
        $params->additionals = $additionals;
        $params->chartType = $charttype;
        $params->drawPoints = $drawpoints;
        $params->zoom = $zoom;
        $params->focus = $focus;
        $params->tops = $tops;
        $params->contents = $contents;
        $params->startyear = $startyear;
        $params->endyear = $endyear;
        $params->timeslices = $timeslices;
        $params->regionalvariable = $regionalvariable;
        $params->regionalkey = $regionalkey;
        $params->classifyingvariable1 = $classifyingvariable1;
        $params->classifyingkey1 = $classifyingkey1;
        $params->classifyingvariable2 = $classifyingvariable2;
        $params->classifyingkey2 = $classifyingkey2;
        $params->classifyingvariable3 = $classifyingvariable3;
        $params->classifyingkey3 = $classifyingkey3;
        $params->stand = $stand;
        $params->format = $format;
        
        return $this->data("cube", $params);
    }
    
    public function data_cubefile($name, $area = "all", $values = true, $metadata = false, $additionals = false, $charttype = 0, $drawpoints = false, $zoom = 0, $focus = false, $tops = false, $contents = null, $startyear = null, $endyear = null, $timeslices = 1, $regionalvariable = null, $regionalkey = null, $classifyingvariable1 = null, $classifyingkey1 = null, $classifyingvariable2 = null, $classifyingkey2 = null, $classifyingvariable3 = null, $classifyingkey3 = null, $stand = null, $format = "csv")
    {
        $params = new \stdClass;
        $params->name = $name;
        $params->area = $area;
        $params->values = $values;
        $params->metadata = $metadata;
        $params->additionals = $additionals;
        $params->chartType = $charttype;
        $params->drawPoints = $drawpoints;
        $params->zoom = $zoom;
        $params->focus = $focus;
        $params->tops = $tops;
        $params->contents = $contents;
        $params->startyear = $startyear;
        $params->endyear = $endyear;
        $params->timeslices = $timeslices;
        $params->regionalvariable = $regionalvariable;
        $params->regionalkey = $regionalkey;
        $params->classifyingvariable1 = $classifyingvariable1;
        $params->classifyingkey1 = $classifyingkey1;
        $params->classifyingvariable2 = $classifyingvariable2;
        $params->classifyingkey2 = $classifyingkey2;
        $params->classifyingvariable3 = $classifyingvariable3;
        $params->classifyingkey3 = $classifyingkey3;
        $params->stand = $stand;
        $params->format = $format;
        
        return $this->data("file", $params);
    }
    
    public function data_map2result($name, $area = "all", $maptype = 0, $classes = 2, $classification = 0, $zoom = 0, $format = "png")
    {
        $params = new \stdClass;
        $params->name = $name;
        $params->area = $area;
        $params->maptype = $maptype;
        $params->classes = $classes;
        $params->classification = $classification;
        $params->zoom = $zoom;
        $params->format = $format;
        
        return $this->data("map2result", $params);
    }
    
    public function data_map2table($name, $area = "all", $maptype = 0, $classes = 2, $classification = 0, $zoom = 0, $startyear = null, $endyear = null, $timeslices = 1, $regionalvariable = null, $regionalkey = null, $classifyingvariable1 = null, $classifyingkey1 = null, $classifyingvariable2 = null, $classifyingkey2 = null, $classifyingvariable3 = null, $classifyingkey3 = null, $stand = null, $format = "png")
    {
        $params = new \stdClass;
        $params->name = $name;
        $params->area = $area;
        $params->maptype = $maptype;
        $params->classes = $classes;
        $params->classification = $classification;
        $params->zoom = $zoom;
        $params->startyear = $startyear;
        $params->endyear = $endyear;
        $params->timeslices = $timeslices;
        $params->regionalvariable = $regionalvariable;
        $params->regionalkey = $regionalkey;
        $params->classifyingvariable1 = $classifyingvariable1;
        $params->classifyingkey1 = $classifyingkey1;
        $params->classifyingvariable2 = $classifyingvariable2;
        $params->classifyingkey2 = $classifyingkey2;
        $params->classifyingvariable3 = $classifyingvariable3;
        $params->classifyingkey3 = $classifyingkey3;
        $params->stand = $stand;
        $params->format = $format;
        
        return $this->data("map2table", $params);
    }
    
    public function data_map2timeseries($name, $area = "all", $maptype = 0, $classes = 2, $classification = 0, $zoom = 0, $contents = null, $startyear = null, $endyear = null, $timeslices = 1, $regionalvariable = null, $regionalkey = null, $classifyingvariable1 = null, $classifyingkey1 = null, $classifyingvariable2 = null, $classifyingkey2 = null, $classifyingvariable3 = null, $classifyingkey3 = null, $stand = null, $format = "png")
    {
        $params = new \stdClass;
        $params->name = $name;
        $params->area = $area;
        $params->maptype = $maptype;
        $params->classes = $classes;
        $params->classification = $classification;
        $params->zoom = $zoom;
        $params->contents = $contents;
        $params->startyear = $startyear;
        $params->endyear = $endyear;
        $params->timeslices = $timeslices;
        $params->regionalvariable = $regionalvariable;
        $params->regionalkey = $regionalkey;
        $params->classifyingvariable1 = $classifyingvariable1;
        $params->classifyingkey1 = $classifyingkey1;
        $params->classifyingvariable2 = $classifyingvariable2;
        $params->classifyingkey2 = $classifyingkey2;
        $params->classifyingvariable3 = $classifyingvariable3;
        $params->classifyingkey3 = $classifyingkey3;
        $params->stand = $stand;
        $params->format = $format;
        
        return $this->data("map2table", $params);
    }
    
    public function data_result($name, $area = "all", $compress = false)
    {
        $params = new \stdClass;
        $params->name = $name;
        $params->area = $area;
        $params->compress = $compress;
        
        return $this->data("result", $params);
    }
    
    public function data_resultfile($name, $area = "all", $compress = false, $format = "datencsv")
    {
        $params = new \stdClass;
        $params->name = $name;
        $params->area = $area;
        $params->compress = $compress;
        $params->format = $format;
        
        return $this->data("resultfile", $params);
    }
    
    public function data_table($name, $area = "all", $compress = false, $transpose = false, $startyear = null, $endyear = null, $timeslices = 1, $regionalvariable = null, $regionalkey = null, $classifyingvariable1 = null, $classifyingkey1 = null, $classifyingvariable2 = null, $classifyingkey2 = null, $classifyingvariable3 = null, $classifyingkey3 = null, $job = false, $stand = null)
    {
        $params = new \stdClass;
        $params->name = $name;
        $params->area = $area;
        $params->compress = $compress;
        $params->transpose = $transpose;
        $params->startyear = $startyear;
        $params->endyear = $endyear;
        $params->timeslices = $timeslices;
        $params->regionalvariable = $regionalvariable;
        $params->regionalkey = $regionalkey;
        $params->classifyingvariable1 = $classifyingvariable1;
        $params->classifyingkey1 = $classifyingkey1;
        $params->classifyingvariable2 = $classifyingvariable2;
        $params->classifyingkey2 = $classifyingkey2;
        $params->classifyingvariable3 = $classifyingvariable3;
        $params->classifyingkey3 = $classifyingkey3;
        $params->job = $job;
        $params->stand = $stand;
        
        return $this->data("table", $params);
    }
    
    public function data_tablefile($name, $area = "all", $compress = false, $transpose = false, $startyear = null, $endyear = null, $timeslices = 1, $regionalvariable = null, $regionalkey = null, $classifyingvariable1 = null, $classifyingkey1 = null, $classifyingvariable2 = null, $classifyingkey2 = null, $classifyingvariable3 = null, $classifyingkey3 = null, $format = "datencsv", $job = false, $stand = null)
    {
        $params = new \stdClass;
        $params->name = $name;
        $params->area = $area;
        $params->compress = $compress;
        $params->transpose = $transpose;
        $params->startyear = $startyear;
        $params->endyear = $endyear;
        $params->timeslices = $timeslices;
        $params->regionalvariable = $regionalvariable;
        $params->regionalkey = $regionalkey;
        $params->classifyingvariable1 = $classifyingvariable1;
        $params->classifyingkey1 = $classifyingkey1;
        $params->classifyingvariable2 = $classifyingvariable2;
        $params->classifyingkey2 = $classifyingkey2;
        $params->classifyingvariable3 = $classifyingvariable3;
        $params->classifyingkey3 = $classifyingkey3;
        $params->format = $format;
        $params->job = $job;
        $params->stand = $stand;
        
        return $this->data("tablefile", $params);
    }
    
    public function data_timeseries($name, $area = "all", $compress = false, $transpose = false, $contents = null, $startyear = null, $endyear = null, $timeslices = 1, $regionalvariable = null, $regionalkey = null, $regionalkeycode = false, $classifyingvariable1 = null, $classifyingkey1 = null, $classifyingkeycode1 = false, $classifyingvariable2 = null, $classifyingkey2 = null, $classifyingkeycode2 = false, $classifyingvariable3 = null, $classifyingkey3 = null, $classifyingkeycode3 = false, $job = false, $stand = null)
    {
        $params = new \stdClass;
        $params->name = $name;
        $params->area = $area;
        $params->contents = $contents;
        $params->compress = $compress;
        $params->transpose = $transpose;
        $params->startyear = $startyear;
        $params->endyear = $endyear;
        $params->timeslices = $timeslices;
        $params->regionalvariable = $regionalvariable;
        $params->regionalkey = $regionalkey;
        $params->regionalkeycode = $regionalkeycode;
        $params->classifyingvariable1 = $classifyingvariable1;
        $params->classifyingkey1 = $classifyingkey1;
        $params->classifyingkeycode1 = $classifyingkeycode1;
        $params->classifyingvariable2 = $classifyingvariable2;
        $params->classifyingkey2 = $classifyingkey2;
        $params->classifyingkeycode2 = $classifyingkeycode2;
        $params->classifyingvariable3 = $classifyingvariable3;
        $params->classifyingkey3 = $classifyingkey3;
        $params->classifyingkeycode3 = $classifyingkeycode3;
        $params->job = $job;
        $params->stand = $stand;
        
        return $this->data("timeseries", $params);
    }
    
    public function data_timeseriesfile($name, $area = "all", $compress = false, $transpose = false, $contents = null, $startyear = null, $endyear = null, $timeslices = 1, $regionalvariable = null, $regionalkey = null, $regionalkeycode = false, $classifyingvariable1 = null, $classifyingkey1 = null, $classifyingkeycode1 = false, $classifyingvariable2 = null, $classifyingkey2 = null, $classifyingkeycode2 = false, $classifyingvariable3 = null, $classifyingkey3 = null, $classifyingkeycode3 = false, $format = "csv", $job = false, $stand = null)
    {
        $params = new \stdClass;
        $params->name = $name;
        $params->area = $area;
        $params->contents = $contents;
        $params->compress = $compress;
        $params->transpose = $transpose;
        $params->startyear = $startyear;
        $params->endyear = $endyear;
        $params->timeslices = $timeslices;
        $params->regionalvariable = $regionalvariable;
        $params->regionalkey = $regionalkey;
        $params->regionalkeycode = $regionalkeycode;
        $params->classifyingvariable1 = $classifyingvariable1;
        $params->classifyingkey1 = $classifyingkey1;
        $params->classifyingkeycode1 = $classifyingkeycode1;
        $params->classifyingvariable2 = $classifyingvariable2;
        $params->classifyingkey2 = $classifyingkey2;
        $params->classifyingkeycode2 = $classifyingkeycode2;
        $params->classifyingvariable3 = $classifyingvariable3;
        $params->classifyingkey3 = $classifyingkey3;
        $params->classifyingkeycode3 = $classifyingkeycode3;
        $params->format = $format;
        $params->job = $job;
        $params->stand = $stand;
        
        return $this->data("timeseriesfile", $params);
    }
    
    public function metadata_cube($name, $area = "all")
    {
        $params = new \stdClass;
        $params->name = $name;
        $params->area = $area;
        
        return $this->metadata("cube", $params);
    }
    
    public function metadata_statistic($name, $area = "all")
    {
        $params = new \stdClass;
        $params->name = $name;
        $params->area = $area;
        
        return $this->metadata("statistic", $params);
    }
    
    public function metadata_table($name, $area = "all")
    {
        $params = new \stdClass;
        $params->name = $name;
        $params->area = $area;
        
        return $this->metadata("table", $params);
    }
    
    public function metadata_timeseries($name, $area = "all")
    {
        $params = new \stdClass;
        $params->name = $name;
        $params->area = $area;
        
        return $this->metadata("timeseries", $params);
    }
    
    public function metadata_value($name, $area = "all")
    {
        $params = new \stdClass;
        $params->name = $name;
        $params->area = $area;
        
        return $this->metadata("values", $params);
    }
    
    public function metadata_variable($name, $area = "all")
    {
        $params = new \stdClass;
        $params->name = $name;
        $params->area = $area;
        
        return $this->metadata("variable", $params);
    }
    
    public function catalogue_cubes($selection = "*", $area = "all", $pagelength = 100)
    {
        $params = new \stdClass;
        $params->selection = $selection;
        $params->area = $area;
        $params->pagelength = $pagelength;
        
        return $this->catalogue("cubes", $params);
    }
    
    public function catalogue_cubes2statistic($name, $selection = "*", $area = "all", $pagelength = 100)
    {
        $params = new \stdClass;
        $params->name = $name;
        $params->selection = $selection;
        $params->area = $area;
        $params->pagelength = $pagelength;
        
        return $this->catalogue("cubes2statistic", $params);
    }
    
    public function catalogue_cubes2variable($name, $selection = "*", $area = "all", $pagelength = 100)
    {
        $params = new \stdClass;
        $params->name = $name;
        $params->selection = $selection;
        $params->area = $area;
        $params->pagelength = $pagelength;
        
        return $this->catalogue("cubes2variable", $params);
    }
    
    public function catalogue_jobs($searchcriterion, $sortcriterion = null, $selection = "*", $type = "all", $pagelength = 100)
    {
        $params = new \stdClass;
        $params->searchcriterion = $searchcriterion;
        $params->sortcriterion = $sortcriterion;
        $params->selection = $selection;
        $params->type = $type;
        $params->pagelength = $pagelength;
        
        return $this->catalogue("jobs", $params);
    }
    
    public function catalogue_modifieddata($selection = "*", $type = "all", $date = null, $pagelength = 100)
    {
        $params = new \stdClass;
        $params->date = $date;
        $params->selection = $selection;
        $params->type = $type;
        $params->pagelength = $pagelength;
        
        return $this->catalogue("modifieddata", $params);
    }
    
    public function catalogue_qualitysigns()
    {
        $params = new \stdClass;
        
        return $this->catalogue("qualitysigns", $params);
    }
    
    public function catalogue_results($selection = "*", $area = "all", $pagelength = 100)
    {
        $params = new \stdClass;
        $params->selection = $selection;
        $params->area = $area;
        $params->pagelength = $pagelength;
        
        return $this->catalogue("results", $params);
    }
    
    public function catalogue_statistics($searchcriterion, $sortcriterion = null, $selection = "*", $pagelength = 100)
    {
        $params = new \stdClass;
        $params->searchcriterion = $searchcriterion;
        $params->sortcriterion = $sortcriterion;
        $params->selection = $selection;
        $params->pagelength = $pagelength;
        
        return $this->catalogue("statistics", $params);
    }
    
    public function catalogue_statistics2variable($name, $searchcriterion, $sortcriterion = null, $selection = "*", $area = "all", $pagelength = 100)
    {
        $params = new \stdClass;
        $params->name = $name;
        $params->searchcriterion = $searchcriterion;
        $params->sortcriterion = $sortcriterion;
        $params->selection = $selection;
        $params->pagelength = $pagelength;
        $params->area = $area;
        
        return $this->catalogue("statistics2variable", $params);
    }
    
    public function catalogue_tables($searchcriterion, $sortcriterion = null, $selection = "*", $area = "all", $pagelength = 100)
    {
        $params = new \stdClass;
        $params->searchcriterion = $searchcriterion;
        $params->sortcriterion = $sortcriterion;
        $params->selection = $selection;
        $params->pagelength = $pagelength;
        $params->area = $area;
        
        return $this->catalogue("tables", $params);
    }
    
    public function catalogue_tables2statistics($name, $selection = "*", $area = "all", $pagelength = 100)
    {
        $params = new \stdClass;
        $params->name = $name;
        $params->selection = $selection;
        $params->pagelength = $pagelength;
        $params->area = $area;
        
        return $this->catalogue("tables2statistics", $params);
    }
    
    public function catalogue_tables2variables($name, $selection = "*", $area = "all", $pagelength = 100)
    {
        $params = new \stdClass;
        $params->name = $name;
        $params->selection = $selection;
        $params->pagelength = $pagelength;
        $params->area = $area;
        
        return $this->catalogue("tables2variables", $params);
    }
    
    public function catalogue_terms($selection = "*", $pagelength = 100)
    {
        $params = new \stdClass;
        $params->selection = $selection;
        $params->pagelength = $pagelength;
        
        return $this->catalogue("terms", $params);
    }
    
    public function catalogue_timeseries($selection = "*", $area = "all", $pagelength = 100)
    {
        $params = new \stdClass;
        $params->selection = $selection;
        $params->pagelength = $pagelength;
        $params->area = $area;

        return $this->catalogue("timeseries", $params);
    }
    
    public function catalogue_timeseries2statistic($name, $selection = "*", $area = "all", $pagelength = 100)
    {
        $params = new \stdClass;
        $params->name = $name;
        $params->selection = $selection;
        $params->pagelength = $pagelength;
        $params->area = $area;

        return $this->catalogue("timeseries2statistic", $params);
    }
    
    public function catalogue_timeseries2variable($name, $selection = "*", $area = "all", $pagelength = 100)
    {
        $params = new \stdClass;
        $params->name = $name;
        $params->selection = $selection;
        $params->pagelength = $pagelength;
        $params->area = $area;

        return $this->catalogue("timeseries2variable", $params);
    }
    
    public function catalogue_values($searchcriterion, $sortcriterion = null, $selection = "*", $area = "all", $pagelength = 100)
    {
        $params = new \stdClass;
        $params->searchcriterion = $searchcriterion;
        $params->sortcriterion = $sortcriterion;
        $params->selection = $selection;
        $params->pagelength = $pagelength;
        $params->area = $area;

        return $this->catalogue("values", $params);
    }
    
    public function catalogue_values2variable($name, $searchcriterion, $sortcriterion = null, $selection = "*", $area = "all", $pagelength = 100)
    {
        $params = new \stdClass;
        $params->name = $name;
        $params->searchcriterion = $searchcriterion;
        $params->sortcriterion = $sortcriterion;
        $params->selection = $selection;
        $params->pagelength = $pagelength;
        $params->area = $area;

        return $this->catalogue("values2variable", $params);
    }
    
    public function catalogue_variables($searchcriterion, $sortcriterion = null, $type = "all", $selection = "*", $area = "all", $pagelength = 100)
    {
        $params = new \stdClass;
        $params->type = $type;
        $params->searchcriterion = $searchcriterion;
        $params->sortcriterion = $sortcriterion;
        $params->selection = $selection;
        $params->pagelength = $pagelength;
        $params->area = $area;

        return $this->catalogue("variables", $params);
    }
    
    public function catalogue_variables2statistic($name, $searchcriterion, $sortcriterion = null, $type = "all", $selection = "*", $area = "all", $pagelength = 100)
    {
        $params = new \stdClass;
        $params->name = $name;
        $params->type = $type;
        $params->searchcriterion = $searchcriterion;
        $params->sortcriterion = $sortcriterion;
        $params->selection = $selection;
        $params->pagelength = $pagelength;
        $params->area = $area;

        return $this->catalogue("variables2statistic", $params);
    }
    
    public function profile_password($new_password, $repeat_password)
    {
        if ($new_password != $repeat_password)
            return false;
            
        $params = new \stdClass;
        $params->new = $new_password;
        $params->repeat = $repeat_password;
            
        return $this->profile("password", $params);
    }
    
    public function profile_remove_result($name, $area = "all")
    {
        if ($new_password != $repeat_password)
            return false;
            
        $params = new \stdClass;
        $params->name = $name;
        $params->area = $area;
            
        return $this->profile("removeResult", $params);
    }
    
    public function parse_csv($csv_buffer, $eol = "\n")
    {
        $csv_data = str_getcsv($csv_buffer, $eol, $this->csv_enclosure, $this->csv_escape);

        foreach ($csv_data as &$row)
            $row = str_getcsv($row, $this->csv_delimiter, $this->csv_enclosure, $this->csv_escape);

        return $csv_data;
    }
    
    function __construct(Config $config)
    {
        $this->config = $config;
    }
}
