<?php
$include_path = get_include_path();
include_once $include_path . '/includes/db_functions.php';
include_once $include_path . '/includes/session_functions.php';
include_once $include_path . '/includes/class.phpmailer.php';
include_once $include_path . '/public_html/classes/AllClasses.php';

sec_session_start();
$resultArray = checkUserLoginStatus(filter_input(INPUT_SERVER,'REQUEST_URI',FILTER_SANITIZE_STRING));
if($resultArray[0]){ 
    $user = $_SESSION['user'];
    $fullName = $user->getFirstName() . ' ' . $user->getSurname();
    $userid = $user->getUserId();
    $userRole = $user->getRole();
}else{
    header($resultArray[1]);
    exit();
}

if(!authoriseUserRoles($userRole, ["SUPER_USER", "STAFF"])){
    header("Location: unauthorisedAccess.php");
    exit();
}

$vid = filter_input(INPUT_GET,'id',FILTER_SANITIZE_NUMBER_INT);

if(!isset($vid)){
    failWithMessage("Something went wrong loading the details, please try again.");
}

$stopFlag = FALSE;

$queries = array(
    "SELECT W.`Worksheet ID` WID, W.`Name` WName, V.`Name` VName, V.`Author ID` AuthorID, S.`Initials` Author, V.`Date Added` Date, W.`Link` Link FROM TWORKSHEETVERSION V JOIN TWORKSHEETS W ON V.`Worksheet ID` = W.`Worksheet ID` JOIN TSTAFF S ON V.`Author ID` = S.`Staff ID` WHERE V.`Version ID` = $vid;",
    "SELECT S.`Stored Question ID` ID, S.`Number` Number, S.`Marks` Marks FROM TSTOREDQUESTIONS S WHERE S.`Version ID` = $vid ORDER BY S.`Question Order`;",
    "SELECT S.`Stored Question ID` ID, T.`Name` Name FROM TSTOREDQUESTIONS S JOIN TQUESTIONTAGS Q ON S.`Stored Question ID` = Q.`Stored Question ID` JOIN TTAGS T ON Q.`Tag ID` = T.`Tag ID` WHERE S.`Version ID` = $vid ORDER BY T.`Name`;",
    "SELECT T.`Name` Name, T.`Tag ID` ID FROM TSTOREDQUESTIONS S JOIN TQUESTIONTAGS Q ON S.`Stored Question ID` = Q.`Stored Question ID` JOIN TTAGS T ON Q.`Tag ID` = T.`Tag ID` GROUP BY T.`Name` ORDER BY COUNT(T.`Name`) DESC, T.`Name`; ",
    "SELECT S.`Initials` Initials, S.`User ID` ID FROM TSTAFF S ORDER BY S.`Initials`;"
);

$errors = array(
    "Something went wrong retrieving the worksheet with Version ID ($vid)",
    "Something went wrong retrieving questions for the worksheet with Version ID ($vid)",
    "Something went wrong retrieving tags for the worksheet with Version ID ($vid)",
    "Something went wrong loading all of the tags, please try again.",
    "Something went wrong loading all of the staff, please try again"
);

