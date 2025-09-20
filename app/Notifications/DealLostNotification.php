<?php

namespace App\Notifications;

use App\Models\Deal;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DealLostNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Deal $deal) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('filament.admin.resources.deals.view', $this->deal);

        return (new MailMessage)
            ->subject("Deal Lost: {$this->deal->title}")
            ->greeting('Deal Update')
            ->line("Deal '{$this->deal->title}' has been marked as lost.")
            ->line("Contact: {$this->deal->contact->name}")
            ->line('Amount: '.number_format($this->deal->amount, 2).' '.$this->deal->currency)
            ->line("Stage: {$this->deal->stage}")
            ->line("Reason: {$this->deal->lost_reason}")
            ->action('View Deal', $url)
            ->line('This information can help improve future sales efforts.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'deal_id' => $this->deal->id,
            'deal_title' => $this->deal->title,
            'contact_name' => $this->deal->contact->name,
            'amount' => $this->deal->amount,
            'currency' => $this->deal->currency,
            'stage' => $this->deal->stage,
            'lost_reason' => $this->deal->lost_reason,
        ];
    }

    public function toFilament(): FilamentNotification
    {
        return FilamentNotification::make()
            ->title('Deal Lost')
            ->body("Deal '{$this->deal->title}' has been marked as lost. Reason: {$this->deal->lost_reason}")
            ->warning()
            ->actions([
                Action::make('view')
                    ->label('View Deal')
                    ->url(route('filament.admin.resources.deals.view', $this->deal)),
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return $this->toFilament()->getDatabaseMessage();
    }
}
