@extends('frontend.layouts.app')

@section('title')
    {{ __('plan') }}
@endsection
@php
      $current_currency = currentCurrency();
      $code = $current_currency->code;
@endphp
@section('main')
    <!-- breedcrumb section end  -->
    <section class="section benefits bgcolor--gray-10 mt-5 pt-5">
        <div class="container">
            <div class="row mt-5 pt-5">
                <h4 class="text-info">{{ __('total_amount_to_pay') }}: {{ currencyPosition(2, true) }}</h4>
            </div>
            <div class="row py-5">
                <h5>{{ __('Pay to Download/View') }}</h5>
                @if (config('paypal.active') ||
                    config('templatecookie.stripe_active') ||
                    config('templatecookie.razorpay_active') ||
                    config('templatecookie.paystack_active') ||
                    config('templatecookie.ssl_active') ||
                    config('templatecookie.flw_active') ||
                    config('templatecookie.im_active') ||
                    config('templatecookie.midtrans_active') ||
                    config('templatecookie.mollie_active'))



                    {{-- Stripe payment --}}
                    @if (config('templatecookie.stripe_active') && config('templatecookie.stripe_key') && config('templatecookie.stripe_secret'))
                        <div class="col-4 my-2">
                            <div class="card jobcardStyle1">
                                <div class="card-body">
                                    <div class="rt-single-icon-box">
                                        <div class="iconbox-content">
                                            <div class="body-font-1 rt-mb-12">
                                                {{ __('stripe') }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="post-info d-flex">
                                        <div class="flex-grow-1">
                                            <button id="stripe_btn" type="button" class="btn btn-primary2-50 d-block">
                                                {{ __('pay_now') }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                @else
                    <div class="text-center">
                        <x-svg.not-found-icon />
                        <h5 class="mt-4">{{ __('no_payment_method_available_here') }}</h5>
                    </div>
                @endif
            </div>

            {{-- @if ($manual_payments && count($manual_payments))
                <div class="row mb-5">
                    <h5>{{ __('manual_payment_gateways') }}</h5>
                    @foreach ($manual_payments as $payment)
                        <div class="col-6 my-2">
                            <form action="{{ route('candidate.manual.payment') }}" method="post">
                                @csrf
                                <input type="hidden" name="candidate_id" value="{{auth()->user()->candidate->id}}">
                                <input type="hidden" name="language_code" value="{{$language_code}}">
                                <input type="hidden" name="language" value="{{$language}}">
                                <div class="card jobcardStyle1">
                                    <div class="card-body">
                                        <div class="rt-single-icon-box">
                                            <div class="iconbox-content">
                                                <div class="body-font-1 rt-mb-12">
                                                    Bilangual Payment
                                                </div>
                                            </div>
                                        </div>
                                        <p>Send Amount on This Easypesa number and send screen shot on gmail.....</p>
                                        <div class="post-info d-flex">
                                            <div class="flex-grow-1">
                                                <button type="submit" class="btn btn-primary2-50 d-block">
                                                    {{ __('pay_now') }}
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    @endforeach

                </div>
            @endif --}}
        </div>


        {{-- Stripe Form --}}
        <form action="{{ route('bilangualStripe') }}" method="POST" class="d-none">
            @csrf
            <input type="hidden" name="candidate_id" value="{{auth()->user()->candidate->id}}">
            <input type="hidden" name="language_code" value="{{$language_code}}">
            <input type="hidden" name="language" value="{{$language}}">
            <input type="hidden" name="amount" value="{{2}}">

            <script id="stripe_script" src="https://checkout.stripe.com/checkout.js" class="stripe-button"
                data-key="{{ config('templatecookie.stripe_key') }}" data-amount="{{ 2*100 }}"
                data-name="{{ config('app.name') }}" data-description="Money pay with stripe"
                data-locale="{{ app()->getLocale() == 'default' ? 'en' : app()->getLocale() }}" data-currency="{{ $code }}"></script>
        </form>


    </section>
@endsection

@section('script')
    @if (config('templatecookie.midtrans_active') &&
        config('templatecookie.midtrans_merchat_id') &&
        config('templatecookie.midtrans_client_key') &&
        config('templatecookie.midtrans_server_key'))

        @if (config('templatecookie.midtrans_live_mode'))
            <script src="https://app.midtrans.com/snap/snap.js" data-client-key="{{ config('templatecookie.midtrans_client_key') }}">
            </script>
        @else
            <script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="{{ config('templatecookie.midtrans_client_key') }}">
            </script>
        @endif
    @endif
    <script>


        // Stripe
        $('#stripe_btn').on('click', function(e) {
            e.preventDefault();
            $('.stripe-button-el').click();
        });

        //iyzipay
        $('#iyzipay_btn').on('click', function(e) {
    e.preventDefault();
    $('#iyzipay-form').submit();
});


        // Razorpay
        $('#razorpay_btn').on('click', function(e) {
            e.preventDefault();
            $('.razorpay-payment-button').click();
        });

        // Paystack
        $('#paystack_btn').on('click', function(e) {
            e.preventDefault();
            $('#paystack-form').submit();
        });

        // Flutterwave
        $('#flutter_btn').on('click', function(e) {
            e.preventDefault();
            $('#flutter-form').submit();
        });

        // Mollie
        $('#mollie_btn').on('click', function(e) {
            e.preventDefault();
            $('#mollie-form').submit();
        });

        // Instamojo
        $('#instamojo_btn').on('click', function(e) {
            e.preventDefault();
            $('#instamojo-form').submit();
        });

        // ssl commerz
        $('#ssl_btn').on('click', function(e) {
            e.preventDefault();
            $('#sslc-form').submit();
        });

        // Midtrans
        if (
            '{{ config('templatecookie.midtrans_active') && config('templatecookie.midtrans_merchat_id') && config('templatecookie.midtrans_client_key') && config('templatecookie.midtrans_server_key') }}'
        ) {

            const payButton = document.querySelector('#midtrans_btn');
            payButton.addEventListener('click', function(e) {
                e.preventDefault();

                snap.pay('{{ $mid_token }}', {
                    onSuccess: function(result) {
                        successMidtransPayment();
                    },
                    onPending: function(result) {
                        alert('Transaction is in pending state');
                    },
                    onError: function(result) {
                        alert('Transaction is failed. Try again.');
                    }
                });
            });

            function successMidtransPayment() {
                $.ajax({
                    type: "post",
                    url: "{{ route('midtrans.success') }}",
                    data: {
                        '_token': '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        console.log(response)
                        window.location.href = response.redirect_url;
                    }
                });
            }
        }
    </script>
@endsection
