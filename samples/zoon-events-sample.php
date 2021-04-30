<?php

// Set your Zoon domain and app token key
const ZOON_URL = 'https://demo-staging.zoon.ch/';
const APP_TOKEN_KEY = '8GySoDVTj8CytzmLngceh5zUSHVNNdQLjN3wg862';

const GRAPHQL_ENDPOINT = ZOON_URL . 'api/graphql';

// GET THE AUTHENTICATION TOKEN
$authQuery = <<<'QUERY'
mutation Auth(
  $key: String!
) {
    authentication {
        createTokenByAppToken(
            appToken: { key: $key }
        ) {
            token
        }
    }
}
QUERY;

$result = sendRequest($authQuery, ['key' => APP_TOKEN_KEY]);
if (!isset($result['data']['authentication']['createTokenByAppToken']['token'])) {
    throw new Exception('Authentication error');
}
$token = $result['data']['authentication']['createTokenByAppToken']['token'];


// REQUEST THE EVENTS
$eventsQuery = <<<'QUERY'
query Events(
  $token: String!
  $offset: Int!
  $length: Int!
) {
  viewer(token: $token) {
    events(limit: {offset: $offset, length: $length}) {
      items {
        id
        title
        description
        startDate
        endDate
      }
    }
  }
}
QUERY;

$result = sendRequest($eventsQuery, ['token' => $token, 'offset' => 0, 'length' => 1]);
if (!isset($result['data']['viewer'])) {
    throw new Exception('Invalid token');
}

printEvents($result['data']['viewer']['events']['items']);


/**
 * Send the request and return the decoded result
 *
 * @param string $query
 * @param array $variables
 * @return array
 */
function sendRequest($query, array $variables = [])
{
    $payload = [
        'query' => $query,
        'variables' => json_encode($variables),
    ];

    $headers = [
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
    ];
    $options = [
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS => $payload,
    ];

    $curlHandle = curl_init(GRAPHQL_ENDPOINT);
    curl_setopt_array($curlHandle, $options);

    $result = curl_exec($curlHandle);

    return json_decode($result, true);
}

/**
 * Sample print of the events
 *
 * @param array $events
 */
function printEvents(array $events)
{
    $output = <<<OUTPUT
<h1>%s</h1>
<small>%s - %s</small>
<p>%s</p>
OUTPUT;

    foreach ($events as $event) {
        $startDate = (new DateTime())->setTimestamp($event['startDate'])->format('d.m.Y H:i');
        $endDate = (new DateTime())->setTimestamp($event['endDate'])->format('d.m.Y H:i');
        printf($output, $event['title'], $startDate, $endDate, $event['description']);
    }
}
