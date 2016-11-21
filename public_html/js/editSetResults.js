$(document).ready(function(){
    $("#popUpBackground").click(function(e){
        clickBackground(e, this);
    });
    
    $("#summaryBoxShowHide").click(function(){
        showHideDetails();
    });
    
    $(window).resize(function(){
       repositionStatusPopUp();
    });
    
    setUpNotes();
    
    getQuestionAverages();
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

/* Set up notes */

function setUpNotes() {
    var gwid = $("#gwid").val();
    var infoArray = {
        gwid: gwid,
        type: "JUSTNOTES",
        userid: $('#userid').val(),
        userval: $('#userval').val()
    };
    $.ajax({
        type: "POST",
        data: infoArray,
        url: "/requests/getWorksheet.php",
        dataType: "json",
        success: function(json){
            addNotesToInputs(json);
        },
        error: function(json){
            console.log("There was an error retrieving the notes");
        }
    });
}

function addNotesToInputs(json){
    if(json["success"]){
        var notes = json["notes"]
        for (var note in notes)
        {
            var stuID = note;
            var realNote = notes[stuID]["Notes"];
            $("#note" + stuID).val(realNote);
        }
    } else {
        console.log("There was an error retrieving the notes");
    } 
}

/* DOM interaction methods */

function clickBackground(e, background){
    if(e.target == background){
        $(background).fadeOut();
    }
}

function dateStatusChange(value){
    showHideDate(value);
    repositionStatusPopUp();
}

function completionStatusChange(value){
    if(value == "Completed" || value == "Partially Completed"){
        $("#popUpDateStatusSelect").prop("disabled", false);
    } else {
        $("#popUpDateStatusSelect").val(0);
        $("#popUpDateStatusSelect").prop("disabled", true);
    }
    dateStatusChange(parseInt($("#popUpDateStatusSelect").val()));
    repositionStatusPopUp();
}

function dueDateChange(){
    setDaysLate();
}

/* Load Pop Up */

function showStatusPopUp(stuID){
    setTitleAndMarks(stuID);
    setPopUpCompletionStatus(stuID);
    setDateStatus(stuID);
    setDateDue(stuID);
    setNote(stuID);
    $("#popUpStudent").val(stuID);
    $("#popUpBackground").fadeIn();
    repositionStatusPopUp();
}

function setTitleAndMarks(stuID){
    var id = "#stu" + stuID;
    $("#popUpName").text($(id).text());
    var stuMarksArray = getStudentMarks(stuID);
    $("#popUpMarks").text(stuMarksArray[0]);
    $("#popUpMarks").attr('class', stuMarksArray[1]);
}

function setPopUpCompletionStatus(stuID){
    var elem = document.getElementById("comp" + stuID);
    var status = elem.value;
    $("#popUpCompletionStatusSelect").val(status);
    completionStatusChange(status)
}

function setDateStatus(stuID){
    var elem = document.getElementById("date" + stuID);
    var status = elem.value;
    if(status == "On Time"){
        $("#popUpDateStatusSelect").val(1);
        showHideDate(1);
    } else if (status == "-") {
        $("#popUpDateStatusSelect").val(0);
        showHideDate(0);
    } else {
        $("#popUpDateStatusSelect").val(2);
        showHideDate(2);
    } 
}

function setDateDue(stuID){
    //Get the date the worksheet was due in
    var dateDueString = $("#dateDueMain").val();
    var dateDue = moment(dateDueString, "DD/MM/YYYY");
    
    //Get current hand in date for student
    var daysLate = $("#daysLate" + stuID).val() != "" ? parseInt($("#daysLate" + stuID).val()) : 0;
    if(daysLate == 0){
        daysLate += 1;
    }
    // TODO check this
    var dateHandedIn = moment(dateDueString, "DD/MM/YYYY");
    dateHandedIn.add(daysLate, 'd');
    
    //Set the day, month and year for that date
    $("#day").val(parseInt(dateHandedIn.format("DD")));
    $("#month").val(parseInt(dateHandedIn.format("MM")));
    $("#year").val(parseInt(dateHandedIn.format("YYYY")));
    
    //Set the date due text
    $("#dateDueText").text(dateDueString);
    
    //Set the number of days late
    var daysLate = calculateHowLate(dateDue, dateHandedIn);
    parseDaysLate(daysLate);
}

function setDaysLate(){
    //Get current hand in date for student
    var dateHandedIn = getDateFromPicker();
    
    //Get the due date
    var dueDate = moment($("#dateDueText").text(), "DD/MM/YYYY");
    
    var daysLate = calculateHowLate(dueDate, dateHandedIn);
    parseDaysLate(daysLate);
}

function parseDaysLate(daysLate){
    if(daysLate < 0){
        $("#daysLateText").text("Not late");
        $("#daysLateText").addClass("notLate");
    }else if(daysLate == 0){
        $("#daysLateText").text("On Time");
        $("#daysLateText").addClass("notLate");
    }else if(daysLate == 1){
        $("#daysLateText").text(daysLate + " day late");
        $("#daysLateText").removeClass("notLate");
    } else {
        $("#daysLateText").text(daysLate + " days late");
        $("#daysLateText").removeClass("notLate");
    } 
}

function setNote(stuID){
    var id = "#note" + stuID;
    $("#popUpNoteText").val($(id).val());
}

/* Helper methods */

function showHideDate(value){
    if(value == 2){
        $("#popUpDateHandedIn").show();
        $("#popUpDateDue").show();
    } else {
        $("#popUpDateHandedIn").hide();
        $("#popUpDateDue").hide();
    } 
}

function getDateFromPicker(){
    var day = $("#day").val();
    var month = $("#month").val();
    var year = $("#year").val();
    var date = moment();
    date.date(day);
    date.month(parseInt(month) - 1);
    date.year(year);
    return date;
}

function calculateHowLate(dateDue, dateHandedIn){
    var duration = moment.duration(dateHandedIn.diff(dateDue));
    var durationDays = duration.asDays();
    return Math.floor(durationDays) >= 0 ? Math.floor(durationDays) : -1;
}

function getStudentMarks(student){
    var elem = document.getElementById("questioncount");
    var count = elem.value;
    var marks = 0;
    var outOf = 0;
    for(i = 1; i <= count; i++){
        var stuMarksElem = document.getElementById(student + "-" + i);
        var totalMarksElem = document.getElementById("ques" + i);
        if(stuMarksElem.value != ""){
            marks += parseInt(stuMarksElem.value);
            outOf += parseInt(totalMarksElem.value);
        }
    }
    var totalMarks = $("#totalMarks").val();
    return [marks + "/" + outOf, getWorksheetClass(totalMarks, outOf)];
}

function getWorksheetClass(totalMarks, outOf){
    if(totalMarks == outOf){
        return "complete";
    } else if (outOf == 0) {
        return "incomplete";
    } else {
        return "partial";
    }
}

/* Updates */

function changeResult(value, student, question){
    var lock = $("#lock" + student).val();
    if(validateResult(value, student, question)){
        if(!lock){
            updateCompletionStatus(student);
        }
        updateResults(student);
    } else {
        $("#" + student + "-" + question).val("");
        $("#" + student + "-" + question).focus();
    }
    getQuestionAverages();
}

function updateCompletionStatus(student){
    var state = checkAllCompleted(student);
    var id = "comp" + student;
    var elem = document.getElementById(id);
    var dateid = "date" + student;
    var dateElem = document.getElementById(dateid);
    $("#comp" + student).removeClass("partial");
    $("#comp" + student).removeClass("late");
    if(state === "INCOMPLETE"){
        $("#comp" + student).val("Not Required");
        $("#date" + student).val("-");
    }else if (state === "PARTIAL"){
        $("#comp" + student).val("Partially Completed");
        $("#date" + student).val("On Time");
        $("#comp" + student).addClass("partial");
        $("#daysLate" + student).val("0");
    }else if (state === "COMPLETE"){
        $("#comp" + student).val("Completed");
        $("#date" + student).val("On Time");
        $("#daysLate" + student).val("0");
    }
}

function updateResults(student){
    // Loop through each question
    var num = parseInt($("#count" + student).val() - 1);
    var totalMark = 0;
    var totalMarks = 0;
    for(var i = 1; i <= num; i++){
        var mark = $("#" + student + "-" + i).val();
        var marks = $("#ques" + i).val();
        if(mark !== "" && mark !== null){
            totalMark += parseInt(mark);
            totalMarks += parseInt(marks);
        }
    }
    $("#total" + student).text(totalMark + " / " + totalMarks);
}

function checkAllCompleted(student){
    var elem = document.getElementById("questioncount");
    var count = elem.value;
    var blank = 0;
    var full = 0;
    for(i = 1; i <= count; i++){
        var stuMarks = document.getElementById(student + "-" + i)
        if(stuMarks.value === ""){
            blank++;
        } else {
            full++;
        }
    }
    if(blank === 0){
        return "COMPLETE";
    } else if (full === 0){
        return "INCOMPLETE";
    } else {
        return "PARTIAL";
    }
}

function validateResult(value, student, question){
    if(isNaN(value)){
        incorrectInput("You have entered a value that is not a number.", student, question);
        return false;
    }
    var value = parseFloat(value);
    if(value < 0) {
        incorrectInput("You have entered a negative number of marks.", student, question);
        return false;
    }
    var elem = document.getElementById("ques" + question);
    var marks = parseInt(elem.value);
    if(marks < value){
        incorrectInput("You have entered too many marks for the question.", student, question);
        return false;
    }
    return true;
}

function incorrectInput(message, student, question){
    resetQuestion(student, question);
    alert(message);
}

function resetQuestion (student, question) {
    var id = student + "-" + question;
    var elem = document.getElementById(id);
    elem.focus();
    elem.value = "";
}

function div_show(){
    document.getElementById("popUpBackground").style.display = "block";
}
function div_hide(save){
    if(save){
        saveChanges();
    }
    document.getElementById("popUpBackground").style.display = "none";
}

function setCompletionStatus(student, value){
    $("#comp" + student).removeClass("partial");
    $("#comp" + student).removeClass("late");
    if(value == "Partially Completed"){
        $("#comp" + student).addClass("partial");
    } else if(value == "Incomplete"){
        $("#comp" + student).addClass("late");
    }
    $("#comp" + student).val(value);
}

function saveChanges(){
    var student = $("#popUpStudent").val();
    //Set comp status
    setCompletionStatus(student, $("#popUpCompletionStatusSelect").val());
    
    //Set date status
    var dateStatusVal = $("#popUpDateStatusSelect").val();
    var dateStatus = "-";
    $("#date" + student).removeClass("late");
    if(dateStatusVal == "0"){
        $("#daysLate" + student).val("");
    }else if(dateStatusVal == "1"){
        dateStatus = "On Time";
        $("#daysLate" + student).val("0");
    }else if(dateStatusVal == "2"){
        dateStatus = $("#daysLateText").text(); 
        var dateHandedIn = getDateFromPicker();
        var dateDue = moment($("#dateDueText").text(), "DD/MM/YYYY");
        var daysLate = calculateHowLate(dateDue, dateHandedIn);
        if(parseInt(daysLate) <= 0){
            $("#daysLate" + student).val("0");
            dateStatus = "On Time";
        } else {
            $("#daysLate" + student).val(daysLate);
            $("#date" + student).addClass("late");
        }
        
    }
    $("#date" + student).val(dateStatus);
    
    //Set note
    $("#note" + student).val($("#popUpNoteText").val());
}

/* Display */

function repositionStatusPopUp(){
    var height = $("#popUpBox").height() / 2;
    $("#popUpBox").attr("style", "margin-top: -" + height + "px");
}

function showHideDetails(){
    if($("#details").css("display") === "none"){
        $("#summaryBoxShowHide").addClass("minus");
    } else {
        $("#summaryBoxShowHide").removeClass("minus");
    }
    $("#details").slideToggle();
}

function changeDateDueMain(){
    var currentDateString = $("#summaryBoxShowDetailsTextMain").text();
    var newDate = $("#dateDueMain").val();
    $("#summaryBoxShowDetailsTextMain").text(currentDateString.slice(0,-10) + newDate);
}

function clickSave(){
    console.log("save");
    return true;
}

function clickCancel(){
    console.log("cancel");
    location.reload();
    return false;
}

function getQuestionAverages(){
    // Main questions
    var x = document.getElementsByClassName("markInput");
    var qAv = [];
    var qAvCount = [];
    for (var i = 0; i < x.length; i++){
        var mark = x[i];
        var markInfo = mark["id"].split("-");
        if(markInfo.length > 1) {
            var question = markInfo[1];
            if(qAv[question] && mark.value) {
                qAv[question] = parseInt(qAv[question]) + parseInt(mark.value);
                qAvCount[question]++;
            } else if (mark.value){
                qAv[question] = parseInt(mark.value);
                qAvCount[question] = 1;
            }
            
        }
    }
    
    var totals = document.getElementsByClassName("totalMarks");
    var total = 0;
    var totalMarks = 0;
    var totalCount = 0;
    for (var i = 0; i < totals.length; i++) {
        var marks = totals[i].innerText.split("/");
        if (marks[1] > 0) {
            total += parseInt(marks[0]);
            totalMarks += parseInt(marks[1]);
            totalCount++;
        }   
    }
    //Averages
    var totalAveragePercentage = Math.round(100 * total / totalMarks);
    var totalAvMark = Math.round(10*total/totalCount)/10;
    var totalAvMarks = Math.round(10*totalMarks/totalCount)/10;
    $("#averagePerc-ALL").text(totalAveragePercentage + "%");
    $("#average-ALL").text(totalAvMark + " / " + totalAvMarks);
    for (var i = 1; i < qAv.length; i++) {
        var average = qAv[i] /qAvCount[i];
        var rounded = Math.round(10 * average)/10;
        var marks = $("#average-mark-" + i).val();
        var percentage = Math.round(100 * average / marks);
        $("#average-" + i).text(rounded);
        $("#averagePerc-" + i).text(percentage + "%");
    }
}