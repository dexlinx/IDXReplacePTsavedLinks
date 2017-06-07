<?php
/*
/~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
/~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
Use Case Example:
- Several saved links set as Condo or other property type (On V1)
- Migration script split those and set the Original saved links to Residential (Wrong PT)
- Now we have the original saved links (with correct naming) with the wrong property type
- And, the new saved links with the wrong names and correct property types 
=> Search for any saved links with -propertytype in the name
=> Get the Coresponding saved links without -propertytype and gather it's Query String and ID
=> Update the Query String of the Original Saved links with Correct Property Type
/~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
/~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
*/
?>

<html>
<head>
<title>Pt Replacement</title>
<style>
.mainContent {
    width: 75%;
    border-style: solid;
    margin: auto;
    padding: 20px;
    border-color: blue;
    background-color: #e7f5e7;
}
input.readOnly {
    background-color: gainsboro;
    border-style: dashed;
    border-color: blue;
    padding: 3px;
}
</style>
</head>
<body>
<div class='mainContent'>
<?php
//--------------------------------------------------------------------
//FUNCTION: API Call CURL code
//--------------------------------------------------------------------
function apiCall($curlUrl,$request,$data,$accesskey){
ini_set('memory_limit', '256M');
ini_set('max_execution_time', '60S');
ini_set('upload_max_filesize','5M');
ini_set('post_max_size','5M');
set_time_limit(0);
$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => $curlUrl,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 30,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_SSL_VERIFYHOST => 0,
  CURLOPT_SSL_VERIFYPEER => 0,
  CURLOPT_CUSTOMREQUEST => $request,
  CURLOPT_HTTPHEADER => array(
    "accesskey:".$accesskey,
    "ancillary:".$ancillarykey,
    "apiversion: 1.4.0",
    "cache-control: no-cache",
    "content-type: application/x-www-form-urlencoded",
    "outputtype: json",
     ),
));

if ($request != 'GET') {
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
}
$output = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);

if ($err) {
  echo "cURL Error #:" . $err;
} else {
  return $output;
}
}


//--------------------------------------------------------------------
//FUNCTION: Form Inputs
//--------------------------------------------------------------------
function inputForm($ancillarykey,$accesskey,$runType,$startKey,$numToRun){

if ($runType == dry){echo "<h3>Preview List of Links to be changed...</h3>";}
if ($runType == first){echo "<h3>Start Updating Your Links...</h3>";}

echo "<form action='replacePt.php' method='post'>";
echo "<table border=0>";	


	if ($runType == dry){//Only Show Links
		echo "<tr><td>API Key:</td><td> <input type='text' name='apiKey' value='".$accesskey."'></td></tr>";
		echo "<input type='hidden' name='dryRun' value='dryruncomplete'>";
	}elseif($runType == realRun || $runType == doMore){//Ready for First Run
		
		if (!isset($startKey)){
			echo "<tr><td>Start Key Value: </td><td> <input type='text' name='startAtNum' value='0' readonly='readonly' class='readOnly'></td></tr>";
		}else{
			echo "<tr><td>Start Key Value: </td><td> <input type='text' name='startAtNum' value='".$startKey."' readonly='readonly' class='readOnly'></td></tr>";
		}
		echo "<tr><td># To Run:</td><td> <input type='text' name='numToRun' value='".$numToRun."'></td></tr>";
		echo "<tr><td>API Key:</td><td> <input type='text' name='apiKey' value='".$accesskey."' readonly='readonly' class='readOnly'></td></tr>";
		echo "<tr><td>Ancillary Key (optional):</td><td> <input type='text' name='ancillary' value='".$ancillarykey."'></td></tr>";
		echo "<input type='hidden' name='dryRun' value='dryruncomplete'>";
		echo "<input type='hidden' name='firstRun' value='firstRunComplete'>";
	}

	
echo "</table>";

	if ($runType == dry){
		echo "<input type='submit' value='Dry Run'>";
	}elseif($runType == doMore){
		echo "<input type='submit' value='Continue Updates'>";
	}else{
		echo "<input type='submit' value='Start Updates'>";
	}



echo "</form>";
echo "<hr>";	
	
}


/*
~~~~~~~~~~~~~~~~~~~~~~~~~~~~ VARIABLES ~~~~~~~~~~~~~~~~~~~~~~~~~~~
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
*/

//Set the Property Types
//-------------------------------------
$wrongPt = 'pt=1'; //Wrong PT
$correctPt = 'pt=2'; //Correct PT

