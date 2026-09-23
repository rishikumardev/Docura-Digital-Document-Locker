<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
$userId=require_login();$conn=db();

if($_SERVER['REQUEST_METHOD']==='GET'){
    $stmt=$conn->prepare('SELECT f.id,f.folder_name,COUNT(d.id) document_count FROM folders f LEFT JOIN documents d ON d.folder_id=f.id AND d.is_deleted=0 WHERE f.user_id=? GROUP BY f.id ORDER BY f.folder_name');
    $stmt->execute([$userId]);json_response(['success'=>true,'folders'=>$stmt->fetchAll()]);
}
if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();$name=trim((string)(request_json()['name']??''));
    if($name==='')json_response(['success'=>false,'message'=>'Folder name is required.'],422);
    $stmt=$conn->prepare('INSERT INTO folders(user_id,folder_name) VALUES(?,?)');
    try{$stmt->execute([$userId,$name]);}catch(Throwable $e){json_response(['success'=>false,'message'=>'A folder with that name already exists.'],409);}
    activity($conn,$userId,'Created folder',$name);json_response(['success'=>true,'message'=>'Folder created.']);
}
json_response(['success'=>false,'message'=>'Method not allowed.'],405);
