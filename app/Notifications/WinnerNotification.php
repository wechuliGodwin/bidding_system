<?php

namespace App\Notifications;

use App\Models\Asset;
use App\Models\DisposalEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WinnerNotification extends Notification
{
    use Queueable;

    protected $asset;
    protected $event;
    protected $winningAmount;

    /**
     * Create a new notification instance.
     */
    public function __construct(Asset $asset, DisposalEvent $event, $winningAmount)
    {
        $this->asset = $asset;
        $this->event = $event;
        $this->winningAmount = $winningAmount;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Congratulations! You Won a Bid')
            ->greeting('Congratulations!')
            ->line("You have won the bid for: {$this->asset->name}")
            ->line("Event: {$this->event->name}")
            ->line("Your winning bid: ₦" . number_format($this->winningAmount, 2))
            ->line('Please proceed with payment to complete your purchase.')
            ->action('View Details', url('/bidder/my-bids'))
            ->line('Thank you for participating in our bidding event!');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'asset_id' => $this->asset->id,
            'asset_name' => $this->asset->name,
            'event_id' => $this->event->id,
            'event_name' => $this->event->name,
            'winning_amount' => $this->winningAmount,
            'message' => "You won the bid for {$this->asset->name} at ₦" . number_format($this->winningAmount, 2),
        ];
    }
}
