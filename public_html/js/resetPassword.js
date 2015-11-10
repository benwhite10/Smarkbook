/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

$(document).ready(function(){

    $.validator.addMethod(
        "regex",
        function(value, element, regexp) {
            var re = new RegExp(regexp);
            return this.optional(element) || re.test(value);
        },
        "Please check your input."
    );
    
    $('#editForm').validate({
        
        rules:{
            conf: {equalTo: '#password'},
            email: {required: true, email: true}
        },
        
        messages:{
            conf: "The passwords you have entered do not match",
            email: "Please enter a valid email address"
        },
        
        submitHandler: function(form){
            if($('#password').val() !== ''){
                // Create a new element input, this will be our hashed password field. 
                var p = document.createElement("input");

                // Add the new element to our form. 
                form.appendChild(p);
                p.name = "p";
                p.type = "hidden";
                p.value = hex_sha512($('#password').val());
            }
            
            form.submit();
            return true;
        }
    });
});
