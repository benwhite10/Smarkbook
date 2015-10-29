/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

$(document).ready(function(){
    $('#login_form').submit(function(){
        $username = $('#username').val();
        $password = $('#password').val();
        if($username == '' || $password == ''){
            //Failure
            
            return false;
        }
        
        //Some of determining whether or not we are dealing with a student or a member of staff
        
        var p = document.createElement("input");

        // Add the new element to our form. 
        form.appendChild(p);
        p.name = "p";
        p.type = "hidden";
        p.value = hex_sha512($('#password').val());
        
        $('#password').val("");
        
        return true;
    })
})


