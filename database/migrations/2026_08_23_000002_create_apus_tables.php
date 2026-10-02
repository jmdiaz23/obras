<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('apus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained()->cascadeOnDelete();
            $table->string('codigo', 30)->nullable();
            $table->string('descripcion');
            $table->string('unidad', 10);
            $table->decimal('pct_herramientas', 5, 2)->default(10.00);
            $table->text('notas')->nullable();
            $table->timestamps();

            $table->unique(['empresa_id', 'codigo']);
            $table->index(['empresa_id', 'descripcion']);
        });

        Schema::create('apu_material', function (Blueprint $table) {
            $table->id();
            $table->foreignId('apu_id')->constrained('apus')->cascadeOnDelete();
            $table->foreignId('material_id')->nullable()->constrained('materiales')->nullOnDelete();
            $table->decimal('cantidad', 14, 6);
            $table->decimal('desperdicio_pct', 5, 2)->default(0);
            $table->unsignedInteger('orden')->default(0);
        });

        Schema::create('apu_manodeobra', function (Blueprint $table) {
            $table->id();
            $table->foreignId('apu_id')->constrained('apus')->cascadeOnDelete();
            $table->foreignId('cargo_id')->nullable()->constrained('cargos_mo')->nullOnDelete();
            $table->decimal('dias_por_unidad', 14, 6);
            $table->unsignedInteger('orden')->default(0);
        });

        Schema::create('apu_equipos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('apu_id')->constrained('apus')->cascadeOnDelete();
            $table->foreignId('equipo_id')->nullable()->constrained('equipos')->nullOnDelete();
            $table->decimal('horas_por_unidad', 14, 6);
            $table->unsignedInteger('orden')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('apu_equipos');
        Schema::dropIfExists('apu_manodeobra');
        Schema::dropIfExists('apu_material');
        Schema::dropIfExists('apus');
    }
};
