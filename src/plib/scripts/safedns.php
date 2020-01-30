<?php
/**
 * This is the SafeDNS Integration Script
 *
 * Documentation Link
 */

// Delete function branch

/**
 * Read zone script from stdin
 *
 *[
 * {
 *  "command": "(update|delete)",
 *  "zone": {
 *      "name": "domain.tld.",
 *      "displayName": "domain.tld.",
 *      "soa": {
 *          "email": "email@address",
 *          "status": 0,
 *          "type": "master",
 *          "ttl": 86400,
 *          "refresh": 10800,
 *          "retry": 3600,
 *          "expire": 604800,
 *          "minimum": 10800,
 *          "serial": 123123123,
 *          "serial_format": "UNIXTIMESTAMP"
 *      },
 *      "rr": [{
 *          "host": "www.domain.tld.",
 *          "displayHost": "www.domain.tld.",
 *          "type": "CNAME",
 *          "displayValue": "domain.tld.",
 *          "opt": "",
 *          "value": "domain.tld."
 *      }]
 * }, {
 *  "command": "(createPTRs|deletePTRs)",
 *  "ptr": {
 *      "ip_address": "1.2.3.4",
 *      "hostname": "domain.tld"}
 * }
 *]
 */

/*

$data = json_decode(file_get_contents('php://stdin'));
//Example:
//[
//    {"command": "update", "zone": {"name": "domain.tld.", "displayName": "domain.tld.", "soa": {"email": "amihailov@parallels.com", "status": 0, "type": "master", "ttl": 86400, "refresh": 10800, "retry": 3600, "expire": 604800, "minimum": 10800, "serial": 1363228965, "serial_format": "UNIXTIMESTAMP"}, "rr": [
//        {"host": "www.domain.tld.", "displayHost": "www.domain.tld.", "type": "CNAME", "displayValue": "domain.tld.", "opt": "", "value": "domain.tld."},
//        {"host": "1.2.3.4", "displayHost": "1.2.3.4", "type": "PTR", "displayValue": "domain.tld.", "opt": "24", "value": "domain.tld."},
//        {"host": "domain.tld.", "displayHost": "domain.tld.", "type": "TXT", "displayValue": "v=spf1 +a +mx -all", "opt": "", "value": "v=spf1 +a +mx -all"},
//        {"host": "ftp.domain.tld.", "displayHost": "ftp.domain.tld.", "type": "CNAME", "displayValue": "domain.tld.", "opt": "", "value": "domain.tld."},
//        {"host": "ipv4.domain.tld.", "displayHost": "ipv4.domain.tld.", "type": "A", "displayValue": "1.2.3.4", "opt": "", "value": "1.2.3.4"},
//        {"host": "mail.domain.tld.", "displayHost": "mail.domain.tld.", "type": "A", "displayValue": "1.2.3.4", "opt": "", "value": "1.2.3.4"},
//        {"host": "domain.tld.", "displayHost": "domain.tld.", "type": "MX", "displayValue": "mail.domain.tld.", "opt": "10", "value": "mail.domain.tld."},
//        {"host": "webmail.domain.tld.", "displayHost": "webmail.domain.tld.", "type": "A", "displayValue": "1.2.3.4", "opt": "", "value": "1.2.3.4"},
//        {"host": "domain.tld.", "displayHost": "domain.tld.", "type": "A", "displayValue": "1.2.3.4", "opt": "", "value": "1.2.3.4"},
//        {"host": "ns.domain.tld.", "displayHost": "ns.domain.tld.", "type": "A", "displayValue": "1.2.3.4", "opt": "", "value": "1.2.3.4"}
//    ]}},
//    {"command": "createPTRs", "ptr": {"ip_address": "1.2.3.4", "hostname": "domain.tld"}},
//    {"command": "createPTRs", "ptr": {"ip_address": "2002:5bcc:18fd:000c:0001:0002:0003:0004", "hostname": "domain.tld"}}
//]

/*

To do list

- Change results per page when querying record

*/

//$data = json_decode(file_get_contents('php://stdin'));
$data = json_decode(file_get_contents('example-data.json'),true);


function call_SafeDNS_API($method, $url, $data){
   $curl = curl_init();
   switch ($method){
      case "POST":
         curl_setopt($curl, CURLOPT_POST, 1);
         if ($data)
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
         break;
      case "GET":
         curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "GET");
         if ($data)
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
         break;
      case "PATCH":
         curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PATCH");
         if ($data)
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
         break;
      case "DELETE":
         curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");
         if ($data)
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
         break;

      default:
         if ($data)
            $url = sprintf("%s?%s", $url, http_build_query($data));
   }
   // OPTIONS:
   curl_setopt($curl, CURLOPT_URL, $url);
   curl_setopt($curl, CURLOPT_HTTPHEADER, array(
      'Authorization: oJAs5AIifPxZ28pTPSC8x2uRV2EPrTgO',
      'Content-Type: application/json',
   ));
   curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
   curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
   // EXECUTE:
   $result = curl_exec($curl);
   $responsecode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
   if(!$result){die("API Connection Failure. Response code :".$responsecode."\n");}
   echo "Response code : ".$responsecode."\n";
