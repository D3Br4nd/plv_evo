<?php

namespace App\Notifications;

use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;
use NotificationChannels\WebPush\WebPushChannel;

class ProjectUpdateNotification extends Notification
{
    use Queueable;

    protected $project;
    protected $oldStatus;
    protected $isNew;

    public function __construct(Project $project, $oldStatus = null, $isNew = false)
    {
        $this->project = $project;
        $this->oldStatus = $oldStatus;
        $this->isNew = $isNew;
    }

    public function via($notifiable): array
    {
        return ['database', WebPushChannel::class];
    }

    public function toArray($notifiable): array
    {
        $statusLabel = $this->getStatusLabel($this->project->status);
        $title = $this->project->title; // Strictly project title
            
        $body = $this->isNew
            ? "È stato creato un nuovo task in stato {$statusLabel}."
            : "Lo stato è cambiato in {$statusLabel}.";

        $role = (isset($notifiable->role) && $notifiable->role instanceof \UnitEnum) 
            ? $notifiable->role->value 
            : ($notifiable->role ?? null);
            
        $isAdmin = in_array($role, ['super_admin', 'admin']);

        return [
            'title' => $title,
            'body' => $body,
            'url' => $isAdmin ? "/admin/projects/{$this->project->id}" : "/me/projects/{$this->project->id}",
            'type' => 'project_update',
            'project_id' => $this->project->id,
            'status' => $this->project->status,
        ];
    }

    public function toWebPush($notifiable, $notification)
    {
        $data = $this->toArray($notifiable);

        return (new WebPushMessage)
            ->title($data['title'])
            ->body($data['body'])
            ->data(['url' => $data['url']])
            ->badge('/icon-192x192.png')
            ->icon('/icon-192x192.png');
    }

    protected function getStatusLabel($status)
    {
        return match ($status) {
            'todo' => 'Da Fare',
            'in_progress' => 'In Corso',
            'done' => 'Completato',
            default => $status,
        };
    }
}
