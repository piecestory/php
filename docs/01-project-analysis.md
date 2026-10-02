# PIECE & STORY — قطعة وقصة
## المرحلة 1: تحليل المشروع (Project Analysis)

الحالة: **معتمد** — القرارات التجارية في القسم 9
التاريخ: 2026-10-02

---

## 0. ملخص تنفيذي

متجر إلكتروني فاخر للتحف والقطع النادرة، عربي أولاً (RTL) مع نسخة إنجليزية (LTR)، يعمل على **Hostinger Web Business + MySQL**.

القرار التقني الأساسي: **Laravel + MySQL + Blade/Livewire + Alpine.js + Tailwind CSS**، مع **Filament** للوحة الإدارة.

السبب باختصار: الاستضافة مشتركة (Shared Hosting) تشغّل PHP بكفاءة، ولا توفّر خادمًا دائمًا (Node server / WebSockets / Redis / Supervisor). Laravel يعمل عليها مباشرة، ويوفّر الأمان (CSRF، Hashing، Validation، Policies) جاهزًا ومُختبَرًا، ويسمح بنقل المشروع لاحقًا إلى VPS أو Cloud دون إعادة كتابة.

---

## 1. Architecture Plan

### 1.1 التقنيات المعتمدة

| الطبقة | القرار | السبب |
|---|---|---|
| Backend | Laravel (أحدث إصدار مستقر يدعمه PHP على Hostinger وقت البدء) | يعمل على Shared Hosting، أمان مدمج، نظام Migrations، Queues، Scheduler |
| Database | MySQL (متوافق أيضًا مع MariaDB 10.6+) | المعتمد في Hostinger. سأتجنب أي ميزة خاصة بإصدار واحد، وأتحقق من الإصدار الفعلي على الخادم في المرحلة 5 |
| Storefront | Blade + Livewire (للأجزاء التفاعلية فقط: السلة، الفلاتر، المفضلة) + Alpine.js | HTML يُولَّد من الخادم = SEO ممتاز وسرعة تحميل أولى عالية، وJavaScript أقل |
| CSS | Tailwind CSS مع Design Tokens + خصائص منطقية (`ms-/me-/ps-/pe-/start/end`) | دعم RTL/LTR من نفس الكود دون نسختين |
| Admin | Filament (مبني على Livewire) بثيم مخصّص بهوية العلامة | لوحة إدارة كاملة بأقل كود، يدعم RTL، والصلاحيات عبر Policies. يمنع تكرار كود CRUD لكل قسم |
| الصلاحيات | spatie/laravel-permission + Laravel Policies | Roles & Permissions قياسية ومُختبَرة |
| الصور | spatie/laravel-medialibrary + GD/Imagick | صور متعددة، ترتيب، صورة رئيسية، تحويل WebP، أحجام متعددة (srcset) |
| سجل التدقيق | spatie/laravel-activitylog | تتبع تغييرات الإدارة (من غيّر ماذا ومتى) |
| البحث | MySQL FULLTEXT + عمود نص مُطبَّع للعربية (أ/إ/آ→ا، ة→ه، ى→ي، إزالة التشكيل) | يعمل على الاستضافة دون خدمة خارجية. نقطة توسع لاحقة: Meilisearch |
| Cache / Session | `database` / `file` | لا يوجد Redis على Shared Hosting. قابل للتبديل بتغيير متغير بيئة فقط |
| Queue | `database` driver يُشغَّل عبر Cron كل دقيقة | لا يوجد Supervisor. الرسائل وتحويل الصور تعمل في الخلفية |
| البريد | SMTP (بريد Hostinger) | متاح ضمن الخطة |
| الاختبارات | Pest (Unit/Feature) + Playwright (E2E، المقاسات، RTL/LTR، لقطات بصرية، فحص Accessibility) | |
| جودة الكود | Laravel Pint + Larastan + `composer audit` / `npm audit` | |
| CI/CD | GitHub Actions: اختبار ← بناء الأصول ← نشر عبر SSH | Hostinger لا يبني أصول الواجهة، لذا تُبنى في CI وتُرفع جاهزة |

**لن أستخدم:** SPA (React/Vue) — يضر SEO ويضيف تعقيدًا دون حاجة. خدمات خارجية مدفوعة غير ضرورية. حزم Modules خارجية (البنية المعيارية ستكون بالتنظيم الداخلي فقط).

### 1.2 شكل البنية (Modular Monolith)

تطبيق واحد منظّم إلى Modules منفصلة منطقيًا. كل Module يملك: Models، Enums، Actions (منطق العمل)، Events، Policies.

