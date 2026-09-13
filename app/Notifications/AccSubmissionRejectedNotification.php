<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccSubmissionRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $submissionId, public string $rejectionReason) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Comprovante de ACC rejeitado')
            ->greeting('Olá!')
            ->line("O comprovante #{$this->submissionId} foi rejeitado.")
            ->line("Motivo: {$this->rejectionReason}")
            ->action('Consultar comprovante', route('submissions.show', $this->submissionId))
            ->line('Se necessário, envie um novo comprovante como uma nova submissão.');
    }
}
