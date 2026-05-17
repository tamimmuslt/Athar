<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Organization;
use App\Notifications\OrganizationStatusNotification;

class AdminOrganizationController extends Controller
{

public function updateStatus(Request $request, $id)
{
    $request->validate([
        'status' => 'required|in:approved,rejected'
    ]);

    $org = Organization::findOrFail($id);
    $org->status = $request->status;
    $org->save();

    $org->notify(new OrganizationStatusNotification($request->status));

    return response()->json([
        'message' => 'تم تحديث حالة المؤسسة وإرسال إشعار بالبريد الإلكتروني',
        'status' => $org->status
    ]);
}}
