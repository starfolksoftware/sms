<?php

namespace App\Notifications;

use App\Models\Deal;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DealCreatedNotification extends Notification implements ShouldQueue
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
            ->subject("[Deals] New Deal Created: {$this->deal->title}")
            ->greeting('New Deal Alert!')
            ->line("A new deal '{$this->deal->title}' has been created.")
            ->line("Contact: {$this->deal->contact->name}")
            ->line('Amount: '.number_format($this->deal->amount, 2).' '.$this->deal->currency)
            ->line("Stage: {$this->deal->stage}")
            ->line("Owner: {$this->deal->owner?->name ?? 'Unassigned'}")
            ->action('View Deal', $url)
            ->line('Stay on top of your pipeline!');
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
            'owner_name' => $this->deal->owner?->name,
        ];
    }

    public function toFilament(): FilamentNotification
    {
        return FilamentNotification::make()
            ->title('New Deal Created!')
            ->body("Deal '{$this->deal->title}' has been created for ".number_format($this->deal->amount, 2).' '.$this->deal->currency)
            ->success()
            ->actions([
                \Filament\Notifications\Actions\Action::make('view')
                    ->label('View Deal')
                    ->url(route('filament.admin.resources.deals.view', $this->deal)),
            ]);
    }
}