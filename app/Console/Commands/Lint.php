<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class Lint extends Command
{
    protected $signature = 'core:lint';
    protected $description = 'Enforce one‑word public method names in Kernel and Livewire';

    public function handle()
    {
        $paths = [app_path('Kernel/Core.php'), app_path('Livewire/Desk.php')];
        $violations = [];
        foreach ($paths as $path) {
            if (!file_exists($path)) continue;
            $content = file_get_contents($path);
            preg_match_all('/public function (\w+)/', $content, $matches);
            foreach ($matches[1] as $method) {
                if (Str::contains($method, '_') || (preg_match('/[A-Z]/', $method) && $method !== lcfirst($method))) {
                    $violations[] = "$path: method `$method` is not one‑word";
                }
            }
        }
        if (empty($violations)) { $this->info('✅ One‑word rule passes.'); return 0; }
        foreach ($violations as $v) $this->error($v);
        return 1;
    }
}
