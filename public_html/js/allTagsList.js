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

$('#editForm').submit(function(){

    var tagsString = getTags();
    var array = convertToArray(tagsString); 
    var updateTagsString = "";
    
    //Loop through the array and add all the tags to the string
    for (var i = 0; i < array.length; i++){
        if(array[i] !== ""){
            var string = array[i];
            if(string.substring(0, 5) === '9%e3]'){
                updateTagsString = updateTagsString + string.substring(5,string.length) + ":NEW/"; 
            }else{
                updateTagsString = updateTagsString + string + ":ADD/";  
            }
        }
    }
    
    if(updateTagsString.length > 1){
        updateTagsString = updateTagsString.slice(0, -1);
    }
    
    // Create a new element input, this will be our hashed password field. 
    var p = document.createElement("input");
 
    // Add the new element to our form. 
    this.appendChild(p);
    p.name = "updateTags";
    p.type = "hidden";
    p.value = updateTagsString;
        
    return true;
});

function getTags(){
    elem = document.getElementsByName('tags');
    return elem[0].value;
}

function increaseCount(){
    count++;
}

function convertToArray(string){
    var array = string.split(',');
    var tag;
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
    var pos = allTagNames.indexOf(string);
    if (pos === -1){
        return '9%e3]' + string;
    }else{
        return allTagIds[pos];
    }
}
    //var count = 1;
    /*<?php 
        $count1 = 1;
        $varname = $count1 . 'currTags';
        if(isset($$varname)){
            $flag = true;
        }else{
            $flag = false;
            $$varname = "blank"; 
        }
        $count1++;
    ?> ;

    var flag = false;
    flag = <?php print $flag ? 'true' : 'false'; ?>;
    console.log(flag);

    while (flag){
        //Convert the string into an array of tag strings

        //Check for duplicate tags and remove

        //Convert the current tags into an array of tag strings          

        var currentTagsString = "<?php print $$varname; ?>";
        var currentTagsArray = currentTagsString.split(',');

        console.log(currentTagsArray);

        //Loop through the new tags, if they don't exist in the 
        //current tags then add to a list of tags to be added

        //Loop through the existing tags, if they don't exist in the 
        //new tags then add to a list of tags to be deleted

        //Convert both arrays into tag ids and then concatenate as a string
        //Pass the results to the php in the form of hidden inputs

        //Update the count and check if anything exists
        <?php 
            $varname = $count1 . 'currTags';
            if(isset($$varname)){
                $flag = true;
            }else{
                $flag = false;
                $$varname = "blank"; 
            }
            $count1++;
        ?> ; 
        console.log("<?php print $count1; ?>");

        flag = <?php print $flag ? 'true' : 'false'; ?>;

        //flag = false;
    }
    return false; 
});*/
        
