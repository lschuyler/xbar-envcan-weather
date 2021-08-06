#!/usr/local/bin/php
<?php

#  <xbar.title>Environment Canada weather</xbar.title>
#  <xbar.version>v1.0</xbar.version>
#  <xbar.author>Lisa Schuyler</xbar.author>
#  <xbar.author.github>lschuyler</xbar.author.github>
#  <xbar.desc>Displays the weather from Environment Canada for your location.</xbar.desc>
#  <xbar.dependencies>php</xbar.dependencies>

$ec_url = 'https://weather.gc.ca/rss/city/yt-6_e.xml';
$xml_data = file_get_contents($ec_url);

$weather_object = new SimpleXMLElement($xml_data);

$xml = $weather_object;
$current_conditions = '';

foreach ($xml->entry as $weather) {
    if ( ( str_starts_with( $weather->title, 'SPECIAL') ) OR ( str_contains( $weather->title, 'WARNING') )  OR ( str_contains( $weather->title, 'WATCH') )) {
        if ( !str_contains( $current_conditions,'⚠' ) ) {
            $current_conditions .= "⚠ ";
        };
    }
    else if ( str_starts_with( $weather->title, 'Current Conditions: ') ) {
        $current_conditions .= trim($weather->title, "Current Conditions: ") . "\n---\n";
        // get link for full weather for click link
        foreach( $weather->link->attributes() as $name => $value ) {
            if ( $name = 'href' ) {
                $ec_link = $value;
            }
        }
        
    }
    else if ( ( str_starts_with( $weather->title, 'Sunday') ) OR ( str_starts_with( $weather->title, 'Monday') ) OR ( str_starts_with( $weather->title, 'Tuesday') ) OR ( str_starts_with( $weather->title, 'Wednesday') ) OR ( str_starts_with( $weather->title, 'Thursday') ) OR ( str_starts_with( $weather->title, 'Friday') ) OR ( str_starts_with( $weather->title, 'Saturday') ) ) {
        $current_conditions .=  $weather->title . "\n";
    }
    else {
        // what's left
        $current_conditions .=  $weather->title . "\n";

    }
}


echo $current_conditions;
echo "Click to view full details | href=" . $ec_link . " | color=blue ";