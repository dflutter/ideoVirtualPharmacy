<?php 
  require_once("sqlConnect.php");
  
  
  if(!empty($_GET))
    $post=$_GET;
  if(!empty($POST))
    $post=$_POST;
  
   //determine what to search for
  
  $drug=$post['inventory'];
  $doses=$post['doses'];
  $location=$post['location'];
  $log=date('Y-m-d H:i:s');
  $pulled=$post['pulled'];

  
  //function to determine the best location to pull doses from (used for 2 options)
  function doseLocation() {
	  global $con,$drug,$doses;
	  
	  $data=mysqli_query($con,"SELECT * from inventory where drug='$drug' and doses>=$doses ORDER BY eDate asc limit 1") or die(mysqli_error($con));
      if(mysqli_num_rows($data)>0) {
     	$s=mysqli_fetch_array($data,MYSQL_ASSOC);
	 	$location=$s['location'];
	  } else 
	    $location="";

     return $location;
  }
  
   
  $where="";
  //The ai provided inventory, dose, location and override so this will just reduce the dosage for that drug and location
  if(count($post)==4) {    

    $log.=": $doses removed.";
    mysqli_query($con,"UPDATE inventory set log=CONCAT(`log`,$log), doses=(`doses`-$doses) where drug='$drug' AND location='$location'") or die(mysqli_error($con));
    $d['message']="$drug inventory at $location was reduced by $doses doses from non optimal location";    	  
  

  //CHECKING CORRECT LOCATION WAS USED
  }elseif(count($post)==3) { //this will check if location is correct and either reduce the doses or respond with error
	  //get the most recently used location from currentLocation
	  $dataLocation=mysqli_query($con,"SELECT * from currentLocation") or die(mysqli_error($con));
	  $s=mysqli_fetch_array($dataLocation);
	  $locUsed=$s['location'];
	  $bestLoc=doseLocation();
	  if(strlen($bestLoc)>0) {
		  //if the best location was used then reduce the number by the doses used
		  if($locUsed==$bestLoc) {
			  $log.=": $doses removed.";
			  mysqli_query($con,"UPDATE inventory set log=CONCAT(`log`,'$log'), doses=(`doses`-$doses) where drug='$drug' AND location='$bestLoc'") or die(mysqli_error($con));
			  $d['message']="$drug inventory at $location was reduced by $doses doses.";    	  
		  } else {//check about the location whether it contains the correct drug or it is the WRONG drug
			  $d['message']="The wrong location was used. Please correct";
			  $d['event']="non-optimial-location";
		  }
	  } else {
		$d['message']="There are insufficient doses of $drug in a single location to fill this request. Should I order more?";
	    $d['event']="Insufficient Stock";   
	  }
	  
  
  //LOCATION TO PULL DOSAGE FROM
  }elseif(count($post)==2) {//this will respond with the storage location of where to get the doses from the location that has sufficient doses     
	  
	 $location=doseLocation();
	 if(strlen($location)>0) {
		 if($doses==1) 
		   $doseText="dose";
		 else
		   $doseText="doses";
		   
	 	$d['message']="Please pull $doses $doseText of $drug from location $location";
     } else {
	    $d['message']="There are insufficient doses of $drug in a single location to fill this request. Should I order more?";
	    $d['event']="insufficient-stock"; 
     }
  
 //DOSES AVAILABLE FOR A SPECIFIC DRUG
  }elseif(count($post)==1) {
	$data=mysqli_query($con,"SELECT sum(doses) as countDoses from inventory where drug='$drug'") or die(mysqli_error($con));
	$s=mysqli_fetch_array($data);
    if(isset($s['countDoses'])) {
		$avail=$s['countDoses'];
		$d['message']="There are $avail doses of $drug available in stock";
	}  else {
		$d['message']="$drug is not currently in inventory. Should I order some?";
		$d['event']="not-available";
	}
 
  //ALL INVENTORY PROVIDED
  } elseif(count($post)==0) {
	  $data=mysqli_query($con,"SELECT sum(doses) as countDoses,drug from inventory where doses>0 group by drug") or die(mysqli_error($con));
	  $d['message']="The following inventory is available.";
	   while($info=mysqli_fetch_array($data,MYSQL_ASSOC)) {
		  $d['message'].=" ".$info['countDoses']." doses of ".$info['drug'].". ";
	  }//end of while
  }
  
  $final=json_encode($d);
  
  echo $final;

?>