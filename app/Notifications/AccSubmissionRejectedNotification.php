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
            ->subject('Documento de ACC rejeitado')
            ->greeting('Olá!')
            ->line("O documento #{$this->submissionId} foi rejeitado.")
            ->line("Motivo: {$this->rejectionReason}")
            ->action('Consultar documento', route('submissions.show', $this->submissionId))
            ->line('Se necessário, envie um novo documento como uma nova submissão.');
    }
}
