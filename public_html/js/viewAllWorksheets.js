var user;
var folders = [];
var root = "#";
var clicks = 0;
var double_clicked = 0;
var update_locked = false;
var failed_updates = 0;
var selected_worksheet;
var teacher_sets = [];
var add_results = false;
var add_results_id = 0;
var loaded = {"sets": false, "worksheets": false}

$(document).ready(function(){
    user = JSON.parse(localStorage.getItem("sbk_usr"));
    window.addEventListener("valid_user", function(){init_page();});
    validateAccessToken(user, ["SUPER_USER", "STAFF"]);
    MicroModal.init();
});

function init_page() {
    writeNavbar(user);
    getSets();
    setUpKeyStrokeListeners();
    getWorksheets("0");
    $("#search_bar_text_input").keyup(function(event){
        searchWorksheets();
    });
    add_results_id = getParameterByName("res");
    if (add_results_id && add_results_id !== null && add_results_id !== "") add_results = true;
    //writeFavouritesBar(favourites, null);
    selectTabOption("search");
}

function setUpKeyStrokeListeners() {
    var input_modal_input = document.getElementById("input_modal_input");
    input_modal_input.addEventListener("keyup", function(event) {
        if (event.keyCode === 13) {
            event.preventDefault();
            $("#input_modal_button").click();
        }
    });
}

function getSets() {
    var infoArray = {
        type: "GETSETSFORSTAFF",
        staff: user["userId"],
        token: user["token"]
    };
    $.ajax({
        type: "POST",
        data: infoArray,
        url: "/requests/manageSets.php",
        dataType: "json",
        success: function(json){
            getSetsSuccess(json);
        }
    });
}

function getSetsSuccess(json) {
    if (json["success"]) {
        teacher_sets = json["response"]["sets"];
        loaded["sets"] = true;
        checkFullyLoaded();
    } else {
        console.log(json["message"]);
    }
}

function getWorksheets(restore) {
    var type = "ALLWORKSHEETS";
    if (restore === "1") {
        type = "DELETEDWORKSHEETS";
        //$("#restore_link").html("<a href='/viewAllWorksheets.php'>View Worksheets</a>");
    }
    var infoArray = {
        type: type,
        orderby: "WV.`Date Added`",
        desc: "TRUE",
        token: user["token"]
    };
    $.ajax({
        type: "POST",
        data: infoArray,
        url: "/requests/getWorksheets.php",
        dataType: "json",
        success: function(json){
            getWorksheetsSuccess(json);
        },
        error: function() {
            console.log("There was an error sending the worksheets request.");
        }
    });
}

function getWorksheetsSuccess(json) {
    if (json["success"]) {
        setUpFolders(json["worksheets"]);
        setUpFilePathBar("all_worksheets_top_bar");
        setUpJSTree();
        loaded["worksheets"] = true;
        checkFullyLoaded();
    } else {
        console.log("Error getting worksheet details.");
        console.log(json);
    }

}

function checkFullyLoaded() {
    for (var key in loaded) {
        if (!loaded[key]) return;
    }
    if (add_results) getWorksheetDetails(add_results_id);
    writeRecentWorksheets();
}

function getWorksheetDetails(id) {
    var infoArray = {
        type: "WORKSHEETSUMMARY",
        wid: id,
        token: user["token"],
        userid: user["userId"]
    };
    $.ajax({
        type: "POST",
        data: infoArray,
        url: "/requests/getWorksheet.php",
        dataType: "json",
        success: function(json){
            getWorksheetDetailsSuccess(json);
        },
        error: function() {
            console.log("There was an error sending the worksheets request.");
        }
    });
}

function selectTabOption(option_id) {
    $('.nav-tabs a[href="#' + option_id + '"]').tab('show');
}

function getWorksheetDetailsSuccess(json) {
    if (json["success"]) {
        selected_worksheet = json["worksheet"];
        writeSelectedWorksheet(selected_worksheet);
        selectTabOption("selected_worksheet");
        $('html, body').animate({
            scrollTop: $("#selected_worksheet").offset().top - 100
        }, 500);
        if (add_results) addNewResults();
    } else {
        $("#selected_worksheet").html("<div class='no_worksheet'>No worksheet selected.</div>");
    }
}

