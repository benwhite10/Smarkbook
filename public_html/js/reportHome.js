var displayed_gwid = "";
var student_report_view = false;
var set_staff_link = {};
var user;
var staff_id;
var stu_id;
var set_id;
var start_date;
var end_date;
var set_student;

$(document).ready(function(){
    user = JSON.parse(localStorage.getItem("sbk_usr"));
    window.addEventListener("valid_user", function(){init_page();});
    validateAccessToken(user, ["SUPER_USER", "STAFF", "STUDENT"]);
});

function init_page() {
    writeNavbar(user);
    $("#variablesInputBoxShowHideButton").click(function(){
        showHideButton("variablesInputMain", "variablesInputBoxShowHideButton");
    });

    $("#worksheetSummaryDetails").click(function(){
        showHideWorksheetDetails();
    });

    showAllSections();
    showAllSpinners();
    setUpVariableInputs();
}

$(function() {
    $(".datepicker").pickadate({
          format: 'dd/mm/yyyy',
          formatSubmit: 'dd/mm/yyyy',
          onClose: function(){
            $(document.activeElement).blur();
        }
    });
});

/* Section set up methods */
function setUpVariableInputs(){
    staff_id = getParameterByName("staff");
    stu_id = getParameterByName("stu");
    set_id = getParameterByName("set");
    start_date = getParameterByName("start");
    end_date = getParameterByName("end");
    set_student = user["role"] === "STUDENT" ? user["userId"] : getParameterByName("student");

    localStorage.setItem("initialRun", true);
    disableGenerateReportButton();
    if (set_student) {
        student_report_view = true;
        setStudent(set_student);
        $("#staff_col").css("display", "none");
    } else {
        getStaff();
    }
    setDates();
}

function setStudent(stuid){
    var infoArray = {
        type: "STUDENTSETS",
        student: stuid,
        token: user["token"]
    };
    $.ajax({
        type: "POST",
        data: infoArray,
        url: "/requests/getStudentSummary.php",
        dataType: "json",
        success: function(json){
            setVariableInputs(json);
        }
    });
}

function setVariableInputs(json) {
    if(json["success"]){
        var results = json["result"];
        var student_details = results["student"];
        var staff_details = results["staff"];

        var first_name = student_details["PName"] ? student_details["PName"] : student_details["FName"];
        var surname = student_details["Surname"];
        $('#student').html("");
        $('#student').append($('<option/>', {
            value: student_details["UserID"],
            text: first_name + " " + surname
        }));

        //Set up staff
        var htmlValue = staff_details.length === 0 ? "<option value='0'>-</option>" : "";
        $("#staff").html(htmlValue);
        $('#set').html(htmlValue);
        var link_id = 1;
        var initial_link = 1;
        set_staff_link = {};
        for (var key in staff_details) {
            set_staff_link[link_id] = {
                "SetID": staff_details[key]["GroupID"],
                "StaffID": staff_details[key]["UserID"]
            };
            if (set_id == staff_details[key]["UserID"] && set_id == staff_details[key]["UserID"]) initial_link = link_id;
            $('#set').append($('<option/>', {
                value: link_id,
                text : staff_details[key]["GroupName"] + " (" + staff_details[key]["Initials"] + ")"
            }));
            $('#staff').append($('<option/>', {
                value: staff_details[key]["UserID"],
                text : staff_details[key]["Initials"]
            }));
            link_id++;
        }

        if($("#set option[value='" + initial_link + "']").length !== 0){
            $('#set').val(initial_link);
        }
        var initial_staff_val = set_staff_link[initial_link]["StaffID"];
        if($("#staff option[value='" + initial_staff_val + "']").length !== 0){
            $('#staff').val(initial_staff_val);
        }

        enableGenerateReportButton();
        if(localStorage.getItem("initialRun") === "true"){
            generateReport();
            localStorage.setItem("initialRun", false);
        }
    } else {
        if (json["response"] === "INVALID_TOKEN") log_out();
        setNoResults();
        console.log("Something went wrong getting the details for the students.");
        console.log(json["message"]);
    }
}

function setDates(){
    if(start_date && start_date !== null){
        $("#startDate").val(start_date);
    }
    if(end_date && end_date !== null){
        $("#endDate").val(end_date);
    } else {
        $("#endDate").val(moment().format('DD/MM/YYYY'));
    }
}

function setInputsTitle(){
    var student = $("#student option:selected").text();
    var set = $("#set option:selected").text();
    student = student === "No Students" ? "" : student;
    set = set === "No Sets" ? "" : set;
    if(set === "" || student === ""){
        $("#variablesInputBoxDetailsTextMain").text(set + student);
    } else {
        $("#variablesInputBoxDetailsTextMain").text(student + " - " + set);
    }
}

/* Display functions */

function showHideWorksheetDetails(){
    if($("#summaryReportDetails").is(":visible")){
        $("#showHideWorksheetText").text("Show Worksheets \u2193");
    } else {
        $("#showHideWorksheetText").text("Hide Worksheets \u2191");
    }
    $("#worksheetSummaryReport").hide();
    $("#summaryReportDetails").slideToggle();
}

function showHideButton(mainId, buttonId){
    if($("#" + mainId).css("display") === "none"){
        $("#" + buttonId).addClass("minus");
    } else {
        $("#" + buttonId).removeClass("minus");
    }
    $("#" + mainId).slideToggle();
}

function showHideFullTagResults(){
    if($("#tagsReportShort").css("display") === "none"){
        $("#showHideFullTagResultsText").text("Show Full Results");
    } else {
        $("#showHideFullTagResultsText").text("Hide Full Results");
    }
    $("#tagsReportShort").slideToggle(800);
    $("#tagsReportFull").slideToggle(800);
}

/* Send Requests */

function getStaff(){
    var infoArray = {
        orderby: "Initials",
        token: user["token"],
        type: "ALLSTAFF"
    };
    $.ajax({
        type: "POST",
        data: infoArray,
        url: "/requests/getUsers.php",
        dataType: "json",
        success: function(json){
            getStaffSuccess(json);
        }
    });
}

