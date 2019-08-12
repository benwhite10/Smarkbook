$(document).ready(function(){
    var user = JSON.parse(localStorage.getItem("sbk_usr"));
    window.addEventListener("valid_user", function(){init_page(user);});
    validateAccessToken(user, ["SUPER_USER", "STAFF", "STUDENT"]);
});

function init_page(user) {
    writeNavbar(user);
    writeHomeGrid(user);
    var blocks = parseInt($("#menuCount").val());
    for(var i = 1; i <= blocks; i++){
        setUpBlockLinkandIcon(i);
    }
    setUpGrid();
    $(window).resize(function(){
        setUpGrid();
    });
    setTimeout(function() { setUpGrid(); }, 1000);
}

function writeHomeGrid(user) {
    var user_id = user["userId"];
    var user_role = user["role"];
    var grid_options = {};
    grid_options["view_worksheets"] = {url: "viewAllWorksheets.php?opt=0", title: "Worksheets", img: "home-worksheets.png", permissions: ["SUPER_USER", "STAFF"]};
    grid_options["mark_book"] = {url: "viewSetMarkbook.php?staffId=" + user_id, title: "Mark Book", img: "home-markbook.png", permissions: ["SUPER_USER", "STAFF"]};
    grid_options["checklists"] = {url: "revisionChecklist.php?course=1", title: "Checklists", img: "home-worksheets.png", permissions: ["SUPER_USER", "STAFF", "STUDENT"]};
    grid_options["enter_results_student"] = {url: "newResultsEntryHome.php", title: "Enter Results", img: "home-enter-results.png", permissions: ["SUPER_USER", "STAFF", "STUDENT"]};
    grid_options["enter_results_staff"] = {url: "viewAllWorksheets.php?opt=1", title: "Enter Results", img: "home-enter-results.png", permissions: ["SUPER_USER", "STAFF"]};
    grid_options["reports_student"] = {url: "reportHome.php?student=" + user_id, title: "Reports", img: "home-worksheets.png", permissions: ["SUPER_USER", "STAFF","STUDENT"]};
    grid_options["reports_staff"] = {url: "reportHome.php?staff=" + user_id, title: "Reports", img: "home-worksheets.png", permissions: ["SUPER_USER", "STAFF"]};
    grid_options["internal_results"] = {url: "internalResultsMenu.php", title: "Int. Results", img: "home-markbook.png", permissions: ["SUPER_USER", "STAFF"]};
    grid_options["sets"] = {url: "viewMySets.php", title: "My Sets", img: "home-sets.png", permissions: ["SUPER_USER", "STAFF"]};
    grid_options["tags"] = {url: "viewAllTags.php", title: "Manage Tags", img: "home-modify.png", permissions: ["SUPER_USER", "STAFF"]};
    grid_options["notes"] = {url: "reportNotes.php?t=" + user_id, title: "Report Notes", img: "home-worksheets.png", permissions: ["SUPER_USER", "STAFF"]};
    grid_options["quiz"] = {url: "quiz_menu.php", title: "Quiz", img: "home-quiz.png", permissions: ["SUPER_USER", "STAFF", "STUDENT"]};
    
    var grid_html = "";
    var staff_grid = ["view_worksheets", "mark_book", "checklists", "enter_results_staff", "reports_staff", "internal_results", "sets", "tags", "notes", "quiz"];
    var student_grid = ["enter_results_student", "reports_student", "checklists", "quiz"];
    var final_grid = [];
    if(user_role === "STAFF" || user_role === "SUPER_USER") {
        final_grid = staff_grid;
    } else if (user_role === "STUDENT") {
        final_grid = student_grid;
    }
    var count = 0;
    for (var i = 0; i< final_grid.length; i++) {
        var grid_item = grid_options[final_grid[i]];
        if (grid_item === undefined) continue;
        if (checkRole(user_role, grid_item["permissions"])) {
            count++;
            grid_html += writeHomeGridItem(grid_item, count);
        }
    }
    grid_html += "<input type='hidden' id='menuCount' value=" + count + " />";
    $("#menuContainer").html(grid_html);
}

