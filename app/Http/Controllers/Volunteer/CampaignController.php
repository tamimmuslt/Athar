<?php

namespace App\Http\Controllers\Volunteer;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\CampaignApplication;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class CampaignController extends Controller
{
    public function index(Request $request)
    {
        // جلب الحملات النشطة مع بيانات المؤسسة التابعة لها
        $query = Campaign::where('status', 'active')->with('organization');

        // ميزة البحث بالاسم إذا استخدم المتطوع شريط البحث بالواجهة
        if ($request->has('search') && !is_null($request->search)) {
            $query->where('title', 'LIKE', '%' . $request->search . '%');
        }

        $campaigns = $query->orderBy('created_at', 'desc')->get()->map(function ($campaign) {
            return [
                'id'            => $campaign->id,
                'title'         => $campaign->title, // عنوان الحملة العلوي
                'org_name'      => $campaign->organization ? $campaign->organization->org_name : 'جمعية خيرية', // اسم المؤسسة تحت العنوان
                'location'      => $campaign->location, // الموقع مثل: Hama, al furkan
                'image_url'     => $campaign->image ? asset('storage/' . $campaign->image) : asset('storage/default-campaign.png'), // صورة الكرت
                'rating'        => 5, // قيمة ثابتة 5 نجوم متل التصميم
                'reviews_count' => 100, // ثابتة "100 Reviews" متل التصميم
            ];
        });

        return response()->json([
            'status'    => 'success',
            'message'   => 'تم جلب قائمة الحملات بنجاح',
            'campaigns' => $campaigns // مصفوفة صافية بالحقول الستة فقط لا غير
        ], 200);
    }
    // 2. تفاصيل الحملة بالكامل (مطابق لواجهات تفاصيل شاشة شجرة الحور والتطوع)
    public function show($id)
{
    // 1. جلب الحملة مع المؤسسة التابعة لها
    $campaign = Campaign::with('organization')->find($id);

    if (!$campaign) {
        return response()->json([
            'status'  => 'error',
            'message' => 'الحملة المطلوبة غير موجودة'
        ], 404);
    }

    // 2. حسابات المقاعد والنسبة المئوية لشريط التقدم (Progress Bar)
    $needed = (int) $campaign->volunteers_needed;
    $registered = (int) $campaign->volunteers_registered;
    $remaining = max(0, $needed - $registered);
    $percentage = $needed > 0 ? round(($registered / $needed) * 100) : 0;

    // 3. عمل تنصيب وترتيب للحملات المقترحة بأسفل الواجهة (Featured Campaigns) - الصورة 3
    // جلب أحدث 3 حملات نشطة لنفس المؤسسة أو بشكل عام مع استبعاد الحملة الحالية
    $featuredCampaigns = Campaign::where('status', 'active')
        ->where('id', '!=', $campaign->id)
        ->with('organization')
        ->latest()
        ->take(3)
        ->get()
        ->map(function ($feat) {
            return [
                'id'            => $feat->id,
                'title'         => $feat->title,
                'org_name'      => $feat->organization ? $feat->organization->org_name :'',
                'location'      => $feat->location,
                'image_url'     => $feat->image ? asset('storage/' . $feat->image) : asset('storage/default-campaign.png'),
                'rating'        => 5,
                'reviews_count' => 100,
            ];
        });

    // 4. بناء الـ JSON الشامل المطابق للهيكلية الرسومية بالصور مية بالمية
    return response()->json([
        'status' => 'success',
        'data'   => [
            
            // القسم العلوي وقسم الـ About (الصورة 1 و 2)
            'campaign_details' => [
                'id'                    => $campaign->id,
                'title'                 => $campaign->title, // Tree Planting Campaign
                'type'                  => $campaign->type,   // on-ground / remote
                'date'                  => Carbon::parse($campaign->start_date)->format('d/m/Y'), // صيغة التاريخ: 23/5/2026
                'time'                  => Carbon::parse($campaign->time)->format('g:i A'),       // صيغة الوقت: 9:00 AM
                'location'              => $campaign->location,       // Aleppo, The Public Park
                'meeting_point'         => $campaign->meeting_point,  // نقطة التجمع عند الحاجة
                'image_url'             => $campaign->image ? asset('storage/' . $campaign->image) : asset('storage/default-campaign.png'),
                'rating'                => 5,
                'reviews_count'         => 100,
                
                // الإحصائيات والأرقام الخاصة بالـ Progress Bar السفلي (الصورة 1)
                'volunteers_needed'     => $needed,
                'volunteers_registered' => $registered,
                'volunteers_remaining'  => $remaining,
                
                // النصوص والوصف (الصورة 2)
                'about'                 => $campaign->about, // نص شرح الحملة كامل
                // حقل المتطلبات: يمكنك وضع نص افتراضي فخم إذا لم تكن قد أضفته بالـ Migration لسا كرمال الواجهة تثبت
                'requirements'          => "• Plant Trees In Designated Areas.\n• Assist With Tools And Activity Preparation.\n• Help Clean And Organize The Area.",
            ],

            // كرت معلومات المؤسسة الجانبي (الصورة 2 على اليمين)
            'organization' => [
                'id'          => $campaign->organization ? $campaign->organization->id : null,
                'name'        => $campaign->organization ? $campaign->organization->org_name : 'The Lifa Organization',
                'logo_url'    => ($campaign->organization && $campaign->organization->logo) ? asset('storage/' . $campaign->organization->logo) : asset('storage/default-org.png'),
                'address'     => $campaign->organization ? $campaign->organization->address : 'Hama, al furkan',
                'website_url' => 'https://www.thelife.com', // زر الـ View Organization الأصفر
            ],

            // قسم الـ Contact Us الثابت على اليمين (الصورة 3)
            'contact_us' => [
                'phone'   => $campaign->organization ? $campaign->organization->phone_number : '',
                'email'   => $campaign->organization ? $campaign->organization->official_email : '',
                'address' => $campaign->organization ? $campaign->organization->address : '',
            ],

            // مصفوفة الكروت السفلية للحملات المقترحة (Featured Campaigns) - الصورة 3 يساراً
            'featured_campaigns' => $featuredCampaigns
        ]
    ], 200);
}
    // 3. جلب أسئلة الاختبار الخاصة بالحملة
    public function getQuiz($id)
    {
        $campaign = Campaign::find($id);
        if (!$campaign) {
            return response()->json(['message' => 'الحملة غير موجودة'], 404);
        }

        $questions = $campaign->questions()->get()->map(function($q) {
            return [
                'id' => $q->id,
                'question_text' => $q->question_text,
                'options' => [
                    ['key' => 'a', 'text' => $q->option_a],
                    ['key' => 'b', 'text' => $q->option_b],
                    ['key' => 'c', 'text' => $q->option_c],
                    ['key' => 'd', 'text' => $q->option_d],
                ]
            ];
        });

        return response()->json(['questions' => $questions], 200);
    }

