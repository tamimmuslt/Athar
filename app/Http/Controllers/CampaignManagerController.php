<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\CampaignQuestion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CampaignManagerController extends Controller
{
    /**
     * 1. إنشاء حملة جديدة مع الأسئلة التقييمية الخاصة بها في نفس الوقت
     */
    public function store(Request $request): JsonResponse
    {
        // استخدام كلاس Validator يدوياً للتحقق من البيانات المدخلة
        $validator = Validator::make($request->all(), [
            'title'               => 'required|string|max:255',
            'about'         => 'required|string',
            'location'       => 'required|string',
            'meeting_point'       => 'nullable|string',
            'latitude'            => 'nullable|numeric',
            'longitude'           => 'nullable|numeric',
            'volunteers_needed' => 'required|integer|min:1',
            'start_date'          => 'required|date',
            'end_date'            => 'required|date|after_or_equal:start_date',
            'time'                => 'required',
            'type'                => 'required|in:on-ground,remote',
            'image'               => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            
            // التحقق من مصفوفة الأسئلة التقييمية (إذا وُجدت)
            'questions'                 => 'nullable|array|min:1',
            'questions.*.question_text' => 'required_with:questions|string',
            'questions.*.option_a'      => 'required_with:questions|string',
            'questions.*.option_b'      => 'required_with:questions|string',
            'questions.*.option_c'      => 'required_with:questions|string',
            'questions.*.option_d'      => 'required_with:questions|string',
            'questions.*.correct_option'=> 'required_with:questions|in:a,b,c,d',
        ]);

        // إذا فشل التحقق، يتم إرجاع الأخطاء مخصصة فوراً قبل الدخول بالـ Transaction
        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'خطأ في البيانات المرسلة، يرجى التحقق من الحقول الجبرية',
                'errors'  => $validator->errors()
            ], 422);
        }

        // استخدام Database Transaction لضمان أمان العمليات المترابطة
        DB::beginTransaction();

        try {
            // معالجة ورفع صورة الحملة
            $imagePath = null;
            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('campaigns', 'public');
            }

            // 1. حفظ بيانات الحملة في جدول campaigns
            $campaign = Campaign::create([
                'organization_id'       => auth()->id(), // معرف المؤسسة الحالية من التوكن
                'title'                 => $request->title,
                'about'           => $request->about,
                'location'         => $request->location,
                'meeting_point'         => $request->meeting_point,
                'latitude'              => $request->latitude,
                'longitude'             => $request->longitude,
                'volunteers_needed'   => $request->volunteers_needed,
                'volunteers_registered' => 0, // تبدأ من الصفر تلقائياً
                'start_date'            => $request->start_date,
                'end_date'              => $request->end_date,
                'time'                  => $request->time,
                'type'                  => $request->type,
                'status'                => 'active', // يتم تفعيلها مباشرة لتظهر عند المتطوعين
                'image'                 => $imagePath,
            ]);

            // 2. التحقق من وجود أسئلة وحفظها بربطها بـ id الحملة التي أُنشئت للتو
            if ($request->has('questions') && is_array($request->questions)) {
                foreach ($request->questions as $qData) {
                    CampaignQuestion::create([
                        'campaign_id'   => $campaign->id,
                        'question_text' => $qData['question_text'],
                        'option_a'      => $qData['option_a'],
                        'option_b'      => $qData['option_b'],
                        'option_c'      => $qData['option_c'],
                        'option_d'      => $qData['option_d'],
                        'correct_option'=> $qData['correct_option'],
                    ]);
                }
            }

            // تثبيت البيانات في قاعدة البيانات بنجاح عند اكتمال الحلقة
            DB::commit();

            return response()->json([
                'status'      => 'success',
                'message'     => 'تم إنشاء الحملة وإضافة الأسئلة التقييمية بنجاح!',
                'campaign_id' => $campaign->id
            ], 201);

        } catch (\Exception $e) {
            // في حال حدوث أي خطأ طارئ، يتم التراجع عن كل العمليات لحماية تكامل البيانات
            DB::rollBack();
            return response()->json([
                'status'  => 'error',
                'message' => 'حدث خطأ أثناء حفظ البيانات، يرجى المحاولة لاحقاً',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 2. عرض كل الحملات الخاصة بالمؤسسة الحالية داخل لوحة التحكم تبعها
     */
    /**
     * 2. عرض كل الحملات الخاصة بالمؤسسة الحالية داخل لوحة التحكم تبعها (الحقول الستة فقط)
     */
    public function index(Request $request): JsonResponse
    {
        // جلب الحملات التابعة للمؤسسة الحالية المسجلة دخول فقط عبر auth()->id()
        $query = Campaign::where('organization_id', auth()->id())->with('organization');

        // ميزة البحث بالاسم داخل حملات المؤسسة نفسها (إذا لزم بالـ Dashboard)
        if ($request->has('search') && !is_null($request->search)) {
            $query->where('title', 'LIKE', '%' . $request->search . '%');
        }

        $campaigns = $query->orderBy('created_at', 'desc')->get()->map(function ($campaign) {
            return [
                'id'            => $campaign->id,
                'title'         => $campaign->title, // عنوان الحملة العلوي
                'org_name'      => $campaign->organization ? $campaign->organization->org_name : 'مؤسستي', // اسم المؤسسة
                'location'      => $campaign->location, // الموقع مثل: Hama, al furkan
                'image_url'     => $campaign->image ? asset('storage/' . $campaign->image) : asset('storage/default-campaign.png'), // صورة الكرت
                'rating'        => 5, // قيمة ثابتة 5 نجوم متل التصميم
                'reviews_count' => 100, // ثابتة "100 Reviews" متل التصميم
            ];
        });

        return response()->json([
            'status'    => 'success',
            'message'   => 'تم جلب حملات المؤسسة بنجاح',
            'campaigns' => $campaigns // مصفوفة صافية ومفلترة للمؤسسة الحالية بالحقول الستة فقط
        ], 200);
    }}