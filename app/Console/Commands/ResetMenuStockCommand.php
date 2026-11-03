<?php

namespace App\Console\Commands;

use App\Models\Menu;
use Illuminate\Console\Command;

class ResetMenuStockCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'menus:reset-stock';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset daily_stock to null at 00:05 each day';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $updated = Menu::whereNotNull('daily_stock')->update(['daily_stock' => null]);

        $this->info("Berhasil mereset sisa porsi harian untuk {$updated} menu.");

        return Command::SUCCESS;
    }
}

