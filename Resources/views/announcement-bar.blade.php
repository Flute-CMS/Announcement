@php
    $announcementService = app(\Flute\Modules\Announcement\Services\AnnouncementService::class);
    $announcements = $announcementService->getVisible();

    $typeIcons = [
        'info' => 'ph.bold.info-bold',
        'success' => 'ph.bold.check-circle-bold',
        'warning' => 'ph.bold.warning-bold',
        'error' => 'ph.bold.warning-circle-bold',
    ];
@endphp

@if (count($announcements) > 0 && !request()->htmx()->isHtmxRequest())
    <div class="announcement-container" id="announcement-container">
        @foreach ($announcements as $i => $announcement)
            @php
                $icon = $announcement['icon'] ?: ($typeIcons[$announcement['type']] ?? $typeIcons['info']);
                $barUrl = $announcement['url'];
                $hasPrimaryButton = $announcement['buttonText'] && $announcement['buttonUrl'];
                $hasSecondaryButton = !empty($announcement['secondaryButtonText']) && !empty($announcement['secondaryButtonUrl']);
                $title = $announcement['title'] ?? null;
                $timerAt = null;
                if (!empty($announcement['showTimer'])) {
                    $timerAt = $announcement['timerAt'] ?? null;
                    if (!$timerAt instanceof \DateTimeInterface && !empty($announcement['endAt']) && $announcement['endAt'] instanceof \DateTimeInterface) {
                        $timerAt = $announcement['endAt'];
                    }
                }
                $showTimer = $timerAt instanceof \DateTimeInterface;
                $timerIso = $showTimer ? $timerAt->format(\DateTimeInterface::ATOM) : null;
                $primaryStyle = $announcement['buttonStyle'] ?? 'primary';
                $secondaryStyle = $announcement['secondaryButtonStyle'] ?? 'outline';
                $hasRightSlot = $showTimer || $hasPrimaryButton || $hasSecondaryButton || $announcement['closable'];
            @endphp
            <div class="announcement-bar announcement-bar--{{ $announcement['type'] }} @if($barUrl) announcement-bar--clickable @endif @if($title) announcement-bar--stacked @endif"
                 data-announcement-id="{{ $announcement['id'] }}"
                 style="animation-delay: {{ $i * 60 }}ms"
                 @if ($barUrl) data-url="{{ $barUrl }}" @endif
                 @if ($announcement['closable']) data-closable="true" @endif>
                <div class="container">
                    <div class="announcement-bar__inner">
                        <div class="announcement-bar__left">
                            <span class="announcement-bar__icon">
                                <x-icon path="{{ $icon }}" />
                            </span>
                            <div class="announcement-bar__body">
                                @if ($title)
                                    <span class="announcement-bar__title">{{ $title }}</span>
                                @endif
                                <span class="announcement-bar__text">{!! markdown()->parse($announcement['content']) !!}</span>
                            </div>
                        </div>

                        @if ($hasRightSlot)
                            <div class="announcement-bar__right">
                                @if ($showTimer)
                                    <div class="announcement-bar__timer"
                                         data-announcement-timer
                                         data-timer-target="{{ $timerIso }}"
                                         aria-live="polite">
                                        <span class="announcement-bar__timer-unit" data-unit="days" hidden>
                                            <span class="announcement-bar__timer-value" data-timer-days>00</span><span class="announcement-bar__timer-label" aria-label="@t('announcement.timer.days')">@t('announcement.timer.days_short')</span>
                                        </span>
                                        <span class="announcement-bar__timer-unit">
                                            <span class="announcement-bar__timer-value" data-timer-hours>00</span><span class="announcement-bar__timer-label" aria-label="@t('announcement.timer.hours')">@t('announcement.timer.hours_short')</span>
                                        </span>
                                        <span class="announcement-bar__timer-unit">
                                            <span class="announcement-bar__timer-value" data-timer-minutes>00</span><span class="announcement-bar__timer-label" aria-label="@t('announcement.timer.minutes')">@t('announcement.timer.minutes_short')</span>
                                        </span>
                                        <span class="announcement-bar__timer-unit">
                                            <span class="announcement-bar__timer-value" data-timer-seconds>00</span><span class="announcement-bar__timer-label" aria-label="@t('announcement.timer.seconds')">@t('announcement.timer.seconds_short')</span>
                                        </span>
                                        <span class="announcement-bar__timer-expired" data-timer-expired-text hidden>@t('announcement.timer.expired')</span>
                                    </div>
                                @endif

                                @if ($hasPrimaryButton || $hasSecondaryButton)
                                    <div class="announcement-bar__buttons">
                                        @if ($hasPrimaryButton)
                                            <a href="{{ $announcement['buttonUrl'] }}"
                                               class="announcement-bar__btn announcement-bar__btn--{{ $primaryStyle }}"
                                               @if ($announcement['buttonNewTab']) target="_blank" rel="noopener noreferrer" @endif>
                                                @if (!empty($announcement['buttonIcon']))
                                                    <x-icon path="{{ $announcement['buttonIcon'] }}" />
                                                @endif
                                                <span>{{ $announcement['buttonText'] }}</span>
                                            </a>
                                        @endif

                                        @if ($hasSecondaryButton)
                                            <a href="{{ $announcement['secondaryButtonUrl'] }}"
                                               class="announcement-bar__btn announcement-bar__btn--{{ $secondaryStyle }}"
                                               @if (!empty($announcement['secondaryButtonNewTab'])) target="_blank" rel="noopener noreferrer" @endif>
                                                @if (!empty($announcement['secondaryButtonIcon']))
                                                    <x-icon path="{{ $announcement['secondaryButtonIcon'] }}" />
                                                @endif
                                                <span>{{ $announcement['secondaryButtonText'] }}</span>
                                            </a>
                                        @endif
                                    </div>
                                @endif

                                @if ($announcement['closable'])
                                    <button type="button"
                                            class="announcement-bar__close"
                                            aria-label="@t('def.close')"
                                            onclick="dismissAnnouncement({{ $announcement['id'] }})">
                                        <x-icon path="ph.bold.x-bold" />
                                    </button>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <script>
        function syncAnnouncementStackHeight() {
            var root = document.documentElement;
            var container = document.getElementById('announcement-container');
            if (!container || !container.isConnected) {
                root.style.removeProperty('--announcement-stack-height');
                root.removeAttribute('data-announcement-active');
                return;
            }
            var h = container.offsetHeight;
            if (h <= 0) {
                root.style.setProperty('--announcement-stack-height', '0px');
                root.removeAttribute('data-announcement-active');
                return;
            }
            root.style.setProperty('--announcement-stack-height', h + 'px');
            root.setAttribute('data-announcement-active', 'true');
        }

        function dismissAnnouncement(id) {
            var row = document.querySelector('[data-announcement-id="' + id + '"]');
            if (row) {
                row.classList.add('announcement-bar--hiding');
                setTimeout(function() {
                    row.remove();

                    var dismissed = getCookie('dismissed_announcements') || '';
                    var ids = dismissed ? dismissed.split(',') : [];
                    if (!ids.includes(String(id))) {
                        ids.push(String(id));
                        setCookie('dismissed_announcements', ids.join(','), { expires: 30, path: '/' });
                    }

                    var container = document.getElementById('announcement-container');
                    if (container && container.children.length === 0) {
                        container.remove();
                    }
                    syncAnnouncementStackHeight();
                }, 350);
            }
        }

        (function initAnnouncementTimers() {
            function pad(n) { return (n < 10 ? '0' : '') + n; }

            function tick(el) {
                var target = el.getAttribute('data-timer-target');
                if (!target) return;
                var diff = Math.max(0, new Date(target).getTime() - Date.now());
                var totalSec = Math.floor(diff / 1000);
                var days = Math.floor(totalSec / 86400);
                var hours = Math.floor((totalSec % 86400) / 3600);
                var minutes = Math.floor((totalSec % 3600) / 60);
                var seconds = totalSec % 60;

                var dEl = el.querySelector('[data-timer-days]');
                var hEl = el.querySelector('[data-timer-hours]');
                var mEl = el.querySelector('[data-timer-minutes]');
                var sEl = el.querySelector('[data-timer-seconds]');
                if (dEl) {
                    dEl.textContent = pad(days);
                    var wrap = dEl.closest('[data-unit="days"]');
                    if (wrap) wrap.hidden = days <= 0;
                }
                if (hEl) hEl.textContent = pad(hours);
                if (mEl) mEl.textContent = pad(minutes);
                if (sEl) sEl.textContent = pad(seconds);

                if (diff <= 0) {
                    el.classList.add('announcement-bar__timer--expired');
                    var units = el.querySelectorAll('.announcement-bar__timer-unit');
                    units.forEach(function (u) { u.hidden = true; });
                    var expired = el.querySelector('[data-timer-expired-text]');
                    if (expired) expired.hidden = false;
                }
            }

            function start() {
                var timers = document.querySelectorAll('[data-announcement-timer]');
                if (!timers.length) return;
                timers.forEach(tick);
                setInterval(function() { timers.forEach(tick); }, 1000);
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', start);
            } else {
                start();
            }
        })();

        document.addEventListener('DOMContentLoaded', function() {
            var bar = document.getElementById('announcement-container');
            if (bar && document.body) {
                document.body.appendChild(bar);
                setTimeout(function() {
                    bar.classList.add('announcement-container--active');
                    syncAnnouncementStackHeight();

                    bar.addEventListener('transitionend', function(e) {
                        if (e.target === bar) syncAnnouncementStackHeight();
                    });

                    if (typeof ResizeObserver !== 'undefined') {
                        var ro = new ResizeObserver(function() {
                            syncAnnouncementStackHeight();
                        });
                        ro.observe(bar);
                    }
                }, 50);

                bar.addEventListener('click', function(e) {
                    var row = e.target.closest('[data-url]');
                    if (!row) return;
                    if (e.target.closest('a, button')) return;
                    window.location.href = row.dataset.url;
                });
            }
        });
    </script>
@endif
