# GLPI API Documentation

## Introduction

The GLPI API is a RESTful API. This version of the API is referred to as the "High-level API".
The original API is now referred to as the "legacy API" but it is still available and in some cases, allows more functionality at the cost of user-friendliness.

For more information on the legacy API, see the [legacy API documentation](/apirest.php).

## Authentication

Authentication for the API is all done via OAuth2.
The following OAuth2 grant types are supported:

- Password: Authenticate with a client ID, secret, and username/password. This is the recommended method for automated scripts.
- Authorization Code: Interactive authentication. This is the recommended method for user-facing applications.

Grants that do not require specific user credentials are not currently supported as there is no virtual user and scope system implemented at this time.

### Client Setup

Clients must be registered with the GLPI server before they can be used to authenticate.
Clients are used to identify the application/user that is using the API and may be limited to specific grant types.

To register a client, you can do so from Setup > OAuth Clients.
When the client is created, there will be an automatically generated client ID and secret.

### Scopes

- `email`: Access to the user's own default email address
- `user`: Access to the user's information
- `status`: Access to the status endpoints (Does not apply when accessed via the CLI commands)
- `inventory`: Access to submit inventory from the GLPI Agent via Client Credentials grant
- `graphql`: Access to the GraphQL endpoint
- `api`: Access to the API (All other endpoints not handled by their own scope)


### Password Grant

To authenticate with the password grant, you will need to make a POST request to the `/api.php/token` endpoint with the following parameters in the request body:

- `grant_type`: `password`
- `client_id`: The client ID of the client you are using
- `client_secret`: The client secret of the client you are using
- `username`: The username of the user you are authenticating as
- `password`: The password of the user you are authenticating as
- `scope`: The scope of the request. The client must be allowed to use the requested scope.

The endpoint will respond (if successful) with a JSON object containing the following fields:

