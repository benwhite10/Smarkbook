$(document).ready(function(){
    setWorksheetID();
    requestAllStaff();
    requestAllTags();
    
    $(window).resize(function(){
       
    });
});

function showHideDetails() {
    if($("#worksheet_details").css("display") === "none"){
        $("#worksheet_details_button").addClass("minus");
    } else {
        $("#worksheet_details_button").removeClass("minus");
    }
    $("#worksheet_details").slideToggle();
}

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
        var options = "<option value='0' selected>No Teacher</option>";
        for (var key in staff) {
            var teacher = staff[key];
            var userid = teacher["User ID"];
            options += "<option value='" + userid + "'>" + teacher["Initials"] + "</option>";
        }
        $("#worksheet_author").html(options);
        getWorksheetInfo();
    } else {
        console.log("There was an error getting the staff: " + json["message"]);
    }
}

function requestAllTags(){
    var infoArray = {
        type: "GETALLTAGS",
        userid: $('#userid').val(),
        userval: $('#userval').val()
    };
    $.ajax({
        type: "POST",
        data: infoArray,
        url: "/requests/tags.php",
        dataType: "json",
        success: function(json){
            allTagsRequestSuccess(json);
        },
        error: function(response){
            console.log("Request failed with status code: " + response.status + " - " + response.statusText);
        }
    });
}

function allTagsRequestSuccess(json) {
    if(json["success"]) {
        sessionStorage.setItem("tags_list", JSON.stringify(json["tagsInfo"]));
    } else {
        console.log("There was an error requesting the tags: " + json["message"]);
    }
}

function setWorksheetID() {
    var wid = getParameterByName("id");
    sessionStorage.setItem("worksheet_id", wid);
}

function getWorksheetInfo() {
    var wid = sessionStorage.getItem("worksheet_id");
    var infoArray = {
        wid: wid,
        type: "WORKSHEETINFO",
        userid: $('#userid').val(),
        userval: $('#userval').val()
    };
    $.ajax({
        type: "POST",
        data: infoArray,
        url: "/requests/getWorksheet.php",
        dataType: "json",
        success: function(json){
            getWorksheetSuccess(json);
        },
        error: function(json){
            console.log("There was an error requesting the worksheet: " + json["message"]);
        }
    });
}

function getWorksheetSuccess(json) {
    if (json["success"]) {
        var worksheet = json["worksheet"];
        var details = worksheet["details"];
        var questions = worksheet["questions"];
        var worksheet_tags = worksheet["tags"];
        parseWorksheetDetails(details);
        parseWorksheetMarks(questions);
        parseWorksheetTags(worksheet_tags);
        parseQuestions(questions);
    } else {
        console.log("There was an error getting the worksheets: " + json["message"]);
    }
}

function parseTagsForDiv(div_id) {
    var tags = $("#" + div_id + "_input_values").val();
    var tags_array = tags.split(":");
    var html_input_string = "";
    for (var i in tags_array) {
        var tag = getTagForID(tags_array[i]);
        if (tag) html_input_string += getTagInputHTML(div_id,tag["Name"],getTypeFromId(tag["TypeID"]),tag["Tag ID"]); 
    }
    $("#" + div_id + "_input").html(html_input_string);
}

function getTagForID(tag_id) {
    var tags = JSON.parse(sessionStorage.getItem("tags_list"));
    for (var i in tags) {
        var tag = tags[i];
        if (parseInt(tag["Tag ID"]) === parseInt(tag_id)) return tag;
    }
    return null;
}

function getIDForTag(tag_name) {
    var tags = JSON.parse(sessionStorage.getItem("tags_list"));
    for (var i in tags) {
        var tag = tags[i];
        var name = tag["Name"];
        if (tag_name.toLowerCase() === name.toLowerCase()) return tag["Tag ID"];
    }
    return null;
}

function addTagIDForInput(div_id, tag_id) {
    var tags = $("#" + div_id + "_input_values").val();
    if (tags.length === 0) {
        tags += tag_id;
    } else {
        tags += ":" + tag_id;
    }
    $("#" + div_id + "_input_values").val(tags);
}

