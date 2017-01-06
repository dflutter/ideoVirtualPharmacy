<?php 
	
	require_once("sqlConnect.php");
    
  if(!empty($_GET))
    $post=$_GET;
  if(!empty($_POST))
    $post=$_POST;
   
  
  if(!empty($post)) {
	  $drug=strtolower($post['inventory']);
	  $doses=$post['doses'];
	  
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
		  $d['message']="There are no open locations for $doses doses of $drug to be stored.";
		  $d['event']="stock-full";
	  }elseif($sufficient) {
		  $d['message']="There are currently $curDoses doses of $drug and ordering more is not recommended at this time.";
		  $d['event']="sufficient-stock";
	  }else {
		 $d['message']="$drug order was processed for $doses doses and has been received. It expires in 60 days. Please store in location $loc.";
		 $eDate=date("Y-m-d",strtotime("+60 days"));
		 mysqli_query($con,"INSERT INTO inventory (drug,doses,location,eDate) VALUES ('$drug','$doses','$loc','$eDate')") or die(mysqli_error($con));
		 mysqli_query($con,"UPDATE location set loc".$loc."='COVERED' where 1") or die(mysqli_error($con));		 
	  }
  } else {
	  $d['message']="Insufficient order information provided.";
	  $d['event']="insufficient-information-provided";
  }
    	
    echo json_encode($d);
?>	