//define the lojax object
//with core configuration settings
var lojax =
{
	//path to server-side script
	'action' : './lojax.php',

	//what kind of implementation to use
	//"auto" = use xhr or lojax as supported
	//"lojax" = use lojax for all
	//"none" = use only xhr (disable this script)
	'implementation' : 'auto',

	//client time-out for abandoning a request (seconds)
	'timeout' : 180,

	//expose the courier mechanism
	'expose' : false,

	//try to negate iframe history events
	'negate' : true,

	//lojax function name
	'fn' : 'XMLHttpRequest'
};