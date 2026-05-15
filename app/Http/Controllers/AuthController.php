<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Volunteer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Notifications\VerifyEmailNotification;
class AuthController extends Controller
{
   public function register(Request $request)
{
    $validator = Validator::make($request->all(), [
        'full_name' => 'required|string',
        'email' => 'required|email|unique:volunteers',
        'password' => 'required|min:8',
    ]);

    if ($validator->fails()) {
        return response()->json($validator->errors(), 422);
    }

    $verificationCode = rand(100000, 999999);

    $volunteer = Volunteer::create([
        'full_name' => $request->full_name,
        'email' => $request->email,
        'password' => Hash::make($request->password),
        'verification_code' => $verificationCode, 
    ]);

$volunteer->notify(new VerifyEmailNotification($verificationCode));

    return response()->json([
        'message' => 'تم إنشاء الحساب بنجاح، يرجى التحقق من بريدك الإلكتروني',
        'email' => $volunteer->email
    ], 201);
}


public function verifyCode(Request $request)
{
    $request->validate([
        'email' => 'required|email',
        'code' => 'required|numeric',
    ]);

    $volunteer = Volunteer::where('email', $request->email)
                          ->where('verification_code', $request->code)
                          ->first();

    if (!$volunteer) {
        return response()->json(['message' => 'الكود غير صحيح أو الإيميل خاطئ'], 401);
    }

    $volunteer->email_verified_at = now();
    $volunteer->verification_code = null;
    $volunteer->save();

    $token = $volunteer->createToken('auth_token')->plainTextToken;

    return response()->json([
        'message' => 'تم تفعيل الحساب بنجاح',
        'access_token' => $token,
        'user' => $volunteer
    ], 200);
}


public function resendCode(Request $request)
{
    $request->validate(['email' => 'required|email']);

    $volunteer = Volunteer::where('email', $request->email)->first();

    if (!$volunteer) {
        return response()->json(['message' => 'المستخدم غير موجود'], 404);
    }

    $newCode = rand(100000, 999999);
    $volunteer->verification_code = $newCode;
    $volunteer->save();

$volunteer->notify(new VerifyEmailNotification($newCode));

    return response()->json([
        'message' => 'تم إعادة إرسال رمز التحقق إلى بريدك الإلكتروني'
    ], 200);
}


public function login(Request $request)
{
    $validator = Validator::make($request->all(), [
        'email' => 'required|email',
        'password' => 'required',
    ]);

    if ($validator->fails()) {
        return response()->json($validator->errors(), 422);
    }

    $volunteer = Volunteer::where('email', $request->email)->first();

    if (!$volunteer || !Hash::check($request->password, $volunteer->password)) {
        return response()->json(['message' => 'بيانات الدخول غير صحيحة'], 401);
    }

    if ($volunteer->email_verified_at == null) {
        return response()->json(['message' => 'يرجى تفعيل حسابك أولاً'], 403);
    }

    $token = $volunteer->createToken('auth_token')->plainTextToken;

    return response()->json([
        'message' => 'تم تسجيل الدخول بنجاح',
        'access_token' => $token,
        'token_type' => 'Bearer',
        'user' => $volunteer
    ]);
}


public function adminLogin(Request $request)
{
    $validator = Validator::make($request->all(), [
        'email' => 'required|email',
        'password' => 'required',
    ]);

    if ($validator->fails()) {
        return response()->json($validator->errors(), 422);
    }

    // 1. فحص هل المستخدم موجود أصلاً
    $admin = User::where('email', $request->email)->first();

    if (!$admin) {
        return response()->json(['message' => 'الإيميل غير موجود في قاعدة البيانات'], 404);
    }

    // 2. فحص كلمة المرور مع طباعة قيمتها في حال الفشل (للتأكد فقط)
    if (!Hash::check($request->password, $admin->password)) {
        // إذا وصلت لهنا، يعني الإيميل صح بس التشفير في القاعدة لسه فيه مشكلة
        return response()->json([
            'message' => 'كلمة المرور غير مطابقة للمشفرة في القاعدة',
            'debug_provided_password' => $request->password,
            'debug_stored_hash' => $admin->password // قارن هذا النص بما وضعته في SQL
        ], 401);
    }

    // 3. إصدار التوكن
    $token = $admin->createToken('admin_token')->plainTextToken;

    return response()->json([
        'message' => 'أهلاً بك يا أدمن، تم تسجيل الدخول',
        'access_token' => $token,
        'user' => $admin
    ]);
}

}
