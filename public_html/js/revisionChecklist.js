$(document).ready(function(){
    getSpecificationPoints(1);
});

function setContentHeight() {
    var height = $("#checklist_div").height();
    $("#main_content").height(height + 250);
}

function getSpecificationPoints(id) {
    var infoArray = {
        type: "GETALLSPECPOINTS",
        course_id: id,
        userid: $('#userid').val(),
        userval: $('#userval').val()
    };
    $.ajax({
        type: "POST",
        data: infoArray,
        url: "requests/revisionChecklist.php",
        dataType: "json",
        success: specificationPointsSuccess,
        error: function(response){
            console.log("There was an error getting the specification points.");
            console.log(response);
        }
    });
}

function specificationPointsSuccess(json) {
    if (json["success"]) {
        var spec_points = json["result"]["spec_points"];
        sessionStorage.setItem("spec_points", JSON.stringify(spec_points));
        $("#checklist_div").html("");
        for (var i = 0; i < spec_points.length; i++) {
            $("#checklist_div").append(writeChecklistItem(spec_points[i]));
            setChecklistScore(spec_points[i]["ID"], spec_points[i]["Score"]);
        }
        setContentHeight();
    } else {
        console.log("There was an error getting the specification points.");
        console.log(json["message"]);
    }  
}

function writeChecklistItem(spec_point) {
    var id = spec_point["ID"];
    var title = spec_point["Title"];
    var subtitle = spec_point["Subtitle"];
    var description = spec_point["Description"];
    var links = spec_point["Links"];
    
    var final_text = "<div id='checklist_item_" + id + "' class='checklist_item'>";
    final_text += "<div class='checklist_item_title' onclick='clickTitle(" + id + ")'>";
    final_text += "<h1>" + title + " - " + subtitle + "</h1></div>";
    final_text += "<div id='checklist_item_buttons_" + id + "'class='checklist_item_buttons'>";
    final_text += "<div id='checklist_button_" + id + "_5' class='checklist_button five' onclick='clickChecklistButton(" + id + ",5)'><p>5</p></div>";
    final_text += "<div id='checklist_button_" + id + "_4' class='checklist_button four' onclick='clickChecklistButton(" + id + ",4)'><p>4</p></div>";
    final_text += "<div id='checklist_button_" + id + "_3' class='checklist_button three' onclick='clickChecklistButton(" + id + ",3)'><p>3</p></div>";
    final_text += "<div id='checklist_button_" + id + "_2' class='checklist_button two' onclick='clickChecklistButton(" + id + ",2)'><p>2</p></div>";
    final_text += "<div id='checklist_button_" + id + "_1' class='checklist_button one' onclick='clickChecklistButton(" + id + ",1)'><p>1</p></div>";
    final_text += "<div id='checklist_button_" + id + "_single' class='checklist_button single'></div></div></div>";
    final_text += "<div id='checklist_item_detail_" + id + "' class='checklist_item_detail'>";
    final_text += "<div class='checklist_item_description'>";
    final_text += "<p>" + description + "</p></div>";
    final_text += "<div class='checklist_item_links'>";
    for (var i = 0; i < links.length; i++) {
        var title = links[i]["Title"];
        var link_id = links[i]["ID"];
        final_text += "<div class='checklist_link' onclick='followLink(" + id + ", " + link_id + ")'>" + title + "</div>";
    }
    final_text += "</div></div>";
    return final_text;
}

function setChecklistScore(spec_id, score) {
    for (var i = 1; i < 6; i++) {
        $("#checklist_button_" + spec_id + "_" + i).removeClass("selected");
    }
    singleButtonRemoveAll(spec_id);
    $("#checklist_button_" + spec_id + "_single").html("");
    if (score !== "") {
        $("#checklist_button_" + spec_id + "_" + score).addClass("selected");
        $("#checklist_button_" + spec_id + "_single").addClass(getChecklistClass(score));
        $("#checklist_button_" + spec_id + "_single").html("<p>" + score + "</p>");
    }
}

function singleButtonRemoveAll(spec_id) {
    $("#checklist_button_" + spec_id + "_single").removeClass("one");
    $("#checklist_button_" + spec_id + "_single").removeClass("two");
    $("#checklist_button_" + spec_id + "_single").removeClass("three");
    $("#checklist_button_" + spec_id + "_single").removeClass("four");
    $("#checklist_button_" + spec_id + "_single").removeClass("five");
}

function getChecklistClass(score) {
    if (score === "") return "";
    switch(parseInt(score)) {
        case 1:
            return "one";
        case 2:
            return "two";
        case 3:
            return "three";
        case 4:
            return "four";
        case 5:
            return "five";
        default:
            return "";
    }
}

function clickChecklistButton(spec_id, score) {
    // Update html
    setChecklistScore(spec_id, score);
    updateStoredInfo(spec_id, score);
    // Send request to update score
    updateScore(spec_id, score);
}

function updateScore(spec_id, score) {
    var infoArray = {
        type: "UPDATESCORE",
        checklist_id: spec_id,
        score: score,
        userid: $('#userid').val(),
        userval: $('#userval').val()
    };
    $.ajax({
        type: "POST",
        data: infoArray,
        url: "requests/revisionChecklist.php",
        dataType: "json",
        success: function(json) {
            console.log(json);
        },
        error: function(response){
            console.log("There was an error getting the specification points.");
            console.log(response);
        }
    });
}

function updateStoredInfo(spec_id, score) {
    var spec_points = JSON.parse(sessionStorage.getItem("spec_points"));
    for (var i = 0; i < spec_points.length; i++) {
        if (parseInt(spec_points[i]["ID"]) === parseInt(spec_id)) {
            spec_points[i]["Score"] = score;
            sessionStorage.setItem("spec_points", JSON.stringify(spec_points));
            return;
        }
    }
    return;
}

function followLink(spec_id, link_id) {
    var spec_points = JSON.parse(sessionStorage.getItem("spec_points"));
    var link = "";
    for (var i = 0; i < spec_points.length; i++) {
        if (parseInt(spec_points[i]["ID"]) === parseInt(spec_id)) {
            var links = spec_points[i]["Links"];
            for (var j = 0; j < links.length; j++) {
                if (parseInt(links[j]["ID"]) === parseInt(link_id)) {
                    link = links[j]["Link"];
                    break;
                }
            }
            break;
        }
    }
    if (link !== "") window.open(link);
}

function setHeights() {
    var checklist_items = document.getElementsByClassName("checklist_item");
    for (var i = 0; i < checklist_items.length; i++) {
        var item = checklist_items[i];
        var title = item.children[0];
        console.log(title.offsetHeight);
    }
}

function clickTitle(id) {
    var show = !checkIfDetailsDisplayed(id);
    hideAllDetails();
    if (show){
        showDetails(id);
    }
    setContentHeight();
}

function hideAllDetails() {
    var all_divs = document.getElementsByClassName("checklist_item_detail");
    for (var i = 0; i < all_divs.length; i++) {
        var div = all_divs[i];
        $("#" + div.id).removeClass("display");
    }
}

function showDetails(id) {
    $("#checklist_item_detail_" + id).addClass("display");
}

function checkIfDetailsDisplayed(id) {
    var div = document.getElementById("checklist_item_detail_" + id);
    var classes = div.className;
    return classes.indexOf("display") !== -1;
}