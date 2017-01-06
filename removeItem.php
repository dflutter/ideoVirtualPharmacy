<?php
	require_once("sqlConnect.php");
	
    $data=mysqli_query($con,"SELECT * FROM inventory") or die(mysqli_error($con));
    
    while($info=mysqli_fetch_array($data)) {
	    $inv[]=$info;
    }	//end while
    
    
    if(count($inv)<5)
      $numToRemove=count($inv);
    else
	     $numToRemove=5;
    for($i=0;$i<$numToRemove;$i++) {
	    $ind=rand(1,count($inv))-1;
	    mysqli_query($con,"DELETE FROM inventory where id=".$inv[$ind]['id']) or die(mysqli_error($con));
	    echo "Item Removed: ".$inv[$ind]['name']." / Updating Blockchain Transaction ".$inv[$ind]['uid']."<br>";
	    $removed[]=$inv[$ind];
    }
    
    
    echo '<br><a href="sensor.php">Back to Sensor</a><br>';
    
    $d['event']="open-cabinet";
	$d['date']=date('Y-m-d H:i:s');
	$d['removed']=$removed;
	
	$url = 'http://82af1ba5.ngrok.io/event';
    //$url="http://www.ccomfort.com/ideo/test.php";
    			
    echo json_encode($d);

	$myvars = 'data=' .urlencode(json_encode($d));

	$ch = curl_init( $url );
	curl_setopt( $ch, CURLOPT_POST, 1);
	curl_setopt( $ch, CURLOPT_POSTFIELDS, $myvars);
	curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt( $ch, CURLOPT_HEADER, 0);
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1);
//	curl_setopt( $ch ,"Accept: application/json",0);
	$response = curl_exec( $ch );
	echo "<br>Response:<br>";
	var_dump($resonpse);

	
?>