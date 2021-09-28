#!/usr/local/bin/php
<?php

#  <xbar.title>Environment Canada weather</xbar.title>
#  <xbar.version>v1.0</xbar.version>
#  <xbar.author>Lisa Schuyler</xbar.author>
#  <xbar.author.github>lschuyler</xbar.author.github>
#  <xbar.desc>Displays the weather from Environment Canada for your specified Canadian location.</xbar.desc>
#  <xbar.dependencies>php</xbar.dependencies>

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

$ec_url = 'https://weather.gc.ca/rss/city/yt-6_e.xml';
$xml_data = file_get_contents( $ec_url );

$current_conditions = '';

// check for file failure
if ($xml_data === false) {
    $current_conditions = 'Error retrieving data';
}
else {
    $xml = new SimpleXMLElement( $xml_data );
}

foreach ($xml->entry as $weather) {
    if ( ( str_starts_with( $weather->title, 'SPECIAL') ) OR ( str_contains( $weather->title, 'WARNING') )  OR ( str_contains( $weather->title, 'WATCH') )) {
        // add notification for just the first alert
        if ( !str_contains( $current_conditions,'⚠' ) ) {
            $current_conditions .= "⚠ ". $weather->title . " - ";
        }
    }
    else if ( str_starts_with( $weather->title, 'No watches or warnings in effect' ) ) {
        // do nothing with this entry
    }
    else if ( str_starts_with( $weather->title, 'Current Conditions:') ) {
        $current_conditions .= str_replace("Current Conditions: ", '', $weather->title) . "\n---\n";
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