<?php
// example-data.json

$json = file_get_contents("example-data.json");
$jsonData = rtrim($json, "\0");
$data = json_decode($jsonData);

echo '.';
echo 'DATA: ';

echo (var_dump($data));


foreach ($data as $record) {
    echo "Recordx -------------: ";
    echo var_dump($record);
    echo "Command: ".$record->command;
}/*
    $zoneName = $record->zone->name;
    $recordsTTL = $record->zone->soa->ttl;
    switch ($record->command) {
        
        case 'create':
        case 'update':
            echo "CREATE OR UPDATE COMMAND";
            // If zone does not exist, create it
            // check_create_zone($api_url,$safedns_domains,$zoneName);

             
            //  For records in zone:
            //     - Check if record is present in safedns
            //     - If record is not present, create it.
            //     - If record is present, check if it's changed ,and update if yes
            //     - If record is present in safedns, but has been removed from plesk, delete it from SafeDNS
        case 'delete':
            echo "DELETE COMMAND";
             // Delete a zone
             //call_SafeDNS_API('DELETE',$api_url."/zones/".$zoneName,false);
              
    }
}
*/
