<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'exchange_rates' => [
        'api_key' => env('EXCHANGE_RATE_API_KEY'),
    ],

    'tax_api' => [
        'enabled' => env('TAX_API_ENABLED', false),
        'url' => env('TAX_API_URL'),
        'api_key' => env('TAX_API_KEY'),
        'version' => env('TAX_API_VERSION', 'v1'),
        'cache_duration' => env('TAX_API_CACHE_DURATION', 3600),
    ],

    'sms' => [
        'api_key' => env('SMS_API_KEY'),
        'from_number' => env('SMS_FROM_NUMBER'),
        'base_url' => env('SMS_API_BASE_URL'),
        'enabled' => env('SMS_ENABLED', true),
    ],

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('APP_URL').'/oauth/google/callback',
    ],

    'github' => [
        'client_id' => env('GITHUB_CLIENT_ID'),
        'client_secret' => env('GITHUB_CLIENT_SECRET'),
        'redirect' => env('APP_URL').'/oauth/github/callback',
    ],

    'gitlab' => [
        'client_id' => env('GITLAB_CLIENT_ID'),
        'client_secret' => env('GITLAB_CLIENT_SECRET'),
        'redirect' => env('APP_URL').'/oauth/gitlab/callback',
    ],

    'facebook' => [
        'client_id' => env('FACEBOOK_CLIENT_ID'),
        'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
        'redirect' => env('APP_URL').'/oauth/facebook/callback',
    ],

    'bitbucket' => [
        'client_id' => env('BITBUCKET_CLIENT_ID'),
        'client_secret' => env('BITBUCKET_CLIENT_SECRET'),
        'redirect' => env('APP_URL').'/oauth/bitbucket/callback',
    ],

    'linkedin' => [
        'client_id' => env('LINKEDIN_CLIENT_ID'),
        'client_secret' => env('LINKEDIN_CLIENT_SECRET'),
        'redirect' => env('APP_URL').'/oauth/linkedin/callback',
    ],

    'linkedin-openid' => [
        'client_id' => env('LINKEDIN_CLIENT_ID'),
        'client_secret' => env('LINKEDIN_CLIENT_SECRET'),
        'redirect' => env('APP_URL').'/oauth/linkedin-openid/callback',
    ],

    'slack' => [
        'client_id' => env('SLACK_CLIENT_ID'),
        'client_secret' => env('SLACK_CLIENT_SECRET'),
        'redirect' => env('APP_URL').'/oauth/slack/callback',
    ],

    'twitter-oauth-2' => [
        'client_id' => env('TWITTER_CLIENT_ID'),
        'client_secret' => env('TWITTER_CLIENT_SECRET'),
        'redirect' => env('APP_URL').'/oauth/twitter-oauth-2/callback',
    ],

    'trello' => [
        'key' => env('TRELLO_KEY'),
        'secret' => env('TRELLO_SECRET'),
        'redirect' => env('APP_URL').'/oauth/trello/callback',
        'scopes' => 'read,write',
    ],

    'enom' => [
        'api_url' => env('ENOM_API_URL', 'https://reseller.enom.com/interface.asp'),
        'username' => env('ENOM_USERNAME'),
        'password' => env('ENOM_PASSWORD'),
    ],

    'resellerclub' => [
        'api_url' => env('RESELLERCLUB_API_URL', 'https://httpapi.com/api'),
        'auth_userid' => env('RESELLERCLUB_AUTH_USERID'),
        'api_key' => env('RESELLERCLUB_API_KEY'),
    ],
];
