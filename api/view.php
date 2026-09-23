<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../config/config.php';
start_secure_session();
$docId=(int)($_GET['id']??0);$userId=current_user_id();
if(!$userId||!$docId){http_response_code(403);exit('Access denied');}
$conn=db();
$stmt=$conn->prepare('SELECT d.file_name,d.stored_name,d.file_type,d.file_size,d.user_id,
    EXISTS(SELECT 1 FROM shares s WHERE s.document_id=d.id AND s.recipient_user_id=? AND (s.expires_at IS NULL OR s.expires_at>CURRENT_TIMESTAMP)) shared_access
    FROM documents d WHERE d.id=? AND d.is_deleted=0 LIMIT 1');
$stmt->execute([$userId,$docId]);$doc=$stmt->fetch();
if(!$doc||((int)$doc['user_id']!==$userId&&!(int)$doc['shared_access'])){http_response_code(403);exit('Access denied');}
$path=UPLOAD_DIR.$doc['stored_name'];if(!is_file($path)){http_response_code(404);exit('File not found');}
header('Content-Type: '.$doc['file_type']);header('Content-Length: '.filesize($path));header('Content-Disposition: inline; filename="'.rawurlencode($doc['file_name']).'"');readfile($path);
