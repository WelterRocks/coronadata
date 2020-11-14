<?php namespace WelterRocks\CoronaData\Table;

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
use WelterRocks\CoronaData\Table\Base;

class Grafana extends Base
{
    protected $__is_view = true;
    
    // Fields from locations
    protected $timestamp_min = null;
    protected $timestamp_max = null;      
    protected $continent_id = null;
    protected $continent_hash = null;
    protected $continent_name = null;
    protected $country_id = null;
    protected $country_hash = null;
    protected $country_name = null;
    protected $state_id = null;
    protected $state_hash = null;
    protected $state_name = null;
    protected $district_id = null;
    protected $district_hash = null;
    protected $district_name = null;
    protected $district_type = null;
    protected $district_fullname = null;
    protected $location_id = null;
    protected $location_hash = null;
    protected $location_name = null;
    protected $location_type = null;
    protected $geo_id = null;
    protected $population_year = null;
    protected $population_count = null;
    protected $population_females = null;
    protected $population_males = null;
    protected $area = null;
    protected $deaths_count = null;
    protected $deaths_min = null;
    protected $deaths_max = null;
    protected $cases_count = null;
    protected $cases_min = null;
    protected $cases_max = null;
    protected $recovered_count = null;
    protected $recovered_min = null;
    protected $recovered_max = null;
    protected $aged_65_older = null;
    protected $aged_70_older = null;
    protected $cardiovasc_death_rate = null;
    protected $diabetes_prevalence = null;
    protected $gdp_per_capita = null;
    protected $handwashing_facilities = null;
    protected $hospital_beds_per_thousand = null;
    protected $human_development_index = null;
    protected $life_expectancy = null;
    protected $median_age = null;
    protected $contamination_total = null;
    protected $contamination_rundays = null;
    protected $contamination_per_day = null;
    protected $contamination_target = null;
    protected $population_density = null;
    protected $infection_density = null;
    protected $infection_probability = null;
    protected $infection_area = null;
    protected $female_smokers = null;
    protected $male_smokers = null;
    protected $extreme_poverty = null;
    protected $esteem_new_diseases = null;
    protected $lower_new_diseases = null;
    protected $upper_new_diseases = null;
    protected $esteem_new_diseases_ma4 = null;
    protected $lower_new_diseases_ma4 = null;
    protected $upper_new_diseases_ma4 = null;
    protected $esteem_reproduction_r = null;
    protected $lower_reproduction_r = null;
    protected $upper_reproduction_r = null;
    protected $esteem_7day_r_value = null;
    protected $lower_7day_r_value = null;
    protected $upper_7day_r_value = null;
    protected $date_nowcast = null;

    // Fields from dataset        
    protected $dataset_hash = null;
    protected $date_rep = null;
    protected $day_of_week = null;
    protected $day = null;
    protected $month = null;
    protected $year = null;
    protected $cases = null;
    protected $deaths = null;
    protected $recovered = null;
    protected $timestamp_represent = null;
    protected $hosp_patients = null;
    protected $hosp_patients_per_million = null;
    protected $icu_patients = null;
    protected $icu_patients_per_million = null;
    protected $new_cases = null;
    protected $new_cases_per_million = null;
    protected $new_cases_smoothed = null;
    protected $new_cases_smoothed_per_million = null;
    protected $new_deaths = null;
    protected $new_deaths_per_million = null;
    protected $new_deaths_smoothed = null;
    protected $new_deaths_smoothed_per_million = null;
    protected $new_recovered = null;
    protected $new_recovered_per_million = null;
    protected $new_recovered_smoothed = null;
    protected $new_recovered_smoothed_per_million = null;
    protected $new_tests = null;
    protected $new_tests_per_thousand = null;
    protected $new_tests_smoothed = null;
    protected $new_tests_smoothed_per_thousand = null;
    protected $positive_rate = null;
    protected $stringency_index = null;
    protected $tests_per_case = null;
    protected $tests_units = null;
    protected $total_cases = null;
    protected $total_cases_per_million = null;
    protected $total_deaths = null;
    protected $total_deaths_per_million = null;
    protected $total_recovered = null;
    protected $total_recovered_per_million = null;
    protected $total_tests = null;
    protected $total_tests_per_thousand = null;
    protected $weekly_hosp_admissions = null;
    protected $weekly_hosp_admissions_per_million = null;
    protected $weekly_icu_admissions = null;
    protected $weekly_icu_admissions_per_million = null;
    protected $incidence_7day = null;
    protected $incidence_14day = null;
    protected $incidence_7day_smoothed = null;
    protected $incidence_14day_smoothed = null;
    protected $exponence_7day = null;
    protected $exponence_14day = null;
    protected $exponence_7day_smoothed = null;
    protected $exponence_14day_smoothed = null;
    protected $warning_level_7day = null;
    protected $warning_level_14day = null;
    protected $warning_level = null;
    protected $warning_tendence = null;
    protected $flag_enforce_daily_need_deliveries = null;
    protected $flag_enforce_treatment_priorization = null;
    protected $flag_lockdown_primary_infrastructure = null;
    protected $flag_isolate_executive_staff = null;
    protected $flag_enforce_federation_control = null;
    protected $flag_limit_fundamental_rights = null;
    protected $flag_lockdown_schools = null;
    protected $flag_lockdown_gastronomy = null;
    protected $flag_lockdown_secondary_infrastructure = null;
    protected $flag_enforce_local_crisis_team_control = null;
    protected $flag_enforce_gastronomy_rules = null;
    protected $flag_lockdown_leisure_activities = null;
    protected $flag_isolate_medium_risk_group = null;
    protected $flag_reserve_icu_units = null;
    protected $flag_enforce_shopping_rules = null;
    protected $flag_isolate_high_risk_group = null;
    protected $flag_general_caution = null;
    protected $flag_attention_on_symptoms = null;
    protected $flag_wash_hands = null;
    protected $flag_recommend_mask_wearing = null;
    protected $flag_enforce_critical_mask_wearing = null;
    protected $flag_enforce_public_mask_wearing = null;
    protected $flag_isolate_low_risk_group = null;
    protected $enforce_distance_meters = null;
    protected $enforce_household_plus_persons_to = null;
    protected $enforce_public_groups_to = null;
    protected $enforce_public_events_to = null;
    
