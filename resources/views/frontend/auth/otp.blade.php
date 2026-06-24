@extends('frontend.layouts.app')
@section('title')
    {{ __('verify_email_address') }}
@endsection
@section('main')
    <div class="otp-container">
        <h2>Verify your Identity</h2>
        <p>Choose an option to receive your OTP</p>

        <!-- Buttons for sending OTP via WhatsApp or Email -->
        <div class="otp-options">
            <button id="whatsappBtn">Send OTP via WhatsApp</button>
            <button id="emailBtn">Send OTP via Email</button>
        </div>

        <!-- OTP Input Block (Hidden initially) -->
        <div id="otpSection" style="display:none;">
            <p>Enter the 6-digit OTP:</p>

            <!-- 6 individual input boxes for the OTP -->
            <div class="otp-input-blocks">
                <input type="text" maxlength="1" class="otp-input" id="otp1">
                <input type="text" maxlength="1" class="otp-input" id="otp2">
                <input type="text" maxlength="1" class="otp-input" id="otp3">
                <input type="text" maxlength="1" class="otp-input" id="otp4">
                <input type="text" maxlength="1" class="otp-input" id="otp5">
                <input type="text" maxlength="1" class="otp-input" id="otp6">
            </div>
            <!-- Timer Display (Initially hidden) -->
            <div id="countdownTimer" style="display:none; color:red; font-weight:bold; margin-top:10px;"></div>
            <button type="button" class="btn btn-primary" id="submitBtn">Submit OTP</button>
        </div>

    </div>
    {{-- @dd( );auth('admin')->user()->id --}}
    </div>
@endsection
@section('css')
    <style>
        .mr--4 {
            margin-right: 12px !important;
        }

        .otp-container {
            text-align: center;
            max-width: 400px;
            margin: 50px auto;
            font-family: Arial, sans-serif;
        }

        .otp-options button {
            padding: 10px 20px;
            margin: 10px;
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
        }

        .otp-input {
            margin-top: 20px;
            padding: 10px;
            font-size: 18px;
            width: 100px;
            text-align: center;
            letter-spacing: 10px;
        }

        .resend-btn {
            margin-top: 20px;
            background-color: #f44336;
            color: white;
            padding: 8px 16px;
            border: none;
            cursor: pointer;
        }

        .otp-input-blocks {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }

        .otp-input {
            width: 40px;
            height: 40px;
            font-size: 20px;
            text-align: center;
            margin: 0 5px;
            border: 2px solid #ccc;
            border-radius: 5px;
            outline: none;
        }

        .otp-input:focus {
            border-color: #4CAF50;
        }

        .resend-btn {
            margin-top: 20px;
            background-color: #f44336;
            color: white;
            padding: 8px 16px;
            border: none;
            cursor: pointer;
        }

        .disabled-button {
            pointer-events: none;
            /* Prevent actual clicking */
            opacity: 0.6;
            /* Make it appear disabled */
            background-color: #4CAF50;
            /* Keep the button color the same */
            cursor: not-allowed;
            /* Show "not-allowed" cursor */
        }

        .disabled-button {
            pointer-events: none;
            /* Prevent actual clicking */
            opacity: 0.6;
            /* Make it appear disabled */
            background-color: #4CAF50;
            /* Keep the button color the same */
            cursor: not-allowed;
            /* Show "not-allowed" cursor */
        }
    </style>
