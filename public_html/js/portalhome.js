$(document).ready(function(){
    var blocks = parseInt($("#menuCount").val());
    for(var i = 1; i <= blocks; i++){
        setUpBlockLinkandIcon(i);
    }
    setUpGrid();
    $(window).resize(function(){
        setUpGrid();
    });
    setTimeout(function() { setUpGrid(); }, 1000);
});

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
