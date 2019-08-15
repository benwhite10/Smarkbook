<?php

$include_path = get_include_path();
include_once $include_path . '/includes/core.php';

function getUsersData() {
    // Get cURL resource
    $curl = curl_init();
    $config = getConfigFile();
    if ($config["status"] === "dev") return [TRUE, unserialize(file_get_contents('user_data.txt'))];
    $api_key = $config["api_key_users"];
    $api_secret = $config["api_secret_users"];
    $new_url = "https://isams.wellingtoncollege.org.uk:443/api/batch/1.0/xml.ashx?apiKey=$api_key&apiSecret=$api_secret";
    curl_setopt_array($curl, array(
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_URL => $new_url
    ));
    // Send the request & save response to $resp
    $resp = curl_exec($curl);
    if(!$resp) return [FALSE, curl_error($curl)];
    curl_close($curl);
    // Close request to clear up some resources
    libxml_use_internal_errors(true);
    $response = simplexml_load_string($resp);
    if(!$response) return [FALSE, $resp];
    /*$user_data = createUserData($response);
    file_put_contents('user_data.txt',serialize($user_data));
    return [TRUE, $user_data];*/
    return [TRUE, createUserData($response)];
}

function createUserData($response) {
    $return_array = array();
    $staff = cleanArray(xmlToArray($response->HRManager->CurrentStaff, array('alwaysArray' => array("Role"))));
    $pupils = cleanArray(xmlToArray($response->PupilManager->CurrentPupils));
    //$academic_houses = cleanArray(xmlToArray($response->SchoolManager->AcademicHouses));
    //$boarding_houses = cleanArray(xmlToArray($response->SchoolManager->BoardingHouses));
    //$forms = cleanArray(xmlToArray($response->SchoolManager->Forms));
    $terms = cleanArray(xmlToArray($response->SchoolManager->Terms));
    $sets = cleanArray(xmlToArray($response->TeachingManager->Sets, array('alwaysArray' => array("Teacher"))));
    $set_lists = cleanArray(xmlToArray($response->TeachingManager->SetLists));
    $return_array["Staff"] = $staff["CurrentStaff"]["StaffMember"];
    $return_array["Pupils"] = $pupils["CurrentPupils"]["Pupil"];
    //$return_array["AcademicHouses"] = $academic_houses["AcademicHouses"]["House"];
    //$return_array["BoardingHouses"] = $boarding_houses["BoardingHouses"]["House"];
    //$return_array["Forms"] = $forms["Forms"]["Form"];
    $return_array["Terms"] = $terms["Terms"]["Term"];
    $return_array["Sets"] = $sets["Sets"]["Set"];
    $return_array["SetLists"] = $set_lists["SetLists"]["SetList"];
    return $return_array;
}

function createSchoolReports($response) {
    $return_array = array();
    $options = array('alwaysArray' => array("ReportCycles", "ReportCycle", "GeneralComment", "Comment", "Grading", "Value", "Grade"));
    $overall_school_reports = cleanArray(xmlToArray($response->SchoolReports, $options));
    $return_array["ReportTypes"] = $overall_school_reports["SchoolReports"]["ReportTypes"]["ReportType"];
    $return_array["Templates"] = $overall_school_reports["SchoolReports"]["Templates"]["Template"];
    $report_cycles = $overall_school_reports["SchoolReports"]["ReportCycles"];
    for ($i = 0; $i < count($report_cycles); $i++) {
        if (!is_array($report_cycles[$i])) continue;
        $type = array_key_exists("Filtered", $report_cycles[$i]) ? "ReportCycles" : "Reports";
        $return_array[$type] = array_key_exists("ReportCycle", $report_cycles[$i]) ? $report_cycles[$i]["ReportCycle"] : [];
    }
    return $return_array;
}