```
app/
├── Domain/                 ← منطق العمل (لا يعرف شيئًا عن HTTP)
│   ├── Catalog/            Products, Categories, Collections, Lookups, Search
│   ├── Customers/          Profiles, Addresses
│   ├── Wishlist/
│   ├── Cart/
│   ├── Orders/             Order, Items, Status machine, History
│   ├── Checkout/           تجميع الطلب، التحقق، حجز المخزون
│   ├── Payments/           PaymentGateway interface + Drivers
│   ├── Shipping/           Methods, Rates, Shipments
│   ├── Inventory/          Stock movements, Reservations
│   ├── Auctions/           Auctions, Lots (أساس فقط)
│   ├── PersonalFinder/
│   ├── Blog/
│   ├── Content/            Pages, Hero slides, Homepage blocks
│   ├── Notifications/
│   └── Settings/
├── Http/Controllers/
│   ├── Storefront/
│   └── Account/
├── Livewire/               مكونات تفاعلية فقط
├── Filament/               لوحة الإدارة
└── Support/                أدوات مشتركة (Money, Localization, SEO)
```

**قواعد الفصل:**
- Controllers رفيعة: تستقبل الطلب ← Form Request للتحقق ← Action ← Response.
- منطق العمل في Actions فقط، يُستخدم نفسه من الواجهة ومن الإدارة (لا تكرار).
- التواصل بين Modules عبر Events (مثال: `OrderPlaced` ← إرسال بريد + خصم مخزون). هذا ما يسمح بنقل أي Module لاحقًا.
- المزادات لا تلمس منطق المنتج العادي؛ ترتبط بالمنتج عبر `auction_lots.product_id` فقط.

### 1.3 نقاط التوسع (Extension Points) — تصميم فقط دون تنفيذ

| النقطة | كيف صُممت |
|---|---|
| مزود دفع آخر | `PaymentGateway` interface — تغيير المزود = Driver جديد + متغير بيئة |
| شركة شحن (API) | `ShippingProvider` interface — النسخة الأولى: رقم تتبع يدوي |
| المزايدة الحية | جداول `bids`/`settlements` مصممة في الخطة، لا تُنشأ قبل الطلب. لاحقًا: Polling على Hostinger أو WebSockets عند الانتقال لـ VPS |
| محرك بحث | واجهة بحث موحدة — التبديل إلى Meilisearch دون تغيير الصفحات |
| Redis / Queue workers | تغيير متغيرات بيئة فقط عند الانتقال لخادم أقوى |
| عملات متعددة | الأسعار تُخزَّن مع رمز العملة؛ النسخة الأولى SAR فقط (بانتظار قرارك) |

### 1.4 اللغات والروابط

- العربية افتراضية على `/` ، الإنجليزية على `/en/...`
- Slug منفصل لكل لغة (`/store/ساعة-كلاسيكية-فرنسية` و `/en/store/french-classic-clock`)
- `hreflang` + `canonical` لكل صفحة.
- الحقول المترجمة كأعمدة صريحة (`name_ar`, `name_en`) وليس JSON — لأنها تحتاج فهرسة وبحث FULLTEXT.

---

## 2. Site Map

```
/                                   الرئيسية
/store                              المتجر — كل المنتجات (فلاتر + ترتيب + ترقيم صفحات)
/store/{category}                   تحف وأنتيك | أثاث كلاسيك | نجف وإضاءة | لوحات فنية | أواني منزلية فاخرة | قطع نادرة
/product/{slug}                     تفاصيل المنتج (معرض صور، القصة، المواصفات، منتجات مشابهة)
/search?q=                          البحث
/collections                        المجموعات
/collections/{slug}                 مجموعة
/auctions                           المزادات
/auctions/{slug}                    مزاد + القطع (Lots)
/personal-finder                    الباحث الشخصي (نموذج طلب)
/blog , /blog/{slug}                المدونة
/about                              من نحن
/services                           الخدمات
/contact                            تواصل معنا
/cart                               السلة
/checkout                           إتمام الطلب
/checkout/confirmation/{order}      تأكيد الطلب
/account                            حسابي: الطلبات، العناوين، المفضلة، طلبات الباحث الشخصي، البيانات
/login , /register , /forgot-password
/policies/{slug}                    الشحن | الإرجاع والاستبدال | الخصوصية | الشروط والأحكام
/admin                              لوحة الإدارة (للموظفين فقط)
/sitemap.xml , /robots.txt
صفحات الأخطاء: 403 · 404 · 419 · 429 · 500 · 503 (صيانة)
```

**ملاحظة على التصنيفات:** التصميم المرجعي يعرض "تحف وأنتيك" و"قطع أثرية" كتصنيفين، بينما خريطة الموقع تذكر "Antiques" و"Rare Pieces". سأعتمد خريطة الموقع، وأجعل "قطع نادرة" تصنيفًا مستقلًا **و**علامة `is_rare` على المنتج (قطعة في أي تصنيف يمكن أن تكون نادرة). التصنيفات تُدار من لوحة الإدارة، فتغيير الأسماء لا يحتاج برمجة.

**عناصر في التصميم المرجعي غير موجودة في خريطة الموقع** — بانتظار قرارك (القسم 9): الأسئلة الشائعة، تتبع الطلب، مبدّل العملة.

---

## 3. Module Map

