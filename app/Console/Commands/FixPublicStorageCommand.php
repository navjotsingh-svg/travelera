<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class FixPublicStorageCommand extends Command
{
    protected $signature = 'storage:fix-public';

    protected $description = 'Remove a broken public/storage link and create a relative symlink that works after deploy';

    public function handle(): int
    {
        $link = public_path('storage');
        $target = storage_path('app/public');

        File::ensureDirectoryExists($target);
        File::ensureDirectoryExists(public_path('uploads/blogs'));
        File::ensureDirectoryExists(public_path('uploads/packages'));

        if (is_link($link) || file_exists($link)) {
            if (is_link($link)) {
                File::delete($link);
                $this->info('Removed old public/storage symlink.');
            } elseif (is_dir($link)) {
                $this->warn('public/storage is a real directory. Leaving it alone.');
                $this->line('Move its files into storage/app/public, then delete public/storage and re-run this command.');

                return self::SUCCESS;
            } else {
                File::delete($link);
            }
        }

        // Relative link survives moving the project between machines/hosts.
        $relativeTarget = '../storage/app/public';
        if (! symlink($relativeTarget, $link)) {
            $this->error('Could not create symlink. Your host may block symlinks.');
            $this->line('Uploads will still work from /uploads/... after deploy.');

            return self::FAILURE;
        }

        @chmod($target, 0755);
        @chmod($link, 0755);

        $this->info('Linked public/storage -> '.$relativeTarget);
        $this->info('Also ready: public/uploads/blogs and public/uploads/packages');

        return self::SUCCESS;
    }
}
