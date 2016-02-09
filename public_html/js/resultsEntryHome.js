$(document).ready(function(){
   changeType();
   setUpSets(true);
   addDate();
});

function setUpStaff(){
    var infoArray = {};
    infoArray["orderby"] = "Initials";
    $.ajax({
        type: "POST",
        data: infoArray,
        url: "requests/getStaff.php",
        dataType: "json",
        success: staffSuccess,
        error: function(){
            console.log("There was an error retrieving the staff.");
        }
    });
}

function staffSuccess(json){
    if(json["success"]){
        var staff = json["staff"];
        var userid = document.getElementById("creatingStaffMember").value;
        var str = "<option value=0>-Initials-</option>";
        var str2 = "<option value=0>-Initials-</option>";
        for(var i = 0; i < staff.length; i++){
            var teacher = staff[i];
            var id = teacher["User ID"];
            var initials = teacher["Initials"];
            if(id === userid){ 
                str2 += "<option value=" + id + " selected>" + initials + "</option>"; 
            } else{
                str2 += "<option value=" + id + ">" + initials + "</option>";
            }
            str += "<option value=" + id + ">" + initials + "</option>";
        }
        document.getElementById("creatingStaff").innerHTML = str2;
        document.getElementById("assisstingStaff1").innerHTML = str;
        document.getElementById("assisstingStaff2").innerHTML = str;
    } else {
        console.log("There was an error retrieving the staff.");
    }
}

function setUpWorksheets(){
    var infoArray = {};
    infoArray["group"] = document.getElementById("group") ? document.getElementById("group").value : 0;
    infoArray["staff"] = document.getElementById("creatingStaffMember") ? document.getElementById("creatingStaffMember").value : 0;
    var url = "requests/getWorksheets.php";

    if(document.getElementById("type") && document.getElementById("type").value == 2){
        infoArray["type"] = "FILTERED";
        infoArray["orderby"] = "DueDate";
        infoArray["desc"] = "TRUE";
    }else{
        infoArray["type"] = "ALL";
        infoArray["orderby"] = "WName";
        infoArray["desc"] = "FALSE";
    }
    
    $.ajax({
        type: "POST",
        data: infoArray,
        url: url,
        dataType: "json",
        success: worksheetsSuccess,
        error: function(){
            console.log("There was an error retrieving the worksheets.");
        }
    });
}

function worksheetsSuccess(json){
    if(json["success"]){
        var worksheets = json["worksheets"];
        var worksheetid = 0;
        var str = "";
        if(worksheets.length === 0){
            str = "<option value=0>-No Worksheets-</option>";
        } else {
            for(var i = 0; i < worksheets.length; i++){
                var worksheet = worksheets[i];
                var name = worksheet["WName"];
                var vname = worksheet["VName"];
                var date = worksheet["DueDate"];
                var id = worksheet["ID"];
                if(date !== undefined){
                    str += "<option value=" + id + ">" + name + " - " + date + "</option>";
                }else if(vname !== undefined){
                    str += "<option value=" + id + ">" + name + " - " + vname + "</option>";
                }else{
                    str += "<option value=" + id + ">" + name + "</option>";
                }
            }
        }
    } else {
        console.log("There was an error retrieving the worksheets.");
    }
    document.getElementById("worksheet").innerHTML = str;
}

function countForEachName(names){
    var counts = {};
    for(var i = 0; i < names.length; i++){
        if(names[i].childNodes[0] !== undefined){
            var name = names[i].childNodes[0].nodeValue;
            counts[name] = counts[name] ? counts[name] + 1 : 1; 
        }
    }
    return counts;
}

function setUpSets(firstTime){
    var infoArray = {orderby: "Name", desc: "FALSE"};
    var type = "SETSBYSTAFF";
    infoArray["type"] = type;
    infoArray["staff"] = document.getElementById("creatingStaffMember") ? document.getElementById("creatingStaffMember").value : 0;
    $.ajax({
        type: "POST",
        data: infoArray,
        url: "requests/getGroup.php",
        dataType: "json",
        success: function(json) {
            if(json["success"]){
                setsSuccess(json, firstTime);
            } else {
                console.log("There was an error setting up the sets");
            }
        },
        error: function(json) {
            console.log("There was an error setting up the sets");
        }
    });
}

