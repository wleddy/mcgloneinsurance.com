// javascript for contractor-list page

function setDownloadDate(ID){
	// insert the current date in the last download date field for this item
	//alert('#' + ID + '_Ddate');
	d = new Date;
	$('#' + ID + '_Ddate').text(d.toLocaleDateString());
	return true;
}