| Module | مسؤوليته | يعتمد على | المرحلة |
|---|---|---|---|
| Core / Support | اللغات، العملة، SEO، الإعدادات، الأخطاء | — | 5 |
| Authentication | تسجيل، دخول، استعادة كلمة المرور، تحقق البريد، Rate limiting | Core | 5 |
| Catalog | المنتجات، التصنيفات، المجموعات، الأصل/الحقبة/المادة، البحث | Core, Media | 6–7 |
| Media | رفع الصور، التحقق من النوع، تحويل WebP، الأحجام | Core | 6 |
| Inventory | المخزون، الحجز المؤقت عند الدفع، سجل الحركات | Catalog | 8–9 |
| Wishlist | المفضلة (ضيف ← تُدمج عند الدخول) | Catalog, Auth | 8 |
| Cart | السلة (ضيف وعضو)، التحقق من التوفر والسعر | Catalog, Inventory | 8 |
| Checkout | تجميع الطلب، العنوان، الشحن، الضريبة، بدء الدفع | Cart, Shipping, Payments | 9 |
| Payments | `PaymentGateway`، Sandbox، Webhooks مع منع التكرار (Idempotency) | — | 9 |
| Shipping | طرق وأسعار الشحن، الشحنات ورقم التتبع | — | 9 |
| Orders | دورة حياة الطلب، سجل الحالات، الفاتورة | Checkout, Payments | 9–11 |
| Customers | الحساب، العناوين، سجل الطلبات | Auth, Orders | 10 |
| Admin | كل شاشات الإدارة، Dashboard، Roles | الكل | 11 |
| Personal Finder | نموذج الطلب، صورة مرجعية، المتابعة من الإدارة | Catalog, Notifications | 12 |
| Auctions | المزادات والقطع (عرض فقط في النسخة الأولى) | Catalog | 13 |
| Blog | المقالات | Media | 11 |
| Content | الصفحات الثابتة، شرائح Hero، أقسام الرئيسية، السياسات | Media | 11 |
| Notifications | بريد للعميل والإدارة (طلب جديد، تغير الحالة، طلب باحث شخصي…) | Queue | 9–12 |
| Settings | بيانات المتجر، الضريبة، الشحن، روابط التواصل | Core | 5, 11 |

### دورة حياة الطلب (State Machine)

```
Pending ──► Confirmed ──► Processing ──► Shipped ──► Delivered
   │            │              │                         │
   └────────────┴──────────────┴──► Cancelled            └──► Refunded
```
- حالة الطلب منفصلة عن حالة الدفع (`unpaid / authorized / paid / failed / refunded / partially_refunded`).
- الانتقالات المسموحة فقط معرّفة في كود واحد (Enum)؛ أي انتقال غير مسموح يُرفض.
- كل تغيير يُسجَّل في `order_status_histories` (من، إلى، بواسطة، ملاحظة، الوقت).
- الإلغاء يعيد المخزون تلقائيًا؛ الاسترجاع المالي يمر عبر طبقة الدفع.

---

## 4. Database Entity Plan

**مبادئ عامة:** `BIGINT UNSIGNED` للمفاتيح، `utf8mb4_unicode_ci`، المبالغ `DECIMAL(12,2)` (لا Float أبدًا)، `created_at/updated_at` لكل الجداول، `deleted_at` (Soft delete) للمنتجات والتصنيفات والمقالات فقط، Foreign Keys بقيود صريحة (`restrict` للبيانات المالية، `set null` لما يجب أن يبقى تاريخيًا، `cascade` للعناصر التابعة فقط).

### 4.1 الهوية والعملاء
| الجدول | أهم الحقول | القيود والفهارس |
|---|---|---|
| `users` | name, email, phone, password, locale, email_verified_at, last_login_at | UNIQUE(email), UNIQUE(phone) nullable |
| `roles`, `permissions`, `model_has_roles`, `role_has_permissions`… | (spatie) | — |
| `addresses` | user_id, recipient_name, phone, country_code, city, district, street, building_no, postal_code, short_address (العنوان الوطني), is_default | FK user cascade, INDEX(user_id) |
| `sessions`, `password_reset_tokens` | (Laravel) | — |

العملاء والموظفون في جدول واحد؛ الدخول للإدارة مشروط بصلاحية `access_admin` + Policy لكل قسم.

### 4.2 الكتالوج
| الجدول | أهم الحقول | القيود والفهارس |
|---|---|---|
| `categories` | parent_id, name_ar/en, slug_ar/en, description_ar/en, sort_order, is_active, meta_title_ar/en, meta_description_ar/en | UNIQUE(slug_ar), UNIQUE(slug_en), INDEX(parent_id, is_active, sort_order) |
| `products` | category_id, sku, name_ar/en, slug_ar/en, description_ar/en, story_ar/en, price, sale_price, sale_starts_at, sale_ends_at, stock_quantity, availability (enum), origin_id, era_id, condition (enum), width_cm, height_cm, depth_cm, weight_kg, is_rare, is_featured, status (draft/published/archived), published_at, meta_* , search_text | UNIQUE(sku), UNIQUE(slug_ar), UNIQUE(slug_en), INDEX(status, published_at), INDEX(category_id, status), INDEX(is_featured), FULLTEXT(search_text), CHECK منطقي: sale_price < price (في Validation) |
| `origins` | name_ar/en, slug | UNIQUE(slug) — مثل: فرنسا، الصين، الدولة العثمانية |
| `eras` | name_ar/en, slug, year_from, year_to, sort_order | UNIQUE(slug) — مثل: القرن التاسع عشر، آرت ديكو |
| `materials` + `material_product` | name_ar/en / (material_id, product_id) | PK مركب — القطعة قد تكون من أكثر من مادة |
| `collections` + `collection_product` | name_ar/en, slug_ar/en, description, cover, is_active, sort_order / (collection_id, product_id, sort_order) | PK مركب |
| `media` | (medialibrary) model_type/id, collection, file, mime, size, order_column, custom_properties (alt_ar/en), conversions | INDEX(model_type, model_id) |

