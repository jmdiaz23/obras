<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * IVA sobre la utilidad (no sobre el subtotal): en Colombia es común
     * cotizar 19 % únicamente sobre el AIU de utilidad, como en el Excel
     * del ingeniero. Por defecto 0 para no alterar presupuestos existentes.
     */
    public function up(): void
    {
        Schema::table('proyectos', function (Blueprint $table) {
            $table->decimal('pct_iva_utilidad', 5, 2)->default(0)->after('pct_utilidad');
        });
    }

    public function down(): void
    {
        Schema::table('proyectos', function (Blueprint $table) {
            $table->dropColumn('pct_iva_utilidad');
        });
    }
};
