<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;

class BackupController extends Controller
{
    private const REPO = 'cxDanilo/CYREX-STORE';

    // La API de GitHub para "disparar" un workflow no devuelve el ID del
    // run creado (es de las llamadas donde solo confirma que lo puso en
    // cola) — por eso el mensaje manda a revisar en GitHub en vez de
    // enlazar a un run puntual.
    public function trigger()
    {
        $token = config('services.github.backup_token');

        if (! $token) {
            return back()->with('error', 'Falta configurar GITHUB_BACKUP_TOKEN en el servidor para poder disparar el backup desde acá.');
        }

        $response = Http::withToken($token)
            ->acceptJson()
            ->post('https://api.github.com/repos/'.self::REPO.'/actions/workflows/backup.yml/dispatches', [
                'ref' => 'main',
            ]);

        if ($response->successful()) {
            return back()->with('status', 'Backup disparado — tarda uno o dos minutos. Podés seguirlo en GitHub → Actions → "Backup diario de dev.cyrexstore.com".');
        }

        return back()->with('error', 'GitHub respondió con un error ('.$response->status().') al intentar disparar el backup. Revisá que el token no haya vencido.');
    }
}
