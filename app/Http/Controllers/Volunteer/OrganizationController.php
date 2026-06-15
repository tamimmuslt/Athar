<?php

namespace App\Http\Controllers\Volunteer;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Campaign;

class OrganizationController extends Controller
{
    public function index(Request $request)
    {
        $query = Organization::where('status', 'approved');

        if ($request->has('search') && !is_null($request->search)) {
            $query->where('org_name', 'LIKE', '%' . $request->search . '%');
        }

        $organizations = $query->withCount('campaigns')->get()->map(function ($org) {
            return [
                'id'              => $org->id,
                'org_name'        => $org->org_name,
                'org_description' => $org->org_description,
                'address'         => $org->address,
                'logo_url'        => $org->logo ? asset('storage/' . $org->logo) : asset('storage/default-org.png'),
                'campaigns_count' => $org->campaigns_count,
            ];
        });

        return response()->json([
            'status'        => 'success',
            'message'       => 'تم جلب المؤسسات بنجاح',
            'organizations' => $organizations
        ], 200);
    }

    /**
     * جلب تفاصيل مؤسسة محددة متطابق بالملي مع الـ UI المطلوبة بالصور
     */
    public function show($id): JsonResponse
    {
        // 1. جلب المؤسسة
        $org = Organization::find($id);

        if (!$org) {
            return response()->json([
                'status'  => 'error',
                'message' => 'المؤسسة المطلوبة غير موجودة'
            ], 404);
        }

        // 2. جلب الحملات النشطة مع عمل Map لتجهيز الحقول والروابط متل كرت الواجهة
        $campaignsQuery = Campaign::where('organization_id', $id)->where('status', 'active');
        
        // حساب الإحصائيات المتقدمة للشريط الكحلي ديناميكياً من الداتابيز
        $campaignsCount = $campaignsQuery->count();
        
        // حساب المتطوعين المسجلين فعلياً بحملات هذه المؤسسة
        $totalVolunteers = $campaignsQuery->sum('volunteers_registered');
        
        // حساب عدد المدن المختلفة التي نفذت بها المؤسسة حملات
        $distinctCitiesCount = $campaignsQuery->distinct()->count('location');

        $campaigns = $campaignsQuery->orderBy('created_at', 'desc')->get()->map(function ($campaign) use ($org) {
            return [
                'id'                    => $campaign->id,
                'title'                 => $campaign->title,
                'org_name'              => $org->org_name, // اسم المؤسسة يظهر بالكرت تحت العنوان بالـ UI
                'location'         => $campaign->location, // مثل: Aleppo, al zahraa
                'image_url'             => $campaign->image ? asset('storage/' . $campaign->image) : asset('storage/default-campaign.png'),
                'rating'                => 5, // قيمة ثابتة 5 نجوم متل التصميم لحين ربط جدول المراجعات
                'reviews_count'         => 100, // متل الـ UI تماماً "100 Reviews"
            ];
        });

        // 3. تجميع البيانات وضخها بنفس الهيكلية التي تطلبها واجهات الفلاتر والبروفايل
        return response()->json([
            'status'  => 'success',
            'message' => 'تم جلب تفاصيل بروفايل المؤسسة بنجاح',
            'data'    => [
                // قشرة بيانات البروفايل العلوي (صورة 1)
                'organization' => [
                    'id'              => $org->id,
                    'org_name'        => $org->org_name,
                    'org_description' => $org->org_description,
                    'address'         => $org->address, // Aleppo
                    'email'           => $org->official_email,
                    'phone'           => $org->phone_number,
                    'website_url'     => 'https://our-website.com', // رابط الموقع الإلكتروني الزر الأصفر بالـ UI
                    'logo_url'        => $org->logo ? asset('storage/' . $org->logo) : asset('storage/default-org.png'),
                ],
                // بيانات الشريط الإحصائي الكحلي (صورة 1 من أسفل)
                'statistics' => [
                    'volunteers_count' => $totalVolunteers > 0 ? '+' . $totalVolunteers : '', // لو الداتابيز فاضية بيعطي المظهر الجاهز للتصميم
                    'campaigns_count'  => $campaignsCount > 0 ? '+' . $campaignsCount : '',
                    'cities_count'     => $distinctCitiesCount > 0 ? '+' . $distinctCitiesCount : '',
                    'volunteer_hours'  => '', // ساعات التطوع الافتراضية لحين بناء سيكرت الساعات
                ],
                // قائمة الكروت السفلى المخصصة (صورة 2 Featured Campaigns)
                'featured_campaigns' => $campaigns
            ]
        ], 200);
    }
}