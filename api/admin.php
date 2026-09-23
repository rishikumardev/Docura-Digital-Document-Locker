<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
$userId=require_login();$conn=db();
$stmt=$conn->prepare('SELECT role FROM users WHERE id=?');$stmt->execute([$userId]);$role=$stmt->fetch()['role']??'user';
if($role!=='admin')json_response(['success'=>false,'message'=>'Admin access required.'],403);

$users=$conn->query('SELECT id,name,email,role,created_at FROM users ORDER BY created_at DESC')->fetchAll();
$docs=$conn->query('SELECT COUNT(*) total,COALESCE(SUM(file_size),0) bytes FROM documents WHERE is_deleted=0')->fetch();
$shares=$conn->query('SELECT COUNT(*) total FROM shares')->fetch();
json_response(['success'=>true,'stats'=>['users'=>count($users),'documents'=>(int)$docs['total'],'storage_bytes'=>(int)$docs['bytes'],'shares'=>(int)$shares['total']],'users_list'=>$users]);
