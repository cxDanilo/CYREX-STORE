<?php

use App\Models\PcBuilderOption;
use Illuminate\Database\Migrations\Migration;

/**
 * "Auriculares → Tipo de conexión" pasó de tener sus opciones fijas en
 * config/pc_builder.php a leerlas de pc_builder_options (dynamic:connection_type),
 * para que sean editables desde Admin. Esto carga los mismos dos valores
 * que ya estaban hardcodeados, así los auriculares ya cargados no cambian.
 */
return new class extends Migration
{
    public function up(): void
    {
        PcBuilderOption::firstOrCreate(
            ['group' => 'connection_type', 'value' => 'cableado'],
            ['label' => 'Cableado', 'sort_order' => 0]
        );

        PcBuilderOption::firstOrCreate(
            ['group' => 'connection_type', 'value' => 'inalambrico'],
            ['label' => 'Inalámbrico', 'sort_order' => 1]
        );
    }

    public function down(): void
    {
        PcBuilderOption::where('group', 'connection_type')->delete();
    }
};