function updateSets(){
    if (!student_report_view) {
        disableGenerateReportButton();
        var infoArray = {
            orderby: "Name",
            desc: "FALSE",
            type: "SETSBYSTAFF",
            staff: $('#staff').val(),
            token: user["token"]
        };
        $.ajax({
            type: "POST",
            data: infoArray,
            url: "/requests/getGroup.php",
            dataType: "json",
            success: function(json){
                updateSetsSuccess(json);
            }
        });
    }
}

function updateStudents(){
    if (!student_report_view) {
        disableGenerateReportButton();
        var sets_info = getStaffForSet($('#set').val());
        var infoArray = {
            type: "STUDENTSBYSET",
            set: sets_info["SetID"],
            token: user["token"]
        };
        $.ajax({
            type: "POST",
            data: infoArray,
            url: "/requests/getUsers.php",
            dataType: "json",
            success: function(json){
                updateStudentsSuccess(json);
            }
        });
    } else {
        var set_info = getStaffForSet($("#set").val());
        $("#staff").val(set_info["StaffID"]);
        generateReport();
    }
}

//TODO
function generateQuestionsRequest(reqid, tagsArray){
    var infoArray = JSON.parse(localStorage.getItem("activeReportRequest"));
    if(parseInt(infoArray["reqid"]) === reqid){
        infoArray["type"] = "STUDENT";
        infoArray["tagsList"] = JSON.stringify(tagsArray);
        $.ajax({
            type: "POST",
            data: infoArray,
            url: "/requests/getSuggestedQuestions.php",
            dataType: "json",
            success: function(json){
                generateQuestionsRequestSuccess(json);
            }
        });
    } else {
        console.log("There was an error in sending the tag list.");
    }
}

//TODO
function sendSummaryRequest(infoArray){
    infoArray["type"] = "STUDENTSUMMARY";
    $.ajax({
        type: "POST",
        data: infoArray,
        url: "/requests/getStudentSummary.php",
        dataType: "json",
        success: function(json){
            summaryRequestSuccess(json);
        }
    });
}

function sendWorksheetSummaryRequest(gwid){
    var infoArray = {
        type: "WORKSHEETREPORT",
        student: $('#student').val(),
        gwid: gwid,
        token: user["token"]
    };
    $.ajax({
        type: "POST",
        data: infoArray,
        url: "/requests/getStudentSummary.php",
        dataType: "json",
        success: function(json){
            worksheetSummaryRequestSuccess(json);
        }
    });
}

function sendReportRequest(){
    var reqid = generateNewReqId();
    var set_info = getStaffForSet($('#set').val());
    var infoArray = {
        reqid: reqid,
        startDate: $('#startDate').val(),
        endDate: $('#endDate').val(),
        student: $('#student').val(),
        staff: $('#staff').val(),
        set: set_info["SetID"],
        userid: user["userId"],
        token: user["token"]
    };
    localStorage.setItem("activeReportRequest", JSON.stringify(infoArray));
    infoArray["type"] = "NEWSTUDENTREPORT";
    $.ajax({
        type: "POST",
        data: infoArray,
        url: "/requests/getStudentSummary.php",
        dataType: "json",
        success: function(json){
            newReportRequestSuccess(json);
        }
    });
    sendSummaryRequest(infoArray);
}

function getStaffForSet(link_id) {
    var set_info = set_staff_link[link_id];
    return set_info ? set_info : {"SetID": 0, "StaffID": 0};
}

/* Responses */

function getStaffSuccess(json){
    if(json["success"]){
        var staff = json["response"];
        var htmlValue = staff.length === 0 ? "<option value='0'>No Teachers</option>" : "";
        $('#staff').html(htmlValue);
        for (var key in staff) {
            $('#staff').append($('<option/>', {
                value: staff[key]["User ID"],
                text : staff[key]["Initials"].toUpperCase()
            }));
        }
        var initialVal = staff_id;
        if($("#staff option[value='" + initialVal + "']").length !== 0){
            $('#staff').val(initialVal);
        }
        updateSets();
    } else {
        if (json["response"] === "INVALID_TOKEN") log_out();
        console.log("Something went wrong loading the staff");
    }
}

function updateSetsSuccess(json){
    if(json["success"]){
        var sets = json["sets"];
        var htmlValue = sets.length === 0 ? "<option value='0'>No Sets</option>" : "";
        var link_id = 1;
        var initial_link = 1;
        var staff_id = $("#staff").val();
        set_staff_link = {};
        $('#set').html(htmlValue);
        for (var key in sets) {
            if (set_id == sets[key]["ID"]) initial_link = link_id;
            set_staff_link[link_id] = {
                "SetID": sets[key]["ID"],
                "StaffID": staff_id
            };
            $('#set').append($('<option/>', {
                value: link_id,
                text : sets[key]["Name"]
            }));
            link_id++;
        }

        if($("#set option[value='" + initial_link + "']").length !== 0){
            $('#set').val(initial_link);
        }
        updateStudents();
    } else {
        if (json["response"] === "INVALID_TOKEN") log_out();
        console.log("Something went wrong loading the staff");
    }
}

function updateStudentsSuccess(json){
    if(json["success"]){
        var students = json["students"];
        var htmlValue = students.length === 0 ? "<option value='0'>No Students</option>" : "";
        $('#student').html(htmlValue);
        for (var key in students) {
            var fname = students[key]["PName"] !== "" ? students[key]["PName"] : students[key]["FName"];
            var name = fname + " " + students[key]["SName"];
            $('#student').append($('<option/>', {
                value: students[key]["ID"],
                text : name
            }));
        }
        var initialVal = stu_id;
        if($("#student option[value='" + initialVal + "']").length !== 0){
            $('#student').val(initialVal);
        }
        studentChange();
    } else {
        if (json["response"] === "INVALID_TOKEN") log_out();
        console.log("Something went wrong loading the students");
    }
}

