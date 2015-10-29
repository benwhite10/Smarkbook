<?php
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

$versionId = filter_input(INPUT_GET,'vid',FILTER_SANITIZE_NUMBER_INT);
$setId = filter_input(INPUT_GET,'setid',FILTER_SANITIZE_STRING);
$staffId = filter_input(INPUT_GET,'staffid',FILTER_SANITIZE_STRING);
$message = filter_input(INPUT_GET,'msg',FILTER_SANITIZE_STRING);
$type = filter_input(INPUT_GET,'err',FILTER_SANITIZE_STRING);

if(!$staffId){
    $staffId = $userid;
}

$query1 = "SELECT W.`Worksheet ID` WID, W.`Name` WName, V.`Name` VName, V.`Author ID` AuthorID, S.`Initials` Author, V.`Date Added` Date, W.`Link` Link FROM TWORKSHEETVERSION V JOIN TWORKSHEETS W ON V.`Worksheet ID` = W.`Worksheet ID` JOIN TSTAFF S ON V.`Author ID` = S.`Staff ID` WHERE V.`Version ID` = $versionId;";
$worksheet = db_select($query1);

$query2 = "SELECT S.`Stored Question ID` ID, S.`Number` Number, S.`Marks` Marks FROM TSTOREDQUESTIONS S WHERE S.`Version ID` = $versionId ORDER BY S.`Order`;";
$questions = db_select($query2);

$query3 = "SELECT U.`User ID` ID, CONCAT(S.`Preferred Name`,' ',U.Surname) Name FROM TUSERGROUPS G JOIN TUSERS U ON G.`User ID` = U.`User ID` JOIN TSTUDENTS S ON U.`User ID` = S.`User ID` WHERE G.`Group ID` = $setId ORDER BY U.Surname;";
$students = db_select($query3);

$query4 = "SELECT CONCAT(C.`Stored Question ID`,'-',C.`Student ID`) String1, CONCAT(C.`Stored Question ID`,'-',C.`Student ID`,'-',C.`Mark`,'-',C.`Completed Question ID`) String2, C.`Stored Question ID` SqID, C.`Student ID` StuID, C.`Mark` Mark FROM `TCOMPLETEDQUESTIONS` C JOIN `TSTOREDQUESTIONS` S ON C.`Stored Question ID` = S.`Stored Question ID` JOIN TSTUDENTS ST ON ST.`Student ID` = C.`Student ID` JOIN TUSERGROUPS U ON ST.`User ID` = U.`User ID` WHERE S.`Version ID` = $versionId AND C.`Staff ID` = $staffId AND U.`Group ID` = $setId;";
$results = db_select($query4);

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
    <script src="js/tagsList.js"></script>
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
                    <h1><?php echo $worksheet[0]['WName']; ?></h1>
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
            
            <form id="editForm" class="editResults" action="includes/updateResults.php" method="POST">
                <div id="main_content" style="overflow: scroll;">
                    <input type="hidden" name = "version" value="<?php echo $versionId ?>" />
                    <input type="hidden" name = "set" value="<?php echo $setId ?>" />
                    <input type="hidden" name = "staff" value="<?php echo $staffId ?>" />
                    <table border="1">
                        <thead>
                            <tr>
                                <th>Students</th>
                                <?php
                                    foreach($questions as $question){
                                        $qno = $question['Number'];
                                        echo "<th style='text-align: center'>$qno</th>";
                                    }
                                ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                                //Make the search array
                                $search = array();
                                foreach($results as $result){
                                    $search[] = $result['String1'];
                                }
                            
                                echo "<tr><td></td>";
                                foreach ($questions as $question){
                                    $marks = $question['Marks'];
                                    echo "<td style='text-align: center'><b>/ $marks</b></td>";
                                }
                                echo "</tr>";
                                foreach($students as $student){
                                    $stuId = $student['ID'];
                                    $stuName = $student['Name'];
                                    echo "<tr><td style='min-width: 180px;'>$stuName</td>";
                                    foreach ($questions as $question){
                                        $qid = $question['ID'];
                                        $string = $qid . '-' . $stuId;
                                        $key = array_search($string, $search);
                                        if($key === false){
                                            $id = $string;
                                            $mark = '';
                                        }else{
                                            $id = $results[$key]['String2'];
                                            $mark = $results[$key]['Mark'];
                                        }
                                        echo "<td style='padding:0px; text-align: center;'><input type='text' class='markInput' name='resultInput[]' value=$mark><input type='hidden' name='resultInfo[]' value=$id /></td>";
                                    }
                                    echo "</tr>";
                                }
                            ?> 
                        </tbody>
                    </table>
                </div><div id="side_bar">
                    <ul class="menu sidebar">
                        <li><input type="submit" value="Save"/></li>
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

	