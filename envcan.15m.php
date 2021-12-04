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
	#  <xbar.var>string(VAR_REGION="YT-6"): Region Code (example YT-6).</xbar.var>
	#  <xbar.var>select(VAR_ICONS="Plain"): Icons. [Colour, Plain, None]</xbar.var>

	// let's get the user preferences:
	$json_vars  = file_get_contents( __FILE__ . ".vars.json" );
	$vars_array = json_decode( $json_vars, true );
	$user_pref  = array(
		"language" => $vars_array['VAR_LANGUAGE'],
		"region"   => strip_tags( $vars_array['VAR_REGION'] ),
		"icons"    => $vars_array['VAR_ICONS']
	);

	// region code should never be more than 6 characters:
	if ( strlen( $user_pref['region'] ) > 6 ) {
		$user_pref['region'] = substr( $user_pref['region'], 0, 6 );
	}

	if ( $user_pref['language'] == "English" ) {
		$lang_short = "e";
		$envcan_url = "weather";
		$link_text  = "Click for full forecast & details";
	} elseif ( $user_pref['language'] == "French" ) {
		$lang_short = "f";
		$envcan_url = "meteo";
		$link_text  = "Cliquez pour les prévisions complètes et les détails";
	}

	// add support for PHP < 8
	if ( ! function_exists( 'str_starts_with' ) ) {
		function str_starts_with( $haystack, $needle ) {
			return (string) $needle !== '' && strncmp( $haystack, $needle, strlen( $needle ) ) === 0;
		}
	}

	// add support for PHP < 8
	if ( ! function_exists( 'str_contains' ) ) {
		function str_contains( $haystack, $needle ) {
			return $needle !== '' && mb_strpos( $haystack, $needle ) !== false;
		}
	}


	if ( $user_pref["icons"] == "Plain" ) {
		$weather_icons = array(
			"snow"                 => "❄",
			"sunny"                => "☀",
			"rain"                 => "☂",
			"showers"              => "☂",
			"cloud"                => "☁",
			"thunderstorm"         => "ϟ",
			"smoke"                => "༄",
			"clear"                => "☾",
			"wind"                 => "༄",
			"fog"                  => "࿓",
			"ice"                  => "❅",
			"flurries"             => "❄",
			"mix of sun and cloud" => "☁"
		);
	} elseif ( $user_pref["icons"] == "Colour" ) {
		$weather_icons = array(
			"snow"                 => "🌨",
			"sunny"                => "🔆",
			"rain"                 => "🌧",
			"showers"              => "🌧",
			"cloud"                => "🌥",
			"thunderstorm"         => "⛈",
			"smoke"                => "🔥",
			"lightning"            => "🌩",
			"clear"                => "🌛",
			"wind"                 => "🌬",
			"fog"                  => "🌫",
			"ice"                  => "🧊",
			"flurries"             => "⛄",
			"mix of sun and cloud" => "🌤",
			"tornado"              => "🌪"
		);
	} else {
		$weather_icons = array();
	}

	function add_icons( $weather_text, $weather_icons ) {
		if ( empty( $weather_icons ) ) {
			return $weather_text;
		}
		foreach ( $weather_icons as $condition => $icon ) {
			if ( strpos( strtolower( $weather_text ), $condition ) !== false ) {
				return substr_replace( $weather_text, $icon . " ", 0, 0 );
			}
		}

		return $weather_text;
	}

	$ec_url   = 'https://' . $envcan_url . '.gc.ca/rss/city/' . strtolower( $user_pref["region"] ) . '_' . $lang_short . '.xml';
	$xml_data = @file_get_contents( $ec_url );

	$current_conditions = '';
	$observations       = '';
	$forecast           = 'Forecast: \n';

	// check for file failure
	if ( $xml_data === false ) {
		exit( 'Error retrieving data - check region code. ' . $ec_url );
	} else {
		$xml = new SimpleXMLElement( $xml_data );
	}

	if ( $user_pref['language'] == "English" ) {
		foreach ( $xml->entry as $weather ) {
			if ( $weather->category['term'] == "Warnings and Watches" ) {
				if ( $weather->summary != "No watches or warnings in effect." ) {
					$current_conditions .= "⚠ " . strtok( $weather->title, ',' );
				}

			} elseif ( $weather->category['term'] == "Current Conditions" ) {
				$current_conditions .= str_replace( "Current Conditions: ", '', add_icons( $weather->title, $weather_icons ) );
				$observations       .= strip_tags( $weather->summary );
				// get link for full weather for click link
				if ( ! isset ( $ec_link ) ) {
					foreach ( $weather->link->attributes() as $name => $value ) {
						if ( $name = 'href' ) {
							$ec_link = $value;
						}
					}
				}
			} elseif ( $weather->category["term"] == "Weather Forecasts" ) {
				$forecast .= add_icons( strip_tags( $weather->title ), $weather_icons ) . "\n";
			}
		}
	} elseif ( $user_pref['language'] == "French" ) {
		foreach ( $xml->entry as $weather ) {
			if ( $weather->category['term'] == "Veilles et avertissements" ) {
				if ( $weather->summary != "Aucune veille ou alerte en vigueur." ) {
					$current_conditions .= "⚠ " . $weather->summary . "\n";
				}

			} elseif ( $weather->category['term'] == "Conditions actuelles" ) {
				$current_conditions .= str_replace( "Conditions actuelles: ", '', add_icons( $weather->title, $weather_icons ) );
				$observations       .= strip_tags( $weather->summary );
				// get link for full weather for click link
				if ( ! isset ( $ec_link ) ) {
					foreach ( $weather->link->attributes() as $name => $value ) {
						if ( $name = 'href' ) {
							$ec_link = $value;
						}
					}
				}
			} elseif ( $weather->category['term'] == "Prévisions météo" ) {
				$forecast .= add_icons( strip_tags( $weather->title ), $weather_icons ) . "\n";
			}
		}
	}

	// backup in case the link wasn't set
	if ( ! $ec_link ) {
		$ec_link = $ec_url;
	}

	$observations = str_replace( "&deg;", "°", $observations );
	echo $current_conditions;
	echo "\n---\n";
	echo $observations . "\n";
	echo $forecast;
	echo $link_text . " | href=" . $ec_link . " | color=blue ";