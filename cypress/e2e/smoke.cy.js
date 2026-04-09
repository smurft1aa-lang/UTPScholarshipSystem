/**
 * Smoke Tests — UTP Scholarship System
 *
 * Validates that critical pages load successfully and render
 * essential UI elements. These tests require the app to be running
 * at http://localhost:8080 (via `make up` or `docker-compose up`).
 */

describe('Landing Page', () => {
  it('loads the public landing page', () => {
    cy.visit('/landing.php');
    cy.get('body').should('be.visible');
    cy.title().should('not.be.empty');
  });

  it('contains navigation links to login and signup', () => {
    cy.visit('/landing.php');
    cy.get('a[href*="login"]').should('exist');
    cy.get('a[href*="signup"]').should('exist');
  });
});

describe('Login Page', () => {
  it('loads the login form', () => {
    cy.visit('/auth/login.php');
    cy.get('form').should('exist');
    cy.get('input[name="email"]').should('be.visible');
    cy.get('input[name="password"]').should('be.visible');
  });

  it('shows an error on invalid credentials', () => {
    cy.visit('/auth/login.php');
    cy.get('input[name="email"]').type('nonexistent@test.com');
    cy.get('input[name="password"]').type('WrongPassword1!');
    cy.get('form').submit();
    cy.get('.alert, .error, .message').should('exist');
  });
});

describe('Signup Page', () => {
  it('loads the registration form', () => {
    cy.visit('/auth/signup.php');
    cy.get('form').should('exist');
    cy.get('input[name="full_name"]').should('be.visible');
    cy.get('input[name="email"]').should('be.visible');
    cy.get('input[name="password"]').should('be.visible');
  });
});

describe('Protected Routes Redirect', () => {
  it('redirects unauthenticated users away from student dashboard', () => {
    cy.visit('/student/dashboard.php', { failOnStatusCode: false });
    cy.url().should('include', 'login');
  });

  it('redirects unauthenticated users away from admin dashboard', () => {
    cy.visit('/admin/dashboard.php', { failOnStatusCode: false });
    cy.url().should('include', 'login');
  });
});
