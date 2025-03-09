<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kerala District & Town Selector</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Custom styles for multi-level dropdown */
        .dropdown-menu {
            min-width: 200px;
        }
        .dropdown-submenu {
            position: relative;
        }
        .dropdown-submenu .dropdown-menu {
            top: 0;
            left: 100%;
            margin-top: -5px;
            display: none;
            position: absolute;
        }
        .dropdown-submenu:hover .dropdown-menu {
            display: block;
        }
    </style>
</head>
<body class="p-5">

    <div class="dropdown">
        <button class="btn btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
            Select District
        </button>
        <ul class="dropdown-menu">
            <!-- Thiruvananthapuram -->
            <li class="dropdown-submenu">
                <a class="dropdown-item" href="#">Thiruvananthapuram</a>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="#">Kazhakoottam</a></li>
                    <li><a class="dropdown-item" href="#">Neyyattinkara</a></li>
                    <li><a class="dropdown-item" href="#">Attingal</a></li>
                    <li><a class="dropdown-item" href="#">Varkala</a></li>
                    <li><a class="dropdown-item" href="#">Kovalam</a></li>
                    <li><a class="dropdown-item" href="#">Poovar</a></li>
                    <li><a class="dropdown-item" href="#">Balaramapuram</a></li>
                    <li><a class="dropdown-item" href="#">Vizhinjam</a></li>
                    <li><a class="dropdown-item" href="#">Pothencode</a></li>
                    <li><a class="dropdown-item" href="#">Kattakada</a></li>
                </ul>
            </li>

            <!-- Kollam -->
            <li class="dropdown-submenu">
                <a class="dropdown-item" href="#">Kollam</a>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="#">Chavara</a></li>
                    <li><a class="dropdown-item" href="#">Karunagappally</a></li>
                    <li><a class="dropdown-item" href="#">Punalur</a></li>
                    <li><a class="dropdown-item" href="#">Kottarakkara</a></li>
                    <li><a class="dropdown-item" href="#">Paravur</a></li>
                    <li><a class="dropdown-item" href="#">Anchal</a></li>
                    <li><a class="dropdown-item" href="#">Pathanapuram</a></li>
                    <li><a class="dropdown-item" href="#">Sasthamcotta</a></li>
                    <li><a class="dropdown-item" href="#">Kundara</a></li>
                    <li><a class="dropdown-item" href="#">Oachira</a></li>
                </ul>
            </li>

            <!-- Alappuzha -->
            <li class="dropdown-submenu">
                <a class="dropdown-item" href="#">Alappuzha</a>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="#">Haripad</a></li>
                    <li><a class="dropdown-item" href="#">Kayamkulam</a></li>
                    <li><a class="dropdown-item" href="#">Cherthala</a></li>
                    <li><a class="dropdown-item" href="#">Ambalappuzha</a></li>
                    <li><a class="dropdown-item" href="#">Mavelikkara</a></li>
                    <li><a class="dropdown-item" href="#">Thiruvalla</a></li>
                    <li><a class="dropdown-item" href="#">Chengannur</a></li>
                    <li><a class="dropdown-item" href="#">Kuttanad</a></li>
                    <li><a class="dropdown-item" href="#">Punnapra</a></li>
                    <li><a class="dropdown-item" href="#">Edathua</a></li>
                </ul>
            </li>

            <!-- More Districts -->
            <li class="dropdown-submenu"><a class="dropdown-item" href="#">Pathanamthitta</a>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="#">Adoor</a></li>
                    <li><a class="dropdown-item" href="#">Ranni</a></li>
                    <li><a class="dropdown-item" href="#">Tiruvalla</a></li>
                    <li><a class="dropdown-item" href="#">Konni</a></li>
                    <li><a class="dropdown-item" href="#">Pandalam</a></li>
                    <li><a class="dropdown-item" href="#">Mallappally</a></li>
                    <li><a class="dropdown-item" href="#">Elanthoor</a></li>
                    <li><a class="dropdown-item" href="#">Kozhencherry</a></li>
                    <li><a class="dropdown-item" href="#">Thumpamon</a></li>
                    <li><a class="dropdown-item" href="#">Niranam</a></li>
                </ul>
            </li>

            <li class="dropdown-submenu"><a class="dropdown-item" href="#">Ernakulam</a>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="#">Kochi</a></li>
                    <li><a class="dropdown-item" href="#">Aluva</a></li>
                    <li><a class="dropdown-item" href="#">Perumbavoor</a></li>
                    <li><a class="dropdown-item" href="#">Muvattupuzha</a></li>
                    <li><a class="dropdown-item" href="#">North Paravur</a></li>
                    <li><a class="dropdown-item" href="#">Angamaly</a></li>
                    <li><a class="dropdown-item" href="#">Kothamangalam</a></li>
                    <li><a class="dropdown-item" href="#">Tripunithura</a></li>
                    <li><a class="dropdown-item" href="#">Kakkanad</a></li>
                    <li><a class="dropdown-item" href="#">Vypin</a></li>
                </ul>
            </li>

            <li class="dropdown-submenu"><a class="dropdown-item" href="#">Kozhikode</a>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="#">Vadakara</a></li>
                    <li><a class="dropdown-item" href="#">Koyilandy</a></li>
                    <li><a class="dropdown-item" href="#">Feroke</a></li>
                    <li><a class="dropdown-item" href="#">Mukkam</a></li>
                    <li><a class="dropdown-item" href="#">Ramanattukara</a></li>
                    <li><a class="dropdown-item" href="#">Balussery</a></li>
                    <li><a class="dropdown-item" href="#">Perambra</a></li>
                    <li><a class="dropdown-item" href="#">Nadapuram</a></li>
                    <li><a class="dropdown-item" href="#">Thamarassery</a></li>
                    <li><a class="dropdown-item" href="#">Payyoli</a></li>
                </ul>
            </li>
        </ul>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
