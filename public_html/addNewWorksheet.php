<?php

date_default_timezone_set('Europe/London');

include_once('../includes/db_functions.php');
include_once('../includes/session_functions.php');
include_once('../includes/class.phpmailer.php');
include_once('classes/AllClasses.php');

sec_session_start();
$resultArray = checkUserLoginStatus(filter_input(INPUT_SERVER,'REQUEST_URI',FILTER_SANITIZE_STRING));
if($resultArray[0]){ 
    $user = $_SESSION['user'];
}else{
    header($resultArray[1]);
    exit();
}

$fullName = $user->getFirstName() . ' ' . $user->getSurname();
$userid = $user->getUserId();

$query5 = "SELECT S.`Initials` Initials, S.`User ID` ID FROM TSTAFF S;";
$staff = db_select($query5);

$query4 = "SELECT T.`Name` Name, T.`Tag ID` ID FROM TSTOREDQUESTIONS S JOIN TQUESTIONTAGS Q ON S.`Stored Question ID` = Q.`Stored Question ID` JOIN TTAGS T ON Q.`Tag ID` = T.`Tag ID` GROUP BY T.`Name` ORDER BY COUNT(T.`Name`) DESC, T.`Name`; ";
$alltags = db_select($query4);

$message = filter_input(INPUT_GET,'msg',FILTER_SANITIZE_STRING);
$type = filter_input(INPUT_GET,'err',FILTER_SANITIZE_STRING);

?>

<!DOCTYPE html>
<html>
<head lang="en">
    <meta charset="UTF-8">
    <title>Smarkbook</title>
    <meta name="description" content="Smarkbook" />
    <meta name="keywords" content="Intelligent, personalised feedback through smart data analysis" />
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=9" />
    <!--<link rel="stylesheet" media="screen and (min-device-width: 668px)" type="text/css" href="css/branding.css" />-->
    <link rel="stylesheet" type="text/css" href="css/branding.css" />
    <link rel="stylesheet" type="text/css" href="css/editworksheet.css" />
    <link href="css/autocomplete.css" rel="stylesheet" />
    <script src="js/jquery.js"></script>
    <script src="js/jquery-ui.js"></script>
    <script src="js/allTagsList.js"></script>
    <script src="js/methods.js"></script>
    <link rel="shortcut icon" href="branding/favicon.ico" />
    <link href='http://fonts.googleapis.com/css?family=Open+Sans:300,400' rel='stylesheet' type='text/css'/>
</head>
<body>
    <div id="main">
    	<div id="header">
            <div id="title">
                <a href="index.php"><img src="branding/mainlogo.png"/></a>
            </div>
            <ul class="menu topbar">
                <li>
                    <a href="portalhome.php"><?php echo $fullName ?> &#x25BE</a>
                    <ul class="dropdown topdrop">
                        <li><a href="portalhome.php">Home</a></li>
                        <li><a <?php echo "href='editUser.php?userid=$userid'"; ?>>My Account</a></li>
                        <li><a href="includes/process_logout.php">Log Out</a></li>
                    </ul>
                </li>
            </ul>
    	</div>
    	<div id="body">
            <div id="top_bar">
                <div id="title2">
                    <h1>Add New Worksheet</h1>
                </div>
                <ul class="menu navbar">
                </ul>
            </div>
            
            <?php
                if(isset($message)){
                    if($type == "ERROR"){
                        $div = 'class="error"';
                    }else if($type == "SUCCESS"){
                        $div = 'class="success"';
                    }
                }else{
                    $div = 'style="display:none;"';
                }
            ?>
            
            <div id="message" <?php echo $div; ?>>
                <div id="messageText"><p><?php echo $message; ?></p>
                </div><div id="messageButton" onclick="closeDiv()"><img src="branding/close.png"/></div>
            </div>   
            
            <form id="editForm" class="editWorksheet" action="includes/addNewWorksheet.php" method="POST">
                <div id="main_content">
                    <label for="worksheetname">Worksheet:
                    </label><input type="text" name="worksheetname" placeholder="Name"></input>
                    <label for="versionname">Version:
                    </label><input type="text" name="versionname" placeholder="Version" value="Original"></input>
                    <label for="link">File Link:
                    </label><input type="url" name="link" placeholder="File Link" id="test123"></input>
                    <label for="author">Author:
                    </label><select name="author">
                        <option value=0>Author:</option>
                        <?php
                            foreach($staff as $teacher){
                                $id = $teacher['ID'];
                                $initials = $teacher['Initials'];
                                if($id == $userid){
                                    echo "<option value='$id' selected>$initials</option>";
                                }else{
                                    echo "<option value='$id'>$initials</option>";
                                }
                            }
                        ?>
                    </select>
                    <?php
                        $newdate = date('d/m/Y');
                    ?>
                    <label for="date">Date Added:
                    </label><input type="text" name="date" placeholder="DD/MM/YYYY" value="<?php echo $newdate ?>"></input>
                    
                    <label for="questions">Questions:
                    </label><input type="text" name="questions" placeholder="Number of Questions"></input>
                    
                    <label for="tags">Tags: 
                    </label><textarea name='tags' class='autocomplete' placeholder='Enter the tags which will be used on every question'></textarea>  
                    
                    <input type="submit" value="Save"/>
                </div><div id="side_bar">
                    <ul class="menu sidebar">
                        <li><input type="submit" value="Create Worksheet"/></li>
                        <li><a href="/viewAllWorksheets.php">Cancel</a></li>
                    </ul>
                </div>
            </form> 
    	</div>
    </div>
    <script>
    var availableTags = [];
    <?php
        foreach ($alltags as $tag){
            print 'availableTags.push("' . $tag['Name'] . '");';
        }
    ?>
        
    var allTagNames = [];
    var allTagIds = [];
    <?php
        foreach ($alltags as $tag){
            print 'allTagNames.push("' . $tag['Name'] . '");';
            print 'allTagIds.push("' . $tag['ID'] . '");';
        }
    ?>
        
    function split( val ) {
      return val.split( /,\s*/ );
    }
    function extractLast( term ) {
      return split( term ).pop();
    }

    $( ".autocomplete" )
    // don't navigate away from the field on tab when selecting an item
    .bind( "keydown", function( event ) {
        if ( event.keyCode === $.ui.keyCode.TAB && $( this ).autocomplete( "instance" ).menu.active ) {
            event.preventDefault();
        }
    })

    .autocomplete({
        minLength: 0,
        source: function( request, response ) {
            // delegate back to autocomplete, but extract the last term
            response( $.ui.autocomplete.filter(availableTags, extractLast( request.term ) ) );
        },
        focus: function() {
            // prevent value inserted on focus
            return false;
        },
        select: function( event, ui ) {
            var terms = split( this.value );
            // remove the current input
            terms.pop();
            // add the selected item
            terms.push( ui.item.value );
            // add placeholder to get the comma-and-space at the end
            terms.push( "" );
            this.value = terms.join( ", " );
            return false;
        }
    });

    </script>
    <script src="js/allTagsList.js"></script>
</body>

	