<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * El alquiler de un equipo no siempre se mide en horas:
     * una formaleta se prorratea por m², un andamio por día, etc.
     *
     * - equipos.unidad_alquiler: unidad sugerida al agregar el equipo a un APU.
     * - apu_equipos.unidad: unidad de esta línea (hr, dia, m2, m3, und).
     * - apu_equipos.cantidad: reemplaza a horas_por_unidad (mismo dato, nombre honesto).
     * - apu_equipos.valor_unitario: tarifa por unidad cuando no se deriva de la tarifa día.
     */
    public function up(): void
    {
        Schema::table('equipos', function (Blueprint $table) {
            $table->string('unidad_alquiler', 10)->default('hr')->after('tarifa_dia');
        });

        Schema::table('apu_equipos', function (Blueprint $table) {
            $table->renameColumn('horas_por_unidad', 'cantidad');
        });

        Schema::table('apu_equipos', function (Blueprint $table) {
            $table->string('unidad', 10)->default('hr')->after('equipo_id');
            $table->decimal('valor_unitario', 18, 4)->nullable()->after('unidad');
        });
    }

    public function down(): void
    {
        Schema::table('apu_equipos', function (Blueprint $table) {
            $table->dropColumn(['unidad', 'valor_unitario']);
        });

        Schema::table('apu_equipos', function (Blueprint $table) {
            $table->renameColumn('cantidad', 'horas_por_unidad');
        });

        Schema::table('equipos', function (Blueprint $table) {
            $table->dropColumn('unidad_alquiler');
        });
    }
};
