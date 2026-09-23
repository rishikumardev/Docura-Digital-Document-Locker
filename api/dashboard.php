<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
$userId=require_login();$conn=db();

$stmt=$conn->prepare('SELECT COUNT(*) c, COALESCE(SUM(CASE WHEN is_deleted=0 THEN file_size ELSE 0 END),0) total_size, SUM(CASE WHEN is_deleted=0 AND is_starred=1 THEN 1 ELSE 0 END) starred FROM documents WHERE user_id=?');
$stmt->execute([$userId]);$s=$stmt->fetch();

$stmt=$conn->prepare('SELECT COUNT(*) c FROM documents WHERE user_id=? AND is_deleted=0 AND EXISTS(SELECT 1 FROM shares sh WHERE sh.document_id=documents.id AND sh.owner_id=?)');
$stmt->execute([$userId,$userId]);$shared=(int)$stmt->fetch()['c'];

$stmt=$conn->prepare('SELECT COUNT(*) c FROM shares WHERE recipient_user_id=?');
$stmt->execute([$userId]);$sharedInbox=(int)$stmt->fetch()['c'];

$stmt=$conn->prepare('SELECT COUNT(*) c FROM documents WHERE user_id=? AND is_deleted=1');
$stmt->execute([$userId]);$trash=(int)$stmt->fetch()['c'];

json_response(['success'=>true,'stats'=>[
    'documents'=>(int)$s['c'],
    'storage_bytes'=>(int)$s['total_size'],
    'starred'=>(int)$s['starred'],
    'shared'=>$shared,
    'shared_inbox'=>$sharedInbox,
    'trash'=>$trash
]]);
