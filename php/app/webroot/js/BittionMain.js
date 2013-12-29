//$(document).ready(function() { //With this uncommented doesn't work globally
//START SCRIPT

//Check url's action and controller
var url = window.location.pathname;
var urlPaths = url.split('/');
var urlModuleController = ('/' + urlPaths[1] + '/' + urlPaths[2] + '/');
var urlAction = urlPaths[3];
var urlActionValue1 = urlPaths[4];

var globalPeriod = $('#globalPeriod').text();//Check Current Period for validation



//***********************START - Execute MAIN*****************************//
fnBittionSetSelectsStyle();
fnBittionSetTypeDate();
//***********************END - Execute MAIN*****************************//


//Wrap every select control with select2() plugin, if select created dynamically still needs to be binded on a success ajax call
function fnBittionSetSelectsStyle() {
	if ($('#currentDeviceType').text() === 'computer') {
		$('select').select2();
	}
}

function fnBittionSetTypeDate(){
//	if ($('#currentDeviceType').text() === 'computer') {
		$('.input-date-type').prop('type', 'text');
		$('.input-date-type').datepicker({ showButtonPanel: true});
		$('.input-date-type-months').datepicker({ showButtonPanel: true,viewMode: "months"});
		$('.input-date-type-years').datepicker({ showButtonPanel: true,viewMode: "years"});
		$('.input-date-type, .input-date-type-months, .input-date-type-years').keydown(function(event){event.preventDefault();});
//	}else{
//	//doesn't work because depends of the browser language and the format changes and appear errors
//	//I think one solution could be to put a mask dd/mm/yyyy in the future
//		$('.input-date-type, .input-date-type-months, .input-date-type-years').prop('type', 'date'); 
//	}
	
}

//'class'=>'input-date-type' 
//END SCRIPT	
//});

