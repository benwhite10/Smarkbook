<?php
$include_path = get_include_path();

include_once $include_path . '/includes/db_functions.php';

class User
{
    public $userId;
    public $firstName;
    public $surname;
    public $email;
    public $role;
    public $locked;
    public $lockedTime;
    public $password;
    public $salt;
    public $lastFailedLogin;
    public $loginAttempts;
    public $validation;

    function __construct() {

    }
     // TODO remove no longer required bits
    public static function createUserLoginDetails($id){
        $userObject = new self();
        $query = "SELECT * FROM TUSERS U WHERE U.`User ID` = $id";
        $user = db_select($query);
        $userObject->setUserId($id);
        $userObject->setEmail($user[0]['Email']);
        $userObject->setRole($user[0]['Role']);
        $userObject->setLocked($user[0]['Locked']);
        $userObject->setLockedTime($user[0]['Locked Time']);
        $userObject->setLastFailedLogin($user[0]['Last Failed Login']);
        $userObject->setLoginAttempts($user[0]['Login Attempts']);
        $userObject->setPassword($user[0]['Password']);
        $userObject->setSalt($user[0]['Salt']);
        $userObject->setValidation($user[0]['Validation']);
        return $userObject;
    }

    function getFirstName() {
        return $this->firstName;
    }

    function setFirstName($firstName) {
        $this->firstName = $firstName;
    }

    function getUserId() {
        return $this->userId;
    }

    function getSurname() {
        return $this->surname;
    }

    function getEmail() {
        return $this->email;
    }

    function getRole() {
        return $this->role;
    }

    function setUserId($userId) {
        $this->userId = $userId;
    }

    function setSurname($surname) {
        $this->surname = $surname;
    }

    function setEmail($email) {
        $this->email = $email;
    }

    function setRole($role) {
        $this->role = $role;
    }

    function getLocked() {
        return $this->locked;
    }

    function setLocked($locked) {
        $this->locked = $locked;
    }

    function getLockedTime() {
        return $this->lockedTime;
    }

    function setLockedTime($lockedTime) {
        $this->lockedTime = $lockedTime;
    }

    function getPassword() {
        return $this->password;
    }

    function getSalt() {
        return $this->salt;
    }

    function getLastFailedLogin() {
        return $this->lastFailedLogin;
    }

    function getLoginAttempts() {
        return $this->loginAttempts;
    }

    function setPassword($password) {
        $this->password = $password;
    }

    function setSalt($salt) {
        $this->salt = $salt;
    }

    function setLastFailedLogin($lastFailedLogin) {
        $this->lastFailedLogin = $lastFailedLogin;
    }

    function setLoginAttempts($loginAttempts) {
        $this->loginAttempts = $loginAttempts;
    }

    function setValidation($validation) {
        $this->validation = $validation;
    }

    function getValidation() {
        return $this->validation;
    }

    function setDisplayName($firstName, $surname) {
        $this->displayname = $firstName . " " . $surname;
    }

    function getDisplayName() {
        return $this->displayname;
    }
}

class Teacher extends User
{
    public $staffId;
    public $title;
    public $intials;

    function __construct() {

    }

    public static function createTeacherFromId($id){
        $teacher = new self();
        $query = "SELECT * FROM TUSERS WHERE `User ID` = $id";
        $user = db_select($query);
        $teacher->setUserId($id);
        $teacher->setFirstName($user[0]['First Name']);
        $teacher->setSurname($user[0]['Surname']);
        $teacher->setDisplayName($user[0]['First Name'], $user[0]['Surname']);
        $teacher->setTitle($user[0]['Title']);
        $teacher->setEmail($user[0]['Email']);
        $teacher->setRole($user[0]['Role']);
        $teacher->setInitials($user[0]['Initials']);
        $teacher->setValidation($user[0]['Validation']);
        return $teacher;
    }

    function getStaffId() {
        return $this->staffId;
    }

    function getTitle() {
        return $this->title;
    }

    function getInitials() {
        return $this->intials;
    }

    function setStaffId($staffId) {
        $this->staffId = $staffId;
    }

    function setTitle($title) {
        $this->title = $title;
    }

    function setInitials($initials) {
        $this->intials = $initials;
    }
}

class Student extends User
{
    public $studentId;
    public $prefferedName;

    public static function createStudentFromId($id){
        $student = new self();
        $query = "SELECT * FROM TUSERS WHERE `User ID` = $id";
        $user = db_select($query);
        $student->setUserId($id);
        $student->setFirstName($user[0]['First Name']);
        $student->setSurname($user[0]['Surname']);
        $student->setPrefferedName($user[0]['Preferred Name']);
        $student->setDisplayName($user[0]['First Name'], $user[0]['Surname']);
        $student->setEmail($user[0]['Email']);
        $student->setRole($user[0]['Role']);
        $student->setValidation($user[0]['Validation']);
        return $student;
    }

    function getStudentId() {
        return $this->studentId;
    }

    function getPrefferedName() {
        return $this->prefferedName;
    }

    function setStudentId($studentId) {
        $this->studentId = $studentId;
    }

    function setPrefferedName($prefferedName) {
        $this->prefferedName = $prefferedName;
    }
}
