<?php

namespace App\Livewire\Proyectos;

use App\Models\Apu;
use App\Models\Proyecto;
use App\Models\ProyectoApu;
use App\Models\ProyectoEtapa;
use App\Services\AvanceService;
use App\Services\PresupuestoService;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
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

    /** @var array<int, string> */
    public array $ejecutadas = [];

    public ?int $selApu = null;

    public bool $mostrarCapitulo = false;

    public bool $mostrarEtapa = false;

    public array $capitulo = [
        'item' => '',
        'titulo' => '',
        'etapa_id' => '',
    ];

    public array $etapa = [
        'nombre' => '',
        'fecha_inicio' => '',
        'fecha_fin' => '',
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
            'pct_iva_utilidad' => rtrim(rtrim(number_format($this->proyecto->pct_iva_utilidad, 2, '.', ''), '0'), '.'),
        ];
    }

    private function sincronizarLineas(): void
    {
        $this->proyecto->load('items');

        $items = $this->proyecto->items->reject(fn ($fila) => $fila->es_capitulo);

        $this->cantidades = $items
            ->mapWithKeys(fn ($fila) => [
                $fila->id => $fila->cantidad !== null
                    ? rtrim(rtrim(number_format($fila->cantidad, 4, '.', ''), '0'), '.')
                    : '',
            ])
            ->toArray();

        $this->ejecutadas = $items
            ->mapWithKeys(fn ($fila) => [
                $fila->id => $fila->cantidad_ejecutada !== null
                    ? rtrim(rtrim(number_format($fila->cantidad_ejecutada, 4, '.', ''), '0'), '.')
                    : '',
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
            'etapas',
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
            'cab.pct_iva_utilidad' => ['required', 'numeric', 'min:0', 'max:100'],
            'capitulo.item' => [
                'required', 'string', 'max:10', 'regex:/^\d+(\.\d+)?$/',
                Rule::unique('proyecto_apu', 'item')->where('proyecto_id', $this->proyecto->id),
            ],
            'capitulo.titulo' => ['required', 'string', 'min:3', 'max:255'],
            'capitulo.etapa_id' => [
                'nullable',
                Rule::exists('proyecto_etapas', 'id')->where('proyecto_id', $this->proyecto->id),
            ],
            'etapa.nombre' => [
                'required', 'string', 'min:3', 'max:120',
                Rule::unique('proyecto_etapas', 'nombre')->where('proyecto_id', $this->proyecto->id),
            ],
            'etapa.fecha_inicio' => ['nullable', 'date'],
            'etapa.fecha_fin' => ['nullable', 'date'],
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
            'etapa.nombre.required' => 'El nombre de la etapa es obligatorio.',
            'etapa.nombre.min' => 'El nombre de la etapa debe tener al menos 3 caracteres.',
            'etapa.nombre.unique' => 'Ya existe una etapa con ese nombre en este proyecto.',
            'etapa.fecha_inicio.date' => 'La fecha de inicio no es válida.',
            'etapa.fecha_fin.date' => 'La fecha de fin no es válida.',
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
            'pct_iva_utilidad' => (float) $this->cab['pct_iva_utilidad'],
        ]);
    }

    public function updatedCab(string $valor, string $campo): void
    {
        $campos = ['nombre', 'cliente', 'ubicacion', 'estado', 'pct_administracion', 'pct_imprevistos', 'pct_utilidad', 'pct_iva_utilidad'];

        if (! in_array($campo, $campos, true)) {
            return;
        }

        $this->validateOnly("cab.$campo");

        $valorFinal = match ($campo) {
            'cliente', 'ubicacion' => $valor ?: null,
            'pct_administracion', 'pct_imprevistos', 'pct_utilidad', 'pct_iva_utilidad' => (float) $valor,
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

    public function updatedEjecutadas(string $valor, string $idLinea): void
    {
        ProyectoApu::query()
            ->where('proyecto_id', $this->proyecto->id)
            ->where('es_capitulo', false)
            ->findOrFail((int) $idLinea)
            ->update(['cantidad_ejecutada' => max(0, (float) $valor)]);
    }

    public function abrirCapitulo(): void
    {
        $this->resetValidation(['capitulo.item', 'capitulo.titulo', 'capitulo.etapa_id']);
        $siguiente = $this->proyectoFresco()->items
            ->filter(fn ($fila) => ! Str::contains($fila->item, '.'))
            ->max(fn ($fila) => (int) $fila->item);

        $this->capitulo = [
            'item' => (string) (($siguiente ?? 0) + 1),
            'titulo' => '',
            'etapa_id' => '',
        ];
        $this->mostrarCapitulo = true;
    }

    public function agregarCapitulo(): void
    {
        $datos = $this->validate([
            'capitulo.item' => $this->rules()['capitulo.item'],
            'capitulo.titulo' => $this->rules()['capitulo.titulo'],
            'capitulo.etapa_id' => $this->rules()['capitulo.etapa_id'],
        ])['capitulo'];

        ProyectoApu::create([
            'proyecto_id' => $this->proyecto->id,
            'apu_id' => null,
            'etapa_id' => $datos['etapa_id'] ?: null,
            'item' => $datos['item'],
            'titulo' => $datos['titulo'],
            'es_capitulo' => true,
            'cantidad' => null,
            'cantidad_ejecutada' => 0,
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
        $capitulos = $items->filter(fn ($fila) => $fila->es_capitulo && ! Str::contains($fila->item, '.'));
        $etapaId = null;

        if ($capitulos->isEmpty()) {
            $codigo = '1';
        } else {
            $ultimo = $capitulos->sortByDesc(fn ($capitulo) => (int) $capitulo->item)->first();
            $etapaId = $ultimo->etapa_id;
            $n = $items
                ->filter(fn ($fila) => str_starts_with($fila->item, $ultimo->item.'.') && ! $fila->es_capitulo)
                ->count() + 1;
            $codigo = $ultimo->item.'.'.$n;
        }

        ProyectoApu::create([
            'proyecto_id' => $this->proyecto->id,
            'apu_id' => $this->selApu,
            'etapa_id' => $etapaId,
            'item' => $codigo,
            'es_capitulo' => false,
            'cantidad' => 1,
            'cantidad_ejecutada' => 0,
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

    public function abrirEtapa(): void
    {
        $this->resetValidation(['etapa.nombre', 'etapa.fecha_inicio', 'etapa.fecha_fin']);
        $this->etapa = ['nombre' => '', 'fecha_inicio' => '', 'fecha_fin' => ''];
        $this->mostrarEtapa = true;
    }

    public function agregarEtapa(): void
    {
        $datos = $this->validate([
            'etapa.nombre' => $this->rules()['etapa.nombre'],
            'etapa.fecha_inicio' => $this->rules()['etapa.fecha_inicio'],
            'etapa.fecha_fin' => $this->rules()['etapa.fecha_fin'],
        ])['etapa'];

        if ($datos['fecha_inicio'] && $datos['fecha_fin'] && $datos['fecha_fin'] < $datos['fecha_inicio']) {
            throw ValidationException::withMessages([
                'etapa.fecha_fin' => 'La fecha de fin no puede ser anterior a la de inicio.',
            ]);
        }

        ProyectoEtapa::create([
            'proyecto_id' => $this->proyecto->id,
            'nombre' => $datos['nombre'],
            'fecha_inicio' => $datos['fecha_inicio'] ?: null,
            'fecha_fin' => $datos['fecha_fin'] ?: null,
            'orden' => (ProyectoEtapa::where('proyecto_id', $this->proyecto->id)->max('orden') ?? 0) + 1,
        ]);

        $this->mostrarEtapa = false;
        $this->etapa = ['nombre' => '', 'fecha_inicio' => '', 'fecha_fin' => ''];
    }

    public function eliminarEtapa(int $id): void
    {
        $etapa = ProyectoEtapa::query()
            ->where('proyecto_id', $this->proyecto->id)
            ->findOrFail($id);

        ProyectoApu::query()
            ->where('proyecto_id', $this->proyecto->id)
            ->where('etapa_id', $etapa->id)
            ->update(['etapa_id' => null]);

        $etapa->delete();
    }

    public function asignarEtapa(int $idLinea, mixed $etapaId): void
    {
        $etapaId = ($etapaId === null || $etapaId === '' || $etapaId === '0') ? null : (int) $etapaId;

        if ($etapaId !== null) {
            ProyectoEtapa::query()
                ->where('proyecto_id', $this->proyecto->id)
                ->findOrFail($etapaId);
        }

        $linea = ProyectoApu::query()
            ->where('proyecto_id', $this->proyecto->id)
            ->findOrFail($idLinea);

        if (! $linea->es_capitulo) {
            return;
        }

        $linea->update(['etapa_id' => $etapaId]);

        ProyectoApu::query()
            ->where('proyecto_id', $this->proyecto->id)
            ->where('es_capitulo', false)
            ->where('item', 'like', $linea->item.'.%')
            ->update(['etapa_id' => $etapaId]);
    }

    public function render()
    {
        $proyecto = $this->proyectoFresco();
        $calculo = PresupuestoService::calcular($proyecto);

        return view('livewire.proyectos.editor', [
            'estados' => Proyecto::ESTADOS,
            'calculo' => $calculo,
            'avance' => AvanceService::calcular($proyecto, $calculo),
            'etapas' => $proyecto->etapas,
            'apusDisponibles' => Apu::query()->orderBy('codigo')->get(['id', 'codigo', 'descripcion', 'unidad']),
        ]);
    }
}
