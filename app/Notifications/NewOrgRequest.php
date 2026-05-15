<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\Organization;

class NewOrgRequest extends Notification
{
    use Queueable;

    public $organization;

    /**
     * نمرر موديل المؤسسة عند استدعاء الإشعار لكي نصل لبياناتها
     */
    public function __construct(Organization $organization)
    {
        $this->organization = $organization;
    }

    /**
     * نحدد القناة بأنها قاعدة البيانات فقط لتظهر في الداشبورد
     */
    public function via($notifiable): array
    {
        return ['database'];
    }

    /**
     * البيانات التي سيتم تخزينها في جدول الـ notifications
     */
    public function toArray($notifiable): array
    {
        return [
            'org_id'     => $this->organization->id,
            'org_name'   => $this->organization->org_name,
            'message'    => 'مؤسسة جديدة تطلب الانضمام: ' . $this->organization->org_name,
            'action_url' => '/admin/organizations/pending', // هذا الرابط ستستخدمه في فرونت الأدمين
            'type'       => 'new_registration'
        ];
    }
}