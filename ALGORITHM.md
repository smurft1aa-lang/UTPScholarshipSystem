# UTP Eligibility & Scholarship Recommendation Algorithm

**File:** `includes/ai_engine.php` + `includes/GradeMapper.php`  
**Class:** `AIEngine`  
**Purpose:** Evaluate a student's academic results against official UTP entry requirements, calculate a fit percentage, rank programmes, and match scholarships.

---

## Overview

When a student submits their SPM, O-Level, or IGCSE grades, the system does not simply check pass or fail. It runs a **weighted scoring algorithm** that:

1. Converts letter grades into numeric points
2. Applies a subject weight to each grade based on how important that subject is to the programme
3. Calculates a **Fit Percentage** — how well the student's overall profile matches each programme
4. Identifies **gaps** — which subjects are missing or below the minimum
5. Assigns a **Confidence Label** — a human-readable summary of the match quality
6. Ranks all programmes — eligible ones first, then by fit percentage descending
7. Matches **scholarships** based on fit percentage thresholds

---

## Step 1 — Grade to Points Conversion

Raw letter grades are meaningless for calculation. The system first converts every grade into a numeric point value using `GradeMapper`.

### SPM Grade Points Table

| Grade | Points |
|-------|--------|
| A+    | 10     |
| A     | 9      |
| A-    | 8      |
| B+    | 7      |
| B     | 6      |
| B-    | 5      |
| C+    | 4      |
| C     | 3      |
| D     | 2      |
| E     | 1      |
| G / F | 0      |

### O-Level / IGCSE Grade Points Table

| Grade | Points |
|-------|--------|
| A*    | 10     |
| A     | 9      |
| B     | 7      |
| C     | 5      |
| D     | 3      |
| E     | 2      |
| F     | 1      |
| G / U | 0      |

**Minimum pass threshold:**
- SPM: Grade C = 3 points
- O-Level/IGCSE: Grade C = 5 points

---

## Step 2 — Subject Weighting

Not all subjects carry equal importance for every programme. Each entry requirement in the database has a `weight` value between `0.00` and `1.00`.

| Weight | Meaning |
|--------|---------|
| `1.00` | Core subject — full importance (e.g. Mathematics for Engineering) |
| `0.90` | High importance (e.g. English for all programmes) |
| `0.80` | Supporting subject (e.g. Bahasa Melayu, Other Subject slots) |

### Example weights for Chemical Engineering (SPM):

| Subject               | Min Grade | Weight |
|-----------------------|-----------|--------|
| Mathematics           | C         | 1.00   |
| Additional Mathematics| C         | 1.00   |
| Physics               | C         | 1.00   |
| Chemistry             | C         | 1.00   |
| English               | C         | 0.90   |
| Bahasa Melayu         | C         | 0.80   |

The system calculates a **maximum possible weighted score** for each programme, which is the sum of `maxPoints × weight` for every required subject:

```
maxWeightedScore = Σ (10 × weight)   for all required subjects
```

---

## Step 3 — Fit Percentage Calculation

For each programme, the student's actual grades are converted to points and multiplied by the subject weight:

```
totalWeightedScore = Σ (studentPoints × weight)   for all matched subjects
```

The **Fit Percentage** is then:

```
fitPercentage = (totalWeightedScore / maxWeightedScore) × 100
```

Rounded to 1 decimal place.

### Worked Example

A student applies for **Chemical Engineering** with SPM grades:

| Subject                | Student Grade | Points | Weight | Weighted Score |
|------------------------|---------------|--------|--------|----------------|
| Mathematics            | A             | 9      | 1.00   | 9.0            |
| Additional Mathematics | B+            | 7      | 1.00   | 7.0            |
| Physics                | A-            | 8      | 1.00   | 8.0            |
| Chemistry              | B             | 6      | 1.00   | 6.0            |
| English                | A+            | 10     | 0.90   | 9.0            |
| Bahasa Melayu          | C             | 3      | 0.80   | 2.4            |

**totalWeightedScore** = 9.0 + 7.0 + 8.0 + 6.0 + 9.0 + 2.4 = **41.4**

**maxWeightedScore** = (10×1.00) + (10×1.00) + (10×1.00) + (10×1.00) + (10×0.90) + (10×0.80) = 10 + 10 + 10 + 10 + 9 + 8 = **57.0**

```
fitPercentage = (41.4 / 57.0) × 100 = 72.6%
```

The student scored **72.6%** fit for Chemical Engineering.

---

## Step 4 — Eligibility Check

Fit percentage alone does not determine eligibility. A student is only marked **eligible** if they meet the **minimum grade** for every required subject.

```
eligible = true   if studentPoints >= minGradePoints   for ALL required subjects
eligible = false  if ANY subject is below minimum OR missing entirely
```

A student can have a high fit percentage but still be **ineligible** — for example, if they scored A+ in everything but missed one required subject entirely.

### "Other Subject" Placeholder Handling

Some programmes (e.g. Information Technology, Business Management) require generic slots like "Other Non-Language Subject" rather than a specific subject. Since the student may take any qualifying subject in that slot, the system auto-matches it and awards **minimum pass points** as partial credit:

```
If subject name contains "other":
    → mark as met = true
    → award minPassPoints × weight to totalWeightedScore
```

---

## Step 5 — Gap Analysis

For every subject where the student either:
- Scored below the minimum required grade, OR
- Did not take the subject at all

The system records a **gap entry**:

