<?php

namespace Flute\Modules\Announcement\Admin\Screens;

use DateTimeImmutable;
use Flute\Admin\Platform\Actions\Button;
use Flute\Admin\Platform\Actions\DropDown;
use Flute\Admin\Platform\Actions\DropDownItem;
use Flute\Admin\Platform\Fields\ButtonGroup;
use Flute\Admin\Platform\Fields\CheckBox;
use Flute\Admin\Platform\Fields\Input;
use Flute\Admin\Platform\Fields\RadioCards;
use Flute\Admin\Platform\Fields\Select;
use Flute\Admin\Platform\Fields\Sight;
use Flute\Admin\Platform\Layouts\LayoutFactory;
use Flute\Admin\Platform\Layouts\Modal;
use Flute\Admin\Platform\Repository;
use Flute\Admin\Platform\Screen;
use Flute\Admin\Platform\Support\Color;
use Flute\Modules\Announcement\database\Entities\Announcement;
use Flute\Modules\Announcement\Services\AnnouncementService;
use Throwable;

class AnnouncementScreen extends Screen
{
    public ?string $name = null;

    public ?string $description = null;

    public ?string $permission = 'admin.announcement';

    public $announcements;

    public function mount(): void
    {
        breadcrumb()->add(__('def.admin_panel'), url('/admin'))->add(__('admin-announcement.title'));

        $this->loadAnnouncements();
    }

    public function layout(): array
    {
        return [
            LayoutFactory::sortable('announcements', [
                Sight::make(
                    'content',
                    __('admin-announcement.table.content'),
                )->render(static fn(Announcement $announcement) => view(
                    'admin-announcement::cells.item',
                    compact('announcement'),
                )),
                Sight::make('actions', __('admin-announcement.table.actions'))->render(
                    static fn(Announcement $announcement) => DropDown::make()
                        ->icon('ph.regular.dots-three-outline-vertical')
                        ->list([
                            DropDownItem::make(__('def.edit'))
                                ->modal('editAnnouncementModal', ['announcement' => $announcement->id])
                                ->icon('ph.bold.pencil-bold')
                                ->type(Color::OUTLINE_PRIMARY)
                                ->size('small')
                                ->fullWidth(),
                            DropDownItem::make(__('def.delete'))
                                ->confirm(__('admin-announcement.confirms.delete'))
                                ->method('deleteAnnouncement', ['id' => $announcement->id])
                                ->icon('ph.bold.trash-bold')
                                ->type(Color::OUTLINE_DANGER)
                                ->size('small')
                                ->fullWidth(),
                        ]),
                ),
            ])
                ->onSortEnd('updatePositions')
                ->commands([
                    Button::make(__('def.create'))
                        ->icon('ph.bold.plus-bold')
                        ->size('medium')
                        ->modal('createAnnouncementModal')
                        ->type(Color::PRIMARY),
                ])
                ->title(__('admin-announcement.sections.list.title'))
                ->description(__('admin-announcement.sections.list.description')),
        ];
    }

    /**
     * Update positions after sorting
     */
    public function updatePositions()
    {
        $sortableResult = json_decode(request()->input('sortableResult'), true);
        if (!$sortableResult) {
            $this->flashMessage(__('admin-announcement.messages.invalid_sort_data'), 'danger');

            return;
        }

        $position = 0;
        foreach ($sortableResult as $item) {
            $announcement = Announcement::findByPK($item['id']);
            if ($announcement) {
                $announcement->position = ++$position;
                $announcement->save();
            }
        }

        orm()->getHeap()->clean();
        $this->clearCache();
        $this->loadAnnouncements();
    }

    /**
     * Modal for creating a new announcement
     */
    public function createAnnouncementModal(Repository $parameters)
    {
        return LayoutFactory::modal($parameters, $this->getFormFields())
            ->title(__('admin-announcement.modal.create_title'))
            ->applyButton(__('def.create'))
            ->size(Modal::SIZE_LG)
            ->method('saveAnnouncement');
    }

