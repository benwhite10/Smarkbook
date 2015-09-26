<?php
include_once('../includes/db_functions.php');

class User
{
    public $userId;
    public $firstName;
    public $surname;
    public $email;
    public $role;
    
    function __construct() {
       
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
}

class Teacher extends User
{
    public $staffId;
    public $title;
    public $classroom;
    public $phoneNumber;
    public $intials;
    
    function __construct() {
       
    }
    
    public static function createTeacherFromId($id){
        $teacher = new self();
        $query = "SELECT * FROM TUSERS U JOIN TSTAFF S ON U.`User ID` = S.`User ID` WHERE U.`User ID` = $id";
        $user = db_select($query);
        $teacher->setUserId($id);
        $teacher->setFirstName($user[0]['First Name']);
        $teacher->setSurname($user[0]['Surname']);
        $teacher->setTitle($user[0]['Title']);
        $teacher->setStaffId($user[0]['Staff ID']);
        $teacher->setEmail($user[0]['Email']);
        $teacher->setClassroom($user[0]['Classroom']);
        $teacher->setPhoneNumber($user[0]['Phone Number']);
        $teacher->setRole($user[0]['Role']);
        $teacher->setInitials($user[0]['Initials']);
        return $teacher;
    }

    function getStaffId() {
        return $this->staffId;
    }

    function getTitle() {
        return $this->title;
    }

    function getClassroom() {
        return $this->classroom;
    }

    function getPhoneNumber() {
        return $this->phoneNumber;
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

    function setClassroom($classroom) {
        $this->classroom = $classroom;
    }

    function setPhoneNumber($phoneNumber) {
        $this->phoneNumber = $phoneNumber;
    }
    
    function setInitials($initials) {
        $this->intials = $initials;
    }
}

class Student extends User
{
    public $studentId;
    public $prefferedName;
    public $gender;
    public $dateOfBirth;
    
    public static function createStudentFromId($id){
        $student = new self();
        $query = "SELECT * FROM TUSERS U JOIN TSTUDENTS S ON U.`User ID` = S.`User ID` WHERE U.`User ID` = $id";
        $user = db_select($query);
        $student->setUserId($id);
        $student->setFirstName($user[0]['First Name']);
        $student->setSurname($user[0]['Surname']);
        $student->setPrefferedName($user[0]['Preferred Name']);
        $student->setStudentId($user[0]['Student ID']);
        $student->setEmail($user[0]['Email']);
        $student->setRole($user[0]['Role']);
        $student->setGender($user[0]['Gender']);
        $student->setDateOfBirth($user[0]['DOB']);
        return $student;
    }
    
    function getStudentId() {
        return $this->studentId;
    }

    function getPrefferedName() {
        return $this->prefferedName;
    }

    function getGender() {
        return $this->gender;
    }

    function getDateOfBirth() {
        return $this->dateOfBirth;
    }

    function setStudentId($studentId) {
        $this->studentId = $studentId;
    }

    function setPrefferedName($prefferedName) {
        $this->prefferedName = $prefferedName;
    }

    function setGender($gender) {
        $this->gender = $gender;
    }

    function setDateOfBirth($dateOfBirth) {
        $this->dateOfBirth = $dateOfBirth;
    }
}

