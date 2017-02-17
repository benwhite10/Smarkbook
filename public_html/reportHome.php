<?php
$include_path = get_include_path();
include_once $include_path . '/includes/db_functions.php';
include_once $include_path . '/public_html/includes/mail_functions.php';
include_once $include_path . '/includes/session_functions.php';
include_once $include_path . '/public_html/classes/AllClasses.php';
include_once $include_path . '/public_html/requests/core.php';
include_once $include_path . '/public_html/includes/htmlCore.php';

sec_session_start();
$resultArray = checkUserLoginStatus(filter_input(INPUT_SERVER, 'REQUEST_URI', FILTER_SANITIZE_STRING));
if ($resultArray[0]) {
    $user = $_SESSION['user'];
    $fullName = $user->getFirstName() . ' ' . $user->getSurname();
    $userid = $user->getUserId();
    $userRole = $user->getRole();
    $userval = base64_encode($user->getValidation());
    $info = Info::getInfo();
    $info_version = $info->getVersion();
} else {
    header($resultArray[1]);
    exit();
}

if (!authoriseUserRoles($userRole, ["SUPER_USER", "STAFF", "STUDENT"])) {
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
$set_student = filter_input(INPUT_GET, 'student', FILTER_SANITIZE_NUMBER_INT);
?>

<!DOCTYPE html>
<html>
    <head lang="en">
        <?php pageHeader("Report", $info_version) ?>
        <link rel="stylesheet" type="text/css" href="css/reportHome.css?<?php echo $info_version; ?>" />
        <link rel="stylesheet" type="text/css" href="pickadate/themes/default.css?<?php echo $info_version; ?>"/>
        <link rel="stylesheet" type="text/css" href="pickadate/themes/default.date.css?<?php echo $info_version; ?>"/>
        <script src="js/tagsList.js?<?php echo $info_version; ?>"></script>
        <script src="js/reportHome.js?<?php echo $info_version; ?>"></script>
        <script src="pickadate/picker.js?<?php echo $info_version; ?>"></script>
        <script src="pickadate/picker.date.js?<?php echo $info_version; ?>"></script>
        <script src="pickadate/legacy.js?<?php echo $info_version; ?>"></script>
        <script src="libraries/spin.js?<?php echo $info_version; ?>"></script>
    </head>
    <body>
        <?php
        echo "<input type='hidden' id='staffid' value='$staffid' />";
        echo "<input type='hidden' id='studentid' value='$studentid' />";
        echo "<input type='hidden' id='set_student' value='$set_student' />";
        echo "<input type='hidden' id='setid' value='$setid' />";
        echo "<input type='hidden' id='start' value='$startdate' />";
        echo "<input type='hidden' id='end' value='$enddate' />";
        setUpRequestAuthorisation($userid, $userval);
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
                <div id="summaryReport" class="sectionWrapper">
                    <div id="summaryReportSpinner" class="spinnerBox">
                    </div>
                    <div id="summaryReportMain" class="innerSectionWrapper top">
                        <div id="worksheetSummaryDetails">
                            <table id="worksheetSummaryDetailsTable">
                                <tbody>
                                    <tr id="tableTitleRow">
                                        <td colspan="2">Worksheets</td>
                                        <td colspan="2">Handed In</td>
                                    </tr>
                                    <tr>
                                        <td>Completed:</td>
                                        <td id="compValue" class="green">-</td>
                                        <td>On Time:</td>
                                        <td id="onTimeValue" class="green">-</td>
                                    </tr>
                                    <tr>
                                        <td>Partially Completed:</td>
                                        <td id="partialValue" class="orange">-</td>
                                        <td>Late:</td>
                                        <td id="lateValue" class="red">-</td>
                                    </tr>
                                    <tr>
                                        <td>Incomplete:</td>
                                        <td id="incompleteValue" class="red">-</td>
                                        <td>Blank:</td>
                                        <td id="dateNoInfoValue" class="grey">-</td>
                                    </tr>
                                    <tr>
                                        <td>Blank:</td>
                                        <td id="compNoInfoValue" class="grey">-</td>
                                        <td> </td>
                                        <td id="showHideWorksheetText" style="text-align: right;">Show Worksheets</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div id="summaryReportUserAvg">
                            <p id="summaryReportUserAvgTitle" class="title">Student Average</p>
                            <h1 id="summaryReportUserAvgValue" class="value"></h1>
                        </div>
                        <div id="summaryReportSetAvg">
                            <p id="summaryReportSetAvgTitle" class="title">Set Average</p>
                            <h1 id="summaryReportSetAvgValue" class="value"></h1>
                        </div>
                    </div>
                    <div id="summaryReportDetails" class="innerSectionWrapper">
                        <div id="new_worksheets_report" class="half_column_report">
                            <div id="new_worksheets_report_title" class="report_column_title">
                                <div id="new_worksheets_report_title_top" class="report_column_title_top">
                                    <h1>Worksheets</h1>
                                </div>
                                <div id="new_worksheets_report_title_bottom" class="report_column_title_bottom">
                                    <input type="hidden" id="new_worksheets_report_criteria">
                                    <input type="hidden" id="new_worksheets_report_order">
                                    <div id="new_worksheets_report_criteria_title" class="new_worksheets_report_criteria" onclick="changeWorksheetsCriteria()"></div>
                                    <div id="new_worksheets_report_order_title" class="new_worksheets_report_order" onclick="changeWorksheetsOrder()"></div>
                                </div>
                            </div>
                            <div id="new_worksheets_report_main" class="report_column_main"></div>
                        </div>
                        <div id="new_worksheet_report" class="half_column_report">
                            <div id="new_worksheet_report_title" class="report_column_title">
                                <div id="new_worksheet_report_title_top" class="report_column_title_top">
                                    <div id="section_questions" class="title_sections selected" onclick="changeSection('section_questions')"><h1>Questions</h1></div>
                                    <div id="section_tags" class="title_sections" onclick="changeSection('section_tags')"><h1>Tags</h1></div>
                                    <div id="section_blank_1" class="title_sections nosection"></div>
                                    <div id="section_blank_2" class="title_sections nosection" style="border-right: none"></div>
                                </div>
                                <div id="new_worksheet_report_title_bottom" class="report_column_title_bottom">
                                    <input type="hidden" id="new_worksheet_report_criteria">
                                    <input type="hidden" id="new_worksheet_report_order">
                                    <div id="new_worksheet_report_criteria_title" class="new_worksheets_report_criteria"><h2></h2></div>
                                    <div id="new_worksheet_report_order_title" class="new_worksheets_report_order"></div>
                                </div>
                            </div>
                            <div id="new_worksheet_report_main" class="report_column_main">
                                <div id="new_worksheet_placeholder">
                                    <p>Click on a worksheet to view the details for that worksheet.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div id="new_tags_report" class="fullSection">
                    <div id="new_tags_report_spinner" class="spinnerBox">
                    </div>
                    <div id="new_tags_report_main">
                        <div class="new_tags_report_titles">
                            <div class="new_tags_report_title">
                                <div class="new_tags_report_title_top">
                                    <h1>Classification Tags</h1>
                                </div>
                                <div class="new_tags_report_title_bottom">
                                    <input type="hidden" id="classification_criteria">
                                    <input type="hidden" id="classification_order">
                                    <div id="tags_report_criteria_classification" class="new_tags_report_title_bottom_criteria" onclick="changeCriteria(1)">
                                    </div>
                                    <div id="tags_report_order_classification" class="new_tags_report_title_bottom_order" onclick="changeOrder(1)">
                                    </div>
                                </div>
                            </div>
                            <div class="new_tags_report_title">
                                <div class="new_tags_report_title_top">
                                    <h1>Major Tags</h1>
                                </div>
                                <div class="new_tags_report_title_bottom">
                                    <input type="hidden" id="major_criteria">
                                    <input type="hidden" id="major_order">
                                    <div id="tags_report_criteria_major" class="new_tags_report_title_bottom_criteria" onclick="changeCriteria('2')">
                                    </div>
                                    <div id="tags_report_order_major" class="new_tags_report_title_bottom_order" onclick="changeOrder('2')">
                                    </div>
                                </div>
                            </div>
                            <div class="new_tags_report_title">
                                <div class="new_tags_report_title_top">
                                    <h1>Minor Tags</h1>
                                </div>
                                <div class="new_tags_report_title_bottom">
                                    <input type="hidden" id="minor_criteria">
                                    <input type="hidden" id="minor_order">
                                    <div id="tags_report_criteria_minor" class="new_tags_report_title_bottom_criteria" onclick="changeCriteria('3')">
                                    </div>
                                    <div id="tags_report_order_minor" class="new_tags_report_title_bottom_order" onclick="changeOrder('3')">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="new_tags_report_sections">
                            <div id="new_tags_report_classification" class="tags_report_section"></div>
                            <div id="new_tags_report_major" class="tags_report_section"></div>
                            <div id="new_tags_report_minor" class="tags_report_section"></div>
                        </div>
                    </div>
                </div>
                
                <div id="report_notes" class="fullSection">
                    <div id="report_notes_spinner" class="spinnerBox">
                    </div>
                    <div id="report_notes_main">
                        <div class="report_notes_title">
                            <h1>Report Notes</h1>
                        </div>
                        <div id="report_notes_notes"></div>
                    </div>
                </div>
                
                <div id="tagsReport" class="fullSection" style="display:none">
                    <div id="tagsReportSpinner" class="spinnerBox">
                    </div>
                    <div id="tagsReportSummary" class="sectionMain" style="display: none;">
                        <h4 style="text-align: right;" onclick="showHideFullTagResults()" id="showHideFullTagResultsText">Show Full Results</h4>
                    </div>
                    <div id="tagsReportShort" style="display: none;">
                        <table class="results half" id="top5tags" style="border-right: solid thin #323232;">
                            <thead class="results">
                                <tr class="results">
                                    <th colspan="4" class="results">Hot Topics</th>
                                </tr>
                                <tr class="results">
                                    <th class="results" style="width: 99%; text-align: left; padding-left: 10px;">Tag</th>
                                    <th class="results" style="min-width: 100px">Avg. Score</th>
                                    <th class="results" style="min-width: 100px">Last 5 questions</th>
                                    <th class="results" style="min-width: 100px">No. of Questions</th>
                                </tr>
                            </thead>
                            <tbody class="results">
                            </tbody>
                        </table>
                        <table class="results half" id="bottom5tags">
                            <thead class="results">
                                <tr class="results">
                                    <th colspan="4" class="results">Areas To Work On</th>
                                </tr>
                                <tr class="results">
                                    <th class="results" style="width: 99%; text-align: left; padding-left: 10px;">Tag</th>
                                    <th class="results" style="min-width: 100px">Avg. Score</th>
                                    <th class="results" style="min-width: 100px">Last 5 questions</th>
                                    <th class="results" style="min-width: 100px">No. of Questions</th>
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
                                    <th class="results" style="width: 99%; text-align: left; padding-left: 10px;">Tag</th>
                                    <th class="results" style="min-width: 100px">Avg. Score</th>
                                    <th class="results" style="min-width: 100px">Last 5 questions</th>
                                    <th class="results" style="min-width: 100px">No. of Questions</th>
                                </tr>
                            </thead>
                            <tbody class="results">
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div id="questionsReport" class="sectionWrapper" style="display:none">
                    <div id="questionsReportSpinner" class="spinnerBox" style="display:none;">
                    </div>
                    <div id="questionsReportMain" class="innerSectionWrapper top">
                        <div id="questionsSummaryDetails">
                            <table id="questionsSummaryDetailsTable">
                                <thead>
                                    <tr>
                                        <th>No.</th>
                                        <th class="worksheetName" style="min-width: 400px;">Worksheet</th>
                                        <th class="worksheetName" style="max-width: 400px;">Tags</th>
                                        <th>Marks</th>
                                        <th>Last Completed</th>
                                        <th>Score</th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <?php pageFooter($info_version) ?>
        </div>
    </body>


