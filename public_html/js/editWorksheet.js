$(document).ready(function(){
    setWorksheetID();
    requestAllStaff();
    requestAllTags();
    startSpinnerInDiv('spinner');
    
    sessionStorage.setItem("save_requests", "[]");
    
    $(window).resize(function(){
       
    });
    
    // Get the modal
    var modal = document.getElementById('modal_add_new');

    // When the user clicks anywhere outside of the modal, close it
    window.onclick = function(event) {
        toggleMenuOff();
        if (event.target === modal) {
            closeModal();
        }
    };
    
    document.addEventListener("contextmenu", function(e) {
        var id = clickInsideElement(e, "tag", "suggested");
        if (id) {
            e.preventDefault();
            toggleMenuOn();
            positionMenu(e);
            context_tag_id = id;
        } else {
            toggleMenuOff();
        }
    });
});

var context_tag_id = "";

function clickInsideElement(e, className, notClassName) {
  var el = e.srcElement || e.target;

  if (el.classList.contains(className)) {
    return el.classList.contains(notClassName) ? false : el.id;
  } else {
    while (el = el.parentNode) {
      if (el.classList && el.classList.contains(className) ) {
        return el.classList.contains(notClassName) ? false : el.id;
      }
    }
  }
  return false;
}

function addAllQuestions() {
    var info = context_tag_id.split("-");
    var tag_id = info[0];
    addTagIDForInput("worksheet_tags", tag_id);
    clearTagFromList("worksheet_tags_list", tag_id);
    parseTagsForDiv("worksheet_tags");
    saveQuestion("worksheet_tags");
    requestSuggestedTags("worksheet_tags");
    var questions_string = sessionStorage.getItem("questions_array");
    var questions = questions_string === "" ? [] : JSON.parse(questions_string);
    for (var i in questions) {
        var question = questions[i];
        var div_id = "question_" + question["Stored Question ID"];
        addTagIDForInput(div_id, tag_id);
        clearTagFromList(div_id + "_list", tag_id); 
        parseTagsForDiv(div_id);
        requestSuggestedTags(div_id);
        saveQuestion(div_id);
    }  
}

function removeAllQuestions() {
    var info = context_tag_id.split("-");
    var tag_id = info[0];
    
    removeTagIDFromInput("worksheet_tags", tag_id);
    addTagToList("worksheet_tags_list", tag_id);
    parseTagsForDiv("worksheet_tags");
    saveQuestion("worksheet_tags");
    requestSuggestedTags("worksheet_tags");
    var questions_string = sessionStorage.getItem("questions_array");
    var questions = questions_string === "" ? [] : JSON.parse(questions_string);
    for (var i in questions) {
        var question = questions[i];
        var div_id = "question_" + question["Stored Question ID"];
        removeTagIDFromInput(div_id, tag_id);
        addTagToList(div_id + "_list", tag_id); 
        parseTagsForDiv(div_id);
        requestSuggestedTags(div_id);
        saveQuestion(div_id);
    }  
}

function toggleMenuOn() {
    $("#context-menu").addClass("context-menu--active");
}

function toggleMenuOff() {
    $("#context-menu").removeClass("context-menu--active");
}

function getPosition(e) {
  var posx = 0;
  var posy = 0;

  if (!e) var e = window.event;

  if (e.pageX || e.pageY) {
    posx = e.pageX;
    posy = e.pageY;
  } else if (e.clientX || e.clientY) {
    posx = e.clientX + document.body.scrollLeft + 
                       document.documentElement.scrollLeft;
    posy = e.clientY + document.body.scrollTop + 
                       document.documentElement.scrollTop;
  }

  return {
    x: posx,
    y: posy
  }
}

function positionMenu(e) {
  var menuPosition = getPosition(e);
  var menuPositionX = menuPosition.x + "px";
  var menuPositionY = menuPosition.y + "px";

  $("#context-menu").css("left", menuPositionX);
  $("#context-menu").css("top", menuPositionY);
}

