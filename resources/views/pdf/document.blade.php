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

    #briefing p {
        font-family: var(--font-main);
        font-size: 17px;
    }

    #time-groups {
        position: absolute;
        right: 65px;
        text-transform: uppercase;
    }

    #time-groups-name {
        text-transform: uppercase;
        font-family: var(--font-main);
    }

    #groups {
        padding: 5px;
        border: 1px solid var(--color-black);
        margin-bottom: 7px;
    }

    #timeTotal {
        font-family: var(--font-main);
        font-weight: bold;
        background-color: var(--color-gray);
        padding: 5px;
        margin-top: 25px;
    }

    #timeTotalParagrafh {
        text-transform: uppercase;
        font-size: 17px;
    }

    #timeTotalParagrafh #hours {
        position: absolute;
        right: 65px;
    }

    #observations {
        font-family: var(--font-main);
        font-size: 16px;
    }

    #obsResult,
    #list-observations {
        position: relative;
        font-family: var(--font-main);
        font-size: 16.8px;
        left: 8px;
    }

    #list-observations {
        margin-bottom: 10px;

    }
</style>

<body>
    <header>
        <div id="logo">
            <img
                src="data:image/jpeg;base64,{{ base64_encode(file_get_contents(public_path('assets/img/logobizsys.jpg'))) }}">
        </div>
        <div id="info-date-name">
            {{-- <h1 id="title">{{ $name }}</h1> --}}
            <p id="paragrafh">Gerado em: {{ $date }}</p>
        </div>
    </header>
    <div class="separator">
        <div class="line"></div>
        <span></span>
    </div>
    <main>
        <div id="briefing">
            <h2 class="subtitle">briefing</h2>
            <p>{{ $briefing }}</p>
        </div>
        <div class="separator">
            <div class="line"></div>
            <span></span>
        </div>
        <div id="info-project">
            <h2 class="subtitle">Plano de Ação</h2>

            <div>
                {!! $groups !!}
            </div>

            <div id="timeTotal">
                <p id="timeTotalParagrafh">Tempo total do projeto: <span
                        id="hours">{{ $hours }}H:{{ $minutes }}m</span></p>
            </div>
        </div>
        <div class="separator">
            <div class="line"></div>
            <span></span>
        </div>
        <div id="observations">
            <h2 class="subtitle">Observações</h2>
            <div>
                {!! $observations !!}
            </div>
        </div>
    </main>
</body>

</html>