- **الصورة الرئيسية** = أول صورة في الترتيب (`order_column`)، فلا يمكن أن يكون هناك صورتان رئيسيتان أو منتج بلا رئيسية مع وجود صور.
- **التحويلات:** thumb (400px)، card (800px)، large (1600px) بصيغة WebP + srcset، وصورة احتياطية موحدة عند غياب الصورة.
- `availability`: available / reserved / sold / on_request — مناسب لقطع فريدة.
- `search_text`: نص عربي/إنجليزي مُطبَّع يُحدَّث تلقائيًا عند الحفظ.
- **ربط المزادات مستقبلًا:** عبر `auction_lots.product_id` دون تعديل جدول المنتجات.

### 4.3 المفضلة والسلة والمخزون
| الجدول | أهم الحقول | القيود |
|---|---|---|
| `wishlist_items` | user_id, product_id | UNIQUE(user_id, product_id), FK cascade |
| `carts` | user_id nullable, token (للضيف), expires_at | UNIQUE(token), INDEX(user_id) |
| `cart_items` | cart_id, product_id, quantity | UNIQUE(cart_id, product_id) |
| `stock_reservations` | product_id, order_id, quantity, expires_at | INDEX(product_id, expires_at) — حجز مؤقت أثناء الدفع (15 دقيقة) ثم يُحرَّر تلقائيًا |
| `inventory_movements` | product_id, change (+/-), reason (enum), reference_type/id, user_id | سجل لكل تغيير مخزون |

السعر **لا يُخزَّن** في السلة؛ يُقرأ من المنتج دائمًا حتى لا يدفع العميل سعرًا قديمًا. يُثبَّت السعر فقط عند إنشاء الطلب.

### 4.4 الطلبات والدفع والشحن
| الجدول | أهم الحقول | القيود |
|---|---|---|
| `orders` | number (مثل PS-2026-000123), user_id nullable, email, phone, locale, status, payment_status, currency, subtotal, discount_total, shipping_total, tax_total, grand_total, shipping_* (نسخة من العنوان وقت الطلب), shipping_method_id, customer_note, placed_at | UNIQUE(number), INDEX(user_id), INDEX(status, placed_at), FK user `set null` |
| `order_items` | order_id, product_id nullable, sku, name_ar, name_en, unit_price, quantity, line_total | FK order cascade, FK product `set null` (الطلب يبقى صحيحًا حتى لو حُذف المنتج) |
| `order_status_histories` | order_id, from_status, to_status, changed_by, note | INDEX(order_id) |
| `payments` | order_id, provider, provider_reference, method, amount, currency, status, failure_reason, paid_at, metadata (JSON لرد المزود فقط — ليس بيانات علائقية) | UNIQUE(provider, provider_reference), FK order `restrict` |
| `payment_webhook_events` | provider, event_id, payload_hash, processed_at | UNIQUE(provider, event_id) — يمنع تنفيذ نفس الإشعار مرتين |
| `refunds` | payment_id, amount, reason, status, provider_reference, processed_by | FK `restrict` |
| `shipping_methods` | name_ar/en, code, base_rate, free_threshold, is_active, sort_order | UNIQUE(code) |
| `shipping_zones` + `shipping_rates` | (حسب قرار الشحن — القسم 9) | — |
| `shipments` | order_id, carrier, tracking_number, shipped_at, delivered_at | INDEX(order_id) |

لا تُخزَّن بيانات البطاقات إطلاقًا؛ الدفع يتم عند مزود الدفع (Hosted / Tokenized).

### 4.5 الباحث الشخصي
| الجدول | أهم الحقول |
|---|---|
| `finder_requests` | reference (رقم مرجعي)، user_id nullable، name، email، phone، category_id nullable، description، budget_min، budget_max، preferences، status (new / reviewing / sourcing / offer_sent / closed / cancelled)، assigned_to، admin_notes، contact_consent_at |
| `finder_request_status_histories` | request_id, from, to, changed_by, note |
| الصور المرجعية | عبر `media` (حد أقصى للعدد والحجم، JPEG/PNG/WebP فقط، تحقق MIME حقيقي) |

### 4.6 المزادات (أساس فقط)
| الجدول | الحالة |
|---|---|
| `auctions` (title_ar/en, slug, description, starts_at, ends_at, status: draft/scheduled/live/ended/cancelled, cover) | يُنشأ في المرحلة 13 |
| `auction_lots` (auction_id, product_id nullable, lot_number, title, starting_price, reserve_price, estimate_low/high, status) | يُنشأ في المرحلة 13 — UNIQUE(auction_id, lot_number) |
| `bids`, `auction_registrations`, `settlements` | **مصممة على الورق فقط** — لا تُنشأ قبل اعتماد المزايدة الفعلية |

