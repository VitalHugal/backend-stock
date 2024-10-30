<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;


return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('category', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description');
            $table->timestamps();
            $table->softDeletes();
        });

        DB::table('category')->insert([
            'name' => 'TI',
            'description' => 'Estoque de equipamentos tecnológicos e acessórios, como computadores, roteadores, cabos, dispositivos de rede e periféricos. Garante a reposição e manutenção de componentes necessários para o suporte de TI.',
        ]);
        DB::table('category')->insert([
            'name' => 'Limpeza',
            'description' => 'Produtos de limpeza e equipamentos como detergentes, desinfetantes, escovas, panos, e EPI específicos, garantindo que todos os materiais estejam em estoque para manter a higiene e organização dos espaços.',
        ]);
        DB::table('category')->insert([
            'name' => 'Oficina',
            'description' => 'Armazena ferramentas, peças de reposição e materiais de construção e reparo, como parafusos, tintas e equipamentos de manutenção. Suporta demandas de consertos e reformas dentro da organização.',
        ]);
        DB::table('category')->insert([
            'name' => 'Cenografia',
            'description' => 'Controla o estoque de materiais cenográficos, incluindo elementos decorativos, estruturas, tecidos e iluminação para montagem de cenários, contribuindo para a execução e manutenção de projetos cenográficos.',
        ]);
        DB::table('category')->insert([
            'name' => 'Uniformes',
            'description' => 'Responsável pelo estoque de uniformes e acessórios para os colaboradores, garantindo que estejam disponíveis em todas as variações de tamanho e modelo para reposição e distribuição conforme necessário.',
        ]);
        DB::table('category')->insert([
            'name' => 'EPI',
            'description' => 'Gerencia o estoque de equipamentos de segurança, como capacetes, luvas, óculos de proteção, máscaras e botas, visando garantir a segurança dos colaboradores e o cumprimento de normas de segurança no trabalho.',
        ]);
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category');
    }
};