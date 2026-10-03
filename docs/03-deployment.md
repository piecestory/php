# النشر والتشغيل — Hostinger Web Business

## أ) ما أحتاجه من صاحب المشروع (مرة واحدة)

| البند | لماذا |
|---|---|
| اسم النطاق النهائي (مثل `piecenstory.com`) وأين هو مسجّل | لربط الموقع وإصدار شهادة SSL |
| صلاحية دخول hPanel، أو تفعيل SSH وإضافة مفتاح النشر الذي أرسله لك | لتجهيز الخادم والنشر الآلي |
| مستودع GitHub خاص باسم المتجر (أو صلاحية إنشائه) | النشر الآلي يمر عبره بعد نجاح كل الاختبارات |
| صندوق البريد `info@` على Hostinger | لإرسال تأكيدات الطلبات والتنبيهات |
| قرار موعد الإطلاق، وهل نبدأ بنطاق تجريبي (staging) محمي بكلمة مرور | للمراجعة قبل الفتح للعامة |

> بدون حساب دفع إلكتروني (Moyasar) يعمل الموقع بالحجز والدفع في المعرض فقط، ويظهر الدفع الإلكتروني تلقائيًا عند تفعيل الحساب.

## ب) تجهيز الخادم (مرة واحدة)

1. hPanel → Advanced → PHP Configuration: PHP 8.4، وتفعيل `opcache` و`intl` و`gd` و`zip` و`exif` و`fileinfo`.
2. hPanel → Databases: إنشاء قاعدة ومستخدم بصلاحيات على هذه القاعدة فقط.
3. hPanel → Emails: إنشاء `info@<domain>`؛ وتفعيل SPF وDKIM وDMARC من إعدادات النطاق (DNS).
4. hPanel → Security → SSL: شهادة Let's Encrypt + إجبار HTTPS.
5. hPanel → Advanced → SSH Access: تفعيل SSH وإضافة المفتاح العام للنشر.
6. هيكل المجلدات تحت `~/domains/<domain>/`:
   ```
   releases/        نسخ الموقع (آخر 3)
   shared/.env      من .env.production.example (chmod 600)
   shared/storage/  الصور المرفوعة والسجلات والنسخ الاحتياطية
   current  →  releases/<آخر نسخة>
   public_html  →  current/public
   ```
   ```bash
   cd ~/domains/<domain>
   mkdir -p releases shared
   cp <من المستودع>/.env.production.example shared/.env && chmod 600 shared/.env   # ثم تعبئة القيم
   mv public_html public_html.hostinger && ln -s current/public public_html          # بعد أول نشر
   ```
7. GitHub → Settings → Environments: بيئتا `staging` و`production` (الثانية بموافقة يدوية)، وفي كل منهما:
   الأسرار `SSH_HOST`، `SSH_PORT` (65002)، `SSH_USER`، `SSH_PRIVATE_KEY`، `DEPLOY_PATH`؛ والمتغير `APP_URL`.
8. hPanel → Advanced → Cron Jobs، مهمة واحدة كل دقيقة:
   ```
   cd ~/domains/<domain>/current && php artisan schedule:run >> /dev/null 2>&1
   ```
   تشغّل: الطوابير (البريد والرسائل والصور)، إلغاء الحجوزات المنتهية، تذكير الحجوزات، تنظيف السلال، والنسخة الاحتياطية الليلية.

## ج) أول نشر

1. GitHub → Actions → **Deploy** → Run workflow → `staging` (ثم `production`).
   يشغّل كل الفحوص أولًا، ثم يرفع النسخة ويشغّل `deploy/release.sh`، ثم يتحقق من أن الموقع يستجيب؛ وإن لم يستجب يعود للنسخة السابقة.
2. على الخادم، مرة واحدة بعد أول نشر:
   ```bash
   cd ~/domains/<domain>/current
   php artisan db:seed --force          # البيانات المرجعية فقط (التصنيفات، المعارض، الصفحات…) — آمن ولا يكرر
   php artisan admin:create             # حساب المدير (كلمة المرور تُكتب في سطر مخفي)
   ```
   لا تُشغَّل بيانات القطع التجريبية في الإنتاج (الأمر يرفض ذلك تلقائيًا).

## د) قائمة ما قبل الإطلاق

- [ ] `APP_DEBUG=false` و`APP_ENV=production` في `shared/.env` (الموقع يفرض إخفاء الأخطاء في الإنتاج على أي حال).
- [ ] إزالة القطع التجريبية إن كانت قد أُضيفت: `php artisan catalog:remove-demo`.
- [ ] من لوحة الإدارة: بيانات المعرضين، نشر الصفحات المعتمدة (الخصوصية، الشروط، الاسترجاع)، الأسئلة الشائعة، شرائح الرئيسية، القطع الحقيقية بصورها.
- [ ] تجربة: طلب حجز من الجوال، وصول الرسالة والبريد، ظهوره في الإدارة، تسجيل الدفع في المعرض.
- [ ] `https://<domain>/robots.txt` يسمح بالفهرسة، و`/sitemap.xml` يعمل؛ إضافة الموقع إلى Google Search Console.
- [ ] قياس PageSpeed Insights (جوال) للرئيسية وصفحة قطعة؛ الهدف LCP < 2.5 ث (انظر docs/qa/phase-14).
- [ ] ترويسات الأمان: `curl -I https://<domain>` تُظهر CSP وHSTS.
- [ ] نسخة احتياطية يدوية أولى: `php artisan backup:run`.

## هـ) التشغيل اليومي

| المهمة | كيف |
|---|---|
| نشر تحديث | GitHub → Actions → Deploy → staging ثم production |
| الرجوع لنسخة سابقة | `bash ~/domains/<domain>/current/deploy/rollback.sh ~/domains/<domain>` (ثوانٍ؛ الكود فقط) |
| النسخ الاحتياطي | تلقائي 04:30 يوميًا في `shared/storage/app/backups` لمدة 14 يومًا + نسخ Hostinger اليومية؛ عند الفشل يصل بريد تنبيه |
| استعادة القاعدة | `gunzip -c <file>-database.sql.gz \| mysql -u <user> -p <database>` (بعد وضع الموقع في الصيانة) |
| استعادة الصور | فك `<file>-files.zip`: `public/` → `shared/storage/app/public`، `private/` → `shared/storage/app/private` |
| الأخطاء | `shared/storage/logs/laravel-YYYY-MM-DD.log` (14 يومًا)؛ أخطاء الخادم تصل بريدًا (مرة كل 30 دقيقة لكل نوع) |
| تفعيل بوابة دفع | مفاتيح الحساب في `shared/.env` + إضافة عنوان صفحة الدفع إلى `PAYMENT_CHECKOUT_ORIGINS` |
