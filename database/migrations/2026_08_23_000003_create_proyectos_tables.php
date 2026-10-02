<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proyectos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained()->cascadeOnDelete();
            $table->string('nombre');
            $table->string('cliente')->nullable();
            $table->string('ubicacion')->nullable();
            $table->string('estado', 20)->default('borrador');
            $table->decimal('pct_administracion', 5, 2)->default(10);
            $table->decimal('pct_imprevistos', 5, 2)->default(5);
            $table->decimal('pct_utilidad', 5, 2)->default(10);
            $table->timestamps();

            $table->index(['empresa_id', 'estado']);
        });

        Schema::create('proyecto_apu', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyecto_id')->constrained('proyectos')->cascadeOnDelete();
            $table->foreignId('apu_id')->nullable()->constrained('apus')->nullOnDelete();
            $table->string('item', 10);
            $table->boolean('es_capitulo')->default(false);
            $table->decimal('cantidad', 14, 4)->nullable();
            $table->unsignedInteger('orden')->default(0);

            $table->index(['proyecto_id', 'item']);
        });

        Schema::table('apus', function (Blueprint $table) {
            $table->decimal('dias_por_unidad', 14, 8)->nullable()->after('unidad');
            $table->string('cuadrilla', 30)->nullable()->after('dias_por_unidad');
        });
    }

    public function down(): void
    {
        Schema::table('apus', function (Blueprint $table) {
            $table->dropColumn(['dias_por_unidad', 'cuadrilla']);
        });
        Schema::dropIfExists('proyecto_apu');
        Schema::dropIfExists('proyectos');
    }
};
