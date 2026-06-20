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
        $query = Campaign::where('status', 'active')->with('organization');

        if ($request->has('search') && !is_null($request->search)) {
            $query->where('title', 'LIKE', '%' . $request->search . '%');
        }

        $campaigns = $query->orderBy('created_at', 'desc')->get()->map(function ($campaign) {
            return [
                'id'            => $campaign->id,
                'title'         => $campaign->title, 
                'org_name'      => $campaign->organization ? $campaign->organization->org_name : 'جمعية خيرية', // اسم المؤسسة تحت العنوان
                'location'      => $campaign->location, 
                'image_url'     => $campaign->image ? asset('storage/' . $campaign->image) : asset('storage/default-campaign.png'), // صورة الكرت
                'rating'        => 5, 
                'reviews_count' => 100, 
            ];
        });

        return response()->json([
            'status'    => 'success',
            'message'   => 'تم جلب قائمة الحملات بنجاح',
            'campaigns' => $campaigns 
        ], 200);
    }
\   public function show($id)
    {
        $campaign = Campaign::with('organization')->find($id);

        if (!$campaign) {
            return response()->json([
                'status'  => 'error',
                'message' => 'الحملة المطلوبة غير موجودة'
            ], 404);
        }

        $daysLeft = 0;
        if ($campaign->end_date) {
            $daysLeft = Carbon::now()->diffInDays(Carbon::parse($campaign->end_date), false);
            $daysLeft = $daysLeft < 0 ? 0 : (int)$daysLeft;
        }

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
                    'category'      => $feat->category ?? 'volunteer',
                    'org_name'      => $feat->organization ? $feat->organization->org_name : 'مؤسسة أثر',
                    'location'      => $feat->location,
                    'image_url'     => $feat->image ? asset('storage/' . $feat->image) : asset('storage/default-campaign.png'),
                    'rating'        => 5,
                    'reviews_count' => 100,
                ];
            });

        $data = [
            'id'            => $campaign->id,
            'title'         => $campaign->title,
            'category'      => $campaign->category ?? 'volunteer', 
            'location'      => $campaign->location,
            'image_url'     => $campaign->image ? asset('storage/' . $campaign->image) : asset('storage/default-campaign.png'),
            'rating'        => 5,
            'reviews_count' => 100,
            'days_left'     => $daysLeft,
            'about'         => $campaign->about, 
        ];

        if ($campaign->category === 'donation') {
            $donationFields = [
                'donation_goal'     => (float) ($campaign->donation_goal ?? 10000.00),
                'raised_amount'     => (float) ($campaign->raised_amount ?? 0.00),
                'donors_count'      => (int) ($campaign->donors_count ?? 0),
                
                'donation_benefits' => $campaign->donation_benefits ?? "• Provide Food Baskets To Families.\n• Support Medical Treatments.\n• Supply School Materials.\n• Improve Living Conditions.",
                
                'donation_impact'   => [
                    ['amount' => '$10', 'label' => 'Feed One Family'],
                    ['amount' => '$25', 'label' => 'School Supplies'],
                    ['amount' => '$50', 'label' => 'Medical Assistance'],
                    ['amount' => '$100', 'label' => 'Emergency Support'],
                ]
            ];
            $data = array_merge($data, $donationFields);
        } else {
            $needed     = (int) $campaign->volunteers_needed;
            $registered = (int) $campaign->volunteers_registered;
            $remaining  = max(0, $needed - $registered);

            $volunteerFields = [
                'type'                  => $campaign->type,
                'date'                  => Carbon::parse($campaign->start_date)->format('d/m/Y'),
                'time'                  => $campaign->time ? Carbon::parse($campaign->time)->format('g:i A') : null,
                'meeting_point'         => $campaign->meeting_point,
                'volunteers_needed'     => $needed,
                'volunteers_registered' => $registered,
                'volunteers_remaining'  => $remaining,

                'requirements'          => $campaign->requirements ?? "• Plant Trees In Designated Areas.\n• Assist With Tools And Activity Preparation.\n• Help Clean And Organize The Area.",
                'responsibilities'      => $campaign->responsibilities ?? "• Follow The Team Leader Instructions.\n• Respect The Environment And Protect Trees.\n• Complete Assigned Tasks.\n• Cooperate With Other Volunteers.",
                'important_notes'       => $campaign->important_notes ?? "• Wear Comfortable Clothes And Closed Shoes.\n• Arrive On Time.\n• Bring Water If Needed.\n• Stay In The Assigned Area.",
            ];
            $data = array_merge($data, $volunteerFields);
        }

        return response()->json([
            'status' => 'success',
            'data'   => [
                'campaign_details'   => $data,
                
                'organization' => [
                    'id'          => $campaign->organization ? $campaign->organization->id : null,
                    'name'        => $campaign->organization ? $campaign->organization->org_name : 'The Lifa Organization',
                    'logo_url'    => ($campaign->organization && $campaign->organization->logo) ? asset('storage/' . $campaign->organization->logo) : asset('storage/default-org.png'),
                    'address'     => $campaign->organization ? $campaign->organization->address : 'Hama, al furkan',
                    'website_url' => 'https://www.thelife.com', 
                ],

                'contact_us' => [
                    'phone'   => $campaign->organization ? $campaign->organization->phone_number : '+963-906-156-2849',
                    'email'   => $campaign->organization ? $campaign->organization->official_email : 'Athar@gmail.com',
                    'address' => $campaign->organization ? $campaign->organization->address : 'Syria-Aleppo',
                ],

                'featured_campaigns' => $featuredCampaigns
            ]
        ], 200);
    }
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

    public function submitQuiz(Request $request, $id)
{
    $validator = Validator::make($request->all(), [
        'answers' => 'required|array',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status'  => 'error',
            'message' => 'خطأ في البيانات المرسلة، يرجى إرسال مصفوفة الإجابات بشكل صحيح',
            'errors'  => $validator->errors()
        ], 422);
    }

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

    foreach ($campaign->questions as $question) {
        if (isset($request->answers[$question->id])) {
            $userAnswer = $request->answers[$question->id];
            
            if (trim(strtolower($userAnswer)) === trim(strtolower($question->correct_option))) {
                $correctAnswersCount++;
            }
        }
    }

    $scorePercentage = round(($correctAnswersCount / $totalQuestions) * 100);
    $passed = $scorePercentage >= 70; 

    $application = CampaignApplication::updateOrCreate(
        [
            'volunteer_id'     => auth()->id(), 
            'campaign_id' => $campaign->id,
        ],
        [
            'score'  => $scorePercentage,
            'status' => $passed ? 'passed' : 'failed',
        ]
    );

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
}

