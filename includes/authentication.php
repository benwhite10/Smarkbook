<?php

/*
 * The MIT License
 *
 * Copyright 2019 benwhite.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

include_once $include_path . '/jwt/JWT.php';

function createJWT($user_id, $user_role, $parent_id = null, $parent_role = null) {
    $config = parse_ini_file('../../includes/config.ini');
    $jwt_key = $config["jwt_key"];
    $server = $config["server"];
    // create a token
    $payload_array = array();
    $payload_array['user_id'] = $user_id;
    $payload_array['user_role'] = $user_role;
    $payload_array['parent_id'] = $parent_id;
    $payload_array['parent_role'] = $parent_role;
    $payload_array['nbf'] = time();
    $payload_array['exp'] = $server === "local" ? time() + 315569520 : time() + 12*60*60;

    $token = JWT::encode($payload_array, $jwt_key);
    return $token;
}

function validateToken($token) {
    $config = parse_ini_file('../../includes/config.ini');
    $jwt_key = $config["jwt_key"];
    try {
        $decoded_array = JWT::decode($token, $jwt_key, array('HS256'));
        return [TRUE, $decoded_array];
    } catch (BeforeValidException $ex) {
        return [FALSE, "This token is not yet valid."];
    } catch (ExpiredException $ex) {
        return [FALSE, "This token has expired."];
    } catch (SignatureInvalidException $ex) {
        return [FALSE, "This token is invalid"];
    } catch (Exception $ex) {
        return [FALSE, "This token is invalid"];
    }
    return [FALSE, "Error validating"];
}

function runSwitchUser($token, $new_user_id) {
    $response = validateToken($token);
    if(!$response[0]) return [FALSE, "Invalid token."];
    $token_array = $response[1];
    if ($token_array->parent_id !== null && $token_array->parent_role !== null) {
        $user_id = $token_array->parent_id;
        $user_role = $token_array->parent_role;
    } else {
        $user_id = $token_array->user_id;
        $user_role = $token_array->user_role;
    }
    $new_user_role = getUserRole($new_user_id);
    $valid_roles = ["SUPER_USER", "STAFF"];
    if(in_array($user_role, $valid_roles)) {
        $token = createJWT($new_user_id, $new_user_role, $user_id, $user_role);
        return [TRUE, $token];
    } else {
        return [FALSE, "Insufficient access to complete request."];
    }
}

function getUserRole($user_id) {
    $query = "SELECT `Role` FROM TUSERS U WHERE `User ID` = $user_id";
    try{
        $result = db_select_exception($query);
        if (count($result) > 0) {
            return $result[0]["Role"];
        } else {
            log_error("Get user role failed due to invalid user with ID ($user_id).", "includes/authentication.php", __LINE__);
            return FALSE;
        }
    } catch (Exception $ex) {
        log_error("Get user role failed with exception: " . $ex->getMessage(), "includes/authentication.php", __LINE__);
        return FALSE;
    }
}