function confirmLeave(){
    return "You have unchanged saves, if you leave the page then your changes will be saved.";  
}

function startSpinnerInDiv(div){
    stopSpinnerInDiv(div);
    var opts = {
      lines: 10             // The number of lines to draw
    , length: 9             // The length of each line
    , width: 4              // The line thickness
    , radius: 10            // The radius of the inner circle
    , scale: 1.0            // Scales overall size of the spinner
    , corners: 1           // Roundness (0..1)
    , color: '#000'         // #rgb or #rrggbb
    , left: '0%'           // center horizontally
    , position: 'relative'  // Element positioning
    };
    $("#" + div).show();
    var spinner = new Spinner(opts).spin($("#" + div).get(0));
    $($("#" + div).get(0)).data('spinner', spinner);
    $("#main_content").hide();
    $("#side_bar").hide();
}

function stopSpinnerInDiv(div){
    if($('#' + div).data('spinner') !== undefined){
        $('#' + div).data('spinner').stop();
        $('#' + div).hide();
    }
    $("#main_content").show();
    $("#side_bar").show();
}

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
        sessionStorage.setItem("questions_array", JSON.stringify(questions));
        parseWorksheetDetails(details);
        parseWorksheetMarks(questions);
        parseWorksheetTags(worksheet_tags);
        parseQuestions(questions);
        requestAllSuggestedTags(questions);
        stopSpinnerInDiv('spinner');
    } else {
        console.log("There was an error getting the worksheets: " + json["message"]);
    }
}

function requestAllSuggestedTags(questions) {
    requestSuggestedTags("worksheet_tags");
    for (var i in questions) {
        var question = questions[i];
        requestSuggestedTags("question_" + question["Stored Question ID"]);
    }
}

function parseTagsForDiv(div_id) {
    var tags = $("#" + div_id + "_input_values").val();
    var tags_string = getTagsString(tags);
    var tags_array = tags_string.split(":");
    var html_input_string = "";
    for (var i in tags_array) {
        var tag = getTagForID(tags_array[i]);
        if (tag) html_input_string += getTagInputHTML(div_id,tag["Name"],getTypeFromId(tag["TypeID"]),tag["Tag ID"]); 
    }
    $("#" + div_id + "_input").html(html_input_string);
}

function parseSuggestedTagsForDiv(div_id) {
    var tags = $("#" + div_id + "_suggested_values").val();
    var tags_array = tags.split(":");
    var html_input_string = "";
    for (var i in tags_array) {
        var tag = getTagForID(tags_array[i]);
        if (tag) html_input_string += getSuggestedTagInputHTML(div_id,tag["Name"],getTypeFromId(tag["TypeID"]),tag["Tag ID"]); 
    }
    $("#" + div_id + "_input_suggested").html(html_input_string);
}

function addSuggestedTag(div_id, tag_id) {
    addTagIDForInput(div_id, tag_id);
    clearTagFromList(div_id + "_list", tag_id); 
    parseTagsForDiv(div_id);
    removeSuggestedTagIDFromInput(div_id, tag_id);
    parseSuggestedTagsForDiv(div_id);
    requestSuggestedTags(div_id);
    saveQuestion(div_id);
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
    var tag_name_comparison = escapeString(tag_name).toLowerCase().trim();
    for (var i in tags) {
        var tag = tags[i];
        var name = tag["Name"];
        if (tag_name_comparison === name.toLowerCase().trim()) return tag["Tag ID"];
    }
    return null;
}

function addTagIDForInput(div_id, tag_id) {
    var tags = $("#" + div_id + "_input_values").val();
    if (tags.length === 0) tags = "--";
    tags = addTagString(tag_id, tags);
    $("#" + div_id + "_input_values").val(tags);
}

function addSuggestedTagIDForInput(div_id, tag_id) {
    var tags = $("#" + div_id + "_suggested_values").val();
    tags += tag_id + ":";
    $("#" + div_id + "_suggested_values").val(tags);
}

