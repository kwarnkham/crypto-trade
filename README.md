# Test Data

1. Request url is https://crypto-api.pi55xx.com
2. x-agent is 'agent'
3. api key is 'sQGmLykW2kGy4kz256bSLMb5dU5Waq4302qsHawvE58YwBYh1GWxiE8MPY60iPNM'

-   Please note that x-api-key is JWT string, you can't use the key directly

# Agent Key

> Every request must have 2 headers.

1. x-agent (The name of the agent)
2. x-api-key (The key of agent encoded in JWT format)
    - payload for JWT is {key:'your-agent-key'}
    - header for JWT is {alg:'HS256', 'typ':'JWT'}
    - below is example using https://github.com/firebase/php-jwt

```
JWT::encode(['key' => $this->key], $this->key, 'HS256', null, ['alg' => 'HS256', 'typ' => 'JWT'])
```

# Deposit

## Create a new deposit

> User can deposit TRC-20 USDT to the platform

-   **POST** (http://127.0.0.1:8000/api/deposits/agent)
-   **Data**
    1. code (Unique user's id from agent platform) [String || Integer]
    2. name (User's name) [String || Integer]
    3. amount (Deposit amount) [integer]

```
curl --location 'http://127.0.0.1:8000/api/deposits/agent' \
--header 'x-agent: agent' \
--header 'x-api-key: eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJrZXkiOiJmVHN2SXNNNDJKQjBJS2d4RTFyYUxoUmRxN3BYZFdJMHJVR1RzbEp3b0xOZTNpa3VjeXN2Q1h6Y2VLZHZ5SlJCIn0.bm75Ryp8LnqAa1ue_CHlhmOL-xCYnhuWgeAYv8xGMEs' \
--header 'Accept: application/json' \
--header 'Content-Type: application/x-www-form-urlencoded' \
--data-urlencode 'code=1' \
--data-urlencode 'name=Moon' \
--data-urlencode 'amount=1'
```

> Response

-   **wallet** > User must send the requested amount to this wallet adderss(Tron Wallet)
-   **depoist** > The created deposit instance. Please save it because you will need it for another request

```
{
   "wallet": "TDqVegmPEb3juuAV4vZYNS5AWUbvTUFH3y",
   "deposit": {
       "wallet_id": 1,
       "amount": 1,
       "user_id": 1,
       "updated_at": "2023-10-13T08:58:19.000000Z",
       "created_at": "2023-10-13T08:58:19.000000Z",
       "id": 3
   }
}
```

## Confirm the created deposit

> After sending USDT to the wallet responded from **Create a new depoist** api, you must request this api to confirm the deposit

-   **POST** (http://127.0.0.1:8000/api/deposits/agent/{deposit_id}/confirm)
-   **URL param**
    1. deposit_id

```
curl --location --request POST 'http://127.0.0.1:8000/api/deposits/agent/3/confirm' \
--header 'x-agent: agent' \
--header 'x-api-key: eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJrZXkiOiJmVHN2SXNNNDJKQjBJS2d4RTFyYUxoUmRxN3BYZFdJMHJVR1RzbEp3b0xOZTNpa3VjeXN2Q1h6Y2VLZHZ5SlJCIn0.bm75Ryp8LnqAa1ue_CHlhmOL-xCYnhuWgeAYv8xGMEs' \
--header 'Accept: application/json'
```

> Response

-   **depoist** > The confirmed deposit instance. Please updated it in your platform to keep the data in sync

```
{
    "deposit": {
        "id": 4,
        "user_id": 1,
        "wallet_id": 1,
        "transaction_id": null,
        "amount": 1,
        "status": 2,
        "attempts": 0,
        "created_at": "2023-10-13T09:56:52.000000Z",
        "updated_at": "2023-10-13T09:57:04.000000Z"
    }
}
```

> Note

1. After confirming the depoist, the system will do check up with the network to confirm if the user really sent the specific amount USDT
2. The check up will happen every minute
3. The check up will be performed up to 5 times
4. After trying 5 times and cannot confirm, the deposit will become canceled
5. You can only confirm a pending withdraw
6. When the withdraw is confirmed you can give me a callback url to notify you the updated withdraw

> Deposit Status

```
enum WithdrawStatus: int
{
    case PENDING = 1;
    case CONFIRMED = 2;
    case COMPLETED = 3;
    case CANCELED = 4;
}
```

## Cancel a pending deposit

> A request can be sent to cancel a pending deposit

-   **POST** (http://127.0.0.1:8000/api/deposits/agent/{deposit_id}/cancel)
-   **URL param**
    1. deposit_id

```
curl --location --request POST 'http://127.0.0.1:8000/api/deposits/agent/7/cancel' \
--header 'x-agent: agent' \
--header 'x-api-key: eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJrZXkiOiJmVHN2SXNNNDJKQjBJS2d4RTFyYUxoUmRxN3BYZFdJMHJVR1RzbEp3b0xOZTNpa3VjeXN2Q1h6Y2VLZHZ5SlJCIn0.bm75Ryp8LnqAa1ue_CHlhmOL-xCYnhuWgeAYv8xGMEs' \
--header 'Accept: application/json'
```

> Response

-   **depoist** > The canceled deposit instance
-   **user** > The user instance relating to the deposit
-   **wallet** > The wallet instance relating to the deposit

```
{
    "deposit": {
        "id": 7,
        "user_id": 1,
        "wallet_id": 1,
        "transaction_id": null,
        "amount": 1,
        "status": 4,
        "attempts": 0,
        "created_at": "2023-10-16T10:06:19.000000Z",
        "updated_at": "2023-10-16T10:06:31.000000Z",
        "user": {

        },
        "wallet": {

        }
    }
}
```

## List deposits

> A request can be sent to list all deposits

-   **GET** (http://127.0.0.1:8000/api/deposits/agent)
-   **Filter param**
    1. status, either one of these [1,2,3,4,5]

```
curl --location 'http://127.0.0.1:8000/api/deposits/agent?status=1' \
--header 'x-agent: agent' \
--header 'x-api-key: eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJrZXkiOiJmVHN2SXNNNDJKQjBJS2d4RTFyYUxoUmRxN3BYZFdJMHJVR1RzbEp3b0xOZTNpa3VjeXN2Q1h6Y2VLZHZ5SlJCIn0.bm75Ryp8LnqAa1ue_CHlhmOL-xCYnhuWgeAYv8xGMEs' \
--header 'Accept: application/json'
```

> Response

```
{
    "current_page": 1,
    "data": [
        {
            "id": 7,
            "user_id": 1,
            "wallet_id": 1,
            "transaction_id": null,
            "amount": 1,
            "status": 4,
            "attempts": 0,
            "created_at": "2023-10-16T10:06:19.000000Z",
            "updated_at": "2023-10-16T10:06:31.000000Z",
            "wallet": {

            },
            "user": {

            }
        }
    ],
    "first_page_url": "http://127.0.0.1:8000/api/deposits/agent?page=1",
    "from": 1,
    "last_page": 1,
    "last_page_url": "http://127.0.0.1:8000/api/deposits/agent?page=1",
    "links": [
        {
            "url": null,
            "label": "&laquo; Previous",
            "active": false
        },
        {
            "url": "http://127.0.0.1:8000/api/deposits/agent?page=1",
            "label": "1",
            "active": true
        },
        {
            "url": null,
            "label": "Next &raquo;",
            "active": false
        }
    ],
    "next_page_url": null,
    "path": "http://127.0.0.1:8000/api/deposits/agent",
    "per_page": 10,
    "prev_page_url": null,
    "to": 7,
    "total": 7
}
```

# Withdraw

## Create a new withdraw

> User can withdraw TRC-20 USDT to the wallet

-   **POST** (http://127.0.0.1:8000/api/withdraws/agent)
-   **Data**
    1. code (Unique user's id from agent platform) [String || Integer]
    2. to (Wallet's address) [String]
    3. amount (Withdraw amount) [integer]

```
curl --location 'http://127.0.0.1:8000/api/withdraws/agent' \
--header 'x-agent: agent' \
--header 'x-api-key: eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJrZXkiOiJHRkJ4YjhzZnV3eXA3WlNZaTU1NVVHb0FSVVp6UkhuQnRXTm1FSkVGTGVXNUFvVWhWMzk0VTJqTld2S0t4b2xGIn0.R-fmd_RwWReRScoTUQcxfZUq6MF_-Daj4Pkg0hmtaWk' \
--header 'Accept: application/json' \
--header 'Content-Type: application/x-www-form-urlencoded' \
--form 'code="1"' \
--form 'to="TDqVegmPEb3juuAV4vZYNS5AWUbvTUFH3y"' \
--form 'amount="2"'
```

> Response

-   **withdraw** > The created withdraw instance. Please save it because you will need it for another request

```
{
    "withdraw": {
        "user_id": 1,
        "to": "TDqVegmPEb3juuAV4vZYNS5AWUbvTUFH3y",
        "amount": 2,
        "fee": 1,
        "updated_at": "2023-10-19T07:04:19.000000Z",
        "created_at": "2023-10-19T07:04:19.000000Z",
        "id": 2
    }
}
```

> Note

1. Only wallet address is valid, the withdraw can be continued
2. The withdraw amount cannot be greather than balance amount
3. The withdraw amount must be greater than the withdraw fee

## List Withdraw

> A request can be sent to list all withdraw

-   **GET** (http://127.0.0.1:8000/api/withdraws/agent)
-   **Filter param**
    1. status, either one of these [1,2,3,4]

```
curl --location 'http://127.0.0.1:8000/api/withdraws/agent?status=1' \
--header 'Accept: application/json' \
--header 'x-agent: agent' \
--header 'x-api-key: eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJrZXkiOiJHRkJ4YjhzZnV3eXA3WlNZaTU1NVVHb0FSVVp6UkhuQnRXTm1FSkVGTGVXNUFvVWhWMzk0VTJqTld2S0t4b2xGIn0.R-fmd_RwWReRScoTUQcxfZUq6MF_-Daj4Pkg0hmtaWk'
```

> Response

```
{
    "current_page": 1,
    "data": [
        {
            "id": 1,
            "user_id": 1,
            "wallet_id": null,
            "to": "TDqVegmPEb3juuAV4vZYNS5AWUbvTUFH3y",
            "amount": 2,
            "fee": 1,
            "status": 1,
            "txid": null,
            "transaction_id": null,
            "attempts": 0,
            "created_at": "2023-10-19T07:03:46.000000Z",
            "updated_at": "2023-10-19T07:03:46.000000Z",
            "user": {
                "id": 1,
                "code": "1",
                "name": "agent",
                "balance": 4,
                "agent_id": 1,
                "created_at": "2023-10-19T04:49:59.000000Z",
                "updated_at": "2023-10-19T05:20:56.000000Z",
                "agent": {
                    "id": 1,
                    "name": "agent",
                    "remark": null,
                    "status": 1,
                    "ip": "103.213.30.137",
                    "created_at": null,
                    "updated_at": "2023-10-19T03:42:36.000000Z"
                }
            },
            "wallet": null
        }
    ],
    "first_page_url": "http://127.0.0.1:8000/api/withdraws/agent?page=1",
    "from": 1,
    "last_page": 1,
    "last_page_url": "http://127.0.0.1:8000/api/withdraws/agent?page=1",
    "links": [
        {
            "url": null,
            "label": "&laquo; Previous",
            "active": false
        },
        {
            "url": "http://127.0.0.1:8000/api/withdraws/agent?page=1",
            "label": "1",
            "active": true
        },
        {
            "url": null,
            "label": "Next &raquo;",
            "active": false
        }
    ],
    "next_page_url": null,
    "path": "http://127.0.0.1:8000/api/withdraws/agent",
    "per_page": 10,
    "prev_page_url": null,
    "to": 2,
    "total": 2
}
```

## Confirm the created withdraw

> After sending USDT to the wallet responded from **Create a new withdraw** api, you must request this api to confirm the withdraw

-   **POST** (http://127.0.0.1:8000/api/withdraws/agent/{withdraw_id}/confirm)
-   **URL param**
    1. withdraw_id

```
curl --location 'http://127.0.0.1:8000/api/withdraws/agent/1/confirm' \
--header 'x-agent: agent' \
--header 'x-api-key: eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJrZXkiOiJhR3FGQUVWODR1azFWNFN2U1A4SUhjUHhrT0E1Rk1OdjE5WEdsOGNZenRvRzJJN25nR05Fckpoc2F4Tmg3NGs5In0.977MGNWWUr97oLCfSeK9eTaCa-glQc_AcubgJ8SQVoo' \
--header 'Accept: application/json' \
```

> Response

-   **withdraw** > The confirmed withdraw instance. Please updated it in your platform to keep the data in sync

```
{
    "withdraw": {
        "id": 1,
        "user_id": 1,
        "wallet_id": 1,
        "to": "TWxQ5m1TMLumFH7bMws4Q1qoP1FeYfkhKc",
        "amount": 5,
        "fee": 1,
        "status": 2,
        "txid": "618bf54de993a7dbbda82827fe3d18ac23fa99fb135595372b2143509eec6bfc",
        "transaction_id": null,
        "attempts": 0,
        "created_at": "2023-10-24T10:01:28.000000Z",
        "updated_at": "2023-10-24T10:01:44.000000Z"
    }
}
```

> Note

1. After confirming the withdraw, the system will do check up with the network to confirm if the user really sent the specific amount USDT
2. The check up will happen every minute
3. The check up will be performed up to 5 times
4. After trying 5 times and cannot confirm, the withdraw will be come exipred
5. You can only confirm a pending withdraw
6. When the confirm is confirmed you can give me a callback url to notify you the updated withdraw

> Withdraw Status

```
enum WithdrawStatus: int
{
    case PENDING = 1;
    case CONFIRMED = 2;
    case COMPLETED = 3;
    case CANCELED = 4;
}

```

## Cancel a pending withdraw

> A request can be sent to cancel a pending withdraw

-   **POST** (http://127.0.0.1:8000/api/withdraws/agent/{withdraw_id}/cancel)
-   **URL param**
    1. withdraw_id

```
curl --location --request POST 'http://127.0.0.1:8000/api/withdraws/agent/1/cancel' \
--header 'Accept: application/json' \
--header 'x-agent: agent' \
--header 'x-api-key: eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJrZXkiOiJHRkJ4YjhzZnV3eXA3WlNZaTU1NVVHb0FSVVp6UkhuQnRXTm1FSkVGTGVXNUFvVWhWMzk0VTJqTld2S0t4b2xGIn0.R-fmd_RwWReRScoTUQcxfZUq6MF_-Daj4Pkg0hmtaWk'
```

> Response

-   **withdraw** > The canceled withdraw instance

```
{
    "withdraw": {
        "id": 1,
        "user_id": 1,
        "wallet_id": 2,
        "to": "TDqVegmPEb3juuAV4vZYNS5AWUbvTUFH3y",
        "amount": 2,
        "fee": 1,
        "status": 4,
        "txid": null,
        "transaction_id": null,
        "attempts": 0,
        "created_at": "2023-10-19T07:03:46.000000Z",
        "updated_at": "2023-10-24T03:42:27.000000Z"
    }
}
```

# Transfer

## Create a new transfer

> User can transfer TRC-20 USDT to each other in the platform

-   **POST** (http://127.0.0.1:8000/api/transfers/agent)
-   **Data**
    1. from (Unique user's id from agent platform) [String || Integer]
    2. to (Unique user's id to agent platform) [String || Integer]
    3. amount (Transfer amount) [integer]

```
curl --location 'http://127.0.0.1:8000/api/transfers/agent' \
--header 'Accept: application/json' \
--header 'Content-Type: application/x-www-form-urlencoded' \
--header 'x-agent: agent' \
--header 'x-api-key: eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJrZXkiOiJHRkJ4YjhzZnV3eXA3WlNZaTU1NVVHb0FSVVp6UkhuQnRXTm1FSkVGTGVXNUFvVWhWMzk0VTJqTld2S0t4b2xGIn0.R-fmd_RwWReRScoTUQcxfZUq6MF_-Daj4Pkg0hmtaWk' \
--form 'from="1"' \
--form 'to="2"' \
--form 'amount="2"'
```

> Response

-   **transfer** > The created transfer instance. Please save it because you will need it for another request

```
{
    "transfer": {
        "user_id": 1,
        "recipient_id": 2,
        "amount": 2,
        "fee": 1,
        "updated_at": "2023-10-24T04:20:28.000000Z",
        "created_at": "2023-10-24T04:20:28.000000Z",
        "id": 1
    }
}
```

> Note

1. From and to user id must be valid
2. Only users from same agent can transfer
3. The transfer amount must be greather than balance amount of from user

## List Transfer

> A request can be sent to list all transfer

-   **GET** (http://127.0.0.1:8000/api/transfers/agent)
-   **Filter param**
    1. status, either one of these [1,2,3,4]

```
curl --location 'http://127.0.0.1:8000/api/transfers/agent' \
--header 'Accept: application/json' \
--header 'x-agent: agent' \
--header 'x-api-key: eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJrZXkiOiJHRkJ4YjhzZnV3eXA3WlNZaTU1NVVHb0FSVVp6UkhuQnRXTm1FSkVGTGVXNUFvVWhWMzk0VTJqTld2S0t4b2xGIn0.R-fmd_RwWReRScoTUQcxfZUq6MF_-Daj4Pkg0hmtaWk'
```

> Response

```
{
    "current_page": 1,
    "data": [
        {
            "id": 1,
            "user_id": 1,
            "recipient_id": 2,
            "amount": 2,
            "fee": 1,
            "created_at": "2023-10-24T04:20:28.000000Z",
            "updated_at": "2023-10-24T04:20:28.000000Z",
            "user": {
                "id": 1,
                "code": "1",
                "name": "agent",
                "balance": 102,
                "agent_id": 1,
                "created_at": "2023-10-19T04:49:59.000000Z",
                "updated_at": "2023-10-24T04:20:28.000000Z"
            },
            "recipient": {
                "id": 2,
                "code": "2",
                "name": "user2",
                "balance": 1,
                "agent_id": 1,
                "created_at": "2023-10-19T04:49:59.000000Z",
                "updated_at": "2023-10-24T04:20:28.000000Z"
            }
        }
    ],
    "first_page_url": "http://127.0.0.1:8000/api/transfers/agent?page=1",
    "from": 1,
    "last_page": 1,
    "last_page_url": "http://127.0.0.1:8000/api/transfers/agent?page=1",
    "links": [
        {
            "url": null,
            "label": "&laquo; Previous",
            "active": false
        },
        {
            "url": "http://127.0.0.1:8000/api/transfers/agent?page=1",
            "label": "1",
            "active": true
        },
        {
            "url": null,
            "label": "Next &raquo;",
            "active": false
        }
    ],
    "next_page_url": null,
    "path": "http://127.0.0.1:8000/api/transfers/agent",
    "per_page": 10,
    "prev_page_url": null,
    "to": 1,
    "total": 1
}
```

## Get user info

> A request can be sent query the user info
> Note that the url param is user_id, not code

-   **GET** (http://127.0.0.1:8000/api/users/agent/{user_id})

```
curl --location 'http://127.0.0.1:8000/api/users/agent/1' \
--header 'x-agent: agent' \
--header 'x-api-key: eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJrZXkiOiJhR3FGQUVWODR1azFWNFN2U1A4SUhjUHhrT0E1Rk1OdjE5WEdsOGNZenRvRzJJN25nR05Fckpoc2F4Tmg3NGs5In0.977MGNWWUr97oLCfSeK9eTaCa-glQc_AcubgJ8SQVoo' \
--header 'Accept: application/json'
```

> Response

```
{
    "user": {
        "id": 1,
        "code": "3",
        "name": "Moon",
        "balance": 0,
        "agent_id": 1,
        "created_at": "2023-10-27T08:30:42.000000Z",
        "updated_at": "2023-10-27T08:30:42.000000Z"
    }
}
```
