# Oniva GraphQL API documentation and samples

Documentation and sample package for the [Oniva](https://www.oniva.events) GraphQL API.


## Getting started

For API access you generally need the following parameters provided by Oniva.

|Parameter| Example                                  | Note                                                                                                                                                                                                                                                 |
---|------------------------------------------|------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
|Endpoint Url| https://app-staging.oniva.dev/api/graphql  | The url of your Oniva instance appended by `/api/graphql`                                                                                                                                                                                            |
|App token key| 9b90683d5d689942cff7b46c5725547961c1a9e5 | Token for app token authentication. This is provided by the Oniva support or can be created by a super administrator in the admin interface. Alternatively, event token or further authentication methods for user based integrations are available. |

Basic GraphQL knowledge is required for successfully consuming the API. 
For general learnings please consult [https://graphql.org/learn/](https://graphql.org/learn/)

### API introspection

GraphQL APIs are documented by their schema and are available through [GraphQL introspection](https://graphql.org/learn/introspection/)

For development, we therefore suggest using an IDE with GraphQL introspection feature.
Alternatively you can introspect the API through an online tool such as [graphiql-online.com](https://graphiql-online.com/). By that you get access on the whole API specification.


### Requests

Requests can be done through a specific GraphQL client or any other HTTP requests. 

Curl example returning the "foo" event's public id and title fields. If the event does not exist, null is returned: 

```shell
curl \
  -X POST \
  -H "Content-Type: application/json" \
  --data '{
      "query": " query event {viewer { event(key: \"foo\") { id title } } }"
  }' \
  https://app-staging.oniva.dev/api/graphql
```

Cf. [Apollo blog post "4 simple ways to call a GraphQL API"](https://blog.apollographql.com/4-simple-ways-to-call-a-graphql-api-a6807bcdb355)


## Concepts

### Viewer

Queries as well as mutations are based on the viewer concept (cf. https://medium.com/the-graphqlhub/graphql-and-authentication-b73aed34bbeb).
It makes the viewer sub-nodes dependent from the viewer object itself and separates the different concerns and permissions.

Example query for an event dependent on the viewer:

```graphql
query Event($token: String!) {
    viewer(token: $token) {
        event(key: $key) {
            title
            description
        }
    }
}
```
The public field `title` is exposed for all events, while the protected `description` field is only returned for events the viewer has explicitly permission for.

Further more the viewer supports a `language` argument for specific languages. Per default supported values are `de`, `fr`, `it`, `en`.

### Authentication

The viewer can be queried without authentication. In that case, only public properties are returned. For authentication,
the `createTokenByXyz` mutations are called to generate a Json Web Token. For further queries, this token is passed to
the viewer by argument.

For 3rd party application integrations the following 2 authentication methods are relevant:

- **App token** authentication grants access for a set of events limited by event types.
- **Event token** authentication grants access for a specific event.

Example mutation for app token authentication:

```graphql
mutation Auth($key: String!) {
    authentication {
        createTokenByAppToken(appToken: { key: $key }) {
            token
        }
    }
}
```

## Use Cases

A small collection of use cases and samples are available.

### Event import

Use case: _Event import to render the upcoming events in the intranet._

Requesting the available events into a third-party application requires the following 2 requests:

1.  Get the authentication token
2.  Request the events via the `eventTeasers`

The sample is available in [samples/oniva-events-sample.php](samples/oniva-events-sample.php)

### Event participants accesses
 
Use case: _Display the currently checked-in guests in an event app_

Requesting a list of guests from an event in combination with their check-in status requires the following conditions:

- Token is a valid event or app token
- The check-in setting is enabled and names are exposed for the event

Then, also an authentication as well as the actual request is needed:

1.  Get the authentication token
2.  Request the participants accesses

The sample is available in [samples/oniva-participants-accesses-sample.php](samples/oniva-participants-accesses-sample.php)
