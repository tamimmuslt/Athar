<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class CampaignReviewController extends Controller
{
    public function store(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'rating'      => 'required|integer|min:1|max:5', 
            'recommend'   => 'required|in:yes,no',
            'feedback'    => 'nullable|string|max:1000' 
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $volunteerId = auth()->id(); 

        $hasParticipated = DB::table('campaign_volunteer')
            ->where('campaign_id', $id)
            ->where('volunteer_id', $volunteerId)
            ->exists();

        if (!$hasParticipated) {
            return response()->json([
                'status' => 'error',
                'message' => 'عذراً، لا يمكنك تقييم حملة لم تشارك بها سابقاً.'
            ], 403);
        }


        DB::table('campaign_reviews')->updateOrInsert(
            [
                'campaign_id'  => $id,
                'volunteer_id' => $volunteerId,
            ],
            [
                'rating'       => $request->rating,
                'recommend'    => $request->recommend, 
                'feedback'     => $request->feedback,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Thank you! Your feedback has been submitted successfully.'
        ], 200);
    }
}