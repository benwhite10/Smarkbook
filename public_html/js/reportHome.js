$(document).ready(function(){  
    $("#variablesInputBoxShowHideButton").click(function(){
        showHideButton("variablesInputMain", "variablesInputBoxShowHideButton");
    });
    
    $("#worksheetSummaryDetails").click(function(){
        showHideWorksheetDetails();
    });
    
    showAllSections();
    showAllSpinners();
    setUpVariableInputs(); 
});

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
    localStorage.setItem("initialRun", true);
    disableGenerateReportButton();
    getStaff();
    setDates();
}

function setDates(){
    if($("#start").val()){
        $("#startDate").val($("#start").val());
    }
    if($("#end").val()){
        $("#endDate").val($("#end").val());
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
        userid: $('#userid').val(),
        userval: $('#userval').val()
    };
    $.ajax({
        type: "POST",
        data: infoArray,
        url: "/requests/getStaff.php",
        dataType: "json",
        success: function(json){
            getStaffSuccess(json);
        }
    });
}

function updateSets(){
    disableGenerateReportButton()
    var infoArray = {
        orderby: "Name",
        desc: "FALSE",
        type: "SETSBYSTAFF",
        staff: $('#staff').val(),
        userid: $('#userid').val(),
        userval: $('#userval').val()
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

function updateStudents(){
    disableGenerateReportButton();
    var infoArray = {
        orderby: "SName",
        desc: "FALSE",
        type: "STUDENTSBYSET",
        set: $('#set').val(),
        userid: $('#userid').val(),
        userval: $('#userval').val()
    };
    $.ajax({
        type: "POST",
        data: infoArray,
        url: "/requests/getStudents.php",
        dataType: "json",
        success: function(json){
            updateStudentsSuccess(json);
        }
    });
}

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

function sendReportRequest(){
    var reqid = generateNewReqId();
    var infoArray = {
        reqid: reqid,
        startDate: $('#startDate').val(),
        endDate: $('#endDate').val(),
        student: $('#student').val(),
        staff: $('#staff').val(),
        set: $('#set').val(),
        userid: $('#userid').val(),
        userval: $('#userval').val()
    };
    localStorage.setItem("activeReportRequest", JSON.stringify(infoArray));
    infoArray["type"] = "STUDENTREPORT";
    $.ajax({
        type: "POST",
        data: infoArray,
        url: "/requests/getStudentSummary.php",
        dataType: "json",
        success: function(json){
            reportRequestSuccess(json);
        }
    });
    sendSummaryRequest(infoArray);
}

/* Responses */

function getStaffSuccess(json){
    if(json["success"]){
        var staff = json["staff"];
        var htmlValue = staff.length === 0 ? "<option value='0'>No Teachers</option>" : "";
        $('#staff').html(htmlValue);
        for (var key in staff) {
            $('#staff').append($('<option/>', { 
                value: staff[key]["Staff ID"],
                text : staff[key]["Initials"] 
            }));
        }
        var initialVal = $('#staffid').val();
        if($("#staff option[value='" + initialVal + "']").length !== 0){
            $('#staff').val(initialVal);
        }
        updateSets();
    } else {
        console.log("Something went wrong loading the staff");
    }
}

function updateSetsSuccess(json){
    if(json["success"]){
        var sets = json["sets"];
        var htmlValue = sets.length === 0 ? "<option value='0'>No Sets</option>" : "";
        $('#set').html(htmlValue);
        for (var key in sets) {
            $('#set').append($('<option/>', { 
                value: sets[key]["ID"],
                text : sets[key]["Name"] 
            }));
        }
        var initialVal = $('#setid').val();
        if($("#set option[value='" + initialVal + "']").length !== 0){
            $('#set').val(initialVal);
        }
        updateStudents();
    } else {
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
        var initialVal = $('#studentid').val();
        if($("#student option[value='" + initialVal + "']").length !== 0){
            $('#student').val(initialVal);
        }
        studentChange();
    } else {
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
        console.log("Something went wrong generating the reports.");
    }
}

function summaryRequestSuccess(json){
    if(validateResponse(json)){
        var results = json["result"];
        if(results !== null){
            localStorage.setItem("summary", JSON.stringify(json["result"]["summary"]));
            localStorage.setItem("userAverage", Math.round(parseFloat(json["result"]["stuAvg"]) * 100));
            localStorage.setItem("setAverage", parseInt(parseFloat(json["result"]["setAvg"]) * 100));
        } else {
            localStorage.setItem("summary", null);
            localStorage.setItem("userAverage", null);
            localStorage.setItem("setAverage", null);
        }
        refreshSummaryResults();
    } else {
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
    sendReportRequest();
    setInputsTitle();
    return false;
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
    var userAvg = parseInt(JSON.parse(localStorage.getItem("userAverage")));
    var setAvg = parseInt(JSON.parse(localStorage.getItem("setAverage")));
    $('#summaryReportUserAvgValue').text(userAvg + "%");
    $('#summaryReportSetAvgValue').text(setAvg + "%");
    $('#summaryReportSetAvgValue').css('color', getColourForPercentage(setAvg));
    $('#summaryReportUserAvgValue').css('color', getColourForPercentage(userAvg));
    
    setWorksheetsSummary();
    setWorksheetsTable();
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

function setWorksheetsTable(){
    var summary = JSON.parse(localStorage.getItem("summary"));
    if(summary !== null){
        var list = summary["worksheetList"];
        for(var key in list){
            var sheet = list[key];
            var name = sheet["WName"];
            var date = sheet["DateDue"];
            var lateString = "-";
            var comp = "-";
            var student = "-";
            var set = "-";
            var classString = "worksheetSummaryTable noResults";
            if(sheet["Results"]){
                var stuScore = parseInt(100 * sheet["StuAVG"]);
                student = sheet["StuMark"] + "/" + sheet["StuMarks"] + " (" + stuScore + "%)";
                var setScore = parseInt(100 * sheet["AVG"]);
                var setMarks = sheet["Marks"];
                var setMark = parseInt(setMarks * sheet["AVG"]);
                set = setMark + "/" + setMarks + " (" + setScore + "%)";
                var late = sheet["StuDays"];
                if(late === "" || late === null){
                    lateString = "-";
                } else if(late === 0 || late === "0") {
                    lateString = "On Time";
                } else {
                    lateString = late + " Days Late";
                }
                comp = sheet["StuComp"];
                classString = "worksheetSummaryTable";
            }
            var string = "<tr class='" + classString + "'>";
            string += "<td class='worksheetName'>" + name + "</td>";
            string += "<td>" + date + "</td>";
            string += "<td>" + student + "</td>";
            string += "<td>" + set + "</td>";
            string += "<td>" + comp + "</td>";
            string += "<td>" + lateString + "</td>";
            string += "</tr>";
            $('#worksheetSummaryTable tbody').append(string);
        }
    }
}

function setNewHalfWidthTagResults(tag, position){
    var name = tag["name"];
    var marks = tag["marks"];
    var mark = tag["mark"];
    var recentMarks = tag["recentmarks"];
    var recentMark = tag["recentmark"];
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
}

function showSuggestedQuestions(){
    stopSpinnerInDiv('questionsReportSpinner');
    $("#questionsReportMain").show();
}

function getColourForPercentage(value){
    var red = Math.min(255, parseInt(255 + (5.1 * (50 - value))));
    var green = parseInt(value * 2.55);
    var blue = Math.max(0, parseInt(2 * (value - 50)));
    return "rgb(" + red + ", " + green + ", " + blue + ")";
}

function setNoResults(){
    hideAllSections();
}

function hideAllSections(){
    $("#tagsReport").hide();
    $("#summaryReport").hide();
    $("#questionsReport").hide();
    $("#noResults").show();
}

function showAllSections(){
    $("#tagsReport").show();
    $("#summaryReport").show();
    $("#questionsReport").show();
    $("#noResults").hide();
    $("#showHideWorksheetText").text("Show Worksheets \u2193");
}

function hideAllContent(){
    $("#tagsReportSummary").hide();
    $("#tagsReportShort").hide();
    $("#tagsReportFull").hide();
    $("#summaryReportMain").hide();
    $("#summaryReportDetails").hide();
    $("#questionsReportMain").hide();
}

function showAllSpinners(){
    hideAllContent();
    startSpinnerInDiv('tagsReportSpinner');
    startSpinnerInDiv('summaryReportSpinner');
    startSpinnerInDiv('questionsReportSpinner');
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