function generateQuestionsRequestSuccess(json){
    if(validateResponse(json)){
        var result = json["result"];
        if(result !== null){
            localStorage.setItem("suggested", JSON.stringify(result));
        } else {
            localStorage.setItem("suggested", null);
        }
        refreshSuggestedQuestions();
        showSuggestedQuestions();
        localStorage.setItem("activeReportRequest", null);
    } else {
        console.log("Something went wrong generating the suggested questions");
    }
}

function reportRequestSuccess(json){
    if(validateResponse(json)){
        var results = json["result"];
        if(results !== null){
            localStorage.setItem("tagResults", JSON.stringify(json["result"]["tags"]));
            generateQuestionsRequest(parseInt(json["reqid"]), json["result"]["tags"]);
        } else {
            localStorage.setItem("tagResults", null);
        }
        refreshTagResults();
    } else {
        setNoResults();
        console.log("Something went wrong generating the reports.");
    }
}

function newReportRequestSuccess(json) {
    if(validateResponse(json)){
        var results = json["result"];
        if(results !== null){
            localStorage.setItem("new_tag_results", JSON.stringify(results["tags"]));
            localStorage.setItem("new_tag_questions", JSON.stringify(results["questions"]));
        } else {
            localStorage.setItem("new_tag_results", "");
            localStorage.setItem("new_tag_questions", "");
        }
        refreshNewTagResults();
    } else {
        setNoResults();
        console.log("Something went wrong generating the reports.");
    }
}

function summaryRequestSuccess(json){
    if(validateResponse(json)){
        var results = json["result"];
        if(results !== null){
            localStorage.setItem("summary", JSON.stringify(json["result"]["summary"]));
            localStorage.setItem("userAverage", Math.round(parseFloat(json["result"]["stuAvg"]) * 100));
            localStorage.setItem("setAverage", Math.round(parseFloat(json["result"]["setAvg"]) * 100));
            setUpWorksheetList();
            refreshSummaryResults();
        } else {
            localStorage.setItem("summary", null);
            localStorage.setItem("userAverage", null);
            localStorage.setItem("setAverage", null);
            setNoResults();
        }
    } else {
        setNoResults();
        console.log("Something went wrong generating the report summary.");
    }
}

function studentChange(){
    enableGenerateReportButton();
    if(localStorage.getItem("initialRun") === "true"){
        generateReport();
        localStorage.setItem("initialRun", false);
    }
}

/* Generate Report */
function generateReport(){
    showAllSections();
    showAllSpinners();
    clearWorksheetsSummary();
    sendReportRequest();
    setInputsTitle();
    //getNotesRequest();
    return false;
}

function clearWorksheetsSummary() {
    displayed_gwid = "";
    clearWorksheetSummary();
}

/* Request Validation */

function generateNewReqId(){
    var reportRequest = JSON.parse(localStorage.getItem("activeReportRequest"));
    var curreqid = reportRequest !== null ? reportRequest["reqid"] : null;
    do {
        var reqid = Math.floor(Math.random() * 9999);
    } while (reqid === curreqid);
    return reqid;
}

function validateResponse(json){
    if(json["success"] || json["reqid"] === undefined){
        var array = JSON.parse(localStorage.getItem("activeReportRequest"));
        return parseInt(array["reqid"]) === parseInt(json["reqid"]);
    } else {
        if (json["response"] === "INVALID_TOKEN") log_out();
    }
    return false;
}

function disableGenerateReportButton(){
    $('#generateReportButton').hide();
}

function enableGenerateReportButton(){
    $('#generateReportButton').show();
}

/* Refresh Displays */

function refreshNewTagResults(){
    parseNewTagResults("1", 1, true);
    parseNewTagResults("2", 1, true);
    parseNewTagResults("3", 1, true);
    stopSpinnerInDiv('new_tags_report_spinner');
    $("#new_tags_report_main").show();
}

function parseNewTagResults(type, order, desc) {
    var results = JSON.parse(localStorage.getItem("new_tag_results"));
    var order_info = getOrderInformation(order);
    results = orderArrayBy(results, order_info["array_key"], desc);
    var type_name = getTypeFromId(type).toLowerCase();
    setOrderTextAndDirection(type_name, order, desc);
    $("#new_tags_report_" + type_name).html("");
    for(var i = 0; i < results.length; i++){
        var result = results[i];
        if (parseInt(type) === parseInt(result["type"])) {
            var tag_string = parseNewTagResult(result, order_info["array_key"]);
            $("#new_tags_report_" + type_name).append(tag_string);
        }
    }
}

function getOrderInformation(value) {
    switch (value) {
        case 1:
            return {
                array_key: "count",
                display_text: "No. Of Questions"
            };
            break;
        case 2:
            return {
                array_key: "perc",
                display_text: "Percentage"
            };
            break;
        case 3:
            return {
                array_key: "marks",
                display_text: "Marks"
            };
            break;
        case 4:
            return {
                array_key: "recent_perc",
                display_text: "Last 5"
            };
            break;
        default:
            return {
                array_key: "count",
                display_text: "No. Of Questions"
            };
            break;
    }
}

function setOrderTextAndDirection(type_name, order, desc) {
    var order_info = getOrderInformation(order);
    var desc_text = desc ? "\u2193" : "\u2191";
    $("#tags_report_order_" + type_name).html("<h2>" + desc_text + "</h2>");
    $("#tags_report_criteria_" + type_name).html("<h2>" + order_info["display_text"] + "</h2>");
    $("#" + type_name + "_criteria").val(order);
    $("#" + type_name + "_order").val(desc);
}

function setWorksheetsOrderTextAndDirection(order, desc) {
    var order_info = getWorksheetsOrderInformation(order);
    var desc_text = desc ? "\u2193" : "\u2191";
    $("#new_worksheets_report_order_title").html("<h2>" + desc_text + "</h2>");
    $("#new_worksheets_report_criteria_title").html("<h2>" + order_info["display_text"] + "</h2>");
    $("#new_worksheets_report_criteria").val(order);
    $("#new_worksheets_report_order").val(desc);
}