    // 4. إرسال إجابات الاختبار وحساب النتيجة (تحديد ناجح/راسب بناءً على 70%)
    public function submitQuiz(Request $request, $id)
{
    // 1. استخدام كلاس Validator يدوياً للتحقق من البيانات المدخلة
    $validator = Validator::make($request->all(), [
        'answers' => 'required|array', // مصفوفة تحتوي على [question_id => selected_option]
    ]);

    // إذا فشل التحقق، يتم إرجاع الأخطاء مخصصة فوراً بنمط JSON وبكود 422
    if ($validator->fails()) {
        return response()->json([
            'status'  => 'error',
            'message' => 'خطأ في البيانات المرسلة، يرجى إرسال مصفوفة الإجابات بشكل صحيح',
            'errors'  => $validator->errors()
        ], 422);
    }

    // 2. جلب الحملة مع الأسئلة التقييمية الخاصة بها
    $campaign = Campaign::with('questions')->find($id);
    if (!$campaign) {
        return response()->json([
            'status'  => 'error',
            'message' => 'الحملة المطلوبة غير موجودة'
        ], 404);
    }

    $totalQuestions = $campaign->questions->count();
    if ($totalQuestions == 0) {
        return response()->json([
            'status'  => 'error',
            'message' => 'هذه الحملة لا تحتاج لاختبار قبولي'
        ], 400);
    }

    $correctAnswersCount = 0;

    // 3. مقارنة إجابات المتطوع بالأجوبة الصحيحة في قاعدة البيانات بطريقة آمنة
    foreach ($campaign->questions as $question) {
        // التشييك لضمان عدم حدوث خطأ (Undefined index) إذا لم يرسل المتطوع إجابة على هذا السؤال المحدد
        if (isset($request->answers[$question->id])) {
            $userAnswer = $request->answers[$question->id];
            
            // تنظيف الحروف ومقارنتها للتأكد من التطابق (مثلاً: 'A ' تساوي 'a')
            if (trim(strtolower($userAnswer)) === trim(strtolower($question->correct_option))) {
                $correctAnswersCount++;
            }
        }
    }

    // 4. حساب النسبة المئوية للنجاح
    $scorePercentage = round(($correctAnswersCount / $totalQuestions) * 100);
    $passed = $scorePercentage >= 70; // حد النجاح الافتراضي 70%

    // 5. تحديث أو إنشاء طلب التقديم في قاعدة البيانات (Campaign Application)
    $application = CampaignApplication::updateOrCreate(
        [
            'volunteer_id'     => auth()->id(), // معرف المتطوع الحالي المسجل دخول من التوكن
            'campaign_id' => $campaign->id,
        ],
        [
            'score'  => $scorePercentage,
            'status' => $passed ? 'passed' : 'failed',
            // 'submitted_at' => now() // فك الكومنت عن هذا السطر إذا كان الحقل متاحاً في جدولك ومختلفاً عن created_at
        ]
    );

    // 6. إرجاع النتيجة منسقة ونظيفة ومطابقة تماماً لمتطلبات الفرونت إند
    return response()->json([
        'status'  => 'success',
        'message' => $passed ? 'مبروك! لقد اجتزت الاختبار بنجاح.' : 'للأسف، لم تحقق الحد الأدنى لعلامة النجاح.',
        'data'    => [
            'score'              => $scorePercentage,
            'passing_score'      => 70,
            'passed'             => $passed,
            'application_status' => $application->status,
            'correct_answers'    => $correctAnswersCount,
            'total_questions'    => $totalQuestions
        ]
    ], 200);
}}