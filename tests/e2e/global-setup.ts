import { execSync } from 'node:child_process';

export default function globalSetup(): void {
  execSync('php artisan migrate --force', {
    cwd: process.cwd(),
    env: { ...process.env, READ_ONLY_LEGACY: 'false' },
    stdio: 'inherit',
  });
  execSync('php artisan cache:clear', { cwd: process.cwd(), stdio: 'ignore' });
  execSync('php artisan db:seed --class=Database\\\\Seeders\\\\E2eSeeder --force', {
    cwd: process.cwd(),
    env: { ...process.env, READ_ONLY_LEGACY: 'false', LEGACY_ALLOW_SEED: 'true' },
    stdio: 'inherit',
  });
}
