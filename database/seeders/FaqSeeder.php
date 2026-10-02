<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Content\Models\Faq;
use Illuminate\Database\Seeder;

/** Initial FAQ written from the owner's confirmed business decisions; editable from the admin panel. */
class FaqSeeder extends Seeder
{
    private const array FAQS = [
        [
            'هل توصلون خارج السعودية؟', 'Do you deliver outside Saudi Arabia?',
            'حاليًا نوصل الطلبات داخل المملكة العربية السعودية فقط.',
            'We currently deliver within Saudi Arabia only.',
        ],
        [
            'هل يمكنني استلام طلبي من المعرض؟', 'Can I collect my order from a showroom?',
            'نعم، يمكنك اختيار الاستلام من معرض البوادي أو معرض الحرازات في جدة عند إتمام الطلب.',
            'Yes. Choose pickup from our Al-Bawadi or Al-Harazat showroom in Jeddah at checkout.',
        ],
        [
            'ما طرق الدفع المتاحة؟', 'Which payment methods do you accept?',
            'مدى، وApple Pay، والتقسيط عبر تابي وتمارا.',
            'mada, Apple Pay, and instalments with Tabby and Tamara.',
        ],
        [
            'هل الأسعار شاملة الضريبة؟', 'Do prices include VAT?',
            'نعم، جميع الأسعار المعروضة شاملة ضريبة القيمة المضافة (15%).',
            'Yes. All prices shown include 15% VAT.',
        ],
        [
            'ما سياسة الإرجاع والاستبدال؟', 'What is your returns and exchange policy?',
            'نقبل الإرجاع أو الاستبدال إذا كان بالقطعة خلل أو عيب تصنيع، أو إذا لم تطابق الوصف المعروض في الموقع.',
            'We accept returns or exchanges when a piece has a defect or manufacturing fault, or does not match its description on the site.',
        ],
        [
            'هل يمكنني حجز قطعة قبل شرائها؟', 'Can I reserve a piece before buying it?',
            'نعم، نوفر الحجز المسبق للقطع، إما حجزًا مباشرًا أو حجزًا بعربون لتوثيق أعلى.',
            'Yes. You can reserve a piece directly, or with a deposit for a firmer reservation.',
        ],
        [
            'هل تعرضون قطعًا للبيع نيابة عن أصحابها؟', 'Do you sell pieces on behalf of their owners?',
            'نعم. يقدّم صاحب القطعة طلبًا عبر الموقع، ويراجعه فريقنا ثم يوافق عليه قبل عرض القطعة في الموقع أو المعرض.',
            'Yes. Owners submit a request through the site; our team reviews and approves it before the piece is listed online or in the showroom.',
        ],
        [
            'ما أوقات العمل ومتى تردون على الرسائل؟', 'What are your opening hours and response times?',
            "نعمل يوميًا من 12 ظهرًا حتى 12 منتصف الليل.\nنرد خلال 30 دقيقة في أوقات العمل، وخلال 2 إلى 4 ساعات خارجها.",
            "We are open daily from 12 noon to 12 midnight.\nWe reply within 30 minutes during opening hours, and within 2 to 4 hours outside them.",
        ],
    ];

    public function run(): void
    {
        foreach (self::FAQS as $order => [$questionAr, $questionEn, $answerAr, $answerEn]) {
            Faq::query()->firstOrCreate(['question_ar' => $questionAr], [
                'question_en' => $questionEn,
                'answer_ar' => $answerAr,
                'answer_en' => $answerEn,
                'sort_order' => $order + 1,
                'is_active' => true,
            ]);
        }
    }
}
