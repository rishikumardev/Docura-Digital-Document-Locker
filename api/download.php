<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/helpers.php';
start_secure_session();
$docId=(int)($_GET['id']??0);$userId=current_user_id();
if(!$userId||!$docId){http_response_code(403);exit('Access denied');}
$conn=db();
$stmt=$conn->prepare('SELECT d.file_name,d.stored_name,d.file_type,d.user_id,
 EXISTS(SELECT 1 FROM shares s WHERE s.document_id=d.id AND s.recipient_user_id=? AND s.permission="download" AND (s.expires_at IS NULL OR s.expires_at>CURRENT_TIMESTAMP)) download_access
 FROM documents d WHERE d.id=? AND d.is_deleted=0');
$stmt->execute([$userId,$docId]);$doc=$stmt->fetch();
if(!$doc||((int)$doc['user_id']!==$userId&&!(int)$doc['download_access'])){http_response_code(403);exit('Download permission denied');}
$path=UPLOAD_DIR.$doc['stored_name'];if(!is_file($path)){http_response_code(404);exit('File not found');}
activity($conn,$userId,'Downloaded',$doc['file_name']);
header('Content-Type: '.$doc['file_type']);header('Content-Length: '.filesize($path));header('Content-Disposition: attachment; filename="'.basename($doc['file_name']).'"');readfile($path);
