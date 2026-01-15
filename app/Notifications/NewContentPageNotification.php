<?php

namespace App\Notifications;

use App\Models\ContentPage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;
use NotificationChannels\WebPush\WebPushChannel;

class NewContentPageNotification extends Notification
{
    use Queueable;

    protected ContentPage $page;

    public function __construct(ContentPage $page)
    {
        $this->page = $page;
    }

    public function via($notifiable): array
    {
        return ['database', WebPushChannel::class];
    }

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

    public function toArray($notifiable): array
    {
        // Everyone goes to the PWA /me view for information pages
        $url = '/me/content/' . $this->page->slug;

        return [
            'title' => 'Nuovo contenuto: ' . $this->page->title,
            'body' => $this->page->excerpt ?: 'È stata pubblicata una nuova pagina informativa. Leggi di più!',
            'page_id' => $this->page->id,
            'slug' => $this->page->slug,
            'url' => $url,
            'type' => 'content_page',
        ];
    }
}
