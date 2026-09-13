<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccReviewStartedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $submissionId) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Análise de comprovante iniciada')
            ->greeting('Olá!')
            ->line("A análise acadêmica do comprovante #{$this->submissionId} foi iniciada pela coordenação.")
            ->action('Consultar comprovante', route('submissions.show', $this->submissionId))
            ->line('Você receberá uma nova mensagem quando houver uma decisão.');
    }
}
