@extends($activeTemplate . 'layouts.frontend')

@section('content')

    @if ($announcements->count())
        <section class="py-0 py-md-0 border-0" style="background-color: #fff;">
            <div class="container">
                <div id="announcementCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="3000"
                    data-bs-pause="false">

                    {{-- ✅ Dots Indicators --}}
                    <div class="carousel-indicators">
                        @foreach ($announcements as $key => $item)
                            <button type="button" data-bs-target="#announcementCarousel"
                                data-bs-slide-to="{{ $key }}"
                                class="@if ($key == 0) active @endif"
                                aria-current="@if ($key == 0) true @endif"
                                aria-label="Slide {{ $key + 1 }}"></button>
                        @endforeach
                    </div>

                    <div class="carousel-inner">
                        @foreach ($announcements as $key => $item)
                            @php $mediaType = $item->media_type ?? 'image'; @endphp
                            <div class="carousel-item @if ($key == 0) active @endif">
                                <div class="d-flex align-items-center justify-content-center py-0 py-md-2">
                                    @if ($item->title || $item->description)
                                        <div class="row align-items-center g-4 w-100">
                                            <div class="col-lg-6">
                                                @if (!empty($item->media_path))
                                                    @if ($mediaType === 'video')
                                                        <div class="ratio ratio-16x9 rounded-3 shadow-sm overflow-hidden">
                                                            <video controls class="w-100 rounded shadow-sm">
                                                                <source src="{{ announcementAsset($item->media_path) }}"
                                                                    type="video/mp4">
                                                            </video>
                                                        </div>
                                                    @else
                                                        <img src="{{ announcementAsset($item->media_path) }}"
                                                            class="img-fluid rounded shadow-sm w-100" />
                                                    @endif
                                                @endif
                                            </div>
                                            <div class="col-lg-6">
                                                @if ($item->title)
                                                    <h3 class="fw-bold text-primary mb-3">📢 {{ $item->title }}</h3>
                                                @endif
                                                @if ($item->description)
                                                    <p class="mb-4 text-muted fs-6">{{ $item->description }}</p>
                                                @endif
                                                <a href="#games" class="btn btn-outline-primary px-4 fw-semibold">
                                                    View Games <i class="las la-arrow-right ms-2"></i>
                                                </a>
                                            </div>
                                        </div>
                                    @else
                                        <div class="row w-100">
                                            <div class="col-12">
                                                @if (!empty($item->media_path))
                                                    @if ($mediaType === 'video')
                                                        <div class="ratio ratio-21x9 rounded-3 shadow-sm overflow-hidden">
                                                            <video controls class="w-100 rounded shadow-sm">
                                                                <source src="{{ announcementAsset($item->media_path) }}"
                                                                    type="video/mp4">
                                                            </video>
                                                        </div>
                                                    @else
                                                        <img src="{{ announcementAsset($item->media_path) }}"
                                                            class="img-fluid rounded shadow-sm w-100" />
                                                    @endif
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    @endif

    {{-- ✅ ACTIVE GAMES SECTION --}}
    <div class="container pt-1 pb-2  py-md-3" id="games">
        <h2 class="text-center fw-bold mb-5 display-6 text-primary"></h2>

        <div class="row g-4">
            @forelse ($games as $game)
                <div class="col-md-4">
                    <div class="card h-100 border-primary border-2 overflow-hidden">
                        <div class="card-body p-4 d-flex flex-column">
                            <h5 class="text-center fw-bold mb-2">{{ $game->name }}</h5>
                            <p class="text-center text-muted mb-3">
                                Price: <strong>PKR {{ $game->ticket_price }}</strong>
                            </p>

                            <div class="d-flex justify-content-center gap-3 mb-3 countdown-timer"
                                data-close-time="{{ \Carbon\Carbon::today()->format('Y-m-d') }} {{ $game->close_time }}"
                                id="countdown-{{ $game->id }}">
                                @foreach (['Hours' => 'hours', 'Mins' => 'mins', 'Secs' => 'secs'] as $label => $class)
                                    <div class="text-center">
                                        <div class="countdown-circle flat-style">
                                            <span class="fw-bold text-primary {{ $class }}">00</span>
                                        </div>
                                        <small class="text-muted d-block mt-1">{{ $label }}</small>
                                    </div>
                                @endforeach
                            </div>

                            <div class="p-2  number-container">
                                <div class="d-flex flex-wrap justify-content-center gap-2">
                                    @for ($i = $game->range_start; $i <= $game->range_end; $i++)
                                        <div class="number-box flat-style" data-game="{{ $game->id }}"
                                            data-number="{{ sprintf('%02d', $i) }}" onclick="handleNumberClick(this)">
                                            {{ sprintf('%02d', $i) }}
                                        </div>
                                    @endfor
                                </div>
                                <input type="hidden" name="selected_numbers_game_{{ $game->id }}"
                                    id="selectedNumbersGame{{ $game->id }}">
                            </div>

                            <div class="mt-auto">
                                <button
                                    class="btn btn-primary w-100 fw-semibold d-flex align-items-center justify-content-center gap-2 py-2"
                                    onclick="confirmPurchase({{ $game->id }})">
                                    <i class="las la-shopping-cart fs-5"></i> Buy Now
                                </button>
                            </div>

                        </div>
                    </div>
                </div>
            @empty
                <p class="text-center text-muted">No active games available at the moment.</p>
            @endforelse
        </div>
    </div>

    {{-- ✅ LEADERBOARD SECTION (CARD STYLE) --}}
    <section class="py-5 bg-white border-top">
        <div class="container">
            <div class="row g-4">

                @foreach ($leaderboardGroups as $gameId => $winners)
                    @php $game = $winners->first()->game; @endphp

                    <div class="col-md-6">
                        <div class="p-4 rounded-4 shadow h-100 d-flex flex-column justify-content-between"
                            style="background: linear-gradient(to bottom right, #058ec8, #012b54);">
                            <h4 class="text-white fw-semibold mb-4 border-bottom pb-2">
                                🏆 Winners of <span class="text-warning">{{ $game->name ?? 'Game #' . $gameId }}</span>
                            </h4>

                            {{-- Winner List --}}
                            <div class="winner-slider text-white">
                                @foreach ($winners as $winner)
                                    <div class="mb-4 pb-3 border-bottom border-white">
                                        <h5 class="fw-semibold text-light mb-1">
                                            {{ $winner->user->firstname ?? 'Guest' }}
                                        </h5>
                                        <p class="mb-1 small text-light">
                                            🎉 Prize: <strong class="text-warning">
                                                PKR {{ number_format($winner->winning_prize ?? 0) }}
                                            </strong>
                                        </p>
                                        <p class="mb-0 small text-light">
                                            🎟️ Ticket ID: <span class="text-white-50">
                                                #{{ getTicketId($winner->ticket_id) }}
                                            </span>
                                        </p>
                                    </div>
                                @endforeach
                            </div>

                            {{-- Optional Button --}}
                            {{-- <a href="#" class="btn btn-outline-light fw-bold w-100 mt-auto">
                            View All
                        </a> --}}
                        </div>
                    </div>
                @endforeach

            </div>
        </div>
    </section>



    {{-- LOGIN MODAL --}}
    <div class="modal fade" id="loginModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow rounded-4 p-4 bg-light bg-opacity-75 backdrop-blur">

                <a href="{{ route('home') }}" class="logo text-center"><img src="{{ siteLogo('dark') }}"
                        alt="images"></a>

                <h5 class="fw-bold text-primary text-center mb-3">Login to Continue</h5>

                <form method="POST" action="{{ route('user.login') }}">
                    @csrf
                    <input type="hidden" name="redirect_game_id" id="redirectGameIdInput">

                    <div class="mb-3">
                        <label class="form-label text-secondary">Email or Username</label>
                        <input type="text" class="form-control rounded-pill" name="username" required />
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-secondary">Password</label>
                        <input type="password" class="form-control rounded-pill" name="password" required
                            id="login-password" />
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary rounded-pill">Login</button>
                    </div>

                    <div class="text-center mt-3">
                        <small class="text-muted">Don't have an account?
                            <a href="#" data-bs-toggle="modal" data-bs-target="#registerModal"
                                data-bs-dismiss="modal" class="text-decoration-none text-primary fw-semibold">Register</a>
                        </small>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- REGISTER MODAL --}}
    <div class="modal fade" id="registerModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow rounded-4 p-4 bg-light bg-opacity-75 backdrop-blur">
                <a href="{{ route('home') }}" class="logo text-center"><img src="{{ siteLogo('dark') }}"
                        alt="images"></a>
                <h5 class="fw-bold text-center mb-3">Register to Play</h5>

                <form method="POST" action="{{ route('user.register') }}">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label text-secondary">First Name</label>
                        <input type="text" name="firstname" class="form-control rounded-pill" required />
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-secondary">Last Name</label>
                        <input type="text" name="lastname" class="form-control rounded-pill" required />
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-secondary">Email Address</label>
                        <input type="email" name="email" class="form-control rounded-pill" required />
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-secondary">Password</label>
                        <input type="password" name="password" class="form-control rounded-pill" required
                            id="register-password" />
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-secondary">Confirm Password</label>
                        <input type="password" name="password_confirmation" class="form-control rounded-pill" required />
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-success rounded-pill">Register</button>
                    </div>

                    <div class="text-center mt-3">
                        <small class="text-muted">Already have an account?
                            <a href="#" data-bs-toggle="modal" data-bs-target="#loginModal"
                                data-bs-dismiss="modal" class="text-decoration-none fw-semibold">Login</a>
                        </small>
                    </div>
                </form>
            </div>
        </div>
    </div>


    {{-- INSUFFICIENT BALANCE MODAL --}}
    <div class="modal fade" id="insufficientModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-center p-4">
                <h5 class="text-danger fw-bold mb-3">Insufficient Balance</h5>
                <p>Your wallet does not have enough funds to complete this purchase.</p>
                <a href="{{ route('user.deposit.index') }}" class="btn">Deposit Now</a>
            </div>
        </div>
    </div>

    <!-- Game Closed Modal -->
    <div class="modal fade" id="gameClosedModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow rounded-4 p-4 text-center">
                <h5 class="text-danger fw-bold mb-3">Game Closed</h5>
                <p id="gameClosedMessage">This game is not open right now. Please try during the open hours.</p>
                <button type="button" class="btn btn-secondary mt-2" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>


    {{-- Toast Container --}}
    <div class="position-fixed start-50 translate-middle-x p-3" style="top: 100px; z-index: 1100;">

        <div id="numberLimitToast" class="toast align-items-center text-white bg-danger border-0 shadow" role="alert"
            aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    ⚠️ You can only select up to 6 numbers per game.
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"
                    aria-label="Close"></button>
            </div>
        </div>
    </div>


    {{-- JS --}}
    <script>
        const selectedNumbersByGame = {};

        function handleNumberClick(el) {
            const number = el.dataset.number;
            const gameId = el.dataset.game;
            const isLoggedIn = {{ auth()->check() ? 'true' : 'false' }};

            if (!isLoggedIn) {
                localStorage.setItem('redirectGameId', gameId); // ✅ Save game ID
                new bootstrap.Modal(document.getElementById('loginModal')).show();
                return;
            }

            if (!selectedNumbersByGame[gameId]) selectedNumbersByGame[gameId] = [];

            const selected = selectedNumbersByGame[gameId];

            if (el.classList.contains('selected')) {
                el.classList.remove('selected');
                const index = selected.indexOf(number);
                if (index !== -1) selected.splice(index, 1);
            } else {
                if (selected.length >= 6) {
                    const toastEl = document.getElementById('numberLimitToast');
                    const toast = new bootstrap.Toast(toastEl);
                    toast.show();
                    return;
                }
                el.classList.add('selected');
                selected.push(number);
            }
            document.getElementById('selectedNumbersGame' + gameId).value = selected.join(',');
        }

        // Confirm purchase function

        function confirmPurchase(gameId) {
            const isLoggedIn = {{ auth()->check() ? 'true' : 'false' }};
            if (!isLoggedIn) {
                new bootstrap.Modal(document.getElementById('loginModal')).show();
                return;
            }

            const selected = selectedNumbersByGame[gameId] || [];
            if (selected.length === 0) {
                alert("Please select numbers for this game.");
                return;
            }

            console.log({
                game_id: gameId,
                numbers: selected,
                _token: '{{ csrf_token() }}'
            });
            // Send AJAX request to Laravel backend
            $.ajax({
                url: '{{ route('ticket.buy') }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    game_id: gameId,
                    numbers: selected
                },
                success: function(res) {
                    console.log(res);
                    if (res.success) {
                        // Show SweetAlert then redirect
                        Swal.fire({
                            icon: 'success',
                            title: 'Ticket Purchased!',
                            text: res.message,
                            confirmButtonText: 'View My Tickets',
                        }).then(() => {
                            window.location.href =
                                "{{ route('tickets.history') }}"; // ✅ Your ticket history route
                        });

                        // Clear selected numbers
                        selectedNumbersByGame[gameId] = [];
                        document.querySelectorAll(`[data-game="${gameId}"].selected`).forEach(el =>
                            el.classList.remove('selected')
                        );
                    } else {
                        if (res.message.includes('only open between')) {
                            // Show Game Closed Modal
                            document.getElementById('gameClosedMessage').textContent = res.message;
                            new bootstrap.Modal(document.getElementById('gameClosedModal')).show();
                        } else {
                            // Show Insufficient Balance Modal
                            new bootstrap.Modal(document.getElementById('insufficientModal')).show();
                        }
                    }
                },

                error: function() {
                    alert("Something went wrong while purchasing the ticket.");
                }
            });
        }



        function updateCountdowns() {
            const countdowns = document.querySelectorAll('.countdown-timer');

            countdowns.forEach(timer => {
                const closeTimeStr = timer.dataset.closeTime; // e.g., "2025-07-10 23:00:00"
                const closeTime = new Date(closeTimeStr).getTime();
                const now = new Date().getTime();
                const distance = closeTime - now;

                if (distance <= 0) {
                    timer.innerHTML = `<div class="text-danger fw-bold">Game Closed</div>`;
                    return;
                }

                const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const mins = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                const secs = Math.floor((distance % (1000 * 60)) / 1000);

                timer.querySelector('.hours').textContent = String(hours).padStart(2, '0');
                timer.querySelector('.mins').textContent = String(mins).padStart(2, '0');
                timer.querySelector('.secs').textContent = String(secs).padStart(2, '0');
            });
        }

        setInterval(updateCountdowns, 1000);
        updateCountdowns(); // Initial run

        // ✅ Automatically set the selected game ID before submitting login form
        document.addEventListener('DOMContentLoaded', function() {
            const loginForm = document.querySelector('#loginModal form');
            if (loginForm) {
                loginForm.addEventListener('submit', function() {
                    const gameId = localStorage.getItem('redirectGameId');
                    document.getElementById('redirectGameIdInput').value = gameId ?? '';
                });
            }
        });
    </script>

    @if (session('redirect_game_id'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const gameId = "{{ session('redirect_game_id') }}";
                const countdown = document.getElementById('countdown-' + gameId);
                if (countdown) {
                    countdown.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                    countdown.classList.add('highlight-flash'); // Optional: highlight
                }

                // Remove redirectGameId from localStorage after use
                localStorage.removeItem('redirectGameId');
            });
        </script>