function addTagString(tag_id, tags) {
    var tags_types = tags.split("-");
    var tag = getTagForID(tag_id);
    var classification_tags = tags_types[0];
    var major_tags = tags_types[1];
    var minor_tags = tags_types[2];
    switch(parseInt(tag["TypeID"])) {
        case 1:
            if (!checkIfTagInTags(tag_id, classification_tags)) {
                classification_tags += classification_tags.length === 0 ? tag_id : ":" + tag_id;
            }
            break;
        case 2:
            if (!checkIfTagInTags(tag_id, major_tags)) {
                major_tags += major_tags.length === 0 ? tag_id : ":" + tag_id;
            }
            break;
        case 3:
        default:
            if (!checkIfTagInTags(tag_id, minor_tags)) {
                minor_tags += minor_tags.length === 0 ? tag_id : ":" + tag_id;
            }
            break;
    }
    return classification_tags + "-" + major_tags + "-" + minor_tags;
}

function checkIfTagInTags(tag_id, tags) {
    var tags_array = tags.split(":");
    for (var i in tags_array) {
        if(parseInt(tags_array[i]) === parseInt(tag_id)) return true;
    }
    return false;
}

function removeTagIDFromInput(div_id, tag_id) {
    var tags = $("#" + div_id + "_input_values").val();
    var tags_string = getTagsString(tags);
    var tags_array = tags_string.split(":");
    $("#" + div_id + "_input_values").val("");
    for (var i in tags_array) {
        var tag = tags_array[i];
        if (parseInt(tag) !== parseInt(tag_id)) {
            addTagIDForInput(div_id, tag);
        }
    }
}

function removeSuggestedTagIDFromInput(div_id, tag_id) {
    var tags = $("#" + div_id + "_suggested_values").val();
    var tags_array = tags.split(":");
    var new_string = "";
    for (var i in tags_array) {
        var tag = tags_array[i];
        if (parseInt(tag) !== parseInt(tag_id)) {
            new_string += new_string.length === 0 ? tag : ":" + tag;
        }
    }
    $("#" + div_id + "_suggested_values").val(new_string);
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
    var str = "<div id='" + tag_id + "-" + div_id + "' class='tag " + tag_type.toLowerCase() + "'>";
    str += "<div class='tag_text'>" + tag_name + "</div>";
    str += "<div class='tag_button' onclick='deleteTag(&quot;" + div_id + "&quot;," + tag_id + ")'></div></div>";
    return str;
}

function getSuggestedTagInputHTML(div_id, tag_name, tag_type, tag_id) {
    var str = "<div id='" + tag_id + "-" + div_id + "' class='tag suggested " + tag_type.toLowerCase() + "' onclick='addSuggestedTag(&quot;" + div_id + "&quot;, " + tag_id + ")'>";
    str += "<div class='tag_text'>" + tag_name + "</div>";
    str += "<div class='tag_button' ></div></div>";
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
        var div_id = "question_" + question["Stored Question ID"];
        questions_html += "<div id='" + div_id + "' class='worksheet_question_div'>";
        questions_html += stringForQuestionDetails(div_id, question);
        questions_html += stringForBlankTagEntry(div_id);
        questions_html += "</div>";
    }
    $("#worksheet_questions").html(questions_html);
    for (var i in questions) {
        var question = questions[i];
        var div_id = "question_" + question["Stored Question ID"];
        var tags = question["Tags"];
        $("#" + div_id + "_input_text").keydown(function(event){
            changeTagInput(event);
        });
        setUpTagSelect(div_id);
        for (var j in tags) {
            var tag = tags[j];
            if (tag["Deleted"] !== "1") {
                addTagIDForInput(div_id, tag["Tag ID"]);
                clearTagFromList(div_id + "_list", tag["Tag ID"]);
            }  
        }
        parseTagsForDiv(div_id);
    }
}

