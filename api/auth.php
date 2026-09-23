<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';

start_secure_session();
$conn = db();
$action = $_GET['action'] ?? '';

if (in_array($action, ['register', 'login'], true)) verify_csrf();

if ($action === 'register') {
    $data = request_json();
    $name = trim((string)($data['name'] ?? ''));
    $email = strtolower(trim((string)($data['email'] ?? '')));
    $password = (string)($data['password'] ?? '');

    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6) {
        json_response(['success'=>false,'message'=>'Enter a valid name, email and password of at least 6 characters.'],422);
    }
    if (user_exists_by_email($conn,$email)) {
        json_response(['success'=>false,'message'=>'An account with this email already exists.'],409);
    }

    $hash = password_hash($password,PASSWORD_DEFAULT);
    $stmt=$conn->prepare('INSERT INTO users (name,email,password,role) VALUES (?,?,?,?)');
    $stmt->execute([$name,$email,$hash,'user']);
    $userId=(int)$conn->lastInsertId();

    $folderStmt=$conn->prepare('INSERT INTO folders (user_id,folder_name) VALUES (?,?)');
    foreach(['Identity','Education','Finance','Certificates'] as $folder){
        $folderStmt->execute([$userId,$folder]);
    }

    session_regenerate_id(true);
    $_SESSION['user_id']=$userId;

    $stmt2=$conn->prepare('SELECT id,name,email,role,created_at FROM users WHERE id=?');
    $stmt2->execute([$userId]);
    $user=$stmt2->fetch();

    activity($conn,$userId,'Account created',null,'New Docura account');
    json_response(['success'=>true,'message'=>'Account created successfully.','user'=>$user]);
}

if ($action === 'login') {
    $data=request_json();
    $email=strtolower(trim((string)($data['email']??'')));
    $password=(string)($data['password']??'');

    $stmt=$conn->prepare('SELECT id,name,email,password,role,created_at FROM users WHERE email=? LIMIT 1');
    $stmt->execute([$email]);
    $user=$stmt->fetch();

    if(!$user || !password_verify($password,$user['password'])){
        json_response(['success'=>false,'message'=>'Invalid email or password.'],401);
    }

    session_regenerate_id(true);
    $_SESSION['user_id']=(int)$user['id'];
    unset($user['password']);
    activity($conn,(int)$user['id'],'Signed in',null,'User logged in');
    json_response(['success'=>true,'message'=>'Signed in successfully.','user'=>$user]);
}

if($action==='logout'){
    verify_csrf();
    $uid=current_user_id();
    if($uid) activity($conn,$uid,'Logged out',null,'User logged out');
    $_SESSION=[];
    session_destroy();
    json_response(['success'=>true,'message'=>'Logged out successfully.']);
}

if($action==='me'){
    $uid=current_user_id();
    if(!$uid) json_response(['success'=>true,'user'=>null]);
    $stmt=$conn->prepare('SELECT id,name,email,role,created_at FROM users WHERE id=? LIMIT 1');
    $stmt->execute([$uid]);
    json_response(['success'=>true,'user'=>$stmt->fetch() ?: null]);
}

json_response(['success'=>false,'message'=>'Invalid auth action.'],400);
