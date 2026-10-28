<?php
declare(strict_types=1);
session_start();
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

function out(array $data, int $status=200): never {
    http_response_code($status); echo json_encode($data, JSON_UNESCAPED_UNICODE); exit;
}
function config(): array {
    $p=__DIR__.'/config.php';
    if (!is_file($p)) out(['ok'=>false,'error'=>'Avval setup.php orqali sozlang.'],503);
    return require $p;
}
function db(): PDO {
    $c=config(); $path=$c['db_path'] ?? (__DIR__.'/data/yummi.sqlite');
    $dir=dirname($path); if(!is_dir($dir)) mkdir($dir,0700,true);
    $pdo=new PDO('sqlite:'.$path,null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
    $pdo->exec('PRAGMA foreign_keys=ON');
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY, telegram_id TEXT UNIQUE NOT NULL, username TEXT DEFAULT '', first_name TEXT DEFAULT 'Foydalanuvchi', coins INTEGER NOT NULL DEFAULT 0, diamonds INTEGER NOT NULL DEFAULT 0, energy INTEGER NOT NULL DEFAULT 200, max_energy INTEGER NOT NULL DEFAULT 200, power INTEGER NOT NULL DEFAULT 1, taps INTEGER NOT NULL DEFAULT 0, uzs INTEGER NOT NULL DEFAULT 0, last_energy INTEGER NOT NULL DEFAULT 0, created_at TEXT DEFAULT CURRENT_TIMESTAMP)");
    $pdo->exec("CREATE TABLE IF NOT EXISTS settings (key TEXT PRIMARY KEY, value TEXT NOT NULL)");
    $pdo->exec("CREATE TABLE IF NOT EXISTS tasks (id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT NOT NULL, description TEXT DEFAULT '', reward INTEGER NOT NULL DEFAULT 0, active INTEGER NOT NULL DEFAULT 1)");
    $pdo->exec("CREATE TABLE IF NOT EXISTS claims (user_id INTEGER NOT NULL, task_id INTEGER NOT NULL, claimed_at TEXT DEFAULT CURRENT_TIMESTAMP, UNIQUE(user_id,task_id), FOREIGN KEY(user_id) REFERENCES users(id), FOREIGN KEY(task_id) REFERENCES tasks(id))");
    $pdo->exec("CREATE TABLE IF NOT EXISTS orders (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER NOT NULL, kind TEXT NOT NULL, amount INTEGER NOT NULL DEFAULT 0, status TEXT NOT NULL DEFAULT 'pending', created_at TEXT DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY(user_id) REFERENCES users(id))");
    return $pdo;
}
function telegramUser(array $c): array {
    $raw=file_get_contents('php://input'); $body=json_decode($raw ?: '{}',true);
    if(!is_array($body)) out(['ok'=>false,'error'=>'JSON noto‘g‘ri'],400);
    $init=(string)($body['initData']??'');
    $token=(string)($c['telegram_bot_token']??'');
    if($init==='' || $token==='' || $token==='BOT_TOKENNI_SHU_YERGA_KIRITING') out(['ok'=>false,'error'=>'Telegram Web App initData yoki bot token sozlanmagan.'],401);
    parse_str($init,$data); $hash=$data['hash']??''; unset($data['hash']);
    if(!$hash || empty($data['auth_date']) || abs(time()-(int)$data['auth_date'])>86400) out(['ok'=>false,'error'=>'Telegram sessiyasi eskirgan yoki noto‘g‘ri.'],401);
    ksort($data); $check=[]; foreach($data as $k=>$v)$check[]=$k.'='.$v; $checkString=implode("\n",$check);
    $secret=hash_hmac('sha256',$token,'WebAppData',true); $calc=hash_hmac('sha256',$checkString,$secret);
    if(!hash_equals($calc,(string)$hash)) out(['ok'=>false,'error'=>'Telegram imzosi tekshiruvdan o‘tmadi.'],401);
    $u=json_decode((string)($data['user']??'{}'),true);
    if(!is_array($u)||empty($u['id'])) out(['ok'=>false,'error'=>'Telegram foydalanuvchisi topilmadi.'],401);
    return ['id'=>(string)$u['id'],'username'=>(string)($u['username']??''),'first_name'=>(string)($u['first_name']??'Foydalanuvchi')];
}
function getUser(PDO $pdo,array $u): array {
    $st=$pdo->prepare('INSERT OR IGNORE INTO users(telegram_id,username,first_name,last_energy) VALUES(?,?,?,?)');$st->execute([$u['id'],$u['username'],$u['first_name'],time()]);
    $st=$pdo->prepare('UPDATE users SET username=?,first_name=? WHERE telegram_id=?');$st->execute([$u['username'],$u['first_name'],$u['id']]);
    $st=$pdo->prepare('SELECT * FROM users WHERE telegram_id=?');$st->execute([$u['id']]);return $st->fetch();
}
function state(PDO $pdo,array $u): array {
    $row=getUser($pdo,$u); $now=time();$elapsed=max(0,min(86400,$now-(int)$row['last_energy']));$energy=min((int)$row['max_energy'],(int)$row['energy']+(int)floor($elapsed/2));
    if($energy!==(int)$row['energy'] || $now-(int)$row['last_energy']>=2){$st=$pdo->prepare('UPDATE users SET energy=?,last_energy=? WHERE id=?');$st->execute([$energy,$now,$row['id']]);$row['energy']=$energy;$row['last_energy']=$now;}
    $tasks=$pdo->query('SELECT id,title,description,reward FROM tasks WHERE active=1 ORDER BY id DESC')->fetchAll();
    $claimed=$pdo->prepare('SELECT task_id FROM claims WHERE user_id=?');$claimed->execute([$row['id']]);$done=array_column($claimed->fetchAll(),'task_id');$tasks=array_values(array_filter($tasks,fn($t)=>!in_array($t['id'],$done)));
    return ['state'=>['coins'=>(int)$row['coins'],'diamonds'=>(int)$row['diamonds'],'energy'=>(int)$row['energy'],'maxEnergy'=>(int)$row['max_energy'],'power'=>(int)$row['power'],'taps'=>(int)$row['taps'],'uzs'=>(int)$row['uzs'],'tasks'=>$tasks],'user'=>['username'=>$row['username'],'first_name'=>$row['first_name']]];
}
$c=config();$body=json_decode(file_get_contents('php://input')?:'{}',true);if(!is_array($body))out(['ok'=>false,'error'=>'JSON noto‘g‘ri'],400);
if(($_SERVER['REQUEST_METHOD']??'GET')!=='POST')out(['ok'=>false,'error'=>'Faqat POST so‘rovi mumkin'],405);
$u=telegramUser($c);$pdo=db();$action=(string)($body['action']??'state');$user=getUser($pdo,$u);
if($action==='tap'){
  $pdo->beginTransaction();
  $st=$pdo->prepare('SELECT * FROM users WHERE id=?');$st->execute([$user['id']]);$r=$st->fetch();
  $elapsed=max(0,min(86400,time()-(int)$r['last_energy']));$energy=min((int)$r['max_energy'],(int)$r['energy']+(int)floor($elapsed/2));
  if($energy<1){$pdo->prepare('UPDATE users SET energy=?,last_energy=? WHERE id=?')->execute([$energy,time(),$user['id']]);$pdo->commit();out(['ok'=>false,'error'=>'Energiya tugagan. Biroz kuting.'],409);}
  $pdo->prepare('UPDATE users SET energy=?,coins=coins+power,taps=taps+1,last_energy=? WHERE id=?')->execute([$energy-1,time(),$user['id']]);$pdo->commit();
} elseif($action==='claim'){
  $id=(int)($body['task_id']??0);$st=$pdo->prepare('SELECT * FROM tasks WHERE id=? AND active=1');$st->execute([$id]);$task=$st->fetch();if(!$task)out(['ok'=>false,'error'=>'Vazifa mavjud emas'],404);
  $pdo->beginTransaction();try{$pdo->prepare('INSERT INTO claims(user_id,task_id) VALUES(?,?)')->execute([$user['id'],$id]);$pdo->prepare('UPDATE users SET coins=coins+? WHERE id=?')->execute([(int)$task['reward'],$user['id']]);$pdo->commit();}catch(Throwable $e){$pdo->rollBack();out(['ok'=>false,'error'=>'Bu vazifa avval olingan yoki bajarilmadi.'],409);}
} elseif($action!=='state') out(['ok'=>false,'error'=>'Noma’lum amal'],400);
out(['ok'=>true]+state($pdo,$u));
