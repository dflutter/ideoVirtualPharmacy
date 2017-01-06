<?php
  require_once("sqlConnect.php");
  $data=mysqli_query($con,"SELECT * from location");
  $s=mysqli_fetch_array($data);
  if($s['loc1']=="OPEN") {
    $loc[1]['open']="checked";	
    $loc[1]['covered']="";
  }elseif($s['loc1']=="COVERED") {
	  $loc[1]['covered']="checked";
	  $loc[1]['open']="";
  }
  
  if($s['loc2']=="OPEN") {
    $loc[2]['open']="checked";	
    $loc[2]['covered']="";
  }elseif($s['loc2']=="COVERED") {
	  $loc[2]['covered']="checked";
	  $loc[2]['open']="";
  }
  
  if($s['loc3']=="OPEN") {
    $loc[3]['open']="checked";	
    $loc[3]['covered']="";
  }elseif($s['loc3']=="COVERED") {
	  $loc[3]['covered']="checked";
	  $loc[3]['open']="";
  }
  
  $data=mysqli_query($con,"SELECT * from currentLocation");
  $s=mysqli_fetch_array($data);
  $location[1]="";
  $location[2]="";
  $location[3]="";
  $location[$s['location']]="checked";
  
  if($s['status']=="OPEN") { 
    $loc['status']['open']="checked";
    $loc['status']['covered']="";
  } elseif($s['status']=="COVERED") {
    $loc['status']['open']="";
    $loc['status']['covered']="checked";
  }
	
?>

<style>
body {
  background: #efefef;
  font-size: 62.5%;
  font-family: 'Lato', sans-serif;
  font-weight: 300;
  color: #B6B6B6;
}
body section {
  background: white;
  margin: 60px auto 120px;
  border-top: 15px solid #313A3D;
  text-align: center;
  padding: 50px 0 110px;
  width: 80%;
  max-width: 1100px;
}
body section h1 {
  margin-bottom: 40px;
  font-size: 4em;
  text-transform: uppercase;
  font-family: 'Lato', sans-serif;
  font-weight: 100;
}

