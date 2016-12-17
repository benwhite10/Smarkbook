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
    
    //setUpNotes();
    
    //getQuestionAverages();
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

/* DOM interaction methods */

function clickBackground(e, background){
    if(e.target == background){
        $(background).fadeOut();
    }
}

/* Helper methods */

/* Updates */

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

/* Display */

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
    return true;
}

function clickCancel(){
    location.reload();
    return false;
}