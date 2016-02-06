<?php

function orderBy($orderby, $desc){
    $query = "";
    if(count($orderby) == count($desc)){
        $query .= " ORDER BY";
        if(count($orderby) == 1){
            $variable = $orderby[0];
            $des = $desc[0];
            $query .= " $variable ";
            if($des == "TRUE"){
                $query .= "DESC ";
            }
        }else{
            for($i = 0; $i < count($orderby); $i++){
                $variable = $orderby[$i];
                $des = $desc[$i];
                $query .= " $variable ";
                if($des == "TRUE"){
                    $query .= "DESC ";
                }
                if($i != count($orderby) - 1){
                    $query .= ","; 
                }
            }
        }
    }
    return $query;
}

function groupBy($variables){
    if(!isset($variables) || count($variables) == 0){
        return "";
    }
    $query = " GROUP BY";
    if(count($variables) == 1){
        $variable = $variables[0];
        $query .= " $variable";
    }else{
        for($i = 0; $i < count($variables); $i++){
            $variable = $variables[$i];
            $query .= " $variable";
            if($i != count($variables) - 1){
                $query .= ","; 
            }
        }
    }
    return $query;
}

function filterBy($variables, $values){
    $query = "";
    if(count($variables) == count($values)){
        $query .= " WHERE ";
        if(count($variables) == 1){
            $variable = $variables[0];
            $value = $values[0];
            $query .= " $variable = '$value'";
        }else{
            for($i = 0; $i < count($variables); $i++){
                $variable = $variables[$i];
                $value = $values[$i];
                $query .= " $variable = '$value'";
                if($i != count($variables) - 1){
                    $query .= " AND "; 
                }
            }
        }
    }
    return $query;
}

function sendCURLRequest($url, $postData){
    // Get cURL resource
    $curl = curl_init();
    
    $url = $_SERVER['HTTP_HOST'] . $url;

    // Set some options - we are passing in a useragent too here
    curl_setopt_array($curl, array(
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_URL => $url,
        CURLOPT_POST => 1,
        CURLOPT_POSTFIELDS => $postData
    ));

    // Send the request & save response to $resp
    $resp = curl_exec($curl);
    $success = TRUE;
    if(!$resp){
        $success = FALSE;
        $resp = 'Error: "' . curl_error($curl) . '" - Code: ' . curl_errno($curl);
    }
    
    // Close request to clear up some resources
    curl_close($curl);
    
    return [$success, $resp];
}

