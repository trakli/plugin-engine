<?php

namespace Trakli\PluginEngine\Console\Commands;

class ListCommand extends PluginCommand
{
    protected $signature = 'plugin:list
                            {--debug : Show detailed error information for problematic plugins}';

    protected $description = 'List all available plugins.';

    public function handle()
    {
        $plugins = $this->pluginManager->discover();
        $debug = $this->option('debug');
        $hasErrors = false;
        $errorMessages = [];

        if ($plugins->isEmpty()) {
            $this->info('No plugins found.');

            return 0;
        }

        
        $rows = [];

        foreach ($plugins as $plugin) {
            $status = $plugin['enabled'] ? '<fg=green>Enabled</>' : '<fg=red>Disabled</>';
            $error = null;

            // Check for common plugin issues
            if (isset($plugin['error'])) {
                $hasErrors = true;
                $status = '<fg=yellow>Error</>';
                $error = $plugin['error'];
                $errorMessages[] = "Plugin {$plugin['id']}: {$error}";
            } elseif ($debug) {
                // Additional debug checks
                if (! class_exists($plugin['provider'] ?? '')) {
                    $error = "Provider class not found: {$plugin['provider']}";
                    $status = '<fg=yellow>Error</>';
                    $errorMessages[] = $error;
                }
            }

            $rows[] = [
                'id' => $plugin['id'] ?? '',
                'name' => $plugin['name'] ?? 'Unknown',
                'version' => $plugin['version'] ?? '1.0.0',
                'status' => $status,
                'description' => $plugin['description'] ?? 'No description',
                'error' => $error,
            ];
        }

        $this->table(
            ['ID', 'Name', 'Version', 'Status', 'Description', 'Error'],
            $rows
        );

        if ($hasErrors) {
            $this->warn('Some plugins have errors. Use --debug for more details.');
            
            if ($debug) {
                $this->line('');
                $this->warn('Detailed errors:');
                foreach ($errorMessages as $message) {
                    $this->line("  - {$message}");
                }
            }
        }

        return 0;
    }
}
