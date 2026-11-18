// assets/js/script.js - Interaktivitas CineTix

document.addEventListener('DOMContentLoaded', function () {
    // 1. Interactive Seat Selection
    const seatButtons = document.querySelectorAll('.seat-btn.available');
    const selectedInput = document.getElementById('selected-seats-input');
    const selectedDisplay = document.getElementById('selected-seats-display');
    const totalPriceDisplay = document.getElementById('total-price-display');
    const submitBtn = document.getElementById('btn-submit-booking');
    const ticketPrice = parseFloat(document.getElementById('ticket-price')?.value || 0);

    let selectedSeats = [];

    if (seatButtons.length > 0 && selectedInput) {
        seatButtons.forEach(btn => {
            btn.addEventListener('click', function () {
                const seatNo = this.getAttribute('data-seat');

                if (selectedSeats.includes(seatNo)) {
                    // Deselect
                    selectedSeats = selectedSeats.filter(s => s !== seatNo);
                    this.classList.remove('selected');
                } else {
                    // Select
                    selectedSeats.push(seatNo);
                    this.classList.add('selected');
                }

                // Update UI & Form
                selectedInput.value = selectedSeats.join(' ');
                
                if (selectedDisplay) {
                    selectedDisplay.textContent = selectedSeats.length > 0 ? selectedSeats.join(', ') : 'Belum ada kursi dipilih';
                }

                if (totalPriceDisplay) {
                    const total = selectedSeats.length * ticketPrice;
                    totalPriceDisplay.textContent = 'Rp ' + total.toLocaleString('id-ID');
                }

                if (submitBtn) {
                    submitBtn.disabled = selectedSeats.length === 0;
                }
            });
        });
    }

    // 2. Countdown Timer untuk Pembayaran
    const timerElem = document.getElementById('countdown-timer');
    if (timerElem) {
        let secondsLeft = parseInt(timerElem.getAttribute('data-seconds-left'), 10);

        function updateTimer() {
            if (secondsLeft <= 0) {
                timerElem.innerHTML = '<span class="text-danger fw-bold">WAKTU HABIS</span>';
                clearInterval(timerInterval);
                setTimeout(() => {
                    alert('Batas waktu pembayaran (15 menit) telah habis. Pesanan dibatalkan otomatis.');
                    window.location.reload();
                }, 1000);
                return;
            }

            const minutes = Math.floor(secondsLeft / 60);
            const seconds = secondsLeft % 60;
            timerElem.textContent = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
            secondsLeft--;
        }

        updateTimer();
        const timerInterval = setInterval(updateTimer, 1000);
    }
});
