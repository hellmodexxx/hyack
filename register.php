<?

// register.php
/* needs to:
	X validate input
	X sanitize input (phone number)
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
$doc['phone'] = ereg_replace("[^0-9]", "", $doc['phone']);

$field_map = array(
	"registration_date" => "Registration Date",
	"organization" => "Organization", 
	"council" => "Council",
	"area" => "Area",
	"group" => "Group Name",
	"contactname" => "Leader in Charge", 
	"streetaddress" => "Street Address", 
	"city" => "City", 
	"prov" => "Province/State",
	"postalcode" => "Postal Code", 
	"phone" => "Phone Number",
	"email" => "Email Address",
	"camping_youth" => "Number of youth camping", 
	"camping_adults" => "Number of adults camping",
	"parade_lunch_youth" => "Number of parade/lunch/badge-only youth",
	"parade_lunch_adults" => "Number of parade/lunch/badge-only adults",
	"total_amount" => "Total amount due"
);

$required_fields = array(
	"organization" => "Organization", 
	"group" => "Group Name",
	"contactname" => "Leader in Charge", 
	"streetaddress" => "Street Address", 
	"city" => "City", 
	"prov" => "Province/State",
	"postalcode" => "Postal Code", 
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
	$m = new Mongo('icat-graham.its.sfu.ca:80');
	$c = $m->hyack->registration;
	$insert = $c->insert($doc);
	$e = $m->hyack->lastError();
	$errmsg = $e['err'];
	$isdup = str_contains($errmsg, "E11000");
	
	if ( $isdup ) {
		set_error('duplicate_group', "A group named <strong>" . $doc['group'] . "</strong> is already registered. Please contact <a href=\"mailto:hyack@newwestscouts.ca\">hyack@newwestscouts.ca</a> if you need to modify your registration.", null);
	} else if ($errmsg == "") {
		$response['status'] = 'success';
		$message = array(
			'<p>Than you for registering for Hyack Camp 2010! A registration confirmation has been sent to ',
			$doc['email'],
			'</p>',
			'<p>If you need to change any registration information or have any questions, please contact us at <a href="mailto:hyack@newwestscouts.ca">hyack@newwestscouts.ca</a></p>',
			'<p>We\'re looking forward to seeing you at Hyack Camp 2010!</p>'
		);
		$response['message'] = join("", $message);
		// send email to user and hyack@newwestscous.ca confirming registration
		$to = $doc['email'];
		$headers = 'From: Hyack Camp 2010 <hyack@newwestscouts.ca>' . "\r\n";
		$headers .= "Bcc: hyack@newwestscouts.ca" . "\r\n";
		$subject = 'Hyack Camp 2010 Registration Confirmation';
		$body = "Thank you for registering your group for Hyack Camp 2010. Your registration information is below. Please review it and let us know if there are any changes to be made (simply reply to this email).\n\n";
		foreach ($doc as $key=>$value) {
			if ($key != "agree_to_terms" || $key != "_id") {
				if ($key == "registration_date") {
					$ts = $value->sec;
					$body .= $field_map[$key] . ": " . date('r', $ts);
				} else {
					$body .= $field_map[$key] . ": " . $value . "\n";
				}
				
			}
		}
		
		$body .= "\n Your total owing amount owing is $" . $doc['total_amount'] . "\n\n";
		$body .= "We look forward to seeing your group at Hyack Camp 2010 on May 28\n\n";
		$body .= "Yours in Scouting,\nHyack Camp 2010\n";
		mail($to, $subject, $body, $headers);
		$response['doc'] = $doc;
	} else if (!$isdup && $errmsg != "") {
		set_error('unknown_error', "An unknown error has occurred while attempting to save your registration information. The camp organizers have been informed of the error and will follow up with you to complete your registration.", null);
		$docjson = json_encode($doc);
		// send email to hyack@newwestscouts.ca with error message and json doc
		$to = "hyack@newwestscouts.ca";
		$headers = 'From: Hyack Camp 2010 <>' . "\r\n";
		$subject = 'Hyack registration error';
		$body = "Something went horribly wrong:\n\nError Message: " . $errmsg . "\n\n$docjson";
		mail($to, $subject, $body, $headers);
	}
}

if ($_SERVER['HTTP_X_REQUESTED_WITH'] == "XMLHttpRequest"){
	echo json_encode($response);
} else {
	// make fancy page here
}




?>