$variables = array(
    "worksheet",
    "questions",
    "tags",
    "alltags",
    "staff"
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

function failWithMessage($msg, $ex){
    $_SESSION['message'] = new Message("ERROR", $msg);
    errorLog($msg . ' - ' . $ex->getMessage());
}

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
    <link rel="stylesheet" type="text/css" href="css/jquery-ui-date.css"/>
    <script src="js/jquery.js"></script>
    <script src="js/jquery-ui.js"></script>
    <script src="js/jquery-ui.min.js"></script>
    <script src="js/jquery.validate.min.js"></script>
    <script src="js/tagsList.js"></script>
    <script src="js/methods.js"></script>
    <script src="js/moment.js"></script>
    <script>
        $(function() {
          $( "#datepicker" ).datepicker({ dateFormat: 'dd/mm/yy' });
        });
    </script>
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
                    <a href="portalhome.php"><?php echo $fullName; ?> &#x25BE</a>
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
                    <h1>Edit Worksheet</h1>
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
            <form id="editForm" class="editWorksheet" action="includes/updateWorksheet.php" method="POST">
                <div id="main_content">
                    <input type="hidden" name = "version" value="<?php echo $vid ?>" />
                    <label for="worksheetname">Worksheet:
                    </label><input type="text" name="worksheetname" id="worksheetname" placeholder="Name" value="<?php echo $worksheet[0]['WName'] ?>"></input>
                    <label for="versionname">Version:
                    </label><input type="text" name="versionname" id="versionname" placeholder="Version" value="<?php echo $worksheet[0]['VName'] ?>"></input>
                    <label for="link">File Link:
                    </label><input type="url" name="link" placeholder="File Link" id="test123" value="<?php echo $worksheet[0]['Link'] ?>"></input>
                    <label for="author">Author:
                    </label><select name="author" id="author">
                        <option value=0>Author:</option>
                        <?php
                            $author = $worksheet[0]['AuthorID'];
                            foreach($staff as $teacher){
                                $id = $teacher['ID'];
                                $initials = $teacher['Initials'];
                                if($id == $author){
                                    echo "<option value='$id' selected>$initials</option>";
                                }else{
                                    echo "<option value='$id'>$initials</option>";
                                }
                            }
                        ?>
                    </select>
                    <?php
                        $date = $worksheet[0]['Date'];
                        $newdate = date('d/m/Y',strtotime($date));
                    ?>
                    <label for="date">Date Added:
                    </label><input type="text" name="date" id="datepicker" placeholder="DD/MM/YYYY" value="<?php echo $newdate ?>"></input>
                    <?php 
                        $count = 1;
                        foreach ($questions as $question){
                            $number = $question['Number'];
                            $marks = $question['Marks'];
                            $qid = $question['ID'];

                            $ques = 'question' . $number;
                            echo "<fieldset id='$ques'>";
                            echo "<legend>Question " . $number . "</legend>";
                            
                            $name = $count . 'a';
                            echo "<input type='hidden' name='$name' value='$qid' >";
                            
                            $name1 = $count .'num';
                            echo "<label for='$name1'>Number:";
                            echo "</label><input type='text' name='$name1' value='$number'></input>";
                            
                            $name2 = $count . 'mark';
                            echo "<label for='$name2'>Marks:";
                            echo "</label><input type='text' name='$name2' value='$marks'></input>";

                            $tagstring = "";
                            
                            foreach($tags as $tag){
                                if($tag['ID'] == $qid){
                                    $name = $tag['Name'];
                                    $tagstring = $tagstring . $name . ", ";
                                }
                            }

                            $substr = substr($tagstring, 0, -2);

                            $name3 = $count . 'tags';
                            echo "<label for='$name3'>Tags: ";
                            echo "</label><textarea name='$name3' class='autocomplete' >$tagstring</textarea>";
                            
                            //Leave like this so that ' can be included in the tag name
                            $varname = $count . 'currTags';
                            print '<input type="hidden" name="' . $varname . '" value="' . $tagstring . '" />';

                            echo "</fieldset>";
                            $count = $count + 1;
                        }
                    ?> 
                    <input type="submit" value="Save"/>
                </div>
            <?php } ?>
                <div id="side_bar">
                    <ul class="menu sidebar">
                        <!--<li><a href="www.bbc.co.uk">Add Question</a></li>-->
                        <?php if(!$stopFlag){ ?>
                        <li><input type="submit" value="Save"/></li>
                        <?php } ?>
                        <li><a <?php 
                                    if(isset($vid)){
                                        echo "href='/viewWorksheet.php?id=$vid'";
                                    }else{
                                        echo "href='/viewAllWorksheets.php'";
                                    }
                                ?>
                                >Back To Overview</a></li>
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
    <script src="js/tagsList.js"></script>
</body>

	