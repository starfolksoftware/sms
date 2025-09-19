<?php

namespace App\Notifications;

use App\Models\Deal;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DealStageChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Deal $deal,
        public string $fromStage,
        public string $toStage
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('filament.admin.resources.deals.view', $this->deal);

        return (new MailMessage)
            ->subject("[Deals] Stage Changed: {$this->deal->title}")
            ->greeting('Deal Update!')
            ->line("Deal '{$this->deal->title}' has moved to a new stage.")
            ->line("Stage Change: {$this->fromStage} → {$this->toStage}")
            ->line("Contact: {$this->deal->contact->name}")
            ->line('Amount: '.number_format($this->deal->amount, 2).' '.$this->deal->currency)
            ->line("Owner: {$this->deal->owner?->name ?? 'Unassigned'}")
            ->action('View Deal', $url)
            ->line('Keep the momentum going!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'deal_id' => $this->deal->id,
            'deal_title' => $this->deal->title,
            'contact_name' => $this->deal->contact->name,
            'amount' => $this->deal->amount,
            'currency' => $this->deal->currency,
            'from_stage' => $this->fromStage,
            'to_stage' => $this->toStage,
            'owner_name' => $this->deal->owner?->name,
        ];
    }

    public function toFilament(): FilamentNotification
    {
        return FilamentNotification::make()
            ->title('Deal Stage Changed!')
            ->body("Deal '{$this->deal->title}' moved from {$this->fromStage} to {$this->toStage}")
            ->info()
            ->actions([
                \Filament\Notifications\Actions\Action::make('view')
                    ->label('View Deal')
                    ->url(route('filament.admin.resources.deals.view', $this->deal)),
            ]);
    }
}