    /**
     * Save new announcement
     */
    public function saveAnnouncement()
    {
        $data = $this->normalizeData(request()->input());

        $validation = $this->validate($this->validationRules(), $data);

        if (!$validation) {
            return;
        }

        $lastItem = Announcement::query()->orderBy('position', 'desc')->fetchOne();
        $position = $lastItem ? $lastItem->position + 1 : 1;

        $announcement = new Announcement();
        $this->fillFromRequest($announcement, $data);
        $announcement->position = $position;
        $announcement->save();

        $this->flashMessage(__('admin-announcement.messages.created'), 'success');
        $this->closeModal();

        $this->clearCache();
        $this->loadAnnouncements();
    }

    /**
     * Modal for editing an announcement
     */
    public function editAnnouncementModal(Repository $parameters)
    {
        $announcementId = $parameters->get('announcement');
        $announcement = Announcement::findByPK($announcementId);
        if (!$announcement) {
            $this->flashMessage(__('admin-announcement.messages.not_found'), 'error');

            return;
        }

        return LayoutFactory::modal($parameters, $this->getFormFields($announcement))
            ->title(__('admin-announcement.modal.edit_title'))
            ->applyButton(__('def.save'))
            ->size(Modal::SIZE_LG)
            ->method('updateAnnouncement');
    }

    /**
     * Update existing announcement
     */
    public function updateAnnouncement()
    {
        $data = $this->normalizeData(request()->input());
        $announcementId = $this->modalParams->get('announcement');

        $announcement = Announcement::findByPK($announcementId);
        if (!$announcement) {
            $this->flashMessage(__('admin-announcement.messages.not_found'), 'error');

            return;
        }

        $validation = $this->validate($this->validationRules(), $data);

        if (!$validation) {
            return;
        }

        $this->fillFromRequest($announcement, $data);
        $announcement->save();

        $this->flashMessage(__('admin-announcement.messages.updated'), 'success');
        $this->closeModal();

        $this->clearCache();
        $this->loadAnnouncements();
    }

    /**
     * Delete an announcement
     */
    public function deleteAnnouncement()
    {
        $id = request()->input('id');

        $announcement = Announcement::findByPK($id);
        if (!$announcement) {
            $this->flashMessage(__('admin-announcement.messages.not_found'), 'error');

            return;
        }

        $announcement->delete();
        $this->flashMessage(__('admin-announcement.messages.deleted'), 'success');

        $this->clearCache();
        $this->loadAnnouncements();
    }

    protected function loadAnnouncements()
    {
        $this->announcements = Announcement::query()->orderBy('position', 'asc')->fetchAll();
    }

    protected function validationRules(): array
    {
        return [
            'content' => ['required', 'string'],
            'title' => ['nullable', 'string', 'max-str-len:255'],
            'icon' => ['nullable', 'string', 'max-str-len:255'],
            'url' => ['nullable', 'string', 'max-str-len:255'],
            'button_text' => ['nullable', 'string', 'max-str-len:255'],
            'button_url' => ['nullable', 'string', 'max-str-len:255'],
            'button_icon' => ['nullable', 'string', 'max-str-len:255'],
            'button_style' => ['nullable', 'string', 'in:primary,outline,ghost,light'],
            'secondary_button_text' => ['nullable', 'string', 'max-str-len:255'],
            'secondary_button_url' => ['nullable', 'string', 'max-str-len:255'],
            'secondary_button_icon' => ['nullable', 'string', 'max-str-len:255'],
            'secondary_button_style' => ['nullable', 'string', 'in:primary,outline,ghost,light'],
            'type' => ['required', 'string', 'in:info,success,warning,error'],
            'target' => ['required', 'string', 'in:all,guests,auth'],
        ];
    }

    /**
     * Flatten array-valued ButtonGroup inputs to scalar values.
     */
    protected function normalizeData(array $data): array
    {
        foreach (
            [
                'type',
                'target',
                'button_style',
                'secondary_button_style',
                'has_button',
                'has_secondary_button',
                'button_new_tab',
                'secondary_button_new_tab',
                'show_timer',
                'closable',
                'is_active',
            ] as $key
        ) {
            if (isset($data[$key]) && is_array($data[$key])) {
                $data[$key] = $data[$key][0] ?? null;
            }
        }

        return $data;
    }

