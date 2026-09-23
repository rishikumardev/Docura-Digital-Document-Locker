<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
$userId=require_login();$conn=db();

if($_SERVER['REQUEST_METHOD']==='GET'){
    $limit=min(100,max(1,(int)($_GET['limit']??100)));
    $stmt=$conn->prepare("SELECT id,action,document_name,details,created_at FROM activity WHERE user_id=? ORDER BY created_at DESC LIMIT $limit");
    $stmt->execute([$userId]);json_response(['success'=>true,'activity'=>$stmt->fetchAll()]);
}
if($_SERVER['REQUEST_METHOD']==='DELETE'){
    verify_csrf();$conn->prepare('DELETE FROM activity WHERE user_id=?')->execute([$userId]);json_response(['success'=>true,'message'=>'Activity log cleared.']);
}
json_response(['success'=>false,'message'=>'Method not allowed.'],405);
