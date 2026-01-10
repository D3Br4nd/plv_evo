<?php

namespace App\Notifications;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;
use NotificationChannels\WebPush\WebPushChannel;

class NewEventNotification extends Notification
{
    use Queueable;

    protected Event $event;

    /**
     * Create a new notification instance.
     */
    public function __construct(Event $event)
    {
        $this->event = $event;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', WebPushChannel::class];
    }

    /**
     * Get the web push representation of the notification.
     */
    public function toWebPush(object $notifiable, $notification): WebPushMessage
    {
        $startDate = $this->event->start_date->format('d/m/Y H:i');
        
        return (new WebPushMessage)
            ->title('Nuovo Evento: ' . $this->event->title)
            ->body("In programma il {$startDate}. Scopri i dettagli!")
            ->icon('/favicon.png')
            ->data([
                'url' => '/me/events',
                'event_id' => $this->event->id,
            ])
            ->badge('/favicon.png');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Nuovo Evento: ' . $this->event->title,
            'body' => "L'evento \"" . $this->event->title . "\" è stato aggiunto al calendario per il " . $this->event->start_date->format('d/m/Y H:i') . ".",
            'event_id' => $this->event->id,
            'url' => '/me/events',
            'type' => 'event',
        ];
    }
}
