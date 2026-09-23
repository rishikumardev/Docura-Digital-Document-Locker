<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../config/config.php';

$userId=require_login();$conn=db();$method=$_SERVER['REQUEST_METHOD'];

if($method==='GET'){
    $includeTrash=($_GET['trash']??'0')==='1';
    $q=trim((string)($_GET['q']??''));$type=trim((string)($_GET['type']??'all'));
    $sql='SELECT d.id,d.file_name,d.file_size,d.file_type,d.is_starred,d.is_deleted,d.created_at,d.folder_id,COALESCE(f.folder_name,"Unsorted") folder_name
          FROM documents d LEFT JOIN folders f ON f.id=d.folder_id
          WHERE d.user_id=? AND d.is_deleted=?';
    $params=[$userId,$includeTrash?1:0];

    if($q!==''){ $sql.=' AND d.file_name LIKE ?';$params[]='%'.$q.'%'; }
    if($type!=='all'){
        if($type==='pdf')$sql.=' AND d.file_type="application/pdf"';
        elseif($type==='image')$sql.=' AND d.file_type LIKE "image/%"';
        elseif($type==='sheet')$sql.=' AND d.file_type IN ("text/csv","application/vnd.ms-excel","application/vnd.openxmlformats-officedocument.spreadsheetml.sheet")';
        elseif($type==='doc')$sql.=' AND d.file_type NOT LIKE "image/%" AND d.file_type<>"application/pdf" AND d.file_type NOT IN ("text/csv","application/vnd.ms-excel","application/vnd.openxmlformats-officedocument.spreadsheetml.sheet")';
    }
    $sql.=' ORDER BY d.created_at DESC';
    $stmt=$conn->prepare($sql);$stmt->execute($params);
    json_response(['success'=>true,'documents'=>$stmt->fetchAll()]);
}

if($method==='POST'){
    verify_csrf();
    if(!isset($_FILES['document']))json_response(['success'=>false,'message'=>'No document uploaded.'],422);
    $file=$_FILES['document'];
    if($file['error']!==UPLOAD_ERR_OK)json_response(['success'=>false,'message'=>'Upload failed.'],422);
    if((int)$file['size']<=0 || (int)$file['size']>MAX_UPLOAD_BYTES)json_response(['success'=>false,'message'=>'Maximum file size is 10 MB.'],422);

    $finfo=new finfo(FILEINFO_MIME_TYPE);$mime=$finfo->file($file['tmp_name'])?:'application/octet-stream';
    $allowed=['application/pdf','image/jpeg','image/png','image/gif','image/webp','text/plain','text/csv','application/msword','application/vnd.openxmlformats-officedocument.wordprocessingml.document','application/vnd.ms-excel','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
    if(!in_array($mime,$allowed,true))json_response(['success'=>false,'message'=>'This file type is not allowed.'],422);

    if(!is_dir(UPLOAD_DIR))mkdir(UPLOAD_DIR,0755,true);
    $original=clean_original_name($file['name']);
    $stored=bin2hex(random_bytes(16)).'_'.preg_replace('/[^a-zA-Z0-9._-]/','_',$original);
    $dest=UPLOAD_DIR.$stored;
    if(!move_uploaded_file($file['tmp_name'],$dest))json_response(['success'=>false,'message'=>'Could not save uploaded file.'],500);

    $folderId=null;
    if(isset($_POST['folder_id'])&&$_POST['folder_id']!==''){
        $check=$conn->prepare('SELECT id FROM folders WHERE id=? AND user_id=?');
        $check->execute([(int)$_POST['folder_id'],$userId]);
        if($check->fetch())$folderId=(int)$_POST['folder_id'];
    }

    $stmt=$conn->prepare('INSERT INTO documents(user_id,folder_id,file_name,stored_name,file_size,file_type) VALUES(?,?,?,?,?,?)');
    $stmt->execute([$userId,$folderId,$original,$stored,(int)$file['size'],$mime]);
    activity($conn,$userId,'Uploaded',$original,'Document stored successfully');
    json_response(['success'=>true,'message'=>'Document uploaded successfully.']);
}

if($method==='PUT'){
    verify_csrf();$data=request_json();$id=(int)($data['id']??0);$action=(string)($data['action']??'');
    $stmt=$conn->prepare('SELECT id,file_name,is_deleted,is_starred FROM documents WHERE id=? AND user_id=? LIMIT 1');
    $stmt->execute([$id,$userId]);$doc=$stmt->fetch();
    if(!$doc)json_response(['success'=>false,'message'=>'Document not found.'],404);

    if($action==='star'){
        $new=((int)$doc['is_starred'])?0:1;
        $conn->prepare('UPDATE documents SET is_starred=? WHERE id=? AND user_id=?')->execute([$new,$id,$userId]);
        activity($conn,$userId,$new?'Starred':'Unstarred',$doc['file_name']);
        json_response(['success'=>true,'starred'=>(bool)$new]);
    }
    if($action==='restore'){
        $conn->prepare('UPDATE documents SET is_deleted=0 WHERE id=? AND user_id=?')->execute([$id,$userId]);
        activity($conn,$userId,'Restored',$doc['file_name']);json_response(['success'=>true,'message'=>'Document restored.']);
    }
    if($action==='trash'){
        $conn->prepare('UPDATE documents SET is_deleted=1 WHERE id=? AND user_id=?')->execute([$id,$userId]);
        activity($conn,$userId,'Moved to Trash',$doc['file_name']);json_response(['success'=>true,'message'=>'Document moved to Trash.']);
    }
    if($action==='permanent'){
        $stmt=$conn->prepare('SELECT stored_name FROM documents WHERE id=? AND user_id=? AND is_deleted=1');
        $stmt->execute([$id,$userId]);$stored=$stmt->fetch()['stored_name']??null;
        $conn->prepare('DELETE FROM documents WHERE id=? AND user_id=? AND is_deleted=1')->execute([$id,$userId]);
        if($stored)@unlink(UPLOAD_DIR.$stored);
        activity($conn,$userId,'Permanently deleted',$doc['file_name']);json_response(['success'=>true,'message'=>'Document permanently deleted.']);
    }
    json_response(['success'=>false,'message'=>'Unknown document action.'],400);
}
json_response(['success'=>false,'message'=>'Method not allowed.'],405);
