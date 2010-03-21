<?

// register.php
/* needs to:
	X validate input
	- sanitize input (phone number)
	X check if group exists (?)
	X insert into db
	X return success to browser
	- email confirmation
*/

$response = array("status" => "");

function str_contains($haystack, $needle, $ignoreCase = false) {
    if ($ignoreCase) {
        $haystack = strtolower($haystack);
        $needle   = strtolower($needle);
    }
    $needlePos = strpos($haystack, $needle);
    return ($needlePos === false ? false : ($needlePos+1));
}


function set_error($error_type, $error_message, $fields) {	
	global $response;
	$response['status'] = 'error';
	if (!is_array($response['errors'])) {
		$resposne['errors'] = array();
	}
	$response['errors'][] = array("error_type" => $error_type, "error_message" => $error_message, "fields" => $fields);
}

// grab the POST vars and stash in $doc
$doc = $_POST;
$doc['registration_date'] = new MongoDate();

$required_fields = array(
	"organization" => "Organization", 
	"group" => "Group Name",
	"contactname" => "Leader in Charge", 
	"streetaddress" => "Street Address", 
	"city" => "City", 
	"postalcode" => "Province", 
	"phone" => "Phone Number",
	"email" => "Email Address",
	"camping_youth" => "Number of youth camping", 
	"camping_adults" => "Number of adults camping",
	"agree_to_terms" => "Agree to terms checkbox"
);

$numeric_fields = array(
	"camping_youth" => "Number of youth camping", 
	"camping_adults" => "Number of adults camping",
	"parade_lunch_youth" => "Number of parade/lunch/badge-only youth",
	"parade_lunch_adults" => "Number of parade/lunch/badge-only adults"
);

// if organization !scouts_canada, unset council and area from $doc
if ($doc['organization'] != "scouts_canada") {
	unset($doc['council']);
	unset($doc['area']);
} else if ($doc['organization'] == "scouts_canada") {
	$required_fields['council'] = "Council";
	$required_fields["area"] = "Area";
}

// check for empty or non-existant required fields
$empty_fields = array();
foreach ($required_fields as $key => $value) {
	if (!$doc[$key] || $doc[$key] == "") {
		$empty_fields[$key] = $value;
	}
}
if (count($empty_fields) > 0) {
	set_error("missing_fields", "The following required fields were not completed: ", $empty_fields);
}

// check for non-numeric values in numeric-only fields
$non_numeric_fields = array();
foreach ($numeric_fields as $key => $value) {
	if ( ($doc[$key] != "") && (!is_numeric($doc[$key])) ) {
		$non_numeric_fields[$key] = $value;
	}
}
if (count($non_numeric_fields) > 0) {
	set_error("non_numeric_fields", "The following fields require a numeric value: ", $non_numeric_fields);
}

if ($response['status'] == "") {
	$m = new Mongo();
	$c = $m->hyack->registration;
	$insert = $c->insert($doc);
	$e = $m->hyack->lastError();
	$errmsg = $e['err'];
	$isdup = str_contains($errmsg, "E11000");
	
	if ( $isdup ) {
		set_error('duplicate_group', "A group named <strong>" . $doc['group'] . "</strong> is already registered. Please contact <a href=\"mailto:hyack@newwestscouts.ca\">hyack@newwestscouts.ca</a> if you need to modify your registration.", null);
	} else if ($errmsg == "") {
		$response['status'] = 'success';
		$response['message'] = 'done';
		// send email to user and hyack@newwestscous.ca confirming registration
	} else if (!$isdup && $errmsg != "") {
		set_error('unknown_error', "An unknown error has occurred while attempting to save your registration information. The camp organizers have been informed of the error and will follow up with you to complete your registration.", null);
		$docjson = json_encode($doc);
		// send email to hyack@newwestscouts.ca with error message and json doc
	}
}

if ($_SERVER['HTTP_X_REQUESTED_WITH'] == "XMLHttpRequest"){
	echo json_encode($response);
} else {
	// make fancy page here
}




?>