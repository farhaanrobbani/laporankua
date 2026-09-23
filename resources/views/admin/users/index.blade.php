<x-admin.layout>
    <x-slot name="header">User Management</x-slot>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <form method="GET" class="flex items-center gap-2">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari user..." class="border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md text-sm" />
                <button type="submit" class="px-3 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-md hover:bg-gray-200 dark:hover:bg-gray-600">Cari</button>
            </form>
            <a href="{{ route('admin.users.create') }}" style="background-color: #111827; color: #fff;" class="inline-flex items-center px-4 py-2 text-sm font-semibold rounded-md hover:opacity-90">
                + Tambah User
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Nama</th>
                        <th class="px-6 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Email</th>
                        <th class="px-6 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Role</th>
                        <th class="px-6 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Status</th>
                        <th class="px-6 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Plan</th>
                        <th class="px-6 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Terdaftar</th>
                        <th class="px-6 py-3 text-right font-medium text-gray-500 dark:text-gray-400">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($users as $user)
                        <tr>
                            <td class="px-6 py-4 text-gray-900 dark:text-gray-100 font-medium">{{ $user->name }}</td>
                            <td class="px-6 py-4 text-gray-600 dark:text-gray-400">{{ $user->email }}</td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 text-xs font-medium rounded-full {{ $user->role === 'admin' ? 'bg-indigo-100 dark:bg-indigo-900/50 text-indigo-800 dark:text-indigo-300' : 'bg-gray-100 dark:bg-gray-600 text-gray-800 dark:text-gray-200' }}">
                                    {{ $user->role }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 text-xs font-medium rounded-full {{ $user->status === 'active' ? 'bg-green-100 dark:bg-green-900/50 text-green-800 dark:text-green-300' : 'bg-red-100 dark:bg-red-900/50 text-red-800 dark:text-red-300' }}">
                                    {{ $user->status }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 text-xs font-medium rounded-full {{ $user->isPremium() ? 'bg-yellow-100 dark:bg-yellow-900/50 text-yellow-800 dark:text-yellow-300' : 'bg-gray-100 dark:bg-gray-600 text-gray-800 dark:text-gray-200' }}">
                                    {{ $user->isPremium() ? 'Premium' : 'Free' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-gray-500 dark:text-gray-400 text-sm">{{ $user->created_at->format('d M Y') }}</td>
                            <td class="px-6 py-4 text-right space-x-2">
                                <a href="{{ route('admin.users.edit', $user) }}" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 text-sm font-medium">Edit</a>
                                @if ($user->id !== auth()->id())
                                    <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="inline" onsubmit="return confirm('Yakin ingin menghapus user ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 text-sm font-medium">Hapus</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">Tidak ada user ditemukan</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            {{ $users->links() }}
        </div>
    </div>
</x-admin.layout>