function xmlToArray($xml, $options = array()) {
    $defaults = array(
        'namespaceSeparator' => ':',//you may want this to be something other than a colon
        'attributePrefix' => '',   //to distinguish between attributes and nodes with the same name
        'alwaysArray' => array(),   //array of xml tag names which should always become arrays
        'autoArray' => true,       //only create arrays for tags which appear more than once
        'textContent' => 'Value',       //key used for the text content of elements
        'autoText' => true,         //skip textContent key if node has no attributes or child nodes
        'keySearch' => false,       //optional search and replace on tag and attribute names
        'keyReplace' => false       //replace values for above search values (as passed to str_replace())
    );
    $options = array_merge($defaults, $options);
    $namespaces = $xml->getDocNamespaces();
    $namespaces[''] = null; //add base (empty) namespace

    //get attributes from all namespaces
    $attributesArray = array();
    foreach ($namespaces as $prefix => $namespace) {
        foreach ($xml->attributes($namespace) as $attributeName => $attribute) {
            //replace characters in attribute name
            if ($options['keySearch']) $attributeName =
                    str_replace($options['keySearch'], $options['keyReplace'], $attributeName);
            $attributeKey = $options['attributePrefix']
                    . ($prefix ? $prefix . $options['namespaceSeparator'] : '')
                    . $attributeName;
            $attributesArray[$attributeKey] = (string)$attribute;
        }
    }

    //get child nodes from all namespaces
    $tagsArray = array();
    foreach ($namespaces as $prefix => $namespace) {
        foreach ($xml->children($namespace) as $childXml) {
            //recurse into child nodes
            $childArray = xmlToArray($childXml, $options);
            list($childTagName, $childProperties) = each($childArray);

            //replace characters in tag name
            if ($options['keySearch']) $childTagName =
                    str_replace($options['keySearch'], $options['keyReplace'], $childTagName);
            //add namespace prefix, if any
            if ($prefix) $childTagName = $prefix . $options['namespaceSeparator'] . $childTagName;

            if (!isset($tagsArray[$childTagName])) {
                //only entry with this key
                //test if tags of this type should always be arrays, no matter the element count
                $tagsArray[$childTagName] =
                        in_array($childTagName, $options['alwaysArray']) || !$options['autoArray']
                        ? array($childProperties) : $childProperties;
            } elseif (
                is_array($tagsArray[$childTagName]) && array_keys($tagsArray[$childTagName])
                === range(0, count($tagsArray[$childTagName]) - 1)
            ) {
                //key already exists and is integer indexed array
                $tagsArray[$childTagName][] = $childProperties;
            } else {
                //key exists so convert to integer indexed array with previous value in position 0
                $tagsArray[$childTagName] = array($tagsArray[$childTagName], $childProperties);
            }
        }
    }

    //get text content of node
    $textContentArray = array();
    $plainText = trim((string)$xml);
    if ($plainText !== '') $textContentArray[$options['textContent']] = $plainText;

    //stick it all together
    $propertiesArray = !$options['autoText'] || $attributesArray || $tagsArray || ($plainText === '')
            ? array_merge($attributesArray, $tagsArray, $textContentArray) : $plainText;

    //return node as array
    return array(
        $xml->getName() => $propertiesArray
    );
}

function cleanArray($array) {
    if (!is_array($array)) return $array;
    foreach ($array as $key => $row) {
        if (is_array($row)
        && count($row) === 2
        && array_key_exists("Legacy", $row)
        && array_key_exists("Value", $row)) {
            $array[$key] = $row["Value"];
        } else if (is_array($row)
        && count($row) === 1
        && array_key_exists("Legacy", $row)) {
            $array[$key] = "";
        } else if (is_array($row)
        && count($row) === 1
        && (array_key_exists(0, $row) && is_string($row[0]))) {
            $array[$key] = $row[0];
        } else if (is_array($row)
        && count($row) === 0) {
            $array[$key] = "";
        } else {
            $array[$key] = cleanArray($row);
        }

    }
    return $array;
}
