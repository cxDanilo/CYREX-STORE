<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;

class ChangelogController extends Controller
{
    /**
     * Los datos vienen de storage/app/changelog.txt y version.txt,
     * generados por el workflow de deploy (.github/workflows/deploy.yml)
     * a partir del propio historial de git en cada push — no hay nada
     * que cargar a mano acá, se arma solo en cada deploy.
     */
    public function index()
    {
        $path = storage_path('app/changelog.txt');
        $entries = [];

        if (File::exists($path)) {
            foreach (explode("\n", trim(File::get($path))) as $line) {
                if (! $line) {
                    continue;
                }

                [$hash, $date, $subject] = array_pad(explode('|', $line, 3), 3, null);

                $entries[] = [
                    'hash' => $hash ? substr($hash, 0, 7) : null,
                    'date' => $date ? Carbon::createFromFormat('Y-m-d H:i', $date) : null,
                    'subject' => $subject,
                ];
            }
        }

        return view('admin.changelog.index', [
            'entries' => $entries,
            'version' => $this->currentVersion(),
        ]);
    }

    /**
     * version.txt trae el total de commits (ej. "182") — se muestra
     * como "1.8.2" separando esos mismos dígitos en major.minor.patch,
     * no como un versionado semántico real con bumps a mano. Sigue
     * funcionando igual arriba de 999 (ej. 1042 -> "10.4.2").
     */
    public static function currentVersion(): ?string
    {
        $path = storage_path('app/version.txt');

        if (! File::exists($path)) {
            return null;
        }

        $count = (int) trim(File::get($path));

        return sprintf('%d.%d.%d', intdiv($count, 100), intdiv($count, 10) % 10, $count % 10);
    }
}
