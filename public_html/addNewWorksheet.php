<?php

$include_path = get_include_path();
include_once $include_path . '/includes/db_functions.php';
include_once $include_path . '/includes/session_functions.php';
include_once $include_path . '/includes/class.phpmailer.php';
include_once $include_path . '/public_html/classes/AllClasses.php';
include_once $include_path . '/public_html/includes/htmlCore.php';

sec_session_start();
$resultArray = checkUserLoginStatus(filter_input(INPUT_SERVER,'REQUEST_URI',FILTER_SANITIZE_STRING));
if($resultArray[0]){ 
    $user = $_SESSION['user'];
    $fullName = $user->getFirstName() . ' ' . $user->getSurname();
    $userid = $user->getUserId();
    $userRole = $user->getRole();
    $author = $userid;
}else{
    header($resultArray[1]);
    exit();
}

if(!authoriseUserRoles($userRole, ["SUPER_USER", "STAFF"])){
    header("Location: unauthorisedAccess.php");
    exit();
}

if(isset($_SESSION["formValues"])){
    $informationArray = $_SESSION["formValues"];
    $wname = $informationArray[0];
    $vname = $informationArray[1];
    $author = $informationArray[2];
    $date = $informationArray[3];
    $number = $informationArray[4];
    $tags = $informationArray[5];
    $link = $informationArray[6];
    unset($_SESSION["formValues"]);
}

$stopFlag = FALSE;

$queries = array(
    "SELECT S.`Initials` Initials, S.`User ID` ID FROM TSTAFF S WHERE S.`Initials` <> '' ORDER BY S.`Initials`;",
    "SELECT T.`Name` Name, T.`Tag ID` ID FROM TSTOREDQUESTIONS S JOIN TQUESTIONTAGS Q ON S.`Stored Question ID` = Q.`Stored Question ID` JOIN TTAGS T ON Q.`Tag ID` = T.`Tag ID` GROUP BY T.`Name` ORDER BY COUNT(T.`Name`) DESC, T.`Name`; "
);

$errors = array(
    "Something went wrong loading all of the staff, please try again.",
    "Something went wrong loading all of the tags, please try again"
);

$variables = array(
    "staff",
    "alltags"
);

for($i = 0; $i < count($queries); $i++) {
    if(!$stopFlag){
        $query = $queries[$i];
        $error = $errors[$i];
        $variable = $variables[$i];
        if(isset($query, $variable, $error)){
            try{
                $$variable = db_select_exception($query);
            } catch (Exception $ex) {
                $stopFlag = true;
                failWithMessage($error, $ex);
            }
        }else{
            $stopFlag = true;
        }
    }
}

if(isset($_SESSION['message'])){
    $Message = $_SESSION['message'];
    $message = $Message->getMessage();
    $type = $Message->getType();
    unset($_SESSION['message']);
}

?>

<!DOCTYPE html>
<html>
<head lang="en">
    <?php pageHeader("New Worksheet"); ?>
    <script src="js/jquery-ui.js"></script>
    <script>
        $(function() {
          $( "#datepicker" ).datepicker({ dateFormat: 'dd/mm/yy' });
        });
    </script>
    <link rel="stylesheet" type="text/css" href="css/editworksheet.css" />
    <link rel="stylesheet" type="text/css" href="css/autocomplete.css"  />
    <link rel="stylesheet" type="text/css" href="css/jquery-ui-date.css"/>
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
                <div id="messageText"><p><?php if(isset($message)) {echo $message;} ?></p>
                </div><div id="messageButton" onclick="closeDiv()"><img src="branding/close.png"/></div>
            </div>   
            
            <?php if(!$stopFlag){ ?>
            <form id="editForm" class="editWorksheet" action="includes/addNewWorksheet.php" method="POST">
                <div id="main_content">
                    <label for="worksheetname">Worksheet:
                    </label><input type="text" name="worksheetname" id="worksheetname" placeholder="Name" value="<?php if(isset($wname)){ echo $wname; } ?>" />
                    <!--
                    <label for="versionname">Version:
                    </label><input type="text" name="versionname" id="versionname" placeholder="Version" value="<?php echo isset($vname)?$vname:"Original"; ?>" />
                    -->
                    <label for="link">File Link:
                    </label><input type="url" name="link" placeholder="File Link" id="test123" value="<?php if(isset($link)){ echo $link; } ?>" />
                    <label for="author">Author:
                    </label><select name="author" id="author">
                        <option value=0>Author:</option>
                        <?php
                            if(isset($staff)){
                                foreach($staff as $teacher){
                                    $id = $teacher['ID'];
                                    $initials = $teacher['Initials'];
                                    if($id == $author){
                                        echo "<option value='$id' selected>$initials</option>";
                                    }else{
                                        echo "<option value='$id'>$initials</option>";
                                    }
                                }
                            }
                        ?>
                    </select>
                    <?php
                        if(!isset($date)){
                            $date = date('d/m/Y');
                        }
                    ?>
                    <label for="date">Date Added:
                    </label><input type="text" name="date" id="datepicker" placeholder="DD/MM/YYYY" value="<?php echo $date ?>"/>
    
                    <label for="questions">Questions:
                    </label><input type="text" name="questions" id="questions" placeholder="Number of Questions" value="<?php echo isset($number)?$number:1; ?>"/>
                    
                    <label for="tags">Tags: 
                    </label><textarea name="tags" id="tags" class="autocomplete" placeholder="Enter the tags which will be used on every question" ><?php if(isset($tags)){echo $tags;} ?></textarea>  
                    
                    <!--<input type="submit" value="Save"/>-->
                </div>
                <?php } ?>
                <div id="side_bar">
                    <ul class="menu sidebar">
                        <?php if(!$stopFlag){ ?>
                        <li><input type="submit" value="Create Worksheet"/></li>
                        <?php } ?>
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
    <script src="js/addWorksheet.js"></script>
</body>

	