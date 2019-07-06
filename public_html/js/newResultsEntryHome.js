var user;

$(document).ready(function(){
    user = JSON.parse(localStorage.getItem("sbk_usr"));
    window.addEventListener("valid_user", function(){init_page();});
    validateAccessToken(user, ["SUPER_USER", "STAFF", "STUDENT"]);
});

function init_page() {
    writeNavbar(user);
    setUpSets(true);
}

function setUpSets(firstTime){
    var infoArray = {
        orderby: "Name",
        desc: "FALSE",
        token: user["token"],
        staff: user["userId"],
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
    var sets = json["sets"];
    //var setid = firstTime ? $("#originalGroup").val() : 0;
    var setid = 0;
    var str = "";
    if(sets.length === 0){
        str = "<option value=0>-No Sets-</option>";
    }else{
        for(var i = 0; i < sets.length; i++){
            var set = sets[i];
            var id = set["ID"];
            var name = set["Name"] + " (" + set["Initials"] + ")";
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
        token: user["token"],
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
    var url = "/studentWorksheetSummary.php?gw=" + gwid + "&s=" + user["userId"];
    window.location.href = url;
}

function setUpStaff(){
    var infoArray = {
        orderby: "Initials",
        token: user["token"]
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
        var staff = json["response"];
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