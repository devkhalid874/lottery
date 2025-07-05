@extends($activeTemplate . 'layouts.frontend')

@section('content')

    {{-- ✅ ACTIVE GAMES SECTION --}}
    <div class="container py-5" id="games">
        <div class="row g-4">

            @forelse ($games as $game)
                <div class="col-md-4">
                    <div class="card h-100 shadow border-0 rounded-4 overflow-hidden">
                        <div class="card-body p-4 d-flex flex-column">
                            <h5 class="text-center fw-bold mb-2">{{ $game->name }}</h5>
                            <p class="text-center text-muted mb-3">Price: <strong>PKR
                                    {{ $game->ticket_price }}</strong></p>

                            <div class="d-flex justify-content-center gap-3 mb-3 countdown-timer"
                                data-close-time="{{ $game->close_time }}" id="countdown-{{ $game->id }}">
                                <div class="text-center">
                                    <div class="countdown-circle"><span class="fw-bold text-primary days">00</span></div>
                                    <small class="text-muted d-block mt-1">Days</small>
                                </div>
                                <div class="text-center">
                                    <div class="countdown-circle"><span class="fw-bold text-primary hours">00</span></div>
                                    <small class="text-muted d-block mt-1">Hours</small>
                                </div>
                                <div class="text-center">
                                    <div class="countdown-circle"><span class="fw-bold text-primary mins">00</span></div>
                                    <small class="text-muted d-block mt-1">Mins</small>
                                </div>
                                <div class="text-center">
                                    <div class="countdown-circle"><span class="fw-bold text-primary secs">00</span></div>
                                    <small class="text-muted d-block mt-1">Secs</small>
                                </div>
                            </div>


                            <div class="bg-light p-2 rounded mb-3 number-container"
                                style="max-height: 160px; overflow-y: auto;">
                                <div class="d-flex flex-wrap justify-content-center">
                                    @for ($i = $game->range_start; $i <= $game->range_end; $i++)
                                        <div class="m-1 number-box" data-game="{{ $game->id }}"
                                            data-number="{{ sprintf('%02d', $i) }}" onclick="handleNumberClick(this)">
                                            {{ sprintf('%02d', $i) }}
                                        </div>
                                    @endfor
                                </div>
                                <input type="hidden" name="selected_numbers_game_{{ $game->id }}"
                                    id="selectedNumbersGame{{ $game->id }}">
                            </div>

                            <div class="mt-auto">
                                <button class="btn btn-primary w-100 fw-semibold"
                                    onclick="confirmPurchase({{ $game->id }})">Buy Now</button>
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
                <a href="{{ route('user.deposit.index')}}" class="btn btn-warning">Deposit Now</a>
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
                localStorage.setItem('redirectGameId', gameId);  // ✅ Save game ID
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

        // function confirmPurchase(gameId) {
        //     const isLoggedIn = {{ auth()->check() ? 'true' : 'false' }};

        //     if (!isLoggedIn) {
        //         new bootstrap.Modal(document.getElementById('loginModal')).show();
        //         return;
        //     }

        //     const selected = selectedNumbersByGame[gameId] || [];

        //     if (selected.length === 0) {
        //         alert("Please select numbers for this game.");
        //         return;
        //     }

        //     const balance = 5; // Simulated
        //     const pricePerTicket = 10;

        //     if (balance < pricePerTicket) {
        //         new bootstrap.Modal(document.getElementById('insufficientModal')).show();
        //     } else {
        //         alert("Ticket purchased successfully for Game ID " + gameId + ": " + selected.join(', '));
        //     }
        // }



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
        url: '{{ route("ticket.buy") }}',
        type: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            game_id: gameId,
            numbers: selected
        },
        success: function (res) {
              console.log(res);
            if (res.success) {
                alert("✅ " + res.message);
                // Clear selected numbers
                selectedNumbersByGame[gameId] = [];
                document.querySelectorAll(`[data-game="${gameId}"].selected`).forEach(el => el.classList.remove('selected'));
            } else {
                new bootstrap.Modal(document.getElementById('insufficientModal')).show();
            }
        },
        error: function () {
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
    document.addEventListener('DOMContentLoaded', function () {
        const loginForm = document.querySelector('#loginModal form');
        if (loginForm) {
            loginForm.addEventListener('submit', function () {
                const gameId = localStorage.getItem('redirectGameId');
                document.getElementById('redirectGameIdInput').value = gameId ?? '';
            });
        }
    });
    </script>

    @if (session('redirect_game_id'))
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const gameId = "{{ session('redirect_game_id') }}";
            const countdown = document.getElementById('countdown-' + gameId);
            if (countdown) {
                countdown.scrollIntoView({ behavior: 'smooth', block: 'center' });
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
            0% { background-color: #e3f2fd; }
            50% { background-color: #bbdefb; }
            100% { background-color: #e3f2fd; }
        }
    </style>
@endif

@endsection
