<?php
$include_path = get_include_path();
include_once $include_path . '/includes/db_functions.php';
include_once $include_path . '/public_html/includes/mail_functions.php';
include_once $include_path . '/includes/session_functions.php';
include_once $include_path . '/public_html/classes/AllClasses.php';
include_once $include_path . '/public_html/requests/core.php';

sec_session_start();
$resultArray = checkUserLoginStatus(filter_input(INPUT_SERVER, 'REQUEST_URI', FILTER_SANITIZE_STRING));
if ($resultArray[0]) {
    $user = $_SESSION['user'];
    $fullName = $user->getFirstName() . ' ' . $user->getSurname();
    $userid = $user->getUserId();
    $userRole = $user->getRole();
} else {
    header($resultArray[1]);
    exit();
}

if (!authoriseUserRoles($userRole, ["SUPER_USER", "STAFF"])) {
    header("Location: unauthorisedAccess.php");
    exit();
}

if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
}

$staffid = filter_input(INPUT_GET, 'staff', FILTER_SANITIZE_NUMBER_INT);
$studentid = filter_input(INPUT_GET, 'stu', FILTER_SANITIZE_NUMBER_INT);
$setid = filter_input(INPUT_GET, 'set', FILTER_SANITIZE_NUMBER_INT);
$startdate = filter_input(INPUT_GET, 'start', FILTER_SANITIZE_STRING);
$enddate = filter_input(INPUT_GET, 'end', FILTER_SANITIZE_STRING);
?>