form {
  width: 58.33333%;
  margin: 0 auto;
}
form .field {
  width: 100%;
  position: relative;
  margin-bottom: 15px;
}
form .field label {
  text-transform: uppercase;
  position: absolute;
  top: 0;
  left: 0;
  background: #313A3D;
  width: 25%;
  padding: 18px 0;
  font-size: 1.45em;
  letter-spacing: 0.075em;
  -webkit-transition: all 333ms ease-in-out;
  -moz-transition: all 333ms ease-in-out;
  -o-transition: all 333ms ease-in-out;
  -ms-transition: all 333ms ease-in-out;
  transition: all 333ms ease-in-out;
}
form .field label + span {
  font-family: 'SSStandard';
  opacity: 0;
  color: white;
  display: block;
  position: absolute;
  top: 12px;
  left: 7%;
  font-size: 2.5em;
  text-shadow: 1px 2px 0 #cd6302;
  -webkit-transition: all 333ms ease-in-out;
  -moz-transition: all 333ms ease-in-out;
  -o-transition: all 333ms ease-in-out;
  -ms-transition: all 333ms ease-in-out;
  transition: all 333ms ease-in-out;
}
form .field input[type="radio"] {
	padding-top: 25px;
}
form .field input[type="text"],
form .field textarea {
  border: none;
  background: #E8E9EA;
  width: 80.5%;
  margin: 0;
  padding: 18px 0;
  padding-left: 19.5%;
  color: #313A3D;
  font-size: 1.4em;
  letter-spacing: 0.05em;
  text-transform: uppercase;
}
form .field input[type="text"]#msg,
form .field textarea#msg {
  height: 18px;
  resize: none;
  -webkit-transition: all 333ms ease-in-out;
  -moz-transition: all 333ms ease-in-out;
  -o-transition: all 333ms ease-in-out;
  -ms-transition: all 333ms ease-in-out;
  transition: all 333ms ease-in-out;
}
form .field input[type="text"]:focus, form .field input[type="text"].focused,
form .field textarea:focus,
form .field textarea.focused {
  outline: none;
}
form .field input[type="text"]:focus#msg, form .field input[type="text"].focused#msg,
form .field textarea:focus#msg,
form .field textarea.focused#msg {
  padding-bottom: 50px;
}
form .field input[type="text"]:focus + label, form .field input[type="text"].focused + label,
form .field textarea:focus + label,
form .field textarea.focused + label {
  width: 25%;
  background: #FD9638;
  color: #313A3D;
}
form .field input[type="text"].focused + label,
form .field textarea.focused + label {
  color: #FD9638;
}
form .field:hover label {
  width: 25%;
  background: #313A3D;
  color: white;
}
form input[type="submit"] {
  background: #FD9638;
  color: white;
  -webkit-appearance: none;
  border: none;
  text-transform: uppercase;
  position: relative;
  padding: 13px 50px;
  font-size: 1.4em;
  letter-spacing: 0.1em;
  font-family: 'Lato', sans-serif;
  font-weight: 300;
  -webkit-transition: all 333ms ease-in-out;
  -moz-transition: all 333ms ease-in-out;
  -o-transition: all 333ms ease-in-out;
  -ms-transition: all 333ms ease-in-out;
  transition: all 333ms ease-in-out;
}
form input[type="submit"]:hover {
  background: #313A3D;
  color: #FD9638;
}
form input[type="submit"]:focus {
  outline: none;
  background: #cd6302;
}	
</style>

	<section id="hire">
    <h1>Virtual Pharm Interface</h1>
    
    <form action="order.php" method="post">
	      <div class="field name-box">
		        <input type="text" id="inventory" name="inventory" placeholder="Drug"/>
        		<label for="name">Drug</label>

	      </div>

	      <div class="field name-box">
		        <input type="text" id="doses" name="doses" placeholder="doses"/>
		        <label for="email">Doses</label>
	      </div>
	      
	      <input class="button" type="submit" value="Order" />
	      
    </form><br><hr><br>
    
    <form action="activeLocation.php" method="post">
	      <div class="field name-box">
		        <br><span style="font-size:14px;">OPEN</span><input type="radio" id="loc1" name="loc1" value="OPEN" <?php echo $loc[1]['open']; ?>>
		        <span style="font-size:14px;">COVERED</span><input type="radio" id="loc1" name="loc1" value="COVERED" <?php echo $loc[1]['covered']; ?>>
		        <label for="msg">Loc1</label>
	      </div><br>
	     <div class="field name-box">
		        <br><span style="font-size:14px;">OPEN</span><input type="radio" id="loc2" name="loc2" value="OPEN" <?php echo $loc[2]['open']; ?>>
		        <span style="font-size:14px;">COVERED</span><input type="radio" id="loc2" name="loc2" value="COVERED" <?php echo $loc[2]['covered']; ?>>
		        <label for="msg">Loc2</label>
	      </div>
	      <br>
	      <div class="field name-box">
		        <br><span style="font-size:14px;">OPEN</span><input type="radio" id="loc3" name="loc3" value="OPEN" <?php echo $loc[3]['open']; ?>>
		        <span style="font-size:14px;">COVERED</span><input type="radio" id="loc3" name="loc3" value="COVERED" <?php echo $loc[3]['covered']; ?>>
		        <label for="msg">Loc3</label>
	      </div>
	      
	       <input class="button" type="submit" value="Update Sensor Status" />
    </form><br><hr><br>
    <form action="overRideCurrentLocation.php" method="post">
	      <br>
	      <div class="field name-box">
		        <br><span style="font-size:14px;">1</span><input type="radio" id="location" name="location" value="1" <?php echo $location[1]; ?>>
		        <span style="font-size:14px;">2</span><input type="radio" id="location" name="location" value="2" <?php echo $location[2]; ?>>
   		        <span style="font-size:14px;">3</span><input type="radio" id="location" name="location" value="3" <?php echo $location[3]; ?>>
		        <label for="msg">Last Loc</label>
	      </div>	      
	      
	      <br>
	      <div class="field name-box">
		        <br><span style="font-size:14px;">OPEN</span><input type="radio" id="status" name="status" value="OPEN" <?php echo $loc['status']['open']; ?>>
		        <span style="font-size:14px;">COVERED</span><input type="radio" id="status" name="status" value="COVERED" <?php echo $loc['status']['covered']; ?>>
		        <label for="msg">Status</label>
	      </div>
	      
	      <br/><br/>

	      <input class="button" type="submit" value="Change Current Location" />
  </form>
  <br><hr><br>
  <form action="updateInventory.php" action="post">
<?
  $data=mysqli_query($con,"SELECT * FROM inventory");
  while($info=mysqli_fetch_array($data)) {
	  echo '
	  		<div class="field name-box">
		        <input type="text" id="doses" name="doses" value="'.$info['doses'].'"/>
		        <input type="text" id="location" name="location" value="'.$info['location'].'"/>
        		<label for="name"><br><br>'.$info['drug'].'<br><br></label>
			</div>
		';
  }	//end of while

?>
		<input class="button" type="submit" value="Change Inventory Database" />
  </form>
</section>