<?php

namespace App\Notifications;

use App\Models\CommitteePost;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use NotificationChannels\WebPush\WebPushMessage;
use NotificationChannels\WebPush\WebPushChannel;

class NewCommitteePost extends Notification
{
    protected $post;

    /**
     * Create a new notification instance.
     */
    public function __construct(CommitteePost $post)
    {
        $this->post = $post;
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
        return (new WebPushMessage)
            ->title('Nuovo post nel comitato: ' . $this->post->committee->name)
            ->body($this->post->author->name . ' ha pubblicato: "' . $this->post->title . '"')
            ->icon('/favicon.png')
            ->data([
                'url' => '/me/committees/' . $this->post->committee_id,
                'post_id' => $this->post->id,
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
            'title' => 'Nuovo post nel comitato: ' . $this->post->committee->name,
            'body' => $this->post->author->name . ' ha pubblicato: "' . $this->post->title . '"',
            'post_id' => $this->post->id,
            'committee_id' => $this->post->committee_id,
            'url' => '/me/committees/' . $this->post->committee_id,
            'type' => 'committee_post',
        ];
    }
}
