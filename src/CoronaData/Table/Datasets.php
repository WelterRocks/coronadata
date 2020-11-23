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

class Datasets extends Base
{
    protected $locations_uid = null;
    protected $location_type = null;

    protected $dataset_hash = null;

    protected $location_hash = null;
    protected $district_hash = null;
    protected $state_hash = null;
    protected $country_hash = null;
    protected $continent_hash = null;

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

    protected $exponence_yesterday = null;
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
    
    protected function get_install_sql()
    {
      return "CREATE TABLE IF NOT EXISTS `datasets` (
        `uid` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
        `locations_uid` bigint UNSIGNED NULL DEFAULT NULL,
        `location_type` ENUM('continent','country','state','district','location') NOT NULL DEFAULT 'location',
        `timestamp_last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        `timestamp_registration` timestamp NOT NULL,
        `timestamp_deleted` timestamp NULL DEFAULT NULL,
        `timestamp_undeleted` timestamp NULL DEFAULT NULL,
        `timestamp_disabled` timestamp NULL DEFAULT NULL,
        `timestamp_enabled` timestamp NULL DEFAULT NULL,
        `timestamp_represent` timestamp NULL DEFAULT NULL,
        `date_rep` DATE NULL DEFAULT NULL,
        `day_of_week` smallint UNSIGNED NOT NULL DEFAULT '0',
        `day` smallint UNSIGNED NOT NULL DEFAULT '0',
        `month` smallint UNSIGNED NOT NULL DEFAULT '0',
        `year` YEAR NOT NULL DEFAULT '0',
        `dataset_hash` VARCHAR(32) NULL DEFAULT NULL,
        `continent_hash` VARCHAR(32) NULL DEFAULT NULL,
        `country_hash` VARCHAR(32) NULL DEFAULT NULL,
        `state_hash` VARCHAR(32) NULL DEFAULT NULL,
        `district_hash` VARCHAR(32) NULL DEFAULT NULL,
        `location_hash` VARCHAR(32) NULL DEFAULT NULL,
        `cases_rate` float NOT NULL DEFAULT '0',
        `cases_new` int NOT NULL DEFAULT '0',
        `cases_count` int NOT NULL DEFAULT '0',
        `cases_delta` int NOT NULL DEFAULT '0',
        `cases_today` int NOT NULL DEFAULT '0',
        `cases_yesterday` int NOT NULL DEFAULT '0',
        `cases_total` int NOT NULL DEFAULT '0',
        `cases_pointer` ENUM('asc','desc','sty') NOT NULL DEFAULT 'sty',   
        `cases_4day_average` FLOAT NOT NULL DEFAULT '0',
        `cases_7day_average` FLOAT NOT NULL DEFAULT '0',
        `cases_14day_average` FLOAT NOT NULL DEFAULT '0',    
        `cases_new_agegroup_0_4` int NOT NULL DEFAULT '0',
        `cases_new_agegroup_5_14` int NOT NULL DEFAULT '0',
        `cases_new_agegroup_15_34` int NOT NULL DEFAULT '0',
        `cases_new_agegroup_35_59` int NOT NULL DEFAULT '0',
        `cases_new_agegroup_60_79` int NOT NULL DEFAULT '0',
        `cases_new_agegroup_80_plus` int NOT NULL DEFAULT '0',
        `cases_new_agegroup_unknown` int NOT NULL DEFAULT '0',
        `cases_count_agegroup_0_4` int NOT NULL DEFAULT '0',
        `cases_count_agegroup_5_14` int NOT NULL DEFAULT '0',
        `cases_count_agegroup_15_34` int NOT NULL DEFAULT '0',
        `cases_count_agegroup_35_59` int NOT NULL DEFAULT '0',
        `cases_count_agegroup_60_79` int NOT NULL DEFAULT '0',
        `cases_count_agegroup_80_plus` int NOT NULL DEFAULT '0',
        `cases_count_agegroup_unknown` int NOT NULL DEFAULT '0',
        `cases_delta_agegroup_0_4` int NOT NULL DEFAULT '0',
        `cases_delta_agegroup_5_14` int NOT NULL DEFAULT '0',
        `cases_delta_agegroup_15_34` int NOT NULL DEFAULT '0',
        `cases_delta_agegroup_35_59` int NOT NULL DEFAULT '0',
        `cases_delta_agegroup_60_79` int NOT NULL DEFAULT '0',
        `cases_delta_agegroup_80_plus` int NOT NULL DEFAULT '0',
        `cases_delta_agegroup_unknown` int NOT NULL DEFAULT '0',
        `cases_today_agegroup_0_4` int NOT NULL DEFAULT '0',
        `cases_today_agegroup_5_14` int NOT NULL DEFAULT '0',
        `cases_today_agegroup_15_34` int NOT NULL DEFAULT '0',
        `cases_today_agegroup_35_59` int NOT NULL DEFAULT '0',
        `cases_today_agegroup_60_79` int NOT NULL DEFAULT '0',
        `cases_today_agegroup_80_plus` int NOT NULL DEFAULT '0',
        `cases_today_agegroup_unknown` int NOT NULL DEFAULT '0',
        `cases_yesterday_agegroup_0_4` int NOT NULL DEFAULT '0',
        `cases_yesterday_agegroup_5_14` int NOT NULL DEFAULT '0',
        `cases_yesterday_agegroup_15_34` int NOT NULL DEFAULT '0',
        `cases_yesterday_agegroup_35_59` int NOT NULL DEFAULT '0',
        `cases_yesterday_agegroup_60_79` int NOT NULL DEFAULT '0',
        `cases_yesterday_agegroup_80_plus` int NOT NULL DEFAULT '0',
        `cases_yesterday_agegroup_unknown` int NOT NULL DEFAULT '0',
        `cases_total_agegroup_0_4` int NOT NULL DEFAULT '0',
        `cases_total_agegroup_5_14` int NOT NULL DEFAULT '0',
        `cases_total_agegroup_15_34` int NOT NULL DEFAULT '0',
        `cases_total_agegroup_35_59` int NOT NULL DEFAULT '0',
        `cases_total_agegroup_60_79` int NOT NULL DEFAULT '0',
        `cases_total_agegroup_80_plus` int NOT NULL DEFAULT '0',
        `cases_total_agegroup_unknown` int NOT NULL DEFAULT '0',
        `cases_pointer_agegroup_0_4` ENUM('asc','desc','sty') NOT NULL DEFAULT 'sty',
        `cases_pointer_agegroup_5_14` ENUM('asc','desc','sty') NOT NULL DEFAULT 'sty',
        `cases_pointer_agegroup_15_34` ENUM('asc','desc','sty') NOT NULL DEFAULT 'sty',
        `cases_pointer_agegroup_35_59` ENUM('asc','desc','sty') NOT NULL DEFAULT 'sty',
        `cases_pointer_agegroup_60_79` ENUM('asc','desc','sty') NOT NULL DEFAULT 'sty',
        `cases_pointer_agegroup_80_plus` ENUM('asc','desc','sty') NOT NULL DEFAULT 'sty',
        `cases_pointer_agegroup_unknown` ENUM('asc','desc','sty') NOT NULL DEFAULT 'sty',
        `deaths_rate` float NOT NULL DEFAULT '0',
        `deaths_new` int NOT NULL DEFAULT '0',
        `deaths_count` int NOT NULL DEFAULT '0',
        `deaths_delta` int NOT NULL DEFAULT '0',
        `deaths_today` int NOT NULL DEFAULT '0',
        `deaths_yesterday` int NOT NULL DEFAULT '0',
        `deaths_total` int NOT NULL DEFAULT '0',
        `deaths_pointer` ENUM('asc','desc','sty') NOT NULL DEFAULT 'sty',
        `deaths_4day_average` FLOAT NOT NULL DEFAULT '0',
        `deaths_7day_average` FLOAT NOT NULL DEFAULT '0',
        `deaths_14day_average` FLOAT NOT NULL DEFAULT '0',
        `deaths_new_agegroup_0_4` int NOT NULL DEFAULT '0',
        `deaths_new_agegroup_5_14` int NOT NULL DEFAULT '0',
        `deaths_new_agegroup_15_34` int NOT NULL DEFAULT '0',
        `deaths_new_agegroup_35_59` int NOT NULL DEFAULT '0',
        `deaths_new_agegroup_60_79` int NOT NULL DEFAULT '0',
        `deaths_new_agegroup_80_plus` int NOT NULL DEFAULT '0',
        `deaths_new_agegroup_unknown` int NOT NULL DEFAULT '0',
        `deaths_count_agegroup_0_4` int NOT NULL DEFAULT '0',
        `deaths_count_agegroup_5_14` int NOT NULL DEFAULT '0',
        `deaths_count_agegroup_15_34` int NOT NULL DEFAULT '0',
        `deaths_count_agegroup_35_59` int NOT NULL DEFAULT '0',
        `deaths_count_agegroup_60_79` int NOT NULL DEFAULT '0',
        `deaths_count_agegroup_80_plus` int NOT NULL DEFAULT '0',
        `deaths_count_agegroup_unknown` int NOT NULL DEFAULT '0',
        `deaths_delta_agegroup_0_4` int NOT NULL DEFAULT '0',
        `deaths_delta_agegroup_5_14` int NOT NULL DEFAULT '0',
        `deaths_delta_agegroup_15_34` int NOT NULL DEFAULT '0',
        `deaths_delta_agegroup_35_59` int NOT NULL DEFAULT '0',
        `deaths_delta_agegroup_60_79` int NOT NULL DEFAULT '0',
        `deaths_delta_agegroup_80_plus` int NOT NULL DEFAULT '0',
        `deaths_delta_agegroup_unknown` int NOT NULL DEFAULT '0',
        `deaths_today_agegroup_0_4` int NOT NULL DEFAULT '0',
        `deaths_today_agegroup_5_14` int NOT NULL DEFAULT '0',
        `deaths_today_agegroup_15_34` int NOT NULL DEFAULT '0',
        `deaths_today_agegroup_35_59` int NOT NULL DEFAULT '0',
        `deaths_today_agegroup_60_79` int NOT NULL DEFAULT '0',
        `deaths_today_agegroup_80_plus` int NOT NULL DEFAULT '0',
        `deaths_today_agegroup_unknown` int NOT NULL DEFAULT '0',
        `deaths_yesterday_agegroup_0_4` int NOT NULL DEFAULT '0',
        `deaths_yesterday_agegroup_5_14` int NOT NULL DEFAULT '0',
        `deaths_yesterday_agegroup_15_34` int NOT NULL DEFAULT '0',
        `deaths_yesterday_agegroup_35_59` int NOT NULL DEFAULT '0',
        `deaths_yesterday_agegroup_60_79` int NOT NULL DEFAULT '0',
        `deaths_yesterday_agegroup_80_plus` int NOT NULL DEFAULT '0',
        `deaths_yesterday_agegroup_unknown` int NOT NULL DEFAULT '0',
        `deaths_total_agegroup_0_4` int NOT NULL DEFAULT '0',
        `deaths_total_agegroup_5_14` int NOT NULL DEFAULT '0',
        `deaths_total_agegroup_15_34` int NOT NULL DEFAULT '0',
        `deaths_total_agegroup_35_59` int NOT NULL DEFAULT '0',
        `deaths_total_agegroup_60_79` int NOT NULL DEFAULT '0',
        `deaths_total_agegroup_80_plus` int NOT NULL DEFAULT '0',
        `deaths_total_agegroup_unknown` int NOT NULL DEFAULT '0',
        `deaths_pointer_agegroup_0_4` ENUM('asc','desc','sty') NOT NULL DEFAULT 'sty',
        `deaths_pointer_agegroup_5_14` ENUM('asc','desc','sty') NOT NULL DEFAULT 'sty',
        `deaths_pointer_agegroup_15_34` ENUM('asc','desc','sty') NOT NULL DEFAULT 'sty',
        `deaths_pointer_agegroup_35_59` ENUM('asc','desc','sty') NOT NULL DEFAULT 'sty',
        `deaths_pointer_agegroup_60_79` ENUM('asc','desc','sty') NOT NULL DEFAULT 'sty',
        `deaths_pointer_agegroup_80_plus` ENUM('asc','desc','sty') NOT NULL DEFAULT 'sty',
        `deaths_pointer_agegroup_unknown` ENUM('asc','desc','sty') NOT NULL DEFAULT 'sty',
        `recovered_new` int NOT NULL DEFAULT '0',
        `recovered_count` int NOT NULL DEFAULT '0',
        `recovered_delta` int NOT NULL DEFAULT '0',
        `recovered_today` int NOT NULL DEFAULT '0',
        `recovered_yesterday` int NOT NULL DEFAULT '0',
        `recovered_total` int NOT NULL DEFAULT '0',
        `recovered_pointer` ENUM('asc','desc','sty') NOT NULL DEFAULT 'sty',
        `recovered_4day_average` FLOAT NOT NULL DEFAULT '0',
        `recovered_7day_average` FLOAT NOT NULL DEFAULT '0',
        `recovered_14day_average` FLOAT NOT NULL DEFAULT '0',
        `recovered_new_agegroup_0_4` int NOT NULL DEFAULT '0',
        `recovered_new_agegroup_5_14` int NOT NULL DEFAULT '0',
        `recovered_new_agegroup_15_34` int NOT NULL DEFAULT '0',
        `recovered_new_agegroup_35_59` int NOT NULL DEFAULT '0',
        `recovered_new_agegroup_60_79` int NOT NULL DEFAULT '0',
        `recovered_new_agegroup_80_plus` int NOT NULL DEFAULT '0',
        `recovered_new_agegroup_unknown` int NOT NULL DEFAULT '0',
        `recovered_count_agegroup_0_4` int NOT NULL DEFAULT '0',
        `recovered_count_agegroup_5_14` int NOT NULL DEFAULT '0',
        `recovered_count_agegroup_15_34` int NOT NULL DEFAULT '0',
        `recovered_count_agegroup_35_59` int NOT NULL DEFAULT '0',
        `recovered_count_agegroup_60_79` int NOT NULL DEFAULT '0',
        `recovered_count_agegroup_80_plus` int NOT NULL DEFAULT '0',
        `recovered_count_agegroup_unknown` int NOT NULL DEFAULT '0',
        `recovered_delta_agegroup_0_4` int NOT NULL DEFAULT '0',
        `recovered_delta_agegroup_5_14` int NOT NULL DEFAULT '0',
        `recovered_delta_agegroup_15_34` int NOT NULL DEFAULT '0',
        `recovered_delta_agegroup_35_59` int NOT NULL DEFAULT '0',
        `recovered_delta_agegroup_60_79` int NOT NULL DEFAULT '0',
        `recovered_delta_agegroup_80_plus` int NOT NULL DEFAULT '0',
        `recovered_delta_agegroup_unknown` int NOT NULL DEFAULT '0',
        `recovered_today_agegroup_0_4` int NOT NULL DEFAULT '0',
        `recovered_today_agegroup_5_14` int NOT NULL DEFAULT '0',
        `recovered_today_agegroup_15_34` int NOT NULL DEFAULT '0',
        `recovered_today_agegroup_35_59` int NOT NULL DEFAULT '0',
        `recovered_today_agegroup_60_79` int NOT NULL DEFAULT '0',
        `recovered_today_agegroup_80_plus` int NOT NULL DEFAULT '0',
        `recovered_today_agegroup_unknown` int NOT NULL DEFAULT '0',
        `recovered_yesterday_agegroup_0_4` int NOT NULL DEFAULT '0',
        `recovered_yesterday_agegroup_5_14` int NOT NULL DEFAULT '0',
        `recovered_yesterday_agegroup_15_34` int NOT NULL DEFAULT '0',
        `recovered_yesterday_agegroup_35_59` int NOT NULL DEFAULT '0',
        `recovered_yesterday_agegroup_60_79` int NOT NULL DEFAULT '0',
        `recovered_yesterday_agegroup_80_plus` int NOT NULL DEFAULT '0',
        `recovered_yesterday_agegroup_unknown` int NOT NULL DEFAULT '0',
        `recovered_total_agegroup_0_4` int NOT NULL DEFAULT '0',
        `recovered_total_agegroup_5_14` int NOT NULL DEFAULT '0',
        `recovered_total_agegroup_15_34` int NOT NULL DEFAULT '0',
        `recovered_total_agegroup_35_59` int NOT NULL DEFAULT '0',
        `recovered_total_agegroup_60_79` int NOT NULL DEFAULT '0',
        `recovered_total_agegroup_80_plus` int NOT NULL DEFAULT '0',
        `recovered_total_agegroup_unknown` int NOT NULL DEFAULT '0',
        `recovered_pointer_agegroup_0_4` ENUM('asc','desc','sty') NOT NULL DEFAULT 'sty',
        `recovered_pointer_agegroup_5_14` ENUM('asc','desc','sty') NOT NULL DEFAULT 'sty',
        `recovered_pointer_agegroup_15_34` ENUM('asc','desc','sty') NOT NULL DEFAULT 'sty',
        `recovered_pointer_agegroup_35_59` ENUM('asc','desc','sty') NOT NULL DEFAULT 'sty',
        `recovered_pointer_agegroup_60_79` ENUM('asc','desc','sty') NOT NULL DEFAULT 'sty',
        `recovered_pointer_agegroup_80_plus` ENUM('asc','desc','sty') NOT NULL DEFAULT 'sty',
        `recovered_pointer_agegroup_unknown` ENUM('asc','desc','sty') NOT NULL DEFAULT 'sty',
        `incidence_4day` FLOAT NOT NULL DEFAULT '0',
        `incidence_7day` FLOAT NOT NULL DEFAULT '0',
        `incidence_14day` FLOAT NOT NULL DEFAULT '0',
        `incidence_4day_smoothed` FLOAT NOT NULL DEFAULT '0',
        `incidence_7day_smoothed` FLOAT NOT NULL DEFAULT '0',
        `incidence_14day_smoothed` FLOAT NOT NULL DEFAULT '0',
        `incidence2_4day` FLOAT NOT NULL DEFAULT '0',
        `incidence2_7day` FLOAT NOT NULL DEFAULT '0',
        `incidence2_14day` FLOAT NOT NULL DEFAULT '0',
        `incidence2_4day_smoothed` FLOAT NOT NULL DEFAULT '0',
        `incidence2_7day_smoothed` FLOAT NOT NULL DEFAULT '0',
        `incidence2_14day_smoothed` FLOAT NOT NULL DEFAULT '0',
        `exponence_yesterday` FLOAT NOT NULL DEFAULT '0',
        `exponence_4day` FLOAT NOT NULL DEFAULT '0',
        `exponence_7day` FLOAT NOT NULL DEFAULT '0',
        `exponence_14day` FLOAT NOT NULL DEFAULT '0',
        `exponence_4day_smoothed` FLOAT NOT NULL DEFAULT '0',
        `exponence_7day_smoothed` FLOAT NOT NULL DEFAULT '0',
        `exponence_14day_smoothed` FLOAT NOT NULL DEFAULT '0',        
        `reproduction_4day` FLOAT NOT NULL DEFAULT '0',
        `reproduction_7day` FLOAT NOT NULL DEFAULT '0',
        `reproduction_14day` FLOAT NOT NULL DEFAULT '0',
        `alert_condition_4day` smallint NOT NULL DEFAULT '-1',
        `alert_condition_7day` smallint NOT NULL DEFAULT '-1',
        `alert_condition_14day` smallint NOT NULL DEFAULT '-1',
        `alert_condition` smallint NOT NULL DEFAULT '-1',
        `alert_condition_pointer` ENUM('asc','desc','sty') NOT NULL DEFAULT 'sty',
        `alert_condition2_4day` smallint NOT NULL DEFAULT '-1',
        `alert_condition2_7day` smallint NOT NULL DEFAULT '-1',
        `alert_condition2_14day` smallint NOT NULL DEFAULT '-1',
        `alert_condition2` smallint NOT NULL DEFAULT '-1',
        `alert_condition2_pointer` ENUM('asc','desc','sty') NOT NULL DEFAULT 'sty',
        `data_checksum` VARCHAR(40) NULL DEFAULT NULL,
        `update_count` int UNSIGNED NOT NULL DEFAULT '0',
        `flag_updated` tinyint(1) NOT NULL DEFAULT '0',
        `flag_disabled` tinyint(1) NOT NULL DEFAULT '0',
        `flag_deleted` tinyint(1) NOT NULL DEFAULT '0',
        `flag_case_free` tinyint(1) NOT NULL DEFAULT '0',
        `flag_enforce_daily_need_deliveries` tinyint(1) NOT NULL DEFAULT '0',
        `flag_enforce_treatment_priorization` tinyint(1) NOT NULL DEFAULT '0',
        `flag_lockdown_primary_infrastructure` tinyint(1) NOT NULL DEFAULT '0',
        `flag_isolate_executive_staff` tinyint(1) NOT NULL DEFAULT '0',
        `flag_enforce_federation_control` tinyint(1) NOT NULL DEFAULT '0',
        `flag_limit_fundamental_rights` tinyint(1) NOT NULL DEFAULT '0',
        `flag_lockdown_schools` tinyint(1) NOT NULL DEFAULT '0',
        `flag_lockdown_gastronomy` tinyint(1) NOT NULL DEFAULT '0',
        `flag_lockdown_secondary_infrastructure` tinyint(1) NOT NULL DEFAULT '0',
        `flag_enforce_local_crisis_team_control` tinyint(1) NOT NULL DEFAULT '0',
        `flag_enforce_gastronomy_rules` tinyint(1) NOT NULL DEFAULT '0',
        `flag_lockdown_leisure_activities` tinyint(1) NOT NULL DEFAULT '0',
        `flag_isolate_medium_risk_group` tinyint(1) NOT NULL DEFAULT '0',
        `flag_reserve_icu_units` tinyint(1) NOT NULL DEFAULT '0',
        `flag_enforce_shopping_rules` tinyint(1) NOT NULL DEFAULT '0',
        `flag_isolate_high_risk_group` tinyint(1) NOT NULL DEFAULT '0',
        `flag_general_caution` tinyint(1) NOT NULL DEFAULT '0',
        `flag_attention_on_symptoms` tinyint(1) NOT NULL DEFAULT '0',
        `flag_wash_hands` tinyint(1) NOT NULL DEFAULT '0',
        `flag_recommend_mask_wearing` tinyint(1) NOT NULL DEFAULT '0',
        `flag_enforce_critical_mask_wearing` tinyint(1) NOT NULL DEFAULT '0',
        `flag_enforce_public_mask_wearing` tinyint(1) NOT NULL DEFAULT '0',
        `flag_isolate_low_risk_group` tinyint(1) NOT NULL DEFAULT '0',
        `enforce_distance_meters` smallint NOT NULL DEFAULT '-1',
        `enforce_household_plus_persons_to` smallint NOT NULL DEFAULT '-1',
        `enforce_public_groups_to` smallint NOT NULL DEFAULT '-1',
        `enforce_public_events_to` smallint NOT NULL DEFAULT '-1',
        PRIMARY KEY (`uid`,`month`),
        UNIQUE KEY `dataset_hash` (`dataset_hash`,`month`),
        KEY `continent_hash` (`continent_hash`),
        KEY `country_hash` (`country_hash`),
        KEY `state_hash` (`state_hash`),
        KEY `district_hash` (`district_hash`),
        KEY `location_hash` (`location_hash`),
        KEY `location_type` (`location_type`),
        KEY `locations_uid` (`locations_uid`),
        KEY `data_checksum` (`data_checksum`)
      ) PARTITION BY RANGE ( `month` ) (
        PARTITION jan VALUES LESS THAN (2),
        PARTITION feb VALUES LESS THAN (3),
        PARTITION mar VALUES LESS THAN (4),
        PARTITION apr VALUES LESS THAN (5),
        PARTITION may VALUES LESS THAN (6),
        PARTITION jun VALUES LESS THAN (7),
        PARTITION jul VALUES LESS THAN (8),
        PARTITION aug VALUES LESS THAN (9),
        PARTITION sep VALUES LESS THAN (10),
        PARTITION oct VALUES LESS THAN (11),
        PARTITION nov VALUES LESS THAN (12),
        PARTITION `dec` VALUES LESS THAN MAXVALUE
      );
      
      ALTER TABLE `datasets` ADD CONSTRAINT `dataset_location` FOREIGN KEY (`locations_uid`) REFERENCES `locations`(`uid`) ON DELETE CASCADE ON UPDATE CASCADE;
      ";
    }
}


