/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

function validatePassword(pwd){
    //Length must be at least 8 characters
    if (pwd.value.length < 8) {
        alert('Passwords must be at least 8 characters long. Please try again.');
        return false;
    }
    
    //Must contain at least 1 number and 1 of each case
    var re = /(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}/;
    if (!re.test(pwd)){
        alert('Passwords must contain at least one number, one lowercase and one uppercase letter. Please try again.');
        return false;
    }
    
    return true;
}

function loginFormHash(form, usrname, pwd){
    return true;
}