### 4.7 المحتوى والمدونة والإعدادات
| الجدول | أهم الحقول |
|---|---|
| `posts` | author_id, title_ar/en, slug_ar/en, excerpt, body, status, published_at, meta_* |
| `pages` | key (about / services / shipping-policy / returns-policy / privacy / terms), title_ar/en, body_ar/en, meta_* |
| `hero_slides` | title, subtitle, cta_label, cta_url (لكل لغة), sort_order, is_active, starts_at, ends_at + صورة (desktop/mobile) |
| `home_blocks` | key, ترتيب وتفعيل أقسام الرئيسية (التصنيفات المميزة، أحدث القطع، بطاقة المزادات، بطاقة الباحث الشخصي، شريط المزايا) |
| `contact_messages` | name, email, phone, subject, message, status, handled_by |
| `settings` | group, key, value, type — UNIQUE(group, key) |
| `activity_log`, `jobs`, `failed_jobs`, `cache`, `notifications` | (Laravel/spatie) |

**عدد الجداول التقريبي في النسخة الأولى:** ~40 (منها ~12 جداول نظام قياسية).

---

## 5. Design System Plan

### 5.1 تحليل التصميم المرجعي

**ما يجب الحفاظ عليه:**
- شريط علوي داكن رفيع (معلومات + لغة/عملة + روابط خدمية).
- رأس عاجي: الشعار يمين، القائمة في المنتصف، البحث، ثم الحساب/المفضلة/السلة يسارًا.
- Hero بعرض كامل، صورة داكنة دافئة، العنوان يمين بخط كبير، فاصل زخرفي ذهبي، زر برونزي.
- شريط التصنيفات (6 بطاقات) **يتداخل** مع أسفل الـ Hero داخل لوحة بيضاء — هذه أقوى لمسة في التصميم وسأحتفظ بها.
- "أحدث القطع" + بطاقتا ترويج (المزادات داكنة، الباحث الشخصي فاتحة).
- شريط المزايا (5 عناصر بأيقونات خطية).

**ما سأحسّنه (دون تغيير الاتجاه):**
| المشكلة في المرجع | التحسين |
|---|---|
| بطاقات المنتجات صغيرة ومضغوطة بجانب البطاقتين الترويجيتين | قسم "أحدث القطع" بعرض كامل بصفوف أوسع؛ البطاقتان الترويجيتان في صف مستقل تحته |
| ظلال وحدود باهتة جدًا تجعل البطاقات "تذوب" | حدود دقيقة بلون دافئ بدل الظلال، مع ظل خفيف عند المرور فقط |
| روابط ثانوية (عرض الكل) بتباين منخفض | تباين يحقق WCAG AA |
| أيقونة زر "اطلب الآن" غير واضحة المعنى | أيقونة سهم متسقة مع بقية الأزرار |
| تكرار "تسوق الآن" تحت كل تصنيف | يبقى، لكن البطاقة كاملة قابلة للنقر بدل النص الصغير فقط |
| غياب سلوك الجوال | تصميم جوال مستقل (5.4) |

### 5.2 Design Tokens

| Token | القيمة | الاستخدام |
|---|---|---|
| `ivory` | `#F7F2EA` | خلفية الصفحة |
| `paper` | `#FCFAF6` | البطاقات واللوحات |
| `linen` | `#EEE6DA` | خلفيات ثانوية، حقول الإدخال |
| `line` | `#E3D9CB` | الحدود والفواصل |
| `ink` | `#1C1612` | النصوص الرئيسية، الشريط العلوي، البطاقات الداكنة |
| `ink-soft` | `#5E544B` | النصوص الثانوية (تباين ≥ 4.5:1 على العاجي) |
| `bronze` | `#9A6A36` | الأزرار الرئيسية والروابط |
| `bronze-deep` | `#7E5428` | حالة Hover/Active |
| `gold` | `#C9A46A` | الزخارف والفواصل فقط (لا للنصوص الصغيرة — تباينه منخفض) |
| `success / warning / danger` | درجات مطفأة متناسقة مع الهوية | رسائل الحالة |

- الزوايا: 2–6px (فخامة كلاسيكية، لا زوايا دائرية كبيرة).
- المسافات: مقياس 4px. الحاوية القصوى ~1360px مع هامش 16px على الجوال.
- الظلال: مستويان فقط.
- الحركة: انتقالات 150–250ms للـ Hover والقوائم فقط، واحترام `prefers-reduced-motion`. لا حركات مبالغ فيها، لا Popups.

### 5.3 الخطوط (مستضافة ذاتيًا — بدون Google Fonts وقت التشغيل للأداء والخصوصية)

