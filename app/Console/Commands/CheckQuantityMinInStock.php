<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ProductEquipament;
use Illuminate\Support\Facades\Log;

class CheckQuantityMinInStock extends Command
{
    protected $signature = 'stock:check-quantity';
    protected $description = 'Verifica se a quantidade de algum produto está abaixo do mínimo';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $lowStockProducts = ProductEquipament::whereColumn('quantity', '<=', 'quantity_min')->get();

        if ($lowStockProducts->isEmpty()) {
            Log::info('Nenhum produto atingiu a quantidade minima.');
        } else {
            foreach ($lowStockProducts as $product) {
                Log::warning("Produto {$product->name} está com estoque baixo. Quantidade atual: {$product->quantity}");
            }
        }
    }
}