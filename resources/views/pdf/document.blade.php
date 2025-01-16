<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Nunito+Sans:ital,opsz,wght@0,6..12,200..1000;1,6..12,200..1000&display=swap"
        rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
        rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@300..700&display=swap" rel="stylesheet">
    <title></title>
</head>
<style>
    :root {
        --font-title: "Nunito Sans", sans-serif;
        --font-subtitle: "Poppins", sans-serif;
        --font-main: "Quicksand", sans-serif;
        --color-gray: #ddd;
        --color-black: #181818;
        --color-gray2: #969696;
    }

    * {
        padding: 0px;
        margin: 0px;
        border: 0px;
    }

    body {
        margin-left: 60px;
        margin-right: 60px;
    }

    #logo {
        display: flex;
        justify-content: space-around;
        align-items: center;
    }

    #logo img {
        margin-top: 30px;
        width: 80px;
    }

    img {
        position: relative;
    }

    #title {
        text-transform: uppercase;
        font-family: var(--font-title);
    }

    #info-date-name {
        position: absolute;
        top: 22px;
        right: 60px;
        text-align: right
    }

    #info-date-name p {
        font-size: 17px;
    }

    #paragrafh {
        /* padding-top: 10px; */
        text-transform: uppercase;
        font-size: 5px;
    }

    .line {
        border-top: 1px solid var(--color-black);
        margin-top: 10px;
        /* margin-bottom: 25px; */
    }

    #briefing {
        font-family: var(--font-title);
    }

    .subtitle {
        font-family: var(--font-subtitle);
        text-transform: uppercase;
        margin-bottom: 10px;
        font-size: 22px;
        font-weight: bold;
    }

    /* //------------------------------------------- */

    .product-list {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }

    .product-list th,
    .product-list td {
        text-align: left;
        padding: 10px;
        border: 1px solid var(--color-black);
    }

    .product-list td {
        text-align: center;
    }

    .product-list th {
        text-transform: uppercase;
        font-size: 13px;
        text-align: center;
        font-weight: bolder;
        background-color: var(--color-gray);
        color: var(--color-black);
        /* border: 1px solid var(--color-black); */
    }

    .product-list td {
        text-transform: uppercase;
        font-size: 14px;
    }

    @page {
        margin: 20mm;
    }

    .page {
        page-break-after: always;
        width: 100%;
        text-align: center;
    }

    .page-break {
        page-break-after: always;
    }

    footer {
        text-align: center;
        bottom: 10mm;
        width: 100%;
        text-align: center;
        font-size: 12px;
    }

    .page-number {
        margin-top: 10mm;
        text-transform: uppercase;
    }

    .abcd {
        color: transparent;
    }

    /* #notFound{
        text-align: center;
        text-transform: uppercase;
        padding-top: 10px;
    } */
</style>

<body>
    <header>
        <div id="logo">
            <img
                src="data:image/jpeg;base64,{{ base64_encode(file_get_contents(public_path('assets/img/logobizsys.jpg'))) }}">
        </div>
        <div id="info-date-name">
            <h1 id="title">Produtos em alerta</h1>
            <p id="paragrafh">{{ $name }} <span class="abcd">-</span>em<span class="abcd">-</span>
                {{ $date }}</p>
        </div>
    </header>

    <div class="separator">
        <div class="line"></div>
        <span></span>
    </div>
    {{-- <p id="notFound">{{$notFound}}</p> --}}
    <main>
        @foreach ($pages as $index => $products)
            <table class="product-list">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Qtd. em estoque</th>
                        <th>Qtd. mínima</th>
                        <th>Setor</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($products as $product)
                        <tr>
                            <td>{{ $product['name'] }}</td>
                            <td>{{ $product['quantity_stock'] }}</td>
                            <td>{{ $product['quantity_min'] }}</td>
                            <td>{{ $product['name-category'] ?? 'Sem categoria' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            {{-- Rodapé com número da página --}}
            <footer>
                <p class="page-number">Página {{ $index + 1 }} de {{ count($pages) }}</p>
            </footer>

            {{-- Adiciona uma quebra de página, exceto na última página --}}
            @if (!$loop->last)
                <div class="page-break"></div>
            @endif
        @endforeach
    </main>
</body>

</html>