@endif

@if (session('winner_alert'))
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            Swal.fire({
                icon: 'success',
                title: 'Winner!',
                text: '{{ session('winner_alert') }}',
                confirmButtonText: 'Awesome!'
            });
        });
    </script>
@endif



        <style>
            /* Optional: add visual highlight to selected game after login */
            .highlight-flash {
                animation: flashHighlight 2s ease-in-out;
                border: 2px solid #007bff;
                border-radius: 12px;
            }

            @keyframes flashHighlight {
                0% {
                    background-color: #e3f2fd;
                }

                50% {
                    background-color: #bbdefb;
                }

                100% {
                    background-color: #e3f2fd;
                }
            }

            .winner-slider h5 {
                font-size: 1.1rem;
            }

            .winner-slider p {
                font-size: 0.9rem;
            }

            .winner-slider .border-bottom {
                margin-bottom: 1rem;
            }
        </style>


    {{-- other HTML and scripts above this --}}

    {{-- Floating WhatsApp and Messenger Buttons --}}
    <style>
        .floating-buttons {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .floating-buttons a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            color: #fff;
            text-decoration: none;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .floating-buttons .whatsapp {
            background-color: #25D366;
        }

        .floating-buttons .messenger {
            background-color: #0084FF;
        }

        .floating-buttons a i {
            font-size: 24px;
        }
    </style>

    <div class="floating-buttons">
        <a href="https://wa.me/923001234567" target="_blank" class="whatsapp" title="Chat on WhatsApp">
            <i class="lab la-whatsapp"></i>
        </a>
        <a href="https://m.me/yourPageUsername" target="_blank" class="messenger" title="Chat on Messenger">
            <i class="lab la-facebook-messenger"></i>
        </a>
    </div>

@endsection
