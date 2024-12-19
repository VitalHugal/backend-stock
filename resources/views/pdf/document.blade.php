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
        top: 35px;
        right: 60px;
        text-align: right
    }

    #info-date-name p {
        font-size: 17px;
    }

    #paragrafh {
        text-transform: uppercase;
    }

    .line {
        border-top: 1px solid var(--color-black);
        margin-top: 25px;
        margin-bottom: 25px;
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

    .product-list {
        margin: 0;
        padding: 0;
        list-style: none;
    }

    .product-item {
        margin-bottom: 15px;
        padding: 10px;
        border: 1px solid var(--color-gray2);
        border-radius: 5px;
        background-color: var(--color-gray);
    }

    .product-item h4 {
        margin: 0 0 5px 0;
        font-size: 14px;
        color: var(--color-black);
    }

    .product-item p {
        margin: 0;
        font-size: 12px;
    }
</style>

<body>
    <header>
        <div id="logo">
            <img
                src="data:image/jpeg;base64,{{ base64_encode(file_get_contents(public_path('assets/img/logobizsys.jpg'))) }}">
        </div>
        <div id="info-date-name">
            <h1 id="title">Produtos em alerta</h1>
            <p id="paragrafh">{{ $name }} - {{ $date }}</p>
            {{-- <p>{{ $date }}</p> --}}
        </div>
    </header>
    <div class="separator">
        <div class="line"></div>
        <span></span>
    </div>
    <main>
        <div>
            <ul class="product-list">
                @forelse ($products as $product)
                    <li class="product-item">
                        <h4><strong>Produto ID:</strong> {{ $product['id'] }}</h4>
                        <p><strong>Nome:</strong> {{ $product['name'] }}</p>
                        <p><strong>Quantidade em estoque:</strong> {{ $product['quantity_stock'] }}</p>
                        <p><strong>Estoque mínimo:</strong> {{ $product['quantity_min'] }}</p>
                        <p><strong>Categoria:</strong> {{ $product['name-category'] ?? 'Sem categoria' }}</p>
                    </li>
                @empty
                    <li>Nenhum produto encontrado.</li>
                @endforelse
            </ul>
        </div>
    </main>
</body>

</html>
