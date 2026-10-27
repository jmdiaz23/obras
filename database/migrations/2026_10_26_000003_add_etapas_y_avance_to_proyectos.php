<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Etapas de obra: agrupan capítulos completos. El avance se registra con
     * cantidades ejecutadas por ítem para calcular valor ganado (financiero) y
     * avance físico cuando los ítems comparten la misma unidad.
     */
    public function up(): void
    {
        Schema::create('proyecto_etapas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyecto_id')->constrained('proyectos')->cascadeOnDelete();
            $table->string('nombre', 120);
            $table->unsignedInteger('orden')->default(0);
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->timestamps();

            $table->unique(['proyecto_id', 'nombre']);
        });

        Schema::table('proyecto_apu', function (Blueprint $table) {
            $table->foreignId('etapa_id')
                ->nullable()
                ->after('apu_id')
                ->constrained('proyecto_etapas')
                ->nullOnDelete();
            $table->decimal('cantidad_ejecutada', 14, 4)->default(0)->after('cantidad');
            $table->index(['etapa_id', 'es_capitulo']);
        });
    }

    public function down(): void
    {
        Schema::table('proyecto_apu', function (Blueprint $table) {
            $table->dropIndex(['etapa_id', 'es_capitulo']);
            $table->dropConstrainedForeignId('etapa_id');
            $table->dropColumn('cantidad_ejecutada');
        });

        Schema::dropIfExists('proyecto_etapas');
    }
};
