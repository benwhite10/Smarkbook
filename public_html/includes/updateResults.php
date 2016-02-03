<?php
$include_path = get_include_path();
include_once '../../includes/db_functions.php';
include_once $include_path . '/public_html/requests/core.php';
include_once $include_path . '/public_html/includes/errorReporting.php';
include_once $include_path . '/includes/session_functions.php';

sec_session_start();
if(isset($_SESSION['user'])){ 
    $user = $_SESSION['user'];
    $userRole = $user->getRole();
    if(!authoriseUserRoles($userRole, ["SUPER_USER", "STAFF"])){
        header("Location: ../unauthorisedAccess.php");
        exit();
    }
}else{
    header("Location: ../unauthorisedAccess.php");
    exit();
}

//$setId = filter_input(INPUT_POST, 'set', FILTER_SANITIZE_NUMBER_INT);
//$staffId = filter_input(INPUT_POST, 'staff', FILTER_SANITIZE_NUMBER_INT);
//$versionId = filter_input(INPUT_POST, 'version', FILTER_SANITIZE_NUMBER_INT);

$newResults = $_POST['resultInput'];
$worksheetColumns = array(
    "gwid",
    "dateDueMain",
    "staff1",
    "staff2",
    "staff3",
    "studentNotes",
    "staffNotes"
);
$worksheetDetails = array();
foreach($worksheetColumns as $variable)
{
    $worksheetDetails[$variable] = $_POST[$variable];
}
$gwid = $worksheetDetails["gwid"];

//Set up completed worksheets
$notes = $_POST["notes"];
$daysLate = $_POST["dates"];
$cwid = $_POST["ids"];
$completionStatus = $_POST["completion"];

$completedWorksheets = array(
    "notes" => $notes,
    "dates" => $daysLate,
    "completion" => $completionStatus,
    "cwid" => $cwid
);

try {
    $postData = array(
        "details" => $worksheetDetails,
        "newResults" => $newResults,
        "compWorksheets" => $completedWorksheets,
        "type" => "UPDATE"
    );

    $postData = array("data" => json_encode($postData));
    $resp = sendCURLRequest("/requests/setWorksheetResult.php", $postData);
    $respArray = json_decode($resp[1], TRUE);
    if(!$respArray["result"]){
        //Failure
        failWithMessage($gwid, $respArray["message"]);
    }
} catch (Exception $ex) {
    $message = "There was an error saving the results, please try again.";
    failWithMessageAndException($gwid, $message, $ex);
}

$message = 'Results succesfully updated.';
completeWithMessage($gwid, $message);

function failWithMessage($gwid, $message)
{
    $type = 'ERROR';
    $_SESSION['message'] = new Message($type, $message);
    header("Location: ../editSetResults.php?gwid=$gwid");
    exit;
}

function failWithMessageAndException($gwid, $message, $ex)
{
    $type = 'ERROR';
    $_SESSION['message'] = new Message($type, $message);
    $exMsg = $ex != null ? $ex->getMessage() : "";
    errorLog($message . " With exception: " . $exMsg);
    header("Location: ../editSetResults.php?gwid=$gwid");
    exit;
}

function completeWithMessage($gwid, $message)
{
    $type = 'SUCCESS';
    $_SESSION['message'] = new Message($type, $message);
    header("Location: ../editSetResults.php?gwid=$gwid");
    exit;
}