function parseNewTagResult(result, order_key) {
    var tag_id = result["TagID"];
    var name = result["name"];
    var name_style = "";
    if (name.length > 45) {
        name_style = "line-height: 12.5px; font-size:0.8rem";
    } else if (name.length > 35) {
        name_style = "font-size:0.8rem";
    }
    var marks = result["marks"];
    var type = getTypeFromId(result["type"]).toLowerCase();
    var count = result["count"];
    var totalScore = result["perc"] !== "-" ? parseInt(result["perc"]): 0;
    var width = totalScore === 0 ? 0.1 : totalScore;
    var recentScore = parseInt(result["recent_perc"]);
    var string = "<div id='tag_" + tag_id + "' class='" + type + " new_tag'>";
    string += "<div id='background_tag_" + tag_id + "' class='background_block " + type + "' style='width:" + width + "%'></div>";
    string += "<div class='tag_content'>";
    string += "<div class='tag_content_name'><p style='" + name_style + "'>" + name + "</p></div>";
    string += "<div class='tag_content_main_display'><p>" + getMainDisplayCriteria(result, order_key) + "</p></div>";
    string += "<div class='tag_content_main_extra'><div class='tag_content_main_extra_value'><p>" + totalScore + "%</p></div>";
    string += "<div class='tag_content_main_extra_writing'><p>ALL</p></div></div>";
    string += "<div class='tag_content_main_extra'><div class='tag_content_main_extra_value'><p>" + recentScore + "%</p></div>";
    string += "<div class='tag_content_main_extra_writing'><p>LAST 5</p></div></div>";
    string += "<div class='tag_content_main_extra'><div class='tag_content_main_extra_value'><p>" + count + "</p></div>";
    string += "<div class='tag_content_main_extra_writing'><p>QUESTIONS</p></div></div>";
    string += "<div class='tag_content_main_extra'><div class='tag_content_main_extra_value'><p>" + marks + "</p></div>";
    string += "<div class='tag_content_main_extra_writing'><p>MARKS</p></div></div>";
    string += "</div></div>";
    return string;
}

function getMainDisplayCriteria(result, order_key) {
    return (order_key === "count" || order_key === "marks") ? parseInt(result[order_key]) : parseInt(result[order_key]) + "%";
}

function changeCriteria(type) {
    var type_name = getTypeFromId(type).toLowerCase();
    var order = parseInt($("#" + type_name + "_criteria").val());
    var desc = $("#" + type_name + "_order").val();
    desc = desc === "true" ? true : false;
    order = order < 4 ? order + 1 : 1;
    parseNewTagResults(type, order, desc);
}

function changeOrder(type) {
    var type_name = getTypeFromId(type).toLowerCase();
    var order = parseInt($("#" + type_name + "_criteria").val());
    var desc = $("#" + type_name + "_order").val();
    desc = desc === "true" ? false : true;
    parseNewTagResults(type, order, desc);
}

function changeWorksheetsCriteria() {
    var order = parseInt($("#new_worksheets_report_criteria").val());
    var desc = $("#new_worksheets_report_order").val();
    desc = desc === "true" ? true : false;
    order = order < 2 ? order + 1 : 1;
    setWorksheetsTable(order, desc);
}

function changeWorksheetsOrder() {
    var order = parseInt($("#new_worksheets_report_criteria").val());
    var desc = $("#new_worksheets_report_order").val();
    desc = desc === "true" ? false : true;
    setWorksheetsTable(order, desc);
}

function refreshTagResults(){
    $('#top5tags tbody').html('');
    $('#bottom5tags tbody').html('');
    $('#alltags tbody').html('');
    var results = JSON.parse(localStorage.getItem("tagResults"));
    if(results === null || results.length === 0){
        setNoResults();
    } else {
        showTagResults(false);
        var length = results.length;
        for(var i = 0; i < 5; i++){
            $('#bottom5tags tbody').append(setNewHalfWidthTagResults(results[i], i));
            $('#top5tags tbody').append(setNewHalfWidthTagResults(results[length - (i+1)], i));
        }

        for(var i = 0; i < length; i++){
            $('#alltags tbody').append(setNewHalfWidthTagResults(results[i], i));
        }
    }
}

function refreshSummaryResults(){
    $('#worksheetSummaryTable tbody').html('');
    setSummaryReportToDefaults();

    // Set the averages
    var userAvg = Math.round(JSON.parse(localStorage.getItem("userAverage")));
    var setAvg = Math.round(JSON.parse(localStorage.getItem("setAverage")));
    $('#summaryReportUserAvgValue').text(userAvg + "%");
    $('#summaryReportSetAvgValue').text(setAvg + "%");
    $('#summaryReportSetAvgValue').css('color', getColour(setAvg, 60, 40, [220, 0, 0], [240, 160, 0], [0, 240, 0]));
    $('#summaryReportUserAvgValue').css('color', getColour(userAvg, 60, 40, [220, 0, 0], [240, 160, 0], [0, 240, 0]));

    setWorksheetsSummary();
    setWorksheetsTable(0, true);
    showSummaryResults();
}

function refreshSuggestedQuestions(){
    var suggested = JSON.parse(localStorage.getItem("suggested"));
    var results = 20;
    $('#questionsSummaryDetailsTable tbody').html('');
    if(suggested !== null){
        for(var i = 0; i < Math.min(suggested.length, results); i++){
            var question = suggested[i];
            var number = question["details"]["Number"];
            var name = question["details"]["WName"];
            var marks = question["marks"];
            var markString = "-";
            var date = "-";
            if(question["mark"]){
                markString = question["mark"] + "/" + marks;
            }
            if(question["date"]){
                date = question["date"];
            }
            var vid = question["details"]["VID"];
            var tagString = "";
            var tags = question["tags"];
            for(var j = 0; j < tags.length; j++){
                var tag = tags[j][1];
                tagString += tag;
                if(j < tags.length - 1){
                    tagString += ', ';
                }
            }
            var link = question["details"]["Link"] ? question["details"]["Link"] : null;
            var string = "<tr>";
            string += "<td>" + number + "</td>";
            if(link === null){
                string += "<td class='worksheetName'>" + name + "</td>";
            } else {
                string += "<td class='worksheetName'><a href='" + link + "'>" + name + "</a></td>";
            }
            string += "<td class='worksheetName'>" + tagString + "</td>";
            string += "<td>" + marks + "</td>";
            string += "<td>" + date + "</td>";
            string += "<td>" + markString + "</td>";
            string += "</tr>";
            $('#questionsSummaryDetailsTable tbody').append(string);
        }
        showSuggestedQuestions();
    }
}