function writeSelectedWorksheet(worksheet) {
    var html_text = "<div class='selected_worksheet_pane'>";
    html_text += "<div class='selected_worksheet_title'>" + worksheet["WName"] + "</div>";
    html_text += "<div id='selected_worksheet_filetree'></div>";
    html_text += "<div class='selected_worksheet_subpane'>";
    html_text += writeWorksheetInfoOption("Author", worksheet["Initials"]);
    var date_added = moment(worksheet["Date Added"]);
    html_text += writeWorksheetInfoOption("Date", date_added.format("DD/MM/YY"));
    html_text += writeWorksheetInfoOption("Questions", worksheet["questions"]);
    html_text += writeWorksheetInfoOption("Marks", worksheet["marks"]);
    html_text += writeWorksheetInfoOption("Students", worksheet["students"]);
    html_text += "</div><div class='selected_worksheet_subpane last'>";
    html_text += "<div class='selected_worksheet_button add' onclick='addNewResults()'>Add New Results</div>";
    html_text += "<a class='selected_worksheet_button' href='/editWorksheet.php?id=" + worksheet["Version ID"] + "'>Edit</a>";
    html_text += "<div class='selected_worksheet_button' onclick='clickRename(\"selected\")'>Rename</div>";
    html_text += "<div class='selected_worksheet_button last remove' onclick='clickDelete(\"selected\")'>Delete</div>";
    html_text += "</div></div><div class='selected_worksheet_pane last'>";
    html_text += "<div class='selected_worksheet_title results'>Existing Results</div>";
    html_text += "<div class='selected_worksheet_results'>";
    var sets = worksheet["sets"];
    for (var i = 0; i < sets.length; i++) {
        var date_due = moment(sets[i]["Date Due"], "DD/MM/YYYY");
        var text = sets[i]["Name"] + " - " + sets[i]["Initials"] + " - " + date_due.format("DD/MM/YY");
        var gwid = sets[i]["Group Worksheet ID"];
        html_text += "<a class='selected_worksheet_result' onclick='goToExistingResult(" + gwid + ")' href='/editSetResults.php?gwid=" + gwid + "'>" + text + "</a>";
    }
    html_text += "</div></div>";
    $("#selected_worksheet").html(html_text);
    setUpFilePathBar("selected_worksheet_filetree", worksheet["ParentID"]);
}

function goToExistingResult(gwid) {
    window.location.href = "editSetResults.php?gwid=" + gwid;
}

function addNewResults() {
    $("#new_results_text").html("To enter results for the worksheet '" + selected_worksheet["WName"] + "' select either an existing set of results to edit or select a set to enter new results for.");
    var existing_html = "";
    var worksheet_id = selected_worksheet["Version ID"];
    var sets = selected_worksheet["sets"];
    for (var i = 0; i < sets.length; i++) {
        var date_due = moment(sets[i]["Date Due"], "DD/MM/YYYY");
        var text = sets[i]["Name"] + " - " + sets[i]["Initials"] + " - " + date_due.format("DD/MM/YY");
        var gwid = sets[i]["Group Worksheet ID"];
        existing_html += "<a href='/editSetResults.php?gwid=" + gwid + "' class='new_results_section_result' onclick='goToExistingResult(" + gwid + ")'>" + text + "</a>";
    }
    $("#existing_results_section").html(existing_html);
    var sets_html = "";
    for (var i = 0; i < teacher_sets.length; i++) {
        sets_html += "<div class='new_results_section_result' onclick='addNewGroupWorksheet(" + teacher_sets[i]["Group ID"] + "," + worksheet_id + ")'>" + teacher_sets[i]["Name"] + "</div>";
    }
    $("#new_sets_section").html(sets_html);
    MicroModal.show("new_results_modal");
}

function addNewGroupWorksheet(group_id, vid) {
    var infoArray = {
        type: "FORCENEW",
        set: group_id,
        worksheet: vid,
        userid: user["userId"],
        token: user["token"]
    };
    $.ajax({
        type: "POST",
        data: infoArray,
        url: "/requests/setGroupWorksheet.php",
        dataType: "json",
        success: function(json){
            if (json["success"]) {
                if (json["result"]["created"]) {
                    window.location.href = "editSetResults.php?gwid=" + json["result"]["gwid"];
                }
            } else {
                console.log(json);
            }
        },
        error: function() {
            console.log("There was an error getting the groups.");
        }
    });
}

function writeWorksheetInfoOption(label, value) {
    var html_text = "<div class='selected_worksheet_info'>";
    html_text += "<div class='selected_worksheet_info_label'>" + label + "</div>";
    html_text += "<div class='selected_worksheet_info_value'>" + value + "</div>";
    html_text += "</div>";
    return html_text;
}

