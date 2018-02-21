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
    $userval = base64_encode($user->getValidation());
    $info = Info::getInfo();
    $info_version = $info->getVersion();
}else{
    header($resultArray[1]);
    exit();
}

if(!authoriseUserRoles($userRole, ["SUPER_USER", "STAFF"])){
    header("Location: unauthorisedAccess.php");
    exit();
}

if($_GET['id'])
{
    $groupid = $_GET['id'];
}

$query = "SELECT U.`First Name`, U.`Preferred Name`, U.`Surname`, U.`User ID` ID
        FROM TUSERS U JOIN TUSERGROUPS G ON U.`User ID` = G.`User ID`
        WHERE G.`Group ID` = $groupid
        AND G.`Archived` = 0
        AND U.`Role` = 'STUDENT';";
$students = db_select($query);

$query2 = "SELECT  U.`Title`, U.`First Name`, U.`Surname`, U.`Email`
            FROM TUSERS U JOIN TUSERGROUPS G ON U.`User ID` = G.`User ID`
            WHERE G.`Group ID` = $groupid AND G.`Archived` = 0;";
$staff = db_select($query2);

$query3 = "SELECT Name FROM TGROUPS WHERE `Group ID` = $groupid;";
$groupNameResult = db_select($query3);
$groupName = $groupNameResult[0]['Name'];

?>

<!DOCTYPE html>
<html>
<head lang="en">
    <?php pageHeader("Smarkbook", $info_version); ?>
    <script src="js/sorttable.js?<?php echo $info_version; ?>"></script>
    <script src="js/manageGroups.js?<?php echo $info_version; ?>"></script>
    <link rel="stylesheet" type="text/css" href="css/viewGroup.css?<?php echo $info_version; ?>" />
</head>
<body>
    <?php setUpRequestAuthorisation($userid, $userval); ?>
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
                    <h1><?php echo $groupName . ' (' . count($students) . ' Students)';; ?></h1>
                </div>
                <ul class="menu navbar">
                </ul>
            </div><div id="main_content">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                            foreach ($students as $key=>$student){
                                $firstName = $student['First Name'];
                                $prefName = $student['Preferred Name'];
                                if($firstName == $prefName){
                                    $frstName = $firstName;
                                }else{
                                    $frstName = $prefName . ' (<i>' . $firstName . '</i>)';
                                }
                                $surname = $student['Surname'];
                                $id = $student['ID'];
                                $fullName = $frstName . ' ' . $surname;
                                echo "<tr><td style='height: 40px;'><div class='row_left'>$fullName</div><div class='row_right' onClick='removeStudentPrompt($groupid,$id, &quot;$firstName $surname&quot;, &quot;$groupName&quot;);'>Remove</div></td></tr>";
                            }
                        ?>
                    </tbody>
                </table>
                <div id="add_student">
                    <h1 style="margin-left: 10px;">Add New Student</h1>
                    <div id="students_input_div">
                    <input id="students_input" type="text" list="students" placeholder="Student">
                    <datalist id="students">
                        <option value="0">No Students</option>
                    </datalist>
                    </div>
                    <div id="students_button_div" onclick="addStudent(<?php echo $groupid ?>)"><h4 style="text-align: center; line-height: 35px; font-weight: 400;">Add</h4></div>
                </div>
            </div><div id="side_bar" class="menu_bar">
            <ul class="menu sidebar">
                <?php if(authoriseUserRoles($userRole, ["SUPER_USER", "STAFF"])){
                    echo "<li><a href='resultsEntryHome.php?level=1&type=2&staffid=$userid&groupid=$groupid'>Enter Results</a></li>";
                    echo "<li><a href='resultsEntryHome.php?level=2&type=2&staffid=$userid&groupid=$groupid'>Edit Results</a></li>";
                    echo "<li><a href='setReport.php?staff=$userid&set=$groupid'>Set Report</a></li>";
                } ?>
                <li><a href="viewMySets.php">Back To My Sets</a></li>
            </ul>
            </div>
    	</div>
        <?php pageFooter($info_version) ?>
    </div>
</body>
