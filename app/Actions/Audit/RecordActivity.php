<?php

namespace App\Actions\Audit;

use App\Enums\AuditEvent;
use App\Enums\AuditSource;
use App\Models\Affiliation;
use App\Models\AuditActivity;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use RuntimeException;

class RecordActivity
{
    /**
     * Record one immutable, privacy-safe audit event.
     *
     * @param  array<string, Model|array<string, mixed>>  $references
     * @param  array<string, mixed>  $changes
     */
    public function execute(
        AuditEvent $event,
        Model $subject,
        ?User $causer = null,
        ?Affiliation $activeAffiliation = null,
        ?int $contextCourseId = null,
        array $references = [],
        array $changes = [],
        ?string $reason = null,
        AuditSource $source = AuditSource::Web,
        ?string $sourceDetail = null,
    ): AuditActivity {
        $activityReferences = $references;

        if ($activeAffiliation !== null && ! array_key_exists('actor_affiliation', $activityReferences)) {
            $activityReferences = [
                'actor_affiliation' => $activeAffiliation,
                ...$activityReferences,
            ];
        }

        $properties = [
            'source' => array_filter([
                'type' => $source->value,
                'detail' => $sourceDetail,
            ], static fn (mixed $value): bool => $value !== null),
            'context' => array_filter([
                'course_id' => $contextCourseId,
                'actor_affiliation_id' => $activeAffiliation?->getKey(),
            ], static fn (mixed $value): bool => $value !== null),
            'references' => [
                'subject' => $this->snapshot($subject),
                ...$this->snapshots($activityReferences),
            ],
            'changes' => $this->safeChanges($changes),
        ];

        if (filled($reason)) {
            $properties['reason'] = $this->redactSensitiveText($reason);
        }

        $logger = activity()
            ->useLog($event->area())
            ->performedOn($subject)
            ->event($event->value)
            ->withChanges([])
            ->withProperties($properties)
            ->tap(function (AuditActivity $activity) use ($contextCourseId): void {
                $activity->context_course_id = $contextCourseId;
            });

        if ($causer === null) {
            $logger->causedByAnonymous();
        } else {
            $logger->causedBy($causer);
        }

        $activity = $logger->log($event->label());

        if (! $activity instanceof AuditActivity) {
            throw new RuntimeException('O registro de Activity Log não usa o modelo AuditActivity configurado.');
        }

        return $activity;
    }

    /**
     * @param  array<string, Model|array<string, mixed>>  $references
     * @return array<string, array<string, mixed>>
     */
    private function snapshots(array $references): array
    {
        $snapshots = [];

        foreach ($references as $name => $reference) {
            $snapshots[$name] = $reference instanceof Model
                ? $this->snapshot($reference)
                : $this->withoutSensitiveValues($reference);
        }

        return $snapshots;
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshot(Model $model): array
    {
        if ($model instanceof User) {
            return [
                'id' => $model->getKey(),
                'type' => 'user',
                'name' => $model->name,
            ];
        }

        if ($model instanceof Affiliation) {
            $model->loadMissing('user');

            return array_filter([
                'id' => $model->getKey(),
                'type' => 'affiliation',
                'affiliation_type' => $model->type->value,
                'affiliation_type_label' => $model->type->label(),
                'user' => $model->user === null ? null : $this->snapshot($model->user),
                'registration_number' => $model->getAttribute('registration_number'),
                'course_id' => $model->getAttribute('course_id'),
            ], static fn (mixed $value): bool => $value !== null);
        }

        return [
            'id' => $model->getKey(),
            'type' => class_basename($model),
        ];
    }

    /**
     * @param  array<string, mixed>  $changes
     * @return array<string, mixed>
     */
    private function safeChanges(array $changes): array
    {
        $safeChanges = [];

        foreach ($changes as $attribute => $change) {
            if ($this->isSensitiveKey($attribute)) {
                $safeChanges[$attribute] = $this->safeSensitiveChange((string) $attribute, $change);

                continue;
            }

            $safeChanges[$attribute] = is_array($change)
                ? $this->withoutSensitiveValues($change)
                : $change;
        }

        return $safeChanges;
    }

    /**
     * Keep password values out of the log while preserving masked identity fields.
     *
     * @return array<string, mixed>
     */
    private function safeSensitiveChange(string $attribute, mixed $change): array
    {
        if (! is_array($change) || ! in_array($attribute, ['cpf', 'email', 'operational_email'], true)) {
            return ['changed' => true];
        }

        $mask = $attribute === 'cpf'
            ? fn (mixed $value): mixed => $this->maskedCpf(is_string($value) ? $value : null)
            : fn (mixed $value): mixed => $this->maskedEmail(is_string($value) ? $value : null);

        return [
            'old' => $mask($change['old'] ?? null),
            'new' => $mask($change['new'] ?? null),
        ];
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function withoutSensitiveValues(array $values): array
    {
        $sanitizedValues = [];

        foreach ($values as $key => $value) {
            if ($this->isSensitiveKey((string) $key)) {
                $sanitizedValues[$key] = ['changed' => true];

                continue;
            }

            $sanitizedValues[$key] = is_array($value)
                ? $this->withoutSensitiveValues($value)
                : $this->redactSensitiveText(
                    $value,
                    preserveNumericIdentifier: in_array($key, ['registration_number', 'course_code'], true),
                );
        }

        return $sanitizedValues;
    }

    private function isSensitiveKey(string $key): bool
    {
        return Str::contains(Str::lower($key), [
            'cpf',
            'email',
            'password',
            'token',
            'secret',
            'url',
            'path',
            'content',
        ]);
    }

    private function redactSensitiveText(mixed $value, bool $preserveNumericIdentifier = false): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $patterns = ['/\\b[A-Z0-9._%+-]+@[A-Z0-9.-]+\\.[A-Z]{2,}\\b/i'];
        $replacements = ['[e-mail protegido]'];

        if (! $preserveNumericIdentifier) {
            $patterns[] = '/(?<!\\d)\\d{3}\\.?\\d{3}\\.?\\d{3}-?\\d{2}(?!\\d)/';
            $replacements[] = '[CPF protegido]';
        }

        return preg_replace($patterns, $replacements, $value) ?? $value;
    }

    private function maskedEmail(?string $email): ?string
    {
        if (! filled($email) || ! str_contains($email, '@')) {
            return null;
        }

        [$localPart, $domain] = explode('@', $email, 2);
        $visibleCharacter = mb_substr($localPart, 0, 1);

        return $visibleCharacter.str_repeat('*', max(mb_strlen($localPart) - 1, 1)).'@'.$domain;
    }

    private function maskedCpf(?string $cpf): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $cpf) ?? '';

        if (mb_strlen($digits) !== 11) {
            return null;
        }

        return '***.***.'.mb_substr($digits, 6, 3).'-'.mb_substr($digits, 9, 2);
    }
}
