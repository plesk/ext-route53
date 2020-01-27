<?php

/*

To do list

- Change results per page when querying record

*/

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
   echo "Response code : ".$responsecode;
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
function request_zone_records($zone,$api_url){
    $get_data = call_SafeDNS_API('GET',$api_url."/zones/".$zone."/records/",false);
    $response = json_decode($get_data, true);
    $data = $response;
    $datax = explode(",",json_encode($data));

    foreach ($datax as $val) {
//        echo ("VAL: ".var_dump($val));
        if (strpos($val , 'name') !== false){
            $exploded=explode(":",$val);
      //      echo("EXPLODED:".var_dump($exploded));
  //          $domain=str_replace('"','',$domainx);
      //      echo "Domain: ".$domain."\n";
    //        $safedns_domains[] = $domain;
        echo "................................................";
        }
     }

    echo "\n -----------------------------------------------------------------------------------\n requesting zone records for ".$zone."\n";
    //echo (var_dump($data));


}


function check_create_zone($api_url,$safedns_domains,$input_zone){


 //   $input_zone="blerg.chrotek.co.uk";
    if (in_array($input_zone, $safedns_domains))
      {}
    else
      {
      echo "Match not found for ".$input_zone."\n";
      // CREATE ZON
      $postdata = array(
          'name' => $input_zone,
      );
      call_SafeDNS_API('POST',$api_url."/zones/", json_encode($postdata));
      }
}


request_safedns_zones($api_url);
//echo var_dump($safedns_domains)."\nhere\n";
//foreach ($safedns_domains as $val){
#    echo "Check create zone ".$val;
check_create_zone($api_url,$safedns_domains,"quackers.com");
?>
