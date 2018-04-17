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

if (!authoriseUserRoles($userRole, ["SUPER_USER", "STAFF"])) {
    header("Location: unauthorisedAccess.php");
    exit();
}

if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
}

$query = "SELECT T.`Name` Name, T.`Tag ID` ID FROM TSTOREDQUESTIONS S JOIN TQUESTIONTAGS Q ON S.`Stored Question ID` = Q.`Stored Question ID` JOIN TTAGS T ON Q.`Tag ID` = T.`Tag ID` GROUP BY T.`Name` ORDER BY COUNT(T.`Name`) DESC, T.`Name`; ";
try{
    $alltags = db_select_exception($query);
} catch (Exception $ex) {
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
        <?php pageHeader("Report", $info_version) ?>
        <link rel="stylesheet" type="text/css" href="css/reportHome.css?<?php echo $info_version; ?>" />
        <link rel="stylesheet" type="text/css" href="css/setReport.css?<?php echo $info_version; ?>" />
        <link rel="stylesheet" type="text/css" href="pickadate/themes/default.css?<?php echo $info_version; ?>"/>
        <link rel="stylesheet" type="text/css" href="pickadate/themes/default.date.css?<?php echo $info_version; ?>"/>
        <link rel="stylesheet" type="text/css" href="css/autocomplete.css?<?php echo $info_version; ?>"  />
        <script src="js/jquery-ui.js?<?php echo $info_version; ?>"></script>
        <script src="js/tagsList.js?<?php echo $info_version; ?>"></script>
        <script src="js/setReport.js?<?php echo $info_version; ?>"></script>
        <script src="pickadate/picker.js?<?php echo $info_version; ?>"></script>
        <script src="pickadate/picker.date.js?<?php echo $info_version; ?>"></script>
        <script src="pickadate/legacy.js?<?php echo $info_version; ?>"></script>
        <script src="libraries/spin.js?<?php echo $info_version; ?>"></script>
    </head>
    <body>
        <?php
        echo "<input type='hidden' id='staffid' value='$staffid' />";
        echo "<input type='hidden' id='studentid' value='$studentid' />";
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
                <?php navbarMenu($fullName, $userid, $userRole) ?>
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
                            </div>
                        </div><div id="variablesInputBoxButtons" class="sectionSummaryButtons">
                            <div id="generateReportButton" onclick="generateReport()">Generate Report</div><div id="printReportButton" onclick="printReport()">Print Report</div>
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
                                        <select name="set" id="set">
                                            <option value="0">No Sets</option>
                                        </select>
                                    </td>
                                </tr>
                                <tr class="form">
                                    <td class="form" colspan="2">
                                        <textarea name="tags" id="tags" class="autocomplete" placeholder="Enter the tags which will be included in your report" ></textarea>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </form>
                <div id="main_tables">
                </div>
            </div>
            <?php pageFooter($info_version) ?>
        </div>
        <script>
        var availableTags = [];
        <?php
            foreach ($alltags as $tag){
                print 'availableTags.push("' . $tag['Name'] . '");';
            }
        ?>

        var allTagNames = [];
        var allTagIds = [];
        <?php
            foreach ($alltags as $tag){
                print 'allTagNames.push("' . $tag['Name'] . '");';
                print 'allTagIds.push("' . $tag['ID'] . '");';
            }
        ?>

        function split( val ) {
          return val.split( /,\s*/ );
        }
        function extractLast( term ) {
          return split( term ).pop();
        }

        $( ".autocomplete" )
        // don't navigate away from the field on tab when selecting an item
        .bind( "keydown", function( event ) {
            if ( event.keyCode === $.ui.keyCode.TAB && $( this ).autocomplete( "instance" ).menu.active ) {
                event.preventDefault();
            }
        })

        .autocomplete({
            minLength: 0,
            source: function( request, response ) {
                // delegate back to autocomplete, but extract the last term
                response( $.ui.autocomplete.filter(availableTags, extractLast( request.term ) ) );
            },
            focus: function() {
                // prevent value inserted on focus
                return false;
            },
            select: function( event, ui ) {
                var terms = split( this.value );
                // remove the current input
                terms.pop();
                // add the selected item
                terms.push( ui.item.value );
                // add placeholder to get the comma-and-space at the end
                terms.push( "" );
                this.value = terms.join( ", " );
                return false;
            }
        });

        </script>
    </body>
