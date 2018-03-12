<?php
/*
	BL - 3/10/2018
	this script is used to import the current list of Contractor License applications.
	
	getFile(string fileURI) downloads the txt file from CSLB site, converts it to csv format and downloads it to user

	The txt files are fixed length files. There may be two field layouts used in the same file.
	getDBALayout() returns an array used to interpret one line of data for Contractors
	getHILayout() returns an array used to interpret one line of data for Home Improvement Salespeople
*/

/*
	Settins.php exposes:
		$targetDomain = "http://www.cslb.ca.gov";
		$targetListURL = $targetDomain . "/Consumers/Data.aspx";
		$lastDownloaded = 'downloads.json';
*/
if((@include "settings.php") === false)
{
	echo("<h2>Could not load Settings file</h2><p>Contact developer for help.</p>");
	exit;
}


function getDBALayout(){
	/*
	The rows for Contractor Licenses have this layout: 
	The field APPT-TP != H to indicate this standard class
	The array values are (Field Length, Include in output?)
	
	this array also determines the column order for the final output
	*/
	return array(
		"APPT-FEE-NUM"  => array(11,true),
		"APP-TP"  => array(1,false),
		"APP-ENTITY-CDE"  => array(2,false),
		"APP-EXAM-WAIVER-CDE"  => array(1,false),
		"PRINT-NAME"  => array(65,true),
		"APP-DBA-NAME"  => array(65,true),
		"APP-NAME"  => array(65,true),
		"LIC-NUM"  => array(8,true),
		"APP-ADDR1"  => array(30,true),
		"APP-ADDR2"  => array(30,true),
		"APP-CITY"  => array(25,true),
		"APP-STATE"  => array(2,true),
		"APP-ZIP"  => array(9,true),
		"APP-CNTRY"  => array(25,false),
		"FILLER1"  => array(10,false),
		"APP-CLASSES" => array(90,false),
		"FILLER2" => array(1,false),
		"APP-POST-DT"  => array(8,true),
		"EXAMEE-LNAME"  => array(35,true),
		"EXAMEE-FNAME"  => array(15,true),
		"EXAMEE-MNAME"  => array(12,true),
		"EXAMEE-SFX"  => array(3,true),
		"APP-EXAMEE-CLASS"  => array(3,false),
	);
}

function getHILayout(){
	/*
	The rows for Home Improvement Salesperson have a different layout: 
	The field APPT-TP == H to indicate this class
	The array values are (Field Length, Include in output?)
	*/
	return array(
		"APPT-FEE-NUM"  => array(11,true),
		"APP-TP"  => array(1,false),
		"APP-ENTITY-CDE"  => array(2,false),
		"APP-EXAM-WAIVER-CDE"  => array(1,false),
		"PRINT-NAME"  => array(65,true),
		"EXAMEE-LNAME"  => array(35,true),
		"EXAMEE-FNAME"  => array(15,true),
		"EXAMEE-MNAME"  => array(12,true),
		"EXAMEE-SFX"  => array(3,true),
		"APP-NAME" => array(65,true),
		"LIC-NUM"  => array(8,true),
		"APP-ADDR1"  => array(30,true),
		"APP-ADDR2"  => array(30,true),
		"APP-CITY"  => array(25,true),
		"APP-STATE"  => array(2,true),
		"APP-ZIP"  => array(9,true),
		"APP-CNTRY"  => array(25,false),
		"FILLER1"  => array(10,false),
		"APP-CLASSES" => array(90,false),
		"FILLER2" => array(1,false),
		"APP-POST-DT"  => array(8,true),
		"EMPTY1"  => array(35,false),
		"EMPTY2"  => array(15,false),
		"EMPTY3"  => array(12,false),
		"EMPTY4"  => array(3,false),
		"APP-EXAMEE-CLASS"  => array(3,false),
	);
}

function getFile($filePath){
	if(is_string($filePath)){
		$newFilename = dirname(__FILE__) . "/" . basename($filePath,".txt").'.csv';
	} else {
		echo "<h2>Bad request URL</h2>";
		return false;
	}
	// get the dates that files were downloaded
	global $lastDownloaded;
	$downloadDates = json_decode(file_get_contents($lastDownloaded),True);
	if($downloadDates === NULL){
		$downloadDates = array();
	}

	global $targetDomain;
	$targetFile = $targetDomain . $filePath;
	//echo('target = ' . $targetFile . '<br>');
	$in = file($targetFile);
	//var_dump( $in);
	if(!$in === FALSE){
		// update the json file to show that this file was downloaded
		date_default_timezone_set('America/Los_Angeles'); // set to our local time
		$downloadDates[basename($filePath)] = date('n/j/Y');
		file_put_contents($lastDownloaded,json_encode($downloadDates));
		
		// compile data in arrays
		$data = array();
		foreach($in as $row => $line) {
			// test for blank line (no Fee num) and filter the ^Z that ends the file
			if(rtrim(substr($line, 0, 11)) != "" && ord($line) > 31){
				// select the appropriate column layout
				if(substr($line,11,1)=="H"){
					// Home improvement Sales
					$columns = getHILayout();
				} else {
					$columns = getDBALayout();
				}
				$pos = 0;
				$dataRow = array();
				foreach ($columns as $colKey => $colSize) {
					if($colSize[1]){
						// only include some columns
						$dataRow[$colKey] = rtrim(substr($line, $pos, $colSize[0]));
					}
					$pos += $colSize[0];
				}
				// add row to data
				$data[] = $dataRow;
			}
		}
		
		// create the output text
		$out = "";
		// output header row
		$columns = getDBALayout();
		foreach ($columns as $colKey => $colSize) {
			if($colSize[1]){
				$out = $out . $colKey .',';
			}
		}
		// remove trailing comma and add return
		$out = substr($out,0, -1);
		$out = $out . "\n";
		
		//output the data
		foreach($data as $row => $line) {
			foreach ($columns as $colKey => $colSize) {
				if(!array_key_exists($colKey,$line)){
					$line[$colKey] = "";
				}
				if($colSize[1]){
					$out = $out . $line[$colKey] . ',';
				}
			}
			// remove trailing comma and add return
			$out = substr($out,0, -1) . "\n";;
		}
		
		// Finally, remove the final return
		$out = substr($out,0, -1);
		return $out;
		
	}
	return false; // Failure
}

$filePath = rtrim($_GET["ref"]);
if($filePath !== null && $filePath != ""){
	$out = getFile($filePath);
	if($out === false){
		echo "<h2>File could not be retrived from host</h2>";
		exit;
	}
	//echo $out;
	$newFilename = basename($filePath,".txt").'.csv';
	
    header('Content-Type: "text/plain"');
    header('Content-Disposition: attachment; filename="'. $newFilename . '"');

	echo  $out;

} else {
	echo "<h2>Bad File Name</h2>";
	exit;
}
?>