@endsection
@section('script')
    <script>
        // Function to disable buttons and start the timer
        function disableButtonsAndStartTimer() {
            const whatsappBtn = document.getElementById('whatsappBtn');
            const emailBtn = document.getElementById('emailBtn');
            const countdownTimer = document.getElementById('countdownTimer');

            // Disable buttons and apply disabled class
            whatsappBtn.disabled = true;
            emailBtn.disabled = true;
            whatsappBtn.classList.add('disabled-button');
            emailBtn.classList.add('disabled-button');

            // Show the timer below the buttons
            countdownTimer.style.display = 'block';
            let timeLeft = 60;

            const countdownInterval = setInterval(() => {
                timeLeft--;
                countdownTimer.textContent = `Please wait ${timeLeft} seconds before you can resend OTP.`;

                if (timeLeft <= 0) {
                    clearInterval(countdownInterval);

                    // Re-enable buttons after timer ends
                    whatsappBtn.disabled = false;
                    emailBtn.disabled = false;
                    whatsappBtn.classList.remove('disabled-button');
                    emailBtn.classList.remove('disabled-button');

                    // Hide the timer
                    countdownTimer.style.display = 'none';
                }
            }, 1000); // Update the timer every second
        }

        // Function to show loader when verifying OTP
        function showLoader() {
            const loader = document.createElement('div');
            loader.className = 'loader';
            loader.textContent = 'Verifying...';
            document.querySelector('.otp-container').appendChild(loader);
        }

        // Function to hide loader after verification
        function hideLoader() {
            const loader = document.querySelector('.loader');
            if (loader) {
                loader.remove();
            }
        }

        // Add event listeners for buttons
        document.getElementById('whatsappBtn').addEventListener('click', function() {
            sendOTPRequest('whatsapp');
        });

        document.getElementById('emailBtn').addEventListener('click', function() {
            sendOTPRequest('email');
        });

        function sendOTPRequest(via) {
            document.getElementById('otpSection').style.display = 'block';
            disableButtonsAndStartTimer(); // Disable buttons and start timer

            // Send AJAX request to backend
            fetch('{{ route('send.otp') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        via: via,
                        user_id: {{ auth()->user()->id ?? auth('admin')->user()->id }} // Ensure this is set correctly
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('OTP sent successfully via ' + via);
                    } else {
                        console.error('Error:', data.error);
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        // Trigger OTP verification when all 6 inputs are filled
        let otpInputs = document.querySelectorAll('.otp-input');
        otpInputs.forEach((input, index) => {
            input.addEventListener('input', () => {
                if (input.value.length === 1 && index < 5) {
                    otpInputs[index + 1].focus();
                }

                // Check if all 6 inputs are filled
                const otp = Array.from(otpInputs).map(input => input.value).join('');
                if (otp.length === 6) {
                    verifyOTP(otp); // Call the function to verify OTP
                }
            });
        });

        // Handle backspace to move to the previous field
        otpInputs.forEach((input, index) => {
            input.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && input.value === '' && index > 0) {
                    otpInputs[index - 1].focus();
                }
            });
        });

        // function verifyOTP(otp) {
        //     showLoader(); // Show loader while verifying

        //     // Send AJAX request to backend for OTP verification
        //     fetch('{{ route('verify.otp') }}', {
        //             method: 'POST',
        //             headers: {
        //                 'Content-Type': 'application/json',
        //                 'X-CSRF-TOKEN': '{{ csrf_token() }}'
        //             },
        //             body: JSON.stringify({
        //                 otp: otp,
        //                 user_id: {{ auth()->user()->id ?? auth('admin')->user()->id }} // Pass the user's ID
        //             })
        //         })
        //         .then(response => response.json())
        //         .then(data => {
        //             hideLoader(); // Hide loader after verification
        //             if (data.success) {
        //                 // OTP verification was successful, redirect or show success message
        //                 // window.location.href = '{{ route('user.dashboard') }}'; // Redirect to dashboard
        //                 window.location.href =
        //                     '{{ auth('admin')->user() ? route('admin.dashboard') : route('user.dashboard') }}';

        //             } else {
        //                 // Show error message if OTP verification failed
        //                 alert('Invalid OTP. Please try again.');
        //             }
        //         })
        //         .catch(error => {
        //             hideLoader();
        //             console.error('Error:', error);
        //         });
        // }
        // Handle the OTP submission when the Submit button is clicked
        document.getElementById('submitBtn').addEventListener('click', function() {
            const otpInputs = document.querySelectorAll('.otp-input');
            const otp = Array.from(otpInputs).map(input => input.value).join('');

            if (otp.length !== 6) {
                alert('Please enter all 6 digits of the OTP.');
                return;
            }

            // Handle the OTP submission logic
            fetch('/verify-otp', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        otp: otp,
                        user_id: {{ auth()->user()->id ?? auth('admin')->user()->id }}
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('OTP verified successfully!');
                        // Redirect or perform the desired action
                        window.location.href =
                            '{{ auth('admin')->user() ? route('admin.dashboard') : route('user.dashboard') }}';

                    } else {
                        alert('Invalid OTP. Please try again.');
                    }
                })
                .catch(error => console.error('Error:', error));
        });
    </script>
@endsection