    // Generic fields
    protected $location_update_count = null;
    protected $location_timestamp_last_update = null;
    protected $location_timestamp_registration = null;
    protected $location_timestamp_disabled = null;
    protected $location_timestamp_enabled = null;
    protected $location_timestamp_deleted = null;
    protected $location_timestamp_undeleted = null;

    protected function get_install_sql()
    {
      $prefix = "locations";
      $k = array();
      
      foreach ($this as $key => $val)
      {
        if ($key == "dataset_hash")
          $prefix = "datasets";
          
        if (substr($key, 0, 2) == "__")
          continue;
        
        switch ($key)
        {
          case "flag_updated":
          case "flag_deleted":
          case "flag_disabled":
            $k[$key] = "(`locations`.`".$key."` | `datasets`.`".$key."`) as `".$key."`";
            continue(2);
          case "location_update_count":
          case "location_timestamp_last_update":
          case "location_timestamp_registration":
          case "location_timestamp_disabled":
          case "location_timestamp_enabled":
          case "location_timestamp_deleted":
          case "location_timestamp_undeleted":
            continue(2);
          case "update_count":
          case "timestamp_last_update":
          case "timestamp_registration":
          case "timestamp_disabled":
          case "timestamp_enabled":
          case "timestamp_deleted":
          case "timestamp_undeleted":
            $k["location_".$key] = "`locations`.`".$key."` as `locations_".$key."`";
            $k[$key] = "`datasets`.`".$key."` as `".$key."`";
            continue(2);
        }
        
        $k[$key] = "`".$prefix."`.`".$key."` as '".$key."'";
      }
         
      $sql = "CREATE VIEW grafana AS SELECT\n\t".implode(",\n\t", $k)."\nFROM\n\t`locations`,`datasets`\nWHERE\n";
      $sql .= "\t(\n";
      $sql .= "\t(`locations`.`location_type` = 'continent' AND `locations`.`continent_hash` = `datasets`.`continent_hash` AND `datasets`.`country_hash` IS NULL AND `datasets`.`state_hash` IS NULL AND `datasets`.`district_hash` IS NULL AND `datasets`.`location_hash` IS NULL)\n";
      $sql .= "\tOR\n";
      $sql .= "\t(`locations`.`location_type` = 'country' AND `locations`.`continent_hash` = `datasets`.`continent_hash` AND `locations`.`country_hash` = `datasets`.`country_hash` AND `datasets`.`state_hash` IS NULL AND `datasets`.`district_hash` IS NULL AND `datasets`.`location_hash` IS NULL)\n";
      $sql .= "\tOR\n";
      $sql .= "\t(`locations`.`location_type` = 'state' AND `locations`.`continent_hash` = `datasets`.`continent_hash` AND `locations`.`country_hash` = `datasets`.`country_hash` AND `locations`.`state_hash` = `datasets`.`state_hash` AND `datasets`.`district_hash` IS NULL AND `datasets`.`location_hash` IS NULL)\n";
      $sql .= "\tOR\n";
      $sql .= "\t(`locations`.`location_type` = 'district' AND `locations`.`continent_hash` = `datasets`.`continent_hash` AND `locations`.`country_hash` = `datasets`.`country_hash` AND `locations`.`state_hash` = `datasets`.`state_hash` AND `locations`.`district_hash` = `datasets`.`district_hash` AND `datasets`.`location_hash` IS NULL)\n";
      $sql .= "\tOR\n";
      $sql .= "\t(`locations`.`location_type` = 'location' AND `locations`.`continent_hash` = `datasets`.`continent_hash` AND `locations`.`country_hash` = `datasets`.`country_hash` AND `locations`.`state_hash` = `datasets`.`state_hash` AND `locations`.`district_hash` = `datasets`.`district_hash` AND `locations`.`location_hash` = `datasets`.`location_hash`)\n";
      $sql .= "\t)\n";
      $sql .= "ORDER BY `datasets`.`timestamp_represent` DESC\n";
      
      return $sql;
    }
}
