# Quick Test Guide

## Step 1: Register User (POST)

**Request di Thunder Client:**
- Method: `POST`
- URL: `http://localhost:8001/api/register`
- Headers:
  ```
  Content-Type: application/json
  X-Correlation-ID: test-register-001
  ```
- Body (raw JSON):
  ```json
  {
    "name": "Test User",
    "email": "test@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }
  ```

**Expected Response:**
- Status: `201 Created`
- Response Body: User data

## Step 2: Login (POST)

**Request di Thunder Client:**
- Method: `POST`
- URL: `http://localhost:8001/api/login`
- Headers:
  ```
  Content-Type: application/json
  X-Correlation-ID: test-login-001
  ```
- Body (raw JSON):
  ```json
  {
    "email": "test@example.com",
    "password": "password123"
  }
  ```

**Expected Response:**
- Status: `200 OK`
- Response Body:
  ```json
  {
    "access_token": "...",
    "token_type": "bearer",
    "expires_in": 3600
  }
  ```
- **SIMPAN TOKEN** dari response ini!

## Step 3: Test Gateway dengan Token

**Request di Thunder Client:**
- Method: `GET`
- URL: `http://localhost:8000/api/gateway/siswa/1`
- Headers:
  ```
  Authorization: Bearer <token_dari_step_2>
  X-Correlation-ID: test-gateway-001
  ```

**Expected Response:**
- Status: `200 OK`
- Response Body: Data siswa dan tabungan
- Response Header: `X-Correlation-ID: test-gateway-001`

## Checklist

- [ ] Register user berhasil
- [ ] Login berhasil dan dapat token
- [ ] Gateway request berhasil dengan token
- [ ] Correlation ID sama muncul di semua log files