function setSummaryReportToDefaults(){
    $('#compValue').text('0');
    $('#partialValue').text('0');
    $('#incompleteValue').text('0');
    $('#onTimeValue').text('0');
    $('#lateValue').text('0');
    $('#dateNoInfoValue').text('0');
    $('#compNoInfoValue').text('0');
    $('#summaryReportUserAvgTitle').text('Student Average');
    $('#summaryReportUserAvgValue').text('-');
    $('#summaryReportSetAvgTitle').text('Set Average');
    $('#summaryReportSetAvgValue').text('-');
}

function setWorksheetsSummary(){
    var summary = JSON.parse(localStorage.getItem("summary"));
    if(summary !== null){
        $('#compValue').text(summary["compStatus"]["Completed"]);
        $('#partialValue').text(summary["compStatus"]["Partially Completed"]);
        $('#incompleteValue').text(summary["compStatus"]["Incomplete"]);
        $('#onTimeValue').text(summary["dateStatus"]["OnTime"]);
        $('#lateValue').text(summary["dateStatus"]["Late"]);
        $('#dateNoInfoValue').text(summary["dateStatus"]["-"]);
        $('#compNoInfoValue').text(summary["compStatus"]["-"]);
    }
}

function changeSection(id) {
    if (displayed_gwid !== "") {
        switch(id) {
            case "section_questions":
            default:
                setWorksheetSummary(0);
                break;
            case "section_tags":
                setWorksheetSummary(1);
                break;
            case "section_details":
                setWorksheetSummary(2);
                break;
        }
    }
}

function changeSectionTab(id) {
    var tabs = document.getElementsByClassName("title_sections");
    for (var i = 0; i < tabs.length; i++) {
        var tab = tabs[i];
        $("#" + tab.id).removeClass("selected");
    }
    $("#" + id).addClass('selected');
}

function setUpWorksheetList() {
    var summary = JSON.parse(localStorage.getItem("summary"));
    var worksheet_list = [];
    if(summary !== null){
        var list = summary["worksheetList"];
        for(var key in list){
            var sheet = list[key];
            var worksheet = {};
            if (!sheet["Results"]) continue;
            worksheet["name"] = (sheet["DisplayName"] && sheet["DisplayName"] !== "") ? sheet["DisplayName"] : sheet["WName"];
            var date_due = moment(sheet["DateDue"], "DD/MM/YYYY");
            var date_string = date_due.format("DD/MM/YY");
            var short_date_string = date_due.format("DD/MM");
            worksheet["date_order"] = date_due.unix();
            worksheet["date_string"] = date_string;
            worksheet["short_date_string"] = short_date_string;
            worksheet["gwid"] = sheet["GWID"];
            var stu_score = 0.1;
            var display_status = "-";
            var display_relative = "-";
            var relative_colour = "rgb(0,0,0)";
            var status_colour = "rgb(0,0,0)";
            var stu_perc = sheet["StuAVG"] ? Math.round(100 * sheet["StuAVG"]) : 0;
            stu_score = parseInt(stu_perc) === 0 ? 0.1 : parseInt(stu_perc);
            var stuMark = sheet["StuMark"] ? sheet["StuMark"] : 0;
            stuMark = parseFloat(stuMark) === parseInt(stuMark) ? parseInt(stuMark) : Math.round(10 * parseFloat(stuMark)) / 10;
            var stuMarks = sheet["StuMarks"] ? sheet["StuMarks"] : 0;
            var display_marks = stuMark + "/" + stuMarks;
            var set_score = sheet["AVG"] ? Math.round(100 * sheet["AVG"]) : 0;
            var relative_score = stu_score - set_score;
            if (stu_score !== 0.1) {
                if (relative_score < 0) {
                    display_relative = "\u2193" + Math.abs(relative_score) + "%";
                } else if (stu_score - set_score > 0) {
                    display_relative = "\u2191" + Math.abs(relative_score) + "%";
                }
                relative_colour = getColour(relative_score, 0, 20, [255, 0, 0], [80, 80, 80], [0, 210, 0]);
            }
            var late = sheet["StuDays"];
            if(late === "" || late === null){
                display_status = "-";
            } else if(late === 0 || late === "0") {
                display_status = "On Time";
                status_colour = "rgb(50,130,50)";
            } else {
                display_status = late + " Days Late";
                status_colour = "rgb(255,0,0)";
            }
            worksheet["stu_score"] = stu_score;
            worksheet["set_score"] = set_score;
            worksheet["percentage"] = stu_perc;
            worksheet["display_status"] = display_status;
            worksheet["display_status_colour"] = status_colour;
            worksheet["relative_score"] = relative_score;
            worksheet["display_relative_score"] = display_relative;
            worksheet["relative_colour"] = relative_colour;
            worksheet["display_marks"] = display_marks;
            worksheet_list.push(worksheet);
        }
    }
    sessionStorage.setItem("worksheet_list", JSON.stringify(worksheet_list));
}

