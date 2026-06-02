<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Volunteer;
use App\Models\User;
use App\Models\VolunteerHour;
use App\Models\Certificate;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
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
    $isProfileCompleted =!is_null(value: $volunteer->age) && !is_null($volunteer->gender);
    return response()->json([
        'message' => 'تم تفعيل الحساب بنجاح',
        'is_profile_completed' => $isProfileCompleted, 
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
$isProfileCompleted = !is_null($volunteer->age) && !is_null($volunteer->gender);
    return response()->json([
        'message' => 'تم تسجيل الدخول بنجاح',
        'access_token' => $token,
        'is_profile_completed' => $isProfileCompleted, 
        'token_type' => 'Bearer',
        'user' => $volunteer
    ]);
}
public function logout(Request $request)
{
    $request->user()->currentAccessToken()->delete();

    return response()->json([
        'message' => 'تم تسجيل الخروج بنجاح وتدمير جلسة العمل الحالية'
    ], 200);
}

public function forgotPassword(request $request){

$validatore=validator::make($request->all(),[
    'email'=>'required|email',
]);

  if ($validatore->fails()){
    return response()->json($validatore->errors(),422);
  }

  $volunteer=Volunteer::where('email',$request->email)->first();

  if(!$volunteer){
    return response()->json(['message'=>'بيانات الدخول غير صحيحة '],404);
  }

$code = rand(100000,999999);
$volunteer->verification_code=$code;
$volunteer->save();

$volunteer->notify(new VerifyEmailNotification($code));

   return response()->json([
            'message' => 'تم إرسال رمز التحقق بنجاح إلى بريدك الإلكتروني',
            'email'   => $volunteer->email
        ], 200);
    }
public function verifyResetCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'code'  => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $volunteer = Volunteer::where('email', $request->email)
                              ->where('verification_code', $request->code)
                              ->first();

        if (!$volunteer) {
            return response()->json(['message' => 'رمز التحقق غير صحيح أو منتهي الصلاحية'], 401);
        }

        return response()->json([
            'message' => 'تم التحقق من الرمز بنجاح، يمكنك الآن تعيين كلمة مرور جديدة'
        ], 200);
    }

    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email',
            'code'     => 'required|numeric',
            'password' => 'required|string|min:8|confirmed', 
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $volunteer = Volunteer::where('email', $request->email)
                              ->where('verification_code', $request->code)
                              ->first();

        if (!$volunteer) {
            return response()->json(['message' => 'فشلت العملية، الرمز أو البريد غير صالح'], 401);
        }

        $volunteer->update([
            'password'          => Hash::make($request->password),
            'verification_code' => null, // تصفير الحقل للأمان لمنع إعادة استخدام الكود
        ]);

        return response()->json([
            'message' => 'تم إعادة تعيين كلمة المرور بنجاح، يمكنك الآن تسجيل الدخول'
        ], 200);
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

    $admin = User::where('email', $request->email)->first();

    if (!$admin) {
        return response()->json(['message' => 'الإيميل غير موجود في قاعدة البيانات'], 404);
    }

    if (!Hash::check($request->password, $admin->password)) {
        return response()->json([
            'message' => 'كلمة المرور غير مطابقة للمشفرة في القاعدة',
            'debug_provided_password' => $request->password,
            'debug_stored_hash' => $admin->password 
        ], 401);
    }

    $token = $admin->createToken('admin_token')->plainTextToken;

    return response()->json([
        'message' => 'أهلاً بك يا أدمن، تم تسجيل الدخول',
        'access_token' => $token,
        'user' => $admin
    ]);
}

public function getProfile()
{
    $volunteer = auth()->user();

    return response()->json([
        'message' => 'تم جلب بيانات الملف الشخصي بنجاح',
        'user' => $volunteer
    ], 200);
}

public function updateProfile(Request $request)
{
    $volunteer = auth()->user();

    $validator = Validator::make($request->all(), [
        'full_name'    => 'nullable|string|max:255',
        'phone'        => 'nullable|string|max:20',
        'city'         => 'nullable|string|max:100',
        'gender'       => 'nullable|in:male,female',
        'bio'          => 'nullable|string|max:500',
        'age'          => 'nullable|integer|min:15|max:90',
    ]);

    if ($validator->fails()) {
        return response()->json($validator->errors(), 422);
    }

    $data = array_filter($request->only([
        'full_name', 'phone', 'city', 'gender', 'bio', 'age'
    ]), function ($value) {
        return !is_null($value); 
    });

    if (!empty($data)) {
        $volunteer->update($data);
    }

    return response()->json([
        'message' => 'تم تحديث البيانات الشخصية بنجاح',
        'user' => $volunteer
    ], 200);
}

public function getVolunteerHours()
{
    $volunteer = auth()->user();

    $hoursLog = VolunteerHour::where('volunteer_id', $volunteer->id)
       ->orderBy('date', 'desc')
       ->get();

    $totalVerifiedHours = VolunteerHour::where('volunteer_id', $volunteer->id)
         ->where('status', 'verified')
         ->sum('hours');

    return response()->json([
        'message' => 'تم جلب سجل ساعات التطوع بنجاح',
        'total_verified_hours' => $totalVerifiedHours, 
        'hours_log' => $hoursLog                     
    ], 200);
}

public function getCertificates()
{
    $volunteer = auth()->user();

    $certificates = Certificate::where('volunteer_id', $volunteer->id)
       ->orderBy('date', 'desc')
       ->paginate(5);

    $certificates->getCollection()->transform(function ($certificate) {
        $certificate->certificate_file_url = asset('storage/' . $certificate->certificate_file);
        return $certificate;
    });

    return response()->json([
        'message' => 'تم جلب الشهادات بنجاح',
        'certificates' => $certificates
    ], 200);
}
}
