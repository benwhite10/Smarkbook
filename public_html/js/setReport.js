$(document).ready(function(){  
    setUpVariableInputs(); 
});

/* Section set up methods */
function setUpVariableInputs(){
    disableGenerateReportButton();
    getStaff();
}

function disableGenerateReportButton(){
    $('#generateReportButton').hide();
}

function enableGenerateReportButton(){
    $('#generateReportButton').show();
}

/* Send Requests */

function getStaff(){
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
            getStaffSuccess(json);
        }
    });
}

function updateSets(){
    disableGenerateReportButton();
    var infoArray = {
        orderby: "Name",
        desc: "FALSE",
        type: "SETSBYSTAFF",
        staff: $('#staff').val(),
        userid: $('#userid').val(),
        userval: $('#userval').val()
    };
    $.ajax({
        type: "POST",
        data: infoArray,
        url: "/requests/getGroup.php",
        dataType: "json",
        success: function(json){
            updateSetsSuccess(json);
        }
    });
}

function getStaffSuccess(json){
    if(json["success"]){
        var staff = json["staff"];
        var htmlValue = staff.length === 0 ? "<option value='0'>No Teachers</option>" : "";
        $('#staff').html(htmlValue);
        for (var key in staff) {
            $('#staff').append($('<option/>', { 
                value: staff[key]["Staff ID"],
                text : staff[key]["Initials"] 
            }));
        }
        var initialVal = $('#staffid').val();
        if($("#staff option[value='" + initialVal + "']").length !== 0){
            $('#staff').val(initialVal);
        }
        updateSets();
    } else {
        console.log("Something went wrong loading the staff");
    }
}

function updateSetsSuccess(json){
    if(json["success"]){
        var sets = json["sets"];
        var htmlValue = sets.length === 0 ? "<option value='0'>No Sets</option>" : "";
        $('#set').html(htmlValue);
        for (var key in sets) {
            $('#set').append($('<option/>', { 
                value: sets[key]["ID"],
                text : sets[key]["Name"] 
            }));
        }
        var initialVal = $('#setid').val();
        if($("#set option[value='" + initialVal + "']").length !== 0){
            $('#set').val(initialVal);
        }
        updateTitle();
        enableGenerateReportButton();
    } else {
        console.log("Something went wrong loading the staff");
    }
}

function updateTitle() {
    var value = $("#set").val();
    $("#variablesInputBoxDetailsTextMain").text($("#set option[value='" + value + "']").text());
}

function generateReport() {
    updateTitle();
    sendReportRequest();
}

function sendReportRequest() {
    var infoArray = {
        type: "SETTAGREPORT",
        staff: $('#staff').val(),
        set: $('#set').val(),
        tags: $('#tags').val(),
        userid: $('#userid').val(),
        userval: $('#userval').val()
    };
    $.ajax({
        type: "POST",
        data: infoArray,
        url: "/requests/getSetReport.php",
        dataType: "json",
        success: function(json){
            getReportSuccess(json);
        }
    });
}

function getReportSuccess(json) {
    console.log(json);
    if(json["success"]) {
        $("#main_tables").html('');
        var results = json["result"];
        for (var key in results) {
            var result = results[key];
            var details = result["details"];
            var scores = result["scores"];
            var table_str = "<table class='student_table'><thead class='student_table_head'><tr class='student_table_head'><th class='student_table name' colspan='4'><h1 class='name_head'>";
            table_str += details["name"];
            table_str += "</h1></th></tr><tr class='student_table_head'><th class='student_table name'>Topic Name</th><th class='student_table value'>%</th><th class='student_table value'>Marks</th><th class='student_table value'>No. of Q's</th></tr></thead>";
            table_str += "<tbody class='student_table_body'>";
            for (var key2 in scores) {
                var score = scores[key2];
                var tag_name = score["name"];
                var mark = score["mark"];
                var marks = score["marks"];
                var count = score["count"];
                var percentage = Math.round(100*mark/marks);
                table_str += "<tr class='student_table_body'><td class='student_table name'>" + tag_name + "</td><td class='student_table value'>" + percentage + "</td>";
                table_str += "<td class='student_table value'>" + marks + "</td><td class='student_table value'>" + count + "</td></tr>";
            }
            table_str += "</tbody></table><footer></footer>";
            $("#main_tables").append(table_str);
        }
    } else {
        console.log("Fail");
    }
}

function printReport() {
    window.print();
}