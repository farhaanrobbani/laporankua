<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-gray-800">
                        {{ __('Your email address is unverified.') }}

                        <button form="send-verification" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-green-600">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <x-input-label for="kecamatan" :value="__('Kecamatan')" />
                <x-text-input id="kecamatan" name="kecamatan" type="text" class="mt-1 block w-full" :value="old('kecamatan', $user->kecamatan)" />
                <x-input-error class="mt-2" :messages="$errors->get('kecamatan')" />
            </div>
            <div>
                <x-input-label for="nama_kepala_kua" :value="__('Nama Kepala KUA')" />
                <x-text-input id="nama_kepala_kua" name="nama_kepala_kua" type="text" class="mt-1 block w-full" :value="old('nama_kepala_kua', $user->nama_kepala_kua)" />
                <x-input-error class="mt-2" :messages="$errors->get('nama_kepala_kua')" />
            </div>
            <div>
                <x-input-label for="nip_kepala" :value="__('NIP Kepala KUA')" />
                <x-text-input id="nip_kepala" name="nip_kepala" type="text" class="mt-1 block w-full" :value="old('nip_kepala', $user->nip_kepala)" />
                <x-input-error class="mt-2" :messages="$errors->get('nip_kepala')" />
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-[1fr_60px] gap-4 items-end">
            <div>
                <x-input-label for="nama_kementerian" :value="__('Nama Kementerian')" />
                <x-text-input id="nama_kementerian" name="nama_kementerian" type="text" class="mt-1 block w-full" :value="old('nama_kementerian', $user->nama_kementerian ?? 'Kementerian Agama')" />
                <x-input-error class="mt-2" :messages="$errors->get('nama_kementerian')" />
            </div>
            <div>
                <x-input-label for="font_size_kop_kementerian" :value="__('px')" />
                <x-text-input id="font_size_kop_kementerian" name="font_size_kop_kementerian" type="number" min="8" max="20" class="mt-1 block w-full" :value="old('font_size_kop_kementerian', $user->font_size_kop_kementerian ?? '12')" />
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-[1fr_60px] gap-4 items-end">
            <div>
                <x-input-label for="nama_kantor_kota" :value="__('Nama Kantor Kemenag Kota')" />
                <x-text-input id="nama_kantor_kota" name="nama_kantor_kota" type="text" class="mt-1 block w-full" :value="old('nama_kantor_kota', $user->nama_kantor_kota)" placeholder="Contoh: Kantor Kementerian Agama Kota X" />
                <x-input-error class="mt-2" :messages="$errors->get('nama_kantor_kota')" />
            </div>
            <div>
                <x-input-label for="font_size_kop_kantor_kota" :value="__('px')" />
                <x-text-input id="font_size_kop_kantor_kota" name="font_size_kop_kantor_kota" type="number" min="8" max="20" class="mt-1 block w-full" :value="old('font_size_kop_kantor_kota', $user->font_size_kop_kantor_kota ?? '12')" />
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-[1fr_60px] gap-4 items-end">
            <div>
                <x-input-label for="nama_kantor" :value="__('Nama Kantor (KUA)')" />
                <x-text-input id="nama_kantor" name="nama_kantor" type="text" class="mt-1 block w-full" :value="old('nama_kantor', $user->nama_kantor)" placeholder="Contoh: Kantor Urusan Agama Kecamatan X" />
                <x-input-error class="mt-2" :messages="$errors->get('nama_kantor')" />
            </div>
            <div>
                <x-input-label for="font_size_kop_kantor" :value="__('px')" />
                <x-text-input id="font_size_kop_kantor" name="font_size_kop_kantor" type="number" min="8" max="20" class="mt-1 block w-full" :value="old('font_size_kop_kantor', $user->font_size_kop_kantor ?? '12')" />
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-[1fr_60px] gap-4 items-end">
            <div>
                <x-input-label for="alamat_kantor" :value="__('Alamat Kantor')" />
                <x-text-input id="alamat_kantor" name="alamat_kantor" type="text" class="mt-1 block w-full" :value="old('alamat_kantor', $user->alamat_kantor)" placeholder="Jl. ..." />
                <x-input-error class="mt-2" :messages="$errors->get('alamat_kantor')" />
            </div>
            <div>
                <x-input-label for="font_size_kop_alamat" :value="__('px')" />
                <x-text-input id="font_size_kop_alamat" name="font_size_kop_alamat" type="number" min="8" max="20" class="mt-1 block w-full" :value="old('font_size_kop_alamat', $user->font_size_kop_alamat ?? '10')" />
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-[1fr_1fr_60px] gap-4 items-end">
            <div>
                <x-input-label for="telepon_kantor" :value="__('Telepon Kantor')" />
                <x-text-input id="telepon_kantor" name="telepon_kantor" type="text" class="mt-1 block w-full" :value="old('telepon_kantor', $user->telepon_kantor)" />
                <x-input-error class="mt-2" :messages="$errors->get('telepon_kantor')" />
            </div>
            <div>
                <x-input-label for="email_kantor" :value="__('Email Kantor')" />
                <x-text-input id="email_kantor" name="email_kantor" type="email" class="mt-1 block w-full" :value="old('email_kantor', $user->email_kantor)" />
                <x-input-error class="mt-2" :messages="$errors->get('email_kantor')" />
            </div>
            <div>
                <x-input-label for="font_size_kop_kontak" :value="__('px')" />
                <x-text-input id="font_size_kop_kontak" name="font_size_kop_kontak" type="number" min="8" max="20" class="mt-1 block w-full" :value="old('font_size_kop_kontak', $user->font_size_kop_kontak ?? '10')" />
            </div>
        </div>

        <div>
            <x-input-label for="logo_kantor" :value="__('Logo Kop Surat')" />
            <div class="mt-1 flex items-center gap-4">
                @if ($user->logo_kantor)
                    <img src="{{ asset('storage/' . $user->logo_kantor) }}" alt="Logo" class="h-16 w-16 object-contain border rounded" />
                @else
                    <div class="h-16 w-16 border-2 border-dashed border-gray-300 rounded flex items-center justify-center text-gray-400 text-xs">Logo</div>
                @endif
                <div>
                    <input type="file" id="logo_kantor" name="logo_kantor" accept="image/*" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200" onchange="document.getElementById('logo-preview').src = window.URL.createObjectURL(this.files[0]); document.getElementById('logo-preview').classList.remove('hidden');" />
                    <p class="text-xs text-gray-500 mt-1">PNG, JPG, atau SVG. Maks 2MB.</p>
                    <img id="logo-preview" src="" alt="Preview" class="h-16 w-16 object-contain border rounded mt-2 hidden" />
                </div>
            </div>
            <x-input-error class="mt-2" :messages="$errors->get('logo_kantor')" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