function setUpFolders(worksheets) {
    folders = [];
    for (var i = 0; i < worksheets.length; i++) {
        var worksheet = worksheets[i];
        folders.push({
           id: worksheet["ID"],
           parent: worksheet["ParentID"],
           value: worksheet["WName"],
           date: worksheet["Date"],
           customDate: worksheet["CustomDate"],
           type: worksheet["Type"],
           changed: false,
           changes_sent: false,
           state: {opened: false, disabled: false, selected: false}
        });
    }
    orderFoldersAndUpdateText();
}

function orderFoldersAndUpdateText() {
    var temp_folders = [];
    var temp_files = [];
    for (var j = 0; j < folders.length; j++) {
        if (folders[j].type === "Folder") temp_folders.push(folders[j]);
        if (folders[j].type === "File") temp_files.push(folders[j]);
    }
    temp_folders.sort(compareValues("value"));
    temp_files.sort(compareValues("customDate", "desc"));
    folders = temp_folders.concat(temp_files);
    for (var j = 0; j < folders.length; j++) {
        var text = "<span class='left_span'>" + folders[j]["value"] + "</span>";
        if (folders[j]["type"] === "File") text += "<span class='right_span'>" + folders[j]["date"] + "</span>";
        folders[j]["text"] = text;
    }
}

// function for dynamic sorting
function compareValues(key, order='asc') {
      return function(a, b) {
            if(!a.hasOwnProperty(key) || !b.hasOwnProperty(key)) {
                // property doesn't exist on either object
                return 0;
            }

            const varA = (typeof a[key] === 'string') ? a[key].toUpperCase() : a[key];
            const varB = (typeof b[key] === 'string') ? b[key].toUpperCase() : b[key];

            let comparison = 0;
            if (varA > varB) {
                comparison = 1;
            } else if (varA < varB) {
                comparison = -1;
            }
            return (
                (order == 'desc') ? (comparison * -1) : comparison
            );
      };
}

function setUpJSTree() {
    var data = setUpDataForRoot(folders, root);
    $('#worksheets_jstree').jstree({
        core: {
            data : data,
            themes: {dots: false},
            dblclick_toggle : false,
            check_callback: checkOperation,
            multiple: false
        },
        types: {
            "default": {
                icon: "glyphicon glyphicon-folder-close"
            },
            "Folder": {
                icon: "glyphicon glyphicon-folder-close"
            },
            "File": {
                icon: "glyphicon glyphicon-file"
            }
        },
        plugins: ["types", "dnd"]
    }).on('open_node.jstree', function (e, data) {
        data.instance.set_icon(data.node, "glyphicon glyphicon-folder-open");
    }).on('close_node.jstree', function (e, data) {
        data.instance.set_icon(data.node, "glyphicon glyphicon-folder-close");
    }).on('click.jstree', '.jstree-anchor', function (e) {
        clicks++;
        if (clicks <= 1) {
            setTimeout(function(){
                if (!double_clicked) {
                    $("#worksheets_jstree").jstree(true).toggle_node(e.target);
                    var node = $("#worksheets_jstree").jstree().get_node(e.target);
                    if (node.type === "File") getWorksheetDetails(node.id);
                }
                double_clicked = false;
                clicks = 0;
            }, 300);
        }
    }).on('dblclick.jstree', '.jstree-anchor', function (evt) {
        double_clicked = true;
        var node = $("#worksheets_jstree").jstree().get_node(evt.target);
        if(node.type !== "File") {
            refreshTreeForRoot(node.id);
        } else {
            goToEditWorksheet(node.id);
        }
    }).on("changed.jstree", function (e, data) {
        //console.log(data.selected);
    }).bind("move_node.jstree", function(e, data) {
        updateMovedFiles($("#worksheets_jstree").jstree(true)._model.data);
    });
}

function updateMovedFiles(new_data) {
    for (var key in new_data) {
        var new_folder = new_data[key];
        for (var j = 0; j < folders.length; j++) {
            if (new_folder["id"] === folders[j]["id"]) {
                if (checkIfChangedFolder(new_folder, folders[j])) {
                    folders[j]["parent"] = getNewParent(new_folder);
                    folders[j]["changed"] = true;
                }
                break;
            }
        }
    }
    refreshTreeForRoot();
    requestFolderUpdate();
}

function goToEditWorksheet(id) {
    window.location.href = "editWorksheet.php?id=" + id;
}

function getNewParent(new_folder) {
    var new_parent = new_folder["parent"];
    if (new_parent === "#") return root;
    for (var i = 0; i < folders.length; i++) {
        if (folders[i]["id"] === new_parent) {
            if (folders[i]["type"] === "File") {
                return folders[i]["parent"];
            } else {
                return folders[i]["id"];
            }
        }
    }
    return root;
}

