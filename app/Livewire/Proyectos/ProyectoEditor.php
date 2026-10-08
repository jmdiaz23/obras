<?php

namespace App\Livewire\Proyectos;

use App\Models\Apu;
use App\Models\Proyecto;
use App\Models\ProyectoApu;
use App\Services\PresupuestoService;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Editor de proyecto')]
class ProyectoEditor extends Component
{
    public Proyecto $proyecto;

    public array $cab = [];

    /** @var array<int, string> */
    public array $cantidades = [];

    public ?int $selApu = null;

    public bool $mostrarCapitulo = false;

    public array $capitulo = [
        'item' => '',
        'titulo' => '',
    ];

    public function mount(Proyecto $proyecto): void
    {
        $this->proyecto = $proyecto;
        $this->sincronizarCabecera();
        $this->sincronizarLineas();
    }

    private function sincronizarCabecera(): void
    {
        $this->cab = [
            'nombre' => (string) $this->proyecto->nombre,
            'cliente' => (string) ($this->proyecto->cliente ?? ''),
            'ubicacion' => (string) ($this->proyecto->ubicacion ?? ''),
            'estado' => (string) $this->proyecto->estado,
            'pct_administracion' => rtrim(rtrim(number_format($this->proyecto->pct_administracion, 2, '.', ''), '0'), '.'),
            'pct_imprevistos' => rtrim(rtrim(number_format($this->proyecto->pct_imprevistos, 2, '.', ''), '0'), '.'),
            'pct_utilidad' => rtrim(rtrim(number_format($this->proyecto->pct_utilidad, 2, '.', ''), '0'), '.'),
        ];
    }

    private function sincronizarLineas(): void
    {
        $this->proyecto->load('items');

        $this->cantidades = $this->proyecto->items
            ->reject(fn ($f) => $f->es_capitulo)
            ->mapWithKeys(fn ($f) => [
                $f->id => $f->cantidad !== null ? rtrim(rtrim(number_format($f->cantidad, 4, '.', ''), '0'), '.') : '',
            ])
            ->toArray();
    }

    private function proyectoFresco(): Proyecto
    {
        return $this->proyecto->fresh([
            'items.apu.materiales.material',
            'items.apu.manoObra.cargo',
            'items.apu.equipos.equipo',
            'items.apu.empresa',
        ]);
    }

    protected function rules(): array
    {
        return [
            'cab.nombre' => ['required', 'string', 'min:3', 'max:255'],
            'cab.cliente' => ['nullable', 'string', 'max:255'],
            'cab.ubicacion' => ['nullable', 'string', 'max:255'],
            'cab.estado' => ['required', Rule::in(array_keys(Proyecto::ESTADOS))],
            'cab.pct_administracion' => ['required', 'numeric', 'min:0', 'max:100'],
            'cab.pct_imprevistos' => ['required', 'numeric', 'min:0', 'max:100'],
            'cab.pct_utilidad' => ['required', 'numeric', 'min:0', 'max:100'],
            'capitulo.item' => [
                'required', 'string', 'max:10', 'regex:/^\d+(\.\d+)?$/',
                Rule::unique('proyecto_apu', 'item')->where('proyecto_id', $this->proyecto->id),
            ],
            'capitulo.titulo' => ['required', 'string', 'min:3', 'max:255'],
        ];
    }

    protected function messages(): array
    {
        return [
            'cab.nombre.required' => 'El nombre del proyecto es obligatorio.',
            'cab.nombre.min' => 'El nombre debe tener al menos 3 caracteres.',
            'capitulo.item.required' => 'El número del capítulo es obligatorio.',
            'capitulo.item.regex' => 'Usa solo números y puntos (ej: 1 o 2.1).',
            'capitulo.item.unique' => 'Ya existe un ítem con ese número en este proyecto.',
            'capitulo.titulo.required' => 'El título del capítulo es obligatorio.',
            'capitulo.titulo.min' => 'El título debe tener al menos 3 caracteres.',
        ];
    }

