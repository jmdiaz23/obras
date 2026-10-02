<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proveedores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained()->cascadeOnDelete();
            $table->string('nombre');
            $table->string('contacto')->nullable();
            $table->string('telefono', 30)->nullable();
            $table->string('email')->nullable();
            $table->text('notas')->nullable();
            $table->timestamps();

            $table->unique(['empresa_id', 'nombre']);
        });

        Schema::create('materiales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained()->cascadeOnDelete();
            $table->foreignId('proveedor_id')->nullable()->constrained('proveedores')->nullOnDelete();
            $table->string('codigo', 20);
            $table->string('descripcion');
            $table->string('unidad', 10);
            $table->decimal('precio', 18, 4)->default(0);
            $table->timestamps();

            $table->unique(['empresa_id', 'codigo']);
            $table->index(['empresa_id', 'descripcion']);
        });

        Schema::create('equipos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained()->cascadeOnDelete();
            $table->foreignId('proveedor_id')->nullable()->constrained('proveedores')->nullOnDelete();
            $table->string('codigo', 20);
            $table->string('descripcion');
            $table->string('tipo', 60)->nullable();
            $table->decimal('tarifa_dia', 18, 4)->default(0);
            $table->timestamps();

            $table->unique(['empresa_id', 'codigo']);
        });

        Schema::create('cargos_mo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained()->cascadeOnDelete();
            $table->string('nombre');
            $table->decimal('jornal', 18, 4)->default(0);
            $table->string('descripcion')->nullable();
            $table->timestamps();

            $table->unique(['empresa_id', 'nombre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cargos_mo');
        Schema::dropIfExists('equipos');
        Schema::dropIfExists('materiales');
        Schema::dropIfExists('proveedores');
    }
};