function requestFolderUpdate() {
    if (update_locked) return;
    update_locked = true;
    var changes = [];
    for (var i = 0; i < folders.length; i++) {
        if (folders[i]["changed"]) {
            changes.push(folders[i]);
            folders[i]["changed"] = false;
            folders[i]["changes_sent"] = true;
        }
    }
    if (changes.length === 0) {
        update_locked = false;
        return;
    }
    var infoArray = {
        type: "UPDATEFILETREE",
        array: changes,
        token: user["token"]
    };
    console.log(infoArray);
    $.ajax({
        type: "POST",
        data: infoArray,
        url: "/requests/worksheet.php",
        dataType: "json",
        success: function(json){
            console.log(json);
            if (json["success"]) {
                updateChangesArray(json["result"], null);
                failed_updates = 0;
                update_locked = false;
                requestFolderUpdate();
            } else {
                if (json["result"] === undefined) {
                    $("#message_modal-content").html("<p>There has been an error updating the folders. Please refresh and try again.<br>If the problem persists then contact <a mailto='contact.smarkbook@gmail.com'>support</a>.</p>");
                    MicroModal.show("message_modal");
                    console.log(json);
                    return;
                }
                updateChangesArray(json["result"]["updated"], json["result"]["errors"]);
                failed_updates++;
                if (failed_updates < 5) {
                    if (failed_updates === 1) {
                        console.log("Update filetree request failed.");
                        console.log(json);
                    }
                    update_locked = false;
                    requestFolderUpdate();
                } else {
                    $("#message_modal-content").html("<p>There has been an error updating the folders. Please refresh and try again.<br>If the problem persists then contact <a mailto='contact.smarkbook@gmail.com'>support</a>.</p>");
                    MicroModal.show("message_modal");
                    console.log("Update filetree request failed too many times and has been disabled.");
                    console.log(json);
                }
            }
        },
        error: function(response){
            console.log("Request failed with status code: " + response.status + " - " + response.statusText);
            updateChangesArrayOnError();
            failed_updates++;
            if (failed_updates < 5) {
                update_locked = false;
                requestFolderUpdate();
            } else {
                $("#message_modal-content").html("<p>There has been an error updating the folders. Please refresh and try again.<br>If the problem persists then contact <a mailto='contact.smarkbook@gmail.com'>support</a>.</p>");
                MicroModal.show("message_modal");
                console.log("Update filetree request failed too many times and has been disabled.");
            }
        }
    });
}

function updateChangesArray(updated, errors) {
    for (var i = 0; i < folders.length; i++) {
        if (folders[i]["changes_sent"]) {
            if (updated !== null) {
                for (var j = 0; j < updated.length; j++) {
                    var updated_id = parseInt(updated[j]);
                    var folder_id = parseInt(folders[i]["id"]);
                    if (updated_id === folder_id) {
                        folders[i]["changes_sent"] = false;
                    }
                }
            }
            if (errors !== null) {
                for (var j = 0; j < errors.length; j++) {
                    var error_id = parseInt(errors[j][0]);
                    var folder_id = parseInt(folders[i]["id"]);
                    if (error_id === folder_id) {
                        folders[i]["changes_sent"] = false;
                        folders[i]["changed"] = true;
                    }
                }
            }
        }
    }
}

function updateChangesArrayOnError() {
    for (var i = 0; i < folders.length; i++) {
        if (folders[i]["changes_sent"]) {
            folders[i]["changes_sent"] = false;
            folders[i]["changed"] = true;
        }
    }
}

function checkIfChangedFolder(new_folder, original_folder) {
    if (new_folder["original"]["value"] !== original_folder["value"]) return true;
    if (new_folder["parent"] !== original_folder["parent"]) return true;
    return false;
}

function checkOperation(operation, node, parent, position, more) {
    if (operation === "move_node") {
        if (more && more.dnd && more.pos !== "i") {
            return false;
        } else {
            return true;
        }
    } else if (operation === "copy_node") {
        return false;
    }
    return true;
}

function refreshTreeForRoot(new_root) {
    root = isNaN(parseInt(new_root)) ? "#" : parseInt(new_root);
    setUpFilePathBar("all_worksheets_top_bar");
    orderFoldersAndUpdateText();
    $('#worksheets_jstree').jstree(true).settings.core.data = setUpDataForRoot(folders, root);
    $("#worksheets_jstree").jstree("deselect_all", true);
    $('#worksheets_jstree').jstree(true).refresh();
}

