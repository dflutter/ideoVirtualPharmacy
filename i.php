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
    "action": "whats-missing",
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
		 $d['speech']="There is no stock available of $drug. Should I order more?";
		 $d['contextOut']=array(array("name"=>"no-stock", "lifespan" =>"1", "parameters" =>array("drug"=>"$drug")));
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
	    $d['speech']="There are insufficient doses of $drug in a single location to fill this request. Should I order more?";
	    $d['contextOut']=array(array("name"=>"no-stock", "lifespan" =>"1", "parameters" =>array("drug"=>"$drug")));
     }

      return $d;
  }
  
  
  function useInventory() {
	  global $con,$drug,$doses,$override;
	  
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
		  $d['speech']="You have taken $drugInUsed from location $usedLocation but you needed $drug in location $location.  Please return $drugInUsed to location $usedLocation and get $drug from location $location. Fixed It?";
		  $d['contextOut']=array(array("name"=>"location-swap", "lifespan" =>"1", "parameters" =>array("drug"=>"$drug","doses"=>"$doses")));
	  } elseif($location<>$usedLocation) {
		  $d['speech']="You have taken $drug from location $usedLocation which was not optimal, please return and take $drug from location $location.";
		   $d['contextOut']=array(array("name"=>"not-optimal", "lifespan" =>"1", "parameters" =>array("drug"=>"$drug","doses"=>"$doses","location"=>"$usedLocation")));
	  }elseif($location<>$usedLocation AND $override) {
	       $d['speech']="Though not optimal, $doses $drugInUsed have been removed from location $location. Inventory has been adjusted.";
		  mysqli_query($con,"UPDATE inventory set doses=(`doses`-$doses) where lower(drug) LIKE '%".strtolower($drugInUsed)."%' AND location='$usedLocation'");
		  //remove any drugs that have zero inventory
		  mysqli_query($con,"DELETE from inventory where doses<=0");
	  } elseif($location==$usedLocation AND $drug==$drugInUsed) {
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
	
	
	function resetInventory() {
	  global $con;	
	  $d['speech']="For testing purposes the inventory has been reset. Please request an update on the total inventory and reset bottles as described.";
	  mysqli_query($con,"DELETE from inventory where 1");
	  $date1=date('Y-m-d',strtotime("+1 day"));
	  $date2=date('Y-m-d',strtotime("+2 days"));
	  $date3=date('Y-m-d',strtotime("+3 days"));
	  mysqli_query($con,"INSERT INTO inventory (drug,doses,eDate,location) VALUES ('aspirin','100','$date1','1')");
	  mysqli_query($con,"INSERT INTO inventory (drug,doses,eDate,location) VALUES ('cipro','10','$date2','2')");
	  mysqli_query($con,"INSERT INTO inventory (drug,doses,eDate,location) VALUES ('arv','1','$date3','3')");	  
	  mysqli_query($con,"UPDATE location set loc1='COVERED',loc2='COVERED',loc3='COVERED' where 1");
	  mysqli_query($con,"UPDATE currentLocation set location='1',status='COVERED' where 1");
	  
	  return $d;
	}//end function
	
	function missing() {
		global $con;
		//this returns if a location is OPEN and an item in inventory exists for that location it will reply with what's missing
		$data=mysqli_query($con,"SELECT * from location");
		$loc=mysqli_fetch_array($data,MYSQL_ASSOC);
		if($loc['loc1']=="OPEN")
		  $l[]=1;
		if($loc['loc2']=="OPEN")
		  $l[]=2;
		if($loc['loc3']=="OPEN")
		  $l[]=3;
		  

		if(count($l)==0)
		  $d['speech']="All locations contain medication, there is nothing missing.";
		else {
			foreach($l as $location) {

				$data=mysqli_query($con,"SELECT * FROM inventory where doses>0 and location='$location'");
				if(mysqli_num_rows($data)>0) {
					$s=mysqli_fetch_array($data);
					$d['speech'].=$s['drug']." is missing from location ".$location.". Please find and return it.";
				} else {
					$d['speech'].="Location $location is open and no medication is currently expected to be stored there.";
				}
			}
		}
      return $d;
	}
  	   
  if($action=="total-inventory" OR $action=="drug-inventory") {
	  $d=inventory();
  }elseif($action=="need-inventory") {
	  $d=needInventory();
  }elseif($action=="used-inventory" OR $action=="use-inventory-override") {
	  if($action=="use-inventory-override") 
	     $override=true;
	  else
	     $override=false;
      $d=useInventory();
  }elseif($action=="order-inventory") {
	  $d=orderInventory();
  }elseif($action=="reset-inventory") {
	  $d=resetInventory();
  }elseif($action="whats-missing"){
	  $d=missing();
  }
  
  
  
  
  $d['displayText']=$d['speech'];
  
  $d['speech']=str_replace("\r\n","",$d['speech']);
  $final=stripslashes(json_encode($d));

 
  header('Content-Type: application/json');
  echo $final;

?>