function setWorksheetsTable(order, desc){
    var worksheet_list = JSON.parse(sessionStorage.getItem("worksheet_list"));
    setWorksheetsOrderTextAndDirection(order, desc);
    var order_info = getWorksheetsOrderInformation(order);
    worksheet_list = orderArrayBy(worksheet_list, order_info["array_key"], desc);
    $('#new_worksheets_report_main').html("");
    for(var key in worksheet_list){
        var sheet = worksheet_list[key];
        var class_string = parseInt(sheet["gwid"]) === parseInt(displayed_gwid) ? "selected" : "";
        var string = "<div id='worksheet_" + sheet["gwid"] + "' class='new_tag worksheet_summary " + class_string + "' onclick='clickWorksheet(" + sheet["gwid"] + ")'>";
        string += "<div id='background_worksheet_" + sheet["gwid"] + "' class='background_block_worksheet " + class_string + "' style='width:" + sheet["stu_score"] + "%'></div>";
        string += "<div class='tag_content'>";
        var sheet_name = sheet["name"];
        var class_name = "";
        if (sheet_name.length > 50) {
            class_name = (sheet_name.length < 70) ? "medium_worksheet_name" : "long_worksheet_name";
        }
        string += "<div class='tag_content_name'><p class='" + class_name + "'>" + sheet_name + "</p></div>";
        string += "<div class='tag_content_main_display'><p>" + getMainWorksheetsDisplayCriteria(order_info["display_key"], sheet) + " </p></div>";
        string += "<div class='tag_content_main_extra'><div class='tag_content_main_extra_value'><p>" + sheet["date_string"] + "</p></div>";
        string += "<div class='tag_content_main_extra_writing'><p>DATE</p></div></div>";
        string += "<div class='tag_content_main_extra'><div class='tag_content_main_extra_value'><p>" + sheet["display_marks"] + "</p></div>";
        string += "<div class='tag_content_main_extra_writing'><p>MARK</p></div></div>";
        string += "<div class='tag_content_main_extra'><div class='tag_content_main_extra_value'><p style='color:" + sheet["relative_colour"] + "'>" + sheet["display_relative_score"] + "</p></div>";
        string += "<div class='tag_content_main_extra_writing'><p style='color:" + sheet["relative_colour"] + "'>REL</p></div></div>";
        string += "<div class='tag_content_main_extra'><div class='tag_content_main_extra_value'><p style='color:" + sheet["display_status_colour"] + "'>" + sheet["display_status"] + "</p></div>";
        string += "<div class='tag_content_main_extra_writing'><p style='color:" + sheet["display_status_colour"] + "'>STATUS</p></div></div>";
        string += "</div></div>";
        $('#new_worksheets_report_main').append(string);
    }
}

function getMainWorksheetsDisplayCriteria(display_key, sheet) {
    return (display_key === "percentage") ? sheet[display_key] + "%" : sheet[display_key];
}

function getWorksheetsOrderInformation(order) {
    switch(order) {
        case 0:
        default:
            return {
                array_key: "date_order",
                display_text: "Date",
                display_key: "short_date_string"
            };
            break;
        case 1:
            return {
                array_key: "percentage",
                display_text: "Percentage",
                display_key: "percentage"
            };
            break;
    }
}

function clickWorksheet(gwid) {
    if (gwid === displayed_gwid) {
        displayed_gwid = "";
        clearWorksheetSelected();
        clearWorksheetSummary();
    } else {
        displayed_gwid = gwid;
        sendWorksheetSummaryRequest(gwid);
        setWorksheetSelected(gwid);
    }
}

function setWorksheetSelected(gwid) {
    clearWorksheetSelected();
    $("#background_worksheet_" + gwid).addClass("selected");
    $("#worksheet_" + gwid).addClass("selected");
}

function clearWorksheetSelected() {
    var divs = document.getElementsByClassName("background_block_worksheet");
    for (var i = 0; i < divs.length; i++) {
        var id = divs[i].id;
        $("#" + id).removeClass("selected");
    }
    divs = document.getElementsByClassName("worksheet_summary");
    for (var i = 0; i < divs.length; i++) {
        var id = divs[i].id;
        $("#" + id).removeClass("selected");
    }
}

function clearWorksheetSummary() {
    $("#new_worksheet_report_main").html("<div id='new_worksheet_placeholder'><p>Click on a worksheet to view the details for that worksheet.</p></div>");
    changeSectionTab("section_details");
}

function worksheetSummaryRequestSuccess(json) {
    sessionStorage.setItem("worksheet_summary", JSON.stringify(json["result"]));
    setWorksheetSummary(2);
}

