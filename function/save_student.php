<?php
include __DIR__ . '/function.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id     = $_POST['id'];
    $fname  = $_POST['fname'];
    $mname  = $_POST['mname'];
    $lname  = $_POST['lname'];
    $gender = $_POST['gender'];
    $email  = $_POST['email'];
    $class  = $_POST['class'];

    if (registerStudent($id, $fname, $mname, $lname, $gender, $email, $class)) {
        echo "<script>alert('Student Registered Successfully'); window.location.href='module/enrollment_form.php';</script>";
    } else {
        echo "<script>alert('Error Registering Student'); window.history.back();</script>";
    }
}
?>