    protected function fillFromRequest(Announcement $announcement, array $data): void
    {
        $scalar = static function ($value, $default = null) {
            if (is_array($value)) {
                $value = $value[0] ?? $default;
            }

            return $value ?? $default;
        };

        $bool = static function ($value) use ($scalar) {
            return filter_var($scalar($value, '0'), FILTER_VALIDATE_BOOLEAN);
        };

        $announcement->content = (string) ($data['content'] ?? '');
        $announcement->title = !empty($data['title']) ? (string) $data['title'] : null;
        $announcement->icon = $data['icon'] ?? null ?: null;
        $announcement->url = $data['url'] ?? null ?: null;

        $announcement->type = $scalar($data['type'] ?? 'info', 'info');
        $announcement->target = $scalar($data['target'] ?? 'all', 'all');
        $announcement->closable = $bool($data['closable'] ?? false);
        $announcement->isActive = $bool($data['is_active'] ?? false);

        $hasPrimary = $bool($data['has_button'] ?? false);
        if ($hasPrimary) {
            $announcement->buttonText = (string) ($data['button_text'] ?? '') ?: null;
            $announcement->buttonUrl = (string) ($data['button_url'] ?? '') ?: null;
            $announcement->buttonIcon = (string) ($data['button_icon'] ?? '') ?: null;
            $announcement->buttonStyle = $scalar($data['button_style'] ?? 'primary', 'primary');
            $announcement->buttonNewTab = $bool($data['button_new_tab'] ?? false);
        } else {
            $announcement->buttonText = null;
            $announcement->buttonUrl = null;
            $announcement->buttonIcon = null;
            $announcement->buttonStyle = 'primary';
            $announcement->buttonNewTab = false;
        }

        $hasSecondary = $bool($data['has_secondary_button'] ?? false);
        if ($hasSecondary) {
            $announcement->secondaryButtonText = (string) ($data['secondary_button_text'] ?? '') ?: null;
            $announcement->secondaryButtonUrl = (string) ($data['secondary_button_url'] ?? '') ?: null;
            $announcement->secondaryButtonIcon = (string) ($data['secondary_button_icon'] ?? '') ?: null;
            $announcement->secondaryButtonStyle = $scalar($data['secondary_button_style'] ?? 'outline', 'outline');
            $announcement->secondaryButtonNewTab = $bool($data['secondary_button_new_tab'] ?? false);
        } else {
            $announcement->secondaryButtonText = null;
            $announcement->secondaryButtonUrl = null;
            $announcement->secondaryButtonIcon = null;
            $announcement->secondaryButtonStyle = 'outline';
            $announcement->secondaryButtonNewTab = false;
        }

        $announcement->showTimer = $bool($data['show_timer'] ?? false);
        $announcement->timerAt = $announcement->showTimer && !empty($data['timer_at'])
            ? new DateTimeImmutable($data['timer_at'])
            : null;

        $announcement->startAt = !empty($data['start_at']) ? new DateTimeImmutable($data['start_at']) : null;
        $announcement->endAt = !empty($data['end_at']) ? new DateTimeImmutable($data['end_at']) : null;
    }

    protected function getFormFields(?Announcement $announcement = null): array
    {
        return [
            $this->mainBlock($announcement),
            $this->primaryButtonBlock($announcement),
            $this->secondaryButtonBlock($announcement),
            $this->timerBlock($announcement),
            $this->scheduleBlock($announcement),
            $this->visibilityBlock($announcement),
        ];
    }

    private function buttonStyleOptions(): array
    {
        return [
            'primary' => [
                'label' => __('admin-announcement.button_styles.primary'),
                'icon' => 'ph.bold.paint-bucket-bold',
            ],
            'outline' => [
                'label' => __('admin-announcement.button_styles.outline'),
                'icon' => 'ph.bold.square-bold',
            ],
            'light' => [
                'label' => __('admin-announcement.button_styles.light'),
                'icon' => 'ph.bold.sun-bold',
            ],
            'ghost' => [
                'label' => __('admin-announcement.button_styles.ghost'),
                'icon' => 'ph.bold.circle-dashed-bold',
            ],
        ];
    }

    private function onOffOptions(): array
    {
        return [
            '0' => ['label' => __('def.off'), 'icon' => 'ph.bold.x-bold'],
            '1' => ['label' => __('def.on'), 'icon' => 'ph.bold.check-bold'],
        ];
    }

