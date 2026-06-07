<?php

namespace App\Http\Controllers\Volunteer;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\Request;

class OrganizationController extends Controller
{
  
    public function index(Request $request)
    {
        $query = Organization::where('status', 'approved'); // فقط المؤسسات المقبولة

        if ($request->has('search') && !is_null($request->search)) {
            $query->where('org_name', 'LIKE', '%' . $request->search . '%');
        }

        $organizations = $query->withCount('campaigns')->get()->map(function ($org) {
            return [
                'id'              => $org->id,
                'org_name'        => $org->org_name,
                'org_description' => $org->org_description,
                'address'         => $org->address, // مثل: Aleppo أو Hama
                'logo_url'        => $org->logo ? asset('storage/' . $org->logo) : asset('storage/default-org.png'),
                'campaigns_count' => $org->campaigns_count, // عدد الحملات
            ];
        });

        return response()->json([
            'message'       => 'تم جلب المؤسسات بنجاح',
            'organizations' => $organizations
        ], 200);
    }

    /**
     * واجهة: تفاصيل المؤسسة + الحملات المميزة التابعة لها (Featured Campaigns)
     * تطابق واجهة تفاصيل الهلال الأحمر السوري التي أرفقتها بالكامل
     */
    public function show($id)
    {
        // جلب المؤسسة مع حملاتها النشطة وحساب عدد الحملات الكلي
        $org = Organization::where('status', 'approved')->withCount('campaigns')->find($id);

        if (!$org) {
            return response()->json(['message' => 'المؤسسة غير موجودة أو غير مفعّلة بعد'], 404);
        }

        // جلب الحملات التابعة للمؤسسة (Featured Campaigns)
        $featuredCampaigns = $org->campaigns()->where('status', 'active')->get()->map(function ($campaign) {
            return [
                'id'                  => $campaign->id,
                'title'               => $campaign->title,
                'location_name'       => $campaign->location_name, // مثل: Hama, al-furkan
                'image_url'           => $campaign->image ? asset('storage/' . $campaign->image) : asset('storage/default-campaign.png'),
                'stars_count'         => 5, // قيمة افتراضية للتقييمات بالواجهة (5 Stars)
                'reviews_count'       => 100, // قيمة افتراضية لعدد المراجعات (100 Reviews)
            ];
        });

        // هنا نجهز العدادات الأربعة الضخمة الموجودة في تصميمك المبدع (+150 Volunteers, +30 Campaign, الخ)
        return response()->json([
            'message' => 'تم جلب تفاصيل المؤسسة بنجاح',
            'organization_details' => [
                'id'              => $org->id,
                'org_name'        => $org->org_name,
                'org_description' => $org->org_description,
                'address'         => $org->address,
                'phone_number'    => $org->phone_number,
                'official_email'  => $org->official_email,
                'website_link'    => $org->website_link,
                'logo_url'        => $org->logo ? asset('storage/' . $org->logo) : asset('storage/default-org.png'),
                
                // الإحصائيات المطلوبة للواجهة
                'stats' => [
                    'volunteers_count' => 150, // هاد الرقم افتراضي لحين بناء جدول طلبات التطوع وربطه
                    'campaigns_count'  => $org->campaigns_count, // عدد حملات المؤسسة الفعلي من القاعدة
                    'cities_count'     => 10,  // عدد المدن الافتراضي
                    'volunteer_hours'  => 200, // إجمالي الساعات الافتراضي
                ]
            ],
            'featured_campaigns' => $featuredCampaigns
        ], 200);
    }
}