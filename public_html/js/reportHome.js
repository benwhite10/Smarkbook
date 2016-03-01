$(document).ready(function(){  
    $("#variablesInputBoxShowHideButton").click(function(){
        showHideButton("variablesInputMain", "variablesInputBoxShowHideButton");
    });
    
    $("#worksheetSummaryDetails").click(function(){
        showHideWorksheetDetails();
    });
    
    setUpVariableInputs();
});

/* Section set up methods */
function setUpVariableInputs(){
    localStorage.setItem("initialRun", true);
    getStaff();
    setDates();
}

function showHideWorksheetDetails(){
    if($("#summaryReportDetails").is(":visible")){
        $("#showHideWorksheetText").text("Show Worksheets");
    } else {
        $("#showHideWorksheetText").text("Hide Worksheets");
    }
    $("#summaryReportDetails").slideToggle();
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

/* Requests */

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

function studentChange(){
    if(localStorage.getItem("initialRun") === "true"){
        generateReport();
        localStorage.setItem("initialRun", false);
    }
}

/* Summary show/hide buttons */

function showHideButton(mainId, buttonId){
    if($("#" + mainId).css("display") === "none"){
        $("#" + buttonId).addClass("minus");
    } else {
        $("#" + buttonId).removeClass("minus");
    }
    $("#" + mainId).slideToggle();
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

function showHideFullTagResults(){
    if($("#tagsReportShort").css("display") === "none"){
        $("#showHideFullTagResultsText").text("Show Full Results");
    } else {
        $("#showHideFullTagResultsText").text("Hide Full Results");
    }
    $("#tagsReportShort").slideToggle(800);
    $("#tagsReportFull").slideToggle(800);
}

/* Generate Report */
function generateReport(){
    showAllSections();
    showAllSpinners();
    sendReportRequest();
    sendSummaryRequest();
    setInputsTitle();
    return false;
}

function sendSummaryRequest(){
    var infoArray = {
        type: "STUDENTSUMMARY",
        startDate: $('#startDate').val(),
        endDate: $('#endDate').val(),
        student: $('#student').val(),
        staff: $('#staff').val(),
        set: $('#set').val(),
        userid: $('#userid').val(),
        userval: $('#userval').val()
    };
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
    var infoArray = {
        type: "STUDENTREPORT",
        startDate: $('#startDate').val(),
        endDate: $('#endDate').val(),
        student: $('#student').val(),
        staff: $('#staff').val(),
        set: $('#set').val(),
        userid: $('#userid').val(),
        userval: $('#userval').val()
    };
    $.ajax({
        type: "POST",
        data: infoArray,
        url: "/requests/getStudentSummary.php",
        dataType: "json",
        success: function(json){
            reportRequestSuccess(json);
        }
    });
}

function reportRequestSuccess(json){
    if(json["success"]){
        var results = json["result"];
        if(results !== null){
            localStorage.setItem("tagResults", JSON.stringify(json["result"]["tags"]));
        } else {
            localStorage.setItem("tagResults", null);
        }
        refreshTagResults();
    } else {
        console.log("Something went wrong generating the reports.");
    }
}

function summaryRequestSuccess(json){
    if(json["success"]){
        var results = json["result"];
        if(results !== null){
            localStorage.setItem("summary", JSON.stringify(json["result"]["summary"]));
            localStorage.setItem("userAverage", parseInt(parseFloat(json["result"]["stuAvg"]) * 100));
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

function refreshTagResults(){
    $('#top5tags tbody').html('');
    $('#bottom5tags tbody').html('');
    $('#alltags tbody').html('');
    var results = JSON.parse(localStorage.getItem("tagResults"));
    if(results === null){
        setNoResults();
    } else {
        showTagResults(false);
        var length = Object.keys(results).length;
        for(var count = 0; count < 5; count++){
            for(var key in results){
                if(count === results[key]["Rank"]){
                    $('#bottom5tags tbody').append(setHalfWidthTagResults(results, key, length));
                }
                if((length - count - 1) === results[key]["Rank"]){
                    $('#top5tags tbody').append(setHalfWidthTagResults(results, key, length));
                }
            }
        }

        for(var key in results){
            $('#alltags tbody').append(setHalfWidthTagResults(results, key, length));
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

function getColourForPercentage(value){
    var red = Math.min(255, parseInt(255 + (5.1 * (50 - value))));
    var green = parseInt(value * 2.55);
    var blue = Math.max(0, parseInt(2 * (value - 50)));
    return "rgb(" + red + ", " + green + ", " + blue + ")";
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

function setHalfWidthTagResults(results, key, length){
    var name = results[key]["Name"];
    var rel = parseInt(results[key]["Reliability"] * 100);
    var rank = length - parseInt(results[key]["Rank"]);
    var score = results[key]["Score"].toPrecision(2);
    var colourString = "rgb(" 
            + Math.min(parseInt(255 - (255 * score)), 255) 
            + ", " 
            + Math.min(parseInt(255 + (score * 255)), 255) 
            + ", 0)";
    var string = "<tr class='results'>";
    string += "<td class='results'>" + rank + ".</td>";
    string += "<td class='results' style='text-align:left;'>" + name + "</td>";
    string += "<td class='results'>" + rel + "% </td>";
    string += "<td class='results' style='background: " + colourString + ";' >" + score + "</td>";
    string += "</tr>";
    return string;
}

function setNoResults(){
    hideAllSections();
}

function hideAllSections(){
    $("#tagsReport").hide();
    $("#summaryReport").hide();
    $("#noResults").show();
}

function showAllSections(){
    $("#tagsReport").show();
    $("#summaryReport").show();
    $("#noResults").hide();
}

function hideAllContent(){
    $("#tagsReportSummary").hide();
    $("#tagsReportShort").hide();
    $("#tagsReportFull").hide();
    $("#summaryReportMain").hide();
    $("#summaryReportDetails").hide();
}

function showAllSpinners(){
    hideAllContent();
    startSpinnerInDiv('tagsReportSpinner');
    startSpinnerInDiv('summaryReportSpinner');
}

function startSpinnerInDiv(div){
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
    $('#' + div).data('spinner').stop();
    $('#' + div).hide();
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