var user;
var folders = [];
var root = "#";
var clicks = 0;
var double_clicked = 0;
var update_locked = false;
var failed_updates = 0;

$(document).ready(function(){
    user = JSON.parse(localStorage.getItem("sbk_usr"));
    window.addEventListener("valid_user", function(){init_page();});
    validateAccessToken(user, ["SUPER_USER", "STAFF"]);
    MicroModal.init();
    //MicroModal.show('modal-1');
});

function init_page() {
    writeNavbar(user);
    setUpKeyStrokeListeners();
    getWorksheets("0");
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
    setUpFolders(json["worksheets"]);
    setUpFilePathBar();
    setUpJSTree();
}

function setUpFolders(worksheets) {
    folders = [];
    for (var i = 0; i < worksheets.length; i++) {
        var worksheet = worksheets[i];
        folders.push({
           id: worksheet["ID"],
           parent: worksheet["ParentID"],
           text: worksheet["WName"],
           date: worksheet["Date"],
           type: worksheet["Type"],
           changed: false,
           changes_sent: false,
           state: {opened: false, disabled: false, selected: false}
        });
    }
    orderFolders();
}

function orderFolders() {
    var temp_folders = [];
    var type_order = ["Folder", "File"];
    for (var i = 0; i < type_order.length; i++) {
        for (var j = 0; j < folders.length; j++) {
            if (folders[j].type === type_order[i]) temp_folders.push(folders[j]);
        }
    }
    folders = temp_folders;
}

function setUpJSTree() {
    var data = setUpDataForRoot(folders, root);
    $('#jstree_demo_div').jstree({ 
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
                    $("#jstree_demo_div").jstree(true).toggle_node(e.target);
                }
                double_clicked = false;
                clicks = 0;
            }, 300);
        }
    }).on('dblclick.jstree', '.jstree-anchor', function (evt) {
        double_clicked = true;
        var node = $("#jstree_demo_div").jstree().get_node(evt.target); 
        if(node.type !== "File") refreshTreeForRoot(node.id);
    }).on("changed.jstree", function (e, data) {
        //console.log(data.selected);
    }).bind("move_node.jstree", function(e, data) {
        updateMovedFiles($("#jstree_demo_div").jstree(true)._model.data);
    });
}