| الاستخدام | العربية | الإنجليزية |
|---|---|---|
| العناوين | **Alexandria** (أوزان 300–500) — حديث وأنيق، قريب جدًا من عناوين المرجع | **Cormorant Garamond** — طابع دور المزادات الأوروبية |
| النصوص والواجهات | **IBM Plex Sans Arabic** | **IBM Plex Sans** (متناسق مع العربي) |
| الأرقام والأسعار | أرقام لاتينية (SAR 1,850) كما في المرجع | |

الشعار: سأستخدم ملف الشعار الرسمي (SVG) — لن أعيد رسمه (انظر القسم 9).

### 5.4 المكونات (Blade Components — كل مكون مرة واحدة ويُعاد استخدامه)

`button` (primary / secondary / ghost / dark) · `icon` (مجموعة أيقونات خطية واحدة) · `ornament-divider` (الفاصل الذهبي SVG) · `section-heading` · `product-card` · `category-tile` · `promo-card` · `price` (يعرض سعر التخفيض تلقائيًا) · `badge` (نادر / مُباع / محجوز) · `breadcrumbs` · `responsive-image` · `form.*` (input / select / textarea / file / checkbox مع label وخطأ) · `pagination` · `empty-state` · `alert` · `modal` (للتأكيد فقط) · `drawer` (قائمة الجوال + السلة المصغرة) · `gallery` (معرض صور المنتج مع تكبير) · `trust-bar`.

### 5.5 السلوك على الأجهزة

| العنصر | الجوال (320–430) | التابلت (768–1024) | سطح المكتب (1280+) |
|---|---|---|---|
| الرأس | شعار + بحث (أيقونة) + سلة؛ القائمة في Drawer | مثل الجوال مع بحث ظاهر | كامل كما في المرجع |
| Hero | صورة مخصصة للجوال (قص عمودي)، نص أسفل الصورة | نص فوق الصورة | كما في المرجع |
| التصنيفات | تمرير أفقي بـ 2.5 بطاقة ظاهرة (يوحي بوجود المزيد) | 3 أعمدة | 6 أعمدة متداخلة مع Hero |
| المنتجات | عمودان | 3 أعمدة | 4–5 أعمدة |
| البطاقتان الترويجيتان | فوق بعض | جنبًا إلى جنب | جنبًا إلى جنب |
| شريط المزايا | شبكة 2×3 | 3 + 2 | 5 في صف |
| الفلاتر في المتجر | Bottom sheet | Drawer جانبي | عمود جانبي ثابت |

---

## 6. Development Roadmap

كل مرحلة تنتهي بـ: **BUILD → TEST → QA → FIX → RETEST** ثم تقرير مختصر لك. لا انتقال مع مشكلة CRITICAL أو HIGH مفتوحة.

| # | المرحلة | المخرجات | بوابة الاجتياز |
|---|---|---|---|
| 1 | تحليل المشروع | هذا المستند | **اعتمادك** |
| 2 | Architecture | هيكل المشروع، ADRs (سجل القرارات)، معايير الكود | مراجعة ذاتية |
| 3 | Database | Migrations + Models + علاقات + Factories + بيانات تجريبية **معلَّمة كتجريبية** للتطوير فقط | كل Migrations تعمل ذهابًا وإيابًا، اختبارات القيود |
| 4 | Design System | Tokens، الخطوط، المكونات، صفحة مرجعية للمكونات (بيئة التطوير فقط) | لقطات RTL/LTR على كل المقاسات — **أعرضها عليك** |
| 5 | Core Application | اللغات، الإعدادات، المصادقة، صفحات الأخطاء، الرأس والتذييل، الرئيسية | اختبارات المصادقة، لقطات الصفحة الرئيسية مقابل المرجع — **أعرضها عليك** |
| 6 | Catalog | التصنيفات، المتجر، الفلاتر، الترتيب، البحث العربي، المجموعات | اختبارات البحث والفلاتر، لا N+1 |
| 7 | Product Pages | صفحة المنتج، المعرض، القصة، المواصفات، Schema | E2E + لقطات |
| 8 | Cart / Wishlist | السلة والمفضلة (ضيف + عضو + الدمج) | E2E كامل |
| 9 | Checkout + الحجز المسبق | العنوان، الشحن، الضريبة، الدفع Sandbox، الحجز/العربون، تأكيد الطلب، البريد | E2E لمسار الشراء الكامل + فشل الدفع + Webhooks مكررة |
| 10 | Customer Account | الطلبات، العناوين، المفضلة، البيانات | اختبارات عزل البيانات (عميل لا يرى بيانات غيره) |
| 11 | Admin | كل أقسام الإدارة، الأدوار، المحتوى، المدونة، Dashboard | اختبارات الصلاحيات لكل دور |
| 12 | Personal Finder + بيع منتجات الغير | نموذجا الطلب، الرفع الآمن، المراجعة والموافقة من الإدارة، الإشعارات | اختبارات الرفع والتحقق |
| 13 | Auctions Foundation | صفحات المزادات والقطع + إدارتها (حسب قرارك) | — |
| 14 | SEO / Performance | Sitemap، Schema، OG، Cache، ضغط الأصول، ميزانيات الأداء | Lighthouse ≥ 90 للأداء على الجوال للصفحات الرئيسية |
| 15 | Security | مراجعة شاملة، Headers، Rate limits، فحص الثغرات | لا ثغرات CRITICAL/HIGH |
| 16 | Testing | استكمال التغطية، اختبارات التكامل | كل الاختبارات ناجحة |
| 17 | QA | QA شامل + Visual QA | Critical = 0، High = 0 |
| 18 | Production | النشر على Hostinger، النسخ الاحتياطي، التوثيق | **FINAL QA REPORT** |

