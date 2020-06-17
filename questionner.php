<?php # -*- coding: utf-8 -*-
declare( strict_types = 1 );

/**
 * Plugin Name: Questionner Coverdeal
 * Plugin URI:  TODO
 * Description: This plugin provides questionner functionality.
 * Version:     dev-master
 * Author:      Leverate team
 */

use Questionner\SetUp;

require_once dirname( __FILE__ )  . '/vendor/autoload.php';

const ASCORE = "new_appropriatenessscore";
const AML = "new_scoreresult";
const TMSCORE = "new_tmscore";
const CSCORE = "new_countryrisk";
const ECONOMICRISK = "new_economicprofilerisk";

$acceptedCountires = [
    "Austria" => 39.6,
    "Bulgaria" => 41.1,
    "Croatia" => 40.5,
    "Cyprus" => 40.7,
    "Czech Republic" => 40.6,
    "Denmark" => 35.8,
    "Estonia" => 36.7,
    "Finland" => 33.2,
    "France" => 33.2,
    "Germany" => 39.0,
    "Greece" => 43.8,
    "Hungary" => 40.4,
    "Iceland" => 37.3,
    "Ireland" => 37.8,
    "Italy" => 43.1,
    "Latvia" => 41.4,
    "Liechtenstein" => 51.8,
    "Lithuania" => 38.6,
    "Luxembourg" => 39.8,
    "Malta" => 39.8,
    "Netherlands" => 37.6,
    "Poland" => 38.9,
    "Portugal" => 39.0,
    "Romania" => 41.8,
    "Slovakia" => 41.3,
    "Slovenia" => 38.3,
    "Spain" => 41.1,
    "Sweden" => 35.7,
];

define ("ACCEPTED", serialize($acceptedCountires));


new SetUp();