    private function boolFromRequest(string $key, ?bool $fallback): bool
    {
        $req = request()->input($key);
        if ($req === null) {
            return (bool) $fallback;
        }

        return filter_var(is_array($req) ? ($req[0] ?? '0') : $req, FILTER_VALIDATE_BOOLEAN);
    }

    private function mainBlock(?Announcement $announcement): \Flute\Admin\Platform\Layout
    {
        return LayoutFactory::block([
            LayoutFactory::field(
                Input::make('title')
                    ->type('text')
                    ->placeholder(__('admin-announcement.modal.fields.title.placeholder'))
                    ->value($announcement?->title),
            )
                ->label(__('admin-announcement.modal.fields.title.label'))
                ->small(__('admin-announcement.modal.fields.title.help')),

            LayoutFactory::field(
                Input::make('content')
                    ->type('textarea')
                    ->placeholder(__('admin-announcement.modal.fields.content.placeholder'))
                    ->value($announcement?->content),
            )
                ->label(__('admin-announcement.modal.fields.content.label'))
                ->required()
                ->small(__('admin-announcement.modal.fields.content.help')),

            LayoutFactory::field(
                RadioCards::make('type')
                    ->options([
                        'info' => [
                            'label' => __('admin-announcement.types.info'),
                            'icon' => 'ph.bold.info-bold',
                        ],
                        'success' => [
                            'label' => __('admin-announcement.types.success'),
                            'icon' => 'ph.bold.check-circle-bold',
                        ],
                        'warning' => [
                            'label' => __('admin-announcement.types.warning'),
                            'icon' => 'ph.bold.warning-bold',
                        ],
                        'error' => [
                            'label' => __('admin-announcement.types.error'),
                            'icon' => 'ph.bold.warning-circle-bold',
                        ],
                    ])
                    ->columns(4)
                    ->value($announcement?->type ?? 'info'),
            )
                ->label(__('admin-announcement.modal.fields.type.label'))
                ->required(),

            LayoutFactory::split([
                LayoutFactory::field(
                    Input::make('icon')
                        ->type('icon')
                        ->placeholder(__('admin-announcement.modal.fields.icon.placeholder'))
                        ->value($announcement?->icon),
                )
                    ->label(__('admin-announcement.modal.fields.icon.label'))
                    ->small(__('admin-announcement.modal.fields.icon.help')),

                LayoutFactory::field(
                    Input::make('url')
                        ->type('text')
                        ->placeholder(__('admin-announcement.modal.fields.url.placeholder'))
                        ->value($announcement?->url),
                )
                    ->label(__('admin-announcement.modal.fields.url.label'))
                    ->small(__('admin-announcement.modal.fields.url.help')),
            ]),
        ])
            ->title(__('admin-announcement.sections.main.title'))
            ->description(__('admin-announcement.sections.main.description'));
    }

    private function primaryButtonBlock(?Announcement $announcement): \Flute\Admin\Platform\Layout
    {
        $hasButton = $this->boolFromRequest(
            'has_button',
            !empty($announcement?->buttonText) && !empty($announcement?->buttonUrl),
        );

        $fields = [
            LayoutFactory::field(
                ButtonGroup::make('has_button')
                    ->options($this->onOffOptions())
                    ->value($hasButton ? '1' : '0')
                    ->color('accent')
                    ->yoyo(),
            )
                ->label(__('admin-announcement.modal.fields.has_button.label'))
                ->small(__('admin-announcement.modal.fields.has_button.help')),
        ];

        if ($hasButton) {
            $fields[] = LayoutFactory::split([
                LayoutFactory::field(
                    Input::make('button_text')
                        ->type('text')
                        ->placeholder(__('admin-announcement.modal.fields.button_text.placeholder'))
                        ->value($announcement?->buttonText),
                )
                    ->label(__('admin-announcement.modal.fields.button_text.label'))
                    ->required(),

                LayoutFactory::field(
                    Input::make('button_url')
                        ->type('text')
                        ->placeholder(__('admin-announcement.modal.fields.button_url.placeholder'))
                        ->value($announcement?->buttonUrl),
                )
                    ->label(__('admin-announcement.modal.fields.button_url.label'))
                    ->required(),
            ]);

            $fields[] = LayoutFactory::field(
                ButtonGroup::make('button_style')
                    ->options($this->buttonStyleOptions())
                    ->value($announcement?->buttonStyle ?? 'primary')
                    ->color('accent'),
            )
                ->label(__('admin-announcement.modal.fields.button_style.label'))
                ->small(__('admin-announcement.modal.fields.button_style.help'));

            $fields[] = LayoutFactory::split([
                LayoutFactory::field(
                    Input::make('button_icon')
                        ->type('icon')
                        ->placeholder(__('admin-announcement.modal.fields.button_icon.placeholder'))
                        ->value($announcement?->buttonIcon),
                )
                    ->label(__('admin-announcement.modal.fields.button_icon.label'))
                    ->small(__('admin-announcement.modal.fields.button_icon.help')),

                LayoutFactory::field(
                    ButtonGroup::make('button_new_tab')
                        ->options($this->onOffOptions())
                        ->value($announcement?->buttonNewTab ? '1' : '0')
                        ->color('accent'),
                )
                    ->label(__('admin-announcement.modal.fields.button_new_tab.label'))
                    ->popover(__('admin-announcement.modal.fields.button_new_tab.help')),
            ]);
        }

        return LayoutFactory::block($fields)
            ->title(__('admin-announcement.sections.primary_button.title'))
            ->description(__('admin-announcement.sections.primary_button.description'));
    }

