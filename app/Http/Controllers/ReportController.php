<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class ReportController extends Controller
{
    /**
     * إرسال بلاغ جديد (حملة أو مؤسسة)
     */
    public function store(Request $request): JsonResponse
    {
        // 1. التحقق من صحة المدخلات القادمة من الفرونت إند بناءً على نوع البلاغ
        $validator = Validator::make($request->all(), [
            'report_type'     => 'required|in:campaign,organization',
            'campaign_id'     => 'required_if:report_type,campaign|exists:campaigns,id',
            'organization_id' => 'required_if:report_type,organization|exists:organizations,id',
            'reason'          => 'required|string|max:255',
            'description'     => 'required|string',
            'evidence'        => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:4096', // حد أقصى 4 ميجا
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        // 2. معالجة رفع ملف الإثبات (Evidence) إن وجد
        $evidencePath = null;
        if ($request->hasFile('evidence')) {
            $evidencePath = $request->file('evidence')->store('reports/evidence', 'public');
        }

        // 3. تخزين البلاغ في قاعدة البيانات
        $report = DB::table('reports')->insert([
            'volunteer_id'    => auth()->id(), // معرف المتطوع الحالي من الـ Token
            'report_type'     => $request->report_type,
            'campaign_id'     => $request->report_type === 'campaign' ? $request->campaign_id : null,
            'organization_id' => $request->report_type === 'organization' ? $request->organization_id : null,
            'reason'          => $request->reason,
            'description'     => $request->description,
            'evidence'        => $evidencePath,
            'status'          => 'pending',
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

       
        return response()->json([
            'status'  => 'success',
            'message' => $request->report_type === 'organization' 
                ? 'Your report has been submitted to the Super Admin successfully.' 
                : 'Your report on the campaign has been submitted to the Organization successfully.'
        ], 201);
    }
}