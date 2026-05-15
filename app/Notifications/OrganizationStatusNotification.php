<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrganizationStatusNotification extends Notification
{
   use Queueable;

    public $status; // 1. يجب تعريف المتغير هنا

    /**
     * Create a new notification instance.
     */
    public function __construct($status) // 2. يجب استقبال الحالة هنا
    {
        $this->status = $status;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
public function toMail($notifiable)
{
    $url = "http://localhost:3000/organization/dashboard";

    if ($this->status === 'approved') {
      return (new MailMessage)
    ->subject('تفعيل حساب مؤسستكم على منصة أثر')
    ->greeting('السادة في ' . $notifiable->org_name . ' المحترمين،')
    ->line('يسر إدارة منصة أثر إعلامكم باعتماد مؤسستكم رسمياً كشريك استراتيجي في المنصة.')
    ->line('بإمكانكم الآن الوصول الكامل لجميع الأدوات والمزايا التي توفرها لوحة التحكم لإدارة متطوعيكم وتنظيم حملاتكم بكفاءة عالية.')
    ->action('الانتقال إلى لوحة التحكم', $url)
    ->line('نحن هنا لدعمكم في كل خطوة، لا تترددوا في التواصل معنا إذا واجهتكم أي استفسارات.')
    ->salutation('مع خالص التقدير، إدارة منصة أثر');
    } else {
        return (new MailMessage)
            ->subject('بخصوص طلب انضمام مؤسستكم')
            ->greeting('أهلاً ' . $notifiable->org_name)
            ->line('نعتذر منكم، فقد تم رفض طلب الانضمام حالياً.')
            ->line('يمكنكم التواصل مع الإدارة لمزيد من التفاصيل.');
    }
}
     /* Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
