<div class="rounded-2xl bg-white p-6 shadow-xl shadow-slate-900/5 ring-1 ring-slate-200 sm:p-8">
    <h2 class="text-xl font-bold text-slate-900">Bienvenido de nuevo</h2>
    <p class="mt-1 text-sm text-slate-500">Ingresa para gestionar tus obras.</p>

    <form class="mt-6 space-y-4" wire:submit="authenticate">
        <div>
            <label for="email" class="mb-1.5 block text-sm font-medium text-slate-700">Correo electrónico</label>
            <input id="email" type="email" wire:model="email" autocomplete="email" required autofocus
                   class="campo
                          @error('email') border-red-400 @enderror"
                   placeholder="tu@empresa.com">
            @error('email') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <div class="mb-1.5 flex items-center justify-between">
                <label for="password" class="block text-sm font-medium text-slate-700">Contraseña</label>
            </div>
            <input id="password" type="password" wire:model="password" autocomplete="current-password" required
                   class="campo
                          @error('password') border-red-400 @enderror"
                   placeholder="••••••••">
            @error('password') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <label class="flex items-center gap-2">
            <input type="checkbox" wire:model="remember"
                   class="h-4 w-4 rounded border-slate-300 text-amber-600 focus:ring-amber-500">
            <span class="text-sm text-slate-600">Recuérdame</span>
        </label>

        <button type="submit"
                class="flex w-full items-center justify-center gap-2 rounded-xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-slate-900/20 transition hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900 focus:ring-offset-2 active:scale-[0.98]">
            <span wire:loading.remove wire:target="authenticate">Iniciar sesión</span>
            <span wire:loading wire:target="authenticate">Ingresando...</span>
        </button>
    </form>

    <p class="mt-6 text-center text-xs leading-5 text-slate-400">
        El acceso es por invitación. Si necesitas una cuenta,
        contacta al administrador de tu empresa.
    </p>
</div>