function writeHomeGridItem(grid_item, count) {
    var html_text = "<div class='menuobject' id='menuobject" + count + "' >";
    html_text += "<a href='" + grid_item["url"] + "' class='title'>" + grid_item["title"] + "</a>";
    html_text += "<input type='hidden' id='menuObjectLink" + count + "' value='" + grid_item["url"] + "'>";
    html_text += "<input type='hidden' id='menuObjectIcon" + count + "' value='" + grid_item["img"] + "'>";
    html_text += "</div>";
    return html_text;
}

function setUpGrid(){
    //Find max and min per row
    var totalBlocks = parseInt($("#menuCount").val());
    var maxWidth = 200;
    var minWidth = 200;
    var screenWidth = parseInt($("#body").css("width"));
    var maxSideMargin = 180;
    var maxMargin = 15;
    var minMargin = 10;
    // Max number = maxAvailableSpace / minSize
    var maxN = (screenWidth + minMargin) / (minWidth + minMargin);
    var intMaxN = Math.floor(maxN);

    // Min number = minAvailableSpace / maxSize
    var minN = (screenWidth + maxMargin - 2 * maxSideMargin) / (maxWidth + maxMargin);
    var intMinN = Math.ceil(minN);
    
    var possN = new Array();
    for(var i = intMaxN; i >= intMinN; i--){
        var ratio = totalBlocks % i !== 0 ? (totalBlocks % i) / i : 1;
        possN.push(ratio);
    }
    
    var n = 0;
    var maxRatio = 0;
    for(var i = 0; i < possN.length; i++){
        if(possN[i] > maxRatio){
            maxRatio = possN[i];
            n = intMaxN - i;
        }
    }
    
    var finalSize = minWidth;
    var finalMargin = minMargin;
    var finalSideMargin = getSideMargin(screenWidth, finalSize, finalMargin, n);
    overall:
    for(var width = minWidth; width <= maxWidth; width++){
        for(var margin = minMargin; margin <= maxMargin; margin++){
            var sideMargin = getSideMargin(screenWidth, width, margin, n);
            if (sideMargin >= 0 && sideMargin <= maxSideMargin){
                var finalSize = width;
                var finalMargin = margin;
                var finalSideMargin = sideMargin;
                break overall;
            }
        }
    }
    
    // Set the size of everything
    var numCols = n;
    var numRows = Math.ceil(totalBlocks/numCols);
    var left, right, top, bottom, light;
    for(var i = 0; i < totalBlocks; i++){
        left = right = top = bottom = false;
        var col = i % numCols;
        var row = Math.floor(i/numCols);
        left = col === 0;
        right = col === (numCols - 1);
        top = row === 0;
        bottom = row === (numRows - 1);
        if(row % 2 === 0){
            light = (col % 2 !== 0);
        } else {
            light = (col % 2 === 0);
        }
        
        setUpBlockSize(i+1, left, right, top, bottom, finalSize, finalMargin, finalSideMargin, light);
    }
}

function setUpBlockSize(num, left, right, top, bottom, size, margin, side, light)
{
    var id = "#menuobject" + num;
    $(id).css("width", size);
    $(id).css("height", size);
    if(left){
        $(id).css("margin-left", side - 1);
    } else {
        $(id).css("margin-left", margin / 2);
    }
    
    if(right){
        $(id).css("margin-right", side - 1);
    } else {
        $(id).css("margin-right", margin / 2);
    }
    
    $(id).css("margin-top", margin / 2);
    $(id).css("margin-bottom", margin / 2);
    
    if(light){
        $(id).addClass("light");
    } else {
        $(id).removeClass("light");
    }
}

function setUpBlockLinkandIcon(num)
{
    var link = $("#menuObjectLink" + num).val();
    var icon = $("#menuObjectIcon" + num).val();
    $("#menuobject" + num).css("background-image", "url('../images/" + icon + "')");
    $("#menuobject" + num).click(function(){
        window.location.href = link;
    });
}

function getSideMargin(screenWidth, boxWidth, margin, n){
    return Math.floor((screenWidth - (n * boxWidth) - ((n - 1) * margin))/2);
}
