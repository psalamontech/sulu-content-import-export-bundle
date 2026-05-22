<?php

declare(strict_types=1);

namespace SuluContentImportExportBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'sulu-content-import-export:install',
    description: 'Set up routes, webpack alias, and JS import for the Sulu Content Import/Export bundle.',
)]
class InstallCommand extends Command
{
    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $errors = false;

        $errors = !$this->installRoutes($io) || $errors;
        $errors = !$this->installWebpackAlias($io) || $errors;
        $errors = !$this->installJsImport($io) || $errors;

        if ($errors) {
            $io->warning('Some steps were skipped. Review warnings above and complete manually.');
        } else {
            $io->success('Installation complete. Run: cd assets/admin && npm run build');
        }

        return $errors ? Command::FAILURE : Command::SUCCESS;
    }

    private function installRoutes(SymfonyStyle $io): bool
    {
        $path = $this->projectDir . '/config/routes/sulu_content_import_export.yaml';

        if (file_exists($path)) {
            $io->writeln('<info>Routes file already exists, skipping.</info>');

            return true;
        }

        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $content = <<<YAML
sulu_content_import_export:
    resource: '@SuluContentImportExportBundle/config/routes_admin.yaml'
YAML;

        file_put_contents($path, $content . "\n");
        $io->writeln('<info>Created config/routes/sulu_content_import_export.yaml</info>');

        return true;
    }

    private function installWebpackAlias(SymfonyStyle $io): bool
    {
        $path = $this->projectDir . '/assets/admin/webpack.config.js';

        if (!file_exists($path)) {
            $io->warning('assets/admin/webpack.config.js not found. Add the webpack alias manually (see docs/installation.md).');

            return false;
        }

        $content = file_get_contents($path);

        if (str_contains($content, 'sulu-content-import-export-bundle')) {
            $io->writeln('<info>Webpack alias already present, skipping.</info>');

            return true;
        }

        $alias = <<<'JS'

    config.resolve = config.resolve || {};
    config.resolve.alias = {
        ...(config.resolve.alias || {}),
        'sulu-content-import-export-bundle': path.resolve(
            __dirname,
            '../../vendor/psalamon/sulu-content-import-export-bundle/assets/admin'
        ),
    };
    config.resolve.modules = [
        path.resolve(__dirname, 'node_modules'),
        ...(config.resolve.modules || ['node_modules']),
    ];

JS;

        // Insert before the last `return config;`
        $lastReturn = strrpos($content, 'return config;');
        if (false === $lastReturn) {
            $io->warning('Could not locate "return config;" in webpack.config.js. Add the webpack alias manually (see docs/installation.md).');

            return false;
        }

        $content = substr($content, 0, $lastReturn) . $alias . substr($content, $lastReturn);
        file_put_contents($path, $content);
        $io->writeln('<info>Added webpack alias to assets/admin/webpack.config.js</info>');

        return true;
    }

    private function installJsImport(SymfonyStyle $io): bool
    {
        $path = $this->projectDir . '/assets/admin/app.js';
        if (!file_exists($path)) {
            $path = $this->projectDir . '/assets/admin/index.js';
        }

        if (!file_exists($path)) {
            $io->warning('Neither assets/admin/app.js nor assets/admin/index.js found. Add the import manually (see docs/installation.md).');

            return false;
        }

        $content = file_get_contents($path);
        $filename = basename($path);

        if (str_contains($content, 'sulu-content-import-export-bundle/app')) {
            $io->writeln("<info>JS import already present in {$filename}, skipping.</info>");

            return true;
        }

        $import = "\nimport 'sulu-content-import-export-bundle/app';\n";
        file_put_contents($path, rtrim($content) . $import);
        $io->writeln("<info>Added import to assets/admin/{$filename}</info>");

        return true;
    }
}
