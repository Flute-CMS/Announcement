@php
    $announcementService = app(\Flute\Modules\Announcement\Services\AnnouncementService::class);
    $announcements = $announcementService->getVisible();
@endphp

@if (count($announcements) > 0 && !request()->htmx()->isHtmxRequest())
    <div class="announcement-container" id="announcement-container">
        @foreach ($announcements as $announcement)
            <div class="announcement-bar announcement-bar--{{ $announcement['type'] }}" 
                 data-announcement-id="{{ $announcement['id'] }}"
                 @if ($announcement['closable']) data-closable="true" @endif>
                <div class="container">
                    <div class="announcement-bar__inner">
                        {{-- Left spacer for centering --}}
                        <div class="announcement-bar__spacer"></div>
                        
                        {{-- Center content --}}
                        <div class="announcement-bar__center">
                            <div class="announcement-bar__content">
                                <span class="announcement-bar__text">{!! $announcement['content'] !!}</span>
                                
                                @if ($announcement['buttonText'] && $announcement['buttonUrl'])
                                    <span class="announcement-bar__separator">•</span>
                                    <a href="{{ $announcement['buttonUrl'] }}" 
                                       class="announcement-bar__link"
                                       @if ($announcement['buttonNewTab']) target="_blank" rel="noopener noreferrer" @endif>
                                        {{ $announcement['buttonText'] }}
                                        <x-icon path="ph.bold.caret-right-bold" class="announcement-bar__arrow" />
                                    </a>
                                @endif
                            </div>
                        </div>
                        
                        {{-- Close button --}}
                        <div class="announcement-bar__right">
                            @if ($announcement['closable'])
                                <button type="button" 
                                        class="announcement-bar__close" 
                                        aria-label="@t('def.close')"
                                        onclick="dismissAnnouncement({{ $announcement['id'] }})">
                                    <x-icon path="ph.bold.x-bold" />
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <script>
        function dismissAnnouncement(id) {
            const bar = document.querySelector('[data-announcement-id="' + id + '"]');
            if (bar) {
                bar.classList.add('announcement-bar--hiding');
                setTimeout(() => {
                    bar.remove();
                    
                    let dismissed = getCookie('dismissed_announcements') || '';
                    let ids = dismissed ? dismissed.split(',') : [];
                    if (!ids.includes(String(id))) {
                        ids.push(String(id));
                        setCookie('dismissed_announcements', ids.join(','), { expires: 30, path: '/' });
                    }   
                    
                    const container = document.querySelector('.announcement-container');
                    if (container && container.children.length === 0) {
                        container.remove();
                    }
                }, 300);
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const bar = document.getElementById('announcement-container');
            const header = document.querySelector('.flute_header');
            if (bar && header && header.parentNode) {
                header.parentNode.insertBefore(bar, header);
                setTimeout(() => bar.classList.add('announcement-container--active'), 50);
            }
        });
    </script>
@endif
