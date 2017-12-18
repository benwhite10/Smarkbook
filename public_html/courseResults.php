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

?>

<!DOCTYPE html>
<html>
<head lang="en">
    <?php pageHeader("Internal Results", $info_version); ?>
    <script src="js/sorttable.js?<?php echo $info_version; ?>"></script>
    <script src="js/courseResults.js?<?php echo $info_version; ?>"></script>
    <link rel="stylesheet" type="text/css" href="css/courseResults.css?<?php echo $info_version; ?>" />
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
                    <h1>Course Name</h1>
                </div>
                <ul class="menu navbar">
                </ul>
            </div><div id="main_content">
                <table border="1" id='results_table'>
                    <thead>
                        <tr class="no_hover">
                            <th class="blank_cell" ></th>
                            <th class="blank_cell" ></th>
                            <th class="blank_cell" ></th>
                            <th style='text-align: center' class='rotate'><div title='Worksheet Name' onclick=''><span title='Worksheet Name'>Worksheet Name 1</span></div></th>
                            <th style='text-align: center' class='rotate'><div title='Worksheet Name' onclick=''><span title='Worksheet Name'>Worksheet Name 2</span></div></th>
                            <th style='text-align: center' class='rotate'><div title='Worksheet Name' onclick=''><span title='Worksheet Name'>Worksheet Name 3</span></div></th>
                            <th style='text-align: center' class='rotate'><div><span>&nbsp</span></div></th>
                            <th style='text-align: center' class='rotate'><div><span>&nbsp</span></div></th>
                            <th style='text-align: center' class='rotate'><div><span>&nbsp</span></div></th>
                            <th style='text-align: center' class='rotate'><div><span>&nbsp</span></div></th>
                            <th style='text-align: center' class='rotate'><div><span>&nbsp</span></div></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class='no_hover blank_cell'>
                            <td class='blank_cell'></td>
                            <td class='blank_cell'></td>
                            <td class='blank_cell'></td>
                            <td class='date' title='02/11/17' onclick=''><b>02/11</b></td>
                            <td class='date' title='02/11/17' onclick=''><b>03/11</b></td>
                            <td class='date' title='02/11/17' onclick=''><b>04/11</b></td>
                            <td class='date'></td>
                            <td class='date'></td>
                            <td class='date'></td>
                            <td class='date'></td>
                            <td class='date'></td>
                        </tr>
                        <tr class='no_hover'>
                            <td class='blank_cell'></td>
                            <td class='blank_cell'></td>
                            <td class='blank_cell'></td>
                            <td class='total_marks'><b>/ 30</b></td>
                            <td class='total_marks'><b>/ 40</b></td>
                            <td class='total_marks'><b>/ 50</b></td>
                            <td class='total_marks'></td>
                            <td class='total_marks'></td>
                            <td class='total_marks'></td>
                            <td class='total_marks'></td>
                            <td class='total_marks'></td>
                        </tr>
                        <tr>
                            <td class='name' onclick=''>Student 1</td>
                            <td class='set' onclick=''>09CGCMaN</td>
                            <td class='initials' onclick=''>BJW</td>
                            <td class='marks'>10</td>
                            <td class='marks'>20</td>
                            <td class='marks'>30</td>
                            <td class='marks'></td>
                            <td class='marks'></td>
                            <td class='marks'></td>
                            <td class='marks'></td>
                            <td class='marks'></td>
                        </tr>
                        <tr>
                            <td class='name' onclick=''>Student 2</td>
                            <td class='set' onclick=''>09CGCMaN</td>
                            <td class='initials' onclick=''>BJW</td>
                            <td class='marks'>10</td>
                            <td class='marks'>20</td>
                            <td class='marks'>30</td>
                            <td class='marks'></td>
                            <td class='marks'></td>
                            <td class='marks'></td>
                            <td class='marks'></td>
                            <td class='marks'></td>
                        </tr>
                        <tr>
                            <td class='name' onclick=''>Student 3</td>
                            <td class='set' onclick=''>09CGCMaE</td>
                            <td class='initials' onclick=''>RPJ</td>
                            <td class='marks'>10</td>
                            <td class='marks'>20</td>
                            <td class='marks'>30</td>
                            <td class='marks'></td>
                            <td class='marks'></td>
                            <td class='marks'></td>
                            <td class='marks'></td>
                            <td class='marks'></td>
                        </tr>                        
                    </tbody>
                </table>
            </div><div id="side_bar" class="menu_bar">

            </div>
    	</div>
        <?php pageFooter($info_version) ?>
    </div>
</body>

	