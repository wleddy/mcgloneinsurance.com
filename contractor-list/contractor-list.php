
<?php
/*
	BL - 3/10/2018
	this script is used to import the current list of Contractor License applications.
	
	getList($targetListURL) fetches the current list from the State Contractor License board web site for display
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

function pruneDownloadDates($fileNameList){
	global $lastDownloaded;
	$downloadDates = json_decode(file_get_contents($lastDownloaded),True);
	if($downloadDates === NULL){
		$downloadDates = array();
	}
	$temp2 = $downloadDates;
	foreach ($temp2 as $key => $value) {
		if(!in_array($key,$fileNameList)){
			unset($downloadDates[$key]);
		}
	}
	file_put_contents($lastDownloaded,json_encode($downloadDates));
	
}

function getList($targetListURL){
	// download a part of the page from the license board and present our version to our users
	$content = file_get_contents($targetListURL);
	// load json data
	global $lastDownloaded;
	$downloadDates = json_decode(file_get_contents($lastDownloaded),True);
	if($downloadDates === NULL){
		$downloadDates = array();
	}
	// find the URLs of the links we want. All file names start with "PL"
	preg_match_all('/href=(.*PL.*\.txt)>(PL.*\.txt).*&nbsp;(\d*\/\d+\/\d+)/', $content,$matches,PREG_SET_ORDER);
	arsort($matches); // sort newest to top
	$out = '<tr><th style="width:100pt;">File Name</th><th style="width:100pt;">Posting Date</th><th style="width:100pt;">Last Download</th></tr>';
	foreach ($matches as $key => $value) {
		$out = $out . '<tr><td><a id="' . basename($value[2],'.txt') . '" onclick="setDownloadDate(this.id)" href="downloadFile.php?ref=' . $value[1] . '">' . $value[2] . '</a></td><td>' . $value[3] . '</td>';
		// Get the date las downloaded
		$last = '<em>Never</em>';
		if(array_key_exists($value[2],$downloadDates)){
			$last = $downloadDates[$value[2]];
		}
		$out = $out . '<td id="' . basename($value[2],'.txt') . '_Ddate">' . $last . '</td></tr>' . "\n";
	}
	
	// clean up the download dates json file
	// need to match the values in $matches[?][2] so convert it to a single dimentional array
	$fileNameList = array();
	foreach ($matches as $key => $value) {
		$fileNameList[] = $value[2];
	}
	pruneDownloadDates($fileNameList);
	
	return $out;
}

?>

<h2>Application List</h2>
<table style="margin:10pt auto;">
	<?php  echo getList($targetListURL); ?>
</table>

