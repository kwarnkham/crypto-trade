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

> User can deposit TRC-20 USDT to the platform

-   **POST**
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
