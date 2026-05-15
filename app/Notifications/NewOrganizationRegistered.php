<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Organization;

class NewOrganizationRegistered extends Notification
{
    use Queueable;

    public $organization;

    public function __construct(Organization $organization)
    {
        $this->organization = $organization;
    }

    public function via($notifiable): array
    {
        // سنرسل للأدمن إيميل + نخزن التنبيه في قاعدة البيانات
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('طلب انضمام مؤسسة جديدة - أثر')
            ->line('هناك مؤسسة جديدة قامت بالتسجيل وتنتظر المراجعة.')
            ->line('اسم المؤسسة: ' . $this->organization->org_name)
            ->line('الإيميل الرسمي: ' . $this->organization->official_email)
            ->action('عرض الطلبات المعلقة', url('/admin/pending-organizations'))
            ->line('يرجى اتخاذ إجراء بالقبول أو الرفض.');
    }

    public function toArray($notifiable): array
    {
        return [
            'org_id' => $this->organization->id,
            'org_name' => $this->organization->org_name,
            'message' => 'قامت مؤسسة جديدة بالتسجيل'
        ];
    }
}