<!DOCTYPE html>
<html>
    <head lang="en">
        <meta charset="UTF-8">
        <title>Smarkbook</title>
        <meta name="description" content="Smarkbook" />
        <meta name="keywords" content="Intelligent, personalised feedback through smart data analysis" />
        <meta http-equiv="content-type" content="text/html; charset=utf-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=9" />
        <!--<link rel="stylesheet" media="screen and (min-device-width: 668px)" type="text/css" href="css/branding.css" />-->
        <link rel="stylesheet" type="text/css" href="css/branding.css" />
        <link rel="stylesheet" type="text/css" href="css/reportHome.css" />
        <link href="css/autocomplete.css" rel="stylesheet" />
        <link rel="stylesheet" type="text/css" href="pickadate/themes/default.css"/>
        <link rel="stylesheet" type="text/css" href="pickadate/themes/default.date.css"/>
        <script src="js/jquery.js"></script>
        <script src="js/tagsList.js"></script>
        <script src="js/methods.js"></script>
        <script src="js/moment.js"></script>
        <script src="js/reportHome.js"></script>
        <script src="pickadate/picker.js"></script>
        <script src="pickadate/picker.date.js"></script>
        <script src="pickadate/legacy.js"></script>
        <script src="libraries/spin.js"></script>
        <link rel="shortcut icon" href="branding/favicon.ico" />
        <link href='https://fonts.googleapis.com/css?family=Open+Sans:400,300,300italic,400italic,700,700italic' rel='stylesheet' type='text/css'>
    </head>
    <body>
        <?php
        echo "<input type='hidden' id='staffid' value='$staffid' />";
        echo "<input type='hidden' id='studentid' value='$studentid' />";
        echo "<input type='hidden' id='setid' value='$setid' />";
        echo "<input type='hidden' id='start' value='$startdate' />";
        echo "<input type='hidden' id='end' value='$enddate' />";
        ?>
        <div id="main">
            <div id="header">
                <div id="title">
                    <a href="index.php"><img src="branding/mainlogo.png"/></a>
                </div>
                <ul class="menu topbar">
                    <li>
                        <a href="portalhome.php"><?php echo $fullName; ?> &#x25BE</a>
                        <ul class="dropdown topdrop">
                            <li><a href="portalhome.php">Home</a></li>
                            <li><a <?php echo "href='editUser.php?userid=$userid'"; ?>>My Account</a></li>
                            <li><a href="includes/process_logout.php">Log Out</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
            <div id="body">
                <?php
                if (isset($message)) {
                    $type = $message->getType();
                    $string = $message->getMessage();
                    if ($type == "ERROR") {
                        $div = 'class="error"';
                    } else if ($type == "SUCCESS") {
                        $div = 'class="success"';
                    }
                } else {
                    $div = 'style="display:none;"';
                }
                ?>

                <div id="message" <?php echo $div; ?>>
                    <div id="messageText"><p><?php if (isset($string)) {
                    echo $string;
                } ?></p>
                    </div><div id="messageButton" onclick="closeDiv()"><img src="branding/close.png"/></div>
                </div>

                <form id="variablesInput" class="fullSection" style="border: none;" action="" method="POST">      
                    <div id="variablesInputBox" class="sectionSummary">
                        <div id="variablesInputBoxDetails" class="sectionSummaryDetails">
                            <div id="variablesInputBoxDetailsText" class="sectionSummaryDetailsText">
                                <h2 onclick="showHideButton('variablesInputMain', 'variablesInputBoxShowHideButton')" id="variablesInputBoxDetailsTextMain"></h2>
                            </div><div id="variablesInputBoxShowHideButton" class="sectionSummaryShowHideButton  minus">
                            </div>
                        </div><div id="variablesInputBoxButtons" class="sectionSummaryButtons">
                            <input id="generateReportButton" type="submit" value="Generate Report" onclick="return generateReport()"/>
                        </div>
                    </div>
                    <div id="variablesInputMain" class="sectionMain">
                        <table class="form">
                            <tbody class="form">
                                <tr class="form">
                                    <td class="form">
                                        <select name="staff" id="staff" onchange="updateSets()">
                                            <option value="0">No Teachers</option>
                                        </select>
                                    </td>
                                    <td class="form">
                                        <select name="set" id="set" onchange="updateStudents()">
                                            <option value="0">No Sets</option>
                                        </select>
                                    </td>
                                    <td class="form">
                                        <select name="student" id="student" onchange="studentChange()">
                                            <option value="0">No Students</option>
                                        </select>
                                    </td>
                                </tr>
                                <tr class="form">
                                    <td class="form" colspan="4">
                                        <div class="dateLeft">
                                            <label for="date">Start Date:</label><!--
                                            --><input type="text" name="startDate" id="startDate" class="datepicker" placeholder="DD/MM/YYYY" />
                                        </div><div class="dateRight">
                                            <label for="date">End Date:</label><!--
                                            --><input type="text" name="endDate" id="endDate" class="datepicker" placeholder="DD/MM/YYYY" />
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </form>
                <div id="noResults">
                    <h1>No results to display</h1>
                    <p>If you are expecting results then please check the start and end date for this set of results</p>
                </div>
                <div id="tagsReport" class="fullSection">
                    <div id="tagsReportSpinner" class="spinnerBox">
                    </div>
                    <div id="tagsReportSummary" class="sectionMain" style="display: none;">
                        <h4 style="text-align: right;" onclick="showHideFullTagResults()" id="showHideFullTagResultsText">Show Full Results</h4>
                    </div>
                    <div id="tagsReportShort" style="display: none;">
                        <table class="results half" id="top5tags" style="border-right: solid thin #323232;">
                            <thead class="results">
                                <tr class="results">
                                    <th colspan="4" class="results">Top 5 Tags</th>
                                </tr>
                                <tr class="results">
                                    <th class="results" style="min-width: 60px">No.</th>
                                    <th class="results" style="width: 99%; text-align: left;">Tag</th>
                                    <th class="results" style="min-width: 100px">Reliability</th>
                                    <th class="results" style="min-width: 100px">Total Marks</th>
                                </tr>
                            </thead>
                            <tbody class="results">
                            </tbody>
                        </table>
                        <table class="results half" id="bottom5tags">
                            <thead class="results">
                                <tr class="results">
                                    <th colspan="4" class="results">Bottom 5 Tags</th>
                                </tr>
                                <tr class="results">
                                    <th class="results" style="min-width: 60px">No.</th>
                                    <th class="results" style="width: 99%; text-align: left;">Tag</th>
                                    <th class="results" style="min-width: 100px">Reliability</th>
                                    <th class="results" style="min-width: 100px">Total Marks</th>
                                </tr>
                            </thead>
                            <tbody class="results">
                            </tbody>
                        </table>
                    </div>
                    <div id="tagsReportFull" style="display: none;">
                        <table class="results fullResults" id="alltags">
                            <thead class="results">
                                <tr class="results">
                                    <th class="results" style="min-width: 60px">No.</th>
                                    <th class="results" style="width: 99%; text-align: left;">Tag</th>
                                    <th class="results" style="min-width: 100px">Reliability</th>
                                    <th class="results" style="min-width: 100px">Total Marks</th>
                                </tr>
                            </thead>
                            <tbody class="results">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </body>


