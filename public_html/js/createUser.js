/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

$(document).ready(function(){
    $('#role').change(function(){
        var elem = document.getElementById('role');
        if(elem.value === 'STUDENT'){
            var hideElems = document.getElementsByClassName('staff');
            var showElems = document.getElementsByClassName('student');
            hideElements(hideElems);
            showElements(showElems);
        }else{
            var hideElems = document.getElementsByClassName('student');
            var showElems = document.getElementsByClassName('staff');
            hideElements(hideElems);
            showElements(showElems);
        }
    });
    
    function hideElements(elements){
        for(var i = 0; i < elements.length; i++){
            elements[i].style.display = 'none';
        }
    }

    function showElements(elements){
        for(var i = 0; i < elements.length; i++){
            elements[i].style.display = 'block';
        }
    }
    
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
            password: {required: true, minlength: 6},
            confPassword: {equalTo: '#password'},
            firstname: "required",
            surname: "required",
            email: {required: true, email: true},
            number: {regex: "^[0-9 ]+$"}
        },
        
        messages:{
            password: "Please enter a password at least 6 characters long",
            confPassword: "The passwords you have entered do not match",
            firstname: "Please enter your first name",
            surname: "Please enter a surname",
            email: "Please enter a valid email address",
            number: "Please enter a valid phone number"
        },
        
        submitHandler: function(form){
            // Create a new element input, this will be our hashed password field. 
            var p = document.createElement("input");

            // Add the new element to our form. 
            form.appendChild(p);
            p.name = "p";
            p.type = "hidden";
            p.value = hex_sha512($('#password').val());
            
            var elem = document.getElementById('role');
            
            form.submit();
            return true;
        }
    });
});
