<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Correo electrónico')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" placeholder="admin@cucei.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Contraseña')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" placeholder="••••••••" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-brand-600 shadow-sm focus:ring-brand-500" name="remember">
                <span class="ms-2 text-sm text-gray-600">{{ __('Recordarme') }}</span>
            </label>
        </div>

        <!-- Captcha -->
        <div class="mt-6">
            <x-input-label value="Verificación de seguridad" />
            <div class="flex items-center gap-3 mt-2">
                <img id="captcha-imagen" src="{{ $captcha_imagen }}" alt="Captcha" class="h-14 border border-gray-300 rounded-lg bg-gray-50">
                <button type="button" id="btnCaptcha" class="text-sm text-brand-600 hover:underline font-medium">Actualizar imagen</button>
            </div>
            <div class="mt-3">
                <x-input-label for="captcha" value="¿Cuál es el resultado de la operación?" />
                <x-text-input id="captcha" class="block mt-1 w-full" type="text" name="captcha" maxlength="4" required autocomplete="off" placeholder="Escribe el resultado" />
                <x-input-error :messages="$errors->get('captcha')" class="mt-2" />
            </div>
        </div>

        <div class="flex items-center justify-between mt-4">
            <x-primary-button>
                {{ __('Iniciar sesión') }}
            </x-primary-button>
        </div>
    </form>

    <script>
        document.getElementById('btnCaptcha').addEventListener('click', async function () {
            const res = await fetch('{{ route('captcha.refresh') }}', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });
            const datos = await res.json();
            document.getElementById('captcha-imagen').src = datos.imagen;
        });
    </script>
</x-guest-layout>
