#!/usr/local/bin/php
<?php

#  <xbar.title>Environment Canada weather</xbar.title>
#  <xbar.version>v2.1</xbar.version>

define( 'CURRENT_VERSION', 'v2.1' );
define( 'GITHUB_RAW_URL', 'https://raw.githubusercontent.com/lschuyler/xbar-envcan-weather/master/envcan.15m.php' );
define( 'GITHUB_REPO_URL', 'https://github.com/lschuyler/xbar-envcan-weather' );

/**
 * Check for updates once per day
 * Returns the latest version if an update is available, false otherwise
 */
function check_for_update() {
	$cache_file = sys_get_temp_dir() . '/envcan-update-check.json';
	$cache_ttl  = 86400; // 24 hours

	// Check cache first
	if ( file_exists( $cache_file ) ) {
		$cache = json_decode( file_get_contents( $cache_file ), true );
		if ( $cache && isset( $cache['timestamp'] ) && ( time() - $cache['timestamp'] ) < $cache_ttl ) {
			return $cache['update_available'] ? $cache['latest_version'] : false;
		}
	}

	// Fetch latest version from GitHub
	$context = stream_context_create( array(
		'http' => array( 'timeout' => 5 )
	) );
	$remote_content = @file_get_contents( GITHUB_RAW_URL, false, $context );

	if ( $remote_content === false ) {
		return false; // Network error, skip update check
	}

	// Extract version from remote script
	if ( preg_match( '/<xbar\.version>(v[\d.]+)<\/xbar\.version>/', $remote_content, $matches ) ) {
		$latest_version = $matches[1];
		$update_available = version_compare(
			str_replace( 'v', '', $latest_version ),
			str_replace( 'v', '', CURRENT_VERSION ),
			'>'
		);

		// Cache the result
		file_put_contents( $cache_file, json_encode( array(
			'timestamp'        => time(),
			'latest_version'   => $latest_version,
			'update_available' => $update_available
		) ) );

		return $update_available ? $latest_version : false;
	}

	return false;
}

$update_available = check_for_update();
#  <xbar.author>Lisa Schuyler</xbar.author>
#  <xbar.author.github>lschuyler</xbar.author.github>
#  <xbar.desc>Displays the weather from Environment Canada for your specified Canadian location.</xbar.desc>
#  <xbar.dependencies>php</xbar.dependencies>

// xbar variables
#  <xbar.var>select(VAR_LANGUAGE="English"): Language. [English, French]</xbar.var>
#  <xbar.var>string(VAR_COORDS="43.643,-79.394"): Coordinates as latitude,longitude (example 43.643,-79.394 for Toronto).</xbar.var>
#  <xbar.var>select(VAR_ICONS="Plain"): Icons. [Colour, Plain, None]</xbar.var>

// let's get the user preferences (with defaults if vars file doesn't exist yet):
$vars_file  = __FILE__ . ".vars.json";
$vars_array = file_exists( $vars_file ) ? json_decode( file_get_contents( $vars_file ), true ) : array();

// check if user has old region code format and needs to update settings
if ( isset( $vars_array['VAR_REGION'] ) && ! isset( $vars_array['VAR_COORDS'] ) ) {
	echo "Update Required\n";
	echo "---\n";
	echo "Environment Canada changed their API.\n";
	echo "Please update your plugin settings:\n";
	echo "1. Open xbar plugin settings\n";
	echo "2. Enter your coordinates (lat,lon)\n";
	echo "3. Example: 43.643,-79.394 for Toronto\n";
	echo "---\n";
	echo "Find coordinates at weather.gc.ca | href=https://weather.gc.ca | color=blue\n";
	exit;
}

$user_pref  = array(
	"language" => $vars_array['VAR_LANGUAGE'] ?? "English",
	"coords"   => strip_tags( $vars_array['VAR_COORDS'] ?? "43.643,-79.394" ),
	"icons"    => $vars_array['VAR_ICONS'] ?? "Plain"
);

// parse coordinates - expected format: "lat,lon" (e.g., "43.643,-79.394")
$coords_parts = explode( ',', $user_pref['coords'] );
if ( count( $coords_parts ) !== 2 ) {
	exit( 'Error: Invalid coordinates format. Use latitude,longitude (e.g., 43.643,-79.394)' );
}
$latitude  = trim( $coords_parts[0] );
$longitude = trim( $coords_parts[1] );

if ( ! is_numeric( $latitude ) || ! is_numeric( $longitude ) ) {
	exit( 'Error: Coordinates must be numeric values (e.g., 43.643,-79.394)' );
}

if ( $user_pref['language'] == "French" ) {
	$lang_short = "f";
	$envcan_url = "meteo";
	$link_text  = "Cliquez pour les prévisions complètes et les détails";
} else {
	$lang_short = "e";
	$envcan_url = "weather";
	$link_text  = "Click for full forecast & details";
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
		"thunderstorm"         => "ϟ",
		"rain"                 => "☂",
		"showers"              => "☂",
		"cloud"                => "☁",
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
		"thunderstorm"         => "⛈",
		"rain"                 => "🌧",
		"showers"              => "🌧",
		"cloud"                => "🌥",
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

$ec_url   = 'https://' . $envcan_url . '.gc.ca/rss/weather/' . $latitude . '_' . $longitude . '_' . $lang_short . '.xml';
$xml_data = @file_get_contents( $ec_url );

$current_conditions = '';
$observations       = '';
$forecast           = 'Forecast: \n';
$ec_link            = '';

// check for file failure
if ( $xml_data === false ) {
	exit( 'Error retrieving data - check coordinates. ' . $ec_url );
} else {
	$xml = new SimpleXMLElement( $xml_data );
}

if ( $user_pref['language'] == "English" ) {
	foreach ( $xml->entry as $weather ) {
		if ( $weather->category['term'] == "Warnings and Watches" ) {
			if ( $weather->summary != "No watches or warnings in effect." ) {
				$current_conditions .= "⚠ " . strtok( $weather->title, ',' ) . " ";
			}

		} elseif ( $weather->category['term'] == "Current Conditions" ) {
			$current_conditions .= str_replace( "Current Conditions: ", '', add_icons( $weather->title, $weather_icons ) );
			$observations       .= strip_tags( $weather->summary );
			// get link for full weather for click link
			if ( ! $ec_link ) {
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
				$current_conditions .= "⚠ " . strtok( $weather->title, ',' ) . " ";
			}

		} elseif ( $weather->category['term'] == "Conditions actuelles" ) {
			$current_conditions .= str_replace( "Conditions actuelles: ", '', add_icons( $weather->title, $weather_icons ) );
			$observations       .= strip_tags( $weather->summary );
			// get link for full weather for click link
			if ( ! $ec_link ) {
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

// backup in case the link wasn't set - use coordinate-based web URL
if ( ! $ec_link ) {
	$ec_link = 'https://' . $envcan_url . '.gc.ca/en/location/index.html?coords=' . $latitude . ',' . $longitude;
}

$observations = str_replace( "&deg;", "°", $observations );
echo $current_conditions;
echo "\n---\n";
echo $observations . "\n";
echo $forecast;
echo $link_text . " | href=" . $ec_link . " | color=blue\n";

// Show update notification if available
if ( $update_available ) {
	echo "---\n";
	echo "⬆ Update available: " . $update_available . " | color=orange\n";
	echo "Download update | href=" . GITHUB_REPO_URL . " | color=blue\n";
}
