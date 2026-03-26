describe('Student Pages', () => {
  it('loads the signup page with all required fields', () => {
    cy.visit('/auth/signup.php')
    cy.get('input[name="full_name"]').should('be.visible')
    cy.get('input[name="email"]').should('be.visible')
    cy.get('input[name="ic_number"]').should('be.visible')
    cy.get('input[name="phone"]').should('be.visible')
    cy.get('input[name="password"]').should('be.visible')
    cy.get('button[type="submit"]').should('be.visible')
  })

  it('loads the check-eligibility page structure', () => {
    // This page requires login, should redirect to login
    cy.visit('/student/check-eligibility.php')
    cy.url().should('include', '/auth/login.php')
  })

  it('loads the results page structure', () => {
    // This page requires login, should redirect to login
    cy.visit('/student/results.php')
    cy.url().should('include', '/auth/login.php')
  })
})