---

## 7. QA Strategy

### 7.1 مستويات الاختبار
| المستوى | الأداة | يغطي |
|---|---|---|
| Unit | Pest | حسابات المال والضريبة، انتقالات حالة الطلب، تطبيع البحث العربي، التحقق |
| Feature | Pest | كل Route: الاستجابة، التحقق، الصلاحيات، رسائل الخطأ، اللغتين |
| Integration | Pest + Sandbox | الدفع، Webhooks، البريد، Queue، الرفع |
| E2E | Playwright | التصفح ← البحث ← المفضلة ← السلة ← الدفع ← الحساب ← الإدارة |
| Responsive | Playwright | 320 · 375 · 390 · 430 · 768 · 1024 · 1280 · 1440 · 1920 × (AR/RTL + EN/LTR) |
| Visual Regression | لقطات Playwright | مقارنة كل تغيير مع اللقطة المعتمدة |
| Accessibility | axe-core داخل Playwright | WCAG 2.1 AA: التباين، Labels، Focus، لوحة المفاتيح |
| Performance | Lighthouse CI + عدّاد الاستعلامات | LCP < 2.5s، CLS < 0.1، منع N+1 (إيقاف Lazy loading في التطوير) |
| Static | Larastan + Pint | أخطاء الأنواع، أسلوب موحد |
| Security | `composer audit`، `npm audit`، اختبارات الصلاحيات، فحص Headers | |

### 7.2 تصنيف المشاكل
- **CRITICAL:** فقدان بيانات، ثغرة أمنية، دفع خاطئ، تعطل الشراء أو الموقع.
- **HIGH:** وظيفة أساسية لا تعمل، كسر في RTL/LTR أو الجوال في صفحة رئيسية، خطأ في الصلاحيات.
- **MEDIUM:** خلل بصري واضح، أداء دون الميزانية، مشكلة Accessibility غير حرجة.
- **LOW:** تفاصيل تجميلية بسيطة.

### 7.3 قواعد التقرير
- لكل بند: **PASS / FAIL / BLOCKED / NOT TESTED** — لا PASS دون تشغيل فعلي.
- كل تقرير يتضمن أرقام الاختبارات الفعلية (عدد الناجح/الفاشل) ولقطات الشاشة حيث يلزم.
- ملف QA Checklist حي يُحدَّث بعد كل مرحلة.

---

## 8. Deployment Strategy — Hostinger Web Business + MySQL

### 8.1 البنية على الخادم
```
~/domains/<domain>/
├── app/                  ← كود Laravel (خارج المجلد العام — لا يمكن الوصول له من المتصفح)
│   ├── .env              ← الأسرار هنا فقط (صلاحيات 600، خارج Git)
│   └── storage/
└── public_html  →  رابط إلى app/public
```

### 8.2 خطوات النشر (آلية عبر GitHub Actions)
1. تشغيل كل الاختبارات — أي فشل يوقف النشر.
2. بناء أصول الواجهة (CSS/JS) وضغطها.
3. رفع الإصدار عبر SSH إلى مجلد إصدار جديد.
4. `composer install --no-dev --optimize-autoloader`
5. وضع الصيانة ← `migrate --force` ← تخزين الإعدادات والمسارات والقوالب مؤقتًا ← تبديل الرابط للإصدار الجديد ← إنهاء وضع الصيانة.
6. الاحتفاظ بآخر 3 إصدارات للرجوع السريع (Rollback).

### 8.3 الإعداد
| البند | القرار |
|---|---|
| PHP | أحدث إصدار متاح في hPanel ومتوافق مع Laravel، مع `opcache` |
| MySQL | قاعدة + مستخدم بصلاحيات على قاعدة المتجر فقط |
| Cron | مهمة واحدة كل دقيقة: `schedule:run` — تشغّل الـ Queue وتنظيف الحجوزات المنتهية والسلات القديمة والنسخ الاحتياطي |
| Queue | `database` + `queue:work --stop-when-empty --max-time=50` من الـ Scheduler |
| Cache / Session | `file` / `database`؛ Cookies بـ `Secure` و `HttpOnly` و `SameSite=Lax` |
| Storage | `storage:link` للصور العامة؛ مرفقات الباحث الشخصي في تخزين **خاص** لا يُصل إليه إلا من الإدارة |
| SSL | شهادة Let's Encrypt المجانية من hPanel + إجبار HTTPS + HSTS |
| Headers | CSP، X-Frame-Options، X-Content-Type-Options، Referrer-Policy، Permissions-Policy |
| البريد | SMTP عبر Hostinger + سجلات SPF/DKIM/DMARC |
| النسخ الاحتياطي | نسخ Hostinger اليومية + نسخة يومية للقاعدة والصور عبر Scheduler مع احتفاظ 14 يومًا. يُنصح لاحقًا بوجهة خارجية |
| المراقبة | سجل أخطاء يومي (`daily`) بدون كلمات مرور أو بيانات دفع؛ إشعار بريدي للأخطاء الحرجة |
| البيئات | Local ← Staging (نطاق فرعي محمي) ← Production |

