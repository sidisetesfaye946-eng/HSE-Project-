<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Notifications') }}</h2>
            @if (auth()->user()->unreadNotifications->count() > 0)
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                        {{ __('Mark all as read') }}
                    </button>
                </form>
            @endif
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="overflow-hidden rounded-xl bg-white shadow-sm dark:bg-gray-800">
                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($notifications as $notification)
                        <div @class([
                            'px-6 py-4',
                            'bg-indigo-50/60 dark:bg-indigo-900/20' => $notification->read_at === null,
                        ])>
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                        {{ $notification->data['title'] ?? __('Notification') }}
                                    </p>
                                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                                        {{ $notification->data['message'] ?? '' }}
                                    </p>
                                    <p class="mt-2 text-xs text-gray-400 dark:text-gray-500">
                                        {{ $notification->created_at->diffForHumans() }}
                                        @if ($notification->read_at === null)
                                            · <span class="font-medium text-indigo-600 dark:text-indigo-400">{{ __('Unread') }}</span>
                                        @endif
                                    </p>
                                </div>
                                @if (! empty($notification->data['action_url']))
                                    <a href="{{ $notification->data['action_url'] }}" class="shrink-0 text-sm text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">{{ __('View') }}</a>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="px-6 py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                            {{ __('No notifications yet.') }}
                        </div>
                    @endforelse
                </div>
                @if ($notifications->hasPages())
                    <div class="border-t border-gray-200 px-6 py-4 dark:border-gray-700">
                        {{ $notifications->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
