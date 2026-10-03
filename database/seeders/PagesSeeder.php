<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Content\Enums\PageKey;
use App\Domain\Content\Models\Page;
use Illuminate\Database\Seeder;

/**
 * Starting content for the fixed pages, written only from the owner's recorded decisions
 * (docs/01-project-analysis.md §9.1). Existing pages are never overwritten: once edited in the
 * admin, the owner's text is kept. Privacy and terms are legal texts: seeded as unpublished drafts
 * for the owner's review.
 */
class PagesSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->pages() as $key => $page) {
            Page::query()->firstOrCreate(['key' => $key], [
                ...$page,
                'slug_ar' => $key,
                'slug_en' => $key,
            ]);
        }
    }

    /** @return array<string, array<string, mixed>> */
    private function pages(): array
    {
        return [
            PageKey::About->value => [
                'title_ar' => 'من نحن',
                'title_en' => 'About us',
                'is_published' => true,
                'body_ar' => <<<'MD'
                    **قطعة وقصة** متجر للتحف والقطع الفاخرة في جدة. نؤمن أن لكل قطعة حكاية: مكانًا صُنعت فيه، وحقبة عاشت فيها، وأيادٍ اعتنت بها حتى وصلت إليك.

                    ## ما نقدمه
                    - التحف والأنتيك
                    - الأثاث الكلاسيكي
                    - النجف والإضاءة
                    - اللوحات الفنية
                    - الأواني المنزلية الفاخرة
                    - القطع النادرة

                    ## معارضنا
                    يسعدنا استقبالك في **معرض البوادي** (المعرض الرئيسي) و**معرض الحرازات** في جدة، يوميًا من 12 ظهرًا حتى 12 منتصف الليل.
                    MD,
                'body_en' => <<<'MD'
                    **Piece & Story** is an antiques and luxury pieces store in Jeddah. We believe every piece carries a story: where it was made, the era it lived through, and the hands that cared for it on its way to you.

                    ## What we offer
                    - Antiques
                    - Classic furniture
                    - Chandeliers and lighting
                    - Fine art
                    - Luxury homeware
                    - Rare pieces

                    ## Our showrooms
                    Visit us at the **Al-Bawadi showroom** (our main showroom) and the **Al-Harazat showroom** in Jeddah, daily from 12 pm to 12 am.
                    MD,
            ],

            PageKey::Services->value => [
                'title_ar' => 'خدماتنا',
                'title_en' => 'Our services',
                'is_published' => true,
                'body_ar' => <<<'MD'
                    ## التوصيل داخل المملكة
                    نوصل القطع داخل المملكة العربية السعودية، وتظهر رسوم التوصيل عند إتمام الطلب. التوصيل مجاني للطلبات التي تزيد عن 150 ر.س.

                    ## الاستلام والخدمة في المعرض
                    يمكنك استلام طلبك مجانًا من معرض البوادي أو معرض الحرازات، ومعاينة القطع على الطبيعة قبل الشراء.

                    ## الحجز المسبق
                    أعجبتك قطعة ولم تقرر بعد؟ احجزها باسمك **4 أيام**:
                    - **حجز بدون دفع**، أو
                    - **حجز بعربون 15%** من قيمة الطلب لتوثيق أعلى، ويُسترد العربون كاملًا إذا أُلغي الحجز.

                    بعد انتهاء مدة الحجز نذكّرك بإكمال الدفع لمدة 3 أيام، ثم يُلغى الحجز تلقائيًا وتعود القطعة للعرض.

                    ## بيع قطعك عن طريقنا
                    لديك قطعة مميزة تريد بيعها؟ أرسل لنا تفاصيلها وصورها من صفحة [بِع قطعتك عن طريقنا](/sell-with-us)، ونراجعها ونرد عليك بالموافقة أو الاعتذار.

                    ## الباحث الشخصي
                    تبحث عن قطعة بعينها؟ صِفها لنا من صفحة [الباحث الشخصي](/personal-finder) ونبحث عنها لك.
                    MD,
                'body_en' => <<<'MD'
                    ## Delivery within Saudi Arabia
                    We deliver within Saudi Arabia; the delivery fee is shown at checkout. Delivery is free for orders over 150 SAR.

                    ## Showroom pickup and service
                    Collect your order free of charge from our Al-Bawadi or Al-Harazat showroom, and see the pieces in person before you buy.

                    ## Advance reservation
                    Love a piece but not ready to decide? Reserve it in your name for **4 days**:
                    - **without payment**, or
                    - **with a 15% deposit** for a firmer hold, refunded in full if the reservation is cancelled.

                    After the reservation period we remind you to complete payment for 3 more days; then the reservation is cancelled automatically and the piece goes back on sale.

                    ## Sell your pieces through us
                    Have a special piece you would like to sell? Send us its details and photos on the [Sell with us](/en/sell-with-us) page; we review it and come back to you with our decision.

                    ## Personal finder
                    Looking for a particular piece? Describe it on the [Personal finder](/en/personal-finder) page and we will search for it.
                    MD,
            ],

            PageKey::ShippingPolicy->value => [
                'title_ar' => 'سياسة الشحن والاستلام',
                'title_en' => 'Shipping & pickup policy',
                'is_published' => true,
                'body_ar' => <<<'MD'
                    - نشحن **داخل المملكة العربية السعودية فقط**.
                    - **الاستلام من المعرض مجاني** من معرض البوادي أو معرض الحرازات في جدة.
                    - تظهر رسوم التوصيل عند إتمام الطلب، و**التوصيل مجاني للطلبات التي تزيد عن 150 ر.س**.
                    - **الأسعار شاملة ضريبة القيمة المضافة 15%**.
                    - تُحجز القطع لطلبك 15 دقيقة لإكمال الدفع الإلكتروني؛ إن لم يكتمل الدفع تعود القطع للعرض.
                    - نرسل لك رقم الشحنة برسالة وبالبريد عند تسليم الطلب لشركة الشحن، ويمكنك متابعته من صفحة «تتبع الطلب».
                    MD,
                'body_en' => <<<'MD'
                    - We ship **within Saudi Arabia only**.
                    - **Showroom pickup is free** from our Al-Bawadi or Al-Harazat showroom in Jeddah.
                    - The delivery fee is shown at checkout, and **delivery is free for orders over 150 SAR**.
                    - **Prices include 15% VAT**.
                    - Pieces are held for your order for 15 minutes while you pay online; if payment is not completed, they go back on sale.
                    - When your order is handed to the courier we send you the tracking number by SMS and email, and you can follow it on the "Track order" page.
                    MD,
            ],

            PageKey::ReturnsPolicy->value => [
                'title_ar' => 'سياسة الإرجاع والاستبدال',
                'title_en' => 'Returns & exchanges policy',
                'is_published' => true,
                'body_ar' => <<<'MD'
                    كل قطعة لدينا فريدة، ونحرص على وصفها بدقة. نقبل الإرجاع أو الاستبدال في الحالات التالية:

                    - وجود **عيب أو خلل في القطعة**.
                    - وجود **عيب تصنيع**.
                    - إذا كانت القطعة **لا تطابق الوصف** المعروض في الموقع.

                    لطلب الإرجاع أو الاستبدال تواصل معنا مع رقم طلبك وصور توضح الحالة، وسنتواصل معك لترتيب ذلك.
                    MD,
                'body_en' => <<<'MD'
                    Every piece we sell is unique, and we take care to describe it accurately. We accept returns or exchanges when:

                    - the piece has a **defect or fault**;
                    - there is a **manufacturing defect**; or
                    - the piece **does not match its description** on the website.

                    To request a return or exchange, contact us with your order number and photos showing the issue, and we will arrange it with you.
                    MD,
            ],

            PageKey::Privacy->value => [
                'title_ar' => 'سياسة الخصوصية',
                'title_en' => 'Privacy policy',
                'is_published' => false,
                'body_ar' => <<<'MD'
                    > مسودة للمراجعة — لا تُنشر قبل اعتمادها.

                    ## البيانات التي نجمعها
                    الاسم ورقم الجوال والبريد الإلكتروني (اختياري) والعنوان عند التوصيل، لتنفيذ طلبك والتواصل معك بشأنه.

                    ## الدفع
                    لا نطّلع على بيانات بطاقتك ولا نحفظها؛ يتم الدفع لدى مزوّد الدفع مباشرة.

                    ## التواصل
                    نرسل رسائل تخص طلبك وحجزك فقط.
                    MD,
                'body_en' => <<<'MD'
                    > Draft for review — not to be published before approval.

                    ## Data we collect
                    Your name, mobile number, email (optional) and, for delivery, your address — to fulfil your order and contact you about it.

                    ## Payment
                    We never see or store your card details; payment is made directly with the payment provider.

                    ## Messages
                    We only send messages about your orders and reservations.
                    MD,
            ],

            PageKey::Terms->value => [
                'title_ar' => 'الشروط والأحكام',
                'title_en' => 'Terms & conditions',
                'is_published' => false,
                'body_ar' => <<<'MD'
                    > مسودة للمراجعة — لا تُنشر قبل اعتمادها.

                    - الأسعار بالريال السعودي وشاملة ضريبة القيمة المضافة.
                    - تخضع الحجوزات المسبقة لمدة 4 أيام، ثم 3 أيام للتذكير بالدفع، ثم تُلغى تلقائيًا، ويُسترد العربون كاملًا عند الإلغاء.
                    - تخضع عمليات الإرجاع والاستبدال لـ «سياسة الإرجاع والاستبدال».
                    MD,
                'body_en' => <<<'MD'
                    > Draft for review — not to be published before approval.

                    - Prices are in Saudi riyals and include VAT.
                    - Advance reservations last 4 days, followed by 3 days of payment reminders, then are cancelled automatically; any deposit is refunded in full on cancellation.
                    - Returns and exchanges follow our Returns & exchanges policy.
                    MD,
            ],
        ];
    }
}
