/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

$(document).ready(function(){
    $('#editForm').submit(function(){
        
        var validationMessage = validate();
        if(validationMessage !== true){
            setUpErrorMessage(validationMessage);
            console.log(validationMessage);
            return false;
        }
        convertAllToLowerCase();

        if(document.getElementById("tags")){
            var tagsString = document.getElementById("tags").value;
            var tagsArray = convertToArray(tagsString);
            var json = JSON.stringify(tagsArray);
        }
        
        // Create a new element input, this will be our update string. 
        var p = document.createElement("input");

        // Add the new element to our form. 
        this.appendChild(p);
        p.name = "updateTags";
        p.type = "hidden";
        p.value = json;

        return true;
    });
});

function validate(){
    // Worksheet
    if(document.getElementById("worksheetname") && document.getElementById("worksheetname").value.length === 0){
        return "Please enter a name for your worksheet.";
    }
    // Version
    if(document.getElementById("versionname") && document.getElementById("versionname").value.length === 0){
        return "Please enter a version name for your worksheet.";
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

function getIdFromTag(string){
    var pos = allTagNamesLowerCase.indexOf(string.toLowerCase());
    if (pos === -1){
        return ["NEW", string];
    }else{
        return ["CURRENT", allTagIds[pos]];
    }
}

function convertAllToLowerCase(){
    allTagNamesLowerCase = allTagNames.map(function(value) {
      return value.toLowerCase();
    });
}