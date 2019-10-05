<?php
$include_path = get_include_path();
include_once $include_path . '/public_html/classes/AllClasses.php';
include_once $include_path . '/public_html/includes/htmlCore.php';
$info_version = Info::getInfo()->getVersion();
?>

<!DOCTYPE html>
<html>
<head lang="en">
    <?php googleAnalytics(); ?>
    <?php pageHeader("Smarkbook", $info_version); ?>
    <link rel="stylesheet" type="text/css" href="css/editSetResults.css?<?php echo $info_version; ?>" />
    <link href="css/autocomplete.css?<?php echo $info_version; ?>" rel="stylesheet" />
    <link rel="stylesheet" type="text/css" href="pickadate/themes/default.css?<?php echo $info_version; ?>"/>
    <link rel="stylesheet" type="text/css" href="pickadate/themes/default.date.css?<?php echo $info_version; ?>"/>
    <script src='js/jquery-ui.js?<?php echo $info_version; ?>'></script>
    <script src="js/log_events.js?<?php echo $info_version; ?>"></script>
    <script src="js/editSetResults.js?<?php echo $info_version; ?>"></script>
    <script src="libraries/lockablestorage.js?<?php echo $info_version; ?>"></script>
    <script src="pickadate/picker.js?<?php echo $info_version; ?>"></script>
    <script src="pickadate/picker.date.js?<?php echo $info_version; ?>"></script>
    <script src="pickadate/legacy.js?<?php echo $info_version; ?>"></script>
