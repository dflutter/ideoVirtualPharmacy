<?php
	
	require_once("sqlConnect.php");
	
	if(!empty($_GET))
    	$post=$_GET;
	if(!empty($_POST))
    	$post=$_POST;
    	
  $temp=file_get_contents('php://input');
  $temp=json_decode($temp,true);
  $post=$temp;
  $temp=print_r($temp,true);
    	
    	
  $fp=fopen("active.txt","a");
  fputs($fp,date('Y-m-d H:i:s').print_r($post,true)."\r\n");
  fclose($fp);

    	
    if(!empty($post)) {
	
	  $data['loc1']=$post['loc1'];
	  $data['loc2']=$post['loc2'];
	  $data['loc3']=$post['loc3'];
	  
	    //get current conditions:
	    $d=mysqli_query($con,"SELECT * from location") or die(mysqli_error($con));
        $s=mysqli_fetch_array($d, MYSQL_ASSOC);
        
        if($s['loc1']<>$data['loc1']) {
	        $s['loc1']=$data['loc1'];
	        $title='1';
	        $value=$data['loc1'];
        } elseif($s['loc2']<>$data['loc2']){
	        $s['loc2']=$data['loc2'];
	        $title='2';
	        $value=$data['loc2'];
        } elseif($s['loc3']<>$data['loc3']){
	        $s['loc3']=$data['loc3'];
	        $title='3';
	        $value=$data['loc3'];
        }
        
        //update current location and then location
        if($title<>"" AND $value<>"")
        mysqli_query($con,"UPDATE currentLocation set location='$title', status='$value' where 1") or die(mysqli_error($con));
        print_r($s);            
        
        if($title<>"" AND $value<>"") {
          mysqli_query($con,"UPDATE location set loc1='".$s['loc1']."', loc2='".$s['loc2']."',loc3='".$s['loc3']."'") or die(mysqli_error($con));
          echo "<br> location: $title  value= $value <br>";
        } else {
	        echo "<br> No change in status of any location";
        }
    }
	
?>