public function myApplications()
    {
        $volunteerId = auth()->id();

        $applications = CampaignApplication::where('volunteer_id', $volunteerId)
            ->with(['campaign.organization'])
            ->latest()
            ->get()
            ->map(function ($app) {
                return [
                    'application_id'     => $app->id,
                    'score'              => $app->score,
                    'status'             => $app->status, // passed, failed, pending
                    'campaign_id'        => $app->campaign ? $app->campaign->id : null,
                    'campaign_title'     => $app->campaign ? $app->campaign->title : 'حملة محذوفة',
                    'organization_name'  => ($app->campaign && $app->campaign->organization) ? $app->campaign->organization->org_name : 'مؤسسة غير معروفة',
                    'location'           => $app->campaign ? $app->campaign->location : 'غير محدد',
                    'image_url'          => ($app->campaign && $app->campaign->image) ? asset('storage/' . $app->campaign->image) : asset('storage/default-campaign.png'),
                ];
            });

        return response()->json([
            'status'  => 'success',
            'message' => 'تم جلب طلباتك بنجاح',
            'data'    => $applications
        ], 200);
    }

   
    public function showApplication($application_id)
    {
        $application = CampaignApplication::where('volunteer_id', auth()->id())
            ->with(['campaign.organization'])
            ->find($application_id);

        if (!$application) {
            return response()->json([
                'status'  => 'error',
                'message' => 'طلب التقديم المطلوب غير موجود أو لا تملك صلاحية للوصول إليه'
            ], 404);
        }

        $statusText = 'Under Review';
        if ($application->status === 'pending') {
            $statusText = 'Pending, Under Review';
        } elseif ($application->status === 'approved' || $application->status === 'passed') {
            $statusText = 'Approved, Passed';
        } elseif ($application->status === 'rejected' || $application->status === 'failed') {
            $statusText = 'Rejected, Failed';
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'تم جلب تفاصيل الطلب بنجاح',
            'data'    => [
                'application_id'     => $application->id,
                
                'current_status'     => $statusText, 
                'status_raw'         => $application->status, 
                
                'organization_name'  => $application->campaign->organization->org_name, 
                
                'campaign_title'     => $application->campaign->title, 
                
                'location'           => $application->campaign->location, 
                
                'submitted_on'       => Carbon::parse($application->created_at)->format('Y/m/d'), 
            ]
        ], 200);
    }

    
}