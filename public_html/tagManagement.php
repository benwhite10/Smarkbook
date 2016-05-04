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
}else{
    header($resultArray[1]);
    exit();
}

if(!authoriseUserRoles($userRole, ["SUPER_USER"])){
    header("Location: unauthorisedAccess.php");
    exit();
}

$query = "select `Tag ID`, `Name` from TTAGS order by `Name`;";
try{
    $tags = db_select_exception($query);
} catch (Exception $ex) {
   
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
    <?php pageHeader("Tags"); ?>
    <script src="js/jquery-ui.js"></script>
    <script src="js/tagsList.js"></script>
    <script src="js/tagManagement.js"></script>
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
                    <h1>Manage Tags</h1>
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
            
            <form id="editForm" class="editWorksheet" action="includes/manageTags.php" method="POST">
                <div id="main_content">
                    <div id="modeDiv">
                    <label for="type">Mode:
                    </label><select name="type" id="mode" onchange="changeType()">
                        <option value='MERGE'>Merge Tags</option>
                        <option value='MODIFY'>Modify Tag</option>
                        <!--<option value='DELETE'>Delete Tag</option>-->
                    </select>
                    </div><div id="tag1">
                    <label for="tag1" id="tag1label">Tag:
                    </label><select name="tag1">
                        <option value=0>-No Tag Selected-</option>
                        <?php
                            if(isset($tags)){
                                foreach($tags as $tag){
                                    $id = $tag['Tag ID'];
                                    $name = $tag['Name'];
                                    echo "<option value='$id'>$name</option>";
                                }
                            }
                        ?>
                    </select>
                    </div><div id="tag2">
                    <label for="tag2" id="tag2label">Tag 2:
                    </label><select name="tag2">
                        <option value=0>-No Tag Selected-</option>
                        <?php
                            if(isset($tags)){
                                foreach($tags as $tag){
                                    $id = $tag['Tag ID'];
                                    $name = $tag['Name'];
                                    echo "<option value='$id'>$name</option>";
                                }
                            }
                        ?>
                    </select>
                    </div><div id="name">
                    <label for="name">Name:
                    </label><input type="text" name="name" placeholder="Name">
                    </div>
                    <p id="descText" style="text-align: center;">This will replace all instances of tag 2 with the value of tag 1 and then delete tag 2. This process is irreversible.</p>
                </div><div id="side_bar">
                    <ul class="menu sidebar">
                        <li><input type="submit" value="Submit" id="submit"/></li>
                    </ul>
                </div>
            </form> 
    	</div>
    </div>  
    <script src="js/tagsList.js"></script>
</body>

	