<?php 
  
  //create a connection to the database it will use $con as the database connection variable in all of the mysqli_query calls
  require_once("sqlConnect.php");
  
  
  //get the POST BODY from the call to this page
  $temp=file_get_contents('php://input');
  if(strlen($temp)==0) {
  $temp='{
    "id": "35ea307e-7c43-4394-863c-4ca01e84f48d",
  "timestamp": "2017-01-06T17:48:29.315Z",
  "result": {
    "source": "agent",
    "resolvedQuery": "i used 2 doses of aspirin",
    "action": "used-inventory",
    "actionIncomplete": false,
    "parameters": {
      "drug": "aspirin",
      "doses": "2"
    },
    "contexts": [],
    "metadata": {
      "intentId": "849a5b56-8007-490d-9775-649e1834d239",
      "webhookUsed": "true",
      "webhookForSlotFillingUsed": "false",
      "intentName": "used inventory"
    },
    "fulfillment": {
      "speech": "",
      "messages": [
        {
          "type": 0,
          "speech": ""
        }
      ]
    },
    "score": 1
  }
  }';  
  }
  
  //Assumes it is JSON and decodes it
  $temp=json_decode($temp,true);
  
  //print_r($temp);
  
  
  //converts the JSON POST payload to parameters (drug,dose,etc) and action being requested
  $result=$temp['result'];
  	$action=$result['action'];
  	$parameters=$result['parameters'];
	  	$drug=$parameters['drug'];
	  	$doses=$parameters['doses'];
	  	
  $today=date("Y-m-d");
	  	
  function dateDifference($eDate, $differenceFormat = '%a' ) {
    $datetime1 = date_create(date('Y-m-d'));
    $datetime2 = date_create(date('Y-m-d',strtotime($eDate)));
    
    $interval = date_diff($datetime1, $datetime2);
    

    
    $days=$interval->format($differenceFormat);
    if($days<0) 
      $days="is expried";
    if($days==0)
      $days="expires today";
    elseif($days==1)
      $days="expires tomorrow";
    else
      $days="expires in $days days";
    
    return $days;
    
}//end function dateDifference
  
  function inventory() {	  
	  //this function returns the inventory based on the drug provided. If $drug="" then it will return total inventory.
	  global $drug,$con,$today;
	  
	  if(strlen($drug)>0)
	    $where=" AND lower(drug) LIKE '%".strtolower($drug)."%' ";
	  else
	    $where="";
	  
	  //mysql query for inventory   
	  $data=mysqli_query($con,"SELECT doses,drug,location,eDate from inventory where doses>0 $where") or die(mysqli_error($con));
	  
	  $d['speech']="The following $drug inventory is available.\r\n\r\n";
	  if(mysqli_num_rows($data)==0) {
		 $d['speech']="There is no stock available of $drug. You should order more.";
	  } else {
		  while($info=mysqli_fetch_array($data,MYSQL_ASSOC)) {
			  if($info['doses']==1)
			  	$doseType="dose";
			  else
			  	$doseType="doses";
			  $d['speech'].=" ".$info['doses']." $doseType of ".$info['drug']." at location ".$info['location']." and ".dateDifference($info['eDate']).".\r\n\r\n ";
	  	  }//end of while
	  } //end of else

	  //return the response array $d
      return $d;
  }
  
  //function to determine the best location to pull doses from (used for 2 options)
  function doseLocation() {
	  global $con,$drug,$doses;
	  
	  $data=mysqli_query($con,"SELECT * from inventory where lower(drug) like '%".strtolower($drug)."%' and doses>=$doses ORDER BY eDate desc limit 1") or die(mysqli_error($con));
      if(mysqli_num_rows($data)>0) {
     	$s=mysqli_fetch_array($data,MYSQL_ASSOC);
	 	$location=$s['location'];
	  } else 
	    $location="";

     return $location;
  }
  
  function needInventory() {
	 global $drug,$doses,$con;
	 
	 $location=doseLocation();
	 if(strlen($location)>0) {
		 $data=mysqli_query($con,"SELECT * from inventory where location='$location' and lower(drug) like '%".strtolower($drug)."%'");
		 $s=mysqli_fetch_array($data);
		 $eDate=$s['eDate'];
		 $expire=dateDifference($eDate);
		 if($doses==1) 
		   $doseText="dose";
		 else
		   $doseText="doses";
		   
	 	$d['speech']="Please pull $doses $doseText of $drug from location $location which $expire.";
     } else {
	    $d['speech']="There are insufficient doses of $drug in a single location to fill this request. You should order more.";
     }

      return $d;
  }
  
  
  function useInventory() {
	  global $con,$drug,$doses;
	  
	  //get best location
	  $location=doseLocation();
	  
	  //Get most recently used location
	  $data=mysqli_query($con,"SELECT * from currentLocation");
	  $s=mysqli_fetch_array($data);
	  $usedLocation=$s['location'];
	  
	  //Get drug in the location used
	  $data=mysqli_query($con,"SELECT * from inventory where doses>0 and location='$usedLocation'");
	  $s=mysqli_fetch_array($data);
	  $drugInUsed=strtolower($s['drug']);
	  
	  if($drug<>$drugInUsed) {
		  $d['speech']="You have taken $drugInUsed from location $usedLocation but you needed $drug in location $location.  Please return $drugInUsed to location $usedLocation and get $drug from location $location.";
	  } elseif($location<>$usedLocation) {
		  $d['speech']="You have taken $drug from location $usedLocation which was not optimal, please return and take $drug from location $location.";
	  }elseif($location==$usedLocation AND $drug==$drugInUsed) {
		  $d['speech']="Great Job, $doses $drugInUsed have been removed from location $location. Inventory has been adjusted.";
		  mysqli_query($con,"UPDATE inventory set doses=(`doses`-$doses) where lower(drug) LIKE '%".strtolower($drugInUsed)."%' AND location='$location'");
		  //remove any drugs that have zero inventory
		  mysqli_query($con,"DELETE from inventory where doses<=0");
	  }
	    	  
	  return $d;
  }
  
  function orderInventory() {
	  global $con,$drug,$doses;
	 
	  $dataLocation=mysqli_query($con,"SELECT * from location") or die(mysqli_error($con));
	  $sLocation=mysqli_fetch_array($dataLocation);
	  
	  $open=false;
	  if($sLocation['loc1']=="OPEN") {
		  $open=true;
		  $loc='1';
	  }elseif($sLocation['loc2']=="OPEN") {
		  $open=true;
		  $loc='2';
	 }elseif($sLocation['loc3']=="OPEN") {
		 $open=true;
		 $loc='3';
	  }
	  
	  $dataDose=mysqli_query($con,"SELECT sum(doses) as curDose from inventory where lower(drug) LIKE '%$drug%'") or die(mysqli_error($con));
	  $s=mysqli_fetch_array($dataDose,MYSQL_ASSOC);
      if($s['curDose']>0) {		  
		  $sufficient=true;
		  $curDoses=$s['curDose'];
	  } else {
		  $sufficient=false;
	  }
	  
	  if(!$open) {
		  $d['speech']="There are no open locations for $doses doses of $drug to be stored.";
//		  $d['event']="stock-full";
	  }elseif($sufficient) {
		  $d['speech']="There are currently $curDoses doses of $drug and ordering more is not recommended at this time.";
//		  $d['event']="sufficient-stock";
	  }else {
		 $days=rand(1,5);
		 $d['speech']="$drug order was processed for $doses doses and has been received. It expires in $days days. Please store in location $loc.";
		 $eDate=date("Y-m-d",strtotime("+$days days"));
		 mysqli_query($con,"INSERT INTO inventory (drug,doses,location,eDate) VALUES ('$drug','$doses','$loc','$eDate')") or die(mysqli_error($con));
		 mysqli_query($con,"UPDATE location set loc".$loc."='COVERED' where 1") or die(mysqli_error($con));		 
	  }
	  
	  return $d;
	} //end function
  	   
  if($action=="total-inventory" OR $action=="drug-inventory") {
	  $d=inventory();
  }elseif($action=="need-inventory") {
	  $d=needInventory();
  }elseif($action=="used-inventory") {
      $d=useInventory();
  }elseif($action=="order-inventory") {
	  $d=orderInventory();
  }
  
  
  $d['displayText']=$d['speech'];
  $d['speech']=str_replace("\r\n","",$d['speech']);
  $final=stripslashes(json_encode($d));

 
  header('Content-Type: application/json');
  echo $final;

?>