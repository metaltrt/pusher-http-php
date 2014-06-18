<?php namespace PusherREST;

use PusherREST\Config;
use PusherREST\Client;

/**
 * Replaces the global config with a new set of parameters.
 *
 * @param $config array see PusherREST\Config's __constructor
 * @throws ConfigurationError
 * @return PusherREST\Config
 **/
function configure($config = array())
{
    $config = new Config($config);
    $config->validate();
    config($config);
    return $config;
}

function config($new_config = null)
{
    static $config;
    if (!is_null($new_config)) {
        $config = $new_config;
    }
    return $config;
}

// Fetch the config from the env vars.
config(new Config());

/**
 * Validates and decodes an incoming HTTP webhook request from Pusher and
 * returns the parsed JSON data.
 *
 * If the request is invalid the request is short-circuited and returns
 * a 401 Unauthorized response.
 *
 * @return mixed
 **/
function webhook_events()
{
    $api_key = $_REQUEST['HTTP_X_PUSHER_KEY'];
    $signature = $_REQUEST['HTTP_X_PUSHER_SIGNATURE'];
    if (empty($api_key) || empty($signature)) {
        not_authorized();
    }

    $body = file_get_contents('php://input');

    if (!validate_webhook($api_key, $signature, $body)) {
        not_authorized();
    }

    return json_decode( $body, true );
}

/**
 * Validates that the signature is correct.
 * Used by webhook_events() or to build your own webhook_events that
 * integrates with various PHP frameworks.
 *
 * @param $api_key string
 * @param $signature string
 * @param $body string
 * @return boolean
 **/
function validate_request($api_key, $signature, $body)
{
    $key_pair = $config->key($api_key);
    if (is_null($key_pair)) {
        return false;
    }

    return $key_pair->verify($signature, $body);
}

/**
 * Makes a call to the Pusher API to send an event on a given channel.
 *
 * @param $channels string|array
 * @param $event_name string
 * @param $body string
 * @return ???
 **/
function trigger($channel_name, $event_name, $data)
{
    $client = new Client(PusherREST::config);
    return $client->trigger($channel_name, $event_name, $data);
}

/**
 * Used by the webhook_events function to abord the response with a 401
 * Unauthorized.
 *
 * @return void
 **/
function not_authorized()
{
    header("Status: 401 Unauthorized");
    exit;
}