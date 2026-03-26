/**
 * Cypress E2E Tests — Edge Cases & Failure Scenarios
 *
 * Tests error handling, invalid inputs, unauthorized access,
 * and boundary conditions that the happy-path tests don't cover.
 */

const BASE_URL = Cypress.config('baseUrl') || 'http://localhost:8000';

describe('Authentication Edge Cases', () => {
    it('should reject login with empty fields', () => {
        cy.visit(`${BASE_URL}/auth/login.php`);
        cy.get('form').submit();
        // Should stay on login page or show validation error
        cy.url().should('include', 'login');
    });

    it('should reject login with wrong password', () => {
        cy.visit(`${BASE_URL}/auth/login.php`);
        cy.get('input[name="email"]').type('admin@utp.edu.my');
        cy.get('input[name="password"]').type('TotallyWrong!');
        cy.get('form').submit();
        cy.url().should('include', 'login');
        cy.get('.toast-error, .alert-danger, .error-message, [role="alert"]')
            .should('exist');
    });

    it('should reject signup with weak password', () => {
        cy.visit(`${BASE_URL}/auth/signup.php`);
        cy.get('input[name="full_name"]').type('Test Edge Case');
        cy.get('input[name="email"]').type('edgecase@test.com');
        cy.get('input[name="ic_number"]').type('990101010001');
        cy.get('input[name="phone"]').type('0123456789');
        cy.get('input[name="password"]').type('weak');
        cy.get('input[name="confirm_password"]').type('weak');
        cy.get('form').submit();
        // Should not redirect to dashboard
        cy.url().should('include', 'signup');
    });

    it('should reject signup with mismatched passwords', () => {
        cy.visit(`${BASE_URL}/auth/signup.php`);
        cy.get('input[name="full_name"]').type('Test Mismatch');
        cy.get('input[name="email"]').type('mismatch@test.com');
        cy.get('input[name="ic_number"]').type('990101010002');
        cy.get('input[name="phone"]').type('0123456780');
        cy.get('input[name="password"]').type('Strong@1234');
        cy.get('input[name="confirm_password"]').type('Different@1234');
        cy.get('form').submit();
        cy.url().should('include', 'signup');
    });
});

describe('Authorization Guards', () => {
    it('should redirect unauthenticated user from student dashboard', () => {
        cy.visit(`${BASE_URL}/student/dashboard.php`);
        cy.url().should('include', 'login');
    });

    it('should redirect unauthenticated user from check-eligibility', () => {
        cy.visit(`${BASE_URL}/student/check-eligibility.php`);
        cy.url().should('include', 'login');
    });

    it('should redirect unauthenticated user from admin dashboard', () => {
        cy.visit(`${BASE_URL}/admin/dashboard.php`);
        cy.url().should('include', 'login');
    });
});

describe('Eligibility Form Validation', () => {
    beforeEach(() => {
        // Login as student first
        cy.visit(`${BASE_URL}/auth/login.php`);
        cy.get('input[name="email"]').type('azimsanji@gmail.com');
        cy.get('input[name="password"]').type('Sanji123@');
        cy.get('form').submit();
        cy.url().should('include', 'dashboard');
    });

    it('should not submit eligibility form without selecting qualification', () => {
        cy.visit(`${BASE_URL}/student/check-eligibility.php`);
        // Try to submit without selecting qualification type
        cy.get('button[type="submit"], input[type="submit"]').first().click();
        // Should stay on the check-eligibility page
        cy.url().should('include', 'check-eligibility');
    });
});

describe('404 and Invalid Routes', () => {
    it('should handle non-existent page gracefully', () => {
        cy.request({
            url: `${BASE_URL}/totally-fake-page.php`,
            failOnStatusCode: false
        }).then((response) => {
            expect(response.status).to.be.oneOf([404, 302, 301]);
        });
    });

    it('should handle non-existent API endpoint gracefully', () => {
        cy.request({
            url: `${BASE_URL}/api/fake-endpoint.php`,
            failOnStatusCode: false
        }).then((response) => {
            expect(response.status).to.be.oneOf([404, 302, 301]);
        });
    });
});