function removeTagIDFromInput(div_id, tag_id) {
    var tags = $("#" + div_id + "_input_values").val();
    var tags_array = tags.split(":");
    var new_string = "";
    for (var i in tags_array) {
        var tag = tags_array[i];
        if (parseInt(tag) !== parseInt(tag_id)) {
            new_string += new_string.length === 0 ? tag : ":" + tag;
        }
    }
    $("#" + div_id + "_input_values").val(new_string);
}

function setUpTagSelect(div_id) {
    var tags = JSON.parse(sessionStorage.getItem("tags_list"));
    var tags_string = "";
    for (var i in tags) {
        var tag = tags[i];
        tags_string += "<option data-value='" + tag["Tag ID"] + "'>" + tag["Name"] + "</option>";
    }
    $("#" + div_id + "_list").html(tags_string);
}

function getTagInputHTML(div_id, tag_name, tag_type, tag_id) {
    var str = "<div class='tag " + tag_type.toLowerCase() + "'>";
    str += "<div class='tag_text'>" + tag_name + "</div>";
    str += "<div class='tag_button' onclick='deleteTag(&quot;" + div_id + "&quot;," + tag_id + ")'></div></div>";
    return str;
}

function clearTagFromList(list_id, tag_id) {
    var list = document.getElementById(list_id);
    var tag_array = list.children;
    var len = tag_array.length;
    while(len > 0) {
        len--;
        var tag_data = tag_array[len].dataset;
        var id = tag_data["value"];
        if (parseInt(id) === parseInt(tag_id)) {
            list.removeChild(tag_array[len]);
            return;
        }
    }
}

function addTagToList(list_id, tag_id) {
    var html = $("#" + list_id).html();
    var tag = getTagForID(tag_id);
    html += "<option data-value='" + tag["Tag ID"] + "'>" + tag["Name"] + "</option>";
    $("#" + list_id).html(html);
}

function parseWorksheetTags(worksheet_tags) {
    $("#worksheet_tags").html(stringForBlankTagEntry("worksheet_tags"));
    $("#worksheet_tags_input_text").keydown(function(event){
        changeTagInput(event);
    });
    setUpTagSelect("worksheet_tags");
    for (var j in worksheet_tags) {
        var tag = worksheet_tags[j];
        addTagIDForInput("worksheet_tags", tag["ID"]);
        clearTagFromList("worksheet_tags_list", tag["ID"]);
    }
    parseTagsForDiv("worksheet_tags");
}

function parseQuestions(questions) {
    var questions_html = "";
    for (var i in questions) {
        var question = questions[i];
        var div_id = "question_" + question["Question ID"];
        questions_html += "<div id='" + div_id + "' class='worksheet_question_div'>";
        questions_html += stringForQuestionDetails(div_id, question);
        questions_html += stringForBlankTagEntry(div_id);
        questions_html += "</div>";
    }
    $("#worksheet_questions").html(questions_html);
    for (var i in questions) {
        var question = questions[i];
        var div_id = "question_" + question["Question ID"];
        var tags = question["Tags"];
        $("#" + div_id + "_input_text").keydown(function(event){
            changeTagInput(event);
        });
        setUpTagSelect(div_id);
        for (var j in tags) {
            var tag = tags[j];
            addTagIDForInput(div_id, tag["Tag ID"]);
            clearTagFromList(div_id + "_list", tag["Tag ID"]);
        }
        parseTagsForDiv(div_id);
    }
}

function stringForQuestionDetails(div_id, question) {
    var label = question["Number"] ? question["Number"] : "-";
    var marks = question["Marks"] ? question["Marks"] : "-";
    var html = "<div id='" + div_id + "_details' class='worksheet_question_details'>";
    html += "<div class='wqd_question_text'>Question</div>";
    html += "<div contenteditable='true' class='wqd_question_input'>" + label + "</div>";
    html += "<div class='wqd_marks_input'><input type='text' id='ques_marks_" + question["Question ID"] + "' class='question_marks_input' onchange='updateMark(" + question["Question ID"] + ",0)' value=" + marks + " /></div>";
    html += "<div class='wqd_marks_text'>Marks:</div></div>";
    return html;
}

