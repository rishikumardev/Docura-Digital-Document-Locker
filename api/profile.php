<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
$userId=require_login();$conn=db();

if($_SERVER['REQUEST_METHOD']==='PUT'){
    verify_csrf();$d=request_json();$name=trim((string)($d['name']??''));$email=strtolower(trim((string)($d['email']??'')));
    if($name===''||!filter_var($email,FILTER_VALIDATE_EMAIL))json_response(['success'=>false,'message'=>'Enter a valid name and email.'],422);
    $check=$conn->prepare('SELECT id FROM users WHERE email=? AND id<>?');$check->execute([$email,$userId]);if($check->fetch())json_response(['success'=>false,'message'=>'That email is already in use.'],409);
    $conn->prepare('UPDATE users SET name=?,email=? WHERE id=?')->execute([$name,$email,$userId]);activity($conn,$userId,'Updated profile',null,'Profile details changed');
    $stmt=$conn->prepare('SELECT id,name,email,role,created_at FROM users WHERE id=?');$stmt->execute([$userId]);
    json_response(['success'=>true,'user'=>$stmt->fetch(),'message'=>'Profile updated.']);
}
json_response(['success'=>false,'message'=>'Method not allowed.'],405);