</head>
<body>
    <div id="main">
    	<div id="popUpBackground">
            <div id="popUpBox">
                <div id="popUpBoxMain">
                    <h2 id="popUpName">Name</h2><!--
                    --><h2 id="popUpMarks">Marks</h2>
                    <input type="hidden" value="" id="popUpStudent" />
                    <input type="hidden" value="" id="popUpLate" />
                    <select id="popUpCompletionStatusSelect" onchange="completionStatusChange(this.value)">
                        <option value="Completed">Completed</option>
                        <option value="Partially Completed">Partially Completed</option>
                        <option value="Incomplete" class="incomplete">Incomplete</option>
                        <option value="Not Required">Not Required</option>
                    </select><!--
                    --><select id="popUpDateStatusSelect" onChange="dateStatusChange(this.value, true)">
                        <option value=0> - </option>
                        <option value=1>On Time</option>
                        <option value=2>Late</option>
                    </select>
                    <br>
                    <div id="popUpDateHandedIn">
                        <div class="dateLabel">
                            <p>Handed In</p>
                        </div><div class="dateInput">
                            <select id="day" onChange="dueDateChange()"></select>
                            <select id="month" onChange="dueDateChange()">
                                <option value=1>Jan</option>
                                <option value=2>Feb</option>
                                <option value=3>Mar</option>
                                <option value=4>Apr</option>
                                <option value=5>May</option>
                                <option value=6>Jun</option>
                                <option value=7>Jul</option>
                                <option value=8>Aug</option>
                                <option value=9>Sep</option>
                                <option value=10>Oct</option>
                                <option value=11>Nov</option>
                                <option value=12>Dec</option>
                            </select>
                            <select id="year" onChange="dueDateChange()"></select>
                        </div>
                    </div>
                    <div id="popUpDateDue">
                        <div class="dateLabel">
                            <p>Date Due</p>
                        </div><div class="dateDue">
                            <p id="dateDueText"></p>
                        </div><div class="daysLate">
                            <p id="daysLateText"></p>
                        </div>
                    </div>
                    <div id="popUpNotes">
                        <textarea id="popUpNoteText" placeholder="Notes"></textarea>
                    </div>
                </div>
                <div id="popUpBoxButtons">
                    <button id="popUpSave" onclick="div_hide(true)">Save</button><!--
                    --><button id="popUpClose" onclick="div_hide(false)">Close</button>
                </div>
            </div>
        </div>
        <div id="header">
            <div id="title">
                <a href="portalhome.php"><img src="branding/mainlogo.png"/></a>
            </div>
            <ul class='menu topbar'><li id="navbar"></li></ul>
    	</div>
    	<div id="body">
            <div id="top_bar">
                <div id="title2"></div>
                <ul id="menu_button" class="menu navbar"></ul>
            </div>

            <form id="editForm" class="editResults" action="" method="POST">
                <input type='hidden' id ='gwid' name='gwid' />
                <div id="summaryBox">
                    <div id="summaryBoxDetails">
                        <div id="summaryBoxShowDetailsText">
                            <h2 onclick="showHideDetails()" id="summaryBoxShowDetailsTextMain" ></h2>
                        </div><div id="summaryBoxShowHide">
                        </div>
                    </div><div id="summaryBoxButtons">
                        <div id="saveButton" onclick="clickSave()">Force Save
                        </div><div id="cancelButton" onclick="clickBack()">Back
                        </div><div id="downloadButton" onclick="downloadCSV()"></div>
                    </div>
                </div>

                <div id="details" style="display:none">
                    <table class="form">
                        <tbody class="form">
                            <tr class="form">
                                <td class="form">
                                    <label for="date">Date Due:</label><!--
                                    --><input type="text" name="dateDueMain" id="dateDueMain" class="datepicker" placeholder="DD/MM/YYYY" onChange="changeDateDueMain()" />
                                </td>
                                <td class="form">
                                    <label for="staff1">Teacher:</label>
                                    <select name="staff1" id="staff1" onchange="changeGWValue()"></select>
                                </td>
                            </tr>
                            <tr class="form">
                                <td class="form">
                                    <label for="staff2">Extra Teacher:</label>
                                    <select name="staff2" id="staff2" onchange="changeGWValue()"></select>
                                </td>
                                <td class="form">
                                    <label for="staff3">Extra Teacher:</label>
                                    <select name="staff3" id="staff3" onchange="changeGWValue()"></select>
                                </td>
                            </tr>
                            <tr class="form">
                                <td class="form">
                                    <label for="displayName">Display Name:</label><!--
                                    --><input type="text" id="displayName" onchange="changeGWValue()"/>
                                </td>
                                <td class="form">
                                    <div class="detail_button" onclick="hideButton()">
                                        <label>Show in mark book</label>
                                        <input type="checkbox" name="hide_checkbox" id="hide_checkbox" onchange="changeGWValue()"/>
                                    </div>
                                    <div class="detail_button delete" onclick="deleteButton()">
                                        <h3>Delete Worksheet</h3>
                                    </div>
                                    <div class="detail_button" onclick="studentInputButton()">
                                        <label>Allow Student Input</label>
                                        <input type="checkbox" name="student_checkbox" id="student_checkbox" onchange="changeGWValue()"/>
                                    </div>
                                    <div class="detail_button" onclick="enterTotalsButton()">
                                        <label>Enter Totals</label>
                                        <input type="checkbox" name="totals_checkbox" id="totals_checkbox" onchange="changeGWValue()"/>
                                    </div>
                                </td>
                            </tr>
                            <tr class="form">
                                <td class="form title">
                                    <h1>Inputs</h1>
                                </td>
                                <td class="form title">
                                    <h1>Tags</h1>
                                </td>
                            </tr>
                            <tr class="form">
                                <td class="form inputs">
                                    <div class="select_inputs_input left" id="select_inputs_input"></div>
                                </td>
                                <td class="form inputs">
                                    <div class="select_inputs_input" id="select_inputs_tags"></div>
                                </td>
                            </tr>
                            <tr class="form grade_boundaries_row">
                                <td class="form title">
                                    <h1>Grade Boundaries</h1>
                                </td>
                                <td class="form buttons">
                                    <div class="grade_button"></div>
                                    <div class="grade_button" id="update_all_button" onclick="updateAllResults()">Update All</div>
                                </td>
                            </tr>
                            <tr class="form grade_boundaries_row">
                                <td class="form boundaries" colspan="2">
                                    <div class="grade_boundaries_input" id="grade_boundaries_table">
                                        <table class="grade_boundaries">
                                            <tbody class="grade_boundaries"></tbody>
                                        </table>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div id="main_content" style="overflow: scroll;">
                    <table class="results" border="1">
                        <thead class="results">
                            <tr class="results" id="row_head_1"></tr>
                            <tr class="results" id="row_head_2"></tr>
                        </thead>
                        <tbody class="results" id="table_body">
                        </tbody>
                    </table>
                </div>
            </form>
    	</div>
        <?php pageFooter($info_version); ?>
    </div>
</body>
