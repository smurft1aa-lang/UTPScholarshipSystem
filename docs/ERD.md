# UTP Scholarship System - Entity Relationship Diagram

This diagram visualizes the database table relationships mapping students to their qualifications, applications, and the AI evaluation engine outputs.

```mermaid
erDiagram
    users ||--o{ qualifications : has
    users ||--o{ applications : tracks
    users ||--o{ documents : owns
    qualifications ||--|{ grades : contains
    programmes ||--o{ entry_requirements : enforces
    scholarships }|--|{ programmes : valid_for
    applications ||--|{ eligibility_results : outputs
    users ||--o{ audit_log : causes
```