function setUpDataForRoot(folders, root) {
    if (root === "#") return folders;
    var data = [];
    var roots = [root];
    while(true) {
        if(roots.length === 0) break;
        var new_roots = [];
        for (var j = 0; j < roots.length; j++) {
            for (var i = 0; i < folders.length; i++) {
                var folder = folders[i];
                var parent_id = parseInt(folder["parent"]);
                if (parent_id === roots[j]) {
                    data.push(JSON.parse(JSON.stringify(folder)));
                    if (parent_id === root) data[data.length - 1]["parent"] = "#";
                    new_roots.push(parseInt(folder["id"]));
                }
            }
        }
        roots = new_roots;
    }
    return data;
}

function setUpFilePathBar(id, local_root = root) {
    $("#" + id).html(getFullFilePath(folders, local_root));
}

function getFullFilePath(folders, root) {
    var html_text = "";
    var urls = [];
    var current_root = isNaN(parseInt(root)) ? root : parseInt(root);
    var flag = true;
    while (flag) {
        flag = false;
        for (var i = 0; i < folders.length; i++) {
            if (parseInt(folders[i]["id"]) === current_root) {
                urls.push([folders[i]["id"], folders[i]["value"]]);
                current_root = parseInt(folders[i]["parent"]);
                flag = true;
                break;
            }
        }
    }
    urls.push(["#", "Home"]);
    for (var i = urls.length - 1; i >= 0; i--) {
        if (i < urls.length - 1) html_text += "<span class='folder_display_span'>></span>";
        html_text += "<div class='folder_display_div' onclick='return clickFolder(\"" + urls[i][0] + "\")'>" + urls[i][1] + "</div>";
        //html_text += "<a href='" + writeUrl(urls[i][0]) + "' onclick='return clickFolder(\"" + urls[i][0] + "\")';>" + urls[i][1] + "</a>";
    }
    return html_text;
}

function clickFolder(root) {
    var new_root = isNaN(parseInt(root)) ? "#" : parseInt(root);
    refreshTreeForRoot(new_root);
    return false;
}

function writeUrl(root) {
    return "/viewAllWorksheets.php?root=" + root;
}

function clickNewFolder() {
    setUpInputModal("New Folder",
        {"Value": "", "Placeholder": "Folder Name"},
        false,
        false,
        false,
        "Create");
    $("#input_modal_button").off('click').on('click',createNewFolder);
    MicroModal.show("input_modal");
    $("#input_modal_input").select();
}

function clickNewFile() {
    setUpInputModal("New Worksheet",
        {"Value": "", "Placeholder": "Worksheet Title"},
        {"Value": "1", "Placeholder": "No. Of Questions", "Label": "Questions"},
        false,
        false,
        "Create");
    $("#input_modal_button").off('click').on('click',createNewFile);
    MicroModal.show("input_modal");
    $("#input_modal_input").select();
}

function setUpInputModal(title = "Title", input_1 = false, input_2 = false, text = false, id=false, button = false) {
    $("#input_modal-title").html(title);
    if (input_1) {
        $("#input_modal_input").val(input_1["Value"] ? input_1["Value"] : "");
        $("#input_modal_input").attr("placeholder", input_1["Placeholder"] ? input_1["Placeholder"] : "");
        $("#input_modal_input").removeClass("hidden");
    } else {
        $("#input_modal_input").addClass("hidden");
    }
    if (input_2) {
        $("#input_modal_input_2").val(input_2["Value"] ? input_2["Value"] : "");
        $("#input_modal_input_2").attr("placeholder", input_2["Placeholder"] ? input_2["Placeholder"] : "");
        $("#input_modal_input_2_label").html(input_2["Label"] ? input_2["Label"] : "");
        $("#input_modal_input_2").removeClass("hidden");
        $("#input_modal_input_2_label").removeClass("hidden");
    } else {
        $("#input_modal_input_2").addClass("hidden");
        $("#input_modal_input_2_label").addClass("hidden");
    }
    if (text) {
        $("#input_modal_text").html("<p>" + text + "</p>");
        $("#input_modal_text").removeClass("hidden");
        $("#input_modal_text").removeClass("error");
    } else {
        $("#input_modal_text").html("<p></p>");
        $("#input_modal_text").addClass("hidden");
        $("#input_modal_text").removeClass("error");
    }
    $("#input_modal_id").val(id ? id : 0);
    $("#input_modal_button").html(button ? button : "Create");
}


