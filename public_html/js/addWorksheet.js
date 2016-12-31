
$(document).ready(function(){
    requestAllStaff();
    
    $(function() {
        $( "#datepicker" ).datepicker({ dateFormat: 'dd/mm/yy' });
    });
});

function requestAllStaff() {
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
            requestStaffSuccess(json);
        }
    });
}

function requestStaffSuccess(json) {
    if(json["success"]) {
        var staff = json["staff"];
        $("#worksheet_author").html("");
        var options = "<option value='0' selected>Author</option>";
        for (var key in staff) {
            var teacher = staff[key];
            var userid = teacher["User ID"];
            options += "<option value='" + userid + "'>" + teacher["Initials"] + "</option>";
        }
        $("#worksheet_author").html(options);
        $("#worksheet_author").val($('#userid').val());
    } else {
        console.log("There was an error getting the staff: " + json["message"]);
    }
}

function createWorksheet() {
    var validation = validate();
    if (validation === true) {
        // Create worksheet
        createWorksheetRequest();
    } else {
        alert (validation);
    }
}

function createWorksheetRequest() {
    var array_to_send = {
        name: $("#worksheetname").val(),
        link: $("#link").val(),
        author: $("#worksheet_author").val(),
        date: $("#datepicker").val(),
        questions: $("#questions").val()
    };
    var infoArray = {
        type: "NEWWORKSHEET",
        array: array_to_send,
        userid: $('#userid').val(),
        userval: $('#userval').val()
    };
    $.ajax({
        type: "POST",
        data: infoArray,
        url: "/requests/worksheet.php",
        dataType: "json",
        success: function(json){
            createWorksheetSuccess(json);
        },
        error: function(response){
            console.log("Request failed with status code: " + response.status + " - " + response.statusText);
        }
    });
}

function createWorksheetSuccess(json) {
    if (json["success"]) {
        window.location.href = "/editWorksheet_new.php?id=" + json["result"];
    } else {
        console.log("There was an error creating the worksheet: " + json["message"]);
    }
}

function validate(){
    // Worksheet
    if(document.getElementById("worksheetname") && document.getElementById("worksheetname").value.length === 0){
        return "Please enter a name for your worksheet.";
    }

    //Date
    var datepicker = document.getElementById("datepicker");
    if(datepicker){
        var dateString = datepicker.value;
        if(dateString.length === 0){
            return "Please enter the date that your worksheet was created.";
        }
        var date = moment(dateString, ["DD/MM/YYYY", "DD/MM/YY"]);
        if(!date.isValid()){
            return "You have entered an invalid date. Please enter a date in the form 'DD/MM/YYYY";
        }
        datepicker.value = date.format("DD/MM/YYYY");
    }

    //Author
    if(document.getElementById("author") && document.getElementById("author").value === "0"){
        return "Please enter an author for your worksheet.";
    }
    
    //Number of questions
    if(document.getElementById("questions")){
        var questions = document.getElementById("questions").value;
        if(isNaN(questions) || questions < 1 || questions%1 !== 0 || questions.length === 0){
            return "Please enter a valid number of questions for your worksheet, every worksheet must have at least 1 question.";
        }
    }
        
    return true;
}

function setUpErrorMessage(message){
    var errorInput = document.getElementById("message");
    errorInput.style.display = "block";
    errorInput.className = "error";
    var errorMessage = document.getElementById("messageText");
    errorMessage.innerHTML = "<p>" + message + "</p>";
}

function convertToArray(string){
    string = string.trim();
    if(string.substr(string.length-1) === ','){
        string = string.substring(0, string.length - 1);
    }
    var array = string.split(',');
    // TODO What happens for a ',' in the middle!!!
    // NEEDS TO BE SORTED OUT
    for(var i = 0; i < array.length; i++){
        var tag = array[i];
        tag = tag.trim();
        if (tag.length === 0){
            array[i] = ["NULL", "NULL"];
        }else{
            array[i] = getIdFromTag(tag);
        }
    }
    return array;
}