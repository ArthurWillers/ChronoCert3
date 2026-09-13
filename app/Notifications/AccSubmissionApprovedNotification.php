<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccSubmissionApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $submissionId, public string $approvedHours) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Comprovante de ACC aprovado')
            ->greeting('Olá!')
            ->line("O comprovante #{$this->submissionId} foi aprovado.")
            ->line("Horas contabilizadas: {$this->approvedHours}.")
            ->action('Consultar comprovante', route('submissions.show', $this->submissionId))
            ->line('Consulte o extrato de ACC para acompanhar sua carga horária.');
    }
}
