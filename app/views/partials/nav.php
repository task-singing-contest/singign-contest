<nav class="navbar navbar-expand-md <?= theme('navbar-dark', 'navbar-light') ?>">
    <div class="container-fluid">
        <button class="navbar-toggler ms-auto ms-md-2" type="button" data-bs-toggle="collapse" data-bs-target="#navbar">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbar">
            <ul class="navbar-nav align-items-center <?= theme('bg-dark','bg-white') ?>">
                <li class="nav-item">
                    <a class="nav-link <?= theme('text-light', 'text-primary') ?>" href="/">Index</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= theme('text-light', 'text-primary') ?>" href="/history">History</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= theme('text-light', 'text-primary') ?>" href="/contact">Contact</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= theme('text-light', 'text-primary') ?>" href="#" onclick="toggleDarkMode()">Dark Mode</a>
                </li>
            </ul>
        </div>
    </div>
</nav>