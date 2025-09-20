<?php

namespace App\Notifications;

use App\Models\Deal;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Filament\Actions\Action; // Added this line to import Action

class DealWonNotification extends Notification implements ShouldQueue
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
            ->subject("Deal Won: {$this->deal->title}")
            ->greeting('Great News!')
            ->line("Deal '{$this->deal->title}' has been marked as won!")
            ->line("Contact: {$this->deal->contact->name}")
            ->line('Won Amount: '.number_format($this->deal->won_amount, 2).' '.$this->deal->currency)
            ->line("Stage: {$this->deal->stage}")
            ->action('View Deal', $url)
            ->line('Congratulations on closing this deal!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'deal_id' => $this->deal->id,
            'deal_title' => $this->deal->title,
            'contact_name' => $this->deal->contact->name,
            'won_amount' => $this->deal->won_amount,
            'currency' => $this->deal->currency,
            'stage' => $this->deal->stage,
        ];
    }

    public function toFilament(): FilamentNotification
    {
        return FilamentNotification::make()
            ->title('Deal Won!')
            ->body("Deal '{$this->deal->title}' has been marked as won for ".number_format($this->deal->won_amount, 2).' '.$this->deal->currency)
            ->success()
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
