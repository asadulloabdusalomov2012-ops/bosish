<?php
declare(strict_types=1);
session_start();
$cfgPath=__DIR__.'/config.php';
if(is_file($cfgPath)){http_response_code(403);exit('Sozlash allaqachon bajarilgan. Xavfsizlik uchun setup.php faylini serverdan o‘chiring.');}
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 $password=(string)($_POST['password']??'');$confirm=(string)($_POST['confirm']??'');$token=trim((string)($_POST['token']??''));
 if(strlen($password)<12)$error='Admin paroli kamida 12 belgidan iborat bo‘lsin.';
 elseif($password!==$confirm)$error='Parollar mos kelmadi.';
 elseif($token==='' || strpos($token,':')===false)$error='BotFather bergan Telegram bot tokenini kiriting.';
 else{
  $data="<?php\nreturn ".var_export(['admin_password_hash'=>password_hash($password,PASSWORD_DEFAULT),'telegram_bot_token'=>$token,'db_path'=>__DIR__.'/data/yummi.sqlite','allowed_origin'=>''],true).";\n";
  if(file_put_contents($cfgPath,$data,LOCK_EX)===false)$error='config.php yozilmadi. Hosting ruxsatlarini tekshiring.';
  else{ @chmod($cfgPath,0600); header('Location: admin.php?welcome=1');exit; }
 }
}
?><!doctype html><html lang="uz"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Yummi Tanga sozlash</title>
<style>body{font:16px Arial;background:#102d3a;color:white;padding:20px}.box{max-width:480px;margin:30px auto;padding:22px;background:#1c4652;border-radius:18px}input{width:100%;padding:12px;margin:8px 0 16px;box-sizing:border-box;border-radius:8px;border:0}button{padding:12px 18px;background:#ffd84e;border:0;border-radius:8px;font-weight:bold}small{color:#d2e9ef;line-height:1.5}</style>
<div class="box"><h2>Yummi Tanga — birinchi sozlash</h2><p>Admin paroli va Telegram bot tokenini kiriting. Bu sahifa faqat bir marta ishlaydi.</p><?php if($error):?><p style="color:#ffb4b4"><?=htmlspecialchars($error)?></p><?php endif?><form method="post"><label>Telegram bot tokeni (BotFather)</label><input name="token" required autocomplete="off"><label>Admin paroli (kamida 12 belgi)</label><input type="password" name="password" minlength="12" required autocomplete="new-password"><label>Parolni takrorlang</label><input type="password" name="confirm" minlength="12" required autocomplete="new-password"><button>Saqlash va admin paneliga kirish</button></form><small>Token va parolni hech kimga yubormang. Sozlashdan so‘ng setup.php ni o‘chiring.</small></div></html>
