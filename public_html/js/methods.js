/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

function closeDiv(){
    document.getElementById('message').style.display = 'none';
}

$(document).ready(function(){
    IECheck();
});

function IECheck(){
    var ie = /MSIE (\d+)/.exec(navigator.userAgent);
    ie = ie? ie[1] : null;
    if(ie && ie <= 9) {
        $("#msg_IE").css("display", "block");
    }
}

function closeIEMsg() {
    $("#msg_IE").css("display", "none");
}
