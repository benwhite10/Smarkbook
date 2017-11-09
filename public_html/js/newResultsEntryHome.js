$(document).ready(function(){
    setUpSets(true);
});

function setUpSets(firstTime){
    var infoArray = {
        orderby: "Name",
        desc: "FALSE",
        userid: $('#userid').val(),
        userval: $('#userval').val(),
        staff: $('#userid').val(),
        type: "SETSBYSTUDENT"
    };

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

function setsSuccess(json, firstTime){
    var sets = json["sets"]
    //var setid = firstTime ? $("#originalGroup").val() : 0;
    var setid = 0;
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
    setUpWorksheets();
}

function setUpWorksheets(){
    var infoArray = {
        userid: $('#userid').val(),
        userval: $('#userval').val(),
        type: "STUDENTEDITABLESHEETS"
    };
    infoArray["group"] = document.getElementById("group") ? document.getElementById("group").value : 0;

    $.ajax({
        type: "POST",
        data: infoArray,
        url: "requests/getWorksheets.php",
        dataType: "json",
        success: worksheetsSuccess,
        error: function(json){
            console.log("There was an error retrieving the worksheets.");
            console.log(json);
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
                var date = worksheet["Date"];
                var id = worksheet["GWID"];
                var initials = worksheet["Initials"];
                if(date !== undefined){
                    str += "<option value=" + id + " id='w" + id + "'>" + name + " (" + initials + " - " + date + ")</option>";
                }else{
                    str += "<option value=" + id + " id='w" + id + "'>" + name + " (" + initials + ")</option>";
                }
            }
        }
    } else {
        console.log("There was an error retrieving the worksheets.");
    }
    document.getElementById("worksheet").innerHTML = str;
    var vid = $("#originalWorksheet").val();
    var option = document.getElementById("w" + vid);
    if(option) option.selected = true;
}

function changeGroup(){
    setUpWorksheets();
}

function goToInput() {
    var gwid = $("#worksheet").val();
    var url = "/studentWorksheetSummary.php?gw=" + gwid + "&s=" + $("#userid").val();
    window.location.href = url;
}



function setUpStaff(){
    var infoArray = {
        orderby: "Initials",
        userid: $('#userid').val(),
        userval: $('#userval').val()
    };
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



/*
function setUpStudents(){
    if(document.getElementById("level")){
        var level = document.getElementById("level").value;
        if(level === "1"){
            document.getElementById("students").style.display = "none";
            document.getElementById("studentslabel").style.display = "none";
        }else{
            document.getElementById("students").style.display = "inline-block";
            document.getElementById("studentslabel").style.display = "inline-block";
            var set = document.getElementById("group") ? document.getElementById("group").value : 0;
            var infoArray = {
                orderby: "SName",
                desc: "FALSE",
                type: "STUDENTSBYSET",
                set: set,
                userid: $('#userid').val(),
                userval: $('#userval').val()
            };
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
*/
