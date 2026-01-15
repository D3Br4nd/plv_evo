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
    public function toWebPush($notifiable, $notification): WebPushMessage
    {
        $data = $this->toArray($notifiable);

        return (new WebPushMessage)
            ->title($data['title'])
            ->body($data['body'])
            ->icon('/favicon.png')
            ->data(['url' => $data['url']])
            ->badge('/favicon.png');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray($notifiable): array
    {
        $role = (isset($notifiable->role) && $notifiable->role instanceof \UnitEnum) 
            ? $notifiable->role->value 
            : ($notifiable->role ?? null);
            
        $isAdmin = in_array($role, ['super_admin', 'admin']);
        
        // In admin there is no "post show", but we use this pattern for the middleware to redirect to the PWA post view
        $url = $isAdmin 
            ? "/admin/committees/{$this->post->committee_id}/posts/{$this->post->id}" 
            : "/me/committees/posts/{$this->post->id}";

        return [
            'title' => $this->post->title,
            'body' => $this->post->author->name . ' in ' . $this->post->committee->name,
            'post_id' => $this->post->id,
            'committee_id' => $this->post->committee_id,
            'url' => $url,
            'type' => 'committee_post',
        ];
    }
}