    public function guardarDatos(): void
    {
        foreach (array_keys($this->cab) as $campo) {
            $this->validateOnly("cab.$campo");
        }

        $this->proyecto->update([
            'nombre' => $this->cab['nombre'],
            'cliente' => $this->cab['cliente'] ?: null,
            'ubicacion' => $this->cab['ubicacion'] ?: null,
            'estado' => $this->cab['estado'],
            'pct_administracion' => (float) $this->cab['pct_administracion'],
            'pct_imprevistos' => (float) $this->cab['pct_imprevistos'],
            'pct_utilidad' => (float) $this->cab['pct_utilidad'],
        ]);
    }

    public function updatedCab(string $valor, string $campo): void
    {
        $campos = ['nombre', 'cliente', 'ubicacion', 'estado', 'pct_administracion', 'pct_imprevistos', 'pct_utilidad'];

        if (! in_array($campo, $campos, true)) {
            return;
        }

        $this->validateOnly("cab.$campo");

        $valorFinal = match ($campo) {
            'cliente', 'ubicacion' => $valor ?: null,
            'pct_administracion', 'pct_imprevistos', 'pct_utilidad' => (float) $valor,
            default => $valor,
        };

        $this->proyecto->update([$campo => $valorFinal]);
    }

    public function updatedCantidades(string $valor, string $idLinea): void
    {
        ProyectoApu::query()
            ->where('proyecto_id', $this->proyecto->id)
            ->where('es_capitulo', false)
            ->findOrFail((int) $idLinea)
            ->update(['cantidad' => max(0, (float) $valor)]);
    }

    public function abrirCapitulo(): void
    {
        $this->resetValidation(['capitulo.item', 'capitulo.titulo']);
        $siguiente = $this->proyectoFresco()->items
            ->filter(fn ($f) => ! Str::contains($f->item, '.'))
            ->max(fn ($f) => (int) $f->item);

        $this->capitulo = [
            'item' => (string) (($siguiente ?? 0) + 1),
            'titulo' => '',
        ];
        $this->mostrarCapitulo = true;
    }

    public function agregarCapitulo(): void
    {
        $datos = $this->validate([
            'capitulo.item' => $this->rules()['capitulo.item'],
            'capitulo.titulo' => $this->rules()['capitulo.titulo'],
        ])['capitulo'];

        ProyectoApu::create([
            'proyecto_id' => $this->proyecto->id,
            'apu_id' => null,
            'item' => $datos['item'],
            'es_capitulo' => true,
            'cantidad' => null,
            'orden' => ProyectoApu::where('proyecto_id', $this->proyecto->id)->max('orden') + 1,
        ]);

        $this->mostrarCapitulo = false;
        $this->sincronizarLineas();
    }

    public function agregarApu(): void
    {
        if (! $this->selApu) {
            return;
        }

        Apu::query()->findOrFail($this->selApu); // valida que sea de la empresa

        $items = $this->proyectoFresco()->items;
        $capitulos = $items->filter(fn ($f) => ! Str::contains($f->item, '.'));

        if ($capitulos->isEmpty()) {
            $codigo = '1';
        } else {
            $ultimo = $capitulos->sortByDesc(fn ($c) => (int) $c->item)->first();
            $n = $items
                ->filter(fn ($f) => str_starts_with($f->item, $ultimo->item.'.') && ! $f->es_capitulo)
                ->count() + 1;
            $codigo = $ultimo->item.'.'.$n;
        }

        ProyectoApu::create([
            'proyecto_id' => $this->proyecto->id,
            'apu_id' => $this->selApu,
            'item' => $codigo,
            'es_capitulo' => false,
            'cantidad' => 1,
            'orden' => ProyectoApu::where('proyecto_id', $this->proyecto->id)->max('orden') + 1,
        ]);

        $this->reset('selApu');
        $this->sincronizarLineas();
    }

    public function quitarLinea(int $id): void
    {
        ProyectoApu::query()->where('proyecto_id', $this->proyecto->id)->find($id)?->delete();

        $this->sincronizarLineas();
    }

    public function render()
    {
        $calculo = PresupuestoService::calcular($this->proyectoFresco());

        return view('livewire.proyectos.editor', [
            'estados' => Proyecto::ESTADOS,
            'calculo' => $calculo,
            'apusDisponibles' => Apu::query()->orderBy('codigo')->get(['id', 'codigo', 'descripcion', 'unidad']),
        ]);
    }
}
