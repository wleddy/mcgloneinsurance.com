<?php

// Set some parameters...
$targetDomain = "http://www.cslb.ca.gov";
$targetListURL = $targetDomain . "/Consumers/Data.aspx";
$lastDownloaded = 'downloads.json';
if(!file_exists($lastDownloaded)){touch($lastDownloaded);}
	
?>