// TODO - If response code not 200 , handle
   curl_close($curl);


   return $result;
}

$api_url="https://api.ukfast.io/safedns/v1";
$safedns_domains=array();

function request_safedns_zones($api_url){
    $get_data = call_SafeDNS_API('GET',$api_url."/zones",false);
    $response = json_decode($get_data, true);
    $data = $response;
    $safedns_domains=array();
    global $safedns_domains;

    $datax = explode(",",json_encode($data));

    foreach ($datax as $val) {
        if (strpos($val, 'name') !== false){
            $exploded=explode(":",$val);
            $domainx=end($exploded);
            $domain=str_replace('"','',$domainx);
//            echo "Domain: ".$domain."\n";
            $safedns_domains[] = $domain;

        }
    }
    return $safedns_domains;
}

function request_safedns_record_for_zone($api_url,$zone_name){
    $get_data = call_SafeDNS_API('GET',$api_url."/zones/".$zone_name."/records",false);
    $response = json_decode($get_data, true);
    $data = $response;
    global $records_array;
    $records_array = array();
    foreach ($data['data'] as $val) {
    /* echo "ID : " .$val['id']."\n";
       echo "NAME : ".$val['name']."\n";
       echo "TYPE : ".$val['type']."\n";
       echo "CONTENT : ".$val['content']."\n";         */
       array_push($records_array,$val['id'].",".$val['name'].",".$val['type'].",".$val['content']);
    }
    return $records_array;
}

function check_create_zone($api_url,$safedns_domains,$input_zone){

    if (in_array($input_zone, $safedns_domains))
      {}
    else
      {
      echo "Creating Zone: ".$input_zone."\n";
      // CREATE ZONE
      $postdata = array(
          'name' => $input_zone,
      );
      call_SafeDNS_API('POST',$api_url."/zones/", json_encode($postdata));
      }
}

function create_record($api_url,$zone_name,$record_name,$record_type,$record_content,$record_priority){
    echo "Creating ".$record_type." Record: ".rtrim($record_name, ".")." with content ".$record_content." on zone ".$zone_name."\n";

    if(strcasecmp($record_type, 'MX') == 0){
        $postdata = array(
            'name' => rtrim($record_name, "."),
            'type' => $record_type,
            'content' => rtrim($record_content, "."),
            'priority' => $record_priority
            );
    } elseif(strcasecmp($record_type, 'TXT') == 0) {
        $postdata = array(
            'name' => rtrim($record_name, "."),
            'type' => $record_type,
            'content' => '"'.rtrim($record_content, ".").'"'
            );
    } else {
        $postdata = array(
            'name' => rtrim($record_name, "."),
            'type' => $record_type,
            'content' => rtrim($record_content, ".")
//        'ttl' => $record_ttl
    );
    }
    call_SafeDNS_API('POST',$api_url."/zones/".$zone_name."/records", json_encode($postdata));
}


request_safedns_zones($api_url);
//$data = json_decode(file_get_contents('php://stdin'));
$data = json_decode(file_get_contents('example-data.json'),true);

$xcommand=explode('"', $data['command']);
$command=$xcommand[0];

if(!isset($data['command'])){
    echo "No command provided in data. Exiting Script. \n";
    echo "------------- Data Provided -----------------\n";
    echo var_dump($data);
    echo "---------------------------------------------\n";
    exit(1);
}

