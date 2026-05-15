<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\Organization;
use App\Models\User; 
use App\Notifications\NewOrganizationRegistered;
use App\Notifications\NewOrgRequest;
class OrganizationAuthController extends Controller
{
    public function register(Request $request)
{
    $validator = Validator::make($request->all(), [
        'org_name' => 'required|string|max:255',
        'official_email' => 'required|email|unique:organizations,official_email',
        'password' => 'required|min:8',
        'phone_number' => 'required',
        'address' => 'required',
        'org_description' => 'required',
        'document' => 'required|file|mimes:pdf,jpg,png|max:2048', // الوثائق المطلوبة
    ]);

    if ($validator->fails()) {
        return response()->json($validator->errors(), 422);
    }

    // رفع الملف وتخزينه
    $documentPath = $request->file('document')->store('verification_documents', 'public');

    $organization = Organization::create([
        'org_name' => $request->org_name,
        'official_email' => $request->official_email,
        'password' => Hash::make($request->password),
        'phone_number' => $request->phone_number,
        'address' => $request->address,
        'org_description' => $request->org_description,
        'verification_document' => $documentPath,
        'status' => 'pending', 
    ]);
$admin = User::first(); 

    if ($admin) {
        $admin->notify(new NewOrgRequest($organization));
    }
    return response()->json([
        'message' => 'Registration Request Submitted! Your account is under review.',
    ], 201);
}

public function login(Request $request)
{
    $validator = Validator::make($request->all(), [
        'official_email' => 'required|email',
        'password'       => 'required',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'message' => 'خطأ في البيانات المدخلة',
            'errors'  => $validator->errors()
        ], 422);
    }

    // البحث عن المؤسسة
    $org = Organization::where('official_email', $request->official_email)->first();

    // التأكد من صحة بيانات الدخول
   if (!$org || !Hash::check($request->password, $org->password)) {
    return response()->json(['message' => 'بيانات الدخول خاطئة'], 401);
}

    // التحقق من حالة الحساب
    if ($org->status === 'pending') {
        return response()->json(['message' => 'حسابك لا يزال قيد المراجعة من قبل الإدارة'], 403);
    }

    if ($org->status === 'rejected') {
        return response()->json(['message' => 'تم رفض طلب انضمام مؤسستكم، يرجى التواصل معنا'], 403);
    }

    // إنشاء التوكن في حال كان الحساب مقبولاً (approved)
    $token = $org->createToken('org_token')->plainTextToken;

    return response()->json([
        'message'      => 'تم تسجيل الدخول بنجاح',
        'access_token' => $token,
        'organization' => $org
    ]);
}
}