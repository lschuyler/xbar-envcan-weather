#!/usr/local/bin/php
<?php

	#  <xbar.title>Environment Canada weather</xbar.title>
	#  <xbar.version>v1.0</xbar.version>
	#  <xbar.author>Lisa Schuyler</xbar.author>
	#  <xbar.author.github>lschuyler</xbar.author.github>
	#  <xbar.desc>Displays the weather from Environment Canada for your specified Canadian location.</xbar.desc>
	#  <xbar.dependencies>php</xbar.dependencies>

	// xbar variables
	#  <xbar.var>select(VAR_LANGUAGE="English"): Language. [English, French]</xbar.var>
	#  <xbar.var>string(VAR_REGION="Region Code"): Region Code (example YT-6).</xbar.var>

	// let's get the user preferences:
	$json_vars = file_get_contents(__FILE__.".vars.json");
	$vars_array = json_decode($json_vars, true);
	$language = $vars_array['VAR_LANGUAGE'];
	$region = $vars_array['VAR_REGION'];

	if ( $language = "English" ) {
		$lang_short = "e";
		$envcan_url = "weather";
	} elseif ( $language = "French" ) {
		$lang_short = "f";
		$envcan_url = "mateo";
	}


	// add support for PHP < 8
	if ( !function_exists('str_starts_with' ) ) {
		function str_starts_with($haystack, $needle) {
			return (string)$needle !== '' && strncmp($haystack, $needle, strlen($needle)) === 0;
		}
	}

	// add support for PHP < 8
	if ( !function_exists('str_contains' ) ) {
		function str_contains($haystack, $needle) {
			return $needle !== '' && mb_strpos($haystack, $needle) !== false;
		}
	}

	$ec_url = 'https://'. $envcan_url .'.gc.ca/rss/city/'. strtolower($region) .'_' . $lang_short . '.xml';
	$xml_data = @file_get_contents( $ec_url );

	$current_conditions = '';

	// check for file failure
	if ($xml_data === FALSE) {
		exit('Error retrieving data - check region code.');
	}
	else {
		$xml = new SimpleXMLElement( $xml_data );
	}

	foreach ($xml->entry as $weather) {
		if ( ( str_starts_with( $weather->title, 'SPECIAL') ) OR ( str_starts_with( $weather->title, 'SPÉCIAL') ) OR
		     ( str_contains( $weather->title, 'WARNING') )  OR ( str_contains( $weather->title, 'ATTENTION') ) OR
		     ( str_contains( $weather->title, 'WATCH') ) OR ( str_contains( $weather->title, 'ALERTE') )) {
			// add notification for just the first alert
			if ( !str_contains( $current_conditions,'⚠' ) ) {
				$current_conditions .= "⚠ ". $weather->title . " - ";
			}
		}
		elseif ( ( str_starts_with( $weather->title, 'No watches or warnings in effect' ) ) OR ( str_starts_with( $weather->title, 'Aucune veille ou alerte en vigueur' ) ) ) {
			// do nothing with this entry
		}
		elseif ( ( str_starts_with( $weather->title, 'Current Conditions:') ) OR ( str_starts_with( $weather->title, 'Conditions actuelles:') ) ) {
			if ( $language = "English" ) {
				$current_conditions .= str_replace("Current Conditions: ", '', $weather->title);
			}
            elseif ( $language = "French" ) {
				$current_conditions .= str_replace("Conditions actuelles: ", '', $weather->title);
			}
			$current_conditions .= "\n---\n";
			// get link for full weather for click link
			if ( !isset ( $ec_link ) ) {
				foreach( $weather->link->attributes() as $name => $value ) {
					if ( $name = 'href' ) {
						$ec_link = $value;
					}
				}
			}
		}
		else {
			$current_conditions .=  $weather->title . "\n";
		}
	}

	if (!$ec_link) {
		$ec_link = $ec_url;
	}

	echo $current_conditions;
	echo "Click for full forecast & details | href=" . $ec_link . " | color=blue ";