<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Donation;
use App\Models\CampaignQuestion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CampaignManagerController extends Controller
{
    /**
     * 1. إنشاء حملة جديدة (تطوعية أو تبرعية) مع الأسئلة التقييمية إن وُجدت
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title'             => 'required|string|max:255',
            'about'             => 'required|string',
            'location'          => 'required|string',
            'category'          => 'required|in:volunteer,donation', // تحديد النوع جبراً
            'image'             => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'start_date'        => 'required|date',
            'end_date'          => 'required|date|after_or_equal:start_date',
            'status'            => 'nullable|in:active,completed,cancelled,pending',

            // حقول تصبح جبرية فقط في حال كانت الحملة تطوعية (volunteer)
            'volunteers_needed' => 'required_if:category,volunteer|integer|min:1',
            'time'              => 'required_if:category,volunteer',
            'type'              => 'required_if:category,volunteer|in:on-ground,remote',
            'meeting_point'     => 'nullable|string',
            'latitude'          => 'nullable|numeric',
            'longitude'         => 'nullable|numeric',

            // حقول تصبح جبرية فقط في حال كانت الحملة تبرعية (donation)
            'donation_goal'     => 'required_if:category,donation|numeric|min:0',
            'donation_benefits' => 'nullable|string',

            // التحقق من مصفوفة الأسئلة التقييمية (خاصة بالتطوع عادةً)
            'questions'                  => 'nullable|array|min:1',
            'questions.*.question_text'  => 'required_with:questions|string',
            'questions.*.option_a'       => 'required_with:questions|string',
            'questions.*.option_b'       => 'required_with:questions|string',
            'questions.*.option_c'       => 'required_with:questions|string',
            'questions.*.option_d'       => 'required_with:questions|string',
            'questions.*.correct_option' => 'required_with:questions|in:a,b,c,d',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'خطأ في البيانات المرسلة، يرجى التحقق من الحقول المطلوبة لنوع الحملة',
                'errors'  => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            $imagePath = null;
            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('campaigns', 'public');
            }

            // تجهيز البيانات الأساسية
            $campaignData = [
                'organization_id'       => auth()->id(), // معرف المؤسسة من التوكن
                'title'                 => $request->title,
                'about'                 => $request->about,
                'location'              => $request->location,
                'category'              => $request->category,
                'start_date'            => $request->start_date,
                'end_date'              => $request->end_date,
                'status'                => $request->status ?? 'active',
                'image'                 => $imagePath,
                'meeting_point'         => $request->meeting_point,
                'latitude'              => $request->latitude,
                'longitude'             => $request->longitude,
            ];

            // تخصيص الحقول بناءً على نوع الحملة المختارة
            if ($request->category === 'donation') {
                $campaignData['donation_goal']      = $request->donation_goal;
                $campaignData['raised_amount']      = 0.00;
                $campaignData['donors_count']       = 0;
                $campaignData['donation_benefits']  = $request->donation_benefits;
                $campaignData['volunteers_needed']  = 0; // حقل افتراضي لقاعدة البيانات
                $campaignData['volunteers_registered'] = 0;
                $campaignData['type']               = 'remote'; 
                $campaignData['time']               = '00:00:00';
            } else {
                $campaignData['volunteers_needed']  = $request->volunteers_needed;
                $campaignData['volunteers_registered'] = 0;
                $campaignData['time']               = $request->time;
                $campaignData['type']               = $request->type;
            }

            // 1. حفظ الحملة في جدول campaigns
            // $campaign = Campaign::create($campaignData);

            // // 2. حفظ الأسئلة التقييمية إن وُجدت (للحملات التطوعية)
            // if ($request->category === 'volunteer' && $request->has('questions') && is_array($request->questions)) {
            //     foreach ($request->questions as $qData) {
            //         $campaign->questions()->create([
            //             'question_text'  => $qData['question_text'],
            //             'option_a'       => $qData['option_a'],
            //             'option_b'       => $qData['option_b'],
            //             'option_c'       => $qData['option_c'],
            //             'option_d'       => $qData['option_d'],
            //             'correct_option' => $qData['correct_option'],
            //         ]);
            //     }
            // }
$campaign = Campaign::create($campaignData);

            // 2. حفظ الأسئلة التقييمية إن وُجدت (للحملات التطوعية)
            if ($request->category === 'volunteer' && $request->has('questions') && is_array($request->questions)) {
                foreach ($request->questions as $qData) {
                    $campaign->questions()->create([
                        'question_text'  => $qData['question_text'],
                        'option_a'       => $qData['option_a'],
                        'option_b'       => $qData['option_b'],
                        'option_c'       => $qData['option_c'],
                        'option_d'       => $qData['option_d'],
                        'correct_option' => $qData['correct_option'],
                    ]);
                }
            }

            $volunteers = \App\Models\Volunteer::all(); 
            
            if ($volunteers->isNotEmpty()) {
                foreach ($volunteers as $volunteer) {
                    
                    DB::table('notifications')->insert([
                        'id'              => \Illuminate\Support\Str::uuid(),
                        'type'            => 'App\Notifications\CampaignAvailableNotification',
                        'notifiable_type' => 'App\Models\Volunteer', // أو مسار كلاس المتطوع عندك
                        'notifiable_id'   => $volunteer->id,
                        'data'            => json_encode(['campaign_name' => $campaign->title]),
                        'created_at'      => now(),
                        'updated_at'      => now()
                    ]);
                }
            }
            DB::commit();

            return response()->json([
                'status'      => 'success',
                'message'     => 'تم إنشاء الحملة بنجاح وطبقاً لنوعها المخزن!',
                'campaign_id' => $campaign->id
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'  => 'error',
                'message' => 'حدث خطأ أثناء حفظ البيانات، يرجى المحاولة لاحقاً',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 2. عرض كل الحملات الخاصة بالمؤسسة الحالية داخل لوحة التحكم (الحقول الستة + الـ Category)
     */
    public function index(Request $request): JsonResponse
    {
        $query = Campaign::where('organization_id', auth()->id())->with('organization');

        if ($request->has('search') && !is_null($request->search)) {
            $query->where('title', 'LIKE', '%' . $request->search . '%');
        }

        $campaigns = $query->orderBy('created_at', 'desc')->get()->map(function ($campaign) {
            return [
                'id'            => $campaign->id,
                'title'         => $campaign->title,
                'category'      => $campaign->category ?? 'volunteer', // لتفريق نوع الكرت بالـ Dashboard
                'org_name'      => $campaign->organization ? $campaign->organization->org_name : 'مؤسستي',
                'location'      => $campaign->location,
                'image_url'     => $campaign->image ? asset('storage/' . $campaign->image) : asset('storage/default-campaign.png'),
                'rating'        => 5,
                'reviews_count' => 100,
            ];
        });

        return response()->json([
            'status'    => 'success',
            'message'   => 'تم جلب حملات المؤسسة بنجاح',
            'campaigns' => $campaigns
        ], 200);
    }



    public function makeDonation(Request $request, $id): JsonResponse
{
    // 1. التحقق من المدخلات القادمة من واجهة الدفع
    $validator = Validator::make($request->all(), [
        'amount'           => 'required_without:custom_amount|numeric|min:1',
        'custom_amount'    => 'nullable|numeric|min:1',
        'payment_method'   => 'required|in:credit_card,paypal,sham_cash',
        'optional_message' => 'nullable|string|max:500',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status'  => 'error',
            'message' => 'يرجى التحقق من الحقول واختيار طريقة الدفع الصحيحة',
            'errors'  => $validator->errors()
        ], 422);
    }

    // تحديد القيمة النهائية للتبرع سواء اختيار كرت جاهز أو كتابة مبلغ مخصص
    $donationAmount = $request->custom_amount ?? $request->amount;

    // التأكد من أن الحملة موجودة ومخصصة للتبرع وليست للتطوع فقط
    $campaign = Campaign::where('id', $id)->where('category', 'donation')->first();

    if (!$campaign) {
        return response()->json([
            'status'  => 'error',
            'message' => 'عذراً، هذه الحملة غير مخصصة لاستقبال التبرعات المالية'
        ], 444);
    }

    DB::beginTransaction();

    try {
        // 2. تسجيل حركية التبرع في جدول التبرعات
        $donation = Donation::create([
            'volunteer_id'          => auth()->id(), // معرف المستخدم الحالي (أو افتراضي للتجربة في بوستمان)
            'campaign_id'      => $campaign->id,
            'amount'           => $donationAmount,
            'payment_method'   => $request->payment_method,
            'optional_message' => $request->optional_message,
            'status'           => 'completed', // محاكاة لنجاح عملية الدفع الفوري محلياً
        ]);

        // 3. تحديث الحقول الديناميكية في جدول الـ campaigns تلقائياً
        // زيادة المبلغ الكلي المجموع + زيادة عدد المتبرعين بمقدار 1
        $campaign->increment('raised_amount', $donationAmount);
        $campaign->increment('donors_count');

        DB::commit();
return response()->json([
    'status'  => 'success',
    'message' => 'شكراً لمساهمتك! تم تأكيد عملية الدفع وحفظ التبرع بنجاح.',
    'data'    => [
        'donation_id'    => $donation->id,
        // 🔥 توليد رقم عملية احترافي بناءً على الـ ID (مثال: #G6787656 + ID)
        'transaction_id' => 'ATHR-' . (100000 + $donation->id), 
        'campaign_title' => $campaign->title,
        'raised_amount'  => $campaign->raised_amount,
        'donors_count'   => $campaign->donors_count,
        // 🔥 إرسال التاريخ والوقت الفعلي للعملية بالتنسيق المطلوب بالواجهة
        'donation_date'  => $donation->created_at->format('Y/m/d - h:i A'), 
    ]
], 200);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'status'  => 'error',
            'message' => 'حدث خطأ أثناء معالجة عملية الدفع الرقمية',
            'error'   => $e->getMessage()
        ], 500);
    }
}