function stringForQuestionDetails(div_id, question) {
    var label = question["Number"] ? question["Number"] : "-";
    var marks = question["Marks"] ? question["Marks"] : "-";
    var html = "<div id='" + div_id + "_details' class='worksheet_question_details'>";
    html += "<div class='wqd_question_text'>Question</div>";
    html += "<div class='wqd_question_input'><input type='text' id='ques_label_" + question["Stored Question ID"] + "' class='question_label_input' onblur='updateLabel(" + question["Stored Question ID"] + ",0)' value=" + label + " /></div>";
    html += "<div class='wqd_delete_button' onclick='deleteQuestion(" + question["Stored Question ID"] + ")'></div>";
    html += "<div class='wqd_marks_input'><input type='text' id='ques_marks_" + question["Stored Question ID"] + "' class='question_marks_input' onblur='updateMark(" + question["Stored Question ID"] + ",0)' value=" + marks + " /></div>";
    html += "<div class='wqd_marks_text'>Marks:</div></div>";
    return html;
}

function stringForBlankTagEntry(div_id) {
    var html = "<div id='" + div_id + "_tags_entry' class='worksheet_question_tags_entry'>";
    html += "<input type='hidden' id='" + div_id + "_input_values' />";
    html += "<input type='hidden' id='" + div_id + "_suggested_values' />";
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
        ques_row += "<td class='worksheet_marks' id='summary_label_" + question["Stored Question ID"] + "' ><b>" + question["Number"] + "</b></td>";
        marks_row += "<td class='worksheet_marks'><input type='text' id='ques_marks_summary_" + question["Stored Question ID"] + "' class='marks_input' value='" + marks + "' onblur='updateMark(" + question["Stored Question ID"] + ",1)' /></td>";
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
            saveQuestion(div_id);
            $("#" + div_id + "_input_text").val("");
            parseTagsForDiv(div_id);
            clearTagFromList(div_id + "_list", tag_id);
            requestSuggestedTags(div_id);
        } else { 
            openModal(tag_name, div_id);
        }
    }
}

function deleteTag(div_id, tag_id) {
    removeTagIDFromInput(div_id, tag_id);
    saveQuestion(div_id);
    $("#" + div_id + "_input_text").val("");
    parseTagsForDiv(div_id);
    addTagToList(div_id + "_list", tag_id);
    requestSuggestedTags(div_id);
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
    var val_id = summary === 0 ? "#ques_marks_" + q_id : "#ques_marks_summary_" + q_id;
    var update_id = summary === 0 ? "#ques_marks_summary_" + q_id : "#ques_marks_" + q_id;
    var new_val = $(val_id).val();
    if (validateMarks(new_val)){
        $(update_id).val(parseInt(new_val));
        $(val_id).val(parseInt(new_val));
        saveQuestion("question_" + q_id);
        updateTotalMarks();
    } else {
        $(val_id).val($(update_id).val());
        $(val_id).focus();
        alert("Please enter a valid mark.\n\nAll marks should be positive integer values.");
    }
}

