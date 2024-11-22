<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestEmailCommand extends Command
{
    protected $signature = 'email:test {to}';
    protected $description = 'Envie um e-mail de teste';

    public function handle()
    {
        $to = $this->argument('to');

        Mail::raw('Este é um e-mail de teste!', function ($message) use ($to) {
            $message->to($to)
                    ->subject('Teste de Envio - AWS SES');
        });

        $this->info('E-mail enviado para ' . $to);
    }
}