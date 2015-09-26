/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

var count;
var hiddenTagsName;
var elem;
var flag;
var currentTagsString;
var allTagNamesLowerCase;

$('#editForm').submit(function(){
    //Start a loop to loop through all of the questions
    count = 0;
    flag = true;
    var allCurrentTagsArray = [];
    var allNewTagsArray = [];
    var questionIds = [];
    
    convertAllToLowerCase();
    
    while(flag){
        //Check if there are any tags
        increaseCount();
        
        var currentTagsString = getCurrentTags('currTags');
        var newTagsString = getCurrentTags('tags');
        var qid = getCurrentTags('a');
        
        if (qid){
            //Convert the current tag string into an array
            allCurrentTagsArray.push(convertToArray(currentTagsString));
            //Convert the new tag sring into an array
            allNewTagsArray.push(convertToArray(newTagsString));
            //Add the question id
            questionIds.push(qid);
        }
    }
    
    var updateTagsString = "";
    
    //Loop through each question and see which tags need to be added and which deleted
    for(var i = 0; i < allCurrentTagsArray.length; i++){
        var oldArray = allCurrentTagsArray[i];
        var newArray = allNewTagsArray[i];
        
        console.log(oldArray);
        console.log(newArray);

        //Loop through the new array, if it doesn't appear on the old array then it should be added
        for (var j = 0; j < newArray.length; j++){
            if(oldArray.indexOf(newArray[j]) === -1 && newArray[j] !== ""){
                //Add the value
                var string = newArray[j];
                if(string.substring(0, 5) === '9%e3]'){
                    updateTagsString = updateTagsString + questionIds[i] + ":" + string.substring(5,string.length) + ":NEW/"; 
                }else{
                    updateTagsString = updateTagsString + questionIds[i] + ":" + newArray[j] + ":ADD/";  
                }               
            }
        }
        //Loop through the old array, if it doesn't appear on the new array then it should be deleted
        for (var j = 0; j < oldArray.length; j++){
            if(newArray.indexOf(oldArray[j]) === -1){
                //Delete the value
                updateTagsString = updateTagsString + questionIds[i] + ":" + oldArray[j] + ":DELETE/";
            }
        }
    }
    
    if(updateTagsString.length > 1){
        updateTagsString = updateTagsString.slice(0, -1);
    }
    
    console.log(updateTagsString);
    
    // Create a new element input, this will be our hashed password field. 
    var p = document.createElement("input");
 
    // Add the new element to our form. 
    this.appendChild(p);
    p.name = "updateTags";
    p.type = "hidden";
    p.value = updateTagsString;
        
    return true;
});

function getCurrentTags(ending){
    var currentTagsString;
    hiddenTagsName = count + ending;
    elem = document.getElementsByName(hiddenTagsName);
    if(elem.length === 0){
        flag = false;
    }else{
        currentTagsString = elem[0].value;
        return currentTagsString;
    }  
}

function increaseCount(){
    count++;
}

function convertToArray(string){
    string = string.trim();
    if(string.substr(string.length-1) === ','){
        string = string.substring(0, string.length - 1);
    }
    var array = string.split(',');
    var tag;
    //What happens for one in the middle!!!
    // NEEDS TO BE SORTED OUT
    for(var i = 0; i < array.length; i++){
        tag = array[i];
        tag = tag.trim();
        if (tag.length === 0){
            array[i] = "";
        }else{
            array[i] = getIdFromTag(tag);
        }
    }
    return array;
}

function getIdFromTag(string){
    var pos = allTagNamesLowerCase.indexOf(string.toLowerCase());
    if (pos === -1){
        return '9%e3]' + string;
    }else{
        return allTagIds[pos];
    }
}

function convertAllToLowerCase(){
    allTagNamesLowerCase = allTagNames.map(function(value) {
      return value.toLowerCase();
    });
    console.log(allTagNamesLowerCase);
}