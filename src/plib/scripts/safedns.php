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
echo "Test Delete \n";
call_SafeDNS_API('DELETE',$api_url."/zones/"."EXAMPLEWIBBLE.COM",false);

   // }
//}