function setWorksheetSummary(type) {
    var summary = JSON.parse(sessionStorage.getItem("worksheet_summary"));
    var worksheet_list = JSON.parse(sessionStorage.getItem("worksheet_list"));
    var summary_info = [];
    var parse_array = [];
    var id = "";
    var order_display = "";
    var no_hide = false;
    switch(type) {
        case 0:
        default:
            summary_info = summary["questions"];
            orderArrayBy(summary_info, "QOrder", false);
            order_display = "Marks";
            for (var i = 0; i < summary_info.length; i++) {
                var row = summary_info[i];
                var mark = row["Mark"];
                mark = parseFloat(mark) === parseInt(mark) ? parseInt(mark) : Math.round(10 * parseFloat(mark)) / 10;
                parse_array.push({
                    main: "Q. " + row["Number"],
                    main_display: mark + "/" + row["Marks"],
                    width: parseFloat(row["Mark"])/parseFloat(row["Marks"]),
                    option_tags: row["tag_string"]
                });
            }
            id = "section_questions";
            break;
        case 1:
            summary_info = summary["tags"];
            orderArrayBy(summary_info, "Count", true);
            order_display = "Questions";
            for (var i = 0; i < summary_info.length; i++) {
                var row = summary_info[i];
                var mark = row["Mark"];
                mark = parseFloat(mark) === parseInt(mark) ? parseInt(mark) : Math.round(10 * parseFloat(mark)) / 10;
                parse_array.push({
                    main: row["Name"],
                    main_display: row["Count"],
                    width: parseFloat(row["Perc"]),
                    option_1: ["QUESTIONS", row["Count"]],
                    option_2: ["MARK", mark + "/" + row["Marks"]],
                    option_3: ["PERC", parseInt(100*parseFloat(row["Perc"])) + "%"]
                });
            }
            id = "section_tags";
            break;
        case 2:
            var worksheet = false;
            for (var i = 0; i < worksheet_list.length; i++) {
                if (worksheet_list[i]["gwid"] == summary["gwid"]) {
                    worksheet = worksheet_list[i];
                    break;
                }
            }
            if (worksheet) {
                parse_array.push({
                    main: "Marks",
                    main_display: worksheet["display_marks"],
                    width: 0
                })
                parse_array.push({
                    main: "Percentage",
                    main_display: worksheet["percentage"] + "%",
                    width: 0
                })
                parse_array.push({
                    main: "Set Average",
                    main_display: worksheet["set_score"] + "%",
                    width: 0
                })
            }

            summary_info = summary["cw_info"];
            if (summary_info["Grade"] && summary_info["Grade"] !== "") {
                parse_array.push({
                    main: "Grade",
                    main_display: summary_info["Grade"],
                    width: 0
                });
            }
            if (summary_info["UMS"] && summary_info["UMS"] !== "") {
                parse_array.push({
                    main: "UMS",
                    main_display: summary_info["UMS"],
                    width: 0
                });
            }
            var inputs = summary_info["Inputs"]
            for (var i = 0; i < inputs.length; i++) {
                var value = inputs[i];
                parse_array.push({
                    main: value["Name"],
                    main_display: value["Value"],
                    width: 0
                });
            }
            id = "section_details";
            no_hide = true;
            break;
    }
    changeSectionTab(id);
    parseWorksheetSummary(parse_array, "#new_worksheet_report", order_display, no_hide);
}

function parseWorksheetSummary(info, id, order_display, no_hide) {
    $(id + "_main").html("");
    $(id + "_criteria_title").html("<h2>" + order_display + "</h2>");
    for (var i = 0; i < info.length; i++) {
        var row = info[i];
        var width = parseFloat(row["width"]) > 0 ? 100 * parseFloat(row["width"]) : 0.1;
        var extra_width = getExtraContentWidth(row);
        var string = "<div class='new_tag worksheet_summary'>";
        string += "<div class='background_block_summary' style='width:" + width + "%'></div>";
        string += "<div class='tag_content'>";
        string += "<div class='tag_content_name ";
        if(no_hide) string += "no_hide";
        string += "'><p>" + row["main"] + "</p></div>";
        string += "<div class='tag_content_main_display ";
        if(no_hide) string += "no_hide";
        string += "'><p>" + row["main_display"] + "</p></div>";
        if(row["option_tags"]) {
            var tags_string = row["option_tags"];
            if(tags_string.length < 70) {
                string += "<div class='tag_content_tags_string'><p>" + row["option_tags"] + "</p></div>";
            } else if (tags_string.length < 110) {
                string += "<div class='tag_content_tags_string'><p class='smaller'>" + row["option_tags"] + "</p></div>";
            } else {
                string += "<div class='tag_content_tags_string'><p class='two_lines'>" + row["option_tags"] + "</p></div>";
            }
        } else {
            for (var j = 1; j < 5; j++) {
                if(row["option_" + j]) {
                    var title = row["option_" + j][0];
                    var value = row["option_" + j][1];
                    string += "<div class='tag_content_main_extra' style='width:" + extra_width + "%'><div class='tag_content_main_extra_value'><p>" + value + "</p></div>";
                    string += "<div class='tag_content_main_extra_writing'><p>" + title + "</p></div></div>";
                }
            }
        }
        string += "</div></div>";
        $(id + "_main").append(string);
    }
}

function getExtraContentWidth(row) {
    var count = 0;
    for (var j = 1; j < 5; j++) {
        if(row["option_" + j]) count++;
    }
    return count > 0 ? 100/count : 100;
}

function setNewHalfWidthTagResults(tag, position){
    var name = tag["name"];
    var marks = tag["marks"];
    var mark = tag["mark"];
    var recentMarks = tag["recentmarks"];
    var recentMark = tag["recentmark"];
    var type = tag["type"];
    var totalScore = parseInt(mark / marks * 100);;
    var recentScore = parseInt(recentMark / recentMarks * 100);
    var totalMarks = mark + "/" + marks;
    var recentMarks = recentMark + "/" + recentMarks;
    var questionsAnswered = tag["count"];
    var string = "<tr class='results'>";
    string += "<td class='results' style='text-align:left; padding-left: 10px;'>" + name + "</td>";
    string += "<td class='results' title='" + totalMarks + "'>" + totalScore + "% </td>";
    string += "<td class='results' title='" + recentMarks + "'>" + recentScore + "% </td>";
    string += "<td class='results'>" + questionsAnswered + "</td>";
    string += "</tr>";
    return string;
}

function showTagResults(full){
    stopSpinnerInDiv('tagsReportSpinner');
    $("#tagsReportSummary").show();
    if(full){
        $("#tagsReportFull").show();
        $("#tagsReportShort").hide();
        $("#showHideFullTagResultsText").text("Hide Full Results");
    } else {
        $("#tagsReportShort").show();
        $("#tagsReportFull").hide();
        $("#showHideFullTagResultsText").text("Show Full Results");
    }
}

function showSummaryResults(){
    stopSpinnerInDiv('summaryReportSpinner');
    $("#summaryReportMain").show();
    $("#summaryReportDetails").show();
}

function showSuggestedQuestions(){
    stopSpinnerInDiv('questionsReportSpinner');
    $("#questionsReportMain").show();
}

