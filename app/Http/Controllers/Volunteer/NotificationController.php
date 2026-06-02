<?php

namespace App\Http\Controllers\Volunteer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * 1. جلب جميع الإشعارات الخاصة بالمتطوع الحالي
     */
    public function index()
    {
        $volunteer = auth()->user();

        // جلب الإشعارات مفسرة وجاهزة مع التوقيت الصديق للموبايل (2 hours ago)
        $notifications = $volunteer->notifications->map(function ($notification) {
            return [
                'id' => $notification->id,
                'type' => $notification->type,
                'data' => $notification->data, // تحتوي على مصفوفة البيانات مثل عنوان الحملة ورسالتها
                'read_at' => $notification->read_at,
                'is_read' => !is_null($notification->read_at), // علم لمطور الفلاتر ليتحكم بالنقطة الحمراء 🔴
                'created_at_human' => $notification->created_at->diffForHumans(), // سيرجع مثلاً "2 hours ago" أو "منذ ساعتين" حسب لغة التطبيق
            ];
        });

        return response()->json([
            'message' => 'تم جلب الإشعارات بنجاح',
            'unread_count' => $volunteer->unreadNotifications->count(), // عدد الإشعارات غير المقروءة لتظهر فوق أيقونة الجرس 🔔
            'notifications' => $notifications
        ], 200);
    }

    /**
     * 2. تعيين إشعار محدد كمقروء (عندما يضغط المتطوع على إشعار معين)
     */
    public function markAsRead($id)
    {
        $notification = auth()->user()->notifications()->find($id);

        if (!$notification) {
            return response()->json(['message' => 'الإشعار غير موجود'], 404);
        }

        $notification->markAsRead();

        return response()->json([
            'message' => 'تم تعيين الإشعار كمقروء بنجاح'
        ], 200);
    }

    /**
     * 3. تعيين كل الإشعارات كمقروءة (عند الضغط على زر Mark all as read في واجهتك)
     */
    public function markAllAsRead()
    {
        auth()->user()->unreadNotifications->markAsRead();

        return response()->json([
            'message' => 'تم تعيين جميع الإشعارات كمقروءة بنجاح'
        ], 200);
    }
}