function stringForBlankTagEntry(div_id) {
    var html = "<div id='" + div_id + "_tags_entry' class='worksheet_question_tags_entry'>";
    html += "<input type='hidden' id='" + div_id + "_input_values' />";
    html += "<div id='" + div_id + "_input' class='tags_input'></div>";
    html += "<div id='" + div_id + "_input_suggested' class='tags_input suggested'></div>";
    html += "<div id='" + div_id + "_input_text_div' class='tags_input_text_div'>";
    html += "<input id='" + div_id + "_input_text' class='tags_input_text' type='text' list='" + div_id + "_list' placeholder='Enter tags here'>";
    html += "<datalist id='" + div_id + "_list'></datalist></div></div>";
    return html;
}

function parseWorksheetDetails(details) {
    var name = details["WName"];
    var link = details["Link"];
    var author = details["Author ID"];
    var date = moment(details["Date Added"]);
    var date_text = date.format("DD/MM/YYYY");
    $("#title2").html("<h1>" + name + "</h1>");
    $("#worksheet_name").val(name);
    $("#worksheet_link").val(link);
    $("#worksheet_author").val(author);
    $("#worksheet_date").val(date_text);
}

function parseWorksheetMarks(questions) {
    var ques_row = "<td class='worksheet_marks'><b>Ques</b></td>";
    var marks_row = "<td class='worksheet_marks'><b>Marks</b></td>";
    var totalMarks = 0;
    for (var i in questions) {
        var question = questions[i];
        var marks = question["Marks"];
        totalMarks += parseInt(marks);
        ques_row += "<td class='worksheet_marks'><b>" + question["Number"] + "</b></td>";
        marks_row += "<td class='worksheet_marks'><input type='text' id='ques_marks_summary_" + question["Question ID"] + "' class='marks_input' value='" + marks + "' onchange='updateMark(" + question["Question ID"] + ",1)' /></td>";
    }
    ques_row += "<td class='worksheet_marks'><b>Total</b></td>";
    marks_row += "<td class='worksheet_marks' id='ques_marks_summary_total' ><b>" + totalMarks + "</b></td>";
    $("#worksheet_marks_ques").html(ques_row);
    $("#worksheet_marks_marks").html(marks_row);
}

function getParameterByName(name, url) {
    if (!url) {
      url = window.location.href;
    }
    name = name.replace(/[\[\]]/g, "\\$&");
    var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"),
        results = regex.exec(url);
    if (!results) return null;
    if (!results[2]) return '';
    return decodeURIComponent(results[2].replace(/\+/g, " "));
}

function changeTagInput(e) {
    if(e && e.keyCode === 13) {
        var input_id = e.currentTarget.id;
        var div_id = input_id.substring(0, input_id.length - 11);
        var tag_name = $("#" + div_id + "_input_text").val();
        var tag_id = getIDForTag(tag_name);
        if (tag_id) {
            addTagIDForInput(div_id, tag_id);
            $("#" + div_id + "_input_text").val("");
            parseTagsForDiv(div_id);
            clearTagFromList(div_id + "_list", tag_id);
        } else { 
            console.log("Add tag: " + tag_name);
        }
    }
}

function deleteTag(div_id, tag_id) {
    removeTagIDFromInput(div_id, tag_id);
    $("#" + div_id + "_input_text").val("");
    parseTagsForDiv(div_id);
    addTagToList(div_id + "_list", tag_id);
}

function getTypeFromId(type_id) {
    switch(type_id) {
        case "1":
        case 1:
            return "Classification";
        case "2":
        case 2:
            return "Major";
        case "3":
        case 3:
            return "Minor";
        default:
            return "Minor";
    }
}

function updateMark(q_id, summary) {
    if (summary === 0) {
        var new_val = $("#ques_marks_" + q_id).val(); 
        $("#ques_marks_summary_" + q_id).val(new_val); 
    } else {
        var new_val = $("#ques_marks_summary_" + q_id).val();
        $("#ques_marks_" + q_id).val(new_val); 
    }
    updateTotalMarks();
}

function updateTotalMarks() {
    var array = document.getElementsByClassName("marks_input");
    var totalMarks = 0;
    for (var i = 0; i < array.length; i++) {
        var mark = array[i];
        if (mark.value !== "") totalMarks += parseInt(mark.value);
    }
    $("#ques_marks_summary_total").html("<b>" + totalMarks + "</b>");
}