function getColour(value, centre, width, colour1, colour2, colour3) {
    var r1 = colour1[0];
    var g1 = colour1[1];
    var b1 = colour1[2];
    var r2 = colour2[0];
    var g2 = colour2[1];
    var b2 = colour2[2];
    var r3 = colour3[0];
    var g3 = colour3[1];
    var b3 = colour3[2];
    var min = centre - width;
    var max = centre + width;
    var r = 0;
    var g = 0;
    var b = 0;
    if(value < min) {
        r = parseInt(r1);
        g = parseInt(g1);
        b = parseInt(b1);
    } else if (value < centre) {
        r = parseInt(r1 + (r2 - r1) * (value - min) / width);
        g = parseInt(g1 + (g2 - g1) * (value - min) / width);
        b = parseInt(b1 + (b2 - b1) * (value - min) / width);
    } else if (value < max) {
        r = parseInt(r2 + (r3 - r2) * (value - centre) / width);
        g = parseInt(g2 + (g3 - g2) * (value - centre) / width);
        b = parseInt(b2 + (b3 - b2) * (value - centre) / width);
    } else {
        r = parseInt(r3);
        g = parseInt(g3);
        b = parseInt(b3);
    }
    return "rgb(" + r + ", " + g + ", " + b + ")";
}

function setNoResults(){
    hideAllSections();
}

function hideAllSections(){
    $("#tagsReport").hide();
    $("#summaryReport").hide();
    $("#questionsReport").hide();
    $("#new_tags_report").hide();
    //$("#report_notes").hide();
    $("#noResults").show();
}

function showAllSections(){
    //$("#tagsReport").show();
    $("#summaryReport").show();
    //$("#questionsReport").show();
    $("#new_tags_report").show();
    //$("#report_notes").show();
    $("#noResults").hide();
    $("#showHideWorksheetText").text("Hide Worksheets \u2191");
}

function hideAllContent(){
    $("#tagsReportSummary").hide();
    $("#tagsReportShort").hide();
    $("#tagsReportFull").hide();
    $("#summaryReportMain").hide();
    $("#new_tags_report_main").hide();
    $("#summaryReportDetails").hide();
    $("#questionsReportMain").hide();
    //$("#report_notes_main").hide();
}

function showAllSpinners(){
    hideAllContent();
    startSpinnerInDiv('summaryReportSpinner');
    startSpinnerInDiv('new_tags_report_spinner');
    //startSpinnerInDiv('report_notes_spinner');
}

function startSpinnerInDiv(div){
    stopSpinnerInDiv(div);
    var opts = {
      lines: 10             // The number of lines to draw
    , length: 9             // The length of each line
    , width: 4              // The line thickness
    , radius: 10            // The radius of the inner circle
    , scale: 1.0            // Scales overall size of the spinner
    , corners: 1           // Roundness (0..1)
    , color: '#000'         // #rgb or #rrggbb
    , left: '0%'           // center horizontally
    , position: 'relative'  // Element positioning
    };
    $("#" + div).show();
    var spinner = new Spinner(opts).spin($("#" + div).get(0));
    $($("#" + div).get(0)).data('spinner', spinner);
}

function stopSpinnerInDiv(div){
    if($('#' + div).data('spinner') !== undefined){
        $('#' + div).data('spinner').stop();
        $('#' + div).hide();
    }
}

function getTypeFromId(type_id) {
    switch(type_id) {
        case "1":
        case 1:
            return "Classification";
        case "2":
        case 2:
            return "Major";
        case "3":
        case 3:
            return "Minor";
        default:
            return "Minor";
    }
}

function orderArrayBy(array, key, desc) {
    array.sort(function(a, b) {
        return desc ? parseFloat(b[key]) - parseFloat(a[key]) :parseFloat(a[key]) - parseFloat(b[key]);
    });
    return array;
}

function getNotesRequest() {
    var infoArray = {
        staffid: $('#staff').val(),
        startDate: $('#startDate').val(),
        endDate: $('#endDate').val(),
        stuid: $('#student').val(),
        type: "GET_ALL_NOTE_TYPES",
        token: user["token"]
    };
    $.ajax({
        type: "POST",
        data: infoArray,
        url: "/requests/reportNotes.php",
        dataType: "json",
        success: function(json){
            getNotesSuccess(json);
        }
    });
}

function getNotesSuccess(json) {
    if (json["success"]) {
        var reports_array = json["result"];
        // Update dates
        for (var i = 0; i < reports_array.length; i++) {
            var date = moment(reports_array[i]["Date"], "YYYY/MM/DD HH:II:SS");
            reports_array[i]["date_display"] = date.format("DD/MM/YY");
            reports_array[i]["date_int"] = date.unix();
        }
        reports_array = orderArrayBy(reports_array, "date_int", true);
        stopSpinnerInDiv('report_notes_spinner');
        if (reports_array.length === 0) {
            $("#report_notes").hide();
        } else {
            $("#report_notes_main").show();
            parseReportNotes(reports_array);
        }
    } else {
        if (json["response"] === "INVALID_TOKEN") log_out();
        $("#report_notes").hide();
        stopSpinnerInDiv('report_notes_spinner');
        console.log("Error requesting notes");
        console.log(json["message"]);
    }
}

function parseReportNotes(reports_array) {
    $("#report_notes_notes").html("");
    for (var i = 0; i < reports_array.length; i++) {
        var date_display = reports_array[i]["date_display"];
        var name = reports_array[i]["WName"] ? reports_array[i]["WName"] : "-";
        var note = reports_array[i]["Note"];
        var style = i === (reports_array.length - 1) ? "border-bottom: none;" : "";
        var string = "<div class='report_note' style='" + style + "'><div class='note_details'><div class='note_details_date'>";
        string += "<p>" + date_display + "</p>";
        string += "</div><div class='note_details_name'>";
        string += "<p>" + name + "</p>";
        string += "</div></div><div class='note_text'>";
        string += "<p>" + note + "</p>";
        string += "</div></div>";
        $("#report_notes_notes").append(string);
    }
}

function getParameterByName(name, url) {
    if (!url) {
      url = window.location.href;
    }
    name = name.replace(/[\[\]]/g, "\\$&");
    var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"),
        results = regex.exec(url);
    if (!results) return null;
    if (!results[2]) return '';
    return decodeURIComponent(results[2].replace(/\+/g, " "));
}
