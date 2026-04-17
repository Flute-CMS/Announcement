<?php

return [
    'title' => 'Announcements',
    'description' => 'Manage site announcements',
    'table' => [
        'content' => 'Content',
        'type' => 'Type',
        'status' => 'Status',
        'actions' => 'Actions',
    ],
    'sections' => [
        'list' => [
            'title' => 'Announcements list',
            'description' => 'Announcements are displayed at the top of the site for all visitors. Drag to reorder.',
        ],
        'main' => [
            'title' => 'Content',
            'description' => 'Title, body text and base styling',
        ],
        'primary_button' => [
            'title' => 'Primary button',
            'description' => 'Main call-to-action next to the text',
        ],
        'secondary_button' => [
            'title' => 'Secondary button',
            'description' => 'Optional additional button (e.g. "Learn more")',
        ],
        'timer' => [
            'title' => 'Countdown',
            'description' => 'Live countdown to the target date',
        ],
        'schedule' => [
            'title' => 'Show schedule',
            'description' => 'Limit the time window when the announcement is shown',
        ],
        'visibility' => [
            'title' => 'Visibility',
            'description' => 'Who sees it and whether it can be dismissed',
        ],
    ],
    'types' => [
        'info' => 'Info',
        'success' => 'Success',
        'warning' => 'Warning',
        'error' => 'Error',
    ],
    'targets' => [
        'all' => 'Everyone',
        'guests' => 'Guests only',
        'auth' => 'Authorized only',
    ],
    'button_styles' => [
        'primary' => 'Primary (filled)',
        'outline' => 'Outline',
        'light' => 'Light',
        'ghost' => 'Ghost (transparent)',
    ],
    'status' => [
        'scheduled' => 'Scheduled',
        'expired' => 'Expired',
    ],
    'meta' => [
        'button' => 'button',
        'buttons' => 'buttons',
        'from' => 'from',
        'until' => 'until',
    ],
    'modal' => [
        'create_title' => 'Create announcement',
        'edit_title' => 'Edit announcement',
        'fields' => [
            'title' => [
                'label' => 'Title',
                'placeholder' => 'e.g. Black Friday Sale!',
                'help' => 'Short bold headline above the main text (optional)',
            ],
            'content' => [
                'label' => 'Announcement text',
                'placeholder' => 'Enter announcement text...',
                'help' => 'Main text (markdown supported)',
            ],
            'type' => [
                'label' => 'Announcement type',
                'help' => 'Determines the color scheme of the announcement',
            ],
            'icon' => [
                'label' => 'Icon',
                'placeholder' => 'ph.bold.megaphone-bold',
                'help' => 'Custom icon (defaults to type icon if empty)',
            ],
            'url' => [
                'label' => 'Bar link',
                'placeholder' => '/page or https://...',
                'help' => 'Makes the entire bar clickable (optional)',
            ],
            'target' => [
                'label' => 'Audience',
                'help' => 'Who will see this announcement',
            ],
            'has_button' => [
                'label' => 'Add primary button',
                'help' => 'Enable to show a button next to the text',
            ],
            'has_secondary_button' => [
                'label' => 'Add secondary button',
                'help' => 'Optional second button alongside the primary one',
            ],
            'button_text' => [
                'label' => 'Button text',
                'placeholder' => 'Learn more',
                'help' => 'Text for the button (optional)',
            ],
            'button_url' => [
                'label' => 'Button URL',
                'placeholder' => '/page or https://...',
                'help' => 'URL where the button leads',
            ],
            'button_icon' => [
                'label' => 'Button icon',
                'placeholder' => 'ph.bold.arrow-right-bold',
                'help' => 'Icon for the button (optional)',
            ],
            'button_new_tab' => [
                'label' => 'Open in new tab',
                'help' => 'If enabled, the link will open in a new tab',
            ],
            'button_style' => [
                'label' => 'Primary button style',
                'help' => 'Visual style of the primary button',
            ],
            'secondary_button_text' => [
                'label' => 'Secondary button text',
                'placeholder' => 'Learn more',
                'help' => 'Optional — second button next to the primary one',
            ],
            'secondary_button_url' => [
                'label' => 'Secondary button URL',
                'placeholder' => '/page or https://...',
                'help' => 'URL for the secondary button',
            ],
            'secondary_button_icon' => [
                'label' => 'Secondary button icon',
                'placeholder' => 'ph.bold.arrow-right-bold',
                'help' => 'Icon for the secondary button (optional)',
            ],
            'secondary_button_style' => [
                'label' => 'Secondary button style',
                'help' => 'Visual style of the secondary button',
            ],
            'secondary_button_new_tab' => [
                'label' => 'Secondary in new tab',
                'help' => 'If enabled, the secondary link opens in a new tab',
            ],
            'show_timer' => [
                'label' => 'Show countdown',
                'help' => 'Displays a countdown to the target date',
            ],
            'timer_at' => [
                'label' => 'Countdown target',
                'help' => 'Date and time the countdown runs to',
            ],
            'start_at' => [
                'label' => 'Show from',
                'help' => 'Start date and time (optional)',
            ],
            'end_at' => [
                'label' => 'Show until',
                'help' => 'End date and time (optional)',
            ],
            'closable' => [
                'label' => 'Can be closed',
                'help' => 'Allows the user to close the announcement',
            ],
            'is_active' => [
                'label' => 'Active',
                'help' => 'Whether to show the announcement on the site',
            ],
        ],
    ],
    'confirms' => [
        'delete' => 'Are you sure you want to delete this announcement?',
    ],
    'messages' => [
        'invalid_sort_data' => 'Invalid sorting data.',
        'created' => 'Announcement created successfully.',
        'updated' => 'Announcement updated successfully.',
        'deleted' => 'Announcement deleted successfully.',
        'not_found' => 'Announcement not found.',
    ],
];
