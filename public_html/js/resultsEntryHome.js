$(document).ready(function(){
   changeType();
   setUpSets(true);
   addDate();
});

function setUpStaff(){
    $.ajax({
        type: "GET",
        url: "requests/getStaff.php?orderby=Initials",
        dataType: "xml",
        success: staffSuccess
    });
}

function staffSuccess(xml){
    var ids = xml.getElementsByTagName("UserID");
    var initials = xml.getElementsByTagName("Initials");
    var userid = document.getElementById("creatingStaffMember").value;
    var str = "<option value=0>-Initials-</option>";
    var str2 = "<option value=0>-Initials-</option>";
    for(i=0;i<ids.length;i++){
        if(initials[i].childNodes[0] !== undefined && ids[i].childNodes[0] !== undefined){
            var initial = initials[i].childNodes[0].nodeValue;
            var id = ids[i].childNodes[0].nodeValue;
            if(id == userid){ 
                str2 += "<option value=" + id + " selected>" + initial + "</option>"; 
            } else{
                str2 += "<option value=" + id + ">" + initial + "</option>";
            }
            str += "<option value=" + id + ">" + initial + "</option>";
        }

    }
    document.getElementById("creatingStaff").innerHTML = str2;
    document.getElementById("assisstingStaff1").innerHTML = str;
    document.getElementById("assisstingStaff2").innerHTML = str;
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
        $.ajax({
            type: "POST",
            data: infoArray,
            url: "requests/getWorksheets.php",
            dataType: "xml",
            success: filteredWorksheetsSuccess,
            error: function(){
                alert("Errrrrror");
            }
        });
    }else{
        infoArray["type"] = "ALL";
        infoArray["orderby"] = "WName";
        infoArray["desc"] = "FALSE";
        $.ajax({
            type: "POST",
            data: infoArray,
            url: "requests/getWorksheets.php",
            dataType: "xml",
            success: allWorksheetsSuccess,
            error: function(){
                alert("Errrrrror");
            }
        });
    }
}

function allWorksheetsSuccess(xml){
    if(xml.getElementsByTagName("result")){
        var ids = xml.getElementsByTagName("ID");
        var names = xml.getElementsByTagName("WName");
        var vnames = xml.getElementsByTagName("VName");
        var worksheetid = 0;
        var str = "";
        if(ids.length === 0){
            str = "<option value=0>-No Worksheets-</option>";
        }else{
            var counts = countForEachName(names);
            for(i=0;i<ids.length;i++){
                if(names[i].childNodes[0] !== undefined && ids[i].childNodes[0] !== undefined){
                    var name = names[i].childNodes[0].nodeValue;
                    var id = ids[i].childNodes[0].nodeValue;
                    if(counts[name] > 1 && vnames[i].childNodes[0] !== undefined){
                        var vname = vnames[i].childNodes[0].nodeValue;
                        str += "<option value=" + id + ">" + name + " - " + vname + "</option>";
                    }else{
                        str += "<option value=" + id + ">" + name + "</option>";
                    }

                }

            }
        }
        document.getElementById("worksheet").innerHTML = str;
    }
}

function checkResult(result){
    if (result[0].childNodes[0] !== undefined){
        if(result[0].childNodes[0].nodeValue === "TRUE"){
            return true;
        } else {
            console.log("Finding worksheets failed.");
            return false;
        }
    } else {
        console.log("Finding worksheets failed.");
        return false;
    }
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

function filteredWorksheetsSuccess(xml){
    if(xml.getElementsByTagName("result")){    
        var ids = xml.getElementsByTagName("GWID");
        var names = xml.getElementsByTagName("WName");
        var dates = xml.getElementsByTagName("DueDate");
        //var versions = xml.getElementsByTagName("VName");
        //var worksheetid = document.getElementById("creatingStaffMember").value;
        var worksheetid = 0;
        var str = "";
        if(ids.length === 0){
            str = "<option value=0>-No Worksheets-</option>";
        }else{
            for(i=0;i<ids.length;i++){
                if(names[i].childNodes[0] !== undefined && ids[i].childNodes[0] !== undefined && dates[i].childNodes[0] !== undefined){
                    var name = names[i].childNodes[0].nodeValue;
                    var date = dates[i].childNodes[0].nodeValue;
                    var gwid = ids[i].childNodes[0].nodeValue;
                    str += "<option value=" + gwid + ">" + name + " - " + date + "</option>";
                }
            }
        }
        document.getElementById("worksheet").innerHTML = str;
    }
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
        dataType: "xml",
        success: function(xml) {
            setsSuccess(xml, firstTime);
        },
        error: function(xml) {
            console.log(xml);
        }
    });
}

function setUpStudents(){
    if(document.getElementById("level")){
        var level = document.getElementById("level").value;
        if(level == 1){
            document.getElementById("students").style.display = "none";
            document.getElementById("studentslabel").style.display = "none";
        }else{
            document.getElementById("students").style.display = "inline-block";
            document.getElementById("studentslabel").style.display = "inline-block";
            var infoArray = {orderby: "SName", desc: "FALSE"};
            var type = "STUDENTSBYSET";
            infoArray["type"] = type;
            infoArray["set"] = document.getElementById("group") ? document.getElementById("group").value : 0;
            console.log(infoArray);
            $.ajax({
                type: "POST",
                data: infoArray,
                url: "requests/getStudents.php",
                dataType: "xml",
                success: studentsSuccess,
                error: function(xml) {
                    console.log(xml);
                }
            });
        }
    }
}

function studentsSuccess(xml){
    var ids = xml.getElementsByTagName("ID");
    var fnames = xml.getElementsByTagName("FName");
    var snames = xml.getElementsByTagName("SName");
    var pnames = xml.getElementsByTagName("PName");
    var studentid = 0;
    var str = "";
    if(ids.length === 0){
        str = "<option value=0>-No Students-</option>";
    }else{
        for(i=0;i<ids.length;i++){
            if(fnames[i].childNodes[0] !== undefined && ids[i].childNodes[0] !== undefined && fnames[i].childNodes[0] !== undefined){
                if(pnames[i] !== undefined){
                    var name = pnames[i].childNodes[0].nodeValue;
                }else{
                    var name = fnames[i].childNodes[0].nodeValue;
                }
                var sname = snames[i].childNodes[0].nodeValue;
                var id = ids[i].childNodes[0].nodeValue;
                if(id === studentid){ 
                    str += "<option value=" + id + " selected>" + name + " " + sname + "</option>"; 
                } else{
                    str += "<option value=" + id + ">" + name + " " + sname + "</option>";
                }
            }

        }
    }
    document.getElementById("students").innerHTML = str;
}

function setsSuccess(xml, firstTime){
    var ids = xml.getElementsByTagName("ID");
    var names = xml.getElementsByTagName("Name");
    var setid = firstTime ? $("#originalGroup").val() : 0;
    var str = "";
    if(ids.length === 0){
        str = "<option value=0>-No Sets-</option>";
    }else{
        for(i=0;i<ids.length;i++){
            if(names[i].childNodes[0] !== undefined && ids[i].childNodes[0] !== undefined){
                var name = names[i].childNodes[0].nodeValue;
                var id = ids[i].childNodes[0].nodeValue;
                if(id === setid){ 
                    str += "<option value=" + id + " selected>" + name + "</option>"; 
                } else{
                    str += "<option value=" + id + ">" + name + "</option>";
                }
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
        setUpStudents();
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