function clickRename(type) {
    var current_name;
    var current_id;
    if (type === "selected") {
        current_name = selected_worksheet["WName"];
        current_id = selected_worksheet["Version ID"];
    } else {
        var selected = $("#worksheets_jstree").jstree("get_selected", true);
        if (selected.length === 0) return;
        current_id = selected[0]["original"]["id"];
        current_name = selected[0]["original"]["value"];
    }
    $("#input_modal-title").html("Rename");
    $("#input_modal_input").val(current_name);
    $("#input_modal_button").html("Confirm");
    $("#input_modal_id").val(current_id);
    $("#input_modal_button").off('click').on('click',renameFolder);
    MicroModal.show("input_modal");
}

function clickDelete(type) {
    var current_name;
    var current_id;
    var selected_type;
    var message = "";
    if (type === "selected") {
        current_name = selected_worksheet["WName"];
        current_id = selected_worksheet["Version ID"];
        selected_type = "File";
        message = "<p>Are you sure your wish to delete the file '<b>" + current_name + "</b>'?";
    } else {
        var selected = $("#worksheets_jstree").jstree("get_selected", true);
        if (selected.length === 0) return;
        selected_type = selected[0]["original"]["type"];
        current_id = selected[0]["original"]["id"];
        current_name = selected[0]["original"]["value"];
        if (selected_type === "File") {
            message = "<p>Are you sure your wish to delete the file '<b>" + current_name + "</b>'?";
        } else {
            var children = selected[0]["children_d"];
            var children_types = getChildrenTypes(children);
            var files_count = children_types["files"];
            var folders_count = children_types["folders"];
            message = "<p>Are you sure your wish to delete the folder '<b>" + current_name + "</b>'? <br>";
            message += "The folder contains: <br> &emsp;- " + writeFilesCount(folders_count, "subfolder");
            message += "<br> &emsp;- " + writeFilesCount(files_count, "subfile");
        }
    }
    message += "<br>This process is irreversible and all associated results will also be lost.</p>";
    $("#message_modal_title").html("Delete");
    $("#message_modal_content").html(message);
    $("#message_modal_button").html("Delete");
    $("#message_modal_button").off('click').on('click',function(){
        deleteFolder(current_id);
    });
    MicroModal.show("message_modal");
}

function writeFilesCount(count, text) {
    var return_text = count + " ";
    if (count === 0 || count > 1) {
        return_text += text + "s";
    } else {
        return_text += text;
    }
    return return_text;
}

function getChildrenTypes(children) {
    var folders_count = 0;
    var files_count = 0;
    for (var i = 0; i < folders.length; i++) {
        for (var j = 0; j < children.length; j++) {
            if (folders[i]["id"] === children[j]) {
                if (folders[i]["type"] === "File") files_count++;
                if (folders[i]["type"] === "Folder") folders_count++;
            }
        }
    }
    return {
        folders: folders_count,
        files: files_count
    };
}

function renameFolder() {
    $("#input_modal_text").addClass("hidden");
    $("#input_modal_text").removeClass("error");
    var folder_id = parseInt($("#input_modal_id").val());
    if (isNaN(folder_id)) {
        $("#input_modal_text").html("<p>There has been an error, please refresh and try again.</p>");
        $("#input_modal_text").removeClass("hidden");
        $("#input_modal_text").addClass("error");
        return;
    }
    var name = $("#input_modal_input").val();
    if (name === "" || name === null || name === undefined) {
        $("#input_modal_text").html("<p>Please input a valid name.</p>");
        $("#input_modal_text").removeClass("hidden");
        $("#input_modal_text").addClass("error");
        return;
    }

    var node = $("#worksheets_jstree").jstree('get_selected');
    $("#worksheets_jstree").jstree(true).rename_node(node, name);

    for (var j = 0; j < folders.length; j++) {
        if (folder_id === parseInt(folders[j]["id"])) {
            folders[j]["value"] = name;
            folders[j]["changed"] = true;
            break;
        }
    }
    orderFoldersAndUpdateText();
    if (selected_worksheet && selected_worksheet["Version ID"] && parseInt(selected_worksheet["Version ID"]) === parseInt(folder_id)) {
        selected_worksheet["WName"] = name;
        writeSelectedWorksheet(selected_worksheet);
    }
    requestFolderUpdate();
    MicroModal.close("input_modal");
}

