<?php

namespace App\Http\Controllers;

use App\Support\InstallGate;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use PDO;
use Throwable;

class InstallController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $gate = $this->gate();
        $result = $gate->handle($request->isMethod('post') ? $request->all() : []);

        return response($result['body'], $result['code'], ['X-Robots-Tag' => 'noindex']);
    }

    private function gate(): InstallGate
    {
        $pdo = null;
        try {
            $connection = DB::connection()->getPdo();
            if ($connection instanceof PDO) {
                $pdo = $connection;
            }
        } catch (Throwable) {
            $pdo = null;
        }

        $gate = new InstallGate(
            base_path(),
            (string) config('deploy.env_file'),
            (string) config('deploy.lock'),
        );
        $gate->useRuntime([
            'APP_KEY' => (string) config('app.key'),
            'APP_ENV' => (string) config('app.env'),
            'APP_DEBUG' => config('app.debug') ? 'true' : 'false',
            'INSTALL_TOKEN' => (string) config('deploy.token'),
            'MAIL_MAILER' => (string) config('mail.default'),
            'MAIL_FROM_ADDRESS' => (string) config('mail.from.address'),
            'READ_ONLY_LEGACY' => config('legacy.read_only') ? 'true' : 'false',
            'SAFE_MODE' => config('legacy.safe_mode') ? 'true' : 'false',
        ], $pdo);

        return $gate;
    }
}