- `token_type`: `Bearer`
- `expires_in`: The number of seconds until the token expires. By default, this is set to 1 hour, but it can be controlled by defining the `GLPI_OAUTH_ACCESS_TOKEN_EXPIRES` in your `config/local_define.php` file (See the [installation documentation](https://glpi-install.readthedocs.io/en/latest/install/index.html#files-and-directories-locations) for more information on configuring GLPI config constants).
- `access_token`: The access token that can be used to authenticate future requests.

For API requests, you should include the access token in the `Authorization` header of the request with the `Bearer` prefix in the format `Authorization: Bearer <access_token>`.

### Authorization Code Grant

To authenticate with the authorization code grant, you will need to make a GET request to the `/api.php/authorize` endpoint with the following parameters in the query string:

- `response_type`: `code`
- `client_id`: The client ID of the client you are using
- `scope`: The scope of the request. The client must be allowed to use the requested scope.

The endpoint will respond with a redirect to the GLPI login page.
If successful, the user will be redirected back to the client with a `code` parameter in the query string.
This code can be used to get an access token by making a POST request to the `/api.php/token` endpoint with the following parameters in the request body:

- `grant_type`: `authorization_code`
- `client_id`: The client ID of the client you are using
- `client_secret`: The client secret of the client you are using
- `code`: The code returned by the `/api.php/authorize` endpoint

The endpoint will respond (if successful) with a JSON object containing the following fields:

- `token_type`: `Bearer`
- `expires_in`: The number of seconds until the token expires. By default, this is set to 1 hour, but it can be controlled by defining the `GLPI_OAUTH_ACCESS_TOKEN_EXPIRES` in your `config/local_define.php` file (See the [installation documentation](https://glpi-install.readthedocs.io/en/latest/install/index.html#files-and-directories-locations) for more information on configuring GLPI config constants).
- `access_token`: The access token that can be used to authenticate future requests.
- `refresh_token`: The refresh token that can be used to get a new access token when the current one expires.

### Client Credentials Grant

Authentication with the client credentials grant is only supported for the `inventory` scope.
Access to other API resources requires an actual user to be authenticated which is not possible with the client credentials grant.

## RSQL Filtering

The GLPI supports "REST Query Language" (RSQL) filtering in a `filter` parameter for certain endpoints that return collections of items.
RSQL itself is a super-set of the "Feed Item Query Language" (FIQL).
It allows for an intuitive and easy to use syntax for filtering collections of items.

The basic syntax is as follows: `<field><operator><value>`. For some operators, there is no value, so the syntax is just `<field><operator>`.

### Operators
| Operator      | Description              | Example                |
|---------------|--------------------------|------------------------|
| `==`          | Equal to                 | `name==John`           |
| `!=`          | Not equal to             | `name!=John`           |
| `=in=`        | In                       | `name=in=(John,Paul)`  |
| `=out=`       | Not in                   | `name=out=(John,Paul)` |
 | `=lt=`        | Less than                | `age=lt=18`            |
 | `=le=`        | Less than or equal to    | `age=le=18`            |
 | `=gt=`        | Greater than             | `age=gt=18`            |
 | `=ge=`        | Greater than or equal to | `age=ge=18`            |
 | `=like=`      | Like                     | `name=like=John`       |
| `=ilike=`     | Case insensitive like    | `name=ilike=John`      |
| `=isnull=`    | Is null                  | `name=isnull=`         |
| `=isnotnull=` | Is not null              | `name=isnotnull=`      |
| `=empty=`     | Is empty                 | `name=empty=`          |
| `=notempty=`  | Is not empty             | `name=notempty=`       |

The fields available for filtering are dependent on the endpoint. Everything that is returned by the endpoint can be filtered on in the exact same format that they are returned from the server.
For example, if an endpoint returns a collection of items and each item has an `emails` property with an array of email addresses which each contain an `email` property, you can filter using `emails.email` as the field name in the filter.

## Endpoints

For specific documentation on each endpoint, see the Swagger documentation at `/api.php/doc`.
You may also make a request to `/api.php/doc` to get the raw Swagger JSON document if you set the `Content-Type` header to `application/json` or add `.json` to the end of the URL.

## GraphQL

The GLPI API is also available via a GraphQL wrapper at `/api.php/GraphQL`. Like all compliant GraphQL implementations, it is self documenting and accepts only POST requests.
Since it is a wrapper around the REST API, that means that any object schema defined and available through the REST API is also available via GraphQL.
It also means that authentication is the exact same as the rest of the API.
The only difference is that you can access more properties in some cases via the GraphQL API.
For example, when you request a list of cartridge models (`CartridgeItem`) via the REST API it returns the `id`, `name`, and `comment` properties for the associated printer models.
When you request the data for cartridge models via GraphQL, it unlocks access to all the properties of the `PrinterModal` schema.
This can reduce the number of requests that need to be made to the API in some cases.

The implementation of the GraphQL does not include any mutators. It is read-only.

The GraphQL API supports the same parameters as the REST API for filtering, sorting, and pagination.

Basic Example:
```graphql
query {
    Ticket(limit: 1, filter: "name=ilike=test") {
        id
        name
        status {
            id
            name
        }
    }
}
```

Example crossing between schemas:
```graphql
query {
    CartridgeItem {
        id
        name
         printer_models {
            name
            # the product number is not usually returned by the REST API, but is available in GraphQL since you can access the full PrinterModel schema here.
            product_number
        }
    }
}
```

You can explore the schemas using standard GraphQL requests like:
```graphql
query {
    __schema {
        types {
            name
            description
            fields {
                description
                type {
                    name
                    description
                }
            }
        }
    }
}
```

Alternatively, a REST API interface is available for the raw GraphQL type declarations at `/api.php/GraphQL/Schema` using a GET request.

As is standard with GraphQL, you MUST specify each property that you want to be returned.

For more information about GraphQL, see the [GraphQL documentation](https://graphql.org/learn/).


## API Versioning

The API is versioned by the URL path. If no version is specified, the latest version will be used.
If you specify `v1`, your request will be routed to the legacy API while `v2` and later will be routed to the high-level API.
Example: `/api.php/v2/...`

### Version pinning strategy

When you specify a version in the URL, the API will use the following rules when determining which version to use:
- If only a major version is specified, the latest minor version will be used. For example `/api.php/v2/...` may use `v2.1` if it is the latest version.
- If a major and minor version are specified, the latest patch version will be used. For example `/api.php/v2.1/...` may use `v2.1.3` if it is the latest version.
- If a major, minor, and patch version are specified, that exact version will be used. For example `/api.php/v2.1.3/...` will use `v2.1.3`.

In general, specifying only the major version is sufficient and shouldn't break your application when new versions are released as long as there is a supported version available.
