<?php
declare(strict_types=1);
session_start();
header('X-Content-Type-Options: nosniff');
if(!is_file(__DIR__.'/config.php')){http_response_code(503);exit('Avval setup.php ni ishga tushiring.');}
$c=require __DIR__.'/config.php';
function esc($s){return htmlspecialchars((string)$s,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function dbAdmin($c){$p=$c['db_path'];if(!is_dir(dirname($p)))mkdir(dirname($p),0700,true);$d=new PDO('sqlite:'.$p,null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);$d->exec('PRAGMA foreign_keys=ON');return $d;}
$pdo=dbAdmin($c);
$pdo->exec("CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY, telegram_id TEXT UNIQUE NOT NULL, username TEXT DEFAULT '', first_name TEXT DEFAULT 'Foydalanuvchi', coins INTEGER NOT NULL DEFAULT 0, diamonds INTEGER NOT NULL DEFAULT 0, energy INTEGER NOT NULL DEFAULT 200, max_energy INTEGER NOT NULL DEFAULT 200, power INTEGER NOT NULL DEFAULT 1, taps INTEGER NOT NULL DEFAULT 0, uzs INTEGER NOT NULL DEFAULT 0, last_energy INTEGER NOT NULL DEFAULT 0, created_at TEXT DEFAULT CURRENT_TIMESTAMP)");
$pdo->exec("CREATE TABLE IF NOT EXISTS settings (key TEXT PRIMARY KEY, value TEXT NOT NULL)");
$pdo->exec("CREATE TABLE IF NOT EXISTS tasks (id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT NOT NULL, description TEXT DEFAULT '', reward INTEGER NOT NULL DEFAULT 0, active INTEGER NOT NULL DEFAULT 1)");
$pdo->exec("CREATE TABLE IF NOT EXISTS claims (user_id INTEGER NOT NULL, task_id INTEGER NOT NULL, claimed_at TEXT DEFAULT CURRENT_TIMESTAMP, UNIQUE(user_id,task_id), FOREIGN KEY(user_id) REFERENCES users(id), FOREIGN KEY(task_id) REFERENCES tasks(id))");
$pdo->exec("CREATE TABLE IF NOT EXISTS orders (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER NOT NULL, kind TEXT NOT NULL, amount INTEGER NOT NULL DEFAULT 0, status TEXT NOT NULL DEFAULT 'pending', created_at TEXT DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY(user_id) REFERENCES users(id))");
if(empty($_SESSION['admin_csrf']))$_SESSION['admin_csrf']=bin2hex(random_bytes(32));
$msg='';
if(isset($_GET['logout'])){session_destroy();header('Location: admin.php');exit;}
if(empty($_SESSION['admin_ok']) && $_SERVER['REQUEST_METHOD']==='POST' && ($_POST['do']??'')==='login'){
 $pw=(string)($_POST['password']??'');if(password_verify($pw,(string)($c['admin_password_hash']??''))){session_regenerate_id(true);$_SESSION['admin_ok']=true;$_SESSION['admin_csrf']=bin2hex(random_bytes(32));header('Location: admin.php');exit;}else $msg='Parol noto‘g‘ri.';
}
if(!empty($_SESSION['admin_ok']) && $_SERVER['REQUEST_METHOD']==='POST' && ($_POST['do']??'')!=='login'){
 if(!hash_equals($_SESSION['admin_csrf'],(string)($_POST['csrf']??''))){http_response_code(403);exit('CSRF tekshiruvi bajarilmadi. Sahifani yangilang.');}
 $do=(string)($_POST['do']??'');
 try{
 if($do==='settings'){
  $allowed=['app_name','tap_reward','starting_coins','daily_bonus','theme_color'];foreach($allowed as $k){$v=trim((string)($_POST[$k]??''));if(in_array($k,['tap_reward','starting_coins','daily_bonus'],true)&&(!ctype_digit($v)|| (int)$v>100000000))throw new RuntimeException('Mukofot qiymati noto‘g‘ri.');$st=$pdo->prepare('INSERT INTO settings(key,value) VALUES(?,?) ON CONFLICT(key) DO UPDATE SET value=excluded.value');$st->execute([$k,$v]);}$msg='Sozlamalar saqlandi.';
 }elseif($do==='task_add'){
  $title=trim((string)($_POST['title']??''));$desc=trim((string)($_POST['description']??''));$reward=(int)($_POST['reward']??0);if($title===''||mb_strlen($title)>100||$reward<0||$reward>10000000)throw new RuntimeException('Vazifa nomi yoki mukofot noto‘g‘ri.');$st=$pdo->prepare('INSERT INTO tasks(title,description,reward,active) VALUES(?,?,?,1)');$st->execute([$title,$desc,$reward]);$msg='Vazifa qo‘shildi.';
 }elseif($do==='task_toggle'){$id=(int)$_POST['id'];$pdo->prepare('UPDATE tasks SET active=CASE active WHEN 1 THEN 0 ELSE 1 END WHERE id=?')->execute([$id]);$msg='Vazifa holati o‘zgartirildi.';
 }elseif($do==='user_update'){
  $id=(int)($_POST['id']??0);$coins=(string)($_POST['coins']??'');$diamonds=(string)($_POST['diamonds']??'');if(!ctype_digit($coins)||!ctype_digit($diamonds)||(int)$coins>2000000000||(int)$diamonds>2000000000)throw new RuntimeException('Balans qiymatlari 0 dan katta butun son bo‘lishi kerak.');$st=$pdo->prepare('UPDATE users SET coins=?,diamonds=? WHERE id=?');$st->execute([(int)$coins,(int)$diamonds,$id]);$msg='Foydalanuvchi balansi yangilandi.';
 }
 }catch(Throwable $e){$msg='Xato: '.$e->getMessage();}
}
$logged=!empty($_SESSION['admin_ok']);$settings=[];foreach($pdo->query('SELECT key,value FROM settings') as $r)$settings[$r['key']]=$r['value'];
$tasks=$pdo->query('SELECT * FROM tasks ORDER BY id DESC')->fetchAll();$users=$pdo->query('SELECT * FROM users ORDER BY id DESC LIMIT 100')->fetchAll();$total=(int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
?><!doctype html><html lang="uz"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Yummi Tanga Admin</title><style>
body{margin:0;font:15px Arial;background:#102d3a;color:#f4fbff}main{max-width:1000px;margin:auto;padding:18px}.box{background:#1b414d;border:1px solid #54737c;border-radius:16px;padding:16px;margin:12px 0}h1{font-size:24px}h2{font-size:18px}label{display:block;font-size:13px;color:#cce5ec;margin-top:9px}input{width:100%;box-sizing:border-box;padding:11px;margin:6px 0;border:1px solid #62838c;border-radius:8px;background:#0f303b;color:white}button,.btn{background:#ffd84e;color:#29220c;border:0;border-radius:8px;padding:11px 14px;font-weight:bold;cursor:pointer}button.secondary{background:#2b6571;color:white;border:1px solid #81b5c0}.msg{background:#174d38;padding:10px;border-radius:8px}table{width:100%;border-collapse:collapse;font-size:13px}td,th{padding:8px;border-bottom:1px solid #456771;text-align:left;vertical-align:top}td input{min-width:75px}.scroll{overflow:auto}.danger{color:#ffc5c5}small{color:#c5e0e7;line-height:1.5}</style></head><body><main>
<?php if(!$logged):?><div class="box" style="max-width:440px;margin:8vh auto"><h1>🛠 Yummi Tanga Admin</h1><?php if($msg):?><p class="danger"><?=esc($msg)?></p><?php endif?><form method="post"><input type="hidden" name="do" value="login"><label>Admin paroli</label><input type="password" name="password" required autocomplete="current-password"><button>Kirish</button></form><p><small>Admin parolini GitHub yoki chatlarda ulashmang.</small></p></div>
<?php else:?>
<div style="display:flex;justify-content:space-between;align-items:center;gap:10px"><h1>🛠 Yummi Tanga Admin</h1><a class="btn" href="?logout=1">Chiqish</a></div>
<?php if(isset($_GET['welcome'])):?><p class="msg">Sozlash muvaffaqiyatli. Endi setup.php faylini serverdan o‘chiring.</p><?php endif?>
<?php if($msg):?><p class="msg"><?=esc($msg)?></p><?php endif?>
<div class="box"><h2>🎨 O‘yin sozlamalari</h2><form method="post"><input type="hidden" name="csrf" value="<?=esc($_SESSION['admin_csrf'])?>"><input type="hidden" name="do" value="settings">
<label>O‘yin nomi</label><input name="app_name" value="<?=esc($settings['app_name']??'Yummi Tanga')?>" maxlength="80">
<label>Har bosish uchun mukofot</label><input name="tap_reward" type="number" min="1" max="100000000" value="<?=esc($settings['tap_reward']??'1')?>">
<label>Boshlang‘ich tanga (yangi foydalanuvchilar uchun)</label><input name="starting_coins" type="number" min="0" max="100000000" value="<?=esc($settings['starting_coins']??'0')?>">
<label>Kunlik bonus</label><input name="daily_bonus" type="number" min="0" max="100000000" value="<?=esc($settings['daily_bonus']??'25')?>">
<label>Asosiy rang (CSS hex)</label><input name="theme_color" value="<?=esc($settings['theme_color']??'#ffd84e')?>" maxlength="20">
<button>Sozlamalarni saqlash</button></form><small>Eslatma: ushbu boshlang‘ich panel sozlamalarni saqlaydi; ayrim sozlamalarni frontendda qo‘llash uchun keyingi iteratsiyada API bilan bog‘lash kerak.</small></div>
<div class="box"><h2>✅ Vazifalar qo‘shish</h2><form method="post"><input type="hidden" name="csrf" value="<?=esc($_SESSION['admin_csrf'])?>"><input type="hidden" name="do" value="task_add"><label>Vazifa nomi</label><input name="title" required maxlength="100"><label>Tavsif</label><input name="description" maxlength="250"><label>Mukofot (tanga)</label><input name="reward" type="number" min="0" max="10000000" value="10" required><button>Vazifa qo‘shish</button></form>
<h2>Mavjud vazifalar</h2><div class="scroll"><table><tr><th>ID</th><th>Vazifa</th><th>Mukofot</th><th>Holat</th><th>Amal</th></tr><?php foreach($tasks as $t):?><tr><td><?=esc($t['id'])?></td><td><?=esc($t['title'])?><br><small><?=esc($t['description'])?></small></td><td><?=esc($t['reward'])?></td><td><?=$t['active']?'Faol':'O‘chiq'?></td><td><form method="post"><input type="hidden" name="csrf" value="<?=esc($_SESSION['admin_csrf'])?>"><input type="hidden" name="do" value="task_toggle"><input type="hidden" name="id" value="<?=esc($t['id'])?>"><button class="secondary">Yoqish/o‘chirish</button></form></td></tr><?php endforeach?></table></div></div>
<div class="box"><h2>👥 Foydalanuvchilar (jami: <?=$total?>)</h2><small>Oxirgi 100 ta hisob ko‘rsatilgan. Balansni o‘zgartirishda ehtiyot bo‘ling.</small><div class="scroll"><table><tr><th>ID</th><th>Telegram ID</th><th>Ism / username</th><th>Tanga</th><th>Olmos</th><th>Saqlash</th></tr><?php foreach($users as $u):?><tr><form method="post"><input type="hidden" name="csrf" value="<?=esc($_SESSION['admin_csrf'])?>"><input type="hidden" name="do" value="user_update"><input type="hidden" name="id" value="<?=esc($u['id'])?>"><td><?=esc($u['id'])?></td><td><?=esc($u['telegram_id'])?></td><td><?=esc($u['first_name'])?><br>@<?=esc($u['username'])?></td><td><input name="coins" type="number" min="0" max="2000000000" value="<?=esc($u['coins'])?>"></td><td><input name="diamonds" type="number" min="0" max="2000000000" value="<?=esc($u['diamonds'])?>"></td><td><button>Saqlash</button></td></form></tr><?php endforeach?></table></div></div>
<div class="box"><h2>🔐 Xavfsizlik</h2><ul><li>setup.php ni sozlashdan keyin o‘chiring.</li><li>config.php va data/ papkasini GitHub'ga yuklamang.</li><li>Faqat HTTPS ishlating va kuchli, noyob admin paroli qo‘ying.</li><li>Balanslar demo ballari; pul yechish/to‘lov funksiyasi ulanmagan.</li></ul></div>
<?php endif?></main></body></html>