function deleteFolder(id) {
    var type = "DELETEFOLDER";
    var infoArray = {
        type: type,
        vid: id,
        token: user["token"]
    };
    $("#message_modal_button").off('click');
    $.ajax({
        type: "POST",
        data: infoArray,
        url: "/requests/worksheetFunctions.php",
        dataType: "json",
        success: function(json){
            if (json["success"]) {
                location.reload();
            } else {
                console.log("Delete folder request has failed.");
                console.log(json);
                $("#message_modal_title").html("Error");
                $("#message_modal_content").html("<p>There has been an error deleting one or more of the files requested. Please refresh and try again.</p>");
                $("#message_modal_button").html("OK");
                $("#message_modal_button").off('click').on('click',function (){
                    location.reload();
                });
                MicroModal.show("message_modal");
            }
        },
        error: function(response){
            console.log("Delete folder request has failed.");
            console.log("Request failed with status code: " + response.status + " - " + response.statusText);
            $("#message_modal_title").html("Error");
            $("#message_modal_content").html("<p>There has been an error deleting one or more of the files requested. Please refresh and try again.</p>");
            $("#message_modal_button").html("OK");
            $("#message_modal_button").off('click').on('click',function (){
                location.reload();
            });
            MicroModal.show("message_modal");
        }
    });
}

function createNewFile() {
    var name = $("#input_modal_input").val();
    if (name === "" || name === null || name === undefined) name = "New Worksheet";
    var selected = $("#worksheets_jstree").jstree("get_selected", true);
    var parent_id = root;
    if (selected.length > 0) {
        if (selected[0]["original"]["type"] === "File") {
            parent_id = selected[0]["parent"] !== "#" ? selected[0]["parent"] : root;
        } else {
            parent_id = selected[0]["id"];
        }
    }
    var questions = isNaN(parseInt($("#input_modal_input_2").val())) ? 1 : parseInt($("#input_modal_input_2").val());
    $("#input_modal_button").off('click');
    var array_to_send = {
        name: name,
        author: user["userId"],
        questions: questions,
        parent: parent_id
    };
    var infoArray = {
        type: "NEWWORKSHEET",
        array: array_to_send,
        token: user["token"]
    };
    $.ajax({
        type: "POST",
        data: infoArray,
        url: "/requests/worksheet.php",
        dataType: "json",
        success: function(json){
            createNewFileSuccess(json);
        },
        error: function(response){
            $("#input_modal_button").off('click').on('click',createNewFile);
            $("#input_modal_text").html("<p>Error creating worksheet, please try again.</p>");
            $("#input_modal_text").removeClass("hidden");
            $("#input_modal_text").addClass("error");
            console.log("Create new file request failed with status code: " + response.status + " - " + response.statusText);
        }
    });
}

function createNewFileSuccess(json) {
    if (json["success"]) {
        var new_vid = json["result"];
        window.location.href = "editWorksheet.php?id=" + new_vid;
    } else {
        $("#input_modal_button").off('click').on('click',createNewFile);
        $("#input_modal_text").html("<p>Error creating worksheet, please try again.</p>");
        $("#input_modal_text").removeClass("hidden");
        $("#input_modal_text").addClass("error");
        console.log("Error creating new file.");
        console.log(json);
    }
}

function createNewFolder() {
    var name = $("#input_modal_input").val();
    if (name === "" || name === null || name === undefined) name = "New Folder";
    var selected = $("#worksheets_jstree").jstree("get_selected", true);
    var parent_id = root;
    if (selected.length > 0) {
        if (selected[0]["original"]["type"] === "File") {
            parent_id = selected[0]["parent"] !== "#" ? selected[0]["parent"] : root;
        } else {
            parent_id = selected[0]["id"];
        }
    }
    createNewFolderRequest(name, parent_id);
}

function newFolderSuccess(new_folder) {
    folders.push({
        id: new_folder["ID"],
        parent: new_folder["ParentID"],
        value: new_folder["WName"],
        customDate: new_folder["CustomDate"],
        date: new_folder["Date"],
        type: new_folder["Type"],
        changed: false,
        state: {opened: false, disabled: false, selected: false}
    });
    orderFoldersAndUpdateText();
    MicroModal.close("input_modal");
    $("#input_modal_input").val("New Folder");
    refreshTreeForRoot(root);
}

