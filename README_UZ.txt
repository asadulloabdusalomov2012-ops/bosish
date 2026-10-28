YUMMI TANGA — PHP + SQLite demo loyiha
======================================

MUHIM:
- GitHub Pages HTML/CSS/JavaScript fayllarini ko‘rsatadi, PHP ishga tushirmaydi.
- To‘liq versiya uchun PHP 8.1+ va PDO_SQLITE qo‘llaydigan HTTPS hosting kerak.
- Bu loyiha demo tanga/olmos ishlatadi. Haqiqiy pul kiritish/chiqarish yoqilmagan.
- Telegram Mini App ichida ochilishi uchun Telegram bot va Web App URL sozlanishi shart.

FAYLLAR:
index.html       — clicker interfeysi
api.php          — server API; Telegram initData tekshiruvi, tanga/energiya/vazifalar
admin.php        — admin login, sozlamalar, vazifalar, foydalanuvchi balanslari
setup.php        — birinchi sozlash (bir marta ishlating)
config.example.php — namuna konfiguratsiya
.gitignore       — sirli config va ma’lumotlar bazasini GitHub’dan chetlatadi

ANDROID TELEFONDAN O‘RNATISH:
1. ZIP faylni oching.
2. GitHub repository yarating. Kodni GitHub'ga joylashing, lekin config.php va data/ ni aslo joylamang.
3. PHP hosting tanlang (PHP 8.1+, PDO_SQLITE, HTTPS). GitHub Pages o‘zi PHP hosting emas.
4. Hosting File Manager yoki SFTP orqali index.html, api.php, admin.php, setup.php fayllarini hostingning public papkasiga yuklang.
5. Brauzerda https://SIZNING-DOMENINGIZ/setup.php ni oching.
6. BotFather orqali Telegram bot yarating, bot tokenini oling. Tokenni setup sahifasiga kiriting. Admin uchun kamida 12 belgili noyob parol belgilang.
7. Setup muvaffaqiyatli tugagach, hosting File Manager orqali setup.php ni o‘chiring.
8. Admin paneli: https://SIZNING-DOMENINGIZ/admin.php
9. BotFather > /mybots > botingiz > Bot Settings > Menu Button yoki Web App sozlamasida Web App URL sifatida HTTPS domenni kiriting.
10. Mini App'ni Telegram ichida oching. API Telegram Web App initData imzosini serverda tekshiradi.

GITHUB PAGES HAQIDA:
GitHub Pages'da faqat statik demo interfeysni host qilishingiz mumkin; PHP API/admin/backend ishlamaydi. To‘liq backendni alohida PHP hostingda ishlating. Eng sodda va ishonchli variant — index.html, api.php va admin.php ni bitta HTTPS PHP hosting domenida saqlash. Shunda CORS/cookie sozlashga ehtiyoj qolmaydi.
Agar index.html GitHub Pages'da, API boshqa domenda bo‘lsa, API CORS, credential cookie, SameSite=None; Secure va origin ruxsatlarini alohida sozlash kerak; ushbu ZIP bunday cross-origin joylashuv uchun tayyorlanmagan.

XAVFSIZLIK:
- config.php bot tokeni va admin parol hashini saqlaydi; uni GitHub'ga yuklamang.
- Hostingda data/ papkasi web orqali yuklab olinmaydigan bo‘lishi kerak. Mumkin bo‘lsa public_html tashqarisiga joylashtiring va config.php dagi db_path ni o‘zgartiring.
- HTTPS ishlating, admin parolini hech kimga bermang.
- Telegram initData foydalanuvchi identifikatsiyasini tekshiradi. Bot tokeni maxfiy qolishi shart.
- Admin panelidagi dizayn sozlamalarining saqlash qismi mavjud; hozircha barcha dizayn sozlamalari frontendga avtomatik tatbiq etilmagan.
- To‘lov, pul yechish, referral tasdig‘i, reklama/video ko‘rish tasdig‘i ishlab chiqarish uchun alohida server-side tekshiruvlar va tegishli provayder integratsiyasi talab qiladi.
