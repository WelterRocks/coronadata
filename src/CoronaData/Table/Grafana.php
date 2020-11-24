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

    // Location table
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
    protected $location_id = null;
    protected $location_hash = null;
    protected $location_name = null;
    protected $location_type = null;
    protected $location_tags = null;
    protected $geo_id = null;
    protected $population_year = null;
    protected $population_count = null;
    protected $population_females = null;
    protected $population_males = null;
    protected $population_density = null;
    protected $area = null;

    // Dataset table
    protected $dataset_hash = null;
    protected $dataset_gender = null;

    protected $date_rep = null;
    protected $day_of_week = null;
    protected $day = null;
    protected $month = null;
    protected $year = null;

    protected $cases_rate = null;
    protected $cases_new = null;
    protected $cases_count = null;
    protected $cases_delta = null;
    protected $cases_today = null;
    protected $cases_yesterday = null;
    protected $cases_total = null;
    protected $cases_pointer = null;

    protected $cases_4day_average = null;
    protected $cases_7day_average = null;
    protected $cases_14day_average = null;

    protected $cases_new_agegroup_0_4 = null;
    protected $cases_new_agegroup_5_14 = null;
    protected $cases_new_agegroup_15_34 = null;
    protected $cases_new_agegroup_35_59 = null;
    protected $cases_new_agegroup_60_79 = null;
    protected $cases_new_agegroup_80_plus = null;
    protected $cases_new_agegroup_unknown = null;

    protected $cases_count_agegroup_0_4 = null;
    protected $cases_count_agegroup_5_14 = null;
    protected $cases_count_agegroup_15_34 = null;
    protected $cases_count_agegroup_35_59 = null;
    protected $cases_count_agegroup_60_79 = null;
    protected $cases_count_agegroup_80_plus = null;
    protected $cases_count_agegroup_unknown = null;

    protected $cases_delta_agegroup_0_4 = null;
    protected $cases_delta_agegroup_5_14 = null;
    protected $cases_delta_agegroup_15_34 = null;
    protected $cases_delta_agegroup_35_59 = null;
    protected $cases_delta_agegroup_60_79 = null;
    protected $cases_delta_agegroup_80_plus = null;
    protected $cases_delta_agegroup_unknown = null;

    protected $cases_today_agegroup_0_4 = null;
    protected $cases_today_agegroup_5_14 = null;
    protected $cases_today_agegroup_15_34 = null;
    protected $cases_today_agegroup_35_59 = null;
    protected $cases_today_agegroup_60_79 = null;
    protected $cases_today_agegroup_80_plus = null;
    protected $cases_today_agegroup_unknown = null;

    protected $cases_yesterday_agegroup_0_4 = null;
    protected $cases_yesterday_agegroup_5_14 = null;
    protected $cases_yesterday_agegroup_15_34 = null;
    protected $cases_yesterday_agegroup_35_59 = null;
    protected $cases_yesterday_agegroup_60_79 = null;
    protected $cases_yesterday_agegroup_80_plus = null;
    protected $cases_yesterday_agegroup_unknown = null;

    protected $cases_total_agegroup_0_4 = null;
    protected $cases_total_agegroup_5_14 = null;
    protected $cases_total_agegroup_15_34 = null;
    protected $cases_total_agegroup_35_59 = null;
    protected $cases_total_agegroup_60_79 = null;
    protected $cases_total_agegroup_80_plus = null;
    protected $cases_total_agegroup_unknown = null;

    protected $cases_pointer_agegroup_0_4 = null;
    protected $cases_pointer_agegroup_5_14 = null;
    protected $cases_pointer_agegroup_15_34 = null;
    protected $cases_pointer_agegroup_35_59 = null;
    protected $cases_pointer_agegroup_60_79 = null;
    protected $cases_pointer_agegroup_80_plus = null;
    protected $cases_pointer_agegroup_unknown = null;

    protected $deaths_rate = null;
    protected $deaths_new = null;
    protected $deaths_count = null;
    protected $deaths_delta = null;
    protected $deaths_today = null;
    protected $deaths_yesterday = null;
    protected $deaths_total = null;
    protected $deaths_pointer = null;

    protected $deaths_4day_average = null;
    protected $deaths_7day_average = null;
    protected $deaths_14day_average = null;

    protected $deaths_new_agegroup_0_4 = null;
    protected $deaths_new_agegroup_5_14 = null;
    protected $deaths_new_agegroup_15_34 = null;
    protected $deaths_new_agegroup_35_59 = null;
    protected $deaths_new_agegroup_60_79 = null;
    protected $deaths_new_agegroup_80_plus = null;
    protected $deaths_new_agegroup_unknown = null;

    protected $deaths_count_agegroup_0_4 = null;
    protected $deaths_count_agegroup_5_14 = null;
    protected $deaths_count_agegroup_15_34 = null;
    protected $deaths_count_agegroup_35_59 = null;
    protected $deaths_count_agegroup_60_79 = null;
    protected $deaths_count_agegroup_80_plus = null;
    protected $deaths_count_agegroup_unknown = null;

    protected $deaths_delta_agegroup_0_4 = null;
    protected $deaths_delta_agegroup_5_14 = null;
    protected $deaths_delta_agegroup_15_34 = null;
    protected $deaths_delta_agegroup_35_59 = null;
    protected $deaths_delta_agegroup_60_79 = null;
    protected $deaths_delta_agegroup_80_plus = null;
    protected $deaths_delta_agegroup_unknown = null;

    protected $deaths_today_agegroup_0_4 = null;
    protected $deaths_today_agegroup_5_14 = null;
    protected $deaths_today_agegroup_15_34 = null;
    protected $deaths_today_agegroup_35_59 = null;
    protected $deaths_today_agegroup_60_79 = null;
    protected $deaths_today_agegroup_80_plus = null;
    protected $deaths_today_agegroup_unknown = null;

    protected $deaths_yesterday_agegroup_0_4 = null;
    protected $deaths_yesterday_agegroup_5_14 = null;
    protected $deaths_yesterday_agegroup_15_34 = null;
    protected $deaths_yesterday_agegroup_35_59 = null;
    protected $deaths_yesterday_agegroup_60_79 = null;
    protected $deaths_yesterday_agegroup_80_plus = null;
    protected $deaths_yesterday_agegroup_unknown = null;

    protected $deaths_total_agegroup_0_4 = null;
    protected $deaths_total_agegroup_5_14 = null;
    protected $deaths_total_agegroup_15_34 = null;
    protected $deaths_total_agegroup_35_59 = null;
    protected $deaths_total_agegroup_60_79 = null;
    protected $deaths_total_agegroup_80_plus = null;
    protected $deaths_total_agegroup_unknown = null;

    protected $deaths_pointer_agegroup_0_4 = null;
    protected $deaths_pointer_agegroup_5_14 = null;
    protected $deaths_pointer_agegroup_15_34 = null;
    protected $deaths_pointer_agegroup_35_59 = null;
    protected $deaths_pointer_agegroup_60_79 = null;
    protected $deaths_pointer_agegroup_80_plus = null;
    protected $deaths_pointer_agegroup_unknown = null;

    protected $recovered_rate = null;
    protected $recovered_new = null;
    protected $recovered_count = null;
    protected $recovered_delta = null;
    protected $recovered_today = null;
    protected $recovered_yesterday = null;
    protected $recovered_total = null;
    protected $recovered_pointer = null;

    protected $recovered_4day_average = null;
    protected $recovered_7day_average = null;
    protected $recovered_14day_average = null;

    protected $recovered_new_agegroup_0_4 = null;
    protected $recovered_new_agegroup_5_14 = null;
    protected $recovered_new_agegroup_15_34 = null;
    protected $recovered_new_agegroup_35_59 = null;
    protected $recovered_new_agegroup_60_79 = null;
    protected $recovered_new_agegroup_80_plus = null;
    protected $recovered_new_agegroup_unknown = null;

    protected $recovered_count_agegroup_0_4 = null;
    protected $recovered_count_agegroup_5_14 = null;
    protected $recovered_count_agegroup_15_34 = null;
    protected $recovered_count_agegroup_35_59 = null;
    protected $recovered_count_agegroup_60_79 = null;
    protected $recovered_count_agegroup_80_plus = null;
    protected $recovered_count_agegroup_unknown = null;

    protected $recovered_delta_agegroup_0_4 = null;
    protected $recovered_delta_agegroup_5_14 = null;
    protected $recovered_delta_agegroup_15_34 = null;
    protected $recovered_delta_agegroup_35_59 = null;
    protected $recovered_delta_agegroup_60_79 = null;
    protected $recovered_delta_agegroup_80_plus = null;
    protected $recovered_delta_agegroup_unknown = null;

    protected $recovered_today_agegroup_0_4 = null;
    protected $recovered_today_agegroup_5_14 = null;
    protected $recovered_today_agegroup_15_34 = null;
    protected $recovered_today_agegroup_35_59 = null;
    protected $recovered_today_agegroup_60_79 = null;
    protected $recovered_today_agegroup_80_plus = null;
    protected $recovered_today_agegroup_unknown = null;

    protected $recovered_yesterday_agegroup_0_4 = null;
    protected $recovered_yesterday_agegroup_5_14 = null;
    protected $recovered_yesterday_agegroup_15_34 = null;
    protected $recovered_yesterday_agegroup_35_59 = null;
    protected $recovered_yesterday_agegroup_60_79 = null;
    protected $recovered_yesterday_agegroup_80_plus = null;
    protected $recovered_yesterday_agegroup_unknown = null;

    protected $recovered_total_agegroup_0_4 = null;
    protected $recovered_total_agegroup_5_14 = null;
    protected $recovered_total_agegroup_15_34 = null;
    protected $recovered_total_agegroup_35_59 = null;
    protected $recovered_total_agegroup_60_79 = null;
    protected $recovered_total_agegroup_80_plus = null;
    protected $recovered_total_agegroup_unknown = null;

    protected $recovered_pointer_agegroup_0_4 = null;
    protected $recovered_pointer_agegroup_5_14 = null;
    protected $recovered_pointer_agegroup_15_34 = null;
    protected $recovered_pointer_agegroup_35_59 = null;
    protected $recovered_pointer_agegroup_60_79 = null;
    protected $recovered_pointer_agegroup_80_plus = null;
    protected $recovered_pointer_agegroup_unknown = null;

    protected $timestamp_represent = null;

    protected $incidence_4day = null;
    protected $incidence_7day = null;
    protected $incidence_14day = null;
    protected $incidence_4day_smoothed = null;
    protected $incidence_7day_smoothed = null;
    protected $incidence_14day_smoothed = null;
    protected $incidence2_4day = null;
    protected $incidence2_7day = null;
    protected $incidence2_14day = null;
    protected $incidence2_4day_smoothed = null;
    protected $incidence2_7day_smoothed = null;
    protected $incidence2_14day_smoothed = null;

    protected $exponence_4day = null;
    protected $exponence_7day = null;
    protected $exponence_14day = null;
    protected $exponence_4day_smoothed = null;
    protected $exponence_7day_smoothed = null;
    protected $exponence_14day_smoothed = null;

    protected $reproduction_4day = null;
    protected $reproduction_7day = null;
    protected $reproduction_14day = null;

    protected $alert_condition_4day = null;
    protected $alert_condition_7day = null;
    protected $alert_condition_14day = null;
    protected $alert_condition_pointer = null;
    protected $alert_condition = null;

    protected $alert_condition2_4day = null;
    protected $alert_condition2_7day = null;
    protected $alert_condition2_14day = null;
    protected $alert_condition2_pointer = null;
    protected $alert_condition2 = null;

    protected $divi_cases_covid = null;
    protected $divi_cases_covid_ventilated = null;
    protected $divi_reporting_areas = null;
    protected $divi_locations_count = null;
    protected $divi_beds_free = null;
    protected $divi_beds_occupied = null;
    protected $divi_beds_total = null;

    protected $nowcast_esteem_new_diseases = null;
    protected $nowcast_lower_new_diseases = null;
    protected $nowcast_upper_new_diseases = null;
    protected $nowcast_esteem_new_diseases_ma4 = null;
    protected $nowcast_lower_new_diseases_ma4 = null;
    protected $nowcast_upper_new_diseases_ma4 = null;
    protected $nowcast_esteem_reproduction_r = null;
    protected $nowcast_lower_reproduction_r = null;
    protected $nowcast_upper_reproduction_r = null;
    protected $nowcast_esteem_7day_r_value = null;
    protected $nowcast_lower_7day_r_value = null;
    protected $nowcast_upper_7day_r_value = null;

    protected $flag_casted_r_values = null;
    protected $flag_has_divi = null;
    protected $flag_case_free = null;
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
    
    // Generic fields from locations
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
          case "location_type":
            $k[$key] = "`locations`.`".$key."` as '".$key."'";
            continue(2);
          case "flag_updated":
          case "flag_deleted":
          case "flag_disabled":
            $k[$key] = "(`locations`.`".$key."` | `datasets`.`".$key."`) as '".$key."'";
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
            $k["location_".$key] = "`locations`.`".$key."` as 'locations_".$key."'";
            $k[$key] = "`datasets`.`".$key."` as '".$key."'";
            continue(2);
        }
                
        $k[$key] = "`".$prefix."`.`".$key."` as '".$key."'";
      }
         
      $sql = "CREATE VIEW grafana AS SELECT\n\t".implode(",\n\t", $k)."\nFROM\n\t`locations`,`datasets`\nWHERE\n";
      $sql .= "\t(`locations`.`uid` = `datasets`.`locations_uid`)\n";
      $sql .= "ORDER BY `datasets`.`timestamp_represent` DESC;";

      return $sql;
    }
}