function createNewFolderRequest(name, parent_id) {
    var array_to_send = {
        name: name,
        author: user["userId"],
        parent: parent_id
    };
    var infoArray = {
        type: "NEWFOLDER",
        array: array_to_send,
        token: user["token"]
    };
    $("#input_modal_button").off('click');
    $.ajax({
        type: "POST",
        data: infoArray,
        url: "/requests/worksheet.php",
        dataType: "json",
        success: function(json){
            if (json["success"]) {
                newFolderSuccess(json["result"]);
                console.log("New folder success");
                console.log(json["result"]);
            } else {
                console.log("Add new folder request failed.");
                $("#input_modal_button").off('click').on('click',createNewFolder);
                $("#input_modal_text").html("<p>Error creating worksheet, please try again.</p>");
                $("#input_modal_text").removeClass("hidden");
                $("#input_modal_text").addClass("error");
            }
        },
        error: function(response){
            console.log("Request failed with status code: " + response.status + " - " + response.statusText);
            $("#input_modal_button").off('click').on('click',createNewFolder);
            $("#input_modal_text").html("<p>Error creating worksheet, please try again.</p>");
            $("#input_modal_text").removeClass("hidden");
            $("#input_modal_text").addClass("error");
        }
    });
}

function searchWorksheets() {
    var searchTerm = $("#search_bar_text_input").val();
    var infoArray = {
        type: "SEARCH",
        search: searchTerm,
        token: user["token"]
    };
    $.ajax({
        type: "POST",
        data: infoArray,
        url: "/requests/searchWorksheets.php",
        dataType: "json",
        success: function(json){
            searchSuccess(json, searchTerm);
        },
        error: function() {
            console.log("There was an error sending the search worksheets request.");
        }
    });
}

function searchSuccess(json, searchTerm) {
    if(json["success"]) {
        if(!json["noresults"]) {
            writeFilteredWorksheets(json["vids"], "search_results", null);
        } else {
            writeFilteredWorksheets(false, "search_results", null);
        }
    } else {
        console.log("There was an error searching the worksheets.");
        console.log(json["message"]);
    }
}

function clickSearchResult(id) {
    getWorksheetDetails(id);
    $("#worksheets_jstree").jstree("deselect_all", true);
}

function clearSearch() {
    $("#search_bar_text_input").val("");
    searchWorksheets();
}


function writeFilteredWorksheets(results, id, max = null) {
    var html_text = "";
    var count = max === null ? results.length : Math.min(max, results.length);
    if (results) {
        for (var i = 0; i < count; i++) {
            html_text += "<a class='worksheet_result";
            if (i === count - 1) html_text += " last";
            html_text += "' onclick='clickSearchResult(" + results[i]["Version ID"] + ")'>";
            html_text += "<div class='worksheet_result_name'>" + results[i]["Name"] + "</div>";
            html_text += "<div class='worksheet_result_date'>" + results[i]["Date"] + "</div></a>";
        }
        $("#" + id).removeClass("no_results");
    } else {
        html_text += "No search results to display.";
        $("#" + id).addClass("no_results");
    }
    $("#" + id).html(html_text);
}

function writeRecentWorksheets() {
    var max_count = 100;
    var id = "recent_results";
    var html_text = "";
    var results = [];
    for (var i = 0; i < folders.length; i++) {
        if (folders[i]["type"] === "File") {
            results.push({
                "Version ID": folders[i]["id"],
                "Name": folders[i]["value"],
                "Date": folders[i]["date"]
            })
            if (results.length >= max_count) break;
        }
    }
    if (results.length > 0) {
        for (var i = 0; i < results.length; i++) {
            html_text += "<a class='worksheet_result";
            if (i === results.length - 1) html_text += " last";
            html_text += "' onclick='clickSearchResult(" + results[i]["Version ID"] + ")'>";
            html_text += "<div class='worksheet_result_name'>" + results[i]["Name"] + "</div>";
            html_text += "<div class='worksheet_result_date'>" + results[i]["Date"] + "</div></a>";
        }
        $("#" + id).removeClass("no_results");
    } else {
        html_text += "No recent worksheets to display.";
        $("#" + id).addClass("no_results");
    }
    $("#" + id).html(html_text);
}

function writeFavouritesBar(favourites, max) {
    if (favourites.length === 0) {
        $("#all_worksheets_favourites_bar").html(html_text);
        $("#all_worksheets_favourites_bar").addClass("hidden");
        return;
    }
    var html_text = "<div class='folder_display_div favourite_title'>Favourites:</div>";
    var count = max === null ? favourites.length : Math.min(max, favourites.length);
    for (var i = 0; i < count; i++) {
        html_text += "<div class='folder_display_div'><span class='glyphicon glyphicon-folder-close'></span> " + favourites[i]["Name"] + "</div>";
    }
    $("#all_worksheets_favourites_bar").html(html_text);
    $("#all_worksheets_favourites_bar").removeClass("hidden");
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