function updateMovedFiles(new_data) {
    for (var key in new_data) {
        var new_folder = new_data[key];
        for (var j = 0; j < folders.length; j++) {
            if (new_folder["id"] === folders[j]["id"]) {
                if (checkIfChangedFolder(new_folder, folders[j])) {
                    folders[j]["text"] = new_folder["text"];
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
    $.ajax({
        type: "POST",
        data: infoArray,
        url: "/requests/worksheet.php",
        dataType: "json",
        success: function(json){
            if (json["success"]) {
                updateChangesArray(json["result"], null);
                failed_updates = 0;
                update_locked = false;
                requestFolderUpdate();
            } else {
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
    if (new_folder["text"] !== original_folder["text"]) return true;
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
    setUpFilePathBar();
    orderFolders();
    $('#jstree_demo_div').jstree(true).settings.core.data = setUpDataForRoot(folders, root);
    $("#jstree_demo_div").jstree("deselect_all", true);
    $('#jstree_demo_div').jstree(true).refresh();
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

function setUpFilePathBar() {
    $("#all_worksheets_top_bar").html(getFullFilePath(folders, root));
}

function getFullFilePath(folders, root) {
    var html_text = "";
    var urls = [];
    var current_root = root;
    var flag = true;
    while (flag) {
        flag = false;
        for (var i = 0; i < folders.length; i++) {
            if (parseInt(folders[i]["id"]) === current_root) {
                urls.push([folders[i]["id"], folders[i]["text"]]);
                current_root = parseInt(folders[i]["parent"]);
                flag = true;
                break;
            }
        }
    }
    urls.push(["#", "Home"]);
    for (var i = urls.length - 1; i >= 0; i--) {
        if (i < urls.length - 1) html_text += " > ";
        html_text += "<a href='" + writeUrl(urls[i][0]) + "' onclick='return clickFolder(\"" + urls[i][0] + "\")';>" + urls[i][1] + "</a>";
    }
    return html_text;
}

function clickFolder(root) {
    var new_root = isNaN(parseInt(root)) ? "#" : parseInt(root);
    refreshTreeForRoot(new_root);
    return false;
}

function writeUrl(root) {
    return "/viewAllWorksheets2.php?root=" + root;
}

function clickNewFolder() {
    $("#input_modal-title").html("New Folder");
    $("#input_modal_input").val("New Folder");
    $("#input_modal_button").html("Create");
    $("#input_modal_button").off('click').on('click',createNewFolder);
    MicroModal.show("input_modal");
    $("#input_modal_input").select();
}

function clickRename() {
    var selected = $("#jstree_demo_div").jstree("get_selected", true);
    if (selected.length === 0) return;
    var current_name = selected[0]["original"]["text"];
    var current_id = selected[0]["original"]["id"];
    $("#input_modal-title").html("Rename");
    $("#input_modal_input").val(current_name);
    $("#input_modal_button").html("Confirm");
    $("#input_modal_button").off('click').on('click',renameFolder);
    MicroModal.show("input_modal");
}

function clickDelete() {
    var selected = $("#jstree_demo_div").jstree("get_selected", true);
    if (selected.length === 0) return;
    var current_id = selected[0]["original"]["id"];
    var current_name = selected[0]["original"]["text"];
    var message = "";
    if (selected[0]["original"]["type"] === "File") {
        message = "<p>Are you sure your wish to delete the file '<b>" + current_name + "</b>'?";
    } else {
        var children = selected[0]["children_d"];
        var children_types = getChildrenTypes(children);
        var files_count = children_types["files"];
        var folders_count = children_types["folders"];
        message = "<p>Are you sure your wish to delete the folder '<b>" + current_name + "</b>'? <br>";
        message += "The folder contains: <br> &emsp;- " + writeFilesCount(folders_count, "subfolder");
        message += "<br> &emsp;- " + writeFilesCount(files_count, "file");
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
    
    var selected = $("#jstree_demo_div").jstree("get_selected", true);
    if (selected.length > 0) {
        var folder_id = parseInt(selected[0]["id"]);
    } else {
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
    
    var node = $("#jstree_demo_div").jstree('get_selected');
    $("#jstree_demo_div").jstree(true).rename_node(node, name);
    
    for (var j = 0; j < folders.length; j++) {
        if (folder_id === parseInt(folders[j]["id"])) {
            folders[j]["text"] = name;
            folders[j]["changed"] = true;
            break;
        }
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

function createNewFolder() {
    var name = $("#input_modal_input").val();
    if (name === "" || name === null || name === undefined) name = "New Folder";
    var selected = $("#jstree_demo_div").jstree("get_selected", true);
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
        text: new_folder["WName"],
        date: new_folder["Date"],
        type: new_folder["Type"],
        changed: false,
        state: {opened: false, disabled: false, selected: false}
     });
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
                $("#message_modal_title").html("Error");
                $("#message_modal_content").html("<p>There has been an error creating the new folder. Please refresh and try again.<br>If the problem persists then contact <a mailto='contact.smarkbook@gmail.com'>support</a>.</p>");
                $("#message_modal_button").html("OK");
                $("#message_modal_button").off('click').on('click',"");
                MicroModal.show("message_modal");
            }
        },
        error: function(response){
            console.log("Request failed with status code: " + response.status + " - " + response.statusText);
            $("#message_modal_title").html("Error");
            $("#message_modal_content").html("<p>There has been an error creating the new folder. Please refresh and try again.<br>If the problem persists then contact <a mailto='contact.smarkbook@gmail.com'>support</a>.</p>");
            $("#message_modal_button").html("OK");
            $("#message_modal_button").off('click').on('click',"");
            MicroModal.show("message_modal");
        }
    });
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
