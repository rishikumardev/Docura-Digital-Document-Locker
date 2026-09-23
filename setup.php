<?php
declare(strict_types=1);
require_once __DIR__.'/config/config.php';

$dbPath=__DIR__.'/database/docura.sqlite';
$messages=[];$ok=false;

try{
    if(!is_dir(__DIR__.'/database'))mkdir(__DIR__.'/database',0755,true);
    $pdo=new PDO('sqlite:'.$dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE,PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA foreign_keys=ON');
    $sql=file_get_contents(__DIR__.'/database/schema.sql');
    $pdo->exec($sql);
    $messages[]='SQLite database and all tables are ready.';
    $ok=true;
}catch(Throwable $e){
    $messages[]='Setup failed: '.$e->getMessage();
}
?>
<!doctype html>
<html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Docura Setup</title>
<style>
body{font-family:Segoe UI,Arial;margin:0;background:linear-gradient(135deg,#e8f0ff,#f5efff);color:#172741}.wrap{max-width:650px;margin:60px auto;padding:20px}.box{background:#fff;border:1px solid #dbe5f1;border-radius:18px;padding:26px;box-shadow:0 20px 50px #33527a18}h1{margin-top:0}.msg{padding:11px;border-radius:9px;margin:10px 0;background:#eaf8ef;color:#187954}.btn{display:inline-block;text-decoration:none;padding:12px 18px;border-radius:9px;background:linear-gradient(135deg,#397fff,#6d55ef);color:#fff;font-weight:800}.note{font-size:13px;color:#71839b;line-height:1.6;margin-top:18px}
</style></head><body><div class="wrap"><div class="box"><h1>Docura Setup</h1><p>This version uses SQLite, so XAMPP/MySQL is not required.</p>
<?php foreach($messages as $m): ?><div class="msg"><?=htmlspecialchars($m)?></div><?php endforeach; ?>
<?php if($ok): ?><a class="btn" href="index.php">Open Docura</a><?php endif; ?>
<div class="note">If PHP is available, this creates <b>database/docura.sqlite</b> automatically. For security, remove or rename setup.php after setup.</div>
</div></div></body></html>
