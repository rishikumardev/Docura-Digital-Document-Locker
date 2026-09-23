<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
$userId=require_login();$conn=db();

if($_SERVER['REQUEST_METHOD']==='GET'){
    $stmt=$conn->prepare('SELECT s.id,s.permission,s.created_at,d.id document_id,d.file_name,d.file_size,d.file_type,u.name owner_name,u.email owner_email
                          FROM shares s JOIN documents d ON d.id=s.document_id JOIN users u ON u.id=s.owner_id
                          WHERE s.recipient_user_id=? AND d.is_deleted=0 AND (s.expires_at IS NULL OR s.expires_at>CURRENT_TIMESTAMP)
                          ORDER BY s.created_at DESC');
    $stmt->execute([$userId]);json_response(['success'=>true,'shares'=>$stmt->fetchAll()]);
}
if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();$d=request_json();$documentId=(int)($d['document_id']??0);$email=strtolower(trim((string)($d['email']??'')));$permission=($d['permission']??'view')==='download'?'download':'view';
    $stmt=$conn->prepare('SELECT id,file_name FROM documents WHERE id=? AND user_id=? AND is_deleted=0');
    $stmt->execute([$documentId,$userId]);$doc=$stmt->fetch();if(!$doc)json_response(['success'=>false,'message'=>'Document not found.'],404);
    $recipient=user_exists_by_email($conn,$email);if(!$recipient)json_response(['success'=>false,'message'=>'Recipient must create a Docura account first.'],404);
    if((int)$recipient['id']===$userId)json_response(['success'=>false,'message'=>'You cannot share a document with yourself.'],422);
    $dup=$conn->prepare('SELECT id FROM shares WHERE document_id=? AND recipient_user_id=?');
    $dup->execute([$documentId,(int)$recipient['id']]);if($dup->fetch())json_response(['success'=>false,'message'=>'Already shared with this user.'],409);
    $conn->prepare('INSERT INTO shares(document_id,owner_id,recipient_user_id,permission) VALUES(?,?,?,?)')->execute([$documentId,$userId,(int)$recipient['id'],$permission]);
    activity($conn,$userId,'Shared',$doc['file_name'],'with '.$email);
    json_response(['success'=>true,'message'=>'Document shared successfully.']);
}
json_response(['success'=>false,'message'=>'Method not allowed.'],405);
