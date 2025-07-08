@extends($activeTemplate . 'layouts.frontend')

@section('content')

{{-- ✅ DYNAMIC ANNOUNCEMENT SECTION --}}
@if ($announcement)
    <section class="py-5 bg-light border-top border-bottom">
        <div class="container">
            @if ($announcement->title || $announcement->description)
                {{-- ➕ Layout with image/video on left and text on right --}}
                <div class="row align-items-center g-4">
                    {{-- Media (Image or Video) --}}
                    <div class="col-lg-6">
                        @if ($announcement->media_type === 'video')
                            <div class="ratio ratio-16x9 rounded-3 shadow-sm overflow-hidden">
                                <video controls class="w-100 rounded shadow-sm">
                                    <source src="{{ announcementAsset($announcement->media_path) }}" type="video/mp4">
                                    Your browser does not support the video tag.
                                </video>
                            </div>
                        @else
                            <img src="{{ announcementAsset($announcement->media_path) }}" alt="Announcement"
                                 class="img-fluid rounded shadow-sm w-100">
                        @endif
                    </div>

                    {{-- Text Content --}}
                    <div class="col-lg-6">
                        @if ($announcement->title)
                            <h3 class="fw-bold text-primary mb-3">📢 {{ $announcement->title }}</h3>
                        @endif
                        @if ($announcement->description)
                            <p class="mb-4 text-muted fs-6">
                                {{ $announcement->description }}
                            </p>
                        @endif
                        <a href="#games" class="btn btn-outline-primary px-4 fw-semibold">
                            View Games <i class="las la-arrow-right ms-2"></i>
                        </a>
                    </div>
                </div>
            @else
                {{-- ➖ Full-width layout for media only --}}
                <div class="row">
                    <div class="col-12">
                        @if ($announcement->media_type === 'video')
                            <div class="ratio ratio-21x9 rounded-3 overflow-hidden shadow-sm">
                                <video controls class="w-100 rounded shadow-sm">
                                    <source src="{{ announcementAsset($announcement->media_path) }}" type="video/mp4">
                                    Your browser does not support the video tag.
                                </video>
                            </div>
                        @else
                            <img src="{{ announcementAsset($announcement->media_path) }}" alt="Full Banner"
                                 class="img-fluid rounded shadow-sm w-100">
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </section>
@endif






    {{-- ✅ ACTIVE GAMES SECTION --}}
    <div class="container py-5" id="games">
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
                                data-close-time="{{ $game->close_time }}" id="countdown-{{ $game->id }}">
                                @foreach (['Days' => 'days', 'Hours' => 'hours', 'Mins' => 'mins', 'Secs' => 'secs'] as $label => $class)
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


    {{-- LOGIN MODAL --}}
    <div class="modal fade" id="loginModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content p-4">
                <h5 class="fw-bold mb-3 text-center">🔐 Login to Continue</h5>

                <form method="POST" action="{{ route('user.login') }}">
                    @csrf
                    <input type="hidden" name="redirect_game_id" id="redirectGameIdInput">
                    <div class="mb-3">
                        <label class="form-label">Email or Username</label>
                        <input type="text" class="form-control" name="username" required />
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" class="form-control" name="password" required id="login-password" />
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary w-100">Login</button>
                    </div>

                    <div class="text-center mt-3">
                        <small>Don't have an account? <a href="#" data-bs-toggle="modal"
                                data-bs-target="#registerModal" data-bs-dismiss="modal">Register</a></small>
                    </div>
                </form>
            </div>
        </div>
    </div>



    {{-- REGISTER MODAL --}}
    <div class="modal fade" id="registerModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content p-4">
                <h5 class="fw-bold mb-3 text-center">📝 Register to Play</h5>

                <form method="POST" action="{{ route('user.register') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">First Name</label>
                        <input type="text" name="firstname" class="form-control" required />
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Last Name</label>
                        <input type="text" name="lastname" class="form-control" required />
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control" required />
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required id="register-password" />
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Confirm Password</label>
                        <input type="password" name="password_confirmation" class="form-control" required />
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-success w-100">Register</button>
                    </div>

                    <div class="text-center mt-3">
                        <small>Already have an account?
                            <a href="#" data-bs-toggle="modal" data-bs-target="#loginModal"
                                data-bs-dismiss="modal">Login</a>
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
                <a href="{{ route('user.deposit.index') }}" class="btn btn-warning">Deposit Now</a>
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
                    alert("You can only select up to 6 numbers per game.");
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
                        new bootstrap.Modal(document.getElementById('insufficientModal')).show();
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
                const closeTime = new Date(timer.dataset.closeTime).getTime();
                const now = new Date().getTime();
                const distance = closeTime - now;

                let days = 0,
                    hours = 0,
                    mins = 0,
                    secs = 0;

                if (distance > 0) {
                    days = Math.floor(distance / (1000 * 60 * 60 * 24));
                    hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                    mins = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                    secs = Math.floor((distance % (1000 * 60)) / 1000);
                }

                timer.querySelector('.days').textContent = String(days).padStart(2, '0');
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
        </style>
    @endif

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