//IDX Broker API Keys
//-------------------------------------
$accesskey = $_POST['apiKey']; //Customer Key
$ancillarykey = $_POST['ancillary']; //Partner Key (Optional)

//Other VARIABLES
//--------------------------------------
$showOnly = $_POST['showOnly'];
$dryRunComplete = $_POST['dryRun'];
$numToRun = $_POST['numToRun'];
$startKey = $_POST['startAtNum'];
$firstRun = $_POST['firstRun'];

//Gather Saved Links
//-------------------------------------
$response = apiCall('https://api.idxbroker.com/clients/savedlinks','GET','',$accesskey);


echo "-->".$startKey."<--<p>";

//Show Form
//-------------------------------------
if (!isset($dryRunComplete)){
	$runType = dry;
	inputForm($ancillarykey,$accesskey,$runType,$startKey,$numToRun);
}elseif(!isset($firstRun)){
	$runType = realRun;
	inputForm($ancillarykey,$accesskey,$runType,$startKey,$numToRun);
}


//Decode the List of Saved Links
$savedLinksDecoded = json_decode($response, true);

$counter = 1; //Counting Inner Loop

//Die Gracefully if no API Key
if (empty($accesskey)){
	echo "<font color=red>You Must have an API Key to Run.</font>";
}else{
	
//==============================================================================
//====================================================== MAIN FOR EACH LOOP ====
//==============================================================================
//Loop Through the Saved Links
foreach ($savedLinksDecoded as $key => $value){

echo "Key---->".$key."<p>";
echo "Start Key-->".$startKey."<p>";
echo "numToRun-->".$numToRun."<p>";

//Get the Saved Links with - in the Name (Created By Migration Script)
if (strpos($value[linkName],'-') == true){
	
	//Split the Saved Links to Get Original Name
	$splitLinkName = preg_split("/-/", $value[linkName]);
	$stopKey = $key;
	echo "<b><font color=blue>".$counter."(".$stopKey.") - </font></b>";
	echo "<b>Link Name & ID:</b> ".$splitLinkName[0].": ";	
	
	
	//Now, I need the Saved Link ID of the Original Link
	foreach ($savedLinksDecoded as $key => $value){
	
	//Now we get the ID of the Original Saved Link
	if ($value[linkName] == $splitLinkName[0]){
		echo $value[id]."<br>";
		
		
		$updatedQueryString = str_replace($wrongPt,$correctPt,$value[queryString]);
	 
		echo "<b>Original Query:</b> ".$value[queryString]."<br>";
		echo "<b>New Query:</b> ".$updatedQueryString."<p>";
	
		//Explode the Query String into an Array
		$myQueryString = explode("&",$updatedQueryString);
		
		//Instantiate the Final Array
		$completedQueryString = array();
		
		//Loop Through array to get proper key/value pairs
		//Create the final array for the API Query
		foreach ($myQueryString as $Qkey => $Qvalue){
			$splitThisValue = preg_split("/=/", $Qvalue);
					
			if ($splitThisValue[0] != "page"){
			$completedQueryString[$splitThisValue[0]] = $splitThisValue[1];
			}
			
			
		}
	
//This is where the work happens, updating the saved links with the correct Pt
//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	
if ($_POST["dryRun"] == "run") {

//Data string for the API Call to change PT		
$data = array('queryString' => $completedQueryString);
$data = http_build_query($data); 

//API Call to Change the PT of this Saved Link
$apiUrl = "https://api.idxbroker.com/clients/savedlinks/".$value[id];
$updateSavedLink = apiCall($apiUrl,'POST',$data,$accesskey);		

	}
	
	
	
}//If Statement; getting ID of orig. saved link
}//Internal For Each Loop

echo "Counter State: ".$counter."<p>";



/*
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ Limit the Number of API Calls ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
*/
	
if (isset($dryRunComplete) && $numToRun > 0){
	if ($counter >= $numToRun){
		
		$hour_ago = date('g:iA ', time() - 3600); //When to Start the next run
		$runType = 'doMore';
		$startKey = $stopKey;
		
		echo "<hr>";
		echo "<h3>You Can Run This again at: ".$hour_ago."</h3>";
				
		inputForm($ancillarykey,$accesskey,$runType,$startKey,$numToRun);

		exit("The Next Run Will Start On Key: $stopKey.");//Exit this run with friendly message
	
	}else{echo "All Done";}
	}
//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

$counter++;


}//If Statement; getting links with - in the name




} //Main For Each Loop
} //Parent If/Else Statement
?>
</div>
</body>
</html>
