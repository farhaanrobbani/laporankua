<x-admin::layout>
    <x-slot name="header">Edit User: {{ $user->name }}</x-slot>

    <div class="bg-white rounded-lg shadow-sm border border-gray-200 max-w-xl">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Form Edit User</h3>
        </div>
        <form method="POST" action="{{ route('admin.users.update', $user) }}" class="p-6 space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nama</label>
                <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required class="w-full border-gray-300 rounded-md text-sm" />
                @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required class="w-full border-gray-300 rounded-md text-sm" />
                @error('email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="role" class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                <select id="role" name="role" class="w-full border-gray-300 rounded-md text-sm">
                    <option value="user" {{ old('role', $user->role) === 'user' ? 'selected' : '' }}>User</option>
                    <option value="admin" {{ old('role', $user->role) === 'admin' ? 'selected' : '' }}>Admin</option>
                </select>
            </div>
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select id="status" name="status" class="w-full border-gray-300 rounded-md text-sm">
                    <option value="active" {{ old('status', $user->status) === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ old('status', $user->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password Baru (opsional)</label>
                <input type="password" id="password" name="password" class="w-full border-gray-300 rounded-md text-sm" placeholder="Kosongkan jika tidak diubah" />
                @error('password') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Konfirmasi Password Baru</label>
                <input type="password" id="password_confirmation" name="password_confirmation" class="w-full border-gray-300 rounded-md text-sm" />
            </div>
            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="px-4 py-2 bg-gray-900 text-white text-sm font-semibold rounded-md hover:bg-gray-800">Simpan</button>
                <a href="{{ route('admin.users.index') }}" class="px-4 py-2 text-sm font-semibold text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200">Batal</a>
            </div>
        </form>
    </div>
</x-admin::layout>
