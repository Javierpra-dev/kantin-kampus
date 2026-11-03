<?php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PurgeOrderPhotosCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:purge-photos';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Purge location seating photos older than 24 hours for privacy';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $cutoff = now()->subHours(24);
        $orders = Order::whereNotNull('location_photo')
            ->where('created_at', '<', $cutoff)
            ->get();

        $count = 0;
        foreach ($orders as $order) {
            if ($order->location_photo && Storage::disk('local')->exists($order->location_photo)) {
                Storage::disk('local')->delete($order->location_photo);
            }
            $order->update(['location_photo' => null]);
            $count++;
        }

        $this->info("Berhasil membersihkan {$count} foto lokasi tempat duduk lama.");

        return Command::SUCCESS;
    }
}

