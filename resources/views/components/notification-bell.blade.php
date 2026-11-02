<div
    x-data="notificationBell()"
    x-init="init()"
    class="relative me-4"
>
    <button
        type="button"
        @click="toggle()"
        class="relative inline-flex items-center rounded-md p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-700 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-900 dark:hover:text-gray-200"
        aria-label="{{ __('Notifications') }}"
    >
        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
        </svg>
        <span
            x-show="unreadCount > 0"
            x-cloak
            x-text="unreadCount > 9 ? '9+' : unreadCount"
            class="absolute -end-0.5 -top-0.5 inline-flex min-h-5 min-w-5 items-center justify-center rounded-full bg-red-600 px-1 text-[10px] font-bold text-white"
        ></span>
    </button>

    <div
        x-show="open"
        x-cloak
        @click.outside="open = false"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        class="absolute end-0 z-50 mt-2 w-96 origin-top-right rounded-lg bg-white shadow-xl ring-1 ring-black/5 dark:bg-gray-800 dark:ring-white/10"
    >
        <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3 dark:border-gray-700">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ __('Notifications') }}</h3>
            <button
                type="button"
                x-show="unreadCount > 0"
                @click="markAllRead()"
                class="text-xs font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400"
            >
                {{ __('Mark all read') }}
            </button>
        </div>

        <div class="max-h-96 overflow-y-auto">
            <template x-if="loading">
                <p class="px-4 py-6 text-sm text-gray-500 dark:text-gray-400">{{ __('Loading notifications...') }}</p>
            </template>

            <template x-if="! loading && notifications.length === 0">
                <p class="px-4 py-6 text-sm text-gray-500 dark:text-gray-400">{{ __('No new notifications.') }}</p>
            </template>

            <template x-for="notification in notifications" :key="notification.id">
                <button
                    type="button"
                    @click="openNotification(notification)"
                    class="block w-full border-b border-gray-100 px-4 py-3 text-left hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-900"
                >
                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100" x-text="notification.title"></p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400" x-text="notification.message"></p>
                    <p class="mt-1 text-[11px] text-gray-400 dark:text-gray-500" x-text="notification.created_at"></p>
                </button>
            </template>
        </div>

        <div class="border-t border-gray-200 px-4 py-3 dark:border-gray-700">
            <a href="{{ route('notifications.index') }}" class="text-xs font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">{{ __('View all notifications') }}</a>
        </div>
    </div>
</div>

<script>
    function notificationBell() {
        return {
            open: false,
            loading: false,
            unreadCount: 0,
            notifications: [],
            pollInterval: null,
            init() {
                this.fetchNotifications();
                this.pollInterval = setInterval(() => this.fetchNotifications(), 15000);
            },
            toggle() {
                this.open = ! this.open;

                if (this.open) {
                    this.fetchNotifications();
                }
            },
            fetchNotifications() {
                fetch('{{ route('notifications.unread') }}', {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                })
                    .then((response) => response.json())
                    .then((data) => {
                        this.unreadCount = data.unread_count;
                        this.notifications = data.notifications;
                        this.loading = false;
                    })
                    .catch(() => {
                        this.loading = false;
                    });
            },
            openNotification(notification) {
                fetch(`{{ url('notifications') }}/${notification.id}/read`, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    credentials: 'same-origin',
                }).finally(() => {
                    if (notification.action_url) {
                        window.location.href = notification.action_url;
                    } else {
                        this.fetchNotifications();
                        this.open = false;
                    }
                });
            },
            markAllRead() {
                fetch('{{ route('notifications.read-all') }}', {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    credentials: 'same-origin',
                })
                    .then((response) => response.json())
                    .then((data) => {
                        this.unreadCount = data.unread_count;
                        this.notifications = [];
                    });
            },
        };
    }
</script>
