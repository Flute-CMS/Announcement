@php
    $typeIcons = [
        'info' => 'ph.bold.info-bold',
        'success' => 'ph.bold.check-circle-bold',
        'warning' => 'ph.bold.warning-bold',
        'error' => 'ph.bold.warning-circle-bold',
    ];
    $targetIcons = [
        'all' => 'ph.bold.users-bold',
        'guests' => 'ph.bold.eye-bold',
        'auth' => 'ph.bold.lock-bold',
    ];

    $icon = $announcement->icon ?: ($typeIcons[$announcement->type] ?? $typeIcons['info']);
    $title = $announcement->title;
    $preview = mb_strimwidth(strip_tags($announcement->content ?? ''), 0, 120, '…');

    $hasPrimary = !empty($announcement->buttonText) && !empty($announcement->buttonUrl);
    $hasSecondary = !empty($announcement->secondaryButtonText) && !empty($announcement->secondaryButtonUrl);
    $buttonsCount = ($hasPrimary ? 1 : 0) + ($hasSecondary ? 1 : 0);

    $now = new DateTimeImmutable();
    $isScheduled = $announcement->startAt instanceof DateTimeInterface && $now < $announcement->startAt;
    $isExpired = $announcement->endAt instanceof DateTimeInterface && $now > $announcement->endAt;

    $timerTarget = $announcement->timerAt instanceof DateTimeInterface
        ? $announcement->timerAt
        : ($announcement->endAt instanceof DateTimeInterface ? $announcement->endAt : null);
@endphp

<div class="announcement-cell announcement-cell--{{ $announcement->type }}">
    <div class="announcement-cell__icon">
        <x-icon path="{{ $icon }}" />
    </div>

    <div class="announcement-cell__body">
        <div class="announcement-cell__head">
            @if ($title)
                <span class="announcement-cell__title">{{ $title }}</span>
            @endif

            <span class="badge {{ $announcement->type }} announcement-cell__type-badge">
                @t('admin-announcement.types.' . $announcement->type)
            </span>

            @if (!$announcement->isActive)
                <span class="badge" data-tooltip="@t('admin-announcement.modal.fields.is_active.help')">
                    @t('def.inactive')
                </span>
            @elseif ($isScheduled)
                <span class="badge info" data-tooltip="{{ $announcement->startAt->format('d.m.Y H:i') }}">
                    <x-icon path="ph.bold.clock-bold" />
                    @t('admin-announcement.status.scheduled')
                </span>
            @elseif ($isExpired)
                <span class="badge" data-tooltip="{{ $announcement->endAt->format('d.m.Y H:i') }}">
                    <x-icon path="ph.bold.clock-counter-clockwise-bold" />
                    @t('admin-announcement.status.expired')
                </span>
            @else
                <span class="badge success">
                    <x-icon path="ph.bold.check-bold" />
                    @t('def.active')
                </span>
            @endif
        </div>

        @if ($preview !== '')
            <div class="announcement-cell__content">{{ $preview }}</div>
        @endif

        <div class="announcement-cell__meta">
            <span class="announcement-cell__meta-item" data-tooltip="@t('admin-announcement.modal.fields.target.label')">
                <x-icon path="{{ $targetIcons[$announcement->target] ?? $targetIcons['all'] }}" />
                @t('admin-announcement.targets.' . $announcement->target)
            </span>

            @if ($announcement->url)
                <span class="announcement-cell__meta-item" data-tooltip="{{ $announcement->url }}">
                    <x-icon path="ph.bold.link-bold" />
                    {{ mb_strimwidth($announcement->url, 0, 30, '…') }}
                </span>
            @endif

            @if ($buttonsCount > 0)
                <span class="announcement-cell__meta-item" data-tooltip="@t('admin-announcement.sections.primary_button.title')">
                    <x-icon path="ph.bold.cursor-click-bold" />
                    {{ $buttonsCount }}&nbsp;@t($buttonsCount === 1 ? 'admin-announcement.meta.button' : 'admin-announcement.meta.buttons')
                </span>
            @endif

            @if ($announcement->showTimer && $timerTarget)
                <span class="announcement-cell__meta-item" data-tooltip="{{ $timerTarget->format('d.m.Y H:i') }}">
                    <x-icon path="ph.bold.timer-bold" />
                    @t('admin-announcement.sections.timer.title')
                </span>
            @endif

            @if ($announcement->closable)
                <span class="announcement-cell__meta-item" data-tooltip="@t('admin-announcement.modal.fields.closable.help')">
                    <x-icon path="ph.bold.x-circle-bold" />
                    @t('admin-announcement.modal.fields.closable.label')
                </span>
            @endif

            @if ($announcement->startAt || $announcement->endAt)
                <span class="announcement-cell__meta-item"
                      data-tooltip="{{ ($announcement->startAt?->format('d.m.Y H:i') ?? '—') . ' — ' . ($announcement->endAt?->format('d.m.Y H:i') ?? '∞') }}">
                    <x-icon path="ph.bold.calendar-bold" />
                    @if ($announcement->startAt && $announcement->endAt)
                        {{ $announcement->startAt->format('d.m') }} — {{ $announcement->endAt->format('d.m') }}
                    @elseif ($announcement->startAt)
                        @t('admin-announcement.meta.from'): {{ $announcement->startAt->format('d.m.Y') }}
                    @else
                        @t('admin-announcement.meta.until'): {{ $announcement->endAt->format('d.m.Y') }}
                    @endif
                </span>
            @endif
        </div>
    </div>
</div>
