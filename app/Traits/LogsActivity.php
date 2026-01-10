<?php

namespace App\Traits;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

trait LogsActivity
{
    protected static function bootLogsActivity()
    {
        static::created(function (Model $model) {
            self::logActivity($model, 'created');
        });

        static::updated(function (Model $model) {
            self::logActivity($model, 'updated');
        });

        static::deleted(function (Model $model) {
            self::logActivity($model, 'deleted');
        });
    }

    protected static function logActivity(Model $model, string $action)
    {
        // Don't log if there is no authenticated user (e.g. during seeding or CLI)
        // unless we want to log system actions too.
        // Only log if there is an authenticated user
        if (!auth()->check()) {
            return;
        }

        $actorId = auth()->id();
        
        $summary = self::getActivitySummary($model, $action);

        ActivityLog::create([
            'actor_user_id' => $actorId,
            'action' => $action,
            'subject_type' => get_class($model),
            'subject_id' => $model->id,
            'summary' => $summary,
            'meta' => [
                'attributes' => $model->getAttributes(),
                'changes' => $action === 'updated' ? $model->getChanges() : null,
            ],
        ]);
    }

    protected static function getActivitySummary(Model $model, string $action): string
    {
        $class = get_class($model);
        $map = [
            \App\Models\Event::class => 'evento',
            \App\Models\Committee::class => 'comitato',
            \App\Models\CommitteePost::class => 'contenuto comitato',
            \App\Models\BroadcastNotification::class => 'notifica broadcast',
            \App\Models\User::class => 'socio',
            \App\Models\Membership::class => 'tessera',
            \App\Models\Project::class => 'task/progetto',
            \App\Models\EventCheckin::class => 'check-in evento',
            \App\Models\MemberInvitation::class => 'invito socio',
            \App\Models\ContentPage::class => 'pagina contenuto',
        ];

        $modelName = $map[$class] ?? class_basename($model);
        
        if ($class === \App\Models\CommitteeUser::class) {
            $model->loadMissing(['committee', 'user']);
            $userName = $model->user?->name ?? 'Socio sconosciuto';
            $committeeName = $model->committee?->name ?? 'comitato sconosciuto';
            
            return $action === 'created' 
                ? "Socio {$userName} aggiunto al comitato {$committeeName}"
                : "Socio {$userName} rimosso dal comitato {$committeeName}";
        }

        $title = $model->title ?? $model->name ?? $model->id;

        switch ($action) {
            case 'created':
                return "Creato {$modelName}: {$title}";
            case 'updated':
                return "Aggiornato {$modelName}: {$title}";
            case 'deleted':
                return "Eliminato {$modelName}: {$title}";
            default:
                return ucfirst($action) . " {$modelName}: {$title}";
        }
    }
}
