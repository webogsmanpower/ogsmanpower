<?php

declare(strict_types=1);

return [
    /*
     * ------------------------------------------------------------------------
     * Default Firebase project
     * ------------------------------------------------------------------------
     */

    'default' => env('FIREBASE_PROJECT', 'app'),

    /*
     * ------------------------------------------------------------------------
     * Firebase project configurations
     * ------------------------------------------------------------------------
     */

    'projects' => [
        'app' => [

            /*
             * ------------------------------------------------------------------------
             * Credentials / Service Account
             * ------------------------------------------------------------------------
             *
             * In order to access a Firebase project and its related services using a
             * server SDK, requests must be authenticated. For server-to-server
             * communication this is done with a Service Account.
             *
             * If you don't already have generated a Service Account, you can do so by
             * following the instructions from the official documentation pages at
             *
             * https://firebase.google.com/docs/admin/setup#initialize_the_sdk
             *
             * Once you have downloaded the Service Account JSON file, you can use it
             * to configure the package.
             *
             * If you don't provide credentials, the Firebase Admin SDK will try to
             * auto-discover them
             *
             * - by checking the environment variable FIREBASE_CREDENTIALS
             * - by checking the environment variable GOOGLE_APPLICATION_CREDENTIALS
             * - by trying to find Google's well known file
             * - by checking if the application is running on GCE/GCP
             *
             * If no credentials file can be found, an exception will be thrown the
             * first time you try to access a component of the Firebase Admin SDK.
             *
             */

            // 'credentials' => env('FIREBASE_CREDENTIALS', env('GOOGLE_APPLICATION_CREDENTIALS')),
            'credentials' => [
                'type' => 'service_account',
                'project_id' => 'jobpilot-mobile-app',
                'private_key_id' => '35556a77bea5d91709378b0567c5bf04fadeb75d',
                'private_key' => '-----BEGIN PRIVATE KEY-----\nMIIEvAIBADANBgkqhkiG9w0BAQEFAASCBKYwggSiAgEAAoIBAQCf4SOiyOJC7CQt\nepny6AHEbUEkahIoKsiJcTNJIYsp3n9bVNPt+bKNGDgoO2Lqq08+8FaC8rY4Mgv3\n06nNwN4gAKLxL2qy3iEu2hHDXv56YtGPWCvNNo2GBi2WWbRPlDjES/5kwOsIG5Cj\n8fyQ7zRN47ywwD7WTP6x3XTGLb8FPdlLtJYboXHUvmc6xpULXK55gpmMQa2cXvwb\nElUfW4nUrCSVWjBv3DW8s77D7B+UJk09R15Ix0XJ0bBfo5m5l+NtbjkTbvWjVzl8\n83tBFxcJdYheeUUsxEo7LJeEL+7xe9BMHOuchntoTPHnje2G1YpSjhvpDGsuU5y7\npug1sNgNAgMBAAECggEALXHc48YPDm+5/tVlEhchfXiCtjuSSqmSlALBza1DtdSB\nRfAwR2oc9x8lw0XSIZstLNo69QDRi9qp7TlUgGAso1Ma/cx5GzupmfwxZWPImPgi\n0giBdtTlWG3EIoMADki29BEX0ALIK+dN71P28ymTJ/quZV1X3ylGkj89FKHnNLFD\nWFOL273pvdH3bDsYM6c73IxJwAJaKiZIzDTrdG6Rl3qsHphd/CnTQN4bNvm97U1N\n14ZQrRFssA5WIEuBhOM2C4bwUURGEmMil1gSJDg0StgOwgO9pS2iL0CaMTUe7f5U\njgL5xY2xXlFdwnb4IRyGY6c5mV+7FbAdvGCYJc2L3wKBgQDPnCuFEXN4Mec+Uxmx\nT81tETxfM5OFaDe6N0Z1CdnY+VkE6Vr7rwjZixWtJnS+/s3lt58CTxuufxDYcyFc\nQvEtzya3+yqYuTNLY+FF7J05Lr8hIPiM4GwftYgwMjyhcA0CLkcpwnRh7RGsqGIZ\nAeG2Hc7sHIfGLx813udCc2xmYwKBgQDFJPHpWsupkBmRBz+Mmj+6ZfTo/okle5K+\ngYIDwSDHe6BAYcZDbf76xz1VxPLjZ0VT9lDIfl2H3c9qLOWZnxI+mV3bwB9EgYRI\n+8UnrEV5PuWEhHRrFLRb2FOAwMWEo3LS+gWcdxy+fGyD6FDycTkf+OPqqNvnAK+l\nAWUgRgwazwKBgBGPLOMhWbnVxrAvySGFFWPfLFMFAroq2WPHnw7xGVrPpCbVMdvN\nrxrvN5MgiIlK9dEzGaDRXg8BMkSMYEr0Yn+0YJr5BF5Mc7kxpnEKlTWr7YB40Gzh\n4mNAMFebOCOTXZZMobo3dy59JZyL1Vg2H4gOt1yjARzTCXm986NcLdPvAoGAEz/D\nOxS5dAn05Rs/EjxxYoLYAfPwci95qY49op411CR3BjJFyImx7syLaZd8W8XWmBch\n1iG4gi6fNO/DT1ef0WrqJi3BH9BBmVqz6mbAtdD1WhYQw+/WmG0r4hNwdb371SvC\nZn+e196QPeTuGm4BtOR5ZI0uQ4TmoYbcV+VfAzMCgYAYnfsVRZxPo97Put7JCRl6\nI4TOU0XNDG4Ng/Cw3pUyuBBbJxfFi9v8ptp4641VskbnSg4tQAgRLt2TDmRgk8uy\n16snNodwCpfYO98lbalBehruic2/GDlfEEoy9z1tJ+PFPhELA9kXC2mGlMuqAQB8\nixldGWoVJCj4wvqCIhyTUQ==\n-----END PRIVATE KEY-----\n',
                'client_email' => 'firebase-adminsdk-kzrtq@jobpilot-mobile-app.iam.gserviceaccount.com',
                'client_id' => '113215739113590005497',
                'auth_uri' => 'https://accounts.google.com/o/oauth2/auth',
                'token_uri' => 'https://oauth2.googleapis.com/token',
                'auth_provider_x509_cert_url' => 'https://www.googleapis.com/oauth2/v1/certs',
                'client_x509_cert_url' => 'https://www.googleapis.com/robot/v1/metadata/x509/firebase-adminsdk-kzrtq%40jobpilot-mobile-app.iam.gserviceaccount.com',
                'universe_domain' => 'googleapis.com',
            ],

            /*
             * ------------------------------------------------------------------------
             * Firebase Auth Component
             * ------------------------------------------------------------------------
             */

            'auth' => [
                'tenant_id' => env('FIREBASE_AUTH_TENANT_ID'),
            ],

            /*
             * ------------------------------------------------------------------------
             * Firestore Component
             * ------------------------------------------------------------------------
             */

            'firestore' => [

                /*
                 * If you want to access a Firestore database other than the default database,
                 * enter its name here.
                 *
                 * By default, the Firestore client will connect to the `(default)` database.
                 *
                 * https://firebase.google.com/docs/firestore/manage-databases
                 */

                // 'database' => env('FIREBASE_FIRESTORE_DATABASE'),
            ],

            /*
             * ------------------------------------------------------------------------
             * Firebase Realtime Database
             * ------------------------------------------------------------------------
             */

            'database' => [

                /*
                 * In most of the cases the project ID defined in the credentials file
                 * determines the URL of your project's Realtime Database. If the
                 * connection to the Realtime Database fails, you can override
                 * its URL with the value you see at
                 *
                 * https://console.firebase.google.com/u/1/project/_/database
                 *
                 * Please make sure that you use a full URL like, for example,
                 * https://my-project-id.firebaseio.com
                 */

                'url' => 'https://jobpilot-mobile-app-default-rtdb.asia-southeast1.firebasedatabase.app',

                /*
                 * As a best practice, a service should have access to only the resources it needs.
                 * To get more fine-grained control over the resources a Firebase app instance can access,
                 * use a unique identifier in your Security Rules to represent your service.
                 *
                 * https://firebase.google.com/docs/database/admin/start#authenticate-with-limited-privileges
                 */

                // 'auth_variable_override' => [
                //     'uid' => 'my-service-worker'
                // ],

            ],

            'dynamic_links' => [

                /*
                 * Dynamic links can be built with any URL prefix registered on
                 *
                 * https://console.firebase.google.com/u/1/project/_/durablelinks/links/
                 *
                 * You can define one of those domains as the default for new Dynamic
                 * Links created within your project.
                 *
                 * The value must be a valid domain, for example,
                 * https://example.page.link
                 */

                'default_domain' => env('FIREBASE_DYNAMIC_LINKS_DEFAULT_DOMAIN'),
            ],

            /*
             * ------------------------------------------------------------------------
             * Firebase Cloud Storage
             * ------------------------------------------------------------------------
             */

            'storage' => [

                /*
                 * Your project's default storage bucket usually uses the project ID
                 * as its name. If you have multiple storage buckets and want to
                 * use another one as the default for your application, you can
                 * override it here.
                 */

                'default_bucket' => env('FIREBASE_STORAGE_DEFAULT_BUCKET'),

            ],

            /*
             * ------------------------------------------------------------------------
             * Caching
             * ------------------------------------------------------------------------
             *
             * The Firebase Admin SDK can cache some data returned from the Firebase
             * API, for example Google's public keys used to verify ID tokens.
             *
             */

            'cache_store' => env('FIREBASE_CACHE_STORE', 'file'),

            /*
             * ------------------------------------------------------------------------
             * Logging
             * ------------------------------------------------------------------------
             *
             * Enable logging of HTTP interaction for insights and/or debugging.
             *
             * Log channels are defined in config/logging.php
             *
             * Successful HTTP messages are logged with the log level 'info'.
             * Failed HTTP messages are logged with the log level 'notice'.
             *
             * Note: Using the same channel for simple and debug logs will result in
             * two entries per request and response.
             */

            'logging' => [
                'http_log_channel' => env('FIREBASE_HTTP_LOG_CHANNEL'),
                'http_debug_log_channel' => env('FIREBASE_HTTP_DEBUG_LOG_CHANNEL'),
            ],

            /*
             * ------------------------------------------------------------------------
             * HTTP Client Options
             * ------------------------------------------------------------------------
             *
             * Behavior of the HTTP Client performing the API requests
             */

            'http_client_options' => [

                /*
                 * Use a proxy that all API requests should be passed through.
                 * (default: none)
                 */

                'proxy' => env('FIREBASE_HTTP_CLIENT_PROXY'),

                /*
                 * Set the maximum amount of seconds (float) that can pass before
                 * a request is considered timed out
                 *
                 * The default time out can be reviewed at
                 * https://github.com/kreait/firebase-php/blob/6.x/src/Firebase/Http/HttpClientOptions.php
                 */

                'timeout' => env('FIREBASE_HTTP_CLIENT_TIMEOUT'),

                'guzzle_middlewares' => [],
            ],
        ],
    ],
];