/**
     * 1. All Campaigns Endpoint (image_e1c0bb.png)
     * يجلب كل الحملات التي سجل فيها المتطوع بدون أي شروط تواريخ
     */
    public function allCampaigns(): JsonResponse
    {
        $volunteerId = auth()->id();
        
        $data = DB::table('campaign_volunteer')
            ->where('volunteer_id', $volunteerId)
            ->join('campaigns', 'campaign_volunteer.campaign_id', '=', 'campaigns.id')
            ->select('campaigns.title as campaign', 'campaigns.location', 'campaigns.start_date', 'campaigns.end_date')
            ->orderBy('campaign_volunteer.created_at', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'campaign'   => $item->campaign,
                    'location'   => $item->location,
                    'start_date' => \Carbon\Carbon::parse($item->start_date)->format('d/m/Y'),
                    'end_date'   => \Carbon\Carbon::parse($item->end_date)->format('d/m/Y'),
                ];
            });

        return response()->json(['status' => 'success', 'data' => $data], 200);
    }

    /**
     * 2. Current Campaigns Endpoint (image_e1c09d.png)
     * يجلب الحملات المقبولة (Accepted) التي يشارك فيها المتطوع حالياً بغض النظر عن التاريخ
     */
    public function currentCampaigns(): JsonResponse
    {
        $volunteerId = auth()->id();

        $data = DB::table('campaign_volunteer')
            ->where('volunteer_id', $volunteerId)
            ->join('campaigns', 'campaign_volunteer.campaign_id', '=', 'campaigns.id')
            ->where('campaign_volunteer.status', '=', 'accepted') // الاعتماد على حالة القبول فقط
            ->select('campaigns.title as campaign', 'campaigns.type', 'campaign_volunteer.created_at as joint_date')
            ->orderBy('campaign_volunteer.created_at', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'campaign' => $item->campaign,
                    'type'     => ucfirst($item->type), 
                    'status'   => 'Current',
                    'date'     => \Carbon\Carbon::parse($item->joint_date)->format('d/m/Y'),
                ];
            });

        return response()->json(['status' => 'success', 'data' => $data], 200);
    }

    /**
     * 3. Upcoming Campaigns Endpoint (image_e1c07b.png)
     * يجلب الحملات التي ما زالت قيد الانتظار (Pending) أو الحالات الأخرى المعلقة
     */
    public function upcomingCampaigns(): JsonResponse
    {
        $volunteerId = auth()->id();

        $data = DB::table('campaign_volunteer')
            ->where('volunteer_id', $volunteerId)
            ->join('campaigns', 'campaign_volunteer.campaign_id', '=', 'campaigns.id')
            // يجلب كل الطلبات سواء معلقة، مقبولة، أو مرفوضة لتعرض حالتها في جدول الـ Upcoming بالتصميم
            ->select('campaigns.title as campaign', 'campaigns.location', 'campaign_volunteer.status as request_status', 'campaigns.start_date')
            ->orderBy('campaign_volunteer.created_at', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'campaign'   => $item->campaign,
                    'location'   => $item->location,
                    'status'     => ucfirst($item->request_status), // Pending, Accepted, Cancelled
                    'start_date' => \Carbon\Carbon::parse($item->start_date)->format('d/m/Y'),
                ];
            });

        return response()->json(['status' => 'success', 'data' => $data], 200);
    }

    /**
     * 4. Completed Campaigns Endpoint (image_e1c044.png)
     * يجلب الحملات التي يملك فيها المتطوع ساعات تطوع فعلية أكبر من 0 (أنهى مشاركته بها)
     */
    public function completedCampaigns(): JsonResponse
    {
        $volunteerId = auth()->id();

        $data = DB::table('campaign_volunteer')
            ->where('volunteer_id', $volunteerId)
            ->join('campaigns', 'campaign_volunteer.campaign_id', '=', 'campaigns.id')
            ->where('campaign_volunteer.hours_participated', '>', 0) // الشرط هنا يعتمد على وجود ساعات عمل منجزة
            ->select('campaigns.title as campaign', 'campaigns.location', 'campaign_volunteer.hours_participated', 'campaign_volunteer.rating')
            ->orderBy('campaign_volunteer.created_at', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'campaign'           => $item->campaign,
                    'location'           => $item->location,
                    'hours_participated' => $item->hours_participated . ' Hours',
                    'rating'             => $item->rating, 
                ];
            });

        return response()->json(['status' => 'success', 'data' => $data], 200);
    }

    /**
     * 5. Donations Endpoint (image_e1c023.png)
     * سجل التبرعات المالية بالكامل
     */
    public function donationCampaigns(): JsonResponse
    {
        $volunteerId = auth()->id();

        $data = Donation::where('volunteer_id', $volunteerId)
            ->with('campaign')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($donation) {
                return [
                    'campaign'       => $donation->campaign->title ?? 'حملة تبرعات أثر',
                    'payment_method' => ucwords(str_replace('_', ' ', $donation->payment_method)),
                    'amount'         => $donation->amount . '$',
                    'date'           => $donation->created_at->format('d/m/Y'),
                ];
            });

        return response()->json(['status' => 'success', 'data' => $data], 200);
    }
}