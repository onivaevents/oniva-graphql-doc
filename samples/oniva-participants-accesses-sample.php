<?php

// Set your Oniva domain and event settings
const ONIVA_URL = 'https://app-staging.zoon.ch/';
const EVENT_ID = '1e7a814e-5daf-4df5-aae1-6f592fa1819f';
const EVENT_TOKEN_KEY = 'qyo6Vouo';

const GRAPHQL_ENDPOINT = ONIVA_URL . 'api/graphql';

// GET THE AUTHENTICATION TOKEN
$authQuery = <<<'QUERY'
mutation Auth(
  $key: String!
  $eventId: ID!
) {
  authentication {
    createTokenByEventToken(eventToken: {eventId: $eventId, key: $key}) {
      token
    }
  }
}
QUERY;

$result = sendRequest($authQuery, ['eventId' => EVENT_ID, 'key' => EVENT_TOKEN_KEY]);
if (!isset($result['data']['authentication']['createTokenByEventToken']['token'])) {
    throw new Exception('Authentication error');
}
$token = $result['data']['authentication']['createTokenByEventToken']['token'];


// REQUEST THE PARTICIPANTS ACCESSES
$accessesQuery = <<<'QUERY'
query AccessQuery(
  $eventId: ID!
  $token: String
) {
  viewer(token: $token) {
    event(id: $eventId) {
      accesses {
        itemCount
        totalCount
        items {
          firstName
          lastName
          checkIn {
            attendance
            attendanceDate
          }
        }
      }
    }
  }
}
QUERY;

$result = sendRequest($accessesQuery, ['eventId' => EVENT_ID, 'token' => $token]);
if (!isset($result['data']['viewer'])) {
    throw new Exception('Invalid token');
}

printAccesses($result['data']['viewer']['event']['accesses']['items']);


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
 * Sample print of the participants accesses
 *
 * @param array $accesses
 */
function printAccesses(array $accesses)
{
    printf('<table>
<tr>
<th>Firstname</th>
<th>Lastname</th>
<th>Check-in time</th>
</tr>');

    $output = <<<OUTPUT
<tr>
<td>%s</td>
<td>%s</td>
<td>%s</td>
</tr>
OUTPUT;

    foreach ($accesses as $access) {
        $dateTime = $access['checkIn']['attendance']
            ? (new DateTime())->setTimestamp($access['checkIn']['attendanceDate'])->format('d.m.Y H:i')
            : '';
        printf($output, $access['firstName'], $access['lastName'], $dateTime);
    }
    printf('</table>');
}
