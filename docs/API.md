# UTP Scholarship System — API Reference

All endpoints require an active PHP session (cookie-based). CSRF tokens must be included in every POST request.

---

## Authentication

| Header | Value |
|--------|-------|
| Cookie | `PHPSESSID=<session_id>` |

All POST requests must include `csrf_token` in the request body.

---

## POST `/api/check-eligibility.php`

**Auth:** Student (verified email required)

Check programme eligibility based on submitted academic grades.

### Request Body (`application/x-www-form-urlencoded`)

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `csrf_token` | string | ✅ | CSRF protection token |
| `qual_type` | string | ✅ | `SPM`, `O-Level`, or `IGCSE` |
| `subjects[]` | string[] | ✅ | Array of subject names |
| `grades[]` | string[] | ✅ | Array of letter grades (parallel to subjects) |

### Response (`application/json`)

**200 OK — Success:**
```json
{
  "success": true,
  "qualification_id": 5,
  "results": [
    {
      "programme_id": 1,
      "programme_name": "Computer Science",
      "category": "Technology",
      "eligible": true,
      "fit_percentage": 85.0,
      "confidence_label": "Strong Match",
      "recommendation": "Excellent match for Computer Science...",
      "subject_results": [...],
      "gaps": []
    }
  ],
  "scholarships": [
    {
      "id": 1,
      "name": "PETRONAS Education Sponsorship",
      "budget_min": 0,
      "budget_max": 200000,
      "best_fit": 85.0
    }
  ]
}
```

**200 OK — Validation Error:**
```json
{
  "success": false,
  "error": "Please submit at least one subject and grade."
}
```

**403 Forbidden:** User not logged in or email not verified.

---

## POST `/api/submit-application.php`

**Auth:** Student (verified email required)

Submit a formal scholarship application with programme preferences.

### Request Body (`application/x-www-form-urlencoded`)

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `csrf_token` | string | ✅ | CSRF protection token |
| `programme_1` | int | ✅ | 1st choice programme ID |
| `programme_2` | int | ✅ | 2nd choice programme ID |
| `programme_3` | int | ✅ | 3rd choice programme ID |
| `scholarship_id` | int | ❌ | Preferred scholarship ID |

### Response (`application/json`)

**200 OK — Success:**
```json
{
  "success": true,
  "application_id": 12,
  "message": "Application submitted successfully."
}
```

**200 OK — Error:**
```json
{
  "success": false,
  "error": "You must complete an eligibility check first."
}
```

**403 Forbidden:** User not logged in or email not verified.

---

## POST `/api/logout.php`

**Auth:** Any authenticated user

Terminates the current session and redirects to the login page.

### Request

No body required.

### Response

**302 Redirect** → `/auth/login.php`

---

## GET `/api/export-audit-csv.php`

**Auth:** Admin only

Exports the audit log as a downloadable CSV file.

### Query Parameters

| Param | Type | Required | Description |
|-------|------|----------|-------------|
| `start_date` | string | ❌ | Filter start (YYYY-MM-DD) |
| `end_date` | string | ❌ | Filter end (YYYY-MM-DD) |

### Response

**200 OK** — `Content-Type: text/csv` with `Content-Disposition: attachment`

**403 Forbidden:** User is not an admin.

---

## Error Codes

| HTTP Code | Meaning |
|-----------|---------|
| 200 | Success (check `success` field in JSON body) |
| 302 | Redirect (session expired or action completed) |
| 403 | Forbidden (not authenticated or insufficient role) |
| 429 | Rate limited (too many login attempts) |
| 500 | Server error (database connection failure) |