echo var_dump($data);
/*      ---------------------------- MAIN PROGRAM LOOP COMMENTED OUT FOR NOW WHILST I WRITE FUNCTIONS. 
switch ($command) :
    case 'create':
    case 'update':
        echo "UPDATE COMMAND\n";
        // If zone does not exist, create it
        check_create_zone($api_url,$safedns_domains,$data['zone']['name']);

        $rrCount=0;
        foreach ($data['zone']['rr'] as $variablerr) {
            create_record($api_url,$data['zone']['name'],$variablerr['host'],$variablerr['type'],$variablerr['value'],$variablerr['opt']); 
        //  For records in zone:
        //     - Check if record is present in safedns
        //     - If record is not present, create it.
            
        //     - If record is present, check if it's changed ,and update if yes
        //     - If record is present in safedns, but has been removed from plesk, delete it from SafeDNS
            $rrCount++;
        }
        break;
    case 'delete':
        echo "DELETE COMMAND\n";
             // Delete a zone
             //call_SafeDNS_API('DELETE',$api_url."/zones/".$zoneName,false);
        break;
    default:
        echo "Unknown Command : ".$command." !! \n";
        echo "------------- Data Provided -----------------\n";
        echo var_dump($data);
        echo "---------------------------------------------\n";
        exit(1);
endswitch;
--------------------------------------------------------------------------------------------------------------- */ 
// OUTSIDE LOOP
function find_matching_record_safedns($api_url,$zone_name,$record_name,$record_type,$record_content,$records_array){
// Check the record exists in zone exactly as specified. If yes return the Safedns ID Number and True, if No just return False
    echo "Checking if ".$record_type." Record: ".rtrim($record_name, ".")." EXISTS with content ".$record_content." on zone ".$zone_name."\n";
    $testResult = False;
    $recordID = 'Null';
    global $test_result_array;
    foreach ($records_array as $safedns_recordx) {
        $safedns_record=explode(",",$safedns_recordx);
//        echo "recordarray- ".$safedns_record[0];
        // 0 - ID
        // 1 - NAME
        // 2 - TYPE
        // 3 - CONTENT
        if(strcasecmp($safedns_record[2], $record_type) == 0){
//            echo "\nRecord type matches !\n";
            if(strcasecmp($safedns_record[1], $record_name) == 0){
//                echo "Record name matches \n";
                if(strcasecmp($safedns_record[3], '1.2.3.4') == 0){
//                    echo "Record content matches \n";
//                    echo "\n \n The matching record's ID is ".$safedns_record[0]."\n";
//                    call_SafeDNS_API('DELETE',$api_url."/zones/".$zone_name."/records/".$safedns_record[0], false);
                    $testResult = True;
//                    echo "TestResult set to \n";
//                    echo var_dump($testResult);
                    $recordID = $safedns_record[0];
//                    break;
                }
            }
        }
        $test_result_array=(array('testResult' => $testResult, 'recordID' => $recordID));
//        echo "Resultreturn Array for ".$safedns_record[1]."\n";
//        echo var_dump($test_result_array);
//        return $test_result_array;
    }
//    if(strcasecmp($record_type, 'MX') == 0){
//        echo "Record type matches MX! 
       
}

// INSIDE LOOP - Delete function

//$rrCount=0;
//foreach ($data['zone']['rr'] as $variablerr) {
//    check_record_exists_safedns($api_url,$data['zone']['name'],$variablerr['host'],$variablerr['type'],$variablerr['value'],$variablerr['opt']);
    
//  For records in zone(plesk input):
//     - If zone exists
//         - Get all records from safedns (record name, type, content, id) , store in an array called records_array
if ($records_array) {
    echo "Records Array Exists!";
}

if (!$records_array) {
    echo "Records Array Exists!";
}

request_safedns_record_for_zone($api_url,"chrotek.tk");
//         In the delete_matching_record function:
//                 - For records in array above, match record type.
//                     - If record type matches, match for record name.
//                         - If record name matches , match the content too (to be sure we're going to delete the right record)
//                             - If (record name, type, content) all Match , get the ID from safedns, and use the ID to delete the record . 
//delete_matching_record_safedns($api_url,$zone_name,$record_name,$record_type,$record_content,$records_array)
//find_matching_record_safedns($api_url,"chrotek.tk","deleteme.chrotek.tk","A","1.2.3.4",$records_array);
//echo var_dump($records_array);

find_matching_record_safedns($api_url,"chrotek.tk","deleteme.chrotek.tk","A","1.2.3.4",$records_array);

echo "\nMatch test:\n  ";
echo var_dump($test_result_array);

echo "\nMatch test BOOL :\n  ";
echo var_dump($test_result_array['testResult']);

echo "\nMatch test ID   :\n  ";
echo var_dump($test_result_array['recordID']);

echo "\nMatched Record: \n  ";

if ($test_result_array['testResult']) {
    echo "Match TRUE\n";
}

if (!$test_result_array['testResult']) {
    echo "Match FALSE\n";
}
//echo "END MATCHES\n";
//echo var_dump(find_matching_record_safedns($api_url,"chrotek.tk","deleteme.chrotek.tk","A","1.2.3.4",$records_array));

//     - If zone does not exist
//         - Do nothing?

//    $rrCount++;
//        }



//echo "Test Delete \n";
//call_SafeDNS_API('DELETE',$api_url."/zones/"."EXAMPLEWIBBLE.COM",false);

   // }
//}



