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
4. After trying 5 times and cannot confirm, the deposit will be come exipred
5. Only after the deposit is 'canceled', 'completed' or 'expired', the user can request a new deposit
6. You can only confirm a pending deposit
7. When the depoist is confirmed you can give me a callback url to notify you the updated deposit

> Deposit Status

```
enum DepositStatus: int
{
    case PENDING = 1;
    case CONFIRMED = 2;
    case COMPLETED = 3;
    case CANCELED = 4;
    case EXPIRED = 5;
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