    private function secondaryButtonBlock(?Announcement $announcement): \Flute\Admin\Platform\Layout
    {
        $hasSecondary = $this->boolFromRequest(
            'has_secondary_button',
            !empty($announcement?->secondaryButtonText) && !empty($announcement?->secondaryButtonUrl),
        );

        $fields = [
            LayoutFactory::field(
                ButtonGroup::make('has_secondary_button')
                    ->options($this->onOffOptions())
                    ->value($hasSecondary ? '1' : '0')
                    ->color('accent')
                    ->yoyo(),
            )
                ->label(__('admin-announcement.modal.fields.has_secondary_button.label'))
                ->small(__('admin-announcement.modal.fields.has_secondary_button.help')),
        ];

        if ($hasSecondary) {
            $fields[] = LayoutFactory::split([
                LayoutFactory::field(
                    Input::make('secondary_button_text')
                        ->type('text')
                        ->placeholder(__('admin-announcement.modal.fields.secondary_button_text.placeholder'))
                        ->value($announcement?->secondaryButtonText),
                )
                    ->label(__('admin-announcement.modal.fields.secondary_button_text.label'))
                    ->required(),

                LayoutFactory::field(
                    Input::make('secondary_button_url')
                        ->type('text')
                        ->placeholder(__('admin-announcement.modal.fields.secondary_button_url.placeholder'))
                        ->value($announcement?->secondaryButtonUrl),
                )
                    ->label(__('admin-announcement.modal.fields.secondary_button_url.label'))
                    ->required(),
            ]);

            $fields[] = LayoutFactory::field(
                ButtonGroup::make('secondary_button_style')
                    ->options($this->buttonStyleOptions())
                    ->value($announcement?->secondaryButtonStyle ?? 'outline')
                    ->color('accent'),
            )
                ->label(__('admin-announcement.modal.fields.secondary_button_style.label'))
                ->small(__('admin-announcement.modal.fields.secondary_button_style.help'));

            $fields[] = LayoutFactory::split([
                LayoutFactory::field(
                    Input::make('secondary_button_icon')
                        ->type('icon')
                        ->placeholder(__('admin-announcement.modal.fields.secondary_button_icon.placeholder'))
                        ->value($announcement?->secondaryButtonIcon),
                )
                    ->label(__('admin-announcement.modal.fields.secondary_button_icon.label'))
                    ->small(__('admin-announcement.modal.fields.secondary_button_icon.help')),

                LayoutFactory::field(
                    ButtonGroup::make('secondary_button_new_tab')
                        ->options($this->onOffOptions())
                        ->value($announcement?->secondaryButtonNewTab ? '1' : '0')
                        ->color('accent'),
                )
                    ->label(__('admin-announcement.modal.fields.secondary_button_new_tab.label'))
                    ->popover(__('admin-announcement.modal.fields.secondary_button_new_tab.help')),
            ]);
        }

        return LayoutFactory::block($fields)
            ->title(__('admin-announcement.sections.secondary_button.title'))
            ->description(__('admin-announcement.sections.secondary_button.description'));
    }