### 8.4 قيود الاستضافة المعروفة وكيف تعاملت معها
| القيد | الحل |
|---|---|
| لا خادم دائم / Supervisor | Queue عبر Cron |
| لا Redis | Database/File cache — قابل للتبديل لاحقًا |
| لا WebSockets | المزايدة الحية مؤجلة؛ الخيار لاحقًا Polling أو الانتقال لـ VPS |
| لا بناء Node على الخادم | البناء في GitHub Actions |
| حدود الذاكرة والوقت | تحويل الصور في الخلفية، ترقيم الصفحات، تصدير البيانات على دفعات |

### 8.5 بيئة التطوير المحلية
متوفر على الجهاز: PHP 8.5، Composer 2.10، Node 26، Git. **MySQL غير مثبت** — سأجهّز MySQL محلي في المرحلة 2، وسأختبر أيضًا على نفس إصدار PHP/MySQL الموجود فعليًا على Hostinger لتجنب أي اختلاف.

---

## 9. قرارات صاحب المشروع

### 9.1 تم اعتمادها (2026-10-02)
| الموضوع | القرار |
|---|---|
| الشحن | داخل السعودية فقط |
| طرق الدفع | مدى، Apple Pay، تابي، تمارا |
| الضريبة | المتجر مسجّل، والأسعار المعروضة شاملة الضريبة (15%) |
| الشراء كضيف | مسموح. رقم الجوال إلزامي عند الدفع (يحتاجه المندوب للتوصيل)، والبريد اختياري لإرسال التأكيد |
| المزادات (النسخة الأولى) | عرض المزادات والقطع + زر "سجّل اهتمامك" |
| المنتجات | صور مؤقتة معلَّمة كتجريبية، تُستبدل من لوحة الإدارة |
| الشعار | صممته بنسخة احترافية (vector) — انظر `docs/brand/` |
| النطاق | تجريبي: `php.piecenstory.com` — النهائي: `piecenstory.com` |
| تسجيل الدخول | بالبريد وكلمة المرور، **و**برمز SMS على الجوال |
| زمن الرد على العملاء | خلال 30 دقيقة في أوقات العمل الرسمية، وخلال 2–4 ساعات خارجها |
| الباحث الشخصي | لا يتطلب تسجيل دخول |
| الخدمات | التوصيل، الخدمة في الفرع، الحجز المسبق، بيع منتجات الغير (في الموقع أو المتجر) |
| أوقات العمل | يوميًا من 12 ظهرًا حتى 12 منتصف الليل (بتوقيت الرياض) |
| صفحات إضافية | "الأسئلة الشائعة" و"تتبع الطلب" تُضاف |
| الإرجاع والاستبدال | مقبول فقط إذا كان بالمنتج خلل أو عيب تصنيع أو لا يطابق الوصف |
| مزود SMS | يُضاف من لوحة الإدارة لاحقًا (المفاتيح تُحفظ مشفرة) — حتى ذلك الحين يعمل الدخول بالبريد |
| الفروع | معرض البوادي، معرض الحرازات، المستودع (الخمرة) |
| سعر الشحن | يُحدَّد لاحقًا من لوحة الإدارة (التوصيل معطّل حتى إدخال السعر) |
| الحجز المسبق | نوعان: حجز قطعة، أو حجز بعربون (توثيق أعلى) |
| بيع منتجات الغير | نموذج في الموقع ← مراجعة ← موافقة أو رفض من الإدارة |
| مدة الحجز المسبق | 4 أيام، ثم تذكير العميل بالدفع لمدة 3 أيام إضافية، ثم إلغاء تلقائي إن لم يدفع |
| المعرض الرئيسي | معرض البوادي — الإحداثيات 21.587040, 39.175657 |
| صورة الواجهة | صورة واجهة المعرض المرسلة من المالك (تُستخدم كأول شريحة في الصفحة الرئيسية) |
| العربون | 15% من سعر القطعة، ويُسترد إذا أُلغي الحجز |
| العملة | SAR فقط (مستنتج من الشحن داخل السعودية — مبدّل العملة يُحذف) |

### 9.2 ما زال مفتوحًا

| # | السؤال | مطلوب قبل |
|---|---|---|
| 2 | عنوان وأرقام معرض الحرازات، ورقم التواصل الرسمي | قبل الإطلاق |

---

## 10. ما لن يُبنى في النسخة الأولى (إلا بطلبك)
كوبونات الخصم · تقييمات العملاء · النشرة البريدية · المزايدة الفعلية · تسجيل الدخول بالسوشيال ميديا · تطبيق جوال · ربط API مع شركات الشحن · عملات متعددة.

**الحالة الحالية:** المراحل 1–8 مكتملة. التالي: المرحلة 9 (إتمام الطلب والحجز المسبق).
