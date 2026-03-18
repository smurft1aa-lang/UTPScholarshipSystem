describe('Authentication Flow', () => {
  beforeEach(() => {
    // Navigate to the local server
    cy.visit('/')
  })

  it('successfully loads the landing page', () => {
    cy.contains('UTP Scholarship & Course Eligibility System')
    cy.get('a[href="/auth/login.php"]').should('be.visible')
  })

  it('allows a user to navigate to the login page', () => {
    cy.get('a[href="/auth/login.php"]').first().click()
    cy.url().should('include', '/auth/login.php')
    cy.get('form').should('be.visible')
    cy.get('input[name="ic_number"]').should('be.visible')
    cy.get('input[name="password"]').should('be.visible')
  })

  it('shows an error on invalid login', () => {
    cy.visit('/auth/login.php')
    cy.get('input[name="ic_number"]').type('999999-99-9999')
    cy.get('input[name="password"]').type('WrongPassword123!')
    cy.get('button[type="submit"]').click()
    
    // Should show error message
    cy.get('.alert-danger').should('be.visible')
  })
})