function updateLabel(q_id, summary) {
    $("#summary_label_" + q_id).html("<b>" + $("#ques_label_" + q_id).val() + "</b>");
    saveQuestion("question_" + q_id);
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

function validateMarks(mark) {
    return !isNaN(mark) && mark !== "" && parseInt(mark) === parseFloat(mark) && parseInt(mark) > 0;
}

function openModal(name, div_id) {
    requestSimilarTags(name);
    $("#add_new_tag_name").val(name);
    $("#modal_add_new").css("display", "block");
    $("#add_new_tag_type").val("minor");
    $("#tag_type_value").val(3);
    $("#add_new_tag_div_id").val(div_id);
    $("#add_new_tag_input").html("");
    $("#add_new_tag_input_values").val("");
}

function closeModal() {
    $("#modal_add_new").css("display", "none");
}

function changeNewTagType(type) {
    $("#add_new_tag_type").val(type);
    setSelectedType(type);
}

function setSelectedType(type) {
    $("#tag_type_classification").removeClass("selected");
    $("#tag_type_major").removeClass("selected");
    $("#tag_type_minor").removeClass("selected");
    $("#tag_type_" + type).addClass("selected");
    switch(type) {
        case "minor":
            $("#tag_type_value").val(3);
            break;
        case "major":
            $("#tag_type_value").val(2);
            break;
        case "classification":
            $("#tag_type_value").val(1);
            break;
    }
}

function requestSuggestedTags(div_id) {
    var tags = getTagsString($("#" + div_id + "_input_values").val());
    var infoArray = {
        type: "SUGGESTEDTAGS",
        tags: tags,
        div_id: div_id,
        userid: $('#userid').val(),
        userval: $('#userval').val()
    };
    $.ajax({
        type: "POST",
        data: infoArray,
        url: "/requests/worksheet.php",
        dataType: "json",
        success: function(json){
            suggestedTagsSuccess(json);
        },
        error: function(response){
            console.log("Request failed with status code: " + response.status + " - " + response.statusText);
        }
    });
}

function suggestedTagsSuccess(json) {
    if (json["success"]) {
        var suggested_tags = json["result"]["top_values"];
        console.log(suggested_tags);
        var div_id = json["result"]["div_id"];
        $("#" + div_id + "_suggested_values").val("");
        for (var i in suggested_tags) {
            var tag = suggested_tags[i];
            addSuggestedTagIDForInput(div_id, tag["ID"]);
        }
        parseSuggestedTagsForDiv(div_id);
    }
}

function requestSimilarTags(name) {
    var infoArray = {
        type: "SIMILARNEWTAGS",
        name: name,
        userid: $('#userid').val(),
        userval: $('#userval').val()
    };
    $.ajax({
        type: "POST",
        data: infoArray,
        url: "/requests/tags.php",
        dataType: "json",
        success: function(json){
            similarTagsRequestSuccess(json);
        },
        error: function(response){
            console.log("Request failed with status code: " + response.status + " - " + response.statusText);
        }
    });
}

function similarTagsRequestSuccess(json) {
    if(json["success"]) {
        var count = 10;
        var tags = json["tags"];
        for (var i = 0; i < count; i++) {
            addTagIDForInput("add_new_tag", tags[i]["Tag ID"]);
        }
        parseSimilarTagsForDiv("add_new_tag");
    } else {
        console.log("There was an error getting the similar tags: " + json["message"]);
    }
}

function parseSimilarTagsForDiv(div_id) {
    var tags = $("#" + div_id + "_input_values").val();
    var tags_array = tags.split(":");
    var html_input_string = "";
    for (var i in tags_array) {
        var tag = getTagForID(tags_array[i]);
        if (tag) html_input_string += getSimilarTagInputHTML(div_id,tag["Name"],getTypeFromId(tag["TypeID"]),tag["Tag ID"]); 
    }
    $("#" + div_id + "_input").html(html_input_string);
}

function getSimilarTagInputHTML(div_id, tag_name, tag_type, tag_id) {
    var str = "<div class='tag " + tag_type.toLowerCase() + "'>";
    str += "<div class='tag_text' onclick='addSimilarTag(" + tag_id + ")'>" + tag_name + "</div>";
    str += "<div class='tag_button' onclick='deleteTag(&quot;" + div_id + "&quot;," + tag_id + ")'></div></div>";
    return str;
}

function addSimilarTag(tag_id) {
    var root_id = $("#add_new_tag_div_id").val();
    addTagIDForInput(root_id, tag_id);
    saveQuestion(root_id);
    $("#" + root_id + "_input_text").val("");
    parseTagsForDiv(root_id);
    clearTagFromList(root_id + "_list", tag_id);
    closeModal();
}

function saveNewTag() {
    var name = $("#add_new_tag_name").val();
    var tag_type = $("#tag_type_value").val();
    var div_id = $("#add_new_tag_div_id").val();
    var infoArray = {
        type: "ADDNEWTAG",
        name: name,
        type_id: tag_type,
        div_id: div_id,
        userid: $('#userid').val(),
        userval: $('#userval').val()
    };
    $.ajax({
        type: "POST",
        data: infoArray,
        url: "/requests/tags.php",
        dataType: "json",
        success: function(json){
            addNewTagRequestSuccess(json);
        },
        error: function(response){
            console.log("Request failed with status code: " + response.status + " - " + response.statusText);
        }
    });
}

function addNewTagRequestSuccess(json) {
    if(json["success"]) {
        var tag_list = JSON.parse(sessionStorage.getItem("tags_list"));
        tag_list.push(json["tag"]);
        sessionStorage.setItem("tags_list", JSON.stringify(tag_list));
        var div_id = json["div_id"];
        var tag_id = json["tag"]["Tag ID"];
        addTagToAllLists(tag_id);
        addTagIDForInput(div_id, tag_id);
        saveQuestion(div_id);
        $("#" + div_id + "_input_text").val("");
        parseTagsForDiv(div_id);
        clearTagFromList(div_id + "_list", tag_id);
        closeModal();
    } else {
        console.log("Adding tag failed");
    }
}

function addTagToAllLists(tag_id) {
    var list_id = "worksheet_tags_list";
    addTagToList(list_id, tag_id);
    var elems = document.getElementsByClassName("worksheet_question_div");
    for (var i = 0; i < elems.length; i++) {
        var elem_id = elems[i].id;
        addTagToList(elem_id + "_list", tag_id);
    }
}

function escapeString(string) {
    string = string.replace("'", "&#39;");
    string = string.replace('"', "&#34;");
    return string;
}

function saveQuestion(div_id) {
    var save_requests = JSON.parse(sessionStorage.getItem("save_requests"));
    sessionStorage.setItem("save_requests", JSON.stringify(addRequestToSave(div_id, save_requests)));
    setSaveButton("Save");
}

function setSaveButton(status) {
    $("#save_worksheet_button").removeClass("saving");
    $("#save_worksheet_button").removeClass("save");
    var button = document.getElementById("save_worksheet_button");
    if (status === "Save") {
        $("#save_worksheet_button").html("Save");
        button.onclick = function() { saveWorksheet(null); };
        $("#save_worksheet_button").addClass("save");
        window.onbeforeunload = confirmLeave;
    } else if (status === "Saving") {
        $("#save_worksheet_button").html("Saving..."); 
        $("#save_worksheet_button").addClass("saving");
        button.onclick = function() { };
        window.onbeforeunload = confirmLeave;
    } else {
        $("#save_worksheet_button").html("Save"); 
        button.onclick = function() { };
        window.onbeforeunload = null;
    }
}

function addRequestToSave(div_id, save_requests) {
    for (var i = 0; i < save_requests.length; i++) {
        if (save_requests[i] === div_id) return save_requests;
    }
    save_requests.push(div_id);
    return save_requests;
}

function saveWorksheet(delete_sqid) {
    if (!checkLock("save_worksheet_request_lock")) {
        saveWorksheetRequest(delete_sqid);
    } else {
        var save_interval = setInterval(function() {
            if (!checkLock("save_worksheet_request_lock")) {
                saveWorksheetRequest(delete_sqid);
                clearInterval(save_interval);
            }
        }, 1000);
    }   
}

function saveWorksheetRequest(delete_sqid) {
    var save_worksheet_array = JSON.parse(sessionStorage.getItem("save_requests"));
    if (save_worksheet_array.length === 0 && delete_sqid === null) return; 
    
    sessionStorage.setItem("save_requests", "[]");
    setSaveButton("Saving");
    
    var wid = sessionStorage.getItem("worksheet_id");
    var req_id = generateRequestLock("save_worksheet_request_lock");
    var array_to_send = [];
    for (var i = 0; i < save_worksheet_array.length; i++) {
        var type = save_worksheet_array[i];
        if (type === "worksheet_tags") {
            var tags = getTagsString($("#worksheet_tags_input_values").val()); 
            var array = {
                type: type,
                wid: wid,
                tags: tags
            };
            array_to_send.push(array);
        } else if (type === "worksheet_details"){
            var name = $("#worksheet_name").val();
            var link = $("#worksheet_link").val();
            var date = $("#worksheet_date").val();
            var author = $("#worksheet_author").val();
            var array = {
                type: type,
                wid: wid,
                name: name,
                link: link,
                date: date,
                author: author
            };
            array_to_send.push(array);
        } else {
            var sqid = type.substring(9);
            var tags = getTagsString($("#" + type + "_input_values").val());
            var mark = $("#ques_marks_" + sqid).val();
            var label = $("#ques_label_" + sqid).val();
            var array = {
                type: type,
                sqid: sqid,
                tags: tags,
                mark: mark,
                label: label
            };
            array_to_send.push(array);
        }
    }
    if (delete_sqid !== null) {
        if (delete_sqid === "add") {
            var array = {
                type: "add_question",
                wid: wid
            };
        } else {
            var array = {
                type: "delete_question",
                sqid: delete_sqid
            };
        }
        array_to_send.push(array);
    }
    var infoArray = {
        type: "UPDATEWORKSHEET",
        array: array_to_send,
        req_id: req_id,
        userid: $('#userid').val(),
        userval: $('#userval').val()
    };
    $.ajax({
        type: "POST",
        data: infoArray,
        url: "/requests/worksheet.php",
        dataType: "json",
        success: function(json){
            saveWorksheetSuccess(json);
        },
        error: function(response){
            console.log("Request failed with status code: " + response.status + " - " + response.statusText);
        }
    });
}

function saveWorksheetSuccess(json) {
    if (json["success"]) {
        var req_id = json["result"]["req_id"];
        clearLock("save_worksheet_request_lock", req_id);
        var response = json["result"]["results"];
        var save_requests = JSON.parse(sessionStorage.getItem("save_requests"));
        var reload_page = false;
        for (var i = 0; i < response.length; i++) {
            if(!response[i]["success"]) {
                save_requests = addRequestToSave(response[i]["div_id"], save_requests);
            } else if (response[i]["div_id"] === "delete_question" || response[i]["div_id"] === "add_question") {
                reload_page = true;
            }
        }
        sessionStorage.setItem("save_requests", JSON.stringify(save_requests));
        if(save_requests.length === 0) {
            setSaveButton();
        } else {
            setSaveButton("Save");
        }    
        if (reload_page) location.reload();
    } else {
        console.log("Saving worksheet failed: " + json["message"]);
    }
}

function deleteQuestion(sqid) {
    if (confirm("Are you sure you want to delete this question? This process will also save any unsaved changes.")) {
        saveWorksheet(sqid);
    }
}

function addNewQuestion() {
    if (confirm("Adding a new question will also save any unsaved changes. Are you sure you wish to continue?")) {
        saveWorksheet("add");
    }
}

function getTagsString(tags) {
    var types_array = tags.split("-");
    var tags_string = "";
    for (var i = 0; i < types_array.length; i++) {
        var type_string = types_array[i];
        tags_string += type_string.length === 0 ? "" : type_string + ":";
    }
    if (tags_string.substr(tags_string.length - 1) === ":") {
        tags_string = tags_string.substring(0, tags_string.length - 1);
    }
    return tags_string;
}

function generateRequestLock(key, maxDuration) {
    maxDuration = 10000 || maxDuration;
    var time = new Date().getTime() + maxDuration;
    var rand_num = Math.random() * 1000000000 | 0;
    var req_id = time + ":" + rand_num;
    sessionStorage.setItem(key, req_id);
    return rand_num;
}

function checkLock(key) {
    var lock = sessionStorage.getItem(key);
    if (lock === null || lock === "") return false;
    var info = lock.split(":");
    var time = new Date().getTime();
    if (parseInt(info[0]) < time) return false;
    return true;
}

function clearLock(key, req_id) {
    var lock = sessionStorage.getItem(key);
    if (lock !== "") {
        var info = lock.split(":");
        if (info[1] && info[1] === req_id) {
            sessionStorage.setItem(key, "");
        }
    }
}