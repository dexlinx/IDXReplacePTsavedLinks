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
</style>
</head>
<body>

<?php

/*
Use Case Example:
- Several saved links set as Condo or other property type (On V1)
- Migration script split those and set the Original saved links to Residential (Wrong PT)
- Now we have the original saved links (with correct naming) with the wrong property type
- And, the new saved links with the wrong names and correct property types 
=> Search for any saved links with -propertytype in the name
=> Get the Coresponding saved links without -propertytype and gather it's Query String and ID
=> Update the Query String of the Original Saved links with Correct Property Type
*/
//Set the Property Types We're dealing with
/* Wrong PT */ $wrongPt = 'pt=1';
/* Correct PT */ $correctPt = 'pt=2';

//IDX Broker API Key
$accesskey = $_POST['apiKey'];

//API Call CURL code
//--------------------------------------------------------------------
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
    "apiversion: {{apiversion}}",
    "cache-control: no-cache",
    "content-type: application/x-www-form-urlencoded",
    "outputtype: {{outputtype}}",
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
//--------------------------------------------------------------------
$response = apiCall('https://api.idxsandbox.com/clients/savedlinks','GET','',$accesskey);


//Form to perform Dry-Run (No Updates)
?>
<div class="mainContent">
<b>Note:</b> Leave un-checked to see the results w/o updating links. Check when you're ready to update your saved links.<p>
<form action="replacePt.php" method="post">
  API Key: <input type="text" name="apiKey" value='<?php echo $_POST['apiKey']; ?>'><br>
  <input type="checkbox" name="dryRun" value="run">Run<br>
  <input type="submit" value="Submit">
</form> 
<hr>

<?php

echo $_POST["dryRun"]."<p>";

//Decode the List of Saved Links
$savedLinksDecoded = json_decode($response, true);

$counter = 1;

?>



<?php
//Die Gracefully if no API Key
if (empty($_POST['apiKey'])){
	echo "<font color=red>Please Enter Your API Key</font>";
}else{
//Loop Through the Saved Links
foreach ($savedLinksDecoded as $key => $value){



//Get the Saved Links with - in the Name (Created By Migration Script)
if (strpos($value[linkName],'-') == true){
	
	//Split the Saved Links to Get Original Name
	$splitLinkName = preg_split("/-/", $value[linkName]);
	echo "<b><font color=blue>".$counter++." - </font></b>";
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
		
if ($_GET["dryRun"] == "run") {

//Data string for the API Call to change PT		
$data = array('queryString' => $completedQueryString);
$data = http_build_query($data); 

//API Call to Change the PT of this Saved Link
$apiUrl = "https://api.idxsandbox.com/clients/savedlinks/".$value[id];
$updateSavedLink = apiCall($apiUrl,'POST',$data,$accesskey);		

	}
	}
}
}
}
}
?>
</div>
</body>
</html>
