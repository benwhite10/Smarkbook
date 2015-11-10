/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

$(document).ready(function(){
    $('#editForm').validate({

        rules:{
            worksheetname: "required",
            versionname: "required",
            questions: {required:true, number:true, min:1}
        },

        messages:{
            worksheetname: "Please enter a worksheet name",
            versionname: "Please enter a version",
            questions: "Please enter the number of questions"
        },

        submitHandler: function(form){
            form.submit();
            return true;
        }
    });
});
