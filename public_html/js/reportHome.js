$(document).ready(function(){  
    $("#variablesInputBoxShowHideButton").click(function(){
        showHideButton("variablesInputMain", "variablesInputBoxShowHideButton");
    });
    
    setUpVariableInputs();
});

/* Section set up methods */
function setUpVariableInputs(){
    localStorage.setItem("initialRun", true);
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

/* Requests */

function getStaff(){
    $.ajax({
        type: "POST",
        data: {"orderby": "Initials"},
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
        staff: $('#staff').val()
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
        set: $('#set').val()
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
    sendReportRequest();
    setInputsTitle();
    return false;
}

function sendReportRequest(){
    var infoArray = {
        type: "STUDENTREPORT",
        startDate: $('#startDate').val(),
        endDate: $('#endDate').val(),
        student: $('#student').val(),
        staff: $('#staff').val(),
        set: $('#set').val()
    };
    console.log(infoArray);
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
        localStorage.setItem("tagResults", JSON.stringify(json["result"]));
        refreshTagResults();
    } else {
        console.log("Something went wrong generating the reports.");
    }
}

function refreshTagResults(){
    $('#top5tags tbody').html('');
    $('#bottom5tags tbody').html('');
    $('#alltags tbody').html('');
    var results = JSON.parse(localStorage.getItem("tagResults"));
    if(results === null){
        showTagResults(false);
    } else {
        showTagResults(true);
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

function showTagResults(results){
    showHideAllSections(results);
    showHideNoResults(!results);
}

function showHideAllSections(show){
    if(show){
        $("#tagsReport").show();
    } else {
        $("#tagsReport").hide();
    }
}

function showHideNoResults(show){
    if(show){
        $("#noResults").show();
    } else {
        $("#noResults").hide();
    }
}