    private function timerBlock(?Announcement $announcement): \Flute\Admin\Platform\Layout
    {
        $showTimer = $this->boolFromRequest('show_timer', $announcement?->showTimer);

        $fields = [
            LayoutFactory::field(
                ButtonGroup::make('show_timer')
                    ->options($this->onOffOptions())
                    ->value($showTimer ? '1' : '0')
                    ->color('accent')
                    ->yoyo(),
            )
                ->label(__('admin-announcement.modal.fields.show_timer.label'))
                ->small(__('admin-announcement.modal.fields.show_timer.help')),
        ];

        if ($showTimer) {
            $fields[] = LayoutFactory::field(
                Input::make('timer_at')
                    ->type('datetime-local')
                    ->value($announcement?->timerAt?->format('Y-m-d\TH:i')),
            )
                ->label(__('admin-announcement.modal.fields.timer_at.label'))
                ->small(__('admin-announcement.modal.fields.timer_at.help'));
        }

        return LayoutFactory::block($fields)
            ->title(__('admin-announcement.sections.timer.title'))
            ->description(__('admin-announcement.sections.timer.description'));
    }

    private function scheduleBlock(?Announcement $announcement): \Flute\Admin\Platform\Layout
    {
        return LayoutFactory::block([
            LayoutFactory::split([
                LayoutFactory::field(
                    Input::make('start_at')
                        ->type('datetime-local')
                        ->value($announcement?->startAt?->format('Y-m-d\TH:i')),
                )
                    ->label(__('admin-announcement.modal.fields.start_at.label'))
                    ->small(__('admin-announcement.modal.fields.start_at.help')),

                LayoutFactory::field(
                    Input::make('end_at')
                        ->type('datetime-local')
                        ->value($announcement?->endAt?->format('Y-m-d\TH:i')),
                )
                    ->label(__('admin-announcement.modal.fields.end_at.label'))
                    ->small(__('admin-announcement.modal.fields.end_at.help')),
            ]),
        ])
            ->title(__('admin-announcement.sections.schedule.title'))
            ->description(__('admin-announcement.sections.schedule.description'));
    }

    private function visibilityBlock(?Announcement $announcement): \Flute\Admin\Platform\Layout
    {
        return LayoutFactory::block([
            LayoutFactory::field(
                ButtonGroup::make('target')
                    ->options([
                        'all' => [
                            'label' => __('admin-announcement.targets.all'),
                            'icon' => 'ph.bold.users-bold',
                        ],
                        'guests' => [
                            'label' => __('admin-announcement.targets.guests'),
                            'icon' => 'ph.bold.eye-bold',
                        ],
                        'auth' => [
                            'label' => __('admin-announcement.targets.auth'),
                            'icon' => 'ph.bold.lock-bold',
                        ],
                    ])
                    ->value($announcement?->target ?? 'all')
                    ->color('accent'),
            )
                ->label(__('admin-announcement.modal.fields.target.label'))
                ->small(__('admin-announcement.modal.fields.target.help')),

            LayoutFactory::split([
                LayoutFactory::field(
                    ButtonGroup::make('closable')
                        ->options($this->onOffOptions())
                        ->value($announcement?->closable ? '1' : '0')
                        ->color('accent'),
                )
                    ->label(__('admin-announcement.modal.fields.closable.label'))
                    ->popover(__('admin-announcement.modal.fields.closable.help')),

                LayoutFactory::field(
                    ButtonGroup::make('is_active')
                        ->options($this->onOffOptions())
                        ->value(($announcement?->isActive ?? true) ? '1' : '0')
                        ->color('accent'),
                )
                    ->label(__('admin-announcement.modal.fields.is_active.label'))
                    ->popover(__('admin-announcement.modal.fields.is_active.help')),
            ]),
        ])
            ->title(__('admin-announcement.sections.visibility.title'))
            ->description(__('admin-announcement.sections.visibility.description'));
    }

    /**
     * Clear announcement cache
     */
    private function clearCache(): void
    {
        try {
            app(AnnouncementService::class)->clearCache();
        } catch (Throwable $e) {
            // Swallow exceptions to avoid breaking admin UI
        }
    }
}
