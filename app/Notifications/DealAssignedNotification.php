<?php

namespace App\Notifications;

use App\Models\Deal;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DealAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Deal $deal,
        public ?User $oldOwner,
        public User $newOwner
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('filament.admin.resources.deals.view', $this->deal);
        $oldOwnerName = $this->oldOwner?->name ?? 'Unassigned';

        return (new MailMessage)
            ->subject("[Deals] Deal Reassigned: {$this->deal->title}")
            ->greeting('Deal Assignment Update!')
            ->line("Deal '{$this->deal->title}' has been reassigned.")
            ->line("Previous Owner: {$oldOwnerName}")
            ->line("New Owner: {$this->newOwner->name}")
            ->line("Contact: {$this->deal->contact->name}")
            ->line('Amount: '.number_format($this->deal->amount, 2).' '.$this->deal->currency)
            ->line("Stage: {$this->deal->stage}")
            ->action('View Deal', $url)
            ->line('Stay engaged with your deals!');
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
            'old_owner_name' => $this->oldOwner?->name,
            'new_owner_name' => $this->newOwner->name,
        ];
    }

    public function toFilament(): FilamentNotification
    {
        $oldOwnerName = $this->oldOwner?->name ?? 'Unassigned';
        
        return FilamentNotification::make()
            ->title('Deal Reassigned!')
            ->body("Deal '{$this->deal->title}' reassigned from {$oldOwnerName} to {$this->newOwner->name}")
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