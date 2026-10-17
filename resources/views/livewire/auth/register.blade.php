<div class="rounded-2xl bg-white p-6 shadow-xl shadow-slate-900/5 ring-1 ring-slate-200 sm:p-8">
    <h2 class="text-xl font-bold text-slate-900">Registra tu empresa</h2>
    <p class="mt-1 text-sm text-slate-500">Empieza a presupuestar en minutos.</p>

    <form class="mt-6 space-y-4" wire:submit="register">
        <div>
            <label for="nombre_empresa" class="mb-1.5 block text-sm font-medium text-slate-700">Nombre de la empresa</label>
            <input id="nombre_empresa" type="text" wire:model="nombre_empresa" required autofocus
                   class="campo @error('nombre_empresa') border-red-400 @enderror"
                   placeholder="Constructora El Ejemplo S.A.S.">
            @error('nombre_empresa') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="nit" class="mb-1.5 block text-sm font-medium text-slate-700">NIT <span class="font-normal text-slate-400">(opcional)</span></label>
            <input id="nit" type="text" wire:model="nit"
                   class="campo @error('nit') border-red-400 @enderror"
                   placeholder="900.123.456-7">
            @error('nit') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="name" class="mb-1.5 block text-sm font-medium text-slate-700">Tu nombre</label>
            <input id="name" type="text" wire:model="name" autocomplete="name" required
                   class="campo @error('name') border-red-400 @enderror"
                   placeholder="Juan Pérez">
            @error('name') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="email" class="mb-1.5 block text-sm font-medium text-slate-700">Correo electrónico</label>
            <input id="email" type="email" wire:model="email" autocomplete="email" required
                   class="campo @error('email') border-red-400 @enderror"
                   placeholder="tu@empresa.com">
            @error('email') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="password" class="mb-1.5 block text-sm font-medium text-slate-700">Contraseña</label>
                <input id="password" type="password" wire:model="password" autocomplete="new-password" required
                       class="campo @error('password') border-red-400 @enderror">
                @error('password') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="password_confirmation" class="mb-1.5 block text-sm font-medium text-slate-700">Confirmar</label>
                <input id="password_confirmation" type="password" wire:model="password_confirmation" autocomplete="new-password" required
                       class="campo">
            </div>
        </div>

        <button type="submit"
                class="flex w-full items-center justify-center gap-2 rounded-xl bg-amber-500 px-4 py-3 text-sm font-bold text-white shadow-lg shadow-amber-500/30 transition hover:bg-amber-600 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 active:scale-[0.98]">
            <span wire:loading.remove wire:target="register">Crear empresa</span>
            <span wire:loading wire:target="register">Creando...</span>
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-500">
        ¿Ya tienes cuenta?
        <a href="{{ route('login') }}" wire:navigate class="font-semibold text-amber-600 hover:text-amber-700">Inicia sesión</a>
    </p>
</div>
