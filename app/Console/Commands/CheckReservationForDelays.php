<?php

namespace App\Console\Commands;

use App\Models\Reservation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckReservationForDelays extends Command
{
    protected $signature = 'app:check-reservation-for-delays';
    protected $description = 'Verifica se existe alguma reserva que ultrapassou o limite de entrega.';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $datenow = now();

        $reservation = Reservation::where('return_date', '>=', $datenow)
            ->where('reservation_finished', 'false')
            ->where('date_finished', 'false')
            ->get();

        if (empty($reservation)) {
            Log::info('Nenhuma reserva esta em atraso.');
        }

        if ($reservation) {
            foreach ($reservation as $reserve) {
                Log::warning("Reserva nº{$reserve->id} realizada por {$reserve->delivery_to} está em atraso.");
            }
        }
    }
}