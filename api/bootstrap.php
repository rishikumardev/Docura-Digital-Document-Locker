<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/session.php';
start_secure_session();

if(empty($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(32));

$user=null;
if(current_user_id()){
    require_once __DIR__ . '/../includes/db.php';
    $conn=db();
    $stmt=$conn->prepare('SELECT id,name,email,role,created_at FROM users WHERE id=? LIMIT 1');
    $stmt->execute([current_user_id()]);
    $user=$stmt->fetch() ?: null;
}
json_response(['success'=>true,'csrf'=>$_SESSION['csrf'],'user'=>$user]);
