<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class AccountInvitation extends ResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Sua conta no '.config('app.name').' foi criada')
            ->greeting('Olá, '.$notifiable->name.'!')
            ->line('Uma conta de acesso foi criada para você no '.config('app.name').'.')
            ->line('Para concluir seu acesso, defina sua senha pelo botão abaixo.')
            ->action('Definir minha senha', $this->resetUrl($notifiable))
            ->line('Este link expira em '.config('auth.passwords.'.config('fortify.passwords').'.expire').' minutos.')
            ->line('Se você não reconhece esta conta, entre em contato com a instituição.');
    }
}