function setUpStudents(){
    if(document.getElementById("level")){
        var level = document.getElementById("level").value;
        if(level === "1"){
            document.getElementById("students").style.display = "none";
            document.getElementById("studentslabel").style.display = "none";
        }else{
            document.getElementById("students").style.display = "inline-block";
            document.getElementById("studentslabel").style.display = "inline-block";
            var infoArray = {orderby: "SName", desc: "FALSE"};
            var type = "STUDENTSBYSET";
            infoArray["type"] = type;
            infoArray["set"] = document.getElementById("group") ? document.getElementById("group").value : 0;
            $.ajax({
                type: "POST",
                data: infoArray,
                url: "requests/getStudents.php",
                dataType: "json",
                success: studentsSuccess,
                error: function() {
                    console.log("There was an error loading the students.");
                }
            });
        }
    }
}

function studentsSuccess(json){
    if(json["success"]){
        var students = json["students"];
        var studentid = 0;
        var str = "";
        if(students.length === 0){
            str = "<option value=0>-No Students-</option>";
        }
        for(var i = 0; i < students.length; i++){
            var student = students[i];
            var id = student["ID"];
            var name = (student["PName"] === undefined || student["PName"] === "") ? student["FName"] : student["PName"];
            var sname = student["SName"];
            if(id === studentid){ 
                str += "<option value=" + id + " selected>" + name + " " + sname + "</option>"; 
            } else{
                str += "<option value=" + id + ">" + name + " " + sname + "</option>";
            }
        }
    } else {
        console.log("There was an error loading the students.");
    }
    document.getElementById("students").innerHTML = str;
}

function setsSuccess(json, firstTime){
    var sets = json["sets"]
    var setid = firstTime ? $("#originalGroup").val() : 0;
    var str = "";
    if(sets.length === 0){
        str = "<option value=0>-No Sets-</option>";
    }else{
        for(var i = 0; i < sets.length; i++){
            var set = sets[i];
            var id = set["ID"];
            var name = set["Name"]
            if(id === setid){ 
                str += "<option value=" + id + " selected>" + name + "</option>"; 
            } else{
                str += "<option value=" + id + ">" + name + "</option>";
            }
        }
    }
    document.getElementById("group").innerHTML = str;
    setUpStudents();
    if(document.getElementById("type") && parseInt(document.getElementById("type").value) === 2){
        setUpWorksheets();
    }
}

function changeType(){
    if(document.getElementById("type") && document.getElementById("level")){
        var type = document.getElementById("type").value;
        var level = document.getElementById("level").value;
        var value = type + level;
        var text = "";
        switch(value){
            case "11":
                text = "This will allow you to enter a new set of tracked results for a specific group or set. " +
                       "If you have existing marks for the same group and worksheet then this will add a new set of marks in addition to the existing marks. " +
                       "If you wish to overwrite the existing marks instead then please select 'Edit Existing Results' instead.";
                showHideAdditionalStaff(true);
                break;
            case "12":
                text = "This will allow you to enter a new result for an individual student. " +
                       "If the student has existing marks for the worksheet then a new set of marks will be added. " +
                       "If you wish to overwrite the existing marks instead then please select 'Edit Existing Results' instead.";
                showHideAdditionalStaff(true);
                break;
            case "21":
                text = "This will allow you to either edit or add to an existing set of results for a specific group and worksheet. " +
                       "Here you will be able to add results for any students that have not yet completed the worksheet and have the results included with the others. ";
                showHideAdditionalStaff(false);
                break;
            case "22":
                text = "This will allow you to edit an existing set of marks for an individual student";
                showHideAdditionalStaff(false);
                break;
        }
        setUpStaff();
        setUpWorksheets();
        var textDisplay = document.getElementById("typeDescription");
        if(textDisplay){
            textDisplay.innerHTML = "<i>" + text + "</i>";
        }
    }
}

function showHideAdditionalStaff(show){
    if(show){
        $("#assisstingStaff").show();
    } else {
        $("#assisstingStaff").hide();
    }
}

function changeGroup(){
    if(document.getElementById("type") && document.getElementById("type").value == 2){
        setUpWorksheets();
    }
    setUpStudents();
}

function changeStaffMember(){
    if(document.getElementById("creatingStaffMember") && document.getElementById("creatingStaff")){
        document.getElementById("creatingStaffMember").value = document.getElementById("creatingStaff").value;
    }
    setUpSets(false);
}

function addDate(){
    var dateString = moment().format("DD/MM/YYYY");
    document.getElementById("datedue").value = dateString;
}