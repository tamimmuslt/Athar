<?php


namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VerifyEmailNotification extends Notification
{
    use Queueable;

    protected $code;

    public function __construct($code)
    {
        $this->code = $code;
    }

    public function via($notifiable): array
    {
        return ['mail']; // سنعتمد الإيميل حالياً
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('رمز التحقق الخاص بمشروع أثر')
            ->greeting('أهلاً بك في أثر!')
            ->line('شكراً لتسجيلك معنا. رمز التحقق الخاص بك هو:')
            ->line('**' . $this->code . '**') // عرض الكود بشكل بارز
            ->line('يرجى إدخال هذا الرمز في التطبيق لإتمام عملية إنشاء الحساب.');
    }
}