```php
gaps[] = [
    'subject'  => 'Physics',
    'required' => 'C',
    'got'      => 'D',
    'message'  => 'Need at least C in Physics, got D'
]
```

These gaps are displayed to the student so they understand exactly what to improve.

---

## Step 6 — Materials Engineering Bonus

Students with exceptional performance in both Physics and Chemistry receive a **+5% fit bonus** when evaluated for Materials Engineering. This reflects the programme's heavy reliance on these two subjects.

```
if programme == "Materials Engineering":
    if physicsPoints >= 9 (A or above) AND chemistryPoints >= 9 (A or above):
        fitPercentage = min(100, fitPercentage + 5.0)
```

---

## Step 7 — Confidence Label

Once the fit percentage is calculated, the system assigns a **Confidence Label** — a plain-English quality indicator shown to the student alongside the score:

| Fit Percentage | Confidence Label   | Badge Colour |
|----------------|--------------------|--------------|
| 90% – 100%     | Excellent Match    | Green        |
| 75% – 89%      | Strong Match       | Green        |
| 60% – 74%      | Good Match         | Yellow       |
| 40% – 59%      | Possible Match     | Yellow       |
| Below 40%      | Not Recommended    | Red          |

---

## Step 8 — Natural Language Recommendation

The engine generates a personalised recommendation sentence for each programme based on eligibility status and fit percentage:

| Condition                          | Recommendation Text |
|------------------------------------|---------------------|
| Eligible + fit ≥ 80%               | "Excellent match. Your grades strongly meet all entry requirements." |
| Eligible + fit ≥ 60%               | "Good match. Consider strengthening core subjects for scholarship opportunities." |
| Eligible + fit < 60%               | "You meet minimum requirements. Strengthening grades would improve your profile." |
| Not eligible + fit ≥ 60%           | "You are close. Focus on improving: [gap subjects]." |
| Not eligible + fit < 60%           | "You do not currently meet requirements. Key areas to improve: [gap subjects]." |

---

## Step 9 — Programme Ranking

All programmes are sorted in this order:

1. **Eligible programmes first** — ineligible programmes are pushed to the bottom
2. Within each group, sorted by **fit percentage descending** — best matches appear at the top

```
sort by: eligible DESC, fitPercentage DESC
```

---

## Step 10 — Scholarship Matching

After eligibility results are generated, the system finds matching scholarships for the student.

### Matching Rules

1. Only scholarships **linked** to at least one of the student's **eligible programmes** are considered
2. Each scholarship has a `min_fit_percentage` threshold — the student's **best fit score** across all linked eligible programmes must meet or exceed this threshold
3. Only **active** scholarships with a valid date range are included
4. Results are sorted by `budget_max` descending — highest value scholarships appear first

```
for each scholarship linked to an eligible programme:
    bestFit = highest fitPercentage among student's eligible programmes linked to this scholarship
    if bestFit >= scholarship.min_fit_percentage:
        → include in matched scholarships
```

### Example

A student is eligible for Computer Science (fit: 85%) and Information Technology (fit: 78%).

| Scholarship       | min_fit_percentage | Linked Programmes       | Student bestFit | Matched? |
|-------------------|--------------------|-------------------------|-----------------|----------|
| PETRONAS Loan     | 70%                | All programmes          | 85%             | ✅ Yes   |
| JPA Scholarship   | 80%                | All programmes          | 85%             | ✅ Yes   |
| Yayasan Gamuda    | 70%                | Civil, Mechanical, Integrated | —         | ❌ No (not linked to CS/IT) |
| YTL Foundation    | 75%                | Engineering, Technology | 78%             | ✅ Yes   |

---

## Full Algorithm Flow Diagram

```
Student submits grades
         │
         ▼
Convert grades → numeric points (GradeMapper)
         │
         ▼
For each active programme:
    │
    ├── For each required subject:
    │       ├── Look up student grade
    │       ├── Convert to points
    │       ├── Multiply by subject weight → weighted score
    │       ├── Check if meets minimum → eligible flag
    │       └── If missing/below → add to gaps[]
    │
    ├── fitPercentage = (totalWeightedScore / maxWeightedScore) × 100
    ├── Apply Materials Engineering bonus if applicable
    ├── Assign confidence label
    └── Generate recommendation text
         │
         ▼
Sort: eligible first → fit percentage descending
         │
         ▼
Match scholarships by:
    ├── Linked to eligible programme
    ├── bestFit >= min_fit_percentage
    └── Active + valid date
         │
         ▼
Return ranked results + matched scholarships to student
```

---

## Performance Monitoring

The engine is wrapped in a timer. If the full eligibility check takes longer than **500ms**, a WARNING event is automatically logged to `logs/app.log` and Sentry:

```php
if ($time > 500) {
    trackEvent('Slow AI Calculation', ['time_ms' => $time], 'WARNING');
}
```

---

## Files Reference

| File | Responsibility |
|------|---------------|
| `includes/ai_engine.php` | Main engine: eligibility check, programme evaluation, scholarship matching, recommendation generation |
| `includes/GradeMapper.php` | Grade-to-points conversion tables and min/max pass thresholds |
| `api/check-eligibility.php` | API endpoint that calls `AIEngine::checkEligibility()` and saves results to DB |
| `student/results.php` | Displays fit bars, confidence badges, gap analysis, and scholarship matches to the student |
| `sql/setup.sql` | Seeds all 16 programmes, entry requirements with weights, and 25 real scholarships |
